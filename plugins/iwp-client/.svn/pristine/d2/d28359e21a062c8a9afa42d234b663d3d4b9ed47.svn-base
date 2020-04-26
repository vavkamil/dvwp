<?php

class IWP_BACKUP_REPO_TEST {

	public function repositoryTestConnection($account_info){
		if(isset($account_info['iwp_ftp']) && !empty($account_info)) {
			$return = $this->FTPTestConnection($account_info['iwp_ftp']);
		}
		  
		if(isset($account_info['iwp_amazon_s3']) && !empty($account_info['iwp_amazon_s3'])) {
			if(phpversion() >= '5.3.3'){
				require_once($GLOBALS['iwp_mmb_plugin_dir'] . '/lib/amazon/s3IWPBackup.php');
				$return = iwpRepositoryAmazons3($account_info['iwp_amazon_s3']);
			}
			else{
				$return = $this->repositoryAmazons3BwdComp($account_info['iwp_amazon_s3']);
			}
		}
		  
		if (isset($account_info['iwp_dropbox']) && !empty($account_info['iwp_dropbox'])) {
			$return = $this->backupRepositoryDropbox($account_info['iwp_dropbox']);
		}	
		  
		if (isset($account_info['iwp_gdrive']) && !empty($account_info['iwp_gdrive'])) {
		 	$return = $this->repositoryGDrive($account_info['iwp_gdrive']);
		}
		return $return;	
	}

