<?php
/**
 * Large trip card (homepage "Most-loved trips", trips archive). Expects the current post in the loop.
 *
 * Args: delay (int) reveal delay index, rank (string) optional "#1 Bestseller" override.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_t     = ogtrips_trip_card_data( get_post() );
$ogtrips_delay = (int) ( $args['delay'] ?? 0 );
$ogtrips_rank  = (string) ( $args['rank'] ?? '' );
$ogtrips_badge = '' !== $ogtrips_rank ? $ogtrips_rank : $ogtrips_t['badge'];
$ogtrips_sty   = '' !== $ogtrips_rank ? 'sun' : $ogtrips_t['badge_sty'];
$ogtrips_chip  = [ 'sun' => ' chip--sun', 'coral' => ' chip--coral' ][ $ogtrips_sty ] ?? '';
?>
<article class="best-card reveal<?php echo $ogtrips_delay ? ' reveal-d' . (int) $ogtrips_delay : ''; ?>">
	<a href="<?php echo esc_url( $ogtrips_t['url'] ); ?>" class="media" tabindex="-1" aria-hidden="true">
		<?php echo get_the_post_thumbnail( $ogtrips_t['id'], 'ogt-card', [ 'loading' => 'lazy', 'alt' => '' ] ); ?>
		<?php if ( '' !== $ogtrips_badge ) : ?>
			<span class="chip<?php echo esc_attr( $ogtrips_chip ); ?> rank"><?php echo ( '' === $ogtrips_rank && $ogtrips_t['badge_ic'] ) ? ogtrips_icon( ogtrips_safe_icon( $ogtrips_t['badge_ic'] ) ) . ' ' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $ogtrips_badge ); ?></span>
		<?php endif; ?>
	</a>
	<button class="fav" type="button" aria-label="<?php esc_attr_e( 'Save trip', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
	<div class="body">
		<?php if ( '' !== $ogtrips_t['location'] ) : ?>
			<span class="where"><?php echo ogtrips_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_t['location'] ); ?></span>
		<?php endif; ?>
		<h3><a href="<?php echo esc_url( $ogtrips_t['url'] ); ?>"><?php echo esc_html( $ogtrips_t['title'] ); ?></a></h3>
		<div class="facts">
			<?php if ( $ogtrips_t['days'] ) : ?>
				<span><?php echo ogtrips_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_t['days'] . 'D / ' . $ogtrips_t['nights'] . 'N' ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $ogtrips_t['rating'] ) : ?>
				<span><?php echo ogtrips_icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( number_format_i18n( (float) $ogtrips_t['rating'], 1 ) . ( $ogtrips_t['reviews'] ? ' (' . $ogtrips_t['reviews'] . ')' : '' ) ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $ogtrips_t['group'] ) : ?>
				<span><?php echo ogtrips_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_t['group'] ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( $ogtrips_t['incl'] ) : ?>
			<div class="incl-mini" aria-hidden="true">
				<?php foreach ( array_slice( $ogtrips_t['incl'], 0, 4 ) as $ogtrips_ic ) : ?>
					<span><?php echo ogtrips_icon( ogtrips_safe_icon( $ogtrips_ic ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="foot">
			<?php if ( $ogtrips_t['price'] ) : ?>
				<div class="price"><small><?php esc_html_e( 'from / person', 'ogtrips' ); ?></small><strong><?php echo esc_html( ogtrips_price( $ogtrips_t['price'] ) ); ?></strong><?php if ( $ogtrips_t['orig'] > $ogtrips_t['price'] ) : ?><s><?php echo esc_html( ogtrips_price( $ogtrips_t['orig'] ) ); ?></s><?php endif; ?></div>
			<?php endif; ?>
			<a href="<?php echo esc_url( $ogtrips_t['url'] . '#book' ); ?>" class="btn"><?php esc_html_e( 'Reserve', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
		</div>
	</div>
</article>
