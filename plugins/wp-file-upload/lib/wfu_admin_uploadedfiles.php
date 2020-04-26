<?php

/**
 * Uploaded Files Page in Dashboard Area of Plugin
 *
 * This file contains functions related to Uploaded Files page of plugin's
 * Dashboard area.
 *
 * @link /lib/wfu_admin_uploadedfiles.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 4.7.0
 */

/**
 * Process Dashboard Requests for Uploaded Files Page
 *
 * This function processes Dashboard requests and shows main Uploaded Files page
 * of the plugin.
 *
 * @since 4.7.0
 */
function wfu_uploadedfiles_menu() {
	$_GET = stripslashes_deep($_GET);
	$tag = (!empty($_GET['tag']) ? $_GET['tag'] : '1');
	$page = max((int)$tag, 1);
	echo wfu_uploadedfiles_manager($page);
}

/**
 * Display the Uploaded Files Page.
 *
 * This function displays the Uploaded Files page of the plugin.
 *
 * @since 4.7.0
 *
 * @param integer $page Optional. The page to display in case contents are
 *        paginated.
 * @param bool $only_table_rows Optional. Return only the HTML code of the table
 *        rows.
 *
 * @return string The HTML output of the plugin's Uploaded Files Dashboard page.
 */
function wfu_uploadedfiles_manager($page = 1, $only_table_rows = false) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$table_name3 = $wpdb->prefix . "wfu_dbxqueue";
	$def_other_cols = array( 'upload_date', 'user', 'properties', 'remarks', 'actions' );

	if ( !current_user_can( 'manage_options' ) ) return;

	$siteurl = site_url();
	$maxrows = (int)WFU_VAR("WFU_UPLOADEDFILES_TABLE_MAXROWS");

	//get log data from database
	//$files_total = $wpdb->get_var('SELECT COUNT(idlog) FROM '.$table_name1.' WHERE action = \'upload\'');
	//$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action = \'upload\' ORDER BY date_from DESC'.( $maxrows > 0 ? ' LIMIT '.$maxrows.' OFFSET '.(($page - 1) * $maxrows) : '' ));
	$files_total = 0;
	$filerecs = array();
	$has_history = false;
	extract(wfu_uploadedfiles_get_filerecs($page));
	
	//get last record already read
	$last_idlog = get_option( "wordpress_file_upload_last_idlog", array( "pre" => 0, "post" => 0, "time" => 0 ) );
	
	//get visible columns and their order
	$cols = array();
	$cols_raw = explode(',', WFU_VAR("WFU_UPLOADEDFILES_COLUMNS"));
	//normalize column list
	foreach ( $cols_raw as $ind => $col ) $cols_raw[$ind] = strtolower(trim($col));
	//check if '#' column is visible
	$id_visible = in_array('#', $cols_raw);
	//'file' column is always visible and follows '#' column
	//create an associative array $cols where keys are the columns and values
	//are either true for visible columns or false for hidden ones
	$visible_cols_count = 0;
	foreach ( $cols_raw as $col )
		if ( ($key = array_search($col, $def_other_cols)) !== false ) {
			unset($def_other_cols[$key]);
			$cols[$col] = true;
			$visible_cols_count ++;
		}
	foreach ( $def_other_cols as $col ) $cols[$col] = false;
		
	//prepare html
	$echo_str = "";
	if ( !$only_table_rows ) {
		//Update last_idlog option so that next time Uploaded Files menu item is
		//pressed files have been read.
		//Option last_idlog requires a minimum interval of some seconds, defined
		//by advanced variable WFU_UPLOADEDFILES_RESET_TIME, before it can be
		//updated. This way, if the admin presses Uploaded Files menu item two
		//times immediately, the same number of unread files will not change.
		//It is noted that last_idlog option uses two values, 'pre' and 'post'.
		//The way they are updated makes sure that the number of unread files
		//gets reset only when Uploaded Files menu item is pressed and not
		//when the admin browses through the pages of the list (when pagination
		//is activated).
		$limit = (int)WFU_VAR("WFU_UPLOADEDFILES_RESET_TIME");
		if ( $limit == -1 || time() > $last_idlog["time"] + $limit ) {
			$last_idlog["pre"] = $last_idlog["post"];
			$last_idlog["post"] = $wpdb->get_var('SELECT MAX(idlog) FROM '.$table_name1);
			$last_idlog["time"] = time();
			update_option( "wordpress_file_upload_last_idlog", $last_idlog );		
		}
		
		$echo_str .= "\n".'<div class="wrap">';
		$echo_str .= "\n\t".'<h2>List of Uploaded Files</h2>';
		$echo_str .= "\n\t".'<div style="position:relative;">';
		$echo_str .= wfu_add_loading_overlay("\n\t\t", "uploadedfiles");
		$echo_str .= "\n\t\t".'<div class="wfu_uploadedfiles_header" style="width: 100%;">';
		if ( $maxrows > 0 ) {
			$pages = ceil($files_total / $maxrows);
			$echo_str .= wfu_add_pagination_header("\n\t\t\t", "uploadedfiles", $page, $pages);
		}
		$echo_str .= "\n\t\t\t".'<input id="wfu_download_file_nonce" type="hidden" value="'.wp_create_nonce('wfu_download_file_invoker').'" />';
		$echo_str .= "\n\t\t".'</div>';
		$echo_str .= "\n\t\t".'<table id="wfu_uploadedfiles_table" class="wfu-uploadedfiles wp-list-table widefat fixed striped">';
		$echo_str .= "\n\t\t\t".'<thead>';
		$echo_str .= "\n\t\t\t\t".'<tr>';
		$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="5%" class="manage-column'.( $id_visible ? '' : ' hidden' ).'">';
		$echo_str .= "\n\t\t\t\t\t\t".'<label>#</label>';
		$echo_str .= "\n\t\t\t\t\t".'</th>';
		$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="30%" class="manage-column column-primary">';
		$echo_str .= "\n\t\t\t\t\t\t".'<label>File</label>';
		$echo_str .= "\n\t\t\t\t\t".'</th>';
		foreach ( $cols as $col => $is_visible ) {
			if ( $col == 'upload_date' ) {
				$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="10%" class="manage-column'.( $is_visible ? '' : ' hidden' ).'">';
				$echo_str .= "\n\t\t\t\t\t\t".'<label>Upload Date</label>';
				$echo_str .= "\n\t\t\t\t\t".'</th>';
			}
			elseif ( $col == 'user' ) {
				$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="10%" class="manage-column'.( $is_visible ? '' : ' hidden' ).'">';
				$echo_str .= "\n\t\t\t\t\t\t".'<label>User</label>';
				$echo_str .= "\n\t\t\t\t\t".'</th>';
			}
			elseif ( $col == 'properties' ) {
				$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="10%" class="manage-column'.( $is_visible ? '' : ' hidden' ).'">';
				$echo_str .= "\n\t\t\t\t\t\t".'<label>Properties</label>';
				$echo_str .= "\n\t\t\t\t\t".'</th>';
			}
			elseif ( $col == 'remarks' ) {
				$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="25%" class="manage-column'.( $is_visible ? '' : ' hidden' ).'">';
				$echo_str .= "\n\t\t\t\t\t\t".'<label>Remarks</label>';
				$echo_str .= "\n\t\t\t\t\t".'</th>';
			}
			elseif ( $col == 'actions' ) {
				$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="10%" class="manage-column'.( $is_visible ? '' : ' hidden' ).'">';
				$echo_str .= "\n\t\t\t\t\t\t".'<label>Actions</label>';
				$echo_str .= "\n\t\t\t\t\t".'</th>';
			}
		}
		$echo_str .= "\n\t\t\t\t".'</tr>';
		$echo_str .= "\n\t\t\t".'</thead>';
		$echo_str .= "\n\t\t\t".'<tbody>';
	}
	//echo the number of unread uploaded files in order to update the
	//notification bubble of the toplevel menu item
	$unread_files_count = wfu_get_new_files_count($last_idlog["pre"]);
	$echo_str .= "\n\t\t\t".'<!-- wfu_uploadedfiles_unread['.$unread_files_count.'] -->';
	
	$i = ($page - 1) * $maxrows;
	$abspath_notrailing_slash = substr(wfu_abspath(), 0, -1);
	$pagecode = wfu_safe_store_browser_params('wfu_uploaded_files&tag='.$page);
	$nopagecode = wfu_safe_store_browser_params('no_referer');
	foreach ( $filerecs as $filerec ) {
		$i ++;
		$initialrec = $filerec;
		//get all newer associated file records
		$historyrecs = array();
		if ( $has_history ) $historyrecs = $filerec->history;
		else $historyrecs = wfu_get_rec_new_history($initialrec->idlog);
		//get the latest record of this upload
		$filerec = $historyrecs[count($historyrecs) - 1];
		$filedata = wfu_get_filedata_from_rec($filerec, false, true, false);
		if ( $filedata == null ) $filedata = array();

		$echo_str .= "\n\t\t\t\t".'<tr class="wfu_row-'.$i.( $initialrec->idlog > $last_idlog["pre"] ? ' wfu_unread' : '' ).'">';
		$file_relpath = ( substr($filerec->filepath, 0, 4) == 'abs:' ? substr($filerec->filepath, 4) : $filerec->filepath );
		$file_abspath = wfu_path_rel2abs($filerec->filepath);
		$displayed_data = array(
			"file"			=> $file_relpath,
			"date"			=> get_date_from_gmt($initialrec->date_from),
			"user"			=> wfu_get_username_by_id($filerec->uploaduserid),
			"properties"	=> '',
			"remarks"		=> '<div class="wfu-remarks-container"></div>',
			"actions"		=> ''
		);
		$properties = wfu_init_uploadedfiles_properties();
		$actions = wfu_init_uploadedfiles_actions();
		$remarks = '';
		//check if file is stored in FTP location
		$file_in_ftp = ( substr($file_abspath, 0, 6) == 'ftp://' || substr($file_abspath, 0, 7) == 'ftps://' || substr($file_abspath, 0, 7) == 'sftp://' );
		//check if file resides inside WP root
		$file_in_root = ( !$file_in_ftp && substr($file_abspath, 0, strlen($abspath_notrailing_slash)) == $abspath_notrailing_slash );
		//check if file exists for non-ftp uploads
		$file_exists = ( $file_in_ftp ? true : file_exists($file_abspath) );
		//check if record is obsolete
		$obsolete = ( $filerec->date_to != "0000-00-00 00:00:00" );
		//check if file is associated with Media item
		$has_media = ( $file_in_root && $file_exists && !$obsolete && isset($filedata["media"]) );
		
		//update properties
		$properties['status']['icon'] = ( $file_exists ? ( $obsolete ? "obsolete" : "ok" ) : "notexists" );
		$properties['userdata']['visible'] = ( count(wfu_get_userdata_from_rec($filerec)) > 0 );
		if ( $has_media ) {
			$properties['media']['visible'] = true;
			$properties['media']['remarks'] = 'File is associated with Media item ID <strong>'.$filedata["media"]["attach_id"].'</strong>';
		}
		$properties['ftp']['visible'] = $file_in_ftp;
		/**
		 * Customize Uploaded File Properties.
		 *
		 * This filter allows scripts to customize the list of properties of an
		 * uploaded file.
		 *
		 * @since 4.8.0
		 *
		 * @param array $properties The list of properties of the file.
		 * @param object $filerec The database record of the uploaded file.
		 * @param integer $i The file's index in the list of uploaded files.
		*/
		$properties = apply_filters("_wfu_uploadefiles_file_properties", $properties, $filerec, $i);

		//update actions
		$details_href_net = $siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_details&file=byID:'.$filerec->idlog;
		if ( $actions['details']['allowed'] ) {
			$actions['details']['visible'] = true;
			$actions['details']['href'] = $details_href_net.'&invoker='.$nopagecode;
		}
		$media_href = null;
		if ( $has_media && $actions['media']['allowed'] ) {
			$actions['media']['visible'] = true;
			$media_href = get_attachment_link( $filedata["media"]["attach_id"] );
			$actions['media']['href'] = $media_href;
		}
		$adminbrowser_href = false;
		if ( $file_in_root && $file_exists && !$obsolete && $actions['adminbrowser']['allowed'] ) {
			$only_path = wfu_basedir($file_relpath);
			$dir_code = wfu_safe_store_filepath($only_path.'{{'.wfu_basename($file_relpath).'}}');
			$adminbrowser_href = $siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$dir_code;
			$actions['adminbrowser']['visible'] = true;
			$actions['adminbrowser']['href'] = $adminbrowser_href;
		}
		$historylog_href = $siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=view_log&invoker='.$initialrec->idlog;
		if ( $actions['historylog']['allowed'] ) {
			$actions['historylog']['visible'] = true;
			$actions['historylog']['href'] = $historylog_href;
		}
		$link_href = ( $file_in_root ? site_url().( substr($file_relpath, 0, 1) == '/' ? '' : '/' ) : '' ).$file_relpath;
		if ( ( $file_in_ftp || $file_in_root ) && $file_exists && !$obsolete && $actions['link']['allowed'] ) {
			$actions['link']['visible'] = true;
			$actions['link']['href'] = $link_href;
		}
		$download_href = false;
		if ( !$file_in_ftp && $file_exists && !$obsolete && $actions['download']['allowed'] ) {
			$file_code = wfu_safe_store_filepath(wfu_path_abs2rel($file_abspath));
			$download_href = 'javascript:wfu_download_file(\''.$file_code.'\', '.$i.');';
			$actions['download']['visible'] = true;
			$actions['download']['href'] = $download_href;
			$actions['download']['newtab'] = false;
		}
		/**
		 * Customize Uploaded File Actions.
		 *
		 * This filter allows scripts to customize the list of actions of an
		 * uploaded file.
		 *
		 * @since 4.8.0
		 *
		 * @param array $actions The list of actions of the file.
		 * @param object $filerec The database record of the uploaded file.
		 * @param integer $i The file's index in the list of uploaded files.
		*/
		$actions = apply_filters("_wfu_uploadefiles_file_actions", $actions, $filerec, $i);

		//update default file link action
		$default_link = $displayed_data["file"];
		if ( WFU_VAR("WFU_UPLOADEDFILES_DEFACTION") == "details" )
			$default_link = '<a href="'.$details_href_net.'&invoker='.$pagecode.'" title="Go to file details">'.$file_relpath.'</a>';
		elseif ( $file_in_root && $file_exists && !$obsolete && WFU_VAR("WFU_UPLOADEDFILES_DEFACTION") == "adminbrowser" ) {
			if ( $adminbrowser_href === false ) {
				$only_path = wfu_basedir($file_relpath);
				$dir_code = wfu_safe_store_filepath($only_path.'{{'.wfu_basename($file_relpath).'}}');
				$adminbrowser_href = $siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=file_browser&dir='.$dir_code;
			}
			$default_link = '<a href="'.$adminbrowser_href.'" title="Open file in File Browser">'.$file_relpath.'</a>';
		}
		elseif ( WFU_VAR("WFU_UPLOADEDFILES_DEFACTION") == "historylog" )
			$default_link = '<a href="'.$historylog_href.'" title="Go to View Log record of file">'.$file_relpath.'</a>';
		elseif ( ( $file_in_ftp || $file_in_root ) && $file_exists && !$obsolete && WFU_VAR("WFU_UPLOADEDFILES_DEFACTION") == "link" )
			$default_link = '<a href="'.$link_href.'" title="Open file link">'.$file_relpath.'</a>';
		elseif ( !$file_in_ftp && $file_exists && !$obsolete && WFU_VAR("WFU_UPLOADEDFILES_DEFACTION") == "download" ) {
			if ( $download_href === false ) {
				$file_code = wfu_safe_store_filepath(wfu_path_abs2rel($file_abspath));
				$download_href = 'javascript:wfu_download_file(\''.$file_code.'\', '.$i.');';
			}
			$default_link = '<a href="'.$download_href.'" title="Download file">'.$file_relpath.'</a>';
		}
		/**
		 * Customize Default File Link.
		 *
		 * This filter allows scripts to customize the default file link action
		 * of an uploaded file.
		 *
		 * @since 4.8.0
		 *
		 * @param string $default_link The default file link action.
		 * @param object $filerec The database record of the uploaded file.
		 * @param integer $i The file's index in the list of uploaded files.
		*/
		$default_link = apply_filters("_wfu_uploadefiles_file_link", $default_link, $filerec, $i);

		$displayed_data["file"] = $default_link;
		$displayed_data["properties"] = wfu_render_uploadedfiles_properties($properties, $i);
		$displayed_data["actions"] = wfu_render_uploadedfiles_actions($actions);
		$echo_str .= "\n\t\t\t\t\t".'<th style="word-wrap: break-word;"'.( $id_visible ? '' : ' class="hidden"' ).'>'.$i.'</th>';
		$echo_str .= "\n\t\t\t\t\t".'<td class="column-primary" data-colname="File">'.$displayed_data["file"];
		$echo_str .= "\n\t\t\t\t\t\t".'<div id="wfu_file_download_container_'.$i.'" style="display: none;"></div>';
		if ( $visible_cols_count > 0 ) $echo_str .= "\n\t\t\t\t\t\t".'<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>';
		$echo_str .= "\n\t\t\t\t\t".'</td>';
		foreach ( $cols as $col => $is_visible )
			if ( $col == 'upload_date' ) $echo_str .= "\n\t\t\t\t\t".'<td data-colname="Upload Date"'.( $is_visible ? '' : ' class="hidden"' ).'>'.$displayed_data["date"].'</td>';
			elseif ( $col == 'user' ) $echo_str .= "\n\t\t\t\t\t".'<td data-colname="User"'.( $is_visible ? '' : ' class="hidden"' ).'>'.$displayed_data["user"].'</td>';
			elseif ( $col == 'properties' ) $echo_str .= "\n\t\t\t\t\t".'<td data-colname="Properties"'.( $is_visible ? '' : ' class="hidden"' ).'>'.$displayed_data["properties"].'</td>';
			elseif ( $col == 'remarks' ) $echo_str .= "\n\t\t\t\t\t".'<td data-colname="Remarks"'.( $is_visible ? '' : ' class="hidden"' ).'>'.$displayed_data["remarks"].'</td>';
			elseif ( $col == 'actions' ) $echo_str .= "\n\t\t\t\t\t".'<td data-colname="Actions"'.( $is_visible ? '' : ' class="hidden"' ).'>'.$displayed_data["actions"].'</td>';
		$echo_str .= "\n\t\t\t\t".'</tr>';
	}
	if ( !$only_table_rows ) {
		$echo_str .= "\n\t\t\t".'</tbody>';
		$echo_str .= "\n\t\t".'</table>';
		$echo_str .= "\n\t".'</div>';
		$handler = 'function() { wfu_attach_uploadedfiles_events(); }';
		$echo_str .= "\n\t".'<script type="text/javascript">if(window.addEventListener) { window.addEventListener("load", '.$handler.', false); } else if(window.attachEvent) { window.attachEvent("onload", '.$handler.'); } else { window["onload"] = '.$handler.'; }</script>';
		$echo_str .= "\n".'</div>';
	}

	/**
	 * Customize Uploaded Files Page Output.
	 *
	 * This filter allows scripts to customize the HTML code of Uploaded Files
	 * Dashboard page.
	 *
	 * @since 4.8.0
	 *
	 * @param string $echo_str The HTML code of Uploaded Files page.
	 * @param integer $page The current shown page of uploaded files list.
	 * @param bool $only_table_rows Return only HTML code of table rows.
	*/
	$echo_str = apply_filters("_wfu_uploadedfiles_output", $echo_str, $page, $only_table_rows);
	return $echo_str;
}

