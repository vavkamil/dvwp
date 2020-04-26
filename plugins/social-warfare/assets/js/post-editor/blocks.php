<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// @since 3.4.2 | 10 DEC 2018 | Changed function check from 'is_gutenberg_page' to 'register_block_type'
if ( true === SWP_Utility::get_option( 'gutenberg_switch' ) && function_exists( 'register_block_type' ) ) {
	add_action( 'init', 'register_gutenberg_blocks' );
	add_filter( 'block_categories', 'add_block_category', 10, 2 );
}

/**
 * Block Initializer. Registers and loads the CSS and JS for
 * Gutenberg blocks in the post editor.
 *
 * @since 3.4.0 | 26 NOV 2018 | Created.
 * @since 3.4.2 | 10 DEC 2018 | Removed dependencies from wp_register_style
 */
 function register_gutenberg_blocks() {
 	wp_register_style(
 		'social-warfare-block-css',
 		plugins_url( '/post-editor/dist/blocks.style.build.css', dirname( __FILE__ ) )
 	);

	wp_register_script(
		'social-warfare-block-js',
		plugins_url( '/post-editor/dist/blocks.build.js', dirname( __FILE__ ) ),
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor' ),
		true
	);

	wp_enqueue_style('social-warfare-block-css');

	//* All of our block scripts are compiled to a single, common file.
	$scripts = array(
	   'editor_script' => 'social-warfare-block-js',
	   'block_script'	=> 'social-warfare-block-js'
   );

	register_block_type( 'social-warfare/social-warfare', $scripts);
	register_block_type( 'social-warfare/click-to-tweet', $scripts);
	register_block_type( 'social-warfare/pinterest-image', $scripts);
 }

/**
 * Create the custom Social Warfare category for Gutenberg blocks.
 *
 * @param array $categories The registerd Gutenberg categories.
 * @param Object $post The WP post being edited, to optionally conditionally load blocks.
 * @since 3.4.0 | 26 NOV 2018 | Created.
 */
 function add_block_category( $categories, $post ) {
     return array_merge(
         $categories,
         array(
             array(
                 'slug' => 'social-warfare',
                 'title' => __( 'Social Warfare', 'social-warfare' ),
                 'icon'  => '<i className="mce-ico mce-i-sw sw sw-social-warfare" />',
             ),
         )
     );
 }
