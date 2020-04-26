<?php

use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class IWP_MMB_S3_MULTICALL extends IWP_MMB_Backup_Multicall
{
	function amazons3_backup($historyID , $args='' ){
		if (!$this->iwp_mmb_function_exists('curl_init')) {
			return array(
				'error' => 'You cannot use Amazon S3 on your server. Please enable curl first.',
				'partial' => 1, 'error_code' => 'cannot_use_s3_enable_curl_first'
			);
		}
		
		if(!class_exists('S3Client')){
			require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/autoload.php');
		}
			
		$s3StartTime = $this->iwpScriptStartTime;
		$this -> backup_settings_vals = get_option('iwp_client_multi_backup_temp_values');
		$backup_settings_values = $this -> backup_settings_vals;
		if(isset($backup_settings_values['s3_retrace_count']) && !empty($backup_settings_values['s3_retrace_count'])){
			$s3_retrace_count = $backup_settings_values['s3_retrace_count'][$historyID];
		}
		else{
			$s3_retrace_count = 0;
		}
		//get the settings by other method
		$requestParams = $this -> getRequiredData($historyID,"requestParams");
		$upload_loop_break_time = $requestParams['account_info']['upload_loop_break_time'];			//darkcode changed
		$upload_file_block_size = $requestParams['account_info']['upload_file_block_size'];
		if($upload_file_block_size < (5*1024*1024)){
			$upload_file_block_size = (5*1024*1024)+1;
		}
		$del_host_file = $requestParams['args']['del_host_file'];
		
		$task_result = $this -> getRequiredData($historyID,"taskResults");
		
		@set_time_limit(0);
		$this -> hisID = $historyID;
		
		$uploadLoopCount = 0;
		$uploadId = 'start';
		$parts = array();
		$nextPart = 1;
		$retrace = 'notSet';
		$doComplete = false;

		if($args == ''){
			//on the next call $args would be ''
			//set $args, $uploadid, $offset  from the DB
			$responseParams = $this -> getRequiredData($historyID,"responseParams");
			
			if(!$responseParams)
			return $this->statusLog($this -> hisID, array('stage' => 's3Upload', 'status' => 'error', 'statusMsg' => 'S3 Upload failed: Error while fetching table data.', 'statusCode' => 's3_upload_failed_error_while_fetching_table_data'));
			
			$args = $responseParams['s3Args'];
			$prevChunkResults = $responseParams['response_data'];
			$uploadId = $prevChunkResults['uploadId'];
			$nextPart = $prevChunkResults['nextPart'];
			$partsArray = $prevChunkResults['partsArray'];
			$parts = $prevChunkResults['parts'];
			
			$current_file_num = $responseParams['current_file_num'];
			$dont_retrace = $responseParams['dont_retrace'];
			$start_new_backup = $responseParams['start_new_backup'];
		}
		if(empty($current_file_num)){
			$current_file_num = 0;
		}
		//traceback options and setting values 
		
		if((!$uploadId)&&(empty($dont_retrace))){
			if($s3_retrace_count <= 3){
				$args = $requestParams['secure']['account_info']['iwp_amazon_s3'];
				if($backup_settings_values['s3_upload_id']){
					$uploadId = $backup_settings_values['s3_upload_id'][$historyID];
				}
				else{
					return $this->statusLog($this -> hisID, array('stage' => 's3Upload Retrace', 'status' => 'error', 'statusMsg' => 'S3 Upload failed: Error while fetching table data during retrace',  'statusCode' => 's3_upload_failed_error_while_fetching_table_data_during_retrace'));
				}
				$backup_file = $backup_settings_values['backup_file'];
				$retrace = 'set';
				$s3_retrace_count++;
				$backup_settings_values['s3_retrace_count'][$historyID] = $s3_retrace_count;
				update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
			}
			else{
				return $this->statusLog($this -> hisID, array('stage' => 's3Upload', 'status' => 'error', 'statusMsg' => 'S3 upload failed: Retrace limit reached.', 'statusCode' => 's3_upload_failed_retrace_limit_reached'));
			}
		}
		
		//tracback ends
		$tempArgs = $args;
		extract($args);
		
		if(!is_array($backup_file)){
			$temp_backup_file = $backup_file;
			$backup_file = array();
			$backup_file[] = $temp_backup_file;
		}
		
		if(is_array($backup_file)){
			$backup_files_count = count($backup_file);
			$temp_single_file = $backup_file[$current_file_num];
			unset($backup_file);
			$backup_file = $temp_single_file;
		}else{
			$backup_files_count=1;
		}
			
		if ($as3_site_folder == true){
			if(!empty($as3_directory)){
				$as3_directory .= '/' . $this->site_name;
			}
			else{
				$as3_directory =  $this->site_name;
			}
		}	
		if (empty($as3_bucket_region)) {
			require_once($GLOBALS['iwp_mmb_plugin_dir']."/lib/S3.php");
			$s3 = new IWP_MMB_S3(trim($as3_access_key), trim(str_replace(' ', '+', $as3_secure_key)), false, 's3.amazonaws.com');
			$as3_bucket_region = $s3->getBucketLocationNew($as3_bucket);
			if (empty($as3_bucket_region) && false !== $as3_bucket_region) $as3_bucket_region = null;
		}

		if($s3_retrace_count<=3){
			try{
		
				if (empty($as3_bucket_region)) {
					$s3 = S3Client::factory(array(
						'key' => trim($as3_access_key),
						'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
						'region' => $as3_bucket_region,
						'ssl.certificate_authority' => false
					));
				}else{
					$s3 = S3Client::factory(array(
						'key' => trim($as3_access_key),
						'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
						'region' => $as3_bucket_region,
						'signature' => 'v4',
						'ssl.certificate_authority' => false
					));
				}
				
				$objects = $s3->getIterator('ListObjects', array(
				'Bucket' => $as3_bucket,
				));
				foreach ($objects as $object) {
					echo $s3->getObjectUrl($as3_bucket,$object['Key']);
					break; 
				}
				
				//the mulitCall upload starts				darkCode starts
				if(!empty($as3_directory)){
					$as3_file = $as3_directory . '/' . basename($backup_file);
				}
				else{
					$as3_file = basename($backup_file);
				}
				if((iwp_mmb_get_file_size($backup_file) <= 5*1024*1024)){
					//new starts
					echo "<br>small backup so single upload<br>";
					$putArray = array(
					'Bucket'     => $as3_bucket,
					'SourceFile' => $backup_file,
					'Key'        => $as3_file,
					'ACL' => 'private'
					);
					if ($server_side_encryption == 1) {
						$putArray['ServerSideEncryption']='AES256';
					}
					$s3->putObject($putArray);
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
					
					if($current_file_num >= $backup_files_count){
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
						$chunkResult['uploadId'] = 'start';
						
						$result_arr['response_data'] = $chunkResult;
						$result_arr['nextFunc'] = 'amazons3_backup';
						$result_arr['status'] = 'partiallyCompleted';
						$result_arr['start_new_backup'] = true;
						
						@unlink($backup_file);
					}
					$this->statusLog($this -> hisID, array('stage' => 's3MultiCall', 'status' => 'completed', 'statusMsg' => 'nextCall','nextFunc' => 'amazons3_backup', 'task_result' => $task_result, 'responseParams' => $result_arr));
					
					return $resArray;
				
				} // ends simple upload
				else {
					if($uploadId == 'start' && isset($parts)){
						echo "iwpmsg initiating multiCall upload";
						//get the uploadID
						$filename = $backup_file;
						$putArray = array(
						'Bucket'     => $as3_bucket,
						'Key'        => $as3_file,
						'ACL' => 'private'
						);
						if ($server_side_encryption == 1) {
							$putArray['ServerSideEncryption']='AES256';
						}
						$result = $s3->createMultipartUpload($putArray);
					
						$parts = array();
						$uploadId = $result['UploadId'];	
						//storing the uploadID in DB 
						$backup_settings_values['s3_upload_id'][$historyID] = $uploadId;
						$backup_settings_values['backup_file'] = $backup_file;
						update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
					}
					$s3ChunkTimeTaken = 0;
					$s3ChunkCount = 0;
					$reloopCount = 0;
				
					try{
						$filename = $backup_file;
						$file = fopen($filename, 'r');
			
						$partNumber = 1;
						echo $partNumber;
						$reloopCount = 0;
						while (!feof($file)){
							if($reloopCount == 0){
								$s3ChunkStartTime = $s3StartTime;
								$reloopCount++;
							}
							else{
								$s3ChunkStartTime = microtime(true);
							}
							if($partNumber == $nextPart){
								$result = $s3->uploadPart(array(
									'Bucket'     => $as3_bucket,
									'Key'        => $as3_file,
									'UploadId'   => $uploadId,
									'PartNumber' => $partNumber,
									'Body'       => fread($file, 5 * 1024 * 1024),
								));
						
								$parts[] = array(
								'PartNumber' => $partNumber++,
								'ETag'       => $result['ETag'],
								);
						
								echo "Uploading part {$partNumber} of {$filename}.\n";
						
								$chunkResult['nextPart'] = $nextPart+1;
								$chunkResult['uploadId'] = $uploadId;
								$chunkResult['parts'] = $parts;
								$nextPart = $nextPart + 1;
								
								$backup_settings_values['s3_retrace_count'][$historyID] = 0;
								update_option('iwp_client_multi_backup_temp_values', $backup_settings_values);
								
								$status = 'partiallyCompleted';
								
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
								$s3ChunkEndTime = microtime(true);
								$s3ChunkTimeTaken = ($s3ChunkEndTime - $s3ChunkStartTime);
								$s3EndTime = microtime(true);
								$s3TimeTaken = $s3EndTime - $s3StartTime;
								$s3TimeLeft = $upload_loop_break_time - $s3TimeTaken;
						
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
								
								if(($s3TimeLeft <= $s3ChunkTimeTaken)){
									$this->statusLog($this -> hisID, array('stage' => 's3MultiCall', 'status' => 'partiallyCompleted', 'statusMsg' => 'nextCall','nextFunc' => 'amazons3_backup', 'task_result' => $task_result, 'responseParams' => $result_arr));
									fclose($file);
									break;
								}
							}
							else{
								fread($file, 5 * 1024 * 1024);
								$partNumber++;
							}
						}
						@fclose($file);
					}
					catch (S3Exception $e) {
						$this->statusLog($this -> hisID, array('stage' => 's3MultiCall', 'status' => 'partiallyCompleted', 'statusMsg' => 'retracingValues','nextFunc' => 'amazons3_backup', 'task_result' => $task_result, 'responseParams' => $result_arr));
					}
					if($nextPart==(ceil((((iwp_mmb_get_file_size($backup_file)/1024)/1024)/5))+1)){
						$result = $s3->completeMultipartUpload(array(
						'Bucket'   => $as3_bucket,
						'Key'      => $as3_file,
						'UploadId' => $uploadId,
						'Parts'    => $parts,
						));
						$url = $result['Location'];
						$current_file_num += 1;
						$result_arr = array();
						$result_arr['response_data'] = $chunkResult;
						$result_arr['status'] = 'completed';
						$result_arr['nextFunc'] = 'amazons3_backup_over';
						$result_arr['s3Args'] = $tempArgs;
						$result_arr['dont_retrace'] = true;
						$result_arr['current_file_num'] = $current_file_num;
						
						$resArray = array(
						  'status' => 'completed',
						  'backupParentHID' => $historyID,
						);
						
						if($current_file_num >= $backup_files_count){
							$task_result['task_results'][$historyID]['amazons3'][$current_file_num-1] = basename($backup_file);
							$task_result['amazons3'][$current_file_num-1] = basename($backup_file);
							unset($task_result['task_results'][$historyID]['server']);
						}
						else{
							//to continue zip split parts
							$chunkResult = array();
							$chunkResult['partsArray'] = array();
							$chunkResult['nextPart'] = 1;
							$chunkResult['uploadId'] = 'start';
							$chunkResult['parts'] = '';
						
							$result_arr['response_data'] = $chunkResult;
							$result_arr['status'] = 'partiallyCompleted';
							$result_arr['nextFunc'] = 'amazons3_backup';
							$result_arr['start_new_backup'] = true;
							
							$resArray['status'] = 'partiallyCompleted';
						}
						$this->statusLog($this -> hisID, array('stage' => 's3MultiCall', 'status' => 'completed', 'statusMsg' => 'finalCall','nextFunc' => 'amazons3_backup', 'task_result' => $task_result, 'responseParams' => $result_arr));
						
						$status = 'completed';
						iwp_mmb_print_flush('Amazon S3 upload: End');
						if($status == 'completed'){
							$partArrayLength = count($partsArray);
							$verificationResult = $this -> postUploadVerification($s3, $backup_file, $as3_file, $type = "amazons3", $as3_bucket,$as3_access_key,$as3_secure_key,$as3_bucket_region);
							if(!$verificationResult){
								return $this->statusLog($historyID, array('stage' => 'uploadAmazons3', 'status' => 'error', 'statusMsg' => 'S3 verification failed: File may be corrupted.', 'statusCode' => 'docomplete_S3_verification_failed_file_may_be_corrupted'));
							}
							if($del_host_file){
								@unlink($backup_file);
							}
							return $resArray;	
						}
						echo "Uploaded {$filename} to {$backup_file}.\n";
					}
					else {
						return $resArray;			
					}
				}
			}
			catch (Exception $e)
			{
				$result = $s3->abortMultipartUpload(array(
								'Bucket'   => $as3_bucket,
								'Key'      => $as3_file,
								'UploadId' => $uploadId
								));
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
		else {
			return array(
			'error' => 'Failed to upload to Amazon S3. Could not connect amazon server at the moment',
			'partial' => 1, 'error_code' => 'failed_to_upload_to_s3_Could_not_connect_amazon_server_at_the_moment'
			);
		}
	}
	
	function get_amazons3_backup($args){
		if (!$this->iwp_mmb_function_exists('curl_init')) {
			return array(
				'error' => 'You cannot use Amazon S3 on your server. Please enable curl first.',
				'partial' => 1, 'error_code' => 'cannot_use_s3_enable_curl_first'
			);
		}
		if(!class_exists('S3Client')){
			require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/autoload.php');
		}
		extract($args);
		$temp = '';
		if (empty($as3_bucket_region)) {
			require_once($GLOBALS['iwp_mmb_plugin_dir']."/lib/S3.php");
			$s3 = new IWP_MMB_S3(trim($as3_access_key), trim(str_replace(' ', '+', $as3_secure_key)), false, 's3.amazonaws.com');
			$as3_bucket_region = $s3->getBucketLocationNew($as3_bucket);
			if (empty($as3_bucket_region) && false !== $as3_bucket_region) $as3_bucket_region = null;
		}
		try{
		if (empty($as3_bucket_region)) {
			$s3 = S3Client::factory(array(
				'key' => trim($as3_access_key),
				'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
				'region' => $as3_bucket_region,
				'ssl.certificate_authority' => false
			));
		}else{
			$s3 = S3Client::factory(array(
				'key' => trim($as3_access_key),
				'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
				'region' => $as3_bucket_region,
				'signature' => 'v4',
				'ssl.certificate_authority' => false
			));
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
		$getResult = $s3->getObject(array(
			'Bucket' => $as3_bucket,
			'Key'    => $single_as3_file,
			'SaveAs' => $temp
		));
	   } catch (Exception $e){
		return false;
	   }
		return $temp;
	}
	
	function remove_amazons3_backup($args){
    	if (!$this->iwp_mmb_function_exists('curl_init')) {
			return array(
                'error' => 'You cannot use Amazon S3 on your server. Please enable curl first.',
                'partial' => 1, 'error_code' => 'cannot_use_s3_enable_curl_first'
            );
		}
        if(!class_exists('S3Client')){
			require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/autoload.php');
		}
        extract($args);
		if (empty($as3_bucket_region)) {
			require_once($GLOBALS['iwp_mmb_plugin_dir']."/lib/S3.php");
			$s3 = new IWP_MMB_S3(trim($as3_access_key), trim(str_replace(' ', '+', $as3_secure_key)), false, 's3.amazonaws.com');
			$as3_bucket_region = $s3->getBucketLocationNew($as3_bucket);
			if (empty($as3_bucket_region) && false !== $as3_bucket_region) $as3_bucket_region = null;
		}
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
			if (empty($as3_bucket_region)) {
				$s3 = S3Client::factory(array(
					'key' => trim($as3_access_key),
					'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
					'region' => $as3_bucket_region,
					'ssl.certificate_authority' => false
				));
			}else{
				$s3 = S3Client::factory(array(
					'key' => trim($as3_access_key),
					'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
					'region' => $as3_bucket_region,
					'signature' => 'v4',
					'ssl.certificate_authority' => false
				));
			}
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
				$result = $s3->deleteObject(array(
					'Bucket' => $as3_bucket,
					'Key'    => $single_as3_file
				));
			}
      	} catch (Exception $e){
      		$err = $e->getMessage();
			if($err){
				 return array(
					'error' => 'Failed to upload to AmazonS3 ('.$err.').', 'error_code' => 'failed_to_upload_s3_err'
				);
			}else {
				return array(
					'error' => 'Failed to upload to Amazon S3.', 'error_code' => 'failed_to_upload_s3'
				);
      	}
      }
    }
	
	function postUploadS3Verification($backup_file, $destFile, $type = "", $as3_bucket = "", $as3_access_key = "", $as3_secure_key = "", $as3_bucket_region = "", $size1, $size2, $return_size = false){
		if (empty($as3_bucket_region)) {
			require_once($GLOBALS['iwp_mmb_plugin_dir']."/lib/S3.php");
			$s3 = new IWP_MMB_S3(trim($as3_access_key), trim(str_replace(' ', '+', $as3_secure_key)), false, 's3.amazonaws.com');
			$as3_bucket_region = $s3->getBucketLocationNew($as3_bucket);
			if (empty($as3_bucket_region) && false !== $as3_bucket_region) $as3_bucket_region = null;
		}
		if (empty($as3_bucket_region)) {
			$s3 = S3Client::factory(array(
				'key' => trim($as3_access_key),
				'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
				'region' => $as3_bucket_region,
				'ssl.certificate_authority' => false
			));
		}else{
			$s3 = S3Client::factory(array(
				'key' => trim($as3_access_key),
				'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
				'region' => $as3_bucket_region,
				'signature' => 'v4',
				'ssl.certificate_authority' => false
			));
		}
		if(!$s3){
			return false;
		}
		try {

			$result = $s3->headObject(array(
			'Bucket' => $as3_bucket,
			'Key'    => $destFile
			));

			$s3_file_metadata = $result->toArray();
			$s3_file_size = $s3_file_metadata['ContentLength'];
			if ($return_size == true) {
				return $s3_file_size;
			}
			echo "S3 fileszie during verification - ".$s3_file_size.PHP_EOL."size 1 - ".$size1.PHP_EOL."size 2 - ".$size2.PHP_EOL;

			if((($s3_file_size >= $size1 && $s3_file_size <= $actual_file_size) || ($s3_file_size <= $size2 && $s3_file_size >= $actual_file_size) || ($s3_file_size == $actual_file_size)) && ($s3_file_size != 0)){
					return true;
			} else {
				return false;
			}
			
		} catch (S3Exception $e) {
			return false;
		}
	}
}

class IWP_MMB_S3_SINGLECALL extends IWP_MMB_Backup_Multicall
{
	function amazons3_backup($args){
		if (!$this->iwp_mmb_function_exists('curl_init')) {
			return array(
                'error' => 'You cannot use Amazon S3 on your server. Please enable curl first.',
                'partial' => 1, 'error_code' => 'cannot_use_s3_enable_curl_first'
            );
		}
        
		if(!class_exists('S3Client')){
			require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/autoload.php');
		}
			extract($args);
        if (file_exists($backup_file)) {
            if ($as3_site_folder == true)
			{
				if(!empty($as3_directory))
				{
					$as3_directory .= '/' . $this->site_name;
				}
				else
				{
					$as3_directory = $this->site_name;
				}
            }
			if(empty($as3_directory))
			{
				$as3_file = basename($backup_file);
			}
			else
			{
				$as3_file =  $as3_directory . '/' . basename($backup_file);
			}
            try{
            	if (empty($as3_bucket_region)) {
            		require_once($GLOBALS['iwp_mmb_plugin_dir']."/lib/S3.php");
            		$s3 = new IWP_MMB_S3(trim($as3_access_key), trim(str_replace(' ', '+', $as3_secure_key)), false, 's3.amazonaws.com');
            		$as3_bucket_region = $s3->getBucketLocationNew($as3_bucket);
            		if (empty($as3_bucket_region) && false !== $as3_bucket_region) $as3_bucket_region = null;
            	}
				if (empty($as3_bucket_region)) {
					$s3 = S3Client::factory(array(
						'key' => trim($as3_access_key),
						'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
						'region' => $as3_bucket_region,
						'ssl.certificate_authority' => false
					));
				}else{
					$s3 = S3Client::factory(array(
						'key' => trim($as3_access_key),
						'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
						'region' => $as3_bucket_region,
						'signature' => 'v4',
						'ssl.certificate_authority' => false
					));
				}
				
				$objects = $s3->getIterator('ListObjects', array(
					'Bucket' => $as3_bucket,
				));
				foreach ($objects as $object) {
					echo $s3->getObjectUrl($as3_bucket,$object['Key']);
					break; 
				} 
            }catch (Exception $e){
                return array(
                    'error' => 'Failed to upload to Amazon S3. Please check your details and set Managed Policies on your users to AmazonS3FullAccess.',
                    'error_code' => 'upload_failed_to_S3_check_your_details_and_set_managed_policies_amazonS3FullAccess_on_your_users',
                    'partial' => 1
                );
            }

            if(filesize($backup_file) <5*1024*1024){
                try{

                	$putArray = array(
					'Bucket'     => $as3_bucket,
					'SourceFile' => $backup_file,
					'Key'        => $as3_file,
					'ACL' => 'private'
					);
					if ($server_side_encryption == 1) {
						$putArray['ServerSideEncryption']='AES256';
					}
					$s3->putObject($putArray);
                   return true;
        }catch (Exception $e){
         $err = $e->getMessage();
         if($err){
         	 return array(
                'error' => 'Failed to upload to AmazonS3 ('.$err.').',
				'error_code' => 'failed_upload_s3_with_error'
            );
         } else {
         	return array(
                'error' => 'Failed to upload to Amazon S3.',
				'error_code' => 'failed_upload_s3'
            );
         }
        }
            }
            else {
				$filename = $backup_file;
				$result = $s3->createMultipartUpload(array(
					'Bucket'       => $as3_bucket,
					'Key'          => $as3_file,
					'ACL'          => 'private',
				  
				));

				$uploadId = $result['UploadId'];
				echo $uploadId;
				// 3. Upload the file in parts.
				try {
					$file = fopen($filename, 'r');
					$parts = array();
					$partNumber = 1;
					echo $partNumber;
					while (!feof($file)) {
						$result = $s3->uploadPart(array(
							'Bucket'     => $as3_bucket,
							'Key'        => $as3_file,
							'UploadId'   => $uploadId,
							'PartNumber' => $partNumber,
							'Body'       => fread($file, 5 * 1024 * 1024),
						));
						$parts[] = array(
							'PartNumber' => $partNumber++,
							'ETag'       => $result['ETag'],
						);
						echo "Uploading part {$partNumber} of {$filename}.\n";
					}
					fclose($file);
				}catch (S3Exception $e) {
					$result = $s3->abortMultipartUpload(array(
						'Bucket'   => $as3_bucket,
						'Key'      => $as3_file,
						'UploadId' => $uploadId
					));
					return array(
						'error' => "Backup is not Uploaded (".$e->getMessage().")",
						'error_code' => 'timeout_error',
						'partial' => 1
					);
				}

				// 4. Complete multipart upload.
				if(!empty($parts)){
					$result = $s3->completeMultipartUpload(array(
						'Bucket'   => $as3_bucket,
						'Key'      => $as3_file,
						'UploadId' => $uploadId,
						'Parts'    => $parts,
					));
					$url = $result['Location'];
					echo "Uploaded {$filename} to {$url}.\n";
					return true;
				}
            }
		} 
		else {
            return array(
				'error' => 'Server Lost connection Please try again !',
				'error_code' => 'file_doesnot_exist',
                'partial' => 1
            );
        }
    }

	function remove_amazons3_backup($args){
    	if (!$this->iwp_mmb_function_exists('curl_init')) {
			return array(
                'error' => 'You cannot use Amazon S3 on your server. Please enable curl first.',
                'partial' => 1, 'error_code' => 'cannot_use_s3_enable_curl_first'
            );
		}
		extract($args);
        if(!class_exists('S3Client')){
			require_once($GLOBALS['iwp_mmb_plugin_dir'].'/lib/amazon/autoload.php');
		}
		
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
		if (empty($as3_bucket_region)) {
			require_once($GLOBALS['iwp_mmb_plugin_dir']."/lib/S3.php");
			$s3 = new IWP_MMB_S3(trim($as3_access_key), trim(str_replace(' ', '+', $as3_secure_key)), false, 's3.amazonaws.com');
			$as3_bucket_region = $s3->getBucketLocationNew($as3_bucket);
			if (empty($as3_bucket_region) && false !== $as3_bucket_region) $as3_bucket_region = null;
		}
        try{
            if (empty($as3_bucket_region)) {
            	$s3 = S3Client::factory(array(
            		'key' => trim($as3_access_key),
            		'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
            		'region' => $as3_bucket_region,
            		'ssl.certificate_authority' => false
            	));
            }else{
            	$s3 = S3Client::factory(array(
            		'key' => trim($as3_access_key),
            		'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
            		'region' => $as3_bucket_region,
            		'signature' => 'v4',
            		'ssl.certificate_authority' => false
            	));
            }
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
                $result = $s3->deleteObject(array(
                    'Bucket' => $as3_bucket,
                    'Key'    => $single_as3_file
                ));
			}
      	} catch (Exception $e){
      		return false;
      }
    }
}

