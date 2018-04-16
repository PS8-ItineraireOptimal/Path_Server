<?php

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
	
	function __construct($n_id,$tps_arrivee)
	{
		$this->id=$n_id;
		$this->tps_arrivee=$tps_arrivee;
	}	
}

class Arc
{
	public $id;
	public $edges=array();
	public $tps_parcours;
	public $energie_cons;
	
	function __construct($n_id,Node $noeud1, Node $noeud2, $n_tps_parcours)
	{
		$this->id=$n_id;
		$this->edges[0]=$noeud1;
		$this->edges[1]=$noeud2;
		$this->tps_parcours=$n_tps_parcours;
	}
}	

class Graph
{
	public $nodes=array();
	public $arcs=array();

	function __construct($n_nodes,$n_arcs)
	{
		$this->nodes=$n_nodes;
		$this->arcs=$n_arcs;
	}

	function find_arc(Node $noeud1, Node $noeud2)
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
			if(in_array($noeud,$this->arcs[$i]->edges))
			{
				$key=array_search($noeud, $this->arcs[$i]->edges);
				$noeud->next_nodes[]= $this->arcs[$i]->edges[1-$key];
			}
		}
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

	function __construct (Graph $g, Node $start, Node $arrival)
	{
		$this->graph=$g;
		$this->start=$start;
		$this->arrival=$arrival;
	}

	function update_coefficients(Node $current_node, Node $next_node)
	{
		$arc=$this->graph->find_arc($current_node,$next_node);

		if(in_array($next_node,$this->openlist)==TRUE && in_array($next_node,$this->closelist)==FALSE)
		{
			if($current_node->tps_depart+$arc->tps_parcours < $next_node->tps_depart)
			{
				$next_node->tps_depart=$current_node->tps_depart+$arc->tps_parcours;
				$next_node->previous_node=$current_node;

				//calcul coeff astar
				$next_node->coeff_astar=$next_node->tps_depart+$next_node->tps_arrivee;
			}
		}
		else if(in_array($next_node,$this->openlist)==FALSE && in_array($next_node,$this->closelist)==FALSE)
		{
			$next_node->tps_depart=$current_node->tps_depart+$arc->tps_parcours;
			$next_node->previous_node=$current_node;

			//calcul coeff astar
			$next_node->coeff_astar=$next_node->tps_depart+$next_node->tps_arrivee;
			$this->openlist[]=$next_node;
		}
	}

	function new_current()
	{
		$min_coeff_astar=end($this->openlist)->coeff_astar;
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
		return end($this->closelist);
	}

	function find_best_path()
	{
		$this->start->tps_depart=0;
		$this->current=$this->start;

		while ($this->current!=$this->arrival) 
		{
			$this->closelist[]=$this->current;

			$this->graph->find_next_nodes($this->current);

			$neighbors=$this->current->next_nodes;

			foreach($neighbors as $index => $valeur)
				$this->update_coefficients($this->current,$valeur);
			
			$this->current=$this->new_current();
		}

		print('fin de boucle');


		$this->path_steps[]=$this->current;
		while($this->current!=$this->start)
		{
			$this->path_steps[]=$this->current->previous_node;
			$this->current=$this->current->previous_node;
		}

		$this->path_steps=array_reverse($this->path_steps);

		return $this->path_steps;
	}
}

class Dijkstra
{
	const C_INF = '1000000';

	public $start;
	public $arrival;
	public $current;
	public $openlist=array();
	public $closelist=array();
	public $graph;

	function __construct (Graph $g, Node $start, Node $arrival)
	{
		$this->graph=$g;
		$this->start=$start;
		$this->arrival=$arrival;

		foreach($this->graph->nodes as $index => $valeur)
			$valeur->tps_depart = self::C_INF;
	}

	function update_coefficients(Node $current_node, Node $next_node)
	{
		$arc=$this->graph->find_arc($current_node,$next_node);

		if(in_array($next_node,$this->openlist))
		{
			if($current_node->tps_depart + $arc->tps_parcours < $next_node->tps_depart)
			{
				$next_node->tps_depart = $current_node->tps_depart + $arc->tps_parcours;
				$next_node->previous_node = $current_node;
		    }
		}
		
	}

	function new_current()
	{
		$min_tps_depart=end($this->openlist)->tps_depart;
		foreach($this->openlist as $index => $valeur)
		{
			if($valeur->tps_depart<=$min_tps_depart)
			{	
				$min_tps_depart=$valeur->tps_depart;
				$j=$index;
			}
		}

		$this->closelist[]=$this->openlist[$j];
		unset($this->openlist[$j]);
		return end($this->closelist);
	}


	function compute_best_path()
	{
		$this->openlist=$this->graph->nodes;

		$this->start->tps_depart = 0;

		$this->current = $this->start;

		while ($this->openlist != null) 
		{

			$this->graph->find_next_nodes($this->current);

			$neighbors=$this->current->next_nodes;

			foreach($neighbors as $index => $valeur)
				$this->update_coefficients($this->current,$valeur);
			
			$this->current=$this->new_current();
		}

		$this->graph->nodes=$this->closelist;

		print("fin de l'algo de Dijkstra <br/>");
	}

	function get_path(Node $depart, Node $arrivee)
	{
		$path_steps[]=$this->current;

		while($this->current!=$this->start)
		{
			$path_steps[]=$this->current->previous_node;
			$this->current=$this->current->previous_node;
		}

		$path_steps=array_reverse($path_steps);

		return $path_steps;
	}

	function best_path_through_specific_nodes(array $nodes_list)
	{

	}
}

?>