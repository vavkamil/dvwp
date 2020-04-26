<?php

/**
 * Maintenance Actions Page in Dashboard Area of Plugin
 *
 * This file contains functions related to Maintenance Actions page of plugin's
 * Dashboard area.
 *
 * @link /lib/wfu_admin_maintenance.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 3.7.1
 */

/**
 * Display the Maintenance Actions Page.
 *
 * This function displays the Maintenance Actions page of the plugin's Dashboard
 * area.
 *
 * @since 3.3.1
 *
 * @param string $message Optional. A message to display on top of the page.
 *
 * @return string The HTML output of the plugin's Maintenance Actions Dashboard
 *         page.
 */
function wfu_maintenance_actions($message = '') {
	if ( !current_user_can( 'manage_options' ) ) return wfu_manage_mainmenu();

	$siteurl = site_url();
	
	$echo_str = '<div class="wrap">';
	$echo_str .= "\n\t".'<h2>Wordpress File Upload Control Panel</h2>';
	if ( $message != '' ) {
		$echo_str .= "\n\t".'<div class="updated">';
		$echo_str .= "\n\t\t".'<p>'.$message.'</p>';
		$echo_str .= "\n\t".'</div>';
	}
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= wfu_generate_dashboard_menu("\n\t\t", "Maintenance Actions");
	//maintenance actions
	$echo_str .= "\n\t\t".'<h3 style="margin-bottom: 10px;">Maintenance Actions</h3>';
	$echo_str .= "\n\t\t".'<table class="form-table">';
	$echo_str .= "\n\t\t\t".'<tbody>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$wfu_maintenance_nonce = wp_create_nonce("wfu_maintenance_actions");
	$echo_str .= "\n\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=sync_db&amp;nonce='.$wfu_maintenance_nonce.'" class="button" title="Update database to reflect current status of files">Sync Database</a>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Update database to reflect current status of files.</label>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<a href="" class="button" title="Clean database log" onclick="wfu_cleanlog_selector_toggle(true); return false;">Clean Log</a>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Clean-up database log, either all or of specific period, including file information, user data and optionally the files.</label>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t\t".'<tr class="wfu_cleanlog_tr">';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row"></th>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<div>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<label>Select Clean-Up Period</label>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<select id="wfu_cleanlog_period" onchange="wfu_cleanlog_period_changed();">';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<option value="older_than_date">Clean-up log older than date</option>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<option value="older_than_period">Clean-up log older than period</option>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<option value="between_dates">Clean-up log between dates</option>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<option value="all">Clean-up all log</option>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'</select>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<div class="wfu_selectdate_container">';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<label>Select date</label>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<input id="wfu_cleanlog_dateold" type="text" />';
	$echo_str .= "\n\t\t\t\t\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<div class="wfu_selectperiod_container">';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<label>Select period</label>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<input id="wfu_cleanlog_periodold" type="number" min="1" />';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<select id="wfu_cleanlog_periodtype">';
	$echo_str .= "\n\t\t\t\t\t\t\t\t\t".'<option value="days">days</option>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t\t".'<option value="months">months</option>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t\t".'<option value="years">years</option>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'</select>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<div class="wfu_selectdates_container">';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<label>Select period from</label>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<input id="wfu_cleanlog_datefrom" type="text" />';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<label>back to</label>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<input id="wfu_cleanlog_dateto" type="text" />';
	$echo_str .= "\n\t\t\t\t\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<div class="wfu_includefiles_container">';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<label for="wfu_includefiles">Clean-up also affected files</label>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<input id="wfu_includefiles" type="checkbox" />';
	$echo_str .= "\n\t\t\t\t\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<div class="wfu_buttons_container">';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<a href="" class="button" title="Close" onclick="wfu_cleanlog_selector_toggle(false); return false;">Close</a>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<a href="" class="button wfu_cleanlog_proceed" title="Proceed to log clean-up" onclick="if (wfu_cleanlog_selector_checkproceed()) return true; else return false; ">Proceed</a>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<span class="wfu_cleanlog_error hidden">Error</span>';
	$echo_str .= "\n\t\t\t\t\t\t\t\t".'<input id="wfu_cleanlog_href" type="hidden" value="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=clean_log_ask&amp;nonce='.$wfu_maintenance_nonce.'" />';
	$echo_str .= "\n\t\t\t\t\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=purge_data_ask&amp;nonce='.$wfu_maintenance_nonce.'" class="button" title="Remove all plugin data from website" style="color:red;">Purge All Data</a>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Purge all plugin options and tables from database, as well as any session data. The plugin will be deactivated after this action.</label>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t".'</table>';
	$echo_str .= "\n\t".'</div>';
	//export actions
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<h3 style="margin-bottom: 10px;">Export Actions</h3>';
	$echo_str .= "\n\t\t".'<table class="form-table">';
	$echo_str .= "\n\t\t\t".'<tbody>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<a href="javascript:wfu_download_file(\'exportdata\', 1);" class="button" title="Export uploaded file data">Export Uploaded File Data</a>';
	$echo_str .= "\n\t\t\t\t\t\t".'<input id="wfu_download_file_nonce" type="hidden" value="'.wp_create_nonce('wfu_download_file_invoker').'" />';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Export uploaded valid file data, together with any userdata fields, to a comma-separated text file.</label>';
	$echo_str .= "\n\t\t\t\t\t\t".'<div id="wfu_file_download_container_1" style="display: none;"></div>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t".'</table>';
	$echo_str .= "\n\t".'</div>';
	$handler = 'function() { wfu_cleanlog_initialize_elements(); }';
	$echo_str .= "\n\t".'<script type="text/javascript">if(window.addEventListener) { window.addEventListener("load", '.$handler.', false); } else if(window.attachEvent) { window.attachEvent("onload", '.$handler.'); } else { window["onload"] = '.$handler.'; }</script>';
	$echo_str .= "\n".'</div>';
	
	echo $echo_str;
}

