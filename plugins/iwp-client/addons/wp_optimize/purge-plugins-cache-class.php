<?php

if ( ! defined('ABSPATH') )
	die();

class IWP_MMB_PURGE_CACHE extends IWP_MMB_Core
{
	
	function __construct()
	{
	    parent::__construct();
	}
	/*
  * Public function, Will Clear all cache plugins
  */
	public function purgeAllCache($params = array()){
		$cleanup_values = array();
		$cleanup_values['value_array'] = array();
		$text = '';
		if (empty($params) || isset($params['wpfc_cache'])) {
			$response = $this->deleteALLWPFCCache();
			if (!empty($response['success'])) {
				$text .= "<span class='wpm_results_db'> WP Fastest Cache"." : " . $response['success'] . "</span><br>";
				$cleanup_values['value_array']['wpfc_cache'] = $values['value'];
			}elseif(!empty($response['error'])){
				$text .= "<span class='wpm_results_db'> WP Fastest Cache"." : " . $response['error'] . "</span><br>";
				$cleanup_values['value_array']['wpfc_cache'] = 'WP Fastest Cache';
			}
		}
		if (empty($params) || isset($params['wp_super_cache'])) {
			$response = $this->deleteALLWPSuperCache();
			if (!empty($response['success'])) {
				$text .= "<span class='wpm_results_db'> WP Super Cache"." : " . $response['success'] . "</span><br>";
				$cleanup_values['value_array']['wp_super_cache'] = $values['value'];
			}elseif(!empty($response['error'])){
				$text .= "<span class='wpm_results_db'> WP Super Cache"." : " . $response['error'] . "</span><br>";
				$cleanup_values['value_array']['wp_super_cache'] = 'WP Super Cache';
			}
		}
		if (empty($params) || isset($params['w3_total_cache'])) {
			$response = $this->deleteALLW3TotalCache();
			if (!empty($response['success'])) {
				$text .= "<span class='wpm_results_db'> W3 Total Cache"." : " . $response['success'] . "</span><br>";
				$cleanup_values['value_array']['w3_total_cache'] = $values['value'];
			}elseif(!empty($response['error'])){
				$text .= "<span class='wpm_results_db'> W3 Total Cache"." : " . $response['error'] . "</span><br>";
				$cleanup_values['value_array']['w3_total_cache'] = 'W3 Total Cache';
			}
		}
		if (empty($params) || isset($params['wp_rocket_cache'])) {
			$response = $this->deleteALLWPRocketCache();
			if (!empty($response['success'])) {
				$text .= "<span class='wpm_results_db'> WP Rocket Cache"." : " . $response['success'] . "</span><br>";
				$cleanup_values['value_array']['wp_rocket_cache'] = $values['value'];
			}elseif(!empty($response['error'])){
				$text .= "<span class='wpm_results_db'> WP Rocket Cache"." : " . $response['error'] . "</span><br>";
				$cleanup_values['value_array']['wp_rocket_cache'] = 'WP Rocket Cache';
			}
		}
		if (empty($params) || isset($params['comet_cache'])) {
			$response = $this->deleteALLCometCache();
			if (!empty($response['success'])) {
				$text .= "<span class='wpm_results_db'> Comet Cache"." : " . $response['success'] . "</span><br>";
				$cleanup_values['value_array']['comet_cache'] = $values['value'];
			}elseif(!empty($response['error'])){
				$text .= "<span class='wpm_results_db'> Comet Cache"." : " . $response['error'] . "</span><br>";
				$cleanup_values['value_array']['comet_cache'] = 'Comet Cache';
			}
		}

		if (empty($params) || isset($params['auto_optimize'])) {
			$response = $this->deleteAllautoptimizeCache();
			if (!empty($response['success'])) {
				$text .= "<span class='wpm_results_db'> Autoptimize"." : " . $response['success'] . "</span><br>";
				$cleanup_values['value_array']['auto_optimize'] = $values['value'];
			}elseif(!empty($response['error'])){
				$text .= "<span class='wpm_results_db'> Autoptimize"." : " . $response['error'] . "</span><br>";
				$cleanup_values['value_array']['auto_optimize'] = 'Autoptimize';
			}
		}

		if ($text !==''){
			$cleanup_values['message'] = $text;
			return $cleanup_values;
		}
	}
	/*
	  * Public function, Will return the WpFastestCache is loaded or not
	  */

