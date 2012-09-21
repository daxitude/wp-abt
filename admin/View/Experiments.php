<?php
/*
 * Admin main page for the plugin  - lists the Experiments
 * Admin tools page provides tools to help plan experiments
 */
class ABT_View_Experiments extends ABT_View_Base {
	
	// page name eg /?page=$page_name. also added to hashing for the nonce
	protected static $page_name = 'abt_list';
	
	// register the Main page that lists experiments
	// also registers the Tools page
	public function admin_menu () {
		add_menu_page(
			'A/B Tests',
			'A/B Tests',
			'publish_pages',
			$this->get_page_name(),
			array($this, 'get'),
			'',
			'99'
		);
	}
	// render the html for the list view. if there are $_GET params
	// then find by a where. otherwise find all
	function get() {
		$exps = $this->request->experiment ?
			ABT_Model_Experiment::where($this->request->experiment) :
			ABT_Model_Experiment::all();
		
		echo ABT_Mustache::render('list',
			array(
				'_nonce' => $this->generate_nonce(),
				'flash' => $this->flash->get(),
				'exps' => $exps,
				'count_total' => ABT_Model_Experiment::count(),
				'count_running' => ABT_Model_Experiment::count(array('status' => 1))
			)
		);
	}
	
	static function get_page_name() {
		return self::$page_name;
	}

}

