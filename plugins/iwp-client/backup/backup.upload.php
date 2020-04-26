<?php

if ( ! defined('ABSPATH') )
	die();

abstract class IWP_MMB_UploadModule {

	private $_options;

	private $_instance_id;
	
	/**
	 * Store options (within this class) for this remote storage module. There is also a parameter for saving to the permanent storage (i.e. database).
	 *
	 * @param  array       $options     array of options to store
	 * @param  boolean     $save        whether or not to also save the options to the database
	 * @param  null|String $instance_id optionally set the instance ID for this instance at the same time. This is required if you have not already set an instance ID with set_instance_id()
	 * @return void|Boolean If saving to DB, then the result of the DB save operation is returned.
	 */
	public function set_options($options, $save = false, $instance_id = null) {
	
		$this->_options = $options;
		
		if (null !== $instance_id) $this->set_instance_id($instance_id);
		
		if ($save) return $this->save_options();

	}
	
	/**
	 * Saves the current options to the database. This is a private function; external callers should use set_options().
	 *
	 * @throws Exception if trying to save options without indicating an instance_id, or if the remote storage module does not have the multi-option capability
	 */
	private function save_options() {
	
		if (!$this->supports_feature('multi_options')) {
			throw new Exception('set_options() can only be called on a storage method which supports multi_options (this module, '.$this->get_id().', does not)');
		}
	
		if (!$this->_instance_id) {
			throw new Exception('set_options() requires an instance ID, but was called without setting one (either directly or via set_instance_id())');
		}
		
		global $iwp_backup_core;
		
		$current_db_options = $iwp_backup_core->update_remote_storage_options_format($this->get_id());

		if (is_wp_error($current_db_options)) {
			throw new Exception('set_options(): options fetch/update failed ('.$current_db_options->get_error_code().': '.$current_db_options->get_error_message().')');
		}

		$current_db_options['settings'][$this->_instance_id] = $this->_options;

		return IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_'.$this->get_id(), $current_db_options);
	
	}
	
	/**
	 * Retrieve default options for this remote storage module.
	 * This method would normally be over-ridden by the child.
	 *
	 * @return Array - an array of options
	 */
	public function get_default_options() {
		return array();
	}

	/**
	 * Retrieve a list of supported features for this storage method
	 * This method should be over-ridden by methods supporting new
	 * features.
	 *
	 * Keys are strings, and values are booleans.
	 *
	 * Currently known features:
	 *
	 * - multi_options : indicates that the remote storage module
	 * can handle its options being in the Feb-2017 multi-options
	 * format. N.B. This only indicates options handling, not any
	 * other multi-destination options.
	 *
	 * - multi_servers : not implemented yet: indicates that the
	 * remote storage module can handle multiple servers at backup
	 * time. This should not be specified without multi_options.
	 * multi_options without multi_servers is fine - it will just
	 * cause only the first entry in the options array to be used.
	 *
	 * @return Array - an array of supported features (any features not
	 * mentioned are assumed to not be supported)
	 */
	public function get_supported_features() {
		return array();
	}
	
	/**
	 * Over-ride this to allow methods to not use the hidden version field, if they do not output any settings (to prevent their saved settings being over-written by just this hidden field
	 *
	 * @return [boolean] - return true to output the version field or false to not output the field
	 */
	public function print_shared_settings_fields() {
		return true;
	}

	/**
	 * Prints out the configuration section for a particular module
	 */
	abstract function config_print();

	/**
	 * Supplies the list of keys for options to be saved in the backup job.
	 */
	public function get_credentials() {
		$keys = array('IWP_ssl_disableverify', 'IWP_ssl_nossl', 'IWP_ssl_useservercerts');
		if (!$this->supports_feature('multi_servers')) $keys[] = 'IWP_'.$this->get_id();
		return $keys;
	}


	
	/**
	 * Returns a space-separated list of CSS classes suitable for rows in the configuration section
	 *
	 * @returns String - the list of CSS classes
	 */
	public function get_css_classes() {
		$classes = 'IWPmethod '.$this->get_id();
		if ('' != $this->_instance_id) $classes .= ' '.$this->get_id().'-'.$this->_instance_id;
		return $classes;
	}
	
	/**
	 * Get the backup method identifier for this class
	 *
	 * @return String - the identifier
	 */
	public function get_id() {
		$class = get_class($this);
		// IWP_MMB_UploadModule_
		return substr($class, 21);
	}
	
	/**
	 * Sets the instance ID - for supporting multi_options
	 *
	 * @param String $instance_id - the instance ID
	 */
	public function set_instance_id($instance_id) {
		$this->_instance_id = $instance_id;
	}
	
