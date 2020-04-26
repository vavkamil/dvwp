<?php

/**
 * Twitter
 *
 * Class to add a Twitter share button to the available buttons
 *
 * @package   SocialWarfare\Functions\Social-Networks
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0 | Unknown     | Created
 * @since     2.2.4 | 02 MAY 2017 | Refactored functions & updated docblocking
 * @since     3.0.0 | 07 APR 2018 | Rebuilt into a class-based system.
 * @since     3.4.0 | 16 NOV 2018 | Removed Open Share Counts API.
 * @since     3.4.0 | 16 NOV 2018 | Added local properties for debugging.
 *
 */
class SWP_Twitter extends SWP_Social_Network {


	/**
	 * The Magic __construct Method
	 *
	 * This method is used to instantiate the social network object. It does three things.
	 * First it sets the object properties for each network. Then it adds this object to
	 * the globally accessible swp_social_networks array. Finally, it fetches the active
	 * state (does the user have this button turned on?) so that it can be accessed directly
	 * within the object.
	 *
	 * @since  3.0.0 | 07 APR 2018 | Created
	 * @param  none
	 * @return none
	 * @access public
	 *
	 */
	public function __construct() {

		// Update the class properties for this network
		$this->name    = __( 'Twitter','social-warfare' );
		$this->cta     = __( 'Tweet','social-warfare' );
		$this->key     = 'twitter';
		$this->default = 'true';

		$this->handle_invalid_share_count_sources();
		$this->init_social_network();
	}


	/**
	 * Generate the API Share Count Request URL
	 *
	 * If a zero is returned, the cURL processes will no that this network does
	 * not have an active API endpoint and will not make a remote call.
	 *
	 * This method is called by the SWP_Post_Cache class when rebuilding the
	 * cached share count data.
	 *
	 * @since  3.0.0 | 07 APR 2018 | Created
	 * @since  3.4.0 | 16 NOV 2018 | Removed Open Share Counts API.
	 * @since  3.4.0 | 16 NOV 2018 | Added local property for debugging.
	 * @var    $request_url Stored in a local property to allow us to output it
	 *                      via the debug method when ?swp_debug=twitter is used.
	 * @param  string $url The permalink of the page or post for which to fetch share counts
	 * @return string $request_url The complete URL to be used to access share counts via the API
	 *
	 */
	public function get_api_link( $url ) {

		// Fetch the user's options
		global $swp_user_options;
		$this->request_url = 0;


		/**
		 * If share counts for Twitter aren't even turned on, just return zero
		 * and bail early.
		 *
		 */
		if( false === SWP_Utility::get_option( 'twitter_shares' ) ) {
			return 0;
		}


		/**
		 * Twitcount is currently the only working, valid source of Twitter
		 * share counts. If it's active, return the API url of the JSON enpoint.
		 *
		 */
		if( 'twitcount' === SWP_Utility::get_option( 'tweet_count_source' ) ) {
			$this->request_url = 'https://counts.twitcount.com/counts.php?url=' . $url;
			return $this->request_url;
		}

		return 0;
	}


	/**
	 * Parse the response to get the share count
	 *
	 * This method is called by the SWP_Post_Cache class when rebuilding the
	 * cached share count data.
	 *
	 * @since  3.0.0 | 07 APR 2018 | Created
	 * @since  3.4.0 | 16 NOV 2018 | Added local property for debugging.
	 * @access public
	 * @var    $this->response Stored in a local property to allow us to output it
	 *                         via the debug method when ?swp_debug=twitter is used.
	 * @param  string $response The raw response returned from the API request
	 * @return int $total_activity The number of shares reported from the API
	 *
	 */
	public function parse_api_response( $response ) {

		// Fetch the user's options
		global $swp_user_options;
		$this->response = 0;

		// If the user has enabled Twitter shares....
		if ( true == SWP_Utility::get_option('twitter_shares') ) {
			$response       = json_decode( $response, true );
			$this->response = isset( $response['count'] ) ? intval( $response['count'] ) : 0;
			return $this->response;
		}

		return $this->response;
	}


	/**
	 * Generate the share link
	 *
	 * This is the link that is being clicked on which will open up the share
	 * dialogue.
	 *
	 * @since  3.0.0 | 07 APR 2018 | Created
	 * @since  3.4.0 | 17 NOV 2018 | Stripped down into smaller, subordinate methods.
	 * @param  array $post_data The array of information passed in from the buttons panel.
	 * @return string The generated link
	 *
	 */
	public function generate_share_link( $post_data ) {

		$tweet         = $this->get_tweet( $post_data );
		$url_parameter = $this->get_url_parameter( $tweet, $post_data );
		$via_parameter = $this->get_via_parameter( $post_data );
		$parameters    = $tweet . $url_parameter . $via_parameter;
		$intent_link   = 'https://twitter.com/intent/tweet?text=' . $parameters;

		return $intent_link;
	}


