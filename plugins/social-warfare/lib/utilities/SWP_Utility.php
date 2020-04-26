<?php

/**
 * SWP_Utility
 *
 * A collection of utility functions.
 *
 * All of the methods should be static. The class serves as tidy container
 * for various utility functions.
 *
 * The constructor serves only to set up hooks and filters.
 *
 * @package   SocialWarfare\Utilities
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     3.3.0 | 14 AUG 2018 | Created.
 * @access    public
 *
 */
class SWP_Utility {


	/**
	 * Insantiates filterss and hooks, for admin and ajax.
	 *
	 * @since  3.3.0 \ 14 AUG 2018 | Created.
	 *
	 */
	public function __construct() {
		add_action( 'wp_ajax_swp_store_settings', array( 'SWP_Utility', 'store_settings' ) );
		add_filter( 'screen_options_show_screen', array( 'SWP_Utility', 'remove_screen_options' ), 10, 2 );
		add_action( 'wp_ajax_swp_reset_post_meta', array ( 'SWP_Utility' , 'reset_post_meta' ) );
	}


	/**
	 *
	 * Fetches a key from our filtered $swp_user_options.
	 *
	 * @since  3.0.0 | 24 APR 2018 | Created.
	 * @since  3.0.8 | 16 MAY 2018 | Added $options parameter.
	 * @since  3.3.0 | 14 AUG 2018 | Added $key validation, refactored method body.
	 * @param  string $key   The key associated with the option we want.
	 *
	 * @return mixed  $value The value of the option if set, or false.
	 *
	 */
	public static function get_option( $key = '' ) {
		if ( !isset( $key ) || !is_string( $key ) ) :
			return false;
		endif;

		global $swp_user_options;

		if ( !is_array( $swp_user_options ) ) :
			return false;
		endif;

		if ( array_key_exists( $key, $swp_user_options ) ) :
			return $swp_user_options[$key];
		endif;

		return false;
	}

	/**
	 *
	 * Fetches a meta value.
	 *
	 * @since  3.5.0 | 19 DEC 2018 | Created.
	 * @param  int    $id    The post id to fetch meta from.
	 * @param  string $key   The key associated with the option we want.
	 *
	 * @return mixed  $value The value of the option if set, or false.
	 *
	 * * @TODO This needs to go through SWP meta filters.
	 */
	 public static function get_meta( $id, $key ) {
		 $value = get_post_meta( $id, $key, true );

		 // Sometimes a boolean value is stored in the meta as a string.
		 if ( 'false' === $value ) {
			  return false;
		 }

		 if ( 'true' === $value ) {
			 return true;
		 }
		 // echo "<br>".__METHOD__, var_dump($id), var_dump($key), var_dump($value);

		 return $value;
	 }

	/**
	 * Fetches a meta key we know to be an array.
	 * @param  [type] $id  [description]
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public static function get_meta_array( $id, $key ) {
		$value = get_post_meta( $id, $key, true );

		// Sometimes a boolean value ideas stored in the meta as a string.
		if ( 'false' === $value ) {
			 return false;
		 }

		if ( 'true' === $value ) {
			return true;
		}

		//* I think everything fetched form meta is returned as a string.
		if (is_string($value)) {
			$value = json_decode($value);
		}

		//* Do the same kind of checks/filtering as above.
		return is_array( $value ) ? $value : false;
	}




	/**
	 * Handle the options save request inside of admin-ajax.php
	 *
	 * @since  2.x.x | Unknown | Created.
	 * @since  3.0.9 | 31 MAY 2018 | Added call to wp_cache_delete to make sure settings save
	 * @since  3.3.0 | 14 AUG 2018 | Removed deprecated code.
	 *
	 * @return bool Whether or not the options were updated in the database.
	 */
	public static function store_settings() {
		if ( !check_ajax_referer( 'swp_plugin_options_save', 'security', false ) ) {
			wp_send_json_error( esc_html__( 'Security failed.', 'social-warfare' ) );
			die;
		}

		$data = wp_unslash( $_POST );

		if ( empty( $data['settings'] ) ) {
			wp_send_json_error( esc_html__( 'No settings to save.', 'social-warfare' ) );
			die;
		}

		$options = get_option( 'social_warfare_settings', array() );
		$settings = $data['settings'];

		// Loop and check for checkbox values, convert them to boolean.
		foreach ( $data['settings'] as $key => $value ) {
			if ( 'true' == $value ) {
				$settings[$key] = true;
			} elseif ( 'false' == $value ) {
				$settings[$key] = false;
			} else {
				$settings[$key] = $value;
			}
		}

		$new_settings = array_merge( $options, $settings );

		echo json_encode( update_option( 'social_warfare_settings', $new_settings ) );

		wp_die();
	}


