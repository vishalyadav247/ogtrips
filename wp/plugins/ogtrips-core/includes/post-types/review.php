<?php
/**
 * Post type ogt_review — "Reviews". Not public: shown only inside other pages.
 * The title is filled automatically from the reviewer's name.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Reviews post type.
 */
function ogtrips_core_register_review() {
	register_post_type(
		'ogt_review',
		[
			'labels'          => [
				'name'               => __( 'Reviews', 'ogtrips-core' ),
				'singular_name'      => __( 'Review', 'ogtrips-core' ),
				'menu_name'          => __( 'Reviews', 'ogtrips-core' ),
				'all_items'          => __( 'All reviews', 'ogtrips-core' ),
				'add_new'            => __( 'Add review', 'ogtrips-core' ),
				'add_new_item'       => __( 'Add a new review', 'ogtrips-core' ),
				'edit_item'          => __( 'Edit review', 'ogtrips-core' ),
				'new_item'           => __( 'New review', 'ogtrips-core' ),
				'search_items'       => __( 'Search reviews', 'ogtrips-core' ),
				'not_found'          => __( 'No reviews yet.', 'ogtrips-core' ),
				'not_found_in_trash' => __( 'No reviews in the bin.', 'ogtrips-core' ),
				'item_published'     => __( 'Review published.', 'ogtrips-core' ),
				'item_updated'       => __( 'Review updated.', 'ogtrips-core' ),
			],
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-star-filled',
			'supports'        => false,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		]
	);
}
add_action( 'init', 'ogtrips_core_register_review' );

/**
 * Titles the review "{reviewer name}" after SCF has saved the fields.
 *
 * @param int|string $post_id Post ID (or 'option' etc. for non-post screens).
 */
function ogtrips_core_review_title_from_name( $post_id ) {
	if ( ! is_numeric( $post_id ) || 'ogt_review' !== get_post_type( $post_id ) || ! function_exists( 'get_field' ) ) {
		return;
	}

	$name = sanitize_text_field( (string) get_field( 'reviewer_name', $post_id ) );

	if ( '' !== $name && get_the_title( $post_id ) !== $name ) {
		wp_update_post(
			[
				'ID'         => (int) $post_id,
				'post_title' => $name,
				'post_name'  => sanitize_title( $name ),
			]
		);
	}
}
add_action( 'acf/save_post', 'ogtrips_core_review_title_from_name', 20 );
