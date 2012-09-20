<?php
/*
 * abtstract class to interact with the WP database
 * stored in wp_options
 *		abt_version			number reflecting current plugin version
 *		abt_filter_ip_on	bool, whether ip filtering is on/off
 * 		abt_filter_ip		serialized array of ips to filter
 */
abstract class ABT_DB {
	
	// current plugin version to check against for db migrations 
	public static $version = 0.20;
	
	public static function init() {
		// do an "install" if the plugin version stored in wp_options doesn't match
		$stored_ver = get_option('abt_version');
		if ( $stored_ver != self::$version ) {
			self::install();
		}

//		add_action( 'deleted_post', array( __CLASS__, 'post_deleted' ) );
		
		// plugin options for wp_options table
		// @todo could serialize these together into one row
		if (!get_option('abt_filter_ip')) add_option('abt_filter_ip', '');
		if (!get_option('abt_filter_ip_on')) add_option('abt_filter_ip_on', 0);
	}
	
	// define two tables to add
	public static function install() {
		self::abt_install_table( 'abt_experiments', "
			id bigint(20) unsigned NOT NULL auto_increment,
			experiment_name varchar(255) NOT NULL default '',
			start_date datetime NULL default NULL,
			end_date datetime NULL default NULL,
			status tinyint(11) NOT NULL default '0',			
			confidence decimal(2,2) NOT NULL default '0.8',			
			effect tinyint(11) NOT NULL default '10',			
			goal_page_id bigint(20) unsigned NOT NULL default '0',
			goal_name varchar(255) NOT NULL default '',
			PRIMARY KEY  (id)
		" );
		
		self::abt_install_table( 'abt_variations', "
			id bigint(20) unsigned NOT NULL auto_increment,
			experiment_id bigint(20) unsigned NOT NULL default '0',
			post_id bigint(20) unsigned NOT NULL default '0',
			variation_name varchar(255) NOT NULL default '',
			visits int(11) NOT NULL default '0',
			conversions int(11) NOT NULL default '0',
			base BOOLEAN NOT NULL DEFAULT '0',
			PRIMARY KEY  (id),
			KEY experiment_id (experiment_id),
			KEY post_id (post_id)
		" );
		
		// if we're "installing" then we need to update the stored version
		update_option( 'abt_version', self::$version);
	}
	
	// delete tables and options on uninstall. called in uninstall.php
	public static function uninstall() {
		self::abt_uninstall_table( 'abt_experiments' );
		self::abt_uninstall_table( 'abt_variations' );

		delete_option( 'abt_version' );
		delete_option( 'abt_filter_ip' );
		delete_option( 'abt_filter_ip_on' );
	}

/*
	static function post_deleted( $id ) {
		
	}
*/
	// "install" a table. dbDelta checks the schema against current install and
	// can make add/modifies to the table config
	private static function abt_install_table($name, $columns) {
		global $wpdb;
		
		$wpdb->tables[] = $name;
		$table_name = $wpdb->prefix . $name;
		
		// dbdelta: http://codex.wordpress.org/Creating_Tables_with_Plugins
		// NOTE: dbdelta won't remove columns. only add/modify
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( "CREATE TABLE $table_name ( $columns );" );
	}
	
	// uninstall a table
	private static function abt_uninstall_table($name) {
		global $wpdb;		
		$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . $name );
	}

}






