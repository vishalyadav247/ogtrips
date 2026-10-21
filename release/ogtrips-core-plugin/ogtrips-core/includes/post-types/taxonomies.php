<?php
/**
 * Taxonomies:
 * - ogt_destination  "Destinations" (country → region) on Trips, Tour Guides, Blog. URL /destinations/{slug}/.
 * - ogt_trip_type    "Trip types" (honeymoon, family…) on Trips. Not public (drives filters/form pills).
 * - ogt_guide_topic  "Guide topics" (Destination guide, Travel tips…) on Tour Guides. Not public yet.
 *
 * Trip types and guide topics are registered as hierarchical only so wp-admin shows
 * simple tick-boxes instead of a free-text tag box; they are never nested (the parent
 * picker is hidden by assets/admin.css).
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the taxonomies.
 */
function ogtrips_core_register_taxonomies() {
	register_taxonomy(
		'ogt_destination',
		[ 'ogt_itinerary', 'ogt_guide', 'post' ],
		[
			'labels'            => [
				'name'              => __( 'Destinations', 'ogtrips-core' ),
				'singular_name'     => __( 'Destination', 'ogtrips-core' ),
				'menu_name'         => __( 'Destinations', 'ogtrips-core' ),
				'all_items'         => __( 'All destinations', 'ogtrips-core' ),
				'edit_item'         => __( 'Edit destination', 'ogtrips-core' ),
				'add_new_item'      => __( 'Add destination', 'ogtrips-core' ),
				'new_item_name'     => __( 'New destination name', 'ogtrips-core' ),
				'parent_item'       => __( 'Country (leave empty for a country)', 'ogtrips-core' ),
				'parent_item_colon' => __( 'Country:', 'ogtrips-core' ),
				'search_items'      => __( 'Search destinations', 'ogtrips-core' ),
				'not_found'         => __( 'No destinations yet.', 'ogtrips-core' ),
			],
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => [
				'slug'         => 'destinations',
				'with_front'   => false,
				'hierarchical' => false,
			],
		]
	);

	register_taxonomy(
		'ogt_trip_type',
		[ 'ogt_itinerary' ],
		[
			'labels'             => [
				'name'          => __( 'Trip types', 'ogtrips-core' ),
				'singular_name' => __( 'Trip type', 'ogtrips-core' ),
				'menu_name'     => __( 'Trip types', 'ogtrips-core' ),
				'all_items'     => __( 'All trip types', 'ogtrips-core' ),
				'edit_item'     => __( 'Edit trip type', 'ogtrips-core' ),
				'add_new_item'  => __( 'Add trip type', 'ogtrips-core' ),
				'new_item_name' => __( 'New trip type name', 'ogtrips-core' ),
				'search_items'  => __( 'Search trip types', 'ogtrips-core' ),
				'not_found'     => __( 'No trip types yet.', 'ogtrips-core' ),
			],
			'hierarchical'       => true,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'rewrite'            => false,
		]
	);

	register_taxonomy(
		'ogt_guide_topic',
		[ 'ogt_guide' ],
		[
			'labels'             => [
				'name'          => __( 'Guide topics', 'ogtrips-core' ),
				'singular_name' => __( 'Guide topic', 'ogtrips-core' ),
				'menu_name'     => __( 'Guide topics', 'ogtrips-core' ),
				'all_items'     => __( 'All guide topics', 'ogtrips-core' ),
				'edit_item'     => __( 'Edit guide topic', 'ogtrips-core' ),
				'add_new_item'  => __( 'Add guide topic', 'ogtrips-core' ),
				'new_item_name' => __( 'New guide topic name', 'ogtrips-core' ),
				'search_items'  => __( 'Search guide topics', 'ogtrips-core' ),
				'not_found'     => __( 'No guide topics yet.', 'ogtrips-core' ),
			],
			'hierarchical'       => true,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'rewrite'            => false,
		]
	);
}
add_action( 'init', 'ogtrips_core_register_taxonomies', 5 );

