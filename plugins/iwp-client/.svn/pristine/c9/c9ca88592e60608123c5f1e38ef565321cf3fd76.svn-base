<?php
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/
/*************************************************************
 * 
 * backup.class.php
 * 
 * Manage Backups
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if ( ! defined('ABSPATH') )
	die();

if(!defined('IWP_BACKUP_DIR')){
define('IWP_BACKUP_DIR', WP_CONTENT_DIR . '/infinitewp/backups');
}

if(!defined('IWP_DB_DIR')){
define('IWP_DB_DIR', IWP_BACKUP_DIR . '/iwp_db');
}

if(!defined('IWP_PCLZIP_TEMPORARY_DIR')){
define('IWP_PCLZIP_TEMPORARY_DIR', WP_CONTENT_DIR . '/infinitewp/temp/');
}

$zip_errors   = array(
    'No error',
    'No error',
    'Unexpected end of zip file',
    'A generic error in the zipfile format was detected',
    'zip was unable to allocate itself memory',
    'A severe error in the zipfile format was detected',
    'Entry too large to be split with zipsplit',
    'Invalid comment format',
    'zip -T failed or out of memory',
    'The user aborted zip prematurely',
    'zip encountered an error while using a temp file. Please check if this domain\'s account has enough disk space.',
    'Read or seek error',
    'zip has nothing to do',
    'Missing or empty zip file',
    'Error writing to a file. Please check if this domain\'s account has enough disk space.',
    'zip was unable to create a file to write to',
    'bad command line parameters',
    'no error',
    'zip could not open a specified file to read'
);
$unzip_errors = array(
    'No error',
    'One or more warning errors were encountered, but processing completed successfully anyway',
    'A generic error in the zipfile format was detected',
    'A severe error in the zipfile format was detected.',
    'unzip was unable to allocate itself memory.',
    'unzip was unable to allocate memory, or encountered an encryption error',
    'unzip was unable to allocate memory during decompression to disk',
    'unzip was unable allocate memory during in-memory decompression',
    'unused',
    'The specified zipfiles were not found',
    'Bad command line parameters',
    'No matching files were found',
    50 => 'The disk is (or was) full during extraction',
    51 => 'The end of the ZIP archive was encountered prematurely.',
    80 => 'The user aborted unzip prematurely.',
    81 => 'Testing or extraction of one or more files failed due to unsupported compression methods or unsupported decryption.',
    82 => 'No files were found due to bad decryption password(s)'
);

class IWP_MMB_Backup_Multicall extends IWP_MMB_Core
{
    var $site_name;
    var $statuses;
    var $tasks;
    var $s3;
    var $ftp;
    var $dropbox;
	var $statusLogVar;
	var $hisID;
	var $backup_url;
	var $backup_settings_vals = array();
	var $iwpScriptStartTime;
        
    function __construct()
    {
        
		//require_once $GLOBALS['iwp_mmb_plugin_dir'].'/pclzip.class.split.php';
		require_once $GLOBALS['iwp_mmb_plugin_dir'].'/pclzip.class.php';
		parent::__construct();
        $this->site_name = str_replace(array(
            "_",
            "/",
	    			"~"
        ), array(
            "",
            "-",
            "-"
        ), rtrim($this->remove_http(get_bloginfo('url')), "/"));
        $this->statuses  = array(
            'db_dump' => 1,
            'db_zip' => 2,
            'files_zip' => 3,
            'finished' => 100
        );
        $this->tasks     = get_option('iwp_client_multi_backup_temp_values');
        if (!empty($GLOBALS['IWP_MMB_PROFILING']['ACTION_START'])) {
			$this->iwpScriptStartTime = $GLOBALS['IWP_MMB_PROFILING']['ACTION_START'];
        }else{
        	$this->iwpScriptStartTime = microtime(1);
        }
    }

    function set_resource_limit()
   	{   		   		
   		$changed = array('execution_time' => 0, 'memory_limit' => 0, 'ini_memory_limit' => ini_get('memory_limit'), 'ini_execution_time' => ini_get('max_execution_time'));
   		@ignore_user_abort(true);

		
		$mod_memory = (@ini_set('memory_limit', -1) == false) ? $changed['memory_limit'] = false : $changed['memory_limit'] = 1;
		
		@ini_set('memory_limit', '-1');
		
      	if ( (int) @ini_get('max_execution_time') < 1200 ) {
     	  	$mod_exec = @ini_set('max_execution_time', 1200) == false ? $changed['execution_time'] = false : $changed['execution_time'] = 1;  //twenty minutes
			@set_time_limit(1200);
     		
     	}
		
     	return $changed;
     	
  	}
  	
	
	function trigger_check($datas)
	{
		global $iwp_multicall_hisID;
		if(!empty($datas))
		{
			$this->set_resource_limit();
			$responseParams = $this -> getRequiredData($datas['backupParentHID'],"responseParams");
			if(empty($responseParams))
			{
				return $this->statusLog($datas['backupParentHID'], array('stage' => 'trigger_check', 'status' => 'error', 'statusMsg' => 'Error while fetching table data', 'statusCode' => 'error_while_fetching_table_data'));
			}
			$action = $responseParams['nextFunc'];
			$status = $responseParams['status'];
			if(empty($action))
			{
				$iwp_multical_db_dump_flag = get_option('iwp_multical_db_dump_flag');
				if ($iwp_multical_db_dump_flag) {
					delete_option('iwp_multical_db_dump_flag');
					$db_result = $this->backupDBPHP($datas['backupParentHID']);
					return $db_result;
				}
				if (empty($datas['params']['success']['nextFunc'])) {
					manual_debug('', 'triggerError');
					return $this->statusLog($datas['backupParentHID'], array('stage' => 'trigger_check', 'status' => 'error', 'statusMsg' => 'Calling Next Function failed - Error while fetching table data', 'statusCode' => 'calling_next_function_failed_error_while_fetching_table_data'));
				}
				$action = $datas['params']['success']['nextFunc'];
			}

			unset($responseParams);
			
			$is_s3 = false;
			$is_s3 = $this->check_if_s3_backup($action, $datas['backupParentHID']);
			
			$iwp_multicall_hisID = $datas['backupParentHID'];
			if(method_exists('IWP_MMB_Backup_Multicall', $action) || !empty($is_s3)){
				manual_debug('', 'triggerStart');
				if(empty($is_s3)){
					$result = self::$action($datas['backupParentHID']);
				}
				else{
					$result = $is_s3;
				}
				manual_debug('', 'triggerEnd');
				return $result;
			}else{
				if ($action == 'backupFilesZIPOver' && $status == 'completed') {
					$result = array('status' => 'completed');
					return $result;
				}
			}
		}
	}
	
	function check_if_s3_backup($action, $h_id){
		$amazons3_result = false;
		if($action == 'amazons3_backup'){
			if(is_new_s3_compatible()){
				require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/s3IWPBackup.php';
				$new_s3_obj = new IWP_MMB_S3_MULTICALL();
				$amazons3_result = $new_s3_obj->amazons3_backup($h_id);
			}
			else{
				$action = 'amazons3_backup_bwd_comp';
				$amazons3_result = self::$action($h_id);
			}
		}
		return $amazons3_result;
	}
	
	function set_backup_task($params)
	{
		global $iwp_mmb_activities_log, $iwp_multicall_hisID;
		
		if(!empty($params))
		{
			// $disk_space = iwp_mmb_check_disk_space();
			// if ($disk_space != false) {
			// 	iwp_mmb_response(array('error' =>  'Your disk space is very low available space: '.$disk_space.'MB'), false);
			// }
			$this->cleanup();
			$initialize_result = refresh_iwp_files_db();
			if(is_array($initialize_result) && isset($initialize_result['error'])){
				return $initialize_result;
			}
			
			
			//darkCode testing purpose static values
			if((empty($params['args']['file_block_size']))||($params['args']['file_block_size'] < 1))
			{
				$params['args']['file_block_size'] = 5;  //MB
			}
			if($params['args']['disable_comp'] == '')
			{
				$params['args']['is_compressed'] = true;
			}
			else
			{
				$params['args']['is_compressed'] = false;
			}
			if((empty($params['args']['file_loop_break_time']))||($params['args']['file_loop_break_time'] < 6))
			{
				$params['args']['file_loop_break_time'] = 15;
			}
			if((empty($params['args']['db_loop_break_time']))||($params['args']['db_loop_break_time'] < 6))
			{
				$params['args']['db_loop_break_time'] = 23;
			}
			if($params['account_info'])
			{
				if((empty($params['account_info']['upload_loop_break_time']))||($params['account_info']['upload_loop_break_time'] < 6))
				{
					$params['account_info']['upload_loop_break_time'] = 23;
				}
				if((empty($params['account_info']['upload_file_block_size']))||($params['account_info']['upload_file_block_size'] < 1))
				{
					$params['account_info']['upload_file_block_size'] = (5*1024*1024)+1;
				}
				else
				{
					$params['account_info']['upload_file_block_size'] = ($params['account_info']['upload_file_block_size']*1024*1024)+1;
				}
				$params['account_info']['actual_file_size'] = 0;
				
			}
			$historyID = '';
			
			$this->statusLog($historyID, array('stage' => 'verification', 'status' => 'processing', 'statusMsg' => 'verificationInitiated'),$params);
			$historyID = $params['args']['parentHID'];
			
			$this->hisID = $historyID;
			$iwp_multicall_hisID = $historyID;
					
			initialize_manual_debug();
			
			$setMemory = $this->set_resource_limit();

			if(!empty($params['account_info']) && !empty($params['account_info']['iwp_dropbox'])){
				if(empty($params['account_info']['iwp_dropbox']['dropbox_access_token']) && time() > 1506556800){
					return $this->statusLog($historyID, array('stage' => 'verification', 'status' => 'error', 'statusMsg' => 'Please update your cloud backup addon to v1.2.0 or above to use Dropbox API V2', 'statusCode' => 'drop_box_update'));
				}elseif(!is_new_dropbox_compatible()){
					return $this->statusLog($historyID, array('stage' => 'verification', 'status' => 'error', 'statusMsg' => 'Please upgrade your PHP version to 5.3.3 or above to use Dropbox V2 API', 'statusCode' => 'drop_box_version_incompitability'));
				}
				upgradeOldDropBoxBackupList($params['account_info']['iwp_dropbox']);
			}
			
			if(file_exists(IWP_BACKUP_DIR) && is_dir(IWP_BACKUP_DIR)){
					$this->statusLog($historyID, array('stage' => 'verification', 'status' => 'processing', 'statusMsg' => 'Directory Writable'));
			}else{
				$mkdir = @mkdir(IWP_BACKUP_DIR, 0755, true);
				if(!$mkdir){
					return $this->statusLog($historyID, array('stage' => 'verification', 'status' => 'error', 'statusMsg' => 'Permission denied; Make sure you have write permission for the wp-content folder.', 'statusCode' => 'permission_denied_make_sure_you_have_write_permission_for_the_wp_content_folder'));
				}
			}
			if(is_writable(IWP_BACKUP_DIR)){
				@file_put_contents(IWP_BACKUP_DIR . '/index.php', ''); //safe
				
			}else{
					$chmod = chmod(IWP_BACKUP_DIR, 777);
					if(!is_writable(IWP_BACKUP_DIR)){
						return $this->statusLog($historyID, array('stage' => 'verification', 'status' => 'error', 'statusMsg' => IWP_BACKUP_DIR.' directory is not writable. Please set 755 or 777 file permission and try again.', 'statusCode' => 'backup_dir_is_not_writable'));
					}
			}
			
			//pclzip temp folder creation
			
			if(file_exists(IWP_PCLZIP_TEMPORARY_DIR) && is_dir(IWP_PCLZIP_TEMPORARY_DIR))
			{
				$this->statusLog($historyID, array('stage' => 'verification', 'status' => 'processing', 'statusMsg' => 'Directorywritable'));
			}
			else
			{
				$mkdir = @mkdir(IWP_PCLZIP_TEMPORARY_DIR, 0755, true);
				if(!$mkdir){
					return $this->statusLog($historyID, array('stage' => 'verification', 'status' => 'error', 'statusMsg' => 'Error creating database backup folder (' . IWP_PCLZIP_TEMPORARY_DIR . '). Make sure you have corrrect write permissions.', 'statusCode' => 'error_creating_database_backup_folder'));
				}
			}
			if(is_writable(IWP_PCLZIP_TEMPORARY_DIR))
			{
				@file_put_contents(IWP_PCLZIP_TEMPORARY_DIR . '/index.php', ''); //safe	
			}
			else
			{
				$chmod = chmod(IWP_PCLZIP_TEMPORARY_DIR, 777);
				if(!is_writable(IWP_PCLZIP_TEMPORARY_DIR)){
					//$this->statusLog($historyID, "verification", false, "can't set 777");
					return $this->statusLog($historyID, array('stage' => 'verification', 'status' => 'error', 'statusMsg' => IWP_PCLZIP_TEMPORARY_DIR.' directory is not writable. Please set 755 or 777 file permission and try again.', 'statusCode' => 'pclzip_dir_not_writable'));
				}
			}
			if ((!defined('DISABLE_IWP_CLOUD_VERIFICATION')) && (empty($params['args']['disable_iwp_cloud_verification']))) {
				$backup_repo_test_obj = new IWP_BACKUP_REPO_TEST();
				$backup_repo_test_result = $backup_repo_test_obj->repositoryTestConnection($params['account_info']);
				if (!empty($backup_repo_test_result['error']) && $backup_repo_test_result['status'] != 'success') {
					return $this->statusLog($historyID, array('stage' => 'backup_repo_test', 'status' => 'error', 'statusMsg' => $backup_repo_test_result['error'], 'statusCode' => $backup_repo_test_result['error_code']));
				}
			}
			//if verification is ok then store the settings in the options table
			$backup_settings_values = array();
			$backup_settings_values['file_block_size'] = $params['args']['file_block_size'];
			$backup_settings_values['is_compressed'] = $params['args']['is_compressed'];
			$backup_settings_values['file_loop_break_time']	= $params['args']['file_loop_break_time'];
			$backup_settings_values['del_host_file']	= $params['args']['del_host_file'];
			$backup_settings_values['task_name']	= $params['args']['backup_name'];
			if($params['account_info'])
			{
				$backup_settings_values['upload_loop_break_time'] = $params['account_info']['upload_loop_break_time'];
				$backup_settings_values['upload_file_block_size'] = $params['account_info']['upload_file_block_size'];
			}
			if($params['args']['what'] != 'files')
			{
				$backup_settings_values['db_loop_break_time']	= $params['args']['db_loop_break_time'];
			}
			
			//Remove the old backups (limit)
			$removed = $this->remove_old_backups($params['task_name']);
			if (is_array($removed) && isset($removed['error'])) 
			{
				return $this->statusLog($this -> hisID, array('stage' => 'removingBackupFiles', 'status' => 'error', 'statusMsg' => 'Error while removing old backups. ('.$removed['error'].')', 'statusCode' => 'error_while_removing_old_backups', 'responseParams' => $result_arr));
			}
			
			update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
			$responseParams = array();
			$responseParams['nextFunc'] = 'backup';
			$responseParams['mechanism'] = 'multiCall';
			
			$iwp_mmb_activities_log->iwp_mmb_collect_backup_details($params);
			
			return $this->statusLog($historyID, array('stage' => 'verification', 'status' => 'completed', 'statusMsg' => 'verified', 'nextFunc' => 'backup', 'responseParams' => $responseParams));
		}
	}
	
	function backup($historyID)
	{
		$this -> hisID = $historyID;
			$args = $this->getRequiredData($historyID, "requestParams");		//format available - $args
		
		$backup_file_details = $this->prepareBackupFileDetails($args);
		extract($backup_file_details);
		if($what == 'db')
		{
			//DB alone funcion			
			$result = $this->backupDB($historyID,$backup_file,$account_info);
			return $result;
		}
		elseif($what == 'files')
		{
			//FIle alone
			$result = $this->backupFiles($historyID,$backup_file,$account_info);
			return $result;
		}
		elseif($what == 'full')
		{
			//both files and db.
			
			$result = $this->backupDB($historyID,$backup_file,$account_info);
			return $result;
		}
		
		
	}

	function backup_db_dump_multi($historyID)
    {	
    	$requestParams = $this->getRequiredData($historyID, "requestParams");
		$db_loop_break_time = $requestParams['args']['db_loop_break_time'];
		$exclude_tables =$requestParams['args']['exclude_tables'];
    	$responseParams = $this -> getRequiredData($historyID,"responseParams");
		$file = $responseParams['file_name'];
		
		if(!$file)
		{
			$file = '';
		}
    	$db_folder = IWP_DB_DIR . '/';
		$temp_sql_file_name = '';
		$db_final_response = array();
		$db_final_response['success'] = array ();
		$db_final_response['success']['type'] = 'db';
		if($file == '')
		{
			$file = DB_NAME;
		}
		$db_final_response['success']['file_name'] = $file;		
		//$temp_sql_file_name = $file."-".$callCount.".sql";			//old method 
		$temp_sql_file_name = $file.".sql";
		$file   = $db_folder . $temp_sql_file_name;
        global $wpdb;
        $paths   = $this->getMySQLPath();
        $brace   = (substr(PHP_OS, 0, 3) == 'WIN') ? '"' : '';
		//$command = $brace . $paths['mysqldump'] . $brace . ' --force --host="' . DB_HOST . '" --user="' . DB_USER . '" --password="' . DB_PASSWORD . '" --add-drop-table --skip-lock-tables "' . DB_NAME . '" > ' . $brace . $file . $brace;
        $command0 = $wpdb->get_col('SHOW TABLES LIKE "'.$wpdb->base_prefix.'%"');
        $full_table = array();
        $structure_only_table = array();
		if (!empty($exclude_tables)) {
			foreach ($command0 as $tk => $table) {
				foreach ($exclude_tables as $ke => $exclude_table) {
					$structure = false;
					if (strpos($table, $exclude_table)) {
						$structure = true;
						break;
					}
				}
				if ($structure) {
					$structure_only_table [] = $table;
				}else{
					$full_table [] = $table;
 				}
			}
		}else{
			$full_table = $command0;
		}
        $wp_tables = join("\" \"",$full_table);
        $skipThisTable = false;
        $command = $brace . $paths['mysqldump'] . $brace . ' --force --host="' . DB_HOST . '" --user="' . DB_USER . '" --password="' . DB_PASSWORD . '" --max_allowed_packet=8M --net_buffer_length=1M --skip-comments --skip-set-charset --allow-keywords --dump-date --add-drop-table --skip-lock-tables --extended-insert "' . DB_NAME . '" "'.$wp_tables.'" > ' . $brace . $file . $brace;
        

		iwp_mmb_print_flush('DB DUMP CMD: Start');
        ob_start();
        update_option('iwp_multical_db_dump_flag', 1);
        if (!empty($structure_only_table)) {
        	$wp_tables = join("\" \"",$structure_only_table);
        	$structure_command = $brace . $paths['mysqldump'] . $brace . ' --force --host="' . DB_HOST . '" --user="' . DB_USER . '" --password="' . DB_PASSWORD . '" --add-drop-table --skip-lock-tables --no-data "' . DB_NAME . '" "'.$wp_tables.'" >> ' . $brace . $file . $brace;
        	// $result = $this->iwp_mmb_exec($structure_command);
        	$command.=' && '.$structure_command;

        }
        $result = $this->iwp_mmb_exec($command);
        ob_get_clean();
		iwp_mmb_print_flush('DB DUMP CMD: End');
		$time = microtime(true);
		$finish_part = $time;
		$total_time_part = $finish_part - $this->iwpScriptStartTime;
        
        if (!$result) { // Fallback to php
            // $result = $this->backup_db_php($file);
            @unlink($file);
            return $result;
        }
        
        if (iwp_mmb_get_file_size($file) == 0 || !is_file($file) || !$result) {
            @unlink($file);
            return false;
        } else {
			$db_final_response['success']['backup_file'] = $responseParams['backup_file'];
			$db_final_response['success']['backup_url'] = $responseParams['backup_url'];
			$db_final_response['success']['parentHID'] = $historyID;
			$db_final_response['success']['backupParentHID'] = $historyID;
			$db_final_response['success']['nextFunc'] = 'backupDBZip';
			$db_final_response['success']['account_info'] = $responseParams['account_info'];
			//$this->statusLog($historyID, "backupDB", true, "completed", $params, true);
			//$this->statusLog($historyID, array('stage' => $backupStage, 'status' => 'completed', 'statusMsg' => 'backupDBCompleted'));
			$db_final_response['success']['status'] = 'partiallyCompleted';
			$backupStage = 'backupDBMultiCall';
			$this->statusLog($historyID, array('stage' => $backupStage, 'status' => 'completed', 'statusMsg' => 'backupDBCompleted','nextFunc' => 'backupDBZip', 'responseParams' => $db_final_response['success']));
			unset($db_final_response['success']['response_data']);
			delete_option('iwp_multical_db_dump_flag');
			//to continue in the same call
			if(($db_loop_break_time - $total_time_part) > 5)
			{
				return $this->backupDBZip($historyID);
			}
			else
			{
				delete_option('iwp_multical_db_dump_flag');
				$db_res_array = array();
				$db_res_array['status'] = $db_final_response['success']['status'];
				$db_res_array['backupParentHID'] = $db_final_response['success']['backupParentHID'];
				$db_res_array['parentHID'] = $db_final_response['success']['parentHID'];
				return $db_res_array;
			}
        }
    }

  function check_mysql_paths()
    {
        global $wpdb;
        $paths = array(
            'mysql' => '',
            'mysqldump' => ''
        );
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $mysql_install = $wpdb->get_row("SHOW VARIABLES LIKE 'basedir'");
            if ($mysql_install) {
                $install_path       = str_replace('\\', '/', $mysql_install->Value);
                $paths['mysql']     = $install_path . '/bin/mysql.exe';
                $paths['mysqldump'] = $install_path . '/bin/mysqldump.exe';
            } else {
                $paths['mysql']     = 'mysql.exe';
                $paths['mysqldump'] = 'mysqldump.exe';
            }
        } else {
            $paths['mysql'] = $this->iwp_mmb_exec('which mysql', true);
            if (empty($paths['mysql']))
                $paths['mysql'] = 'mysql'; // try anyway
            
            $paths['mysqldump'] = $this->iwp_mmb_exec('which mysqldump', true);
            if (empty($paths['mysqldump']))
                $paths['mysqldump'] = 'mysqldump'; // try anyway         
            
        }
        
        
        return $paths;
    }

    public function getMySQLPath(){
    	global $wpdb;
    	 $paths = array(
            'mysql' => '',
            'mysqldump' => ''
        );
    	if (substr(PHP_OS, 0, 3) == 'WIN') {
            $mysql_install = $wpdb->get_row("SHOW VARIABLES LIKE 'basedir'");
            if ($mysql_install) {
                $install_path       = str_replace('\\', '/', $mysql_install->Value);
                $paths['mysql']     = $install_path . '/bin/mysql.exe';
                $paths['mysqldump'] = $install_path . '/bin/mysqldump.exe';
            } else {
                $paths['mysql']     = 'mysql.exe';
                $paths['mysqldump'] = 'mysqldump.exe';
            }
        } else{
	    	$mysqlPath = "/usr/bin/mysqldump,/bin/mysqldump,/usr/local/bin/mysqldump,/usr/sfw/bin/mysqldump,/usr/xdg4/bin/mysqldump,/opt/bin/mysqldump";
			$bin = explode(',' , $mysqlPath);
			$brace   = (substr(PHP_OS, 0, 3) == 'WIN') ? '"' : '';
			$db_folder = IWP_DB_DIR . '/';
			$temp_sql_file_name = "iwp_temp.sql";
			$file   = $db_folder . $temp_sql_file_name;
			foreach ($bin as $key => $value) {
				$command = $brace . $value . $brace . ' --force --host="' . DB_HOST . '" --user="' . DB_USER . '" --password="' . DB_PASSWORD . '" --add-drop-table --skip-lock-tables --extended-insert=FALSE "' . DB_NAME . '" ""'.$wpdb->base_prefix.'options"" > ' . $brace . $file . $brace;
				$result = $this->iwp_mmb_exec($command);
				if (!$result) { 
				   	continue;
				}
				
				if (iwp_mmb_get_file_size($file) == 0 || !is_file($file) || !$result) {
				    continue;
				}
				unlink($file);
				 $paths = array(
			        'mysql' => $value,
			        'mysqldump' => $value
			    );

				 return $paths;
			}
			unlink($file);
        }

        return $paths;
	}
	
	function backup_uploads($historyID)
	{
		//after creating the backup file dont forget to include it in the account_info array 
		$this -> hisID = $historyID;
		$responseParams = $this -> getRequiredData($historyID,"responseParams");
		$account_info = $responseParams['response_data']['account_info'];
		$backup_file = $responseParams['response_data']['backup_file'];
		//storing the filesize value into settings array - first get the values and then append the value of filesize to it
		$this -> backup_settings_vals = get_option('iwp_client_multi_backup_temp_values');
		$backup_settings_values = $this -> backup_settings_vals;	
		$backup_settings_values['actual_file_size'] = iwp_mmb_get_file_size($backup_file);
		update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
		
		if (isset($account_info['iwp_ftp']) && !empty($account_info['iwp_ftp'])) {
			$account_info['iwp_ftp']['backup_file'] = $backup_file;
			iwp_mmb_print_flush('FTP upload: Start');
			$ftp_result                             = $this->ftp_backup($historyID, $account_info['iwp_ftp']);
			if(!$ftp_result)
			{
				if($del_host_file){
					$this->unlinkBackupFiles($backup_file);
				}
				return array('error' => "Unexpected Error", 'error_code' => "unexpected_error");
			}
			else if(is_array($ftp_result) && isset($ftp_result['error'])){
				if($del_host_file){
					$this->unlinkBackupFiles($backup_file);
				}
				return $ftp_result;
			}
			else
			{
				return $ftp_result;
			}
		}
		
		if (isset($account_info['iwp_amazon_s3']) && !empty($account_info['iwp_amazon_s3'])) {
			$account_info['iwp_amazon_s3']['backup_file'] = $backup_file;
			iwp_mmb_print_flush('Amazon S3 upload: Start');
			if(is_new_s3_compatible()){
				require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/s3IWPBackup.php';
				$new_s3_obj = new IWP_MMB_S3_MULTICALL();
				$amazons3_result = $new_s3_obj->amazons3_backup($historyID,$account_info['iwp_amazon_s3']);
			}
			else{
				$amazons3_result = $this->amazons3_backup_bwd_comp($historyID,$account_info['iwp_amazon_s3']);
			}
			iwp_mmb_print_flush('Amazon S3 upload: End');
			if (is_array($amazons3_result) && isset($amazons3_result['error'])) {
				$this->unlinkBackupFiles($backup_file);
			}
			return $amazons3_result;
			
		}
		
		if (isset($account_info['iwp_gdrive']) && !empty($account_info['iwp_gdrive'])) {
			$account_info['iwp_gdrive']['backup_file'] = $backup_file;
			iwp_mmb_print_flush('google Drive upload: Start');
			$gdrive_result = $this->google_drive_backup($historyID, $account_info['iwp_gdrive']);
			iwp_mmb_print_flush('google Drive upload: End');
			if (is_array($gdrive_result) && isset($gdrive_result['error'])){
				if($del_host_file){
					$this->unlinkBackupFiles($backup_file);
				}
			}
			
			return $gdrive_result;
			
		}
		
		if (isset($account_info['iwp_dropbox']) && !empty($account_info['iwp_dropbox'])) {
			$this->statusLog($historyID, array('stage' => 'uploadDrobox', 'status' => 'processing', 'statusMsg' => 'tempDirectorywritable'));
			
			$account_info['iwp_dropbox']['backup_file'] = $backup_file;
			iwp_mmb_print_flush('Dropbox upload: Start');
			$dropbox_result  = $this->dropbox_backup($historyID, $account_info['iwp_dropbox']);
			iwp_mmb_print_flush('Dropbox upload: End');
			
			if (empty($dropbox_result) && $del_host_file) {
				$this->unlinkBackupFiles($backup_file);
			}
			
			$dropbox_skip_errors = array('Failed to connect to content.dropboxapi.com port 443: Connection timed out',
			                             'Could not resolve host: api.dropboxapi.com'
										);
			if (is_array($dropbox_result) && isset($dropbox_result['error']) && !in_array($dropbox_result['error'], $dropbox_skip_errors)) {
				$this->unlinkBackupFiles($backup_file);
			}
			if($dropbox_result['status'] == 'partiallyCompleted')
			{
				return $dropbox_result;
			}
			
			$this->wpdb_reconnect();
			return $dropbox_result;
		}
	   
		if ($del_host_file) {
			//@unlink($backup_file);							//darkCode testing purpose
		}
            
        
	}
	
	function unlinkBackupFiles($this_files){
		if(is_array($this_files)){
			if(!empty($this_files)){
				foreach($this_files as $this_key => $this_file){
					@unlink($this_file);
				}
			}
		}
		else{
			@unlink($this_files);
		}
	}
	
	function backupDB($historyID,$backup_file,$account_info = array())
	{
		manual_debug('', 'backupDBStart');
		$this->statusLog($historyID, array('stage' => 'backupDB', 'status' => 'processing', 'statusMsg' => 'backupDBInitiated'));
		clearstatcache();
		if(file_exists(IWP_DB_DIR) && is_dir(IWP_DB_DIR))
		{
			$this->statusLog($historyID, array('stage' => 'verification', 'status' => 'processing', 'statusMsg' => 'Directorywritable'));
		}
		else
		{
			$mkdir = mkdir(IWP_DB_DIR, 0755, true);
			if(!$mkdir){
				return $this->statusLog($historyID, array('stage' => 'verification', 'status' => 'error', 'statusMsg' => 'Error creating database backup folder (' . IWP_DB_DIR . '). Make sure you have corrrect write permissions.', 'statusCode' => 'error_creating_database_backup_folder'));
			}
		}
		if(is_writable(IWP_DB_DIR))
		{
			@file_put_contents(IWP_DB_DIR . '/index.php', ''); //safe	
		}
		else
		{
			$chmod = chmod(IWP_DB_DIR, 777);
			if(!is_writable(IWP_DB_DIR)){
				//$this->statusLog($historyID, "verification", false, "can't set 777");
				return $this->statusLog($historyID, array('stage' => 'verification', 'status' => 'error', 'statusMsg' => IWP_DB_DIR.' directory is not writable. Please set 755 or 777 file permission and try again.', 'statusCode' => 'db_dir_not_writable'));
			}
		}
		
			$db_index_file = '<?php
			global $old_url, $old_file_path;
			$old_url = \''.get_option('siteurl').'\';
			$old_file_path = \''.ABSPATH.'\';
			';

			@file_put_contents(IWP_DB_DIR . '/index.php', $db_index_file); //safe
			
			//$this->statusLog($historyID, "verification", true, "Backup DB directory Created and writable");
			$this->statusLog($historyID, array('stage' => 'verification', 'status' => 'processing', 'statusMsg' => 'BackupDBDirectoryCreatedAndWritable'));
			$res_arr = array();
			$res_arr['response_data'] = array();
			$res_arr['file_name'] = DB_NAME;
			$res_arr['response_data'] = array();
			$res_arr['backup_file'] = $backup_file;
			$res_arr['backup_url'] = $this -> backup_url;
			$res_arr['account_info'] = $account_info;
			$this->statusLog($historyID, array('stage' => 'backupDB', 'status' => 'initiating', 'statusMsg' => 'createdFileNameAndSent','responseParams' => $res_arr));
			$func = $this->check_sys();	
			$db_result = true;
			if ($db_result) {
				$db_result = $this->backup_db_dump_multi($historyID);
			}
			if ($db_result === true) {
				delete_option('iwp_multical_db_dump_flag');
			}
			if ($db_result == false) {
				delete_option('iwp_multical_db_dump_flag');
				$db_result = $this->backupDBPHP($historyID);
			}	
			
			//arguments format - dbresult_before_zip
			//$result = $this->backupDBZip($historyID,$db_result,$backup_url);				//if DB is succsessful do the DB zip 
			
							
			return $db_result;	
		}
		
	function backupDBZip($historyID)
	{
		manual_debug('', 'backupDBZipStart');
		// if the DB backup is successful do the zip operations 
		$responseParams = $this -> getRequiredData($historyID,"responseParams");
		$responseParams['category'] = 'dbZip';
		$backup_file = $responseParams['backup_file'];
		$backup_url = $responseParams['backup_url'];
		$responseParams['response_data']['backup_file'] = $backup_file;
		$responseParams['response_data']['backup_url'] = $backup_url;
		$responseParams['response_data']['account_info'] = $responseParams['account_info'];
		$db_result = $responseParams['response_data'];
		$this->statusLog($historyID, array('stage' => 'backupDBZip', 'status' => 'processing', 'statusMsg' => 'backupZipInitiated','responseParams' => $responseParams));			
		if ($db_result == false) {
			return array(
			'error' => 'Failed to backup database.'
			);
		} 
		else if (is_array($db_result) && isset($db_result['error'])) {
			return array(
			'error' => $db_result['error']
			);
		}
		else
		{
			unset($responseParams);
			unset($db_result);
			//perform the zip operations here ..... for DB
			iwp_mmb_print_flush('DB ZIP PCL: Start');
			// fallback to pclzip
			//define('IWP_PCLZIP_TEMPORARY_DIR', IWP_BACKUP_DIR . '/');
			/* require_once $GLOBALS['iwp_mmb_plugin_dir'].'/pclzip.class.php';
			$archive = new IWPPclZip($backup_file);
			$result = $archive->add(IWP_DB_DIR, IWP_PCLZIP_OPT_REMOVE_PATH, IWP_BACKUP_DIR); */
			$result = $this -> backupFilesZIP($historyID);
			iwp_mmb_print_flush('DB ZIP PCL: End');
			/* @unlink($db_result);
			@unlink(IWP_BACKUP_DIR.'/iwp_db/index.php');														//dark comment
			@rmdir(IWP_DB_DIR); */
			if (!$result) {
				return $this->statusLog($historyID, array('stage' => 'backupDBZip', 'status' => 'error', 'statusMsg' => 'Database zip failed', 'statusCode' => 'database_zip_failed'));
				return array(
				'error' => 'Failed to zip database (pclZip - ' . $archive->error_code . '): .' . $archive->error_string
				);
			}
			
		}
		//$this->statusLog($historyID, array('stage' => 'backupDBZip', 'status' => 'completed', 'statusMsg' => 'backupZipCompleted'));
		manual_debug('', 'backupDBZipEnd');
		return $result;
	}
	
	function backupDBPHP($historyID)    //file must be db name alone ; $response_array should be table_name and its fields and callCount 
	{
		//getting the settings first 
		$this -> backup_settings_vals = get_option('iwp_client_multi_backup_temp_values');
		$backup_settings_values = $this -> backup_settings_vals;
		
		//$file_block_size = $backup_settings_values['file_block_size'];
		//$is_compressed = $backup_settings_values['is_compressed'];
		//$file_loop_break_time = $backup_settings_values['file_loop_break_time'];
		//$db_loop_break_time = $backup_settings_values['db_loop_break_time'];
		
		//getting the settings by other method
		$requestParams = $this->getRequiredData($historyID, "requestParams");
		$file_block_size = $requestParams['args']['file_block_size'];			//darkcode changed
		$is_compressed = $requestParams['args']['is_compressed'];
		$file_loop_break_time = $requestParams['args']['file_loop_break_time'];
		$db_loop_break_time = $requestParams['args']['db_loop_break_time'];
		$zip_split_size = $requestParams['args']['zip_split_size'];
		$exclude_tables =$requestParams['args']['exclude_tables'];
		$responseParams = $this -> getRequiredData($historyID,"responseParams");
		$file = $responseParams['file_name'];
		$total_time_part = 0;
		
		if(!$file)
		{
			$file = '';
		}
		$backup_file = $responseParams['backup_file'];
		$backup_url = $responseParams['backup_url'];
		$response_array = $responseParams['response_data'];
		$account_info = $responseParams['account_info'];
		$backupStage = '';
		if(empty($response_array))
		{
			$backupStage = 'backupDB';
			$callCount = 0;
		}
		else
		{
			iwp_mmb_print_flush('DB DUMP PHP CALL COUNT :' . $response_array['callCount']);
			$callCount = $response_array['callCount'];
			$backupStage = 'backupDBMultiCall';
		}
		//$this->statusLog($historyID, "backupDB", true, "processing", $params, true);
		$this->statusLog($historyID, array('stage' => $backupStage, 'status' => 'processing', 'statusMsg' => 'backupDBInitiated', 'responseParams' => $responseParams));
		global $wpdb;
		$db_folder = IWP_DB_DIR . '/';
		$time = microtime(true);
		$start = $time;
		$break_flag = '';
		$is_continue = '';
		$break_flag_first_key = '';
		$temp_sql_file_name = '';
		iwp_mmb_print_flush('DB DUMP PHP Fail-safe: Start');
		$dump_data = '';
		//$response_array = array();
		//$response_array['db_response'] = array();
		//$response_array['status'] = '';
		//$response_array['callCount'] = 0;
		$left_out_array = array();
		$left_out_table = '';
		$left_out_count = '';
		$db_final_response = array();
		$db_final_response['success'] = array ();
		$db_final_response['success']['type'] = 'db';
		/* $response_array = array (
				'callCount' => 5,
				'wp_commentmeta' => 0,
				'wp_comments' => 16,
				'wp_links' => 92,
				'wp_options' => 1149,
				'wp_postmeta' => 109,
				'wp_posts' => 116,
				'wp_term_relationships' => 28,
				'wp_term_taxonomy' => 79,
				'wp_terms' => 22,
				);*/
		
		$left_out_array = array_slice($response_array,-1,1);
		array_pop($response_array);
		$response_array['callCount'] = $callCount;
		if($file == '')
		{
			$file = DB_NAME;
		}
		$db_final_response['success']['file_name'] = $file;		
		//$temp_sql_file_name = $file."-".$callCount.".sql";			//old method 
		$temp_sql_file_name = $file.".sql";
		$file   = $db_folder . $temp_sql_file_name;
		//file_put_contents($file, '');//safe  to reset any old data
		/* if($callCount == 0)				//used in old method
		{
			$db_final_response['success']['file'] = $file;
			file_put_contents($file, '');//safe  to reset any old data
		} */
		//$tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
			$this_prefix = $wpdb->base_prefix;
			$tables = $wpdb->get_results('SHOW TABLES LIKE "'.$this_prefix.'%"', ARRAY_N);
		
		foreach ($tables as $table) {
			$is_continue = '';
			foreach($response_array as $k => $v)
			{
				if($k == $table[0])
				{
					$is_continue = 'set';
					break;
				}
				else
				{
					$is_continue = '';
				}
			}
			if($is_continue == 'set')
			{
				continue;
			}
			
			foreach ($left_out_array as $key => $val)
			{
				$left_out_table = $key;
				$left_out_count = $val;
			}
			if($left_out_table != $table[0])
			{
				//drop existing table
				$dump_data    = "DROP TABLE IF EXISTS $table[0];";
				file_put_contents($file, $dump_data, FILE_APPEND);
				//create table
				$create_table = $wpdb->get_row("SHOW CREATE TABLE $table[0]", ARRAY_N);
				$dump_data = "\n\n" . $create_table[1] . ";\n\n";
				$response_array[$table[0]] = 0;
				file_put_contents($file, $dump_data, FILE_APPEND);
				//$left_out_count = '';
			}
			//Skip log tables 
			$skipThisTable = false;
			if (!empty($exclude_tables)) {
				foreach ($exclude_tables as $ke => $exclude_table) {
					if (strpos($table[0], $exclude_table)) {
							$skipThisTable = true;
							break;
					}
				}
			}
			if ($skipThisTable) {
				continue;
			}
			$count = $wpdb->get_var("SELECT count(*) FROM $table[0]");
			$count_field = 1;
			
			$table_fields = $wpdb->get_results("SHOW COLUMNS FROM $table[0]", ARRAY_A);
			$no_of_cols = count($table_fields);
			$initialCount = 0;
			$done_count = 0;
			if($left_out_table == $table[0]){
				$breakingCount = isset($responseParams['breakingCount']) ? $responseParams['breakingCount'] : 0;				//new changes
			}
			else{
				$breakingCount = 0;
			}
				
			if(!$breakingCount)
			{
				$breakingCount = 0;
			}
			if ($count > 100)
			{
				$count = ceil($count / 100);
				if($left_out_count > 0)
				{
					$temp_left_count = $left_out_count;
					//$done_count = floor($temp_left_count / (100*$no_of_cols));
					$done_count = $breakingCount;
				}
			}
			else if ($count > 0)
			{            
				$count = 1;                
			}
			$table_structure = $wpdb->get_results("DESCRIBE $table[0]");
			$search = array("\x00", "\x0a", "\x0d", "\x1a");
			$replace = array('\0', '\n', '\r', '\Z');
			$defs = array();
			$integer_fields = array();
			foreach ($table_structure as $struct) {
				if ( (0 === strpos($struct->Type, 'tinyint')) || (0 === strpos(strtolower($struct->Type), 'smallint')) ||
					(0 === strpos(strtolower($struct->Type), 'mediumint')) || (0 === strpos(strtolower($struct->Type), 'int')) || (0 === strpos(strtolower($struct->Type), 'bigint')) ) {
						$defs[strtolower($struct->Field)] = ( null === $struct->Default ) ? 'NULL' : $struct->Default;
						$integer_fields[strtolower($struct->Field)] = "1";
				}
			}
			for($i = 0; $i < $count; $i++){
				if($done_count > 0)
				{
					if($done_count > ($i))
					{
						$count_field += 100 * $no_of_cols;
						continue;
					}
				}
				
				iwp_mmb_auto_print('backup_db_php_fail_safe');
				$low_limit = $i * 100;
				$qry       = "SELECT * FROM $table[0] LIMIT $low_limit, 100";
				$rows      = $wpdb->get_results($qry, ARRAY_A);
				
				$number_data_types = 'tinyint, smallint, mediumint, bigint, int, decimal, numeric, real, float, double';
				if (is_array($rows)) {
						foreach ($rows as $row) {
						manual_debug('', 'eachRow', 1000);
						//insert single row
						if(($table[0] != $left_out_table))
						$dump_data = "INSERT INTO $table[0] VALUES(";
						if(($table[0] == $left_out_table)&&($left_out_count <= $count_field))
						$dump_data = "INSERT INTO $table[0] VALUES(";
						$num_values = count($row);
						$j          = 1;
						foreach ($row as $key => $value) {
							$count_field++;
							$response_array[$table[0]] = $count_field;
							if(($left_out_table == $table[0])&&($count_field <= $left_out_count))
							{
								$j++;
								continue;
							}
							$time = microtime(true);
							$finish_part = $time;
							$total_time_part = $finish_part - $this->iwpScriptStartTime;
							
							//$dump_data .= $count_field;
							/****************New method starts here********************/	
							// if (empty($value) && $value != 0 || $value == '') {
							// 	$default_value = isset($table_description[$key]['Default']) ? $table_description[$key]['Default']: NULL;
							// 	if (@strstr(strtolower($number_data_types), substr($table_description[$key]['Type'], 0, strpos($table_description[$key]['Type'], '('))) !== false && ($default_value === NULL && $default_value != '') ) {
							// 		if ($default_value === NULL) {
							// 			$num_values == $j ? $dump_data .= "NULL " : $dump_data .= "NULL, ";
							// 		} else {
							// 			$num_values == $j ? $dump_data .= "'$default_value'" : $dump_data .= "'$default_value', ";
							// 		}
							// 	} else if($default_value === NULL){
							// 		$num_values == $j ? $dump_data .= "NULL " : $dump_data .= "NULL, ";
							// 	} else {
							// 		$num_values == $j ? $dump_data .= "$default_value" : $dump_data .= "$default_value,";
							// 	}
							// } else if (@strstr(strtolower($number_data_types), substr($table_description[$key]['Type'], 0, strpos($table_description[$key]['Type'], '('))) !== false) {
							//   	$num_values == $j ? $dump_data .= "'$value'" : $dump_data .= "'$value',";
							// } else {
							//     $value = addslashes($value);
							// 	$value = str_replace("\n", "\\n", $value);
							// 	$value = str_replace("\r", "\\r", $value);
							// 	$num_values == $j ? $dump_data .= "'" . $value . "'" : $dump_data .= "'" . $value . "', ";
							// }
							//$num_values = $dump_data;
							/*********New Method ends Here******************/	

							/**********Old Method start here ************/
							// $value2 = $value;
							// $value = addslashes($value);
							// $value = preg_replace("/\n/Ui", "\\n", $value);
							// $value = str_replace("\n", "\\n", $value);
							// $value = str_replace("\r", "\\r", $value);
							// $num_values == $j ? $dump_data .= "'" . $value . "'" : $dump_data .= "'" . $value . "', ";
							/*************** Old Method ends here ********/
							
							/**********Phoenix Method start here ************/
							if (isset($integer_fields[strtolower($key)])) {
								// make sure there are no blank spots in the insert syntax,
								// yet try to avoid quotation marks around integers
								$value = ( null === $value || '' === $value) ? $defs[strtolower($key)] : $value;
								$value = ( '' === $value ) ? "''" : $value;
							} else {
								$value = (null === $value) ? 'NULL' : "'" . str_replace($search, $replace, str_replace('\'', '\\\'', str_replace('\\', '\\\\', $value))) . "'";
							}

							/**********Phoenix Method end here ************/

							$num_values == $j ? $dump_data .= $value: $dump_data .= $value . ", ";


							$j++;
							unset($value);
							if($total_time_part > $db_loop_break_time)
							{
								$break_flag = 'set';
								$break_flag_first_key = 'set';
								//$this -> sendNextCallFlag = '';
								break;
							}
							else
							{
								$break_flag == '';
							}
						}
							if(($left_out_table == $table[0])&&(($count_field <= $left_out_count-1)&&(!empty($count_field))))
						{
							continue;
						}
						//if(($break_flag == '')&&($count_field > $left_out_count))
						if(($break_flag == ''))
						{
							$dump_data .= ");\n";
						}
						else
						{
							break;
						}
						/* if($count_field != $left_out_count)
						{
						} */
						file_put_contents($file, $dump_data, FILE_APPEND);
					}
				}
				if($break_flag == 'set')
				{
					break;
				}
			}
			
			
			if($break_flag == '')
			{
				$dump_data = "\n\n\n";
				file_put_contents($file, $dump_data, FILE_APPEND);
				//manual_debug('', 'endingTableSingle');
			}
			else
			{
				//$temp_sql_file_name = "DE_dbFailsafeCont"."-".$callCount.".sql";
				file_put_contents($file, $dump_data, FILE_APPEND);
				$callCount++;
				//$response_array['status'] = 'partiallyCompleted';
				$response_array['callCount'] = $callCount;
				$db_final_response['success']['response_data']  = $response_array;
				$db_final_response['success']['breakingCount'] = $i;
				$db_final_response['success']['status'] = 'partiallyCompleted';
				$db_final_response['success']['parentHID'] = $historyID;
				$db_final_response['success']['backupParentHID'] = $historyID;
				$db_final_response['success']['nextFunc'] = 'backupDBPHP';
				$db_final_response['success']['file'] = $file;
				$db_final_response['success']['backup_file'] = $backup_file;
				$db_final_response['success']['backup_url'] = $backup_url;
				$db_final_response['success']['account_info'] = $account_info;
				
				//$this->statusLog($historyID, array('stage' => $backupStage, 'status' => 'completed', 'statusMsg' => 'singleCallCompleted'));
				$this->statusLog($historyID, array('stage' => $backupStage, 'status' => 'completed', 'statusMsg' => 'singleDBCallPartiallyCompleted','nextFunc' => 'backupDBPHP', 'responseParams' => $db_final_response['success']));
				$db_res_array = array();
				$db_res_array['status'] = $db_final_response['success']['status'];
				$db_res_array['backupParentHID'] = $db_final_response['success']['backupParentHID'];
				$db_res_array['parentHID'] = $db_final_response['success']['parentHID'];
				//manual_debug('', 'endingTableMulti');
				return $db_res_array;
				
				break;
			}
			
			unset($rows);
			unset($dump_data);
		}
		
		unset($tables);
		iwp_mmb_print_flush('DB DUMP PHP Fail-safe: End');
		
		
		if (iwp_mmb_get_file_size($file) == 0 || !is_file($file))
		{
			//@unlink($file);
			$this->statusLog($historyID, array('stage' => $backupStage, 'status' => 'error', 'statusMsg' => 'DatabaseBackupFailed', 'statusCode' => 'database_backup_failed'));
			return array(
			'error' => 'Database backup failed. Try to enable MySQL dump on your server.', 'error_code' => 'database_backup_failed_try_to_enable_mysql_dump_on_your_server'       							//returning here may not be necessary
			);
		}
		$db_final_response['success']['response_data']  = $response_array;
		$db_final_response['success']['backup_file'] = $backup_file;
		$db_final_response['success']['backup_url'] = $backup_url;
		$db_final_response['success']['parentHID'] = $historyID;
		$db_final_response['success']['backupParentHID'] = $historyID;
		$db_final_response['success']['nextFunc'] = 'backupDBZip';
		$db_final_response['success']['account_info'] = $account_info;
		//$this->statusLog($historyID, "backupDB", true, "completed", $params, true);
		//$this->statusLog($historyID, array('stage' => $backupStage, 'status' => 'completed', 'statusMsg' => 'backupDBCompleted'));
		$db_final_response['success']['status'] = 'partiallyCompleted';
		unset($response_array);
		$this->statusLog($historyID, array('stage' => $backupStage, 'status' => 'completed', 'statusMsg' => 'backupDBCompleted','nextFunc' => 'backupDBZip', 'responseParams' => $db_final_response['success']));
		unset($db_final_response['success']['response_data']);
		//to continue in the same call
		if(($db_loop_break_time - $total_time_part) > 5)
		{
			return $this->backupDBZip($historyID);
		}
		else
		{
			$db_res_array = array();
			$db_res_array['status'] = $db_final_response['success']['status'];
			$db_res_array['backupParentHID'] = $db_final_response['success']['backupParentHID'];
			$db_res_array['parentHID'] = $db_final_response['success']['parentHID'];
			return $db_res_array;
		}
        		
	}
	
	function backupFiles($historyID, $backup_file='', $account_info = array(), $exclude = array(), $include = array())
	{
		iwp_mmb_auto_print("backupFiles");
		$this -> hisID = $historyID;
		
		$initialize_result = refresh_iwp_files_db();
		if(is_array($initialize_result) && isset($initialize_result['error'])){
			return $initialize_result;
		}
		//for exclude and include
		$requestParams = $this->getRequiredData($historyID, "requestParams");
		$exclude = $requestParams['args']['exclude'];
		$include = $requestParams['args']['include'];
		$exclude_extensions = $requestParams['args']['exclude_extensions'];
		$exclude_file_size = $requestParams['args']['exclude_file_size'];
		$category = '';
		if(empty($exclude_extensions))
		{
			$exclude_extensions = array();
		}
		else if($exclude_extensions == 'eg. .zip,.mp4')
		{
			$exclude_extensions = array();
		}
		else
		{
			$exclude_extensions_array = explode(",",$exclude_extensions);
			$exclude_extensions = array();
			$exclude_extensions = $exclude_extensions_array;
		}
		if($backup_file != '')
		{
			$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'processing', 'statusMsg' => 'backupFilesInitiated'));
		}
		$backup_url = $this -> backup_url;
		$zip_split_part = 0;
		if($backup_file == '')
		{
			$responseParams = $this -> getRequiredData($this -> hisID, "responseParams");
			$backup_file = $responseParams['response_data']['backup_file'];
			$backup_url = $responseParams['response_data']['backup_url'];
			$category = $responseParams['category'];
			$account_info = $responseParams['response_data']['account_info'];
			$zip_split_part = $responseParams['response_data']['zip_split_part'];
			if(empty($zip_split_part))
			{
				$zip_split_part = 0;
			}
			$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'processing', 'statusMsg' => 'backupFilesInitiated','responseParams' => $responseParams));
		}
		/* if($category == "fileZipAfterDBZip")
		{
			$account_info = $responseParams['account_info'];
		} */
		
		
		
		//Always remove backup folders    
      $remove = array(
            trim(basename(WP_CONTENT_DIR)) . "/infinitewp/backups",
            trim(basename(WP_CONTENT_DIR)) . "/" . md5('iwp_mmb-client') . "/iwp_backups",
			trim(basename(WP_CONTENT_DIR)) . "/cache",
			trim(basename(WP_CONTENT_DIR)) . "/managewp/backups",
			trim(basename(WP_CONTENT_DIR)) . "/backupwordpress",
			trim(basename(WP_CONTENT_DIR)) . "/contents/cache",
			trim(basename(WP_CONTENT_DIR)) . "/content/cache",
			trim(basename(WP_CONTENT_DIR)) . "/old-cache",
			trim(basename(WP_CONTENT_DIR)) . "/cmscommander/backups",
			trim(basename(WP_CONTENT_DIR)) . "/gt-cache",
			trim(basename(WP_CONTENT_DIR)) . "/wfcache",
			trim(basename(WP_CONTENT_DIR)) . "/bps-backup",
			trim(basename(WP_CONTENT_DIR)) . "/old-cache",
			trim(basename(WP_CONTENT_DIR)) . "/nfwlog",
			trim(basename(WP_CONTENT_DIR)) . "/upgrade",
			trim(basename(WP_CONTENT_DIR)) . "/nfwlog",
			trim(basename(WP_CONTENT_DIR)) . "/wflogs",
			trim(basename(WP_CONTENT_DIR)) . "/debug.log",
			trim(basename(WP_CONTENT_DIR)) . "/wptouch-data/infinity-cache/",
			trim(basename(WP_CONTENT_DIR)) . "/mysql.sql",
			trim(basename(WP_CONTENT_DIR)) . "/wishlist-backup",
			trim(basename(WP_CONTENT_DIR)) . "/w3tc",
			trim(basename(WP_CONTENT_DIR)) . "/logs",
			trim(basename(WP_CONTENT_DIR)) . "/widget_cache",
			trim(basename(WP_CONTENT_DIR)) . "/tmp",
			trim(basename(WP_CONTENT_DIR)) . "/updraft",
			trim(basename(WP_CONTENT_DIR)) . "/updraftplus",
			trim(basename(WP_CONTENT_DIR)) . "/backups",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/wp-clone",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/uploads/db-backup",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/ithemes-security/backups",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/mainwp/backup",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/backupbuddy_backups",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/vcf",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/pb_backupbuddy",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/sucuri",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/aiowps_backups",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/mainwp",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/snapshots",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/wp_system",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/wpcf7_captcha",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/wc-logs",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/siteorigin-widgets",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/wp-hummingbird-cache",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/wp-security-audit-log",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/backwpup-12b462-backups",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/backwpup-12b462-logs",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/wpallimport",
			trim(basename(WP_CONTENT_DIR)) . "/uploads/backwpup-12b462-temp",
			trim(basename(WP_CONTENT_DIR)) . "/Dropbox_Backup",
			trim(basename(WP_PLUGIN_DIR)) . "/cache",
			"wp-admin/error_log",
            "wp-admin/php_errorlog",
            "error_log",
            "error.log",
            "debug.log",
            "WS_FTP.LOG",
            "security.log",
            "wp-tcapsule-bridge.zip",
            "dbcache",
            "pgcache",
            "objectcache",
            "wp-snapshots",
            "site_map.xml",
            "iwp-clone-log.txt",
            "iwp-restore-log.txt"
        );
		manual_debug('', 'beforeExclude', 0);
		if((!empty($exclude_file_size))||(!empty($exclude_extensions)))
		{
				/* //removing files which are larger than the specified size
			$total_files_array = get_all_files_from_dir(ABSPATH, $remove);
			$files_excluded_by_size = array();
			foreach($total_files_array as $key => $value)
			{
				$this_base_name = basename($value);
				$skip_after_ext = false;
				//file extension based exclude
				if(is_array($exclude_extensions) && (!empty($exclude_extensions)))
				{
					foreach($exclude_extensions as $ext)
					{
						$this_pos = strrpos($this_base_name, $ext);
						if($this_pos !== false)
						{
							if(substr($this_base_name, $this_pos) == $ext)
							{
								$files_excluded_by_size[] = substr($value, strlen(ABSPATH));
								$skip_after_ext = true;											//to skip the file exclude by size 
								break;
							}
						}
					}
				}
				if($skip_after_ext)
				{
					continue;
				}
				//file size based exclude
				if(!empty($exclude_file_size))
				{
					if(iwp_mmb_get_file_size($value) >= $exclude_file_size*1024*1024)
					{
						$files_excluded_by_size[] = substr($value, strlen(ABSPATH));
					}
				}
			}
				$remove = array_merge($remove, $files_excluded_by_size); */
		}
		$exclude = array_merge($exclude, $remove);
		manual_debug('', 'afterExclude', 0);
        //Exclude paths
       
                     
        //Include paths by default
        $add = array(
            trim(WPINC),
            trim(basename(WP_CONTENT_DIR)),
            "wp-admin"
        );
        chdir(ABSPATH);
		$include_data = array();
		if (!empty($include) && is_array($include)) {
			foreach ($include as $data) {
				if ($data && file_exists(ABSPATH . $data))
				$include_data[] = ABSPATH . $data . '/';
			}
		}
		
		foreach ($add as $data) {
			if (file_exists(ABSPATH . $data))
			$include_data[] = ABSPATH . $data . '/';
		}
		
		//Include root files
		if ($handle = @opendir(ABSPATH)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && !is_dir($file) && file_exists(ABSPATH . $file)) {
					$include_data[] = ABSPATH . $file;
				}
			}
			closedir($handle);
		}
		
		//exclude paths
		$exclude_data = array();
		if (!empty($exclude) && is_array($exclude)) {
			foreach ($exclude as $data) {
				if (is_dir(ABSPATH . $data))
				$exclude_data[] = $data . '/';
				else
				$exclude_data[] = $data;
			}
		}
		
		foreach ($remove as $rem) {
			$exclude_data[] = $rem . '/';
		}
		
		iwp_mmb_print_flush('Exclude Include Time Taken');
		
		$result_arr = array();
		$result_arr['response_data']['nextCount'] = 0;
		$result_arr['status'] = 'processing';
		$result_arr['category'] = $category;
		/* $include_data = array (
			0 => 'F:\\wamp\\www\\plugin_for_bugs/wp-new-dark/',
		); */
		/* $include_data = array (
			0 => '/mnt/weba/e2/89/53672689/htdocs/wordpress_v2/wp-content/uploads/2015/03/',
		); */
		$result_arr['response_data']['include_data'] = $include_data;
		$result_arr['response_data']['exclude_data'] = $exclude_data;
		$result_arr['response_data']['backup_file'] = $backup_file;
		$result_arr['response_data']['backup_url'] = $backup_url;
		$result_arr['response_data']['account_info'] = $account_info;
		$result_arr['response_data']['zip_split_part'] = $zip_split_part;
		//$result_arr['response_data']['files_excluded_by_size'] = $files_excluded_by_size;
		
		$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'processing', 'statusMsg' => 'backupFileSingleCallStage1Complete','responseParams' => $result_arr));
		unset($result_arr);
		$result = $this->backupFilesZIP($this -> hisID);
		return $result;
	}
	
	function backupFilesZIP($historyID)
	{
		$this -> hisID = $historyID;
		$files_with_error = array();
		$files_excluded_by_size = array();
		//get the backup settings values from options table
		$this -> backup_settings_vals = get_option('iwp_client_multi_backup_temp_values');
		$backup_settings_values = $this -> backup_settings_vals;
		//$file_block_size = $backup_settings_values['file_block_size'];
		//$is_compressed = $backup_settings_values['is_compressed'];
		//$file_loop_break_time = $backup_settings_values['file_loop_break_time'];
		//$task_name = $backup_settings_values['task_name'];
		
		//get the settings by other method
		$requestParams = $this->getRequiredData($historyID, "requestParams");
		$file_block_size = $requestParams['args']['file_block_size'];			//darkcode changed
		$is_compressed = $requestParams['args']['is_compressed'];
		$file_loop_break_time = $requestParams['args']['file_loop_break_time'];
		$task_name = $requestParams['args']['backup_name'];
		$exclude_file_size = $requestParams['args']['exclude_file_size'];
		$exclude_extensions = explode(",",$requestParams['args']['exclude_extensions']);
		$zip_split_size = $requestParams['args']['zip_split_size'];
		$v_offset = 0;
		if(isset($backup_settings_values['dbFileHashValue']) && !empty($backup_settings_values['dbFileHashValue'][$historyID]))
		{
			$dbFileHashValue = $backup_settings_values['dbFileHashValue'][$historyID];
		}
		else
		{
			$dbFileHashValue = array();
		}
		$responseParams = $this -> getRequiredData($historyID,"responseParams");
		$category =  $responseParams['category'];                        //Am getting the category to perform the dbZip actions
		
		if(!$responseParams)
		{
			return $this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'error', 'statusMsg' => 'Backup of files failed - Error while fetching table data', 'statusCode' => 'backup_of_files_failed_error_while_fetching_table_data'));
		}
		
		$include_data = isset($responseParams['response_data']['include_data']) ? $responseParams['response_data']['include_data'] : array();
		$exclude_data = isset($responseParams['response_data']['exclude_data']) ? $responseParams['response_data']['exclude_data'] : array();
		$backup_file = $responseParams['response_data']['backup_file'];
		$backup_url = $responseParams['response_data']['backup_url'];
		$nextCount = isset($responseParams['response_data']['nextCount']) ? $responseParams['response_data']['nextCount'] : 0;
		$account_info = $responseParams['response_data']['account_info'];
		$files_with_error = isset($responseParams['response_data']['files_with_error']) ? $responseParams['response_data']['files_with_error'] : array();
		$files_excluded_by_size = isset($responseParams['response_data']['files_excluded_by_size']) ? $responseParams['response_data']['files_excluded_by_size'] : array();
		$p_filedescr_list = isset($responseParams['response_data']['p_filedescr_list']) ? $responseParams['response_data']['p_filedescr_list'] : array();
		$zip_split_part = isset($responseParams['response_data']['zip_split_part']) ? $responseParams['response_data']['zip_split_part'] : 0; 
		$is_new_zip = isset($responseParams['response_data']['is_new_zip']) ? $responseParams['response_data']['is_new_zip'] : 0;
			$get_file_list = isset($responseParams['response_data']['get_file_list']) ? $responseParams['response_data']['get_file_list'] : '';
			$next_file_index = isset($responseParams['response_data']['next_file_index']) ? $responseParams['response_data']['next_file_index'] : 0;
		/* if(empty($zip_split_part))
		{
			$zip_split_part = 1;
		} */
			//If the zip file exceeds ~1.6 GB we are splitting the zip file into many parts.
		if((!empty($zip_split_part))&&(!empty($is_new_zip)))
		{
			if(strpos($backup_file, '_iwp_part_'))
			{
				$backup_file = substr($backup_file, 0, strpos($backup_file, '_iwp_part_')).'_iwp_part_'.$zip_split_part.'.zip';
				$backup_url = substr($backup_url, 0, strpos($backup_url, '_iwp_part_')).'_iwp_part_'.$zip_split_part.'.zip';
			}
			else
			{
				$backup_file = substr($backup_file, 0, strpos($backup_file, '.zip')).'_iwp_part_'.$zip_split_part.'.zip';
				$backup_url = substr($backup_url, 0, strpos($backup_url, '.zip')).'_iwp_part_'.$zip_split_part.'.zip';
			}
		}
		if(empty($zip_split_part))
		{
			$zip_split_part = 0;
		}
		
		include_once $GLOBALS['iwp_mmb_plugin_dir'].'/pclzip.class.php';
		$returnArr = array();
		if((($nextCount != 0)||($category == 'fileZipAfterDBZip'))&&(empty($is_new_zip)))
		{
			unset($responseParams);
			$initialFileSize = iwp_mmb_get_file_size($backup_file)/1024/1024;
			$returnArr = $this->backupFilesNext($include_data, $exclude_data, $backup_file, $backup_url, $nextCount, $p_filedescr_list, $account_info, $files_with_error, $files_excluded_by_size, $zip_split_part);
			$fileNextTimeTaken = microtime(true) - $this->iwpScriptStartTime;
				echo "<br>iwpmsg Total file size".(iwp_mmb_get_file_size($backup_file)/1024/1024);
			$file_size_in_this_call = (iwp_mmb_get_file_size($backup_file)/1024/1024) - $initialFileSize;
				echo "<br>iwpmsg file size in this call".$file_size_in_this_call;
				echo "<br>iwpmsg Time taken in this call ".$fileNextTimeTaken."<br>";
		/*	
			//Some times the difference between intitial and this call may equal to 0 
			if( !(($file_size_in_this_call == 0) && is_array($returnArr) && !empty($returnArr['error'])) && !(is_array($returnArr) && !empty($returnArr['isGetFileList'])))
			{
				return array( 'error' => 'Zip-error: Unable to zip', 'error_code' => 'zip_error_unable_to_zip');
			}
		*/
			return $returnArr;
		}
		else
		{
		//$nextCount = 0;
		$this->statusLog($this->hisID, array('stage' => 'backupFiles', 'status' => 'processing', 'statusMsg' => 'backupSingleCallInitiated','responseParams' => $responseParams));
	
		$time = microtime(true);
		$start = $time;
		//$archive = new IWPPclZip('../archive.zip');
		global $archive;
		$archive = new IWPPclZip($backup_file);
		if($category == 'dbZip')
		{
					if(empty($get_file_list))
			{
						manual_debug('', 'beforeGettingFileList', 0);
				//define('IWP_PCLZIP_TEMPORARY_DIR', IWP_BACKUP_DIR . '/');
				$p_filedescr_list_array = $archive->getFileList(IWP_DB_DIR, IWP_PCLZIP_OPT_REMOVE_PATH, IWP_BACKUP_DIR, IWP_PCLZIP_OPT_CHUNK_BLOCK_SIZE, $file_block_size, IWP_PCLZIP_OPT_HISTORY_ID, $historyID);				//darkCode set the file block size here .. static values
						//$p_filedescr_list = $p_filedescr_list_array['p_filedescr_list'];
						//unset($p_filedescr_list_array['p_filedescr_list']);
						$next_file_index = $p_filedescr_list_array['next_file_index'];
						manual_debug('', 'afterGettingFileList', 0);
				if($p_filedescr_list_array['status'] == 'partiallyCompleted')
				{
							echo('iwpmsg fileListDBInMultiPart');
					$result_arr = array();
					$result_arr = $responseParams;
					$result_arr['nextFunc'] = 'backupFilesZIP';
					$result_arr['response_data']['p_filedescr_list'] = $p_filedescr_list;
					unset($p_filedescr_list);
					$result_arr['response_data']['next_file_index'] = $next_file_index;
					$result_arr['response_data']['complete_folder_list'] = $p_filedescr_list_array['complete_folder_list'];
					unset($p_filedescr_list_array);
					$this->statusLog($this -> hisID, array('stage' => 'gettingFileList', 'status' => 'processing', 'statusMsg' => 'gettingFileListInMultiCall','responseParams' => $result_arr));
					$resArray = array();
					$resArray['status'] = 'partiallyCompleted';
					$resArray['backupParentHID'] = $historyID;
					return $resArray;
				}
				elseif(($p_filedescr_list_array['status'] == 'error')||(!$p_filedescr_list_array))
				{
					return $this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'error', 'statusMsg' => 'Backup of files failed - Error while preparing file list', 'statusCode' => 'dbZip_backup_of_files_failed_error_while_preparing_file_list'));
				}
				elseif($p_filedescr_list_array['status'] == 'completed')
				{
					
				}
			}
		}
		else
		{
			if(empty($get_file_list))
			{
				manual_debug('', 'beforeGettingFileList', 0);
						
						//$p_filedescr_list_array = $archive->getFileList($include_data, IWP_PCLZIP_OPT_REMOVE_PATH, ABSPATH, IWP_PCLZIP_OPT_IWP_EXCLUDE, $exclude_data, IWP_PCLZIP_OPT_CHUNK_BLOCK_SIZE, $file_block_size, IWP_PCLZIP_OPT_HISTORY_ID, $historyID);  //testing	darkCode set the file block size here .. static values
				
				$p_filedescr_list_array = $archive->getFileList($include_data, IWP_PCLZIP_OPT_REMOVE_PATH, ABSPATH, IWP_PCLZIP_OPT_FILE_EXCLUDE_SIZE, $exclude_file_size, IWP_PCLZIP_OPT_IWP_EXCLUDE, $exclude_data, IWP_PCLZIP_OPT_IWP_EXCLUDE_EXT, $exclude_extensions, IWP_PCLZIP_OPT_CHUNK_BLOCK_SIZE, $file_block_size, IWP_PCLZIP_OPT_HISTORY_ID, $historyID);
				
						manual_debug('', 'afterGettingFileList', 0);
						//$p_filedescr_list = $p_filedescr_list_array['p_filedescr_list'];
						//unset($p_filedescr_list_array['p_filedescr_list']);
				$next_file_index = $p_filedescr_list_array['next_file_index'];
				
				if($p_filedescr_list_array['status'] == 'partiallyCompleted')
				{
							echo('iwpmsg fileListInMultiPart');
					$result_arr = array();
					$result_arr = $responseParams;
					$result_arr['nextFunc'] = 'backupFilesZIP';
					$result_arr['response_data']['p_filedescr_list'] = $p_filedescr_list;
					unset($p_filedescr_list);
					$result_arr['response_data']['next_file_index'] = $next_file_index;
					$result_arr['response_data']['complete_folder_list'] = $p_filedescr_list_array['complete_folder_list'];
					unset($p_filedescr_list_array);
					$this->statusLog($this -> hisID, array('stage' => 'gettingFileList', 'status' => 'processing', 'statusMsg' => 'gettingFileListInMultiCall','responseParams' => $result_arr));
					
					$resArray = array();
					$resArray['status'] = 'partiallyCompleted';
					$resArray['backupParentHID'] = $historyID;
					return $resArray;
				}
				elseif(($p_filedescr_list_array['status'] == 'error')||(!$p_filedescr_list_array))
				{
					return $this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'error', 'statusMsg' => 'Backup of files failed - Error while preparing file list', 'statusCode' => 'backup_of_files_failed_error_while_preparing_file_list'));
				}
				elseif($p_filedescr_list_array['status'] == 'completed')
				{
					
				}
			}
		}
		//usort($p_filedescr_list, "cmp");
		$p_options = array (						//darkCode static values
			77021 => true,				//tempFile ON 
			77007 => !($is_compressed),				//if we dont need to compress .. set as true
			77020 => 63082332,				//setting tempFIle threshold value here
			78999 => $file_block_size,
		);
		$v_result = 1;
		$v_header = array();
				$v_header_list = array();
				$v_nb = get_iwp_files_db_count('headers');
				$v_nb_initial = get_iwp_files_db_count('headers');
		$p_result_list = array();
		//$nextCount = 0;
		$archive->privOpenFd('wb');
		$p_filedescr_list_omitted = array();
		$omitted_flag = '';
				//$p_filedescr_list_size = sizeof($p_filedescr_list);
				//$p_filedescr_list_size = $p_filedescr_list_array['total_FL_count'];
				$p_filedescr_list_size = get_iwp_files_db_count('files');
				echo "iwpmsg loopStarted";
				echo "iwpmsg ". $p_filedescr_list_size;
				manual_debug('', 'beforeStartingLoop', 0);
		for ($j=$nextCount; ($j<$p_filedescr_list_size) && ($v_result==1); $j++) {
					
					//new method of getting fileList
					$p_filedescr_list[$j] = get_from_iwp_files_db($j);
					
			// ----- Format the filename
			$p_filedescr_list[$j]['filename'] = IWPPclZipUtilTranslateWinPath($p_filedescr_list[$j]['filename'], false);
			
			// ----- Skip empty file names
			
			// TBC : Can this be possible ? not checked in DescrParseAtt ?
			if ($p_filedescr_list[$j]['filename'] == "") {
				continue;
			}
			
			// ----- Check the filename
			if (   ($p_filedescr_list[$j]['type'] != 'virtual_file')
					&& (!file_exists($p_filedescr_list[$j]['filename']))) {
						echo 'iwpmsg FILE DOESNT EXIST';
						continue;
			}

			// ----- Look if it is a file or a dir with no all path remove option
			// or a dir with all its path removed
			//      if (   (is_file($p_filedescr_list[$j]['filename']))
			//          || (   is_dir($p_filedescr_list[$j]['filename'])
			if (   ($p_filedescr_list[$j]['type'] == 'file')
					|| ($p_filedescr_list[$j]['type'] == 'virtual_file')
					|| (   ($p_filedescr_list[$j]['type'] == 'folder')
						&& (   !isset($p_options[IWP_PCLZIP_OPT_REMOVE_ALL_PATH])
							|| !$p_options[IWP_PCLZIP_OPT_REMOVE_ALL_PATH]))
					) {
				
				// ----- Add the file
				$v_result = $archive->privAddFile($p_filedescr_list[$j], $v_header, $p_options);
						//saving the header in DB
						$cur_file = $p_filedescr_list[$j]['filename'];
						if($v_result == 1){
							$header_save_result = save_in_iwp_files_db(0, array(), $v_header, 'update', $v_nb);
						}
						else{
							$header_save_result = save_in_iwp_files_db(0, array(), array('error' => 1), 'update', $v_nb);
							$files_with_error[] = $cur_file;
						}
						if(is_array($header_save_result) && isset($header_save_result['error'])){
							return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'Zip-error: Error while updating the file "'.$cur_file.'" in the file list table.', 'statusCode' => 'zip_error_while_updating_file_list_table'));
						}
						$v_nb++;
						unset($p_filedescr_list);

				// ----- Store the file infos
						//$v_header_list[$v_nb++] = $v_header;
				$nextCount = $j+1;
				
						//unset($v_header_list[$v_nb++]);
						
				if ($v_result != 1) {
					echo 'iwpmsg Error zipping this file'.$cur_file;
					$files_with_error[] = $cur_file;
					if($v_result == -10)
					{
						return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'Zip-error: Error compressing the file "'.$cur_file.'".Try excluding this file and try again.', 'statusCode' => 'zip_error_while_compressing_file'));
					}
					continue;
					//return $v_result;
				}
			}
			
			$total_time = microtime(true) - $this->iwpScriptStartTime;
			//if(($total_time > $file_loop_break_time)||)							//darkCode static Values
			$buffer_size = $zip_split_size*1024*1024 - 3*1024*1024*$file_block_size;
			$is_new_zip = false;
			if(($total_time > $file_loop_break_time)||(iwp_mmb_get_file_size($backup_file) >= $buffer_size))
			{
				if(iwp_mmb_get_file_size($backup_file) >= $buffer_size)
				{
					$zip_split_part += 1;
					$is_new_zip = true;
				}
				break;
			}
			//iwp_mmb_print_flush("|");
			iwp_mmb_auto_print("multiCallZip");
			echo("|");
					manual_debug('', "zipLoop", 1000);
		}
				echo "iwpmsg loopEnded";
				manual_debug('', 'afterEndingLoop', 0);
		$v_offset = @ftell($archive->zip_fd);
				$size_of_v_header_list = 0;
				//$v_header_list = $p_result_list;
		//$nextCount = sizeof($p_result_list);
				manual_debug('', 'beforeStartingHeaderWrite', 0);
				$total_header_count = get_iwp_files_db_count('headers');
				//for ($i=$v_nb_initial, $v_count=$v_nb_initial; $i<sizeof($v_header_list); $i++)
				for ($i=$v_nb_initial, $v_count=0; $i<$total_header_count; $i++)
		{
					//getting header list from db
					$v_header_list[$i] = get_from_iwp_files_db($i, 'thisFileHeader');
					
			// ----- Create the file header
			if ($v_header_list[$i]['status'] == 'ok') {
				if (($v_result = $archive->privWriteCentralFileHeader($v_header_list[$i])) != 1) {
					// ----- Return
					echo 'error writing header';
					//return $v_result;
				}
				$v_count++;
			}

			// ----- Transform the header to a 'usable' info
			$archive->privConvertHeader2FileInfo($v_header_list[$i], $p_result_list[$i]);
					unset($v_header_list);
					unset($p_result_list);
					
					//manual_debug('', "duringEachHeaderWrite", 100);
		}
				manual_debug('', 'afterHeaderWrite', 0);
		$v_size = @ftell($archive->zip_fd)-$v_offset;
		$archive->privWriteCentralHeader($v_count, $v_size, $v_offset, $v_comment);
		$archive->privCloseFd();
				echo 'iwpmsg next Count -'.$nextCount;
				//if(($nextCount == sizeof($p_filedescr_list)+1)||($nextCount == sizeof($p_filedescr_list)))
				if(($nextCount == get_iwp_files_db_count('files')+1)||($nextCount == get_iwp_files_db_count('files')))
		{
			$nextCount = "completed";
			$status = "completed";
		}
		else
		{ 
			$status = "partiallyCompleted"; 
		}
				manual_debug('', 'afterWholeHeaderWrite', 0);
		$result_arr = array();
		
		//return $p_result_list;
		$result_arr['response_data']['nextCount'] = $nextCount;
		$result_arr['status'] = $status;
		$result_arr['category'] = $category;
		$result_arr['nextFunc'] = 'backupFilesZIP';
		$result_arr['response_data']['include_data'] = $include_data;							
		$result_arr['response_data']['exclude_data'] = $exclude_data;
		$result_arr['response_data']['backup_file'] = $backup_file;
		$result_arr['response_data']['backup_url'] = $backup_url;
		$result_arr['response_data']['account_info'] = $account_info;
		$result_arr['response_data']['files_with_error'] = $files_with_error; 
		$result_arr['response_data']['files_excluded_by_size'] = $files_excluded_by_size;
		$result_arr['response_data']['is_new_zip'] = $is_new_zip;
				$result_arr['response_data']['get_file_list'] = 'completed';
		//$result_arr['response_data']['p_filedescr_list'] = $p_filedescr_list;
		$result_arr['response_data']['zip_split_part'] = $zip_split_part;
		$resArray = array (
		  'responseData' => 
		  array (
			'stage' => 'backupFiles',
			'status' => 'completed',
			
		  ),
		  'parentHID' => $this -> hisID,
		  'nextFunc' => 'backupFilesZIP',
		  'status' => $status,
		  'backupParentHID' => $this -> hisID,
		  'category' => $category,
		);
		if(($nextCount == 0)&&($nextCount != 'completed'))
		{
			$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'error', 'statusMsg' => 'backupFileSingleCall'.$status, 'statusCode' => 'backup_file_single_call_error', 'responseParams' => $result_arr));
			$nextFunc = 'error';
			$status = 'error';
			return array('error' => 'Must be error');
		}
		if($status == 'partiallyCompleted')
		{
					echo 'iwpmsg filesNextCount: '.$nextCount;
					echo 'iwpmsg totalFilesCount: '.get_iwp_files_db_count('files');
			$result_arr['response_data']['p_filedescr_list'] = $p_filedescr_list;
			unset($p_filedescr_list);
			$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'completed', 'statusMsg' => 'backupFileSingleCall'.$status,'nextFunc' => 'backupFilesZIP', 'responseParams' => $result_arr));
		    unset($result_arr);
		}
		else
		{
			$main_category = $this -> getRequiredData($historyID,"category");
			if(($main_category == 'full')&&($category != 'fileZipAfterDBZip'))
			{
				//storing hash values of db-file if any
				$backup_settings_values['dbFileHashValue'][$historyID] = $this -> getHashValuesArray($p_filedescr_list);
				update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
				
				$result_arr['category'] = 'fileZipAfterDBZip';
				$result_arr['nextFunc'] = 'backupFiles';
				$resArray['response_data']['backup_file'] = $backup_file;
				$resArray['status'] = 'partiallyCompleted';				//Here am setting partiallyCompleted to continue the loop for the full(both db and files) method 
				$result_arr['status'] = 'partiallyCompleted';
				$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'partiallyCompleted', 'statusMsg' => 'backupFileSingleCall'.$status,'nextFunc' => 'backupFiles', 'responseParams' => $result_arr));
			}
			else
			{
				refresh_iwp_files_db();			//truncating table on final call.
				$paths           = array();
				$tempPaths = array();
				
				$backup_files_array = $this->get_files_array_from_iwp_part($backup_file);
				$backup_file = array();
				$backup_file = $backup_files_array;
				
				$backup_url_array = $this->get_files_array_from_iwp_part($backup_url);
				$backup_url = array();
				$backup_url = $backup_url_array;
				
				$size            = round($this->get_total_files_size($backup_file) / 1024, 2);
				if ($size > 1000) {
					$paths['size'] = round($size / 1024, 2) . " MB"; //Modified by IWP //Mb => MB
				} else {
					$paths['size'] = $size . 'KB'; //Modified by IWP //Kb => KB
				}
				$paths['backup_name'] = $task_name;
				$paths['mechanism'] = 'multicall';
				$paths['server'] = array(
						'file_path' => $backup_file,
						'file_url' => $backup_url);
				
				$paths['time'] = time();
				$paths['adminHistoryID'] = $historyID;
				$paths['files_with_error'] = $files_with_error;
				$paths['files_excluded_by_size'] = $files_excluded_by_size;
				//$paths['hashValues'] = $this -> getHashValuesArray($p_filedescr_list);
				//$paths['hashValues'] = array_merge($dbFileHashValue, $paths['hashValues']);
				unset($p_filedescr_list);
				$tempPath = $paths;
				$paths['task_results'][$historyID] = $tempPath;
				if(empty($account_info))
				{
					$tempPath['server']['dbHost'] = DB_HOST;
					$tempPath['server']['dbName'] = DB_NAME;
					$tempPath['server']['dbUser'] = DB_USER;
					$tempPath['server']['dbPassword'] = DB_PASSWORD;
					$resArray['task_results'][$historyID] = $tempPath;
					
					$result_arr['nextFunc'] = 'backupFilesZIPOver';
				}
				else
				{
					$resArray['status'] = 'partiallyCompleted';			//continuing the flow to backup_uploads 
					
					$result_arr['nextFunc'] = 'backup_uploads';
					$result_arr['status'] = 'partiallyCompleted';
					$result_arr['response_data']['backup_file'] = $backup_file;
				}
				
				$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'completed', 'statusMsg' => 'backupFileSingleCall'.$status, 'responseParams' => $result_arr,'task_result' => $paths));
				
				if((($main_category != 'files')&&($category == 'dbZip')) || ($main_category == 'db'))
				{
					@unlink(IWP_BACKUP_DIR.'/iwp_db/index.php');
					@unlink(DB_NAME);
					@rmdir(IWP_DB_DIR);
				}
				
				//verification 
				if(is_array($backup_file))
				{
					foreach($backup_file as $key => $single_backup_file)
					{
						$verification_result = $this -> postBackupVerification($archive, $single_backup_file);
						if(!$verification_result)
						{
							return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'The zip file was corrupted while zipping', 'responseParams' => $result_arr));
						}
					}
				}
				//Remove the old backups (limit)
				$removed = $this->remove_old_backups($requestParams['task_name']);
				if (is_array($removed) && isset($removed['error'])) {
					//$error_message = $removed['error'];
					return $this->statusLog($this -> hisID, array('stage' => 'removingBackupFiles', 'status' => 'error', 'statusMsg' => 'Error while removing old backups. ('.$removed['error'].')', 'statusCode' => 'remove_old_backups_error_while_removing_old_backups', 'responseParams' => $result_arr));
				}
				
			}
			
		}
		
		return $resArray;

		}
	}
	
	function get_total_files_size($backup_files)
	{
		if(is_array($backup_files))
		{
			$total_size = 0;
			foreach($backup_files as $key => $value)
			{
				$total_size += iwp_mmb_get_file_size($value);
			}
			return $total_size;
		}
		else
		{
			return iwp_mmb_get_file_size($backup_files);
		}
	}
	
	function backupFilesNext($include_data, $exclude_data, $backup_file, $backup_url, $nextCount, $p_filedescr_list = array(), $account_info = array(), $files_with_error = array(), $files_excluded_by_size = array(), $zip_split_part = 0)
	{
		$historyID = $this -> hisID;
		$is_new_zip = false;
		$backup_settings_values = $this -> backup_settings_vals;
		//$file_block_size = $backup_settings_values['file_block_size'];
		//$is_compressed = $backup_settings_values['is_compressed'];
		//$file_loop_break_time = $backup_settings_values['file_loop_break_time'];
		//$task_name = $backup_settings_values['task_name'];
		
		//get the settings by other method
		$requestParams = $this->getRequiredData($historyID, "requestParams");
		$file_block_size = $requestParams['args']['file_block_size'];			//darkcode changed
		$is_compressed = $requestParams['args']['is_compressed'];
		$file_loop_break_time = $requestParams['args']['file_loop_break_time'];
		$task_name = $requestParams['args']['backup_name'];
		$exclude_file_size = $requestParams['args']['exclude_file_size'];
		$exclude_extensions = explode(",",$requestParams['args']['exclude_extensions']);
		$zip_split_size = $requestParams['args']['zip_split_size'];
		
		if($backup_settings_values['dbFileHashValue'][$historyID])
		$dbFileHashValue = $backup_settings_values['dbFileHashValue'][$historyID];
		else
		$dbFileHashValue = array();
		
		$responseParams = $this -> getRequiredData($historyID,"responseParams");
		$category =  $responseParams['category'];                        //Am getting the category to perform the dbZip actions
	
			$get_file_list = isset($responseParams['response_data']['get_file_list']) ? $responseParams['response_data']['get_file_list'] : '';
			
			
		$this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'processing', 'statusMsg' => 'backupMultiCallInitiated', 'responseParams' => $responseParams));
		
		$time = microtime(true);
		$start = $time;
		//include_once 'pclzip.class.php';
		//include_once 'pclzip.class.split.php';
		global $archive;
		$archive = new IWPPclZip($backup_file);
		if($category == 'dbZip')
		{
			if(empty($p_filedescr_list)||($nextCount == 0))
			{
					if($get_file_list != 'completed'){
				$p_filedescr_list_array = $archive->getFileList(IWP_DB_DIR, IWP_PCLZIP_OPT_REMOVE_PATH, IWP_BACKUP_DIR, IWP_PCLZIP_OPT_CHUNK_BLOCK_SIZE, $file_block_size, IWP_PCLZIP_OPT_HISTORY_ID, $historyID);//darkCode set the file block size here .. static values
				$p_filedescr_list = $p_filedescr_list_array['p_filedescr_list'];
						$next_file_index = $p_filedescr_list_array['next_file_index'];
				
				if($p_filedescr_list_array['status'] == 'partiallyCompleted')
				{
					$result_arr = array();
					$result_arr = $responseParams;
					$result_arr['nextFunc'] = 'backupFilesZIP';
					$result_arr['response_data']['p_filedescr_list'] = $p_filedescr_list;
					$result_arr['response_data']['next_file_index'] = $p_filedescr_list_array['next_file_index'];
					$result_arr['response_data']['complete_folder_list'] = $p_filedescr_list_array['complete_folder_list'];
							unset($p_filedescr_list_array);
					$this->statusLog($this -> hisID, array('stage' => 'gettingFileList', 'status' => 'processing', 'statusMsg' => 'gettingFileListInMultiCall','responseParams' => $result_arr));
					
					$resArray = array();
					$resArray['status'] = 'partiallyCompleted';
					$resArray['backupParentHID'] = $historyID;
					return $resArray;
				}
				elseif(($p_filedescr_list_array['status'] == 'error')||(!$p_filedescr_list_array))
				{
					return $this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'error', 'statusMsg' => 'Backup of files failed - Error while preparing file list', 'statusCode' => 'backup_files_next_dbZip_backup_of_files_failed_error_while_preparing_file_list'));
				}
				elseif($p_filedescr_list_array['status'] == 'completed')
				{
					
				}
			}
		}
			}
		else
		{
			if(empty($p_filedescr_list)||($nextCount == 0))
			{
					if($get_file_list != 'completed'){
						//$p_filedescr_list_array = $archive->getFileList($include_data, IWP_PCLZIP_OPT_REMOVE_PATH, ABSPATH, IWP_PCLZIP_OPT_IWP_EXCLUDE, $exclude_data, IWP_PCLZIP_OPT_CHUNK_BLOCK_SIZE, $file_block_size, IWP_PCLZIP_OPT_HISTORY_ID, $historyID);  //testing	darkCode set the file block size here .. static values
						
						$p_filedescr_list_array = $archive->getFileList($include_data, IWP_PCLZIP_OPT_REMOVE_PATH, ABSPATH, IWP_PCLZIP_OPT_FILE_EXCLUDE_SIZE, $exclude_file_size, IWP_PCLZIP_OPT_IWP_EXCLUDE, $exclude_data, IWP_PCLZIP_OPT_IWP_EXCLUDE_EXT, $exclude_extensions, IWP_PCLZIP_OPT_CHUNK_BLOCK_SIZE, $file_block_size, IWP_PCLZIP_OPT_HISTORY_ID, $historyID);
				
				$p_filedescr_list = $p_filedescr_list_array['p_filedescr_list'];
				
				if($p_filedescr_list_array['status'] == 'partiallyCompleted')
				{
					$result_arr = array();
					$result_arr = $responseParams;
					$result_arr['nextFunc'] = 'backupFilesZIP';
					$result_arr['response_data']['p_filedescr_list'] = $p_filedescr_list;
					unset($p_filedescr_list);
					unset($p_filedescr_list_array['p_filedescr_list']);
					$result_arr['response_data']['next_file_index'] = $p_filedescr_list_array['next_file_index'];
					$result_arr['response_data']['complete_folder_list'] = $p_filedescr_list_array['complete_folder_list'];
					
					$this->statusLog($this -> hisID, array('stage' => 'gettingFileList', 'status' => 'processing', 'statusMsg' => 'gettingFileListInMultiCall','responseParams' => $result_arr));
					unset($p_filedescr_list_array);
					$resArray = array();
					$resArray['status'] = 'partiallyCompleted';
					$resArray['backupParentHID'] = $historyID;
					$resArray['isGetFileList'] = true;
					return $resArray;
				}
				elseif(($p_filedescr_list_array['status'] == 'error')||(!$p_filedescr_list_array))
				{
					return $this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'error', 'statusMsg' => 'Backup of files failed - Error while preparing file list', 'statusCode' => 'backup_files_next_p_filedescr_list_array_dbZip_backup_of_files_failed_Error_while_preparing_file_list'));
				}
				elseif($p_filedescr_list_array['status'] == 'completed')
				{
				
				}
			}
		}
			}
		$archive->privDisableMagicQuotes();
		if (($v_result=$archive->privOpenFd('rb+')) != 1)
			{
				$archive->privSwapBackMagicQuotes();
				$this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'openingFileError', 'statusCode' => 'opening_file_error'));
				return array( 'error' => 'Zip-error: Error opening file', 'error_code' => 'zip_error_opening_file');
				//return $v_result;
			}
		clearstatcache();
		// ----- Read the central directory informations
		$v_central_dir = array();
		if (($v_result = $archive->privReadEndCentralDir($v_central_dir)) != 1)
		{
				echo 'iwpmsg error2';
			$archive->privCloseFd();
			$archive->privSwapBackMagicQuotes();
			if(is_array($v_result) && !empty($v_result['error']))
			{
				return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => $v_result['error'], 'statusCode' => 'priv_read_end_central_dir_error'));
			}
			else
			{
				return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'Zip-error: Error reading end directory', 'statusCode' => 'zip_error_error_reading_end_directory'));
			}
		}

		// ----- Go to beginning of File
		@rewind($archive->zip_fd);

		// ----- Creates a temporay file
		$v_zip_temp_name = IWP_PCLZIP_TEMPORARY_DIR.uniqid('pclzip-').'.tmp';

		// ----- Open the temporary file in write mode
		if (($v_zip_temp_fd = @fopen($v_zip_temp_name, 'wb+')) == 0)
		{
			$archive->privCloseFd();
			$archive->privSwapBackMagicQuotes();
				echo 'iwpmsg error3';
			return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'Unable to open temporary file', 'statusCode' => 'unable_to_open_temporary_file'));  // ----- Return
			
		}

		// ----- Copy the files from the archive to the temporary file
		// TBC : Here I should better append the file and go back to erase the central dir
		$v_size = $v_central_dir['offset'];
		
		fseek($archive->zip_fd, $v_size, SEEK_SET);
		$actualFileSize = iwp_mmb_get_file_size($backup_file);
		
		while ($actualFileSize != 0)
		{
			$v_read_size = ($actualFileSize < IWP_PCLZIP_READ_BLOCK_SIZE ? $actualFileSize : IWP_PCLZIP_READ_BLOCK_SIZE);
			$v_buffer = fread($archive->zip_fd, $v_read_size);
			@fwrite($v_zip_temp_fd, $v_buffer, $v_read_size);
			$actualFileSize -= $v_read_size;
		}

		// ----- Swap the file descriptor
		// Here is a trick : I swap the temporary fd with the zip fd, in order to use
		// the following methods on the temporary fil and not the real archive
		/* $v_swap = $archive->zip_fd;
		$archive->zip_fd = $v_zip_temp_fd;
		$v_zip_temp_fd = $v_swap; */
		//usort($p_filedescr_list, "cmp");
		
		
		//truncate the file after just at the begining of central header
		fseek($archive->zip_fd, $v_size, SEEK_SET);
		$truncateResult = ftruncate($archive->zip_fd, $v_size);
		clearstatcache();
		
	
		$p_options = array (									//darkCode static values
			77021 => true,							//using temp method
			77007 => !($is_compressed),				//if no compression is needed set as true 
			77020 => 63082332,
		);
		$v_result = 1;
		$v_header = array();
		$p_result_list = array();
		$v_header_list = array();
			$v_nb = $v_nb_initial = get_iwp_files_db_count('headers');
		$v_comment = '';
		//$nextCount = $_REQUEST['nextCount'];
		$omitted_flag = '';
		$nextCountHere = 0;
			$p_filedescr_list_size = get_iwp_files_db_count('files');
		iwp_mmb_print_flush("loopStarted");
			echo $p_filedescr_list_size;
			manual_debug('', 'beforeStartingNextLoop', 0);
			$p_filedescr_list = array();
		for ($j=($nextCount); ($j<$p_filedescr_list_size) && ($v_result==1); $j++) {
				
				//new method of getting fileList
				$p_filedescr_list[$j] = get_from_iwp_files_db($j);
				
			// ----- Format the filename
				$p_filedescr_list[$j]['filename'] = IWPPclZipUtilTranslateWinPath($p_filedescr_list[$j]['filename'], false);

			// ----- Skip empty file names
			// TBC : Can this be possible ? not checked in DescrParseAtt ?
			if ($p_filedescr_list[$j]['filename'] == "") {
				continue;
			}

			// ----- Check the filename
			if (   ($p_filedescr_list[$j]['type'] != 'virtual_file')
					&& (!file_exists($p_filedescr_list[$j]['filename']))) {
				echo 'FILE DOESNT EXIST';
				continue;
			}

			// ----- Look if it is a file or a dir with no all path remove option
			// or a dir with all its path removed
			//      if (   (is_file($p_filedescr_list[$j]['filename']))
			//          || (   is_dir($p_filedescr_list[$j]['filename'])
			if (   ($p_filedescr_list[$j]['type'] == 'file')
					|| ($p_filedescr_list[$j]['type'] == 'virtual_file')
					|| (   ($p_filedescr_list[$j]['type'] == 'folder')
						&& (   !isset($p_options[IWP_PCLZIP_OPT_REMOVE_ALL_PATH])
							|| !$p_options[IWP_PCLZIP_OPT_REMOVE_ALL_PATH]))
					) {
				$time = microtime(true);
				$finish_part = $time;
				$total_time_part = $finish_part - $start;
				$nextCountHere = $j+1;
				/* if(($total_time_part > 2)&&($p_filedescr_list[$j]['size'] > 5000000))
				{
					$p_filedescr_list_omitted[$j] = $p_filedescr_list[$j];
					$v_nb++;
					$nextCount = $v_nb;
					$omitted_flag = 'set';
					continue;
					
				} */
				
				// ----- Add the file
					echo "|";
					//global $cur_file;
					$cur_file = $p_filedescr_list[$j]['filename'];
				$v_result = $archive->privAddFile($p_filedescr_list[$j], $v_header, $p_options);
					//saving the header in DB
					if($v_result == 1){
						$header_save_result = save_in_iwp_files_db(0, array(), $v_header, 'update', $v_nb);
					}
					else{
						$header_save_result = save_in_iwp_files_db(0, array(), array('error' => 1), 'update', $v_nb);
					}
					if(is_array($header_save_result) && isset($header_save_result['error'])){
						return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'Zip-error: Error while updating the file "'.$cur_file.'" in the file list table.', 'statusCode' => 'zip_error_while_updating_file_list_table'));
					}
					$v_nb++;
					unset($p_filedescr_list);
				// ----- Store the file infos
					//$v_header_list[$v_nb++] = $v_header;
				
				if ($v_result != 1) {
					//$this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'SomeError1'));
						echo "iwpmsg error zipping this file:".$cur_file;
						echo 'iwpmsg errorCode - '.$v_result;
						$files_with_error[] = $cur_file;
					if($v_result == -10)
					{
							return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'Zip-error: Error compressing the file "'.$cur_file.'".Try excluding this file and try again.', 'statusCode' => 'zip_error_while_compressing_file'));
					}
					continue;
					//return $v_result;
				}
			}

			$time = microtime(true);
			$finish = $time;
			$total_time = $finish - $this->iwpScriptStartTime;
			$buffer_size = $zip_split_size*1024*1024 - 3*1024*1024*$file_block_size;
			if(($total_time > $file_loop_break_time)||(iwp_mmb_get_file_size($backup_file) >= $buffer_size))			//darkCode static values
			{
				if(iwp_mmb_get_file_size($backup_file) >= $buffer_size)
				{
					$zip_split_part += 1;
					$is_new_zip = true;
						echo "iwpmsg splitting into new zip";
				}
				break;
			}
			//iwp_mmb_print_flush("|");
			iwp_mmb_auto_print("multiCallZip");
			//echo "|";
				manual_debug('', "zipLoop", 1000);
		}
			echo "iwpmsg loopEnded";
			manual_debug('', 'afterNextLoop', 0);
		$v_offset = @ftell($archive->zip_fd);
		$v_size = $v_central_dir['size'];
		/* while ($v_size != 0)
			{
				$v_read_size = ($v_size < IWP_PCLZIP_READ_BLOCK_SIZE ? $v_size : IWP_PCLZIP_READ_BLOCK_SIZE);
				$v_buffer = @fread($v_zip_temp_fd, $v_read_size);
				@fwrite($archive->zip_fd, $v_buffer, $v_read_size);
				$v_size -= $v_read_size;
			}
		 */
		clearstatcache();
		$endOfFile = iwp_mmb_get_file_size($backup_file); 
		
		
		
		//writing the header which is stored in temp file
		
		fseek($archive->zip_fd, $endOfFile, SEEK_SET);
		fseek($v_zip_temp_fd, 0, SEEK_SET);
		
		$v_buffer = fread($v_zip_temp_fd, $v_central_dir['size']);
		$writeResult = fwrite($archive->zip_fd, $v_buffer);
		
			manual_debug('', 'beforeStartingNextHeaderWrite', 0);
		
			$v_header_list = array();
		//array_pop($v_header_list);
		//$v_header_list = $p_result_list;
		// ----- Create the Central Dir files header
		$total_header_count = get_iwp_files_db_count('headers');
		for ($i=$v_nb_initial, $v_count=0; $i<$total_header_count; $i++)
		{
			//getting header list from db
			$v_header_list[$i] = get_from_iwp_files_db($i, 'thisFileHeader');
			
			// ----- Create the file header
			if ($v_header_list[$i]['status'] == 'ok') {
				if (($v_result = $archive->privWriteCentralFileHeader($v_header_list[$i])) != 1) {
						echo 'iwpmsg error4';
					fclose($v_zip_temp_fd);
					$archive->privCloseFd();
					@unlink($v_zip_temp_name);
					$archive->privSwapBackMagicQuotes();// ----- Return
					return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'Zip-error: Error while writing header', 'statusCode' => 'zip_error_while_writing_header'));
					//return $v_result;
				}
				$v_count++;
			}

			// ----- Transform the header to a 'usable' info
			$archive->privConvertHeader2FileInfo($v_header_list[$i], $p_result_list[$i]);
				unset($v_header_list);
				unset($p_result_list);
		}
			manual_debug('', 'afterNextHeaderWrite', 0);
		// ----- Calculate the size of the central header
		$v_size = @ftell($archive->zip_fd)-$v_offset;

		// ----- Create the central dir footer
		if (($v_result = $archive->privWriteCentralHeader($v_count+$v_central_dir['entries'], $v_size, $v_offset, $v_comment)) != 1)
		{
			// ----- Reset the file list
				echo 'iwpmsg error5';
			unset($v_header_list);
			$archive->privSwapBackMagicQuotes();
			return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'Zip-Error: Error while writing footer', 'statusCode' => 'zip_error_while_writing_footer'));
		}

		// ----- Swap back the file descriptor
		/* $v_swap = $archive->zip_fd;
			$archive->zip_fd = $v_zip_temp_fd;
		$v_zip_temp_fd = $v_swap; */

		// ----- Close
		$archive->privCloseFd();

		// ----- Close the temporary file
		@fclose($v_zip_temp_fd);

		// ----- Magic quotes trick
		$archive->privSwapBackMagicQuotes();

		// ----- Delete the zip file
		// TBC : I should test the result ...
		//@unlink($archive->zipname);
		@unlink($v_zip_temp_name);
		
		// ----- Rename the temporary file
		// TBC : I should test the result ...
		//@rename($v_zip_temp_name, $archive->zipname);
		//IWPPclZipUtilRename($v_zip_temp_name, $archive->zipname);
		
		$nextCount = $nextCountHere;
		
			$size_file_des = get_iwp_files_db_count('files');
		if($nextCount == $size_file_des)
		//if(true)
		{
			$nextCount = "completed";
			$status = "completed";
			
		}
		else{ 
			$status = "partiallyCompleted"; 
		}
			manual_debug('', 'afterWholeHeaderWrite', 0);
		$result_arr = array();
		$result_arr['response_data']['nextCount'] = $nextCount;
		$result_arr['status'] = $status;
		$result_arr['category'] = $category;
		$result_arr['nextFunc'] = 'backupFilesZIP';
		$result_arr['response_data']['include_data'] = $include_data;
		$result_arr['response_data']['exclude_data'] = $exclude_data;
		$result_arr['response_data']['backup_file'] = $backup_file;
		$result_arr['response_data']['backup_url'] = $backup_url;
		$result_arr['response_data']['account_info'] = $account_info;
		$result_arr['response_data']['zip_split_part'] = $zip_split_part;
		$result_arr['response_data']['is_new_zip'] = $is_new_zip;
		$result_arr['response_data']['files_with_error'] = $files_with_error; 
		$result_arr['response_data']['files_excluded_by_size'] = $files_excluded_by_size;
			$result_arr['response_data']['get_file_list'] = 'completed';
		
		//$result_arr['response_data']['p_filedescr_list'] = $p_filedescr_list;
		$resArray = array (
		  'responseData' => 
		  array (
			'stage' => 'backupFilesNext',
			'status' => 'completed',
		  ),
		  'parentHID' => $this -> hisID,
		  'nextFunc' => 'backupFilesZIP',
		  'status' => $status,
		  'backupParentHID' => $this -> hisID,
		);
		if(($nextCount == 0)&&($nextCount != 'completed'))
		{
			$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'error', 'statusMsg' => 'backupFileNextCallError'.$status, 'responseParams' => $result_arr, 'statusCode' => 'backup_file_next_call_error'));
			$nextFunc = 'error';
			$status = 'error';
			return array('error' => 'Must be error', 'error_code' => 'backup_file_next_call_error');
		}
		if($status == "partiallyCompleted")
		{
				echo 'iwpmsg filesNextCount: '.$nextCount;
				echo 'iwpmsg totalFilesCount: '.$p_filedescr_list_size;
				$result_arr['response_data']['p_filedescr_list'] = array();
			unset($p_filedescr_list);
			$this->statusLog($this -> hisID, array('stage' => 'backupFilesMultiCall', 'status' => 'completed', 'statusMsg' => 'nextCall'.$status,'nextFunc' => 'backupFilesZIP', 'responseParams' => $result_arr));
			unset($result_arr);
		}
		else
		{
			$main_category = $this -> getRequiredData($historyID,"category");
			
			//this is where the call goes to backupFiles after DB complete
			if(($main_category == 'full')&&($category != 'fileZipAfterDBZip'))
			{
				//storing hash values of db-file if any
				$backup_settings_values['dbFileHashValue'][$historyID] = $this -> getHashValuesArray($p_filedescr_list);
				update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
				
				$result_arr['category'] = 'fileZipAfterDBZip';
				$resArray['status'] = 'partiallyCompleted';
				$result_arr['nextFunc'] = 'backupFiles';
				$result_arr['status'] = 'partiallyCompleted';
				$result_arr['response_data']['get_file_list'] = '';
				$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'completed', 'statusMsg' => 'nextCall'.$status,'nextFunc' => 'backupFiles', 'responseParams' => $result_arr));
			}
			else
			{
				
				//$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'completed', 'statusMsg' => 'nextCall'.$status, 'responseParams' => $result_arr));
				refresh_iwp_files_db();			//truncating table on final call.
				$paths = array();
				$tempPaths = array();
				
				$backup_files_array = $this->get_files_array_from_iwp_part($backup_file);
				$backup_file = array();
				$backup_file = $backup_files_array;
				
				$backup_url_array = $this->get_files_array_from_iwp_part($backup_url);
				$backup_url = array();
				$backup_url = $backup_url_array;
				
				$size            = round($this->get_total_files_size($backup_file) / 1024, 2);
				if ($size > 1000) {
					$paths['size'] = round($size / 1024, 2) . " MB"; //Modified by IWP //Mb => MB
				} else {
					$paths['size'] = $size . 'KB'; //Modified by IWP //Kb => KB
				}
				$paths['backup_name'] = $task_name;
				$paths['mechanism'] = 'multicall';
				$paths['server'] = array(
						'file_path' => $backup_file,
						'file_url' => $backup_url,
						);
				
				$paths['time'] = time();
				$paths['adminHistoryID'] = $historyID;
				$paths['files_with_error'] = $files_with_error; 
				$paths['files_excluded_by_size'] = $files_excluded_by_size;
				//$paths['hashValues'] = $this -> getHashValuesArray($p_filedescr_list);
				//$paths['hashValues'] = array_merge($dbFileHashValue, $paths['hashValues']);
				unset($p_filedescr_list);
				$tempPath = $paths;
				$paths['task_results'][$historyID] = $tempPath;
				
				if(empty($account_info))
				{
					//this is where the call goes to upload after backup zip completion .. 
					$resArray['status'] = 'completed';
					
					$tempPath['server']['dbHost'] = DB_HOST;
					$tempPath['server']['dbName'] = DB_NAME;
					$tempPath['server']['dbUser'] = DB_USER;
					$tempPath['server']['dbPassword'] = DB_PASSWORD;
					
					$resArray['task_results'][$historyID] = $tempPath;
					
					$result_arr['nextFunc'] = 'backupFilesZIPOver';
					$result_arr['status'] = 'completed';
				}
				else
				{
					$resArray['actual_file_size'] = $size;  //necessary for dropbox function
					$resArray['status'] = 'partiallyCompleted';
					$result_arr['nextFunc'] = 'backup_uploads';
					$result_arr['status'] = 'partiallyCompleted';
					$result_arr['actual_file_size'] = $size;
					$result_arr['response_data']['backup_file'] = $backup_file;
				}
				
				$this->statusLog($this -> hisID, array('stage' => 'backupFiles', 'status' => 'completed', 'statusMsg' => 'nextCall'.$status, 'responseParams' => $result_arr,'task_result' => $paths));
				
				if((($main_category != 'files')&&($category == 'dbZip')) || ($main_category == 'db'))
				{
					@unlink(IWP_BACKUP_DIR.'/iwp_db/index.php');
					@unlink(DB_NAME);
					@rmdir(IWP_DB_DIR);
				}
				
				//checking zip corruption
				if(is_array($backup_file))
				{
					foreach($backup_file as $key => $single_backup_file)
					{
						$verification_result = $this -> postBackupVerification($archive, $single_backup_file);
						if(!$verification_result)
						{
							return $this->statusLog($historyID, array('stage' => 'backupFilesMultiCall', 'status' => 'error', 'statusMsg' => 'The zip file was corrupted while zipping', 'responseParams' => $result_arr));
						}
					}
				}
				
				//Remove the old backups (limit)
				$removed = $this->remove_old_backups($requestParams['task_name']);
				
				if (is_array($removed) && isset($removed['error'])) {
					return $this->statusLog($this -> hisID, array('stage' => 'removingBackupFiles', 'status' => 'error', 'statusMsg' => 'Error while removing old backups. ('.$removed['error'].')', 'responseParams' => $result_arr, 'statusCode' => 'error_while_removing_old_backups'));
				}
				
			}
		}
		return $resArray;

	}
	
	function get_files_array_from_iwp_part($backup_file)
	{
		$backup_files_array = array();
		if(strpos($backup_file, '_iwp_part') !== false)
		{
			$orgName = substr($backup_file, 0, strpos($backup_file, '_iwp_part_'));
			$totalParts = substr($backup_file, strpos($backup_file, '_iwp_part_')+10);
			$totalParts = substr($totalParts, 0, strlen($totalParts)-4);
			for($i=0; $i<=$totalParts; $i++)
			{
				if($i == 0)
				{
					$backup_files_array[] = $orgName.'.zip';
				}
				else
				{
					$backup_files_array[] = $orgName.'_iwp_part_'.$i.'.zip';
				}
			}
			return $backup_files_array;
		}
		else
		{
			$backup_files_array[] = $backup_file;
			return $backup_file;
		}
	}
	
	function postBackupVerification(&$obj, $backup_file)
	{
		$file_size = iwp_mmb_get_file_size($backup_file);
		if($file_size > 0)
		{
			$list = $obj->listContent();
			if ($list == 0) 
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			return false;
		}
	}

	function getHashValuesArray($p_filedescr_list)
	{
		$hashValues = array();
		if(is_array($p_filedescr_list))
		{
			foreach($p_filedescr_list as $key => $value)
			{
				if($value['fileHash'])
				{
					if($value['fileHash'] !== '')
					{
						$hashValues[$value['stored_filename']] = $value['fileHash'];
					}
				}
			}
		}
		return $hashValues;
	}
	
	function maybe_serialize_compress($value){
		$value = serialize($value);
		if(!function_exists('gzdeflate') || !function_exists('gzinflate')){
			return $value;
		}
		$value = gzdeflate($value);
		$value = '**ZIP**'.base64_encode($value);
		return $value;
	}
	
	function maybe_unserialize_uncompress($value){
		if(strpos($value, '**ZIP**') !== false){
			$value = gzinflate (base64_decode(str_replace('**ZIP**', '', $value)));
		}
		return unserialize($value);
	}
	
	function getRequiredData($historyID, $field){
		global $wpdb;
		$backupData = $wpdb->get_row("SELECT ".$field." FROM ".$wpdb->base_prefix."iwp_backup_status WHERE historyID = ".$historyID);
		if(($field == 'responseParams')||($field == 'requestParams')||($field == 'taskResults')){

		$fieldParams = $this->maybe_unserialize_uncompress($backupData->$field);

		}
		else
		{
			$fieldParams = $backupData->$field;
		}
		return $fieldParams;	
	}
	
	function get_all_tasks($isNeedRequestParams =false){
		/*global $wpdb;
	
		$stats = array();
		$table_name = $wpdb->base_prefix . "iwp_backup_status";
		
		$rows = $wpdb->get_col("SELECT taskResults FROM ".$table_name);
		$task_res = array();
		foreach($rows as $key => $value){
			$task_results = unserialize($value);
			if(is_array($task_results['task_results'])){
				
				foreach($task_results['task_results'] as $key => $data){
					$task_res['task_results'][$key] = $data;
				}
			}
		}*/
		
		global $wpdb;
	
		$stats = array();
		$table_name = $wpdb->base_prefix . "iwp_backup_status";
		
		$rows = $wpdb->get_results("SELECT ID, taskName, taskResults, requestParams FROM ".$table_name." ORDER BY ID DESC",  ARRAY_A);
		$this->cleanup_failed_backups($rows);
		$task_res = array();
		foreach($rows as $key => $value){
			$task_results = unserialize($value['taskResults']);
			$requestParams = unserialize($value['requestParams']);
			if(!empty($task_results['task_results'])){
				foreach($task_results['task_results'] as $key => $data){
					if ($isNeedRequestParams===true && !empty($requestParams)) {
						$task_res[$value['taskName']]['requestParams'][$key] = $requestParams;
					}else{
						$task_res[$value['taskName']]['task_results'][$key] = $data;
					}
				}
			}
		}
			
		return $task_res;
	}
	
	function cleanup_failed_backups($rows){
		$rowCount = 0;
        if (empty($rows) || !is_array($rows)) {
            return false;
        }
        foreach($rows as $key => $value){
            $task_results = unserialize($value['taskResults']);
            if(empty($task_results['task_results'])){
                if ($rowCount > 0) {
                   $this->remove_failed_backups($value['ID']);
                }
                $rowCount++;
                continue;
            }
            $rowCount++;
        }
    }

    function remove_failed_backups($ID){
        global $wpdb;
        $table_name = $wpdb->base_prefix . "iwp_backup_status";
        $delete_query = "DELETE FROM ".$table_name." WHERE ID = '".$ID."' ";
        $deleteRes = $wpdb->query($delete_query);
    }

    function remove_failed_backups_by_hisID($ID){
        global $wpdb;
        $table_name = $wpdb->base_prefix . "iwp_backup_status";
        $delete_query = "DELETE FROM ".$table_name." WHERE historyID IN (".implode(', ', $ID).") ";
        $deleteRes = $wpdb->query($delete_query);
    }

	function get_this_tasks(){
		$this->wpdb_reconnect();
		
		global $wpdb;
		$table_name = $wpdb->base_prefix . "iwp_backup_status";
		if($GLOBALS['IWP_CLIENT_HISTORY_ID'] != $this -> hisID)
		{
			$rows = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$table_name." WHERE historyID = %d", $this -> hisID), ARRAY_A);
		}
		else
		{
			$rows = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$table_name." WHERE historyID = %d", $GLOBALS['IWP_CLIENT_HISTORY_ID']), ARRAY_A);
		}
						
		return $rows;
		
	}
	
	function get_requested_task($ID){
		global $wpdb;
		$table_name = $wpdb->base_prefix . "iwp_backup_status";
				
		$rows = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$table_name." WHERE historyID = %d ORDER BY ID DESC LIMIT 1", $ID), ARRAY_A);
						
		return $rows;
		
	}
	
    function get_backup_stats()
    {
		global $wpdb;
	
		$stats = array();
		$table_name = $wpdb->base_prefix . "iwp_backup_status";
		
		$rows = $wpdb->get_results("select * from ".$table_name);
		
		$task_res = array();
		foreach($rows as $key => $value){
			$task_results = unserialize($value->taskResults);
			$task_res[$value->taskName][$value->historyID] = $task_results['task_results'][$value->historyID];
			$task_res[$value->taskName][$value->historyID]['backhack_status'] = $task_results['backhack_status'];
		}
		
				
		$stats = $task_res;
		
		return $stats;
		
	}
    

    function storeRequestParams($historyID, $requestParams)
	{
		global $wpdb;
		$update = $wpdb->update($wpdb->base_prefix.'iwp_backup_status',array( 'requestParams' => serialize($requestParams), ),array( 'historyID' => $historyID),array('%s'),array('%d'));
		
	}
	
  	function statusLog($historyID = '', $statusArray = array(), $params=array())
	{
  		global $wpdb,$insertID;
		$this->wpdb_reconnect();
		iwp_mmb_create_backup_status_table();
  		if(empty($historyID))
		{
  			$insert  = $wpdb->insert($wpdb->base_prefix.'iwp_backup_status',array( 'stage' => $statusArray['stage'], 'status' => $statusArray['status'],  'action' => $params['args']['action'], 'type' => $params['args']['type'],'category' => $params['args']['what'],'historyID' => $params['args']['parentHID'],'finalStatus' => 'pending','startTime' => microtime(true), 'lastUpdateTime' => microtime(true), 'endTime' => '','statusMsg' => $statusArray['statusMsg'],'requestParams' => serialize($params),'taskName' => $params['task_name']), array( '%s', '%s','%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s' ) );
			if($insert)
			{
				$insertID = $wpdb->insert_id; 
			}
  		}
		else if((isset($statusArray['responseParams']))||(isset($statusArray['task_result'])))
		{
			$update = $wpdb->update($wpdb->base_prefix.'iwp_backup_status',array( 'responseParams' => $this->maybe_serialize_compress($statusArray['responseParams']),'stage' => $statusArray['stage'], 'status' => $statusArray['status'],'statusMsg' => $statusArray['statusMsg'],'taskResults' =>  isset($statusArray['task_result']) ? serialize($statusArray['task_result']) : serialize(array()), 'lastUpdateTime' => microtime(true)),array( 'historyID' => $historyID),array('%s','%s', '%s', '%s','%s'),array('%d'));
		}
  		else
		{
			//$responseParams = $this -> getRequiredData($historyID,"responseParams");
			$update = $wpdb->update($wpdb->base_prefix.'iwp_backup_status',array('stage' => $statusArray['stage'], 'status' => $statusArray['status'],'statusMsg' => $statusArray['statusMsg'], 'lastUpdateTime' => microtime(true)),array( 'historyID' => $historyID),array( '%s', '%s', '%s'),array('%d'));
		}
		if( (isset($update)&&($update === false)) || (isset($insert)&&($insert === false)) )
		{
			//return array('error'=> $statusArray['statusMsg']);
			iwp_mmb_response(array('error' => 'MySQL Error: '.$wpdb -> last_error, 'error_code' => 'mysql_error_status_log'), false);
		}
		if((isset($statusArray['sendResponse']) && $statusArray['sendResponse'] == true) || $statusArray['status'] == 'completed')
		{
			$returnParams = array();
			$returnParams['parentHID'] = $historyID;
			$returnParams['backupRowID'] = $insertID;
			$returnParams['stage'] = $statusArray['stage'] ;
			$returnParams['status'] = $statusArray['status'];
			$returnParams['nextFunc'] = isset($statusArray['nextFunc']) ? $statusArray['nextFunc'] : '';
			return array('success' => $returnParams);
		}
		else
		{
			if($statusArray['status'] == 'error')
			{
					refresh_iwp_files_db();    //truncating the file list table on error
					
				$returnParams = array();
				$returnParams['parentHID'] = $historyID;
				$returnParams['backupRowID'] = $insertID;
				$returnParams['stage'] = $statusArray['stage'] ;
				$returnParams['status'] = $statusArray['status'];
				$returnParams['statusMsg'] = $statusArray['statusMsg'];
				
				return array('error'=> $statusArray['statusMsg'], 'error_code' => $statusArray['statusCode']);
			}
		}
  	}
  	
	
    function get_backup_settings()
    {
        $backup_settings = get_option('iwp_client_multi_backup_temp_values');
        if (!empty($backup_settings))
            return $backup_settings;
        else
            return false;
    }
    
    
	
	function cmp($a, $b) {
			/* if ($a['size'] == $b['size']) {
				return 0;
			}
			return ($a['size'] < $b['size']) ? -1 : 1; */
			return $a['size'] - $b['size'];
		}
     
  
	function task_now($task_name){

		 $settings = $this->tasks;
			 if(!array_key_exists($task_name,$settings)){
				return array('error' => $task_name." does not exist.", 'error_code' => 'task_name_doesnt_exists');
			 } else {
				$setting = $settings[$task_name];
			 }    
		   
		   $this->set_backup_task(array(
							'task_name' => $task_name,
							'args' => $settings[$task_name]['task_args'],
							'time' => time()
						));
		  
		  //Run backup              
		  $result = $this->backup($setting['task_args'], $task_name);
		  
		  //Check for error
		  if (is_array($result) && array_key_exists('error', $result)) {
							$this->set_backup_task(array(
								'task_name' => $task_name,
								'args' => $settings[$task_name]['task_args'],
								'error' => $result['error']
							));
			return $result;
		   } else {
			return $this->get_backup_stats();
		   }
			
	}


	/**
	 * Copies a directory from one location to another via the WordPress Filesystem Abstraction.
	 * Assumes that WP_Filesystem() has already been called and setup.
	 *
	 * @since 2.5.0
	 *
	 * @param string $from source directory
	 * @param string $to destination directory
	 * @param array $skip_list a list of files/folders to skip copying
	 * @return mixed WP_Error on failure, True on success.
	 */
	function iwp_mmb_direct_to_any_copy_dir($from, $to, $skip_list = array() ) {//$from => direct file system, $to => automatic filesystem
		global $wp_filesystem;
		
		$wp_temp_direct = new WP_Filesystem_Direct('');
		

		$dirlist = $wp_temp_direct->dirlist($from);

		$from = trailingslashit($from);
		$to = trailingslashit($to);

		$skip_regex = '';
		foreach ( (array)$skip_list as $key => $skip_file )
			$skip_regex .= preg_quote($skip_file, '!') . '|';

		if ( !empty($skip_regex) )
			$skip_regex = '!(' . rtrim($skip_regex, '|') . ')$!i';

		foreach ( (array) $dirlist as $filename => $fileinfo ) {
			if ( !empty($skip_regex) )
				if ( preg_match($skip_regex, $from . $filename) )
					continue;

			if ( 'f' == $fileinfo['type'] ) {
				if ( ! $this->iwp_mmb_direct_to_any_copy($from . $filename, $to . $filename, true, FS_CHMOD_FILE) ) {
					// If copy failed, chmod file to 0644 and try again.
					$wp_filesystem->chmod($to . $filename, 0644);
					if ( ! $this->iwp_mmb_direct_to_any_copy($from . $filename, $to . $filename, true, FS_CHMOD_FILE) )
						{
							continue;
							return new WP_Error('copy_failed', __('Could not copy file.'), $to . $filename);
						}
				}
			} elseif ( 'd' == $fileinfo['type'] ) {
				if ( !$wp_filesystem->is_dir($to . $filename) ) {
					if ( !$wp_filesystem->mkdir($to . $filename, FS_CHMOD_DIR) )
						return new WP_Error('mkdir_failed', __('Could not create directory.'), $to . $filename);
				}
				$result = $this->iwp_mmb_direct_to_any_copy_dir($from . $filename, $to . $filename, $skip_list);
				if ( is_wp_error($result) )
					return $result;
			}
		}
		return true;
	}

	function iwp_mmb_direct_to_any_copy($source, $destination, $overwrite = false, $mode = false){
		global $wp_filesystem;
		if($wp_filesystem->method == 'direct'){
			return $wp_filesystem->copy($source, $destination, $overwrite, $mode);
		}
		elseif($wp_filesystem->method == 'ftpext' || $wp_filesystem->method == 'ftpsockets'){
			if ( ! $overwrite && $wp_filesystem->exists($destination) )
				return false;
			//$content = $this->get_contents($source);
	//		if ( false === $content)
	//			return false;
				
			//put content	
			//$tempfile = wp_tempnam($file);
			$source_handle = fopen($source, 'r');
			if ( ! $source_handle )
				return false;

			//fwrite($temp, $contents);
			//fseek($temp, 0); //Skip back to the start of the file being written to
			
			$sample_content = fread($source_handle, (1024 * 1024 * 2));//1024 * 1024 * 2 => 2MB
			fseek($source_handle, 0); //Skip back to the start of the file being written to

			$type = $wp_filesystem->is_binary($sample_content) ? FTP_BINARY : FTP_ASCII;
			unset($sample_content);
			if($wp_filesystem->method == 'ftpext'){
				$ret = @ftp_fput($wp_filesystem->link, $destination, $source_handle, $type);
			}
			elseif($wp_filesystem->method == 'ftpsockets'){
				$wp_filesystem->ftp->SetType($type);
				$ret = $wp_filesystem->ftp->fput($destination, $source_handle);
			}

			fclose($source_handle);
			unlink($source);//to immediately save system space
			//unlink($tempfile);

			$wp_filesystem->chmod($destination, $mode);

			return $ret;
			
			//return $this->put_contents($destination, $content, $mode);
		}
	}

    
    
	function restore($args)
	{
		global $wpdb, $wp_filesystem;
		if (empty($args)) {
			return false;
		}
		
		extract($args);
		$this->set_resource_limit();
		
		$unlink_file = true; //Delete file after restore
		
		include_once ABSPATH . 'wp-admin/includes/file.php';
		
		//Detect source
		if ($backup_url || (isset($manualBackupFile) && !empty($manualBackupFile))) {
			//This is for clone (overwrite)
			$backup_file = array();
                        if(!$backup_url) {
                            $site_url   =   site_url();
                            $backup_url = $site_url."/".$manualBackupFile;
                        }
			$backup_url_array = $this->get_files_array_from_iwp_part($backup_url);
			if(!is_array($backup_url_array))
			{
				echo "this backup backup_url - ".$backup_url_array;
				$temp_backup_url = $backup_url_array;
				$backup_url_array = array();
				$backup_url_array[] = $temp_backup_url;
			}
			foreach($backup_url_array as $key => $single_backup_url)
			{
				$backup_file[] = download_url($single_backup_url);
				if (is_wp_error($backup_file[$key])) {
					return array(
					'error' => 'Unable to download backup file ('.$backup_file[$key]->get_error_message().')', 'error_code' => 'unable_to_download_backup_file'
					);
				}
			}
			$what = 'full';
		} else {
			//manual restore darkPrince
			
			$tasks = array();
			$task = array();
			
			$tasks = $this->get_requested_task($result_id);
			$tasks['taskResults'] = unserialize($tasks['taskResults']);
			
			$backup = $tasks['taskResults']['task_results'][$result_id];				//darkCode testing purpose
			$hashValues = $backup['hashValues'];
			//$backup = $tasks['taskResults'];
			$requestParams = unserialize($tasks['requestParams']);
			$args = $requestParams['account_info'];
			//$task = $tasks['Backup Now'];
			if (isset($backup['server'])) {
				$backup_file = $backup['server']['file_path'];
				$unlink_file = false; //Don't delete file if stored on server
			}
			elseif (isset($backup['ftp'])) {
				$ftp_file            = $backup['ftp'];
				$args                = $args['iwp_ftp'];
				if(!is_array($ftp_file))
				{
					$ftp_file = array();
					$ftp_file[0] = $backup['ftp'];
					$backup_file = array();
				}
				foreach($ftp_file as $key => $value)
				{
					$args['backup_file'] = $value;
					iwp_mmb_print_flush('FTP download: Start '.$key);
					$backup_file[]         = $this->get_ftp_backup($args);
					iwp_mmb_print_flush('FTP download: End '.$key);
					if ($backup_file[$key] == false) {
						return array(
						'error' => 'Failed to download file from FTP.', 'error_code' => 'failed_to_download_file_from_ftp'
						);
					}
				}
			}
			elseif (isset($backup['amazons3'])) {
				$amazons3_file       = $backup['amazons3'];
				$args                = $args['iwp_amazon_s3'];
				if(!is_array($amazons3_file))
				{
					$amazons3_file = array();
					$amazons3_file[0] = $backup['amazons3'];
					$backup_file = array();
				}
				foreach($amazons3_file as $key => $value)
				{
					$args['backup_file'] = $value;
					iwp_mmb_print_flush('Amazon S3 download: Start '.$key);
					if(is_new_s3_compatible()){
						require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/s3IWPBackup.php';
						$new_s3_obj = new IWP_MMB_S3_MULTICALL();
						$backup_file[]         = $new_s3_obj->get_amazons3_backup($args);
					}
					else{
						$backup_file[]         = $this->get_amazons3_backup_bwd_comp($args);
					}
					iwp_mmb_print_flush('Amazon S3 download: End '.$key);
					if ($backup_file[$key] == false) {
						return array(
						'error' => 'Failed to download file from Amazon S3.', 'error_code' => 'failed_to_download_file_from_s3'
						);
					}
					else if(is_array($backup_file[$key]) && isset($backup_file[$key]['error'])){
						return array(
						'error' => 'Failed to download file from Amazon S3. Please enable curl first.', 'error_code' => 'failed_to_download_file_from_s3_enable_curl'
						);
					}
				}
			} 
			elseif(isset($backup['dropbox'])){
				$dropbox_file       = $backup['dropbox'];
				$args                = $args['iwp_dropbox'];
				if(!is_array($dropbox_file))
				{
					$dropbox_file = array();
					$dropbox_file[0] = $backup['dropbox'];
					$backup_file = array();
				}
				foreach($dropbox_file as $key => $value)
				{
					$args['backup_file'] = $value;
					iwp_mmb_print_flush('Dropbox download: Start '.$key);
					$backup_file[]         = $this->get_dropbox_backup($args);
					iwp_mmb_print_flush('Dropbox download: End '.$key);
					if ($backup_file[$key] == false) {
						return array(
						'error' => 'Failed to download file from Dropbox.', 'error_code' => 'failed_to_download_file_from_dropbox'
						);
					}
					else if(is_array($backup_file[$key]) && isset($backup_file[$key]['error'])){
						return array(
						'error' => 'Failed to download file from Dropbox. Please enable curl first.', 'error_code' => 'failed_to_download_file_from_dbox_enable_curl'
						);
					}
				}
				
			}
			elseif(isset($backup['gDrive'])){
            	$gdrive_file       = $backup['gDrive'];
                $args                = $args['iwp_gdrive'];
				
				if(!is_array($gdrive_file))
				{
					$gdrive_file = array();
					$gdrive_file[0] = $backup['gDrive'];
					$backup_file = array();
				}
				foreach($gdrive_file as $key => $value)
				{
					$args['backup_file'] = $value;
					iwp_mmb_print_flush('gDrive download: Start');
					$backup_file[]         = $this->get_google_drive_backup($args);
					iwp_mmb_print_flush('gDrive download: End');
					
					if(is_array($backup_file[$key]) && array_key_exists('error', $backup_file[$key]))
					{
						return $backup_file[$key];
					}

					if ($backup_file[$key] == false) {
						return array(
							'error' => 'Failed to download file from gDrive.'
						);
					}
				}
            }
			
			//$what = $tasks[$task_name]['task_args']['what'];
			$what = $requestParams['args']['what'];
		}
		
		
		
		$this->wpdb_reconnect();
		
		/////////////////// dev ////////////////////////
		
        if (!$this->is_server_writable()) {
            return array(
	            'error' => 'Failed, please add FTP details', 'error_code' => 'failed_please_add_ftp_details'
            );
        }

		$url = wp_nonce_url('index.php?page=iwp_no_page','iwp_fs_cred');
		ob_start();
		if (false === ($creds = request_filesystem_credentials($url, '', false, ABSPATH, null) ) ) {
			return array(
			'error' => 'Unable to get file system credentials', 'error_code' => 'unable_to_get_file_system_credentials'
			);   // stop processing here
		}
		ob_end_clean();
		
		if ( ! WP_Filesystem($creds, ABSPATH) ) {
			//request_filesystem_credentials($url, '', true, false, null);
			return array(
			'error' => 'Unable to initiate file system. Please check you have entered valid FTP credentials.', 'error_code' => 'unable_to_initiate_file_system'
			);   // stop processing here
			//return;
		}
		
		require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');//will be used to copy from temp directory
		
		// do process
		//$temp_dir = get_temp_dir();
		$temp_dir = IWP_PCLZIP_TEMPORARY_DIR;
		
		
		
		if(file_exists(IWP_PCLZIP_TEMPORARY_DIR) && is_dir(IWP_PCLZIP_TEMPORARY_DIR))
		{
			//
		}
		else
		{
			if(file_exists(dirname(IWP_PCLZIP_TEMPORARY_DIR)) && is_dir(dirname(IWP_PCLZIP_TEMPORARY_DIR))){
				@mkdir(IWP_PCLZIP_TEMPORARY_DIR, 0755, true);
			}
			else{
				@mkdir(dirname(IWP_PCLZIP_TEMPORARY_DIR), 0755, true);
				@mkdir(IWP_PCLZIP_TEMPORARY_DIR, 0755, true);
			}
			
		}
		if(is_writable(IWP_PCLZIP_TEMPORARY_DIR))
		{
			@file_put_contents(IWP_PCLZIP_TEMPORARY_DIR . '/index.php', ''); //safe	
		}
		else
		{
			$chmod = chmod(IWP_PCLZIP_TEMPORARY_DIR, 777);
			if(is_writable(IWP_PCLZIP_TEMPORARY_DIR)){
				@file_put_contents(IWP_PCLZIP_TEMPORARY_DIR . '/index.php', ''); //safe		
			}
	
		}
		
		if(is_writable(IWP_PCLZIP_TEMPORARY_DIR))
		{
			$temp_dir = IWP_PCLZIP_TEMPORARY_DIR;
		}
		else{
			$temp_dir = get_temp_dir();
			if(!is_writable($temp_dir)){
				return array(
							'error' => 'Temporary directory is not writable. Please set 777 permission for '.IWP_PCLZIP_TEMPORARY_DIR.' and try again.', 'error_code' => 'pclzip_temp_dir_not_writable_please_set_777'
							);
			}
		}
		
		
		
		$new_temp_folder = untrailingslashit($temp_dir);
		$temp_uniq = md5(microtime(1));//should be random
		while (is_dir($new_temp_folder .'/'. $temp_uniq )) {
			$temp_uniq = md5(microtime(1));
		}
		$new_temp_folder = trailingslashit($new_temp_folder .'/'. $temp_uniq);
		$is_dir_created = mkdir($new_temp_folder);// new folder should be empty
		if(!$is_dir_created){
			return array(
			'error' => 'Unable to create a temporary directory.', 'error_code' => 'unable_to_create_temporary_directory'
			);
		}
		
		
		$remote_abspath = $wp_filesystem->abspath();
		if(!empty($remote_abspath)){
			$remote_abspath = trailingslashit($remote_abspath);	
		}else{
			return array(
			'error' => 'Unable to locate WP root directory using file system.', 'error_code' => 'unable_to_locate_wp_root_directory_using_file_system'
			);
		}
		
		//global $wp_filesystem;
		//		$wp_filesystem->put_contents(
		//		  '/tmp/example.txt',
		//		  'Example contents of a file',
		//		  FS_CHMOD_FILE // predefined mode settings for WP files
		//		);
		
		/////////////////// dev ////////////////////////
		
		//if ($backup_file && file_exists($backup_file)) {
		if ($backup_file) {
			if ($overwrite) 
			{   //clone only fresh or existing to existing
				//Keep old db credentials before overwrite
				if (!$wp_filesystem->copy($remote_abspath . 'wp-config.php', $remote_abspath . 'iwp-temp-wp-config.php', true)) {
					if($unlink_file)
					{
						if(!is_array($backup_file))
						{
							$temp_backup_file = $backup_file;
							$backup_file = array();
							$backup_file = $temp_backup_file;
						}
						foreach($backup_file as $k => $value)
						{
							@unlink($value);
						}
					}
					return array(
					'error' => 'Error creating wp-config. Please check your write permissions.', 'error_code' => 'error_creating_wp_config'
					);
				}
				
				$db_host     = DB_HOST;
				$db_user     = DB_USER;
				$db_password = DB_PASSWORD;
				$home        = rtrim(get_option('home'), "/");
				$site_url    = get_option('site_url');

				$clone_options                       = array();
				if (trim($clone_from_url) || trim($iwp_clone) || trim($maintain_old_key)) {
					
					$clone_options['iwp_client_nossl_key']  = get_option('iwp_client_nossl_key');
					$clone_options['iwp_client_public_key'] = get_option('iwp_client_public_key');
					$clone_options['iwp_client_action_message_id'] = get_option('iwp_client_action_message_id');
					
				}
				
				//$clone_options['iwp_client_backup_tasks'] = serialize(get_option('iwp_client_multi_backup_temp_values'));
				$clone_options['iwp_client_notifications'] = serialize(get_option('iwp_client_notifications'));
				$clone_options['iwp_client_pageview_alerts'] = serialize(get_option('iwp_client_pageview_alerts'));
				
				$qry = "SELECT * FROM ".$wpdb->base_prefix."iwp_backup_status";
				$clone_options['iwp_client_backup_tasks'] = $wpdb->get_results($qry, ARRAY_A);
				
				/*if(!$clone_options['iwp_client_backup_tasks'])
				{
					return array(
					'error' => 'Unable to restore clone options.'
					);
				}*/
				
			}
			else {
				$restore_options                       = array();
				$restore_options['iwp_client_notifications'] = serialize(get_option('iwp_client_notifications'));
				$restore_options['iwp_client_pageview_alerts'] = serialize(get_option('iwp_client_pageview_alerts'));
				$restore_options['iwp_client_user_hit_count'] = serialize(get_option('iwp_client_user_hit_count'));
				//$restore_options['iwp_client_backup_tasks'] = serialize(get_option('iwp_client_multi_backup_temp_values'));
				
				$qry = "SELECT * FROM ".$wpdb->base_prefix."iwp_backup_status";
				$restore_options['iwp_client_backup_tasks'] = $wpdb->get_results($qry, ARRAY_A);
				
				/*if(!$restore_options['iwp_client_backup_tasks'])
				{
					return array(
					'error' => 'Unable to restore options.'
					);
				}*/
				
			}
						
			//Backup file will be extracted to a temporary path
			if(!is_array($backup_file))
			{
				$temp_backup_file = $backup_file;
				$backup_file = array();
				$backup_file[0] = $temp_backup_file;
			}
			foreach($backup_file as $single_backup_file)
			{
				echo "this backup file - ".$single_backup_file;
				//chdir(ABSPATH);
				$unzip   = $this->get_unzip();
				$command = "$unzip -o $single_backup_file -d $new_temp_folder";
				iwp_mmb_print_flush('ZIP Extract CMD: Start');
				ob_start();
				$result = $this->iwp_mmb_exec($command); 
				//$result = false;
				ob_get_clean();
				iwp_mmb_print_flush('ZIP Extract CMD: End');
				
				if (!$result) { //fallback to pclzip
					////define('IWP_PCLZIP_TEMPORARY_DIR', IWP_BACKUP_DIR . '/');
					//require_once ABSPATH . '/wp-admin/includes/class-pclzip.php';
					//require_once $GLOBALS['iwp_mmb_plugin_dir'].'/pclzip.class.php';
					iwp_mmb_print_flush('ZIP Extract PCL: Start');
					$archive = new IWPPclZip($single_backup_file);
					$result  = $archive->extract(IWP_PCLZIP_OPT_PATH, $new_temp_folder, IWP_PCLZIP_OPT_REPLACE_NEWER);
					iwp_mmb_print_flush('ZIP Extract PCL: End');
				}
				
				$this->wpdb_reconnect();
				if ($unlink_file) {
					@unlink($single_backup_file);
				}
				
				if (!$result) {
					if ($unlink_file) {
						foreach($backup_file as $single_file)
						{
							@unlink($single_file);
						}
					}
					return array(
					'error' => 'Failed to unzip files. pclZip error (' . $archive->error_code . '): .' . $archive->error_string, 'error_code' => 'failed_to_unzip_files'
					);
				}
				
			}
			
			//appending files if split is done
			$joinedFilesArray = $this -> appendSplitFiles($new_temp_folder);
			//$compareHashValuesArray = $this -> compareHashValues($joinedFilesArray['orgHash'], $joinedFilesArray['afterSplitHash']);
			
			//do the restore db part only if the category is full or db .. else skip it for files alone concept
			if(($what == 'full')||($what == 'db'))
			{
				$db_result = $this->restore_db($new_temp_folder); 
				
				if (!$db_result) {
					return array(
					'error' => 'Error restoring database.', 'error_code' => 'error_restoring_database'
					);
				} else if(is_array($db_result) && isset($db_result['error'])){
					return array(
					'error' => $db_result['error']
					);
				}
			}
			
		}
		else {
			return array(
			'error' => 'Backup file not found.', 'error_code' => 'backup_file_not_found'
			);
		}
		$bError = error_get_last();
		
		
		//copy files from temp to ABSPATH
		$copy_result = $this->iwp_mmb_direct_to_any_copy_dir($new_temp_folder, $remote_abspath);
		
		if ( is_wp_error($copy_result) ){
			$wp_temp_direct2 = new WP_Filesystem_Direct('');
			$wp_temp_direct2->delete($new_temp_folder, true);
			return $copy_result;
		}
		
		
		$this->wpdb_reconnect();
		
		
		
		//Replace options and content urls
		if ($overwrite) {//fresh WP package or existing to existing site
			//Get New Table prefix
			$new_table_prefix = trim($this->get_table_prefix());
			//Retrieve old wp_config
			//@unlink(ABSPATH . 'wp-config.php');
			$wp_filesystem->delete($remote_abspath . 'wp-config.php', false, 'f');
			//Replace table prefix
			//$lines = file(ABSPATH . 'iwp-temp-wp-config.php');
			$lines = $wp_filesystem->get_contents_array($remote_abspath . 'iwp-temp-wp-config.php');
			
			$new_lines = '';
			foreach ($lines as $line) {
				if (strstr($line, '$table_prefix')) {
					$line = '$table_prefix = "' . $new_table_prefix . '";' . PHP_EOL;
				}
				$new_lines .= $line;
				//file_put_contents(ABSPATH . 'wp-config.php', $line, FILE_APPEND);
			}
			
			$wp_filesystem->put_contents($remote_abspath . 'wp-config.php', $new_lines);
			
			//@unlink(ABSPATH . 'iwp-temp-wp-config.php');
			$wp_filesystem->delete($remote_abspath . 'iwp-temp-wp-config.php', false, 'f');
			
			//Replace options
			$query = "SELECT option_value FROM " . $new_table_prefix . "options WHERE option_name = 'home'";
			$old   = $wpdb->get_var($query);
			$old   = rtrim($old, "/");
			$query = "UPDATE " . $new_table_prefix . "options SET option_value = %s WHERE option_name = 'home'";
			$wpdb->query($wpdb->prepare($query, $home));
			$query = "UPDATE " . $new_table_prefix . "options  SET option_value = %s WHERE option_name = 'siteurl'";
			$wpdb->query($wpdb->prepare($query, $home));
			//Replace content urls
			
			$regexp1 = 'src="(.*)'.$old.'(.*)"';
			$regexp2 = 'href="(.*)'.$old.'(.*)"';
			$query = "UPDATE " . $new_table_prefix . "posts SET post_content = REPLACE (post_content, %s,%s) WHERE post_content REGEXP %s OR post_content REGEXP %s";
			$wpdb->query($wpdb->prepare($query, $old, $home, $regexp1, $regexp2));
			
			if (trim($new_password)) {
				$new_password = wp_hash_password($new_password);
			}
			if (!trim($clone_from_url) && !trim($iwp_clone)) {
				if ($new_user && $new_password) {
					$query = "UPDATE " . $new_table_prefix . "users SET user_login = %s, user_pass = %s WHERE user_login = %s";
					$wpdb->query($wpdb->prepare($query, $new_user, $new_password, $old_user));
				}
			} else {
				
				// if ($iwp_clone) {
				if ($admin_email) {
					//Clean Install
					$query = "UPDATE " . $new_table_prefix . "options SET option_value = %s WHERE option_name = 'admin_email'";
					$wpdb->query($wpdb->prepare($query, $admin_email));
					$query     = "SELECT * FROM " . $new_table_prefix . "users LIMIT 1";
					$temp_user = $wpdb->get_row($query);
					if (!empty($temp_user)) {
						$query = "UPDATE " . $new_table_prefix . "users SET user_email=%s, user_login = %s, user_pass = %s WHERE user_login = %s";
						$wpdb->query($wpdb->prepare($query, $admin_email, $new_user, $new_password, $temp_user->user_login));
					}
					
				}
				// }
				
				//if ($clone_from_url) {
				if ($new_user && $new_password) {
					$query = "UPDATE " . $new_table_prefix . "users SET user_pass = %s WHERE user_login = %s";
					$wpdb->query($wpdb->prepare($query, $new_password, $new_user));
				}
				// }
				
			}
			
			if (is_array($clone_options) && !empty($clone_options)) {
				
				$GLOBALS['table_prefix'] = $new_table_prefix;
				
				$this->clone_restore_options($clone_options);
                                
                                if(!empty($clone_options['iwp_client_nossl_key'])){
                                    
                                    $query     = "SELECT * FROM " . $new_table_prefix . "options WHERE option_name = 'iwp_client_nossl_key'";
                                    $temp_row = $wpdb->get_row($query);
                                    if (!empty($temp_row)) {
                                        $query = "UPDATE " . $new_table_prefix . "options SET option_value = %s WHERE option_name = 'iwp_client_nossl_key'";
                                        $wpdb->query($wpdb->prepare($query, $clone_options['iwp_client_nossl_key']));
                                    } else {
                                        $insert  = $wpdb->insert($new_table_prefix."options",array( 'option_name' => 'iwp_client_nossl_key', 'option_value' => $clone_options['iwp_client_nossl_key'],  'autoload' => 'yes'), array( '%s', '%s','%s') );
                                    }
                                }
                                
                                if(!empty($clone_options['iwp_client_public_key'])){
                                    
                                    $query     = "SELECT * FROM " . $new_table_prefix . "options WHERE option_name = 'iwp_client_public_key'";
                                    $temp_row = $wpdb->get_row($query);
                                    if (!empty($temp_row)) {
                                        $query = "UPDATE " . $new_table_prefix . "options SET option_value = %s WHERE option_name = 'iwp_client_public_key'";
                                        $wpdb->query($wpdb->prepare($query, $clone_options['iwp_client_public_key']));
                                    } else {
                                        $insert  = $wpdb->insert($new_table_prefix."options",array( 'option_name' => 'iwp_client_public_key', 'option_value' => $clone_options['iwp_client_public_key'],  'autoload' => 'yes'), array( '%s', '%s','%s') );
                                    }
                                }
			}
                        $query     = "SELECT * FROM " . $new_table_prefix . "users LIMIT 1";
                        $temp_user = $wpdb->get_row($query);
                        $new_user = $temp_user->user_login;
			
			//Remove hit count
			$query = "DELETE FROM " . $new_table_prefix . "options WHERE option_name = 'iwp_client_user_hit_count'";
			$wpdb->query($query);
			
			//Check for .htaccess permalinks update
			$this->replace_htaccess($home, $remote_abspath);
		}
		else {
			//restore client options
			if (is_array($restore_options) && !empty($restore_options)) {
				
				$GLOBALS['table_prefix'] = $wpdb->base_prefix;
				$this->clone_restore_options($restore_options);				
			}
		}
		
		//clear the temp directory
		$wp_temp_direct2 = new WP_Filesystem_Direct('');
		$wp_temp_direct2->delete($new_temp_folder, true);
		
		return !empty($new_user) ? $new_user : true ;
	}
	
	
	function clone_restore_options($clone_restore_options){
		global $wpdb;
		
		$table = $GLOBALS['table_prefix'].'iwp_backup_status';
		$wpdb->query("SHOW TABLES LIKE '".$table."'");
		if($wpdb->num_rows  == 1){
			$delete = $wpdb->query("DROP TABLE '".$table."' ");
		}
			
		iwp_mmb_backup_db_changes();
		
		if(!empty($clone_restore_options['iwp_client_backup_tasks'])){
			$this->insertBackupStatusContens($clone_restore_options['iwp_client_backup_tasks']);
		}
		
		return true;
	}
	
	
	function insertBackupStatusContens($dataContent){
		global $wpdb;		
		
		$table = $GLOBALS['table_prefix'].'iwp_backup_status';
		if(!empty($dataContent)){
			foreach($dataContent as $key => $value){
				$insert  = $wpdb->insert($table,array( 'ID' => $value['stage'], 'historyID' => $value['historyID'],  'taskName' => $value['taskName'], 'action' => $value['action'],'type' => $value['type'], 'category' => $value['category'], 'stage' => $value['stage'],'status' => $value['status'],'finalStatus' => $value['finalStatus'],'statusMsg' => $value['statusMsg'],'requestParams' => $value['requestParams'],'responseParams' => $value['responseParams'], 'taskResults' => $value['taskResults'], 'startTime' => $value['startTime'], 'endTime' => $value['endTime']), array( '%d', '%d','%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' ) );
				
			}
		}
	}
	
	function compareHashValues($joinedFilesArray, $hashValues)
	{
		$filesWithChangedHash = array();
		foreach($hashValues as $key => $value)
		{
			foreach($joinedFilesArray as $k => $v)
			{
				
				$pos = strpos($k, $key);
				if($pos !== false)
				{
					if($value != $v)
					{
						$filesWithChangedHash[$k] = $key;
					}
					break;
				}
			}
		}
		return $filesWithChangedHash;
	}
	
    function appendSplitFiles($fileToAppend)
	{
		// function to join the split files during multicall backup
		$directory_tree = get_all_files_from_dir($fileToAppend);
		usort($directory_tree, array($this, "sortString"));
		
		$joinedFilesArray = array();
		$orgHashValues = array();
		$hashValue = '';
		
		foreach($directory_tree as $k => $v)
		{
			$contents = '';
			$orgFileCount = 0;
			/* $subject = $v;
			$pattern = '/iwp_part/i';
			preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE);
			print_r($matches); */
			$pos = strpos($v, 'iwp_part');
			if($pos !== false)
			{
				$currentFile = explode(".",$v);
				$currentFileSize = count($currentFile);
				foreach($currentFile as $key => $val)
				{
					if(($key == ($currentFileSize-2))||($currentFileSize == 1))
					{
						$insPos = strpos($val, '_iwp_part');
						$rest = substr_replace($val, '', $insPos);
						$currentFile[$key] = $rest;
						
						$insPos2 = strpos($rest, '_iwp_hash');
						if($insPos2 !== false)
						{
							$hashInitialPoint = strrpos($rest, "_iwp_hash");  
							$hashValue = substr($rest, $hashInitialPoint+10);
							//$hashValue = substr($rest, -32);
							$rest = substr_replace($rest, '', $insPos2);
							$currentFile[$key] = $rest;
						}
					}
				}
				$orgFileCount++;	
				$orgFileName = implode(".", $currentFile);
				$handle = fopen($v,"r");
				$contents = fread($handle, iwp_mmb_get_file_size($v));
				fclose($handle);
				if($orgFileCount == 1)
				{
					//clearing contents of file intially to prevent appending to already existing file
					//file_put_contents($orgFileName,'',FILE_APPEND);
				}
				file_put_contents($orgFileName,$contents,FILE_APPEND);
				$joinedFilesArray[$orgFileName] = 'hash';
				$orgHashValues[$orgFileName] = $hashValue;
				echo " orgFileName - ".$orgFileName;
				$file_to_ulink = realpath($v);
				$resultUnlink = unlink($file_to_ulink);
				$resultUnlink = error_get_last();
				if(!$resultUnlink)
				{
					if(is_file($v))
					{
						unlink($file_to_ulink);
					}
				}
		
				
			}
		}
		$hashValues = array();
		foreach($joinedFilesArray as $key => $value)
		{
			//$hashValues[$key] = md5_file($key);
			$hashValues[$key] = 'hash';
		}
		$totalHashValues = array();
		$totalHashValues['orgHash'] = $orgHashValues;
		$totalHashValues['afterSplitHash'] = $hashValues;
		return $totalHashValues;
	}
	
	function sortString($a, $b)
	{
		// the uSort CallBack Function used in the appendSplitFiles function
		$stringArr = array();
		$stringArr[0] = $a;
		$stringArr[1] = $b;
		$strA = '';
		$strB = '';
		foreach($stringArr as $strKey => $strVal)
		{
			$mystring = $strVal;
			$findme = '_iwp_part';																		//fileNameSplit logic
			$pos = strpos($mystring, $findme);
			$rest = substr($mystring, $pos);
			$pos2 = strrpos($rest, $findme);
			$len = strlen($rest);
			$actLen = $pos2+strlen($findme);
			$actPos = $len - $actLen -1;
			$actPartNum = substr($rest, -($actPos));
			$actPartNumArray = explode(".",$actPartNum);
			foreach($actPartNumArray as $key => $val)
			{
				if($key == 0)
				$actPartNum = $val;
			}
			if($strKey == 0)
			$strA = intval($actPartNum);
			else
			$strB = intval($actPartNum);
		}
		if ($strA == $strB){return 0;}
		return ($strA < $strB) ? -1 : 1;	
	}
	
    function restore_db($new_temp_folder)
    {
        global $wpdb;
        $paths     = $this->check_mysqli_paths();
        $file_path = $new_temp_folder . '/iwp_db';
        @chmod($file_path,0755);
        $file_name = glob($file_path . '/*.sql');
        $file_name = $file_name[0];
        
        if(!$file_name){
        	return array('error' => 'Cannot access database file.');
        }
        
        $brace     = (substr(PHP_OS, 0, 3) == 'WIN') ? '"' : '';
        $command   = $brace . $paths['mysql'] . $brace . ' --host="' . DB_HOST . '" --user="' . DB_USER . '" --password="' . DB_PASSWORD . '" --default-character-set="utf8" ' . DB_NAME . ' < ' . $brace . $file_name . $brace;
        iwp_mmb_print_flush('DB Restore CMD: Start'); 
        ob_start();
        $result = $this->iwp_mmb_exec($command);
        ob_get_clean();
		iwp_mmb_print_flush('DB Restore CMD: End'); 
        if (!$result) {
            //try php
            $this->restore_db_php($file_name);
        }
        
        
        @unlink($file_name);
		@unlink(dirname($file_name).'/index.php');
		@rmdir(dirname($file_name));//remove its folder
        return true;
    }
    
	
	
	
    function restore_db_php($file_name)
    {
        
		$this->wpdb_reconnect();
		global $wpdb;
		
		$wpdb->query("SET NAMES 'utf8'");
		
        $current_query = '';
        // Read in entire file
        $lines         = file($file_name);
        // Loop through each line
		if(!empty($lines)){
			foreach ($lines as $line) {
				iwp_mmb_auto_print('restore_db_php');
				// Skip it if it's a comment
				if(substr($line, 0, 2) == '--' || $line == '' || substr($line, 0, 3) == '/*!')
					continue;
				
				// Add this line to the current query
				$current_query .= $line;
				// If it has a semicolon at the end, it's the end of the query
				if (substr(trim($line), -1, 1) == ';') {
					// Perform the query
					$result = $wpdb->query($current_query);
					if ($result === false)
						return false;
					// Reset temp variable to empty
					$current_query = '';
				}
			}
		}
        
        return true;
    }
    
    function get_table_prefix()
    {
        $lines = file(ABSPATH . 'wp-config.php');
        foreach ($lines as $line) {
            if (strstr($line, '$table_prefix')) {
                $pattern = "/(\'|\")[^(\'|\")]*/";
                preg_match($pattern, $line, $matches);
                $prefix = substr($matches[0], 1);
                return $prefix;
                break;
            }
        }
        return 'wp_'; //default
    }
    
    function optimize_tables()
    {
        global $wpdb;
        $query  = 'SHOW TABLE STATUS';
        $tables = $wpdb->get_results($query, ARRAY_A);
        foreach ($tables as $table) {
            if (in_array($table['Engine'], array(
                'MyISAM',
                'ISAM',
                'HEAP',
                'MEMORY',
                'ARCHIVE'
            )))
                $table_string .= $table['Name'] . ",";
            elseif ($table['Engine'] == 'InnoDB') {
                $optimize = $wpdb->query("ALTER TABLE {$table['Name']} ENGINE=InnoDB");
            }
        }
        
        if(!empty($table_string)){
			$table_string = rtrim($table_string, ',');
        $optimize     = $wpdb->query("OPTIMIZE TABLE $table_string");
		}
        
        return $optimize ? true : false;
    }
    
    ### Function: Auto Detect MYSQL and MYSQL Dump Paths
    function check_mysqli_paths()
    {
        global $wpdb;
        $paths = array(
            'mysql' => '',
            'mysqldump' => ''
        );
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $mysql_install = $wpdb->get_row("SHOW VARIABLES LIKE 'basedir'");
            if ($mysql_install) {
                $install_path       = str_replace('\\', '/', $mysql_install->Value);
                $paths['mysql']     = $install_path . 'bin/mysql.exe';
                $paths['mysqldump'] = $install_path . 'bin/mysqldump.exe';
            } else {
                $paths['mysql']     = 'mysql.exe';
                $paths['mysqldump'] = 'mysqldump.exe';
            }
        } else {
            $paths['mysql'] = $this->iwp_mmb_exec('which mysql', true);
            if (empty($paths['mysql']))
                $paths['mysql'] = 'mysql'; // try anyway
            
            $paths['mysqldump'] = $this->iwp_mmb_exec('which mysqldump', true);
            if (empty($paths['mysqldump']))
                $paths['mysqldump'] = 'mysqldump'; // try anyway         
            
        }
        
        
        return $paths;
    }
    
    //Check if exec, system, passthru functions exist
    function check_sys()
    {
        if ($this->iwp_mmb_function_exists('exec'))
            return 'exec';
        
        if ($this->iwp_mmb_function_exists('system'))
            return 'system';
        
        if ($this->iwp_mmb_function_exists('passhtru'))
            return 'passthru';
        
        return false;
        
    }
    
    function iwp_mmb_exec($command, $string = false, $rawreturn = false)
    {
        if ($command == '')
            return false;
        
        if ($this->iwp_mmb_function_exists('exec')) {
            $log = @exec($command, $output, $return);
            
            if ($string)
                return $log;
            if ($rawreturn)
                return $return;
            
            return $return ? false : true;
        } elseif ($this->iwp_mmb_function_exists('system')) {
            $log = @system($command, $return);
            
            if ($string)
                return $log;
            
            if ($rawreturn)
                return $return;
            
            return $return ? false : true;
        } elseif ($this->iwp_mmb_function_exists('passthru') && !$string) {
            $log = passthru($command, $return);
            
            if ($rawreturn)
                return $return;
            
            return $return ? false : true;
        }
        
        if ($rawreturn)
        	return -1;
        	
        return false;
    }
    
    function get_zip()
    {
        $zip = $this->iwp_mmb_exec('which zip', true);
        if (!$zip)
            $zip = "zip";
        return $zip;
    }
    
    function get_unzip()
    {
        $unzip = $this->iwp_mmb_exec('which unzip', true);
        if (!$unzip)
            $unzip = "unzip";
        return $unzip;
    }
    
    function check_backup_compat()
    {
        $reqs = array();
        if (strpos($_SERVER['DOCUMENT_ROOT'], '/') === 0) {
            $reqs['Server OS']['status'] = 'Linux (or compatible)';
            $reqs['Server OS']['pass']   = true;
        } else {
            $reqs['Server OS']['status'] = 'Windows';
            $reqs['Server OS']['pass']   = true;
            $pass                        = false;
        }
        $reqs['PHP Version']['status'] = phpversion();
        if ((float) phpversion() >= 5.1) {
            $reqs['PHP Version']['pass'] = true;
        } else {
            $reqs['PHP Version']['pass'] = false;
            $pass                        = false;
        }
        
        
        if (is_writable(WP_CONTENT_DIR)) {
            $reqs['Backup Folder']['status'] = "writable";
            $reqs['Backup Folder']['pass']   = true;
        } else {
            $reqs['Backup Folder']['status'] = "not writable";
            $reqs['Backup Folder']['pass']   = false;
        }
        
        
        $file_path = IWP_BACKUP_DIR;
        $reqs['Backup Folder']['status'] .= ' (' . $file_path . ')';
        
        if ($func = $this->check_sys()) {
            $reqs['Execute Function']['status'] = $func;
            $reqs['Execute Function']['pass']   = true;
        } else {
            $reqs['Execute Function']['status'] = "not found";
            $reqs['Execute Function']['info']   = "(will try PHP replacement)";
            $reqs['Execute Function']['pass']   = false;
        }
        $reqs['Zip']['status'] = $this->get_zip();
        
        $reqs['Zip']['pass'] = true;
        
        
        
        $reqs['Unzip']['status'] = $this->get_unzip();
        
        $reqs['Unzip']['pass'] = true;
        
        $paths = $this->check_mysqli_paths();
        
        if (!empty($paths['mysqldump'])) {
            $reqs['MySQL Dump']['status'] = $paths['mysqldump'];
            $reqs['MySQL Dump']['pass']   = true;
        } else {
            $reqs['MySQL Dump']['status'] = "not found";
            $reqs['MySQL Dump']['info']   = "(will try PHP replacement)";
            $reqs['MySQL Dump']['pass']   = false;
        }
        
        $exec_time                        = ini_get('max_execution_time');
        $reqs['Execution time']['status'] = $exec_time ? $exec_time . "s" : 'unknown';
        $reqs['Execution time']['pass']   = true;
        
        $mem_limit                      = ini_get('memory_limit');
        $reqs['Memory limit']['status'] = $mem_limit ? $mem_limit : 'unknown';
        $reqs['Memory limit']['pass']   = true;
        
        
        return $reqs;
    }
        
