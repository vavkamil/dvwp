<?php

/**
 * Initialize Dashboard Area of Plugin
 *
 * This file initializes the Dashboard area of the plugin; it registers the
 * Dashboard menu pages and processes Dashboard requests.
 *
 * @link /lib/wfu_admin.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 2.1.2
 */

/**
 * Register Dashboard Styles and Scripts.
 *
 * This function registers styles and scripts for Dashboard area.
 *
 * @since 2.4.6
 */
function wordpress_file_upload_admin_init() {
	$uri = $_SERVER['REQUEST_URI'];
	$is_admin = current_user_can( 'manage_options' );
	$can_edit_posts = ( current_user_can( 'edit_pages' ) || current_user_can( 'edit_posts' ) );
	$can_open_composer = ( WFU_VAR("WFU_SHORTCODECOMPOSER_NOADMIN") == "true" && $can_edit_posts );
	if ( is_admin() && ( ( $is_admin && strpos($uri, "options-general.php") !== false ) ) ||
		//conditional that will register scripts for non-admin users who can
		//edit posts or pages so that they can open the shortcode composer
		( is_admin() && $can_open_composer && strpos($uri, "admin.php") !== false ) ) {
		//apply wfu_before_admin_scripts to get additional settings 
		$changable_data = array();
		/**
		 * Execute Custom Actions Before Loading Admin Scripts.
		 *
		 * This filter allows to execute custom actions before scripts and
		 * styles of the plugin's main Dashboard area are loaded. Loading of
		 * plugin's scripts and styles can be completely customised.
		 *
		 * @since 4.1.0
		 *
		 * @param array $changable_data {
		 *     Controls loading of frontpage scripts.
		 *
		 *     @type mixed $return_value Optional. If it is set then no
		 *           frontpage scripts will be loaded.
		 *     @type string $correct_NextGenGallery_incompatibility Optional. If
		 *           it is set to "true" then JQuery UI styles will not be
		 *           loaded in order to avoid incompatibility with NextGEN
		 *           Gallery plugin.
		 *     @type string $correct_JQueryUI_incompatibility Optional. If it is
		 *           set to "true" then JQuery UI styles will not be loaded
		 *           (same as previous parameter).
		 *     @type string $exclude_datepicker Optional. If it is set to "true"
		 *           then jQuery datepicker styles and scripts will not be
		 *           loaded.
		 * }
		 */
		$ret_data = apply_filters('wfu_before_admin_scripts', $changable_data);
		//if $ret_data contains 'return_value' key then no scripts will be
		//registered
		if ( isset($ret_data['return_value']) ) return $ret_data['return_value'];
		//continue with script and style registering
		wp_register_style('wordpress-file-upload-admin-style', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_adminstyle.css',false,'1.0','all');
		wp_register_style('wordpress-file-upload-adminbar-style', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_adminbarstyle.css',false,'1.0','all');
		//do not register JQuery UI css if $ret_data denotes incompatibility
		//issues
		if ( ( !isset($ret_data["correct_NextGenGallery_incompatibility"]) || $ret_data["correct_NextGenGallery_incompatibility"] != "true" ) &&
			( !isset($ret_data["correct_JQueryUI_incompatibility"]) || $ret_data["correct_JQueryUI_incompatibility"] != "true" ) )
			wp_register_style('jquery-ui-css', WPFILEUPLOAD_DIR.'vendor/jquery/jquery-ui.min.css');
		//don't load datepicker js if $ret_data exclude_datepicker flag is true
		if ( !isset($ret_data["exclude_datepicker"]) || $ret_data["exclude_datepicker"] != "true" )
			wp_register_script('jquery-ui-datepicker', false, array('jquery'));
		wp_register_script('wordpress_file_upload_admin_script', WPFILEUPLOAD_DIR.'js/wordpress_file_upload_adminfunctions.js', array( 'wp-color-picker' ), false, true);
		if ( !$is_admin ) {
			add_action('admin_post_edit_shortcode', 'wordpress_file_upload_manage_dashboard');
			add_action('admin_print_scripts', 'wfu_enqueue_admin_scripts');
		}
	}
	//register scripts for Uploaded Files
	elseif ( is_admin() && $is_admin && strpos($uri, "admin.php") !== false ) {
		//apply wfu_before_admin_scripts to get additional settings 
		$changable_data = array();
		/**
		 * Execute Custom Actions Before Loading Uploaded Files Scripts.
		 *
		 * This filter allows to execute custom actions before scripts and
		 * styles of the plugin's Uploaded Files Dashboard page are loaded.
		 * Loading of plugin's scripts and styles can be completely customised.
		 *
		 * @since 4.7.0
		 *
		 * @param array $changable_data {
		 *     Controls loading of frontpage scripts.
		 *
		 *     @type mixed $return_value Optional. If it is set then no
		 *           frontpage scripts will be loaded.
		 *     @type string $correct_NextGenGallery_incompatibility Optional. If
		 *           it is set to "true" then JQuery UI styles will not be
		 *           loaded in order to avoid incompatibility with NextGEN
		 *           Gallery plugin.
		 *     @type string $correct_JQueryUI_incompatibility Optional. If it is
		 *           set to "true" then JQuery UI styles will not be loaded
		 *           (same as previous parameter).
		 * }
		 */
		$ret_data = apply_filters('wfu_before_uploadedfiles_admin_scripts', $changable_data);
		//if $ret_data contains 'return_value' key then no scripts will be
		//registered
		if ( isset($ret_data['return_value']) ) return $ret_data['return_value'];
		//continue with script and style registering
		wp_register_style('wordpress-file-upload-admin-style', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_adminstyle.css',false,'1.0','all');
		wp_register_style('wordpress-file-upload-adminbar-style', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_adminbarstyle.css',false,'1.0','all');
		//do not register JQuery UI css if $ret_data denotes incompatibility
		//issues
		if ( ( !isset($ret_data["correct_NextGenGallery_incompatibility"]) || $ret_data["correct_NextGenGallery_incompatibility"] != "true" ) &&
			( !isset($ret_data["correct_JQueryUI_incompatibility"]) || $ret_data["correct_JQueryUI_incompatibility"] != "true" ) )
			wp_register_style('jquery-ui-css', WPFILEUPLOAD_DIR.'vendor/jquery/jquery-ui.min.css');
		wp_register_script('wordpress_file_upload_admin_script', WPFILEUPLOAD_DIR.'js/wordpress_file_upload_adminfunctions.js', array( 'wp-color-picker' ), false, true);
	}
	//register scripts for admin bar menu item
	elseif ( is_admin() && $is_admin ) {
		//script and style registering
		wp_register_style('wordpress-file-upload-adminbar-style', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_adminbarstyle.css',false,'1.0','all');
	}
}

/**
 * Register Dashboard Menu Pages.
 *
 * This function registers the Dashboard pages of the plugin.
 *
 * @since 2.1.2
 */
function wordpress_file_upload_add_admin_pages() {
	global $wpdb;
	global $wfu_uploadedfiles_hook_suffix;
	$table_name1 = $wpdb->prefix . "wfu_log";

	$page_hook_suffix = false;
	if ( current_user_can( 'manage_options' ) ) $page_hook_suffix = add_options_page('Wordpress File Upload', 'Wordpress File Upload', 'manage_options', 'wordpress_file_upload', 'wordpress_file_upload_manage_dashboard');
	if ( $page_hook_suffix !== false ) add_action('admin_print_scripts-'.$page_hook_suffix, 'wfu_enqueue_admin_scripts');
	//conditional that will create Wordpress File Upload Dashboard menu, if it
	//has not already been created, for non-admin users who can edit posts or
	//pages, so that their requests for opening the shortcode composer can be
	//handled
	elseif ( WFU_VAR("WFU_SHORTCODECOMPOSER_NOADMIN") == "true" && ( current_user_can( 'edit_pages' ) || current_user_can( 'edit_posts' ) ) ) {
		$page_hook_suffix = add_menu_page('Wordpress File Upload', 'Wordpress File Upload', 'read', 'wordpress_file_upload', 'wordpress_file_upload_manage_dashboard_editor');
		if ( $page_hook_suffix !== false ) add_action('admin_print_scripts-'.$page_hook_suffix, 'wfu_enqueue_admin_scripts');
	}
	//add Uploaded Files menu if it is allowed
	$wfu_uploadedfiles_hook_suffix = false;
	if ( current_user_can( 'manage_options' ) && WFU_VAR("WFU_UPLOADEDFILES_MENU") == "true" ) {
		//get the number of new (unread) uploaded files
		$unread_files_count = wfu_get_unread_files_count();
		$text = $unread_files_count;
		if ( $unread_files_count > 99 ) $text = "99+";
		$title = 'Uploaded Files <span class="update-plugins count-'.$unread_files_count.'"><span class="plugin-count">'.$text.'</span></span>';
		$wfu_uploadedfiles_hook_suffix = add_menu_page( 
			'Uploaded Files',
			$title,
			'manage_options',
			'wfu_uploaded_files',
			'wfu_uploadedfiles_menu',
			'dashicons-upload',
			6
		); 
	}
	if ( $wfu_uploadedfiles_hook_suffix !== false ) {
		add_action('admin_print_scripts-'.$wfu_uploadedfiles_hook_suffix, 'wfu_enqueue_uploadedfiles_admin_scripts');
	}
	//enqueue scripts for admin bar menu item
	if ( current_user_can( 'manage_options' ) )
		add_action('admin_print_scripts', 'wfu_enqueue_uploadedfiles_adminbar_scripts');
}

/**
 * Enqueue Main Dashboard Page Styles and Scripts.
 *
 * This function registers the styles and scripts of the plugin's main
 * Dashboard page.
 *
 * @since 2.4.6
 */
function wfu_enqueue_admin_scripts() {
	$uri = $_SERVER['REQUEST_URI'];
	$is_admin = current_user_can( 'manage_options' );
	$can_open_composer = ( WFU_VAR("WFU_SHORTCODECOMPOSER_NOADMIN") == "true" && ( current_user_can( 'edit_pages' ) || current_user_can( 'edit_posts' ) ) );
	if ( is_admin() && ( ( $is_admin && strpos($uri, "options-general.php") !== false ) ) ||
		//conditional that will enqueue scripts for non-admin users who can
		//edit posts or pages so that they can open the shortcode composer
		( is_admin() && $can_open_composer && strpos($uri, "admin.php") !== false ) ) {
		//apply wfu_before_admin_scripts to get additional settings 
		$changable_data = array();
		/** This filter is documented above */
		$ret_data = apply_filters('wfu_before_admin_scripts', $changable_data);
		//if $ret_data contains 'return_value' key then no scripts will be
		//enqueued
		if ( isset($ret_data['return_value']) ) return $ret_data['return_value'];
		//continue with script and style enqueuing
		wp_enqueue_style('wordpress-file-upload-admin-style');
		wp_enqueue_style('wordpress-file-upload-adminbar-style');
		//do not enqueue JQuery UI css if $ret_data denotes incompatibility
		//issues
		if ( ( !isset($ret_data["correct_NextGenGallery_incompatibility"]) || $ret_data["correct_NextGenGallery_incompatibility"] != "true" ) &&
			( !isset($ret_data["correct_JQueryUI_incompatibility"]) || $ret_data["correct_JQueryUI_incompatibility"] != "true" ) )
			wp_enqueue_style('jquery-ui-css');
		wp_enqueue_style( 'wp-color-picker' );
		//don't load datepicker js if $ret_data exclude_datepicker flag is true
		if ( !isset($ret_data["exclude_datepicker"]) || $ret_data["exclude_datepicker"] != "true" )
			wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('wordpress_file_upload_admin_script');
		$AdminParams = array("wfu_ajax_url" => site_url()."/wp-admin/admin-ajax.php");
		wp_localize_script( 'wordpress_file_upload_admin_script', 'AdminParams', $AdminParams );
	}
}

/**
 * Enqueue Uploaded Files Dashboard Page Styles and Scripts.
 *
 * This function registers the styles and scripts of the plugin's Uploaded Files
 * Dashboard page.
 *
 * @since 4.7.0
 */
function wfu_enqueue_uploadedfiles_admin_scripts() {
	$uri = $_SERVER['REQUEST_URI'];
	$is_admin = current_user_can( 'manage_options' );
	if ( is_admin() && $is_admin && strpos($uri, "admin.php") !== false ) {
		//apply wfu_before_admin_scripts to get additional settings 
		$changable_data = array();
		/** This filter is documented above */
		$ret_data = apply_filters('wfu_before_uploadedfiles_admin_scripts', $changable_data);
		//if $ret_data contains 'return_value' key then no scripts will be
		//enqueued
		if ( isset($ret_data['return_value']) ) return $ret_data['return_value'];
		//continue with script and style enqueuing
		wp_enqueue_style('wordpress-file-upload-admin-style');
		wp_enqueue_style('wordpress-file-upload-adminbar-style');
		//do not enqueue JQuery UI css if $ret_data denotes incompatibility
		//issues
		if ( ( !isset($ret_data["correct_NextGenGallery_incompatibility"]) || $ret_data["correct_NextGenGallery_incompatibility"] != "true" ) &&
			( !isset($ret_data["correct_JQueryUI_incompatibility"]) || $ret_data["correct_JQueryUI_incompatibility"] != "true" ) )
			wp_enqueue_style('jquery-ui-css');
		wp_enqueue_script('wordpress_file_upload_admin_script');
		$AdminParams = array("wfu_ajax_url" => site_url()."/wp-admin/admin-ajax.php");
		wp_localize_script( 'wordpress_file_upload_admin_script', 'AdminParams', $AdminParams );
	}
}

/**
 * Enqueue Admin Bar Styles and Scripts.
 *
 * This function registers the styles and scripts of the plugin for the Admin
 * Bar.
 *
 * @since 4.8.0
 */
function wfu_enqueue_uploadedfiles_adminbar_scripts() {
	$is_admin = current_user_can( 'manage_options' );
	if ( is_admin() && $is_admin ) {
		//script and style enqueuing
		wp_enqueue_style('wordpress-file-upload-adminbar-style');
	}
}


/**
 * Initialize Tables.
 *
 * This function initializes the plugin's database tables and other actions.
 *
 * @since 2.4.1
 */
function wordpress_file_upload_install() {
	global $wpdb;
	global $wfu_tb_log_version;
	global $wfu_tb_userdata_version;
	global $wfu_tb_dbxqueue_version;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	//define database tables
	$table_name1 = $wpdb->prefix . "wfu_log";
	$installed_ver = get_option( "wordpress_file_upload_table_log_version" );
	if( $installed_ver != $wfu_tb_log_version ) {
		$sql = "CREATE TABLE " . $table_name1 . " ( 
			idlog mediumint(9) NOT NULL AUTO_INCREMENT,
			userid int NOT NULL,
			uploaduserid int NOT NULL,
			uploadtime bigint,
			sessionid VARCHAR(40),
			filepath TEXT NOT NULL,
			filehash VARCHAR(100) NOT NULL,
			filesize bigint NOT NULL,
			uploadid VARCHAR(20) NOT NULL,
			pageid mediumint(9),
			blogid mediumint(9),
			sid VARCHAR(10),
			date_from DATETIME,
			date_to DATETIME,
			action VARCHAR(20) NOT NULL,
			linkedto mediumint(9),
			filedata TEXT,
			PRIMARY KEY  (idlog))
			DEFAULT CHARACTER SET = utf8
			DEFAULT COLLATE = utf8_general_ci;";
		dbDelta($sql);
		update_option("wordpress_file_upload_table_log_version", $wfu_tb_log_version);
	}

	$table_name2 = $wpdb->prefix . "wfu_userdata";
	$installed_ver = get_option( "wordpress_file_upload_table_userdata_version" );
	if( $installed_ver != $wfu_tb_userdata_version ) {
		$sql = "CREATE TABLE " . $table_name2 . " ( 
			iduserdata mediumint(9) NOT NULL AUTO_INCREMENT,
			uploadid VARCHAR(20) NOT NULL,
			property VARCHAR(100) NOT NULL,
			propkey mediumint(9) NOT NULL,
			propvalue TEXT,
			date_from DATETIME,
			date_to DATETIME,
			PRIMARY KEY  (iduserdata))
			DEFAULT CHARACTER SET = utf8
			DEFAULT COLLATE = utf8_general_ci;";
		dbDelta($sql);
		update_option("wordpress_file_upload_table_userdata_version", $wfu_tb_userdata_version);
	}

	$table_name3 = $wpdb->prefix . "wfu_dbxqueue";
	$installed_ver = get_option( "wordpress_file_upload_table_dbxqueue_version" );
	if( $installed_ver != $wfu_tb_dbxqueue_version ) {
		$sql = "CREATE TABLE " . $table_name3 . " ( 
			iddbxqueue mediumint(9) NOT NULL AUTO_INCREMENT,
			fileid mediumint(9) NOT NULL,
			priority mediumint(9) NOT NULL,
			status mediumint(9) NOT NULL,
			jobid VARCHAR(10) NOT NULL,
			start_time bigint,
			PRIMARY KEY  (iddbxqueue))
			DEFAULT CHARACTER SET = utf8
			DEFAULT COLLATE = utf8_general_ci;";
		dbDelta($sql);
		update_option("wordpress_file_upload_table_dbxqueue_version", $wfu_tb_dbxqueue_version);
	}
	//adjust user state handler to 'dboption' except if there are active hooks
	//that use session; adjustment will be done only once
	if ( WFU_VAR("WFU_US_HANDLER_CHANGED") == "false" ) {
		$envars = get_option("wfu_environment_variables", array());
		{
			$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
			if ( $plugin_options["userstatehandler"] != "dboption" ) wfu_update_setting("userstatehandler", "dboption");
			if ( WFU_VAR("WFU_US_DBOPTION_BASE") != "cookies" ) {
				$GLOBALS["WFU_GLOBALS"]["WFU_US_DBOPTION_BASE"][3] = "cookies";
				$envars["WFU_US_DBOPTION_BASE"] = "cookies";
			}
		}
		$GLOBALS["WFU_GLOBALS"]["WFU_US_HANDLER_CHANGED"][3] = "true";
		$envars["WFU_US_HANDLER_CHANGED"] = "true";
		update_option("wfu_environment_variables", $envars);				
	}
}

/**
 * Actions Before Uninstalling Plugin.
 *
 * This function performs actions before uninstalling the plugin.
 *
 * @since 4.4.0
 */
function wordpress_file_upload_uninstall() {
}

/**
 * Actions After Plugins are Loaded.
 *
 * This function performs actions after plugin are loaded. It updates the
 * database tables in necessary.
 *
 * @since 2.4.1
 */
function wordpress_file_upload_update_db_check() {
	global $wfu_tb_log_version;
	global $wfu_tb_userdata_version;
	global $wfu_tb_dbxqueue_version;
//	update_option("wordpress_file_upload_table_log_version", "0");
//	update_option("wordpress_file_upload_table_userdata_version", "0");
//	update_option("wordpress_file_upload_table_dbxqueue_version", "0");
	if ( get_option('wordpress_file_upload_table_log_version') != $wfu_tb_log_version || get_option('wordpress_file_upload_table_userdata_version') != $wfu_tb_userdata_version || get_option('wordpress_file_upload_table_dbxqueue_version') != $wfu_tb_dbxqueue_version ) {
		wordpress_file_upload_install();
	}
}

/**
 * Process Dashboard Requests.
 *
 * This function processes Dashboard requests and shows main Dashboard pages of
 * the plugin in Settings.
 *
 * @since 2.1.2
 */
function wordpress_file_upload_manage_dashboard() {
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);
	$action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
	$dir = (!empty($_POST['dir']) ? $_POST['dir'] : (!empty($_GET['dir']) ? $_GET['dir'] : ''));
	$file = (!empty($_POST['file']) ? $_POST['file'] : (!empty($_GET['file']) ? $_GET['file'] : ''));
	$referer = (!empty($_POST['referer']) ? $_POST['referer'] : (!empty($_GET['referer']) ? $_GET['referer'] : ''));
	$data_enc = (!empty($_POST['data']) ? $_POST['data'] : (!empty($_GET['data']) ? $_GET['data'] : ''));
	$postid = (!empty($_POST['postid']) ? $_POST['postid'] : (!empty($_GET['postid']) ? $_GET['postid'] : ''));
	$nonce = (!empty($_POST['nonce']) ? $_POST['nonce'] : (!empty($_GET['nonce']) ? $_GET['nonce'] : ''));
	$tag = (!empty($_POST['tag']) ? $_POST['tag'] : (!empty($_GET['tag']) ? $_GET['tag'] : ''));
	$username = (!empty($_POST['username']) ? $_POST['username'] : (!empty($_GET['username']) ? $_GET['username'] : ''));
	$invoker = (!empty($_POST['invoker']) ? $_POST['invoker'] : (!empty($_GET['invoker']) ? $_GET['invoker'] : ''));
	$echo_str = "";

	if ( $action == 'edit_settings' ) {
		wfu_update_settings();
		$echo_str = wfu_manage_settings();
	}
	elseif ( $action == 'shortcode_composer' ) {
		$echo_str = wfu_shortcode_composer();
	}
	elseif ( $action == 'file_browser' ) {
		$echo_str = wfu_browse_files($dir);
	}
	elseif ( $action == 'view_log' ) {
		$page = $tag;
		if ( $page == '' ) $page = 1;
		$page = (int)wfu_sanitize_int($page);
		$located_rec = $invoker;
		if ( $located_rec == '' ) $located_rec = -1;
		$located_rec = (int)wfu_sanitize_int($located_rec);
		$echo_str = wfu_view_log($page, false, $located_rec);
	}
	elseif ( $action == 'rename_file' && $file != "" ) {
		$echo_str = wfu_rename_file_prompt($file, 'file', false);
	}
	elseif ( $action == 'rename_dir' && $file != "" ) {
		$echo_str = wfu_rename_file_prompt($file, 'dir', false);
	}
	elseif ( $action == 'move_file' && $file != "" ) {
		if ( substr($file, 0, 5) == "list:" ) $file = explode(",", substr($file, 5));
		$echo_str = wfu_move_file_prompt($file, false);
	}
	elseif ( $action == 'renamefile' && $file != "" ) {
		if ( wfu_rename_file($file, 'file') ) $echo_str = wfu_browse_files($dir);
		else $echo_str = wfu_rename_file_prompt($file, 'file', true);
	}
	elseif ( $action == 'renamedir' && $file != "" ) {
		if ( wfu_rename_file($file, 'dir') ) $echo_str = wfu_browse_files($dir);
		else $echo_str = wfu_rename_file_prompt($file, 'dir', true);
	}
	elseif ( $action == 'movefile' && $file != "" ) {
		if ( substr($file, 0, 5) == "list:" ) $file = explode(",", substr($file, 5));
		if ( wfu_move_file($file) ) $echo_str = wfu_browse_files($dir);
		else $echo_str = wfu_move_file_prompt($file, true);
	}
	elseif ( $action == 'delete_file' && $file != "" && $referer != "" ) {
		if ( substr($file, 0, 5) == "list:" ) $file = explode(",", substr($file, 5));
		$echo_str = wfu_delete_file_prompt($file, 'file', $referer);
	}
	elseif ( $action == 'delete_dir' && $file != "" && $referer != "" ) {
		$echo_str = wfu_delete_file_prompt($file, 'dir', $referer);
	}
	elseif ( $action == 'deletefile' && $file != "" ) {
		if ( substr($file, 0, 5) == "list:" ) $file = explode(",", substr($file, 5));
		wfu_delete_file($file, 'file');
		$referer_url = wfu_flatten_path(wfu_get_filepath_from_safe(wfu_sanitize_code($referer)));
		if ( $referer_url === false ) $referer_url = "";
		$match = array();
		preg_match("/\&dir=(.*)/", $referer_url, $match);
		$dir = ( isset($match[1]) ? $match[1] : "" );
		$echo_str = wfu_browse_files($dir);	
	}
	elseif ( $action == 'deletedir' && $file != "" ) {
		wfu_delete_file($file, 'dir');
		$referer_url = wfu_flatten_path(wfu_get_filepath_from_safe(wfu_sanitize_code($referer)));
		if ( $referer_url === false ) $referer_url = "";
		$match = array();
		preg_match("/\&dir=(.*)/", $referer_url, $match);
		$dir = ( isset($match[1]) ? $match[1] : "" );
		$echo_str = wfu_browse_files($dir);	
	}
	elseif ( $action == 'create_dir' ) {
		$echo_str = wfu_create_dir_prompt($dir, false);
	}
	elseif ( $action == 'createdir' ) {
		if ( wfu_create_dir($dir) ) $echo_str = wfu_browse_files($dir);
		else $echo_str = wfu_create_dir_prompt($dir, true);
	}
	elseif ( $action == 'include_file' && $file != "" && $referer != "" ) {
		if ( substr($file, 0, 5) == "list:" ) $file = explode(",", substr($file, 5));
		$echo_str = wfu_include_file_prompt($file, $referer);
	}
	elseif ( $action == 'includefile' && $file != "" ) {
		if ( substr($file, 0, 5) == "list:" ) $file = explode(",", substr($file, 5));
		wfu_include_file($file);
		$referer_url = wfu_flatten_path(wfu_get_filepath_from_safe(wfu_sanitize_code($referer)));
		if ( $referer_url === false ) $referer_url = "";
		$match = array();
		preg_match("/\&dir=(.*)/", $referer_url, $match);
		$dir = ( isset($match[1]) ? $match[1] : "" );
		$echo_str = wfu_browse_files($dir);	
	}
	elseif ( $action == 'file_details' && $file != "" ) {
		$echo_str = wfu_file_details($file, false, $invoker);
	}
	elseif ( $action == 'edit_filedetails' && $file != "" ) {
		wfu_edit_filedetails($file);
		$echo_str = wfu_file_details($file, false, $invoker);
	}
	elseif ( $action == 'personal_data' && $plugin_options["personaldata"] == "1" ) {
		$echo_str = wfu_manage_personaldata_policies();
	}
	elseif ( $action == 'erase_userdata_ask' && $plugin_options["personaldata"] == "1" && $username != "" ) {
		$echo_str = wfu_erase_userdata_ask_prompt($username);
	}
	elseif ( $action == 'erase_userdata' && $plugin_options["personaldata"] == "1" && $username != "" ) {
		$ret = wfu_erase_userdata($username);
		if ( $ret <= -1 ) $echo_str = wfu_manage_personaldata_policies();
		else $echo_str = wfu_manage_personaldata_policies('Database cleaned. '.$ret.' items where affected.');
	}
	elseif ( $action == 'maintenance_actions' ) {
		$echo_str = wfu_maintenance_actions();
	}
	elseif ( $action == 'sync_db' && $nonce != "" ) {
		$affected_items = wfu_sync_database_controller($nonce);
		if ( $affected_items > -1 ) $echo_str = wfu_maintenance_actions('Database updated. '.$affected_items.' items where affected.');
		else $echo_str = wfu_maintenance_actions();
	}
	elseif ( $action == 'clean_log_ask' && $nonce != "" && $data_enc != "" ) {
		$echo_str = wfu_clean_log_prompt($nonce, $data_enc);
	}
	elseif ( $action == 'clean_log' ) {
		$ret = wfu_clean_log();
		if ( $ret["recs_count"] <= -1 && $ret["files_count"] ) $echo_str = wfu_maintenance_actions();
		else $echo_str = wfu_maintenance_actions('Database cleaned. '.$ret["recs_count"].' records and '.$ret["files_count"].' files where deleted.');
	}
	elseif ( $action == 'purge_data_ask' && $nonce != "" ) {
		$echo_str = wfu_purge_data_prompt($nonce);
	}
	elseif ( $action == 'purge_data' ) {
		$ret = wfu_purge_data();
		if ( !$ret ) $echo_str = wfu_maintenance_actions();
		else $echo_str = '<script type="text/javascript">window.location.replace("'.admin_url('plugins.php').'");</script>';
	}
	elseif ( $action == 'reset_all_transfers' && $nonce != "" ) {
		if ( wfu_reset_all_transfers_controller($nonce) === true )
			$echo_str = wfu_maintenance_actions('All file transfers were successfully reset.');
		else $echo_str = wfu_maintenance_actions();
	}
	elseif ( $action == 'clear_all_transfers' && $nonce != "" ) {
		if ( wfu_clear_all_transfers_controller($nonce) === true )
			$echo_str = wfu_maintenance_actions('All file transfers were successfully cleared.');
		else $echo_str = wfu_maintenance_actions();
	}
	elseif ( $action == 'plugin_settings' ) {
		$echo_str = wfu_manage_settings();	
	}
	elseif ( $action == 'add_shortcode' && $postid != "" && $nonce != "" && $tag != "" ) {
		if ( WFU_USVAR('wfu_add_shortcode_ticket_for_'.$tag) != $nonce ) $echo_str = wfu_manage_mainmenu();
		elseif ( wfu_add_shortcode($postid, $tag) ) $echo_str = wfu_manage_mainmenu();
		else $echo_str = wfu_manage_mainmenu(WFU_DASHBOARD_ADD_SHORTCODE_REJECTED);
		WFU_USVAR_store('wfu_add_shortcode_ticket', 'noticket');
	}
	elseif ( $action == 'edit_shortcode' && $data_enc != "" && $tag != "" ) {
		$data = wfu_decode_array_from_string(wfu_get_shortcode_data_from_safe($data_enc));
		if ( $data['post_id'] == "" || $referer == 'guteditor' || wfu_check_edit_shortcode($data) ) wfu_shortcode_composer($data, $tag, $referer);
		else $echo_str = wfu_manage_mainmenu(WFU_DASHBOARD_EDIT_SHORTCODE_REJECTED);
	}
	elseif ( $action == 'delete_shortcode' && $data_enc != "" ) {
		$data = wfu_decode_array_from_string(wfu_get_shortcode_data_from_safe($data_enc));
		if ( wfu_check_edit_shortcode($data) ) $echo_str = wfu_delete_shortcode_prompt($data_enc);
		else $echo_str = wfu_manage_mainmenu(WFU_DASHBOARD_DELETE_SHORTCODE_REJECTED);
	}
	elseif ( $action == 'deleteshortcode' && $data_enc != "" ) {
		$data = wfu_decode_array_from_string(wfu_get_shortcode_data_from_safe($data_enc));
		if ( wfu_check_edit_shortcode($data) ) {
			if ( wfu_delete_shortcode($data) ) wfu_clear_shortcode_data_from_safe($data_enc);
			$echo_str = wfu_manage_mainmenu();
		}
		else $echo_str = wfu_manage_mainmenu(WFU_DASHBOARD_DELETE_SHORTCODE_REJECTED);
	}
	elseif ( $action == 'add_policy' ) {
		$echo_str = wfu_edit_pd_policy();
	}
	else {
		$echo_str = wfu_manage_mainmenu();
	}

	echo $echo_str;
}

/**
 * Process Dashboard Requests for Non-Admin Users.
 *
 * This function processes Dashboard requests and shows the shortcode composer
 * to users that are not admins but who can edit posts or pages. It also lets
 * extensions implement their own actions when receiving Dashboard requests by
 * non-admin users.
 *
 * @since 4.11.0
 */
function wordpress_file_upload_manage_dashboard_editor() {
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);
	$action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
	$referer = (!empty($_POST['referer']) ? $_POST['referer'] : (!empty($_GET['referer']) ? $_GET['referer'] : ''));
	$data_enc = (!empty($_POST['data']) ? $_POST['data'] : (!empty($_GET['data']) ? $_GET['data'] : ''));
	$tag = (!empty($_POST['tag']) ? $_POST['tag'] : (!empty($_GET['tag']) ? $_GET['tag'] : ''));
	$echo_str = "";

	if ( $action == 'edit_shortcode' && $data_enc != "" && $tag != "" ) {
		$data = wfu_decode_array_from_string(wfu_get_shortcode_data_from_safe($data_enc));
		if ( $data['post_id'] == "" || $referer == 'guteditor' || wfu_check_edit_shortcode($data) ) wfu_shortcode_composer($data, $tag, $referer);
		else $echo_str = wfu_manage_mainmenu(WFU_DASHBOARD_EDIT_SHORTCODE_REJECTED);
	}
	else {
		$echo_str = wfu_manage_mainmenu_editor();
	}

	echo $echo_str;
}

