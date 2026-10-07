<?php
/**
 * Tour guide listing (/travel-guide/), with destination filter chips (/travel-guide/?destination=ladakh).
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ogtrips_current = ogtrips_current_destination();

get_template_part(
	'template-parts/listing',
	null,
	[
		'label' => __( 'Travel guide', 'ogtrips' ),
		/* translators: %s: destination */
		'title' => $ogtrips_current ? sprintf( __( '*%s* travel guides', 'ogtrips' ), $ogtrips_current->name ) : __( 'Plan smarter with our *travel guides*', 'ogtrips' ),
		'lead'  => __( 'Where to stay, how to get around, what it costs and when to go — from the trip captains who have been there.', 'ogtrips' ),
		'card'  => 'post',
		'chips' => ogtrips_destination_chips( 'ogt_guide', __( 'All guides', 'ogtrips' ) ),
		'empty' => __( 'No guides for this destination yet — ask us on WhatsApp and we will tell you everything.', 'ogtrips' ),
	]
);

get_footer();
