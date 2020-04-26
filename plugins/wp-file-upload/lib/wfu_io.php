<?php

/**
 * Create FTP Directory Recursively.
 *
 * This function creates an FTP directory recursively (including
 * subdirectories).
 *
 * @since 3.10.0
 *
 * @redeclarable
 *
 * @param stream $conn_id The FTP connection ID.
 * @param string $basepath The parent path of the directory to be created.
 * @param string $path The directory to be created.
 */
function wfu_mk_dir_deep($conn_id, $basepath, $path) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	@ftp_chdir($conn_id, $basepath);
	$parts = explode('/', $path);
	foreach ( $parts as $part ) {
		if( !@ftp_chdir($conn_id, $part) ) {
			ftp_mkdir($conn_id, $part);
			ftp_chdir($conn_id, $part);
			ftp_chmod($conn_id, 493, $part);
		}
	}
}

/**
 * Check If Path Is Directory.
 *
 * This function checks whether a path is a valid directory.
 *
 * @since 3.9.1
 *
 * @redeclarable
 *
 * @param string $path The path to check.
 * @param string $ftpdata FTP credentials in case of FTP method.
 *
 * @return bool True if the path is directory, false otherwise.
 */
function wfu_is_dir($path, $ftpdata) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$result = false;
	//check whether this is an sftp dir
	if ( substr($path, 0, 7) == "sftp://" ) {
		$ftpinfo = wfu_decode_ftpinfo($ftpdata);
		if ( !$ftpinfo["error"] ) {
			$data = $ftpinfo["data"];
			//extract relative FTP path
			$ftp_port = $data["port"];
			if ( $ftp_port == "" ) $ftp_port = "22";
			$flat_host = preg_replace("/^(.*\.)?([^.]*\..*)$/", "$2", $data["ftpdomain"].":".$ftp_port);
			$pos1 = strpos($path, $flat_host);
			if ( $pos1 ) {
				$path = substr($path, $pos1 + strlen($flat_host));
				{
					$conn = ssh2_connect($data["ftpdomain"], $ftp_port);
					if ( $conn && @ssh2_auth_password($conn, $data["username"], $data["password"]) ) {
						$sftp = @ssh2_sftp($conn);
						if ( $sftp ) {
							$result = is_dir('ssh2.sftp://'.intval($sftp).$path);
						}
					}
				}
			}
		}
	}
	else $result = is_dir($path);
	
	return $result;
}

/**
 * Create Directory.
 *
 * This function creates a directory.
 *
 * @since 2.1.2
 *
 * @redeclarable
 *
 * @param string $path The path of the directory to create.
 * @param string $method File upload method, 'normal' or 'ftp'.
 * @param string $ftpdata FTP credentials in case of FTP method.
 *
 * @return string Empty string if the directory was created successfully, or an
 *         error message if it failed.
 */
function wfu_create_directory($path, $method, $ftpdata) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$ret_message = "";
	if ( $method == "" || $method == "normal" ) {
		mkdir($path, 0777, true);
	}
	else if ( $method == "ftp" && $ftpdata != "" ) {
		$ftpinfo = wfu_decode_ftpinfo($ftpdata);
		if ( !$ftpinfo["error"] ) {
			$data = $ftpinfo["data"];
			//extract relative FTP path
			$ftp_port = $data["port"];
			if ( $data["sftp"] && $ftp_port == "" ) $ftp_port = "22";
			$flat_host = preg_replace("/^(.*\.)?([^.]*\..*)$/", "$2", $data["ftpdomain"].( $ftp_port != "" ? ":".$ftp_port : "" ));
			$pos1 = strpos($path, $flat_host);
			if ( $pos1 ) {
				$path = substr($path, $pos1 + strlen($flat_host));
				if ( $data["sftp"] ) {
					wfu_create_dir_deep_sftp($data["ftpdomain"], $ftp_port, $data["username"], $data["password"], $path);
				}
				else {
					if ( $ftp_port != "" ) $conn_id = ftp_connect($data["ftpdomain"], $ftp_port);
					else $conn_id = ftp_connect($data["ftpdomain"]);
					$login_result = ftp_login($conn_id, $data["username"], $data["password"]);
					if ( $conn_id && $login_result ) {
						wfu_mk_dir_deep($conn_id, '/', $path);
					}
					else {
						$ret_message = WFU_ERROR_ADMIN_FTPINFO_INVALID;
					}
					ftp_quit($conn_id);
				}
			}
			else {
				$ret_message = WFU_ERROR_ADMIN_FTPFILE_RESOLVE;
			}
		}
		else {
			$ret_message = WFU_ERROR_ADMIN_FTPINFO_EXTRACT;
		}
	}
	else {
		$ret_message = WFU_ERROR_ADMIN_FTPINFO_INVALID;
	}
	return $ret_message;
}

