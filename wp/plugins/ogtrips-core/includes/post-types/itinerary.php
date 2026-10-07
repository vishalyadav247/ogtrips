<?php
/**
 * Post type ogt_itinerary — "Trips". URL /trips/{slug}/, archive /trips/.
 * Fields: acf-json/group_ogt_itinerary.json (classic form; block editor off — see admin/editor.php).
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Trips post type.
 */
function ogtrips_core_register_itinerary() {
	register_post_type(
		'ogt_itinerary',
		[
			'labels'          => [
				'name'               => __( 'Trips', 'ogtrips-core' ),
				'singular_name'      => __( 'Trip', 'ogtrips-core' ),
				'menu_name'          => __( 'Trips', 'ogtrips-core' ),
				'all_items'          => __( 'All trips', 'ogtrips-core' ),
				'add_new'            => __( 'Add trip', 'ogtrips-core' ),
				'add_new_item'       => __( 'Add a new trip', 'ogtrips-core' ),
				'edit_item'          => __( 'Edit trip', 'ogtrips-core' ),
				'new_item'           => __( 'New trip', 'ogtrips-core' ),
				'view_item'          => __( 'View trip', 'ogtrips-core' ),
				'view_items'         => __( 'View trips', 'ogtrips-core' ),
				'search_items'       => __( 'Search trips', 'ogtrips-core' ),
				'not_found'          => __( 'No trips yet.', 'ogtrips-core' ),
				'not_found_in_trash' => __( 'No trips in the bin.', 'ogtrips-core' ),
				'featured_image'     => __( 'Hero image', 'ogtrips-core' ),
				'set_featured_image' => __( 'Set hero image (landscape, at least 2200px wide)', 'ogtrips-core' ),
				'item_published'     => __( 'Trip published.', 'ogtrips-core' ),
				'item_updated'       => __( 'Trip updated.', 'ogtrips-core' ),
			],
			'public'          => true,
			'has_archive'     => 'trips',
			'rewrite'         => [
				'slug'       => 'trips',
				'with_front' => false,
			],
			'menu_icon'       => 'dashicons-palmtree',
			'supports'        => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
			'show_in_rest'    => true,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		]
	);
}
add_action( 'init', 'ogtrips_core_register_itinerary' );

/**
 * Placeholder in the title box.
 *
 * @param string  $text Default placeholder.
 * @param WP_Post $post Post being edited.
 * @return string
 */
function ogtrips_core_itinerary_title_placeholder( $text, $post ) {
	return 'ogt_itinerary' === $post->post_type ? __( 'Trip name, e.g. Bali Bliss: temples & rice terraces', 'ogtrips-core' ) : $text;
}
add_filter( 'enter_title_here', 'ogtrips_core_itinerary_title_placeholder', 10, 2 );
