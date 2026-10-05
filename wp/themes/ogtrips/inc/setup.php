<?php
/**
 * Theme setup: supports, menus, image sizes, translations.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers theme features. Runs after the parent's setup (priority 20) so ours win.
 */
function ogtrips_setup() {
	load_child_theme_textdomain( 'ogtrips', get_stylesheet_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'html5',
		[ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ]
	);

	register_nav_menus(
		[
			'primary'         => __( 'Main menu', 'ogtrips' ),
			'footer-explore'  => __( 'Footer — Explore', 'ogtrips' ),
			'footer-support'  => __( 'Footer — Support', 'ogtrips' ),
		]
	);

	add_image_size( 'ogt-hero', 2200, 0 );
	add_image_size( 'ogt-card', 900, 0 );
	add_image_size( 'ogt-thumb', 500, 0 );
	add_image_size( 'ogt-avatar', 120, 120, true );
}
add_action( 'after_setup_theme', 'ogtrips_setup', 20 );
