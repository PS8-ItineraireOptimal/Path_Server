<?php
include_once('bdd.php');
include_once('geometry.php');

//Class representing a node of the graph
class Node
{
	public $id;//id of the node in the DB
	public $x;//absciss of the node in the Lambert93 system
	public $y;//ordinate of the node in the Lambert93 system
	public $tps_depart;//travel time from the start to the node
	public $tps_arrivee;//estimed travel time from the node to the arrival
	public $coeff_astar;//coefficient used in the A* algorithm
	public $previous_node;//not checked before coming to the this node
	public $next_nodes;// an array containing the id of the nearby nodes
	

	//class construtor
	function __construct($n_id,$x,$y)
	{
		$this->id=$n_id;
		$this->x=$x;
		$this->y=$y;
		$this->tps_depart=0;
		$this->tps_arrivee=0;
		$this->coeff_astar=0;
		$this->previous_node;
		$this->next_nodes=array();
	}	

	//calculate the A* coefficient of the node
	function compute_coeff_astar()
	{
		$this->coeff_astar = $this->tps_depart + $this->tps_arrivee;
	}

	//Shows the information about the node
	function print_node()
	{
		print("<p> id: ".$this->id."<br/> x:".$this->x."<br/> y:".$this->y."<br/> tps_depart:".$this->tps_depart."<br/> tps_arrivee:".$this->tps_arrivee."<br/> coeff_astar:".$this->coeff_astar);
		print("<br/> previous nodes:".$previous_node->id);
		print("<br/> next nodes:");
		foreach($this->next_nodes as $key => $value)
		{
			print($value->id." -");
		}
		print("</p>");
	}
}

//represent an arc of the road network
class Arc
{
	public $id;//id of the arc in the DB
	public $edges=array();//An array containing the ids of the 2 nodes composing the arc 
	public $tps_parcours;//travel time of the arc
	public $energie_cons;//energy consumed by the arc
	public $length;//distance of the arc
	
	//constructor of the class
	function __construct($n_id,$id_noeud1,$id_noeud2, $n_tps_parcours, $energie_cons, $length)
	{
		$this->id=$n_id;
		$this->edges[0]=$id_noeud1;
		$this->edges[1]=$id_noeud2;
		$this->tps_parcours=$n_tps_parcours;
		$this->energie_cons=$energie_cons;
		$this->length=$length;
	}

	//shows informations about the arc
	function print_arc()
	{
		print("<p> id:".$this->id." noeud1:".$this->edges[0]." noeud2:".$this->edges[1]." tps_parcours:".$this->tps_parcours." energie_cons:".$this->energie_cons." length:".$this->length."</p>");
	}
}	

//represent the road network
class Graph
{
	public $nodes;// nodes of the graph
	public $arcs;// arcs of the graph

	// constructor of the class
	function __construct($n_nodes=array(),$n_arcs=array())
	{
		$this->nodes=$n_nodes;
		$this->arcs=$n_arcs;
	}

	//Return the arc composed of the 2 nodes possessing respectively the id given as parameters 
	function find_arc($id_noeud1,$id_noeud2)
	{
		$i=0;
	
		while($i<count($this->arcs))
		{
			if(in_array($id_noeud1, $this->arcs[$i]->edges)==TRUE && in_array($id_noeud2, $this->arcs[$i]->edges)==TRUE)
			{
				return $this->arcs[$i];
			}
			$i=$i+1;
		}
		print("<p> THERE IS NO ARC GOING THROUGH NODES ".$id_noeud1." AND ".$id_noeud2." </p>");
		return null;

	}

	//Return the neighbors of a given Node
	function find_next_nodes(Node $noeud)
	{
		//we first empty the table, to make sure there is nothing in
		$noeud->next_nodes=array();

		/*For each arc containing $noeud, we had the other node of the arc in the table 
		$noeud->next_nodes*/
		for ($i=0;$i<count($this->arcs);$i++) 
		{
			if(in_array($noeud->id,$this->arcs[$i]->edges))
			{
				$key = array_search($noeud->id, $this->arcs[$i]->edges);
				$noeud->next_nodes[]= $this->find_node_in_graph($this->arcs[$i]->edges[1-$key]);
			}
		}

		if(count($noeud->next_nodes)==0)
			print("<p> NODE ".$noeud->id." DOESN'T HAVE ANY NEIGHBORS</p>");
	}

