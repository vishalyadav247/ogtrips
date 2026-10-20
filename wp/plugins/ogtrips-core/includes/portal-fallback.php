<?php
/**
 * Minimal portal output used only when the active theme has no portal.php.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

get_header();

echo '<main style="max-width:720px;margin:60px auto;padding:0 20px;font-family:sans-serif">';
echo '<h1>' . esc_html__( 'My trips', 'ogtrips-core' ) . '</h1>';

foreach ( ogtrips_core_client_bookings( get_current_user_id() ) as $ogtrips_core_booking_id ) {
	$ogtrips_core_b = ogtrips_core_booking_data( $ogtrips_core_booking_id );
	printf( '<p><strong>%s</strong> · %s · %s</p>', esc_html( $ogtrips_core_b['ref'] ), esc_html( $ogtrips_core_b['title'] ), esc_html( $ogtrips_core_b['status_label'] ) );
}

echo '</main>';

get_footer();
