<?php
/*************************************************************
 * 
 * user.class.php
 * 
 * Add Users
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if(basename($_SERVER['SCRIPT_FILENAME']) == "user.class.php"):
    exit;
endif;
class IWP_MMB_User extends IWP_MMB_Core
{
    function __construct()
    {
        parent::__construct();
    }
    
    function get_users($args){
		global $wpdb;

		//$args: $user_roles;
		if (empty($args)) {
		    return false;
		}

		$user_roles      = isset($args['user_roles']) ? $args['user_roles'] : array();
		$username_filter = isset($args['username_filter']) ? $args['username_filter'] : '';

		$userlevels    = array();
		$level_strings = array();
		foreach ($user_roles as $user_role) {
		    switch (strtolower($user_role)) {
		        case 'subscriber' :
		            $userlevels[]    = 0;
		            $level_strings[] = $user_role;
		            break;
		        case 'contributor' :
		            $userlevels[]    = 1;
		            $level_strings[] = $user_role;
		            break;
		        case 'author' :
		            $userlevels[]    = 2;
		            $level_strings[] = $user_role;
		            break;
		        case 'editor' :
		            $userlevels[]    = 7;
		            $level_strings[] = $user_role;
		            break;
		        case 'administrator' :
		            $userlevels[]    = 10;
		            $level_strings[] = $user_role;
		            break;
		        default:
		            break;
		    }
		}

		$users         = array();
		$userlevel_qry = "('".implode("','", $userlevels)."')";
		$queryOR       = '';
		if (!empty($level_strings)) {
		    foreach ($level_strings as $level) {
		        if (!empty($queryOR)) {
		            $queryOR .= ' OR ';
		        }
		        $queryOR .= "meta_value LIKE '%{$level}%'";
		    }
		}
		$field  = $wpdb->prefix."capabilities";
		$field2 = $wpdb->prefix."user_level";

		$metaQuery  = "SELECT * from {$wpdb->usermeta} WHERE meta_key = '{$field}' AND ({$queryOR})";
		$user_metas = $wpdb->get_results($metaQuery);

		if ($user_metas == false || empty($user_metas)) {
		    $metaQuery  = "SELECT * from {$wpdb->usermeta} WHERE meta_key = '{$field2}' AND meta_value IN {$userlevel_qry}";
		    $user_metas = $wpdb->get_results($metaQuery);
		}

		$include = array(0 => 0);
		if (is_array($user_metas) && !empty($user_metas)) {
		    foreach ($user_metas as $user_meta) {
		        $include[] = $user_meta->user_id;
		    }
		}

		$args            = array(0, 0);
		$args['include'] = $include;
		$args['fields']  = 'all_with_meta';
		if (!empty($username_filter)) {
		    $args['search'] = $username_filter;
		}
		$temp_users = get_users($args);
		$user       = array();
		foreach ((array) $temp_users as $temp) {
		    $user['user_id']         = $temp->ID;
		    $user['user_login']      = $temp->user_login;
		    $user['wp_capabilities'] = array_keys($temp->$field);
		    $users[]                 = $user;
		}
		$users['request_roles'] = $user_roles;
		return array('users' => $users);
 }
    
    function add_user($args)
    {
    	
    	if(!function_exists('username_exists') || !function_exists('email_exists'))
    	 include_once(ABSPATH . WPINC . '/registration.php');
      
      if(username_exists($args['user_login']))
    	 return array('error' => 'Username already exists', 'error_code' => 'username_already_exists');
    	
    	if (email_exists($args['user_email']))
    		return array('error' => 'Email already exists', 'error_code' => 'email_already_exists');
    	
			if(!function_exists('wp_insert_user'))
			 include_once (ABSPATH . 'wp-admin/includes/user.php');
			
			$user_id =  wp_insert_user($args);
			
			if($user_id){
			
				if($args['email_notify']){
					//require_once ABSPATH . WPINC . '/pluggable.php';
					wp_new_user_notification($user_id, $args['user_pass']);
				}
				return $user_id;
			}else{
				return array('error' => 'User not added. Please try again.', 'error_code' => 'user_not_added_please_try_again');
			}
			 
    }
    
    function edit_users($args){
    	
    	if(empty($args))
    		return false;
    		if(!function_exists('get_user_to_edit'))
			 		include_once (ABSPATH . 'wp-admin/includes/user.php');
			 	if(!function_exists('wp_update_user'))
			 		include_once (ABSPATH . WPINC.'/user.php');
			 
    	 extract($args);
    	 //$args: $users, $new_role, $new_password, $user_edit_action
    	 
    	 $return = array();
    	 if(count($users)){
    	 foreach($users as $user){
    	 	$result = '';
    	 	$user_obj = $this->iwp_mmb_get_user_info( $user );
    	 	if($user_obj != false){
		    	 switch($user_edit_action){
		    		case 'change-password':
		    		 if($new_password){ 
		    		 	$user_data = array();
		    		 	$userdata['user_pass'] = $new_password;
		    	 	 	$userdata['ID'] = $user_obj->ID;
		    	   	$result = wp_update_user($userdata);
		    	  	} else {
		    	  		$result = array('error' => 'No password provided.', 'error_code' => 'no_password_provided');
		    	  	}
		    		 break;
		    		case 'change-role':
		    		 if($new_role){
		    		 	if($user != $username){
		    			if(!$this->last_admin($user_obj)){
		    				$user_data = array(); 
		    	 	 		$userdata['ID'] = $user_obj->ID;
		    	 	 		$userdata['role'] = strtolower($new_role);
		    	   		$result = wp_update_user($userdata);
		    	  	} else {
		    	  		$result = array('error' => 'Cannot change role to the only one left admin user.', 'error_code' => 'cannot_change_role_to_the_only_one_left_admin_user');
		    	  		}
		    	  	} else {
		    	  		$result = array('error' => 'Cannot change role to user assigned for InfiniteWP.', 'error_code' => 'cannot_change_role_to_user_assigned_to_InfiniteWP');
		    	  	} 
		    	 	} else {
		    	 		$result = array('error' => 'No role provided.', 'error_code' => 'no_role_provided');
		    	 	}
		    			break;
		    		case 'delete-user':
		    			if($user != $username){
			    			if(!$this->last_admin($user_obj)){
				    			if($reassign_user){
				    			$to_user = $this->iwp_mmb_get_user_info( $reassign_user );
				    				if($to_user != false){
				    					$result = wp_delete_user($user_obj->ID, $to_user->ID);
				    				} else {
				    					$result = array('error' => 'User not deleted. User to reassign posts doesn\'t exist.', 'error_code' => 'user_not_deleted_user_to_reassign_posts_doesnt_exist');
				    				}
				    			} else {
				    				$result = wp_delete_user($user_obj->ID);
				    			}
				    		} else {
				    			$result = array('error' => 'Cannot delete the only one left admin user.', 'error_code' => 'cannot_delete_the_only_one_left_admin_user');
				    		}
			    		} else {
			    			$result = array('error' => 'Cannot delete user assigned for InfiniteWP.', 'error_code' => 'cannot_delete_user_assigned_for_InfiniteWP');
			    		}
		    		
		    			break;
		    		default:
		    			$result = array('error' => 'Wrong action provided. Please try again.', 'error_code' => 'wrong_action_provided_please_try_again');
		    			break;
		    		}
    			} else {
    				$result = array('error' => 'User not found.', 'error_code' => 'user_not_found');
    			}
    			
    			if(is_wp_error($result)){
    				$result = array('error' => $result->get_error_message()); 
    			}
    			
    			$return[$user] = $result; 
    		}
    	}
    	
    	return $return;
    		
    }
    
    //Check if user is the only one admin on the site
    function last_admin($user_obj){
    	global $wpdb;
    	$field = $wpdb->base_prefix."capabilities";
    	$capabilities = array_map('strtolower',array_keys($user_obj->$field));
    	$result = count_users();
	    	if(in_array('administrator',$capabilities)){
	    		
	    		if(!function_exists('count_users')){
				 		include_once (ABSPATH . WPINC. '/user.php');
					}
					
	    		$result = count_users();
	    		if($result['avail_roles']['administrator'] == 1){
	    			return true;
	    		}	
	    	}
    		return false;
    }
    
}
?>