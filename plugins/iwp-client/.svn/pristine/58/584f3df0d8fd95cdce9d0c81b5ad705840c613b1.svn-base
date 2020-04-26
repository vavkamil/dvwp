<?php

if ( ! defined('ABSPATH') )
	die();

if (!class_exists('IWP_MMB_UploadModule')) require_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/backup.upload.php');

class IWP_MMB_UploadModule_googledrive extends IWP_MMB_UploadModule {

	private $service;
	private $client;
	private $ids_from_paths;

	public function get_supported_features() {
		// This options format is handled via only accessing options via $this->get_options()
		return array('multi_options');
	}

	public function get_default_options() {
		# parentid is deprecated since April 2014; it should not be in the default options (its presence is used to detect an upgraded-from-previous-SDK situation). For the same reason, 'folder' is also unset; which enables us to know whether new-style settings have ever been set.
		return array(
			'clientid' => '',
			'secret' => '',
			'token' => '',
		);
	}

	private function root_id() {
		if (empty($this->root_id)) $this->root_id = $this->service->about->get()->getRootFolderId();
		return $this->root_id;
	}

	public function id_from_path($path, $retry = true) {
		global $iwp_backup_core;

		try {
			while ('/' == substr($path, 0, 1)) { $path = substr($path, 1); }

			$cache_key = (empty($path)) ? '/' : $path;
			if (!empty($this->ids_from_paths) && isset($this->ids_from_paths[$cache_key])) return $this->ids_from_paths[$cache_key];

			$current_parent = $this->root_id();
			$current_path = '/';

			if (!empty($path)) {
				foreach (explode('/', $path) as $element) {
					$found = false;
					$sub_items = $this->get_subitems($current_parent, 'dir', $element);

					foreach ($sub_items as $item) {
						try {
							if ($item->getTitle() == $element) {
								$found = true;
								$current_path .= $element.'/';
								$current_parent = $item->getId();
								break;
							}
						} catch (Exception $e) {
							$iwp_backup_core->log("Google Drive id_from_path: exception: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
						}
					}

					if (!$found) {
						$ref = new Google_Service_Drive_ParentReference;
						$ref->setId($current_parent);
						$dir = new Google_Service_Drive_DriveFile();
						$dir->setMimeType('application/vnd.google-apps.folder');
						$dir->setParents(array($ref));
						$dir->setTitle($element);
						$iwp_backup_core->log("Google Drive: creating path: ".$current_path.$element);
						$dir = $this->service->files->insert(
							$dir,
							array('mimeType' => 'application/vnd.google-apps.folder')
						);
						$current_path .= $element.'/';
						$current_parent = $dir->getId();
					}
				}
			}

			if (empty($this->ids_from_paths)) $this->ids_from_paths = array();
			$this->ids_from_paths[$cache_key] = $current_parent;

			return $current_parent;

		} catch (Exception $e) {
			$msg = $e->getMessage();
			$iwp_backup_core->log("Google Drive id_from_path failure: exception (".get_class($e)."): ".$msg.' (line: '.$e->getLine().', file: '.$e->getFile().')');
			if (is_a($e, 'Google_Service_Exception') && false !== strpos($msg, 'Invalid json in service response') && function_exists('mb_strpos')) {
				// Aug 2015: saw a case where the gzip-encoding was not removed from the result
				// https://stackoverflow.com/questions/10975775/how-to-determine-if-a-string-was-compressed
				$is_gzip = false !== mb_strpos($msg , "\x1f" . "\x8b" . "\x08");
				if ($is_gzip) $iwp_backup_core->log("Error: Response appears to be gzip-encoded still; something is broken in the client HTTP stack, and you should define IWP_GOOGLEDRIVE_DISABLEGZIP as true in your wp-config.php to overcome this.");
			}
			# One retry
			return ($retry) ? $this->id_from_path($path, false) : false;
		}
	}

	private function get_parent_id($opts) {
		$filtered = apply_filters('IWP_googledrive_parent_id', false, $opts, $this->service, $this);
		if (!empty($filtered)) return $filtered;
		if (isset($opts['parentid'])) {
			if (empty($opts['parentid'])) {
				return $this->root_id();
			} else {
				$parent = (is_array($opts['parentid'])) ? $opts['parentid']['id'] : $opts['parentid'];
			}
		} else {
			$path = 'infinitewp';
			if (!empty($opts['gdrive_site_folder'])) {
				$site_name = iwp_getSiteName();
				$path = trailingslashit($path);
				$path.= $site_name;
			}
			$parent = $this->id_from_path($path);
		}
		return (empty($parent)) ? $this->root_id() : $parent;
	}

	public function listfiles($match = 'backup_') {

		$opts = $this->get_options();

		if (empty($opts['secret']) || empty($opts['clientid']) || empty($opts['clientid'])) return new WP_Error('no_settings', sprintf(__('No %s settings were found', 'InfiniteWP'), __('Google Drive','InfiniteWP')));

		$service = $this->bootstrap();
		if (is_wp_error($service) || false == $service) return $service;

		global $iwp_backup_core;

		try {
			$parent_id = $this->get_parent_id($opts);
			$sub_items = $this->get_subitems($parent_id, 'file');
		} catch (Exception $e) {
			return new WP_Error(__('Google Drive list files: failed to access parent folder', 'InfiniteWP').":  ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
		}

		$results = array();

		foreach ($sub_items as $item) {
			$title = "(unknown)";
			try {
				$title = $item->getTitle();
				if (0 === strpos($title, $match)) {
					$results[] = array('name' => $title, 'size' => $item->getFileSize());
				}
			} catch (Exception $e) {
				$iwp_backup_core->log("Google Drive delete: exception: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
				$ret = false;
				continue;
			}
		}

		return $results;
	}

	// Get a Google account access token using the refresh token
	private function access_token($refresh_token, $client_id, $client_secret) {

		global $iwp_backup_core;
		$iwp_backup_core->log("Google Drive: requesting access token: client_id=$client_id");

		$query_body = array(
			'refresh_token' => $refresh_token,
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'grant_type' => 'refresh_token'
		);

		$result = wp_remote_post('https://accounts.google.com/o/oauth2/token',
			array(
				'timeout' => '20',
				'method' => 'POST',
				'body' => $query_body
			)
		);

		if (is_wp_error($result)) {
			$iwp_backup_core->log("Google Drive error when requesting access token");
			foreach ($result->get_error_messages() as $msg) $iwp_backup_core->log("Error message: $msg");
			return false;
		} else {
			$json_values = json_decode(wp_remote_retrieve_body($result), true);
			if ( isset( $json_values['access_token'] ) ) {
				$iwp_backup_core->log("Google Drive: successfully obtained access token");
				return $json_values['access_token'];
			} else {
				$response = json_decode($result['body'],true);
				if (!empty($response['error']) && 'deleted_client' == $response['error']) {
					$iwp_backup_core->log(__('The client has been deleted from the Google Drive API console. Please create a new Google Drive project and reconnect with iwp_backup_core.','iwp_backup_core'), 'error');
				}
				$error_code = empty($response['error']) ? 'no error code' : $response['error'];
				$iwp_backup_core->log("Google Drive error ($error_code) when requesting access token: response does not contain access_token. Response: ".(is_string($result['body']) ? str_replace("\n", '', $result['body']) : json_encode($result['body'])));
				return false;
			}
		}
	}

	private function redirect_uri() {
		return  '';
	}

	// Acquire single-use authorization code from Google OAuth 2.0
	public function gdrive_auth_request() {
		$opts = $this->get_options();
		// First, revoke any existing token, since Google doesn't appear to like issuing new ones
		if (!empty($opts['token'])) $this->gdrive_auth_revoke();
		
		// We use 'force' here for the approval_prompt, not 'auto', as that deals better with messy situations where the user authenticated, then changed settings

		# We require access to all Google Drive files (not just ones created by this app - scope https://www.googleapis.com/auth/drive.file) - because we need to be able to re-scan storage for backups uploaded by other installs
		$params = array(
			'response_type' => 'code',
			'client_id' => $opts['clientid'],
			'redirect_uri' => $this->redirect_uri(),
			'scope' => 'https://www.googleapis.com/auth/drive',
			'state' => 'token',
			'access_type' => 'offline',
			'approval_prompt' => 'force'
		);
		if(headers_sent()) {
			global $iwp_backup_core;
			$iwp_backup_core->log(sprintf(__('The %s authentication could not go ahead, because something else on your site is breaking it. Try disabling your other plugins and switching to a default theme. (Specifically, you are looking for the component that sends output (most likely PHP warnings/errors) before the page begins. Turning off any debugging settings may also help).', ''), 'Google Drive'), 'error');
		} else {
			header('Location: https://accounts.google.com/o/oauth2/auth?'.http_build_query($params, null, '&'));
		}
	}


	// This function just does the formalities, and off-loads the main work to upload_file
	public function backup($backup_array) {

		global $iwp_backup_core, $IWP_backup;

		$service = $this->bootstrap();
		if (false == $service || is_wp_error($service)) return $service;

		$iwp_backup_dir = trailingslashit($iwp_backup_core->backups_dir_location());

		$opts = $this->get_options();

		try {
			$parent_id = $this->get_parent_id($opts);
		} catch (Exception $e) {
			$iwp_backup_core->log("Google Drive upload: failed to access parent folder: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			$iwp_backup_core->log(sprintf(__('Failed to upload to %s','InfiniteWP'),__('Google Drive','InfiniteWP')).': '.__('failed to access parent folder', 'InfiniteWP').' ('.$e->getMessage().')', 'error');
			return false;
		}

		foreach ($backup_array as $file) {

			$available_quota = -1;

			try {
				$about = $service->about->get();
				$quota_total = max($about->getQuotaBytesTotal(), 1);
				$quota_used = $about->getQuotaBytesUsed();
				$available_quota = $quota_total - $quota_used;
				$message = "Google Drive quota usage: used=".round($quota_used/1048576,1)." MB, total=".round($quota_total/1048576,1)." MB, available=".round($available_quota/1048576,1)." MB";
				$iwp_backup_core->log($message);
			} catch (Exception $e) {
				$iwp_backup_core->log("Google Drive quota usage: failed to obtain this information: ".$e->getMessage());
			}

			$file_path = $iwp_backup_dir.$file;
			$file_name = basename($file_path);
			$iwp_backup_core->log("$file_name: Attempting to upload to Google Drive (into folder id: $parent_id)");

			$filesize = filesize($file_path);
			$already_failed = false;
			if ($available_quota != -1) {
				if ($filesize > $available_quota) {
					$already_failed = true;
					$iwp_backup_core->log("File upload expected to fail: file ($file_name) size is $filesize b, whereas available quota is only $available_quota b");
					$iwp_backup_core->log(sprintf(__("Account full: your %s account has only %d bytes left, but the file to be uploaded is %d bytes",'InfiniteWP'),__('Google Drive', 'InfiniteWP'), $available_quota, $filesize), +'error');
				}
			}

			if (!$already_failed && $filesize > 10737418240) {
				# 10GB
				$iwp_backup_core->log("File upload expected to fail: file ($file_name) size is $filesize b (".round($filesize/1073741824, 4)." GB), whereas Google Drive's limit is 10GB (1073741824 bytes)");
				$iwp_backup_core->log(sprintf(__("Upload expected to fail: the %s limit for any single file is %s, whereas this file is %s GB (%d bytes)",'InfiniteWP'),__('Google Drive', 'InfiniteWP'), '10GB (1073741824)', round($filesize/1073741824, 4), $filesize), 'warning');
			} 

			try {
				$timer_start = microtime(true);
				if ($this->upload_file($file_path, $parent_id)) {
					$iwp_backup_core->log('OK: Archive ' . $file_name . ' uploaded to Google Drive in ' . ( round(microtime(true) - $timer_start, 2) ) . ' seconds');
					$iwp_backup_core->uploaded_file($file);
				} else {
					$iwp_backup_core->log("ERROR: $file_name: Failed to upload to Google Drive" );
					$iwp_backup_core->log("$file_name: ".sprintf(__('Failed to upload to %s','InfiniteWP'),__('Google Drive','InfiniteWP')), 'error');
				}
			} catch (Exception $e) {
				$msg = $e->getMessage();
				$iwp_backup_core->log("ERROR: Google Drive upload error: ".$msg.' (line: '.$e->getLine().', file: '.$e->getFile().')');
				if (false !== ($p = strpos($msg, 'The user has exceeded their Drive storage quota'))) {
					$iwp_backup_core->log("$file_name: ".sprintf(__('Failed to upload to %s','InfiniteWP'),__('Google Drive','InfiniteWP')).': '.substr($msg, $p), 'error');
				} else {
					$iwp_backup_core->log("$file_name: ".sprintf(__('Failed to upload to %s','InfiniteWP'),__('Google Drive','InfiniteWP')), 'error');
				}
				$this->client->setDefer(false);
			}
		}

		return null;
	}

	public function bootstrap($access_token = false) {

		global $iwp_backup_core;

		if (!empty($this->service) && is_object($this->service) && is_a($this->service, 'Google_Service_Drive')) return $this->service;

		$opts = $this->get_options();

		if (empty($access_token)) {
			if (empty($opts['token']) || empty($opts['clientid']) || empty($opts['secret'])) {
				$iwp_backup_core->log('Google Drive: this account is not authorised');
				$iwp_backup_core->log('Google Drive: '.__('Account is not authorized.', 'InfiniteWP'), 'error', 'googledrivenotauthed');
				return new WP_Error('not_authorized', __('Account is not authorized.', 'InfiniteWP'));
			}
		}
		
		$spl = spl_autoload_functions();
		if (is_array($spl)) {
			if (in_array('wpbgdc_autoloader', $spl)) spl_autoload_unregister('wpbgdc_autoloader');
			// http://www.wpdownloadmanager.com/download/google-drive-explorer/ - but also others, since this is the default function name used by the Google SDK
			if (in_array('google_api_php_client_autoload', $spl)) spl_autoload_unregister('google_api_php_client_autoload');
		}

		if ((!class_exists('Google_Config') || !class_exists('Google_Client') || !class_exists('Google_Service_Drive') || !class_exists('Google_Http_Request')) && !function_exists('google_api_php_client_autoload_iwp')) {
			require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google2/autoload.php'); 
		}

		if (!class_exists('IWP_MMB_Google_Http_MediaFileUpload')) {
			require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/google-extensions.php'); 
		}

		$config = new Google_Config();
		$config->setClassConfig('Google_IO_Abstract', 'request_timeout_seconds', 60);
		# In our testing, $service->about->get() fails if gzip is not disabled when using the stream wrapper
		if (!function_exists('curl_version') || !function_exists('curl_exec') || (defined('IWP_GOOGLEDRIVE_DISABLEGZIP') && IWP_GOOGLEDRIVE_DISABLEGZIP)) {
			$config->setClassConfig('Google_Http_Request', 'disable_gzip', true);
		}

		$client = new Google_Client($config);
		$client->setClientId($opts['clientid']);
		$client->setClientSecret($opts['secret']);
// 			$client->setUseObjects(true);

		if (empty($access_token)) {
			$access_token = $this->access_token($opts['token'], $opts['clientid'], $opts['secret']);
		}

		// Do we have an access token?
		if (empty($access_token) || is_wp_error($access_token)) {
			$iwp_backup_core->log('ERROR: Have not yet obtained an access token from Google (has the user authorised?)');
			$iwp_backup_core->log(__('Have not yet obtained an access token from Google - you need to authorise or re-authorise your connection to Google Drive.','InfiniteWP'), 'error');
			return $access_token;
		}

		$client->setAccessToken(json_encode(array(
			'access_token' => $access_token,
			'refresh_token' => $opts['token']
		)));

		$io = $client->getIo();
		$setopts = array();

		if (is_a($io, 'Google_IO_Curl')) {
			$setopts[CURLOPT_SSL_VERIFYPEER] = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_ssl_disableverify') ? false : true;
			if (!IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_ssl_useservercerts')) $setopts[CURLOPT_CAINFO] = $GLOBALS['iwp_mmb_plugin_dir'].'/lib/cacert.pem';
			// Raise the timeout from the default of 15
			$setopts[CURLOPT_TIMEOUT] = 60;
			$setopts[CURLOPT_CONNECTTIMEOUT] = 15;
			if (defined('IWP_IPV4_ONLY') && IWP_IPV4_ONLY) $setopts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
		} elseif (is_a($io, 'Google_IO_Stream')) {
			$setopts['timeout'] = 60;
			# We had to modify the SDK to support this
			# https://wiki.php.net/rfc/tls-peer-verification - before PHP 5.6, there is no default CA file
			if (!IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_ssl_useservercerts') || (version_compare(PHP_VERSION, '5.6.0', '<'))) $setopts['cafile'] = $GLOBALS['iwp_mmb_plugin_dir'].'/lib/cacert.pem';
			if (IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_ssl_disableverify')) $setopts['disable_verify_peer'] = true;
		}

		$io->setOptions($setopts);

		$service = new Google_Service_Drive($client);
		$this->client = $client;
		$this->service = $service;

		try {
			# Get the folder name, if not previously known (this is for the legacy situation where an id, not a name, was stored)
			if (!empty($opts['parentid']) && (!is_array($opts['parentid']) || empty($opts['parentid']['name']))) {
				$rootid = $this->root_id();
				$title = '';
				$parentid = is_array($opts['parentid']) ? $opts['parentid']['id'] : $opts['parentid'];
				while ((!empty($parentid) && $parentid != $rootid)) {
					$resource = $service->files->get($parentid);
					$title = ($title) ? $resource->getTitle().'/'.$title : $resource->getTitle();
					$parents = $resource->getParents();
					if (is_array($parents) && count($parents)>0) {
						$parent = array_shift($parents);
						$parentid = is_a($parent, 'Google_Service_Drive_ParentReference') ? $parent->getId() : false;
					} else {
						$parentid = false;
					}
				}
				if (!empty($title)) {
					$opts['parentid'] = array(
						'id' => (is_array($opts['parentid']) ? $opts['parentid']['id'] : $opts['parentid']),
						'name' => $title
					);
					$this->set_options($opts, true);
				}
			}
		} catch (Exception $e) {
			$iwp_backup_core->log("Google Drive: failed to obtain name of parent folder: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
		} 

		return $this->service;

	}

	// Returns array of Google_Service_Drive_DriveFile objects
	private function get_subitems($parent_id, $type = 'any', $match = 'backup_') {
		$q = '"'.$parent_id.'" in parents and trashed = false';
		if ('dir' == $type) {
			$q .= ' and mimeType = "application/vnd.google-apps.folder"';
		} elseif ('file' == $type) {
			$q .= ' and mimeType != "application/vnd.google-apps.folder"';
		}
		# We used to use 'contains' in both cases, but this exposed some bug that might be in the SDK or at the Google end - a result that matched for = was not returned with contains
		if (!empty($match)) {
			if ('backup_' == $match) {
				$q .= " and title contains '$match'";
			} else {
				$q .= " and title contains '$match'";
			}
		}

		$result = array();
		$pageToken = NULL;

		do {
			try {
				// Default for maxResults is 100
				$parameters = array('q' => $q, 'maxResults' => 200);
				if ($pageToken) {
					$parameters['pageToken'] = $pageToken;
				}
				$files = $this->service->files->listFiles($parameters);

				$result = array_merge($result, $files->getItems());
				$pageToken = $files->getNextPageToken();
			} catch (Exception $e) {
				global $iwp_backup_core;
				$iwp_backup_core->log("Google Drive: get_subitems: An error occurred (will not fetch further): " . $e->getMessage());
				$pageToken = NULL;
			}
		} while ($pageToken);
		
		return $result;
    }

	public function delete($files, $data=null, $sizeinfo = array()) {

		if (is_string($files)) $files=array($files);

		$service = $this->bootstrap();
		if (is_wp_error($service) || false == $service) return $service;

		$opts = $this->get_options();

		global $iwp_backup_core;

		try {
			$parent_id = $this->get_parent_id($opts);
			$iwp_getSiteName = iwp_getSiteName();
			$sub_items = $this->get_subitems($parent_id, 'file', $files[0]);
		} catch (Exception $e) {
			$iwp_backup_core->log("Google Drive delete: failed to access parent folder: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			return false;
		}

		$ret = true;

		foreach ($sub_items as $item) {
			$title = "(unknown)";
			try {
				$title = $item->getTitle();
				if (in_array($title, $files)) {
					$service->files->delete($item->getId());
					$iwp_backup_core->log("$title: Deletion successful");
					if(($key = array_search($title, $files)) !== false) {
						unset($files[$key]);
					}
				}
			} catch (Exception $e) {
				$iwp_backup_core->log("Google Drive delete: exception: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
				$ret = false;
				continue;
			}
		}

		foreach ($files as $file) {
			$iwp_backup_core->log("$file: Deletion failed: file was not found");
		}

		return $ret;

	}

	private function upload_file($file, $parent_id, $try_again = true) {

		global $iwp_backup_core;
		$opts = $this->get_options();
		$basename = basename($file);

		$service = $this->service;
		$client = $this->client;

		# See: https://github.com/google/google-api-php-client/blob/master/examples/fileupload.php (at time of writing, only shows how to upload in chunks, not how to resume)

		$client->setDefer(true);

		$local_size = filesize($file);

		$gdfile = new Google_Service_Drive_DriveFile();
		$gdfile->title  = $basename;

		$ref = new Google_Service_Drive_ParentReference;
		$ref->setId($parent_id);
		$gdfile->setParents(array($ref));

		$size = 0;
		$request = $service->files->insert($gdfile);

		$chunk_bytes = 1048576;

		$hash = md5($file);
		$transkey = 'gdresume_'.$hash;
		// This is unset upon completion, so if it is set then we are resuming
		$possible_location = $iwp_backup_core->jobdata_get($transkey);

		if (is_array($possible_location)) {

			$headers = array( 'content-range' => "bytes */".$local_size);

			$httpRequest = new Google_Http_Request(
				$possible_location[0],
				'PUT',
				$headers,
				''
			);
			$response = $this->client->getIo()->makeRequest($httpRequest);
			$can_resume = false;
			
			$response_http_code = $response->getResponseHttpCode();
			
			if ($response_http_code == 200 || $response_http_code == 201) {
				$client->setDefer(false);
				$iwp_backup_core->jobdata_delete($transkey);
				$iwp_backup_core->log("$basename: upload appears to be already complete (HTTP code: $response_http_code)");
				return true;
			}
			
			if (308 == $response_http_code) {
				$range = $response->getResponseHeader('range');
				if (!empty($range) && preg_match('/bytes=0-(\d+)$/', $range, $matches)) {
					$can_resume = true;
					$possible_location[1] = $matches[1]+1;
					$iwp_backup_core->log("$basename: upload already began; attempting to resume from byte ".$matches[1]);
				}
			}
			if (!$can_resume) {
				$iwp_backup_core->log("$basename: upload already began; attempt to resume did not succeed (HTTP code: ".$response_http_code.")");
			}
		}

		$media = new IWP_MMB_Google_Http_MediaFileUpload(
			$client,
			$request,
			(('.zip' == substr($basename, -4, 4)) ? 'application/zip' : 'application/octet-stream'),
			null,
			true,
			$chunk_bytes
		);
		$media->setFileSize($local_size);

		if (!empty($possible_location)) {
// 			$media->resumeUri = $possible_location[0];
// 			$media->progress = $possible_location[1];
			$media->IWP_setResumeUri($possible_location[0]);
			$media->IWP_setProgress($possible_location[1]);
			$size = $possible_location[1];
		}
		if ($size >= $local_size) return true;

		$status = false;
		if (false == ($handle = fopen($file, 'rb'))) {
			$iwp_backup_core->log("Google Drive: failed to open file: $basename");
			$iwp_backup_core->log("$basename: ".sprintf(__('%s Error: Failed to open local file', 'iwp_backup_core'),'Google Drive'), 'error');
			return false;
		}
		if ($size > 0 && 0 != fseek($handle, $size)) {
			$iwp_backup_core->log("Google Drive: failed to fseek file: $basename, $size");
			$iwp_backup_core->log("$basename (fseek): ".sprintf(__('%s Error: Failed to open local file', 'InfiniteWP'), 'Google Drive'), 'error');
			return false;
		}

		$pointer = $size;

		try {
			while (!$status && !feof($handle)) {
				$chunk = fread($handle, $chunk_bytes);
				# Error handling??
				$pointer += strlen($chunk);
				$status = $media->nextChunk($chunk);
				$iwp_backup_core->jobdata_set($transkey, array($media->IWP_getResumeUri(), $media->getProgress()));
				$iwp_backup_core->record_uploaded_chunk(round(100*$pointer/$local_size, 1), $media->getProgress(), $file);
			}
			
		} catch (Google_Service_Exception $e) {
			$iwp_backup_core->log("ERROR: Google Drive upload error (".get_class($e)."): ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			$client->setDefer(false);
			fclose($handle);
			$iwp_backup_core->jobdata_delete($transkey);
			if (false == $try_again) throw($e);
			# Reset this counter to prevent the something_useful_happened condition's possibility being sent into the far future and potentially missed
			if ($iwp_backup_core->current_resumption > 9) $iwp_backup_core->jobdata_set('uploaded_lastreset', $iwp_backup_core->current_resumption);
			return $this->upload_file($file, $parent_id, false);
		}

		// The final value of $status will be the data from the API for the object
		// that has been uploaded.
		$result = false;
		if ($status != false) $result = $status;

		fclose($handle);
		$client->setDefer(false);
		$iwp_backup_core->jobdata_delete($transkey);

		return true;

	}

	public function download($file) {

		global $iwp_backup_core;

		$service = $this->bootstrap();
		if (false == $service || is_wp_error($service)) return false;

		global $iwp_backup_core;
		$opts = $this->get_options();

		try {
			$parent_id = $this->get_parent_id($opts);
			#$gdparent = $service->files->get($parent_id);
			$site_name = iwp_getSiteName();
			$sub_items = $this->get_subitems($parent_id, 'file', $file);
		} catch (Exception $e) {
			$iwp_backup_core->log("Google Drive delete: failed to access parent folder: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			return false;
		}
		$found = false;	
		foreach ($sub_items as $item) {
			if ($found) continue;
			$title = "(unknown)";
			try {
				$title = $item->getTitle();
				if ($title == $file) {
					$gdfile = $item;
					$found = $item->getId();
					$size = $item->getFileSize();
				}
			} catch (Exception $e) {
				$iwp_backup_core->log("Google Drive download: exception: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			}
		}

		if (false === $found) {
			$iwp_backup_core->log("Google Drive download: failed: file not found");
			$iwp_backup_core->log("$file: ".sprintf(__("%s Error",'InfiniteWP'), 'Google Drive').": ".__('File not found', 'InfiniteWP'), 'error');
			return false;
		}

		$download_to = $iwp_backup_core->backups_dir_location().'/'.$file;

		$existing_size = (file_exists($download_to)) ? filesize($download_to) : 0;

		if ($existing_size >= $size) {
			$iwp_backup_core->log('Google Drive download: was already downloaded ('.filesize($download_to)."/$size bytes)");
			return true;
		}

		# Chunk in units of 2MB
		$chunk_size = 2097152;

		try {
			while ($existing_size < $size) {

				$end = min($existing_size + $chunk_size, $size);

				if ($existing_size > 0) {
					$put_flag = FILE_APPEND;
					$headers = array('Range' => 'bytes='.$existing_size.'-'.$end);
				} else {
					$put_flag = null;
					$headers = ($end < $size) ? array('Range' => 'bytes=0-'.$end) : array();
				}

				$pstart = round(100*$existing_size/$size,1);
				$pend = round(100*$end/$size,1);
				$iwp_backup_core->log("Requesting byte range: $existing_size - $end ($pstart - $pend %)");

				$request = $this->client->getAuth()->sign(new Google_Http_Request($gdfile->getDownloadUrl(), 'GET', $headers, null));
				$http_request = $this->client->getIo()->makeRequest($request);
				$http_response = $http_request->getResponseHttpCode();
				if (200 == $http_response || 206 == $http_response) {
					file_put_contents($download_to, $http_request->getResponseBody(), $put_flag);
				} else {
					$iwp_backup_core->log("Google Drive download: failed: unexpected HTTP response code: ".$http_response);
					$iwp_backup_core->log(sprintf(__("%s download: failed: file not found", 'iwp_backup_core'), 'Google Drive'), 'error');
					return false;
				}

				clearstatcache();
				$new_size = filesize($download_to);
				if ($new_size > $existing_size) {
					$existing_size = $new_size;
				} else {
					throw new Exception('Failed to obtain any new data at size: '.$existing_size);
				}
			}
		} catch (Exception $e) {
			$iwp_backup_core->log("Google Drive download: exception: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
		}

		return true;
	}

	public function config_print() {
		$opts = $this->get_options();

		if (isset($opts['parentid'])) {
			$parentid = (is_array($opts['parentid'])) ? $opts['parentid']['id'] : $opts['parentid'];
			$showparent = (is_array($opts['parentid']) && !empty($opts['parentid']['name'])) ? $opts['parentid']['name'] : $parentid;
		}
	}

	public function get_backup_file_size($file){
		global $iwp_backup_core;

		$service = $this->bootstrap();
		if (false == $service || is_wp_error($service)) return false;

		global $iwp_backup_core;
		$opts = $this->get_options();
		try {
			$parent_id = $this->get_parent_id($opts);
			#$gdparent = $service->files->get($parent_id);
			$site_name = iwp_getSiteName();
			$sub_items = $this->get_subitems($parent_id, 'file', $file);
		} catch (Exception $e) {
			$iwp_backup_core->log("Google Drive delete: failed to access parent folder: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			return false;
		}

		foreach ($sub_items as $item) {
			if ($found) continue;
			$title = "(unknown)";
			try {
				$title = $item->getTitle();
				if ($title == $file) {
					$gdfile = $item;
					$found = $item->getId();
					$size = $item->getFileSize();
					return $size;
				}
			} catch (Exception $e) {
				$iwp_backup_core->log("Google Drive download: exception: ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			}
		}
	}
}
