<?php
/**
 * Search results — trips, tour guides and blog posts.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part(
	'template-parts/listing',
	null,
	[
		'label' => __( 'Search', 'ogtrips' ),
		/* translators: %s: search terms */
		'title' => sprintf( __( 'Results for *%s*', 'ogtrips' ), get_search_query( false ) ),
		'card'  => 'post',
		'empty' => __( 'Nothing matched that search. Try a destination like Bali or Kerala — or ask us below.', 'ogtrips' ),
	]
);

get_template_part( 'template-parts/contact' );

get_footer();
