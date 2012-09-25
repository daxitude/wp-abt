<?php
/*
 * abstract class for representing data from the wpdb
 */
abstract class ABT_Model_Base {

/*	Declare model attributes like this:
	========
	public $id;
	public $name = array('required' => true);
*/	
	
	// stores attribute configuration
	private $_attributes = null;
	// stores validation errors. array keys by model attribute
	// accessible by public method errors()
	private $_errors = null;
	
	// when a model is instantiated an array of attributes can be passed.
	// ALL attributes passed are set but only those that are declared will
	// pass thru validation and be allowed to persist
	function __construct($opts) {
		$this->defined_attrs();
		$opts = (array) $opts;
		foreach ($opts as $key => $value) {
			$this->$key = $value;
		}
	}
	
	/*
	 * set up access to models' db table
	 * @todo should put these on ABT_DB class
	 */
	// return a reference to the global wpdb object
	static function get_db() {
		global $wpdb;
		return $wpdb;
	}
	// return an array of the db table names used by the plugin
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
	// NOTE: all being inserted as string right now
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
		$this->set_attributes($new_values);
		$data = $this->get_attributes();
		if ( !$this->validate() ) return false;		
		$where = array('id' => $this->id);
		$update = $db->update( $this->get_db_table(), $data, $where, null, array('%d') );
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
	 * attribute helper methods
	 */
	
	// return an array of null/not null declared public model attributes
	// @public - only returns public attributes
	public function get_properties($null_values = false) {
		$get_fields = create_function('$obj', 'return get_object_vars($obj);');
		$fields = $get_fields($this);
		return $null_values ? $fields : array_filter((array) $fields);
	}
	
	// return an array of declared model attributes and their configuration.
	// if called on model instantiation, reset each attribute to null so values
	// can be set on them
	public function defined_attrs() {
		if ($this->_attributes) return $this->_attributes;
		$model_attrs = $this->get_properties(true);
		$this->_attributes = $model_attrs;
		foreach ($model_attrs as $key => $value) {
			$this->$key = null;
		}
		return $model_attrs;
	}
	
	// bool, check whether the model has a certain declared attribute
	public function has_attribute($attribute) {
		return array_key_exists($attribute, $this->defined_attrs());
	}
	
	// mass assignment of an array of attributes. will only set declared public attributes
	public function set_attributes($attrs) {
		foreach ((array) $attrs as $prop => $value) {
			if ($this->has_attribute($prop))
				$this->$prop = $value;
		}
	}
	
	// @internal, for persistance
	// return an array of persistable model attributes
	private function get_attributes() {
		$data = (array) $this;
		$props = $this->defined_attrs();
		return array_intersect_key($data, $props);
	}
	
	/*
	 * methods for validation and errors
	 */
	
	// @todo, right now only validates 'required'
	// @return true on has_errors. false for no errors
	public function validate() {
		foreach($this->defined_attrs() as $field => $attrs) {
			// if is a required field
			if( isset($attrs['required']) && $attrs['required'] && empty($this->$field) ) {
				$this->add_error($field, $field . ' is required.');
			}
		}		
		return $this->has_errors() ? false : true;
	}
	
	// add an error to model state for a given attribute
	public function add_error($attribute, $msgs) {
		if ( !isset($this->_errors[$attribute]) ) $this->_errors[$attribute] = array();

		if( is_array($msgs) ) {
			foreach($msgs as $msg) {
				$this->_errors[$attribute][] = $msg;
			}
		} else {
			$this->_errors[$attribute][] = $msgs;
		}
	}
	
	// return whether model has errors. can take a specific model attribute to check
	// for errors
	public function has_errors($attribute = null) {
		if($attribute !== null) {
			return isset($this->_errors[$attribute]) ? count($this->_errors[$attribute]) : false;
		}
		return count($this->_errors) > 0 ? true : false;
	}
	
	// return an array of model errors
	public function errors() {
		return $this->_errors;
	}
	
	// take an array of 'where' params and convert to sql string
	protected static function where_query_string($where) {
		foreach ($where as $key => $value) {
			$where[$key] = 'p.'.$key.'=\''.$value.'\'';
		}
		return 'WHERE '. implode(' AND ', $where);
	}
	
	
}