<?php
/**
 * Pinterest
 *
 * Class to add a Pinterest share button to the available buttons
 *
 * @package   SocialWarfare\Functions\Social-Networks
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0 | Unknown     | CREATED
 * @since     2.2.4 | 02 MAY 2017 | Refactored functions & upinterest_descriptionated docblocking
 * @since     3.0.0 | 05 APR 2018 | Rebuilt into a class-based system.
 *
 */
class SWP_Pinterest extends SWP_Social_Network {

	/**
	 * The Magic __construct Method
	 *
	 * This method is used to instantiate the social network object. It does three things.
	 * First it sets the object properties for each network. Then it adds this object to
	 * the globally accessible swp_social_networks array. Finally, it fetches the active
	 * state (does the user have this button turned on?) so that it can be accessed directly
	 * within the object.
	 *
	 * @since  3.0.0 | 06 APR 2018 | Created
	 * @param  none
	 * @return none
	 * @access public
	 *
	 */
	public function __construct() {
		// Upinterest_descriptionate the class properties for this network
		$this->name           = __( 'Pinterest', 'social-warfare' );
		$this->cta            = __( 'Pin', 'social-warfare' );
		$this->key            = 'pinterest';
		$this->default        = 'true';
		$this->base_share_url = 'https://pinterest.com/pin/create/button/?url=';
		$this->init_social_network();
	}

	/**
	 * Generate the API Share Count Request URL
	 *
	 * @since  1.0.0 | 06 APR 2018 | Created
	 * @access public
	 * @param  string $url The permalink of the page or post for which to fetch share counts
	 * @return string $request_url The complete URL to be used to access share counts via the API
	 *
	 */
	public function get_api_link( $url ) {
		return 'https://api.pinterest.com/v1/urls/count.json?url=' . $url;
	}

	/**
	 * Parse the response to get the share count
	 *
	 * @since  1.0.0 | 06 APR 2018 | Created
	 * @access public
	 * @param  string $response The raw response returned from the API request
	 * @return int $total_activity The number of shares reported from the API
	 *
	 */
	public function parse_api_response( $response ) {
		$response = preg_replace( '/^receiveCount\((.*)\)$/', "\\1", $response );
		$response = json_decode( $response, true );
		return isset( $response['count'] ) ? intval( $response['count'] ) : 0;
	}


