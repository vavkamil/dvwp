<?php

// https://www.dropbox.com/developers/apply?cont=/developers/apps
if ( ! defined('ABSPATH') )
	die();

if (!class_exists('IWP_MMB_UploadModule')) require_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/backup.upload.php');

# Fix a potential problem for users who had the short-lived 1.12.35-1.12.38 free versions (see: https://wordpress.org/support/topic/1-12-37-dropbox-auth-broken/page/2/#post-8981457)
# Can be removed after a few months
$potential_options = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_dropbox');
if (is_array($potential_options) && isset($potential_options['version']) && isset($potential_options['settings']) && array() === $potential_options['settings']) {
	// Wipe it, which wil force its re-creation in proper format
	IWP_MMB_Backup_Options::delete_iwp_backup_option('IWP_dropbox');
}


class IWP_MMB_UploadModule_dropbox extends IWP_MMB_UploadModule {

	private $current_file_hash;
	private $current_file_size;
	private $dropbox_object;
	private $uploaded_offset;
	private $upload_tick; 

	public function chunked_callback($offset, $uploadid, $fullpath = false) {
		global $iwp_backup_core;

		// Update upload ID
		$iwp_backup_core->jobdata_set('IWP_dbid_'.$this->current_file_hash, $uploadid);
		$iwp_backup_core->jobdata_set('IWP_dbof_'.$this->current_file_hash, $offset);

		$time_now = microtime(true);
		
		$time_since_last_tick = $time_now - $this->upload_tick;
		$data_since_last_tick = $offset - $this->uploaded_offset;
		
		$this->upload_tick = $time_now;
		$this->uploaded_offset = $offset;
		
		$chunk_size = $iwp_backup_core->jobdata_get('dropbox_chunk_size', 1048576);
		// Don't go beyond 10MB, or change the chunk size after the last segment
		if ($chunk_size < 10485760 && $this->current_file_size > 0 && $offset < $this->current_file_size) {
			$job_run_time = $time_now - $iwp_backup_core->job_time_ms;
			if ($time_since_last_tick < 10) {
				$upload_rate = $data_since_last_tick / max($time_since_last_tick, 1);
				$upload_secs = min(floor($job_run_time), 10);
				if ($job_run_time < 15) $upload_secs = max(6, $job_run_time*0.6);
				$new_chunk = max(min($upload_secs * $upload_rate * 0.9, 10485760), 1048576);
				$new_chunk = $new_chunk - ($new_chunk % 524288);
				$chunk_size = (int)$new_chunk;
				$this->dropbox_object->setChunkSize($chunk_size);
				$iwp_backup_core->jobdata_set('dropbox_chunk_size', $chunk_size);
			}
		}
		
		if ($this->current_file_size > 0) {
			$percent = round(100*($offset/$this->current_file_size),1);
			$iwp_backup_core->record_uploaded_chunk($percent, "$uploadid, $offset, ".round($chunk_size/1024, 1)." KB", $fullpath);
		} else {
			$iwp_backup_core->log("Dropbox: Chunked Upload: $offset bytes uploaded");
			// This act is done by record_uploaded_chunk, and helps prevent overlapping runs
			touch($fullpath);
		}
	}

	public function get_supported_features() {
		// This options format is handled via only accessing options via $this->get_options()
		return array('multi_options');
	}

	public function get_default_options() {
		return array(
			'appkey' => '',
			'secret' => '',
			'folder' => '',
			'tk_access_token' => '',
		);
	}

