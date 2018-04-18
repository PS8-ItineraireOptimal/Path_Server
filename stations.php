<?php

include_once("bdd.php");
include_once("geometry.php");
include_once("classes.php");

// Represent a station
public class Station extends Node
{
	public $d;
	
	function __construct($n_id, $x, $y, $d)
	{
		$this->id = $n_id;
		$this->x = $x;
		$this->y = $y;
		$this->d = $d;
	}
}

// Get stations in the area from the database
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
// If n>1, stations need to be sorted from best-to-worst distances
function simplifyStations($n, &$stations)
{
	// 1 and 0 don't need simplifications
	if ($n <= 1)
	{
		return;
	}
	
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

// Get the wantedAmount best stations depending on the value of n
function bestStations($n, $i, $j, &$stations, $wantedAmount)
{
	$temp_paths = array();
	$amount = 0;
	$paths = array();
	$size = count($stations);
	
	if ($n == 1)
	{
		// Case n=1 is an exception compared to the others
		// Here we don't use temp_paths
		// We use the computed distance holded in the station
		// This distance will be reused to sort the stations according to their importance
		
		// Compute distance
		for ($u = 0; $u < $size; $u++)
		{
			$stations[$u]->d = distanceNN($i, $stations[$u]) + distanceNN($stations[$u], $j);
		}
		
		// Sort using distance
		usort($stations, function($a, $b)
		{
			if ($a->d == $b->d)
			{
				return 0;
			}
			return ($a->d < $b->d) ? -1 : 1;
		});
		
		// Build paths
		for ($a = 0; $a < $wantedAmount; $a++)
		{
			$p = array();
			for ($b = 0; $b < $n; $b++)
			{
				array_push($p, $b, $stations[$b]->id);
			}
			array_push($paths, $a, $p);
		}
		
		return $paths;
	}
	else if ($n == 2)
	{
		// Compute distance
		for ($u = 0; $u < $size; $u++)
		{
			for ($v = 0; $v < $size; $v++)
			{
				if ($u != $v)
				{
					$temp_path = array();
					array_push($temp_path, 'd', distanceNN($i, $stations[$u]) + distanceNN($stations[$u], $stations[$v]) + distanceNN($stations[$v], $j));
					array_push($temp_path, 0, $stations[$u]->id);
					array_push($temp_path, 1, $stations[$v]->id);
					array_push($temp_paths, $amount++, $temp_path);
				}
			}
		}
	}
	else if ($n == 3)
	{
		// Compute distance
		for ($u = 0; $u < $size; $u++)
		{
			for ($v = 0; $v < $size; $v++)
			{
				for ($w = 0; $w < $size; $w++)
				{
					if ($u != $v && $u != $w && $v != $w)
					{
						$temp_path = array();
						array_push($temp_path, 'd', distanceNN($i, $stations[$u]) + distanceNN($stations[$u], $stations[$v]) + distanceNN($stations[$v], $stations[$w]) + distanceNN($stations[$w], $j));
						array_push($temp_path, 0, $stations[$u]->id);
						array_push($temp_path, 1, $stations[$v]->id);
						array_push($temp_path, 2, $stations[$w]->id);
						array_push($temp_paths, $amount++, $temp_path);
					}
				}
			}
		}
	}
	else if ($n == 4)
	{
		// Compute distance
		for ($u = 0; $u < $size; $u++)
		{
			for ($v = 0; $v < $size; $v++)
			{
				for ($w = 0; $w < $size; $w++)
				{
					for ($x = 0; $x < $size; $x++)
					{
						if ($u != $v && $u != $w && $u != $x && $v != $w && $v != $x && $w != $x)
						{
							$temp_path = array();
							array_push($temp_path, 'd', distanceNN($i, $stations[$u]) + distanceNN($stations[$u], $stations[$v]) + distanceNN($stations[$v], $stations[$w]) + distanceNN($stations[$w], $stations[$x]) + distanceNN($stations[$x], $j));
							array_push($temp_path, 0, $stations[$u]->id);
							array_push($temp_path, 1, $stations[$v]->id);
							array_push($temp_path, 2, $stations[$w]->id);
							array_push($temp_path, 2, $stations[$x]->id);
							array_push($temp_paths, $amount++, $temp_path);
						}
					}
				}
			}
		}
	}
	else
	{
		// Not possible case
		return null;
	}
	
	
	// In case n>1 :
		
	// Sort using distance
	usort($temp_paths, function($a, $b)
	{
		if ($a['d'] == $b['d'])
		{
			return 0;
		}
		return ($a['d'] < $b['d']) ? -1 : 1;
	});
	
	// Build paths
	for ($a = 0; $a < $wantedAmount; $a++)
	{
		$p = array();
		for ($b = 0; $b < $n; $b++)
		{
			array_push($p, $b, $temp_paths[$a][$b]);
		}
		array_push($paths, $a, $p);
	}
	
	return $paths;
}

?>