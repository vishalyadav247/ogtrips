<?php
/**
 * Homepage content lives on the Home page (Pages → Home, the static front page): its edit screen
 * shows only the Homepage fields (group_ogt_homepage.json, location "front page") plus Yoast SEO —
 * no block editor. Values saved on the old "Homepage" settings screen are copied over once.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * ID of the static front page (0 if the site shows latest posts on the front).
 *
 * @return int
 */
function ogtrips_core_homepage_id() {
	return 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
}

/**
 * Reads a homepage field: from the Home page, falling back to the old settings value.
 *
 * @param string $name    Field name.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function ogtrips_core_home_field( $name, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$empty = static function ( $v ) {
		return null === $v || '' === $v || false === $v || [] === $v;
	};

	$home  = ogtrips_core_homepage_id();
	$value = $home ? get_field( $name, $home ) : null;
	if ( $empty( $value ) ) {
		$value = get_field( $name, 'option' );
	}

	return $empty( $value ) ? $default : $value;
}

/**
 * No block editor on the Home page — its content is the Homepage fields.
 *
 * @param bool    $use  Use the block editor.
 * @param WP_Post $post Post.
 * @return bool
 */
function ogtrips_core_home_no_block_editor( $use, $post ) {
	return ( $post && (int) $post->ID === ogtrips_core_homepage_id() && ogtrips_core_homepage_id() ) ? false : $use;
}
add_filter( 'use_block_editor_for_post', 'ogtrips_core_home_no_block_editor', 10, 2 );

/**
 * Removes the classic content box on the Home page edit screen and explains the screen.
 */
function ogtrips_core_home_edit_screen() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.

	if ( ! $post_id || $post_id !== ogtrips_core_homepage_id() ) {
		return;
	}

	remove_post_type_support( 'page', 'editor' );

	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-info"><p>' . esc_html__( 'This is your homepage. Edit each section in the tabs below, then click Update. Write *word* to show a word in coral.', 'ogtrips-core' ) . '</p></div>';
		}
	);
}
add_action( 'load-post.php', 'ogtrips_core_home_edit_screen' );

/**
 * One-time copy of the old "Homepage" settings values onto the Home page (fields still empty there).
 */
function ogtrips_core_migrate_homepage() {
	$home = ogtrips_core_homepage_id();

	if ( ! $home || get_option( 'ogtrips_core_home_migrated' ) || ! function_exists( 'acf_get_fields' ) ) {
		return;
	}

	foreach ( (array) acf_get_fields( 'group_ogt_homepage' ) as $field ) {
		if ( empty( $field['name'] ) ) {
			continue; // Tabs.
		}
		$old = get_field( $field['name'], 'option', false );
		$new = get_field( $field['name'], $home, false );
		if ( ( null === $new || '' === $new || [] === $new || false === $new ) && null !== $old && '' !== $old && [] !== $old ) {
			update_field( $field['key'], $old, $home );
		}
	}

	update_option( 'ogtrips_core_home_migrated', time(), false );
}
add_action( 'admin_init', 'ogtrips_core_migrate_homepage' );
