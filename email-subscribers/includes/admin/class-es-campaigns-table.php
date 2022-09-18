<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ES_Campaigns_Table extends WP_List_Table {
	/**
	 * @since 4.2.1
	 * @var string
	 *
	 */
	public static $option_per_page = 'es_campaigns_per_page';

	/**
	 * ES_Campaigns_Table constructor.
	 *
	 * @since 4.0
	 */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Campaign', 'email-subscribers' ), //singular name of the listed records
			'plural'   => __( 'Campaign', 'email-subscribers' ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?
			'screen'   => 'es_campaigns'
		) );
	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.2.1
	 */
	public static function screen_options() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Number of campaigns per page', 'email-subscribers' ),
			'default' => 20,
			'option'  => self::$option_per_page
		);

		add_screen_option( $option, $args );

	}

	/**
	 * Render Campaigns table
	 *
	 * @since 4.0
	 */
	public function render() {
		$action = ig_es_get_request_data( 'action' );

		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Campaigns', 'email-subscribers' ) ?>
                <a href="admin.php?page=es_notifications&action=new" class="page-title-action"><?php _e( 'Create Post Notification', 'email-subscribers' ) ?></a>
                <a href="admin.php?page=es_newsletters" class="page-title-action"><?php _e( 'Send Broadcast', 'email-subscribers' ) ?></a>
				<?php do_action( 'ig_es_after_campaign_type_buttons' ) ?>
                <a href="edit.php?post_type=es_template" class="page-title-action es-imp-button"><?php _e( 'Manage Templates', 'email-subscribers' ) ?></a>
            </h1>
			<?php Email_Subscribers_Admin::es_feedback(); ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
								<?php
								$this->prepare_items();
								$this->display(); ?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>
		<?php
	}

	public function custom_admin_notice() {
		$es_note_cat = ig_es_get_request_data( 'es_note_cat' );

		if ( $es_note_cat ) {
			echo '<div class="updated"><p>Notification Added Successfully!</p></div>';
		}
	}

	/**
	 * Retrieve lists data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_lists( $per_page = 5, $page_number = 1, $do_count_only = false ) {

		global $wpdb;

		$order_by = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order    = ig_es_get_request_data( 'order' );
		$search   = ig_es_get_request_data( 's' );

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM " . IG_CAMPAIGNS_TABLE;
		} else {
			$sql = "SELECT * FROM " . IG_CAMPAIGNS_TABLE;
		}

		$args             = $query = array();
		$add_where_clause = true;

		$query[] = "( deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' )";


		if ( ! empty( $search ) ) {
			$query[] = " name LIKE %s ";
			$args[]  = "%" . $wpdb->esc_like( $search ) . "%";
		}

		$query = apply_filters( 'ig_es_campaign_list_where_caluse', $query );

		if ( $add_where_clause ) {
			$sql .= " WHERE ";

			if ( count( $query ) > 0 ) {
				$sql .= implode( " AND ", $query );

				if ( count( $args ) > 0 ) {
					$sql = $wpdb->prepare( $sql, $args );
				}
			}
		}

		if ( ! $do_count_only ) {

			$order                 = ! empty( $order ) ? strtolower( $order ) : 'desc';
			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'base_template_id', 'type' );
			if ( ! in_array( $order_by, $expected_order_by_values ) ) {
				$order_by_clause = " ORDER BY {$default_order_by} DESC";
			} else {
				$order_by        = esc_sql( $order_by );
				$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
			}

			$sql .= $order_by_clause;
			$sql .= " LIMIT $per_page";
			$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

			$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		} else {
			$result = $wpdb->get_var( $sql );
		}

		return $result;
	}

	/**
	 * Text Display when no items available
	 *
	 * @since 4.0
	 */
	public function no_items() {
		_e( 'No Campaigns Found.', 'email-subscribers' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {

			case 'list_ids':
				if ( ! empty( $item[ $column_name ] ) ) {
					return ES()->lists_db->get_list_id_name_map( $item[ $column_name ] );
				} else {
					return '-';
				}
				break;
			case 'status':
				$status = ( $item[ $column_name ] == 1 ) ? __( 'Active', 'email-subscribers' ) : __( 'Inactive', 'email-subscribers' );

				return $status;

				break;
			case 'type':
				$type = ( $item[ $column_name ] === 'newsletter' ) ? __( 'Broadcast', 'email-subscribers' ) : $item[ $column_name ];
				$type = ucwords( str_replace( '_', ' ', $type ) );

				return $type;
				break;
			case 'categories':
				if ( ! empty( $item[ $column_name ] ) ) {
					$categories = ES_Common::convert_categories_string_to_array( $item[ $column_name ], false );
					$categories = strpos( $item[ $column_name ], '{a}All{a}' ) ? __( 'All', 'email-subscribers' ) : trim( trim( implode( ', ', $categories ) ), ',' );

					return $categories;
				} else {
					return '-';
				}
				break;
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="campaigns[]" value="%s" />', $item['id']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_base_template_id( $item ) {

		$type = $item['type'];


		$nonce = wp_create_nonce( 'es_post_notification' );

		if ( $type !== 'newsletter' ) {

			$template = get_post( $item['base_template_id'] );

			if ( $template instanceof WP_Post ) {
				$title = '<strong>' . $template->post_title . '</strong>';
			} else {
				$title = ! empty( $item['name'] ) ? $item['name'] : '';
			}

			$slug = ( in_array( $item['type'], array( 'post_notification', 'post_digets' ) ) ) ? esc_attr( 'es_notifications' ) : 'es_' . $item['type'];

			$actions ['edit']  = sprintf( __( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s">Edit</a>', 'email-subscribers' ), $slug, 'edit', absint( $item['id'] ), $nonce );
			$actions['delete'] = sprintf( __( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s" onclick="return checkDelete()">Delete</a>', 'email-subscribers' ), esc_attr( 'es_campaigns' ), 'delete', absint( $item['id'] ), $nonce );

			$title .= $this->row_actions( $actions );
		} else {
			$title = $item['name'];
		}

		return $title;
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'               => '<input type="checkbox" />',
			'base_template_id' => __( 'Name', 'email-subscribers' ),
			'type'             => __( 'Type', 'email-subscribers' ),
			'list_ids'         => __( 'List', 'email-subscribers' ),
			'categories'       => __( 'Categories', 'email-subscribers' ),
			'status'           => __( 'Status', 'email-subscribers' )
		);

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			//'base_template_id' => array( 'base_template_id', true ),
			//'list_ids'         => array( 'list_ids', true ),
			//'status'           => array( 'status', true )
			'type' => array( 'type', true )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk_delete' => 'Delete'
		);

		return $actions;
	}

	public function search_box( $text, $input_id ) { ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( __( 'Search Campaigns', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
	<?php }

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		// Note: Disable Search box for now.
		$search = ig_es_get_request_data( 's' );
		$this->search_box( $search, 'notification-search-input' );

		$per_page = $this->get_items_per_page( self::$option_per_page, 25 );

		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //We have to calculate the total number of items
			'per_page'    => $per_page //We have to determine how many items to show on a page
		) );

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	public function process_bulk_action() {

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_post_notification' ) ) {
				$message = __( 'You are not allowed to delete campaign.', 'email-subscribers' );
				$status  = 'error';
			} else {
				$list = ig_es_get_request_data( 'list' );
				$this->delete_list( array( $list ) );
				$message = __( 'Campaign has been deleted successfully!', 'email-subscribers' );
				$status  = 'success';
			}

			ES_Common::show_message( $message, $status );
		}

		$action  = ig_es_get_request_data( 'action' );
		$action2 = ig_es_get_request_data( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			$ids = ig_es_get_request_data( 'campaigns' );

			if ( is_array( $ids ) && count( $ids ) > 0 ) {

				$deleted = $this->delete_list( $ids );

				if ( $deleted ) {
					$message = __( 'Campaign(s) have been deleted successfully!', 'email-subscribers' );
					ES_Common::show_message( $message );
				}
			} else {

				$message = __( 'Please check campaign(s) to delete.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}


		}
	}

	/**
	 * Delete a list record.
	 *
	 * @param int $id list ID
	 */
	public function delete_list( $ids ) {
		global $wpdb;

		if ( is_array( $ids ) && count( $ids ) > 0 ) {

			$campaigns_table = IG_CAMPAIGNS_TABLE;

			$ids = implode( ',', array_map( 'absint', $ids ) );

			$current_date = gmdate( 'Y-m-d G:i:s' );
			$query        = "UPDATE {$campaigns_table} SET deleted_at = %s WHERE id IN ($ids)";
			$query        = $wpdb->prepare( $query, array( $current_date ) );
			$result       = $wpdb->query( $query );

			if ( $result ) {
				return true;
			}
		}

		return false;
	}

}
