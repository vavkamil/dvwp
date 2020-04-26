<?php

/**
 * SWP_Buttons_Panel_Trait
 *
 * The purpose of this trait is to allow access to commonly used methods
 * throughout the various Buttons_Panel classes of the plugin without having to
 * force the extension of an abstract class onto them.
 *
 *     create_panel();
 *
 *     generate_panel_html();
 *     generate_individual_buttons_html();
 *     generate_total_shares_html();
 *
 *     should_panel_display();
 *     should_total_shares_display();
 *
 *     get_alignment();
 *     get_colors();
 *     get_shape();
 *     get_scale();
 *     get_min_width();
 *     get_float_background();
 *     get_option();
 *     get_float_location();
 *     get_mobile_float_location();
 *     get_order_of_icons();
 *     get_ordered_network_objects();
 *     get_key_from_name();
 *
 *
 *
 * @since 3.4.0 | 21 SEP 2018 | Created
 *
 */
trait SWP_Buttons_Panel_Trait {


	protected function append_panel_to_content() {


		/**
		 * If the panel type is static_horizontal, then the switch below will
		 * determine the lcoation setting and append the html to the content
		 * according to that location.
		 *
		 */
		if( $this->panel_type === 'static_horizontal' ) {
			switch ($this->location) {
				case 'both' :
					$content = $this->html . $this->content . $this->html;
				break;
				case 'above' :
					$content = $this->html . $this->content;
				break;
				case 'below' :
					$content = $this->content . $this->html;
				break;

				case 'none' :
					$content = $this->content;
				default :
					$content = $this->content;
				break;
			}
		}


		/**
		 * If the panel type is floating_side, then forget the content because
		 * it will be empty. Instead just replace the content to be returned
		 * with the generated html for the panel.
		 *
		 */
		if( $this->panel_type === 'floating_side' ) {
			$content = $this->html;
		}

		$this->content = $content;

		if ( isset( $this->args['echo']) && true === $this->args['echo'] ) {
			echo $this->content;
		}

		return $this->content;
	}

	/**
	* Takes a display name and returns the snake_cased key of that name.
	*
	* This is used to convert a network's name, such as Google Plus,
	* to the database-friendly key of google_plus.
	*
	* @since  3.0.0 | 18 APR 2018 | Created
	* @param  string $name The string to convert.
	* @return string The converted string.
	*
	*/
	public function get_key_from_name( $string ) {
		return preg_replace( '/[\s]+/', '_', strtolower( trim ( $string ) ) );
	}


	/**
	* Tells you true/false if the buttons should print on this page.
	*
	* Each variable is a boolean value. For the buttons to eligible for printing,
	* each of the variables must evaluate to true.
	*
	* $user_settings: Options editable by the Admin user.
	* $desired_conditions: WordPress conditions we require for the buttons.
	* $undesired_conditions: WordPress pages where we do not display the buttons.
	*
	*
	* @return Boolean True if the buttons are okay to print, else false.
	* @since  3.0.8 | 21 MAY 2018 | Added extra condition to check for content
	*                               (for calls to social_warfare()).
	* @since  3.3.3 | 18 SEP 2018 | Added check for in_the_loop().
	* @since  3.4.0 | 24 OCT 2018 | Added check for $this->post_data.
	* @param  void
	* @return void
	*
	*/
	public function should_panel_display() {


		/**
		 * If for some reason the post_data failed to populate, we just have to
		 * bail out so the PHP doesn't throw undefined property errors.
		 *
		 */
		if( empty( $this->post_data ) ) {
			return false;
		}


		/**
		* WordPress requires title and content. This indicates the buttons are
		* called via social_warfare() or via the shortcode.
		*
		*/
		if ( empty( $this->content ) && !isset( $this->args['content'] )  ) {
		   return true;
		}


		$user_settings        = 'none' !== $this->location;
		$desired_conditions   = is_main_query() && in_the_loop() && get_post_status( $this->post_id ) === 'publish';
		$undesired_conditions = is_admin() || is_feed() || is_search() || is_attachment() || is_preview();

		return $user_settings && $desired_conditions && !$undesired_conditions;
	}


