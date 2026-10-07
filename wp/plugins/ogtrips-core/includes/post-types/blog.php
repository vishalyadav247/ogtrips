<?php
/**
 * Core "Posts" become the "Blog" (optional for the client; same article layout as
 * Tour Guides). URL /blog/{slug}/ comes from the permalink structure set in setup.sh.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Relabels Posts → Blog.
 *
 * @param object $labels Post type labels.
 * @return object
 */
function ogtrips_core_blog_labels( $labels ) {
	$labels->name               = __( 'Blog', 'ogtrips-core' );
	$labels->menu_name          = __( 'Blog', 'ogtrips-core' );
	$labels->singular_name      = __( 'Blog post', 'ogtrips-core' );
	$labels->all_items          = __( 'All blog posts', 'ogtrips-core' );
	$labels->add_new            = __( 'Add blog post', 'ogtrips-core' );
	$labels->add_new_item       = __( 'Add a new blog post', 'ogtrips-core' );
	$labels->edit_item          = __( 'Edit blog post', 'ogtrips-core' );
	$labels->new_item           = __( 'New blog post', 'ogtrips-core' );
	$labels->view_item          = __( 'View blog post', 'ogtrips-core' );
	$labels->search_items       = __( 'Search blog posts', 'ogtrips-core' );
	$labels->not_found          = __( 'No blog posts yet.', 'ogtrips-core' );
	$labels->not_found_in_trash = __( 'No blog posts in the bin.', 'ogtrips-core' );
	$labels->name_admin_bar     = __( 'Blog post', 'ogtrips-core' );

	return $labels;
}
add_filter( 'post_type_labels_post', 'ogtrips_core_blog_labels' );
