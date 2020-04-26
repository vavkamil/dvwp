<?php

function wfu_load_pd_policies() {
	$pd_policies = array();
	$pd_policies_data = get_option( "wordpress_file_upload_pd_policies" );
	if ( !is_array($pd_policies_data) ) $pd_policies_data = array();
	//the first policy is the default one; the default policy is the one that
	//applies to all the users that do not have any other associated policy;
	//if $pd_policies has no items then we need to auto-create the default
	//policy
	if ( count($pd_policies_data) == 0 ) {
		//initialize default policy
		$policy = new WFU_Personal_Data_Policy();
		$policy->set_name("Default PD Policy");
		//store to db
		array_push($pd_policies_data, $policy->export_policy());
		set_option("wordpress_file_upload_pd_policies", $pd_policies_data);
		//add to return array
		array_push($pd_policies, $policy);
	}
	else {
		foreach( $pd_policies_data as $data ) {
			$policy = new WFU_Personal_Data_Policy();
		}
	}
}

function wfu_manage_personaldata_policies($error_message = "") {
	if ( !current_user_can( 'manage_options' ) ) return;
	$siteurl = site_url();
	$basic = true;

	$echo_str = "";
	$echo_str .= "\n".'<div class="wrap">';
	$echo_str .= "\n\t".'<h2>Wordpress File Upload Control Panel</h2>';
	if ( $error_message != "" ) {
		$echo_str .= "\n\t".'<div class="error">';
		$echo_str .= "\n\t\t".'<p>'.$error_message.'</p>';
		$echo_str .= "\n\t".'</div>';
	}
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$nonce = wp_nonce_field('wfu_edit_policy', '_wpnonce', false, false);
	$nonce_ref = wp_referer_field(false);
	$echo_str .= "\n\t".$nonce;
	$echo_str .= "\n\t".$nonce_ref;
	$echo_str .= wfu_generate_dashboard_menu("\n\t\t", "Personal Data");
	
	$echo_str2 = $echo_str;
	
	//select user
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<h3 style="margin-bottom: 10px;">Select User</h3>';
	$echo_str .= "\n\t\t".'<table class="form-table">';
	$echo_str .= "\n\t\t\t".'<tbody>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Type user in text box</label><br />';
	$echo_str .= "\n\t\t\t\t\t\t".'<input type="text" id="wfu_pd_user_box0" class="wfu_pd_user_box0" value="" /><br />';
	$echo_str .= "\n\t\t\t\t\t\t".'<select id="wfu_pd_user_select0" class="wfu_pd_user_select0" size="10"></select>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Select a user to perform operations (export or delete) on his/her personal data. Type an asterisk (*) in the text box to display all users.</label>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t".'</table>';
	$echo_str .= "\n\t".'</div>';
	//export actions
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<h3 style="margin-bottom: 10px;">Export Operations</h3>';
	$echo_str .= "\n\t\t".'<table class="form-table">';
	$echo_str .= "\n\t\t\t".'<tbody>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<a href="javascript:wfu_export_user_data();" class="button" title="Export File Data of User">Export File Data of User</a>';
	$echo_str .= "\n\t\t\t\t\t\t".'<input id="wfu_download_file_nonce" type="hidden" value="'.wp_create_nonce('wfu_download_file_invoker').'" />';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Export uploaded valid file data, together with any userdata fields, to a comma-separated text file.</label>';
	$echo_str .= "\n\t\t\t\t\t\t".'<div id="wfu_file_download_container_1" style="display: none;"></div>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t".'</table>';
	$handler = 'function() { wfu_initialize_consent_policy_basic(); }';
	$echo_str .= "\n\t".'<script type="text/javascript">if(window.addEventListener) { window.addEventListener("load", '.$handler.', false); } else if(window.attachEvent) { window.attachEvent("onload", '.$handler.'); } else { window["onload"] = '.$handler.'; }</script>';
	$echo_str .= "\n\t".'</div>';
	//delete actions
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<h3 style="margin-bottom: 10px;">Erase Operations</h3>';
	$echo_str .= "\n\t\t".'<table class="form-table">';
	$echo_str .= "\n\t\t\t".'<tbody>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<a id="wfu_erase_userdata0" href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=erase_userdata_ask" class="button" title="Erase All User Data" onclick="if (!wfu_erase_user_data_check()) return false;">Erase All User Data</a>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Erase all data of user kept in database.</label>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t".'</table>';
	$handler = 'function() { wfu_initialize_consent_policy_basic(); }';
	$echo_str .= "\n\t".'<script type="text/javascript">if(window.addEventListener) { window.addEventListener("load", '.$handler.', false); } else if(window.attachEvent) { window.attachEvent("onload", '.$handler.'); } else { window["onload"] = '.$handler.'; }</script>';
	$echo_str .= "\n\t".'</div>';
	
	if ( $basic ) return $echo_str;
	$echo_str = $echo_str2;
	
	$echo_str .= "\n\t\t".'<form enctype="multipart/form-data" name="personaldata" id="personaldata" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=manage_pd_policies" class="validate">';
	$nonce = wp_nonce_field('wfu_manage_pd_policies', '_wpnonce', false, false);
	$nonce_ref = wp_referer_field(false);
	$echo_str .= "\n\t\t\t".$nonce;
	$echo_str .= "\n\t\t\t".$nonce_ref;
	$echo_str .= "\n\t\t\t".'<input type="hidden" name="action" value="manage_pd_policies" />';
	$echo_str .= "\n\t\t\t".'<div class="tablenav top">';
	$echo_str .= "\n\t\t\t\t".'<div class="alignleft actions bulkactions">';
	$echo_str .= "\n\t\t\t\t\t".'<select name="bulkaction" id="bulk-action-selector-top">';
	$echo_str .= "\n\t\t\t\t\t\t".'<option value="-1" selected="selected">Bulk Actions</option>';
	$echo_str .= "\n\t\t\t\t\t\t".'<option value="delete">Delete</option>';
	$echo_str .= "\n\t\t\t\t\t\t".'<option value="activate">Activate</option>';
	$echo_str .= "\n\t\t\t\t\t\t".'<option value="deactivate">Deactivate</option>';
	$echo_str .= "\n\t\t\t\t\t".'</select>';
	$echo_str .= "\n\t\t\t\t\t".'<input type="submit" id="doaction" name="doaction" class="button action" value="Apply" />';
	$echo_str .= "\n\t\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=add_policy" class="button" title="add new personal data policy" style="float:right;">Add New Personal Data Policy</a>';
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<table class="wp-list-table widefat fixed striped">';
	$echo_str .= "\n\t\t\t\t".'<thead>';
	$echo_str .= "\n\t\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t\t".'<td scope="col" width="5%" style="width:5%;">';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<input id="cb-select-all" type="checkbox" onchange="var actions=document.getElementsByName(\'hook[]\'); for (var i=0; i<actions.length; i++) {actions[i].checked=this.checked;}" />';
	$echo_str .= "\n\t\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<th scope="col" width="30%" style="width:30%;">';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<label>Title</label>';
	$echo_str .= "\n\t\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t\t".'<th scope="col" width="50%" style="width:50%;">';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<label>Description</label>';
	$echo_str .= "\n\t\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t\t".'<th scope="col" width="15%" style="width:15%; text-align:center;">';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<label>Status</label>';
	$echo_str .= "\n\t\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t\t".'</thead>';
	$echo_str .= "\n\t\t\t\t".'<tbody>';

	$echo_str .= "\n\t\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t\t".'</table>';
	$echo_str .= "\n\t\t".'</form>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n".'</div>';
	
	return $echo_str;
}

