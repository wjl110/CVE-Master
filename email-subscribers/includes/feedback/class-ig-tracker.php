<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'IG_Tracker_V_1_0_10' ) ) {

	/**
	 * Class IG_Tracker_V_1_0_9
	 *
	 * Icegram tracker handler class is responsible for sending anonymous plugin
	 * data to Icegram servers for users that actively allowed data tracking.
	 *
	 * @class       IG_Tracker_V_1_0_9
	 * @package     feedback
	 * @copyright   Copyright (c) 2019, Icegram
	 * @license     https://opensource.org/licenses/gpl-license GNU Public License
	 * @author      Icegram
	 * @since       1.0.0
	 *
	 */
	class IG_Tracker_V_1_0_10 {

		/**
		 * Get Active, Inactive or all plugins info
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_plugins( $status = 'all', $details = false ) {

			$plugins = array(
				'active_plugins'   => array(),
				'inactive_plugins' => array()
			);

			// Check if get_plugins() function exists. This is required on the front end of the
			// site, since it is in a file that is normally only loaded in the admin.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$all_plugins    = get_plugins();
			$active_plugins = get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$sitewide_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
				$active_plugins             = ! empty( $active_plugins ) ? array_merge( $sitewide_activated_plugins, $active_plugins ) : $sitewide_activated_plugins;
			}

			foreach ( $all_plugins as $plugin_path => $plugin ) {
				// If the plugin isn't active, don't show it.
				if ( in_array( $plugin_path, $active_plugins ) ) {
					$slug = 'active_plugins';
				} else {
					$slug = 'inactive_plugins';
				}

				if ( $details ) {

					$plugin_data = array(
						'name'       => $plugin['Name'],
						'version'    => $plugin['Version'],
						'author'     => $plugin['Author'],
						'author_uri' => $plugin['AuthorURI'],
						'plugin_uri' => $plugin['PluginURI']
					);

					$plugins[ $slug ][ $plugin_path ] = $plugin_data;
				} else {
					$plugins[ $slug ][] = $plugin_path;
				}
			}

			if ( 'active' === $status ) {
				return $plugins['active_plugins'];
			} elseif ( 'inactive' === $status ) {
				return $plugins['inactive_plugins'];
			} else {
				return array_merge( $plugins['active_plugins'], $plugins['inactive_plugins'] );
			}
		}

		/**
		 * Get Active Plugins
		 *
		 * @param bool $details
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_active_plugins( $details = false ) {
			return self::get_plugins( 'active', $details );
		}

		/**
		 * Get Inactive plugins
		 *
		 * @param bool $details
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_inactive_plugins( $details = false ) {
			return self::get_plugins( 'inactive', $details );
		}

		/**
		 * Get Current Theme Info
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_current_theme_info() {
			$current_theme = array();
			if ( function_exists( 'wp_get_theme' ) ) {
				$theme_data    = wp_get_theme();
				$current_theme = array(
					'name'       => $theme_data->get( 'Name' ),
					'version'    => $theme_data->get( 'Version' ),
					'author'     => $theme_data->get( 'Author' ),
					'author_uri' => $theme_data->get( 'AuthorURI' )
				);
			} elseif ( function_exists( 'get_theme_data' ) ) {
				$theme_data    = get_theme_data( get_stylesheet_directory() . '/style.css' );
				$current_theme = array(
					'name'       => $theme_data['Name'],
					'version'    => $theme_data['Version'],
					'author'     => $theme_data['Author'],
					'author_uri' => $theme_data['AuthorURI']
				);
			}

			return $current_theme;
		}

		/**
		 * Get server info
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_server_info() {
			global $wpdb;

			$server_info = array(
				'php_version'                  => PHP_VERSION,
				'mysql_version'                => $wpdb->db_version(),
				'web_server_info'              => $_SERVER['SERVER_SOFTWARE'],
				'user_agent'                   => $_SERVER['HTTP_USER_AGENT'],
				'php_memory_limit'             => ini_get( 'memory_limit' ),
				'php_post_max_size'            => ini_get( 'post_max_size' ),
				'php_upload_max_file_size'     => ini_get( 'upload_max_filesize' ),
				'php_max_execution_time'       => ini_get( 'max_execution_time' ),
				'session'                      => isset( $_SESSION ) ? 'enabled' : 'disabled',
				'session_name'                 => esc_html( ini_get( 'session.name' ) ),
				'cookie_path'                  => esc_html( ini_get( 'session.cookie_path' ) ),
				'session_save_path'            => esc_html( ini_get( 'session.save_path' ) ),
				'use_cookies'                  => ini_get( 'session.use_cookies' ) ? 'on' : 'off',
				'use_only_cookies'             => ini_get( 'session.use_only_cookies' ) ? 'on' : 'off',
				'ssl_support_extension_loaded' => extension_loaded( 'openssl' ) ? 'yes' : 'no',
				'mb_string_extension_loaded'   => extension_loaded( 'mbstring' ) ? 'yes' : 'no',
			);

			return $server_info;
		}

		/**
		 * Get WordPress information
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_wp_info() {
			global $wpdb;

			$wp_info = array(
				'site_url'              => site_url(),
				'home_url'              => home_url(),
				'wp_version'            => get_bloginfo( 'version' ),
				'permalink_structure'   => get_option( 'permalink_structure' ),
				'multisite'             => is_multisite() ? 'yes' : 'no',
				'wp_debug'              => defined( 'WP_DEBUG' ) ? ( WP_DEBUG ? 'enabled' : 'disabled' ) : '',
				'display_errors'        => ( ini_get( 'display_errors' ) ) ? 'on' : 'off',
				'wp_table_prefix'       => $wpdb->prefix,
				'wp_db_charset_Collate' => $wpdb->get_charset_collate(),
				'wp_memory_limit'       => ( size_format( (int) WP_MEMORY_LIMIT * 1048576 ) ),
				'wp_upload_size'        => ( size_format( wp_max_upload_size() ) ),
				'filesystem_method'     => get_filesystem_method(),
			);

			return $wp_info;
		}
	}
}