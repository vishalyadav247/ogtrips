<?php
/**
 * Post type ogt_guide — "Tour Guides" (approved guide.html design).
 * URL /travel-guide/{slug}/, archive /travel-guide/. Block editor body + side fields.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Tour Guides post type.
 */
function ogtrips_core_register_guide() {
	register_post_type(
		'ogt_guide',
		[
			'labels'          => [
				'name'               => __( 'Tour Guides', 'ogtrips-core' ),
				'singular_name'      => __( 'Tour Guide', 'ogtrips-core' ),
				'menu_name'          => __( 'Tour Guides', 'ogtrips-core' ),
				'all_items'          => __( 'All tour guides', 'ogtrips-core' ),
				'add_new'            => __( 'Add tour guide', 'ogtrips-core' ),
				'add_new_item'       => __( 'Add a new tour guide', 'ogtrips-core' ),
				'edit_item'          => __( 'Edit tour guide', 'ogtrips-core' ),
				'new_item'           => __( 'New tour guide', 'ogtrips-core' ),
				'view_item'          => __( 'View tour guide', 'ogtrips-core' ),
				'view_items'         => __( 'View tour guides', 'ogtrips-core' ),
				'search_items'       => __( 'Search tour guides', 'ogtrips-core' ),
				'not_found'          => __( 'No tour guides yet.', 'ogtrips-core' ),
				'not_found_in_trash' => __( 'No tour guides in the bin.', 'ogtrips-core' ),
				'featured_image'     => __( 'Cover image', 'ogtrips-core' ),
				'set_featured_image' => __( 'Set cover image', 'ogtrips-core' ),
				'item_published'     => __( 'Tour guide published.', 'ogtrips-core' ),
				'item_updated'       => __( 'Tour guide updated.', 'ogtrips-core' ),
			],
			'public'          => true,
			'has_archive'     => 'travel-guide',
			'rewrite'         => [
				'slug'       => 'travel-guide',
				'with_front' => false,
			],
			'menu_icon'       => 'dashicons-book-alt',
			'supports'        => [ 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ],
			'show_in_rest'    => true,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		]
	);
}
add_action( 'init', 'ogtrips_core_register_guide' );