function wfu_edit_pd_policy($key = "", $error_status = "") {
	$siteurl = site_url();

	if ( !current_user_can( 'manage_options' ) ) return;
	
	$policy = new WFU_Personal_Data_Policy();

	$echo_str = "";
	$echo_str = '<div class="wrap">';
	$echo_str .= "\n\t".'<h2>Wordpress File Upload Control Panel</h2>';
	if ( $error_status == "error" ) {
		$echo_str .= "\n\t".'<div class="error">';
		$echo_str .= "\n\t\t".'<p>'.'an error occurred'.'</p>';
		$echo_str .= "\n\t".'</div>';
	}
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=personal_data" class="button" title="go back">Go back</a>';
	$echo_str .= "\n\t\t".'<h2 style="margin-bottom: 10px; margin-top: 20px;">'.( $key == "" ? 'Add New Policy' : 'Edit Policy' ).'</h2>';
	$echo_str .= "\n\t\t".'<form enctype="multipart/form-data" name="updatepolicy" id="updatepolicy" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=updatepolicy" class="validate">';
	$nonce = wp_nonce_field('wfu_edit_policy', '_wpnonce', false, false);
	$nonce_ref = wp_referer_field(false);
	$echo_str .= "\n\t\t\t".$nonce;
	$echo_str .= "\n\t\t\t".$nonce_ref;
	$echo_str .= "\n\t\t\t".'<input type="hidden" name="action" value="updatepolicy">';
	$echo_str .= "\n\t\t\t".'<input type="hidden" name="wfu_key" value="'.$key.'">';
	$echo_str .= "\n\t\t\t".'<input type="hidden" id="wfu_PD_bank" name="wfu_PD_bank" value="">';
	$echo_str .= "\n\t\t\t".'<div id="titlediv">';
	$echo_str .= "\n\t\t\t\t".'<input type="text" id="title" value="'.$policy->get_name().'">';
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<h3 style="margin-top: 40px;">Plugin Operations';
	$echo_str .= "\n\t\t\t\t".'<div class="wfu_pdheader_button wfu_pdop_header_button"></div>';
	$echo_str .= "\n\t\t\t".'</h3>';
	$echo_str .= "\n\t\t\t".'<div class="wfu_plugin_operations">';
	$echo_str .= "\n\t\t\t\t".'<input type="hidden" id="wfu_consent_policy" name="wfu_consent_policy" value="'.wfu_encode_array_to_string($policy->get_consent_policy(true)).'" />';
	$echo_str .= "\n\t\t\t\t".'<span>Select which plugin operations involved in personal data handling will be executed.</span>';
	$operations = wfu_get_pd_operations_structure();
	$echo_str .= wfu_render_pd_operations("\n\t\t\t\t", $operations);
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<h3 style="margin-top: 20px;">Consent Operations';
	$echo_str .= "\n\t\t\t\t".'<div class="wfu_pdheader_button wfu_conop_header_button"></div>';
	$echo_str .= "\n\t\t\t".'</h3>';
	$echo_str .= "\n\t\t\t".'<div class="wfu_consent_operations">';
	$echo_str .= "\n\t\t\t\t".'<span>Select which plugin operations that have been selected to be executed require consent.</span>';
	$echo_str .= wfu_render_consent_operations("\n\t\t\t\t", $operations);
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<h3 style="margin-top: 20px;">Consent Questions';
	$echo_str .= "\n\t\t\t\t".'<div class="wfu_pdheader_button wfu_conquestion_header_button"></div>';
	$echo_str .= "\n\t\t\t".'</h3>';
	$echo_str .= "\n\t\t\t".'<div class="wfu_consent_questions">';
	$echo_str .= "\n\t\t\t\t".'<span>Define how consent questions will be presented to users through the upload form.</span>';
	$echo_str .= wfu_render_consent_questions();
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<h3 style="margin-top: 20px;">Permissions';
	$echo_str .= "\n\t\t\t\t".'<div class="wfu_pdheader_button wfu_permissions_header_button"></div>';
	$echo_str .= "\n\t\t\t".'</h3>';
	$echo_str .= "\n\t\t\t".'<div class="wfu_consent_permissions">';
	$echo_str .= "\n\t\t\t\t".'<input type="hidden" id="wfu_permissions_policy" name="wfu_permissions_policy" value="'.wfu_encode_array_to_string($policy->get_permissions_policy(true)).'" />';
	$echo_str .= "\n\t\t\t\t".'<span>Define how users will access, review and control their personal data.</span>';
	$echo_str .= wfu_render_pd_permissions("\n\t\t\t\t");
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<h3 style="margin-top: 20px;">Log Actions';
	$echo_str .= "\n\t\t\t\t".'<div class="wfu_pdheader_button wfu_logactions_header_button"></div>';
	$echo_str .= "\n\t\t\t".'</h3>';
	$echo_str .= "\n\t\t\t".'<div class="wfu_consent_logactions">';
	$echo_str .= "\n\t\t\t\t".'<input type="hidden" id="wfu_logactions_policy" name="wfu_logactions_policy" value="'.wfu_encode_array_to_string($policy->get_logactions_policy(true)).'" />';
	$echo_str .= "\n\t\t\t\t".'<span>Define which actions occurring on personal data will be logged.</span>';
	$echo_str .= wfu_render_pd_logactions("\n\t\t\t\t");
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<h3 style="margin-top: 20px;">Assigned Users';
	$echo_str .= "\n\t\t\t\t".'<div class="wfu_pdheader_button wfu_pdusers_header_button"></div>';
	$echo_str .= "\n\t\t\t".'</h3>';
	$echo_str .= "\n\t\t\t".'<div class="wfu_consent_users">';
	$echo_str .= "\n\t\t\t\t".'<input type="hidden" id="wfu_assigned_users" name="wfu_assigned_users" value="'.wfu_encode_array_to_string($policy->get_assigned_users()).'" />';
	$echo_str .= "\n\t\t\t\t".'<span>Select the user roles and users assigned to this personal data policy.</span>';
	$echo_str .= wfu_render_pd_users("\n\t\t\t\t");
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<div class="submit">';
	$echo_str .= "\n\t\t\t\t".'<input type="submit" id="submitcancel" class="button" name="submitform" value="Cancel" />';
	$echo_str .= "\n\t\t\t\t".'<input type="submit" id="submitsave" class="button-primary" name="submitform" value="Save" onclick="wfu_pd_pre_save_actions();" />';
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t".'</form>';
	$echo_str .= "\n\t".'</div>';
	$params["oplevels"] = WFU_VAR("WFU_PD_VISIBLE_OPLEVELS");
	$params["perlevels"] = WFU_VAR("WFU_PD_VISIBLE_PERLEVELS");
	$params["loglevels"] = WFU_VAR("WFU_PD_VISIBLE_LOGLEVELS");
	$params["multi_op_assignments"] = true;
	$handler = 'function() { wfu_initialize_consent_policy('.wfu_PHP_array_to_JS_object($params).'); }';
	$echo_str .= "\n\t".'<script type="text/javascript">if(window.addEventListener) { window.addEventListener("load", '.$handler.', false); } else if(window.attachEvent) { window.attachEvent("onload", '.$handler.'); } else { window["onload"] = '.$handler.'; }</script>';
	$echo_str .= "\n".'</div>';
	
	return $echo_str;
}

