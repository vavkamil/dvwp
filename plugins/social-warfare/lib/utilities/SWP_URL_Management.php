<?php

/**
* SWP_URL_Management
*
* A class engineered to manage the links that are shared out to the various social
* networks. This class will shorten them via Bitly or add Google Analytics tracking
* parameters if the user has either of these options enabled and configured.
*
* @since 3.0.0 | 14 FEB 2018 | Added check for is_attachment() to swp_google_analytics
* @since 3.0.0 | 04 APR 2018 | Converted to class-based, object-oriented system.
*
*/
class SWP_URL_Management {


	/**
	 * The magic __construct method.
	 *
	 * This method instantiates the SWP_URL_Management object. It's primary function
	 * is to add the various methods to their approprate hooks for use later on in
	 * modifying the links.
	 *
	 * @since  3.0.0 | 04 APR 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function __construct() {

		add_filter( 'swp_link_shortening'  	        , array( __CLASS__ , 'link_shortener' ) );
		add_filter( 'swp_analytics' 		        , array( $this , 'google_analytics' ) );
		add_action( 'wp_ajax_nopriv_swp_bitly_oauth', array( $this , 'bitly_oauth_callback' ) );

	}


	/**
	 * Google Analytics UTM Tracking Parameters
	 *
	 * This is the method used to add Google analytics UTM parameters to the links
	 * that are being shared on social media.
	 *
	 * @since  3.0.0 | 04 APR 2018 | Created
	 * @since  3.4.0 | 16 OCT 2018 | Refactored, Simplified, Docblocked.
	 * @param  array $array An array of arguments and data used in processing the URL.
	 * @return array $array The modified array.
	 *
	 */
	public function google_analytics( $array ) {


		/**
		 * If Google UTM Analytics are disabled, then bail out early and return
		 * the unmodified array.
		 *
		 */
    	if ( false == SWP_Utility::get_option('google_analytics') ) {
			return $array;
		}


		/**
		 * If the network is Pinterest and UTM analtyics are disabled on
		 * Pinterest links, then simply bail out and return the unmodified array.
		 *
		 */
		if ( 'pinterest' === $array['network'] && false == SWP_Utility::get_option('utm_on_pins') ) {
			return $array;
		}


		/**
		 * If we're on a WordPress attachment, then bail out early and return
		 * the unmodified array.
		 *
		 */
    	if ( true === is_attachment() ) {
    		return $array;
    	}


		/**
		 * If all of our checks are passed, then compile the UTM string, attach
		 * it to the end of the URL, and return the modified array.
		 *
		 */
		$separator     = ( true == strpos( $array['url'], '?' ) ? '&' : '?' );
		$utm_source    = 'utm_source=' . $array['network'];
		$utm_medium    = '&utm_medium=' . SWP_Utility::get_option( 'analytics_medium' );
		$utm_campaign  = '&utm_campaign=' . SWP_Utility::get_option( 'analytics_campaign' );
        $url_string    = $separator . $utm_source . $utm_medium . $utm_campaign;
		$array['url'] .= $url_string;

	    return $array;
	}


	/**
	 * Fetch the bitly link that is cached in the local database.
	 *
	 * When the cache is fresh, we just pull the existing bitly link from the
	 * database rather than making an API call on every single page load.
	 *
	 * @since  3.3.2 | 12 SEP 2018 | Created
	 * @since  3.4.0 | 16 OCT 2018 | Refactored, Simplified, Docblocked.
	 * @param  int $post_id The post ID
	 * @param  string $network The key for the current social network
	 * @return mixed           string: The short url; false on failure.
	 *
	 */
    public static function fetch_local_bitly_link( $post_id, $network ) {


		/**
		 * Fetch the local bitly link. We'll use this one if Google Analytics is
		 * not enabled. Otherwise we'll switch it out below.
		 *
		 */
		$short_url = get_post_meta( $post_id, 'bitly_link', true );


		/**
		 * If Google analytics are enabled, we'll need to fetch a different
		 * shortlink for each social network. If they are disabled, we just use
		 * the same shortlink for all of them.
		 *
		 */
		if ( true == SWP_Utility::get_option('google_analytics') ) {
        	$short_url = get_post_meta( $post_id, 'bitly_link_' . $network, true);
		}


		/**
		 * We need to make sure that the $short_url returned from get_post_meta()
		 * is not false or an empty string. If so, we'll return false.
		 *
		 */
        if ( !empty( $short_url ) ) {
            return $short_url;
        }

        return false;
    }