/**
 * Store the Uploaded File.
 *
 * This function stores the uploaded file that was saved in a temporary location
 * to its final destination. In case of a chunked upload, then the source does
 * not contain the whole file, but only a part of it. The chunk is stored in the
 * partial file in the correct position.
 *
 * @since 2.1.2
 *
 * @redeclarable
 *
 * @param string $source The temporary source path of the uploaded file.
 * @param string $target The final path of the uploaded file.
 * @param string $method File upload method, 'normal', 'ftp' or 'chunked'. In
 *        case of 'chunked' method it contains information about the chunks.
 * @param string $ftpdata FTP credentials in case of FTP method.
 * @param string $passive 'true' if FTP passive mode will be used.
 * @param string $fileperms File permissions of the stored file (FTP method).
 *
 * @return array {
 *         Store result info.
 *
 *         @type bool $uploaded True if the file was stored successfully.
 *         @type string $admin_message An admin error message on failure.
 * }
 */
function wfu_upload_file($source, $target, $method, $ftpdata, $passive, $fileperms) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$ret_array = array();
	$ret_array["uploaded"] = false;
	$ret_array["admin_message"] = "";
	$ret_message = "";
	$target_perms = substr(sprintf('%o', fileperms(dirname($target))), -4);
	$target_perms = octdec($target_perms);
	$target_perms = (int)$target_perms;
	if ( $method == "" || $method == "normal" ) {
		$ret_array["uploaded"] = move_uploaded_file($source, $target);
		if ( !$ret_array["uploaded"] && !is_writable(dirname($target)) ) {
			$ret_message = WFU_ERROR_ADMIN_DIR_PERMISSION;
		}
	}
	elseif ( $method == "ftp" &&  $ftpdata != "" ) {
		$result = false;
		$ftpinfo = wfu_decode_ftpinfo($ftpdata);
		if ( !$ftpinfo["error"] ) {
			$data = $ftpinfo["data"];
			//extract relative FTP path
			$ftp_port = $data["port"];
			if ( $data["sftp"] && $ftp_port == "" ) $ftp_port = "22";
			$flat_host = preg_replace("/^(.*\.)?([^.]*\..*)$/", "$2", $data["ftpdomain"].( $ftp_port != "" ? ":".$ftp_port : "" ));
			$pos1 = strpos($target, $flat_host);
			if ( $pos1 ) {
				$target = substr($target, $pos1 + strlen($flat_host));
				if ( $data["sftp"] ) {
					$ret_message = wfu_upload_file_sftp($data["ftpdomain"], $ftp_port, $data["username"], $data["password"], $source, $target, $fileperms);
					$ret_array["uploaded"] = ( $ret_message == "" );
					unlink($source);
				}
				else {
					if ( $ftp_port != "" ) $conn_id = ftp_connect($data["ftpdomain"], $ftp_port);
					else $conn_id = ftp_connect($data["ftpdomain"]);
					$login_result = ftp_login($conn_id, $data["username"], $data["password"]);
					if ( $conn_id && $login_result ) {
						if ( $passive == "true" ) ftp_pasv($conn_id, true);
//						$temp_fname = tempnam(dirname($target), "tmp");
//						move_uploaded_file($source, $temp_fname);
//						ftp_chmod($conn_id, 0755, dirname($target));
						$ret_array["uploaded"] = ftp_put($conn_id, $target, $source, FTP_BINARY);
						//apply user-defined permissions to file
						$fileperms = trim($fileperms);
						if ( strlen($fileperms) == 4 && sprintf("%04o", octdec($fileperms)) == $fileperms ) {
							$fileperms = octdec($fileperms);
							$fileperms = (int)$fileperms;
							ftp_chmod($conn_id, $fileperms, $target);
						}
//						ftp_chmod($conn_id, 0755, $target);
//						ftp_chmod($conn_id, $target_perms, dirname($target));
						unlink($source);
						if ( !$ret_array["uploaded"] ) {
							$ret_message = WFU_ERROR_ADMIN_DIR_PERMISSION;
						}
					}
					else {
						$ret_message = WFU_ERROR_ADMIN_FTPINFO_INVALID;
					}
					ftp_quit($conn_id);
				}
			}
			else {
				$ret_message = WFU_ERROR_ADMIN_FTPFILE_RESOLVE;
			}
		}
		else {
			$ret_message = WFU_ERROR_ADMIN_FTPINFO_EXTRACT.$ftpdata;
		}
	}		
	else {
		$ret_message = WFU_ERROR_ADMIN_FTPINFO_INVALID;
	}

	$ret_array["admin_message"] = $ret_message;
	return $ret_array;
}