function wfu_get_pd_operations_structure($only_indices = false) {
	$opdefs = wfu_personal_data_operations();
	//make the array indexed
	$ops_idx = array();
	foreach ( $opdefs as $def ) {
		$opID = $def["ID"];
		$ops_idx[$opID] = $def;
		$ops_idx[$opID]["children"] = array();
	}
	//add topmost dummy def and find for all grouped defs their children 
	$ops_idx[0] = array( "ID" => 0, "children" => array() );
	foreach ( $ops_idx as $ind => $def ) {
		if ( $ind > 0 ) {
			$par = $ops_idx[$def["Parent"]];
			//put this def in its parent's children array in the correct order
			$pos = -1;
			foreach ( $par["children"] as $ind2 => $childID ) {
				$def2 = $ops_idx[$childID];
				if ( $def["Order"] < $def2["Order"] ) {
					$pos = $ind2;
					break;
				}			
			}
			if ( $pos > -1 ) array_splice($ops_idx[$def["Parent"]]["children"], $pos, 0, $ind);
			else array_push($ops_idx[$def["Parent"]]["children"], $ind);
		}
	}
	//create array of nested defs
	$ops = $ops_idx[0]["children"];
	_wfu_nested_pd_operations_array($ops, $ops_idx, $only_indices);
	
	return $ops;
}