	/**
	* A method to get the alignment when scale is set below 100%.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return string A string of the appropriate CSS class to add to the panel.
	*
	*/
	protected function get_alignment() {
		return ' scale-' . $this->get_option('button_alignment');
	}


	/**
	* A function to get the color states for this buttons panel.
	*
	* All of the buttons contain 3 states: default, hover, and single. The
	* default state is what the buttons look like when not being interacted
	* with. The hover is what all the buttons in the panel look like when
	* the panel is being hovered. The single is what the individual button
	* being hovered will look like.
	*
	* This method handles generating the classes that the CSS can target to
	* ensure that all three of those states work.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @since  3.3.2 | 13 SEP 2018 | Modified to control float selectors better
	* @param  boolean $float Whether this is a floating panel or not.
	* @return string  The string of CSS classes to be used on the panel.
	*
	*/
	protected function get_colors( $float = false ) {


		/**
		* If pro was installed, but no longer is installed or activated,
		* then this option won't exist and will return false. If so, then
		* we output the default core color/style classes.
		*
		*/
		if ( false === $this->get_option( 'default_colors' ) ) {
		   return " swp_default_full_color swp_individual_full_color swp_other_full_color ";
		}


		/**
		* If the buttons are the static horizontal buttons (not the float),
		* or if it is the float but we are inheriting the styles from the
		* horizontal buttons, then just output the CSS classes that are used
		* for the horizontal buttons.
		*
		* "float_style_source" on the options page is actually answering
		* the question "Do the floating buttons inherit their colors from
		* the horizontal buttons?" It will be true if they do, and false if
		* they don't.
		*
		*/
		$prefix = '';


		/**
		* If this is a set of floating buttons and we are not inheriting
		* the color styles from the static floating buttons, then we need
		* to return the style classes that are specific to the floating
		* buttons being rendered.
		*
		*/
		if( true === $float && false === $this->options['float_style_source'] ) {
		   $prefix = 'float_';
		}


		/**
		*
		* If it's a static, horizontal panel, there is no prefix. If it's
		* a floating panel, there is a prefix. However, the prefix needs
		* to be removed for the CSS class name that is actualy output.
		*
		* So here we fetch the appropriate color options, strip out the
		* "float_" prefix since we don't use that on the actual CSS
		* selector, and then return the CSS classes that will be added to
		* this buttons panel that is being rendered.
		*
		*/
		$default = str_replace( $prefix, '', $this->get_option( $prefix . 'default_colors' ) );
		$hover   = str_replace( $prefix, '', $this->get_option( $prefix . 'hover_colors' ) );
		$single  = str_replace( $prefix, '', $this->get_option( $prefix . 'single_colors' ) );
		return " swp_default_{$default} swp_other_{$hover} swp_individual_{$single} ";

	}


	/**
	* A method to fetch/determine the shape of the current buttons.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return string The string of the CSS class to be used.
	*
	*/
	protected function get_shape() {
		$button_shape = $this->get_option( 'button_shape' );

		//* They have gone from an Addon to Core.
		if ( false === $button_shape ) {
		   return "swp_flat_fresh ";
		}

		return "swp_{$button_shape} ";
	}


	/**
	* A method to fetch/determine the size/scale of the panel.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return string The CSS class to be added to the panel.
	*
	*/
	protected function get_scale() {
		$button_size = $this->get_option( 'button_size' );

		//* They have gone from an Addon to Core.
		if ( false === $button_size ) {
		   return "scale-100 ";
		}

		return 'scale-' . $button_size * 100;
	}


	/**
	* A method for getting the minimum width of the buttons panel.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return string The HTML attribute to be added to the buttons panel.
	*
	*/
	protected function get_min_width() {
		$min_width = $this->get_option( 'float_screen_width' );

		//* They have gone from an Addon to Core.
		if ( false === $min_width ) {
		   return 'data-min-width="1100" ';
		}

		return 'data-min-width="' . $min_width . '" ';
	}