	/**
	 *  Rounds a number to the appropriate thousands.
	 *
	 * @since  2.x.x | Unknown | Created.
	 * @access public
	 * @param  float $number The float to be rounded.
	 *
	 * @return float A rounded number.
	 *
	 */
	public static function kilomega( $number = 0) {
		if ( empty( $number ) ) :
			return 0;
		endif;


		if ( $number < 1000 ) :
			return $number;
		endif;

		if ( $number < 1000000 ) {
			$suffix = 'K';
			$value = $number / 1000;
		} else {
			$suffix = 'M';
			$value = $number / 1000000;
		}

		if ( 'period' == SWP_Utility::get_option( 'decimal_separator' ) ) :
			$decimal_point = '.';
			$thousands_separator = ',';
		else :
			$decimal_point = ',';
			$thousands_separator = '.';
		endif;

		$decimals = SWP_Utility::get_option( 'decimals' );
		$display_number = number_format( $value, $decimals, $decimal_point, $thousands_separator ) . $suffix;

		return $display_number;
	}


	/**
	 *  Process the excerpts for descriptions.
	 *
	 * While similar to WordPress's own get_the_excerpt, ours prevents
	 * infinite recursion from Social Warfare code.
	 *
	 * @since  1.0.0 | Created | Unknown.
	 * @since  2.2.4 | Updated | 6 March 2017 | Added the filter to remove the script and style tags.
	 * @access public
	 * @param  int $post_id The post ID to use when getting an exceprt.
	 *
	 * @return string The excerpt.
	 *
	 */
	public static function get_the_excerpt( $post_id ) {
		// Check if the post has an excerpt
		if ( has_excerpt() ) :
			$the_post = get_post( $post_id ); // Gets post ID
			$the_excerpt = $the_post->post_excerpt;

		// If not, let's create an excerpt
		else :
			$the_post = get_post( $post_id ); // Gets post ID
			$the_excerpt = $the_post->post_content; // Gets post_content to be used as a basis for the excerpt
		endif;

		$excerpt_length = 100; // Sets excerpt length by word count

		// Filter out any inline script or style tags as well as their content
		if( !empty( $the_excerpt ) ):
			$the_excerpt = preg_replace('/(<script[^>]*>.+?<\/script>|<style[^>]*>.+?<\/style>)/s', '', $the_excerpt);
		endif;

		$the_excerpt = strip_tags( strip_shortcodes( $the_excerpt ) ); // Strips tags and images
		$the_excerpt = preg_replace( '/\[[^\]]+\]/', '', $the_excerpt );
		$the_excerpt = str_replace( ']]>', ']]&gt;', $the_excerpt );
		$the_excerpt = strip_tags( $the_excerpt );
		$excerpt_length = apply_filters( 'excerpt_length', 100 );
		$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[...]' );
		$words = preg_split( "/[\n\r\t ]+/", $the_excerpt, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY );

		if ( count( $words ) > $excerpt_length ) :
			array_pop( $words );
			// array_push($words, '…');
			$the_excerpt = implode( ' ', $words );
		endif;

		$the_excerpt = preg_replace( "/\r|\n/", '', $the_excerpt );

		return $the_excerpt;
	}


	/**
	 * Checks to see if a debugging query paramter has been set.
	 *
	 * @since  2.1.0
	 * @since  3.3.0 | 14 AUG 2018 | Refactored to a one-liner.
	 * @access public
	 * @param  string $type The query paramter to check for.
	 *
	 * @return bool True if the specified key is set for debugging, else false.
	 *
	 */
	public static function debug( $key = '' ) {
		return !empty( $_GET['swp_debug'] ) && ( strtolower( $_GET['swp_debug'] ) == strtolower( $key ) );
	}

	/**
	 * Converts curly quotes to straight quotes.
	 *
	 * @since  1.4.0
	 * @param  string $content The text to be filtered.
	 *
	 * @return string $content The filtered text.
	 *
	 */
	public static function convert_smart_quotes( $content ) {
		$content = str_replace( '"', "'", $content );
		$content = str_replace( '&#8220;', "'", $content );
		$content = str_replace( '&#8221;', "'", $content );
		$content = str_replace( '&#8216;', "'", $content );
		$content = str_replace( '&#8217;', "'", $content );
		return $content;
	}


	/**
	 * Returns post types supported by Social Warfare. Includes Custom Post Types.
	 *
	 * @since 2.x.x | Unknown | Created.
	 * @return array The names of registered post types.
	 *
	 */
	public static function get_post_types() {
		$types = get_post_types( array( 'public' => true, '_builtin' => false ), 'names' );

		$types = array_merge( array( 'home', 'archive_categories', 'post', 'page' ), $types );

		return apply_filters( 'swp_post_types', $types );
	}


