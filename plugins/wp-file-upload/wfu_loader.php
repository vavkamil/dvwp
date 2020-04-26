<?php

/**
 * Initialize plugin
 *
 * This file initializes the plugin; defines constants, loads plugin's files,
 * defines shortcodes and text domain, registers filters and actions and
 * processes website requests.
 *
 * @link /wfu_loader.php
 *
 * @package WordPress File Upload Plugin
 * @subpackage Core Components
 * @since 4.9.1
 */
 
if ( !defined("WPFILEUPLOAD_PLUGINFILE") ) return;

//set global db variables
//wfu_tb_log_version v2.0 changes:
//  sessionid field added
//wfu_tb_log_version v3.0 changes:
//  uploadtime field added
//  blogid field added
//wfu_tb_log_version v4.0 changes:
//  filedata field added
$wfu_tb_log_version = "4.0";
$wfu_tb_userdata_version = "1.0";
$wfu_tb_dbxqueue_version = "1.0";

DEFINE("WPFILEUPLOAD_DIR", plugin_dir_url( WPFILEUPLOAD_PLUGINFILE ));
DEFINE("ABSWPFILEUPLOAD_DIR", plugin_dir_path( WPFILEUPLOAD_PLUGINFILE ));
DEFINE("WPFILEUPLOAD_COOKIE", "wp_wpfileupload_".COOKIEHASH);
add_shortcode("wordpress_file_upload", "wordpress_file_upload_handler");
//activation-deactivation hooks
register_activation_hook(WPFILEUPLOAD_PLUGINFILE,'wordpress_file_upload_install');
register_deactivation_hook(WPFILEUPLOAD_PLUGINFILE,'wordpress_file_upload_uninstall');
add_action('plugins_loaded', 'wordpress_file_upload_initialize');
add_action('plugins_loaded', 'wordpress_file_upload_update_db_check');
//widget
add_action( 'widgets_init', 'register_wfu_widget' );
//admin hooks
add_action('admin_init', 'wordpress_file_upload_admin_init');
add_action('admin_menu', 'wordpress_file_upload_add_admin_pages');
//load styles and scripts for front pages
if ( !is_admin() ) {
	add_action( 'wp_enqueue_scripts', 'wfu_enqueue_frontpage_scripts' );
}
//add admin bar menu item of new uploaded files
add_action( 'wp_before_admin_bar_render', 'wfu_admin_toolbar_new_uploads', 999 );
//general ajax actions
add_action('wp_ajax_wfu_ajax_action', 'wfu_ajax_action_callback');
add_action('wp_ajax_nopriv_wfu_ajax_action', 'wfu_ajax_action_callback');
add_action('wp_ajax_wfu_ajax_action_ask_server', 'wfu_ajax_action_ask_server');
add_action('wp_ajax_nopriv_wfu_ajax_action_ask_server', 'wfu_ajax_action_ask_server');
add_action('wp_ajax_wfu_ajax_action_cancel_upload', 'wfu_ajax_action_cancel_upload');
add_action('wp_ajax_nopriv_wfu_ajax_action_cancel_upload', 'wfu_ajax_action_cancel_upload');
add_action('wp_ajax_wfu_ajax_action_send_email_notification', 'wfu_ajax_action_send_email_notification');
add_action('wp_ajax_nopriv_wfu_ajax_action_send_email_notification', 'wfu_ajax_action_send_email_notification');
add_action('wp_ajax_wfu_ajax_action_notify_wpfilebase', 'wfu_ajax_action_notify_wpfilebase');
add_action('wp_ajax_nopriv_wfu_ajax_action_notify_wpfilebase', 'wfu_ajax_action_notify_wpfilebase');
add_action('wp_ajax_wfu_ajax_action_save_shortcode', 'wfu_ajax_action_save_shortcode');
add_action('wp_ajax_wfu_ajax_action_check_page_contents', 'wfu_ajax_action_check_page_contents');
add_action('wp_ajax_wfu_ajax_action_read_subfolders', 'wfu_ajax_action_read_subfolders');
add_action('wp_ajax_wfu_ajax_action_download_file_invoker', 'wfu_ajax_action_download_file_invoker');
add_action('wp_ajax_nopriv_wfu_ajax_action_download_file_invoker', 'wfu_ajax_action_download_file_invoker');
add_action('wp_ajax_wfu_ajax_action_download_file_monitor', 'wfu_ajax_action_download_file_monitor');
add_action('wp_ajax_nopriv_wfu_ajax_action_download_file_monitor', 'wfu_ajax_action_download_file_monitor');
add_action('wp_ajax_wfu_ajax_action_edit_shortcode', 'wfu_ajax_action_edit_shortcode');
add_action('wp_ajax_wfu_ajax_action_gutedit_shortcode', 'wfu_ajax_action_gutedit_shortcode');
add_action('wp_ajax_wfu_ajax_action_get_historylog_page', 'wfu_ajax_action_get_historylog_page');
add_action('wp_ajax_wfu_ajax_action_get_uploadedfiles_page', 'wfu_ajax_action_get_uploadedfiles_page');
add_action('wp_ajax_wfu_ajax_action_get_adminbrowser_page', 'wfu_ajax_action_get_adminbrowser_page');
add_action('wp_ajax_wfu_ajax_action_include_file', 'wfu_ajax_action_include_file');
add_action('wp_ajax_wfu_ajax_action_update_envar', 'wfu_ajax_action_update_envar');
add_action('wp_ajax_wfu_ajax_action_transfer_command', 'wfu_ajax_action_transfer_command');
add_action('wp_ajax_wfu_ajax_action_pdusers_get_users', 'wfu_ajax_action_pdusers_get_users');
//personal data related actions
add_action( 'show_user_profile', 'wfu_show_consent_profile_fields' );
add_action( 'edit_user_profile', 'wfu_show_consent_profile_fields' );
add_action( 'personal_options_update', 'wfu_update_consent_profile_fields' );
add_action( 'edit_user_profile_update', 'wfu_update_consent_profile_fields' );
//Media editor custom properties
if ( is_admin() ) add_action( 'attachment_submitbox_misc_actions', 'wfu_media_editor_properties', 11 );
//register admin filter to check consent status before upload
add_filter("wfu_before_upload", "wfu_consent_ask_server_handler", 10, 2);
//register internal filter that is executed before upload for classic uploader
add_filter("_wfu_before_upload", "wfu_classic_before_upload_handler", 10, 2);
wfu_include_lib();

