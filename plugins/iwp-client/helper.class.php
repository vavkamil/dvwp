<?php
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/
/*************************************************************
 * 
 * helper.class.php
 * 
 * Utility functions
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if(basename($_SERVER['SCRIPT_FILENAME']) == "helper.class.php"):
    exit;
endif;
if(!defined('MMB_WORKER_VERSION'))
	define('MMB_WORKER_VERSION', '0');

class IWP_MMB_Helper
{
    /**
     * A helper function to log data
     * 
     * @param mixed $mixed
     */
    function _log($mixed)
    {
        if (is_array($mixed)) {
            $mixed = print_r($mixed, 1);
        } else if (is_object($mixed)) {
            ob_start();
            var_dump($mixed);
            $mixed = ob_get_clean();
        }
        
        //$handle = fopen(dirname(__FILE__) . '/log', 'a');
        //fwrite($handle, $mixed . PHP_EOL);
        //fclose($handle);
    }
	
    function _escape(&$array)
    {
        global $wpdb;
        
        if (!is_array($array)) {
            return ($wpdb->escape($array));
        } else {
            foreach ((array) $array as $k => $v) {
                if (is_array($v)) {
                    $this->_escape($array[$k]);
                } else if (is_object($v)) {
                    //skip
                } else {
                    $array[$k] = $wpdb->escape($v);
                }
            }
        }
    }
    
    /**
     * Initializes the file system
     * 
     */
    function init_filesystem()
    {
        global $wp_filesystem;
        
        if (!$wp_filesystem || !is_object($wp_filesystem)) {
            WP_Filesystem();
        }
        
        if (!is_object($wp_filesystem))
            return FALSE;
        
        return TRUE;
    }
    
	/**
	 *
	 * Check if function exists or not on `suhosin` black list
	 *
	 */
	
	function iwp_mmb_get_user_info( $user_info = false, $info = 'login' ){
				
		if($user_info === false)
			return false;
			
		if( strlen( trim( $user_info ) ) == 0)
			return false;
			
			
		global $wp_version;
		if (version_compare($wp_version, '3.2.2', '<=')){
			return get_userdatabylogin( $user_info );
		} else {
			return iwp_mmb_get_user_by( $info, $user_info );
		}
	}
	
	/**
	 *
	 * Call action item filters
	 *
	 */
	
	function iwp_mmb_parse_action_params( $key = '', $params = null, $call_object = null ){
		
		global $_iwp_mmb_item_filter;
		$call_object = $call_object !== null ? $call_object : $this;
		$return = array();
		
		if(isset($_iwp_mmb_item_filter[$key]) && !empty($_iwp_mmb_item_filter[$key])){
			if( isset($params['item_filter']) && !empty($params['item_filter'])){
				foreach($params['item_filter'] as $_items){
					if(!empty($_items)){
						foreach($_items as $_item){
							if(in_array($_item[0], $_iwp_mmb_item_filter[$key])){
								$_item[1] = isset($_item[1]) ? $_item[1] : array();
								$return = call_user_func(array( &$call_object, 'get_'.$_item[0]), $return, $_item[1]);
							}
						}
					}
				}
			}
		}
		
		return $return;
	}
	
	/**
	 *
	 * Check if function exists or not on `suhosin` black list
	 *
	 */
	
	function iwp_mmb_function_exists($function_callback){
		
		if(!function_exists($function_callback))
			return false;
			
		$disabled = explode(', ', @ini_get('disable_functions'));
		if (in_array($function_callback, $disabled))
			return false;
			
		if (extension_loaded('suhosin')) {
			$suhosin = @ini_get("suhosin.executor.func.blacklist");
			if (empty($suhosin) == false) {
				$suhosin = explode(',', $suhosin);
				$blacklist = array_map('trim', $suhosin);
				$blacklist = array_map('strtolower', $blacklist);
				if(in_array($function_callback, $blacklist))
					return false;
			}
		}
		return true;
	}
	
    /**
     *  Gets transient based on WP version
     *
     * @global string $wp_version
     * @param string $option_name
     * @return mixed
     */
	 
	function iwp_mmb_set_transient($option_name = false, $data = false){
		
		if (!$option_name || !$data) {
            return false;
        }
		if($this->iwp_mmb_multisite)
			return $this->iwp_mmb_set_sitemeta_transient($option_name, $data);
			
		global $wp_version;
        
        if (version_compare($wp_version, '2.7.9', '<=')) {
            update_option($option_name, $data);
        } else if (version_compare($wp_version, '2.9.9', '<=')) {
            update_option('_transient_' . $option_name, $data);
        } else {
			update_option('_site_transient_' . $option_name, $data);
        }
		
	}
    function iwp_mmb_get_transient($option_name)
    {
        global $wp_version;

        if (trim($option_name) == '') {
            return false;
        }

        if (version_compare($wp_version, '3.4', '>')) {
            return get_site_transient($option_name);
        }

        if (!empty($this->iwp_mmb_multisite)) {
            return $this->iwp_mmb_get_sitemeta_transient($option_name);
        }

        $transient = get_option('_site_transient_'.$option_name);

        return apply_filters("site_transient_".$option_name, $transient);
    }
    
    function iwp_mmb_delete_transient($option_name)
    {
        if (trim($option_name) == '') {
            return FALSE;
        }
        
        global $wp_version;
        
		if (version_compare($wp_version, '2.7.9', '<=')) {
            delete_option($option_name);
        } else if (version_compare($wp_version, '2.9.9', '<=')) {
            delete_option('_transient_' . $option_name);
        } else {
            delete_option('_site_transient_' . $option_name);
        }
    }
    
	function iwp_mmb_get_sitemeta_transient($option_name){
		global $wpdb;
		$option_name = '_site_transient_'. $option_name;
		
		$result = $wpdb->get_var( $wpdb->prepare("SELECT `meta_value` FROM `{$wpdb->sitemeta}` WHERE meta_key = %s AND `site_id` = %s", $option_name, $this->iwp_mmb_multisite)); 
		$result = maybe_unserialize($result);
		return $result;
	}
	
	function iwp_mmb_set_sitemeta_transient($option_name, $option_value){
		global $wpdb;
		$option_name = '_site_transient_'. $option_name;
		
		if($this->iwp_mmb_get_sitemeta_transient($option_name)){
			$result = $wpdb->update( $wpdb->sitemeta,
				array(
					'meta_value' => maybe_serialize($option_value)
				),
				array(
					'meta_key' => $option_name, 
					'site_id' => $this->iwp_mmb_multisite
				)
			); 
		}else {
			$result = $wpdb->insert( $wpdb->sitemeta,
				array(
					'meta_key' => $option_name,
					'meta_value' => maybe_serialize($option_value),
					'site_id' => $this->iwp_mmb_multisite
				)
			); 
		}
		return $result;
	}
	
    function delete_temp_dir($directory)
    {
        if (substr($directory, -1) == "/") {
            $directory = substr($directory, 0, -1);
        }
        if (!file_exists($directory) || !is_dir($directory)) {
            return false;
        } elseif (!is_readable($directory)) {
            return false;
        } else {
            $directoryHandle = opendir($directory);
            
            while ($contents = readdir($directoryHandle)) {
                if ($contents != '.' && $contents != '..') {
                    $path = $directory . "/" . $contents;
                    
                    if (is_dir($path)) {
                        $this->delete_temp_dir($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            closedir($directoryHandle);
            rmdir($directory);
            return true;
        }
    }
    
    function set_client_message_id($message_id = false)
    {
        if ($message_id) {
             if (is_multisite()) {
                global $wpdb;
                $blogIDs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogIDs as $blogID) {
                    update_blog_option($blogID, 'iwp_client_action_message_id', $message_id);
                }
                return true;
            } else {
               update_option('iwp_client_action_message_id', $message_id);
               return $message_id;
            }
            
        }
        return false;
    }
    
    function get_client_message_id()
    {
        return (int) get_option('iwp_client_action_message_id');
    }
    
    function set_admin_panel_public_key($public_key = false)
    {

         if (is_multisite()) {
            global $wpdb;
            $blogIDs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blogIDs as $blogID) {
                update_blog_option($blogID, 'iwp_client_public_key', base64_encode($public_key));
            }
            return true;
        } else {
            if ($public_key && !get_option('iwp_client_public_key')) {
                add_option('iwp_client_public_key', base64_encode($public_key));
                return true;
            }
        }

        return false;
    }
    
    function get_admin_panel_public_key()
    {
        if (!get_option('iwp_client_public_key'))
            return false;
        return base64_decode(get_option('iwp_client_public_key'));
    }
    
    
    function get_random_signature()
    {
        if (!get_option('iwp_client_nossl_key'))
            return false;
        return base64_decode(get_option('iwp_client_nossl_key'));
    }
    
    function set_random_signature($random_key = false)
    {
        if ($random_key && !get_option('iwp_client_nossl_key')) {
            add_option('iwp_client_nossl_key', base64_encode($random_key));
            return true;
        }
        return false;
    }
    
    
    function authenticate_message($data = false, $signature = false, $message_id = false)
    {
        if (!$data && !$signature) {
            return array(
                'error' => 'Authentication failed.', 'error_code' => 'authentication_failed'
            );
        }
        
        $current_message = $this->get_client_message_id();
        
        if(isset($_GET['auto_login'])){//temp fix for stopping reuse of open admin url
        	if ((int) $current_message >= (int) $message_id)
				return array(
					'error' => 'Invalid message recieved.', 'error_code' => 'invalid_message_received'
				);
		}
		
        $pl_key = $this->get_admin_panel_public_key();
        if (!$pl_key) {
            return array(
                'error' => 'Authentication failed. Deactivate and activate the InfiniteWP Client plugin on this site, then remove the website from your InfiniteWP Admin Panel and add it again.', 'error_code' => 'authentication_failed_reactive_and_readd_the_site'
            );
        }
        
        if (checkOpenSSL() && !$this->get_random_signature()) {
            $verify = openssl_verify($data, $signature, $pl_key);
            if ($verify == 1) {
                $message_id = $this->set_client_message_id($message_id);
                return true;
            } else if ($verify == 0) {
                return array(
                    'error' => 'Invalid message signature. Deactivate and activate the InfiniteWP Client plugin on this site, then remove the website from your InfiniteWP Admin Panel and add it again.', 'error_code' => 'invalid_message_signature_openssl'
                );
            } else {
                return array(
                    'error' => 'Command not successful! Please try again.', 'error_code' => 'command_not_successful'
                );
            }
        } else if ($this->get_random_signature()) {
			
            if (md5($data . $this->get_random_signature()) === $signature) {
                $message_id = $this->set_client_message_id($message_id);
				return true;
            }
            return array(
                'error' => 'Invalid message signature. Deactivate and activate the InfiniteWP Client plugin on this site, then remove the website from your InfiniteWP Admin Panel and add it again.', 'error_code' => 'invalid_message_signature_random_signature'
            );
        }
        // no rand key - deleted in get_stat maybe
        else
            return array(
                'error' => 'Invalid message signature. Deactivate and activate the InfiniteWP Client plugin on this site, then remove the website from your InfiniteWP Admin Panel and add it again.', 'error_code' => 'invalid_message_signature'
            );
    }
    
	function _secure_data($data = false){
		if($data == false)
			return false;
			
		$pl_key = $this->get_admin_panel_public_key();
        if (!$pl_key)
            return false;
		
		$secure = '';
		if( function_exists('openssl_public_decrypt') && !$this->get_random_signature()){
			if(is_array($data) && !empty($data)){
				foreach($data as $input){
					openssl_public_decrypt($input, $decrypted, $pl_key);
					$secure .= $decrypted;
				}
			} else {
				openssl_public_decrypt($input, $decrypted, $pl_key);
				$secure = $decrypted;
			}
			return $secure;
		}
		return false;
		
	}
	
    function check_if_user_exists($username = false)
    {
        global $wpdb;
        if ($username) {
			if( !function_exists('username_exists') )
				include_once(ABSPATH . WPINC . '/registration.php');
			// if( !function_exists('get_user_by') )	
   //              include_once(ABSPATH . 'wp-includes/pluggable.php');
            
            // if (username_exists($username) == null) {
            //     return false;
            // }
			
            $user = (array) $this->iwp_mmb_get_user_info( $username );
			if ((isset($user[$wpdb->base_prefix . 'user_level']) && $user[$wpdb->base_prefix . 'user_level'] == 10) || isset($user[$wpdb->base_prefix . 'capabilities']['administrator']) || 
				(isset($user['caps']['administrator']) && $user['caps']['administrator'] == 1)){
                return true;
            }
            return false;
        }
        return false;
    }
    
    function refresh_updates()
    {
        if (rand(1, 3) == '2') {
            require_once(ABSPATH . WPINC . '/update.php');
            wp_update_plugins();
            wp_update_themes();
            wp_version_check();
        }
    }
    
    function remove_http($url = '')
    {
        if ($url == 'http://' OR $url == 'https://') {
            return $url;
        }
        return preg_replace('/^(http|https)\:\/\/(www.)?/i', '', $url);
        
    }
    
    function iwp_mmb_get_error($error_object)
    {
        if (!is_wp_error($error_object)) {
            return $error_object != '' ? $error_object : '';
        } else {
            $errors = array();
			if(!empty($error_object->error_data))  {
				foreach ($error_object->error_data as $error_key => $error_string) {
					$errors[] = str_replace('_', ' ', ucfirst($error_key)) . ': ' . $error_string;
				} 
			} elseif (!empty($error_object->errors)){
				foreach ($error_object->errors as $error_key => $err) {
					$errors[] = 'Error: '.str_replace('_', ' ', strtolower($error_key));
				} 
			}
            return implode('<br />', $errors);
        }
    }
    
	function is_server_writable(){
		if((!defined('FTP_HOST') || !defined('FTP_USER') || !defined('FTP_PASS')) && (get_filesystem_method(array(), false) != 'direct'))
			return false;
		else
			return true;
	}


    function define_ftp_constants($params){

        if (!$this->is_server_writable()) {
            $ftp_details = unserialize($params['account_info']);
            if (empty($ftp_details)) {
                return true;
            }
            if (!defined('FS_METHOD')) {
                define( 'FS_METHOD', 'ftpext' );
            }
            if (!defined('FTP_BASE')) {
                define( 'FTP_BASE', $ftp_details['remoteFolder'] );
            }
            if (!defined('FTP_USER')) {
                define( 'FTP_USER', $ftp_details['hostUserName'] );
            }
            if (!defined('FTP_PASS')) {
                define( 'FTP_PASS', $ftp_details['hostPassword'] );
            }
            if (!defined('FTP_HOST')) {
                define( 'FTP_HOST', $ftp_details['hostName'] );
            }
            if (!defined('FTP_SSL')) {
                define( 'FTP_SSL', $ftp_details['hostSSL'] );
            }
        }
        return true;
    }
	
	function iwp_mmb_download_url($url, $file_name)
	{
		if (function_exists('fopen') && function_exists('ini_get') && ini_get('allow_url_fopen') == true && ($destination = @fopen($file_name, 'wb')) && ($source = @fopen($url, "r")) ) {
		
		
		while ($a = @fread($source, 1024* 1024)) {
		@fwrite($destination, $a);
		}
		
		fclose($source);
		fclose($destination);
		} else 
		if (!fsockopen_download($url, $file_name))
			die('Error downloading file ' . $url);
		return $file_name;
	}
}
?>