<?php

/**
 * File Browser Page in Dashboard Area of Plugin
 *
 * This file contains functions related to File Browser page of plugin's
 * Dashboard area.
 *
 * @link /lib/wfu_admin_browser.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 3.7.1
 */

/**
 * Display the File Browser Page.
 *
 * This function displays the File Browser page of the plugin's Dashboard area.
 *
 * @since 2.2.1
 *
 * @param string $basedir_code A code string corresponding to the folder to be
 *        displayed.
 * @param integer $page Optional. The page to display in case folder contents
 *        are paginated.
 * @param bool $only_table_rows Optional. Return only the HTML code of the table
 *        rows.
 *
 * @return string The HTML output of the plugin's File Browser Dashboard page.
 */
function wfu_browse_files($basedir_code, $page = -1, $only_table_rows = false) {
	$siteurl = site_url();
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$user = wp_get_current_user();
	//store session variables for use from the downloader
	
	if ( !current_user_can( 'manage_options' ) ) return;

	//first decode basedir_code
	$basedir = wfu_get_filepath_from_safe($basedir_code);
	//clean session array holding dir and file paths if it is too big
	if ( WFU_USVAR_exists('wfu_filepath_safe_storage') && count(WFU_USVAR('wfu_filepath_safe_storage')) > WFU_VAR("WFU_PHP_ARRAY_MAXLEN") ) WFU_USVAR_store('wfu_filepath_safe_storage', array());
	
	//basedir may also contain information about the sorting of the displayed
	//elements, as well as a filename that needs to be located and get focus on
	//the browser;
	//sorting information is enclosed in double brackets: [[sort_info]]
	//filename information is enclosed in double braces: {{filename}}
	$sort = "";
	$located_file = "";
	$located_file_found = false;
	$filter = "";
	if ( $basedir !== false ) {
		$ret = wfu_extract_sortdata_from_path($basedir);
		$basedir = $ret['path'];
		$sort = $ret['sort'];
		$located_file = $ret['file'];
		$filter = $ret['filter'];
	}
	if ( $sort == "" ) $sort = 'name';
	if ( substr($sort, 0, 1) == '-' ) $order = SORT_DESC;
	else $order = SORT_ASC;
	//if page is not -1, then do not locate a file
	if ( $located_file != "" && $page > -1 ) $located_file = "";
	//adjust page to be larger than zero
	if ( $page < 1 ) $page = 1;

	//adjust basedir to have a standard format
	if ( $basedir !== false ) {
		if ( substr($basedir, -1) != '/' ) $basedir .= '/';
		if ( substr($basedir, 0, 1) == '/' ) $basedir = substr($basedir, 1);
		//calculate the absolute path of basedir knowing that basedir is relative to website root
		$basedir = wfu_path_rel2abs($basedir);
		if ( !file_exists($basedir) ) $basedir = false;
	}
	//set basedit to default value if empty
	if ( $basedir === false ) {
		$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
		$basedir = ( isset($plugin_options['basedir']) ? $plugin_options['basedir'] : "" );
		$temp_params = array( 'uploadpath' => $basedir, 'accessmethod' => 'normal', 'ftpinfo' => '', 'useftpdomain' => 'false' );
		$basedir = wfu_upload_plugin_full_path($temp_params);
	}
	//find relative dir
	$reldir = str_replace(wfu_abspath(), "root/", $basedir);
	//save dir route to an array
	$parts = explode('/', $reldir);
	$route = array();
	$prev = "";
	foreach ( $parts as $part ) {
		$part = trim($part);
		if ( $part != "" ) {
//			if ( $part == 'root' && $prev == "" ) $prev = wfu_abspath();
			if ( $part == 'root' && $prev == "" ) $prev = "";
			else $prev .= $part.'/';
			array_push($route, array( 'item' => $part, 'path' => $prev ));
		}
	}
	//calculate upper directory
	$updir = substr($basedir, 0, -1);
	$delim_pos = strrpos($updir, '/');
	if ( $delim_pos !== false ) $updir = substr($updir, 0, $delim_pos + 1);

	//define referer (with sort data) to point to this url for use by the elements
	$referer = $siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$basedir_code;
	$referer_code = wfu_safe_store_filepath($referer.'[['.$sort.']]');
	//define header parameters that can be later used when defining file actions
	$header_params = array();

	//find contents of current folder taking into account pagination, if it is
	//activated; contents are found following an optimized procedure as follows:
	// 1.  all dirs and files are found and stored in separate arrays
	// 2.  if pagination is activated then it is checked if there are any dirs
	//     in the current page
	// 3.  if dir sorting is name then dirs are sorted
	// 4.  if dir sorting is date then stat is called for all dirs and then they
	//     are sorted
	// 5.  if pagination is activated then dirs array is sliced to keep only the
	//     ones belonging to the current page and then stat is called if it has
	//     not already been called
	// 6.  if there is room in the page for showing files, then files are also
	//     processed
	// 7.  if file sorting is name then files are sorted
	// 8.  if file sorting is date or size then stat is called for all files and
	//     then they are sorted
	// 9.  if file sorting is user then db record is retrieved for all files and
	//     then they are sorted
	// 10. if pagination is activated then files array is sliced to keep only
	//     the ones fitting in the page; then stat is called and/or db record is
	//     retrieved
	//first calculate dirs and files arrays
	$dirlist = array();
	$dirlist_include = true;
	$dirlist_perpage = array();
	$dirstat_ok = false;
	$filelist = array();
	$filestat_ok = false;
	$filerec_ok = false;
	if ( $handle = opendir($basedir) ) {
		$blacklist = array('.', '..');
		while ( false !== ($file = readdir($handle)) )
			if ( !in_array($file, $blacklist) ) {
				$filepath = $basedir.$file;
				if ( is_dir($filepath) ) array_push($dirlist, array( 'name' => $file, 'fullpath' => $filepath ));
				else array_push($filelist, array( 'name' => $file, 'fullpath' => $filepath ));
			}
		closedir($handle);
	}
	$dirlist_count = count($dirlist);
	$filelist_count = count($filelist);
	//get pagination details and determine if any dirs will be shown
	$maxrows = (int)WFU_VAR("WFU_ADMINBROWSER_TABLE_MAXROWS");
	$files_total = $dirlist_count + $filelist_count;
	if ( $maxrows > 0 ) {
		$pages = max(ceil($files_total / $maxrows), 1);
		if ( $page > $pages ) $page = $pages;
		//if first item index passes number of dirs then do not include dirs
		if ( ($page - 1) * $maxrows >= $dirlist_count ) $dirlist_include = false;
		//if a filename has been defined to get focus, then $dirlist_include
		//needs to be true in order to calculate the dirs of every page
		if ( $located_file != "" ) $dirlist_include = true;
	}
	//process dirs if they are included in page
	if ( $dirlist_include ) {
		//adjust sort details
		$dirsort = ( substr($sort, -4) == 'date' ? 'mdate' : substr($sort, -4) );
		$dirorder = $order;
		if ( $dirsort == 'size' ) { $dirsort = 'name'; $dirorder = SORT_ASC; }
		if ( $dirsort == 'user' ) { $dirsort = 'name'; $dirorder = SORT_ASC; }
		switch ( $dirsort ) {
			case "name": $dirsort .= ":s"; break;
			case "mdate": $dirsort .= ":n"; break;
		}
		//if dir sort is mdate or if a file needs to be located then first
		//calculate stat
		if ( substr($dirsort, 0, 5) == 'mdate' || $located_file != "" ) {
			foreach ( $dirlist as &$dir ) {
				$stat = stat($dir['fullpath']);
				$dir['mdate'] = $stat['mtime'];
			}
			unset($dir);
			$dirstat_ok = true;
		}
		//sort dirs
		$dirlist = wfu_array_sort($dirlist, $dirsort, $dirorder);
		//if pagination is activated then slice dirs array to keep only the
		//items belonging in the current page
		if ( $maxrows > 0 ) {
			//before slicing we store the items in $dirlist_perpage array
			$i = $maxrows;
			$ipage = 0;
			foreach ( $dirlist as $dir ) {
				if ( $i >= $maxrows ) {
					$i = 0;
					$ipage ++;
					$dirlist_perpage[$ipage] = array();
				}
				array_push($dirlist_perpage[$ipage], $dir);
				$i ++;
			}
			//now we slice $dirlist
			$dirlist = array_slice($dirlist, ($page - 1) * $maxrows, $maxrows);
		}
		//calculate stat for the remaining dirs array, if it has not already
		//been done
		if ( !$dirstat_ok ) {
			foreach ( $dirlist as &$dir ) {
				$stat = stat($dir['fullpath']);
				$dir['mdate'] = $stat['mtime'];
			}
			unset($dir);
		}
	}
	else $dirlist = array();
	//determine if any files will be included in page; in case pagination is
	//activated then the remaining places need to be more than zero
	$files_included = ( $maxrows > 0 ? ( $maxrows - count($dirlist) > 0 ) : true );
	//if a filename has been defined to get focus, then $files_included
	//needs to be true in order to re-calculate the page
	if ( $located_file != "" ) $files_included = true;
	if ( $files_included ) {
		//adjust sort details
		$filesort = ( substr($sort, -4) == 'date' ? 'mdate' : substr($sort, -4) );
		switch ( $filesort ) {
			case "name": $filesort .= ":s"; break;
			case "size": $filesort .= ":n"; break;
			case "mdate": $filesort .= ":n"; break;
			case "user": $filesort .= ":s"; break;
		}
		//if file sort is size or mdate then first calculate stat
		if ( substr($filesort, 0, 4) == 'size' || substr($filesort, 0, 5) == 'mdate' ) {
			foreach ( $filelist as &$file ) {
				$stat = stat($file['fullpath']);
				$file['size'] = $stat['size'];
				$file['mdate'] = $stat['mtime'];
			}
			unset($file);
			$filestat_ok = true;
		}
		//if file sort is user then first calculate db records
		elseif ( substr($filesort, 0, 4) == 'user' ) {
			foreach ( $filelist as &$file ) {
				//find relative file record in database together with user data;
				//if the file is php, then file record is null meaning that the file
				//can only be viewed; if file record is not found then the file can
				//again only be viewed
				if ( preg_match("/\.php$/", $file['fullpath']) ) $filerec = null;
				else $filerec = wfu_get_file_rec($file['fullpath'], true);
				//find user who uploaded the file
				$username = ( $filerec != null ? wfu_get_username_by_id($filerec->uploaduserid) : '' );
				$file['user'] = $username;
				$file['filedata'] = $filerec;
			}
			unset($file);
			$filerec_ok = true;
		}
		//sort files
		$filelist = wfu_array_sort($filelist, $filesort, $order);
		//if pagination is activated and a file needs to receive focus, then we
		//need to calculate the page where the file is shown
		if ( $maxrows > 0 && $located_file != "" ) {
			$i = $dirlist_count;
			foreach ( $filelist as $key => $file ) {
				if ( $file['name'] == $located_file ) {
					$located_file_found = true;
					$filelist[$key]['highlighted'] = 1;
					break;
				}
				$i ++;
			}
			if ( $located_file_found ) {
				$page = floor( $i / $maxrows ) + 1;
				if ( isset($dirlist_perpage[$page]) ) $dirlist = $dirlist_perpage[$page];
				else $dirlist = array();
			}
		}
		//if pagination is activated then slice files array to keep only the items
		//belonging in the current page
		if ( $maxrows > 0 )
			$filelist = array_slice($filelist, max(($page - 1) * $maxrows - $dirlist_count, 0), $maxrows - count($dirlist));
		if ( !$filestat_ok || !$filerec_ok ) {
			foreach ( $filelist as &$file ) {
				if ( !$filestat_ok ) {
					$stat = stat($file['fullpath']);
					$file['size'] = $stat['size'];
					$file['mdate'] = $stat['mtime'];
				}
				if ( !$filerec_ok ) {
					if ( preg_match("/\.php$/", $file['fullpath']) ) $filerec = null;
					else $filerec = wfu_get_file_rec($file['fullpath'], true);
					$username = ( $filerec != null ? wfu_get_username_by_id($filerec->uploaduserid) : '' );
					$file['user'] = $username;
					$file['filedata'] = $filerec;
				}
			}
			unset($file);
		}
	}
	else $filelist = array();

	//start html output
	$echo_str = "";
	if ( !$only_table_rows ) {
		$echo_str .= "\n".'<div class="wrap">';
		$echo_str .= "\n\t".'<h2>Wordpress File Upload Control Panel</h2>';
		$echo_str .= "\n\t".'<div style="margin-top:20px;">';
		$echo_str .= wfu_generate_dashboard_menu("\n\t\t", "File Browser");
		$echo_str .= "\n\t".'<div>';
		$echo_str .= "\n\t\t".'<span><strong>Location:</strong> </span>';
		foreach ( $route as $item ) {
			// store dir path that we need to pass to other functions in session, instead of exposing it in the url
			$dir_code = wfu_safe_store_filepath($item['path']);
			$echo_str .= '<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$dir_code.'">'.$item['item'].'</a>';
			$echo_str .= '<span>/</span>';
		}
		//file browser header
		$echo_str .= "\n\t".'</div>';
		//	$dir_code = wfu_safe_store_filepath(wfu_path_abs2rel($basedir).'[['.$sort.']]');
		//	$echo_str .= "\n\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=create_dir&dir='.$dir_code.'" class="button" title="create folder" style="margin-top:6px">Create folder</a>';
		$echo_str .= "\n\t".'<div style="margin-top:10px; position:relative;">';
		$echo_str .= wfu_add_loading_overlay("\n\t\t", "adminbrowser");
		$adminbrowser_nonce = wp_create_nonce( 'wfu-adminbrowser-page' );
		$echo_str .= "\n\t\t".'<div class="wfu_adminbrowser_header" style="width: 100%;">';
		$bulkactions = array(
			array( "name" => "move", "title" => "Move" ),
			array( "name" => "delete", "title" => "Delete" ),
			array( "name" => "include", "title" => "Include" )
		);
		$echo_str .= wfu_add_bulkactions_header("\n\t\t\t", "adminbrowser", $bulkactions);
		if ( $maxrows > 0 ) {
			$echo_str .= wfu_add_pagination_header("\n\t\t\t", "adminbrowser", $page, $pages, $adminbrowser_nonce);
		}
		$echo_str .= "\n\t\t\t".'<input id="wfu_adminbrowser_action_url" type="hidden" value="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" />';
		$echo_str .= "\n\t\t\t".'<input id="wfu_adminbrowser_code" type="hidden" value="'.$basedir_code.'" />';
		$echo_str .= "\n\t\t\t".'<input id="wfu_adminbrowser_referer" type="hidden" value="'.$referer_code.'" />';
		$echo_str .= "\n\t\t\t".'<input id="wfu_download_file_nonce" type="hidden" value="'.wp_create_nonce('wfu_download_file_invoker').'" />';
		$echo_str .= "\n\t\t\t".'<input id="wfu_include_file_nonce" type="hidden" value="'.wp_create_nonce('wfu_include_file').'" />';
		$echo_str .= "\n\t\t".'</div>';
		$echo_str .= "\n\t\t".'<table id="wfu_adminbrowser_table" class="wfu-adminbrowser wp-list-table widefat fixed striped">';
		$echo_str .= "\n\t\t\t".'<thead>';
		$echo_str .= "\n\t\t\t\t".'<tr>';
		$echo_str .= "\n\t\t\t\t\t".'<td scope="col" width="5%" class="manage-column check-column">';
		$echo_str .= "\n\t\t\t\t\t\t".'<input id="wfu_select_all_visible" type="checkbox" onchange="wfu_adminbrowser_select_all_visible_changed();" style="-webkit-appearance:checkbox;" />';
		$echo_str .= "\n\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="25%" class="manage-column column-primary">';
		$dir_code = wfu_safe_store_filepath(wfu_path_abs2rel($basedir).'[['.( substr($sort, -4) == 'name' ? ( $order == SORT_ASC ? '-name' : 'name' ) : 'name' ).']]');
		$echo_str .= "\n\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$dir_code.'">Name'.( substr($sort, -4) == 'name' ? ( $order == SORT_ASC ? ' &uarr;' : ' &darr;' ) : '' ).'</a>';
		$echo_str .= "\n\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="10%" class="manage-column">';
		$dir_code = wfu_safe_store_filepath(wfu_path_abs2rel($basedir).'[['.( substr($sort, -4) == 'size' ? ( $order == SORT_ASC ? '-size' : 'size' ) : 'size' ).']]');
		$echo_str .= "\n\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$dir_code.'">Size'.( substr($sort, -4) == 'size' ? ( $order == SORT_ASC ? ' &uarr;' : ' &darr;' ) : '' ).'</a>';
		$echo_str .= "\n\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="20%" class="manage-column">';
		$dir_code = wfu_safe_store_filepath(wfu_path_abs2rel($basedir).'[['.( substr($sort, -4) == 'date' ? ( $order == SORT_ASC ? '-date' : 'date' ) : 'date' ).']]');
		$echo_str .= "\n\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$dir_code.'">Date'.( substr($sort, -4) == 'date' ? ( $order == SORT_ASC ? ' &uarr;' : ' &darr;' ) : '' ).'</a>';
		$echo_str .= "\n\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="10%" class="manage-column">';
		$dir_code = wfu_safe_store_filepath(wfu_path_abs2rel($basedir).'[['.( substr($sort, -4) == 'user' ? ( $order == SORT_ASC ? '-user' : 'user' ) : 'user' ).']]');
		$echo_str .= "\n\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$dir_code.'">Uploaded By'.( substr($sort, -4) == 'user' ? ( $order == SORT_ASC ? ' &uarr;' : ' &darr;' ) : '' ).'</a>';
		$echo_str .= "\n\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="30%" class="manage-column">';
		$echo_str .= "\n\t\t\t\t\t\t".'<label>User Data</label>';
		$echo_str .= "\n\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t".'</tr>';
		$echo_str .= "\n\t\t\t".'</thead>';
		$echo_str .= "\n\t\t\t".'<tbody>';
	}
	
	//show subfolders first
	if ( $reldir != "root/" ) {
		$dir_code = wfu_safe_store_filepath(wfu_path_abs2rel($updir));
		$echo_str .= "\n\t\t\t\t".'<tr>';
		$echo_str .= "\n\t\t\t\t\t".'<th class="check-column"><input type="checkbox" disabled="disabled" /></th>';
		$echo_str .= "\n\t\t\t\t\t".'<td class="column-primary" data-colname="Name">';
		$echo_str .= "\n\t\t\t\t\t\t".'<a class="row-title" href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$dir_code.'" title="go up">..</a>';
		$echo_str .= "\n\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Size"> </td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Date"> </td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Uploaded By"> </td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="User Data"> </td>';
		$echo_str .= "\n\t\t\t\t".'</tr>';
	}
	$ii = 1;
	foreach ( $dirlist as $dir ) {
		$dir_code = wfu_safe_store_filepath(wfu_path_abs2rel($dir['fullpath']).'[['.$sort.']]');
		$echo_str .= "\n\t\t\t\t".'<tr onmouseover="var actions=document.getElementsByName(\'wfu_dir_actions\'); for (var i=0; i<actions.length; i++) {actions[i].style.visibility=\'hidden\';} document.getElementById(\'wfu_dir_actions_'.$ii.'\').style.visibility=\'visible\'" onmouseout="var actions=document.getElementsByName(\'wfu_dir_actions\'); for (var i=0; i<actions.length; i++) {actions[i].style.visibility=\'hidden\';}">';
		$echo_str .= "\n\t\t\t\t\t".'<th class="check-column"><input type="checkbox" disabled="disabled" /></th>';
		$echo_str .= "\n\t\t\t\t\t".'<td class="column-primary" data-colname="Name">';
		$echo_str .= "\n\t\t\t\t\t\t".'<a class="row-title" href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$dir_code.'" title="'.$dir['name'].'">'.$dir['name'].'</a>';
		$echo_str .= "\n\t\t\t\t\t\t".'<div id="wfu_dir_actions_'.$ii.'" name="wfu_dir_actions" style="visibility:hidden;">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<span style="visibility:hidden;">';
		$echo_str .= "\n\t\t\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir=">Noaction</a>';
		$echo_str .= "\n\t\t\t\t\t\t\t\t".' | ';
		$echo_str .= "\n\t\t\t\t\t\t\t".'</span>';
//		$echo_str .= "\n\t\t\t\t\t\t\t".'<span>';
//		$echo_str .= "\n\t\t\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=rename_dir&file='.$dir_code.'" title="Rename this folder">Rename</a>';
//		$echo_str .= "\n\t\t\t\t\t\t\t\t".' | ';
//		$echo_str .= "\n\t\t\t\t\t\t\t".'</span>';
//		$echo_str .= "\n\t\t\t\t\t\t\t".'<span>';
//		$echo_str .= "\n\t\t\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=delete_dir&file='.$dir_code.'" title="Delete this folder">Delete</a>';
//		$echo_str .= "\n\t\t\t\t\t\t\t".'</span>';
		$echo_str .= "\n\t\t\t\t\t\t".'</div>';
		$echo_str .= "\n\t\t\t\t\t\t".'<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>';
		$echo_str .= "\n\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Size"> </td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Date">'.get_date_from_gmt(date("Y-m-d H:i:s", $dir['mdate']), "d/m/Y H:i:s").'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Uploaded By"> </td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="User Data"> </td>';
		$echo_str .= "\n\t\t\t\t".'</tr>';
		$ii ++;
	}
	//show contained files
	foreach ( $filelist as $file ) {
		$is_included = ( $file['filedata'] != null );
		$can_be_included = ( $plugin_options['includeotherfiles'] == "1" ) && !wfu_file_extension_blacklisted($file['name']);
		$highlighted = ( isset($file['highlighted']) && $file['highlighted'] == 1 );
		$file_code = '';
		if ( $is_included || $can_be_included ) $file_code = wfu_safe_store_filepath(wfu_path_abs2rel($file['fullpath']).'[['.$sort.']]');
		$echo_str .= "\n\t\t\t\t".'<tr '.( $highlighted ? 'class="wfu-highlighted" ' : '' ).'onmouseover="var actions=document.getElementsByName(\'wfu_file_actions\'); for (var i=0; i<actions.length; i++) {actions[i].style.visibility=\'hidden\';} document.getElementById(\'wfu_file_actions_'.$ii.'\').style.visibility=\'visible\'" onmouseout="var actions=document.getElementsByName(\'wfu_file_actions\'); for (var i=0; i<actions.length; i++) {actions[i].style.visibility=\'hidden\';}">';
		$echo_str .= "\n\t\t\t\t\t".'<th class="check-column">';
		if ( $is_included || $can_be_included ) $echo_str .= "\n\t\t\t\t\t\t".'<input class="wfu_selectors'.( $is_included ? ' wfu_included' : '' ).' wfu_selcode_'.$file_code.'" type="checkbox" onchange="wfu_adminbrowser_selector_changed(this);" />';
		else $echo_str .= "\n\t\t\t\t\t\t".'<input type="checkbox" disabled="disabled" />';
		$echo_str .= "\n\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t".'<td class="column-primary" data-colname="Name">';
		if ( $is_included || $can_be_included )
			$echo_str .= "\n\t\t\t\t\t\t".'<a id="wfu_file_link_'.$ii.'" class="row-title" href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_details&file='.$file_code.'" title="View and edit file details" style="font-weight:normal;'.( $is_included ? '' : ' display:none;' ).'">'.$file['name'].'</a>';
		if ( !$is_included )
			$echo_str .= "\n\t\t\t\t\t\t".'<span id="wfu_file_flat_'.$ii.'">'.$file['name'].'</span>';
		//set additional $file properties for generating file actions
		$file["index"] = $ii;
		$file["code"] = $file_code;
		$file["referer_code"] = $referer_code;
		$file_actions = wfu_adminbrowser_file_actions($file, $header_params);
		$echo_str .= "\n\t\t\t\t\t\t".'<div id="wfu_file_actions_'.$ii.'" name="wfu_file_actions" style="visibility:hidden;">';
		if ( $is_included || $can_be_included ) {
			$echo_str .= "\n\t\t\t\t\t\t\t".'<div id="wfu_file_is_included_actions_'.$ii.'" style="display:'.( $is_included ? 'block' : 'none' ).';">';
			//add file actions for files already included
			$array_keys = array_keys($file_actions["is_included"]);
			$lastkey = array_pop($array_keys);
			foreach ( $file_actions["is_included"] as $key => $action ) {
				$echo_str .= "\n\t\t\t\t\t\t\t\t".'<span>';
				foreach ( $action as $line )
					$echo_str .= "\n\t\t\t\t\t\t\t\t\t".$line;
				if ( $key != $lastkey ) $echo_str .= "\n\t\t\t\t\t\t\t\t\t".' | ';
				$echo_str .= "\n\t\t\t\t\t\t\t\t".'</span>';
			}
			$echo_str .= "\n\t\t\t\t\t\t\t".'</div>';
			$echo_str .= "\n\t\t\t\t\t\t\t".'<div id="wfu_file_can_be_included_actions_'.$ii.'" style="display:'.( $is_included ? 'none' : 'block' ).';">';
			//add file actions for files that can be included
			$array_keys = array_keys($file_actions["can_be_included"]);
			$lastkey = array_pop($array_keys);
			foreach ( $file_actions["can_be_included"] as $key => $action ) {
				$echo_str .= "\n\t\t\t\t\t\t\t\t".'<span>';
				foreach ( $action as $line )
					$echo_str .= "\n\t\t\t\t\t\t\t\t\t".$line;
				if ( $key != $lastkey ) $echo_str .= "\n\t\t\t\t\t\t\t\t\t".' | ';
				$echo_str .= "\n\t\t\t\t\t\t\t\t".'</span>';
			}
			$echo_str .= "\n\t\t\t\t\t\t\t".'</div>';
		}
		else {
			$echo_str .= "\n\t\t\t\t\t\t\t".'<span style="visibility:hidden;">';
			$echo_str .= "\n\t\t\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir=">Noaction</a>';
			$echo_str .= "\n\t\t\t\t\t\t\t\t".' | ';
			$echo_str .= "\n\t\t\t\t\t\t\t".'</span>';
		}
		$echo_str .= "\n\t\t\t\t\t\t".'</div>';
		$echo_str .= "\n\t\t\t\t\t\t".'<div id="wfu_file_download_container_'.$ii.'" style="display: none;"></div>';
		$echo_str .= "\n\t\t\t\t\t\t".'<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>';
		$echo_str .= "\n\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Size">'.$file['size'].'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Date">'.get_date_from_gmt(date("Y-m-d H:i:s", $file['mdate']), "d/m/Y H:i:s").'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Uploaded By">'.$file['user'].'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="User Data">';
		if ( $is_included ) {
			if ( count($file['filedata']->userdata) > 0 ) {
				$echo_str .= "\n\t\t\t\t\t\t".'<select multiple="multiple" style="width:100%; height:40px; background:none; font-size:small;">';
				foreach ( $file['filedata']->userdata as $userdata )
					$echo_str .= "\n\t\t\t\t\t\t\t".'<option>'.$userdata->property.': '.$userdata->propvalue.'</option>';
				$echo_str .= "\n\t\t\t\t\t\t".'</select>';
			}
		}
		$echo_str .= "\n\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t".'</tr>';
		$ii ++;
	}

	if ( !$only_table_rows ) {
		$echo_str .= "\n\t\t\t".'</tbody>';
		$echo_str .= "\n\t\t".'</table>';
		$echo_str .= "\n\t\t".'<iframe id="wfu_download_frame" style="display: none;"></iframe>';
		$echo_str .= "\n\t".'</div>';
		$echo_str .= "\n\t".'</div>';
		$echo_str .= "\n".'</div>';
	}
	if ( $located_file_found ) {
		$handler = 'function() { wfu_focus_table_on_highlighted_file("wfu_adminbrowser_table"); }';
		$echo_str .= "\n\t".'<script type="text/javascript">if(window.addEventListener) { window.addEventListener("load", '.$handler.', false); } else if(window.attachEvent) { window.attachEvent("onload", '.$handler.'); } else { window["onload"] = '.$handler.'; }</script>';
	}

	return $echo_str;
}

