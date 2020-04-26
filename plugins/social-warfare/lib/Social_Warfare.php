<?php

/**
 * A class of functions used to load the plugin files and functions
 *
 * This is the class that brings the entire plugin to life. It is used to
 * instatiate all other classes throughout the plugin.
 *
 * This class also serves as a table of contents for all of the plugin's
 * functionality. By browsing below, you will see a brief description of each
 * class that is being instantiated.
 *
 * @package   SocialWarfare\Utilities
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     3.0.0  | 19 FEB 2018 | Created
 * @since     3.1.0 | 20 JUN 2018 | Added instantiate_frontend_classes()
 *
 */
class Social_Warfare {

	/**
	 * The magic method used to instantiate this class.
	 *
	 * This method will load all of the classes using the "require_once" command.
	 * It will then instantiate them all one by one.
	 *
	 * @since  3.0.0  | 19 FEB 2018 | Created
	 * @since  3.1.0 | 20 JUN 2018 | Added instantiate_frontend_classes()
	 * @param  void
	 * @return void
	 * @access public
	 *
	 */
	public function __construct() {
		$this->core_version = SWP_VERSION;
		require_once SWP_PLUGIN_DIR . '/lib/utilities/functions.php';
		add_action('plugins_loaded', array($this, 'init'));
	}

	public function init() {
		// Loads the files for each class.
		$this->load_classes();

		// Instantiate all the core classes
		$this->instantiate_classes();

		// Instantiate the admin-only classes.
		if( true === is_admin() ) {
			$this->instantiate_admin_classes();
		}

		// Instatiate classes that need to be defered.
		add_action('plugins_loaded' , array( $this, 'instantiate_deferred_classes' ) , 100 );
		require_once SWP_PLUGIN_DIR . '/assets/js/post-editor/blocks.php';
	}


	/**
	 * The method used to instantiate all classes used on both frontend and admin.
	 *
	 * This method will instantiate every class throughout the plugin except for
	 * those classes that are used in both the frontend and the admin area.
	 *
	 * @since  3.0.0
	 * @param  void
	 * @return void
	 * @access public
	 *
	 */
	private function instantiate_classes() {


		/**
		 * The Global $swp_user_options Loader
		 *
		 * This creates and filters and manages the user options array.
		 *
		 */
		new SWP_User_Options();


		/**
		 * The Global Options Page Object
		 *
		 * This is created as a global so that all addons can modify it.
		 *
		 */
		global $SWP_Options_Page;


		/**
		 * The Social Networks Loader
		 *
		 * Instantiates the class that will load the social networks.
		 *
		 */
		new SWP_Social_Networks_Loader();


		/**
		 * The Localization Class
		 *
		 * Instantiates the class that will load the plugin translations.
		 *
		 */
		$Localization = new SWP_Localization();
		$Localization->init();


		/**
		 * The URL_Management Class
		 *
		 * This is the class that controls short links and UTM parameters.
		 *
		 */
		new SWP_URL_Management();


		/**
		 * The Script Class
		 *
		 * Instantiates the class that will enqueue all of the styles and
		 * scripts used throughout the plugin both frontend, and admin.
		 *
		 */
		new SWP_Script();


		/**
		 * The Shortcode Class
		 *
		 * Instantiate the class that will process all instances of the
		 * click to tweets, total shares, and other shortcodes used in posts and
		 * pages, and consequently convert those shortcodes into their
		 * respective HTML output.
		 *
		 */
		new SWP_Shortcode();


		/**
		 * The Buttons Panel Shortcode Class
		 *
		 * Instantiate the class that will process all instances of the
		 * [social_warfare] shortcode used in posts and pages, and consequently
		 * convert those shortcodes into sets of share buttons.
		 *
		 */
		new SWP_Buttons_Panel_Shortcode();


		/**
		 * The Header Output Class
		 *
		 * Instantiate the class that processes the values and creates the HTML
		 * output required in the <head> section of a website. This includes our
		 * font css, open graph meta tags, and Twitter cards.
		 *
		 */
		new SWP_Header_Output();


		/**
		 * The Buttons Panel Loader
		 *
		 * Instantiates the class that is used to queue up or hook the buttons
		 * generator into WordPress' the_content() hook which allows us to
		 * append our buttons to it.
		 *
		 */
		new SWP_Buttons_Panel_Loader();


		/**
		 * The Compatibility Class
		 *
		 * Instantiate the class that provides solutions to very specific
		 * incompatibilities with certain other plugins.
		 *
		 */
		new SWP_Compatibility();


		/**
		 * The Widget Loader Class
		 *
		 * Instantiate the class that registers and output the "Popular Posts"
		 * widget. If other widgets are added later, this class will fire those
		 * up as well.
		 *
		 */
		new SWP_Widget_Loader();


		/**
		 * Database Migration
		 *
		 * Converts camelCased variable names to the new snake_case option names.
		 *
		 */
		new SWP_Database_Migration();


		/**
		 * The Options Page Class
		 *
		 * Instantiates the class that will load the plugin options page.
		 *
		 */
		$SWP_Options_Page = new SWP_Options_Page();


		/**
		 * The Post Cache Loader Class
		 *
		 * Instantiates a global object that will manage and load cached data
		 * for each individual post on a site allowing access to cached data like
		 * share counts, for example.
		 *
		 */
		global $SWP_Post_Caches;
		$SWP_Post_Caches = new SWP_Post_Cache_Loader();


		/**
		 * The Utility Class
		 *
		 * While the methods are all static functions that do not require
		 * a class instance to use, there are hooks that need to be set up
		 * in the class __construct() method.
		 *
		 */

		new SWP_Utility();

	}