/**
 * Get List of Uploaded Files.
 *
 * This function returns the list of uploaded files to be displayed in Uploaded
 * Files Dashboard page.
 *
 * @since 4.9.1
 *
 * @redeclarable
 *
 * @param integer $page The page number where the uploaded files belong.
 *
 * @return array An array holding the list of uploaded files.
 */
function wfu_uploadedfiles_get_filerecs($page) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$maxrows = (int)WFU_VAR("WFU_UPLOADEDFILES_TABLE_MAXROWS");
	$ret = array(
		"files_total"	=> 0,
		"filerecs"		=> array(),
		"has_history"	=> false
	);

	if ( WFU_VAR("WFU_UPLOADEDFILES_HIDEINVALID") != "true" ) {
		$ret["files_total"] = $wpdb->get_var('SELECT COUNT(idlog) FROM '.$table_name1.' WHERE action = \'upload\'');
		$ret["filerecs"] = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action = \'upload\' ORDER BY date_from DESC'.( $maxrows > 0 ? ' LIMIT '.$maxrows.' OFFSET '.(($page - 1) * $maxrows) : '' ));
	}
	else {
		$filerecs = $wpdb->get_results('SELECT * FROM '.$table_name1.' WHERE action = \'upload\' ORDER BY date_from DESC');
		foreach ( $filerecs as $ind => $filerec ) {
			$initialrec = $filerec;
			//get all newer associated file records
			$historyrecs = wfu_get_rec_new_history($initialrec->idlog);
			//get the latest record of this upload
			$filerec = $historyrecs[count($historyrecs) - 1];
			$file_abspath = wfu_path_rel2abs($filerec->filepath);
			//check if file is stored in FTP location
			$file_in_ftp = ( substr($file_abspath, 0, 6) == 'ftp://' || substr($file_abspath, 0, 7) == 'ftps://' || substr($file_abspath, 0, 7) == 'sftp://' );
			//check if file exists for non-ftp uploads
			$file_exists = ( $file_in_ftp ? true : file_exists($file_abspath) );
			//check if record is obsolete
			$obsolete = ( $filerec->date_to != "0000-00-00 00:00:00" );
			if ( !$file_exists || $obsolete ) unset($filerecs[$ind]);
			else $filerecs[$ind]->history = $historyrecs;
		}
		$ret["files_total"] = count($filerecs);
		if (  $maxrows > 0 ) $filerecs = array_slice($filerecs, ($page - 1) * $maxrows, $maxrows);
		$ret["filerecs"] = $filerecs;
		$ret["has_history"] = true;
	}
	
	return $ret;
}

