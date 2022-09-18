<?php
/**
 * Created by PhpStorm.
 * User: malayladu
 * Date: 2018-12-20
 * Time: 15:19
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Cron {

	public function __construct() {


		$this->handle_cron_request();
		$this->set_cron();
		add_action( 'ig_es_cron_fifteen_mins', array( $this, 'handle_cron_request' ), 10, 2 );
	}

	public function handle_cron_request( $es = '', $guid = '' ) {

		$is_wp_cron = false;
		if ( ! empty( $es ) ) {
			$es_request = $es;
			$is_wp_cron = true;
		} else {
			$es_request = ig_es_get_request_data( 'es' );
		}

		// It's not a cron request . Say Goodbye!
		if ( 'cron' !== $es_request ) {
			return;
		}

		$ig_es_disable_wp_cron = get_option( 'ig_es_disable_wp_cron', 'no' );

		if ( $is_wp_cron && 'yes' === $ig_es_disable_wp_cron ) {
			return;
		}

		$self = ig_es_get_request_data( 'self', 0 );

		if ( 'cron' === $es_request ) {
			/*
			$ig_es_last_cron_run = get_option( 'ig_es_last_cron_run', true );
			$time_diff           = ( time() - $ig_es_last_cron_run );
			$time_diff           = ( ! empty( $_GET['es_pro'] ) && ( $_GET['es_pro'] == 1 || $_GET['es_pro'] === true || $_GET['es_pro'] === 'true' ) ) ? 1 : floor( $time_diff / 800 );
			*/

			$server_cron = true;
			$time_diff   = 1;
			if ( $is_wp_cron ) {
				$server_cron = false;
			}

			if ( $server_cron || ( $is_wp_cron && $time_diff >= 1 ) || $self == 1 ) {
				$guid = ( ! empty( $guid ) ) ? $guid : ig_es_get_request_data( 'guid' );
				if ( ! empty( $guid ) ) {
					$response = array( 'status' => 'SUCCESS', 'es_remaining_email_count' => 100 );

					// Queue Auto Responder
					do_action('ig_es_cron_auto_responder');

					// Worker
					do_action('ig_es_cron_worker');

					$es_process_request = true;

					// filter request
					$es_process_request = apply_filters( 'es_process_request', $es_process_request );

					if ( true === $es_process_request ) {
						$security1             = strlen( $guid );
						$es_c_cronguid_noslash = str_replace( "-", "", $guid );
						$security2             = strlen( $es_c_cronguid_noslash );
						if ( $security1 == 34 && $security2 == 30 ) {
							if ( ! preg_match( '/[^a-z]/', $es_c_cronguid_noslash ) ) {
								$es_c_cronurl   = ES_Common::get_cron_url();
								$es_c_croncount = (int)get_option( 'ig_es_hourly_email_send_limit', 50 );

								// Modify cron count?
								$es_c_croncount = apply_filters( 'es_email_sending_limit', 0 );
								if ( ! is_numeric( $es_c_croncount ) ) {
									$es_c_croncount = 50; // Set default
								}

								parse_str( $es_c_cronurl, $output );
								if ( $guid === $output['guid'] ) {

									/**
									 * - Get GUID from ig_es_mailing_queue table which are in queue
									 * - Get contacts from the ig_es_sending_queue table based on fetched guid
									 * - Prepare email content
									 * - Send emails based on fetched contacts
									 * - Update status in ig_es_mailing_queue table
									 * - Update status in ig_es_sending_queue table
									 */
									$es_c_croncount = ES_Common::total_emails_to_be_sent( $es_c_croncount );
									if ( $es_c_croncount > 0 ) {

										// Get GUID from sentdetails report which are in queue
										$campaign_hash = ig_es_get_request_data( 'campaign_hash' );

										$notification      = ES_DB_Mailing_Queue::get_notification_to_be_sent( $campaign_hash );
										$notification_guid = isset( $notification['hash'] ) ? $notification['hash'] : null;

										if ( ! is_null( $notification_guid ) ) {
											ES_DB_Mailing_Queue::update_sent_status( $notification_guid, 'Sending' );

											// Get subscribers from the deliverreport table based on fetched guid
											$emails       = ES_DB_Sending_Queue::get_emails_to_be_sent_by_hash( $notification_guid, $es_c_croncount );
											$total_emails = count( $emails );
											// Found Subscribers to send notification?
											if ( $total_emails > 0 ) {
												$ids = array();
												foreach ( $emails as $email ) {
													$ids[] = $email['id'];
												}

												$updated = ES_DB_Sending_Queue::update_sent_status( $ids, 'Sending' );

												// Send out emails
												if ( $updated ) {
													ES_Mailer::prepare_and_send_email( $emails, $notification );
													ES_DB_Sending_Queue::update_sent_status( $ids, 'Sent' );
												}

												$total_remaining_emails      = ES_DB_Sending_Queue::get_total_emails_to_be_sent_by_hash( $notification_guid );
												$remaining_emails_to_be_sent = ES_DB_Sending_Queue::get_total_emails_to_be_sent();

												// No emails left for the $notification_guid??? Send admin notification for the
												// Completion of a job
												if ( $total_remaining_emails == 0 ) {
													ES_DB_Mailing_Queue::update_sent_status( $notification_guid, 'Sent' );

													$notify_admin = get_option( 'ig_es_enable_cron_admin_email', 'yes' );

													if ( 'yes' === $notify_admin ) {

														$admin_email_addresses = get_option( 'ig_es_admin_emails' );
														if ( ! empty( $admin_email_addresses ) ) {
															$template = ES_Mailer::prepare_es_cron_admin_email( $notification_guid );

															if ( ! empty( $template ) ) {
																$subject      = get_option( 'ig_es_cron_admin_email_subject', __( 'Campaign Sent!', 'email-subscribers' ) );
																$notification = ES_DB_Mailing_Queue::get_notification_by_hash( $notification_guid );
																if ( isset( $notification['subject'] ) ) {
																	$subject = str_replace( '{{SUBJECT}}', $notification['subject'], $subject );
																}

																$admin_emails = explode( ',', $admin_email_addresses );
																foreach ( $admin_emails as $admin_email ) {
																	$admin_email = trim( $admin_email );
																	ES_Mailer::send( $admin_email, $subject, $template );
																}
															}
														}
													}
												}

												$response['total_emails_sent']        = $total_emails;
												$response['es_remaining_email_count'] = $remaining_emails_to_be_sent;
												$response['message']                  = 'EMAILS_SENT';
												$response['status']                   = 'SUCCESS';
												// update last cron run time
												update_option( 'ig_es_last_cron_run', time() );
											} else {
												$response['es_remaining_email_count'] = 0;
												$response['message']                  = 'EMAILS_NOT_FOUND';
												$response['status']                   = 'SUCCESS';
												ES_DB_Mailing_Queue::update_sent_status( $notification_guid, 'Sent' );
											}
										} else {
											$response['es_remaining_email_count'] = 0;
											$response['message']                  = 'NOTIFICATION_NOT_FOUND';
											$response['status']                   = 'SUCCESS';
										}
									} else {
										$self                = false;
										$response['status']  = 'ERROR';
										$response['message'] = 'EMAIL_SENDING_LIMIT_EXCEEDED';
									}
								} else {
									$self                = false;
									$response['status']  = 'ERROR';
									$response['message'] = 'CRON_GUID_DOES_NOT_MATCH';
								}
							} else {
								$self                = false;
								$response['status']  = 'ERROR';
								$response['message'] = 'CRON_GUID_PATTERN_DOES_NOT_MATCH';
							}
						} else {
							$self                = false;
							$response['status']  = 'ERROR';
							$response['message'] = 'INVALID_CRON_GUID';
						}
					} else {
						$self                = false;
						$response['status']  = 'ERROR';
						$response['message'] = 'DO_NOT_PROCESS_REQUEST';
					}
				} else {
					$self                = false;
					$response['status']  = 'ERROR';
					$response['message'] = 'EMPTY_CRON_GUID';
				}

			} else {
				$response['es_remaining_email_count'] = 0;
				$response['message']                  = 'PLEASE_TRY_AGAIN_LATER';
				$response['status']                   = 'ERROR';
			}

			if ( $self ) {

				$total_emails_sent       = ! empty( $response['total_emails_sent'] ) ? $response['total_emails_sent'] : 0;
				$status                  = ! empty( $response['status'] ) ? $response['status'] : 'ERROR';
				$total_emails_to_be_sent = ! empty( $response['es_remaining_email_count'] ) ? $response['es_remaining_email_count'] : 0;
				$cron_url                = ES_Common::get_cron_url( true );

				$send_now_text = __( sprintf( "<a href='%s'>Send Now</a>", $cron_url ), 'email-subscribers' );

				if ( 'SUCCESS' === $status ) {
					$message = __( sprintf( 'Email(s) have been sent successfully!' ), 'email-subscribers' );
				} else {
					$message = $this->get_status_messages( $response['message'] );
				}

				include ES_PLUGIN_DIR . '/public/partials/cron-message.php';
				die();
			} else {
				echo json_encode( $response );
				die();
			}

		}

	}

	public function set_cron() {
		$args['es']   = 'cron';
		$es_c_cronurl = ES_Common::get_cron_url();

		if ( ! empty( $es_c_cronurl ) ) {
			parse_str( $es_c_cronurl, $output );
			$args['guid'] = $output['guid'];
			if ( ! wp_next_scheduled( 'ig_es_cron_fifteen_mins', array( $args['es'], $args['guid'] ) ) ) {
				wp_clear_scheduled_hook( 'ig_es_cron_fifteen_mins', array( $args['es'], $args['guid'] ) );
				wp_clear_scheduled_hook( 'es_cron_hourly', array( $args['es'], $args['guid'] ) );
				wp_schedule_event( time(), 'ig_es_fifteen_mins_interval', 'ig_es_cron_fifteen_mins', array( $args['es'], $args['guid'] ) );
			}
		}
	}

	public function get_status_messages( $message = '' ) {

		if ( empty( $message ) ) {
			return '';
		}

		$status_messages = array(
			'EMAILS_SENT'                      => __( 'Emails sent succssfully!', 'email-susbscribers' ),
			'EMAILS_NOT_FOUND'                 => __( 'Emails not found.', 'email-susbscribers' ),
			'NOTIFICATION_NOT_FOUND'           => __( 'No notifications found to send.', 'email-susbscribers' ),
			'CRON_GUID_DOES_NOT_MATCH'         => __( 'Invalid GUID.', 'email-susbscribers' ),
			'CRON_GUID_PATTERN_DOES_NOT_MATCH' => __( 'Invalid GUID.', 'email-susbscribers' ),
			'INVALID_CRON_GUID'                => __( 'Invalid GUID.', 'email-susbscribers' ),
			'DO_NOT_PROCESS_REQUEST'           => __( 'Not allowed to process request.', 'email-susbscribers' ),
			'EMPTY_CRON_GUID'                  => __( 'GUID is empty.', 'email-susbscribers' ),
			'PLEASE_TRY_AGAIN_LATER'           => __( 'Please try after sometime.', 'email-susbscribers' ),
			'EMAIL_SENDING_LIMIT_EXCEEDED'     => __( 'You have hit your hourly email sending limit. Please try after sometime.', 'email-susbscribers' ),
		);

		$message_text = ! empty( $status_messages[ $message ] ) ? $status_messages[ $message ] : '';

		return $message_text;
	}

}

