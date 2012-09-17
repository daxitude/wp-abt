<?php

class ABT_Experiments_List_Page extends ABT_Admin_Page {
	
	function admin_menu () {
		add_menu_page(
			'A/B Tests',
			'A/B Tests',
			'publish_pages',
			'abt_list',
			array($this, 'index'),
			'',
			'99'
		);
		add_submenu_page(
			'abt_list',
			'A/B Test Tools',
			'Tools',
			'publish_pages',
			'abt_tools',
			array($this, 'tools')
		);
	}
	
	function index() {		
		$exps = isset($this->GET_VARS['experiment']) ?
			Experiment::where($this->GET_VARS['experiment']) :
			Experiment::all();
		
		echo ABT_Mustache::render('list',
			array(
				'exps' => $exps,
				'count_total' => Experiment::count(),
				'count_running' => Experiment::count(array('status' => 1))
			)
		);
	}
	
	function tools() {
		echo ABT_Mustache::render('tools', null);
	}
}

new ABT_Experiments_List_Page($_REQUEST);