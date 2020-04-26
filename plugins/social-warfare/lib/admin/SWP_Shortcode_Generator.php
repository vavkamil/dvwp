<?php

/**
 * A class of functions used to render the shortcode generator
 *
 * This provides the shortcode generator for the [social_warfare]
 * shortcode which allows the user to be able to output a panel of
 * share buttons right in the middle of a post if they want.
 *
 * @package   SocialWarfare\Frontend-Output
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0
 * @since     3.0.0 | 20 FEB 2018 | Refactored this file to align
 * 			  with our code style guide
 *
 */
class SWP_Shortcode_Generator {

	/**
    * The magic method for instatiating this class
    *
    * This method called the activation and decativation hooks and
    * sets up the button and it's associated JS to be registered with
    * the TinyMCE editor on WordPress posts (AKA the Kitchen Sink).
    *
    * @param None
    * @return None
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
    * Pretty print data for debugging.
    *
    * @param Array $array The data to print.
    *
    */
	public function debug( $array ) {
		echo '<pre>';
		print_r( $array );
		echo '</pre>';
	}

    /**
    * Activate the shortcode
    *
    */
	public function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}


    /**
    * Register the admin hooks
    *
    */
	public function register_admin_hooks() {
		add_filter( 'tiny_mce_version', array( $this, 'refresh_mce' ) );
		add_action( 'init', array( $this, 'tinymce_button' ) );
	}


    /**
    * A method for adding the button to tinymce editor
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
     * Register the shortcode button.
     *
     * @param array $buttons
     * @return array
     *
     */
	public function tinymce_register_button( $buttons ) {
		array_push( $buttons, '|', 'swp_shortcode_generator' );
		return $buttons;
	}


    /**
    * Register the JS file with the TinyMCE editor
    *
    * @param Array An array of plugins registered with the TinyMCE editor
    * @return Array The modified array with our plugin and JS file added
    *
    */
	public function tinymce_register_plugin( $plugin_array ) {
		if( true == SWP_Utility::get_option( 'gutenberg_switch' ) && function_exists( 'is_gutenberg_page' )  && is_gutenberg_page() ) {
			return $plugin_array;
		}
		$plugin_array['swp_shortcode_generator'] = SWP_PLUGIN_URL . '/assets/js/sw-shortcode-generator.js';
		return $plugin_array;
	}

    /**
	 * Force TinyMCE to refresh.
	 *
	 * @param  int $version
	 * @return int
	 */
	public function refresh_mce( $ver ) {
		$ver += 3;
		return $ver;
	}
}