function ftp_backup($historyID,$args = '')
    {
		//getting the settings
		$this -> backup_settings_vals = get_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
		$current_file_num = 0;
		if($args == '')
		{
			
			$responseParams = $this -> getRequiredData($historyID,"responseParams");
			
			if(!$responseParams)
			return $this->statusLog($this -> hisID, array('stage' => 'UploadbackupFiles', 'status' => 'error', 'statusMsg' => 'FTP Backup failed: Error while fetching table data', 'statusCode' => 'ftp_backup_failed_error_while_fetching_table_data'));
			
			$args = $responseParams['ftpArgs'];
			$current_file_num = $responseParams['current_file_num'];
		}
		$tempArgs = $args;
        extract($args);
        //Args: $ftp_username, $ftp_password, $ftp_hostname, $backup_file, $ftp_remote_folder, $ftp_site_folder
        $port = $ftp_port ? $ftp_port : 21; //default port is 21
        if (!empty($ftp_ssl)) {
            if (function_exists('ftp_ssl_connect')) {
                $conn_id = ftp_ssl_connect($ftp_hostname,$port);
                if ($conn_id === false) {
                	return array(
                			'error' => 'Failed to connect to ' . $ftp_hostname,
                			'partial' => 1, 'error_code' => 'failed_to_connect_to_hostname_ftp_ssl_connect'
                	);
                }
            } else {
                return array(
                    'error' => 'Your server doesn\'t support FTP SSL',
                    'partial' => 1, 'error_code' => 'your_server_doesnt_support_ftp_ssl'
                );
            }
        }
		else {
            if (function_exists('ftp_connect')) {
                $conn_id = ftp_connect($ftp_hostname,$port);
                if ($conn_id === false) {
                    return array(
                        'error' => 'Failed to connect to ' . $ftp_hostname,
                        'partial' => 1, 'error_code' => 'failed_to_connect_hostname_ftp_connect'
                    );
                }
            } else {
                return array(
                    'error' => 'Your server doesn\'t support FTP',
                    'partial' => 1, 'error_code' => 'your_server_doesnt_support_ftp'
                );
            }
        }
		
        $login = @ftp_login($conn_id, $ftp_username, $ftp_password);
        if ($login === false) {
            return array(
                'error' => 'FTP user name and password invalid',
                'partial' => 1, 'error_code' => 'ftp_login_failed'
            );
        }
        
        if(!empty($ftp_passive)){
					@ftp_pasv($conn_id,true);
				}
		
		
		
        @ftp_mkdir($conn_id, $ftp_remote_folder);
        if (!empty($ftp_site_folder)) {
            $ftp_remote_folder .= '/' . $this->site_name;
        }
        @ftp_mkdir($conn_id, $ftp_remote_folder);
        
        //$upload = @ftp_put($conn_id, $ftp_remote_folder . '/' . basename($backup_file), $backup_file, FTP_BINARY);
		if(!is_array($backup_file))
		{
			$temp_backup_file = $backup_file;
			$backup_file = array();
			$backup_file[] = $temp_backup_file;
		}
		
		if(is_array($backup_file))
		{
			$backup_file_base_name = basename($backup_file[$current_file_num]);
		}
		else
		{
			$backup_file_base_name = basename($backup_file);
		}
		
		$upload = $this -> ftp_multi_upload($conn_id, rtrim($ftp_remote_folder, '/') . '/' . basename($backup_file_base_name), $backup_file, FTP_BINARY, $historyID, $tempArgs, $current_file_num);
		
        
        if ($upload === false) { //Try ascii
            //$upload = @ftp_put($conn_id, $ftp_remote_folder . '/' . basename($backup_file), $backup_file, FTP_ASCII);					//darkCode testing purpose
        }
		
        @ftp_close($conn_id);
        
        if ($upload === false) {
            return array(
                'error' => 'Failed to upload file to FTP. Please check your specified path.',
                'partial' => 1, 'error_code' => 'failed_to_upload_file_to_ftp'
            );
        }
        
        return $upload;
    }
	
	function ftp_multi_upload($conn_id, $remoteFileName, $backup_file, $mode, $historyID, $tempArgs, $current_file_num = 0)
	{
		$requestParams = $this->getRequiredData($historyID, "requestParams");
		$task_result = $this->getRequiredData($historyID, "taskResults");
		
		if(!$remoteFileName)
		{
			return array(
                'error' => 'Failed to upload file to FTP. Please check your specified path.',
                'partial' => 1, 'error_code' => 'failed_to_upload_file_to_ftp'
            );
		}
		
		$backup_files_base_name = array();
		if(is_array($backup_file))
		{
			foreach($backup_file as $value)
			{
				$backup_files_base_name[] = basename($value);
			}
		}
		else
		{
			$backup_files_base_name = basename($backup_file);
		}
		
		$backup_files_count = count($backup_file);
		
		if(is_array($backup_file))
		{
			$backup_file = $backup_file[$current_file_num];
		}
		
		
		$task_result['task_results'][$historyID]['ftp'] = $backup_files_base_name;
		$task_result['ftp'] = $backup_files_base_name;
		
		$backup_settings_values = $this -> backup_settings_vals;
		/* $upload_loop_break_time = $backup_settings_values['upload_loop_break_time'];
		$del_host_file = $backup_settings_values['del_host_file']; */
		
		$upload_loop_break_time = $requestParams['account_info']['upload_loop_break_time'];			//darkcode changed
		$del_host_file = $requestParams['args']['del_host_file'];
		
		if(!$upload_loop_break_time)
		{
			$upload_loop_break_time = 25;			//safe
		}
		
		$startTime = microtime(true);
		//get the filesize of the remote file first
		$file_size = ftp_size($conn_id, $remoteFileName);
		if ($file_size != -1) 
		{
			echo "size of $remoteFileName is $file_size bytes";
		}
		else 
		{
			
			$file_size = 0;
		}
		if(!$file_size)
		$file_size = 0;
		
		$real_size = filesize($local_file_path);
		//read the parts local file , if it is a second call start reading the file from the left out part which is at the offset of the remote file's filesize.
		$fp = fopen($backup_file, 'r');
		fseek($fp,$file_size);
		
		$ret = ftp_nb_fput($conn_id, $remoteFileName, $fp, FTP_BINARY, $file_size);
		if(!$ret || $ret == FTP_FAILED)
		{
			return array(
                'error' => "FTP upload Error. ftp_nb_fput(): Append/Restart not permitted. This feature is required for multi-call backup upload via FTP to work. Please contact your WP site's hosting provider and ask them to fix the problem. You can try dropbox, Amazon S3 or Google Driver as an alternative to it.",
                'partial' => 1, 'error_code' => 'ftp_nb_fput_not_permitted_error'
            );
		}
		$resArray = array (
		  'status' => 'partiallyCompleted',
		  'backupParentHID' => $historyID,
		);
		$result_arr = array();
		$result_arr['status'] = 'partiallyCompleted';
		$result_arr['nextFunc'] = 'ftp_backup';
		$result_arr['ftpArgs'] = $tempArgs;
		$result_arr['current_file_num'] = $current_file_num;
		
		/*
		1.run the while loop as long as FTP_MOREDATA is set
		2.within the loop if time is greater than specified seconds break the loop and close ftp_con return as "partiallyCompleted" setting nextFunc as ftp_backup.
		3.if ret == FTP_FINISHED , it means the ftpUpload is complete .. return as "completed".
		*/
		while ($ret == FTP_MOREDATA) {
			// Do whatever you want
			$endTime = microtime(true);
			$timeTaken = $endTime - $this->iwpScriptStartTime;
			// Continue upload...
			if($timeTaken > $upload_loop_break_time)
			{
				echo "being stopped --- ".$file_size;
				$result_arr['timeTaken'] = $timeTaken;
				$result_arr['file_size_written'] = $file_size;
				fclose($fp);
				$this->statusLog($historyID, array('stage' => 'ftpMultiCall', 'status' => 'completed', 'statusMsg' => 'nextCall being stopped --- ','nextFunc' => 'ftp_backup','task_result' => $task_result, 'responseParams' => $result_arr));
				break;
			}
			else
			{
				$ret = ftp_nb_continue($conn_id);
			}
			iwp_mmb_auto_print("ftploop");
		}
		if ($ret != FTP_FINISHED) {
			fclose($fp);
			/* if($del_host_file)
			{
				@unlink($backup_file);
			} */
			echo "backup not yet finished";
			echo "real file size $real_size";
			echo "FTP file size $size";
			return $resArray;
		}
		else
		{
			//this is where the backup call ends completing all the uploads 
			$current_file_num += 1;
			$result_arr['timeTaken'] = $timeTaken;
			$result_arr['file_size_written'] = $file_size;
			$result_arr['current_file_num'] = $current_file_num;
			
			if($current_file_num == $backup_files_count)
			{
				$result_arr['status'] = 'completed';
				$result_arr['nextFunc'] = 'ftp_backup_over';
				unset($task_result['task_results'][$historyID]['server']);
				$resArray['status'] = 'completed';
			}
			else
			{
				$result_arr['status'] = 'partiallyCompleted';
				$resArray['status'] = 'partiallyCompleted';
			}
			
			
			$this->statusLog($historyID, array('stage' => 'ftpMultiCall', 'status' => 'completed', 'statusMsg' => 'nextCall','nextFunc' => 'ftp_backup','task_result' => $task_result, 'responseParams' => $result_arr));
			
			
			fclose($fp);
			//checking file size and comparing
			$verificationResult = $this -> postUploadVerification($conn_id, $backup_file, $remoteFileName, $type = "ftp");
			if(!$verificationResult)
			{
				return $this->statusLog($historyID, array('stage' => 'uploadFTP', 'status' => 'error', 'statusMsg' => 'FTP verification failed: File may be corrupted.', 'statusCode' => 'ftp_verification_failed_file_maybe_corrupted'));
			}
			
			
			if($del_host_file)
			{
				@unlink($backup_file);				// darkcode testing purpose
			}
			iwp_mmb_print_flush('FTP upload: End');
			
			return $resArray;
		}
	}
	
	function get_files_base_name($backup_file)
	{
		$backup_files_base_name = array();
		if(is_array($backup_file))
		{
			foreach($backup_file as $value)
			{
				$backup_files_base_name[] = basename($value);
			}
		}
		else
		{
			$backup_files_base_name = basename($backup_file);
		}
		return $backup_files_base_name;
	}
	
    function postUploadVerification(&$obj, $backup_file, $destFile, $type = "", $as3_bucket = "", $as3_access_key = "", $as3_secure_key = "", $as3_bucket_region = "")
	{
		$actual_file_size = iwp_mmb_get_file_size($backup_file);
		$size1 = $actual_file_size-((0.1) * $actual_file_size);
		$size2 = $actual_file_size+((0.1) * $actual_file_size);
		if($type == "dropbox")
		{
			$dBoxMetaData = $obj -> metaData($destFile);
			$filename = basename($backup_file);
			$path = '/'.$destFile.$filename;
			if (empty($dBoxMetaData['body']->contents)) {
				return true;
			}
			foreach ($dBoxMetaData['body']->contents as $key => $value) {
				if(strtolower($path) == strtolower($value->path)){
					$dBoxFileSize = $value->bytes;
					if((($dBoxFileSize >= $size1 && $dBoxFileSize <= $actual_file_size) || ($dBoxFileSize <= $size2 && $dBoxFileSize >= $actual_file_size) || ($dBoxFileSize == $actual_file_size)) && ($dBoxFileSize != 0))
					{
						return  true;
					}
				}
			}
			
				return false;
		}
		else if($type == "amazons3")
		{
			if(is_new_s3_compatible()){
				require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/s3IWPBackup.php';
				$new_s3_obj = new IWP_MMB_S3_MULTICALL();
				return $new_s3_obj->postUploadS3Verification($backup_file, $destFile, $type, $as3_bucket, $as3_access_key, $as3_secure_key, $as3_bucket_region, $size1, $size2);
			}
			else{
				return $this->postUploadS3VerificationBwdComp($backup_file, $destFile, $type, $as3_bucket, $as3_access_key, $as3_secure_key, $as3_bucket_region, $obj, $actual_file_size, $size1, $size2);
			}
		}
		else if($type == "ftp")
		{
			ftp_chdir ($obj , dirname($destFile));
			$ftp_file_size = ftp_size($obj, basename($destFile));
			if($ftp_file_size > 0)
			{
				if((($ftp_file_size >= $size1 && $ftp_file_size <= $actual_file_size) || ($ftp_file_size <= $size2 && $ftp_file_size >= $actual_file_size) || ($ftp_file_size == $actual_file_size)) && ($ftp_file_size != 0))
				{
					return true;								
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
	}
	
	function postUploadS3VerificationBwdComp($backup_file, $destFile, $type = "", $as3_bucket = "", $as3_access_key = "", $as3_secure_key = "", $as3_bucket_region = "", &$obj, $actual_file_size, $size1, $size2, $return_size = false){
		$response = $obj -> if_object_exists($as3_bucket, $destFile);
		if($response == true)
		{
			$meta = $obj -> get_object_headers($as3_bucket, $destFile);
			$cfu_obj = new CFUtilities;
			$meta_response_array = $cfu_obj->convert_response_to_array($meta);
			$s3_filesize = $meta_response_array['header']['content-length'];
			if ($return_size == true) {
				return $s3_file_size;
			}
			echo "S3 fileszie during verification - ".$s3_filesize;
			if((($s3_filesize >= $size1 && $s3_filesize <= $actual_file_size) || ($s3_filesize <= $size2 && $s3_filesize >= $actual_file_size) || ($s3_filesize == $actual_file_size)) && ($s3_filesize != 0))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	
    function remove_ftp_backup($args)
    {
        extract($args);
        //Args: $ftp_username, $ftp_password, $ftp_hostname, $backup_file, $ftp_remote_folder
        //Args: $ftp_username, $ftp_password, $ftp_hostname, $backup_file, $ftp_remote_folder
        if(isset($use_sftp) && $use_sftp==1) {
            $port = $ftp_port ? $ftp_port : 22; //default port is 22
            /*
             * SFTP section start here phpseclib library is used for this functionality
             */
            $iwp_mmb_plugin_dir = WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__));
            $path = $iwp_mmb_plugin_dir.'/lib/phpseclib/phpseclib/phpseclib';
            set_include_path(get_include_path() . PATH_SEPARATOR . $path);
            include_once('Net/SFTP.php');

            
            $sftp = new Net_SFTP($ftp_hostname,$port);
            if(!$sftp) {
                return array(
                                            'error' => 'Failed to connect to ' . $ftp_hostname,
                                            'partial' => 1
                            );
            }
            if (!$sftp->login($ftp_username, $ftp_password)) {
                return array(
                                            'error' => 'FTP user name and password invalid',
                                            'partial' => 1
                            );
            } else {
                if ($ftp_site_folder) {
                    $ftp_remote_folder .= '/' . $this->site_name;
                }
                $remote_loation = basename($backup_file);
                $local_location = $backup_file;
                
                $sftp->chdir($ftp_remote_folder);
                $sftp->delete(basename($backup_file));

            }
            //SFTP library has automatic connection closed. So no need to call seperate connection close function
            
        } else {
        $port = $ftp_port ? $ftp_port : 21; //default port is 21
        if ($ftp_ssl && function_exists('ftp_ssl_connect')) {
            $conn_id = ftp_ssl_connect($ftp_hostname,$port);
        } else if (function_exists('ftp_connect')) {
            $conn_id = ftp_connect($ftp_hostname,$port);
        }
        
        if ($conn_id) {
            $login = @ftp_login($conn_id, $ftp_username, $ftp_password);
            if ($ftp_site_folder)
                $ftp_remote_folder .= '/' . $this->site_name;
            
            if($ftp_passive){
							@ftp_pasv($conn_id,true);
						}
			
			if(!is_array($backup_file))
			{
				$temp_backup_file = $backup_file;
				$backup_file = array();
				$backup_file[] = $temp_backup_file;
			}
			
			foreach($backup_file as $key => $value)
			{
				$delete = ftp_delete($conn_id, $ftp_remote_folder . '/' . $value);
            }
            ftp_close($conn_id);
        }
        }
        
    }
    
    function get_ftp_backup($args, $current_file_num = 0)
    {
        extract($args);
        if(isset($use_sftp) && $use_sftp==1) {
            $port = $ftp_port ? $ftp_port : 22; //default port is 22
            /*
             * SFTP section start here phpseclib library is used for this functionality
             */
            $iwp_mmb_plugin_dir = WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__));
            $path = $iwp_mmb_plugin_dir.'/lib/phpseclib';
            set_include_path(get_include_path() . PATH_SEPARATOR . $path);
            include_once('Net/SFTP.php');
            
            
            $sftp = new Net_SFTP($ftp_hostname,$port);
            if(!$sftp) {
                return array(
                                            'error' => 'Failed to connect to ' . $ftp_hostname,
                                            'partial' => 1
                            );
            }
            if (!$sftp->login($ftp_username, $ftp_password)) {
                return array(
                                            'error' => 'FTP login failed for ' . $ftp_username . ', ' . $ftp_password,
                                            'partial' => 1
                            );
            } else {
                if ($ftp_site_folder) {
                    $ftp_remote_folder .= '/' . $this->site_name;
                }
                $remote_loation = basename($backup_file);
                $local_location = $backup_file;
                
                $sftp->chdir($ftp_remote_folder);
                //$sftp->delete(basename($backup_file));
                $temp = wp_tempnam('iwp_temp_backup.zip');
                
                $get  = $sftp->get(basename($backup_file), $temp);
                if ($get === false) {
                    return false;
                } else {
                    return $temp;
                }
                //SFTP library has automatic connection closed. So no need to call seperate connection close function

            }
            
        } else {
        //Args: $ftp_username, $ftp_password, $ftp_hostname, $backup_file, $ftp_remote_folder
        $port = $ftp_port ? $ftp_port : 21; //default port is 21
        if ($ftp_ssl && function_exists('ftp_ssl_connect')) {
            $conn_id = ftp_ssl_connect($ftp_hostname,$port);
            
        } else if (function_exists('ftp_connect')) {
            $conn_id = ftp_connect($ftp_hostname,$port);
            if ($conn_id === false) {
                return false;
            }
        } 
        $login = @ftp_login($conn_id, $ftp_username, $ftp_password);
        if ($login === false) {
            return false;
        }
        
        if ($ftp_site_folder)
            $ftp_remote_folder .= '/' . $this->site_name;
        
        if($ftp_passive){
					@ftp_pasv($conn_id,true);
				}
        
		//$temp = ABSPATH . 'iwp_temp_backup.zip';
        $temp = wp_tempnam('iwp_temp_backup.zip');
		
        $get  = ftp_get($conn_id, $temp, $ftp_remote_folder . '/' . $backup_file, FTP_BINARY);
        if ($get === false) {
            return false;
        } else {
        }
        ftp_close($conn_id);
        
        return $temp;
    }
    }
	
	
	/*
		--The new Dropbox function which supports multiple calls--
		
		1.first call the chunked_upload function with no upload_id and get a response array of upload_id and offset .
		2.pass the upload_id and offset in the multiple calls until the file is completely uploaded .
		
		--note--
		1.the args should have the backup_file_size in bytes.
		2.on final call the chunked upload will be commited.
		3.there are some changes in the dropbox.php lib file .
	
	*/
   
 function dropbox_backup($historyID = 0, $args = '', $uploadid = null, $offset = 0)
	{
		//included two arguments $uploadid and $offset
		$dBoxStartTime = $this->iwpScriptStartTime;
		
		//get the settings
		//$this -> backup_settings_vals = get_option('iwp_client_multi_backup_temp_values');
		//$backup_settings_values = $this -> backup_settings_vals;
		//$upload_file_block_size = $backup_settings_values['upload_file_block_size'];
		//$actual_file_size = $backup_settings_values['actual_file_size'];
		//$del_host_file = $backup_settings_values['del_host_file'];
		
		//get the settings other method
		$requestParams = $this->getRequiredData($historyID, "requestParams");
		$upload_loop_break_time = $requestParams['account_info']['upload_loop_break_time'];			//darkcode changed
		$upload_file_block_size = $requestParams['account_info']['upload_file_block_size'];
		$del_host_file = $requestParams['args']['del_host_file'];
		$current_file_num = 0;
		
		if($args == '')
		{
			//on the next call $args would be ''
			//set $args, $uploadid, $offset  from the DB
			$responseParams = $this -> getRequiredData($historyID,"responseParams");
			
			if(!$responseParams)
			$this->statusLog($historyID, array('stage' => 'backupFiles', 'status' => 'error', 'statusMsg' => 'errorGettingDBValues', 'statusCode' => 'error_getting_db_values'));
			
			$args = $responseParams['dropboxArgs'];
			$prevChunkResults = $responseParams['response_data'];
			$uploadid = $prevChunkResults['upload_id'];
			$offset = $prevChunkResults['offset'];
			$current_file_num = $responseParams['current_file_num'];
		}
		
		$tempArgs = $args;
        extract($args);
        
		$task_result = $this->getRequiredData($historyID, "taskResults");
		$task_result['task_results'][$historyID]['dropbox'] = $this->get_files_base_name($backup_file);
		$task_result['dropbox'] = $this->get_files_base_name($backup_file);
		
		if(!is_array($backup_file))
		{
			$temp_backup_file = $backup_file;
			$backup_file = array();
			$backup_file[] = $temp_backup_file;
		}
		
		if(is_array($backup_file))
		{
			$backup_files_count = count($backup_file);
			$backup_file = $backup_file[$current_file_num];
		}
		$actual_file_size = iwp_mmb_get_file_size($backup_file);
		$backup_file_size = $actual_file_size;
		//$backup_file_size = 10394909;				//darkCode testing purpose
		
		if (!$this->iwp_mmb_function_exists('curl_init')) {
			return array(
                'error' => 'You cannot use Dropbox on your server. Please enable curl first.',
                'partial' => 1, 'error_code' => 'cannot_use_dropbox_enable_curl_first'
            );
		}
		$oldVersion = false;
			if((isset($consumer_secret) && !empty($consumer_secret)) || (isset($dropbox_access_token) && !empty($dropbox_access_token))){
				if(!isset($dropbox_access_token) && empty($dropbox_access_token)){

					require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/dropbox.php';
					
					$dropbox = new IWP_Dropbox($consumer_key, $consumer_secret);
					$dropbox->setOAuthTokens($oauth_token, $oauth_token_secret);
					$oldVersion = true;
					if (isset($dropbox_site_folder) && $dropbox_site_folder == true)
						$dropbox_destination .= '/' . $this->site_name . '/' . basename($backup_file);
					else
						$dropbox_destination .= '/' . basename($backup_file);
				}else{
					require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/API.php';
					require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/Exception.php';
					require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/OAuth/Consumer/ConsumerAbstract.php';
					require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/OAuth/Consumer/Curl.php';
					
					$oauth = new IWP_Dropbox_OAuth_Consumer_Curl($dropbox_app_key, $dropbox_app_secure_key);
					$oauth->setToken($dropbox_access_token);
					$dropbox = new IWP_Dropbox_API($oauth);
					$oldRoot = 'Apps/InfiniteWP/';
					$dropbox_destination = $oldRoot.ltrim(trim($dropbox_destination), '/');
						$dropbox_destination = rtrim($dropbox_destination, '/');
					if (isset($dropbox_site_folder) && $dropbox_site_folder == true){
						$dropbox_destination .=  '/'.$this->site_name;
					}
					$folders = explode('/',$dropbox_destination);
					foreach ($folders as $key => $name) {
					    $path.=trim($name).'/';
					}
					$dropbox_destination = $path;

				}
				
				try {
					//$dropbox->upload($backup_file, $dropbox_destination, true);                       //we are using new multiCAll function
					
					// this is the dropbox loop ..
					$reloop = false;
					$chunkCount = 0;
					$chunkTimeTaken = 0;
					do
					{
						if($chunkCount == 0)
						{
							$chunkStartTime = $dBoxStartTime;
						}
						else
						{
							$chunkStartTime = microtime(true);
						}
						if(($backup_file_size - $offset) >= 4194304)		//the chunk size is set here
						{
							$readsize = $upload_file_block_size;
							$isCommit = false;
							$status = 'partiallyCompleted';
						}
						else
						{
							$readsize = ($backup_file_size - $offset);
							
							$isCommit = true;
							$status = 'completed';
						}
						if($oldVersion){
							$chunkResult = $dropbox->chunked_upload($backup_file, $dropbox_destination, true, $uploadid, $offset, $readsize, $isCommit);
						}else{
							$chunkResult = $dropbox->chunked_upload($backup_file ,$dropbox_destination, true, $uploadid, $offset, $isCommit);
						}
						$result_arr = array();
						$result_arr['response_data'] = $chunkResult;
						$result_arr['status'] = $status;
						$result_arr['nextFunc'] = 'dropbox_backup';
						$result_arr['dropboxArgs'] = $tempArgs;
						$result_arr['current_file_num'] = $current_file_num;
						//updating offset and uploadid values for relooping.
						$offset = isset($chunkResult['offset']) ? $chunkResult['offset'] : 0;
						$uploadid = isset($chunkResult['upload_id']) ? $chunkResult['upload_id'] : 0; 
						echo 'completed-size'.($offset/1024/1024);
						//check time 
						$chunkCompleteTime = microtime(true);
						$dBoxCompleteTime = microtime(true);
						$chunkTimeTaken = (($chunkTimeTaken + ($chunkCompleteTime - $chunkStartTime))/($chunkCount + 1));		// this is the average chunk time
						echo " thisChunkTimeTaken".$chunkTimeTaken;
						$dBoxTimeTaken = $dBoxCompleteTime - $dBoxStartTime;
						$dBoxTimeLeft = $upload_loop_break_time - $dBoxTimeTaken;								//calculating time left for the dBOX upload .. 
						$dBoxTimeLeft = $dBoxTimeLeft;														//for safe time limit
						echo " dBoxTimeLeft".$dBoxTimeLeft;
						//$halfOfLoopTime = (($upload_loop_break_time / 2) - 1);
						if(($dBoxTimeLeft <= $chunkTimeTaken)||($status == 'completed'))			//if the time Left for the dropbox upload is less than the time to upload a single chunk break the loop 
						{	
							if ($status == 'complete') {
								$result_arr['response_data']['offset'] = 0;
								$result_arr['response_data']['upload_id'] = null;
							}
							$reloop = false;
						}
						else
						{

							$reloop = true;
							$chunkCount++;
						}
					}while($reloop);
					
					$resArray = array (
					  'status' => $status,
					  'backupParentHID' => $historyID,
					);
					
					if($status == 'completed')
					{
						$current_file_num += 1;
						if($current_file_num == $backup_files_count)
						{
							$result_arr['nextFunc'] = 'dropbox_backup_over';
							iwp_mmb_print_flush('Dropbox upload: End');
							unset($task_result['task_results'][$historyID]['server']);
						}
						else
						{
							$result_arr['nextFunc'] = 'dropbox_backup';
							$result_arr['current_file_num'] = $current_file_num;
							$result_arr['status'] = 'partiallyCompleted';
							$resArray['status'] = 'partiallyCompleted';
						}
					}
					$this->statusLog($historyID, array('stage' => 'dropboxMultiCall', 'status' => 'completed', 'statusMsg' => 'nextCall','nextFunc' => 'dropbox_backup', 'task_result' => $task_result,  'responseParams' => $result_arr));
					
					
					if($status == 'completed')
					{
						//checking file size and comparing
						$verificationResult = $this -> postUploadVerification($dropbox, $backup_file, $dropbox_destination, $type = "dropbox");
						if(!$verificationResult)
						{
							return $this->statusLog($historyID, array('stage' => 'uploadDropBox', 'status' => 'error', 'statusMsg' => 'Dropbox verification failed: File may be corrupted.', 'statusCode' => 'dropbox_verification_failed_file_may_be_corrupted'));
						}
						if($del_host_file)
						{
							@unlink($backup_file);
						}
					}
					
					return $resArray;
					
				} 
				catch (Exception $e) {
					if (preg_match("/Submitted input out of alignment: got \[(\d+)\] expected \[(\d+)\]/i", $e->getMessage(), $matches)) {
						// Try the indicated offset
						$we_tried = $matches[1];
						$offset = $matches[2];
						/*if($oldVersion){
							$chunkResult = $dropbox->chunked_upload($backup_file, $dropbox_destination, true, $uploadid, $offset, $readsize, $isCommit);
						}else{
							$chunkResult = $dropbox->chunked_upload($backup_file ,$dropbox_destination, true, $uploadid, $offset, $isCommit);
						}*/
						echo "Submitted input out of alignment";
						$result_arr = array();
						$result_arr['nextFunc'] = 'dropbox_backup';
						$result_arr['dropboxArgs'] = $tempArgs;
						$chunkResult['offset'] = isset($offset) ? $offset : 0;
						$chunkResult['uploadid'] = isset($prevChunkResults['upload_id']) ? $prevChunkResults['upload_id'] : 0; 
						$result_arr['response_data'] = $chunkResult;
						$resArray = array (
						  'backupParentHID' => $historyID,
						  'status' => 'partiallyCompleted'
						);
						$result_arr['nextFunc'] = 'dropbox_backup';
						$result_arr['current_file_num'] = $current_file_num;
						$result_arr['status'] = 'partiallyCompleted';
						$this->statusLog($historyID, array('stage' => 'dropboxMultiCall', 'status' => 'completed', 'statusMsg' => 'nextCall','nextFunc' => 'dropbox_backup', 'task_result' => $task_result,  'responseParams' => $result_arr));
						return $resArray;
					}

					if ($e->getMessage() == 'path') {
						
						$response = $dropbox->quotaInfo();
						$usedSize = $response['body']->used;
						$allocated = $response['body']->allocation->allocated;
						if ($usedSize>=$allocated) {
							return array(
								'error' => "Dropbox quota exceeded (Allowed ".round($allocated / (1024*1024), 2)." MB and used ".round($usedSize / (1024*1024), 2)." MB)",
								'partial' => 1
							);
						}
					}
					$this->_log($e->getMessage());
					return array(
						'error' => $e->getMessage(),
						'partial' => 1
					);
				}
				
				//return true;
				
			}
			else {
				return array(
					'error' => 'Please connect your InfiniteWP panel with your Dropbox account.', 'error_code' => 'please_connect_your_iwp_panel_with_your_dropbox_account'
				);
			}
		}
	
	
	
	function remove_dropbox_backup($args) {
    	extract($args);
        
       if(!isset($dropbox_access_token) && empty($dropbox_access_token)){

	       	require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/dropbox.php';
	       	
	       	$dropbox = new IWP_Dropbox($consumer_key, $consumer_secret);
	       	$dropbox->setOAuthTokens($oauth_token, $oauth_token_secret);
	       	$oldVersion = true;
	       	if ($dropbox_site_folder == true)
	       		$dropbox_destination .= '/' . $this->site_name;
       }else{
	       	require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/API.php';
	       	require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/Exception.php';
	       	require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/OAuth/Consumer/ConsumerAbstract.php';
	       	require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/OAuth/Consumer/Curl.php';
	       	
	       	$oauth = new IWP_Dropbox_OAuth_Consumer_Curl($dropbox_app_key, $dropbox_app_secure_key);
	       	$oauth->setToken($dropbox_access_token);
	       	$dropbox = new IWP_Dropbox_API($oauth);
	       	$oldRoot = 'Apps/InfiniteWP/';
			$dropbox_destination = $oldRoot.ltrim(trim($dropbox_destination), '/');
			$dropbox_destination = rtrim($dropbox_destination, '/');
			if (isset($dropbox_site_folder) && $dropbox_site_folder == true){
			    $dropbox_destination .=  '/'.$this->site_name;
			}
			$folders = explode('/',$dropbox_destination);
			foreach ($folders as $key => $name) {
			    $path.=trim($name).'/';
			}
			$dropbox_destination = $path;
				$oldVersion = false;
       }
        
    	
		$temp_backup_file = $backup_file;
		if(!is_array($backup_file))
		{
			$backup_file = array();
			$backup_file[] = $temp_backup_file;
		}
		foreach($backup_file as $key => $value)
		{
			try {
				if ($oldVersion) {
					$dropbox->fileopsDelete($dropbox_destination . '/' . $value);
				}else{
					$dropbox->delete($dropbox_destination . '/' . $value);
				}
			} catch (Exception $e) {
				$this->_log($e->getMessage());
				/*return array(
					'error' => $e->getMessage(),
					'partial' => 1
				);*/
			}
    	}
    	//return true;
	}
	

	function get_dropbox_backup($args) {
		if ($this->iwp_mmb_function_exists('curl_init')) {
			extract($args);
			if(!isset($dropbox_access_token) && empty($dropbox_access_token)){

				require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/dropbox.php';
				
				$dropbox = new IWP_Dropbox($consumer_key, $consumer_secret);
				$dropbox->setOAuthTokens($oauth_token, $oauth_token_secret);
				$oldVersion = true;
				if ($dropbox_site_folder == true)
				$dropbox_destination .= '/' . $this->site_name;
			}else{
				require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/API.php';
				require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/Exception.php';
				require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/OAuth/Consumer/ConsumerAbstract.php';
				require_once $GLOBALS['iwp_mmb_plugin_dir'] . '/lib/Dropbox/OAuth/Consumer/Curl.php';
				
				$oauth = new IWP_Dropbox_OAuth_Consumer_Curl($dropbox_app_key, $dropbox_app_secure_key);
				$oauth->setToken($dropbox_access_token);
				$dropbox = new IWP_Dropbox_API($oauth);
				$oldRoot = 'Apps/InfiniteWP/';
				$dropbox_destination = $oldRoot.ltrim(trim($dropbox_destination), '/');
                $dropbox_destination = rtrim($dropbox_destination, '/');
	            if (isset($dropbox_site_folder) && $dropbox_site_folder == true){
	                $dropbox_destination .=  '/'.$this->site_name;
	            }
	            $folders = explode('/',$dropbox_destination);
	            foreach ($folders as $key => $name) {
	                $path.=trim($name).'/';
	            }
	            $dropbox_destination = $path;
				$oldVersion = false;
			}
			
			
			//$temp = ABSPATH . 'iwp_temp_backup.zip';
			$temp = wp_tempnam('iwp_temp_backup.zip');
			
			try {
				//exception should handle the errors
				if ($oldVersion) {
					$dropbox->download($dropbox_destination.'/'.$backup_file, $temp); 					
				}else{
	  				$dropbox->getFile($dropbox_destination.'/'.$backup_file, $temp); 
				}
				return $temp;
			} catch (Exception $e) {
				$this->_log($e->getMessage());
				return array(
					'error' => $e->getMessage(),
					'partial' => 1
				);
			}
		}
		else{
			return array(
                'error' => 1,
            );
		}
	}
	
	/*
	This is the new amazon s3 function with multiCall support
	
	1.initiate the multipart process and get an upload_id in return [using the function initiate_multipart_upload() ].
	2.divide the backup file into many parts  [ using the function get_multipart_counts() ]
	3.call the function upload_part() and upload the parts one by one by getting an partsArray of PartNumber and Etag as response. 
	4.On the finalCall complete the multipart upload by calling the function complete_multipart_upload() - by providing the $uploadID as well as partsArray .
	
	---notes---
	1.complete the multiPart only on the final call
	2.the mulitpart upload process should have to be completed or aborted because amazon will charge for the data used 
	
	*/
	
	function amazons3_backup_bwd_comp($historyID , $args='' )
    {
		$s3StartTime = $this->iwpScriptStartTime;
		$this -> backup_settings_vals = get_option('iwp_client_multi_backup_temp_values');
		$backup_settings_values = $this -> backup_settings_vals;
		if(isset($backup_settings_values['s3_retrace_count']) && !empty($backup_settings_values['s3_retrace_count']))
		{
			$s3_retrace_count = $backup_settings_values['s3_retrace_count'][$historyID];
		}
		else
		{
			$s3_retrace_count = 0;
		}
		//get the settings by other method
		$requestParams = $this -> getRequiredData($historyID,"requestParams");
		$upload_loop_break_time = $requestParams['account_info']['upload_loop_break_time'];			//darkcode changed
		$upload_file_block_size = $requestParams['account_info']['upload_file_block_size'];
		if($upload_file_block_size < (5*1024*1024))
		{
			$upload_file_block_size = (5*1024*1024)+1;
		}
		$del_host_file = $requestParams['args']['del_host_file'];
		
		$task_result = $this -> getRequiredData($historyID,"taskResults");
		
		@set_time_limit(0);
		$this -> hisID = $historyID;
		
		$uploadLoopCount = 0;
		$upload_id = 'start';
		$partsArray = array();
		$nextPart = 1;
		$retrace = 'notSet';
		$doComplete = false;

		if($args == '')
		{
			//on the next call $args would be ''
			//set $args, $uploadid, $offset  from the DB
			$responseParams = $this -> getRequiredData($historyID,"responseParams");
			
			if(!$responseParams)
			return $this->statusLog($this -> hisID, array('stage' => 's3Upload', 'status' => 'error', 'statusMsg' => 'S3 Upload failed: Error while fetching table data.', 'statusCode' => 's3_upload_failed_error_while_fetching_table_data'));
			
			$args = $responseParams['s3Args'];
			$prevChunkResults = $responseParams['response_data'];
			$upload_id = $prevChunkResults['upload_id'];
			$nextPart = $prevChunkResults['nextPart'];
			$partsArray = $prevChunkResults['partsArray'];
			$current_file_num = $responseParams['current_file_num'];
			$dont_retrace = $responseParams['dont_retrace'];
			$start_new_backup = $responseParams['start_new_backup'];
			
		}
		if(empty($current_file_num))
		{
			$current_file_num = 0;
		}
		
		//traceback options and setting values 
		
		if((!$upload_id)&&(empty($dont_retrace)))
		{
			if($s3_retrace_count <= 3)
			{
				$args = $requestParams['secure']['account_info']['iwp_amazon_s3'];
				if($backup_settings_values['s3_upload_id'])
				{
					$upload_id = $backup_settings_values['s3_upload_id'][$historyID];
				}
				else
				{
					return $this->statusLog($this -> hisID, array('stage' => 's3Upload Retrace', 'status' => 'error', 'statusMsg' => 'S3 Upload failed: Error while fetching table data during retrace',  'statusCode' => 's3_upload_failed_error_while_fetching_table_data_during_retrace'));
				}
				$backup_file = $backup_settings_values['backup_file'];
				$retrace = 'set';
				$s3_retrace_count++;
				$backup_settings_values['s3_retrace_count'][$historyID] = $s3_retrace_count;
				update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
				
			}
			else
			{
				return $this->statusLog($this -> hisID, array('stage' => 's3Upload', 'status' => 'error', 'statusMsg' => 'S3 upload failed: Retrace limit reached.', 'statusCode' => 's3_upload_failed_retrace_limit_reached'));
			}
		}
		
        if (!$this->iwp_mmb_function_exists('curl_init')) {
			return array(
                'error' => 'You cannot use Amazon S3 on your server. Please enable curl first.',
                'partial' => 1, 'error_code' => 'cannot_use_s3_enable_curl_first'
            );
		}
            require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon_s3_bwd_comp/sdk.class.php');
			
			$tempArgs = $args;
            extract($args);
			
			if(!is_array($backup_file))
			{
				$temp_backup_file = $backup_file;
				$backup_file = array();
				$backup_file[] = $temp_backup_file;
			}
			
			if(is_array($backup_file))
			{
				$backup_files_count = count($backup_file);
				$temp_single_file = $backup_file[$current_file_num];
				unset($backup_file);
				$backup_file = $temp_single_file;
			}
			
            if ($as3_site_folder == true)
			{
				if(!empty($as3_directory))
				{
					$as3_directory .= '/' . $this->site_name;
				}
				else
				{
					$as3_directory =  $this->site_name;
				}
			}	
           
            try{
				
				CFCredentials::set(array('development' => array('key' => trim($as3_access_key), 'secret' => trim(str_replace(' ', '+', $as3_secure_key)), 'default_cache_config' => '', 'certificate_authority' => true, 'use_ssl'=>false, 'ssl_verification'=>false), '@default' => 'development'));
				$s3 = new AmazonS3();
				$cfu_obj = new CFUtilities;
				//the mulitCall upload starts				darkCode starts
				
				
				//$this->statusLog($this -> hisID, array('stage' => 'uploadingFiles', 'status' => 'partiallyCompleted', 'statusMsg' => 's3MultiCallStartsHere'));
				if(!empty($as3_directory)){
					$as3_file = $as3_directory . '/' . basename($backup_file);
				}
				else{
					$as3_file = basename($backup_file);
				}
				
				if((iwp_mmb_get_file_size($backup_file) <= 5*1024*1024))
				{
					echo "<br>small backup so single upload<br>";
					$response = $s3->create_object($as3_bucket, $as3_file, array('fileUpload' => $backup_file));
					if($response->isOK())
					{
						$current_file_num += 1;
						
						$resArray = array (
						  'status' => "completed",
						  'backupParentHID' => $historyID,
						);
						
						$result_arr = array();
						$result_arr['status'] = 'completed';
						$result_arr['nextFunc'] = 'amazons3_backup_over';
						$result_arr['s3Args'] = $tempArgs;
						$result_arr['current_file_num'] = $current_file_num;
						$result_arr['dont_retrace'] = true;
						
						$task_result['task_results'][$historyID]['amazons3'][$current_file_num-1] = basename($backup_file);
						$task_result['amazons3'][$current_file_num-1] = basename($backup_file);
						
						if($current_file_num >= $backup_files_count)
						{
							unset($task_result['task_results'][$historyID]['server']);
							@unlink($backup_file);
						}
						else
						{
							//to continue zip split parts
							
							$resArray['status'] = 'partiallyCompleted';
							
							$chunkResult = array();
							$chunkResult['partsArray'] = array();
							$chunkResult['nextPart'] = 1;
							$chunkResult['upload_id'] = 'start';
							
							$result_arr['response_data'] = $chunkResult;
							$result_arr['nextFunc'] = 'amazons3_backup';
							$result_arr['status'] = 'partiallyCompleted';
							$result_arr['start_new_backup'] = true;
							
							@unlink($backup_file);
						}
						$this->statusLog($this -> hisID, array('stage' => 's3MultiCall', 'status' => 'completed', 'statusMsg' => 'nextCall','nextFunc' => 'amazons3_backup', 'task_result' => $task_result, 'responseParams' => $result_arr));
						
						return $resArray;
					}
					else
					{
						return array(
							'error' => 'Failed to upload to Amazon S3.'
						);
					}
				}
				
				if($upload_id == 'start')
				{
					echo "initiating multiCall upload";
					
					//initiate the multiPartUpload to get the uploadID from its response 
					$response = $s3->initiate_multipart_upload($as3_bucket, $as3_file);	 //createMultipartUpload
					
					
					
					//convert the response into an array
					$response_array = $cfu_obj->convert_response_to_array($response);
					
					
					//get the uploadID
					$upload_id = $response_array['body']['UploadId'];	
					
					
					//storing the uploadID in DB 
					$backup_settings_values['s3_upload_id'][$historyID] = $upload_id;
					$backup_settings_values['backup_file'] = $backup_file;
					update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
				}
				
				//get the parts of the big file
				$parts = $s3->get_multipart_counts(iwp_mmb_get_file_size($backup_file), $upload_file_block_size);			//1 MB chunks
				
				if($retrace == 'set')
				{
					$list_parts_response = $s3->list_parts($as3_bucket, $as3_file, $upload_id);
					$partsArray = CFUtilities::convert_response_to_array($list_parts_response);
					$nextPart = (count($partsArray) + 1);
					$this->statusLog($this -> hisID, array('stage' => 's3MultiCall', 'status' => 'partiallyCompleted', 'statusMsg' => 'retracingValues','nextFunc' => 'amazons3_backup', 'task_result' => $task_result, 'responseParams' => $result_arr));
					$retrace = 'unset';
				}
				
				
				//this is the main upload loop break it on when the timeLimit is reached 
				//chunk upload loop
				$partsArraySize = count($parts);
				$s3ChunkTimeTaken = 0;
				$s3ChunkCount = 0;
				$reloop = false;
				$reloopCount = 0;
				$status = '';
				do
				{
					$uploadLoopCount = 0;
					if($reloopCount == 0)
					{
						$s3ChunkStartTime = $s3StartTime;
					}
					else
					{
						$s3ChunkStartTime = microtime(true);
					}
				
					foreach ($parts as $i => $part)
					{
						$uploadLoopCount += 1; 
						if($uploadLoopCount == $nextPart)
						{
							$singleUploadResponse = $s3->/* batch()-> */upload_part($as3_bucket, $as3_file, $upload_id, array(
							//'expect'     => '100-continue',
							'fileUpload' => $backup_file,
							'partNumber' => ($i + 1),
							'seekTo'     => /* (integer)  */$part['seekTo'],
							'length'     => /* (integer)  */$part['length'],
							));
							
							$singleUploadResult = $singleUploadResponse->isOk();

							echo "singleUploadResult - ".$singleUploadResult;
							
							$singleUploadResponseArray = $cfu_obj->convert_response_to_array($singleUploadResponse);
							/* $response = $s3->complete_multipart_upload($bucket, $filename, $upload_id, array(
								array('PartNumber' => 1, 'ETag' => '"25e317773f308e446cc84c503a6d1f85"'),
								array('PartNumber' => 2, 'ETag' => '"a6d1f85f58498973f308e446cc84c503"'),
								array('PartNumber' => 3, 'ETag' => '"bed3c0a4a1407f584989b4009e9ce33f"'),
							)); */
							
							$nextPart = $uploadLoopCount;
							$partsArray[$i + 1]['PartNumber'] = $i + 1;
							$partsArray[$i + 1]['ETag'] = $singleUploadResponseArray['header']['etag'];
							
							
							$chunkResult = array();
							$chunkResult['partsArray'] = $partsArray;
							$chunkResult['nextPart'] = $nextPart+1;
							$chunkResult['upload_id'] = $upload_id;
							$nextPart = $nextPart + 1;
							
							$backup_settings_values['s3_retrace_count'][$historyID] = 0;
							update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
							
							$status = 'partiallyCompleted';
							if(($nextPart) == ($partsArraySize + 1))
							{
								$doComplete = true;
								$status = 'completed';
							}
							
							$result_arr = array();
							$result_arr['response_data'] = $chunkResult;
							$result_arr['status'] = $status;
							$result_arr['nextFunc'] = 'amazons3_backup';
							$result_arr['s3Args'] = $tempArgs;
							$result_arr['current_file_num'] = $current_file_num;
							
							$task_result['task_results'][$historyID]['amazons3'][$current_file_num] = basename($backup_file);
							$task_result['amazons3'][$current_file_num] = basename($backup_file);
							
							$this->statusLog($this -> hisID, array('stage' => 's3MultiCall', 'status' => 'completed', 'statusMsg' => 'nextCall','nextFunc' => 'amazons3_backup', 'task_result' => $task_result, 'responseParams' => $result_arr));
							
							$resArray = array (
							  'status' => $status,
							  'backupParentHID' => $historyID,
							);
							
							/* $resArray = array (
							  'status' => 'completed',
							  'backupParentHID' => $historyID,
							); */
							break;
							//return $resArray;
							//exit;
						}
						else
						{
							if($nextPart == ($partsArraySize+1))
							{
								$doComplete = true;
								break;
							}
						}
					}
					
					if($doComplete)
					{
						// complete the multipart upload
						$response = $s3->complete_multipart_upload($as3_bucket, $as3_file, $upload_id, $partsArray);
						
						if($response->isOK() != true)
						{
							
							$response = $s3->abort_multipart_upload($as3_bucket, $as3_file, $upload_id );
						}
						$response_array = $cfu_obj->convert_response_to_array($response);
						
						$current_file_num += 1;
						
						$result_arr = array();
						$result_arr['response_data'] = $chunkResult;
						$result_arr['status'] = 'completed';
						$result_arr['nextFunc'] = 'amazons3_backup_over';
						$result_arr['s3Args'] = $tempArgs;
						$result_arr['dont_retrace'] = true;
						$result_arr['current_file_num'] = $current_file_num;
						
						$resArray = array (
						  'status' => 'completed',
						  'backupParentHID' => $historyID,
						);
						
						
						
						if($current_file_num >= $backup_files_count)
						{
							$task_result['task_results'][$historyID]['amazons3'][$current_file_num-1] = basename($backup_file);
							$task_result['amazons3'][$current_file_num-1] = basename($backup_file);
							unset($task_result['task_results'][$historyID]['server']);
						}
						else
						{
							//to continue zip split parts
							$status = 'partiallyCompleted';
							$chunkResult = array();
							$chunkResult['partsArray'] = array();
							$chunkResult['nextPart'] = 1;
							$chunkResult['upload_id'] = 'start';
							
							$result_arr['response_data'] = $chunkResult;
							$result_arr['status'] = 'partiallyCompleted';
							$result_arr['nextFunc'] = 'amazons3_backup';
							$result_arr['start_new_backup'] = true;
							
							$resArray['status'] = 'partiallyCompleted';
						}
						$this->statusLog($this -> hisID, array('stage' => 's3MultiCall', 'status' => 'completed', 'statusMsg' => 'finalCall','nextFunc' => 'amazons3_backup', 'task_result' => $task_result, 'responseParams' => $result_arr));
						
						$upload = $response->isOk();
					}
					
					
					
					//check time
					$s3ChunkEndTime = microtime(true);
					$s3ChunkTimeTaken = (($s3ChunkEndTime - $s3ChunkStartTime) + ($s3ChunkTimeTaken) / ($reloopCount + 1));
					$s3EndTime = microtime(true);
					$s3TimeTaken = $s3EndTime - $s3StartTime;
					$s3TimeLeft = $upload_loop_break_time - $s3TimeTaken;
					$s3TimeLeft = $s3TimeLeft - 5;								//for safe timeLimit
					
					if(!empty($chunkResult['nextPart']))
					{
						echo 'parts'.$chunkResult['nextPart'];
					}
					echo " s3TimeTaken ".$s3TimeTaken;
					$s3UploadedSize = $uploadLoopCount * 5;
					echo " s3 approx file size written ".$s3UploadedSize;
					iwp_mmb_print_flush("s3loop");
					echo " s3TimeLeft ".$s3TimeLeft;
					echo " s3ChunkTimeTaken ".$s3ChunkTimeTaken;
					if(($s3TimeLeft <= $s3ChunkTimeTaken)||(!$singleUploadResult)||($doComplete))
					{
						$reloop = false;
						echo "reloop stopped";
					}
					else
					{
						$reloop = true;
						$reloopCount++;
					}
					
				}while($reloop);
				
				if(!$doComplete)
				{
					return $resArray;
				}
				
				if($doComplete && $upload) 
				{
					
					$status = 'completed';
					iwp_mmb_print_flush('Amazon S3 upload: End');
					if($status == 'completed')
					{
						//file verification
						//checking file size and comparing
						//getting the hash value 
						$partArrayLength = count($partsArray);
						$verificationResult = $this -> postUploadVerification($s3, $backup_file, $as3_file, $type = "amazons3", $as3_bucket);
						if(!$verificationResult)
						{
							return $this->statusLog($historyID, array('stage' => 'uploadAmazons3', 'status' => 'error', 'statusMsg' => 'S3 verification failed: File may be corrupted.', 'statusCode' => 'docomplete_S3_verification_failed_file_may_be_corrupted'));
						}
						if($del_host_file)
						{
							@unlink($backup_file);
						}
					}
					return $resArray;			
				}
				else {
					
					return array(
						'error' => 'Failed to upload to Amazon S3. Please check your details and set upload/delete permissions on your bucket.',
						'partial' => 1, 'error_code' => 'failed_to_upload_to_s3_check_your_details_and_set_upload_delete_permissions_on_your_bucket'
					);
				}
				
				
			}
			catch (Exception $e)
			{
				
				$err = $e->getMessage();
				if($err){
					 return array(
						'error' => 'Failed to upload to AmazonS3 ('.$err.').', 'error_code' => 'failed_to_upload_s3_err'
					);
				} else {
					return array(
						'error' => 'Failed to upload to Amazon S3.', 'error_code' => 'failed_to_upload_s3'
					);
				 }
			}
    }
    
	function remove_amazons3_backup_bwd_comp($args)
    {
    	if ($this->iwp_mmb_function_exists('curl_init')) {
        require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon_s3_bwd_comp/sdk.class.php');
        extract($args);
		
		if(!is_array($backup_file))
		{
			$temp_backup_file = $backup_file;
			$backup_file = array();
			$backup_file[] = $temp_backup_file;
		}
		
        if ($as3_site_folder == true)
		{
			if(!empty($as3_directory))
			{
				$as3_directory .= '/' . $this->site_name;
			}
			else
			{
				$as3_directory =  $this->site_name;
			}
		}
        try{
			CFCredentials::set(array('development' => array('key' => trim($as3_access_key), 'secret' => trim(str_replace(' ', '+', $as3_secure_key)), 'default_cache_config' => '', 'certificate_authority' => true), '@default' => 'development'));
			$s3 = new AmazonS3();
			foreach($backup_file as $single_backup_file)
			{
				if(empty($as3_directory))
				{
					$single_as3_file = $single_backup_file;
				}
				else
				{
					$single_as3_file = $as3_directory . '/' . $single_backup_file;
				}
				$s3->delete_object($as3_bucket, $single_as3_file);
			}
			
      	} catch (Exception $e){
      		
      	}
      }
    }
    
	function get_amazons3_backup_bwd_comp($args)
    {
		if ($this->iwp_mmb_function_exists('curl_init')) {
			require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon_s3_bwd_comp/sdk.class.php');
			extract($args);
			$temp = '';
			try{
				CFCredentials::set(array('development' => array('key' => trim($as3_access_key), 'secret' => trim(str_replace(' ', '+', $as3_secure_key)), 'default_cache_config' => '', 'certificate_authority' => true), '@default' => 'development'));
				$s3 = new AmazonS3();
				if ($as3_site_folder == true)
				{
					if(!empty($as3_directory))
					{
						$as3_directory .= '/' . $this->site_name;
					}
					else
					{
						$as3_directory =  $this->site_name;
					}
				}
				if(empty($as3_directory))
				{
					$single_as3_file = $backup_file;
				}
				else
				{
					$single_as3_file = $as3_directory . '/' . $backup_file;
				}
				
				//$temp = ABSPATH . 'iwp_temp_backup.zip';
				$temp = wp_tempnam('iwp_temp_backup.zip');
				$s3->get_object($as3_bucket, $single_as3_file, array("fileDownload" => $temp));
		   } catch (Exception $e){
			return false;
		   }
			return $temp;
		}
		else{
			return array(
                'error' => 1,
            );
		}
    }
	//IWP Remove ends here

	function get_google_drive_backup($args)
	{
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Client.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Service/Drive.php');
		
		//refresh token 
		$client = new IWP_google_Client();
		$client->setClientId($args['clientID']);
		$client->setClientSecret($args['clientSecretKey']);
		$client->setRedirectUri($args['redirectURL']);
		$client->setScopes(array(
		  'https://www.googleapis.com/auth/drive',
		  'https://www.googleapis.com/auth/userinfo.email',
		  'https://www.googleapis.com/auth/userinfo.profile'));
		  
		//$client->setUseObjects(true);
		
		$accessToken = $args['token'];
		$refreshToken = $accessToken['refresh_token'];
		
		try
		{
			$client->refreshToken($refreshToken);
		}
		catch(Exception $e)
		{	
			echo 'google Error ',  $e->getMessage(), "\n";
			return array("error" => $e->getMessage(), "error_code" => "google_error_refresh_token");
		}
		
		//downloading the file
		$service = new IWP_google_Service_Drive($client);
		
		$file = $service->files->get($args['backup_file']);
		
		$downloadUrl = $file->getDownloadUrl();
		
		$temp = wp_tempnam('iwp_temp_backup.zip');
		
		try
		{
			if ($downloadUrl) 
			{
				$request = new IWP_google_Http_Request($downloadUrl, 'GET', null, null);
				
				$signHttpRequest = $client->getAuth()->sign($request);
				$httpRequest = $client->getIo()->makeRequest($signHttpRequest);
				
				if ($httpRequest->getResponseHttpCode() == 200) {
					file_put_contents($temp, $httpRequest->getResponseBody());
					return $temp;
				} else {
				  // An error occurred.
				  return array("error" => "There is some error.", "error_code" => "google_error_bad_response_code");
				}
			}
			else
			{
				// The file doesn't have any content stored on Drive.
				return array("error" => "Google Drive file doesnt have nay content.", "error_code" => "google_error_download_url");
			}
		}catch(Exception $e)
		{	
			echo 'google Error ',  $e->getMessage(), "\n";
			return array("error" =>$e->getMessage(), "error_code" => "google_error_download_url_catch_excep");
		}
		
		
	}
	
	
	/*
	Google Drive Upload Function:
	
	*/
	
	function google_drive_backup($historyID = 0, $args = '', $uploadid = null, $offset = 0){
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Client.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Http/MediaFileUpload.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Service/Drive.php');
		
		$this -> hisID = $historyID;
		
		$requestParams = $this -> getRequiredData($historyID,"requestParams");
		$upload_loop_break_time = $requestParams['account_info']['upload_loop_break_time'];			//darkcode changed
		$upload_file_block_size = $requestParams['account_info']['upload_file_block_size'];
		$upload_file_block_size = 5 *1024 *1024;
		$del_host_file = $requestParams['args']['del_host_file'];
		$iwp_folder_id = '';
		$sub_folder_id = '';
		$sub_folder_name = $this->site_name;
		
		$task_result = $this->getRequiredData($historyID, "taskResults");
		
		$fileSizeUploaded = 0;
		$resumeURI = false;
		$current_file_num = 0;
		
		if($args == ''){
			//on the next call $args would be ''
			//set $args, $uploadid, $offset  from the DB
			$responseParams = $this -> getRequiredData($historyID,"responseParams");
			
			if(!$responseParams){
			return $this->statusLog($this -> hisID, array('stage' => 'google_drive_upload', 'status' => 'error', 'statusMsg' => 'google Upload failed: Error while fetching table data.','statusCode' => 'google_upload_failed_error_fetching_data'));
			}
			
			$args = $responseParams['gDriveArgs'];
			$prevChunkResults = $responseParams['response_data'];
			if(is_array($prevChunkResults))
			{
				$resumeURI = $prevChunkResults['resumeURI'];
				$fileSizeUploaded = $prevChunkResults['fileSizeUploaded'];
			}
			$current_file_num = $responseParams['current_file_num'];
		}
		$create_sub_folder = $args['gdrive_site_folder'];
		$tempArgs = $args;
		$client = new IWP_google_Client();
		$client->setClientId($args['clientID']);
		$client->setClientSecret($args['clientSecretKey']);
		$client->setRedirectUri($args['redirectURL']);
		$client->setScopes(array(
		  'https://www.googleapis.com/auth/drive',
		  'https://www.googleapis.com/auth/userinfo.email',
		  'https://www.googleapis.com/auth/userinfo.profile'));
		$accessToken = $args['token'];
		$refreshToken = $accessToken['refresh_token'];
		$backup_file = $args['backup_file'];
		
		if(!is_array($backup_file)){
			$temp_backup_file = $backup_file;
			$backup_file = array();
			$backup_file[] = $temp_backup_file;
		}
		
		if(is_array($backup_file)){
			$backup_files_count = count($backup_file);
			$backup_file = $backup_file[$current_file_num];
		}
		
		try{
			$client->refreshToken($refreshToken);
		}
		catch(Exception $e){
			echo 'google Error ',  $e->getMessage(), "\n";
			return array("error" => $e->getMessage(), "error_code" => "google_error_backup_refresh_token");
		}
		
		//$client = new IWP_google_Client();
		//$accessToken = $client->authenticate($accessToken_early['refresh_token']);
		//$client->setAccessToken($accessToken);
		
		$service = new IWP_google_Service_Drive($client);
		
		//create iwp folder folder if it is not present
		try{
			$parameters = array();
			$parameters['q'] = "title = 'infinitewp' and trashed = false and 'root' in parents and 'me' in owners and mimeType= 'application/vnd.google-apps.folder'";
			$files = $service->files->listFiles($parameters);
			$list_result = array();
			$list_result = array_merge($list_result, $files->getItems());
			$list_result = (array)$list_result;
			
			if(empty($list_result)){
				$file = new IWP_google_Service_Drive_DriveFile();
				$file->setTitle('infinitewp');
				$file->setMimeType('application/vnd.google-apps.folder');
				
				$createdFolder = $service->files->insert($file, array(
					'mimeType' => 'application/vnd.google-apps.folder',
				));
				if($createdFolder){
					$createdFolder = (array)$createdFolder;
					$iwp_folder_id = $createdFolder['id'];
				}
			}
			else{
					foreach($list_result as $k => $v){
						$iwp_folder_id = $v->id;
					}
			}
		}catch (Exception $e){
			print "An error occurred: " . $e->getMessage();
			return array('error' => $e->getMessage(), 'error_code' => 'google_error_occured_list_results');
		}
		
		//create sub folder by site name
		if($create_sub_folder){
			$parameters = array();
			$parameters['q'] = "title = '$sub_folder_name' and trashed = false and '$iwp_folder_id' in parents and 'me' in owners and mimeType = 'application/vnd.google-apps.folder'";
			//$parameters['corpus'] = "DEFAULT";
			$files = $service->files->listFiles($parameters);
			$list_result = array();
			$list_result = array_merge($list_result, $files->getItems());
			$list_result = (array)$list_result;
			
			if(empty($list_result)){
				$file = new IWP_google_Service_Drive_DriveFile();
				$file->setTitle($sub_folder_name);
				$file->setMimeType('application/vnd.google-apps.folder');
				
				//setting parent as infinitewpFolder
				$parent = new IWP_google_Service_Drive_ParentReference();
				$parent->setId($iwp_folder_id);
				$file->setParents(array($parent));
				
				$createdFolder = $service->files->insert($file, array(
					'mimeType' => 'application/vnd.google-apps.folder',
				));
				if($createdFolder)
				{
					$createdFolder = (array)$createdFolder;
					$sub_folder_id = $createdFolder['id'];
				}
			}
			else{
				foreach($list_result as $k => $v){
					$sub_folder_id = $v->id;
				}
			}
		}
		
		//Insert a file
		$file = new IWP_google_Service_Drive_DriveFile();
		$file->setTitle(basename($backup_file));
		$file->setMimeType('binary/octet-stream');
		
		// Set the Parent Folder on Google Drive
		$parent = new IWP_google_Service_Drive_ParentReference();
		if(empty($sub_folder_id)){
			$parent->setId($iwp_folder_id);
		}
		else{
			$parent->setId($sub_folder_id);
		}
		$file->setParents(array($parent));
		
		$gDriveID = '';
		try{
			if(false){
				//single upload
				$data = file_get_contents($backup_file);
				$createdFile = (array)$service->files->insert($file, array(
				  'data' => $data,
				  //'mimeType' => 'text/plain',
				));
				$gDriveID = $createdFile['id'];
			}
			
			//multipart upload
			
			if(true){
				// Call the API with the media upload, defer so it doesn't immediately return.
				$client->setDefer(true);
				$request = $service->files->insert($file);
				
				// Create a media file upload to represent our upload process.
				$media = new IWP_google_Http_MediaFileUpload($client, $request, 'application/zip', null, true, $upload_file_block_size);
				$media->setFileSize(filesize($backup_file));
				

				$status = false;
				$handle = fopen($backup_file, "rb");
				fseek($handle, $fileSizeUploaded);
				
				$resArray = array (
				  'status' => 'completed',
				  'backupParentHID' => $historyID,
				);
						
				while (!$status && !feof($handle)){
					iwp_mmb_auto_print('gdrive_chucked_upload');
					$chunk = fread($handle, $upload_file_block_size);
					$statusArray = $media->nextChunk($chunk, $resumeURI, $fileSizeUploaded);
					$status = $statusArray['status'];
					$resumeURI = $statusArray['resumeURI'];
					//$fileSizeUploaded = ftell($handle);
					$fileSizeUploaded = $statusArray['progress'];
					
					$googleTimeTaken = microtime(1) - $GLOBALS['IWP_MMB_PROFILING']['ACTION_START'];
					if(($googleTimeTaken > $upload_loop_break_time)&&($status != true)){
						$chunkResult['resumeURI'] = $resumeURI;
						$chunkResult['fileSizeUploaded'] = $fileSizeUploaded;
						
						echo "<br> file uploaded size in this call: ".$fileSizeUploaded."<br>";
						
						$result_arr = array();
						$result_arr['response_data'] = $chunkResult;
						$result_arr['status'] = 'partiallyCompleted';
						$result_arr['nextFunc'] = 'google_drive_backup';
						$result_arr['gDriveArgs'] = $tempArgs;
						$result_arr['current_file_num'] = $current_file_num;
						
						/* $task_result['task_results'][$historyID]['gDriveOrgFileName'][] = basename($backup_file);
						$task_result['task_results'][$historyID]['gDrive'][] = $gDriveID;
						//$task_result['gDrive'] = basename($backup_file);
						$task_result['gDrive'][] = $gDriveID; */
						
						$this->statusLog($this -> hisID, array('stage' => 'amazonMultiCall', 'status' => 'partiallyCOmpleted', 'statusMsg' => 'nextCall','nextFunc' => 'amazons3_backup', 'task_result' => $task_result, 'responseParams' => $result_arr));
						
						$resArray['status'] = "partiallyCompleted";
						return $resArray;
					}
				}
				
				$result = false;
				if($status != false){
				  $result = $status;
				}
				
				fclose($handle);
				$client->setDefer(false);
				
				$completeBackupResult = (array)$status;
				
				//$gDriveID = $createdFile['id'];	
				$gDriveID = $completeBackupResult['id'];	
			}
		}catch (Exception $e){
			echo "An error occurred: " . $e->getMessage();
			return array("error" => "gDrive Error".$e->getMessage(), "error_code" => "google_error_multipart_upload");
		}
		
		$current_file_num += 1;
		$result_arr = array();
		$result_arr['response_data'] = (isset($createdFile) && !empty($createdFile['id'])) ? $createdFile['id'] : array();
		$result_arr['status'] = "completed";
		$result_arr['nextFunc'] = 'google_drive_completed';
		$result_arr['gDriveArgs'] = $tempArgs;
		$result_arr['current_file_num'] = $current_file_num;
		
		$resArray = array (
		  'status' => 'completed',
		  'backupParentHID' => $historyID,
		);
		
		//$task_result = $this->getRequiredData($historyID, "taskResults");
		$task_result['task_results'][$historyID]['gDriveOrgFileName'][] = basename($backup_file);
		$task_result['task_results'][$historyID]['gDrive'][] = $gDriveID;
		//$task_result['gDrive'] = basename($backup_file);
		$task_result['gDrive'][] = $gDriveID;
		
		if($current_file_num == $backup_files_count){
			$result_arr['nextFunc'] = 'google_drive_completed';
			iwp_mmb_print_flush('Google Drive upload: End');
			unset($task_result['task_results'][$historyID]['server']);
		}
		else{
			$result_arr['status'] = "partiallyCompleted";
			$result_arr['nextFunc'] = 'google_drive_backup';
			$result_arr['response_data'] = false;
			$resArray['status'] = 'partiallyCompleted';
		}
		
		if($del_host_file){
			@unlink($backup_file);
		}
		
		$this->statusLog($this -> hisID, array('stage' => 'gDriveMultiCall', 'status' => 'completed', 'statusMsg' => 'nextCall','nextFunc' => 'google_drive_completed', 'task_result' => $task_result, 'responseParams' => $result_arr));
		
		return $resArray;				
	}
	
	
	function remove_google_drive_backup($args)
	{
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Client.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Service/Drive.php');
		
		$client = new IWP_google_Client();
		$client->setClientId($args['clientID']);
		$client->setClientSecret($args['clientSecretKey']);
		$client->setRedirectUri($args['redirectURL']);
		$client->setScopes(array(
		  'https://www.googleapis.com/auth/drive',
		  'https://www.googleapis.com/auth/userinfo.email',
		  'https://www.googleapis.com/auth/userinfo.profile'));
		  
		//$client->setUseObjects(true);
		
		$accessToken = $args['token'];
		$refreshToken = $accessToken['refresh_token'];
		$backup_file = $args['backup_file'];
		if(!is_array($backup_file))
		{
			$backup_file = array();
			$backup_file[0] = $args['backup_file'];
		}
		
		try
		{
			$client->refreshToken($refreshToken);
		}
		catch(Exception $e)
		{	
			echo 'google Error ',  $e->getMessage(), "\n";
			return array("error" => $e->getMessage(), "error_code" => "google_error_remove_refresh_token");
		}
		
		$service = new IWP_google_Service_Drive($client);
		
		foreach($backup_file as $key => $value)
		{
			try
			{
				$service->files->delete($value);
			}
			catch (Exception $e)
			{
				echo "An error occurred: " . $e->getMessage();
				return array("error" => "gDrive Remove Error".$e->getMessage(), "error_code" => "google_error_delete");
			}
		}
	}
    
    function schedule_next($type, $schedule)
    {
        $schedule = explode("|", $schedule);
        if (empty($schedule))
            return false;
        switch ($type) {
            
            case 'daily':
                
                if (isset($schedule[1]) && $schedule[1]) {
                    $delay_time = $schedule[1] * 60;
                }
                
                $current_hour  = date("H");
                $schedule_hour = $schedule[0];
                if ($current_hour >= $schedule_hour){
                    $time = mktime($schedule_hour, 0, 0, date("m"), date("d") + 1, date("Y"));
					//$time ='0001#'.$current_hour.'|'.$schedule_hour;
					
				}
			
                else{
                    $time = mktime($schedule_hour, 0, 0, date("m"), date("d"), date("Y"));
					//$time ='0000#'.$current_hour.'|'.$schedule_hour;
				}
				$time = time() + 30;
				
			
                break;
            
            
            case 'weekly':
                if (isset($schedule[2]) && $schedule[2]) {
                    $delay_time = $schedule[2] * 60;
                }
                $current_weekday  = date('w');
                $schedule_weekday = $schedule[1];
                $current_hour     = date("H");
                $schedule_hour    = $schedule[0];
                
                if ($current_weekday > $schedule_weekday)
                    $weekday_offset = 7 - ($week_day - $task_schedule[1]);
                else
                    $weekday_offset = $schedule_weekday - $current_weekday;
                
                
                if (!$weekday_offset) { //today is scheduled weekday
                    if ($current_hour >= $schedule_hour)
                        $time = mktime($schedule_hour, 0, 0, date("m"), date("d") + 7, date("Y"));
                    else
                        $time = mktime($schedule_hour, 0, 0, date("m"), date("d"), date("Y"));
                } else {
                    $time = mktime($schedule_hour, 0, 0, date("m"), date("d") + $weekday_offset, date("Y"));
                }
                
                break;
            
            case 'monthly':
                if (isset($schedule[2]) && $schedule[2]) {
                    $delay_time = $schedule[2] * 60;
                }
                $current_monthday  = date('j');
                $schedule_monthday = $schedule[1];
                $current_hour      = date("H");
                $schedule_hour     = $schedule[0];
                
                if ($current_monthday > $schedule_monthday) {
                    $time = mktime($schedule_hour, 0, 0, date("m") + 1, $schedule_monthday, date("Y"));
                } else if ($current_monthday < $schedule_monthday) {
                    $time = mktime($schedule_hour, 0, 0, date("m"), $schedule_monthday, date("Y"));
                } else if ($current_monthday == $schedule_monthday) {
                    if ($current_hour >= $schedule_hour)
                        $time = mktime($schedule_hour, 0, 0, date("m") + 1, $schedule_monthday, date("Y"));
                    else
                        $time = mktime($schedule_hour, 0, 0, date("m"), $schedule_monthday, date("Y"));
                    break;
                }
                
                break;
            default:
                break;
        }
        
        if (isset($delay_time) && $delay_time) {
            $time += $delay_time;
        }
		
        return $time;
    }

    
    //Parse task arguments for info on IWP Admin Panel
   /*  function get_backup_stats()
    {
        $stats = array();
        $tasks = $this->tasks;
        if (is_array($tasks) && !empty($tasks)) {
            foreach ($tasks as $task_name => $info) {
                if (is_array($info['task_results']) && !empty($info['task_results'])) {
                    foreach ($info['task_results'] as $key => $result) {
                        if (isset($result['server']) && !isset($result['error'])) {
                            if (!file_exists($result['server']['file_path'])) {
                                $info['task_results'][$key]['error'] = 'Backup created but manually removed from server.';
                            }
                        }
                    }
                }
                if (is_array($info['task_results']))
                	$stats[$task_name] = $info['task_results'];
            }
        }
        return $stats;
    } */
        
    
/*function get_next_schedules()
    {
        $stats = array();
        $tasks = $this->tasks;
        if (is_array($tasks) && !empty($tasks)) {
            foreach ($tasks as $task_name => $info) {
                $stats[$task_name] = isset($info['task_args']['next']) ? $info['task_args']['next'] : array();
            }
        }
        return $stats;
    }
*/
    
	function remove_old_backups($task_name, $limit = false)
    {
	    //Check for previous failed backups first
        //$this->cleanup();
		
		global $wpdb;
		$table_name = $wpdb->base_prefix . "iwp_backup_status";
        
		//Check for previous failed backups first
        $this->cleanup();
		
        //Remove by limit
        $backups = $this->get_all_tasks();
		
		if ($limit === false) {
			$thisTask = $this->get_this_tasks();
			$requestParams = unserialize($thisTask['requestParams']);
			$limit = $requestParams['args']['limit'];
		}else{
			$limit = ($limit == 1)?0:$limit;
			$fromNewBackup = true;
		}
        /*if ($task_name == 'Backup Now') {
            $num = 0;
        } else {
            $num = 1;
        }*/
        $other_method_backups = iwp_mmb_get_backup_ID_by_taskname('advanced', $task_name);
        $current_backups = $this->get_timestamp_by_label($task_name);
        $all_backups = array();
        $delete_backup = array();
        if (!empty($other_method_backups)) {
        	$all_backups = array_merge($all_backups, $other_method_backups);
        	if (!empty($current_backups)) {
        		$all_backups = array_merge($all_backups, $current_backups);
        		ksort($all_backups);
        		ksort($current_backups);
        		foreach ($other_method_backups as $timestamp => $historyID) {
        			foreach ($current_backups as $time => $value) {
        				if ($time > $timestamp) {
        					$delete_backup[$timestamp] = $timestamp;
        				}
        				break;
        			}
        		}
        	}
        }
        if (!empty($delete_backup)) {
        	$total_backups = count($all_backups);
        	if ($total_backups > $limit) {
        		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/backup.core.class.php');
        		iwp_mmb_define_constant();
        		$backup_instance = new IWP_MMB_Backup_Core();
        		foreach ($delete_backup as $timestamp => $historyID) {
        			$total_backups--;
        			$backup_instance->delete_backup(array('result_id' => $historyID));
        			if ($total_backups<= $limit) {
        				return;
        			}
        		}
        	}
        }
        
		$select_prev_backup = "SELECT historyID, taskResults FROM ".$table_name." WHERE taskName = '".$task_name."' ORDER BY ID DESC LIMIT ".$limit.",100 ";
		
		$select_prev_backup_res = $wpdb->get_results($select_prev_backup, ARRAY_A);
		
		
		foreach ( $select_prev_backup_res as $backup_data ) 
		{
			$deleted = 1;
			$task_result = unserialize($backup_data['taskResults']);
								
			if (isset($task_result['task_results'][$backup_data['historyID']]['server'])) {
				$backup_file = $task_result['task_results'][$backup_data['historyID']]['server']['file_path'];
				if(!is_array($backup_file))
				{
					$temp_backup_file = $backup_file;
					$backup_file = array();
					$backup_file[0] = $temp_backup_file;
				}
				foreach($backup_file as $value)
				{
					@unlink($value);
				} 
			}
			
			$thisRequestParams = $this->getRequiredData($backup_data['historyID'], "requestParams");
			
			if (isset($task_result['task_results'][$backup_data['historyID']]['ftp'])) {
				$ftp_file            = $task_result['task_results'][$backup_data['historyID']]['ftp'];
				$args                = $thisRequestParams['account_info']['iwp_ftp'];
				$args['backup_file'] = $ftp_file;
				$this->remove_ftp_backup($args);
			}
			
			if (isset($task_result['task_results'][$backup_data['historyID']]['amazons3'])) {
				$amazons3_file       = $task_result['task_results'][$backup_data['historyID']]['amazons3'];
				$args                = $thisRequestParams['account_info']['iwp_amazon_s3'];
				$args['backup_file'] = $amazons3_file;
				if(is_new_s3_compatible()){
					require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/s3IWPBackup.php';
					$new_s3_obj = new IWP_MMB_S3_MULTICALL();
					$new_s3_obj->remove_amazons3_backup($args);
				}
				else{
					$this->remove_amazons3_backup_bwd_comp($args);
				}
			}
			
			if (isset($task_result['task_results'][$backup_data['historyID']]['dropbox']) && isset($thisRequestParams['account_info']['iwp_dropbox'])) {
				//To do: dropbox remove
				$dropbox_file       = $task_result['task_results'][$backup_data['historyID']]['dropbox'];
				$args                = $thisRequestParams['account_info']['iwp_dropbox'];
				$args['backup_file'] = $dropbox_file;
				if(!empty($args['dropbox_access_token']) || (empty($args['dropbox_access_token']) &&  time() < 1498608000)){
			  	 	$this->remove_dropbox_backup($args);
			  	}
			}
			
			if (isset($task_result['task_results'][$backup_data['historyID']]['gDrive'])) {
				$gdrive_file       = $task_result['task_results'][$backup_data['historyID']]['gDrive'];
				$args                = $thisRequestParams['account_info']['iwp_gdrive'];
				$args['backup_file'] = $gdrive_file;
				$this->remove_google_drive_backup($args);
			}
			
			$delete_query = "DELETE FROM ".$table_name." WHERE historyID = ".$backup_data['historyID'];
												
			$deleteRes = $wpdb->query($delete_query);
		}
		if ($fromNewBackup) {
			return ($deleted)?true:false;
		}else{
			return true;
		}
    
    }
	
	function cleanup()
    {
		$tasks = $this->get_all_tasks(); //all backups task results array.
		$requestParams = $this->get_all_tasks(true);
		$thisTask = $this->get_this_tasks();
        $backup_folder     = WP_CONTENT_DIR . '/' . md5('iwp_mmb-client') . '/iwp_backups/';
        $backup_folder_new = IWP_BACKUP_DIR . '/';
		$backup_temp_folder = IWP_PCLZIP_TEMPORARY_DIR;
        $files             = glob($backup_folder . "*");
        $new               = glob($backup_folder_new . "*");
		$new_temp               = glob($backup_temp_folder . "*");
        
        //Failed db files first
        $db_folder = IWP_DB_DIR . '/';
        $db_files  = glob($db_folder . "*");
        if (is_array($db_files) && !empty($db_files)) {
            foreach ($db_files as $file) {
                @unlink($file);
            }
			@unlink(IWP_BACKUP_DIR.'/iwp_db/index.php');
            @rmdir(IWP_DB_DIR);
        }
        
        
        //clean_old folder?
        if ((count($files) == 1 && basename($files[0]) == 'index.php') || (!empty($files))) {  //USE  (!empty($files)
			foreach ($files as $file) {
				@unlink($file);
            }
            @rmdir(WP_CONTENT_DIR . '/' . md5('iwp_mmb-client') . '/iwp_backups');
            @rmdir(WP_CONTENT_DIR . '/' . md5('iwp_mmb-client'));
        }
        
        if (!empty($new)) {
	        foreach ($new as $b) {
	            $files[] = $b;
	        }
        }
			if (!empty($new_temp)) {
				foreach ($new_temp as $c) {
					$files[] = $c;
				}
			}
        $deleted = array();
        
        $results = array();
        if (is_array($files) && count($files)) {
            $cloudFailedBackup = array();
            $failedBackupHisID = array();
            if (!empty($tasks)) {
                foreach ((array) $tasks as $taskName => $task) {
                    //if (isset($task) && count($task)) {
                    //    foreach ($task as $backup) {
					if (isset($task['task_results']) && count($task['task_results'])) {
                        foreach ($task['task_results'] as $historyID => $backup) {
                            if (isset($backup['server'])) {
								$this_backup_file = $backup['server']['file_path'];
								if(is_array($this_backup_file))
								{
									foreach($this_backup_file as $single_backup_file)
									{	if (!empty($requestParams[$taskName]['requestParams'][$historyID]['account_info']) && $thisTask['historyID'] != $historyID) {
											$cloudFailedBackup[]= $single_backup_file;
											$failedBackupHisID[$historyID]=$historyID;
										}
										$results[] = $single_backup_file;
									}
								}
								else
								{
									if (!empty($requestParams[$taskName]['requestParams'][$historyID]['account_info']) && $thisTask['historyID'] != $historyID) {
										$cloudFailedBackup[]= $this_backup_file;
										$failedBackupHisID[$historyID]=$historyID;
									}
									$results[] = $this_backup_file;
								}
								$results[]	 = 'DE_clMemoryPeak.'.$historyID.'.txt';
								$results[]  = 'DE_clMemoryUsage.'.$historyID.'.txt';
								$results[] 	 = 'DE_clTimeTaken.'.$historyID.'.txt';
								$results[]	 = 'DE_clCPUUsage.'.$historyID.'.txt';
                            }
                        }
                    }
                }
            }

            $pheonixBackup = $GLOBALS['iwp_backup_core']->get_backup_history();
            if (!empty($pheonixBackup)) {
            	foreach ($pheonixBackup as $timestamp => $backup) {
            		if (!empty($backup['plugins'])) {
            			$results = array_merge($results, $backup['plugins']);
            		}
            		if (!empty($backup['themes'])) {
            			$results = array_merge($results, $backup['themes']);
            		}
            		if (!empty($backup['uploads'])) {
            			$results = array_merge($results, $backup['uploads']);
            		}
            		if (!empty($backup['others'])) {
            			$results = array_merge($results, $backup['others']);
            		}
            		if (!empty($backup['more'])) {
            			$results = array_merge($results, $backup['more']);
            		}
            		if (!empty($backup['db'])) {
            			$results[] = $backup['db'];
            		}
            		$results[] = $backup['backup_file_basename'];
            	}
            }

            $num_deleted = 0;
            foreach ($files as $file) {
                if (((!in_array($file, $results) && !in_array(basename($file), $results)) || in_array($file, $failedBackupHisID)) && basename($file) != 'index.php') {
                    @unlink($file);
                    // $deleted[] = basename($file);
					$deleted[] = $file;
                    $num_deleted++;
                }
            }

            if (!empty($failedBackupHisID)) {
            	$this->remove_failed_backups_by_hisID($failedBackupHisID);
            }
        }
        return $deleted;
    }
	
    
    /**
     * Delete specified backup
     * Args: $task_name, $result_id
     */
    
    function delete_backup($args)
    {
		
        if (empty($args))
            return false;
			
		global $wpdb;
		$table_name = $wpdb->base_prefix . "iwp_backup_status";
		
        extract($args);
         
		$tasks = $this->get_requested_task($result_id);
		
		$backup = $tasks['task_results'][$result_id];
		
		$requestParams = unserialize($tasks['requestParams']);
		$args = $requestParams['secure']['account_info'];
		
        if (isset($backup['server'])) {
			$backup_file = $backup['server']['file_path'];
			if(is_array($backup_file))
			{
				foreach($backup_file as $value)
				{
					@unlink($value);
				}
			}
			else
			{
				@unlink($backup_file);
			}
        }        
        

        //Remove from ftp
        if (isset($backup['ftp'])) {
            $ftp_file            = $backup['ftp'];
            $args                = $args['iwp_ftp'];
            $args['backup_file'] = $ftp_file;
            $this->remove_ftp_backup($args);
        }
        
        if (isset($backup['amazons3'])) {
            $amazons3_file       = $backup['amazons3'];
            $args                = $args['iwp_amazon_s3'];
            $args['backup_file'] = $amazons3_file;
            if(is_new_s3_compatible()){
				require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/s3IWPBackup.php';
				$new_s3_obj = new IWP_MMB_S3_MULTICALL();
				$new_s3_obj->remove_amazons3_backup($args);
			}
			else{
				$this->remove_amazons3_backup_bwd_comp($args);
			}
        }
        
        if (isset($backup['dropbox'])) {
        	$dropbox_file       = $backup['dropbox'];
            $args                = $args['iwp_dropbox'];
            $args['backup_file'] = $dropbox_file;
            $this->remove_dropbox_backup($args);
        }
		
		if (isset($backup['gDrive'])) {
        	$g_drive_file       = $backup['gDrive'];
            $args                = $args['iwp_gdrive'];
            $args['backup_file'] = $g_drive_file;
            $this->remove_google_drive_backup($args);
        }
		
		$delete_query = "DELETE FROM ".$table_name." WHERE historyID = ".$result_id;
												
		$deleteRes = $wpdb->query($delete_query);
		
        return true;
        
    }
    
    function wpdb_reconnect(){
    	global $wpdb;
		$old_wpdb = $wpdb;
    	//Reconnect to avoid timeout problem after ZIP files
      	if(class_exists('wpdb') && function_exists('wp_set_wpdb_vars')){
      		if ($wpdb->use_mysqli) {
				@mysqli_close($wpdb->dbh);
			} else {
				if (function_exists('mysql_close')){
					@mysql_close($wpdb->dbh);
				}
			}
        	$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
        	wp_set_wpdb_vars(); 
			$wpdb->options = $old_wpdb->options;//fix for multi site full backup
      	}
    }
    
  function replace_htaccess($url, $remote_abspath)
	{
		global $wp_filesystem;
		//$file = @file_get_contents(ABSPATH.'.htaccess');
		$file = $wp_filesystem->get_contents($remote_abspath.'.htaccess');
		if ($file && strlen($file)) {
			$args    = parse_url($url);        
			$string  = rtrim($args['path'], "/");
			$regex   = "/BEGIN WordPress(.*?)RewriteBase(.*?)\n(.*?)RewriteRule \.(.*?)index\.php(.*?)END WordPress/sm";
			$replace = "BEGIN WordPress$1RewriteBase " . $string . "/ \n$3RewriteRule . " . $string . "/index.php$5END WordPress";
			$file    = preg_replace($regex, $replace, $file);
			//@file_put_contents(ABSPATH.'.htaccess', $file);
			$wp_filesystem->put_contents($remote_abspath.'.htaccess', $file);
		}
	}
    
	function check_cron_remove(){
		if(empty($this->tasks) || (count($this->tasks) == 1 && isset($this->tasks['Backup Now'])) ){
			wp_clear_scheduled_hook('iwp_client_backup_tasks');
			exit;
		}
	}

	public function readd_tasks( $params = array() ){
		global $iwp_mmb_core;
		
		if( empty($params) || !isset($params['backups']) )
			return $params;
		
		$before = array();
		$tasks = $params['backups'];
		if( !empty($tasks) ){
			$iwp_mmb_backup = new IWP_MMB_Backup();
			
			if( function_exists( 'wp_next_scheduled' ) ){
				if ( !wp_next_scheduled('iwp_client_backup_tasks') ) {
					wp_schedule_event( time(), 'tenminutes', 'iwp_client_backup_tasks' );
				}
			}
			
			foreach( $tasks as $task ){
				$before[$task['task_name']] = array();
				
				if(isset($task['secure'])){
					if($decrypted = $iwp_mmb_core->_secure_data($task['secure'])){
						$decrypted = maybe_unserialize($decrypted);
						if(is_array($decrypted)){
							foreach($decrypted as $key => $val){
								if(!is_numeric($key))
									$task[$key] = $val;							
							}
							unset($task['secure']);
						} else 
							$task['secure'] = $decrypted;
					}
					
				}
				if (isset($task['account_info']) && is_array($task['account_info'])) { //only if sends from panel first time(secure data)
					$task['args']['account_info'] = $task['account_info'];
				}
				
				$before[$task['task_name']]['task_args'] = $task['args'];
				$before[$task['task_name']]['task_args']['next'] = $iwp_mmb_backup->schedule_next($task['args']['type'], $task['args']['schedule']);
			}
		}
		update_option('iwp_client_backup_tasks', $before);
		
		unset($params['backups']);
		return $params;
	}
	
	function is_server_writable(){
		if((!defined('FTP_HOST') || !defined('FTP_USER') || !defined('FTP_PASS')) && (get_filesystem_method(array(), ABSPATH) != 'direct'))
			return false;
		else
			return true;
	}
		
		function prepareBackupFileDetails($args){
			//Prepare .zip file name
			extract($args['args']);   //{type, what, ..etc}
			extract($args);           //{task_name, secure, mechanism}
			
			$hash        = md5(microtime(true).uniqid('',true).substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, rand(20,60)));
			$label       = $type ? $type : 'manual';
			$backup_file_name = $this->site_name . '_' . $label . '_' . $what . '_' . date('Y-m-d') . '_' . $hash . '.zip';
			$backup_file = IWP_BACKUP_DIR . '/' . $this->site_name . '_' . $label . '_' . $what . '_' . date('Y-m-d') . '_' . $hash . '.zip';
			$backup_url  = content_url() . '/infinitewp/backups/' . $this->site_name . '_' . $label . '_' . $what . '_' . date('Y-m-d') . '_' . $hash . '.zip';
			$this -> backup_url = $backup_url;
			
			if(empty($account_info))
			{
				$account_info = array();
			}
			
			return array(
				'backup_file' => $backup_file,
				'backup_url' => $backup_url,
				'account_info' => $account_info,
				'what' => $what
			);
		}

		function get_timestamp_by_label($label){
			$new_backup_keys = array();
			global $wpdb;
			$table_name = $wpdb->base_prefix . "iwp_backup_status";
			$select_prev_backup = "SELECT historyID, lastUpdateTime FROM ".$table_name." WHERE taskName = '".$label."' ORDER BY ID DESC ";
			$select_prev_backup_res = $wpdb->get_results($select_prev_backup, ARRAY_A);
			foreach ($select_prev_backup_res as $key => $value) {
				$new_backup_keys[$value['lastUpdateTime']]= $value['historyID'];
			}
			return $new_backup_keys;
		}
	}
	
	if( !function_exists('initialize_manual_debug') ){
		function initialize_manual_debug($conditions = ''){
			if(file_exists(WP_CONTENT_DIR . '/DE_clMemoryPeak.php')){ @unlink(WP_CONTENT_DIR . '/DE_clMemoryPeak.php');}
			if(file_exists(WP_CONTENT_DIR . '/DE_clMemoryUsage.php')){ @unlink(WP_CONTENT_DIR . '/DE_clMemoryUsage.php');}
			if(file_exists(WP_CONTENT_DIR . '/DE_clTimeTaken.php')){ @unlink(WP_CONTENT_DIR . '/DE_clTimeTaken.php');}
			if(file_exists(WP_CONTENT_DIR . '/DE_clCPUUsage.php')){ @unlink(WP_CONTENT_DIR . '/DE_clCPUUsage.php');}
			if (defined('DISABLE_IWP_MULTICALL_DEBUG') && DISABLE_IWP_MULTICALL_DEBUG) {
				return ;
			}

			global $debug_count, $every_count, $iwp_multicall_hisID;
			$memoryPeakLog	 = 'DE_clMemoryPeak.'.$iwp_multicall_hisID.'.txt';
			$memoryUsageLog  = 'DE_clMemoryUsage.'.$iwp_multicall_hisID.'.txt';
			$timeTakenLog 	 = 'DE_clTimeTaken.'.$iwp_multicall_hisID.'.txt';
			$cpuUsageLog	 = 'DE_clCPUUsage.'.$iwp_multicall_hisID.'.txt';
			$debug_count = 0;
			$every_count = 0;
			if (function_exists('sys_getloadavg')) {
				$cpu_load = sys_getloadavg();
				$current_cpu_load = $cpu_load[0];
			}
			$this_memory_peak_in_mb = memory_get_peak_usage();
			$this_memory_peak_in_mb = $this_memory_peak_in_mb / 1048576;
			$this_memory_in_mb = memory_get_usage();
			$this_memory_in_mb = $this_memory_in_mb / 1048576;
			$this_time_taken = microtime(true) - $GLOBALS['IWP_MMB_PROFILING']['ACTION_START'];
			
			file_put_contents(IWP_BACKUP_DIR . '/'.$memoryPeakLog,$debug_count . $printText . "  " . round($this_memory_peak_in_mb, 2) ."\n");
			file_put_contents(IWP_BACKUP_DIR . '/'.$memoryUsageLog,$debug_count . $printText . "  " . round($this_memory_in_mb, 2) ."\n");
			file_put_contents(IWP_BACKUP_DIR . '/'.$timeTakenLog,$debug_count . $printText . "  " . round($this_time_taken, 2) ."\n");
			file_put_contents(IWP_BACKUP_DIR . '/'.$cpuUsageLog,$debug_count . $printText . "  " . $current_cpu_load ."\n");
}
	}
	
	if( !function_exists('manual_debug') ){
		function manual_debug($conditions = '', $printText = '', $forEvery = 0){
			if (defined('DISABLE_IWP_MULTICALL_DEBUG') && DISABLE_IWP_MULTICALL_DEBUG) {
				return ;
			}

			global $debug_count;
			$debug_count++;
			$printText = '  ' . $printText; 
			
			global $every_count;
			//$conditions = 'printOnly';
			
			if(empty($forEvery)){
				print_memory_debug($debug_count, $conditions, $printText);
			}
			else{
				$every_count++;
				if($every_count % $forEvery == 0){
					print_memory_debug($debug_count, $conditions, $printText);
					return true;
				}
			}
		}
	}
	
	if( !function_exists('print_memory_debug') ){
		function print_memory_debug($debug_count, $conditions = '', $printText = ''){
			global $iwp_multicall_hisID;
			$this_memory_peak_in_mb = memory_get_peak_usage();
			$this_memory_peak_in_mb = $this_memory_peak_in_mb / 1048576;
			$this_memory_in_mb = memory_get_usage();
			$this_memory_in_mb = $this_memory_in_mb / 1048576;
			$this_time_taken = microtime(true) - $GLOBALS['IWP_MMB_PROFILING']['ACTION_START'];
			$current_cpu_load = 0;
			$memoryPeakLog	 = 'DE_clMemoryPeak.'.$iwp_multicall_hisID.'.txt';
			$memoryUsageLog  = 'DE_clMemoryUsage.'.$iwp_multicall_hisID.'.txt';
			$timeTakenLog 	 = 'DE_clTimeTaken.'.$iwp_multicall_hisID.'.txt';
			$cpuUsageLog	 = 'DE_clCPUUsage.'.$iwp_multicall_hisID.'.txt';
			if (function_exists('sys_getloadavg')) {
				$cpu_load = sys_getloadavg();
				$current_cpu_load = $cpu_load[0];
			}

			if($conditions == 'printOnly'){
				if($this_memory_peak_in_mb >= 34){
					file_put_contents(IWP_BACKUP_DIR . '/'.$memoryPeakLog,$debug_count . $printText . "  " . round($this_memory_peak_in_mb, 2) ."\n",FILE_APPEND);
					file_put_contents(IWP_BACKUP_DIR . '/'.$memoryUsageLog,$debug_count . $printText . "  " . round($this_memory_in_mb, 2) ."\n",FILE_APPEND);
					file_put_contents(IWP_BACKUP_DIR . '/'.$timeTakenLog,$debug_count . $printText . "  " . round($this_time_taken, 2) ."\n",FILE_APPEND);
					file_put_contents(IWP_BACKUP_DIR . '/'.$cpuUsageLog,$debug_count . $printText . "  " . $current_cpu_load ."\n",FILE_APPEND);
				}
			}
			else{
				file_put_contents(IWP_BACKUP_DIR . '/'.$memoryPeakLog,$debug_count . $printText . "  " . round($this_memory_peak_in_mb, 2) ."\n",FILE_APPEND);
				file_put_contents(IWP_BACKUP_DIR . '/'.$memoryUsageLog,$debug_count . $printText . "  " . round($this_memory_in_mb, 2) ."\n",FILE_APPEND);
				file_put_contents(IWP_BACKUP_DIR . '/'.$timeTakenLog,$debug_count . $printText . "  " . round($this_time_taken, 2) ."\n",FILE_APPEND);
					file_put_contents(IWP_BACKUP_DIR . '/'.$cpuUsageLog,$debug_count . $printText . "  " . $current_cpu_load ."\n",FILE_APPEND);

			}
		}
	}
	
	if( !function_exists('print_debugg') ){
		function print_debugg($printText = '', $printVal = null, $forEvery = 0, $conditions = ''){
			static $print_count = 0;
			$print_count++;
			if($print_count > $forEvery){
				file_put_contents(WP_CONTENT_DIR . '/DE_clientPluginSIde.php',"\n -----".$printText."------- ".var_export($printVal,true)."\n",FILE_APPEND);
				$print_count = 0;
			}
		}
	}
	
	if( !function_exists('refresh_iwp_files_db') ){
		function refresh_iwp_files_db($this_file_id = 0, $field = 'thisFileDetails' ){
			global $wpdb, $iwp_db_upgrade_error;
			$this_table_name = $wpdb->base_prefix . 'iwp_file_list';			//in case, if we are changing table name.
			$result = true;
                        
			$IWP_FILE_LIST_TABLE_VERSION =	iwp_mmb_get_site_option('iwp_file_list_table_version');
			
			//write in db and refresh for_every_count,  all_files_detail;
			if($wpdb->get_var("SHOW TABLES LIKE '$this_table_name'") == $this_table_name) {
				$result = $wpdb->query('TRUNCATE TABLE ' . $this_table_name );
				$error_msg = 'Unable to empty File list table : <span style="font-weight:700;">' . $wpdb->last_error.'</span>' ;
				if(version_compare($IWP_FILE_LIST_TABLE_VERSION, '1.1') == -1){
					$result = iwp_create_file_list_table();
					$error_msg = 'Unable to update File list table : <span style="font-weight:700;">' . $iwp_db_upgrade_error.'</span>' ;
				}
			}
			else{
				$result = iwp_create_file_list_table();
				$error_msg = 'Unable to create File list table : <span style="font-weight:700;">'.$iwp_db_upgrade_error.'</span>';
			}
			
			if($result === false){
				return array( 'error' => $error_msg);
			}
			return true;
		}
	}
	
	if(!function_exists('iwp_create_file_list_table')){
		function iwp_create_file_list_table(){
			global $wpdb, $iwp_db_upgrade_error;
			if(method_exists($wpdb, 'get_charset_collate')){
				$charset_collate = $wpdb->get_charset_collate();
			}
			$table_created = false;
			
			$IWP_FILE_LIST_TABLE_VERSION =	iwp_mmb_get_site_option('iwp_file_list_table_version');
			$table_name = $wpdb->base_prefix . "iwp_file_list";
			
			if (!empty($charset_collate)){
				$cachecollation = $charset_collate;
			}
			else{
				$cachecollation = ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci ';
			}
			
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name){
				$sql = "
					CREATE TABLE IF NOT EXISTS $table_name (
					  `ID` int(11) NOT NULL AUTO_INCREMENT,
						`thisFileDetails` text,
						`thisFileCount` int(11) DEFAULT NULL,
						`thisFileHeader` text,
						`thisFileName` varchar(255) DEFAULT NULL,
						`thisFileNameHash` varchar(32) DEFAULT NULL,
						UNIQUE KEY `thisFileNameHash` (`thisFileNameHash`(32)),
						PRIMARY KEY (`ID`)
					)".$cachecollation." ;
				";
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta($sql);
				if($wpdb->last_error !== '') {
					$iwp_db_upgrade_error = $wpdb->last_error;
				}
				if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name){
					$table_created = true;
					update_option( "iwp_file_list_table_version", '1.1');
				}
			}
			else if(version_compare($IWP_FILE_LIST_TABLE_VERSION, '1.1') == -1){
				if(iwp_alter_file_list_table()){
				$table_created = true;
				}
			}
			return $table_created;
		}
	}
	
	if( !function_exists('get_from_iwp_files_db') ){
		function get_from_iwp_files_db($this_file_id = 0, $field = 'thisFileDetails' ){
			global $wpdb;
			$this_table_name = 'iwp_file_list';			//in case, if we are changing table name.
			//$field = 'thisFileDetails';
			
			// $this_obj = new IWP_MMB_Backup_Multicall();
			// $this_obj->wpdb_reconnect();
			
			$this_file_id = $this_file_id + 1;
			//write in db and refresh for_every_count,  all_files_detail;
			$all_files_detail = $wpdb->get_row("SELECT ".$field." FROM " . $wpdb->base_prefix. $this_table_name . " WHERE ID = " . $this_file_id );
			
			if(!empty($all_files_detail))
			{
				$all_files_detail = (array)$all_files_detail;
				if(!empty($all_files_detail[$field])){
					return unserialize($all_files_detail[$field]);
				}
				else{
					return array( 'error' => 'emptyValues');
				}
			}
			else{
				return array( 'error' => $wpdb->last_error);
			}
		}
	}
	
	if( !function_exists('get_iwp_files_db_count') ){
		function get_iwp_files_db_count($what_type = 'files', $how_many = 'total'){
			global $wpdb;
			$this_table_name = 'iwp_file_list';
			
			if($what_type == 'files'){
				$result = $wpdb->get_var( "SELECT COUNT(*) FROM " . $wpdb->base_prefix . $this_table_name);
			}
			else if($what_type == 'headers'){
				$result = $wpdb->get_var( "SELECT COUNT(*) FROM " . $wpdb->base_prefix . $this_table_name . " WHERE thisFileHeader IS NOT NULL");
			}
			
			if($result !== false)
			{
				return (int)$result;
			}
			else{
				return array( 'error' => $wpdb->last_error);
			}
		}
	}
	
	if( !function_exists('delete_in_iwp_files_db') ){
		function delete_in_iwp_files_db($ID = 0){
			global $wpdb;
			$this_table_name = $wpdb->base_prefix . 'iwp_file_list';
			$ID = $ID + 1;
			$result = $wpdb->delete( $this_table_name, array( 'ID' => $ID ), array( '%d' ) );
			
			return $result;
		}
	}
	
	if( !function_exists('save_in_iwp_files_db') ){
		function save_in_iwp_files_db($for_every = 0, $this_file_details = array(), $this_header_details = array(), $action = 'insert', $ID = 0){
			//assuming insert only happens for file adding; update happens only for header adding.
			static $for_every_count; $for_every_count++;
			static $all_files_header_detail = array();
			if(!empty($this_file_details)){
				$all_files_header_detail[] = $this_file_details;
			}
			else{
				$all_files_header_detail[] = $this_header_details;
			}

			global $wpdb;
			$this_table_name = 'iwp_file_list';			//in case, if we are changing table name.
			$this_insertID = 0;
			$result = true;
			
			if($for_every_count >= $for_every){
				//write in db and refresh for_every_count,  all_files_header_detail;
				//$this_obj = new IWP_MMB_Backup_Multicall();
				//$this_obj->wpdb_reconnect();
				
				foreach($all_files_header_detail as $k => $v){
					if($action == 'insert'){
						$is_already = $wpdb->get_row("SELECT * FROM " . $wpdb->base_prefix. $this_table_name . " WHERE thisFileName = '". $v['stored_filename'].$v['splitFilename']."' AND thisFileNameHash = '".md5($v['stored_filename'].$v['splitFilename'])."'" );
						if(empty($is_already)){
							$wpdb->query("SET @@auto_increment_increment=1");
							$result = $wpdb->insert($wpdb->base_prefix . $this_table_name, array('thisFileDetails' => serialize($v), 'thisFileCount' => $k, 'thisFileName' => $v['stored_filename'].$v['splitFilename'],'thisFileNameHash'=>md5($v['stored_filename'].$v['splitFilename'])), array( '%s', '%d', '%s', '%s' ));
						}
					}
					else if($action == 'update'){
						$ID = $ID + 1;
						$result = $wpdb->update($wpdb->base_prefix . $this_table_name, array('thisFileHeader' => serialize($v), ), array( 'ID' => $ID), array('%s'), array('%d'));
					}
					if($result)
					{
						if($action == 'insert'){
							$this_insertID = $wpdb->insert_id;
						}
					}elseif ($result === false) {
						if($action == 'update'){
							return array('error' => 1);
						}
					}
				}
				$for_every_count = 0;
				$all_files_header_detail = array();
				if($action == 'insert'){
					$is_break = check_and_break_iwp();
					if($is_break){
						return array( 'break' => $this_insertID );
					}
				}
			}
			return false;
		}
	}
	
	if( !function_exists('check_and_break_iwp') ){
		function check_and_break_iwp(){
			$timeLimit = 18;
			if (defined('IWP_FILE_LIST_BREAK_TIME') && IWP_FILE_LIST_BREAK_TIME) {
				$timeLimit = IWP_FILE_LIST_BREAK_TIME;
			}
			if((microtime(true) - $GLOBALS['IWP_MMB_PROFILING']['ACTION_START']) > $timeLimit){
				return true;
			}
			else{
				return false;
			}
		}
}

	if( !function_exists('check_and_break_iwp_test') ){
		function check_and_break_iwp_test(){
			static $this_count;
			$this_count++;
			if($this_count >= 20){
				$this_count = 0;
				return true;
			}
			else{
				return false;
			}
		}
	}
	
	if( !function_exists('debug_put') ){
		function debug_put($values, $string){
			file_put_contents(WP_CONTENT_DIR . '/DE_clientPluginSIde.php',"\n -----".$string."------- ".var_export($values,true)."\n",FILE_APPEND);
		}
	}
	
	if( !function_exists('is_new_s3_compatible') ){
		function is_new_s3_compatible(){
			if(phpversion() >= '5.3.3'){
				return true;
			}
			return false;
		}
	}

	if( !function_exists('is_new_dropbox_compatible') ){
		function is_new_dropbox_compatible(){
			if(version_compare(phpversion() , '5.3.3', '>=')){
				return true;
			}
			return false;
		}
	}
	
	if(!function_exists('iwp_alter_file_list_table')){
		function iwp_alter_file_list_table(){
			$altered = true;
			$IWP_FILE_LIST_TABLE_VERSION =	iwp_mmb_get_site_option('iwp_file_list_table_version');
			$failed_alter = false;
			if(version_compare($IWP_FILE_LIST_TABLE_VERSION, '1.1') != -1){
				return true;
			}
			
			/*upgrade file list table version from 1.0 to 1.1*/
			if(version_compare($IWP_FILE_LIST_TABLE_VERSION, '1.0', '=')){
				if(!alter_iwp_filelisttable_1_1()){
					$altered = false;
				}
			}
			return $altered;
		}
	}

	if(!function_exists('alter_iwp_filelisttable_1_1')){
		function alter_iwp_filelisttable_1_1(){
			global $wpdb, $iwp_db_upgrade_error;
			if(method_exists($wpdb, 'get_charset_collate')){
				$charset_collate = $wpdb->get_charset_collate();
			}	
			$table_name = $wpdb->base_prefix . "iwp_file_list";

			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
				if (!empty($charset_collate)){
					$cachecollation = str_ireplace('DEFAULT ', '', $charset_collate);
				}
				else{
					$cachecollation = ' CHARACTER SET utf8 COLLATE utf8_general_ci ';
				}

				$sql = array();

				$columnData = $wpdb->get_var("SHOW COLUMNS FROM $table_name WHERE Field = 'thisFileNameHash'");
                                if(empty($columnData)) {
                                    $sql[] = "ALTER TABLE $table_name ADD `thisFileNameHash` VARCHAR(32) $cachecollation NULL DEFAULT NULL AFTER `thisFileName`";
                                    $sql[] = "ALTER IGNORE TABLE $table_name ADD UNIQUE `thisFileNameHash` (`thisFileNameHash`(32))";
                                }
                                $indexData = $wpdb->get_var("SHOW KEYS FROM $table_name WHERE Key_name = 'thisFileName'");
                                if(!empty($indexData)){
                                    $sql[] = "ALTER TABLE $table_name DROP INDEX thisFileName;";
                                }

				//Running the alter queries to the table
				foreach($sql as $v){
					if(!$wpdb->query($v)){
						$failed_alter = true;
						$iwp_db_upgrade_error = $wpdb->last_error;
					}
				}
				if(!$failed_alter){
					update_option( "iwp_file_list_table_version", '1.1');
					return true;
				}
				return false;
			}
		}
	}
