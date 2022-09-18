<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ES_Old_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'widget_text elp-widget', 'description' => __( 'Email Subscribers', 'email-subscribers' ) );
		parent::__construct( 'email-subscribers', __( 'Email Subscribers', 'email-subscribers' ), $widget_ops );
	}

	function widget( $args, $instance ) {

		$title = apply_filters( 'widget_title', $instance['es_title'] );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$display_name      = isset( $instance['es_name'] ) ? esc_attr( $instance['es_name'] ) : '';
		$subscribers_group = isset( $instance['es_group'] ) ? esc_attr( $instance['es_group'] ) : '';
		$desc              = isset( $instance['es_desc'] ) ? esc_attr( $instance['es_desc'] ) : '';

		$name = strtolower( $display_name ) != 'no' ? 'yes' : '';

		$list = ES()->lists_db->get_list_by_name($subscribers_group);
		if(!empty($list)) {
		    $list_id = $list['id'];
        }

		$data['name_visible'] = $name;
		$data['list_visible'] = 'no';
		$data['lists']        = array();
		$data['form_id']      = 0;
		$data['list']         = $list_id;
		$data['desc']         = $desc;

		ES_Shortcode::render_form( $data );

		echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {
		$instance             = $old_instance;
		$instance['es_title'] = ( ! empty( $new_instance['es_title'] ) ) ? strip_tags( $new_instance['es_title'] ) : '';
		$instance['es_desc']  = ( ! empty( $new_instance['es_desc'] ) ) ? strip_tags( $new_instance['es_desc'] ) : '';
		$instance['es_name']  = ( ! empty( $new_instance['es_name'] ) ) ? strip_tags( $new_instance['es_name'] ) : '';
		$instance['es_group'] = ( ! empty( $new_instance['es_group'] ) ) ? strip_tags( $new_instance['es_group'] ) : '';

		return $instance;
	}

	function form( $instance ) {
		$defaults = array(
			'es_title' => '',
			'es_desc'  => '',
			'es_name'  => '',
			'es_group' => ''
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		$es_title = $instance['es_title'];
		$es_desc  = $instance['es_desc'];
		$es_name  = $instance['es_name'];
		$es_group = $instance['es_group'];
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'es_title' ); ?>"><?php echo __( 'Widget Title', 'email-subscribers' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'es_title' ); ?>" name="<?php echo $this->get_field_name( 'es_title' ); ?>" type="text" value="<?php echo $es_title; ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'es_desc' ); ?>"><?php echo __( 'Short description about subscription form', 'email-subscribers' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'es_desc' ); ?>" name="<?php echo $this->get_field_name( 'es_desc' ); ?>" type="text" value="<?php echo $es_desc; ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'es_name' ); ?>"><?php echo __( 'Display Name Field', 'email-subscribers' ); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id( 'es_name' ); ?>" name="<?php echo $this->get_field_name( 'es_name' ); ?>">
                <option value="YES" <?php $this->es_selected( $es_name == 'YES' ); ?>><?php echo __( 'YES', 'email-subscribers' ); ?></option>
                <option value="NO" <?php $this->es_selected( $es_name == 'NO' ); ?>><?php echo __( 'NO', 'email-subscribers' ); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'es_group' ); ?>"><?php echo __( 'Subscriber Group', 'email-subscribers' ); ?></label>
            <select class="widefat" name="<?php echo $this->get_field_name( 'es_group' ); ?>" id="<?php echo $this->get_field_id( 'es_group' ); ?>">
				<?php
				$groups = ES()->lists_db->get_list_id_name_map();
				if ( count( $groups ) > 0 ) {
					$i = 1;
					foreach ( $groups as $group ) {
						?>
                        <option value="<?php echo esc_html( stripslashes( $group ) ); ?>" <?php if ( stripslashes( $es_group ) == $group ) {
							echo 'selected="selected"';
						} ?>>
							<?php echo stripslashes( $group ); ?>
                        </option>
						<?php
					}
				}
				?>
            </select>
        </p>
		<?php
	}

	function es_selected( $var ) {
		if ( $var == 1 || $var == true ) {
			echo 'selected="selected"';
		}
	}
}