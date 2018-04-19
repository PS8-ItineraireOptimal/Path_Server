<!DOCTYPE html> 
<html> 
<body> 

<?php

// This is a example of using the two functions of change_projection.php

include ("change_projection.php");

$x = 652709.401;
$y = 6859290.946;

$geo_WGS83 = from_L93_to_WGS($x , $y);

//i changed it in to a string for printing
echo "Conversion: " . $geo_WGS83 ->toShortString() . " in WGS84<br><br>";


//change it from an object into a list
$tried = $geo_WGS83 ->toArray();


//index 0 is x
echo $tried[0];
echo "<br>";

//index 1 is y
echo $tried[1];
echo "<br>";

//transforme it into the original version
$geo_LAM93 = from_WGS_to_L93($tried[0] , $tried[1]);
echo "Conversion: " . $geo_LAM93 ->toShortString() . " in LAM93<br><br>";
    
?>
    
</body> 
</html>