	/**
	* A method for getting the transition mode for the side floating buttons.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return string The HTML attribute to be added to the buttons panel.
	*
	*/
	protected function get_float_transition() {
		$transition = $this->get_option( 'transition' );

		//* They have gone from an Addon to Core.
		if ( false === $transition ) {
		   return 'data-transition="slide" ';
		}

		return 'data-transition="' . $transition . '" ';
	}


	/**
	* A method to determin the background color of the floating buttons.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return string The HTML attribute to be added to the buttons panel.
	*
	*/
	protected function get_float_background() {
		$float_background_color = $this->get_option( 'float_background_color' );

		//* They have gone from an Addon to Core.
		if ( false === $float_background_color ) {
		   return '';
		}

		return 'data-float-color="' . $float_background_color . '" ';
	}


	/**
	* Get one of the user options.
	*
	* This function acts just like the global SWP_Utility:get_option() method.
	* In fact, it even uses that function as a fallback. Basically, when the
	* Buttons_Panel class is instantiated, a user has the option to pass in an
	* array of options. These will be merged with the global $swp_user_options,
	* and stored in the $this->options property.
	*
	* First, we check if the option exists in our local options property. Second,
	* we use the SWP_Utility::get_option() method which will pull the option
	* from the global settings as well as handle things like requests for
	* options that may not exist (return false).
	*
	* @since  3.0.5 | 10 MAY 2018 | Created
	* @param  string $key The name of the option.
	* @return mixed       The value of that option.
	*
	*/
	protected function get_option( $key ) {

		// Check if this option exists in this panel's localized options.
		if( isset( $this->options[$key] ) ) {
		   return $this->options[$key];
		}

		// As a backup, use the option as it exists in the global user options.
		return SWP_Utility::get_option( $key );
	}


	/**
	* A Method to determine the location of the floating buttons
	*
	* This method was created because we can't just use the option as it is set
	* in the options page. Instead, we must first check that we are on a single.php
	* page and second we must check that the floating buttons toggle is turned on.
	* Then and only then will we check the actual floating location and return it.
	*
	* @since  3.0.0 | 09 MAY 2018 | Created
	* @since  3.0.4 | 09 MAY 2018 | Added check for the global post type on/off toggle.
	* @param  void
	* @return string A string containing the float bar location.
	*
	*/
	public function get_float_location() {


		/**
		 * If we failed to populate a post id, then we just bail out and won't
		 * be showing any.
		 *
		 */
		if( false == isset( $this->post_id ) ) {
			return 'none';
		}


		/**
		 * These are the float location settings all across the WordPress
		 * ecosystem. There is a global on/off setting, a per post type on/off
		 * setting, and even a setting on each individual post.
		 *
		 */
		$float_location    = $this->get_option( 'float_location' );
		$global_setting    = $this->get_option('floating_panel' );
		$post_type_setting = 'on' == $this->get_option('float_location_' . $this->post_data['post_type'] );
		$post_setting      = get_post_meta( $this->post_id, 'swp_float_location', true );


		/**
		 * If the floaters are implicitly turned on at the post level, then that
		 * means the user wants them to float on this post regardless of the
		 * global settings.
		 *
		 */
		if( 'on' === $post_setting ) {
			return $float_location;
		}


		/**
		 * We don't use floating buttons on the home page.
		 *
		 */
		if( is_home() && !is_front_page() ) {
			return 'none';
		}


		/**
		 * Do not print floating buttons on archive pages.
		 *
		 */
		if ( !is_singular() ) {
			return 'none';
		}


		/**
		 * If the location on this specific post is set to off, then we'll
		 * disable floating locations. Anything else and we'll defer to the
		 * global setting.
		 *
		 */
		if ( !empty( $post_setting ) && 'off' === $post_setting ) {
			return 'none';
		}


		/**
		 * If everything checks out, we'll return the global float location. If
		 * somehow nothing checked out, we'll return none.
		 *
		 */
		if ( $global_setting && $post_type_setting ) {
			return $float_location;
		}

		return 'none';
	}


