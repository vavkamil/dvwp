<?php
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2012 Revmakx								*
 * www.revmakx.com											*
 *															*
 ************************************************************/
/*************************************************************
 * 
 * api.php
 * 
 * InfiniteWP addons api
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/
if ( ! defined('ABSPATH') )
	die();

if( !function_exists('iwp_mmb_add_action')) :
	function iwp_mmb_add_action($action = false, $callback = false)
	{
		if (!$action || !$callback)
			return false;
		
		global $iwp_mmb_actions;
		iwp_mmb_function_exists($callback);
		
		if (isset($iwp_mmb_actions[$action]))
			wp_die('Cannot redeclare InfiniteWP action "' . $action . '".');
		
		$iwp_mmb_actions[$action] = $callback;
	}
endif;

if( !function_exists('iwp_mmb_function_exists') ) :
	function iwp_mmb_function_exists($callback)
	{
		global $iwp_core;
		if (!is_string($callback) && !empty($callback) && count($callback) === 2) {
			if (!method_exists($callback[0], $callback[1]))
				wp_die($iwp_core->iwp_dashboard_widget('Information', '', '<p>Class ' . get_class($callback[0]) . ' does not contain <b>"' . $callback[1] . '"</b> function</p>', '', '50%'));
		} else {
			if (!function_exists($callback))
				wp_die($iwp_core->iwp_dashboard_widget('Information', '', '<p>Function <b>"' . $callback . '"</b> does not exists.</p>', '', '50%'));
		}
	}
endif;

?>