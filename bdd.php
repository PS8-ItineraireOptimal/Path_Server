<?php

//////////////////////////////////////////////////////
//
// Connexion à la BDD
//
//////////////////////////////////////////////////////

// Login
$db_host = "78.113.61.31";
$db_user = "ps8user";
$db_pass = "projets8";
$db_base = "projet_S8";

// Connexion
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_base);
if ($mysqli->connect_errno)
{
	echo 'error bdd';
}

/*function get_bdd()
{
	global $mysqli;
	return $mysqli;
}*/

?>