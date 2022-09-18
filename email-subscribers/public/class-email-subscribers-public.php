<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      4.0
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Email_Subscribers
 * @subpackage Email_Subscribers/public
 * @author     Your Name <email@example.com>
 */
class Email_Subscribers_Public {

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
	 * @param string $email_subscribers The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    4.0
	 *
	 */
	public function __construct( $email_subscribers, $version ) {

		$this->email_subscribers = $email_subscribers;
		$this->version           = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    4.0
	 */
	public function enqueue_styles() {

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
		wp_enqueue_style( $this->email_subscribers, plugin_dir_url( __FILE__ ) . 'css/email-subscribers-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    4.0
	 */
	public function enqueue_scripts() {

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

		wp_enqueue_script( $this->email_subscribers, plugin_dir_url( __FILE__ ) . 'js/email-subscribers-public.js', array( 'jquery' ), $this->version, false );

		$es_data = array(

			'messages' => array(
				'es_empty_email_notice'           => __( 'Please enter email address', 'email-subscribers' ),
				'es_rate_limit_notice'            => __( 'You need to wait for sometime before subscribing again', 'email-subscribers' ),
				'es_single_optin_success_message' => __( 'Successfully Subscribed.', 'email-subscribers' ),
				// 'es_double_optin_success_message' => __( 'Your subscription was successful! Kindly check your mailbox and confirm your subscription. If you don\'t see the email within a few minutes, check the spam/junk folder.', 'email-subscribers' ),
				'es_email_exists_notice'          => __( 'Email Address already exists!', 'email-subscribers' ),
				'es_unexpected_error_notice'      => __( 'Oops.. Unexpected error occurred.', 'email-subscribers' ),
				'es_invalid_email_notice'         => __( 'Invalid email address', 'email-subscribers' ),
				'es_try_later_notice'             => __( 'Please try after some time', 'email-subscribers' )
			),

			'es_ajax_url' => admin_url( 'admin-ajax.php' ),

		);

		wp_localize_script( $this->email_subscribers, 'es_data', $es_data );


	}

	public function es_email_subscribe_wp_loaded() {
		new ES_Cron();
	}

	public function es_email_subscribe_init() {
		global $wpdb, $ig_es_tracker;
		//initialize 
		// new ES_Cron();
		new ES_Handle_Subscription();
		new ES_Shortcode();

		$option = ig_es_get_request_data( 'es' );
		$hash   = ig_es_get_request_data( 'hash' );

		if ( ! empty( $hash ) ) {
			$data = ig_es_decode_request_data( $hash );

			$db_id       = ! empty( $data['contact_id'] ) ? $data['contact_id'] : 0;
			$email       = ! empty( $data['email'] ) ? $data['email'] : '';
			$guid        = ! empty( $data['guid'] ) ? $data['guid'] : '';
			$message_id  = ! empty( $data['message_id'] ) ? $data['message_id'] : 0;
			$campaign_id = ! empty( $data['campaign_id'] ) ? $data['campaign_id'] : 0;
		} else {
			$db_id      = ig_es_get_request_data( 'db' );
			$email      = ig_es_get_request_data( 'email' );
			$guid       = ig_es_get_request_data( 'guid' );
			$message_id = $campaign_id = 0;
		}

		$email = sanitize_email( $email );
		$email = str_replace( ' ', '+', $email );

		if ( ! empty( $option ) ) {
			if ( ( 'optin' === $option || 'unsubscribe' === $option ) && ! empty( $db_id ) ) {
				//check if contact exist with id and email
				$contacts = ES_DB_Contacts::is_contact_exists( $db_id, $email );

				if ( ! empty( $contacts ) && count( $contacts ) > 0 ) {
					$ids                       = array( $db_id );
					$status                    = $subject = $content = '';
					$unsubscribed              = 0;
					$status                    = ( $option === 'optin' ) ? 'subscribed' : 'unsubscribed';
					$is_status_update_required = ES_DB_Lists_Contacts::is_status_update_required( $ids, $status );
					if ( $is_status_update_required ) {
						if ( $option === 'optin' ) {
							$message = get_option( 'ig_es_subscription_success_message' );
							ES_DB_Contacts::edit_subscriber_status_global( $ids, $unsubscribed );
							ES_DB_Lists_Contacts::edit_subscriber_status( $ids, $status );
							//send welcome email
							$contact = ES_DB_Contacts::get_subscribers_email_name_map( array( $email ) );
							$data    = array(
								'name'       => ! empty( $contact[ $email ] ) ? $contact[ $email ]['name'] : '',
								'first_name' => ! empty( $contact[ $email ] ) ? $contact[ $email ]['first_name'] : '',
								'last_name'  => ! empty( $contact[ $email ] ) ? $contact[ $email ]['last_name'] : '',
								'email'      => $email,
								'db_id'      => $db_id,
								'guid'       => $guid
							);

							$enable_welcome_email = get_option( 'ig_es_enable_welcome_email', 'no' );

							if ( $enable_welcome_email === 'yes' ) {
								$content = ES_Mailer::prepare_welcome_email( $data );
								$subject = ES_Mailer::prepare_welcome_email_subject( $data );
								ES_Mailer::send( $email, $subject, $content );
							}

							$lists     = ES()->lists_db->get_all_lists_name_by_contact( $db_id );
							$list_name = implode( ", ", $lists );

							$data['list_name'] = $list_name;
							ES_Common::send_signup_notification_to_admins( $data );
						} elseif ( $option === 'unsubscribe' ) {
							$unsubscribed = 1;

							$submitted         = ig_es_get_post_data( 'submitted' );
							$unsubscribe_lists = ig_es_get_post_data( 'unsubscribe_lists', array() );
							$list_selected     = ig_es_get_request_data( 'list_selected' );


							$message = get_option( 'ig_es_unsubscribe_success_message' );

							$active_plugins = $ig_es_tracker::get_active_plugins();

							if ( in_array( 'email-subscribers-premium/email-subscribers-premium.php', $active_plugins ) && empty( $submitted ) && empty( $unsubscribe_lists ) && ! $list_selected ) {
								do_action( 'ig_es_update_subscriber', $db_id );
							}

							if ( empty( $unsubscribe_lists ) ) {
								// We don't get any lists to unsubscribe. Which means we have to
								// ask contact for confirmation about unsubscription
								// If we haven't received confirmation about unsubscription,
								// Show confirmation message
								$confirm_unsubscription = ig_es_get_request_data( 'confirm_unsubscription' );
								if ( empty( $submitted ) && ! $confirm_unsubscription ) {
									do_action( 'ig_es_confirm_unsubscription' );
								}

								$unsubscribe_lists = ES_DB_Lists_Contacts::get_list_ids_by_contact( $db_id, 'subscribed' );
							}

							//update list status
							ES_DB_Contacts::edit_list_contact_status( array( $db_id ), $unsubscribe_lists, 'unsubscribed' );
							//check if all list have same status
							$list_ids = ES_DB_Lists_Contacts::get_list_ids_by_contact( $db_id, 'subscribed' );
							if ( count( $list_ids ) == 0 ) {
								//update global
								ES_DB_Contacts::edit_subscriber_status_global( array( $db_id ), 1 );

							}

							do_action( 'ig_es_contact_unsubscribe', $db_id, $message_id, $campaign_id, $unsubscribe_lists );

						}

						do_action( 'es_redirect_to_optin_page', $option );
					} else {
						if ( $status === 'subscribed' ) {
							$message = __( 'You are already subscribed!', 'email-subscribers' );
						} else {
							$message = __( 'You are already unsubscribed!', 'email-subscribers' );
						}
					}

				} else {
					$message = __( 'Sorry, we couldn\'t find you. Please contact admin.', 'email-subscribers' );
				}
				// We are using $message in following file
				include 'partials/subscription-successfull.php';

			} elseif ( in_array( $option, array( 'viewstatus', 'open' ) ) ) {
				if ( ! empty( $guid ) && ! empty( $email ) ) {
					ES_DB_Sending_Queue::update_viewed_status( $guid, $email );

					// Track Message Open
					do_action( 'ig_es_message_open', $db_id, $message_id, $campaign_id );
				}
			}

		}

	}

	public function add_contact( $contact_data, $list_id ) {

		$email = $contact_data['email'];

		$default_data = array(
			'status'     => 'verified',
			'hash'       => ES_Common::generate_guid(),
			'created_at' => ig_get_current_date_time(),
			'wp_user_id' => 0
		);

		$contact_data = wp_parse_args( $contact_data, $default_data );

		$contact = ES_DB_Contacts::is_subscriber_exist_in_list( $email, $list_id );
		if ( empty( $contact['contact_id'] ) ) {
			$contact_id = ES_DB_Contacts::add_subscriber( $contact_data );
		} else {
			$contact_id = $contact['contact_id'];
		}

		if ( empty( $contact['list_id'] ) ) {

			$optin_type        = get_option( 'ig_es_optin_type', true );
			$optin_type        = ( $optin_type === 'double_opt_in' ) ? 2 : 1;
			$list_id           = ! empty( $list_id ) ? $list_id : 1;
			$list_contact_data = array(
				'list_id'       => array( $list_id ),
				'contact_id'    => $contact_id,
				'status'        => 'subscribed',
				'subscribed_at' => ig_get_current_date_time(),
				'optin_type'    => $optin_type,
				'subscribed_ip' => null
			);

			ES_DB_Lists_Contacts::delete_list_contacts( $contact_id, array( $list_id ) );

			$result = ES_DB_Lists_Contacts::add_lists_contacts( $list_contact_data );
		}

	}


	/**
	 * Allow user to select the list from which they want to unsubscribe
	 *
	 * @since 4.2
	 */
	function confirm_unsubscription() {
		global $wp;
		$get    = ig_es_get_request_data();
		$action = home_url( add_query_arg( $get, $wp->request ) );
		$action = add_query_arg( 'confirm_unsubscription', 1, $action );

		?>

		<style type="text/css">
			.ig_es_form_wrapper {
				width: 30%;
				margin: 0 auto;
				border: 2px #e8e3e3 solid;
				padding: 0.9em;
				border-radius: 5px;
			}

			.ig_es_form_heading {
				font-size: 1.3em;
				line-height: 1.5em;
				margin-bottom: 0.5em;
			}

			.ig_es_list_checkbox {
				margin-right: 0.5em;
			}

			.ig_es_submit {
				color: #FFFFFF !important;
				border-color: #03a025 !important;
				background: #03a025 !important;
				box-shadow: 0 1px 0 #03a025;
				font-weight: bold;
				height: 2.4em;
				line-height: 1em;
				cursor: pointer;
				border-width: 1px;
				border-style: solid;
				-webkit-appearance: none;
				border-radius: 3px;
				white-space: nowrap;
				box-sizing: border-box;
				font-size: 1em;
				padding: 0 2em;
				margin-top: 1em;
			}

			.confirmation-no : {
				border-color: #FF0000 !important;
				background: #FF0000 !important;
				box-shadow: 0 1px 0 #FF0000;
			}

			.ig_es_submit:hover {
				color: #FFF !important;
				background: #0AAB2E !important;
				border-color: #0AAB2E !important;
			}

			.ig_es_form_wrapper hr {
				display: block;
				height: 1px;
				border: 0;
				border-top: 1px solid #ccc;
				margin: 1em 0;
				padding: 0;
			}

		</style>

        <div class="ig_es_form_wrapper">
            <form action="<?php echo $action; ?>" method="post" id="">
                <div class="ig_es_form_heading"><?php _e( 'Are you sure you want to unsubscribe?', 'email-subscribers' ); ?></div>
                <input type="hidden" name="submitted" value="submitted">
                <input class="ig_es_submit" type="submit" name="unsubscribe" value="Yes">
            </form>
        </div>
		<?php
		die();
	}

}
