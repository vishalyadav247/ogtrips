<?php
/**
 * Article body: table of contents from H2s and design classes on core blocks.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gives every H2 an id and returns the TOC items.
 *
 * @param string $content Rendered post content.
 * @return array{content:string,items:array<int,array{0:string,1:string}>}
 */
function ogtrips_prepare_toc( $content ) {
	$items = [];
	$used  = [];

	$content = preg_replace_callback(
		'#<h2([^>]*)>(.*?)</h2>#is',
		static function ( $m ) use ( &$items, &$used ) {
			$attrs = $m[1];
			$text  = trim( wp_strip_all_tags( $m[2] ) );

			if ( preg_match( '/\sid=["\']([^"\']+)["\']/', $attrs, $id ) ) {
				$slug = $id[1];
			} else {
				$slug = sanitize_title( $text );
				$slug = '' !== $slug ? $slug : 'section';
				$base = $slug;
				$n    = 2;
				while ( isset( $used[ $slug ] ) ) {
					$slug = $base . '-' . $n++;
				}
				$attrs .= ' id="' . esc_attr( $slug ) . '"';
			}

			$used[ $slug ] = true;
			$items[]       = [ $slug, $text ];

			return '<h2' . $attrs . '>' . $m[2] . '</h2>';
		},
		$content
	);

	return [
		'content' => (string) $content,
		'items'   => $items,
	];
}

/**
 * Design classes on core blocks: tables (.table-wrap/.table) and quotes (.pullquote).
 *
 * @param string              $html  Block HTML.
 * @param array<string,mixed> $block Block.
 * @return string
 */
function ogtrips_block_classes( $html, $block ) {
	if ( 'core/table' === $block['blockName'] ) {
		$html = preg_replace( '/<figure class="wp-block-table/', '<figure class="table-wrap wp-block-table', $html, 1 );
		$html = preg_replace( '/<table(?: class="([^"]*)")?/', '<table class="table $1"', (string) $html, 1 );
	}

	if ( 'core/quote' === $block['blockName'] ) {
		$html = preg_replace( '/<blockquote class="/', '<blockquote class="pullquote ', $html, 1 );
	}

	return (string) $html;
}
add_filter( 'render_block', 'ogtrips_block_classes', 10, 2 );
