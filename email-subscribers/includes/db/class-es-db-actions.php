<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Actions extends ES_DB {
	/**
	 * @since 4.2.1
	 * @var $table_name
	 *
	 */
	public $table_name;
	/**
	 * @since 4.2.1
	 * @var $version
	 *
	 */
	public $version;
	/**
	 * @since 4.2.1
	 * @var $primary_key
	 *
	 */
	public $primary_key;

	/**
	 * ES_DB_Lists constructor.
	 *
	 * @since 4.2.1
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'ig_actions';

		$this->primary_key = 'id';

		$this->version = '1.0';

	}

	/**
	 * Get table columns
	 *
	 * @return array
	 *
	 * @since 4.2.1
	 */
	public function get_columns() {
		return array(
			'id'         => '%d',
			'slug'       => '%s',
			'name'       => '%s',
			'created_at' => '%s',
			'updated_at' => '%s',
			'deleted_at' => '%s'
		);
	}

	/**
	 * Get default column values
	 *
	 * @since  4.2.1
	 */
	public function get_column_defaults() {
		return array(
			'slug'       => null,
			'name'       => null,
			'created_at' => ig_get_current_date_time(),
			'updated_at' => null,
			'deleted_at' => null
		);
	}
}
