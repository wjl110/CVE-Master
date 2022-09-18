<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ES_Reports_Table extends WP_List_Table {

	static $instance;

	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Report', 'email-subscribers' ), //singular name of the listed records
			'plural'   => __( 'Reports', 'email-subscribers' ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?,
			'screen'   => 'es_reports'
		) );

		//add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function es_reports_callback() {

		$action = ig_es_get_request_data( 'action' );
		if ( 'view' === $action ) {
			$list = ig_es_get_request_data( 'list' );
			$this->view_list( $list );
		} else {
			?>

            <div class="wrap">
                <h1 class="wp-heading-inline"><?php _e( 'Reports', 'email-subscribers' ); ?></h1>
				<?php
				$emails_to_be_sent = ES_DB_Sending_Queue::get_total_emails_to_be_sent();
				if ( $emails_to_be_sent > 0 ) {
					$cron_url = ES_Common::get_cron_url( true );
					$content  = sprintf( __( "<a href='%s' target='_blank' class='page-title-action es-imp-button'>Send Queued Emails Now</a>", 'email-subscribers' ), $cron_url );
				} else {
					$content = sprintf( __( "<span class='page-title-action button-disabled'>Send Queued Emails Now</span>", 'email-subscribers' ) );
					$content .= sprintf( __( "<br /><span class='es-helper'>No emails found in queue</span>", 'email-subscribers' ) );
				}
				?>

                <span class="ig-es-process-queue"><?php echo $content; ?></span>


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
	}

	public function screen_option() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Reports', 'email-subscribers' ),
			'default' => 10,
			'option'  => 'reports_per_page'
		);

		add_screen_option( $option, $args );

	}

	public function prepare_header_footer_row() {

		?>

        <tr>
            <th width="6%" scope="col"><?php _e( 'Sr No', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Email', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Status', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Sent Date', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Viewed Status', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Viewed Date', 'email-subscribers' ); ?></th>
        </tr>

		<?php
	}

	public function view_list( $id ) {
		global $wpdb;

		$emails             = ES_DB_Sending_Queue::get_emails_by_hash( $id );
		$email_viewed_count = ES_DB_Sending_Queue::get_viewed_count_by_hash( $id );
		$total_email_sent   = ES_DB_Sending_Queue::get_total_email_count_by_hash( $id );

		?>
        <div class="wrap">
            <div class="tool-box">
                <div class="tablenav">
                    <div class="alignleft" style="padding-bottom:10px;"><?php echo 'Viewed ' . $email_viewed_count . '/' . $total_email_sent; ?></div>
                </div>
                <form name="frm_es_display" method="post">
                    <table width="100%" class="widefat" id="straymanage">
                        <thead>
						<?php echo $this->prepare_header_footer_row(); ?>
                        </thead>
                        <tbody>
						<?php echo $this->prepare_body( $emails ); ?>
                        </tbody>
                        <tfoot>
						<?php echo $this->prepare_header_footer_row(); ?>
                        </tfoot>
                    </table>
                </form>
            </div>
        </div>

		<?php

		//$wpdb->update( EMAIL_SUBSCRIBERS_STATS_TABLE, array( 'viewdate' => date( 'Y-m-d H:i:s' ) ), array( 'viewdate' => $id ) );

	}


	public function prepare_body( $emails ) {

		$i = 1;
		foreach ( $emails as $key => $email ) {
			$class = '';
			if ( $i % 2 === 0 ) {
				$class = 'alternate';
			}

			$email_id  = ! empty( $email['email'] ) ? $email['email'] : ( ! empty( $email['es_deliver_emailmail'] ) ? $email['es_deliver_emailmail'] : '' );
			$status    = ! empty( $email['status'] ) ? $email['status'] : ( ! empty( $email['es_deliver_sentstatus'] ) ? $email['es_deliver_sentstatus'] : '' );
			$sent_at   = ! empty( $email['sent_at'] ) ? $email['sent_at'] : ( ! empty( $email['es_deliver_sentdate'] ) ? $email['es_deliver_sentdate'] : '' );
			$opened    = ! empty( $email['opened'] ) ? $email['opened'] : ( ! empty( $email['es_deliver_status'] ) && $email['es_deliver_status'] === 'Viewed' ? 1 : 0 );
			$opened_at = ! empty( $email['opened_at'] ) ? $email['opened_at'] : ( ! empty( $email['es_deliver_viewdate'] ) ? $email['es_deliver_viewdate'] : '' );

			?>

            <tr class="<?php echo $class; ?>">
                <td align="left"><?php echo $i; ?></td>
                <td><?php echo $email_id; ?></td>
                <td><span style="color:#03a025;font-weight:bold;"><?php echo $status; ?></span></td>
                <td><?php echo ig_es_format_date_time( $sent_at ); ?></td>
                <td><span><?php echo ( ! empty( $opened ) && $opened == 1 ) ? _e( 'Viewed', 'email-subscribers' ) : '<i title="' . __( 'Not yet viewed', 'email-subscribers' ) . '" class="dashicons dashicons-es dashicons-minus">' ?></span></td>
                <td><?php echo ig_es_format_date_time( $opened_at ); ?></td>
            </tr>

			<?php
			$i ++;
		}

	}


	/** Text displayed when no list data is available */
	public function no_items() {
		_e( 'No Reports avaliable.', 'email-subscribers' );
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
		global $wpdb;

		switch ( $column_name ) {
			case 'start_at':
			case 'finish_at':
				return ig_es_format_date_time( $item[ $column_name ] );
			case 'type':
				if ( empty( $item['campaign_id'] ) ) {
					$type = __( 'Post Notification', 'email-subscribers' );
				} else {
					$type = ES()->campaigns_db->get_campaign_type_by_id( $item['campaign_id'] );
					$type = strtolower( $type );
					$type = ( 'newsletter' === $type ) ? __( 'Broadcast', 'email-subscribers' ) : $type;
				}

				$type = ucwords( str_replace( '_', ' ', $type ) );

				return $type;
			case 'subject':
				// case 'type':
				//      return ucwords($item[ $column_name ]);
			case 'count':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	function column_status( $item ) {
		if ( $item['status'] == 'Sent' ) {
			return __( 'Completed', 'email-subscribers' );
		} else {

			$actions = array(
				'send_now' => $this->prepare_send_now_url( $item )
			);

			return $item['status'] . $this->row_actions( $actions, true );
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
			'<input type="checkbox" name="bulk_delete[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_subject( $item ) {

		$es_nonce = wp_create_nonce( 'es_notification' );
		$page     = ig_es_get_request_data( 'page' );

		$title = '<strong>' . $item['subject'] . '</strong>';

		$actions = array(
			'view'          => sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s">%s</a>', esc_attr( $page ), 'view', $item['hash'], $es_nonce, __( 'View', 'email-subscribers' ) ),
			'delete'        => sprintf( '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s">%s</a>', esc_attr( $page ), 'delete', absint( $item['id'] ), $es_nonce, __( 'Delete', 'email-subscribers' ) ),
			'preview_email' => sprintf( '<a target="_blank" href="?page=%s&action=%s&list=%s&_wpnonce=%s">%s</a>', esc_attr( $page ), 'preview', absint( $item['id'] ), $es_nonce, __( 'Preview', 'email-subscribers' ) )
		);

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'subject'   => __( 'Subject', 'email-subscribers' ),
			'type'      => __( 'Type', 'email-subscribers' ),
			'status'    => __( 'Status', 'email-subscribers' ),
			'start_at'  => __( 'Start Date', 'email-subscribers' ),
			'finish_at' => __( 'End Date', 'email-subscribers' ),
			'count'     => __( 'Total Contacts', 'email-subscribers' ),
		);

		return $columns;
	}

	function column_count( $item ) {

		$campaign_hash = $item['hash'];

		$total_emails_sent = $total_emails_to_be_sent = $item['count'];
		// if ( ! empty( $campaign_hash ) ) {
		// 	$total_emails_sent = ES_DB_Sending_Queue::get_total_emails_sent_by_hash( $campaign_hash );
		// }

		// $content = $total_emails_sent . "/" . $total_emails_to_be_sent;

		return $total_emails_to_be_sent;

	}

	function prepare_send_now_url( $item ) {
		$campaign_hash = $item['hash'];

		$cron_url = '';
		if ( ! empty( $campaign_hash ) ) {
			$cron_url = ES_Common::get_cron_url( true, false, $campaign_hash );
		}


		$content = '';
		if ( ! empty( $cron_url ) ) {
			$content = __( sprintf( "<a href='%s' target='_blank'>Send</a>", $cron_url ), 'email-subscribers' );
		}

		return $content;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'subject'   => array( 'subject', true ),
			'status'    => array( 'status', true ),
			'start_at'  => array( 'start_at', true ),
			'finish_at' => array( 'finish_at', true ),
			'count'     => array( 'count', true )
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


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'reports_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_notifications( 0, 0, true );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		) );

		$this->items = $this->get_notifications( $per_page, $current_page, false );
	}

	public function get_notifications( $per_page = 5, $page_number = 1, $do_count_only = false ) {
		global $wpdb;

		$order_by = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order    = ig_es_get_request_data( 'order' );

		$ig_mailing_queue_table = IG_MAILING_QUEUE_TABLE;

		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM {$ig_mailing_queue_table}";
		} else {
			$sql = "SELECT * FROM {$ig_mailing_queue_table}";
		}

		if ( ! $do_count_only ) {

			// Prepare Order by clause
			$order = ! empty( $order ) ? strtolower( $order ) : 'desc';

			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'subject', 'type', 'status', 'start_at', 'count', 'created_at' );

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

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'view' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_notification' ) ) {
				$message = __( 'You do not have permission to view notification', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$this->view_list( ig_es_get_request_data( 'list' ) );
			}

		} elseif ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_notification' ) ) {
				$message = __( 'You do not have permission to delete notification', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$notification_ids = absint( ig_es_get_request_data( 'list' ) );
				ES_DB_Mailing_Queue::delete_notifications( array( $notification_ids ) );
				ES_DB_Sending_Queue::delete_sending_queue_by_mailing_id( array( $notification_ids ) );
				$message = __( 'Report has been deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}

		} elseif ( 'preview' === $this->current_action() ) {
			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_notification' ) ) {
				$message = __( 'You do not have permission to preview notification', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {
				$report_id = ig_es_get_request_data( 'list' );
				echo $this->preview_email( $report_id );
				die();
			}
		}

		$action  = ig_es_get_request_data( 'action' );
		$action2 = ig_es_get_request_data( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {
			$notification_ids = ig_es_get_request_data( 'bulk_delete' );

			if ( count( $notification_ids ) > 0 ) {
				ES_DB_Mailing_Queue::delete_notifications( $notification_ids );
				ES_DB_Sending_Queue::delete_sending_queue_by_mailing_id( $notification_ids );
				$message = __( 'Reports have been deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}

		}
	}

	public function preview_email( $report_id ) {
		ob_start();
		?>
        <div class="wrap">
            <h2 style="margin-bottom:1em;">
				<?php echo __( 'Preview Email', 'email-subscribers' ); ?>
            </h2>
            <p>
				<?php echo __( 'This is how the email you sent may look. <br>Note: Different email services (like gmail, yahoo etc) display email content differently. So there could be a slight variation on how your customer will view the email content.', 'email-subscribers' ); ?>
            </p>
            <div class="tool-box">
                <div style="padding:15px;background-color:#FFFFFF;">
					<?php
					$preview = array();
					$preview = ES_DB_Mailing_Queue::get_email_by_id( $report_id );

					$es_email_type = get_option( 'ig_es_email_type' );    // Not the ideal way. Email type can differ while previewing sent email.

					if ( $es_email_type == "WP HTML MAIL" || $es_email_type == "PHP HTML MAIL" ) {
						$preview['body'] = ES_Common::es_process_template_body( $preview['body'], $report_id );
					} else {
						$preview['body'] = str_replace( "<br />", "\r\n", $preview['body'] );
						$preview['body'] = str_replace( "<br>", "\r\n", $preview['body'] );
					}

					echo stripslashes( $preview['body'] );
					?>
                </div>
            </div>
        </div>
		<?php
		$html = ob_get_clean();

		return $html;

	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}