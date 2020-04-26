<?php

/**
 * Mix
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
class SWP_Mix extends SWP_Social_Network {


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
		$this->name           = __( 'Mix','social-warfare' );
		$this->cta            = __( 'Share','social-warfare' );
		$this->key            = 'mix';
		$this->default        = 'false';
		$this->base_share_url = 'https://mix.com/mixit?url=';

        $today = date("Y-m-d H:i:s");

        $this->check_stumble_upon_shares();
        $this->init_social_network();
	}


    public function check_stumble_upon_shares() {
        global $post;

        if ( !is_object( $post ) || empty( $post->ID ) ) :
            return;
        endif;

        $stumble_shares = get_post_meta( $post->ID, '_stumbleupon_shares', true );

        if ( !is_numeric( $stumble_shares ) ) :
            return;
        endif;

        if ( update_post_meta( $post->ID, '_mix_shares', (int) $stumble_shares ) ) :
            delete_post_meta( $post->ID, '_stumbleupon_shares' );
        endif;
    }

}