/**
 * Generate Default List of Properties of an Uploaded File.
 *
 * This function generates the list of default properties of an uploaded file.
 * Each property has an icon, a title (when the mouse hovers over the icon) and
 * remarks (shown in Remarks column when the mouse hovers over the icon).
 *
 * @since 4.7.0
 *
 * @return array An array of properties of an uploaded file.
 */
function wfu_init_uploadedfiles_properties() {
	$props["status"] = array(
		"icon"			=> "obsolete",
		"icon-list"		=> array(
			"ok"			=> "dashicons-yes",
			"notexists"		=> "dashicons-trash",
			"obsolete"		=> "dashicons-warning"
		),
		"title"			=> "",
		"title-list"	=> array(
			"ok"			=> "File is Ok",
			"notexists"		=> "File does not exist",
			"obsolete"		=> "Record is invalid"
		),
		"visible"		=> true,
		"remarks"		=> '',
		"remarks-list"	=> array(
			"ok"			=> "File uploaded successfully to the website",
			"notexists"		=> "File does not exist anymore in the website",
			"obsolete"		=> "Record is not valid anymore"
		),
		"code"		=> wfu_create_random_string(6)
	);
	$props["userdata"] = array(
		"icon"		=> "dashicons-id-alt",
		"title"		=> "File has user data",
		"visible"	=> false,
		"remarks"	=> 'File has user data, accessible in File Details',
		"code"		=> wfu_create_random_string(6)
	);
	$props["media"] = array(
		"icon"		=> "dashicons-admin-media",
		"title"		=> "File is associated with Media item",
		"visible"	=> false,
		"remarks"	=> 'File is associated with Media item',
		"code"		=> wfu_create_random_string(6)
	);
	$props["ftp"] = array(
		"icon"		=> "wfu-dashicons-ftp",
		"title"		=> "File saved in FTP",
		"visible"	=> false,
		"remarks"	=> 'File has been saved in FTP location',
		"code"		=> wfu_create_random_string(6)
	);
	
	return $props;
}

