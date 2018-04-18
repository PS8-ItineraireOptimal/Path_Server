<?php

include_once("stations.php");
include_once("algorithm.php");

//////////////////////////////////////////////////////
//
// Chargement des entrées
//
//////////////////////////////////////////////////////

$bdd = get_bdd();

$iNode = new Node();
$iNode->x = $GET['ix'];
$iNode->y = $GET['iy'];

$jNode = new Node();
$jNode->x = $GET['jx'];
$jNode->y = $GET['jy'];

$bestAmount = 3;

// TODO : Car
// TODO : Ei
// TODO : Ej

// TODO : Conversion x/y -> Node


//////////////////////////////////////////////////////
//
// Itinéraire
//
//////////////////////////////////////////////////////

// Calcul avec 0 stations
$path = algorithm($i, $j, $Ei, $Ej);
if ($path->isValid())
{
	$path->output();
}

// Generation de la carte des stations dans le secteur restreint
$stations = generateStations($i, $j, 10000, $bdd);

// Tests avec des stations
for ($n = 1; $n <= 4; $n++)
{
	// Calcul uniquement sur les meilleures stations 
	// Réduit considérablement le nombre de calcul pour n > 1
	simplifyStations($n, $stations);
	
	// Determine les bestAmount meilleurs chemins possibles
	$bestStations = bestStations($n, $i, $j, $stations, $bestAmount);
	
	// TODO : A changer ici mais c'est pour que vous compreniez l'idéee
	
	// Calcul avec n stations
	$path = algorithm($i, $j, $Ei, $Ej, $bestStations);
	if ($path->isValid())
	{
		$path->output();
		break;
	}
}

?>