/**
 * Add Actions to Displayed Files.
 *
 * This function sets the actions that can be applied on the displayed files.
 * Filters can customize these actions.
 *
 * @since 4.1.0
 *
 * @param array $file An array containing properties of the file.
 * @param array $params An array of custom parameters to pass to file actions
 *        filter.
 *
 * @return array An array of actions that can be executed on the file.
 */
function wfu_adminbrowser_file_actions($file, $params) {
	$siteurl = site_url();
	$actions = array(
		"is_included"		=> array(),
		"can_be_included"	=> array()
	);
	//add file actions if file is already included
	$actions["is_included"] += array(
		array( '<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_details&file='.$file["code"].'" title="View and edit file details">Details</a>' ),
		array( '<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=rename_file&file='.$file["code"].'" title="Rename this file">Rename</a>' ),
		array( '<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=move_file&file='.$file["code"].'" title="Move this file">Move</a>' ),
		array( '<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=delete_file&file='.$file["code"].'&referer='.$file["referer_code"].'" title="Delete this file">Delete</a>' ),
		array( '<a href="javascript:wfu_download_file(\''.$file["code"].'\', '.$file["index"].');" title="Download this file">Download</a>' )
	);
	//add file actions if file can be included
	$actions["can_be_included"] += array(
		array(
			'<a id="wfu_include_file_'.$file["index"].'_a" href="javascript:wfu_include_file(\''.$file["code"].'\', '.$file["index"].');" title="Include file in plugin\'s database">Include File</a>',
			'<img id="wfu_include_file_'.$file["index"].'_img" src="'.WFU_IMAGE_ADMIN_SUBFOLDER_LOADING.'" style="width:12px; display:none;" />',
			'<input id="wfu_include_file_'.$file["index"].'_inpfail" type="hidden" value="File could not be included!" />'
		)
	);

	return $actions;
}

