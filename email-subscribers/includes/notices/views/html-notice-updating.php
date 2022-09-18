<?php
/**
 * Admin View: Notice - Updating
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$force_update_url = wp_nonce_url(
	add_query_arg( 'force_update_ig_es', 'true', admin_url( 'admin.php?page=es_settings' ) ),
	'ig_es_force_db_update',
	'ig_es_force_db_update_nonce'
);

?>
<div id="message" class="updated es-message es-connect">
	<p>
		<strong><?php esc_html_e( 'Email Subscribers data update', 'email-subscribers' ); ?></strong> &#8211; <?php esc_html_e( 'Your database is being updated in the background. Please be patient.', 'email-subscribers' ); ?>
        <!--
		<a href="<?php echo esc_url( $force_update_url ); ?>">
			<?php esc_html_e( 'Taking a while? Click here to run it now.', 'email-subscribers' ); ?>
		</a>
		-->
	</p>
</div>
