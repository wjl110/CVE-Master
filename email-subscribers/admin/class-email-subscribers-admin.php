<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/admin
 * @author     Your Name <email@example.com>
 */
class Email_Subscribers_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    4.0
	 * @access   private
	 * @var      string $email_subscribers The ID of this plugin.
	 */
	private $email_subscribers;

	/**
	 * The version of this plugin.
	 *
	 * @since    4.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $email_subscribers The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    4.0
	 *
	 */
	public function __construct( $email_subscribers, $version ) {

		$this->email_subscribers = $email_subscribers;
		$this->version           = $version;

		// Reorder ES Submenu
		add_filter( 'custom_menu_order', array( $this, 'submenu_order' ) );

		add_action( 'admin_menu', array( $this, 'email_subscribers_admin_menu' ) );
		add_action( 'wp_ajax_es_klawoo_subscribe', array( $this, 'klawoo_subscribe' ) );
		add_action( 'admin_footer', array( $this, 'remove_submenu' ) );
		add_action( 'wp_ajax_send_test_email', array( $this, 'send_test_email' ) );
		add_action( 'admin_init', array( $this, 'es_save_onboarding_skip' ) );

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    4.0
	 */
	public function enqueue_styles() {
		$screen             = get_current_screen();
		$screen_id          = $screen ? $screen->id : '';
		$enqueue_on_screens = array(
			'toplevel_page_es_dashboard',
			'email-subscribers_page_es_subscribers',
			'email-subscribers_page_es_lists',
			'email-subscribers_page_es_forms',
			'email-subscribers_page_es_campaigns',
			'email-subscribers_page_es_newsletters',
			'email-subscribers_page_es_notifications',
			'edit-es_template',
			'email-subscribers_page_es_reports',
			'email-subscribers_page_es_tools',
			'email-subscribers_page_es_settings',
			'email-subscribers_page_es_general_information',
			'email-subscribers_page_es_pricing',
			'es_template',
		);
		//all admin notice
		if ( ! in_array( $screen_id, $enqueue_on_screens, true ) ) {
			return;
		}
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Email_Subscribers_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Email_Subscribers_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->email_subscribers, plugin_dir_url( __FILE__ ) . 'css/email-subscribers-admin.css', array(), $this->version, 'all' );

		$get_page = ig_es_get_request_data( 'page' );

		if ( ! empty( $get_page ) && 'es_settings' === $get_page ) {
			// wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'email-jquery-ui', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    4.0
	 */
	public function enqueue_scripts() {
		$screen             = get_current_screen();
		$screen_id          = $screen ? $screen->id : '';
		$enqueue_on_screens = array(
			'toplevel_page_es_dashboard',
			'email-subscribers_page_es_subscribers',
			'email-subscribers_page_es_lists',
			'email-subscribers_page_es_forms',
			'email-subscribers_page_es_campaigns',
			'email-subscribers_page_es_newsletters',
			'email-subscribers_page_es_notifications',
			'edit-es_template',
			'email-subscribers_page_es_reports',
			'email-subscribers_page_es_tools',
			'email-subscribers_page_es_settings',
			'email-subscribers_page_es_general_information',
			'email-subscribers_page_es_pricing',
		);
		//all admin notice
		if ( ! in_array( $screen_id, $enqueue_on_screens, true ) ) {
			return;
		}
		wp_enqueue_script( $this->email_subscribers, plugin_dir_url( __FILE__ ) . 'js/email-subscribers-admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs' ), $this->version, false );
		wp_enqueue_script( 'custom', plugin_dir_url( __FILE__ ) . 'js/es-onboarding.js', array( 'jquery' ), $this->version, false );
	}

	public function remove_submenu() {
		//remove submenues
		?>
        <script type="text/javascript">
			jQuery(document).ready(function () {
				var removeSubmenu = ['ig-es-broadcast', 'ig-es-lists', 'ig-es-post-notifications', 'ig-es-sequence'];
				jQuery.each(removeSubmenu, function (key, id) {
					jQuery("#" + id).parent('a').parent('li').hide();
				});
			})
        </script>
		<?php
	}

	public function email_subscribers_admin_menu() {
		// This adds the main menu page
		add_menu_page( __( 'Email Subscribers', 'email-subscribers' ), __( 'Email Subscribers', 'email-subscribers' ), 'edit_posts', 'es_dashboard', array( $this, 'es_dashboard_callback' ), 'dashicons-email', 30 );

		// Submenu
		add_submenu_page( 'es_dashboard', __( 'Dashboard', 'email-subscribers' ), __( 'Dashboard', 'email-subscribers' ), 'edit_posts', 'es_dashboard', array( $this, 'es_dashboard_callback' ) );

		// Add Campaigns Submenu
		$hook = add_submenu_page( 'es_dashboard', __( 'Campaigns', 'email-subscribers' ), __( 'Campaigns', 'email-subscribers' ), 'edit_posts', 'es_campaigns', array( $this, 'render_campaigns' ) );
		add_action( "load-$hook", array( 'ES_Campaigns_Table', 'screen_options' ) );

		// Add Forms Submenu
		$hook = add_submenu_page( 'es_dashboard', __( 'Forms', 'email-subscribers' ), __( 'Forms', 'email-subscribers' ), 'edit_posts', 'es_forms', array( $this, 'render_forms' ) );
		add_action( "load-$hook", array( 'ES_Forms_Table', 'screen_options' ) );

		// Add Contacts Submenu
		$hook = add_submenu_page( 'es_dashboard', __( 'Audience', 'email-subscribers' ), __( 'Audience', 'email-subscribers' ), 'edit_posts', 'es_subscribers', array( $this, 'render_contacts' ) );
		add_action( "load-$hook", array( 'ES_Contacts_Table', 'screen_options' ) );

		// Add Lists Submenu
		$hook = add_submenu_page( 'es_dashboard', __( 'Lists', 'email-subscribers' ), '<span id="ig-es-lists">' . __( 'Lists', 'email-subscribers' ) . '</span>', 'edit_posts', 'es_lists', array( $this, 'render_lists' ) );
		add_action( "load-$hook", array( 'ES_Lists_Table', 'screen_options' ) );

		add_submenu_page( 'es_dashboard', __( 'Post Notifications', 'email-subscribers' ), '<span id="ig-es-post-notifications">' . __( 'Post Notifications', 'email-subscribers' ) . '</span>', 'edit_posts', 'es_notifications', array( $this, 'load_post_notifications' ) );
		add_submenu_page( 'es_dashboard', __( 'Broadcast', 'email-subscribers' ), '<span id="ig-es-broadcast">' . __( 'Broadcast', 'email-subscribers' ) . '</span>', 'edit_posts', 'es_newsletters', array( $this, 'load_newsletters' ) );
		add_submenu_page( 'es_dashboard', __( 'Reports', 'email-subscribers' ), __( 'Reports', 'email-subscribers' ), 'edit_posts', 'es_reports', array( $this, 'load_reports' ) );
		add_submenu_page( 'es_dashboard', __( 'Settings', 'email-subscribers' ), __( 'Settings', 'email-subscribers' ), 'edit_posts', 'es_settings', array( $this, 'load_settings' ) );
		add_submenu_page( null, __( 'Template Preview', 'email-subscribers' ), __( 'Template Preview', 'email-subscribers' ), 'edit_posts', 'es_template_preview', array( $this, 'load_preview' ) );
	}

	public function plugins_loaded() {
		ES_Templates_Table::get_instance();
		new Export_Subscribers();
		new ES_Handle_Post_Notification();
		ES_Handle_Sync_Wp_User::get_instance();
		new ES_Import_Subscribers();
		ES_Info::get_instance();
		ES_Newsletters::get_instance();
		ES_Tools::get_instance();
		new ES_Tracking();
	}

	// Function for Klawoo's Subscribe form on Help & Info page
	public static function klawoo_subscribe() {
		$url = 'http://app.klawoo.com/subscribe';

		$form_source = ig_es_get_request_data( 'from_source' );
		if ( ! empty( $form_source ) ) {
			update_option( 'ig_es_onboarding_status', $form_source );
		}

		if ( ! empty( $_POST ) ) {
			$params = ig_es_get_post_data();
		} else {
			exit();
		}
		$method = 'POST';
		$qs     = http_build_query( $params );

		$options = array(
			'timeout' => 15,
			'method'  => $method
		);

		if ( $method == 'POST' ) {
			$options['body'] = $qs;
		} else {
			if ( strpos( $url, '?' ) !== false ) {
				$url .= '&' . $qs;
			} else {
				$url .= '?' . $qs;
			}
		}

		$response = wp_remote_request( $url, $options );

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$data = $response['body'];
			if ( $data != 'error' ) {

				$message_start = substr( $data, strpos( $data, '<body>' ) + 6 );
				$remove        = substr( $message_start, strpos( $message_start, '</body>' ) );
				$message       = trim( str_replace( $remove, '', $message_start ) );
				echo( $message );
				exit();
			}
		}
		exit();
	}

	/**
	 * Render Campaigns Screen
	 *
	 * @since 4.2.1
	 */
	public function render_campaigns() {
		$campaigns = new ES_Campaigns_Table();
		$campaigns->render();
	}

	/**
	 * Render Contacts Screen
	 *
	 * @since 4.2.1
	 */
	public function render_contacts() {
		$campaigns = new ES_Contacts_Table();
		$campaigns->render();
	}

	/**
	 * Render Forms Screen
	 *
	 * @since 4.2.1
	 */
	public function render_forms() {
		$campaigns = new ES_Forms_Table();
		$campaigns->render();
	}

	/**
	 * Render Lists Screen
	 *
	 * @since 4.2.1
	 */
	public function render_lists() {
		$campaigns = new ES_Lists_Table();
		$campaigns->render();
	}

	public function load_post_notifications() {
		$post_notifications = ES_Post_Notifications_Table::get_instance();
		$post_notifications->es_notifications_callback();
	}

	public function load_newsletters() {
		$newsletters = ES_Newsletters::get_instance();
		$newsletters->es_newsletters_settings_callback();
	}

	public function load_reports() {
		$reports = ES_Reports_Table::get_instance();
		$reports->es_reports_callback();
	}

	public function load_settings() {
		$settings = ES_Admin_Settings::get_instance();
		$settings->es_settings_callback();
	}

	public function load_preview() {
		$preview = ES_Templates_Table::get_instance();
		$preview->es_template_preview_callback();
	}

	public function do_send( $response, $data ) {
		global $phpmailer;

		if ( ! is_object( $phpmailer ) || ! is_a( $phpmailer, 'PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$phpmailer = new PHPMailer( true );
		}

		$to_email       = $data['to_email'];
		$subject        = $data['subject'];
		$email_template = $data['email_template'];
		$headers        = $data['headers'];

		$result                = array( 'status' => 'SUCCESS' );
		$ig_es_mailer_settings = get_option( 'ig_es_mailer_settings' );
		$mailer                = $ig_es_mailer_settings['mailer'];

		$mailer_classname     = 'ES_' . ucfirst( $mailer ) . '_Mailer';
		$is_mailer_file_exist = ( class_exists( $mailer_classname ) ) ? true : false;
		if ( $mailer != 'wpmail' && $is_mailer_file_exist ) {
			$mailer_instance = new $mailer_classname();
			$send_mail       = $mailer_instance->send( $response, $data );
		} else {
			$send_mail = wp_mail( $to_email, $subject, $email_template, $headers );
		}

		if ( ! $send_mail || $send_mail['status'] === 'ERROR' ) {

			$result = array(
				'status'  => 'ERROR',
				'message' => ! empty( $send_mail['message'] ) ? $send_mail['message'] : wp_strip_all_tags( $phpmailer->ErrorInfo )
			);

		}

		return $result;
	}

	function submenu_order( $menu_order ) {
		global $submenu;

		$es_menus = isset( $submenu['es_dashboard'] ) ? $submenu['es_dashboard'] : array();

		if ( ! empty( $es_menus ) ) {

			$es_menu_order = array(
				'es_dashboard',
				'es_subscribers',
				'es_lists',
				'es_forms',
				'es_campaigns',
				'edit.php?post_type=es_template',
				'es_notifications',
				'es_newsletters',
				'es_sequence',
				'es_integrations',
				'es_reports',
				'es_tools',
				'es_settings',
				'es_general_information',
				'es_pricing',
			);

			$order = array_flip( $es_menu_order );

			$reorder_es_menu = array();
			foreach ( $es_menus as $menu ) {
				$reorder_es_menu[ $order[ $menu[2] ] ] = $menu;
			}

			ksort( $reorder_es_menu );

			$submenu['es_dashboard'] = $reorder_es_menu;

		}

		# Return the new submenu order
		return $menu_order;
	}

	public function es_dashboard_callback() {
		$es_plugin_data           = get_plugin_data( plugin_dir_path( __DIR__ ) . 'email-subscribers.php' );
		$es_current_version       = $es_plugin_data['Version'];
		$admin_email              = get_option( 'admin_email' );
		$ig_es_db_update_history  = ES_Common::get_ig_option( 'db_update_history', array() );
		$ig_es_4015_db_updated_at = ( is_array( $ig_es_db_update_history ) && isset( $ig_es_db_update_history['4.0.15'] ) ) ? $ig_es_db_update_history['4.0.15'] : false;

		$is_sa_option_exists = get_option( 'current_sa_email_subscribers_db_version', false );
		$onboarding_status   = get_option( 'ig_es_onboarding_complete', 'no' );
		if ( ! $is_sa_option_exists && ! $ig_es_4015_db_updated_at && 'yes' !== $onboarding_status ) {
			include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/onboarding.php';
		} else {
			include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/dashboard.php';
		}

	}

	public static function es_feedback() {
		$star_rating_dismiss = get_option( 'ig_es_dismiss_star_notice', 'no' );
		$star_rating_done    = get_option( 'ig_es_star_notice_done', 'no' );
		// Show if - more than 2 post notifications or Newsletters sent OR more than 10 subscribers
		$total_contacts   = ES_DB_Contacts::count_active_subscribers_by_list_id();
		$total_email_sent = ES_DB_Mailing_Queue::get_notifications_count();

		if ( ( $total_contacts >= 10 || $total_email_sent > 2 ) && 'yes' !== $star_rating_dismiss && 'yes' !== $star_rating_done ) {
			echo '<div class="notice notice-warning" style="background-color: #FFF;"><p style="letter-spacing: 0.6px;">If you like <strong>Email Subscribers</strong>, please consider leaving us a <a target="_blank" href="?es_dismiss_admin_notice=1&option_name=star_notice_done"><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span><span>&#9733;</span></a> rating. A huge thank you from Icegram in advance! <a style="float:right" class="es-admin-btn es-admin-btn-secondary" href="?es_dismiss_admin_notice=1&option_name=dismiss_star_notice">No, I don\'t like it</a></p></div>';
		}
	}


	function send_test_email() {
		$message = array();
		$message = array(
			'status'  => 'ERROR',
			'message' => __( 'Something went wrong', 'email-subscribers' )
		);

		$emails = ig_es_get_request_data( 'emails', array() );
		if ( is_array( $emails ) && count( $emails ) > 0 ) {
			$default_list = ES()->lists_db->get_list_by_name( IG_DEFAULT_LIST );
			$list_id      = $default_list['id'];
			//add to the default list
			foreach ( $emails as $email ) {
				$data       = array(
					'first_name'   => ES_Common::get_name_from_email( $email ),
					'email'        => $email,
					'source'       => 'admin',
					'form_id'      => 0,
					'status'       => 'verified',
					'unsubscribed' => 0,
					'hash'         => ES_Common::generate_guid(),
					'created_at'   => ig_get_current_date_time()
				);
				$contact_id = ES_DB_Contacts::add_subscriber( $data );
				if ( $contact_id ) {
					$data = array(
						'list_id'       => array( $list_id ),
						'contact_id'    => $contact_id,
						'status'        => 'subscribed',
						'optin_type'    => IG_SINGLE_OPTIN,
						'subscribed_at' => ig_get_current_date_time(),
						'subscribed_ip' => null
					);

					ES_DB_Lists_Contacts::add_lists_contacts( $data );
				}
			}
			$res = ES_Install::create_and_send_default_broadcast();
			$res = ES_Install::create_and_send_default_post_notification();
			if ( $res['status'] === 'SUCCESS' ) {
				update_option( 'ig_es_onboarding_test_campaign_success', 'yes' );
			} else {
				update_option( 'ig_es_onboarding_test_campaign_error', 'yes' );
			}
			update_option( 'ig_es_onboarding_complete', 'yes' );
			$res['dashboard_url'] = admin_url( 'admin.php?page=es_dashboard' );
			echo json_encode( $res );
			exit;

		}
	}

	//save skip signup option
	function es_save_onboarding_skip() {

		$es_skip     = ig_es_get_request_data( 'es_skip' );
		$option_name = ig_es_get_request_data( 'option_name' );

		if ( $es_skip == '1' && ! empty( $option_name ) ) {
			update_option( 'ig_es_ob_skip_' . $option_name, 'yes' );
			$referer = wp_get_referer();
			wp_safe_redirect( $referer );
			exit();
		}
	}

	public function count_contacts_by_list() {

		$list_id = (int) ig_es_get_request_data( 'list_id', 0 );
		$status  = ig_es_get_request_data( 'status', 'all' );

		if ( $list_id == 0 ) {
			return 0;
		}

		$total_count = ES_DB_Lists_Contacts::get_total_count_by_list( $list_id, $status );

		die( json_encode( array( 'total' => $total_count ) ) );
	}

	/**
	 * Hooked to 'set-screen-options' filter
	 *
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return mixed
	 *
	 * @since 4.2.1
	 */
	public function save_screen_options( $status, $option, $value ) {

		$ig_es_options = array(
			'es_campaigns_per_page',
			'es_contacts_per_page',
			'es_lists_per_page',
			'es_forms_per_page'
		);

		if ( in_array( $option, $ig_es_options ) ) {
			return $value;
		}

		return $status;
	}

}
