<?php

/*
Plugin Name: CF7 Export CSV
Plugin URI: http://imdr.github.io/cf7-export-csv
Description: CF7 Export CSV is a plugin for storing data of Contact Form 7 forms into the database and exporting data as CSV files
Author: Dinesh Rawat
Author URI: http://imdr.github.io/
Version: 1.6
License: GPLv2 or later
*/

include_once( 'inc/cf7-export-csv-page.php' );
include_once( 'inc/cf7-save-data.php' );


/*-----------database table creation-----------*/
register_activation_hook( __FILE__, 'CF7EC_create_db' );

function CF7EC_create_db() {
	global $wpdb;
	$charset    = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'cf7_export_csv_db';
	if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {
		$sql = "CREATE TABLE $table_name ( 
			`id` INT NOT NULL AUTO_INCREMENT , 
			`submission_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
			`form_id` INT , 
			`form_values` TEXT , 
			UNIQUE (`id`))$charset;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

}


/*-----------database table deletion-----------*/
register_uninstall_hook( __FILE__, 'CF7EC_delete_db' );

function CF7EC_delete_db() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'cf7_export_csv_db';
	$sql        = "DROP TABLE IF EXISTS $table_name";
	$wpdb->query( $sql );
	delete_option( 'cf7db' );
}

add_action( 'admin_notices', 'CF7EC_activation_notice' );

function CF7EC_activation_notice() {
	if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
		?>
        <div class="notice notice-warning is-dismissible">
            <p>Contact Form 7 is not <strong>activated.</strong></p>
        </div>
		<?php
	}
}
