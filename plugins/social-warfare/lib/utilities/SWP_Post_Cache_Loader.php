<?php

/**
 * The Global SWP_Post_Caches Object
 *
 * This class allows for the creation of a global $SWP_Post_Caches object. This
 * will be called and instantiated from the main loader class. It will then be
 * made available to classes like the buttons_panel class which can then use it
 * to fetch share counts for specific posts via their post_cache objects.
 *
 * This class is essentially a loader class for the post_cache objects.
 *
 * @package   SocialWarfare\Functions\Utilities
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     3.1.0 | 20 JUN 2018 | Created
 * @access    public
 *
 */
class SWP_Post_Cache_Loader {


    /**
    * Array of the currently loaded SWP_Post_Cache objects, indexed by post_id.
    * These are meant to be accessed by the Buttons Panel, for example.
    *
    * @var array
    *
    */
    public $post_caches = array();


	/**
	 * Load the class and queue up the admin hooks.
	 *
	 * @since  3.1.0 | 25 JUN 2018 | Created
	 * @since  3.4.0 | 17 OCT 2018 | Removed legacy AJAX methods (hooked here).
	 * @param  void
	 * @return void
	 *
	 */
	public function __construct() {
        add_action( 'save_post', array( $this, 'update_post' ) );
		add_action( 'publish_post', array( $this, 'update_post' ) );
	}


	/**
	 * Gets the post_cache object for a specific post.
	 *
	 * Since all requests for post_cache objects should be called via this
	 * method, we shouldn't have to worry about a post_cache object being
	 * instantiated more than once for any given post. As such, we can use the
	 * instantiation of that object to call functions that we want to make sure
	 * only ever get run once, like updating the cached data.
	 *
	 * @since  3.1.0 | 20 JUNE 2018 | Created
	 * @param  integer $post_id The ID of the post being requested.
	 * @return object           The post_cache object for the post.
	 *
	 */
    public function get_post_cache( $post_id ) {

		if ( empty( $this->post_caches[$post_id] ) ) {
			$this->post_caches[$post_id] = new SWP_Post_Cache( $post_id );
		}

		return $this->post_caches[$post_id];
	}


	/**
	 * Resets the cache timestamp so that it will rebuild during the next page load.
	 *
	 * @since  3.1.0 | 26 JUN 2018 | Created the method.
	 * @since  3.4.0 | 17 OCT 2018 | Moved publish conditional from constructor
	 *                               into this method.
	 * @param  void
	 * @return void
	 *
	 */
	public function update_post( $post_id ) {


		/**
		 * If the post isn't published, we don't need to do anything with the
		 * post cache. Just ignore it.
		 *
		 */
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return;
		}


		/**
		 * Instantiate a post cache object for this post, and then call the
		 * public method delete_timestamp();
		 *
		 */
		$Post_Cache = new SWP_Post_Cache( $post_id );
		$Post_Cache->delete_timestamp();
	}

}