/**
 * Check and Execute Database Synchronization.
 *
 * This function performs security checks whether database synchronization can
 * be executed and then executes this operation.
 *
 * @since 4.6.0
 *
 * @param string $nonce A string that verifies that the request came from
 *        Maintenance Actions page.
 *
 * @return int The number of records affected by synchronization.
 */
function wfu_sync_database_controller($nonce) {
	if ( !current_user_can( 'manage_options' ) ) return -1;
	if ( !wp_verify_nonce($nonce, 'wfu_maintenance_actions') ) return -1;
	
	return wfu_sync_database();
}

/**
 * Prepare Data for Log Cleaning.
 *
 * This function prepares data for executing log cleaning operation.
 *
 * @since 4.6.0
 *
 * @param string $data An encoded string containing information about what
 *        records to clean.
 *
 * @return array An array containing log cleaning data.
 */
function wfu_clean_log_parse_data($data) {
	$ret = array( "result" => true );
	$data = sanitize_text_field($data);
	$data_array = explode(":", $data);
	if ( count($data_array) == 0 ) $ret["result"] = false;
	elseif ( $data_array[0] == "00" || $data_array[0] == "01" ) {
		$ret["code"] = "0";
		$ret["include_files"] = ( substr($data_array[0], 1, 1) == "1" );
		if ( count($data_array) != 2 || strlen($data_array[1]) != 8 ) $ret["result"] = false;
		else {
			$ret["dateold"] = strtotime(substr($data_array[1], 0, 4)."-".substr($data_array[1], 4, 2)."-".substr($data_array[1], 6, 2)." 00:00");
			if ( $ret["dateold"] > time() ) $ret["result"] = false;
		}
	}
	elseif ( $data_array[0] == "10" || $data_array[0] == "11" ) {
		$ret["code"] = "1";
		$ret["include_files"] = ( substr($data_array[0], 1, 1) == "1" );
		if ( count($data_array) != 3 ) $ret["result"] = false;
		else {
			$ret["periodold"] = (int)$data_array[1];
			if ( $ret["periodold"] <= 0 ) $ret["result"] = false;
			elseif ( $data_array[2] == 'd' ) $ret["periodtype"] = 'days';
			elseif ( $data_array[2] == 'm' ) $ret["periodtype"] = 'months';
			elseif ( $data_array[2] == 'y' ) $ret["periodtype"] = 'years';
			else $ret["result"] = false;
		}
	}
	elseif ( $data_array[0] == "20" || $data_array[0] == "21" ) {
		$ret["code"] = "2";
		$ret["include_files"] = ( substr($data_array[0], 1, 1) == "1" );
		if ( count($data_array) != 3 || strlen($data_array[1]) != 8 || strlen($data_array[2]) != 8 ) $ret["result"] = false;
		$ret["datefrom"] = strtotime(substr($data_array[1], 0, 4)."-".substr($data_array[1], 4, 2)."-".substr($data_array[1], 6, 2)." 00:00");
		if ( $ret["datefrom"] > time() ) $ret["result"] = false;
		else {
			$ret["dateto"] = strtotime(substr($data_array[2], 0, 4)."-".substr($data_array[2], 4, 2)."-".substr($data_array[2], 6, 2)." 00:00");
			if ( $ret["dateto"] > $ret["datefrom"] ) $ret["result"] = false;
		}
	}
	elseif ( $data_array[0] == "30" || $data_array[0] == "31" ) {
		$ret["code"] = "3";
		$ret["include_files"] = ( substr($data_array[0], 1, 1) == "1" );
		if ( count($data_array) != 1 ) $ret["result"] = false;
	}
	else $ret["result"] = false;
	
	return $ret;
}