	/**
	 * This is the method that generates the via=username section of the share link.
	 *
	 * @since  3.4.0 | 19 NOV 2018 | Created
	 * @param  array $post_data The array of information passed in from the buttons panel.
	 * @return sting The via=username section of the share link.
	 *
	 */
	protected function get_via_parameter( $post_data ) {


		/**
		 * Find out who the author of this post is, then check that author's
		 * profile to see if they have filled out their Twitter username. If so,
		 * we'll use that for this post instead of the global Twitter username.
		 *
		 */
		$author = SWP_User_Profile::get_author( $post_data['ID'] );
		$user_twitter_handle = get_the_author_meta( 'swp_twitter' , $author );
		if ( !empty( $user_twitter_handle ) ) {
			return '&via=' . str_replace( '@', '', $user_twitter_handle );
		}


		/**
		 * Next we'll check to see if a Twitter username has been filled out on
		 * the global options page. If so, we'll use that as a backup.
		 *
		 */
		$twitter_id = SWP_Utility::get_option( 'twitter_id' );
		if ( !empty( $twitter_id ) ) {
			return '&via=' . str_replace( '@', '', $twitter_id );
		}

		return '';
	}


	/**
	 * The method that generates the URL parameter of the share link.
	 *
	 * @since  3.4.0 | 17 NOV 2018 | Created
	 * @param  string $tweet     The tweet being shared.
	 * @param  array  $post_data The array of information passed in from the buttons panel.
	 * @return string The url parameter to be concatenated to the share link.
	 *
	 */
	protected function get_url_parameter( $tweet , $post_data ) {


		/**
		 * If the custom tweet already contains a link in it, then setting this
		 * parameter to a forward slash will stop Twitter from adding its own
		 * link, AKA a second link in the tweet.
		 *
		 */
		if ( false !== strpos( $tweet , 'http' ) ) {
			return $url_parameter = '&url=/';
		}

		$twitter_link = $this->get_shareable_permalink( $post_data );
		return '&url=' . $twitter_link;
	}


	/**
	 * Retrieves tweet from database and converts to UTF-8 for Twitter.
	 *
	 * @since  3.3.0 | 16 AUG 2018 | Created. Ported code from $this->generate_share_link.
	 * @param array $post_data WordPress post data, such as 'ID' and 'post_content'.
	 * @return string $tweet The encoded tweet text.
	 *
	 */
	protected function get_tweet( $post_data ) {


		/**
		 * If the user has drafted a custom tweet for this post, it will be
		 * stored in the swp_custom_tweet custom field. We will check and use
		 * this if it is available.
		 *
		 */
		$tweet = get_post_meta( $post_data['ID'] , 'swp_custom_tweet' , true );


		/**
		 * If the user has not filled out the custom tweet field, then we will
		 * use the title of the post instead.
		 *
		 */
		if ( empty( $tweet ) ) {
			$tweet = str_replace( '|', '', strip_tags( $post_data['post_title'] ) );
		}

		return urlencode( $tweet );
	}


	/**
	 * A method to turn off share counts if an old, deprecated share count
	 * source is currently active in the options as well as register a dashboard
	 * notice to inform the user.
	 *
	 * @since  3.2.0 | 24 JUL 2018 | Created
	 * @since  3.4.0 | 16 NOV 2018 | Completely rewritten
	 * @param  void
	 * @return void
	 *
	 */
	protected function handle_invalid_share_count_sources() {

		// Fetch the user's Twitter share count source/service.
		$source = SWP_Utility::get_option( 'tweet_count_source' );

		// If the current source is set to New Share Counts
		if ( 'newsharecounts' == $source ) {
			$service_name = 'New Share Count';
		}

		// If the current source is set to Open Share Counts
		if ( 'opensharecount' == $source ) {
			$service_name = 'Open Share Counts';
		}

		// If an invalid source was matched above, handle it here.
		if( isset( $service_name ) ) {

			// Disable share counts for Twitter.
			SWP_Utility::update_option( 'twitter_shares', false );

			// Draft the messae for the dashbaord notice.
			$message = $service_name . ' is no longer in service. For performance reasons, we have switched your Tweet Counts to "OFF". To re-activate tweet counts, please visit Settings -> Social Identity -> Tweet Count Registration and follow the directions for one of our alternative counting services.';

			// Instantiate a notice object.
			new SWP_Notice( $source . '_deprecation_notice', $message );
		}
	}
}
