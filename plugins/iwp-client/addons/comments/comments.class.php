<?php
/*************************************************************
 * 
 * comment.class.php
 * 
 * Get comments
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if(basename($_SERVER['SCRIPT_FILENAME']) == "comments.class.php"):
    exit;
endif;
class IWP_MMB_Comment extends IWP_MMB_Core
{
    function __construct()
    {
        parent::__construct();
    }
    
    function change_status($args)
    {

    	global $wpdb;
    	$comment_id = $args['comment_id'];
    	$status = $args['status']; 
    	
       	if ( 'approve' == $status )
			$status_sql = '1';
		elseif ( 'unapprove' == $status )
			$status_sql = '0';
		elseif ( 'spam' == $status )
			$status_sql =  'spam';
		elseif ( 'trash' == $status )
			$status_sql =  'trash';
		$sql = "update ".$wpdb->base_prefix."comments set comment_approved = '%s' where comment_ID = '%s'";
		$success = $wpdb->query($wpdb->prepare($sql, $status_sql, $comment_id));
		
				
        return $success;
    }
	
	function get_comments($args){
	
		global $wpdb;
		
		$where='';
		
		extract($args);
		
		if(!empty($filter_comments))
		{
			$where.=" AND (c.comment_author LIKE '%".esc_sql($filter_comments)."%' OR c.comment_content LIKE '%".esc_sql($filter_comments)."%')";
		}
		$comment_array = array();
		$comment_statuses = array('approved', 'pending', 'spam', 'trash');
		foreach ($args as $checkbox => $checkbox_val)
		{
			if($checkbox_val=="on") {
				$status_val = str_replace("iwp_get_comments_","",$checkbox);
				if($status_val == 'approved'){
					$status_val = 1;
				}elseif($status_val == 'pending'){
					$status_val = 0;
				}
				$comment_array[]="'".$status_val."'";
			}
		}
		if(!empty($comment_array))
		{
			$where.=" AND c.comment_approved IN (".implode(",",$comment_array).")";
		}
		
		$sql_query = "$wpdb->comments as c, $wpdb->posts as p WHERE c.comment_post_ID = p.ID ".$where;
		
		$comments_total = $wpdb->get_results("SELECT count(*) as total_comments FROM ".$sql_query);
		$total=$comments_total[0]->total_comments;
		$comments_approved = $this->comment_total();
		
		$query_comments = $wpdb->get_results("SELECT c.comment_ID, c.comment_post_ID, c.comment_author, c.comment_author_email, c.comment_author_url, c.comment_author_IP, c.comment_date, c.comment_content, c.comment_approved, c.comment_parent, p.post_title, p.post_type, p.guid FROM ".$sql_query." ORDER BY c.comment_date DESC LIMIT 500");
		$comments = array();
		foreach ( $query_comments as $comments_info ) 
		{
			$comment_total_approved = 0;
			if(isset($comments_approved[$comments_info->comment_post_ID]['approved'])){
				$comment_total_approved = $comments_approved[$comments_info->comment_post_ID]['approved'];
			}
			$comment_total_pending = 0;
			if(isset($comments_approved[$comments_info->comment_post_ID]['pending'])){
				$comment_total_pending = $comments_approved[$comments_info->comment_post_ID]['pending'];
			}
			$comment_parent_author = '';
			if($comments_info->comment_parent > 0){
				$select_parent_author = "SELECT comment_author FROM $wpdb->comments WHERE comment_ID = ".$comments_info->comment_parent;
				$select_parent_author_res = $wpdb->get_row($select_parent_author);
				$comment_parent_author = $select_parent_author_res->comment_author;
			}
			
			$comments[$comments_info->comment_ID] = array(
				"comment_post_ID" => $comments_info->comment_post_ID,
				"comment_author" => $comments_info->comment_author,
				"comment_author_email" => $comments_info->comment_author_email,
				"comment_author_url" => $comments_info->comment_author_url,
				"comment_author_IP" => $comments_info->comment_author_IP,
				"comment_date" => $comments_info->comment_date,
				"comment_content" => $comments_info->comment_content,
				"comment_approved" => $comments_info->comment_approved,
				"comment_parent" => $comments_info->comment_parent,
				"comment_parent_author" => $comment_parent_author,
				"post_title" => $comments_info->post_title,
				"post_type" => $comments_info->post_type,
				"guid" => $comments_info->guid,
				"comment_total_approved" => $comment_total_approved,
				"comment_total_pending" => $comment_total_pending,
			);
		}
		
		return array('comments' => $comments, 'total' => $total);
	}
	
	function comment_total(){
		global $wpdb;
		$totals = array();
		$select_total_approved = "SELECT COUNT(*) as total, p.ID FROM $wpdb->comments as c, $wpdb->posts as p WHERE c.comment_post_ID = p.ID AND c.comment_approved = 1 GROUP BY p.ID";
		$select_total_approved_res = $wpdb->get_results($select_total_approved);
	
		if(!empty($select_total_approved_res)){
			foreach($select_total_approved_res as $row){
				$totals[$row->ID]['approved'] = $row->total;
			}
		}
		$select_total_pending = "SELECT COUNT(*) as total, p.ID FROM $wpdb->comments as c, $wpdb->posts as p WHERE c.comment_post_ID = p.ID AND c.comment_approved = 0 GROUP BY p.ID";
		$select_total_pending_res = $wpdb->get_results($select_total_pending);
		if(!empty($select_total_pending_res)){
			foreach($select_total_pending_res as $row){
				$totals[$row->ID]['pending'] = $row->total;
			}
		}
		
		return $totals;
	}
	
	function action_comment($args){
		global $wpdb;
		extract($args);
		if($docomaction == 'approve'){
			$docomaction = '1';
		}else if($docomaction == 'unapprove' || $docomaction == 'untrash' || $docomaction == 'unspam'){
			$docomaction = '0';
		}
		
		if(!empty($comment_id)){
			if($docomaction == 'delete'){
				$update_query = "DELETE FROM $wpdb->comments WHERE comment_ID = ".$comment_id;
				$delete_query = "DELETE FROM $wpdb->commentmeta WHERE comment_id = ".$comment_id;
				$wpdb->query($delete_query);
			}else{
				$update_query = "UPDATE $wpdb->comments SET comment_approved = '".$docomaction."' WHERE comment_ID = ".$comment_id;
			}
			$wpdb->query($update_query);
		
			return 'Comment updated.';
		}else{
			return 'No ID...';
		}
	}
	
	function bulk_action_comments($args){
		global $wpdb;
		extract($args);
		
		if($docomaction=='delete'){
			$update_query_intro = "DELETE FROM $wpdb->comments WHERE comment_ID = ";
		}else{
			if($docomaction=='unapprove' || $docomaction == 'untrash' || $docomaction == 'unspam'){
				$docomaction = '0';
			}else if($docomaction == 'approve'){
				$docomaction = '1';
			}
			$update_query_intro = "UPDATE $wpdb->comments SET comment_approved = '".$docomaction."' WHERE comment_ID = ";
		}
		foreach($args as $key=>$val){
			
			if(!empty($val) && is_numeric($val))
			{
				if($docomaction=='delete'){
					$delete_query = "DELETE FROM $wpdb->commentmeta WHERE comment_id = ".$val;
					$wpdb->query($delete_query);
				}
				$update_query = $update_query_intro.$val;
				
				$wpdb->query($update_query);
			}
		}
		return "comments updated";
	}
	
	function reply_comment($args){
		global $wpdb, $current_user;
		
		extract($args);
		
		$comments = array();
		
		if(!empty($comment_id) && !empty($reply_text)){
			$admins = get_userdata($current_user->ID);
			$now = time();
			$insert_reply = "INSERT INTO $wpdb->comments(comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_date_gmt, comment_content, comment_karma, comment_approved, comment_agent, comment_parent, user_id) VALUES(".$post_id.", '".$admins->user_login."', '".$admins->user_email."', '".$admins->user_url."', '".$_SERVER['REMOTE_ADDR']."', NOW(), NOW(), '%s', 0, 1, '".$_SERVER['HTTP_USER_AGENT']."', ".$comment_id.", ".$current_user->ID.")";
			$insert_reply_res = $wpdb->query($wpdb->prepare($insert_reply, $reply_text));
			$lastid = $wpdb->insert_id;
		
			$comments_approved = $this->comment_total();
			
			$comment_total_approved = 0;
			if(isset($comments_approved[$post_id]['approved'])){
				$comment_total_approved = $comments_approved[$post_id]['approved'];
			}
			$comment_total_pending = 0;
			if(isset($comments_approved[$post_id]['pending'])){
				$comment_total_pending = $comments_approved[$post_id]['pending'];
			}
			
			$comment_parent_author = '';
			if($comment_id > 0){
				$select_parent_author = "SELECT c.comment_author, p.post_title, p.post_type, p.guid FROM $wpdb->comments as c, $wpdb->posts as p WHERE c.comment_post_ID = p.ID AND c.comment_ID = ".$comment_id;
				$select_parent_author_res = $wpdb->get_row($select_parent_author);
				$comment_parent_author = $select_parent_author_res->comment_author;
			}
			
			$comments[$lastid] = array(
				"comment_post_ID" => $post_id,
				"comment_author" => $admins->user_login,
				"comment_author_email" => $admins->user_email,
				"comment_author_url" => $admins->user_url,
				"comment_author_IP" => $_SERVER['REMOTE_ADDR'],
				"comment_date" => $now,
				"comment_content" => $reply_text,
				"comment_approved" => '1',
				"comment_parent" => $comment_id,
				"comment_parent_author" => $comment_parent_author,
				"post_title" => $select_parent_author_res->post_title,
				"post_type" => $select_parent_author_res->post_type,
				"guid" => $select_parent_author_res->guid,
				"comment_total_approved" => $comment_total_approved,
				"comment_total_pending" => $comment_total_pending,
			);
			
			
		}
		
		return $comments;
	}
    
}
?>