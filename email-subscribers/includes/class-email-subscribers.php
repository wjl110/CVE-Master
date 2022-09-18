<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      4.0
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/includes
 * @author     Your Name <email@example.com>
 */
class Email_Subscribers {
	/**
	 *
	 * @since 4.2.1
	 *
	 * @var Email_Subscribers The one true Email_Subscribers
	 *
	 */
	private static $instance;

	/**
	 * ES_Queue object
	 *
	 * @since 4.2.1
	 * @var object|ES_Queue
	 *
	 */
	public $queue;

	/**
	 * ES_DB_Queue object
	 *
	 * @since 4.2.1
	 * @var object|ES_DB_Queue
	 *
	 */
	public $queue_db;
	/**
	 * ES_Actions object
	 *
	 * @since 4.2.1
	 * @var object|ES_Actions
	 *
	 */
	public $actions;

	/**
	 * ES_DB_Actions object
	 *
	 * @since 4.2.1
	 * @var object|ES_DB_Actions
	 *
	 */
	public $actions_db;

	/**
	 * Feedback object
	 *
	 * @since 4.2.1
	 *
	 * @var $feedback
	 *
	 */
	public $feedback;

	/**
	 * Tracker Object
	 *
	 * @since 4.2.1
	 *
	 * @var $tracker
	 *
	 */
	public $tracker;

	/**
	 * @since 4.2.1
	 *
	 * @var object|ES_Campaigns_Table
	 */
	public $campaigns;

	/**
	 * ES_DB_Campaigns object
	 *
	 * @since 4.2.1
	 *
	 * @var object|ES_DB_Campaigns
	 *
	 */
	public $campaigns_db;

	/**
	 * @since 4.2.1
	 * @var object|ES_Lists_Table
	 *
	 */
	public $lists;

	/**
	 * @since 4.2.1
	 *
	 * @var object|ES_DB_Lists
	 *
	 */
	public $lists_db;


	/**
	 * @since 4.2.1
	 * @var object|ES_Forms_Table
	 *
	 */
	public $forms;

	/**
	 * @since 4.2.1
	 *
	 * @var object|ES_DB_Forms
	 */
	public $forms_db;

	/**
	 * @since 4.2.1
	 *
	 * @var object|ES_Contacts_Table
	 */
	public $contacts;

	/**
	 * @since 4.2.1
	 *
	 * @var object|ES_DB_Contacts
	 */
	public $contacts_db;

	/**
	 * @since 4.2.2
	 *
	 * @var object|ES_DB_Blocked_Emails
	 */
	public $blocked_emails_db;

	/**
	 *
	 * @since 4.2.1
	 *
	 * @var object|ES_Integrations
	 *
	 */
	public $integrations;

	/**
	 * @since 4.2.1
	 *
	 * @var object|IG_Logger
	 *
	 */
	public $logger;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    4.0
	 * @access   protected
	 * @var      Email_Subscribers_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    4.0
	 * @access   protected
	 * @var      string $email_subscribers The string used to uniquely identify this plugin.
	 */
	protected $email_subscribers;

	/**
	 * The current version of the plugin.
	 *
	 * @since    4.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    4.0
	 */
	public function ___construct() {
		global $ig_es_feedback, $ig_es_tracker;

		require_once plugin_dir_path( __FILE__ ) . 'class-email-subscribers-activator.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-subscribers-deactivator.php';

		add_action( 'admin_notices', array( $this, 'add_admin_notice' ) );
		add_action( 'admin_init', array( &$this, 'es_dismiss_admin_notice' ) );
		if ( ! post_type_exists( 'es_template' ) ) {
			add_action( 'init', array( 'Email_Subscribers_Activator', 'register_email_templates' ) );
		}

		$this->email_subscribers = 'email-subscribers';

		$this->define_constants();
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$ig_es_tracker = 'IG_Tracker_V_' . str_replace( '.', '_', IG_ES_FEEDBACK_VERSION );
		if ( is_admin() ) {
			$ig_es_feedback_class = 'IG_Feedback_V_' . str_replace( '.', '_', IG_ES_FEEDBACK_VERSION );
			$ig_es_feedback       = new $ig_es_feedback_class( 'Email Subscribers', 'email-subscribers', 'ig_es', 'esfree.', false );
			$ig_es_feedback->render_deactivate_feedback();
		}

		add_action( 'widgets_init', array( $this, 'register_es_widget' ) );
		add_filter( 'cron_schedules', array( $this, 'es_add_cron_interval' ) );
	}

