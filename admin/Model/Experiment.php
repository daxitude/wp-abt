<?php
/*
 * Experiment model
 *
	Table def
	==========
	id bigint(20) unsigned NOT NULL auto_increment,
	experiment_name varchar(255) NOT NULL default '',
	start_date datetime NULL default NULL,
	end_date datetime NULL default NULL,
	status tinyint(11) NOT NULL default '0',			
	confidence decimal(2,2) NOT NULL default '0.8',			
	effect tinyint(11) NOT NULL default '10',			
	goal_page_id bigint(20) unsigned NOT NULL default '0',
	goal_name varchar(255) NOT NULL default '',
	PRIMARY KEY  (id)
*/
class ABT_Model_Experiment extends ABT_Model_Base {
	
	// instance attributes that are stored in db
	public $id;
	public $experiment_name = array('required' => true);
	public $start_date;
	public $end_date;
	public $status;
	public $effect = array('default' => 10);
	public $confidence = array('default' => 0.9);
	public $goal_page_id = array('required' => true);
	public $goal_name = array('required' => true);

	// constants to convert integer status values to text
	const READY = 0;
	const RUNNING = 1;
	const COMPLETED = 2;
	private static $statuses = array('Ready', 'Running', 'Completed');
	
	// table name in db
	protected static $table_name = 'experiments';
	
	// get model's table name
	public static function get_db_table() {
		$name = self::$table_name;
		return self::get_db_tables()->$name;
	}
	
	/*
	 * override delete and update
	 */	
	public function update($new) {
		if (isset($new['status']) && $new['status'] == self::RUNNING && $this->status == self::READY)
			$new['start_date'] = date("Y-m-d H:m:s");
		if (isset($new['status']) && $new['status'] == self::COMPLETED && $this->status == self::RUNNING)
			$new['end_date'] = date("Y-m-d H:m:s");

		return parent::update($new);
	}
	// override delete() to include deleting any associated variation
	public static function delete($id) {
		$db = self::get_db();
		$tables = self::get_db_tables();
		$db->delete( $tables->variations, array( 'experiment_id' => $id ), array( '%d' ) );
		$db->delete( self::get_db_table(), array( 'id' => $id ), array( '%d' ) );
	}
	
	
	/*
	 * static methods for fetching from database
	 */
		
	static function by_id($id) {
		$db = self::get_db();
		$table = self::get_db_table();
		$tables = self::get_db_tables();
		$exp = $db->get_row(
			$db->prepare(
				"SELECT p.*, c.post_title
				FROM $table p
				INNER JOIN $tables->posts c ON p.goal_page_id = c.ID
				WHERE p.id = $id"
			)
		);
		$exp = new self($exp);
		return $exp;
	}
	
	static function by_goal_page_id($id) {
		$db = self::get_db();		
		$table = self::get_db_table();
		$exps = $db->get_results(
			$db->prepare(
				"SELECT * FROM $table p			
				WHERE p.goal_page_id = $id"
			)
		);
		if (!$exps) return false;		
		$exp_objects = array();
		foreach ($exps as $exp) {
			$exp_objects[] = new self($exp);
		}
		return $exp_objects;
	}
		
	static function all() {
		return self::where(null);
	}
	
	// @todo - test this for more than one param
	static function where($where = null) {
		$db = self::get_db();
		$table = self::get_db_table();
		$tables = self::get_db_tables();
		
		if ($where !== null) {
			$where = self::where_query_string($where);
		}
				
		$exps = $db->get_results(
			$db->prepare(
				"SELECT p.*,
				SUM(c.visits) total_visits, SUM(c.conversions) total_conversions,
				e.post_title
				FROM $table p
				LEFT JOIN $tables->variations c ON p.id = c.experiment_id
				LEFT JOIN $tables->posts e ON goal_page_id = e.ID
				$where				
				GROUP BY p.id
				ORDER BY experiment_name"
			)
		);
		$exp_objects = array();
		foreach ($exps as $exp) {
			$exp_objects[] = new self($exp);
		}
		return $exp_objects;
	}
	
	/*
	 * helper methods for working with an instance and rendering a view
	 */
	
	// permalink to the goal page
	public function goal_page_link() {
		return get_permalink($this->goal_page_id);
	}
	
	// HTML in the model? I know this smells, but the logiclessness of Mustache is throwing
	// me for a loop. For now, this is the fastest and easiest way to get this done.
	public function status_text() {
		$text = self::$statuses[$this->status];
		switch ($this->status) {
			case '0':
				$text = ($this->num_variations() < 2) ?
					'<strong class="label badge warning">&nbsp;!&nbsp;</strong> &nbsp;<em>Please create at least 2 variations.</em>' :
					'<strong class="label badge info">✓</strong> &nbsp;' . $text;
				break;
			case '1':
				$text = '<strong class="label">' . $text . '</strong>';
				$text .= ($this->days_to_confidence()) ?
					' Approximately ' . max((int)$this->days_to_confidence() - (int)$this->days_running(), 0) .
						' days remaining.' :
					' Need more data to estimate time remaining.';
				break;
			case '2':
				$text .= ' in ' . $this->days_running() . ' days.';
				break;
		}	
		return $text;
	}
	
	// bool, true if is status == ready and has at least 2 variations
	public function can_start() {
		return $this->is_ready() && $this->num_variations() > 1;
	}
	
	// bool, true if running
	public function can_stop() {
		return $this->is_running();
	}
	
