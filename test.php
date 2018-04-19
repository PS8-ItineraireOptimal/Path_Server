php

include_once("bdd.php");
include_once("functions.php");

$bdd = get_bdd();
$delta = 10000; // 10 km

$xPointA = 7136880.21;
$yPointA = 2997330.88;

$xPointB = 7126554.86;
$yPointB = 3003326.80;

$iNode = findNearestNode($xPointA, $yPointA, $bdd, $delta);
$jNode = findNearestNode($xPointB, $yPointB, $bdd, $delta);

echo $iNode->id . ' : ' . $iNode-id . '<br/>';
echo $jNode->id . ' : ' . $jNode-id . '<br/>';

?>