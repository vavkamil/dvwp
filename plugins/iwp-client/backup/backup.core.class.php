<?php

if ( ! defined('ABSPATH') )
	die();

class IWP_MMB_Backup_Core {

	public $errors = array();
	public $nonce;
	public $logfile_name = "";
	public $logfile_handle = false;
	public $backup_time;
	public $job_time_ms;
	public $version;
	public $opened_log_time;
	private $iwp_backup_dir;
	public $blog_name;

	private $jobdata;

	public $something_useful_happened = false;

	// Used to schedule resumption attempts beyond the tenth, if needed
	public $current_resumption;
	public $newresumption_scheduled = false;

	public $cpanel_quota_readable = false;

	public $error_reporting_stop_when_logged = false;
	
	private $combine_jobs_around;

	public function __construct() {

		# The two actions which we schedule upon
		$this->version = IWP_MMB_CLIENT_VERSION;
		add_action('IWP_backup', array($this, 'backup_files'));
		add_action('IWP_backup_database', array($this, 'backup_database'));
		add_filter('IWP_backupable_file_entities_final', array($this, 'backupable_file_entities_final'), 10, 3);


		# The three actions that can be called from "Backup Now"
		add_action('IWP_backupnow_backup', array($this, 'backupnow_files'));
		add_action('IWP_backupnow_backup_database', array($this, 'backupnow_database'));
		add_action('IWP_backupnow_backup_all', array($this, 'backup_all'));
		add_action('IWP_backup_resume', array($this, 'backup_resume'), 10, 3);
		# backup_all as an action is legacy (Oct 2013) - there may be some people who wrote cron scripts to use it
		add_action('IWP_backup_all', array($this, 'backup_all'));

		add_filter('schedule_event', array($this, 'schedule_event'));
		add_filter('IWP_dropbox_modpath', array($this, 'dropbox_modpath'),10, 2);

	}

