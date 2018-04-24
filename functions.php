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
function best_path_through_stations(Node $depart, Node $arrivee,$start_energy,$end_energy,$battery_capacity,$bestStations=array())
{
	global $astar;
	$nodes_path = array_merge_recursive(array($depart),$bestStations,array($arrivee));
	$final_path = array();
	$total_length = 0;
	$total_travel_time = 0;
	$remaining_energy = 0; 


	//debug
	/*print("<p> Id du tableau node_new_graph :</p>");
	foreach ($nodes_path as $key => $value) 
	{
		print($value->id." -> ");
	}*/
	//fin debug
		
	//ajout du depart au chemin finale
	$final_path[] = $nodes_path[0];

	//calcul du chemin allant du depart à l'arrivée et passant par les stations du tableau 
	//$bestStations
	for($i=0; $i<=count($nodes_path)-2;$i++)
	{

		if($i == 0)
			$starting_level = $start_energy;
		else
			$starting_level = $battery_capacity;

		$j = $i+1;
		if($j == count($nodes_path)-1)
			$limit_energy = $end_energy;
		else
			$limit_energy = 0.05*$battery_capacity;


		$path = $astar->get_best_path($nodes_path[$i],$nodes_path[$j]);

		$energy_cons = $astar->get_path_energy($path);

		$length = $astar->get_path_length($path);

		$travel_time = $astar->get_path_time($path);

		if($starting_level - $energy_cons >= $limit_energy)
		{
			$total_length = $total_length + $length;
			$total_travel_time = $total_travel_time + $travel_time;
			$remaining_energy = (($starting_level - $energy_cons)/$battery_capacity)*100; 
			$final_path[] = $nodes_path[$j];

		}
		else
			return null;

		
	}

	$total_travel_time = round($total_travel_time,4);
	$remaining_energy = round($remaining_energy,0,PHP_ROUND_HALF_DOWN);
	return array('path'=>$final_path,'time'=>$total_travel_time,'length'=>$total_length,'end_energy'=>$remaining_energy);

}

//Recupère la capacité totale de la batterie du véhicule dans la bdd
function get_car_battery_capacity($car_model,$bdd)
{
	$req=$bdd->query("SELECT Battery FROM car WHERE Modele LIKE '".$car_model."';");

	$res=$req->fetch_assoc();

	return $res['Battery'];
}

//Renvoie les coordonnées en WGS84 des stations de l'itinéraire
function get_waypoints($path,$bdd)
{
	$waypoints = array();

	for($i=1;$i<count($path)-1;$i++) 
	{
		//projection de L93 vers WGS84
		$path[$i]=get_station_near_node($path[$i],$bdd);
	 	$projection = from_L93_to_WGS($path[$i]->x,$path[$i]->y);
		$lon = $projection->toArray()[0];
		$lat = $projection->toArray()[1];

		//enregistrement de la latitude et de la longitude
		$waypoints[]=array('lat'=>$lat,'lng'=>$lon);

	} 

	return $waypoints;
}

//Replace the coordinates of a node by the nearest station coordinates to make the itinerary go through the station
function get_station_near_node(Node $noeud, $bdd)
{
	$req = $bdd -> query ("SELECT lat_station,lon_station
							FROM nodes
							WHERE id_noeud=".$noeud->id.";");

	$res = $req->fetch_assoc();

	$noeud->y = $res['lon_station'];
	$noeud->x = $res['lat_station'];

	return $noeud;
}

?>