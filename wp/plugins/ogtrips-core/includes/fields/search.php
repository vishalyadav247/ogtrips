<?php
/**
 * Site search that understands trips and tour guides.
 *
 * Core search only looks at title, excerpt and content. Each trip / guide also keeps a plain-text
 * "_ogt_search" meta (destinations, trip types, topics and the form fields: location, highlights,
 * route, days, stays, inclusions, FAQs, at-a-glance…), rebuilt whenever the post, its fields or
 * its terms are saved. Search matches that text too, and lists trips first, then guides, then
 * blog posts and pages.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plain text of everything worth finding in a trip or guide.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function ogtrips_core_search_text( $post_id ) {
	$parts = [];

	foreach ( [ 'ogt_destination', 'ogt_trip_type', 'ogt_guide_topic' ] as $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$parts = array_merge( $parts, wp_list_pluck( $terms, 'name' ) );
		}
	}

	if ( function_exists( 'get_field' ) ) {
		$fields = 'ogt_itinerary' === get_post_type( $post_id )
			? [ 'title_display', 'short_title', 'location_label', 'badge', 'best_time', 'stays_label', 'offer_label', 'route_stops', 'highlights', 'days', 'stays', 'included', 'excluded', 'faqs' ]
			: [ 'cover_caption', 'glance_best_time', 'glance_budget', 'glance_length', 'glance_visa' ];

		foreach ( $fields as $name ) {
			$value = get_field( $name, $post_id, false );
			if ( is_array( $value ) ) {
				array_walk_recursive(
					$value,
					static function ( $v, $key ) use ( &$parts ) {
						// Text only — skip IDs (images, users), field keys and icon names.
						if ( is_string( $v ) && '' !== $v && ! is_numeric( $v ) && 0 !== strpos( $v, 'field_' ) && ! preg_match( '/(^|_)icon$/', (string) $key ) ) {
							$parts[] = $v;
						}
					}
				);
			} elseif ( is_string( $value ) && ! is_numeric( $value ) ) {
				$parts[] = $value;
			}
		}
	}

	$text = wp_strip_all_tags( implode( ' ', $parts ) );
	$text = str_replace( '*', '', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );

	return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * Rebuilds a post's search text (trips and tour guides only).
 *
 * @param int $post_id Post ID.
 */
function ogtrips_core_index_post( $post_id ) {
	$post_id = (int) $post_id;

	if ( ! in_array( get_post_type( $post_id ), [ 'ogt_itinerary', 'ogt_guide' ], true ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_ogt_search', ogtrips_core_search_text( $post_id ) );
}
add_action( 'acf/save_post', 'ogtrips_core_index_post', 20 ); // After SCF has saved the fields.
add_action( 'save_post', 'ogtrips_core_index_post', 99 );

/**
 * Re-index when a trip's or guide's destinations / types / topics change.
 *
 * @param int $object_id Post ID.
 */
function ogtrips_core_index_on_terms( $object_id ) {
	ogtrips_core_index_post( $object_id );
}
add_action( 'set_object_terms', 'ogtrips_core_index_on_terms' );

/**
 * Indexes every trip and guide once after install or a plugin update (so existing content is searchable).
 */
function ogtrips_core_maybe_reindex() {
	if ( get_option( 'ogtrips_core_search_version' ) === OGTRIPS_CORE_VERSION ) {
		return;
	}

	$ids = get_posts(
		[
			'post_type'      => [ 'ogt_itinerary', 'ogt_guide' ],
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]
	);
	foreach ( $ids as $id ) {
		ogtrips_core_index_post( $id );
	}

	update_option( 'ogtrips_core_search_version', OGTRIPS_CORE_VERSION, true );
}
add_action( 'admin_init', 'ogtrips_core_maybe_reindex' );

/**
 * Is this the front-end site search?
 *
 * @param WP_Query $query Query.
 * @return bool
 */
function ogtrips_core_is_site_search( $query ) {
	return ! is_admin() && $query->is_main_query() && $query->is_search() && ! empty( $query->get( 'search_terms' ) );
}

/**
 * Same as core search (every word must match) but each word may also match the search text.
 *
 * @param string   $search SQL search clause.
 * @param WP_Query $query  Query.
 * @return string
 */
function ogtrips_core_posts_search( $search, $query ) {
	global $wpdb;

	if ( ! ogtrips_core_is_site_search( $query ) ) {
		return $search;
	}

	$clauses = [];
	foreach ( (array) $query->get( 'search_terms' ) as $term ) {
		$exclude = '-' === substr( $term, 0, 1 ) && strlen( $term ) > 1;
		$like    = '%' . $wpdb->esc_like( $exclude ? substr( $term, 1 ) : $term ) . '%';

		if ( $exclude ) {
			$clauses[] = $wpdb->prepare( "({$wpdb->posts}.post_title NOT LIKE %s AND {$wpdb->posts}.post_excerpt NOT LIKE %s AND {$wpdb->posts}.post_content NOT LIKE %s)", $like, $like, $like );
			continue;
		}

		$clauses[] = $wpdb->prepare(
			"({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} ogt_s WHERE ogt_s.post_id = {$wpdb->posts}.ID AND ogt_s.meta_key = '_ogt_search' AND ogt_s.meta_value LIKE %s))",
			$like,
			$like,
			$like,
			$like
		);
	}

	if ( ! $clauses ) {
		return $search;
	}

	$search = ' AND (' . implode( ' AND ', $clauses ) . ')';
	if ( ! is_user_logged_in() ) {
		$search .= " AND ({$wpdb->posts}.post_password = '')";
	}

	return $search;
}
add_filter( 'posts_search', 'ogtrips_core_posts_search', 10, 2 );

/**
 * Trips first, then tour guides, then blog posts and pages; core relevance within each group.
 *
 * @param string   $orderby SQL ORDER BY for search.
 * @param WP_Query $query   Query.
 * @return string
 */
function ogtrips_core_search_orderby( $orderby, $query ) {
	global $wpdb;

	if ( ! ogtrips_core_is_site_search( $query ) ) {
		return $orderby;
	}

	$by_type = "FIELD({$wpdb->posts}.post_type, 'ogt_itinerary', 'ogt_guide', 'post', 'page')";

	return '' !== trim( (string) $orderby ) ? $by_type . ', ' . $orderby : $by_type . ", {$wpdb->posts}.post_date DESC";
}
add_filter( 'posts_search_orderby', 'ogtrips_core_search_orderby', 10, 2 );
