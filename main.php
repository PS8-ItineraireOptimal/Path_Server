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

// Projection de WGS84 vers Lambert93 des coordonnées des adresses de départ et d'arrivée
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


$delta = 20000; // 20km

// trouver les noeuds du graphe les plus proches du départ et de l'arrivée
$start = findNearestNode($start_x, $start_y, $bdd, $delta);
$finish = findNearestNode($finish_x, $finish_y, $bdd, $delta);

//Récupérer les noeuds et arcs du graphe dans la BDD
$bestAmount = 3;
$g = new Graph();
$g->get_graph_from_bdd($start,$finish,$delta,$bdd);
$astar = new Astar($g);

//////////////////////////////////////////////////////
//
// Itinéraire
//
//////////////////////////////////////////////////////

$result = null;
$waypoints = array();//Coordonnées des étapes du trajet
$stats = array();//Statistiques du trajet

// Calcul avec 0 stations
$result = best_path_through_stations($start, $finish, $Ei, $Ej, $battery_capacity);

if($result != null)
{
	$waypoints = array($start_WGS84,$finish_WGS84);
	$stats = array('distance'=>$result['length'],'energy'=>$result['end_energy'],'time'=>$result['time'],'nbStations'=>0);
}
else
{
	//récupérer les stations de la zone de calcul dans la BDD
	$stations = generateStations($start, $finish, $delta, $bdd);
	
	$n = 1;
	$bestPaths = array();
	while ($n <= 4 && count($bestPaths) == 0)
	{
		// Should not be done for small area
		// Only large area
		//simplifyStations();

		//Recherche des n meilleures stations
		$bestStations = bestStations($n, $start, $finish, $stations, $bestAmount);
		$nbPathsStations = count($bestStations);
		$bestPaths = array();

		// On calcule tous les chemins
		for ($i = 0; $i < $nbPathsStations; $i++)
		{
			$nodeStations=array();
			foreach ($bestStations[$i] as $key => $value) 
			{
				//Les éléments du tableau $bestStations[$i] sont des id de stations
				//On récupère les noeuds du graphe correspondant à ces id
				//et on les stocke dans le tableau $nodeStations
				$nodeStations[] = $g->find_node_in_graph($value);
			}

			//Calcul du chemin optimal passant par les stations du tableau nodeStations
			$bestPaths[$i] = best_path_through_stations($start, $finish, $Ei, $Ej, $battery_capacity, $nodeStations);
			if ($bestPaths[$i] == null)
			{
				//Les chemins impossibles à effectuer par le véhicule sont supprimés du tableau bestPaths
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

		//Waypoints contient les coordonnées WGS84 de toutes les étapes du trajet.
		//Stats contient les données du trajet.
		//waypoints et stats sont envoyés à la page Web result.php dans laquelle sont affichés l'itinéraire et ses statistiques
		$waypoints = array_merge_recursive(array($start_WGS84),get_waypoints($bestPaths[0]['path'],$bdd),array($finish_WGS84));
		$stats = array('distance'=>$bestPaths[0]['length'],'energy'=>$bestPaths[0]['end_energy'],'time'=>$bestPaths[0]['time'],'nbStations'=>$n);
	}
	else
	{
		print("<p><br/><br/><br/><br/>THERE IS NO PATH MATCHING WITH THE GIVEN PARAMETERS.</p>");
	}
}



?>