	/**
	 * Add Admin Notices
	 *
	 * @since 4.0.0
	 */
	public function add_admin_notice() {
		global $ig_es_tracker;

		$active_plugins = $ig_es_tracker::get_active_plugins();

		if ( is_admin() && in_array( 'email-subscribers-premium/email-subscribers-premium.php', $active_plugins ) ) {
			$es_pro_plugin_meta_data = get_plugin_data( WP_PLUGIN_DIR . '/email-subscribers-premium/email-subscribers-premium.php' );
			$es_pro_plugin_version   = $es_pro_plugin_meta_data['Version'];

			if ( is_admin() && ! empty( $es_pro_plugin_version ) && version_compare( $es_pro_plugin_version, '4.2.1', '<' ) ) {
				$upgrade_url = admin_url( 'plugins.php?plugin_status=upgrade' );
				ob_start();
				?>
                <div class="notice notice-error">
                    <p><?php echo sprintf( __( '<strong>Email Subscribers</strong> plugin is activated but it won\'t work because it needs <strong>Email Subscribers Premium</strong> plugin to be updated. Please <a href="%s" target="_blank">update</a> plugin first.', 'email-subscribers-premium' ), $upgrade_url ); ?></p>
                </div>
				<?php
				return;
			}
		}

		$screen          = get_current_screen();
		$screen_id       = $screen ? $screen->id : '';
		$show_on_screens = array(
			'toplevel_page_es_dashboard',
			'email-subscribers_page_es_subscribers',
			'email-subscribers_page_es_forms',
			'email-subscribers_page_es_campaigns',
			'email-subscribers_page_es_reports',
			'email-subscribers_page_es_settings',
			'email-subscribers_page_es_general_information',
		);

		if ( ! in_array( $screen_id, $show_on_screens, true ) ) {
			return;
		}

		// Don't show admin notices on Dashboard if onboarding is not yet completed.
		$is_onboarding_complete = get_option( 'ig_es_onboarding_complete', false );

		// We don't have ig_es_onboarding_complete option if somebody is migrating from older version
		if ( ( 'toplevel_page_es_dashboard' === $screen_id ) && ( ! $is_onboarding_complete || $is_onboarding_complete == 'no' ) ) {
			return;
		}

		//Email Subscribers Pro update notice
		$active_plugins = $ig_es_tracker::get_active_plugins();

		if ( is_admin() && in_array( 'email-subscribers-premium/email-subscribers-premium.php', $active_plugins ) ) {
			$es_pro_plugin_meta_data = get_plugin_data( WP_PLUGIN_DIR . '/email-subscribers-premium/email-subscribers-premium.php' );
			$es_pro_plugin_version   = $es_pro_plugin_meta_data['Version'];

			if ( is_admin() && ! empty( $es_pro_plugin_version ) && version_compare( $es_pro_plugin_version, 4.0, '<' ) ) {
				$url = admin_url( "plugins.php?plugin_status=upgrade" );
				?>
                <div class="notice notice-error">
                    <p><?php echo sprintf( __( '<strong>Email Subscribers Pro</strong> plugin is activated but it won\'t work because it needs plugin to be updated. Please update %s plugin first.', 'email-subscribers-premium' ),
							'<a href="' . $url . '" target="_blank">' . __( 'Email Subscribers Pro', 'email-subscribers' ) . '</a>' ); ?></p>
                </div>
				<?php
				return;
			}
			if ( is_admin() && ! empty( $es_pro_plugin_version ) && version_compare( $es_pro_plugin_version, '4.2.1', '<' ) ) {
				$url             = admin_url( "plugins.php?plugin_status=upgrade" );
				$es_upgrade_text = __( 'We have released a recommended update of Email subscribers Premium. So kindly ', 'email-subscribers-premium' ) . '<a href=' . $url . ' target="_blank" style="cursor:pointer">' . __( "update to the latest version", "email-subscribers-premium" ) . '</a>' . __( " right now", "email-subscribers-premium" );
				echo '<div class="notice notice-error" style="background-color: #FFF;"><p style="letter-spacing: 0.6px;">' . $es_upgrade_text . '</p></div>';
			}
		}
		$es_premium  = 'email-subscribers-premium/email-subscribers-premium.php';
		$all_plugins = $ig_es_tracker::get_plugins();
		//cron notice
		$notice_option = get_option( 'ig_es_wp_cron_notice' );

		$show_notice = true;
		$show_notice = apply_filters( 'ig_es_show_wp_cron_notice', $show_notice );

		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON && $notice_option != 'yes' && $show_notice ) {
			$es_cron_url            = 'https://www.icegram.com/documentation/how-to-enable-the-wordpress-cron/?utm_source=es&utm_medium=in_app&utm_campaign=view_admin_notice';
			$cpanel_url             = 'https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=es&utm_medium=in_app&utm_campaign=view_admin_notice';
			$es_pro_url             = 'https://www.icegram.com/documentation/es-how-to-schedule-cron-emails-in-cpanel/?utm_source=es&utm_medium=in_app&utm_campaign=view_admin_notice';
			$disable_wp_cron_notice = sprintf( __( 'WordPress Cron is disable on your site. Email notifications from Email Subscribers plugin will not be sent automatically. <a href="%s" target="_blank" >Here\'s how you can enable it.</a>', 'email-subscribers' ), $es_cron_url );
			$disable_wp_cron_notice .= '<br/>' . sprintf( __( 'Or schedule Cron in <a href="%s" target="_blank">cPanel</a>', 'email-subscribers' ), $cpanel_url );
			$disable_wp_cron_notice .= '<br/>' . sprintf( __( 'Or use <strong><a href="%s" target="_blank">Email Subscribers Pro</a></strong> for automatic Cron support', 'email-subscribers' ), $es_pro_url );
			echo '<div class="notice notice-warning" style="background-color: #FFF;"><p style="letter-spacing: 0.6px;">' . $disable_wp_cron_notice . '<a style="float:right" class="es-admin-btn es-admin-btn-secondary " href="' . admin_url() . '?es_dismiss_admin_notice=1&option_name=wp_cron_notice">' . __( 'OK, I Got it!',
					'email-subscribers' ) . '</a></p></div>';
		}

