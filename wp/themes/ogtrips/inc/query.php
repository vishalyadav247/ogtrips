<?php
/**
 * Main-query tweaks for the listing pages, and the ?destination= filter on Trips and Travel Guide.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers ?destination=<slug> (filter chips on /trips/ and /travel-guide/).
 *
 * @param array<int,string> $vars Public query vars.
 * @return array<int,string>
 */
function ogtrips_query_vars( $vars ) {
	$vars[] = 'destination';

	return $vars;
}
add_filter( 'query_vars', 'ogtrips_query_vars' );

/**
 * The destination term chosen with ?destination=, if valid.
 *
 * @return WP_Term|null
 */
function ogtrips_current_destination() {
	if ( is_tax( 'ogt_destination' ) ) {
		$term = get_queried_object();
		return $term instanceof WP_Term ? $term : null;
	}

	$slug = sanitize_title( (string) get_query_var( 'destination' ) );
	$term = '' !== $slug ? get_term_by( 'slug', $slug, 'ogt_destination' ) : false;

	return $term instanceof WP_Term ? $term : null;
}

/**
 * Filter chips for a listing: "All" + every destination that has published posts of $post_type.
 *
 * @param string $post_type ogt_itinerary or ogt_guide.
 * @param string $all_label Label of the "All" chip.
 * @return array<int,array{0:string,1:string,2:bool}> [ url, label, active ].
 */
function ogtrips_destination_chips( $post_type, $all_label ) {
	$base    = (string) get_post_type_archive_link( $post_type );
	$current = ogtrips_current_destination();
	$chips   = [ [ $base, $all_label, ! $current ] ];
	$terms   = get_terms(
		[
			'taxonomy'   => 'ogt_destination',
			'hide_empty' => true,
		]
	);

	foreach ( is_wp_error( $terms ) ? [] : $terms as $term ) {
		$has = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'ogt_destination',
						'terms'    => [ $term->term_id ],
					],
				],
			]
		);
		if ( $has ) {
			$chips[] = [ add_query_arg( 'destination', $term->slug, $base ), $term->name, $current && $current->term_id === $term->term_id ];
		}
	}

	return count( $chips ) > 2 ? $chips : [];
}

/**
 * Page sizes, post types and the destination filter for listings and search.
 *
 * @param WP_Query $query Query.
 */
function ogtrips_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_search() ) {
		$query->set( 'post_type', [ 'ogt_itinerary', 'ogt_guide', 'post', 'page' ] );
		$query->set( 'posts_per_page', 12 );
	}

	// Destination page: its trips in the main list; its tour guides get their own row (template).
	if ( $query->is_tax( 'ogt_destination' ) ) {
		$query->set( 'post_type', 'ogt_itinerary' );
		$query->set( 'posts_per_page', 12 );
	}

	if ( $query->is_post_type_archive( [ 'ogt_itinerary', 'ogt_guide' ] ) ) {
		$query->set( 'posts_per_page', 12 );

		$slug = sanitize_title( (string) $query->get( 'destination' ) );
		if ( '' !== $slug && term_exists( $slug, 'ogt_destination' ) ) { // Unknown slug: show everything.
			$query->set(
				'tax_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					[
						'taxonomy' => 'ogt_destination',
						'field'    => 'slug',
						'terms'    => [ $slug ],
					],
				]
			);
		}
	}

	if ( $query->is_home() ) {
		$query->set( 'posts_per_page', 12 );
	}
}
add_action( 'pre_get_posts', 'ogtrips_pre_get_posts' );

/**
 * Filtered listings (?destination=) point search engines at the unfiltered page; the
 * /destinations/<name>/ pages are the ones meant to rank for a place.
 *
 * @param string $canonical Canonical URL.
 * @return string
 */
function ogtrips_filtered_canonical( $canonical ) {
	if ( '' !== (string) get_query_var( 'destination' ) && is_post_type_archive( [ 'ogt_itinerary', 'ogt_guide' ] ) ) {
		return (string) get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
	}

	return $canonical;
}
add_filter( 'wpseo_canonical', 'ogtrips_filtered_canonical' );
