<?php

/**
 * Stumbleupon
 *
 * Class to add a StumbleUpon share button to the available buttons
 *
 * @package   SocialWarfare\Functions\Social-Networks
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0 | Unknown     | CREATED
 * @since     2.2.4 | 02 MAY 2017 | Refactored functions & updated docblocking
 * @since     3.0.0 | 05 APR 2018 | Rebuilt into a class-based system.
 *
 */
class SWP_Stumble_Upon extends SWP_Social_Network {


	/**
	 * The Magic __construct Method
	 *
	 * This method is used to instantiate the social network object. It does three things.
	 * First it sets the object properties for each network. Then it adds this object to
	 * the globally accessible swp_social_networks array. Finally, it fetches the active
	 * state (does the user have this button turned on?) so that it can be accessed directly
	 * within the object.
	 *
	 * @since  3.0.0 | 06 APR 2018 | Created
	 * @param  none
	 * @return none
	 * @access public
	 *
	 */
	public function __construct() {

		// Update the class properties for this network
		$this->name           = __( 'StumbleUpon','social-warfare' );
		$this->cta            = __( 'Stumble','social-warfare' );
		$this->key            = 'stumbleupon';
		$this->default        = 'false';
		$this->base_share_url = 'https://www.stumbleupon.com/submit?url=';

        $today = date("Y-m-d H:i:s");
        $expiry = "2018-06-30 00:00:00";

        if ( $today < $expiry ) :
    		$this->init_social_network();
        else :
            $options = get_option('social_warfare_settings');
            if ( isset( $options['order_of_icons']['stumbleupon'] ) ) :
                unset( $options['order_of_icons']['stumbleupon'] );
                update_option( 'social_warfare_settings', $options );
            endif;
        endif;
	}


	/**
	 * Generate the API Share Count Request URL
	 *
	 * @since  1.0.0 | 06 APR 2018 | Created
	 * @access public
	 * @param  string $url The permalink of the page or post for which to fetch share counts
	 * @return string $request_url The complete URL to be used to access share counts via the API
	 *
	 */
	public function get_api_link( $url ) {
		return 'https://www.stumbleupon.com/services/1.01/badge.getinfo?url=' . $url;
	}


	/**
	 * Parse the response to get the share count
	 *
	 * @since  1.0.0 | 06 APR 2018 | Created
	 * @access public
	 * @param  string $response The raw response returned from the API request
	 * @return int $total_activity The number of shares reported from the API
	 *
	 */
	public function parse_api_response( $response ) {
        $response = json_decode( $response, true );
    	return isset( $response['result']['views'] ) ? intval( $response['result']['views'] ) : 0;
    }
}
