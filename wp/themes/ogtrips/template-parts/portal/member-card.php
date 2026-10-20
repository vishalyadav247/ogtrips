<?php
/**
 * "Member club status" card for the logged-in client (tier, next-trip discount, promo code).
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_uid   = get_current_user_id();
$ogtrips_tiers = function_exists( 'ogtrips_core_member_tiers' ) ? ogtrips_core_member_tiers() : [];
$ogtrips_tier  = (string) ogtrips_field( 'member_tier', 'user_' . $ogtrips_uid, 'explorer' );
$ogtrips_disc  = (float) ogtrips_field( 'member_discount', 'user_' . $ogtrips_uid, 0 );
$ogtrips_promo = (string) ogtrips_field( 'promo_code', 'user_' . $ogtrips_uid );
$ogtrips_note  = (string) ogtrips_field( 'member_note', 'user_' . $ogtrips_uid );
$ogtrips_since = get_userdata( $ogtrips_uid );
?>
<div class="member-card" id="rewards">
	<span class="label"><?php esc_html_e( 'Member club status', 'ogtrips' ); ?></span>
	<strong class="tier"><?php echo esc_html( ( $ogtrips_tiers[ $ogtrips_tier ] ?? __( 'Explorer', 'ogtrips' ) ) ); ?> <?php esc_html_e( 'tier', 'ogtrips' ); ?></strong>
	<?php if ( $ogtrips_disc > 0 ) : ?>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: discount percentage */
					__( 'Your next trip qualifies for a <b>%s%% loyalty discount</b> — just mention it when you book again.', 'ogtrips' ),
					esc_html( rtrim( rtrim( number_format_i18n( $ogtrips_disc, 1 ), '0' ), '.' ) )
				),
				[ 'b' => [] ]
			);
			?>
		</p>
	<?php else : ?>
		<p><?php esc_html_e( 'Travel with us again and your loyalty discount unlocks automatically on your next booking.', 'ogtrips' ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $ogtrips_promo ) : ?>
		<span class="promo"><?php esc_html_e( 'Active promo:', 'ogtrips' ); ?> <code><?php echo esc_html( $ogtrips_promo ); ?></code></span>
	<?php endif; ?>
	<?php if ( '' !== $ogtrips_note ) : ?>
		<small><?php echo esc_html( $ogtrips_note ); ?></small>
	<?php endif; ?>
	<?php if ( $ogtrips_since ) : ?>
		<small>
			<?php
			/* translators: %s: month and year */
			echo esc_html( sprintf( __( 'Member since %s', 'ogtrips' ), wp_date( 'F Y', strtotime( $ogtrips_since->user_registered ) ) ) );
			?>
		</small>
	<?php endif; ?>
</div>
