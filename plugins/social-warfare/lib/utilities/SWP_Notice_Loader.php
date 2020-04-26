<?php

/**
 * SWP_Notice_Loader
 *
 * This is where we define all the messages, CTAs, and scheudling for each notice.
 * It is fine to bloat the method with as many notices as necessary.
 *
 * Output the contents of this class using ?swp_debug=notice_loader
 *
 * @package   SocialWarfare\Functions\Social-Networks
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since  3.0.9 | 09 JUN 2018 | Created the class.
 * @since  3.1.0 | 27 JUN 2018 | Break each notice into it's own method.
 *
 */
class SWP_Notice_Loader {


	/**
	 * SWP_Debug_Trait provides useful tool like error handling and a debug
	 * method which outputs the contents of the current object.
	 *
	 */
	use SWP_Debug_Trait;


	/**
	 * Instantiate the class.
	 *
	 * The constructor will call up the methods that create each of the various
	 * notices throughout the plugin.
	 *
	 * @since  3.0.9  | 09 JUN 2018 | Created.
	 * @since  3.1.0 | 27 JUN 2018 | Updated to use separate methods per notice.
	 * @see    SWP_Notice.php
	 * @param  void
	 * @return void
	 *
	 */
    public function __construct() {
		$this->activate_json_notices();
		$this->debug();
    }


	/**
	 * Activate notices created via our remote JSON file.
	 *
	 * @since  3.1.0 | 27 JUN 2018 | Created
	 * @since  3.4.0 | 16 NOV 2018 | Audit, docblock, formatting.
	 * @param  void
	 * @return void
	 *
	 */
	private function activate_json_notices() {


		/**
		 * The JSON loader class fetches the JSON from
		 * https://warfareplugins.com/JSON_updates.php. This is stored in the
		 * options database under swp_json_cache as an array. So here we are
		 * simply fetching what was stored.
		 *
		 */
		$cache_data = get_option( 'swp_json_cache' );


		/**
		 * If there is nothing in the JSON notices options table, then simply
		 * bail out because we have no notices to process.
		 *
		 */
		if( false === $cache_data ) {
			return;
		}


		/**
		 * If the notices are not an array or if it is an empty array, it means
		 * that we have no notices to print so just bail out.
		 *
		 */
		if( !is_array( $cache_data ) || empty( $cache_data['notices'] ) ) {
			return;
		}


		/**
		 * Loop through each available notice in the array, and use the data in
		 * that array to instantiate a notice object.
		 *
		 */
		foreach( $cache_data['notices'] as $data ) {


			/**
			 * Each notice must have a key and a message or else an SWP_Notice
			 * object will not be able to be instantiated.
			 *
			 */
            if ( empty( $data['key'] ) || empty( $data['message'] ) ) {
                continue;
            }


			/**
			 * If this notice has a "Call to Action" then use it, otherwise we
			 * set the $ctas value to an empty array so we at least have something
			 * to pass in to the SWP_Notice class.
			 *
			 */
            $ctas = !empty( $data['ctas'] ) ? $data['ctas'] : array();


			/**
			 * This is what actually creates the notice. This will instantiate
			 * a notice object. The SWP_Notice class will add it's own html
			 * render methods to the appropriate hooks to make sure that it shows
			 * up both on the admin dashboard and on our options page.
			 *
			 */
            $notice = new SWP_Notice( $data['key'], $data['message'], $ctas );


			/**
			 * If the notice has a start date, go ahead and set it. The notice
			 * will not be displayed prior to this date.
			 *
			 */
            if ( isset( $data['start_date'] ) ) {
                $notice->set_start_date( $data['start_date'] );
            }


			/**
			 * If the notice has an end date, go ahead and set it. The notice
			 * will not be displayed after this date has passed.
			 *
			 */
            if ( isset( $data['end_date'] ) ) {
                $notice->set_end_date( $data['end_date'] );
            }


			/**
			 * If the CTA's are removed, this will simply make the notice have
			 * the default dismissal CTA, "Thanks, I understand."
			 *
			 */
            if ( isset( $data['no_cta'] ) ) {
                $notice->remove_cta();
            }


			/**
			 * This is added to a local property so that when we use the debug
			 * method, ?swp_debug=notice_loader, we will be able to view all
			 * properties of this object.
			 *
			 */
			$this->notices[] = $notice;

		}
	}
}