		$sequence_option_dismiss = get_option( 'ig_es_sequence_release_notice_dismiss' );
		$sequence_option_seen    = get_option( 'ig_es_sequence_release_notice_seen' );
		if ( ! in_array( $es_premium, $all_plugins ) && is_admin() && $sequence_option_seen != 'yes' && $sequence_option_dismiss != 'yes' ) {
			$sequence_url    = admin_url() . '?es_dismiss_admin_notice=1&option_name=sequence_release_notice_seen';
			$sequence_notice = sprintf( __( '📣 <b>[ Released ]</b> New Feature : <strong>Sequences</strong> (Autoresponders) using Email Subscribers <a href="%s" target="_blank">[Read more]</a>', 'email-subscribers' ), $sequence_url );
			echo '<div class="notice notice-warning" style="background-color: #FFF;"><p style="letter-spacing: 0.6px;">' . $sequence_notice . '<a style="float:right" class="es-admin-btn es-admin-btn-secondary " href="' . admin_url() . '?es_dismiss_admin_notice=1&option_name=sequence_release_notice_dismiss">' . __( 'OK, I Got it!',
					'email-subscribers' ) . '</a></p></div>';
		}

		//pre-halloween 2019 :start
        $timezone_format = _x('Y-m-d', 'timezone date format');
        $ig_current_date = strtotime(date_i18n($timezone_format));
        $ig_es_offer_start = strtotime("2019-10-15");
        $ig_es_offer_end = strtotime("2019-10-17");
        if(($ig_current_date >= $ig_es_offer_start) && ($ig_current_date <= $ig_es_offer_end)) {
            include_once( EMAIL_SUBSCRIBERS_DIR.'/ig-es-offer.php');
        }
        //pre-halloween 2019 :end


	}

	/**
	 * Dismiss Admin Notices
	 *
	 * @since 4.0.0
	 */
	public function es_dismiss_admin_notice() {
		$es_dismiss_admin_notice = ig_es_get_request_data( 'es_dismiss_admin_notice' );
		$option_name             = ig_es_get_request_data( 'option_name' );
		if ( $es_dismiss_admin_notice == '1' && ! empty( $option_name ) ) {
			update_option( 'ig_es_' . $option_name, 'yes' );
			if ( in_array( $option_name, array( 'redirect_upsale_notice', 'dismiss_upsale_notice', 'dismiss_star_notice', 'star_notice_done' ) ) ) {
				update_option( 'ig_es_' . $option_name . '_date', ig_get_current_date_time() );
			}
			if ( $option_name === 'star_notice_done' ) {
				header( "Location: https://wordpress.org/support/plugin/email-subscribers/reviews/" );
				exit();
			}
			if ( $option_name === 'sequence_release_notice_seen' ) {
				header( "Location: https://www.icegram.com/autoresponders-the-simplest-way-of-keeping-your-customers-engaged-for-long/?utm_source=es&utm_medium=es_upsale_banner&utm_campaign=es_upsale_sequences" );
				exit();
			}
			if ( $option_name === 'redirect_upsale_notice' ) {
				header( "Location: https://www.icegram.com/email-subscribers-starter-plan-pricing/?utm_source=es&utm_medium=es_upsale_banner&utm_campaign=es_upsale" );
				exit();
			} if($option_name === 'offer_pre_halloween_done_2019'){
                header("Location: https://www.icegram.com/?utm_source=in_app&utm_medium=es_banner&utm_campaign=pre-halloween-2019");
                exit();
            }else {
				$referer = wp_get_referer();
				wp_safe_redirect( $referer );
			}
			exit();
		}
	}

	/**
	 * Define Contstants
	 *
	 * @since 4.0.0
	 */
	public function define_constants() {

		global $wpdb;

		$upload_dir = wp_upload_dir( null, false );

		if ( ! defined( 'EMAIL_SUBSCRIBERS_SLUG' ) ) {
			define( 'EMAIL_SUBSCRIBERS_SLUG', 'email-subscribers' );
		}

		if ( ! defined( 'IG_LOG_DIR' ) ) {
			define( 'IG_LOG_DIR', $upload_dir['basedir'] . '/ig-logs/' );
		}

		if ( ! defined( 'EMAIL_SUBSCRIBERS_INCLUDES_DIR' ) ) {
			define( 'EMAIL_SUBSCRIBERS_INCLUDES_DIR', __DIR__ . '/includes' );
		}

		if ( ! defined( 'EMAIL_SUBSCRIBERS_DIR' ) ) {
			define( 'EMAIL_SUBSCRIBERS_DIR', WP_PLUGIN_DIR . '/email-subscribers' );
		}

		if ( ! defined( 'EMAIL_SUBSCRIBERS_URL' ) ) {
			define( 'EMAIL_SUBSCRIBERS_URL', WP_PLUGIN_URL . '/email-subscribers' );
		}

		if ( ! defined( 'ES_EMAILLIST_TABLE' ) ) {
			define( 'ES_EMAILLIST_TABLE', $wpdb->prefix . 'es_emaillist' );
		}

		if ( ! defined( 'EMAIL_LIST_TABLE' ) ) {
			define( 'EMAIL_LIST_TABLE', $wpdb->prefix . 'es_lists' );
		}
		if ( ! defined( 'EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE' ) ) {
			define( 'EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE', $wpdb->prefix . 'es_notification' );
		}

		if ( ! defined( 'EMAIL_SUBSCRIBERS_STATS_TABLE' ) ) {
			define( 'EMAIL_SUBSCRIBERS_STATS_TABLE', $wpdb->prefix . 'es_deliverreport' );
		}
		if ( ! defined( 'EMAIL_SUBSCRIBERS_SENT_TABLE' ) ) {
			define( 'EMAIL_SUBSCRIBERS_SENT_TABLE', $wpdb->prefix . 'es_sentdetails' );
		}

		if ( ! defined( 'EMAIL_TEMPLATES_TABLE' ) ) {
			define( 'EMAIL_TEMPLATES_TABLE', $wpdb->prefix . 'es_templates' );
		}
		if ( ! defined( 'EMAIL_SUBSCRIBERS_ADVANCED_FORM' ) ) {
			define( 'EMAIL_SUBSCRIBERS_ADVANCED_FORM', $wpdb->prefix . 'es_advanced_form' );
		}
		if ( ! defined( 'EMAIL_SUBSCRIBERS_LIST_MAX' ) ) {
			define( 'EMAIL_SUBSCRIBERS_LIST_MAX', 40 );
		}
		if ( ! defined( 'EMAIL_SUBSCRIBERS_CRON_INTERVAL' ) ) {
			define( 'EMAIL_SUBSCRIBERS_CRON_INTERVAL', 300 );
		}
		if ( ! defined( 'IG_CAMPAIGNS_TABLE' ) ) {
			define( 'IG_CAMPAIGNS_TABLE', $wpdb->prefix . 'ig_campaigns' );
		}
		if ( ! defined( 'IG_CONTACTS_TABLE' ) ) {
			define( 'IG_CONTACTS_TABLE', $wpdb->prefix . 'ig_contacts' );
		}
		if ( ! defined( 'IG_CONTACTS_IPS_TABLE' ) ) {
			define( 'IG_CONTACTS_IPS_TABLE', $wpdb->prefix . 'ig_contacts_ips' );
		}
		if ( ! defined( 'IG_FORMS_TABLE' ) ) {
			define( 'IG_FORMS_TABLE', $wpdb->prefix . 'ig_forms' );
		}
		if ( ! defined( 'IG_LISTS_TABLE' ) ) {
			define( 'IG_LISTS_TABLE', $wpdb->prefix . 'ig_lists' );
		}
		if ( ! defined( 'IG_LISTS_CONTACTS_TABLE' ) ) {
			define( 'IG_LISTS_CONTACTS_TABLE', $wpdb->prefix . 'ig_lists_contacts' );
		}
		if ( ! defined( 'IG_MAILING_QUEUE_TABLE' ) ) {
			define( 'IG_MAILING_QUEUE_TABLE', $wpdb->prefix . 'ig_mailing_queue' );
		}
		if ( ! defined( 'IG_SENDING_QUEUE_TABLE' ) ) {
			define( 'IG_SENDING_QUEUE_TABLE', $wpdb->prefix . 'ig_sending_queue' );
		}
		if ( ! defined( 'IG_BLOCKED_EMAILS_TABLE' ) ) {
			define( 'IG_BLOCKED_EMAILS_TABLE', $wpdb->prefix . 'ig_blocked_emails' );
		}
		if ( ! defined( 'IG_ACTIONS_TABLE' ) ) {
			define( 'IG_ACTIONS_TABLE', $wpdb->prefix . 'ig_actions' );
		}
		if ( ! defined( 'IG_LINKS_TABLE' ) ) {
			define( 'IG_LINKS_TABLE', $wpdb->prefix . 'ig_links' );
		}

		if ( ! defined( 'IG_CONTACT_META_TABLE' ) ) {
			define( 'IG_CONTACT_META_TABLE', $wpdb->prefix . 'ig_contactmeta' );
		}

		if ( ! defined( 'IG_QUEUE_TABLE' ) ) {
			define( 'IG_QUEUE_TABLE', $wpdb->prefix . 'ig_queue' );
		}

		if ( ! defined( 'IG_EMAIL_STATUS_IN_QUEUE' ) ) {
			define( 'IG_EMAIL_STATUS_IN_QUEUE', 'in_queue' );
		}
		if ( ! defined( 'IG_EMAIL_STATUS_SENDING' ) ) {
			define( 'IG_EMAIL_STATUS_SENDING', 'sending' );
		}
		if ( ! defined( 'IG_EMAIL_STATUS_SENT' ) ) {
			define( 'IG_EMAIL_STATUS_SENT', 'sent' );
		}
		if ( ! defined( 'IG_SINGLE_OPTIN' ) ) {
			define( 'IG_SINGLE_OPTIN', 1 );
		}
		if ( ! defined( 'IG_DOUBLE_OPTIN' ) ) {
			define( 'IG_DOUBLE_OPTIN', 2 );
		}
		if ( ! defined( 'IG_CAMPAIGN_TYPE_POST_NOTIFICATION' ) ) {
			define( 'IG_CAMPAIGN_TYPE_POST_NOTIFICATION', 'post_notification' );
		}
		if ( ! defined( 'IG_CAMPAIGN_TYPE_NEWSLETTER' ) ) {
			define( 'IG_CAMPAIGN_TYPE_NEWSLETTER', 'newsletter' );
		}
		if ( ! defined( 'IG_CAMPAIGN_TYPE_POST_DIGEST' ) ) {
			define( 'IG_CAMPAIGN_TYPE_POST_DIGEST', 'post_digest' );
		}
		if ( ! defined( 'IG_CAMPAIGN_TYPE_SEQUENCE' ) ) {
			define( 'IG_CAMPAIGN_TYPE_SEQUENCE', 'sequence' );
		}

		if ( ! defined( 'IG_CAMPAIGN_TYPE_SEQUENCE_MESSAGE' ) ) {
			define( 'IG_CAMPAIGN_TYPE_SEQUENCE_MESSAGE', 'sequence_message' );
		}

		if ( ! defined( 'IG_DEFAULT_BATCH_SIZE' ) ) {
			define( 'IG_DEFAULT_BATCH_SIZE', 100 );
		}
		if ( ! defined( 'IG_MAX_MEMORY_LIMIT' ) ) {
			define( 'IG_MAX_MEMORY_LIMIT', '-1' );
		}
		if ( ! defined( 'IG_SET_TIME_LIMIT' ) ) {
			define( 'IG_SET_TIME_LIMIT', 0 );
		}
		if ( ! defined( 'IG_DEFAULT_LIST' ) ) {
			define( 'IG_DEFAULT_LIST', 'Test' );
		}
		if ( ! defined( 'IG_MAIN_LIST' ) ) {
			define( 'IG_MAIN_LIST', 'Main' );
		}
		if ( ! defined( 'IG_CONTACT_SUBSCRIBE' ) ) {
			define( 'IG_CONTACT_SUBSCRIBE', 1 );
		}
		if ( ! defined( 'IG_MESSAGE_SENT' ) ) {
			define( 'IG_MESSAGE_SENT', 2 );
		}
		if ( ! defined( 'IG_MESSAGE_OPEN' ) ) {
			define( 'IG_MESSAGE_OPEN', 3 );
		}
		if ( ! defined( 'IG_LINK_CLICK' ) ) {
			define( 'IG_LINK_CLICK', 4 );
		}
		if ( ! defined( 'IG_CONTACT_UNSUBSCRIBE' ) ) {
			define( 'IG_CONTACT_UNSUBSCRIBE', 5 );
		}
		if ( ! defined( 'IG_MESSAGE_SOFT_BOUNCE' ) ) {
			define( 'IG_MESSAGE_SOFT_BOUNCE', 6 );
		}
		if ( ! defined( 'IG_MESSAGE_HARD_BOUNCE' ) ) {
			define( 'IG_MESSAGE_HARD_BOUNCE', 7 );
		}
		if ( ! defined( 'IG_MESSAGE_ERROR' ) ) {
			define( 'IG_MESSAGE_ERROR', 8 );
		}
	}

	/**
	 * Define Constant
	 *
	 * @param $constant
	 * @param $value
	 *
	 * @since 4.2.0
	 */
	function define( $constant, $value ) {
		if ( ! defined( $constant ) ) {
			define( $constant, $value );
		}
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Email_Subscribers_Loader. Orchestrates the hooks of the plugin.
	 * - Email_Subscribers_Admin. Defines all hooks for the admin area.
	 * - Email_Subscribers_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    4.0
	 * @access   private
	 */
	private function load_dependencies() {

		// Loader
		require_once ES_PLUGIN_DIR . 'includes/class-email-subscribers-loader.php';

		// Language
		require_once ES_PLUGIN_DIR . 'includes/class-email-subscribers-i18n.php';

		// Logs
		require_once ES_PLUGIN_DIR . 'includes/logs/class-ig-logger-interface.php';
		require_once ES_PLUGIN_DIR . 'includes/logs/class-ig-log-handler-interface.php';
		require_once ES_PLUGIN_DIR . 'includes/logs/class-ig-log-handler.php';
		require_once ES_PLUGIN_DIR . 'includes/logs/log-handlers/class-ig-log-handler-file.php';
		require_once ES_PLUGIN_DIR . 'includes/logs/class-ig-log-levels.php';
		require_once ES_PLUGIN_DIR . 'includes/class-ig-logger.php';

		// Admin Notices
		require_once ES_PLUGIN_DIR . 'includes/notices/class-es-admin-notices.php';

		// DB Classes
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-queue.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-mailing-queue.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-lists.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-contacts.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-lists-contacts.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-sending-queue.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-notifications.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-campaigns.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-forms.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-blocked-emails.php';
		require_once ES_PLUGIN_DIR . 'includes/db/class-es-db-actions.php';

		// Mailers
		require_once ES_PLUGIN_DIR . 'includes/class-es-mailer.php';
		require_once ES_PLUGIN_DIR . 'includes/mailers/class-es-base-mailer.php';
		require_once ES_PLUGIN_DIR . 'includes/mailers/class-es-pepipost-mailer.php';


		require_once ES_PLUGIN_DIR . 'includes/class-es-common.php';

		// Classes
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-lists-table.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-contacts-table.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-post-notifications.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-templates-table.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-campaigns-table.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-reports-table.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-forms-table.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-queue.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-cron.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-newsletters.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-tools.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-admin-settings.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-widget.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-old-widget.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-form-widget.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-export-subscribers.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-import-subscribers.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-info.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-handle-post-notification.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-handle-subscription.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-handle-sync-wp-user.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-subscription-throttaling.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-actions.php';
		require_once ES_PLUGIN_DIR . 'includes/admin/class-es-tracking.php';

		// Upgrade functions
		require_once ES_PLUGIN_DIR . 'includes/upgrade/es-update-functions.php';

		// Core Function
		require_once ES_PLUGIN_DIR . 'includes/es-core-functions.php';

		// Install Class
		require_once ES_PLUGIN_DIR . 'includes/class-es-install.php';

		// Public Class
		require_once ES_PLUGIN_DIR . 'public/class-email-subscribers-public.php';

		// Partials
		require_once ES_PLUGIN_DIR . 'admin/partials/admin-header.php';
		require_once ES_PLUGIN_DIR . 'public/partials/class-es-shortcode.php';

		// Backward Compatibility
		require_once ES_PLUGIN_DIR . 'includes/es-backward.php';

		// Admin Main Class
		require_once ES_PLUGIN_DIR . 'admin/class-email-subscribers-admin.php';

		// Pro Features indicator
		require_once ES_PLUGIN_DIR . 'includes/pro-features.php';

		// Feedback Class
		require_once ES_PLUGIN_DIR . 'includes/feedback/class-ig-tracker.php';
		require_once ES_PLUGIN_DIR . 'includes/feedback/class-ig-feedback.php';
		require_once ES_PLUGIN_DIR . 'includes/feedback.php';

		add_shortcode( 'email-subscribers', array( 'ES_Shortcode', 'render_es_subscription_shortcode' ) );
		add_shortcode( 'email-subscribers-advanced-form', array( 'ES_Shortcode', 'render_es_advanced_form' ) );
		add_shortcode( 'email-subscribers-form', array( 'ES_Shortcode', 'render_es_form' ) );

		$this->loader = new Email_Subscribers_Loader();

	}

	/**
	 * Set Localization.
	 *
	 * @since   1.0.0
	 */
	private function set_locale() {

		$plugin_i18n = new Email_Subscribers_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    4.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Email_Subscribers_Admin( $this->get_email_subscribers(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'plugins_loaded' );

		$this->loader->add_filter( 'ig_es_lite_do_send', $plugin_admin, 'do_send', 10, 2 );
		$this->loader->add_filter( 'set-screen-option', $plugin_admin, 'save_screen_options', 20, 3 );

		$this->loader->add_action( 'wp_ajax_count_contacts_by_list', $plugin_admin, 'count_contacts_by_list' );

		//$this->loader->add_filter( 'ig_es_blocked_domains', $plugin_admin, 'blocked_domains', 10, 1 );
		//$this->loader->add_filter( 'ig_es_whitelist_ips', $plugin_admin, 'whitelist_ips', 10, 1 );
		//$this->loader->add_filter( 'ig_es_blacklist_ips', $plugin_admin, 'blacklist_ips', 10, 1 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    4.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Email_Subscribers_Public( $this->get_email_subscribers(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'es_email_subscribe_init' );
		$this->loader->add_action( 'wp_loaded', $plugin_public, 'es_email_subscribe_wp_loaded' );
		$this->loader->add_action( 'ig_es_add_contact', $plugin_public, 'add_contact', 10, 2 );
		$this->loader->add_action( 'ig_es_confirm_unsubscription', $plugin_public, 'confirm_unsubscription', 10, 2 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    4.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     4.0
	 */
	public function get_email_subscribers() {
		return $this->email_subscribers;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Email_Subscribers_Loader    Orchestrates the hooks of the plugin.
	 * @since     4.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     4.0
	 */
	public function get_version() {
		return $this->version;
	}

	public static function get_redirect( $url ) {
		header( "Location: " . $url );
		exit;
	}

	public function register_es_widget() {
		//register_widget( 'ES_Old_Widget' );
		//register_widget( 'ES_Widget' );
		register_widget( 'ES_Form_Widget' );
	}

	public static function insert_widget_in_sidebar( $widget_id, $widget_data, $sidebar ) {
		// Retrieve sidebars, widgets and their instances
		$sidebars_widgets = get_option( 'sidebars_widgets', array() );
		$widget_instances = get_option( 'widget_' . $widget_id, array() );
		// Retrieve the key of the next widget instance
		$numeric_keys = array_filter( array_keys( $widget_instances ), 'is_int' );
		$next_key     = $numeric_keys ? max( $numeric_keys ) + 1 : 2;
		// Add this widget to the sidebar
		if ( ! isset( $sidebars_widgets[ $sidebar ] ) ) {
			$sidebars_widgets[ $sidebar ] = array();
		}
		$sidebars_widgets[ $sidebar ][] = $widget_id . '-' . $next_key;
		// Add the new widget instance
		$widget_instances[ $next_key ] = $widget_data;
		// Store updated sidebars, widgets and their instances
		update_option( 'sidebars_widgets', $sidebars_widgets );
		update_option( 'widget_' . $widget_id, $widget_instances );
	}

	function es_add_cron_interval( $schedules ) {
		$schedules['ig_es_fifteen_mins_interval'] = array(
			'interval' => 900,
			'display'  => esc_html__( 'Every Fifteen Minutes' ),
		);

		return $schedules;
	}

	/**
	 * Return a true instance of a class
	 *
	 * @return Email_Subscribers
	 *
	 * @since 4.2.1
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Email_Subscribers ) ) {
			global $ig_es_feedback, $ig_es_tracker;

			self::$instance = new Email_Subscribers();

			require_once plugin_dir_path( __FILE__ ) . 'class-email-subscribers-activator.php';
			require_once plugin_dir_path( __FILE__ ) . 'class-email-subscribers-deactivator.php';

			self::$instance->email_subscribers = 'email-subscribers';

			self::$instance->define_constants();
			self::$instance->load_dependencies();
			self::$instance->set_locale();
			self::$instance->define_admin_hooks();
			self::$instance->define_public_hooks();

			add_action( 'widgets_init', array( self::$instance, 'register_es_widget' ) );
			add_filter( 'cron_schedules', array( self::$instance, 'es_add_cron_interval' ) );

			self::$instance->queue_db          = new ES_DB_Queue();
			self::$instance->actions_db        = new ES_DB_Actions();
			self::$instance->campaigns_db      = new ES_DB_Campaigns();
			self::$instance->lists_db          = new ES_DB_Lists();
			self::$instance->forms_db          = new ES_DB_Forms();
			self::$instance->contacts_db       = new ES_DB_Contacts();
			self::$instance->blocked_emails_db = new ES_DB_Blocked_Emails();
			self::$instance->queue             = new ES_Queue();
			self::$instance->actions           = new ES_Actions();

			self::$instance->logger = get_ig_logger();

			$ig_es_tracker = 'IG_Tracker_V_' . str_replace( '.', '_', IG_ES_FEEDBACK_VERSION );
			if ( is_admin() ) {
				$ig_es_feedback_class = 'IG_Feedback_V_' . str_replace( '.', '_', IG_ES_FEEDBACK_VERSION );
				$ig_es_feedback       = new $ig_es_feedback_class( 'Email Subscribers', 'email-subscribers', 'ig_es', 'esfree.', false );
				$ig_es_feedback->render_deactivate_feedback();
			}

			add_action( 'admin_notices', array( self::$instance, 'add_admin_notice' ) );
			add_action( 'admin_init', array( self::$instance, 'es_dismiss_admin_notice' ) );

			if ( ! post_type_exists( 'es_template' ) ) {
				add_action( 'init', array( 'Email_Subscribers_Activator', 'register_email_templates' ) );
			}

		}

		return self::$instance;
	}
}