/**
 * Generate Default List of Actions of an Uploaded File.
 *
 * This function generates the list of default actions of an uploaded file. Each
 * action has an icon, a title (when the mouse hovers over the icon) and a link
 * URL (the action itself).
 *
 * @since 4.7.0
 *
 * @return array An array of properties of an uploaded file.
 */
function wfu_init_uploadedfiles_actions() {
	$def_actions["details"] = array(
		"icon"		=> "dashicons-info",
		"title"		=> "View file details",
		"allowed"	=> false,
		"visible"	=> false,
		"href"		=> "",
		"newtab"	=> true
	);
	$def_actions["media"] = array(
		"icon"		=> "wfu-dashicons-media-external",
		"title"		=> "Open associated Media item",
		"allowed"	=> false,
		"visible"	=> false,
		"href"		=> "",
		"newtab"	=> true
	);
	$def_actions["adminbrowser"] = array(
		"icon"		=> "dashicons-portfolio",
		"title"		=> "Locate file in File Browser",
		"allowed"	=> false,
		"visible"	=> false,
		"href"		=> "",
		"newtab"	=> true
	);
	$def_actions["historylog"] = array(
		"icon"		=> "dashicons-backup",
		"title"		=> "Locate file record in View Log",
		"allowed"	=> false,
		"visible"	=> false,
		"href"		=> "",
		"newtab"	=> true
	);
	$def_actions["link"] = array(
		"icon"		=> "dashicons-external",
		"title"		=> "Open file link",
		"allowed"	=> false,
		"visible"	=> false,
		"href"		=> "",
		"newtab"	=> true
	);
	$def_actions["download"] = array(
		"icon"		=> "dashicons-download",
		"title"		=> "Download file",
		"allowed"	=> false,
		"visible"	=> false,
		"href"		=> "",
		"newtab"	=> true
	);
	
	//get visible actions and their order
	$actions = array();
	$actions_raw = explode(',', WFU_VAR("WFU_UPLOADEDFILES_ACTIONS"));
	//normalize action list
	foreach ( $actions_raw as $ind => $action ) $actions_raw[$ind] = strtolower(trim($action));
	//generate associative array of actions adjusting order and 'allowed'
	//property
	foreach ( $actions_raw as $ind => $action )
		if ( isset($def_actions[$action]) ) {
			$actions[$action] = $def_actions[$action];
			$actions[$action]['allowed'] = true;
			unset($def_actions[$action]);
		}
	foreach ( $def_actions as $action => $props ) $actions[$action] = $props;
	
	return $actions;
}

