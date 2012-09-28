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
				$text .= ($this->days_needed()) ?
					' &nbsp;Approximately ' . $this->days_needed() .
						' days needed.' :
					' &nbsp;Need more data.';
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
	
	// http://www.evanmiller.org/how-not-to-run-an-ab-test.html
	public function detectable_effect() {
		$visits = $this->total_visits();
		$conv = $this->total_conversions();
		if ($visits < 1 || $conv < 1) return false;
		$rate = $conv / $visits;
		$conf = $this->confidence;
		$eff = ( ABT_Util_Stats::ptz($conf + (1 - $conf)/2) + ABT_Util_Stats::ptz(0.8) ) * 
			SQRT($rate * (1 - $rate)) * SQRT(2 / $visits);

		return round($eff, 3);
	}
	
	public function visits_needed() {
		$visits = $this->total_visits();
		if ($visits < 1) return false;
		$de = $this->effect / 100;
		$conf = $this->confidence;		
		$ecr = $this->total_conversions() / $visits;
		$total_needed = round(( (2 * POW(( ABT_Util_Stats::ptz($conf + (1 - $conf)/2)
			+ ABT_Util_Stats::ptz(0.8) ), 2) * $ecr * (1 - $ecr)) / POW($de, 2) ), 0);
		
		$visits_left = $total_needed - $this->total_visits();
		return max($visits_left, 0);
	}
	
	// calculate number of days to specified confidence level
	// http://blog.marketo.com/blog/2007/10/landing-page-1.html
	// uses 0.8 for the Power
	public function days_needed() {
		$total_visits = $this->total_visits();
		$days_running = $this->days_running();
		if ($total_visits < 1 || $days_running < 1) return '_?_';
		$visits_per_day = ($total_visits / $days_running);
		$days_rm = $this->visits_needed() / $visits_per_day;
		return round(max($days_rm, 0));
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