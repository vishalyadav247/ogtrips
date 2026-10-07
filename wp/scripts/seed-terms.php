<?php
/**
 * Starter terms for the local site (run by setup.sh via `wp eval-file`). Idempotent.
 *
 * @package ogtrips-core
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$ogtrips_terms = [
	'ogt_trip_type'   => [
		'honeymoon' => [ 'Honeymoon', 'heart' ],
		'family'    => [ 'Family', 'users' ],
		'adventure' => [ 'Adventure', 'mountain' ],
		'friends'   => [ 'Friends', 'party-popper' ],
		'culture'   => [ 'Culture', 'landmark' ],
	],
	'ogt_guide_topic' => [
		'destination-guide' => [ 'Destination guide', '' ],
		'travel-tips'       => [ 'Travel tips', '' ],
		'seasonal'          => [ 'Seasonal', '' ],
	],
];

foreach ( $ogtrips_terms as $ogtrips_taxonomy => $ogtrips_list ) {
	foreach ( $ogtrips_list as $ogtrips_slug => [ $ogtrips_name, $ogtrips_icon ] ) {
		$ogtrips_term = get_term_by( 'slug', $ogtrips_slug, $ogtrips_taxonomy );
		$ogtrips_id   = $ogtrips_term ? (int) $ogtrips_term->term_id : 0;

		if ( ! $ogtrips_id ) {
			$ogtrips_new = wp_insert_term( $ogtrips_name, $ogtrips_taxonomy, [ 'slug' => $ogtrips_slug ] );
			if ( is_wp_error( $ogtrips_new ) ) {
				WP_CLI::error( $ogtrips_new->get_error_message() );
			}
			$ogtrips_id = (int) $ogtrips_new['term_id'];
			WP_CLI::log( "Created {$ogtrips_taxonomy}: {$ogtrips_name}" );
		}

		if ( $ogtrips_icon && ! get_term_meta( $ogtrips_id, 'icon', true ) ) {
			update_term_meta( $ogtrips_id, 'icon', $ogtrips_icon );
			update_term_meta( $ogtrips_id, '_icon', 'field_ogt_trip_type_icon' );
		}
	}
}

WP_CLI::success( 'Starter terms in place.' );
