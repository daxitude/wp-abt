<?php
/*
 * Admin Experiment page - shows a single experiment
 */
class ABT_View_Experiment extends ABT_View_Base {
	
	// page name eg /?page=$page_name. also added to hashing for the nonce
	protected static $page_name = 'abt_experiment';
	protected $help_menu = true;
	
	// register the page but don't show it on the admin bar
	// this is kinda bootsy cuz the 'current' state doesn't render on the 
	// admin bar link when you're on this page. could have used ?page=abt&action=show..
	public function admin_menu () {
		$this->wp_page_name = add_submenu_page(
			null,
			'A/B Test Experiment',
			'A/B Tests',
			'publish_pages',
			self::get_page_name(),
			array($this, 'get')
		);
	}
	
	static function get_page_name() {
		return self::$page_name;
	}
	
	// render the html for the view. 
	function get() {
		$req = (object) $this->request;
		// find the requested experiment and variations
		$this->exp = $req->id ? ABT_Model_Experiment::by_id($req->id) : new ABT_Model_Experiment();
		$vars = $req->id ? ABT_Model_Variation::by_experiment_id($req->id) : false;
		$action = $req->id ? 'update' : 'create';

		echo ABT_Mustache::render('experiment',
			array(
				// merge any GET params with the experiment model
				'exp' => abt_merge($_GET, $this->exp),
				'vars' => $vars,
				'action' => $action,
				'_nonce' => $this->generate_nonce(),
				'flash' => $this->flash->get(),
				'isNew' => $req->id ? false : true,
				'pages' => array(&$this, 'list_pages'),
				'status_html' => create_function('$txt', 'return ABT_Experiment_Page::status_html($txt);')
			)
		);
	}
	// renders a dropdown <select> of wp pages for choosing a goal page
	function list_pages($id = null) {
		$post_type = 'page';//$this->$goal_post_type;
		$pt_object = get_post_type_object($post_type);
		$option_label = $pt_object->labels->singular_name;
		
		$list =  $this->dropdown_posts(
			array(
				'post_type' => $post_type,
				'selected' => $id,
				'echo' => false,
				'name' => 'experiment[goal_page_id]',
				'show_option_none' => 'Select ' . $option_label
			)
		);
		return $list;
	}
	
	// route GET and POST actions
	function create($request) {
		// only allow create on POST
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
		$exp = new ABT_Model_Experiment($request->experiment);
		if ($result = $exp->create()) {
			abt_redirect_to('?page=abt_experiment&id=' . $exp->id);
		}
		else {
			$this->flash('errors', $exp->errors());
			abt_redirect_to('?page=abt_experiment&'. http_build_query($request->experiment));
		}
	}

	function update($request) {
		$exp = ABT_Model_Experiment::find_by_id($request->experiment['id']);
		if ($result = $exp->update($request->experiment)) {
			abt_redirect_to('?page=abt_experiment&id=' . $exp->id);
		} else {
			$this->flash('errors', $exp->errors());
			abt_redirect_to('?page=abt_experiment&'. http_build_query($request->experiment));
		}
	}

	function delete($request) {
		ABT_Model_Experiment::delete($request->experiment['id']);
		abt_redirect_to('?page=abt_list');
	}
	
}

