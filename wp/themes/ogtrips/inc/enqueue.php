<?php
/**
 * Front-end assets: the one stylesheet (style.css) and the one script (assets/js/main.js).
 *
 * GeneratePress assets are still loaded in this phase (it styles pages our templates
 * don't cover yet); dequeuing them is phase 08.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the child theme stylesheet and script.
 */
function ogtrips_enqueue_assets() {
	// Load after GeneratePress's CSS while it is still enqueued (until phase 08 dequeues it).
	wp_enqueue_style(
		'ogtrips-style',
		get_stylesheet_uri(),
		wp_style_is( 'generate-style', 'registered' ) ? [ 'generate-style' ] : [],
		ogtrips_asset_version( 'style.css' )
	);

	wp_enqueue_script(
		'ogtrips-main',
		get_theme_file_uri( 'assets/js/main.js' ),
		[],
		ogtrips_asset_version( 'assets/js/main.js' ),
		[
			'in_footer' => true,
			'strategy'  => 'defer',
		]
	);
}
add_action( 'wp_enqueue_scripts', 'ogtrips_enqueue_assets', 20 );

// GeneratePress would also enqueue the child style.css (as "generate-child"); we enqueue it ourselves.
add_filter( 'generate_load_child_theme_stylesheet', '__return_false' );

/**
 * Preloads the two most-used font files (DM Sans 400 body, Poppins 700 headings) to avoid a late font swap.
 */
function ogtrips_preload_fonts() {
	$fonts = [
		'assets/fonts/dm-sans-latin-400-normal.woff2',
		'assets/fonts/poppins-latin-700-normal.woff2',
	];

	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( $font ) )
		);
	}
}
add_action( 'wp_head', 'ogtrips_preload_fonts', 2 );
