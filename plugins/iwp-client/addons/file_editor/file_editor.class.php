<?php
// IWP_MMB_fileEditor
if(basename($_SERVER['SCRIPT_FILENAME']) == "file_editor.class.php"):
    exit;
endif;
class IWP_MMB_fileEditor extends IWP_MMB_Core
{
    function __construct()
    {
        parent::__construct();
    }

    function is_server_writable(){
        if((!defined('FTP_HOST') || !defined('FTP_USER') || !defined('FTP_PASS')) && (get_filesystem_method(array(), ABSPATH) != 'direct'))
            return false;
        else
            return true;
    }

    function iwp_mmb_direct_to_any_copy_dir($from, $to, $skip_list = array() ) {//FIX ME: directly call function in backup.class.php later
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
                        return new WP_Error('copy_failed', __('Could not copy file.'), $to . $filename);
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


function iwp_mmb_direct_to_any_copy($source, $destination, $overwrite = false, $mode = false){//FIX ME: directly call function in backup.class.php later
    global $wp_filesystem;
    if($wp_filesystem->method == 'direct'){
        return $wp_filesystem->copy($source, $destination, $overwrite, $mode);
    }
    elseif($wp_filesystem->method == 'ftpext' || $wp_filesystem->method == 'ftpsockets'){
        if ( ! $overwrite && $wp_filesystem->exists($destination) )
            return false;
            
        //put content   
        $source_handle = fopen($source, 'r');
        if ( ! $source_handle )
            return false;

        
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

        $wp_filesystem->chmod($destination, $mode);

        return $ret;
        
    }
}

    function get_file_editor_vars($abspath,$folderPath,$filePath,$ext){
        if($filePath[0] == ""){
            $filePath = untrailingslashit($filePath[1]);
        }else{
            $filePath = trailingslashit($filePath[0]).untrailingslashit($filePath[1]);
        }

        $folderName = $this->get_file_editor_folder_name($abspath,$folderPath);
        if($folderName){
            $folderPath = trailingslashit($folderName);
        }else{
            return false;
        }
        $tempFilePath = explode("/", $filePath);
        $temp=0;
        $tempPath=$folderPath;
        for($i=0;$i<count($tempFilePath)-1;$i++){
            $tempPath .= trailingslashit($tempFilePath[$i]);
            if (is_dir($tempPath )) {
                $temp = $i+1;
                $folderPath = $tempPath;
            }else{
                break;
            }
        }
        if ($temp != (count($tempFilePath)-1)) {
            array_splice($tempFilePath,0,$temp);
            $temp = array_splice($tempFilePath,-1,1);
            $fileName = $temp[0];
        }else{
            $temp = array_splice($tempFilePath,-1,1);
            $fileName = $temp[0];
            $tempFilePath = array();
        }              
        $fileName = $this->get_file_editor_file_name($fileName,$ext);
        $FILE = array('toPath'=>$folderPath,'toCreate'=>$tempFilePath,'name'=>$fileName);
        return $FILE;
    }

   

    function get_file_editor_folder_name($abspath,$folderVar){
        switch ($folderVar) {
            case 'admin':
                $folder = trailingslashit($abspath).'wp-admin';
                break;
            
            case 'content':
                $folder = WP_CONTENT_DIR;
                break;
            
            case 'plugins':
                $folder = WP_PLUGIN_DIR;
                break;
            
            case 'themes':
                $folder = get_theme_root();
                break;
            
            case 'uploads':
                $temp = wp_upload_dir();
                $folder = $temp['basedir'];
                break;
            
            case 'includes':
                $folder = trailingslashit($abspath).WPINC;
                break;
            
            case 'root':
                $folder = trailingslashit($abspath);
                break;
            
            default:
                $folder = false;
                break;
        }
        return $folder;
    }
    
    function get_file_editor_file_name($filePath,$ext){
        if(strrpos($filePath, '.')){
            $EXT = substr($filePath,strrpos($filePath, '.')+1);
            if($EXT == $ext) return $filePath;
            else return $filePath.'.'.$ext;
        }else return $filePath.'.'.$ext;

    }

    function file_editor_upload($params){
        global $wpdb, $wp_filesystem;
        include_once ABSPATH . 'wp-admin/includes/file.php';
        extract($params);
       if (!function_exists('gzinflate')) {
            return array(
                   'error' => 'Gzip library functions are not available.', 'error_code' => 'gzip_library_functions_are_not_available'
             );
        }else{
            $fileContent = base64_decode($fileContent);
            $fileContent = gzinflate($fileContent);
        }

        if (!$this->is_server_writable()) {
            return array(
                'error' => 'Failed, please add FTP details', 'error_code' => 'failed_please_add_FTP_details_file_editor_upload'
            );
        }
        
        $url = wp_nonce_url('index.php?page=iwp_no_page','iwp_fs_cred');
        ob_start();
        if (false === ($creds = request_filesystem_credentials($url, '', false, ABSPATH, null) ) ) {
            return array(
                   'error' => 'Unable to get file system credentials', 'error_code' => 'unable_to_get_file_system_credentials_file_editor_upload'
             );   // stop processing here
        }
        ob_end_clean();
        
        if ( ! WP_Filesystem($creds, ABSPATH) ) {
            return array(
                   'error' => 'Unable to initiate file system. Please check you have entered valid FTP credentials.', 'error_code' => 'unable_to_initiate_file_system_check_ftp_credentials_file_editor_upload'
             );   // stop processing here
        }

        require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');//will be used to copy from temp directory
        $temp_folder = untrailingslashit(get_temp_dir());
        $temp_uniq = 'iwp_'.md5(microtime(1));//should be random
        while (is_dir($temp_folder .'/'. $temp_uniq )) {
            $temp_uniq = 'iwp_'.md5(microtime(1));
        }
        $new_temp_folder = trailingslashit($temp_folder .'/'. $temp_uniq);
        $is_dir_created = mkdir($new_temp_folder);// new folder should be empty
        if(!$is_dir_created){
            return array(
                   'error' => 'Unable to create a temporary directory.', 'error_code' => 'unable_to_create_temporary_directory_file_editor_upload'
            );
        }

        $remote_abspath = $wp_filesystem->abspath();
        if(!empty($remote_abspath)){
            $remote_abspath = trailingslashit($remote_abspath); 
        }else{
            return array(
                   'error' => 'Unable to locate WP root directory using file system.', 'error_code' => 'unable_to_locate_root_directory_remote_abspath_file_editor_upload'
             );
        }

        if($folderPath == 'root' && ($filePath == 'wp-config' || $filePath == 'wp-config.php' ) ){
            return array(
                    'error' => 'wp-config file is not allowed.', 'error_code' => 'config_file_is_not_allowed_file_editor_upload'
                );
        }
        
        $file = $this->get_file_editor_vars($remote_abspath,$folderPath,$filePath,$ext);
        if($file === false){
            return array('error' => 'File path given is invalid.', 'error_code' => 'file_path_given_is_invalid_file_editor_upload');
        }

        $new_temp_subfolders = $new_temp_folder;
        for($i=0;$i<count($file['toCreate']);$i++){
            $new_temp_subfolders .= trailingslashit($file['toCreate'][$i]);
            $is_subdir_created = mkdir($new_temp_subfolders);// new folder should be empty
            if(!$is_subdir_created){
                return array(
                       'error' => 'Unable to create a temporary directory.', 'error_code' => 'unable_to_locate_wp_root_directory_using_file_system_file_editor_upload'
                );
            }
        }

        $fileHandler = fopen($new_temp_subfolders.$file['name'],w);
        fwrite($fileHandler,$fileContent);
        fclose($fileHandler);

        $copy_result = $this->iwp_mmb_direct_to_any_copy_dir($new_temp_folder, $file['toPath']);
        
        if ( is_wp_error($copy_result) ){
            $wp_temp_direct2 = new WP_Filesystem_Direct('');
            $wp_temp_direct2->delete($new_temp_folder, true);

            if(is_wp_error($copy_result)){
                $result['error_code'] = $copy_result->get_error_code();
                $result['error'] = $copy_result->get_error_message();
                return $result;
            }
            return $copy_result;
        }

        



    	return 'success';
    }

}