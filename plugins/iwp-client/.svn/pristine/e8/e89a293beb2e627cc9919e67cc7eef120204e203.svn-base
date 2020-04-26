<?php 
if(basename($_SERVER['SCRIPT_FILENAME']) == "brokenlinks.class.php"):
    exit;
endif;
class IWP_MMB_YWPSEO extends IWP_MMB_Core
{
    function __construct()
    {
        parent::__construct( array(
            'singular'  => 'interval',     //singular name of the listed records
            'plural'    => 'intervals',    //plural name of the listed records
            'ajax'      => false,        //does this table support ajax?
            'screen'      => 'interval-list'        //hook suffix
        ) );
    }
    /********
    ** Private function -  will return the status of  the yoast premium
    ********/
    private function _checkYWPSEO() {
        @include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
            $ywpseo_main_file = WP_PLUGIN_DIR . '/wordpress-seo-premium/wp-seo-main.php';
            $ywpseo_premium_file = WP_PLUGIN_DIR . '/wordpress-seo-premium/wp-seo-premium.php';
            if(file_exists($ywpseo_main_file)){
                @include_once($ywpseo_main_file);
            }else{
                return false;
            }
            if(file_exists($ywpseo_premium_file)){
                @include_once($ywpseo_premium_file);
            }else{
                return false;
            }
            if (class_exists('WPSEO_Premium')) {
                return true;
            } else {
             return false;
            }
        } else {
            return false;
        } 
    }

    function get_seo_info($params){
        if($this->_checkYWPSEO()){
            @require_once(ABSPATH . 'wp-admin/includes/template.php' );
            $ywpseo_title_editor_file = WP_PLUGIN_DIR . '/wordpress-seo-premium/admin/class-bulk-title-editor-list-table.php';
            $ywpseo_desc_editor_file  = WP_PLUGIN_DIR . '/wordpress-seo-premium/admin/class-bulk-description-editor-list-table.php';
            if(file_exists($ywpseo_title_editor_file)){
                @include_once($ywpseo_title_editor_file);
                if(class_exists('WPSEO_Bulk_Title_Editor_List_Table')){
                    $wpseo_bulk_titles_table = new WPSEO_Bulk_Title_Editor_List_Table();
                    $wpseo_bulk_titles_table->prepare_items();
                    $titles = $wpseo_bulk_titles_table->items;
                }else{return false;}
            }else{return false;}
            if(file_exists($ywpseo_desc_editor_file)){
                @include_once($ywpseo_desc_editor_file);
                if(class_exists('WPSEO_Bulk_Description_List_Table')){
                    $wpseo_bulk_desc_table = new WPSEO_Bulk_Description_List_Table();
                    $wpseo_bulk_desc_table->prepare_items();
                    $desc = $wpseo_bulk_desc_table->items;
                }else{return false;}
            }else{return false;}
            $result = array();
            foreach ($titles as $key => $value) {
                $value = (array)$value;
                $result[$value['ID']]['post_modified'] = $value['post_modified'];
                $result[$value['ID']]['post_status'] = $value['post_status'];
                $result[$value['ID']]['post_title'] = $value['post_title'];
                $result[$value['ID']]['post_modified'] =  @date('M d, Y @ h:ia', strtotime($value['post_modified']));
                // $result[$value['ID']]['post_modified'] =  $value['post_modified'];
                $result[$value['ID']]['post_type'] = $value['post_type'];
                $result[$value['ID']]['seo_title'] = $value['seo_title'];
                $result[$value['ID']]['post_link'] = get_permalink($value['ID']);
            }
            foreach ($desc as $key => $value) {
                $value = (array)$value;
                $result[$value['ID']]['post_modified'] = $value['post_modified'];
                $result[$value['ID']]['post_status'] = $value['post_status'];
                $result[$value['ID']]['post_title'] = $value['post_title'];
                $result[$value['ID']]['post_modified'] = @date('M d, Y @ h:ia', strtotime($value['post_modified']));
                // $result[$value['ID']]['post_modified'] = $value['post_modified'];
                $result[$value['ID']]['post_type'] = $value['post_type'];
                $result[$value['ID']]['seo_meta_desc'] = $value['meta_desc'];
                $result[$value['ID']]['post_link'] = get_permalink($value['ID']);
            }
            $refinedResult =array();
            foreach ($result as $pid => $value) {
                $check = 1;
                if($params['type'] != 'all'){
                    if($params['type'] != $value['post_type']){
                        $check = 0;
                    }
                }
                if($params['status'] != 'all'){
                    if($params['status'] != $value['post_status']){
                        $check = 0;
                    }
                }
                if($check){
                    $refinedResult[$pid] = $value;
                }
            }
            if(!empty($refinedResult)){
                return $refinedResult;
            }else{
                return "No results available with the filters selected :(";
            }
        }
    }






function save_seo_info($params){
        if($this->_checkYWPSEO()){
            $ywpseo_meta_file = WP_PLUGIN_DIR . '/wordpress-seo-premium/inc/class-wpseo-meta.php';
            $ywpseo_ajax_file = WP_PLUGIN_DIR . '/wordpress-seo-premium/admin/ajax.php';
            if(file_exists($ywpseo_meta_file)){
                @include_once($ywpseo_meta_file);
                if(class_exists('WPSEO_Meta')){
                    if(file_exists($ywpseo_ajax_file)){
                        @include_once($ywpseo_ajax_file);
                        for($i=0;$i<count($params['data']);$i++){
                            $post_id = $params['data'][$i]['post_id'];
                            $original_title = $params['data'][$i]['old_title'];
                            $new_title = $params['data'][$i]['new_title'];
                            $original_metadesc = $params['data'][$i]['old_metadesc'];
                            $new_metadesc = $params['data'][$i]['new_metadesc'];
                            $title_check = intval($params['data'][$i]['title_check']);
                            $metadesc_check = intval($params['data'][$i]['metadesc_check']);

                            if($original_title == 'null') $original_title == null;
                            if($new_title == 'null') $new_title == null;
                            if($original_metadesc == 'null') $original_metadesc == null;
                            if($new_metadesc == 'null') $new_metadesc == null;
                            $save_title = array();
                            $save_metadesc = array();
                            if($title_check){ $save_title = wpseo_upsert_new_title( $post_id, $new_title, $original_title );}else{$save_title = array('status'=>'neutral');}
                            if($metadesc_check){ $save_metadesc = wpseo_upsert_new_description( $post_id, $new_metadesc, $original_metadesc );}else{$save_metadesc = array('status'=>'neutral');}
                            if($save_title['status'] != 'failure' && $save_metadesc['status'] != 'failure'){
                                $result[$post_id] = array('original_title'=>$original_title,'original_metadesc'=>$original_metadesc,'new_title'=>$new_title,'new_metadesc'=>$new_metadesc);
                            }else{
                                if($save_title['status'] == 'failure'){$errorMsg = $save_title['results'];}
                                if($save_metadesc['status'] == 'failure'){$errorMsg = $save_metadesc['results'];}
                                $result[$post_id] = array('error'=>$errorMsg);
                            }
                        }
                    }else{return false;}
                }else{return false;}
            }else{return false;}
            return $result;
        }
    }


function sample_func($params){
        if($this->_checkYWPSEO()){
            $a = array('aaaa' => 'vvvvv' );
            return $a;
            // return 'sample_res';
        }
    }


}