/**
 * Check if User Owns a File.
 *
 * This function checks if a user is the owner of a specific file. It will
 * return true if the user in an administrator.
 *
 * @since 3.8.5
 *
 * @param integer $userid The ID of the user to check.
 * @param object $filerec The database record of the file.
 *
 * @return bool True if the user owns the file, false otherwise.
 */
function wfu_user_owns_file($userid, $filerec) {
	if ( 0 == $userid )
		return false;
	if ( current_user_can('manage_options') ) return true;
	return false;
}

/**
 * Check if Current User Owns a File.
 *
 * This function checks if the current user is the owner of a specific file. It
 * will first check if the file extension is valid.
 *
 * @since 3.0.0
 *
 * @param string $filepath The full path of the file to check.
 *
 * @return bool True if the user owns the file, false otherwise.
 */
function wfu_current_user_owes_file($filepath) {
	//first check if file has a restricted extension; for security reasons some file extensions cannot be owned
	if ( wfu_file_extension_blacklisted($filepath) ) return false;
	//then get file data from database, if exist
	$filerec = wfu_get_file_rec($filepath, false);
	if ( $filerec == null ) return false;

	$user = wp_get_current_user();
	return wfu_user_owns_file($user->ID, $filerec);
}

/**
 * Check if Current User is Allowed to Execute an Action on a File.
 *
 * This function checks if the current user is allowed to execute a specific
 * action on a file.
 *
 * @since 2.4.1
 *
 * @param string $action A file action to check.
 * @param string $filepath The full path of the file to check.
 *
 * @return object|null Returns the current WP_User object if current user is
 *         allowed to execute the action on the file or null otherwise.
 */
