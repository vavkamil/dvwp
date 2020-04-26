<?php

if ( ! defined('ABSPATH') )
	die();

if (!class_exists('IWP_MMB_PclZip')) {
	require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup/backup.zip.class.php");
	require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup/functions.php");

}

// This file contains functions that are only needed/loaded when a backup is running (reduces memory usage on other pages)

class IWP_MMB_Backup {

	public $index = 0;

	private $zipfiles_added;
	private $zipfiles_added_thisrun = 0;
	public $zipfiles_dirbatched;
	public $zipfiles_batched;
	public $zipfiles_skipped_notaltered;
	private $zip_split_every = 419430400; # 400MB
	private $zip_last_ratio = 1;
	private $whichone;
	private $zip_basename = '';
	private $backup_basename = '';
	private $zipfiles_lastwritetime;
	// 0 = unknown; false = failed
	public $binzip = 0;

	private $dbhandle;
	private $dbhandle_isgz;

	# Array of entities => times
	private $altered_since = -1;
	# Time for the current entity
	private $makezip_if_altered_since = -1;

	private $excluded_extensions = false;

	private $use_zip_object = 'IWP_MMB_ZipArchive';
	public $debug = false;

	public $iwp_backup_dir;
	private $blog_name;
	private $wpdb_obj;
	private $job_file_entities = array();

	private $first_run = 0;

	// Record of zip files created
	private $backup_files_array = array();

	// Used for reporting
	private $remotestorage_extrainfo = array();

	// Used when deciding to use the 'store' or 'deflate' zip storage method
	private $extensions_to_not_compress = array();

	// Append to this any skipped tables
	private $skipped_tables;

	public function __construct($backup_files, $altered_since = -1) {

		global $iwp_backup_core;

		// Get the blog name and rip out known-problematic characters. Remember that we may need to be able to upload this to any FTP server or cloud storage, where filename support may be unknown
		$blog_name = str_replace('__', '_', preg_replace('/[^A-Za-z0-9_]/','', str_replace(' ','_', substr(get_bloginfo(), 0, 32))));
		if (!$blog_name || preg_match('#^_+$#', $blog_name)) {
			// Try again...
			$parsed_url = parse_url(home_url(), PHP_URL_HOST);
			$parsed_subdir = untrailingslashit(parse_url(home_url(), PHP_URL_PATH));
			if ($parsed_subdir && '/' != $parsed_subdir) $parsed_url .= str_replace(array('/', '\\'), '_', $parsed_subdir);
			$blog_name = str_replace('__', '_', preg_replace('/[^A-Za-z0-9_]/','', str_replace(' ','_', substr($parsed_url, 0, 32))));
			if (!$blog_name || preg_match('#^_+$#', $blog_name)) $blog_name = 'WordPress_Backup';
		}

		$this->debug = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_debug_mode');
		$this->iwp_backup_dir = $iwp_backup_core->backups_dir_location();
		$this->iwp_backup_dir_realpath = realpath($this->iwp_backup_dir);
		$iwp_backup_core->blog_name = $blog_name;
		if ('no' === $backup_files) {
			$this->use_zip_object = 'IWP_MMB_PclZip';
			return;
		}

		$this->extensions_to_not_compress = array_unique(array_map('strtolower', array_map('trim', explode(',', IWP_ZIP_NOCOMPRESS))));

		$this->altered_since = $altered_since;

		// false means 'tried + failed'; whereas 0 means 'not yet tried'
		// Disallow binzip on OpenVZ when we're not sure there's plenty of memory
		if ($this->binzip === 0 && (!defined('IWP_PREFERPCLZIP') || IWP_PREFERPCLZIP != true) && (!defined('IWP_NO_BINZIP') || !IWP_NO_BINZIP) && $iwp_backup_core->current_resumption <9) {

			if (@file_exists('/proc/user_beancounters') && @file_exists('/proc/meminfo') && @is_readable('/proc/meminfo')) {
				$meminfo = @file_get_contents('/proc/meminfo', false, null, -1, 200);
				if (is_string($meminfo) && preg_match('/MemTotal:\s+(\d+) kB/', $meminfo, $matches)) {
					$memory_mb = $matches[1]/1024;
					# If the report is of a large amount, then we're probably getting the total memory on the hypervisor (this has been observed), and don't really know the VPS's memory
					$vz_log = "OpenVZ; reported memory: ".round($memory_mb, 1)." MB";
					if ($memory_mb < 1024 || $memory_mb > 8192) {
						$openvz_lowmem = true;
						$vz_log .= " (will not use BinZip)";
					}
					$iwp_backup_core->log($vz_log);
				}
			}
			if (empty($openvz_lowmem)) {
				$iwp_backup_core->log('Checking if we have a zip executable available');
				$binzip = $iwp_backup_core->find_working_bin_zip();
				if (is_string($binzip)) {
					$iwp_backup_core->log("Zip engine: found/will use a binary zip: $binzip");
					$this->binzip = $binzip;
					$this->use_zip_object = 'IWP_MMB_BinZip';
				}
			}
		}

		# In tests, PclZip was found to be 25% slower than ZipArchive
		if ($this->use_zip_object != 'IWP_MMB_PclZip' && empty($this->binzip) && ((defined('IWP_PREFERPCLZIP') && IWP_PREFERPCLZIP == true) || !class_exists('ZipArchive') || !class_exists('IWP_MMB_ZipArchive') || (!extension_loaded('zip') && !method_exists('ZipArchive', 'AddFile')))) {
			global $iwp_backup_core;
			$iwp_backup_core->log("Zip engine: ZipArchive is not available or is disabled (will use PclZip if needed)");
			$this->use_zip_object = 'IWP_MMB_PclZip';
		}

	}

	public function report_remotestorage_extrainfo($service, $info_html, $info_plain) {
		$this->remotestorage_extrainfo[$service] = array('pretty' => $info_html, 'plain' => $info_plain);
	}

	// Public, because called from the 'More Files' add-on
	public function create_zip($create_from_dir, $whichone, $backup_file_basename, $index, $first_linked_index = false) {
		// Note: $create_from_dir can be an array or a string
		@set_time_limit(IWP_SET_TIME_LIMIT);
		$original_index = $index;
		$this->index = $index;
		$this->first_linked_index = (false === $first_linked_index) ? 0 : $first_linked_index;

		$this->whichone = $whichone;

		global $iwp_backup_core;

		$this->zip_split_every = max((int)$iwp_backup_core->jobdata_get('split_every'), IWP_SPLIT_MIN)*1048576;

		if ('others' != $whichone) $iwp_backup_core->log("Beginning creation of dump of $whichone (split every: ".round($this->zip_split_every/1048576,1)." MB)");

		if (is_string($create_from_dir) && !file_exists($create_from_dir)) {
			$flag_error = true;
			$iwp_backup_core->log("Does not exist: $create_from_dir");
			if ('mu-plugins' == $whichone) {
				if (!function_exists('get_mu_plugins')) require_once(ABSPATH.'wp-admin/includes/plugin.php');
				$mu_plugins = get_mu_plugins();
				if (count($mu_plugins) == 0) {
					$iwp_backup_core->log("There appear to be no mu-plugins to back up. Will not raise an error.");
					$flag_error = false;
				}
			}
			if ($flag_error) $iwp_backup_core->log(sprintf(__("%s - could not back this entity up; the corresponding directory does not exist (%s)", 'iwp_backup_core'), $whichone, $create_from_dir), 'error');
			return false;
		}

		$itext = (empty($index)) ? '' : ($index+1);
		$base_path = $backup_file_basename.'-'.$whichone.$itext.'.zip';
		$full_path = $this->iwp_backup_dir.'/'.$base_path;
		$time_now = time();

		# This is compatible with filenames which indicate increments, as it is looking only for the current increment
		if (file_exists($full_path)) {
			# Gather any further files that may also exist
			$files_existing = array();
			while (file_exists($full_path)) {
				$files_existing[] = $base_path;
				$time_mod = (int)@filemtime($full_path);
				$iwp_backup_core->log($base_path.": this file has already been created (age: ".round($time_now-$time_mod,1)." s)");
				if ($time_mod>100 && ($time_now-$time_mod)<30) {
					$iwp_backup_core->terminate_due_to_activity($base_path, $time_now, $time_mod);
				}
				$index++;
				# This is compatible with filenames which indicate increments, as it is looking only for the current increment
				$base_path = $backup_file_basename.'-'.$whichone.($index+1).'.zip';
				$full_path = $this->iwp_backup_dir.'/'.$base_path;
			}
		}

		// Temporary file, to be able to detect actual completion (upon which, it is renamed)

		// New (Jun-13) - be more aggressive in removing temporary files from earlier attempts - anything >=600 seconds old of this kind
		$iwp_backup_core->clean_temporary_files('_'.$iwp_backup_core->nonce."-$whichone", 600);

		// Firstly, make sure that the temporary file is not already being written to - which can happen if a resumption takes place whilst an old run is still active
		$zip_name = $full_path.'.tmp';
		$time_mod = (int)@filemtime($zip_name);
		if (file_exists($zip_name) && $time_mod>100 && ($time_now-$time_mod)<30) {
			$iwp_backup_core->terminate_due_to_activity($zip_name, $time_now, $time_mod);
		}
		if (file_exists($zip_name)) {
			$iwp_backup_core->log("File exists ($zip_name), but was apparently not modified within the last 30 seconds, so we assume that any previous run has now terminated (time_mod=$time_mod, time_now=$time_now, diff=".($time_now-$time_mod).")");
		}

		// Now, check for other forms of temporary file, which would indicate that some activity is going on (even if it hasn't made it into the main zip file yet)
		// Note: this doesn't catch PclZip temporary files
		$d = dir($this->iwp_backup_dir);
		$match = '_'.$iwp_backup_core->nonce."-".$whichone;
		while (false !== ($e = $d->read())) {
			if ('.' == $e || '..' == $e || !is_file($this->iwp_backup_dir.'/'.$e)) continue;
			$ziparchive_match = preg_match("/$match([0-9]+)?\.zip\.tmp\.([A-Za-z0-9]){6}?$/i", $e);
			$binzip_match = preg_match("/^zi([A-Za-z0-9]){6}$/", $e);
			$pclzip_match = preg_match("/^pclzip-[a-z0-9]+.tmp$/", $e);
			if ($time_now-filemtime($this->iwp_backup_dir.'/'.$e) < 30 && ($ziparchive_match || (0 != $iwp_backup_core->current_resumption && ($binzip_match || $pclzip_match)))) {
				$iwp_backup_core->terminate_due_to_activity($this->iwp_backup_dir.'/'.$e, $time_now, filemtime($this->iwp_backup_dir.'/'.$e));
			}
		}
		@$d->close();
		clearstatcache();

		if (isset($files_existing)) {
			# Because of zip-splitting, the mere fact that files exist is not enough to indicate that the entity is finished. For that, we need to also see that no subsequent file has been started.
			# Q. What if the previous runner died in between zips, and it is our job to start the next one? A. The next temporary file is created before finishing the former zip, so we are safe (and we are also safe-guarded by the updated value of the index being stored in the database).
			return $files_existing;
		}

		$this->log_account_space();

		$this->zip_microtime_start = microtime(true);

		# The paths in the zip should then begin with '$whichone', having removed WP_CONTENT_DIR from the front
		$zipcode = $this->make_zipfile($create_from_dir, $backup_file_basename, $whichone);
		if ($zipcode !== true) {
			$iwp_backup_core->log("ERROR: Zip failure: Could not create $whichone zip (".$this->index." / $index)");
			$iwp_backup_core->log(sprintf(__("Could not create %s zip. Consult the log file for more information.",'iwp_backup_core'),$whichone), 'error');
			# The caller is required to update $index from $this->index
			return false;
		} else {
			$itext = (empty($this->index)) ? '' : ($this->index+1);
			$full_path = $this->iwp_backup_dir.'/'.$backup_file_basename.'-'.$whichone.$itext.'.zip';
			if (file_exists($full_path.'.tmp')) {
				if (@filesize($full_path.'.tmp') === 0) {
					$iwp_backup_core->log("Did not create $whichone zip (".$this->index.") - not needed");
					@unlink($full_path.'.tmp');
				} else {
				
					$checksum_description = '';
		
					$checksums = $iwp_backup_core->which_checksums();
		
					foreach ($checksums as $checksum) {
					
						$cksum = hash_file($checksum, $full_path.'.tmp');
						$iwp_backup_core->jobdata_set($checksum.'-'.$whichone.$this->index, $cksum);
						if ($checksum_description) $checksum_description .= ', ';
						$checksum_description .= "$checksum: $cksum";
					
					}
				
					@rename($full_path.'.tmp', $full_path);
					$timetaken = max(microtime(true)-$this->zip_microtime_start, 0.000001);
					$kbsize = filesize($full_path)/1024;
					$rate = round($kbsize/$timetaken, 1);
					$iwp_backup_core->log("Created $whichone zip (".$this->index.") - ".round($kbsize,1)." KB in ".round($timetaken,1)." s ($rate KB/s) ($checksum_description)");
					// We can now remove any left-over temporary files from this job
				}
			} elseif ($this->index > $original_index) {
				$iwp_backup_core->log("Did not create $whichone zip (".$this->index.") - not needed (2)");
				# Added 12-Feb-2014 (to help multiple morefiles)
				$this->index--;
			} else {
				$iwp_backup_core->log("Looked-for $whichone zip (".$this->index.") was not found (".basename($full_path).".tmp)", 'warning');
			}
			$iwp_backup_core->clean_temporary_files('_'.$iwp_backup_core->nonce."-$whichone", 0);
		}

		// Remove cache list files as well, if there are any
		$iwp_backup_core->clean_temporary_files('_'.$iwp_backup_core->nonce."-$whichone", 0, true);

		# Create the results array to send back (just the new ones, not any prior ones)
		$files_existing = array();
		$res_index = 0;
		for ($i = $original_index; $i<= $this->index; $i++) {
			$itext = (empty($i)) ? '' : ($i+1);
			$full_path = $this->iwp_backup_dir.'/'.$backup_file_basename.'-'.$whichone.$itext.'.zip';
			if (file_exists($full_path)) {
				$files_existing[$res_index] = $backup_file_basename.'-'.$whichone.$itext.'.zip';
			}
			$res_index++;
		}
		return $files_existing;
	}

	// This method is for calling outside of a cloud_backup() context. It constructs a list of services for which prune operations should be attempted, and then calls prune_retained_backups() if necessary upon them.
	public function do_prune_standalone() {
		global $iwp_backup_core;

		$services = $iwp_backup_core->just_one($iwp_backup_core->jobdata_get('service'));
		if (!is_array($services)) $services = array($services);

		$prune_services = array();

		foreach ($services as $ind => $service) {
			if ($service == "none" || '' == $service) continue;

			$objname = "IWP_MMB_UploadModule_${service}";
			if (!class_exists($objname) && file_exists($GLOBALS['iwp_mmb_plugin_dir'].'/backup/'.$service.'.php')) {
				require_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/'.$service.'.php');
			}
			if (class_exists($objname)) {
				$remote_obj = new $objname;
				$pass_to_prune = null;
				$prune_services[$service] = array($remote_obj, null);
			} else {
				$iwp_backup_core->log("Could not prune from service $service: remote method not found");
			}

		}

		if (!empty($prune_services)) $this->prune_retained_backups($prune_services);
	}

	// Dispatch to the relevant function
	public function cloud_backup($backup_array) {

		global $iwp_backup_core;

		$services = $iwp_backup_core->just_one($iwp_backup_core->jobdata_get('service'));
		if (!is_array($services)) $services = array($services);

		// We need to make sure that the loop below actually runs
		if (empty($services)) $services = array('none');

		$iwp_backup_core->jobdata_set('jobstatus', 'clouduploading');

		add_action('http_request_args', array($iwp_backup_core, 'modify_http_options'));

		$upload_status = $iwp_backup_core->jobdata_get('uploading_substatus');
		if (!is_array($upload_status) || !isset($upload_status['t'])) {
			$upload_status = array('i' => 0, 'p' => 0, 't' => max(1, count($services))*count($backup_array));
			$iwp_backup_core->jobdata_set('uploading_substatus', $upload_status);
		}

		$do_prune = array();

		# If there was no check-in last time, then attempt a different service first - in case a time-out on the attempted service leads to no activity and everything stopping
		if (count($services) >1 && !empty($iwp_backup_core->no_checkin_last_time)) {
			$iwp_backup_core->log('No check-in last time: will try a different remote service first');
			array_push($services, array_shift($services));
			// Make sure that the 'no worthwhile activity' detector isn't flumoxed by the starting of a new upload at 0%
			if ($iwp_backup_core->current_resumption > 9) $iwp_backup_core->jobdata_set('uploaded_lastreset', $iwp_backup_core->current_resumption);
			if (1 == ($iwp_backup_core->current_resumption % 2) && count($services)>2) array_push($services, array_shift($services));
		}

		$errors_before_uploads = $iwp_backup_core->error_count();

		foreach ($services as $ind => $service) {
			# Used for logging by record_upload_chunk()
			$this->current_service = $service;
			# Used when deciding whether to delete the local file
			$this->last_service = ($ind+1 >= count($services) && $errors_before_uploads == $iwp_backup_core->error_count()) ? true : false;

			$log_extra = ($this->last_service) ? ' (last)' : '';
			$iwp_backup_core->log("Cloud backup selection (".($ind+1)."/".count($services)."): ".$service.$log_extra);
			@set_time_limit(IWP_SET_TIME_LIMIT);

			$method_include = $GLOBALS['iwp_mmb_plugin_dir'].'/backup/'.$service.'.php';
			if (file_exists($method_include)) require_once($method_include);

			if ($service == "none" || '' == $service) {
				$iwp_backup_core->log("No remote despatch: user chose no remote backup service");
				# Still want to mark as "uploaded", to signal that nothing more needs doing. (Important on incremental runs with no cloud storage).
				foreach ($backup_array as $bind => $file) {
					if ($iwp_backup_core->is_uploaded($file)) {
						$iwp_backup_core->log("Already uploaded: $file");
					} else {
						$iwp_backup_core->uploaded_file($file, true);
					}
				}
				$this->prune_retained_backups(array("none" => array(null, null)));
			} else {
				$iwp_backup_core->log("Beginning dispatch of backup to remote ($service)");
				$sarray = array();
				foreach ($backup_array as $bind => $file) {
					if ($iwp_backup_core->is_uploaded($file, $service)) {
						$iwp_backup_core->log("Already uploaded to $service: $file");
					} else {
						$sarray[$bind] = $file;
					}
				}
				$objname = "IWP_MMB_UploadModule_$service";
				if (class_exists($objname)) {
					$remote_obj = new $objname;
					if (count($sarray)>0) {
						$pass_to_prune = $remote_obj->backup($sarray);
						$do_prune[$service] = array($remote_obj, $pass_to_prune);
					} else {
						// We still need to make sure that prune is run on this remote storage method, even if all entities were previously uploaded
						$do_prune[$service] = array($remote_obj, null);
					}
				} else {
					$iwp_backup_core->log("Unexpected error: no class '$objname' was found ($method_include)");
					$iwp_backup_core->log(sprintf(__("Unexpected error: no class '%s' was found (your iwp_backup_core installation seems broken - try re-installing)", 'iwp_backup_core'), $objname), 'error');
				}
			}
		}

		if (!empty($do_prune)) $this->prune_retained_backups($do_prune);

		remove_action('http_request_args', array($iwp_backup_core, 'modify_http_options'));

	}

