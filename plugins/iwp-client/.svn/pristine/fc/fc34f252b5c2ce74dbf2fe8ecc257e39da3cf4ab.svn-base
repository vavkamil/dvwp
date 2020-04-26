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
 * Various searches on client
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if(basename($_SERVER['SCRIPT_FILENAME']) == "search.php"):
    exit;
endif;
	iwp_mmb_add_action('iwp_mmb_search_posts_by_term', 'iwp_mmb_search_posts_by_term');
	
    function iwp_mmb_search_posts_by_term($params = false){

    	global $wpdb, $current_user;

    	$search_type = trim($params['search_type']);
        $search_term = strtolower(trim($params['search_term']));
    	switch ($search_type){    		
    	case 'plugin':
    		$plugins = get_option('active_plugins');
    		
			if(!function_exists('get_plugin_data'))
				include_once( ABSPATH.'/wp-admin/includes/plugin.php');
				
    		$have_plugin = array();
    		foreach ($plugins as $plugin) {
    			$pl =  WP_PLUGIN_DIR . '/' . $plugin ;
    			$pl_extended = get_plugin_data($pl);
   				$pl_name = $pl_extended['Name'];
    			if(strpos(strtolower($pl_name), $search_term)>-1){

    				$have_plugin[] = $pl_name; 
    			}
    		}
    		if($have_plugin){
    			iwp_mmb_response($have_plugin, true);
    		}else{
    			iwp_mmb_response('Not found', false);
    		}
    		break;
    	case 'theme':
    		$theme = strtolower(get_option('stylesheet'));
    		$tm = ABSPATH . 'wp-content/themes/'. $theme . '/style.css' ;
    		$tm_extended = wp_get_theme($tm);
            $tm_name = $tm_extended->get('Name');
    		$have_theme = array();
    		if(strpos(strtolower($tm_name), $search_term)>-1){
    				$have_theme[] = $tm_name; 
    				iwp_mmb_response($have_theme, true);
    		}else{
    			iwp_mmb_response('Not found', false);
    		}
    		break;
    	default: iwp_mmb_response('Not found', false);
    	}
    }

?>