	public function FTPTestConnection($args){
		extract($args);	
		$ftp_hostname = $ftp_hostname ? $ftp_hostname : $hostName;
		$ftp_username = $ftp_username ? $ftp_username : $hostUserName;
		$ftp_password = $ftp_password ? $ftp_password : $hostPassword;
		$FTPBase = $ftp_remote_folder ? $ftp_remote_folder : $FTPBase;
		
		if(empty($ftp_hostname)){
			return array('status' => 'error',
				'errorMsg' => 'Inavlid FTP host',
				);	
		}

	    if(isset($use_sftp) && $use_sftp == 1) {
	    	return $this->SFTPTestConnection($args);
	    }
		else{
			return $this->simpleFTPTestConnection($args);
		}

	}
	public function simpleFTPTestConnection($args){
		global $iwp_mmb_core;
		extract($args);
        //Args: $ftp_username, $ftp_password, $ftp_hostname, $backup_file, $ftp_remote_folder, $ftp_site_folder
        $port = $ftp_port ? $ftp_port : 21; //default port is 21
        if (!empty($ftp_ssl)) {
            if (function_exists('ftp_ssl_connect')) {
                $conn_id = ftp_ssl_connect($ftp_hostname,$port);
                if ($conn_id === false) {
                	return array(
                			'error' => 'Failed to connect to host',
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
                        'error' => 'Failed to connect to host',
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
                'error' => 'Invalid FTP Username and password login ',
                'partial' => 1, 'error_code' => 'ftp_login_failed'
            );
        }else{
        	return array('status' => 'success');
        }
        
        if(!empty($ftp_passive)){
			@ftp_pasv($conn_id,true);
		}
        @ftp_mkdir($conn_id, $ftp_remote_folder);
        if (!empty($ftp_site_folder)) {
            $ftp_remote_folder .= '/' . $iwp_mmb_core->backup_instance->site_name;
        }
        @ftp_mkdir($conn_id, $ftp_remote_folder);
        $backup_file = IWP_BACKUP_DIR.'/__testFTP2'.time().'.php';
        $test_content = '<?php
			 // Silence is golden.
			';

		@file_put_contents($backup_file, $test_content); //safe
        $upload = $this -> ftp_multi_upload($conn_id, rtrim($ftp_remote_folder, '/') . '/' . basename($backup_file), $backup_file, FTP_BINARY);
		unlink($backup_file);
		ftp_delete($conn_id, rtrim($ftp_remote_folder, '/') . '/' . basename($backup_file));
        @ftp_close($conn_id);
        
        return $upload;	
	}

	public function ftp_multi_upload($conn_id, $remoteFileName, $backup_file, $mode){
		$file_size = 0;
		$fp = fopen($backup_file, 'r');
		fseek($fp,$file_size);
		
		$ret = ftp_nb_fput($conn_id, $remoteFileName, $fp, FTP_BINARY, $file_size);
		if(!$ret || $ret == FTP_FAILED){
			return array(
                'error' => "FTP upload Error. ftp_nb_fput(): Append/Restart not permitted. This feature is required for multi-call backup upload via FTP to work. Please contact your WP site's hosting provider and ask them to fix the problem. You can try dropbox, Amazon S3 or Google Driver as an alternative to it.",
                'partial' => 1, 'error_code' => 'ftp_nb_fput_not_permitted_error'
            );
		}

		while ($ret == FTP_MOREDATA) {
			$ret = ftp_nb_continue($conn_id);
		}

		if ($ret == FTP_FINISHED) {
			fclose($fp);
			return array('status' => 'success');
		}
	}

	public function SFTPTestConnection($args){
		extract($args);	

		$ftp_hostname = $ftp_hostname ? $ftp_hostname : $hostName;
		$ftp_username = $ftp_username ? $ftp_username : $hostUserName;
		$ftp_password = $ftp_password ? $ftp_password : $hostPassword;
		$FTPBase = $ftp_remote_folder ? $ftp_remote_folder : $FTPBase;
		$port = $ftp_port ? $ftp_port : 22;
		$path = $GLOBALS['iwp_mmb_plugin_dir'].'/lib/phpseclib/phpseclib/phpseclib';
		set_include_path(get_include_path() . PATH_SEPARATOR . $path);
        include_once('Net/SFTP.php');
        $sftp = new Net_SFTP($ftp_hostname, $port);
		
	    if(!$sftp) {
	        return array('status' => 'error',
	                            'errorMsg' => 'Failed to connect to host',
	            );
	    }
	    if (!$sftp->login($ftp_username, $ftp_password)) {
	        return array('status' => 'error', 
				'errorMsg' => 'Invalid FTP Username and password login ',
			);
	    }
		else{
			if(empty($FTPBase)){
				return array('status' => 'error', 
					'errorMsg' => 'Invalid FTP base path..',
				);
			}
			if(!$sftp->chdir($FTPBase)){
				return array('status' => 'error', 
					'errorMsg' => 'FTP upload failed.',
				);	
			}
	        $backup_file = IWP_BACKUP_DIR.'/__testFTP2'.time().'.php';
	        $test_content = '<?php
				 // Silence is golden.
				';

			@file_put_contents($backup_file, $test_content); //safe
			$remote_loation = basename($backup_file);
            $local_location = $backup_file;
            $sftp->mkdir($ftp_remote_folder,-1,true);
            $sftp->chdir($ftp_remote_folder);
			$uploadFilePath = IWP_BACKUP_DIR; 
			$uploadTestFile = $sftp->put(basename($backup_file),$backup_file,NET_SFTP_LOCAL_FILE);
			@unlink($backup_file);
			$appPathFile = APP_ROOT.$testFile;
			if ($uploadTestFile === false) {
				return array('status' => 'error', 
					'errorMsg' => 'FTP upload failed.',
				);
			}else{
				return array('status' => 'success');
			}
		}
	}

	public function backupRepositoryDropbox($args){
		extract($args);
		if(isset($dropbox_access_token) && !empty($dropbox_access_token)){
			require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/Dropbox/API.php';
			require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/Dropbox/Exception.php';
			require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/Dropbox/OAuth/Consumer/ConsumerAbstract.php';
			require_once $GLOBALS['iwp_mmb_plugin_dir'].'/lib/Dropbox/OAuth/Consumer/Curl.php';
			
			try{
				$oauth = new IWP_Dropbox_OAuth_Consumer_Curl($dropbox_app_key, $dropbox_app_secure_key);
				$oauth->setToken($dropbox_access_token);
				$dropbox = new IWP_Dropbox_API($oauth);
				$oldRoot = 'Apps/InfiniteWP';
				$dropbox_destination = $oldRoot.$dropbox_destination;
				$response = $dropbox->accountInfo();
				return array('status' => 'success');			
			}
			catch(Exception $e){
				return array('error' => $e->getMessage(), 'error_code' => 'dropbox_test_failed');
			}
		}
		return array('error' => 'Consumer Secret not available', 'error_code' => 'consumer_secret_not_available');
	}

	public function repositoryGDrive($gDriveArgs){
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Client.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Http/MediaFileUpload.php');
		require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/Google/Service/Drive.php');
		
		if(!empty($gDriveArgs) && !empty($gDriveArgs['clientID']) && !empty($gDriveArgs['clientSecretKey']) )
		{		
			$accessToken = $gDriveArgs['token'];
			
			$client = new IWP_google_Client();
			$client->setClientId($gDriveArgs['clientID']);

			$client->setClientSecret($gDriveArgs['clientSecretKey']);
			$client->setRedirectUri($gDriveArgs['redirectURL']);
			$client->setScopes(array(
			  'https://www.googleapis.com/auth/drive',
			  'https://www.googleapis.com/auth/userinfo.email'));
			
			$accessToken = $gDriveArgs['token'];
			$refreshToken = $accessToken['refresh_token'];
			
			try
			{
				$client->refreshToken($refreshToken);
				return array('status' => 'success');
			}
			catch(Exception $e)
			{	
				return array('error' => $e->getMessage(), 'error_code' => 'gdrive_backup_test_failed');
			}
		}
		return array('status' => 'error', 'errorMsg' => 'API key not available.');
	}

	public function repositoryAmazons3BwdComp($args){
		require_once($GLOBALS['iwp_mmb_plugin_dir']."/lib/S3.php");
		extract($args);
		
		if(!empty($as3_bucket_region)){
			$endpoint = 's3-' . $as3_bucket_region . '.amazonaws.com';
		}
		else{
			$endpoint = 's3.amazonaws.com';
		}
	    $s3 = new IWP_MMB_S3(trim($as3_access_key), trim(str_replace(' ', '+', $as3_secure_key)), false, $endpoint);
		
		try{
			$s3->getBucket($as3_bucket, IWP_MMB_S3::ACL_PUBLIC_READ);
			return array('status' => 'success');
		}
		catch (Exception $e){
	         return array('error' => $e->getMessage(), 'error_code' => 's3_backup_test_verify_failed');
			 //$e->getMessage();
		}
	}
}