/**
 * Display the Main Dashboard Page.
 *
 * This function displays the Main Dashboard page of the plugin.
 *
 * @since 2.5.2
 *
 * @param string $message Optional. A message to display on top when showing
 *        Main page of the plugin in Dashboard.
 *
 * @return string The HTML output of the plugin's Main Dashboard page.
 */
function wfu_manage_mainmenu($message = '') {
	if ( !current_user_can( 'manage_options' ) ) return;
	
	//get php version
	$php_version = preg_replace("/-.*/", "", phpversion());

	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	
	$echo_str = '<div class="wrap wfumain">';
	$echo_str .= "\n\t".'<h2>Wordpress File Upload Control Panel</h2>';
	if ( $message != '' ) {
		$echo_str .= "\n\t".'<div class="updated">';
		$echo_str .= "\n\t\t".'<p>'.$message.'</p>';
		$echo_str .= "\n\t".'</div>';
	}
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= wfu_generate_dashboard_menu("\n\t\t", "Main");
	$echo_str .= "\n\t\t".'<h3 style="margin-bottom: 10px;">Status';
	if ( $plugin_options["altserver"] == "1" && substr(trim(WFU_VAR("WFU_ALT_IPTANUS_SERVER")), 0, 5) == "http:" ) {
		$echo_str .= '<div style="display: inline-block; margin-left:20px;" title="'.WFU_WARNING_ALT_IPTANUS_SERVER_ACTIVATED.'"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 200 800" version="1.1" style="background:darkorange; border-radius:13px; padding:2px; vertical-align:middle; border: 1px solid silver;"><path d="M 110,567 L 90,567 L 42,132 C 40,114 40,100 40,90 C 40,70 45,49 56,35 C 70,22 83,15 100,15 C 117,15 130,22 144,35 C 155,49 160,70 160,90 C 160,100 160,114 158,132 z M 100,640 A 60,60 0 1,1 100,760 A 60,60 0 1,1 100,640 z"/></svg></div>';
	}
	$echo_str .= '</h3>';
	$echo_str .= "\n\t\t".'<table class="form-table">';
	$echo_str .= "\n\t\t\t".'<tbody>';
	//plugin edition
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label style="cursor:default;">Edition</label>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td style="width:100px; vertical-align:top;">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label style="font-weight:bold; cursor:default;">Free</label>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t\t".'<div style="display:inline-block; background-color:bisque; padding:0 0 0 4px; border-left:3px solid lightcoral;">';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<label style="cursor:default;">Consider </label><a href="'.WFU_PRO_VERSION_URL.'">Upgrading</a><label style="cursor:default;"> to the Professional Version. </label>';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<button onclick="if (this.innerText == \'See why >>\') {this.innerText = \'<< Close\'; document.getElementById(\'wfu_version_comparison\').style.display = \'inline-block\';} else {this.innerText = \'See why >>\'; document.getElementById(\'wfu_version_comparison\').style.display = \'none\';}">See why >></button>';
	$echo_str .= "\n\t\t\t\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t\t\t\t".'<br /><div id="wfu_version_comparison" style="display:none; background-color:lightyellow; border:1px solid yellow; margin:10px 0; padding:10px;">';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<img src="'.WFU_IMAGE_VERSION_COMPARISON.'" style="display:block; margin-bottom:6px;" />';
	$echo_str .= "\n\t\t\t\t\t\t\t".'<a class="button-primary" href="'.WFU_PRO_VERSION_URL.'">Go for the PRO version</a>';
	$echo_str .= "\n\t\t\t\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	//plugin version
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label style="cursor:default;">Version</label>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td style="width:100px;">';
	$cur_version = wfu_get_plugin_version();
	$echo_str .= "\n\t\t\t\t\t\t".'<label style="font-weight:bold; cursor:default;">'.$cur_version.'</label>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$lat_version = wfu_get_latest_version();
	$ret = wfu_compare_versions($cur_version, $lat_version);
	if ( $lat_version == "" && WFU_VAR("WFU_DISABLE_VERSION_CHECK") != "true" ) {
		$echo_str .= "\n\t\t\t\t\t\t".'<div style="display:inline-block; background-color:transparent; padding:0 0 0 4px; color:red;">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 200 800" version="1.1" style="background:transparent; border-radius:13px; padding:2px; vertical-align:middle; border: 2px solid red; fill:red;"><path d="M 110,567 L 90,567 L 42,132 C 40,114 40,100 40,90 C 40,70 45,49 56,35 C 70,22 83,15 100,15 C 117,15 130,22 144,35 C 155,49 160,70 160,90 C 160,100 160,114 158,132 z M 100,640 A 60,60 0 1,1 100,760 A 60,60 0 1,1 100,640 z"/></svg>';
		$warning_text = preg_replace("/:(\w+):/", '<a target="_blank" href="'.WFU_IPTANUS_SERVER_UNREACHABLE_ARTICLE.'" title="Iptanus Services Server Unreachable Error of WFU Plugin">$1</a>', WFU_WARNING_IPTANUS_SERVER_UNREACHABLE);
		$echo_str .= "\n\t\t\t\t\t\t\t".'<label style="cursor:default;">'.$warning_text.'</label>';
		$echo_str .= "\n\t\t\t\t\t\t".'</div>';
	}
	elseif ( $ret['status'] && $ret['result'] == 'lower' ) {
		$echo_str .= "\n\t\t\t\t\t\t".'<div style="display:inline-block; background-color:bisque; padding:0 0 0 4px; border-left:3px solid lightcoral;">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<label style="cursor:default;">Version <strong>'.$lat_version.'</strong> of the plugin is available. Go to Plugins page of your Dashboard to update to the latest version.</label>';
		if ( $ret['custom'] ) $echo_str .= '<label style="cursor:default; color: purple;"> <em>Please note that you are using a custom version of the plugin. If you upgrade to the newest version, custom changes will be lost.</em></label>';
		$echo_str .= "\n\t\t\t\t\t\t".'</div>';
	}
	elseif ( $ret['status'] && $ret['result'] == 'equal' ) {
		$echo_str .= "\n\t\t\t\t\t\t".'<div style="display:inline-block; background-color:rgb(220,255,220); padding:0 0 0 4px; border-left:3px solid limegreen;">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<label style="cursor:default;">You have the latest version.</label>';
		if ( $ret['custom'] ) $echo_str .= '<label style="cursor:default; color: purple;"> <em>(Please note that your version is custom)</em></label>';
		$echo_str .= "\n\t\t\t\t\t\t".'</div>';
	}
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	//server environment
	$php_env = wfu_get_server_environment();
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label style="cursor:default;">Server Environment</label>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td style="width:100px;">';
	if ( $php_env == '64bit' ) $echo_str .= "\n\t\t\t\t\t\t".'<label style="font-weight:bold; cursor:default;">64bit</label></td><td><label style="font-weight:normal; font-style:italic; cursor:default;">(Your server supports files up to 1 Exabyte, practically unlimited)</label>';
	if ( $php_env == '32bit' ) $echo_str .= "\n\t\t\t\t\t\t".'<label style="font-weight:bold; cursor:default;">32bit</label></td><td><label style="font-weight:normal; font-style:italic; cursor:default;">(Your server does not support files larger than 2GB)</label>';
	if ( $php_env == '' ) $echo_str .= "\n\t\t\t\t\t\t".'<label style="font-weight:bold; cursor:default;">Unknown</label></td><td><label style="font-weight:normal; font-style:italic; cursor:default;">(The maximum file size supported by the server cannot be determined)</label>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label style="cursor:default;">PHP Version</label>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td style="width:100px;">';
	$cur_version = wfu_get_plugin_version();
	$echo_str .= "\n\t\t\t\t\t\t".'<label style="font-weight:bold; cursor:default;">'.$php_version.'</label>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t\t".'<td>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="row">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label style="cursor:default;">Release Notes</label>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<td colspan="2" style="width:100px;">';
	$rel_path = ABSWPFILEUPLOAD_DIR.'release_notes.txt';
	$rel_notes = '';
	if ( file_exists($rel_path) ) $rel_notes = file_get_contents($rel_path);
	$echo_str .= "\n\t\t\t\t\t\t".'<div style="text-align:justify;">'.$rel_notes.'</div>';
	$echo_str .= "\n\t\t\t\t\t".'</td>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t".'</table>';

	$echo_str .= wfu_manage_instances();

	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n".'</div>';
	
	return $echo_str;
}

/**
 * Default Dashboard Page for Non-Admin Users.
 *
 * This function displays the plugin's default Dashboard page for non-admin
 * users who can edit pages or posts.
 *
 * @since 4.11.0
 *
 * @param string $message Optional. A message to display on top when showing
 *        the default Dashboard page of the plugin for non-admin users.
 *
 * @return string The HTML output of the plugin's default Dashboard page.
 */
function wfu_manage_mainmenu_editor($message = '') {
	if ( !current_user_can( 'edit_pages' ) && !current_user_can( 'edit_posts' ) ) return;
	
	$echo_str = '<div class="wrap wfumain">';
	$echo_str .= "\n\t".'<h2>Wordpress File Upload Control Panel</h2>';
	if ( $message != '' ) {
		$echo_str .= "\n\t".'<div class="updated">';
		$echo_str .= "\n\t\t".'<p>'.$message.'</p>';
		$echo_str .= "\n\t".'</div>';
	}
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<h3 style="margin-bottom: 10px;">This menu item exists to show the plugin\'s shortcode composer when editing pages or posts.</h3>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n".'</div>';
	
	return $echo_str;
}

/**
 * Main Dashboard Page Tabs.
 *
 * This function generates the tabs of the plugin's main area in Dashboard.
 *
 * @since 3.6.0
 *
 * @redeclarable
 *
 * @param string $dlp Identation string before the beginning of each HTML line.
 * @param string $active The name of the tab that it is active.
 *
 * @return string The HTML output of the tabs.
 */
function wfu_generate_dashboard_menu($dlp, $active) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$siteurl = site_url();
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	
	$echo_str = $dlp.'<h2 class="nav-tab-wrapper" style="margin-bottom:40px;">';
	$echo_str .= $dlp."\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="nav-tab'.( $active == "Main" ? ' nav-tab-active' : '' ).'" title="Main">Main</a>';
	$echo_str .= $dlp."\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=plugin_settings" class="nav-tab'.( $active == "Settings" ? ' nav-tab-active' : '' ).'" title="Settings">Settings</a>';
	$echo_str .= $dlp."\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=file_browser" class="nav-tab'.( $active == "File Browser" ? ' nav-tab-active' : '' ).'" title="File browser">File Browser</a>';
	$echo_str .= $dlp."\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=view_log" class="nav-tab'.( $active == "View Log" ? ' nav-tab-active' : '' ).'" title="View log">View Log</a>';
	if ( $plugin_options["personaldata"] == "1" )
		$echo_str .= $dlp."\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=personal_data" class="nav-tab'.( $active == "Personal Data" ? ' nav-tab-active' : '' ).'" title="Personal Data">Personal Data</a>';
	$echo_str .= $dlp."\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=maintenance_actions" class="nav-tab'.( $active == "Maintenance Actions" ? ' nav-tab-active' : '' ).'" title="Maintenance Actions">Maintenance Actions</a>';
	$echo_str .= $dlp.'</h2>';
	
	return $echo_str;
}