/**
 * Prepare Query for Log Cleaning.
 *
 * This function prepares the SQL WHERE clause of the query for log cleaning.
 *
 * @since 4.9.1
 *
 * @param array $data An array containing log cleaning data.
 *
 * @return string An SQL WHERE clause that defines which database records will
 *         be affected by log cleaning operation.
 */
function wfu_clean_log_where_query($data) {
	$query = "";
	if ( $data["code"] == "0" ) $query = " WHERE date_from < '".date('Y-m-d H:i:s', $data["dateold"])."'";
	elseif ( $data["code"] == "1" ) {
		$date = strtotime(date('Y-m-d', strtotime('-'.$data["periodold"].' '.$data["periodtype"]))." 00:00");
		$query = " WHERE date_from < '".date('Y-m-d H:i:s', $date)."'";
	}
	elseif ( $data["code"] == "2" ) $query = " WHERE date_from < '".date('Y-m-d H:i:s', $data["datefrom"] + 86400)."' AND date_from >= '".date('Y-m-d H:i:s', $data["dateto"])."'";
	
	return $query;
}

/**
 * Confirm Log Cleaning Operation.
 *
 * This function shows a page to confirm log cleaning operation.
 *
 * @since 3.3.1
 *
 * @param string $nonce A string that verifies that the request came from
 *        Maintenance Actions page.
 * @param string $data_enc An encoded string containing information about what
 *        records to clean.
 *
 * @return string The HTML code of the confirmation page.
 */
