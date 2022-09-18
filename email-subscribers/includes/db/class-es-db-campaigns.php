<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Campaigns extends ES_DB {

	const STATUS_ACTIVE = 1;

	const STATUS_INACTIVE = 0;
	/**
	 * @since 4.2.1
	 * @var string $table_name
	 *
	 */
	public $table_name;

	/**
	 * @since 4.2.1
	 * @var string $version
	 *
	 */
	public $version;

	/**
	 * @since 4.2.1
	 * @var string
	 *
	 */
	public $primary_key;

	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_campaigns';

		$this->primary_key = 'id';

		$this->version = '1.0';


	}

	/**
	 * Get columns and formats
	 *
	 * @since  4.0.0
	 */
	public function get_columns() {
		return array(
			'id'               => '%d',
			'slug'             => '%s',
			'name'             => '%s',
			'type'             => '%s',
			'parent_id'        => '%d',
			'parent_type'      => '%s',
			'subject'          => '%s',
			'body'             => '%s',
			'from_name'        => '%s',
			'from_email'       => '%s',
			'reply_to_name'    => '%s',
			'reply_to_email'   => '%s',
			'categories'       => '%s',
			'list_ids'         => '%s',
			'base_template_id' => '%d',
			'status'           => '%d',
			'created_at'       => '%s',
			'updated_at'       => '%s',
			'deleted_at'       => '%s',
			'meta'             => '%s'
		);
	}

	/**
	 * Get default column values
	 *
	 * @since  4.0.0
	 */
	public function get_column_defaults() {

		$from_name  = ES_Common::get_ig_option( 'from_name' );
		$from_email = ES_Common::get_ig_option( 'from_email' );

		return array(
			'slug'             => null,
			'name'             => null,
			'type'             => null,
			'parent_id'        => null,
			'parent_type'      => null,
			'subject'          => null,
			'body'             => '',
			'from_name'        => $from_name,
			'from_email'       => $from_email,
			'reply_to_name'    => $from_name,
			'reply_to_email'   => $from_email,
			'categories'       => '',
			'list_ids'         => '',
			'base_template_id' => 0,
			'status'           => 0,
			'created_at'       => ig_get_current_date_time(),
			'updated_at'       => null,
			'deleted_at'       => null,
			'meta'             => null
		);
	}

	/**
	 * Get template id by campaign id
	 *
	 * @param $id
	 *
	 * @return array|string|null
	 *
	 * @since 4.2.1
	 */
	public function get_template_id_by_campaign( $id ) {
		return $this->get_column( 'base_template_id', $id );
	}

	/**
	 * @param $data
	 * @param null $id
	 *
	 * @return false|int
	 *
	 * @since 4.0.0
	 */
	public function save_campaign( $data, $id = null ) {

		$insert = is_null( $id ) ? true : false;

		if ( $insert ) {
			$result = $this->insert( $data );
		} else {
			// Set updated_at if not set
			$data['updated_at'] = ! empty( $data['updated_at'] ) ? $data['updated_at'] : ig_get_current_date_time();

			$result = $this->update( $id, $data );
		}

		return $result;

		/*
		$insert          = is_null( $id ) ? true : false;
		$column_formats  = self::get_columns();
		$column_defaults = self::get_column_defaults();
		$prepared_data   = ES_DB::prepare_data( $data, $column_formats, $column_defaults, $insert );

		$campaigns_data = $prepared_data['data'];
		$column_formats = $prepared_data['column_formats'];
		if ( $insert ) {
			$result = $wpdb->insert( IG_CAMPAIGNS_TABLE, $campaigns_data, $column_formats );
			if ( $result ) {
				return $wpdb->insert_id;
			}
		} else {
			$campaigns_data['updated_at'] = ! empty( $campaigns_data['updated_at'] ) ? $campaigns_data['updated_at'] : ig_get_current_date_time();

			$result = $wpdb->update( IG_CAMPAIGNS_TABLE, $campaigns_data, array( 'id' => $id ), $column_formats );
		}
		return $result;
		*/

	}

	/**
	 * Get campaign type by campaign id
	 *
	 * @param $id
	 *
	 * @return string|null
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.1
	 */
	public function get_campaign_type_by_id( $id ) {
		return $this->get_by( 'type', $id );
	}

	/**
	 * Migrate post notification from ES 3.5.x
	 *
	 * @since 4.0.0
	 */
	public function migrate_post_notifications() {
		/**
		 * - Migrate post notifications from es_notification table
		 *
		 */
		global $wpdb;

		$campaigns_data = array();
		$template_ids   = array();

		$from_name        = ES_Common::get_ig_option( 'from_name' );
		$from_email       = ES_Common::get_ig_option( 'from_email' );
		$list_is_name_map = ES()->lists_db->get_list_id_name_map( '', true );

		$query = "SELECT count(*) as total FROM " . EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE;
		$total = $wpdb->get_var( $query );

		if ( $total > 0 ) {
			$batch_size = IG_DEFAULT_BATCH_SIZE;

			$total_batches = ( $total > IG_DEFAULT_BATCH_SIZE ) ? ceil( $total / $batch_size ) : 1;

			for ( $i = 0; $i < $total_batches; $i ++ ) {
				$batch_start   = $i * $batch_size;
				$query         = "SELECT * FROM " . EMAIL_SUBSCRIBERS_NOTIFICATION_TABLE . " LIMIT {$batch_start}, {$batch_size}";
				$notifications = $wpdb->get_results( $query, ARRAY_A );
				if ( count( $notifications ) > 0 ) {
					foreach ( $notifications as $key => $notification ) {
						$categories = ! empty( $notification['es_note_cat'] ) ? $notification['es_note_cat'] : '';
						if ( ! empty( $categories ) ) {
							$categories = explode( '--', $categories );
							$categories = array_map( array( 'ES_Common', 'temp_filter_category' ), $categories );
							$categories = ES_Common::convert_categories_array_to_string( $categories );
						}

						$template_id = 0;
						if ( ! empty( $notification['es_note_templ'] ) ) {
							$template_id = $notification['es_note_templ'];

							if ( ! in_array( $template_id, $template_ids ) ) {
								$template_ids[] = $template_id;
							}
						}

						$campaigns_data[ $key ]['slug']             = $template_id; // We don't have slug at this moment. So, we will fetch template's slug and store it later
						$campaigns_data[ $key ]['name']             = $template_id; // We don't have name at this moment. So, we will fetch template's name and store it later
						$campaigns_data[ $key ]['type']             = IG_CAMPAIGN_TYPE_POST_NOTIFICATION;
						$campaigns_data[ $key ]['from_name']        = $from_name;
						$campaigns_data[ $key ]['from_email']       = $from_email;
						$campaigns_data[ $key ]['reply_to_name']    = $from_name; // We don't have this option avaialble. So, setting from_name as reply_to_name
						$campaigns_data[ $key ]['reply_to_email']   = $from_email; // We don't have this option available. So, setting from_email as reply_to_email
						$campaigns_data[ $key ]['categories']       = $categories;
						$campaigns_data[ $key ]['list_ids']         = ( ! empty( $notification['es_note_group'] ) && ! empty( $list_is_name_map[ $notification['es_note_group'] ] ) ) ? $list_is_name_map[ $notification['es_note_group'] ] : 0;
						$campaigns_data[ $key ]['base_template_id'] = $template_id;
						$campaigns_data[ $key ]['status']           = ( ! empty( $notification['es_note_status'] ) && $notification['es_note_status'] === 'Disable' ) ? 0 : 1;
						$campaigns_data[ $key ]['created_at']       = ig_get_current_date_time();
						$campaigns_data[ $key ]['updated_at']       = null;
						$campaigns_data[ $key ]['deleted_at']       = null;
					}

					$templates_data = array();
					// Get Template Name & Slug
					if ( count( $template_ids ) > 0 ) {
						$template_ids_str = "'" . implode( "', '", $template_ids ) . "'";
						$query            = "SELECT ID, post_name, post_title FROM {$wpdb->prefix}posts WHERE id IN ({$template_ids_str})";
						$templates        = $wpdb->get_results( $query, ARRAY_A );
						foreach ( $templates as $template ) {
							$templates_data[ $template['ID'] ] = $template;
						}
					}

					//Do Batach Insert
					$values  = $place_holders = array();
					$columns = $this->get_columns();
					unset( $columns['id'] );
					$fields = array_keys( $columns );

					foreach ( $campaigns_data as $campaign_data ) {
						$campaign_data['slug'] = ! empty( $templates_data[ $campaign_data['slug'] ] ) ? $templates_data[ $campaign_data['slug'] ]['post_name'] : '';
						$campaign_data['name'] = ! empty( $templates_data[ $campaign_data['name'] ] ) ? $templates_data[ $campaign_data['name'] ]['post_title'] : '';

						$campaign_data = wp_parse_args( $campaign_data, $this->get_column_defaults() );

						$formats = array();
						foreach ( $columns as $column => $format ) {
							$values[]  = $campaign_data[ $column ];
							$formats[] = $format;
						}

						$place_holders[] = "( " . implode( ', ', $formats ) . " )";
					}

					ES_DB::do_insert( IG_CAMPAIGNS_TABLE, $fields, $place_holders, $values );
				}
			}
		}
	}

	/**
	 * Migrate Newsletters from ES 3.5.x
	 *
	 * @since 4.0.0
	 */
	public function migrate_newsletters() {
		global $wpdb;

		$from_name  = ES_Common::get_ig_option( 'from_name' );
		$from_email = ES_Common::get_ig_option( 'from_email' );

		$query = "SELECT count(*) as total FROM " . EMAIL_SUBSCRIBERS_SENT_TABLE . " WHERE es_sent_source = 'Newsletter'";
		$total = $wpdb->get_var( $query );

		if ( $total > 0 ) {

			$list_is_name_map = ES()->lists_db->get_list_id_name_map( '', true );
			$batch_size       = IG_DEFAULT_BATCH_SIZE;
			$total_batches    = ceil( $total / $batch_size );

			$values  = $place_holders = array();
			$columns = $this->get_columns();
			unset( $columns['id'] );
			$fields = array_keys( $columns );
			for ( $i = 0; $i <= $total_batches; $i ++ ) {
				$batch_start = $i * $batch_size;

				$query       = "SELECT * FROM " . EMAIL_SUBSCRIBERS_SENT_TABLE . " WHERE es_sent_source = 'Newsletter' LIMIT {$batch_start}, {$batch_size}";
				$newsletters = $wpdb->get_results( $query, ARRAY_A );

				if ( count( $newsletters ) > 0 ) {
					$campaign_data = $values = $place_holders = array();
					foreach ( $newsletters as $key => $newsletter ) {
						$campaign_data['slug']           = sanitize_title( $newsletter['es_sent_subject'] );
						$campaign_data['name']           = $newsletter['es_sent_subject'];
						$campaign_data['type']           = IG_CAMPAIGN_TYPE_NEWSLETTER;
						$campaign_data['from_name']      = $from_name;
						$campaign_data['from_email']     = $from_email;
						$campaign_data['reply_to_name']  = $from_name; // We don't have this option avaialble. So, setting from_name as reply_to_name
						$campaign_data['reply_to_email'] = $from_email; // We don't have this option available. So, setting from_email as reply_to_email
						$campaign_data['list_ids']       = ( ! empty( $newsletter['es_note_group'] ) && ! empty( $list_is_name_map[ $newsletter['es_note_group'] ] ) ) ? $list_is_name_map[ $newsletter['es_note_group'] ] : 0;
						$campaign_data['status']         = 1;
						$campaign_data['created_at']     = $newsletter['es_sent_starttime'];

						$campaign_data = wp_parse_args( $campaign_data, $this->get_column_defaults() );

						$formats = array();
						foreach ( $columns as $column => $format ) {
							$values[]  = $campaign_data[ $column ];
							$formats[] = $format;
						}

						$place_holders[] = "( " . implode( ', ', $formats ) . " )";
					}

					ES_DB::do_insert( IG_CAMPAIGNS_TABLE, $fields, $place_holders, $values );
				}
			}
		}
	}

	/**
	 * After migration we are not able to get the campaign_id in mailing queue
	 * table. So, we are fetching it now and set campaign_id based on subject match.
	 * If not match, set as 0.
	 */
	public function update_campaign_id_in_mailing_queue() {
		global $wpdb;

		$sql       = "SELECT id, name FROM {$wpdb->prefix}ig_campaigns";
		$campaigns = $wpdb->get_results( $sql, ARRAY_A );

		$data_to_update = array();
		if ( count( $campaigns ) > 0 ) {
			$sql                   = "SELECT * FROM {$wpdb->prefix}ig_mailing_queue";
			$mailing_queue_results = $wpdb->get_results( $sql, ARRAY_A );
			if ( count( $mailing_queue_results ) > 0 ) {
				foreach ( $mailing_queue_results as $result ) {
					$subject = trim( $result['subject'] );
					foreach ( $campaigns as $campaign ) {
						$campaign_name = trim( $campaign['name'] );
						if ( $campaign_name == $subject ) {
							$data_to_update[ $result['id'] ] = $campaign['id'];
							break;
						}
					}

				}
			}

		}

		if ( ! empty( $data_to_update ) ) {
			foreach ( $data_to_update as $mailing_queue_id => $campaign_id ) {
				$sql   = "UPDATE {$wpdb->prefix}ig_mailing_queue SET campaign_id = %d WHERE id = %d";
				$query = $wpdb->prepare( $sql, array( $campaign_id, $mailing_queue_id ) );
				$wpdb->query( $query );
			}
		}
	}

	/**
	 * Get total campaigns
	 *
	 * @return string|null
	 *
	 * @since 4.2.1
	 */
	public function get_total_campaigns( $where = '' ) {

		if ( empty( $where ) ) {
			$where = "deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'";
		}

		$campaigns = $this->count( $where );

		return $campaigns;

	}

	/**
	 * Get total campaigns by type
	 *
	 * @param string $type
	 *
	 * @return string|null
	 *
	 * @since 4.2.1
	 */
	public function get_total_campaigns_by_type( $type = 'newsletter' ) {
		global $wpdb;

		$where = $wpdb->prepare( "type = %s AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')", array( $type ) );

		$campaigns = $this->get_total_campaigns( $where );

		return $campaigns;
	}

	/**
	 * Get total post notifications
	 *
	 * @return string|null
	 *
	 * @since 4.2.1
	 */
	public function get_total_post_notifications() {
		return $this->get_total_campaigns_by_type( 'post_notification' );
	}

	/**
	 * Get total newsletters
	 *
	 * @return string|null
	 *
	 * @since 4.2.1
	 */
	public function get_total_newsletters() {
		return $this->get_total_campaigns_by_type( 'newsletter' );
	}

	/**
	 * Get campaign meta data
	 *
	 * @param $id
	 *
	 * @return mixed|string|null
	 *
	 * @since 4.2.0
	 */
	public function get_campaign_meta_by_id( $id ) {
		$meta = $this->get_column( 'meta', $id );

		if ( $meta ) {
			$meta = maybe_unserialize( $meta );
		}

		return $meta;
	}

	/**
	 * Get campaign categories string
	 *
	 * @param $id
	 *
	 * @return mixed|string|null
	 *
	 * @since 4.2.0
	 */
	public function get_campaign_categories_str_by_id( $id ) {
		$categories_str = $this->get_column( 'categories', $id );

		return $categories_str;
	}


	/**
	 * Get campaigns by id
	 *
	 * @param int $id
	 *
	 * @return array|object|null
	 */
	public function get_campaign_by_id( $id = 0 ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return array();
		}

		$where = $wpdb->prepare( "id = %d AND status = %d", $id, self::STATUS_ACTIVE );

		$campaigns = $this->get_by_conditions( $where );

		$campaign = array();
		if ( ! empty( $campaigns ) ) {
			$campaign = array_shift( $campaigns );
		}

		return $campaign;
	}

	/**
	 * Get campaigns by parent id
	 *
	 * @param int $id
	 *
	 * @return array|object|null
	 *
	 * @since 4.2.1
	 */
	public function get_campaign_by_parent_id( $id = 0 ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return array();
		}

		$where = $wpdb->prepare( "parent_id = %d AND status = %d AND ( deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' )", $id, self::STATUS_ACTIVE );

		$campaigns = $this->get_by_conditions( $where );

		return $campaigns;

	}


	/**
	 * Get Active Campaigns
	 *
	 * @return array|object|null
	 *
	 * @since 4.2.0
	 */
	public function get_active_campaigns( $type = '' ) {
		global $wpdb;

		if ( empty( $type ) ) {
			$where = $wpdb->prepare( "status = %d AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')", self::STATUS_ACTIVE );
		} else {
			$where = $wpdb->prepare( "status = %d AND type = %s AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')", self::STATUS_ACTIVE, $type );
		}

		$campaigns = $this->get_by_conditions( $where );

		return $campaigns;
	}

	/**
	 * Update meta value
	 *
	 * @param int $campaign_id
	 * @param array $meta_data
	 *
	 * @return bool|false|int
	 *
	 * @sine 4.2.0
	 */
	public function update_campaign_meta( $campaign_id = 0, $meta_data = array() ) {

		$update = false;
		if ( ! empty( $campaign_id ) && ! empty( $meta_data ) ) {
			$campaign = $this->get_campaign_by_id( $campaign_id );

			if ( ! empty( $campaign ) ) {

				if ( isset( $campaign['meta'] ) ) {
					$meta = maybe_unserialize( $campaign['meta'] );

					foreach ( $meta_data as $meta_key => $meta_value ) {
						$meta[ $meta_key ] = $meta_value;
					}

					$campaign['meta'] = maybe_serialize( $meta );

					$update = $this->save_campaign( $campaign, $campaign_id );

				}
			}
		}

		return $update;

	}

}
