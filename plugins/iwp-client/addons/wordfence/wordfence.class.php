<?php
if(basename($_SERVER['SCRIPT_FILENAME']) == "wordfence.class.php"):
    exit;
endif;
class IWP_WORDFENCE extends IWP_MMB_Core
{
    function __construct()
    {
        parent::__construct();
    }
	
	/*
	 * Load the Load Previousa Scan Results from WordFence
	 */
	 function load() {
	 	if($this->_checkWordFence()) {
	 		if(wfConfig::get('wf_scanRunning')){
	 			return array('scan'=>'yes');
	 		} else {
	 			return wordfence::ajax_loadIssues_callback();
	 		}
	 	} else {
	 		return array('warning'=>"Word Fence plugin is not activated");
	 	}
	 }
	 

	 /*
	 * Get Log count from WordFence
	 */

	 function getLogCounts($from = false, $to = false ){
        if (!$from && !$to) {
            $from = strtotime('yesterday');
            $to = time();
        }

        $return['wordfence_humans'] = self::getGivenLogHistory('humans', $from, $to);
        $return['wordfence_registered_users'] = self::getGivenLogHistory('registered_users', $from, $to);
        $return['wordfence_crawlers'] = self::getGivenLogHistory('crawlers', $from, $to);
        $return['wordfence_google_crawlers'] = self::getGivenLogHistory('google_crawlers', $from, $to);
        $return['wordfence_four_oh_four'] = self::getGivenLogHistory('four_oh_four', $from, $to);
        $return['wordfence_logins_logouts'] = self::getGivenLogHistory('logins_logouts', $from, $to);
        $return['wordfence_locked_out'] = self::getGivenLogHistory('locked_out', $from, $to);
        $return['wordfence_blocked'] = self::getGivenLogHistory('blocked', $from, $to);
        $return['wordfence_blocked_firewall'] = self::getGivenLogHistory('blocked_firewall', $from, $to);
        return $return;
    }

    /*
    * Get Humans history
    */
    function getGivenLogHistory($type, $from = false, $to = false){
    	global $wpdb;
    	$query = '';
    	switch ($type) {
    		case 'humans':
    			$query = "SELECT count('id')
				FROM ".$wpdb->base_prefix."wfHits
				WHERE jsRun = 1 AND ctime >= '$from' AND ctime <= '$to'";
    			break;

    		case 'registered_users':
    			$query = "SELECT count('id')
				FROM ".$wpdb->base_prefix."wfHits
				WHERE userID > 0 AND ctime >= '$from' AND ctime <= '$to'";
    			break;
    		case 'crawlers':
    			$query = "SELECT count('id')
				FROM ".$wpdb->base_prefix."wfHits
				WHERE jsRun = 0 AND ctime >= '$from' AND ctime <= '$to'";
    			break;

    		case 'google_crawlers':
    			$query = "SELECT count('id')
				FROM ".$wpdb->base_prefix."wfHits
				WHERE isGoogle = 1 AND ctime >= '$from' AND ctime <= '$to'";
    			break;
    		case 'four_oh_four':
    			$query = "SELECT count('id')
				FROM ".$wpdb->base_prefix."wfHits
				WHERE statusCode = '404' AND ctime >= '$from' AND ctime <= '$to'";
    			break;
    		case 'logins_logouts':
    			$query = "SELECT count('id')
				FROM ".$wpdb->base_prefix."wfLogins
				WHERE  ctime >= '$from' AND ctime <= '$to'";
    			break;
    		case 'locked_out':
    			$query = "SELECT count('IP')
				FROM ".$wpdb->base_prefix."wfLockedOut
				WHERE  blockedTime >= '$from' AND `blockedTime` <= '$to'";
    			break;
    		case 'blocked':
    		$query = "SELECT count('id')
				FROM ".$wpdb->base_prefix."wfHits
				WHERE action = 'blocked:wordfence' AND ctime >= '$from' AND ctime <= '$to'";
    			break;
    		case 'blocked_firewall':
    			$query = "SELECT count('id')
				FROM ".$wpdb->base_prefix."wfHits
				WHERE action = 'blocked:waf' AND ctime >= '$from' AND ctime <= '$to'";
    			break;
    		
    		default:
    			return 0;
  
    	}
    	$count = $wpdb->get_var( $query );
    	if (empty($count)) {
    		return 0;
    	}
    	return $count;

    }

	 /*
	 * Start the new scan on WordFence
	 */
	 function scan() {
	 	if($this->_checkWordFence()) {
	 		return wordfence::ajax_scan_callback();
	 	} else {
	 		return array('error'=>"Word Fence plugin is not activated", 'error_code' => 'wordfence_plugin_is_not_activated');
	 	}
	 }
	 
	 /*
	  *  Will return the wordfence is load or not
	  */
	 function _checkWordFence() {
	 	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	 	if ( is_plugin_active( 'wordfence/wordfence.php' ) ) {
	 		@include_once(WP_PLUGIN_DIR . '/wordfence/wordfence.php');
	 		if (class_exists('wordfence')) {
		    	return true;
			} else {
				return false;
			}
	 	} else {
	 		return false;
	 	}
	 	
		
		
	 }
    
}