/**
 * Display Properties of an Uploaded File.
 *
 * This function generates the HTML code of the properties of an uploaded file
 * that will be shown in Properties column.
 *
 * @since 4.7.0
 *
 * @redeclarable
 *
 * @param array $props The properties of the uploaded file.
 * @param integer $index The index of the uploaded file.
 *
 * @return string The HTML code of the properties of an uploaded file.
 */
function wfu_render_uploadedfiles_properties($props, $index) {
	$a = func_get_args(); switch(WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out)) { case 'X': break; case 'R': return $out; break; case 'D': die($out); break; }
	$i = 0;
	$echo_str = "";
	foreach ( $props as $key => $prop ) {
		$ii = $i + 1;
		$iconclass = $prop['icon'];
		if ( isset($prop['icon-list']) ) $iconclass = $prop['icon-list'][$prop['icon']];
		$title = $prop['title'];
		if ( isset($prop['title-list']) ) $title = $prop['title-list'][$prop['icon']];
		$remarks = $prop['remarks'];
		if ( isset($prop['remarks-list']) ) $remarks = $prop['remarks-list'][$prop['icon']];
		$echo_str .= '<div id="p_'.$index.'_'.$ii.'" class="wfu-properties dashicons '.$iconclass.( $i == 0 ? '' : ' wfu-dashicons-after' ).( $prop['visible'] ? '' : ' wfu-dashicons-hidden' ).'" title="'.$title.'"><input type="hidden" class="wfu-remarks" value="'.wfu_plugin_encode_string($remarks).'" /></div>';
		$i ++;
	}
	
	return $echo_str;
}

