<?php

/*
Methods to define when extending this class (can use $this->storage and $this->options where relevant):
do_bootstrap($possible_options_array) # Return a WP_Error object if something goes wrong
do_upload($file, $sourcefile) # Return true/false
do_listfiles($match)
do_delete($file) - return true/false
do_download($file, $fullpath, $start_offset) - return true/false
do_config_print()
do_credentials_test_parameters() - return an array: keys = required _POST parameters; values = description of each
do_credentials_test($testfile, $posted_settings) - return true/false
do_credentials_test_deletefile($testfile, $posted_settings)
*/

// Uses job options: Yes
// Uses single-array storage: Yes

if (!class_exists('IWP_MMB_UploadModule')) require_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/backup.upload.php');

/**
 * Note that the naming of this class is historical. There is nothing inherent which restricts it to add-ons, or requires add-ons to use it. It is just an abstraction layer that results in needing to write less code for the storage module.
 */
abstract class IWP_MMB_RemoteStorage_Extension extends IWP_MMB_UploadModule {

	protected $method;

	protected $description;

	protected $storage;

	protected $options;

	private $chunked;

	public function __construct($method, $description, $chunked = true, $test_button = true) {

		$this->method = $method;
		$this->description = $description;
		$this->chunked = $chunked;
		$this->test_button = $test_button;

	}
	
	/**
	 * download method: takes a file name (base name), and removes it from the cloud storage
	 *
	 * @param  string $file specific file for being removed from cloud storage
	 * @return array
	 */
	public function download($file) {
		return $this->download_file(false, $file);
	}
	
	public function backup($backup_array) {
		return $this->upload_files(null, $backup_array);
	}
	
	public function delete($files, $method_obj = false, $sizeinfo = array()) {
		return $this->delete_files(false, $files, $method_obj, $sizeinfo);
	}
		
	protected function required_configuration_keys() {
	}

	public function upload_files($ret, $backup_array) {

		global $iwp_backup_core;

		$this->options = $this->get_options();

		if (!$this->options_exist($this->options)) {
			$iwp_backup_core->log('No '.$this->method.' settings were found');
			$iwp_backup_core->log(sprintf(__('No %s settings were found', 'InfiniteWP'), $this->description), 'error');
			return false;
		}

		$storage = $this->bootstrap();
		if (is_wp_error($storage)) return $iwp_backup_core->log_wp_error($storage, false, true);

		$this->storage = $storage;

		$iwp_backup_dir = trailingslashit($iwp_backup_core->backups_dir_location());

		foreach ($backup_array as $file) {
			$iwp_backup_core->log($this->method." upload ".((!empty($this->options['ownername'])) ? '(account owner: '.$this->options['ownername'].')' : '').": attempt: $file");
			try {
				if ($this->do_upload($file, $iwp_backup_dir.$file)) {
					$iwp_backup_core->uploaded_file($file);
				} else {
					$any_failures = true;
					$iwp_backup_core->log('ERROR: '.$this->method.': Failed to upload file: '.$file);
					$iwp_backup_core->log(__('Error', 'InfiniteWP').': '.$this->description.': '.sprintf(__('Failed to upload %s', 'InfiniteWP'), $file), 'error');
				}
			} catch (Exception $e) {
				$any_failures = true;
				$iwp_backup_core->log('ERROR ('.get_class($e).'): '.$this->method.": $file: Failed to upload file: ".$e->getMessage().' (code: '.$e->getCode().', line: '.$e->getLine().', file: '.$e->getFile().')');
				$iwp_backup_core->log(__('Error', 'InfiniteWP').': '.$this->description.': '.sprintf(__('Failed to upload %s', 'InfiniteWP'), $file), 'error');
			}
		}

		return (!empty($any_failures)) ? null : true;

	}

	public function listfiles($match = 'backup_') {

		try {

			if (!method_exists($this, 'do_listfiles')) {
				return new WP_Error('no_listing', 'This remote storage method does not support file listing');
			}

			$this->options = $this->get_options();
			if (!$this->options_exist($this->options)) return new WP_Error('no_settings', sprintf(__('No %s settings were found', 'InfiniteWP'), $this->description));

			$this->storage = $this->bootstrap();
			if (is_wp_error($this->storage)) return $this->storage;

			return $this->do_listfiles($match);
			
		} catch (Exception $e) {
			global $iwp_backup_core;
			$iwp_backup_core->log('ERROR: '.$this->method.": $file: Failed to list files: ".$e->getMessage().' (code: '.$e->getCode().', line: '.$e->getLine().', file: '.$e->getFile().')');
			return new WP_Error('list_failed', $this->description.': '.__('failed to list files', 'InfiniteWP'));
		}

	}

