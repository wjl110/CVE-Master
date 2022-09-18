<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ES_DB_Links' ) ) {
	/**
	 * Store Campaigns links
	 *
	 * Class ES_DB_Links
	 *
	 * @since 4.2.1
	 */
	class ES_DB_Links extends ES_DB {
		/**
		 * Table Name
		 *
		 * @since 4.2.1
		 * @var string
		 *
		 */
		public $table_name;
		/**
		 * Table Version
		 *
		 * @since 4.2.1
		 * @var string
		 *
		 */
		public $version;
		/**
		 * Primary key
		 *
		 * @since 4.2.1
		 * @var string
		 *
		 */
		public $primary_key;

		/**
		 * Initialize
		 *
		 * ES_DB_Links constructor.
		 *
		 * @since 4.2.1
		 */
		public function __construct() {
			global $wpdb;

			parent::__construct();

			$this->table_name = $wpdb->prefix . 'ig_links';

			$this->version = '1.0';

			$this->primary_key = 'id';
		}

		/**
		 * Get columns and formats
		 *
		 * @since 4.2.1
		 */
		public function get_columns() {
			return array(
				'id'          => '%d',
				'message_id'  => '%d',
				'campaign_id' => '%d',
				'link'        => '%s',
				'hash'        => '%s',
				'created_at'  => '%s',
			);
		}

		/**
		 * Get default column values
		 *
		 * @since 4.2.1
		 */
		public function get_column_defaults() {

			return array(
				'message_id'  => 0,
				'campaign_id' => 0,
				'link'        => null,
				'hash'        => null,
				'created_at'  => ig_get_current_date_time(),
			);
		}

		/**
		 * Get link by hash
		 *
		 * @param null $hash
		 *
		 * @return string
		 *
		 * @since 4.2.1
		 */
		public function get_by_hash( $hash = null ) {

			if ( ! empty( $hash ) ) {
				return '';
			}

			return $this->get_by( 'hash', $hash );
		}

		/**
		 * Get link by id
		 *
		 * @param int $id
		 *
		 * @return array|object|void|null
		 *
		 * @since 4.2.1
		 */
		public function get_by_id( $id = 0 ) {

			if ( empty( $id ) ) {
				return;
			}

			return $this->get_by( 'id', $id );
		}

	}
}
