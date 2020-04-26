<?php
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/
/*************************************************************
 * 
 * activities_log.class.php
 * 
 * Utility functions
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if(basename($_SERVER['SCRIPT_FILENAME']) == "activities_log.class.php"):
    exit;
endif;
class IWP_MMB_Activities_log {
	
	function __construct() {
		if(function_exists('add_action')) {
			add_action('core_upgrade_preamble',array( &$this, 'iwp_mmb_core_upgrade_preamble'));
			add_action('_core_updated_successfully',array( &$this, 'iwp_mmb_core_updated_successfully'), 1, 1); // It will call after "wordpress core updates via wp-admin or wp-cron" completed. It is available from wordpress 3.3.
			add_action('upgrader_process_complete', array( &$this, 'iwp_mmb_upgrader_process_complete'), 1, 2); // It is available from wordpress 3.7. It is for plugins upgrade, themes upgrade, plugins install and themes install.	
			add_action('automatic_updates_complete', array( &$this, 'iwp_mmb_automatic_updates_complete'), 10, 1); // It is available since wordpress 3.8. It is for automatic translation updates.
			add_action('updated_option', array( &$this, 'iwp_mmb_check_and_update_all_plugins_themes_history'), 10, 3);  
			add_action( 'init', array( &$this, 'iwp_mmb_register_custom_post_type' ),10,1,1 ); 
    		// add_action('sucuriscan_scheduled_scan', array( &$this, 'iwp_mmb_save_sucuri_activity_log'),99999); // We can use this action if sucuri implement schedule remote scan 

		}

		if(function_exists('add_filter')) {

			add_filter('update_theme_complete_actions', array( &$this, 'iwp_mmb_update_theme_complete_actions'), 1, 2); // It is available from wordpress 2.7 to 3.6.
			add_filter('update_bulk_theme_complete_actions', array( &$this, 'iwp_mmb_update_bulk_theme_complete_actions'), 1, 2); // It is available from wordpress 2.7 to 3.6.

			add_filter('async_update_translation', array( &$this, 'iwp_mmb_async_update_translation'), 1, 2); // why we added this hook? Because, whenever we tried to update the core, plugins and themes, translation updates automatically trigger. To prevent it, we added this line. It is available since wordpress 4.0.
			
			add_filter('update_translations_complete_actions', array( &$this, 'iwp_mmb_update_translations_complete_actions'), 10, 1); // It is available since wordpress 3.7.
			add_filter('upgrader_post_install', array( &$this, 'iwp_mmb_upgrader_post_install'), 10, 3); 
			// We couldn't get the error for failure translations updates (in wordpress 3.7 DE) when individual plugin updates happened. But the above line solved it.
			// Activities log for automatic translation updates wont work in wordpress 3.7. Because, wordpress 3.7 hasnt given any option to achieve it. But the above line solved it.

		}		
	}
	
	// whenever iwp client plugin updated also, it will call the following function for creating options like iwp_client_all_plugins_history, iwp_client_all_themes_history and iwp_client_wp_version_old.
	function iwp_mmb_save_options_for_activity_log($activity = '') {
		global $wp_version;
		
		// The following three lines are used for Client Reporting (Beta) - activities log.
		if(!get_option('iwp_client_all_plugins_history') || in_array($activity, array('update_client_plugin', 'install'))) {
			$this->iwp_mmb_update_all_plugins_history();
		}
		if(!get_option('iwp_client_all_themes_history') || in_array($activity, array('update_client_plugin', 'install'))) {
			$this->iwp_mmb_update_all_themes_history();
		}
		
		if(!get_option('iwp_client_wp_version_old') || in_array($activity, array('update_client_plugin', 'install'))) {
			update_option('iwp_client_wp_version_old',$wp_version); // It is mainly used when wp core auto updates happened.
		}		
	}
	
	function iwp_mmb_collect_backup_details($params) {
		global $iwp_activities_log_post_type;
		
		$user = get_user_by( 'login', $params['username'] );
		$userid = $user->data->ID;
		
		$details = array();
		$details['backup_name'] = isset($params['args']['backup_name']) ? $params['args']['backup_name'] : '';
		$details['limit'] = isset($params['args']['limit']) ? $params['args']['limit'] : '';
		$details['disable_comp'] = isset($params['args']['disable_comp']) ? $params['args']['disable_comp'] : '';
		$details['optimize_tables'] = isset($params['args']['optimize_tables']) ? $params['args']['optimize_tables'] : '';
		$details['what'] = isset($params['args']['what']) ? $params['args']['what'] : '';
		$details['exclude'] = isset($params['args']['exclude']) ? $params['args']['exclude'] : '';
		$details['exclude_extensions'] = isset($params['args']['exclude_extensions']) ? $params['args']['exclude_extensions'] : '';
		$details['exclude_file_size'] = isset($params['args']['exclude_file_size']) ? $params['args']['exclude_file_size'] : '';
		$details['include'] = isset($params['args']['include']) ? $params['args']['include'] : '';
		$details['mechanism'] = isset($params['mechanism']) ? $params['mechanism'] : '';					
		$details['fail_safe_files'] = isset($params['args']['fail_safe_files']) ? $params['args']['fail_safe_files'] : '';
		$details['fail_safe_db'] = isset($params['args']['fail_safe_db']) ? $params['args']['fail_safe_db'] : '';
		$details['del_host_file'] = isset($params['args']['del_host_file']) ? $params['args']['del_host_file'] : '';
		$details['backup_repo_type'] = isset($params['args']['backup_repo_type']) ? $params['args']['backup_repo_type'] : '';
		$details['when'] = isset($params['args']['when']) ? $params['args']['when'] : '';
		$details['at'] = isset($params['args']['at']) ? $params['args']['at'] : '';
		
		$this->iwp_mmb_save_iwp_activities(isset($params['args']['type'])?$params['args']['type']:'backup', isset($params['args']['action'])?$params['args']['action']:'now', $iwp_activities_log_post_type, (object)$details, $userid);
		
		unset($details);		
	}
	
	function iwp_mmb_save_iwp_activities($iwp_type, $iwp_action, $activities_type, $params, $userid) {
		global $wpdb,$iwp_activities_log_post_type, $wpdb;
		if(!$this->iwp_mmb_get_is_save_activity_log()) {
			return false;
		}
		
		$iwp_activities = array(
			'post_title'		=> uniqid( $iwp_activities_log_post_type.'_' ),
			'post_author'		=> $userid,
			'post_status'		=> 'publish',
			'post_type'			=> $iwp_activities_log_post_type
		);
		
		if(!empty($GLOBALS['activities_log_datetime'])) {
			$iwp_activities['post_date'] = $iwp_activities['post_date_gmt'] = $iwp_activities['post_modified'] = $iwp_activities['post_modified_gmt'] = $GLOBALS['activities_log_datetime'];
		}
		if (is_multisite() && in_array($iwp_action, array('now', 'schedule','multiCallNow'))) {
			$wpdb->set_blog_id(1);
		}
		$post_id = wp_insert_post( $iwp_activities );
		
		unset($iwp_activities);
		/* 
			meta keys 
			==========
			iwp_log_type
			iwp_log_action
			iwp_log_activities_type - i. iwp_activities_log ii. direct iii. automatic
			iwp_log_details
			iwp_log_actions - i. core-updated ii. plugins-updated iii. themes-updated iv. translations-updated
		*/
		$details = array();
		$actions = '';
		switch($iwp_action) {
			case 'update':
				switch($iwp_type) {
					case 'core':
						$details['old_version'] = $params->current_version;
						$details['updated_version'] = $params->current;	
						update_option('iwp_client_wp_version_old',$params->current);
					break;
					case 'plugins':
					case 'themes':
						$details['name'] = $params->name;
						$details['slug'] = $params->slug;
						$details['old_version'] = $params->old_version;
						$details['updated_version'] = $params->updated_version;						
					break;
					case 'translations':	
					break;
				}
				$actions = $iwp_type.'-updated';
			break;
			case 'now':
			case 'schedule':
			case 'multiCallNow':
				switch($iwp_type) {
					case 'backup':
					case 'scheduleBackup':
						$details = (array) $params;					
					break;
				}
				$actions = 'backups';
			break;
			case 'scan':
				$actions = 'sucuri';
				$details = serialize($params);
				break;
		}
		
		if(!function_exists('update_post_meta')) {
			require_once(ABSPATH.'wp-includes/post.php');
		}

		update_post_meta($post_id,$iwp_activities_log_post_type.'_type',$iwp_type);
		update_post_meta($post_id,$iwp_activities_log_post_type.'_action',$iwp_action);
		update_post_meta($post_id,$iwp_activities_log_post_type.'_activities_type',$activities_type);
		update_post_meta($post_id,$iwp_activities_log_post_type.'_actions',$actions);
		update_post_meta($post_id,$iwp_activities_log_post_type.'_details',$details);
		unset($details);
	}

	function iwp_mmb_get_is_save_activity_log() {
		return get_option('is_save_activity_log');
	}	
	
	function iwp_mmb_core_upgrade_preamble() {

	}

	function iwp_mmb_core_updated_successfully($new_version) {
		global $pagenow;
		
		$current = array();
		$current['current_version'] = get_option('iwp_client_wp_version_old');
		$current['current'] = $new_version;
		$activities_type = ('update-core.php' !== $pagenow)?'automatic':'direct';
		
		$userid = $this->iwp_mmb_get_current_user_id();
		
		$this->iwp_mmb_save_iwp_activities('core', 'update', $activities_type, (object)$current, $userid);
	}
	
	function iwp_mmb_get_current_user_id() {
		if(!function_exists('get_current_user_id')) {
			include_once (ABSPATH . 'wp-admin/includes/user.php');
		}
		return get_current_user_id();		
	}	

	function iwp_mmb_upgrader_process_complete($upgrader, $extra) {
		global $pagenow,$wp_version;
		
		if(version_compare($wp_version,'3.6','<=')) {
			return false;
		}

		$success = ! is_wp_error( $upgrader->skin->result );
		$error   = null;

		if ( ! $success ) {
			$errors = $upgrader->skin->result->errors;

			list( $error ) = reset( $errors );
		}

		// This would have failed down the road anyway
		if ( ! isset( $extra['type'] ) ) {
			return false;
		}

		$type   = $extra['type'];
		$action = $extra['action'];

		if ( ! in_array( $type, array( 'plugin', 'theme' ) ) ) {
			return false;
		}	
		
		if ( 'install' === $action ) {
			if ( 'plugin' === $type ) {
			} else { // theme
			}
		} elseif ( 'update' === $action ) {
			if(
				(
					'theme' === $type
				) 
				|| (
					'plugin' === $type 
					&& version_compare($wp_version,'3.7','>=') 
					&& version_compare($wp_version,'3.8','<')
					&& isset( $extra['bulk'] ) 
					&& true === $extra['bulk'] 
					&& isset($extra['themes'])
					&& is_array($extra['themes'])
					&& count($extra['themes'])
				) // In wordpress 3.7, it is behaving differently. Thats why, we have written this "or" condition.
			) { // theme
				if(isset($extra['theme']) && $extra['theme']) { // It is mainly used when wp cron themes updates happened.
					$slugs = array( $extra['theme'] );
				} else if ( isset( $extra['bulk'] ) && true === $extra['bulk'] ) {
					$slugs = $extra['themes'];
				} else {
					$slugs = array( $upgrader->skin->theme );
				}

				foreach ( $slugs as $slug ) {
					$this->iwp_mmb_collect_theme_details($slug);
				}			
			} else if ( 'plugin' === $type ) {
				if(isset($extra['plugin']) && $extra['plugin']) { // It is mainly used when wp cron plugins updates happened.
					$slugs = array( $extra['plugin'] );
				} else if ( isset( $extra['bulk'] ) && true === $extra['bulk'] ) {
					$slugs = $extra['plugins'];
				} else {
					$slugs = array( $upgrader->skin->plugin );
				}
				
				foreach ( $slugs as $slug ) {
					if($slug!='iwp-client/init.php') {
						$this->iwp_mmb_collect_plugin_details($slug);
					}
				}				
			} 
			unset($current);
		} else {
			return false;
		}
	}
	
	function iwp_mmb_collect_theme_details($theme_slug) {
		global $pagenow;

		$activities_type = (!in_array($pagenow,array('update.php','admin-ajax.php')))?'automatic':'direct';
		
		$theme = $this->iwp_mmb_get_theme_details($theme_slug);
		
		if(empty($theme) || !is_array($theme)) {
			return false;
		}
	
		$userid = $this->iwp_mmb_get_current_user_id();		
		
		$current = array();	
		
		$stylesheet  = $theme['Stylesheet Dir'] . '/style.css';
		
		if(!function_exists('get_file_data')) {
			require_once(ABSPATH.'wp-includes/functions.php');
		}
		
		$theme_data  = get_file_data( $stylesheet, array( 'Version' => 'Version' ) );
		$current['name']        		= $current['slug'] = $theme['Name']; // slug is used to get short description. Here theme name as slug.
		$current['updated_version']     = $theme_data['Version'];
		$current['old_version'] 		= $theme['Version'];
		
		$all_themes_history = get_option('iwp_client_all_themes_history');
		
		if(empty($current['name'])) {
			return false;
		}

		if(!empty($current['updated_version']) && !empty($current['old_version']) && version_compare($current['updated_version'],$current['old_version'],'==') && isset($all_themes_history) && isset($all_themes_history[$theme_slug]) && $all_themes_history[$theme_slug]) {
			$current['old_version'] = $all_themes_history[$theme_slug];
			$all_themes_history[$theme_slug] = $current['updated_version'];
			update_option('iwp_client_all_themes_history',$all_themes_history);
		}					

		if(!empty($current['updated_version']) && !empty($current['old_version']) && version_compare($current['updated_version'],$current['old_version'],'==')) {
			return false; 
		} // From wordpress 3.6 to lower versions, even though we got errors when we tried to update the themes, wordpress wont inform us about error via hooks. Thats why we have written this "if".

		$this->iwp_mmb_save_iwp_activities('themes','update',$activities_type,(object)$current,$userid);
		unset($current);
		return true;
	}

	function iwp_mmb_get_theme_details($theme_slug) {
		if(!function_exists('wp_get_theme')) {
			require_once(ABSPATH.'wp-includes/theme.php');
		}
		if(function_exists('wp_get_theme')) {
			$theme = wp_get_theme( $theme_slug );
		} else if(function_exists('get_theme_data') && file_exists(ABSPATH . 'wp-content/themes/'. $theme_slug . '/style.css')) {
			$theme = get_theme_data( ABSPATH . 'wp-content/themes/'. $theme_slug . '/style.css');
		} else {
			$theme = array();
		}
			
		return $theme;
	}

	function iwp_mmb_collect_plugin_details($plugin_file) {
		global $pagenow;
		
		$activities_type = (!in_array($pagenow,array('update.php','admin-ajax.php')))?'automatic':'direct';
	
		$userid = $this->iwp_mmb_get_current_user_id();		
		
		$current = array();	
		
		$_plugins = $this->iwp_mmb_get_all_plugin_details();
		
		if(!count($_plugins)) {
			return false;
		}
		
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
		$current['name']        		= isset($plugin_data['Name'])?$plugin_data['Name']:'';
		$current['slug']				= $plugin_file;
		$current['updated_version']     = isset($plugin_data['Version'])?$plugin_data['Version']:'';
		$current['old_version'] 		= isset($_plugins[ $plugin_file ]['Version'])?$_plugins[ $plugin_file ]['Version']:'';
		
		$all_plugins_history = get_option('iwp_client_all_plugins_history');

		if(!empty($current['updated_version']) && !empty($current['old_version']) && version_compare($current['updated_version'],$current['old_version'],'==') && isset($all_plugins_history) && isset($all_plugins_history[$plugin_file]) && $all_plugins_history[$plugin_file]) {
			$current['old_version'] = $all_plugins_history[$plugin_file];
			$all_plugins_history[$plugin_file] = $current['updated_version'];
			update_option('iwp_client_all_plugins_history',$all_plugins_history);
		}		
		
		if(!empty($current['updated_version']) && !empty($current['old_version']) && version_compare($current['updated_version'],$current['old_version'],'==')) {
			return false; 
		} // From wordpress 3.6 to lower versions, even though we got errors when we tried to update the plugins, wordpress wont inform us about error via hooks. Thats why we have written this "if".
		
		$this->iwp_mmb_save_iwp_activities('plugins','update',$activities_type,(object)$current,$userid);
		unset($current);
		return true;
	}

	function iwp_mmb_get_all_plugin_details() {
		
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}		
		$_plugins = get_plugins();
		if (empty($_plugins) || !is_array($_plugins)) {
			return array();
		}
		return $_plugins;
	}	

	function iwp_mmb_automatic_updates_complete($update_results) {
		if(empty($update_results['translation'])) {
			return false;
		}
		return $this->iwp_mmb_post_update_translations_complete_actions(array());
	}
	
	function iwp_mmb_post_update_translations_complete_actions($update_actions) {	
		global $pagenow,$iwp_client_plugin_translations,$iwp_client_plugin_ptc_updates,$wp_version,$iwp_activities_log_post_type;
		
		$activities_type = (!in_array($pagenow,array('update.php','update-core.php')))?'automatic':'direct';
		
		if(
			isset($iwp_client_plugin_ptc_updates) 
			&& $iwp_client_plugin_ptc_updates==1
		) {
			$activities_type = $iwp_activities_log_post_type;
		}
		
		$userid = $this->iwp_mmb_get_current_user_id();
		
		$details = array();
		$this->iwp_mmb_save_iwp_activities('translations', 'update', $activities_type, (object)$details, $userid);
		return $update_actions;
	}
	
	function iwp_mmb_check_and_update_all_plugins_themes_history($option, $old_value, $value) {
		if(in_array($option,array('_site_transient_update_plugins','_site_transient_update_themes'))) {

			$this->iwp_mmb_update_all_plugins_history();
			$this->iwp_mmb_update_all_themes_history();			
		}
	}
	
	function iwp_mmb_update_all_plugins_history() {
		$all_plugins = $this->get_all_plugins();
		unset($all_plugins['iwp-client/init.php']);
		$all_plugins_history = array();
		foreach($all_plugins as $key=>$plugin) {
			$all_plugins_history[$key] = $plugin['Version'];
		}
		
		update_option('iwp_client_all_plugins_history',$all_plugins_history);
		unset($all_plugins,$all_plugins_history);			
	}	
	
	function iwp_mmb_update_all_themes_history() {
		$all_themes = $this->get_all_themes();
		$all_themes_history = array();
		$theme_details = array();
		foreach($all_themes as $key=>$theme) {
			$theme_details = $this->iwp_mmb_get_theme_details($key);
			$all_themes_history[$key] = $theme_details->Version;
		}
		
		update_option('iwp_client_all_themes_history',$all_themes_history);
		unset($all_themes,$all_themes_history,$theme_details);		
	}

	function get_all_plugins() {
        if (!function_exists('get_plugins')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $all_plugins = get_plugins();
		return $all_plugins;		
	}

	function get_all_themes() {
        if (!function_exists('wp_get_themes')) {
            include_once(ABSPATH . WPINC . '/theme.php');
        }
        if(function_exists('wp_get_themes')){
	        $all_themes = wp_get_themes();	
	    }else{
			$all_themes = get_themes();	
		}
		return $all_themes;
	}	

	function iwp_mmb_update_theme_complete_actions($update_actions, $theme_slug) {
		global $wp_version;
		
		if(version_compare($wp_version,'3.7','>=') or empty($theme_slug)) {
			return $update_actions;
		}
			
		$this->iwp_mmb_collect_theme_details($theme_slug);		
		
		return $update_actions;		
	}	
	
	function iwp_mmb_update_bulk_theme_complete_actions($update_actions, $theme_info) {
		global $wp_version,$iwp_client_plugin_ptc_updates;
		
		if(
			(
				version_compare($wp_version,'3.7','>=')
			)
			or (
				isset($iwp_client_plugin_ptc_updates) 
				&& $iwp_client_plugin_ptc_updates==1
			)
		) {
			return $update_actions;
		}
				
		$theme_info = (array) $theme_info;
		$theme_slug = '';
		
		foreach($theme_info as $key=>$value) {
			if(isset($value['TextDomain']) && $value['TextDomain']) {
				$theme_slug = $value['TextDomain'];
				break;
			} else if(strstr($key,'stylesheet') || strstr($key,'template')) {
				$theme_slug = $value;
				break;				
			} else if($key=='Name') {
				$theme_slug = str_replace(array(' '),array(''),strtolower($value));
				break;
			}
		}
		
		if($theme_slug=='') {
			return $update_actions; 	
		}
			
		$this->iwp_mmb_collect_theme_details($theme_slug);
		
		return $update_actions;		
	}
	
	function iwp_mmb_async_update_translation($update, $language_update) {
		return false;
	}
	
	function iwp_mmb_update_translations_complete_actions($update_actions) {
		global $pagenow,$iwp_client_plugin_translations,$iwp_client_plugin_ptc_updates,$wp_version;

		if(
			(
				isset($iwp_client_plugin_translations) 
				&& $iwp_client_plugin_translations==1
			)
			or (
				version_compare($wp_version,'4.0','<')
			)
		) {
			return $update_actions;
		}
		
		return $this->iwp_mmb_post_update_translations_complete_actions($update_actions);
	}
	
	function iwp_mmb_upgrader_post_install($flag, $hook_extra, $result) {
		global $wp_version;

		if(
			isset($hook_extra['language_update_type']) 
			&& isset($hook_extra['language_update']) 
			&& is_object($hook_extra['language_update'])
		) {
			remove_filter('update_translations_complete_actions', array(&$this,'iwp_mmb_update_translations_complete_actions'));
			return $this->iwp_mmb_post_update_translations_complete_actions(array());
		}
		
		if(
			version_compare($wp_version,'3.6','<=')
			&& isset($hook_extra['plugin'])
			&& $hook_extra['plugin']
			&& strstr($hook_extra['plugin'],'.zip')===false
		) {
			$this->iwp_mmb_collect_plugin_details($hook_extra['plugin']);	
		}
		
		return $result;
	}
	
	function iwp_mmb_update_is_save_activity_log($is_save_activity_log) {
		if(isset($is_save_activity_log)) {
			update_option('is_save_activity_log', $is_save_activity_log);
		}
	}

	function iwp_mmb_process_and_fetch_activities_log($params) {
		global $wpdb,$iwp_activities_log_post_type;
		
		$updated_key = 'updated';
		$backups_key = 'backups';
		$count_key = 'count';
		$name_key = 'name';
		$date_key = 'date';
		$time_key = 'time';
		$type_key = 'type';
		$from_key = 'from';
		$to_key = 'to';
		$translations_updated = 'translations-updated';
		$sucuri = 'sucuri';
		$ithemesec = 'ithemesec';
		$wordfence = 'wordfence';
		
		if(
			!is_array($params['originalActions']) 
			|| !is_array($params['actions']) 
			|| !count($params['originalActions']) 
			|| !count($params['actions']) 
			|| empty($params['fromDate']) 
			|| empty($params['toDate'])
		) {
			iwp_mmb_response(array('error' => 'Invalid request', 'error_code' => 'invalid_request'), false);
		}

		$iwp_action = implode("','",$params['actions']);	

		$query = "
			select 
				p.ID as post_id, 
				p.post_date as date, 
				pm.meta_value as actions 
			from 
				{$wpdb->prefix}posts as p 
				left join {$wpdb->prefix}postmeta as pm on pm.post_id = p.ID 
			where 
				p.post_type = '".$iwp_activities_log_post_type."' 
				and unix_timestamp(p.post_date)>='".$params['fromDate']."' 
				and unix_timestamp(p.post_date)<='".$params['toDate']."' 
				and pm.meta_key in ('".$iwp_activities_log_post_type."_actions') 
				and pm.meta_value in ('".$iwp_action."')
			order by p.post_date asc
		";
		
		$activities_log_result = $wpdb->get_results($query,ARRAY_A);
		$return = array();
		$return['detailed'] = $params['detailed'];
		$return[$count_key] = array_map('iwp_make_values_as_zero',array_flip($params['actions']));		
		
		foreach($activities_log_result as $key=>$activities_log) {
			
			$date = date('M d, y',strtotime($activities_log['date']));
			$time = date('g',strtotime($activities_log['date'])).':'.date('i',strtotime($activities_log['date'])).' '.date('a',strtotime($activities_log['date']));
			
			$detailed_array = array(
				$date_key => $date,
				$time_key => $time			
			);
			
			$activities_log_details = get_post_meta($activities_log['post_id'],$iwp_activities_log_post_type.'_details',true);
			
			// The following lines are for CR New
			if($activities_log['actions']==$backups_key) {
				$return['detailed'][$activities_log['actions']]['details'][$return['detailed'][$activities_log['actions']][$count_key]] = $detailed_array;
				if($activities_log_details['what']=='full') {
					$backup_what_type = 'Files & DB';
				} else if($activities_log_details['what']=='files') {
					$backup_what_type = 'Files';
				} else {
					$backup_what_type = 'DB';
				}
				$return['detailed'][$activities_log['actions']]['details'][$return['detailed'][$activities_log['actions']][$count_key]][$type_key] = $backup_what_type;
				$return['detailed'][$activities_log['actions']][$count_key]++;
			} elseif($activities_log['actions']==$sucuri){
				$return['detailed'][$sucuri][$count_key]++;
				$return['detailed'][$sucuri]['details'][]=$activities_log_details;
			}else {
				
				$return['detailed'][$updated_key][$count_key]++;

				$return['detailed'][$updated_key][$activities_log['actions']]['details'][$return['detailed'][$updated_key][$activities_log['actions']][$count_key]] = $detailed_array;
				
				if($activities_log['actions']!=$translations_updated) {
					
					$name = str_replace(array($translations_updated,'s-updated','core-updated'),array(''),$activities_log['actions']);
					$what_updated = isset($activities_log_details['name'])?$activities_log_details['name']:'Wordpress Core Updates';
					
					$return['detailed'][$updated_key][$activities_log['actions']]['details'][$return['detailed'][$updated_key][$activities_log['actions']][$count_key]][$name.$name_key] = $what_updated;
					$return['detailed'][$updated_key][$activities_log['actions']]['details'][$return['detailed'][$updated_key][$activities_log['actions']][$count_key]][$from_key] = $activities_log_details['old_version'];
					$return['detailed'][$updated_key][$activities_log['actions']]['details'][$return['detailed'][$updated_key][$activities_log['actions']][$count_key]][$to_key] = $activities_log_details['updated_version'];
				}
				$return['detailed'][$updated_key][$activities_log['actions']][$count_key]++;				
			}
			// The above lines are for CR New
			$return[$count_key][$activities_log['actions']]++; // This line is for CR Old
		}
		foreach($return[$count_key] as $key => &$value) {
			if($value==0) {
				unset($return[$count_key][$key]);
			}
		}
		foreach($return['detailed'] as $key => &$mainActionArray) {
			if(!$mainActionArray[$count_key]) {
				unset($return['detailed'][$key]);
			} else if(!array_key_exists('details',$mainActionArray)) {
				foreach($mainActionArray as $key_inner => &$subActionsArray) {
					if(!$subActionsArray[$count_key] && $key_inner!=$count_key) {
						unset($mainActionArray[$key_inner]);
					}
				}
			}
		}
		if (in_array($ithemesec, $params['actions']) && iwp_mmb_ithemes_security_check()) {
			global $iwp_mmb_core;
			$ithemessec_instance = $iwp_mmb_core->get_ithemessec_instance();
			$logCounts = $ithemessec_instance->getLogCounts($params['fromDate'], $params['toDate']);
			$return['detailed'][$ithemesec]['details'] = $logCounts;
		}

		if (in_array($wordfence, $params['actions']) && iwp_mmb_is_wordfence()) {
			global $iwp_mmb_core;
			require_once($GLOBALS['iwp_mmb_plugin_dir'] . "/addons/wordfence/wordfence.class.php");
			$wordfence_instance = $iwp_mmb_core->get_wordfence_instance();
			$logCounts = $wordfence_instance->getLogCounts($params['fromDate'], $params['toDate']);
			$return['detailed'][$wordfence]['details'] = $logCounts;
		}
		if (in_array($backups_key, $params['actions']) && iwp_mmb_is_WPTC()) {
			$query = "SELECT backup_id from ".$wpdb->base_prefix."wptc_backups WHERE backup_id >='".$params['fromDate']."' AND backup_id<='".$params['toDate']."'";
			$wptc_backup_counts = 0;
			$wptc_backups = $wpdb->get_results($query,ARRAY_A);
			if (!empty($wptc_backups)) {
				$wptc_backups_details = array();
				foreach ($wptc_backups as $key => $backup) {
					$wptc_backup_counts ++;
					$details =  array();
					$date = date('M d, y',$backup['backup_id']);
					$time = date('g',$backup['backup_id']).':'.date('i',$backup['backup_id']).' '.date('a',$backup['backup_id']);
					$details['time'] = $time;
					$details['date'] = $date;
					$details['type'] = 'Files & DB';
					$wptc_backups_details[] =  $details;
				}
				$parentDetails = $return['detailed']['backups']['details'];
				if (empty($parentDetails)) {
					$parentDetails = array();
				}
				$parentDetails = array_merge($parentDetails,$wptc_backups_details);
				$return['detailed']['backups']['count']+=$wptc_backup_counts;
				$return['detailed']['backups']['details']= $parentDetails;
				$return['count']['backups']+=$wptc_backup_counts;
			}
		}
		iwp_mmb_response($return, true);		
	}
	
	function iwp_mmb_do_remove_upgrader_process_complete_action() {
		remove_action('upgrader_process_complete', array( &$this, 'iwp_mmb_upgrader_process_complete'), 1);
	}	
	
	function iwp_mmb_do_remove_theme_filters() {
		remove_filter('update_theme_complete_actions', array( &$this, 'iwp_mmb_update_theme_complete_actions')); // It is available from wordpress 2.7 to 3.6.
		remove_filter('update_bulk_theme_complete_actions', array( &$this, 'iwp_mmb_update_bulk_theme_complete_actions')); // It is available from wordpress 2.7 to 3.6.			
	}
	
	function iwp_mmb_do_remove_upgrader_post_install_filter() {
		remove_filter('upgrader_post_install', array( &$this, 'iwp_mmb_upgrader_post_install'));
	}
	
	function iwp_mmb_do_remove_core_updated_successfully() {
		remove_action('_core_updated_successfully', array( &$this, 'iwp_mmb_core_updated_successfully'),1);
	}	

	function iwp_mmb_register_custom_post_type(){
		register_post_type('iwp-log', array('label' => 'IWP Log'));	
	}
	function iwp_mmb_save_sucuri_activity_log(){
		$object = new IWP_MMB_Sucuri();
		$details = $object->getScannedCacheResult(1);
		if (!empty($details)) {
			$info = $details['info'];
			$userid = $this->iwp_mmb_get_current_user_id();
			$this->iwp_mmb_save_iwp_activities('sucuri', 'scan', 'automatic',$info, $userid);
		}
	}

	function iwp_mmb_backup_complete(){
		return true;
	}
}

if(!function_exists('iwp_make_values_as_zero')) {
	function iwp_make_values_as_zero($value) {
		return 0;
	}
}
if( !function_exists ( 'iwp_mmb_fetch_activities_log' )) {
	function iwp_mmb_fetch_activities_log($params) {
		global $iwp_mmb_activities_log;

		$iwp_mmb_activities_log->iwp_mmb_process_and_fetch_activities_log($params);
	}
}