/**
 * Generate List of Posts in Tree Order.
 *
 * This function converts a flat array of posts into a tree structure, where an
 * individual item of the returned array may contain a nested array of children.
 * Items of the same parent and level are sorted by post status (publish,
 * private, draft) and then by title.
 *
 * @since 2.7.6
 *
 * @param array $posts The initial flat array of posts.
 *
 * @return array The returned list of posts in tree order.
 */
function wfu_construct_post_list($posts) {
	$ids = array();
	$list = array();
	$id_keys = array();
	//construct item indices
	foreach ( $posts as $key => $post ) {
		if ( !array_key_exists($post->post_type, $ids) ) {
			$ids[$post->post_type] = array();
			$list[$post->post_type] = array();
		}
		array_push($ids[$post->post_type], $post->ID);
		$id_keys[$post->ID] = $key;
	}
	//create post list in tree order; items are sorted by post status (publish,
	//private, draft) and then by title
	$i = 0;
	while ( $i < count($posts) ) {
		$post = $posts[$i];
		//find topmost element in family tree
		$tree = array( $post->ID );
		$topmost = $post;
		$par_id = $topmost->post_parent;
		while ( in_array($par_id, $ids[$post->post_type]) ) {
			$topmost = $posts[$id_keys[$par_id]];
			array_splice($tree, 0, 0, $par_id);
			$par_id = $topmost->post_parent;
		}
		//find which needs to be processed
		$level = 0;
		$host = &$list[$post->post_type];
		foreach ( $tree as $process_id ) {
			$found_key = -1;
			foreach ( $host as $key => $item )
				if ( $item['id'] == $process_id ) {
					$found_key = $key;
					break;
				}
			if ( $found_key == -1 ) break;
			$level++;
			$host = &$host[$found_key]['children'];
		}
		if ( $found_key == -1 ) {
			$processed = $posts[$id_keys[$process_id]];
			//add the processed item in the right position in children's list
			$pos = 0;
			$status = ( $processed->post_status == 'publish' ? 0 : ( $processed->post_status == 'private' ? 1 : 2 ) );
			foreach ($host as $item) {
				if ( $status < $item['status'] ) break;
				if ( $status == $item['status'] && strcmp($processed->post_title, $item['title']) < 0 ) break;
				$pos++;
			}
			$new_item = array(
				'id'		=> $process_id,
				'title' 	=> $processed->post_title,
				'status' 	=> $status,
				'level' 	=> $level,
				'children' 	=> array()
			);
			array_splice($host, $pos, 0, array($new_item));
		}
		//advance index if we have finished processing all the tree
		if ( $process_id == $post->ID ) $i++;
	}
	return $list;
}

