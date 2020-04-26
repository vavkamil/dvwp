<?php
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/
/*************************************************************
 * 
 * 
 * 
 * InfiniteWP Client Plugin
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if(basename($_SERVER['SCRIPT_FILENAME']) == "cleanup.php"):
    exit;
endif;
add_filter('iwp_mmb_stats_filter', 'iwp_mmb_get_extended_info');


function iwp_mmb_get_extended_info($stats)
{
	global $iwp_mmb_core;
	$params = get_option('iwp_mmb_stats_filter');
	$filter = isset($params['plugins']['cleanup']) ? $params['plugins']['cleanup'] : array();
    $stats['num_revisions']     = iwp_mmb_num_revisions();
    //$stats['num_revisions'] = 5;
    $stats['overhead']          = iwp_mmb_handle_overhead(false);
    $stats['num_spam_comments'] = iwp_mmb_num_spam_comments();
    return $stats;
}

/* Revisions */

iwp_mmb_add_action('cleanup_delete', 'iwp_mmb_cleanup_delete_client');

function iwp_mmb_cleanup_delete_client($params = array())
{
    global $iwp_mmb_core;
    $revision_params = get_option('iwp_mmb_stats_filter');
	$revision_filter = isset($revision_params['plugins']['cleanup']) ? $revision_params['plugins']['cleanup'] : array();
    
    $params_array = explode('_', $params['actions']);
    $return_array = array();
	
    foreach ($params_array as $param) {
        switch ($param) {
            case 'revision':
                if (iwp_mmb_delete_all_revisions($revision_filter['revisions'])) {
                    $return_array['revision'] = 'OK';
                } else {
                    $return_array['revision_error'] = 'Failed, please try again';
                }
                break;
            case 'overhead':
                if (iwp_mmb_handle_overhead(true)) {
                    $return_array['overhead'] = 'OK';
                } else {
                    $return_array['overhead_error'] = 'Failed, please try again';
                }
                break;
            case 'comment':
                if (iwp_mmb_delete_spam_comments()) {
                    $return_array['comment'] = 'OK';
                } else {
                    $return_array['comment_error'] = 'Failed, please try again';
                }
                break;
            default:
                break;
        }
        
    }
    
    unset($params);
    
    iwp_mmb_response($return_array, true);
}

function iwp_mmb_num_revisions()
{
    global $wpdb;
    $sql           = "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'";
    $num_revisions = $wpdb->get_var($sql);
    return $num_revisions;
}

function iwp_mmb_select_all_revisions()
{
    global $wpdb;
    $sql       = "SELECT * FROM $wpdb->posts WHERE post_type = 'revision'";
    $revisions = $wpdb->get_results($sql);
    return $revisions;
}

function iwp_mmb_delete_all_revisions()
{
    global $wpdb;
    $sql       = "DELETE a,b,c FROM $wpdb->posts a LEFT JOIN $wpdb->term_relationships b ON (a.ID = b.object_id) LEFT JOIN $wpdb->postmeta c ON (a.ID = c.post_id) WHERE a.post_type = 'revision'";
    $revisions = $wpdb->query($sql);
    
    return $revisions;
}





/* Optimize */

function iwp_mmb_handle_overhead($clear = false)
{
    global $wpdb, $iwp_mmb_core;
    $tot_data   = 0;
    $tot_idx    = 0;
    $tot_all    = 0;
    $query      = 'SHOW TABLES';
    $tables     = $wpdb->get_results($query, ARRAY_A);
    $total_gain = 0;
	$table_string = '';
    if (!empty($table) && is_array($table)) {
    
    foreach ($tables as $table) {
        if (in_array($table['Engine'], array(
            'MyISAM',
            'ISAM',
            'HEAP',
            'MEMORY',
            'ARCHIVE'
        ))) {
            if ($wpdb->base_prefix != $wpdb->base_prefix) {
                if (preg_match('/^' . $wpdb->base_prefix . '*/Ui', $table['Name'])) {
                    if ($table['Data_free'] > 0) {
                        $total_gain += $table['Data_free'] / 1024;
                        $table_string .= $table['Name'] . ",";
                    }
                }
            } else if (preg_match('/^' . $wpdb->base_prefix . '[0-9]{1,20}_*/Ui', $table['Name'])) {
                continue;
            } else {
                if ($table['Data_free'] > 0) {
                    $total_gain += $table['Data_free'] / 1024;
                    $table_string .= $table['Name'] . ",";
                }
            }
        } elseif ($table['Engine'] == 'InnoDB') {
            //$total_gain +=  $table['Data_free'] > 100*1024*1024 ? $table['Data_free'] / 1024 : 0;
        }
    }
   } 
    if ($clear) {
        $table_string = substr($table_string, 0, strlen($table_string) - 1); //remove last ,
        
        $table_string = rtrim($table_string);
        
        $query = "OPTIMIZE TABLE $table_string";
        
        $optimize = $wpdb->query($query);
        
        return $optimize === FALSE ? false : true;
    } else
        return round($total_gain, 3);
}




/* Spam Comments */

function iwp_mmb_num_spam_comments()
{
    global $wpdb;
    $sql       = "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'";
    $num_spams = $wpdb->get_var($sql);
    return $num_spams;
}

function iwp_mmb_delete_spam_comments()
{
    global $wpdb;
    $spams = 1;
    $total = 0;
    while ($spams) {
        $sql   = "DELETE FROM $wpdb->comments WHERE comment_approved = 'spam' LIMIT 200";
        $spams = $wpdb->query($sql);
        $total += $spams;
        if ($spams)
            usleep(100000);
    }
    return $total;
}


function iwp_mmb_get_spam_comments()
{
    global $wpdb;
    $sql   = "SELECT * FROM $wpdb->comments as a LEFT JOIN $wpdb->commentmeta as b WHERE a.comment_ID = b.comment_id AND a.comment_approved = 'spam'";
    $spams = $wpdb->get_results($sql);
    return $spams;
}

?>