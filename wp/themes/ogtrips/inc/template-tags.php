<?php
/**
 * Template tags used across the child theme templates.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns an inline SVG that references a Lucide icon in the local sprite (assets/icons/sprite.svg).
 *
 * Same names as the design's data-lucide attributes, same markup Lucide generates (class "lucide"),
 * so the design CSS applies unchanged. Decorative: hidden from assistive tech — give the parent
 * link/button an accessible name.
 *
 * @param string $name  Lucide icon name, e.g. 'map-pin'.
 * @param string $class Extra CSS classes.
 * @return string
 */
function ogtrips_icon( $name, $class = '' ) {
	static $sprite = null;

	if ( null === $sprite ) {
		$sprite = get_theme_file_uri( 'assets/icons/sprite.svg' ) . '?ver=' . ogtrips_asset_version( 'assets/icons/sprite.svg' );
	}

	$name    = sanitize_key( $name );
	$classes = trim( 'lucide lucide-' . $name . ' ' . $class );
	$href    = $sprite . '#' . $name;

	return sprintf(
		'<svg class="%1$s" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><use href="%2$s"></use></svg>',
		esc_attr( $classes ),
		esc_url( $href )
	);
}
