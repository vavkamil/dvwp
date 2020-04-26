<?php
/*************************************************************
 * 
 * link.class.php
 * 
 * Manage/Add Links
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if(basename($_SERVER['SCRIPT_FILENAME']) == "link.class.php"):
    exit;
endif;
class IWP_MMB_Link extends IWP_MMB_Core
{
    function __construct()
    {
        parent::__construct();
    }
    
    function add_link($args)
    {
    	global $wpdb;
		extract($args);
    	
    	$params['link_url'] = esc_html($url);
			$params['link_url'] = esc_url($params['link_url']);
			$params['link_name'] = esc_html($name);
			$params['link_id'] = '';
			$params['link_description'] = $description;
			$params['link_target'] = $link_target;
			$params['link_category'] = array();
			
			//Add Link category
			if(is_array($link_category) && !empty($link_category)){
				$terms = get_terms('link_category',array('hide_empty' => 0));
				
				if($terms){
					foreach($terms as $term){
						if(in_array($term->name,$link_category)){
							$params['link_category'][] = $term->term_id;
							$link_category = $this->remove_element($link_category, $term->name);
						}
					}
				}
				if(!empty($link_category)){
					foreach($link_category as $linkkey => $linkval){
						if(!empty($linkval)){
							$link = wp_insert_term($linkval,'link_category');
							
							if(isset($link['term_id']) && !empty($link['term_id'])){
								$params['link_category'][] = $link['term_id'];
							}
						}
					}
				}
			}
			
			//Add Link Owner
			$user_obj = get_userdatabylogin($user);
			if($user_obj && $user_obj->ID){
				$params['link_owner'] = $user_obj->ID;
			}
			
			
			if(!function_exists('wp_insert_link'))
				include_once (ABSPATH . 'wp-admin/includes/bookmark.php');
			
			$is_success = wp_insert_link($params);
			
			if ($is_success && $Rlink) {
				$is_success = $wpdb->insert($wpdb->base_prefix."links_extrainfo", array('link_id'=> $is_success, 'link_reciprocal' => $Rlink, 'link_submitter_email' => $submitterMail));
			}
			return $is_success ? true : array('error' => 'Failed to add link.', 'error_code' => 'failed_to_add_link'); 
    }
	
	function remove_element($arr, $val){
		foreach ($arr as $key => $value){
			if ($value == $val){
				unset($arr[$key]);
			}
		}
		return $arr = array_values($arr);
	}
	
	function get_links($args){
		global $wpdb;
		
		$where='';

		extract($args);
		
		if(!empty($filter_links))
		{
			$table_name = $wpdb->prefix."links_extrainfo";
			$cus_sql = '';
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
				$cus_sql= " OR link_id IN(SELECT link_id FROM ".$wpdb->prefix."links_extrainfo WHERE 1=1 AND (link_reciprocal LIKE '%".esc_sql($filter_links)."%' OR link_submitter_email LIKE '%".esc_sql($filter_links)."%'))";
			}
			
			$where.=" AND (link_name LIKE '%".esc_sql($filter_links)."%' OR link_url LIKE '%".esc_sql($filter_links)."%'".$cus_sql.")";
		}
		
		$linkcats = $this->getLinkCats();
		$sql_query = "$wpdb->links WHERE 1=1 ".$where;
		$links_total = $wpdb->get_results("SELECT count(*) as total_links FROM ".$sql_query);
		$total=$links_total[0]->total_links;
		
		$query_links = $wpdb->get_results("SELECT link_id, link_url, link_name, link_target, link_visible, link_rating, link_rel FROM ".$sql_query." ORDER BY link_name ASC LIMIT 500");
		$links = array();
		foreach ( $query_links as $link_info ) 
		{
			$link_cat = $linkcats[$link_info->link_id];
			$cats = array();
			if (!empty($link_cat)) {
				foreach($link_cat as $catkey=>$catval)
				{
					$cats[] = $catval;
				}
			}
			
			$links[$link_info->link_id] = array(
				"link_url" => $link_info->link_url,
				"link_name" => $link_info->link_name,
				"link_target" => $link_info->link_target,
				"link_visible" => $link_info->link_visible,
				"link_rating" => $link_info->link_rating,
				"link_rel" => $link_info->link_rel,
				"link_cats" => $cats
			);
		}
		
		return array('links' => $links, 'total' => $total);
	}
	
	function getLinkCats($taxonomy = 'link_category')
	{
		global $wpdb;
		
		$cats = $wpdb->get_results("SELECT l.link_id, $wpdb->terms.name
FROM $wpdb->links AS l
INNER JOIN $wpdb->term_relationships ON ( l.link_id = $wpdb->term_relationships.object_id )
INNER JOIN $wpdb->term_taxonomy ON ( $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id
AND $wpdb->term_taxonomy.taxonomy = '".$taxonomy."' )
INNER JOIN $wpdb->terms ON ( $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id )");
		
		foreach ( $cats as $post_val )
		{
			
			$post_cats[$post_val->link_id][] = $post_val->name;
		} 
		
		return $post_cats;
	}
	
	function delete_link($args){
		global $wpdb;
		
		if(!empty($args['link_id']))
		{
			$delete_query = "DELETE FROM $wpdb->links WHERE link_id = ".$args['link_id'];
			$wpdb->get_results($delete_query);
		
			return 'Link deleted.';
		}
		else
		{
			return 'No ID...';
		}
	}
	
	function delete_links($args){
		global $wpdb;
		extract($args);
		
		if($deleteaction=='delete'){
			$delete_query_intro = "DELETE FROM $wpdb->links WHERE link_id = ";
		}
		foreach($args as $key=>$val){
			
			if(!empty($val) && is_numeric($val))
			{
				$delete_query = $delete_query_intro.$val;
				
				$wpdb->query($delete_query);
			}
		}
		return "Link deleted";
	}
    
}
?>