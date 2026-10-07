<?php
/**
 * Front-end assets: the one stylesheet (style.css) and the one script (assets/js/main.js).
 * Every front-end template is ours, so GeneratePress's own CSS/JS is not loaded.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the child theme stylesheet and script; drops parent and unused core assets.
 */
function ogtrips_enqueue_assets() {
	wp_enqueue_style( 'ogtrips-style', get_stylesheet_uri(), [], ogtrips_asset_version( 'style.css' ) );

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

	wp_localize_script(
		'ogtrips-main',
		'ogtripsL10n',
		[
			'copied'   => __( 'Link copied', 'ogtrips' ),
			'expand'   => __( 'Expand all', 'ogtrips' ),
			'collapse' => __( 'Collapse all', 'ogtrips' ),
			'sending'  => __( 'Sending…', 'ogtrips' ),
		]
	);

	// GeneratePress styles/scripts: our templates replace its layout.
	foreach ( [ 'generate-style', 'generate-main', 'generate-font-icons', 'generate-child', 'generate-widget-areas', 'generate-comments', 'generate-offside', 'generate-navigation-branding' ] as $handle ) {
		wp_dequeue_style( $handle );
	}
	foreach ( [ 'generate-main', 'generate-menu', 'generate-navigation-search', 'generate-back-to-top', 'generate-dropdown-click', 'generate-a11y' ] as $handle ) {
		wp_dequeue_script( $handle );
	}

	// Block CSS only where block content is shown (articles and plain pages).
	if ( ! is_singular( [ 'ogt_guide', 'post', 'page' ] ) || is_front_page() ) {
		foreach ( [ 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles', 'core-block-supports' ] as $handle ) {
			wp_dequeue_style( $handle );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'ogtrips_enqueue_assets', 100 );

// GeneratePress would also enqueue the child style.css (as "generate-child"); we enqueue it ourselves.
add_filter( 'generate_load_child_theme_stylesheet', '__return_false' );

/**
 * Removes head clutter: emoji, oEmbed discovery, RSD, generator, shortlinks, GP's viewport (ours is in header.php).
 */
function ogtrips_clean_head() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'generate_add_viewport', 1 );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	add_filter( 'emoji_svg_url', '__return_false' );
	add_filter( 'should_load_separate_core_block_assets', '__return_true' );
}
add_action( 'after_setup_theme', 'ogtrips_clean_head', 30 );

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

/**
 * Site icon fallback: the OgTrips mark when no Site Icon is set in Settings → General.
 */
function ogtrips_favicon() {
	if ( ! has_site_icon() ) {
		printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( get_theme_file_uri( 'assets/img/ogtrips-mark.svg' ) ) );
	}
}
add_action( 'wp_head', 'ogtrips_favicon', 3 );
