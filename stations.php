<?php

include_once("bdd.php");

// Represent a node
public class Node
{
	public $id;
	public $x;
	public $y;
	
	public __construct($p_id, $p_x, $p_y, $p_d)
	{
		$this->id = $p_id;
		$this->x = $p_x;
		$this->y = $p_y;
	}
}

// Represent a station
public class Station extends Node
{
	public $d;
	
	public __construct($p_id, $p_x, $p_y, $p_d)
	{
		$this->id = $p_id;
		$this->x = $p_x;
		$this->y = $p_y;
		$this->d = $p_d;
	}
}

// Get stations in AABB from DB
function generateStations($i, $j, $delta, $db)
{
	$i = 0;
	$stations = array();
	$aabb = computeAABBFromNodes($i, $j, $delta);
	
	$req = $db->query("SELECT id, x, y, FROM nodes WHERE station==true AND (x>'$aabb->x_min' AND x<'$aabb->x_max' AND y>'$aabb->y_min' AND y<'$aabb->y_max')");
	while ($res = $req->fetch_assoc())
	{
		array_push($stations, $i++, new Station($res['id'], $res['x'], $res['y'], INF);
	}
	
	return $stations;
}

// Remove worst stations
function simplifyStations($n, &$stations)
{
	// 1 and 0 don't need simplifications
	if ($n <= 1)
	{
		return;
	}
	
	// Sort using the computed distance
	usort($stations, function($a, $b)
	{
		if ($a->d == $b->d)
		{
			return 0;
		}
		return ($a->d < $b->d) ? -1 : 1;
	});
	
	// Determine the new size
	$wantedSize = count($stations);
	if ($n == 2)
	{
		$wantedSize /= 100;
	}
	if ($n == 3)
	{
		$wantedSize /= 10;
	}
	
	// Remove the worst stations
	while (count($stations) > $wantedSize)
	{
		array_pop($stations);
	}
}

function bestStations($n, $i, $j, &$stations, $wantedAmount)
{
	$paths = array();
	$size = count($stations);
	
	if ($n == 1)
	{
		for ($u = 0; $u < $s; $u++)
		{
			$stations[$u]->d = distanceNN($i, $stations[$u]) + distanceNN($stations[$u], $j);
		}
	}
	else if ($n == 2)
	{
		// TODO
	}
	else if ($n == 3)
	{
		// TODO
	}
	else if ($n == 4)
	{
		// TODO
	}
	
	// TODO
	return;
}

?>