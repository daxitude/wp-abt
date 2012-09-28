<?php
/*
Plugin Name: A/B Tests for WP
Description: Increase conversions by running A/B content experiments right from your WordPress admin.
Version: 0.4
Author: daxitude
Author URI: http://github.com/daxitude/
Plugin URI: http://github.com/daxitude/wp-abt
*/

// define path constants
define( 'ABT_BASE_DIR', dirname( __FILE__ ) . '/' );
define( 'ABT_ADMIN_DIR', dirname( __FILE__ ) . '/admin/' );

require_once ABT_BASE_DIR . 'db.php';
require_once ABT_ADMIN_DIR . 'abt_manager.php';
require_once ABT_ADMIN_DIR . 'helpers.php';

// init the db class. checks for changes in schema by plugin version.
// uses wp's dbDelta for simple update/add migrations if necessary
ABT_DB::init();

// register an autoloader. automatically requires files matching a class
// when the class is first used. files must start from the plugin's admin base path
// underscores in class names correspond to folder changes.
// eg ABT_Model_Base = abtforwp/admin/Model/Base (case sensitive)
spl_autoload_register(array('ABT_Admin_Mgr', 'autoloader'));

if (is_admin())
	require_once ABT_BASE_DIR . 'admin.php';
	
if (!is_admin())
	require_once ABT_BASE_DIR . 'public.php';


