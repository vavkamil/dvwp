<?php
/************************************************************
 * This plugin was modified by Revmakx                      *
 * Copyright (c) 2012 Revmakx                               *
 * www.revmakx.com                                          *
 *                                                          *
 ************************************************************/
/*************************************************************
 * 
 * iwp-file-iterator.php
 * 
 * Manage Backups
 * 
 * 
 **************************************************************/
class IWP_Seek_Iterator{

	public $iterator_common;
	public $external_obj;
	public $iterator_loop_limit;
	public $path;
	public $type;
	public $query;
	public $processed_files;
	public $app_functions;
	public $is_recursive;
	public $exclude_class_obj;

	public function __construct($type = false, $iterator_loop_limit = 1000){
	    $this->type = $type;
	    $this->iterator_loop_limit = $iterator_loop_limit;
	}

	public function get_seekable_files_obj($path){

	    $temp_path = $path;

	    // IWP_add_abspath($path);

	    $path = is_valid_path($path);

	    if( is_array($path) ) {
	        return $path;
	    }

	    $this->path = $temp_path;

	    return new DirectoryIterator($path);
	}

	public function process_iterator($path, $offset = false, $is_recursive = false){

	    $iterator = $this->get_seekable_files_obj($path);

	    if (empty($iterator)) {
	        return ;
	    }

	    $this->seek = empty($offset) ? array() : explode('-', $offset);

	    $this->counter = 0;
	    $this->is_recursive = $is_recursive;

	    if ($is_recursive) {
	        $folder_list_result = $this->recursive_iterator($iterator, false);
	    } else {
	        $folder_list_result = $this->iterator($iterator);
	    }
	    if(!empty($folder_list_result) && $folder_list_result['break']){
	       return $folder_list_result;
	    }
	}

	public function process_file($iterator, $key){
		$folder_list_result = process_file($iterator, $this->is_recursive, $this->path, $key, $this->counter, $this->iterator_loop_limit);
		if(!empty($folder_list_result) && $folder_list_result['break']){
		   return $folder_list_result;
		}
	}

	private function extra_check_query(){
	    if (!empty($this->query)) {
	        insert_into_current_process($this->query);
	        $this->query = '';
	    }
	}

	public function iterator($iterator){
	    //Moving satelite into position.
	    $this->seek_offset($iterator);

	    while ($iterator->valid()) {

	        $this->counter++;

	        $recursive_path = $iterator->getPathname();

	        //Dont recursive iterator if its a dir or dot
	        if ($iterator->isDot() || !$iterator->isReadable()  || $iterator->isDir()) {

	            //move to next file
	            $iterator->next();

	            continue;
	        }

	        $key = $iterator->key();

	        $folder_list_result = $this->process_file( $iterator, $key );
	        if(!empty($folder_list_result) && $folder_list_result['break']){
	           return $folder_list_result;
	        }

	        //move to next file
	        $iterator->next();
	    }

	    $this->extra_check_query();
	}


	public function recursive_iterator($iterator, $key_recursive) {

	    $this->seek_offset($iterator);

	    while ($iterator->valid()) {

	        //Forming current path from iterator
	        $recursive_path = $iterator->getPathname();
            $recursive_path = wp_normalize_path($recursive_path);

	        //Mapping keys
	        $key = ($key_recursive !== false ) ? $key_recursive . '-' . $iterator->key() : $iterator->key() ;

	        //Do recursive iterator if its a dir
	        if (!$iterator->isDot() && $iterator->isReadable() && $iterator->isDir() ) {

	            if (!skip_file($recursive_path)) {//exclude
	                //create new object for new dir
	                $sub_iterator = new DirectoryIterator($recursive_path);

	                $folder_list_result = $this->recursive_iterator($sub_iterator, $key);
                    if(!empty($folder_list_result) && $folder_list_result['break']){
                       return $folder_list_result;
                    }

	            } else{
	            }

	        }

	        //Ignore dots paths
	        if(!$iterator->isDot()){
	           $folder_list_result = $this->process_file( $iterator, $key );
	            if(!empty($folder_list_result) && $folder_list_result['break']){
	               return $folder_list_result;
	            }
	        }

	        //move to next file
	        $iterator->next();
	    }

	    $this->extra_check_query();
	}