/**
 * Initialize plugin.
 *
 * Runs after plugins are loaded in order to correctly load the plugin's text
 * domain and then load all translatable strings. Then it loads the User State
 * Handler (session or db). Then it executes all active plugin hooks.
 *
 * @since 4.7.0
 *
 * @redeclarable
 */
function wordpress_file_upload_initialize() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	load_plugin_textdomain('wp-file-upload', false, dirname(plugin_basename (WPFILEUPLOAD_PLUGINFILE)).'/languages');
	wfu_initialize_i18n_strings();
	//store the User State handler in a global variable for easy access by the
	//plugin's routines
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$GLOBALS["wfu_user_state_handler"] = $plugin_options['userstatehandler'];
	wfu_initialize_user_state();

}

/**
 * Register Upload Form Widget.
 *
 * Runs on widget initialization to register the upload form widget of the
 * plugin.
 *
 * @since 3.4.0
 */
function register_wfu_widget() {
	/**
	 * Allow Custom Scripts to Register WFU Widget.
	 *
	 * This filter allows custom scripts to register the WFU widget in their own
	 * was.
	 *
	 * @since 4.12.2
	 *
	 * @param bool $processed True if the filter has completed registration or
	 *        false otherwise.
	*/
	$processed = apply_filters("_register_wfu_widget", false);
	if ( !$processed ) register_widget( 'WFU_Widget' );
}

/**
 * Enqueue frontpage styles and scripts.
 *
 * It enqueues all necessary frontpage styles and scripts of the plugin.
 *
 * @since 2.4.6
 *
 * @redeclarable
 */
