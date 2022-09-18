<?php

class ES_Handle_Sync_Wp_User {

	public static $instance;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );

		// Sync upcoming WordPress users
		add_action( 'user_register', array( $this, 'sync_registered_wp_user' ) );
		add_action( 'ig_es_sync_users_tabs_wordpress', array( $this, 'sync_wordpress_users_settings' ) );

	}

	public function sync_wordpress_users_settings( $wordpress_tab ) {

		if ( ! empty( $wordpress_tab['indicator_option'] ) ) {
			update_option( $wordpress_tab['indicator_option'], 'no' );
		}

		$submitted = ig_es_get_post_data( 'submitted' );
		if ( 'submitted' === $submitted ) {

			$form_data = ig_es_get_post_data( 'form_data' );

			$error = false;
			if ( ! empty( $form_data['es_registered'] ) && 'YES' === $form_data['es_registered'] ) {
				$list_id = ! empty( $form_data['es_registered_group'] ) ? $form_data['es_registered_group'] : 0;
				if ( $list_id === 0 ) {
					$message = __( 'Please select list', 'email-subscribers' );
					ES_Common::show_message( $message, 'error' );
					$error = true;
				}
			}

			if ( ! $error ) {
				update_option( 'ig_es_sync_wp_users', $form_data );
				$message = __( 'Settings have been saved successfully!', 'email-subscribers' );
				ES_Common::show_message( $message, 'success' );
			}
		}

		$default_form_data = array(
			'es_registered'       => 'NO',
			'es_registered_group' => 0,
		);

		$form_data = get_option( 'ig_es_sync_wp_users', array() );
		$form_data = wp_parse_args( $form_data, $default_form_data );

		?>

        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="tag-image">
						<?php echo __( 'Sync WordPress Users?', 'email-subscribers' ); ?>
                    </label>
                    <p class="description"><?php _e( "Whenever someone signup, it will automatically be added into selected list", 'email-subscribers' ); ?></p>
                </th>
                <td>
                    <select name="form_data[es_registered]" id="es_email_status">
                        <option value='NO' <?php if ( $form_data['es_registered'] == 'NO' ) {
							echo "selected='selected'";
						} ?>><?php echo __( 'No', 'email-subscribers' ); ?></option>
                        <option value='YES' <?php if ( $form_data['es_registered'] == 'YES' ) {
							echo "selected='selected'";
						} ?>><?php echo __( 'Yes', 'email-subscribers' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="tag-display-status">
						<?php echo __( 'Select List', 'email-subscribers' ); ?>
                    </label>
                    <p class="description"><?php _e( "Select the list in which newly registered user will be subscribed to", 'email-subscribers' ); ?></p>
                </th>
                <td>
                    <select name="form_data[es_registered_group]">
						<?php echo ES_Common::prepare_list_dropdown_options( $form_data['es_registered_group'], 'Select List' ); ?>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>
        <input type="hidden" name="submitted" value="submitted"/>
        <p style="padding-top:5px;">
            <input type="submit" class="button-primary" value="<?php echo __( 'Save Settings', 'email-subscribers' ); ?>"/>
        </p>

		<?php
	}

	public function plugin_menu() {
		add_submenu_page( null, 'Sync', __( 'Sync', 'email-subscribers' ), get_option( 'es_roles_subscriber', true ), 'es_sync', array( $this, 'prepare_sync_user' ) );
	}

	public function sync_registered_wp_user( $user_id ) {
		//get option
		$ig_es_sync_wp_users          = get_option( 'ig_es_sync_wp_users', 'norecord' );
		$ig_es_sync_unserialized_data = maybe_unserialize( $ig_es_sync_wp_users );
		$ig_es_registered             = ( $ig_es_sync_unserialized_data != 'norecord' ) ? $ig_es_sync_unserialized_data['es_registered'] : 'NO';
		if ( $ig_es_sync_wp_users != 'norecord' && 'YES' === $ig_es_registered ) {
			$list_id = $ig_es_sync_unserialized_data['es_registered_group'];
			//get user info
			$user_info = get_userdata( $user_id );
			if ( ! ( $user_info instanceof WP_User ) ) {
				return false;
			}
			$user_firstname = $user_info->display_name;


			$email = $user_info->user_email;
			if ( empty( $user_firstname ) ) {
				$user_firstname = ES_Common::get_name_from_email( $email );
			}
			//prepare data
			$data = array(
				'first_name' => $user_firstname,
				'email'      => $email,
				'source'     => 'wp',
				'status'     => 'verified',
				'hash'       => ES_Common::generate_guid(),
				'created_at' => ig_get_current_date_time(),
				'wp_user_id' => $user_id
			);

			do_action( 'ig_es_add_contact', $data, $list_id );
		}

		return true;

	}

	public function prepare_sync_user() {

		$audience_tab_main_navigation = array();
		$active_tab                   = 'sync';
		$audience_tab_main_navigation = apply_filters( 'ig_es_audience_tab_main_navigation', $active_tab, $audience_tab_main_navigation );

		?>
        <div class="wrap">
            <h2> <?php _e( 'Audience > Sync Contacts', 'email-subscribers' );
				ES_Common::prepare_main_header_navigation( $audience_tab_main_navigation );
				?>
            </h2>
			<?php $this->sync_users_callback(); ?>
        </div>

		<?php
	}

	public function sync_users_callback() {

		$logger = get_ig_logger();
		$logger->trace( 'Sync Users' );
		$active_tab = ig_es_get_request_data( 'tab', 'wordpress' );

		$tabs = array(
			'wordpress' => array(
				'name' => __( 'WordPress', 'email-subscribers' ),
				'url'  => admin_url( 'admin.php?page=es_subscribers&action=sync&tab=wordpress' )
			)
		);

		$tabs = apply_filters( 'ig_es_sync_users_tabs', $tabs );
		?>
        <h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $key => $tab ) {
				$tab_url = admin_url( 'admin.php?page=es_subscribers&action=sync' );
				$tab_url = add_query_arg( 'tab', $key, $tab_url );

				$indicator_option = ! empty( $tab['indicator_option'] ) ? $tab['indicator_option'] : '';
				$indicator_label  = '';
				$indicator_class  = '';
				if ( ! empty( $indicator_option ) && ( get_option( $indicator_option, 'yes' ) == 'yes' ) ) {
					$indicator_label = ! empty( $tab['indicator_label'] ) ? $tab['indicator_label'] : '';
					$indicator_class = ! empty( $tab['indicator_type'] ) ? 'ig-es-indicator-' . $tab['indicator_type'] : 'ig-es-indicator-new';
				}
				?>
                <a class="nav-tab <?php echo $key === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $tab_url ); ?>"><?php echo esc_html__( $tab['name'] ); ?>
					<?php if ( ! empty( $indicator_label ) ) { ?>
                        <span class="ig-es-indicator<?php echo " " . $indicator_class; ?>"><?php echo $indicator_label; ?></span>
					<?php } ?>
                </a>
			<?php } ?>
        </h2>
        <form name="form_sync" id="form_sync" method="post" action="#">
			<?php
			$from = ! empty( $tabs[ $active_tab ]['from'] ) ? $tabs[ $active_tab ]['from'] . '_' : '';
			do_action( $from . 'ig_es_sync_users_tabs_' . $active_tab, $tabs[ $active_tab ] ); ?>
        </form>

		<?php
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
