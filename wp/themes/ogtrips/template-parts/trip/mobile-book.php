<?php
/**
 * Mobile sticky booking bar on the trip page.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_price = (int) ogtrips_field( 'price_from', get_queried_object_id(), 0 );
?>
<div class="mobile-book">
	<div>
		<?php if ( $ogtrips_price ) : ?>
			<small><?php esc_html_e( 'From, per person', 'ogtrips' ); ?></small><strong><?php echo esc_html( ogtrips_price( $ogtrips_price ) ); ?></strong>
		<?php endif; ?>
	</div>
	<a href="#book" class="btn btn--coral"><?php esc_html_e( 'Reserve', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
</div>
