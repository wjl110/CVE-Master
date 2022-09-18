<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Widget extends WP_Widget {

	function __construct() {
		parent::__construct( 'email_subscriber_widget', __( 'Email Subscribers Widget', 'email-subscribers' ), array( 'description' => __( 'Email Subscribers', 'email-subscribers' ) ) );
	}

	public function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$display_name      = isset( $instance['display_name'] ) ? esc_attr( $instance['display_name'] ) : '';
		$subscribers_group = isset( $instance['subscribers_group'] ) ? esc_attr( $instance['subscribers_group'] ) : '';
		$desc              = isset( $instance['short_desc'] ) ? esc_attr( $instance['short_desc'] ) : '';

		$name = strtolower( $display_name ) != 'no' ? 'yes' : '';

		$data['name_visible'] = $name;
		$data['list_visible'] = 'no';
		$data['lists']        = array();
		$data['form_id']      = 0;
		$data['list']         = $subscribers_group;
		$data['desc']         = $desc;

		ES_Shortcode::render_form( $data );

		echo $args['after_widget'];
	}

	public function form( $instance ) {

		$title             = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$short_desc        = isset( $instance['short_desc'] ) ? esc_attr( $instance['short_desc'] ) : '';
		$display_name      = isset( $instance['display_name'] ) ? esc_attr( $instance['display_name'] ) : '';
		$subscribers_group = isset( $instance['subscribers_group'] ) ? esc_attr( $instance['subscribers_group'] ) : '';

		$display_names = array( 'yes' => __( 'Yes', 'email-subscribers' ), 'no' => __( 'No', 'email-subscribers' ) );

		?>

        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'short_desc' ); ?>"><?php _e( 'Short description' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'short_desc' ); ?>" name="<?php echo $this->get_field_name( 'short_desc' ); ?>" type="text" value="<?php echo esc_attr( $short_desc ); ?>">
        </p>
        <p>
            <label for="widget-email-subscribers-2-es_name"><?php _e( 'Display Name Field' ); ?></label>
            <select id="<?php echo $this->get_field_id( 'display_name' ); ?>" name="<?php echo $this->get_field_name( 'display_name' ); ?>" class="widefat" style="width:100%;">
				<?php foreach ( $display_names as $name ) { ?>
                    <option <?php selected( $display_name, $name ); ?> value="<?php echo $name; ?>"><?php echo $name; ?></option>
				<?php } ?>
            </select>
        </p>
        <p>
            <label for="widget-email-subscribers-2-es_group"><?php _e( 'Subscriber List' ); ?></label>
            <select id="<?php echo $this->get_field_id( 'subscribers_group' ); ?>" name="<?php echo $this->get_field_name( 'subscribers_group' ); ?>" class="widefat" style="width:100%;">
				<?php echo ES_Common::prepare_list_dropdown_options( $subscribers_group ); ?>
            </select>
        </p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance                      = array();
		$instance['title']             = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['short_desc']        = ( ! empty( $new_instance['short_desc'] ) ) ? strip_tags( $new_instance['short_desc'] ) : '';
		$instance['display_name']      = ( ! empty( $new_instance['display_name'] ) ) ? strip_tags( $new_instance['display_name'] ) : '';
		$instance['subscribers_group'] = ( ! empty( $new_instance['subscribers_group'] ) ) ? strip_tags( $new_instance['subscribers_group'] ) : '';

		return $instance;
	}
}