	/**
	 * Sets the instance ID - for supporting multi_options
	 *
	 * @returns String the instance ID
	 */
	public function get_instance_id() {
		return $this->_instance_id;
	}
	
	/**
	 * Check whether this storage module supports a mentioned feature
	 *
	 * @param String $feature - the feature concerned
	 *
	 * @returns Boolean
	 */
	public function supports_feature($feature) {
		return in_array($feature, $this->get_supported_features());
	}
	
	/**
	 * Retrieve options for this remote storage module
	 *
	 * @uses get_default_options
	 *
	 * @return Array - array of options. This will include default values for any options not set.
	 */
	public function get_options() {
	
		global $iwp_backup_core;
	
		$supports_multi_options = $this->supports_feature('multi_options');

		if (is_array($this->_options)) {
		
			// First, prioritise any options that were explicitly set. This is the eventual goal for all storage modules.
			$options = $this->_options;
			
		} elseif (is_callable(array($this, 'get_opts'))) {
		
			// Next, get any options available via a legacy / over-ride method.
		
			if ($supports_multi_options) {
				// This is forbidden, because get_opts() is legacy and is for methods that do not support multi-options. Supporting multi-options leads to the array format being updated, which will then break get_opts().
				die('Fatal error: method '.$this->get_id().' both supports multi_options and provides a get_opts method');
			}
			
			$options = $this->get_opts();
			
		} else {
		
			// Next, look for job options (which in turn, falls back to saved settings if no job options were set)
	
			$options = $iwp_backup_core->get_job_option('IWP_'.$this->get_id());
			if (!is_array($options)) $options = array();

			if ($supports_multi_options) {

				if (!isset($options['version'])) {
					$options_full = $iwp_backup_core->update_remote_storage_options_format($this->get_id());
					
					if (is_wp_error($options_full)) {
						$iwp_backup_core->log("Options retrieval failure: ".$options_full->get_error_code().": ".$options_full->get_error_message()." (".json_encode($options_full->get_error_data()).")");
						return array();
					}
					
				} else {
					$options_full = $options;
				}
				
				// IWP_MMB_UploadModule::get_options() is for getting the current instance's options. So, this branch (going via the job option) is a legacy route, and hence we just give back the first one. The non-legacy route is to call the set_options() method externally.
				$options = reset($options_full['settings']);

				if (false === $options) {
					$iwp_backup_core->log("Options retrieval failure (no options set)");
					return array();
				}
				$instance_id = key($options_full['settings']);
				$this->set_options($options, false, $instance_id);
				
			}
			
		}

		$options = apply_filters(
			'IWP_backupmodule_get_options',
			wp_parse_args($options, $this->get_default_options()),
			$this
		);
		
		return $options;
		
	}

	public function jobdata_set($key, $value, $legacy_key = null) {
	
		$instance_key = $this->get_id().'-'.($this->_instance_id ? $this->_instance_id : 'no_instance');
		
		global $iwp_backup_core;
		
		$instance_data = $iwp_backup_core->jobdata_get($instance_key);
		
		if (!is_array($instance_data)) $instance_data = array();
		
		$instance_data[$key] = $value;
		
		$iwp_backup_core->jobdata_set($instance_key, $instance_data);
		
		if (is_string($legacy_key)) $iwp_backup_core->jobdata_set($legacy_key, $value);
		
	}

	public function jobdata_get($key, $default = null, $legacy_key = null) {
	
		$instance_key = $this->get_id().'-'.($this->_instance_id ? $this->_instance_id : 'no_instance');
		
		global $iwp_backup_core;
		
		$instance_data = $iwp_backup_core->jobdata_get($instance_key);
		
		if (is_array($instance_data) && isset($instance_data[$key])) return $instance_data[$key];
		
		return is_string($legacy_key) ? $iwp_backup_core->jobdata_get($legacy_key, $default) : $default;
		
	}

	public function jobdata_delete($key, $legacy_key = null) {
	
		$instance_key = $this->get_id().'-'.($this->_instance_id ? $this->_instance_id : 'no_instance');
		
		global $iwp_backup_core;
		
		$instance_data = $iwp_backup_core->jobdata_get($instance_key);
		
		if (is_array($instance_data) && isset($instance_data[$key])) {
			unset($instance_data[$key]);
			$iwp_backup_core->jobdata_set($instance_key, $instance_data);
		}
		
		if (is_string($legacy_key)) $iwp_backup_core->jobdata_delete($legacy_key);
		
	}
}
