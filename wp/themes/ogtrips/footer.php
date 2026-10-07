<?php
/**
 * Site footer — the approved design's footer, filled from Site Settings and the footer menus.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_blurb   = (string) ogtrips_setting( 'footer_blurb', __( "Trips planned by people who've actually been there. Handpicked stays, honest prices, 24/7 support.", 'ogtrips' ) );
$ogtrips_phone   = (string) ogtrips_setting( 'phone' );
$ogtrips_email   = (string) ogtrips_setting( 'email' );
$ogtrips_address = (string) ogtrips_setting( 'address' );
$ogtrips_socials = [
	'instagram' => [ (string) ogtrips_setting( 'instagram_url' ), __( 'Instagram', 'ogtrips' ) ],
	'facebook'  => [ (string) ogtrips_setting( 'facebook_url' ), __( 'Facebook', 'ogtrips' ) ],
	'youtube'   => [ (string) ogtrips_setting( 'youtube_url' ), __( 'YouTube', 'ogtrips' ) ],
];
?>
</main>

<?php if ( is_singular( 'ogt_itinerary' ) ) : ?>
	<?php get_template_part( 'template-parts/trip/mobile-book' ); ?>
<?php endif; ?>

<footer class="footer">
	<div class="container">
		<div class="footer-top">
			<div>
				<?php get_template_part( 'template-parts/logo' ); ?>
				<p class="muted" style="margin-top:16px;max-width:340px"><?php echo esc_html( $ogtrips_blurb ); ?></p>
				<?php if ( ogtrips_field( 'newsletter_enabled', 'option', false ) ) : ?>
					<form class="news" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php ogtrips_enquiry_hidden_fields( 'newsletter' ); ?>
						<input type="email" name="email" required placeholder="<?php esc_attr_e( 'Get travel deals by email', 'ogtrips' ); ?>" aria-label="<?php esc_attr_e( 'Email', 'ogtrips' ); ?>">
						<button type="submit" class="btn btn--plain"><?php esc_html_e( 'Subscribe', 'ogtrips' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
			<div><h4><?php esc_html_e( 'Explore', 'ogtrips' ); ?></h4><?php ogtrips_menu( 'footer-explore', '' ); ?></div>
			<div><h4><?php esc_html_e( 'Support', 'ogtrips' ); ?></h4><?php ogtrips_menu( 'footer-support', '' ); ?></div>
			<div><h4><?php esc_html_e( 'Say hello', 'ogtrips' ); ?></h4>
				<ul>
					<?php if ( '' !== $ogtrips_phone ) : ?>
						<li><a href="tel:<?php echo esc_attr( ogtrips_phone_digits( $ogtrips_phone ) ); ?>"><?php echo esc_html( $ogtrips_phone ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $ogtrips_email ) : ?>
						<li><a href="mailto:<?php echo esc_attr( antispambot( $ogtrips_email ) ); ?>"><?php echo esc_html( antispambot( $ogtrips_email ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $ogtrips_address ) : ?>
						<li><?php echo esc_html( $ogtrips_address ); ?></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
		<p class="wordmark" aria-hidden="true"><?php echo wp_kses( __( 'Where to <em>next</em><span class="q">?</span>', 'ogtrips' ), [ 'em' => [], 'span' => [ 'class' => [] ] ] ); ?></p>
		<div class="footer-bottom">
			<span>
				<?php
				/* translators: %s: year */
				echo esc_html( sprintf( __( '© %s OgTrips. All rights reserved.', 'ogtrips' ), wp_date( 'Y' ) ) );
				?>
			</span>
			<div class="socials">
				<?php foreach ( $ogtrips_socials as $ogtrips_icon_name => $ogtrips_social ) : ?>
					<?php if ( '' !== $ogtrips_social[0] ) : ?>
						<a href="<?php echo esc_url( $ogtrips_social[0] ); ?>" aria-label="<?php echo esc_attr( $ogtrips_social[1] ); ?>" rel="noopener" target="_blank"><?php echo ogtrips_icon( $ogtrips_icon_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</footer>

<?php
$ogtrips_wa_float = function_exists( 'get_field' ) ? get_field( 'whatsapp_float', 'option' ) : false;
$ogtrips_wa_text  = is_singular( 'ogt_itinerary' )
	/* translators: %s: trip name */
	? sprintf( __( 'Hi OgTrips! I have a question about the %s trip.', 'ogtrips' ), str_replace( '*', '', get_post_field( 'post_title', get_queried_object_id() ) ) )
	: __( 'Hi OgTrips! I would like help planning a trip.', 'ogtrips' );
$ogtrips_wa_link  = ( null === $ogtrips_wa_float || $ogtrips_wa_float ) ? ogtrips_whatsapp_url( (string) ogtrips_setting( 'whatsapp' ), $ogtrips_wa_text ) : '';
?>
<?php if ( '' !== $ogtrips_wa_link ) : ?>
<a href="<?php echo esc_url( $ogtrips_wa_link ); ?>" class="wa-float" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Chat with us on WhatsApp', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
