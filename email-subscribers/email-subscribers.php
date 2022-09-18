<?php
/**
 * Plugin Name: Email Subscribers & Newsletters
 * Plugin URI: https://www.icegram.com/
 * Description: Add subscription forms on website, send HTML newsletters & automatically notify subscribers about new blog posts once it is published.
 * Version: 4.2.2
 * Author: Icegram
 * Author URI: https://www.icegram.com/
 * Requires at least: 3.9
 * Tested up to: 5.2.3
 * Text Domain: email-subscribers
 * Domain Path: /languages/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Copyright (c) 2016-2019 Icegram
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants
 */
//define( 'ES_PLUGIN_DIR', dirname( __FILE__ ) );
// Plugin Folder Path.
if ( ! defined( 'ES_PLUGIN_DIR' ) ) {
	define( 'ES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
define( 'ES_PLUGIN_VERSION', '4.2.2' );
define( 'ES_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'IG_ES_FEEDBACK_VERSION', '1.0.10' );

if ( ! defined( 'ES_PLUGIN_FILE' ) ) {
	define( 'ES_PLUGIN_FILE', __FILE__ );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-email-subscribers-activator.php
 */
function activate_email_subscribers() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-email-subscribers-activator.php';
	Email_Subscribers_Activator::activate();
	add_option( 'email_subscribers_do_activation_redirect', true );
	es_update_current_version_and_date();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-email-subscribers-deactivator.php
 */
function deactivate_email_subscribers() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-email-subscribers-deactivator.php';
	Email_Subscribers_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_email_subscribers' );
register_deactivation_hook( __FILE__, 'deactivate_email_subscribers' );

add_action( 'admin_init', 'email_subscribers_redirect' );

function email_subscribers_redirect() {
	if ( get_option( 'email_subscribers_do_activation_redirect', false ) ) {
		delete_option( 'email_subscribers_do_activation_redirect' );
		wp_redirect( 'admin.php?page=es_dashboard' );
	}
}

add_action( 'upgrader_process_complete', 'es_upgrader_check', 10, 2 );

/**
 * The code that runs actionvation or upgrade
 *
 */
function es_upgrader_check( $upgrader, $options ) {

	// The path to our plugin's main file
	$our_plugin = plugin_basename( __FILE__ );

	// If an update has taken place and the updated type is plugins and the plugins element exists
	if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {

		// Iterate through the plugins being updated and check if ours is there
		foreach ( $options['plugins'] as $plugin ) {

			if ( $plugin == $our_plugin ) {
				es_update_current_version_and_date();
			}
		}
	}

}

function es_update_current_version_and_date() {
	$es_plugin_meta_data = get_plugin_data( WP_PLUGIN_DIR . '/email-subscribers/email-subscribers.php' );
	$es_current_version  = $es_plugin_meta_data['Version'];

	$timezone_format = _x( 'Y-m-d H:i:s', 'timezone date format' );
	$es_current_date = date_i18n( $timezone_format );

	$es_current_version_date_details                       = array();
	$es_current_version_date_details['es_current_version'] = $es_current_version;
	$es_current_version_date_details['es_current_date']    = $es_current_date;

	update_option( 'ig_es_current_version_date_details', $es_current_version_date_details, 'no' );
}


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-email-subscribers.php';

function es_subbox( $namefield = null, $desc = null, $group = null ) {

	$atts = array(
		'namefield' => $namefield,
		'desc'      => $desc,
		'group'     => $group
	);

	echo ES_Shortcode::render_es_subscription_shortcode( $atts );
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    4.0
 */
function run_email_subscribers() {
	$plugin = new Email_Subscribers();
	$plugin->run();
}

//run_email_subscribers();

/**
 * @return Email_Subscribers
 *
 * @since 4.2.1
 */
function ES() {
	return Email_Subscribers::instance();
}

// Start ES
ES();

ES()->run();
