<?php

abstract class ABT_DB_Model {
	
	private $_attributes = null;
	private $_errors = null;
		
	function __construct($opts) {
		$this->defined_attrs();
		$opts = (array) $opts;
		foreach ($opts as $key => $value) {
			$this->$key = $value;
		}
		return $this;		
	}
	
	/*
	 * set up access to models' db table
	 * @todo should put these on ABT_DB class
	 */
	
	static function get_db() {
		global $wpdb;
		return $wpdb;
	}
	
	static function get_db_tables() {
		$db = self::get_db();
		$prefix = $db->prefix;
		return (object) array(
			'posts' => $prefix."posts",
			'experiments' => $prefix."abt_experiments",
			'variations' => $prefix."abt_variations"
		);
	}
	
	/*
	 * CRUD operations
	 */
	
	// see wp-db.php line 1140
	// NOTE: all being inserted as string
	public function create() {		
		$db = $this->get_db();
		$data = $this->get_attributes();
		if ( !$this->validate() ) return false;
		$db->insert( $this->get_db_table(), $data );
		$this->id = $db->insert_id;		
		$this->_errors = null;
		return $this;
	}
	// see wp-db.php line 1224
	public function update($new_values) {
		$db = $this->get_db();
		$data = $this->get_attributes($new_values);
		if ( !$this->validate() ) return false;
		$where = array('id' => $this->id);
		$db->update( $this->get_db_table(), $data, $where );
		$this->_errors = null;
		return $this;
	}
	
	/*
	 * these 4 methods wont' work < php 5.3 so going to reluctantly repeat them in sub classes
	 */

/*	
	// see wp-db.php line 1275
	public static function delete($id) {
		$class= get_called_class();
		$db = self::get_db();
		return $db->delete( $class::get_db_table(), array( 'id' => $id ), array( '%d' ) );
	}
	
	public static function first($where = null) {
		$class= get_called_class();
		$db = self::get_db();
		$table = $class::get_db_table();
		
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
		return $record ? new $class($record) : false;
	}
	
	public static function find_by_id($id) {
		$class= get_called_class();
		$db = self::get_db();
		$table = $class::get_db_table();
		$record = $db->get_row(
			$db->prepare(
			"SELECT * FROM $table
			WHERE id = $id"
			)
		);
		// return a new instance of the subclass that called this method
		return $record ? new $class($record) : false;
	}
	
	public static function count($where = null) {
		$class= get_called_class();
		$db = self::get_db();
		$table = $class::get_db_table();
		
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
*/	
	/*
	 * property helper methods
	 */
	
	// array_filter removes null values
	public function get_properties($null_values = false) {
		$get_fields = create_function('$obj', 'return get_object_vars($obj);');
		$fields = $get_fields($this);
		return $null_values ? $fields : array_filter((array) $fields);
	}
	
	public function defined_attrs() {
		if ($this->_attributes) return $this->_attributes;
		$model_attrs = $this->get_properties(true);
		$this->_attributes = $model_attrs;
		foreach ($model_attrs as $key => $value) {
			$this->$key = null;
		}
		return $model_attrs;
	}
	
	public function has_attribute($field) {
		return array_key_exists($field, $this->defined_attrs());
	}
	
	public function get_attributes($new_vals = null) {
		$data = array();
		$props = $this->defined_attrs();
		foreach ($props as $prop => $value) {
			$data[$prop] = ($new_vals && isset($new_vals[$prop])) ? $new_vals[$prop] : $this->$prop;
			$this->$prop = $data[$prop];
		}
		return $data;
	}
	
	// @todo, right now only validates required
	public function validate() {
		foreach($this->defined_attrs() as $field => $attrs) {
			// if is a required field
			if( isset($attrs['required']) && $attrs['required'] && empty($this->$field) ) {
				$this->add_error($field, $field . ' is required.');
			}
		}		
		return $this->has_errors() ? false : true;
	}
	
	public function add_error($field, $msgs) {
		if ( !isset($this->_errors[$field]) ) $this->_errors[$field] = array();

		if( is_array($msgs) ) {
			foreach($msgs as $msg) {
				$this->_errors[$field][] = $msg;
			}
		} else {
			$this->_errors[$field][] = $msgs;
		}
	}
	
	public function has_errors($field = null) {
		if($field !== null) {
			return isset($this->_errors[$field]) ? count($this->_errors[$field]) : false;
		}
		return count($this->_errors) > 0 ? true : false;
	}

	public function errors() {
		return $this->_errors;
	}
	
	protected static function where_query_string($where) {
		foreach ($where as $key => $value) {
			$where[$key] = 'p.'.$key.'=\''.$value.'\'';
		}
		return 'WHERE '. implode(' AND ', $where);
	}
	
	
}