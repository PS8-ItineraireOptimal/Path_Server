

    
<?php 

// Use a PSR-4 autoloader for the `proj4php` root namespace.
include("proj4php/vendor/autoload.php");

use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;


function from_L93_to_WGS($x , $y)
{
    // Initialise Proj4
    $proj4 = new Proj4php();

    // Create two different projections.
    $projL93    = new Proj('EPSG:2154', $proj4);
    $projWGS84  = new Proj('EPSG:4326', $proj4);

    // Create a point.
    $pointSrc = new Point($x, $y, $projL93);

    // Transform the point between datums.
    $pointDest = $proj4->transform($projWGS84, $pointSrc);
    
    //output is an object(classe )
    return $pointDest;
}

function from_WGS_to_L93($x , $y)
{
    // Initialise Proj4
    $proj4 = new Proj4php();

    // Create two different projections.
    $projL93    = new Proj('EPSG:2154', $proj4);
    $projWGS84  = new Proj('EPSG:4326', $proj4);

    // Create a point.
    $pointSrc = new Point($x, $y, $projWGS84);

    // Transform the point between datums.
    $pointDest = $proj4->transform($projL93, $pointSrc);
    
    //output is an object(classe )
    return $pointDest;
}
?> 
        