function wfu_clean_log_prompt($nonce, $data_enc) {
	global $wpdb;
	$table_name1 = $wpdb->prefix . "wfu_log";
	$siteurl = site_url();

	if ( !current_user_can( 'manage_options' ) || !wp_verify_nonce($nonce, 'wfu_maintenance_actions') ) return wfu_maintenance_actions();
	//parse data
	$data = wfu_clean_log_parse_data($data_enc);
	if ( $data["result"] == false ) return wfu_maintenance_actions();

	$echo_str = "\n".'<div class="wrap">';
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=maintenance_actions" class="button" title="go back">Go back</a>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h2 style="margin-bottom: 10px;">Clean Database Log</h2>';
	$echo_str .= "\n\t".'<form enctype="multipart/form-data" name="clean_log" id="clean_log" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="validate">';
	$nonce = wp_nonce_field('wfu_clean_log', '_wpnonce', false, false);
	$nonce_ref = wp_referer_field(false);
	$echo_str .= "\n\t\t".$nonce;
	$echo_str .= "\n\t\t".$nonce_ref;
	$echo_str .= "\n\t\t".'<input type="hidden" name="action" value="clean_log">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="data" value="'.$data_enc.'">';
	if ( $data["include_files"] ) {
		if ( $data["code"] == "0" )
			$echo_str .= "\n\t\t".'<label>This will erase all files uploaded <strong>before '.date("Y-m-d", $data["dateold"]).'</strong> together with associated records kept by the plugin in the database (like file metadata and userdata).</label><br/>';
		elseif ( $data["code"] == "1" )
			$echo_str .= "\n\t\t".'<label>This will erase all files uploaded <strong>'.$data["periodold"].' '.$data["periodtype"].' ago or older</strong> together with associated records kept by the plugin in the database (like file metadata and userdata).</label><br/>';
		elseif ( $data["code"] == "2" )
			$echo_str .= "\n\t\t".'<label>This will erase all files uploaded <strong>between '.date("Y-m-d", $data["datefrom"]).' and '.date("Y-m-d", $data["dateto"]).'</strong> together with associated records kept by the plugin in the database (like file metadata and userdata).</label><br/>';
		else
			$echo_str .= "\n\t\t".'<label>This will erase <strong>ALL</strong> files and associated records kept by the plugin in the database (like file metadata and userdata).</label><br/>';
		$affected_recs = $wpdb->get_results("SELECT * FROM $table_name1".wfu_clean_log_where_query($data));
		$affected_files = wfu_get_valid_affected_files($affected_recs);
		$echo_str .= "\n\t\t".'<br/><div class="wfu_cleanlog_files">';
		$echo_str .= "\n\t\t\t".'<div>';
		$echo_str .= "\n\t\t\t\t".'<label style="vertical-align: middle;"><strong>'.count($affected_files).'</strong> files will be deleted</label>';
		$echo_str .= "\n\t\t\t\t".'<button id="wfu_cleanlog_prompt_button" onclick="document.querySelector(\'.wfu_cleanlog_files\').classList.toggle(\'visible\');return false;" style="vertical-align: middle;"></button>';
		$echo_str .= "\n\t\t\t".'</div>';
		$echo_str .= "\n\t\t\t".'<div id="wfu_cleanlog_prompt_list" style="margin-top:10px;">';
		$echo_str .= "\n\t\t\t\t".'<textarea readonly="readonly" style="width:250px; height:150px; overflow:scroll; white-space:pre; resize:both;">';
		foreach ( $affected_files as $file ) {
			$echo_str .= $file."\n";
		}
		$echo_str .= "\n\t\t\t\t".'</textarea>';
		$echo_str .= "\n\t\t\t".'</div>';
		$echo_str .= "\n\t\t".'</div>';
		$echo_str .= "\n\t\t".'<br/><label>Are you sure that you want to continue?</label><br/>';
		$echo_str .= "\n\t\t".'<style>';
		$echo_str .= "\n\t\t".'.wfu_cleanlog_files button:before { content: "Click to see affected files"; } ';
		$echo_str .= "\n\t\t".'.wfu_cleanlog_files.visible button:before { content: "Close list"; } ';
		$echo_str .= "\n\t\t".'.wfu_cleanlog_files #wfu_cleanlog_prompt_list { display: none; } ';
		$echo_str .= "\n\t\t".'.wfu_cleanlog_files.visible #wfu_cleanlog_prompt_list { display: block; } ';
		$echo_str .= "\n\t\t".'</style>';
	}
	else {
		if ( $data["code"] == "0" )
			$echo_str .= "\n\t\t".'<label>This will erase all records <strong>before '.date("Y-m-d", $data["dateold"]).'</strong> kept by the plugin in the database (like file metadata and userdata, however files uploaded by the plugin will be maintained). Are you sure that you want to continue?</label><br/>';
		elseif ( $data["code"] == "1" )
			$echo_str .= "\n\t\t".'<label>This will erase all records <strong>older than '.$data["periodold"].' '.$data["periodtype"].'</strong> kept by the plugin in the database (like file metadata and userdata, however files uploaded by the plugin will be maintained). Are you sure that you want to continue?</label><br/>';
		elseif ( $data["code"] == "2" )
			$echo_str .= "\n\t\t".'<label>This will erase all records <strong>between '.date("Y-m-d", $data["datefrom"]).' and '.date("Y-m-d", $data["dateto"]).'</strong> kept by the plugin in the database (like file metadata and userdata, however files uploaded by the plugin will be maintained). Are you sure that you want to continue?</label><br/>';
		else
			$echo_str .= "\n\t\t".'<label>This will erase <strong>ALL</strong> records kept by the plugin in the database (like file metadata and userdata, however files uploaded by the plugin will be maintained). Are you sure that you want to continue?</label><br/>';
	}
	$echo_str .= "\n\t\t".'<p class="submit">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Yes">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Cancel">';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t".'</form>';
	$echo_str .= "\n".'</div>';
	return $echo_str;
}

/**
 * Execute Log Cleaning.
 *
 * This function cleans the database log based on criteria selected by the
 * admin.
 *
 * @since 3.3.1
 *
 * @redeclarable
 *
 * @return array An array containing the number of records and files affected by
 *         cleaning operation.
 */
