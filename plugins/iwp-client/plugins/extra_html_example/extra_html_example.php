<?php
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/

// add filter for the stats structure
 if(basename($_SERVER['SCRIPT_FILENAME']) == "extra_html_example.php"):
    exit;
endif;
add_filter('iwp_mmb_stats_filter', iwp_mmb_extra_html_example);

function iwp_mmb_extra_html_example($stats)
 {
        $count_posts = wp_count_posts();
     
        $published_posts = $count_posts->publish;
        
        // add 'extra_html' element. This is what gets displayed in the dashboard
 	$stats['extra_html'] = '<p>Hello from '.get_bloginfo('name').' with '.$published_posts.' published posts.</p>';
 	
 	// return the whole array back
 	return $stats;
 }
?>