/**
 * Display Actions of an Uploaded File.
 *
 * This function generates the HTML code of the actions of an uploaded file that
 * will be shown in Actions column.
 *
 * @since 4.7.0
 *
 * @redeclarable
 *
 * @param array $actions The actions of the uploaded file.
 *
 * @return string The HTML code of the actions of an uploaded file.
 */
function wfu_render_uploadedfiles_actions($actions) {
	$a = func_get_args(); switch(WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out)) { case 'X': break; case 'R': return $out; break; case 'D': die($out); break; }
	$i = 0;
	$echo_str = "";
	foreach ( $actions as $key => $action ) {
		$iconclass = $action['icon'];
		if ( isset($action['icon-list']) ) $iconclass = $action['icon-list'][$action['icon']];
		$title = $action['title'];
		if ( isset($action['title-list']) ) $title = $action['title-list'][$action['icon']];
		$echo_str .= '<a class="dashicons '.$iconclass.( $i == 0 ? '' : ' wfu-dashicons-after' ).( $action['visible'] ? '' : ' wfu-dashicons-hidden' ).'" href="'.$action['href'].'" target="'.( !isset($action['newtab']) || $action['newtab'] ? '_blank' : '_self' ).'" title="'.$title.'"></a>';
		$i ++;
	}
	
	return $echo_str;
}

