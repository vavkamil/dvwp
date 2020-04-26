<?php

function wfu_process_files_queue($params, $method) {
	$sid = $params["uploadid"];
	$unique_id = sanitize_text_field($_POST['uniqueuploadid_'.$sid]);
	$queue = "wfu_queue_".$unique_id;
	if ( $unique_id != "" ) {
		$queue_id = wfu_create_random_string(16);
		wfu_join_queue($queue, $queue_id);
		while (true) {
			$cur_id = wfu_get_queue_thread($queue);
			if ( $cur_id == $queue_id ) break;
			usleep(100000);
		}
	}
	$queue_count = intval(wfu_get_option("wfu_queue_".$unique_id."_count", 0, "string")) + 1;
		wfu_debug_log("queue_count:".$queue_count."\n");
		$chunk_data = explode(",", ( isset($_POST['chunk_data']) ? $_POST['chunk_data'] : "0,0,0,0," ));
		if ( count($chunk_data) != 5 ) $chunk_data = array( "0", "0", "0", "0", "" );
		list($file_id, $file_size, $chunk_count, $chunk_id, $filename_enc) = $chunk_data;
		$file_id = wfu_sanitize_int($file_id);
		$file_size = wfu_sanitize_int($file_size);
		$chunk_id = wfu_sanitize_int($chunk_id);
		wfu_debug_log("chunk_data:".( isset($_POST['chunk_data']) ? $_POST['chunk_data'] : "0,0,0,0," )."\n");
	wfu_update_option("wfu_queue_".$unique_id."_count", $queue_count, "string");
	/*if ( $queue_count >= 3 && $queue_count <= 5 ) $ret = "abort";
	else */$ret = wfu_process_files_net($params, $method);
	wfu_advance_queue($queue);
	return $ret;
}

