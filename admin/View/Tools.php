<?php
/*
 * Admin main page for the plugin  - lists the Experiments
 * Admin tools page provides tools to help plan experiments
 */
class ABT_View_Tools extends ABT_View_Base {
	
	// page name eg /?page=$page_name. also added to hashing for the nonce
	protected static $page_name = 'abt_tools';
	
	// register the Main page that lists experiments
	// also registers the Tools page
	public function admin_menu () {
		add_submenu_page(
			'abt_list',
			'A/B Test Tools',
			'Tools',
			'publish_pages',
			self::get_page_name(),
			array($this, 'get')
		);
	}
	
	static function get_page_name() {
		return self::$page_name;
	}
	
	// render the html for the tools page
	function get() {
		echo ABT_Mustache::render('tools', null);
	}
	
	function route($opts) {
		return false;
	}
}

