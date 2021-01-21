<?php

add_action( 'admin_menu', 'CF7EC_create_menu' );


function CF7EC_create_menu() {
	add_submenu_page( 'wpcf7', 'Export CSV', 'Export CSV', 'administrator', 'cf7_export_csv', 'CF7EC_admin_page' );
}

function CF7EC_admin_page() {
	global $wpdb;
	?>
    <style>
        table td {
            padding: 8px;
        }

        table tr:nth-child(even) {
            background: #e1e1e1
        }

        table tr:nth-child(odd) {
            background: #ffffff
        }


    </style>
    <script>
			function deleteConfirm() {
				return confirm('Do you want to delete all records?');
			}
    </script>
    <div class="wrap">
		<?php
		if ( isset( $_POST['settingFormId'] ) && isset( $_POST['action'] ) ) {
			$formId = intval( $_POST['settingFormId'] );
			$action = sanitize_text_field( $_POST['action'] );
			if ( $formId > 0 && $action != '' ) {
				$cf7Id = array();

				if ( get_option( 'cf7db' ) != '' ) {
					$cf7Id = json_decode( get_option( 'cf7db' ), true );
				}

				if ( $action == 'on' ) {
					array_push( $cf7Id, $formId );
				} else {
					$cf7Id = array_flip( $cf7Id );
					unset( $cf7Id[ $formId ] );
					$cf7Id = array_flip( $cf7Id );
				}
				$success = update_option( 'cf7db', json_encode( array_unique( $cf7Id ) ) );
				if ( $success ) {
					if ( $action == 'on' ) {
						echo '<div class="notice  notice-success is-dismissible"><p>Form Activated Successfully.</p></div>';
					} else {
						echo '<div class="notice  notice-success is-dismissible"><p>Form Deactivated Successfully.</p></div>';
					}
				} else {
					echo '<div class="notice  notice-error is-dismissible"><p>Something went wrong.</p></div>';
				}
			}
		}

		if ( isset( $_POST['deleteFormId'] ) ) {
			$deleteFormId = intval( $_POST['deleteFormId'] );
			if ( $deleteFormId > 0 ) {
				$table_name = $wpdb->prefix . 'cf7_export_csv_db';
				$result     = $wpdb->delete( $table_name, array( 'form_id' => $deleteFormId ) );
				if ( $result ) {
					echo '<div class="notice  notice-success is-dismissible"><p>Records successfully deleted from database.</p></div>';
				} else {
					echo '<div class="notice  notice-error is-dismissible"><p>Something went wrong.</p></div>';
				}
			}
		}
		?>
        <h1>Export CSV</h1>
        <hr/>
        <h3>All Forms</h3>

        <table>
            <tr>
                <td width="200"><b>Form Name</b></td>
                <td width="150"><b>Number of Records</b></td>
                <td width="150"><b>Download CSV</b></td>
                <td width="150"><b>Delete Records</b></td>
                <td width="150"><b>Form Settings</b></td>
            </tr>
			<?php
			$cf7Id = array();

			if ( get_option( 'cf7db' ) != '' ) {
				$cf7Id = json_decode( get_option( 'cf7db' ), true );
			}

			$args = array(
				'posts_per_page' => - 1,
				'post_type'      => 'wpcf7_contact_form'
			);

			$allForms = get_posts( $args );
			if ( ! empty( $allForms ) ) {
				foreach ( $allForms as $post ) :
					setup_postdata( $post );
					$postId       = $post->ID;
					$postTitle    = $post->post_title;
					$table_name   = $wpdb->prefix . 'cf7_export_csv_db';
					$records      = $wpdb->get_results( "SELECT * FROM $table_name where `form_id` = '$postId'" );
					$record_count = count( $records );
					?>
                    <tr id="<?php echo esc_attr( $postId ); ?>">
                        <td><?php echo esc_html( $postTitle ); ?></td>
                        <td><?php echo esc_html( $record_count ); ?></td>
                        <td>
							<?php if ( $record_count ) { ?>
                                <form method="post">
                                    <input type="hidden" name="downloadFormId"
                                           value="<?php echo esc_attr( $postId ); ?>"/>
                                    <input type="hidden" name="downloadFormName"
                                           value="<?php echo esc_attr( $postTitle ); ?>"/>
                                    <input type="submit" class="button button-primary" value="Download"/>
                                </form>
							<?php } ?>
                        </td>
                        <td>
							<?php if ( $record_count ) { ?>
                                <form method="post" onsubmit="return deleteConfirm();">
                                    <input type="hidden" name="deleteFormId"
                                           value="<?php echo esc_attr( $postId ); ?>"/>
                                    <input type="submit" class="button action" value="Delete"/>
                                </form>
							<?php } ?>
                        </td>
                        <td>
                            <form method="post">
                                <input type='hidden' name='settingFormId' value='<?php echo esc_attr( $postId ); ?>'/>
                                <input type='hidden' name='action'
                                       value='<?php echo ( in_array( $postId, $cf7Id ) ) ? 'off' : 'on'; ?>'/>
                                <input type='submit' <?php echo ( in_array( $postId, $cf7Id ) ) ? "class='button button-primary' value='Activated'" : "class='button action' value='Activate Now'"; ?> />
                            </form>
                        </td>
                    </tr>
				<?php
				endforeach;
				wp_reset_postdata();
			} else {
				?>
                <tr>
                    <td colspan="5">No form found.</td>
                </tr>
				<?php
			}

			?>
        </table>
    </div>
	<?php
}

if ( ( isset( $_POST['downloadFormId'] ) ) && ( isset( $_POST['downloadFormName'] ) ) ) {
	$formId     = intval( $_POST['downloadFormId'] );
	$formName   = sanitize_text_field( $_POST['downloadFormName'] );
	$table_name = $wpdb->prefix . 'cf7_export_csv_db';
	$records    = $wpdb->get_results( "SELECT * FROM $table_name where `form_id` = '$formId'" );
	$dataArr    = array();
	foreach ( $records as $values ) {
		$submissionTime          = $values->submission_time;
		$data                    = json_decode( $values->form_values, true );
		$data['submission-Time'] = $submissionTime;

		$dataArr[] = $data;

	}
	CF7EC_download( $dataArr, $formName );
}

function CF7EC_download( $dataArr, $formName ) {
	$formName = str_replace( " ", "-", $formName );
	$fileName = $formName . "-" . date( 'Y-m-d' ) . ".csv";
	header( 'Content-type: application/csv' );
	header( "Content-Disposition: attachment; filename=" . $fileName );

	$fp     = fopen( 'php://output', 'w' );
	$header = array();
	foreach ( array_keys( $dataArr[0] ) as $value ) {
		$header[] = strtoupper( str_replace( "_", " ", str_replace( "-", " ", $value ) ) );
	}
	fputcsv( $fp, $header );
	foreach ( $dataArr as $row ) {

		fputcsv( $fp, $row );
	}

	fclose( $fp );
	exit();
}