function wfu_process_files($params, $method) {
	$sid = $params["uploadid"];
	$sesid = wfu_get_session_id();
	$user = wp_get_current_user();
	if ( 0 == $user->ID ) {
		$user_id = 0;
		$user_login = "guest";
		$user_email = "";
		$is_admin = false;
	}
	else {
		$user_id = $user->ID;
		$user_login = $user->user_login;
		$user_email = $user->user_email;
		$is_admin = current_user_can('manage_options');
	}
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$unique_id = sanitize_text_field($_POST['uniqueuploadid_'.$sid]);
	// determine if this routine is only for checking the file
	$only_check = ( isset($_POST['only_check']) ? ( $_POST['only_check'] == "1" ) : false );
	// determine if this is an upload without a file
	$nofileupload = ( $params["allownofile"] == "true" && isset($_POST['nofileupload_'.$sid]) ? ( $_POST['nofileupload_'.$sid] == "1" ) : false );
	$force_notifications = ( WFU_VAR("WFU_FORCE_NOTIFICATIONS") == "true" );
	$consent_revoked = ( $plugin_options["personaldata"] == "1" && $params["consent_result"] == "0" );
	$not_store_files = ( $params["personaldatatypes"] == "userdata and files" );
	$empty_userdata_fields = $params["userdata_fields"];
	$store_nothing = ( $consent_revoked && $not_store_files );

	$suppress_admin_messages = ( $params["adminmessages"] != "true" || !$is_admin );
	$success_count = 0;
	$warning_count = 0;
	$error_count = 0;
	$default_colors = wfu_prepare_message_colors(WFU_VAR("WFU_DEFAULTMESSAGECOLORS"));
	$notify_by_email = 0;
	$notify_target_path_list = array();
	$uploadedfile = 'uploadedfile_'.$sid;
	$hiddeninput = 'hiddeninput_'.$sid;
	$allowed_patterns = explode(",",$params["uploadpatterns"]);
	foreach ($allowed_patterns as $key => $allowed_pattern) {
		$allowed_patterns[$key] = trim($allowed_pattern);
	}
	$userdata_fields = $params["userdata_fields"]; 
	foreach ( $userdata_fields as $userdata_key => $userdata_field ) {
		$userdata_fields[$userdata_key]["value"] = ( isset($_POST[$hiddeninput.'_userdata_'.$userdata_key]) ? strip_tags($_POST[$hiddeninput.'_userdata_'.$userdata_key]) : "" );
	}
	$params_output_array["version"] = "full";
	$params_output_array["general"]['shortcode_id'] = $sid;
	$params_output_array["general"]['unique_id'] = $unique_id;
	$params_output_array["general"]['state'] = 0;
	$params_output_array["general"]['files_count'] = 0;
	$params_output_array["general"]['update_wpfilebase'] = "";
	$params_output_array["general"]['redirect_link'] = ( $params["redirect"] == "true" ? $params["redirectlink"] : "" );
	$params_output_array["general"]['upload_finish_time'] = 0;
	$params_output_array["general"]['message'] = "";
	$params_output_array["general"]['message_type'] = "";
	$params_output_array["general"]['admin_messages']['wpfilebase'] = "";
	$params_output_array["general"]['admin_messages']['notify'] = "";
	$params_output_array["general"]['admin_messages']['redirect'] = "";
	$params_output_array["general"]['admin_messages']['other'] = "";
	$params_output_array["general"]['errors']['wpfilebase'] = "";
	$params_output_array["general"]['errors']['notify'] = "";
	$params_output_array["general"]['errors']['redirect'] = "";
	$params_output_array["general"]['color'] = $default_colors['color'];
	$params_output_array["general"]['bgcolor'] = $default_colors['bgcolor'];
	$params_output_array["general"]['borcolor'] = $default_colors['borcolor'];
	$params_output_array["general"]['notify_by_email'] = 0;
	$params_output_array["general"]['fail_message'] = "";
	$params_output_array["general"]['fail_admin_message'] = "";
	/* safe_output is a minimized version of params_output_array, that is passed as text, in case JSON parse fails
	   its data are separated by semicolon (;) and are the following:
		upload state: the upload state number
		default colors: the default color, bgcolor and borcolor values, separated by comma(,)
		file_count: the number of files processed
		filedata: message type, header, message and admin message of each file, encoded and separated by comma (,) */
	$params_output_array["general"]['safe_output'] = "";
	/* js_script is javascript code that is executed after each file upload and is defined in wfu_after_file_upload action */
	$params_output_array["general"]['js_script'] = "";

	/* adjust $uploadedfile variable (holding file data) if this is a redirection caused because the browser of the user could not handle AJAX upload */
	if ( isset($_FILES[$uploadedfile.'_redirected']) ) $uploadedfile .= '_redirected';
	/* notify admin if this is a redirection caused because the browser of the user could not handle AJAX upload */
	$params_output_array["general"]['admin_messages']['other'] = $params['adminerrors'];

	if ( isset($_FILES[$uploadedfile]['error']) || $only_check || $nofileupload ) {
		$files_count = 1;
		// in case of checking of file or no file upload, then the $_FILES
		// variable has not been set because no file has been uploaded,
		// so we set it manually in order to allow the routine to continue
		if ( $only_check || $nofileupload ) {
			$_FILES[$uploadedfile]['name'] = wfu_plugin_decode_string($_POST[$uploadedfile.'_name']);
			$_FILES[$uploadedfile]['type'] = 'any';
			$_FILES[$uploadedfile]['tmp_name'] = 'any';
			$_FILES[$uploadedfile]['error'] = '';
			$_FILES[$uploadedfile]['size'] = wfu_sanitize_int($_POST[$uploadedfile.'_size']);
		}
	}
	else $files_count = 0;
	$params_output_array["general"]['files_count'] = $files_count;
	// index of uploaded file in case of ajax uploads (in ajax uploads only one file is uploaded in every ajax call)
	// the index is used to store any file data in session variables, in case the file is uploaded in two or more passes
	// (like the case were in the first pass it is only checked) 
	$single_file_index = ( isset($_POST[$uploadedfile.'_index']) ? $_POST[$uploadedfile.'_index'] : -1 );
	$single_file_index = wfu_sanitize_int($single_file_index);

	/* append userdata fields to upload path */
	$search = array ( );	 
	$replace = array ( );
	foreach ( $userdata_fields as $userdata_key => $userdata_field ) { 
		$ind = 1 + $userdata_key;
		array_push($search, '/%userdata'.$ind.'%/');  
		array_push($replace, $userdata_field["value"]);
	}   
	$params["uploadpath"] =  preg_replace($search, $replace, $params["uploadpath"]);

	/* append subfolder name to upload path */
	if ( $params["askforsubfolders"] == "true" ) {
		if ( $params["subfoldertree"] == "auto+" && $params['subdir_selection_index'] != '' ) {
			if ( substr($params["uploadpath"], -1, 1) == "/" ) $params["uploadpath"] .= $params['subdir_selection_index'];
			else $params["uploadpath"] .= '/'.$params['subdir_selection_index'];			
		}
		elseif ( $params["subfoldertree"] != "auto+" && $params['subdir_selection_index'] >= 1 ) {
			if ( substr($params["uploadpath"], -1, 1) == "/" ) $params["uploadpath"] .= $params['subfoldersarray'][$params['subdir_selection_index']];
			else $params["uploadpath"] .= '/'.$params['subfoldersarray'][$params['subdir_selection_index']];
		}
	}

	/* if webcam uploads are enabled, then correct the filename */
	if ( strpos($params["placements"], "webcam") !== false && $params["webcam"] == "true" ) {
		$initial_file_name = $_FILES[$uploadedfile]['name'];
		$dotfileext = wfu_fileext($initial_file_name, true);
		$file_name = wfu_filename($initial_file_name);
		if ( $file_name == "video" ) $file_name = $params["videoname"];
		else $file_name = $params["imagename"];
		$search = array ('/%userid%/', '/%username%/', '/%blogid%/', '/%pageid%/', '/%pagetitle%/');	
		$replace = array ($user_id, $user_login, $params['blogid'], $params['pageid'], get_the_title($params['pageid']));
		foreach ( $userdata_fields as $userdata_key => $userdata_field ) { 
			$ind = 1 + $userdata_key;
			array_push($search, '/%userdata'.$ind.'%/');  
			array_push($replace, $userdata_field["value"]);
		}   
		$file_name = preg_replace($search, $replace, $file_name);
		$_FILES[$uploadedfile]['name'] = $file_name.$dotfileext;
	}	
	
	if ( $files_count == 1 ) {

		foreach ( $_FILES[$uploadedfile] as $key => $prop )
			$fileprops[$key] = $prop;

		$sftp_not_supported = false;
		$upload_path_ok = false;
		$allowed_file_ok = false;
		$size_file_ok = false;
		$size_file_phpenv_ok = true;
		$ignore_server_actions = false;
		$file_output['color'] = $default_colors['color'];
		$file_output['bgcolor'] = $default_colors['bgcolor'];
		$file_output['borcolor'] = $default_colors['borcolor'];
		$file_output['header'] = "";
		$file_output['message'] = "";
		$file_output['message_type'] = "";
		$file_output['admin_messages'] = "";
		$file_output['uploaded_file_props'] = "";
		$fileid = -1;

		//calculate index of file
		$real_file_index = $single_file_index;
		if ( $single_file_index == -1 ) $real_file_index = ( isset($i) ? $i : 0 );
		// determine if file data have been saved to session variables, due to a previous pass of this file
		$file_map = "filedata_".$unique_id."_".$real_file_index;
		// retrieve unique id of the file, used in filter actions for identifying each separate file
		if ( WFU_USVAR_exists($file_map) ) {
			$file_map_arr = WFU_USVAR($file_map);
			$file_unique_id = $file_map_arr['file_unique_id'];
		}
		else $file_unique_id = '';
		$filedata_previously_defined = ( $file_unique_id != '' );
		/* generate unique id for each file for use in filter actions if it has not been previously defined */
		if ( !$filedata_previously_defined )
			$file_unique_id = wfu_create_random_string(20);

		/* Get uploaded file size in Mbytes */
		// correct file size in case of checking of file or no file upload
		// otherwise $upload_file_size will be zero and the routine will fail
		if ( $only_check || $nofileupload ) {
			$upload_file_size = $fileprops['size'];
			if ( $upload_file_size == 0 ) $upload_file_size ++;
		}
		else {
			$upload_file_size = filesize($fileprops['tmp_name']);
			if ( $upload_file_size == 0 && file_exists($fileprops['tmp_name']) && $fileprops['error'] == 0 ) $upload_file_size ++;
		}
		$upload_file_size_MB = $upload_file_size / 1024 / 1024;
		
		$only_filename = $fileprops['name'];
		$target_path = wfu_upload_plugin_full_path($params).$only_filename;

		if ( $upload_file_size > 0 ) {
			/* Section to perform filter action wfu_before_file_check before file is checked in order to perform
			   any filename or userdata modifications or reject the upload of the file by setting error_message item
			   of $ret_data array to a non-empty value */
			$filter_error_message = '';
			$filter_admin_message = '';
			if ( $file_unique_id != '' && !$filedata_previously_defined ) {
				// get correct file size
				if ( $only_check || $nofileupload ) $filesize = $fileprops['size'];
				else $filesize = filesize($fileprops['tmp_name']);
				/* store file data and upload result to filedata session array 
				   for use by after_upload filters */
				if ( !$nofileupload ) {
					if ( !WFU_USVAR_exists("filedata_".$unique_id) ) WFU_USVAR_store("filedata_".$unique_id, array());
					$filedata_id = WFU_USVAR("filedata_".$unique_id);
					$filedata_id[$real_file_index] = array(
						"file_unique_id"	=> $file_unique_id,
						"original_filename"	=> $only_filename,
						"filesize" 			=> $filesize,
					);
					WFU_USVAR_store("filedata_".$unique_id, $filedata_id);
				}
				// prepare parameters for wfu_before_file_check filter
				// if this is a no file upload the prepare parameters for
				// wfu_before_data_submit filter
				if ( !$nofileupload ) $changable_data['file_path'] = $target_path;
				$changable_data['user_data'] = $userdata_fields;
				$changable_data['error_message'] = $filter_error_message;
				$changable_data['admin_message'] = $filter_admin_message;
				$additional_data['shortcode_id'] = $sid;
				$additional_data['unique_id'] = $unique_id;
				if ( !$nofileupload ) $additional_data['file_unique_id'] = $file_unique_id;
				if ( !$nofileupload ) $additional_data['file_size'] = $filesize;
				$additional_data['user_id'] = $user->ID;
				$additional_data['page_id'] = $params["pageid"];
				if ( !$nofileupload ) $ret_data = apply_filters('wfu_before_file_check', $changable_data, $additional_data);
				else $ret_data = apply_filters('wfu_before_data_submit', $changable_data, $additional_data);
				if ( !$nofileupload ) $target_path = $ret_data['file_path'];
				if ( !$nofileupload ) $only_filename = wfu_basename($target_path);
				$userdata_fields = $ret_data['user_data'];
				$filter_error_message = $ret_data['error_message'];
				$filter_admin_message = $ret_data['admin_message'];
				// if this is a file check, which means that a second pass of
				// the file will follow, then we do not want to apply the
				// filters again, so we store the changable data to session
				// variables for this specific file
				if ( $only_check && !$nofileupload ) {
					if ( !WFU_USVAR_exists($file_map) ) WFU_USVAR_store($file_map, array());
					$file_map_arr = WFU_USVAR($file_map);
					$file_map_arr['file_unique_id'] = $file_unique_id;
					$file_map_arr['filepath'] = $target_path;
					$file_map_arr['userdata'] = $userdata_fields;
					WFU_USVAR_store($file_map, $file_map_arr);
				}
			}
			// if this is a second pass of the file, because a first pass with file checking was done before, then retrieve
			// file data that may have previously changed because of application of filters
			if ( $filedata_previously_defined ) {
				$file_map_arr = WFU_USVAR($file_map);
				$target_path = $file_map_arr['filepath'];
				$only_filename = wfu_basename($target_path);
				$userdata_fields = $file_map_arr['userdata'];
			}
			if ( $filter_error_message != '' ) {
				//errorabort flag designates that file will be aborted and no resuming will be attempted
				$file_output['message_type'] = "errorabort";
				$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], $filter_error_message);
				if ( $filter_admin_message != '' )
					$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], $filter_admin_message);
			}
			/* Perform security test for denial-of-service (DOS) attacks by
			   counting the number of files that have been uploaded within a
			   specific time interval, if DOS Attack Check is enabled. If the
			   number of files exceeds the limit then the file is rejected and a
			   message is sent to the administrator. */
			elseif ( WFU_VAR("WFU_DOS_ATTACKS_CHECK") == "true" && wfu_check_DOS_attack() ) {
				//notify admin about DOS attacks
				$last_notification = wfu_get_option("wfu_admin_notification_about_DOS", null);
				if ( $last_notification == null || time() - (int)$last_notification > (int)WFU_VAR("WFU_DOS_ATTACKS_ADMIN_EMAIL_FREQUENCY") ) {
					$home = get_option("home");
					$subject = str_replace("{SITE}", $home, WFU_WARNING_POTENTIAL_DOS_EMAIL_SUBJECT);
					$message = str_replace(array( "{SITE}", "{FILENUM}", "{INTERVAL}" ), array( $home, WFU_VAR("WFU_DOS_ATTACKS_FILE_LIMIT"), WFU_VAR("WFU_DOS_ATTACKS_TIME_INTERVAL") ), WFU_WARNING_POTENTIAL_DOS_EMAIL_MESSAGE);
					wfu_notify_admin($subject, $message);
					wfu_update_option("wfu_admin_notification_about_DOS", time());
				}
				//errorabort flag designates that file will be aborted and no resuming will be attempted
				$file_output['message_type'] = "errorabort";
				$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_DOS_ATTACK);
				$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], WFU_ERROR_ADMIN_DOS_ATTACK);
			}
			else {
				if ( !$nofileupload ) {
					/* generate safe filename by removing invalid characters if
					   forcefilename is deactivated */
					if ( $params['forcefilename'] != "true" ) $only_filename = wfu_upload_plugin_clean( $only_filename );
					/* in case that forcefilename is activated then strip tags
					   as a minimum measure against hacking */
					else $only_filename = strip_tags( $only_filename );
					//reconstruct target_path
					$target_path = wfu_basedir($target_path).$only_filename;

					/* if medialink or postlink is activated then the target path becomes the current wordpress upload folder */
					if ( $params["medialink"] == "true" || $params["postlink"] == "true" ) {
						$mediapath = wp_upload_dir();
						$target_path = $mediapath['path'].'/'.$only_filename;
					}
					/* Check if this is an sftp upload and sftp is supported */
					if ( substr($target_path, 0, 7) == "sftp://" && !function_exists("ssh2_connect") ) {
						$upload_path_ok = false;
						$sftp_not_supported = true;
					}
					/* Check if upload path exists */
					elseif ( wfu_is_dir( wfu_basedir($target_path), $params["ftpinfo"] ) ) {		
						$upload_path_ok = true;
					}
					/* Attempt to create path if user has selected to do so */ 
					else if ( $params["createpath"] == "true" ) {
						$wfu_create_directory_ret = wfu_create_directory(wfu_basedir($target_path), $params["accessmethod"], $params["ftpinfo"]);
						if ( $wfu_create_directory_ret != "" ) {
							$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], $wfu_create_directory_ret);
						}
						if ( wfu_is_dir( wfu_basedir($target_path), $params["ftpinfo"] ) ) {		
							$upload_path_ok = true;
						}
					}

					/* File name control, reject files with .php, .js (and other) extensions for security reasons.
					   This is the first pass of extension control, which only checks the filename.
					   A second pass is performed after the file has completely uploaded, using WP inherent file
					   extension control, which provides better security. */
					if ( !wfu_file_extension_blacklisted(strtolower($only_filename)) )
						foreach ($allowed_patterns as $allowed_pattern) {
							if ( wfu_file_extension_matches_pattern($allowed_pattern, strtolower($only_filename)) ) {
								$allowed_file_ok = true;
								break ;
							}
						}

					/* File size control */
					if ( $upload_file_size_MB <= $params["maxsize"] ) {
						if ( $params['php_env'] == '32bit' && $upload_file_size > 2147483647 ) $size_file_phpenv_ok = false;
						else $size_file_ok = true;
					}
				}
				/* In case of no file upload then bypass above checks */
				else {
					$upload_path_ok = true;
					$allowed_file_ok = true;
					$size_file_ok = true;
				}
	
				if ( !$upload_path_ok or !$allowed_file_ok or !$size_file_ok ) {
					//abort the file, no resuming will be attempted
					$file_output['message_type'] = "errorabort";
					$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_UPLOAD_FAILED);

					if ( !$upload_path_ok ) $file_output['message'] = wfu_join_strings("<br />", $file_output['message'], ( $sftp_not_supported ? WFU_ERROR_ADMIN_SFTP_UNSUPPORTED : WFU_ERROR_DIR_EXIST ));
					if ( !$allowed_file_ok ) $file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_FILE_ALLOW);
					if ( !$size_file_ok ) {
						if ( $size_file_phpenv_ok ) $file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_FILE_PLUGIN_SIZE);
						else $file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_FILE_PLUGIN_2GBSIZE);
					}
				}
			}
		}
		else {
			// This block is executed when there is an error
			$upload_error = $fileprops['error'];
			if ( $upload_error == 1 ) {
				$message_text = WFU_ERROR_FILE_PHP_SIZE;
				$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], WFU_ERROR_ADMIN_FILE_PHP_SIZE);
			}
			elseif ( $upload_error == 2 ) $message_text = WFU_ERROR_FILE_HTML_SIZE;
			elseif ( $upload_error == 3 ) $message_text = WFU_ERROR_FILE_PARTIAL;
			elseif ( $upload_error == 4 ) $message_text = WFU_ERROR_FILE_NOTHING;
			elseif ( $upload_error == 6 ) $message_text = WFU_ERROR_DIR_NOTEMP;
			elseif ( $upload_error == 7 ) $message_text = WFU_ERROR_FILE_WRITE;
			elseif ( $upload_error == 8 ) $message_text = WFU_ERROR_UPLOAD_STOPPED;
			else {
				$upload_time_limit = ini_get("max_input_time");
				$params_output_array["general"]['upload_finish_time'] = $params["upload_start_time"] + $upload_time_limit * 1000;
				$message_text = WFU_ERROR_FILE_PHP_TIME;
				$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], WFU_ERROR_ADMIN_FILE_PHP_TIME);
			}
			//error (and not errorabort) flag designates that a resuming of the file may be attempted
			$file_output['message_type'] = "error";
			$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], $message_text);
		}
		
		$message_processed = false;
