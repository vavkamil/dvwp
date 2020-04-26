<?php
/**
* The core Social Warfare admin settings page.
*
* This extensive method instantiates each of the five main tabs:
* Display, Styles, Social Identity, Advanced, and Registration.
*
* For each of these tabs all of the core sections and options
* are also created.
*
* Addons, such as Pro, can hook into this object to add
* their own sections and options by using the one of the
*
* @package   SocialWarfare\Functions\Social-Networks
* @copyright Copyright (c) 2018, Warfare Plugins, LLC
* @license   GPL-3.0+
* @since     3.0.0   | Created | 02 MAR 2017
* @access    public
* @todo      Clean out all array bracket notations.
*
*/
class SWP_Options_Page extends SWP_Option_Abstract {
	/**
	* The Options Page Tabs
	*
	* An object holding each of the tabs by index name.
	* The tab is required to be either an SWP_Options_Page_Tab
	* object, or a class which extends this object.
	*
	*/
	public $tabs;


	/**
	* Boolean indicating whether the plugin is registered or not.
	*
	* @var bool $swp_registration
	*
	*/
	public $swp_registration;


	/**
	* The user's selected icons to display.
	*
	* As defined in the Display tab on the settings page.
	*
	*/
	public $icons = array();


	/**
	 * The magic construct method to instatiate the options object.
	 *
	 * This class method provides the framework for the entire options page.
	 * It outlines the chronology of loading order and makes it so that addons
	 * can easily access this object to add their own tabs, sections, and
	 * options as needed prior to the final output of the page and it's HTML.
	 *
	 * @since  3.0.0 | 01 MAR 2018 | Created
	 * @param  void
	 * @return object $this The options page object. Allows for method chaining.
	 *
	 */
	public function __construct() {
		// Create a 'tabs' object to which we can begin adding tabs.
		$this->tabs = new stdClass();


		/**
		 * STEP #1: We create the initial options object immediately when
		 * this class is loaded which takes place while WordPress is loading
		 * all of the installed plugins on the site.
		 *
		 */
		$this->init_display_tab()
			->init_styles_tab()
			->init_social_tab()
			->init_advanced_tab();

		add_action('wp_loaded', [$this, 'load_deferred_options']);


		/**
		 * STEP #2: Addons can now access this object to add their own
		 * tabs, sections, and options prior to the page being rendered.
		 * They will need to use the 'plugins_loaded' hook to ensure that
		 * the first step above has already occurred.
		 *
		 */


		/**
		 * STEP #3: We take the final options object and render the
		 * options page and it's necessary HTML. We defer this step until
		 * much later using the admin_menu hook to ensure that all addons
		 * have had an opportunity to modify the options object as needed.
		 *
		 */
		add_action( 'admin_menu', array( $this, 'options_page') );


		// Checks the URL for a new access_token.
		SWP_Credential_Helper::options_page_scan_url();

		add_action( 'wp_ajax_swp_delete_network_tokens', array( $this, 'delete_network_tokens' ) );
	}


	/**
	 * Load Deferred Options
	 *
	 * The buttons position table needs deferred via a hook. This method
	 * takes care of that.
	 *
	 * @since  3.0.0 | 01 MAR 2018 | Created
	 * @param  void
	 * @return void
	 */
	public function load_deferred_options() {
		$this->tabs->display->sections->button_position->options->button_position_table->do_button_position_table();

		$authorizations  = $this->establish_authorizations();
		if ( count( $authorizations ) > 0 ) {

			$auths = new SWP_Options_Page_Section( __( 'Social Network Connections', 'social-warfare' ), 'addon_authorizations' );
			$auths->set_priority( 20 );

				foreach( $authorizations as $auth ) {
					$auths->add_option( $auth );
				}

			$this->tabs->social_identity->add_section( $auths );
		}
	}


