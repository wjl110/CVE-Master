<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Templates_Table {

	static $instance;

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'es_template_meta_box_add' ) );
		add_action( 'save_post', array( $this, 'es_template_meta_save' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'preview_button' ) );
		add_filter( 'manage_edit-es_template_columns', array( $this, 'add_new_columns' ), 10, 1 );
		add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ) );
		add_action( 'admin_footer', array( $this, 'add_custom_button' ) );
		add_action( 'edit_form_after_title', array( $this, 'add_template_type' ) );
		//duplicate template
		add_filter( 'post_row_actions', array( &$this, 'add_message_action' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'duplicate_message' ), 10, 1 );
	}

	public function add_template_type() {
		global $post;
		if ( ! ( is_object( $post ) && 'es_template' === $post->post_type ) ) {
			return;
		}
		$values = get_post_custom( $post->ID );

		$selected      = isset( $values['es_template_type'] ) ? esc_attr( $values['es_template_type'][0] ) : '';
		$template_type = array(
			'newsletter'        => __( 'Broadcast', 'email-subscribers' ),
			'post_notification' => __( 'Post Notification', 'email-subscribers' )
		);
		$template_type = apply_filters( 'es_template_type', $template_type );
		?>
        <p>
            <label for="es_template_type"><strong><?php _e( 'Select your Email Template Type', 'email-subscirbers' ); ?></strong></label></br>
            <select name="es_template_type" id="es_template_type">
				<?php
				if ( ! empty( $template_type ) ) {
					foreach ( $template_type as $key => $value ) {
						echo "<option value=" . $key . " " . selected( $selected, $key, false ) . ">" . $value . "</option>";
					}
				}
				?>

            </select>
        </p>
		<?php
	}

	public function es_template_meta_box_add() {
		add_meta_box( 'es_template_meta_box', 'Available Keywords', array( $this, 'es_template_type_meta_box' ), 'es_template', 'normal', 'high' );
	}

	public function es_template_type_meta_box( $post ) {

		if ( ! ( is_object( $post ) && 'es_template' === $post->post_type ) ) {
			return;
		}
		?>
        <p id="post_notification">
            <a href="https://www.icegram.com/documentation/es-what-are-the-available-keywords-in-the-post-notifications/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_docs_help_page" target="_blank"><?php _e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php _e( 'for Post Notification: ', 'email-subsribers' ); ?> {{FIRSTNAME}},
            {{LASTNAME}}, {{NAME}}, {{EMAIL}},
            {{DATE}}, {{POSTTITLE}}, {{POSTIMAGE}}, {{POSTEXCERPT}}, {{POSTDESC}},
            {{POSTAUTHOR}}, {{POSTLINK}}, {{POSTLINK-WITHTITLE}}, {{POSTLINK-ONLY}}, {{POSTFULL}} </p>
        <p id="newsletter">
            <a href="https://www.icegram.com/documentation/es-what-are-the-available-keywords-in-the-newsletters/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_docs_help_page" target="_blank"><?php _e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php _e( 'for Broadcast:', 'email-subscribers' ); ?> {{FIRSTNAME}}, {{LASTNAME}}, {{NAME}},
            {{EMAIL}} </p>
         <div id="post_digest">
         	<span style="font-size: 0.8em; margin-left: 0.3em; padding: 2px; background: #e66060; color: #fff; border-radius: 2px; ">Pro</span>&nbsp;
            <a href="https://www.icegram.com/send-post-digest-using-email-subscribers-plugin/?utm_source=es&amp;utm_medium=in_app&amp;utm_campaign=view_post_digest_post" target="_blank"><?php _e( 'Available Keywords', 'email-subscribers' ); ?></a> <?php _e( 'for Post Digest:', 'email-subscribers' ); ?> 
            {{FIRSTNAME}}, {{LASTNAME}}, {{NAME}}<div class="post_digest_block"> {{POSTDIGEST}} <br/><?php _e( 'Any keywords related Post Notification', 'email-subscribers' ); ?> <br/>{{/POSTDIGEST}} </div>
        </div>
		<?php
	}

	public function es_template_meta_save( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( is_int( wp_is_post_revision( $post ) ) ) {
			return;
		}
		if ( is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( $post->post_type != 'es_template' ) {
			return;
		}


		if ( isset( $_POST['es_template_type'] ) ) {
		    $es_template_type = ig_es_get_post_data('es_template_type');
			update_post_meta( $post_id, 'es_template_type', esc_attr( $es_template_type ) );
		}
	}


	public function preview_button( $post ) {
		if ( is_object( $post ) && 'es_template' === $post->post_type ) {
			$post_id = $post->ID;
			?>

            <div class="misc-pub-section">
                <div id="" class="es_preview_button" style="display: block;">
                    <a href="<?php echo admin_url(); ?>admin.php?page=es_template_preview&post=<?php echo $post_id; ?>&preview=true&preview_id=<?php echo $post_id ?>" target="_blank" class="button button-primary es_preview"><?php _e( 'Preview Template', 'email-subscribers' ); ?></a>
                    <div class="clear"></div>
                </div>
            </div>

			<?php
		}
	}

	public function add_custom_button() {
		$screen = get_current_screen();
		if ( $screen->post_type == 'es_template' ) {
			?>
            <script type="text/javascript">
				jQuery('<a href="admin.php?page=es_campaigns" class="page-title-action">Campaigns</a>').insertBefore(".wp-header-end");
            </script>
			<?php
		}
	}

	public function es_template_preview_callback() {

		$template_id = ig_es_get_request_data('post');

		$template = get_post( $template_id, ARRAY_A );

		if ( $template ) {
			$current_user = wp_get_current_user();
			$username     = $current_user->user_login;
			$useremail    = $current_user->user_email;

			$es_template_body = $template['post_content'];

			$es_template_type = get_post_meta( $template_id, 'es_template_type', true );


			if ( 'post_notification' === $es_template_type ) {
				$args         = array( 'numberposts' => '1', 'order' => 'DESC', 'post_status' => 'publish' );
				$recent_posts = wp_get_recent_posts( $args );

				if ( count( $recent_posts ) > 0 ) {
					$recent_post = array_shift( $recent_posts );

					$post_id          = $recent_post['ID'];
					$es_template_body = ES_Handle_Post_Notification::prepare_body( $es_template_body, $post_id, $template_id );
				}
			} else {
				$es_template_body = ES_Common::es_process_template_body( $es_template_body, $template_id );
			}

			$es_template_body = str_replace( '{{NAME}}', $username, $es_template_body );
			$es_template_body = str_replace( '{{EMAIL}}', $useremail, $es_template_body );

			if ( has_post_thumbnail( $template_id ) ) {
				$image_array = wp_get_attachment_image_src( get_post_thumbnail_id( $template_id ), 'full' );
				$image       = '<img src="' . $image_array[0] . '" class="img-responsive" alt="Image for Post ' . $template_id . '" />';
			} else {
				$image = '';
			}
			$html = '';
			$html .= '<style type="text/css">
	.es-sidebar {
		width: 23%;
	    background-color: rgb(230, 230, 230);
	    padding:15px;
	    border-right: 1px solid #bdbdbd;
	}
	.es-preview {
	    float: left;
		padding:15px;
		width: 70%;
		background-color:#FFF;
		font-size:16px;
	}
</style>
<div class="wrap">
	<div class="tool-box">
		<div class="es-main" style="display:flex;">
			<div class="es-sidebar">
				<h2 style="margin-bottom:1em;">
					Template Preview					<a class="add-new-h2" href="' . admin_url() . 'admin.php?page=es-general-information">Help</a>
				</h2>' . Email_Subscribers_Admin::es_feedback() . '
				<p>
					<a class="button-primary"  href="' . admin_url() . 'post.php?post=' . $template_id . '&action=edit">Edit</a>
				</p>
				<p>
					This is how your email may look.<br><br>Note: Different email services (like gmail, yahoo etc) display email content differently. So there could be a slight variation on how your customer will view the email content.				</p>
			</div>
			<div class="es-preview">' . $es_template_body . '</div>
			<div style="clear:both;"></div>
		</div>
		<div style="clear:both;"></div>
		</div>
		</div>';
			echo apply_filters( 'the_content', $html );
		} else {
			echo 'Please publish it or save it as a draft';
		}

	}

	public function add_new_columns( $existing_columns ) {

		$date = $existing_columns['date'];
		unset( $existing_columns['date'] );

		$existing_columns['es_template_type']      = __( 'Template Type', 'email-subscribers' );
		$existing_columns['es_template_thumbnail'] = __( 'Thumbnail', 'email-subscribers' );
		$existing_columns['date']                  = $date;

		return $existing_columns;

	}

	public function custom_columns( $column ) {

		global $post;

		$es_template_thumbnail      = get_the_post_thumbnail( $post->ID, array( '200', '200' ) );
		$default_template_thumbnail = '<img src="' . EMAIL_SUBSCRIBERS_URL . '/admin/images/envelope.png" />';
		$es_template_thumbnail      = apply_filters( 'es_template_thumbnail', $es_template_thumbnail );
		$es_template_thumbnail      = ( ! empty( $es_template_thumbnail ) ) ? $es_template_thumbnail : $default_template_thumbnail;
		switch ( $column ) {
			case 'es_template_type':
				$type = get_post_meta( $post->ID, 'es_template_type', true );
				$type = sanitize_text_field(strtolower( $type ));
				$type = ( 'newsletter' === $type ) ? __( 'Broadcast', 'email-subscribers' ) : $type;
				echo $type = ucwords( str_replace( '_', ' ', $type ) );
				break;
			case 'es_template_thumbnail' :
				echo $es_template_thumbnail;
				break;
			default:
				break;
		}

		return $column;
	}

	function add_message_action( $actions, $post ) {
		if ( $post->post_type != 'es_template' ) {
			return $actions;
		}
		$actions['duplicate_template'] = '<a class="es-duplicate-template"  href="post.php?template_id=' . $post->ID . '&action=duplicate-template" >' . __( 'Duplicate', 'email-subscribers' ) . '</a>';

		return $actions;
	}

	function duplicate_message() {
		$action      = ig_es_get_request_data( 'action' );
		$template_id = ig_es_get_request_data( 'template_id' );
		if ( ! empty( $template_id ) && 'duplicate-template' === $action ) {
			//duplicate tempalte
			$this->duplicate_in_db( $template_id );
			// $location = admin_url( 'post.php?post='.$duplicate_template_id.'&action=edit');
			$location = admin_url( 'edit.php?post_type=es_template' );
			wp_safe_redirect( $location );
			exit;
		}
	}

	function duplicate_in_db( $original_id ) {
		// Get access to the database
		global $wpdb;
		// Get the post as an array
		$duplicate = get_post( $original_id, 'ARRAY_A' );
		// Modify some of the elements
		$duplicate['post_title']  = $duplicate['post_title'] . ' ' . __( 'Copy', 'icegram' );
		$duplicate['post_status'] = 'draft';
		// Set the post date
		$timestamp = current_time( 'timestamp', 0 );

		$duplicate['post_date'] = date( 'Y-m-d H:i:s', $timestamp );

		// Remove some of the keys
		unset( $duplicate['ID'] );
		unset( $duplicate['guid'] );
		unset( $duplicate['comment_count'] );

		// Insert the post into the database
		$duplicate_id = wp_insert_post( $duplicate );

		// Duplicate all taxonomies/terms
		$taxonomies = get_object_taxonomies( $duplicate['post_type'] );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_post_terms( $original_id, $taxonomy, array( 'fields' => 'names' ) );
			wp_set_object_terms( $duplicate_id, $terms, $taxonomy );
		}

		// Duplicate all custom fields
		$custom_fields = get_post_custom( $original_id );
		foreach ( $custom_fields as $key => $value ) {
			add_post_meta( $duplicate_id, $key, maybe_unserialize( $value[0] ) );
		}

		return $duplicate_id;
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