function wfu_current_user_allowed_action($action, $filepath) {
	//first get file data from database, if exist
	$filerec = wfu_get_file_rec($filepath, false);

	$user = wp_get_current_user();
	if ( 0 == $user->ID ) return null;
	else $is_admin = current_user_can('manage_options');
	if ( !$is_admin ) {
			return null;
	}
	return $user;
}

/**
 * Check if User is Allowed to Execute an Action on a File.
 *
 * This function checks if a user is allowed to execute a specific action on a
 * file.
 *
 * @since 2.6.0
 *
 * @param string $action A file action to check.
 * @param string $filepath The full path of the file to check.
 * @param integer $userid The ID of the user to check.
 *
 * @return bool|null Returns true if current user is allowed to execute the
 *         action on the file or null otherwise.
 */
function wfu_current_user_allowed_action_remote($action, $filepath, $userid) {
	//first get file data from database, if exist
	$filerec = wfu_get_file_rec($filepath, false);

	if ( 0 == $userid ) return null;
	else $is_admin = user_can($userid, 'manage_options');
	if ( !$is_admin ) {
		return null;
	}
	return true;
}

/**
 * Confirm Renaming of File.
 *
 * This function shows a page to confirm renaming of a file.
 *
 * @since 2.2.1
 *
 * @param string $file_code A code corresponding to the file/dir to be renamed.
 * @param string $type Rename dir or file. Can take the values 'dir' or 'file'.
 * @param string $error An error message to show on top of the page in case an
 *        error occured during renaming.
 *
 * @return string The HTML code of the confirmation page.
 */
function wfu_rename_file_prompt($file_code, $type, $error) {
	if ( $type == 'dir' ) return;
	
	$siteurl = site_url();

	$is_admin = current_user_can( 'manage_options' );
	//check if user is allowed to view file details
	if ( !$is_admin ) {
			return;
	}
	$file_code = wfu_sanitize_code($file_code);
	$dec_file = wfu_get_filepath_from_safe($file_code);
	if ( $dec_file === false ) return;
	
	//first extract sort info from dec_file
	$ret = wfu_extract_sortdata_from_path($dec_file);
	$dec_file = wfu_path_rel2abs($ret['path']);
	if ( $type == 'dir' && substr($dec_file, -1) == '/' ) $dec_file = substr($dec_file, 0, -1);

	//check if user is allowed to perform this action
	if ( !wfu_current_user_owes_file($dec_file) ) return;

	$parts = pathinfo($dec_file);
	$newname = $parts['basename'];
	$dir_code = wfu_safe_store_filepath(wfu_path_abs2rel($parts['dirname']).'[['.$ret['sort'].']]');

	$echo_str = "\n".'<div class="wrap">';
	if ( $error ) {
		$rename_file = WFU_USVAR('wfu_rename_file');
		$newname = $rename_file['newname'];
		$echo_str .= "\n\t".'<div class="error">';
		$echo_str .= "\n\t\t".'<p>'.WFU_USVAR('wfu_rename_file_error').'</p>';
		$echo_str .= "\n\t".'</div>';
	}
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	if ( $is_admin ) $echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=file_browser&dir='.$dir_code.'" class="button" title="go back">Go back</a>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h2 style="margin-bottom: 10px;">Rename '.( $type == 'dir' ? 'Folder' : 'File' ).'</h2>';
	if ( $is_admin ) $echo_str .= "\n\t".'<form enctype="multipart/form-data" name="renamefile" id="renamefile" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="validate">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="action" value="rename'.( $type == 'dir' ? 'dir' : 'file' ).'">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="dir" value="'.$dir_code.'">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="file" value="'.$file_code.'">';
	if ( $type == 'dir' ) $echo_str .= "\n\t\t".'<label>Enter new name for folder <strong>'.$dec_file.'</strong></label><br/>';
	elseif ( $is_admin ) $echo_str .= "\n\t\t".'<label>Enter new filename for file <strong>'.$dec_file.'</strong></label><br/>';
	$echo_str .= "\n\t\t".'<input name="wfu_newname" id="wfu_newname" type="text" value="'.$newname.'" style="width:50%;" />';
	$echo_str .= "\n\t\t".'<p class="submit">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Rename">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Cancel">';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t".'</form>';
	$echo_str .= "\n".'</div>';
	return $echo_str;
}

/**
 * Confirm Moving of File.
 *
 * This function shows a page to confirm moving of a file to a new location.
 *
 * @since 4.10.3
 *
 * @param string $file_code A code corresponding to the file to be moved.
 * @param string $error An error message to show on top of the page in case an
 *        error occured during move.
 *
 * @return string The HTML code of the confirmation page.
 */
function wfu_move_file_prompt($file_code, $error) {
	$siteurl = site_url();

	$is_admin = current_user_can( 'manage_options' );
	//check if user is allowed to view file details
	if ( !$is_admin ) return;

	if ( !is_array($file_code) ) $file_code = array( $file_code );
	$names = array();
	foreach ( $file_code as $index => $code ) {
		$file_code[$index] = wfu_sanitize_code($code);
		$dec_file = wfu_get_filepath_from_safe($file_code[$index]);
		if ( $dec_file === false ) unset($file_code[$index]);
		else {
			//first extract sort info from dec_file
			$ret = wfu_extract_sortdata_from_path($dec_file);
			$dec_file = $ret['path'];
			$parts = pathinfo($dec_file);
			array_push($names, $parts['basename']);
		}
	}
	if ( count($file_code) == 0 ) return;
	$file_code_list = "list:".implode(",", $file_code);
	
	$newpath = $parts['dirname'];
	$replacefiles = "";
	$dir_code = wfu_safe_store_filepath($parts['dirname'].'[['.$ret['sort'].']]');

	$echo_str = "\n".'<div class="wrap">';
	if ( $error ) {
		$move_file = WFU_USVAR('wfu_move_file');
		$newpath = $move_file['newpath'];
		$replacefiles = $move_file['replacefiles'];
		$echo_str .= "\n\t".'<div class="error">';
		$echo_str .= "\n\t\t".'<p>'.WFU_USVAR('wfu_move_file_error').'</p>';
		$echo_str .= "\n\t".'</div>';
	}
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=file_browser&dir='.$dir_code.'" class="button" title="go back">Go back</a>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h2 style="margin-bottom: 10px;">Move File</h2>';
	$echo_str .= "\n\t".'<form enctype="multipart/form-data" name="movefile" id="movefile" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="validate">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="action" value="movefile">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="dir" value="'.$dir_code.'">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="file" value="'.$file_code_list.'">';
	if ( count($names) == 1 )
		$echo_str .= "\n\t\t".'<label style="display:inline-block; margin-bottom:1em;">Enter destination folder for file <strong>'.$dec_file.'</strong></label><br/>';
	else {
		$echo_str .= "\n\t\t".'<label>Enter destination folder for files:</label><br/>';
		$echo_str .= "\n\t\t".'<ul style="padding-left: 20px; list-style: initial;">';
		foreach ( $names as $name )
			$echo_str .= "\n\t\t\t".'<li><strong>'.$name.'</strong></li>';
		$echo_str .= "\n\t\t".'</ul>';
	}
	$echo_str .= "\n\t\t".'<input name="wfu_newpath" id="wfu_newpath" type="text" value="'.$newpath.'" style="width:50%;" />';
	$echo_str .= "\n\t\t".'<p>';
	$echo_str .= "\n\t\t\t".'<label>Replace files with the same filename at destination:</label><br />';
	$echo_str .= "\n\t\t\t".'<input name="wfu_replace" id="wfu_replace_yes" type="radio" value="yes"'.( $replacefiles == "yes" ? ' checked="checked"' : '' ).' /><label for="wfu_replace_yes">Yes</label>';
	$echo_str .= "\n\t\t\t".'<input name="wfu_replace" id="wfu_replace_no" type="radio" value="no"'.( $replacefiles == "no" ? ' checked="checked"' : '' ).' style="margin-left:1em;" /><label for="wfu_replace_no">No</label>';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t\t".'<p class="submit">';
	$echo_str .= "\n\t\t\t".'<input type="button" class="button-primary" name="submitBtn" value="Move" onclick="if (!document.getElementById(\'wfu_replace_yes\').checked && !document.getElementById(\'wfu_replace_no\').checked) alert(\'Please select if files in destination with the same filename will be replaced or not!\'); else this.form.submit();" />';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submitBtn" value="Cancel" />';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t".'</form>';
	$echo_str .= "\n".'</div>';
	return $echo_str;
}

/**
 * Execute Renaming of File.
 *
 * This function renames a file.
 *
 * @since 2.2.1
 *
 * @param string $file_code A code corresponding to the file/dir to be renamed.
 * @param string $type Rename dir or file. Can take the values 'dir' or 'file'.
 *
 * @return bool True if renaming of file succeeded, false otherwise.
 */
