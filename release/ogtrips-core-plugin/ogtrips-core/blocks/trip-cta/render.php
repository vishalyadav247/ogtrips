<?php
/**
 * Trip CTA block output — markup from the approved guide.html (.trip-cta); styled by the theme.
 *
 * SCF provides: $block, $content, $is_preview, $post_id.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_trip_id = (int) get_field( 'itinerary' );
$ogtrips_trip    = $ogtrips_trip_id ? get_post( $ogtrips_trip_id ) : null;

if ( ! $ogtrips_trip || 'ogt_itinerary' !== $ogtrips_trip->post_type || ( 'publish' !== $ogtrips_trip->post_status && empty( $is_preview ) ) ) {
	if ( ! empty( $is_preview ) ) {
		echo '<p class="ogtrips-block-placeholder">' . esc_html__( 'Trip CTA: choose a trip in the block settings on the right.', 'ogtrips-core' ) . '</p>';
	}
	return;
}

$ogtrips_heading = (string) get_field( 'heading' );
$ogtrips_text    = (string) get_field( 'text' );
$ogtrips_days    = (int) get_field( 'duration_days', $ogtrips_trip_id );
$ogtrips_heading = '' !== $ogtrips_heading ? $ogtrips_heading : get_the_title( $ogtrips_trip );
?>
<div class="trip-cta">
	<?php echo get_the_post_thumbnail( $ogtrips_trip, 'ogt-thumb', [ 'alt' => '', 'loading' => 'lazy' ] ); ?>
	<div>
		<?php if ( $ogtrips_days ) : ?>
			<span class="chip chip--sun">
				<?php
				/* translators: %d: number of days */
				echo esc_html( sprintf( _n( 'OG Trip · %d day', 'OG Trip · %d days', $ogtrips_days, 'ogtrips-core' ), $ogtrips_days ) );
				?>
			</span>
		<?php endif; ?>
		<h4><?php echo esc_html( $ogtrips_heading ); ?></h4>
		<?php if ( '' !== $ogtrips_text ) : ?>
			<p><?php echo esc_html( $ogtrips_text ); ?></p>
		<?php endif; ?>
		<a href="<?php echo esc_url( get_permalink( $ogtrips_trip ) ); ?>" class="btn btn--coral">
			<?php esc_html_e( 'See the itinerary', 'ogtrips-core' ); ?>
			<span class="arrow"><?php echo function_exists( 'ogtrips_icon' ) ? ogtrips_icon( 'arrow-up-right' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ogtrips_icon() escapes. ?></span>
		</a>
	</div>
</div>
