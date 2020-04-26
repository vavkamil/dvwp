<?php

class WFU_Widget extends WP_Widget {
	
	function __construct() {
		parent::__construct(
			'wordpress_file_upload_widget', // Base ID
			WFU_WIDGET_PLUGINFORM_TITLE, // Name
			array( 'description' => WFU_WIDGET_PLUGINFORM_DESCRIPTION ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		
		$shortcode_attrs = ! empty( $instance['shortcode_attrs'] ) ? $instance['shortcode_attrs'] : '';
		$shortcode_id = ! empty( $instance['shortcode_id'] ) ? $instance['shortcode_id'] : $this->update_external();

		echo do_shortcode('[wordpress_file_upload uploadid="'.$shortcode_id.'" widgetid="'.$this->id.'" placements="filename+selectbutton/uploadbutton+progressbar/message" fitmode="responsive" '.$shortcode_attrs.']');
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : WFU_WIDGET_SIDEBAR_DEFAULTTITLE;
		$shortcode_attrs = ! empty( $instance['shortcode_attrs'] ) ? $instance['shortcode_attrs'] : '';
		$shortcode_id = ! empty( $instance['shortcode_id'] ) ? $instance['shortcode_id'] : '';
		if ( $shortcode_id == '' ) {
			mt_srand((double)microtime()*1000000);
			$shortcode_id = (string)mt_rand(1000, 9999);
		}
		?>
		<input type="hidden" id="<?php echo $this->get_field_id( 'shortcode_id' ); ?>" name="<?php echo $this->get_field_name( 'shortcode_id' ); ?>" value="<?php echo esc_attr( $shortcode_id ); ?>" />
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'shortcode_attrs' ); ?>"><?php _e( 'Shortcode Attributes:' ); ?></label> 
		<textarea class="widefat" id="<?php echo $this->get_field_id( 'shortcode_attrs' ); ?>" name="<?php echo $this->get_field_name( 'shortcode_attrs' ); ?>" value="<?php echo esc_attr( $shortcode_attrs ); ?>"><?php echo esc_attr( $shortcode_attrs ); ?></textarea>
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['shortcode_id'] = ( ! empty( $new_instance['shortcode_id'] ) ) ? strip_tags( $new_instance['shortcode_id'] ) : '';
		$instance['shortcode_attrs'] = ( ! empty( $new_instance['shortcode_attrs'] ) ) ? strip_tags( $new_instance['shortcode_attrs'] ) : '';

		return $instance;
	}
	
	/**
	 * Get the shortcode of this plugin instance.
	 *
	 * @return string with shortcode (excluding the widgetid attribute which is a hidden attribute).
	 */
	public function shortcode() {
		$all_instances = $this->get_settings();
		$instance = $all_instances[$this->number];
		$shortcode = '[wordpress_file_upload uploadid="'.$instance['shortcode_id'].'" placements="filename+selectbutton/uploadbutton+progressbar/message" fitmode="responsive" '.$instance['shortcode_attrs'].']';

		return $shortcode;
	}
	
	/**
	 * Update widget shortcode attributes from Shortcode Composer or other external function.
	 *
	 * @param string $shortcode_attrs the new shortcode attributes.
	 */
	public function update_external($shortcode_attrs = '') {
		$all_instances = $this->get_settings();
		$instance = $all_instances[$this->number];
		$_POST['widget-'.$this->id_base][$this->number]['title'] = ! empty( $instance['title'] ) ? $instance['title'] : WFU_WIDGET_SIDEBAR_DEFAULTTITLE;
		$_POST['widget-'.$this->id_base][$this->number]['shortcode_attrs'] = $shortcode_attrs;
		$shortcode_id = ! empty( $instance['shortcode_id'] ) ? $instance['shortcode_id'] : '';
		if ( $shortcode_id == '' ) {
			mt_srand((double)microtime()*1000000);
			$shortcode_id = (string)mt_rand(1000, 9999);
		}
		$_POST['widget-'.$this->id_base][$this->number]['shortcode_id'] = $shortcode_id;
		$this->update_callback();
		return $shortcode_id;
	}

}

?>
