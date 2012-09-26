<?php
/*
 * Admin settings page
 */
class ABT_View_Settings extends ABT_View_Base {
	
	// plugin options that can be set by the settings page
	public $filter_ip;
	public $filter_ip_on;
	
	protected $help_menu = true;
	
	// page name eg /?page=$page_name. also added to hashing for the nonce
	protected static $page_name = 'abt_settings';
	
	// register the settings page
	public function admin_menu () {
		$this->wp_page_name = add_submenu_page(
			'abt_list',
			'A/B Test Settings',
			'Settings',
			'publish_pages',
			$this->get_page_name(),
			array($this, 'get')
		);		
	}
	
	static function get_page_name() {
		return self::$page_name;
	}
	
	// render the html for the view
	function get() {
		echo ABT_Mustache::render(
			'settings',
			array(
				'_nonce' => $this->generate_nonce(),
				'flash' => $this->flash->get(),
				'filter_ip' => abt_get_option('filter_ip'),
				'filter_ip_on' => abt_get_option('filter_ip_on')
				)
		);
	}
	// route GET and POST actions
	function update($request) {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
		foreach ($request->settings as $key => $value) {					
			if ( property_exists(__class__, $key) )
				abt_update_option($key, $value);
		}
		abt_redirect_to($this->admin_url());
	}
}

