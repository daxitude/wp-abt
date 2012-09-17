<?php

abstract class ABT_Admin_Page {
	
	function __construct($_REQUEST) {
		$this->id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
		$this->experiment_id = isset($_REQUEST['experiment_id']) ? $_REQUEST['experiment_id'] : false;
		$this->GET_VARS = $_GET;
		if ( !isset($_SESSION) ) session_start();
		
		if ( isset($_REQUEST['action']) ) {
			$this->route($_REQUEST);
		}
		else {
			add_action('admin_menu', array(&$this, 'admin_menu'));
			add_action('admin_print_scripts', array(&$this, 'add_js'));
			add_action('admin_print_scripts', array(&$this, 'add_css'));
		}
		// MAMP seems to be acting up
		date_default_timezone_set('America/Chicago');
	}
	
	function admin_menu() {}
	
	function add_js() {
		wp_register_script( 'abt_js', plugins_url('', __file__) . '/assets/abt.dev.js', array('jquery'), '0.1', 'true' );
		wp_enqueue_script( 'abt_js' );
		wp_enqueue_script('dashboard');
	}
	
	function add_css() {
		wp_register_style( 'abt_css', plugins_url('', __file__) . '/assets/abt.dev.css', '0.1' );
		wp_enqueue_style( 'abt_css' );
	}
	
	function route() {}
		
}

