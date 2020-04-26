<?php

if ( ! defined('ABSPATH') )
	die();

if (!class_exists('IWP_MMB_UploadModule')) require_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/backup.upload.php');

class IWP_MMB_UploadModule_ftp extends IWP_MMB_UploadModule {

	// Get FTP object with parameters set
	private function getFTP($server, $user, $pass, $disable_ssl = false, $disable_verify = true, $use_server_certs = false, $passive = true) {

		if ('' == trim($server) || '' == trim($user) || '' == trim($pass)) return new WP_Error('no_settings', sprintf(__('No %s settings were found','InfiniteWP'), 'FTP'));

		if( !class_exists('IWP_MMB_ftp_wrapper')) require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/ftp.class.php');

		$port = 21;
		if (preg_match('/^(.*):(\d+)$/', $server, $matches)) {
			$server = $matches[1];
			$port = $matches[2];
		}

		$ftp = new IWP_MMB_ftp_wrapper($server, $user, $pass, $port);

		if ($disable_ssl) $ftp->ssl = false;
		$ftp->use_server_certs = $use_server_certs;
		$ftp->disable_verify = $disable_verify;
		$ftp->passive = ($passive) ? true : false;

		return $ftp;

	}
	
	public function get_supported_features() {
		// This options format is handled via only accessing options via $this->get_options()
		return array('multi_options');
	}

	public function get_default_options() {
		return array(
			'host' => '',
			'user' => '',
			'pass' => '',
			'path' => '',
			'passive' => true
		);
	}
	
	public function backup($backup_array) {

		global $iwp_backup_core;

		$opts = $this->get_options();

		$ftp = $this->getFTP(
			$opts['host'],
			$opts['user'],
			$opts['pass'],
			$iwp_backup_core->get_job_option('IWP_ssl_nossl'),
			$iwp_backup_core->get_job_option('IWP_ssl_disableverify'),
			$iwp_backup_core->get_job_option('IWP_ssl_useservercerts'),
			$opts['passive']
		);

		if (is_wp_error($ftp) || !$ftp->connect()) {
			if (is_wp_error($ftp)) {
				$iwp_backup_core->log_wp_error($ftp);
			} else {
				$iwp_backup_core->log("FTP Failure: we did not successfully log in with those credentials.");
			}
			$iwp_backup_core->log(sprintf(__("%s login failure",'InfiniteWP'), 'FTP'), 'error');
			return false;
		}

		//$ftp->make_dir(); we may need to recursively create dirs? TODO

		$iwp_backup_dir = $iwp_backup_core->backups_dir_location().'/';

		$ftp_remote_path = trailingslashit($opts['path']);
		if (!empty($opts['ftp_site_folder'])) {
			$site_name = iwp_getSiteName();
			$ftp_remote_path.= trailingslashit($site_name);
		}
		foreach($backup_array as $file) {
			$fullpath = $iwp_backup_dir.$file;
			$iwp_backup_core->log("FTP upload attempt: $file -> ftp://".$opts['user']."@".$opts['host']."/${ftp_remote_path}${file}");
			$timer_start = microtime(true);
			$size_k = round(filesize($fullpath)/1024,1);
			# Note :Setting $resume to true unnecessarily is not meant to be a problem. Only ever (Feb 2014) seen one weird FTP server where calling SIZE on a non-existent file did create a problem. So, this code just helps that case. (the check for non-empty upload_status[p] is being cautious.
			$upload_status = $iwp_backup_core->jobdata_get('uploading_substatus');
			if (0 == $iwp_backup_core->current_resumption || (is_array($upload_status) && !empty($upload_status['p']) && $upload_status['p'] == 0)) {
				$resume = false;
			} else {
				$resume = true;
			}
	
			if ($ftp->put($fullpath, $ftp_remote_path.$file, FTP_BINARY, $resume, $iwp_backup_core, $ftp_remote_path)) {
				$iwp_backup_core->log("FTP upload attempt successful (".$size_k."KB in ".(round(microtime(true)-$timer_start,2)).'s)');
				$iwp_backup_core->uploaded_file($file);
			} else {
				$iwp_backup_core->log("ERROR: FTP upload failed" );
				$iwp_backup_core->log(sprintf(__("%s upload failed",'InfiniteWP'), 'FTP'), 'error');
			}
		}

		return array('ftp_object' => $ftp, 'ftp_remote_path' => $ftp_remote_path);
	}