	/**
	 * The Bitly Link Shortener Method
	 *
	 * This is the function used to manage shortened links via the Bitly link
	 * shortening service.
	 *
	 * @since  3.0.0 | 04 APR 2018 | Created
	 * @since  3.4.0 | 16 OCT 2018 | Modified order of conditionals, docblocked.
	 * @param  array $array An array of arguments and information.
	 * @return array $array The modified array.
	 *
	 */
	public static function link_shortener( $array ) {


		/**
		 * Pull together the information that we'll need to generate bitly links.
		 *
		 */
        global $post;
        $post_id           = $array['post_id'];
        $google_analytics  = SWP_Utility::get_option('google_analytics');
        $access_token      = SWP_Utility::get_option( 'bitly_access_token' );
        $cached_bitly_link = SWP_URL_Management::fetch_local_bitly_link( $post_id, $array['network'] );
		$start_date        = SWP_Utility::get_option( 'bitly_start_date' );


		/**
		 * If we don't have an access token or if Bitly links are implicitly
		 * turned off on the options page, then let's just bail early and return
		 * the array without modification.
		 *
		 */
        if ( false == $access_token || false == SWP_Utility::get_option( 'bitly_authentication' ) ) {
			return $array;
        }


		/**
		 * Bitly links can now be turned on or off at the post_type level on the
		 * options page. So if the bitly links are turned off for our current
		 * post type, let's bail and return the unmodified array.
		 *
		 */
        $links_enabled = SWP_Utility::get_option( "bitly_links_{$post->post_type}" );
        if ( false == $links_enabled || 'off' == $links_enabled ) :
            return $array;
        endif;


        /**
         * If the chache is fresh and we have a valid bitly link stored in the
         * database, then let's use our cached link.
         *
         * If the cache is fresh and we don't have a valid bitly link, we just
         * return the unmodified array.
         *
         */
        if ( true == $array['fresh_cache'] ) {
			if( false !== $cached_bitly_link ) {
				$array['url'] = $cached_bitly_link;
			}
            return $array;
        }


		/**
		 * We don't want bitly links generated for the total shares buttons
		 * (since they don't have any links at all), and Pinterest doesn't allow
		 * shortlinks on their network.
		 *
		 */
        if ( $array['network'] == 'total_shares' || $array['network'] == 'pinterest' ) {
            return $array;
        }


		/**
		 * Users can select a date prior to which articles will not get short
		 * links. This is to prevent the case where some users get their quotas
		 * filled up as soon as the option is turned on because it is generating
		 * links for older articles. So this conditional checks the publish date
		 * of an article and ensures that the article is eligible for links.
		 *
		 */
        if ( $start_date ) {

			// Bail if we don't have a valid post object or post_date.
            if ( !is_object( $post ) || empty( $post->post_date ) ) {
                return $array;
            }

            $start_date = DateTime::createFromFormat( 'Y-m-d', $start_date );
            $post_date  = new DateTime( $post->post_date );

            //* The post is too new for $start_date.
            if ( $start_date > $post_date ) {
                return $array;
            }
        }


		/**
		 * If all checks have passed, let's generate a new bitly URL. If an
		 * existing link exists for the link passed to the API, it won't generate
		 * a new one, but will instead return the existing one.
		 *
		 */
        $network       = $array['network'];
        $url           = urldecode( $array['url'] );
        $new_bitly_url = SWP_URL_Management::make_bitly_url( $url, $access_token );


		/**
		 * If a link was successfully created, let's store it in the database,
		 * let's store it in the url indice of the array, and then let's wrap up.
		 *
		 */
        if ( $new_bitly_url ) {
			$meta_key = 'bitly_link';

			if ( $google_analytics ) {
				$meta_key .= "_$network";
			}

            delete_post_meta( $post_id, $meta_key );
            update_post_meta( $post_id, $meta_key, $new_bitly_url );
            $array['url'] = $new_bitly_url;
        }

	    return $array;
	}


