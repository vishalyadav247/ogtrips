<?php
/**
 * Portal home: the client's bookings, member club card and account box.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_user     = wp_get_current_user();
$ogtrips_first    = '' !== $ogtrips_user->first_name ? $ogtrips_user->first_name : $ogtrips_user->display_name;
$ogtrips_client   = function_exists( 'ogtrips_core_is_client' ) && ogtrips_core_is_client( $ogtrips_user );
$ogtrips_bookings = function_exists( 'ogtrips_core_client_bookings' ) ? ogtrips_core_client_bookings( $ogtrips_user->ID ) : [];
$ogtrips_saved    = isset( $_GET['saved'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
$ogtrips_phone    = (string) get_user_meta( $ogtrips_user->ID, 'client_phone', true );
?>
<header class="article-head container listing-head portal-head">
	<?php ogtrips_breadcrumbs(); ?>
	<span class="label reveal"><?php esc_html_e( 'My trips', 'ogtrips' ); ?></span>
	<h1 class="reveal">
		<?php
		/* translators: %s: first name */
		echo ogtrips_em( sprintf( __( 'Welcome back, *%s*', 'ogtrips' ), $ogtrips_first ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by ogtrips_em().
		?>
	</h1>
	<p class="lead reveal"><?php esc_html_e( 'Your confirmed journeys, personalised schedules, vouchers and member rewards — all in one place.', 'ogtrips' ); ?></p>
</header>

<section class="section listing-body">
	<div class="container portal-layout">
		<div>
			<?php if ( ! $ogtrips_client ) : ?>
				<div class="form-msg is-ok" style="margin:0 0 24px"><?php esc_html_e( 'You are logged in as a team member. Clients see their own bookings on this page; manage them under Bookings in the dashboard.', 'ogtrips' ); ?></div>
			<?php endif; ?>

			<?php if ( $ogtrips_bookings ) : ?>
				<div class="pcards">
					<?php foreach ( $ogtrips_bookings as $ogtrips_i => $ogtrips_bid ) : ?>
						<?php
						$ogtrips_b     = ogtrips_core_booking_data( $ogtrips_bid );
						$ogtrips_dates = array_filter( [ '' !== $ogtrips_b['start'] ? wp_date( 'j M Y', strtotime( $ogtrips_b['start'] ) ) : '', '' !== $ogtrips_b['end'] ? wp_date( 'j M Y', strtotime( $ogtrips_b['end'] ) ) : '' ] );
						$ogtrips_cur   = $ogtrips_b['curator'] ? get_userdata( $ogtrips_b['curator'] ) : false;
						?>
						<a href="<?php echo esc_url( $ogtrips_b['url'] ); ?>" class="pcard reveal<?php echo $ogtrips_i % 2 ? ' reveal-d1' : ''; ?>">
							<div class="media">
								<?php echo ogtrips_img( $ogtrips_b['cover'], 'ogt-card', [ 'alt' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span class="chip status-chip is-<?php echo esc_attr( $ogtrips_b['status'] ); ?>"><?php echo esc_html( $ogtrips_b['status_label'] ); ?></span>
							</div>
							<div class="body">
								<span class="ref"><?php echo esc_html( $ogtrips_b['ref'] ); ?><?php echo $ogtrips_b['custom'] ? ' · ' . esc_html__( 'Custom package', 'ogtrips' ) : ''; ?></span>
								<h3><?php echo esc_html( '' !== $ogtrips_b['title'] ? $ogtrips_b['title'] : $ogtrips_b['ref'] ); ?></h3>
								<div class="facts">
									<?php if ( $ogtrips_dates ) : ?>
										<span><?php echo ogtrips_icon( 'calendar-days' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( implode( ' – ', $ogtrips_dates ) ); ?></span>
									<?php endif; ?>
									<?php if ( $ogtrips_b['travellers'] ) : ?>
										<span><?php echo ogtrips_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sprintf( /* translators: %d: travellers */ _n( '%d traveller', '%d travellers', $ogtrips_b['travellers'], 'ogtrips' ), $ogtrips_b['travellers'] ) ); ?></span>
									<?php endif; ?>
									<?php if ( $ogtrips_cur ) : ?>
										<span><?php echo ogtrips_icon( 'headset' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sprintf( /* translators: %s: curator name */ __( 'Curator: %s', 'ogtrips' ), $ogtrips_cur->display_name ) ); ?></span>
									<?php endif; ?>
								</div>
								<span class="foot"><span class="btn btn--coral"><?php esc_html_e( 'Open trip', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></span></span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="portal-empty reveal">
					<?php echo ogtrips_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h3><?php esc_html_e( 'No trips yet', 'ogtrips' ); ?></h3>
					<p><?php esc_html_e( 'When you confirm a trip with us, it appears here with your schedule, vouchers and documents.', 'ogtrips' ); ?></p>
					<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'ogt_itinerary' ) ); ?>" class="btn btn--coral"><?php esc_html_e( 'Browse trips', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
				</div>
			<?php endif; ?>
		</div>

		<aside class="portal-side">
			<?php if ( $ogtrips_client ) : ?>
				<?php get_template_part( 'template-parts/portal/member-card' ); ?>
			<?php endif; ?>

			<div class="account-box" id="account">
				<span class="label"><?php esc_html_e( 'Your account', 'ogtrips' ); ?></span>
				<strong><?php echo esc_html( $ogtrips_user->display_name ); ?></strong>
				<small><?php echo esc_html( $ogtrips_user->user_email ); ?></small>
				<?php if ( $ogtrips_saved ) : ?>
					<div class="form-msg is-ok"><?php esc_html_e( 'Phone number saved.', 'ogtrips' ); ?></div>
				<?php endif; ?>
				<?php if ( $ogtrips_client ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ogtrips_client_phone">
						<?php wp_nonce_field( 'ogtrips_client_phone' ); ?>
						<div class="input"><input type="tel" id="client_phone" name="client_phone" value="<?php echo esc_attr( $ogtrips_phone ); ?>" placeholder=" " autocomplete="tel"><label for="client_phone"><?php esc_html_e( 'Phone / WhatsApp', 'ogtrips' ); ?></label></div>
						<button type="submit" class="btn btn--ghost"><?php esc_html_e( 'Save phone', 'ogtrips' ); ?></button>
					</form>
				<?php endif; ?>
				<div class="links">
					<a href="<?php echo esc_url( wp_lostpassword_url( ogtrips_core_portal_url() ) ); ?>"><?php echo ogtrips_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Change password', 'ogtrips' ); ?></a>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php echo ogtrips_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Log out', 'ogtrips' ); ?></a>
				</div>
			</div>
		</aside>
	</div>
</section>