	private function group_backups($backup_history) {
		return array(array('sets' => $backup_history, 'process_order' => 'keep_newest'));
	}
	
	// $services *must* be an array
	public function prune_retained_backups($services) {

		global $iwp_backup_core, $wpdb;

		if ($iwp_backup_core->jobdata_get('remotesend_info') != '') {
			$iwp_backup_core->log("Prune old backups from local store: skipping, as this was a remote send operation");
			return;
		}

		if (method_exists($wpdb, 'check_connection') && (!defined('IWP_SUPPRESS_CONNECTION_CHECKS') || !IWP_SUPPRESS_CONNECTION_CHECKS)) {
			if (!$wpdb->check_connection(false)) {
				$iwp_backup_core->reschedule(60);
				$iwp_backup_core->log("It seems the database went away; scheduling a resumption and terminating for now");
				$iwp_backup_core->record_still_alive();
				die;
			}
		}

		// If they turned off deletion on local backups, then there is nothing to do
		if (0 == IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_delete_local') && 1 == count($services) && in_array('none', $services)) {
			$iwp_backup_core->log("Prune old backups from local store: nothing to do, since the user disabled local deletion and we are using local backups");
			return;
		}

// 		$iwp_backup_core->jobdata_set('jobstatus', 'pruning');
// 		$iwp_backup_core->jobdata_set('prune', 'begun');
		call_user_func_array(array($iwp_backup_core, 'jobdata_set_multi'), array('jobstatus', 'pruning', 'prune', 'begun'));

		// Number of backups to retain - files
		$label = $iwp_backup_core->jobdata_get('label');
		$IWP_retain = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_retain', 2);
		$IWP_retain = is_numeric($IWP_retain) ? $IWP_retain : 1;

		// Number of backups to retain - db
		$IWP_retain_db = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_retain_db', $IWP_retain);
		$IWP_retain_db = is_numeric($IWP_retain_db) ? $IWP_retain_db : 1;
		$other_method_backups = iwp_mmb_get_backup_ID_by_taskname('multicall', $label);
		$current_backups = $iwp_backup_core->get_timestamp_by_label($label);
		$all_backups = array();
		$delete_backup = array();
		if (!empty($other_method_backups)) {
			$all_backups = array_merge($all_backups, $other_method_backups);
			if (!empty($current_backups)) {
				$all_backups = array_merge($all_backups, $current_backups);
				ksort($all_backups);
				ksort($current_backups);
				foreach ($other_method_backups as $lastUpdateTime => $historyID) {
					foreach ($current_backups as $time => $value) {
						if ($time > $lastUpdateTime) {
							$delete_backup[$lastUpdateTime] = $historyID;
						}
						break;
					}
				}
			}
		}
		if (!empty($delete_backup)) {
			$total_backups = count($all_backups);
			if ($total_backups > $IWP_retain) {
				require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup.class.singlecall.php");
				$backup_instance = new IWP_MMB_Backup_Singlecall();
				foreach ($delete_backup as $lastUpdateTime => $historyID) {
					$total_backups--;
					$backup_instance->delete_backup(array('result_id' => $historyID));
					if ($total_backups<= $IWP_retain) {
						return;
					}
				}
			}
		}
		// $IWP_retain_db-=1;
		$iwp_backup_core->log("Retain: beginning examination of existing backup sets; user setting: retain_files=$IWP_retain, retain_db=$IWP_retain_db");

		// Returns an array, most recent first, of backup sets
		
		
		$ignored_because_imported = array();
		ksort($current_backups);
		$delete_backup_count = count($current_backups);
		if (!empty($current_backups) && $delete_backup_count>$IWP_retain) {
			foreach ($current_backups as $timestamp => $value) {
				$delete_backup_count--;
				$iwp_backup_core->delete_backup(array('result_id'=>$timestamp));
				if ($delete_backup_count==$IWP_retain) {
					break;
				}
			}
		}

		
		// // Remove non-native (imported) backups, which are neither counted nor pruned. It's neater to do these in advance, and log only one line.
		// $functional_backup_history = $backup_history;
		// foreach ($functional_backup_history as $backup_time => $backup_to_examine) {
		// 	if (isset($backup_to_examine['native']) && false == $backup_to_examine['native']) {
		// 		$ignored_because_imported[] = $backup_time;
		// 		unset($functional_backup_history[$backup_time]);
		// 	}
		// }
		// if (!empty($ignored_because_imported)) {
		// 	$iwp_backup_core->log("These backup set(s) were imported from a remote location, so will not be counted or pruned. Skipping: ".implode(', ', $ignored_because_imported));
		// }

		// $backupable_entities = $iwp_backup_core->get_backupable_file_entities(true);
		// $backupable_entities = array_merge($backupable_entities, array('backup_file_basename'=>0, 'db' =>0));
		// $database_backups_found = array();

		// $file_entities_backups_found = array();
		// foreach ($backupable_entities as $entity => $info) {
		// 	$file_entities_backups_found[$entity] = 0;
		// }
		// $file_entities_backups_found['db'] = 0;
// 		if (false === ($backup_db_groups = apply_filters('IWP_group_backups_for_pruning', false, $functional_backup_history, 'db'))) {
// 			$backup_db_groups = $this->group_backups($functional_backup_history);
// 		}
// 		$iwp_backup_core->log("Number of backup sets in history: ".count($backup_history)."; groups (db): ".count($backup_db_groups));
// 		foreach ($backup_db_groups as $group_id => $group) {
			
// 			// The array returned by iwp_backup_core::get_backup_history() is already sorted, with most-recent first
// // 			foreach ($backup_history as $backup_datestamp => $backup_to_examine) {

// 			if (empty($group['sets']) || !is_array($group['sets'])) continue;
// 			$sets = $group['sets'];

// 			// Sort the groups into the desired "keep this first" order
// 			$process_order = (!empty($group['process_order']) && 'keep_oldest' == $group['process_order']) ? 'keep_oldest' : 'keep_newest';
// 			if ('keep_oldest' == $process_order) ksort($sets);
			
// 			$rule = !empty($group['rule']) ? $group['rule'] : array('after-howmany' => 0, 'after-period' => 0, 'every-period' => 1, 'every-howmany' => 1);
			
// 			foreach ($sets as $backup_datestamp => $backup_to_examine) {
// 				if ($backup_to_examine['label'] != $label) {
// 					continue;
// 				}

// 				$files_to_prune = array();
// 				$nonce = empty($backup_to_examine['nonce']) ? '???' : $backup_to_examine['nonce'];

// 				// $backup_to_examine is an array of file names, keyed on db/plugins/themes/uploads
// 				// The new backup_history array is saved afterwards, so remember to unset the ones that are to be deleted
// 				$iwp_backup_core->log(sprintf("Examining (for databases) backup set with group_id=$group_id, nonce=%s, datestamp=%s (%s)", $nonce, $backup_datestamp, gmdate('M d Y H:i:s', $backup_datestamp)));

// 				// Auto-backups are only counted or deleted once we have reached the retain limit - before that, they are skipped
// 				$is_autobackup = !empty($backup_to_examine['autobackup']);

// 				$remote_sent = (!empty($backup_to_examine['service']) && ((is_array($backup_to_examine['service']) && in_array('remotesend', $backup_to_examine['service'])) || 'remotesend' === $backup_to_examine['service'])) ? true : false;

// 				$any_deleted_via_filter_yet = false;

// 				// Databases
// 				foreach ($backup_to_examine as $key => $data) {
// 					if ('db' != strtolower(substr($key, 0, 2)) || '-size' == substr($key, -5, 5)) continue;

// 					if (empty($database_backups_found[$key])) $database_backups_found[$key] = 0;
					
// 					if ($nonce == $iwp_backup_core->nonce) {
// 						$iwp_backup_core->log("This backup set is the backup set just made, so will not be deleted.");
// 						$database_backups_found[$key]++;
// 						continue;
// 					}
					
// 					if ($is_autobackup) {
// 						if ($any_deleted_via_filter_yet) {
// 							$iwp_backup_core->log("This backup set ($backup_datestamp) was an automatic backup, but we have previously deleted a backup due to a limit, so it will be pruned (but not counted towards numerical limits).");
// 							$prune_it = true;
// 						} elseif ($database_backups_found[$key] < $IWP_retain_db) {
// 							$iwp_backup_core->log("This backup set ($backup_datestamp) was an automatic backup, and we have not yet reached any retain limits, so it will not be counted or pruned. Skipping.");
// 							continue;
// 						} else {
// 							$iwp_backup_core->log("This backup set ($backup_datestamp) was an automatic backup, and we have already reached retain limits, so it will be pruned.");
// 							$prune_it = true;
// 						}
// 					} else {
// 						$prune_it = false;
// 					}

// 					if ($remote_sent) {
// 						$prune_it = true;
// 						$iwp_backup_core->log("$backup_datestamp: $key: was sent to remote site; will remove from local record (only)");
// 					}
					
// 					// All non-auto backups must be run through this filter (in date order) regardless of the current state of $prune_it - so that filters are able to track state.
// 					$prune_it_before_filter = $prune_it;

// 					if (!$is_autobackup) $prune_it = apply_filters('IWP_prune_or_not', $prune_it, 'db', $backup_datestamp, $key, $database_backups_found[$key], $rule, $group_id);

// 					// Apply the final retention limit list (do not increase the 'retained' counter before seeing if the backup is being pruned for some other reason)
// 					if (!$prune_it && !$is_autobackup) {

// 						if ($database_backups_found[$key] + 1 > $IWP_retain_db) {
// 							$prune_it = true;

// 							$fname = (is_string($data)) ? $data : $data[0];
// 							$iwp_backup_core->log("$backup_datestamp: $key: this set includes a database (".$fname."); db count is now ".$database_backups_found[$key]);

// 							$iwp_backup_core->log("$backup_datestamp: $key: over retain limit ($IWP_retain_db); will delete this database");
// 						}
					
// 					}
					
// 					if ($prune_it) {
// 						if (!$prune_it_before_filter) $any_deleted_via_filter_yet = true;

// 						if (!empty($data)) {
// 							$size_key = $key.'-size';
// 							$size = isset($backup_to_examine[$size_key]) ? $backup_to_examine[$size_key] : null;
// 							foreach ($services as $service => $sd) {
// 								$this->prune_file($service, $data, $sd[0], $sd[1], array($size));
// 							}
// 						}
// 						unset($backup_to_examine[$key]);
// 						$iwp_backup_core->record_still_alive();
// 					} elseif (!$is_autobackup) {
// 						$database_backups_found[$key]++;
// 					}else{
// 						continue;
// 					}

// 					$backup_to_examine = $this->remove_backup_set_if_empty($backup_to_examine, $backup_datestamp, $backupable_entities, $backup_history);
// 					if (empty($backup_to_examine)) {
// 						unset($functional_backup_history[$backup_datestamp]);
// 						unset($backup_history[$backup_datestamp]);
// 						$this->maybe_save_backup_history_and_reschedule($backup_history);
// 					} else {
// 						$functional_backup_history[$backup_datestamp] = $backup_to_examine;
// 						$backup_history[$backup_datestamp] = $backup_to_examine;
// 					}
// 				}
// 			}
// 		}

// 		if (false === ($backup_files_groups = apply_filters('IWP_group_backups_for_pruning', false, $functional_backup_history, 'files'))) {
// 			$backup_files_groups = $this->group_backups($functional_backup_history);
// 		}

// 		$iwp_backup_core->log("Number of backup sets in history: ".count($backup_history)."; groups (files): ".count($backup_files_groups));
		
// 		// Now again - this time for the files
// 		foreach ($backup_files_groups as $group_id => $group) {
			
// 			// The array returned by iwp_backup_core::get_backup_history() is already sorted, with most-recent first
// // 			foreach ($backup_history as $backup_datestamp => $backup_to_examine) {

// 			if (empty($group['sets']) || !is_array($group['sets'])) continue;
// 			$sets = $group['sets'];
			
// 			// Sort the groups into the desired "keep this first" order
// 			$process_order = (!empty($group['process_order']) && 'keep_oldest' == $group['process_order']) ? 'keep_oldest' : 'keep_newest';
// 			// Youngest - i.e. smallest epoch - first
// 			if ('keep_oldest' == $process_order) ksort($sets);

// 			$rule = !empty($group['rule']) ? $group['rule'] : array('after-howmany' => 0, 'after-period' => 0, 'every-period' => 1, 'every-howmany' => 1);
			
// 			foreach ($sets as $backup_datestamp => $backup_to_examine) {
// 				if ($backup_to_examine['label'] != $label) {
// 					continue;
// 				}
// 				$files_to_prune = array();
// 				$nonce = empty($backup_to_examine['nonce']) ? '???' : $backup_to_examine['nonce'];

// 				// $backup_to_examine is an array of file names, keyed on db/plugins/themes/uploads
// 				// The new backup_history array is saved afterwards, so remember to unset the ones that are to be deleted
// 				$iwp_backup_core->log(sprintf("Examining (for files) backup set with nonce=%s, datestamp=%s (%s)", $nonce, $backup_datestamp, gmdate('M d Y H:i:s', $backup_datestamp)));

// 				// This was already done earlier
// // 				if (isset($backup_to_examine['native']) && false == $backup_to_examine['native']) {
// // 					$iwp_backup_core->log("This backup set was imported from a remote location, so will not be counted or pruned. Skipping.");
// // 					continue;
// // 				}

// 				// Auto-backups are only counted or deleted once we have reached the retain limit - before that, they are skipped
// 				$is_autobackup = !empty($backup_to_examine['autobackup']);

// 				$remote_sent = (!empty($backup_to_examine['service']) && ((is_array($backup_to_examine['service']) && in_array('remotesend', $backup_to_examine['service'])) || 'remotesend' === $backup_to_examine['service'])) ? true : false;

// 				$any_deleted_via_filter_yet = false;
		
// 				$file_sizes = array();

// 				// Files
// 				foreach ($backupable_entities as $entity => $info) {
// 					if (!empty($backup_to_examine[$entity])) {
					
// 						// This should only be able to happen if you import backups with a future timestamp
// 						if ($nonce == $iwp_backup_core->nonce) {
// 							$iwp_backup_core->log("This backup set is the backup set just made, so will not be deleted.");
// 							$file_entities_backups_found[$entity]++;
// 							continue;
// 						}

// 						if ($is_autobackup) {
// 							if ($any_deleted_via_filter_yet) {
// 								$iwp_backup_core->log("This backup set was an automatic backup, but we have previously deleted a backup due to a limit, so it will be pruned (but not counted towards numerical limits).");
// 								$prune_it = true;
// 							} elseif ($file_entities_backups_found[$entity] < $IWP_retain) {
// 								$iwp_backup_core->log("This backup set ($backup_datestamp) was an automatic backup, and we have not yet reached any retain limits, so it will not be counted or pruned. Skipping.");
// 								continue;
// 							} else {
// 								$iwp_backup_core->log("This backup set ($backup_datestamp) was an automatic backup, and we have already reached retain limits, so it will be pruned.");
// 								$prune_it = true;
// 							}
// 						} else {
// 							$prune_it = false;
// 						}

// 						if ($remote_sent) {
// 							$prune_it = true;
// 						}

// 						// All non-auto backups must be run through this filter (in date order) regardless of the current state of $prune_it - so that filters are able to track state.
// 						$prune_it_before_filter = $prune_it;
// 						if (!$is_autobackup) $prune_it = apply_filters('IWP_backup_core_prune_or_not', $prune_it, 'files', $backup_datestamp, $entity, $file_entities_backups_found[$entity], $rule, $group_id);

// 						// The "more than maximum to keep?" counter should not be increased until we actually know that the set is being kept. Before verison 1.11.22, we checked this before running the filter, which resulted in the counter being increased for sets that got pruned via the filter (i.e. not kept) - and too many backups were thus deleted
// 						if (!$prune_it && !$is_autobackup) {
// 							if ($file_entities_backups_found[$entity] >= $IWP_retain) {
// 								$iwp_backup_core->log("$entity: over retain limit ($IWP_retain); will delete this file entity");
// 								$prune_it = true;
// 							}
// 						}else{
// 							continue;
// 						}
						
// 						if ($prune_it) {
// 							if (!$prune_it_before_filter) $any_deleted_via_filter_yet = true;
// 							$prune_this = $backup_to_examine[$entity];
// 							if (is_string($prune_this)) $prune_this = array($prune_this);
// 							foreach ($prune_this as $k => $prune_file) {
// 								if ($remote_sent) {
// 									$iwp_backup_core->log("$entity: $backup_datestamp: was sent to remote site; will remove from local record (only)");
// 								}
// 								$size_key = (0 == $k) ? $entity.'-size' : $entity.$k.'-size';
// 								$size = (isset($backup_to_examine[$size_key])) ? $backup_to_examine[$size_key] : null;
// 								$files_to_prune[] = $prune_file;
// 								$file_sizes[] = $size;
// 							}
// 							unset($backup_to_examine[$entity]);
							
// 						} elseif (!$is_autobackup) {
// 							$file_entities_backups_found[$entity]++;
// 						}
// 					}
// 				}

// 				// Sending an empty array is not itself a problem - except that the remote storage method may not check that before setting up a connection, which can waste time: especially if this is done every time around the loop.
// 				if (!empty($files_to_prune)) {
// 					// Actually delete the files
// 					foreach ($services as $service => $sd) {
// 						$this->prune_file($service, $files_to_prune, $sd[0], $sd[1], $file_sizes);
// 						$iwp_backup_core->record_still_alive();
// 					}
// 				}

// 				$backup_to_examine = $this->remove_backup_set_if_empty($backup_to_examine, $backup_datestamp, $backupable_entities, $backup_history);
// 				if (empty($backup_to_examine)) {
// // 					unset($functional_backup_history[$backup_datestamp]);
// 					unset($backup_history[$backup_datestamp]);
// 					$this->maybe_save_backup_history_and_reschedule($backup_history);
// 				} else {
// // 					$functional_backup_history[$backup_datestamp] = $backup_to_examine;
// 					$backup_history[$backup_datestamp] = $backup_to_examine;
// 				}

// 			// Loop over backup sets
// 			}
			
// 		// Look over backup groups
// 		}

// 		$iwp_backup_core->log("Retain: saving new backup history (sets now: ".count($backup_history).") and finishing retain operation");
		
// 		IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_backup_history', $backup_history, false);

		do_action('IWP_prune_retained_backups_finished');
		
		$iwp_backup_core->jobdata_set('prune', 'finished');

	}

