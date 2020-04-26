<?php

class Social_Warfare_Addon {
	public function __construct( $args = array() ) {
		$this->establish_class_properties( $args );
		$this->establish_license_key();
		$this->is_registered = $this->establish_resgistration();

		add_action( 'wp_ajax_swp_register_plugin', [$this, 'register_plugin'] );
		add_action( 'wp_ajax_swp_unregister_plugin', [$this, 'unregister_plugin'] );
		add_action( 'wp_ajax_swp_ajax_passthrough', [$this, 'ajax_passthrough'] );

		add_filter( 'swp_registrations', array( $this, 'add_self' ) );
	}

	private function establish_class_properties( $args = array () ) {
		$required= ['name', 'key', 'version'];

		foreach($args as $key => $value) {
			$this->$key = $value;
		}

		foreach($required as $key) {
			if ( !isset( $this->$key ) ) :
				$message = "Hey developer, you must provide us this information for your class: $key => \$value";
				throw new Exception($message);
			endif;
		}
		if ( isset( $this->product_id ) && empty ( $this->store_url ) ) {
			$this->store_url = 'https://warfareplugins.com';
			$message = "You provided `product_id` without a `store_url`. Please provide `store_url` as a top level domain. Using default value " . $this->store_url;
			error_log( $message );
		}
	}


	/**
	 * The callback function used to add a new instance of this
	 * to our swp_registrations filter.
	 *
	 * This should be the last item called in an addon's main class.
	 *
	 * @param array $addons The array of addons currently activated.
	 */
	public function add_self( $addons ) {
		$addons[] = $this;

		return $addons;
	}

	public function establish_license_key() {
		$key = SWP_Utility::get_option( $this->key . '_license_key' );

		if ( !$key ) :
			$old_options = get_option( 'socialWarfareOptions', false );

			if ( isset( $old_options[$this->key . '_license_key']) ) :
				$key = isset( $old_options[$this->key . '_license_key']);
			endif;

		endif;

		$this->license_key = $key ? $key : '';
	}

	public function establish_resgistration() {
		// Get the timestamps setup for comparison to see if a week has passed since our last check
		$current_time = time();

		if ( !( $timestamp = SWP_Utility::get_option( $this->key . '_license_key_timestamp' ) ) ) {
			$timestamp =  0;
		}

		$time_to_recheck = $timestamp + 604800;

		// If they have a key and a week hasn't passed since the last check, just return true...the plugin is registered.
		if( !empty( $this->license_key)  && $current_time < $time_to_recheck ) :
			return true;
		endif;

		// If a week has passed since the last check, ping our API to check the validity of the license key
		if ( !empty( $this->license_key) ) :
			global $swp_user_options;

			$data = array(
				'edd_action' => 'check_license',
				'item_id' => $this->product_id,
				'license' => $this->license_key,
				'url' => $this->site_url,
			);

			$response = wp_remote_retrieve_body( wp_remote_post( $this->store_url , array('body' => $data, 'timeout' => 10 ) ) );

			if( false !== $response ) :
				$license_data = json_decode( $response );

				$swp_user_options[$this->key . '_license_key_timestamp'] = $current_time;

				// If the license was invalid
				if ( isset( $license_data->license ) && 'invalid' === $license_data->license ) :
					$this->license_key = '';

					$swp_user_options[$this->key . '_license_key'] = '';

					update_option( 'social_warfare_settings' , $swp_user_options );

					return false;

				// If the property is some other status, just go with it.
				else :
					update_option( 'social_warfare_settings' , $swp_user_options );

					return true;
				endif;

			// If we recieved no response from the server, we'll just check again next week
			else :
				$swp_user_options[$key.'_license_key_timestamp'] = $current_time;
				update_option( 'social_warfare_settings' , $swp_user_options );

				return true;
			endif;
		endif;

		return false;
	}

	public function check_for_updates() {
		if ( version_compare(SWP_VERSION, $this->core_required) >= 0 ) :

		endif;
	}

