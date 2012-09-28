<?php
/*
 * Admin main page for the plugin  - lists the Experiments
 * Admin tools page provides tools to help plan experiments
 */
class ABT_View_Experiments extends ABT_View_Base {
	
	// page name eg /?page=$page_name. also added to hashing for the nonce
	protected static $page_name = 'abt_list';
	protected $help_menu = true;
	
	// the experiments to be rendered
	private $exps;
	
	// register the Main page that lists experiments
	// also registers the Tools page
	public function admin_menu () {
		$this->wp_page_name = add_menu_page(
			'A/B Tests',
			'A/B Tests',
			'publish_pages',
			$this->get_page_name(),
			array($this, 'get'),
			'',
			'111'
		);
	}
	// render the html for the list view. if there are $_GET params
	// then find by a where. otherwise find all
	function get() {
		$exps = $this->request->experiment ?
			ABT_Model_Experiment::where($this->request->experiment) :
			ABT_Model_Experiment::all();
			
		$this->exps = $exps;
		
		echo ABT_Mustache::render('experiments',
			array(
				'_nonce' => $this->generate_nonce(),
				'flash' => $this->flash->get(),
				'exps' => $exps,
				'count_total' => ABT_Model_Experiment::count(),
				'count_running' => ABT_Model_Experiment::count(array('status' => 1)),
				'status_txt'=> array($this, 'status_txt')
			)
		);
	}
	
	function status_txt($id, $mustache) {
		$exp = $this->pluck_experiment($mustache->render($id));
		switch($exp->status) {
			case '0':
				$status = ($exp->num_variations() < 2) ?
					'<strong class="label badge warning">&nbsp;!&nbsp;</strong>' :
					'<strong class="label badge info">✓</strong> &nbsp;' . $exp->status_text();
				break;
			case '1':
				$status = '<strong class="label">' . $exp->status_text() . '</strong>';
				break;
			case '2':
				$status = $exp->status_text();
				break;
		}
		return $status;
	}
	
	private function pluck_experiment($id) {
		foreach ($this->exps as $key => $exp) {
			if ($exp->id === $id)
				return $exp;
		}
	}
	
	static function get_page_name() {
		return self::$page_name;
	}

}