function wfu_clean_log() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;

	if ( !current_user_can( 'manage_options' ) ) return array( "recs_count" => -1, "files_count" => -1 );
	if ( !check_admin_referer('wfu_clean_log') ) return array( "recs_count" => -1, "files_count" => -1 );
	
	$recs_count = 0;
	if ( isset($_POST['data']) && isset($_POST['submit']) && $_POST['submit'] == "Yes" ) {
		$data = wfu_clean_log_parse_data($_POST['data']);
		if ( $data["result"] ) {
			$table_name1 = $wpdb->prefix . "wfu_log";
			$table_name2 = $wpdb->prefix . "wfu_userdata";
			//$table_name3 = $wpdb->prefix . "wfu_dbxqueue";

			$affected_files = array();
			if ( $data["include_files"] ) {
				$affected_recs = $wpdb->get_results("SELECT * FROM $table_name1".wfu_clean_log_where_query($data));
				$affected_files = wfu_get_valid_affected_files($affected_recs);
			}
			$query1 = "DELETE FROM $table_name1";
			$query2 = "DELETE FROM $table_name2";
			//$query3 = "DELETE FROM $table_name3";
			if ( $data["code"] == "0" ) {
				$query1 .= " WHERE date_from < '".date('Y-m-d H:i:s', $data["dateold"])."'";
				$query2 .= " WHERE date_from < '".date('Y-m-d H:i:s', $data["dateold"])."'";
			}
			elseif ( $data["code"] == "1" ) {
				$date = strtotime(date('Y-m-d', strtotime('-'.$data["periodold"].' '.$data["periodtype"]))." 00:00");
				$query1 .= " WHERE date_from < '".date('Y-m-d H:i:s', $date)."'";
				$query2 .= " WHERE date_from < '".date('Y-m-d H:i:s', $date)."'";
			}
			elseif ( $data["code"] == "2" ) {
				$query1 .= " WHERE date_from < '".date('Y-m-d H:i:s', $data["datefrom"] + 86400)."' AND date_from >= '".date('Y-m-d H:i:s', $data["dateto"])."'";
				$query2 .= " WHERE date_from < '".date('Y-m-d H:i:s', $data["datefrom"] + 86400)."' AND date_from >= '".date('Y-m-d H:i:s', $data["dateto"])."'";
			}
			$recs_count = $wpdb->query($query1);
			$recs_count += $wpdb->query($query2);
			//$recs_count += $wpdb->query($query3);
			
			//delete affected files
			$files_count = 0;
			foreach( $affected_files as $file ) {
				unlink($file);
				if ( !file_exists($file) ) $files_count ++;
			}
		}
	}
	
	return array( "recs_count" => $recs_count, "files_count" => $files_count );
}

/**
 * Confirm Purge of Data Operation.
 *
 * This function shows a page to confirm purge of data operation. Purge
 * operation deletes all plugin data from the website.
 *
 * @since 4.9.1
 *
 * @param string $nonce A string that verifies that the request came from
 *        Maintenance Actions page.
 *
 * @return string The HTML code of the confirmation page.
 */
function wfu_purge_data_prompt($nonce) {
	$siteurl = site_url();

	if ( !current_user_can( 'manage_options' ) || !wp_verify_nonce($nonce, 'wfu_maintenance_actions') ) return wfu_maintenance_actions();

	$echo_str = "\n".'<div class="wrap">';
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=maintenance_actions" class="button" title="go back">Go back</a>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h2 style="margin-bottom: 10px;">Purge All Data</h2>';
	$echo_str .= "\n\t".'<form enctype="multipart/form-data" name="purge_data" id="purge_data" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="validate">';
	$nonce = wp_nonce_field('wfu_purge_data', '_wpnonce', false, false);
	$nonce_ref = wp_referer_field(false);
	$echo_str .= "\n\t\t".$nonce;
	$echo_str .= "\n\t\t".$nonce_ref;
	$echo_str .= "\n\t\t".'<input type="hidden" name="action" value="purge_data">';
	$echo_str .= "\n\t\t".'<label>This action will remove all plugin options and records from database, data stored in session and will dectivate the plugin. Use it only if you want to entirely remove the plugin from the website.</label><br/>';
	$echo_str .= "\n\t\t".'<br/><label>Are you sure you want to continue?</label><br/>';
	$echo_str .= "\n\t\t".'<p class="submit">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Yes">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Cancel">';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t".'</form>';
	$echo_str .= "\n".'</div>';
	return $echo_str;
}

/**
 * Purge Plugin Data.
 *
 * This function deletes all plugin data from the website. It drops the tables
 * of the plugin from the database, it deletes all plugin options and all plugin
 * data stored in session.
 *
 * @since 4.9.1
 *
 * @redeclarable
 *
 * @return bool Always true.
 */
