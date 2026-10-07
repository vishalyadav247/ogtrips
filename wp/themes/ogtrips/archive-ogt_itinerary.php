<?php
/**
 * All trips (/trips/) and trips by destination (/destinations/<name>/).
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ogtrips_current = is_tax( 'ogt_destination' ) ? get_queried_object() : null;
$ogtrips_chips   = [ [ (string) get_post_type_archive_link( 'ogt_itinerary' ), __( 'All trips', 'ogtrips' ), ! $ogtrips_current ] ];
$ogtrips_terms   = get_terms(
	[
		'taxonomy'   => 'ogt_destination',
		'hide_empty' => true,
	]
);

if ( ! is_wp_error( $ogtrips_terms ) ) {
	foreach ( $ogtrips_terms as $ogtrips_term ) {
		$ogtrips_chips[] = [ (string) get_term_link( $ogtrips_term ), $ogtrips_term->name, $ogtrips_current && $ogtrips_current->term_id === $ogtrips_term->term_id ];
	}
}

get_template_part(
	'template-parts/listing',
	null,
	[
		'label' => __( 'Our OG Trips', 'ogtrips' ),
		/* translators: %s: destination */
		'title' => $ogtrips_current ? sprintf( __( 'Trips & guides for *%s*', 'ogtrips' ), $ogtrips_current->name ) : __( 'Every trip, *fully planned*', 'ogtrips' ),
		'lead'  => $ogtrips_current && '' !== $ogtrips_current->description ? $ogtrips_current->description : __( 'Handpicked stays, day-by-day plans and a real human on call 24/7. Pick a trip — or ask us to tailor one.', 'ogtrips' ),
		'card'  => 'best',
		'chips' => count( $ogtrips_chips ) > 2 ? $ogtrips_chips : [],
		'empty' => __( 'No trips here yet — tell us where you want to go and we will plan it.', 'ogtrips' ),
	]
);

get_template_part( 'template-parts/contact' );

get_footer();