function wfu_rename_file($file_code, $type) {
	if ( $type == 'dir' ) return;
	
	$user = wp_get_current_user();
	$is_admin = current_user_can( 'manage_options' );
	//check if user is allowed to view file details
	if ( !$is_admin ) {
			return;
	}
	$file_code = wfu_sanitize_code($file_code);
	$dec_file = wfu_get_filepath_from_safe($file_code);
	if ( $dec_file === false ) return;
	
	$dec_file = wfu_path_rel2abs(wfu_flatten_path($dec_file));
	if ( $type == 'dir' && substr($dec_file, -1) == '/' ) $dec_file = substr($dec_file, 0, -1);
	if ( !file_exists($dec_file) ) return;

	//check if user is allowed to perform this action
	if ( !wfu_current_user_owes_file($dec_file) ) return;

	$parts = pathinfo($dec_file);
	$error = "";
	if ( isset($_POST['wfu_newname']) && isset($_POST['submit']) ) {
		if ( $_POST['submit'] == "Rename" && $_POST['wfu_newname'] != $parts['basename'] ) {
			$new_file = $parts['dirname'].'/'.$_POST['wfu_newname'];
			if ( $_POST['wfu_newname'] == "" ) $error = 'Error: New '.( $type == 'dir' ? 'folder ' : 'file' ).'name cannot be empty!';
			elseif ( preg_match("/[^A-Za-z0-9_.#\-$]/", $_POST['wfu_newname']) ) $error = 'Error: name contained invalid characters that were stripped off! Please try again.';
			elseif ( substr($_POST['wfu_newname'], -1 - strlen($parts['extension'])) != '.'.$parts['extension'] ) $error = 'Error: new and old file name extensions must be identical! Please correct.';
			elseif ( wfu_file_extension_blacklisted($_POST['wfu_newname']) ) $error = 'Error: the new file name has an extension that is forbidden for security reasons. Please correct.';
			elseif ( file_exists($new_file) ) $error = 'Error: The '.( $type == 'dir' ? 'folder' : 'file' ).' <strong>'.$_POST['wfu_newname'].'</strong> already exists! Please choose another one.';
			else {
				//pre-log rename action
				if ( $type == 'file' ) $retid = wfu_log_action('rename:'.$new_file, $dec_file, $user->ID, '', 0, 0, '', null);
				//perform rename action
				if ( rename($dec_file, $new_file) == false ) $error = 'Error: Rename of '.( $type == 'dir' ? 'folder' : 'file' ).' <strong>'.$parts['basename'].'</strong> failed!';
				//revert log action if file was not renamed
				if ( $type == 'file' && !file_exists($new_file) ) wfu_revert_log_action($retid);
			}
		}
	}
	if ( $error != "" ) {
		WFU_USVAR_store('wfu_rename_file_error', $error);
		$rename_file = WFU_USVAR('wfu_rename_file');
		$rename_file['newname'] = preg_replace("/[^A-Za-z0-9_.#\-$]/", "", $_POST['wfu_newname']);
		WFU_USVAR_store('wfu_rename_file', $rename_file);
	}
	return ( $error == "" );
}

/**
 * Execute Moving of File.
 *
 * This function moves a file to another location.
 *
 * @since 4.10.3
 *
 * @param string $file_code A code corresponding to the file to be moved.
 *
 * @return bool True if move of file succeeded, false otherwise.
 */
function wfu_move_file($file_code) {
	$user = wp_get_current_user();
	$is_admin = current_user_can( 'manage_options' );
	//check if user is allowed to view file details
	if ( !$is_admin ) return;

	if ( !is_array($file_code) ) $file_code = array( $file_code );
	$dec_files = array();
	foreach ( $file_code as $index => $code ) {
		$file_code[$index] = wfu_sanitize_code($code);
		$dec_file = wfu_get_filepath_from_safe($file_code[$index]);
		if ( $dec_file !== false ) {
			$dec_file = wfu_path_rel2abs(wfu_flatten_path($dec_file));
			array_push($dec_files, $dec_file);
		}
	}
	if ( count($dec_files) == 0 ) return;

	$parts = pathinfo($dec_files[0]);
	$error = "";
	$regex = "/([^A-Za-z0-9\-._~!$&'()*+,;=:@#\/\\\\%]|%[^A-Fa-f0-9][^A-Fa-f0-9]|%[A-Fa-f0-9][^A-Fa-f0-9]|%[^A-Fa-f0-9][A-Fa-f0-9]|%.?$)/";
	if ( isset($_POST['wfu_newpath']) && isset($_POST['wfu_replace']) ) {
		$oldpath = $parts['dirname'];
		if ( substr($oldpath, -1) != '/' ) $oldpath = $oldpath.'/';
		$newpath = preg_replace($regex, "", $_POST['wfu_newpath']);
		if ( substr($newpath, 0, 1) != '/' ) $newpath = '/'.$newpath;
		$newpath = realpath(wfu_path_rel2abs($newpath));
		if ( substr($newpath, -1) != '/' ) $newpath = $newpath.'/';
		$replacefiles = ( $_POST['wfu_replace'] == 'yes' ? 'yes' : ( $_POST['wfu_replace'] == 'no' ? 'no' : '' ) );
		if ( trim($_POST['wfu_newpath']) == "" ) $error = 'Error: Destination path cannot be empty!';
		elseif ( $newpath == $oldpath ) $error = 'Error: Destination path is the same as source path!';
		elseif ( preg_match($regex, $_POST['wfu_newpath']) ) $error = 'Error: path contained invalid characters that were stripped off! Please try again.';
		elseif ( !file_exists($newpath) ) $error = 'Error: Destination folder <strong>'.$_POST['wfu_newpath'].'</strong> does not exist!';
		elseif ( $replacefiles == "" ) $error = 'Error: Invalid selection about replacing files with same filename at destination!';
		else {
			foreach ( $dec_files as $dec_file ) {
				if ( file_exists($dec_file) ) {
					$new_file = $newpath.wfu_basename($dec_file);
					if ( !file_exists($new_file) || $replacefiles == "yes" ) {
						//pre-log move action
						$retid = wfu_log_action('move:'.$new_file, $dec_file, $user->ID, '', 0, 0, '', null);
						//perform move action
						if ( @rename($dec_file, $new_file) === false || !file_exists($new_file) ) {
							wfu_revert_log_action($retid);
						}
					}
				}
			}
		}
	}
	if ( $error != "" ) {
		WFU_USVAR_store('wfu_move_file_error', $error);
		$move_file = WFU_USVAR('wfu_move_file');
		$move_file['newpath'] = preg_replace($regex, "", $_POST['wfu_newpath']);
		$move_file['replacefiles'] = $replacefiles;
		WFU_USVAR_store('wfu_move_file', $move_file);
	}
	return ( $error == "" );
}

/**
 * Confirm Deletion of File.
 *
 * This function shows a page to confirm deletion of a file.
 *
 * @since 2.2.1
 *
 * @param string $file_code A code corresponding to the file/dir to be deleted.
 * @param string $type Delete dir or file. Can take the values 'dir' or 'file'.
 * @param string $referer The page that initiated the deletion of the file.
 *
 * @return string The HTML code of the confirmation page.
 */
function wfu_delete_file_prompt($file_code, $type, $referer) {
	if ( $type == 'dir' ) return;
	
	$siteurl = site_url();

	$is_admin = current_user_can( 'manage_options' );
	//check if user is allowed to view file details
	if ( !$is_admin ) {
			return;
	}
	if ( !is_array($file_code) ) $file_code = array( $file_code );
	$names = array();
	foreach ( $file_code as $index => $code ) {
		$file_code[$index] = wfu_sanitize_code($code);
		$dec_file = wfu_get_filepath_from_safe($file_code[$index]);
		if ( $dec_file === false ) unset($file_code[$index]);
		else {
			//first extract sort info from dec_file
			$ret = wfu_extract_sortdata_from_path($dec_file);
			$dec_file = wfu_path_rel2abs($ret['path']);
			if ( $type == 'dir' && substr($dec_file, -1) == '/' ) $dec_file = substr($dec_file, 0, -1);
			//check if user is allowed to perform this action
			if ( !wfu_current_user_owes_file($dec_file) ) unset($file_code[$index]);
			else {
				$parts = pathinfo($dec_file);
				array_push($names, $parts['basename']);
			}
		}
	}
	if ( count($file_code) == 0 ) return;
	$file_code_list = "list:".implode(",", $file_code);

	$referer_url = wfu_get_filepath_from_safe(wfu_sanitize_code($referer));
	$ret = wfu_extract_sortdata_from_path($referer_url);
	$referer_url = $ret['path'];

	$echo_str = "\n".'<div class="wrap">';
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	if ( $is_admin ) $echo_str .= "\n\t\t".'<a href="'.$referer_url.'" class="button" title="go back">Go back</a>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h2 style="margin-bottom: 10px;">Delete '.( $type == 'dir' ? 'Folder' : 'File'.( count($names) == 1 ? '' : 's' ) ).'</h2>';
	if ( $is_admin ) $echo_str .= "\n\t".'<form enctype="multipart/form-data" name="deletefile" id="deletefile" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="validate">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="action" value="delete'.( $type == 'dir' ? 'dir' : 'file' ).'">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="referer" value="'.$referer.'">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="file" value="'.$file_code_list.'">';
	if ( count($names) == 1 )
		$echo_str .= "\n\t\t".'<label>Are you sure that you want to delete '.( $type == 'dir' ? 'folder' : 'file' ).' <strong>'.$names[0].'</strong>?</label><br/>';
	else {
		$echo_str .= "\n\t\t".'<label>Are you sure that you want to delete '.( $type == 'dir' ? 'folder' : 'files' ).':';
		$echo_str .= "\n\t\t".'<ul style="padding-left: 20px; list-style: initial;">';
		foreach ( $names as $name )
			$echo_str .= "\n\t\t\t".'<li><strong>'.$name.'</strong></li>';
		$echo_str .= "\n\t\t".'</ul>';
	}
	$echo_str .= "\n\t\t".'<p class="submit">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Delete">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Cancel">';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t".'</form>';
	$echo_str .= "\n".'</div>';
	return $echo_str;
}

/**
 * Execute Deletion of File.
 *
 * This function deletes a file.
 *
 * @since 2.2.1
 *
 * @param string $file_code A code corresponding to the file/dir to be deleted.
 * @param string $type Delete dir or file. Can take the values 'dir' or 'file'.
 *
 * @return bool True if deletion of file succeeded, false otherwise.
 */
