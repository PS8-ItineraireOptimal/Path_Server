<?php

namespace proj4php\projCode;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4JS from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodma$p->com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */
/*******************************************************************************
  NAME                            EQUIDISTANT CONIC

  PURPOSE:	Transforms input longitude and latitude to Easting and Northing
  for the Equidistant Conic projection.  The longitude and
  latitude must be in radians.  The Easting and Northing values
  will be returned in meters.

  PROGRAMMER              DATE
  ----------              ----
  T. Mittan		Mar, 1993

  ALGORITHM REFERENCES

  1.  Snyder, John $p->, "Map Projections--A Working Manual", U.S. Geological
  Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
  State Government Printing Office, Washington D.C., 1987.

  2.  Snyder, John $p-> and Voxland, Philip M., "An Album of Map Projections",
  U.S. Geological Survey Professional Paper 1453 , United State Government
  Printing Office, Washington D.C., 1989.
*******************************************************************************/

/* Variables common to all subroutines in this code file
-----------------------------------------------------*/

use proj4php\Proj4php;
use proj4php\Common;

class Eqdc
{
    public $a;
    public $b;
    public $cosphi;
    public $e;
    public $e0;
    public $e1;
    public $e2;
    public $e3;
    public $es;
    public $g;
    public $lat0;
    public $lat1;
    public $lat2;
    public $long0;
    public $ml0;
    public $ml1;
    public $ml2;
    public $mode;
    public $ms1;
    public $ms2;
    public $ns;
    public $rh;
    public $sinphi;
    public $temp;
    public $x0;
    public $y0;

    /**
     * Initialize the Equidistant Conic projection
     */
    public function init()
    {
        // Place parameters in static storage for common use

        if (! isset($this->mode)) {
            //chosen default mode
            $this->mode = 0;
        }

        $this->temp = $this->b / $this->a;
        $this->es = 1.0 - pow( $this->temp, 2 );

        $this->e = sqrt( $this->es );

        $this->e0 = Common::e0fn( $this->es );
        $this->e1 = Common::e1fn( $this->es );
        $this->e2 = Common::e2fn( $this->es );
        $this->e3 = Common::e3fn( $this->es );

        $this->sinphi = sin( $this->lat1 );
        $this->cosphi = cos( $this->lat1 );

        $this->ms1 = Common::msfnz( $this->e, $this->sinphi, $this->cosphi );
        $this->ml1 = Common::mlfn( $this->e0, $this->e1, $this->e2, $this->e3, $this->lat1 );

        // format B

        if ($this->mode != 0) {
            if (abs($this->lat1 + $this->lat2) < Common::EPSLN) {
                Proj4php::reportError( "eqdc:Init:EqualLatitudes" );
                //return(81);
            }

            $this->sinphi = sin($this->lat2);
            $this->cosphi = cos($this->lat2);

            $this->ms2 = Common::msfnz( $this->e, $this->sinphi, $this->cosphi );
            $this->ml2 = Common::mlfn( $this->e0, $this->e1, $this->e2, $this->e3, $this->lat2 );

            if (abs($this->lat1 - $this->lat2) >= Common::EPSLN) {
                $this->ns = ($this->ms1 - $this->ms2) / ($this->ml2 - $this->ml1);
            } else {
                $this->ns = $this->sinphi;
            }
        } else {
            $this->ns = $this->sinphi;
        }

        $this->g = $this->ml1 + $this->ms1 / $this->ns;
        $this->ml0 = Common::mlfn( $this->e0, $this->e1, $this->e2, $this->e3, $this->lat0 );
        $this->rh = $this->a * ($this->g - $this->ml0);
    }

    /**
     * Forward equations
     * Equidistant Conic forward equations--mapping lat,long to x,y
     */
    public function forward($p)
    {
        $lon = $p->x;
        $lat = $p->y;

        $ml = Common::mlfn( $this->e0, $this->e1, $this->e2, $this->e3, $lat );
        $rh1 = $this->a * ($this->g - $ml);

        $theta = $this->ns * Common::adjust_lon( $lon - $this->long0 );

        $x = $this->x0 + $rh1 * sin($theta);
        $y = $this->y0 + $this->rh - $rh1 * cos($theta);

        $p->x = $x;
        $p->y = $y;

        return $p;
    }

    /**
     * Inverse equations
     */
    public function inverse($p)
    {
        $p->x -= $this->x0;
        $p->y = $this->rh - $p->y + $this->y0;

        if ($this->ns >= 0) {
            $rh1 = sqrt( $p->x * $p->x + $p->y * $p->y );
            $con = 1.0;
        } else {
            $rh1 = -sqrt( $p->x * $p->x + $p->y * $p->y );
            $con = -1.0;
        }

        $theta = 0.0;

        if ($rh1 != 0.0) {
            $theta = atan2($con * $p->x, $con * $p->y);
        }

        $ml = $this->g - $rh1 / $this->a;

        $lat = $this->phi3z( $ml, $this->e0, $this->e1, $this->e2, $this->e3 );
        $lon = Common::adjust_lon( $this->long0 + $theta / $this->ns );

        $p->x = $lon;
        $p->y = $lat;

        return $p;
    }

    /*
     * Compute latitude, phi3, for the inverse of the Equidistant
     * Conic projection.
     */
    public function phi3z($ml, $e0, $e1, $e2, $e3)
    {
        $phi = $ml;

        for ($i = 0; $i < 15; $i++) {
            $dphi = ($ml + $e1 * sin( 2.0 * $phi ) - $e2 * sin( 4.0 * $phi ) + $e3 * sin( 6.0 * $phi )) / $e0 - $phi;
            $phi += $dphi;

            if (abs( $dphi ) <= .0000000001) {
                return $phi;
            }
        }

        Proj4php::reportError("PHI3Z-CONV:Latitude failed to converge after 15 iterations");

        return null;
    }
}
