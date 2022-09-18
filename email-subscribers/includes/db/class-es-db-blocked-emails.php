<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Blocked_Emails extends ES_DB {
	/**
	 * @since 4.2.2
	 * @var $table_name
	 *
	 */
	public $table_name;

	/**
	 * @since 4.2.2
	 * @var $version
	 *
	 */
	public $version;

	/**
	 * @since 4.2.2
	 * @var $primary_key
	 *
	 */
	public $primary_key;

	/**
	 * ES_DB_Blocked_Emails constructor.
	 *
	 * @since 4.2.2
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct();

		$this->table_name = $wpdb->prefix . 'ig_blocked_emails';

		$this->primary_key = 'id';

		$this->version = '1.0';
	}

	/**
	 * Get columns and formats
	 *
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'id'         => '%d',
			'email'      => '%s',
			'ip'         => '%s',
			'created_on' => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return array(
			'email'      => null,
			'ip'         => null,
			'created_on' => ig_get_current_date_time(),
		);
	}

}