function wfu_delete_file($file_code, $type) {
	if ( $type == 'dir' ) return;
	
	$user = wp_get_current_user();
	$is_admin = current_user_can( 'manage_options' );
	//check if user is allowed to view file details
	if ( !$is_admin ) {
			return;
	}
	if ( !is_array($file_code) ) $file_code = array( $file_code );
	$dec_files = array();
	foreach ( $file_code as $index => $code ) {
		$file_code[$index] = wfu_sanitize_code($code);
		$dec_file = wfu_get_filepath_from_safe($file_code[$index]);
		if ( $dec_file !== false ) {
			$dec_file = wfu_path_rel2abs(wfu_flatten_path($dec_file));
			if ( $type == 'dir' && substr($dec_file, -1) == '/' ) $dec_file = substr($dec_file, 0, -1);
			//check if user is allowed to perform this action
			if ( wfu_current_user_owes_file($dec_file) ) array_push($dec_files, $dec_file);
		}
	}
	if ( count($dec_files) == 0 ) return;

	if ( isset($_POST['submit']) ) {
		if ( $_POST['submit'] == "Delete" ) {
			foreach ( $dec_files as $dec_file ) {
				//pre-log delete action
				if ( $type == 'file' ) wfu_delete_file_execute($dec_file, $user->ID);
				elseif ( $type == 'dir' && $dec_file != "" ) wfu_delTree($dec_file);
			}
		}
	}
	return true;
}

/**
 * Confirm Creation of a Directory.
 *
 * This function shows a page to confirm creation of a directory.
 *
 * @since 2.2.1
 *
 * @param string $dir_code A code corresponding to the dir to be created.
 * @param string $error An error message to show on top of the page in case an
 *        error occured during creation.
 *
 * @return string The HTML code of the confirmation page.
 */
function wfu_create_dir_prompt($dir_code, $error) {
	return;
	
	$siteurl = site_url();

	if ( !current_user_can( 'manage_options' ) ) return;

	$dir_code = wfu_sanitize_code($dir_code);
	$dec_dir = wfu_get_filepath_from_safe($dir_code);
	if ( $dec_dir === false ) return;
	
	//first extract sort info from dec_dir
	$ret = wfu_extract_sortdata_from_path($dec_dir);
	$dec_dir = wfu_path_rel2abs($ret['path']);
	if ( substr($dec_dir, -1) != '/' ) $dec_dir .= '/';
	$newname = '';

	$echo_str = "\n".'<div class="wrap">';
	if ( $error ) {
		$create_dir = WFU_USVAR('wfu_create_dir');
		$newname = $create_dir['newname'];
		$echo_str .= "\n\t".'<div class="error">';
		$echo_str .= "\n\t\t".'<p>'.WFU_USVAR('wfu_create_dir_error').'</p>';
		$echo_str .= "\n\t".'</div>';
	}
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=file_browser&dir='.$dir_code.'" class="button" title="go back">Go back</a>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h2 style="margin-bottom: 10px;">Create Folder</h2>';
	$echo_str .= "\n\t".'<form enctype="multipart/form-data" name="createdir" id="createdir" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="validate">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="action" value="createdir">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="dir" value="'.$dir_code.'">';
	$echo_str .= "\n\t\t".'<label>Enter the name of the new folder inside <strong>'.$dec_dir.'</strong></label><br/>';
	$echo_str .= "\n\t\t".'<input name="wfu_newname" id="wfu_newname" type="text" value="'.$newname.'" style="width:50%;" />';
	$echo_str .= "\n\t\t".'<p class="submit">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Create">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Cancel">';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t".'</form>';
	$echo_str .= "\n".'</div>';
	return $echo_str;
}

/**
 * Execute Creation of Directory.
 *
 * This function creates a new directory.
 *
 * @since 2.2.1
 *
 * @param string $dir_code A code corresponding to the dir to be created.
 *
 * @return bool True if creation of dir succeeded, false otherwise.
 */
function wfu_create_dir($dir_code) {
	return;
	
	if ( !current_user_can( 'manage_options' ) ) return;

	$dir_code = wfu_sanitize_code($dir_code);
	$dec_dir = wfu_get_filepath_from_safe($dir_code);
	if ( $dec_dir === false ) return;

	$dec_dir = wfu_path_rel2abs(wfu_flatten_path($dec_dir));
	if ( substr($dec_dir, -1) != '/' ) $dec_dir .= '/';
	if ( !file_exists($dec_dir) ) return;
	$error = "";
	if ( isset($_POST['wfu_newname'])  && isset($_POST['submit']) ) {
		if ( $_POST['submit'] == "Create" ) {
			$new_dir = $dec_dir.$_POST['wfu_newname'];
			if ( $_POST['wfu_newname'] == "" ) $error = 'Error: New folder name cannot be empty!';
			elseif ( preg_match("/[^A-Za-z0-9_.#\-$]/", $_POST['wfu_newname']) ) $error = 'Error: name contained invalid characters that were stripped off! Please try again.';
			elseif ( file_exists($new_dir) ) $error = 'Error: The folder <strong>'.$_POST['wfu_newname'].'</strong> already exists! Please choose another one.';
			elseif ( mkdir($new_dir) == false ) $error = 'Error: Creation of folder <strong>'.$_POST['wfu_newname'].'</strong> failed!';
		}
	}
	if ( $error != "" ) {
		WFU_USVAR_store('wfu_create_dir_error', $error);
		$create_dir = WFU_USVAR('wfu_create_dir');
		$create_dir['newname'] = preg_replace("/[^A-Za-z0-9_.#\-$]/", "", $_POST['wfu_newname']);
		WFU_USVAR_store('wfu_create_dir', $create_dir);
	}
	return ( $error == "" );
}

/**
 * Confirm Inclusion of File in Plugin's Database.
 *
 * This function shows a page to confirm inclusion of a file in plugin's
 * database.
 *
 * @since 3.8.5
 *
 * @param string $file_code A code corresponding to the file to be included.
 * @param string $type Rename dir or file. Can take the values 'dir' or 'file'.
 * @param string $referer The page that initiated the inclusion of the file.
 *
 * @return string The HTML code of the confirmation page.
 */
function wfu_include_file_prompt($file_code, $referer) {
	if ( !current_user_can( 'manage_options' ) ) return;
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	if ( $plugin_options['includeotherfiles'] != "1" ) return;
	
	$siteurl = site_url();
	if ( !is_array($file_code) ) $file_code = array( $file_code );
	$names = array();
	foreach ( $file_code as $index => $code ) {
		$file_code[$index] = wfu_sanitize_code($code);
		$dec_file = wfu_get_filepath_from_safe($file_code[$index]);
		if ( $dec_file === false ) unset($file_code[$index]);
		else {
			$dec_file = wfu_path_rel2abs(wfu_flatten_path($dec_file));
			//do not include file if it has a forbidden extention or it is already included
			if ( wfu_file_extension_blacklisted(wfu_basename($dec_file)) || wfu_get_file_rec($dec_file, false) != null )
				unset($file_code[$index]);
			else array_push($names, wfu_basename($dec_file));
		}
	}
	if ( count($file_code) == 0 ) return;
	$file_code_list = "list:".implode(",", $file_code);

	$referer_url = wfu_get_filepath_from_safe(wfu_sanitize_code($referer));
	$ret = wfu_extract_sortdata_from_path($referer_url);
	$referer_url = $ret['path'];

	$echo_str = "\n".'<div class="wrap">';
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<a href="'.$referer_url.'" class="button" title="go back">Go back</a>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h2 style="margin-bottom: 10px;">Include File'.( count($names) == 1 ? '' : 's' ).'</h2>';
	$echo_str .= "\n\t".'<form enctype="multipart/form-data" name="includefile" id="includefile" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="validate">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="action" value="includefile">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="referer" value="'.$referer.'">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="file" value="'.$file_code_list.'">';
	if ( count($names) == 1 )
		$echo_str .= "\n\t\t".'<label>Are you sure that you want to include file <strong>'.$names[0].'</strong>?</label><br/>';
	else {
		$echo_str .= "\n\t\t".'<label>Are you sure that you want to include files:';
		$echo_str .= "\n\t\t".'<ul style="padding-left: 20px; list-style: initial;">';
		foreach ( $names as $name )
			$echo_str .= "\n\t\t\t".'<li><strong>'.$name.'</strong></li>';
		$echo_str .= "\n\t\t".'</ul>';
	}
	$echo_str .= "\n\t\t".'<p class="submit">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Include">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Cancel">';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t".'</form>';
	$echo_str .= "\n".'</div>';
	return $echo_str;
}

/**
 * Execute Inclusion of File in Plugin's Database.
 *
 * This function includes a file in plugin's database.
 *
 * @since 3.8.5
 *
 * @param string $file_code A code corresponding to the file to be included.
 *
 * @return bool True if inclusion of file succeeded, false otherwise.
 */
function wfu_include_file($file_code) {
	if ( !current_user_can( 'manage_options' ) ) return;
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	if ( $plugin_options['includeotherfiles'] != "1" ) return;

	if ( !is_array($file_code) ) $file_code = array( $file_code );
	$dec_files = array();
	foreach ( $file_code as $index => $code ) {
		$file_code[$index] = wfu_sanitize_code($code);
		$dec_file = wfu_get_filepath_from_safe($file_code[$index]);
		if ( $dec_file !== false ) {
			$dec_file = wfu_path_rel2abs(wfu_flatten_path($dec_file));
			//include file if it does not have a forbidden extention and it not already included
			if ( !wfu_file_extension_blacklisted(wfu_basename($dec_file)) && wfu_get_file_rec($dec_file, false) == null )
				array_push($dec_files, $dec_file);
		}
	}
	if ( count($dec_files) == 0 ) return;

	$user = wp_get_current_user();
	if ( isset($_POST['submit']) ) {
		if ( $_POST['submit'] == "Include" ) {
			foreach ( $dec_files as $dec_file )
				$fileid = wfu_log_action('include', $dec_file, $user->ID, '', '', get_current_blog_id(), '', null);
		}
	}
	return true;
}

/**
 * Show File Details Page.
 *
 * This function shows a page displaying details of the uploaded file.
 *
 * @since 2.4.1
 *
 * @param string $file_code A code corresponding to the file to be included.
 * @param string $errorstatus Error status. If it has the value 'error' then an
 *        error will be shown on top of the page.
 * @param string $invoker Optional. The page URL that initiated file details
 *        page.
 *
 * @return string The HTML code of File Details page.
 */
