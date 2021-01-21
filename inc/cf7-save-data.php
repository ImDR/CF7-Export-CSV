<?php

add_action( 'wpcf7_before_send_mail', 'CF7EC_save_form' );

function CF7EC_save_form( $wpcf7 ) {
	global $wpdb;

	$submission = WPCF7_Submission::get_instance();

	if ( ! empty( $submission ) ) {
		$wpcf7->title();
		$form_data = $submission->get_posted_data();
		if ( $wpcf7->id !== 0 ) {
			$cf7Id = array();

			if ( get_option( 'cf7db' ) != '' ) {
				$cf7Id = json_decode( get_option( 'cf7db' ), true );
			}

			$form_id = $wpcf7->id;
			if ( in_array( $form_id, $cf7Id ) ) {
				foreach ( $form_data as $key => $value ) {
					if ( preg_match( '/^_/', $key ) ) {
						unset( $form_data[ $key ] );
					}
				}
				$form_values = json_encode( $form_data );
				$table_name  = $wpdb->prefix . "cf7_export_csv_db";
				$wpdb->insert( $table_name, array( 'form_id' => $form_id, 'form_values' => $form_values ) );
			}
		}
	}

}