	/**
	 * This method will load up all of the admin-only classes.
	 *
	 * @since  3.0.0
	 * @param  void
	 * @return void
	 * @access public
	 *
	 */
	private function instantiate_admin_classes() {


		/**
		 * The Shortcode Generator
		 *
		 * Instantiate the class that creates the shortcode generator on the
		 * post editor which allows users to generate the [social_warfare]
		 * shortcodes by simply pointing clicking, and filling in a few fill in
		 * the blanks.
		 *
		 */
		new SWP_Shortcode_Generator();


		/**
		 * The Click to Tweet Class
		 *
		 * Instantiate the class that that creates the Click to Tweet button in
		 * the WordPress post editor's dashboard (the kitchen sink) and also
		 * process the shortcode on the front end.
		 *
		 */
		new SWP_Click_To_Tweet();


		/**
		 * The "Social Shares" column in the posts view.
		 *
		 * Instantiate the class that creates the column in the posts view of
		 * the WordPress admin area. This column allows you to see how many
		 * times each post has been shared. It also allows you to sort the
		 * column in ascending or descending order.
		 *
		 */
		new SWP_Column();


		/**
		 * The The Settings Link
		 *
		 * Instantiates the class that addes links to the plugin listing on the
		 * plugins page of the WordPress admin area. This will link to the
		 * Social Warfare options page.
		 *
		 */
		new SWP_Settings_Link();


		/**
		 * The User Profile Fields
		 *
		 * Instantiates the class that adds our custom fields to the user
		 * profile area of the WordPress backend. This allows users to set a
		 * Twitter username and Facebook author URL on a per-user basis. If set,
		 * this will override these same settings from the options page on any
		 * posts authored by that user.
		 *
		 */
		new SWP_User_Profile();


		/**
		 * The JSON Cache Handler
		 *
		 * This class fetches the JSON data from our home server and makes it
		 * available to the plugin for important information like adding
		 * dashboard notices or updating the sidebar in the admin settings page.
		 *
		 */
		new SWP_JSON_Cache_Handler();


		/**
		 * The Settings Page Sidebar Loader
		 *
		 * This class controls the sidebar on the settings page.
		 *
		 */
		new SWP_Sidebar_Loader();

	}