	// Ugly, but necessary to prevent debug output breaking the conversation when the user has debug turned on
	private function no_deprecation_warnings_on_php7() {
		// PHP_MAJOR_VERSION is defined in PHP 5.2.7+
		// We don't test for PHP > 7 because the specific deprecated element will be removed in PHP 8 - and so no warning should come anyway (and we shouldn't suppress other stuff until we know we need to).
		if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION == 7) {
			$old_level = error_reporting();
			$new_level = $old_level & ~E_DEPRECATED;
			if ($old_level != $new_level) error_reporting($new_level);
			$this->no_deprecation_warnings = true;
		}
	}

	/**
	 * This converts array-style options (i.e. late 2013-onwards) to
	 * 2017-style multi-array-style options.
	 *
	 * N.B. Don't actually call this on any particular method's options
	 * until the functions which read the options can cope!
	 * 
	 * N.B. Until the UI is changed (DOM changed), saving settings will
	 * revert to the previous format. But that does not break anything.
	 * 
	 * Don't call for settings that aren't array-style. You may lose
	 * the settings if you do.
	 *
	 * It is safe to call this if you are not sure if the options are
	 * already updated.
	 *
	 * @param String $method - the method identifier
	 * 
	 * @returns Array|WP_Error - returns the new options, or a WP_Error if it failed
	 */
	public function update_remote_storage_options_format($method) {
		// Prevent recursion
		static $already_active = false;
		
		if ($already_active) return new WP_Error('recursion', 'IWP_MMB_Backup_Core::update_remote_storage_options_format() was called in a loop. This is usually caused by an options filter failing to correctly process a "recursion" error code');
	
		if (!file_exists($GLOBALS['iwp_mmb_plugin_dir'].'/backup/'.$method.'.php')) return new WP_Error('no_such_method', 'Remote storage method not found', $method);
		
		// Sanity/inconsistency check
		$settings_keys = $this->get_settings_keys();
		
		$method_key = 'IWP_'.$method;
		
		if (!in_array($method_key, $settings_keys)) return new WP_Error('no_such_setting', 'Setting not found for this method', $method);
	
		$current_setting = IWP_MMB_Backup_Options::get_iwp_backup_option($method_key, array());
		
		if (!is_array($current_setting) && false !== $current_setting) return new WP_Error('format_unrecognised', 'Settings format not recognised', array('method' => $method, 'current_setting' => $current_setting));

		// Already converted?
		if (isset($current_setting['version'])) return $current_setting;
		
		$new_setting = $this->wrap_remote_storage_options($current_setting);
		
		$already_active = true;
		$updated = IWP_MMB_Backup_Options::update_iwp_backup_option($method_key, $new_setting);
		$already_active = false;
		
		if ($updated) {
			return $new_setting;
		} else {
			return WP_Error('save_failed', 'Saving the options in the new format failed', array('method' => $method, 'current_setting' => $new_setting));
		}
	
	}

	/**
	 * This method will update the old style remote storage options to the new style (Apr 2017) if the user has imported a old style version of settings
	 *
	 * @param  Array $options - The remote storage options settings array
	 * @return Array          - The updated remote storage options settings array
	 */
	public function wrap_remote_storage_options($options) {
		// Already converted?
		if (isset($options['version'])) return $options;
		
		// Cryptographic randomness not required. The prefix helps avoid potential for type-juggling issues.
		$uuid = 's-'.md5(rand().uniqid().microtime(true));
		
		$new_setting = array(
			'version' => 1,
		);
		
		if (!is_array($options)) $options = array();

		$new_setting['settings'] = array($uuid => $options);

		return $new_setting;
	}

	// Returns the number of bytes free, if it can be detected; otherwise, false
	// Presently, we only detect CPanel. If you know of others, then feel free to contribute!
	public function get_hosting_disk_quota_free() {
		if (!@is_dir('/usr/local/cpanel') || $this->detect_safe_mode() || !function_exists('popen') || (!@is_executable('/usr/local/bin/perl') && !@is_executable('/usr/local/cpanel/3rdparty/bin/perl')) || (defined('IWP_SKIP_CPANEL_QUOTA_CHECK') && IWP_SKIP_CPANEL_QUOTA_CHECK)) return false;

		$perl = (@is_executable('/usr/local/cpanel/3rdparty/bin/perl')) ? '/usr/local/cpanel/3rdparty/bin/perl' : '/usr/local/bin/perl';

		$exec = "IWPKEY=IWP $perl ".$GLOBALS['iwp_mmb_plugin_dir']."/lib/get-cpanel-quota-usage.pl";

		$handle = @popen($exec, 'r');
		if (!is_resource($handle)) return false;

		$found = false;
		$lines = 0;
		while (false === $found && !feof($handle) && $lines<100) {
			$lines++;
			$w = fgets($handle);
			# Used, limit, remain
			if (preg_match('/RESULT: (\d+) (\d+) (\d+) /', $w, $matches)) { $found = true; }
		}
		$ret = pclose($handle);
		if (false === $found ||$ret != 0) return false;

		if ((int)$matches[2]<100 || ($matches[1] + $matches[3] != $matches[2])) return false;

		$this->cpanel_quota_readable = true;

		return $matches;
	}

	public function last_modified_log() {
		$iwp_backup_dir = $this->backups_dir_location();

		$log_file = '';
		$mod_time = false;
		$nonce = '';

		if ($handle = @opendir($iwp_backup_dir)) {
			while (false !== ($entry = readdir($handle))) {
				// The latter match is for files created internally by zipArchive::addFile
				if (preg_match('/^log\.([a-z0-9]+)\.txt$/i', $entry, $matches)) {
					$mtime = filemtime($iwp_backup_dir.'/'.$entry);
					if ($mtime > $mod_time) {
						$mod_time = $mtime;
						$log_file = $iwp_backup_dir.'/'.$entry;
						$nonce = $matches[1];
					}
				}
			}
			@closedir($handle);
		}

		return array($mod_time, $log_file, $nonce);
	}

	public function register_wp_http_option_hooks($register = true) {
		if ($register) {
			add_filter('http_request_args', array($this, 'modify_http_options'));
			add_action('http_api_curl', array($this, 'http_api_curl'));
		} else {
			remove_filter('http_request_args', array($this, 'modify_http_options'));
			remove_action('http_api_curl', array($this, 'http_api_curl'));
		}
	}

	public function http_api_curl($handle) {
		if (defined('IWP_IPV4_ONLY') && IWP_IPV4_ONLY) {
			curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}
		return $handle;
	}

	public function modify_http_options($opts) {

		if (!is_array($opts)) return $opts;

		if (!IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_ssl_useservercerts')) $opts['sslcertificates'] = $GLOBALS['iwp_mmb_plugin_dir'].'/lib/cacert.pem';

		$opts['sslverify'] = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_ssl_disableverify') ? false : true;

		return $opts;

	}

	public function get_table_prefix($allow_override = false) {
		global $wpdb;
		if (is_multisite() && !defined('MULTISITE')) {
			# In this case (which should only be possible on installs upgraded from pre WP 3.0 WPMU), $wpdb->get_blog_prefix() cannot be made to return the right thing. $wpdb->base_prefix is not explicitly marked as public, so we prefer to use get_blog_prefix if we can, for future compatibility.
			$prefix = $wpdb->base_prefix;
		} else {
			$prefix = $wpdb->get_blog_prefix(0);
		}
		return ($allow_override) ? apply_filters('IWP_get_table_prefix', $prefix) : $prefix;
	}

	public function siteid() {
		$sid = get_site_option('IWP-addons_siteid');
		if (!is_string($sid) || empty($sid)) {
			$sid = md5(rand().microtime(true).home_url());
			update_site_option('IWP-addons_siteid', $sid);
		}
		return $sid;
	}

	public function plugins_loaded() {
		
		// The Google Analyticator plugin does something horrible: loads an old version of the Google SDK on init, always - which breaks us
		if ((defined('DOING_CRON') && DOING_CRON) || (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['subaction']) && 'backupnow' == $_REQUEST['subaction']) ) {
			remove_action('init', 'ganalyticator_stats_init');
			// Appointments+ does the same; but provides a cleaner way to disable it
			@define('APP_GCAL_DISABLE', true);
		}
		
	}
	
	// Cleans up temporary files found in the InfinteWP directory (and some in the site root - pclzip)
	// Always cleans up temporary files over 12 hours old.
	// With parameters, also cleans up those.
	// Also cleans out old job data older than 12 hours old (immutable value)
	// include_cachelist also looks to match any files of cached file analysis data
	public function clean_temporary_files($match = '', $older_than = 43200, $include_cachelist = false) {
		# Clean out old job data
		if ($older_than > 10000) {
			global $wpdb;

			$all_jobs = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'IWP_jobdata_%'", ARRAY_A);
			foreach ($all_jobs as $job) {
				$val = maybe_unserialize($job['option_value']);
				# TODO: Can simplify this after a while (now all jobs use job_time_ms) - 1 Jan 2014
				$delete = false;
				if (!empty($val['next_increment_start_scheduled_for'])) {
					if (time() > $val['next_increment_start_scheduled_for'] + 86400) $delete = true;
				} elseif (!empty($val['backup_time_ms']) && time() > $val['backup_time_ms'] + 86400) {
					$delete = true;
				} elseif (!empty($val['job_time_ms']) && time() > $val['job_time_ms'] + 86400) {
					$delete = true;
				} elseif (!empty($val['job_type']) && 'backup' != $val['job_type'] && empty($val['backup_time_ms']) && empty($val['job_time_ms'])) {
					$delete = true;
				}
				if ($delete) delete_option($job['option_name']);
			}
		}
		$iwp_backup_dir = $this->backups_dir_location();
		$now_time=time();
		if ($handle = opendir($iwp_backup_dir)) {
			while (false !== ($entry = readdir($handle))) {
				$manifest_match = preg_match("/^udmanifest$match\.json$/i", $entry);
				// This match is for files created internally by zipArchive::addFile
				$ziparchive_match = preg_match("/$match([0-9]+)?\.zip\.tmp\.([A-Za-z0-9]){6}?$/i", $entry);
				// zi followed by 6 characters is the pattern used by /usr/bin/zip on Linux systems. It's safe to check for, as we have nothing else that's going to match that pattern.
				$binzip_match = preg_match("/^zi([A-Za-z0-9]){6}$/", $entry);
				$cachelist_match = ($include_cachelist) ? preg_match("/$match-cachelist-.*.tmp$/i", $entry) : false;
				$browserlog_match = preg_match('/^log\.[0-9a-f]+-browser\.txt$/', $entry);
				# Temporary files from the database dump process - not needed, as is caught by the catch-all
				# $table_match = preg_match("/${match}-table-(.*)\.table(\.tmp)?\.gz$/i", $entry);
				# The gz goes in with the txt, because we *don't* want to reap the raw .txt files
				if ((preg_match("/$match\.(tmp|table|txt\.gz)(\.gz)?$/i", $entry) || $cachelist_match || $ziparchive_match || $binzip_match || $manifest_match || $browserlog_match) && is_file($iwp_backup_dir.'/'.$entry) && !strrpos($entry,'backup_meta')) {
					// We delete if a parameter was specified (and either it is a ZipArchive match or an order to delete of whatever age), or if over 12 hours old
					if ((($match || $match == '') && ($ziparchive_match || $binzip_match || $cachelist_match || $manifest_match || 0 == $older_than) && $now_time-filemtime($iwp_backup_dir.'/'.$entry) >= $older_than) || $now_time-filemtime($iwp_backup_dir.'/'.$entry)>43200) {
						$this->log("Deleting old temporary file: $entry");
						@unlink($iwp_backup_dir.'/'.$entry);
					}
				}
			}
			@closedir($handle);
		}

		foreach (array(ABSPATH, ABSPATH.'wp-admin/', $iwp_backup_dir.'/') as $path) {
			if ($handle = opendir($path)) {
				while (false !== ($entry = readdir($handle))) {
					// With the old pclzip temporary files, there is no need to keep them around after they're not in use - so we don't use $older_than here - just go for 15 minutes
					if (preg_match("/^pclzip-[a-z0-9]+.tmp$/", $entry) && $now_time-filemtime($path.$entry) >= 900) {
						$this->log("Deleting old PclZip temporary file: $entry");
						@unlink($path.$entry);
					}
				}
				@closedir($handle);
			}
		}
	}

	public function backup_time_nonce($nonce = false) {
		$this->job_time_ms = microtime(true);
		$this->backup_time = time();
		if (false === $nonce) $nonce = substr(md5(time().rand()), 20);
		$this->nonce = $nonce;
		return $nonce;
	}
	
	public function get_wordpress_version() {
		static $got_wp_version = false;
		if (!$got_wp_version) {
			global $wp_version;
			@include(ABSPATH.WPINC.'/version.php');
			$got_wp_version = $wp_version;
		}
		return $got_wp_version;
	}

	/**
	 * Opens the log file, writes a standardised header, and stores the resulting name and handle in the class variables logfile_name/logfile_handle/opened_log_time (and possibly backup_is_already_complete)
	 * 
	 * @param string $nonce - Used in the log file name to distinguish it from other log files. Should be the job nonce.
	 * @returns void
	 */
	public function logfile_open($nonce) {

		$iwp_backup_dir = $this->backups_dir_location();
		$this->logfile_name =  $iwp_backup_dir."/log.$nonce.txt";

		if (file_exists($this->logfile_name)) {
			$seek_to = max((filesize($this->logfile_name) - 340), 1);
			$handle = fopen($this->logfile_name, 'r');
			if (is_resource($handle)) {
				// Returns 0 on success
				if (0 === @fseek($handle, $seek_to)) {
					$bytes_back = filesize($this->logfile_name) - $seek_to;
					# Return to the end of the file
					$read_recent = fread($handle, $bytes_back);
					# Move to end of file - ought to be redundant
					if (false !== strpos($read_recent, ') The backup apparently succeeded') && false !== strpos($read_recent, 'and is now complete')) {
						$this->backup_is_already_complete = true;
					}
				}
				fclose($handle);
			}
		}

		$this->logfile_handle = fopen($this->logfile_name, 'a');

		$this->opened_log_time = microtime(true);
		
		$this->write_log_header(array($this, 'log'));
		
	}
	
	/**
	 * Writes a standardised header to the log file, using the specified logging function, which needs to be compatible with (or to be) InfiniteWP::log()
	 * 
	 * @param callable $logging_function
	 */
	public function write_log_header($logging_function) {
		
		global $wpdb;

		$iwp_backup_dir = $this->backups_dir_location();

		call_user_func($logging_function, 'Opened log file at time: '.date('r').' on '.network_site_url());
		
		$wp_version = $this->get_wordpress_version();
		$mysql_version = $wpdb->db_version();
		$safe_mode = $this->detect_safe_mode();

		$memory_limit = ini_get('memory_limit');
		$memory_usage = round(@memory_get_usage(false)/1048576, 1);
		$memory_usage2 = round(@memory_get_usage(true)/1048576, 1);

		// Attempt to raise limit to avoid false positives
		@set_time_limit(IWP_SET_TIME_LIMIT);
		$max_execution_time = (int)@ini_get("max_execution_time");

		$logline = "InfiniteWP WordPress plugin (https://infinitewp.com): ".$this->version." WP: ".$wp_version." PHP: ".phpversion()." (".PHP_SAPI.", ".@php_uname().") MySQL: $mysql_version WPLANG: ".get_locale()." Server: ".$_SERVER["SERVER_SOFTWARE"]." safe_mode: $safe_mode max_execution_time: $max_execution_time memory_limit: $memory_limit (used: ${memory_usage}M | ${memory_usage2}M) multisite: ".(is_multisite() ? 'Y' : 'N')." openssl: ".(defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'N')." mcrypt: ".(function_exists('mcrypt_encrypt') ? 'Y' : 'N')." LANG: ".getenv('LANG')." ZipArchive::addFile: ";

		// method_exists causes some faulty PHP installations to segfault, leading to support requests
		if (version_compare(phpversion(), '5.2.0', '>=') && extension_loaded('zip')) {
			$logline .= 'Y';
		} else {
			$logline .= (class_exists('ZipArchive') && method_exists('ZipArchive', 'addFile')) ? "Y" : "N";
		}

		if (0 === $this->current_resumption) {
			$memlim = $this->memory_check_current();
			if ($memlim<65 && $memlim>0) {
				$this->log(sprintf(__('The amount of memory (RAM) allowed for PHP is very low (%s Mb) - you should increase it to avoid failures due to insufficient memory (consult your web hosting company for more help)', 'InfiniteWP'), round($memlim, 1)), 'warning', 'lowram');
			}
			if ($max_execution_time>0 && $max_execution_time<20) {
				call_user_func($logging_function, sprintf(__('The amount of time allowed for WordPress plugins to run is very low (%s seconds) - you should increase it to avoid backup failures due to time-outs (consult your web hosting company for more help - it is the max_execution_time PHP setting; the recommended value is %s seconds or more)', 'InfiniteWP'), $max_execution_time, 90), 'warning', 'lowmaxexecutiontime');
			}

		}

		call_user_func($logging_function, $logline);

		$hosting_bytes_free = $this->get_hosting_disk_quota_free();
		if (is_array($hosting_bytes_free)) {
			$perc = round(100*$hosting_bytes_free[1]/(max($hosting_bytes_free[2], 1)), 1);
			$quota_free = ' / '.sprintf('Free disk space in account: %s (%s used)', round($hosting_bytes_free[3]/1048576, 1)." MB", "$perc %");
			if ($hosting_bytes_free[3] < 1048576*50) {
				$quota_free_mb = round($hosting_bytes_free[3]/1048576, 1);
				call_user_func($logging_function, sprintf(__('Your free space in your hosting account is very low - only %s Mb remain', 'InfiniteWP'), $quota_free_mb), 'warning', 'lowaccountspace'.$quota_free_mb);
			}
		} else {
			$quota_free = '';
		}

		$disk_free_space = @disk_free_space($iwp_backup_dir);
		# == rather than === here is deliberate; support experience shows that a result of (int)0 is not reliable. i.e. 0 can be returned when the real result should be false.
		if ($disk_free_space == false) {
			call_user_func($logging_function, "Free space on disk containing InfiniteWP's temporary directory: Unknown".$quota_free);
		} else {
			call_user_func($logging_function, "Free space on disk containing InfiniteWP's temporary directory: ".round($disk_free_space/1048576, 1)." MB".$quota_free);
			$disk_free_mb = round($disk_free_space/1048576, 1);
			if ($disk_free_space < 50*1048576) call_user_func($logging_function, sprintf(__('Your free disk space is very low - only %s Mb remain', 'InfiniteWP'), round($disk_free_space/1048576, 1)), 'warning', 'lowdiskspace'.$disk_free_mb);
		}

	}

	/* Logs the given line, adding (relative) time stamp and newline
	Note these subtleties of log handling:
	- Messages at level 'error' are not logged to file - it is assumed that a separate call to log() at another level will take place. This is because at level 'error', messages are translated; whereas the log file is for developers who may not know the translated language. Messages at level 'error' are for the user.
	- Messages at level 'error' do not persist through the job (they are only saved with save_backup_history(), and never restored from there - so only the final save_backup_history() errors persist); we presume that either a) they will be cleared on the next attempt, or b) they will occur again on the final attempt (at which point they will go to the user). But...
	- ... messages at level 'warning' persist. These are conditions that are unlikely to be cleared, not-fatal, but the user should be informed about. The $uniq_id field (which should not be numeric) can then be used for warnings that should only be logged once
	$skip_dblog = true is suitable when there's a risk of excessive logging, and the information is not important for the user to see in the browser on the settings page
	
	The uniq_id field is also used with PHP event detection - it is set then to 'php_event' - which is useful for anything hooking the action to detect
	*/

	public function verify_free_memory($how_many_bytes_needed) {
		// This returns in MB
		$memory_limit = $this->memory_check_current();
		if (!is_numeric($memory_limit)) return false;
		$memory_limit = $memory_limit * 1048576;
		$memory_usage = round(@memory_get_usage(false)/1048576, 1);
		$memory_usage2 = round(@memory_get_usage(true)/1048576, 1);
		if ($memory_limit - $memory_usage > $how_many_bytes_needed && $memory_limit - $memory_usage2 > $how_many_bytes_needed) return true;
		return false;
	}

	/*
		$line - the log line
		$level - the log level: notice, warning, error. If suffixed with a hypen and a destination, then the default destination is changed too.
		$uniq_id - (string)each of these will only be logged once
		$skip_dblog - if true, then do not write to the database
	*/
	public function log($line, $level = 'notice', $uniq_id = false, $skip_dblog = false) {

		$destination = 'default';
		if (preg_match('/^([a-z]+)-([a-z]+)$/', $level, $matches)) {
			$level = $matches[1];
			$destination = $matches[2];
		}
	
		if ('error' == $level || 'warning' == $level) {
			if ('error' == $level && 0 == $this->error_count()) $this->log('An error condition has occurred for the first time during this job');
			if ($uniq_id) {
				$this->errors[$uniq_id] = array('level' => $level, 'message' => $line);
			} else {
				$this->errors[] = array('level' => $level, 'message' => $line);
			}
			# Errors are logged separately
			if ('error' == $level) return;
			# It's a warning
			$warnings = $this->jobdata_get('warnings');
			if (!is_array($warnings)) $warnings = array();
			if ($uniq_id) {
				$warnings[$uniq_id] = $line;
			} else {
				$warnings[] = $line;
			}
			$this->jobdata_set('warnings', $warnings);
		}

		if (false === ($line = apply_filters('IWP_logline', $line, $this->nonce, $level, $uniq_id, $destination))) return;

		if ($this->logfile_handle) {
			# Record log file times relative to the backup start, if possible
			$rtime = (!empty($this->job_time_ms)) ? microtime(true)-$this->job_time_ms : microtime(true)-$this->opened_log_time;
			fwrite($this->logfile_handle, sprintf("%08.03f", round($rtime, 3))." (".$this->current_resumption.") ".(('notice' != $level) ? '['.ucfirst($level).'] ' : '').$line."\n");
		}

		switch ($this->jobdata_get('job_type')) {
			case 'download':
				// Download messages are keyed on the job (since they could be running several), and type
				// The values of the POST array were checked before
				$findex = empty($_POST['findex']) ? 0 : $_POST['findex'];

				if (!empty($_POST['timestamp']) && !empty($_POST['type'])) $this->jobdata_set('dlmessage_'.$_POST['timestamp'].'_'.$_POST['type'].'_'.$findex, $line);

				break;
			case 'restore':
				#if ('debug' != $level) echo $line."\n";
				break;
			default:
				if (!$skip_dblog && 'debug' != $level) IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_lastmessage', $line." (".date_i18n('M d H:i:s').")", false);
				break;
		}

		if (defined('IWP_BROWSERLOG_CONSOLELOG')) print $line."\n";
		if (defined('IWP_BROWSERLOG_BROWSERLOG')) print htmlentities($line)."<br>\n";
	}

	public function log_removewarning($uniq_id) {
		$warnings = $this->jobdata_get('warnings');
		if (!is_array($warnings)) $warnings=array();
		unset($warnings[$uniq_id]);
		$this->jobdata_set('warnings', $warnings);
		unset($this->errors[$uniq_id]);
	}

	# For efficiency, you can also feed false or a string into this function
	public function log_wp_error($err, $echo = false, $logerror = false) {
		if (false === $err) return false;
		if (is_string($err)) {
			$this->log("Error message: $err");
			if ($echo) $this->log(sprintf(__('Error: %s', 'InfiniteWP'), $err), 'notice-warning');
			if ($logerror) $this->log($err, 'error');
			return false;
		}
		foreach ($err->get_error_messages() as $msg) {
			$this->log("Error message: $msg");
			if ($echo) $this->log(sprintf(__('Error: %s', 'InfiniteWP'), $msg), 'notice-warning');
			if ($logerror) $this->log($msg, 'error');
		}
		$codes = $err->get_error_codes();
		if (is_array($codes)) {
			foreach ($codes as $code) {
				$data = $err->get_error_data($code);
				if (!empty($data)) {
					$ll = (is_string($data)) ? $data : serialize($data);
					$this->log("Error data (".$code."): ".$ll);
				}
			}
		}
		# Returns false so that callers can return with false more efficiently if they wish
		return false;
	}

	public function get_max_packet_size() {
		global $wpdb;
		$mp = (int)$wpdb->get_var("SELECT @@session.max_allowed_packet");
		# Default to 1MB
		$mp = (is_numeric($mp) && $mp > 0) ? $mp : 1048576;
		# 32MB
		if ($mp < 33554432) {
			$save = $wpdb->show_errors(false);
			$req = @$wpdb->query("SET GLOBAL max_allowed_packet=33554432");
			$wpdb->show_errors($save);
			if (!$req) $this->log("Tried to raise max_allowed_packet from ".round($mp/1048576,1)." MB to 32 MB, but failed (".$wpdb->last_error.", ".serialize($req).")");
			$mp = (int)$wpdb->get_var("SELECT @@session.max_allowed_packet");
			# Default to 1MB
			$mp = (is_numeric($mp) && $mp > 0) ? $mp : 1048576;
		}
		$this->log("Max packet size: ".round($mp/1048576, 1)." MB");
		return $mp;
	}

	# Q. Why is this abstracted into a separate function? A. To allow poedit and other parsers to pick up the need to translate strings passed to it (and not pick up all of those passed to log()).
	# 1st argument = the line to be logged (obligatory)
	# Further arguments = parameters for sprintf()
	public function log_e() {
		$args = func_get_args();
		# Get first argument
		$pre_line = array_shift($args);
		# Log it whilst still in English
		if (is_wp_error($pre_line)) {
			$this->log_wp_error($pre_line);
		} else {
			// Now run (v)sprintf on it, using any remaining arguments. vsprintf = sprintf but takes an array instead of individual arguments
			$this->log(vsprintf($pre_line, $args));
			// This is slightly hackish, in that we have no way to use a different level or destination. In that case, the caller should instead call log() twice with different parameters, instead of using this convenience function.
			$this->log(vsprintf(__($pre_line, 'InfiniteWP'), $args), 'notice-restore');
		}
	}

	// This function is used by cloud methods to provide standardised logging, but more importantly to help us detect that meaningful activity took place during a resumption run, so that we can schedule further resumptions if it is worthwhile
	public function record_uploaded_chunk($percent, $extra = '', $file_path = false, $log_it = true) {

		// Touch the original file, which helps prevent overlapping runs
		if ($file_path) touch($file_path);

		// What this means in effect is that at least one of the files touched during the run must reach this percentage (so lapping round from 100 is OK)
		if ($percent > 0.7 * ($this->current_resumption - max($this->jobdata_get('uploaded_lastreset'), 9))) $this->something_useful_happened();

		// Log it
		global $IWP_backup;
		$log = (!empty($IWP_backup->current_service)) ? ucfirst($IWP_backup->current_service)." chunked upload: $percent % uploaded" : '';
		if ($log && $log_it) $this->log($log.(($extra) ? " ($extra)" : ''));
		// If we are on an 'overtime' resumption run, and we are still meaningfully uploading, then schedule a new resumption
		// Our definition of meaningful is that we must maintain an overall average of at least 0.7% per run, after allowing 9 runs for everything else to get going
		// i.e. Max 100/.7 + 9 = 150 runs = 760 minutes = 12 hrs 40, if spaced at 5 minute intervals. However, our algorithm now decreases the intervals if it can, so this should not really come into play
		// If they get 2 minutes on each run, and the file is 1GB, then that equals 10.2MB/120s = minimum 59KB/s upload speed required

		$upload_status = $this->jobdata_get('uploading_substatus');
		if (is_array($upload_status)) {
			$upload_status['p'] = $percent/100;
			$this->jobdata_set('uploading_substatus', $upload_status);
		}

	}

	/**
	 * Method for helping remote storage methods to upload files in chunks without needing to duplicate all the overhead
	 *
	 * @param	string	$file	the full path to the file
	 * @param	object	$caller	the object to call back to do the actual network API calls; needs to have a chunked_upload() method.
	 * @param	string	$cloudpath	this is passed back to the callback function; within this function, it is used only for logging
	 * @param	string	$logname	the prefix used on log lines. Also passed back to the callback function.
	 * @param	integer	$chunk_size	the size, in bytes, of each upload chunk
	 * @param	integer	$uploaded_size	how many bytes have already been uploaded. This is passed back to the callback function; within this method, it is only used for logging.
	 * @param	boolean	$singletons	when the file, given the chunk size, would only have one chunk, should that be uploaded (true), or instead should 1 be returned (false) ?
	*/
	public function chunked_upload($caller, $file, $cloudpath, $logname, $chunk_size, $uploaded_size, $singletons = false) {

		$fullpath = $this->backups_dir_location().'/'.$file;
		$orig_file_size = filesize($fullpath);
		if ($uploaded_size >= $orig_file_size) return true;

		$chunks = floor($orig_file_size / $chunk_size);
		// There will be a remnant unless the file size was exactly on a chunk boundary
		if ($orig_file_size % $chunk_size > 0) $chunks++;

		$this->log("$logname upload: $file (chunks: $chunks, size: $chunk_size) -> $cloudpath ($uploaded_size)");

		if (0 == $chunks) {
			return 1;
		} elseif ($chunks < 2 && !$singletons) {
			return 1;
		} else {

			if (false == ($fp = @fopen($fullpath, 'rb'))) {
				$this->log("$logname: failed to open file: $fullpath");
				$this->log("$file: ".sprintf(__('%s Error: Failed to open local file','InfiniteWP'), $logname), 'error');
				return false;
			}

			$errors_so_far = 0;
			$upload_start = 0;
			$upload_end = -1;
			$chunk_index = 1;
			// The file size minus one equals the byte offset of the final byte
			$upload_end = min($chunk_size - 1, $orig_file_size - 1);
			
			while ($upload_start < $orig_file_size) {

				// Don't forget the +1; otherwise the last byte is omitted
				$upload_size = $upload_end - $upload_start + 1;

				if ($upload_start) fseek($fp, $upload_start);

				/*
				* Valid return values for $uploaded are many, as the possibilities have grown over time.
				* This could be cleaned up; but, it works, and it's not hugely complex.
				*
				* WP_Error : an error occured. The only permissible codes are: reduce_chunk_size (only on the first chunk), try_again
				* (bool)true : What was requested was done
				* (int)1 : What was requested was done, but do not log anything
				* (bool)false : There was an error
				* (Object) : Properties:
				*  (bool)log: (bool) - if absent, defaults to true
				*  (int)new_chunk_size: advisory amount for the chunk size for future chunks
				*  NOT IMPLEMENTED: (int)bytes_uploaded: Actual number of bytes uploaded (needs to be positive - o/w, should return an error instead)
				*  
				* N.B. Consumers should consult $fp and $upload_start to get data; they should not re-calculate from $chunk_index, which is not an indicator of file position.
				*/
				$uploaded = $caller->chunked_upload($file, $fp, $chunk_index, $upload_size, $upload_start, $upload_end, $orig_file_size);

				// Try again? (Just once - added in 1.12.6 (can make more sophisticated if there is a need))
				if (is_wp_error($uploaded) && 'try_again' == $uploaded->get_error_code()) {
					// Arbitrary wait
					sleep(3);
					$this->log("Re-trying after wait (to allow apparent inconsistency to clear)");
					$uploaded = $caller->chunked_upload($file, $fp, $chunk_index, $upload_size, $upload_start, $upload_end, $orig_file_size);
				}
				
				// This is the only other supported case of a WP_Error - otherwise, a boolean must be returned
				// Note that this is only allowed on the first chunk. The caller is responsible to remember its chunk size if it uses this facility.
				if (1 == $chunk_index && is_wp_error($uploaded) && 'reduce_chunk_size' == $uploaded->get_error_code() && false != ($new_chunk_size = $uploaded->get_error_data()) && is_numeric($new_chunk_size)) {
					$this->log("Re-trying with new chunk size: ".$new_chunk_size);
					return $this->chunked_upload($caller, $file, $cloudpath, $logname, $new_chunk_size, $uploaded_size, $singletons);
				}
				
				$uploaded_amount = $chunk_size;
				
				/*
				// Not using this approach for now. Instead, going to allow the consumers to increase the next chunk size
				if (is_object($uploaded) && isset($uploaded->bytes_uploaded)) {
					if (!$uploaded->bytes_uploaded) {
						$uploaded = false;
					} else {
						$uploaded_amount = $uploaded->bytes_uploaded;
						$uploaded = (!isset($uploaded->log) || $uploaded->log) ? true : 1;
					}
				}
				*/
				if (is_object($uploaded) && isset($uploaded->new_chunk_size)) {
					if ($uploaded->new_chunk_size >= 1048576) $new_chunk_size = $uploaded->new_chunk_size;
					$uploaded = (!isset($uploaded->log) || $uploaded->log) ? true : 1;
				}
				
				if ($uploaded) {
					$perc = round(100*($upload_end + 1)/max($orig_file_size, 1), 1);
					// Consumers use a return value of (int)1 (rather than (bool)true) to suppress logging
					$log_it = ($uploaded === 1) ? false : true;
					$this->record_uploaded_chunk($perc, $chunk_index, $fullpath, $log_it);
					
					// $uploaded_bytes = $upload_end + 1;
					
				} else {
					$errors_so_far++;
					if ($errors_so_far >= 3) { @fclose($fp); return false; }
				}
				
				$chunk_index++;
				$upload_start = $upload_end + 1;
				$upload_end += isset($new_chunk_size) ? $uploaded_amount + $new_chunk_size - $chunk_size : $uploaded_amount;
				$upload_end = min($upload_end, $orig_file_size - 1);

			}

			@fclose($fp);

			if ($errors_so_far) return false;

			// All chunks are uploaded - now combine the chunks
			$ret = true;
			if (method_exists($caller, 'chunked_upload_finish')) {
				$ret = $caller->chunked_upload_finish($file);
				if (!$ret) {
					$this->log("$logname - failed to re-assemble chunks (".$e->getMessage().')');
					$this->log(sprintf(__('%s error - failed to re-assemble chunks', 'InfiniteWP'), $logname), 'error');
				}
			}
			if ($ret) {
				$this->log("$logname upload: success");
				#  calls this itself
				if (!is_a($caller, 'IWP_MMB_Addons_RemoteStorage_sftp')) $this->uploaded_file($file);
			}

			return $ret;

		}
	}

	/**
	 * Provides a convenience function allowing remote storage methods to download a file in chunks, without duplicated overhead.
	 * 
	 * @param string $file - The basename of the file being downloaded
	 * @param object $method - This remote storage method object needs to have a chunked_download() method to call back
	 * @param integer $remote_size - The size, in bytes, of the object being downloaded
	 * @param boolean $manually_break_up - Whether to break the download into multiple network operations (rather than just issuing a GET with a range beginning at the end of the already-downloaded data, and carrying on until it times out)
	 * @param * $passback - A value to pass back to the callback function
	 * @param integer $chunk_size - Break up the download into chunks of this number of bytes. Should be set if and only if $manually_break_up is true.
	 */
	public function chunked_download($file, $method, $remote_size, $manually_break_up = false, $passback = null, $chunk_size = 1048576) {

		try {

			$fullpath = $this->backups_dir_location().'/'.$file;
			$start_offset = file_exists($fullpath) ? filesize($fullpath) : 0;

			if ($start_offset >= $remote_size) {
				$this->log("File is already completely downloaded ($start_offset/$remote_size)");
				return true;
			}

			// Some more remains to download - so let's do it
			// N.B. We use ftell(), which precludes us from using open in append-only ('a') mode - see https://php.net/manual/en/function.fopen.php
			if (!($fh = fopen($fullpath, 'c'))) {
				$this->log("Error opening local file: $fullpath");
				$this->log($file.": ".__("Error",'InfiniteWP').": ".__('Error opening local file: Failed to download','InfiniteWP'), 'error');
				return false;
			}

			$last_byte = ($manually_break_up) ? min($remote_size, $start_offset + $chunk_size ) : $remote_size;

			# This only affects logging
			$expected_bytes_delivered_so_far = true;

			while ($start_offset < $remote_size) {
				$headers = array();
				// If resuming, then move to the end of the file

				$requested_bytes = $last_byte-$start_offset;

				if ($expected_bytes_delivered_so_far) {
					$this->log("$file: local file is status: $start_offset/$remote_size bytes; requesting next $requested_bytes bytes");
				} else {
					$this->log("$file: local file is status: $start_offset/$remote_size bytes; requesting next chunk (${start_offset}-)");
				}

				if ($start_offset > 0 || $last_byte<$remote_size) {
					fseek($fh, $start_offset);
					// N.B. Don't alter this format without checking what relies upon it
					$last_byte_start = $last_byte - 1;
					$headers['Range'] = "bytes=$start_offset-$last_byte_start";
				}

				/*
				* The most common method is for the remote storage module to return a string with the results in it. In that case, the final $fh parameter is unused. However, since not all SDKs have that option conveniently, it is also possible to use the file handle and write directly to that; in that case, the method can either return the number of bytes written, or (boolean)true to infer it from the new file *pointer*.
				* The method is free to write/return as much data as it pleases.
				*/
				$ret = $method->chunked_download($file, $headers, $passback, $fh);
				if (true === $ret) {
					clearstatcache();
					// Some SDKs (including AWS/S3) close the resource
					// N.B. We use ftell(), which precludes us from using open in append-only ('a') mode - see https://php.net/manual/en/function.fopen.php
					if (is_resource($fh)) {
						$ret = ftell($fh);
					} else {
						$ret = filesize($fullpath);
						// fseek returns - on success
						if (false == ($fh = fopen($fullpath, 'c')) || 0 !== fseek($fh, $ret)) {
							$this->log("Error opening local file: $fullpath");
							$this->log($file.": ".__("Error",'InfiniteWP').": ".__('Error opening local file: Failed to download','InfiniteWP'), 'error');
							return false;
						}
					}
					if (is_integer($ret)) $ret -= $start_offset;
				}
				
				// Note that this covers a false code returned either by chunked_download() or by ftell.
				if (false === $ret) return false;
				
				$returned_bytes = is_integer($ret) ? $ret : strlen($ret);

				if ($returned_bytes > $requested_bytes || $returned_bytes < $requested_bytes - 1) $expected_bytes_delivered_so_far = false;

				if (!is_integer($ret) && !fwrite($fh, $ret)) throw new Exception('Write failure (start offset: '.$start_offset.', bytes: '.strlen($ret).'; requested: '.$requested_bytes.')');

				clearstatcache();
				$start_offset = ftell($fh);
				$last_byte = ($manually_break_up) ? min($remote_size, $start_offset + $chunk_size) : $remote_size;

			}

		} catch(Exception $e) {
			$this->log('Error ('.get_class($e).') - failed to download the file ('.$e->getCode().', '.$e->getMessage().')');
			$this->log("$file: ".__('Error - failed to download the file', 'InfiniteWP').' ('.$e->getCode().', '.$e->getMessage().')' ,'error');
			return false;
		}

		fclose($fh);

		return true;
	}

	/**
	 * This will decrypt an encryped db file
	 * @param  string  $fullpath   This is the full path to the encrypted file location
	 * @param  string  $key        This is the key (satling) to be used when decrypting
	 * @param  boolean $to_temporary_file Use if the resulting file is not intended to be kept
	 * @return array               This bring back an array of full decrypted path
	 */
	public function decrypt($fullpath, $key, $to_temporary_file = false) {
		$this->ensure_phpseclib('Crypt_Rijndael', 'Crypt/Rijndael');
		if (defined('IWP_DECRYPTION_ENGINE')) {
			if ('openssl' == IWP_DECRYPTION_ENGINE) {
				$rijndael->setPreferredEngine(CRYPT_ENGINE_OPENSSL);
			} elseif ('mcrypt' == IWP_DECRYPTION_ENGINE) {
				$rijndael->setPreferredEngine(CRYPT_ENGINE_MCRYPT);
			} elseif ('internal' == IWP_DECRYPTION_ENGINE) {
				$rijndael->setPreferredEngine(CRYPT_ENGINE_INTERNAL);
			}
		}
		
		//open file to read
		if (false === ($file_handle = fopen($fullpath, 'rb'))) return false;

		$decrypted_path = dirname($fullpath).'/decrypt_'.basename($fullpath).'.tmp';
		//open new file from new path
		if (false === ($decrypted_handle = fopen($decrypted_path, 'wb+'))) return false;

		//setup encryption
		$rijndael = new Crypt_Rijndael();
		$rijndael->setKey($key);
		$rijndael->disablePadding();
		$rijndael->enableContinuousBuffer();

		$file_size = filesize($fullpath);
		$bytes_decrypted = 0;
		$buffer_size = defined('IWP_CRYPT_BUFFER_SIZE') ? IWP_CRYPT_BUFFER_SIZE : 2097152;

		//loop around the file
		while ($bytes_decrypted < $file_size) {
			//read buffer sized amount from file
			if (false === ($file_part = fread($file_handle, $buffer_size))) return false;
			//check to ensure padding is needed before decryption
			$length = strlen($file_part);
			if ($length % 16 != 0) {
				$pad = 16 - ($length % 16);
				$file_part = str_pad($file_part, $length + $pad, chr($pad));
// 				$file_part = str_pad($file_part, $length + $pad, chr(0));
			}
			
			$decrypted_data = $rijndael->decrypt($file_part);
			
			$is_last_block = ($bytes_decrypted + strlen($decrypted_data) >= $file_size);
			
			$write_bytes = min($file_size - $bytes_decrypted, strlen($decrypted_data));
			if ($is_last_block) {
				$is_padding = false;
				$last_byte = ord(substr($decrypted_data, -1, 1));
				if ($last_byte < 16) {
					$is_padding = true;
					for ($j = 1 ; $j<=$last_byte; $j++) {
						if (substr($decrypted_data, -$j, 1) != chr($last_byte)) $is_padding = false;
					}
				}
				if ($is_padding) {
					$write_bytes -= $last_byte;
				}
			}
			
			if (false === fwrite($decrypted_handle, $decrypted_data, $write_bytes)) return false;
			$bytes_decrypted += $buffer_size;
		}
		 
		//close the main file handle
		fclose($decrypted_handle);
		//close original file
		fclose($file_handle);
		
		//remove the crypt extension from the end as this causes issues when opening
		$fullpath_new = preg_replace('/\.crypt$/', '', $fullpath, 1);
		// //need to replace original file with tmp file
		
		$fullpath_basename = basename($fullpath_new);
		
		if ($to_temporary_file) {
			return array(
				'fullpath' 	=> $decrypted_path,
				'basename' => $fullpath_basename
			);
		}
		
		if (false === rename($decrypted_path, $fullpath_new)) return false;

		//need to send back the new decrypted path
		$decrypt_return = array(
			'fullpath' 	=> $fullpath_new,
			'basename' => $fullpath_basename
		);

		return $decrypt_return;
	}

	public function detect_safe_mode() {
		return (@ini_get('safe_mode') && strtolower(@ini_get('safe_mode')) != "off") ? 1 : 0;
	}

	public function find_working_sqldump($logit = true, $cacheit = true) {

		// The hosting provider may have explicitly disabled the popen or proc_open functions
		if ($this->detect_safe_mode() || !function_exists('popen') || !function_exists('escapeshellarg')) {
			if ($cacheit) $this->jobdata_set('binsqldump', false);
			return false;
		}
		$existing = $this->jobdata_get('binsqldump', null);
		# Theoretically, we could have moved machines, due to a migration
		if (null !== $existing && (!is_string($existing) || @is_executable($existing))) return $existing;

		$iwp_backup_dir = $this->backups_dir_location();
		global $wpdb;
		$table_name = $wpdb->get_blog_prefix().'options';
		$tmp_file = md5(time().rand()).".sqltest.tmp";
		$pfile = md5(time().rand()).'.tmp';
		file_put_contents($iwp_backup_dir.'/'.$pfile, "[mysqldump]\npassword=".DB_PASSWORD."\n");

		$result = false;
		foreach (explode(',', IWP_MYSQLDUMP_EXECUTABLE) as $potsql) {
			
			if (!@is_executable($potsql)) continue;
			
			if ($logit) $this->log("Testing: $potsql");

			if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
				$exec = "cd ".escapeshellarg(str_replace('/', '\\', $iwp_backup_dir))." & ";
				$siteurl = "'siteurl'";
				if (false !== strpos($potsql, ' ')) $potsql = '"'.$potsql.'"';
			} else {
				$exec = "cd ".escapeshellarg($iwp_backup_dir)."; ";
				$siteurl = "\\'siteurl\\'";
				if (false !== strpos($potsql, ' ')) $potsql = "'$potsql'";
			}
				
			$exec .= "$potsql --defaults-file=$pfile --max_allowed_packet=1M --quote-names --add-drop-table --skip-comments --skip-set-charset --allow-keywords --dump-date --extended-insert --where=option_name=$siteurl --user=".escapeshellarg(DB_USER)." --host=".escapeshellarg(DB_HOST)." ".DB_NAME." ".escapeshellarg($table_name)."";
			
			$handle = popen($exec, "r");
			if ($handle) {
				if (!feof($handle)) {
					$output = fread($handle, 8192);
					if ($output && $logit) {
						$log_output = (strlen($output) > 512) ? substr($output, 0, 512).' (truncated - '.strlen($output).' bytes total)' : $output;
						$this->log("Output: ".str_replace("\n", '\\n', trim($log_output)));
					}
				} else {
					$output = '';
				}
				$ret = pclose($handle);
				if ($ret !=0) {
					if ($logit) {
						$this->log("Binary mysqldump: error (code: $ret)");
					}
				} else {
// 					$dumped = file_get_contents($iwp_backup_dir.'/'.$tmp_file, false, null, 0, 4096);
					if (stripos($output, 'insert into') !== false) {
						if ($logit) $this->log("Working binary mysqldump found: $potsql");
						$result = $potsql;
						break;
					}
				}
			} else {
				if ($logit) $this->log("Error: popen failed");
			}
		}

		@unlink($iwp_backup_dir.'/'.$pfile);
		@unlink($iwp_backup_dir.'/'.$tmp_file);

		if ($cacheit) $this->jobdata_set('binsqldump', $result);

		return $result;
	}

	// We require -@ and -u -r to work - which is the usual Linux binzip
	public function find_working_bin_zip($logit = true, $cacheit = true) {
		if ($this->detect_safe_mode()) return false;
		// The hosting provider may have explicitly disabled the popen or proc_open functions
		if (!function_exists('popen') || !function_exists('proc_open') || !function_exists('escapeshellarg')) {
			if ($cacheit) $this->jobdata_set('binzip', false);
			return false;
		}

		$existing = $this->jobdata_get('binzip', null);
		# Theoretically, we could have moved machines, due to a migration
		if (null !== $existing && (!is_string($existing) || @is_executable($existing))) return $existing;

		$iwp_backup_dir = $this->backups_dir_location();
		foreach (explode(',', IWP_ZIP_EXECUTABLE) as $potzip) {
			if (!@is_executable($potzip)) continue;
			if ($logit) $this->log("Testing: $potzip");

			# Test it, see if it is compatible with Info-ZIP
			# If you have another kind of zip, then feel free to tell me about it
			@mkdir($iwp_backup_dir.'/binziptest/subdir1/subdir2', 0777, true);

			if (!file_exists($iwp_backup_dir.'/binziptest/subdir1/subdir2')) return false;
			
			file_put_contents($iwp_backup_dir.'/binziptest/subdir1/subdir2/test.html', '<html><body><a href="https://infinitewp.com">InfiniteWP is a great backup and restoration plugin for WordPress.</a></body></html>');
			@unlink($iwp_backup_dir.'/binziptest/test.zip');
			if (is_file($iwp_backup_dir.'/binziptest/subdir1/subdir2/test.html')) {

				$exec = "cd ".escapeshellarg($iwp_backup_dir)."; $potzip";
				if (defined('IWP_BINZIP_OPTS') && IWP_BINZIP_OPTS) $exec .= ' '.IWP_BINZIP_OPTS;
				$exec .= " -v -u -r binziptest/test.zip binziptest/subdir1";

				$all_ok=true;
				$handle = popen($exec, "r");
				if ($handle) {
					while (!feof($handle)) {
						$w = fgets($handle);
						if ($w && $logit) $this->log("Output: ".trim($w));
					}
					$ret = pclose($handle);
					if ($ret !=0) {
						if ($logit) $this->log("Binary zip: error (code: $ret)");
						$all_ok = false;
					}
				} else {
					if ($logit) $this->log("Error: popen failed");
					$all_ok = false;
				}

				# Now test -@
				if (true == $all_ok) {
					file_put_contents($iwp_backup_dir.'/binziptest/subdir1/subdir2/test2.html', '<html><body><a href="https://infinitewp.com">InfiniteWP is a really great backup and restoration plugin for WordPress.</a></body></html>');
					
					$exec = $potzip;
					if (defined('IWP_BINZIP_OPTS') && IWP_BINZIP_OPTS) $exec .= ' '.IWP_BINZIP_OPTS;
					$exec .= " -v -@ binziptest/test.zip";

					$all_ok=true;

					$descriptorspec = array(
						0 => array('pipe', 'r'),
						1 => array('pipe', 'w'),
						2 => array('pipe', 'w')
					);
					$handle = proc_open($exec, $descriptorspec, $pipes, $iwp_backup_dir);
					if (is_resource($handle)) {
						if (!fwrite($pipes[0], "binziptest/subdir1/subdir2/test2.html\n")) {
							@fclose($pipes[0]);
							@fclose($pipes[1]);
							@fclose($pipes[2]);
							$all_ok = false;
						} else {
							fclose($pipes[0]);
							while (!feof($pipes[1])) {
								$w = fgets($pipes[1]);
								if ($w && $logit) $this->log("Output: ".trim($w));
							}
							fclose($pipes[1]);
							
							while (!feof($pipes[2])) {
								$last_error = fgets($pipes[2]);
								if (!empty($last_error) && $logit) $this->log("Stderr output: ".trim($w));
							}
							fclose($pipes[2]);

							$ret = proc_close($handle);
							if ($ret !=0) {
								if ($logit) $this->log("Binary zip: error (code: $ret)");
								$all_ok = false;
							}

						}

					} else {
						if ($logit) $this->log("Error: proc_open failed");
						$all_ok = false;
					}

				}

				// Do we now actually have a working zip? Need to test the created object using PclZip
				// If it passes, then remove dirs and then return $potzip;
				$found_first = false;
				$found_second = false;
				if ($all_ok && file_exists($iwp_backup_dir.'/binziptest/test.zip')) {
					if (function_exists('gzopen')) {
						if(!class_exists('PclZip')) require_once(ABSPATH.'/wp-admin/includes/class-pclzip.php');
						$zip = new PclZip($iwp_backup_dir.'/binziptest/test.zip');
						$foundit = 0;
						if (($list = $zip->listContent()) != 0) {
							foreach ($list as $obj) {
								if ($obj['filename'] && !empty($obj['stored_filename']) && 'binziptest/subdir1/subdir2/test.html' == $obj['stored_filename'] && $obj['size']==129) $found_first=true;
								if ($obj['filename'] && !empty($obj['stored_filename']) && 'binziptest/subdir1/subdir2/test2.html' == $obj['stored_filename'] && $obj['size']==136) $found_second=true;
							}
						}
					} else {
						// PclZip will die() if gzopen is not found
						// Obviously, this is a kludge - we assume it's working. We could, of course, just return false - but since we already know now that PclZip can't work, that only leaves ZipArchive
						$this->log("gzopen function not found; PclZip cannot be invoked; will assume that binary zip works if we have a non-zero file");
						if (filesize($iwp_backup_dir.'/binziptest/test.zip') > 0) {
							$found_first = true;
							$found_second = true;
						}
					}
				}
				$this->remove_binzip_test_files($iwp_backup_dir);
				if ($found_first && $found_second) {
					if ($logit) $this->log("Working binary zip found: $potzip");
					if ($cacheit) $this->jobdata_set('binzip', $potzip);
					return $potzip;
				}

			}
			$this->remove_binzip_test_files($iwp_backup_dir);
		}
		if ($cacheit) $this->jobdata_set('binzip', false);
		return false;
	}

	private function remove_binzip_test_files($iwp_backup_dir) {
		@unlink($iwp_backup_dir.'/binziptest/subdir1/subdir2/test.html');
		@unlink($iwp_backup_dir.'/binziptest/subdir1/subdir2/test2.html');
		@rmdir($iwp_backup_dir.'/binziptest/subdir1/subdir2');
		@rmdir($iwp_backup_dir.'/binziptest/subdir1');
		@unlink($iwp_backup_dir.'/binziptest/test.zip');
		@rmdir($iwp_backup_dir.'/binziptest');
	}

	// This function is purely for timing - we just want to know the maximum run-time; not whether we have achieved anything during it
	public function record_still_alive() {
		// Update the record of maximum detected runtime on each run
		$time_passed = $this->jobdata_get('run_times');
		if (!is_array($time_passed)) $time_passed = array();

		$time_this_run = microtime(true)-$this->opened_log_time;
		$time_passed[$this->current_resumption] = $time_this_run;
		$this->jobdata_set('run_times', $time_passed);

		$resume_interval = $this->jobdata_get('resume_interval');
		if ($time_this_run + 30 > $resume_interval) {
			$new_interval = ceil($time_this_run + 30);
			set_site_transient('IWP_initial_resume_interval', (int)$new_interval, 8*86400);
			$this->log("The time we have been running (".round($time_this_run,1).") is approaching the resumption interval ($resume_interval) - increasing resumption interval to $new_interval");
			$this->jobdata_set('resume_interval', $new_interval);
		}

	}

	public function something_useful_happened() {

		$this->record_still_alive();

		if (!$this->something_useful_happened) {
			$useful_checkin = $this->jobdata_get('useful_checkin');
			if (empty($useful_checkin) || $this->current_resumption > $useful_checkin) $this->jobdata_set('useful_checkin', $this->current_resumption);
		}

		$this->something_useful_happened = true;

		$iwp_backup_dir = $this->backups_dir_location();
		if (file_exists($iwp_backup_dir.'/deleteflag-'.$this->nonce.'.txt')) {
			$this->log("User request for abort: backup job will be immediately halted");
			@unlink($iwp_backup_dir.'/deleteflag-'.$this->nonce.'.txt');
			$this->backup_finish($this->current_resumption + 1, true, true, $this->current_resumption, true);
			die;
		}
		
		if ($this->current_resumption >=5  && false == $this->newresumption_scheduled) {
			$this->log("This is resumption ".$this->current_resumption.", but meaningful activity is still taking place; so a new one will be scheduled");
			// We just use max here to make sure we get a number at all
			$resume_interval = max($this->jobdata_get('resume_interval'), 75);
			// Don't consult the minimum here
			// if (!is_numeric($resume_interval) || $resume_interval<300) { $resume_interval = 300; }
			$schedule_for = time()+$resume_interval;
			$this->newresumption_scheduled = $schedule_for;
			wp_schedule_single_event($schedule_for, 'IWP_backup_resume', array($this->current_resumption + 1, $this->nonce));
		} else {
			$this->reschedule_if_needed();
		}
	}

	public function option_filter_get($which) {
		global $wpdb;
		$row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $which));
		// Has to be get_row instead of get_var because of funkiness with 0, false, null values
		return (is_object($row)) ? $row->option_value : false;
	}

	public function parse_filename($filename) {
		if (preg_match('/^backup_([\-0-9]{10})-([0-9]{4})_.*_([0-9a-f]{12})-([\-a-z]+)([0-9]+)?+\.(zip|gz|gz\.crypt)$/i', $filename, $matches)) {
			return array(
				'date' => strtotime($matches[1].' '.$matches[2]),
				'nonce' => $matches[3],
				'type' => $matches[4],
				'index' => (empty($matches[5]) ? 0 : $matches[5]-1),
				'extension' => $matches[6]);
		} else {
			return false;
		}
	}
	
	/**
	 * Indicate which checksums to take for backup files. Abstracted for extensibilty and future changes.
	 * 
	 * @returns array - a list of hashing algorithms, as understood by PHP's hash() function
	 */
	public function which_checksums() {
		return apply_filters('IWP_which_checksums', array('sha1', 'sha256'));
	}

	// Pretty printing
	public function printfile($description, $history, $entity, $checksums, $jobdata, $smaller=false) {

		if (empty($history[$entity])) return;

		if ($smaller) {
			$pfiles =  "<strong>".$description." (".sprintf(__('files: %s', 'InfiniteWP'), count($history[$entity])).")</strong><br>\n";
		} else {
			$pfiles =  "<h3>".$description." (".sprintf(__('files: %s', 'InfiniteWP'), count($history[$entity])).")</h3>\n\n";
		}

		$pfiles .= '<ul>';
		$files = $history[$entity];
		if (is_string($files)) $files = array($files);

		foreach ($files as $ind => $file) {

			$op = htmlspecialchars($file)."\n";
			$skey = $entity.((0 == $ind) ? '' : $ind).'-size';

			$meta = '';
			if ('db' == substr($entity, 0, 2) && 'db' != $entity) {
				$dind = substr($entity, 2);
				if (is_array($jobdata) && !empty($jobdata['backup_database']) && is_array($jobdata['backup_database']) && !empty($jobdata['backup_database'][$dind]) && is_array($jobdata['backup_database'][$dind]['dbinfo']) && !empty($jobdata['backup_database'][$dind]['dbinfo']['host'])) {
					$dbinfo = $jobdata['backup_database'][$dind]['dbinfo'];
					$meta .= sprintf(__('External database (%s)', 'InfiniteWP'), $dbinfo['user'].'@'.$dbinfo['host'].'/'.$dbinfo['name'])."<br>";
				}
			}
			if (isset($history[$skey])) $meta .= sprintf(__('Size: %s MB', 'InfiniteWP'), round($history[$skey]/1048576, 1));
			$ckey = $entity.$ind;
			foreach ($checksums as $ck) {
				$ck_plain = false;
				if (isset($history['checksums'][$ck][$ckey])) {
					$meta .= (($meta) ? ', ' : '').sprintf(__('%s checksum: %s', 'InfiniteWP'), strtoupper($ck), $history['checksums'][$ck][$ckey]);
					$ck_plain = true;
				}
				if (isset($history['checksums'][$ck][$ckey.'.crypt'])) {
					if ($ck_plain) $meta .= ' '.__('(when decrypted)');
					$meta .= (($meta) ? ', ' : '').sprintf(__('%s checksum: %s', 'InfiniteWP'), strtoupper($ck), $history['checksums'][$ck][$ckey.'.crypt']);
				}
			}

			$fileinfo = apply_filters("IWP_fileinfo_$entity", array(), $ind);
			if (is_array($fileinfo) && !empty($fileinfo)) {
				if (isset($fileinfo['html'])) {
					$meta .= $fileinfo['html'];
				}
			}

			#if ($meta) $meta = " ($meta)";
			if ($meta) $meta = "<br><em>$meta</em>";
			$pfiles .= '<li>'.$op.$meta."\n</li>\n";
		}

		$pfiles .= "</ul>\n";

		return $pfiles;

	}

	// This important function returns a list of file entities that can potentially be backed up (subject to users settings), and optionally further meta-data about them
	public function get_backupable_file_entities($include_others = true, $full_info = false) {

		$wp_upload_dir = $this->wp_upload_dir();

		if ($full_info) {
			$arr = array(
				'plugins' => array('path' => untrailingslashit(WP_PLUGIN_DIR), 'description' => __('Plugins','IWP')),
				'themes' => array('path' => WP_CONTENT_DIR.'/themes', 'description' => __('Themes','IWP')),
				'uploads' => array('path' => untrailingslashit($wp_upload_dir['basedir']), 'description' => __('Uploads','IWP'))
			);
		} else {
			$arr = array(
				'plugins' => untrailingslashit(WP_PLUGIN_DIR),
				'themes' => WP_CONTENT_DIR.'/themes',
				'uploads' => untrailingslashit($wp_upload_dir['basedir'])
			);
		}

		$arr = apply_filters('IWP_backupable_file_entities', $arr, $full_info);

		// We then add 'others' on to the end
		if ($include_others) {
			if ($full_info) {
				$arr['others'] = array('path' => WP_CONTENT_DIR, 'description' => __('Others', 'IWP'));
			} else {
				$arr['others'] = WP_CONTENT_DIR;
			}
		}

		// Entries that should be added after 'others'
		$arr = apply_filters('IWP_backupable_file_entities_final', $arr, $full_info);

		return $arr;

	}

	# This is just a long-winded way of forcing WP to get the value afresh from the db, instead of using the auto-loaded/cached value (which can be out of date, especially since backups are, by their nature, long-running)
	public function filter_IWP_backup_history($v) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'IWP_backup_history' ) );
		if (is_object($row )) return maybe_unserialize($row->option_value);
		return false;
	}

	public function php_error_to_logline($errno, $errstr, $errfile, $errline) {
		switch ($errno) {
			case 1:		$e_type = 'E_ERROR'; break;
			case 2:		$e_type = 'E_WARNING'; break;
			case 4:		$e_type = 'E_PARSE'; break;
			case 8:		$e_type = 'E_NOTICE'; break;
			case 16:		$e_type = 'E_CORE_ERROR'; break;
			case 32:		$e_type = 'E_CORE_WARNING'; break;
			case 64:		$e_type = 'E_COMPILE_ERROR'; break;
			case 128:		$e_type = 'E_COMPILE_WARNING'; break;
			case 256:		$e_type = 'E_USER_ERROR'; break;
			case 512:		$e_type = 'E_USER_WARNING'; break;
			case 1024:	$e_type = 'E_USER_NOTICE'; break;
			case 2048:	$e_type = 'E_STRICT'; break;
			case 4096:	$e_type = 'E_RECOVERABLE_ERROR'; break;
			case 8192:	$e_type = 'E_DEPRECATED'; break;
			case 16384:	$e_type = 'E_USER_DEPRECATED'; break;
			case 30719:	$e_type = 'E_ALL'; break;
			default:		$e_type = "E_UNKNOWN ($errno)"; break;
		}

		if (!is_string($errstr)) $errstr = serialize($errstr);

		if (0 === strpos($errfile, ABSPATH)) $errfile = substr($errfile, strlen(ABSPATH));

		if ('E_DEPRECATED' == $e_type && !empty($this->no_deprecation_warnings)) {
			return false;
		}
		
		return "PHP event: code $e_type: $errstr (line $errline, $errfile)";

	}

	public function php_error($errno, $errstr, $errfile, $errline) {
		if (0 == error_reporting()) return true;
		$logline = $this->php_error_to_logline($errno, $errstr, $errfile, $errline);
		if (false !== $logline) $this->log($logline, 'notice', 'php_event');
		// Pass it up the chain
		return $this->error_reporting_stop_when_logged;
	}

	public function backup_resume($resumption_no, $bnonce, $first_call = false) {

		set_error_handler(array($this, 'php_error'), E_ALL & ~E_STRICT);
		if ($first_call) {
			$this->reschedule(10, $first_call);
			die;
		}
		$this->current_resumption = $resumption_no;

		@set_time_limit(IWP_SET_TIME_LIMIT);
		@ignore_user_abort(true);

		$runs_started = array();
		$time_now = microtime(true);

		add_filter('pre_option_IWP_backup_history', array($this, 'filter_IWP_backup_history'));

		// Restore state
		$resumption_extralog = '';
		$prev_resumption = $resumption_no - 1;
		$last_successful_resumption = -1;
		$job_type = 'backup';

		if ($resumption_no > 0) {

			$this->nonce = $bnonce;
			$this->backup_time = $this->jobdata_get('backup_time');
			$this->job_time_ms = $this->jobdata_get('job_time_ms');
			
			# Get the warnings before opening the log file, as opening the log file may generate new ones (which then leads to $this->errors having duplicate entries when they are copied over below)
			$warnings = $this->jobdata_get('warnings');
			
			$this->logfile_open($bnonce);
			
			// Import existing warnings. The purpose of this is so that when save_backup_history() is called, it has a complete set - because job data expires quickly, whilst the warnings of the last backup run need to persist
			if (is_array($warnings)) {
				foreach ($warnings as $warning) {
					$this->errors[] = array('level' => 'warning', 'message' => $warning);
				}
			}

			$runs_started = $this->jobdata_get('runs_started');
			if (!is_array($runs_started)) $runs_started=array();
			$time_passed = $this->jobdata_get('run_times');
			if (!is_array($time_passed)) $time_passed = array();
			
			foreach ($time_passed as $run => $passed) {
				if (isset($runs_started[$run]) && $runs_started[$run] + $time_passed[$run] + 30 > $time_now) {
					// We don't want to increase the resumption if WP has started two copies of the same resumption off
					if ($run && $run == $resumption_no) {
						$increase_resumption = false;
						$this->log("It looks like WordPress's scheduler has started multiple instances of this resumption");
					} else {
						$increase_resumption = true;
					}
					$this->terminate_due_to_activity('check-in', round($time_now, 1), round($runs_started[$run] + $time_passed[$run], 1), $increase_resumption);
				}
			}

			for ($i = 0; $i<=$prev_resumption; $i++) {
				if (isset($time_passed[$i])) $last_successful_resumption = $i;
			}

			if (isset($time_passed[$prev_resumption])) {
				$resumption_extralog = ", previous check-in=".round($time_passed[$prev_resumption], 1)."s";
			} else {
				$this->no_checkin_last_time = true;
			}

			// This is just a simple test to catch restorations of old backup sets where the backup includes a resumption of the backup job
			if ($time_now - $this->backup_time > 172800 && true == apply_filters('IWP_check_obsolete_backup', true, $time_now, $this)) {
			
				// We have seen cases where the get_site_option() call that self::get_jobdata() relies on returns nothing, even though the data was there in the database. This appears to be sometimes reproducible for the people who get it, but stops being reproducible if they change their backup times - which suggests that they're having failures at times of extreme load. We can attempt to detect this case, and reschedule, instead of aborting.
				if (empty($this->backup_time) && empty($this->backup_is_already_complete) && !empty($this->logfile_name) && is_readable($this->logfile_name)) {
					$first_log_bit = file_get_contents($this->logfile_name, false, null, 0, 250);
					if (preg_match('/\(0\) Opened log file at time: (.*) on /', $first_log_bit, $matches)) {
						$first_opened = strtotime($matches[1]);
						// The value of 1000 seconds here is somewhat arbitrary; but allows for the problem to occur in ~ the first 15 minutes. In practice, the problem is extremely rare; if this does not catch it, we can tweak the algorithm.
						if (time() - $first_opened < 1000) {
							$this->log("This backup task (".$this->nonce.") failed to load its job data (possible database server malfunction), but appears to be only recently started: scheduling a fresh resumption in order to try again, and then ending this resumption ($time_now, ".$this->backup_time.") (existing jobdata keys: ".implode(', ', array_keys($this->jobdata)).")");
							$this->reschedule(120);
							die;
						}
					}
				}
			
				$this->log("This backup task (".$this->nonce.") is either complete or began over 2 days ago: ending ($time_now, ".$this->backup_time.") (existing jobdata keys: ".implode(', ', array_keys($this->jobdata)).")");
				die;
			}

		} else {
			$label = $this->jobdata_get('label');
			if ($label) $resumption_extralog = ", label=$label";
		}

		$this->last_successful_resumption = $last_successful_resumption;

		$runs_started[$resumption_no] = $time_now;
		if (!empty($this->backup_time)) $this->jobdata_set('runs_started', $runs_started);

		// Schedule again, to run in 5 minutes again, in case we again fail
		// The actual interval can be increased (for future resumptions) by other code, if it detects apparent overlapping
		$resume_interval = max(intval($this->jobdata_get('resume_interval')), 100);

		$btime = $this->backup_time;

		$job_type = $this->jobdata_get('job_type');

		do_action('IWP_resume_backup_'.$job_type);

		$iwp_backup_dir = $this->backups_dir_location();

		$time_ago = time()-$btime;

		$this->log("Backup run: resumption=$resumption_no, nonce=$bnonce, begun at=$btime (${time_ago}s ago), job type=$job_type".$resumption_extralog);

		// This works round a bizarre bug seen in one WP install, where delete_transient and wp_clear_scheduled_hook both took no effect, and upon 'resumption' the entire backup would repeat.
		// Argh. In fact, this has limited effect, as apparently (at least on another install seen), the saving of the updated transient via jobdata_set() also took no effect. Still, it does not hurt.
		if ($resumption_no >= 1 && 'finished' == $this->jobdata_get('jobstatus')) {
			$this->log('Terminate: This backup job is already finished (1).');
			delete_option('IWP_backup_status');
			die;
		} elseif ('backup' == $job_type && !empty($this->backup_is_already_complete)) {
			$this->jobdata_set('jobstatus', 'finished');
			$this->log('Terminate: This backup job is already finished (2).');
			delete_option('IWP_backup_status');
			die;
		}

		if ($resumption_no > 0 && isset($runs_started[$prev_resumption])) {
			$our_expected_start = $runs_started[$prev_resumption] + $resume_interval;
			# If the previous run increased the resumption time, then it is timed from the end of the previous run, not the start
			if (isset($time_passed[$prev_resumption]) && $time_passed[$prev_resumption]>0) $our_expected_start += $time_passed[$prev_resumption];
			$our_expected_start = apply_filters('IWP_expected_start', $our_expected_start, $job_type);
			# More than 12 minutes late?
			if ($time_now > $our_expected_start + 720) {
				$this->log('Long time past since expected resumption time: approx expected='.round($our_expected_start,1).", now=".round($time_now, 1).", diff=".round($time_now-$our_expected_start,1));
				$this->log(__('Your website is visited infrequently and InfiniteWP is not getting the resources it hoped for; please set Uptime monitor', 'InfiniteWP'), 'warning', 'infrequentvisits');
			}
		}

		$this->jobdata_set('current_resumption', $resumption_no);

		$first_run = apply_filters('IWP_filerun_firstrun', 0);

		// We just do this once, as we don't want to be in permanent conflict with the overlap detector
		if ($resumption_no >= $first_run + 8 && $resumption_no < $first_run + 15 && $resume_interval >= 300) {

			// $time_passed is set earlier
			list($max_time, $timings_string, $run_times_known) = $this->max_time_passed($time_passed, $resumption_no - 1, $first_run);

			# Do this on resumption 8, or the first time that we have 6 data points
			if (($first_run + 8 == $resumption_no && $run_times_known >= 6) || (6 == $run_times_known && !empty($time_passed[$prev_resumption]))) {
				$this->log("Time passed on previous resumptions: $timings_string (known: $run_times_known, max: $max_time)");
				// Remember that 30 seconds is used as the 'perhaps something is still running' detection threshold, and that 45 seconds is used as the 'the next resumption is approaching - reschedule!' interval
				if ($max_time + 52 < $resume_interval) {
					$resume_interval = round($max_time + 52);
					$this->log("Based on the available data, we are bringing the resumption interval down to: $resume_interval seconds");
					$this->jobdata_set('resume_interval', $resume_interval);
				}
				// This next condition was added in response to HS#9174, a case where on one resumption, PHP was allowed to run for >3000 seconds - but other than that, up to 500 seconds. As a result, the resumption interval got stuck at a large value, whilst resumptions were only allowed to run for a much smaller amount.
				// This detects whether our last run was less than half the resume interval,  but was non-trivial (at least 50 seconds - so, indicating it didn't just error out straight away), but with a resume interval of over 300 seconds. In this case, it is reduced.
			} elseif (isset($time_passed[$prev_resumption]) && $time_passed[$prev_resumption] > 50 && $resume_interval > 300 && $time_passed[$prev_resumption] < $resume_interval/2 && 'clouduploading' == $this->jobdata_get('jobstatus')) {
				$resume_interval = round($time_passed[$prev_resumption] + 52);
				$this->log("Time passed on previous resumptions: $timings_string (known: $run_times_known, max: $max_time). Based on the available data, we are bringing the resumption interval down to: $resume_interval seconds");
				$this->jobdata_set('resume_interval', $resume_interval);
			}

		}

		// A different argument than before is needed otherwise the event is ignored
		$next_resumption = $resumption_no+1;
		if ($next_resumption < $first_run + 10) {
			if (true === $this->jobdata_get('one_shot')) {
				if (true === $this->jobdata_get('reschedule_before_upload') && 1 == $next_resumption) {
					$this->log('A resumption will be scheduled for the cloud backup stage');
					$schedule_resumption = true;
				} else {
					$this->log('We are in "one shot" mode - no resumptions will be scheduled');
				}
			} else {
				$schedule_resumption = true;
			}
		} else {
			// We're in over-time - we only reschedule if something useful happened last time (used to be that we waited for it to happen this time - but that meant that temporary errors, e.g. Google 400s on uploads, scuppered it all - we'd do better to have another chance
			$useful_checkin = $this->jobdata_get('useful_checkin');
			$last_resumption = $resumption_no-1;

			if (empty($useful_checkin) || $useful_checkin < $last_resumption) {
				$this->log(sprintf('The current run is resumption number %d, and there was nothing useful done on the last run (last useful run: %s) - will not schedule a further attempt until we see something useful happening this time', $resumption_no, $useful_checkin));
			} else {
				$schedule_resumption = true;
			}
		}

		// Sanity check
		if (empty($this->backup_time)) {
			$this->log('The backup_time parameter appears to be empty (usually caused by resuming an already-complete backup).');
			return false;
		}

		if (isset($schedule_resumption)) {
			$schedule_for = time()+$resume_interval;
			$this->log("Scheduling a resumption ($next_resumption) after $resume_interval seconds ($schedule_for) in case this run gets aborted");
			wp_schedule_single_event($schedule_for, 'IWP_backup_resume', array($next_resumption, $bnonce));
			$this->newresumption_scheduled = $schedule_for;
		}

		$backup_files = $this->jobdata_get('backup_files');

		global $IWP_backup;
		// Bring in all the backup routines
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/backup.php');
		$IWP_backup = new IWP_MMB_Backup($backup_files, apply_filters('IWP_files_altered_since', -1, $job_type));

		$undone_files = array();

		if ('no' == $backup_files) {
			$this->log("This backup run is not intended for files - skipping");
			$our_files = array();
		} else {

			// This should be always called; if there were no files in this run, it returns us an empty array
			$backup_array = $IWP_backup->resumable_backup_of_files($resumption_no);

			// This save, if there was something, is then immediately picked up again
			if (is_array($backup_array)) {
				$this->log('Saving backup status to database (elements: '.count($backup_array).")");
				$this->save_backup_history($backup_array);
			}

			// Switch of variable name is purely vestigial
			$our_files = $backup_array;
			if (!is_array($our_files)) $our_files = array();

		}

		$backup_databases = $this->jobdata_get('backup_database');

		if (!is_array($backup_databases)) $backup_databases = array('wp' => $backup_databases);

		foreach ($backup_databases as $whichdb => $backup_database) {

			if (is_array($backup_database)) {
				$dbinfo = $backup_database['dbinfo'];
				$backup_database = $backup_database['status'];
			} else {
				$dbinfo = array();
			}

			$tindex = ('wp' == $whichdb) ? 'db' : 'db'.$whichdb;

			if ('begun' == $backup_database || 'finished' == $backup_database || 'encrypted' == $backup_database) {

				if ('wp' == $whichdb) {
					$db_descrip = 'WordPress DB';
				} else {
					if (!empty($dbinfo) && is_array($dbinfo) && !empty($dbinfo['host'])) {
						$db_descrip = "External DB $whichdb - ".$dbinfo['user'].'@'.$dbinfo['host'].'/'.$dbinfo['name'];
					} else {
						$db_descrip = "External DB $whichdb - details appear to be missing";
					}
				}

				if ('begun' == $backup_database) {
					if ($resumption_no > 0) {
						$this->log("Resuming creation of database dump ($db_descrip)");
					} else {
						$this->log("Beginning creation of database dump ($db_descrip)");
					}
				} elseif ('encrypted' == $backup_database) {
					$this->log("Database dump ($db_descrip): Creation and encryption were completed already");
				} else {
					$this->log("Database dump ($db_descrip): Creation was completed already");
				}

				if ('wp' != $whichdb && (empty($dbinfo) || !is_array($dbinfo) || empty($dbinfo['host']))) {
					unset($backup_databases[$whichdb]);
					$this->jobdata_set('backup_database', $backup_databases);
					continue;
				}

				$db_backup = $IWP_backup->backup_db($backup_database, $whichdb, $dbinfo);

				if(is_array($our_files) && is_string($db_backup)) $our_files[$tindex] = $db_backup;

				if ('encrypted' != $backup_database) {
					$backup_databases[$whichdb] = array('status' => 'finished', 'dbinfo' => $dbinfo);
					$this->jobdata_set('backup_database', $backup_databases);
				}
			} elseif ('no' == $backup_database) {
				$this->log("No database backup ($whichdb) - not part of this run");
			} else {
				$this->log("Unrecognised data when trying to ascertain if the database ($whichdb) was backed up (".serialize($backup_database).")");
			}

			// This is done before cloud despatch, because we want a record of what *should* be in the backup. Whether it actually makes it there or not is not yet known.
			$this->save_backup_history($our_files);

			// Potentially encrypt the database if it is not already
			if ('no' != $backup_database && isset($our_files[$tindex]) && !preg_match("/\.crypt$/", $our_files[$tindex])) {
				$our_files[$tindex] = $IWP_backup->encrypt_file($our_files[$tindex]);
				// No need to save backup history now, as it will happen in a few lines time
				if (preg_match("/\.crypt$/", $our_files[$tindex])) {
					$backup_databases[$whichdb] = array('status' => 'encrypted', 'dbinfo' => $dbinfo);
					$this->jobdata_set('backup_database', $backup_databases);
				}
			}

			if ('no' != $backup_database && isset($our_files[$tindex]) && file_exists($iwp_backup_dir.'/'.$our_files[$tindex])) {
				$our_files[$tindex.'-size'] = filesize($iwp_backup_dir.'/'.$our_files[$tindex]);
				$this->save_backup_history($our_files);
			}

		}

		$backupable_entities = $this->get_backupable_file_entities(true);

		$checksum_list = $this->which_checksums();
		
		$checksums = array();
		
		foreach ($checksum_list as $checksum) {
			$checksums[$checksum] = array();
		}

		$total_size = 0;
		
		// Queue files for upload
		foreach ($our_files as $key => $files) {
			// Only continue if the stored info was about a dump
			if (!isset($backupable_entities[$key]) && ('db' != substr($key, 0, 2) || '-size' == substr($key, -5, 5))) continue;
			if (is_string($files)) $files = array($files);
			foreach ($files as $findex => $file) {
			
				$size_key = (0 == $findex) ? $key.'-size' : $key.$findex.'-size';
				$total_size = (false === $total_size || !isset($our_files[$size_key]) || !is_numeric($our_files[$size_key])) ? false : $total_size + $our_files[$size_key];
			
				foreach ($checksum_list as $checksum) {
			
					$cksum = $this->jobdata_get($checksum.'-'.$key.$findex);
					if ($cksum) $checksums[$checksum][$key.$findex] = $cksum;
					$cksum = $this->jobdata_get($checksum.'-'.$key.$findex.'.crypt');
					if ($cksum) $checksums[$checksum][$key.$findex.".crypt"] = $cksum;
				
				}
				
				if ($this->is_uploaded($file)) {
					$this->log("$file: $key: This file has already been successfully uploaded");
				} elseif (is_file($iwp_backup_dir.'/'.$file)) {
					if (!in_array($file, $undone_files)) {
						$this->log("$file: $key: This file has not yet been successfully uploaded: will queue");
						$undone_files[$key.$findex] = $file;
					} else {
						$this->log("$file: $key: This file was already queued for upload (this condition should never be seen)");
					}
				} else {
					$this->log("$file: $key: Note: This file was not marked as successfully uploaded, but does not exist on the local filesystem ($iwp_backup_dir/$file)");
					$this->uploaded_file($file, true);
				}
			}
		}
		$our_files['checksums'] = $checksums;

		// Save again (now that we have checksums)
		$size_description = (false === $total_size) ? 'Unknown' : $this->convert_numeric_size_to_text($total_size);
		$this->log("Saving backup history. Total backup size: $size_description");
		$backup_meta_file = $this->createBackupMetaFile($our_files);
		if ($backup_meta_file) {
			$our_files['backup_file_basename'] = $backup_meta_file;
			$undone_files['backup_file_basename'] = $backup_meta_file;
		}
		$this->save_backup_history($our_files);
		do_action('IWP_final_backup_history', $our_files);
		// We finished; so, low memory was not a problem
		$this->log_removewarning('lowram');

		if (0 == count($undone_files)) {
			$this->log("Resume backup ($bnonce, $resumption_no): finish run");
			if (is_array($our_files)) $this->save_last_backup($our_files);
			$this->log("There were no more files that needed uploading");
			// No email, as the user probably already got one if something else completed the run
			$allow_email = false;
			if ('begun' == $this->jobdata_get('prune')) {
				// Begun, but not finished
				$this->log("Restarting backup prune operation");
				$IWP_backup->do_prune_standalone();
				$allow_email = true;
			}
			$this->backup_finish($next_resumption, true, $allow_email, $resumption_no);
			restore_error_handler();
			return;
		}

		$this->error_count_before_cloud_backup = $this->error_count();

		// This is intended for one-shot backups, where we do want a resumption if it's only for uploading
		if (empty($this->newresumption_scheduled) && 0 == $resumption_no && 0 == $this->error_count_before_cloud_backup && true === $this->jobdata_get('reschedule_before_upload')) {
			$this->log("Cloud backup stage reached on one-shot backup: scheduling resumption for the cloud upload");
			$this->reschedule(60);
			$this->record_still_alive();
		}

		$this->log("Requesting upload of the files that have not yet been successfully uploaded (".count($undone_files).")");
		
		$IWP_backup->cloud_backup($undone_files);

		$this->log("Resume backup ($bnonce, $resumption_no): finish run");
		if (is_array($our_files)) $this->save_last_backup($our_files);
		$this->backup_finish($next_resumption, true, true, $resumption_no);

		restore_error_handler();

	}

	public function convert_numeric_size_to_text($size) {
		if ($size > 1073741824) {
			return round($size / 1073741824, 1).' GB';
		} elseif ($size > 1048576) {
			return round($size / 1048576, 1).' MB';
		} elseif ($size > 1024) {
			return round($size / 1024, 1).' KB';
		} else {
			return round($size, 1).' B';
		}
	}
	
	public function max_time_passed($time_passed, $upto, $first_run) {
		$max_time = 0;
		$timings_string = "";
		$run_times_known=0;
		for ($i=$first_run; $i<=$upto; $i++) {
			$timings_string .= "$i:";
			if (isset($time_passed[$i])) {
				$timings_string .=  round($time_passed[$i], 1).' ';
				$run_times_known++;
				if ($time_passed[$i] > $max_time) $max_time = round($time_passed[$i]);
			} else {
				$timings_string .=  '? ';
			}
		}
		return array($max_time, $timings_string, $run_times_known);
	}

	public function jobdata_getarray($non) {
		return get_site_option("IWP_jobdata_".$non, array());
	}

	public function jobdata_set_from_array($array) {
		$this->jobdata = $array;
		if (!empty($this->nonce)) update_site_option("IWP_jobdata_".$this->nonce, $this->jobdata);
	}

	// This works with any amount of settings, but we provide also a jobdata_set for efficiency as normally there's only one setting
	public function jobdata_set_multi() {
		if (!is_array($this->jobdata)) $this->jobdata = array();

		$args = func_num_args();

		for ($i=1; $i<=$args/2; $i++) {
			$key = func_get_arg($i*2-2);
			$value = func_get_arg($i*2-1);
			$this->jobdata[$key] = $value;
		}
		if (!empty($this->nonce)) update_site_option("IWP_jobdata_".$this->nonce, $this->jobdata);
	}

	public function jobdata_set($key, $value) {
		if (empty($this->jobdata)) {
			$this->jobdata = empty($this->nonce) ? array() : get_site_option("IWP_jobdata_".$this->nonce);
			if (!is_array($this->jobdata)) $this->jobdata = array();
		}
		$this->jobdata[$key] = $value;
		if ($this->nonce) update_site_option("IWP_jobdata_".$this->nonce, $this->jobdata);
	}

	public function jobdata_delete($key) {
		if (!is_array($this->jobdata)) {
			$this->jobdata = empty($this->nonce) ? array() : get_site_option("IWP_jobdata_".$this->nonce);
			if (!is_array($this->jobdata)) $this->jobdata = array();
		}
		unset($this->jobdata[$key]);
		if ($this->nonce) update_site_option("IWP_jobdata_".$this->nonce, $this->jobdata);
	}

	public function get_job_option($opt) {
		// These are meant to be read-only
		if (empty($this->jobdata['option_cache']) || !is_array($this->jobdata['option_cache'])) {
			if (!is_array($this->jobdata)) $this->jobdata = get_site_option("IWP_jobdata_".$this->nonce, array());
			$this->jobdata['option_cache'] = array();
		}
		return isset($this->jobdata['option_cache'][$opt]) ? $this->jobdata['option_cache'][$opt] : IWP_MMB_Backup_Options::get_iwp_backup_option($opt);
	}

	public function jobdata_get($key, $default = null, $all_data = false) {
		if (empty($this->jobdata)) {
			$this->jobdata = empty($this->nonce) ? array() : get_site_option("IWP_jobdata_".$this->nonce, array());
			if ($all_data) return $this->jobdata;
			if (!is_array($this->jobdata)) return $default;
		}
		if ($all_data) return $this->jobdata;
		return isset($this->jobdata[$key]) ? $this->jobdata[$key] : $default;
	}

	public function jobdata_reset() {
		$this->jobdata = null;
	}

	private function ensure_semaphore_exists($semaphore) {
		// Make sure the options for semaphores exist
		global $wpdb;
		$results = $wpdb->get_results("
			SELECT option_id
				FROM $wpdb->options
				WHERE option_name IN ('IWP_locked_$semaphore', 'IWP_unlocked_$semaphore', 'IWP_last_lock_time_$semaphore', 'IWP_semaphore_$semaphore')
		");

		if (!is_array($results) || count($results) < 3) {
		
			if (is_array($results) && count($results) > 0) {
				$this->log("Semaphore ($semaphore, ".$wpdb->options.") in an impossible/broken state - fixing (".count($results).")");
			} else {
				$this->log("Semaphore ($semaphore, ".$wpdb->options.") being initialised");
			}
			
			$wpdb->query("
				DELETE FROM $wpdb->options
				WHERE option_name IN ('IWP_locked_$semaphore', 'IWP_unlocked_$semaphore', 'IWP_last_lock_time_$semaphore', 'IWP_semaphore_$semaphore')
			");
			
			$wpdb->query($wpdb->prepare("
				INSERT INTO $wpdb->options (option_name, option_value, autoload)
				VALUES
				('IWP_unlocked_$semaphore', '1', 'no'),
				('IWP_last_lock_time_$semaphore', '%s', 'no'),
				('IWP_semaphore_$semaphore', '0', 'no')
			", current_time('mysql', 1)));
		}
	}

	public function backup_files() {
		# Note that the "false" for database gets over-ridden automatically if they turn out to have the same schedules
		$this->boot_backup(true, false);
	}
	
	public function backup_database() {
		# Note that nothing will happen if the file backup had the same schedule
		$this->boot_backup(false, true);
	}

	public function backup_all($options) {
		$skip_cloud = empty($options['nocloud']) ? false : false;
		$this->boot_backup(1, 1, false, false, ($skip_cloud) ? 'none' : false, $options);
	}
	
	public function backupnow_files($options) {
		$skip_cloud = empty($options['nocloud']) ? false : true;
		$this->boot_backup(1, 0, false, false, ($skip_cloud) ? 'none' : false, $options);
	}
	
	public function backupnow_database($options) {
		$skip_cloud = empty($options['nocloud']) ? false : true;
		$this->boot_backup(0, 1, false, false, ($skip_cloud) ? 'none' : false, $options);
	}

	// This procedure initiates a backup run
	// $backup_files/$backup_database: true/false = yes/no (over-write allowed); 1/0 = yes/no (force)
	public function boot_backup($backup_files, $backup_database, $restrict_files_to_override = false, $one_shot = false, $service = false, $options = array()) {

		@ignore_user_abort(true);
		@set_time_limit(IWP_SET_TIME_LIMIT);

		if (false === $restrict_files_to_override && isset($options['restrict_files_to_override'])) $restrict_files_to_override = $options['restrict_files_to_override'];
		// Generate backup information
		$use_nonce = (empty($options['use_nonce'])) ? false : $options['use_nonce'];
		$this->backup_time_nonce($use_nonce);
		// The current_resumption is consulted within logfile_open()
		$this->current_resumption = 0;
		$this->logfile_open($this->nonce);
		$iwp_backup_dir = $this->backups_dir_location();
		if (!is_file($this->logfile_name)) {
			$this->log('Failed to open log file ('.$this->logfile_name.') - the directory ('.$iwp_backup_dir.') for creating files in is not writable, or you ran out of disk space). Backup aborted.');
			$this->log(__('Could not create files in the backup directory. Backup aborted','InfiniteWP'), 'error');
			return false;
		}

		// Some house-cleaning
		$this->clean_temporary_files();
		
		// Log some information that may be helpful
		$this->log("Tasks: Backup files: $backup_files (schedule: ".IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_interval', 'unset').") Backup DB: $backup_database (schedule: ".IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_interval_database', 'unset').")");

		$semaphore = (($backup_files) ? 'f' : '') . (($backup_database) ? 'd' : '');
		$this->ensure_semaphore_exists($semaphore);

		if (!is_string($service) && !is_array($service)) $service = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_service');
		$service = $this->just_one($service);
		if (is_string($service)) $service = array($service);
		if (!is_array($service)) $service = array('none');

		if (!empty($options['extradata']) && preg_match('#services=remotesend/(\d+)#', $options['extradata'])) {
			if ($service === array('none')) $service = array();
			$service[] = 'remotesend';
		}

		$option_cache = array();
		foreach ($service as $serv) {
			if ('' == $serv || 'none' == $serv) continue;
			include_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/'.$serv.'.php');
			$cclass = 'IWP_MMB_UploadModule_'.$serv;
			if (!class_exists($cclass)) {
				error_log("InfiniteWP: backup class does not exist: $cclass");
				continue;
			}
			$obj = new $cclass;

			if (is_callable(array($obj, 'get_credentials'))) {
				$opts = $obj->get_credentials();
				if (is_array($opts)) {
					foreach ($opts as $opt) $option_cache[$opt] = IWP_MMB_Backup_Options::get_iwp_backup_option($opt);
				}
			}
		}

		// If nothing to be done, then just finish
		if (!$backup_files && !$backup_database) {
			$ret = $this->backup_finish(1, false, false, 0);
			// Don't keep useless log files
			if (!IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_debug_mode') && !empty($this->logfile_name) && file_exists($this->logfile_name)) {
				unlink($this->logfile_name);
			}
			return $ret;
		}

		// Are we doing an action called by the WP scheduler? If so, we want to check when that last happened; the point being that the dodgy WP scheduler, when overloaded, can call the event multiple times - and sometimes, it evades the semaphore because it calls a second run after the first has finished, or > 3 minutes (our semaphore lock time) later
		// doing_action() was added in WP 3.9
		// wp_cron() can be called from the 'init' action
		
		if (function_exists('doing_action') && (doing_action('init') || @constant('DOING_CRON')) && (doing_action('IWP_backup_database') || doing_action('IWP_backup'))) {
			$last_scheduled_action_called_at = get_option("IWP_last_scheduled_$semaphore");
			// 11 minutes - so, we're assuming that they haven't custom-modified their schedules to run scheduled backups more often than that. If they have, they need also to use the filter to over-ride this check.
			$seconds_ago = time() - $last_scheduled_action_called_at;
			if ($last_scheduled_action_called_at && $seconds_ago < 660 && apply_filters('IWP_check_repeated_scheduled_backups', true)) {
				$this->log(sprintf('Scheduled backup aborted - another backup of this type was apparently invoked by the WordPress scheduler only %d seconds ago - the WordPress scheduler invoking events multiple times usually indicates a very overloaded server (or other plugins that mis-use the scheduler)', $seconds_ago));
				return;
			}
		}
		update_option("IWP_last_scheduled_$semaphore", time());
		
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/class.semaphore.php');
		$this->semaphore = IWP_MMB_Semaphore::factory();
		$this->semaphore->lock_name = $semaphore;
		
		$semaphore_log_message = 'Requesting semaphore lock ('.$semaphore.')';
		if (!empty($last_scheduled_action_called_at)) {
			$semaphore_log_message .= " (apparently via scheduler: last_scheduled_action_called_at=$last_scheduled_action_called_at, seconds_ago=$seconds_ago)";
		} else {
			$semaphore_log_message .= " (apparently not via scheduler)";
		}
		
		$this->log($semaphore_log_message);
		if (!$this->semaphore->lock()) {
			$this->log('Failed to gain semaphore lock ('.$semaphore.') - another backup of this type is apparently already active - aborting (if this is wrong - i.e. if the other backup crashed without removing the lock, then another can be started after 3 minutes)');
			return;
		}
		
		// Allow the resume interval to be more than 300 if last time we know we went beyond that - but never more than 600
		if (defined('IWP_INITIAL_RESUME_INTERVAL') && is_numeric(IWP_INITIAL_RESUME_INTERVAL)) {
			$resume_interval = IWP_INITIAL_RESUME_INTERVAL;
		} else {
			$resume_interval = (int)min(max(300, get_site_transient('IWP_initial_resume_interval')), 600);
		}
		# We delete it because we only want to know about behaviour found during the very last backup run (so, if you move servers then old data is not retained)
		delete_site_transient('IWP_initial_resume_interval');

		$job_file_entities = array();
		if ($backup_files) {
			$possible_backups = $this->get_backupable_file_entities(true);
			foreach ($possible_backups as $youwhat => $whichdir) {
				if ((false === $restrict_files_to_override && IWP_MMB_Backup_Options::get_iwp_backup_option("IWP_include_$youwhat", apply_filters("IWP_defaultoption_include_$youwhat", true))) || (is_array($restrict_files_to_override) && !in_array($youwhat, $restrict_files_to_override))) {
					// The 0 indicates the zip file index
					$job_file_entities[$youwhat] = array(
						'index' => 0
					);
				}
			}
		}

		$followups_allowed = (((!$one_shot && defined('DOING_CRON') && DOING_CRON)) || (defined('IWP_FOLLOWUPS_ALLOWED') && IWP_FOLLOWUPS_ALLOWED));

		$split_every = max(intval(IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_split_every', 200)), IWP_SPLIT_MIN);

		$initial_jobdata = array(
			'resume_interval', $resume_interval,
			'job_type', 'backup',
			'jobstatus', 'begun',
			'backup_time', $this->backup_time,
			'job_time_ms', $this->job_time_ms,
			'service', $service,
			'split_every', $split_every,
			'maxzipbatch', 26214400, #25MB
			'job_file_entities', $job_file_entities,
			'option_cache', $option_cache,
			'uploaded_lastreset', 9,
			'one_shot', $one_shot,
			'followsups_allowed', $followups_allowed
		);

		

		if (!empty($options['extradata']) && 'autobackup' == $options['extradata']) array_push($initial_jobdata, 'is_autobackup', true);

		// Save what *should* be done, to make it resumable from this point on
		if ($backup_database) {
			$dbs = apply_filters('IWP_backup_databases', array('wp' => 'begun'));
			if (is_array($dbs)) {
				foreach ($dbs as $key => $db) {
					if ('wp' != $key && (!is_array($db) || empty($db['dbinfo']) || !is_array($db['dbinfo']) || empty($db['dbinfo']['host']))) unset($dbs[$key]);
				}
			}
		} else {
			$dbs = "no";
		}

		array_push($initial_jobdata, 'backup_database', $dbs);
		array_push($initial_jobdata, 'backup_files', (($backup_files) ? 'begun' : 'no'));

		if (is_array($options) && !empty($options['label'])) array_push($initial_jobdata, 'label', $options['label']);
		if (is_array($options) && !empty($options['backup_name'])) array_push($initial_jobdata, 'backup_name', $options['backup_name']);
		try {
			// Use of jobdata_set_multi saves around 200ms
			call_user_func_array(array($this, 'jobdata_set_multi'), apply_filters('IWP_initial_jobdata', $initial_jobdata, $options, $split_every));
		} catch (Exception $e) {
			$this->log($e->getMessage());
			return false;
		}

		// Everything is set up; now go
		if (!empty($options['cron_start'])) {
			$this->backup_resume(0, $this->nonce, 1);
		}else{
			$this->backup_resume(0, $this->nonce);
		}

	}

	// This function examines inside the InfiniteWP directory to see if any new archives have been uploaded. If so, it adds them to the backup set. (Non-present items are also removed, only if the service is 'none').
	// If $remotescan is set, then remote storage is also scanned
	// $only_add_this_file : an array with keys 'name' and (optionally) 'label' 
	public function rebuild_backup_history($remotescan = false, $only_add_this_file = false) {

		# TODO: Make compatible with incremental naming scheme

		$messages = array();
		$gmt_offset = get_option('gmt_offset');

		// Array of nonces keyed by filename
		$known_files = array();
		// Array of backup times keyed by nonce
		$known_nonces = array();
		$changes = false;
		$site_name = iwp_getSiteName();

		$backupable_entities = $this->get_backupable_file_entities(true, false);

		$backup_history = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_backup_history');
		if (!is_array($backup_history)) $backup_history = array();
		$iwp_backup_dir = $this->backups_dir_location();
		if (!is_dir($iwp_backup_dir)) return;

		$accept = apply_filters('IWP_accept_archivename', array());
		if (!is_array($accept)) $accept = array();
		// Process what is known from the database backup history; this means populating $known_files and $known_nonces
		foreach ($backup_history as $btime => $bdata) {
			$found_file = false;
			foreach ($bdata as $key => $values) {
				if ('db' != $key && !isset($backupable_entities[$key])) continue;
				// Record which set this file is found in
				if (!is_array($values)) $values=array($values);
				foreach ($values as $val) {
					if (!is_string($val)) continue;
					if (preg_match('/^'.$site_name.'backup_([\-0-9]{15})_.*_([0-9a-f]{12})-[\-a-z]+([0-9]+)?+(\.(zip|gz|gz\.crypt))?$/i', $val, $matches)) {
						$nonce = $matches[2];
						if (isset($bdata['service']) && ($bdata['service'] === 'none' || (is_array($bdata['service']) && (array('none') === $bdata['service'] || (1 == count($bdata['service']) && isset($bdata['service'][0]) && empty($bdata['service'][0]))))) && !is_file($iwp_backup_dir.'/'.$val)) {
							# File without remote storage is no longer present
						} else {
							$found_file = true;
							$known_files[$val] = $nonce;
							$known_nonces[$nonce] = (empty($known_nonces[$nonce]) || $known_nonces[$nonce]<100) ? $btime : min($btime, $known_nonces[$nonce]);
						}
					} else {
						$accepted = false;
						foreach ($accept as $fkey => $acc) {
							if (preg_match('/'.$acc['pattern'].'/i', $val)) $accepted = $fkey;
						}
						if (!empty($accepted) && (false != ($btime = apply_filters('IWP_foreign_gettime', false, $accepted, $val))) && $btime > 0) {
							$found_file = true;
							# Generate a nonce; this needs to be deterministic and based on the filename only
							$nonce = substr(md5($val), 0, 12);
							$known_files[$val] = $nonce;
							$known_nonces[$nonce] = (empty($known_nonces[$nonce]) || $known_nonces[$nonce]<100) ? $btime : min($btime, $known_nonces[$nonce]);
						}
					}
				}
			}
			if (!$found_file) {
				# File recorded as being without remote storage is no longer present - though it may in fact exist in remote storage, and this will be picked up later
				unset($backup_history[$btime]);
				$changes = true;
			}
		}

		$remotefiles = array();
		$remotesizes = array();

		if (!$handle = opendir($iwp_backup_dir)) return;

		// See if there are any more files in the local directory than the ones already known about
		while (false !== ($entry = readdir($handle))) {
			$accepted_foreign = false;
			$potmessage = false;

			if ($only_add_this_file !== false && $entry != $only_add_this_file['file']) continue;

			if ('.' == $entry || '..' == $entry) continue;
			# TODO: Make compatible with Incremental naming
			if (preg_match('/^'.$site_name.'backup_([\-0-9]{15})_.*_([0-9a-f]{12})-([\-a-z]+)([0-9]+)?(\.(zip|gz|gz\.crypt))?$/i', $entry, $matches)) {

				// Interpret the time as one from the blog's local timezone, rather than as UTC
				# $matches[1] is YYYY-MM-DD-HHmm, to be interpreted as being the local timezone
				$btime2 = strtotime($matches[1]);
				$btime = (!empty($gmt_offset)) ? $btime2 - $gmt_offset*3600 : $btime2;
				$nonce = $matches[2];
				$type = $matches[3];
				if ('db' == $type) {
					$type .= (!empty($matches[4])) ? $matches[4] : '';
					$index = 0;
				} else {
					$index = (empty($matches[4])) ? '0' : (max((int)$matches[4]-1,0));
				}
				$itext = ($index == 0) ? '' : $index;
			} elseif (false != ($accepted_foreign = apply_filters('IWP_accept_foreign', false, $entry)) && false !== ($btime = apply_filters('IWP_foreign_gettime', false, $accepted_foreign, $entry))) {
				$nonce = substr(md5($entry), 0, 12);
				$type = (preg_match('/\.sql(\.(bz2|gz))?$/i', $entry) || preg_match('/-database-([-0-9]+)\.zip$/i', $entry) || preg_match('/backup_db_/', $entry)) ? 'db' : 'wpcore';
				$index = apply_filters('IWP_accepted_foreign_index', 0, $entry, $accepted_foreign);
				$itext = $index ? $index : '';
				$potmessage = array(
					'code' => 'foundforeign_'.md5($entry),
					'desc' => $entry,
					'method' => '',
					'message' => sprintf(__('Backup created by: %s.', 'InfiniteWP'), $accept[$accepted_foreign]['desc'])
				);
			} elseif ('.zip' == strtolower(substr($entry, -4, 4)) || preg_match('/\.sql(\.(bz2|gz))?$/i', $entry)) {
				$potmessage = array(
					'code' => 'possibleforeign_'.md5($entry),
					'desc' => $entry,
					'method' => '',
					'message' => __('This file does not appear to be an InfiniteWP backup archive (such files are .zip or .gz files which have a name like: backup_(time)_(site name)_(code)_(type).(zip|gz)).', 'InfiniteWP')
				);
				$messages[$potmessage['code']] = $potmessage;
				continue;
			} else {
				continue;
			}
			// The time from the filename does not include seconds. Need to identify the seconds to get the right time
			if (isset($known_nonces[$nonce])) {
				$btime_exact = $known_nonces[$nonce];
				# TODO: If the btime we had was more than 60 seconds earlier, then this must be an increment - we then need to change the $backup_history array accordingly. We can pad the '60 second' test, as there's no option to run an increment more frequently than every 4 hours (though someone could run one manually from the CLI)
				if ($btime > 100 && $btime_exact - $btime > 60 && !empty($backup_history[$btime_exact])) {
					# TODO: This needs testing
					# The code below assumes that $backup_history[$btime] is presently empty
					# Re-key array, indicating the newly-found time to be the start of the backup set
					$backup_history[$btime] = $backup_history[$btime_exact];
					unset($backup_history[$btime_exact]);
					$btime_exact = $btime;
				}
				$btime = $btime_exact;
			}
			if ($btime <= 100) continue;
			$fs = @filesize($iwp_backup_dir.'/'.$entry);

			if (!isset($known_files[$entry])) {
				$changes = true;
				if (is_array($potmessage)) $messages[$potmessage['code']] = $potmessage;
				if (is_array($only_add_this_file)) {
					if (isset($only_add_this_file['label'])) $backup_history[$btime]['label'] = $only_add_this_file['label'];
					$backup_history[$btime]['native'] = false;
				} elseif ('db' == $type && !$accepted_foreign) {
					list ($mess, $warn, $err, $info) = $this->analyse_db_file(false, array(), $iwp_backup_dir.'/'.$entry, true);
					if (!empty($info['label'])) {
						$backup_history[$btime]['label'] = $info['label'];
					}
					if (!empty($info['created_by_version'])) {
						$backup_history[$btime]['created_by_version'] = $info['created_by_version'];
					}
				}
			}

			# TODO: Code below here has not been reviewed or adjusted for compatibility with incremental backups
			# Make sure we have the right list of services
			$current_services = (!empty($backup_history[$btime]) && !empty($backup_history[$btime]['service'])) ? $backup_history[$btime]['service'] : array();
			if (is_string($current_services)) $current_services = array($current_services);
			if (!is_array($current_services)) $current_services = array();
			if (!empty($remotefiles[$entry])) {
				if (0 == count(array_diff($current_services, $remotefiles[$entry]))) {
					$backup_history[$btime]['service'] = $remotefiles[$entry];
					$changes = true;
				}
				# Get the right size (our local copy may be too small)
				foreach ($remotefiles[$entry] as $rem) {
					if (!empty($rem['size']) && $rem['size'] > $fs) {
						$fs = $rem['size'];
						$changes = true;
					}
				}
				# Remove from $remotefiles, so that we can later see what was left over
				unset($remotefiles[$entry]);
			} else {
				# Not known remotely
				if (!empty($backup_history[$btime])) {
					if (empty($backup_history[$btime]['service']) || ('none' !== $backup_history[$btime]['service'] && ''  !== $backup_history[$btime]['service'] && array('none') !== $backup_history[$btime]['service'])) {
						$backup_history[$btime]['service'] = 'none';
						$changes = true;
					}
				} else {
					$backup_history[$btime]['service'] = 'none';
					$changes = true;
				}
			}

			$backup_history[$btime][$type][$index] = $entry;
			if ($fs > 0) $backup_history[$btime][$type.$itext.'-size'] = $fs;
			$backup_history[$btime]['nonce'] = $nonce;
			if (!empty($accepted_foreign)) $backup_history[$btime]['meta_foreign'] = $accepted_foreign;
		}

		# Any found in remote storage that we did not previously know about?
		# Compare $remotefiles with $known_files / $known_nonces, and adjust $backup_history
		if (count($remotefiles) > 0) {

			# $backup_history[$btime]['nonce'] = $nonce
			foreach ($remotefiles as $file => $services) {
				if (!preg_match('/^'.$site_name.'backup_([\-0-9]{15})_.*_([0-9a-f]{12})-([\-a-z]+)([0-9]+)?(\.(zip|gz|gz\.crypt))?$/i', $file, $matches)) continue;
				$nonce = $matches[2];
				$type = $matches[3];
				if ('db' == $type) {
					$index = 0;
					$type .= !empty($matches[4]) ? $matches[4] : '';
				} else {
					$index = (empty($matches[4])) ? '0' : (max((int)$matches[4]-1,0));
				}
				$itext = ($index == 0) ? '' : $index;
				$btime2 = strtotime($matches[1]);
				$btime = (!empty($gmt_offset)) ? $btime2 - $gmt_offset*3600 : $btime2;

				if (isset($known_nonces[$nonce])) $btime = $known_nonces[$nonce];
				if ($btime <= 100) continue;
				# Remember that at this point, we already know that the file is not known about locally
				if (isset($backup_history[$btime])) {
					if (!isset($backup_history[$btime]['service']) || ((is_array($backup_history[$btime]['service']) && $backup_history[$btime]['service'] !== $services) || is_string($backup_history[$btime]['service']) && (1 != count($services) || $services[0] !== $backup_history[$btime]['service']))) {
						$changes = true;
						$backup_history[$btime]['service'] = $services;
						$backup_history[$btime]['nonce'] = $nonce;
					}
					if (!isset($backup_history[$btime][$type][$index])) {
						$changes = true;
						$backup_history[$btime][$type][$index] = $file;
						$backup_history[$btime]['nonce'] = $nonce;
						if (!empty($remotesizes[$file])) $backup_history[$btime][$type.$itext.'-size'] = $remotesizes[$file];
					}
				} else {
					$changes = true;
					$backup_history[$btime]['service'] = $services;
					$backup_history[$btime][$type][$index] = $file;
					$backup_history[$btime]['nonce'] = $nonce;
					if (!empty($remotesizes[$file])) $backup_history[$btime][$type.$itext.'-size'] = $remotesizes[$file];
					$backup_history[$btime]['native'] = false;
					$messages['nonnative'] = array(
						'message' => __('One or more backups has been added from scanning remote storage; note that these backups will not be automatically deleted through the "retain" settings; if/when you wish to delete them then you must do so manually.', 'InfiniteWP'),
						'code' => 'nonnative',
						'desc' => '',
						'method' => ''
					);
				}

			}
		}

		if ($changes) IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_backup_history', $backup_history);

		return $messages;

	}

	private function backup_finish($cancel_event, $do_cleanup, $allow_email, $resumption_no, $force_abort = false) {

		if (!empty($this->semaphore)) $this->semaphore->unlock();

		$delete_jobdata = false;

		// The valid use of $do_cleanup is to indicate if in fact anything exists to clean up (if no job really started, then there may be nothing)

		// In fact, leaving the hook to run (if debug is set) is harmless, as the resume job should only do tasks that were left unfinished, which at this stage is none.
		if (0 == $this->error_count() || $force_abort) {
			if ($do_cleanup) {
				$this->log("There were no errors in the uploads, so the 'resume' event ($cancel_event) is being unscheduled");
				# This apparently-worthless setting of metadata before deleting it is for the benefit of a WP install seen where wp_clear_scheduled_hook() and delete_transient() apparently did nothing (probably a faulty cache)
				$this->jobdata_set('jobstatus', 'finished');
				wp_clear_scheduled_hook('IWP_backup_resume', array($cancel_event, $this->nonce));
				# This should be unnecessary - even if it does resume, all should be detected as finished; but I saw one very strange case where it restarted, and repeated everything; so, this will help
				wp_clear_scheduled_hook('IWP_backup_resume', array($cancel_event+1, $this->nonce));
				wp_clear_scheduled_hook('IWP_backup_resume', array($cancel_event+2, $this->nonce));
				wp_clear_scheduled_hook('IWP_backup_resume', array($cancel_event+3, $this->nonce));
				wp_clear_scheduled_hook('IWP_backup_resume', array($cancel_event+4, $this->nonce));
				$delete_jobdata = true;
			}
		} else {
			$this->log("There were errors in the uploads, so the 'resume' event is remaining scheduled");
			$this->jobdata_set('jobstatus', 'resumingforerrors');
			# If there were no errors before moving to the upload stage, on the first run, then bring the resumption back very close. Since this is only attempted on the first run, it is really only an efficiency thing for a quicker finish if there was an unexpected networking event. We don't want to do it straight away every time, as it may be that the cloud service is down - and might be up in 5 minutes time. This was added after seeing a case where resumption 0 got to run for 10 hours... and the resumption 7 that should have picked up the uploading of 1 archive that failed never occurred.
			if (isset($this->error_count_before_cloud_backup) && 0 === $this->error_count_before_cloud_backup) {
				if (0 == $resumption_no) {
					$this->reschedule(60);
				} else {
					// Added 27/Feb/2016 - though the cloud service seems to be down, we still don't want to wait too long
					$resume_interval = $this->jobdata_get('resume_interval');
					
					// 15 minutes + 2 for each resumption (a modest back-off)
					$max_interval = 900 + $resumption_no * 120;
					if ($resume_interval > $max_interval) {
						$this->reschedule($max_interval);
					}
				}
			}
		}

		// Send the results email if appropriate, which means:
		// - The caller allowed it (which is not the case in an 'empty' run)
		// - And: An email address was set (which must be so in email mode)
		// And one of:
		// - Debug mode
		// - There were no errors (which means we completed and so this is the final run - time for the final report)
		// - It was the tenth resumption; everything failed
		# Save the jobdata's state for the reporting - because it might get changed (e.g. incremental backup is scheduled)
		$jobdata_as_was = $this->jobdata;

		// Make sure that the final status is shown
		if ($force_abort) {
			$send_an_email = true;
			$final_message = __('The backup was aborted by the user', 'InfiniteWP');
		} elseif (0 == $this->error_count()) {
			$send_an_email = true;
			$service = $this->jobdata_get('service');
			$remote_sent = (!empty($service) && ((is_array($service) && in_array('remotesend', $service)) || 'remotesend' === $service)) ? true : false;
			$userid = get_current_user_id();
			$backup_files = $this->jobdata_get('backup_files');
			$backup_database = $this->jobdata_get('backup_database');
			$what = 'full';
			if ($backup_files == 'no') {
				$what = 'db';
			}elseif($backup_database == 'no'){
				$what = 'files';
			}
			if (0 == $this->error_count('warning')) {
				$final_message = __('The backup apparently succeeded and is now complete', 'InfiniteWP');
				# Ensure it is logged in English. Not hugely important; but helps with a tiny number of really broken setups in which the options cacheing is broken
				if ('The backup apparently succeeded and is now complete' != $final_message) {
					$this->log('The backup apparently succeeded and is now complete');
				}
				delete_option('IWP_jobdata_'.$this->nonce);
				update_option('IWP_backup_status', '0');
				$GLOBALS['iwp_mmb_activities_log']->iwp_mmb_save_iwp_activities('backup', 'multiCallNow', 'direct', array('what' => $what), $userid);
			} else {
				$final_message = __('The backup apparently succeeded (with warnings) and is now complete','InfiniteWP');
				if ('The backup apparently succeeded (with warnings) and is now complete' != $final_message) {
					$this->log('The backup apparently succeeded (with warnings) and is now complete');
				}
				$GLOBALS['iwp_mmb_activities_log']->iwp_mmb_save_iwp_activities('backup', 'multiCallNow', 'direct', array('what' => $what), $userid);
				delete_option('IWP_jobdata_'.$this->nonce);
				update_option('IWP_backup_status', '0');
			}
			if ($remote_sent && !$force_abort) $final_message .= '. '.__('To complete your migration/clone, you should now log in to the remote site and restore the backup set.', 'InfiniteWP');
			if ($do_cleanup) $delete_jobdata = apply_filters('IWP_backup_complete', $delete_jobdata);
		} elseif (false == $this->newresumption_scheduled) {
			$send_an_email = true;
			wp_clear_scheduled_hook('IWP_backup_resume');
			$this->kill_new_backup(array('result_id'=>$this->nonce));
			$final_message = __('The backup attempt has finished, apparently unsuccessfully', 'InfiniteWP');
		} else {
			// There are errors, but a resumption will be attempted
			$final_message = __('The backup has not finished; a resumption is scheduled', 'InfiniteWP');
		}

		global $IWP_backup;

		if ($force_abort) $jobdata_as_was['aborted'] = true;

		# Make sure this is the final message logged (so it remains on the dashboard)
		$this->log($final_message);

		@fclose($this->logfile_handle);
		$this->logfile_handle = null;

		// This is left until last for the benefit of the front-end UI, which then gets maximum chance to display the 'finished' status
		if ($delete_jobdata) {
			delete_option('IWP_jobdata_'.$this->nonce);
			update_option('IWP_backup_status', '0');
		}

	}

	// This function returns 'true' if mod_rewrite could be detected as unavailable; a 'false' result may mean it just couldn't find out the answer
	public function mod_rewrite_unavailable($check_if_in_use_first = true) {
		if (function_exists('apache_get_modules')) {
			global $wp_rewrite;
			$mods = apache_get_modules();
			if ((!$check_if_in_use_first || $wp_rewrite->using_mod_rewrite_permalinks()) && ((in_array('core', $mods) || in_array('http_core', $mods)) && !in_array('mod_rewrite', $mods))) {
				return true;
			}
		}
		return false;
	}
	
	public function error_count($level = 'error') {
		$count = 0;
		foreach ($this->errors as $err) {
			if (('error' == $level && (is_string($err) || is_wp_error($err))) || (is_array($err) && $level == $err['level']) ) { $count++; }
		}
		return $count;
	}

	public function list_errors() {
		echo '<ul style="list-style: disc inside;">';
		foreach ($this->errors as $err) {
			if (is_wp_error($err)) {
				foreach ($err->get_error_messages() as $msg) {
					echo '<li>'.htmlspecialchars($msg).'<li>';
				}
			} elseif (is_array($err) && ('error' == $err['level'] || 'warning' == $err['level'])) {
				echo  "<li>".htmlspecialchars($err['message'])."</li>";
			} elseif (is_string($err)) {
				echo  "<li>".htmlspecialchars($err)."</li>";
			} else {
				print "<li>".print_r($err,true)."</li>";
			}
		}
		echo '</ul>';
	}

	private function save_last_backup($backup_array) {
		$success = ($this->error_count() == 0) ? 1 : 0;
		$last_backup = apply_filters('IWP_save_last_backup', array(
			'backup_time' => $this->backup_time,
			'backup_array' => $backup_array,
			'success' => $success,
			'errors' => $this->errors,
			'backup_nonce' => $this->nonce
		));
		IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_last_backup', $last_backup, false);
	}

	# $handle must be either false or a WPDB class (or extension thereof). Other options are not yet fully supported.
	public function check_db_connection($handle = false, $logit = false, $reschedule = false) {

		$type = false;
		if (false === $handle || is_a($handle, 'wpdb')) {
			$type='wpdb';
		} elseif (is_resource($handle)) {
			# Expected: string(10) "mysql link"
			$type=get_resource_type($handle);
		} elseif (is_object($handle) && is_a($handle, 'mysqli')) {
			$type='mysqli';
		}
 
		if (false === $type) return -1;

		$db_connected = -1;

		if ('mysql link' == $type || 'mysqli' == $type) {
			if ('mysql link' == $type && @mysql_ping($handle)) return true;
			if ('mysqli' == $type && @mysqli_ping($handle)) return true;

			for ( $tries = 1; $tries <= 5; $tries++ ) {
				# to do, if ever needed
// 				if ( $this->db_connect( false ) ) return true;
// 				sleep( 1 );
			}

		} elseif ('wpdb' == $type) {
			if (false === $handle || (is_object($handle) && 'wpdb' == get_class($handle))) {
				global $wpdb;
				$handle = $wpdb;
			}
			if (method_exists($handle, 'check_connection') && (!defined('IWP_SUPPRESS_CONNECTION_CHECKS') || !IWP_SUPPRESS_CONNECTION_CHECKS)) {
				if (!$handle->check_connection(false)) {
					if ($logit) $this->log("The database went away, and could not be reconnected to");
					# Almost certainly a no-op
					if ($reschedule) $this->reschedule(60);
					$db_connected = false;
				} else {
					$db_connected = true;
				}
			}
		}

		return $db_connected;

	}

	// This should be called whenever a file is successfully uploaded
	public function uploaded_file($file, $force = false) {
	
		global $IWP_backup;

		$db_connected = $this->check_db_connection(false, true, true);

		$service = empty($IWP_backup->current_service) ? '' : $IWP_backup->current_service;
		$shash = $service.'-'.md5($file);

		$this->jobdata_set("uploaded_".$shash, 'yes');
	
		if ($force || !empty($IWP_backup->last_service)) {
			$hash = md5($file);
			$this->log("Recording as successfully uploaded: $file ($hash)");
			$this->jobdata_set('uploaded_lastreset', $this->current_resumption);
			$this->jobdata_set("uploaded_".$hash, 'yes');
		} else {
			$this->log("Recording as successfully uploaded: $file (".$IWP_backup->current_service.", more services to follow)");
		}

		$upload_status = $this->jobdata_get('uploading_substatus');
		if (is_array($upload_status) && isset($upload_status['i'])) {
			$upload_status['i']++;
			$upload_status['p']=0;
			$this->jobdata_set('uploading_substatus', $upload_status);
		}

		# Really, we could do this immediately when we realise the DB has gone away. This is just for the probably-impossible case that a DB write really can still succeed. But, we must abort before calling delete_local(), as the removal of the local file can cause it to be recreated if the DB is out of sync with the fact that it really is already uploaded
		if (false === $db_connected) {
			$this->record_still_alive();
			die;
		}

		// Delete local files immediately if the option is set
		// Where we are only backing up locally, only the "prune" function should do deleting
		$service = $this->jobdata_get('service');
		if (!empty($IWP_backup->last_service) && ($service !== '' && ((is_array($service) && count($service)>0 && (count($service) > 1 || ($service[0] != '' && $service[0] != 'none'))) || (is_string($service) && $service !== 'none')))) {
			$this->delete_local($file);
		}
	}

	public function is_uploaded($file, $service = '') {
		$hash = $service.(('' == $service) ? '' : '-').md5($file);
		return ($this->jobdata_get("uploaded_$hash") === "yes") ? true : false;
	}

	private function delete_local($file) {
		$log = "Deleting local file: $file: ";
		if (IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_delete_local')) {
			$fullpath = $this->backups_dir_location().'/'.$file;

			//check to make sure it exists before removing
			if(realpath($fullpath)){
				$deleted = unlink($fullpath);
				$this->log($log.(($deleted) ? 'OK' : 'failed'));
				return $deleted;
			}
		} else {
			$this->log($log."skipped: user has unchecked IWP_delete_local option");
		}
		return true;
	}

	// This function is not needed for backup success, according to the design, but it helps with efficient scheduling
	private function reschedule_if_needed() {
		// If nothing is scheduled, then return
		if (empty($this->newresumption_scheduled)) return;
		$time_now = time();
		$time_away = $this->newresumption_scheduled - $time_now;
		// 45 is chosen because it is 15 seconds more than what is used to detect recent activity on files (file mod times). (If we use exactly the same, then it's more possible to slightly miss each other)
		if ($time_away >1 && $time_away <= 45) {
			$this->log('The scheduled resumption is within 45 seconds - will reschedule');
			// Push 45 seconds into the future
 			// $this->reschedule(60);
			// Increase interval generally by 45 seconds, on the assumption that our prior estimates were innaccurate (i.e. not just 45 seconds *this* time)
			$this->increase_resume_and_reschedule(45);
		}
	}

	public function reschedule($how_far_ahead, $first_call=false) {
		// Reschedule - remove presently scheduled event
		$next_resumption = $this->current_resumption + 1;
		wp_clear_scheduled_hook('IWP_backup_resume', array($next_resumption, $this->nonce));
		// Add new event
		# This next line may be too cautious; but until 14-Aug-2014, it was 300.
		# Update 20-Mar-2015 - lowered from 180
		if ($how_far_ahead < 120 && !$first_call) $how_far_ahead=120;
		$schedule_for = time() + $how_far_ahead;
		$this->log("Rescheduling resumption $next_resumption: moving to $how_far_ahead seconds from now ($schedule_for)");
		wp_schedule_single_event($schedule_for, 'IWP_backup_resume', array($next_resumption, $this->nonce));
		$this->newresumption_scheduled = $schedule_for;
	}

	private function increase_resume_and_reschedule($howmuch = 120, $force_schedule = false) {

		$resume_interval = max(intval($this->jobdata_get('resume_interval')), ($howmuch === 0) ? 120 : 300);

		if (empty($this->newresumption_scheduled) && $force_schedule) {
			$this->log("A new resumption will be scheduled to prevent the job ending");
		}

		$new_resume = $resume_interval + $howmuch;
		# It may be that we're increasing for the second (or more) time during a run, and that we already know that the new value will be insufficient, and can be increased
		if ($this->opened_log_time > 100 && microtime(true)-$this->opened_log_time > $new_resume) {
			$new_resume = ceil(microtime(true)-$this->opened_log_time)+45;
			$howmuch = $new_resume-$resume_interval;
		}

		# This used to be always $new_resume, until 14-Aug-2014. However, people who have very long-running processes can end up with very long times between resumptions as a result.
		# Actually, let's not try this yet. I think it is safe, but think there is a more conservative solution available.
		#$how_far_ahead = min($new_resume, 600);
		$how_far_ahead = $new_resume;
		# If it is very long-running, then that would normally be known soon.
		# If the interval is already 12 minutes or more, then try the next resumption 10 minutes from now (i.e. sooner than it would have been). Thus, we are guaranteed to get at least 24 minutes of processing in the first 34.
		if ($this->current_resumption <= 1 && $new_resume > 720) $how_far_ahead = 600;

		if (!empty($this->newresumption_scheduled) || $force_schedule) $this->reschedule($how_far_ahead);
		$this->jobdata_set('resume_interval', $new_resume);

		$this->log("To decrease the likelihood of overlaps, increasing resumption interval to: $resume_interval + $howmuch = $new_resume");
	}

	// For detecting another run, and aborting if one was found
	public function check_recent_modification($file) {
		if (file_exists($file)) {
			$time_mod = (int)@filemtime($file);
			$time_now = time();
			if ($time_mod>100 && ($time_now-$time_mod)<30) {
				$this->terminate_due_to_activity($file, $time_now, $time_mod);
			}
		}
	}

	public function get_exclude($whichone) {
		if ('uploads' == $whichone) {
			$exclude = explode(',', IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_include_uploads_exclude', IWP_DEFAULT_UPLOADS_EXCLUDE));
		} elseif ('others' == $whichone) {
			$exclude = explode(',', IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_include_others_exclude', IWP_DEFAULT_OTHERS_EXCLUDE));
		} else {
			$exclude = apply_filters('IWP_include_'.$whichone.'_exclude', array());
		}
		return (empty($exclude) || !is_array($exclude)) ? array() : $exclude;
	}

	public function really_is_writable($dir) {
		// Suppress warnings, since if the user is dumping warnings to screen, then invalid JavaScript results and the screen breaks.
		if (!@is_writable($dir)) return false;
		// Found a case - GoDaddy server, Windows, PHP 5.2.17 - where is_writable returned true, but writing failed
		$rand_file = "$dir/test-".md5(rand().time()).".txt";
		while (file_exists($rand_file)) {
			$rand_file = "$dir/test-".md5(rand().time()).".txt";
		}
		$ret = @file_put_contents($rand_file, 'testing...');
		@unlink($rand_file);
		return ($ret > 0);
	}

	public function wp_upload_dir() {
		if (is_multisite()) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		
		$wp_upload_dir = wp_upload_dir();
		
		if (is_multisite()) restore_current_blog();

		return $wp_upload_dir;
	}

	public function backup_uploads_dirlist($logit = false) {
		# Create an array of directories to be skipped
		# Make the values into the keys
		$exclude = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_include_uploads_exclude', IWP_DEFAULT_UPLOADS_EXCLUDE);
		if ($logit) $this->log("Exclusion option setting (uploads): ".$exclude);
		$skip = array_flip(preg_split("/,/", $exclude));
		$wp_upload_dir = $this->wp_upload_dir();
		$uploads_dir = $wp_upload_dir['basedir'];
		return $this->compile_folder_list_for_backup($uploads_dir, array(), $skip);
	}

	public function backup_others_dirlist($logit = false) {
		# Create an array of directories to be skipped
		# Make the values into the keys
		$exclude = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_include_others_exclude', IWP_DEFAULT_OTHERS_EXCLUDE);
		if ($logit) $this->log("Exclusion option setting (others): ".$exclude);
		$skip = array_flip(preg_split("/,/", $exclude));
		$file_entities = $this->get_backupable_file_entities(false);

		# Keys = directory names to avoid; values = the label for that directory (used only in log files)
		#$avoid_these_dirs = array_flip($file_entities);
		$avoid_these_dirs = array();
		foreach ($file_entities as $type => $dirs) {
			if (is_string($dirs)) {
				$avoid_these_dirs[$dirs] = $type;
			} elseif (is_array($dirs)) {
				foreach ($dirs as $dir) {
					$avoid_these_dirs[$dir] = $type;
				}
			}
		}
		return $this->compile_folder_list_for_backup(WP_CONTENT_DIR, $avoid_these_dirs, $skip);
	}

	public function backup_more_dirlist($whichdir = false) {
		# Create an array of directories to be skipped
		# Make the values into the keys
		
		

		# Keys = directory names to avoid; values = the label for that directory (used only in log files)
		#$avoid_these_dirs = array_flip($file_entities);
		$avoid_these_dirs = array();
		$skip = array();
		$dir_list = $this->compile_folder_list_for_backup($whichdir, $avoid_these_dirs, $skip);
		$include = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_default_includes', IWP_DEFAULT_INCLUDES);
		foreach ($dir_list as $key => $value) {
			if (!in_array(str_replace(ABSPATH, '', $value), explode(',',$include))) {
				unset($dir_list[$key]);		
			}
		}
		return $dir_list;
	}

	// Add backquotes to tables and db-names in SQL queries. Taken from phpMyAdmin.
	public function backquote($a_name) {
		if (!empty($a_name) && $a_name != '*') {
			if (is_array($a_name)) {
				$result = array();
				reset($a_name);
				while(list($key, $val) = each($a_name)) 
					$result[$key] = '`'.$val.'`';
				return $result;
			} else {
				return '`'.$a_name.'`';
			}
		} else {
			return $a_name;
		}
	}

	public function strip_dirslash($string) {
		return preg_replace('#/+(,|$)#', '$1', $string);
	}

	public function remove_empties($list) {
		if (!is_array($list)) return $list;
		foreach ($list as $ind => $entry) {
			if (empty($entry)) unset($list[$ind]);
		}
		return $list;
	}

	// avoid_these_dirs and skip_these_dirs ultimately do the same thing; but avoid_these_dirs takes full paths whereas skip_these_dirs takes basenames; and they are logged differently (dirs in avoid are potentially dangerous to include; skip is just a user-level preference). They are allowed to overlap.
	public function compile_folder_list_for_backup($backup_from_inside_dir, $avoid_these_dirs, $skip_these_dirs) {

		// Entries in $skip_these_dirs are allowed to end in *, which means "and anything else as a suffix". It's not a full shell glob, but it covers what is needed to-date.

		$dirlist = array();
		$added = 0;

		$this->log('Looking for candidates to back up in: '.$backup_from_inside_dir);
		$iwp_backup_dir = $this->backups_dir_location();

		if (is_file($backup_from_inside_dir)) {
			array_push($dirlist, $backup_from_inside_dir);
			$added++;
			$this->log("finding files: $backup_from_inside_dir: adding to list ($added)");
		} elseif ($handle = opendir($backup_from_inside_dir)) {
			
			while (false !== ($entry = readdir($handle))) {
				// $candidate: full path; $entry = one-level
				$candidate = $backup_from_inside_dir.'/'.$entry;
				if ($entry != "." && $entry != "..") {
					if (isset($avoid_these_dirs[$candidate])) {
						$this->log("finding files: $entry: skipping: this is the ".$avoid_these_dirs[$candidate]." directory");
					} elseif ($candidate == $iwp_backup_dir) {
						$this->log("finding files: $entry: skipping: this is the InfiniteWP directory");
					} elseif (isset($skip_these_dirs[$entry])) {
						$this->log("finding files: $entry: skipping: excluded by options");
					} else {
						$add_to_list = true;
						// Now deal with entries in $skip_these_dirs ending in * or starting with *
						foreach ($skip_these_dirs as $skip => $sind) {
							if ('*' == substr($skip, -1, 1) && '*' == substr($skip, 0, 1) && strlen($skip) > 2) {
								if (strpos($entry, substr($skip, 1, strlen($skip-2))) !== false) {
									$this->log("finding files: $entry: skipping: excluded by options (glob)");
									$add_to_list = false;
								}
							} elseif ('*' == substr($skip, -1, 1) && strlen($skip) > 1) {
								if (substr($entry, 0, strlen($skip)-1) == substr($skip, 0, strlen($skip)-1)) {
									$this->log("finding files: $entry: skipping: excluded by options (glob)");
									$add_to_list = false;
								}
							} elseif ('*' == substr($skip, 0, 1) && strlen($skip) > 1) {
								if (strlen($entry) >= strlen($skip)-1 && substr($entry, (strlen($skip)-1)*-1) == substr($skip, 1)) {
									$this->log("finding files: $entry: skipping: excluded by options (glob)");
									$add_to_list = false;
								}
							}
						}
						if ($add_to_list) {
							array_push($dirlist, $candidate);
							$added++;
							$skip_dblog = (($added > 50 && 0 != $added % 100) || ($added > 2000 && 0 != $added % 500));
							$this->log("finding files: $entry: adding to list ($added)", 'notice', false, $skip_dblog);
						}
					}
				}
			}
			@closedir($handle);
		} else {
			$this->log('ERROR: Could not read the directory: '.$backup_from_inside_dir);
			$this->log(__('Could not read the directory', 'InfiniteWP').': '.$backup_from_inside_dir, 'error');
		}

		return $dirlist;

	}

	private function save_backup_history($backup_array) {
		if(is_array($backup_array)) {
			$backup_history = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_backup_history');
			$backup_history = (is_array($backup_history)) ? $backup_history : array();
			$backup_array['nonce'] = $this->nonce;
			$backup_array['service'] = $this->jobdata_get('service');
			if (!empty($backup_array['service'][0]) && $backup_array['service'][0] != 'none') {
				$service = 'IWP_'.$backup_array['service'][0];
				$backup_array['service_setting'] = IWP_MMB_Backup_Options::get_iwp_backup_option($service);
			}
			if ('' != ($label = $this->jobdata_get('label', ''))) $backup_array['label'] = $label;
			if ('' != ($backup_name = $this->jobdata_get('backup_name', ''))) $backup_array['backup_name'] = $backup_name;
			$backup_array['created_by_version'] = $this->version;
			$backup_array['is_multisite'] = is_multisite() ? true : false;
			$backup_array['wp_content_url'] = content_url();
			$backup_array['wp_content_path'] = WP_CONTENT_DIR;
			$backup_array['old_url'] = get_option('siteurl');
			$backup_array['old_file_path'] = ABSPATH;
			$remotesend_info = $this->jobdata_get('remotesend_info');
			if (is_array($remotesend_info) && !empty($remotesend_info['url'])) $backup_array['remotesend_url'] = $remotesend_info['url'];
			if (false != ($autobackup = $this->jobdata_get('is_autobackup', false))) $backup_array['autobackup'] = true;
			$backup_history[$this->backup_time] = $backup_array;
			IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_backup_history', $backup_history, false);
		} else {
			$this->log('Could not save backup history because we have no backup array. Backup probably failed.');
			$this->log(__('Could not save backup history because we have no backup array. Backup probably failed.','InfiniteWP'), 'error');
		}
	}
	
	public function is_db_encrypted($file) {
		return preg_match('/\.crypt$/i', $file);
	}

	public function get_backup_history($timestamp = false) {
		$backup_history = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_backup_history');
		// The line below actually *introduces* a race condition
 		// global $wpdb;
 		// $backup_history = @unserialize($wpdb->get_var($wpdb->prepare("SELECT option_value from $wpdb->options WHERE option_name='IWP_backup_history'")));
		if (is_array($backup_history)) {
			krsort($backup_history); //reverse sort so earliest backup is last on the array. Then we can array_pop.
		} else {
			$backup_history = array();
		}
		if (!$timestamp) return $backup_history;
		return (isset($backup_history[$timestamp])) ? $backup_history[$timestamp] : array();
	}

	public function terminate_due_to_activity($file, $time_now, $time_mod, $increase_resumption = true) {
		# We check-in, to avoid 'no check in last time!' detectors firing
		$this->record_still_alive();
		$file_size = file_exists($file) ? round(filesize($file)/1024,1). 'KB' : 'n/a';
		$this->log("Terminate: ".basename($file)." exists with activity within the last 30 seconds (time_mod=$time_mod, time_now=$time_now, diff=".(floor($time_now-$time_mod)).", size=$file_size). This likely means that another InfiniteWP run is at work; so we will exit.");
		$increase_by = ($increase_resumption) ? 120 : 0;
		$this->increase_resume_and_reschedule($increase_by, true);
		if (!defined('IWP_ALLOW_RECENT_ACTIVITY') || true != IWP_ALLOW_RECENT_ACTIVITY) die;
	}

	# Replace last occurence
	public function str_lreplace($search, $replace, $subject) {
		$pos = strrpos($subject, $search);
		if($pos !== false) $subject = substr_replace($subject, $replace, $pos, strlen($search));
		return $subject;
	}

	public function str_replace_once($needle, $replace, $haystack) {
		$pos = strpos($haystack, $needle);
		return ($pos !== false) ? substr_replace($haystack,$replace,$pos,strlen($needle)) : $haystack;
	}

	/*
		If files + db are on different schedules but are scheduled for the same time, then combine them
		$event = (object) array( 'hook' => $hook, 'timestamp' => $timestamp, 'schedule' => $recurrence, 'args' => $args, 'interval' => $schedules[$recurrence]['interval'] );
		See wp_schedule_single_event() and wp_schedule_event() in wp-includes/cron.php
	*/
	public function schedule_event($event) {
	
		static $scheduled = array();
	
		
		if (is_object($event) && ('IWP_backup' == $event->hook || 'IWP_backup_database' == $event->hook)) {
		
			// Reset the option - but make sure it is saved first so that we can used it (since this hook may be called just before our actual cron task)
			$this->combine_jobs_around = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_combine_jobs_around');
			
			IWP_MMB_Backup_Options::delete_iwp_backup_option('IWP_combine_jobs_around');
		
			$scheduled[$event->hook] = true;
			
			// This next fragment is wrong: there's only a 'second call' when saving all settings; otherwise, the WP scheduler might just be updating one event. So, there's some inefficieny as the option is wiped and set uselessly at least once when saving settings.
			// We only want to take action on the second call (otherwise, our information is out-of-date already)
			// If there is no second call, then that's fine - nothing to do
			//if (count($scheduled) < 2) {
			//	return $event;
			//}
		
			$backup_scheduled_for =  ('IWP_backup' == $event->hook) ? $event->timestamp : wp_next_scheduled('IWP_backup');
			$db_scheduled_for = ('IWP_backup_database' == $event->hook) ? $event->timestamp : wp_next_scheduled('IWP_backup_database');
		
			$diff = absint($backup_scheduled_for - $db_scheduled_for);
			
			$margin = (defined('IWP_COMBINE_MARGIN') && is_numeric(IWP_COMBINE_MARGIN)) ? IWP_COMBINE_MARGIN : 600;
			
			if ($backup_scheduled_for && $db_scheduled_for && $diff < $margin) {
				// We could change the event parameters; however, this would complicate other code paths (because the WP cron system uses a hash of the parameters as a key, and you must supply the exact parameters to look up events). So, we just set a marker that boot_backup() can pick up on.
				IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_combine_jobs_around', min($backup_scheduled_for, $db_scheduled_for));
			}
			
		}
	
		return $event;
	
	}
	
	/*
		This function is both the backup scheduler and a filter callback for saving the option.
		It is called in the register_setting for the IWP_interval, which means when the
		admin settings are saved it is called.
	*/
	public function schedule_backup($interval) {
		$previous_time = wp_next_scheduled('IWP_backup');

		// Clear schedule so that we don't stack up scheduled backups
		wp_clear_scheduled_hook('IWP_backup');
		if ('manual' == $interval) return 'manual';
		$previous_interval = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_interval');

		$valid_schedules = wp_get_schedules();
		if (empty($valid_schedules[$interval])) $interval = 'daily';

		// Try to avoid changing the time is one was already scheduled. This is fairly conservative - we could do more, e.g. check if a backup already happened today.
		$default_time = ($interval == $previous_interval && $previous_time>0) ? $previous_time : time()+120;
		$first_time = apply_filters('IWP_schedule_firsttime_files', $default_time);

		wp_schedule_event($first_time, $interval, 'IWP_backup');

		return $interval;
	}

	public function schedule_backup_database($interval) {
		$previous_time = wp_next_scheduled('IWP_backup_database');

		// Clear schedule so that we don't stack up scheduled backups
		wp_clear_scheduled_hook('IWP_backup_database');
		if ('manual' == $interval) return 'manual';

		$previous_interval = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_interval_database');

		$valid_schedules = wp_get_schedules();
		if (empty($valid_schedules[$interval])) $interval = 'daily';

		// Try to avoid changing the time is one was already scheduled. This is fairly conservative - we could do more, e.g. check if a backup already happened today.
		$default_time = ($interval == $previous_interval && $previous_time>0) ? $previous_time : time()+120;

		$first_time = apply_filters('IWP_schedule_firsttime_db', $default_time);
		wp_schedule_event($first_time, $interval, 'IWP_backup_database');

		return $interval;
	}

	public function ftp_sanitise($ftp) {
		if (is_array($ftp) && !empty($ftp['host']) && preg_match('#ftp(es|s)?://(.*)#i', $ftp['host'], $matches)) {
			$ftp['host'] = untrailingslashit($matches[2]);
		}
		return $ftp;
	}

	public function s3_sanitise($s3) {
		if (is_array($s3) && !empty($s3['path']) && '/' == substr($s3['path'], 0, 1)) {
			$s3['path'] = substr($s3['path'], 1);
		}
		return $s3;
	}

	public function remove_local_directory($dir, $contents_only = false) {
		// PHP 5.3+ only
		//foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
		//	$path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
		//}
		//return rmdir($dir);

		if ($handle = @opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
				if ('.' !== $entry && '..' !== $entry) {
					if (is_dir($dir.'/'.$entry)) {
						$this->remove_local_directory($dir.'/'.$entry, false);
					} else {
						@unlink($dir.'/'.$entry);
					}
				}
			}
			@closedir($handle);
		}

		return ($contents_only) ? true : rmdir($dir);
	}

	// Returns without any trailing slash
	public function backups_dir_location($allow_cache = true) {

		if ($allow_cache && !empty($this->backup_dir)) return $this->backup_dir;

		if(!file_exists(IWP_BACKUP_DIR) && !is_dir(IWP_BACKUP_DIR)){
			$mkdir = @mkdir(IWP_BACKUP_DIR, 0755, true);
			if(!$mkdir){
				return  array('error' => 'Permission denied; Make sure you have write permission for the wp-content folder.', 'error_code' => 'permission_denied_make_sure_you_have_write_permission_for_the_wp_content_folder');
			}
		}
		if(is_writable(IWP_BACKUP_DIR)){
			@file_put_contents(IWP_BACKUP_DIR . '/index.php', ''); //safe
			
		}else{
			$chmod = chmod(IWP_BACKUP_DIR, 777);
			if(!is_writable(IWP_BACKUP_DIR)){
				return array('error' => IWP_BACKUP_DIR.' directory is not writable. Please set 755 or 777 file permission and try again.', 'error_code' => 'backup_dir_is_not_writable');
			}
		}

		$this->backup_dir = IWP_BACKUP_DIR;

		return IWP_BACKUP_DIR;
	}
	/**
	 * This function creates the correct header when download files
	 * @param  string $fullpath   This is the full path to the encrypted file
	 * @param  string $encryption This is the key (salting) used to decrypt the file
	 * @return heder              This will download the fila when via the browser
	 */
	private function spool_crypted_file($fullpath, $encryption) {
		if ('' == $encryption) $encryption = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_encryptionphrase');
		if ('' == $encryption) {
			header('Content-type: text/plain');
			_e("Decryption failed. The database file is encrypted, but you have no encryption key entered.", 'InfiniteWP');
			$this->log('Decryption of database failed: the database file is encrypted, but you have no encryption key entered.', 'error');
		} else {


			//now decrypt the file and return array
			$decrypted_file = $this->decrypt($fullpath, $encryption, true);

			//check to ensure there is a response back
			if (is_array($decrypted_file)) {
				header('Content-type: application/x-gzip');
				header("Content-Disposition: attachment; filename=\"".$decrypted_file['basename']."\";");
				header("Content-Length: ".filesize($decrypted_file['fullpath']));
				readfile($decrypted_file['fullpath']);

				//need to remove the file as this is no longer needed on the local server
				unlink($decrypted_file['fullpath']);
			} else {
				header('Content-type: text/plain');
				echo __("Decryption failed. The most likely cause is that you used the wrong key.", 'InfiniteWP')." ".__('The decryption key used:', 'InfiniteWP').' '.$encryption;
				
			}
		}
	}

	public function get_mime_type_from_filename($filename, $allow_gzip = true) {
		if ('.zip' == substr($filename, -4, 4)) {
			return 'application/zip';
		} elseif ('.tar' == substr($filename, -4, 4)) {
			return 'application/x-tar';
		} elseif ('.tar.gz' == substr($filename, -7, 7)) {
			return 'application/x-tgz';
		} elseif ('.tar.bz2' == substr($filename, -8, 8)) {
			return 'application/x-bzip-compressed-tar';
		} elseif ($allow_gzip && '.gz' == substr($filename, -3, 3)) {
			// When we sent application/x-gzip as a content-type header to the browser, we found a case where the server compressed it a second time (since observed several times)
			return 'application/x-gzip';
		} else {
			return 'application/octet-stream';
		}
	}


	public function retain_range($input) {
		$input = (int)$input;
		return  ($input > 0) ? min($input, 9999) : 1;
	}

	public function just_one_email($input, $required = false) {
		$x = $this->just_one($input, 'saveemails', (empty($input) && false === $required) ? '' : get_bloginfo('admin_email'));
		if (is_array($x)) {
			foreach ($x as $ind => $val) {
				if (empty($val)) unset($x[$ind]);
			}
			if (empty($x)) $x = '';
		}
		return $x;
	}

	public function just_one($input, $filter = 'savestorage', $rinput = false) {
		$oinput = $input;
		if (false === $rinput) $rinput = (is_array($input)) ? array_pop($input) : $input;
		if (is_string($rinput) && false !== strpos($rinput, ',')) $rinput = substr($rinput, 0, strpos($rinput, ','));
		return apply_filters('IWP_'.$filter, $rinput, $oinput);
	}
	
	public function memory_check_current($memory_limit = false) {
		# Returns in megabytes
		if ($memory_limit == false) $memory_limit = ini_get('memory_limit');
		$memory_limit = rtrim($memory_limit);
		$memory_unit = $memory_limit[strlen($memory_limit)-1];
		if ((int)$memory_unit == 0 && $memory_unit !== '0') {
			$memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
		} else {
			$memory_unit = '';
		}
		switch($memory_unit) {
			case '':
				$memory_limit = floor($memory_limit/1048576);
			break;
			case 'K':
			case 'k':
				$memory_limit = floor($memory_limit/1024);
			break;
			case 'G':
				$memory_limit = $memory_limit*1024;
			break;
			case 'M':
				//assumed size, no change needed
			break;
		}
		return $memory_limit;
	}

	public function memory_check($memory, $check_using = false) {
		$memory_limit = $this->memory_check_current($check_using);
		return ($memory_limit >= $memory)?true:false;
	}

	public function analyse_db_file($timestamp, $res, $db_file = false, $header_only = false) {

		$mess = array(); $warn = array(); $err = array(); $info = array();

		$wp_version = $this->get_wordpress_version();
		global $wpdb;

		$iwp_backup_dir = $this->backups_dir_location();

		if (false === $db_file) {
			# This attempts to raise the maximum packet size. This can't be done within the session, only globally. Therefore, it has to be done before the session starts; in our case, during the pre-analysis.
			$this->get_max_packet_size();

			$backup = $this->get_backup_history($timestamp);
			if (!isset($backup['nonce']) || !isset($backup['db'])) return array($mess, $warn, $err, $info);

			$db_file = (is_string($backup['db'])) ? $iwp_backup_dir.'/'.$backup['db'] : $iwp_backup_dir.'/'.$backup['db'][0];
		}

		if (!is_readable($db_file)) return array($mess, $warn, $err, $info);

		// Encrypted - decrypt it
		if ($this->is_db_encrypted($db_file)) {

			$encryption = empty($res['IWP_encryptionphrase']) ? IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_encryptionphrase') : $res['IWP_encryptionphrase'];

			if (!$encryption) {
				if (class_exists('IWP_MMB_Addon_MoreDatabase')) {
					$err[] = sprintf(__('Error: %s', 'InfiniteWP'), __('Decryption failed. The database file is encrypted, but you have no encryption key entered.', 'InfiniteWP'));
				} else {
					$err[] = sprintf(__('Error: %s', 'InfiniteWP'), __('Decryption failed. The database file is encrypted.', 'InfiniteWP'));
				}
				return array($mess, $warn, $err, $info);
			}

			$decrypted_file = $this->decrypt($db_file, $encryption);

			if (is_array($decrypted_file)) {
				$db_file = $decrypted_file['fullpath'];
			} else {
				$err[] = __('Decryption failed. The most likely cause is that you used the wrong key.','InfiniteWP');
				return array($mess, $warn, $err, $info);
			}


		}

		# Even the empty schema when gzipped comes to 1565 bytes; a blank WP 3.6 install at 5158. But we go low, in case someone wants to share single tables.
		if (filesize($db_file) < 1000) {
			$err[] = sprintf(__('The database is too small to be a valid WordPress database (size: %s Kb).','InfiniteWP'), round(filesize($db_file)/1024, 1));
			return array($mess, $warn, $err, $info);
		}

		$is_plain = ('.gz' == substr($db_file, -3, 3)) ? false : true;

		$dbhandle = ($is_plain) ? fopen($db_file, 'r') : $this->gzopen_for_read($db_file, $warn, $err);
		if (!is_resource($dbhandle)) {
			$err[] =  __('Failed to open database file.', 'InfiniteWP');
			return array($mess, $warn, $err, $info);
		}

		$info['timestamp'] = $timestamp;

		# Analyse the file, print the results.

		$line = 0;
		$old_siteurl = '';
		$old_home = '';
		$old_table_prefix = '';
		$old_siteinfo = array();
		$gathering_siteinfo = true;
		$old_wp_version = '';
		$old_php_version = '';

		$tables_found = array();

		// TODO: If the backup is the right size/checksum, then we could restore the $line <= 100 in the 'while' condition and not bother scanning the whole thing? Or better: sort the core tables to be first so that this usually terminates early

		$wanted_tables = array('terms', 'term_taxonomy', 'term_relationships', 'commentmeta', 'comments', 'links', 'options', 'postmeta', 'posts', 'users', 'usermeta');

		$migration_warning = false;
		$processing_create = false;
		$db_version = $wpdb->db_version();

		// Don't set too high - we want a timely response returned to the browser
		// Until April 2015, this was always 90. But we've seen a few people with ~1GB databases (uncompressed), and 90s is not enough. Note that we don't bother checking here if it's compressed - having a too-large timeout when unexpected is harmless, as it won't be hit. On very large dbs, they're expecting it to take a while.
		// "120 or 240" is a first attempt at something more useful than just fixed at 90 - but should be sufficient (as 90 was for everyone without ~1GB databases)
		$default_dbscan_timeout = (filesize($db_file) < 31457280) ? 120 : 240;
		$dbscan_timeout = (defined('IWP_DBSCAN_TIMEOUT') && is_numeric(IWP_DBSCAN_TIMEOUT)) ? IWP_DBSCAN_TIMEOUT : $default_dbscan_timeout;
		@set_time_limit($dbscan_timeout);

		while ((($is_plain && !feof($dbhandle)) || (!$is_plain && !gzeof($dbhandle))) && ($line<100 || (!$header_only && count($wanted_tables)>0))) {
			$line++;
			// Up to 1MB
			$buffer = ($is_plain) ? rtrim(fgets($dbhandle, 1048576)) : rtrim(gzgets($dbhandle, 1048576));
			// Comments are what we are interested in
			if (substr($buffer, 0, 1) == '#') {
				$processing_create = false;
				if ('' == $old_siteurl && preg_match('/^\# Backup of: (http(.*))$/', $buffer, $matches)) {
					$old_siteurl = untrailingslashit($matches[1]);
					$mess[] = __('Backup of:', 'InfiniteWP').' '.htmlspecialchars($old_siteurl).((!empty($old_wp_version)) ? ' '.sprintf(__('(version: %s)', 'InfiniteWP'), $old_wp_version) : '');
					// Check for should-be migration
					if ($old_siteurl != untrailingslashit(site_url())) {
						if (!$migration_warning) {
							$migration_warning = true;
							$powarn = apply_filters('IWP_dbscan_urlchange', sprintf(__('Warning: %s', 'InfiniteWP'), 'URL not matching'), $old_siteurl, $res);
							if (!empty($powarn)) $warn[] = $powarn;
						}
						// Explicitly set it, allowing the consumer to detect when the result was unknown
						$info['same_url'] = false;
						
						if ($this->mod_rewrite_unavailable(false)) {
							$warn[] = sprintf(__('You are using the %s webserver, but do not seem to have the %s module loaded.', 'InfiniteWP'), 'Apache', 'mod_rewrite').' '.sprintf(__('You should enable %s to make any pretty permalinks (e.g. %s) work', 'InfiniteWP'), 'mod_rewrite', 'http://example.com/my-page/');
						}
						
					} else {
						$info['same_url'] = true;
					}
				} elseif ('' == $old_home && preg_match('/^\# Home URL: (http(.*))$/', $buffer, $matches)) {
					$old_home = untrailingslashit($matches[1]);
					// Check for should-be migration
					if (!$migration_warning && $old_home != home_url()) {
						$migration_warning = true;
						$powarn = apply_filters('IWP_dbscan_urlchange', sprintf(__('Warning: %s', 'InfiniteWP'), 'URL not matching'), $old_siteurl, $res);
						if (!empty($powarn)) $warn[] = $powarn;
					}
				} elseif (!isset($info['created_by_version']) && preg_match('/^\# Created by InfiniteWP version ([\d\.]+)/', $buffer, $matches)) {
					$info['created_by_version'] = trim($matches[1]);
				} elseif ('' == $old_wp_version && preg_match('/^\# WordPress Version: ([0-9]+(\.[0-9]+)+)(-[-a-z0-9]+,)?(.*)$/', $buffer, $matches)) {
					$old_wp_version = $matches[1];
					if (!empty($matches[3])) $old_wp_version .= substr($matches[3], 0, strlen($matches[3])-1);
					if (version_compare($old_wp_version, $wp_version, '>')) {
						$warn[] = sprintf(__('You are importing from a newer version of WordPress (%s) into an older one (%s). There are no guarantees that WordPress can handle this.', 'InfiniteWP'), $old_wp_version, $wp_version);
					}
					if (preg_match('/running on PHP ([0-9]+\.[0-9]+)(\s|\.)/', $matches[4], $nmatches) && preg_match('/^([0-9]+\.[0-9]+)(\s|\.)/', PHP_VERSION, $cmatches)) {
						$old_php_version = $nmatches[1];
						$current_php_version = $cmatches[1];
						if (version_compare($old_php_version, $current_php_version, '>')) {
							$warn[] = sprintf(__('The site in this backup was running on a webserver with version %s of %s. ', 'InfiniteWP'), $old_php_version, 'PHP').' '.sprintf(__('This is significantly newer than the server which you are now restoring onto (version %s).', 'InfiniteWP'), PHP_VERSION).' '.sprintf(__('You should only proceed if you cannot update the current server and are confident (or willing to risk) that your plugins/themes/etc. are compatible with the older %s version.', 'InfiniteWP'), 'PHP').' '.sprintf(__('Any support requests to do with %s should be raised with your web hosting company.', 'InfiniteWP'), 'PHP');
						}
					}
				} elseif ('' == $old_table_prefix && (preg_match('/^\# Table prefix: (\S+)$/', $buffer, $matches) || preg_match('/^-- Table prefix: (\S+)$/i', $buffer, $matches))) {
					$old_table_prefix = $matches[1];
				} elseif (empty($info['label']) && preg_match('/^\# Label: (.*)$/', $buffer, $matches)) {
					$info['label'] = $matches[1];
					$mess[] = __('Backup label:', 'InfiniteWP').' '.htmlspecialchars($info['label']);
				} elseif ($gathering_siteinfo && preg_match('/^\# Site info: (\S+)$/', $buffer, $matches)) {
					if ('end' == $matches[1]) {
						$gathering_siteinfo = false;
						// Sanity checks
						if (isset($old_siteinfo['multisite']) && !$old_siteinfo['multisite'] && is_multisite()) {
								$warn[] = __('You are running on WordPress multisite - but your backup is not of a multisite site.', 'InfiniteWP').' '.__('It will be imported as a new site.', 'InfiniteWP').' <a href="https://InfiniteWP.com/information-on-importing-a-single-site-wordpress-backup-into-a-wordpress-network-i-e-multisite/">'.__('Please read this link for important information on this process.', 'InfiniteWP').'</a>';
						
							if (!class_exists('IWP_MMBAddOn_MultiSite') || !class_exists('IWP_MMB_Addons_Migrator')) {
								 $err[] = sprintf(__('Error: %s', 'InfiniteWP'), sprintf(__('To import an ordinary WordPress site into a multisite installation requires %s.', 'InfiniteWP'), 'InfiniteWP Premium'));
								return array($mess, $warn, $err, $info);
							}
						} elseif (isset($old_siteinfo['multisite']) && $old_siteinfo['multisite'] && !is_multisite()) {
							$warn[] = __('Warning:', 'InfiniteWP').' '.__('Your backup is of a WordPress multisite install; but this site is not. Only the first site of the network will be accessible.', 'InfiniteWP').' <a href="https://codex.wordpress.org/Create_A_Network">'.__('If you want to restore a multisite backup, you should first set up your WordPress installation as a multisite.', 'InfiniteWP').'</a>';
						}
					} elseif (preg_match('/^([^=]+)=(.*)$/', $matches[1], $kvmatches)) {
						$key = $kvmatches[1];
						$val = $kvmatches[2];
						if ('multisite' == $key) {
							$info['multisite'] = $val ? true : false;
							if ($val) $mess[] = '<strong>'.__('Site information:', 'InfiniteWP').'</strong> '.'backup is of a WordPress Network';
						}
						$old_siteinfo[$key]=$val;
					}
				} elseif (preg_match('/^\# Skipped tables: (.*)$/', $buffer, $matches)) {
					$skipped_tables = explode(',', $matches[1]);
				}

			} elseif (preg_match('/^\s*create table \`?([^\`\(]*)\`?\s*\(/i', $buffer, $matches)) {
				$table = $matches[1];
				$tables_found[] = $table;
				if ($old_table_prefix) {
					// Remove prefix
					$table = $this->str_replace_once($old_table_prefix, '', $table);
					if (in_array($table, $wanted_tables)) {
						$wanted_tables = array_diff($wanted_tables, array($table));
					}
				}
				if (substr($buffer, -1, 1) != ';') $processing_create = true;
			} elseif ($processing_create) {
				if (substr($buffer, -1, 1) == ';') $processing_create = false;
				static $mysql_version_warned = false;
				if (!$mysql_version_warned && version_compare($db_version, '5.2.0', '<') && preg_match('/(CHARSET|COLLATE)[= ]utf8mb4/', $buffer)) {
					$mysql_version_warned = true;
					 $err[] = sprintf(__('Error: %s', 'InfiniteWP'), sprintf(__('The database backup uses MySQL features not available in the old MySQL version (%s) that this site is running on.', 'InfiniteWP'), $db_version).' '.__('You must upgrade MySQL to be able to use this database.', 'InfiniteWP'));
				}
			}
		}

		if ($is_plain) {
			@fclose($dbhandle);
		} else {
			@gzclose($dbhandle);
		}

/*        $blog_tables = "CREATE TABLE $wpdb->terms (
CREATE TABLE $wpdb->term_taxonomy (
CREATE TABLE $wpdb->term_relationships (
CREATE TABLE $wpdb->commentmeta (
CREATE TABLE $wpdb->comments (
CREATE TABLE $wpdb->links (
CREATE TABLE $wpdb->options (
CREATE TABLE $wpdb->postmeta (
CREATE TABLE $wpdb->posts (
        $users_single_table = "CREATE TABLE $wpdb->users (
        $users_multi_table = "CREATE TABLE $wpdb->users (
        $usermeta_table = "CREATE TABLE $wpdb->usermeta (
        $ms_global_tables = "CREATE TABLE $wpdb->blogs (
CREATE TABLE $wpdb->blog_versions (
CREATE TABLE $wpdb->registration_log (
CREATE TABLE $wpdb->site (
CREATE TABLE $wpdb->sitemeta (
CREATE TABLE $wpdb->signups (
*/
		if (!isset($skipped_tables)) $skipped_tables = array();
		$missing_tables = array();
		if ($old_table_prefix) {
			if (!$header_only) {
				foreach ($wanted_tables as $table) {
					if (!in_array($old_table_prefix.$table, $tables_found)) {
						$missing_tables[] = $table;
					}
				}

				foreach ($missing_tables as $key => $value) {
					if (in_array($old_table_prefix.$value, $skipped_tables)) {
						unset($missing_tables[$key]);
					}
				}

				if (count($missing_tables)>0) {
					$warn[] = sprintf(__('This database backup is missing core WordPress tables: %s', 'InfiniteWP'), implode(', ', $missing_tables));
				}
				if (count($skipped_tables)>0) {
					$warn[] = sprintf(__('This database backup has the following WordPress tables excluded: %s', 'InfiniteWP'), implode(', ', $skipped_tables));
				}
			}
		} else {
			if (empty($backup['meta_foreign'])) {
				$warn[] = __('InfiniteWP was unable to find the table prefix when scanning the database backup.', 'InfiniteWP');
			}
		}

		// //need to make sure that we reset the file back to .crypt before clean temp files
		// $db_file = $decrypted_file['fullpath'].'.crypt';
		// unlink($decrypted_file['fullpath']);
		
		return array($mess, $warn, $err, $info);

	}

	private function gzopen_for_read($file, &$warn, &$err) {
		if (!function_exists('gzopen') || !function_exists('gzread')) {
			$missing = '';
			if (!function_exists('gzopen')) $missing .= 'gzopen';
			if (!function_exists('gzread')) $missing .= ($missing) ? ', gzread' : 'gzread';
			$err[] = sprintf(__("Your web server's PHP installation has these functions disabled: %s.", 'InfiniteWP'), $missing).' '.sprintf(__('Your hosting company must enable these functions before %s can work.', 'InfiniteWP'), __('restoration', 'InfiniteWP'));
			return false;
		}
		if (false === ($dbhandle = gzopen($file, 'r'))) return false;

		if (!function_exists('gzseek')) return $dbhandle;

		if (false === ($bytes = gzread($dbhandle, 3))) return false;
		# Double-gzipped?
		if ('H4sI' != base64_encode($bytes)) {
			if (0 === gzseek($dbhandle, 0)) {
				return $dbhandle;
			} else {
				@gzclose($dbhandle);
				return gzopen($file, 'r');
			}
		}
		# Yes, it's double-gzipped

		$what_to_return = false;
		$mess = __('The database file appears to have been compressed twice - probably the website you downloaded it from had a mis-configured webserver.', 'InfiniteWP');
		$messkey = 'doublecompress';
		$err_msg = '';

		if (false === ($fnew = fopen($file.".tmp", 'w')) || !is_resource($fnew)) {

			@gzclose($dbhandle);
			$err_msg = __('The attempt to undo the double-compression failed.', 'InfiniteWP');

		} else {

			@fwrite($fnew, $bytes);
			$emptimes = 0;
			while (!gzeof($dbhandle)) {
				$bytes = @gzread($dbhandle, 262144);
				if (empty($bytes)) {
					$emptimes++;
					$this->log("Got empty gzread ($emptimes times)");
					if ($emptimes>2) break;
				} else {
					@fwrite($fnew, $bytes);
				}
			}

			gzclose($dbhandle);
			fclose($fnew);
			# On some systems (all Windows?) you can't rename a gz file whilst it's gzopened
			if (!rename($file.".tmp", $file)) {
				$err_msg = __('The attempt to undo the double-compression failed.', 'InfiniteWP');
			} else {
				$mess .= ' '.__('The attempt to undo the double-compression succeeded.', 'InfiniteWP');
				$messkey = 'doublecompressfixed';
				$what_to_return = gzopen($file, 'r');
			}

		}

		$warn[$messkey] = $mess;
		if (!empty($err_msg)) $err[] = $err_msg;
		return $what_to_return;
	}

	# TODO: Remove legacy storage setting keys from here
	// These are used in 4 places (Feb 2016 - of course, you should re-scan the code to check if relying on this): showing current settings on the debug modal, wiping all current settings, getting a settings bundle to restore when migrating, and for relevant keys in POST-ed data when saving settings over AJAX
	public function get_settings_keys() {
		return array('IWP_autobackup_default', 'IWP_dropbox', 'IWP_googledrive', 'IWP_tmp_googledrive_access_token', 'IWP_dismissedautobackup', 'dismissed_general_notices_until', 'dismissed_season_notices_until', 'IWP_dismissedexpiry', 'IWP_dismisseddashnotice', 'IWP_interval', 'IWP_interval_increments', 'IWP_interval_database', 'IWP_retain', 'IWP_retain_db', 'IWP_encryptionphrase', 'IWP_service', 'IWP_googledrive_clientid', 'IWP_googledrive_secret', 'IWP_googledrive_remotepath', 'IWP_ftp', 'IWP_server_address', 'IWP_dir', 'IWP_email', 'IWP_delete_local', 'IWP_debug_mode', 'IWP_include_plugins', 'IWP_include_themes', 'IWP_include_uploads', 'IWP_include_others', 'IWP_include_wpcore', 'IWP_include_wpcore_exclude', 'IWP_include_more', 'IWP_include_blogs', 'IWP_include_mu-plugins',
		'IWP_include_others_exclude', 'IWP_include_uploads_exclude', 'IWP_lastmessage', 'IWP_googledrive_token', 'IWP_dropboxtk_request_token', 'IWP_dropboxtk_access_token', 'IWP_adminlocking', 'IWP_IWPvault', 'IWP_remotesites', 'IWP_migrator_localkeys', 'IWP_central_localkeys', 'IWP_retain_extrarules', 'IWP_googlecloud', 'IWP_include_more_path', 'IWP_split_every', 'IWP_ssl_nossl', 'IWP_backupdb_nonwp', 'IWP_extradbs', 'IWP_combine_jobs_around',
		'IWP_last_backup', 'IWP_starttime_files', 'IWP_starttime_db', 'IWP_startday_db', 'IWP_startday_files', 'IWP_sftp', 'IWP_s3', 'IWP_s3generic', 'IWP_dreamhost', 'IWP_s3generic_login', 'IWP_s3generic_pass', 'IWP_s3generic_remote_path', 'IWP_s3generic_endpoint', 'IWP_webdav', 'IWP_openstack', 'IWP_onedrive', 'IWP_azure', 'IWP_cloudfiles', 'IWP_cloudfiles_user', 'IWP_cloudfiles_apikey', 'IWP_cloudfiles_path', 'IWP_cloudfiles_authurl', 'IWP_ssl_useservercerts', 'IWP_ssl_disableverify', 'IWP_s3_login', 'IWP_s3_pass', 'IWP_s3_remote_path', 'IWP_dreamobjects_login', 'IWP_dreamobjects_pass', 'IWP_dreamobjects_remote_path', 'IWP_dreamobjects', 'IWP_report_warningsonly', 'IWP_report_wholebackup', 'IWP_log_syslog', 'IWP_extradatabases');
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

	public function get_backup_stats()
    {
		global $wpdb;
	
		$stats = array();
		$new_backup_method = $this->get_backup_history();
		$task_res = array();
		if (!empty($new_backup_method)) {
			foreach ($new_backup_method as $time => $value) {
				$task_res[$value['label']][$time]=$value;
			}
		}
		$stats = $task_res;
		
		
		return $stats;
		
	}

	public function getRunningBackupStatus($params){
		$result = get_option('IWP_backup_status');
		$job_id = $params['params']['backup_id'];
		$job_data = $this->jobdata_getarray($job_id);
		if ($result == '1') {
			$cron_disable = false;
			$cron_params = array();
			if (( defined('DISABLE_WP_CRON') && DISABLE_WP_CRON )) {
				$cron_disable = true;
				$cron_params = $this->get_cron($job_id);
			}
			$cron_do_action = $this->is_cron_do_action_need($job_id);
			return array('success'=>array('status' => 'partiallyCompleted', 'params' => $params['params'], 'jobdata'=>$job_data, 'cron_disable' => $cron_disable, 'cron_params' =>$cron_params, 'wp_content_url' => content_url(),'cron_do_action' =>$cron_do_action));
		} elseif ($result == '0') {
			$cron = $this->get_cron($job_id);
			if ($cron == false) {
				$last_backup = $this->last_backup_staus();
				if (empty($last_backup) || empty($last_backup['error'])) {
					$IWP_last_backup = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_last_backup');
					return array('success'=>array('status' => 'completed', 'last_backup' => $IWP_last_backup, 'wp_content_url' => content_url(), 'backup_id' => $job_id));
				}
				$errorMsg = 'Backup Failed';
				if (!empty($last_backup['error'])) {
					$errorMsg = $last_backup['error'];
				}
				return array('error' => array('error_code' => 'backup_failed', 'error' => $errorMsg, 'jobdata' => $job_data, 'wp_content_url' => content_url(), 'backup_id' => $job_id));
			}
			if (!empty($cron)) {
				if (time()> $cron[0]) {
					wp_cron();
				}
			}
			$cron_params = array();
			if (( defined('DISABLE_WP_CRON') && DISABLE_WP_CRON )) {
				$cron_disable = true;
				$cron_params = $this->get_cron($job_id);
			}
			$cron_do_action = $this->is_cron_do_action_need($job_id);
			return array('success'=>array('status' => 'partiallyCompleted', 'params' => $params['params'], 'jobdata'=>$job_data, 'cron_data' => $cron, 'cron_disable' => $cron_disable, 'cron_params' =>$cron_params, 'wp_content_url' => content_url(),'cron_do_action' =>$cron_do_action) );
		}

	}

	public function ensure_phpseclib($classes = false, $class_paths = false) {

		$this->no_deprecation_warnings_on_php7();

		if ($classes) {
			$any_missing = false;
			if (is_string($classes)) $classes = array($classes);
			foreach ($classes as $cl) {
				if (!class_exists($cl)) $any_missing = true;
			}
			if (!$any_missing) return;
		}

		if ($class_paths) {
			$phpseclib_dir = $GLOBALS['iwp_mmb_plugin_dir'].'/lib/phpseclib/phpseclib/phpseclib';
			if (false === strpos(get_include_path(), $phpseclib_dir)) set_include_path(get_include_path().PATH_SEPARATOR.$phpseclib_dir);
			if (is_string($class_paths)) $class_paths = array($class_paths);
			foreach ($class_paths as $cp) {
				include_once($phpseclib_dir.'/'.$cp.'.php');
			}
		}
	}

	public function fetch_log($backup_nonce = '', $log_pointer = 0, $output_format = 'html') {
		global $iwp_backup_core;

		if (empty($backup_nonce)) {
			list($mod_time, $log_file, $nonce) = $iwp_backup_core->last_modified_log();
		} else {
			$nonce = $backup_nonce;
		}

		if (!preg_match('/^[0-9a-f]+$/', $nonce)) die('Security check');
		
		$log_content = '';
		$new_pointer = $log_pointer;
		
		if (!empty($nonce)) {
			$iwp_backup_dir = $iwp_backup_core->backups_dir_location();

			$potential_log_file = $iwp_backup_dir."/log.".$nonce.".txt";

			if (is_readable($potential_log_file)){
				
				$templog_array = array();
				$log_file = fopen($potential_log_file, "r");
				if ($log_pointer > 0) fseek($log_file, $log_pointer);
				
				while (($buffer = fgets($log_file, 4096)) !== false) {
					$templog_array[] = $buffer;
				}
				if (!feof($log_file)) {
					$templog_array[] = __('Error: unexpected file read fail', 'InfiniteWP');
				}
				
				$new_pointer = ftell($log_file);
				$log_content = implode("", $templog_array);

				
			} else {
				$log_content .= __('The log file could not be read.', 'InfiniteWP');
			}

		} else {
			$log_content .= __('The log file could not be read.', 'InfiniteWP');
		}
		
		if ('html' == $output_format) $log_content = htmlspecialchars($log_content);
		
		$ret_array = array(
			'log' => $log_content,
			'nonce' => $nonce,
			'pointer' => $new_pointer
		);
		
		return $ret_array;
	}

	public function get_cron($job_id = false) {

		$cron = get_option('cron');
		if (!is_array($cron)) $cron = array();
		if (false === $job_id) return $cron;

		foreach ($cron as $time => $job) {
			if (isset($job['IWP_backup_resume'])) {
				foreach ($job['IWP_backup_resume'] as $hook => $info) {
					if (isset($info['args'][1]) && $job_id == $info['args'][1]) {
						$jobdata = $this->jobdata_getarray($job_id);
						return (!is_array($jobdata)) ? false : array($time);
					}
				}
			}
		}
	}

	public function get_cron_data($job_id = false) {

		$cron = get_option('cron');
		if (!is_array($cron)) $cron = array();
		if (false === $job_id) return $cron;

		foreach ($cron as $time => $job) {
			if (isset($job['IWP_backup_resume'])) {
				foreach ($job['IWP_backup_resume'] as $hook => $info) {
					if (isset($info['args'][1]) && $job_id == $info['args'][1]) {
						$jobdata = $this->jobdata_getarray($job_id);
						return (!is_array($jobdata)) ? false : $info['args'];
					}
				}
			}
		}
	}

	public function last_backup_staus() {
		$last_backup = array();
		$IWP_last_backup = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_last_backup');

		if ($IWP_last_backup) {

			// Show errors + warnings
			if (is_array($IWP_last_backup['errors'])) {
				foreach ($IWP_last_backup['errors'] as $err) {
					$level = (is_array($err)) ? $err['level'] : 'error';
					$message = (is_array($err)) ? $err['message'] : $err;
					
					if ('warning' == $level) {
						$last_backup['warning'] = $message;
					} else {
						$last_backup['error'] = $message;
					}
					
				}
			}

		} 

		return $last_backup;

	}

	public function backupable_file_entities_final($arr, $full_info) {
		$path = ABSPATH;
		if (is_array($path)) {
			$path = array_map('untrailingslashit', $path);
			if (1 == count($path)) $path = array_shift($path);
		} else {
			$path = untrailingslashit($path);
		}
		if ($full_info) {
			$arr['more'] = array(
				'path' => $path,
				'description' => __('Any other file/directory on your server that you wish to back up', 'InfiniteWP'),
				'shortdescription' => __('More Files', 'InfiniteWP'),
				'restorable' => false
			);
		} else {
			$arr['more'] = $path;
		}
		return $arr;
	}

	public function set_backup_task_option($params){
		if (empty($params)) {
			return false;
		}
		$exclude_others = '';
		$exclude_uploads = '';
		$IWP_service = false;
		update_option('IWP_delete_local', 1);
		if (!empty($params['args']['exclude'])) {
			if (defined('IWP_DEFAULT_OTHERS_EXCLUDE')) {
				$exclude_others = IWP_DEFAULT_OTHERS_EXCLUDE.',';
			}
			$exclude_others.= $params['args']['exclude'];
			$exclude_uploads.= $params['args']['exclude'];
			update_option('IWP_include_others_exclude', $exclude_others);
			update_option('IWP_include_uploads_exclude', $exclude_uploads);
		}
		if (!empty($params['args']['include'])) {
			if (defined('IWP_DEFAULT_INCLUDES')) {
				$include = IWP_DEFAULT_INCLUDES.',';
			}
			$include.= implode(",", $params['args']['include']);
			update_option('IWP_default_includes', $include);
		}
		if (!empty($params['args']['exclude_extensions']) && !defined('IWP_EXCLUDE_EXTENSIONS')) {
			define('IWP_EXCLUDE_EXTENSIONS', $params['args']['exclude_extensions']);
		}
		if (!empty($params['args']['IWP_encryptionphrase'])) {
			IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_encryptionphrase', $params['args']['IWP_encryptionphrase']);
		}else{
			IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_encryptionphrase', false);
		}

		if (!empty($params['account_info'])) {
			if (!empty($params['account_info']['iwp_ftp'])) {
				$ftp_details = $params['account_info']['iwp_ftp'];
				$opts = array(
					'user' => $ftp_details['ftp_username'],
					'pass' => $ftp_details['ftp_password'],
					'host' => $ftp_details['ftp_hostname'],
					'path' => $ftp_details['ftp_remote_folder'],
					'port' => $ftp_details['ftp_port'],
					'ftp_site_folder' => $ftp_details['ftp_site_folder'],
					'passive' => $ftp_details['ftp_passive']?true:false,
					'key' => $ftp_details['ftp_key'],
				);
				if ($ftp_details['use_sftp']) {
					update_option('IWP_service', 'sftp');
					IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_sftp', $opts);
				}else{
					update_option('IWP_service', 'ftp');
					if(!empty($ftp_details['ftp_ssl'])){
						$opts['host'] = $opts['host'].':'.$opts['port'];
						unset($opts['port']);
						IWP_MMB_Backup_Options::delete_iwp_backup_option('IWP_ssl_nossl');
					}else{
						IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_ssl_nossl', 1);
					}
					IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_ftp', $opts);
				}
			}elseif (!empty($params['account_info']['iwp_amazon_s3'])) {
				update_option('IWP_service', 's3');
				$s3_details = $params['account_info']['iwp_amazon_s3'];
				if (!empty($s3_details['as3_directory'])) {
					$path = trim($s3_details['as3_bucket'],'/').'/'.trim($s3_details['as3_directory'],'/');
				}else{
					$path = $s3_details['as3_bucket'];
				}
				$opts = array(
					'endpoint' => '',
					'accesskey' => $s3_details['as3_access_key'],
					'secretkey' => $s3_details['as3_secure_key'],
					'path' => $path,
					'as3_site_folder' => $s3_details['as3_site_folder'],
					'server_side_encryption' => $s3_details['server_side_encryption']?true:false
				);
				IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_s3', $opts);

			}elseif (!empty($params['account_info']['iwp_dropbox'])) {
				update_option('IWP_service', 'dropbox');
				$dropbox_details = $params['account_info']['iwp_dropbox'];

				$opts = array(
					'appkey' => $dropbox_details['dropbox_app_key'],
					'secret' => $dropbox_details['dropbox_app_secure_key'],
					'tk_access_token' => $dropbox_details['dropbox_access_token'],
					'folder' => $dropbox_details['dropbox_destination'],
					'ownername' => '',
					'CSRF' => '',
					'dropbox_site_folder' => $dropbox_details['dropbox_site_folder']
				);
				IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_dropbox', $opts);
			}elseif (!empty($params['account_info']['iwp_gdrive'])) {
				update_option('IWP_service', 'googledrive');
				$google_details = $params['account_info']['iwp_gdrive'];
				$opts = array(
					'clientid' => $google_details['clientID'],
					'secret' => $google_details['clientSecretKey'],
					'token' => $google_details['token']['refresh_token'],
					'tmp_access_token' => $google_details['token']['access_token'],
					'gdrive_site_folder' => $google_details['gdrive_site_folder'],
					'ownername' => ''
				);
				IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_googledrive', $opts);
			}
		}else{
			delete_option('IWP_service');
		}

		if (!empty($params['args']['limit'])) {
			update_option('IWP_retain', $params['args']['limit']);
			update_option('IWP_retain_db', $params['args']['limit']);
		}

		if (!empty($params['args']['exclude_tables'])) {
			$exclude_tables = @implode(',', $params['args']['exclude_tables']);
			update_option('IWP_default_exclude_tables', $exclude_tables);
		}
	}

	public function get_remote_file($services, $file, $timestamp, $restore = false) {
			global $iwp_backup_core;
			
			$fullpath = $iwp_backup_core->backups_dir_location().'/'.$file;

			$storage_objects_and_ids = $iwp_backup_core->get_storage_objects_and_ids($services);

			$is_downloaded = false;

			$iwp_backup_core->register_wp_http_option_hooks();

			foreach ($services as $service) {

				if (empty($service) || 'none' == $service) continue;
			
				if ($restore) {
					$service_description = empty($iwp_backup_core->backup_methods[$service]) ? $service : $iwp_backup_core->backup_methods[$service];
					$iwp_backup_core->log(__("File is not locally present - needs retrieving from remote storage",'updraftplus')." ($service_description)", 'notice-restore');
				}

				$object = $storage_objects_and_ids[$service]['object'];

				if (!$object->supports_feature('multi_options')) { 
					error_log("UpdraftPlus_Admin::get_remote_file(): Multi options not supported by: ".$service); 
					continue; 
				}
				
				$instance_ids = $storage_objects_and_ids[$service]['instance_settings'];
				$backups_instance_ids = isset($backup_history[$timestamp]['service_instance_ids'][$service]) ? $backup_history[$timestamp]['service_instance_ids'][$service] : array(false);
				
				foreach ($backups_instance_ids as $instance_id) {

					if (isset($instance_ids[$instance_id])) {
						$options = $instance_ids[$instance_id];
					} else {
						// If we didn't find a instance id match, it could be a new UpdraftPlus upgrade or a wipe settings with the same details entered so try the default options saved.
						$options = $object->get_options();
					}

					$object->set_options($options, false, $instance_id);

					$download = $this->download_file($file, $object);

					if (is_readable($fullpath) && false !== $download) {
						if ($restore) {
							$iwp_backup_core->log(__("OK", 'InfiniteWP'), 'notice-restore');
						} else {
							clearstatcache();
							$iwp_backup_core->log('Remote fetch was successful (file size: '.round(filesize($fullpath)/1024, 1).' KB)');
						}
						break 2;
					} else {
						if ($restore) {
							$iwp_backup_core->log(__("Error", 'InfiniteWP'), 'notice-restore');
						} else {
							clearstatcache();
							if (0 === @filesize($fullpath)) @unlink($fullpath);
							$iwp_backup_core->log('Remote fetch failed');
						}
					}
				}
			}
			$iwp_backup_core->register_wp_http_option_hooks(false);
		}

	public function get_storage_objects_and_ids($services) {
		
			$storage_objects_and_ids = array();

			foreach ($services as $method) {

				if ('none' === $method || '' == $method) continue;
			
				$call_method = 'IWP_MMB_UploadModule_'.$method;
				
				if (!class_exists($call_method)) include_once $GLOBALS['iwp_mmb_plugin_dir'].'/backup/'.$method.'.php';
				
				if (class_exists($call_method)) {
				
					$remote_storage = new $call_method;
					
					if (!empty($method_objects[$method])) $storage_objects_and_ids[$method] = array();
					
					$storage_objects_and_ids[$method]['object'] = $remote_storage;
					
					if ($remote_storage->supports_feature('multi_options')) {
					
						$settings = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_'.$method);
						
						if (!is_array($settings)) $settings = array();
					
						if (!isset($settings['version'])) $settings = $this->update_remote_storage_options_format($method);
						
						if (is_wp_error($settings)) {
							error_log("InfiniteWP: failed to convert storage options format: $method");
							$settings = array('settings' => array());
						}

						if (empty($settings['settings'])) {
							// See: https://wordpress.org/support/topic/cannot-setup-connectionauthenticate-with-dropbox/
							error_log("InfiniteWP: Warning: settings for $method are empty. A dummy field is usually needed so that something is saved.");
							
							// Try to recover by getting a default set of options for display
							if (is_callable(array($remote_storage, 'get_default_options'))) {
								$uuid = 's-'.md5(rand().uniqid().microtime(true));
								$settings['settings'] = array($uuid => $remote_storage->get_default_options());
							}
							
						}

						if (!empty($settings['settings'])) {
							
							if (!isset($storage_objects_and_ids[$method]['instance_settings'])) $storage_objects_and_ids[$method]['instance_settings'] = array();
							
							foreach ($settings['settings'] as $instance_id => $storage_options) {
								$storage_objects_and_ids[$method]['instance_settings'][$instance_id] = $storage_options;
							}
						}
					}

				} else {
					error_log("InfiniteWP: no such storage class: $call_method");
				}
			}

			return $storage_objects_and_ids;
			
		}

	public function download_file($file,  $object) {

			global $iwp_backup_core;

			@set_time_limit(IWP_SET_TIME_LIMIT);

			$service = $object->get_id();
			
			$iwp_backup_core->log("Requested file from remote service: $service: $file");

			if (method_exists($object, 'download')) {
			
				try {
					return $object->download($file);
				} catch (Exception $e) {
					$log_message = 'Exception ('.get_class($e).') occurred during download: '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
					$iwp_backup_core->log($log_message);
					error_log($log_message);
					$iwp_backup_core->log(sprintf(__('A PHP exception (%s) has occurred: %s', 'InfiniteWP'), get_class($e), $e->getMessage()), 'error');
					return false;
				// @codingStandardsIgnoreLine
				} catch (Error $e) {
					$log_message = 'PHP Fatal error ('.get_class($e).') has occurred. Error Message: '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
					$iwp_backup_core->log($log_message);
					error_log($log_message);
					$iwp_backup_core->log(sprintf(__('A PHP fatal error (%s) has occurred: %s', 'InfiniteWP'), get_class($e), $e->getMessage()), 'error');
					return false;
				}
			} else {
				$iwp_backup_core->log("Automatic backup restoration is not available with the method: $service.");
				$iwp_backup_core->log("$file: ".sprintf(__("The backup archive for this file could not be found. The remote storage method in use (%s) does not allow us to retrieve files. To perform any restoration using InfiniteWP, you will need to obtain a copy of this file and place it inside UpdraftPlus's working folder", 'InfiniteWP'), $service)." (".$this->prune_iwp_dir_prefix($iwp_backup_core->backups_dir_location()).")", 'error');
				return false;
			}

		}
	public function prune_iwp_dir_prefix($iwp_backup_dir) {
			if ('/' == substr($iwp_backup_dir, 0, 1) || "\\" == substr($iwp_backup_dir, 0, 1) || preg_match('/^[a-zA-Z]:/', $iwp_backup_dir)) {
				$wcd = trailingslashit(WP_CONTENT_DIR);
				if (strpos($iwp_backup_dir, $wcd) === 0) {
					$iwp_backup_dir = substr($iwp_backup_dir, strlen($wcd));
				}
				# Legacy
	// 			if (strpos($iwp_backup_dir, ABSPATH) === 0) {
	// 				$iwp_backup_dir = substr($iwp_backup_dir, strlen(ABSPATH));
	// 			}
			}
			return $iwp_backup_dir;
		}

	public function do_iwp_download_backup($params = array()) {
	
		@set_time_limit(IWP_SET_TIME_LIMIT);
		global $iwp_backup_core;
		$timestamp = $params['resultID'];
		$taskName = $params['taskName'];
		$job_nonce = dechex($timestamp).substr(md5($taskName), 0, 5);
		// You need a nonce before you can set job data. And we certainly don't yet have one.
		$nounce = $this->backup_time_nonce($job_nonce);

		$debug_mode = true;

		// Set the job type before logging, as there can be different logging destinations
		$running_download = $iwp_backup_core->jobdata_get ('download');
		if (empty($running_download)) {
			$iwp_backup_core->jobdata_set('download', $params);
			$iwp_backup_core->jobdata_set('job_time_ms', $iwp_backup_core->job_time_ms);
		}
		if ($debug_mode) $iwp_backup_core->logfile_open($iwp_backup_core->nonce);

		$iwp_backup_dir = $iwp_backup_core->backups_dir_location();
		if (!empty($params['isNewBackup'])) {
			$types_to_downlaod = $params['types_to_downlaod'];
			$types_to_downlaod[] = 'backup_file_basename';
			// Retrieve the information from our backup history
			$backup_history = $this->get_backup_history();
			// Base name
			foreach ($types_to_downlaod as $key => $type ) {
				$files = $backup_history[$timestamp][$type];
				if (is_array($files)) {
					foreach ($files as $index => $file_name) {
						$itext = empty($index) ? '' : $index;
						$known_size = isset($backup_history[$timestamp][$type.$itext.'-size']) ? $backup_history[$timestamp][$type.$itext.'-size'] : 0;
						$file = $files[$index];
						$fullpath = $iwp_backup_dir.'/'.$file;
						if (!file_exists($fullpath)) {
							$findex = $index;
							break;
						} elseif ($known_size > 0 && filesize($fullpath) < $known_size) {
							$findex = $index;
							break;
						}else{
							$file = '';
						}
					}
				}else{
					$file = $files;
					$fullpath = $iwp_backup_dir.'/'.$file;
					$findex = '';
					$itext = empty($findex) ? '' : $findex;
					$known_size = isset($backup_history[$timestamp][$type.$itext.'-size']) ? $backup_history[$timestamp][$type.$itext.'-size'] : 0;
					if (!file_exists($fullpath)) {
							$findex = $index;
							break;
						} elseif ($known_size > 0 && filesize($fullpath) < $known_size) {
							$findex = $index;
							break;
						}else{
							$file = '';
						}

				}
				if (!empty($file)) {
					break;
				}
			}
			set_error_handler(array($iwp_backup_core, 'php_error'), E_ALL & ~E_STRICT);

			$iwp_backup_core->log("Requested to obtain file: timestamp=$timestamp, type=$type, index=$findex");

			$services = isset($backup_history[$timestamp]['service']) ? $backup_history[$timestamp]['service'] : false;
			if (!empty($backup_history[$timestamp]['service'][0]) && $backup_history[$timestamp]['service'][0] != 'none' && !empty($backup_history[$timestamp]['service_setting'])) {
				$service_setting =  $backup_history[$timestamp]['service_setting'];
				$service = 'IWP_'.$backup_history[$timestamp]['service'][0];
				IWP_MMB_Backup_Options::update_iwp_backup_option($service, $service_setting);
			}
			if (is_string($services)) $services = array($services);

			$iwp_backup_core->jobdata_set('service', $services);

		}else{
			$tasks = $this->get_requested_task($timestamp);
			$tasks['taskResults'] = unserialize($tasks['taskResults']);
			$backup = $tasks['taskResults']['task_results'][$timestamp];				//darkCode testing purpose
			$hashValues = $backup['hashValues'];
			//$backup = $tasks['taskResults'];
			$requestParams = unserialize($tasks['requestParams']);
			$args = $requestParams['account_info'];
			$this->set_cloud_upload_setting($requestParams);
			if (isset($backup['ftp'])) {
				if (!empty($args['iwp_ftp']['use_sftp'])) {
					$services = array('sftp');
					$files = $backup['ftp'];
				}else{
					$services = array('ftp');
					$files = $backup['ftp'];
					
				}
				$type = 'ftp';
			}elseif (isset($backup['amazons3'])) {
				$services = array('s3');
				$files = $backup['amazons3'];
				$type = 's3';
			}elseif (isset($backup['dropbox'])) {
				$services = array('dropbox');
				$files = $backup['dropbox'];
				$type = 'dropbox';
			}elseif (isset($backup['gDrive'])) {
				$services = array('googledrive');
				$files = $backup['gDriveOrgFileName'];
				$type = 'gDrive';

			}elseif (isset($backup['server'])) {
				$files = $backup['file_path'];
			}
			$cloudInstance = $this->createCloudInstance($type, $args);
			if (is_array($files)) {
				foreach ($files as $index => $file_name) {
					$itext = empty($index) ? '' : $index;
					$backup_size = $this->getCloudBackupSize($type, $cloudInstance, $files[$index], $args);
					// $known_size = isset($backup['size']) ? $this->toBytes($backup['size']) : 0;
					$known_size = isset($backup_size) ? $backup_size : 0;
					$file = $files[$index];
					$fullpath = $iwp_backup_dir.'/'.$file;
					if (!file_exists($fullpath)) {
						$findex = $index;
						break;
					} elseif ($known_size > 0 && filesize($fullpath) < $known_size) {
						$findex = $index;
						break;
					}else{
						$file = '';
					}
				}
			}else {
				$file = $files;
				$fullpath = $iwp_backup_dir.'/'.$file;
				$findex = '';
				$itext = empty($findex) ? '' : $findex;
				$backup_size = $this->getCloudBackupSize($type, $cloudInstance, $file, $args);
				// $known_size = isset($backup['size']) ? $this->toBytes($backup['size']) : 0;
				$known_size = isset($backup_size) ? $backup_size : 0;
				if (!file_exists($fullpath)) {
						$findex = $index;
					} elseif ($known_size > 0 && filesize($fullpath) < $known_size) {
						$findex = $index;
					}else{
						$file = '';
					}

			}
			set_error_handler(array($iwp_backup_core, 'php_error'), E_ALL & ~E_STRICT);
			$iwp_backup_core->log("Requested to obtain file: Old History ID=$timestamp, type=full, index=all");
			$iwp_backup_core->jobdata_set('service', $services);

		}
		// TODO: FIXME: Failed downloads may leave log files forever (though they are small)


		// Fetch it from the cloud, if we have not already got it

		$needs_downloading = false;
		if (!file_exists($fullpath)) {
			//if the file doesn't exist and they're using one of the cloud options, fetch it down from the cloud.
			$needs_downloading = true;
			$iwp_backup_core->log('File does not yet exist locally - needs downloading');
		} elseif ($known_size > 0 && filesize($fullpath)+10 < $known_size) {
			$iwp_backup_core->log("The file was found locally (".filesize($fullpath).") but did not match the size in the backup history ($known_size) - will resume downloading");
			$needs_downloading = true;
		} else{
			return array('success' => 'completed', 'already_closed' => $needs_downloading, 'backup_dir' => $iwp_backup_dir);
		}

		// The AJAX responder that updates on progress wants to see this
		$iwp_backup_core->jobdata_set('dlfile_'.$timestamp.'_'.$type.'_'.$findex, "downloading:$known_size:$fullpath");

		if ($needs_downloading) {

			// Update the "last modified" time to dissuade any other instances from thinking that no downloaders are active
			@touch($fullpath);

			$msg = array(
				'result' => 'needs_download',
				'request' => array(
						'type' => $type,
						'timestamp' => $timestamp,
						'findex' => $findex
				)
			);
		
			$this->get_remote_file($services, $file, $timestamp);
		}

		// Now, be ready to spool the thing to the browser
		if (is_file($fullpath) && is_readable($fullpath)) {

			// That message is then picked up by the AJAX listener
			$iwp_backup_core->jobdata_set('dlfile_'.$timestamp.'_'.$type.'_'.$findex, 'downloaded:'.filesize($fullpath).":$fullpath");

			$result = 'downloaded';
			
		} else {

			$iwp_backup_core->jobdata_set('dlfile_'.$timestamp.'_'.$type.'_'.$findex, 'failed');
			$iwp_backup_core->jobdata_set('dlerrors_'.$timestamp.'_'.$type.'_'.$findex, $iwp_backup_core->errors);
			$iwp_backup_core->log('Remote fetch failed. File '.$fullpath.' did not exist or was unreadable. If you delete local backups then remote retrieval may have failed.');
			
			$result = 'download_failed';
		}

		restore_error_handler();

		@fclose($iwp_backup_core->logfile_handle);
		if (!$debug_mode) @unlink($iwp_backup_core->logfile_name);

		// The browser connection was possibly already closed, but not necessarily
		return array('success' => $result, 'already_closed' => $needs_downloading);

	}

	public function createCloudInstance($type, $args){
		if ($type == 'dropbox') {
			extract($args['iwp_dropbox']);
			require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/API.php';
			require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/Exception.php';
			require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/OAuth/Consumer/ConsumerAbstract.php';
			require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/OAuth/Consumer/Curl.php';
			
			$oauth = new IWP_Dropbox_OAuth_Consumer_Curl($dropbox_app_key, $dropbox_app_secure_key);
			$oauth->setToken($dropbox_access_token);
			$dropbox = new IWP_Dropbox_API($oauth);
			return $dropbox;
		}elseif ($type == 'ftp') {
			extract($args['iwp_ftp']);
			if(isset($use_sftp) && $use_sftp==1) {
			    $port = $ftp_port ? $ftp_port : 22; //default port is 22
			    /*
			     * SFTP section start here phpseclib library is used for this functionality
			     */
			    $path = $GLOBALS['iwp_mmb_plugin_dir'].'/lib/phpseclib/phpseclib/phpseclib';
			    set_include_path(get_include_path() . PATH_SEPARATOR . $path);
			    include_once('Net/SFTP.php');
			    
			    
			    $sftp = new Net_SFTP($ftp_hostname, $port);
			    if(!$sftp) {
			        return false;
			    }
			    if (!$sftp->login($ftp_username, $ftp_password)) {
			        return false;
			    } else {
			        return $sftp;
			    }
			    
			}
			$port = $ftp_port ? $ftp_port : 21; //default port is 21
	        if (!empty($ftp_ssl)) {
	            if (function_exists('ftp_ssl_connect')) {
	                $conn_id = ftp_ssl_connect($ftp_hostname,$port);
	                if ($conn_id === false) {
	                	return false;
	                }
	            } else {
	                return false;
	            }
	        }
			else {
	            if (function_exists('ftp_connect')) {
	                $conn_id = ftp_connect($ftp_hostname,$port);
	                if ($conn_id === false) {
	                    return false;
	                }
	            } else {
	                return false;
	            }
	        }
			
	        $login = @ftp_login($conn_id, $ftp_username, $ftp_password);
	        if ($login === false) {
	            return false;
	        }
	        
	        if(!empty($ftp_passive)){
				@ftp_pasv($conn_id,true);
			}
			return $conn_id;
		}elseif ($type == 'gDrive') {
			require_once $GLOBALS['iwp_mmb_plugin_dir'].'/backup/googledrive.php';
			$obj = new IWP_MMB_UploadModule_googledrive();
			return $obj;
		}
	}

	public function getCloudBackupSize($type, &$obj, $backup_file, $args){
		require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup.class.multicall.php");
		$backup_instance = new IWP_MMB_Backup_Multicall();
		if ($type == 'dropbox') {
			extract($args['iwp_dropbox']);
			$oldRoot = 'Apps/InfiniteWP/';
			$dropbox_destination = $oldRoot.ltrim(trim($dropbox_destination), '/');
				$dropbox_destination = rtrim($dropbox_destination, '/');
			if (isset($dropbox_site_folder) && $dropbox_site_folder == true){
				$dropbox_destination .=  '/'.$backup_instance->site_name;
			}
			$folders = explode('/',$dropbox_destination);
			foreach ($folders as $key => $name) {
			    $path.=trim($name).'/';
			}
			$destFile = trim($path, '/').'/';
			$filename = basename($backup_file);
			$destFile .= $filename;
			$dBoxMetaData = $obj -> metaData($destFile);
			if (empty($dBoxMetaData['body']->size)) {
				return false;
			}else{
				return $dBoxMetaData['body']->size;
			}
			
		}elseif ($type == 's3') {
			extract($args['iwp_amazon_s3']);
			if (isset($as3_site_folder) && $as3_site_folder == true){
				$destination .=  '/'.$backup_instance->site_name;
			}
			$folders = explode('/',$destination);
			foreach ($folders as $key => $name) {
			    $path.=trim($name).'/';
			}
			$destFile = trim($path, '/').'/';
			$filename = basename($backup_file);
			$destFile .= $filename;
			if(1 || is_new_s3_compatible()){
				require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/s3IWPBackup.php';
				if(!class_exists('S3Client')){
					require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/autoload.php');
				}
				$new_s3_obj = new IWP_MMB_S3_MULTICALL();
				return $new_s3_obj->postUploadS3Verification($backup_file, $destFile, $type, $as3_bucket, $as3_access_key, $as3_secure_key, $as3_bucket_region, $size1, $size2, $return_size = true);
			}
			else{
				return $backup_instance->postUploadS3VerificationBwdComp($backup_file, $destFile, $type, $as3_bucket, $as3_access_key, $as3_secure_key, $as3_bucket_region, $obj, $actual_file_size, $size1, $size2, $return_size = true);
			}
		}elseif ($type == 'ftp') {
			extract($args['iwp_ftp']);
			
			$destination = trim($ftp_remote_folder, '/');
			if (isset($ftp_site_folder) && $ftp_site_folder == true){
				$destination .=  '/'.$backup_instance->site_name;
			}
			$folders = explode('/',$destination);
			foreach ($folders as $key => $name) {
			    $path.=trim($name).'/';
			}
			$destFile = trim($path, '/').'/';
			$filename = basename($backup_file);
			$destFile .= $filename;
			if(isset($use_sftp) && $use_sftp==1) {
				$destFile = '/'.$destFile;
				$ftp_file_size = $obj->size($destFile);
			}else{
				ftp_chdir ($obj , dirname($destFile));
				$ftp_file_size = ftp_size($obj, basename($destFile));
			}
			if($ftp_file_size > 0)
			{
				return $ftp_file_size;
			}
			else
			{
				return false;
			}
		}elseif ($type == 'gDrive') {
			$return = $obj->get_backup_file_size($backup_file);
			if ($return >0 ) {
				return $return;
			}
		}
	}

	public function get_requested_task($ID){
		global $wpdb;
		$table_name = $wpdb->base_prefix . "iwp_backup_status";
				
		$rows = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$table_name." WHERE historyID = %d ORDER BY ID DESC LIMIT 1", $ID), ARRAY_A);
						
		return $rows;
		
	}

	public function toBytes($str){
        $val = str_replace(array(' MB', ' KB'),'', $str);
        $last = strtolower($str[strlen($str)-2]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
	}

	public function createBackupMetaFile($our_files){
		$site_name = str_replace(array(
            "_",
            "/",
	    			"~"
        ), array(
            "",
            "-",
            "-"
        ), rtrim(remove_http(get_bloginfo('url')), "/"));
		$backup_file_basename = $site_name.'_backup_'.get_date_from_gmt(gmdate('Y-m-d H:i:s', $this->backup_time), 'Y-m-d-Hi').'_'.$this->blog_name.'_'.$this->nonce.'_backup_meta_'.$this->get_wordpress_version().'.tmp';
		$our_files['wp_content_url'] = content_url();
		$our_files['wp_content_path'] = WP_CONTENT_DIR;
		$our_files['backup_meta_file'] = $backup_file_basename;
		$our_files['old_file_path'] = ABSPATH;
		$our_files['old_url'] = get_option('siteurl');
		$our_files['IWP_encryptionphrase'] = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_encryptionphrase');
		$backup_dir = $this->backups_dir_location();
		$backup_meta_file = $backup_dir.'/'.$backup_file_basename;
		$meta_file_handle = fopen($backup_meta_file, 'w');
		if ($meta_file_handle == false) {
			return false;
		}
		@fwrite($meta_file_handle, "<?php"."\n".'$backup_meta_files ='."'".serialize($our_files)."';\n");
		fclose($meta_file_handle);
		return $backup_file_basename;
	}

	public function delete_backup($opts) {
		
		$backups = $this->get_backup_history();
		$timestamps = (string)$opts['result_id'];

		$remote_delete_limit = (isset($opts['remote_delete_limit']) && $opts['remote_delete_limit'] > 0) ? (int)$opts['remote_delete_limit'] : PHP_INT_MAX;
		
		$timestamps = explode(',', $timestamps);
		$delete_remote = empty($opts['delete_remote']) ? true : true;

		// You need a nonce before you can set job data. And we certainly don't yet have one.
		// $this->backup_time_nonce();
		// // Set the job type before logging, as there can be different logging destinations
		// $this->jobdata_set('job_type', 'delete');
		// $this->jobdata_set('job_time_ms', $this->job_time_ms);

		if (IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_debug_mode')) {
			$this->logfile_open($this->nonce);
			set_error_handler(array($this, 'php_error'), E_ALL & ~E_STRICT);
		}

		$iwp_backup_dir = $this->backups_dir_location();
		$backupable_entities = $this->get_backupable_file_entities(true, true);

		$local_deleted = 0;
		$remote_deleted = 0;
		$sets_removed = 0;
		foreach ($timestamps as $i => $timestamp) {

			if (!isset($backups[$timestamp])) {
				return array('result' => 'error', 'message' => __('Backup set not found', 'InfiniteWP'));
			}

			$nonce = isset($backups[$timestamp]['nonce']) ? $backups[$timestamp]['nonce'] : '';

			$delete_from_service = array();

			if ($delete_remote) {
				// Locate backup set
				if (isset($backups[$timestamp]['service'])) {
					// Convert to an array so that there is no uncertainty about how to process it
					$services = is_string($backups[$timestamp]['service']) ? array($backups[$timestamp]['service']) : $backups[$timestamp]['service'];
					if (is_array($services)) {
						foreach ($services as $service) {
							if ($service && $service != 'none' && $service != 'email') $delete_from_service[] = $service;
						}
					}
				}
			}

			$files_to_delete = array();
			foreach ($backupable_entities as $key => $ent) {
				if (isset($backups[$timestamp][$key])) {
					$files_to_delete[$key] = $backups[$timestamp][$key];
				}
			}
			// Delete DB
			foreach ($backups[$timestamp] as $key => $value){
				if ('db' == strtolower(substr($key, 0, 2)) && '-size' != substr($key, -5, 5)) {
					$files_to_delete[$key] = $backups[$timestamp][$key];
				}
			}

			// Also delete the log
			if ($nonce && !IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_debug_mode')) {
				$files_to_delete['log'] = "log.$nonce.txt";
			}
			if (!empty($backups[$timestamp]['backup_file_basename'])) {
				$files_to_delete['backup_file_basename'] = $backups[$timestamp]['backup_file_basename'];
			}
			$this->register_wp_http_option_hooks();

			foreach ($files_to_delete as $key => $files) {

				if (is_string($files)) {
					$was_string = true;
					$files = array($files);
				} else {
					$was_string = false;
				}

				foreach ($files as $file) {
					if (is_file($iwp_backup_dir.'/'.$file) && @unlink($iwp_backup_dir.'/'.$file)) $local_deleted++;
				}

				if ('log' != $key && count($delete_from_service) > 0) {

					$storage_objects_and_ids = $this->get_storage_objects_and_ids($delete_from_service);

					foreach ($delete_from_service as $service) {
					
						if ('email' == $service || 'none' == $service || !$service) continue;

						$deleted = -1;

						$remote_obj = $storage_objects_and_ids[$service]['object'];

						$instance_settings = $storage_objects_and_ids[$service]['instance_settings'];
						$this->backups_instance_ids = empty($backups[$timestamp]['service_instance_ids'][$service]) ? array() : $backups[$timestamp]['service_instance_ids'][$service];

						uksort($instance_settings, array($this, 'instance_ids_sort'));

						foreach ($instance_settings as $instance_id => $options) {

							$remote_obj->set_options($options, false, $instance_id);

							foreach ($files as $index => $file) {
								if ($remote_deleted == $remote_delete_limit) {
									return $this->remove_backup_set_cleanup(false, $backups, $local_deleted, $remote_deleted, $sets_removed);
								}

								$deleted = $remote_obj->delete($file);
								
								if (-1 === $deleted) {
									//echo __('Did not know how to delete from this cloud service.', 'updraftplus');
								} elseif (false !== $deleted) {
									$remote_deleted++;
								}
								
								$itext = $index ? (string)$index : '';
								if ($was_string) {
									unset($backups[$timestamp][$key]);
									if ('db' == strtolower(substr($key, 0, 2))) unset($backups[$timestamp][$key][$index.'-size']);
								} else {
									unset($backups[$timestamp][$key][$index]);
									unset($backups[$timestamp][$key.$itext.'-size']);
									if (empty($backups[$timestamp][$key])) unset($backups[$timestamp][$key]);
								}
								if (isset($backups[$timestamp]['checksums']) && is_array($backups[$timestamp]['checksums'])) {
									foreach (array_keys($backups[$timestamp]['checksums']) as $algo) {
										unset($backups[$timestamp]['checksums'][$algo][$key.$index]);
									}
								}
								
								// If we don't save the array back, then the above section will fire again for the same files - and the remote storage will be requested to delete already-deleted files, which then means no time is actually saved by the browser-backend loop method.
								$this->save_history($backups);
							}
						}
					}
				}
			}

			unset($backups[$timestamp]);
			$this->save_history($backups);
			$sets_removed++;
		}

		return $this->remove_backup_set_cleanup(true, $backups, $local_deleted, $remote_deleted, $sets_removed);

	}

	public function remove_backup_set_cleanup($delete_complete, $backups, $local_deleted, $remote_deleted, $sets_removed) {

		$this->register_wp_http_option_hooks(false);

		$this->save_history($backups);

		$this->log("Local files deleted: $local_deleted. Remote files deleted: $remote_deleted");

		if ($delete_complete) {
			$set_message = __('Backup sets removed:', 'InfiniteWP');
			$local_message = __('Local files deleted:', 'InfiniteWP');
			$remote_message = __('Remote files deleted:', 'InfiniteWP');

			if (IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_debug_mode')) {
				restore_error_handler();
			}
			
			return array('result' => 'success', 'set_message' => $set_message, 'local_message' => $local_message, 'remote_message' => $remote_message, 'backup_sets' => $sets_removed, 'backup_local' => $local_deleted, 'backup_remote' => $remote_deleted);
		} else {
		
			return array('result' => 'continue', 'backup_local' => $local_deleted, 'backup_remote' => $remote_deleted, 'backup_sets' => $sets_removed);
		}
	}

	public function save_history($backup_history, $use_cache = true) {
		IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_backup_history', $backup_history, $use_cache);
	}

	public function instance_ids_sort($a, $b) {
		if (in_array($a, $this->backups_instance_ids)) {
			if (in_array($b, $this->backups_instance_ids)) return 0;
			return -1;
		}
		return in_array($b, $this->backups_instance_ids) ? 1 : 0;
	}

	public function activejobs_delete($job_id) {
			
		if (preg_match("/^[0-9a-f]{12}$/", $job_id)) {
		
			global $iwp_backup_core;
			$cron = get_option('cron');
			$found_it = false;
			$iwp_backup_dir = $iwp_backup_core->backups_dir_location();
			if (file_exists($iwp_backup_dir.'/log.'.$job_id.'.txt')) touch($iwp_backup_dir.'/deleteflag-'.$job_id.'.txt');
			foreach ($cron as $time => $job) {
				if (isset($job['IWP_backup_resume'])) {
					foreach ($job['IWP_backup_resume'] as $hook => $info) {
						if (isset($info['args'][1]) && $info['args'][1] == $job_id) {
							$args = $cron[$time]['IWP_backup_resume'][$hook]['args'];
							wp_unschedule_event($time, 'IWP_backup_resume', $args);
							if (!$found_it) return array('ok' => 'Y', 'c' => 'deleted', 'm' => __('Job deleted', 'InfiniteWP'));
							$found_it = true;
						}
					}
				}
			}
		}

		if (!$found_it) return true;

	}

	public function kill_new_backup($params){
		$this->activejobs_delete($params['result_id']);
		$backups = $this->get_backup_history();
		$this->delete_backup_by_id($params['result_id']);
		delete_option('IWP_jobdata_'.$params['result_id']);
		delete_option('IWP_backup_status', '0');
		delete_option('IWP_semaphore_fd');
		delete_option('IWP_locked_fd');
		delete_option('IWP_unlocked_fd');
		delete_option('IWP_semaphore_d');
		delete_option('IWP_unlocked_d');
		delete_option('IWP_locked_d');
		wp_clear_scheduled_hook('IWP_backup_resume');
		/*if (!empty($backups)) {
			foreach ($backups as $key => $value) {
				if ($value['nonce'] == $params['result_id']) {
					$params['result_id'] = $key;
				}
			}*/
			return $this->delete_backup($params);
		//}

		return true;
	}
	public function dropbox_modpath($file, $obj){
		$opts = $obj->get_options();
		$dropbox_site_folder = $opts['dropbox_site_folder'];
		$dropbox_destination = $opts['folder'];
		$path= '';
		if (isset($dropbox_site_folder) && $dropbox_site_folder == true){
			$site_name = iwp_getSiteName();
			$dropbox_destination .= '/' . $site_name . '/';
		}
		else{
			$dropbox_destination .= '/';
		}
		$oldRoot = 'Apps/InfiniteWP/';
		$dropbox_destination = $oldRoot.ltrim(trim($dropbox_destination), '/');
			$dropbox_destination = rtrim($dropbox_destination, '/');
		$folders = explode('/',$dropbox_destination);
		foreach ($folders as $key => $name) {
		    $path.=trim($name).'/';
		}
		$dropbox_destination = $path;
		$dropbox_folder = untrailingslashit($dropbox_destination);
		if (strpos($file, $dropbox_folder) === false) {
			$dropbox_folder.= '/'.$file;
		}else{
			$dropbox_folder = $file;
		}
		return $dropbox_folder;
	}

	public function get_timestamp_by_label($label){
		$new_backup_keys = array();
		$new_backups = $this->get_backup_history();
		if (!empty($new_backups)) {
			foreach ($new_backups as $timestamp => $value) {
				if ($label == $value['label']) {
					$new_backup_keys[$timestamp] = $value;
				}
			}
			ksort($new_backup_keys);
		}

		return $new_backup_keys;
	}

	public function set_cloud_upload_setting($params){
		if (!empty($params['account_info'])) {
			if (!empty($params['account_info']['iwp_ftp'])) {
				$ftp_details = $params['account_info']['iwp_ftp'];
				$opts = array(
					'user' => $ftp_details['ftp_username'],
					'pass' => $ftp_details['ftp_password'],
					'host' => $ftp_details['ftp_hostname'],
					'path' => $ftp_details['ftp_remote_folder'],
					'port' => $ftp_details['ftp_port'],
					'ftp_site_folder' => $ftp_details['ftp_site_folder'],
					'passive' => $ftp_details['ftp_passive']?true:false
				);
				if ($ftp_details['use_sftp']) {
					update_option('IWP_service', 'sftp');
					IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_sftp', $opts);
				}else{
					update_option('IWP_service', 'ftp');
					if(!empty($ftp_details['ftp_ssl'])){
						$opts['host'] = $opts['host'].':'.$opts['port'];
						unset($opts['port']);
						IWP_MMB_Backup_Options::delete_iwp_backup_option('IWP_ssl_nossl');
					}else{
						IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_ssl_nossl', 1);
					}
					IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_ftp', $opts);
				}
			}elseif (!empty($params['account_info']['iwp_amazon_s3'])) {
				$s3_details = $params['account_info']['iwp_amazon_s3'];
				if (!empty($s3_details['as3_directory'])) {
					$path = trim($s3_details['as3_bucket'],'/').'/'.trim($s3_details['as3_directory'],'/');
				}else{
					$path = $s3_details['as3_bucket'];
				}
				$opts = array(
					'endpoint' => '',
					'accesskey' => $s3_details['as3_access_key'],
					'secretkey' => $s3_details['as3_secure_key'],
					'path' => $path,
					'as3_site_folder' => $s3_details['as3_site_folder'],
					'server_side_encryption' => $s3_details['server_side_encryption']?true:false
				);
				IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_s3', $opts);

			}elseif (!empty($params['account_info']['iwp_dropbox'])) {
				$dropbox_details = $params['account_info']['iwp_dropbox'];

				$opts = array(
					'appkey' => $dropbox_details['dropbox_app_key'],
					'secret' => $dropbox_details['dropbox_app_secure_key'],
					'tk_access_token' => $dropbox_details['dropbox_access_token'],
					'folder' => $dropbox_details['dropbox_destination'],
					'ownername' => '',
					'CSRF' => '',
					'dropbox_site_folder' => $dropbox_details['dropbox_site_folder']
				);
				IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_dropbox', $opts);
			}elseif (!empty($params['account_info']['iwp_gdrive'])) {
				$google_details = $params['account_info']['iwp_gdrive'];
				$opts = array(
					'clientid' => $google_details['clientID'],
					'secret' => $google_details['clientSecretKey'],
					'token' => $google_details['token']['refresh_token'],
					'tmp_access_token' => $google_details['token']['access_token'],
					'gdrive_site_folder' => $google_details['gdrive_site_folder'],
					'ownername' => ''
				);
				IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_googledrive', $opts);
			}
		}
	}

	function delete_backup_by_id($backup_id){
		$iwp_backup_dir = $this->backups_dir_location();

		if (!$handle = opendir($iwp_backup_dir)) return;

		// See if there are any more files in the local directory than the ones already known about
		while (false !== ($entry = readdir($handle))) {
			if (strrpos($entry, $backup_id) /*&& strrpos($entry, 'log.') === false*/ && strrpos($entry, 'deleteflag-') === false) {
				@unlink($iwp_backup_dir.'/'.$entry);
			}
		}
	}

	public function is_cron_do_action_need($job_id){
		$time = time();
		$cron_time = $this->get_cron($job_id);
		if (empty($cron_time[0]) || $time < $cron_time[0]) {
			return false;
		}

		return true;
	}

	public function iwp_pheonix_backup_cron_do_action($params){
		$job_id = $params['params']['backup_id'];

		$is_cron_do_action_need = $this->is_cron_do_action_need($job_id);
		if ($is_cron_do_action_need == false) {
			return false;
		}
		$cron_data = $this->get_cron_data($job_id);
		if (!empty($cron_data)) {
			wp_clear_scheduled_hook('IWP_backup_resume', $cron_data);
			do_action( 'IWP_backup_resume', $cron_data[0], $cron_data[1] );
		}

	}
}
