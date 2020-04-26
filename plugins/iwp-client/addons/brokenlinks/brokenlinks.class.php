<?php 
if(basename($_SERVER['SCRIPT_FILENAME']) == "brokenlinks.class.php"):
    exit;
endif;
class IWP_MMB_BLC extends IWP_MMB_Core
{

    function __construct()
    {
        parent::__construct();
    }
    /********
    ** Private function, Will return the wordfence is load or not
    ********/
    private function _checkBLC() {
        @include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'broken-link-checker/broken-link-checker.php' ) ) {
            $blc_file = WP_PLUGIN_DIR . '/broken-link-checker/includes/links.php';
            if(file_exists($blc_file)){
                @include_once($blc_file);
            }else{
                return false;
            }
            if (class_exists('blcLink')) {
                return true;
            } else {
             return false;
            }
        } else {
            return false;
        } 
    }

    function blc_get_http_status($code){
	   $http_status_codes = array(
            // [Informational 1xx]  
            100=>'Continue',  
            101=>'Switching Protocols',  
            // [Successful 2xx]  
            200=>'OK',  
            201=>'Created',  
            202=>'Accepted',  
            203=>'Non-Authoritative Information',  
            204=>'No Content',  
            205=>'Reset Content',  
            206=>'Partial Content',  
            // [Redirection 3xx]  
            300=>'Multiple Choices',  
            301=>'Moved Permanently',  
            302=>'Found',  
            303=>'See Other',  
            304=>'Not Modified',  
            305=>'Use Proxy',  
            //306=>'(Unused)',  
            307=>'Temporary Redirect',  
            // [Client Error 4xx]  
            400=>'Bad Request',  
            401=>'Unauthorized',  
            402=>'Payment Required',  
            403=>'Forbidden',  
            404=>'Not Found',  
            405=>'Method Not Allowed',  
            406=>'Not Acceptable',  
            407=>'Proxy Authentication Required',  
            408=>'Request Timeout',  
            409=>'Conflict',  
            410=>'Gone',  
            411=>'Length Required', 
            412=>'Precondition Failed',  
            413=>'Request Entity Too Large',  
            414=>'Request-URI Too Long',  
            415=>'Unsupported Media Type',  
            416=>'Requested Range Not Satisfiable',  
            417=>'Expectation Failed',  
            // [Server Error 5xx]  
            500=>'Internal Server Error',  
            501=>'Not Implemented',  
            502=>'Bad Gateway',  
            503=>'Service Unavailable',  
            504=>'Gateway Timeout',  
            505=>'HTTP Version Not Supported',
            509=>'Bandwidth Limit Exceeded',
            510=>'Not Extended',
        ); 
        if(array_key_exists($code, $http_status_codes)){
            return $http_status_codes[$code];
        }else{
            return false;
        }
    }

    function blc_get_all_links()
    {
        if($this->_checkBLC()){
            global $wpdb;
            $sql = "SELECT l.*,i.container_id,i.link_text FROM (SELECT link_id,url,redirect_count,http_code,status_text,broken,false_positive,dismissed FROM ".$wpdb->prefix."blc_links) AS l INNER JOIN (SELECT link_id,container_id,link_text FROM ".$wpdb->prefix."blc_instances) AS i  ON l.link_id=i.link_id  GROUP BY l.link_id";
            // refer file link-query.php get_links()
            $success = $wpdb->get_results($sql);
             if(!empty($success)){
                foreach ($success as $link) {
                    $link->source = get_the_title($link->container_id);
                    if($link->status_text == '') $link->status_text = self::blc_get_http_status($link->http_code);
                }
            }else{
                return 'nolinks';
            }
            return $success;
        } else {
            return array('error'=>"Broken Link Checker plugin is not activated", 'error_code' => 'blc_plugin_not_activated_blc_get_all_links');
        }
    }

    function blc_update_link($params){
        if($this->_checkBLC()){
            $link = new blcLink( intval($params['linkID']) );
            $rez = $link->edit($params['newLink'],$params['newText']);
            $rez['new_text']=$params['newText'];
            $rez['old_link_id']=$params['linkID'];
            $rez['linkType']=$params['linkType'];
            return $rez;
        } else {
            return array('error'=>"Broken Link Checker plugin is not activated", 'error_code' => 'blc_plugin_not_activated_blc_update_link');
        }
    }

    function blc_unlink($params){
        if($this->_checkBLC()){
            $link = new blcLink( intval($params['linkID']) );
            if ( !$link->valid() ){
                return "Oops, I can't find the link ". intval($params['linkID']) ;
            }
            $rez = $link->unlink();
            $rez['old_link_id']=$params['linkID'];
            $rez['linkType']=$params['linkType'];
            return $rez;
        } else {
            return array('error'=>"Broken Link Checker plugin is not activated", 'error_code' => 'blc_plugin_not_activated_blc_unlink');
        }
    }

    function blc_mark_as_not_broken($params){
        if($this->_checkBLC()){
            $link = new blcLink( intval($params['linkID']) );
            if ( !$link->valid() ){
                return "Oops, I can't find the link ". intval($params['linkID']) ;
            }
            //Make it appear "not broken"
            $link->broken = false;  
            $link->false_positive = true;
            $link->last_check_attempt = time();
            $link->isOptionLinkChanged = true;
            $link->log = __("This link was manually marked as working by the user.", 'broken-link-checker');
            
            //Save the changes
            if ( $link->save() ){
                $rez = array('old_link_id'=>$params['linkID'],'linkType'=>$params['linkType'],'marked'=>1);
            } else {
                $rez = array('error'=>'Action couldn\'t be completed', 'error_code' => 'blc_plugin_action_couldnt_completed_blc_mark_as_not_broken');
            }
            return $rez;
        } else {
            return array('error'=>"Broken Link Checker plugin is not activated", 'error_code' => 'blc_plugin_not_activated_blc_mark_as_not_broken');
        }
    }
    function blc_dismiss_link($params){
        $rez = $this->blc_set_dismiss_status(true,$params);
        return $rez;
    }
    function blc_undismiss_link($params){
        $rez = $this->blc_set_dismiss_status(false,$params);
        return $rez;
    }

    private function blc_set_dismiss_status($dismiss,$params){
        if($this->_checkBLC()){
            $link = new blcLink( intval($params['linkID']) );
            if ( !$link->valid() ){
                return "Oops, I can't find the link ". intval($params['linkID']) ;
            }
            $link->dismissed = $dismiss;
            $link->isOptionLinkChanged = true;
            if ( $link->save() ){
                $rez = array('old_link_id'=>$params['linkID'],'linkType'=>$params['linkType'],'dismissvalue_set'=>1);
            } else {
                $rez = array('error'=>'Action couldn\'t be completed', 'error_code' => 'blc_plugin_action_couldnt_completed_blc_set_dismiss_status');
            }
            return $rez;
        } else {
            return array('error'=>"Broken Link Checker plugin is not activated", 'error_code' => 'blc_plugin_not_activated_blc_set_dismiss_status');
        }
    }

    function blc_bulk_actions($params){
        $result = array();
        if($params['action'] == 'bulk_unlink'){
            for ($i=0; $i < count($params['linkData']); $i++) { 
                $redefinedParams = array('linkID'=>$params['linkData'][$i][0],'linkType'=>$params['linkData'][$i][1]);
                array_push($result, $this->blc_unlink($redefinedParams));
            }
        }else if($params['action'] == 'bulk_mark_not_broken'){
            for ($i=0; $i < count($params['linkData']); $i++) { 
                $redefinedParams = array('linkID'=>$params['linkData'][$i][0],'linkType'=>$params['linkData'][$i][1]);
                array_push($result, $this->blc_mark_as_not_broken($redefinedParams));
            }
        }else if($params['action'] == 'bulk_dismiss'){
            for ($i=0; $i < count($params['linkData']); $i++) { 
                $redefinedParams = array('linkID'=>$params['linkData'][$i][0],'linkType'=>$params['linkData'][$i][1]);
                array_push($result, $this->blc_dismiss_link($redefinedParams));
            }
        }else if($params['action'] == 'undismissBroken'){
            for ($i=0; $i < count($params['linkData']); $i++) { 
                $redefinedParams = array('linkID'=>$params['linkData'][$i][0],'linkType'=>$params['linkData'][$i][1]);
                array_push($result, $this->blc_undismiss_link($redefinedParams));
            }
        }  
        return array($result,$params['action']);
    }



}