function wfu_file_details($file_code, $errorstatus, $invoker = '') {
	$siteurl = site_url();
	$allow_obsolete = false;
	$file_exists = true;
	$file_belongs = true;
	$admin_can_edit = true;

	//if $file_code starts with 'byID:', then it contains a db record ID and not
	//a file path; in this case we show the properties of the specific record
	//and all linked ones, even if it is obsolete; this is only allowed for
	//admins
	if ( substr($file_code, 0, 5) == "byID:" ) {
		$allow_obsolete = true;
		$file_code = substr($file_code, 5);
	}

	$user = wp_get_current_user();
	$is_admin = current_user_can( 'manage_options' );
	//check if user is allowed to view file details
	if ( !$is_admin ) {
		if ( $allow_obsolete ) return;
			return;
	}
	if ( $allow_obsolete ) {
		$file_code = wfu_sanitize_int($file_code);
		$initialrec = wfu_get_file_rec_from_id($file_code, true);
		if ( $initialrec == null ) return;
		
		//get all associated file records
		$filerecs = wfu_get_rec_new_history($initialrec->idlog);
		//get the latest record of this upload
		$filerec = $filerecs[count($filerecs) - 1];
		$filerec->userdata = $initialrec->userdata;

		$filepath = wfu_path_rel2abs($filerec->filepath);
		//in the case of $allow_obsolete we need to check if the file exists and
		//if it belongs to the current record
		$latestrec = wfu_get_file_rec($filepath, true);
		$file_exists = ( $latestrec != null );
		$file_belongs = ( $file_exists && $latestrec->idlog == $filerec->idlog );
		$admin_can_edit = $file_exists;

		//extract file parts and file properties 
		$parts = pathinfo($filepath);
		if ( $file_exists ) $stat = stat($filepath);
		else $stat['mtime'] = '';
	}
	else {
		$file_code = wfu_sanitize_code($file_code);
		$dec_file = wfu_get_filepath_from_safe($file_code);
		if ( $dec_file === false ) return;

		//extract file browser data from $file variable
		$ret = wfu_extract_sortdata_from_path($dec_file);
		$filepath = wfu_path_rel2abs($ret['path']);
		
		//check if user is allowed to perform this action
		if ( !wfu_current_user_owes_file($filepath) ) return;

		//get file data from database with user data
		$filerec = wfu_get_file_rec($filepath, true);
		if ( $filerec == null ) return;

		//extract sort info and construct contained dir
		$parts = pathinfo($filepath);
		$dir_code = wfu_safe_store_filepath(wfu_path_abs2rel($parts['dirname']).'[['.$ret['sort'].']]');

		$stat = stat($filepath);
	}

	$echo_str = '<div class="regev_wrap">';
	if ( $errorstatus == 'error' ) {
		$echo_str .= "\n\t".'<div class="error">';
		$echo_str .= "\n\t\t".'<p>'.WFU_USVAR('wfu_filedetails_error').'</p>';
		$echo_str .= "\n\t".'</div>';
	}
	//show file detais
	$echo_str .= "\n\t".'<h2>Detais of File: '.$parts['basename'].'</h2>';
	if ( !$file_exists ) {
		$echo_str .= "\n\t\t".'<div class="notice notice-warning">';
		$echo_str .= "\n\t\t\t".'<p>File does not exist on the server anymore!</p>';
		$echo_str .= "\n\t\t".'</div>';
	}
	elseif ( !$file_belongs ) {
		$echo_str .= "\n\t\t".'<div class="notice notice-warning">';
		$echo_str .= "\n\t\t\t".'<p>This record is old. The file is associated with another record.</p>';
		$echo_str .= "\n\t\t".'</div>';
	}
	$echo_str .= "\n\t".'<div style="margin-top:10px;">';
	if ( $is_admin ) {
		$invoker_action = ( $invoker == '' ? false : wfu_get_browser_params_from_safe($invoker) );
		$goback_action = ( $invoker_action === false ? 'file_browser&dir='.$dir_code : $invoker_action );
		if ( substr($goback_action, 0, 18) == "wfu_uploaded_files" )
			$echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/admin.php?page='.$goback_action.'" class="button" title="go back">Go back</a>';
		elseif ( $goback_action != "no_referer" )
			$echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action='.$goback_action.'" class="button" title="go back">Go back</a>';
		$echo_str .= "\n\t\t".'<form enctype="multipart/form-data" name="editfiledetails" id="editfiledetails" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=edit_filedetails" class="validate">';
	}
	$echo_str .= "\n\t\t\t".'<h3 style="margin-bottom: 10px; margin-top: 40px;">Upload Details</h3>';
	$echo_str .= "\n\t\t\t".'<input type="hidden" name="action" value="edit_filedetails" />';
	//$echo_str .= "\n\t\t\t".'<input type="hidden" name="dir" value="'.$dir_code.'">';
	$echo_str .= "\n\t\t\t".'<input type="hidden" name="invoker" value="'.$invoker.'">';
	$echo_str .= "\n\t\t\t".'<input type="hidden" name="file" value="'.( $allow_obsolete ? 'byID:'.$file_code : $file_code ).'">';
	$echo_str .= "\n\t\t\t".'<table class="form-table">';
	$echo_str .= "\n\t\t\t\t".'<tbody>';
	if ( $is_admin ) {
		$echo_str .= "\n\t\t\t\t\t".'<tr>';
		$echo_str .= "\n\t\t\t\t\t\t".'<th scope="row">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<label>Full Path</label>';
		$echo_str .= "\n\t\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t\t".'<td>';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<input type="text" value="'.$filepath.'" readonly="readonly" style="width:50%;" />';
		$echo_str .= "\n\t\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t\t".'</tr>';
		$echo_str .= "\n\t\t\t\t\t".'<tr>';
		$echo_str .= "\n\t\t\t\t\t\t".'<th scope="row">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<label>Uploaded By User</label>';
		$echo_str .= "\n\t\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t\t".'<td>';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<select id="wfu_filedetails_users" disabled="disabled">';
		//get all users
		$args = array();
		/**
		 * Filter Arguments for Getting List of Users.
		 *
		 * This filter allows to customize the arguments passed to get_users()
		 * function to get a list of users. By default the plugin will get a
		 * list of all users. If the website contains too many users this
		 * operation may take time and delay loading of the page. So this filter
		 * can be used to optimize this operation.
		 *
		 * @since 4.11.0
		 *
		 * @param array $args Arguments to retrieve users.
		 * @param string $operation A parameter designating in which operation
		 *        the filter is used.
		*/
		$args = apply_filters("_wfu_get_users", $args, "edit_file_details");
		$users = get_users($args);
		foreach ( $users as $userid => $user )
			$echo_str .= "\n\t\t\t\t\t\t\t\t".'<option value="'.$user->ID.'"'.( $filerec->uploaduserid == $user->ID ? ' selected="selected"' : '' ).'>'.$user->display_name.' ('.$user->user_login.')</option>';
		$echo_str .= "\n\t\t\t\t\t\t\t".'</select>';
		if ( $admin_can_edit ) {
			$echo_str .= "\n\t\t\t\t\t\t\t".'<a class="button" id="btn_change" href="" onclick="document.getElementById(\'wfu_filedetails_users\').disabled = false; this.style.display = \'none\'; document.getElementById(\'btn_ok\').style.display = \'inline-block\'; document.getElementById(\'btn_cancel\').style.display = \'inline-block\'; return false;"'.( $is_admin ? '' : ' style="display:none;"' ).'>Change User</a>';
			$echo_str .= "\n\t\t\t\t\t\t\t".'<a class="button" id="btn_ok" href="" onclick="document.getElementById(\'wfu_filedetails_users\').disabled = true; document.getElementById(\'btn_change\').style.display = \'inline-block\'; this.style.display=\'none\'; document.getElementById(\'btn_cancel\').style.display = \'none\'; document.getElementById(\'wfu_filedetails_userid\').value = document.getElementById(\'wfu_filedetails_users\').value; wfu_filedetails_changed(); return false;" style="display:none;">Ok</a>';
			$echo_str .= "\n\t\t\t\t\t\t\t".'<a class="button" id="btn_cancel" href="" onclick="document.getElementById(\'wfu_filedetails_users\').disabled = true; document.getElementById(\'btn_change\').style.display = \'inline-block\'; this.style.display=\'none\'; document.getElementById(\'btn_ok\').style.display = \'none\'; document.getElementById(\'wfu_filedetails_users\').value = document.getElementById(\'wfu_filedetails_userid\').value; return false;" style="display:none;">Cancel</a>';
			$echo_str .= "\n\t\t\t\t\t\t\t".'<input type="hidden" id="wfu_filedetails_userid" name="wfu_filedetails_userid" value="'.$filerec->uploaduserid.'" />';
			$echo_str .= "\n\t\t\t\t\t\t\t".'<input type="hidden" id="wfu_filedetails_userid_default" value="'.$filerec->uploaduserid.'" />';
		}
		$echo_str .= "\n\t\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t\t".'</tr>';
	}
	$echo_str .= "\n\t\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<label>File Size</label>';
	$echo_str .= "\n\t\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<input type="text" value="'.$filerec->filesize.'" readonly="readonly" style="width:auto;" />';
	$echo_str .= "\n\t\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<label>File Date</label>';
	$echo_str .= "\n\t\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<input type="text" value="'.( $file_exists ? get_date_from_gmt(date("Y-m-d H:i:s", $stat['mtime']), "d/m/Y H:i:s") : '' ).'" readonly="readonly" style="width:auto;" />';
	$echo_str .= "\n\t\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<label>Uploaded From Page</label>';
	$echo_str .= "\n\t\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<input type="text" value="'.get_the_title($filerec->pageid).' ('.$filerec->pageid.')'.'" readonly="readonly" style="width:50%;" />';
	$echo_str .= "\n\t\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t\t".'</tr>';
	if ( $is_admin ) {
		$echo_str .= "\n\t\t\t\t\t".'<tr>';
		$echo_str .= "\n\t\t\t\t\t\t".'<th scope="row">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<label>Upload Plugin ID</label>';
		$echo_str .= "\n\t\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t\t".'<td>';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<input type="text" value="'.$filerec->sid.'" readonly="readonly" style="width:auto;" />';
		$echo_str .= "\n\t\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t\t".'</tr>';
	}
	$echo_str .= "\n\t\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t\t".'</table>';
	if ( $is_admin ) {
		//show history details
		$echo_str .= "\n\t\t\t".'<h3 style="margin-bottom: 10px; margin-top: 40px;">File History</h3>';
		$echo_str .= "\n\t\t\t".'<table class="form-table">';
		$echo_str .= "\n\t\t\t\t".'<tbody>';
		$echo_str .= "\n\t\t\t\t\t".'<tr>';
		$echo_str .= "\n\t\t\t\t\t\t".'<th scope="row">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<label></label>';
		$echo_str .= "\n\t\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t\t".'<td>';
		//read all linked older records
		$filerecs = wfu_get_rec_old_history($filerec->idlog);
		//construct report from db records
		$rep = '';
		foreach ( $filerecs as $rec ) {
			$username = wfu_get_username_by_id($rec->userid);
			$fileparts = pathinfo($rec->filepath);
			if ( $rep != '' ) $rep .= "<br />";
			$rep .= '<strong>['.get_date_from_gmt($rec->date_from).']</strong> ';
			if ( $rec->action == 'upload' )
				$rep .= 'File uploaded at <strong>'.$fileparts['dirname'].'</strong> with name <strong>'.$fileparts['basename'].'</strong> by user <strong>'.$username.'</strong>';
			elseif ( $rec->action == 'include' )
				$rep .= 'File included in database at <strong>'.$fileparts['dirname'].'</strong> with name <strong>'.$fileparts['basename'].'</strong> by user <strong>'.$username.'</strong>';
			elseif ( $rec->action == 'download' )
				$rep .= 'File downloaded by user <strong>'.$username.'</strong>';
			elseif ( $rec->action == 'rename' )
				$rep .= 'File renamed to <strong>'.$fileparts['basename'].'</strong> by user <strong>'.$username.'</strong>';
			elseif ( $rec->action == 'move' )
				$rep .= 'File moved to <strong>'.$fileparts['dirname'].'</strong> by user <strong>'.$username.'</strong>';
			elseif ( $rec->action == 'delete' )
				$rep .= 'File deleted by user <strong>'.$username.'</strong>';
			elseif ( $rec->action == 'modify' )
				$rep .= 'File userdata modified by user <strong>'.$username.'</strong>';
			elseif ( $rec->action == 'changeuser' )
				$rep .= 'File upload user modified by user <strong>'.$username.'</strong>';
		}
		$echo_str .= "\n\t\t\t\t\t\t\t".'<div style="border:1px solid #dfdfdf; border-radius:3px; width:50%; overflow:scroll; padding:6px; height:100px; background-color:#eee;">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<span style="white-space:nowrap;">'.$rep.'</span>';
		$echo_str .= "\n\t\t\t\t\t\t\t".'</div>';
		$echo_str .= "\n\t\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t\t".'</tr>';
		$echo_str .= "\n\t\t\t\t".'</tbody>';
		$echo_str .= "\n\t\t\t".'</table>';
	}

	$echo_str .= "\n\t\t\t".'<h3 style="margin-bottom: 10px; margin-top: 40px;">User Data Details</h3>';
	$echo_str .= "\n\t\t\t".'<table class="form-table">';
	$echo_str .= "\n\t\t\t\t".'<tbody>';
	if ( count($filerec->userdata) > 0 ) {
		foreach ( $filerec->userdata as $userdata ) {
			$echo_str .= "\n\t\t\t\t\t".'<tr>';
			$echo_str .= "\n\t\t\t\t\t\t".'<th scope="row">';
			$echo_str .= "\n\t\t\t\t\t\t\t".'<label>'.$userdata->property.'</label>';
			$echo_str .= "\n\t\t\t\t\t\t".'</th>';
			$echo_str .= "\n\t\t\t\t\t\t".'<td>';
//			$echo_str .= "\n\t\t\t\t\t\t\t".'<input id="wfu_filedetails_userdata_value_'.$userdata->propkey.'" name="wfu_filedetails_userdata" type="text"'.( $is_admin ? '' : ' readonly="readonly"' ).' value="'.$userdata->propvalue.'" />';
			$echo_str .= "\n\t\t\t\t\t\t\t".'<textarea id="wfu_filedetails_userdata_value_'.$userdata->propkey.'" name="wfu_filedetails_userdata" '.( ($is_admin && $admin_can_edit) ? '' : ' readonly="readonly"' ).' value="'.$userdata->propvalue.'">'.$userdata->propvalue.'</textarea>';
			$echo_str .= "\n\t\t\t\t\t\t\t".'<input id="wfu_filedetails_userdata_default_'.$userdata->propkey.'" type="hidden" value="'.$userdata->propvalue.'" />';
			$echo_str .= "\n\t\t\t\t\t\t\t".'<input id="wfu_filedetails_userdata_'.$userdata->propkey.'" name="wfu_filedetails_userdata_'.$userdata->propkey.'" type="hidden" value="'.$userdata->propvalue.'" />';
			$echo_str .= "\n\t\t\t\t\t\t".'</td>';
			$echo_str .= "\n\t\t\t\t\t".'</tr>';
		}
	}
	else {
		$echo_str .= "\n\t\t\t\t\t".'<tr>';
		$echo_str .= "\n\t\t\t\t\t\t".'<th scope="row">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<label>No user data</label>';
		$echo_str .= "\n\t\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t\t".'<td></td>';
		$echo_str .= "\n\t\t\t\t\t".'</tr>';
	}
	$echo_str .= "\n\t\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t\t".'</table>';
	if ( ($is_admin && $admin_can_edit) ) {
		$echo_str .= "\n\t\t\t".'<p class="submit">';
		$echo_str .= "\n\t\t\t\t".'<input id="dp_filedetails_submit_fields" type="submit" class="button-primary" name="submit" value="Update" disabled="disabled" />';
		$echo_str .= "\n\t\t\t".'</p>';
	}
	$echo_str .= "\n\t\t".'</form>';
	$echo_str .= "\n\t".'</div>';
	$handler = 'function() { wfu_Attach_FileDetails_Admin_Events(); }';
	$echo_str .= "\n\t".'<script type="text/javascript">if(window.addEventListener) { window.addEventListener("load", '.$handler.', false); } else if(window.attachEvent) { window.attachEvent("onload", '.$handler.'); } else { window["onload"] = '.$handler.'; }</script>';
	$echo_str .= '</div>';
    
	return $echo_str;
}

