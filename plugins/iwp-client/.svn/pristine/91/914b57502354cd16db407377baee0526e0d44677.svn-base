<?php
global $wpdb;
if(basename($_SERVER['SCRIPT_FILENAME']) == "optimize.class.php"):
    exit;
endif;
class IWP_MMB_Optimize extends IWP_MMB_Core
{
    function __construct()
    {
        parent::__construct();
    }
    
	function cleanup_system($cleanupType){
		
		$cleanup_values = array();
		$cleanup_values['value_array'] = array();
		$text = '';

		if (isset($cleanupType["clean-revisions"])) {
			$values = self::cleanup_type_process('revisions', $cleanupType['numberOfRevisions']);
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['revisions'] = $values['value'];
		}
	
		if (isset($cleanupType["clean-autodraft"])) {
			$values = self::cleanup_type_process('autodraft');
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['autodraft'] = $values['value'];
			}	
			
		if (isset($cleanupType["clean-comments"])) {
			$values = self::cleanup_type_process('spam');
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['spam'] = $values['value'];
			}
		
		if (isset($cleanupType["unapproved-comments"])) {
			$values = self::cleanup_type_process('unapproved');
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['unapproved'] = $values['value'];
			}
		if (isset($cleanupType["trash-post"])) {
			$values = self::cleanup_type_process('trash-post');
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['trash-post'] = $values['value'];
			}
		if (isset($cleanupType["trash-comments"])) {
			$values = self::cleanup_type_process('trash-comments');
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['trash-comments'] = $values['value'];
			}
		if (isset($cleanupType["meta-comments"])) {
			$values = self::cleanup_type_process('meta-comments');
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['meta-comments'] = $values['value'];
			}
		if (isset($cleanupType["meta-posts"])) {
			$values = self::cleanup_type_process('meta-posts');
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['meta-posts'] = $values['value'];
			}
		if (isset($cleanupType["pingbacks"])) {
			$values = self::cleanup_type_process('pingbacks');
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['pingbacks'] = $values['value'];
			}
		if (isset($cleanupType["trackbacks"])) {
			$values = self::cleanup_type_process('trackbacks');
			$text .= "<span class='wpm_results'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['trackbacks'] = $values['value'];
			}
		
		
		$text .= '<br>';
		
		if (isset($cleanupType["optimize-db"])) {
			$values = self::cleanup_type_process('optimize-db');
			$text .= "<span class='wpm_results_db'>" . $values['message'] . "</span>";
			$cleanup_values['value_array']['optimize-db'] = $values['value'];
			//$text .= DB_NAME.__(" Database Optimized!<br>", 'wp-optimize');
			}
	
		if ($text !==''){
			$cleanup_values['message'] = $text;
			return $cleanup_values;
		}
	}
	
