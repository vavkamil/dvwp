<?php
/**
 * Functions to load translations for the plugin.
 *
 * @package   SocialWarfare\Functions
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0
 */

class SWP_Localization {


	/**
	 * Load up the text domain for translations
	 *
	 * @since  1.0.0
	 * @return void
	 */
    public function init() {
        $loaded = load_plugin_textdomain(
			'social-warfare',
			false,
			dirname( plugin_basename( SWP_PLUGIN_FILE ) ) . '/languages'
		);
    }


	/**
	 * Remove translations from memory.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return bool true if the text domain was loaded, false if it was not.
	 *
	 */
	public function swp_unload_textdomain() {
		return unload_textdomain( 'social-warfare' );
	}

	/**
	 * Whether or not the language has been loaded already.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return bool
	 *
	 */
	public function swp_is_textdomain_loaded() {
		return is_textdomain_loaded( 'social-warfare' );
	}

}
