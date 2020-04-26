<?php

/**
 * SWP_CURL: A class process API share count requests via cURL
 *
 * @package   SocialWarfare\Functions
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0
 * @since     3.0.0 | 22 FEB 2018 | Refactored into a class-based system.
 *
 */
class SWP_CURL {

	public static function fetch_shares_via_curl_multi( $links ) {

		if ( SWP_Utility::debug( 'is_cache_fresh' ) ) :
			  $started = time();
			  echo "Starting multi curl request at : " . $started;
		endif;

		$curly = array();
		$result = array();

		// multi handle
		$mh = curl_multi_init();

		// loop through $links and create curl handles
		// then add them to the multi-handle
		if( is_array( $links ) ):
			foreach ( $links as $network => $link_data ) :
				if ( $link_data !== 0 || ($link_data !== 0 && $network == 'google_plus') ) :
					$curly[ $network ] = curl_init();

					if ( $network == 'google_plus' ) :

						curl_setopt( $curly[ $network ], CURLOPT_URL, 'https://clients6.google.com/rpc' );
						curl_setopt( $curly[ $network ], CURLOPT_POST, true );
						curl_setopt( $curly[ $network ], CURLOPT_SSL_VERIFYPEER, false );
						curl_setopt( $curly[ $network ], CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . rawurldecode( $link_data ) . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]' );
						curl_setopt( $curly[ $network ], CURLOPT_RETURNTRANSFER, true );
						curl_setopt( $curly[ $network ], CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );

					else :

						$url = (is_array( $link_data ) && ! empty( $link_data['url'] )) ? $link_data['url'] : $link_data;
						curl_setopt( $curly[ $network ], CURLOPT_URL, $url );
						curl_setopt( $curly[ $network ], CURLOPT_HEADER, 0 );
						curl_setopt( $curly[ $network ], CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
						curl_setopt( $curly[ $network ], CURLOPT_FAILONERROR, 0 );
						curl_setopt( $curly[ $network ], CURLOPT_FOLLOWLOCATION, 0 );
						curl_setopt( $curly[ $network ], CURLOPT_RETURNTRANSFER, 1 );
						curl_setopt( $curly[ $network ], CURLOPT_SSL_VERIFYPEER, false );
						curl_setopt( $curly[ $network ], CURLOPT_SSL_VERIFYHOST, false );
						curl_setopt( $curly[ $network ], CURLOPT_TIMEOUT, 3 );
						curl_setopt( $curly[ $network ], CURLOPT_CONNECTTIMEOUT, 3 );
						curl_setopt( $curly[ $network ], CURLOPT_NOSIGNAL, 1 );
						curl_setopt( $curly[ $network ], CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
						// curl_setopt($curly[$network], CURLOPT_SSLVERSION, CURL_SSLVERSION_SSLv3);
					endif;

					curl_multi_add_handle( $mh, $curly[ $network ] );

				endif;
			endforeach;
		endif;

		// execute the handles
		$running = null;

		do {
		   $mrc = curl_multi_exec($mh, $running);
		}

		while ($mrc == CURLM_CALL_MULTI_PERFORM);


		while ($running && $mrc == CURLM_OK) {
			if (curl_multi_select($mh) == -1) {
				usleep(1);
			}

			do {
				$mrc = curl_multi_exec($mh, $running);
			}

			while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}

	  // get content and remove handles
		foreach ( $curly as $network => $content ) {
			$result[ $network ] = curl_multi_getcontent( $content );
			curl_multi_remove_handle( $mh, $content );
		}

		curl_multi_close( $mh );

	  return $result;
	}

	public static function file_get_contents_curl( $url, $headers = null) {
		$ch = curl_init();
		
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
		curl_setopt( $ch, CURLOPT_FAILONERROR, 0 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER,1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $ch, CURLOPT_NOSIGNAL, 1 );
		curl_setopt( $ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );

		if ( $headers ) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
		}
		else {
			curl_setopt( $ch, CURLOPT_HEADER, 0 );
		}

		$cont = @curl_exec( $ch );
		$curl_errno = curl_errno( $ch );
		curl_close( $ch );

		if ( $curl_errno > 0 ) {
			// echo curl_error ( $cont );
			return false;
		}

		return $cont;
	}
}
