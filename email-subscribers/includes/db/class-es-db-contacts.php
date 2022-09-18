<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Contacts {

	public function __construct() {

	}

	public static function get_columns() {
		return array(
			'id'             => '%d',
			'wp_user_id'     => '%d',
			'first_name'     => '%s',
			'last_name'      => '%s',
			'email'          => '%s',
			'source'         => '%s',
			'form_id'        => '%d',
			'status'         => '%s',
			'unsubscribed'   => '%d',
			'hash'           => '%s',
			'created_at'     => '%s',
			'updated_at'     => '%s',
			'is_verified'    => '%d',
			'is_disposable'  => '%d',
			'is_rolebased'   => '%d',
			'is_webmail'     => '%d',
			'is_deliverable' => '%d',
			'is_sendsafely'  => '%d',
			'meta'           => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since   2.1
	 */
	public static function get_column_defaults() {
		return array(
			'wp_user_id'     => 0,
			'first_name'     => '',
			'last_name'      => '',
			'email'          => '',
			'source'         => '',
			'form_id'        => 0,
			'status'         => 'verified',
			'unsubscribed'   => 0,
			'hash'           => '',
			'created_at'     => ig_get_current_date_time(),
			'updated_at'     => '',
			'is_verified'    => 1,
			'is_disposable'  => 0,
			'is_rolebased'   => 0,
			'is_webmail'     => 0,
			'is_deliverable' => 1,
			'is_sendsafely'  => 1,
			'meta'           => '',
		);
	}


	public static function get_subscribers_by_id( $id ) {

		global $wpdb;
		$sql        = "SELECT * FROM " . IG_CONTACTS_TABLE . " WHERE id = $id ";
		$subscriber = $wpdb->get_row( $sql, ARRAY_A );

		return $subscriber;
	}

	public static function get_subscribers_email_name_map( $emails = array() ) {

		global $wpdb;

		$subscriber_email_name_map = array();
		if ( count( $emails ) > 0 ) {
			function temp( $v ) {
				return "'" . esc_sql( $v ) . "'";
			}

			$emails = array_map( "temp", $emails );

			$emails_str  = implode( ', ', $emails );
			$subscribers = $wpdb->get_results( "SELECT email, first_name, last_name FROM " . IG_CONTACTS_TABLE . " WHERE email IN ( " . $emails_str . ")", ARRAY_A );

			if ( count( $subscribers ) > 0 ) {
				foreach ( $subscribers as $subscriber ) {
					$name                                              = ES_Common::prepare_name_from_first_name_last_name( $subscriber['first_name'], $subscriber['last_name'] );
					$subscriber_email_name_map[ $subscriber['email'] ] = array(
						'name'       => $name,
						'first_name' => $subscriber['first_name'],
						'last_name'  => $subscriber['last_name']
					);
				}
			}
		}

		return $subscriber_email_name_map;

	}

	public static function get_contact_hash_by_id( $id ) {
		global $wpdb;

		if ( ! empty( $id ) ) {

			$ig_contacts_table = IG_CONTACTS_TABLE;
			$query             = "SELECT hash FROM {$ig_contacts_table} WHERE id = %d";
			$sql               = $wpdb->prepare( $query, $id );
			$contact_hash      = $wpdb->get_col( $sql );

			if ( ! empty( $contact_hash ) ) {
				return $contact_hash[0];
			}
		}

		return '';
	}

	public static function is_contact_exists( $id = '', $email = '' ) {
		global $wpdb;

		$query         = "SELECT * FROM " . IG_CONTACTS_TABLE . " WHERE id = %d AND email = %s";
		$sql           = $wpdb->prepare( $query, $id, $email );
		$contact_count = $wpdb->get_results( $sql, ARRAY_A );

		return $contact_count;
	}

	public static function search_subscriber( $id = '', $email = '' ) {
		global $wpdb;
		$sql = "SELECT * FROM " . IG_CONTACTS_TABLE . " WHERE id = $id ";
		if ( ! empty( $email ) ) {
			$sql .= "AND `email` LIKE '%{$email}%'";
		}

		$subscriber = $wpdb->get_row( $sql, ARRAY_A );

		return $subscriber;

	}

	/**
	 */
	public static function get_active_subscribers_by_list_id( $list_id ) {

		global $wpdb;

		$query       = "SELECT * FROM " . IG_CONTACTS_TABLE . " WHERE id IN ( SELECT contact_id FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE list_id = %d AND status IN ( 'subscribed', 'confirmed' )  )";
		$sql         = $wpdb->prepare( $query, $list_id );
		$subscribers = $wpdb->get_results( $sql, ARRAY_A );

		return $subscribers;

	}

	/**
	 * Get contacts by ids
	 *
	 * @param $ids
	 *
	 * @return array|object|null
	 *
	 * @since 4.2.1
	 */
	public static function get_contacts_by_ids( $ids ) {

		global $wpdb;

		$contacts_table = IG_CONTACTS_TABLE;

		$ids_str = implode( ", '", $ids );

		$query       = "SELECT * FROM $contacts_table WHERE id IN ($ids_str)";
		$subscribers = $wpdb->get_results( $query, ARRAY_A );

		return $subscribers;

	}

	public static function count_active_subscribers_by_list_id( $list_id = '' ) {

		global $wpdb;

		$query = "SELECT count(distinct(contact_id)) as total_subscribers FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE status = 'subscribed'";

		if ( $list_id ) {
			$query .= ' AND list_id = %d';
			$query = $wpdb->prepare( $query, $list_id );
		}

		$subscribers = $wpdb->get_var( $query );

		return $subscribers;

	}

	public static function get_active_subscribers_count() {
		global $wpdb;

		$query              = "SELECT COUNT(email) FROM " . IG_CONTACTS_TABLE . " WHERE unsubscribed = 0 OR unsubscribed IS NULL";
		$active_subscribers = $wpdb->get_var( $query );

		return $active_subscribers;
	}

	public static function add_subscriber( $data ) {
		global $wpdb;

		$data   = wp_parse_args( $data, self::get_column_defaults() );
		$insert = $wpdb->insert( IG_CONTACTS_TABLE, $data );

		if ( $insert ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	public static function delete_subscribers( $ids ) {
		global $wpdb;
		$ids   = implode( ',', array_map( 'absint', $ids ) );
		$query = "DELETE FROM " . IG_CONTACTS_TABLE . " WHERE id IN ($ids)";
		$wpdb->query( $query );
		$query_2 = "DELETE FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE contact_id IN ($ids)";

		return $wpdb->query( $query_2 );
	}

	public static function update_contacts_list( $ids, $list_id ) {
		global $wpdb;

		$ids_str = implode( ',', array_map( 'absint', $ids ) );
		//delete all list contact entry
		$del_query = "DELETE FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE contact_id IN ($ids_str) ";
		$wpdb->query( $del_query );

		$values            = array();
		$optin_type_option = get_option( 'ig_es_optin_type', true );
		$optin_type        = 1;
		if ( in_array( $optin_type_option, array( 'double_opt_in', 'double_optin' ) ) ) {
			$optin_type = 2;
		}
		$subscribed_at = ig_get_current_date_time();
		$subscribed_ip = '';
		foreach ( $ids as $contact_id ) {
			array_push( $values, $list_id, $contact_id, 'subscribed', $optin_type, $subscribed_at, $subscribed_ip );
			$place_holders[] = "( %d, %d, %s, %s, %s, %s )"; /* In my case, i know they will always be integers */
		}
		$query = "INSERT INTO " . IG_LISTS_CONTACTS_TABLE . " (`list_id`, `contact_id`, `status`, `optin_type`, `subscribed_at`, `subscribed_ip` ) VALUES ";
		$query .= implode( ', ', $place_holders );
		$sql   = $wpdb->prepare( "$query ", $values );
		if ( $wpdb->query( $sql ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function add_contacts_to_list( $ids, $list_id ) {
		global $wpdb;

		$ids_str = implode( ',', array_map( 'absint', $ids ) );

		$delete_list_contact = "DELETE FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE contact_id IN ($ids_str) AND list_id = %s";
		$del_query           = $wpdb->prepare( $delete_list_contact, array( $list_id ) );
		$wpdb->query( $del_query );

		$values            = array();
		$optin_type_option = get_option( 'ig_es_optin_type', true );
		$optin_type        = 1;
		if ( in_array( $optin_type_option, array( 'double_opt_in', 'double_optin' ) ) ) {
			$optin_type = 2;
		}
		$subscribed_at = ig_get_current_date_time();
		$subscribed_ip = '';
		foreach ( $ids as $contact_id ) {
			array_push( $values, $list_id, $contact_id, 'subscribed', $optin_type, $subscribed_at, $subscribed_ip );
			$place_holders[] = "( %d, %d, %s, %s, %s, %s )"; /* In my case, i know they will always be integers */
		}
		$query = "INSERT INTO " . IG_LISTS_CONTACTS_TABLE . " (`list_id`, `contact_id`, `status`, `optin_type`, `subscribed_at`, `subscribed_ip` ) VALUES ";
		$query .= implode( ', ', $place_holders );
		$sql   = $wpdb->prepare( "$query ", $values );
		if ( $wpdb->query( $sql ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function edit_subscriber_status_global( $ids, $unsubscribed ) {
		global $wpdb;

		$contacts_table = IG_CONTACTS_TABLE;
		$ids            = implode( ',', array_map( 'absint', $ids ) );

		$sql   = "UPDATE {$contacts_table} SET unsubscribed = %d WHERE id IN ($ids)";
		$query = $wpdb->prepare( $sql, array( $unsubscribed ) );

		return $wpdb->query( $query );

	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function get_total_subscribers() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . IG_CONTACTS_TABLE;

		return $wpdb->get_var( $sql );
	}

	public static function is_subscriber_exist_in_list( $email, $list_id ) {
		global $wpdb;

		$query      = "SELECT id as contact_id FROM " . IG_CONTACTS_TABLE . " WHERE email = %s";
		$sql        = $wpdb->prepare( $query, $email );
		$contact_id = $wpdb->get_var( $sql );
		$data       = array();
		if ( ! empty( $contact_id ) ) {

			if ( ! is_array( $list_id ) ) {
				$list_id = array( $list_id );
			}

			$list_query         = "SELECT count(*) as count FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE list_id IN (%s) AND contact_id = %s";
			$list_sql           = $wpdb->prepare( $list_query, implode( ', ', $list_id ), $contact_id );
			$data['contact_id'] = $contact_id;
			$list_contact_count = $wpdb->get_var( $list_sql );
			if ( ! empty( $list_contact_count ) ) {
				$data['list_id'] = true;
			}

			return $data;
		}

		return $data;
	}

	public static function get_email_details_map() {
		global $wpdb;

		$query    = "SELECT id, email, hash FROM " . IG_CONTACTS_TABLE;
		$contacts = $wpdb->get_results( $query, ARRAY_A );
		$details  = array();
		if ( count( $contacts ) > 0 ) {
			foreach ( $contacts as $contact ) {
				$details[ $contact['email'] ]['id']   = $contact['id'];
				$details[ $contact['email'] ]['hash'] = $contact['hash'];
			}
		}

		return $details;

	}

	/**
	 * Get contacts id details map
	 *
	 * @param array $contact_ids
	 *
	 * @return array
	 *
	 * @since 4.2.1
	 */
	public static function get_details_by_ids( $contact_ids = array() ) {

		$contacts = self::get_contacts_by_ids( $contact_ids );

		$results = array();
		if ( ! empty( $contacts ) && count( $contacts ) > 0 ) {

			foreach ( $contacts as $contact ) {
				$results[ $contact['id'] ] = $contact;
			}
		}

		return $results;
	}

	public static function get_contact_ids_by_emails( $emails = array() ) {
		global $wpdb;

		$query = "SELECT id FROM " . IG_CONTACTS_TABLE;

		if ( count( $emails ) > 0 ) {
			$emails_str = "'" . implode( "', '", $emails ) . "'";
			$query      .= " WHERE email IN ({$emails_str})";
		}

		$ids = $wpdb->get_col( $query );

		return $ids;
	}

	public static function get_email_id_map( $emails = array() ) {
		global $wpdb;

		$query = "SELECT id, email FROM " . IG_CONTACTS_TABLE;

		if ( count( $emails ) > 0 ) {
			$emails_str = implode( ', ', $emails );
			$query      .= " WHERE email IN ({$emails_str})";
		}
		$results = $wpdb->get_results( $query, ARRAY_A );
		$map     = array();
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$map[ $result['email'] ] = $result['id'];
			}
		}

		return $map;

	}

	public static function do_batch_insert( $contacts ) {

		// Prepare a batch of 50 contacts.
		$batches = array_chunk( $contacts, 50 );

		$columns = self::get_columns();
		unset( $columns['id'] );
		$fields = array_keys( $columns );
		foreach ( $batches as $batch ) {
			$values = $place_holders = array();
			foreach ( $batch as $key => $contact ) {

				$contact = wp_parse_args( $contact, self::get_column_defaults() );

				$formats = array();
				foreach ( $columns as $column => $format ) {
					$values[]  = $contact[ $column ];
					$formats[] = $format;
				}

				$place_holders[] = "( " . implode( ', ', $formats ) . " )";
			}

			ES_DB::do_insert( IG_CONTACTS_TABLE, $fields, $place_holders, $values );
		}
	}


	public static function do_insert( $place_holders, $values ) {
		global $wpdb;

		$contacts_table = IG_CONTACTS_TABLE;
		$query          = "INSERT INTO {$contacts_table} (`wp_user_id`, `first_name`, `last_name`, `email`, `source`, `form_id`, `status`, `unsubscribed`, `hash`, `created_at`, `updated_at` ) VALUES ";
		$query          .= implode( ', ', $place_holders );
		$sql            = $wpdb->prepare( "$query ", $values );

		if ( $wpdb->query( $sql ) ) {
			return true;
		} else {
			return false;
		}

	}

	public static function get_contact_id_by_email( $email ) {
		global $wpdb;

		$query   = "SELECT id FROM " . IG_CONTACTS_TABLE . ' WHERE email = %s';
		$contact = $wpdb->get_var( $wpdb->prepare( $query, $email ) );

		if ( $contact ) {
			return $contact;
		}

		return null;

	}

	public static function migrate_subscribers_from_older_version() {
		global $wpdb;
		//Get Total count of subscribers
		$query = "SELECT count(*) as total FROM " . ES_EMAILLIST_TABLE;
		$total = $wpdb->get_var( $query );

		// If we have subscribers?
		if ( $total > 0 ) {

			// Get all existing Contacats
			$query  = "SELECT email from " . IG_CONTACTS_TABLE;
			$emails = $wpdb->get_col( $query );
			if ( ! is_array( $emails ) ) {
				$emails = array();
			}

			// Import subscribers into batch of 100
			$batch_size     = IG_DEFAULT_BATCH_SIZE;
			$total_bataches = ( $total > IG_DEFAULT_BATCH_SIZE ) ? ceil( $total / $batch_size ) : 1;
			$lists_contacts = array();
			//$exclude_status = array( 'Unsubscribed', 'Unconfirmed' );
			$j = 0;
			for ( $i = 0; $i < $total_bataches; $i ++ ) {
				$batch_start = $i * $batch_size;
				$query       = "SELECT * FROM " . ES_EMAILLIST_TABLE . " LIMIT {$batch_start}, {$batch_size} ";
				$results     = $wpdb->get_results( $query, ARRAY_A );
				if ( count( $results ) > 0 ) {
					$contacts = array();
					foreach ( $results as $key => $result ) {
						$email = $result['es_email_mail'];
						if ( ! in_array( $email, $emails ) ) {

							$contacts[ $key ] = $result;

							$names = array(
								'first_name' => '',
								'last_name'  => ''
							);

							if ( ! empty( $result['es_email_name'] ) ) {
								$names = ES_Common::prepare_first_name_last_name( $result['es_email_name'] );
							} else {
								$name = ES_Common::get_name_from_email( $email );

								$names['first_name'] = $name;
							}

							$contacts[ $key ]['first_name']   = $names['first_name'];
							$contacts[ $key ]['last_name']    = $names['last_name'];
							$contacts[ $key ]['email']        = $email;
							$contacts[ $key ]['source']       = 'Migrated';
							$contacts[ $key ]['status']       = ( 'spam' === strtolower( $result['es_email_status'] ) ) ? 'spam' : 'verified';
							$contacts[ $key ]['unsubscribed'] = ( $result['es_email_status'] === 'Unsubscribed' ) ? 1 : 0;
							$contacts[ $key ]['hash']         = $result['es_email_guid'];
							$contacts[ $key ]['created_at']   = $result['es_email_created'];
							$contacts[ $key ]['updated_at']   = ig_get_current_date_time();

							$emails[] = $email;
						}

						//Collect all contacts based on Lists
						//if ( ! in_array( $result['es_email_status'], $exclude_status ) ) {
						$lists_contacts[ $result['es_email_group'] ][ $j ]['email']         = $email;
						$lists_contacts[ $result['es_email_group'] ][ $j ]['status']        = $result['es_email_status'];
						$lists_contacts[ $result['es_email_group'] ][ $j ]['subscribed_at'] = $result['es_email_created'];
						$lists_contacts[ $result['es_email_group'] ][ $j ]['subscribed_ip'] = null;
						$j ++;
						//}
					}

					self::do_batch_insert( $contacts );
				}

			}

			//Do import Lists Contacts
			if ( count( $lists_contacts ) > 0 ) {
				$list_name_id_map = ES()->lists_db->get_list_id_name_map( '', true );
				foreach ( $lists_contacts as $list_name => $contacts ) {
					if ( ! empty( $list_name_id_map[ $list_name ] ) ) {
						ES_DB_Lists_Contacts::import_contacts_into_lists( $list_name_id_map[ $list_name ], $contacts );
					}
				}
			}
		}
	}

	/**
	 * Edit List Contact Status
	 *
	 * @param $contact_ids
	 * @param $list_ids
	 * @param $status
	 *
	 * @return bool|int
	 *
	 * @since 4.2.0
	 */
	public static function edit_list_contact_status( $contact_ids, $list_ids, $status ) {
		global $wpdb;

		$ids      = implode( ',', array_map( 'absint', $contact_ids ) );
		$list_ids = implode( ',', array_map( 'absint', $list_ids ) );

		$current_date = ig_get_current_date_time();

		if ( 'subscribed' === $status ) {
			$sql   = "UPDATE " . IG_LISTS_CONTACTS_TABLE . " SET status = %s, subscribed_at = %s WHERE contact_id IN ($ids) AND list_id IN ($list_ids)";
			$query = $wpdb->prepare( $sql, array( $status, $current_date ) );
		} elseif ( 'unsubscribed' === $status ) {
			$sql   = "UPDATE " . IG_LISTS_CONTACTS_TABLE . " SET status = %s, unsubscribed_at = %s WHERE contact_id IN ($ids) AND list_id IN ($list_ids)";
			$query = $wpdb->prepare( $sql, array( $status, $current_date ) );
		} elseif ( 'unconfirmed' === $status ) {
			$sql   = "UPDATE " . IG_LISTS_CONTACTS_TABLE . " SET status = %s, optin_type = %d, subscribed_at = NULL, unsubscribed_at = NULL WHERE contact_id IN ($ids) AND list_id IN ($list_ids)";
			$query = $wpdb->prepare( $sql, array( $status, IG_DOUBLE_OPTIN ) );
		}

		return $wpdb->query( $query );

	}
}
