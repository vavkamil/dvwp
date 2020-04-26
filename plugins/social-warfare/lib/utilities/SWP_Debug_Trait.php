<?php

/**
 * SWP_Debug_Trait
 *
 * The purpose of this trait is to allow access to commonly used methods
 * throughout the various classes of the plugin without always having to
 * include the SWP_Abstract class as a parent class.
 *
 * The SWP_Abstract class was primary designed to support the classes
 * used to create the options page. As such, using it as a parent class for
 * something like, say, SWP_Social_Network seemed out of order as the social
 * network objects would now have all of the properties that were used on the
 * options objects.
 *
 * This means that the object would not be structured the way that I would
 * prefer. As such, I think we should rename it to SWP_Options_Abstract and
 * move it into the options folder and only use it for a parent class in
 * the options classes. We can then migrate all methods that we want to use
 * everywhere else into this trait.
 *
 * @since 3.0.0 | 07 APR 2018 | Created
 *
 */
trait SWP_Debug_Trait {


	/**
    * Give classes an error handling method.
    *
    * @since  3.0.0 | 07 APR 2018 | Created
    * @param  mixed $message The message to send as an error.
    * @return object Exception An exception with the passed in message.
    *
    */
    public function _throw( $message ) {
        ob_start();
        print_r( debug_backtrace()[1]['args'] );
        $dump = ob_get_clean();

        if ( is_string( $message ) ) {
            throw new Exception( get_class( $this ) . '->' . debug_backtrace()[1]['function'] . '() ' . $message . ' Here is what I received: ' . $dump );
        } else {
            throw new Exception( get_class( $this ) . '->' . debug_backtrace()[1]['function'] . '() ' . PHP_EOL . var_dump( $message ) );
        }
    }


	/**
	 * A method for debugging and outputting the class object.
	 *
	 * This method allows you to input a URL debug paramater into the browser's
	 * address bar in order to easily output the entire contents of any given
	 * object. It is called by using the lowercase name of the class without
	 * the swp_ prefix. This is used following the ?swp_debug= parameter.
	 *
	 * Example: To debug SWP_Post_Cache, use ?swp_debug=post_cache.
	 *
	 * @since  3.1.0 | 25 JUN 2018 | Created
	 * @since  3.4.0 | 18 OCT 2018 | Moved into this trait.
	 * @param  void
	 * @return void
	 *
	 */
	public function debug() {
		$class_name = str_replace('swp_', '', strtolower( __CLASS__ ) );
		if( true === SWP_Utility::debug( $class_name ) ) {
			echo "<pre>", var_dump( $this ), "</pre>";
		}
	}




}
