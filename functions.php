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

?>