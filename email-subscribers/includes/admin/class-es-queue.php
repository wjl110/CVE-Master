<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ES_Queue' ) ) {
	/**
	 * Class ES_Queue
	 *
	 * Manage Mailing Queue
	 *
	 * Actions
	 * ig_es_time_based_campaign - Immediately, Daily, Weekly, Monthly
	 * ig_es_contact_insert - Time after contact subscribe
	 * ig_es_campaign_open - Time after specific campaign open
	 *
	 * @since 4.2.0
	 */
	class ES_Queue {
		/**
		 * ES_DB_Queue object
		 *
		 * @since 4.2.1
		 * @var $db
		 *
		 */
		protected $db;

		/**
		 * ES_Queue constructor.
		 *
		 * @since 4.2.0
		 */
		public function __construct() {

			$this->db = new ES_DB_Queue();

			add_action( 'plugins_loaded', array( &$this, 'init' ), 1 );
		}

		/**
		 * Initialize Queue
		 *
		 * @since 4.2.0
		 */
		public function init() {
			add_action( 'ig_es_cron_auto_responder', array( &$this, 'queue_time_based_campaigns' ), 30 );
			add_action( 'ig_es_cron_auto_responder', array( &$this, 'queue_sequences' ), 30 );

			add_action( 'ig_es_cron_worker', array( &$this, 'process_queue' ), 30 );
		}

		/**
		 * Queue valid time based campaigns
		 *
		 * @since 4.2.0
		 */
		public function queue_time_based_campaigns( $campaign_id, $force = false ) {
			/**
			 * Steps
			 *  1. Fetch all active campaigns
			 *  2. Loop over through and based on matched condition put campaign into mailing_queue table
			 *  3. And also insert subscribers for respective campaign into snding_queue_table
			 *  4. Call es cron to send emails from queue
			 */
			static $campaigns_to_process;

			if ( ! isset( $campaigns_to_process ) ) {
				$campaigns_to_process = array();
			}

			if ( $campaign_id ) {
				$campaign  = ES()->campaigns_db->get_campaign_by_id( $campaign_id );
				$campaigns = array( $campaign );
			} else {
				$campaigns = ES()->campaigns_db->get_active_campaigns( IG_CAMPAIGN_TYPE_POST_DIGEST );
			}

			if ( empty( $campaigns ) ) {
				return;
			}

			$now = time();

			foreach ( $campaigns as $campaign ) {

				if ( in_array( $campaign['id'], $campaigns_to_process ) && ! $force ) {
					continue;
				}

				$campaign_id = $campaign['id'];

				$campaigns_to_process[] = $campaign_id;

				$meta = maybe_unserialize( $campaign['meta'] );

				$rules = ! empty( $meta['rules'] ) ? $meta['rules'] : array();

				if ( ! empty( $rules ) ) {

					$action = ! empty( $rules['action'] ) ? $rules['action'] : '';

					if ( 'ig_es_time_based_campaign' != $action ) {
						continue;
					}

					$start_time = ! empty( $meta['next_run'] ) ? $meta['next_run'] : 0;

					if ( ! empty( $start_time ) ) {

						$scheduled = ! empty( $meta['scheduled'] ) ? $meta['scheduled'] : 0;

						$delay = $start_time - $now;

						// seconds the campaign should created before the actual send time.
						$time_created_before = 3600;

						// Is it a good time to do now?
						$do_it = $delay <= $time_created_before;

						// By default do not schedule
						if ( $do_it && ! $scheduled ) {

							$campaign['start_at'] = date( 'Y-m-d H:i:s', $start_time );

							$list_id = $campaign['list_ids'];

							// Do we have active subscribers?
							$contacts       = ES_DB_Contacts::get_active_subscribers_by_list_id( $list_id );
							$total_contacts = count( $contacts );

							if ( $total_contacts > 0 ) {
								// Create a new mailing queue using this campaign
								$result = $this->add_campaign_to_queue( $campaign, $total_contacts );

								if ( is_array( $result ) ) {
									$queue_id = $result['id'];
									$hash     = $result['hash'];

									$this->add_contacts_to_queue( $campaign_id, $hash, $queue_id, $contacts );
								}

							}
						}

						$time_frame = ! empty( $rules['time_frame'] ) ? $rules['time_frame'] : '';

						if ( 'immediately' !== $time_frame ) {

							$data = array(
								'utc_start'   => $start_time,
								'interval'    => $rules['interval'],
								'time_frame'  => $time_frame,
								'time_of_day' => $rules['time_of_day'],
								'weekdays'    => $rules['weekdays'],
								'force'       => true
							);

							// Get the next run time.
							$next_run = ig_es_get_next_future_schedule_date( $data );

							$meta_data['next_run'] = $next_run;
							if ( $next_run == $start_time ) {
								$meta_data['scheduled'] = 1;
							} else {
								$meta_data['scheduled'] = 0;
							}

						} else {
							$meta_data['scheduled'] = 1;
						}

						ES()->campaigns_db->update_campaign_meta( $campaign_id, $meta_data );
					}
				}
			}

		}

		/**
		 * Queue Valid Sequence messages
		 *
		 * @since 4.2.1
		 */
		public function queue_sequences( $campaign_id, $force = false ) {
			global $wpdb;
			/**
			 * Steps
			 *  1. Fetch all active Sequence Message
			 *  2. Loop over through and based on matched condition put campaign into mailing_queue table if not already exists
			 *  3. And also insert subscribers for respective campaign into snding_queue_table
			 */
			static $campaigns_to_process;

			if ( ! isset( $campaigns_to_process ) ) {
				$campaigns_to_process = array();
			}

			if ( $campaign_id ) {
				$campaign  = ES()->campaigns_db->get_campaign_by_id( $campaign_id );
				$campaigns  = array($campaign);
			} else {
				$campaigns = ES()->campaigns_db->get_active_campaigns( IG_CAMPAIGN_TYPE_SEQUENCE_MESSAGE );
			}

			if ( empty( $campaigns ) ) {
				return;
			}

			$now = time();

			foreach ( $campaigns as $campaign ) {

				if ( in_array( $campaign['id'], $campaigns_to_process ) && ! $force ) {
					continue;
				}

				$campaign_id = $campaign['id'];

				$campaigns_to_process[] = $campaign_id;

				$meta = maybe_unserialize( $campaign['meta'] );

				$rules = ! empty( $meta['rules'] ) ? $meta['rules'] : array();

				//ES()->logger->info( 'Rules: ' . print_r( $rules, true ) );

				if ( ! empty( $rules ) ) {

					$action = ! empty( $rules['action'] ) ? $rules['action'] : '';

					if ( 'ig_es_contact_insert' != $action ) {
						continue;
					}

					// We are considering contacts for sequences which are last added in a week.
					$grace_period  = 1 * DAY_IN_SECONDS;
					$queue_upfront = 3600;

					$offset = (int) $rules['amount'] . ' ' . strtoupper( $rules['unit'] );

					$list_ids = $campaign['list_ids'];

					$ig_actions_table        = IG_ACTIONS_TABLE;
					$ig_lists_contacts_table = IG_LISTS_CONTACTS_TABLE;
					$ig_queue_table          = IG_QUEUE_TABLE;
					$ig_campaign_sent        = IG_MESSAGE_SENT;

					$query_args = array(
						"select"   => "SELECT lists_contacts.contact_id, UNIX_TIMESTAMP ( lists_contacts.subscribed_at + INTERVAL $offset ) AS timestamp",
						"from"     => "FROM $ig_lists_contacts_table AS lists_contacts",
						'join1'    => "LEFT JOIN $ig_actions_table AS actions_sent_message ON lists_contacts.contact_id = actions_sent_message.contact_id AND actions_sent_message.type = $ig_campaign_sent AND actions_sent_message.campaign_id IN ($campaign_id)",
						'join2'    => "LEFT JOIN $ig_queue_table AS queue ON lists_contacts.contact_id = queue.contact_id AND queue.campaign_id IN ($campaign_id)",
						'where'    => "WHERE 1=1 AND lists_contacts.list_id IN ($list_ids) AND lists_contacts.status = 'subscribed' AND actions_sent_message.contact_id IS NULL AND queue.contact_id IS NULL",
						'group_by' => "GROUP BY lists_contacts.contact_id",
						'having'   => "HAVING timestamp <= " . ( $now + $queue_upfront ) . " AND timestamp >= " . ( $now - $grace_period ),
						'order_by' => 'ORDER BY timestamp ASC',
					);

					$query = implode( ' ', $query_args );

					//ES()->logger->info( '----------------------------Query Args (ig_es_contact_insert) ----------------------------' );
					//ES()->logger->info( $query );
					//ES()->logger->info( '----------------------------Query Args Complete (ig_es_contact_insert) ----------------------------' );

					$results = $wpdb->get_results( $query, ARRAY_A );

					//ES()->logger->info( 'Results: ' . print_r( $results, true ) );

					if ( ! empty( $results ) ) {

						$contact_ids = wp_list_pluck( $results, 'contact_id' );
						$timestamps  = wp_list_pluck( $results, 'timestamp' );

						/**
						 * Check whether campaign is already exists in mailing_queue table with $campaign_id
						 * If Exists, Get the mailing_queue_id & hash
						 * If Not, create new and get the mailing_queue_id & hash
						 */
						$total_contacts = count( $contact_ids );
						if ( $total_contacts > 0 ) {

							$this->bulk_add( $campaign_id, $contact_ids, $timestamps, 15 );

							$timestamp = min( $timestamps );

							// handle instant delivery
							if ( $timestamp - time() <= 0 ) {
								wp_schedule_single_event( $timestamp, 'ig_es_cron_worker', array( $campaign_id ) );
							}

						}
					}

				}
			}


		}

		/**
		 * Add campaign to queue
		 *
		 * @param $campaign
		 *
		 * @return int | array
		 *
		 * @since 4.2.0
		 */
		public function add_campaign_to_queue( $campaign, $total_contacts ) {

			$campaign_id = $campaign['id'];
			$template_id = $campaign['base_template_id'];
			$template    = get_post( $template_id );
			$queue_id    = 0;
			if ( $template instanceof WP_Post && $total_contacts > 0 ) {

				$subject = ! empty( $template->post_title ) ? $template->post_title : '';
				$content = ! empty( $template->post_content ) ? $template->post_content : '';
				$content = ES_Common::es_process_template_body( $content, $template_id );

				$guid = ES_Common::generate_guid( 6 );

				$data = array(
					'hash'        => $guid,
					'campaign_id' => $campaign_id,
					'subject'     => $subject,
					'body'        => $content,
					'count'       => $total_contacts,
					'status'      => 'In Queue',
					'start_at'    => ! empty( $campaign['start_at'] ) ? $campaign['start_at'] : '',
					'finish_at'   => '',
					'created_at'  => ig_get_current_date_time(),
					'updated_at'  => ig_get_current_date_time(),
					'meta'        => maybe_serialize( array( 'type' => $campaign['type'] ) )
				);

				$queue_id = ES_DB_Mailing_Queue::add_notification( $data );

				return array(
					'hash' => $guid,
					'id'   => $queue_id
				);

			}

			return $queue_id;
		}

		/**
		 * Add contacts into sending_queue_table
		 *
		 * @param $campaign_id
		 * @param $guid
		 * @param $queue_id
		 * @param $contacts
		 *
		 * @since 4.2.1
		 */
		public function add_contacts_to_queue( $campaign_id, $guid, $queue_id, $contacts ) {

			$delivery_data                     = array();
			$delivery_data['hash']             = $guid;
			$delivery_data['subscribers']      = $contacts;
			$delivery_data['campaign_id']      = $campaign_id;
			$delivery_data['mailing_queue_id'] = $queue_id;

			ES_DB_Sending_Queue::do_batch_insert( $delivery_data );
		}

		/**
		 * Bulk Add contacts into queue
		 *
		 * @param $campaign_id
		 * @param $subscribers
		 * @param null $timestamp
		 * @param int $priority
		 * @param bool $clear
		 * @param bool $ignore_status
		 * @param bool $reset
		 * @param bool $options
		 * @param bool $tags
		 *
		 * @return bool|void
		 *
		 * @since 4.2.1
		 */
		public function bulk_add( $campaign_id, $subscribers, $timestamp = null, $priority = 10, $clear = false, $ignore_status = false, $reset = false, $options = false, $tags = false ) {

			global $wpdb;

			if ( $clear ) {
				$this->clear( $campaign_id, $subscribers );
			}

			if ( empty( $subscribers ) ) {
				return;
			}

			if ( is_null( $timestamp ) ) {
				$timestamp = time();
			}

			$timestamps = ! is_array( $timestamp )
				? array_fill( 0, count( $subscribers ), $timestamp )
				: $timestamp;

			$now = time();

			$campaign_id = (int) $campaign_id;
			$subscribers = array_filter( $subscribers, 'is_numeric' );

			if ( $tags ) {
				$tags = maybe_serialize( $tags );
			}
			if ( $options ) {
				$options = maybe_serialize( $options );
			}

			if ( empty( $subscribers ) ) {
				return true;
			}

			$inserts = array();

			foreach ( $subscribers as $i => $subscriber_id ) {
				$inserts[] = "($subscriber_id,$campaign_id,$now," . $timestamps[ $i ] . ",$priority,1,'$ignore_status','$options','$tags')";
			}

			$chunks = array_chunk( $inserts, 1000 );

			$success = true;

			foreach ( $chunks as $insert ) {

				$sql = "INSERT INTO {$wpdb->prefix}ig_queue (contact_id, campaign_id, added, timestamp, priority, count, ignore_status, options, tags) VALUES";

				$sql .= ' ' . implode( ',', $insert );

				$sql .= ' ON DUPLICATE KEY UPDATE timestamp = values(timestamp), ignore_status = values(ignore_status)';
				if ( $reset ) {
					$sql .= ', sent = 0';
				}
				if ( $options ) {
					$sql .= sprintf( ", options = '%s'", $options );
				}
				if ( $tags ) {
					$sql .= sprintf( ", tags = '%s'", $tags );
				}

				//ES()->logger->info( 'Adding Bulk SQL: ' . $sql );

				$success = $success && false !== $wpdb->query( $sql );

			}

			return $success;

		}

		/**
		 * Clear queue which are not assigned to any campaign
		 *
		 * @param null $campaign_id
		 * @param array $subscribers
		 *
		 * @return bool
		 *
		 * @since 4.2.1
		 */
		public function clear( $campaign_id = null, $subscribers = array() ) {

			global $wpdb;

			$campaign_id = (int) $campaign_id;
			$subscribers = array_filter( $subscribers, 'is_numeric' );

			if ( empty( $subscribers ) ) {
				$subscribers = array( - 1 );
			}

			$sql = "DELETE queue FROM {$wpdb->prefix}ig_queue AS queue WHERE queue.sent = 0 AND queue.contact_id NOT IN (" . implode( ',', $subscribers ) . ')';
			if ( ! is_null( $campaign_id ) ) {
				$sql .= $wpdb->prepare( ' AND queue.campaign_id = %d', $campaign_id );
			}

			return false !== $wpdb->query( $sql );

		}

		/**
		 * Process Queue
		 *
		 * @since 4.2.1
		 */
		public function process_queue() {
			global $wpdb;

			$micro_time = microtime( true );

			$ig_queue_table     = IG_QUEUE_TABLE;
			$ig_campaigns_table = IG_CAMPAIGNS_TABLE;

			$sql = 'SELECT queue.campaign_id, queue.contact_id, queue.count AS _count, queue.requeued AS _requeued, queue.options AS _options, queue.tags AS _tags, queue.priority AS _priority';
			$sql .= " FROM $ig_queue_table AS queue";
			$sql .= " LEFT JOIN $ig_campaigns_table AS campaigns ON campaigns.id = queue.campaign_id";
			$sql .= ' WHERE queue.timestamp <= ' . (int) $micro_time . " AND queue.sent_at = 0";
			$sql .= " AND (campaigns.status = 1)";
			$sql .= ' ORDER BY queue.priority DESC';

			//ES()->logger->info( 'Process Queue:' );
			//ES()->logger->info( 'SQL: ' . $sql );

			$notifications = $wpdb->get_results( $sql, ARRAY_A );

			if ( is_array( $notifications ) && count( $notifications ) > 0 ) {
				$campaigns_notifications = $contact_ids = array();
				foreach ( $notifications as $notification ) {
					$campaigns_notifications[ $notification['campaign_id'] ][] = $notification;

					$contact_ids[] = $notification['contact_id'];
				}

				// We need unique ids
				$contact_ids = array_unique( $contact_ids );

				$contacts = ES_DB_Contacts::get_details_by_ids( $contact_ids );

				foreach ( $campaigns_notifications as $campaign_id => $notifications ) {

					$campaign = ES()->campaigns_db->get( $campaign_id );

					if ( ! empty( $campaign ) ) {

						$content = $campaign['body'];
						$subject = $campaign['subject'];

						foreach ( $notifications as $notification ) {

							$contact_id = $notification['contact_id'];

							if ( ! empty( $contacts[ $contact_id ] ) ) {

								$first_name = $contacts[ $contact_id ]['first_name'];
								$last_name  = $contacts[ $contact_id ]['last_name'];
								$hash       = $contacts[ $contact_id ]['hash'];
								$email      = $contacts[ $contact_id ]['email'];
								$name       = ES_Common::prepare_name_from_first_name_last_name( $first_name, $last_name );

								$keywords = array(
									'name'        => $name,
									'first_name'  => $first_name,
									'last_name'   => $last_name,
									'email'       => $email,
									'guid'        => $hash,
									'dbid'        => $contact_id,
									'message_id'  => 0,
									'campaign_id' => $campaign_id
								);

								// Preparing email body
								$body = ES_Mailer::prepare_email_template( $content, $keywords );

								$result = ES_Mailer::send( $email, $subject, $body );

								do_action( 'ig_es_message_sent', $contact_id, $campaign_id, 0 );

								// Email Sent now delete from queue now.
								$this->db->delete_from_queue( $campaign_id, $contact_id );
							}
						}
					}

				}

			}
		}

	}
}



