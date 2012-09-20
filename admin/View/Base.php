<?php
/*
 * abstract class for a plugin page in wp-admin
 * takes in the page request params (GET or POST), populates some 
 * instance properties, and routes if a particular CRUD action is specified
 * these aren't exactly views...more like controllers. Maybe.
 */
abstract class ABT_View_Base {
	
	// page name eg /?page=$page_name. also added to hashing for the nonce
	protected static $page_name;
	
	protected $flash;
	
	function __construct() {}
	
	// gets called by the mgr right before routing
	// @todo don't really need to load these if on a C, U, or D operation
	function init() {
		add_action('admin_print_scripts', array($this, 'add_js'));
		// @todo why is admin_print_styles printing near the footer and not head?
		add_action('admin_print_styles', array($this, 'add_css'));
	}
	
	// subclass will override this method to hook its page into the wp admin menu
	public function admin_menu() {}
	// add js to the page including the wp dashboard script, which provides postbox toggling
	function add_js() {
		wp_register_script( 'abt_js', plugins_url('', __file__) . '/../assets/abt.dev.js',
			array('jquery'), '0.1', 'true' );
		wp_enqueue_script( 'abt_js' );
		wp_enqueue_script('dashboard');
	}
	// add css to the page
	function add_css() {
		wp_register_style( 'abt_css', plugins_url('', __file__) . '/../assets/abt.dev.css', '0.1' );
		wp_enqueue_style( 'abt_css' );
	}
	// route a request to the appropriate method
	// some delete requests are sent as a GET via a link with action=delete param
	// this is how WP does it on other admin pages but it's not ideal
	function route($request) {
		$method = $request->method;
		$this->request = $request;
		if ( $method !== 'get' && method_exists($this, $method) ) {
			$this->verify_nonce($request);
			$this->$method($request);
		}
	}
	// child classes will override these methods to process CRUD actions
	function get() {}
	function create() {}
	function update() {}
	function delete() {}
	
	// return the url to the page in the wp-admin
	function admin_url() {
		return '?page=' . $this->get_page_name();
	}
	
	static function get_page_name() {
		return self::page_name;
	}
	
	function generate_nonce($action = null) {
		return wp_create_nonce($action || $this->get_page_name());
	}
	
	function set_flash($flash) {
		$this->flash = $flash;
	}
	
	function get_flash() {
		return $this->flash->get();
	}
	
	function flash($key, $value) {
		$this->flash->set($key, $value);
	}
	
	// verify a nonce on a GET or POST request
	function verify_nonce($request, $action = null) {
		// @todo why having to include pluggable here??
		require_once(dirname(__file__) . '/../../../../../wp-includes/pluggable.php');
		if (!wp_verify_nonce($request->_nonce, $action || $request->page || $this->get_page_name())) {
			$this->flash('notice', 'Yuh oh! Security check failed.');
			$query = $request->id ? '&id=' . $request->id : '';
			abt_redirect_to($_SERVER['HTTP_REFERER']);
		} else {
			$_SESSION['errors']['notice'] = null;
		}
	}
		
}

