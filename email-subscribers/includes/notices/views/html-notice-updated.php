<?php
/**
 * Admin View: Notice - Updated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated es-message es-connect es-message--success">
	<a class="es-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-hide-notice', 'update', remove_query_arg( 'do_update_ig_es' ) ), 'ig_es_hide_notices_nonce', '_ig_es_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'email-subscribers' ); ?></a>

	<p><?php _e( 'Email Subscribers data update complete. Thank you for updating to the latest version!', 'email-subscribers' ); ?></p>
</div>
