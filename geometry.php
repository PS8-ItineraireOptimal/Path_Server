<?php

// Axis Aligned Bounding Box : usefull for occlusion
class AABB
{
	public $x_min;
	public $x_max;
	public $y_min;
	public $y_max;
}

// Distance between two nodes
function distanceNN($node_i, $node_j)
{
	$dx = $node_i->x - $node_j->x;
	$dy = $node_i->y - $node_j->y;
	return sqrt(dx * dx + dy * dy);
}

// Distance between a node and a couple of coordinates
function distanceNC($node, $x, $y)
{
	return sqrt($node->x * $x + $node->y * $y);
}

// Distance between two couples of coordinates
function distanceCC($x1, $y1, $x2, $y2)
{
	return sqrt(($x1-$x2)*($x1-$x2) + ($y1-$y2)*($y1-$y2));
}

// Compute AABB from I and J with some delta
function computeAABBFromNodes($i, $j, $delta)
{
	$aabb = new AABB();
	$aabb->x_min = min($i->x, $j->x) - $delta;
	$aabb->x_max = max($i->x, $j->x) + $delta;
	$aabb->y_min = min($i->y, $j->y) + $delta;
	$aabb->y_max = max($i->y, $j->y) - $delta;
	return $aabb;
}

// Compute AABB from a couple of coordinates with some delta
function computeAABBFromCenter($x, $y, $delta)
{
	$aabb = new AABB();
	$aabb->x_min = $x - $delta;
	$aabb->x_max = $x + $delta;
	$aabb->y_min = $y + $delta;
	$aabb->y_max = $y - $delta;
	return $aabb;
}

?>