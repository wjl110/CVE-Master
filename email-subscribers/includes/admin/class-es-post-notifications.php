<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Post_Notifications_Table {

	static $instance;

	public function __construct() {

	}

	public function es_notifications_callback() {

		$action = ig_es_get_request_data( 'action' );

		?>
        <div class="wrap">
			<?php if ( 'new' === $action ) {
				$this->es_newnotification_callback();
			} elseif ( 'edit' === $action ) {
				$list = ig_es_get_request_data( 'list' );
				$this->edit_list( absint( $list ) );
			}
			?>
        </div>
		<?php
	}

	public function es_newnotification_callback() {

		$submitted = ig_es_get_request_data( 'submitted' );
		if ( 'submitted' === $submitted ) {

			$list_id     = ig_es_get_request_data( 'list_id' );
			$template_id = ig_es_get_request_data( 'template_id' );
			$cat         = ig_es_get_request_data( 'es_note_cat' );
			$es_note_cat_parent = ig_es_get_request_data( 'es_note_cat_parent' );
			$cat = !empty($es_note_cat_parent) ?  array($es_note_cat_parent) : $cat;

			if ( empty( $list_id ) ) {
				$message = __( 'Please select list.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
				$this->prepare_post_notification_form();

				return;
			}

			if ( empty( $cat ) ) {
				$message = __( 'Please select categories.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
				$this->prepare_post_notification_form();

				return;
			}

			$type  = 'post_notification';
			$title = get_the_title( $template_id );
            
			$data = array(
				'categories'       => ES_Common::convert_categories_array_to_string( $cat ),
				'list_ids'         => $list_id,
				'base_template_id' => $template_id,
				'status'           => 1,
				'type'             => $type,
				'name'             => $title,
				'slug'             => sanitize_title( $title )
			);
			$data = apply_filters( 'ig_es_post_notification_data', $data );
			if ( empty( $data['base_template_id'] ) ) {
				$message = __( 'Please select template.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
				$this->prepare_post_notification_form();

				return;
			}
			$this->save_list( $data );
			$message = __( 'Post notification has been added successfully!', 'email-subscribers' );
			ES_Common::show_message( $message, 'success' );
		}

		$this->prepare_post_notification_form();

	}

	public function custom_admin_notice() {
		$es_note_cate = ig_es_get_request_data( 'es_note_cate' );

		if ( $es_note_cate ) {
			echo '<div class="updated"><p>Notification Added Successfully!</p></div>';
		}
	}

	public function update_list( $id ) {

		global $wpdb;
		$cat  = ig_es_get_request_data( 'es_note_cat' );
		$data = array(
			'categories'       => ES_Common::convert_categories_array_to_string( $cat ),
			'list_ids'         => ig_es_get_request_data( 'list_id' ),
			'base_template_id' => ig_es_get_request_data( 'template_id' ),
			'status'           => 'active'
		);
		$wpdb->update( IG_CAMPAIGNS_TABLE, $data, array( 'id' => $id ) );

	}

	public function save_list( $data, $id = null ) {
		return ES()->campaigns_db->save_campaign( $data, $id );
	}

	/**
	 * Retrieve lists data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_lists( $per_page = 5, $page_number = 1 ) {

		global $wpdb;

		$order_by = sanitize_sql_orderby( ig_es_get_request_data( 'orderby' ) );
		$order    = ig_es_get_request_data( 'order' );
		$search   = ig_es_get_request_data( 's' );

		$add_where_clause = false;
		$sql              = "SELECT * FROM " . IG_CAMPAIGNS_TABLE;
		$args             = array();
		$query            = array();

		if ( ! empty( $search ) ) {
			$add_where_clause = true;
			$query[]          = " name LIKE %s ";
			$args[]           = "%" . $wpdb->esc_like( $search ) . "%";
		}

		if ( $add_where_clause ) {
			$sql .= " WHERE ";

			if ( count( $query ) > 0 ) {
				$sql .= implode( " AND ", $query );
				$sql = $wpdb->prepare( $sql, $args );
			}
		}

		// Prepare Order by clause
		$order_by_clause = '';
		if ( ! empty( $order_by ) ) {
			$order_by_clause = ' ORDER BY ' . esc_sql( $order_by );
			$order_by_clause .= ! empty( $order ) ? ' ' . esc_sql( $order ) : ' ASC';
		}

		$sql .= $order_by_clause;
		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;

	}

	public function edit_list( $id ) {

		global $wpdb;

		$notifications = $wpdb->get_results( "SELECT * FROM " . IG_CAMPAIGNS_TABLE . " WHERE id = $id LIMIT 0, 1", ARRAY_A );

		$submitted = ig_es_get_request_data( 'submitted' );
		if ( 'submitted' === $submitted ) {
			$categories = ig_es_get_request_data( 'es_note_cat', array() );

			//all categories selected
			$parent_category_option = ig_es_get_request_data( 'es_note_cat_parent' );
			if ( $parent_category_option === '{a}All{a}' ) {
				array_unshift( $categories, $parent_category_option );
			}

			$data = array(
				'categories'       => ES_Common::convert_categories_array_to_string( $categories ),
				'list_ids'         => ig_es_get_request_data( 'list_id' ),
				'base_template_id' => ig_es_get_request_data( 'template_id' ),
				'status'           => ig_es_get_request_data( 'status' )
			);

			$title = '';
			if ( ! empty( $data['base_template_id'] ) ) {
				$title = get_the_title( $data['base_template_id'] );
			}
			$data['name'] = $title;

            $data = apply_filters( 'ig_es_post_notification_data', $data );
			$this->save_list( $data, $id );

			$data['categories'] = ES_Common::convert_categories_string_to_array( $data['categories'], true );
			$message            = __( 'Post notification has been updated successfully!', 'email-subscribers' );
			ES_Common::show_message( $message, 'success' );
		} else {

			$notification = array_shift( $notifications );
			$id           = $notification['id'];

			$categories_str = ! empty( $notification['categories'] ) ? $notification['categories'] : '';
			$categories     = ES_Common::convert_categories_string_to_array( $categories_str, true );
			$data           = array(
				'categories'       => $categories,
				'list_ids'         => $notification['list_ids'],
				'base_template_id' => $notification['base_template_id'],
				'status'           => $notification['status']
			);
		}

		$this->prepare_post_notification_form( $id, $data );

	}

	public static function prepare_post_notification_form( $id = '', $data = array() ) {

		$is_new = empty( $id ) ? 1 : 0;

		$action  = 'new';
		$heading = __( 'Campaigns > New Post Notification', 'email-subscribers' );
		if ( ! $is_new ) {
			$action  = 'edit';
			$heading = __( 'Campaigns > Edit Post Notification', 'email-subscribers' );
		}
		$cat         = isset( $data['categories'] ) ? $data['categories'] : '';
		$list_id     = isset( $data['list_ids'] ) ? $data['list_ids'] : '';
		$template_id = isset( $data['base_template_id'] ) ? $data['base_template_id'] : '';
		$status      = isset( $data['status'] ) ? $data['status'] : '';
		$nonce       = wp_create_nonce( 'es_post_notification' );
		?>

        <div class="wrap">
            <h2 class="wp-heading-inline"><?php echo $heading; ?>
                <a href="admin.php?page=es_campaigns" class="page-title-action"><?php _e( 'Campaigns', 'email-subscribers' ) ?></a>
				<?php if ( $action === 'edit' ) { ?>
                    <a href="admin.php?page=es_notifications&action=new" class="page-title-action"><?php _e( 'Add New', 'email-subscribers' ) ?></a>
				<?php } ?>
                <a href="edit.php?post_type=es_template" class="page-title-action es-imp-button"><?php _e( 'Manage Templates', 'email-subscribers' ) ?></a>
            </h2>
            <hr class="wp-header-end">
            <div class="meta-box-sortables ui-sortable" style="width: 80%;display:inline;float:left">
                <form method="post" action="admin.php?page=es_notifications&action=<?php echo $action; ?>&list=<?php echo $id; ?>&_wpnonce=<?php echo $nonce; ?>">
                    <table class="form-table">
                        <tbody>
                        <?php do_action('es_before_post_notification_settings', $id ); ?>
                        <tr>
                            <th scope="row">
                                <label for="tag-link"><?php _e( 'Select List', 'email-subscribers' ); ?></label>
                                <p class="helper"><?php _e( 'Contacts from the selected list will be notified about new post notification.', 'email-subscribers' ); ?></p>
                            </th>
                            <td>
                                <select name="list_id" id="ig_es_post_notification_list_ids">
									<?php echo ES_Common::prepare_list_dropdown_options( $list_id ); ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="tag-link">
									<?php _e( 'Select template', 'email-subscribers' ); ?>
                                    <p class="helper"><?php _e( 'Content of the selected template will be sent out as post notification.', 'email-subscribers' ); ?></p>
                                </label>
                            </th>
                            <td>
                                <select name="template_id" id="base_template_id">
									<?php echo ES_Common::prepare_templates_dropdown_options( 'post_notification', $template_id ); ?>
                                </select>
                            </td>
                        </tr>
                        <?php do_action('es_after_post_notification_template', $id ); ?>
						<?php if ( ! $is_new ) { ?>
                            <tr>
                                <th scope="row">
                                    <label for="tag-link">
										<?php _e( 'Select Status', 'email-subscribers' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="status" id="status">
										<?php echo ES_Common::prepare_status_dropdown_options( $status ); ?>
                                    </select>
                                </td>
                            </tr>
						<?php } ?>
                        <tr>
                            <th scope="row">
                                <label for="tag-link"><?php _e( 'Select Post Category', 'email-subscribers' ); ?></label>
                                <p class="helper"><?php _e( 'Notification will be sent out when any post from selected categories will be published.', 'email-subscribers' ); ?></p>
                            </th>
                            <td style="vertical-align: top;">
                                <table border="0" cellspacing="0">
                                    <tbody>
									<?php echo ES_Common::prepare_categories_html( $cat ); ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="tag-link">
									<?php _e( 'Select custom post type(s)', 'email-subscribers' ); ?>
                                    <p class="helper"><?php _e( '(Optional) Select custom post type for which you want to send notification.', 'email-subscribers' ); ?></p>
                                </label>
                            </th>
                            <td>
                                <table border="0" cellspacing="0">
                                    <tbody>
									<?php $custom_post_type = '';
									echo ES_Common::prepare_custom_post_type_checkbox( $cat ); ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <?php do_action('es_after_post_notification_settings', $id ); ?>
                        <tr>
                            <td><input type="hidden" name="submitted" value="submitted"></td>
                        </tr>
                        </tbody>
                    </table>
                    <div class="row-blog">
                        <div class="leftside">
                            <p class="submit"><input type="submit" name="submit" id="ig_es_campaign_post_notification_submit_button" class="button button-primary button-large" value="Save Changes"></p>
                        </div>
                    </div>
                </form>
            </div>
            <div clas="es-preview" style="float: right;width: 19%;">
                <div class="es-templ-img"></div>
            </div>
        </div>

		<?php

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
			<?php submit_button( 'Search Notifications', 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
		<?php
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}