	/**
	 * A function to remove the screen options tab from our admin page
	 *
	 * @since 2.2.1 | Unknown | Created.
	 * @param bool Whether to show Screen Options tab. Default true.
	 * @param WP_Screen $wp_screen Current WP_Screen instance.
	 *
	 * @return boolean $display or false.
	 *
	 */
	public static function remove_screen_options( $show_screen, $wp_screen ){
		 $blacklist = array('admin.php?page=social-warfare');

		 if ( in_array( $GLOBALS['pagenow'], $blacklist ) ) {
			 $wp_screen->render_screen_layout();
			 $wp_screen->render_per_page_options();
			 return false;
		 }

		 return $show_screen;
	 }


	 /**
	  * Returns the URL of current website or network.
	  *
	  * @since 2.3.3 | 25 SEP 2017 | Created.
	  *
	  * @return string The URL of the site.
	  *
	  */
	public static function get_site_url() {
		if( true == is_multisite() ) {
			return network_site_url();
		} else {
			return get_site_url();
		}
	}

	/**
	 * Updates an option in the Social Warfare settings.
	 *
	 * @since 3.3.2 | 12 SEP 2018 | Created.
	 *
	 * @param  string $key   The key under which the option needs to be stored.
	 * @param  mixed  $value The value at the key.
	 * @return bool          True if the option was updated, else false.
	 *
	 */
	public static function update_option( $key, $value ) {
		if ( empty( $key ) ) {
			return false;
		}

		$options = get_option( 'social_warfare_settings', array() );
		$options[$key] = $value;

		return update_option( 'social_warfare_settings', $options );
	}

	public static function delete_option( $key ) {
		if ( empty( $key )  ) {
			return false;
		}

		$options = get_option( 'social_warfare_settings', array() );
		unset( $options[$key] );

		return update_option( 'social_warfare_settings', $options);
	}

   /**
	* Check the version range between core and addons.
	*
	* The idea here is that we can only maintain backwards compatibility to a
	* reasonable, but limited, extent. As such, we will check if the version
	* of core is within 6 version of pro. This will allow us to depracate and
	* remove some really old backwards-compatibility workarounds that we've put
	* in place.
	*
	* We want to be able to input two version (core and pro) and have it return
	* a string/integer indicating how many versions they are different from one
	* another. If the answer is greater than -6 and less than 6, we can fire up
	* the pro addon.
	*
	* @param  string $core_version The verison of Core currently installed.
	* @param  string $addon_version The version of the addon currently installed.
	* @return bool   True if the versions are compatible, else false.
	*
	*/
	public static function check_version_range( $core_version, $addon_version ) {
		$core_versions = explode( '.', $core_version );
		$addon_versions = explode( '.', $addon_version );

		$version_difference = absint( $core_versions[1] - $addon_versions[1] );

		//* Force plugin users to be on the same major version.
		if ( $core_versions[0] != $addon_verisons[0] ) {
			return false;
		}

		//* Require plugin users to be within nearby secondary versions.
		if ( $version_difference < 5 ) {
			return true;
		}

		return false;
	}

	/**
	 * Immediately redirect to the Social Warfare settings page.
	 *
	 * @since  3.5.0  | 12 FEB 2019 | Created.
	 * @param  string $params The pre-formatted string of query args.
	 * @param  array  $params And asssociative array to format as query args.
	 * @return exit           End all program exectution and return to SW.
	 *
	 */
	public static function settings_page_redirect( $params = '' ) {
		$destination = admin_url('?page=social-warfare');

		if ( is_string($params) && 0 == strpos( $params, '&' ) ) {
			$destination .= $params;
		}

		if ( is_array($params) ) {
			foreach($params as $key => $value) {
				$destination = add_query_arg($key, $value, $destination);
			}
		}

		return $destination;
	}

	/**
	 * Ajax callback to delete all post meta for a post.
	 *
	 * @since  3.5.0  | 14 FEB 2019 | Created.
	 * @return bool   True iff reset, else false.
	 *
	 */
	public static function reset_post_meta() {
		$post_id = $_POST['post_id'];
		if ( empty( $post_id ) ) {
			wp_die(0);
		}

		$all_meta = get_post_meta( $post_id );

		foreach ( $all_meta as $meta_key => $value ) {
			// Confirm this is a social warfare meta key.
			if ( ( strpos( $meta_key, 'swp_' ) === 0 ||
				 ( strpos( $meta_key, '_shares' ) > 0 ) &&
				   strpos( $meta_key, '_') === 0 ) ) {
				//* Everything comes in as an array, pull out the first value.
				delete_post_meta( $post_id, $meta_key );
			}
		}

		wp_die(1);
	}
}
