<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Form_Widget extends WP_Widget {

	function __construct() {
		parent::__construct( 'email-subscribers-form', __( 'Email Subscribers', 'email-subscribers' ), array( 'description' => __( 'Email Subscribers Form', 'email-subscribers' ) ) );
	}

	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$form_id = isset( $instance['form_id'] ) ? esc_attr( $instance['form_id'] ) : '';

		$form_data = array();
		if ( ! empty( $form_id ) ) {

			$form = ES()->forms_db->get_form_by_id( $form_id );

			$form_data = ES_Forms_Table::get_form_data_from_body( $form );
		}


		$data                       = array();
		$data['form_id']            = 0;
		$data['list']               = '';
		$data['name_visible']       = ( ! empty( $form_data['name_visible'] ) && 'yes' === $form_data['name_visible'] ) ? 'yes' : '';
		$data['name_required']      = ( ! empty( $form_data['name_required'] ) && 'yes' === $form_data['name_required'] ) ? 'yes' : '';
		$data['list_visible']       = ( ! empty( $form_data['list_visible'] ) && 'yes' === $form_data['list_visible'] ) ? 'yes' : '';
		$data['lists']              = ( ! empty( $form_data['lists'] ) ) ? $form_data['lists'] : array();
		$data['desc']               = ( ! empty( $form_data['desc'] ) ) ? $form_data['desc'] : '';
		$data['name_label']         = ( ! empty( $form_data['name_label'] ) ) ? $form_data['name_label'] : '';
		$data['name_place_holder']  = ( ! empty( $form_data['name_place_holder'] ) ) ? $form_data['name_place_holder'] : '';
		$data['email_label']        = ( ! empty( $form_data['email_label'] ) ) ? $form_data['email_label'] : '';
		$data['email_place_holder'] = ( ! empty( $form_data['email_place_holder'] ) ) ? $form_data['email_place_holder'] : '';
		$data['button_label']       = ( ! empty( $form_data['button_label'] ) ) ? $form_data['button_label'] : '';

		ES_Shortcode::render_form( $data );

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$selected_form_id = isset( $instance['form_id'] ) ? esc_attr( $instance['form_id'] ) : '';
		$title            = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		?>

        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="widget-email-subscribers-2-es_group"><?php _e( 'Forms' ); ?></label>
            <select id="<?php echo $this->get_field_id( 'form_id' ); ?>" name="<?php echo $this->get_field_name( 'form_id' ); ?>" class="widefat" style="width:100%;">
				<?php echo ES_Common::prepare_form_dropdown_options( $selected_form_id, null ); ?>
            </select>
        </p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance            = array();
		$instance['title']   = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['form_id'] = ( ! empty( $new_instance['form_id'] ) ) ? strip_tags( $new_instance['form_id'] ) : '';

		return $instance;
	}
}