<?php
/**
 * Post type ogt_moment — "Moments": traveller photos/reels for the homepage
 * "#OgTrips moments" grid, curated by the team. Not public.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Moments post type.
 */
function ogtrips_core_register_moment() {
	register_post_type(
		'ogt_moment',
		[
			'labels'          => [
				'name'               => __( 'Moments', 'ogtrips-core' ),
				'singular_name'      => __( 'Moment', 'ogtrips-core' ),
				'menu_name'          => __( 'Moments', 'ogtrips-core' ),
				'all_items'          => __( 'All moments', 'ogtrips-core' ),
				'add_new'            => __( 'Add moment', 'ogtrips-core' ),
				'add_new_item'       => __( 'Add a new moment', 'ogtrips-core' ),
				'edit_item'          => __( 'Edit moment', 'ogtrips-core' ),
				'new_item'           => __( 'New moment', 'ogtrips-core' ),
				'search_items'       => __( 'Search moments', 'ogtrips-core' ),
				'not_found'          => __( 'No moments yet.', 'ogtrips-core' ),
				'not_found_in_trash' => __( 'No moments in the bin.', 'ogtrips-core' ),
				'item_published'     => __( 'Moment published.', 'ogtrips-core' ),
				'item_updated'       => __( 'Moment updated.', 'ogtrips-core' ),
			],
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-camera',
			'supports'        => [ 'title' ],
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		]
	);
}
add_action( 'init', 'ogtrips_core_register_moment' );

/**
 * Placeholder in the title box (the title is only used inside wp-admin).
 *
 * @param string  $text Default placeholder.
 * @param WP_Post $post Post being edited.
 * @return string
 */
function ogtrips_core_moment_title_placeholder( $text, $post ) {
	return 'ogt_moment' === $post->post_type ? __( 'Short note for you, e.g. Ubud swing reel by @priya', 'ogtrips-core' ) : $text;
}
add_filter( 'enter_title_here', 'ogtrips_core_moment_title_placeholder', 10, 2 );
