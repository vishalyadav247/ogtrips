<?php
/**
 * Structured data for trip pages, added to Yoast SEO's schema graph (no second JSON-LD block):
 * TouristTrip (route, duration, offer when prices are shown) and FAQPage from the trip FAQs.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds TouristTrip + FAQPage nodes on single trips.
 *
 * @param array<int,array<string,mixed>> $graph   Yoast graph pieces.
 * @param object                         $context Yoast meta-tags context.
 * @return array<int,array<string,mixed>>
 */
function ogtrips_core_trip_schema( $graph, $context = null ) {
	if ( ! is_singular( 'ogt_itinerary' ) || ! function_exists( 'get_field' ) ) {
		return $graph;
	}

	$id   = get_queried_object_id();
	$url  = get_permalink( $id );
	$name = str_replace( '*', '', get_post_field( 'post_title', $id ) );

	$trip = [
		'@type'       => 'TouristTrip',
		'@id'         => $url . '#trip',
		'name'        => $name,
		'description' => wp_strip_all_tags( get_the_excerpt( $id ) ),
		'url'         => $url,
	];

	$image = get_the_post_thumbnail_url( $id, 'large' );
	if ( $image ) {
		$trip['image'] = $image;
	}

	$stops = array_values( array_filter( get_field( 'route_stops', $id ) ?: [], static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['label'] ?? '' ) ) ) );
	if ( $stops ) {
		$trip['itinerary'] = [
			'@type'           => 'ItemList',
			'itemListElement' => array_values(
				array_map(
					static function ( $stop, $i ) {
						return [
							'@type'    => 'ListItem',
							'position' => $i + 1,
							'item'     => [
								'@type' => 'Place',
								'name'  => (string) ( $stop['label'] ?? '' ),
							],
						];
					},
					$stops,
					array_keys( $stops )
				)
			),
		];
	}

	$types = get_the_terms( $id, 'ogt_trip_type' );
	if ( $types && ! is_wp_error( $types ) ) {
		$trip['touristType'] = wp_list_pluck( $types, 'name' );
	}

	$price = (int) get_field( 'price_from', $id );
	if ( $price && get_field( 'show_prices', 'option' ) ) {
		$trip['offers'] = [
			'@type'         => 'Offer',
			'price'         => $price,
			'priceCurrency' => 'INR',
			'url'           => $url . '#book',
			'availability'  => 'https://schema.org/InStock',
		];
	}

	$graph[] = $trip;

	$faqs = array_values( array_filter( get_field( 'faqs', $id ) ?: [], static fn( $row ) => is_array( $row ) && '' !== trim( (string) ( $row['question'] ?? '' ) ) && '' !== trim( wp_strip_all_tags( (string) ( $row['answer'] ?? '' ) ) ) ) );
	if ( $faqs ) {
		$graph[] = [
			'@type'      => 'FAQPage',
			'@id'        => $url . '#faq',
			'mainEntity' => array_values(
				array_map(
					static function ( $faq ) {
						return [
							'@type'          => 'Question',
							'name'           => wp_strip_all_tags( (string) ( $faq['question'] ?? '' ) ),
							'acceptedAnswer' => [
								'@type' => 'Answer',
								'text'  => wp_strip_all_tags( (string) ( $faq['answer'] ?? '' ) ),
							],
						];
					},
					$faqs
				)
			),
		];
	}

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'ogtrips_core_trip_schema', 10, 2 );
