<?php

if ( ! defined('ABSPATH') )
	die();

if( !function_exists ( 'iwp_mmb_define_constant' )) {
	function iwp_mmb_define_constant(){
		if (!defined('IWP_DEFAULT_OTHERS_EXCLUDE')) define('IWP_DEFAULT_OTHERS_EXCLUDE','upgrade,cache,updraft,backup*,*backups,mysql.sql,debug.log,managewp,infinity-cache,backupwordpress,old-cache,nfwlog,wflogs,wishlist-backup,w3tc,logs,widget_cache,updraftplus');

		if (!defined('IWP_DEFAULT_INCLUDES')) define('IWP_DEFAULT_INCLUDES','google,wp-config.php,.htaccess');

		if (!defined('IWP_DEFAULT_UPLOADS_EXCLUDE')) define('IWP_DEFAULT_UPLOADS_EXCLUDE','backup*,*backups,backwpup*,wp-clone,snapshots,db-backup,backupbuddy_backups,vcf,pb_backupbuddy,sucuri,aiowps_backups,mainwp,wp_system,wpcf7_captcha,wc-logs,siteorigin-widgets,wp-hummingbird-cache,wp-security-audit-log,backwpup-12b462-backups,backwpup-12b462-logs,backwpup-12b462-temp,Dropbox_Backup,cache');

		if (!defined('IWP_DATA_OPTIONAL_TABLES')) define('IWP_DATA_OPTIONAL_TABLES', 'bwps_log,statpress,slim_stats,redirection_logs,Counterize,Counterize_Referers,Counterize_UserAgents,wbz404_logs,wbz404_redirects,tts_trafficstats,tts_referrer_stats,wponlinebackup_generations,svisitor_stat,simple_feed_stats,itsec_log,relevanssi_log,blc_instances,wysija_email_user_stat,woocommerce_sessions,et_bloom_stats,redirection_404,iwp_backup_status,iwp_file_list');

		if (!defined('IWP_ZIP_EXECUTABLE')) define('IWP_ZIP_EXECUTABLE', "/usr/bin/zip,/bin/zip,/usr/local/bin/zip,/usr/sfw/bin/zip,/usr/xdg4/bin/zip,/opt/bin/zip");

		if (!defined('IWP_MYSQLDUMP_EXECUTABLE')) define('IWP_MYSQLDUMP_EXECUTABLE', iwp_mmb_build_mysqldump_list());

		if (!defined('IWP_WARN_FILE_SIZE')) define('IWP_WARN_FILE_SIZE', 1024*1024*250);

		if (!defined('IWP_WARN_DB_ROWS')) define('IWP_WARN_DB_ROWS', 150000);

		if (!defined('IWP_SPLIT_MIN')) define('IWP_SPLIT_MIN', 200);

		if (!defined('IWP_MAXBATCHFILES')) define('IWP_MAXBATCHFILES', 500);

		if (!defined('IWP_WARN_EMAIL_SIZE')) define('IWP_WARN_EMAIL_SIZE', 20*1048576);

		if (!defined('IWP_ZIP_NOCOMPRESS')) define('IWP_ZIP_NOCOMPRESS', '.jpg,.jpeg,.png,.gif,.zip,.gz,.bz2,.xz,.rar,.mp3,.mp4,.mpeg,.avi,.mov');

		if (!defined('IWP_SET_TIME_LIMIT')) define('IWP_SET_TIME_LIMIT', 900);
		if (!defined('IWP_INITIAL_RESUME_INTERVAL')) define('IWP_INITIAL_RESUME_INTERVAL', 300);

		if (!defined('IWP_BINZIP_OPTS')) {
			$zip_nocompress = array_map('trim', explode(',', IWP_ZIP_NOCOMPRESS));
			$zip_binzip_opts = '';
			foreach ($zip_nocompress as $ext) {
				if (empty($zip_binzip_opts)) {
					$zip_binzip_opts = "-n $ext:".strtoupper($ext);
				} else {
					$zip_binzip_opts .= ':'.$ext.':'.strtoupper($ext);
				}
			}
			define('IWP_BINZIP_OPTS', $zip_binzip_opts);
		}

		if(!defined('IWP_BACKUP_DIR')){
		define('IWP_BACKUP_DIR', WP_CONTENT_DIR . '/infinitewp/backups');
		}

		if(!defined('IWP_DB_DIR')){
		define('IWP_DB_DIR', IWP_BACKUP_DIR . '/iwp_db');
		}

		if(!defined('IWP_PCLZIP_TEMPORARY_DIR')){
		define('IWP_PCLZIP_TEMPORARY_DIR', WP_CONTENT_DIR . '/infinitewp/temp/');
		}


	}
}

