<?php
/**
 * Trip booking aside: price, enquiry form (email + WhatsApp), PDF, trip expert.
 *
 * Args: trip (array) from ogtrips_trip_card_data().
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_t      = $args['trip'];
$ogtrips_id     = (int) $ogtrips_t['id'];
$ogtrips_offer  = $ogtrips_t['price'] ? (string) ogtrips_field( 'offer_label', $ogtrips_id ) : '';
$ogtrips_deps   = (array) ogtrips_field( 'departures', $ogtrips_id, [] );
$ogtrips_pdf    = (int) ogtrips_field( 'pdf_itinerary', $ogtrips_id, 0 );
$ogtrips_expert = (int) ogtrips_field( 'expert', $ogtrips_id, 0 );
$ogtrips_trust  = (string) ogtrips_setting( 'booking_trust_text', __( 'No payment now · Free cancellation', 'ogtrips' ) );
$ogtrips_save   = $ogtrips_t['orig'] > $ogtrips_t['price'] ? $ogtrips_t['orig'] - $ogtrips_t['price'] : 0;
$ogtrips_today  = wp_date( 'Y-m-d' );
?>
<aside class="aside" id="book">
	<div class="book">
		<?php if ( $ogtrips_t['price'] ) : ?>
			<small class="muted"><?php esc_html_e( 'Starting from, per person', 'ogtrips' ); ?></small>
			<div class="book-price"><strong><?php echo esc_html( ogtrips_price( $ogtrips_t['price'] ) ); ?></strong><?php if ( $ogtrips_save ) : ?><s><?php echo esc_html( ogtrips_price( $ogtrips_t['orig'] ) ); ?></s><?php endif; ?></div>
			<?php if ( $ogtrips_save || '' !== $ogtrips_offer ) : ?>
				<span class="save">
					<?php
					$ogtrips_bits = array_filter(
						[
							$ogtrips_offer,
							/* translators: %s: amount saved */
							$ogtrips_save ? sprintf( __( 'save %s', 'ogtrips' ), ogtrips_price( $ogtrips_save ) ) : '',
						]
					);
					echo esc_html( implode( ' · ', $ogtrips_bits ) );
					?>
				</span>
			<?php endif; ?>
		<?php else : ?>
			<small class="muted"><?php esc_html_e( 'Personalised for your dates', 'ogtrips' ); ?></small>
			<div class="book-price book-price--quote"><strong><?php esc_html_e( 'Price on request', 'ogtrips' ); ?></strong></div>
		<?php endif; ?>

		<?php echo ogtrips_enquiry_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php ogtrips_enquiry_hidden_fields( 'booking', $ogtrips_id ); ?>
			<div class="input"><select id="b-date" name="departure">
				<?php foreach ( $ogtrips_deps as $ogtrips_dep ) : ?>
					<?php
					$ogtrips_date = (string) ( $ogtrips_dep['date'] ?? '' );
					if ( '' === $ogtrips_date || $ogtrips_date < $ogtrips_today ) {
						continue;
					}
					$ogtrips_seats = (int) ( $ogtrips_dep['seats_left'] ?? 0 );
					$ogtrips_label = wp_date( 'd M Y', strtotime( $ogtrips_date ) );
					if ( $ogtrips_seats ) {
						/* translators: %d: seats left */
						$ogtrips_label .= ' · ' . sprintf( _n( '%d seat left', '%d seats left', $ogtrips_seats, 'ogtrips' ), $ogtrips_seats );
					}
					?>
					<option value="<?php echo esc_attr( $ogtrips_label ); ?>"><?php echo esc_html( $ogtrips_label ); ?></option>
				<?php endforeach; ?>
				<option value="<?php esc_attr_e( 'Private — my own dates', 'ogtrips' ); ?>"><?php esc_html_e( 'Private — my own dates', 'ogtrips' ); ?></option>
			</select><label for="b-date"><?php esc_html_e( 'Departure', 'ogtrips' ); ?></label></div>
			<div class="stepper"><div><small><?php esc_html_e( 'Travellers', 'ogtrips' ); ?></small><strong id="pax" data-price="<?php echo esc_attr( (string) $ogtrips_t['price'] ); ?>">2</strong></div>
				<input type="hidden" name="travellers" value="2" id="pax-input">
				<div class="ctrl"><button type="button" data-step="minus" aria-label="<?php esc_attr_e( 'Fewer', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'minus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button><button type="button" data-step="plus" aria-label="<?php esc_attr_e( 'More', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button></div>
			</div>
			<div class="input"><input id="b-name" name="name" type="text" placeholder=" " autocomplete="name"><label for="b-name"><?php esc_html_e( 'Your name', 'ogtrips' ); ?></label></div>
			<div class="input"><input id="b-phone" name="phone" type="tel" placeholder=" " required autocomplete="tel"><label for="b-phone"><?php esc_html_e( 'Phone / WhatsApp', 'ogtrips' ); ?></label></div>
			<?php if ( $ogtrips_t['price'] ) : ?>
				<div class="total"><span class="muted"><?php esc_html_e( 'Estimated total', 'ogtrips' ); ?></span><strong id="total"><?php echo esc_html( ogtrips_price( $ogtrips_t['price'] * 2 ) ); ?></strong></div>
			<?php endif; ?>
			<button type="submit" name="channel" value="email" class="btn btn--coral btn--block"><?php esc_html_e( 'Reserve — pay later', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></button>
			<?php if ( '' !== (string) ogtrips_setting( 'whatsapp' ) ) : ?>
				<button type="submit" name="channel" value="whatsapp" class="btn btn--ghost btn--block btn--wa" style="justify-content:center"><?php echo ogtrips_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Send on WhatsApp', 'ogtrips' ); ?></button>
			<?php endif; ?>
		</form>
		<?php if ( $ogtrips_pdf && wp_get_attachment_url( $ogtrips_pdf ) ) : ?>
			<a href="<?php echo esc_url( (string) wp_get_attachment_url( $ogtrips_pdf ) ); ?>" class="btn btn--ghost btn--block" style="justify-content:center" download><?php echo ogtrips_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Download PDF itinerary', 'ogtrips' ); ?></a>
		<?php endif; ?>
		<?php if ( '' !== $ogtrips_trust ) : ?>
			<div class="trust"><?php echo ogtrips_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_trust ); ?></div>
		<?php endif; ?>
	</div>

	<?php if ( $ogtrips_expert && get_userdata( $ogtrips_expert ) ) : ?>
		<?php
		$ogtrips_user  = get_userdata( $ogtrips_expert );
		$ogtrips_first = $ogtrips_user->first_name ? $ogtrips_user->first_name : $ogtrips_user->display_name;
		$ogtrips_sub   = array_filter(
			[
				(string) get_user_meta( $ogtrips_expert, 'specialty', true ),
				(string) get_user_meta( $ogtrips_expert, 'reply_time', true ),
			]
		);
		$ogtrips_wa    = (string) get_user_meta( $ogtrips_expert, 'whatsapp', true );
		$ogtrips_wa    = '' !== $ogtrips_wa ? $ogtrips_wa : (string) ogtrips_setting( 'whatsapp' );
		/* translators: %s: trip name */
		$ogtrips_wa_url = ogtrips_whatsapp_url( $ogtrips_wa, sprintf( __( 'Hi! I have a question about the %s trip.', 'ogtrips' ), $ogtrips_t['title'] ) );
		?>
		<div class="expert">
			<?php echo get_avatar( $ogtrips_expert, 120, '', '', [ 'loading' => 'lazy' ] ); ?>
			<div><strong>
				<?php
				/* translators: %s: expert first name */
				echo esc_html( sprintf( __( 'Talk to %s', 'ogtrips' ), $ogtrips_first ) );
				?>
			</strong><small><?php echo esc_html( implode( ' · ', $ogtrips_sub ) ); ?></small></div>
			<?php if ( '' !== $ogtrips_wa_url ) : ?>
				<a href="<?php echo esc_url( $ogtrips_wa_url ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'WhatsApp', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</aside>
