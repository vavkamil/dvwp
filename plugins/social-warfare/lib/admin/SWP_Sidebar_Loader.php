<?php

/**
 * SWP_Sidebar_Loader
 *
 * This pulls in the sidebar component JSON from our server and displays it as HTML.
 *
 * We can cachce the HTML so as not to make an excessive number of ajax calls.
 *
 * @package   SocialWarfare\Functions\Social-Networks
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since  3.3.0 | 03 AUG 2018 | Created.
 *
 */
class SWP_Sidebar_Loader {


	/**
	 * SWP_Debug_Trait provides useful tool like error handling and a debug
	 * method which outputs the contents of the current object.
	 *
	 */
	use SWP_Debug_Trait;
	

	/**
	 * Instantiate the class.
	 *
	 * @since  3.3.0 | 03 AUG 2018 | Created.
	 * @param  void
	 * @return void
	 *
	 */
    public function __construct() {
		$this->load_components();
    }


	/**
	 * Activate notices created via our remote JSON file.
	 *
	 * @since  3.1.0 | 27 JUN 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	private function load_components() {
		$cache_data = get_option('swp_json_cache');

		if( false === $cache_data ):
			return;
		endif;

		if( !is_array( $cache_data ) || empty($cache_data['sidebar']) ):
			return;
		endif;

        add_filter( 'swp_admin_sidebar', function( $components ) {
            return array_merge( $components, $cache_data['sidebar'] );
        });
	}

}
