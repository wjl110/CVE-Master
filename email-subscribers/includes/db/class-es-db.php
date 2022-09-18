<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_DB base class
 *
 * @since 4.0
 */
abstract class ES_DB {
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
	 * ES_DB constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get default columns
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_columns() {
		return array();
	}

	/**
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_column_defaults() {
		return array();
	}

	/**
	 * Retrieve a row by the primary key
	 *
	 * @param $row_id
	 *
	 * @return array|object|void|null
	 *
	 * @since 4.0.0
	 */
	public function get( $row_id, $output = ARRAY_A ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ), $output );
	}

	/**
	 * Retrieve a row by a specific column / value
	 *
	 * @param $column
	 * @param $row_id
	 *
	 * @return array|object|void|null
	 *
	 * @since 4.0.0
	 */
	public function get_by( $column, $row_id, $output = ARRAY_A ) {
		global $wpdb;
		$column = esc_sql( $column );

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id ), $output );
	}

	/**
	 * Get rows by conditions
	 *
	 * @param string $where
	 *
	 * @since 4.2.1
	 */
	public function get_by_conditions( $where = '', $output = ARRAY_A ) {
		global $wpdb;

		$query = "SELECT * FROM $this->table_name";

		if ( ! empty( $where ) ) {
			$query .= " WHERE {$where}";
		}

		return $wpdb->get_results( $query, $output );
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 *
	 * @param $column
	 * @param $row_id
	 *
	 * @return null|string|array
	 *
	 * @since 4.0.0
	 */
	public function get_column( $column, $row_id = 0 ) {
		global $wpdb;

		$column = esc_sql( $column );

		if ( $row_id ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) );
		} else {
			return $wpdb->get_col( "SELECT $column FROM $this->table_name" );
		}
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 *
	 * @param $column
	 * @param $column_where
	 * @param $column_value
	 *
	 * @return string|null
	 *
	 * @since 4.0.0
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;
		$column_where = esc_sql( $column_where );
		$column       = esc_sql( $column );

		return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = %s LIMIT 1;", $column_value ) );
	}

	/**
	 * Insert a new row
	 *
	 * @param $data
	 * @param string $type
	 *
	 * @return int
	 *
	 * @since 4.0.0
	 */
	public function insert( $data, $type = '' ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'ig_es_pre_insert_' . $type, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );
		$wpdb_insert_id = $wpdb->insert_id;

		do_action( 'ig_es_post_insert_' . $type, $wpdb_insert_id, $data );

		return $wpdb_insert_id;
	}

	/**
	 * Update a specific row
	 *
	 * @param $row_id
	 * @param array $data
	 * @param string $where
	 *
	 * @return bool
	 *
	 * @since 4.0.0
	 */
	public function update( $row_id, $data = array(), $where = '' ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row by primary key
	 *
	 * @param int $row_id
	 *
	 * @return bool
	 *
	 * @since 4.0.0
	 */
	public function delete( $row_id = 0 ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check whether table exists or not
	 *
	 * @param $table
	 *
	 * @return bool
	 *
	 * @since 4.0.0
	 */
	public function table_exists( $table ) {
		global $wpdb;
		$table = sanitize_text_field( $table );

		return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;
	}

	/**
	 * Check whether table installed
	 *
	 * @return bool
	 *
	 * @since 4.0.0
	 */
	public function installed() {
		return $this->table_exists( $this->table_name );
	}

	/**
	 * Get total count
	 *
	 * @return string|null
	 *
	 * @since 4.2.1
	 */
	public function count( $where = '' ) {
		global $wpdb;

		$query = "SELECT count(*) FROM $this->table_name";

		if ( ! empty( $where ) ) {
			$query .= " WHERE {$where}";
		}

		return $wpdb->get_var( $query );
	}

	/**
	 * Insert data into bulk
	 *
	 * @param $values
	 * @param int $length
	 * @param string $type
	 *
	 * @since 4.2.1
	 */
	public function bulk_insert( $values, $length = 100, $type = '' ) {
		global $wpdb;

		if ( ! is_array( $values ) ) {
			return;
		}

		// Get the first value from an array to check data structure
		$data = $values[0];

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Remove primary key as we don't require while inserting data
		unset( $column_formats[ $this->primary_key ] );

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		$data_keys = array_keys( $data );
		$fields    = array_merge( array_flip( $data_keys ), $column_formats );

		// Convert Batches into smaller chunk
		$batches = array_chunk( $values, $length );

		foreach ( $batches as $key => $batch ) {

			$place_holders = $final_values = array();

			foreach ( $batch as $value ) {

				$formats = array();
				foreach ( $column_formats as $column => $format ) {
					$final_values[] = $value[ $column ];
					$formats[]      = $format;
				}

				$place_holders[] = "( " . implode( ', ', $formats ) . " )";
				$fields_str      = "`" . implode( "`, `", $fields ) . "`";
				$query           = "INSERT INTO $this->table_name ({$fields_str}) VALUES ";
				$query           .= implode( ', ', $place_holders );
				$sql             = $wpdb->prepare( $query, $final_values );

				$wpdb->query( $sql );
			}
		}
	}

	public static function do_insert( $table_name, $fields, $place_holders, $values ) {
		global $wpdb;

		$fields_str = "`" . implode( "`, `", $fields ) . "`";

		$query = "INSERT INTO {$table_name} ({$fields_str}) VALUES ";
		$query .= implode( ', ', $place_holders );
		$sql   = $wpdb->prepare( $query, $values );

		if ( $wpdb->query( $sql ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get ID, Name Map
	 *
	 * @param string $where
	 *
	 * @return array
	 *
	 * @since 4.2.2
	 */
	public function get_id_name_map($where = '') {
		global $wpdb;

		$query = "SELECT $this->primary_key, name FROM $this->table_name";

		if(!empty($where)) {
			$query .= " WHERE $where";
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		$id_name_map = array();
		if ( count( $results ) > 0 ) {
			foreach ( $results as $result ) {
				$id_name_map[ $result['id'] ] = $result['name'];
			}
		}

		return $id_name_map;
	}

	public static function prepare_data( $data, $column_formats, $column_defaults, $insert = true ) {

		// Set default values
		if ( $insert ) {
			$data = wp_parse_args( $data, $column_defaults );
		}

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		return array(
			'data'           => $data,
			'column_formats' => $column_formats
		);

	}

}
