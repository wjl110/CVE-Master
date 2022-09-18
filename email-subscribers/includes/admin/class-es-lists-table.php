<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ES_Lists_Table extends WP_List_Table {
	/**
	 * ES_DB_Lists object
	 *
	 * @since 4.2.1
	 * @var $db
	 *
	 */
	protected $db;
	/**
	 * @since 4.2.1
	 * @var string
	 *
	 */
	public static $option_per_page = 'es_lists_per_page';

	/**
	 * ES_Lists_Table constructor.
	 *
	 * @since 4.0
	 */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'List', 'email-subscribers' ), //singular name of the listed records
			'plural'   => __( 'Lists', 'email-subscribers' ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?,
			'screen'   => 'es_lists'
		) );

		$this->db = new ES_DB_Lists();
	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.2.1
	 */
	public static function screen_options() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Number of lists per page', 'email-subscribers' ),
			'default' => 20,
			'option'  => self::$option_per_page
		);

		add_screen_option( $option, $args );

	}

	public function render() {

		$action = ig_es_get_request_data( 'action' );

		?>
        <div class="wrap">
		<?php if ( 'new' === $action ) {
			$this->es_new_lists_callback();
		} elseif ( 'edit' === $action ) {
			$list = ig_es_get_request_data( 'list' );
			echo $this->edit_list( absint( $list ) );
		} else { ?>
            <h1 class="wp-heading-inline"><?php _e( 'Audience > Lists', 'email-subscribers' ); ?> <a href="admin.php?page=es_lists&action=new" class="page-title-action">Add New</a></h1>
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
		<?php }
	}

	/**
     * Validate data
     *
	 * @param $data
	 *
	 * @return array
	 */
	public function validate_data( $data ) {

		$nonce     = $data['nonce'];
		$list_name = $data['list_name'];

		$status  = 'error';
		$message = '';
		if ( ! wp_verify_nonce( $nonce, 'es_list' ) ) {
			$message = __( 'You do not have permission to edit list', 'email-subscribers' );
		} elseif ( empty( $list_name ) ) {
			$message = __( 'Please add list name', 'email-subscribers' );
		} elseif ($this->db->is_list_exists($list_name)) {
			$message = __( 'List already exists. Please choose different name', 'email-subscribers' );
		} else {
			$status = 'success';
		}

		$response = array(
			'status'  => $status,
			'message' => $message
		);

		return $response;

	}

	public function es_new_lists_callback() {

		$submitted = ig_es_get_request_data( 'submitted' );

		if ( 'submitted' === $submitted ) {

			$nonce     = ig_es_get_request_data( '_wpnonce' );
			$list_name = ig_es_get_request_data( 'list_name' );

			$validate_data = array(
				'nonce'     => $nonce,
				'list_name' => $list_name,
			);

			$response = $this->validate_data( $validate_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				ES_Common::show_message( $message, 'error' );
				$this->prepare_list_form( null, $validate_data );

				return;
			}

			$data = array(
				'list_name' => $list_name,
			);

			$save = $this->save_list( null, $data );

			if ( $save ) {
				$message = __( 'List has been added successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			} else {

            }
		}

		$this->prepare_list_form();
	}

	/**
     * Edit List
     *
	 * @param $id
     *
     * @since 4.0.0
	 */
	public function edit_list( $id ) {

		$list = $this->db->get($id);

		$submitted = ig_es_get_request_data( 'submitted' );

		if ( 'submitted' === $submitted ) {

			$nonce     = ig_es_get_request_data( '_wpnonce' );
			$list_name = ig_es_get_request_data( 'list_name' );

			$validate_data = array(
				'nonce'     => $nonce,
				'list_name' => $list_name,
			);

			$response = $this->validate_data( $validate_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				ES_Common::show_message( $message, 'error' );
				$this->prepare_list_form( $id, $validate_data );

				return;
			}

			$data = array(
				'list_name' => $list_name,
			);

			$save = $this->save_list( $id, $data );
			if ( $save ) {
				$message = __( 'List has been updated successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			} else {

            }
		} else {

			$id = $list['id'];

			$data = array(
				'list_name' => $list['name'],
			);

		}

		$this->prepare_list_form( $id, $data );

		?>

		<?php
	}

	public function prepare_list_form( $id = 0, $data = array() ) {

		$is_new = empty( $id ) ? 1 : 0;

		$action = 'new';
		if ( ! $is_new ) {
			$action = 'edit';
		}

		$list_name = isset( $data['list_name'] ) ? $data['list_name'] : '';

		$nonce = wp_create_nonce( 'es_list' );

		?>

        <div class="wrap">
            <h1 class="wp-heading-inline">
				<?php
				if ( $is_new ) {
					_e( 'Add New', 'email-subscribers' );
				} else {
					_e( 'Edit List', 'email-subscribers' );
				}

				?>
                <a href="admin.php?page=es_lists&action=manage-lists" class="page-title-action es-imp-button"><?php _e( 'Manage Lists', 'email-subscribers' ); ?></a>
            </h1>

			<?php Email_Subscribers_Admin::es_feedback(); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post" action="admin.php?page=es_lists&action=<?php echo $action; ?>&list=<?php echo $id; ?>&_wpnonce=<?php echo $nonce; ?>">
                                <div class="row-blog">
                                    <label><?php _e( 'Name', 'email-subscribers' ); ?>: </label>
                                    <input type="text" id="name" name="list_name" value="<?php echo $list_name; ?>"/>
                                </div>
                                <input type="hidden" name="submitted" value="submitted"/>
                                <div class="row-blog"><?php submit_button(); ?></div>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>

		<?php

	}

	/**
	 * Save list
	 *
	 * @param $id
	 * @param $data
	 *
	 * @return bool|int|void
	 *
	 * @since 4.0.0
	 */
	public function save_list( $id, $data ) {
		$name = sanitize_text_field( $data['list_name'] );

		if ( ! empty( $id ) ) {
			$return = $this->db->update_list( $id, $name );
		} else {
			$return = $this->db->add_list( $name );
		}

		/*
		$list_data['name']       = sanitize_text_field( $data['list_name'] );
		$list_data['slug']       = sanitize_title( $list_data['name'] );
		$list_data['created_at'] = ig_get_current_date_time();

		if ( ! empty( $id ) ) {
			$return = $wpdb->update( IG_LISTS_TABLE, $list_data, array( 'id' => $id ) );
		} else {
			$return = $wpdb->insert( IG_LISTS_TABLE, $list_data );
		}
		*/

		return $return;
	}

	/**
	 * Retrieve lists data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_lists( $per_page = 5, $page_number = 1, $do_count_only = false ) {

		global $wpdb;

		$order_by = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order    = ig_es_get_request_data( 'order' );
		$search   = ig_es_get_request_data( 's' );

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM " . IG_LISTS_TABLE;
		} else {
			$sql = "SELECT * FROM " . IG_LISTS_TABLE;
		}

		$args = $query = array();

		$add_where_clause = true;

		$query[] = "( deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00' )";

		if ( ! empty( $search ) ) {
			$query[] = " name LIKE %s ";
			$args[]  = "%" . $wpdb->esc_like( $search ) . "%";
		}

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

			// Prepare Order by clause
			$order                 = ! empty( $order ) ? strtolower( $order ) : 'desc';
			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'name', 'created_at' );

			if ( ! in_array( $order_by, $expected_order_by_values ) ) {
				$order_by_clause = " ORDER BY {$default_order_by} DESC";
			} else {
				$order_by        = esc_sql( $order_by );
				$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
			}

			$sql    .= $order_by_clause;
			$sql    .= " LIMIT $per_page";
			$sql    .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
			$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		} else {
			$result = $wpdb->get_var( $sql );
		}

		return $result;
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . IG_LISTS_TABLE;

		return $wpdb->get_var( $sql );
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
			case 'active_contacts':
				$count = ES_DB_Lists_Contacts::get_total_count_by_list( $item['id'], 'subscribed' );
				if ( $count > 0 ) {
					$url   = admin_url( 'admin.php?page=es_subscribers&filter_by_status=subscribed&filter_by_list_id=' . $item['id'] );
					$count = sprintf( __( '<a href="%s" target="_blank">%d</a>', 'email-subscribers' ), $url, $count );
				}

				return $count;
				break;
			case 'all_contacts':
				$count = ES_DB_Lists_Contacts::get_total_count_by_list( $item['id'], 'all' );
				if ( $count > 0 ) {
					$url   = admin_url( 'admin.php?page=es_subscribers&filter_by_list_id=' . $item['id'] );
					$count = sprintf( __( '<a href="%s" target="_blank">%d</a>', 'email-subscribers' ), $url, $count );
				}

				return $count;
				break;
			case 'created_at':
				return ig_es_format_date_time( $item[ $column_name ] );
				break;

			case 'export':
				return "<a href='admin.php?page=download_report&report=users&status=select_list&list_id={$item['id']}'>" . __( 'Download', 'email-subscribers' ) . '</a>';
			default:
				return '';
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
			'<input type="checkbox" name="lists[]" value="%s" />', $item['id']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

		$list_nonce = wp_create_nonce( 'es_list' );

		$title   = '<strong>' . $item['name'] . '</strong>';
		$actions = array();
		if ( $item['id'] != 1 ) {
			$page    = ig_es_get_request_data( 'page' );
			$actions = array(
				'edit'   => sprintf( __( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s">Edit</a>', 'email-subscribers' ), esc_attr( $page ), 'edit', absint( $item['id'] ), $list_nonce ),
				'delete' => sprintf( __( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s" onclick="return checkDelete()">Delete</a>', 'email-subscribers' ), esc_attr( $page ), 'delete', absint( $item['id'] ), $list_nonce )
			);
		}

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'name'            => __( 'Name', 'email-subscribers' ),
			'active_contacts' => __( 'Active Contacts', 'email-subscribers' ),
			'all_contacts'    => __( 'All Contacts', 'email-subscribers' ),
			'created_at'      => __( 'Created', 'email-subscribers' ),
			'export'          => __( 'Export', 'email-subscribers' )
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
			'name'       => array( 'name', true ),
			'created_at' => array( 'created_at', true ),
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
			'bulk_delete' => __( 'Delete', 'email-subscribers' )
		);

		return $actions;
	}

	public function search_box( $text, $input_id ) { ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button( __( 'Search Lists', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
	<?php }

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();
		$this->search_box( ig_es_get_request_data( 's' ), 'list-search-input' );

		$per_page     = $this->get_items_per_page( self::$option_per_page, 10 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		) );

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'edit' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( ig_es_get_request_data( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'es_list' ) ) {
				$message = __( 'You do not have permission to edit list', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$this->edit_list( absint( ig_es_get_request_data( 'list' ) ) );
				$message = __( 'List has been updated successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}

		}

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( ig_es_get_request_data( '_wpnonce' ) );

			if ( ! wp_verify_nonce( $nonce, 'es_list' ) ) {
				$message = __( 'You do not have permission to delete list', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$list = ig_es_get_request_data( 'list' );
				if ( $list != 1 ) {
					$list = ig_es_get_request_data( 'list' );
					$this->db->delete_lists( array( $list ) );
					$message = __( 'List has been deleted successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				}
			}
		}

		$action  = ig_es_get_request_data( 'action' );
		$action2 = ig_es_get_request_data( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			$lists = ig_es_get_request_data( 'lists' );

			if ( ! empty( $lists ) > 0 ) {
				$this->db->delete_lists( $lists );
				$message = __( 'List(s) have been deleted successfully', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			} else {
				$message = __( 'Please select list', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );

				return;
			}
		}
	}

	public function status_label_map( $status ) {

		$statuses = array(
			'enable'  => __( 'Enable', 'email-subscribers' ),
			'disable' => __( 'Disable', 'email-subscribers' )
		);

		if ( ! in_array( $status, array_keys( $statuses ) ) ) {
			return '';
		}

		return $statuses[ $status ];
	}

	/** Text displayed when no list data is available */
	public function no_items() {
		_e( 'No lists avaliable.', 'email-subscribers' );
	}

}
