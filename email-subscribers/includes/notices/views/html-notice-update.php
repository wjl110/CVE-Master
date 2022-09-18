<?php
/**
 * Admin View: Notice - Update
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$update_url = wp_nonce_url(
	add_query_arg( 'do_update_ig_es', 'true', admin_url( 'admin.php?page=es_dashboard' ) ),
	'ig_es_db_update',
	'ig_es_db_update_nonce'
);

?>
<div id="message" class="updated">
	<p>
		<strong><?php esc_html_e( 'Email Subscribers data update', 'email-subscribers' ); ?></strong> &#8211; <?php esc_html_e( 'We need to update your data store to the latest version.', 'email-subscribers' ); ?>
	</p>
	<p class="submit">
		<a href="<?php echo esc_url( $update_url ); ?>" class="es-update-now button-primary">
			<?php esc_html_e( 'Run the updater', 'email-subscribers' ); ?>
		</a>
	</p>
</div>
<script type="text/javascript">
	jQuery( '.es-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'Are you sure you wish to run the updater now?', 'email-subscribers' ) ); ?>' ); // jshint ignore:line
	});
</script>
