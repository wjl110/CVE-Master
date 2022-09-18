<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Import_Subscribers {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
	}

	public function plugin_menu() {
		add_submenu_page( null, __( 'Import Subscribers', 'email-subscribers' ), __( 'Import Subscribers', 'email-subscribers' ), get_option( 'es_roles_subscriber', true ), 'es_import_subscribers', array( $this, 'import_subscribers_page' ) );
	}

	/**
	 * Import Data HTML
	 */
	public function import_report_callback() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$submit = ig_es_get_post_data( 'submit' );
		if ( $submit ) {
			$import_contacts_nonce = ig_es_get_post_data( 'import_contacts' );
			if ( ! isset( $_POST['import_contacts'] ) || ! wp_verify_nonce( sanitize_key( $import_contacts_nonce ), 'import-contacts' ) ) {
				$message = __( "Sorry, you do not have permission to import contacts.", 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}

			if ( isset( $_FILES["file"] ) ) {

				if ( is_uploaded_file( $_FILES["file"]["tmp_name"] ) ) {

					$ext = substr( $_FILES['file']['name'], strrpos( $_FILES['file']['name'], "." ), ( strlen( $_FILES['file']['name'] ) - strrpos( $_FILES['file']['name'], "." ) ) );

					if ( $ext == ".csv" ) {

						$statuses        = ES_Common::get_statuses_key_name_map();
						$es_email_status = ig_es_get_post_data( 'es_email_status' );

						$status = '';
						if ( in_array( $es_email_status, array_keys( $statuses ) ) ) {
							$status = $es_email_status;
						}

						if ( ! empty( $status ) ) {

							$lists = ES_Common::get_list_id_details_map();

							$list_id = ig_es_get_post_data( 'list_id' );

							if ( ! in_array( $list_id, array_keys( $lists ) ) ) {
								$list_id = '';
							}

							if ( ! empty( $list_id ) ) {

								$handle = fopen( $_FILES["file"]["tmp_name"], 'r' );

								// Get Headers
								$headers = array_map( 'trim', fgetcsv( $handle ) );

								$existing_contacts_email_id_map = ES_DB_Contacts::get_email_id_map();

								$existing_contacts = array();
								if ( count( $existing_contacts_email_id_map ) > 0 ) {
									$existing_contacts = array_keys( $existing_contacts_email_id_map );
								}

								$invalid_emails_count = $imported_subscribers_count = $existing_contacts_count = 0;
								$emails               = array();

								$values            = $place_holders = $contacts_data = array();
								$current_date_time = ig_get_current_date_time();

								$i = 0;
								while ( ( $data = fgetcsv( $handle ) ) !== false ) {

									$data = array_map( 'trim', $data );

									$data = array_combine( $headers, $data );

									$email = isset( $data['Email'] ) ? sanitize_email( trim( $data['Email'] ) ) : '';

									if ( empty( $email ) ) {
										$invalid_emails_count ++;
										continue;
									}

									if ( ! in_array( $email, $existing_contacts ) ) {

										$name       = isset( $data['Name'] ) ? trim( $data['Name'] ) : '';
										$first_name = isset( $data['First Name'] ) ? sanitize_text_field( trim( $data['First Name'] ) ) : '';
										$last_name  = isset( $data['Last Name'] ) ? sanitize_text_field( trim( $data['Last Name'] ) ) : '';

										// If we don't get the first_name & last_name, consider Name field.
										// If name empty, get the name from Email
										if ( empty( $first_name ) && empty( $last_name ) ) {

											if ( empty( $name ) ) {
												$name = ES_Common::get_name_from_email( $email );
											}

											$names      = ES_Common::prepare_first_name_last_name( $name );
											$first_name = sanitize_text_field( $names['first_name'] );
											$last_name  = sanitize_text_field( $names['last_name'] );
										}

										$guid = ES_Common::generate_guid();

										$contacts_data[ $imported_subscribers_count ]['first_name'] = $first_name;
										$contacts_data[ $imported_subscribers_count ]['last_name']  = $last_name;
										$contacts_data[ $imported_subscribers_count ]['email']      = $email;
										$contacts_data[ $imported_subscribers_count ]['source']     = 'import';
										$contacts_data[ $imported_subscribers_count ]['status']     = 'verified';
										$contacts_data[ $imported_subscribers_count ]['hash']       = $guid;
										$contacts_data[ $imported_subscribers_count ]['created_at'] = $current_date_time;

										$existing_contacts[] = $email;
									} else {
										$existing_contacts_count ++;
									}

									$emails[] = $email;
									$imported_subscribers_count ++;
								}

								if ( count( $emails ) > 0 ) {

									ES_DB_Contacts::do_batch_insert( $contacts_data );

									$contact_ids = ES_DB_Contacts::get_contact_ids_by_emails( $emails );
									if ( count( $contact_ids ) > 0 ) {
										ES_DB_Lists_Contacts::delete_contacts_from_list( $list_id, $contact_ids );
										ES_DB_Lists_Contacts::do_import_contacts_into_list( $list_id, $contact_ids, $status, 1, $current_date_time );
									}

									$message = sprintf( __( 'Total %d contacts have been imported successfully!', 'email-subscribers' ), $imported_subscribers_count );
									$status  = 'success';
								} else {

									$message = sprintf( __( 'Total %d contacts have been imported successfully!', 'email-subscribers' ), $imported_subscribers_count );

									if ( $existing_contacts_count > 0 ) {
										$message .= " ";
										$message .= sprintf( __( '%d contacts are already exists.', 'email-subscribers' ), $existing_contacts );
									}

									if ( $invalid_emails_count > 0 ) {
										$message .= " ";
										$message .= sprintf( __( '%d contacts are invalid.', 'email-subscribers' ), $invalid_emails_count );
									}

									$status = 'error';
								}

								fclose( $handle );
								ES_Common::show_message( $message, $status );

							} else {
								$message = __( "Error: Please Select List", 'email-subscribers' );
								ES_Common::show_message( $message, 'error' );
							}
						} else {
							$message = __( "Error: Please select status", 'email-subscribers' );
							ES_Common::show_message( $message, 'error' );
						}
					} else {
						$message = __( "Error: Please Upload only CSV File", 'email-subscribers' );
						ES_Common::show_message( $message, 'error' );
					}
				} else {
					$message = __( "Error: Please Upload File", 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
				}
			} else {
				$message = __( "Error: Please Upload File", 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}
		}

		$this->prepare_import_subscriber_form();

	}

	public function prepare_import_subscriber_form() {

		?>

        <div class="tool-box">
            <form name="form_addemail" id="form_addemail" method="post" action="#" enctype="multipart/form-data">
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="tag-image"><?php _e( 'Select CSV file', 'email-subscribers' ); ?>
                                <p class="description">
									<?php _e( 'Check CSV structure', 'email-subscribers' ); ?>
                                    <a target="_blank" href="<?php echo plugin_dir_url( __FILE__ ) . '../../admin/partials/sample.csv'; ?>"><?php _e( 'from here', 'email-subscribers' ); ?></a>
                                </p>
                            </label>
                        </th>
                        <td>
                            <input type="file" name="file" id="file"/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="tag-email-status">
								<?php _e( 'Select status', 'email-subscribers' ); ?> <p></p>
                            </label>
                        </th>
                        <td>
                            <select name="es_email_status" id="es_email_status">
								<?php echo ES_Common::prepare_statuses_dropdown_options(); ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="tag-email-group">
								<?php _e( 'Select list', 'email-subscribers' ); ?>
                            </label>
                        </th>
                        <td>
                            <select name="list_id" id="list_id">
								<?php echo ES_Common::prepare_list_dropdown_options(); ?>
                            </select>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p style="padding-top:10px;">
					<?php wp_nonce_field( 'import-contacts', 'import_contacts' ); ?>
                    <input type="submit" name="submit" class="button-primary" value=<?php _e( "Import", 'email-subscribers' ); ?>>
                </p>
            </form>
        </div>

		<?php
	}

	public function import_subscribers_page() {

		$audience_tab_main_navigation = array();
		$active_tab                   = 'import';
		$audience_tab_main_navigation = apply_filters( 'ig_es_audience_tab_main_navigation', $active_tab, $audience_tab_main_navigation );

		?>

        <div class="wrap">
            <h2> <?php _e( 'Audience > Import Contacts', 'email-subscribers' );

				ES_Common::prepare_main_header_navigation( $audience_tab_main_navigation );
				?>

            </h2>
			<?php $this->import_report_callback(); ?>
        </div>

		<?php
	}

}