	/**
	 * Create the HTML to display the share button
	 *
	 * @since  1.0.0
	 * @since  3.0.0 | 01 MAY 2018 | Re-wrote the function to a class method.
	 * @since  3.0.6 | 14 MAY 2018 | Appended $pinterest_username to $pinterest_description.
	 * @since  3.0.9 | 04 JUN 2018 | Updated the check for pinterest image.
	 * @access public
	 * @return array $panel_context Array of
	 *                   ['post_data']  => metadata about the post;
	 *                   ['shares']     => share count data
	 *                   ['options']    => swp_user_options
	 * @param  bool $echo If true, this will immediately echo its code rather than save it for later.
	 *
	 */
	 public function render_HTML( $panel_context, $echo = false ) {
		global $swp_user_options;
		$post_id = $panel_context['post_data']['ID'];
		$post_url = urlencode( urldecode( SWP_URL_Management::process_url( $panel_context['post_data']['permalink'] , 'pinterest' , $post_id ) ) );

		$options = $swp_user_options;
		$metabox_pinterest_image = get_post_meta( $post_id , 'swp_pinterest_image_url' , true );

		if ( !empty( $metabox_pinterest_image ) ) :
			$pinterest_image = $metabox_pinterest_image;
		elseif ( isset($options['pinterest_fallback']) && $options['pinterest_fallback'] == 'featured' ):
			$pinterest_image = wp_get_attachment_url( get_post_thumbnail_id( $post_id ) );
		else :
			$pinterest_image = '';
		endif;

		$pinterest_username = '';
		$pinterest_id = SWP_Utility::get_option( 'pinterest_id' );
		if ( !empty( $pinterest_id ) ) {
			 $pinterest_username = ' via @' . str_replace( '@' , '' , $pinterest_id );
		}

		$title = str_replace( '|', '', strip_tags( $panel_context['post_data']['post_title'] ) );
		$pinterest_description	= get_post_meta( $post_id , 'swp_pinterest_description' , true );

		if( is_array( $pinterest_description ) && !empty( $pinterest_description ) ) {
			$pinterest_description = $pinterest_description[0];
			// delete_post_meta( $post_id , 'swp_pinterest_description' );
			update_post_meta( $post_id , 'swp_pinterest_description' , $pinterest_description );
		}

		if ( empty( $pinterest_description ) ) :
			$pinterest_description = $title;
		endif;

		$pinterest_username = SWP_Pinterest::get_via();
		$pinterest_description = SWP_Pinterest::trim_pinterest_description( $pinterest_description, $pinterest_username );

		if ( !empty( $pinterest_image ) ) :
			   $anchor = '<a rel="nofollow noreferrer noopener" class="nc_tweet swp_share_link" data-count="0" ' .
						'data-link="https://pinterest.com/pin/create/button/' .
						'?url=' . $panel_context['post_data']['permalink'] .
						'&media=' . urlencode( $pinterest_image ) .
						'&description=' . urlencode( $pinterest_description ) .
					'">';
		   else :
			   $anchor = '<a rel="nofollow noreferrer noopener" class="nc_tweet swp_share_link noPop" ' .
						'onClick="var e=document.createElement(\'script\');
						   e.setAttribute(\'type\',\'text/javascript\');
						   e.setAttribute(\'charset\',\'UTF-8\');
						   e.setAttribute(\'src\',\'//assets.pinterest.com/js/pinmarklet.js?r=\'+Math.random()*99999999);
						   document.body.appendChild(e);
						" >';
		   endif;

		 //* Begin parent class method.

		 $post_data = $panel_context['post_data'];
		 $share_counts = $panel_context['shares'];
		 $options = $panel_context['options'];

		 // Build the button.
		 $icon = '<span class="iconFiller">';
			 $icon.= '<span class="spaceManWilly">';
				 $icon.= '<i class="sw swp_'.$this->key.'_icon"></i>';
				 $icon.= '<span class="swp_share">' . $this->cta . '</span>';
			 $icon .= '</span>';
		 $icon .= '</span>';

		 if ( true === $this->are_shares_shown( $share_counts , $options ) ) :
			 $icon .= '<span class="swp_count">' . SWP_Utility::kilomega( $share_counts[$this->key] ) . '</span>';
		 else :
			 $icon = '<span class="swp_count swp_hide">' . $icon . '</span>';
		 endif;

		 // Build the wrapper.
		 $html = '<div class="nc_tweetContainer swp_share_button swp_'.$this->key.'" data-network="'.$this->key.'">';
			 $html .= $anchor;
				 // Put the button inside.
				 $html .= $icon;
			 $html.= '</a>';
		 $html.= '</div>';

		 // Store these buttons so that we don't have to generate them for each set
		 $this->html = $html;

		 if ( $echo ) :
			 echo $html;
		 endif;

		 return $html;
	 }


	 public function generate_share_link( $post_data ) {
		return 0;
	 }

	 /**
	  * Trims the text of a pinterest description down to the 500 character max.
	  *
	  * @since  3.5.0 | 21 FEB 2019 | Created.
	  * @param  string $pinterest_description The target Pinterest description.
	  * @return string The same pinterest description, capped at 500 characters.
	  *
	  */
	 public static function trim_pinterest_description( $pinterest_description, $via = '') {
		 if ( strlen( $pinterest_description ) > 500 ) {
			 /**
			  * The provided description is too long before we have added
			  * anything. We need to trim it before appending the @via.
			  *
			  */
			 $read_more = '... ' . $via;
			 $cutoff = 500 - strlen( $read_more );

			 $pinterest_description = substr( $pinterest_description, 0, $cutoff );
			 $pinterest_description .= $read_more;
		 }
		 else {
			 /**
			  * The description length + via length would be too long, so
			  * trim a little bit of description so via will fit.
			  *
			  */
			 if ( strlen( $pinterest_description ) + strlen( $via ) > 500 ) {
				 $cutoff = 500 - strlen( $via );
				 $pinterest_description = substr( $pinterest_description, 0, $cutoff );
			 }

			 $pinterest_description .= $via;
		 }
		 return $pinterest_description;
	 }

	 /**
	  * Fetches the user's @via for Pinterest, if it exists.
	  *
	  * @since  3.5.1 | 26 FEB 2019 | Created.
	  * @param  void
	  * @return string The '@via $username', or an empty string.
	  *
	  */
	public static function get_via() {
		$pinterest_username = '';
		$via = SWP_Utility::get_option( 'pinterest_id' );

		if ( !empty( $via ) ) {
			$pinterest_username = ' via @' . str_replace( '@' , '' , $via );
		}
		return $pinterest_username;
	 }
}
