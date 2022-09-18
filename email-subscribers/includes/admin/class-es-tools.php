<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Admin Settings
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/admin
 * @author     Your Name <email@example.com>
 */
class ES_Tools {
	// class instance
	static $instance;

	// class constructor
	public function __construct() {
		add_action( 'wp_ajax_es_send_test_email', array( $this, 'es_send_test_email_callback' ) );
	}

	public static function es_send_test_email_callback() {
	    $email = sanitize_email(ig_es_get_request_data('es_test_email'));

		$email_response = '';
		$response       = array();
		if ( ! empty( $email ) ) {
			$subject  = 'Email Subscribers: ' . sprintf( esc_html__( 'Test email to %s', 'email-subscribers' ), $email );
			$content  = self::get_email_message();
			$response = ES_Mailer::send( $email, $subject, $content );
			if ( $response['status'] === 'SUCCESS' ) {
				$response['message'] = __( 'Email has been sent. Please check your inbox', 'email-subscribers' );
			}
			// if ( $email_response ) {
			// 	$response['status']  = 'success';
			// } else {
			// 	$response['message'] = __( 'Something went wrong', 'email-subscribers' );
			// 	$response['status']  = 'error';
			// }
		}

		echo json_encode( $response );
		exit;
	}


	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function get_email_message() {
		ob_start();
		?>

        <html>
        <head></head>
        <body>
        <p>Congrats, test email was sent successfully!</p>

        <p>Thank you for trying out Email Subscribers. We are on a mission to make the best Email Marketing Automation plugin for WordPress.</p>

        <p>If you find this plugin useful, please consider giving us <a href="https://wordpress.org/support/plugin/email-subscribers/reviews/?filter=5">5 stars review</a> on WordPress!</p>

        <p>Nirav Mehta</p>
        <p>Founder, <a href="https://www.icegram.com/">Icegram</a></p>
        </body>
        </html>

		<?php
		$message = ob_get_clean();

		return $message;

	}
}


?>