<?php

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

// Axis Aligned Bounding Box : usefull for occlusion
public class AABB
{
	public $x_min;
	public $x_max;
	public $y_min;
	public $y_max;
}

// Distance between two nodes
function distance($node_i, $node_j)
{
	$dx = $node_i->x - $node_j->x;
	$dy = $node_i->y - $node_j->y;
	return sqrt(dx * dx + dy * dy);
}

// Compute AABB from I and J with some delta
function computeAABB($i, $j, $delta_coef)
{
	$delta = distance($i, $j) / $delta_coef;
	
	$aabb = new AABB();
	$aabb->x_min = min($i->x, $j->x) - $delta;
	$aabb->x_max = max($i->x, $j->x) + $delta;
	$aabb->y_min = min($i->y, $j->y) + $delta;
	$aabb->y_max = max($i->y, $j->y) - $delta;
	
	return $aabb;
}

// Get stations in AABB from DB
function generateStations($i, $j, $delta_coef, $db_nodes)
{
	$i = 0;
	$stations = array();
	$aabb = computeAABB($i, $j, $delta_coef);
	
	$req = $db_nodes->query("SELECT id, x, y, FROM nodes WHERE station==true AND (x>'$aabb->x_min' AND x<'$aabb->x_max' AND y>'$aabb->y_min' AND y<'$aabb->y_max')");
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
			$stations[$u]->d = distance($i, $stations[$u]) + distance($stations[$u], $j);
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