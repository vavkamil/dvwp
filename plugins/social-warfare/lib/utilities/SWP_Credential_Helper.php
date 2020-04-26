<?php
/**
 * Of data we handle, log in credentials and access tokens are the most sensitive.
 * We want to keep them as safe and secure as possible.
 *
 * We encode the entire swp_authorizations option, as well as the keys and
 * values for those options. This way no sensitive data is kept as plaintext
 * in the database.
 *
 * Since we are using two way encoding/decoding, this means other users who
 * know the encoding functions are also able to access this data.
 *
 * To prevent this would be to store the data in our own server,
 * rather than the user's server, but that is not an option.
 *
 */
class SWP_Credential_Helper {


	public static $swp_authorizations;


	/**
	 * Retrieve a token granted by a third party service.
	 *
	 * We use base64_encode so that no network name or token is stored as
	 * plaintext in the database.
	 *
	 * This is not the same as hashing it like a password.
	 *
	 * @since 3.5.0 | 10 JAN 2018 | Created.
	 * @param  string $network The host service that provided the token.
	 * @param  string $field    The type of token to fetch. Usually 'access_token'.
	 * @return mixed  A string token, if it exists, else `false`.
	 *
	 */
	public static function get_token( $network, $field = 'access_token' ) {
		$encoded_tokens = self::get_authorizations();
		$encoded_key = base64_encode( $network );
		$encoded_field = base64_encode( $field );

		// We do not have any data for this network.
		if ( empty ( $encoded_tokens[$encoded_key] ) ) {
			return false;
		}


		if ( !empty( $encoded_tokens[$encoded_key][$encoded_field] ) ) {
			$encoded_token = $encoded_tokens[$encoded_key][$encoded_field];
			return base64_decode( $encoded_token );
		}

		// We do not have the requested type of token for this network.
		return false;
	}


	/**
	 * Deletes network data, if it exists.
	 *
	 * @since 3.5.0 | 10 JAN 2018 | Created.
	 * @param  string $network The network with data to delete.
	 * @param  string $field   The type of data to remove. Most often 'access_token'.
	 * @return bool            True iff deleted, else false.
	 *
	 */
	public static function delete_token( $network, $field = 'access_token' ) {
		// No encoding/decoding necessary. It is handled in store_data.
		return self::store_data( $network, $field, '' );
	}


	/**
	 * When processing network authentications, the user is ultimately
	 * redirected back to the Social Warfare options.
	 *
	 * If the authentication was a success, we can store the token for later use.
	 *
	 * The paramters these functions look for are generated in
	 * https://warfareplugins.com/authorizations/${network}/return_token.php.
	 *
	 * @since 3.5.0 | 10 JAN 2018 | Created.
	 * @param void
	 * @return void
	 *
	 */
	public static function options_page_scan_url() {
		if ( empty( $_GET['network'] ) || empty( $_GET['access_token'] ) ) {
			return false;
		}

		// We have a new access_token.
		$network = $_GET['network'];
		self::store_data( $network, 'access_token', $_GET['access_token'] );

		// Not every network uses access_secret.
		if ( isset( $_GET['access_secret'] ) ) {
			self::store_data( $network, 'access_secret', $_GET['access_secret'] );
		}
	}


	/**
	 * Fetches and prepares options for use by SWP_Credential_Helper.
	 *
	 * The encoding is not secure, but it obfuscates the data.
	 *
	 * @since 3.5.0 | 10 JAN 2018 | Created.
	 * @param  array $authorizations	The data to store.
	 * @return array  					The authorizations, or an empty array.
	 *
	 */
	public static function get_authorizations() {
		if ( !empty( self::$swp_authorizations ) ) {
			return self::$swp_authorizations;
		}

		$encoded_json = get_option( 'swp_authorizations', array() );
		if ( empty( $encoded_json ) ) {
			return array();
		}

		$encoded_tokens = json_decode( base64_decode( $encoded_json ), true );
		self::$swp_authorizations = $encoded_tokens;

		return $encoded_tokens;
	}


	/**
	 * Save a token granted by a third party service.
	 *
	 * We use base64_encode so that no network name or token is stored as
	 * plaintext in the database.
	 *
	 * This is not the same as hashing it like a password.
	 *
	 * @since 3.5.0 | 10 JAN 2018 | Created.
	 * @param  string $network 	The host service that provided the token.
	 * @param  string $field	The type of token to fetch. Usually 'access_token'.
	 * @return bool  			True iff updated, else false.
	 *
	 */
	public static function store_data( $network, $field, $data ) {
		$encoded_key = base64_encode( $network );
		$encoded_field = base64_encode( $field );
		$encoded_data = base64_encode( $data );

		$encoded_tokens = self::get_authorizations();

		/**
		 * A network may have both an access key and access secret.
		 * Setting $tokens[$network_key] as an array keeps the network
		 * open for arbitrary data storage.
		 *
		 */
		if ( empty( $encoded_tokens[$encoded_key] ) ) {
			$encoded_tokens[$encoded_key] = array();
		}

		$encoded_tokens[$encoded_key][$encoded_field] = $encoded_data;

		return self::update_authorizations( $encoded_tokens );
	}


	/**
	 * Encodes and stores the options in the database.
	 *
	 * The encoding is not secure, but it obfuscates the data.
	 *
	 * @since 3.5.0 | 10 JAN 2018 | Created.
	 * @param  array $authorizations	The data to store.
	 * @return bool  					True iff the options were successfully updated.
	 *
	 */
	public static function update_authorizations( $encoded_tokens ) {
		if ( !is_array( $encoded_tokens ) ) {
			error_log( 'SWP_Credential_Helper->update_options() requires parameter 1 to be an array.' );
			return false;
		}

		$encoded_json = base64_encode( json_encode( $encoded_tokens ) );

		self::$swp_authorizations = $encoded_tokens;
		return update_option( 'swp_authorizations', $encoded_json );
	}
}
