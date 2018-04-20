<?php

include_once("bdd.php");
include_once("geometry.php");
include_once("functions.php");
include_once('change_projection.php');

$bdd = get_bdd();
$bestAmount = 3;
$delta = 10000; // 10km

// Projection de WSG84 vers Lambert93
$project_start_node = from_WGS_to_L93($_GET['ilng'],$_GET['ilat']);
$start_x = $project_start_node->toArray()[0];
$start_y = $project_start_node->toArray()[1];

$project_finish_node = from_WGS_to_L93($_GET['jlng'],$_GET['jlat']);
$finish_x = $project_finish_node->toArray()[0];
$finish_y = $project_finish_node->toArray()[1];

// trouver les noeuds du graph les plus proches du départ et de l'arrivée
$start = findNearestNode($start_x, $start_y, $bdd, $delta);
$finish = findNearestNode($finish_x, $finish_y, $bdd, $delta);

echo $start->id . " " . $finish->id;

?>