	/**
	 * This method wraps the output of get_float_location into it's html
	 * attribute that will be appended to the wrapper div of the buttons panel.
	 *
	 * @since  3.4.0 | 24 OCT 2018 | Created
	 * @param  void
	 * @return string The html attribute.
	 *
	 */
	public function get_float_location_attribute() {
		return 'data-float="' . $this->get_float_location() . '" ';
	}


	/**
	* A Method to determine the location of the floating buttons on mobile devices
	*
	* This method was created because we can't just use the option as it is set
	* in the options page. Instead, we must first check that we are on a single.php
	* page and second we must check that the floating buttons toggle is turned on.
	* Then and only then will we check the actual floating location and return it.
	*
	* @since  3.0.0 | 09 MAY 2018 | Created
	* @since  3.0.4 | 09 MAY 2018 | Added check for the global post type on/off toggle.
	* @since  3.4.0 | 17 OCT 2018 | Added conditions for front_page, archive, category.
	* @since  3.4.2 | 07 DEC 2018 | Added conditions for false mobile locations.
	* @param  void
	* @return string A string containing the float bar location.
	*
	*/
	public function get_mobile_float_location() {
		$global_float_toggle    = $this->get_option( 'floating_panel' );
		$post_type_float_toggle = $this->get_option( 'float_location_' . $this->post_data['post_type'] );
		$float_location         = $this->get_option( 'float_location' );
		$mobile_location        = $this->get_option( 'float_mobile' );


		/**
		 * If the float location is completely set to none, then we won't have
		 * any floating buttons on mobile either.
		 *
		 */
		if( 'none' == $this->get_float_location() ) {
			$mobile_location = 'none';
		}


		/**
		 * If the $mobile_location is set to false, it means that this option
		 * is not available which means that pro is not installed. If this
		 * option were available, it would return as a string.
		 * As such, we'll set it to the defaults that are available in core.
		 *
		 */
		if( false === $mobile_location ) {
			$mobile_location = $float_location;


			/**
			 * If the main floating buttons are set to left or right, then the
			 * user won't get any floating buttons at all once those go away.
			 * Switching from side to top/bottom is a pro only feature. If they
			 * have them already set to top/bottom then we will just keep that
			 * setting as no actual transition is needed.
			 *
			 */
			if( true === in_array( $float_location, array( 'left','right' ) ) ) {
				$mobile_location = 'none';
			}
		}


		//* Front page, archive, and categories do not have a global float option.
		//* Instead they use options in the post editor (saved in post_meta).
		if ( is_front_page() || is_archive() || is_category() ) {
		   $float_enabled = get_post_meta( $this->post_data['ID'], 'swp_float_location', true );

		   if ( 'off' != $float_enabled ) {
			   return 'data-float-mobile="' . $mobile_location . '" ';
		   }

		   return 'data-float-mobile="none" ';
		}

		if( is_singular() && true === $global_float_toggle && 'on' === $post_type_float_toggle ) {
		   return 'data-float-mobile="' . $mobile_location . '" ';
		}

		return 'data-float-mobile="none" ';
	}


	/**
	* A method to control the order in which the buttons are output.
	*
	* @since  3.4.0 | 20 SEP 2018 | Created
	* @since  3.4.2 | 05 DEC 2018 | Added check for false sort_method for core.
	* @param  void
	* @return array The array of network names in their proper order.
	*
	*/
	protected function get_order_of_icons() {
		global $swp_social_networks;
		$active_networks = SWP_Utility::get_option( 'order_of_icons' );
		$sort_method     = SWP_Utility::get_option( 'order_of_icons_method' );
		$order           = array();


		/**
		* If the icons are set to be manually sorted, then we simply use the
		* order from the options page that the user has set.
		*
		* Adding a check for false, because this option is pro only and will
		* return false if it is not available in core, and therefore will default
		* to the manual sorting method.
		*
		*/
		if ( 'manual' === $sort_method || false === $sort_method ) {
			return $active_networks;
		}


		/**
		* Even if it's not set to manual sorting, we will still use the manual
		* order of the buttons if we don't have any share counts by which to
		* process the order dynamically.
		*
		*/
		if( empty( $this->shares ) || !is_array( $this->shares ) ) {
		   return $active_networks;
		}


		/**
		* If the icons are set to be ordered dynamically, and we passed the
		* check above then we will sort them based on how many shares each
		* network has.
		*
		*/
		arsort( $this->shares );
		foreach( $this->shares as $network => $share_count ) {
		   if( $network != 'total_shares' && in_array( $network, $active_networks ) ) {
			   $order[$network] = $network;
		   }
		}
		$this->options['order_of_icons'] = $order;
		return $order;
	}


