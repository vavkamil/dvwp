<?php

// Options handling
if ( ! defined('ABSPATH') )
	die();

class IWP_MMB_Backup_Options {

	public static function get_iwp_backup_option($option, $default = null) {
		$ret = get_option($option, $default);
		return apply_filters('IWP_get_option', $ret, $option, $default);
	}

	// The apparently unused parameter is used in the alternative class in the Multisite add-on
	public static function update_iwp_backup_option($option, $value, $use_cache = true) {
		return update_option($option, apply_filters('IWP_update_option', $value, $option, $use_cache));
	}

	public static function delete_iwp_backup_option($option) {
		delete_option($option);
	}

	public static function admin_page_url() {
		return admin_url('options-general.php');
	}
}