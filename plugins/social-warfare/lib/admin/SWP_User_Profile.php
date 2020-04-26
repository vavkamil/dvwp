<?php

/**
 * SWP_User_Profile: Manages the input fields on the user profile page.
 *
 * This class is used to create and control the fields of input on the user
 * profile within WordPress. It is specially used to allow users to input their
 * Twitter username and Facebook author URL so that we can use these to control
 * tagging and mentions on these respective social media platforms.
 *
 * These fields will override the Twitter username and Facebook URL that is set
 * in the global options page.
 *
 * @since  Unknown | Created |
 * @since  2.2.4   | Updated | 07 MAR 2017 | Added gettext calls to the form.
 * @since  3.0.0   | Updated | 21 FEB 2017 | Refactored into a class-based system.
 * @access public
 * @return none
 *
 */
class SWP_User_Profile {


	/**
	 * This is the magic method used to instantiate this class
	 *
	 * This method is used to queue up all the other methods by attaching them
	 * to the appropriate action hooks in WordPress. The first set of functions
	 * make it so the fields appear. The second set take care of saving the data
	 * when the profile is updated.
	 *
	 * @param  object $user The user object
	 * @since  Unknown
	 * @since  3.0.0   | Created | 21 FEB 2017
	 * @access public
	 * @return none
	 *
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this , 'show_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this , 'show_user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this , 'save_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this , 'save_user_profile_fields' ) );
	}


	/**
	 * Display the new options on the user profile edit page
	 *
	 * This method outputs the user profile fields for the Twitter username
	 * and the Facebook author URL.
	 *
	 * @param  object $user The user object
	 * @since  Unknown
	 * @since  2.2.4   | Updated | 07 MAR 2017 | Added translation gettext calls to each title and description
	 * @access public
	 * @return none
	 *
	 */
	public function show_user_profile_fields( $user ) {
		echo '<h3>Social Warfare Fields</h3>';
		echo '<table class="form-table">';
		echo '<tr>';
		echo '<th><label for="twitter">' . __( 'Twitter Username','social-warfare' ) . '</label></th>';
		echo '<td>';
		echo '<input type="text" name="swp_twitter" id="swp_twitter" value="' . esc_attr( get_the_author_meta( 'swp_twitter' , $user->ID ) ) . '" class="regular-text" />';
		echo '<br /><span class="description">' . __( 'Please enter your Twitter username.','social-warfare' ) . '</span>';
		echo '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<th><label for="facebook_author">' . __( 'Facebook Author URL','social-warfare' ) . '</label></th>';
		echo '<td>';
		echo '<input type="text" name="swp_fb_author" id="swp_fb_author" value="' . esc_attr( get_the_author_meta( 'swp_fb_author' , $user->ID ) ) . '" class="regular-text" />';
		echo '<br /><span class="description">' . __( 'Please enter the URL of your Facebok profile.','social-warfare' ) . '</span>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
	}


	/**
	 * Save our fields when the page is udpated
	 *
	 * This is the method that will save the user's input when the user profile
	 * is updated.
	 *
	 * @param  integer $user_id The user ID
	 * @since  Unknown
	 * @access public
	 * @return none
	 *
	 */
	public function save_user_profile_fields( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		update_user_meta( $user_id, 'swp_twitter', $_POST['swp_twitter'] );
		update_user_meta( $user_id, 'swp_fb_author', $_POST['swp_fb_author'] );
	}


	/**
	 * Traces a post ID back to the user ID of that post.
	 *
	 * Given a post ID, this function will return the author of that post.
	 *
	 * @since  Unknown
	 * @access public
	 * @param  integer $post_id The post ID
	 * @return integer The author ID
	 *
	 */
	public static function get_author( $post_id = 0 ) {
		$post = get_post( $post_id );
		return $post->post_author;
	}
}
