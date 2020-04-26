<?php

/**
 * SWP_Compatibility: A class to enhance compatibility with other plugins
 *
 * @package   SocialWarfare\Functions
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0
 * @since     3.0.0 | 22 FEB 2018 | Refactored into a class-based system.
 *
 */
class SWP_Compatibility {


	/**
	 * The magic method used to insantiate this class.
	 *
	 * This adds compatibility with Simple Podcast Press, the Duplicate Posts
	 * plugin, and Really Simple SSL.
	 *
	 * @since  2.1.4
	 * @access public
	 * @param  integer $id The post ID
	 * @return none
	 *
	 */
	public function __construct() {
		// Disabe Open Graph tags on Simple Podcast Press Pages
		if ( is_plugin_active( 'simple-podcast-press/simple-podcast-press.php' ) ) {
			global $ob_wp_simplepodcastpress;
			remove_action( 'wp_head' , array( $ob_wp_simplepodcastpress, 'spp_open_graph' ) , 1 );
		}

		// Remove our custom fields when a post is duplicated via the Duplicate Post plugin.
		add_filter( 'duplicate_post_meta_keys_filter' , array( $this, 'filter_duplicate_meta_keys' ) );

		// Fix the links that are modified by the Really Simple SSL plugin.
		add_filter("rsssl_fixer_output", [$this, 'rsssl_fix_compatibility']   );

	}


	/**
	 * A function to fix the share recovery conflict with Really Simple SSL plugin
	 * @param  string $html A string of html to be filtered
	 * @return string $html The filtered string of html
	 * @access public
	 * @since 2.2.2
	 *
	 */
	function rsssl_fix_compatibility($html) {
	    //replace the https back to http
	    $html = str_replace( "swp_post_recovery_url = 'https://" , "swp_post_recovery_url = 'http://" , $html);
	    return $html;
	}

    /**
     * Removes Social Warfare keys from the meta before post is duplicated.
     *
     * @param  array  $meta_keys All meta keys prepared for duplication.
     * @return array  $meta_keys $meta_keys with no Social Warfare keys.
     * @since  3.4.2 | 10 DEC 2018 | Created
     *
     */
	function filter_duplicate_meta_keys( $meta_keys = array() ) {
		$blacklist = array( 'swp_', '_shares', 'bitly_link' );

		foreach( $meta_keys as $key ) {
			foreach( $blacklist as $forbidden ) {
				if ( strpos( $forbidden, $key ) ) {
					unset( $meta_keys[$key] );
				}
			}
		}

		return $meta_keys;
	}

}
