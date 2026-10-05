<?php
/**
 * Data helpers. The theme must keep working (no fatals) when Secure Custom Fields is inactive.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version string for a child-theme file: its modification time, so caches bust on every change.
 *
 * @param string $relative_path Path relative to the child theme root.
 * @return string
 */
function ogtrips_asset_version( $relative_path ) {
	$file = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );

	return file_exists( $file ) ? (string) filemtime( $file ) : OGTRIPS_VERSION;
}

/**
 * Reads a Secure Custom Fields value with a fallback. Safe when SCF is not active.
 *
 * Returns the raw value — escape it on output. Empty values (null, '', false, []) return
 * $default, so for true/false fields pass `false` as the default or call get_field() directly.
 *
 * @param string          $name    Field name.
 * @param int|string|null $post_id Post ID, 'option', etc. Null = current post.
 * @param mixed           $default Returned when SCF is inactive or the field is empty.
 * @return mixed
 */
function ogtrips_field( $name, $post_id = null, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $name, $post_id ?? false );

	return ( null === $value || '' === $value || false === $value || [] === $value ) ? $default : $value;
}
