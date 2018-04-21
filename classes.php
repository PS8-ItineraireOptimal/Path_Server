<?php
include_once('bdd.php');
include_once('geometry.php');
// const speed = 50.0;
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
	public $next_nodes;
	
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
		$this->tps_depart=0;
		$this->tps_arrivee=0;
		$this->coeff_astar=0;
		$this->previous_node=array();
		$this->next_nodes=array();
	}	

	function calcul_coeff_astar()
	{
		$this->coeff_astar = $this->tps_depart + $this->tps_arrivee;
	}

	function print_node()
	{
		print("<p> id: ".$this->id."<br/> x:".$this->x."<br/> y:".$this->y."<br/> tps_depart:".$this->tps_depart."<br/> tps_arrivee:".$this->tps_arrivee."<br/> coeff_astar:".$this->coeff_astar);
		print("<br/> previous nodes:");
		foreach($this->previous_nodes as $key => $value)
		{
			print($value->id." -");
		}
		print("<br/> next nodes:");
		foreach($this->next_nodes as $key => $value)
		{
			print($value->id." -");
		}
		print("</p>");
	}
}

class Arc
{
	public $id;
	public $edges=array();
	public $tps_parcours;
	public $energie_cons;
	public $length;
	
	function __construct($n_id,$noeud1,$noeud2, $n_tps_parcours, $energie_cons, $length)
	{
		$this->id=$n_id;
		$this->edges[0]=$noeud1;
		$this->edges[1]=$noeud2;
		$this->tps_parcours=$n_tps_parcours;
		$this->energie_cons=$energie_cons;
		$this->length=$length;
	}

	function print_arc()
	{
		print("<p> id:".$this->id." noeud1:".$this->edges[0]." noeud2:".$this->edges[1]." tps_parcours:".$this->tps_parcours." energie_cons:".$this->energie_cons." length:".$this->length."</p>");
	}
}	

class Graph
{
	public $nodes;
	public $arcs;


	function __construct($n_nodes=array(),$n_arcs=array())
	{
		$this->nodes=$n_nodes;
		$this->arcs=$n_arcs;
	}

	function find_arc($noeud1,$noeud2=null)
	{
		$i=0;

		if($noeud2 != null)
		{	
			while($i<count($this->arcs))
			{
				if(in_array($noeud1, $this->arcs[$i]->edges)==TRUE && in_array($noeud2, $this->arcs[$i]->edges)==TRUE)
				{
					return $this->arcs[$i];
				}
				$i=$i+1;
			}
		}
		else
		{
			while($i<count($this->arcs))
			{
				if(in_array($noeud1, $this->arcs[$i]->edges)==TRUE)
				{
					return $this->arcs[$i];
				}
				$i=$i+1;
			}
		}

		return null;

	}

	function find_next_nodes(Node $noeud)
	{
		$noeud->next_nodes=array();
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

		while($index<count($this->nodes))
		{
			if($this->nodes[$index]->id == $id_noeud)
				return $this->nodes[$index];
			
			$index = $index + 1;
		}
		
		return null;
	}

