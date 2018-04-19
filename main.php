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

//------------Récupération des arcs et des noeuds du graphe---------------

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
$start_energy = 50;
$end_energy = 10;

//-------meilleures stations-----------------
$stations=array($n[4],$n[10]);

//---------Calcul du meilleur chemin passant par N stations---------------

	//---------instanciation de la classe astar------------
	$astar = new Astar($g);

	/*$final_path=$astar->get_best_path($depart,$arrivee);

	print("Le meilleur chemin de ".$depart->id." à ".$arrivee->id." est : ");
	foreach ($final_path as $key => $value) 
	{
		print($value->id."->");
	}*/

	$nodes_new_graph = array_merge_recursive(array($depart),$stations,array($arrivee));
	$new_graph = new Graph($nodes_new_graph,array());
	$id_new_arc = 0;
	//calcul du chemin
	for($i=0; $i<=count($nodes_new_graph)-2;$i++)
	{
		if($i == 0)
			$starting_level = $start_energy;
		else
			$starting_level = 100;

		for ($j=$i+1; $j <= count($nodes_new_graph)-1 ; $j++) 
		{ 

			if($j == count($nodes_new_graph)-1)
				$limit_energy = $end_energy;
			else
				$limit_energy = 5;

			$path = $astar->get_best_path($nodes_new_graph[$i],$nodes_new_graph[$j]);
			
			$energy_cons = $astar->get_path_energy($path);

			$travel_time = $astar->get_path_time($path);
			if($starting_level - $energy_cons >= $limit_energy)
			{
				$new_arc= new Arc($id_new_arc,$nodes_new_graph[$i]->id,$nodes_new_graph[$j]->id,$travel_time,$energy_cons);
				$new_graph->arcs[]=$new_arc;
				$id_new_arc++;
			}

		}
	}

	if($new_graph->find_node_in_graph($arrivee->id) != null)
	{

		$new_astar = new Astar($new_graph);
		$final_path = $new_astar->get_best_path($depart,$arrivee);
		print("Le meilleur chemin de ".$depart->id." à ".$arrivee->id." est : ");
		foreach ($final_path as $key => $value) 
		{
			print($value->id."->");
		}

		$last_arc = array($final_path[count($final_path)-2],$arrivee);
		$energy= 100 - $new_astar->get_path_energy($last_arc);
		print("<p> Energie restante : ".$energy." <br/> Travel time : ".$new_astar->get_path_time($final_path)."</p>");
	}
	else
	{
		print("Il n'y a pas d'itinéraire permettant d'arriver à destination avec le niveau de batterie désiré");
	}

	/*foreach($new_graph->arcs as $keys => $value)
	{
		$value->print_arc();
	}*/

	/*foreach($new_graph->nodes as $keys => $value)
	{
		$value->print_node();
	}*/



	

	/*print("Le meilleur chemin de ".$depart->id." à ".$arrivee->id." est : ");
	foreach ($final_path as $key => $value) 
	{
		print($value->id."->");
	}

	print("<p> Energie restante : ".$energy." <br/> Travel time : ".$astar->get_path_time($final_path)."</p>");*/


?>
