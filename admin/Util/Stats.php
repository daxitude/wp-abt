<?php


class ABT_Util_Stats {
	
	public $method;
	public $body = array();
	
	// http://www.fourmilab.ch/rpkp/experiments/analysis/zCalc.html
	public static function poz($z) {
		$z_max = 6;
		
	    if ($z == 0) {
	        $x = 0;
	    } else {
	        $y = 0.5 * abs($z);
	
	        if ( $y > ($z_max * 0.5) ) {
	            $x = 1;
	
	        }
			else if ( $y < 1 ) {
	            $w = $y * $y;
	            $x = ((((((((0.000124818987 * $w
	                     - 0.001075204047) * $w + 0.005198775019) * $w
	                     - 0.019198292004) * $w + 0.059054035642) * $w
	                     - 0.151968751364) * $w + 0.319152932694) * $w
	                     - 0.531923007300) * $w + 0.797884560593) * $y * 2;
	        }
			else {
	            $y -= 2.0;
	            $x = (((((((((((((-0.000045255659 * $y
	                           + 0.000152529290) * $y - 0.000019538132) * $y
	                           - 0.000676904986) * $y + 0.001390604284) * $y
	                           - 0.000794620820) * $y - 0.002034254874) * $y
	                           + 0.006549791214) * $y - 0.010557625006) * $y
	                           + 0.011630447319) * $y - 0.009279453341) * $y
	                           + 0.005353579108) * $y - 0.002141268741) * $y
	                           + 0.000535310849) * $y + 0.999936657524;
	        }
	    }
	    return $z > 0 ? ( ($x + 1) * 0.5 ) : ( (1 - $x) * 0.5 );
	}
	
	// http://www.fourmilab.ch/rpkp/experiments/analysis/zCalc.html
	public static function ptz($p) {
		$Z_EPSILON = 0.000001;
	    $minz = -6;
	    $maxz = 6;
	    $zval = 0;
	    $pval;

	    if ( $p < 0 || $p > 1) return -1;

	    while ( ($maxz - $minz) > $Z_EPSILON ) {
	        $pval = self::poz($zval);
	
	        if ($pval > $p) {
	            $maxz = $zval;
	        } else {
	            $minz = $zval;
	        }
	        $zval = ($maxz + $minz) * 0.5;
	    }
	    return $zval;
	}
	
}