<?php

if ( !class_exists( 'ABT_DB_Model' ) )
	require dirname(__FILE__) . '/db_model.php';

/*
id bigint(20) unsigned NOT NULL auto_increment,
experiment_id bigint(20) unsigned NOT NULL default '0',
post_id bigint(20) unsigned NOT NULL default '0',
variation_name varchar(255) NOT NULL default '',
visits int(11) NOT NULL default '0',
conversions int(11) NOT NULL default '0',
base BOOLEAN NOT NULL DEFAULT '0',
PRIMARY KEY (id),
KEY id (id),
key post_id (post_id)
*/
class Variation extends ABT_DB_Model {
	
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
	
	static function get_post_ids() {
		$db = self::get_db();		
		$table = self::get_db_table();
		return $db->get_col("SELECT post_id FROM $table", 0);
	}
	
	/*
	 * some helper methods for working with an instance
	 */
	
	public function rate() {
		return ($this->visits > 0) ? round($this->conversions / $this->visits, 4) * 100 . '%' : '0';
	}
	
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
	
	public function norm_dist($x) {
		$d1 = 0.0498673470;
		$d2 = 0.0211410061;
		$d3 = 0.0032776263;
		$d4 = 0.0000380036;
		$d5 = 0.0000488906;
		$d6 = 0.0000053830;

		$a = abs($x);
		$t = 1.0 + $a * ($d1 + $a * ($d2 + $a * ($d3 + $a * ($d4 + $a * ($d5 + $a * $d6)))));
		$t = $t * $t;
		$t = $t * $t;
		$t = $t * $t;
		$t = $t * $t;
		$t = 1.0 / (2 * $t);
		if ($x > 0) $t = 1 - $t;
		return $t;
	}
	
	public function p_value() {
		$base = Variation::get_base_variation($this->experiment_id);
		if ($this->base || $this->visits < 15 || $base->visits < 15) return '--';
		$base_conv_rate = $base->conversions / $base->visits;
		$var_conv_rate = $this->conversions / $this->visits;
		$var_std_err = SQRT( $base_conv_rate * (1 - $base_conv_rate) / $base->visits + $var_conv_rate * (1 - $var_conv_rate) / $this->visits ); 
		$var_z_score = ($base_conv_rate - $var_conv_rate) / $var_std_err;
		$p_value = $this->norm_dist($var_z_score);
		return round($p_value, 3) * 100;
	}
	
	
	/*
	 * these 4 methods wont' work < php 5.3 so going to reluctantly repeat them in sub classes
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