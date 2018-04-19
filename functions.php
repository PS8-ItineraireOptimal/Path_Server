<?php

include_once("classes.php");
include_once("geometry.php");

function findNearestNode($x, $y, $bdd, $delta)
{
	$node = null;
	$distance = INF;
	$aabb = computeAABBFromCenter($x, $y, $delta);
	
	$req = $bdd->query("SELECT id, x, y, FROM nodes WHERE (x>'$aabb->x_min' AND x<'$aabb->x_max' AND y>'$aabb->y_min' AND y<'$aabb->y_max')");
	while ($res = $req->fetch_assoc())
	{
		$d = distanceCC($x, $y, $res['x'], $res['y']);
		if ($distance > $d)
		{
			$node = new Node($res['id'], $res['x'], $res['y']);
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
function best_path_through_stations(Node $depart, Node $arrivee,$start_energy,$end_energy,Graph $g,$bestStations)
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
			$starting_level = 100;

		for ($j=$i+1; $j <= count($nodes_new_graph)-1 ; $j++) 
		{ 

			if($j == count($nodes_new_graph)-1)
				$limit_energy = $end_energy;
			else
				$limit_energy = 5;

			$path = $astar->get_best_path($nodes_new_graph[$i],$nodes_new_graph[$j]);
			
			$energy_cons = $astar->get_path_energy($path);

			$travel_time = $astar->get_path_time($path);
			if($starting_level - $energy_cons >= $limit_energy)
			{
				$new_arc= new Arc($id_new_arc,$nodes_new_graph[$i]->id,$nodes_new_graph[$j]->id,$travel_time,$energy_cons);
				$new_graph->arcs[]=$new_arc;
				$id_new_arc++;
			}

		}
	}

	if($new_graph->find_node_in_graph($arrivee->id) != null)
	{

		$new_astar = new Astar($new_graph);
		$final_path = $new_astar->get_best_path($depart,$arrivee);

		print("Le meilleur chemin de ".$depart->id." à ".$arrivee->id." est : ");
		foreach ($final_path as $key => $value) 
		{
			print($value->id."->");
		}

		$last_arc = array($final_path[count($final_path)-2],$arrivee);
		$energy= 100 - $new_astar->get_path_energy($last_arc);
		print("<p> Energie restante : ".$energy." <br/> Travel time : ".$new_astar->get_path_time($final_path)."</p>");

		return $final_path;
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

?>