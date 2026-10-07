<?php
/**
 * Contact section with the "Plan my trip" enquiry form (homepage, 404, listings).
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_phone = (string) ogtrips_setting( 'phone' );
$ogtrips_wa    = (string) ogtrips_setting( 'whatsapp' );
$ogtrips_email = (string) ogtrips_setting( 'email' );
$ogtrips_addr  = (string) ogtrips_setting( 'address' );
$ogtrips_map   = (string) ogtrips_setting( 'office_map_url' );
$ogtrips_dests = get_terms(
	[
		'taxonomy'   => 'ogt_destination',
		'hide_empty' => false,
	]
);
$ogtrips_types = get_terms(
	[
		'taxonomy'   => 'ogt_trip_type',
		'hide_empty' => false,
		'orderby'    => 'term_order',
	]
);
$ogtrips_opt   = static function ( $key, $default ) {
	return (string) ogtrips_field( $key, 'option', $default );
};

$ogtrips_lines = [];
if ( '' !== $ogtrips_wa ) {
	$ogtrips_lines[] = [ ogtrips_whatsapp_url( $ogtrips_wa, __( 'Hi OgTrips! I would like help planning a trip.', 'ogtrips' ) ), '#25d366', 'message-circle', __( 'WhatsApp', 'ogtrips' ), $ogtrips_wa ];
}
if ( '' !== $ogtrips_phone ) {
	$ogtrips_lines[] = [ 'tel:' . ogtrips_phone_digits( $ogtrips_phone ), 'var(--coral)', 'phone', __( 'Call us', 'ogtrips' ), $ogtrips_phone ];
}
if ( '' !== $ogtrips_email ) {
	$ogtrips_lines[] = [ 'mailto:' . antispambot( $ogtrips_email ), 'var(--teal)', 'mail', __( 'Email', 'ogtrips' ), antispambot( $ogtrips_email ) ];
}
if ( '' !== $ogtrips_addr ) {
	$ogtrips_lines[] = [ '' !== $ogtrips_map ? $ogtrips_map : '#contact', 'var(--navy)', 'map-pin', __( 'Visit', 'ogtrips' ), $ogtrips_addr ];
}
?>
<section class="contact" id="contact">
	<span class="sunball" aria-hidden="true"></span>
	<span class="cloud" style="top:12%;left:12%" aria-hidden="true"></span>
	<span class="cloud" style="top:26%;left:44%;transform:scale(.7);animation-duration:40s" aria-hidden="true"></span>
	<svg class="waves" viewBox="0 0 1440 160" preserveAspectRatio="none" aria-hidden="true">
		<path d="M0 60 C 240 10, 480 110, 720 60 S 1200 10, 1440 60 V160 H0 Z" fill="#00B3BF"/>
		<path d="M0 100 C 260 60, 520 140, 760 100 S 1220 60, 1440 100 V160 H0 Z" fill="#0094a0"/>
	</svg>
	<div class="container">
		<div class="reveal">
			<span class="label" style="color:var(--navy)"><?php echo esc_html( $ogtrips_opt( 'contact_label', __( 'Contact us', 'ogtrips' ) ) ); ?></span>
			<h2 style="margin-top:18px"><?php echo wp_kses( preg_replace( '#</em>\s+#', '</em><br>', ogtrips_em( $ogtrips_opt( 'contact_heading', __( "Where to *next?* Let's talk.", 'ogtrips' ) ) ) ), [ 'em' => [], 'br' => [] ] ); ?></h2>
			<p class="lead" style="margin-top:18px;max-width:440px"><?php echo esc_html( $ogtrips_opt( 'contact_lead', __( 'Tell us your dates and dream — get a free, personalised itinerary within 24 hours.', 'ogtrips' ) ) ); ?></p>
			<?php if ( $ogtrips_lines ) : ?>
				<div class="contact-list">
					<?php foreach ( $ogtrips_lines as $ogtrips_line ) : ?>
						<a href="<?php echo esc_url( $ogtrips_line[0], [ 'http', 'https', 'tel', 'mailto' ] ); ?>"<?php echo 0 === strpos( $ogtrips_line[0], 'http' ) ? ' target="_blank" rel="noopener"' : ''; ?>><span class="ic" style="background:<?php echo esc_attr( $ogtrips_line[1] ); ?>"><?php echo ogtrips_icon( $ogtrips_line[2] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span><small><?php echo esc_html( $ogtrips_line[3] ); ?></small><strong><?php echo esc_html( $ogtrips_line[4] ); ?></strong></span></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="form-card reveal reveal-d1">
			<h3><?php echo esc_html( $ogtrips_opt( 'form_title', __( 'Plan my trip', 'ogtrips' ) ) ); ?></h3>
			<p class="muted" style="margin:0;font-size:.92rem"><?php echo esc_html( $ogtrips_opt( 'form_subtitle', __( 'A trip expert replies within one working day.', 'ogtrips' ) ) ); ?></p>
			<?php echo ogtrips_enquiry_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php ogtrips_enquiry_hidden_fields( 'contact' ); ?>
				<div class="input"><input id="c-name" name="name" type="text" placeholder=" " required autocomplete="name"><label for="c-name"><?php esc_html_e( 'Full name', 'ogtrips' ); ?></label></div>
				<div class="input"><input id="c-phone" name="phone" type="tel" placeholder=" " required autocomplete="tel"><label for="c-phone"><?php esc_html_e( 'Phone / WhatsApp', 'ogtrips' ); ?></label></div>
				<div class="input"><select id="c-dest" name="destination">
					<option value="<?php esc_attr_e( 'Not sure yet', 'ogtrips' ); ?>"><?php esc_html_e( 'Not sure yet', 'ogtrips' ); ?></option>
					<?php if ( ! is_wp_error( $ogtrips_dests ) ) : ?>
						<?php foreach ( $ogtrips_dests as $ogtrips_term ) : ?>
							<option value="<?php echo esc_attr( $ogtrips_term->name ); ?>"><?php echo esc_html( $ogtrips_term->name ); ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select><label for="c-dest"><?php esc_html_e( 'Destination', 'ogtrips' ); ?></label></div>
				<div class="input"><input id="c-date" name="travel_date" type="date" placeholder=" " min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"><label for="c-date"><?php esc_html_e( 'Travel date', 'ogtrips' ); ?></label></div>
				<?php if ( ! is_wp_error( $ogtrips_types ) && $ogtrips_types ) : ?>
					<div class="full trip-types" role="group" aria-label="<?php esc_attr_e( 'Trip type', 'ogtrips' ); ?>">
						<?php foreach ( array_slice( $ogtrips_types, 0, 4 ) as $ogtrips_i => $ogtrips_term ) : ?>
							<label><input type="radio" name="trip_type" value="<?php echo esc_attr( $ogtrips_term->name ); ?>"<?php checked( 0, $ogtrips_i ); ?>><span><?php echo ogtrips_icon( ogtrips_safe_icon( (string) get_term_meta( $ogtrips_term->term_id, 'icon', true ), 'sparkles' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_term->name ); ?></span></label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<div class="input full"><textarea id="c-msg" name="message" placeholder=" "></textarea><label for="c-msg"><?php esc_html_e( 'Budget, interests, special occasions…', 'ogtrips' ); ?></label></div>
				<div class="full"><button type="submit" name="channel" value="email" class="btn btn--coral btn--block"><?php esc_html_e( 'Get my free itinerary', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></button></div>
				<?php if ( '' !== $ogtrips_wa ) : ?>
					<div class="full"><button type="submit" name="channel" value="whatsapp" class="btn btn--ghost btn--block btn--wa"><?php echo ogtrips_icon( 'message-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Or send it on WhatsApp', 'ogtrips' ); ?></button></div>
				<?php endif; ?>
				<p class="form-note full"><?php echo ogtrips_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'We never share your details. No spam, ever.', 'ogtrips' ); ?></p>
			</form>
		</div>
	</div>
</section>
