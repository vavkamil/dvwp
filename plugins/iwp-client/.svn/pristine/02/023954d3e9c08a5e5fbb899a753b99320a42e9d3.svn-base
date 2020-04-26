<?php 
if(basename($_SERVER['SCRIPT_FILENAME']) == "malware_scanner_sucuri.class.php"):
    exit;
endif;
class IWP_MMB_Sucuri extends IWP_MMB_Core {

public $is_sucuri_installed = false;

/**
 * initialize
 * @return void
 */
public function __construct() {
	@include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if (is_plugin_active('sucuri-scanner/sucuri.php')) {
        $this->is_sucuri_installed = true;
    }
}

/**
 * Get the plugin status
 */
public function getSucuriInstalled(){
    return $this->is_sucuri_installed;
}

public function getScannedCacheResult($assoc = 0){
	if (!$this->getSucuriInstalled() || !class_exists('SucuriScanCache')) {
		return false;
	}
	$cache = new SucuriScanCache('sitecheck');
	$finfo = $cache->getDatastoreInfo();
	$object = array();
	$object['info'] = array();
	$object['entries'] = array();

    if ($lines = SucuriScanFileInfo::fileLines($finfo['fpath'])) {
        foreach ($lines as $line) {
            if (strpos($line, "//\x20") === 0
                && strpos($line, '=') !== false
                && $line[strlen($line)-1] === ';'
            ) {
                $section = substr($line, 3, strlen($line)-4);
                list($header, $value) = explode('=', $section, 2);
                $object['info'][$header] = $value;
                continue;
            }

            /* skip content */
            if ($onlyInfo) {
                continue;
            }

            if (strpos($line, ':') !== false) {
                list($keyname, $value) = explode(':', $line, 2);
                $object['entries'][$keyname] = @json_decode($value, $assoc);
            }
        }
    }

	if (empty($object)) {
		return false;
	}
	return @$object;

}

public function scanAndCollectResult(){
	if (!$this->getSucuriInstalled() && !class_exists('SucuriScanSiteCheck')) {
		return false;
	}
	$data = SucuriScanSiteCheck::scanAndCollectData();
	return $data;
}

public function getMalwareResultDetails(){
    if (!$this->getSucuriInstalled()) {
        return array('error'=>"Sucuri plugin is not activated", 'error_code' => 'sucuri_plugin_is_not_activated');
    }
	$results = $this->getScannedCacheResult(1);
    if (empty($results)) {
        return array('error'=>"Sucuri scan is not initiated or completed", 'error_code' => 'sucuri_scan_is_not_completed');
    }

    return $results;

}

public function runAndSaveScanResult(){
    if (!$this->getSucuriInstalled() || !class_exists('SucuriScanSiteCheck')) {
        return array('error'=>"Sucuri plugin is not activated", 'error_code' => 'sucuri_plugin_is_not_activated');
    }
    $cache = new SucuriScanCache('sitecheck');
    $cache->delete('scan_results');
    $results = SucuriScanSiteCheck::runMalwareScan(1);

    /* check for error in the request's response. */
    if (is_string($results) || isset($results['SYSTEM']['ERROR'])) {
        if (isset($results['SYSTEM']['ERROR'])) {
            $results = implode("\x20", $results['SYSTEM']['ERROR']);
        }

        return array('error'=>'SiteCheck error: ' . $results, 'error_code' => 'sucuri_scan_error');
    }
    $cache->add('scan_results', $results);
    $details = $this->getScannedCacheResult();
    /* We can use this action if sucuri implement schedule remote scan 
    $info = $details['info'];
    $userid = $GLOBALS['iwp_mmb_activities_log']->iwp_mmb_get_current_user_id();
    $GLOBALS['iwp_mmb_activities_log']->iwp_mmb_save_iwp_activities('sucuri', 'scan', 'manual',$info, $userid);*/
    return $details;

}

public function changeAlertEmail($params = array()){
    if (!$this->getSucuriInstalled() || !class_exists('SucuriScanSiteCheck')) {
        return array('error'=>"Sucuri plugin is not activated", 'error_code' => 'sucuri_plugin_is_not_activated');
    }

    if (!$params['isDeleteOldMails']) {
        $notify_to = SucuriScanOption::getOption(':notify_to');
    }
    $emails = array();
    if (is_string($notify_to)) {
        $emails = explode(',', $notify_to);
    }
    if (in_array($params['email'], $emails)) {
        return array('error'=>"Email already present", 'error_code' => 'sucuri_email_already_present');
    }
    $emails[] = $params['email'];
    SucuriScanOption::updateOption(':notify_to', implode(',', $emails));
    return array('success' =>  'Successfully changed');
}


}

?>