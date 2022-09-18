<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Exporter bootstrap file
 */
class Export_Subscribers {

	/**
	 * Constructor
	 */
	public function __construct() {

		$report = ig_es_get_request_data( 'report' );
		$status = ig_es_get_request_data( 'status' );

		if ( $report && $status ) {

			$status = trim( $status );

			$selected_list_id = 0;

			if ( 'select_list' === $status ) {
				$selected_list_id = ig_es_get_request_data( 'list_id', 0 );

				if ( 0 === $selected_list_id ) {
					$message = __( "Please Select List", "email-subscribers" );
					ES_Common::show_message( $message, 'error' );
					exit();
				}
			}

			$csv = $this->generate_csv( $status, $selected_list_id );

			$file_name = strtolower( $status ) . '-' . 'contacts.csv';

			if ( empty( $csv ) ) {
				$message = __( "No data available", 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
				exit();
			} else {
				header( "Pragma: public" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Cache-Control: private", false );
				header( "Content-Type: application/octet-stream" );
				header( "Content-Disposition: attachment; filename={$file_name};" );
				header( "Content-Transfer-Encoding: binary" );

				echo $csv;
				exit;
			}
		}

		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
	}

	public function plugin_menu() {
		add_submenu_page( null, 'Export Contacts', __( 'Export Contacts', 'email-subscribers' ), get_option( 'es_roles_subscriber', true ), 'es_export_subscribers', array( $this, 'export_subscribers_page' ) );
	}

	public function prepare_header_footer_row() {

		?>

        <tr>
            <th scope="col"><?php _e( 'No.', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Contacts', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Total Contacts', 'email-subscribers' ); ?></th>
            <th scope="col"><?php _e( 'Export', 'email-subscribers' ); ?></th>
        </tr>

		<?php
	}

	public function prepare_body() {

		$list_dropdown_html = "<select name='list_id' id='ig_es_export_list_dropdown'>";
		$list_dropdown_html .= ES_Common::prepare_list_dropdown_options();
		$list_dropdown_html .= "</select>";

		$export_lists = array(

			'all'          => __( 'All Contacts', 'email-subscribers' ),
			'subscribed'   => __( 'Subscribed Contacts', 'email-subscribers' ),
			'unsubscribed' => __( 'Unsubscribed Contacts', 'email-subscribers' ),
			//'confirmed'    => __( 'Confirmed Contacts', 'email-subscribers' ),
			'unconfirmed'  => __( 'Unconfirmed Contacts', 'email-subscribers' ),
			'select_list'  => $list_dropdown_html
		);

		$i = 1;
		foreach ( $export_lists as $key => $export_list ) {
			$class = '';
			if ( $i % 2 === 0 ) {
				$class = 'alternate';
			}
			$url = "admin.php?page=download_report&report=users&status={$key}";

			?>

            <tr class="<?php echo $class; ?>" id="ig_es_export_<?php echo $key; ?>">
                <td><?php echo $i; ?></td>
                <td><?php _e( $export_list, 'email-subscribers' ); ?></td>
                <td class="ig_es_total_contacts"><?php echo $this->count_subscribers( $key ); ?></td>
                <td><a href="<?php echo $url; ?>" id="ig_es_export_link_<?php echo $key; ?>"><?php _e( 'Download', 'email-subscribers' ); ?></a></td>
            </tr>

			<?php
			$i ++;
		}

	}

	public function export_subscribers_page() {

		$audience_tab_main_navigation = array();
		$active_tab                   = 'export';
		$audience_tab_main_navigation = apply_filters( 'ig_es_audience_tab_main_navigation', $active_tab, $audience_tab_main_navigation );

		?>
        <div class="wrap">
            <h2 style="margin-bottom:1em;">
				<?php _e( 'Audience > Export Contacts', 'email-subscribers' );
				ES_Common::prepare_main_header_navigation( $audience_tab_main_navigation );
				?>
            </h2>
            <div class="tool-box">
                <form name="frm_es_subscriberexport" method="post">
                    <table width="100%" class="widefat" id="straymanage">
                        <thead>
						<?php $this->prepare_header_footer_row(); ?>
                        </thead>
                        <tbody>
						<?php $this->prepare_body(); ?>
                        </tbody>
                        <tfoot>
						<?php $this->prepare_header_footer_row(); ?>
                        </tfoot>
                    </table>
                </form>
            </div>
        </div>
	<?php }


	/**
	 * @param string $status
	 *
	 * @return string|null
	 */
	public function count_subscribers( $status = 'all' ) {

		global $wpdb;

		switch ( $status ) {
			case 'all':
				$sql = "SELECT COUNT(*) FROM " . IG_LISTS_CONTACTS_TABLE;
				break;

			case 'subscribed':
				$sql = $wpdb->prepare( "SELECT COUNT(*) FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE status = %s", 'subscribed' );
				break;

			case 'unsubscribed':
				$sql = $wpdb->prepare( "SELECT COUNT(email) FROM " . IG_CONTACTS_TABLE . " WHERE status = %s", 'unsubscribed' );
				break;

			case 'confirmed':
				$sql = $wpdb->prepare( "SELECT COUNT(*) FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE status = %s AND optin_type = %d", 'subscribed', IG_DOUBLE_OPTIN );
				break;

			case 'unconfirmed':
				$sql = $wpdb->prepare( "SELECT count(contact_id) FROM " . IG_LISTS_CONTACTS_TABLE . " WHERE status = %s", 'unconfirmed' );
				break;

			case 'select_list':
			default:
				return '-';
				break;
		}

		return $wpdb->get_var( $sql );
	}


	/**
	 * Allow for custom query variables
	 */
	public function query_vars( $query_vars ) {
		$query_vars[] = 'download_report';

		return $query_vars;
	}

	/**
	 * Parse the request
	 */
	public function parse_request( &$wp ) {
		if ( array_key_exists( 'download_report', $wp->query_vars ) ) {
			$this->download_report();
			exit;
		}
	}

	/**
	 * Download report
	 */
	public function download_report() {
		?>

        <div class="wrap">
        <div id="icon-tools" class="icon32"></div>
        <h2>Download Report</h2>
        <p>
            <a href="?page=download_report&report=users"><?php _e( 'Export the Subscribers', 'email-subscribers' ); ?></a>
        </p>

		<?php
	}

	/**
	 * Generate CSV
	 * first_name, last_name, email, status, list, subscribed_at, unsubscribed_at
	 *
	 * @param string $status
	 * @param string $list_id
	 *
	 * @return string
	 */
	public function generate_csv( $status = 'all', $list_id = '' ) {

		global $wpdb;

		ini_set( 'memory_limit', IG_MAX_MEMORY_LIMIT );
		set_time_limit( IG_SET_TIME_LIMIT );

		$email_subscribe_table = IG_CONTACTS_TABLE;
		$contact_lists_table   = IG_LISTS_CONTACTS_TABLE;

		if ( 'all' === $status ) {
			$query = "SELECT * FROM " . IG_LISTS_CONTACTS_TABLE;
		} elseif ( 'subscribed' === $status ) {
			$query = $wpdb->prepare( "SELECT * FROM {$contact_lists_table} WHERE status = %s", 'subscribed' );
		} elseif ( 'unsubscribed' === $status ) {
			$query = $wpdb->prepare( "SELECT * FROM {$contact_lists_table} WHERE status = %s", 'unsubscribed' );
		} elseif ( 'confirmed' === $status ) {
			$query = $wpdb->prepare( "SELECT * FROM {$contact_lists_table} WHERE status = %s AND optin_type = %d ", 'subscribed', IG_DOUBLE_OPTIN );
		} elseif ( 'unconfirmed' === $status ) {
			$query = $wpdb->prepare( "SELECT * FROM {$contact_lists_table} WHERE status = %s", 'unconfirmed' );
		} elseif ( 'select_list' === $status ) {
			$query = $wpdb->prepare( "SELECT * FROM {$contact_lists_table} WHERE list_id = %d ", $list_id );
		} else {
			// If nothing comes, export only 10 contacts
			$query = "SELECT * FROM " . IG_LISTS_CONTACTS_TABLE . " LIMIT 0, 10";
		}

		$subscribers = array();
		$results     = $wpdb->get_results( $query, ARRAY_A );

		if ( count( $results ) > 0 ) {
			$contact_list_map = array();
			$contact_ids      = array();
			foreach ( $results as $result ) {

				if ( ! in_array( $result['contact_id'], $contact_ids ) ) {
					$contact_ids[] = $result['contact_id'];
				}

				$contact_list_map[ $result['contact_id'] ][] = array(
					'status'     => $result['status'],
					'list_id'    => $result['list_id'],
					'optin_type' => $result['optin_type']
				);
			}

			$contact_ids_str = "'" . implode( "' , '", $contact_ids ) . "' ";

			$query = "SELECT `id`, `first_name`, `last_name`, `email`, `created_at` FROM {$email_subscribe_table} WHERE id IN ({$contact_ids_str})";

			$subscribers = $wpdb->get_results( $query, ARRAY_A );
		}

		$csv_output = '';
		if ( count( $subscribers ) > 0 ) {

			$headers = array(
				__( 'First Name', 'email-subscribers' ),
				__( 'Last Name', 'email-subscribers' ),
				__( 'Email', 'email-subscribers' ),
				__( 'List', 'email-subscribers' ),
				__( 'Status', 'email-subscribers' ),
				__( 'Opt-In Type', 'email-subscribers' ),
				__( 'Created On', 'email-subscribers' )
			);

			$lists_id_name_map = ES()->lists_db->get_list_id_name_map();
			$csv_output        .= '"' . implode( '", "', $headers ) . '"';
			$csv_output        .= "\n";

			foreach ( $subscribers as $key => $subscriber ) {

				$data['first_name'] = trim( str_replace( '"', ' ', $subscriber['first_name'] ) );
				$data['last_name']  = trim( str_replace( '"', ' ', $subscriber['last_name'] ) );
				$data['email']      = trim( str_replace( '"', ' ', $subscriber['email'] ) );

				$contact_id = $subscriber['id'];
				if ( ! empty( $contact_list_map[ $contact_id ] ) ) {
					foreach ( $contact_list_map[ $contact_id ] as $list_details ) {
						$data['list']       = $lists_id_name_map[ $list_details['list_id'] ];
						$data['status']     = ucfirst( $list_details['status'] );
						$data['optin_type'] = ( $list_details['optin_type'] == 1 ) ? 'Single Opt-In' : 'Double Opt-In';
						$data['created_at'] = $subscriber['created_at'];
						$csv_output         .= '"' . implode( '", "', $data ) . '"';
						$csv_output         .= "\n";
					}
				}
			}
		}

		return $csv_output;
	}

}

