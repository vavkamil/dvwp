<?php

/**
* A Class to create and filter the global $swp_user_options;
*
* This class ensures that if options have been added via updates or by installing
* new addons that they are added to the user options array. Conversely, if
* available options have disappeared from deactivating an addon, those options
* will be removed from the global user options array.
*
* @package   SocialWarfare\Functions\Options
* @copyright Copyright (c) 2018, Warfare Plugins, LLC
* @license   GPL-3.0+
* @since     3.3.0   | Created | 06 AUG 2018
* @access    public
*
*/
class SWP_User_Options {


	/**
	 * SWP_Debug_Trait provides useful tool like error handling and a debug
	 * method which outputs the contents of the current object.
	 *
	 */
	use SWP_Debug_Trait;


	/**
	 * The Constructor
	 *
	 * This is designed to pull in the user's options, filter them appropriately,
	 * and then store them in the global $swp_user_options variable so that the
	 * plugin can easily access them as needed.
	 *
	 * @since  3.0.0 | 01 MAR 2018 | Created
	 * @since  3.4.0 | 19 SEP 2018 | Refactored, cleaned, formatted.
	 * @param  void
	 * @return void
	 *
	 */
	public function __construct() {

        $this->establish_option_data();
		$this->filter_option_data();
		$this->globalize_option_data();

		// Defered to End of Cycle: Add all relevant option info to the database.
		add_action( 'wp_loaded', array( $this , 'store_registered_options_data' ), 10000 );
        add_action( 'admin_footer', array( $this, 'debug' ) );
		add_action( 'wp_footer', array( $this, 'debug' ) );
	}


	/**
	 * Pull the user options and registered options from the database and store
	 * them in a local property.
	 *
	 * @since  3.4.0 | 19 SEP 2018 | Created
     * @param  void
	 * @return void
	 *
	 */
	protected function establish_option_data() {
		$this->unfiltered_options = get_option( 'social_warfare_settings', false );
		$this->registered_options = get_option( 'swp_registered_options', false );
		$this->user_options       = $this->unfiltered_options;
	}


    /**
     * Compares what the admin wants to what is available to the admin.
     *
     * @return void
     *
     */
    protected function filter_option_data() {


		/**
		 * If we didn't find any registered options, just bail out and don't
		 * run any of the filters.
		 *
		 */
        if( false === $this->registered_options ) {
			return;
		}

        $this->remove_unavailable_options();
		$this->correct_invalid_values();
		$this->add_option_defaults();
    }


	/**
	 * Assign the options to the global. This global will be used by the
	 * utility function to ensure that we always have valid options for use.
	 *
	 * @since  3.4.0 | 19 SEP 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	protected function globalize_option_data() {
		global $swp_user_options;
		$swp_user_options = $this->user_options;
	}


	/**
	 * Store the options data in the database.
	 *
	 * This will be an array of all available options, all of their available
	 * values, and all of their defaults.
	 *
	 * This is loaded super late to ensure that all available options will have
	 * already been added to the filter so that we can access them here.
	 *
	 * By loading late, it will not be available on this same page load for use
	 * by the filters. It will be available on the next available page load.
	 * However, this should only have to run on the page load when an addon is
	 * activated or deactivated as they won't change any other time so this won't
	 * be an issue.
	 *
	 * @since  3.3.0 | 06 AUG 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function store_registered_options_data() {


		/**
		 * The whitelist ensures that certain options don't get filtered out.
		 * Right now, this is more of a band aid, and in the future, we hope to
		 * make sure that all options get properly registered so that they won't
		 * need to be whitelisted.
		 *
		 */
        $whitelist = $this->generate_whitelist();


		/**
		 * Each option should register it's default value and it's allowable
		 * values via these two hooks. This is the only way for us to tell if an
		 * option is currently available and if the value for this option is
		 * indeed a valid value.
		 *
		 */
        $new_registered_options = array(
            'defaults'  => apply_filters( 'swp_options_page_defaults', array() ),
            'values'    => apply_filters( 'swp_options_page_values', array() )
        );


		/**
		 * There is one registration item for each addon. This filter allows us
		 * to fetch those. Those registration items will contain the registration
		 * key and the registration timestamp. We want to ensure that these items
		 * are not filtered out.
		 *
		 */
        $registrations = apply_filters( 'swp_registrations', array() );


		/**
		 * We're going to loop through the whitelist and add them to the list of
		 * registered options. This will make them available on the flip side
		 * ensuring that these specific options do not get filtered out of the
		 * options array.
		 *
		 */
        foreach( $whitelist as $key ) {


			/**
			 * If this option doesn't actually exist in the user options, then
			 * we don't actually need to whitelist it.
			 *
			 */
            if ( !isset( $this->unfiltered_options[$key] ) ) {
				continue;
			}

		    $new_registered_options['defaults'][$key] = $this->unfiltered_options[$key];
            $new_registered_options['values'][$key]['type'] = 'none';
            $new_registered_options['values'][$key]['values'] = $this->unfiltered_options[$key];

        }


