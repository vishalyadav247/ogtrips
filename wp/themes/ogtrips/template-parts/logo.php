<?php
/**
 * Logo (mark + "Trıps" wordmark), as in the design.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;
?>
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo" aria-label="<?php esc_attr_e( 'OgTrips home', 'ogtrips' ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/ogtrips-mark.svg' ) ); ?>" alt="" width="40" height="40"><span>Tr<span class="i-pin">ı</span>ps</span></a>
