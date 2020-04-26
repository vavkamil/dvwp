<?php

/**
 * A class of functions used to render shortcodes for the buttons panel.
 *
 * @package   SocialWarfare\Frontend-Output
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0
 * @since     3.0.0 | 19 FEB 2018 | Refactored into a class-based system
 *
 */
class SWP_Buttons_Panel_Shortcode {


	/**
	 * Constructs a new SWP_Buttons_Panel_Shortcode instance
	 *
	 * This function is used to add our shortcodes to WordPress' registry of
	 * shortcodes and to map our functions to each one.
	 *
	 * @since  3.0.0
	 * @param  none
	 * @return none
	 *
	 */
    public function __construct() {
		add_shortcode( 'social_warfare', array( $this, 'buttons_shortcode' ) );
		add_shortcode( 'socialWarfare', array( $this, 'buttons_shortcode' ) );
	}


	/**
	 * Processing the shortcodes that populate a
	 * set of social sharing buttons directly in a WordPress post.
	 *
	 * This function will accept an array of arguments which WordPress
	 * will create from the shortcode attributes.
	 *
	 * @since  3.0.0
	 * @param  $atts Array An array converted from shortcode attributes.
	 *
	 * 		content: The content for the Social Warfare function to filter. In the case of
	 * 			shortcodes, this will be blank since this isn't a content filter.
	 *
	 * 		where: The buttons are designed to be appended to the content. This default
	 * 			tells the buttons to append after the content. Since shortcodes don't have
	 * 			any content, they'll just produce and return the HTML without any content.
	 * 			This will likely never actually be set by the shortcode, but is necessary
	 * 			for the HTML generator to know what to do.
	 *
	 * 		echo: True echos the HTML to the screen. False returns the HTML as a string.
	 *
	 * @return string The HTML of the Social Warfare buttons.
	 *
	 */
	public function buttons_shortcode( $args ) {

		if( !is_array($args) ):
			$args = array();
		endif;

		$buttons_panel = new SWP_Buttons_Panel( $args, true );
		return $buttons_panel->render_html();
	}
}
