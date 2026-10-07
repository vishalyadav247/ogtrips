<?php
/**
 * Logo: the image uploaded in Site Settings → Logo, or the built-in mark + "Trıps" wordmark.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_logo_id = (int) ogtrips_setting( 'logo', 0 );
?>
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo<?php echo $ogtrips_logo_id ? ' logo--custom' : ''; ?>" aria-label="<?php esc_attr_e( 'OgTrips home', 'ogtrips' ); ?>">
	<?php if ( $ogtrips_logo_id && wp_get_attachment_image_url( $ogtrips_logo_id, 'medium' ) ) : ?>
		<?php echo wp_get_attachment_image( $ogtrips_logo_id, 'medium', false, [ 'alt' => get_bloginfo( 'name' ), 'loading' => 'eager', 'decoding' => 'async' ] ); ?>
	<?php else : ?>
		<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/ogtrips-mark.svg' ) ); ?>" alt="" width="40" height="40"><span>Tr<span class="i-pin">ı</span>ps</span>
	<?php endif; ?>
</a>