	public function listfiles($match = 'backup_') {
		global $iwp_backup_core;

		$opts = $this->get_options();

		$ftp = $this->getFTP(
			$opts['host'],
			$opts['user'],
			$opts['pass'],
			$iwp_backup_core->get_job_option('IWP_ssl_nossl'),
			$iwp_backup_core->get_job_option('IWP_ssl_disableverify'),
			$iwp_backup_core->get_job_option('IWP_ssl_useservercerts'),
			$opts['passive']
		);

		if (is_wp_error($ftp)) return $ftp;

		if (!$ftp->connect()) return new WP_Error('ftp_login_failed', sprintf(__("%s login failure",'InfiniteWP'), 'FTP'));

		$ftp_remote_path = $opts['path'];
		if ($ftp_remote_path) $ftp_remote_path = trailingslashit($ftp_remote_path);
		if (!empty($opts['ftp_site_folder'])) {
			$site_name = iwp_getSiteName();
			$ftp_remote_path.= trailingslashit($site_name);
		}

		$dirlist = $ftp->dir_list($ftp_remote_path);
		if (!is_array($dirlist)) return array();

		$results = array();

		foreach ($dirlist as $k => $path) {

			if ($ftp_remote_path) {
				// Feb 2015 - found a case where the directory path was not prefixed on
				if (0 !== strpos($path, $ftp_remote_path) && (false !== strpos('/', $ftp_remote_path) && false !== strpos('\\', $ftp_remote_path))) continue;
				if (0 === strpos($path, $ftp_remote_path)) $path = substr($path, strlen($ftp_remote_path));
				// if (0 !== strpos($path, $ftp_remote_path)) continue;
				// $path = substr($path, strlen($ftp_remote_path));
				if (0 === strpos($path, $match)) $results[]['name'] = $path;
			} else {
				if ('/' == substr($path, 0, 1)) $path = substr($path, 1);
				if (false !== strpos($path, '/')) continue;
				if (0 === strpos($path, $match)) $results[]['name'] = $path;
			}

			unset($dirlist[$k]);
		}

		# ftp_nlist() doesn't return file sizes. rawlist() does, but is tricky to parse. So, we get the sizes manually.
		foreach ($results as $ind => $name) {
			$size = $ftp->size($ftp_remote_path.$name['name']);
			if (0 === $size) {
				unset($results[$ind]);
			} elseif ($size>0) {
				$results[$ind]['size'] = $size;
			}
		}

		return $results;

	}

	public function delete($files, $ftparr = array(), $sizeinfo = array()) {

		global $iwp_backup_core;
		if (is_string($files)) $files=array($files);

		$opts = $this->get_options();

		if (is_array($ftparr) && isset($ftparr['ftp_object'])) {
			$ftp = $ftparr['ftp_object'];
		} else {
			$ftp = $this->getFTP(
				$opts['host'],
				$opts['user'],
				$opts['pass'],
				$iwp_backup_core->get_job_option('IWP_ssl_nossl'),
				$iwp_backup_core->get_job_option('IWP_ssl_disableverify'),
				$iwp_backup_core->get_job_option('IWP_ssl_useservercerts'),
				$opts['passive']
			);

			if (is_wp_error($ftp) || !$ftp->connect()) {
				if (is_wp_error($ftp)) $iwp_backup_core->log_wp_error($ftp);
				$iwp_backup_core->log("FTP Failure: we did not successfully log in with those credentials (host=".$opts['host'].").");
				return false;
			}

		}

		$ftp_remote_path = isset($ftparr['ftp_remote_path']) ? $ftparr['ftp_remote_path'] : trailingslashit($opts['path']);

		if (!empty($opts['ftp_site_folder'])) {
			$site_name = iwp_getSiteName();
			$ftp_remote_path.= trailingslashit($site_name);
		}

		$ret = true;
		foreach ($files as $file) {
			if (@$ftp->delete($ftp_remote_path.$file)) {
				$iwp_backup_core->log("FTP delete: succeeded (${ftp_remote_path}${file})");
			} else {
				$iwp_backup_core->log("FTP delete: failed (${ftp_remote_path}${file})");
				$ret = false;
			}
		}
		return $ret;

	}

	public function download($file) {

		global $iwp_backup_core;

		$opts = $this->get_options();

		$ftp = $this->getFTP(
			$opts['host'],
			$opts['user'],
			$opts['pass'],
			$iwp_backup_core->get_job_option('IWP_ssl_nossl'),
			$iwp_backup_core->get_job_option('IWP_ssl_disableverify'),
			$iwp_backup_core->get_job_option('IWP_ssl_useservercerts'),
			$opts['passive']
		);
		if (is_wp_error($ftp)) return $ftp;

		if (!$ftp->connect()) {
			$iwp_backup_core->log("FTP Failure: we did not successfully log in with those credentials.");
			$iwp_backup_core->log(sprintf(__("%s login failure",'iwp_backup_core'), 'FTP'), 'error');
			return false;
		}

		//$ftp->make_dir(); we may need to recursively create dirs? TODO
		
		$ftp_remote_path = trailingslashit($opts['path']);
		if (!empty($opts['ftp_site_folder'])) {
			$site_name = iwp_getSiteName();
			$ftp_remote_path.= trailingslashit($site_name);
		}
		$fullpath = $iwp_backup_core->backups_dir_location().'/'.$file;

		$resume = false;
		if (file_exists($fullpath)) {
			$resume = true;
			$iwp_backup_core->log("File already exists locally; will resume: size: ".filesize($fullpath));
		}

		return $ftp->get($fullpath, $ftp_remote_path.$file, FTP_BINARY, $resume, $iwp_backup_core);
	}

	private function ftp_possible() {
		$funcs_disabled = array();
		foreach (array('ftp_connect', 'ftp_login', 'ftp_nb_fput') as $func) {
			if (!function_exists($func)) $funcs_disabled['ftp'][] = $func;
		}
		$funcs_disabled = apply_filters('IWP_ftp_possible', $funcs_disabled);
		return (0 == count($funcs_disabled)) ? true : $funcs_disabled;
	}

	public function config_print() {
		global $iwp_backup_core;
		$possible = $this->ftp_possible();
		
		$opts = $this->get_options();
	}

}
