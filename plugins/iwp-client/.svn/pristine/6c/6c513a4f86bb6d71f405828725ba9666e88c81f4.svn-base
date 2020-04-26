<?php
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/
/*************************************************************
 * 
 * installer.class.php
 * 
 * Upgrade WordPress
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if(basename($_SERVER['SCRIPT_FILENAME']) == "installer.class.php"):
    exit;
endif;
class IWP_MMB_Installer extends IWP_MMB_Core
{
    function __construct()
    {
        @set_time_limit(600);
        parent::__construct();
        @include_once(ABSPATH . 'wp-admin/includes/file.php');
        @include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        @include_once(ABSPATH . 'wp-includes/plugin.php');
        @include_once(ABSPATH . 'wp-admin/includes/theme.php');
        @include_once(ABSPATH . 'wp-admin/includes/misc.php');
        @include_once(ABSPATH . 'wp-admin/includes/template.php');
        @include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        
        global $wp_filesystem;
        if (!$wp_filesystem)
            WP_Filesystem();
    }
    
    function iwp_mmb_maintenance_mode($enable = false, $maintenance_message = '')
    {
        global $wp_filesystem;
        
        $maintenance_message .= '<?php $upgrading = ' . time() . '; ?>';
        
        $file = $wp_filesystem->abspath() . '.maintenance';
        if ($enable) {
            $wp_filesystem->delete($file);
            $wp_filesystem->put_contents($file, $maintenance_message, FS_CHMOD_FILE);
        } else {
            $wp_filesystem->delete($file);
        }
    }

    function bypass_url_validation($r,$url){
        // $username = parse_url($url, PHP_URL_USER);
        // $password = parse_url($url, PHP_URL_PASS);
        // $r['headers'] = array('Authorization'=>'Basic'. base64_encode( $username . ':' . $password ) );
        $r['reject_unsafe_urls'] = false;
        return $r;
    }
    
    function install_remote_file($params)
    {
				
        global $wp_filesystem;
        extract($params);
        
        if (!isset($package) || empty($package))
            return array(
                'error' => '<p>No files received. Internal error.</p>', 'error_code' => 'no_files_receive_internal_error'
            );
		
        if (!$this->define_ftp_constants($params)) {
            return array(
                'error' => 'FTP constant define failed', 'error_code' => 'ftp constant define failed'
            );
        }	
        if (!$this->is_server_writable()) {
            return array(
                'error' => 'Failed, please add FTP details', 'error_code' => 'failed_please_add_ftp_install_remote_file'
            );
        }

	
        if (defined('WP_INSTALLING') && file_exists(ABSPATH . '.maintenance'))
            return array(
                'error' => '<p>Site under maintanace.</p>','error_code' => 'site_under_maintanace'
            );
        
        if (!class_exists('WP_Upgrader'))
            include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        
        require_once $GLOBALS['iwp_mmb_plugin_dir'].'/updaterSkin.php';
        $upgrader_skin              = new IWP_Updater_TraceableUpdaterSkin;
        
        $upgrader          = new WP_Upgrader($upgrader_skin);
        $destination       = $type == 'themes' ? WP_CONTENT_DIR . '/themes' : WP_PLUGIN_DIR;
        $clear_destination = isset($clear_destination) ? $clear_destination : false;

        add_filter( 'http_request_args',array( $this, 'bypass_url_validation' ), 10, 2 );
        foreach ($package as $package_url) {
            $key                = basename($package_url);
            $install_info[$key] = @$upgrader->run(array(
                'package' => $package_url,
                'destination' => $destination,
                'clear_destination' => $clear_destination, //Do not overwrite files.
                'clear_working' => true,
                'hook_extra' => array()
            ));
        }

        // if (defined('WP_ADMIN') && WP_ADMIN) {
        //     global $wp_current_filter;
        //     $wp_current_filter[] = 'load-update-core.php';

        //     if (function_exists('wp_clean_update_cache')) {
        //         /** @handled function */
        //         wp_clean_update_cache();
        //     }

        //     /** @handled function */
        //     wp_update_plugins();

        //     array_pop($wp_current_filter);

        //     /** @handled function */
        //     set_current_screen();
        //     do_action('load-update-core.php');

        //     /** @handled function */
        //     wp_version_check();

        //     /** @handled function */
        //     wp_version_check(array(), true);
        // }
				
        if ($activate) {
            if ($type == 'plugins') {
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                 
				 wp_cache_delete( 'plugins', 'plugins' );
				 
				$all_plugins = get_plugins();
				foreach ($all_plugins as $plugin_slug => $plugin) {
                    $plugin_dir = preg_split('/\//', $plugin_slug);
                    foreach ($install_info as $key => $install) {
                        if (!$install || is_wp_error($install))
                            continue;
                        if ($install['destination_name'] == $plugin_dir[0]) {
                            $install_info[$key]['activated'] = activate_plugin($plugin_slug, '', false);
                        }
                    }
                }
            } else if (count($install_info) == 1) {
                global $wp_themes;
                include_once(ABSPATH . 'wp-includes/theme.php');
                
                $wp_themes = null;
                unset($wp_themes); //prevent theme data caching				
                if(function_exists('wp_get_themes')){
	                $all_themes = wp_get_themes();
	                foreach ($all_themes as $theme_name => $theme_data) {
	                    foreach ($install_info as $key => $install) {
	                        if (!$install || is_wp_error($install))
	                            continue;
                
	                        if ($theme_data->Template == $install['destination_name']) {
	                            $install_info[$key]['activated'] = switch_theme($theme_data->Template, $theme_data->Stylesheet);
	                        }
	                    }
	                }
                }else{
                $all_themes = get_themes();
                foreach ($all_themes as $theme_name => $theme_data) {
                    foreach ($install_info as $key => $install) {
                        if (!$install || is_wp_error($install))
                            continue;
                        
                        if ($theme_data['Template'] == $install['destination_name']) {
                            $install_info[$key]['activated'] = switch_theme($theme_data['Template'], $theme_data['Stylesheet']);
                        }
                    }
                }
            }
        }
        }
        $this->iwp_mmb_maintenance_mode(false);
        return $install_info;
    }
    
    function do_upgrade($params = null)
    {
		global $iwp_mmb_activities_log;
		
		if ($params == null || empty($params))
            return array(
                'error' => 'No upgrades passed.', 'error_code' => 'no_upgrades_passed'
            );
        if (!$this->define_ftp_constants($params)) {
            return array(
                'error' => 'FTP constant define failed', 'error_code' => 'ftp constant define failed'
            );
        } 
        if (!$this->is_server_writable()) {
            return array(
                'error' => 'Failed, please add FTP details', 'error_code' => 'failed_please_add_ftp_do_upgrade'
            );
        }
        $WPTC_response = apply_filters('backup_and_update_wptc', $params);
        if ($WPTC_response == 'WPTC_TAKES_CARE_OF_IT') {
            return array('success' => 'The update will now be handled by WP Time Capsule. Check the WPTC page for its status.', 'success_code' => 'WPTC_TAKES_CARE_OF_IT');
        }elseif (!empty($WPTC_response['error_code'])) {
            return $WPTC_response;
        }elseif (!empty($WPTC_response['success'])) {
            return $WPTC_response;
        }
        $params = isset($params['upgrades_all']) ? $params['upgrades_all'] : $params;
        
        $core_upgrade    = isset($params['wp_upgrade']) ? $params['wp_upgrade'] : array();
        $upgrade_plugins = isset($params['upgrade_plugins']) ? $params['upgrade_plugins'] : array();
        $upgrade_themes  = isset($params['upgrade_themes']) ? $params['upgrade_themes'] : array();
        $upgrade_translations = isset($params['upgrade_translations']) ? $params['upgrade_translations'] : array();
        $upgrades         = array();
        $premium_upgrades = array();
		$user = get_user_by( 'login', $params['username'] );
		$userid = $user->data->ID;
		
		if (!empty($core_upgrade) || !empty($upgrade_plugins) || !empty($upgrade_themes) || !empty($upgrade_translations)) {
			$iwp_mmb_activities_log->iwp_mmb_do_remove_upgrader_process_complete_action();
			$iwp_mmb_activities_log->iwp_mmb_do_remove_theme_filters();
			$iwp_mmb_activities_log->iwp_mmb_do_remove_upgrader_post_install_filter();
		}
		if (!empty($core_upgrade) || !empty($upgrade_plugins) || !empty($upgrade_themes)) {
			$GLOBALS['iwp_client_plugin_ptc_updates'] = 1;			
		}
		
        if (!empty($core_upgrade)) {
			$iwp_mmb_activities_log->iwp_mmb_do_remove_core_updated_successfully();
            $upgrades['core'] = $this->upgrade_core($core_upgrade,$userid);
        }
        if (!empty($upgrade_plugins)) {
            $plugin_files = $plugin_details = $premium_plugin_details = array();
            $this->IWP_ithemes_updater_compatiblity();
            foreach ($upgrade_plugins as $plugin) {
                $file_path = $plugin['file'];
                $plugin_name = $plugin['name'];
                if (isset($file_path)) {
					$plugin_details[] = $plugin;
                    $plugin_files[$file_path] = $plugin->old_version;
                } else {
					$premium_plugin_details[] = $plugin;
                    $premium_upgrades[md5($plugin_name)] = $plugin;
				}
            }
            if (!empty($plugin_files)) {
                $upgrades['plugins'] = $this->upgrade_plugins($plugin_files,$plugin_details,$userid);
            }
            $this->IWP_ithemes_updater_compatiblity();
        }
        
        if (!empty($upgrade_themes)) {
            $theme_temps = $theme_details = $premium_theme_details = array();
            foreach ($upgrade_themes as $theme) {
                if (isset($theme['theme_tmp'])) {
					$theme_details[] = $theme;
                    $theme_temps[] = $theme['theme_tmp'];
                } else {
					$premium_theme_details[] = $theme;
                    $premium_upgrades[md5($theme['name'])] = $theme;
				}
            }
            
            if (!empty($theme_temps))
                $upgrades['themes'] = $this->upgrade_themes($theme_temps,$theme_details,$userid);
            
        }
        
        if (!empty($premium_upgrades)) {
            $premium_upgrades = $this->upgrade_premium($premium_upgrades,$premium_plugin_details,$premium_theme_details,$userid);
            if (!empty($premium_upgrades)) {
                if (!empty($upgrades)) {
                    foreach ($upgrades as $key => $val) {
                        if (isset($premium_upgrades[$key])) {
                            $upgrades[$key] = array_merge_recursive($upgrades[$key], $premium_upgrades[$key]);
                        }
                    }
                } else {
                    $upgrades = $premium_upgrades;
                }
            }
        }
        if (!empty($upgrade_translations)) {
            $upgrades['translations'] = $this->upgrade_translations($upgrade_translations,$userid);
        }
        $this->iwp_mmb_maintenance_mode(false);
        return $upgrades;
    }
    
    /**
     * Upgrades WordPress locally
     *
     */
    function upgrade_translations($current,$userid){
		global $iwp_activities_log_post_type, $iwp_mmb_activities_log;		
		$GLOBALS['iwp_client_plugin_translations'] = 1;
        include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once $GLOBALS['iwp_mmb_plugin_dir'].'/updaterSkin.php';
        $upgrader = new Language_Pack_Upgrader( new IWP_Updater_TraceableUpdaterSkin() );
        $result = $upgrader->bulk_upgrade();
        $upgradeFailed = false;
        if (!empty($result)) {
            foreach ($result as $translate_tmp => $translate_info) {
                if (is_wp_error($translate_info) || empty($translate_info)) {
                    $upgradeFailed = true;
                    $return = array('error' => $this->iwp_mmb_get_error($translate_info), 'error_code' => 'upgrade_translations_wp_error');
                    break;
                }
            }
            if(!$upgradeFailed){
				$details = array();
				$iwp_mmb_activities_log->iwp_mmb_save_iwp_activities('translations', 'update', $iwp_activities_log_post_type, (object)$details, $userid);
                $return = 'updated';
            }
            return array('upgraded' => $return);
        } else {
            return array(
                'error' => 'Upgrade failed.', 'error_code' => 'unable_to_update_translations_files'
            );
        }
    }

    function upgrade_core($current,$userid)
    {
		global $iwp_activities_log_post_type, $iwp_mmb_activities_log;		
        ob_start();
        $current = (object)$current;

            include_once(ABSPATH . '/wp-admin/includes/update.php');
        
        @wp_version_check();
        
        $current_update = false;
        ob_end_flush();
        ob_end_clean();
        $core = $this->iwp_mmb_get_transient('update_core');
        
        if (isset($core->updates) && !empty($core->updates)) {
            $updates = $core->updates[0];
            $updated = $core->updates[0];
            if (!isset($updated->response) || $updated->response == 'latest')
                return array(
                    'upgraded' => 'updated'
                );
            
            if ($updated->response == "development" && $current->response == "upgrade") {
                return array(
                    'error' => '<font color="#900">Unexpected error. Please upgrade manually.</font>', 'error_code' => 'unexpected_error_please_upgrade_manually'
                );
            } else if ($updated->response == $current->response || ($updated->response == "upgrade" && $current->response == "development")) {
                if ($updated->locale != $current->locale) {
                    foreach ($updates as $update) {
                        if ($update->locale == $current->locale) {
                            $current_update = $update;
                            break;
                        }
                    }
                    if ($current_update == false)
                        return array(
                            'error' => ' Localization mismatch. Try again.', 'error_code' => 'localization_mismatch'
                        );
                } else {
                    $current_update = $updated;
                }
            } else
                return array(
                    'error' => ' Transient mismatch. Try again.', 'error_code' => 'transient_mismatch'
                );
        } else
            return array(
                'error' => ' Refresh transient failed. Try again.', 'error_code' => 'refresh_transient_failed'
            );
        if ($current_update != false) {
            global $iwp_mmb_wp_version, $wp_filesystem, $wp_version;
            
            if (version_compare($wp_version, '3.1.9', '>')) {
                if (!class_exists('Core_Upgrader'))
                    include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
                require_once $GLOBALS['iwp_mmb_plugin_dir'].'/updaterSkin.php';
                $core   = new Core_Upgrader(new IWP_Updater_TraceableUpdaterSkin());
                $result = $core->upgrade($current_update);
                $this->iwp_mmb_maintenance_mode(false);
                if (is_wp_error($result)) {
                    return array(
                        'error' => $this->iwp_mmb_get_error($result), 'error_code' => 'maintenance_mode_upgrade_core'
                    );
                } else {
					$iwp_mmb_activities_log->iwp_mmb_save_iwp_activities('core', 'update', $iwp_activities_log_post_type, $current, $userid);
                    return array(
                        'upgraded' => 'updated'
                    );
				}
            } else {
                if (!class_exists('WP_Upgrader')) {
                    include_once(ABSPATH . 'wp-admin/includes/update.php');
                    if (function_exists('wp_update_core')) {
                        $result = wp_update_core($current_update);
                        if (is_wp_error($result)) {
                            return array(
                                'error' => $this->iwp_mmb_get_error($result), 'error_code' => 'wp_update_core_upgrade_core'
                            );
                        } else {
							$iwp_mmb_activities_log->iwp_mmb_save_iwp_activities('core', 'update', $iwp_activities_log_post_type, $current, $userid);
                            return array(
                                'upgraded' => 'updated'
                            );
						}
                    }
                }
                
                if (class_exists('WP_Upgrader')) {
                    $upgrader_skin              = new WP_Upgrader_Skin();
                    $upgrader_skin->done_header = true;
                    
                    $upgrader = new WP_Upgrader($upgrader_skin);
                    
                    // Is an update available?
                    if (!isset($current_update->response) || $current_update->response == 'latest')
                        return array(
                            'upgraded' => 'updated'
                        );
                    
                    $res = $upgrader->fs_connect(array(
                        ABSPATH,
                        WP_CONTENT_DIR
                    ));
                    if (is_wp_error($res))
                        return array(
                            'error' => $this->iwp_mmb_get_error($res), 'error_code' => 'upgrade_core_wp_error_res'
                        );
                    
                    $wp_dir = trailingslashit($wp_filesystem->abspath());
                    
                    $core_package = false;
                    if (isset($current_update->package) && !empty($current_update->package))
                        $core_package = $current_update->package;
                    elseif (isset($current_update->packages->full) && !empty($current_update->packages->full))
                        $core_package = $current_update->packages->full;
                    
                    $download = $upgrader->download_package($core_package);
                    if (is_wp_error($download))
                        return array(
                            'error' => $this->iwp_mmb_get_error($download), 'error_code' => 'download_upgrade_core'
                        );
                    
                    $working_dir = $upgrader->unpack_package($download);
                    if (is_wp_error($working_dir))
                        return array(
                            'error' => $this->iwp_mmb_get_error($working_dir), 'error_code' => 'working_dir_upgrade_core'
                        );
                    
                    if (!$wp_filesystem->copy($working_dir . '/wordpress/wp-admin/includes/update-core.php', $wp_dir . 'wp-admin/includes/update-core.php', true)) {
                        $wp_filesystem->delete($working_dir, true);
                        return array(
                            'error' => 'Unable to move update files.', 'error_code' => 'unable_to_move_update_files'
                        );
                    }
                    
                    $wp_filesystem->chmod($wp_dir . 'wp-admin/includes/update-core.php', FS_CHMOD_FILE);
                    
                    require(ABSPATH . 'wp-admin/includes/update-core.php');
                    
                    
                    $update_core = update_core($working_dir, $wp_dir);
                    
                    $this->iwp_mmb_maintenance_mode(false);
                    if (is_wp_error($update_core))
                        return array(
                            'error' => $this->iwp_mmb_get_error($update_core), 'error_code' => 'upgrade_core_wp_error'
                        );
					$iwp_mmb_activities_log->iwp_mmb_save_iwp_activities('core', 'update', $iwp_activities_log_post_type, $current, $userid);
                    return array(
                        'upgraded' => 'updated'
                    );
                } else {
                    return array(
                        'error' => 'failed', 'error_code' => 'failed_WP_Upgrader_class_not_exists'
                    );
                }
            }
        } else {
            return array(
                'error' => 'failed', 'error_code' => 'failed_current_update_false'
            );
        }
    }
    
    function upgrade_plugins($plugins = false,$plugin_details = false,$userid)
    {
		global $iwp_activities_log_post_type, $iwp_mmb_activities_log;
        if (!$plugins || empty($plugins))
            return array(
                'error' => 'No plugin files for upgrade.', 'error_code' => 'no_plugin_files_for_upgrade'
            );	
		$current = $this->iwp_mmb_get_transient('update_plugins');
		$versions = array();
		if(!empty($current)){
			foreach($plugins as $plugin => $data){
				if(isset($current->checked[$plugin])){
					$versions[$current->checked[$plugin]] = $plugin;
				}
			}
		}
        $return = array();
        if (class_exists('Plugin_Upgrader')) {
            
			if (!function_exists('wp_update_plugins'))
                include_once(ABSPATH . 'wp-includes/update.php');

            require_once $GLOBALS['iwp_mmb_plugin_dir'].'/updaterSkin.php';
            
            @wp_update_plugins();
			$upgrader = new Plugin_Upgrader(new IWP_Updater_TraceableUpdaterSkin());
			$result = $upgrader->bulk_upgrade(array_keys($plugins));
			$current = $this->iwp_mmb_get_transient('update_plugins');
			
			if (!empty($result)) {
                foreach ($result as $plugin_slug => $plugin_info) {
                    if (!$plugin_info || is_wp_error($plugin_info)) {
                        $return[$plugin_slug] = array('error' => $this->iwp_mmb_get_error($plugin_info), 'error_code' => 'upgrade_plugins_wp_error');
                    } else {
						if(
							!empty($result[$plugin_slug]) 
							|| (
									isset($current->checked[$plugin_slug]) 
									&& version_compare(array_search($plugin_slug, $versions), $current->checked[$plugin_slug], '<') == true
								)
						){
							foreach($plugin_details as $key=>$plugin_detail) {
								/* the following "if" is used to detect premium plugin properties.*/
								if(is_array($plugin_detail)) {
									$plugin_detail = (object) $plugin_detail;
								}
								/* the above "if" is used to detect premium plugin properties.*/
								
								if(
									(
										isset($plugin_detail->plugin)
										&& $plugin_slug==$plugin_detail->plugin
									) 
									|| ( // This condition is used to detect premium plugin properties.
										isset($plugin_detail->slug)
										&& $plugin_slug==$plugin_detail->slug									
									)
								) {
									$current_plugin = array();
									$current_plugin['name'] = isset($plugin_detail->name)?$plugin_detail->name:'';
									
									if(isset($plugin_detail->textdomain)) { // this "if" is used to detect premium plugin properties.
										$current_plugin['slug'] = $plugin_detail->textdomain;
									} else if(isset($plugin_detail->slug)) {
										$current_plugin['slug'] = $plugin_detail->slug;
									} else {
										$current_plugin['slug'] = '';
									}
									
									if(isset($plugin_detail->old_version)) {
										$current_plugin['old_version'] = $plugin_detail->old_version;										
									} else if(isset($plugin_detail->version)) {
										$current_plugin['old_version'] = $plugin_detail->version;										
									} else {
										$current_plugin['old_version'] = '';
									}

									$current_plugin['updated_version'] = isset($plugin_detail->new_version) ? $plugin_detail->new_version : '';
									$iwp_mmb_activities_log->iwp_mmb_save_iwp_activities('plugins', 'update', $iwp_activities_log_post_type, (object)$current_plugin, $userid);
									unset($current_plugin);
									break;
								}
							}
							$return[$plugin_slug] = 1;
						} else {
							update_option('iwp_client_forcerefresh', true);
							$return[$plugin_slug] = array('error' => 'Could not refresh upgrade transients, please reload website data', 'error_code' => 'upgrade_plugins_could_not_refresh_upgrade_transients_please_reload_website_data');
						}
                    }
                }
                return array(
                    'upgraded' => $return
                );
            } else
                return array(
                    'error' => 'Upgrade failed.', 'error_code' => 'upgrade_failed_upgrade_plugins'
                );
        } else {
            return array(
                'error' => 'WordPress update required first.', 'error_code' => 'upgrade_plugins_wordPress_update_required_first'
            );
        }
    }
    
    function upgrade_themes($themes = false,$theme_details = false,$userid)
    {
		global $iwp_activities_log_post_type, $iwp_mmb_activities_log;
        if (!$themes || empty($themes))
            return array(
                'error' => 'No theme files for upgrade.', 'error_code' => 'no_theme_files_for_upgrade'
            );
		
		$current = $this->iwp_mmb_get_transient('update_themes');
		$versions = array();
		if(!empty($current)){
			foreach($themes as $theme){
				if(isset($current->checked[$theme])){
					$versions[$current->checked[$theme]] = $theme;
				}
			}
		}
		if (class_exists('Theme_Upgrader')) {
            require_once $GLOBALS['iwp_mmb_plugin_dir'].'/updaterSkin.php';
			$upgrader = new Theme_Upgrader(new IWP_Updater_TraceableUpdaterSkin());
            $result = $upgrader->bulk_upgrade($themes);
			
			if (!function_exists('wp_update_themes'))
                include_once(ABSPATH . 'wp-includes/update.php');
            
            @wp_update_themes();
			$current = $this->iwp_mmb_get_transient('update_themes');
			$return = array();
            if (!empty($result)) {
                foreach ($result as $theme_tmp => $theme_info) {
					 if (is_wp_error($theme_info) || empty($theme_info)) {
                        $return[$theme_tmp] = array('error' => $this->iwp_mmb_get_error($theme_info), 'error_code' => 'upgrade_themes_wp_error');
                    } else {
						if(!empty($result[$theme_tmp]) || (isset($current->checked[$theme_tmp]) && version_compare(array_search($theme_tmp, $versions), $current->checked[$theme_tmp], '<') == true)){
							foreach($theme_details as $key=>$theme_detail) {
								if($theme_tmp==$theme_detail['theme_tmp']) {
									$current_theme = array();
									$current_theme['name'] = $current_theme['slug'] = $theme_detail['name'];  // slug is used to get short description. Here theme name as slug.
									$current_theme['old_version'] = $theme_detail['old_version'];
									$current_theme['updated_version'] = $theme_detail['new_version'];
									$iwp_mmb_activities_log->iwp_mmb_save_iwp_activities('themes', 'update', $iwp_activities_log_post_type, (object)$current_theme, $userid);
									unset($current_theme);
									break;
								}
							}							
							$return[$theme_tmp] = 1;
						} else {
							update_option('iwp_client_forcerefresh', true);
							$return[$theme_tmp] = array('error' => 'Could not refresh upgrade transients, please reload website data', 'error_code' => 'upgrade_themes_could_not_refresh_upgrade_transients_reload_website');
						}
                    }
                }
                return array(
                    'upgraded' => $return
                );
            } else
                return array(
                    'error' => 'Upgrade failed.', 'error_code' => 'upgrade_failed_upgrade_themes'
                );
        } else {
            return array(
                'error' => 'WordPress update required first', 'error_code' => 'wordPress_update_required_first_upgrade_themes'
            );
        }
    }
    
    function upgrade_premium($premium = false,$premium_plugin_details = false,$premium_theme_details = false,$userid)
    {
		global $iwp_mmb_plugin_url;
		
        if (!class_exists('WP_Upgrader'))
            include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        
        if (!$premium || empty($premium))
            return array(
                'error' => 'No premium files for upgrade.', 'error_code' => 'no_premium_files_for_upgrade'
            );
        
        $upgrader       = false;
        $pr_update      = array();
        $themes = array();
        $plugins = array();
        $result         = array();
        $premium_update = array();
		$premium_update = apply_filters('mwp_premium_perform_update', $premium_update);
        if (!empty($premium_update)) {
			
            foreach ($premium as $pr) {
				foreach ($premium_update as $key => $update) {
                    $update = array_change_key_case($update, CASE_LOWER);
                    if ($update['name'] == $pr['name']) {
						
						// prepare bulk updates for premiums that use WordPress upgrader
						if(isset($update['type'])){
							if($update['type'] == 'plugin'){
								if(isset($update['slug']) && !empty($update['slug']))
									$plugins[$update['slug']] = $update;
							}
							
							if($update['type'] == 'theme'){
								if(isset($update['template']) && !empty($update['template']))
									$themes[$update['template']] = $update;
							}
						}
					} else {
						unset($premium_update[$key]);
					}
				}
			}
			
			// try default wordpress upgrader
			if(!empty($plugins)){
				$updateplugins = $this->upgrade_plugins($plugins,$premium_plugin_details,$userid);
				if(!empty($updateplugins) && isset($updateplugins['upgraded'])){
					foreach ($premium_update as $key => $update) {
						$update = array_change_key_case($update, CASE_LOWER);
						foreach($updateplugins['upgraded'] as $slug => $upgrade){
							if( isset($update['slug']) && $update['slug'] == $slug){
								if( $upgrade == 1 )
									unset($premium_update[$key]);
								
								$pr_update['plugins']['upgraded'][md5($update['name'])] = $upgrade;
							}
						}
					}
				}
			}
			
			if(!empty($themes)){
				$updatethemes = $this->upgrade_themes(array_keys($themes),$premium_theme_details,$userid);
				if(!empty($updatethemes) && isset($updatethemes['upgraded'])){
					foreach ($premium_update as $key => $update) {
						$update = array_change_key_case($update, CASE_LOWER);
						foreach($updatethemes['upgraded'] as $template => $upgrade){
							if( isset($update['template']) && $update['template'] == $template) {
								if( $upgrade == 1 )
									unset($premium_update[$key]);
        
								$pr_update['themes']['upgraded'][md5($update['name'])] = $upgrade;
							}
						}
					}
				}
			}
			
			//try direct install with overwrite
			if (!empty($premium_update)) {
                foreach ($premium_update as $update) {
                    $update = array_change_key_case($update, CASE_LOWER);
					$update_result = false;
					if (isset($update['url'])) {
						if (defined('WP_INSTALLING') && file_exists(ABSPATH . '.maintenance'))
							$pr_update[$update['type'] . 's']['upgraded'][md5($update['name'])] = 'Site under maintanace.';
						
							$upgrader_skin              = new WP_Upgrader_Skin();
							$upgrader_skin->done_header = true;
							$upgrader = new WP_Upgrader();
						@$update_result = $upgrader->run(array(
							'package' => $update['url'],
							'destination' => isset($update['type']) && $update['type'] == 'theme' ? WP_CONTENT_DIR . '/themes' : WP_PLUGIN_DIR,
							'clear_destination' => true,
							'clear_working' => true,
						'is_multi' => true,
							'hook_extra' => array()
						));
						$update_result = !$update_result || is_wp_error($update_result) ? $this->iwp_mmb_get_error($update_result) : 1;
						
					} else if (isset($update['callback'])) {
						if (is_array($update['callback'])) {
							$update_result = call_user_func(array( $update['callback'][0], $update['callback'][1] ));
						} else if (is_string($update['callback'])) {
							$update_result = call_user_func($update['callback']);
						} else {
							$update_result = array('error' => 'Upgrade function "' . $update['callback'] . '" does not exists.', 'error_code' => 'upgrade_func_callback_does_not_exists');
						}
						
						$update_result = $update_result !== true ? array('error' => $this->iwp_mmb_get_error($update_result), 'error_code' => 'upgrade_premium_wp_error') : 1;
					} else
						$update_result = array('error' => 'Bad update params.', 'error_code' => 'bad_update_params');
					
					$pr_update[$update['type'] . 's']['upgraded'][md5($update['name'])] = $update_result;
                }
            }
            return $pr_update;
        } else {
            foreach ($premium as $pr) {
                $result[$pr['type'] . 's']['upgraded'][md5($pr['name'])] = array('error' => 'This premium plugin/theme update is not registered with the InfiniteWP update mechanism. Please contact the plugin/theme developer to get this issue fixed.', 'error_code' => 'premium_update_not_registered');
            }
            return $result;
        }
    }
    
    function get_upgradable_plugins( $filter = array() )
    {
		if (!function_exists('wp_update_plugins'))
			include_once(ABSPATH . 'wp-includes/update.php');    
		@wp_update_plugins();
        $current            = $this->iwp_mmb_get_transient('update_plugins');
		
        $upgradable_plugins = array();
        if (!empty($current->response)) {
            if (!function_exists('get_plugin_data'))
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            foreach ($current->response as $plugin_path => $plugin_data) {
                if ($plugin_path == 'iwp-client/init.php')
                    continue;
                
                $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);               
				if(isset($data['Name']) && in_array($data['Name'], $filter))
					continue;
				
                if (strlen($data['Name']) > 0 && strlen($data['Version']) > 0) {
                    $current->response[$plugin_path]->name        = $data['Name'];
                    $current->response[$plugin_path]->old_version = $data['Version'];
                    $current->response[$plugin_path]->file        = $plugin_path;
                    unset($current->response[$plugin_path]->upgrade_notice);
                    $upgradable_plugins[]                         = $current->response[$plugin_path];
                }
            }
            return $upgradable_plugins;
        } else
            return array();
    }
  function get_upgradable_translations()  {
         if (!function_exists('wp_get_translation_updates')){
            include_once(ABSPATH . 'wp-includes/update.php');
         }
         
        if (function_exists('wp_get_translation_updates')) {
            $translations_object = wp_get_translation_updates();
            $translations_object = array_filter($translations_object);
         } 

        if (isset($translations_object) && !empty($translations_object)){
            return true;
        } else{
            return false;
        }
    }
    
    function get_upgradable_themes($filter = array()) {
        if (function_exists('wp_get_themes')) {
            $all_themes     = wp_get_themes();
            $upgrade_themes = array();

            $current = $this->iwp_mmb_get_transient('update_themes');
            if (!empty($current->response)) {
                foreach ((array) $all_themes as $theme_template => $theme_data) {
                    foreach ($current->response as $current_themes => $theme) {
                        if ($theme_data->Stylesheet !== $current_themes) {
                            continue;
                        }

                        if (strlen($theme_data->Name) === 0 || strlen($theme_data->Version) === 0) {
                            continue;
                        }

                        $current->response[$current_themes]['name']        = $theme_data->Name;
                        $current->response[$current_themes]['old_version'] = $theme_data->Version;
                        $current->response[$current_themes]['theme_tmp']   = $theme_data->Stylesheet;

                        $upgrade_themes[] = $current->response[$current_themes];
                    }
                }
            }
        } else {
            $all_themes = get_themes();

            $upgrade_themes = array();

            $current = $this->iwp_mmb_get_transient('update_themes');

            if (!empty($current->response)) {
                foreach ((array) $all_themes as $theme_template => $theme_data) {
                    if (isset($theme_data['Parent Theme']) && !empty($theme_data['Parent Theme'])) {
                        continue;
                    }

                    if (isset($theme_data['Name']) && in_array($theme_data['Name'], $filter)) {
                        continue;
                    }
					
                    if (method_exists($theme_data,'parent') && !$theme_data->parent()) {
						foreach ($current->response as $current_themes => $theme) {
							if ($theme_data['Template'] == $current_themes) {
								if (strlen($theme_data['Name']) > 0 && strlen($theme_data['Version']) > 0) {
									$current->response[$current_themes]['name']        = $theme_data['Name'];
									$current->response[$current_themes]['old_version'] = $theme_data['Version'];
									$current->response[$current_themes]['theme_tmp']   = $theme_data['Template'];
									$upgrade_themes[]                                  = $current->response[$current_themes];
								}
							}
						}
					}
				}
			}

		}

        return $upgrade_themes;
    }
    
    function get($args)
    {
        if (empty($args))
            return false;
        
        //Args: $items('plugins,'themes'), $type (active || inactive), $search(name string)
        
        $return = array();
        if (is_array($args['items']) && in_array('plugins', $args['items'])) {
            $return['plugins'] = $this->get_plugins($args);
        }
        if (is_array($args['items']) && in_array('themes', $args['items'])) {
            $return['themes'] = $this->get_themes($args);
        }
        
        return $return;
    }
    
    function get_plugins($args)
    {
        if (empty($args))
            return false;
        
        extract($args);
        
        if (!function_exists('get_plugins')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $all_plugins = get_plugins();
        $plugins     = array(
            'active' => array(),
            'inactive' => array()
        );
        if (is_array($all_plugins) && !empty($all_plugins)) {
            $activated_plugins = get_option('active_plugins');
            if (!$activated_plugins)
                $activated_plugins = array();
            
            $br_a = 0;
            $br_i = 0;
            foreach ($all_plugins as $path => $plugin) {
                if ($plugin['Name'] != 'InfiniteWP - Client') {
                    if (in_array($path, $activated_plugins)) {
                        $plugins['active'][$br_a]['path'] = $path;
                        $plugins['active'][$br_a]['name'] = strip_tags($plugin['Name']);
						$plugins['active'][$br_a]['version'] = $plugin['Version'];
                        $br_a++;
                    }
                    
                    if (!in_array($path, $activated_plugins)) {
                        $plugins['inactive'][$br_i]['path'] = $path;
                        $plugins['inactive'][$br_i]['name'] = strip_tags($plugin['Name']);
						$plugins['inactive'][$br_i]['version'] = $plugin['Version'];
                        $br_i++;
                    }
                    
                }
                
                if ($search) {
                    foreach ($plugins['active'] as $k => $plugin) {
                        if (!stristr($plugin['name'], $search)) {
                            unset($plugins['active'][$k]);
                        }
                    }
                    
                    foreach ($plugins['inactive'] as $k => $plugin) {
                        if (!stristr($plugin['name'], $search)) {
                            unset($plugins['inactive'][$k]);
                        }
                    }
                }
            }
        }
        
        return $plugins;
    }
    
    function get_themes($args)
    {
        if (empty($args))
            return false;
        
        extract($args);
        
        if (!function_exists('wp_get_themes')) {
            include_once(ABSPATH . WPINC . '/theme.php');
        }
        if(function_exists('wp_get_themes')){
	        $all_themes = wp_get_themes();
	        $themes     = array(
					            'active' => array(),
					            'inactive' => array()
					        );
	        if (is_array($all_themes) && !empty($all_themes)) {
	            $current_theme = wp_get_theme();
	            
	            $br_a = 0;
	            $br_i = 0;
	            foreach ($all_themes as $theme_name => $theme) {
	                if ($current_theme == strip_tags($theme->Name)) {
	                    $themes['active'][$br_a]['path']       = $theme->Template;
	                    $themes['active'][$br_a]['name']       = strip_tags($theme->Name);
						$themes['active'][$br_a]['version']    = $theme->Version;
	                    $themes['active'][$br_a]['stylesheet'] = $theme->Stylesheet;
	                    $br_a++;
	                }
	                
	                if ($current_theme != strip_tags($theme->Name)) {
	                    $themes['inactive'][$br_i]['path']       = $theme->Template;
	                    $themes['inactive'][$br_i]['name']       = strip_tags($theme->Name);
						$themes['inactive'][$br_i]['version']    = $theme->Version;
	                    $themes['inactive'][$br_i]['stylesheet'] = $theme->Stylesheet;
	                    $br_i++;
	                }
	                
	            }
	            
	            if (!empty($search)) {
	                foreach ($themes['active'] as $k => $theme) {
	                    if (!stristr($theme['name'], $search)) {
	                        unset($themes['active'][$k]);
	                    }
	                }
	                
	                foreach ($themes['inactive'] as $k => $theme) {
	                    if (!stristr($theme['name'], $search)) {
	                        unset($themes['inactive'][$k]);
	                    }
	                }
	            }
	        }
	    }else{
        $all_themes = get_themes();
        $themes     = array(
            'active' => array(),
            'inactive' => array()
        );
        
        if (is_array($all_themes) && !empty($all_themes)) {
            $current_theme = get_current_theme();
            
            $br_a = 0;
            $br_i = 0;
            foreach ($all_themes as $theme_name => $theme) {
                if ($current_theme == $theme_name) {
                    $themes['active'][$br_a]['path']       = $theme['Template'];
                    $themes['active'][$br_a]['name']       = strip_tags($theme['Name']);
					$themes['active'][$br_a]['version']    = $theme['Version'];
                    $themes['active'][$br_a]['stylesheet'] = $theme['Stylesheet'];
                    $br_a++;
                }
                
                if ($current_theme != $theme_name) {
                    $themes['inactive'][$br_i]['path']       = $theme['Template'];
                    $themes['inactive'][$br_i]['name']       = strip_tags($theme['Name']);
					$themes['inactive'][$br_i]['version']    = $theme['Version'];
                    $themes['inactive'][$br_i]['stylesheet'] = $theme['Stylesheet'];
                    $br_i++;
                }
                
            }
            
            if ($search) {
                foreach ($themes['active'] as $k => $theme) {
                    if (!stristr($theme['name'], $search)) {
                        unset($themes['active'][$k]);
                    }
                }
                
                foreach ($themes['inactive'] as $k => $theme) {
                    if (!stristr($theme['name'], $search)) {
                        unset($themes['inactive'][$k]);
                    }
                }
            }
        }
        
	    }
        
        return $themes;
    }
    
    function edit($args)
    {
        extract($args);
        $return = array();
        if (!$this->define_ftp_constants($args)) {
            return array(
                'error' => 'FTP constant define failed', 'error_code' => 'ftp constant define failed'
            );
        }
        if ($type == 'plugins') {
            $return['plugins'] = $this->edit_plugins($args);
        } elseif ($type == 'themes') {
            $return['themes'] = $this->edit_themes($args);
        }
        return $return;
    }
    
    function edit_plugins($args)
    {
        extract($args);
        $return = array();
        foreach ($items as $item) {
            switch ($item['action']) {//switch ($items_edit_action) => switch ($item['action'])
                case 'activate':
                    $result = activate_plugin($item['path']);
                    break;
                case 'deactivate':
                    $result = deactivate_plugins(array(
                        $item['path']
                    ));
                    break;
                case 'delete':
                    $result = delete_plugins(array(
                        $item['path']
                    ));
                    break;
                default:
                    break;
            }
            
            if (is_wp_error($result)) {
                $result = array(
                    'error' => $result->get_error_message(), 'error_code' => 'wp_error_edit_plugins'
                );
            } elseif ($result === false) {
                $result = array(
                    'error' => "Failed to perform action.", 'error_code' => 'failed_to_perform_action_edit_plugins'
                );
            } else {
                $result = "OK";
            }
            $return[$item['name']] = $result;
        }
        
        return $return;
    }
    
    function edit_themes($args)
    {
        extract($args);
        $return = array();
        foreach ($items as $item) {
            switch ($item['action']) {//switch ($items_edit_action) => switch ($item['action'])
                case 'activate':
                    switch_theme($item['path'], $item['stylesheet']);
                    break;
                case 'delete':
                    $result = delete_theme($item['path']);
                    break;
                default:
                    break;
            }
            
            if (is_wp_error($result)) {
                $result = array(
                    'error' => $result->get_error_message(), 'error_code' => 'wp_error_edit_themes'
                );
            } elseif ($result === false) {
                $result = array(
                    'error' => "Failed to perform action.", 'error_code' => 'failed_to_perform_action_edit_themes'
                );
            } else {
                $result = "OK";
            }
            $return[$item['name']] = $result;
        }
        
        return $return;
        
    }

    function IWP_ithemes_updater_compatiblity()
    {
        // Check for the iThemes updater class
        if (empty($GLOBALS['ithemes_updater_path']) ||
            !file_exists($GLOBALS['ithemes_updater_path'].'/settings.php')
        ) {
            return;
        }

        // Include iThemes updater
        require_once $GLOBALS['ithemes_updater_path'].'/settings.php';

        // Check if the updater is instantiated
        if (empty($GLOBALS['ithemes-updater-settings'])) {
            return;
        }

        // Update the download link
        $GLOBALS['ithemes-updater-settings']->flush('forced');
    }
    function get_additional_plugin_updates()
    {

        $additional_updates = array();

        if (is_plugin_active('woocommerce/woocommerce.php') && $this->has_woocommerce_db_update()) {
            $additional_updates['woocommerce/woocommerce.php'] = 1;
        }

        return $additional_updates;
    }

    function has_woocommerce_db_update()
    {
        $current_db_version = get_option('woocommerce_db_version', null);
        $current_wc_version = get_option('woocommerce_version');
        if (version_compare($current_wc_version, '3.0.0', '<')) {
            return true;
        }

        if (!is_callable('WC_Install::get_db_update_callbacks')) {
            return false;
        }

        /** @handled static */
        $updates = WC_Install::get_db_update_callbacks();

        return !is_null($current_db_version) && version_compare($current_db_version, max(array_keys($updates)), '<');
    }
}
?>