	/**
	* A method to arrange the array of network objects in proper order.
	*
	* @since  3.0.0 | 04 MAY 2018 | Created
	* @since  3.3.0 | 30 AUG 2018 | Renamed from 'order_network_objects' to 'get_ordered_network_objects'
	* @param  array $order An ordered array of network keys.
	* @return array        An ordered array of network objects.
	*
	*/
	protected function get_ordered_network_objects( $order ) {
		$network_objects = array();

		if ( empty( $order ) ) :
		   $order = SWP_Utility::get_option( 'order_of_icons' );
		endif;

		foreach( $order as $network_key ) {
		   foreach( $this->networks as $key => $network ) :
			   if ( $key === $network_key ) :
				   $network_objects[$key] = $network;
			   endif;
		   endforeach;
		}

		return $network_objects;
	}


	/**
	* Render the html for the indivial buttons.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  integer $max_count The maximum number of buttons to display.
	* @return string             The compiled html for the buttons.
	*
	*/
	protected function generate_individual_buttons_html() {
		$html = '';
		$count = 0;

		foreach( $this->networks as $key => $network ) {


			/**
			 * The floating buttons have a maximum number of buttons that can
			 * be displayed at any given time. If that number is set, we bail
			 * out once that maximum has been reached. If that number is set to
			 * zero, it represents unlimited buttons can be displayed.
			 *
			 */
			if ( 0 !== $this->max_buttons && $count == $this->max_buttons ) :
			   return $html;
			endif;

			// Pass in some context for this specific panel of buttons
			$context['shares']    = $this->shares;
			$context['options']   = $this->options;
			$context['post_data'] = $this->post_data;
			$html .= $network->render_HTML( $context );
			$count++;
		}

		return $html;
	}


	/**
	* The Total Shares Count
	*
	* If share counts are active, renders the Total Share Counts HTML.
	*
	* @since  3.0.0 | 18 APR 2018 | Created
	* @since  3.3.2 | 12 SEP 2018 | Moved strtolower to $totals_argument
	* @since  3.4.0 | 20 SEP 2018 | Moved display logic to should_total_shares_display()
	* @param  void
	* @return string $html The fully qualified HTML to display share counts.
	*
	*/
	public function generate_total_shares_html() {

		// Check if total shares should be rendered or not.
		if( false === $this->should_total_shares_display() ) {
		   return;
		}

		// Render the html for the total shares.
		$html = '<div class="nc_tweetContainer swp_share_button total_shares total_sharesalt" >';
		   $html .= '<span class="swp_count ">' . SWP_Utility::kilomega( $this->shares['total_shares'] ) . ' <span class="swp_label">' . __( 'Shares','social-warfare' ) . '</span></span>';
		$html .= '</div>';

		return $html;
	}