	function cleanup_type_process($cleanupType, $numberOfRevisions = 0){
		global $wpdb;
		$clean = ""; $message = "";
		$message_array = array();
		//$message_array['value'] = array();
		$optimized = array();

		switch ($cleanupType) {
			
			case "revisions":
				$revisionWhere = '';
				if (!empty($numberOfRevisions) && $numberOfRevisions != 0) {
					$revisionQuery = "SELECT ID FROM $wpdb->posts WHERE post_type = 'revision' order by ID desc LIMIT ". $numberOfRevisions;
					$revisionIDs = $wpdb->get_results( $revisionQuery, ARRAY_N );
					$revisionsIDsArray = array();
					foreach ($revisionIDs as $key => $revisionID) {
						if ($revisionID) {
							$revisionsIDsArray[]= $revisionID[0];
						}
					}
					$revisionWhere = " AND ID NOT IN('".implode("', '", $revisionsIDsArray)."')";
				}
				$clean = "DELETE FROM $wpdb->posts WHERE post_type = 'revision'".$revisionWhere;
				$revisions = $wpdb->query( $clean );
				$message .= __('Post revisions deleted - ', 'wp-optimize') . $revisions;
				$message_array['value'] = $revisions;
				//$message_array['del_post_rev']['message'] = $revisions.__(' post revisions deleted<br>', 'wp-optimize');
				
				break;
				
	
			case "autodraft":
				$clean = "DELETE FROM $wpdb->posts WHERE post_status = 'auto-draft'";
				$autodraft = $wpdb->query( $clean );
				$message .= __('Auto drafts deleted - ', 'wp-optimize') . $autodraft;
				$message_array['value'] = $autodraft;
				//$message_array['del_auto_drafts']['message'] = $autodraft.__(' auto drafts deleted<br>', 'wp-optimize');
				
				break;
	
			case "spam":
				$clean = "DELETE FROM $wpdb->comments WHERE comment_approved = 'spam';";
				$comments = $wpdb->query( $clean );
				$message .= __('Spam comments deleted - ', 'wp-optimize') . $comments;
				$message_array['value'] = $comments;
				//$message_array['del_spam_comments']['message'] = $comments.__(' spam comments deleted<br>', 'wp-optimize');
				
				break;
	
			case "unapproved":
				$clean = "DELETE FROM $wpdb->comments WHERE comment_approved = '0';";
				$comments = $wpdb->query( $clean );
				$message .= __('Unapproved comments deleted - ', 'wp-optimize') . $comments;
				$message_array['value'] = $comments;
				//$message_array['del_unapproved_comments']['message'] = $comments.__(' unapproved comments deleted<br>', 'wp-optimize');
				
				break;

			case "trash-post":
				$clean = "DELETE FROM $wpdb->posts WHERE post_status = 'trash';";
				$comments = $wpdb->query( $clean );
				$message .= __('Trashed posts deleted - ', 'wp-optimize') . $comments;
				$message_array['value'] = $comments;
				//$message_array['del_unapproved_comments']['message'] = $comments.__(' unapproved comments deleted<br>', 'wp-optimize');
				
				break;

			case "trash-comments":
				$clean = "DELETE FROM $wpdb->comments WHERE comment_approved = 'trash';";
				$comments = $wpdb->query( $clean );
				$message .= __('Trashed comments deleted - ', 'wp-optimize') . $comments;
				$message_array['value'] = $comments;
				break;
			case "meta-comments":
				$clean = "DELETE cm FROM  $wpdb->commentmeta  cm LEFT JOIN  $wpdb->comments  wp ON wp.comment_ID = cm.comment_id WHERE wp.comment_ID IS NULL";
				$comments = $wpdb->query( $clean );
				$message .= __('Unused comments metadata deleted - ', 'wp-optimize') . $comments;
				$message_array['value'] = $comments;
				break;

			case "meta-posts":
				$clean = "DELETE pm FROM  $wpdb->postmeta  pm LEFT JOIN  $wpdb->posts  wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL;";
				$comments = $wpdb->query( $clean );
				$message .= __('Unused posts metadata deleted - ', 'wp-optimize') . $comments;
				$message_array['value'] = $comments;				
				break;

			case "pingbacks":
				$clean = "DELETE FROM $wpdb->comments WHERE comment_type = 'pingback';";
				$comments = $wpdb->query( $clean );
				$message .= __('Pingbacks deleted - ', 'wp-optimize') . $comments;
				$message_array['value'] = $comments;	
				break;

			case "trackbacks":
				$clean = "DELETE FROM $wpdb->comments WHERE comment_type = 'trackback';";
				$comments = $wpdb->query( $clean );
				$message .= __('Trackbacks deleted - ', 'wp-optimize') . $comments;
				$message_array['value'] = $comments;
				
				break;
			case "optimize-db":
			   self::optimize_tables(true);
			   $message .= "Database ".DB_NAME." Optimized!";
			   $message_array['value'] = DB_NAME;
			   
			   break;
		
			default:
				$message .= __('NO Actions Taken', 'wp-optimize');
				$message_array['value'] = $comments;
				
				break;
		} // end of switch
		
	$message_array['message'] = $message;
	return $message_array;

	} // end of function
	
	function optimize_tables($Optimize=false){
	global $wpdb;
		$db_clean = DB_NAME;
			
		$local_query = 'SHOW TABLE STATUS FROM `'. $db_clean.'`';
		$result = $wpdb->get_results($local_query);
		if ($wpdb->num_rows){
			foreach ($result as $row) 
			{
				 $local_query = 'OPTIMIZE TABLE '.$row->Name;
				$resultat  = $wpdb->get_results($local_query);
			}
		}
	
	}

}
?>