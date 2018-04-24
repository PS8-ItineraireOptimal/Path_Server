<?php

include_once("stations.php");
include_once('bdd.php');
include_once("classes.php");
include_once("functions.php");
include_once("geometry.php");
include_once('change_projection.php');

//////////////////////////////////////////////////////
//
// Chargement des entrées
//
//////////////////////////////////////////////////////

$bdd = get_bdd();

// Projection de WGS84 vers Lambert93
$start_WGS84=array("lat"=>$_POST['ilat'],"lng"=>$_POST['ilng']);
$project_start_node = from_WGS_to_L93($_POST['ilng'],$_POST['ilat']);
$start_x = $project_start_node->toArray()[0];
$start_y = $project_start_node->toArray()[1];

$finish_WGS84=array("lat"=>$_POST['jlat'],"lng"=>$_POST['jlng']);
$project_finish_node = from_WGS_to_L93($_POST['jlng'],$_POST['jlat']);
$finish_x = $project_finish_node->toArray()[0];
$finish_y = $project_finish_node->toArray()[1];

//récuperer la  capacité totale de la batterie du véhicule dans la BDD
$car_model=$_POST['VE'];
$battery_capacity=get_car_battery_capacity($car_model,$bdd);

//Passer d'énergies en pourcentages à des énergies en kWh
$Ei = ($_POST['startEnergyInName']*$battery_capacity)/100.0;
$Ej = ($_POST['endEnergyInName']*$battery_capacity)/100.0;


$delta = 10000; // 10km

// trouver les noeuds du graph les plus proches du départ et de l'arrivée
$start = findNearestNode($start_x, $start_y, $bdd, $delta);
$finish = findNearestNode($finish_x, $finish_y, $bdd, $delta);

//Récupérer les noeuds et arcs du graphe dans la BDD
$bestAmount = 3;
$g = new Graph();
$g->get_graph_from_bdd($start,$finish,$delta,$bdd);
$astar = new Astar($g);
//debug
/*print("<br/><br/><br/><br/>");
if(($g->find_node_in_graph($start->id) == null) || ($g->find_node_in_graph($finish->id) == null))
{
	print("<p> Le départ ou l'arrivée ne sont pas dans le graphe</p>");
}
print("<p> id depart: ".$start->id." id arrivee:".$finish->id."</p>");
print("<p> nombre de noeuds ".count($g->nodes)."</p>");*/
//fin debug

//////////////////////////////////////////////////////
//
// Itinéraire
//
//////////////////////////////////////////////////////
// Calcul avec 0 stations

//debug
//print("<p> <h1>Sans passer par des stations</h1></p>");
//fin debug

$result = null;
$waypoints = array();
$stats = array();


$result = best_path_through_stations($start, $finish, $Ei, $Ej, $battery_capacity);



if($result != null)
{
	$waypoints = array($start_WGS84,$finish_WGS84);
	$stats = array('distance'=>$result['length'],'energy'=>$result['end_energy'],'time'=>$result['time'],'nbStations'=>0);
}
else
{
	//uncomment the code below when debuging
	//print("<p> <h1>Passer par des stations</h1></p>");
	//fin debug

	$stations = generateStations($start, $finish, $delta, $bdd);
	
	//uncomment the code below when debuging
	/*print("<p> Nombre de stations entre le depart et l'arrivée ".count($stations)." </p>");*/
	//fin debug

	$n = 0;
	$bestPaths = array();
	while ($n <= 4 && count($bestPaths) == 0 )
	{
		//simplifyStations();

		$bestStations = bestStations($n, $start, $finish, $stations, $bestAmount);
		$nbPathsStations = count($bestStations);
		$bestPaths = array();

		//uncomment the code below when debuging
		/*print("<p> n= ".$n."</p>");
		print("<p> Avec ".$n." stations</p>");
		print("<p> nbre d'éléments du tableau bestStations : ".count($bestStations)." </p>");
		print("<p> Id du tableau bestStations :</p>");
		foreach ($bestStations as $key1 => $value1) 
		{
			print("<p>");
			foreach ($bestStations[$key1] as $key2 => $value2) {
				print($value2." -> ");
			}
			print("</p>");
		}*/
		//fin debug

		// On calcule tous les chemins
		for ($i = 0; $i < $nbPathsStations; $i++)
		{
			$nodeStations=array();
			foreach ($bestStations[$i] as $key => $value) 
			{
				$nodeStations[] = $g->find_node_in_graph($value);
			}
			
			//debug
			/*print("<p> count du tableau nodeStations :".count($nodeStations)."</p>");
			print("<p> Id du tableau nodeStations :</p>");
			foreach ($nodeStations as $key => $value) 
			{
				print($value->id." -> ");
			}*/
			//fin debug

			$bestPaths[$i] = best_path_through_stations($start, $finish, $Ei, $Ej, $battery_capacity, $nodeStations);
			if ($bestPaths[$i] == null)
			{
				unset($bestPaths[$i]);
			}
		}

		$n++;
	}

	$bestPaths = array_values($bestPaths);
	$nbValidPaths = count($bestPaths);

	if ($nbValidPaths > 0)
	{
		// On tri les chemins par ordre de travel time croissant
		usort($bestPaths, function($a, $b)
		{
			if ($a['time'] == $b['time'])
			{
				return 0;
			}
			return ($a['time'] < $b['time']) ? -1 : 1;
		});

		$waypoints = array_merge_recursive(array($start_WGS84),get_waypoints($bestPaths[0]['path'],$bdd),array($finish_WGS84));
		$stats = array('distance'=>$bestPaths[0]['length'],'energy'=>$bestPaths[0]['end_energy'],'time'=>$bestPaths[0]['time'],'nbStations'=>$n);
	}
	else
	{
		print("<p>THERE IS NO PATH MATCHING WITH THE GIVEN PARAMETERS. <br/> THIS CAN BE DUE TO: 
			<ul>
				<li>A too low battery capacity, you should use an other car XD</li>
				<li>A too small area of calculations, you should increase the value of the variable delta at line 38 in main.php</li>
			</ul></P>");
	}
}



?>