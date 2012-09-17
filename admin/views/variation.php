<?php

class ABT_Variation_Page extends ABT_Admin_Page {
	
	function admin_menu () {
		add_submenu_page(
			null,
			'A/B Test Variation',
			'A/B Tests',
			'publish_pages',
			'abt_variation',
			array($this, 'content')
		);
	}
	
	function content() {
		$this->var = $this->id ? Variation::by_id($this->id) : false;
		$this->var->experiment_id = $this->experiment_id;
		$action = $this->id ? 'update' : 'create';

		echo ABT_Mustache::render('variation',
			array(
				'var' => abt_merge($_GET, $this->var),
				'action' => $action,
				'pages' => array($this, 'list_pages'),
				'errors' => $_SESSION['errors'] ? $_SESSION['errors'] : false,
				'isNew' => $this->id ? false : true
			)
		);
	}
	
	function list_pages($id = null) {
		$post_ids = Variation::get_post_ids();
		$list =  wp_dropdown_pages(
			array(
				'selected' => $id,
				'echo' => false,
				'name' => 'variation[post_id]',
				'exclude' => implode(',', $post_ids),
				'show_option_none' => 'Select Page'
			)
		);
		return $list;
	}
	
	function route($opts) {
		switch ($opts['action']) {
			case 'create':
				if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
				$var = new Variation($opts['variation']);
				$exp = new Experiment(array('id' => $var->experiment_id));
				if ($exp->num_variations() == 0) $var->base = true;
				
				if ($result = $var->create()) {
					$_SESSION['errors'] = false;
					abt_redirect_to('?page=abt_experiment&id=' . $var->experiment_id);
				}
				else {
					$_SESSION['errors'] = $var->errors();
					abt_redirect_to('?page=abt_variation&'. http_build_query($opts['variation']));
				}
				break;
			case 'update':
				$var = Variation::find_by_id($opts['variation']['id']);
				if ($result = $var->update($opts['variation'])) {
					$_SESSION['errors'] = false;
					abt_redirect_to('?page=abt_experiment&id=' . $var->experiment_id);
				} else {
					$_SESSION['errors'] = $var->errors();
					abt_redirect_to('?page=abt_variation&'. http_build_query($opts['variation']));
				}			
				break;
			case 'delete':
				$var = Variation::find_by_id($opts['variation']['id']);
				
				if (
					$var->base &&
					Variation::count( array('experiment_id' => $var->experiment_id) ) > 1
				) {
					$new_base = Variation::first( array('experiment_id' => $var->experiment_id) );
					$new_base->update( array('base' => 1) );
				}
			
				Variation::delete($var->id);
				abt_redirect_to('?page=abt_experiment&id=' . $opts['variation']['experiment_id']);
				break;
		}
		
	}
}

if ( abt_admin() == 'variation' ) new ABT_Variation_Page($_REQUEST);