/**
 * Flatten Tree List of Posts.
 *
 * This function converts a list that contains posts in tree order into a flat
 * list (array) of posts.
 *
 * @since 2.7.6
 *
 * @param array $list The initial tree list of posts.
 *
 * @return array The returned flat list of posts.
 */
function wfu_flatten_post_list($list) {
	$flat = array();
	if ( !is_array($list) ) return $flat;
	foreach( $list as $item ) {
		$flat_item = array(
			'id'		=> $item['id'],
			'title'		=> $item['title'],
			'status'	=> $item['status'],
			'level'		=> $item['level']
		);
		array_push($flat, $flat_item);
		$flat = array_merge($flat, wfu_flatten_post_list($item['children']));
	}
	return $flat;
}

/**
 * Generate List of Instances of All Plugin' Shortcodes.
 *
 * This function generates a tabular list of all instances of all plugin's
 * shortcodes.
 *
 * @since 2.5.2
 *
 * @return string The HTML code of the list of instances of all the shortcodes.
 */
function wfu_manage_instances() {
	$echo_str = wfu_manage_instances_of_shortcode('wordpress_file_upload', 'Uploader Instances', 'uploader', 1);
	
	return $echo_str;
}

/**
 * Generate List of Instances of A Plugin' Shortcode.
 *
 * This function generates a tabular list of all instances of a plugin's
 * shortcode.
 *
 * @since 3.1.0
 *
 * @param string $tag The shortcode tag.
 * @param string $title The title of the list
 * @param string $slug A slug of the shortcode.
 * @param integer $inc The increment number of this list of instances.
 *
 * @return string The HTML code of the list of instances of the shortcode.
 */
