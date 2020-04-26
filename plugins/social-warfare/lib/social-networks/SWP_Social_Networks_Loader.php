<?php

/**
 * A class to load up all of this plugin's social networks.
 *
 * The purpose of this class is to create a global social networks array and
 * then to load up and instantiate each of the social networks as objects into
 * that array.
 *
 * @since 3.0.0 | Created | 05 APR 2018
 *
 */
class SWP_Social_Networks_Loader {


	/**
	 * The Magic __construct method.
	 *
	 * This method creates the global $swp_social_networks array and then queues
	 * up the instantiation of all the networks to be run after all the plugin
	 * including all addons have been loaded.
	 *
	 * @since  3.0.0 | Created | 06 APR 2018
	 * @param  none
	 * @return none
	 * @access public
	 *
	 */
	public function __construct() {

		// Create a global array to contain our social network objects.
		global $swp_social_networks;
		$swp_social_networks = array();

		add_filter( 'plugins_loaded' , array( $this , 'instantiate_networks' ) , 999 );

	}


	/**
	 * Instantiate all the networks.
	 *
	 * This class loops through every single declared child class of the
	 * primary SWP_Social_Network class and fires it up.
	 *
	 * @since  3.0.0 | Created | 06 APR 2018
	 * @param  none
	 * @return none
	 * @access public
	 *
	 */
	public function instantiate_networks() {
		foreach( get_declared_classes() as $class ){
			if( is_subclass_of( $class, 'SWP_Social_Network' ) ) {
				new $class;
			}
		}
	}

}