	// check how many variations exist in the experiment. used this to check
	// if result = 0 and set the first saved variation as the Base variation. also 
	// used to verify variation count for status transitions
	public function num_variations() {
		$db = self::get_db();
		$tables = self::get_db_tables();		
		$vars = $db->get_row(
			$db->prepare(
				"SELECT COUNT(p.id) total FROM $tables->variations p
				WHERE p.experiment_id = $this->id"
			)
		);
		return $vars->total;
	}
	
	public function is_ready() {
		return (int) $this->status == self::READY;
	}
	
	public function is_running() {
		return (int) $this->status == self::RUNNING;
	}
	
	// formatters for dates. @oof for putting in model, but with logiclessness
	// of Mustache templates, this seems to be the fastest and easiest way
	public function format_date($date) {
		// silly php bug strtotime not returning false for '0000..' mysql null val
		$date = strtotime($date);
		return $date > 0 ? date('D, M d g:h a', $date) : '--';
	}
	
	public function start_date_f() {
		return $this->format_date($this->start_date);
	}
	
	public function end_date_f() {
		return $this->format_date($this->end_date);
	}

	// calculate total experiment visits as a sum of all variations' visits
	public function total_visits() {
		if (isset($this->total_visits)) return $this->total_visits;
		$db = self::get_db();
		$tables = self::get_db_tables();
		$exp = $db->get_row(
			$db->prepare(
				"SELECT SUM(p.visits) total_visits
				FROM $tables->variations p
				WHERE p.experiment_id = $this->id"
			)
		);
		$this->total_visits = $exp->total_visits;
		return $exp->total_visits;
	}
	
	// calculate total experiment conversions as a sum of all variations' conversions
	public function total_conversions() {
		if (isset($this->total_conversions)) return $this->total_conversions;
		$db = self::get_db();
		$tables = self::get_db_tables();
		$exp = $db->get_row(
			$db->prepare(
				"SELECT SUM(p.conversions) total_conversions
				FROM $tables->variations p
				WHERE p.experiment_id = $this->id"
			)
		);
		$this->total_conversions = $exp->total_conversions;
		return $exp->total_conversions;
	}
	
	// calculate number of days an experiment has been running
	public function days_running() {
		switch ($this->status) {
			case '0':
				$days = 0;
				break;
			case '1':
				$days = round((date('U') - date('U', strtotime($this->start_date))) / (60*60*24));
				break;
			case '2':
				$days = round((date('U', strtotime($this->end_date)) - date('U', strtotime($this->start_date))) / (60*60*24));
				break;
		}
		return $days;
	}
	
	// calculate number of days to specified confidence level
	// http://blog.marketo.com/blog/2007/10/landing-page-1.html
	// uses 0.8 for the Power
	public function days_to_confidence() {
		if ( $this->days_running() < 4 || $this->total_visits() < 10 || $this->total_conversions() < 2 ) return false;
		$num_vars = $this->num_variations();
		$ecr = $this->total_conversions() / $this->total_visits();
		$evd = ($this->total_visits() / $this->days_running());
		$de = $this->effect / 100;
		$conf = $this->confidence;
		$numer = 2 * $num_vars * POW(( $this->p_to_z($conf + (1 - $conf)/2) + $this->p_to_z(0.8) ), 2) * (1 - $ecr);
		$denom = POW($de, 2) * $evd * $ecr;
		$days = $numer / $denom;
		return round($days);
	}
	
	// private, convert a p-value to a z-score with a std normal distribution
	// http://www.fourmilab.ch/rpkp/experiments/analysis/zCalc.html
	private function p_to_z($p) {
		$Z_EPSILON = 0.000001;     /* Accuracy of z approximation */
	    $minz = -6;
	    $maxz = 6;
	    $zval = 0;
	    $pval;

	    if ( $p < 0 || $p > 1) return -1;

	    while ( ($maxz - $minz) > $Z_EPSILON ) {
	        $pval = $this->poz($zval);
	
	        if ($pval > $p) {
	            $maxz = $zval;
	        } else {
	            $minz = $zval;
	        }
	        $zval = ($maxz + $minz) * 0.5;
	    }
	    return $zval;
	}

	// http://www.fourmilab.ch/rpkp/experiments/analysis/zCalc.html
	private function poz($z) {
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
	
	/*
	 * these 4 methods wont' work < php 5.3 (no static:: or get_called_class())
	 * so going to reluctantly repeat them in sub classes for now
	 */
	
	public static function first($where = null) {
		$db = self::get_db();
		$table = self::get_db_table();
		
		if ($where !== null) {
			$where = self::where_query_string($where);
		}
		
		$record = $db->get_row(
			$db->prepare(
			"SELECT * FROM $table p
			$where"
			)
		);
		// return a new instance of the subclass that called this method
		return $record ? new self($record) : false;
	}
	
	public static function find_by_id($id) {
		$db = self::get_db();
		$table = self::get_db_table();
		$record = $db->get_row(
			$db->prepare(
			"SELECT * FROM $table
			WHERE id = $id"
			)
		);
		// return a new instance of the subclass that called this method
		return $record ? new self($record) : false;
	}
	
	public static function count($where = null) {
		$db = self::get_db();
		$table = self::get_db_table();
		
		if ($where !== null) {
			$where = self::where_query_string($where);
		}
		
		$items = $db->get_row(
			$db->prepare(
				"SELECT COUNT(p.id) total FROM $table p
				$where"
			)
		);
		return $items->total;
	}
	
}