function wfu_manage_instances_of_shortcode($tag, $title, $slug, $inc) {
	global $wp_registered_widgets, $wp_registered_sidebars;
	
	$siteurl = site_url();
	$args = array( 'post_type' => array( "post", "page" ), 'post_status' => "publish,private,draft", 'posts_per_page' => -1 );
	/**
	 * Filter Arguments for Getting List of Posts.
	 *
	 * This filter allows to customize the arguments passed to get_posts()
	 * function to get a list of posts. By default the plugin will get a list of
	 * all posts and pages. If the website contains too many posts this
	 * operation may take time and delay loading of the page. So this filter can
	 * be used to optimize this operation.
	 *
	 * @since 4.0.0
	 *
	 * @param array $args Arguments to retrieve posts.
	 * @param string $operation A parameter designating in which operation this
	 *        filter is used.
	 */
	$args = apply_filters("_wfu_get_posts", $args, "manage_instances");
	$posts = get_posts($args);
	$wfu_shortcodes = array();
	//get shortcode instances from page/posts 
	foreach ( $posts as $post ) {
		$ret = wfu_get_content_shortcodes($post, $tag);
		if ( $ret !== false ) $wfu_shortcodes = array_merge($wfu_shortcodes, $ret);
	}
	//get shortcode instances from sidebars
	$data = array();
	$widget_base = $tag.'_widget';
	if ( is_array($wp_registered_widgets) ) {
		foreach ( $wp_registered_widgets as $id => $widget ) {
			if ( substr($id, 0, strlen($widget_base)) == $widget_base ) {
				$widget_obj = ( isset($widget['callback']) ? ( isset($widget['callback'][0]) ? ( $widget['callback'][0] instanceof WP_Widget ? $widget['callback'][0] : false ) : false ) : false );
				$widget_sidebar = is_active_widget(false, $id, $widget_base);
				if ( $widget_obj !== false && $widget_sidebar !== false ) {
					if ( isset($wp_registered_sidebars[$widget_sidebar]) && isset($wp_registered_sidebars[$widget_sidebar]['name']) ) $widget_sidebar = $wp_registered_sidebars[$widget_sidebar]['name'];
					$data['post_id'] = "";
					$data['post_hash'] = "";
					$data['shortcode'] = $widget_obj->shortcode();
					$data['position'] = 0;
					$data['widgetid'] = $id;
					$data['sidebar'] = $widget_sidebar;
					array_push($wfu_shortcodes, $data);
				}
			}
		}
	}

	$list = wfu_construct_post_list($posts);
	$pagelist = wfu_flatten_post_list($list["page"]);
	$postlist = wfu_flatten_post_list($list["post"]);

	$echo_str = "\n\t\t".'<h3 style="margin-bottom: 10px; margin-top: 40px;">'.$title.'</h3>';
	$onchange_js = 'document.getElementById(\'wfu_add_plugin_ok_'.$inc.'\').disabled = !((document.getElementById(\'wfu_page_type_'.$inc.'\').value == \'page\' && document.getElementById(\'wfu_page_list_'.$inc.'\').value != \'\') || (document.getElementById(\'wfu_page_type_'.$inc.'\').value == \'post\' && document.getElementById(\'wfu_post_list_'.$inc.'\').value != \'\'));';
	$no_shortcodes = ( count($wfu_shortcodes) == 0 );
	$echo_str .= "\n\t\t".'<div id="wfu_add_plugin_button_'.$inc.'" style="'. ( !$no_shortcodes ? '' : 'color:blue; font-weight:bold; font-size:larger;' ).'margin-bottom: 20px; margin-top: 10px;">';
	$addbutton_pre = ( !$no_shortcodes ? '' : '<label>Press </label>');
	$addbutton_post = ( !$no_shortcodes ? '' : '<label> to get started and add the '.$slug.' in a page</label>');
	$echo_str .= "\n\t\t\t".$addbutton_pre.'<button onclick="document.getElementById(\'wfu_add_plugin_button_'.$inc.'\').style.display = \'none\'; document.getElementById(\'wfu_add_plugin_'.$inc.'\').style.display = \'inline-block\'; '.$onchange_js.'">'.( !$no_shortcodes ? 'Add Plugin Instance' : 'here' ).'</button>'.$addbutton_post;
	$echo_str .= "\n\t\t".'</div>';
	$echo_str .= "\n\t\t".'<div id="wfu_add_plugin_'.$inc.'" style="margin-bottom: 20px; margin-top: 10px; position:relative; display:none;">';
	$echo_str .= "\n\t\t\t".'<div id="wfu_add_plugin_'.$inc.'_overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; background-color:rgba(255,255,255,0.8); border:none; display:none;">';
	$echo_str .= "\n\t\t\t\t".'<table style="background:none; border:none; margin:0; padding:0; line-height:1; border-spacing:0; width:100%; height:100%; table-layout:fixed;"><tbody><tr><td style="text-align:center; vertical-align:middle;"><div style="display:inline-block;"><span class="spinner" style="opacity:1; float:left; margin:0; display:inline;"></span><label style="margin-left:4px;">please wait...</label></div></td></tr></tbody></table>';
	$echo_str .= "\n\t\t\t".'</div>';
	$echo_str .= "\n\t\t\t".'<label>Add '.$slug.' to </label><select id="wfu_page_type_'.$inc.'" onchange="document.getElementById(\'wfu_page_list_'.$inc.'\').style.display = (this.value == \'page\' ? \'inline-block\' : \'none\'); document.getElementById(\'wfu_post_list_'.$inc.'\').style.display = (this.value == \'post\' ? \'inline-block\' : \'none\'); '.$onchange_js.'"><option value="page" selected="selected">Page</option><option value="post">Post</option></select>';
	$echo_str .= "\n\t\t\t".'<select id="wfu_page_list_'.$inc.'" style="margin-bottom:6px;" onchange="'.$onchange_js.'">';
	$echo_str .= "\n\t\t\t\t".'<option value=""></option>';
	foreach ( $pagelist as $item )
		$echo_str .= "\n\t\t\t\t".'<option value="'.$item['id'].'">'.str_repeat('&nbsp;', 4 * $item['level']).( $item['status'] == 1 ? '[Private]' : ( $item['status'] == 2 ? '[Draft]' : '' ) ).$item['title'].'</option>';
	$echo_str .= "\n\t\t\t".'</select>';
	$echo_str .= "\n\t\t\t".'<select id="wfu_post_list_'.$inc.'" style="display:none; margin-bottom:6px;" onchange="'.$onchange_js.'">';
	$echo_str .= "\n\t\t\t\t".'<option value=""></option>';
	foreach ( $postlist as $item )
		$echo_str .= "\n\t\t\t\t".'<option value="'.$item['id'].'">'.str_repeat('&nbsp;', 4 * $item['level']).( $item['status'] == 1 ? '[Private]' : ( $item['status'] == 2 ? '[Draft]' : '' ) ).$item['title'].'</option>';
	$echo_str .= "\n\t\t\t".'</select><br />';
	$add_shortcode_ticket = wfu_create_random_string(16);
	WFU_USVAR_store('wfu_add_shortcode_ticket_for_'.$tag, $add_shortcode_ticket);
	$echo_str .= "\n\t\t".'<button id="wfu_add_plugin_ok_'.$inc.'" style="float:right; margin: 0 2px 0 4px;" disabled="disabled" onclick="document.getElementById(\'wfu_add_plugin_'.$inc.'_overlay\').style.display = \'block\'; window.location = \''.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=add_shortcode&amp;tag='.$tag.'&amp;postid=\' + (document.getElementById(\'wfu_page_type_'.$inc.'\').value == \'page\' ? document.getElementById(\'wfu_page_list_'.$inc.'\').value : document.getElementById(\'wfu_post_list_'.$inc.'\').value) + \'&amp;nonce='.$add_shortcode_ticket.'\';">Ok</button>';
	$echo_str .= "\n\t\t".'<button style="float:right;" onclick="document.getElementById(\'wfu_page_type_'.$inc.'\').value = \'page\'; document.getElementById(\'wfu_page_list_'.$inc.'\').value = \'\'; document.getElementById(\'wfu_post_list_'.$inc.'\').value = \'\'; document.getElementById(\'wfu_add_plugin_'.$inc.'\').style.display = \'none\'; document.getElementById(\'wfu_add_plugin_button_'.$inc.'\').style.display = \'inline-block\';">Cancel</button>';
	$echo_str .= "\n\t\t".'</div>';
	$echo_str .= "\n\t\t".'<table class="wp-list-table widefat fixed striped">';
	$echo_str .= "\n\t\t\t".'<thead>';
	$echo_str .= "\n\t\t\t\t".'<tr>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="5%" class="manage-column column-primary">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>ID</label>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
//	$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="10%" style="text-align:center;">';
//	$echo_str .= "\n\t\t\t\t\t\t".'<label>ID</label>';
//	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="10%" class="manage-column">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Contained In</label>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="30%" class="manage-column">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Page/Post Title</label>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t\t".'<th scope="col" width="45%" class="manage-column">';
	$echo_str .= "\n\t\t\t\t\t\t".'<label>Shortcode</label>';
	$echo_str .= "\n\t\t\t\t\t".'</th>';
	$echo_str .= "\n\t\t\t\t".'</tr>';
	$echo_str .= "\n\t\t\t".'</thead>';
	$echo_str .= "\n\t\t\t".'<tbody>';
	$i = 1;
	foreach ( $wfu_shortcodes as $key => $data ) {
		$widget_id = ( isset($data['widgetid']) ? $data['widgetid'] : '' );
		if ( $widget_id == "" ) {
			$id = $data['post_id'];
			$posttype_obj = get_post_type_object(get_post_type($id));
			$type = ( $posttype_obj ? $posttype_obj->labels->singular_name : "" );
			$title = get_the_title($id);
			if ( trim($title) == "" ) $title = 'ID: '.$id;
		}
		else {
			$type = 'Sidebar';
			$title = $data['sidebar'];
		}
		$data_enc = wfu_safe_store_shortcode_data(wfu_encode_array_to_string($data));
		$echo_str .= "\n\t\t\t\t".'<tr onmouseover="var actions=document.getElementsByName(\'wfu_shortcode_actions_'.$inc.'\'); for (var i=0; i<actions.length; i++) {actions[i].style.visibility=\'hidden\';} document.getElementById(\'wfu_shortcode_actions_'.$inc.'_'.$i.'\').style.visibility=\'visible\'" onmouseout="var actions=document.getElementsByName(\'wfu_shortcode_actions_'.$inc.'\'); for (var i=0; i<actions.length; i++) {actions[i].style.visibility=\'hidden\';}">';
		$echo_str .= "\n\t\t\t\t\t".'<td class="column-primary" data-colname="ID">';
		$echo_str .= "\n\t\t\t\t\t\t".'<a class="row-title" href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=edit_shortcode&tag='.$tag.'&data='.$data_enc.'&referer=dashboard" title="Instance #'.$i.'">Instance '.$i.'</a>';
		$echo_str .= "\n\t\t\t\t\t\t".'<div id="wfu_shortcode_actions_'.$inc.'_'.$i.'" name="wfu_shortcode_actions_'.$inc.'" style="visibility:hidden;">';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<span>';
		$echo_str .= "\n\t\t\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=edit_shortcode&tag='.$tag.'&data='.$data_enc.'&referer=dashboard" title="Edit this shortcode">Edit</a>';
		$echo_str .= "\n\t\t\t\t\t\t\t\t".' | ';
		$echo_str .= "\n\t\t\t\t\t\t\t".'</span>';
		$echo_str .= "\n\t\t\t\t\t\t\t".'<span>';
		$echo_str .= "\n\t\t\t\t\t\t\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&action=delete_shortcode&data='.$data_enc.'" title="Delete this shortcode">Delete</a>';
		$echo_str .= "\n\t\t\t\t\t\t\t".'</span>';
		$echo_str .= "\n\t\t\t\t\t\t".'</div>';
		$echo_str .= "\n\t\t\t\t\t\t".'<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>';
		$echo_str .= "\n\t\t\t\t\t".'</td>';
//		$echo_str .= "\n\t\t\t\t\t".'<td style="padding: 5px 5px 5px 10px; text-align:center;">'.$id.'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Contained In">'.$type.'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Page/Post Title">'.$title.'</td>';
		$echo_str .= "\n\t\t\t\t\t".'<td data-colname="Shortcode">';
		$echo_str .= "\n\t\t\t\t\t\t".'<textarea rows="3" disabled="disabled" style="width:100%;">'.trim($data['shortcode']).'</textarea>';
		$echo_str .= "\n\t\t\t\t\t".'</td>';
		$echo_str .= "\n\t\t\t\t".'</tr>';
		$i++;
	}
	$echo_str .= "\n\t\t\t".'</tbody>';
	$echo_str .= "\n\t\t".'</table>';
	
	return $echo_str;
}

