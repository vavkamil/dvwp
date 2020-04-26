<?php
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/
/*************************************************************
 * 
 * stats.class.php
 * 
 * Get Site Stats
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if(basename($_SERVER['SCRIPT_FILENAME']) == "stats.class.php"):
    exit;
endif;

class IWP_MMB_Stats extends IWP_MMB_Core
{
    function __construct()
    {
        parent::__construct();
    }
    
    /*************************************************************
     * FACADE functions
     * (functions to be called after a remote call from IWP Admin panel)
     **************************************************************/
    
    function get_core_update($stats, $options = array())
    {
        global $wp_version;
        
        if (isset($options['core']) && $options['core']) {
            $core = $this->iwp_mmb_get_transient('update_core');
            if (isset($core->updates) && !empty($core->updates)) {
                $current_transient = $core->updates[0];
                if ($current_transient->response == "development" || version_compare($wp_version, $current_transient->current, '<')) {
                    $current_transient->current_version = $wp_version;
                    $stats['core_updates']              = $current_transient;
                } else
                    $stats['core_updates'] = false;
            } else
                $stats['core_updates'] = false;
        }
        
        return $stats;
    }
    
    function get_hit_counter($stats, $options = array())
    {
        $iwp_mmb_user_hits = get_option('iwp_client_user_hit_count');
        if (is_array($iwp_mmb_user_hits)) {
            end($iwp_mmb_user_hits);
            $last_key_date = key($iwp_mmb_user_hits);
            $current_date  = date('Y-m-d');
            if ($last_key_date != $current_date)
                $this->set_hit_count(true);
        }
        $stats['hit_counter'] = get_option('iwp_client_user_hit_count');
        
        return $stats;
    }
    
    function get_comments($stats, $options = array())
    {
        $nposts  = ($options['numberposts'] > 0) ? (int) $options['numberposts'] : 100;
        $trimlen = isset($options['trimcontent']) ? (int) $options['trimcontent'] : 200;
        
       // if ($nposts) {
            $comments = get_comments('status=hold&number=' . $nposts);
            if (!empty($comments)) {
                foreach ($comments as &$comment) {
                    $commented_post           = get_post($comment->comment_post_ID);
                    $comment->post_title      = $commented_post->post_title;
                    $comment->comment_content = $this->trim_content($comment->comment_content, $trimlen);
                    unset($comment->comment_author_url);
                    unset($comment->comment_author_email);
                    unset($comment->comment_author_IP);
                    unset($comment->comment_date_gmt);
                    unset($comment->comment_karma);
                    unset($comment->comment_agent);
                    unset($comment->comment_type);
                    unset($comment->comment_parent);
                    unset($comment->user_id);
                }
                $stats['comments']['pending'] = $comments;
            }
            
           /* $comments = get_comments('status=approve&number=' . $nposts);
            if (!empty($comments)) {
                foreach ($comments as &$comment) {
                    $commented_post           = get_post($comment->comment_post_ID);
                    $comment->post_title      = $commented_post->post_title;
                    $comment->comment_content = $this->trim_content($comment->comment_content, $trimlen);
                    unset($comment->comment_author_url);
                    unset($comment->comment_author_email);
                    unset($comment->comment_author_IP);
                    unset($comment->comment_date_gmt);
                    unset($comment->comment_karma);
                    unset($comment->comment_agent);
                    unset($comment->comment_type);
                    unset($comment->comment_parent);
                    unset($comment->user_id);
                }
                $stats['comments']['approved'] = $comments;
            }*/
       //}
        return $stats;
    }
    
    function get_posts($stats, $options = array())
    {
        $nposts = isset($options['numberposts']) ? (int) $options['numberposts'] : 20;
        
        if ($nposts) {
            $posts        = get_posts('post_status=publish&numberposts=' . $nposts . '&orderby=post_date&order=desc');
            $recent_posts = array();
            if (!empty($posts)) {
                foreach ($posts as $id => $recent_post) {
                    $recent                 = new stdClass();
                    $recent->post_permalink = get_permalink($recent_post->ID);
                    $recent->ID             = $recent_post->ID;
                    $recent->post_date      = $recent_post->post_date;
                    $recent->post_title     = $recent_post->post_title;
                    $recent->comment_count  = (int) $recent_post->comment_count;
                    $recent_posts[]         = $recent;
                }
            }
            
            $posts                  = get_pages('post_status=publish&numberposts=' . $nposts . '&orderby=post_date&order=desc');
            $recent_pages_published = array();
            if (!empty($posts)) {
                foreach ((array) $posts as $id => $recent_page_published) {
                    $recent                 = new stdClass();
                    $recent->post_permalink = get_permalink($recent_page_published->ID);
                    
                    $recent->ID         = $recent_page_published->ID;
                    $recent->post_date  = $recent_page_published->post_date;
                    $recent->post_title = $recent_page_published->post_title;
                    
                    $recent_posts[] = $recent;
                }
            }
            if (!empty($recent_posts)) {
                usort($recent_posts, array(
                    $this,
                    'cmp_posts_client'
                ));
                $stats['posts'] = array_slice($recent_posts, 0, $nposts);
            }
        }
        return $stats;
    }
    
    function get_drafts($stats, $options = array())
    {
        $nposts = isset($options['numberposts']) ? (int) $options['numberposts'] : 20;
        
        if ($nposts) {
            $drafts        = get_posts('post_status=draft&numberposts=' . $nposts . '&orderby=post_date&order=desc');
            $recent_drafts = array();
            if (!empty($drafts)) {
                foreach ($drafts as $id => $recent_draft) {
                    $recent                 = new stdClass();
                    $recent->post_permalink = get_permalink($recent_draft->ID);
                    $recent->ID             = $recent_draft->ID;
                    $recent->post_date      = $recent_draft->post_date;
                    $recent->post_title     = $recent_draft->post_title;
                    
                    $recent_drafts[] = $recent;
                }
            }
            $drafts              = get_pages('post_status=draft&numberposts=' . $nposts . '&orderby=post_date&order=desc');
            $recent_pages_drafts = array();
            if (!empty($drafts)) {
                foreach ((array) $drafts as $id => $recent_pages_draft) {
                    $recent                 = new stdClass();
                    $recent->post_permalink = get_permalink($recent_pages_draft->ID);
                    $recent->ID             = $recent_pages_draft->ID;
                    $recent->post_date      = $recent_pages_draft->post_date;
                    $recent->post_title     = $recent_pages_draft->post_title;
                    
                    $recent_drafts[] = $recent;
                }
            }
            if (!empty($recent_drafts)) {
                usort($recent_drafts, array(
                    $this,
                    'cmp_posts_client'
                ));
                $stats['drafts'] = array_slice($recent_drafts, 0, $nposts);
            }
        }
        return $stats;
    }
    
    function get_scheduled($stats, $options = array())
    {
        $nposts = isset($options['numberposts']) ? (int) $options['numberposts'] : 20;
        
        if ($nposts) {
            $scheduled       = get_posts('post_status=future&numberposts=' . $nposts . '&orderby=post_date&order=desc');
            $scheduled_posts = array();
            if (!empty($scheduled)) {
                foreach ($scheduled as $id => $scheduled) {
                    $recent                 = new stdClass();
                    $recent->post_permalink = get_permalink($scheduled->ID);
                    $recent->ID             = $scheduled->ID;
                    $recent->post_date      = $scheduled->post_date;
                    $recent->post_title     = $scheduled->post_title;
                    $scheduled_posts[]      = $recent;
                }
            }
            $scheduled           = get_pages('post_status=future&numberposts=' . $nposts . '&orderby=post_date&order=desc');
            $recent_pages_drafts = array();
            if (!empty($scheduled)) {
                foreach ((array) $scheduled as $id => $scheduled) {
                    $recent                 = new stdClass();
                    $recent->post_permalink = get_permalink($scheduled->ID);
                    $recent->ID             = $scheduled->ID;
                    $recent->post_date      = $scheduled->post_date;
                    $recent->post_title     = $scheduled->post_title;
                    
                    $scheduled_posts[] = $recent;
                }
            }
            if (!empty($scheduled_posts)) {
                usort($scheduled_posts, array(
                    $this,
                    'cmp_posts_client'
                ));
                $stats['scheduled'] = array_slice($scheduled_posts, 0, $nposts);
            }
        }
        return $stats;
    }
    
    function get_backups($stats, $options = array())
    {
        $stats['iwp_backups']      = $this->get_backup_instance()->get_backup_stats();       
        $stats['iwp_new_backups']  = $this->get_new_backup_instance()->get_backup_stats();       
        return $stats;
    }
    
    function get_backup_req($stats = array(), $options = array())
    {
        $stats['iwp_backups']      = $this->get_backup_instance()->get_backup_stats();
        $stats['iwp_new_backups']  = $this->get_new_backup_instance()->get_backup_stats();
        $stats['iwp_next_backups'] = $this->get_backup_instance()->get_next_schedules();
        $stats['iwp_backup_req']   = $this->get_backup_instance()->check_backup_compat();
        
        return $stats;
    }
    
    function get_updates($stats, $options = array())
    {
        $upgrades = false;
        /* No need to fetch this any more 
        $premium = array();
        if (isset($options['premium']) && $options['premium']) {
            $premium_updates = array();
            $upgrades        = apply_filters('mwp_premium_update_notification', $premium_updates);
            if (!empty($upgrades)) {
				foreach( $upgrades as $data ){
					if( isset($data['Name']) )
						$premium[] = $data['Name'];
				}
                $stats['premium_updates'] = $upgrades;
                $upgrades                 = false;
            }
        }*/
        if (isset($options['themes']) && $options['themes']) {
            $this->get_installer_instance();
            $upgrades = $this->installer_instance->get_upgradable_themes();
            if (!empty($upgrades)) {
                $stats['upgradable_themes'] = $upgrades;
                $upgrades                   = false;
            }
        }
        
        if (isset($options['plugins']) && $options['plugins']) {
            $this->get_installer_instance();
            $upgrades = $this->installer_instance->get_upgradable_plugins();
            if (!empty($upgrades)) {
                $stats['upgradable_plugins'] = $upgrades;
                $upgrades                    = false;
            }
        }
          if (isset($options['translations']) && $options['translations']) {
            $this->get_installer_instance();
            $upgrades = $this->installer_instance->get_upgradable_translations();
             if (!empty($upgrades)) {
                 $stats['upgradable_translations'] = $upgrades;
                 $upgrades                         = false;
            }
        }

        if (isset($options['additional_updates']) && $options['additional_updates']) {
            $this->get_installer_instance();
            $upgrades = $this->installer_instance->get_additional_plugin_updates();
            if (!empty($upgrades)) {
                 $stats['additional_updates'] = $upgrades;
                 $upgrades                    = false;
            }
        }
        
        return $stats;
    }
    
	function get_errors($stats, $options = array())
    {
		$period = isset($options['days']) ? (int) $options['days'] * 86400 : 86400;
		$maxerrors = isset($options['max']) ? (int) $options['max'] : 100;
        $errors = array();
        if (isset($options['get']) && $options['get'] == true) {
            if (function_exists('ini_get')) {
                $logpath = ini_get('error_log');
                if (!empty($logpath) && file_exists($logpath)) {
					$logfile = @fopen($logpath, 'r');
                    if ($logfile && filesize($logpath) > 0) {
                        $maxlines = 1;
                        $linesize = -4096;
                        $lines    = array();
                        $line     = true;
                        while ($line !== false) {
                            if( fseek($logfile, ($maxlines * $linesize), SEEK_END) !== -1){
								$maxlines++;
								if ($line) {
									$line = fread($logfile, ($linesize * -1)) . $line;
									
									foreach ((array) preg_split("/(\r|\n|\r\n)/U", $line) as $l) {
										preg_match('/\[(.*)\]/Ui', $l, $match);
										if (!empty($match)) {
											$key = str_replace($match[0], '', $l);
											if(!isset($errors[$key])){
												$errors[$key] = 1;
											} else {
												$errors[$key] = $errors[$key] + 1;
											}
											
											if ((strtotime($match[1]) < ((int) time() - $period)) || count($errors) >= $maxerrors) {
												$line = false;
												break;
											}
										}
									}
								}
							} else
								break;
                        }
                    }
                    if (!empty($errors)){
						$stats['errors'] = $errors;
						$stats['logpath'] = $logpath;
						$stats['logsize'] = @filesize($logpath);
					}
                }
            }
        }
		
        return $stats;
    }
    
	function get_plugins_status($stats=array(), $options = array()){
        $installedPlugins = get_plugins();
        $activePlugins = get_option( 'active_plugins' );
        
        foreach ($installedPlugins as $installed=>$pluginDetails) {
            $pluginData = array('isInstalled' => true);
            $pluginData['name'] = $pluginDetails['Name'];
            $pluginData['pluginURI'] = $pluginDetails['PluginURI'];
            $pluginData['version'] = $pluginDetails['Version'];
            $pluginData['description'] = $pluginDetails['Description'];
            $pluginData['author'] = $pluginDetails['Author'];
            $pluginData['authorURI'] = $pluginDetails['AuthorURI'];
            $pluginData['textDomain'] = $pluginDetails['TextDomain'];
            $pluginData['domainPath'] = $pluginDetails['DomainPath'];
            $pluginData['network'] = $pluginDetails['Network'];
            $pluginData['title'] = $pluginDetails['Title'];
            $pluginData['authorName'] = $pluginDetails['AuthorName'];
            // $pluginData['']
            if (in_array($installed, $activePlugins)){
                $pluginData['isActivated'] = true;
                // $stats['plugins_status'][$installed] = array(true,true);
            }else{
                $pluginData['isActivated'] = false;
                // $stats['plugins_status'][$installed] = array(true,false);
            }
            $stats['plugins_status'][$installed] = $pluginData;
        }

        return $stats;
    }
 	function get_themes_status($stats=array(), $options = array()){
        $params = array('items'=>array('themes'));
        global $iwp_mmb_core;
        $iwp_mmb_core->get_installer_instance();
        $installedThemes = $iwp_mmb_core->installer_instance->get_themes($params);
        $stats['themes_status'] = $installedThemes;
        return $stats;
    }
    
    function pre_init_stats($params)
    {
        global $_iwp_mmb_item_filter;
        
        include_once(ABSPATH . 'wp-includes/update.php');
        include_once(ABSPATH . '/wp-admin/includes/update.php');
        
        $stats = $this->iwp_mmb_parse_action_params('pre_init_stats', $params, $this);
        $num   = extract($params);
		
		if (function_exists( 'w3tc_pgcache_flush' ) ||  function_exists( 'wp_cache_clear_cache' ) || !empty($force_refresh)) {
			$this->iwp_mmb_delete_transient('update_plugins');
			@wp_update_plugins();
			$this->iwp_mmb_delete_transient('update_themes');
			@wp_update_themes();
			$this->iwp_mmb_delete_transient('update_core');
			@wp_version_check();
		}       
        elseif ($refresh == 'transient') {
            $current = $this->iwp_mmb_get_transient('update_core');
            if (isset($current->last_checked) || get_option('iwp_client_forcerefresh')) {
				update_option('iwp_client_forcerefresh', false);
               // if (time() - $current->last_checked > 7200) { No need to check the wordpess 4hr once 
                    @wp_version_check();
                    @wp_update_plugins();
                    @wp_update_themes();
                //}
            }
        }
        
        global $wpdb, $iwp_mmb_wp_version, $iwp_mmb_plugin_dir, $wp_version, $wp_local_package;
        
		$current = get_site_transient( 'update_plugins' );
        if (isset($current->response['iwp-client/init.php'])) {
		  $r = $current->response['iwp-client/init.php'];
        }
		
		//For WPE
		$use_cookie = 0;
		if(defined('WPE_APIKEY')){
            $stats['wpe-auth']          = md5('wpe_auth_salty_dog|'.WPE_APIKEY);
        }
		
        $stats['client_version']        = IWP_MMB_CLIENT_VERSION;
        if (!empty($r)) {
            $stats['client_new_version']    = $r->new_version;
            $stats['client_new_package']    = $r->package;
        }
        $stats['wordpress_version']     = $wp_version;
        $stats['wordpress_locale_pckg'] = $wp_local_package;
        $stats['php_version']           = phpversion();
        $stats['mysql_version']         = $wpdb->db_version();
        $stats['wp_multisite']          = $this->iwp_mmb_multisite;
        $stats['network_install']       = $this->network_admin_install;
        $stats['use_cookie']            = $use_cookie;
        $stats['maintenance_mode']      = get_option('iwp_mmb_maintenance_mode');
        $stats['site_home']             = get_option('home');
        $stats['site_url']              = get_option('siteurl');
        
        if ( !function_exists('get_filesystem_method') )
            include_once(ABSPATH . 'wp-admin/includes/file.php');
        $mmode = get_option('iwp_client_maintenace_mode');
		
		if( !empty($mmode) && isset($mmode['active']) && $mmode['active'] == true){
			$stats['maintenance'] = true;
		}
        $stats['writable'] = $this->is_server_writable();
        if ($this->iwp_mmb_multisite) {
            $details = get_blog_details($this->iwp_mmb_multisite);
            if (isset($details->site_id)) {
                $details = get_blog_details($details->site_id);
                if (isset($details->siteurl))
                    $stats['network_parent'] = $details->siteurl;
            }
        }
        if ($this->iwp_mmb_multisite) {
            $stats = array_merge($stats, $this->get_multisite_stats());
        }
        return $stats;
    }
    
    function get($params)
    {
        global $wpdb, $iwp_mmb_wp_version, $iwp_mmb_plugin_dir, $_iwp_mmb_item_filter;
        
        include_once(ABSPATH . 'wp-includes/update.php');
        include_once(ABSPATH . '/wp-admin/includes/update.php');
        
        $stats = $this->iwp_mmb_parse_action_params('get', $params, $this);
		$update_check = array();
        $num          = extract($params);
        if ($refresh == 'transient') {
           // $update_check = apply_filters('mwp_premium_update_check', $update_check);
            if (!empty($update_check)) {
                foreach ($update_check as $update) {
                    if (is_array($update['callback'])) {
                        $update_result = call_user_func(array(
                            $update['callback'][0],
                            $update['callback'][1]
                        ));
                    } else if (is_string($update['callback'])) {
                        $update_result = call_user_func($update['callback']);
                    }
                }
            }
        }
        
        if ($this->iwp_mmb_multisite) {
            $stats = $this->get_multisite($stats);
        }
        
        $stats = apply_filters('iwp_mmb_stats_filter', $stats);
        return $stats;
    }
    
    function get_multisite($stats = array())
    {
        global $current_user, $wpdb;
        $user_blogs = get_blogs_of_user( $current_user->ID );
		$network_blogs = $wpdb->get_results( "select `blog_id`, `site_id` from `{$wpdb->blogs}`" );
		if ($this->network_admin_install == '1' && is_super_admin()) {
			if (!empty($network_blogs)) {
                $blogs = array();
                foreach ( $network_blogs as $details) {
                    if($details->site_id == $details->blog_id)
						continue;
					else {
						$data = get_blog_details($details->blog_id);
						if(in_array($details->blog_id, array_keys($user_blogs)))
							$stats['network_blogs'][] = $data->siteurl;
						else {
							$user = get_users( array( 'blog_id' => $details->blog_id, 'number' => 1) );
							if( !empty($user) )
								$stats['other_blogs'][$data->siteurl] = $user[0]->user_login;
						}
					}
                }
            }
        }
        return $stats;
    }
    
    function get_comments_stats()
    {
        $num_pending_comments  = 3;
        $num_approved_comments = 3;
        $pending_comments      = get_comments('status=hold&number=' . $num_pending_comments);
        foreach ($pending_comments as &$comment) {
            $commented_post      = get_post($comment->comment_post_ID);
            $comment->post_title = $commented_post->post_title;
        }
        $stats['comments']['pending'] = $pending_comments;
        
        
        $approved_comments = get_comments('status=approve&number=' . $num_approved_comments);
        foreach ($approved_comments as &$comment) {
            $commented_post      = get_post($comment->comment_post_ID);
            $comment->post_title = $commented_post->post_title;
        }
        $stats['comments']['approved'] = $approved_comments;
        
        return $stats;
    }
    
    function get_initial_stats()
    {
        global $iwp_mmb_plugin_dir;
        
        $stats = array();
        
		$current = get_site_transient( 'update_plugins' );
		$r = $current->response['iwp-client/init.php'];
		//For BWP
		$bwp = get_option("bit51_bwps");
		$wp_admin_URL=admin_url();
		if(!empty($bwp))
		{
			if($bwp['hb_enabled']==1)
			$wp_admin_URL = admin_url()."?".$bwp['hb_key'];
		
			
		}
		
		//For WPE
		$use_cookie = 0;
		if(@getenv('IS_WPE'))
		$use_cookie=1;
		
        $stats['email']           			= get_option('admin_email');
        $stats['no_openssl']      			= $this->get_random_signature();
        $stats['content_path']    			= WP_CONTENT_DIR;
        $stats['client_path']     			= $iwp_mmb_plugin_dir;
        $stats['client_version'] 			= IWP_MMB_CLIENT_VERSION;
		$stats['client_new_version']        = $r->new_version;
		$stats['client_new_package']       	= $r->package;
        $stats['site_title']      			= get_bloginfo('name');
        $stats['site_tagline']    			= get_bloginfo('description');
        $stats['site_home']       			= get_option('home');
        $stats['site_url']                  = get_option('siteurl');
        $stats['admin_url']      			= $wp_admin_URL;
        $stats['wp_multisite']    			= $this->iwp_mmb_multisite;
        $stats['network_install'] 			= $this->network_admin_install;
		$stats['use_cookie'] 				= $use_cookie;

	
        
        if ($this->iwp_mmb_multisite) {
            $details = get_blog_details($this->iwp_mmb_multisite);
            if (isset($details->site_id)) {
                $details = get_blog_details($details->site_id);
                if (isset($details->siteurl))
                    $stats['network_parent'] = $details->siteurl;
            }
        }
        if (!function_exists('get_filesystem_method'))
            include_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $stats['writable'] = $this->is_server_writable();
         if ($this->iwp_mmb_multisite) {
            $stats = array_merge($stats, $this->get_multisite_stats());
        }
        return $stats;
    }

    public function get_multisite_stats()
    {
        /** @var $wpdb wpdb */
        global $current_user, $wpdb;
        $user_blogs    = get_blogs_of_user($current_user->ID);
        $network_blogs = (array)$wpdb->get_results("select `blog_id`, `site_id` from `{$wpdb->blogs}`");
        $mainBlogId    = defined('BLOG_ID_CURRENT_SITE') ? BLOG_ID_CURRENT_SITE : false;

        if (/*$this->network_admin_install != '1' || !is_super_admin($current_user->ID)||*/ empty($network_blogs)) {
            return array();
        }

        $stats = array('network_blogs' => array(), 'other_blogs' => array());
        foreach ($network_blogs as $details) {
            if (($mainBlogId !== false && $details->blog_id == $mainBlogId) || ($mainBlogId === false && $details->site_id == $details->blog_id)) {
                continue;
            } else {
                $data = get_blog_details($details->blog_id);
                if (in_array($details->blog_id, array_keys($user_blogs))) {
                    $stats['network_blogs'][] = $data->siteurl;
                } else {
                    $user = get_users(
                        array(
                            'blog_id' => $details->blog_id,
                            'number'  => 1,
                        )
                    );
                    if (!empty($user)) {
                        $stats['other_blogs'][$data->siteurl] = $user[0]->user_login;
                    }
                }
            }
        }

        return $stats;
    }

    
    public static function set_hit_count($fix_count = false)
    {
    	global $iwp_mmb_core;
        if ($fix_count || (!is_admin() && !IWP_MMB_Stats::is_bot())) {
            $date           = date('Y-m-d');
            $iwp_client_user_hit_count = (array) get_option('iwp_client_user_hit_count');
            if (!$iwp_client_user_hit_count) {
                $iwp_client_user_hit_count[$date] = 1;
                update_option('iwp_client_user_hit_count', $iwp_client_user_hit_count);
            } else {
                $dated_keys      = array_keys($iwp_client_user_hit_count);
                $last_visit_date = $dated_keys[count($dated_keys) - 1];
                
                $days = intval((strtotime($date) - strtotime($last_visit_date)) / 60 / 60 / 24);
                
                if ($days > 1) {
                    $date_to_add = date('Y-m-d', strtotime($last_visit_date));
                    
                    for ($i = 1; $i < $days; $i++) {
                        if (count($iwp_client_user_hit_count) > 14) {
                            $shifted = @array_shift($iwp_client_user_hit_count);
                        }
                        
                        $next_key = strtotime('+1 day', strtotime($date_to_add));
                        if ($next_key == $date) {
                            break;
                        } else {
                            $iwp_client_user_hit_count[$next_key] = 0;
                        }
                    }
                    
                }
                
                if (!isset($iwp_client_user_hit_count[$date])) {
                    $iwp_client_user_hit_count[$date] = 0;
                }
                if (!$fix_count)
                    $iwp_client_user_hit_count[$date] = ((int) $iwp_client_user_hit_count[$date]) + 1;
                
                if (count($iwp_client_user_hit_count) > 14) {
                    $shifted = @array_shift($iwp_client_user_hit_count);
                }
                
                update_option('iwp_client_user_hit_count', $iwp_client_user_hit_count);
                
            }
        }
    }
    
    function get_hit_count()
    {
        // Check if there are no hits on last key date
        $iwp_mmb_user_hits = get_option('iwp_client_user_hit_count');
        if (is_array($iwp_mmb_user_hits)) {
            end($iwp_mmb_user_hits);
            $last_key_date = key($iwp_mmb_user_hits);
            $current_date  = date('Y-m-d');
            if ($last_key_date != $curent_date)
                $this->set_hit_count(true);
        }
        
        return get_option('iwp_client_user_hit_count');
    }
    
    public static function is_bot()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        
        if ($agent == '')
            return false;
        
        $bot_list = array(
            "Teoma",
            "alexa",
            "froogle",
            "Gigabot",
            "inktomi",
            "looksmart",
            "URL_Spider_SQL",
            "Firefly",
            "NationalDirectory",
            "Ask Jeeves",
            "TECNOSEEK",
            "InfoSeek",
            "WebFindBot",
            "girafabot",
            "crawler",
            "www.galaxy.com",
            "Googlebot",
            "Scooter",
            "Slurp",
            "msnbot",
            "appie",
            "FAST",
            "WebBug",
            "Spade",
            "ZyBorg",
            "rabaz",
            "Baiduspider",
            "Feedfetcher-Google",
            "TechnoratiSnoop",
            "Rankivabot",
            "Mediapartners-Google",
            "Sogou web spider",
            "WebAlta Crawler",
            "aolserver"
        );
        
        foreach ($bot_list as $bot)
            if (strpos($agent, $bot) !== false)
                return true;
        
        return false;
    }
    
    
    function set_notifications($params)
    {
        if (empty($params))
            return false;
        
        extract($params);
        
        if (!isset($delete)) {
            $iwp_client_notifications = array(
                'plugins' => $plugins,
                'themes' => $themes,
                'wp' => $wp,
                'backups' => $backups,
                'url' => $url,
                'notification_key' => $notification_key
            );
            update_option('iwp_client_notifications', $iwp_client_notifications);
        } else {
            delete_option('iwp_client_notifications');
        }
        
        return true;
        
    }
    
    //Cron update check for notifications
    function check_notifications()
    {
        global $wpdb, $iwp_mmb_wp_version, $iwp_mmb_plugin_dir, $wp_version, $wp_local_package;
        
        $iwp_client_notifications = get_option('iwp_client_notifications', true);
        
        $args         = array();
        $updates           = array();
        $send = 0;
        if (is_array($iwp_client_notifications) && $iwp_client_notifications != false) {
            include_once(ABSPATH . 'wp-includes/update.php');
            include_once(ABSPATH . '/wp-admin/includes/update.php');
            extract($iwp_client_notifications);
            
            //Check wordpress core updates
            if ($wp) {
                @wp_version_check();
                if (function_exists('get_core_updates')) {
                    $wp_updates = get_core_updates();
                    if (!empty($wp_updates)) {
                        $current_transient = $wp_updates[0];
                        if ($current_transient->response == "development" || version_compare($wp_version, $current_transient->current, '<')) {
                            $current_transient->current_version = $wp_version;
                            $updates['core_updates']            = $current_transient;
                        } else
                            $updates['core_updates'] = array();
                    } else
                        $updates['core_updates'] = array();
                }
            }
            
            //Check plugin updates
            if ($plugins) {
                @wp_update_plugins();
                $this->get_installer_instance();
                $updates['upgradable_plugins'] = $this->installer_instance->get_upgradable_plugins();
            }
            
            //Check theme updates
            if ($themes) {
                @wp_update_themes();
                $this->get_installer_instance();
                
                $updates['upgradable_themes'] = $this->installer_instance->get_upgradable_themes();
            }
            
            if ($backups) {
                $this->get_backup_instance();
                $backups            = $this->backup_instance->get_backup_stats();
                $updates['backups'] = $backups;
                foreach ($backups as $task_name => $backup_results) {
                    foreach ($backup_results as $k => $backup) {
                        if (isset($backups[$task_name][$k]['server']['file_path'])) {
                            unset($backups[$task_name][$k]['server']['file_path']);
                        }
                    }
                }
                $updates['backups'] = $backups;
            }
            
            
            if (!empty($updates)) {
                $args['body']['updates'] = $updates;
                $args['body']['notification_key'] = $notification_key;
                $send = 1;
            }
            
        }
        
        
        $alert_data = get_option('iwp_client_pageview_alerts',true);
        if(is_array($alert_data) && $alert_data['alert']){
        	$pageviews = get_option('iwp_client_user_hit_count');
        	$args['body']['alerts']['pageviews'] = $pageviews;
        	$args['body']['alerts']['site_id'] = $alert_data['site_id'];
        	if(!isset($url)){
        		$url = $alert_data['url'];
        	}
        	$send = 1;
        }
        
        if($send){
        	if (!class_exists('WP_Http')) {
                include_once(ABSPATH . WPINC . '/class-http.php');
            }
        	$result       = wp_remote_post($url, $args);
        	
        	if (is_array($result) && $result['body'] == 'iwp_delete_alert') {
        		delete_option('iwp_client_pageview_alerts');
        	}
        }  
    }
    
    
    function cmp_posts_client($a, $b)
    {
        return ($a->post_date < $b->post_date);
    }
    
    function trim_content($content = '', $length = 200)
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr'))
            $content = (mb_strlen($content) > ($length + 3)) ? mb_substr($content, 0, $length) . '...' : $content;
        else
            $content = (strlen($content) > ($length + 3)) ? substr($content, 0, $length) . '...' : $content;
        
        return $content;
    }
    
    function set_alerts($args){
    	extract($args);
    	update_option('iwp_client_pageview_alerts',array('site_id' => $site_id,'alert' => $alert,'url' => $url));
    }
    
	public static function readd_alerts( $params = array() ){
		if( empty($params) || !isset($params['alerts']))
			return $params;
			
		if( !empty($params['alerts']) ){
			update_option('iwp_client_pageview_alerts', $params['alerts']);
			unset($params['alerts']);
		}
		
		return $params;
	}
 }
    
if( function_exists('add_filter') ){ 
	add_filter( 'iwp_website_add', 'IWP_MMB_Stats::readd_alerts' );
}
?>