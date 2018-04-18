<?php

/*include_once("stations.php");
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
}*/

?>

<?php

include('classes.php');

?>

<?php

//------------Graphe test---------------

//tableau de noeuds
$n = array();
$i = 0; $n[$i] = new Node($i+1,1,1); 
$i = $i+1; $n[$i] = new Node($i+1,2,2); 
$i = $i+1; $n[$i] = new Node($i+1,2,0); 
$i = $i+1; $n[$i] = new Node($i+1,4,2); 
$i = $i+1; $n[$i] = new Node($i+1,4,0); 
$i = $i+1; $n[$i] = new Node($i+1,6,1);
$i = $i+1; $n[$i] = new Node($i+1,8,2); 
$i = $i+1; $n[$i] = new Node($i+1,8,0); 
$i = $i+1; $n[$i] = new Node($i+1,11,0); 
$i = $i+1; $n[$i] = new Node($i+1,11,2); 
$i = $i+1; $n[$i] = new Node($i+1,10,1);
$i = $i+1; $n[$i] = new Node($i+1,13,1);

//tableau des arcs
$a=array();
$j=0; $a[$j]= new Arc($j,1,2,1,10);
$j=$j+1; $a[$j]= new Arc($j,1,3,3,10);
$j=$j+1; $a[$j]= new Arc($j,2,4,2,10);
$j=$j+1; $a[$j]= new Arc($j,2,5,3,10);
$j=$j+1; $a[$j]= new Arc($j,3,2,2,10);
$j=$j+1; $a[$j]= new Arc($j,3,5,4,10);
$j=$j+1; $a[$j]= new Arc($j,4,5,5,10);
$j=$j+1; $a[$j]= new Arc($j,4,6,4,10);
$j=$j+1; $a[$j]= new Arc($j,4,7,1,10);
$j=$j+1; $a[$j]= new Arc($j,5,6,3,10);
$j=$j+1; $a[$j]= new Arc($j,5,8,2,10);
$j=$j+1; $a[$j]= new Arc($j,6,7,3,10);
$j=$j+1; $a[$j]= new Arc($j,6,8,1,10);
$j=$j+1; $a[$j]= new Arc($j,7,8,5,10);
$j=$j+1; $a[$j]= new Arc($j,7,11,3,10);
$j=$j+1; $a[$j]= new Arc($j,7,10,4,10);
$j=$j+1; $a[$j]= new Arc($j,8,11,2,10);
$j=$j+1; $a[$j]= new Arc($j,8,9,1,10);
$j=$j+1; $a[$j]= new Arc($j,11,10,2,10);
$j=$j+1; $a[$j]= new Arc($j,10,12,3,10);
$j=$j+1; $a[$j]= new Arc($j,9,12,2,10);

//instanciation du graphe
$g = new Graph($n,$a);


//---------données simulation-------------
$depart = $n[0];
$arrivee = $n[11];
$start_energy = 100;
$end_energy = 10;

//-------meilleures stations-----------------
$stations=array($n[3],$n[9]);

//---------Calcul du meilleur chemin passant par N stations---------------

	//---------instanciation de la classe astar------------
	$astar = new Astar($g);

	//calcul du chemin
	$good=0;
	$nStation=0;
	$path=array();
	$final_path=array();
	$energy = $start_energy;


	while($good == 0)
	{
		if($nStation == 0)
		{
			$path = $astar->get_best_path($depart,$arrivee);
			$energy = $astar->get_path_energy($path);
			if(($start_energy - $energy) < $end_energy)
			{
				$nStation++;
			}
			else
			{
				$good = 1;
				$energy = $start_energy - $energy;
				$final_path = $path;
			}
		}
	}

	print("Le meilleur chemin de ".$depart->id." à ".$arrivee->id." est : ");
	foreach ($final_path as $key => $value) 
	{
		print($value->id."->");
	}

	print("<p> Energie restante : ".$energy." <br/> Travel time : ".$astar->get_path_time($final_path)."</p>");











?>
