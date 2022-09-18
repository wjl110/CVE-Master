<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ES_Forms_Table extends WP_List_Table {

	/**
	 * @since 4.2.1
	 * @var string
	 *
	 */
	public static $option_per_page = 'es_forms_per_page';

	/**
	 * ES_Forms_Table constructor.
	 *
	 * @since 4.0
	 */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Forms', 'email-subscribers' ), //singular name of the listed records
			'plural'   => __( 'Forms', 'email-subscribers' ), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?,
			'screen'   => 'es_forms'
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
			'label'   => __( 'Number of forms per page', 'email-subscribers' ),
			'default' => 20,
			'option'  => self::$option_per_page
		);

		add_screen_option( $option, $args );
	}


	/**
	 * Render Forms list view
	 *
	 * @since 4.0
	 */
	public function render() {

		$action = ig_es_get_request_data( 'action' );
		?>
        <div class="wrap">
		<?php if ( 'new' === $action ) {
			$this->es_new_form_callback();
		} elseif ( 'edit' === $action ) {
			$form = ig_es_get_request_data( 'form' );
			echo $this->edit_form( absint( $form ) );
		} else { ?>
            <h1 class="wp-heading-inline"><?php _e( 'Forms', 'email-subscribers' ) ?><a href="admin.php?page=es_forms&action=new" class="page-title-action"> <?php _e( 'Add New', 'email-subscribers' ) ?></a></h1>
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

	public function validate_data( $data ) {

		$nonce     = $data['nonce'];
		$form_name = $data['name'];
		$lists     = $data['lists'];

		$status  = 'error';
		$error   = false;
		$message = '';
		if ( ! wp_verify_nonce( $nonce, 'es_form' ) ) {
			$message = __( 'You do not have permission to edit this form.', 'email-subscribers' );
			$error   = true;
		} elseif ( empty( $form_name ) ) {
			$message = __( 'Please add form name.', 'email-subscribers' );
			$error   = true;
		}

		if ( empty( $lists ) ) {
			$message = __( 'Please select list(s) in which contact will be subscribed.', 'email-subscribers' );
			$error   = true;
		}

		if ( ! $error ) {
			$status = 'success';
		}

		$response = array(
			'status'  => $status,
			'message' => $message
		);

		return $response;

	}

	public function es_new_form_callback() {

		$submitted = ig_es_get_request_data( 'submitted' );

		if ( 'submitted' === $submitted ) {

			$nonce     = ig_es_get_request_data( '_wpnonce' );
			$form_data = ig_es_get_request_data( 'form_data' );
			$lists     = ig_es_get_request_data( 'lists' );

			$form_data['lists'] = $lists;

			$validate_data = array(
				'nonce' => $nonce,
				'name'  => ! empty( $form_data['name'] ) ? sanitize_text_field( $form_data['name'] ) : '',
				'lists' => ! empty( $form_data['lists'] ) ? $form_data['lists'] : array()
			);

			$response = $this->validate_data( $validate_data );

			if ( 'error' === $response['status'] ) {
				$message = $response['message'];
				ES_Common::show_message( $message, 'error' );
				$this->prepare_list_form( null, $form_data );

				return;
			}

			$this->save_form( null, $form_data );
			$message = __( 'Form has been added successfully!', 'email-subscribers' );
			ES_Common::show_message( $message, 'success' );
		}

		$this->prepare_list_form();
	}


	public function edit_form( $id ) {
		global $wpdb;

		if ( $id ) {
			$form_data = array();

			$data = $wpdb->get_results( "SELECT * FROM " . IG_FORMS_TABLE . " WHERE id = $id", ARRAY_A );

			if ( count( $data ) > 0 ) {

				$submitted = ig_es_get_request_data( 'submitted' );

				if ( 'submitted' === $submitted ) {

					$nonce     = ig_es_get_request_data( '_wpnonce' );
					$form_data = ig_es_get_request_data( 'form_data' );
					$lists     = ig_es_get_request_data( 'lists' );

					$form_data['lists'] = $lists;

					$validate_data = array(
						'nonce' => $nonce,
						'name'  => $form_data['name'],
						'lists' => $form_data['lists']
					);

					$response = $this->validate_data( $validate_data );

					if ( 'error' === $response['status'] ) {
						$message = $response['message'];
						ES_Common::show_message( $message, 'error' );
						$this->prepare_list_form( $id, $form_data );

						return;
					}

					$this->save_form( $id, $form_data );
					$message = __( 'Form has been updated successfully!', 'email-subscribers' );
					ES_Common::show_message( $message, 'success' );
				} else {

					$data      = $data[0];
					$id        = $data['id'];
					$form_data = self::get_form_data_from_body( $data );
				}
			} else {
				$message = __( 'Sorry, form not found', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			}

			$this->prepare_list_form( $id, $form_data );
		}
	}

	public function prepare_list_form( $id = 0, $data = array() ) {

		$is_new = empty( $id ) ? 1 : 0;

		$action = 'new';
		if ( ! $is_new ) {
			$action = 'edit';
		}

		$form_data['name']               = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$form_data['name_visible']       = ! empty( $data['name_visible'] ) ? sanitize_text_field( $data['name_visible'] ) : 'no';
		$form_data['name_required']      = ! empty( $data['name_required'] ) ? sanitize_text_field( $data['name_required'] ) : 'no';
		$form_data['name_label']         = ! empty( $data['name_label'] ) ? sanitize_text_field( $data['name_label'] ) : '';
		$form_data['name_place_holder']  = ! empty( $data['name_place_holder'] ) ? sanitize_text_field( $data['name_place_holder'] ) : '';
		$form_data['email_label']        = ! empty( $data['email_label'] ) ? sanitize_text_field( $data['email_label'] ) : '';
		$form_data['email_place_holder'] = ! empty( $data['email_place_holder'] ) ? sanitize_text_field( $data['email_place_holder'] ) : '';
		$form_data['button_label']       = ! empty( $data['button_label'] ) ? sanitize_text_field( $data['button_label'] ) : __( 'Subscribe', 'email-subscribers' );
		$form_data['list_visible']       = ! empty( $data['list_visible'] ) ? $data['list_visible'] : 'no';
		$form_data['lists']              = ! empty( $data['lists'] ) ? $data['lists'] : array();
		$form_data['af_id']              = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;
		$form_data['desc']               = ! empty( $data['desc'] ) ? sanitize_text_field( $data['desc'] ) : '';

		$lists = ES()->lists_db->get_list_id_name_map();
		$nonce = wp_create_nonce( 'es_form' );

		?>

        <div class="wrap">
            <h1 class="wp-heading-inline">
				<?php
				if ( $is_new ) {
					_e( 'New Form', 'email-subscribers' );
				} else {
					_e( 'Edit Form', 'email-subscribers' );
				}

				?>
            </h1>

			<?php Email_Subscribers_Admin::es_feedback(); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder column-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post" action="admin.php?page=es_forms&action=<?php echo $action; ?>&form=<?php echo $id; ?>&_wpnonce=<?php echo $nonce; ?>">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Form Name', 'email-subscribers' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="form_data[name]" id="ig_es_title" value="<?php echo stripslashes( $form_data['name'] ); ?>" size="30" maxlength="100"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Description', 'email-subscribers' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" name="form_data[desc]" id="ig_es_title" value="<?php echo stripslashes( $form_data['desc'] ); ?>" size="30" maxlength="100"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Form Fields', 'email-subscribers' ); ?></label>
                                        </th>
                                        <td>
                                            <table class="">
                                                <tr class="form-field">
                                                    <td><b><?php _e( 'Field', 'email-subscribers' ); ?></b></td>
                                                    <td><b><?php _e( 'Show?', 'email-subscribers' ); ?></b></td>
                                                    <td><b><?php _e( 'Required?', 'email-subscribers' ); ?></b></td>
                                                    <td><b><?php _e( 'Label', 'email-subscribers' ); ?></b></td>
                                                    <td><b><?php _e( 'Place Holder', 'email-subscribers' ); ?></b></td>
                                                </tr>
                                                <tr class="form-field">
                                                    <td><?php _e( 'Email', 'email-subscribers' ); ?></td>
                                                    <td><input type="checkbox" class="" name="form_data[email_visible]" value="yes" disabled="disabled" checked="checked"></td>
                                                    <td><input type="checkbox" class="" name="form_data[email_required]" value="yes" disabled="disabled" checked="checked"></td>
                                                    <td><input type="text" class="" name="form_data[email_label]" value="<?php echo $form_data['email_label']; ?>"></td>
                                                    <td><input type="text" class="" name="form_data[email_place_holder]" value="<?php echo $form_data['email_place_holder']; ?>"></td>
                                                </tr>
                                                <tr class="form-field">
                                                    <td><?php _e( 'Name', 'email-subscribers' ); ?></td>
                                                    <td><input type="checkbox" class="es_visible" name="form_data[name_visible]" value="yes" <?php if ( $form_data['name_visible'] === 'yes' ) {
															echo 'checked="checked"';
														} ?> /></td>
                                                    <td><input type="checkbox" class="es_required" name="form_data[name_required]" value="yes" <?php if ( $form_data['name_required'] === 'yes' ) {
															echo 'checked=checked';
														} ?>></td>
                                                    <td><input type="text" class="es_name_label" name="form_data[name_label]" value="<?php echo $form_data['name_label']; ?>" <?php if ( $form_data['name_required'] === 'yes' ) {
															echo 'disabled=disabled';
														} ?> ></td>
                                                    <td><input type="text" class="es_name_label" name="form_data[name_place_holder]" value="<?php echo $form_data['name_place_holder']; ?>" <?php if ( $form_data['name_required'] === 'yes' ) {
															echo 'disabled=disabled';
														} ?> ></td>
                                                </tr>
                                                <tr class="form-field">
                                                    <td><?php _e( 'Button', 'email-subscribers' ); ?></td>
                                                    <td><input type="checkbox" class="" name="form_data[button_visible]" value="yes" disabled="disabled" checked="checked"></td>
                                                    <td><input type="checkbox" class="" name="form_data[button_required]" value="yes" disabled="disabled" checked="checked"></td>
                                                    <td><input type="text" class="" name="form_data[button_label]" value="<?php echo $form_data['button_label']; ?>"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Lists', 'email-subscribers' ); ?></label>
                                            <p class="helper"> <?php _e( 'Contacts will be added into selected list(s)', 'email-subscribers' ); ?></p>
                                        </th>
                                        <td>
											<?php

											if ( count( $lists ) > 0 ) {

												echo ES_Shortcode::prepare_lists_checkboxes( $lists, array_keys( $lists ), 3, (array) $form_data['lists'] );

											} else {
												$create_list_link = admin_url( 'admin.php?page=es_lists&action=new' );
												?>
                                                <span><?php _e( sprintf( 'List not found. Please <a href="%s">create your first list</a>.', $create_list_link ) ); ?></span>
											<?php } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="tag-link"><?php echo __( 'Allow contact to choose list(s)', 'email-subscribers' ); ?></label>
                                            <p class="helper"> <?php _e( 'Allow contacts to choose list(s) in which they want to subscribe.', 'email-subscribers' ); ?></p>
                                        </th>
                                        <td>
                                            <input type="radio" name="form_data[list_visible]" value="yes" <?php if ( $form_data['list_visible'] === 'yes' ) {
												echo 'checked="checked"';
											} ?> /><?php _e( 'Yes', 'email-subscribers' ); ?>

                                            <input type="radio" name="form_data[list_visible]" value="no" <?php if ( $form_data['list_visible'] === 'no' ) {
												echo 'checked="checked"';
											} ?> /> <?php _e( 'No', 'email-subscribers' ); ?>
                                        </td>


                                    </tr>

                                    </tbody>
                                </table>
                                <input type="hidden" name="form_data[af_id]" value="<?php echo $form_data['af_id']; ?>"/>
                                <input type="hidden" name="submitted" value="submitted"/>
								<?php if ( count( $lists ) > 0 ) { ?>
                                    <div class="row-blog"><?php submit_button(); ?></div>
								<?php } else {
									$lists_page_url = admin_url( 'admin.php?page=es_lists' );
									$message        = __( sprintf( 'List(s) not found. Please create a first list from <a href="%s">here</a>', $lists_page_url ), 'email-subscribers' );
									$status         = 'error';
									ES_Common::show_message( $message, $status );
								}
								$url = 'https://www.icegram.com/email-subscribers-starter/?utm_source=in_app&utm_medium=es_form_captcha&utm_campaign=es_upsale';
								?>
                                <div style=" background-image: linear-gradient(-100deg, rgba(250, 247, 133, 0.4), rgba(250, 247, 133, 0.8) 95%, rgba(250, 247, 133, 0.2)); padding: 10px; width: 35%; border-radius: 1em 0 1em 0; "><?php echo sprintf( __( 'Secure your form and avoid spam signups with Email Subscribers Starter Plan <a target="_blank" style="font-weight: bold; cursor:pointer; text-decoration:none" href="%s">Get started</a>',
										'email-subscribers' ), $url ) ?></div>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>

		<?php

	}

	public function save_form( $id, $data ) {

		global $wpdb;

		$form_data = self::prepare_form_data( $data );

		if ( ! empty( $id ) ) {
			$form_data['updated_at'] = ig_get_current_date_time();

			// We don't want to change the created_at date for update
			unset( $form_data['created_at'] );
			$return = $wpdb->update( IG_FORMS_TABLE, $form_data, array( 'id' => $id ) );
		} else {
			$return = $wpdb->insert( IG_FORMS_TABLE, $form_data );
		}

		return $return;
	}

	public static function prepare_form_data( $data ) {

		$form_data          = array();
		$name               = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$desc               = ! empty( $data['desc'] ) ? sanitize_text_field( $data['desc'] ) : '';
		$email_label        = ! empty( $data['email_label'] ) ? sanitize_text_field( $data['email_label'] ) : '';
		$email_place_holder = ! empty( $data['email_place_holder'] ) ? sanitize_text_field( $data['email_place_holder'] ) : '';
		$name_label         = ! empty( $data['name_label'] ) ? sanitize_text_field( $data['name_label'] ) : '';
		$name_place_holder  = ! empty( $data['name_place_holder'] ) ? sanitize_text_field( $data['name_place_holder'] ) : '';
		$button_label       = ! empty( $data['button_label'] ) ? sanitize_text_field( $data['button_label'] ) : '';
		$name_visible       = ( ! empty( $data['name_visible'] ) && $data['name_visible'] === 'yes' ) ? true : false;
		$name_required      = ( ! empty( $data['name_required'] ) && $data['name_required'] === 'yes' ) ? true : false;
		$list_visible       = ( ! empty( $data['list_visible'] ) && $data['list_visible'] === 'yes' ) ? true : false;
		$list_requried      = true;
		$list_ids           = ! empty( $data['lists'] ) ? $data['lists'] : array();
		$af_id              = ! empty( $data['af_id'] ) ? $data['af_id'] : 0;

		$body = array(
			array(
				'type'   => 'text',
				'name'   => 'Name',
				'id'     => 'name',
				'params' => array(
					'label'        => $name_label,
					'place_holder' => $name_place_holder,
					'show'         => $name_visible,
					'required'     => $name_required
				),

				'position' => 1
			),

			array(
				'type'   => 'text',
				'name'   => 'Email',
				'id'     => 'email',
				'params' => array(
					'label'        => $email_label,
					'place_holder' => $email_place_holder,
					'show'         => true,
					'required'     => true
				),

				'position' => 2
			),

			array(
				'type'   => 'checkbox',
				'name'   => 'Lists',
				'id'     => 'lists',
				'params' => array(
					'label'    => 'Lists',
					'show'     => $list_visible,
					'required' => $list_requried,
					'values'   => $list_ids
				),

				'position' => 3
			),

			array(
				'type'   => 'submit',
				'name'   => 'submit',
				'id'     => 'submit',
				'params' => array(
					'label'    => $button_label,
					'show'     => true,
					'required' => true
				),

				'position' => 4
			),

		);

		$settings = array(
			'lists'        => $list_ids,
			'desc'         => $desc,
			'form_version' => ES()->forms_db->version
		);

		$form_data['name']       = $name;
		$form_data['body']       = maybe_serialize( $body );
		$form_data['settings']   = maybe_serialize( $settings );
		$form_data['styles']     = null;
		$form_data['created_at'] = ig_get_current_date_time();
		$form_data['updated_at'] = null;
		$form_data['deleted_at'] = null;
		$form_data['af_id']      = $af_id;

		return $form_data;
	}

	public static function get_form_data_from_body( $data ) {

		$name          = ! empty( $data['name'] ) ? $data['name'] : '';
		$id            = ! empty( $data['id'] ) ? $data['id'] : '';
		$af_id         = ! empty( $data['af_id'] ) ? $data['af_id'] : '';
		$body_data     = maybe_unserialize( $data['body'] );
		$settings_data = maybe_unserialize( $data['settings'] );

		$desc         = ! empty( $settings_data['desc'] ) ? $settings_data['desc'] : '';
		$form_version = ! empty( $settings_data['form_version'] ) ? $settings_data['form_version'] : '0.1';

		$form_data = array( 'form_id' => $id, 'name' => $name, 'af_id' => $af_id, 'desc' => $desc, 'form_version' => $form_version );
		foreach ( $body_data as $d ) {
			if ( $d['id'] === 'name' ) {
				$form_data['name_visible']      = ( $d['params']['show'] === true ) ? 'yes' : '';
				$form_data['name_required']     = ( $d['params']['required'] === true ) ? 'yes' : '';
				$form_data['name_label']        = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
				$form_data['name_place_holder'] = ! empty( $d['params']['place_holder'] ) ? $d['params']['place_holder'] : '';
			} elseif ( $d['id'] === 'lists' ) {
				$form_data['list_visible']  = ( $d['params']['show'] === true ) ? 'yes' : '';
				$form_data['list_required'] = ( $d['params']['required'] === true ) ? 'yes' : '';
				$form_data['lists']         = ! empty( $d['params']['values'] ) ? $d['params']['values'] : array();
			} elseif ( $d['id'] === 'email' ) {
				$form_data['email_label']        = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
				$form_data['email_place_holder'] = ! empty( $d['params']['place_holder'] ) ? $d['params']['place_holder'] : '';
			} elseif ( $d['id'] === 'submit' ) {
				$form_data['button_label'] = ! empty( $d['params']['label'] ) ? $d['params']['label'] : '';
			}
		}

		return $form_data;
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

		$forms_table = IG_FORMS_TABLE;
		if ( $do_count_only ) {
			$sql = "SELECT count(*) as total FROM {$forms_table}";
		} else {
			$sql = "SELECT * FROM {$forms_table}";
		}

		$args = $query = array();

		$add_where_clause = true;

		$query[] = '( deleted_at IS NULL OR deleted_at = "0000-00-00 00:00:00" )';

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
	 * Delete a list record.
	 *
	 * @param int $id list ID
	 */
	public function delete_list( $ids ) {
		global $wpdb;

		$forms_table = IG_FORMS_TABLE;

		$ids          = "'" . implode( "', '", array_map( 'absint', $ids ) ) . "'";
		$current_date = ig_get_current_date_time();
		$query        = "UPDATE {$forms_table} SET deleted_at = %s WHERE id IN ({$ids})";
		$query        = $wpdb->prepare( $query, array( $current_date ) );

		$wpdb->query( $query );
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . IG_FORMS_TABLE;

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
			// case 'status':
			// 	return $this->status_label_map( $item[ $column_name ] );
			case 'created_at':
				return ig_es_format_date_time( $item[ $column_name ] );
				break;
			case 'shortcode':
				$shortcode = '[email-subscribers-form id="' . $item['id'] . '"]';

				return '<code>' . $shortcode . '</code>';
				break;
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
			'<input type="checkbox" name="forms[]" value="%s" />', $item['id']
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

		$list_nonce = wp_create_nonce( 'es_form' );

		$title = '<strong>' . stripslashes( $item['name'] ) . '</strong>';

		$page    = ig_es_get_request_data( 'page' );
		$actions = array(
			'edit'   => sprintf( __( '<a href="?page=%s&action=%s&form=%s&_wpnonce=%s">Edit</a>', 'email-subscribers' ), esc_attr( $page ), 'edit', absint( $item['id'] ), $list_nonce ),
			'delete' => sprintf( __( '<a href="?page=%s&action=%s&form=%s&_wpnonce=%s" onclick="return checkDelete()">Delete</a>', 'email-subscribers' ), esc_attr( $page ), 'delete', absint( $item['id'] ), $list_nonce )
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
			'cb'         => '<input type="checkbox" />',
			'name'       => __( 'Name', 'email-subscribers' ),
			'shortcode'  => __( 'Shortcode', 'email-subscribers' ),
			'created_at' => __( 'Created', 'email-subscribers' )
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
			<?php submit_button( __( 'Search Forms', 'email-subscribers' ), 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
        </p>
	<?php }

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();


		/** Process bulk action */
		$this->process_bulk_action();

		$search_str = ig_es_get_request_data( 's' );
		$this->search_box( $search_str, 'form-search-input' );

		$per_page     = $this->get_items_per_page( self::$option_per_page, 25 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		) );

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	public function process_bulk_action() {

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = ig_es_get_request_data( '_wpnonce' );

			if ( ! wp_verify_nonce( $nonce, 'es_form' ) ) {
				$message = __( 'You do not have permission to delete this form.', 'email-subscribers' );
				ES_Common::show_message( $message, 'error' );
			} else {

				$form = ig_es_get_request_data( 'form' );

				$this->delete_list( array( $form ) );
				$message = __( 'Form has been deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}
		}

		$action  = ig_es_get_request_data( 'action' );
		$action2 = ig_es_get_request_data( 'action2' );
		// If the delete bulk action is triggered
		if ( ( 'bulk_delete' === $action ) || ( 'bulk_delete' === $action2 ) ) {

			$forms = ig_es_get_request_data( 'forms' );

			if ( ! empty( $forms ) > 0 ) {
				$this->delete_list( $forms );

				$message = __( 'Form(s) have been deleted successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			} else {
				$message = __( 'Please select form(s) to delete.', 'email-subscribers' );
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
		_e( 'No Forms avaliable.', 'email-subscribers' );
	}
}
