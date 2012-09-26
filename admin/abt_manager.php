<?php

class ABT_Admin_Mgr {
	
	private $pages = array();
	private $request;
	private $req_page;
	private $session;
	
	protected $plugin_url = 'abt_list';
	
	function __construct() {
		if ( !isset($_SESSION) ) session_start();
		$this->flash = new ABT_Util_Flash();
		$this->request = new ABT_Util_Request();
		$this->req_page = $this->parse_request();		
	}
	
	function run() {
		$this->pages_init();
		add_action('admin_menu', array($this, 'admin_menu'));
		add_filter('add_menu_classes', array($this, 'filter_admin_menu'));
		if (! $this->req_page ) return false;

		$page_class = $this->get_page();
		// have to add this action to admin_init to make sure the page's wp_page_name attribute
		// has been set on returned call to add_*_page
		add_action('admin_init', array($page_class, 'init'));
		$page_class->set_flash($this->flash);
		$page_class->route($this->request);
		$this->flash->save();
	}
	
	function filter_admin_menu($menu) {
		$abt = array_filter($menu, create_function('$item', 'return $item[2] == "abt_list";'));
		
		foreach( $menu as $key => $options ) {
			if ($options[0] == 'A/B Tests') {
				// menu-top toplevel_page_abt_list menu-top-first menu-top-last
				$menu[$key][4] .= ' abt-current-submenu';
				break;
			}
		}
				
		return $menu;
	}
	
	function admin_menu() {
		foreach ($this->pages as $page => $page_class) {
			$page_class->admin_menu();
		}
	}
	
	function register($class_name) {
		// register the class
		$page_name = call_user_func(array($class_name, 'get_page_name'), null);
		$this->add_page($page_name, $class_name); 
	}
	
	function add_page($page_name, $class_name) {
		$this->pages[$page_name] = $class_name;
	}
	
	function get_page() {
		return $this->pages[$this->req_page];
	}
	
	function parse_request() {
		$page = isset($_GET['page']) ? explode('_', $_GET['page']) : false;
		return (is_admin() && $page && $page[0] == 'abt') ? $_GET['page'] : false;
	}
	
	function pages_init() {
		foreach ($this->pages as $page => $class) {
			$this->pages[$page] = new $class();
		}
	}
	
	public static function autoloader( $class ) {
        if ( strpos($class, 'ABT') !== 0 ) {
            return;
        }
        $file = dirname(__FILE__) . '/' . str_replace('_', DIRECTORY_SEPARATOR, substr($class, 4)) . '.php';

        if ( file_exists($file) ) {
            require_once $file;
        }
    }
	
}