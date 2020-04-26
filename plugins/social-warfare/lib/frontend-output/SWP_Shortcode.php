<?php

/**
 * A class of functions used to render shortcodes for the user
 *
 * The SWP_Shortcodes Class used to add our shorcodes to WordPress
 * registry of registered functions.
 *
 * @package   SocialWarfare\Frontend-Output
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0
 * @since     3.0.0 | 19 FEB 2018 | Refactored into a class-based system
 *
 */
class SWP_Shortcode {


	/**
	 * Constructs a new SWP_Shortcodes instance
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
		add_shortcode( 'total_shares', array ( $this, 'post_total_shares' ) );
		add_shortcode( 'sitewide_shares', array ( $this, 'sitewide_total_shares' ) );
		add_shortcode( 'click_to_tweet', array( $this, 'click_to_tweet' ) );


		/**
		 * These are old legacy shortcodes that have been replaced with the ones seen above.
		 * We're leaving these here to ensure that it won't break for anyone who has used these
		 * ones in the past. The ones above adhere to our code style guide.
		 *
		 */
		add_shortcode( 'clickToTweet', array( $this, 'click_to_tweet' ) );
	}


	/**
	 * This is used to process the total shares across all tracked
	 * social networks for any given WordPress post.
	 *
	 * This function will accept an array of arguments which WordPress
	 * will create from the shortcode attributes. However, it doesn't actually
	 * use any parameters. It is only included to prevent throwing an error
	 * in the event that someone tries to input a parameter on it.
	 *
	 * @since  3.0.0
	 * @param  $atts Array An array converted from shortcode attributes.
	 * @return string A string of text representing the total shares for the post.
	 *
	 */
	public function post_total_shares( $settings ) {
		$unformatted_total_shares = get_post_meta( get_the_ID(), '_total_shares', true );
		$formatted_total_shares   = SWP_Utility::kilomega( $unformatted_total_shares );
		return $formatted_total_shares;
	}


	/**
	 * This is used to process the total shares across all tracked
	 * social networks for all posts across the site as an aggragate count.
	 *
	 * This function will accept an array of arguments which WordPress
	 * will create from the shortcode attributes. However, it doesn't actually
	 * use any parameters. It is only included to prevent throwing an error
	 * in the event that someone tries to input a parameter on it.
	 *
	 * @since  3.0.0
	 * @param  $atts Array An array converted from shortcode attributes.
	 * @return string A string of text representing the total sitewide shares.
	 *
	 */
	public function sitewide_total_shares( $settings ) {
		global $wpdb;
		$sum = $wpdb->get_results( "SELECT SUM(meta_value) AS total FROM $wpdb->postmeta WHERE meta_key = '_total_shares'" );
		return SWP_Utility::kilomega( $sum[0]->total );
	}


	/**
	 * The function to build the click to tweets
	 *
	 * @param  array $atts The shortcode key/value attributes.
	 * @return string The html of a click to tweet
	 *
	 */
	function click_to_tweet( $atts ) {
		global $post;

		// This is the Add Post editor for a new post, so no $post.
		if ( !is_object( $post ) ) {
			return $atts;
		}


		/**
		 * If they included a link in the tweet text, we need to not pass a URL
		 * parameter over to Twitter.
		 *
		 * Twitter will diregard value if it is: empty, a whitespace, or %20.
		 * Instead, give it an invalid URL! It achieves the targeted effect.
		 *
		 * This means that Twitter will not add a URL to the end of the tweet
		 * which is the desired effect since the author added a link within the
		 * tweet itself.
		 *
		*/
		$url = '&url=' . SWP_URL_Management::process_url( get_permalink() , 'twitter' , get_the_ID() );
		if ( strpos( $atts['tweet'], 'http' ) > -1 ) {
			$url = '&url=x';
		}


		/**
		 * Fetch a Twitter Username for the Via parameter.
		 *
		 * We'll fetch the Twitter username at the global level, the author
		 * level and the post level and then use the lowest level available.
		 *
		 */
		$twitter_handle        = SWP_Utility::get_option( 'twitter_id' );
		$author_twitter_handle = get_the_author_meta( 'swp_twitter', $post->post_author );


		/**
		 * If the author of thist post has an assigned Twitter username, we will
		 * override the global Twitter username with the author level username.
		 *
		 */
		if ( false !== $author_twitter_handle && !empty( $author_twitter_handle ) ) {
			$twitter_handle = $author_twitter_handle;
		}


		/**
		 * If after all three checks, we were able to find a Twitter username,
		 * then we'll create the via parameter of the link. If not, it will be
		 * an empty string.
		 *
		 */
		$via = '';
		if( !empty( $twitter_handle ) ) {
			$via = '&via=' . $twitter_handle;
		}


		/**
		 * If a theme was passed into the shortcode via a parameter, we'll use
		 * that theme. If a theme was not passed in, or if the theme is set to
		 * 'default', or if an empty string was passed in, then we'll use the
		 * global theme which is set on the plugin's options page.
		 *
		 */
		$theme = SWP_Utility::get_option( 'ctt_theme' );
		if ( !empty( $atts['theme'] ) && $atts['theme'] != 'default' ) {
			$theme = $atts['theme'];
		}


		/**
		 * If the Theme is set to false, it means that the user is on the free
		 * version of the plugin and as such, the only available theme is the
		 * first/default theme.
		 *
		 */
		if( false === $theme ) {
			$theme = 'style1';
		}


		/**
		 * This will generate the user's custom Tweet that will be used to
		 * prepopulate the share dialogue box when an end user clicks on the
		 * actual Click to Tweet.
		 *
		 */
		$tweet = $this->get_tweet( $atts );


		/**
		 * Now that all the information has been processed, we generate the
		 * actual string of html that will be returned and output to the screen.
		 *
		 */
		$html = '<div class="sw-tweet-clear"></div>';
		$html .= '<a class="swp_CTT ' . $theme;
		$html .= '" href="https://twitter.com/share?text=' . $tweet . $via . $url;
		$html .= '" data-link="https://twitter.com/share?text=' . $tweet . $via . $url;
		$html .= '" rel="nofollow noreferrer noopener" target="_blank">';
			$html .= '<span class="sw-click-to-tweet">';
				$html .= '<span class="sw-ctt-text">';
					$html .= $atts['quote'];
				$html .= '</span>';
				$html .= '<span class="sw-ctt-btn">';
					$html .= __( 'Click To Tweet','social-warfare' );
					$html .= '<i class="sw swp_twitter_icon"></i>';
			$html .= '</span>';
			$html .= '</span>';
		$html .= '</a>';

		return $html;
	}

	/**
	 *
	 * Retrieves tweet from database and converts to UTF-8 for Twitter.
	 *
	 * @since  3.3.0 | 16 AUG 2018 | Created. Ported code from $this->generate_share_link.
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string $tweet The encoded tweet text.
	 *
	 */
	protected function get_tweet( $atts ) {
		$max_tweet_length = 240;

		// Check for a custom tweet from the shortcode attributes. .
		$tweet = $atts['tweet'];

		if ( function_exists( 'mb_convert_encoding' ) ) :
			$tweet = mb_convert_encoding( $tweet, 'UTF-8', get_bloginfo( "charset" ) );
		endif;

		$html_safe_tweet = htmlentities( $tweet, ENT_COMPAT, 'UTF-8' );
		$tweet = urlencode( $tweet );

		return $tweet;
	}
}
