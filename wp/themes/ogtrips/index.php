<?php
/**
 * Fallback listing: blog posts page, categories, tags, authors and dates.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ogtrips_title = __( 'Stories from the *road*', 'ogtrips' );
$ogtrips_label = __( 'Blog', 'ogtrips' );

if ( is_archive() ) {
	$ogtrips_title = wp_strip_all_tags( get_the_archive_title() );
	$ogtrips_label = __( 'Archive', 'ogtrips' );
}

get_template_part(
	'template-parts/listing',
	null,
	[
		'label' => $ogtrips_label,
		'title' => $ogtrips_title,
		'card'  => 'post',
		'empty' => __( 'No posts yet.', 'ogtrips' ),
	]
);

get_footer();
