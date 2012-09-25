<?php
/*
 * a few helpers, some shared by admin and public
 */
// parses the requested page, eg abt_list, so the plugin can render the appropriate page

// redirect to a specified url. if headers already sent, will do a meta refresh
// using this instead of wp_redirect so we can include the exit within the method
function abt_redirect_to( $url, $status = 302) {
	if( !headers_sent() ) {
		header('location: ' . wp_sanitize_redirect($url), true, $status);
		exit;
	}

	exit('<meta http-equiv="refresh" content="0; url=' . urldecode($url) . '"/>');
	return;
}
// merge first object into second object
// this is used to merge $_GET params into a model instance
function abt_merge($a, $b) {
	foreach ((object) $a as $key => $value) {
		$b->$key = $value;
	}
	return (object) $b;
}
// get an option from wp_options
function abt_get_option($option) {
	$option = get_option('abt_' . $option);
	return $option;
}
// update an option from wp_options
function abt_update_option($option, $value) {
	return update_option('abt_' . $option, $value);
}
// add an option from wp_options
function abt_add_option($option, $value) {
	return add_option('abt_' . $option, $value);
}
