<?php

use WebSharks\CometCache\Classes\ApiBase;
function clearCometCacheIWP(){
	$api = new ApiBase();
	$plugin = $api->plugin(true);
	$response = $plugin->clearCache(true);
	if ($response === false) {
		return array('error' => 'Unable to perform Comet cache', 'error_code' => 'comet_cache_plugin_delete_cache');
	}
	return array('success' => 'All cache files have been deleted');
}