	/**
	 * Create a new Bitly short URL
	 *
	 * This is the method used to interface with the Bitly API with regard to creating
	 * new shortened URL's via their service.
	 *
	 * @since  3.0.0 | 04 APR 2018 | Created
	 * @param  string $url          The URL to be shortened
	 * @param  string $network      The social network on which this URL is being shared.
	 * @param  string $access_token The user's Bitly access token.
	 * @return string               The shortened URL.
	 *
	 */
	public static function make_bitly_url( $url, $access_token ) {


		/**
		 * First we need to compile the link that we'll use to contact the
		 * Bitly API.
		 *
		 */
		$api_request_url = 'https://api-ssl.bitly.com/v3/shorten';
		$api_request_url .= "?access_token=$access_token";
		$api_request_url .= "&longUrl=" . urlencode( $url );
		$api_request_url .= "&format=json";


		/**
		 * Fetch a response from the Bitly API and then parse it from JSON into
		 * an array that we can use.
		 *
		 */
		$response = SWP_CURL::file_get_contents_curl( $api_request_url );
		$result   = json_decode( $response , true );

        //* The user no longer uses Bitly for link shortening.
		if ( isset( $result['status_txt'] ) && 'INVALID_ARG_ACCESS_TOKEN' == $result['status_txt'] )   {
            SWP_Utility::delete_option( 'bitly_access_token' );
			SWP_Utility::delete_option( 'bitly_access_login' );

			//* Turn Bitly link shortening off for the user.
			SWP_Utility::update_option( 'bitly_authentication', false );
		}


		/**
		 * If we have a valid link, we'll use that. If not, we'll return false.
		 *
		 */
		if ( isset( $result['data']['url'] ) ) {
			return $result['data']['url'];
		}

		return false;
	}


	/**
	 * The function that processes a URL
	 *
	 * This method is used throughout the plugin to fetch URL's that have been processed
	 * with the link shorteners and UTM parameters. It processes an array of information
	 * through the swp_analytics filter and the swp_link_shortening filter.
	 *
	 * @since  3.0.0 | 04 APR 2018 | Created
	 * @param  string $url     The URL to be modified.
	 * @param  string $network The network on which the URL is being shared.
	 * @param  int    $post_id  The post ID.
	 * @return string          The modified URL.
	 * @access public
	 *
	 */
	public static function process_url( $url, $network, $post_id, $is_cache_fresh = true ) {


		/**
		 * Bail out if this is an attachment page. We had reports of short links
		 * being created on these.
		 *
		 */
		if( is_attachment() ) {
			return $url;
		}


		/**
		 * Compile all of the parameters passed in into an array so that we can
		 * pass it through our custom filters (which only accept one paramter).
		 *
		 */
		$array['url']         = $url;
		$array['network']     = $network;
		$array['post_id']     = $post_id;
		$array['fresh_cache'] = $is_cache_fresh;
		$array                = apply_filters( 'swp_analytics' , $array );
		$array                = apply_filters( 'swp_link_shortening', $array);

		return $array['url'];
	}


	/**
	 * The Bitly OAuth Callback Function
	 *
	 * When authenticating Bitly to the plugin, Bitly uses a back-and-forth handshake
	 * system. This function will intercept the ping from Bitly's server, process the
	 * information and provide a response to Bitly.
	 *
	 * @since  3.0.0 | 04 APR 2018 | Created
	 * @param  none
	 * @return none A response is echoed to the screen for Bitly to read.
	 * @access public
	 *
	 */
	public function bitly_oauth_callback() {


		/**
		 * If no access token or bitly login username is provided, then we're
		 * just going to store them in the database as empty strings.
		 *
		 */
		$access_token = '';
		$login        = '';


		/**
		 * If the callback contained a valid access token and login, then we'll
		 * use those and store them in the options field of the database.
		 *
		 */
		if( isset( $_GET['access_token'] ) && isset( $_GET['login'] ) ) {
			$access_token = $_GET['access_token'];
			$login        = $_GET['login'];
		}


		/**
		 * Update our options field in the database with our new values.
		 *
		 */
		SWP_Utility::update_option( 'bitly_access_token', $access_token );
		SWP_Utility::update_option( 'bitly_access_login', $login);


		/**
		 * We have to echo out the link to the settings page so that the file
		 * on our server that handles the handshake can initiate a nice clean
		 * redirect and put the user back in their own admin dashboard on our
		 * options page.
		 *
		 */
		echo admin_url( 'admin.php?page=social-warfare' );
	}
}