	private function seek_offset(&$iterator){

	    if(!count($this->seek)){
	        return false;
	    }

	    //Moving satelite into position.
	    $iterator->seek($this->seek[0]);

	    //remove positions from the array after moved satelite
	    unset($this->seek[0]);

	    //reset array index
	    $this->seek = array_values($this->seek);

	}
}

function scan_entire_site($v_filedescr_list){
	global $wpdb;

    $sql = "TRUNCATE TABLE ".$wpdb->base_prefix."iwp_processed_iterator";
    $response = $wpdb->get_results($sql);
    // $dir = get_root_dir_folders();
    // save_dir_list($dir);
    // $dir = get_wp_content_dir_folders();
    // save_dir_list($dir);
    // $dir = get_uploads_dir_folders();
    // save_dir_list($dir);
    // get_db_backup_file();
    // save_dir_list();
    save_deep_dir_list($v_filedescr_list);
}

function get_root_dir_folders(){
    $files_obj = get_files_obj_by_path(ABSPATH);
    return add_dir_list($files_obj);
}

function add_dir_list($files_obj){
    foreach ($files_obj as $key => $file_obj) {

        $file = $file_obj->getPathname();

        if (!IWP_is_dir($file)) {
            /// $this->files[] = $file;
        } else {
            // IWP_remove_abspath($file);
            $dir[] = $file;
        }
    }
    return $dir;
}

function get_files_obj_by_path($path, $recursive = false){

    // IWP_add_abspath($path);

    $path = is_valid_path($path);

    if( is_array($path) ) {
        return $path;
    }

    if($recursive){
        return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path , RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
    }

    return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path , RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CATCH_GET_CHILD);
}

function save_dir_list($dir){
    $qry = '';
    $deep_dirs = array(
        ABSPATH    
    );
    foreach ($dir as $dir) {
        if (in_array($dir, $deep_dirs)) {
            continue;
        }


        $qry .= empty($qry) ? "('" : ",('" ;
        $qry .= wp_normalize_path($dir) . "', '0')";

    }
    insert_into_iterator_process($qry);
}

function save_deep_dir_list($deep_dirs){
    // $deep_dirs = array(
    //     ABSPATH,
    // );
    $qry = '';
    foreach ($deep_dirs as $dir) {
        $qry .= empty($qry) ? "('" : ",('" ;
        $qry .= wp_normalize_path($dir['filename']) . "', '0')";

    }

    insert_into_iterator_process($qry);
}

function insert_into_iterator_process($qry){
	global $wpdb;

    $sql = "insert into ".$wpdb->base_prefix."iwp_processed_iterator ( name, offset  ) values $qry";
    $response = $wpdb->get_results($sql);
}

function iwp_iterator(){
    $break = false;
    $deep_dirs_array = array( 
     
    );

    while(!$break){
        $dir_meta = get_unfnished_folder();
        $deep_dirs = false;

        if (empty($dir_meta) || $dir_meta['offset'] === -1) {
            $break = true;
            continue;
        }

        if( array_search($dir_meta['name'], $deep_dirs_array) !== false ){
            $deep_dirs = true;
        }

        $file = $dir_meta['name'];

        if ($deep_dirs === false && skip_file($file) === true) {
            update_iterator($dir_meta['name'], -1);
            continue;
        }

        if(IWP_is_dir($file)){
            $folder_list_result = iwp_iterate_dir($dir_meta['name'], $dir_meta['offset'], $deep_dirs);
        } else {
            $folder_list_result =  iwp_iterate_file($dir_meta['name'], $update_status = true);
        }
        if(!empty($folder_list_result) && $folder_list_result['break']){
           return $folder_list_result;
        }
    }
}