//		if ( $upload_path_ok and $allowed_file_ok and $size_file_ok ) {
		if ( substr($file_output['message_type'], 0, 5) != "error" ) {

			if ( is_uploaded_file($fileprops['tmp_name']) || $only_check || $nofileupload ) {
				$source_path = $fileprops['tmp_name'];
				
				if ( $only_check || $ignore_server_actions || $nofileupload ) $file_copied = true;
				else {
					$file_copied = false;

					if ($source_path) {
						$file_exists = wfu_file_exists_extended($target_path);
						if ( !$file_exists || $params["duplicatespolicy"] == "" || $params["duplicatespolicy"] == "overwrite" ) {
							//redirect echo in internal buffer to receive and process any unwanted warning messages from wfu_upload_file
							ob_start();
							ob_clean();
							/* Apply wfu_before_file_upload filter right before the upload, in order to allow the user to change the file name.
							   If additional data are required, such as user_id or userdata values, they can be retrieved by implementing the
							   previous filter wfu_before_file_check, corresponding them to the unique file id */
							if ( $file_unique_id != '' ) {
								$target_path = apply_filters('wfu_before_file_upload', $target_path, $file_unique_id);
								$file_map_arr = WFU_USVAR($file_map);
								$file_map_arr['filepath'] = $target_path;
								WFU_USVAR_store($file_map, $file_map_arr);
							}
							//recalculate $only_filename in case it changed with wfu_before_file_upload filter
							$only_filename = wfu_basename($target_path);
							//move the uploaded file to its final destination
							$wfu_upload_file_ret = wfu_upload_file($source_path, $target_path, $params["accessmethod"], $params["ftpinfo"], $params["ftppassivemode"], $params["ftpfilepermissions"]);
							$file_copied = $wfu_upload_file_ret["uploaded"];
							//process warning messages from wfu_upload_file
							$echo_message = ob_get_contents();
							//finish redirecting of echo to internal buffer
							ob_end_clean();
							if ( $echo_message != "" && !$file_copied ) {
								//error (and not errorabort) flag designates that file may be resumed
								$file_output['message_type'] = "error";
								if ( stristr($echo_message, "warning") && stristr($echo_message, "permission denied") && stristr($echo_message, "unable to move") ) {
									$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_DIR_PERMISSION);
									$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], WFU_ERROR_ADMIN_DIR_PERMISSION);
								}
								else { 
									$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_FILE_MOVE);
									$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], strip_tags($echo_message));
								}
								$message_processed = true;
							}
							if ( $wfu_upload_file_ret["admin_message"] != "" ) {
								$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], $wfu_upload_file_ret["admin_message"]);
							}
						}
						else if ( $file_exists && $params["duplicatespolicy"] == "maintain both" ) {
							$full_path = wfu_basedir($target_path);
							$name_part = $only_filename;
							$ext_part = "";
							$dot_pos = strrpos($name_part, ".");
							if ( $dot_pos ) {
								$ext_part = substr($name_part, $dot_pos);
								$name_part = substr($name_part, 0, $dot_pos);
							}
							if ( $params["uniquepattern"] != "datetimestamp" ) {
								$unique_ind = 1;
								do {
									$unique_ind += 1;
									$only_filename = $name_part . "(" . $unique_ind . ")" . $ext_part;
									$target_path = $full_path . $only_filename;
								}
								while ( wfu_file_exists_extended($target_path) );
							}
							else {
								$current_datetime = gmdate("U") - 1;
								do {
									$current_datetime += 1;
									$only_filename = $name_part . "-" . gmdate("YmdHis", $current_datetime) . $ext_part;
									$target_path = $full_path . $only_filename;
								}
								while ( wfu_file_exists_extended($target_path) );
							}
							//redirect echo in internal buffer to receive and process any unwanted warning messages from move_uploaded_file
							ob_start();
							ob_clean();
							/* Apply wfu_before_file_upload filter right before the upload, in order to allow the user to change the file name.
							   If additional data are required, such as user_id or userdata values, they can be retrieved by implementing the
							   previous filter wfu_before_file_check, corresponding them to the unique file id */
							if ( $file_unique_id != '' ) {
								$target_path = apply_filters('wfu_before_file_upload', $target_path, $file_unique_id);
								$file_map_arr = WFU_USVAR($file_map);
								$file_map_arr['filepath'] = $target_path;
								WFU_USVAR_store($file_map, $file_map_arr);
							}
							//recalculate $only_filename in case it changed with wfu_before_file_upload filter
							$only_filename = wfu_basename($target_path);
							//move the uploaded file to its final destination
							$wfu_upload_file_ret = wfu_upload_file($source_path, $target_path, $params["accessmethod"], $params["ftpinfo"], $params["ftppassivemode"], $params["ftpfilepermissions"]);
							$file_copied = $wfu_upload_file_ret["uploaded"];
							//process warning messages from move_uploaded_file
							$echo_message = ob_get_contents();
							//finish redirecting of echo to internal buffer
							ob_end_clean();
							if ( $echo_message != "" && !$file_copied ) {
								//error (and not errorabort) flag designates that file may be resumed
								$file_output['message_type'] = "error";
								if ( stristr($echo_message, "warning") && stristr($echo_message, "permission denied") && stristr($echo_message, "unable to move") ) {
									$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_DIR_PERMISSION);
									$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], WFU_ERROR_ADMIN_DIR_PERMISSION);
								}
								else { 
									$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_FILE_MOVE);
									$file_output['admin_messages'] = wfu_join_strings("<br />n", $file_output['admin_messages'], strip_tags($echo_message));
								}
								$message_processed = true;
							}
							if ( $wfu_upload_file_ret["admin_message"] != "" ) {
								$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], $wfu_upload_file_ret["admin_message"]);
							}
						}
						else {
							//abort the file and do not allow resuming
							$file_output['message_type'] = "errorabort";
							$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_WARNING_FILE_EXISTS);
							$message_processed = true;
							$file_copied = false;
						}
					}
				}

				if ( $file_copied ) {
					/* prepare email notification parameters if email notification is enabled */
					if ( $params["notify"] == "true" && (!$only_check || $nofileupload) ) {
						if ( !$nofileupload ) array_push($notify_target_path_list, $target_path);
					} 

					/* prepare redirect link if redirection is enabled */
					if ( $params["redirect"] == "true" ) {
						/* Define dynamic redirect link from variables */
						$search = array ('/%filename%/', '/%username%/');	 
						$replace = array ($only_filename, $user_login);
						$params_output_array["general"]['redirect_link'] =  trim(preg_replace($search, $replace, $params["redirectlink"]));
					}
					
					if ( !$message_processed ) {
						$file_output['message_type'] = "success";
					}
				}
				else if ( !$message_processed ) {
					//abort the file and do not allow resuming
					$file_output['message_type'] = "errorabort";
					$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_UNKNOWN);
				}

				/* Delete temporary file (in tmp directory) */