	/**
	 * Instatiate the classes that we want to load in a deferred manner.
	 *
	 * @since  3.3.0 | 06 AUG 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function instantiate_deferred_classes() {
		/**
		 * Instantiates all of our notices.
		 *
		 */
		new SWP_Notice_Loader();
	}


	/**
	 * The method is used to include all of the files needed.
	 *
	 * @since  3.0.0
	 * @param  none
	 * @return none
	 * @access public
	 *
	 */
	private function load_classes() {

		// The Social Warfare core Addon class.
		require_once SWP_PLUGIN_DIR . '/lib/Social_Warfare_Addon.php';

		// WordPress functions for plugin operations.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		/**
		 * Utility Classes
		 *
		 * This loads our Utility Trait and our various classes used to provide general
		 * functionality that will be used by many different classes throughout the plugin.
		 *
		 */
		$utilities = array(
			'Debug_Trait',
			'Compatibility',
			'CURL',
			'Localization',
			'Permalink',
			'Database_Migration',
			'URL_Management',
			'Notice',
			'Notice_Loader',
			'Post_Cache_Loader',
			'Post_Cache',
			'JSON_Cache_Handler',
			'Plugin_Updater',
			'Utility',
			'Auth_Helper',
			'Credential_Helper'
		);
		$this->load_files( '/lib/utilities/', $utilities);


		/**
		 * The Social Network Classes
		 *
		 * This family of classes provides the framework and the model needed
		 * for creating a unique object for each social network. It also
		 * provides for maximum extensibility to allow addons even easier access
		 * than ever before to create and add more social networks to the plugin.
		 *
		 */
		$social_networks = array(
			'Social_Networks_Loader',
			'Social_Network',
			'Google_Plus',
			'Facebook',
			'Twitter',
			'Linkedin',
			'Pinterest',
			'Mix'
		);
		$this->load_files( '/lib/social-networks/', $social_networks);


		/**
		 * The Buttons Panel Classes
		 *
		 * These are the classes used to instantiate and render the buttons
		 * panels across a WordPress site. It also controls the hooks and filters
		 * which get the buttons panels added to them.
		 *
		 */
		$buttons_panels = array(
			'Buttons_Panel_Trait',
			'Buttons_Panel',
			'Buttons_Panel_Side',
			'Buttons_Panel_Loader',
			'Buttons_Panel_Shortcode',
		);
		$this->load_files( '/lib/buttons-panel/', $buttons_panels );


		/**
		 * The Frontend Output Classes
		 *
		 * This family of classes control everything that is output on the
		 * WordPress frontend. This includes the HTML for the buttons panels,
		 * the meta data that is output in the head section of the site, scripts
		 * and styles being enqueued for output, and other things like that.
		 *
		 */
		$frontends = array(
			'Header_Output',
			'Script',
			'Shortcode',
		);
		$this->load_files( '/lib/frontend-output/', $frontends );


		/**
		 * The Widget Classes
		 *
		 * These are the classes that create the widgets available for output in
		 * WordPress.
		 * We include our SWP_Widget, which extends the WP_Widget as required,
		 * but also provides other utility methods specific to our plugin.
		 *
		 * The Popular Posts widget provides users options for displaying posts
		 * by share counts.
		 *
		 * The Widget Loader creates a filter hook for adding more widgets
		 * as addons.
		 *
		 */
		$widgets = array(
			'Popular_Posts_Widget',
			'Widget',
			'Widget_Loader'
		);
		$this->load_files( '/lib/widgets/', $widgets );


		/**
		 * The Admin Classes
		 *
		 * This family of classes power everything that you see in the WordPress
		 * admin area of the site. This includes the Click To Tweet generator
		 * and Social Warfare shortcode generator buttons that you see at the
		 * top of the post editor. These include adding the share count column
		 * to the posts view and a few other things related to the admin area.
		 * This does NOT include the classes used to generate the options page
		 * for Social Warfare.
		 *
		 */
		$admins = array(
			'Click_To_Tweet',
			'Column',
			'Settings_Link',
			'Shortcode_Generator',
			'User_Profile',
			'Sidebar_Loader'
		);
		$this->load_files( '/lib/admin/', $admins );


		/**
		 * The Options Classes
		 *
		 * These classes provide the framework that creates the admin options
		 * page as well as the tools needed for addons to be able to interface
		 * with it to add their own options.
		 *
		 */
		$options = array(
			'User_Options',
			'Option_Abstract',
			'Option',
			'Options_Page',
			'Options_Page_Tab',
			'Options_Page_Section',
			'Option_Toggle',
			'Option_Select',
			'Option_Text',
			'Option_Textarea',
			'Section_HTML',
			'Option_Icons',
			'Registration_Tab_Template',
			'Option_Button'
		);
		$this->load_files( '/lib/options/', $options );


		/**
		 * The Update Checker
		 *
		 * This loads the class which will in turn load all other class that are
		 * needed in order to properly check for updates for addons.
		 *
		 */
		require_once SWP_PLUGIN_DIR . '/lib/update-checker/plugin-update-checker.php';


	}


	/**
	 * Loads an array of related files.
	 *
	 * @param  string   $path  The relative path to the files home.
	 * @param  array    $files The name of the files (classes), no vendor prefix.
	 * @return none     The files are loaded into memory.
	 *
	 */
	private function load_files( $path, $files ) {
		foreach( $files as $file ) {

			//* Add our vendor prefix to the file name.
			$file = "SWP_" . $file;
			require_once SWP_PLUGIN_DIR . $path . $file . '.php';
		}
	}


	/**
	 *
	 * When we have known incompatability with other themes/plugins,
	 * we can put those checks in here.
	 *
	 * Checks for known conflicts with other plugins and themes.
	 *
	 * If there is a fatal conflict, returns true and exits printing.
	 * If there are other conflicts, they are silently handled and can still
	 * print.
	 *
	 * @since  3.0.0 | 01 MAR 2018 | Created
	 * @since  3.3.0 | 30 AUG 2018 | Moved from SWP_Buttons_Panel to Social_Warfare.
	 * @param  void
	 *
	 * @return bool True iff the conflict is fatal, else false.
	 *
	 */
	public static function has_plugin_conflict() {

		// Disable subtitles plugin to prevent it from injecting subtitles
		// into our share titles.
		if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'subtitles/subtitles.php' ) && class_exists( 'Subtitles' ) ) :
			remove_filter( 'the_title', array( Subtitles::getinstance(), 'the_subtitle' ), 10, 2 );
		endif;

		//* Disable on BuddyPress pages.
		if ( function_exists( 'is_buddypress' ) && is_buddypress() ) :
			return true;
		endif;

		return false;
	}
}

/**
 * Include the plugin's admin files.
 *
 */
if ( is_admin() ) {
	require_once SWP_PLUGIN_DIR . '/lib/admin/swp_system_checker.php';
}
