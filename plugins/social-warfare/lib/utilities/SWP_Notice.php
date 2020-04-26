<?php

/**
 * SWP_Notice
 *
 * A class to control the creation and display of admin notices throughout the
 * WordPress dashboard and on the Social Warfare settings page. This class also
 * creates the framework and functionality for both permanently and temporarily
 * dismissing these notices. It also allows for creating start dates, end dates,
 * and various types of calls-to-actions used to dismiss these notices.
 *
 * @package   SocialWarfare\Utilities
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     3.0.9 | 07 JUN 2018 | Created
 * @access    public
 *
 */
class SWP_Notice {


	/**
	 * The Magic __construct method
	 *
	 * This method will initialize our notice object and then add the necessary hooks to
	 * allow it to be displayed and to be dismissed via admin-ajax.php.
	 *
	 * @since 3.0.9 | 07 JUN 2018 | Created
	 * @param string $key     A unique key for this notice.
	 * @param string $message The message for this notice
	 *
	 */
    public function __construct( $key = "", $message = "", $ctas = array() ) {
        $this->set_key( $key );
        $this->init();
        $this->set_message( $message );
        $this->actions = $ctas;
        $this->no_cta = false;

		// Add hooks to display our admin notices in the dashbaord and on our settings page.
        add_action( 'admin_notices', array( $this, 'print_HTML' ) );
        add_action( 'swp_admin_notices', array( $this, 'get_HTML' ) );

		// Add a hook for permanently dismissing a notice via admin-ajax.php
        add_action( 'wp_ajax_dismiss', array( $this, 'dismiss' ) );
        add_action( 'wp_ajax_nopriv_dismiss', array( $this, 'dismiss' ) );

    }


	/**
	 * Initialize the basics of this property.
	 *
	 * This method checks for the dismissed_notices options array in the database.
	 * If it doesn't exist, it creates it as an empty array. It then stores that
	 * array in a local property to control the display or non-display of this
	 * notice. It then finds the attributes for this specific notice and stores it
	 * in the local $data property.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access public
	 * @param  null
	 * @return null
	 *
	 */
    public function init() {
        $notices = get_option( 'social_warfare_dismissed_notices', false );

        if ( false === $notices ) {
            update_option( 'social_warfare_dismissed_notices', array() );
            $notices = array();
        }

        $this->notices = $notices;

        if ( isset( $notices[$this->key] ) ) :
            $this->data = $notices[$this->key];
        endif;
    }


	/**
	 * A method to determine if this notice should be displayed.
	 *
	 * This method lets the class now if this notice should be displayed or not. It checks
	 * thing like the start date, the end date, the dimissal status if it was temporarily
	 * dismissed versus permanently dismissed and so on.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access public
	 * @param  null
	 * @return bool Default true.
	 *
	 */
    public function should_display_notice() {
        $now = new DateTime();
		$now = $now->format('Y-m-d H:i:s');

        // If the start date has not been reached.
		if ( isset( $this->start_date ) && $now < $this->start_date ) {
			return false;
		}

		// If the end date has been reached.
		if( isset( $this->end_date ) && $now > $this->end_date ) {
			return false;
		}

        //* No dismissal has happened yet.
        if ( empty( $this->data['timestamp']) ) :
            return true;
        endif;

        //* They have dismissed a permadismiss.
        if ( isset( $this->data['timestamp'] ) && $this->data['timeframe'] == 0) {
            return false;
        }

        //* They have dismissed with a temp CTA.
        if ( isset( $this->data['timeframe'] ) && $this->data['timeframe'] > 0 ) {

            $expiry = $this->data['timestamp'];

            return $now > $expiry;
        }

        return true;
    }


	/**
	 * Processes notice dismissals via ajax.
	 *
	 * This is the method that is added to the Wordpress admin-ajax hooks.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access public
	 * @param  null
	 * @return null The response from update_option is echoed.
	 *
	 */
    public function dismiss() {
        $key = $_POST['key'];
        $timeframe = $_POST['timeframe'];
        $now = new DateTime();

        if ( 0 < $timeframe ) {
            $timestamp = $now->modify("+$timeframe days")->format('Y-m-d H:i:s');
        } else {
            $timestamp = $now->format('Y-m-d H:i:s');
        }

        $this->notices[$key]['timestamp'] = $timestamp;
        $this->notices[$key]['timeframe'] = $timeframe;

        echo json_encode( update_option( 'social_warfare_dismissed_notices', $this->notices ) );
        wp_die();
    }


	/**
	 * A method to allow you to set the message text for this notice.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access public
	 * @param  string $message A string of text for the notices message.
	 * @return object $this    Allows for method chaining.
	 *
	 */
    public function set_message( $message ) {
        if ( !is_string( $message ) ) :
            throw("Please provide a string for your database key.");
        endif;

        $this->message = $message;

        return $this;
    }


	/**
	 * A method to allow you to set the unique key for this notice.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access protected
	 * @param  string $key   A string representing this notices unique key.
	 * @return object $this  Allows for method chaining.
	 *
	 */
    protected function set_key( $key ) {
        if ( !is_string ( $key ) ) :
            throw("Please provide a string for your database key.");
        endif;

        $this->key = $key;

        return $this;
    }