/**
 * Get Shortcodes Contained In A Post.
 *
 * This function returns an array of shortcodes contained inside a post.
 *
 * @since 2.5.4
 *
 * @param object $post The post to check for shortcodes.
 * @param string $tag The shortcode tag to look for.
 *
 * @return array An array of shortcodes contained inside the post.
 */
function wfu_get_content_shortcodes($post, $tag) {
	global $shortcode_tags;
	$found_shortcodes = array();
	$content = $post->post_content;
	if ( false !== strpos( $content, '[' ) ) {
		$hash = hash('md5', $content);
		if ( array_key_exists( $tag, $shortcode_tags ) ) wfu_match_shortcode_nested($tag, $post, $hash, $content, 0, $found_shortcodes);
	}
	/**
	 * Let Custom Scripts Modify the Found Shortcodes.
	 *
	 * This filter allows to execute custom scripts in order to modify the
	 * found shortcodes. It allows to make the plugin compatible with page
	 * builders, like Elementor, that do not handle posts / pages the way
	 * Wordpress does.
	 *
	 * @since 4.12.2
	 *
	 * @param array $found_shortcodes The list of found shortcodes.
	 * @param object $post The post to check for shortcodes.
	 * @param string $tag The shortcode tag to look for.
	 */
	$found_shortcodes = apply_filters("_wfu_get_content_shortcodes", $found_shortcodes, $post, $tag);

	if ( count($found_shortcodes) == 0 ) $found_shortcodes = false;
		
	return $found_shortcodes;
}

