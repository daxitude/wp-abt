<?php

class ABT_Experiment_Page extends ABT_Admin_Page {
				
	function admin_menu () {
		add_submenu_page(
			null,
			'A/B Test Experiment',
			'A/B Tests',
			'publish_pages',
			'abt_experiment',
			array($this, 'content')
		);
	}
	
	function content() {
		$this->exp = $this->id ? Experiment::by_id($this->id) : false;
		$vars = $this->id ? Variation::by_experiment_id($this->id) : false;
		$action = $this->id ? 'update' : 'create';

		echo ABT_Mustache::render('experiment',
			array(
				'exp' => abt_merge($_GET, $this->exp),
				'vars' => $vars,
				'action' => $action,
				'errors' => isset($_SESSION['errors']) ? ($_SESSION['errors']) : false,
				'isNew' => $this->id ? false : true,
				'pages' => array(__class__, 'list_pages'),
				'status_html' => create_function('$txt', 'return ABT_Experiment_Page::status_html($txt);')
			)
		);
	}
	
	static function list_pages($id = null) {
		$list =  wp_dropdown_pages(
			array(
				'selected' => $id,
				'echo' => false,
				'name' => 'experiment[goal_page_id]',
				'show_option_none' => 'Select Page'
			)
		);
		return $list;
	}
	
	function route($opts) {
		switch ($opts['action']) {
			case 'create':
				if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
				$exp = new Experiment($opts['experiment']);
				if ($result = $exp->create()) {
					$_SESSION['errors'] = false;
					abt_redirect_to('?page=abt_experiment&id=' . $exp->id);
				}
				else {
					$_SESSION['errors'] = $exp->errors();
					abt_redirect_to('?page=abt_experiment&'. http_build_query($opts['experiment']));
				}
				break;
			case 'update':
				$exp = Experiment::find_by_id($opts['experiment']['id']);
				if ($result = $exp->update($opts['experiment'])) {
					$_SESSION['errors'] = false;
					abt_redirect_to('?page=abt_experiment&id=' . $exp->id);
				} else {
					$_SESSION['errors'] = $exp->errors();
					abt_redirect_to('?page=abt_experiment&'. http_build_query($opts['experiment']));
				}
				break;
			case 'delete':
				Experiment::delete($opts['experiment']['id']);
				abt_redirect_to('?page=abt_list');
				break;
		}
	}
	
}

if ( abt_admin() == 'experiment' ) new ABT_Experiment_Page($_REQUEST);
