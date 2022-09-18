<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'ES_Pepipost_Mailer' ) ) {

	class ES_Pepipost_Mailer extends ES_Base_mailer {

		function __construct() {
		}

		function send( $response, $data ) {

			$result = array( 'status' => 'SUCCESS' );
			//wp remote call
			$url                   = 'https://api.pepipost.com/v2/sendEmail';
			$ig_es_mailer_settings = get_option( 'ig_es_mailer_settings' );
			$api_key               = ! empty( $ig_es_mailer_settings['pepipost']['api_key'] ) ? $ig_es_mailer_settings['pepipost']['api_key'] : '';

			$params                                    = array();
			$params['personalizations'][]['recipient'] = ! empty( $data['to_email'] ) ? $data['to_email'] : '';
			$params['from']['fromEmail']               = ! empty( $data['sender_email'] ) ? $data['sender_email'] : '';
			$params['from']['fromName']                = ! empty( $data['sender_name'] ) ? $data['sender_name'] : '';
			$params['subject']                         = ! empty( $data['subject'] ) ? $data['subject'] : '';
			$params['content']                         = ! empty( $data['email_template'] ) ? $data['email_template'] : '';
			$headers                                   = array(
				'user-agent'   => 'APIMATIC 2.0',
				'Accept'       => 'application/json',
				'content-type' => 'application/json; charset=utf-8',
				'api_key'      => $api_key
			);

			$headers = ! empty( $data['headers'] ) ? array_merge( $headers, explode( "\n", $data['headers'] ) ) : $headers;
			$method  = 'POST';
			$qs      = json_encode( $params );

			$options = array(
				'timeout' => 15,
				'method'  => $method,
				'headers' => $headers
			);

			if ( $method == 'POST' ) {
				$options['body'] = $qs;
			}

			$response = wp_remote_request( $url, $options );
			if ( ! is_wp_error( $response ) ) {
				$body = ! empty( $response['body'] ) ? json_decode( $response['body'], true ) : '';

				if ( ! empty( $body ) ) {
					if ( 'Success' === $body['message'] ) {
						return $result;
					} elseif ( ! empty( $body['error_info'] ) ) {
						$result = array(
							'status'  => 'ERROR',
							'message' => $body['error_info']['error_message']
						);
					}

				} else {
					$result = array(
						'status'  => 'ERROR',
						'message' => wp_remote_retrieve_response_message( $response )
					);
				}
			}

			return $result;
		}//send

	}

}
