<?php

/**
 * A class of functions used to fetch alternate permalinks.
 *
 * This class allows the share count API to check for share counts using multiple
 * forms of a post's permalink. This is used for the share recovery features.
 *
 * This class has no __construct method as it won't ever really need to be instantiated.
 *
 * @package   SocialWarfare\Utilities
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     3.0.0 | 22 FEB 2018 | Refactored into a class-based system.
 *
 */
class SWP_Permalink {


	/**
	 * A method to parse and produce the alternate permalink.
	 *
	 * @since 1.0.0
	 * @param int The post ID.
	 * @param bool Whether to keep the post name.
	 * @return string The modified URL of the post.
	 *
	 */
	public static function get_alt_permalink( $post = 0, $leavename = false ) {
		global $swp_user_options;

		$rewritecode = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			$leavename? '' : '%postname%',
			'%post_id%',
			'%category%',
			'%author%',
			$leavename? '' : '%pagename%',
		);

		if ( is_object( $post ) && isset( $post->filter ) && 'sample' == $post->filter ) {
			$sample = true;
		} else {
			$post = get_post( $post );
			$sample = false;
		}

		if ( empty( $post->ID ) ) {
			return false;
		}

		// Build the structure
		$structure = $swp_user_options['recovery_format'];

		if ( $structure == 'custom' ) :
			$permalink = $swp_user_options['recovery_permalink'];
		elseif ( $structure == 'unchanged' ) :
			$permalink = get_option( 'permalink_structure' );
			elseif ( $structure == 'default' ) :
				$permalink = '';
			elseif ( $structure == 'day_and_name' ) :
				$permalink = '/%year%/%monthnum%/%day%/%postname%/';
			elseif ( $structure == 'month_and_name' ) :
				$permalink = '/%year%/%monthnum%/%postname%/';
			elseif ( $structure == 'numeric' ) :
				$permalink = '/archives/%post_id%';
			elseif ( $structure == 'post_name' ) :
				$permalink = '/%postname%/';
			else :
				$permalink = get_option( 'permalink_structure' );
			endif;

			/**
			 * Filter the permalink structure for a post before token replacement occurs.
			 *
			 * Only applies to posts with post_type of 'post'.
			 *
			 * @since 3.0.0
			 *
			 * @param string  $permalink The site's permalink structure.
			 * @param WP_Post $post      The post in question.
			 * @param bool    $leavename Whether to keep the post name.
			 */
			$permalink = apply_filters( 'pre_post_link', $permalink, $post, $leavename );

			// Check if the user has defined a specific custom URL
			$custom_url = get_post_meta( get_the_ID() , 'swp_recovery_url' , true );
			if ( $custom_url ) :
				return $custom_url;
			else :

				if ( '' != $permalink && ! in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft', 'future' ) ) ) {
					$unixtime = strtotime( $post->post_date );

					$category = '';
					if ( strpos( $permalink, '%category%' ) !== false ) {
						$cats = get_the_category( $post->ID );
						if ( $cats ) {
							usort( $cats, '_usort_terms_by_ID' ); // order by ID

							/**
							 * Filter the category that gets used in the %category% permalink token.
							 *
							 * @since 3.5.0
							 *
							 * @param stdClass $cat  The category to use in the permalink.
							 * @param array    $cats Array of all categories associated with the post.
							 * @param WP_Post  $post The post in question.
							 */
							$category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );

							$category_object = get_term( $category_object, 'category' );
							$category = $category_object->slug;
							if ( $parent = $category_object->parent ) {
								$category = get_category_parents( $parent, false, '/', true ) . $category;
							}
						}
						// show default category in permalinks, without
						// having to assign it explicitly
						if ( empty( $category ) ) {
							$default_category = get_term( get_option( 'default_category' ), 'category' );
							$category = is_wp_error( $default_category ) ? '' : $default_category->slug;
						}
					}

					$author = '';
					if ( strpos( $permalink, '%author%' ) !== false ) {
						$authordata = get_userdata( $post->post_author );
						$author = $authordata->user_nicename;
					}

					$date = explode( ' ',date( 'Y m d H i s', $unixtime ) );
					$rewritereplace =
					array(
						$date[0],
						$date[1],
						$date[2],
						$date[3],
						$date[4],
						$date[5],
						$post->post_name,
						$post->ID,
						$category,
						$author,
						$post->post_name,
					);
					$permalink = home_url( str_replace( $rewritecode, $rewritereplace, $permalink ) );

					if ( $structure != 'custom' ) :
						$permalink = user_trailingslashit( $permalink, 'single' );
					endif;

				} else { // if they're not using the fancy permalink option
					$permalink = home_url( '?p=' . $post->ID );
				}// End if().

				/**
				 * Filter the permalink for a post.
				 *
				 * Only applies to posts with post_type of 'post'.
				 *
				 * @since 1.5.0
				 *
				 * @param string  $permalink The post's permalink.
				 * @param WP_Post $post      The post in question.
				 * @param bool    $leavename Whether to keep the post name.
				 */
				$url = apply_filters( 'post_link', $permalink, $post, $leavename );

				// Ignore all filters and just start with the site url on the home page
				if( is_front_page() ):
					$url = get_site_url();
				endif;

				// Check if they're using cross domain recovery
				if ( isset( $swp_user_options['current_domain'] ) && $swp_user_options['current_domain']
				&& isset( $swp_user_options['former_domain'] ) && $swp_user_options['former_domain'] ) :
					$url = str_replace( $swp_user_options['current_domain'],$swp_user_options['former_domain'],$url );
				endif;

				// Filter the Protocol
				if ( $swp_user_options['recovery_protocol'] == 'https' && strpos( $url,'https' ) === false ) :
					$url = str_replace( 'http','https',$url );
				elseif ( $swp_user_options['recovery_protocol'] == 'http' && strpos( $url,'https' ) !== false ) :
					$url = str_replace( 'https','http',$url );
				endif;

				// Filter the prefix
				if ( $swp_user_options['recovery_prefix'] == 'unchanged' ) :
				elseif ( $swp_user_options['recovery_prefix'] == 'www' && strpos( $url,'www' ) === false ) :
					$url = str_replace( 'http://','http://www.',$url );
					$url = str_replace( 'https://','https://www.',$url );
				elseif ( $swp_user_options['recovery_prefix'] == 'nonwww' && strpos( $url,'www' ) !== false ) :
					$url = str_replace( 'http://www.','http://',$url );
					$url = str_replace( 'https://www.','https://',$url );
				endif;

				// Filter out the subdomain
				if ( isset( $swp_user_options['recovery_subdomain'] ) && $swp_user_options['recovery_subdomain'] != '' ) :
					$url = str_replace( $swp_user_options['recovery_subdomain'] . '.' , '' , $url );
				endif;

				return $url;

			endif;

	}

}
