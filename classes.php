<?php

const speed = 50.0;
//represent a node
class Node
{
	public $id;
	public $x;
	public $y;
	public $tps_depart;
	public $tps_arrivee;
	public $coeff_astar;
	public $previous_node;
	public $next_nodes=array();
	
	/* public __construct($p_id, $p_x, $p_y, $p_d)
	{
		$this->id = $p_id;
		$this->x = $p_x;
		$this->y = $p_y;
	} */
	
	function __construct($n_id,$x,$y)
	{
		$this->id=$n_id;
		$this->x=$x;
		$this->y=$y;
	}	

	function calcul_coeff_astar()
	{
		$this->coeff_astar = $this->tps_depart + $this->tps_arrivee;
	}
}

class Arc
{
	public $id;
	public $edges=array();
	public $tps_parcours;
	public $energie_cons;
	
	function __construct($n_id,$noeud1,$noeud2, $n_tps_parcours, $energie_cons)
	{
		$this->id=$n_id;
		$this->edges[0]=$noeud1;
		$this->edges[1]=$noeud2;
		$this->tps_parcours=$n_tps_parcours;
		$this->energie_cons=$energie_cons;
	}
}	

class Graph
{
	public $nodes;
	public $arcs;

	function __construct()
	{
		$this->nodes=array();
		$this->arcs=array();
	}

	function __construct($n_nodes,$n_arcs)
	{
		$this->nodes=$n_nodes;
		$this->arcs=$n_arcs;
	}

	function find_arc($noeud1,$noeud2)
	{
		$i=0;
		$found=0;
		while($i<count($this->arcs) || $found!=1)
		{
			if(in_array($noeud1, $this->arcs[$i]->edges)==TRUE && in_array($noeud2, $this->arcs[$i]->edges)==TRUE)
			{
				$found=1;
				$j=$i;
			}
			$i=$i+1;
		}

		return $this->arcs[$j];
	}

	function find_next_nodes(Node $noeud)
	{

		for ($i=0;$i<count($this->arcs);$i++) 
		{
			if(in_array($noeud->id,$this->arcs[$i]->edges))
			{
				$key = array_search($noeud->id, $this->arcs[$i]->edges);
				$noeud->next_nodes[]= $this->find_node_in_graph($this->arcs[$i]->edges[1-$key]);
			}
		}
	}

	function find_node_in_graph($id_noeud)
	{
		$index=0;
		$found=-1;

		while($index<count($this->nodes) || $found == -1)
		{
			if($this->nodes[$index]->id == $id_noeud)
				$found = $index;
			
			$index = $index + 1;
		}

		if($found == -1)
			return null;
		else
			return $this->nodes[$found];
	}
}

class Astar
{
	public $start;
	public $arrival;
	public $current;
	public $openlist=array();
	public $closelist=array();
	public $path_steps=array();
	public $graph;

	function __construct (Graph $g)
	{
		$this->graph=$g;
	}

	function get_best_path(Node $depart, Node $arrivee)
	{

		$this->start=$depart;
		$this->arrival=$arrivee;

		foreach($this->graph->nodes as $index => $valeur)
			$this->calcul_tps_arrivee($valeur);

		$this->start->tps_depart=0;
		$this->current=$this->start;

		while ($this->current!=$this->arrival) 
		{
			$this->closelist[]=$this->current;

			$this->graph->find_next_nodes($this->current);

			$neighbors=$this->current->next_nodes;

			foreach($neighbors as $index => $valeur)
			{
				$this->update_coefficients($this->current,$valeur);
			}

			$this->current=$this->new_current();
		}


		$this->path_steps[]=$this->current;
		while($this->current!=$this->start)
		{
			$this->path_steps[]=$this->current->previous_node;
			$this->current=$this->current->previous_node;
		}

		$this->path_steps=array_reverse($this->path_steps);

		return $this->path_steps;

	}

	function get_path_energy(array $steps)
	{
		$energy=0;
		for($i = 0 ; $i < count($steps)-1 ; $i++)
		{
			$energy = $energy + $this->graph->find_arc($steps[$i]->id,$steps[$i+1]->id)->energie_cons;
		}
		return $energy;
	}

	function get_path_time(array $steps)
	{
		$time=0;
		for($i = 0 ; $i < count($steps)-1 ; $i++)
		{
			$time = $time + $this->graph->find_arc($steps[$i]->id,$steps[$i+1]->id)->tps_parcours;
		}
		return $time;
	}

	function calcul_tps_arrivee(Node $noeud)
	{
		$result=sqrt((($this->arrival->x - $noeud->x)*($this->arrival->x - $noeud->x)-($this->arrival->y - $noeud->y)*($this->arrival->y - $noeud->y)))/speed;
		return $result;
	}

	function update_coefficients(Node $current_node, Node $next_node)
	{

		$arc=$this->graph->find_arc($current_node->id,$next_node->id);

		if(in_array($next_node,$this->openlist)==TRUE && in_array($next_node,$this->closelist)==FALSE)
		{
			if($current_node->tps_depart+$arc->tps_parcours < $next_node->tps_depart)
			{
				$next_node->tps_depart=$current_node->tps_depart+$arc->tps_parcours;
				$next_node->previous_node=$current_node;

				//calcul coeff astar
				$next_node->calcul_coeff_astar($next_node);
			}
		}
		else if(in_array($next_node,$this->openlist)==FALSE && in_array($next_node,$this->closelist)==FALSE)
		{
			$next_node->tps_depart=$current_node->tps_depart+$arc->tps_parcours;
			$next_node->previous_node=$current_node;

			//calcul coeff astar
			$next_node->calcul_coeff_astar($next_node);
			$this->openlist[]=$next_node;
		}

	}

	function new_current()
	{
		$j = -1;

		$min_coeff_astar=$this->openlist[0]->coeff_astar;
		
		foreach($this->openlist as $index => $valeur)
		{
			if($valeur->coeff_astar<=$min_coeff_astar)
			{	
				$min_coeff_astar=$valeur->coeff_astar;
				$j=$index;
			}
		}

		$this->closelist[]=$this->openlist[$j];
		unset($this->openlist[$j]);
		$this->openlist = array_values($this->openlist);

		return end($this->closelist);
	}
}

?>