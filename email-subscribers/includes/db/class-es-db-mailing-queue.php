<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Mailing_Queue {

	public $table_name;

	public $version;

	public $primary_key;

	public function __construct() {

		global $wpdb;

		$this->table_name  = IG_MAILING_QUEUE_TABLE;
		$this->primary_key = 'id';
		$this->version     = '1.0';

	}

	/**
	 * Get columns and formats
	 *
	 * @since   2.1
	 */
	public static function get_columns() {
		return array(
			'id'          => '%d',
			'hash'        => '%s',
			'campaign_id' => '%d',
			'subject'     => '%s',
			'body'        => '%s',
			'count'       => '%d',
			'status'      => '%s',
			'start_at'    => '%s',
			'finish_at'   => '%s',
			'created_at'  => '%s',
			'updated_at'  => '%s',
			'meta'        => '%s'
		);
	}

	public static function get_column_defaults() {
		return array(
			'hash'        => null,
			'campaign_id' => 0,
			'subject'     => '',
			'body'        => '',
			'count'       => 0,
			'status'      => 'In Queue',
			'start_at'    => null,
			'finish_at'   => null,
			'created_at'  => ig_get_current_date_time(),
			'updated_at'  => null,
			'meta'        => null
		);
	}

	public static function get_notification_hash_to_be_sent() {
		global $wpdb;

		$query = $wpdb->prepare( "SELECT hash FROM " . IG_MAILING_QUEUE_TABLE . " WHERE status = %s ORDER BY id LIMIT 0, 1", 'In Queue' );

		$hash = $wpdb->get_var( $query );

		//TODO :: update start date

		return $hash;

	}

	public static function get_notification_to_be_sent( $campaign_hash = '' ) {
		global $wpdb;

		$notification = array();

		$ig_mailing_queue_table = IG_MAILING_QUEUE_TABLE;

		if ( ! empty( $campaign_hash ) ) {
			$query = "SELECT * FROM {$ig_mailing_queue_table} WHERE hash = %s";
			$query = $wpdb->prepare( $query, array( $campaign_hash ) );
		} else {
			$current_time = ig_get_current_date_time();

			$query = "SELECT * FROM {$ig_mailing_queue_table} WHERE status IN ('Sending', 'In Queue') AND start_at <= '{$current_time}' ORDER BY start_at, id LIMIT 0, 1";
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( count( $results ) > 0 ) {
			$notification = array_shift( $results );
			// refresh content
			$meta = maybe_unserialize( $notification['meta'] );

			if ( ! empty( $meta ) ) {
				$filter = 'ig_es_refresh_'.$meta['type'].'_content';
				$post_id = !empty( $meta['post_id'] ) ? $meta['post_id'] : 0;
				$content = array();
				$content = apply_filters($filter, $content, array( 'campaign_id' => $notification['campaign_id'], 'post_id' => $post_id ));
				if ( ! empty( $content ) ) {
					$notification['subject'] = ! empty( $content['subject'] ) ? $content['subject'] : $notification['subject'];
					$notification['body']    = ! empty( $content['body'] ) ? $content['body'] : $notification['body'];
					$query_sub_str           = " , subject = '" . esc_sql($notification['subject']) . "', body = '" . esc_sql($notification['body']) . "' ";
				}
			}
			//update sent date
			$currentdate = ig_get_current_date_time();
			$query_str   = "UPDATE {$ig_mailing_queue_table} SET start_at = %s ";
			$where       = " WHERE hash = %s AND finish_at = %s";
			$query_str   = ! empty( $query_sub_str ) ? $query_str . $query_sub_str . $where : $query_str . $where;
			$query       = $wpdb->prepare( $query_str, array( $currentdate, $notification['hash'], '0000-00-00 00:00:00' ) );
			$return_id   = $wpdb->query( $query );
		}

		return $notification;

	}

	// Query to insert sent emails (cron) records in table: es_sentdetails
	public static function update_sent_status( $hash = "", $status = 'In Queue' ) {

		global $wpdb;

		$current_date_time = ig_get_current_date_time();

		$sql      = "UPDATE " . IG_MAILING_QUEUE_TABLE . " SET status = %s";
		$values[] = $status;

		if ( 'Sent' === $status ) {
			$sql      .= ", finish_at = %s";
			$values[] = $current_date_time;
		}

		$sql      .= " WHERE hash = %s";
		$values[] = $hash;

		$query     = $wpdb->prepare( $sql, $values );
		$return_id = $wpdb->query( $query );

		return $return_id;
	}

	/* Get sent email count */
	public static function get_sent_email_count( $notification_hash ) {
		global $wpdb;
		$query       = $wpdb->prepare( "SELECT count FROM " . IG_MAILING_QUEUE_TABLE . "WHERE hash = %s ", array( $notification_hash ) );
		$email_count = $wpdb->get_col( $query );
		$email_count = array_shift( $email_count );

		return $email_count;
	}

	public static function get_notification_by_hash( $notification_hash ) {
		global $wpdb;

		$notification = array();
		$query        = $wpdb->prepare( "SELECT * FROM " . IG_MAILING_QUEUE_TABLE . " WHERE hash = %s", $notification_hash );
		$results      = $wpdb->get_results( $query, ARRAY_A );

		if ( count( $results ) > 0 ) {
			$notification = array_shift( $results );
		}

		return $notification;
	}

	public static function get_notification_by_campaign_id($campaign_id) {
		global $wpdb;

		$notification = array();
		$query        = $wpdb->prepare( "SELECT * FROM " . IG_MAILING_QUEUE_TABLE . " WHERE campaign_id = %d", $campaign_id );
		$results      = $wpdb->get_results( $query, ARRAY_A );

		if ( count( $results ) > 0 ) {
			$notification = array_shift( $results );
		}

		return $notification;
	}

	public static function get_notifications( $per_page = 5, $page_number = 1 ) {
		global $wpdb;

		$sql = "SELECT * FROM " . IG_MAILING_QUEUE_TABLE . " ORDER BY created_at DESC ";

		if ( ! empty( $per_page ) && ! empty( $page_number ) ) {
			$start_limit = ( $page_number - 1 ) * $per_page;
			$sql         .= "LIMIT " . $start_limit . ', ' . $per_page;
		}

		$result = $wpdb->get_results( $sql, ARRAY_A );

		return $result;
	}

	public static function get_notifications_count() {
		global $wpdb;

		$sql = "SELECT count(*) as total_notifications FROM " . IG_MAILING_QUEUE_TABLE;

		$result = $wpdb->get_col( $sql );

		return $result[0];
	}

	public static function delete_notifications( $ids ) {
		global $wpdb;

		$ids   = implode( ',', array_map( 'absint', $ids ) );
		$query = "DELETE FROM " . IG_MAILING_QUEUE_TABLE . " WHERE id IN ($ids)";

		$wpdb->query( $query );
	}

	public static function add_notification( $data ) {
		global $wpdb;

		$column_formats  = self::get_columns();
		$column_defaults = self::get_column_defaults();
		$prepared_data   = ES_DB::prepare_data( $data, $column_formats, $column_defaults, true );

		$data           = $prepared_data['data'];
		$column_formats = $prepared_data['column_formats'];

		$inserted = $wpdb->insert( IG_MAILING_QUEUE_TABLE, $data, $column_formats );

		$last_report_id = 0;
		if ( $inserted ) {
			$last_report_id = $wpdb->insert_id;
		}

		return $last_report_id;
	}

	public static function get_id_details_map() {
		global $wpdb;

		$query   = "SELECT id, start_at, hash FROM " . IG_MAILING_QUEUE_TABLE;
		$results = $wpdb->get_results( $query, ARRAY_A );
		$details = array();
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$details[ $result['hash'] ]['id']       = $result['id'];
				$details[ $result['hash'] ]['start_at'] = $result['start_at'];
			}
		}

		return $details;
	}

	public static function get_email_by_id( $mailing_queue_id ) {
		global $wpdb;

		$report  = array();
		$query   = $wpdb->prepare( "SELECT * FROM " . IG_MAILING_QUEUE_TABLE . " WHERE id = %s", $mailing_queue_id );
		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( count( $results ) > 0 ) {
			$report = array_shift( $results );
		}

		return $report;
	}

	public static function do_insert( $place_holders, $values ) {
		global $wpdb;

		$mailing_queue_table = IG_MAILING_QUEUE_TABLE;

		$query = "INSERT INTO {$mailing_queue_table} (`hash`, `campaign_id`, `subject`, `body`, `count`, `status`, `start_at`, `finish_at`, `created_at`, `updated_at`) VALUES ";
		$query .= implode( ', ', $place_holders );
		$sql   = $wpdb->prepare( $query, $values );

		$logger = get_ig_logger();
		$logger->info( 'Query....<<<<<' . $sql );

		if ( $wpdb->query( $sql ) ) {
			return true;
		} else {
			return false;
		}

	}

	public static function migrate_notifications() {
		global $wpdb;

		$query = "SELECT count(*) as total FROM " . EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE;
		$total = $wpdb->get_var( $query );

		if ( $total > 0 ) {
			$columns = self::get_columns();
			unset( $columns['id'] );
			$fields = array_keys( $columns );

			$batch_size     = IG_DEFAULT_BATCH_SIZE;
			$total_bataches = ( $total > IG_DEFAULT_BATCH_SIZE ) ? ceil( $total / $batch_size ) : 1;

			for ( $i = 0; $i < $total_bataches; $i ++ ) {
				$batch_start = $i * $batch_size;

				$query   = "SELECT * FROM " . EMAIL_SUBSCRIBERS_SENT_TABLE . " LIMIT {$batch_start}, {$batch_size}";
				$results = $wpdb->get_results( $query, ARRAY_A );

				$values = $place_holders = array();
				foreach ( $results as $key => $result ) {
					$queue_data['hash']        = $result['es_sent_guid'];
					$queue_data['campaign_id'] = 0;
					$queue_data['subject']     = $result['es_sent_subject'];
					$queue_data['body']        = $result['es_sent_preview'];
					$queue_data['count']       = $result['es_sent_count'];
					$queue_data['status']      = $result['es_sent_status'];
					$queue_data['start_at']    = $result['es_sent_starttime'];
					$queue_data['finish_at']   = $result['es_sent_endtime'];
					$queue_data['created_at']  = $result['es_sent_starttime'];

					$queue_data = wp_parse_args( $queue_data, self::get_column_defaults() );

					$formats = array();
					foreach ( $columns as $column => $format ) {
						$values[]  = $queue_data[ $column ];
						$formats[] = $format;
					}

					$place_holders[] = "( " . implode( ', ', $formats ) . " )";
				}

				ES_DB::do_insert( IG_MAILING_QUEUE_TABLE, $fields, $place_holders, $values );
			}
		}
	}
}