	public function delete_files($ret, $files, $ignore_it = false) {

		global $iwp_backup_core;

		if (is_string($files)) $files = array($files);

		if (empty($files)) return true;
		if (!method_exists($this, 'do_delete')) {
			$iwp_backup_core->log($this->method.": Delete failed: this storage method does not allow deletions");
			return false;
		}

		if (empty($this->storage)) {

			$this->options = $this->get_options();
			if (!$this->options_exist($this->options)) {
				$iwp_backup_core->log('No '.$this->method.' settings were found');
				$iwp_backup_core->log(sprintf(__('No %s settings were found', 'InfiniteWP'), $this->description), 'error');
				return false;
			}

			$this->storage = $this->bootstrap();
			if (is_wp_error($this->storage)) return $this->storage;

		}

		$ret = true;

		foreach ($files as $file) {
			$iwp_backup_core->log($this->method.": Delete remote: $file");
			try {
				if (!$this->do_delete($file)) {
					$ret = false;
					$iwp_backup_core->log($this->method.": Delete failed");
				} else {
					$iwp_backup_core->log($this->method.": $file: Delete succeeded");
				}
			} catch (Exception $e) {
				$iwp_backup_core->log('ERROR: '.$this->method.": $file: Failed to delete file: ".$e->getMessage().' (code: '.$e->getCode().', line: '.$e->getLine().', file: '.$e->getFile().')');
				$ret = false;
			}
		}
		
		return $ret;
		
	}

	public function download_file($ret, $files) {

		global $iwp_backup_core;

		if (is_string($files)) $files = array($files);

		if (empty($files)) return true;
		if (!method_exists($this, 'do_download')) {
			$iwp_backup_core->log($this->method.": Download failed: this storage method does not allow downloading");
			$iwp_backup_core->log($this->description.': '.__('This storage method does not allow downloading', 'InfiniteWP'), 'error');
			return false;
		}

		$this->options = $this->get_options();
		if (!$this->options_exist($this->options)) {
			$iwp_backup_core->log('No '.$this->method.' settings were found');
			$iwp_backup_core->log(sprintf(__('No %s settings were found', 'InfiniteWP'), $this->description), 'error');
			return false;
		}

		try {
			$this->storage = $this->bootstrap();
			if (is_wp_error($this->storage)) return $iwp_backup_core->log_wp_error($this->storage, false, true);
		} catch (Exception $e) {
			$ret = false;
			$iwp_backup_core->log('ERROR: '.$this->method.": $files[0]: Failed to download file: ".$e->getMessage().' (code: '.$e->getCode().', line: '.$e->getLine().', file: '.$e->getFile().')');
			$iwp_backup_core->log(__('Error', 'InfiniteWP').': '.$this->description.': '.sprintf(__('Failed to download %s', 'InfiniteWP'), $files[0]), 'error');
		}

		$ret = true;
		$iwp_backup_dir = untrailingslashit($iwp_backup_core->backups_dir_location());

		foreach ($files as $file) {
			try {
				$fullpath = $iwp_backup_dir.'/'.$file;
				$start_offset = file_exists($fullpath) ? filesize($fullpath) : 0;

				if (false == ($this->do_download($file, $fullpath, $start_offset))) {
					$ret = false;
					$iwp_backup_core->log($this->method." error: failed to download: $file");
					$iwp_backup_core->log("$file: ".sprintf(__("%s Error", 'InfiniteWP'), $this->description).": ".__('Failed to download', 'InfiniteWP'), 'error');
				}

			} catch (Exception $e) {
				$ret = false;
				$iwp_backup_core->log('ERROR: '.$this->method.": $file: Failed to download file: ".$e->getMessage().' (code: '.$e->getCode().', line: '.$e->getLine().', file: '.$e->getFile().')');
				$iwp_backup_core->log(__('Error', 'InfiniteWP').': '.$this->description.': '.sprintf(__('Failed to download %s', 'InfiniteWP'), $file), 'error');
			}
		}

		return $ret;
	}

	public function config_print() {

		$this->options = $this->get_options();
		$method = $this->method;
	}
	
	protected function options_exist($opts) {
		if (is_array($opts) && !empty($opts)) return true;
		return false;
	}

	public function bootstrap($opts = false, $connect = true) {
		if (false === $opts) $opts = $this->options;
		// Be careful of checking empty($opts) here - some storage methods may have no options until the OAuth token has been obtained
		if ($connect && !$this->options_exist($opts)) return new WP_Error('no_settings', sprintf(__('No %s settings were found', 'InfiniteWP'), $this->description));
		if (!empty($this->storage) && !is_wp_error($this->storage)) return $this->storage;
		return $this->do_bootstrap($opts, $connect);
	}

}
