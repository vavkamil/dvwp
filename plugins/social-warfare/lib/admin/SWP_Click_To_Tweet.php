<?php

/**
* Functions for creating click to tweets
*
* @package   SocialWarfare\Functions
* @copyright Copyright (c) 2018, Warfare Plugins, LLC
* @license   GPL-3.0+
* @since     1.0.0
* @since     3.0.0 | Feb 23 2018 | Updated class to fit our style guide.
*
*/
class SWP_Click_To_Tweet {


	/**
	 * The Magic Construct method.
	 *
	 * Everything gets added later via hooks.
	 *
	 * @since  3.0.0 | 23 FEB 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivation' ) );

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}
	}


	/**
	 * Register the uninstall hook to remove our button later.
	 *
	 * @since  3.0.0 | 23 FEB 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}


	/**
	 * Register the admin hooks.
	 *
	 * @since  3.0.0 | 23 FEB 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function register_admin_hooks() {
		add_filter( 'tiny_mce_version', array( $this, 'refresh_mce' ) );
		add_action( 'init', array( $this, 'tinymce_button' ) );
	}


	/**
	 * Add the button to the post editor
	 *
	 * @since  3.0.0 | 23 FEB 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function tinymce_button() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( $this, 'tinymce_register_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'tinymce_register_button' ) );
		}
	}


	/**
	 * Add the button to the TinyMCE buttons array
	 *
	 * @since  3.0.0 | 23 FEB 2018 | Created
	 * @param  array $buttons The array of buttons passed in by the hook.
	 * @return array          The modified array with our new button added.
	 *
	 */
	public function tinymce_register_button( $buttons ) {
		array_push( $buttons, '|', 'click_to_tweet' );
		return $buttons;
	}


	/**
	 * Register the JS file used to control the button.
	 *
	 * @since  3.0.0 | 23 FEB 2018 | Created
	 * @param  array $plugin_array Array of tinyMCE plugins that are registered.
	 * @return array               The modified array with our plugin's JS file added.
	 *
	 */
	public function tinymce_register_plugin( $plugin_array ) {
		if ( true == SWP_Utility::get_option( 'gutenberg_switch' ) && function_exists ( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			return $plugin_array;
		}
		$plugin_array['click_to_tweet'] = plugins_url( '/assets/js/clickToTweet.js', __FILE__ );
		return $plugin_array;
	}


	/**
	 * Register settings
	 *
	 * @since  3.0.0 | 23 FEB 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function register_settings() {
		register_setting( 'tmclicktotweet-options', 'twitter-handle', array( $this, 'validate_settings' ) );
	}


	/**
	 * Validate Settings
	 *
	 * @since  3.0.0 | 23 FEB 2018 | Created
	 * @param  str $input The string to validate
	 * @return str        The modified string.
	 *
	 */
	public function validate_settings( $input ) {
		return str_replace( '@', '', strip_tags( stripslashes( $input ) ) );
	}


	/**
	 * Refresh the tinyMCE
	 *
	 * @since  3.0.0 | 23 FEB 2018 | Created
	 * @param  int $ver The current version of the tinyMCE editor.
	 * @return int      The modified version of the tinyMCE editor.
	 *
	 */
	public function refresh_mce( $ver ) {
		$ver += 3;
		return $ver;
	}

}
