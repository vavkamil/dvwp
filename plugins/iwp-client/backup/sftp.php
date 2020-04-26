<?php

if ( ! defined('ABSPATH') )
	die();

if (!class_exists('IWP_MMB_RemoteStorage_sftp')) require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/sftp.php');

if (class_exists('IWP_MMB_RemoteStorage_sftp')) {

	// Migrate options to standard-style - April 2017. This then enables them to get picked up by the multi-options settings translation code
	if (!is_array(IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_sftp')) && '' != IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_sftp_settings', '')) {
		$opts = IWP_MMB_Backup_Options::get_iwp_backup_option('IWP_sftp_settings');
		IWP_MMB_Backup_Options::update_iwp_backup_option('IWP_sftp', $opts);
		IWP_MMB_Backup_Options::delete_iwp_backup_option('IWP_sftp_settings');
	}

	class IWP_MMB_UploadModule_sftp extends IWP_MMB_RemoteStorage_sftp {
		public function __construct() {
			parent::__construct('sftp', 'SFTP/SCP');
		}
	}
	
} 
