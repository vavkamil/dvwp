<?php

/**
 * Shortcode Composer Page
 *
 * This file contains functions related to the shortcode composer page of the
 * plugin. The shortcode composer is a visual editor of the plugin's shortcodes
 * so that they can be configured easily by administrators.
 *
 * @link /lib/wfu_admin_composer.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 2.4.1
 */

/**
 * Display the Shortcode Composer.
 *
 * This function displays the shortcode composer for a specific shortcode.
 *
 * @since 2.1.2
 *
 * @param string|array $data Optional. If this function was called for an
 *        existing shortcode, this param holds data of the shortcode. If it was
 *        called for a new shortcode, it contains an empty string.
 * @param string $shortcode_tag Optional. The shortcode tag.
 * @param string $referer Optional. The page that called this function.
 *
 * @return string The HTML output of the shortcode composer.
 */
function wfu_shortcode_composer($data = '', $shortcode_tag = 'wordpress_file_upload', $referer = 'page') {
	global $wp_roles;
	$siteurl = site_url();
	
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$components = wfu_component_definitions();
	if ( $shortcode_tag == 'wordpress_file_upload' ) {
		$plugin_title = "Uploader";
		$cats = wfu_category_definitions();
		$defs = wfu_attribute_definitions();
		//remove personaldata category if Personal Data are not activated in
		//plugin's Settings
		if ($plugin_options["personaldata"] != "1" && isset($cats["personaldata"])) unset($cats["personaldata"]);
	}
	else {
		$plugin_title = "Browser";
		$cats = wfu_browser_category_definitions();
		$defs = wfu_browser_attribute_definitions();
	}
	
	if ( $data == "" ) {
		$shortcode = $plugin_options['shortcode'];
		$shortcode_full = '['.$shortcode_tag.' '.$shortcode.']';
		$postid = "";
		$postname = "";
		$posttype = "";
		$posthash = "";
		$shortcode_position = -1;
		$widgetid = "";
		$sidebar = "";
		$autosave = true;
	}
	else {
		$shortcode = trim(substr($data['shortcode'], strlen('['.$shortcode_tag), -1));
		$shortcode_full = $data['shortcode'];
		$postid = $data['post_id'];
		$postname = get_the_title($postid);
		$posttype_obj = get_post_type_object(get_post_type($postid));
		$posttype = ( $posttype_obj ? $posttype_obj->labels->singular_name : "" );
		$posthash = $data['post_hash'];
		$shortcode_position = $data['position'];
		$widgetid = ( isset($data['widgetid']) ? $data['widgetid'] : "" );
		$sidebar = ( isset($data['sidebar']) ? $data['sidebar'] : "" );
		$autosave = false;
	}
	
	// index $components
	$components_indexed = array();
	foreach ( $components as $component ) $components_indexed[$component['id']] = $component;
	// complete defs array and index dependencies
	$governors = array();
	$shortcode_attrs = wfu_shortcode_string_to_array($shortcode);
	//replace old attribute definitions with new ones
	$shortcode_attrs = wfu_old_to_new_attributes($shortcode_attrs);
	$shortcode_id = '';
	foreach ( $defs as $key => $def ) {
		$attr = $def['attribute'];
		$defs[$key]['default'] = $def['value'];
		//'flat' property keeps the original attribute, because 'attribute'
		//property will change for defs that their occurrence is higher than 1 
		$defs[$key]['flat'] = $attr;
		if ( array_key_exists($attr, $shortcode_attrs) ) $defs[$key]['value'] = $shortcode_attrs[$attr];
		$subblock_active = false;
		//detect if the dependencies of this attribute will be disabled or not
		if ( ( $def['type'] == "onoff" && $defs[$key]['value'] == "true" ) ||
			( $def['type'] == "radio" && in_array("*".$defs[$key]['value'], $def['listitems']) ) )
			$subblock_active = true;
		// assign dependencies if exist
		if ( $def['dependencies'] != null )
			foreach ( $def['dependencies'] as $dependency ) {
				if ( substr($dependency, 0, 1) == "!" ) //invert state for this dependency if an exclamation mark is defined
					$governors[substr($dependency, 1)] = array( 'attribute' => $attr, 'active' => !$subblock_active, 'inv' => '_inv' );
				else
					$governors[$dependency] = array( 'attribute' => $attr, 'active' => $subblock_active, 'inv' => '' );
			}
		if ( $attr == 'uploadid' || $attr == 'browserid' ) $shortcode_id = $defs[$key]['value'];
	}

	//check if attributes need to be generated more than once because their governor is a component field that appears more than once in placements attribute
	$key = 0;
	while ( $key < count($defs) ) {
		$defs[$key]['additional_values'] = array();
		$def = $defs[$key];
		$attr = $def['attribute'];
		//check if this attribute needs to be generated more than once
		if ( array_key_exists($attr, $governors) ) $governor = $governors[$attr]['attribute'];
		else $governor = "";
		if ( $governor != "" && isset($components_indexed[$governor]) && $components_indexed[$governor]['multiplacements'] && isset($shortcode_attrs['placements']) ) {
			//count how many occurrences of the governor attribute appear inside placements attribute
			$occurrences = 0;
			$sections = explode("/", $shortcode_attrs['placements']);
			foreach ( $sections as $section ) {
				$items_in_section = explode("+", trim($section));
				foreach ( $items_in_section as $item )
					if ( trim($item) == $governor ) $occurrences++;
			}
			//add indexed attributes if there is more than one occurrence
			for ( $ii = 2; $ii <= $occurrences; $ii++ ) {
				$def2 = $def;
				$def2['attribute'] .= $ii;
				$def2['name'] .= ' ('.$ii.')';
				if ( array_key_exists($def2['attribute'], $shortcode_attrs) )
					$def2['value'] = $shortcode_attrs[$def2['attribute']];
				else $def2['value'] = $def2['default'];
				array_splice($defs, $key + 1, 0, array($def2));
				$key++;
			}
			//check if the shortcode contains additional indexed definitions and store them in 'additional_values'
			$ii = max(1, $occurrences) + 1;
			while ( array_key_exists($attr.$ii, $shortcode_attrs) ) {
				$defs[$key]['additional_values'][$ii] = $shortcode_attrs[$attr.$ii];
				$ii++;
			}
		}
		$key++;
	}

	$echo_str = '<div id="wfu_wrapper" class="wrap">';
	$echo_str .= "\n\t".'<h2>Wordpress File Upload Control Panel</h2>';
	$echo_str .= "\n\t".'<div id="wfu_page_obsolete_message" class="error" style="display:none;">';
	$echo_str .= "\n\t\t".'<p>'.WFU_DASHBOARD_PAGE_OBSOLETE.'</p>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<div id="wfu_update_rejected_message" class="error" style="display:none;">';
	$echo_str .= "\n\t\t".'<p>'.WFU_DASHBOARD_UPDATE_SHORTCODE_REJECTED.'</p>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<div id="wfu_update_failed_message" class="error" style="display:none;">';
	$echo_str .= "\n\t\t".'<p>'.WFU_DASHBOARD_UPDATE_SHORTCODE_FAILED.'</p>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	if ( $referer == "dashboard" ) $echo_str .= "\n\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=manage_mainmenu" class="button" title="go back">Go to Main Menu</a>';
	$echo_str .= "\n\t".'</div>';
	if ( $widgetid == "" ) $echo_str .= "\n\t".'<h2 style="margin-bottom: 10px; margin-top: 20px;">'.( $data == "" ? 'Test' : $posttype.' <strong>'.$postname.'</strong>' ).': Shortcode Composer for '.$plugin_title.' <strong>ID '.$shortcode_id.'</strong></h2>';
	else $echo_str .= "\n\t".'<h2 style="margin-bottom: 10px; margin-top: 20px;">Sidebar <strong>'.$sidebar.'</strong>: Shortcode Composer for Uploader <strong>ID '.$shortcode_id.'</strong></h2>';
	$echo_str .= "\n\t".'<div style="margin-top:10px; display:inline-block;">';
	if ( $data != "") $echo_str .= "\n\t\t".'<input id="wfu_update_shortcode" type="button" value="Update" class="button-primary" disabled="disabled" onclick="wfu_save_shortcode()" /><span id="wfu_update_shortcode_wait" class="spinner" style="float:right; display:none;"></span>';
	$echo_str .= "\n\t\t".'<input id="wfu_shortcode_original_enc" type="hidden" value="'.wfu_plugin_encode_string($shortcode_full).'" />';
	$echo_str .= "\n\t\t".'<input id="wfu_shortcode_tag" type="hidden" value="'.$shortcode_tag.'" />';
	$echo_str .= "\n\t\t".'<input id="wfu_shortcode_postid" type="hidden" value="'.$postid.'" />';
	$echo_str .= "\n\t\t".'<input id="wfu_shortcode_posthash" type="hidden" value="'.$posthash.'" />';
	$echo_str .= "\n\t\t".'<input id="wfu_shortcode_position" type="hidden" value="'.$shortcode_position.'" />';
	$echo_str .= "\n\t\t".'<input id="wfu_shortcode_widgetid" type="hidden" value="'.$widgetid.'" />';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<div class="wfu_shortcode_container">';
	$echo_str .= "\n\t\t\t".'<span><strong>Generated Shortcode</strong></span>';
	$echo_str .= "\n\t\t\t".'<span id="wfu_save_label" class="wfu_save_label">saved</span>';
	$echo_str .= "\n\t\t\t".'<textarea id="wfu_shortcode" class="wfu_shortcode" rows="5">['.$shortcode_tag.']</textarea>';
	$echo_str .= "\n\t\t\t".'<div id="wfu_attribute_defaults" style="display:none;">';
	// remove hidden attributes from defs array
	foreach ( $defs as $key => $def ) if ( $def['type'] == "hidden" ) unset($defs[$key]);
	foreach ( $defs as $def )
		$echo_str .= "\n\t\t\t\t".'<input id="wfu_attribute_default_'.$def['attribute'].'" type="hidden" value="'.$def['default'].'" />';
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<div id="wfu_attribute_values" style="display:none;">';
	foreach ( $defs as $def ) {
		$echo_str .= "\n\t\t\t\t".'<input id="wfu_attribute_value_'.$def['attribute'].'" type="hidden" value="'.$def['value'].'" />';
		//add additional values, if exist
		foreach( $def['additional_values'] as $key => $val )
			$echo_str .= "\n\t\t\t\t".'<input id="wfu_attribute_value_'.$def['attribute'].$key.'" type="hidden" value="'.$val.'" />';
	}
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t".'</div>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h3 id="wfu_tab_container" class="nav-tab-wrapper">';
	$is_first = true;
	foreach ( $cats as $key => $cat ) {
		$echo_str .= "\n\t\t".'<a id="wfu_tab_'.$key.'" class="nav-tab'.( $is_first ? ' nav-tab-active' : '' ).'" href="javascript: wfu_admin_activate_tab(\''.$key.'\');">'.$cat.'</a>';
		$is_first = false;
	}
	$echo_str .= "\n\t".'</h3>';

	$prevcat = "";
	$prevsubcat = "";
	$is_first = true;
	$block_open = false;
	$subblock_open = false;
	foreach ( $defs as $def ) {
		$attr = $def['attribute'];
		//check if this attribute depends on other
		if ( !array_key_exists($attr, $governors) ) $governors[$attr] = "";
		if ( $governors[$attr] != "" ) $governor = $governors[$attr];
		else $governor = array( 'attribute' => "independent", 'active' => true, 'inv' => '' );

		//close previous blocks
		if ( $def['parent'] == "" ) {
			if ( $subblock_open ) {
				$echo_str .= "\n\t\t\t\t\t\t\t".'</tbody>';
				$echo_str .= "\n\t\t\t\t\t\t".'</table>';
				$subblock_open = false;
			}
			if ( $block_open ) {
				$echo_str .= "\n\t\t\t\t\t".'</div></td>';
				$echo_str .= "\n\t\t\t\t".'</tr>';
				$block_open = false;
			}
		}
		//check if new category must be generated
		if ( $def['category'] != $prevcat ) {
			if ( $prevcat != "" ) {
				$echo_str .= "\n\t\t\t".'</tbody>';
				$echo_str .= "\n\t\t".'</table>';
				$echo_str .= "\n\t".'</div>';
			}
			$prevcat = $def['category'];
			$prevsubcat = "";
			$echo_str .= "\n\t".'<div id="wfu_container_'.$prevcat.'" class="wfu_container"'.( $is_first ? '' : ' style="display:none;"' ).'">';
			$echo_str .= "\n\t\t".'<table class="form-table wfu_main_table">';
			$echo_str .= "\n\t\t\t".'<thead><tr><th></th><td></td><td></td></tr></thead>';
			$echo_str .= "\n\t\t\t".'<tbody>';
			$is_first = false;
		}
		//check if new sub-category must be generated
		if ( $def['subcategory'] != $prevsubcat ) {
			$prevsubcat = $def['subcategory'];
			$echo_str .= "\n\t\t\t\t".'<tr class="wfu_subcategory">';
			$echo_str .= "\n\t\t\t\t\t".'<th scope="row" colspan="3">';
			$echo_str .= "\n\t\t\t\t\t\t".'<h3 style="margin-bottom: 10px; margin-top: 10px;">'.$prevsubcat.'</h3>';
			$echo_str .= "\n\t\t\t\t\t".'</th>';
			$echo_str .= "\n\t\t\t\t".'</tr>';
		}
		//draw attribute element
		if ( $def['parent'] == "" ) {
			$dlp = "\n\t\t\t\t";
		}
		else {
			if ( !$subblock_open ) {
				$echo_str .= "\n\t\t\t\t\t\t".'<div class="wfu_shadow wfu_shadow_'.$def['parent'].$governor['inv'].'" style="display:'.( $governor['active'] ? 'none' : 'block' ).';"></div>';
				$echo_str .= "\n\t\t\t\t\t\t".'<table class="form-table wfu_inner_table" style="margin:0;">';
				$echo_str .= "\n\t\t\t\t\t\t\t".'<tbody>';
			}
			$dlp = "\n\t\t\t\t\t\t\t\t";
		}
		$echo_str .= $dlp.'<tr>';
		$echo_str .= $dlp."\t".'<th scope="row"><div class="wfu_td_div">';
		if ( $def['parent'] == "" ) $echo_str .= $dlp."\t\t".'<div class="wfu_shadow wfu_shadow_'.$governor['attribute'].$governor['inv'].'" style="display:'.( $governor['active'] ? 'none' : 'block' ).';"></div>';
		$echo_str .= $dlp."\t\t".'<div class="wfu_restore_container" title="Double-click to restore defaults setting"><img src="'.WFU_IMAGE_ADMIN_RESTOREDEFAULT.'" ondblclick="wfu_apply_value(\''.$attr.'\', \''.$def['type'].'\', \''.$def['default'].'\');" /></div>';
		$echo_str .= $dlp."\t\t".'<label for="wfu_attribute_'.$attr.'">'.$def['name'].'</label>';
		$echo_str .= $dlp."\t\t".'<input type="hidden" name="wfu_attribute_governor_'.$governor['attribute'].'" class="wfu_attribute_governor" value="'.$attr.'" />';
		$echo_str .= $dlp."\t\t".'<div class="wfu_help_container" title="'.$def['help'].'"><img src="'.WFU_IMAGE_ADMIN_HELP.'" /></div>';
		$echo_str .= $dlp."\t".'</div></th>';
		$echo_str .= $dlp."\t".'<td style="vertical-align:top;"><div class="wfu_td_div">';
		if ( $def['parent'] == "" ) $echo_str .= $dlp."\t\t".'<div class="wfu_shadow wfu_shadow_'.$governor['attribute'].$governor['inv'].'" style="display:'.( $governor['active'] ? 'none' : 'block' ).';"></div>';
		if ( $def['type'] == "onoff" ) {
			$echo_str .= $dlp."\t\t".'<div id="wfu_attribute_'.$attr.'" class="wfu_onoff_container_'.( $def['value'] == "true" ? "on" : "off" ).'" onclick="wfu_admin_onoff_clicked(\''.$attr.'\');">';
			$echo_str .= $dlp."\t\t\t".'<div class="wfu_onoff_slider"></div>';
			$echo_str .= $dlp."\t\t\t".'<span class="wfu_onoff_text">ON</span>';
			$echo_str .= $dlp."\t\t\t".'<span class="wfu_onoff_text">OFF</span>';
			$echo_str .= $dlp."\t\t".'</div>';
		}
		elseif ( $def['type'] == "text" ) {
			$val = str_replace(array( "%n%", "%dq%", "%brl%", "%brr%" ), array( "\n", "&quot;", "[", "]" ), $def['value']);
			$echo_str .= $dlp."\t\t".'<input id="wfu_attribute_'.$attr.'" type="text" name="wfu_text_elements" value="'.$val.'" style="display:block;" />';
			if ( $def['variables'] != null ) $echo_str .= $dlp.wfu_insert_variables($def['variables'], 'wfu_variable wfu_variable_'.$attr);
		}
		elseif ( $def['type'] == "placements" ) {
			$components_used = array();
			foreach ( $components as $component ) $components_used[$component['id']] = 0;
			$centered_content = '<div class="wfu_component_box_inner"><div class="wfu_component_box_label">XXX</div></div>';
			$centered_content_multi = '<div class="wfu_component_box_inner"><div class="wfu_component_box_label">XXX</div><div class="wfu_component_box_index">YYY</div></div>';
			$echo_str .= $dlp."\t\t".'<div class="wfu_placements_wrapper">';
			$echo_str .= $dlp."\t\t\t".'<div id="wfu_placements_container" class="wfu_placements_container">';
			$itemplaces = explode("/", $def['value']);
			foreach ( $itemplaces as $section ) {
				$echo_str .= $dlp."\t\t\t\t".'<div class="wfu_component_separator_hor"></div>';
				$echo_str .= $dlp."\t\t\t\t".'<div class="wfu_component_separator_ver"></div>';
				$items_in_section = explode("+", trim($section));
				$section_array = array( );
				foreach ( $items_in_section as $item_in_section ) {
					if ( key_exists($item_in_section, $components_indexed) ) {
						if ( $components_indexed[$item_in_section]['multiplacements'] || $components_used[$item_in_section] == 0 ) {
							$components_used[$item_in_section] ++;
							if ( $components_indexed[$item_in_section]['multiplacements'] ) {
								$multi_index = $components_used[$item_in_section];
								$echo_str .= $dlp."\t\t\t\t".'<div id="wfu_component_box_'.$item_in_section.'_'.$multi_index.'" class="wfu_component_box" draggable="true" title="'.$components_indexed[$item_in_section]['help'].'">'.str_replace(array("XXX", "YYY"), array($components_indexed[$item_in_section]['name'], $multi_index), $centered_content_multi).'</div>';
							}
							else
								$echo_str .= $dlp."\t\t\t\t".'<div id="wfu_component_box_'.$item_in_section.'_0" class="wfu_component_box" draggable="true" title="'.$components_indexed[$item_in_section]['help'].'">'.str_replace("XXX", $components_indexed[$item_in_section]['name'], $centered_content).'</div>';
							$echo_str .= $dlp."\t\t\t\t".'<div class="wfu_component_separator_ver"></div>';
						}
					}
				}
			}
			$echo_str .= $dlp."\t\t\t\t".'<div class="wfu_component_separator_hor"></div>';
			$echo_str .= $dlp."\t\t\t\t".'<div id="wfu_component_bar_hor" class="wfu_component_bar_hor"></div>';
			$echo_str .= $dlp."\t\t\t\t".'<div id="wfu_component_bar_ver" class="wfu_component_bar_ver"></div>';
			$echo_str .= $dlp."\t\t\t".'</div>';
			$echo_str .= $dlp."\t\t\t".'<div id="wfu_componentlist_container" class="wfu_componentlist_container">';
			$echo_str .= $dlp."\t\t\t\t".'<div id="wfu_componentlist_dragdrop" class="wfu_componentlist_dragdrop" style="display:none;"></div>';
			$ii = 1;
			foreach ( $components as $component ) {
				$echo_str .= $dlp."\t\t\t\t".'<div id="wfu_component_box_container_'.$component['id'].'" class="wfu_component_box_container">';
				$echo_str .= $dlp."\t\t\t\t\t".'<div class="wfu_component_box_base">'.str_replace("XXX", $component['name'], $centered_content).'</div>';
				if ( $component['multiplacements'] ) {
					$multi_index = $components_used[$component['id']] + 1;
					$echo_str .= $dlp."\t\t\t\t\t".'<div id="wfu_component_box_'.$component['id'].'_'.$multi_index.'" class="wfu_component_box wfu_inbase" draggable="true" title="'.$component['help'].'">'.str_replace(array("XXX", "YYY"), array($component['name'], $multi_index), $centered_content_multi).'</div>';
				}
				elseif ( $components_used[$component['id']] == 0 )
					$echo_str .= $dlp."\t\t\t\t\t".'<div id="wfu_component_box_'.$component['id'].'_0" class="wfu_component_box wfu_inbase" draggable="true" title="'.$component['help'].'">'.str_replace("XXX", $component['name'], $centered_content).'</div>';
				$echo_str .= $dlp."\t\t\t\t".'</div>'.( ($ii++) % 3 == 0 ? '<br />' : '' );
			}
			$echo_str .= $dlp."\t\t\t".'</div>';
			$echo_str .= $dlp."\t\t".'</div>';
		}
		elseif ( $def['type'] == "ltext" ) {
			$val = str_replace(array( "%n%", "%dq%", "%brl%", "%brr%" ), array( "\n", "&quot;", "[", "]" ), $def['value']);
			$echo_str .= $dlp."\t\t".'<input id="wfu_attribute_'.$attr.'" type="text" name="wfu_text_elements" class="wfu_long_text" value="'.$val.'" />';
			if ( $def['variables'] != null ) $echo_str .= $dlp.wfu_insert_variables($def['variables'], 'wfu_variable wfu_variable_'.$attr);
		}
		elseif ( $def['type'] == "integer" ) {
			$val = str_replace(array( "%n%", "%dq%", "%brl%", "%brr%" ), array( "\n", "&quot;", "[", "]" ), $def['value']);
			$echo_str .= $dlp."\t\t".'<input id="wfu_attribute_'.$attr.'" type="number" name="wfu_text_elements" class="wfu_short_text" min="1" value="'.$val.'" />';
			if ( isset($def['listitems']['unit']) ) $echo_str .= $dlp."\t\t".'<label> '.$def['listitems']['unit'].'</label>';
		}
		elseif ( $def['type'] == "float" ) {
			$val = str_replace(array( "%n%", "%dq%", "%brl%", "%brr%" ), array( "\n", "&quot;", "[", "]" ), $def['value']);
			$echo_str .= $dlp."\t\t".'<input id="wfu_attribute_'.$attr.'" type="number" name="wfu_text_elements" class="wfu_short_text" step="any" min="0" value="'.$val.'" />';
			if ( isset($def['listitems']['unit']) ) $echo_str .= $dlp."\t\t".'<label> '.$def['listitems']['unit'].'</label>';
		}
		elseif ( $def['type'] == "date" ) {
			$val = $def['value'];
			$echo_str .= $dlp."\t\t".'<div class="wfu_date_container"><input id="wfu_attribute_'.$attr.'" type="text" value="'.$val.'" readonly style="padding-right:16px; background-color:white; width:auto;" /><img class="wfu_datereset_button" src="'.WFU_IMAGE_ADMIN_SUBFOLDER_CANCEL.'" onclick="var f = document.getElementById(\'wfu_attribute_'.$attr.'\'); f.value = \'\'; wfu_update_date_value({target:f});" /></div><label style="font-size:smaller; margin-left:4px;">format: YYYY-MM-DD</label>';
			$echo_str .= wfu_inject_js_code('jQuery(function() {jQuery("#wfu_attribute_'.$attr.'").datepicker({dateFormat: "yy-mm-dd", onClose: function(date, picker) {wfu_update_date_value({target:this});}});});');
		}
		elseif ( $def['type'] == "radio" ) {
			$echo_str .= $dlp."\t\t";
			$ii = 0;
			foreach ( $def['listitems'] as $item )
				$echo_str .= '<input name="wfu_radioattribute_'.$attr.'" type="radio" value="'.$item.'" '.( $item == $def['value'] || $item == "*".$def['value'] ? 'checked="checked" ' : '' ).'style="width:auto; margin:0px 2px 0px '.( ($ii++) == 0 ? '0px' : '8px' ).';" onchange="wfu_admin_radio_clicked(\''.$attr.'\');" />'.( $item[0] == "*" ? substr($item, 1) : $item );
//			$echo_str .= '<input type="button" class="button" value="empty" style="width:auto; margin:-2px 0px 0px 8px;" />';
		}
		elseif ( $def['type'] == "ptext" ) {
			$val = str_replace(array( "%n%", "%dq%", "%brl%", "%brr%" ), array( "\n", "&quot;", "[", "]" ), $def['value']);
			$parts = explode("/", $val);
			$singular = $parts[0];
			if ( count($parts) < 2 ) $plural = $singular;
			else $plural = $parts[1];
			$echo_str .= $dlp."\t\t".'<span class="wfu_ptext_span">Singular</span><input id="wfu_attribute_s_'.$attr.'" type="text" name="wfu_ptext_elements" value="'.$singular.'" />';
			if ( $def['variables'] != null ) if ( count($def['variables']) > 0 ) $echo_str .= $dlp."\t\t".'<br /><span class="wfu_ptext_span">&nbsp;</span>';
			if ( $def['variables'] != null ) $echo_str .= $dlp.wfu_insert_variables($def['variables'], 'wfu_variable wfu_variable_s_'.$attr);
			$echo_str .= $dlp."\t\t".'<br /><span class="wfu_ptext_span">Plural</span><input id="wfu_attribute_p_'.$attr.'" type="text" name="wfu_ptext_elements" value="'.$plural.'" />';
			if ( $def['variables'] != null ) if ( count($def['variables']) > 0 ) $echo_str .= $dlp."\t\t".'<br /><span class="wfu_ptext_span">&nbsp;</span>';
			if ( $def['variables'] != null ) $echo_str .= $dlp.wfu_insert_variables($def['variables'], 'wfu_variable wfu_variable_p_'.$attr, $dlp);
		}
		elseif ( $def['type'] == "mtext" ) {
			$val = str_replace(array( "%n%", "%dq%", "%brl%", "%brr%" ), array( "\n", "&quot;", "[", "]" ), $def['value']);
			$echo_str .= $dlp."\t\t".'<textarea id="wfu_attribute_'.$attr.'" name="wfu_text_elements" rows="5">'.$val.'</textarea>';
			if ( $def['variables'] != null ) $echo_str .= $dlp.wfu_insert_variables($def['variables'], 'wfu_variable wfu_variable_'.$attr);
		}
		elseif ( $def['type'] == "ftpinfo" ) {
			$val = $def['value'];
			$ftpinfo = wfu_decode_ftpinfo($val);
			$error_class = ( $ftpinfo["error"] ? ' ftpinfo_error' : '' );
			$echo_str .= $dlp."\t\t".'<div class="ftpinfo_header">';
			$echo_str .= $dlp."\t\t\t".'<input id="wfu_attribute_'.$attr.'" type="text" name="wfu_ftpinfobase_elements" class="ftpinfo_text'.$error_class.'" value="'.$val.'" />';
			$echo_str .= $dlp."\t\t\t".'<button class="ftpinfo_btn" onclick="wfu_ftpinfotool_toggle();">Edit</button>';
			$echo_str .= $dlp."\t\t".'</div>';
			$echo_str .= $dlp."\t\t".'<div class="ftpinfo_tool hidden">';
			$echo_str .= $dlp."\t\t\t".'<label class="ftpinfo_label">Username</label><input type="text" id="ftpinfo_username" name="wfu_ftpinfotool_elements" class="ftpinfo_value'.$error_class.'" value="'.$ftpinfo["data"]["username"].'" /><br />';
			$echo_str .= $dlp."\t\t\t".'<label class="ftpinfo_label">Password</label><input type="text" id="ftpinfo_password" name="wfu_ftpinfotool_elements" class="ftpinfo_value'.$error_class.'" value="'.$ftpinfo["data"]["password"].'" /><br />';
			$echo_str .= $dlp."\t\t\t".'<label class="ftpinfo_label">FTP Domain</label><input type="text" id="ftpinfo_domain" name="wfu_ftpinfotool_elements" class="ftpinfo_value'.$error_class.'" value="'.$ftpinfo["data"]["ftpdomain"].'" /><br />';
			$echo_str .= $dlp."\t\t\t".'<label class="ftpinfo_label">Port</label><input type="text" id="ftpinfo_port" name="wfu_ftpinfotool_elements" class="ftpinfo_value'.$error_class.'" value="'.$ftpinfo["data"]["port"].'" /><br />';
			$echo_str .= $dlp."\t\t\t".'<label class="ftpinfo_label">Use SFTP</label><input type="checkbox" id="ftpinfo_sftp" name="wfu_ftpinfotool_elements" class="ftpinfo_checkbox'.$error_class.'"'.( $ftpinfo["data"]["sftp"] ? " checked" : "" ).' />';
			$echo_str .= $dlp."\t\t".'</div>';
			if ( $def['variables'] != null ) $echo_str .= $dlp.wfu_insert_variables($def['variables'], 'wfu_variable wfu_variable_'.$attr);
		}
		elseif ( $def['type'] == "folderlist" ) {
			$echo_str .= $dlp."\t\t".'<div id="wfu_subfolders_inner_shadow_'.$attr.'" class="wfu_subfolders_inner_shadow" style="display:none;"></div>';
			$subfolders = wfu_parse_folderlist($def['value']);
			$poptitle = "Populate list automatically with the first-level subfolders of the path defined in uploadpath";
			$edittitle = "Allow the user to type the subfolder and filter the list during typing";
			$echo_str .= $dlp."\t\t".'<input type="checkbox" id="wfu_subfolders_auto_'.$attr.'"'.( substr($def['value'], 0, 4) == "auto" ? ' checked="checked"' : '' ).' onchange="wfu_subfolders_auto_changed(\''.$attr.'\');" title="'.$poptitle.'" /><label for="wfu_subfolders_auto_'.$attr.'" title="'.$poptitle.'"> Auto-populate list</label>';
			$echo_str .= $dlp."\t\t".'<div style="display:'.( substr($def['value'], 0, 4) == "auto" ? 'inline' : 'none' ).'; padding:0; margin:0 0 0 30px; background:none; border:none;"><input type="checkbox" id="wfu_subfolders_editable_'.$attr.'"'.( substr($def['value'], 0, 5) == "auto+" ? ' checked="checked"' : '' ).' onchange="wfu_subfolders_auto_changed(\''.$attr.'\');" title="'.$edittitle.'" /><label for="wfu_subfolders_editable_'.$attr.'" title="'.$edittitle.'"> List is editable</label></div><br />';
			$echo_str .= $dlp."\t\t".'<input type="hidden" id="wfu_subfolders_manualtext_'.$attr.'" value="'.( substr($def['value'], 0, 4) == "auto" ? "" : $def['value'] ).'" />';
			$echo_str .= $dlp."\t\t".'<select id="wfu_attribute_'.$attr.'" class="wfu_select_folders'.( count($subfolders['path']) == 0 ? ' wfu_select_folders_empty' : '' ).'" size="7"'.( substr($def['value'], 0, 4) == "auto" ? ' disabled="disabled"' : '' ).' onchange="wfu_subfolders_changed(\''.$attr.'\');">';
			foreach ($subfolders['path'] as $ind => $subfolder) {
				if ( substr($subfolder, -1) == '/' ) $subfolder = substr($subfolder, 0, -1);
				$subfolder_raw = explode('/', $subfolder);
				$subfolder = $subfolder_raw[count($subfolder_raw) - 1];
				$text = str_repeat("&nbsp;&nbsp;&nbsp;", intval($subfolders['level'][$ind])).$subfolders['label'][$ind];
				$subvalue = str_repeat("*", intval($subfolders['level'][$ind])).( $subfolders['default'][$ind] ? '&' : '' ).( $subfolder == "" ? '{root}' : $subfolder ).'/'.$subfolders['label'][$ind];
				$echo_str .= $dlp."\t\t\t".'<option class="'.( $subfolders['default'][$ind] ? 'wfu_select_folders_option_default' : '' ).'" value="'.wfu_plugin_encode_string($subvalue).'">'.$text.'</option>';
			}
			$echo_str .= $dlp."\t\t\t".'<option value="">'.( substr($def['value'], 0, 4) != "auto" && count($subfolders['path']) == 0 ? 'press here' : '' ).'</option>';
			$echo_str .= $dlp."\t\t".'</select>';
			$echo_str .= $dlp."\t\t".'<div id="wfu_subfolder_nav_'.$attr.'" class="wfu_subfolder_nav_container">';
			$echo_str .= $dlp."\t\t\t".'<table class="wfu_subfolder_nav"><tbody>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td><button id="wfu_subfolders_up_'.$attr.'" name="wfu_subfolder_nav_'.$attr.'" class="button" disabled="disabled" title="move item up" onclick="wfu_subfolders_up_clicked(\''.$attr.'\');">&uarr;</button></tr></td>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td><button id="wfu_subfolders_left_'.$attr.'" name="wfu_subfolder_nav_'.$attr.'" class="button" title="make it parent" disabled="disabled" style="height:14px;" onclick="wfu_subfolders_left_clicked(\''.$attr.'\');">&larr;</button>';
			$echo_str .= $dlp."\t\t\t\t".'<button id="wfu_subfolders_right_'.$attr.'" name="wfu_subfolder_nav_'.$attr.'" class="button" title="make it child" disabled="disabled" style="height:14px;" onclick="wfu_subfolders_right_clicked(\''.$attr.'\');">&rarr;</button></tr></td>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td><button id="wfu_subfolders_down_'.$attr.'" name="wfu_subfolder_nav_'.$attr.'" class="button" title="move item down" disabled="disabled" onclick="wfu_subfolders_down_clicked(\''.$attr.'\');">&darr;</button></tr></td>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td style="line-height:0;"><button  class="button" style="visibility:hidden; height:10px;"></button></tr></td>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td><button id="wfu_subfolders_add_'.$attr.'" name="wfu_subfolder_nav_'.$attr.'" class="button" title="add new item" disabled="disabled" style="height:14px;" onclick="wfu_subfolders_add_clicked(\''.$attr.'\');">+</button></tr></td>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td><button id="wfu_subfolders_def_'.$attr.'" name="wfu_subfolder_nav_'.$attr.'" class="button" title="make it default" disabled="disabled" style="height:14px;" onclick="wfu_subfolders_def_clicked(\''.$attr.'\');">&diams;</button></tr></td>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td><button id="wfu_subfolders_del_'.$attr.'" name="wfu_subfolder_nav_'.$attr.'" class="button" title="delete item" disabled="disabled" style="height:14px;" onclick="wfu_subfolders_del_clicked(\''.$attr.'\');">-</button></tr></td>';
			$echo_str .= $dlp."\t\t\t".'</tbody></table>';
			$echo_str .= $dlp."\t\t".'</div>';
			$echo_str .= $dlp."\t\t".'<div id="wfu_subfolder_tools_'.$attr.'" class="wfu_subfolder_tools_container wfu_subfolder_tools_disabled">';
			$echo_str .= $dlp."\t\t\t".'<table class="wfu_subfolder_tools"><tbody><tr>';
			$echo_str .= $dlp."\t\t\t\t".'<td style="width:40%;">';
			$echo_str .= $dlp."\t\t\t\t\t".'<label>Label</label>';
			$echo_str .= $dlp."\t\t\t\t\t".'<input id="wfu_subfolders_label_'.$attr.'" name="wfu_subfolder_tools_input" type="text" disabled="disabled" />';
			$echo_str .= $dlp."\t\t\t\t".'</td>';
			$echo_str .= $dlp."\t\t\t\t".'<td style="width:60%;"><div style="padding-right:36px;">';
			$echo_str .= $dlp."\t\t\t\t\t".'<label>Path</label>';
			$echo_str .= $dlp."\t\t\t\t\t".'<input id="wfu_subfolders_path_'.$attr.'" name="wfu_subfolder_tools_input" type="text" disabled="disabled" />';
			$echo_str .= $dlp."\t\t\t\t\t".'<button id="wfu_subfolders_browse_'.$attr.'" class="button" title="browse folders" style="right:18px;" disabled="disabled" onclick="wfu_subfolders_browse_clicked(\''.$attr.'\');"><img src="'.WFU_IMAGE_ADMIN_SUBFOLDER_BROWSE.'" ></button>';
			$echo_str .= $dlp."\t\t\t\t\t".'<button id="wfu_subfolders_ok_'.$attr.'" class="button" title="save changes" style="right:0px;" disabled="disabled" onclick="wfu_subfolders_ok_clicked(\''.$attr.'\');"><img src="'.WFU_IMAGE_ADMIN_SUBFOLDER_OK.'" ></button>';
			// file browser dialog
			$echo_str .= $dlp."\t\t\t\t\t".'<div id="wfu_subfolders_browser_'.$attr.'" class="wfu_subfolders_browser_container" style="display:none;">';
			$echo_str .= $dlp."\t\t\t\t\t\t".'<table><tbody>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t".'<tr><td style="height:15px;">';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t".'<div>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t".'<label>Folder Browser</label>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t".'<button class="button wfu_folder_browser_cancel" onclick="wfu_folder_browser_cancel_clicked(\''.$attr.'\');"><img src="'.WFU_IMAGE_ADMIN_SUBFOLDER_CANCEL.'" ></button>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t".'</div>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t".'</td></tr>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t".'<tr><td style="height:106px;">';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t".'<div>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t".'<select id="wfu_subfolders_browser_list_'.$attr.'" size="2" onchange="wfu_subfolders_browser_list_changed(\''.$attr.'\');">';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t\t".'<option>Value</option>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t\t".'<option>Value2</option>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t\t".'<option>Value3</option>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t".'</select>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t".'<div id="wfu_subfolders_browser_msgcont_'.$attr.'" class="wfu_folder_browser_loading_container" style="padding-top:40px;">';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t\t".'<label id="wfu_subfolders_browser_msg_'.$attr.'" style="margin-bottom:4px;">loading folder contents...</label>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t\t".'<img id="wfu_subfolders_browser_img_'.$attr.'" src="'.WFU_IMAGE_ADMIN_SUBFOLDER_LOADING.'" ></button>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t".'</div>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t".'</div>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t".'</td></tr>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t".'<tr><td align="right" style="height:15px;">';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t".'<div>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t".'<button class="button" onclick="wfu_folder_browser_cancel_clicked(\''.$attr.'\');">Cancel</button>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t\t".'<button id="wfu_subfolders_browser_ok_'.$attr.'" class="button">Ok</button>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t\t".'</div>';
			$echo_str .= $dlp."\t\t\t\t\t\t\t".'</td></tr>';
			$echo_str .= $dlp."\t\t\t\t\t\t".'</tbody></table>';
			$echo_str .= $dlp."\t\t\t\t\t".'</div>';

			$echo_str .= $dlp."\t\t\t\t".'</div></td>';
			$echo_str .= $dlp."\t\t\t".'</tr></tbody></table>';
			$echo_str .= $dlp."\t\t\t".'<input id="wfu_subfolders_isnewitem_'.$attr.'" type="hidden" value="" />';
			$echo_str .= $dlp."\t\t\t".'<input id="wfu_subfolders_newitemindex_'.$attr.'" type="hidden" value="" />';
			$echo_str .= $dlp."\t\t\t".'<input id="wfu_subfolders_newitemlevel_'.$attr.'" type="hidden" value="" />';
			$echo_str .= $dlp."\t\t\t".'<input id="wfu_subfolders_newitemlevel2_'.$attr.'" type="hidden" value="" />';
			$echo_str .= $dlp."\t\t".'</div>';
		}
		elseif ( $def['type'] == "mchecklist" ) {
			$help_count = 0;
			foreach ( $def['listitems'] as $key => $item ) {
				$parts = explode("/", $item);
				if ( count($parts) == 1 ) {
					$items[$key]['id'] = $item;
					$items[$key]['help'] = '';
				}
				else {
					$items[$key]['id'] = $parts[0];
					$items[$key]['help'] = $parts[1];
					$help_count ++;
				}
			}
			$def['value'] = strtolower($def['value']);
			if ( $def['value'] == "all" ) $selected = array();
			else $selected = explode(",", $def['value']);
			foreach ( $selected as $key => $item ) $selected[$key] = trim($item);
			$echo_str .= $dlp."\t\t".'<div id="wfu_attribute_'.$attr.'" class="wfu_mchecklist_container">';
			$is_first = true;
			foreach ( $items as $key => $item ) {
				if ( !$is_first ) $echo_str .= "<br />";
				$is_first = false;
				$echo_str .= $dlp."\t\t\t".'<div class="wfu_mchecklist_item"><input id="wfu_attribute_'.$attr.'_'.$key.'" type="checkbox"'.( $def['value'] == "all" || in_array($item['id'], $selected) ? ' checked="checked"' : '' ).( $def['value'] == "all" ? ' disabled="disabled"' : '' ).' onchange="wfu_update_mchecklist_value(\''.$attr.'\');" /><label for="wfu_attribute_'.$attr.'_'.$key.'">'.$item['id'].'</label>';
				if ( $item['help'] != '' ) $echo_str .= '<div class="wfu_help_container" title="'.$item['help'].'"><img src="'.WFU_IMAGE_ADMIN_HELP.'" /></div>';
				$echo_str .= '</div>';
			}
			$echo_str .= $dlp."\t\t".'</div>';
			$echo_str .= $dlp."\t\t".'<div id="wfu_attribute_'.$attr.'_optionhelp" class="wfu_help_container" title="" style="display:none; position:absolute;"><img src="'.WFU_IMAGE_ADMIN_HELP.'" style="visibility:visible;" /></div>';
			$echo_str .= $dlp."\t\t".'<div class="wfu_mchecklist_checkall"><input id="wfu_attribute_'.$attr.'_all" type="checkbox" onchange="wfu_update_mchecklist_value(\''.$attr.'\');"'.( $def['value'] == "all" ? ' checked="checked"' : '' ).' /> Select all</div>';
		}
		elseif ( $def['type'] == "rolelist" ) {
			$roles = $wp_roles->get_names();
			$selected = explode(",", $def['value']);
			$default_administrator = ( is_array($def['listitems']) && in_array('default_administrator', $def['listitems']) );
			if ( in_array('all', $selected) ) $rolesselected = ( $default_administrator ? array("administrator") : array( ) );
			else $rolesselected = $selected;
			foreach ( $selected as $key => $item ) $selected[$key] = trim($item);
			$echo_str .= $dlp."\t\t".'<table class="wfu_rolelist_container"><tbody><tr><td>';
			$echo_str .= $dlp."\t\t".'<select id="wfu_attribute_'.$attr.'" multiple="multiple" size="'.count($roles).'" onchange="wfu_update_rolelist_value(\''.$attr.'\');"'.( in_array('all', $selected) ? ' disabled="disabled"' : '' ).'>';
			foreach ( $roles as $roleid => $rolename )
				$echo_str .= $dlp."\t\t\t".'<option value="'.$roleid.'"'.( in_array($roleid, $rolesselected) ? ' selected="selected"' : '' ).'>'.$rolename.'</option>';
			$echo_str .= $dlp."\t\t".'</select>';
			$echo_str .= $dlp."\t\t".'</td><td>';
			$echo_str .= $dlp."\t\t".'<div class="wfu_rolelist_checkbtn"><input class="'.( $default_administrator ? 'wfu_default_administrator' : '' ).'" id="wfu_attribute_'.$attr.'_all" type="checkbox" onchange="wfu_update_rolelist_value(\''.$attr.'\');"'.( in_array('all', $selected) ? ' checked="checked"' : '' ).' /><label for="wfu_attribute_'.$attr.'_all"> Select all</label></div><br />';
			$echo_str .= $dlp."\t\t".'<div class="wfu_rolelist_checkbtn"><input id="wfu_attribute_'.$attr.'_guests" type="checkbox" onchange="wfu_update_rolelist_value(\''.$attr.'\');"'.( in_array("guests", $selected) ? ' checked="checked"' : '' ).' /><label for="wfu_attribute_'.$attr.'_guests"> Include guests</label></div>';
			$echo_str .= $dlp."\t\t".'</td></tr></tbody></table>';
		}
		elseif ( $def['type'] == "userlist" ) {
			$args = array();
			/** This filter is documented in lib/wfu_admin_browser.php */
			$args = apply_filters("_wfu_get_users", $args, "shortcode_composer");
			$users = get_users($args);
			$selected = explode(",", $def['value']);
			$default_0 = ( is_array($def['listitems']) && in_array('default_0', $def['listitems']) );
			if ( in_array('all', $selected) ) $usersselected = ( $default_0 ? array($users[0]->user_login) : array( ) );
			else $usersselected = $selected;
			$only_current = false;
			$echo_str .= $dlp."\t\t".'<table class="wfu_userlist_container"><tbody><tr>';
			if ( is_array($def['listitems']) && in_array('include_current', $def['listitems']) ) {
				$only_current = ( $def['value'] == 'current' );
				if ( $only_current ) $usersselected = ( $default_0 ? array($users[0]->user_login) : array( ) );
				$echo_str .= $dlp."\t\t".'<td colspan="2"><div class="wfu_userlist_checkbtn"><input id="wfu_attribute_'.$attr.'_current" type="checkbox" onchange="wfu_update_userlist_value(\''.$attr.'\');"'.( $only_current ? ' checked="checked"' : '' ).' /><label for="wfu_attribute_'.$attr.'_current"> Only From Current User</label></div>';
				$echo_str .= $dlp."\t\t".'</td></tr><tr>';
			}
			$echo_str .= $dlp."\t\t".'<td><select id="wfu_attribute_'.$attr.'" multiple="multiple" size="'.min(count($users), 10).'" onchange="wfu_update_userlist_value(\''.$attr.'\');"'.( $only_current || in_array('all', $selected) ? ' disabled="disabled"' : '' ).'>';
			foreach ( $users as $userid => $user )
				$echo_str .= $dlp."\t\t\t".'<option value="'.$user->user_login.'"'.( in_array($user->user_login, $usersselected) ? ' selected="selected"' : '' ).'>'.$user->display_name.' ('.$user->user_login.')</option>';
			$echo_str .= $dlp."\t\t".'</select>';
			$echo_str .= $dlp."\t\t".'</td><td>';
			$echo_str .= $dlp."\t\t".'<div class="wfu_userlist_checkbtn"><input class="'.( $default_0 ? 'wfu_default_0' : '' ).'" id="wfu_attribute_'.$attr.'_all" type="checkbox" onchange="wfu_update_userlist_value(\''.$attr.'\');"'.( in_array('all', $selected) ? ' checked="checked"' : '' ).( $only_current ? ' disabled="disabled"' : '' ).' /><label for="wfu_attribute_'.$attr.'_all"> Select all</label></div><br />';
			$echo_str .= $dlp."\t\t".'<div class="wfu_userlist_checkbtn"><input id="wfu_attribute_'.$attr.'_guests" type="checkbox" onchange="wfu_update_userlist_value(\''.$attr.'\');"'.( in_array("guests", $selected) ? ' checked="checked"' : '' ).( $only_current ? ' disabled="disabled"' : '' ).' /><label for="wfu_attribute_'.$attr.'_guests"> Include guests</label></div>';
			$echo_str .= $dlp."\t\t".'</td></tr></tbody></table>';
		}
		elseif ( $def['type'] == "postlist" ) {
			$processed = false;
			if ( is_array($def['listitems']) ) {
				$has_current = in_array('include_current', $def['listitems']);
				if ( $has_current ) unset($def['listitems'][array_search('include_current', $def['listitems'])]);
				foreach ( $def['listitems'] as $post_type ) {
					// if a post type cannot be found then we reset the list so that it is not processed at all
					if ( get_post_type_object( $post_type ) == null ) {
						$def['listitems'] = array();
						break;
					}
				}
				if ( count($def['listitems']) > 0 ) {
					$selected = explode(",", $def['value']);
					$only_current = false;
					$echo_str .= $dlp."\t\t".'<input id="wfu_attribute_'.$attr.'_postlist" type="hidden" value="'.implode(",", $def['listitems']).'" />';
					$echo_str .= $dlp."\t\t".'<table class="wfu_postlist_container"><tbody><tr>';
					if ( $has_current ) {
						$only_current = ( $def['value'] == 'current' );
						if ( $only_current ) $sselected = array();
						$echo_str .= $dlp."\t\t".'<td colspan="'.count($def['listitems']).'"><div class="wfu_postlist_checkbtn"><input id="wfu_attribute_'.$attr.'_current" type="checkbox" onchange="wfu_update_postlist_value(\''.$attr.'\');"'.( $only_current ? ' checked="checked"' : '' ).' /><label for="wfu_attribute_'.$attr.'_current"> Only From Current Post/Page</label></div>';
						$echo_str .= $dlp."\t\t".'</td></tr><tr>';
					}
					$postargs = array( 'post_type' => $def['listitems'], 'post_status' => "publish,private,draft", 'posts_per_page' => -1 );
					/** This filter is documented in lib/wfu_admin.php */
					$postargs = apply_filters("_wfu_get_posts", $postargs, "visual_editor");
					$posts = get_posts($postargs);
					$list = wfu_construct_post_list($posts);
					$td_width = (int)(100 / count($def['listitems']));
					foreach ( $def['listitems'] as $post_type ) {
						$flatlist = wfu_flatten_post_list($list[$post_type]);
						$postobj = get_post_type_object( $post_type );
						$echo_str .= $dlp."\t\t".'<td style="width:'.$td_width.'%;"><div class="wfu_postlist_header"><label>'.$postobj->label.'</label><div class="wfu_postlist_selectall"><input id="wfu_attribute_'.$attr.'_all_'.$post_type.'" type="checkbox" onchange="wfu_update_postlist_value(\''.$attr.'\');"'.( in_array('all', $selected) || in_array('all'.$post_type, $selected) ? ' checked="checked"' : '' ).( $only_current ? ' disabled="disabled"' : '' ).' /><label for="wfu_attribute_'.$attr.'_all_'.$post_type.'"> Select all</label></div></div>';
						$echo_str .= $dlp."\t\t".'<select id="wfu_attribute_'.$attr.'_'.$post_type.'" multiple="multiple" size="'.min(count($flatlist), 10).'" onchange="wfu_update_postlist_value(\''.$attr.'\');"'.( $only_current || in_array('all', $selected) || in_array('all'.$post_type, $selected) ? ' disabled="disabled"' : '' ).' style="width:100%; overflow:auto;">';
						foreach ( $flatlist as $item )
							$echo_str .= $dlp."\t\t\t".'<option value="'.$item['id'].'"'.( in_array($item['id'], $selected) ? ' selected="selected"' : '' ).'>'.str_repeat('&nbsp;', 4 * $item['level']).( $item['status'] == 1 ? '[Private]' : ( $item['status'] == 2 ? '[Draft]' : '' ) ).$item['title'].'</option>';
						$echo_str .= $dlp."\t\t".'</select></td>';
					}
					$echo_str .= $dlp."\t\t".'</tr></tbody></table>';
					$processed = true;
				}
			}
			if ( !$processed ) {
				$val = str_replace(array( "%n%", "%dq%", "%brl%", "%brr%" ), array( "\n", "&quot;", "[", "]" ), $def['value']);
				$echo_str .= $dlp."\t\t".'<input id="wfu_attribute_'.$attr.'" type="text" name="wfu_text_elements" value="'.$val.'" />';				
			}
		}
		elseif ( $def['type'] == "bloglist" ) {
			if ( function_exists('wp_get_sites') ) {
				$blogs = wp_get_sites( );
				$selected = explode(",", $def['value']);
				if ( in_array('all', $selected) ) $blogsselected = array( );
				else $blogsselected = $selected;
				$only_current = false;
				$echo_str .= $dlp."\t\t".'<table class="wfu_bloglist_container"><tbody><tr>';
				if ( is_array($def['listitems']) && in_array('include_current', $def['listitems']) ) {
					$only_current = ( $def['value'] == 'current' );
					if ( $only_current ) $blogsselected = array( );
					$echo_str .= $dlp."\t\t".'<td colspan="2"><div class="wfu_bloglist_checkbtn"><input id="wfu_attribute_'.$attr.'_current" type="checkbox" onchange="wfu_update_bloglist_value(\''.$attr.'\');"'.( $only_current ? ' checked="checked"' : '' ).' /><label for="wfu_attribute_'.$attr.'_current"> Only From Current Site</label></div>';
					$echo_str .= $dlp."\t\t".'</td></tr><tr>';
				}
				$echo_str .= $dlp."\t\t".'<td><select id="wfu_attribute_'.$attr.'" multiple="multiple" size="'.min(count($blogs), 10).'" onchange="wfu_update_bloglist_value(\''.$attr.'\');"'.( $only_current || in_array('all', $selected) ? ' disabled="disabled"' : '' ).'>';
				foreach ( $blogs as $blog )
					$echo_str .= $dlp."\t\t\t".'<option value="'.$blog->blog_id.'"'.( in_array($blog->blog_id, $blogsselected) ? ' selected="selected"' : '' ).'>'.$blog->path.'</option>';
				$echo_str .= $dlp."\t\t".'</select>';
				$echo_str .= $dlp."\t\t".'</td><td>';
				$echo_str .= $dlp."\t\t".'<div class="wfu_bloglist_checkbtn"><input id="wfu_attribute_'.$attr.'_all" type="checkbox" onchange="wfu_update_bloglist_value(\''.$attr.'\');"'.( in_array('all', $selected) ? ' checked="checked"' : '' ).( $only_current ? ' disabled="disabled"' : '' ).' /><label for="wfu_attribute_'.$attr.'_all"> Select all</label></div>';
				$echo_str .= $dlp."\t\t".'</td></tr></tbody></table>';
			}
			else {
				$val = str_replace(array( "%n%", "%dq%", "%brl%", "%brr%" ), array( "\n", "&quot;", "[", "]" ), $def['value']);
				$echo_str .= $dlp."\t\t".'<input id="wfu_attribute_'.$attr.'" type="text" name="wfu_text_elements" value="'.$val.'" />';				
			}
		}
		elseif ( $def['type'] == "stringmatch" ) {
			$matchfield = "";
			$matchcriterion = "equal to";
			$matchvalue = "";
			preg_match('/^field:(.*?);\s*criterion:(.*?)\s*;\s*value:(.*)$/', $def['value'], $matches);
			if ( count($matches) == 4 ) {
				$matchfield = $matches[1];
				$matchcriterion = $matches[2];
				$matchvalue = $matches[3];
			}
//			$echo_str .= $dlp."\t\t".'<div style="white-space:nowrap;">';
			$echo_str .= $dlp."\t\t".'<table class="wfu_stringmatch_container"><tbody><tr>';
			$echo_str .= $dlp."\t\t".'<td style="width:40%; white-space:nowrap;"><label>Field </label><input id="wfu_attribute_'.$attr.'_matchfield" type="text" name="wfu_stringmatch_elements" value="'.$matchfield.'" style="width:auto;" /></td>';
			$echo_str .= $dlp."\t\t".'<td style="width:30%;"><select id="wfu_attribute_'.$attr.'_matchcriterion" value="'.$matchcriterion.'" onchange="wfu_update_stringmatch_value(\''.$attr.'\');">';
			$echo_str .= $dlp."\t\t\t".'<option value="equal to"'.( $matchcriterion == "equal to" ? 'selected="selected"' : '' ).'>equal to</option>';
			$echo_str .= $dlp."\t\t\t".'<option value="starts with"'.( $matchcriterion == "starts with" ? 'selected="selected"' : '' ).'>starts with</option>';
			$echo_str .= $dlp."\t\t\t".'<option value="ends with"'.( $matchcriterion == "ends with" ? 'selected="selected"' : '' ).'>ends with</option>';
			$echo_str .= $dlp."\t\t\t".'<option value="contains"'.( $matchcriterion == "contains" ? 'selected="selected"' : '' ).'>contains</option>';
			$echo_str .= $dlp."\t\t\t".'<option value="not equal to"'.( $matchcriterion == "not equal to" ? 'selected="selected"' : '' ).'>not equal to</option>';
			$echo_str .= $dlp."\t\t\t".'<option value="does not start with"'.( $matchcriterion == "does not start with" ? 'selected="selected"' : '' ).'>does not start with</option>';
			$echo_str .= $dlp."\t\t\t".'<option value="does not end with"'.( $matchcriterion == "does not end with" ? 'selected="selected"' : '' ).'>does not end with</option>';
			$echo_str .= $dlp."\t\t\t".'<option value="does not contain"'.( $matchcriterion == "does not contain" ? 'selected="selected"' : '' ).'>does not contain</option>';
			$echo_str .= $dlp."\t\t".'</select></td>';
			$echo_str .= $dlp."\t\t".'<td style="width:30%;"><input id="wfu_attribute_'.$attr.'_matchvalue" type="text" name="wfu_stringmatch_elements" value="'.$matchvalue.'" style="width:auto;" /></td>';
			$echo_str .= $dlp."\t\t".'</tr></tbody></table>';
//			$echo_str .= $dlp."\t\t".'</div>';
		}
		elseif ( $def['type'] == "columns" ) {
			$selected = explode(",", $def['value']);
			if ( count($selected) == 1 && $selected[0] == "" ) $selected = array();
			$selected_flat = array();
			foreach ( $selected as $ind => $item ) $selected_flat[$ind] = preg_replace("/(:|\/).*$/", "", $item);
			$echo_str .= $dlp."\t".'<table class="wfu_columns_container"><tbody><tr>';
			$echo_str .= $dlp."\t\t\t".'<td style="width:45%;"><label class="wfu_columns_listtitle">Available Columns</label></td>';
			$echo_str .= $dlp."\t\t\t".'<td style="width:55%"><label class="wfu_columns_listtitle">Displayed Columns</label></td></tr><tr>';
			$echo_str .= $dlp."\t\t".'<td style="width:45%;">';
			$echo_str .= $dlp."\t\t\t".'<table class="wfu_columns_container" style="table-layout:fixed; width:100%;"><tbody><tr>';
			$echo_str .= $dlp."\t\t\t\t".'<td><select id="wfu_attribute_'.$attr.'_sourcelist" multiple="multiple" size="'.min(count($def['listitems']), 10).'" style="width:100%; overflow:auto;">';
			$itemprops = array();
			foreach ( $def['listitems'] as $item ) {
				$item_required = ( substr($item, 0, 1) == "*" );
				if ( $item_required ) $item = substr($item, 1);
				$item_parts = explode("/", $item, 3);
				$item_name = $item_parts[0];
				$item_label = "";
				$item_title = "";
				if ( count($item_parts) > 1 ) $item_label = $item_parts[1];
				if ( count($item_parts) == 3 ) $item_title = $item_parts[2];
				$item_parts = explode(":", $item_name, 2);
				$item_name = $item_parts[0];
				if ( count($item_parts) == 1 ) $item_sort = "";
				else $item_sort = $item_parts[1];
				if ( $item_label == "" ) $item_label = $item_name;
				if ( $item_title == "" ) $item_title = $item_label;
				$itemprops[$item_name] = array( 'label' => $item_label, 'title' => $item_title, 'required' => $item_required, 'sortable' => ( $item_name == "custom" || $item_sort != "" ), 'sorttype' => $item_sort );
				$val = $item_name.":".$item_sort."/".$item_title;
				$echo_str .= $dlp."\t\t\t\t\t".'<option value="'.$val.'"'.( $item_required ? ' class="wfu_columns_item_required"' : '' ).' onclick="wfu_columns_itemclicked(this, \''.$attr.'\');">'.$item_label.'</option>';
			}
			foreach ( $itemprops as $item_name => $prop )
				if ( $prop['required'] && !in_array($item_name, $selected_flat) )
					array_splice($selected, 0, 0, array( $item_name ));
			$selprops = array();
			foreach ( $selected as $item ) {
				$item_parts = explode("/", $item, 2);
				$item_name = $item_parts[0];
				if ( count($item_parts) == 1 ) $item_title = "";
				else $item_title = $item_parts[1];
				$item_parts = explode(":", $item_name, 2);
				$item_name = $item_parts[0];
				$flat_name = preg_replace("/^custom[0-9]+$/", "custom", $item_name);
				if ( $item_name != "custom" && isset($itemprops[$flat_name]) ) {
					$prop = $itemprops[$flat_name];
					if ( count($item_parts) == 1 ) $item_sort = ( $flat_name == "custom" ? "+-s" : ( $prop['sortable'] ? "-+".$prop['sorttype'] : "" ) );
					elseif ( $flat_name == "custom" ) $item_sort = "+".($item_parts[1] == "" ? "-s" : "+".$item_parts[1]);
					else $item_sort = ( $prop['sortable'] ? "-".($item_parts[1] == "" ? "-" : "+").$prop['sorttype'] : "" );
					if ( $item_title == "" ) $item_title = $prop['title'];
					array_push($selprops, array( 'name' => $item_name, 'label' => $prop['label'], 'title' => $item_title, 'required' => $prop['required'], 'sorttype' => $item_sort ));
				}
			}
			$echo_str .= $dlp."\t\t\t\t".'</select></td>';
			$echo_str .= $dlp."\t\t\t\t".'<td style="width:30px; padding:0 6px;"><button class="wfu_columns_addbutton" title="add column" onclick="wfu_columns_buttonaction(\''.$attr.'\', \'add\');" style="width:100%;">&gt;&gt;</button></td>';
			$echo_str .= $dlp."\t\t\t".'</tr></tbody></table>';
			$echo_str .= $dlp."\t\t".'</td>';
			$echo_str .= $dlp."\t\t".'<td style="width:55%">';
			$echo_str .= $dlp."\t\t\t".'<table class="wfu_columns_container" style="table-layout:fixed; width:100%;"><tbody><tr>';
			$echo_str .= $dlp."\t\t\t\t".'<td><select id="wfu_attribute_'.$attr.'" multiple="multiple" size="'.min(count($def['listitems']), 10).'" onchange="wfu_update_columns(\''.$attr.'\');" style="width:100%; overflow:auto;">';
			foreach ( $selprops as $prop ) {
				$val = $prop['name'].":".$prop['sorttype']."/".$prop['label']."/".$prop['title'];
				$echo_str .= $dlp."\t\t\t\t\t".'<option value="'.$val.'"'.( $prop['required'] ? ' class="wfu_columns_item_required"' : '' ).' onclick="wfu_columns_itemclicked(this, \''.$attr.'\');">'.$prop['label'].( $prop['title'] != "" && $prop['title'] != $prop['label'] ? " (".$prop['title'].")" : "" ).'</option>';
			}
			$echo_str .= $dlp."\t\t\t\t".'</select></td>';
			$echo_str .= $dlp."\t\t\t\t".'<td style="width:30px; padding:0 6px;">';
			$echo_str .= $dlp."\t\t\t\t\t".'<button class="wfu_columns_addbutton" title="move up" onclick="wfu_columns_buttonaction(\''.$attr.'\', \'up\');" style="width:100%;">&#8593;</button>';
			$echo_str .= $dlp."\t\t\t\t\t".'<button class="wfu_columns_addbutton" title="remove" onclick="wfu_columns_buttonaction(\''.$attr.'\', \'del\');" style="width:100%;">-</button>';
			$echo_str .= $dlp."\t\t\t\t\t".'<button class="wfu_columns_addbutton" title="move down" onclick="wfu_columns_buttonaction(\''.$attr.'\', \'down\');" style="width:100%;">&#8595;</button>';
			$echo_str .= $dlp."\t\t\t\t".'</td>';
			$echo_str .= $dlp."\t\t\t".'</tr></tbody></table>';
			$echo_str .= $dlp."\t\t\t".'<label class="wfu_columns_listtitle" style="margin-top:6px; display:block;">Column Properties</label>';
			$echo_str .= $dlp."\t\t\t".'<table id="wfu_attribute_'.$attr.'_columnprops_container" class="wfu_columnprops_container wfu_columnprops_container_disabled"><tbody>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td style="width:1%; padding-right:10px;"><label id="wfu_attribute_'.$attr.'_columnprops_title_label">Title</label></td>';
			$echo_str .= $dlp."\t\t\t\t".'<td><input type="text" id="wfu_attribute_'.$attr.'_columnprops_title" name="wfu_columnprops_elements" value="" style="width:100%;" disabled="disabled" /></td></tr>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td style="width:1%; padding-right:10px; white-space:nowrap;"><label id="wfu_attribute_'.$attr.'_columnprops_id_label">Field ID</label></td>';
			$echo_str .= $dlp."\t\t\t\t".'<td><input type="number" id="wfu_attribute_'.$attr.'_columnprops_id" name="wfu_columnprops_elements" min="1" value="" style="width:100%;" disabled="disabled" /></td></tr>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td colspan="2"><input type="checkbox" id="wfu_attribute_'.$attr.'_columnprops_sort" value="" onchange="wfu_columnprops_element_changed({target:this});" disabled="disabled" /><label id="wfu_attribute_'.$attr.'_columnprops_sort_label" for="wfu_attribute_'.$attr.'_columnprops_sort">Sortable</label></td></tr>';
			$echo_str .= $dlp."\t\t\t\t".'<tr><td style="width:1%; padding-right:10px;"><label id="wfu_attribute_'.$attr.'_columnprops_sorttype_label" style="white-space:nowrap;">Sort As</label></td>';
			$echo_str .= $dlp."\t\t\t\t".'<td><select id="wfu_attribute_'.$attr.'_columnprops_sorttype" value="" onchange="wfu_columnprops_element_changed({target:this});" disabled="disabled"><option value=""></option><option value="s">String</option><option value="n">Integer</option></select></td></tr>';
			$echo_str .= $dlp."\t\t\t".'</tbody></table>';
			$echo_str .= $dlp."\t\t".'</td>';
			$echo_str .= $dlp."\t".'</tr></tbody></table>';
			
		}
		elseif ( $def['type'] == "dimensions" ) {
			$vals_arr = explode(",", $def['value']);
			$vals = array();
			foreach ( $vals_arr as $val_raw ) {
				if ( trim($val_raw) != "" ) {
					list($val_id, $val) = explode(":", $val_raw);
					$vals[trim($val_id)] = trim($val);
				}
			}
			$dims = array();
			foreach ( $components as $comp ) {
				if ( $comp['dimensions'] == null ) $dims[$comp['id']] = $comp['name'];
				else foreach ( $comp['dimensions'] as $dimraw ) {
					list($dim_id, $dim_name) = explode("/", $dimraw);
					$dims[$dim_id] = $dim_name;
				}
			}
			foreach ( $dims as $dim_id => $dim_name ) {
				if ( !array_key_exists($dim_id, $vals) ) $vals[$dim_id] = "";
				$echo_str .= $dlp."\t\t".'<span style="display:inline-block; width:130px;">'.$dim_name.'</span><input id="wfu_attribute_'.$attr.'_'.$dim_id.'" type="text" name="wfu_dimension_elements_'.$attr.'" class="wfu_short_text" value="'.$vals[$dim_id].'" /><br />';
			}
		}
		elseif ( $def['type'] == "userfields" ) {
			$fields_arr = explode("/", $def['value']);
			$fields = array();
			foreach ( $fields_arr as $field_raw ) {
				$is_req = ( substr($field_raw, 0, 1) == "*" );
				if ( $is_req ) $field_raw = substr($field_raw, 1);
				if ( $field_raw != "" ) array_push($fields, array( "name" => $field_raw, "required" => $is_req ));
			}
			if ( count($fields) == 0 ) array_push($fields, array( "name" => "", "required" => false ));
			$echo_str .= $dlp."\t\t".'<div id="wfu_attribute_'.$attr.'" class="wfu_userdata_container">';
			foreach ( $fields as $field ) {
				$echo_str .= $dlp."\t\t\t".'<div class="wfu_userdata_line">';
				$echo_str .= $dlp."\t\t\t\t".'<input type="text" name="wfu_userfield_elements" value="'.$field['name'].'" />';
				$echo_str .= $dlp."\t\t\t\t".'<div class="wfu_userdata_action" onclick="wfu_userdata_add_field(this);"><img src="'.WFU_IMAGE_ADMIN_USERDATA_ADD.'" ></div>';
				$echo_str .= $dlp."\t\t\t\t".'<div class="wfu_userdata_action wfu_userdata_action_disabled" onclick="wfu_userdata_remove_field(this);"><img src="'.WFU_IMAGE_ADMIN_USERDATA_REMOVE.'" ></div>';
				$echo_str .= $dlp."\t\t\t\t".'<input type="checkbox"'.( $field['required'] ? 'checked="checked"' : '' ).' onchange="wfu_update_userfield_value({target:this});" />';
				$echo_str .= $dlp."\t\t\t\t".'<span>Required</span>';
				$echo_str .= $dlp."\t\t\t".'</div>';
			}
			$echo_str .= $dlp."\t\t".'</div>';
		}
		elseif ( $def['type'] == "formfields" ) {
			//find occurrence index of this attribute and total occrrence length
			$flat = $def['flat'];
			$attr_occur_index = 0;
			$attr_occur_length = 0;
			$all_attributes = array();
			foreach ( $defs as $def2 ) {
				if ( $def2['flat'] == $flat ) {
					$attr_occur_length ++;
					array_push($all_attributes, $def2['attribute']);
					if ( $def2['attribute'] == $attr ) $attr_occur_index = $attr_occur_length;
				}
			}
			//get field type definitions
			$fielddefs_array = $def['listitems'];
			foreach ( $fielddefs_array as $fielddef ) $fielddefs[$fielddef['type']] = $fielddef;
			//initialize editable field properties
			$fieldprops_basic = array('label', 'required', 'donotautocomplete', 'validate', 'typehook', 'labelposition', 'hintposition', 'default', 'data', 'group', 'format');
			$fieldprops_default = array ( "type" => "text", "label" => "", "labelposition" => "left", "required" => false, "donotautocomplete" => false, "validate" => false, "default" => "", "data" => "", "group" => "", "format" => "", "hintposition" => "right", "typehook" => false );
			//parse shortcode attribute to $fields
			$fields = wfu_parse_userdata_attribute($def['value']);
			$labelpositions = array("none", "top", "right", "bottom", "left", "placeholder");
			$hintpositions = array("none", "inline", "top", "right", "bottom", "left");
			if ( count($fields) == 0 ) array_push($fields, $fieldprops_default);
			//set html template variable
			$template = $dlp."\t\t\t\t".'<table class="wfu_formdata_props_table"><tbody>';
			$template .= $dlp."\t\t\t\t".'<tr><td colspan="2"><label class="wfu_formdata_label">Type</label><select id="wfu_formfield_[[key]]_type" value="[[t]]" onchange="wfu_formdata_type_changed(\'[[key]]\');">';
			foreach( $fielddefs as $item ) $template .= $dlp."\t\t\t\t\t".'<option value="'.$item['type'].'"[[type_'.$item['type'].'_selected]]>'.$item['type_description'].'</option>';
			$template .= $dlp."\t\t\t\t".'</select></td><td>';
			$template .= $dlp."\t\t\t\t".'<div class="wfu_formdata_action wfu_formdata_action_add" onclick="wfu_formdata_add_field(\'[[key]]\');"><img src="'.WFU_IMAGE_ADMIN_USERDATA_ADD.'" ></div>';
			$template .= $dlp."\t\t\t\t".'<div class="wfu_formdata_action wfu_formdata_action_remove[[remove_disabled]]" onclick="wfu_formdata_remove_field(\'[[key]]\');"><img src="'.WFU_IMAGE_ADMIN_USERDATA_REMOVE.'" ></div>';
			$template .= $dlp."\t\t\t\t".'<div class="wfu_formdata_action wfu_formdata_action_up[[up_disabled]]" onclick="wfu_formdata_move_field(\'[[key]]\', \'up\');"><img src="'.WFU_IMAGE_ADMIN_USERDATA_UP.'" ></div>';
			$template .= $dlp."\t\t\t\t".'<div class="wfu_formdata_action wfu_formdata_action_down[[down_disabled]]" onclick="wfu_formdata_move_field(\'[[key]]\', \'down\');"><img src="'.WFU_IMAGE_ADMIN_USERDATA_DOWN.'" ></div></td></tr>';
			$template .= $dlp."\t\t\t\t".'<tr><td class="wfu_formdata_props"><label class="wfu_formdata_label" title="[[label_hint]]">[[label_label]]</label></td><td><input type="text" id="wfu_formfield_[[key]]_label" name="wfu_formfield_elements" value="[[label]]" /></td><td></td></tr>';
			$labelpos_options = "";
			foreach ( $labelpositions as $pos ) $labelpos_options .= '<option value="'.$pos.'"[[labelposition_'.$pos.'_selected]]>'.$pos.'</option>';
			$template .= '[[S->]]'.$dlp."\t\t\t\t".'<tr><td class="wfu_formdata_props"><label class="wfu_formdata_labelposition" title="[[labelposition_hint]]">Label Position</label></td><td><select id="wfu_formfield_[[key]]_labelposition" value="[[s]]" title="[[labelposition_hint]]" onchange="wfu_update_formfield_value({target:this});">'.$labelpos_options.'</select></td><td></td></tr>[[<-S]]';
			$template .= '[[R->]]'.$dlp."\t\t\t\t".'<tr><td colspan="2" class="wfu_formdata_props"><input id="wfu_formfield_[[key]]_required" type="checkbox"[[r->]] checked="checked"[[<-r]] title="[[required_hint]]" onchange="wfu_update_formfield_value({target:this});" /><label for="wfu_formfield_[[key]]_required" title="[[required_hint]]"> Required</label></td><td></td></tr>[[<-R]]';
			$template .= '[[A->]]'.$dlp."\t\t\t\t".'<tr><td colspan="2" class="wfu_formdata_props"><input id="wfu_formfield_[[key]]_donotautocomplete" type="checkbox"[[a->]] checked="checked"[[<-a]] title="[[donotautocomplete_hint]]" onchange="wfu_update_formfield_value({target:this});" /><label for="wfu_formfield_[[key]]_donotautocomplete" title="[[donotautocomplete_hint]]"> Do not autocomplete</label></td><td></td></tr>[[<-A]]';
			$template .= '[[V->]]'.$dlp."\t\t\t\t".'<tr><td colspan="2" class="wfu_formdata_props"><input id="wfu_formfield_[[key]]_validate" type="checkbox"[[v->]] checked="checked"[[<-v]] title="[[validate_hint]]" onchange="wfu_update_formfield_value({target:this});" /><label for="wfu_formfield_[[key]]_validate" title="[[validate_hint]]"> Validate</label></td><td></td></tr>[[<-V]]';
			$hint_options = "";
			foreach ( $hintpositions as $pos ) $hint_options .= '<option value="'.$pos.'"[[hintposition_'.$pos.'_selected]]>'.$pos.'</option>';
			$template .= '[[P->]]'.$dlp."\t\t\t\t".'<tr><td class="wfu_formdata_props"><label class="wfu_formdata_label" title="[[hintposition_hint]]">Hint Position</label></td><td><select id="wfu_formfield_[[key]]_hintposition" value="[[p]]" title="[[hintposition_hint]]" onchange="wfu_update_formfield_value({target:this});">'.$hint_options.'</select></td><td></td></tr>[[<-P]]';
			$template .= '[[H->]]'.$dlp."\t\t\t\t".'<tr><td colspan="2" class="wfu_formdata_props"><input id="wfu_formfield_[[key]]_typehook" type="checkbox"[[h->]] checked="checked"[[<-h]] title="[[typehook_hint]]" onchange="wfu_update_formfield_value({target:this});" /><label for="wfu_formfield_[[key]]_typehook" title="[[typehook_hint]]"> Type hook</label></td><td></td></tr>[[<-H]]';
			$template .= '[[D->]]'.$dlp."\t\t\t\t".'<tr><td class="wfu_formdata_props"><label class="wfu_formdata_label" title="[[default_hint]]">Default</label></td><td><input id="wfu_formfield_[[key]]_default" type="text" name="wfu_formfield_elements" value="[[d]]" title="[[default_hint]]" /></td><td></td></tr>[[<-D]]';
			$template .= '[[L->]]'.$dlp."\t\t\t\t".'<tr><td class="wfu_formdata_props"><label class="wfu_formdata_label" title="[[data_hint]]">[[data_label]]</label></td><td><input id="wfu_formfield_[[key]]_data" type="text" name="wfu_formfield_elements" value="[[l]]" title="[[data_hint]]" /></td><td></td></tr>[[<-L]]';
			$template .= '[[G->]]'.$dlp."\t\t\t\t".'<tr><td class="wfu_formdata_props"><label class="wfu_formdata_label" title="[[group_hint]]">Group ID</label></td><td><input id="wfu_formfield_[[key]]_group" type="text" name="wfu_formfield_elements" value="[[g]]" title="[[group_hint]]" /></td><td></td></tr>[[<-G]]';
			$template .= '[[F->]]'.$dlp."\t\t\t\t".'<tr><td class="wfu_formdata_props"><label class="wfu_formdata_label" title="[[format_hint]]">Format</label></td><td><input id="wfu_formfield_[[key]]_format" type="text" name="wfu_formfield_elements" value="[[f]]" title="[[format_hint]]" /></td><td></td></tr>[[<-F]]';
			$template .= $dlp."\t\t\t\t".'</tbody></table>';
			//draw html elements
			$echo_str .= $dlp."\t\t".'<div id="wfu_attribute_'.$attr.'" class="wfu_formdata_container">';
			$echo_str .= $dlp."\t\t\t".'<input type="hidden" class="wfu_formdata_all_attributes" value="'.implode(",", $all_attributes).'" />';
			$echo_str .= $dlp."\t\t\t".'<div id="wfu_attribute_'.$attr.'_codeadd" style="display:none;">';
			//pass template and type props to client javascript variable and then erase the code
			$echo_str .= $dlp."\t\t\t\t".'<script type="text/javascript">';
			$echo_str .= $dlp."\t\t\t\t\t".'var wfu_attribute_'.$attr.'_formtemplate = "'.wfu_plugin_encode_string($template).'";';
			$echo_str .= $dlp."\t\t\t\t\t".'var wfu_attribute_'.$attr.'_typeprops = {};';
			$fielddef_array = array();
			foreach( $fielddefs as $item ) array_push($fielddef_array, $item['type']);
			//prepare storage of field definitions to browser context
			$echo_str .= $dlp."\t\t\t\t\t".'wfu_attribute_'.$attr.'_typeprops[0] = \''.implode(",", $fielddef_array).'\'';
			foreach( $fielddefs as $item ) {
				$typeprops = array();
				foreach ( $fieldprops_basic as $prop ) {
					array_push($typeprops, $prop.': \''.$item[$prop].'\'');
					array_push($typeprops, $prop.'_hint: \''.$item[$prop.'_hint'].'\'');
				}
				array_push($typeprops, 'label_label: \''.$item['label_label'].'\'');
				array_push($typeprops, 'data_label: \''.$item['data_label'].'\'');
				$echo_str .= $dlp."\t\t\t\t\t".'wfu_attribute_'.$attr.'_typeprops["'.$item['type'].'"] = {'.implode(", ", $typeprops).'};';
			}
			$echo_str .= $dlp."\t\t\t\t\t".'var self = document.getElementById("wfu_attribute_'.$attr.'_codeadd"); self.parentNode.removeChild(self);';
			$echo_str .= $dlp."\t\t\t\t".'</script>';
			$echo_str .= $dlp."\t\t\t".'</div>';
			$i = 1;
			foreach ( $fields as $field ) {
				$ind = wfu_create_random_string(4);
				$key = $attr."_".$ind;
				$fielddef = $fielddefs[$field["type"]];
				$echo_str .= $dlp."\t\t\t".'<div id="wfu_formfield_'.$key.'_container" class="wfu_formdata_line_container">';
				//generate html elements from template, replacing variables where applicable
				$from_template = str_replace(array('[[key]]', '[[t]]', '[[label]]', '[[s]]', '[[d]]', '[[l]]', '[[label_label]]', '[[data_label]]', '[[g]]', '[[f]]', '[[p]]'), array($key, $field['type'], $field['label'], $field['labelposition'], $field['default'], $field['data'], $fielddef['label_label'], $fielddef['data_label'], $field['group'], $field['format'], $field['hintposition']), $template);
				foreach ( $fieldprops_basic as $prop ) $from_template = str_replace('[['.$prop.'_hint]]', str_replace('\r\n', "\r\n", $fielddef[$prop.'_hint']), $from_template);
				foreach( $fielddefs as $item ) $from_template = str_replace('[[type_'.$item['type'].'_selected]]', ( $item['type'] == $field['type'] ? ' selected = "selected"' : '' ), $from_template);
				foreach( $labelpositions as $pos ) $from_template = str_replace('[[labelposition_'.$pos.'_selected]]', ( $pos == $field['labelposition'] ? ' selected = "selected"' : '' ), $from_template);
				foreach( $hintpositions as $pos ) $from_template = str_replace('[[hintposition_'.$pos.'_selected]]', ( $pos == $field['hintposition'] ? ' selected = "selected"' : '' ), $from_template);
				$from_template = str_replace('[[remove_disabled]]', ( count($fields) <= 1 ? ' wfu_formdata_action_disabled' : '' ), $from_template);
				$from_template = str_replace('[[up_disabled]]', ( ( $attr_occur_index == 1 && $i == 1 ) ? ' wfu_formdata_action_disabled' : '' ), $from_template);
				$from_template = str_replace('[[down_disabled]]', ( ( $attr_occur_index == $attr_occur_length && $i == count($fields) ) ? ' wfu_formdata_action_disabled' : '' ), $from_template);
				//adjust checkbox field values
				$from_template = preg_replace('/\[\[r\-\>\]\]'.( $field['required'] ? '|' : '.*' ).'\[\[\<\-r\]\]/', '', $from_template);
				$from_template = preg_replace('/\[\[a\-\>\]\]'.( $field['donotautocomplete'] ? '|' : '.*' ).'\[\[\<\-a\]\]/', '', $from_template);
				$from_template = preg_replace('/\[\[v\-\>\]\]'.( $field['validate'] ? '|' : '.*' ).'\[\[\<\-v\]\]/', '', $from_template);
				$from_template = preg_replace('/\[\[h\-\>\]\]'.( $field['typehook'] ? '|' : '.*' ).'\[\[\<\-h\]\]/', '', $from_template);
				//adjust visibility of properties
				$from_template = preg_replace('/\[\[S\-\>\]\]'.( substr($fielddef["labelposition"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-S\]\]/s', '', $from_template);
				$from_template = preg_replace('/\[\[R\-\>\]\]'.( substr($fielddef["required"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-R\]\]/s', '', $from_template);
				$from_template = preg_replace('/\[\[A\-\>\]\]'.( substr($fielddef["donotautocomplete"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-A\]\]/s', '', $from_template);
				$from_template = preg_replace('/\[\[V\-\>\]\]'.( substr($fielddef["validate"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-V\]\]/s', '', $from_template);
				$from_template = preg_replace('/\[\[P\-\>\]\]'.( substr($fielddef["hintposition"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-P\]\]/s', '', $from_template);
				$from_template = preg_replace('/\[\[H\-\>\]\]'.( substr($fielddef["typehook"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-H\]\]/s', '', $from_template);
				$from_template = preg_replace('/\[\[D\-\>\]\]'.( substr($fielddef["default"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-D\]\]/s', '', $from_template);
				$from_template = preg_replace('/\[\[L\-\>\]\]'.( substr($fielddef["data"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-L\]\]/s', '', $from_template);
				$from_template = preg_replace('/\[\[G\-\>\]\]'.( substr($fielddef["group"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-G\]\]/s', '', $from_template);
				$from_template = preg_replace('/\[\[F\-\>\]\]'.( substr($fielddef["format"], 0, 4) == "show" ? '|' : '.*' ).'\[\[\<\-F\]\]/s', '', $from_template);
				$echo_str .= $from_template;
				$echo_str .= $dlp."\t\t\t".'</div>';
				$i++;
			}
			$echo_str .= $dlp."\t\t".'</div>';
		}
		elseif ( $def['type'] == "color" ) {
			$val = str_replace(array( "%n%", "%dq%", "%brl%", "%brr%" ), array( "\n", "&quot;", "[", "]" ), $def['value']);
			$echo_str .= $dlp."\t\t".'<input id="wfu_attribute_'.$attr.'" type="text" name="wfu_text_elements" class="wfu_color_field" value="'.$val.'" />';
		}
		elseif ( $def['type'] == "color-triplet" ) {
			$triplet = explode(",", $def['value']);
			foreach ( $triplet as $key => $item ) $triplet[$key] = trim($item);
			if ( count($triplet) == 2 ) $triplet = array( $triplet[0], $triplet[1], "#000000");
			elseif ( count($triplet) == 1 ) $triplet = array( $triplet[0], "#FFFFFF", "#000000");
			elseif ( count($triplet) < 3 ) $triplet = array( "#000000", "#FFFFFF", "#000000");
			$echo_str .= $dlp."\t\t".'<div class="wfu_color_container"><label style="display:inline-block; width:120px; margin-top:-16px;">Text Color</label><input id="wfu_attribute_'.$attr.'_color" type="text" class="wfu_color_field" name="wfu_triplecolor_elements" value="'.$triplet[0].'" /></div>';
			$echo_str .= $dlp."\t\t".'<div class="wfu_color_container"><label style="display:inline-block; width:120px; margin-top:-16px;">Background Color</label><input id="wfu_attribute_'.$attr.'_bgcolor" type="text" class="wfu_color_field" name="wfu_triplecolor_elements" value="'.$triplet[1].'" /></div>';
			$echo_str .= $dlp."\t\t".'<div class="wfu_color_container"><label style="display:inline-block; width:120px; margin-top:-16px;">Border Color</label><input id="wfu_attribute_'.$attr.'_borcolor" type="text" class="wfu_color_field" name="wfu_triplecolor_elements" value="'.$triplet[2].'" /></div>';
		}
		else {
			$echo_str .= $dlp."\t\t".'<input id="wfu_attribute_'.$attr.'" type="text" name="wfu_text_elements" value="'.$def['value'].'" />';
			if ( $def['variables'] != null ) $echo_str .= $dlp.wfu_insert_variables($def['variables'], 'wfu_variable wfu_variable_'.$attr);
		}
		$echo_str .= $dlp."\t".'</div></td>';
		if ( $def['parent'] == "" ) {
			$echo_str .= $dlp."\t".'<td style="position:relative; vertical-align:top; padding:0;"><div class="wfu_td_div">';
			$block_open = false;
		}
		else {
			$echo_str .= $dlp.'</tr>';
			$subblock_open = true;						
		}
	}
	if ( $subblock_open ) {
		$echo_str .= "\n\t\t\t\t\t\t".'</div>';
	}
	if ( $block_open ) {
		$echo_str .= "\n\t\t\t\t\t".'</div></td>';
		$echo_str .= "\n\t\t\t\t".'</tr>';
	}
	$echo_str .= "\n\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t".'</table>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<div id="wfu_global_dialog_container" class="wfu_global_dialog_container">';
	$echo_str .= "\n\t".'</div>';
	$handler = 'function() { wfu_Attach_Admin_Events('.( $data == "" ? 'true' : 'false' ).'); }';
	$echo_str .= "\n\t".'<script type="text/javascript">if(window.addEventListener) { window.addEventListener("load", '.$handler.', false); } else if(window.attachEvent) { window.attachEvent("onload", '.$handler.'); } else { window["onload"] = '.$handler.'; }</script>';
	$echo_str .= "\n".'</div>';
//	$echo_str .= "\n\t".'<div style="margin-top:10px;">';
//	$echo_str .= "\n\t\t".'<label>Final shortcode text</label>';
//	$echo_str .= "\n\t".'</div>';

	echo $echo_str;
}

/**
 * Insert Variables in an Attribute.
 *
 * This function generates the HTML code of the variables that are shown below
 * the attribute which they refer to.
 *
 * @since 2.1.3
 *
 * @param array $variables. The array of variables to display below the
 *        attribute.
 * @param string $class A class name to set in the elements of the generated
 *        HTML code.
 *
 * @return string The HTML output of the variables.
 */
function wfu_insert_variables($variables, $class) {
	$ret = "";
	foreach ( $variables as $variable )
		if ( $variable == "%userdataXXX%" ) $ret .= "\t\t".'<select class="'.$class.'" name="wfu_formfield_select" title="'.constant("WFU_VARIABLE_TITLE_".strtoupper(str_replace("%", "", $variable))).'" onchange="wfu_insert_userfield_variable(this);"><option style="display:none;">%userdataXXX%</option></select>';
		elseif ( $variable != "%n%" && $variable != "%dq%" && $variable != "%brl%" && $variable != "%brr%" ) $ret .= "\t\t".'<span class="'.$class.'" title="'.constant("WFU_VARIABLE_TITLE_".strtoupper(str_replace("%", "", $variable))).'" ondblclick="wfu_insert_variable(this);">'.$variable.'</span>';
	return $ret;
}

?>
