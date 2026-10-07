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
