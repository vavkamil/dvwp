<?php 
if(basename($_SERVER['SCRIPT_FILENAME']) == "google_webmasters.class.php"):
    exit;
endif;


if(!function_exists('iwp_mmb_create_webmasters_redirect_table')){
    function iwp_mmb_create_webmasters_redirect_table(){
            global $wpdb;

            $IWP_MMB_WEBMASTERS_REDIRECT_TABLE_VERSION =    get_site_option( 'iwp_webmasters_redirect_table_version' );

            if(version_compare($IWP_MMB_WEBMASTERS_REDIRECT_TABLE_VERSION, '1.0') == -1){

                $table_name = $wpdb->base_prefix . "iwp_redirects"; 

                $sql = "
                    CREATE TABLE IF NOT EXISTS $table_name (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `oldLink` varchar(255) NOT NULL,
                    `redirectLink` varchar(255) NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `oldLink` (`oldLink`)
                    );
                ";

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );

                $_NEW_IWP_MMB_WEBMASTERS_REDIRECT_TABLE_VERSION = '1.0';
            }

            if(!empty($_NEW_IWP_MMB_WEBMASTERS_REDIRECT_TABLE_VERSION)){
                    add_option( "iwp_webmasters_redirect_table_version", $_NEW_IWP_MMB_WEBMASTERS_REDIRECT_TABLE_VERSION);
            }
    }
}


class IWP_MMB_GWMT extends IWP_MMB_Core 
{

    function __construct()
    {
        parent::__construct();
    }

    function google_webmasters_redirect($params){
        iwp_mmb_create_webmasters_redirect_table();
        if($params['location']=='htaccess'){
            $file = ABSPATH.'.htaccess';
            file_put_contents($file,  "\r\n\r\n"."#Redirecting from ".$params['oldLink']." to ".$params['newLink']."\r\nRedirect ".$params['oldLink']." ".$params['newLink'], FILE_APPEND | LOCK_EX); 
            return $params;
        }else if($params['location']=='database'){
            global $wpdb;
            $success = $wpdb->insert($wpdb->base_prefix.'iwp_redirects',array('oldLink'=>rtrim($params['oldLink'],'/'),'redirectLink'=>$params['newLink']));
            if($success) 
                return $params;
        }
    }

    function google_webmasters_redirect_again($params){
        iwp_mmb_create_webmasters_redirect_table();
        if($params['location']=='htaccess'){
            
        }else if($params['location']=='database'){
            global $wpdb;
            $success = $wpdb->update( $wpdb->base_prefix.'iwp_redirects', array('redirectLink' => $params['newLink']), array('oldLink' => rtrim($params['originalLink'],'/'),'redirectLink'=>$params['oldLink']) );
            if($success) 
                return $params;
        }
    }


}
?>