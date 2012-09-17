<?php

function abt_admin() {
	$page = isset($_GET['page']) ? explode('_', $_GET['page']) : false;
	return (is_admin() && $page && $page[0] == 'abt') ? $page[1] : false;
}

function abt_format_date($date, $format = 'D, M j') {
	return strtotime($date) ? date($format, strtotime($date)) : false;
}

function abt_redirect_to($url) {

	if( !headers_sent() ) {
		header('location: ' . urldecode($url));
		exit;
	}

	exit('<meta http-equiv="refresh" content="0; url=' . urldecode($url) . '"/>');
	return;
}

function abt_merge($a, $b) {
	foreach ((object) $a as $key => $value) {
		$b->$key = $value;
	}
	return (object) $b;
}

function abt_get_option($option) {
	$option = get_option('abt_' . $option);
	return $option;
}

function abt_update_option($option, $value) {
	return update_option('abt_' . $option, $value);
}

function abt_add_option($option, $value) {
	return add_option('abt_' . $option, $value);
}