	function get_graph_from_bdd(Node $start_node, Node $end_node, $marge,$bdd)
	{
		$i=0;
		$aabb = computeAABBFromNodes($start_node, $end_node, $marge);
		try
		{
			$req1 = $bdd->query("SELECT id_noeud, lon, lat
						   FROM nodes
						   WHERE (lon>".$aabb->x_min." AND lon<".$aabb->x_max.") AND (lat>".$aabb->y_min." AND lat<".$aabb->y_max.");");
		}
		catch(Exception $e)
		{
			echo "erreur avec la Requête";
			die('Erreur : '.$e->getMessage());
		}

		while ($res1 = $req1->fetch_assoc())
		{
			$node=new Node(intval($res1["id_noeud"]),intval($res1["lon"]),intval($res1["lat"]));
			$this->nodes[]=$node;
		}

		
		try
		{
			$req2 = $bdd->query("SELECT id_route, id_noeud1, id_noeud2, Tij_h, Eij_kWh,distance 
								FROM roads
								WHERE id_noeud1 IN 
								(SELECT id_noeud
								FROM nodes
								WHERE (lon>".$aabb->x_min." AND lon<".$aabb->x_max.") AND (lat>".$aabb->y_min." AND lat<".$aabb->y_max.")
								)
								AND
								id_noeud2 IN
								(SELECT id_noeud
								FROM nodes
								WHERE (lon>".$aabb->x_min." AND lon<".$aabb->x_max.") AND (lat>".$aabb->y_min." AND lat<".$aabb->y_max.")
								);");
		}
		catch(Exception $e)
		{
			echo "erreur avec la Requête";
			die('Erreur : '.$e->getMessage());
		}
		while ($res2 = $req2->fetch_assoc())
		{
			$arc=new Arc($res2["id_route"], $res2["id_noeud1"], $res2["id_noeud2"], $res2["Tij_h"], $res2["Eij_kWh"], $res2["distance"]);
			$this->arcs[]=$arc;
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

	function __construct (Graph $g)
	{
		$this->graph=$g;
	}

	function get_best_path(Node $depart, Node $arrivee)
	{

		$this->start=$depart;
		$this->arrival=$arrivee;

		$this->start->tps_depart=0;
		$this->current=$this->start;

		$this->current->tps_arrivee = $this->calcul_tps_arrivee($this->current);
		$this->current->calcul_coeff_astar($this->current);

		$this->closelist=array();
		$this->openlist=array("id_noeud"=>array(),"noeud"=>array());

		while ($this->current->id != $this->arrival->id ) 
		{
			$this->closelist[]=$this->current->id;

			$this->graph->find_next_nodes($this->current);

			$neighbors=$this->current->next_nodes;

			//debug
			print("<p>id current:".$this->current->id." neighbors:".count($neighbors)." </p>");
			//fin debug

			foreach($neighbors as $index => $valeur)
			{
				$this->update_coefficients($this->current,$valeur);
			}

			$this->current=$this->new_current();
		}

		$this->path_steps=array();
		$this->path_steps[]=$this->current;
		
		while($this->current != $this->start)
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


	function get_path_length(array $steps)
	{
		$length=0;
		for($i = 0 ; $i < count($steps)-1 ; $i++)
		{
			$length = $length + $this->graph->find_arc($steps[$i]->id,$steps[$i+1]->id)->length;
		}
		return $length;
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
		$result=sqrt((($this->arrival->x - $noeud->x)*($this->arrival->x - $noeud->x)+($this->arrival->y - $noeud->y)*($this->arrival->y - $noeud->y)));
		return $result;
	}

	function update_coefficients(Node $current_node, Node $next_node)
	{

		$arc=$this->graph->find_arc($current_node->id,$next_node->id);

		if(in_array($next_node->id,$this->openlist["id_noeud"])==TRUE && in_array($next_node->id,$this->closelist)==FALSE)
		{
			if($current_node->tps_depart+$arc->tps_parcours < $next_node->tps_depart)
			{
				$next_node->tps_depart=$current_node->tps_depart+$arc->tps_parcours;
				$next_node->previous_node=$current_node;

				$next_node->tps_arrivee = $this->calcul_tps_arrivee($next_node);
				//calcul coeff astar
				$next_node->calcul_coeff_astar($next_node);
			}
		}
		else if(in_array($next_node->id,$this->openlist["id_noeud"])==FALSE && in_array($next_node->id,$this->closelist)==FALSE)
		{
			$next_node->tps_depart=$current_node->tps_depart+$arc->tps_parcours;
			$next_node->previous_node=$current_node;

			//calcul coeff astar
			$next_node->tps_arrivee = $this->calcul_tps_arrivee($next_node);
			$next_node->calcul_coeff_astar($next_node);

			$this->openlist["id_noeud"][]=$next_node->id;
			$this->openlist["noeud"][]=$next_node;
		}

	}

	function new_current()
	{
		$j = -1;
		$min_coeff_astar=INF;
		
		foreach($this->openlist["noeud"] as $index => $valeur)
		{
			if($valeur->coeff_astar<=$min_coeff_astar)
			{	
				$min_coeff_astar=$valeur->coeff_astar;
				$j=$index;
			}
		}

		$this->closelist[]=$this->openlist["id_noeud"][$j];
		
		$current_node = $this->openlist["noeud"][$j];

		unset($this->openlist["noeud"][$j]);
		$this->openlist["noeud"] = array_values($this->openlist["noeud"]);
		unset($this->openlist["id_noeud"][$j]);
		$this->openlist["id_noeud"] = array_values($this->openlist["id_noeud"]);

		return $current_node;
	}
}

?>