	/**
	 * Request to EDD to activate the licence.
	 *
	 * @since  2.1.0
	 * @since  2.3.0 Hooked registration into the new EDD Software Licensing API
	 * @param  none
	 * @return JSON Encoded Array (Echoed) - The Response from the EDD API
	 *
	 */
	public function register_plugin() {
		// Check to ensure that license key was passed into the function
		if ( !empty( $_POST['license_key'] ) ) :

			// Grab the license key so we can use it below
			$key = $_POST['name_key'];
			$license = $_POST['license_key'];
			$item_id = $_POST['item_id'];
			$this->store_url = 'https://warfareplugins.com';

			$api_params = array(
				'edd_action' => 'activate_license',
				'item_id' => $item_id,
				'license' => $license,
				'url' => $this->site_url
			);

			$response =  wp_remote_retrieve_body( wp_remote_post( $this->store_url, array( 'body' => $api_params, 'timeout' => 10 ) ) );

			if ( false != $response ) :

				// Parse the response into an object
				$license_data = json_decode( $response );

				// If the license is valid store it in the database
				if( isset($license_data->license) && 'valid' == $license_data->license ) :

					$current_time = time();
					$options = get_option( 'social_warfare_settings' );
					$options[$key.'_license_key'] = $license;
					$options[$key.'_license_key_timestamp'] = $current_time;
					update_option( 'social_warfare_settings' , $options );

					echo json_encode($license_data);
					wp_die();

				// If the license is not valid
				elseif( isset($license_data->license) &&  'invalid' == $license_data->license ) :
					echo json_encode($license_data);
					wp_die();

				// If some other status was returned
				else :
					$license_data['success'] = false;
					$license_data['data'] = 'Invaid response from the registration server.';
					echo json_encode($license_data);
					wp_die();
				endif;

			// If we didn't get a response from the registration server
			else :
				$license_data['success'] = false;
				$license_data['data'] = 'Failed to connect to registration server.';
				echo json_encode($license_data);
				wp_die();
			endif;
		endif;

		$license_data['success'] = false;
		$license_data['data'] = 'Admin Ajax did not receive valid POST data.';
		echo json_encode($license_data);
		wp_die();

	}


	/**
	 * Request to EDD to deactivate the licence.
	 *
	 * @since  2.1.0
	 * @since  2.3.0 Hooked into the EDD Software Licensing API
	 * @param  none
	 * @return JSON Encoded Array (Echoed) - The Response from the EDD API
	 *
	 */
	public function unregister_plugin() {

		// Setup the variables needed for processing
		$options = get_option( 'social_warfare_settings' );
		$key = $_POST['name_key'];
		$item_id = $_POST['item_id'];
		$response = array('success' => false);

		// Check to see if the license key is even in the options
		if ( !SWP_Utility::get_option( $key . '_license_key' ) ) :
			$response['success'] = true;
			wp_die(json_encode($response));
		endif;

		// Grab the license key so we can use it below
		$license = $options[$key.'_license_key'];

		// Setup the API request parameters
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'item_id' => $item_id,
			'license' => $license,
			'url' => $this->site_url,
		);

		$response =  wp_remote_retrieve_body( wp_remote_post( $this->store_url, array( 'body' => $api_params, 'timeout' => 10 ) ) );
		if ( empty( $response ) ) {
			$response['success'] = false;
			$response['message'] = 'Error making deactivation request to ' . $this->store_url;
			wp_die( json_encode( $response ) );
		}

		$response = json_decode( $response );

		if ( $response->license == 'deactivated' ) {
			$options = get_option( 'social_warfare_settings' );
			$options[$key.'_license_key'] = '';
			update_option( 'social_warfare_settings' , $options );
		}

		wp_die(json_encode($response));
	}

	public function ajax_passthrough() {
		if ( ! check_ajax_referer( 'swp_plugin_registration', 'security', false ) ) {
			wp_send_json_error( esc_html__( 'Security failed.', 'social-warfare' ) );
			die;
		}

		$data = wp_unslash( $_POST ); // Input var okay.

		if ( ! isset( $data['activity'], $data['email'] ) ) {
			wp_send_json_error( esc_html__( 'Required fields missing.', 'social-warfare' ) );
			die;
		}

		if ( 'register' === $data['activity'] ) {
			$response = swp_register_plugin( $data['email'], SWP_Utility::get_site_url() );

			if ( ! $response ) {
				wp_send_json_error( esc_html__( 'Plugin could not be registered.', 'social-warfare' ) );
				die;
			}

			$response['message'] = esc_html__( 'Plugin successfully registered!', 'social-warfare' );
		}

		if ( 'unregister' === $data['activity'] && isset( $data['key'] ) ) {
			$response = swp_unregister_plugin( $data['email'], $data['key'] );

			if ( ! $response ) {
				wp_send_json_error( esc_html__( 'Plugin could not be unregistered.', 'social-warfare' ) );
				die;
			}

			$response['message'] = esc_html__( 'Plugin successfully unregistered!', 'social-warfare' );
		}

		wp_send_json_success( $response );

		die;
	}
}
