<?php

class ABT_Settings_Page extends ABT_Admin_Page {
	
	public $filter_ip;
	public $filter_ip_on;
	
	function admin_menu () {
		add_submenu_page(
			'abt_list',
			'A/B Test Settings',
			'Settings',
			'publish_pages',
			'abt_settings',
			array($this, 'content')
		);
	}
	// get_option, add_option, update_option
	function content() {
		echo ABT_Mustache::render(
			'settings',
			array(
				'filter_ip' => abt_get_option('filter_ip'),
				'filter_ip_on' => abt_get_option('filter_ip_on')
				)
		);
	}
	
	function route($opts) {
		switch($opts['action']) {
			case 'update':
				if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
				foreach ($opts['settings'] as $key => $value) {
					
					if ( property_exists(__class__, $key) )
						abt_update_option($key, $value);
				}
				abt_redirect_to('?page=abt_settings');
				break;
		}
	}
}

new ABT_Settings_Page($_REQUEST);