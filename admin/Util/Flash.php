<?php
/**
 * Bare bones flash message class
 * inspiration from Slim Framework (http://www.slimframework.com)
 */

class ABT_Util_Flash {
	
	protected $messages = array( 'prev' => array(), 'next' => array() );
	
	public function __construct() {
		$this->load();
	}
	
	public function load() {
        $this->messages['prev'] = isset($_SESSION['flash']) ? $_SESSION['flash'] : array();
        return $this;
    }

	public function set($key, $value) {
		$this->messages['next'][$key] = $value;
        return $this->save();
	}
	
	public function get($key = false) {
        return $key ? $this->messages['prev'][$key] : $this->messages['prev'];
    }

	public function save() {
        $_SESSION['flash'] = $this->messages['next'];
        return $this;
    }

}