/**
 * Change File Details.
 *
 * This function modifies the database record of an uploaded file, as well as 
 * any associated user data field records.
 *
 * @since 2.4.1
 *
 * @param string $file_code A code corresponding to the file to be modified.
 *
 * @return bool True if modification of file succeeded, false otherwise.
 */
function wfu_edit_filedetails($file_code) {
	global $wpdb;
	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$allow_obsolete = false;

	if ( substr($file_code, 0, 5) == "byID:" ) {
		$allow_obsolete = true;
		$file_code = substr($file_code, 5);
	}

	$user = wp_get_current_user();
	$is_admin = current_user_can( 'manage_options' );
	//check if user is allowed to view file details
	if ( !$is_admin ) {
		if ( $allow_obsolete ) return;
			return;
	}
	if ( $allow_obsolete ) {
		$file_code = wfu_sanitize_int($file_code);
		$initialrec = wfu_get_file_rec_from_id($file_code, true);
		if ( $initialrec == null ) return;
		
		//get all associated file records
		$filerecs = wfu_get_rec_new_history($initialrec->idlog);
		//get the latest record of this upload
		$filerec = $filerecs[count($filerecs) - 1];
		$filerec->userdata = $initialrec->userdata;

		$filepath = wfu_path_rel2abs($filerec->filepath);
		$latestrec = wfu_get_file_rec($filepath, true);
		//if $latestrec is null then this means that file does not exist
		if ( $latestrec == null ) return;
		//if the record is obsolete then do not proceed
		if ( $latestrec->idlog != $filerec->idlog ) return;
	}
	else {
		$file_code = wfu_sanitize_code($file_code);
		$dec_file = wfu_get_filepath_from_safe($file_code);
		if ( $dec_file === false ) return;

		$filepath = wfu_path_rel2abs(wfu_flatten_path($dec_file));

		//check if user is allowed to perform this action
		if ( !wfu_current_user_owes_file($filepath) ) return;

		//get file data from database with user data
		$filerec = wfu_get_file_rec($filepath, true);
		if ( $filerec == null ) return;
	}

	if ( isset($_POST['submit']) ) {
		if ( $_POST['submit'] == "Update" ) {
			if ( !is_array($filerec->userdata) ) $filerec->userdata = array();
			//check for errors
			$is_error = false;
			foreach ( $filerec->userdata as $userdata ) {
				if ( !isset($_POST['wfu_filedetails_userdata_'.$userdata->propkey]) ) {
					$is_error = true;
					break;
				}
			}
			if ( !$is_error ) {
				$now_date = date('Y-m-d H:i:s');
				$userdata_count = 0;
				foreach ( $filerec->userdata as $userdata ) {
					$userdata_count ++;
					//make existing userdata record obsolete
					$wpdb->update($table_name2,
						array( 'date_to' => $now_date ),
						array( 'uploadid' => $userdata->uploadid, 'propkey'  => $userdata->propkey ),
						array( '%s' ),
						array( '%s', '%s' )
					);
					//insert new userdata record
					$wpdb->insert($table_name2,
						array(
							'uploadid' 	=> $userdata->uploadid,
							'property' 	=> $userdata->property,
							'propkey' 	=> $userdata->propkey,
							'propvalue' 	=> $_POST['wfu_filedetails_userdata_'.$userdata->propkey],
							'date_from' 	=> $now_date,
							'date_to' 	=> 0
						),
						array(
							'%s',
							'%s',
							'%d',
							'%s',
							'%s',
							'%s'
						)
					);
				}
				if ( $userdata_count > 0 ) wfu_log_action('modify:'.$now_date, $filepath, $user->ID, '', 0, 0, '', null);
			}
			if ( isset($_POST['wfu_filedetails_userid']) && $_POST['wfu_filedetails_userid'] != $filerec->uploaduserid ) {
				wfu_log_action('changeuser:'.$_POST['wfu_filedetails_userid'], $filepath, $user->ID, '', 0, 0, '', null);
			}
		}
	}
	return true;
}

?>
