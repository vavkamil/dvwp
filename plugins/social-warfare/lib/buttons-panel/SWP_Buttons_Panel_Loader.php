<?php
/**
 * This serves as a controller to direct the output of buttons panels.
 *
 * Button placement options, as provided in the settings page,
 * are applied in the logic of this class. We also create the
 * content locator, and fallback panels for horizontal floating
 * panels.
 *
 * This used to be the SWP_Display class in /lib/frontend-output/
 *
 * @package   SocialWarfare\Functions
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     1.0.0
 * @since     3.0.0 | 21 FEB 2018 | Refactored into a class-based system.
 * @since     3.1.0 | 18 JUN 2018 | Replaced brack array notation.
 * @since     3.4.0 | 21 SEP 2018 | Ported from SWP_Display to SWP_Buttons_Panel_Loader
 *
 */
class SWP_Buttons_panel_Loader {


	/**
	 * A global for storing post ID's to prevent duplicate processing on the
	 * same posts. Array of post ID's that have been processed during this
	 * pageload.
	 *
	 * @since 2.1.4
	 *
	 * @var array
	 *
	 */
	public $already_printed;


	/**
	 * Options
	 *
	 * This property takes the global $swp_user_options array and stores it
	 * into a local class property.
	 *
	 * @var array
	 */
	public $options;


	/**
	 * Content_Loaded
	 *
	 * A public method that we can check to see if the content hook has been
	 * processed or not.
	 *
	 * @var bool
	 */
	public $content_loaded = false;


	/**
	 * The class constructor.
	 *
	 * @since  3.1.0 | Changed priority for wp_footer. Makes the buttons loads
	 *                 This post data instead of data in the loop.
	 * @param  void
	 * @return void
	 *
	 */
	public function __construct() {


		/**
		 * The global array of posts that have already been processed. This
		 * allows us to ensure that we are not filtering the content from
		 * the_content filter on the same post more than once.
		 *
		 */
		global $swp_already_print;

		// The global array of the user-selected options.
		global $swp_user_options;

		// Declare variable as array if not already done so.
		if ( !is_array( $swp_already_print ) ) {
			$swp_already_print = array();
		}

		// Move these two globals into local properties.
		$this->already_printed = $swp_already_print;
		$this->options = $swp_user_options;

		// Hook into the template_redirect so that is_singular() conditionals will be ready
		add_action( 'template_redirect', array( $this, 'activate_buttons' ) );
		add_action( 'wp_footer', array( $this, 'floating_buttons' ) , 20 );
		add_filter( 'the_content', array( $this, 'add_static_panel_fallback_content' ) , 20 );
		add_action( 'wp_footer', array( $this, 'add_static_panel_fallback_footer' ) , 20 );
	}


	/**
	 * A function to add the buttons
	 *
	 * @since  2.1.4 | 01 JAN 2017 | Created
	 * @since  3.0.6 | 14 MAY 2018 | Added second filter for the_content.
	 * @param  void
	 * @return void
	 *
	 */
	public function activate_buttons() {

		// Bail if we're in the presence of a known conflict without a fix.
		if ( Social_Warfare::has_plugin_conflict() ) {
			return;
		}

		// Only hook into the_content filter if is_singular() is true or
		// they don't use excerpts on the archive pages.
		if( is_singular() || true === SWP_Utility::get_option( 'full_content' ) ) {
			add_filter( 'the_content', array( $this, 'social_warfare_wrapper' ) , 20 );
			add_filter( 'the_content', array( $this, 'add_content_locator' ), 20);
		}

		// If we're not on is_singlular, we'll hook into the excerpt.
		if ( !is_singular() && false === SWP_Utility::get_option( 'full_content' ) ) {
			add_filter( 'the_excerpt', array( $this, 'social_warfare_wrapper' ) );
		}
	}


