<?php

/**
 * Get additional system & plugin specific information for feedback
 *
 */
if ( ! function_exists( 'ig_es_get_additional_info' ) ) {

	function ig_es_get_additional_info( $additional_info, $system_info = false ) {
		global $ig_es_tracker;

		$additional_info['version'] = ES_PLUGIN_VERSION;

		if ( $system_info ) {

			$additional_info['active_plugins']   = implode( ', ', $ig_es_tracker::get_active_plugins() );
			$additional_info['inactive_plugins'] = implode( ', ', $ig_es_tracker::get_inactive_plugins() );
			$additional_info['current_theme']    = $ig_es_tracker::get_current_theme_info();
			$additional_info['wp_info']          = $ig_es_tracker::get_wp_info();
			$additional_info['server_info']      = $ig_es_tracker::get_server_info();

			// ES Specific information
			$additional_info['plugin_meta_info'] = ES_Common::get_ig_es_meta_info();
		}

		return $additional_info;
	}

}

add_filter( 'ig_es_additional_feedback_meta_info', 'ig_es_get_additional_info', 10, 2 );

/**
 * Render general feedback on click of "Feedback" button from ES sidebar
 */
function ig_es_render_general_feedback_widget() {

	if ( is_admin() ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Get all Email Subscribers Screen
		$show_on_screens = ES_Common::get_all_es_admin_screens();

		if ( ! in_array( $screen_id, $show_on_screens ) ) {
			return;
		}

		$event = 'plugin.feedback';

		$params = array(
			'type'              => 'feedback',
			'event'             => $event,
			'title'             => "Have feedback or question for us?",
			'position'          => 'center',
			'width'             => 700,
			'force'             => true,
			'confirmButtonText' => __( 'Send', 'email-subscribers' ),
			'consent_text'      => __( 'Allow Email Subscribers to track plugin usage. We guarantee no sensitive data is collected.', 'email-subscribers' ),
			'name'              => ''
		);

		ES_Common::render_feedback_widget( $params );
	}
}

add_action( 'admin_footer', 'ig_es_render_general_feedback_widget' );

/**
 * Render Broadcast Created feedback widget.
 *
 * @since 4.1.14
 */
function ig_es_render_broadcast_created_feedback_widget() {

	$event = 'broadcast.created';

	$params = array(
		'type'              => 'emoji',
		'event'             => $event,
		'title'             => "How's your experience sending broadcast?",
		'position'          => 'top-end',
		'width'             => 300,
		'delay'             => 2, // seconds
		'confirmButtonText' => __( 'Send', 'email-subscribers' )
	);

	ES_Common::render_feedback_widget( $params );
}

add_action( 'ig_es_broadcast_created', 'ig_es_render_broadcast_created_feedback_widget' );

/**
 * Render Broadcast Created feedback widget.
 *
 * @since 4.1.14
 */
function ig_es_render_fb_widget() {

	if ( is_admin() ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$show_on_screens = ES_Common::get_all_es_admin_screens();

		if ( ! in_array( $screen_id, $show_on_screens ) ) {
			return;
		}

		$total_contacts = ES_DB_Contacts::get_total_subscribers();

		// Got 25 contacts?
		// It's time to Join Email Subscribers Secret Club on Facebook
		if ( $total_contacts >= 25 ) {

			$event = 'join.fb';

			$params = array(
				'type'              => 'fb',
				'title'             => __( 'Not a member yet?', 'email-subscribers' ),
				'event'             => $event,
				'html'              => '<div style="text-align:center;"> ' . __( 'Join', 'email-subscribers' ) . '<strong> ' . __( 'Email Subscribers Secret Club', 'email-subscribers' ) . '</strong> ' . __( 'on Facebook', 'email-subscribers' ) . '</div>',
				'position'          => 'bottom-center',
				'width'             => 500,
				'delay'             => 2, // seconds
				'confirmButtonText' => '<i class="dashicons dashicons-es dashicons-facebook"></i> ' . __( 'Join Now', 'email-subscribers' ),
				'show_once'         => true
			);

			ES_Common::render_feedback_widget( $params );
		}
	}

}

add_action( 'admin_footer', 'ig_es_render_fb_widget' );