	/**
	* Create the admin menu options page
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return void
	*
	*/
	public function options_page() {

		// Declare the menu link
		$swp_menu = add_menu_page(
			'Social Warfare',
			'Social Warfare',
			'manage_options',
			'social-warfare',
			array( $this, 'render_HTML'),
			SWP_PLUGIN_URL . '/assets/images/admin-options-page/socialwarfare-20x20.png'
		);

		// Hook into the CSS and Javascript Enqueue process for this specific page
		add_action( 'admin_print_styles-' . $swp_menu, array( $this, 'admin_css' ) );
		add_action( 'admin_print_scripts-' . $swp_menu, array( $this, 'admin_js' ) );
	}


	/**
	* Add a tab to the Options Page object.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param object $tab The SWP_Options_Page_Tab object to add.
	* @return object $this The calling instance, for method chaining.
	*
	*/
	public function add_tab( $tab ) {
		$class = get_class( $tab );
		if ( !( $class === 'SWP_Options_Page_Tab' || is_subclass_of( $class, 'SWP_Options_Page_Tab' ) ) ) :
			$this->_throw( 'Requires an instance of SWP_Options_Page_Tab or a class which inherits this class.' );
		endif;

		if ( empty( $tab->name ) ):
			$this->_throw( 'Tab name can not be empty.' );
		endif;

		$this->tabs[$tab->name] = $tab;

		return $this;
	}


	/**
	* Enqueue the Settings Page CSS & Javascript
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @see    $this->options_page()
	* @param  void
	* @return void
	*
	*/
	public function admin_css() {
		$suffix = SWP_Script::get_suffix();
		$wp_scripts = wp_scripts();

		wp_enqueue_style(
			'swp-admin-ui-css',
			'//code.jquery.com/ui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.min.css',
			false,
			SWP_VERSION,
			false
		);

		wp_enqueue_style(
			'swp_admin_options_css',
			SWP_PLUGIN_URL . "/assets/css/admin-options-page{$suffix}.css",
			array(),
			SWP_VERSION
		);

		wp_enqueue_style(
			'social_warfare_admin_mobile',
			SWP_PLUGIN_URL . "/assets/css/admin-mobile{$suffix}.css",
			array(),
			SWP_VERSION
		);
	}


