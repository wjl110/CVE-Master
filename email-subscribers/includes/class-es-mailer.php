<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class ES_Mailer {

	public function __construct() {

	}

	/* prepare cron email*/
	public static function prepare_and_send_email( $mails, $notification ) {

		if ( count( $mails ) <= 0 ) {
			return;
		}

		// $source      = $notification['source'];
		$content     = $notification['body'];
		$subject     = $notification['subject'];
		$guid        = $notification['hash'];
		$template_id = ES()->campaigns_db->get_template_id_by_campaign( $notification['id'] );
		function temp_fun( $mail ) {
			return $mail['email'];
		}

		$emails = array_map( "temp_fun", $mails );

		$emails_name_map = ES_DB_Contacts::get_subscribers_email_name_map( $emails );

		foreach ( $mails as $mail ) {
			$email      = $mail['email'];
			$id         = $mail['contact_id'];
			$guid       = $mail['mailing_queue_hash'];
			$email_name = ! empty( $emails_name_map[ $email ] ) ? $emails_name_map[ $email ]['name'] : '';
			$first_name = ! empty( $emails_name_map[ $email ] ) ? $emails_name_map[ $email ]['first_name'] : '';
			$last_name  = ! empty( $emails_name_map[ $email ] ) ? $emails_name_map[ $email ]['last_name'] : '';

			$keywords = array(
				'name'        => $email_name,
				'first_name'  => $first_name,
				'last_name'   => $last_name,
				'email'       => $email,
				'guid'        => $guid,
				'dbid'        => $id,
				'message_id'  => $notification['id'],
				'campaign_id' => $notification['campaign_id']
			);

			// Preparing email body
			$body = self::prepare_email_template( $content, $keywords );
			//add links

			$send = self::send( $email, $subject, $body );

			if ( $send ) {
				ES_DB_Sending_Queue::update_sent_status( $mail['id'], 'Sent' );
			}

			ES_Common::update_total_email_sent_count();
		}

	}

	public static function prepare_email( $content = '', $data = array() ) {

		$blog_name = get_option( 'blogname' );
		$site_url  = home_url( '/' );

		$name       = isset( $data['name'] ) ? $data['name'] : '';
		$first_name = isset( $data['first_name'] ) ? $data['first_name'] : '';
		$last_name  = isset( $data['last_name'] ) ? $data['last_name'] : '';
		$email      = isset( $data['email'] ) ? $data['email'] : '';
		$list_name  = isset( $data['list_name'] ) ? $data['list_name'] : '';

		$content = str_replace( "{{NAME}}", $name, $content );
		$content = str_replace( "{{FIRSTNAME}}", $first_name, $content );
		$content = str_replace( "{{LASTNAME}}", $last_name, $content );
		$content = str_replace( "{{EMAIL}}", $email, $content );
		$content = str_replace( "{{GROUP}}", $list_name, $content );
		$content = str_replace( "{{LIST}}", $list_name, $content );
		$content = str_replace( "{{SITENAME}}", $blog_name, $content );
		$content = str_replace( "{{SITEURL}}", $site_url, $content );


		$content = nl2br( $content );

		return $content;
	}

	public static function prepare_admin_signup_subject( $data ) {

		$content = get_option( 'ig_es_admin_new_contact_email_subject' );

		$result = self::prepare_email( $content, $data );

		return $result;
	}

	public static function prepare_admin_signup_email( $data ) {

		$content = get_option( 'ig_es_admin_new_contact_email_content' );

		$result = self::prepare_email( $content, $data );

		return $result;
	}

	public static function prepare_welcome_email_subject( $data = array() ) {

		$content = stripslashes( get_option( 'ig_es_welcome_email_subject', __( 'Welcome !', 'email-subscribers' ) ) );

		$result = self::prepare_email( $content, $data );

		return $result;
	}

	public static function prepare_welcome_email( $data ) {

		$blog_name      = get_option( 'blogname' );
		$total_contacts = ES_DB_Contacts::count_active_subscribers_by_list_id();
		$content        = stripslashes( get_option( 'ig_es_welcome_email_content', '' ) );

		$name       = isset( $data['name'] ) ? $data['name'] : '';
		$first_name = isset( $data['first_name'] ) ? $data['first_name'] : '';
		$last_name  = isset( $data['last_name'] ) ? $data['last_name'] : '';
		$email      = isset( $data['email'] ) ? $data['email'] : '';
		$list_name  = isset( $data['list_name'] ) ? $data['list_name'] : '';
		$db_id      = isset( $data['db_id'] ) ? $data['db_id'] : '';
		$guid       = ES_DB_Contacts::get_contact_hash_by_id( $db_id );
		// $guid  = isset( $data['guid'] ) ? $data['guid'] : '';
		$guid = ! empty( $guid ) ? $guid : '';

		$link_data = array(
			'action'     => 'unsubscribe',
			'contact_id' => $db_id,
			'email'      => $email,
			'guid'       => $guid
		);

		$unsubscribe_link = self::prepare_link( $link_data );

		$content = str_replace( "{{NAME}}", $name, $content );
		$content = str_replace( "{{FIRSTNAME}}", $first_name, $content );
		$content = str_replace( "{{LASTNAME}}", $last_name, $content );
		$content = str_replace( "{{EMAIL}}", $email, $content );
		$content = str_replace( "{{SITENAME}}", $blog_name, $content );
		$content = str_replace( "{{GROUP}}", $list_name, $content );
		$content = str_replace( "{{LIST}}", $list_name, $content );
		$content = str_replace( "{{UNSUBSCRIBE-LINK}}", $unsubscribe_link, $content );
		$content = str_replace( "{{LINK}}", $unsubscribe_link, $content );
		$content = str_replace( "{{TOTAL-CONTACTS}}", $total_contacts, $content );

		$content = nl2br( $content );

		return $content;

	}

	public static function prepare_double_optin_email( $data ) {

		$blog_name      = get_option( 'blogname' );
		$total_contacts = ES_DB_Contacts::count_active_subscribers_by_list_id();
		$content        = stripslashes( get_option( 'ig_es_confirmation_mail_content', '' ) );


		$db_id = isset( $data['db_id'] ) ? $data['db_id'] : '';
		$guid  = ES_DB_Contacts::get_contact_hash_by_id( $db_id );
		// $guid  = isset( $data['guid'] ) ? $data['guid'] : '';
		$guid       = ! empty( $guid ) ? $guid : '';
		$email      = isset( $data['email'] ) ? $data['email'] : '';
		$name       = isset( $data['name'] ) ? $data['name'] : '';
		$first_name = isset( $data['first_name'] ) ? $data['first_name'] : '';
		$last_name  = isset( $data['last_name'] ) ? $data['last_name'] : '';

		$link_data      = array(
			'action'     => 'subscribe',
			'contact_id' => $db_id,
			'email'      => $email,
			'guid'       => $guid
		);
		$subscribe_link = self::prepare_link( $link_data );

		$content = str_replace( "{{NAME}}", $name, $content );
		$content = str_replace( "{{FIRSTNAME}}", $first_name, $content );
		$content = str_replace( "{{LASTNAME}}", $last_name, $content );
		$content = str_replace( "{{EMAIL}}", $email, $content );
		$content = str_replace( "{{LINK}}", $subscribe_link, $content );
		$content = str_replace( "{{SITENAME}}", $blog_name, $content );
		$content = str_replace( "{{SUBSCRIBE-LINK}}", $subscribe_link, $content );
		$content = str_replace( "{{TOTAL-CONTACTS}}", $total_contacts, $content );

		$content = nl2br( $content );

		return $content;

	}

	public static function prepare_email_template( $template_content, $keywords, $template_id = 0 ) {

		$name       = isset( $keywords['name'] ) ? $keywords['name'] : '';
		$email      = isset( $keywords['email'] ) ? $keywords['email'] : '';
		$first_name = isset( $keywords['first_name'] ) ? $keywords['first_name'] : '';
		$last_name  = isset( $keywords['last_name'] ) ? $keywords['last_name'] : '';
		$contact_id = isset( $keywords['dbid'] ) ? $keywords['dbid'] : '';
		$hash       = isset( $keywords['hash'] ) ? $keywords['hash'] : '';

		if ( empty( $hash ) ) {
			$hash = ES_DB_Contacts::get_contact_hash_by_id( $contact_id );
		}

		$template_content = str_replace( "{{NAME}}", $name, $template_content );
		$template_content = str_replace( "{{FIRSTNAME}}", $first_name, $template_content );
		$template_content = str_replace( "{{LASTNAME}}", $last_name, $template_content );
		$template_content = str_replace( "{{EMAIL}}", $email, $template_content );

		$template_content = convert_chars( convert_smilies( wptexturize( $template_content ) ) );
		if ( isset( $GLOBALS['wp_embed'] ) ) {
			$template_content = $GLOBALS['wp_embed']->autoembed( $template_content );
		}
		$template_content = wpautop( $template_content );

		$template_content = do_shortcode( shortcode_unautop( $template_content ) );
		$data['content']  = $template_content;
		$data['tmpl_id']  = $template_id;
		$data             = apply_filters( 'es_after_process_template_body', $data );
		$template_content = $data['content'];


		$link_data = array(
			'action'      => 'unsubscribe',
			'message_id'  => ! empty( $keywords['message_id'] ) ? $keywords['message_id'] : 0,
			'campaign_id' => ! empty( $keywords['campaign_id'] ) ? $keywords['campaign_id'] : 0,
			'contact_id'  => $contact_id,
			'email'       => $email,
			'guid'        => $hash
		);

		$unsubscribe_link = self::prepare_link( $link_data );
		$unsubtext        = self::get_unsubscribe_text( $unsubscribe_link );

		$is_track_email_opens = get_option( 'ig_es_track_email_opens', 'yes' );

		$email_tracking_image = '';
		if ( 'yes' === $is_track_email_opens ) {
			$data = array(
				'message_id'  => $keywords['message_id'],
				'campaign_id' => $keywords['campaign_id'],
				'contact_id'  => $contact_id,
				'email'       => $email,
				'guid'        => $hash
			);

			$email_tracking_image = self::get_tracking_pixel( $data );
		}

		$template_content = $template_content . $unsubtext . $email_tracking_image;

		return $template_content;
	}

	/**
	 * Get link
	 *
	 * @param array $data
	 *
	 * @return string
	 *
	 * Modified @since 4.2.0
	 */
	public static function prepare_link( $data = array() ) {
		/**
		 * We are getting different data like action, message_id, campaign_id contact_id, guid, email etc in $data
		 */
		$action = ! empty( $data['action'] ) ? $data['action'] : '';

		if ( 'subscribe' === $action ) {
			$action = 'optin';
		}

		$link = add_query_arg( 'es', $action, site_url( '/' ) );

		$data = ig_es_encode_request_data( $data );

		$link = add_query_arg( 'hash', $data, $link );

		return $link;
	}

	public static function get_unsubscribe_text( $unsublink ) {

		$unsubtext = get_option( 'ig_es_unsubscribe_link_content', '' );
		$unsubtext = stripslashes( $unsubtext );
		$unsubtext = str_replace( "{{LINK}}", $unsublink, $unsubtext );
		$unsubtext = str_replace( "{{UNSUBSCRIBE-LINK}}", $unsublink, $unsubtext );

		return $unsubtext;
	}

	/**
	 * Get Tracking pixel
	 *
	 * @param array $data
	 *
	 * @return string
	 *
	 * @since 4.2.0
	 */
	public static function get_tracking_pixel( $data = array() ) {

		$data['action'] = 'open';

		$url = self::prepare_link( $data );

		$tracking_image = "<img src='{$url}' width='1' height='1' alt=''/>";

		return $tracking_image;

	}

	public static function prepare_unsubscribe_email() {
		$content = get_option( 'ig_es_unsubscribe_success_message' );

		return $content;
	}

	public static function prepare_subscribe_email() {
		$content = get_option( 'ig_es_subscription_success_message' );

		return $content;
	}

	public static function prepare_es_cron_admin_email( $notification_guid ) {

		$notification = ES_DB_Mailing_Queue::get_notification_by_hash( $notification_guid );

		$template = '';

		if ( isset( $notification['subject'] ) ) {
			$email_count  = $notification['count'];
			$post_subject = $notification['subject'];
			$cron_date    = date( 'Y-m-d h:i:s' );

			$template = get_option( 'ig_es_cron_admin_email' );

			$template = str_replace( '{{DATE}}', $cron_date, $template );
			$template = str_replace( '{{COUNT}}', $email_count, $template );
			$template = str_replace( '{{SUBJECT}}', $post_subject, $template );

			$template = nl2br( $template );
		}

		return $template;
	}

	/**
	 * Send Welcome email after subscription
	 *
	 * @param $email
	 * @param $data
	 *
	 * @since 4.1.13
	 */
	public static function send_welcome_email( $email, $data ) {

		// Enable Welcome Email?
		$enable_welcome_email = get_option( 'ig_es_enable_welcome_email', 'no' );

		if ( $enable_welcome_email === 'yes' ) {

			// Prepare Welcome Email Subject
			$subject = ES_Mailer::prepare_welcome_email_subject( $data );

			// Prepare Welcome Email Content
			$content = ES_Mailer::prepare_welcome_email( $data );

			// Send Email
			ES_Mailer::send( $email, $subject, $content );
		}

	}

	public static function send( $to_email, $subject, $email_template ) {

		$response = array('status' => 'ERROR');
		$subject        = html_entity_decode( $subject, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$get_email_type = get_option( 'ig_es_email_type', true );
		$site_title     = get_bloginfo();
		$admin_email    = get_option( 'admin_email' );
		//adding missing header
		$from_name  = get_option( 'ig_es_from_name', true );
		$from_email = get_option( 'ig_es_from_email', true );


		$sender_email = ! empty( $from_email ) ? $from_email : $admin_email;
		$sender_name  = ! empty( $from_name ) ? $from_name : $site_title;

		$headers = array(
			"From: \"$sender_name\" <$sender_email>",
			"Return-Path: <" . $sender_email . ">",
			"Reply-To: \"" . $sender_name . "\" <" . $sender_email . ">"
		);

		if ( in_array( $get_email_type, array( 'php_html_mail', 'php_plaintext_mail' ) ) ) {
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "X-Mailer: PHP" . phpversion();
		}

		$plain_text_template   = self::convert_to_text( $email_template );
		$headers[]             = "Content-Type: text/html; charset=\"" . get_bloginfo( 'charset' ) . "\"";
		$headers               = implode( "\n", $headers );
		$ig_es_mailer_settings = get_option( 'ig_es_mailer_settings' );
		if ( ! empty( $ig_es_mailer_settings['mailer'] ) && 'phpmail' !== $ig_es_mailer_settings['mailer'] ) {
			$data = array(
				'to_email'            => $to_email,
				'subject'             => $subject,
				'email_template'      => $email_template,
				'plain_text_template' => $plain_text_template,
				'headers'             => $headers,
				'sender_email'        => $sender_email,
				'sender_name'         => $sender_name,
				'email_type'          => $get_email_type
			);

			$response = apply_filters( 'ig_es_lite_do_send', $response, $data );

		} else {
			$result = mail( $to_email, $subject, $email_template, $headers );
			if($result) {
				$response['status'] = 'SUCCESS';
			}
		}

		return $response;

	}

	public static function convert_to_text( $html, $links_only = false ) {

		if ( $links_only ) {
			$links = '/< *a[^>]*href *= *"([^#]*)"[^>]*>(.*)< *\/ *a *>/Uis';
			$text  = preg_replace( $links, '${2} [${1}]', $html );
			$text  = str_replace( array( ' ', '&nbsp;' ), ' ', strip_tags( $text ) );
			$text  = @html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

			return trim( $text );

		} else {
			require_once ES_PLUGIN_DIR . '/includes/libraries/class-es-html2text.php';
			$htmlconverter = new ES_Html2Text( $html, array( 'width' => 200, 'do_links' => 'table' ) );

			$text = trim( $htmlconverter->get_text() );
			$text = preg_replace( '/\s*$^\s*/mu', "\n\n", $text );
			$text = preg_replace( '/[ \t]+/u', ' ', $text );

			return $text;

		}

	}
}