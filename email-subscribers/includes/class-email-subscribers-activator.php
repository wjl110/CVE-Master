<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      4.0
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/includes
 * @author     Your Name <email@example.com>
 */
class Email_Subscribers_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    4.0
	 */
	public static function activate() {
		require_once dirname( __FILE__ ) . '/class-es-install.php';
		ES_Install::install();
	}

	public static function register_email_templates() {
		$labels = array(
			'name'               => __( 'Templates', 'EMAIL_SUBSCRIBERS' ),
			'singular_name'      => __( 'Templates', 'EMAIL_SUBSCRIBERS' ),
			'add_new'            => __( 'Add new Template', 'EMAIL_SUBSCRIBERS' ),
			'add_new_item'       => __( 'Add new Template', 'EMAIL_SUBSCRIBERS' ),
			'edit_item'          => __( 'Edit Templates', 'EMAIL_SUBSCRIBERS' ),
			'new_item'           => __( 'New Templates', 'EMAIL_SUBSCRIBERS' ),
			'all_items'          => __( 'Templates', 'EMAIL_SUBSCRIBERS' ),
			'view_item'          => __( 'View Templates', 'EMAIL_SUBSCRIBERS' ),
			'search_items'       => __( 'Search Templates', 'EMAIL_SUBSCRIBERS' ),
			'not_found'          => __( 'No Templates found', 'EMAIL_SUBSCRIBERS' ),
			'not_found_in_trash' => __( 'No Templates found in Trash', 'EMAIL_SUBSCRIBERS' ),
			'parent_item_colon'  => __( '', 'EMAIL_SUBSCRIBERS' ),
			'menu_name'          => __( 'Email Subscribers', 'EMAIL_SUBSCRIBERS' ),
			'featured_image'     => __( 'Thumbnail (For Visual Representation only)', 'EMAIL_SUBSCRIBERS' ),
			'set_featured_image' => __( 'Set thumbnail', 'EMAIL_SUBSCRIBERS' )
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => 'es_template' ),
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title', 'editor', 'thumbnail' )
		);

		register_post_type( 'es_template', $args );
	}


}

