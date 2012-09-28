<?php
/*
 * Variation model
 *
	Table def
	==========
	id bigint(20) unsigned NOT NULL auto_increment,
	experiment_id bigint(20) unsigned NOT NULL default '0',
	post_id bigint(20) unsigned NOT NULL default '0',
	variation_name varchar(255) NOT NULL default '',
	visits int(11) NOT NULL default '0',
	conversions int(11) NOT NULL default '0',
	base BOOLEAN NOT NULL DEFAULT '0',
	PRIMARY KEY  (id),
	KEY experiment_id (experiment_id),
	KEY post_id (post_id)
 */
class ABT_Model_Variation extends ABT_Model_Base {
	
	// instance methods that are stored in db
	public $id;
	public $experiment_id = array('required' => true);
	public $post_id = array('required' => true);
	public $variation_name = array('required' => true);
	public $visits;
	public $conversions;
	public $base;
	
	// table name in db
	protected static $table_name = 'variations';
	
	public static function get_db_table() {
		$name = self::$table_name;
		return self::get_db_tables()->$name;
	}
	
	/*
	 * static methods for fetching from database
	 */
	
	// joins with posts table
	static function by_id($id) {
		$db = self::get_db();		
		$table = self::get_db_table();
		$tables = self::get_db_tables();
		$var = $db->get_row(
			$db->prepare(
				"SELECT * FROM $table p			
				INNER JOIN $tables->posts c ON
				p.post_id = c.ID
				WHERE p.id = $id"
			)
		);
		$post_title = $var->post_title;
		$var = new self($var);
		$var->post_title = $post_title;
		return $var;
	}
	
	static function by_experiment_id($id) {
		$db = self::get_db();
		$table = self::get_db_table();
		$tables = self::get_db_tables();
		$vars = $db->get_results(
			$db->prepare(
				"SELECT * FROM $table p			
				INNER JOIN $tables->posts c ON
				p.post_id = c.ID
				WHERE p.experiment_id = $id
				ORDER BY p.base DESC"
			)
		);
		$var_objects = array();
		foreach ($vars as $var) {
			$var_objects[] = new self($var);
		}
		return $var_objects;
	}
	
	static function by_post_id($id) {
		$db = self::get_db();		
		$table = self::get_db_table();
		$var = $db->get_row(
			$db->prepare(
				"SELECT * FROM $table p			
				WHERE p.post_id = $id"
			)
		);
		return $var ? new self($var) : false;
	}
	
	// get an experiment's variation with least number of views
	static function get_least_viewed($experiment_id) {
		$db = self::get_db();		
		$table = self::get_db_table();
		$var = $db->get_row(
			$db->prepare(
				"SELECT * FROM $table p		
				WHERE p.experiment_id = $experiment_id
				ORDER BY p.visits
				LIMIT 1"
			)
		);
		return $var ? new self($var) : false;
	}
	
	// get the "base" variation. 
	static function get_base_variation($experiment_id) {
		$db = self::get_db();		
		$table = self::get_db_table();
		$var = $db->get_row(
			$db->prepare(
				"SELECT * FROM $table p		
				WHERE p.experiment_id = $experiment_id AND p.base = 1
				LIMIT 1"
			)
		);
		return $var ? new self($var) : false;
	}
	
	// get the whole column of post ids. used to filter out options from the html <select>.
	// chinsy way to enforce uniqueness without any validation
	static function get_post_ids() {
		$db = self::get_db();		
		$table = self::get_db_table();
		return $db->get_col("SELECT post_id FROM $table", 0);
	}
	
	/*
	 * some helper methods for working with an instance
	 */
	
	// calc conversion rate
	public function rate() {
		return ($this->visits > 0) ? round($this->conversions / $this->visits, 4) : 0;
	}
	
	public function compare_to_base() {
		if ($this->base) return '--';
		$base = $this->get_base_variation($this->experiment_id);
		$base_cr = $base->rate();
		return ($base_cr > 0) ? round(( $this->rate() - $base_cr ) / $base_cr, 2) : 0;
	}
	
	// get the page's permalink
	public function get_page_link() {
		return get_permalink($this->post_id);
	}
	
	// increment the stored visits value
	public function visited() {
		$db = self::get_db();		
		$table = self::get_db_table();
		$var = $db->get_results(
			$db->prepare(
				"UPDATE $table p
				SET p.visits = p.visits + 1			
				WHERE p.id = $this->id"
			)
		);
		return $var;
	}
	
	// increment the stored conversions value
	public function converted() {
		$db = self::get_db();		
		$table = self::get_db_table();
		$var = $db->get_results(
			$db->prepare(
				"UPDATE $table p
				SET p.conversions = p.conversions + 1			
				WHERE p.id = $this->id"
			)
		);
		return $var;
	}
	
	// calc std normal distribution val for a given z-score
	// http://stackoverflow.com/questions/5259421/cumulative-distribution-function-in-javascript
	private function norm_dist($m, $s, $t) {
		$z = ($t - $m) / SQRT( 2 * $s * $s );
		$t = 1 / ( 1 + 0.3275911 * abs($z) );
		$a1 =  0.254829592;
		$a2 = -0.284496736;
		$a3 =  1.421413741;
		$a4 = -1.453152027;
		$a5 =  1.061405429;
		$erf = 1 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * EXP(-$z*$z);
		if ( $z < 0 ) $erf = $erf * -1;
		return 0.5 * (1 + $erf);
	}
	
	// calc confidence level
	public function confidence_level() {
		$base = self::get_base_variation($this->experiment_id);
		$exp = ABT_Model_Experiment::by_id($this->experiment_id);
		$expconf = $exp->confidence;
		
		if ($this->base || $this->visits < 15 || $base->visits < 15) return '--';
		$base_conv_rate = $base->rate();
		$var_conv_rate = $this->rate();
		$variance = ABT_Util_Stats::ptz($expconf + (1 - $expconf) / 2) *
			SQRT( $base_conv_rate * (1 - $base_conv_rate) / $base->visits );
		$conf = $this->norm_dist($base_conv_rate, $variance, $var_conv_rate);
		return round($conf, 3);
	}
	
	/*
	 * these 4 methods wont' work < php 5.3 (no static:: or get_called_class())
	 * so going to reluctantly repeat them in sub classes for now
	 */
	
	// see wp-db.php line 1275
	public static function delete($id) {
		$db = self::get_db();
		return $db->delete( self::get_db_table(), array( 'id' => $id ), array( '%d' ) );
	}
	
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