function _wfu_nested_pd_operations_array(&$array, $operations_indexed, $only_indices) {
	foreach ( $array as $ind => $childID ) {
		$def = $operations_indexed[$childID];
		if ( $only_indices ) $array[$ind] = array( "ID" => $def["ID"], "children" => $def["children"] );
		else $array[$ind] = $def;
		_wfu_nested_pd_operations_array($array[$ind]["children"], $operations_indexed, $only_indices);
	}
}

function wfu_render_pd_operations($dlp, $operations) {
	$html = "";
	$html .= $dlp.'<div class="wfu_pdop_topmost_panel wfu_pdop_level_1">';
	_wfu_render_nested_pd_operations($html, $operations, 1, $dlp."\t");
	$html .= $dlp.'</div>';
	
	return $html;
}

function _wfu_render_nested_pd_operations(&$html, $operations, $level, $dlp) {
	foreach ( $operations as $def ) {
		$atomic = ( count($def["children"]) == 0 );
		$html .= $dlp.'<div class="wfu_pdop_container" id="wfu_pdop_container_'.$def["ID"].'">';
		$html .= $dlp."\t".'<div class="wfu_pdop_header'.( $atomic || ( $level == WFU_VAR("WFU_PD_VISIBLE_OPLEVELS") ) ? " atomic" : "" ).'" id="wfu_pdop_header_'.$def["ID"].'">';
		$html .= $dlp."\t\t".'<label>'.$def["Name"].'</label>';
		$html .= $dlp."\t\t".'<input type="checkbox" id="wfu_pdop_'.$def["ID"].'" class="wfu_pdop_selector" onchange="wfu_pdop_toggle(this);" />';
		if ( !$atomic && ( WFU_VAR("WFU_PD_VISIBLE_OPLEVELS") < 1 || $level < WFU_VAR("WFU_PD_VISIBLE_OPLEVELS") ) ) $html .= $dlp."\t\t".'<div class="wfu_pdop_button"></div>';
		$html .= $dlp."\t".'</div>';
		if ( !$atomic ) {
			$html .= $dlp."\t".'<div class="wfu_pdop_panel wfu_pdop_level_'.($level + 1).'" id="wfu_pdop_panel_'.$def["ID"].'">';
			_wfu_render_nested_pd_operations($html, $def["children"], $level + 1, $dlp."\t\t");
			$html .= $dlp."\t".'</div>';
		}
		$html .= $dlp.'</div>';
	}
}

function wfu_render_consent_operations($dlp, $operations) {
	$html = "";
	$html .= $dlp.'<div class="wfu_conop_topmost_panel wfu_conop_level_1">';
	_wfu_render_nested_consent_operations($html, $operations, 1, $dlp."\t");
	$html .= $dlp.'</div>';
	
	return $html;
}

function _wfu_render_nested_consent_operations(&$html, $operations, $level, $dlp) {
	foreach ( $operations as $def ) {
		$atomic = ( count($def["children"]) == 0 );
		$html .= $dlp.'<div class="wfu_conop_container" id="wfu_conop_container_'.$def["ID"].'">';
		$html .= $dlp."\t".'<div class="wfu_conop_header'.( $atomic || ( $level == WFU_VAR("WFU_PD_VISIBLE_OPLEVELS") ) ? " atomic" : "" ).'" id="wfu_conop_header_'.$def["ID"].'">';
		$html .= $dlp."\t\t".'<label>'.$def["Name"].'</label>';
		$html .= $dlp."\t\t".'<input type="checkbox" id="wfu_conop_'.$def["ID"].'" class="wfu_conop_selector" onchange="wfu_conop_toggle(this);" />';
		if ( !$atomic && ( WFU_VAR("WFU_PD_VISIBLE_OPLEVELS") < 1 || $level < WFU_VAR("WFU_PD_VISIBLE_OPLEVELS") ) ) $html .= $dlp."\t\t".'<div class="wfu_conop_button"></div>';
		$html .= $dlp."\t".'</div>';
		if ( !$atomic ) {
			$html .= $dlp."\t".'<div class="wfu_conop_panel wfu_conop_level_'.($level + 1).'" id="wfu_conop_panel_'.$def["ID"].'">';
			_wfu_render_nested_consent_operations($html, $def["children"], $level + 1, $dlp."\t\t");
			$html .= $dlp."\t".'</div>';
		}
		$html .= $dlp.'</div>';
	}
}