function wfu_enqueue_frontpage_scripts() {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$relaxcss = false;
	if ( isset($plugin_options['relaxcss']) ) $relaxcss = ( $plugin_options['relaxcss'] == '1' );
	//apply wfu_before_frontpage_scripts to get additional settings 
	$changable_data = array();
	/**
	 * Execute Custom Actions Before Loading Frontpage Scripts.
	 *
	 * This filter allows to execute custom actions before frontpage scripts are
	 * loaded. Loading of plugin's scripts can be completely customised.
	 *
	 * @since 3.5.0
	 *
	 * @param array $changable_data {
	 *     Controls loading of frontpage scripts.
	 *
	 *     @type mixed $return_value Optional. If it is set then no frontpage
	 *           scripts will be loaded.
	 *     @type string $correct_NextGenGallery_incompatibility Optional. If it
	 *           is set to "true" then JQuery UI styles will not be loaded in
	 *           order to avoid incompatibility with NextGEN Gallery plugin.
	 *     @type string $correct_JQueryUI_incompatibility Optional. If it is set
	 *           to "true" then JQuery UI styles will not be loaded (same as
	 *           previous parameter).
	 *     @type string $exclude_timepicker Optional. If it is set to "true"
	 *           then jQuery timepicker styles and scripts will not be loaded.
	 * }
	*/
	$ret_data = apply_filters('wfu_before_frontpage_scripts', $changable_data);
	//if $ret_data contains 'return_value' key then no scripts will be enqueued
	if ( isset($ret_data['return_value']) ) return $ret_data['return_value'];

	if ( $relaxcss ) {
		wp_enqueue_style('wordpress-file-upload-style', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_style_relaxed.css');
		wp_enqueue_style('wordpress-file-upload-style-safe', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_style_safe_relaxed.css');
	}
	else {
		wp_enqueue_style('wordpress-file-upload-style', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_style.css');
		wp_enqueue_style('wordpress-file-upload-style-safe', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_style_safe.css');
	}
	wp_enqueue_style('wordpress-file-upload-adminbar-style', WPFILEUPLOAD_DIR.'css/wordpress_file_upload_adminbarstyle.css');
	//do not load JQuery UI css if $ret_data denotes incompatibility issues
	if ( ( !isset($ret_data["correct_NextGenGallery_incompatibility"]) || $ret_data["correct_NextGenGallery_incompatibility"] != "true" ) &&
		( !isset($ret_data["correct_JQueryUI_incompatibility"]) || $ret_data["correct_JQueryUI_incompatibility"] != "true" ) )
		wp_enqueue_style('jquery-ui-css', WPFILEUPLOAD_DIR.'vendor/jquery/jquery-ui.min.css');
	//do not load timepicker css if $ret_data exclude_timepicker flag is true
	if ( !isset($ret_data["exclude_timepicker"]) || $ret_data["exclude_timepicker"] != "true" )
		wp_enqueue_style('jquery-ui-timepicker-addon-css', WPFILEUPLOAD_DIR.'vendor/jquery/jquery-ui-timepicker-addon.min.css');
	wp_enqueue_script('json2');
	wp_enqueue_script('wordpress_file_upload_script', WPFILEUPLOAD_DIR.'js/wordpress_file_upload_functions.js');
	//do not load timepicker js if $ret_data exclude_timepicker flag is true
	if ( !isset($ret_data["exclude_timepicker"]) || $ret_data["exclude_timepicker"] != "true" ) {
		wp_enqueue_script('jquery-ui-slider');
		wp_enqueue_script('jquery-ui-timepicker-addon-js', WPFILEUPLOAD_DIR.'vendor/jquery/jquery-ui-timepicker-addon.min.js', array("jquery-ui-datepicker"));
	}
}

/**
 * Load plugin libraries.
 *
 * It loads all plugin libraries located in /lib folder of the plugin.
 *
 * @since 2.1.2
 */
function wfu_include_lib() {
	$dir = plugin_dir_path( WPFILEUPLOAD_PLUGINFILE )."lib/";
	if ( $handle = opendir($dir) ) {
		$blacklist = array('.', '..');
		while ( false !== ($file = readdir($handle)) )
			if ( !in_array($file, $blacklist) && substr($file, 0, 1) != "_" )
				include_once $dir.$file;
		closedir($handle);
	}
	if ( $handle = opendir(plugin_dir_path( WPFILEUPLOAD_PLUGINFILE )) ) {
		closedir($handle);
	}
}


/* exit if we are in admin pages (in case of ajax call) */
if ( is_admin() ) return;

/**
 * Render uploader form shortcode.
 *
 * It receives the attributes of an uploader form shortcode and returns the HTML
 * code of the generated upload form.
 *
 * @since 2.1.2
 *
 * @param array $incomingfrompost An associative array of shortcode attributes
 *        (array keys) and their values (array values).
 * @return string The HTML code of the generated upload form
 */
function wordpress_file_upload_handler($incomingfrompost) {
	//replace old attribute definitions with new ones
	$incomingfrompost = wfu_old_to_new_attributes($incomingfrompost);
	//preprocess attributes
	$incomingfrompost = wfu_preprocess_attributes($incomingfrompost);
	//process incoming attributes assigning defaults if required
	$defs_indexed = wfu_shortcode_attribute_definitions_adjusted($incomingfrompost);
	$incomingfrompost = shortcode_atts($defs_indexed, $incomingfrompost);
	//run function that actually does the work of the plugin
	$wordpress_file_upload_output = wordpress_file_upload_function($incomingfrompost);
	//send back text to replace shortcode in post
	return $wordpress_file_upload_output;
}

/**
 * Render front-end file viewer shortcode.
 *
 * It receives the attributes of a front-end file viewer shortcode and returns
 * the HTML code of the generated file viewer.
 *
 * @since 3.1.0
 *
 * @param array $incomingfrompost An associative array of shortcode attributes
 *        (array keys) and their values (array values).
 * @return string The HTML code of the generated file viewer
 */
function wordpress_file_upload_browser_handler($incomingfrompost) {
	//process incoming attributes assigning defaults if required
	$defs = wfu_browser_attribute_definitions();
	$defs_indexed = array();
	foreach ( $defs as $def ) $defs_indexed[$def["attribute"]] = $def["value"];
	$incomingfrompost = shortcode_atts($defs_indexed, $incomingfrompost);
	//run function that actually does the work of the plugin
	$wordpress_file_upload_browser_output = wordpress_file_upload_browser_function($incomingfrompost);
	//send back text to replace shortcode in post
	return $wordpress_file_upload_browser_output;
}

/**
 * Generate the HTML code of uploader form.
 *
 * It receives the processed attributes of an uploader form shortcode and
 * returns the HTML code of the generated upload form.
 *
 * @since 2.1.2
 *
 * @redeclarable
 *
 * @global object $post The current post
 * @global int $blog_id The ID of the current blog
 *
 * @param array $incomingfromhandler An associative array of shortcode
 *        attributes (array keys) and their values (array values).
 *
 * @return string The HTML code of the generated upload form
 */
function wordpress_file_upload_function($incomingfromhandler) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	global $post;
	global $blog_id;

	$plugin_options = wfu_decode_plugin_options(get_option( "wordpress_file_upload_options" ));
	$shortcode_tag = 'wordpress_file_upload';
	$params = wfu_plugin_parse_array($incomingfromhandler);
	//sanitize params
	$params = wfu_sanitize_shortcode_array($params, $shortcode_tag);

	$is_admin = current_user_can( 'manage_options' );
	//check if a non-admin user can edit the shortcode 
	$can_open_composer = ( WFU_VAR("WFU_SHORTCODECOMPOSER_NOADMIN") == "true" && 
		$params["widgetid"] == "" && 
		$post != null && 
		isset($post->post_type) && 
		( $post->post_type == 'post' || $post->post_type == 'page' ) &&
		current_user_can( 'edit_'.$post->post_type, $post->ID ) );
	//take into account if the user is admin
	$can_open_composer = ( $is_admin || $can_open_composer );
	/**
	 * Filter To Customise Shortcode Composer Permission.
	 *
	 * This filter is used to customise the permissions of the user to open the
	 * shortcode composer.
	 *
	 * @since 4.12.2
	 *
	 * @param bool $can_open_composer Whether the composer can be opened or not.
	 * @param array $params An associative array with shortcode attributes.
	 */
	$can_open_composer = apply_filters("_wfu_can_open_composer", $can_open_composer, $params);

	$sid = $params["uploadid"];
	// store current page and blog id in params array
	$params["pageid"] = $post->ID;
	$params["blogid"] = $blog_id;
	
	$token_sid = 'wfu_token_'.$sid;
	if ( !WFU_USVAR_exists($token_sid) || WFU_USVAR($token_sid) == "" )
		WFU_USVAR_store($token_sid, uniqid(mt_rand(), TRUE));
	//store the server environment (32 or 64bit) for use when checking file size limits
	$params["php_env"] = wfu_get_server_environment();

	$user = wp_get_current_user();
	$widths = wfu_decode_dimensions($params["widths"]);
	$heights = wfu_decode_dimensions($params["heights"]);
	//additional parameters to pass to visualization routines
	$additional_params = array( );
	$additional_params['widths'] = $widths;
	$additional_params['heights'] = $heights;
	$additional_params["require_consent"] = ( $plugin_options["personaldata"] == "1" && ( $params["notrememberconsent"] == "true" || wfu_check_user_consent($user) == "" ) && $params["askconsent"] == "true" );

	$uploadedfile = 'uploadedfile_'.$sid;
	$hiddeninput = 'hiddeninput_'.$sid;
	$adminerrorcodes = 'adminerrorcodes_'.$sid;
	
	//set necessary parameters to be passed to client initialization function
	$init_params = array();
	$init_params["shortcode_id"] = $sid;
	$init_params["shortcode_tag"] = $shortcode_tag;
	$init_params["container_id"] = $shortcode_tag.'_block_'.$sid;
	$init_params["session"] = WFU_USVAR($token_sid);
	$init_params["testmode"] = ( $params["testmode"] == "true" );
	$init_params["widgetid"] = $params["widgetid"];
	$init_params["require_consent"] = $additional_params["require_consent"];
	//if the following criteria is met, then maybe the server needs to be asked
	//if upload needs to be rejected or not due to consent denial
	$init_params["consent_maybe_ask_server"] = ( $plugin_options["personaldata"] == "1" && $params["askconsent"] == "true" && $params["consentrejectupload"] == "true" );
	$init_params["consent_rejection_message"] = $params["consentrejectmessage"];
	//add allow no file flag
	$init_params["allownofile"] = ( $params["allownofile"] == "true" );
	$init_params["not_store_files"] = ( $params["personaldatatypes"] == "userdata and files" );
	//add params related to visual editor button
	if ( $can_open_composer ) {
		$init_params["post_id"] = $post->ID;
		/**
		 * Let Custom Scripts Modify the Post Content.
		 *
		 * This filter allows to customize the way post content is read. It allows
		 * to make the plugin compatible with page builders, like Elementor, that do
		 * not handle posts / pages the way Wordpress does.
		 *
		 * @since 4.12.2
		 *
		 * @param string $content The post content.
		 * @param object $post The post to check for shortcodes.
		 */
		$content = apply_filters("_wfu_get_post_content", $post->post_content, $post);
		$init_params["post_hash"] = hash('md5', $content);
	}

	//check if user is allowed to view plugin, otherwise do not generate it
	$uploadroles = explode(",", $params["uploadrole"]);
	foreach ( $uploadroles as &$uploadrole ) {
		$uploadrole = trim($uploadrole);
	}
	$plugin_upload_user_role = wfu_get_user_role($user, $uploadroles);		
	/**
	 * Filter When the Upload Form Must Not be Shown.
	 *
	 * This filter is executed when the upload form must be shown on the page.
	 * It allows to return custom HTML output instead of empty content.
	 *
	 * @since 4.1.0
	 *
	 * @param string $ret The HTML output to return to the page. Default "".
	*/
	if ( $plugin_upload_user_role == 'nomatch' ) return apply_filters("_wfu_file_upload_hide_output", "");

	//activate debug mode only for admins
	if ( $plugin_upload_user_role != 'administrator' ) $params["debugmode"] = "false";

	$params["adminmessages"] = ( $params["adminmessages"] == "true" && $plugin_upload_user_role == 'administrator' );
	// define variable to hold any additional admin errors coming before processing of files (e.g. due to redirection)
	$params["adminerrors"] = "";

	/* Define dynamic upload path from variables */
	$search = array ('/%userid%/', '/%username%/', '/%blogid%/', '/%pageid%/', '/%pagetitle%/');	
	if ( is_user_logged_in() ) $username = $user->user_login;
	else $username = "guests";
	$replace = array ($user->ID, $username, $blog_id, $post->ID, get_the_title($post->ID));
	$params["uploadpath"] = preg_replace($search, $replace, $params["uploadpath"]);

	/* Determine if userdata fields have been defined */
	$userdata_fields = array(); 
	$userdata_occurrencies = substr_count($params["placements"], "userdata");
	if ( $userdata_occurrencies == 0 ) $userdata_occurrencies = 1;
	if ( $params["userdata"] == "true" ) {
		for ( $i = 1; $i <= $userdata_occurrencies; $i++ ) {
			$userdata_fields2 = wfu_parse_userdata_attribute($params["userdatalabel".( $i > 1 ? $i : "" )]);
			foreach ( $userdata_fields2 as $key => $item ) $userdata_fields2[$key]["occurrence"] = $i;
			$userdata_fields = array_merge($userdata_fields, $userdata_fields2);
		}
	}
	$params["userdata_fields"] = $userdata_fields;
	
	/* If medialink or postlink is activated, then subfolders are deactivated */
	if ( $params["medialink"] == "true" || $params["postlink"] == "true" ) $params["askforsubfolders"] = "false";

	/* Generate the array of subfolder paths */
	$params['subfoldersarray'] = wfu_get_subfolders_paths($params);
	

	/* in case that webcam is activated, then some elements related to file
	   selection need to be removed */
	if ( strpos($params["placements"], "webcam") !== false && $params["webcam"] == "true" ) {
		$params["placements"] = wfu_placements_remove_item($params["placements"], "filename");
		$params["placements"] = wfu_placements_remove_item($params["placements"], "selectbutton");
		$params["singlebutton"] = "false";
		$params["uploadbutton"] = $params["uploadmediabutton"];
	}

//____________________________________________________________________________________________________________________________________________________________________________________

	if ( $params['forceclassic'] != "true" ) {	
//**************section to put additional options inside params array**************
		$params['subdir_selection_index'] = "-1";
//**************end of section of additional options inside params array**************


//	below this line no other changes to params array are allowed


//**************section to save params as Wordpress options**************
//		every params array is indexed (uniquely identified) by three fields:
//			- the page that contains the shortcode
//			- the id of the shortcode instance (because there may be more than one instances of the shortcode inside a page)
//			- the user that views the plugin (because some items of the params array are affected by the user name)
//		the wordpress option "wfu_params_index" holds an array of combinations of these three fields, together with a randomly generated string that corresponds to these fields.
//		the wordpress option "wfu_params_xxx", where xxx is the randomly generated string, holds the params array (encoded to string) that corresponds to this string.
//		the structure of the "wfu_params_index" option is as follows: "a1||b1||c1||d1&&a2||b2||c2||d2&&...", where
//			- a is the randomly generated string (16 characters)
//			- b is the page id
//			- c is the shortcode id
//			- d is the user name
		$params_index = wfu_generate_current_params_index($sid, $user->user_login);
		$params_str = wfu_encode_array_to_string($params);
		update_option('wfu_params_'.$params_index, $params_str);
		$init_params["params_index"] = $params_index;
		$init_params["debugmode"] = ( $params["debugmode"] == "true" );
		$init_params["is_admin"] = ( $plugin_upload_user_role == "administrator" );
		$init_params["has_filters"] = has_filter("wfu_before_upload");
		$init_params["error_header"] = $params["errormessage"];
		$init_params["fail_colors"] = $params["failmessagecolors"];
		$init_params["success_header"] = $params["successmessage"];
		$init_params["success_colors"] = $params["successmessagecolors"];
	}


	/* set the template that will be used, default is empty (the original) */
	$params["uploadertemplate"] = "";
//	$params["uploadertemplate"] = "Custom1";
	/**
	 * Filter To Define Custom Uploader Template.
	 *
	 * This filter is used to define a custom uploader template that will be
	 * used to generate the upload form.
	 *
	 * @since 4.0.0
	 *
	 * @param string $ret The uploader template to use. Default "".
	 * @param array $params An associative array with the shortcode attributes.
	 */
	$params["uploadertemplate"] = apply_filters("_wfu_uploader_template", $params["uploadertemplate"], $params);
	$uploadertemplate = wfu_get_uploader_template($params["uploadertemplate"]);
	/* Compose the html code for the plugin */
	$wordpress_file_upload_output = "";
	$wordpress_file_upload_output .= wfu_init_run_js_script();
	$plugin_style = "";
	if ( $widths["plugin"] != "" ) $plugin_style .= 'width: '.$widths["plugin"].'; ';
	if ( $heights["plugin"] != "" ) $plugin_style .= 'height: '.$heights["plugin"].'; ';
	if ( $plugin_style != "" ) $plugin_style = ' style="'.$plugin_style.'"';
	$wordpress_file_upload_output .= "\n".'<div id="'.$init_params["container_id"].'" class="file_div_clean'.( $params["fitmode"] == "responsive" ? '_responsive_container' : '' ).' wfu_container"'.$plugin_style.'>';
	$wordpress_file_upload_output .= "\n".'<!-- Using template '.call_user_func(array($uploadertemplate, 'get_name')).' -->';
	//read indexed component definitions
	$component_output = "";
	$css = "";
	$js = "";
	/* Add generic uploadform code to output from template */
	$wordpress_file_upload_output .= wfu_template_to_HTML("base", $params, array(), 0);
	/* Continue with uploadform elements */
	$components = wfu_component_definitions();
	$components_indexed = array();
	foreach ( $components as $component ) {
		$components_indexed[$component['id']] = $component;
		$components_indexed[$component['id']]['occurrencies'] = 0;
	}
	$itemplaces = explode("/", $params["placements"]);
	foreach ( $itemplaces as $section ) {
		$items_in_section = explode("+", trim($section));
		$section_array = array( $params );
		foreach ( $items_in_section as $item_in_section ) {
			$item_in_section = strtolower(trim($item_in_section));
			if ( isset($components_indexed[$item_in_section]) && ( $components_indexed[$item_in_section]['multiplacements'] || $components_indexed[$item_in_section]['occurrencies'] == 0 ) ) {
				$components_indexed[$item_in_section]['occurrencies'] ++;
				$occurrence_index = ( $components_indexed[$item_in_section]['multiplacements'] ? $components_indexed[$item_in_section]['occurrencies'] : 0 );
				if ( $item_in_section == "title" ) array_push($section_array, wfu_prepare_title_block($params, $additional_params, $occurrence_index));
				elseif ( $item_in_section == "filename" ) array_push($section_array, wfu_prepare_textbox_block($params, $additional_params, $occurrence_index));
				elseif ( $item_in_section == "selectbutton" ) array_push($section_array, wfu_prepare_uploadform_block($params, $additional_params, $occurrence_index));
				elseif ( $item_in_section == "uploadbutton" && $params["singlebutton"] != "true" ) array_push($section_array, wfu_prepare_submit_block($params, $additional_params, $occurrence_index));
				elseif ( $item_in_section == "subfolders" ) array_push($section_array, wfu_prepare_subfolders_block($params, $additional_params, $occurrence_index));
				elseif ( $item_in_section == "progressbar" ) array_push($section_array, wfu_prepare_progressbar_block($params, $additional_params, $occurrence_index));
				elseif ( $item_in_section == "message" ) array_push($section_array, wfu_prepare_message_block($params, $additional_params, $occurrence_index));
				elseif ( $item_in_section == "userdata" && $params["userdata"] == "true" ) array_push($section_array, wfu_prepare_userdata_block($params, $additional_params, $occurrence_index));
				elseif ( $item_in_section == "consent" && $additional_params["require_consent"] ) array_push($section_array, wfu_prepare_consent_block($params, $additional_params, $occurrence_index));
				elseif ( $item_in_section == "webcam" && $params["webcam"] == "true" ) array_push($section_array, wfu_prepare_webcam_block($params, $additional_params, $occurrence_index));
			}
		}
		wfu_extract_css_js_from_components($section_array, $css, $js);
		$component_output .= call_user_func_array("wfu_add_div", $section_array);
	}
	/* Append mandatory blocks, if have not been included in placements attribute */
	if ( $params["userdata"] == "true" && strpos($params["placements"], "userdata") === false ) {
		$section_array = array( $params );
		array_push($section_array, wfu_prepare_userdata_block($params, $additional_params, 0));
		wfu_extract_css_js_from_components($section_array, $css, $js);
		$component_output .= call_user_func_array("wfu_add_div", $section_array);
	}
	if ( $additional_params["require_consent"] && strpos($params["placements"], "consent") === false ) {
		$section_array = array( $params );
		array_push($section_array, wfu_prepare_consent_block($params, $additional_params, 0));
		wfu_extract_css_js_from_components($section_array, $css, $js);
		$component_output .= call_user_func_array("wfu_add_div", $section_array);
	}
	if ( strpos($params["placements"], "selectbutton") === false ) {
		$section_array = array( $params );
		array_push($section_array, wfu_prepare_uploadform_block($params, $additional_params, 0));
		wfu_extract_css_js_from_components($section_array, $css, $js);
		$component_output .= call_user_func_array("wfu_add_div", $section_array);
	}
	if ( strpos($params["placements"], "uploadbutton") === false ) $params["singlebutton"] = "true";

	//set some more parameters for the initialization script
	$init_params["is_formupload"] = ( $params['forceclassic'] == "true" );
	$init_params["singlebutton"] = ( $params["singlebutton"] == "true" );
	$init_params["resetmode"] = $params["resetmode"];

	//output css styling rules
	if ( $css != "" ) {
		//relax css rules if this option is enabled
		if ( $plugin_options['relaxcss'] == '1' ) $css = preg_replace('#.*?/\*relax\*/\s*#', '', $css);
		$wordpress_file_upload_output .= wfu_css_to_HTML($css);
	}
	//output javascript code
	if ( $js != "" ) {
		//add initialization of the object of the upload form
		$wfu_js = 'var WFU_JS_'.$sid.' = function() {';
		$wfu_js .= "\n".'GlobalData.WFU['.$sid.'] = '.wfu_PHP_array_to_JS_object($init_params).'; GlobalData.WFU.n.push('.$sid.');';
		$wfu_js .= "\n".$js;
		$wfu_js .= "\n".'}';
		$wfu_js .= "\n".'wfu_run_js("window", "WFU_JS_'.$sid.'");';
		$wordpress_file_upload_output .= "\n".wfu_js_to_HTML($wfu_js);
	}
	//add visual editor overlay if the current user is administrator
	if ( $can_open_composer ) {
		$wordpress_file_upload_output .= wfu_add_visual_editor_button($shortcode_tag, $params);
	}
	//add components' html output
	$wordpress_file_upload_output .= $component_output;

	/* Pass constants to javascript and run plugin post-load actions */
	$consts = wfu_set_javascript_constants();
	$handler = 'function() { wfu_Initialize_Consts("'.$consts.'"); wfu_Load_Code_Connectors('.$sid.'); wfu_plugin_load_action('.$sid.'); }';
	$wfu_js = 'if (typeof wfu_addLoadHandler == "undefined") function wfu_addLoadHandler(handler) { if(window.addEventListener) { window.addEventListener("load", handler, false); } else if(window.attachEvent) { window.attachEvent("onload", handler); } else { window["onload"] = handler; } }';
	$wfu_js .= "\n".'wfu_addLoadHandler('.$handler.');';
	$wordpress_file_upload_output .= "\n".wfu_js_to_HTML($wfu_js);
	$wordpress_file_upload_output .= '</div>';
//	$wordpress_file_upload_output .= '<div>';
//	$wordpress_file_upload_output .= wfu_test_admin();
//	$wordpress_file_upload_output .= '</div>';

//	The plugin uses sessions in order to detect if the page was loaded due to file upload or
//	because the user pressed the Refresh button (or F5) of the page.
//	In the second case we do not want to perform any file upload, so we abort the rest of the script.
	$check_refresh_sid = 'wfu_check_refresh_'.$sid;
	if ( !WFU_USVAR_exists($check_refresh_sid) || WFU_USVAR($check_refresh_sid) != "form button pressed" ) {
		WFU_USVAR_store($check_refresh_sid, 'do not process');
		$wordpress_file_upload_output .= wfu_post_plugin_actions($params);
		/**
		 * Filter To Customise Uploader Output.
		 *
		 * This filter is used to customise the HTML code generated by the
		 * plugin for showing the upload form.
		 *
		 * @since 3.9.6
		 *
		 * @param string $wordpress_file_upload_output The HTML output.
		 * @param array $params An associative array with shortcode attributes.
		 */
		$wordpress_file_upload_output = apply_filters("_wfu_file_upload_output", $wordpress_file_upload_output, $params);
		return $wordpress_file_upload_output."\n";
	}
	WFU_USVAR_store($check_refresh_sid, 'do not process');
	$params["upload_start_time"] = WFU_USVAR('wfu_start_time_'.$sid);

//	The plugin uses two ways to upload the file:
//		- The first one uses classic functionality of an HTML form (highest compatibility with browsers but few capabilities).
//		- The second uses ajax (HTML5) functionality (medium compatibility with browsers but many capabilities, like no page refresh and progress bar).
//	The plugin loads using ajax functionality by default, however if it detects that ajax functionality is not supported, it will automatically switch to classic functionality. 
//	The next line checks to see if the form was submitted using ajax or classic functionality.
//	If the uploaded file variable stored in $_FILES ends with "_redirected", then it means that ajax functionality is not supported and the plugin must switch to classic functionality. 
	if ( isset($_FILES[$uploadedfile.'_redirected']) ) $params['forceclassic'] = "true";

	if ( $params['forceclassic'] != "true" ) {
		$wordpress_file_upload_output .= wfu_post_plugin_actions($params);
		/** This filter is documented above */
		$wordpress_file_upload_output = apply_filters("_wfu_file_upload_output", $wordpress_file_upload_output, $params);
		return $wordpress_file_upload_output."\n";
	}

//  The following code is executed in case of non-ajax uploads to process the files.
//  Consecutive checks are performed in order to verify and approve the upload of files
	$_REQUEST = stripslashes_deep($_REQUEST);
	$_POST = stripslashes_deep($_POST);
	$wfu_checkpass = true;
	
//  First we test that WP nonce passes the check
	$wfu_checkpass = ( $wfu_checkpass && isset($_REQUEST["wfu_uploader_nonce"]) && wp_verify_nonce( $_REQUEST["wfu_uploader_nonce"], "wfu-uploader-nonce" ) !== false );

	$unique_id = ( isset($_POST['uniqueuploadid_'.$sid]) ? sanitize_text_field($_POST['uniqueuploadid_'.$sid]) : "" );
//  Check that upload_id is valid
	$wfu_checkpass = ( $wfu_checkpass && strlen($unique_id) == 10 );

	//check if honeypot userdata fields have been added to the form and if they
	//contain any data; if wfu_check_remove_honeypot_fields returns true this
	//means that at least one honeypot field has beed filled with a value and
	//the upload must be aborted because it was not done by a human; files will
	//not be saved but a success result will be shown, pretending that they have
	//been saved
	$abort_with_success = ( $params["userdata"] == "true" && wfu_check_remove_honeypot_fields($params["userdata_fields"], 'hiddeninput_'.$sid.'_userdata_') );
	

	if ( $wfu_checkpass ) {
		//process any error messages due to redirection to non-ajax upload
		if ( isset( $_POST[$adminerrorcodes] ) ) {
			$code = $_POST[$adminerrorcodes];
			if ( $code == "" ) $params['adminerrors'] = "";
			elseif ( $code == "1" || $code == "2" || $code == "3" ) $params['adminerrors'] = constant('WFU_ERROR_REDIRECTION_ERRORCODE'.$code);
			else $params['adminerrors'] = WFU_ERROR_REDIRECTION_ERRORCODE0;
		}
	
		$params['subdir_selection_index'] = -1;
		if ( isset( $_POST[$hiddeninput] ) ) $params['subdir_selection_index'] = sanitize_text_field($_POST[$hiddeninput]);
		
		//in case that that the upload has been cancelled then proceed
		//accordingly to notify the user
		$uploadstatus_id = "wfu_uploadstatus_".$unique_id;
		if ( WFU_USVAR_exists($uploadstatus_id) && WFU_USVAR($uploadstatus_id) == 0 ) {
			$safe_output = "17;".WFU_VAR("WFU_DEFAULTMESSAGECOLORS").";0";
			$wfu_process_file_array_str = " ";
			$js_script_enc = "";
		}
		//in case that the upload was performed by a bot, then files are not
		//processed and not saved, however state 18 is returned pretending that
		//the upload was successful
		elseif ( $abort_with_success ) {
			$safe_output = "18;".WFU_VAR("WFU_DEFAULTMESSAGECOLORS").";0";
			$wfu_process_file_array_str = " ";
			$js_script_enc = "";
		}
		else {
			//update consent status of user
			$params["consent_result"] = wfu_check_user_consent($user);
			if ( $additional_params["require_consent"] ) {
				if ( !isset($_POST['consentresult_'.$sid]) ) die();
				$consent_result = ( $_POST['consentresult_'.$sid] == "yes" ? "yes" : ( $_POST['consentresult_'.$sid] == "no" ? "no" : "" ) );
				$params["consent_result"] = ( $_POST['consentresult_'.$sid] == "yes" ? "1" : ( $_POST['consentresult_'.$sid] == "no" ? "0" : "" ) );
				wfu_update_user_consent($user, $consent_result);
			}
			$wfu_process_file_array = wfu_process_files($params, 'no_ajax');
			$safe_output = $wfu_process_file_array["general"]['safe_output'];
			unset($wfu_process_file_array["general"]['safe_output']);
			//javascript code generated from individual wfu_after_upload_filters is not executed in non-ajax uploads
			unset($wfu_process_file_array["general"]['js_script']);
			$js_script_enc = "";
			//execute after upload filters
			$ret = wfu_execute_after_upload_filters($sid, $unique_id, $params);
			if ( $ret["js_script"] != "" ) $js_script_enc = wfu_plugin_encode_string($ret["js_script"]);
			$wfu_process_file_array_str = wfu_encode_array_to_string($wfu_process_file_array);
		}

		$ProcessUploadComplete_functiondef = 'function(){wfu_ProcessUploadComplete('.$sid.', 1, "'.$wfu_process_file_array_str.'", "no-ajax", "'.$safe_output.'", [false, null, false], "fileupload", "'.$js_script_enc.'");}';
		$wfu_js = 'wfu_addLoadHandler('.$ProcessUploadComplete_functiondef.');';
		$wordpress_file_upload_output .= "\n".wfu_js_to_HTML($wfu_js);
	}
	
	$wordpress_file_upload_output .= wfu_post_plugin_actions($params);
	/** This filter is documented above */
	$wordpress_file_upload_output = apply_filters("_wfu_file_upload_output", $wordpress_file_upload_output, $params);
	return $wordpress_file_upload_output."\n";
}

/**
 * Generate HTML code of Shortcode Visual Editor button.
 *
 * It generates the HTML code of the button that invokes the visual editor of
 * the shortcode (shortcode composer).
 *
 * @since 3.1.0
 *
 * @param string $shortcode_tag The tag of the shortcode for which the button
 *        will be generated.
 * @param array $params The shortcode attributes
 * @return string The HTML code of the visual editor button
 */
function wfu_add_visual_editor_button($shortcode_tag, $params) {
	return wfu_template_to_HTML("visualeditorbutton", $params, array( "shortcode_tag" => $shortcode_tag ), 0);
}

/**
 * Additional content after upload form.
 *
 * It generates additional HTML code to be added after the upload form.
 *
 * @since 2.4.1
 *
 * @redeclarable
 *
 * @return string The additional HTML code
 */
function wfu_post_plugin_actions($params) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$echo_str = '';

	return $echo_str;
}

/**
 * Get the list of subfolders of the upload directory.
 *
 * It calculates the subfolders of the upload directory of an upload form in
 * case that subfolders feature is activated in the shortcode and it is
 * configured to calculate the subfolders automatically.
 *
 * @since 3.3.0
 *
 * @redeclarable
 *
 * @param array $params The shortcode attributes
 * @return array The calculated subfolders
 */
function wfu_get_subfolders_paths($params) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	$subfolder_paths = array ( );
	if ( $params["askforsubfolders"] == "true" && $params["testmode"] != "true" ) {
		array_push($subfolder_paths, "");
		if ( substr($params["subfoldertree"], 0, 4) == "auto" ) {
			$upload_directory = wfu_upload_plugin_full_path($params);
			$dirtree = wfu_getTree($upload_directory);
			foreach ( $dirtree as &$dir ) $dir = '*'.$dir;
			$params["subfoldertree"] = implode(',', $dirtree);
		}
		$subfolders = wfu_parse_folderlist($params["subfoldertree"]);
		if ( count($subfolders['path']) == 0 ) array_push($subfolders['path'], "");
		foreach ( $subfolders['path'] as $subfolder ) array_push($subfolder_paths, $subfolder);
	}

	return $subfolder_paths;
}

/**
 * Convert old attribute names to new.
 *
 * Some shortcode attributes have changed name. This function makes sure that
 * shortcode attributes with old names are converted to new names so that they
 * can be processed correctly.
 *
 * @since 3.8.4
 *
 * @param array $shortcode_attrs The shortcode attributes
 * @return array The processed shortcode attributes
 */
function wfu_old_to_new_attributes($shortcode_attrs) {
	//old to new attribute definitions
	$old_to_new = array(
		"dublicatespolicy" => "duplicatespolicy"
	);
	//implement changes
	foreach ( $old_to_new as $old => $new ) {
		if ( isset($shortcode_attrs[$old]) ) {
			$shortcode_attrs[$new] = $shortcode_attrs[$old];
			unset($shortcode_attrs[$old]);
		}
	}
	return $shortcode_attrs;
}

/**
 * Preprocess Attributes Before Handler.
 *
 * Preprocess attributes before they enter the handler. For instance, ftpinfo
 * attribute is not parsed correctly and needs to be adjusted.
 *
 * @since 4.12.0
 *
 * @redeclarable
 *
 * @param array $shortcode_attrs The shortcode attributes
 * @return array The processed shortcode attributes
 */
function wfu_preprocess_attributes($shortcode_attrs) {
	$a = func_get_args(); $a = WFU_FUNCTION_HOOK(__FUNCTION__, $a, $out); if (isset($out['vars'])) foreach($out['vars'] as $p => $v) $$p = $v; switch($a) { case 'R': return $out['output']; break; case 'D': die($out['output']); }
	//correct ftpinfo backslashes
	if ( isset($shortcode_attrs['ftpinfo']) )
		$shortcode_attrs['ftpinfo'] = str_replace(array( '\\:', '\\@' ), array( '\\\\:', '\\\\@' ), $shortcode_attrs['ftpinfo']);
	return $shortcode_attrs;
}

/**
 * Execute custom actions before upload for non-AJAX uploads.
 *
 * This function is executed right after an upload has started for a classic
 * (non-AJAX) upload. It ensures that when the page reloads the plugin will
 * show the result of the upload (and will not render the upload form from the
 * beginning).
 *
 * @since 3.7.0
 *
 * @see _wfu_before_upload filter For more information on parameters and
 *      response array format.
 *
 * @param array $ret An array with information how this function must respond.
 * @param array $attr Information about the upload.
 * @return array The processed $ret array
 */
function wfu_classic_before_upload_handler($ret, $attr) {
	//run only if start_time exists in $_REQUEST parameters
	if ( !isset($_REQUEST['start_time']) ) return $ret;
	if ( $ret["status"] == "die" ) return $ret;
	$start_time = sanitize_text_field( $_REQUEST["start_time"] );
	$sid = $attr["sid"];
	if ( $sid == "" ) {
		$ret["status"] = "die";
		return $ret;
	}
	if ( $ret["status"] != "error" ) {
		$ret["status"] = "success";
		WFU_USVAR_store('wfu_check_refresh_'.$sid, 'form button pressed');
		WFU_USVAR_store('wfu_start_time_'.$sid, $start_time);
	}
	return $ret;
}

/**
 * Check Consent Status of User.
 *
 * This function is executed before an upload starts in order to check the
 * current user's consent status, when consent is activated in the shortcode.
 *
 * @since 4.10.1
 *
 * @see wfu_before_upload filter For more information on parameters and
 *      response array format.
 *
 * @param array $changable_data An array with information that can be changed
 *        by the function
 * @param array $attr Information about the upload.
 * @return array The processed $changable_data array
 */
function wfu_consent_ask_server_handler($changable_data, $attr) {
	//run only if consent_check and consent rejection message exist in
	//$_REQUEST parameters
	if ( !isset($_REQUEST['consent_check']) || !isset($_REQUEST['consent_rejection_message']) ) return $changable_data;
	if ( $changable_data["error_message"] != "" ) return $changable_data;
	$user = wp_get_current_user();
	if ( wfu_check_user_consent($user) != "1" ) {
		$changable_data["error_message"] = wp_strip_all_tags($_REQUEST['consent_rejection_message']);
	}
	return $changable_data;
}

/**
 * Execute After Upload Filters.
 *
 * This function executes internal and custom after upload filters.
 *
 * @since 3.7.0
 *
 * @param int $sid The shortcode ID
 * @param string $unique_id The unique identifier the upload.
 * @param array $params The shortcode attributes.
 * @return array An array holding data after the upload filters
 */
function wfu_execute_after_upload_filters($sid, $unique_id, $params) {
	//apply internal filters from extensions
	$ret = array( "echo" => "" );
	$files = array();
	$filedata_id = "filedata_".$unique_id;
	if ( WFU_USVAR_exists($filedata_id) ) $files = WFU_USVAR($filedata_id);
	$attr = array( "sid" => $sid, "unique_id" => $unique_id, "files" => $files );
	/**
	 * Execute Internal Post Upload Actions.
	 *
	 * This is an internal filter which allows to execute custom actions after
	 * an upload has completely finished.
	 *
	 * @since 3.7.0
	 *
	 * @param array $ret {
	 *     Parameters to return to the plugin.
	 *
	 *     @type string $echo Custom output to return (not used).
	 * }
	 * @param array $attr {
	 *     Various attributes of the upload.
	 *
	 *     @type string $sid The ID of the shortcode.
	 *     @type string $unique_id The unique ID of the upload.
	 *     @type array $files {
	 *         Contains an array of the uploaded files.
	 *
	 *         @type array $file {
	 *             Contains information for each uploaded file.
	 *
	 *             @type string $file_unique_id A unique ID identifying every
	 *                   individual file.
	 *             @type string $original_filename The original filename of the
	 *                   file before any filters might have changed it.
	 *             @type string $filepath The final path of the file, including
	 *                   the filename.
	 *             @type int $filesize The size of the file.
	 *             @type array|null $user_data {
	 *                 An array of user data values if userdata are activated.
	 *
	 *                 @type array $item {
	 *                     Contains information about each user data field.
	 *
	 *                     @type string $label The label of the user data field.
	 *                     @type string $value The value of the user data field.
	 *                 }
	 *             }
	 *             @type string $upload_result The result of the upload process.
	 *                   It can take the following values:
	 *                       success: the upload was successful.
	 *                       warning: the upload was successful but it contains
	 *                                warning messages.
	 *                       error:   the upload failed
	 *             @type string $error_message Warning or error messages
	 *                   generated during the upload process.
	 *             @type string $admin_messages Detailed error messages for
	 *                   administrators generated during the upload process.
	 *         }
	 *     }
	 * }
	 * @param array $params The shortcode attributes of the upload form.
	 */
	$ret = apply_filters("_wfu_after_upload", $ret, $attr, $params);
	//then apply any custom filters created by admin
	$echo_str = "";
	$ret = array( "js_script" => "" );
	/**
	 * Execute Post Upload Actions.
	 *
	 * This filter allows to execute custom actions after an upload has
	 * completely finished. Custom Javascript code can be defined that will be
	 * executed on user's browser after the filter finishes.
	 *
	 * @since 3.7.0
	 *
	 * @param array $ret {
	 *     Parameters to return to the plugin.
	 *
	 *     @type string $js_script Custom Javascript code to execute on user's
	 *           browser.
	 * }
	 * @param array $attr Various attributes of the upload. See previous hook
	 *           for details.
	 */
	$ret = apply_filters("wfu_after_upload", $ret, $attr);
	return $ret;
}

?>