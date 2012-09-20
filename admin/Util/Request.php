<?php


class ABT_Util_Request {
	
	public $method;
	public $body = array();
	
	function __construct() {
		// create, update, delete sent as form param and override GET/POST
		$this->method = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'get';
		$this->body = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $_GET;
	}
	
	private function __isset($name) {
		return isset($this->body[$name]);
	}
	
	public function __get($name) {
		if ( property_exists($this, $name) ) return $this->$name;
		return $this->__isset($name) ? $this->body[$name] : false;
	}
	
	public function method() {
		return $this->method;
	}
	
	public function body() {
		return $this->body;
	}
	
}