		/**
		 * If the registered options have changed since the last update, we'll
		 * need to go ahead and update them in the database so that they are
		 * current.
		 *
		 */
		if( $new_registered_options != $this->registered_options ) {
			update_option( 'swp_registered_options', $new_registered_options );
		}
	}

	/**
	 * Generate a whitelist of items to NOT delete when filtering.
	 *
	 * @since  3.2.0 | 01 JUL 2018 | Created
	 * @since  3.4.0 | 19 SEP 2018 | Added og:type to whitelist.
	 * @todo   Make the og:type auto generate when the option is created in the
	 *         options page class.
	 * @param  void
	 * @return array An array of whitelisted option keys.
	 *
	 */
	public function generate_whitelist() {


		/**
		 * The addons will contain the registration data items to ensure that
		 * when filtering occurs, we do not filter out license keys or tiemstamps.
		 *
		 * The whitelist is the list of items that don't necessarily get
		 * registered from the options page, so we manually whitelist them.
		 *
		 */
        $addons    = apply_filters( 'swp_registrations', array() );
        $whitelist = array(
			'last_migrated',
			'bitly_access_token',
			'bitly_access_login',
			'bitly_authentication'
		);


		/**
		 * If the user doesn't have any addons installed, we just bail and
		 * return the existing whitelist from above.
		 *
		 */
        if ( empty( $addons) ) {
            return $whitelist;
        }


		/**
		 * If the user does have addons installed, we need to add the license
		 * key and the license key timestamp to the whitelist array to ensure
		 * that we don't filter it out.
		 *
		 */
        foreach( $addons as $addon ) {
            $whitelist[] = $addon->key . '_license_key';
            $whitelist[] = $addon->key . '_license_key_timestamp';
        }

		return $whitelist;
	}


	/**
	 * Filter out non-existent options.
	 *
	 * This checks if an option is still registered and removes it from the user
	 * options if it does not exist.
	 *
	 * USE CASE: The user may have had an addon installed that provided several
	 *           new options. Once that addon is removed, those options are still
	 *           stored in the database. This will filter them out.
	 *
	 * @since  3.3.0 | 06 AUG 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	private function remove_unavailable_options() {

		/**
		 * Compare the registered defaults to the options that the user has
		 * saved in the database. Only save those keys that are registered.
		 *
		 */
        $defaults          = array_keys( $this->registered_options['defaults'] );
        $options           = array_keys ( $this->user_options );
        $available_options = array_intersect( $defaults, $options );


		/**
		 * Loop through each of the options in the users options and validate
		 * that it is setup properly and doesn't need filtered out.
		 *
		 */
        foreach( $this->user_options as $key => $value ) {


			/**
			 * The order_of_icons options is a unique case so we've broken out
			 * the logic that controls it's filtering to a separate method.
			 *
			 */
            if ( $key == 'order_of_icons' ) {
                $value = $this->filter_order_of_icons( $value );
                $this->user_icons[$key] = $value;
                continue;
            }


			/**
			 * If a given user option is not listed in the list of registered
			 * options, we need to filter it out of the user options.
			 *
			 */
            if ( !in_array( $key, $available_options ) ) {
                unset( $this->user_options[$key] );
            }
        }
	}


	/**
	 * Filter the order_of_icons.
	 *
	 * This is the option that controls which social networks the user has
	 * active and in what order they are supposed to appear on the buttons panel.
	 * Since it is a very unique options, it gets it own method (this) to process
	 * and control it's filtering.
	 *
	 * @since  3.3.0 | 01 JUL 2018 | Created
	 * @param  array  $user_icons An array of social networks.
	 * @return array              The modified array of social networks.
	 *
	 */
    private function filter_order_of_icons( $user_icons = array() ) {


		/**
		 * Fetch the available registered options and the user selected options
		 * so that we can compare them to each other below.
		 *
		 */
        $networks   = $this->registered_options['values']['order_of_icons']['values'];
        $user_icons = $this->user_options['order_of_icons'];


		/**
		 * Loop through each of the user's selected networks and remove any that
		 * are not available. For example, if they have pro networks selected,
		 * but pro is not longer installed, these will need to be filtered out.
		 *
		 */
        foreach( $user_icons as $network_key ) {
            if ( empty( $networks[$network_key] ) ) {
                unset( $user_icons[$network_key] );
            }
        }


        /**
         * If the user does not have any networks selected (like on a fresh
         * install) then simply create some defaults for them and then return.
         *
         */
        if ( empty ( $user_icons ) ) {
            $user_icons = $this->registered_options['defaults']['order_of_icons'];
        }

        return $user_icons;
    }


	/**
	 * Correct any values that may be invalid.
	 *
	 * @since  3.3.0 | 06 AUG 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	private function correct_invalid_values() {
        $defaults = $this->registered_options['defaults'];
		$values   = $this->registered_options['values'];

		foreach( $this->user_options as $key => $value ) {
			if( $values[$key]['type'] == 'select' && !array_key_exists( $value, $values[$key]['values']) ) {
				$this->user_options[$key] = $defaults[$key];
			}
		}
	}


	/**
	 * Creates the default value for any new keys.
	 *
	 * @since  3.0.8  | 16 MAY 2018 | Created the method.
	 * @since  3.0.8  | 24 MAY 2018 | Added check for order_of_icons
	 * @since  3.1.0  | 13 JUN 2018 | Replaced array bracket notation.
	 * @since  3.3.0  | 06 AUG 2018 | Moved from database migration class.
	 * @param  void
	 * @return void
	 *
	 */
	private function add_option_defaults() {
		$defaults = $this->registered_options['defaults'];

		foreach ( $defaults as $key => $value ) {
			 if ( !isset( $this->user_options[$key] ) ) {
				 $this->user_options[$key] = $value;
			 }
		}
    }
}
