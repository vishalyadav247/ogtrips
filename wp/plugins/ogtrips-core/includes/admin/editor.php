<?php
/**
 * Editors: the block editor is only used for article bodies (Tour Guides, Blog) and
 * limited to basic blocks so articles always match the design. Everything else uses
 * the classic screen with SCF forms.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types that use the block editor.
 *
 * @return string[]
 */
function ogtrips_core_article_post_types() {
	return [ 'ogt_guide', 'post' ];
}

/**
 * Block editor on for articles (and admin-only Pages), off for Trips, Reviews, Moments, Enquiries.
 *
 * @param bool   $use       Whether to use the block editor.
 * @param string $post_type Post type.
 * @return bool
 */
function ogtrips_core_use_block_editor( $use, $post_type ) {
	if ( in_array( $post_type, [ 'ogt_itinerary', 'ogt_review', 'ogt_moment', 'ogt_enquiry' ], true ) ) {
		return false;
	}

	return $use;
}
add_filter( 'use_block_editor_for_post_type', 'ogtrips_core_use_block_editor', 10, 2 );

/**
 * Article bodies: paragraph, heading, image, list, quote, table, separator + Trip CTA.
 *
 * @param bool|string[]           $allowed Allowed block names (true = all).
 * @param WP_Block_Editor_Context $context Editor context.
 * @return bool|string[]
 */
function ogtrips_core_allowed_blocks( $allowed, $context ) {
	if ( empty( $context->post ) || ! in_array( $context->post->post_type, ogtrips_core_article_post_types(), true ) ) {
		return $allowed;
	}

	return [
		'core/paragraph',
		'core/heading',
		'core/image',
		'core/list',
		'core/list-item',
		'core/quote',
		'core/table',
		'core/separator',
		'ogtrips/trip-cta',
	];
}
add_filter( 'allowed_block_types_all', 'ogtrips_core_allowed_blocks', 10, 2 );

/**
 * Headings: only H2 (sections, build the table of contents) and H3.
 *
 * @param array<string,mixed> $args Block type args.
 * @param string              $name Block name.
 * @return array<string,mixed>
 */
function ogtrips_core_heading_levels( $args, $name ) {
	if ( 'core/heading' === $name ) {
		$args['attributes']['levelOptions']['default'] = [ 2, 3 ];
		$args['attributes']['level']['default']        = 2;
	}

	return $args;
}
add_filter( 'register_block_type_args', 'ogtrips_core_heading_levels', 10, 2 );

/**
 * No custom colours, gradients, font sizes, spacing or borders — the design decides.
 *
 * @param WP_Theme_JSON_Data $theme_json Theme JSON data.
 * @return WP_Theme_JSON_Data
 */
function ogtrips_core_lock_design_tools( $theme_json ) {
	return $theme_json->update_with(
		[
			'version'  => 3,
			'settings' => [
				'appearanceTools' => false,
				'color'           => [
					'custom'          => false,
					'customDuotone'   => false,
					'customGradient'  => false,
					'defaultPalette'  => false,
					'defaultGradients' => false,
					'defaultDuotone'  => false,
					'palette'         => [],
					'gradients'       => [],
					'duotone'         => [],
					'background'      => false,
					'text'            => false,
					'link'            => false,
				],
				'typography'      => [
					'customFontSize'   => false,
					'defaultFontSizes' => false,
					'fontSizes'        => [],
					'dropCap'          => false,
					'fontStyle'        => false,
					'fontWeight'       => false,
					'letterSpacing'    => false,
					'lineHeight'       => false,
					'textDecoration'   => false,
					'textTransform'    => false,
					'writingMode'      => false,
				],
				// blockGap left unset on purpose: any value (even false) opts a classic theme into gap styles.
				'spacing'         => [
					'padding' => false,
					'margin'  => false,
				],
				'border'          => [
					'color'  => false,
					'radius' => false,
					'style'  => false,
					'width'  => false,
				],
			],
		]
	);
}
add_filter( 'wp_theme_json_data_theme', 'ogtrips_core_lock_design_tools' );

/**
 * No pattern library, block directory or Openverse in the editor.
 *
 * @param array<string,mixed> $settings Editor settings.
 * @return array<string,mixed>
 */
function ogtrips_core_editor_settings( $settings ) {
	$settings['enableOpenverseMediaCategory'] = false;
	$settings['__experimentalBlockPatterns']   = [];
	$settings['__experimentalBlockPatternCategories'] = [];

	return $settings;
}
add_filter( 'block_editor_settings_all', 'ogtrips_core_editor_settings' );

add_filter( 'should_load_remote_block_patterns', '__return_false' );
remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );

/**
 * Removes core's bundled block patterns.
 */
function ogtrips_core_remove_core_patterns() {
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'ogtrips_core_remove_core_patterns', 99 );

/**
 * Removes GeneratePress's "Layout" box (sidebars, footer widgets, disable elements…) from every
 * edit screen — the OgTrips templates don't use any of those options.
 */
function ogtrips_core_remove_gp_layout_box() {
	foreach ( get_post_types( [ 'show_ui' => true ] ) as $post_type ) {
		remove_meta_box( 'generate_layout_options_meta_box', $post_type, 'side' );
		remove_meta_box( 'generate_layout_options_meta_box', $post_type, 'normal' );
	}
}
add_action( 'add_meta_boxes', 'ogtrips_core_remove_gp_layout_box', 99 );
