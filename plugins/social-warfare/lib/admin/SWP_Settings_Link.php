<?php

/**
 * Adding the settings link to the plugins page
 *
 * This class and its methods add a link to the plugins page which links directly
 * to the Social Warfare settings page.
 *
 * @package   SocialWarfare\Admin\Functions
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0
 * @since     3.0.0 | 21 FEB 2018 | Refactored into a class based system.
 *
 */
class SWP_Settings_Link {


	/**
    * The magic method for instatiating this class
    *
    * This method called in the settings link by attaching it to the appropriate
    * WordPress hooks and filtering the passed array of $links.
    *
    * @since  3.0.0
    * @param  None
    * @return None
    *
    */
	public function __construct() {
		add_filter( 'plugin_action_links_' . plugin_basename( SWP_PLUGIN_FILE ), array( $this , 'add_settings_links' ) );
	}

	/**
	 * Add a "Settings" link to the listing on the plugins page
	 *
	 * @since  1.0.0
	 * @param  array $links Array of links passed in from WordPress core.
	 * @return array $links Array of links modified by the function passed back to WordPress
	 *
	 */
	public function add_settings_links( $links ) {
		$settings_link = sprintf( '<a href="admin.php?page=social-warfare">%s</a>',
			esc_html__( 'Settings', 'social-warfare' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

}