	/**
	 * Set a start date.
	 *
	 * This will allow us to schedule messages to be displayed at a specific date in the
	 * future. For example, before the StumbleUpon service goes away, we may want to post
	 * a notice letting folks know that it WILL BE going away. The day that they actually
	 * go away could be the start date for a notice that says that they HAVE gone away.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access public
	 * @param  string $start_date A str date formatted to 'Y-m-d H:i:s'
	 * @return $this Allows for method chaining
	 * @TODO   Add a type check, if possible, for a properly formatted date string.
	 *
	 */
	public function set_start_date( $start_date ) {
        if ( $this->is_date( $start_date ) ) :
		    $this->start_date = $start_date;
        endif;

		return $this;
	}


	/**
	 * Set an end date.
	 *
	 * This will allow us to schedule messages to stop being displayed at a specific date
	 * in the future. For example, before the StumbleUpon service goes away, we may want
	 * to post a notice letting folks know that it WILL BE going away. The day that they
	 * actually go away could be the end date for that notice and the start date for a
	 * notice that says that they HAVE gone away. Additionally, we may only want to notify
	 * people about StumbleUpon having gone away for 60 days after it happens. After that,
	 * we can just assume that they've probably heard from somewhere else and not worry
	 * about showing a notice message.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access public
	 * @param  string $end_date A str date formatted to 'Y-m-d H:i:s'
	 * @return $this Allows for method chaining
	 * @TODO   Add a type check, if possible, for a properly formatted date string.
	 *
	 */
	public function set_end_date( $end_date ) {
        if ( $this->is_date( $end_date ) ) :
		    $this->end_date = $end_date;
        endif;

		return $this;
	}


    /**
    * Creates the interactive CTA for the notice.
    *
    * @since  3.0.9 | 07 JUN 2018 | Created
    * @access public
    * @param  string $action Optional. The message to be displayed. Default "Thanks, I understand."
    * @param  string $href Optional. The outbound href.
    * @param  string $class Optional. The CSS classname to assign to the CTA.
    * @param  string $timeframe
    * @return $this Allows for method chaining.
    *
    */
    public function add_default_cta()  {
        $cta = array();
        $cta['action']    = "Thanks, I understand.";
        $cta['href']      = '';
        $cta['target']    = '_self';
        $cta['class']     = '';
		$cta['timeframe'] =  0;

        $this->actions[] = $cta;

        return $this;
    }


	/**
	 * Render out the HTML.
	 *
	 * Ideally, everything before this method will create a beautiful data-oriented
	 * object. The only HTML that should be compiled should be inside this method.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access public
	 * @param  null
	 * @return string The compiled HTML of the dashboard notice.
	 *
	 */
    public function render_HTML() {
        if ( empty( $this->actions ) && false === $this->no_cta) :
            $this->add_default_cta();
        endif;

        $html = '<div class="swp-dismiss-notice notice notice-info " data-key="' . $this->key . '">';
            $html .= '<p>' . $this->message . ' - Warfare Plugins Team</p>';
            $html .= '<div class="swp-actions">';

                foreach( $this->actions as $cta) {
                    $class = isset( $cta['class'] ) ? $cta['class'] : '';
                    $href = isset( $cta['href'] ) ? $cta['href'] : '';
                    $target = isset( $cta['target'] ) ? $cta['target'] : '';
                    $timeframe = isset( $cta['timeframe'] ) ?  $cta['timeframe'] : 0;
                    $html .= '<a class="swp-notice-cta ' . $class . '" href="' . $href . '" target="' . $target . '" data-timeframe="' . $timeframe .'">';
                        $html .= $cta['action'];
                    $html .= "</a>";
                }

            $html .= '</div>';
        $html .= '</div>';

        $this->html = $html;

        return $this;
    }


	/**
	 * Gets (returns) the HTML for this notice.
	 *
	 * We have two separate methods for this. One for returning the HTML, and
	 * one for echoing the html. This one returns it.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access public
	 * @param  string $notices The string of notices to be modified.
	 * @return string          The modified string of notices' html.
	 *
	 */
    public function get_HTML( $notices = '' ) {

        if ( !$this->should_display_notice() ) :
            return $notices;
        endif;

        return $this->html;
    }


	/**
	 * Echos the HTML for this notice.
	 *
	 * We have two separate methods for this. One for returning the HTML, and
	 * one for echoing the html. This one echos it.
	 *
	 * @since  3.0.9 | 07 JUN 2018 | Created
	 * @access public
	 * @param  string $notices The string of notices to be modified.
	 * @return string          The modified string of notices' html.
	 *
	 */
    public function print_HTML() {
        if ( !$this->should_display_notice() ) :
            return;
        endif;

        if ( empty( $this->html ) ) :
            $this->render_HTML();
        endif;

        echo $this->html;

        return $this;
    }

    /**
     * Checks whether a string is formatted as our default Date format.
     *
     * @since  3.0.9 | 08 JUN 2018 | Created
     * @param string $string The datetime string in question.
     * @return bool True iff the string is of the format 'Y-m-d h:i:s'.
     *
     */
    private function is_date( $string ) {
        return DateTime::createFromFormat( 'Y-m-d h:i:s', $string ) !== false;
    }


    /**
     * Prevents a CTA from being displayed on the notice.
     *
     * In cases where we require the user to take action, we need them
     * to follow the directions in the message before removing the notice.
     *
     * @since  3.1.0 | 05 JUL 2018 | Created the method.
     * @return SWP_Notice $this, for method chaining.
     *
     */
     public function remove_cta() {
         //* Force the ctas to an empty array so render can still loop over it.
         $this->actions = array();

         $this->no_cta = true;

         return $this;
     }
}
