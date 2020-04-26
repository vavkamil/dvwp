<?php

/**
* A class designed to queue up and register this plugins widgets.
*
* @package   SocialWarfare\Functions\Widgets
* @copyright Copyright (c) 2018, Warfare Plugins, LLC
* @license   GPL-3.0+
* @since     3.0.0 | 22 FEB 2018 | Class Created
*
*/
class SWP_Widget_Loader {


	/**
	 * The magic method used to instantiate this class.
	 *
	 * @since  3.0.0
	 * @param  none
	 * @return none
	 * @access public
	 *
	 */
	public function __construct() {
		add_action( 'widgets_init', array( $this , 'register_widgets' ) );
	}


	/**
	 * Autoregisters all widgets which extend SWP_Widget.
	 *
	 * @since  Since 3.5.0 | 13 DEC 2018 Ported from SWP_Widget
	 * @filter swp_popular_posts_widget
	 * @return void
	 *
	 */
	function register_widgets() {
		$widgets = apply_filters( 'swp_widgets', array() );
		// Apply default values after filtering to guarantee ours are included.

		if (!is_array($widgets)) {
			$widgets = array();
		}
		$widgets = array_merge( array( 'swp_popular_posts_widget' ), $widgets );

		foreach( $widgets as $widget ) {
			if ( class_exists( $widget ) ) {
				register_widget( $widget );
			}
		}
	}
}
