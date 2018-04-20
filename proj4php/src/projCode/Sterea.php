<?php

namespace proj4php\projCode;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4JS from Mike Adair madairATdmsolutions.ca
 * and Richard Greenwood rich@greenwoodma$p->com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */

use proj4php\Proj4php;
use proj4php\Common;
use proj4php\Point;

class Sterea extends Gauss
{
    public $R2;
    public $a;
    public $cosc0;
    public $k0;
    public $long0;
    public $sinc0;
    public $title;
    public $x0;
    public $y0;

    /**
     * @return void 
     */
    public function init()
    {
        // Initialise dependant projection first.
        parent::init();

        if (! $this->rc) {
            Proj4php::reportError("sterea:init:E_ERROR_0");
            return;
        }

        $this->sinc0 = sin($this->phic0);
        $this->cosc0 = cos($this->phic0);
        $this->R2 = 2.0 * $this->rc;

        if (! $this->title) {
            $this->title = "Oblique Stereographic Alternative";
        }
    }

    /**
     * @param Point $p
     * @return Point
     */
    public function forward($p)
    {
        // adjust del longitude
        $p->x = Common::adjust_lon($p->x - $this->long0);

        //$p = Proj4php::$proj['gauss']->forward($p);
        $p = parent::forward($p);

        $sinc = sin($p->y);
        $cosc = cos($p->y);
        $cosl = cos($p->x);
        $k = $this->k0 * $this->R2 / (1.0 + $this->sinc0 * $sinc + $this->cosc0 * $cosc * $cosl);

        $p->x = $k * $cosc * sin( $p->x );
        $p->y = $k * ($this->cosc0 * $sinc - $this->sinc0 * $cosc * $cosl);

        $p->x = $this->a * $p->x + $this->x0;
        $p->y = $this->a * $p->y + $this->y0;

        return $p;
    }

    /**
     * @param Point $p
     * @return Point
     */
    public function inverse($p)
    {
        // descale and de-offset
        $p->x = ($p->x - $this->x0) / $this->a;
        $p->y = ($p->y - $this->y0) / $this->a;

        $p->x /= $this->k0;
        $p->y /= $this->k0;

        if (($rho = sqrt($p->x * $p->x + $p->y * $p->y))) {
            $c = 2.0 * atan2($rho, $this->R2);
            $sinc = sin($c);
            $cosc = cos($c);
            $lat = asin($cosc * $this->sinc0 + $p->y * $sinc * $this->cosc0 / $rho);
            $lon = atan2($p->x * $sinc, $rho * $this->cosc0 * $cosc - $p->y * $this->sinc0 * $sinc);
        } else {
            $lat = $this->phic0;
            $lon = 0.;
        }

        $p->x = $lon;
        $p->y = $lat;
        //$p = Proj4php::$proj['gauss']->inverse($p);
        $p = parent::inverse($p);
        // adjust longitude to CM
        $p->x = Common::adjust_lon($p->x + $this->long0);

        return $p;
    }
}
