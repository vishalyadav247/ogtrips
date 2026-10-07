<?php
/**
 * All trips (/trips/), with destination filter chips (/trips/?destination=ladakh).
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
		'label' => __( 'Our OG Trips', 'ogtrips' ),
		/* translators: %s: destination */
		'title' => $ogtrips_current ? sprintf( __( 'Trips to *%s*', 'ogtrips' ), $ogtrips_current->name ) : __( 'Every trip, *fully planned*', 'ogtrips' ),
		'lead'  => $ogtrips_current && '' !== $ogtrips_current->description ? $ogtrips_current->description : __( 'Handpicked stays, day-by-day plans and a real human on call 24/7. Pick a trip — or ask us to tailor one.', 'ogtrips' ),
		'card'  => 'best',
		'chips' => ogtrips_destination_chips( 'ogt_itinerary', __( 'All trips', 'ogtrips' ) ),
		'empty' => __( 'No trips here yet — tell us where you want to go and we will plan it.', 'ogtrips' ),
	]
);

get_template_part( 'template-parts/contact' );

get_footer();
