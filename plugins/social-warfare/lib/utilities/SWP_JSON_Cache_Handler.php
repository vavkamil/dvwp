<?php

/**
 * A Class for Fetching Remote JSON data and caching it in a manner that will
 * easily allow other classes to access the data for the purpose of generating
 * notices, updating the sidebar, etc.
 *
 * Everything is stored in local properties to allow the debug method to simply
 * dump the $this item allowing us to see the results of everything that has
 * been processed by this class.
 *
 * @package   SocialWarfare\Functions
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     3.1.0 | 27 JUN 2018 | Created
 *
 */
class SWP_JSON_Cache_Handler {


	/**
	 * SWP_Debug_Trait provides useful tool like error handling and a debug
	 * method which outputs the contents of the current object.
	 *
	 */
	use SWP_Debug_Trait;


	/**
	 * The fetched from the remote JSON file.
	 *
	 * @var string
	 *
	 */
	private $response = '';


	/**
	 * The responsed parsed into an associative array.
	 *
	 * @var array
	 *
	 */
	private $parsed_response = array();


	/**
	 * The cached JSON data fetched from the database.
	 *
	 * @var array
	 *
	 */
	private $cached_data = array();


	/**
	 * Instantiate the class object.
	 *
	 * Check if the cache is fresh, if not, ping the JSON file on our server,
	 * parse the results, and store them in an options field in the database.
	 *
	 * @since  3.1.0 | 28 JUN 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function __construct() {
		if( false === $this->is_cache_fresh() ):
			$this->fetch_new_json_data();
			$this->debug();
		endif;
	}


	/**
	 * Fetch new JSON data.
	 *
	 * @since  3.1.0 | 28 JUN 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	private function fetch_new_json_data() {

		// Fetch the response.
        $response = wp_remote_get( 'https://warfareplugins.com/json_updates.php' );
		$this->response = wp_remote_retrieve_body( $response );

		// Create the cache data array.
		$this->parsed_response = array();

		if( !empty($this->response) ):
			$this->parsed_response = json_decode( $this->response , true );
		endif;

		$this->parsed_response['timestamp'] = time();

		// Store the data in the database.
		update_option('swp_json_cache' , $this->parsed_response , true );

	}


	/**
	 * A method to determin if the cached data is still fresh.
	 *
	 * @since  3.1.0 | 28 JUN 2018 | Created
	 * @param  void
	 * @return boolean true if fresh, false if expired.
	 *
	 */
	private function is_cache_fresh() {

		// If we're debugging, the cache is expired and needs to fetch.
		if( true == SWP_Utility::debug( 'json_fetch' ) ):
			return false;
		endif;

		$this->cache_data = get_option('swp_json_cache');

		// If no cached data, the cache is not fresh.
		if( false === $this->cache_data):
			return false;
		endif;

		// Forumlate the timestamps.
		$timestamp = $this->cache_data['timestamp'];
		$current_time = time();
		$time_between_checks = ( 6 * 60 * 60 );

		// Compare the timestamps.
		if ($current_time > $timestamp + $time_between_checks ) :
			return false;
		endif;

		return true;

	}
}
