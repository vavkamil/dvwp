<?php
/**
 * Plugin Name: Social Warfare
 * Plugin URI:  https://warfareplugins.com
 * Description: A plugin to maximize social shares and drive more traffic using the fastest and most intelligent share buttons on the market, calls to action via in-post click-to-tweets, popular posts widgets based on share popularity, link-shortening, Google Analytics and much, much more!
 * Version:     3.5.2
 * Author:      Warfare Plugins
 * Author URI:  https://warfareplugins.com
 * Text Domain: social-warfare
 *
 */

defined( 'WPINC' ) || die;

/**
 * Define plugin constants for use throughout the plugin (Version and Directories)
 *
 */
define( 'SWP_VERSION' , '3.5.2' );
define( 'SWP_PLUGIN_FILE', __FILE__ );
define( 'SWP_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SWP_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'SWP_STORE_URL', 'https://warfareplugins.com' );

/**
 * Legacy version of the update cheker.
 *
 * @since  3.0.6 | 15 MAY 2018 | Added the requirement statement for legacy support.
 * @TODO   THis should be removed after 31 DEC 2018.
 *
 */
if ( file_exists( SWP_PLUGIN_DIR . '/lib/legacy/update-checker.php') ) :
	require_once SWP_PLUGIN_DIR . '/lib/legacy/update-checker.php';
endif;
add_filter('the_excerpt', 'do_shortcode', 1);

// Load the main Social_Warfare class and fire up the plugin.
require_once SWP_PLUGIN_DIR . '/lib/Social_Warfare.php';
new Social_Warfare();
