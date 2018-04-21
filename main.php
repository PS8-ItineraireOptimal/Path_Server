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

$waypoints = array();
$stats = array();

if($result != null)
{
	$waypoints = get_waypoints($result['path']);
	$stats = get_stats($result['astar'], $result['path'], $battery_capacity);
}
else
{
	$stations = generateStations($start, $finish, $delta, $bdd);

	for ($n = 1; $n <= 4; $n++)
	{
		//simplifyStations();

		$bestStations = bestStations($n, $start, $finish, $stations, $bestAmount);
		$nbPathsStations = count($bestStations);
		$bestPaths = array();

		// On calcule tous les chemins
		for ($i = 0; $i < $nbPathsStations; $i++)
		{
			$nodeStations=array();
			foreach ($bestStations[0] as $key => $value) 
			{
				$nodeStations[] = $g->find_node_in_graph($value);
			}

			$bestPaths[$i] = best_path_through_stations($start, $finish, $Ei, $Ej, $battery_capacity, $g, $nodeStations);
			if ($bestPaths[$i] == null)
			{
				unset($bestPaths[$i]);
			}
		}

		$bestPaths = array_values($bestPaths);
		$nbValidPaths = count($bestPaths);

		if ($nbValidPaths > 0)
		{
			// On tri selon le temps
			usort($bestPaths, function($a, $b)
			{
				if ($a['astar']->get_path_time($a['path']) == $b['astar']->get_path_time($b['path']))
				{
					return 0;
				}
				return ($a['astar']->get_path_time($a['path']) < $b['astar']->get_path_time($b['path'])) ? -1 : 1;
			});

			$waypoints = get_waypoints($bestStations[0]['path']);
			$stats = get_stats($bestStations[0]['astar'], $bestStations[0]['path'], $battery_capacity);
		}
	}
}



?>