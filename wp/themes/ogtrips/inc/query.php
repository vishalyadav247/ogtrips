<?php
/**
 * Main-query tweaks for the listing pages.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Page sizes and post types for listings and search.
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

	if ( $query->is_post_type_archive( 'ogt_itinerary' ) || $query->is_tax( 'ogt_destination' ) ) {
		$query->set( 'posts_per_page', 12 );
	}

	if ( $query->is_tax( 'ogt_destination' ) ) {
		$query->set( 'post_type', [ 'ogt_itinerary', 'ogt_guide' ] );
		$query->set( 'orderby', [ 'type' => 'DESC', 'date' => 'DESC' ] );
	}

	if ( $query->is_post_type_archive( 'ogt_guide' ) || $query->is_home() ) {
		$query->set( 'posts_per_page', 12 );
	}
}
add_action( 'pre_get_posts', 'ogtrips_pre_get_posts' );