function wfu_purge_data() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;

	if ( !current_user_can( 'manage_options' ) ) return;
	if ( !check_admin_referer('wfu_purge_data') ) return;
	
	if ( isset($_POST['submit']) && $_POST['submit'] == "Yes" ) {
		$all_options = array_keys(wp_load_alloptions());
		$all_session = array_keys($_SESSION);
		$wfu_options = wfu_get_all_plugin_options();
		//first delete relevant db options
		foreach ( $all_options as $opt1 )
			foreach ( $wfu_options as $opt2 )
				if ( $opt2[2] && $opt2[1] == "db" ) {
					if (( substr($opt2[0], 0, 1) != "*" && substr($opt2[0], -1) != "*" && $opt1 == $opt2[0] ) ||
						( substr($opt2[0], 0, 1) != "*" && substr($opt2[0], -1) == "*" && substr($opt1, 0, strlen($opt2[0])) == substr($opt2[0], 0, -1) ) ||
						( substr($opt2[0], 0, 1) == "*" && substr($opt2[0], -1) != "*" && substr($opt1, -strlen($opt2[0])) == substr($opt2[0], 1) ) ||
						( substr($opt2[0], 0, 1) == "*" && substr($opt2[0], -1) == "*" && strpos($opt1, substr($opt2[0], 1, -1)) !== false ))
						delete_option($opt1);
				}
		//then delete relevant session data
		foreach ( $all_session as $opt1 )
			foreach ( $wfu_options as $opt2 )
				if ( $opt2[2] && $opt2[1] == "session" ) {
					if (( substr($opt2[0], 0, 1) != "*" && substr($opt2[0], -1) != "*" && $opt1 == $opt2[0] ) ||
						( substr($opt2[0], 0, 1) != "*" && substr($opt2[0], -1) == "*" && substr($opt1, 0, strlen($opt2[0])) == substr($opt2[0], 0, -1) ) ||
						( substr($opt2[0], 0, 1) == "*" && substr($opt2[0], -1) != "*" && substr($opt1, -strlen($opt2[0])) == substr($opt2[0], 1) ) ||
						( substr($opt2[0], 0, 1) == "*" && substr($opt2[0], -1) == "*" && strpos($opt1, substr($opt2[0], 1, -1)) !== false ))
						unset($_SESSION[$opt1]);
				}
		//then delete relevant tables
		$wpdb->query( "DROP TABLE IF EXISTS ".$wpdb->prefix."wfu_log" );
		$wpdb->query( "DROP TABLE IF EXISTS ".$wpdb->prefix."wfu_userdata" );
		$wpdb->query( "DROP TABLE IF EXISTS ".$wpdb->prefix."wfu_dbxqueue" );
		//then deactivate the plugin
		deactivate_plugins( plugin_basename( WPFILEUPLOAD_PLUGINFILE ) );
	}
	else return;
	
	return true;
}


/**
 * Update File Transfers.
 *
 * This function causes the file transfers manager to re-check the pending file
 * tranfers immediately.
 *
 * @since 4.6.0
 *
 * @redeclarable
 *
 * @param bool $clearfiles Optional. If it is true then all pending file
 *        transfers will be cleared.
 */
function wfu_process_all_transfers($clearfiles = false) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;
	if ( $clearfiles ) {
		$table_name1 = $wpdb->prefix . "wfu_log";
		$table_name3 = $wpdb->prefix . "wfu_dbxqueue";
		$wpdb->query('DELETE FROM '.$table_name3);
	}
	wfu_schedule_transfermanager(true);
}

/**
 * Check and Execute Reset of File Transfers.
 *
 * This function performs security checks whether reset of file transfers can be
 * executed and then executes this operation.
 *
 * @since 4.6.0
 *
 * @param string $nonce A string that verifies that the request came from
 *        Maintenance Actions page.
 *
 * @return bool Always true.
 */
function wfu_reset_all_transfers_controller($nonce) {
	if ( !current_user_can( 'manage_options' ) ) return false;
	if ( !wp_verify_nonce($nonce, 'wfu_maintenance_actions') ) return false;
	
	wfu_process_all_transfers();
	
	return true;
}

/**
 * Check and Execute Clear of File Transfers.
 *
 * This function performs security checks whether clear of file transfers can be
 * executed and then executes this operation.
 *
 * @since 4.6.0
 *
 * @param string $nonce A string that verifies that the request came from
 *        Maintenance Actions page.
 *
 * @return bool Always true.
 */
function wfu_clear_all_transfers_controller($nonce) {
	if ( !current_user_can( 'manage_options' ) ) return false;
	if ( !wp_verify_nonce($nonce, 'wfu_maintenance_actions') ) return false;
	
	wfu_process_all_transfers(true);
	
	return true;
}

?>