/**
 * Store the Uploaded File in sFTP.
 *
 * This function stores the uploaded file that was saved in a temporary location
 * to its final sFTP destination.
 *
 * @since 4.0.0
 *
 * @redeclarable
 *
 * @param string $ftp_host The sFTP host.
 * @param string $ftp_port The sFTP port.
 * @param string $ftp_username Username for sFTP authentication.
 * @param string $ftp_password Password for sFTP authentication.
 * @param string $source The temporary source path of the uploaded file.
 * @param string $target The final path of the uploaded file.
 * @param string $fileperms File permissions of the stored file (FTP method).
 *
 * @return string Empty string if the file was stored successfully, or an error
 *         message if it failed.
 */
function wfu_upload_file_sftp($ftp_host, $ftp_port, $ftp_username, $ftp_password, $source, $target, $fileperms) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$ret_message = "";
	{
		$conn = @ssh2_connect($ftp_host, $ftp_port);
		if ( !$conn ) $ret_message = WFU_ERROR_ADMIN_FTPHOST_FAIL;
		else {
			if ( !@ssh2_auth_password($conn, $ftp_username, $ftp_password) ) $ret_message = WFU_ERROR_ADMIN_FTPLOGIN_FAIL;
			else {
				$sftp = @ssh2_sftp($conn);
				if ( !$sftp ) $ret_message = WFU_ERROR_ADMIN_SFTPINIT_FAIL;
				else {
					$f = @fopen("ssh2.sftp://".intval($sftp)."$target", 'w');
					if ( !$f ) $ret_message = WFU_ERROR_ADMIN_FTPFILE_RESOLVE;
					else {
						$contents = @file_get_contents($source);
						if ( $contents === false ) $ret_message = WFU_ERROR_ADMIN_FTPSOURCE_FAIL;
						else {
							if ( @fwrite($f, $contents) === false ) $ret_message = WFU_ERROR_ADMIN_FTPTRANSFER_FAIL;
							//apply user-defined permissions to file
							$fileperms = trim($fileperms);
							if ( strlen($fileperms) == 4 && sprintf("%04o", octdec($fileperms)) == $fileperms ) {
								$fileperms = octdec($fileperms);
								$fileperms = (int)$fileperms;
								ssh2_sftp_chmod($sftp, $target, $fileperms);
							}
						}
						@fclose($f);
					}
				}
			}
		}
	}
	
	return $ret_message;
}

/**
 * Create sFTP Directory Recursively.
 *
 * This function creates an sFTP directory recursively (including
 * subdirectories).
 *
 * @since 4.0.0
 *
 * @redeclarable
 *
 * @param string $ftp_host The sFTP host.
 * @param string $ftp_port The sFTP port.
 * @param string $ftp_username Username for sFTP authentication.
 * @param string $ftp_password Password for sFTP authentication.
 * @param string $path The path of the directory to create.
 *
 * @return string Empty string if the directory was created successfully, or an
 *         error message if it failed.
 */
function wfu_create_dir_deep_sftp($ftp_host, $ftp_port, $ftp_username, $ftp_password, $path) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$ret_message = "";
	{
		$conn = @ssh2_connect($ftp_host, $ftp_port);
		if ( !$conn ) $ret_message = WFU_ERROR_ADMIN_FTPHOST_FAIL;
		else {
			if ( !@ssh2_auth_password($conn, $ftp_username, $ftp_password) ) $ret_message = WFU_ERROR_ADMIN_FTPLOGIN_FAIL;
			else {
				$sftp = @ssh2_sftp($conn);
				if ( !$sftp ) $ret_message = WFU_ERROR_ADMIN_SFTPINIT_FAIL;
				else {
					ssh2_sftp_mkdir($sftp, $path, 493, true );
				}
			}
		}
	}
	
	return $ret_message;
}

?>