	// The purpose of this is to save the backup history periodically - for the benefit of setups where the pruning takes longer than the total allow run time (e.g. if the network communications to the remote storage have delays in, and there are a lot of sets to scan)
	private function maybe_save_backup_history_and_reschedule($backup_history) {
		static $last_saved_at = 0;
		if (!$last_saved_at) $last_saved_at = time();
		if (time() - $last_saved_at >= 10) {
			global $iwp_backup_core;
			$iwp_backup_core->log("Retain: saving new backup history, because at least 10 seconds have passed since the last save (sets now: ".count($backup_history).")");
			IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_backup_history', $backup_history, false);
			$iwp_backup_core->something_useful_happened();
			$last_saved_at = time();
		}
	}
	
	private function remove_backup_set_if_empty($backup_to_examine, $backup_datestamp, $backupable_entities, $backup_history) {
	
		global $iwp_backup_core;

		// Get new result, post-deletion; anything left in this set?
		$contains_files = 0;
		foreach ($backupable_entities as $entity => $info) {
			if (isset($backup_to_examine[$entity])) {
				$contains_files = 1;
				break;
			}
		}

		$contains_db = 0;
		foreach ($backup_to_examine as $key => $data) {
			if ('db' == strtolower(substr($key, 0, 2)) && '-size' != substr($key, -5, 5)) {
				$contains_db = 1;
				break;
			}
		}

		// Delete backup set completely if empty, o/w just remove DB
		// We search on the four keys which represent data, allowing other keys to be used to track other things
		if (!$contains_files && !$contains_db) {
			$iwp_backup_core->log("This backup set is now empty; will remove from history");
			if (isset($backup_to_examine['nonce'])) {
				$fullpath = $this->iwp_backup_dir."/log.".$backup_to_examine['nonce'].".txt";
				if (is_file($fullpath)) {
					$iwp_backup_core->log("Deleting log file (log.".$backup_to_examine['nonce'].".txt)");
					@unlink($fullpath);
				} else {
					$iwp_backup_core->log("Corresponding log file (log.".$backup_to_examine['nonce'].".txt) not found - must have already been deleted");
				}
			} else {
				$iwp_backup_core->log("No nonce record found in the backup set, so cannot delete any remaining log file");
			}
// 			unset($backup_history[$backup_datestamp]);
			return false;
		} else {
			$iwp_backup_core->log("This backup set remains non-empty (f=$contains_files/d=$contains_db); will retain in history");
			return $backup_to_examine;
		}
		
	}
	
	# $dofiles: An array of files (or a single string for one file)
	private function prune_file($service, $dofiles, $method_object = null, $object_passback = null, $file_sizes = array()) {
		global $iwp_backup_core;
		if (!is_array($dofiles)) $dofiles=array($dofiles);
		
		if (!apply_filters('IWP_prune_file', true, $dofiles, $service, $method_object, $object_passback, $file_sizes)) {
			$iwp_backup_core->log("Prune: service=$service: skipped via filter");
		}
		foreach ($dofiles as $i => $dofile) {
			if (empty($dofile)) continue;
			$iwp_backup_core->log("Delete file: $dofile, service=$service");
			$fullpath = $this->iwp_backup_dir.'/'.$dofile;
			// delete it if it's locally available
			if (file_exists($fullpath)) {
				$iwp_backup_core->log("Deleting local copy ($dofile)");
				@unlink($fullpath);
			}
		}
		// Despatch to the particular method's deletion routine
		if (!is_null($method_object)) $method_object->delete($dofiles, $object_passback, $file_sizes);
	}

	// The purpose of this function is to make sure that the options table is put in the database first, then the users table, then the site + blogs tables (if present - multisite), then the usermeta table; and after that the core WP tables - so that when restoring we restore the core tables first
	private function backup_db_sorttables($a_arr, $b_arr) {

		$a = $a_arr['name'];
		$a_table_type = $a_arr['type'];
		$b = $b_arr['name'];
		$b_table_type = $b_arr['type'];
	
		// Views must always go after tables (since they can depend upon them)
		if ('VIEW' == $a_table_type && 'VIEW' != $b_table_type) return 1;
		if ('VIEW' == $b_table_type && 'VIEW' != $a_table_type) return -1;
	
		if ('wp' != $this->whichdb) return strcmp($a, $b);

		global $iwp_backup_core;
		if ($a == $b) return 0;
		$our_table_prefix = $this->table_prefix_raw;
		if ($a == $our_table_prefix.'options') return -1;
		if ($b ==  $our_table_prefix.'options') return 1;
		if ($a == $our_table_prefix.'site') return -1;
		if ($b ==  $our_table_prefix.'site') return 1;
		if ($a == $our_table_prefix.'blogs') return -1;
		if ($b ==  $our_table_prefix.'blogs') return 1;
		if ($a == $our_table_prefix.'users') return -1;
		if ($b ==  $our_table_prefix.'users') return 1;
		if ($a == $our_table_prefix.'usermeta') return -1;
		if ($b ==  $our_table_prefix.'usermeta') return 1;

		if (empty($our_table_prefix)) return strcmp($a, $b);

		try {
			$core_tables = array_merge($this->wpdb_obj->tables, $this->wpdb_obj->global_tables, $this->wpdb_obj->ms_global_tables);
		} catch (Exception $e) {
			$iwp_backup_core->log($e->getMessage());
		}
		
		if (empty($core_tables)) $core_tables = array('terms', 'term_taxonomy', 'termmeta', 'term_relationships', 'commentmeta', 'comments', 'links', 'postmeta', 'posts', 'site', 'sitemeta', 'blogs', 'blogversions');

		global $iwp_backup_core;
		$na = $iwp_backup_core->str_replace_once($our_table_prefix, '', $a);
		$nb = $iwp_backup_core->str_replace_once($our_table_prefix, '', $b);
		if (in_array($na, $core_tables) && !in_array($nb, $core_tables)) return -1;
		if (!in_array($na, $core_tables) && in_array($nb, $core_tables)) return 1;
		return strcmp($a, $b);
	}

	private function log_account_space() {
		# Don't waste time if space is huge
		if (!empty($this->account_space_oodles)) return;
		global $iwp_backup_core;
		$hosting_bytes_free = $iwp_backup_core->get_hosting_disk_quota_free();
		if (is_array($hosting_bytes_free)) {
			$perc = round(100*$hosting_bytes_free[1]/(max($hosting_bytes_free[2], 1)), 1);
			$iwp_backup_core->log(sprintf('Free disk space in account: %s (%s used)', round($hosting_bytes_free[3]/1048576, 1)." MB", "$perc %"));
		}
	}

	// Returns the basename up to and including the nonce (but not the entity)
	private function get_backup_file_basename_from_time($use_time) {
		global $iwp_backup_core;
		$site_name = str_replace(array(
            "_",
            "/",
	    			"~"
        ), array(
            "",
            "-",
            "-"
        ), rtrim(remove_http(get_bloginfo('url')), "/"));
		return $site_name.'_backup_'.get_date_from_gmt(gmdate('Y-m-d H:i:s', $use_time), 'Y-m-d-Hi').'_'.$this->blog_name.'_'.$iwp_backup_core->nonce.'_'.$iwp_backup_core->get_wordpress_version();
	}