function wfu_render_consent_questions() {
	$html = '<div class="wfu_conquestion_topmost_panel">';
	$html .= '<div style="display:none;">';
	$html .= '<div class="wfu_conquestion_btn wfu_conquestion_add" id="wfu_conquestion_add"><img class="wfu_conquestion_add" src="'.WFU_IMAGE_ADMIN_USERDATA_ADD.'" /></div>';
	$html .= '<div class="wfu_conquestion_btn wfu_conquestion_remove" id="wfu_conquestion_remove"><img class="wfu_conquestion_remove" src="'.WFU_IMAGE_ADMIN_USERDATA_REMOVE.'" /></div>';
	$html .= '<div class="wfu_conquestion_btn wfu_conquestion_up" id="wfu_conquestion_up"><img class="wfu_conquestion_up" src="'.WFU_IMAGE_ADMIN_USERDATA_UP.'" /></div>';
	$html .= '<div class="wfu_conquestion_btn wfu_conquestion_down" id="wfu_conquestion_down"><img class="wfu_conquestion_down" src="'.WFU_IMAGE_ADMIN_USERDATA_DOWN.'" /></div>';
	$html .= '</div>';
	$html .= '<div class="wfu_conquestions_operations">';
	$html .= '<label>Operations</label>';
	$html .= '<div class="wfu_conquestions_oppanel">';
	$html .= '<table class="wfu_conquestions_optable" id="wfu_conquestions_optable">';
	$html .= '<thead>';
	$html .= '<tr>';
	$html .= '<th>Operation</th>';
	$html .= '<th>Selected</th>';
	$html .= '<th>Inverse</th>';
	$html .= '</tr>';
	$html .= '</thead>';
	$html .= '<tbody></tbody>';
	$html .= '</table>';
	$html .= '</div>';
	$html .= '</div>';
	$html .= '<div class="wfu_conquestions_container" id="wfu_conquestions_container">';
	$html .= '</div>';
	$html .= '</div>';
	
	return $html;
}

function wfu_render_pd_permissions($dlp) {
	$permissions = wfu_get_permissions_structure();
	$locations = wfu_personal_data_locations();
	$html = $dlp.'<div class="wfu_permissions_topmost_panel">';
	$html .= $dlp."\t".'<div class="wfu_permissions_panel">';
	$html .= $dlp."\t\t".'<table class="wfu_permissions_table" id="wfu_permissions_table">';
	$html .= $dlp."\t\t\t".'<thead>';
	$html .= $dlp."\t\t\t\t".'<tr>';
	$html .= $dlp."\t\t\t\t".'<th rowspan="2">Permission</th>';
	$html .= $dlp."\t\t\t\t".'<th colspan="'.count($locations).'">Locations</th>';
	$html .= $dlp."\t\t\t\t".'</tr>';
	$html .= $dlp."\t\t\t\t".'<tr>';
	foreach ( $locations as $location ) $html .= $dlp."\t\t\t\t".'<th>'.$location["Name"].'</th>';
	$html .= $dlp."\t\t\t\t".'</tr>';
	$html .= $dlp."\t\t\t".'</thead>';
	$html .= $dlp."\t\t\t".'<tbody>';
	$perm0 = array( "children" => $permissions , "Locations" => array() );
	$html .= _wfu_render_nested_pd_permissions($perm0, $locations, 1, $dlp."\t\t\t\t");
	$html .= $dlp."\t\t\t".'</tbody>';
	$html .= $dlp."\t\t".'</table>';
	$html .= $dlp."\t".'</div>';
	$html .= $dlp.'</div>';
	
	return $html;
}

function _wfu_render_nested_pd_permissions(&$permissions, $locations, $level, $dlp) {
	$html = "";
	foreach ( $permissions["children"] as $def ) {
		$atomic = ( count($def["children"]) == 0 );
		$html .= $dlp.'<tr class="wfu_perm_row" id="wfu_perm_row_'.$def["ID"].'" style="display:'.($level <= 1 ? 'table-row' : 'none').';">';
		$html .= $dlp."\t".'<td class="wfu_perm_cell">';
		$html .= $dlp."\t\t".'<div class="wfu_perm_container wfu_perm_level_'.$level.'">';
		$html .= $dlp."\t\t\t".'<label>'.$def["Name"].'</label>';
		if ( !$atomic && ( WFU_VAR("WFU_PD_VISIBLE_PERLEVELS") < 1 || $level < WFU_VAR("WFU_PD_VISIBLE_PERLEVELS") ) ) $html .= $dlp."\t\t\t".'<div class="wfu_perm_button" onclick="wfu_perm_button_action(this);"></div>';
		$html .= $dlp."\t\t".'</div>';
		$html .= $dlp."\t".'</td>';
		$childhtml = "";
		if ( !$atomic ) $childhtml .= _wfu_render_nested_pd_permissions($def, $locations, $level + 1, $dlp);
		foreach ( $locations as $location ) $html .= $dlp."\t".'<td class="wfu_location_cell">'.( in_array($location["ID"], $def["Locations"]) ? '<input type="checkbox" class="wfu_location_selector" onchange="wfu_perm_toggle(this);" />' : '' ).'</td>';
		$html .= $dlp.'</tr>';
		$html .= $childhtml;
		foreach ( $def["Locations"] as $locID )
			if ( !in_array($locID, $permissions["Locations"]) ) array_push($permissions["Locations"], $locID);
	}
	
	return $html;
}

