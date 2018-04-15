<?php

include('classes.php');

?>

<?php

$n = array();
$i = 1; $n[$i] = new Node($i, 3); 
$i = $i+1; $n[$i] = new Node($i, 2); 
$i = $i+1; $n[$i] = new Node($i, 2); 
$i = $i+1; $n[$i] = new Node($i, 1); 
$i = $i+1; $n[$i] = new Node($i, 1); 
$i = $i+1; $n[$i] = new Node($i, 0);


$a=array();
$j=0; $a[$j]= new Arc($j,$n[1],$n[2],1);
$j=$j+1; $a[$j]= new Arc($j,$n[1],$n[3],3);
$j=$j+1; $a[$j]= new Arc($j,$n[2],$n[3],4);
$j=$j+1; $a[$j]= new Arc($j,$n[3],$n[4],5);
$j=$j+1; $a[$j]= new Arc($j,$n[2],$n[4],2);
$j=$j+1; $a[$j]= new Arc($j,$n[2],$n[5],1);
$j=$j+1; $a[$j]= new Arc($j,$n[5],$n[4],3);
$j=$j+1; $a[$j]= new Arc($j,$n[5],$n[6],5);
$j=$j+1; $a[$j]= new Arc($j,$n[4],$n[6],2);


$g = new Graph($n,$a);

/*
print($g->find_arc($n[1],$n[3])->tps_parcours);

$g->find_next_nodes($n[6]);

*/

$dijk = new Dijkstra($g,$n[1],$n[6]);

$steps=$dijk->find_best_path();

foreach($steps as $index => $valeur)
{
	print('<p>'.$valeur->id.'</p>');
}

?>