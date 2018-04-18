<?php

include_once("stations.php");
include_once("geometry.php");

//////////////////////////////////////////////////////
//
// Chargement des entrées
//
//////////////////////////////////////////////////////

$bdd = get_bdd();

$bestAmount = 3;
$delta = 10000; // 10km

$i = findNearestNode($_GET['ix'], $_GET['iy'], $bdd, $delta);
$j = findNearestNode($_GET['jx'], $_GET['jy'], $bdd, $delta);
$Ei = $_GET['Ei'];
$Ej = $_GET['Ej'];
// TODO : Car


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