function wfu_get_permissions_structure($only_indices = false) {
	$perdefs = wfu_personal_data_permissions();
	//make the array indexed
	$per_idx = array();
	foreach ( $perdefs as $def ) {
		$perID = $def["ID"];
		$per_idx[$perID] = $def;
		$per_idx[$perID]["children"] = array();
	}
	//add topmost dummy def and find for all grouped defs their children 
	$per_idx[0] = array( "ID" => 0, "children" => array() );
	foreach ( $per_idx as $ind => $def ) {
		if ( $ind > 0 ) {
			$par = $per_idx[$def["Parent"]];
			//put this def in its parent's children array in the correct order
			$pos = -1;
			foreach ( $par["children"] as $ind2 => $childID ) {
				$def2 = $per_idx[$childID];
				if ( $def["Order"] < $def2["Order"] ) {
					$pos = $ind2;
					break;
				}			
			}
			if ( $pos > -1 ) array_splice($per_idx[$def["Parent"]]["children"], $pos, 0, $ind);
			else array_push($per_idx[$def["Parent"]]["children"], $ind);
		}
	}
	//create array of nested defs
	$pers = $per_idx[0]["children"];
	_wfu_nested_permissions_array($pers, $per_idx, $only_indices);
	
	return $pers;
}

function _wfu_nested_permissions_array(&$array, $permissions_indexed, $only_indices) {
	foreach ( $array as $ind => $childID ) {
		$def = $permissions_indexed[$childID];
		if ( $only_indices ) $array[$ind] = array( "ID" => $def["ID"], "children" => $def["children"] );
		else $array[$ind] = $def;
		_wfu_nested_permissions_array($array[$ind]["children"], $permissions_indexed, $only_indices);
	}
}

function wfu_get_logactions_structure($only_indices = false) {
	$logdefs = wfu_personal_data_logactions();
	//make the array indexed
	$log_idx = array();
	foreach ( $logdefs as $def ) {
		$actID = $def["ID"];
		$log_idx[$actID] = $def;
		$log_idx[$actID]["children"] = array();
	}
	//add topmost dummy def and find for all grouped defs their children 
	$log_idx[0] = array( "ID" => 0, "children" => array() );
	foreach ( $log_idx as $ind => $def ) {
		if ( $ind > 0 ) {
			$par = $log_idx[$def["Parent"]];
			//put this def in its parent's children array in the correct order
			$pos = -1;
			foreach ( $par["children"] as $ind2 => $childID ) {
				$def2 = $log_idx[$childID];
				if ( $def["Order"] < $def2["Order"] ) {
					$pos = $ind2;
					break;
				}			
			}
			if ( $pos > -1 ) array_splice($log_idx[$def["Parent"]]["children"], $pos, 0, $ind);
			else array_push($log_idx[$def["Parent"]]["children"], $ind);
		}
	}
	//create array of nested defs
	$logactions = $log_idx[0]["children"];
	_wfu_nested_logactions_array($logactions, $log_idx, $only_indices);
	
	return $logactions;
}

function _wfu_nested_logactions_array(&$array, $logactions_indexed, $only_indices) {
	foreach ( $array as $ind => $childID ) {
		$def = $logactions_indexed[$childID];
		if ( $only_indices ) $array[$ind] = array( "ID" => $def["ID"], "children" => $def["children"] );
		else $array[$ind] = $def;
		_wfu_nested_logactions_array($array[$ind]["children"], $logactions_indexed, $only_indices);
	}
}

function wfu_render_pd_logactions($dlp) {
	$logactions = wfu_get_logactions_structure();
	$entities = wfu_personal_data_entities();
	$html = $dlp.'<div class="wfu_logactions_topmost_panel">';
	$html .= $dlp."\t".'<div class="wfu_logactions_panel">';
	$html .= $dlp."\t\t".'<table class="wfu_logactions_table" id="wfu_logactions_table">';
	$html .= $dlp."\t\t\t".'<thead>';
	$html .= $dlp."\t\t\t\t".'<tr>';
	$html .= $dlp."\t\t\t\t".'<th rowspan="2">Log Action</th>';
	$html .= $dlp."\t\t\t\t".'<th colspan="'.count($entities).'">Entities</th>';
	$html .= $dlp."\t\t\t\t".'</tr>';
	$html .= $dlp."\t\t\t\t".'<tr>';
	foreach ( $entities as $entity ) $html .= $dlp."\t\t\t\t".'<th>'.$entity["Name"].'</th>';
	$html .= $dlp."\t\t\t\t".'</tr>';
	$html .= $dlp."\t\t\t".'</thead>';
	$html .= $dlp."\t\t\t".'<tbody>';
	$log0 = array( "children" => $logactions , "Entities" => array() );
	$html .= _wfu_render_nested_pd_logactions($log0, $entities, 1, $dlp."\t\t\t\t");
	$html .= $dlp."\t\t\t".'</tbody>';
	$html .= $dlp."\t\t".'</table>';
	$html .= $dlp."\t".'</div>';
	$html .= $dlp.'</div>';
	
	return $html;
}