/**
 * Match Shortcodes.
 *
 * This function matches all shortcodes inside post contents. It performs
 * matching recursively in order to identify shortcodes contained in other
 * shortcodes.
 *
 * @since 2.7.6
 *
 * @param string $tag The shortcode tag to look for.
 * @param object $post The post to check for shortcodes.
 * @param string $hash A unique hash representing the current contents of the
 *        post.
 * @param string $content The content where to look for shortcodes.
 * @param integer $position The starting position of content.
 * @param array $found_shortcodes An array of already found shortcodes that must
 *        be filled by additional shortcodes found from this function.
 */
function wfu_match_shortcode_nested($tag, $post, $hash, $content, $position, &$found_shortcodes) {
	if ( false === strpos( $content, '[' ) ) return false;
	preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
	if ( empty( $matches ) ) return false;
	foreach ( $matches as $shortcode ) {
		if ( $tag === $shortcode[2][0] ) {
			$data['post_id'] = $post->ID;
			$data['post_hash'] = $hash;
			$data['shortcode'] = $shortcode[0][0];
			$data['position'] = (int)$shortcode[0][1] + (int)$position;
			array_push($found_shortcodes, $data);
		}
		wfu_match_shortcode_nested($tag, $post, $hash, $shortcode[5][0], $shortcode[5][1] + (int)$position, $found_shortcodes);
	}
	return false;
}