	//return the node of the graph having the corresponding id
	function find_node_in_graph($id_noeud)
	{
		$index=0;

		while($index<count($this->nodes))
		{
			if($this->nodes[$index]->id == $id_noeud)
				return $this->nodes[$index];
			
			$index = $index + 1;
		}
		print(" <p> NODE ".$id_noeud." NOT FOUND IN THE GRAPH</p>");
		return null;
	}

	//create the graph by querying the DB
	function get_graph_from_bdd(Node $start_node, Node $end_node, $marge,$bdd)
	{
		$i=0;

		//Trace the zone where to extract the network informations
		$aabb = computeAABBFromNodes($start_node, $end_node, $marge);
		try
		{
			$req1 = $bdd->query("SELECT id_noeud, lon, lat
						   FROM nodes
						   WHERE (lon>".$aabb->x_min." AND lon<".$aabb->x_max.") AND (lat>".$aabb->y_min." AND lat<".$aabb->y_max.");");
		}
		catch(Exception $e)
		{
			echo "<p>REQUEST FAIL : COULDN'T EXTRACT THE NODES FROM THE DB</p>";
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
			echo "<p>REQUEST FAIL : COULDN'T EXTRACT THE ARCS FROM THE DB";
			die('Erreur : '.$e->getMessage());
		}
		while ($res2 = $req2->fetch_assoc())
		{
			$arc=new Arc($res2["id_route"], $res2["id_noeud1"], $res2["id_noeud2"], $res2["Tij_h"], $res2["Eij_kWh"], $res2["distance"]);
			$this->arcs[]=$arc;
		}

	}
}

//Class allowing us to implement the A* algorithm.
class Astar
{
	public $start;//starting node
	public $arrival;//arriving node
	public $current;//current node
	public $openlist=array();//Array containing the NODES that are not definitely treated and their IDS
	public $closelist=array();//Array containing the IDS of the definitely treated nodes
	public $path_steps=array();//Contains the nodes of the most optimal path
	public $graph;// the graph where we do the calculations

	function __construct (Graph $g)
	{
		$this->graph=$g;
	}

	//Compute de best path knowing the starting and arriving nodes
	function get_best_path(Node $depart, Node $arrivee)
	{
		//initializing the start and arrival
		$this->start=$depart;
		$this->arrival=$arrivee;

		$this->start->tps_depart=0;
		$this->current=$this->start;

		$this->current->tps_arrivee = $this->calcul_tps_arrivee($this->current);
		$this->current->compute_coeff_astar($this->current);

		//Empty the openlist and the closelist to make sure there is nothing in at the beginning
		$this->closelist=array();
		$this->openlist=array("id_noeud"=>array(),"noeud"=>array());

		//we compute the algorithm as long as we have reached the arrival
		while ($this->current->id != $this->arrival->id ) 
		{
			//we look for the current node's neighbors
			$this->graph->find_next_nodes($this->current);
			$neighbors=$this->current->next_nodes;

			//uncomment the code below when debuging
			/*print("<p> <h2> id current:".$this->current->id." neighbors:".count($neighbors)." </h2> </p>");
			print("<p> id neighbors :");
			foreach($neighbors as $index => $valeur)
			{
				print($valeur->id." ");
			}
			print("</p>");*/
			//fin debug

			//We update the coefficients of each neighbor
			foreach($neighbors as $index => $valeur)
			{
				$this->update_coefficients($this->current,$valeur);
			}

			//Look for a new current node
			$this->current=$this->new_current();
		}

		//uncomment the code below when debuging
		/*print("<p> <h2> arrivee:".$this->current->id."</h2></p>");*/
		//fin debug

		//initialize final-steps
		$this->path_steps=array();

		//We add the nodes of the path in path_steps from the end to the beginning and finally reverse the array
		$this->path_steps[]=$this->current;
		while($this->current != $this->start)
		{
			$this->path_steps[]=$this->current->previous_node;
			$this->current=$this->current->previous_node;
		}
		$this->path_steps=array_reverse($this->path_steps);
		
		return $this->path_steps;

	}

	//calculate a path energy
	function get_path_energy(array $steps)
	{
		$energy=0;
		for($i = 0 ; $i < count($steps)-1 ; $i++)
		{
			$energy = $energy + $this->graph->find_arc($steps[$i]->id,$steps[$i+1]->id)->energie_cons;
		}
		return $energy;
	}