function _wfu_render_nested_pd_logactions(&$logactions, $entities, $level, $dlp) {
	$html = "";
	foreach ( $logactions["children"] as $def ) {
		$atomic = ( count($def["children"]) == 0 );
		$html .= $dlp.'<tr class="wfu_log_row" id="wfu_log_row_'.$def["ID"].'" style="display:'.($level <= 1 ? 'table-row' : 'none').';">';
		$html .= $dlp."\t".'<td class="wfu_log_cell">';
		$html .= $dlp."\t\t".'<div class="wfu_log_container wfu_log_level_'.$level.'">';
		$html .= $dlp."\t\t\t".'<label>'.$def["Name"].'</label>';
		if ( !$atomic && ( WFU_VAR("WFU_PD_VISIBLE_LOGLEVELS") < 1 || $level < WFU_VAR("WFU_PD_VISIBLE_LOGLEVELS") ) ) $html .= $dlp."\t\t\t".'<div class="wfu_log_button" onclick="wfu_log_button_action(this);"></div>';
		$html .= $dlp."\t\t".'</div>';
		$html .= $dlp."\t".'</td>';
		$childhtml = "";
		if ( !$atomic ) $childhtml .= _wfu_render_nested_pd_logactions($def, $entities, $level + 1, $dlp);
		foreach ( $entities as $entity ) $html .= $dlp."\t".'<td class="wfu_entity_cell">'.( in_array($entity["ID"], $def["Entities"]) ? '<input type="checkbox" class="wfu_entity_selector" onchange="wfu_log_toggle(this);" />' : '' ).'</td>';
		$html .= $dlp.'</tr>';
		$html .= $childhtml;
		foreach ( $def["Entities"] as $entID )
			if ( !in_array($entID, $logactions["Entities"]) ) array_push($logactions["Entities"], $entID);
	}
	
	return $html;
}

function wfu_render_pd_users($dlp) {
	global $wp_roles;
	$html = $dlp.'<div class="wfu_pdusers_topmost_panel">';
	$html .= $dlp."\t".'<label>Roles</label>';
	$html .= $dlp."\t".'<div class="wfu_pdusers_rolepanel">';
	$roletype = "in";
	for ( $i = 1; $i <= 2; $i++ ) {
		$html .= $dlp."\t\t".'<div class="wfu_pdusers_roles_container" id="wfu_pdusers_roles_'.$roletype.'_container">';
		$html .= $dlp."\t\t\t".'<input type="radio" name="wfu_pdusers_roletypes" value="'.( $roletype == "in" ? "include" : "exclude" ).'" onchange="wfu_pdusers_roletype_handler(this);" />';
		$html .= $dlp."\t\t\t".'<label>'.( $roletype == "in" ? "Include" : "Exclude" ).'</label>';
		$html .= $dlp."\t\t\t".'<div class="wfu_pdusers_roles_toppanel">';
		$html .= $dlp."\t\t\t\t".'<div class="wfu_pdusers_roles_leftpanel">';
		$html .= $dlp."\t\t\t\t\t".'<select class="wfu_pdusers_roles_list" multiple>';
		$roles = $wp_roles->get_names();
		foreach ( $roles as $role => $rolename ) $html .= $dlp."\t\t\t\t\t\t".'<option value="'.$role.'">'.$rolename.'</option>';
		$html .= $dlp."\t\t\t\t\t".'</select>';
		$html .= $dlp."\t\t\t\t".'</div>';
		$html .= $dlp."\t\t\t\t".'<div class="wfu_pdusers_roles_midpanel">';
		$html .= $dlp."\t\t\t\t\t".'<span class="wfu_pdusers_roles_add" onclick="wfu_pdusers_addrole_handler(this);"></span>';
		$html .= $dlp."\t\t\t\t".'</div>';
		$html .= $dlp."\t\t\t\t".'<div class="wfu_pdusers_roles_rightpanel">';
		$html .= $dlp."\t\t\t\t\t".'<div class="wfu_pdusers_roles_show">';
		$html .= $dlp."\t\t\t\t\t\t".'<div class="wfu_pdusers_roles_back"></div>';
		//$html .= $dlp."\t\t\t\t\t\t".'<div class="wfu_pdusers_roles_role administrators">Administrators<span onclick="wfu_pdusers_removerole_handler();"></span></div>';
		$html .= $dlp."\t\t\t\t\t".'</div>';
		$html .= $dlp."\t\t\t\t".'</div>';
		$html .= $dlp."\t\t\t".'</div>';
		$html .= $dlp."\t\t".'</div>';
		$roletype = "out";
	}
	$html .= $dlp."\t".'</div>';
	$html .= $dlp."\t".'<label>Individual Users</label>';
	$html .= $dlp."\t".'<div class="wfu_pdusers_userpanel">';
	$usertype = "in";
	for ( $i = 1; $i <= 2; $i++ ) {
		$html .= $dlp."\t\t".'<div class="wfu_pdusers_users_container" id="wfu_pdusers_users_'.$usertype.'_container">';
		$html .= $dlp."\t\t\t".'<label>'.( $usertype == "in" ? "Include" : "Exclude" ).'</label>';
		$html .= $dlp."\t\t\t".'<div class="wfu_pdusers_users_toppanel">';
		$html .= $dlp."\t\t\t\t".'<div class="wfu_pdusers_users_leftpanel">';
		$html .= $dlp."\t\t\t\t\t".'<label>type user name</label>';
		$html .= $dlp."\t\t\t\t\t".'<input type="text" value="" />';
		$html .= $dlp."\t\t\t\t\t".'<select class="wfu_pdusers_users_list" multiple>';
		$html .= $dlp."\t\t\t\t\t".'</select>';
		$html .= $dlp."\t\t\t\t".'</div>';
		$html .= $dlp."\t\t\t\t".'<div class="wfu_pdusers_users_midpanel">';
		$html .= $dlp."\t\t\t\t\t".'<label>a</label>';
		$html .= $dlp."\t\t\t\t\t".'<span class="wfu_pdusers_users_add" onclick="wfu_pdusers_adduser_handler(this);"></span>';
		$html .= $dlp."\t\t\t\t".'</div>';
		$html .= $dlp."\t\t\t\t".'<div class="wfu_pdusers_users_rightpanel">';
		$html .= $dlp."\t\t\t\t\t".'<div class="wfu_pdusers_users_show">';
		$html .= $dlp."\t\t\t\t\t\t".'<div class="wfu_pdusers_users_back"></div>';
		//$html .= $dlp."\t\t\t\t\t\t".'<div class="wfu_pdusers_users_role administrators">Administrators<span onclick="wfu_pdusers_removerole_handler();"></span></div>';
		$html .= $dlp."\t\t\t\t\t".'</div>';
		$html .= $dlp."\t\t\t\t".'</div>';
		$html .= $dlp."\t\t\t".'</div>';
		$html .= $dlp."\t\t".'</div>';
		$usertype = "out";
	}
	$html .= $dlp."\t".'</div>';
	$html .= $dlp.'</div>';
	
	return $html;
}