/**
 * Check Whether Shortcode Can Be Edited.
 *
 * This function checks whether the shortcode submitted for editing can actually
 * be edited. It checks whether the hash of the post, where the shortcode is
 * contained, is the same with the one stored in the shortcode data. If it is
 * not, then this means that the page contents have changed, so the shortcode
 * cannot be edited and the user will have to reload the page before editing the
 * shortcode.
 *
 * @since 2.6.0
 *
 * @param array $data The shortcode data to check.
 *
 * @return bool True if the shortcode can be edited, false otherwise.
 */
function wfu_check_edit_shortcode($data) {
	$post = get_post($data['post_id']);
	/** This filter is described in wfu_loader.php */
	$content = apply_filters("_wfu_get_post_content", $post->post_content, $post);
	$hash = hash('md5', $content);
	
	return ( $hash == $data['post_hash'] );
}

/**
 * Add Shortcode Inside Post.
 *
 * This function adds a shortcode at the beginning of post's contents.
 *
 * @since 2.7.6
 *
 * @param integer $postid The post ID where to add the shortcode.
 * $param string $tag The shortcode tag to add in post.
 *
 * @return bool True if the shortcode was added successfully inside the post,
 *         false otherwise.
 */
function wfu_add_shortcode($postid, $tag) {
	/**
	 * Let Custom Scripts Add a Shortcode to Post.
	 *
	 * This filter allows to customize the way that a shortcode is added in a
	 * post / page. It allows to make the plugin compatible with page builders,
	 * like Elementor, that do not handle posts / pages the way Wordpress does.
	 *
	 * @since 4.12.2
	 *
	 * @param integer $postid The post ID where to add the shortcode.
	 * @param string $tag The shortcode tag to add in post.
	 */
	$result = apply_filters("_wfu_add_shortcode", null, $postid, $tag);
	if ( $result == null ) {
		$post = get_post($postid);
		$new_content = '['.$tag.']'.$post->post_content;
		$new_post = array( 'ID' => $postid, 'post_content' => $new_content );
		$result = ( wp_update_post( wfu_slash($new_post) ) === 0 ? false : true );
	}
	return $result;
}

/**
 * Replace Shortcode Inside Post.
 *
 * This function replaces a shortcode inside post's contents.
 * 
 * @since 2.6.0
 *
 * @param array $data {
 *     Contains information about the shortcode.
 *
 *     $type integer $post_id The ID of the post that contains the shortcode.
 *     $type string $post_hash A hash that represents the current post contents.
 *     $type string $shortcode The shortcode string to be replaced.
 *     $type integer $position The position of the shortcode inside post's
 *           contents.
 * }
 * $param string $new_shortcode The new shortcode.
 *
 * @return bool True if the shortcode was replaced successfully, false
 *         otherwise.
 */
function wfu_replace_shortcode($data, $new_shortcode) {
	/**
	 * Let Custom Scripts Modify Shortcode Replacement.
	 *
	 * This filter allows to customize the way that a shortcode is replaced. It
	 * allows to make the plugin compatible with page builders, like Elementor,
	 * that do not handle posts / pages the way Wordpress does.
	 *
	 * @since 4.12.2
	 *
	 * @param bool|null $result The result of shortcode replacement. It must be
	 *        true if the replacement succeeded, false if it failed or null if
	 *        no replacement operation occurred.
	 * @param array $data Contains information about the shortcode.
	 * $param string $new_shortcode The new shortcode.
	 */
	$result = apply_filters("_wfu_replace_shortcode", null, $data, $new_shortcode);
	if ( $result == null ) {
		$post = get_post($data['post_id']);
		$new_content = substr($post->post_content, 0, $data['position']).$new_shortcode.substr($post->post_content, (int)$data['position'] + strlen($data['shortcode']));
		$new_post = array( 'ID' => $data['post_id'], 'post_content' => $new_content );
		$result = ( wp_update_post( wfu_slash($new_post) ) === 0 ? false : true );
	}
	return $result;
}

/**
 * Generate Page for Confirmation of Deletion of Shortcode.
 *
 * This function generates the HTML code of the page to ask from the user to
 * confirm deletion of the selected shortcode.
 *
 * @since 2.7.0
 *
 * $param string $data_enc Code that represents the shortcode data stored in
 *        safe.
 *
 * @return string The HTML code of the deletion confirmation page.
 */
function wfu_delete_shortcode_prompt($data_enc) {
	$siteurl = site_url();
	$data = wfu_decode_array_from_string(wfu_get_shortcode_data_from_safe($data_enc));
	$postid = $data['post_id'];
	$echo_str = "\n".'<div class="wrap">';
	$echo_str .= "\n\t".'<h2>Wordpress File Upload Control Panel</h2>';
	$echo_str .= "\n\t".'<div style="margin-top:20px;">';
	$echo_str .= "\n\t\t".'<a href="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload&amp;action=manage_mainmenu" class="button" title="go back">Go to Main Menu</a>';
	$echo_str .= "\n\t".'</div>';
	$echo_str .= "\n\t".'<h2 style="margin-bottom: 10px; margin-top: 20px;">Delete Shortcode</h2>';
	$echo_str .= "\n\t".'<form enctype="multipart/form-data" name="deletefile" id="deleteshortcode" method="post" action="'.$siteurl.'/wp-admin/options-general.php?page=wordpress_file_upload" class="validate">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="action" value="deleteshortcode">';
	$echo_str .= "\n\t\t".'<input type="hidden" name="data" value="'.$data_enc.'">';
	$echo_str .= "\n\t\t".'<label>Are you sure that you want to delete shortcode for <strong>'.get_post_type($postid).' "'.get_the_title($postid).'" ('.$postid.') Position '.$data['position'].'</strong> ?</label><br/>';
	$echo_str .= "\n\t\t".'<p class="submit">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Delete">';
	$echo_str .= "\n\t\t\t".'<input type="submit" class="button-primary" name="submit" value="Cancel">';
	$echo_str .= "\n\t\t".'</p>';
	$echo_str .= "\n\t".'</form>';
	$echo_str .= "\n".'</div>';
	return $echo_str;
}

/**
 * Deletion Shortcode.
 *
 * This function deletes a shortcode from page contents.
 *
 * @since 2.7.0
 *
 * $param array $data Code that represents the shortcode data stored in
 *        safe. See {@see wfu_replace_shortcode()} for a list of supported
 *        arguments.
 *
 * @return bool True if deletion succeeded, false otherwise.
 */
function wfu_delete_shortcode($data) {
	//check if user is allowed to perform this action
	if ( !current_user_can( 'manage_options' ) ) return false;

	$res = true;
	if ( isset($_POST['submit']) ) {
		if ( $_POST['submit'] == "Delete" ) {
			$res = wfu_replace_shortcode($data, '');
		}
	}
	return $res;
}

/**
 * Add Custom Properties to Media Editor.
 *
 * When "Show Custom Fields in Media Library" option in plugin's Settings is
 * true then Media Library attachments created by uploaded files will contain
 * custom fields corresponding to the uploaded files' userdata (if any). This
 * function shows these custom fields when editing the Media Library attachment.
 *
 * @since 3.7.2
 *
 * @redeclarable
 */
function wfu_media_editor_properties() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	if ( $plugin_options["mediacustom"] != "1" ) return;
	
	$post = get_post();
	$meta = wp_get_attachment_metadata( $post->ID );
	
	$echo_str = "";
	if ( isset($meta["WFU User Data"]) && is_array($meta["WFU User Data"]) ) {
		foreach ( $meta["WFU User Data"] as $label => $value )
			$echo_str .= '<div class="misc-pub-section misc-pub-userdata">'.$label.': <strong>'.$value.'</strong></div>';
	}
	echo $echo_str;
}

?>