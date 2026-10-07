<?php
/**
 * Text helpers for merchant-entered copy.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns *starred words* into <em>starred words</em> (the design's coral highlight).
 * Everything else is escaped, so the result is safe to echo.
 *
 * Example: "Most-loved *trips* right now" → "Most-loved <em>trips</em> right now".
 *
 * @param string $text Plain text typed in wp-admin.
 * @return string Safe HTML.
 */
function ogtrips_core_emphasis( $text ) {
	return (string) preg_replace( '/\*([^*\r\n]+)\*/u', '<em>$1</em>', esc_html( (string) $text ) );
}

/**
 * Removes the *stars* from a title wherever it is shown as plain text.
 *
 * @param string $title Title.
 * @return string
 */
function ogtrips_core_strip_stars( $title ) {
	return is_string( $title ) ? str_replace( '*', '', $title ) : $title;
}

/**
 * Plain-text titles on the front end, in feeds and in SEO output; templates that want the
 * coral emphasis read the raw post_title and pass it through ogtrips_core_emphasis().
 */
function ogtrips_core_title_filters() {
	if ( ! is_admin() || wp_doing_ajax() ) {
		add_filter( 'the_title', 'ogtrips_core_strip_stars' );
	}
	add_filter( 'single_post_title', 'ogtrips_core_strip_stars' );
	add_filter( 'the_title_rss', 'ogtrips_core_strip_stars' );
	add_filter( 'wpseo_title', 'ogtrips_core_strip_stars' );
	add_filter( 'wpseo_opengraph_title', 'ogtrips_core_strip_stars' );
	add_filter( 'wpseo_twitter_title', 'ogtrips_core_strip_stars' );
	add_filter(
		'document_title_parts',
		static function ( $parts ) {
			return array_map( 'ogtrips_core_strip_stars', $parts );
		}
	);
}
add_action( 'init', 'ogtrips_core_title_filters' );