function iwpRepositoryAmazons3($args){
	require_once($GLOBALS['iwp_mmb_plugin_dir'] . '/lib/amazon/autoload.php');
	
	extract($args);
	try{
		if (empty($as3_bucket_region)) {
			require_once($GLOBALS['iwp_mmb_plugin_dir']."/lib/S3.php");
			$s3 = new IWP_MMB_S3(trim($as3_access_key), trim(str_replace(' ', '+', $as3_secure_key)), false, 's3.amazonaws.com');
			$as3_bucket_region = $s3->getBucketLocationNew($as3_bucket);
			if (empty($as3_bucket_region) && false !== $as3_bucket_region) $as3_bucket_region = null;
		}
		if (empty($as3_bucket_region)) {
			$s3 = S3Client::factory(array(
				'key' => trim($as3_access_key),
				'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
				'region' => $as3_bucket_region,
				'ssl.certificate_authority' => false
			));
		}else{
			$s3 = S3Client::factory(array(
				'key' => trim($as3_access_key),
				'secret' => trim(str_replace(' ', '+', $as3_secure_key)),
				'region' => $as3_bucket_region,
				'signature' => 'v4',
				'ssl.certificate_authority' => false
			));
		}

		$objects = $s3->getIterator('ListObjects', array(
			'Bucket' => $as3_bucket,
		));
		foreach ($objects as $object){
			echo $s3->getObjectUrl($as3_bucket,$object['Key']);
			break; 
		}
		return array('status' => 'success');
	}
	catch (Exception $e){
         return array('error' => $e->getMessage(), 'error_code' => 's3_cloud_backup_verification_failed');
	}
}