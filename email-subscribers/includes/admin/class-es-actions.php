<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Actions' ) ) {
	/**
	 * Class ES_Actions
	 *
	 * Track all actions
	 *
	 * IG_CONTACT_SUBSCRIBE   => 1,
	 * IG_MESSAGE_SENT        => 2,
	 * IG_MESSAGE_OPEN        => 3,
	 * IG_LINK_CLICK          => 4,
	 * IG_CONTACT_UNSUBSCRIBE => 5,
	 * IG_MESSAGE_SOFT_BOUNCE => 6,
	 * IG_MESSAGE_HARD_BOUNCE => 7,
	 * IG_MESSAGE_ERROR       => 8
	 *
	 * @since 4.2.0
	 */
	class ES_Actions {
		/**
		 * ES_DB_Actions object
		 *
		 * @since 4.2.1
		 * @var $db
		 *
		 */
		protected $db;

		/**
		 * ES_Actions constructor.
		 *
		 * @since 4.2.0
		 */
		public function __construct() {

			$this->db = new ES_DB_Actions();

			add_action( 'init', array( &$this, 'init' ), 1 );
		}

		/**
		 * Init Actions
		 */
		public function init() {
			/**
			 * @since 4.2.0
			 */
			add_action( 'ig_es_contact_subscribe', array( &$this, 'subscribe' ), 10, 2 );
			add_action( 'ig_es_message_sent', array( &$this, 'sent' ), 10, 3 );
			add_action( 'ig_es_message_open', array( &$this, 'open' ), 10, 3 );
			add_action( 'ig_es_message_click', array( &$this, 'click' ), 10, 4 );
			add_action( 'ig_es_contact_unsubscribe', array( &$this, 'unsubscribe' ), 10, 4 );
			//add_action( 'ig_es_message_bounce', array( &$this, 'bounce' ), 10, 3 );
			//add_action( 'ig_es_subscriber_error', array( &$this, 'error' ), 10, 3 );
			//add_action( 'ig_es_contact_list_unsubscribe', array( &$this, 'list_unsubscribe' ), 10, 4 );
		}

		/**
		 * Get action data
		 *
		 * @since 4.2.0
		 */
		public function get_fields( $fields = null, $where = null ) {

			global $wpdb;

			$fields = esc_sql( is_null( $fields ) ? '*' : ( is_array( $fields ) ? implode( ', ', $fields ) : $fields ) );

			$sql = "SELECT $fields FROM {$wpdb->prefix}ig_actions WHERE 1=1";
			if ( is_array( $where ) ) {
				foreach ( $where as $key => $value ) {
					$sql .= ', ' . esc_sql( $key ) . " = '" . esc_sql( $value ) . "'";
				}
			}

			return $wpdb->get_results( $sql, ARRAY_A );

		}

		/**
		 * Add action
		 *
		 * @param $args
		 * @param bool $explicit
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		private function add( $args, $explicit = true ) {

			global $wpdb;

			$args = wp_parse_args( $args, array(
				'created_at' => ig_es_get_current_gmt_timestamp(),
				'updated_at' => ig_es_get_current_gmt_timestamp(),
				'count'      => 1,
			) );

			$sql = "INSERT INTO {$wpdb->prefix}ig_actions (" . implode( ', ', array_keys( $args ) ) . ')';
			$sql .= " VALUES ('" . implode( "','", array_values( $args ) ) . "') ON DUPLICATE KEY UPDATE";

			$sql .= ( $explicit ) ? " created_at = created_at, count = count+1, updated_at = '" . ig_es_get_current_gmt_timestamp() . "'" : ' count = values(count)';

			$result = $wpdb->query( $sql );

			if ( false !== $result ) {
				return true;
			}

			return false;
		}

		/**
		 * Add action
		 *
		 * @param $args
		 * @param bool $explicit
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		private function add_action( $args, $explicit = true ) {
			return $this->add( $args, $explicit );
		}

		/**
		 * Add contact action
		 *
		 * @param $args
		 * @param bool $explicit
		 *
		 * @since 4.2.0
		 */
		public function add_contact_action( $args, $explicit = true ) {

		}

		/**
		 * Track Subscribe Action
		 *
		 * @param $contact_id
		 * @param array $list_ids
		 *
		 * @since 4.2.0
		 */
		public function subscribe( $contact_id, $list_ids = array() ) {
			if ( is_array( $list_ids ) && count( $list_ids ) > 0 ) {
				foreach ( $list_ids as $list_id ) {
					$this->add_action( array(
						'contact_id' => $contact_id,
						'list_id'    => $list_id,
						'type'       => IG_CONTACT_SUBSCRIBE
					) );
				}
			}

		}

		/**
		 * Track Send Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		public function sent( $contact_id, $campaign_id = 0, $message_id = 0 ) {
			return $this->add_action( array(
				'contact_id'  => $contact_id,
				'campaign_id' => $campaign_id,
				'message_id'  => $message_id,
				'type'        => IG_MESSAGE_SENT,
			) );
		}

		/**
		 * Track Message Open Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		public function open( $contact_id, $message_id, $campaign_id ) {
			return $this->add_action( array(
				'contact_id'  => $contact_id,
				'message_id'  => $message_id,
				'campaign_id' => $campaign_id,
				'type'        => IG_MESSAGE_OPEN,
			) );
		}

		/**
		 * Track Link Click Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 * @param $link_id
		 *
		 * @return bool
		 *
		 * @since 4.2.0
		 */
		public function click( $contact_id, $campaign_id, $link_id ) {
			return $this->add_action( array(
				'contact_id'  => $contact_id,
				'campaign_id' => $campaign_id,
				'link_id'     => $link_id,
				'type'        => IG_LINK_CLICK,
			) );
		}

		/**
		 * Track Contact Unsubscribe Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 * @param array $list_ids
		 *
		 * @since 4.2.0
		 */
		public function unsubscribe( $contact_id, $message_id, $campaign_id, $list_ids = array() ) {
			if ( is_array( $list_ids ) && count( $list_ids ) > 0 ) {
				foreach ( $list_ids as $list_id ) {

					$this->add_action( array(
						'contact_id'  => $contact_id,
						'message_id'  => $message_id,
						'campaign_id' => $campaign_id,
						'list_id'     => $list_id,
						'type'        => IG_CONTACT_UNSUBSCRIBE,
					) );
				}
			}
		}

		/**
		 * Track Message Bounce Action
		 *
		 * @param $contact_id
		 * @param $message_id
		 * @param $campaign_id
		 * @param bool $hard
		 *
		 * @since 4.2.0
		 */
		public function bounce( $contact_id, $campaign_id, $hard = false ) {
			$this->add_action( array(
				'contact_id'  => $contact_id,
				'campaign_id' => $campaign_id,
				'type'        => $hard ? IG_MESSAGE_HARD_BOUNCE : IG_MESSAGE_SOFT_BOUNCE,
			) );
		}
	}
}


