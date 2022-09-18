<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_DB_Lists extends ES_DB {
	/**
	 * @since 4.0.0
	 * @var $table_name
	 *
	 */
	public $table_name;
	/**
	 * @since 4.0.0
	 * @var $version
	 *
	 */
	public $version;
	/**
	 * @since 4.0.0
	 * @var $primary_key
	 *
	 */
	public $primary_key;

	/**
	 * ES_DB_Lists constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'ig_lists';

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

	/**
	 * Get Lists
	 *
	 * @return array|object|null
	 *
	 * @since 4.0.0
	 */
	public function get_lists() {
		global $wpdb;

		$query = "SELECT * FROM {$this->table_name} WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' ";

		$lists = $wpdb->get_results( $query, ARRAY_A );

		return $lists;
	}

	/**
	 * Get list id name map
	 *
	 * @param string $list_id
	 * @param bool $flip
	 *
	 * @return array|mixed|string
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.1
	 */
	public function get_list_id_name_map( $list_id = '', $flip = false ) {

		$lists_map = array();

		$lists = $this->get_lists();

		if ( count( $lists ) > 0 ) {

			foreach ( $lists as $list ) {
				$lists_map[ $list['id'] ] = $list['name'];
			}

			if ( ! empty( $list_id ) ) {
				$list_name = ! empty( $lists_map[ $list_id ] ) ? $lists_map[ $list_id ] : '';

				return $list_name;
			}

			if ( $flip ) {
				$lists_map = array_flip( $lists_map );
			}
		}

		return $lists_map;
	}

	/**
	 * Get list by name
	 *
	 * @param $name
	 *
	 * @return array|mixed
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.1
	 */
	public function get_list_by_name( $name ) {
		$list = $this->get_by( 'name', $name );
		if ( is_null( $list ) ) {
			$list = array();
		}

		return $list;

		/* TODO: Keep for sometime. Remove it after complete verification/ testing
		global $wpdb;

		$lists = array();
		if ( ! empty( $name ) ) {

			$query = "SELECT * FROM " . IG_LISTS_TABLE . " WHERE `name` = %s LIMIT 0, 1";
			$sql   = $wpdb->prepare( $query, $name );
			$lists = $wpdb->get_results( $sql, ARRAY_A );
		}

		$list = array();
		if ( count( $lists ) > 0 ) {
			$list = array_shift( $lists );
		}

		return $list;
		*/
	}

	/**
	 * Get all lists name by contact_id
	 *
	 * @param $id
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.0
	 */
	public function get_all_lists_name_by_contact( $id ) {
		global $wpdb;

		$lists_contact_table = IG_LISTS_CONTACTS_TABLE;

		$sSql = $wpdb->prepare( "SELECT `name` FROM {$this->table_name} WHERE id IN ( SELECT list_id FROM {$lists_contact_table} WHERE contact_id = %d ) AND ( deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' ) ", $id );
		$res  = $wpdb->get_col( $sSql );

		return $res;
	}

	/**
	 * Add lists
	 *
	 * @param $lists
	 *
	 * @since 4.0.0
	 *
	 * @modify 4.2.1
	 */
	public function add_lists( $lists ) {

		if ( is_string( $lists ) ) {
			$lists = array( $lists );
		}

		if ( count( $lists ) > 0 ) {
			foreach ( $lists as $key => $list ) {
				$this->add_list( $list );
			}
		}

		/**
		 * $query          = "SELECT LOWER(name) FROM " . IG_LISTS_TABLE;
		 * $existing_lists = $wpdb->get_col( $query );
		 * foreach ( $lists as $key => $list ) {
		 * // Insert only if list is not exists.
		 * $lower_list = strtolower( $list );
		 * if ( ! in_array( $lower_list, $existing_lists ) ) {
		 * $sql   = "INSERT INTO " . IG_LISTS_TABLE . " (`slug`, `name`, `created_at`) VALUES (%s, %s, %s)";
		 * $query = $wpdb->prepare( $sql, sanitize_title( $list ), $list, ig_get_current_date_time() );
		 * $wpdb->query( $query );
		 * $existing_lists[] = $list;
		 * }
		 * }
		 */
	}

	/**
	 * Add List into database
	 *
	 * @param $list
	 *
	 * @return int
	 *
	 * @since 4.0.0
	 */
	public function add_list( $list = '' ) {

		if ( empty( $list ) || ! is_string( $list ) ) {
			return 0;
		}

		$lower_list = strtolower( $list );

		$is_list_exists = $this->is_list_exists( $lower_list );

		if ( $is_list_exists ) {
			return 0;
		}

		$data = array(
			'slug' => sanitize_title( $list ),
			'name' => $list
		);

		return $this->insert( $data );

		/*
		$list_table = IG_LISTS_TABLE;

		$query          = "SELECT LOWER(name) FROM {$list_table}";
		$existing_lists = $wpdb->get_col( $query );

		$lower_list = strtolower( $list );

		if ( ! in_array( $lower_list, $existing_lists ) ) {
			$data               = array();
			$data['slug']       = sanitize_title( $list );
			$data['name']       = $list;
			$data['created_at'] = ig_get_current_date_time();

			$insert = $wpdb->insert( $list_table, $data );

			if ( $insert ) {
				return $wpdb->insert_id;
			}

		}

		return 0;
		*/

	}

	/**
	 * Update List
	 *
	 * @param int $row_id
	 * @param array $data
	 *
	 * @return bool|void
	 *
	 * @since 4.2.1
	 */
	public function update_list( $row_id = 0, $name ) {

		if ( empty( $row_id ) ) {
			return;
		}

		$data = array(
			'name'       => $name,
			'updated_at' => ig_get_current_date_time()
		);

		return $this->update( $row_id, $data );
	}

	/**
	 * Check if list is already exists
	 *
	 * @param $name
	 *
	 * @return bool
	 *
	 * @since 4.2.1
	 */
	public function is_list_exists( $name ) {
		$col = $this->get_by( 'name', $name );

		if ( is_null( $col ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get total count of lists
	 *
	 * @return string|null
	 *
	 * @since 4.2.0
	 */
	public function count_lists() {
		$where = "deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'";

		$lists = $this->count($where);

		return $lists;

	}

	/**
	 * Get List Name By Id
	 *
	 * @param $id
	 *
	 * @return string|null
	 *
	 * @since 4.2.0
	 */
	public function get_list_name_by_id( $id ) {
		return $this->get_column_by( 'name', 'id', $id );
	}

	/**
	 * Delete lists
	 *
	 * @param $ids
	 *
	 * @since 4.2.1
	 */
	public function delete_lists( $ids ) {
		global $wpdb;

		if ( is_int( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = implode( ', ', array_map( 'absint', $ids ) );

		// We are doing soft delete.
		$query = "UPDATE {$this->table_name} SET deleted_at = %s WHERE id IN ($ids)";
		$query = $wpdb->prepare( $query, array( ig_get_current_date_time() ) );
		$wpdb->query( $query );

		// Delete Contacts From Lists Contacts Table
		$ig_lists_contacts_table = IG_LISTS_CONTACTS_TABLE;
		$query = "DELETE FROM {$ig_lists_contacts_table} WHERE list_id IN ($ids)";
		$wpdb->query( $query );
	}


}