	/**
	 * Add the content locator div.
	 *
	 * Inserts the empty div for locating Pin images (with javascript). We only
	 * add this to the content if we need it.
	 *
	 * 1. If the Pinit Image Hover Buttons are active we'll use this locator div
	 * to ensure that we are only adding the "save" button to images that are in
	 * the content area.
	 *
	 * 2. If the "float_before_content" otpion is turned off, we'll use this
	 * locator div to determine where the content is and then not display the
	 * buttons panel unless we are past the top of the content area.
	 *
	 * @since  3.0.6 | 14 MAY 2018 | Created the method.
	 * @since  3.4.0 | 19 SEP 2018 | Added check for pinit_toggle option.
	 * @since  3.4.2 | 11 DEC 2018 | Added check for float_before_content option.
	 * @param  string $content The WordPress content passed via filter.
	 * @return string $content The modified string of content.
	 *
	 */
	public function add_content_locator( $content ) {
		$pinit_toggle         = SWP_Utility::get_option( 'pinit_toggle' );
		$float_before_content = SWP_Utility::get_option( 'float_before_content' );

		if( $pinit_toggle || !$float_before_content ) {
			$content .= '<div class="swp-content-locator"></div>';
		}

		return $content;
	}


	/**
	* A wrapper function for adding the buttons, content, or excerpt.
	*
	* @since  1.0.0
	* @param  string $content The content.
	* @return string $content The modified content
	* @todo   Why is the $content passed to both the instantator and the method?
	*
	*/
	public function social_warfare_wrapper( $content ) {

		// The global WordPress post object.
		global $post;

		  // Ensure it's not an embedded post
		  if ( is_singular() && $post->ID !== get_queried_object_id() ) {
			return $content;
		  }

		// Pass the content to the buttons constructor to place them inside.
		$buttons_panel = new SWP_Buttons_Panel( array( 'content' => $content ) );
		return $buttons_panel->render_html();
	}


	/**
	 * A function to add the side floating buttons to a post.
	 *
	 * @since  2.0.0
	 * @param  void
	 * @return void
	 *
	 */
	function floating_buttons() {

		// Bail if we're in the presence of a known conflict with no fix.
		if ( Social_Warfare::has_plugin_conflict() ) {
			return;
		}

		// Instantiate a new Buttons Panel.
		$side_panel = new SWP_Buttons_Panel_Side( array( 'content' => "" ) );

		// Determine if the floating buttons are not supposed to print.
		$location = $side_panel->get_float_location();
		if ( 'none' === $location || 'ignore' === $location ) {
			return;
		}

		// Render the html to output to the screen.
		echo $side_panel->render_html();

	}


	/**
	 * Add the hidden panel to the content if it is available. If the content()
	 * hook is not available, we will attempt later to add it to the footer.
	 *
	 * @since  3.4.2 | 04 DEC 2018 | Created
	 * @param string $content The post content to be modified
	 * @return string The modified post content
	 *
	 */
	public function add_static_panel_fallback_content( $content ) {

		// Record that the post conent hook has indeed loaded.
		$this->content_loaded = true;

		// Bail if we don't need these fallback buttons.
		if( false === $this->should_float_fallback_display() ) {
			return $content;
		}

		// Generate the buttons and return the modified content.
		return $this->generate_static_panel_fallback( $content );
	}


	/**
	 * Add the static fallback buttons to the footer if the content() failed
	 * to get loaded in the above function.
	 *
	 * @since 3.4.2 | 04 DEC 2018 | Created
	 * @param void
	 * @return void
	 *
	 */
	public function add_static_panel_fallback_footer() {


		// Bail if the content hook was successfully loaded.
		if( true === $this->content_loaded ) {
			return;
		}

		// Bail if we don't need these buttons.
		if( false === $this->should_float_fallback_display() ) {
			return;
		}

		// Generate the static panel fallback and echo it to the screen.
		echo $this->generate_static_panel_fallback();

	}


