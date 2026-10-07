<?php
/**
 * Compact trip card ("More OG trips", trip listings). Expects the current post in the loop.
 *
 * Args: delay (int) reveal delay index.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_t     = ogtrips_trip_card_data( get_post() );
$ogtrips_delay = (int) ( $args['delay'] ?? 0 );
?>
<a href="<?php echo esc_url( $ogtrips_t['url'] ); ?>" class="tour reveal<?php echo $ogtrips_delay ? ' reveal-d' . (int) $ogtrips_delay : ''; ?>" data-cat="<?php echo esc_attr( $ogtrips_t['cat'] ); ?>">
	<div class="tour-media">
		<?php echo get_the_post_thumbnail( $ogtrips_t['id'], 'ogt-card', [ 'loading' => 'lazy', 'alt' => '' ] ); ?>
		<?php if ( $ogtrips_t['days'] ) : ?>
			<div class="chips"><span class="chip"><?php echo ogtrips_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php
				/* translators: %d: number of days */
				echo esc_html( sprintf( _n( '%d day', '%d days', $ogtrips_t['days'], 'ogtrips' ), $ogtrips_t['days'] ) );
				?>
			</span></div>
		<?php endif; ?>
	</div>
	<div class="tour-info">
		<div>
			<h3><?php echo esc_html( $ogtrips_t['short'] ); ?></h3>
			<?php if ( '' !== $ogtrips_t['location'] ) : ?>
				<div class="where"><?php echo ogtrips_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_t['location'] ); ?></div>
			<?php endif; ?>
		</div>
		<?php if ( $ogtrips_t['price'] ) : ?>
			<div class="price-tag"><small><?php esc_html_e( 'from', 'ogtrips' ); ?></small><strong><?php echo esc_html( ogtrips_price( $ogtrips_t['price'] ) ); ?></strong></div>
		<?php endif; ?>
	</div>
</a>
