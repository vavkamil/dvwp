<?php
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/
/*************************************************************
 * 
 * core.class.php
 * 
 * Upgrade Plugins
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
 if(basename($_SERVER['SCRIPT_FILENAME']) == "core.class.php"):
    exit;
endif;
class IWP_MMB_Core extends IWP_MMB_Helper
{
    var $name;
    var $slug;
    var $settings;
    var $remote_client;
    var $comment_instance;
    var $plugin_instance;
    var $theme_instance;
    var $wp_instance;
    var $post_instance;
    var $stats_instance;
    var $search_instance;
    var $links_instance;
    var $user_instance;
    var $backup_instance;
    var $backup_new_instance;
	var $wordfence_instance;
	var $sucuri_instance;
    var $installer_instance;
    var $iwp_mmb_multisite;
    var $network_admin_install;
	
	var $ithemessec_instance;
	var $backup_repository_instance;
	var $optimize_instance;
	var $wp_purge_cache_instance;
	
    private $action_call;
    public  $request_params;
    public  $error_notice;
    private $action_params;
    private $iwp_mmb_pre_init_actions;
    private $iwp_mmb_pre_init_filters;
    private $iwp_mmb_init_actions;
    
    
    function __construct()
    {
        global $iwp_mmb_plugin_dir, $wpmu_version, $blog_id, $_iwp_mmb_plugin_actions, $_iwp_mmb_item_filter;
        
		$_iwp_mmb_plugin_actions = array();
        $this->name     = 'Manage Multiple Blogs';
        $this->slug     = 'manage-multiple-blogs';
		$this->action_call = null;
		$this->action_params = null;
		
		
        $this->settings = get_option($this->slug);
        if (!$this->settings) {
            $this->settings = array(
                'blogs' => array(),
                'current_blog' => array(
                    'type' => null
                )
            );
            $this->save_options();
        }
		if ( function_exists('is_multisite') ) {
            if ( is_multisite() ) {
                $this->iwp_mmb_multisite = $blog_id;
                $this->network_admin_install = get_option('iwp_client_network_admin_install');
                add_action('wpmu_new_blog', array(&$this, 'updateKeys'));
            }
        } else if (!empty($wpmu_version)) {
            $this->iwp_mmb_multisite = $blog_id;
            $this->network_admin_install = get_option('iwp_client_network_admin_install');
        } else {
			$this->iwp_mmb_multisite = false;
			$this->network_admin_install = null;
		}
		
		// admin notices
		if ( !get_option('iwp_client_public_key') ){
			if( $this->iwp_mmb_multisite ){
				if( is_network_admin() && $this->network_admin_install == '1'){
					//add_action('network_admin_notices', array( &$this, 'network_admin_notice' ));// We implemented network activation so no need to show this notification
					add_action('network_admin_notices', array( &$this, 'admin_notice' ));
				} else if( $this->network_admin_install != '1' ){
					//$parent_key = $this->get_parent_blog_option('iwp_client_public_key');//IWP commented to show notice to all subsites of network
					//if(empty($parent_key))//IWP commented to show notice to all subsites of network
					 $parent_key = $this->get_parent_blog_option('iwp_client_public_key');
                    if (empty($parent_key)) {
                        add_action('admin_notices', array(&$this, 'admin_notice'));
                    }
				}
			} else {
				add_action('admin_notices', array( &$this, 'admin_notice' ));
			}
		}
		
		// default filters
		//$this->iwp_mmb_pre_init_filters['get_stats']['iwp_mmb_stats_filter'][] = array('IWP_MMB_Stats', 'pre_init_stats'); // called with class name, use global $iwp_mmb_core inside the function instead of $this
		$this->iwp_mmb_pre_init_filters['get_stats']['iwp_mmb_stats_filter'][] = 'iwp_mmb_pre_init_stats';
		
		$_iwp_mmb_item_filter['pre_init_stats'] = array( 'core_update', 'hit_counter', 'comments', 'backups', 'posts', 'drafts', 'scheduled' );
		$_iwp_mmb_item_filter['get'] = array( 'updates', 'errors','plugins_status','themes_status' );
		
		$this->iwp_mmb_pre_init_actions = array(
			'backup_req' => 'iwp_mmb_get_backup_req',
		);
		
		$this->iwp_mmb_init_actions = array(
			'do_upgrade' => 'iwp_mmb_do_upgrade',
			'get_stats' => 'iwp_mmb_stats_get',
			'remove_site' => 'iwp_mmb_remove_site',
			'backup_clone' => 'iwp_mmb_backup_now',
			'restore' => 'iwp_mmb_restore_now',
			'optimize_tables' => 'iwp_mmb_optimize_tables',
			'check_wp_version' => 'iwp_mmb_wp_checkversion',
			'create_post' => 'iwp_mmb_post_create',
			'update_client' => 'iwp_mmb_update_client_plugin',
			
			'change_comment_status' => 'iwp_mmb_change_comment_status',
			'change_post_status' => 'iwp_mmb_change_post_status',
			'get_comment_stats' => 'iwp_mmb_comment_stats_get',
			
			'get_links' => 'iwp_mmb_get_links',
			'add_link' => 'iwp_mmb_add_link',
			'delete_link' => 'iwp_mmb_delete_link',
			'delete_links' => 'iwp_mmb_delete_links',
			
			'create_post' => 'iwp_mmb_post_create',
			'change_post_status' => 'iwp_mmb_change_post_status',
			'get_posts' => 'iwp_mmb_get_posts',
			'delete_post' => 'iwp_mmb_delete_post',
			'delete_posts' => 'iwp_mmb_delete_posts',
			'edit_posts' => 'iwp_mmb_edit_posts',
			'get_pages' => 'iwp_mmb_get_pages',
			'delete_page' => 'iwp_mmb_delete_page',
			
			'install_addon' => 'iwp_mmb_install_addon',
			'add_link' => 'iwp_mmb_add_link',
			'add_user' => 'iwp_mmb_add_user',
			'email_backup' => 'iwp_mmb_email_backup',
			'check_backup_compat' => 'iwp_mmb_check_backup_compat',
			'scheduled_backup' => 'iwp_mmb_scheduled_backup',
			'new_scheduled_backup' => 'iwp_mmb_new_scheduled_backup',
			'run_task' => 'iwp_mmb_run_task_now',
			'new_run_task' => 'iwp_mmb_new_run_task_now',
			'delete_schedule_task' => 'iwp_mmb_delete_task_now',
			'execute_php_code' => 'iwp_mmb_execute_php_code',
			'delete_backup' => 'iwp_mmb_delete_backup',
			'delete_backup_new' => 'iwp_mmb_delete_backup_new',
			'kill_new_backup' => 'iwp_mmb_kill_new_backup',
			'remote_backup_now' => 'iwp_mmb_remote_backup_now',
			'set_notifications' => 'iwp_mmb_set_notifications',
			'clean_orphan_backups' => 'iwp_mmb_clean_orphan_backups',
			'get_users' => 'iwp_mmb_get_users',
			'edit_users' => 'iwp_mmb_edit_users',
			'get_plugins_themes' => 'iwp_mmb_get_plugins_themes',
			'edit_plugins_themes' => 'iwp_mmb_edit_plugins_themes',
			'get_comments' => 'iwp_mmb_get_comments',
			'action_comment' => 'iwp_mmb_action_comment',
			'bulk_action_comments' => 'iwp_mmb_bulk_action_comments',
			'replyto_comment' => 'iwp_mmb_reply_comment',
			'client_brand' => 'iwp_mmb_client_brand',
			'set_alerts' => 'iwp_mmb_set_alerts',
			'maintenance' => 'iwp_mmb_maintenance_mode',
			
			'wp_optimize' => 'iwp_mmb_wp_optimize',
			'wp_purge_cache' => 'iwp_mmb_wp_purge_cache',
			
			'backup_repository' => 'iwp_mmb_backup_repository',
			'trigger_backup_multi' => 'iwp_mmb_trigger_check',
			'trigger_backup_multi_new' => 'iwp_mmb_trigger_check_new',
			'get_all_links'         => 'iwp_mmb_get_all_links',
            'update_broken_link'    => 'iwp_mmb_update_broken_link',
            'unlink_broken_link'    => 'iwp_mmb_unlink_broken_link',
            'markasnot_broken_link' => 'iwp_mmb_markasnot_broken_link',
            'dismiss_broken_link' => 'iwp_mmb_dismiss_broken_link',
            'undismiss_broken_link' => 'iwp_mmb_undismiss_broken_link',
            'bulk_actions_processor' => 'iwp_mmb_bulk_actions_processor',

            'file_editor_upload'    => 'iwp_mmb_file_editor_upload',

            'put_redirect_url'      =>  'iwp_mmb_gwmt_redirect_url',
            'put_redirect_url_again'=>  'iwp_mmb_gwmt_redirect_url_again',
			'wordfence_scan' => 'iwp_mmb_wordfence_scan',
			'wordfence_load' => 'iwp_mmb_wordfence_load',
			'sucuri_fetch_result' => 'iwp_mmb_sucuri_fetch_result',
			'backup_test_site' => 'iwp_mmb_backup_test_site',
			'ithemes_security_check' => 'iwp_phx_ithemes_security_check',
			'get_seo_info' => 'iwp_mmb_yoast_get_seo_info',
			'save_seo_info' => 'iwp_mmb_yoast_save_seo_info',
			'fetch_activities_log' => 'iwp_mmb_fetch_activities_log',
			'sucuri_scan' => 'iwp_mmb_sucuri_scan',
			'sucuri_change_alert' => 'iwp_mmb_sucuri_change_alert',
			'backup_downlaod' => 'iwp_mmb_backup_downlaod',
			'cronDoAction' => 'iwp_pheonix_backup_cron_do_action',
			'get_additional_stats' => 'iwp_get_additional_stats',
			'get_db_details' => 'iwp_get_db_details'
		);
		
		add_action('rightnow_end', array( &$this, 'add_right_now_info' )); 
		if( $this->iwp_mmb_multisite ){
			add_action('network_admin_menu', array($this,'iwp_admin_menu_actions'), 10, 1);
		}else{
			add_action('admin_menu', array($this,'iwp_admin_menu_actions'), 10, 1);
		}      
		add_action('init', array($this,'iwp_cpb_hide_updates'), 10, 1);
		add_action('admin_init', array(&$this,'admin_actions'));   
		add_action('admin_init', array(&$this,'enqueueConnectionModalOpenScripts'));   
		add_action('admin_init', array(&$this,'enqueueConnectionModalOpenStyles'));   
		add_filter('deprecated_function_trigger_error', '__return_false');
		add_filter('plugin_row_meta', array($this, 'addConnectionKeyLink'), 10, 2);
		add_action('admin_head', array($this, 'printConnectionModalOpenScript'));
        add_action('admin_footer', array($this, 'printConnectionModalDialog'));
		// add_action('wp_loaded', array( &$this, 'iwp_mmb_remote_action'), 2147483650);
		add_action('setup_theme', 'iwp_mmb_set_request');
		add_action('setup_theme', 'iwp_mmb_add_readd_request');
		add_action('set_auth_cookie', array( &$this, 'iwp_mmb_set_auth_cookie'));
		add_action('wp_loaded', array( &$this, 'load_mu_loader_error'));
		add_action('set_logged_in_cookie', array( &$this, 'iwp_mmb_set_logged_in_cookie'));
		
    }
    
	function admin_wp_loaded_iwp(){
        if (!defined('WP_ADMIN')) {
        	define('WP_ADMIN', true);
        }
        if (is_multisite() && !defined('WP_NETWORK_ADMIN')) {
        	define('WP_NETWORK_ADMIN', true);
        }
        if (!defined('WP_BLOG_ADMIN')) {
        	define('WP_BLOG_ADMIN', true);
        }
        require_once ABSPATH.'wp-admin/includes/admin.php';
        // define('DOING_AJAX', true);
        do_action('admin_init');
        if (function_exists('wp_clean_update_cache')) {
            /** @handled function */
            wp_clean_update_cache();
        }

        /** @handled function */
        wp_update_plugins();

        /** @handled function */
        set_current_screen();
        do_action('load-update-core.php');

        /** @handled function */
        wp_version_check();

        /** @handled function */
        wp_version_check(array(), true);
    }
    	
	function iwp_mmb_remote_action(){
		global $iwp_mmb_core;
		if (!empty($iwp_mmb_core->request_params)) {
			$params = $iwp_mmb_core->request_params;
			$action = $iwp_mmb_core->request_params['iwp_action'];
			if( isset($this->iwp_mmb_pre_init_filters[$action]) && !empty($this->iwp_mmb_pre_init_filters[$action])){
				global $iwp_mmb_filters;
				foreach($this->iwp_mmb_pre_init_filters[$action] as $_name => $_functions){
					if(!empty($_functions)){
						$data = array();
						
						foreach($_functions as $_k => $_callback){
							if(is_array($_callback) && method_exists($_callback[0], $_callback[1]) ){
								$data = call_user_func( $_callback, $params );
							} elseif (is_string($_callback) && function_exists( $_callback )){
								$data = call_user_func( $_callback, $params );
							}
							$iwp_mmb_filters[$_name] = isset($iwp_mmb_filters[$_name]) && !empty($iwp_mmb_filters[$_name]) ? array_merge($iwp_mmb_filters[$_name], $data) : $data;
							add_filter( $_name, create_function( '$a' , 'global $iwp_mmb_filters; return array_merge($a, $iwp_mmb_filters["'.$_name.'"]);') );
						}
					}
					
				}
			}
		}
		if($this->action_call != null){
			$params = isset($this->action_params) && $this->action_params != null ? $this->action_params : array();
			call_user_func($this->action_call, $params);
		}
	}
	
	function register_action_params( $action = false, $params = array() ){
		if ($action == 'get_stats' || $action == 'do_upgrade') {
			add_action('wp_loaded', array( &$this, 'iwp_mmb_remote_action'), 2147483650);
			add_action('wp_loaded', array( &$this, 'admin_wp_loaded_iwp'), 2147483649);
		}elseif ($action == 'install_addon') {
			add_action('wp_loaded', array( &$this, 'iwp_mmb_remote_action'));
		}elseif ($action == 'new_run_task' || $action == 'new_scheduled_backup') {
			add_action('after_setup_theme', array( &$this, 'iwp_mmb_remote_action'), 9999);
		}else{
			add_action('init', array( &$this, 'iwp_mmb_remote_action'), 9999);
		}
		
		if(isset($this->iwp_mmb_pre_init_actions[$action]) && function_exists($this->iwp_mmb_pre_init_actions[$action])){
			call_user_func($this->iwp_mmb_pre_init_actions[$action], $params);
		}
		
		if(isset($this->iwp_mmb_init_actions[$action]) && function_exists($this->iwp_mmb_init_actions[$action])){
			$this->action_call = $this->iwp_mmb_init_actions[$action];
			$this->action_params = $params;
			return true;
		} 
		return false;
	}
	
    /**
     * Add notice to network admin dashboard for security reasons    
     * 
     */
    function network_admin_notice()
    {
        echo '<div class="error" style="text-align: center;"><p style="font-size: 14px; font-weight: bold; color:#c00;">Attention !</p>
		<p>The InfiniteWP client plugin has to be activated on individual sites. Kindly deactivate the plugin from the network admin dashboard and activate them from the individual dashboards.</p></div>';
    }
	
		
	/**
     * Add notice to admin dashboard for security reasons    
     * 
     */
    function admin_notice()
    {
       /* IWP */
		if(defined('MULTISITE') && MULTISITE == true){	
			global $blog_id;			
			$details = get_user_by( 'email',get_blog_option($blog_id, 'admin_email'));
			//$details = get_userdata($user_id_from_email->ID);
			$username = $details->user_login;				
		}
		else{
			$current_user = wp_get_current_user(); 
			$username = $current_user->data->user_login;
		}	
		
		$iwp_client_activate_key = get_option('iwp_client_activate_key');
		if (!is_admin()) {
			return false;
		}
		//check BWP 
		$bwp = get_option("bit51_bwps");
		$notice_display_URL=admin_url();
		if(!empty($bwp))
		{
			//$bwpArray = @unserialize($bwp);
			if($bwp['hb_enabled']==1)
			$notice_display_URL = get_option('home');
		}
		
		$notice_display_URL = rtrim($notice_display_URL, '/').'/';
		
		
		echo '<div class="updated" style="text-align: center; display:block !important; "><p style="color: green; font-size: 14px; font-weight: bold;">Add this site to IWP Admin panel</p><p>
		<table border="0" align="center" cellpadding="5">';
		if(!empty($iwp_client_activate_key)){
			echo '<tr><td align="right">WP-ADMIN URL:</td><td align="left"><strong>'.$notice_display_URL.'</strong></td></tr>
			<tr><td align="right">ADMIN USERNAME:</td><td align="left"><strong>'.$username.'</strong> (or any admin id)</td></tr>
            <tr><td align="right">ACTIVATION KEY:</td><td align="left"><strong>'.$iwp_client_activate_key.'</strong></td></tr>
            <tr class="only_flash"><td></td><td align="left" style="position:relative;">
            <tr id="copy_at_once"><td align="right">To quick add, copy this</td><td align="left" style="position:relative;"><input type="text" style="width:295px;" class="read_creds" readonly value="'.$notice_display_URL.'|^|'.$username.'|^|'.$iwp_client_activate_key.'" /></td></tr>
            <tr class="only_flash"><td></td><td align="left" style="position:relative;"><div id="copy_details"  data-clipboard-text="'.$notice_display_URL.'|^|'.$username.'|^|'.$iwp_client_activate_key.'" style="background:#008000;display: inline-block;padding: 4px 10px;border-radius: 5px;color:#fff;font-weight:600;cursor:pointer;">Copy details</div><span class="copy_message" style="display:none;margin-left:10px;color:#008000;">Copied :)</span></td></tr>

            <script type="text/javascript">
                  (function(){
                  	var onhoverMsg = "<span class=\"aftercopy_instruction\" style=\"position: absolute;top: 32px;left:20px;background:#fff;border:1px solid #000;-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius: 5px;padding:2px;margin:2px;text-align:center;\">Paste this in any field in the Add Website dialogue in the InfiniteWP admin panel.</span>";
                      var clipboard = new Clipboard("#copy_details");
                      if (clipboard != undefined) {
	                      clipboard.on("success", function(e) {
	                      	jQuery(".copy_message").show();
	                      	setTimeout(\'jQuery(".copy_message").hide();\',1000);

	                          e.clearSelection();

	                      });
	                      clipboard.on("error", function(e) {
	                      	jQuery(".only_flash").remove();
	                      	jQuery(".read_creds").click(function(){jQuery(this).select();});
	                      });
	                       jQuery("#copy_at_once").hide();
	                       jQuery("#copy_details").mouseenter(function(){jQuery(onhoverMsg).appendTo(jQuery(this).parent());}).mouseleave(function(){jQuery(".aftercopy_instruction").remove();});
                      }else{
                      	jQuery(".only_flash").remove();
                      	jQuery(".read_creds").click(function(){jQuery(this).select();});
                      	jQuery(".read_creds").mouseenter(function(e){jQuery(onhoverMsg).appendTo(jQuery(this).parent());}).mouseleave(function(){jQuery(".aftercopy_instruction").remove();});
                      }
               	 })();

            </script>';
		}
		else{
			echo '<tr><td align="center">Please deactivate and then activate InfiniteWP Client plugin.</td></tr>';
		}		
		
		echo '</table>
	  	</p></div>';		
		
    }
    
    /**
     * Add an item into the Right Now Dashboard widget 
     * to inform that the blog can be managed remotely
     * 
     */
    function add_right_now_info()
    {
        echo '<div class="iwp_mmb-slave-info">
            <p>This site can be managed remotely.</p>
        </div>';
    }
    
    /**
     * Get parent blog options
     * 
     */
    private function get_parent_blog_option( $option_name = '' )
    {
		global $wpdb;
		$option = $wpdb->get_var( $wpdb->prepare( "SELECT `option_value` FROM {$wpdb->base_prefix}options WHERE option_name = %s LIMIT 1", $option_name ) );
        return $option;
    }
    
	
	/**
     * Gets an instance of the WP_Optimize class
     * 
     */
    function wp_optimize_instance()
    {
        if (!isset($this->optimize_instance)) {
            $this->optimize_instance = new IWP_MMB_Optimize();
        }
        
        return $this->optimize_instance;
    }
    
     function wp_purge_cache_instance()
    {
    	global $iwp_mmb_plugin_dir;
    	require_once("$iwp_mmb_plugin_dir/addons/wp_optimize/purge-plugins-cache-class.php");
        if (!isset($this->wp_purge_cache_instance)) {
            $this->wp_purge_cache_instance = new IWP_MMB_PURGE_CACHE();
        }
        
        return $this->wp_purge_cache_instance;
    }
    /**
     * Gets an instance of the WP_BrokenLinks class
     * 
     */
    function wp_blc_get_blinks()
    {
        global $iwp_mmb_plugin_dir;
	require_once("$iwp_mmb_plugin_dir/addons/brokenlinks/brokenlinks.class.php");
        if (!isset($this->blc_get_blinks)) {
            $this->blc_get_blinks = new IWP_MMB_BLC();
        }
        
        return $this->blc_get_blinks;
    }
    

    /**
     * Gets an instance of the WP_BrokenLinks class
     * 
     */
    function wp_google_webmasters_crawls()
    {
        global $iwp_mmb_plugin_dir;
        require_once("$iwp_mmb_plugin_dir/addons/google_webmasters/google_webmasters.class.php");
		if (!isset($this->get_google_webmasters_crawls)) {
            $this->get_google_webmasters_crawls = new IWP_MMB_GWMT();
        }
        
        return $this->get_google_webmasters_crawls;
    }
    
    /**
     * Gets an instance of the fileEditor class
     * 
     */
    function wp_get_file_editor()
    {
        global $iwp_mmb_plugin_dir;
        require_once("$iwp_mmb_plugin_dir/addons/file_editor/file_editor.class.php");
		if (!isset($this->get_file_editor)) {
            $this->get_file_editor = new IWP_MMB_fileEditor();
        }
        
        return $this->get_file_editor;
    }
    
   
    /**
     * Gets an instance of the yoastWpSeo class
     * 
     */
    function wp_get_yoast_seo()
    {
        global $iwp_mmb_plugin_dir;
        require_once("$iwp_mmb_plugin_dir/addons/yoast_wp_seo/yoast_wp_seo.class.php");
		if (!isset($this->get_yoast_seo)) {
            $this->get_yoast_seo = new IWP_MMB_YWPSEO();
        }
        
        return $this->get_yoast_seo;
    }
    

    /**
     * Gets an instance of the Comment class
     * 
     */
    function get_comment_instance()
    {
        if (!isset($this->comment_instance)) {
            $this->comment_instance = new IWP_MMB_Comment();
        }
        
        return $this->comment_instance;
    }
    
    /**
     * Gets an instance of the Plugin class
     * 
     */
    function get_plugin_instance()
    {
        if (!isset($this->plugin_instance)) {
            $this->plugin_instance = new IWP_MMB_Plugin();
        }
        
        return $this->plugin_instance;
    }
    
    /**
     * Gets an instance of the Theme class
     * 
     */
    function get_theme_instance()
    {
        if (!isset($this->theme_instance)) {
            $this->theme_instance = new IWP_MMB_Theme();
        }
        
        return $this->theme_instance;
    }
    
    
    /**
     * Gets an instance of IWP_MMB_Post class
     * 
     */
    function get_post_instance()
    {
        if (!isset($this->post_instance)) {
            $this->post_instance = new IWP_MMB_Post();
        }
        
        return $this->post_instance;
    }
    
    /**
     * Gets an instance of Blogroll class
     * 
     */
    function get_blogroll_instance()
    {
        if (!isset($this->blogroll_instance)) {
            $this->blogroll_instance = new IWP_MMB_Blogroll();
        }
        
        return $this->blogroll_instance;
    }
    
    
    
    /**
     * Gets an instance of the WP class
     * 
     */
    function get_wp_instance()
    {
        if (!isset($this->wp_instance)) {
            $this->wp_instance = new IWP_MMB_WP();
        }
        
        return $this->wp_instance;
    }
    
    /**
     * Gets an instance of User
     * 
     */
    function get_user_instance()
    {
        if (!isset($this->user_instance)) {
            $this->user_instance = new IWP_MMB_User();
        }
        
        return $this->user_instance;
    }
    
    /**
     * Gets an instance of stats class
     * 
     */
    function get_stats_instance()
    {
        if (!isset($this->stats_instance)) {
            $this->stats_instance = new IWP_MMB_Stats();
        }
        return $this->stats_instance;
    }
    /**
     * Gets an instance of search class
     * 
     */
    function get_search_instance()
    {
        if (!isset($this->search_instance)) {
            $this->search_instance = new IWP_MMB_Search();
        }
        //return $this->search_instance;
        return $this->search_instance;
    }
    /**
     * Gets an instance of stats class
     *
     */
    function get_new_backup_instance($params = array())
    {
    	if ((isset($iwp_backup_core) && is_object($iwp_backup_core) && is_a($iwp_backup_core, 'IWP_MMB_Backup_Core'))) return $iwp_backup_core;

    	require_once($GLOBALS['iwp_mmb_plugin_dir'].'/backup/backup.core.class.php');
    	iwp_mmb_define_constant();
    	$iwp_backup_core = new IWP_MMB_Backup_Core();
    	$GLOBALS['iwp_backup_core'] = $iwp_backup_core;
    	$this->backup_new_instance = $iwp_backup_core;
    	if (!$iwp_backup_core->memory_check(192)) {
    		if (!$iwp_backup_core->memory_check($iwp_backup_core->memory_check_current(WP_MAX_MEMORY_LIMIT))) {
    			$new = absint($iwp_backup_core->memory_check_current(WP_MAX_MEMORY_LIMIT));
    			if ($new>32 && $new<100000) {
    				@ini_set('memory_limit', $new.'M');
    			}
    		}
    	}
        return $this->backup_new_instance;
    }

    function get_backup_instance($mechanism='')
    {
		require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup.class.singlecall.php");
		require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup.class.multicall.php");
		require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup/backup-repo-test.php");
		//$mechanism = 'multiCall';
        if (!isset($this->backup_instance)) {
			if($mechanism == 'singleCall' || $mechanism == ''){
				$this->backup_instance = new IWP_MMB_Backup_Singlecall();
			}
			elseif($mechanism == 'multiCall'){
				$this->backup_instance = new IWP_MMB_Backup_Multicall();
			}
			else{
				iwp_mmb_response(array('error' => 'mechanism not found'), true);
				//return false;
			}
        }
        
        return $this->backup_instance;
    }

	 function get_ithemessec_instance() {
	    require_once($GLOBALS['iwp_mmb_plugin_dir'] . "/addons/itheme_security/class-iwp-client-ithemes-security-class.php");
	    if (!isset($this->ithemessec_instance)) {
	        $this->ithemessec_instance = new IWP_MMB_IThemes_security();
	    }
	    return $this->ithemessec_instance;
	}

	function get_backup_repository_instance()
    {
        require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup.class.singlecall.php");
		require_once($GLOBALS['iwp_mmb_plugin_dir']."/backup.class.multicall.php");
		if (!isset($this->backup_repository_instance)) {
            $this->backup_repository_instance = new IWP_MMB_Backup_Repository();
        }
        
        return $this->backup_repository_instance;
    }
    
    /**
     * Gets an instance of links class
     *
     */
    function get_link_instance()
    {
        if (!isset($this->link_instance)) {
            $this->link_instance = new IWP_MMB_Link();
        }
        
        return $this->link_instance;
    }
    
    function get_installer_instance()
    {
        if (!isset($this->installer_instance)) {
            $this->installer_instance = new IWP_MMB_Installer();
        }
        return $this->installer_instance;
    }
	
	/*
	 * Get an instance of WordFence 
	 */
	 function get_wordfence_instance()
	 {
	 	if (!isset($this->wordfence_instance)) {
            $this->wordfence_instance = new IWP_WORDFENCE();
        }
        return $this->wordfence_instance;
	 }
	/*
	 * Get an instance of WordFence 
	 */
	 function get_sucuri_instance()
	 {
	 	if (!isset($this->sucuri_instance)) {
            $this->sucuri_instance = new IWP_MMB_Sucuri();
        }
        return $this->sucuri_instance;
	 }
	
	public function buildLoaderContent($pluginBasename)
    {
        $loader = <<<EOF
<?php

/*
Plugin Name: InfiniteWP - Client Loader
Plugin URI: https://infinitewp.com/
Description: This plugin will be created automatically when you activate your InfiniteWP Client plugin to improve the performance. And it will be deleted when you deactivate the client plugin.
Author: Revmakx
Author URI: https://infinitewp.com/
*/

if (!function_exists('untrailingslashit') || !defined('WP_PLUGIN_DIR')) {
    // WordPress is probably not bootstrapped.
    exit;
}

if (file_exists(untrailingslashit(WP_PLUGIN_DIR).'/$pluginBasename')) {
    if (in_array('$pluginBasename', (array) get_option('active_plugins')) ||
        (function_exists('get_site_option') && array_key_exists('iwp-client/init.php', (array) get_site_option('active_sitewide_plugins')))) {
        \$GLOBALS['iwp_is_mu'] = true;
        include_once untrailingslashit(WP_PLUGIN_DIR).'/$pluginBasename';
    }
}

EOF;

        return $loader;
    }

    public function registerMustUse($loaderName, $loaderContent)
    {
        $mustUsePluginDir = rtrim(WPMU_PLUGIN_DIR, '/');
        $loaderPath       = $mustUsePluginDir.'/'.$loaderName;

        if (file_exists($loaderPath) && md5($loaderContent) === md5_file($loaderPath)) {
            return;
        }

        if (!is_dir($mustUsePluginDir)) {
            $dirMade = @mkdir($mustUsePluginDir);

            if (!$dirMade) {
                $error = error_get_last();
                return array('');
                throw new Exception(sprintf('Unable to create loader directory: %s', $error['message']));
            }
        }

        if (!is_writable($mustUsePluginDir)) {
            throw new Exception('MU-plugin directory is not writable.');
        }

        $loaderWritten = @file_put_contents($loaderPath, $loaderContent);

        if (!$loaderWritten) {
            $error = error_get_last();
            throw new Exception(sprintf('Unable to write loader: %s', $error['message']));
        }
    }

    function error_notices()
    {
    	$error_notice = get_transient( 'iwp_mu_plugin_loader' );
        echo '<div class="error" style="text-align: center;"><p style="font-size: 14px; font-weight: bold; color:#c00;">Attention !</p>
		<p>Unable to write InfiniteWP Client loader: '.$error_notice.'</p></div>';
    }

    function load_mu_loader_error(){
    	$error_notice = get_transient( 'iwp_mu_plugin_loader' );
    	if( !empty($error_notice) ){
			add_action('admin_notices', array( &$this, 'error_notices' ));
    	}
    }
    /**
     * Plugin install callback function
     * Check PHP version
     */
    function install() {
		
        global $wpdb, $_wp_using_ext_object_cache, $current_user, $iwp_mmb_activities_log;
        $_wp_using_ext_object_cache = false;
         try {
            $this->registerMustUse('mu-iwp-client.php', $this->buildLoaderContent('iwp-client/init.php'));
        } catch (Exception $e) {
        	set_transient( 'iwp_mu_plugin_loader', $e->getMessage(), 30 );
        }
        //delete plugin options, just in case
        if ($this->iwp_mmb_multisite != false) {
			$network_blogs = $wpdb->get_results("select `blog_id`, `site_id` from `{$wpdb->blogs}`");
			if(!empty($network_blogs)){
				if( is_network_admin() ){
					update_option('iwp_client_network_admin_install', 1);
					$mainBlogId = defined('BLOG_ID_CURRENT_SITE') ? BLOG_ID_CURRENT_SITE : false;
					foreach($network_blogs as $details){
						 if (($mainBlogId !== false && $details->blog_id == $mainBlogId) || ($mainBlogId === false && $details->site_id == $details->blog_id)) {
							update_blog_option($details->blog_id, 'iwp_client_network_admin_install', 1);
						}
						else {
							update_blog_option($details->blog_id, 'iwp_client_network_admin_install', -1);
						}
						
						update_blog_option($details->blog_id, 'iwp_client_nossl_key', '');
                        update_blog_option($details->blog_id, 'iwp_client_public_key', '');
						delete_blog_option($details->blog_id, 'iwp_client_action_message_id');
					}
				} else {
					update_option('iwp_client_network_admin_install', -1);
					delete_option('iwp_client_nossl_key');
					delete_option('iwp_client_public_key');
					delete_option('iwp_client_action_message_id');
				}
			}
        } else {
            delete_option('iwp_client_nossl_key');
            delete_option('iwp_client_public_key');
            delete_option('iwp_client_action_message_id');
        }
        
        //delete_option('iwp_client_backup_tasks');
        delete_option('iwp_client_notifications');
        delete_option('iwp_client_brand');
        delete_option('iwp_client_public_key');
        delete_option('iwp_client_pageview_alerts');
		
		$this->update_option('iwp_client_activate_key', sha1( rand(1, 99999). uniqid('', true) . get_option('siteurl') ) );
		
		$iwp_mmb_activities_log->iwp_mmb_save_options_for_activity_log('install');
    }
    
    /**
     * Saves the (modified) options into the database
     * 
     */
    function save_options()
    {
        if (get_option($this->slug)) {
            update_option($this->slug, $this->settings);
        } else {
            add_option($this->slug, $this->settings);
        }
    }
    
    /**
     * Deletes options for communication with IWP Admin panel
     * 
     */
    function uninstall( $deactivate = false )
    {
        global $current_user, $wpdb, $_wp_using_ext_object_cache;
		$_wp_using_ext_object_cache = false;
        
        if ($this->iwp_mmb_multisite != false) {
			$network_blogs = $wpdb->get_col("select `blog_id` from `{$wpdb->blogs}`");
			if(!empty($network_blogs)){
				if( is_network_admin() ){
					if( $deactivate ) {
						delete_option('iwp_client_network_admin_install');
						foreach($network_blogs as $blog_id){
							delete_blog_option($blog_id, 'iwp_client_network_admin_install');
							delete_blog_option($blog_id, 'iwp_client_nossl_key');
							delete_blog_option($blog_id, 'iwp_client_public_key');
							delete_blog_option($blog_id, 'iwp_client_action_message_id');
							delete_blog_option($blog_id, 'iwp_client_maintenace_mode');
						}
					}
				} else {
					if( $deactivate )
						delete_option('iwp_client_network_admin_install');
						
					delete_option('iwp_client_nossl_key');
					delete_option('iwp_client_public_key');
					delete_option('iwp_client_action_message_id');
				}
			}
        } else {
			delete_option('iwp_client_nossl_key');
            delete_option('iwp_client_public_key');
            delete_option('iwp_client_action_message_id');
        }
        
        //Delete options
		delete_option('iwp_client_maintenace_mode');
        //delete_option('iwp_client_backup_tasks');
        wp_clear_scheduled_hook('iwp_client_backup_tasks');
        delete_option('iwp_client_notifications');
        wp_clear_scheduled_hook('iwp_client_notifications');        
        delete_option('iwp_client_brand');
        delete_option('iwp_client_pageview_alerts');
		
		delete_option('iwp_client_activate_key');
		delete_option('iwp_client_all_themes_history');
		delete_option('iwp_client_all_plugins_history');
		delete_option('iwp_client_wp_version_old');
		delete_option('is_save_activity_log');
		$loaderName = 'mu-iwp-client.php';
		try {
		    $mustUsePluginDir = rtrim(WPMU_PLUGIN_DIR, '/');
		    $loaderPath       = $mustUsePluginDir.'/'.$loaderName;

		    if (!file_exists($loaderPath)) {
		        return;
		    }

		    $removed = @unlink($loaderPath);

		    if (!$removed) {
		        $error = error_get_last();
		        throw new Exception(sprintf('Unable to remove loader: %s', $error['message']));
		    }
		} catch (Exception $e) {
		    mwp_logger()->error('Unable to remove loader', array('exception' => $e));
		}
    }
    
    
    /**
     * Constructs a url (for ajax purpose)
     * 
     * @param mixed $base_page
     */
    function construct_url($params = array(), $base_page = 'index.php')
    {
        $url = "$base_page?_wpnonce=" . wp_create_nonce($this->slug);
        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }
        
        return $url;
    }
    
    /**
     * Client update
     * 
     */
    function update_client_plugin($params)
    {
		global $iwp_mmb_activities_log;
        extract($params);
        if ($download_url) {
            @include_once ABSPATH . 'wp-admin/includes/file.php';
			@include_once ABSPATH . 'wp-admin/includes/plugin.php';
            @include_once ABSPATH . 'wp-admin/includes/misc.php';
            @include_once ABSPATH . 'wp-admin/includes/template.php';
            @include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            @include_once ABSPATH . 'wp-admin/includes/screen.php';
			if (!$this->define_ftp_constants($params)) {
				return array(
					'error' => 'FTP constant define failed', 'error_code' => 'ftp constant define failed'
				);
			}
			if (!$this->is_server_writable()) {
				return array(
					'error' => 'Failed, please add FTP details', 'error_code' => 'automatic_upgrade_failed_add_ftp_details'
				);
			}

            ob_start();
            @unlink(dirname(__FILE__));
            $upgrader = new Plugin_Upgrader();
            $result   = $upgrader->run(array(
                'package' => $download_url,
                'destination' => WP_PLUGIN_DIR,
                'clear_destination' => true,
                'clear_working' => true,
                'hook_extra' => array(
                    'plugin' => 'iwp-client/init.php'
                )
            ));
            ob_end_clean();
			@wp_update_plugins();
			
            if (is_wp_error($result) || !$result) {
                return array(
                    'error' => 'InfiniteWP Client plugin could not be updated.', 'error_code' => 'client_plugin_could_not_be_updated'
                );
            } else {
				
				$iwp_mmb_activities_log->iwp_mmb_save_options_for_activity_log('update_client_plugin');
				
                return array(
                    'success' => 'InfiniteWP Client plugin successfully updated.'
                );
            }
        }
        return array(
            'error' => 'Bad download path for client installation file.', 'error_code' => 'client_plugin_bad_download_path'
        );
    }
    
    /**
     * Automatically logs in when called from IWP Admin panel
     * 
     */
    function automatic_login()
    {
		$where      = isset($_GET['iwp_goto']) ? $_GET['iwp_goto'] : false;
        $username   = isset($_GET['username']) ? $_GET['username'] : '';
        $auto_login = isset($_GET['auto_login']) ? $_GET['auto_login'] : 0;
        $page       = isset($_GET['page']) ? '?page='.$_GET['page'] : '';
        $action     = isset($_GET['action']) ? '?action='.$_GET['action'] : '';
        $post     = isset($_GET['action']) ? '&post='.$_GET['post'] : '';
        $_SERVER['HTTP_REFERER']='';
		if( !function_exists('is_user_logged_in') )
			include_once( ABSPATH.'wp-includes/pluggable.php' );
		
		if (( $auto_login && strlen(trim($username)) && !is_user_logged_in() ) || (isset($this->iwp_mmb_multisite) && $this->iwp_mmb_multisite )) {
			$signature  = base64_decode($_GET['signature']);
            $message_id = trim($_GET['message_id']);
            
            $auth = $this->authenticate_message($where . $message_id, $signature, $message_id);
			if ($auth === true) {
				
				if (!headers_sent())
					header('P3P: CP="CAO PSA OUR"');
				
				if(!defined('IWP_MMB_USER_LOGIN'))
					define('IWP_MMB_USER_LOGIN', true);
				
				$siteurl = function_exists('get_site_option') ? get_site_option( 'siteurl' ) : get_option('siteurl');
				$user = $this->iwp_mmb_get_user_info($username);
				wp_set_current_user($user->ID);
				
				if(!defined('COOKIEHASH') || (isset($this->iwp_mmb_multisite) && $this->iwp_mmb_multisite) )
					wp_cookie_constants();
				
				wp_set_auth_cookie($user->ID);
				@iwp_mmb_client_header();
				
				//if((isset($this->iwp_mmb_multisite) && $this->iwp_mmb_multisite ) || isset($_REQUEST['iwpredirect'])){//comment makes force redirect, which fix bug https dashboard
					if(function_exists('wp_safe_redirect') && function_exists('admin_url')){
						wp_safe_redirect(admin_url($where.$page.$action.$post));
						exit();
					}
				//}
			} else {
                wp_die($auth['error']);
            }
        } elseif( is_user_logged_in() ) {
			@iwp_mmb_client_header();
			if(isset($_REQUEST['iwpredirect'])){
				if(function_exists('wp_safe_redirect') && function_exists('admin_url')){
					wp_safe_redirect(admin_url($where.$page.$action.$post));
					exit();
				}
			}
		}
    }
    
	function iwp_mmb_set_auth_cookie( $auth_cookie ){
		if(!defined('IWP_MMB_USER_LOGIN'))
			return false;
		
		if( !defined('COOKIEHASH') )
			wp_cookie_constants();
			
		$_COOKIE['wordpress_'.COOKIEHASH] = $auth_cookie;
		
	}
	function iwp_mmb_set_logged_in_cookie( $logged_in_cookie ){
		if(!defined('IWP_MMB_USER_LOGIN'))
			return false;
	
		if( !defined('COOKIEHASH') )
			wp_cookie_constants();
			
		$_COOKIE['wordpress_logged_in_'.COOKIEHASH] = $logged_in_cookie;
	}
		
    function admin_actions(){
    	$replace = get_option("iwp_client_brand");
		if(!empty($replace)){
			if(!empty($replace['hideUpdatesCPB'])){
				//define('DISALLOW_FILE_MODS',true);				//for hiding updates old method
			}
			if(!empty($replace['hideFWPCPB'])){
				//define('DISALLOW_FILE_EDIT',true);				//for hiding file writing permissions old method
			}
			if(!empty($replace['doChangesCPB']) || ( !isset($replace['doChangesCPB']) && (!empty($replace['name']) || !empty($replace['desc']) || !empty($replace['author']) || !empty($replace['author_url'])))){
				add_filter('plugin_row_meta', array($this, 'iwp_client_replace_row_meta'), 10, 2);		//for hiding the view details alone.
				add_filter('site_transient_update_plugins', array($this, 'iwp_site_transient_update_plugins'), 10, 2);   //for hiding the infiniteWP update details.
				add_filter('admin_url', array($this, 'iwp_user_admin_url'), 10, 2);				//for modifying the link available in plugin's view version details link.
			}
			add_filter('all_plugins', array($this, 'client_replace'));			//for replacing name and all.
			add_filter('show_advanced_plugins', array($this, 'muPluginListFilter'), 10, 2);			//for replacing name and all.
		}
    }
	
	function iwp_remove_core_updates($value){
		if(isset($value->response)){
			$old_response = $value->response;
		unset($value->response);
		}
		if(isset($value->updates)){
			unset($value->updates);
		}
		return $value;
	}
	
	function iwp_admin_menu_actions($args){
		//to hide all updates
		global $iwp_mmb_core;
		$replace = get_option("iwp_client_brand");
		if(empty($iwp_mmb_core->request_params) && !empty($replace)){
			if(!empty($replace['hideUpdatesCPB'])){
				//add_filter('wp_get_update_data', array($this, 'iwp_wp_get_update_data'), 10, 2);
				$page = remove_submenu_page( 'index.php', 'update-core.php' );
				add_filter('transient_update_plugins', array($this, 'iwp_remove_core_updates'), 999999, 1);
				add_filter('site_transient_update_core', array($this, 'iwp_remove_core_updates'), 999999, 1);
				add_filter('site_transient_update_plugins', array($this, 'iwp_remove_core_updates'), 999999, 1);
				add_filter('site_transient_update_themes', array($this, 'iwp_remove_core_updates'), 999999, 1);
			}
			if(!empty($replace['hideFWPCPB'])){
				// remove_submenu_page('themes.php','theme-editor.php');
				// remove_submenu_page('plugins.php','plugin-editor.php'); // this is old method this allows editor in direct URL
				if (!defined('DISALLOW_FILE_EDIT')) {
					define('DISALLOW_FILE_EDIT', true);
				}
				add_filter('plugin_action_links', array($this, 'iwp_client_replace_action_links'), 10, 2);
			}
		}
    }

    function iwp_cpb_hide_updates($args){
    	global $iwp_mmb_core;
		$replace = get_option("iwp_client_brand");
		if(empty($iwp_mmb_core->request_params) && !empty($replace)){
			if(!empty($replace['hideUpdatesCPB'])){
				add_filter('transient_update_plugins', array($this, 'iwp_remove_core_updates'), 999999, 1);
				add_filter('site_transient_update_core', array($this, 'iwp_remove_core_updates'), 999999, 1);
				add_filter('site_transient_update_plugins', array($this, 'iwp_remove_core_updates'), 999999, 1);
				add_filter('site_transient_update_themes', array($this, 'iwp_remove_core_updates'), 999999, 1);
			}
		}
    }
	
	function iwp_user_admin_url($args, $args2){
		//for modifying the link available in plugin's view version details link.
		if(strpos($args2, 'plugin-install.php?tab=plugin-information&plugin') !== false){
			$replace = get_option("iwp_client_brand");
			if(!empty($replace) && is_array($replace)){
				if(!empty($replace['name'])){
					$search_str = 'plugin-install.php?tab=plugin-information&plugin='.$replace['name'].'&section=changelog';
					if(strpos($args2, $search_str) !== false){
						$return_var = plugins_url( '/iwp-client/readme.txt' ) . 'TB_iframe=true&width=600&height=550';
						return  $return_var;
					}
				}
			}
		}
		return $args;
	}
	
	function iwp_site_transient_update_plugins($value){
		if(!empty($value->response['iwp-client/init.php'])){
			$replace = get_option("iwp_client_brand");
			if(!empty($replace) && is_array($replace)){
				if(!empty($replace['name'])){
					$file_traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					$called_by_file = array_pop($file_traces);
					$called_by_file = basename($called_by_file['file']);
					if($called_by_file == "update-core.php"){
						unset($value->response['iwp-client/init.php']);   		//for hiding the updates available in updates dashboard section
					}
					else if($called_by_file == "plugins.php"){
						$value->response['iwp-client/init.php']->slug = $replace['name'];    ////for hiding the updates available in plugins section
						$value->response['iwp-client/init.php']->Name = $replace['name'];
						//unset($value->response['iwp-client/init.php']);
					}
				}
			}
		}
		return $value;
	}
	
	function iwp_client_replace_action_links($links, $file){
		//for hiding edit on plugins page.
		if(!empty($links['edit'])){
			unset($links['edit']);
		}
		return $links;
	}
	
	function iwp_client_replace_row_meta($links, $file) {
		//for hiding the view details alone.
		if($file == 'iwp-client/init.php'){
			if(!empty($links[2])){
			unset($links[2]);
		}
		}
		return $links;
    }
    
    function client_replace($all_plugins){
    	$replace = get_option("iwp_client_brand");
    	if(is_array($replace)){
    		if(!empty($replace['doChangesCPB']) || (!isset($replace['doChangesCPB']) && (!empty($replace['name']) || !empty($replace['desc']) || !empty($replace['author']) || !empty($replace['author_url'])))){
    			$all_plugins['iwp-client/init.php']['Name'] = $replace['name'];
    			$all_plugins['iwp-client/init.php']['Title'] = $replace['name'];
    			$all_plugins['iwp-client/init.php']['Description'] = $replace['desc'];
    			$all_plugins['iwp-client/init.php']['AuthorURI'] = $replace['author_url'];
    			$all_plugins['iwp-client/init.php']['Author'] = $replace['author'];
    			$all_plugins['iwp-client/init.php']['AuthorName'] = $replace['author'];
    			$all_plugins['iwp-client/init.php']['PluginURI'] = '';
    		}
    		
    		if(!empty($replace['hide'])){
				if (!function_exists('get_plugins')){
					include_once(ABSPATH . 'wp-admin/includes/plugin.php');
				}
				$activated_plugins = get_option('active_plugins');
				if (!$activated_plugins){
					$activated_plugins = array();
				}
				if(in_array('iwp-client/init.php',$activated_plugins)){
					unset($all_plugins['iwp-client/init.php']);
				}
    		}
    	}
		    	  	
    	return $all_plugins;
    }

    function add_login_action(){
		add_action('plugins_loaded', array( &$this, 'automatic_login'), 10);
    }

    function muPluginListFilter($previousValue, $type)
    {
        // Drop-in's are filtered after MU plugins.
        if ($type !== 'dropins') {
            return $previousValue;
        }

        if (!empty($previousValue['iwp-client/init.php'])) {
            return $previousValue;
        }
        $replace = get_option("iwp_client_brand");

        if (!empty($replace['hide'])) {
            unset($GLOBALS['plugins']['mustuse']['mu-iwp-client.php']);
        } elseif(!empty($replace['doChangesCPB']) || (!isset($replace['doChangesCPB']) && (!empty($replace['name']) || !empty($replace['desc']) || !empty($replace['author']) || !empty($replace['author_url'])))){ 
            $GLOBALS['plugins']['mustuse']['mu-iwp-client.php']['Name']        = $replace['name'];
            $GLOBALS['plugins']['mustuse']['mu-iwp-client.php']['Title']       = $replace['name'];
            $GLOBALS['plugins']['mustuse']['mu-iwp-client.php']['Description'] = $replace['desc'];
            $GLOBALS['plugins']['mustuse']['mu-iwp-client.php']['AuthorURI']   = $replace['author_url'];
            $GLOBALS['plugins']['mustuse']['mu-iwp-client.php']['Author']      = $replace['author'];
            $GLOBALS['plugins']['mustuse']['mu-iwp-client.php']['AuthorName']  = $replace['author'];
            $GLOBALS['plugins']['mustuse']['mu-iwp-client.php']['PluginURI']   = '';
        }

        return $previousValue;
    }
    function updateKeys()
    {
        if (!$this->iwp_mmb_multisite) {
            return;
        }

        global $wpdb;

        $publicKey = $this->get_parent_blog_option('iwp_client_public_key');

        if (empty($publicKey)) {
            return;
        }

        $networkBlogs = $wpdb->get_results("select `blog_id` from `{$wpdb->blogs}`");

        if (empty($networkBlogs)) {
            return;
        }

        foreach ($networkBlogs as $details) {
            update_blog_option($details->blog_id, 'iwp_client_public_key', $publicKey);
        }

        return;
    }

    function addConnectionKeyLink($meta, $slug)
    {
        if (is_multisite() && !is_network_admin()) {
            return $meta;
        }

        if ($slug !== 'iwp-client/init.php') {
            return $meta;
        }

        if (!current_user_can('activate_plugins')) {
            return $meta;
        }

        $meta[] = '<a href="#" id="iwp-view-connection-key" iwp-key="'.get_option('iwp_client_activate_key').'">View activation key</a>';

        return $meta;
    }

    function printConnectionModalOpenScript()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        ob_start()
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var $iwpconnectionKeyDialog = $('#iwp_connection_key_dialog');
                $('#iwp-view-connection-key').click(function(e) {
                    e.preventDefault();
                    $iwpconnectionKeyDialog.dialog({
                        draggable: false,
                        resizable: false,
                        modal: true,
                        width: '530px',
                        height: 'auto',
                        title: 'Activation Key',
                        close: function() {
                            $(this).dialog("destroy");
                        }
                    });
                });
                $('button.copy-key-button').click(function() {
                    $('#activation-key').select();
                    document.execCommand('copy');
                });
            });
        </script>
        <?php

        $content = ob_get_clean();
        print $content;
    }

   function printConnectionModalDialog()
    {
       	if (is_multisite() && !is_network_admin()) {
            return;
        }

        if (!current_user_can('activate_plugins')) {
            return;
        }

        ob_start();
        ?>
        <div id="iwp_connection_key_dialog" style="display: none;">

            <div style="text-align: center;font-weight: bold;"><p style="margin-bottom: 4px;margin-top: 20px;">Activation Key</p></div>
            <input id="activation-key" rows="1" style="padding: 10px;background-color: #fafafa;border: 1px solid black;border-radius: 10px;font-weight: bold;font-size: 14px;text-align: center; width: 85%; margin-right: 5px" onclick="this.focus();this.select()" readonly="readonly" value="<?php echo get_option('iwp_client_activate_key'); ?>">
            <button class="copy-key-button" data-clipboard-target="#activation-key" style="padding: 10px;background-color: #fafafa;border: 1px solid black;border-radius: 10px;font-weight: bold;font-size: 14px;text-align: center;">Copy</button>
        </div>
        <?php

        $content = ob_get_clean();
      	 print $content;
    }	

    function get_option($option){
    	if (is_multisite()) {
            return get_site_option($option);
        }

        return get_option($option);
    }

    function update_option($option, $option_value){
    	 if (is_multisite()) {
            global $wpdb;
            $blogIDs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blogIDs as $blogID) {
                update_blog_option($blogID, $option, $option_value);
            }
            return true;
        } else {
                update_option($option, $option_value);
                return true;
            }
        return false;
    }

    function enqueueConnectionModalOpenScripts(){
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-dialog');
    }

    function enqueueConnectionModalOpenStyles(){
        wp_enqueue_style('wp-jquery-ui');
        wp_enqueue_style('wp-jquery-ui-dialog');
    }
    
    function get_db_details($params){
    	global $wpdb;
    	$result = array();
    	if (defined('DB_HOST')) {
	    	$result['dbHost'] = DB_HOST;
	    	$result['dbName'] = DB_NAME;
	    	$result['dbUser'] = DB_USER;
	    	$result['dbPassword'] = DB_PASSWORD;
	    	$result['db_table_prefix'] = $wpdb->base_prefix;
    	}

    	return $result;
    }
   
}
?>