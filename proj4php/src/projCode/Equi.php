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
  NAME                             EQUIRECTANGULAR

  PURPOSE:	Transforms input longitude and latitude to Easting and
  Northing for the Equirectangular projection.  The
  longitude and latitude must be in radians.  The Easting
  and Northing values will be returned in meters.

  PROGRAMMER              DATE
  ----------              ----
  T. Mittan		Mar, 1993

  ALGORITHM REFERENCES

  1.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
  Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
  State Government Printing Office, Washington D.C., 1987.

  2.  Snyder, John P. and Voxland, Philip M., "An Album of Map Projections",
  U.S. Geological Survey Professional Paper 1453 , United State Government
  Printing Office, Washington D.C., 1989.
 *******************************************************************************/

use proj4php\Proj4php;
use proj4php\Common;

class Equi
{
    public $a;
    public $lat0;
    public $long0;
    public $t1;
    public $t2;
    public $x0;
    public $y0;

    public function init()
    {
        if (! $this->x0) {
            $this->x0 = 0;
        }

        if (! $this->y0) {
            $this->y0 = 0;
        }

        if (! $this->lat0) {
            $this->lat0 = 0;
        }

        if (! $this->long0) {
            $this->long0 = 0;
        }
        ///$this->t2;
    }

    /*(
     * Equirectangular forward equations--mapping lat,long to x,y
     */
    public function forward($p)
    {
        $lon = $p->x;
        $lat = $p->y;

        $dlon = Common::adjust_lon($lon - $this->long0);

        $x = $this->x0 + $this->a * $dlon * cos($this->lat0);
        $y = $this->y0 + $this->a * $lat;

        $this->t1 = $x;
        $this->t2 = cos($this->lat0);

        $p->x = $x;
        $p->y = $y;

        return $p;
    }

    /**
     * Equirectangular inverse equations--mapping x,y to lat/long
     */
    public function inverse($p)
    {
        $p->x -= $this->x0;
        $p->y -= $this->y0;

        $lat = $p->y / $this->a;

        if (abs($lat) > Common::HALF_PI) {
            Proj4php::reportError("equi:Inv:DataError");
        }

        $lon = Common::adjust_lon( $this->long0 + $p->x / ($this->a * cos( $this->lat0 )) );

        $p->x = $lon;
        $p->y = $lat;
    }
}