	/**
	* Should the total shares be rendered.
	*
	* @since  3.4.0 | 20 SEP 2018 | Created
	* @param  void
	* @return bool True: show shares; False: don't render them.
	*
	*/
	protected function should_total_shares_display() {


		/**
		 * Find out if the total shares are activated on the settings page. We
		 * will overwrite this variable if the user has passed in a 'buttons'
		 * argument and instead use what they've passed in.
		 *
		 */
		$are_total_shares_active = $this->get_option('total_shares');


		/**
		* If this is a shortcode and the buttons argument has been specifically
		* passed into the function, then we will use that buttons argument to
		* determine whether or not to display the total shares.
		*
		*/
		$buttons = isset( $this->args['buttons'] ) ? $this->args['buttons'] : array();
		if ( $this->is_shortcode && !empty( $buttons ) ) {
			$total = in_array('total', array_map('strtolower', $buttons) );
			$totals = in_array('totals', array_map('strtolower', $buttons) );
			$are_total_shares_active = ( $total || $totals );
		}


		/**
		* If total shares are turned off or this is a shortcode with a buttons
		* parameter that didn't include totals then we're not going to render
		* any total shares.
		*
		*/
		if ( false == $are_total_shares_active ) {
		   return false;
		}


		/**
		* If minimum share counts are enabled and this post hasn't achieved
		* that threshold of shares yet, then we don't show them.
		*
		*/
		if ( $this->shares['total_shares'] < $this->get_option('minimum_shares') ) {
		   return false;
		}


		// If none of the flags above get caught, return true.
		return true;
	}


	/**
	 * Combine the Total Shares html with the Buttons html.
	 *
	 * @since  3.4.0 | 23 OCT 2018 | Created
	 * @param  void
	 * @return string The html for the buttons and total shares.
	 *
	 */
	protected function generate_buttons_and_totals_html() {

		// Generate the html for the total shares and each button in the set.
		$total_shares_html = $this->generate_total_shares_html();
		$buttons_html      = $this->generate_individual_buttons_html();
		$is_side_floating  = ('floating_side' == $this->panel_type);
		$is_left_aligned   = ('totals_left' == $this->get_option('totals_alignment'));


		/**
		 * $is_side_floating: If this is a set of floating sidebar buttons, then
		 * we will attach the total shares to the left which will then visually
		 * appear at the top of the panel.
		 *
		 * $is_left_aligned: If, in the options page, the user has set the total
		 * shares to appear on the left, then we will concantenate the html to
		 * the left.
		 *
		 */
		if( $is_side_floating || $is_left_aligned ) {
			$this->inner_html = $total_shares_html . $buttons_html;
			return;
		}

		/**
		 * If it's not a set of floating buttons and it's not set to the left,
		 * then we attach the total shares on the right.
		 *
		 */

		else {
			$this->inner_html = $buttons_html . $total_shares_html;
		}
	}


	/**
	 * Generate the html attributes attached to the container.
	 *
	 * @since  3.4.0 | 23 OCT 2018 | Created
	 * @param  void
	 * @return void
	 * @var    $this->attributes Stores panel html attributes as a string of text.
	 *
	 */
	protected function generate_attributes() {
		$attributes  = $this->get_min_width();
		$attributes .= $this->get_float_background();
		$attributes .= $this->get_float_location_attribute();
		$attributes .= $this->get_mobile_float_location();
		$attributes .= $this->get_float_transition();
		$this->attributes = $attributes;
	}


	/**
	 * Generate the CSS classes that need to be applied to the buttons panel.
	 *
	 * @since  3.4.0 | 23 OCT 2018 | Created
	 * @param  void
	 * @return void
	 * @var    $this->classes Stores panel classes as a string of text.
	 *
	 */
   protected function generate_css_classes() {
		$classes = 'class="swp_social_panel swp_horizontal_panel ';
		$classes .= $this->get_shape();
		$classes .= $this->get_colors();
		$classes .= $this->get_scale();
		$classes .= $this->get_alignment();
		$classes .= '" ';
		$this->classes = $classes;
   }


	/**
	 * A method used to combine the CSS classes for the panel wrapper, the
	 * html attributes, and the html for the buttons and total shares into one
	 * single string of html for output.
	 *
	 * @since  3.4.0 | 24 OCT 2018 | Created
	 * @param  void
	 * @return void
	 * @var    $this->html Stores the fully compiled panel html as a string.
	 *
	 */
   protected function combine_html_assets() {
	   $this->html = '<div ' . $this->classes . $this->attributes . '>' . $this->inner_html . '</div>';
   }
}