/**
 * Display Unread Uploaded File in Admin Bar.
 *
 * This function displays the number of unread uploaded files in Admin Bar.
 *
 * @since 4.8.0
 */
function wfu_admin_toolbar_new_uploads() {
	global $wp_admin_bar;
	
	if ( WFU_VAR("WFU_UPLOADEDFILES_BARMENU") == "true" ) {
		//get the number of new (unread) uploaded files
		$unread_files_count = wfu_get_unread_files_count();
		$text = $unread_files_count;
		if ( $unread_files_count > 99 ) $text = "99+";
		$title = ( $unread_files_count == 0 ? 'No new files uploaded' : ( $unread_files_count == 1 ? '1 new file uploaded' : $unread_files_count.' files uploaded' ) );

		$args = array(
			'id'     => 'wfu_uploads',
			'title'  => '<span class="ab-icon"></span><span class="ab-label">'.$unread_files_count.'</span><span class="screen-reader-text">'.$title.'</span>',
			'href'   => admin_url( 'admin.php?page=wfu_uploaded_files' ),
			'group'  => false,
			'meta'   => array(
				'title'    => $title,
				'class'    => ( $unread_files_count == 0 && WFU_VAR("WFU_UPLOADEDFILES_BARAUTOHIDE") == "true" ? 'hidden' : '' )
			),
		);
		$wp_admin_bar->add_menu( $args );
	}
}

/**
 * Display Files Per Page in Uploaded Files Screen Options.
 *
 * This function displays the number of uploaded files per page to display in
 * the screen options section of Uploaded Files Dashboard page.
 *
 * @since 4.8.0
 */
function wfu_uploadedfiles_screen_options() {
	global $wfu_uploadedfiles_hook_suffix;

	$screen = get_current_screen();
	// get out of here if we are not on uploadedfiles page
	if( !is_object($screen) || $screen->id != $wfu_uploadedfiles_hook_suffix ) return;

	$args = array(
		'label'    => 'Files per page',
		'default'  => WFU_VAR("WFU_UPLOADEDFILES_TABLE_MAXROWS"),
		'option'   => 'wfu_uploadedfiles_per_page'
	);
	add_screen_option( 'per_page', $args );
}

?>