	private function find_existing_zips($dir, $match_nonce) {
		$zips = array();
		if ($handle = opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					if (preg_match('/^backup_(\d{4})-(\d{2})-(\d{2})-(\d{2})(\d{2})_.*_([0-9a-f]{12})-([\-a-z]+)([0-9]+)?\.zip$/i', $entry, $matches)) {
						if ($matches[6] !== $match_nonce) continue;
						$timestamp = mktime($matches[4], $matches[5], 0, $matches[2], $matches[3], $matches[1]);
						$entity = $matches[7];
						$index = empty($matches[8]) ? '0': $matches[8];
						$zips[$entity][$index] = array($timestamp, $entry);
					}
				}
			}
		}
		return $zips;
	}

	// $files should be an array as returned by find_existing_zips()
	private function file_exists($files, $entity, $index = 0) {
		if (isset($files[$entity]) && isset($files[$entity][$index])) {
			$file = $files[$entity][$index];
			// Return the filename
			return $file[1];
		} else {
			return false;
		}
	}

	// This function is resumable
	private function backup_dirs($job_status) {

		global $iwp_backup_core;

		if (!$iwp_backup_core->backup_time) $iwp_backup_core->backup_time_nonce();

		$use_time = apply_filters('IWP_backup_time_thisrun', $iwp_backup_core->backup_time);
		$backup_file_basename = $this->get_backup_file_basename_from_time($use_time);

		$backup_array = array();

		$possible_backups = $iwp_backup_core->get_backupable_file_entities(true);

		// Was there a check-in last time? If not, then reduce the amount of data attempted
		if ($job_status != 'finished' && $iwp_backup_core->current_resumption >= 2) {
			if (!empty($iwp_backup_core->no_checkin_last_time) || !$iwp_backup_core->newresumption_scheduled) {
				if ($iwp_backup_core->current_resumption - $iwp_backup_core->last_successful_resumption > 2 || !$iwp_backup_core->newresumption_scheduled) {
					$this->try_split = true;
				} elseif ($iwp_backup_core->current_resumption<=10) {
					$maxzipbatch = $iwp_backup_core->jobdata_get('maxzipbatch', 26214400);
					if ((int)$maxzipbatch < 1) $maxzipbatch = 26214400;

					$new_maxzipbatch = max(floor($maxzipbatch * 0.75), 20971520);
					if ($new_maxzipbatch < $maxzipbatch) {
						$iwp_backup_core->log("No check-in was detected on the previous run - as a result, we are reducing the batch amount (old=$maxzipbatch, new=$new_maxzipbatch)");
						$iwp_backup_core->jobdata_set('maxzipbatch', $new_maxzipbatch);
						$iwp_backup_core->jobdata_set('maxzipbatch_ceiling', $new_maxzipbatch);
					}
				}
			}
		}

		if($job_status != 'finished' && !$iwp_backup_core->really_is_writable($this->iwp_backup_dir)) {
			$iwp_backup_core->log("Backup directory (".$this->iwp_backup_dir.") is not writable, or does not exist");
			$iwp_backup_core->log(sprintf(__("Backup directory (%s) is not writable, or does not exist.", 'InfiniteWP'), $this->iwp_backup_dir), 'error');
			return array();
		}

		$this->job_file_entities = $iwp_backup_core->jobdata_get('job_file_entities');
		# This is just used for the visual feedback (via the 'substatus' key)
		$which_entity = 0;
		# e.g. plugins, themes, uploads, others
		# $whichdir might be an array (if $youwhat is 'more')

		# Returns an array (keyed off the entity) of ($timestamp, $filename) arrays
		$existing_zips = $this->find_existing_zips($this->iwp_backup_dir, $iwp_backup_core->nonce);

		foreach ($possible_backups as $youwhat => $whichdir) {

			if (isset($this->job_file_entities[$youwhat])) {

				$index = (int)$this->job_file_entities[$youwhat]['index'];
				if (empty($index)) $index=0;
				$indextext = (0 == $index) ? '' : (1+$index);

				$zip_file = $this->iwp_backup_dir.'/'.$backup_file_basename.'-'.$youwhat.$indextext.'.zip';

				# Split needed?
				$split_every = max((int)$iwp_backup_core->jobdata_get('split_every'), 250);
				//if (file_exists($zip_file) && filesize($zip_file) > $split_every*1048576) {
				if (false != ($existing_file = $this->file_exists($existing_zips, $youwhat, $index)) && filesize($this->iwp_backup_dir.'/'.$existing_file) > $split_every*1048576) {
					$index++;
					$this->job_file_entities[$youwhat]['index'] = $index;
					$iwp_backup_core->jobdata_set('job_file_entities', $this->job_file_entities);
				}

				// Populate prior parts of array, if we're on a subsequent zip file
				if ($index > 0) {
					for ($i=0; $i<$index; $i++) {
						$itext = (0 == $i) ? '' : ($i+1);
						// Get the previously-stored filename if possible (which should be always); failing that, base it on the current run

						$zip_file = (isset($this->backup_files_array[$youwhat]) && isset($this->backup_files_array[$youwhat][$i])) ? $this->backup_files_array[$youwhat][$i] : $backup_file_basename.'-'.$youwhat.$itext.'.zip';

						$backup_array[$youwhat][$i] = $zip_file;
						$z = $this->iwp_backup_dir.'/'.$zip_file;
						$itext = (0 == $i) ? '' : $i;

						$fs_key = $youwhat.$itext.'-size';
						if (file_exists($z)) {
							$backup_array[$fs_key] = filesize($z);
						} elseif (isset($this->backup_files_array[$fs_key])) {
							$backup_array[$fs_key] = $this->backup_files_array[$fs_key];
						}
					}
				}

				// I am not certain that all the conditions in here are possible. But there's no harm.
				if ('finished' == $job_status) {
					// Add the final part of the array
					if ($index > 0) {
						$zip_file = (isset($this->backup_files_array[$youwhat]) && isset($this->backup_files_array[$youwhat][$index])) ? $this->backup_files_array[$youwhat][$index] : $backup_file_basename.'-'.$youwhat.($index+1).'.zip';

						//$fbase = $backup_file_basename.'-'.$youwhat.($index+1).'.zip';
						$z = $this->iwp_backup_dir.'/'.$zip_file;
						$fs_key = $youwhat.$index.'-size';
						if (file_exists($z)) {
							$backup_array[$youwhat][$index] = $fbase;
							$backup_array[$fs_key] = filesize($z);
						} elseif (isset($this->backup_files_array[$fs_key])) {
							$backup_array[$youwhat][$index] = $fbase;
							$backup_array[$fs_key] = $this->backup_files_array[$fskey];
						}
					} else {
						$zip_file = (isset($this->backup_files_array[$youwhat]) && isset($this->backup_files_array[$youwhat][0])) ? $this->backup_files_array[$youwhat][0] : $backup_file_basename.'-'.$youwhat.'.zip';

						$backup_array[$youwhat] = $zip_file;
						$fs_key=$youwhat.'-size';

						if (file_exists($zip_file)) {
							$backup_array[$fs_key] = filesize($zip_file);
						} elseif (isset($this->backup_files_array[$fs_key])) {
							$backup_array[$fs_key] = $this->backup_files_array[$fs_key];
						}
					}
				} else {

					$which_entity++;
					$iwp_backup_core->jobdata_set('filecreating_substatus', array('e' => $youwhat, 'i' => $which_entity, 't' => count($this->job_file_entities)));

					if ('others' == $youwhat) $iwp_backup_core->log("Beginning backup of other directories found in the content directory (index: $index)");

					# Apply a filter to allow add-ons to provide their own method for creating a zip of the entity
					$created = apply_filters('IWP_backup_makezip_'.$youwhat, $whichdir, $backup_file_basename, $index);
					# If the filter did not lead to something being created, then use the default method
					if ($created === $whichdir) {

						// http://www.phpconcept.net/pclzip/user-guide/53
						/* First parameter to create is:
							An array of filenames or dirnames,
							or
							A string containing the filename or a dirname,
							or
							A string containing a list of filename or dirname separated by a comma.
						*/

						if ('others' == $youwhat) {
							$dirlist = $iwp_backup_core->backup_others_dirlist(true);
						} elseif ('uploads' == $youwhat) {
							$dirlist = $iwp_backup_core->backup_uploads_dirlist(true);
						} elseif ('more' == $youwhat) {
							$dirlist = $iwp_backup_core->backup_more_dirlist($whichdir);
						}else {
							$dirlist = $whichdir;
							if (is_array($dirlist)) $dirlist=array_shift($dirlist);
						}

						if (!empty($dirlist)) {
							$created = $this->create_zip($dirlist, $youwhat, $backup_file_basename, $index);
							# Now, store the results
							if (!is_string($created) && !is_array($created)) $iwp_backup_core->log("$youwhat: create_zip returned an error");
						} else {
							$iwp_backup_core->log("No backup of $youwhat: there was nothing found to back up");
						}
					}

					if ($created != $whichdir && (is_string($created) || is_array($created))) {
						if (is_string($created)) $created=array($created);
						foreach ($created as $findex => $fname) {
							$backup_array[$youwhat][$index] = $fname;
							$itext = ($index == 0) ? '' : $index;
							$index++;
							$backup_array[$youwhat.$itext.'-size'] = filesize($this->iwp_backup_dir.'/'.$fname);
						}
					}

					$this->job_file_entities[$youwhat]['index'] = $this->index;
					$iwp_backup_core->jobdata_set('job_file_entities', $this->job_file_entities);

				}
			} else {
				$iwp_backup_core->log("No backup of $youwhat: excluded by user's options");
			}
		}

		return $backup_array;
	}

	// This uses a saved status indicator; its only purpose is to indicate *total* completion; there is no actual danger, just wasted time, in resuming when it was not needed. So the saved status indicator just helps save resources.
	public function resumable_backup_of_files($resumption_no) {
		global $iwp_backup_core;
		// Backup directories and return a numerically indexed array of file paths to the backup files
		$bfiles_status = $iwp_backup_core->jobdata_get('backup_files');
		$this->backup_files_array = $iwp_backup_core->jobdata_get('backup_files_array');;
		if (!is_array($this->backup_files_array)) $this->backup_files_array = array();
		if ('finished' == $bfiles_status) {
			$iwp_backup_core->log("Creation of backups of directories: already finished");
			# Check for recent activity
			foreach ($this->backup_files_array as $files) {
				if (!is_array($files)) $files=array($files);
				foreach ($files as $file) $iwp_backup_core->check_recent_modification($this->iwp_backup_dir.'/'.$file);
			}
		} elseif ('begun' == $bfiles_status) {
			$this->first_run = apply_filters('IWP_filerun_firstrun', 0);
			if ($resumption_no > $this->first_run) {
				$iwp_backup_core->log("Creation of backups of directories: had begun; will resume");
			} else {
				$iwp_backup_core->log("Creation of backups of directories: beginning");
			}
			$iwp_backup_core->jobdata_set('jobstatus', 'filescreating');
			$this->backup_files_array = $this->backup_dirs($bfiles_status);
			$iwp_backup_core->jobdata_set('backup_files_array', $this->backup_files_array);
			$iwp_backup_core->jobdata_set('backup_files', 'finished');
			$iwp_backup_core->jobdata_set('jobstatus', 'filescreated');
		} else {
			# This is not necessarily a backup run which is meant to contain files at all
			$iwp_backup_core->log('This backup run is not intended for files - skipping');
			return array();
		}
		return $this->backup_files_array;
	}

	/* This function is resumable, using the following method:
	- Each table is written out to ($final_filename).table.tmp
	- When the writing finishes, it is renamed to ($final_filename).table
	- When all tables are finished, they are concatenated into the final file
	*/
	// dbinfo is only used when whichdb != 'wp'; and the keys should be: user, pass, name, host, prefix
	public function backup_db($already_done = 'begun', $whichdb = 'wp', $dbinfo = array()) {

		global $iwp_backup_core, $wpdb;

		$this->whichdb = $whichdb;
		$this->whichdb_suffix = ('wp' == $whichdb) ? '' : $whichdb;

		if (!$iwp_backup_core->backup_time) $iwp_backup_core->backup_time_nonce();
		if (!$iwp_backup_core->opened_log_time) $iwp_backup_core->logfile_open($iwp_backup_core->nonce);

		if ('wp' == $this->whichdb) {
			$this->wpdb_obj = $wpdb;
			# The table prefix after being filtered - i.e. what filters what we'll actually back up
			$this->table_prefix = $iwp_backup_core->get_table_prefix(true);
			# The unfiltered table prefix - i.e. the real prefix that things are relative to
			$this->table_prefix_raw = $iwp_backup_core->get_table_prefix(false);
			$dbinfo['host'] = DB_HOST;
			$dbinfo['name'] = DB_NAME;
			$dbinfo['user'] = DB_USER;
			$dbinfo['pass'] = DB_PASSWORD;
		} else {
			if (!is_array($dbinfo) || empty($dbinfo['host'])) return false;
			# The methods that we may use: check_connection (WP>=3.9), get_results, get_row, query
			$this->wpdb_obj = new IWP_MMB_WPDB_OtherDB($dbinfo['user'], $dbinfo['pass'], $dbinfo['name'], $dbinfo['host']);
			if (!empty($this->wpdb_obj->error)) {
				$iwp_backup_core->log($dbinfo['user'].'@'.$dbinfo['host'].'/'.$dbinfo['name'].' : database connection attempt failed');
				$iwp_backup_core->log($dbinfo['user'].'@'.$dbinfo['host'].'/'.$dbinfo['name'].' : '.__('database connection attempt failed.', 'iwp_backup_core').' '.__('Connection failed: check your access details, that the database server is up, and that the network connection is not firewalled.', 'iwp_backup_core'), 'error');
				return $iwp_backup_core->log_wp_error($this->wpdb_obj->error);
			}
			$this->table_prefix = $dbinfo['prefix'];
			$this->table_prefix_raw = $dbinfo['prefix'];
		}

		$this->dbinfo = $dbinfo;

		$errors = 0;

		$use_time = apply_filters('IWP_backup_time_thisrun', $iwp_backup_core->backup_time);
		$file_base = $this->get_backup_file_basename_from_time($use_time);
		$backup_file_base = $this->iwp_backup_dir.'/'.$file_base;

		if ('finished' == $already_done) return basename($backup_file_base).'-db'.(('wp' == $whichdb) ? '' : $whichdb).'.gz';
		if ('encrypted' == $already_done) return basename($backup_file_base).'-db'.(('wp' == $whichdb) ? '' : $whichdb).'.gz.crypt';

		$iwp_backup_core->jobdata_set('jobstatus', 'dbcreating'.$this->whichdb_suffix);

		$binsqldump = $iwp_backup_core->find_working_sqldump();

		$total_tables = 0;

		# WP 3.9 onwards - https://core.trac.wordpress.org/browser/trunk/src/wp-includes/wp-db.php?rev=27925 - check_connection() allows us to get the database connection back if it had dropped
		if ('wp' == $whichdb && method_exists($this->wpdb_obj, 'check_connection') && (!defined('IWP_SUPPRESS_CONNECTION_CHECKS') || !IWP_SUPPRESS_CONNECTION_CHECKS)) {
			if (!$this->wpdb_obj->check_connection(false)) {
				$iwp_backup_core->reschedule(60);
				$iwp_backup_core->log("It seems the database went away; scheduling a resumption and terminating for now");
				$iwp_backup_core->record_still_alive();
				die;
			}
		}

		// SHOW FULL - so that we get to know whether it's a BASE TABLE or a VIEW
		$all_tables = $this->wpdb_obj->get_results("SHOW FULL TABLES", ARRAY_N);
		
		if (empty($all_tables) && !empty($this->wpdb_obj->last_error)) {
			$all_tables = $this->wpdb_obj->get_results("SHOW TABLES", ARRAY_N);
			$all_tables = array_map(array($this, 'cb_get_name_base_type'), $all_tables);
		} else {
			$all_tables = array_map(array($this, 'cb_get_name_type'), $all_tables);
		}

		# If this is not the WP database, then we do not consider it a fatal error if there are no tables
		if ('wp' == $whichdb && 0 == count($all_tables)) {
			$extra = ($iwp_backup_core->newresumption_scheduled) ? ' - '.__('please wait for the rescheduled attempt', 'InfiniteWP') : '';
			$iwp_backup_core->log("Error: No WordPress database tables found (SHOW TABLES returned nothing)".$extra);
			$iwp_backup_core->log(__("No database tables found", 'InfiniteWP').$extra, 'error');
			die;
		}

		// Put the options table first
		usort($all_tables, array($this, 'backup_db_sorttables'));
		
		$all_table_names = array_map(array($this, 'cb_get_name'), $all_tables);

		if (!$iwp_backup_core->really_is_writable($this->iwp_backup_dir)) {
			$iwp_backup_core->log("The backup directory (".$this->iwp_backup_dir.") could not be written to (could be account/disk space full, or wrong permissions).");
			$iwp_backup_core->log($this->iwp_backup_dir.": ".__('The backup directory is not writable (or disk space is full) - the database backup is expected to shortly fail.','InfiniteWP'), 'warning');
			# Why not just fail now? We saw a bizarre case when the results of really_is_writable() changed during the run.
		}

		# This check doesn't strictly get all possible duplicates; it's only designed for the case that can happen when moving between deprecated Windows setups and Linux
		$this->duplicate_tables_exist = false;
		foreach ($all_table_names as $table) {
			if (strtolower($table) != $table && in_array(strtolower($table), $all_table_names)) {
				$this->duplicate_tables_exist = true;
				$iwp_backup_core->log("Tables with names differing only based on case-sensitivity exist in the MySQL database: $table / ".strtolower($table));
			}
		}
		$how_many_tables = count($all_tables);

		$stitch_files = array();
		$found_options_table = false;
		$is_multisite = is_multisite();

		foreach ($all_tables as $ti) {

			$table = $ti['name'];
			$table_type = $ti['type'];
		
			$manyrows_warning = false;
			$total_tables++;

			// Increase script execution time-limit to 15 min for every table.
			@set_time_limit(IWP_SET_TIME_LIMIT);
			// The table file may already exist if we have produced it on a previous run
			$table_file_prefix = $file_base.'-db'.$this->whichdb_suffix.'-table-'.$table.'.table';

			if ('wp' == $whichdb && (strtolower($this->table_prefix_raw.'options') == strtolower($table) || ($is_multisite && (strtolower($this->table_prefix_raw.'sitemeta') == strtolower($table) || strtolower($this->table_prefix_raw.'1_options') == strtolower($table))))) $found_options_table = true;

			if (file_exists($this->iwp_backup_dir.'/'.$table_file_prefix.'.gz')) {
				$stitched = count($stitch_files);
				$skip_dblog = (($stitched > 10 && 0 != $stitched % 20) || ($stitched > 100 && 0 != $stitched % 100));
				$iwp_backup_core->log("Table $table: corresponding file already exists; moving on", 'notice', false, $skip_dblog);
				$stitch_files[] = $table_file_prefix;
			} else {
				# === is needed, otherwise 'false' matches (i.e. prefix does not match)
				if (empty($this->table_prefix) || ($this->duplicate_tables_exist == false && stripos($table, $this->table_prefix) === 0 ) || ($this->duplicate_tables_exist == true && strpos($table, $this->table_prefix) === 0 ) ) {

					if (!apply_filters('IWP_backup_table', true, $table, $this->table_prefix, $whichdb, $dbinfo)) {
						$iwp_backup_core->log("Skipping table (filtered): $table");
						if (empty($this->skipped_tables)) $this->skipped_tables = array();

						// whichdb could be an int in which case to get the name of the database and the array key use the name from dbinfo
						if ('wp' !== $whichdb) {
							$key = $dbinfo['name'];
						} else {
							$key = $whichdb;
						}

						if (empty($this->skipped_tables[$key])) $this->skipped_tables[$key] = '';
						if ('' != $this->skipped_tables[$key]) $this->skipped_tables[$key] .= ',';
						$this->skipped_tables[$key] .= $table;

						$total_tables--;
					} else {

						$db_temp_file = $this->iwp_backup_dir.'/'.$table_file_prefix.'.tmp.gz';
						$iwp_backup_core->check_recent_modification($db_temp_file);
					
						// Open file, store the handle
						$opened = $this->backup_db_open($db_temp_file, true);
						if (false === $opened) return false;

						// Create the SQL statements
						$this->stow("# " . sprintf('Table: %s' ,$iwp_backup_core->backquote($table)) . "\n");
						$iwp_backup_core->jobdata_set('dbcreating_substatus', array('t' => $table, 'i' => $total_tables, 'a' => $how_many_tables));

						$table_status = $this->wpdb_obj->get_row("SHOW TABLE STATUS WHERE Name='$table'");
						if (isset($table_status->Rows)) {
							$rows = $table_status->Rows;
							$iwp_backup_core->log("Table $table: Total expected rows (approximate): ".$rows);
							$this->stow("# Approximate rows expected in table: $rows\n");
							if ($rows > IWP_WARN_DB_ROWS) {
								$manyrows_warning = true;
								$iwp_backup_core->log(sprintf(__("Table %s has very many rows (%s) - we hope your web hosting company gives you enough resources to dump out that table in the backup", 'InfiniteWP'), $table, $rows).' '.__('If not, you will need to either remove data from this table, or contact your hosting company to request more resources.', 'InfiniteWP'), 'warning', 'manyrows_'.$this->whichdb_suffix.$table);
							}
						}

						# Don't include the job data for any backups - so that when the database is restored, it doesn't continue an apparently incomplete backup
						if  ('wp' == $this->whichdb && (!empty($this->table_prefix) && strtolower($this->table_prefix.'sitemeta') == strtolower($table))) {
							$where = 'meta_key NOT LIKE "IWP_jobdata_%"';
						} elseif ('wp' == $this->whichdb && (!empty($this->table_prefix) && strtolower($this->table_prefix.'options') == strtolower($table))) {
							if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
								$IWP_jobdata = "'IWP_jobdata_%'";
								$site_transient_update = "'_site_transient_update_%'";
							} else {
								$IWP_jobdata = '"IWP_jobdata_%"';
								$site_transient_update = '"_site_transient_update_%"';
							}
							
							$where = 'option_name NOT LIKE '.$IWP_jobdata.' AND option_name NOT LIKE '.$site_transient_update.'';
						} else {
							$where = '';
						}

						# If no check-in last time, then we could in future try the other method (but - any point in retrying slow method on large tables??)

						# New Jul 2014: This attempt to use bindump instead at a lower threshold is quite conservative - only if the last successful run was exactly two resumptions ago - may be useful to expand
						$bindump_threshold = (!$iwp_backup_core->something_useful_happened && !empty($iwp_backup_core->current_resumption) && ($iwp_backup_core->current_resumption - $iwp_backup_core->last_successful_resumption == 2 )) ? 1000 : 8000;

						$bindump = (isset($rows) && ($rows>$bindump_threshold || (defined('IWP_ALWAYS_TRY_MYSQLDUMP') && IWP_ALWAYS_TRY_MYSQLDUMP)) && is_string($binsqldump)) ? $this->backup_table_bindump($binsqldump, $table, $where) : false;
						if (true !== $bindump) $this->backup_table($table, $where, 'none', $table_type);

						if (!empty($manyrows_warning)) $iwp_backup_core->log_removewarning('manyrows_'.$this->whichdb_suffix.$table);

						$this->close();

						$iwp_backup_core->log("Table $table: finishing file (${table_file_prefix}.gz - ".round(filesize($this->iwp_backup_dir.'/'.$table_file_prefix.'.tmp.gz')/1024,1)." KB)");

						rename($db_temp_file, $this->iwp_backup_dir.'/'.$table_file_prefix.'.gz');
						$iwp_backup_core->something_useful_happened();
						$stitch_files[] = $table_file_prefix;
					}
				} else {
					$total_tables--;
					$iwp_backup_core->log("Skipping table (lacks our prefix (".$this->table_prefix.")): $table");
				}
				
			}
		}

		if ('wp' == $whichdb) {
			if (!$found_options_table) {
				if ($is_multisite) {
					$iwp_backup_core->log(__('The database backup appears to have failed', 'InfiniteWP').' - '.__('no options or sitemeta table was found', 'InfiniteWP'), 'warning', 'optstablenotfound');
				} else {
					$iwp_backup_core->log(__('The database backup appears to have failed', 'InfiniteWP').' - '.__('the options table was not found', 'InfiniteWP'), 'warning', 'optstablenotfound');
				}
				$time_this_run = time()-$IWP_backup->opened_log_time;
				if ($time_this_run > 2000) {
					# Have seen this happen; not sure how, but it was apparently deterministic; if the current process had been running for a long time, then apparently all database commands silently failed.
					# If we have been running that long, then the resumption may be far off; bring it closer
					$iwp_backup_core->reschedule(60);
					$iwp_backup_core->log("Have been running very long, and it seems the database went away; scheduling a resumption and terminating for now");
					$iwp_backup_core->record_still_alive();
					die;
				}
			} else {
				$iwp_backup_core->log_removewarning('optstablenotfound');
			}
		}

		// Race detection - with zip files now being resumable, these can more easily occur, with two running side-by-side
		$backup_final_file_name = $backup_file_base.'-db'.$this->whichdb_suffix.'.gz';
		$time_now = time();
		$time_mod = (int)@filemtime($backup_final_file_name);
		if (file_exists($backup_final_file_name) && $time_mod>100 && ($time_now-$time_mod)<30) {
			$iwp_backup_core->terminate_due_to_activity($backup_final_file_name, $time_now, $time_mod);
		}
		if (file_exists($backup_final_file_name)) {
			$iwp_backup_core->log("The final database file ($backup_final_file_name) exists, but was apparently not modified within the last 30 seconds (time_mod=$time_mod, time_now=$time_now, diff=".($time_now-$time_mod)."). Thus we assume that another Backup terminated; thus we will continue.");
		}

		// Finally, stitch the files together
		if (!function_exists('gzopen')) {
			if (function_exists('gzopen64')) {
				$iwp_backup_core->log("PHP function is disabled; abort expected: gzopen - buggy Ubuntu PHP version; try this plugin to help: https://wordpress.org/plugins/wp-ubuntu-gzopen-fix/");
			} else {
				$iwp_backup_core->log("PHP function is disabled; abort expected: gzopen");
			}
		}

		if (false === $this->backup_db_open($backup_final_file_name, true)) return false;

		$this->backup_db_header();

		// We delay the unlinking because if two runs go concurrently and fail to detect each other (should not happen, but there's no harm in assuming the detection failed) then that leads to files missing from the db dump
		$unlink_files = array();

		$sind = 1;
		foreach ($stitch_files as $table_file) {
			$iwp_backup_core->log("{$table_file}.gz ($sind/$how_many_tables): adding to final database dump");
			if (!$handle = gzopen($this->iwp_backup_dir.'/'.$table_file.'.gz', "r")) {
				$iwp_backup_core->log("Error: Failed to open database file for reading: ${table_file}.gz");
				$iwp_backup_core->log(__("Failed to open database file for reading:", 'InfiniteWP').' '.$table_file.'.gz', 'error');
				$errors++;
			} else {
				while ($line = gzgets($handle, 65536)) { $this->stow($line); }
				gzclose($handle);
				$unlink_files[] = $this->iwp_backup_dir.'/'.$table_file.'.gz';
			}
			$sind++;
			// Came across a database with 7600 tables... adding them all took over 500 seconds; and so when the resumption started up, no activity was detected
			if ($sind % 100 == 0) $iwp_backup_core->something_useful_happened();
		}

		if (@constant("DB_CHARSET")) {
			$this->stow("/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");
		}

		$iwp_backup_core->log($file_base.'-db'.$this->whichdb_suffix.'.gz: finished writing out complete database file ('.round(filesize($backup_final_file_name)/1024,1).' KB)');
		if (!$this->close()) {
			$iwp_backup_core->log('An error occurred whilst closing the final database file');
			$iwp_backup_core->log(__('An error occurred whilst closing the final database file', 'InfiniteWP'), 'error');
			$errors++;
		}

		foreach ($unlink_files as $unlink_file) @unlink($unlink_file);

		if ($errors > 0) {
			return false;
		} else {
			# We no longer encrypt here - because the operation can take long, we made it resumable and moved it to the upload loop
			$iwp_backup_core->jobdata_set('jobstatus', 'dbcreated'.$this->whichdb_suffix);
			
			$checksums = $iwp_backup_core->which_checksums();
			
			$checksum_description = '';
			
			foreach ($checksums as $checksum) {
			
				$cksum = hash_file($checksum, $backup_final_file_name);
				$iwp_backup_core->jobdata_set($checksum.'-db'.(('wp' == $whichdb) ? '0' : $whichdb.'0'), $cksum);
				if ($checksum_description) $checksum_description .= ', ';
				$checksum_description .= "$checksum: $cksum";
			
			}
			
			$iwp_backup_core->log("Total database tables backed up: $total_tables (".basename($backup_final_file_name).", size: ".filesize($backup_final_file_name).", $checksum)");
			
			return basename($backup_final_file_name);
		}

	}

	private function backup_table_bindump($potsql, $table_name, $where) {

		$microtime = microtime(true);

		global $iwp_backup_core;
		$table_sans_prefix = substr($table_name, strlen($this->table_prefix_raw));
		$default_exclude_tables = explode(',', IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_default_exclude_tables', IWP_DATA_OPTIONAL_TABLES));
		if (in_array($table_sans_prefix, $default_exclude_tables)) {
			$iwp_backup_core->log("Table $table_name: Data skipped (table is marked as non-essential)");
			return true;
		}

		// Deal with Windows/old MySQL setups with erroneous table prefixes differing in case
		// Can't get binary mysqldump to make this transformation
// 		$dump_as_table = ($this->duplicate_tables_exist == false && stripos($table, $this->table_prefix) === 0 && strpos($table, $this->table_prefix) !== 0) ? $this->table_prefix.substr($table, strlen($this->table_prefix)) : $table;

		$pfile = md5(time().rand()).'.tmp';
		file_put_contents($this->iwp_backup_dir.'/'.$pfile, "[mysqldump]\npassword=".$this->dbinfo['pass']."\n");

		// Note: escapeshellarg() adds quotes around the string
		if ($where) $where="--where=".escapeshellarg($where);

		if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
			$exec = "cd ".escapeshellarg(str_replace('/', '\\', $this->iwp_backup_dir))." & ";
		} else {
			$exec = "cd ".escapeshellarg($this->iwp_backup_dir)."; ";
		}

		$exec .= "$potsql  --defaults-file=$pfile $where --max_allowed_packet=1M --quote-names --add-drop-table --skip-comments --skip-set-charset --allow-keywords --dump-date --extended-insert --user=".escapeshellarg($this->dbinfo['user'])." --host=".escapeshellarg($this->dbinfo['host'])." ".$this->dbinfo['name']." ".escapeshellarg($table_name);
		
		$ret = false;
		$any_output = false;
		$writes = 0;
		$handle = popen($exec, "r");
		if ($handle) {
			while (!feof($handle)) {
				$w = fgets($handle);
				if ($w) {
					$this->stow($w);
					$writes++;
					$any_output = true;
				}
			}
			$ret = pclose($handle);
			if ($ret != 0) {
				$iwp_backup_core->log("Binary mysqldump: error (code: $ret)");
				// Keep counter of failures? Change value of binsqldump?
			} else {
				if ($any_output) {
					$iwp_backup_core->log("Table $table_name: binary mysqldump finished (writes: $writes) in ".sprintf("%.02f",max(microtime(true)-$microtime,0.00001))." seconds");
					$ret = true;
				}
			}
		} else {
			$iwp_backup_core->log("Binary mysqldump error: bindump popen failed");
		}

		# Clean temporary files
		@unlink($this->iwp_backup_dir.'/'.$pfile);

		return $ret;

	}

	/**
	 * Taken partially from phpMyAdmin and partially from
	 * Alain Wolf, Zurich - Switzerland
	 * Website: http://restkultur.ch/personal/wolf/scripts/db_backup/
	 * Modified by Scott Merrill (http://www.skippy.net/) 
	 * to use the WordPress $wpdb object
	 * @param string $table
	 * @param string $segment
	 * @return void
	 */
	private function backup_table($table, $where = '', $segment = 'none', $table_type = 'BASE TABLE') {
		global $iwp_backup_core;

		$microtime = microtime(true);
		$total_rows = 0;

		// Deal with Windows/old MySQL setups with erroneous table prefixes differing in case
		$dump_as_table = ($this->duplicate_tables_exist == false && stripos($table, $this->table_prefix) === 0 && strpos($table, $this->table_prefix) !== 0) ? $this->table_prefix.substr($table, strlen($this->table_prefix)) : $table;

		$table_structure = $this->wpdb_obj->get_results("DESCRIBE ".$iwp_backup_core->backquote($table));
		if (! $table_structure) {
			return false;
		}
	
		if($segment == 'none' || $segment == 0) {
			// Add SQL statement to drop existing table
			$this->stow("\n# Delete any existing table ".$iwp_backup_core->backquote($table)."\n\n");
			$this->stow("DROP TABLE IF EXISTS " . $iwp_backup_core->backquote($dump_as_table) . ";\n");
			
			if ('VIEW' == $table_type) {
				$this->stow("DROP VIEW IF EXISTS " . $iwp_backup_core->backquote($dump_as_table) . ";\n");
			}
			
			// Table structure
			// Comment in SQL-file
			
			$description = ('VIEW' == $table_type) ? 'view' : 'table';
			
			$this->stow("\n# Table structure of $description ".$iwp_backup_core->backquote($table)."\n\n");
			
			$create_table = $this->wpdb_obj->get_results("SHOW CREATE TABLE ".$iwp_backup_core->backquote($table), ARRAY_N);
			if (false === $create_table) {
				$err_msg ='Error with SHOW CREATE TABLE for '.$table;
				//$iwp_backup_core->log($err_msg, 'error');
				$this->stow("#\n# $err_msg\n#\n");
			}
			$create_line = $iwp_backup_core->str_lreplace('TYPE=', 'ENGINE=', $create_table[0][1]);

			# Remove PAGE_CHECKSUM parameter from MyISAM - was internal, undocumented, later removed (so causes errors on import)
			if (preg_match('/ENGINE=([^\s;]+)/', $create_line, $eng_match)) {
				$engine = $eng_match[1];
				if ('myisam' == strtolower($engine)) {
					$create_line = preg_replace('/PAGE_CHECKSUM=\d\s?/', '', $create_line, 1);
				}
			}

			if ($dump_as_table !== $table) $create_line = $iwp_backup_core->str_replace_once($table, $dump_as_table, $create_line);

			$this->stow($create_line.' ;');
			
			if (false === $table_structure) {
				$err_msg = sprintf("Error getting $description structure of %s", $table);
				$this->stow("#\n# $err_msg\n#\n");
			}
		
			// Comment in SQL-file
			$this->stow("\n\n# " . sprintf("Data contents of $description %s", $iwp_backup_core->backquote($table)) . "\n\n");

		}

		# Some tables have optional data, and should be skipped if they do not work
		$table_sans_prefix = substr($table, strlen($this->table_prefix_raw));
		$data_optional_tables = ('wp' == $this->whichdb) ? explode(',', IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_default_exclude_tables', IWP_DATA_OPTIONAL_TABLES)) : array();
		if (in_array($table_sans_prefix, $data_optional_tables)) {
			//if (!$iwp_backup_core->something_useful_happened && !empty($iwp_backup_core->current_resumption) && ($iwp_backup_core->current_resumption - $iwp_backup_core->last_successful_resumption > 2)) {
				// $iwp_backup_core->log("Table $table: Data skipped (previous attempts failed, and table is marked as non-essential)");
				$iwp_backup_core->log("Table $table: Data skipped (table is marked as non-essential)");
				return true;
			//}
		}

		if('VIEW' != $table_type && ($segment == 'none' || $segment >= 0)) {
			$defs = array();
			$integer_fields = array();
			// $table_structure was from "DESCRIBE $table"
			foreach ($table_structure as $struct) {
				if ( (0 === strpos($struct->Type, 'tinyint')) || (0 === strpos(strtolower($struct->Type), 'smallint')) ||
					(0 === strpos(strtolower($struct->Type), 'mediumint')) || (0 === strpos(strtolower($struct->Type), 'int')) || (0 === strpos(strtolower($struct->Type), 'bigint')) ) {
						$defs[strtolower($struct->Field)] = ( null === $struct->Default ) ? 'NULL' : $struct->Default;
						$integer_fields[strtolower($struct->Field)] = "1";
				}
			}

			// Experimentation here shows that on large tables (we tested with 180,000 rows) on MyISAM, 1000 makes the table dump out 3x faster than the previous value of 100. After that, the benefit diminishes (increasing to 4000 only saved another 12%)

			$increment = 1000;
			if (!$iwp_backup_core->something_useful_happened && !empty($iwp_backup_core->current_resumption) && ($iwp_backup_core->current_resumption - $iwp_backup_core->last_successful_resumption > 1)) {
				# This used to be fixed at 500; but we (after a long time) saw a case that looked like an out-of-memory even at this level. We must be careful about going too low, though - otherwise we increase the risks of timeouts.
				$increment = ( $iwp_backup_core->current_resumption - $iwp_backup_core->last_successful_resumption > 2 ) ? 350 : 500;
			}

			if($segment == 'none') {
				$row_start = 0;
				$row_inc = $increment;
			} else {
				$row_start = $segment * $increment;
				$row_inc = $increment;
			}

			$search = array("\x00", "\x0a", "\x0d", "\x1a");
			$replace = array('\0', '\n', '\r', '\Z');

			if ($where) $where = "WHERE $where";

			do {
				@set_time_limit(IWP_SET_TIME_LIMIT);

				$table_data = $this->wpdb_obj->get_results("SELECT * FROM ".$iwp_backup_core->backquote($table)." $where LIMIT {$row_start}, {$row_inc}", ARRAY_A);
				$entries = 'INSERT INTO ' . $iwp_backup_core->backquote($dump_as_table) . ' VALUES ';
				//    \x08\\x09, not required
				if($table_data) {
					$thisentry = "";
					foreach ($table_data as $row) {
						$total_rows++;
						$values = array();
						foreach ($row as $key => $value) {
							if (isset($integer_fields[strtolower($key)])) {
								// make sure there are no blank spots in the insert syntax,
								// yet try to avoid quotation marks around integers
								$value = ( null === $value || '' === $value) ? $defs[strtolower($key)] : $value;
								$values[] = ( '' === $value ) ? "''" : $value;
							} else {
								$values[] = (null === $value) ? 'NULL' : "'" . str_replace($search, $replace, str_replace('\'', '\\\'', str_replace('\\', '\\\\', $value))) . "'";
							}
						}
						if ($thisentry) $thisentry .= ", ";
						$thisentry .= '('.implode(', ', $values).')';
						// Flush every 512KB
						if (strlen($thisentry) > 524288) {
							$this->stow(" \n".$entries.$thisentry.';');
							$thisentry = "";
						}
						
					}
					if ($thisentry) $this->stow(" \n".$entries.$thisentry.';');
					$row_start += $row_inc;
				}
			} while(count($table_data) > 0 && 'none' == $segment);
		}
		
		if(($segment == 'none') || ($segment < 0)) {
			// Create footer/closing comment in SQL-file
			$this->stow("\n");
			$this->stow("# End of data contents of table ".$iwp_backup_core->backquote($table) . "\n");
			$this->stow("\n");
		}
 		$iwp_backup_core->log("Table $table: Total rows added: $total_rows in ".sprintf("%.02f",max(microtime(true)-$microtime,0.00001))." seconds");

	} // end backup_table()


	/*END OF WP-DB-BACKUP BLOCK */

	// Encrypts the file if the option is set; returns the basename of the file (according to whether it was encrypted or nto)
	public function encrypt_file($file) {
		global $iwp_backup_core;
		$encryption = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_encryptionphrase');
		if (strlen($encryption) > 0) {
			$iwp_backup_core->log("Attempting to encrypt backup file");
			try {
				$result = apply_filters('IWP_encrypt_file', null, $file, $encryption, $this->whichdb, $this->whichdb_suffix);
			} catch (Exception $e) {
				$log_message = 'Exception ('.get_class($e).') occurred during encryption: '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
				error_log($log_message);
				// @codingStandardsIgnoreLine
				if (function_exists('wp_debug_backtrace_summary')) $log_message .= ' Backtrace: '.wp_debug_backtrace_summary();
				$iwp_backup_core->log($log_message);
				$iwp_backup_core->log(sprintf(__('A PHP exception (%s) has occurred: %s', 'InfiniteWP'), get_class($e), $e->getMessage()), 'error');
				die();
			// @codingStandardsIgnoreLine
			} catch (Error $e) {
				$log_message = 'PHP Fatal error ('.get_class($e).') has occurred during encryption. Error Message: '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
				error_log($log_message);
				// @codingStandardsIgnoreLine
				if (function_exists('wp_debug_backtrace_summary')) $log_message .= ' Backtrace: '.wp_debug_backtrace_summary();
				$iwp_backup_core->log($log_message);
				$iwp_backup_core->log(sprintf(__('A PHP fatal error (%s) has occurred: %s', 'InfiniteWP'), get_class($e), $e->getMessage()), 'error');
				die();
			}
			if (null === $result) {
				return basename($file);
			}
			return $result;
		} else {
			return basename($file);
		}
	}

	public function close() {
		return ($this->dbhandle_isgz) ? gzclose($this->dbhandle) : fclose($this->dbhandle);
	}

	// Open a file, store its filehandle
	public function backup_db_open($file, $allow_gz = true) {
		if (function_exists('gzopen') && $allow_gz == true) {
			$this->dbhandle = @gzopen($file, 'w');
			$this->dbhandle_isgz = true;
		} else {
			$this->dbhandle = @fopen($file, 'w');
			$this->dbhandle_isgz = false;
		}
		if(false === $this->dbhandle) {
			global $iwp_backup_core;
			$iwp_backup_core->log("ERROR: $file: Could not open the backup file for writing");
			$iwp_backup_core->log($file.": ".__("Could not open the backup file for writing",'InfiniteWP'), 'error');
		}
		return $this->dbhandle;
	}

	public function stow($query_line) {
		if ($this->dbhandle_isgz) {
			if(false == ($ret = @gzwrite($this->dbhandle, $query_line))) {
				//$iwp_backup_core->log(__('There was an error writing a line to the backup script:','wp-db-backup') . '  ' . $query_line . '  ' . $php_errormsg, 'error');
			}
		} else {
			if(false == ($ret = @fwrite($this->dbhandle, $query_line))) {
				//$iwp_backup_core->log(__('There was an error writing a line to the backup script:','wp-db-backup') . '  ' . $query_line . '  ' . $php_errormsg, 'error');
			}
		}
		return $ret;
	}

	private function backup_db_header() {

		@include(ABSPATH.WPINC.'/version.php');
		global $wp_version, $iwp_backup_core;

		$mysql_version = $this->wpdb_obj->db_version();
		# (function_exists('mysql_get_server_info')) ? @mysql_get_server_info() : '?';

		if ('wp' == $this->whichdb) {
			$wp_upload_dir = wp_upload_dir();
			$this->stow("# WordPress MySQL database backup\n");
			$this->stow("# Created by InfiniteWP version ".$iwp_backup_core->version."\n");
			$this->stow("# WordPress Version: $wp_version, running on PHP ".phpversion()." (".$_SERVER["SERVER_SOFTWARE"]."), MySQL $mysql_version\n");
			$this->stow("# Backup of: ".untrailingslashit(site_url())."\n");
			$this->stow("# Home URL: ".untrailingslashit(home_url())."\n");
			$this->stow("# Content URL: ".untrailingslashit(content_url())."\n");
			$this->stow("# Uploads URL: ".untrailingslashit($wp_upload_dir['baseurl'])."\n");
			$this->stow("# Table prefix: ".$this->table_prefix_raw."\n");
			$this->stow("# Filtered table prefix: ".$this->table_prefix."\n");
			$this->stow("# Site info: multisite=".(is_multisite() ? '1' : '0')."\n");
			$this->stow("# Site info: end\n");
		} else {
			$this->stow("# MySQL database backup (supplementary database ".$this->whichdb.")\n");
			$this->stow("# Created by InfiniteWP version ".$iwp_backup_core->version."\n");
			$this->stow("# WordPress Version: $wp_version, running on PHP ".phpversion()." (".$_SERVER["SERVER_SOFTWARE"]."), MySQL $mysql_version\n");
			$this->stow("# ".sprintf('External database: (%s)', $this->dbinfo['user'].'@'.$this->dbinfo['host'].'/'.$this->dbinfo['name'])."\n");
			$this->stow("# Backup created by: ".untrailingslashit(site_url())."\n");
			$this->stow("# Table prefix: ".$this->table_prefix_raw."\n");
			$this->stow("# Filtered table prefix: ".$this->table_prefix."\n");
		}

		$label = $iwp_backup_core->jobdata_get('label');
		if (!empty($label)) $this->stow("# Label: $label\n");

		$this->stow("#\n");
		$this->stow("# Generated: ".date("l j. F Y H:i T")."\n");
		$this->stow("# Hostname: ".$this->dbinfo['host']."\n");
		$this->stow("# Database: ".$iwp_backup_core->backquote($this->dbinfo['name'])."\n");

		if (!empty($this->skipped_tables)) {
			if ('wp' == $this->whichdb) {
				$this->stow("# Skipped tables: " . $this->skipped_tables[$this->whichdb]."\n");
			} elseif (isset($this->skipped_tables[$this->dbinfo['name']])) {
				$this->stow("# Skipped tables: " . $this->skipped_tables[$this->dbinfo['name']]."\n");
			}
		}
		
		$this->stow("# --------------------------------------------------------\n");

		if (@constant("DB_CHARSET")) {
			$this->stow("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
			$this->stow("/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
			$this->stow("/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
			$this->stow("/*!40101 SET NAMES " . DB_CHARSET . " */;\n");
		}
		$this->stow("/*!40101 SET foreign_key_checks = 0 */;\n\n");
	}

	private function makezip_recursive_add($fullpath, $use_path_when_storing, $original_fullpath, $startlevels = 1, &$exclude) {

// 		$zipfile = $this->zip_basename.(($this->index == 0) ? '' : ($this->index+1)).'.zip.tmp';

		global $iwp_backup_core;

		// Only BinZip supports symlinks. This means that as a consistent outcome, the only think that can be done with directory symlinks is either a) potentially duplicate the data or b) skip it. Whilst with internal WP entities (e.g. plugins) we definitely want the data, in the case of user-selected directories, we assume the user knew what they were doing when they chose the directory - i.e. we can skip symlink-accessed data that's outside.
		if (is_link($fullpath) && is_dir($fullpath) && 'more' == $this->whichone) {
			$iwp_backup_core->log("Directory symlink encounted in more files backup: $use_path_when_storing -> ".readlink($fullpath).": skipping");
			return true;
		}
		
		// De-reference. Important to do to both, because on Windows only doing it to one can make them non-equal, where they were previously equal - something which we later rely upon
		$fullpath = realpath($fullpath);
		$original_fullpath = realpath($original_fullpath);

		// Is the place we've ended up above the original base? That leads to infinite recursion
		if (($fullpath !== $original_fullpath && strpos($original_fullpath, $fullpath) === 0) || ($original_fullpath == $fullpath && ((1== $startlevels && strpos($use_path_when_storing, '/') !== false) || (2 == $startlevels && substr_count($use_path_when_storing, '/') >1)))) {
			$iwp_backup_core->log("Infinite recursion: symlink led us to $fullpath, which is within $original_fullpath");
			$iwp_backup_core->log(__("Infinite recursion: consult your log for more information",'InfiniteWP'), 'error');
			return false;
		}

		# This is sufficient for the ones we have exclude options for - uploads, others, wpcore
		$stripped_storage_path = (1 == $startlevels) ? $use_path_when_storing : substr($use_path_when_storing, strpos($use_path_when_storing, '/') + 1);
		if (false !== ($fkey = array_search($stripped_storage_path, $exclude))) {
			$iwp_backup_core->log("Entity excluded by configuration option: $stripped_storage_path");
			unset($exclude[$fkey]);
			return true;
		}

		$if_altered_since = $this->makezip_if_altered_since;

		if (is_file($fullpath)) {
			if (!empty($this->excluded_extensions) && $this->is_entity_excluded_by_extension($fullpath)) {
				$iwp_backup_core->log("Entity excluded by configuration option (extension): ".basename($fullpath));
			} elseif (!empty($this->excluded_prefixes) && $this->is_entity_excluded_by_prefix($fullpath)) {
				$iwp_backup_core->log("Entity excluded by configuration option (prefix): ".basename($fullpath));
			} elseif (apply_filters('IWP_exclude_file', false, $fullpath)) {
				$iwp_backup_core->log("Entity excluded by filter: ".basename($fullpath));
			} elseif (is_readable($fullpath)) {
				$mtime = filemtime($fullpath);
				$key = ($fullpath == $original_fullpath) ? ((2 == $startlevels) ? $use_path_when_storing : $this->basename($fullpath)) : $use_path_when_storing.'/'.$this->basename($fullpath);
				if ($mtime > 0 && $mtime > $if_altered_since) {
					$this->zipfiles_batched[$fullpath] = $key;
					$this->makezip_recursive_batchedbytes += @filesize($fullpath);
					#@touch($zipfile);
				} else {
					$this->zipfiles_skipped_notaltered[$fullpath] = $key;
				}
			} else {
				$iwp_backup_core->log("$fullpath: unreadable file");
				$iwp_backup_core->log(sprintf(__("%s: unreadable file - could not be backed up (check the file permissions and ownership)", 'iwp_backup_core'), $fullpath), 'warning');
			}
		} elseif (is_dir($fullpath)) {
			if ($fullpath == $this->iwp_backup_dir_realpath) {
				$iwp_backup_core->log("Skip directory (InfiniteWP backup directory): $use_path_when_storing");
				return true;
			}
			
			if (apply_filters('IWP_exclude_directory', false, $fullpath)) {
				$iwp_backup_core->log("Skip filtered directory: $use_path_when_storing");
				return true;
			}
			
			if (file_exists($fullpath.'/.donotbackup')) {
				$iwp_backup_core->log("Skip directory (.donotbackup file found): $use_path_when_storing");
				return true;
			}

			if (!isset($this->existing_files[$use_path_when_storing])) $this->zipfiles_dirbatched[] = $use_path_when_storing;
			
			if (!$dir_handle = @opendir($fullpath)) {
				$iwp_backup_core->log("Failed to open directory: $fullpath");
				$iwp_backup_core->log(sprintf(__("Failed to open directory (check the file permissions and ownership): %s",'iwp_backup_dir'), $fullpath), 'error');
				return false;
			}

			while (false !== ($e = readdir($dir_handle))) {
				if ('.' == $e || '..' == $e) continue;
				// if ($this->whichone == 'more') {
				// 	if (!in_array(str_replace(ABSPATH, '', $e), explode(',',IWP_DEFAULT_INCLUDES))) {
				// 		continue;
				// 	}
				// }

				if (is_link($fullpath.'/'.$e)) {
					$deref = realpath($fullpath.'/'.$e);
					if (is_file($deref)) {
						if (is_readable($deref)) {
							$use_stripped = $stripped_storage_path.'/'.$e;
							if (false !== ($fkey = array_search($use_stripped, $exclude))) {
								$iwp_backup_core->log("Entity excluded by configuration option: $use_stripped");
								unset($exclude[$fkey]);
							} elseif (!empty($this->excluded_extensions) && $this->is_entity_excluded_by_extension($e)) {
								$iwp_backup_core->log("Entity excluded by configuration option (extension): $use_stripped");
							} elseif (!empty($this->excluded_prefixes) && $this->is_entity_excluded_by_prefix($e)) {
								$iwp_backup_core->log("Entity excluded by configuration option (prefix): $use_stripped");
							} elseif (apply_filters('IWP_exclude_file', false, $deref)) {
								$iwp_backup_core->log("Entity excluded by filter: $use_stripped");
							} else {
								$mtime = filemtime($deref);
								if ($mtime > 0 && $mtime > $if_altered_since) {
									$this->zipfiles_batched[$deref] = $use_path_when_storing.'/'.$e;
									$this->makezip_recursive_batchedbytes += @filesize($deref);
									#@touch($zipfile);
								} else {
									$this->zipfiles_skipped_notaltered[$deref] = $use_path_when_storing.'/'.$e;
								}
							}
						} else {
							$iwp_backup_core->log("$deref: unreadable file");
							$iwp_backup_core->log(sprintf(__("%s: unreadable file - could not be backed up"), $deref), 'warning');
						}
					} elseif (is_dir($deref)) {
					
// 						$link_target = readlink($deref);
					
						$this->makezip_recursive_add($deref, $use_path_when_storing.'/'.$e, $original_fullpath, $startlevels, $exclude);
					}
				} elseif (is_file($fullpath.'/'.$e)) {
					if (is_readable($fullpath.'/'.$e)) {
						$use_stripped = $stripped_storage_path.'/'.$e;
						if (false !== ($fkey = array_search($use_stripped, $exclude))) {
							$iwp_backup_core->log("Entity excluded by configuration option: $use_stripped");
							unset($exclude[$fkey]);
						} elseif (!empty($this->excluded_extensions) && $this->is_entity_excluded_by_extension($e)) {
							$iwp_backup_core->log("Entity excluded by configuration option (extension): $use_stripped");
						} elseif (!empty($this->excluded_prefixes) && $this->is_entity_excluded_by_prefix($e)) {
							$iwp_backup_core->log("Entity excluded by configuration option (prefix): $use_stripped");
						} elseif (apply_filters('IWP_exclude_file', false, $fullpath.'/'.$e)) {
							$iwp_backup_core->log("Entity excluded by filter: $use_stripped");
						} else {
							$mtime = filemtime($fullpath.'/'.$e);
							if ($mtime > 0 && $mtime > $if_altered_since) {
								$this->zipfiles_batched[$fullpath.'/'.$e] = $use_path_when_storing.'/'.$e;
								$this->makezip_recursive_batchedbytes += @filesize($fullpath.'/'.$e);
							} else {
								$this->zipfiles_skipped_notaltered[$fullpath.'/'.$e] = $use_path_when_storing.'/'.$e;
							}
						}
					} else {
						$iwp_backup_core->log("$fullpath/$e: unreadable file");
						$iwp_backup_core->log(sprintf(__("%s: unreadable file - could not be backed up", 'iwp_backup_dir'), $use_path_when_storing.'/'.$e), 'warning', "unrfile-$e");
					}
				} elseif (is_dir($fullpath.'/'.$e)) {
					if ('wpcore' == $this->whichone && 'updraft' == $e && basename($use_path_when_storing) == 'wp-content' && (!defined('IWP_WPCORE_INCLUDE_IWP_DIRS') || !IWP_WPCORE_INCLUDE_IWP_DIRS)) {
						// This test, of course, won't catch everything - it just aims to make things better by default
						$iwp_backup_core->log("Directory excluded for looking like a sub-site's internal IWP directory (enable by defining IWP_WPCORE_INCLUDE_IWP_DIRS): ".$use_path_when_storing.'/'.$e);
					} else {
						// no need to addEmptyDir here, as it gets done when we recurse
						$this->makezip_recursive_add($fullpath.'/'.$e, $use_path_when_storing.'/'.$e, $original_fullpath, $startlevels, $exclude);
					}
				}
			}
			closedir($dir_handle);
		} else {
			$iwp_backup_core->log("Unexpected: path ($use_path_when_storing) fails both is_file() and is_dir()");
		}

		return true;

	}

	private function get_excluded_extensions($exclude) {
		if (!is_array($exclude)) $exclude = array();
		$exclude_extensions = array();
		foreach ($exclude as $ex) {
			if (preg_match('/^ext:(.+)$/i', $ex, $matches)) {
				$exclude_extensions[] = strtolower($matches[1]);
			}
		}

		if (defined('IWP_EXCLUDE_EXTENSIONS')) {
			$exclude_from_define = explode(',', IWP_EXCLUDE_EXTENSIONS);
			foreach ($exclude_from_define as $ex) {
				$exclude_extensions[] = strtolower(trim($ex));
			}
		}

		return $exclude_extensions;
	}

	private function get_excluded_prefixes($exclude) {
		if (!is_array($exclude)) $exclude = array();
		$exclude_prefixes = array();
		foreach ($exclude as $pref) {
			if (preg_match('/^prefix:(.+)$/i', $pref, $matches)) {
				$exclude_prefixes[] = strtolower($matches[1]);
			}
		}

		return $exclude_prefixes;
	}

	private function is_entity_excluded_by_extension($entity) {
		foreach ($this->excluded_extensions as $ext) {
			if (!$ext) continue;
			$eln = strlen($ext);
			if (strtolower(substr($entity, -$eln, $eln)) == $ext) return true;
		}
		return false;
	}

	private function is_entity_excluded_by_prefix($entity) {
		$entity = basename($entity);
		foreach ($this->excluded_prefixes as $pref) {
			if (!$pref) continue;
			$eln = strlen($pref);
			if (strtolower(substr($entity, 0, $eln)) == $pref) return true;
		}
		return false;
	}

	private function unserialize_gz_cache_file($file) {
		if (!$whandle = gzopen($file, 'r')) return false;
		global $iwp_backup_core;
		$emptimes = 0;
		$var = '';
		while (!gzeof($whandle)) {
			$bytes = @gzread($whandle, 1048576);
			if (empty($bytes)) {
				$emptimes++;
				$iwp_backup_core->log("Got empty gzread ($emptimes times)");
				if ($emptimes>2) return false;
			} else {
				$var .= $bytes;
			}
		}
		gzclose($whandle);
		return unserialize($var);
	}

	// Caution: $source is allowed to be an array, not just a filename
	// $destination is the temporary file (ending in .tmp)
	private function make_zipfile($source, $backup_file_basename, $whichone, $retry_on_error = true) {

		global $iwp_backup_core;

		$original_index = $this->index;

		$itext = (empty($this->index)) ? '' : ($this->index+1);
		$destination_base = $backup_file_basename.'-'.$whichone.$itext.'.zip.tmp';
		$destination = $this->iwp_backup_dir.'/'.$destination_base;

		// Legacy/redundant
		//if (empty($whichone) && is_string($whichone)) $whichone = basename($source);

		// When to prefer PCL:
		// - We were asked to
		// - No zip extension present and no relevant method present
		// The zip extension check is not redundant, because method_exists segfaults some PHP installs, leading to support requests

		// We need meta-info about $whichone
		$backupable_entities = $iwp_backup_core->get_backupable_file_entities(true, false);
		# This is only used by one corner-case in BinZip
		#$this->make_zipfile_source = (isset($backupable_entities[$whichone])) ? $backupable_entities[$whichone] : $source;
		$this->make_zipfile_source = (is_array($source) && isset($backupable_entities[$whichone])) ? (('uploads' == $whichone) ? dirname($backupable_entities[$whichone]) : $backupable_entities[$whichone]) : dirname($source);

		$this->existing_files = array();
		# Used for tracking compression ratios
		$this->existing_files_rawsize = 0;
		$this->existing_zipfiles_size = 0;

		// Enumerate existing files
		// Usually first_linked_index is zero; the exception being with more files, where previous zips' contents are irrelevant
		for ($j=$this->first_linked_index; $j<=$this->index; $j++) {
			$jtext = ($j == 0) ? '' : ($j+1);
			# This is, in a non-obvious way, compatible with filenames which indicate increments
			# $j does not need to start at zero; it should start at the index which the current entity split at. However, this is not directly known, and can only be deduced from examining the filenames. And, for other indexes from before the current increment, the searched-for filename won't exist (even if there is no cloud storage). So, this indirectly results in the desired outcome when we start from $j=0.
			$examine_zip = $this->iwp_backup_dir.'/'.$backup_file_basename.'-'.$whichone.$jtext.'.zip'.(($j == $this->index) ? '.tmp' : '');

	
			if ($j != $this->index && !file_exists($examine_zip)) {
				$alt_examine_zip = $this->iwp_backup_dir.'/'.$backup_file_basename.'-'.$whichone.$jtext.'.zip'.(($j == $this->index - 1) ? '.tmp' : '');
				if ($alt_examine_zip != $examine_zip && file_exists($alt_examine_zip) && is_readable($alt_examine_zip) && filesize($alt_examine_zip)>0) {
					$iwp_backup_core->log("Looked-for zip file not found; but non-zero .tmp zip was, despite not being current index ($j != ".$this->index." - renaming zip (assume previous resumption's IO was lost before kill)");
					if (rename($alt_examine_zip, $examine_zip)) {
						clearstatcache();
					} else {
						$iwp_backup_core->log("Rename failed - backup zips likely to not have sequential numbers (does not affect backup integrity, but can cause user confusion)");
					}
				}
			}

			// If the file exists, then we should grab its index of files inside, and sizes
			// Then, when we come to write a file, we should check if it's already there, and only add if it is not
			if (file_exists($examine_zip) && is_readable($examine_zip) && filesize($examine_zip)>0) {
				$this->existing_zipfiles_size += filesize($examine_zip);
				$zip = new $this->use_zip_object;
				if (true !== $zip->open($examine_zip)) {
					$iwp_backup_core->log("Could not open zip file to examine (".$zip->last_error."); will remove: ".basename($examine_zip));
					@unlink($examine_zip);
				} else {

					# Don't put this in the for loop, or the magic __get() method gets called and opens the zip file every time the loop goes round
					$numfiles = $zip->numFiles;
					for ($i=0; $i < $numfiles; $i++) {
						$si = $zip->statIndex($i);
						$name = $si['name'];
						$this->existing_files[$name] = $si['size'];
						$this->existing_files_rawsize += $si['size'];
					}

					@$zip->close();
				}

				$iwp_backup_core->log(basename($examine_zip).": Zip file already exists, with ".count($this->existing_files)." files");

				# try_split is set if there have been no check-ins recently - or if it needs to be split anyway
				if ($j == $this->index) {
					if (isset($this->try_split)) {
						if (filesize($examine_zip) > 50*1048576) {
							# We could, as a future enhancement, save this back to the job data, if we see a case that needs it
							$this->zip_split_every = max(
								(int)$this->zip_split_every/2,
								IWP_SPLIT_MIN*1048576,
								min(filesize($examine_zip)-1048576, $this->zip_split_every)
							);
							$iwp_backup_core->jobdata_set('split_every', (int)($this->zip_split_every/1048576));
							$iwp_backup_core->log("No check-in on last two runs; bumping index and reducing zip split to: ".round($this->zip_split_every/1048576, 1)." MB");
							$do_bump_index = true;
						}
						unset($this->try_split);
					} elseif (filesize($examine_zip) > $this->zip_split_every) {
						$iwp_backup_core->log(sprintf("Zip size is at/near split limit (%s MB / %s MB) - bumping index (from: %d)", filesize($examine_zip), round($this->zip_split_every/1048576, 1), $this->index));
						$do_bump_index = true;
					}
				}

			} elseif (file_exists($examine_zip)) {
				$iwp_backup_core->log("Zip file already exists, but is not readable or was zero-sized; will remove: ".basename($examine_zip));
				@unlink($examine_zip);
			}
		}

		$this->zip_last_ratio = ($this->existing_files_rawsize > 0) ? ($this->existing_zipfiles_size/$this->existing_files_rawsize) : 1;

		$this->zipfiles_added = 0;
		$this->zipfiles_added_thisrun = 0;
		$this->zipfiles_dirbatched = array();
		$this->zipfiles_batched = array();
		$this->zipfiles_skipped_notaltered = array();
		$this->zipfiles_lastwritetime = time();
		$this->zip_basename = $this->iwp_backup_dir.'/'.$backup_file_basename.'-'.$whichone;

		if (!empty($do_bump_index)) $this->bump_index();

		$error_occurred = false;

		# Store this in its original form
		$this->source = $source;

		# Reset. This counter is used only with PcLZip, to decide if it's better to do it all-in-one
		$this->makezip_recursive_batchedbytes = 0;
		if (!is_array($source)) $source=array($source);

		$exclude = $iwp_backup_core->get_exclude($whichone);

		$files_enumerated_at = $iwp_backup_core->jobdata_get('files_enumerated_at');
		if (!is_array($files_enumerated_at)) $files_enumerated_at = array();
		$files_enumerated_at[$whichone] = time();
		$iwp_backup_core->jobdata_set('files_enumerated_at', $files_enumerated_at);

		$this->makezip_if_altered_since = (is_array($this->altered_since)) ? (isset($this->altered_since[$whichone]) ? $this->altered_since[$whichone] : -1) : -1;

		// Reset
		$got_uploads_from_cache = false;
		
		// Uploads: can/should we get it back from the cache?
		// || 'others' == $whichone
		if (('uploads' == $whichone || 'others' == $whichone) && function_exists('gzopen') && function_exists('gzread')) {
			$use_cache_files = false;
			$cache_file_base = $this->zip_basename.'-cachelist-'.$this->makezip_if_altered_since;
			// Cache file suffixes: -zfd.gz.tmp, -zfb.gz.tmp, -info.tmp, (possible)-zfs.gz.tmp
			if (file_exists($cache_file_base.'-zfd.gz.tmp') && file_exists($cache_file_base.'-zfb.gz.tmp') && file_exists($cache_file_base.'-info.tmp')) {
				// Cache files exist; shall we use them?
				$mtime = filemtime($cache_file_base.'-zfd.gz.tmp');
				// Require < 30 minutes old
				if (time() - $mtime < 1800) { $use_cache_files = true; }
				$any_failures = false;
				if ($use_cache_files) {
					$var = $this->unserialize_gz_cache_file($cache_file_base.'-zfd.gz.tmp');
					if (is_array($var)) {
						$this->zipfiles_dirbatched = $var;
						$var = $this->unserialize_gz_cache_file($cache_file_base.'-zfb.gz.tmp');
						if (is_array($var)) {
							$this->zipfiles_batched = $var;
							if (file_exists($cache_file_base.'-info.tmp')) {
								$var = maybe_unserialize(file_get_contents($cache_file_base.'-info.tmp'));
								if (is_array($var) && isset($var['makezip_recursive_batchedbytes'])) {
									$this->makezip_recursive_batchedbytes = $var['makezip_recursive_batchedbytes'];
									if (file_exists($cache_file_base.'-zfs.gz.tmp')) {~
										$var = $this->unserialize_gz_cache_file($cache_file_base.'-zfs.gz.tmp');
										if (is_array($var)) {
											$this->zipfiles_skipped_notaltered = $var;
										} else {
											$any_failures = true;
										}
									} else {
										$this->zipfiles_skipped_notaltered = array();
									}
								} else {
									$any_failures = true;
								}
							}
						} else {
							$any_failures = true;
						}
					} else {
						$any_failures = true;
					}
					if ($any_failures) {
						$iwp_backup_core->log("Failed to recover file lists from existing cache files");
						// Reset it all
						$this->zipfiles_skipped_notaltered = array();
						$this->makezip_recursive_batchedbytes = 0;
						$this->zipfiles_batched = array();
						$this->zipfiles_dirbatched = array();
					} else {
						$iwp_backup_core->log("File lists recovered from cache files; sizes: ".count($this->zipfiles_batched).", ".count($this->zipfiles_batched).", ".count($this->zipfiles_skipped_notaltered).")");
						$got_uploads_from_cache = true;
					}
				}
			}
		}

		$time_counting_began = time();

		$this->excluded_extensions = $this->get_excluded_extensions($exclude);
		$this->excluded_prefixes = $this->get_excluded_prefixes($exclude);

		foreach ($source as $element) {
			#makezip_recursive_add($fullpath, $use_path_when_storing, $original_fullpath, $startlevels = 1, $exclude_array)
			if ('uploads' == $whichone) {
				if (empty($got_uploads_from_cache)) {
					$dirname = dirname($element);
					$basename = $this->basename($element);
					$add_them = $this->makezip_recursive_add($element, basename($dirname).'/'.$basename, $element, 2, $exclude);
				} else {
					$add_them = true;
				}
			} else {
				if (empty($got_uploads_from_cache)) {
					$add_them = $this->makezip_recursive_add($element, $this->basename($element), $element, 1, $exclude);
				} else {
					$add_them = true;
				}
			}
			if (is_wp_error($add_them) || false === $add_them) $error_occurred = true;
		}

		$time_counting_ended = time();

		// Cache the file scan, if it looks like it'll be useful
		// We use gzip to reduce the size as on hosts which limit disk I/O, the cacheing may make things worse
		// 	|| 'others' == $whichone
		if (('uploads' == $whichone|| 'others' == $whichone) && !$error_occurred && function_exists('gzopen') && function_exists('gzwrite')) {
			$cache_file_base = $this->zip_basename.'-cachelist-'.$this->makezip_if_altered_since;

			// Just approximate - we're trying to avoid an otherwise-unpredictable PHP fatal error. Cacheing only happens if file enumeration took a long time - so presumably there are very many.
			$memory_needed_estimate = 0;
			foreach ($this->zipfiles_batched as $k => $v) { $memory_needed_estimate += strlen($k)+strlen($v)+12; }

			// We haven't bothered to check if we just fetched the files from cache, as that shouldn't take a long time and so shouldn't trigger this
			// Let us suppose we need 15% overhead for gzipping
			
			$memory_limit = ini_get('memory_limit');
			$memory_usage = round(@memory_get_usage(false)/1048576, 1);
			$memory_usage2 = round(@memory_get_usage(true)/1048576, 1);
			
			if ($time_counting_ended-$time_counting_began > 20 && $iwp_backup_core->verify_free_memory($memory_needed_estimate*0.15) && $whandle = gzopen($cache_file_base.'-zfb.gz.tmp', 'w')) {
				$iwp_backup_core->log("File counting took a long time (".($time_counting_ended - $time_counting_began)."s); will attempt to cache results (memory_limit: $memory_limit (used: ${memory_usage}M | ${memory_usage2}M), estimated uncompressed bytes: ".round($memory_needed_estimate/1024, 1)." Kb)");
				
				$buf = 'a:'.count($this->zipfiles_batched).':{';
				foreach ($this->zipfiles_batched as $file => $add_as) {
					$k = addslashes($file);
					$v = addslashes($add_as);
					$buf .= 's:'.strlen($k).':"'.$k.'";s:'.strlen($v).':"'.$v.'";';
					if (strlen($buf) > 1048576) {
						gzwrite($whandle, $buf, strlen($buf));
						$buf = '';
					}
				}
				$buf .= '}';
				$final = gzwrite($whandle, $buf);
				unset($buf);
				

				if (!$final) {
					@unlink($cache_file_base.'-zfb.gz.tmp');
					@gzclose($whandle);
				} else {
					gzclose($whandle);
					if (!empty($this->zipfiles_skipped_notaltered)) {
						if ($shandle = gzopen($cache_file_base.'-zfs.gz.tmp', 'w')) {
							if (!gzwrite($shandle, serialize($this->zipfiles_skipped_notaltered))) {
								$aborted_on_skipped = true;
							}
							gzclose($shandle);
						} else {
							$aborted_on_skipped = true;
						}
					}
					if (!empty($aborted_on_skipped)) {
						@unlink($cache_file_base.'-zfs.gz.tmp');
						@unlink($cache_file_base.'-zfb.gz.tmp');
					} else {
						$info_array = array('makezip_recursive_batchedbytes' => $this->makezip_recursive_batchedbytes);
						if (!file_put_contents($cache_file_base.'-info.tmp', serialize($info_array))) {
							@unlink($cache_file_base.'-zfs.gz.tmp');
							@unlink($cache_file_base.'-zfb.gz.tmp');
						}
						if ($dhandle = gzopen($cache_file_base.'-zfd.gz.tmp', 'w')) {
							if (!gzwrite($dhandle, serialize($this->zipfiles_dirbatched))) {
								$aborted_on_dirbatched = true;
							}
							gzclose($dhandle);
						} else {
							$aborted_on_dirbatched = true;
						}
						if (!empty($aborted_on_dirbatched)) {
							@unlink($cache_file_base.'-zfs.gz.tmp');
							@unlink($cache_file_base.'-zfd.gz.tmp');
							@unlink($cache_file_base.'-zfb.gz.tmp');
							@unlink($cache_file_base.'-info.tmp');
						} else {
							// Success.
						}
					}
				}
			}

/*
		Class variables that get altered:
		zipfiles_batched
		makezip_recursive_batchedbytes
		zipfiles_skipped_notaltered
		zipfiles_dirbatched
		
		Class variables that the result depends upon (other than the state of the filesystem):
		makezip_if_altered_since
		existing_files
		*/

		}

		// Any not yet dispatched? Under our present scheme, at this point nothing has yet been despatched. And since the enumerating of all files can take a while, we can at this point do a further modification check to reduce the chance of overlaps.
		// This relies on us *not* touch()ing the zip file to indicate to any resumption 'behind us' that we're already here. Rather, we're relying on the combined facts that a) if it takes us a while to search the directory tree, then it should do for the one behind us too (though they'll have the benefit of cache, so could catch very fast) and b) we touch *immediately* after finishing the enumeration of the files to add.
		// $retry_on_error is here being used as a proxy for 'not the second time around, when there might be the remains of the file on the first time around'
		if ($retry_on_error) $iwp_backup_core->check_recent_modification($destination);
		// Here we're relying on the fact that both PclZip and ZipArchive will happily operate on an empty file. Note that BinZip *won't* (for that, may need a new strategy - e.g. add the very first file on its own, in order to 'lay down a marker')
		if (empty($do_bump_index)) @touch($destination);

		if (count($this->zipfiles_dirbatched) > 0 || count($this->zipfiles_batched) > 0) {

			$iwp_backup_core->log(sprintf("Total entities for the zip file: %d directories, %d files (%d skipped as non-modified), %s MB", count($this->zipfiles_dirbatched), count($this->zipfiles_batched), count($this->zipfiles_skipped_notaltered), round($this->makezip_recursive_batchedbytes/1048576,1)));

			// No need to warn if we're going to retry anyway. (And if we get killed, the zip will be rescanned for its contents upon resumption).
			$warn_on_failures = ($retry_on_error) ? false : true;
			$add_them = $this->makezip_addfiles($warn_on_failures);

			if (is_wp_error($add_them)) {
				foreach ($add_them->get_error_messages() as $msg) {
					$iwp_backup_core->log("Error returned from makezip_addfiles: ".$msg);
				}
				$error_occurred = true;
			} elseif (false === $add_them) {
				$iwp_backup_core->log("Error: makezip_addfiles returned false");
				$error_occurred = true;
			}

		}

		// Reset these variables because the index may have changed since we began

		$itext = (empty($this->index)) ? '' : ($this->index+1);
		$destination_base = $backup_file_basename.'-'.$whichone.$itext.'.zip.tmp';
		$destination = $this->iwp_backup_dir.'/'.$destination_base;

		// ZipArchive::addFile sometimes fails - there's nothing when we expected something.
		// Did not used to have || $error_occured here. But it is better to retry, than to simply warn the user to check his logs.
		if (((file_exists($destination) || $this->index == $original_index) && @filesize($destination) < 90 && 'IWP_MMB_ZipArchive' == $this->use_zip_object) || ($error_occurred && $retry_on_error)) {
			// This can be made more sophisticated if feedback justifies it. Currently we just switch to PclZip. But, it may have been a BinZip failure, so we could then try ZipArchive if that is available. If doing that, make sure that an infinite recursion isn't made possible.
			$iwp_backup_core->log("makezip_addfiles(".$this->use_zip_object.") apparently failed (file=".basename($destination).", type=$whichone, size=".filesize($destination).") - retrying with PclZip");
			$saved_zip_object = $this->use_zip_object;
			$this->use_zip_object = 'IWP_MMB_PclZip';
			$ret = $this->make_zipfile($source, $backup_file_basename, $whichone, false);
			$this->use_zip_object = $saved_zip_object;
			return $ret;
		}

		// zipfiles_added > 0 means that $zip->close() has been called. i.e. An attempt was made to add something: something _should_ be there.
		// Why return true even if $error_occurred may be set? 1) Because in that case, a warning has already been logged. 2) Because returning false causes an error to be logged, which means it'll all be retried again. Also 3) this has been the pattern of the code for a long time, and the algorithm has been proven in the real-world: don't change what's not broken.
		//  (file_exists($destination) || $this->index == $original_index) might be an alternative to $this->zipfiles_added > 0 - ? But, don't change what's not broken.
		if ($error_occurred == false || $this->zipfiles_added > 0) {
			return true;
		} else {
			$iwp_backup_core->log("makezip failure: zipfiles_added=".$this->zipfiles_added.", error_occurred=".$error_occurred." (method=".$this->use_zip_object.")");
			return false;
		}

	}

	private function basename($element) {
		# This function is an ugly, conservative workaround for https://bugs.php.net/bug.php?id=62119. It does not aim to always work-around, but to ensure that nothing is made worse.
		$dirname = dirname($element);
		$basename_manual = preg_replace('#^[\\/]+#', '', substr($element, strlen($dirname)));
		$basename = basename($element);
		if ($basename_manual != $basename) {
			$locale = setlocale(LC_CTYPE, "0");
			if ('C' == $locale) {
				setlocale(LC_CTYPE, 'en_US.UTF8');
				$basename_new = basename($element);
				if ($basename_new == $basename_manual) $basename = $basename_new;
				setlocale(LC_CTYPE, $locale);
			}
		}
		return $basename;
	}

	private function file_should_be_stored_without_compression($file) {
		if (!is_array($this->extensions_to_not_compress)) return false;
		foreach ($this->extensions_to_not_compress as $ext) {
			$ext_len = strlen($ext);
			if (strtolower(substr($file, -$ext_len, $ext_len)) == $ext) return true;
		}
		return false;
	}

	// Q. Why don't we only open and close the zip file just once?
	// A. Because apparently PHP doesn't write out until the final close, and it will return an error if anything file has vanished in the meantime. So going directory-by-directory reduces our chances of hitting an error if the filesystem is changing underneath us (which is very possible if dealing with e.g. 1GB of files)

	// We batch up the files, rather than do them one at a time. So we are more efficient than open,one-write,close.
	// To call into here, the array $this->zipfiles_batched must be populated (keys=paths, values=add-to-zip-as values). It gets reset upon exit from here.
	private function makezip_addfiles($warn_on_failures) {

		global $iwp_backup_core;

		# Used to detect requests to bump the size
		$bump_index = false;
		$ret = true;

		$zipfile = $this->zip_basename.(($this->index == 0) ? '' : ($this->index+1)).'.zip.tmp';

		$maxzipbatch = $iwp_backup_core->jobdata_get('maxzipbatch', 26214400);
		if ((int)$maxzipbatch < 1024) $maxzipbatch = 26214400;

		// Short-circuit the null case, because we want to detect later if something useful happenned
		if (count($this->zipfiles_dirbatched) == 0 && count($this->zipfiles_batched) == 0) return true;

		# If on PclZip, then if possible short-circuit to a quicker method (makes a huge time difference - on a folder of 1500 small files, 2.6s instead of 76.6)
		# This assumes that makezip_addfiles() is only called once so that we know about all needed files (the new style)
		# This is rather conservative - because it assumes zero compression. But we can't know that in advance.
		$force_allinone = false;
		if (0 == $this->index && $this->makezip_recursive_batchedbytes < $this->zip_split_every) {
			# So far, we only have a processor for this for PclZip; but that check can be removed - need to address the below items
			# TODO: Is this really what we want? Always go all-in-one for < 500MB???? Should be more conservative? Or, is it always faster to go all-in-one? What about situations where we might want to auto-split because of slowness - check that that is still working.
			# TODO: Test this new method for PclZip - are we still getting the performance gains? Test for ZipArchive too.
			if ('IWP_MMB_PclZip' == $this->use_zip_object && ($this->makezip_recursive_batchedbytes < 512*1048576 || (defined('IWP_PCLZIP_FORCEALLINONE') && IWP_PCLZIP_FORCEALLINONE == true && 'IWP_MMB_PclZip' == $this->use_zip_object))) {
				$iwp_backup_core->log("Only one archive required (".$this->use_zip_object.") - will attempt to do in single operation (data: ".round($this->makezip_recursive_batchedbytes/1024,1)." KB, split: ".round($this->zip_split_every/1024, 1)." KB)");
				$force_allinone = true;
			}
		}

		// 05-Mar-2013 - added a new check on the total data added; it appears that things fall over if too much data is contained in the cumulative total of files that were addFile'd without a close-open cycle; presumably data is being stored in memory. In the case in question, it was a batch of MP3 files of around 100MB each - 25 of those equals 2.5GB!

		$data_added_since_reopen = 0;
		# The following array is used only for error reporting if ZipArchive::close fails (since that method itself reports no error messages - we have to track manually what we were attempting to add)
		$files_zipadded_since_open = array();

		$zip = new $this->use_zip_object;
		if (file_exists($zipfile)) {
			$opencode = $zip->open($zipfile);
			$original_size = filesize($zipfile);
			clearstatcache();
		} else {
			$create_code = (version_compare(PHP_VERSION, '5.2.12', '>') && defined('ZIPARCHIVE::CREATE')) ? ZIPARCHIVE::CREATE : 1;
			$opencode = $zip->open($zipfile, $create_code);
			$original_size = 0;
		}

		if ($opencode !== true) return new WP_Error('no_open', sprintf(__('Failed to open the zip file (%s) - %s', 'InfiniteWP'),$zipfile, $zip->last_error));
		# TODO: This action isn't being called for the all-in-one case - should be, I think
		do_action("IWP_makezip_addfiles_prepack", $this, $this->whichone);

		// Make sure all directories are created before we start creating files
		while ($dir = array_pop($this->zipfiles_dirbatched)) $zip->addEmptyDir($dir);
		$zipfiles_added_thisbatch = 0;

		// Go through all those batched files
		foreach ($this->zipfiles_batched as $file => $add_as) {

			if (!file_exists($file)) {
				$iwp_backup_core->log("File has vanished from underneath us; dropping: ".$add_as);
				continue;
			}

			$fsize = filesize($file);

			if (@constant('IWP_SKIP_FILE_OVER_SIZE') && $fsize > IWP_SKIP_FILE_OVER_SIZE) {
				$iwp_backup_core->log("File is larger than the user-configured (IWP_SKIP_FILE_OVER_SIZE) maximum (is: ".round($fsize/1024, 1)." KB); will skip: ".$add_as);
				continue;
			} elseif ($fsize > IWP_WARN_FILE_SIZE) {
				$iwp_backup_core->log(sprintf(__('A very large file was encountered: %s (size: %s Mb)', 'InfiniteWP'), $add_as, round($fsize/1048576, 1)), 'warning', 'vlargefile_'.md5($this->whichone.'#'.$add_as));
			}

			// Skips files that are already added
			if (!isset($this->existing_files[$add_as]) || $this->existing_files[$add_as] != $fsize) {

				@touch($zipfile);
				$zip->addFile($file, $add_as);
				$zipfiles_added_thisbatch++;

				if (method_exists($zip, 'setCompressionName') && $this->file_should_be_stored_without_compression($add_as)) {
					if (false == ($set_compress = $zip->setCompressionName($add_as, ZipArchive::CM_STORE))) {
						$iwp_backup_core->log("Zip: setCompressionName failed on: $add_as");
					}
				}

				// N.B., Since makezip_addfiles() can get called more than once if there were errors detected, potentially $zipfiles_added_thisrun can exceed the total number of batched files (if they get processed twice).
				$this->zipfiles_added_thisrun++;
				$files_zipadded_since_open[] = array('file' => $file, 'addas' => $add_as);

				$data_added_since_reopen += $fsize;
				/* Conditions for forcing a write-out and re-open:
				- more than $maxzipbatch bytes have been batched
				- more than 2.0 seconds have passed since the last time we wrote
				- that adding this batch of data is likely already enough to take us over the split limit (and if that happens, then do actually split - to prevent a scenario of progressively tinier writes as we approach but don't actually reach the limit)
				- more than 500 files batched (should perhaps intelligently lower this as the zip file gets bigger - not yet needed)
				*/

				# Add 10% margin. It only really matters when the OS has a file size limit, exceeding which causes failure (e.g. 2GB on 32-bit)
				# Since we don't test before the file has been created (so that zip_last_ratio has meaningful data), we rely on max_zip_batch being less than zip_split_every - which should always be the case
				$reaching_split_limit = ( $this->zip_last_ratio > 0 && $original_size>0 && ($original_size + 1.1*$data_added_since_reopen*$this->zip_last_ratio) > $this->zip_split_every) ? true : false;

				if (!$force_allinone && ($zipfiles_added_thisbatch > IWP_MAXBATCHFILES || $reaching_split_limit || $data_added_since_reopen > $maxzipbatch || (time() - $this->zipfiles_lastwritetime) > 2)) {

					@set_time_limit(IWP_SET_TIME_LIMIT);
					$something_useful_sizetest = false;

					if ($data_added_since_reopen > $maxzipbatch) {
						$something_useful_sizetest = true;
						$iwp_backup_core->log("Adding batch to zip file (".$this->use_zip_object."): over ".round($maxzipbatch/1048576,1)." MB added on this batch (".round($data_added_since_reopen/1048576,1)." MB, ".count($this->zipfiles_batched)." files batched, $zipfiles_added_thisbatch (".$this->zipfiles_added_thisrun.") added so far); re-opening (prior size: ".round($original_size/1024,1).' KB)');
					} elseif ($zipfiles_added_thisbatch > IWP_MAXBATCHFILES) {
						$iwp_backup_core->log("Adding batch to zip file (".$this->use_zip_object."): over ".IWP_MAXBATCHFILES." files added on this batch (".round($data_added_since_reopen/1048576,1)." MB, ".count($this->zipfiles_batched)." files batched, $zipfiles_added_thisbatch (".$this->zipfiles_added_thisrun.") added so far); re-opening (prior size: ".round($original_size/1024,1).' KB)');
					} elseif (!$reaching_split_limit) {
						$iwp_backup_core->log("Adding batch to zip file (".$this->use_zip_object."): over 2.0 seconds have passed since the last write (".round($data_added_since_reopen/1048576,1)." MB, $zipfiles_added_thisbatch (".$this->zipfiles_added_thisrun.") files added so far); re-opening (prior size: ".round($original_size/1024,1).' KB)');
					} else {
						$iwp_backup_core->log("Adding batch to zip file (".$this->use_zip_object."): possibly approaching split limit (".round($data_added_since_reopen/1048576,1)." MB, $zipfiles_added_thisbatch (".$this->zipfiles_added_thisrun.") files added so far); last ratio: ".round($this->zip_last_ratio,4)."; re-opening (prior size: ".round($original_size/1024,1).' KB)');
					}

					if (!$zip->close()) {
						// Though we will continue processing the files we've got, the final error code will be false, to allow a second attempt on the failed ones. This also keeps us consistent with a negative result for $zip->close() further down. We don't just retry here, because we have seen cases (with BinZip) where upon failure, the existing zip had actually been deleted. So, to be safe we need to re-scan the existing zips.
						$ret = false;
						$this->record_zip_error($files_zipadded_since_open, $zip->last_error, $warn_on_failures);
					}

					$zipfiles_added_thisbatch = 0;

					# This triggers a re-open, later
					unset($zip);
					$files_zipadded_since_open = array();
					// Call here, in case we've got so many big files that we don't complete the whole routine
					if (filesize($zipfile) > $original_size) {

						# It is essential that this does not go above 1, even though in reality (and this can happen at the start, if just 1 file is added (e.g. due to >2.0s detection) the 'compressed' zip file may be *bigger* than the files stored in it. When that happens, if the ratio is big enough, it can then fire the "approaching split limit" detection (very) prematurely
						$this->zip_last_ratio = ($data_added_since_reopen > 0) ? min((filesize($zipfile) - $original_size)/$data_added_since_reopen, 1) : 1;

						# We need a rolling update of this
						$original_size = filesize($zipfile);

						# Move on to next zip?
						if ($reaching_split_limit || filesize($zipfile) > $this->zip_split_every) {
							$bump_index = true;
							# Take the filesize now because later we wanted to know we did clearstatcache()
							$bumped_at = round(filesize($zipfile)/1048576, 1);
						}

						# Need to make sure that something_useful_happened() is always called

						# How long since the current run began? If it's taken long (and we're in danger of not making it at all), or if that is forseeable in future because of general slowness, then we should reduce the parameters.
						if (!$something_useful_sizetest) {
							$iwp_backup_core->something_useful_happened();
						} else {

							// Do this as early as possible
							$iwp_backup_core->something_useful_happened();

							$time_since_began = max(microtime(true)- $this->zipfiles_lastwritetime, 0.000001);
							$normalised_time_since_began = $time_since_began*($maxzipbatch/$data_added_since_reopen);

							// Don't measure speed until after ZipArchive::close()
							$rate = round($data_added_since_reopen/$time_since_began, 1);

							$iwp_backup_core->log(sprintf("A useful amount of data was added after this amount of zip processing: %s s (normalised: %s s, rate: %s KB/s)", round($time_since_began, 1), round($normalised_time_since_began, 1), round($rate/1024, 1)));

							// We want to detect not only that we need to reduce the size of batches, but also the capability to increase them. This is particularly important because of ZipArchive()'s (understandable, given the tendency of PHP processes being terminated without notice) practice of first creating a temporary zip file via copying before acting on that zip file (so the information is atomic). Unfortunately, once the size of the zip file gets over 100MB, the copy operation beguns to be significant. By the time you've hit 500MB on many web hosts the copy is the majority of the time taken. So we want to do more in between these copies if possible.

							/* "Could have done more" - detect as:
							- A batch operation would still leave a "good chunk" of time in a run
							- "Good chunk" means that the time we took to add the batch is less than 50% of a run time
							- We can do that on any run after the first (when at least one ceiling on the maximum time is known)
							- But in the case where a max_execution_time is long (so that resumptions are never needed), and we're always on run 0, we will automatically increase chunk size if the batch took less than 6 seconds.
							*/

							// At one stage we had a strategy of not allowing check-ins to have more than 20s between them. However, once the zip file got to a certain size, PHP's habit of copying the entire zip file first meant that it *always* went over 18s, and thence a drop in the max size was inevitable - which was bad, because with the copy time being something that only grew, the outcome was less data being copied every time

							// Gather the data. We try not to do this unless necessary (may be time-sensitive)
							if ($iwp_backup_core->current_resumption >= 1) {
								$time_passed = $iwp_backup_core->jobdata_get('run_times');
								if (!is_array($time_passed)) $time_passed = array();
								list($max_time, $timings_string, $run_times_known) = $iwp_backup_core->max_time_passed($time_passed, $iwp_backup_core->current_resumption-1, $this->first_run);
							} else {
								$run_times_known = 0;
								$max_time = -1;
							}

							if ($normalised_time_since_began<6 || ($iwp_backup_core->current_resumption >=1 && $run_times_known >=1 && $time_since_began < 0.6*$max_time )) {

								// How much can we increase it by?
								if ($normalised_time_since_began <6) {
									if ($run_times_known > 0 && $max_time >0) {
										$new_maxzipbatch = min(floor(max(
											$maxzipbatch*6/$normalised_time_since_began, $maxzipbatch*((0.6*$max_time)/$normalised_time_since_began))),
										200*1024*1024
										);
									} else {
										# Maximum of 200MB in a batch
										$new_maxzipbatch = min( floor($maxzipbatch*6/$normalised_time_since_began),
										200*1024*1024
										);
									}
								} else {
									// Use up to 60% of available time
									$new_maxzipbatch = min(
									floor($maxzipbatch*((0.6*$max_time)/$normalised_time_since_began)),
									200*1024*1024
									);
								}

								# Throttle increases - don't increase by more than 2x in one go - ???
								# $new_maxzipbatch = floor(min(2*$maxzipbatch, $new_maxzipbatch));
								# Also don't allow anything that is going to be more than 18 seconds - actually, that's harmful because of the basically fixed time taken to copy the file
								# $new_maxzipbatch = floor(min(18*$rate ,$new_maxzipbatch));

								# Don't go above the split amount (though we expect that to be higher anyway, unless sending via email)
								$new_maxzipbatch = min($new_maxzipbatch, $this->zip_split_every);

								# Don't raise it above a level that failed on a previous run
								$maxzipbatch_ceiling = $iwp_backup_core->jobdata_get('maxzipbatch_ceiling');
								if (is_numeric($maxzipbatch_ceiling) && $maxzipbatch_ceiling > 20*1024*1024 && $new_maxzipbatch > $maxzipbatch_ceiling) {
									$iwp_backup_core->log("Was going to raise maxzipbytes to $new_maxzipbatch, but this is too high: a previous failure led to the ceiling being set at $maxzipbatch_ceiling, which we will use instead");
									$new_maxzipbatch = $maxzipbatch_ceiling;
								}

								// Final sanity check
								if ($new_maxzipbatch > 1024*1024) $iwp_backup_core->jobdata_set("maxzipbatch", $new_maxzipbatch);
								
								if ($new_maxzipbatch <= 1024*1024) {
									$iwp_backup_core->log("Unexpected new_maxzipbatch value obtained (time=$time_since_began, normalised_time=$normalised_time_since_began, max_time=$max_time, data points known=$run_times_known, old_max_bytes=$maxzipbatch, new_max_bytes=$new_maxzipbatch)");
								} elseif ($new_maxzipbatch > $maxzipbatch) {
									$iwp_backup_core->log("Performance is good - will increase the amount of data we attempt to batch (time=$time_since_began, normalised_time=$normalised_time_since_began, max_time=$max_time, data points known=$run_times_known, old_max_bytes=$maxzipbatch, new_max_bytes=$new_maxzipbatch)");
								} elseif ($new_maxzipbatch < $maxzipbatch) {
									// Ironically, we thought we were speedy...
									$iwp_backup_core->log("Adjust: Reducing maximum amount of batched data (time=$time_since_began, normalised_time=$normalised_time_since_began, max_time=$max_time, data points known=$run_times_known, new_max_bytes=$new_maxzipbatch, old_max_bytes=$maxzipbatch)");
								} else {
									$iwp_backup_core->log("Performance is good - but we will not increase the amount of data we batch, as we are already at the present limit (time=$time_since_began, normalised_time=$normalised_time_since_began, max_time=$max_time, data points known=$run_times_known, max_bytes=$maxzipbatch)");
								}

								if ($new_maxzipbatch > 1024*1024) $maxzipbatch = $new_maxzipbatch;
							}

							// Detect excessive slowness
							// Don't do this until we're on at least resumption 7, as we want to allow some time for things to settle down and the maxiumum time to be accurately known (since reducing the batch size unnecessarily can itself cause extra slowness, due to PHP's usage of temporary zip files)
								
							// We use a percentage-based system as much as possible, to avoid the various criteria being in conflict with each other (i.e. a run being both 'slow' and 'fast' at the same time, which is increasingly likely as max_time gets smaller).

							if (!$iwp_backup_core->something_useful_happened && $iwp_backup_core->current_resumption >= 7) {

								$iwp_backup_core->something_useful_happened();

								if ($run_times_known >= 5 && ($time_since_began > 0.8 * $max_time || $time_since_began + 7 > $max_time)) {

									$new_maxzipbatch = max(floor($maxzipbatch*0.8), 20971520);
									if ($new_maxzipbatch < $maxzipbatch) {
										$maxzipbatch = $new_maxzipbatch;
										$iwp_backup_core->jobdata_set("maxzipbatch", $new_maxzipbatch);
										$iwp_backup_core->log("We are within a small amount of the expected maximum amount of time available; the zip-writing thresholds will be reduced (time_passed=$time_since_began, normalised_time_passed=$normalised_time_since_began, max_time=$max_time, data points known=$run_times_known, old_max_bytes=$maxzipbatch, new_max_bytes=$new_maxzipbatch)");
									} else {
										$iwp_backup_core->log("We are within a small amount of the expected maximum amount of time available, but the zip-writing threshold is already at its lower limit (20MB), so will not be further reduced (max_time=$max_time, data points known=$run_times_known, max_bytes=$maxzipbatch)");
									}
								}

							} else {
								$iwp_backup_core->something_useful_happened();
							}
						}
						$data_added_since_reopen = 0;
					} else {
						# ZipArchive::close() can take a very long time, which we want to know about
						$iwp_backup_core->record_still_alive();
					}

					clearstatcache();
					$this->zipfiles_lastwritetime = time();
				}
			} elseif (0 == $this->zipfiles_added_thisrun) {
				// Update lastwritetime, because otherwise the 2.0-second-activity detection can fire prematurely (e.g. if it takes >2.0 seconds to process the previously-written files, then the detector fires after 1 file. This then can have the knock-on effect of having something_useful_happened() called, but then a subsequent attempt to write out a lot of meaningful data fails, and the maximum batch is not then reduced.
				// Testing shows that calling time() 1000 times takes negligible time
				$this->zipfiles_lastwritetime=time();
			}

			$this->zipfiles_added++;

			// Don't call something_useful_happened() here - nothing necessarily happens until close() is called
			if ($this->zipfiles_added % 100 == 0) $iwp_backup_core->log("Zip: ".basename($zipfile).": ".$this->zipfiles_added." files added (on-disk size: ".round(@filesize($zipfile)/1024,1)." KB)");

			if ($bump_index) {
				$iwp_backup_core->log(sprintf("Zip size is at/near split limit (%s MB / %s MB) - bumping index (from: %d)", $bumped_at, round($this->zip_split_every/1048576, 1), $this->index));
				$bump_index = false;
				$this->bump_index();
				$zipfile = $this->zip_basename.($this->index+1).'.zip.tmp';
			}

			if (empty($zip)) {
				$zip = new $this->use_zip_object;

				if (file_exists($zipfile)) {
					$opencode = $zip->open($zipfile);
					$original_size = filesize($zipfile);
					clearstatcache();
				} else {
					$create_code = defined('ZIPARCHIVE::CREATE') ? ZIPARCHIVE::CREATE : 1;
					$opencode = $zip->open($zipfile, $create_code);
					$original_size = 0;
				}

				if ($opencode !== true) return new WP_Error('no_open', sprintf(__('Failed to open the zip file (%s) - %s', 'InfiniteWP'), $zipfile, $zip->last_error));
			}

		}

		# Reset array
		$this->zipfiles_batched = array();
		$this->zipfiles_skipped_notaltered = array();

		if (false == ($nret = $zip->close())) $this->record_zip_error($files_zipadded_since_open, $zip->last_error, $warn_on_failures);

		do_action("IWP_makezip_addfiles_finished", $this, $this->whichone);

		$this->zipfiles_lastwritetime = time();
		# May not exist if the last thing we did was bump
		if (file_exists($zipfile) && filesize($zipfile) > $original_size) $iwp_backup_core->something_useful_happened();

		# Move on to next archive?
		if (file_exists($zipfile) && filesize($zipfile) > $this->zip_split_every) {
			$iwp_backup_core->log(sprintf("Zip size has gone over split limit (%s, %s) - bumping index (%d)", round(filesize($zipfile)/1048576,1), round($this->zip_split_every/1048576, 1), $this->index));
			$this->bump_index();
		}

		clearstatcache();

		return ($ret == false) ? false : $nret;
	}

	private function record_zip_error($files_zipadded_since_open, $msg, $warn = true) {
		global $iwp_backup_core;

		if (!empty($iwp_backup_core->cpanel_quota_readable)) {
			$hosting_bytes_free = $iwp_backup_core->get_hosting_disk_quota_free();
			if (is_array($hosting_bytes_free)) {
				$perc = round(100*$hosting_bytes_free[1]/(max($hosting_bytes_free[2], 1)), 1);
				$quota_free_msg = sprintf('Free disk space in account: %s (%s used)', round($hosting_bytes_free[3]/1048576, 1)." MB", "$perc %");
				$iwp_backup_core->log($quota_free_msg);
				if ($hosting_bytes_free[3] < 1048576*50) {
					$quota_low = true;
					$quota_free_mb = round($hosting_bytes_free[3]/1048576, 1);
					$iwp_backup_core->log(sprintf(__('Your free space in your hosting account is very low - only %s Mb remain', 'InfiniteWP'), $quota_free_mb), 'warning', 'lowaccountspace'.$quota_free_mb);
				}
			}
		}

		// Always warn of this
		if (strpos($msg, 'File Size Limit Exceeded') !== false && 'IWP_MMB_BinZip' == $this->use_zip_object) {
			$iwp_backup_core->log(sprintf(__('The zip engine returned the message: %s.', 'InfiniteWP'), 'File Size Limit Exceeded'). __('Go here for more information.','InfiniteWP').' Please define this constant on your wp-config file define("IWP_NO_BINZIP", true);', 'warning', 'zipcloseerror-filesizelimit');
		} elseif ($warn) {
			$warn_msg = __('A zip error occurred', 'InfiniteWP').' - ';
			if (!empty($quota_low)) {
				$warn_msg = sprintf(__('your web hosting account appears to be full; please see: %s', 'InfiniteWP'), 'Increase your server space to backup. Make sure your web server space should be twice the site size');
			} else {
				$warn_msg .= __('check your log for more details.', 'InfiniteWP');
			}
			$iwp_backup_core->log($warn_msg, 'warning', 'zipcloseerror-'.$this->whichone);
		}

		$iwp_backup_core->log("The attempt to close the zip file returned an error ($msg). List of files we were trying to add follows (check their permissions).");

		foreach ($files_zipadded_since_open as $ffile) {
			$iwp_backup_core->log("File: ".$ffile['addas']." (exists: ".(int)@file_exists($ffile['file']).", is_readable: ".(int)@is_readable($ffile['file'])." size: ".@filesize($ffile['file']).')', 'notice', false, true);
		}
	}

	private function bump_index() {
		global $iwp_backup_core;
		$youwhat = $this->whichone;

		$timetaken = max(microtime(true)-$this->zip_microtime_start, 0.000001);

		$itext = ($this->index == 0) ? '' : ($this->index+1);
		$full_path = $this->zip_basename.$itext.'.zip';
		
		$checksums = $iwp_backup_core->which_checksums();
		
		$checksum_description = '';
		
		foreach ($checksums as $checksum) {
		
			$cksum = hash_file($checksum, $full_path.'.tmp');
			$iwp_backup_core->jobdata_set($checksum.'-'.$youwhat.$this->index, $cksum);
			if ($checksum_description) $checksum_description .= ', ';
			$checksum_description .= "$checksum: $cksum";
		
		}

		$next_full_path = $this->zip_basename.($this->index+2).'.zip';
		# We touch the next zip before renaming the temporary file; this indicates that the backup for the entity is not *necessarily* finished
		touch($next_full_path.'.tmp');

		if (file_exists($full_path.'.tmp') && filesize($full_path.'.tmp') > 0) {
			if (!rename($full_path.'.tmp', $full_path)) {
				$iwp_backup_core->log("Rename failed for $full_path.tmp");
			} else {
				$iwp_backup_core->something_useful_happened();
			}
		}
		
		$kbsize = filesize($full_path)/1024;
		$rate = round($kbsize/$timetaken, 1);
		$iwp_backup_core->log("Created ".$this->whichone." zip (".$this->index.") - ".round($kbsize,1)." KB in ".round($timetaken,1)." s ($rate KB/s) (checksums: $checksum_description)");
		$this->zip_microtime_start = microtime(true);

		# No need to add $itext here - we can just delete any temporary files for this zip
		$iwp_backup_core->clean_temporary_files('_'.$iwp_backup_core->nonce."-".$youwhat, 600);

		$this->index++;
		$this->job_file_entities[$youwhat]['index'] = $this->index;
		$iwp_backup_core->jobdata_set('job_file_entities', $this->job_file_entities);
	}

	/**
	 * Returns the member of the array with key (int)0, as a new array. This function is used as a callback for array_map().
	 *
	 * @param Array $a - the array
	 *
	 * @return Array - with keys 'name' and 'type'
	 */
	private function cb_get_name_base_type($a) {
		return array('name' => $a[0], 'type' => 'BASE TABLE');
	}

	/**
	 * Returns the members of the array with keys (int)0 and (int)1, as part of a new array.
	 *
	 * @param Array $a - the array
	 *
	 * @return Array - keys are 'name' and 'type'
	 */
	private function cb_get_name_type($a) {
		return array('name' => $a[0], 'type' => $a[1]);
	}

	/**
	 * Returns the member of the array with key (string)'name'. This function is used as a callback for array_map().
	 *
	 * @param Array $a - the array
	 *
	 * @return Mixed - the value with key (string)'name'
	 */
	private function cb_get_name($a) {
		return $a['name'];
	}

}

class IWP_MMB_WPDB_OtherDB extends wpdb {
	// This adjusted bail() does two things: 1) Never dies and 2) logs in the UD log
	public function bail( $message, $error_code = '500' ) {
		global $iwp_backup_core;
		if ('db_connect_fail' == $error_code) $message = 'Connection failed: check your access details, that the database server is up, and that the network connection is not firewalled.';
		$iwp_backup_core->log("WPDB_OtherDB error: $message ($error_code)");
		# Now do the things that would have been done anyway
		if ( class_exists( 'WP_Error' ) )
			$this->error = new WP_Error($error_code, $message);
		else
			$this->error = $message;
		return false;
	}
}

