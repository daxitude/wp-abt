<?php
/*
 * Admin page for a Variation create/edit view
 */
class ABT_View_Variation extends ABT_View_Base {
	
	// page name eg /?page=$page_name. also added to hashing for the nonce
	protected static $page_name = 'abt_variation';
	
	// register the page but don't show it on the admin bar
	// this is kinda bootsy cuz the 'current' state doesn't render on the 
	// admin bar link when you're on this page. could have used ?page=abt&action=show..
	public function admin_menu () {
		add_submenu_page(
			null,
			'A/B Test Variation',
			'A/B Tests',
			'publish_pages',
			self::get_page_name(),
			array($this, 'get')
		);
	}
	
	static function get_page_name() {
		return self::$page_name;
	}
	
	// render html for the view
	function get() {
		$req = (object) $this->request;
		$this->var = $req->id ? ABT_Model_Variation::by_id($req->id) : false;
		$this->var->experiment_id = $req->experiment_id;
		$action = $req->id ? 'update' : 'create';

		echo ABT_Mustache::render('variation',
			array(
				'var' => abt_merge($_GET, $this->var),
				'action' => $action,
				'pages' => array($this, 'list_pages'),
				'_nonce' => $this->generate_nonce(),
				'flash' => $this->flash->get(),
				'isNew' => $req->id ? false : true
			)
		);
	}
	// render a <select> of available pages. 
	// @param $id the current id to set as selected=selected, but darn thing ain't workin
	// excludes post_ids already having a variation
	function list_pages($id = null) {
		$post_ids = ABT_Model_Variation::get_post_ids();
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
	// route GET and POST actions
	function create($request) {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
		$var = new ABT_Model_Variation($request->variation);
		$exp = new ABT_Model_Experiment(array('id' => $var->experiment_id));
		if ($exp->num_variations() == 0) $var->base = true;
		
		if ($result = $var->create()) {
			abt_redirect_to('?page=abt_experiment&id=' . $var->experiment_id);
		}
		else {
			$this->flash('errors', $var->errors());
			abt_redirect_to('?page=abt_variation&'. http_build_query($request->variation));
		}
	}
	
	function update($request) {
		$var = ABT_Model_Variation::find_by_id($request->variation['id']);
		if ($result = $var->update($request->variation)) {
			abt_redirect_to('?page=abt_experiment&id=' . $var->experiment_id);
		} else {
			$this->flash('errors', $var->errors());
			abt_redirect_to('?page=abt_variation&'. http_build_query($request->variation));
		}
	}
	
	function delete($request) {
		$var = ABT_Model_Variation::find_by_id($request->variation['id']);
		
		if (
			$var->base &&
			ABT_Model_Variation::count( array('experiment_id' => $var->experiment_id) ) > 1
		) {
			$new_base = ABT_Model_Variation::first( array('experiment_id' => $var->experiment_id) );
			$new_base->update( array('base' => 1) );
		}
	
		ABT_Model_Variation::delete($var->id);
		abt_redirect_to('?page=abt_experiment&id=' . $request->variation['experiment_id']);
	}

}