function wfu_update_pd_policy() {
	if ( !current_user_can( 'manage_options' ) ) return;
	if ( !check_admin_referer('wfu_edit_policy') ) return;
	
	
}

function wfu_erase_userdata_ask_prompt($username) {
	$siteurl = site_url();

	if ( !current_user_can( 'manage_options' ) ) return wfu_manage_personaldata_policies();

	$user = get_user_by('login', $username);
	if ( $user->ID == 0 ) return wfu_manage_personaldata_policies();

	$echo_str = "\n".'<div class="wrap">';
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=personal_data" class="button" title="go back">Go back</a>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h2 style="margin-bottom: 10px;">Erase All User Data</h2>';
	$echo_str .= "\n\t".'<form enctype="multipart/form-data" name="erase_userdata" id="erase_userdata" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="validate">';
	$nonce = wp_nonce_field('erase_userdata', '_wpnonce', false, false);
	$nonce_ref = wp_referer_field(false);
	$echo_str .= "\n\t\t".$nonce;
	$echo_str .= "\n\t\t".$nonce_ref;
	$echo_str .= "\n\t\t".'<input type="hidden" name="action" value="erase_userdata">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="username" value="'.$username.'">';
	$echo_str .= "\n\t\t".'<label>Are you sure that you want to erase all data of user <strong>'.$user->display_name.' ('.$username.')</strong>? </label><br/>';
	$echo_str .= "\n\t\t".'<p class="submit">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Yes">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Cancel">';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t".'</form>';
	$echo_str .= "\n".'</div>';
	return $echo_str;
}

function wfu_erase_userdata($username) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $wpdb;

	if ( !current_user_can( 'manage_options' ) ) return -1;
	if ( !check_admin_referer('erase_userdata') ) return -1;
	$user = get_user_by('login', $username);
	if ( $user->ID == 0 ) return -1;

	
	$count = -1;
	if ( isset($_POST['submit']) && $_POST['submit'] == "Yes" ) {
		$table_name1 = $wpdb->prefix . "wfu_log";
		$table_name2 = $wpdb->prefix . "wfu_userdata";
		$table_name3 = $wpdb->prefix . "wfu_dbxqueue";

		$count = $wpdb->query("DELETE FROM $table_name2 WHERE uploadid in (SELECT uploadid FROM $table_name1 WHERE uploaduserid = ".$user->ID.")");
		$count += $wpdb->query("UPDATE $table_name1 SET uploaduserid = 0 WHERE uploaduserid = ".$user->ID);
	}
	
	return $count;
}

?>