<?php

include_once("stations.php");
include_once("geometry.php");
include_once('change_projection.php');

//////////////////////////////////////////////////////
//
// Chargement des entrées
//
//////////////////////////////////////////////////////

$bdd = get_bdd();

$bestAmount = 3;
$delta = 10000; // 10km

// Projection de WSG84 vers Lambert93
$project_start_node = from_WGS_to_L93($_POST['ilng'],$_POST['ilat']);
$start_x = $project_start_node->toArray()[0];
$start_y = $project_start_node->toArray()[1];

$project_finish_node = from_WGS_to_L93($_POST['jlng'],$_POST['jlat']);
$finish_x = $project_finish_node->toArray()[0];
$finish_y = $project_finish_node->toArray()[1];

// trouver les noeuds du graph les plus proches du départ et de l'arrivée
$start = findNearestNode($start_x, $start_y, $bdd, $delta);
$finish = findNearestNode($finish_x, $finish_y, $bdd, $delta);

//récuperer la  capacité totale de la batterie du véhicule dans la BDD
$car_model=$_POST['VE'];
$battery_capacity=get_car_battery_capacity($car_model,$bdd);

//Passer des énergies en pourcentages en énergies en kWh
$Ei = $_POST['startEnergyInName']*$battery_capacity;
$Ej = $_POST['endEnergyInName']*$battery_capacity;

//Récupérer les noeuds et arcs du graphe dans la BDD
$g = new Graph();
$g->get_graph_from_bdd($start,$finish,$delta,$bdd);

//////////////////////////////////////////////////////
//
// Itinéraire
//
//////////////////////////////////////////////////////

// Calcul avec 0 stations

$result = best_path_through_stations($start, $finish, $Ei, $Ej, $battery_capacity, $g);
if($result != null)
{
	print("Le meilleur chemin");
	foreach ($result['path'] as $key => $value) 
	{
		print($value->id."->");
	}
	print("<br/>");

	$waypoints = get_waypoints($result['path']);
	$stats = get_stats($result['astar'], $result['path'], $battery_capacity);
}
else
{

	// Generation de la carte des stations dans le secteur restreint
	//$stations = generateStations($i, $j, 10000, $bdd);

	// Tests avec des stations

	print("<p>test avec plusieurs stations</p>");
/*
	for ($n = 1; $n <= 4; $n++)
	{
		// // Calcul uniquement sur les meilleures stations 
		// // Réduit considérablement le nombre de calcul pour n > 1
		// simplifyStations($n, $stations);
		
		// // Determine les bestAmount meilleurs chemins possibles
		// $bestStations = bestStations($n, $i, $j, $stations, $bestAmount);
		
		// // TODO : A changer ici mais c'est pour que vous compreniez l'idéee
		
		// // Calcul avec n stations
		// $path = algorithm($i, $j, $Ei, $Ej, $bestStations);
		// if ($path->isValid())
		// {
		// 	$path->output();
		// 	break;
		// }
	}*/

	$waypoints = array();
	$stats = array();
}



?>