	//calculate a path distance
	function get_path_length(array $steps)
	{
		$length=0;
		for($i = 0 ; $i < count($steps)-1 ; $i++)
		{
			$length = $length + $this->graph->find_arc($steps[$i]->id,$steps[$i+1]->id)->length;
		}
		return $length;
	}

	//calculate a path travel time
	function get_path_time(array $steps)
	{
		$time=0;
		for($i = 0 ; $i < count($steps)-1 ; $i++)
		{
			$time = $time + $this->graph->find_arc($steps[$i]->id,$steps[$i+1]->id)->tps_parcours;
		}
		return $time;
	}

	//calculate the tps_arrivee of a Node
	function calcul_tps_arrivee(Node $noeud)
	{
		$result=sqrt((($this->arrival->x - $noeud->x)*($this->arrival->x - $noeud->x)+($this->arrival->y - $noeud->y)*($this->arrival->y - $noeud->y)));
		return $result;
	}

	//Update the coefficients of $nest_node 
	function update_coefficients(Node $current_node, Node $next_node)
	{

		$arc=$this->graph->find_arc($current_node->id,$next_node->id);

		//If next_node belongs to the openlist and isn't in the closelist
		if(in_array($next_node->id,$this->openlist["id_noeud"])==TRUE && in_array($next_node->id,$this->closelist)==FALSE)
		{
			//uncomment the code below when debuging
			/*print("<p> update coefficient if</p>");*/
			//fin debug

			//if there is faster path to reach next_node, we update its coefficients
			if($current_node->tps_depart+$arc->tps_parcours < $next_node->tps_depart)
			{
				//uncomment the code below when debuging
				/*print("<p> update coefficient if dans le if</p>");*/
				//fin debug

				$next_node->tps_depart = $current_node->tps_depart+$arc->tps_parcours;
				$next_node->previous_node = $current_node;
				$next_node->tps_arrivee = $this->calcul_tps_arrivee($next_node);

				//calcul coeff astar
				$next_node->compute_coeff_astar($next_node);

				$position_in_openlist = array_search($next_node->id, $this->openlist["id_noeud"]);
				$this->openlist["noeud"][$position_in_openlist]=$next_node;
			}
		}
		//if next_node doesn't belong to nor the openlist, nor the closelist, we update its coefficients and add it to the openlist
		else if(in_array($next_node->id,$this->openlist["id_noeud"])==FALSE && in_array($next_node->id,$this->closelist)==FALSE)
		{
			//uncomment the code below when debuging
			/*print("<p> update coefficient else if</p>");*/
			//fin debug

			$next_node->tps_depart=$current_node->tps_depart+$arc->tps_parcours;
			$next_node->previous_node=$current_node;

			//calcul coeff astar
			$next_node->tps_arrivee = $this->calcul_tps_arrivee($next_node);
			$next_node->compute_coeff_astar($next_node);

			$this->openlist["id_noeud"][]=$next_node->id;
			$this->openlist["noeud"][]=$next_node;
		}

	}

	//Selects the new current node
	function new_current()
	{
		$j = -1;
		$min_coeff_astar=INF;
		
		//the new current node is the node in the openlist with the lower coeff_astar
		foreach($this->openlist["noeud"] as $index => $valeur)
		{
			if($valeur->coeff_astar<=$min_coeff_astar)
			{	
				$min_coeff_astar=$valeur->coeff_astar;
				$j=$index;
			}
		}

		//uncomment the code below when debuging
		/*print("<p> taille openlist :".count($this->openlist["noeud"])."</p>");
		print("<p> min coeff_astar ".$min_coeff_astar."</p>");
		*///findebug

		//the new current node's ID is added to the closelist
		$this->closelist[]=$this->openlist["id_noeud"][$j];
		
		$current_node = $this->openlist["noeud"][$j];

		//suppress the current node from the openlist
		unset($this->openlist["noeud"][$j]);
		$this->openlist["noeud"] = array_values($this->openlist["noeud"]);
		unset($this->openlist["id_noeud"][$j]);
		$this->openlist["id_noeud"] = array_values($this->openlist["id_noeud"]);

		return $current_node;
	}
}

?>