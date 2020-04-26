<?php

/**
 * Google Plus
 *
 * Class to add a Google Plus share button to the available buttons
 *
 * @package   SocialWarfare\Functions\Social-Networks
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0 | Unknown     | Created
 * @since     2.2.4 | 02 MAY 2017 | Refactored functions & updated docblocking
 * @since     3.0.0 | 05 APR 2018 | Rebuilt into a class-based system.
 * @since     3.0.9 | 04 JUN 2018 | Removed deprecated API request (share counts)
 *
 */
class SWP_Google_Plus extends SWP_Social_Network {


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
		$this->name           = __( 'Google Plus','social-warfare' );
		$this->cta            = __( '+1','social-warfare' );
		$this->key            = 'google_plus';
		$this->default        = 'true';
		$this->base_share_url = 'https://plus.google.com/share?url=';

		$this->init_social_network();
	}
}