	/**
	* Enqueue the admin javascript
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @see $this->options_page()
	* @param  void
	* @return void
	* @todo: Are we still using the tooltip for anything?
	*
	*/
	public function admin_js() {
		$suffix = SWP_Script::get_suffix();

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-effects-core' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-tooltip' );
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'jquery-ui-position' );
		wp_enqueue_media();
		wp_enqueue_script(
			'swp_admin_options_js',
			SWP_PLUGIN_URL . "/assets/js/admin-options-page{$suffix}.js",
			array( 'jquery', 'social_warfare_script' ),
			SWP_VERSION
		);

		wp_localize_script( 'swp_admin_options_js', 'swpAdminOptionsData', array(
			'registerNonce' => wp_create_nonce( 'swp_plugin_registration' ),
			'optionsNonce'  => wp_create_nonce( 'swp_plugin_options_save' ),
		));
	}


	/**
	* Creates the commonly used color choides for choice settings.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return array The key/value pairs of color choides.
	*
	*/
	public static function get_color_choices_array() {
		return [
			'full_color'            => __( 'Full Color', 'social-warfare' ),
			'light_gray'            => __( 'Light Gray', 'social-warfare' ),
			'medium_gray'           => __( 'Medium Gray', 'social-warfare' ),
			'dark_gray'             => __( 'Dark Gray', 'social-warfare' ),
			'light_gray_outlines'   => __( 'Light Gray Outlines', 'social-warfare' ),
			'medium_gray_outlines'  => __( 'Medium Gray Outlines', 'social-warfare' ),
			'dark_gray_outlines'    => __( 'Dark Gray Outlines', 'social-warfare' ),
			'color_outlines'        => __( 'Color Outlines', 'social-warfare' ),
			'custom_color'          => __( 'Custom Color', 'social-warfare' ),
			'custom_color_outlines' => __( 'Custom Color Outlines', 'social-warfare' )
		];
	}


	/**
	* Calls rendering methods to assemble HTML for the Admin Settings page.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return object $this The calling object for method chaining.
	*
	*/
	public function render_HTML() {
		//* Fetch all the addons the user has installed,
		//* whether or not they are actively registered.
		$addons = apply_filters( 'swp_registrations', array() );
		$this->is_registered = 0;
		$addon_templates = array();
		$active_addons = '';
		$registered_addons = '';

		if ( !empty( $addons ) ) :

			foreach( $addons as $addon ) {
				if ( gettype($addon) !== 'object' ) :
					continue;
				endif;


				$addon_templates[] = new SWP_Registration_Tab_Template( $addon );
				$active_addons .= " $addon->key ";

				if ( true === $addon->is_registered ) :
					$registered_addons .= " $addon->key ";
					$this->is_registered = 1;
				endif;
			}

		endif;

		$this->init_registration_tab( $addon_templates );
		$menu = $this->create_menu( $addon_templates );
		$tabs = $this->create_tabs( $active_addons, $registered_addons );

		$html = $menu . $tabs;
		$this->html = $html;

		echo $html;

		return $this;
	}


	/**
	* Handwritten list of OG Meta types.
	*
	* @since  3.0.0  | 01 MAR 2018 | Created
	* @since  3.1.0 | 13 JUN 2018 | Replaced array bracket notation.
	* @param  void
	* @return array Custom Post Types.
	*
	*/
	protected function get_og_post_types() {
		return array(
			'article',
			'book',
			'books.author',
			'books.book',
			'books.genre',
			'business.business',
			'fitness.course',
			'game.achievement',
			'music.album',
			'music.playlist',
			'music.radio_station',
			'music.song',
			'place',
			'product',
			'product.group',
			'product.item',
			'profile',
			'restaurant.menu',
			'restaurant.menu_item',
			'restaurant.menu_section',
			'restaurant.restaurant',
			'video.episode',
			'video.movie',
			'video.other',
			'video.tv_show',
		);
	}


	/**
	* Provides the common placement choices for the buttons.
	*
	* @since  3.0.0  | 01 MAR 2018 | Created
	* @since  3.1.0 | 13 JUN 2018 | Replaced Array bracket notation.
	* @param  void
	* @return array Key/Value pairs of button placement options.
	*
	*/
	protected function get_static_options_array() {
		return array(
			'above' => __( 'Above the Content', 'social-warfare' ),
			'below' => __( 'Below the Content', 'social-warfare' ),
			'both'  => __( 'Both Above and Below the Content', 'social-warfare' ),
			'none'  => __( 'None/Manual Placement', 'social-warfare' )
		);
	}


	/**
	* Create the Advanced section of the display tab.
	*
	* This section offers miscellaneous advanced settings for finer control of the plugin.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return object $this The calling object for method chaining.
	*
	*/
	protected function init_advanced_tab() {

		$advanced = new SWP_Options_Page_Tab( __( 'Advanced', 'social-warfare' ), 'advanced' );
		$advanced->set_priority( 40 );

		$gutenberg = new SWP_Options_Page_Section( __( 'Gutenberg Blocks<sup>beta</sup>', 'social-warfare' ), 'gutenberg' );
		$gutenberg->set_priority( 5 )
			->set_description( 'If you want to try Social Warfare blocks with Gutenberg, turn this on. <b>Tested with: WP Core 4.9, WP Core 5.1</b>. <em><br/>We are keeping up the best we can, but Gutenberg development is very rapid and can break our blocks overnight. <br/>If this happens, please turn this setting OFF. Your shortcodes will stay in place.</em>' );
			// ->set_information_link( 'https://warfareplugins.com/support/using-shortcodes-and-php-snippets/' );

			$gutenberg_switch = new SWP_Option_Toggle( __( 'Enable Gutenberg Blocks'), 'gutenberg_switch' );
			$gutenberg_switch->set_default( true )
				->set_size( 'sw-col-300' );

			$gutenberg->add_option( $gutenberg_switch );

		$frame_buster = new SWP_Options_Page_Section( __( 'Frame Buster', 'social-warfare' ), 'frame_buster' );
		$frame_buster->set_priority( 10 )
			->set_description( __( 'If you want to stop content pirates from framing your content, turn this on.', 'social-warfare' ) )
			->set_information_link( 'https://warfareplugins.com/support/options-page-advanced-tab-frame-buster/');

			//* sniplyBuster => frame_buster
			$frame_buster_toggle = new SWP_Option_Toggle( __( 'Frame Buster', 'social-warfare' ), 'frame_buster' );
			$frame_buster_toggle->set_default( true )
				->set_size( 'sw-col-300' );

			$frame_buster->add_option( $frame_buster_toggle );

		$full_content = new SWP_Options_Page_Section( __( 'Full Content vs. Excerpts', 'social-warfare' ), 'full_content' );
		$full_content->set_priority( 70 )
			 ->set_description( __( 'If your theme does not use excerpts, but instead displays the full post content on archive, category, and home pages, activate this toggle to allow the buttons to appear in those areas.', 'social-warfare' ) )
			 ->set_information_link( 'https://warfareplugins.com/support/options-page-advanced-tab-full-content-vs-excerpts/' );

			$full_content_toggle = new SWP_Option_Toggle( __( 'Full Content?', 'social-warfare' ), 'full_content' );
			$full_content_toggle->set_default( false )
				->set_size( 'sw-col-300' );

			$full_content->add_option( $full_content_toggle );

		$advanced->add_sections( [$gutenberg, $frame_buster, $full_content] );

		$this->tabs->advanced = $advanced;

		return $this;
	}


	/**
	* Create the Display section and its child options.
	*
	* This tab offers genereral layout setings for the front end of the site.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return object $this The calling object for method chaining.
	*
	*/
	protected function init_display_tab() {
		$display = new SWP_Options_Page_Tab( __( 'Display', 'social-warfare' ), 'display' );
		$display->set_priority( 10 );

			$social_networks = new SWP_Options_Page_Section( __( 'Social Networks', 'social-warfare' ), 'social_networks' );
			$social_networks->set_priority( 10 )
				->set_description( __( 'Drag & Drop to activate and order your share buttons.', 'social-warfare' ) )
				->set_information_link( 'https://warfareplugins.com/support/options-page-display-tab-social-networks/' );

				//* These two sections are unique and need special HTML.
				$active = new SWP_Option_Icons( __( 'Active', 'social-warfare' ), 'active' );
				$active->do_active_icons()->set_priority( 10 );

				$inactive = new SWP_Option_Icons( __( 'Inactive', 'social-warfare' ), 'inactive' );
				$inactive->do_inactive_icons()->set_priority( 20 );

				$social_networks->add_options( [$active, $inactive] );

			$share_counts = new SWP_Options_Page_Section( __( 'Share Counts', 'social-warfare' ), 'share_counts' );
			$share_counts->set_description( __( 'Use the toggles below to determine how to display your social proof.', 'social-warfare' ) )
				->set_priority( 20 )
				->set_information_link( 'https://warfareplugins.com/support/options-page-display-tab-share-counts/' );

				//* toteseach => network_count
				$network_shares = new SWP_Option_Toggle( __( 'Button Counts', 'social-warfare' ), 'network_shares' );
				$network_shares->set_default( true )
					->set_priority( 10 )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit' );

				//* totes => totals
				$total_shares = new SWP_Option_Toggle( __( 'Total Counts', 'social-warfare' ), 'total_shares' );
				$total_shares->set_default( true )
					->set_priority( 20 )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit' );

				//* swDecimals => decimals
				$decimals = new SWP_Option_Select( __( 'Decimal Places', 'social-warfare' ), 'decimals' );
				$decimals->set_choices( [
					'0' => 'Zero',
					'1' => 'One',
					'2' => 'Two',
				])
					->set_default( '0' )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit' )
					->set_priority( 30 );

				//* swp_decimal_separator => decimal_separator
				$decimal_separator = new SWP_Option_Select( __( 'Decimal Separator', 'social-warfare' ), 'decimal_separator' );
				$decimal_separator->set_choices( [
					'period'    => 'Period',
					'comma'     => 'Comma',
				])
					->set_default( 'period' )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit' )
					->set_priority( 50 );

				//* swTotesFormat => totals_alignment
				$totals_alignment = new SWP_Option_Select( __( 'Alignment', 'social-warfare' ), 'totals_alignment' );
				$totals_alignment->set_choices( [
					'totals_right'  => 'Right',
					'totals_left'   => 'Left'
				])
					->set_default( 'totals_right' )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit' )
					->set_priority( 60 );

			$share_counts->add_options( [$network_shares, $total_shares, $decimals, $decimal_separator, $totals_alignment] );

			$button_position = new SWP_Options_Page_Section( __( 'Position Share Buttons', 'social-warfare' ), 'button_position' );
			$button_position->set_description( __( 'These settings let you decide where the share buttons should go for each post type.', 'social-warfare' ) )
				->set_priority( 40 )
				->set_information_link( 'https://warfareplugins.com/support/options-page-display-tab-position-share-buttons/' );

				$button_position_table = new SWP_Section_HTML( __( 'Position Table', 'social-warfare' ), 'button_position_table' );
				// $button_position_table->do_button_position_table();

			$button_position->add_option( $button_position_table );



		$display->add_sections( [$social_networks, $share_counts, $button_position] );

		$this->tabs->display = $display;

		return $this;
	}


	/**
	* Create the Registration section of the display tab.
	*
	* This section allows users to register activation keys for the premium plugin features.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return object $this The calling object for method chaining.
	*
	*/
	protected function init_registration_tab( $addons ) {
		$registration = new SWP_Options_Page_Tab( __( 'Registration', 'social-warfare' ), 'registration' );


		$registration->set_priority( 50 );

			$wrap = new SWP_Options_Page_Section( __( 'Addon Registrations', 'social-warfare' ), 'addon_registrations' );
			$wrap->set_priority( 10 );

				foreach( $addons as $addon ) {
					$wrap->add_option( $addon );
				}

		$registration->add_section( $wrap );

		$this->tabs->registration = $registration;

		return $this;
	}


	/**
	 *
	 * @since 3.5.0 | 03 JAN 2018 | Created.
	 * @param void
	 * @hook filter | swp_authorizations | Array of network keys requesting oAuth access.
	 * @return array $authorization_options The qualified SWP_Options ready to render_HTML().
	 *
	 */
	public function establish_authorizations() {
		$authorizations = apply_filters( 'swp_authorizations', array() );
		$authorization_options = array();

		// Set up an 'Authorize' or 'Disconnect' button for social network oAuth.
		foreach ( $authorizations as $network_key) {

			$instance = new SWP_Auth_Helper ( $network_key );
			$access_token = $instance->get_access_token();

			// No access tokens exists for this network.
			if ( false == $access_token ) {
				$link = $instance->get_authorization_link();
				$display_text = $instance->get_auth_button_text();
				$classname = "swp-button swp-{$network_key} swp-authorization-button";
				$option = new SWP_Option_Button( $display_text, $network_key, $classname, $link );

			}

			// Provide the option to revoke the connection.
			else {
				$link = $instance->get_revoke_access_url();
				$display_text = 'Disconnect ' . ucfirst ( $network_key );

				/**
				 * JavaScript needs to delete the tokens when this button is clicked.
				 * The SWP_Option_Button does not provide access for data-attributes,
				 * so we'll hack together a CSS classname to parse in JS.
				 *
				 */
				$js_class = 'swp-network-'.$network_key;
				$class = "button sw-navy-button swp-revoke-button $js_class";
				$option = new SWP_Option_Button( $display_text, $network_key, $class, $link, true );

			}

			$option->set_size( 'sw-col-300' );
			$authorization_options[$network_key] = $option;

		}


		return $authorization_options;
	}


	/**
	* Create the Social Identity section of the display tab.
	*
	* This section allows the user to set social network handles and OG metadata.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return SWP_Options_Page $this The calling object for method chaining.
	*
	*/
	protected function init_social_tab() {
		$social_identity = new SWP_Options_Page_Tab( __( 'Social Identity', 'social-warfare' ), 'social_identity' );
		$social_identity->set_priority( 30 );

		$sitewide_identity = new SWP_Options_Page_Section( 'Sitewide Identity', 'sitewide_identity' );
		$sitewide_identity->set_description( __( 'If you would like to set sitewide defaults for your social identity, add them below.', 'social-warfare' ) )
			->set_information_link( 'https://warfareplugins.com/support/options-page-social-identity-tab-sitewide-identity/' );

			$twitter_id = new SWP_Option_Text( __( 'Twitter Username', 'social-warfare' ), 'twitter_id' );
			$twitter_id->set_size( 'sw-col-300' )
				->set_priority( 10 )
				->set_default( '' );

			//* pinterestID => pinterest_id
			$pinterest_id = new SWP_Option_Text( __( 'Pinterest Username', 'social-warfare' ), 'pinterest_id' );
			$pinterest_id->set_size( 'sw-col-300' )
				->set_priority( 20 )
				->set_default( '' );

			//* facebookPublisherUrl => facebook_publisher_url
			$facebook_publisher_url = new SWP_Option_Text( __( 'Facebook Page URL', 'social-warfare' ), 'facebook_publisher_url' );
			$facebook_publisher_url->set_size( 'sw-col-300' )
				->set_priority( 30 )
				->set_default( '' );

			//* facebookAppID => facebook_app_id
			$facebook_app_id = new SWP_Option_Text( __( 'Facebook App ID', 'social-warfare' ), 'facebook_app_id' );
			$facebook_app_id->set_size( 'sw-col-300' )
				->set_priority( 40 )
				->set_default( '' );

		$sitewide_identity->add_options( [$twitter_id, $pinterest_id, $facebook_publisher_url, $facebook_app_id] );
		$social_identity->add_section( $sitewide_identity );

		$this->tabs->social_identity = $social_identity;

		return $this;
	}


	/**
	* Create the Styles section of the display tab.
	*
	* This section allows the user to refine the look, feel, and placement of buttons.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return SWP_Options_Page $this The calling object for method chaining.
	*
	*/
	protected function init_styles_tab() {
		$styles = new SWP_Options_Page_Tab( __( 'Styles' , 'social-warfare' ) , 'styles' );
		$styles->set_priority( 20 );

			$buttons_preview = new SWP_Section_HTML( __( 'Buttons Preview', 'social-warfare' ) );
			$buttons_preview->set_priority( 1000 )
				->do_buttons_preview();


			$buttons_preview_section = new SWP_Options_Page_Section( __( 'Buttons Preview', 'social-warfare' ), 'buttons_preview_section' );
			$buttons_preview_section->add_option( $buttons_preview );

			$styles->add_section( $buttons_preview_section );
			//
			//
			// $total_counts = new SWP_Options_Page_Section( __( 'Total Counts', 'social-warfare' ), 'total_counts' );
			// $total_counts->set_description( __( 'Customize how the "Total Shares" section of your share buttons look.', 'social-warfare' ) )
			//     ->set_priority( 20 )
			//     ->set_information_link( 'https://warfareplugins.com/support/options-page-styles-tab-total-counts/' );



			// $total_counts->add_options( [$decimals, $decimal_separator, $totals_alignment] );

			$floating_share_buttons = new SWP_Options_Page_Section( __( 'Floating Share Buttons', 'social-warfare' ), 'floating_share_buttons' );
			$floating_share_buttons->set_description( __( 'If you would like to activate floating share buttons, turn this on.', 'social-warfare' ) )
				->set_priority( 30 )
				->set_information_link( 'https://warfareplugins.com/support/options-page-styles-tab-floating-share-buttons/' );

				//* float => floating_panel
				$floating_panel = new SWP_Option_Toggle( __( 'Floating Share Buttons', 'social-warfare' ), 'floating_panel' );
				$floating_panel->set_default( false )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit')
					->set_priority( 10 );

				//* floatOption => float_location
				$float_location = new SWP_Option_Select( __( 'Float Position', 'social-warfare' ), 'float_location' );
				$float_location->set_choices( [
					'top'    => __( 'Top of the Page' , 'social-warfare' ),
					'bottom' => __( 'Bottom of the Page' , 'social-warfare' ),
					'left'   => __( 'On the left side of the page' , 'social-warfare' ),
					'right'  => __( 'On the right side of the page' , 'social-warfare' )
					] )
					->set_default( 'bottom' )
					->set_priority( 20 )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit')
					->set_dependency( 'floating_panel', [true] );

				//* floatBgColor => float_background_color
				$float_background_color = new SWP_Option_Text( __( 'Background Color', 'social-warfare' ), 'float_background_color' );
				$float_background_color->set_default( '#ffffff' )
					->set_priority( 25 )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit' )
					->set_dependency( 'float_location', ['top', 'bottom'] );

				//* swp_float_scr_sz => float_screen_width
				$float_screen_width = new SWP_Option_Text( __( 'Minimum Screen Width', 'social-warfare' ), 'float_screen_width' );
				$float_screen_width->set_default( '1100' )
					->set_priority( 30 )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit' )
					->set_dependency( 'float_location', ['left', 'right'] );

				//* sideReveal => transition
				$float_transition = new SWP_Option_Select( __( 'Transition', 'social-warfare' ), 'transition' );
				$float_transition->set_priority( 40 )
					->set_choices( [
						'slide' => __( 'Slide In / Slide Out' , 'social-warfare' ) ,
						'fade'  => __( 'Fade In / Fade Out' , 'social-warfare' )
					] )
					->set_default( 'slide' )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit')
					->set_dependency( 'float_location', ['left', 'right'] );

				$color_choices = $this::get_color_choices_array();

				$float_before_content = new SWP_Option_Toggle( __( 'Float Before Content', 'social-warfare' ), 'float_before_content');
				$float_before_content->set_default( false )
					->set_priority( 140 )
					->set_size( 'sw-col-460', 'sw-col-460 sw-fit')
					->set_dependency( 'floating_panel', true );

				$floating_share_buttons->add_options( [$floating_panel, $float_location, $float_transition,
					$float_screen_width, $float_background_color, $float_before_content] );



		$styles->add_sections( [/*$total_counts,*/ $floating_share_buttons] );

		$this->tabs->styles = $styles;

		return $this;
	}


	/**
	* Creates the HTML for the admin top menu (Logo, tabs, and save button).
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return string $html The fully qualified HTML for the menu.
	*
	*/
	private function create_menu( $addons ) {
		//* Open the admin top menu wrapper.
		$html = '<div class="sw-header-wrapper">';
			$html .= '<div class="sw-grid sw-col-940 sw-top-menu" sw-registered="' . $this->is_registered . '">';

				//* Menu wrapper and tabs.
				$html .= '<div class="sw-grid sw-col-700">';
					$html .= '<img class="sw-header-logo" src="' . SWP_PLUGIN_URL . '/assets/images/admin-options-page/social-warfare-light.png" />';
					$html .= '<img class="sw-header-logo-pro" src="' . SWP_PLUGIN_URL . '/assets/images/admin-options-page/social-warfare-pro-light.png" />';
					$html .= '<ul class="sw-header-menu">';

					$tab_map = $this->sort_by_priority( $this->tabs );

					$activated = true;

					foreach( $tab_map as $prioritized_tab) {
						foreach( $this->tabs as $index => $tab ) {

							if ( $prioritized_tab['key'] === $tab->key ) :

								//* Skip the registration tab if there are no addons.
								if ( 'registration' == $tab->key && 0 === count( $addons ) ) :
									continue;
								endif;

								$active = $activated ? 'sw-active-tab' : '';
								$activated = false;

								$html .= '<li class="' . $active . '">';
									$html .= '<a class="sw-tab-selector" href="#" data-link="swp_' . $tab->link . '">';
										$html .= '<span>' . $tab->name . '</span>';
									$html .= '</a>';
								$html .= '</li>';

							endif;
						}
					}

					$html .= '</ul>';
				$html .= '</div>';

				//* "Save Changes" button.
				$html .= '<div class="sw-grid sw-col-220 sw-fit">';
				$html .= '<a href="#" class="button sw-navy-button sw-save-settings">'. __( 'Save Changes' , 'social-warfare' ) .'</a>';
				$html .= '</div>';

				$html .= '<div class="sw-clearfix"></div>';

			$html .= '</div>';
		$html .= '</div>';

		return $html;
	}


	/**
	* Renders HTML for each tab and assembles for outputting.
	*
	* Note: We have to utilize a $map varaible for this and each
	* other render() method. This is because the data are all
	* stored as objects, when can not be iterated by index,
	* only by key. Since they keys are arbitrary (for a plugin
	* or addon, for example), this is no good, hence the map.
	*
	* @since  3.0.0 | 01 MAR 2018 | Created
	* @param  void
	* @return string $container The Admin tab HTML container.
	*
	*/
	private function create_tabs( $active_addons, $registered_addons ) {
		$sidebar = new SWP_Section_HTML( 'Sidebar' );
		$tab_map = $this->sort_by_priority( $this->tabs );
		$registered = false;
		$notices = apply_filters( 'swp_admin_notices', '' );

		$container = '<div class="sw-admin-wrapper" sw-registered="'. $this->is_registered .'" swp-addons="' . $active_addons . '" swp-registrations="' . $registered_addons . '">';
			$container .= '<div class="swp-notice-wrapper">';
				$container .= $notices;
			$container .= '</div>';

			$container .= '<form class="sw-admin-settings-form">';
				$container .= '<div class="sw-tabs-container sw-grid sw-col-700">';

				foreach( $tab_map as $prioritized_tab ) {
					$key = $prioritized_tab['key'];

					foreach( $this->tabs as $tab ) {
						if ( $key === $tab->key ) :

							if ( 'registration' === $key ) :
								$container .= $tab->render_HTML( $registered_addons );
								continue;
							endif;

							$container .= $tab->render_HTML();

						endif;
					}
				}

				$container .= '</div>';
			$container .= '</form>';
			$container .= $sidebar->do_admin_sidebar();

		$container .= '</div>';

		return $container;
	}


	/**
	 * Called when a 'Revoke Access' button is clicked on the Social Identity tab.
	 *
	 * @since 3.5.0 | 25 JAN 2019 | Created.
	 * @param void
	 * @return void
	 *
	 */
	public function delete_network_tokens() {
		$network = $_POST['network'];
		$response = array('ok' => false);
		$response['ok'] = SWP_Credential_Helper::delete_token($network);
		SWP_Credential_Helper::delete_token($network, 'access_secret');

		if ( $response['ok'] ) {
			$response['url'] = SWP_Utility::settings_page_redirect();
		}

		die(json_encode($response));

	}
}