	public function backup($backup_array) {

		global $iwp_backup_core;

		$opts = $this->get_options();
		
		if (empty($opts['tk_access_token'])) {
			$iwp_backup_core->log('You do not appear to be authenticated with Dropbox (1)');
			$iwp_backup_core->log(__('You do not appear to be authenticated with Dropbox','InfiniteWP'), 'error');
			return false;
		}
		
		// 28 June 2017
		$use_api_ver = 2;
		
		if (empty($opts['tk_request_token'])) {
			$iwp_backup_core->log("Dropbox: begin cloud upload (using API version $use_api_ver with OAuth v2 token)");
		} else {
			$iwp_backup_core->log("Dropbox: begin cloud upload (using API version $use_api_ver with OAuth v1 token)");
		}

		$chunk_size = $iwp_backup_core->jobdata_get('dropbox_chunk_size', 1048576);

		try {
			$dropbox = $this->bootstrap();
			if (false === $dropbox) throw new Exception(__('You do not appear to be authenticated with Dropbox', 'InfiniteWP'));
			$iwp_backup_core->log("Dropbox: access gained; setting chunk size to: ".round($chunk_size/1024, 1)." KB");
			$dropbox->setChunkSize($chunk_size);
		} catch (Exception $e) {
			$iwp_backup_core->log('Dropbox error when trying to gain access: '.$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			$iwp_backup_core->log(sprintf(__('Dropbox error: %s (see log file for more)','InfiniteWP'), $e->getMessage()), 'error');
			return false;
		}

		$iwp_backup_dir = $iwp_backup_core->backups_dir_location();
		

		foreach ($backup_array as $file) {

			$available_quota = -1;

			// If we experience any failures collecting account info, then carry on anyway
			try {

				/*
					Quota information is no longer provided with account information a new call to quotaInfo must be made to get this information.
				 */
				if (1 == $use_api_ver) {
					$quotaInfo = $dropbox->accountInfo();
				} else {
					$quotaInfo = $dropbox->quotaInfo();
				}

				if ($quotaInfo['code'] != "200") {
					$message = "Dropbox account/info did not return HTTP 200; returned: ". $quotaInfo['code'];
				} elseif (!isset($quotaInfo['body'])) {
					$message = "Dropbox account/info did not return the expected data";
				} else {
					$body = $quotaInfo['body'];
					if (isset($body->quota_info)) {
						$quota_info = $body->quota_info; 
						$total_quota = $quota_info->quota; 
			            $normal_quota = $quota_info->normal; 
			            $shared_quota = $quota_info->shared; 
			            $available_quota = $total_quota - ($normal_quota + $shared_quota); 
			            $message = "Dropbox quota usage: normal=".round($normal_quota/1048576,1)." MB, shared=".round($shared_quota/1048576,1)." MB, total=".round($total_quota/1048576,1)." MB, available=".round($available_quota/1048576,1)." MB";
					} else {
						$total_quota = max($body->allocation->allocated, 1);
						$used = $body->used;
						/* check here to see if the account is a team account and if so use the other used value
						This will give us their total usage including their individual account and team account */
						if (isset($body->allocation->used)) $used = $body->allocation->used;
						$available_quota = $total_quota - $used;
						$message = "Dropbox quota usage: used=".round($used/1048576,1)." MB, total=".round($total_quota/1048576,1)." MB, available=".round($available_quota/1048576,1)." MB";
					}
				}
				$iwp_backup_core->log($message);
			} catch (Exception $e) {
				$iwp_backup_core->log("Dropbox error: exception (".get_class($e).") occurred whilst getting account info: ".$e->getMessage());
			}

			$file_success = 1;

			$hash = md5($file);
			$this->current_file_hash = $hash;

			$filesize = filesize($iwp_backup_dir.'/'.$file);
			$this->current_file_size = $filesize;

			// Into KB
			$filesize = $filesize/1024;
			$microtime = microtime(true);

			if ($upload_id = $iwp_backup_core->jobdata_get('IWP_dbid_'.$hash)) {
				# Resume
				$offset =  $iwp_backup_core->jobdata_get('IWP_dbof_'.$hash);
				$iwp_backup_core->log("This is a resumption: $offset bytes had already been uploaded");
			} else {
				$offset = 0;
				$upload_id = null;
			}

			// We don't actually abort now - there's no harm in letting it try and then fail
			if ($available_quota != -1 && $available_quota < ($filesize-$offset)) {
				$iwp_backup_core->log("File upload expected to fail: file data remaining to upload ($file) size is ".($filesize-$offset)." b (overall file size; .".($filesize*1024)." b), whereas available quota is only $available_quota b");
			}


			$ufile = apply_filters('IWP_dropbox_modpath', $file, $this);

			$iwp_backup_core->log("Dropbox: Attempt to upload: $file to: $ufile");

			$this->upload_tick = microtime(true);
			$this->uploaded_offset = $offset;

			try {
				$response = $dropbox->chunkedUpload($iwp_backup_dir.'/'.$file, '', $ufile, true, $offset, $upload_id, array($this, 'chunked_callback'));
				if (empty($response['code']) || "200" != $response['code']) {
					$iwp_backup_core->log('Unexpected HTTP code returned from Dropbox: '.$response['code']." (".serialize($response).")");
					if ($response['code'] >= 400) {
						$iwp_backup_core->log('Dropbox '.sprintf(__('error: failed to upload file to %s (see log file for more)','iwp_backup_core'), $file), 'error');
					} else {
						$iwp_backup_core->log(sprintf(__('%s did not return the expected response - check your log file for more details', 'iwp_backup_core'), 'Dropbox'), 'warning');
					}
				}
			} catch (Exception $e) {
				$iwp_backup_core->log("Dropbox chunked upload exception (".get_class($e)."): ".$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
				if (preg_match("/Submitted input out of alignment: got \[(\d+)\] expected \[(\d+)\]/i", $e->getMessage(), $matches)) {
					// Try the indicated offset
					$we_tried = $matches[1];
					$dropbox_wanted = (int)$matches[2];
					$iwp_backup_core->log("Dropbox not yet aligned: tried=$we_tried, wanted=$dropbox_wanted; will attempt recovery");
					$this->uploaded_offset = $dropbox_wanted;
					try {
						$dropbox->chunkedUpload($iwp_backup_dir.'/'.$file, '', $ufile, true, $dropbox_wanted, $upload_id, array($this, 'chunked_callback'));
					} catch (Exception $e) {
						$msg = $e->getMessage();
						if (preg_match('/Upload with upload_id .* already completed/', $msg)) {
							$iwp_backup_core->log('Dropbox returned an error, but apparently indicating previous success: '.$msg);
						} else {
							$iwp_backup_core->log('Dropbox error: '.$msg.' (line: '.$e->getLine().', file: '.$e->getFile().')');
							$iwp_backup_core->log('Dropbox '.sprintf(__('error: failed to upload file to %s (see log file for more)','iwp_backup_core'), $ufile), 'error');
							$file_success = 0;
							if (strpos($msg, 'select/poll returned error') !== false && $this->upload_tick > 0 && time() - $this->upload_tick > 800) {
								$iwp_backup_core->reschedule(60);
								$iwp_backup_core->log("Select/poll returned after a long time: scheduling a resumption and terminating for now");
								$iwp_backup_core->record_still_alive();
								die;
							}
						}
					}
				} else {
					$msg = $e->getMessage();
					if (preg_match('/Upload with upload_id .* already completed/', $msg)) {
						$iwp_backup_core->log('Dropbox returned an error, but apparently indicating previous success: '.$msg);
					} else {
						$iwp_backup_core->log('Dropbox error: '.$msg);
						$iwp_backup_core->log('Dropbox '.sprintf(__('error: failed to upload file to %s (see log file for more)','iwp_backup_core'), $ufile), 'error');
						$file_success = 0;
						if (strpos($msg, 'select/poll returned error') !== false && $this->upload_tick > 0 && time() - $this->upload_tick > 800) {
							$iwp_backup_core->reschedule(60);
							$iwp_backup_core->log("Select/poll returned after a long time: scheduling a resumption and terminating for now");
							$iwp_backup_core->record_still_alive();
							die;
						}
					}
				}
			}
			if ($file_success) {
				$iwp_backup_core->uploaded_file($file);
				$microtime_elapsed = microtime(true)-$microtime;
				$speedps = $filesize/$microtime_elapsed;
				$speed = sprintf("%.2d",$filesize)." KB in ".sprintf("%.2d",$microtime_elapsed)."s (".sprintf("%.2d", $speedps)." KB/s)";
				$iwp_backup_core->log("Dropbox: File upload success (".$file."): $speed");
				$iwp_backup_core->jobdata_delete('IWP_duido_'.$hash);
				$iwp_backup_core->jobdata_delete('IWP_duidi_'.$hash);
			}

		}

		return null;

	}

	# $match: a substring to require (tested via strpos() !== false)
	public function listfiles($match = 'backup_') {

		$opts = $this->get_options();

		if (empty($opts['tk_access_token'])) return new WP_Error('no_settings', __('No settings were found', 'InfiniteWP').' (dropbox)');

		global $iwp_backup_core;
		try {
			$dropbox = $this->bootstrap();
		} catch (Exception $e) {
			$iwp_backup_core->log('Dropbox access error: '.$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			return new WP_Error('access_error', $e->getMessage());
		}

		$searchpath = '/'.untrailingslashit(apply_filters('IWP_dropbox_modpath', '', $this));

		try {
			/* Some users could have a large amount of backups, the max search is 1000 entries we should continue to search until there are no more entries to bring back. */
			$start = 0;
			$matches = array();

			while (true) {
				$search = $dropbox->search($match, $searchpath, 1000, $start);
				if (empty($search['code']) || 200 != $search['code']) return new WP_Error('response_error', sprintf(__('%s returned an unexpected HTTP response: %s', 'InfiniteWP'), 'Dropbox', $search['code']), $search['body']);

				if (empty($search['body'])) return array();

				if (isset($search['body']->matches) && is_array($search['body']->matches)) {
					$matches = array_merge($matches, $search['body']->matches);
				} elseif (is_array($search['body'])) {
					$matches = $search['body'];
				} else {
					break;
				}

				if (isset($search['body']->more) && true == $search['body']->more && isset($search['body']->start)) {
					$start = $search['body']->start;
				} else {
					break;
				}
			}

		} catch (Exception $e) {
			$iwp_backup_core->log('Dropbox error: '.$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			// The most likely cause of a search_error is specifying a non-existent path, which should just result in an empty result set.
// 			return new WP_Error('search_error', $e->getMessage());
			return array();
		}

		$results = array();

		foreach ($matches as $item) {
			
			$item = $item->metadata;
			if (!is_object($item)) continue;

			if ((!isset($item->size) || $item->size > 0)  && $item->{'.tag'} != 'folder' && !empty($item->path_display) && 0 === strpos($item->path_display, $searchpath)) {

				$path = substr($item->path_display, strlen($searchpath));
				if ('/' == substr($path, 0, 1)) $path=substr($path, 1);

				# Ones in subfolders are not wanted
				if (false !== strpos($path, '/')) continue;

				$result = array('name' => $path);
				if (!empty($item->size)) $result['size'] = $item->size;

				$results[] = $result;
			}
		}

		return $results;
	}

	public function defaults() {
		return apply_filters('IWP_dropbox_defaults', array('Z3Q3ZmkwbnplNHA0Zzlx', 'bTY0bm9iNmY4eWhjODRt'));
	}

	public function delete($files, $data = null, $sizeinfo = array()) {

		global $iwp_backup_core;
		if (is_string($files)) $files=array($files);

		$opts = $this->get_options();

		if (empty($opts['tk_access_token'])) {
			$iwp_backup_core->log('You do not appear to be authenticated with Dropbox (3)');
			$iwp_backup_core->log(sprintf(__('You do not appear to be authenticated with %s (whilst deleting)', 'InfiniteWP'), 'Dropbox'), 'warning');
			return false;
		}

		try {
			$dropbox = $this->bootstrap();
		} catch (Exception $e) {
			$iwp_backup_core->log('Dropbox error: '.$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			$iwp_backup_core->log(sprintf(__('Failed to access %s when deleting (see log file for more)', 'InfiniteWP'), 'Dropbox'), 'warning');
			return false;
		}
		if (false === $dropbox) return false;

		foreach ($files as $file) {
			$ufile = apply_filters('IWP_dropbox_modpath', $file, $this);
			$iwp_backup_core->log("Dropbox: request deletion: $ufile");

			try {
				$dropbox->delete($ufile);
				$file_success = 1;
			} catch (Exception $e) {
				$iwp_backup_core->log('Dropbox error: '.$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			}

			if (isset($file_success)) {
				$iwp_backup_core->log('Dropbox: delete succeeded');
			} else {
				return false;
			}
		}

	}

	public function download($file) {

		global $iwp_backup_core;

		$opts = $this->get_options();

		if (empty($opts['tk_access_token'])) {
			$iwp_backup_core->log('You do not appear to be authenticated with Dropbox (4)');
			$iwp_backup_core->log(sprintf(__('You do not appear to be authenticated with %s','InfiniteWP'), 'Dropbox'), 'error');
			return false;
		}

		try {
			$dropbox = $this->bootstrap();
		} catch (Exception $e) {
			$iwp_backup_core->log('Dropbox error: '.$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')');
			$iwp_backup_core->log('Dropbox error: '.$e->getMessage().' (line: '.$e->getLine().', file: '.$e->getFile().')', 'error');
			return false;
		}
		if (false === $dropbox) return false;

		$iwp_backup_dir = $iwp_backup_core->backups_dir_location();
		$microtime = microtime(true);

		$try_the_other_one = false;

		$ufile = apply_filters('IWP_dropbox_modpath', $file, $this);

		try {
			$get = $dropbox->getFile($ufile, $iwp_backup_dir.'/'.$file, null, true);
		} catch (Exception $e) {
			// TODO: Remove this October 2013 (we stored in the wrong place for a while...)
			$try_the_other_one = true;
			$possible_error = $e->getMessage();
			$iwp_backup_core->log('Dropbox error: '.$e);
			$get = false;
		}

		// TODO: Remove this October 2013 (we stored files in the wrong place for a while...)
		if ($try_the_other_one) {
			$dropbox_folder = trailingslashit($opts['folder']);
			try {
				$get = $dropbox->getFile($dropbox_folder.'/'.$file, $iwp_backup_dir.'/'.$file, null, true);
				if (isset($get['response']['body'])) {
					$iwp_backup_core->log("Dropbox: downloaded ".round(strlen($get['response']['body'])/1024,1).' KB');
				}
			}  catch (Exception $e) {
				$iwp_backup_core->log($possible_error, 'error');
				$iwp_backup_core->log($e->getMessage(), 'error');
				$get = false;
			}
		}

		return $get;

	}

public function config_print() {
	
		$opts = $this->get_options();
		$ownername = empty($opts['ownername']) ? '' : $opts['ownername'];
		if (!empty($opts['appkey'])) {
			$appkey = empty($opts['appkey']) ? '' : $opts['appkey'];
			$secret = empty($opts['secret']) ? '' : $opts['secret'];
		}
	}

	public function auth_token() {
		$this->bootstrap();
		$opts = $this->get_options();
		if (!empty($opts['tk_access_token'])) {
			add_action('all_admin_notices', array($this, 'show_authed_admin_warning') );
		}
	}

	// Acquire single-use authorization code
	public function auth_request() {
		$this->bootstrap();
	}

	// This basically reproduces the relevant bits of bootstrap.php from the SDK
	public function bootstrap($deauthenticate = false) {
		if (!empty($this->dropbox_object) && !is_wp_error($this->dropbox_object)) return $this->dropbox_object;

		/*
			Use Old Dropbox API constant is used to force bootstrap to use the old API this is for users having problems. By default we will use the new Dropbox API v2 as the old version will be deprecated as of June 2017
		 */
		$dropbox_api =  'Dropbox2';

		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/'.$dropbox_api.'/API.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/'.$dropbox_api.'/Exception.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/'.$dropbox_api.'/OAuth/Consumer/ConsumerAbstract.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/'.$dropbox_api.'/OAuth/Storage/StorageInterface.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/'.$dropbox_api.'/OAuth/Storage/Encrypter.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/'.$dropbox_api.'/OAuth/Storage/WordPress.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/'.$dropbox_api.'/OAuth/Consumer/Curl.php');

		$opts = $this->get_options();

		$key = empty($opts['secret']) ? '' : $opts['secret'];
		$sec = empty($opts['appkey']) ? '' : $opts['appkey'];
		
		$oauth2_id = base64_decode('aXA3NGR2Zm1sOHFteTA5');

	
		// Instantiate the Encrypter and storage objects
		$encrypter = new Dropbox_Encrypter('ThisOneDoesNotMatterBeyondLength');

		// Instantiate the storage
		$storage = new Dropbox_WordPress($encrypter, "tk_", 'IWP_dropbox', $this);

//		WordPress consumer does not yet work
//		$OAuth = new Dropbox_ConsumerWordPress($sec, $key, $storage, $callback);

		// Get the DropBox API access details
		list($d2, $d1) = $this->defaults();
		if (empty($sec)) { $sec = base64_decode($d1); }; if (empty($key)) { $key = base64_decode($d2); }
		$root = 'sandbox';
		if ('dropbox:' == substr($sec, 0, 8)) {
			$sec = substr($sec, 8);
			$root = 'dropbox';
		}
		
		try {
			$OAuth = new Dropbox_Curl($sec, $oauth2_id, $key, $storage, null, null, $deauthenticate);
		} catch (Exception $e) {
			global $iwp_backup_core;
			$iwp_backup_core->log("Dropbox Curl error: ".$e->getMessage());
			$iwp_backup_core->log(sprintf(__("%s error: %s", 'iwp_backup_core'), "Dropbox/Curl", $e->getMessage().' ('.get_class($e).') (line: '.$e->getLine().', file: '.$e->getFile()).')', 'error');
			return false;
		}

		if ($deauthenticate) return true;
		
		$OAuth->setToken($opts['tk_access_token']);
		$this->dropbox_object = new IWP_MMB_Dropbox_API($OAuth, $root);
		return $this->dropbox_object;
	}

}
