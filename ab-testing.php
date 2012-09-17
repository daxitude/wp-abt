<?php
/*
Plugin Name: AB Testing
Description: A/B test your WordPress Pages
Version: 0.1
Author: daxitude
Author URI: 
Plugin URI: 
Text Domain: ab-testing
Domain Path: /lang
*/


$abt_base = dirname( __FILE__ ) . '/';

require_once $abt_base . 'db.php';

if (is_admin())
	require_once $abt_base . 'admin.php';
	
if (!is_admin())
	require_once $abt_base . 'public.php';


