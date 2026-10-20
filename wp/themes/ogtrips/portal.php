<?php
/**
 * Client portal ("My trips"): /my-trips/ lists the logged-in client's bookings,
 * /my-trips/{id}/ shows one booking. Routed by ogtrips-core (template_include); the
 * plugin has already required a login and checked the booking belongs to this client.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ogtrips_screen = function_exists( 'ogtrips_core_portal_screen' ) ? ogtrips_core_portal_screen() : 'list';

if ( 'booking' === $ogtrips_screen && function_exists( 'ogtrips_core_booking_data' ) ) {
	get_template_part( 'template-parts/portal/booking', null, [ 'id' => (int) get_query_var( 'ogt_booking' ) ] );
} else {
	get_template_part( 'template-parts/portal/list' );
}

get_footer();