//				unlink($source_path);			
			}
			else {
				//abort the file and do not allow resuming
				$file_output['message_type'] = "errorabort";
				$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_UNKNOWN);
			}
		}

		/* last check of output file status */
		if ( $file_output['message_type'] == "" ) {
			if ( $file_copied ) $file_output['message_type'] = "success";
			else {
				//abort the file and do not allow resuming
				$file_output['message_type'] = "errorabort";
				$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_UNKNOWN);
			}
		}

		/* suppress any admin messages if user is not administrator or adminmessages is not activated */		
		if ( $suppress_admin_messages ) $file_output['admin_messages'] = "";

		/* set file status to "warning" if the file has been uploaded but there are messages */
		if ( $file_output['message_type'] == "success" ) {
			if ( $file_output['message'] != "" || $file_output['admin_messages'] != "" )
				$file_output['message_type'] = "warning";
		}

		/* set success status of the file, to be used for medialink and post actions */
		$file_finished_successfully = ( (!$only_check || $nofileupload) && ( $file_output['message_type'] == "success" || $file_output['message_type'] == "warning" ) );
		/* set non-success status of the file, to be used for medialink and post actions */
		$file_finished_unsuccessfully = ( substr($file_output['message_type'], 0, 5) == "error" );


		/* perform custom actions after file is completely uploaded in order to determine if file is valid ir not */
		if ( $file_finished_successfully && !$ignore_server_actions && !$nofileupload ) {
			/* Here the second pass of file extension control is performed after the file has completely
			   uploaded, using WP inherent functions that determine the real extension from analyzing the
			   data and not from the filename extension. If this check reveals an extension which is not
			   permitted then the file will be rejected and erased. If the real extension is different
			   than the original one but it is permitted, then the file will remain as it is but a warning
			   message will notify the user that the extension of the file does not match its contents. */
			$check = wp_check_filetype_and_ext( $target_path, $only_filename, false );
			if ( $check['proper_filename'] !== false ) {
				$proper_filename = $check['proper_filename'];
				if ( wfu_file_extension_blacklisted(strtolower($only_filename)) ) {
					$file_finished_successfully = false;
					$file_finished_unsuccessfully = true;
					unlink($target_path);
					$file_output['message_type'] = "errorabort";
					$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_ERROR_FILE_REJECT);
					$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], WFU_ERROR_ADMIN_FILE_WRONGEXT.$check['proper_filename']);
				}
				else {
					$file_output['message_type'] = "warning";
					$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_WARNING_FILE_SUSPICIOUS);
					$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], WFU_WARNING_ADMIN_FILE_SUSPICIOUS.$check['proper_filename']);
				}
			}
			// run any wfu_after_file_loaded filters to make any last file checks and accept or reject it
			if ( $file_finished_successfully ) {
				$filter_error_message = '';
				$filter_admin_message = '';
				$changable_data['error_message'] = $filter_error_message;
				$changable_data['admin_message'] = $filter_admin_message;
				$additional_data['file_unique_id'] = $file_unique_id;
				$additional_data['file_path'] = $target_path;
				$additional_data['shortcode_id'] = $sid;
				$ret_data = apply_filters('wfu_after_file_loaded', $changable_data, $additional_data);
				//this is a call to wfu_after_file_complete filters, which is
				//the old name of wfu_after_file_loaded filters, for maintaining
				//backward compatibility
				$changable_data = $ret_data;
				$ret_data = apply_filters('wfu_after_file_complete', $changable_data, $additional_data);
				$filter_error_message = $ret_data['error_message'];
				$filter_admin_message = $ret_data['admin_message'];
				if ( $filter_error_message != '' ) {
					$file_finished_successfully = false;
					$file_finished_unsuccessfully = true;
					unlink($target_path);
					$file_output['message_type'] = "errorabort";
					$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], $filter_error_message);
					if ( $filter_admin_message != '' )
						$file_output['admin_messages'] = wfu_join_strings("<br />", $file_output['admin_messages'], $filter_admin_message);
				}
			}			
		}
	
		/* in case that the file will not be saved due to personal data policy
		   then convert any success message_type to warning */
		if ( $store_nothing && $file_output['message_type'] == "success" ) {
			$file_output['message_type'] = "warning";
			$file_output['message'] = wfu_join_strings("<br />", $file_output['message'], WFU_UPLOAD_STATE19_SINGLEFILE);
		}
		
		/* adjust message details and colors according to file result */
		/* FileResult: A */
		$search = array ('/%username%/', '/%useremail%/', '/%filename%/', '/%filepath%/');	 
		$replace = array ($user_login, ( $user_email == "" ? "no email" : $user_email ), $only_filename, $target_path);
		if ( $file_output['message_type'] == "success" ) {
			$success_count ++;
			$color_array = explode(",", $params['successmessagecolors']);
			$file_output['color'] = $color_array[0];
			$file_output['bgcolor'] = $color_array[1];
			$file_output['borcolor'] = $color_array[2];
			$file_output['header'] = preg_replace($search, $replace, $params['successmessage']);
			/* prepare details of successful file upload, visible only to administrator */
			$file_output['admin_messages'] = wfu_join_strings("<br />", preg_replace($search, $replace, WFU_SUCCESSMESSAGE_DETAILS), $file_output['admin_messages']);
		}
		/* FileResult: B */
		elseif ( $file_output['message_type'] == "warning" ) {
			$warning_count ++;
			$color_array = explode(",", $params['warningmessagecolors']);
			$file_output['color'] = $color_array[0];
			$file_output['bgcolor'] = $color_array[1];
			$file_output['borcolor'] = $color_array[2];
			$file_output['header'] = preg_replace($search, $replace, ( $store_nothing ? WFU_WARNINGMESSAGE_NOSAVE : $params['warningmessage'] ));
			/* prepare and prepend details of successful file upload, visible only to administrator */
			$file_output['admin_messages'] = wfu_join_strings("<br />", preg_replace($search, $replace, WFU_SUCCESSMESSAGE_DETAILS), $file_output['admin_messages']);
		}
		/* FileResult: C */
		elseif ( substr($file_output['message_type'], 0, 5) == "error" ) {
			$error_count ++;
			$color_array = explode(",", $params['failmessagecolors']);
			$file_output['color'] = $color_array[0];
			$file_output['bgcolor'] = $color_array[1];
			$file_output['borcolor'] = $color_array[2];
			$replace = array ($user_login, ( $user_email == "" ? "no email" : $user_email ), $only_filename, $target_path);
			$file_output['header'] = preg_replace($search, $replace, $params['errormessage']);
			/* prepare and prepend details of failed file upload, visible only to administrator */
			if ( !$nofileupload ) $file_output['admin_messages'] = wfu_join_strings("<br />", preg_replace($search, $replace, WFU_FAILMESSAGE_DETAILS), $file_output['admin_messages']);
		}

		/* suppress again any admin messages if user is not administrator or adminmessages is not activated */		
		if ( $suppress_admin_messages ) $file_output['admin_messages'] = "";

		$params_output_array[0] = $file_output;

		if ( $file_unique_id != '' && $file_finished_unsuccessfully && !$ignore_server_actions ) {
			/* Apply wfu_after_file_upload filter after failed upload, in order to allow the user to perform any post-upload actions.
			   If additional data are required, such as user_id or userdata values or filepath, they can be retrieved by implementing
			   the previous filters wfu_before_file_check and wfu_before_file_upload, corresponding them to the unique file id.
			   This actions allows to define custom javascript code to run after each file finishes (either succeeded or failed).
			   For backward compatibility, the wfu_after_file_upload action that was implemented in previous version of the plugin
			   still remains. */
			$changable_data['ret_value'] = null;
			$changable_data['js_script'] = '';
			$additional_data['shortcode_id'] = $sid;
			$additional_data['unique_id'] = $unique_id;
			if ( !$nofileupload ) $additional_data['file_unique_id'] = $file_unique_id;
			if ( !$nofileupload ) $additional_data['upload_result'] = $file_output['message_type'];
			else $additional_data['submit_result'] = $file_output['message_type'];
			$additional_data['error_message'] = $file_output['message'];
			$additional_data['admin_messages'] = $file_output['admin_messages'];
			if ( !$nofileupload ) $ret_data = apply_filters('wfu_after_file_upload', $changable_data, $additional_data);
			else $ret_data = apply_filters('wfu_after_data_submit', $changable_data, $additional_data);
			$params_output_array["general"]['js_script'] = $ret_data['js_script'];
//			do_action('wfu_after_file_upload', $file_unique_id, $file_output['message_type'], $file_output['message'], $file_output['admin_messages']);
		}

		if ( $file_finished_successfully && !$ignore_server_actions ) {
			/* Log file upload action if file has finished uploading
			   uccessfully. If this is a no file upload then log action will be
			   datasubmit. */
			if ( !$nofileupload ) {
				if ( !$consent_revoked ) $fileid = wfu_log_action('upload', $target_path, $user->ID, $unique_id, $params['pageid'], $params['blogid'], $sid, $userdata_fields);
				elseif ( !$not_store_files ) $fileid = wfu_log_action('upload', $target_path, 0, $unique_id, $params['pageid'], $params['blogid'], $sid, $empty_userdata_fields);
			}
			else {
				if ( !$consent_revoked ) $fileid = wfu_log_action('datasubmit', '', $user->ID, $unique_id, $params['pageid'], $params['blogid'], $sid, $userdata_fields);
			}
			/* Apply wfu_after_file_upload filter after failed upload, in order to allow the user to perform any post-upload actions.
			   If additional data are required, such as user_id or userdata values or filepath, they can be retrieved by implementing
			   the previous filters wfu_before_file_check and wfu_before_file_upload, corresponding them to the unique file id.
			   This actions allows to define custom javascript code to run after each file finishes (either suceeded or failed).
			   For backward compatibility, the wfu_after_file_upload action that was implemented in previous version of the plugin
			   still remains. */
			$changable_data['ret_value'] = null;
			$changable_data['js_script'] = '';
			$additional_data['shortcode_id'] = $sid;
			$additional_data['unique_id'] = $unique_id;
			if ( !$nofileupload ) $additional_data['file_unique_id'] = $file_unique_id;
			if ( !$nofileupload ) $additional_data['upload_result'] = $file_output['message_type'];
			else $additional_data['submit_result'] = $file_output['message_type'];
			$additional_data['error_message'] = $file_output['message'];
			$additional_data['admin_messages'] = $file_output['admin_messages'];
			if ( !$nofileupload ) $ret_data = apply_filters('wfu_after_file_upload', $changable_data, $additional_data);
			else $ret_data = apply_filters('wfu_after_data_submit', $changable_data, $additional_data);
			$params_output_array["general"]['js_script'] = $ret_data['js_script'];
//			do_action('wfu_after_file_upload', $file_unique_id, $file_output['message_type'], $file_output['message'], $file_output['admin_messages']);
		}

		/* add file to Media or attach file to current post if any of these options is activated and the file has finished uploading successfully */
		if ( ( $params["medialink"] == "true" || $params["postlink"] == "true" ) && $file_finished_successfully && !$ignore_server_actions && !$nofileupload ) {
			$pageid = ( $params["postlink"] == "true" ? $params['pageid'] : 0 );
			if ( !$consent_revoked ) wfu_process_media_insert($target_path, $userdata_fields, $pageid);
			elseif ( !$not_store_files ) wfu_process_media_insert($target_path, empty_userdata_fields, $pageid);
		}

		/* store final file data and upload result to filemap session array for
		   use by after_upload filters */
		if ( ( $file_finished_successfully || $file_finished_unsuccessfully ) && !$ignore_server_actions && !$nofileupload ) {
			if ( WFU_USVAR_exists("filedata_".$unique_id) ) {
				$filedata_id = WFU_USVAR("filedata_".$unique_id);
				if ( isset($filedata_id[$real_file_index]) ) {
					$filedata_id[$real_file_index]["filepath"] = $target_path;
					$filedata_id[$real_file_index]["user_data"] = $userdata_fields;
					$filedata_id[$real_file_index]["upload_result"] = $file_output['message_type'];
					$filedata_id[$real_file_index]["message"] = $file_output['message'];
					$filedata_id[$real_file_index]["admin_messages"] = $file_output['admin_messages'];
					WFU_USVAR_store("filedata_".$unique_id, $filedata_id);
				}
			}
		}
	}

	// in case of file check set files_count to 0 in order to denote that the file was not really uploaded
	if ( $only_check && !$nofileupload ) $params_output_array["general"]['files_count'] = 0;

	$somefiles_Ok = ( ( $warning_count + $success_count ) > 0 );
	$allfiles_Ok = ( $somefiles_Ok && ( $error_count == 0 ) );

	/* Prepare WPFileBase Plugin update url, if this option has been selected and only if at least one file has been successfully uploaded.
	   Execution will happen only if accumulated $params_output_array["general"]['update_wpfilebase'] is not empty */
	if ( $params["filebaselink"] == "true" && !$nofileupload ) {
		if ( $somefiles_Ok ) {		
			$filebaseurl = site_url();
			if ( substr($filebaseurl, -1, 1) == "/" ) $filebaseurl = substr($filebaseurl, 0, strlen($filebaseurl) - 1);
			/* if the following variable is not empty, then WPFileBase Plugin update must be executed
			   and any admin messages must be suppressed */
			$params_output_array["general"]['update_wpfilebase'] = $filebaseurl;
		}
		else {
			$params_output_array["general"]['admin_messages']['wpfilebase'] = WFU_WARNING_WPFILEBASE_NOTUPDATED_NOFILES;
			$params_output_array["general"]['errors']['wpfilebase'] = "error";
		}
	} 

	/* Prepare email notification parameters if email notification is enabled and only if at least one file has been successfully uploaded
	   	if $method = "no-ajax" then send the email to the recipients 
	   	if $method = "ajax" then return the notification parameters to the handler for further processing
	   In case of ajax, execution will happen only if notify_by_email is greater than 0 */
	if ( $params["notify"] == "true" ) {
		/* verify that there are recipients */
		$notifyrecipients =  trim(preg_replace('/%useremail%/', $user_email, $params["notifyrecipients"]));
		if ( $notifyrecipients != "" ) {
			if ( $somefiles_Ok || $force_notifications ) {	
				if ( $method == 'no_ajax' && !$ignore_server_actions ) {
					$send_error = wfu_send_notification_email($user, $notify_target_path_list, $userdata_fields, $params);
					if ( $send_error != "" ) {
						$params_output_array["general"]['admin_messages']['notify'] = $send_error;
						$params_output_array["general"]['errors']['notify'] = "error";
					}
				}
				else {
					/* if the following variable is not empty, then email notification must be sent
					   and any admin messages must be suppressed */
					$params_output_array["general"]['notify_by_email'] = ( !$nofileupload && !$force_notifications ? count($notify_target_path_list) : 1 );
				}
			}
			else {
				$params_output_array["general"]['admin_messages']['notify'] = WFU_WARNING_NOTIFY_NOTSENT_NOFILES;
				$params_output_array["general"]['errors']['notify'] = "error";
			}
		}
		else {
			$params_output_array["general"]['admin_messages']['notify'] = WFU_WARNING_NOTIFY_NOTSENT_NORECIPIENTS;
			$params_output_array["general"]['errors']['notify'] = "error";
		}
	} 

	/* Prepare redirect link if redirection is enabled and only if all files have been successfully uploaded
	   Execution will happen only if accumulated redirect_link is not empty and accumulated redirect errors are empty */
	if ( $params["redirect"] == "true" ) {
		if ( $params_output_array["general"]['redirect_link'] == "" ) {
			$params_output_array["general"]['admin_messages']['redirect'] = WFU_WARNING_REDIRECT_NOTEXECUTED_EMPTY;
			$params_output_array["general"]['errors']['redirect'] = "error";
		}
		elseif ( !$allfiles_Ok ) {
			$params_output_array["general"]['admin_messages']['redirect'] = WFU_WARNING_REDIRECT_NOTEXECUTED_FILESFAILED;
			$params_output_array["general"]['errors']['redirect'] = "error";
		}
	}

	/* suppress any admin messages if user is not administrator or adminmessages is not activated */		
	if ( $suppress_admin_messages ) {
		$params_output_array["general"]['admin_messages']['wpfilebase'] = "";
		$params_output_array["general"]['admin_messages']['notify'] = "";
		$params_output_array["general"]['admin_messages']['redirect'] = "";
		$params_output_array["general"]['admin_messages']['other'] = "";
	}

	/* Calculate upload state from file results */
	if ( $allfiles_Ok && ( $warning_count == 0 ) ) $params_output_array["general"]['state'] = ( !$nofileupload ? 4 : 14 );
	else if ( $allfiles_Ok ) $params_output_array["general"]['state'] = 5;
	else if ( $somefiles_Ok ) $params_output_array["general"]['state'] = 6;   //only valid in no-ajax method
	else if ( !$somefiles_Ok && $error_count > 0 ) $params_output_array["general"]['state'] = ( !$nofileupload ? 7 : 15 );
	else $params_output_array["general"]['state'] = 8;
	/* in case that the files will not be saved due to personal data policy
	   then adjust general state accordingly (effective for no-ajax uploads) */
	if ( !$nofileupload && $somefiles_Ok && $store_nothing  ) $params_output_array["general"]['state'] = 19;

	/* construct safe output */
	$sout = $params_output_array["general"]['state'].";".WFU_VAR("WFU_DEFAULTMESSAGECOLORS").";".$files_count;
	for ($i = 0; $i < $files_count; $i++) {
		$sout .= ";".wfu_plugin_encode_string($file_output['message_type']);
		$sout .= ",".wfu_plugin_encode_string($file_output['header']);
		$sout .= ",".wfu_plugin_encode_string($file_output['message']);
		$sout .= ",".wfu_plugin_encode_string($file_output['admin_messages']);
		$sout .= ",".$file_output['uploaded_file_props'];
	}
	$params_output_array["general"]['safe_output'] = $sout;

	return $params_output_array;
}

?>