if (!function_exists('iwp_modify_table_description')) {
	
	function iwp_modify_table_description($table_data){
		$temp_table = array();
		foreach ($table_data as $key => $value) {
			$temp = $table_data[$key];
			$temp_table[$value['Field']] = $table_data[$key];
		}
		return $temp_table;
	}
}

if( !function_exists('upgradeOldDropBoxBackupList')){
	function upgradeOldDropBoxBackupList($dropBoxInfo){
		if (!isset($dropBoxInfo['dropbox_access_token']) && empty($dropBoxInfo['dropbox_access_token']) ) {
			return false;
		}
		global $wpdb;
		$table_name = $wpdb->base_prefix . "iwp_backup_status";
				
		$rows = $wpdb->get_results("SELECT ID, taskName, taskResults, requestParams FROM ".$table_name." ORDER BY ID DESC",  ARRAY_A);
		if (empty($rows)) {
			return false;
		}
		foreach ($rows as $ID => $taskArray) {
			$requestParams = unserialize($taskArray['requestParams']);
			$accountInfo = $requestParams['account_info'];
			if (isset($accountInfo['iwp_dropbox']) && isset($accountInfo['iwp_dropbox']['oauth_token']) && !empty($accountInfo['iwp_dropbox']['oauth_token'])) {
				$requestParams['account_info']['iwp_dropbox']['consumer_key'] = '';
				$requestParams['account_info']['iwp_dropbox']['consumer_secret'] = '';
				$requestParams['account_info']['iwp_dropbox']['oauth_token'] = '';
				$requestParams['account_info']['iwp_dropbox']['oauth_token_secret'] = '';
				$requestParams['account_info']['iwp_dropbox']['dropbox_app_key'] = $dropBoxInfo['dropbox_app_key'];
				$requestParams['account_info']['iwp_dropbox']['dropbox_app_secure_key'] = $dropBoxInfo['dropbox_app_secure_key'];
				$requestParams['account_info']['iwp_dropbox']['dropbox_access_token'] = $dropBoxInfo['dropbox_access_token'];
				$update = $wpdb->update($table_name,array( 'requestParams' => serialize($requestParams)),array( 'ID' => $taskArray['ID']),array('%s'),array('%d'));
			}
		}
	}
}
/*if( function_exists('add_filter') ){
	add_filter( 'iwp_website_add', 'IWP_MMB_Backup::readd_tasks' );
}*/

?>