function iwp_iterate_dir($live_path, $offset, $deep_dirs){

    $is_recursive = ($deep_dirs) ? false : true;

    try{
      $obj = new IWP_Seek_Iterator();
       $folder_list_result = $obj->process_iterator($live_path, $offset, $is_recursive);
        if(!empty($folder_list_result) && $folder_list_result['break']){
           return $folder_list_result;
        }
    } catch(Exception $e){

        $exception_msg = $e->getMessage();

        update_iterator($live_path, 0);
        return;
        
    }
    update_iterator($live_path, -1);
}

function iwp_iterate_file($live_file, $update_status = false){
    // initFileSystem();
    // IWP_add_abspath($live_file);
    global $old_next_file_index, $next_file_index,$iwp_v_options,$archive;
    $live_file = wp_normalize_path($live_file);
    $folder_list_result = $archive->fileDetailsExpandManual($live_file, $iwp_v_options, $next_file_index);
    if(!empty($folder_list_result) && $folder_list_result['break']){
      return $folder_list_result;
    }
    if ($update_status) {
        update_iterator($live_file, -1);
    }

}

  function process_file($iterator, $is_recursive, $path, $key, &$counter, $iterator_loop_limit){
    $file = $iterator->getPathname();

    if (!$iterator->isReadable()) {
        return ;
    }

    $file = wp_normalize_path($file);

    if (!$is_recursive && IWP_is_dir($file)){
        return;
    }

    if (skip_file($file) === true) {
        return;
    }

    if(IWP_is_dir($file)){
        return;
    }
    $counter++;
    $folder_list_result = iwp_iterate_file($file);
    if(!empty($folder_list_result) && $folder_list_result['break']){
        update_iterator($path, $key);
        $next_file_index = $folder_list_result['loop_count'];
       return $folder_list_result;
    }
    

}

function get_unfnished_folder() {
    global $wpdb;
    $sql = "SELECT * FROM ".$wpdb->base_prefix."iwp_processed_iterator WHERE offset != -1 LIMIT 1";
    $response = $wpdb->get_results($sql, ARRAY_A);
    // IWP_log($response, '--------$response--------');

    return empty($response) ? false : $response[0];
}

function IWP_is_dir($good_path){
    $good_path = wp_normalize_path($good_path);

    if (is_dir($good_path)) {
        return true;
    }

    $ext = pathinfo($good_path, PATHINFO_EXTENSION);

    if (!empty($ext)) {
        return false;
    }

    if (is_file($good_path)) {
        return false;
    }

    return true;
}

function skip_file($file){
    global $iwp_v_options, $archive;
    $exclude_data = $iwp_v_options[IWP_PCLZIP_OPT_IWP_EXCLUDE];
    if(!is_readable($file)){
        return true;
    }
    if( $file == "." && $file == ".." ) {
        return true;
    }
    if ($archive->excludeDirFromScan($file, $exclude_data)) {
        return true;
    }
    
    return false;
}

function update_iterator($table, $offset) {
        upsert(array(
            'name' => $table,
            'offset' => $offset,
        ));
}

function upsert($data) {
  global $wpdb;
  $this_table_name = 'iwp_processed_iterator';     //in case, if we are changing table name.
  $exists = $wpdb->get_row("SELECT id FROM " . $wpdb->base_prefix. $this_table_name . " WHERE name = '". $data['name']."'" );

  if ($exists) {
      $result = $wpdb->update($wpdb->base_prefix . $this_table_name, $data, array( 'name' => $data['name']));
  }else{
      $result = $wpdb->insert($wpdb->base_prefix . $this_table_name, $data);
  }
}

function is_valid_path($path){
    $default = array();

    if (empty($path)) {
        return $default;
    }

    $path = rtrim($path, '/');

    $path = wp_normalize_path($path);

    if (empty($path)) {
        return $default;
    }

    $basename = basename($path);

    if ($basename == '..' || $basename == '.') {
        return $default;
    }

    if (!is_readable($path)) {
        return $default;
    }

    return $path;
}