if (!function_exists('iwp_mmb_modify_cron_schedules')){
	function iwp_mmb_modify_cron_schedules($schedules) {
		$schedules['weekly'] = array('interval' => 604800, 'display' => 'Once Weekly');
		$schedules['fortnightly'] = array('interval' => 1209600, 'display' => 'Once Each Fortnight');
		$schedules['monthly'] = array('interval' => 2592000, 'display' => 'Once Monthly');
		$schedules['every4hours'] = array('interval' => 14400, 'display' => sprintf(__('Every %s hours', 'InfiniteWP'), 4));
		$schedules['every8hours'] = array('interval' => 28800, 'display' => sprintf(__('Every %s hours', 'InfiniteWP'), 8));
		return $schedules;
	}
}

if (!function_exists('iwp_mmb_build_mysqldump_list')) {
	function iwp_mmb_build_mysqldump_list() {
		if ('win' == strtolower(substr(PHP_OS, 0, 3)) && function_exists('glob')) {
			$drives = array('C','D','E');
			
			if (!empty($_SERVER['DOCUMENT_ROOT'])) {
				//Get the drive that this is running on
				$current_drive = strtoupper(substr($_SERVER['DOCUMENT_ROOT'], 0, 1));
				if(!in_array($current_drive, $drives)) array_unshift($drives, $current_drive);
			}
			
			$directories = array();
			
			foreach ($drives as $drive_letter) {
				$dir = glob("$drive_letter:\\{Program Files\\MySQL\\{,MySQL*,etc}{,\\bin,\\?},mysqldump}\\mysqldump*", GLOB_BRACE);
				if (is_array($dir)) $directories = array_merge($directories, $dir);
			}		
			
			$drive_string = implode(',', $directories);
			return $drive_string;
			
		} else return "/usr/bin/mysqldump,/bin/mysqldump,/usr/local/bin/mysqldump,/usr/sfw/bin/mysqldump,/usr/xdg4/bin/mysqldump,/opt/bin/mysqldump";
	}
}

if (!function_exists('gzopen') && function_exists('gzopen64')) {
	function gzopen($filename, $mode, $use_include_path = 0) { 
		return gzopen64($filename, $mode, $use_include_path);
	}
}

if (!function_exists('remove_http')) {
	function remove_http($url = '')
	    {
	        if ($url == 'http://' OR $url == 'https://') {
	            return $url;
	        }
	        return preg_replace('/^(http|https)\:\/\/(www.)?/i', '', $url);
	        
	}
}

if (!function_exists('iwp_getSiteName')) {
	function iwp_getSiteName(){
		$site_name = str_replace(array(
	        "_",
	        "/",
	    			"~"
	    ), array(
	        "",
	        "-",
	        "-"
	    ), rtrim(remove_http(get_bloginfo('url')), "/"));

	   return $site_name;
	}
}

if (!function_exists('iwp_mmb_get_backup_ID_by_taskname')) {
	function iwp_mmb_get_backup_ID_by_taskname($method, $taskName){
		global $iwp_mmb_core, $iwp_backup_core;
		$backup_keys = array();
		if ($method == 'advanced') {
			$backup_keys = $iwp_backup_core->get_timestamp_by_label($taskName);
		}else{
			require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup.class.multicall.php");
			$backup_instance = new IWP_MMB_Backup_Multicall();
			$backup_keys = $backup_instance->get_timestamp_by_label($taskName);
		}

		return $backup_keys;
	}
}