	/**
	 * When floatingHorizontal buttons are desired, but not staticHorizontal
	 * exists, we need to create a staticHorizontal so the floaters have
	 * a target to clone.
	 *
	 * @since  3.4.0 | 25 OCT 2018 | Created.
	 * @param  void
	 * @return void The rendered HTML is echoed to the screen.
	 *
	 */
	public function generate_static_panel_fallback( $content = '' ) {
		global $post;


		/**
		 * If all the checks above get passed, then we'll go ahead and create a
		 * static horizontal buttons panel, wrap it in a wrapper to make it
		 * invisible, and echo it to the screen.
		 *
		 */
		$staticHorizontal = new SWP_Buttons_Panel();
		$html  = '<div class="swp-hidden-panel-wrap" style="display: none; visibility: collapse; opacity: 0">';
		$html .= $staticHorizontal->render_html();
		$html .= '</div>';
		return $content . $html;
	}


	/**
	 * A method to determine if we need to output a set of hidden horizontal
	 * buttons that can be cloned into the floating buttons on the top or bottom.
	 *
	 * @since  3.4.2 | 04 NOV 2018
	 * @param  void
	 * @return bool
	 *
	 */
	public function should_float_fallback_display() {
		global $post;

		if ( !is_object( $post ) ) {
			return false;
		}


		/**
		 * We'll gather up all of our data into some variables so that we can
		 * clean up the conditionals below.
		 *
		 */
		$floating_panel             = SWP_Utility::get_option( 'floating_panel' );
		$float_mobile               = SWP_Utility::get_option( 'float_mobile' );
		$float_location_post_type   = SWP_Utility::get_option( 'float_location_' . $post->post_type );
		$float_location             = SWP_Utility::get_option( 'float_location' );
		$location_post_type         = SWP_Utility::get_option( 'location_' . $post->post_type );
		$post_meta_enabled_static   = get_post_meta( $post->ID, 'swp_post_location', true);
		$post_meta_enabled_floating = get_post_meta( $post->ID, 'swp_float_location', true );
		$acceptable_locations       = array( 'top', 'bottom' );


		/**
		 * Bail out if the floating options are set to off on this specific post.
		 *
		 */
		if ( 'off' == $post_meta_enabled_floating ) {
			return false;
		}


		/**
		 * Autimatically be true if set to on for this post.
		 *
		 */
		if ( 'on' == $post_meta_enabled_floating ) {
			return true;
		}

		/**
		 * We are only generating this if the user has floating buttons activated
		 * at least somewhere. If all floating options are off, just bail.
		 *
		 */
		if ( false == $floating_panel && 'off' == $float_mobile && 'off' == $float_location_post_type ) {
			return false;
		}


		/**
		 * Do not print top/bottom floating buttons on blog pages.
		 *
		 */
		if ( !is_singular() ) {
			return false;
		}


		/**
		 * If both the floating buttons location and the mobile floating
		 * location are not set to top or bottom, then just bail out because we
		 * won't need this.
		 *
		 */
		if(    !in_array( $float_location, $acceptable_locations )
			&& !in_array( $float_mobile, $acceptable_locations ) ) {
			return false;
		}

		/**
		 * This is a backup/fallback to provide a panel of buttons for the JS
		 * to clone. Therefore if a set of buttons are already being printed, we
		 * can just bail out because the JS will use the preexisting panel.
		 *
		 */
		if ( 'none' != $post_meta_enabled_static && 'none' != $location_post_type ) {
			return false;
		}

		return true;

	}


	/**
	 * The main social_warfare function used to create the buttons.
	 *
	 * @since  3.0.0 | 01 MAR 2018 | A class based method created which clones
	 *                               the public facing function.
	 * @param  array $array An array of options and information to pass into the
	 *                      buttons function.
	 * @return string       The html for a panel of buttons.
	 *
	 */
	public static function social_warfare( $args = array() ) {
		$Buttons_Panel = new SWP_Buttons_Panel( $args );
		echo $Buttons_Panel->render_html();
	}
}
