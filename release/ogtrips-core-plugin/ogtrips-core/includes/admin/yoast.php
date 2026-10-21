<?php
/**
 * Yoast SEO in the editing screens: its box sits below our fields.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Yoast metabox priority: low (below SCF field groups).
 *
 * @return string
 */
function ogtrips_core_yoast_metabox_priority() {
	return 'low';
}
add_filter( 'wpseo_metabox_prio', 'ogtrips_core_yoast_metabox_priority' );

/**
 * Yoast breadcrumb trail (BreadcrumbList schema): trips and tour guides get their destination
 * before the page itself, matching the visible trail the theme prints.
 *
 * @param array<int,array<string,mixed>> $links Yoast crumbs.
 * @return array<int,array<string,mixed>>
 */
function ogtrips_core_yoast_breadcrumb_links( $links ) {
	if ( ! is_array( $links ) || count( $links ) < 2 || ! is_singular( [ 'ogt_itinerary', 'ogt_guide' ] ) ) {
		return $links;
	}

	$terms = get_the_terms( get_queried_object_id(), 'ogt_destination' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return $links;
	}

	$link = get_term_link( $terms[0] );
	if ( is_wp_error( $link ) ) {
		return $links;
	}

	array_splice( $links, count( $links ) - 1, 0, [ [ 'url' => $link, 'text' => $terms[0]->name ] ] );

	return $links;
}
add_filter( 'wpseo_breadcrumb_links', 'ogtrips_core_yoast_breadcrumb_links' );
