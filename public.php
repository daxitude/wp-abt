<?php

/*
 * the main method that checks for variations and conversions on each page request
 * reads the wp_query instance
 * @todo a lot of extra db hits. can do better
*/
add_action( 'pre_get_posts', 'abt_check_page' );
function abt_check_page($query) {	
	// start session if not already running
	if ( !isset($_SESSION) ) session_start();
	// return if not the main query or if not a Page
	if ( !$query->is_main_query() || !is_page() )
		return false;

	// check to see if this is an ip we want to filter out of visits/conversions
	if ( abt_get_option('filter_ip_on') && abt_is_filtered_ip($_SERVER['REMOTE_ADDR']) )
		return false;
				
	// get the ID of the requested page. 2nd option is set on a call to abt_serve_page()
	$requested_id = isset($query->queried_object_id) ? $query->queried_object_id : $query->query_vars['page_id'];

	// if the page is a variation of a running experiment
	if ( $var = abt_is_variation_base($requested_id) ) {
		// check for cookie to see if this visitor has already viewed a
		// variation within this experiment		
		// cookie has serialized array of var_id, post_id, converted
		if ( $cookie = abt_get_cookie($var->experiment_id) ) {
			// if the current requested page is not equal to cookied variation,
			// then redirect to the other variation
			if ( $cookie->post_id != $requested_id ) abt_serve_page($query, $cookie->post_id);			
			
		} else {
			// new visitor, check experiment's variations for lowest visit count and return it's post_id
			// this will be the visitor's variation
			$var = ABT_Model_Variation::get_least_viewed($var->experiment_id);

			// serve that page
			if ( $var->post_id != $requested_id ) abt_serve_page($query, $var->post_id);
			
			// store a visit
			$var->visited();
			
			// store cookie
			abt_set_cookie($var->experiment_id, 
				array( 'var_id' => $var->id, 'post_id' => $var->post_id, 'converted' => false )
			);
		}
		
	}
	
	// if the page is a conversion goal
	// @todo is it ok for a page to be a variation and a conversion?
	// currently, more than one experiment can have same goal
	if ( $exps = abt_is_goal($requested_id) ) {
		// cookie has serialized array of var_id, post_id, converted
		// if there's no cookie for this experiment yet, the visitor is here
		// without having been to a variation page yet!
		foreach ($exps as $exp) {
			if ( $cookie = abt_get_cookie($exp->id) ) {			
				// already converted? then return
				if ( $cookie->converted == true ) return;

				// mark the visit as conversion
				$var = ABT_Model_Variation::find_by_id($cookie->var_id);
				$var->converted();

				// update the cookie to store the conversion
				abt_set_cookie($exp->id,
					array( 'var_id' => $cookie->var_id, 'post_id' => $cookie->post_id, 'converted' => true )
				);
			}
		}
		
	}

}

// check an ip to see if it's in the stored list of ips to filter from
// visit/conversion hits
function abt_is_filtered_ip($ip) {
	$filtered = abt_get_option('filter_ip');
	$filtered = explode("\n", $filtered);
	$filtered = array_map(create_function('$item', 'return trim($item);'), $filtered);
	return in_array($ip, $filtered) ? true : false;
}

// check a Page by post_id to see if it's an experiment variation
// AND if is the base variation, and if the experiment is running
// @return the Variation instance
function abt_is_variation_base($id) {	
	$var = ABT_Model_Variation::by_post_id($id);
	if (!$var) return false;
	$exp = ABT_Model_Experiment::find_by_id($var->experiment_id);
	return ( count($var) > 0 && $var->base && $exp->is_running() ) ? $var : false;
}

// check a Page by post_id to see if it's an experiment goal page
// AND if the experiment is running
// @return all Experiment instances that match and are running
function abt_is_goal($id) {	
	$exps = ABT_Model_Experiment::by_goal_page_id($id);	
	if (!$exps) return false;
	$exps = array_filter($exps, create_function('$exp', 'return $exp->is_running();'));
	return $exps;
}

// override the wp_query instance so we can serve up different Page content
// from the same permalink
function abt_serve_page($query, $id) {
	$query->init();
    $query->query( 'page_id=' . $id );
	$query->set( 'queried_object_id', $id);
}

// get and de-serialize a cookie by experiment_id
function abt_get_cookie($page_id) {
	return
		isset($_COOKIE, $_COOKIE['abt_' . $page_id]) ?
		(object) json_decode( stripslashes($_COOKIE['abt_' . $page_id]) ) :
		false;
}
// serialize and set a cookie for 6 months by experiment_id
function abt_set_cookie($id, $value) {
	return setcookie('abt_' . $id, json_encode($value), time() + 60*60*24*30*6, '/');
}