	public function checkWPFCPlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'wp-fastest-cache/wpFastestCache.php' ) ) {
			@include_once(WP_PLUGIN_DIR . '/wp-fastest-cache/wpFastestCache.php');
			if (class_exists('WpFastestCache')) {
	    		return true;
	    	}
	    }
		return false;
	}

	/*
	  * Public function, Will return the WP Super cache Plugin is loaded or not
	  */

	public function checkWPSuperCachePlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'wp-super-cache/wp-cache.php' ) ) {
			@include_once(WP_PLUGIN_DIR . '/wp-super-cache/wp-cache.php');
			if (function_exists('wp_cache_clean_cache')) {
	    		return true;
	    	}
	    }
		return false;
	}

	/*
	  * Public function, Will return the W3 Total cache Plugin is loaded or not
	  */

	public function checkW3TotalCachePlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ) {
			@include_once(WP_PLUGIN_DIR . '/w3-total-cache/w3-total-cache.php');
			if (function_exists('w3tc_flush_all')) {
	    		return true;
	    	}
	    }
		return false;
	}

	/*
	  * Public function, Will return the Comet cache plugin is loaded or not
	  */

	public function checkCometCachePlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'comet-cache/comet-cache.php' ) ) {
			// @include_once(WP_PLUGIN_DIR . '/comet-cache/comet-cache.php');
			//if (class_exists('ApiBase')) {
	    		return true;
	    	// }
	    }
		return false;
	}

	/*
	  * Public function, Will return the Comet cache plugin is loaded or not
	  */

	public function checkWPRocketPlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'wp-rocket/wp-rocket.php' ) ) {
			@include_once(WP_PLUGIN_DIR . '/wp-rocket/wp-rocket.php');
			if (function_exists('rocket_clean_domain') && function_exists('rocket_clean_minify') && function_exists('rocket_clean_cache_busting') && function_exists('create_rocket_uniqid')) {
	    		return true;
	    	}
	    }
		return false;
	}

	/*
	  * Public function, Will return the Comet cache plugin is loaded or not
	  */

	public function checkAutoptimizePlugin() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'autoptimize/autoptimize.php' ) ) {
			@include_once(WP_PLUGIN_DIR . 'autoptimize/autoptimize.php');
			if (class_exists('autoptimizeCache')) {
	    		return true;
	    	}
	    }
		return false;
	}

	/*
	  * This function will delete all cache files for WP Fastest Plugin
	  */

	public function deleteALLWPFCCache(){
		if($this->checkWPFCPlugin()) {
			$wpfc = new IWP_MMB_WPFC_CACHE();
			$wpfc->deleteMinifiedCache();
			$response = $wpfc->_getSystemMessage();
			if ($response[1] == 'error') {
				return array('error' => $response[0], 'error_code' => 'wpfc_plugin_delete_cache');
			}elseif($response[1] == 'success'){
				return array('success' => $response[0]);
			}else{
				return array('error' => 'Unable to perform WP Fastest cache', 'error_code' => 'wpfc_plugin_delete_cache');
			}
		} else {
			return array('error'=>"WP fastest cache not activated", 'error_code' => 'wpfc_plugin_is_not_activated');
		}
	}

	/*
	  * This function will delete all cache files for WP Super Cache Plugin
	  */

	public function deleteALLWPSuperCache(){
		if($this->checkWPSuperCachePlugin()) {
			global $file_prefix;
			$wp_super_cache = wp_cache_clean_cache($file_prefix, true);
			if ($wp_super_cache == false) {
				return array('error' => 'Unable to perform WP Super cache', 'error_code' => 'wp_super_cache_plugin_delete_cache');
			}
			return array('success' => 'All cache files have been deleted');
		} else {
			return array('error'=>"WP Super cache not activated", 'error_code' => 'wp_super_cache_plugin_is_not_activated');
		}
	}

	/*
	  * This function will delete all cache files for W3 Total Cache Plugin
	  */

	public function deleteALLW3TotalCache(){
		if($this->checkW3TotalCachePlugin()) {
			w3tc_flush_all();
			return array('success' => 'All cache files have been deleted');
		} else {
			return array('error'=>"W3 Total cache not activated", 'error_code' => 'wp_super_cache_plugin_is_not_activated');
		}
	}

	/*
	  * This function will delete all cache files for Comet Cache Plugin
	  */

	public function deleteALLCometCache(){
		if($this->checkCometCachePlugin()) {
			global $iwp_mmb_plugin_dir;
			require_once("$iwp_mmb_plugin_dir/addons/wp_optimize/comet-cache-class.php");
			return clearCometCacheIWP();
		} else {
			return array('error'=>"Comet cache not activated", 'error_code' => 'comet_cache_plugin_is_not_activated');
		}
	}

	/*
	  * This function will delete all cache files for WP Rocket Plugin
	  */

	public function deleteALLWPRocketCache(){
		if($this->checkWPRocketPlugin()) {
			$lang = '';
			// Remove all cache files.
			rocket_clean_domain( $lang );

			// Remove all minify cache files.
			rocket_clean_minify();

			// Remove cache busting files.
			rocket_clean_cache_busting();

			// Generate a new random key for minify cache file.
			$options = get_option( WP_ROCKET_SLUG );
			$options['minify_css_key'] = create_rocket_uniqid();
			$options['minify_js_key'] = create_rocket_uniqid();
			remove_all_filters( 'update_option_' . WP_ROCKET_SLUG );
			update_option( WP_ROCKET_SLUG, $options );
			return array('success' => 'All cache files have been deleted');
		} else {
			return array('error'=>"WP Rocket not activated", 'error_code' => 'comet_cache_plugin_is_not_activated');
		}
	}

	public function deleteAllautoptimizeCache(){
		if ($this->checkAutoptimizePlugin()) {
			$wp_auto_optimize = autoptimizeCache::clearall();
			if ($wp_auto_optimize == false) {
				return array('error' => 'Unable to perform Autoptimize cache', 'error_code' => 'auto_optimize_cache_plugin_delete_cache');
			}
			return array('success' => 'All cache files have been deleted');
		}else {
			return array('error'=>"Autoptimize not activated", 'error_code' => 'auto_optimize_plugin_is_not_activated');
		}
	}

}

if(class_exists('WpFastestCache')){
	class IWP_MMB_WPFC_CACHE extends WpFastestCache{
		
		public function deleteALLCache(){
			$this->deleteCacheToolbar();
		}

		public function deleteMinifiedCache(){
			$this->deleteCssAndJsCacheToolbar();
		}

		public function _getSystemMessage(){
			return $this->getSystemMessage();
		}
	}
}
