<?php

include_once("classes.php");
include_once("geometry.php");
include_once ('change_projection.php');

function findNearestNode($x, $y, $bdd, $delta)
{
	$node = null;
	$distance = INF;
	$aabb = computeAABBFromCenter($x, $y, $delta);
	
	$req = $bdd->query("SELECT id_noeud, lon, lat FROM nodes WHERE (lon>'$aabb->x_min' AND lon<'$aabb->x_max' AND lat>'$aabb->y_min' AND lat<'$aabb->y_max')");
	while ($res = $req->fetch_assoc())
	{
		$d = distanceCC($x, $y, $res['lon'], $res['lat']);
		if ($distance > $d)
		{
			$node = new Node($res['id_noeud'], $res['lon'], $res['lat']);
			$distance = $d;
		}
	}
	
	if ($node != null)
	{
		return $node;
	}
	else
	{
		// If no node found, extend the AABB area -> Should (very) rarely happend
		return findNearestNode($x, $y, $bdd, 2 * $delta);
	}
}


//A commenter par Yves
function best_path_through_stations(Node $depart, Node $arrivee,$start_energy,$end_energy,$battery_capacity,Graph $g,$bestStations=array())
{
	$astar = new Astar($g);
	$nodes_new_graph = array_merge_recursive(array($depart),$bestStations,array($arrivee));
	$new_graph = new Graph($nodes_new_graph,array());
	$id_new_arc = 0;
	//calcul du chemin
	for($i=0; $i<=count($nodes_new_graph)-2;$i++)
	{
		if($i == 0)
			$starting_level = $start_energy;
		else
			$starting_level = $battery_capacity;

		for ($j=$i+1; $j <= count($nodes_new_graph)-1 ; $j++) 
		{ 

			if($j == count($nodes_new_graph)-1)
				$limit_energy = $end_energy;
			else
				$limit_energy = 5.0/$battery_capacity;

			$path = $astar->get_best_path($nodes_new_graph[$i],$nodes_new_graph[$j]);
			
			$energy_cons = $astar->get_path_energy($path);

			$length = $astar->get_path_length($path);

			$travel_time = $astar->get_path_time($path);

			if($starting_level - $energy_cons >= $limit_energy)
			{
				$new_arc= new Arc($id_new_arc, $nodes_new_graph[$i]->id, $nodes_new_graph[$j]->id, $travel_time, $energy_cons, $length);
				$new_graph->arcs[]=$new_arc;
				$id_new_arc++;
			}

		}
	}

	if(($new_graph->find_arc($arrivee->id) != null)  && ($new_graph->find_arc($depart->id) != null))
	{

		$new_astar = new Astar($new_graph);
		$final_path = $new_astar->get_best_path($depart,$arrivee);

		return array('astar'=>$new_astar,'path'=>$final_path);
	}
	else
	{
		print("Il n'y a pas d'itinéraire permettant d'arriver à destination avec le niveau de batterie désiré");
		return null;
	}

}

//A commenter
function get_car_battery_capacity($car_model,$bdd)
{
	$req=$bdd->query("SELECT Battery FROM car WHERE Modele LIKE '".$car_model."';");

	$res=$req->fetch_assoc();

	return $res['Battery'];
}

function get_waypoints($path)
{
	$waypoints = array();

	foreach ($path as $key => $value) 
	{
		//projection de L93 vers WGS84
	 	$projection = from_L93_to_WGS($value->x,$value->y);
		$lon = $projection->toArray()[0];
		$lat = $projection->toArray()[1];

		//enregistrement de la latitude et de la longitude
		$waypoints[]=array('lat'=>$lat,'lng'=>$lon);

	} 

	return $waypoints;
}

function get_stats(astar $astar, $path, $battery_capacity)
{
	$stats = array();

	//Ajout de la longueur 
	$stats['distance'] = $astar->get_path_length($path);

	//Ajout du temps de trajet (Temps de recharge dans les stations non pris en compte)
	$stats['time'] = $astar->get_path_time($path);

	//Ajout du nombre de stations
	$stats['nbStations'] = count($path) - 2;

	//Ajout de l'energie restante à l'arrivée (en pourcentage)
	$last_arc = array($path[count($path)-2], $path[count($path)-1]);
	$energy = ( ($battery_capacity - $astar->get_path_energy($last_arc)) / $battery_capacity) * 100;
	$stats['energy'] = $energy;

	return $stats;
}

?>