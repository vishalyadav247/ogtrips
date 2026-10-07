<?php
/**
 * Tour guide listing (/travel-guide/).
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part(
	'template-parts/listing',
	null,
	[
		'label' => __( 'Travel guide', 'ogtrips' ),
		'title' => __( 'Plan smarter with our *travel guides*', 'ogtrips' ),
		'lead'  => __( 'Where to stay, how to get around, what it costs and when to go — from the trip captains who have been there.', 'ogtrips' ),
		'card'  => 'post',
		'empty' => __( 'Our first guides are on their way.', 'ogtrips' ),
	]
);

get_footer();
