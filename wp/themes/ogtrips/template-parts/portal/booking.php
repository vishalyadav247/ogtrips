<?php
/**
 * One booking in the portal: header, personalised schedule, day by day, stays, service
 * status tracker, inclusions, documents & vouchers; price, quick actions, curator and
 * member card in the sidebar. Args: id (booking ID).
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_b = ogtrips_core_booking_data( (int) ( $args['id'] ?? 0 ) );
$ogtrips_d = static function ( $date ) {
	return '' !== $date ? wp_date( 'j M Y', strtotime( $date ) ) : '';
};
$ogtrips_cur    = $ogtrips_b['curator'] ? get_userdata( $ogtrips_b['curator'] ) : false;
$ogtrips_me     = wp_get_current_user();
$ogtrips_nights = '' !== $ogtrips_b['start'] && '' !== $ogtrips_b['end'] ? max( 0, (int) round( ( strtotime( $ogtrips_b['end'] ) - strtotime( $ogtrips_b['start'] ) ) / DAY_IN_SECONDS ) ) : 0;
$ogtrips_title  = '' !== $ogtrips_b['title'] ? $ogtrips_b['title'] : $ogtrips_b['ref'];
$ogtrips_pdf    = $ogtrips_b['itinerary_pdf'] ? wp_get_attachment_url( $ogtrips_b['itinerary_pdf'] ) : '';
$ogtrips_wa     = '';
if ( $ogtrips_cur ) {
	$ogtrips_num = (string) get_user_meta( $ogtrips_cur->ID, 'whatsapp', true );
	$ogtrips_num = '' !== $ogtrips_num ? $ogtrips_num : (string) ogtrips_setting( 'whatsapp' );
	/* translators: 1: client name, 2: booking reference, 3: trip name */
	$ogtrips_wa = ogtrips_whatsapp_url( $ogtrips_num, sprintf( __( 'Hi! This is %1$s (%2$s). I have a question about my %3$s trip.', 'ogtrips' ), $ogtrips_me->display_name, $ogtrips_b['ref'], $ogtrips_title ) );
}
$ogtrips_sections = array_filter(
	[
		'overview'  => __( 'Overview', 'ogtrips' ),
		'schedule'  => $ogtrips_b['schedule'] ? __( 'Your schedule', 'ogtrips' ) : '',
		'plan'      => $ogtrips_b['days'] ? __( 'Day by day', 'ogtrips' ) : '',
		'stays'     => $ogtrips_b['stays'] ? __( 'Stays', 'ogtrips' ) : '',
		'services'  => ( $ogtrips_b['services'] || '' !== $ogtrips_b['special_requests'] ) ? __( 'Service status', 'ogtrips' ) : '',
		'included'  => ( $ogtrips_b['included'] || $ogtrips_b['excluded'] ) ? __( "What's included", 'ogtrips' ) : '',
		'documents' => ( '' !== $ogtrips_pdf || $ogtrips_b['documents'] ) ? __( 'Documents & vouchers', 'ogtrips' ) : '',
		'payment'   => $ogtrips_b['price_total'] > 0 ? __( 'Payment', 'ogtrips' ) : '',
		'rewards'   => __( 'Member rewards', 'ogtrips' ),
	]
);
$ogtrips_svc_labels = function_exists( 'ogtrips_core_service_statuses' ) ? ogtrips_core_service_statuses() : [];
?>
<section class="page-hero portal-hero">
	<div class="hero-frame">
		<div class="hero-media"><?php echo ogtrips_img( $ogtrips_b['cover'], 'ogt-hero', [ 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<div class="hero-content" style="grid-template-columns:1fr">
			<div>
				<?php ogtrips_breadcrumbs(); ?>
				<h1><?php echo esc_html( $ogtrips_title ); ?></h1>
				<div class="hero-chips">
					<span class="chip status-chip is-<?php echo esc_attr( $ogtrips_b['status'] ); ?>"><?php echo ogtrips_icon( 'badge-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_b['status_label'] ); ?></span>
					<span class="chip"><?php echo ogtrips_icon( 'ticket' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sprintf( /* translators: %s: booking reference */ __( 'Ref: %s', 'ogtrips' ), $ogtrips_b['ref'] ) ); ?></span>
					<?php if ( $ogtrips_b['custom'] ) : ?>
						<span class="chip"><?php echo ogtrips_icon( 'sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Custom package', 'ogtrips' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>

<div class="container">
	<div class="facts-bar reveal portal-facts">
		<?php if ( '' !== $ogtrips_b['start'] ) : ?>
			<div class="fact"><?php echo ogtrips_icon( 'calendar-days' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><small><?php esc_html_e( 'Travel dates', 'ogtrips' ); ?></small><strong><?php echo esc_html( $ogtrips_d( $ogtrips_b['start'] ) . ( '' !== $ogtrips_b['end'] ? ' – ' . $ogtrips_d( $ogtrips_b['end'] ) : '' ) ); ?></strong></div></div>
		<?php endif; ?>
		<?php if ( $ogtrips_nights ) : ?>
			<div class="fact"><?php echo ogtrips_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><small><?php esc_html_e( 'Duration', 'ogtrips' ); ?></small><strong><?php echo esc_html( sprintf( /* translators: 1: days, 2: nights */ __( '%1$s · %2$s', 'ogtrips' ), sprintf( _n( '%d day', '%d days', $ogtrips_nights + 1, 'ogtrips' ), $ogtrips_nights + 1 ), sprintf( _n( '%d night', '%d nights', $ogtrips_nights, 'ogtrips' ), $ogtrips_nights ) ) ); ?></strong></div></div>
		<?php endif; ?>
		<?php if ( $ogtrips_b['travellers'] ) : ?>
			<div class="fact"><?php echo ogtrips_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><small><?php esc_html_e( 'Travellers', 'ogtrips' ); ?></small><strong><?php echo esc_html( number_format_i18n( $ogtrips_b['travellers'] ) ); ?></strong></div></div>
		<?php endif; ?>
		<?php if ( '' !== $ogtrips_b['pickup'] ) : ?>
			<div class="fact"><?php echo ogtrips_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><small><?php esc_html_e( 'Pick-up', 'ogtrips' ); ?></small><strong><?php echo esc_html( $ogtrips_b['pickup'] ); ?></strong></div></div>
		<?php endif; ?>
		<?php if ( $ogtrips_cur ) : ?>
			<div class="fact"><?php echo ogtrips_icon( 'headset' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><small><?php esc_html_e( 'Assigned curator', 'ogtrips' ); ?></small><strong><?php echo esc_html( $ogtrips_cur->display_name ); ?><?php echo '' !== $ogtrips_wa ? ' <span class="wa-on">' . esc_html__( 'WhatsApp active', 'ogtrips' ) . '</span>' : ''; ?></strong></div></div>
		<?php endif; ?>
	</div>

	<nav class="subnav" aria-label="<?php esc_attr_e( 'Sections', 'ogtrips' ); ?>">
		<?php $ogtrips_first = true; ?>
		<?php foreach ( $ogtrips_sections as $ogtrips_anchor => $ogtrips_label ) : ?>
			<a href="#<?php echo esc_attr( $ogtrips_anchor ); ?>"<?php echo $ogtrips_first ? ' class="is-active"' : ''; ?>><?php echo esc_html( $ogtrips_label ); ?></a>
			<?php $ogtrips_first = false; ?>
		<?php endforeach; ?>
	</nav>

	<div class="layout portal-booking">
		<div>
			<div class="block" id="overview">
				<?php if ( '' !== $ogtrips_b['client_note'] ) : ?>
					<div class="client-note reveal"><?php echo ogtrips_icon( 'message-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><strong><?php esc_html_e( 'A note from your trip expert', 'ogtrips' ); ?></strong><?php echo wp_kses( wpautop( $ogtrips_b['client_note'] ), [ 'p' => [], 'br' => [] ] ); ?></div></div>
				<?php endif; ?>
				<h2><?php echo wp_kses( __( 'Your <em>trip</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
				<?php if ( '' !== $ogtrips_b['summary'] ) : ?>
					<div class="lead"><?php echo wp_kses( wpautop( $ogtrips_b['summary'] ), [ 'p' => [], 'br' => [] ] ); ?></div>
				<?php endif; ?>
				<?php if ( '' !== $ogtrips_b['trip_url'] ) : ?>
					<p style="margin-top:18px"><a class="link-arrow" href="<?php echo esc_url( $ogtrips_b['trip_url'] ); ?>"><?php esc_html_e( 'See this trip on the website', 'ogtrips' ); ?> <?php echo ogtrips_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></p>
				<?php endif; ?>
			</div>

			<?php if ( $ogtrips_b['schedule'] ) : ?>
				<div class="block" id="schedule">
					<h2><?php echo wp_kses( __( 'Your personalised <em>schedule</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
					<ol class="timeline">
						<?php foreach ( $ogtrips_b['schedule'] as $ogtrips_row ) : ?>
							<li class="reveal">
								<span class="when"><?php echo esc_html( sprintf( /* translators: %d: day number */ __( 'Day %d', 'ogtrips' ), (int) ( $ogtrips_row['day'] ?? 1 ) ) ); ?><?php echo '' !== (string) ( $ogtrips_row['time'] ?? '' ) ? ' · ' . esc_html( (string) $ogtrips_row['time'] ) : ''; ?></span>
								<strong><?php echo esc_html( (string) ( $ogtrips_row['title'] ?? '' ) ); ?></strong>
								<?php if ( '' !== (string) ( $ogtrips_row['details'] ?? '' ) ) : ?>
									<span class="details"><?php echo esc_html( (string) $ogtrips_row['details'] ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== (string) ( $ogtrips_row['voucher'] ?? '' ) ) : ?>
									<span class="chip chip--line"><?php echo ogtrips_icon( 'ticket' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sprintf( /* translators: %s: voucher reference */ __( 'Voucher %s', 'ogtrips' ), (string) $ogtrips_row['voucher'] ) ); ?></span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>

			<?php if ( $ogtrips_b['days'] ) : ?>
				<div class="block" id="plan">
					<div style="display:flex;justify-content:space-between;align-items:end;gap:20px;flex-wrap:wrap;margin-bottom:24px">
						<h2 style="margin:0;font-family:var(--f-display);font-size:clamp(2.2rem,4vw,3.4rem)"><?php echo wp_kses( __( 'Day by <em>day</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
						<button class="btn btn--ghost" type="button" data-expand-all style="height:44px"><?php esc_html_e( 'Expand all', 'ogtrips' ); ?> <?php echo ogtrips_icon( 'chevrons-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					</div>
					<div class="days">
						<?php foreach ( $ogtrips_b['days'] as $ogtrips_i => $ogtrips_day ) : ?>
							<?php $ogtrips_open = 0 === $ogtrips_i; ?>
							<div class="day<?php echo $ogtrips_open ? ' is-open' : ''; ?>">
								<button class="day-head" type="button" aria-expanded="<?php echo $ogtrips_open ? 'true' : 'false'; ?>">
									<span class="day-no"><?php esc_html_e( 'Day', 'ogtrips' ); ?><b><?php echo esc_html( str_pad( (string) ( $ogtrips_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></b></span>
									<span><h3><?php echo esc_html( (string) ( $ogtrips_day['title'] ?? '' ) ); ?></h3>
										<?php if ( ! empty( $ogtrips_day['location'] ) ) : ?>
											<span class="where"><?php echo ogtrips_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( (string) $ogtrips_day['location'] ); ?></span>
										<?php endif; ?>
									</span>
									<span class="plus"><?php echo ogtrips_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								</button>
								<div class="day-body"><div><div class="day-inner">
									<div>
										<?php echo wp_kses_post( (string) ( $ogtrips_day['description'] ?? '' ) ); ?>
										<?php if ( ! empty( $ogtrips_day['tags'] ) ) : ?>
											<div class="day-tags">
												<?php foreach ( (array) $ogtrips_day['tags'] as $ogtrips_tag ) : ?>
													<span class="chip chip--line"><?php echo ogtrips_icon( ogtrips_safe_icon( $ogtrips_tag['icon'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( (string) ( $ogtrips_tag['text'] ?? '' ) ); ?></span>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
									<?php echo ogtrips_img( (int) ( $ogtrips_day['image'] ?? 0 ), 'ogt-thumb', [ 'alt' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div></div></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $ogtrips_b['stays'] ) : ?>
				<div class="block" id="stays">
					<h2><?php echo wp_kses( __( "Where you'll <em>stay</em>", 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
					<div class="stays">
						<?php foreach ( $ogtrips_b['stays'] as $ogtrips_i => $ogtrips_stay ) : ?>
							<div class="stay reveal<?php echo $ogtrips_i % 3 ? ' reveal-d' . ( $ogtrips_i % 3 ) : ''; ?>">
								<?php if ( ! empty( $ogtrips_stay['image'] ) ) : ?>
									<div class="media"><?php echo ogtrips_img( (int) $ogtrips_stay['image'], 'ogt-thumb', [ 'alt' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
								<?php endif; ?>
								<strong><?php echo esc_html( (string) ( $ogtrips_stay['name'] ?? '' ) ); ?></strong>
								<small>
									<?php
									$ogtrips_n     = (int) ( $ogtrips_stay['nights'] ?? 0 );
									$ogtrips_parts = array_filter(
										[
											/* translators: %d: nights */
											$ogtrips_n ? sprintf( _n( '%d night', '%d nights', $ogtrips_n, 'ogtrips' ), $ogtrips_n ) : '',
											(string) ( $ogtrips_stay['room'] ?? '' ),
										]
									);
									echo esc_html( implode( ' · ', $ogtrips_parts ) );
									?>
								</small>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $ogtrips_b['services'] || '' !== $ogtrips_b['special_requests'] ) : ?>
				<div class="block" id="services">
					<h2><?php echo wp_kses( __( 'Service status <em>tracker</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
					<p class="muted"><?php esc_html_e( 'Everything we have arranged for you and where it stands.', 'ogtrips' ); ?></p>
					<ul class="tracker">
						<?php foreach ( $ogtrips_b['services'] as $ogtrips_row ) : ?>
							<?php $ogtrips_st = (string) ( $ogtrips_row['status'] ?? 'confirmed' ); ?>
							<li class="is-<?php echo esc_attr( $ogtrips_st ); ?>">
								<span class="mark"><?php echo ogtrips_icon( 'na' === $ogtrips_st ? 'x' : ( 'pending' === $ogtrips_st ? 'clock' : 'check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<div><strong><?php echo esc_html( (string) ( $ogtrips_row['service'] ?? '' ) ); ?></strong><?php echo '' !== (string) ( $ogtrips_row['details'] ?? '' ) ? '<small>' . esc_html( (string) $ogtrips_row['details'] ) . '</small>' : ''; ?></div>
								<span class="state"><?php echo esc_html( $ogtrips_svc_labels[ $ogtrips_st ] ?? $ogtrips_st ); ?></span>
							</li>
						<?php endforeach; ?>
						<?php if ( '' !== $ogtrips_b['special_requests'] ) : ?>
							<li class="is-request">
								<span class="mark"><?php echo ogtrips_icon( 'sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<div><strong><?php esc_html_e( 'Your special requests', 'ogtrips' ); ?></strong><small><?php echo esc_html( $ogtrips_b['special_requests'] ); ?></small></div>
								<span class="state"><?php esc_html_e( 'Noted', 'ogtrips' ); ?></span>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $ogtrips_b['included'] || $ogtrips_b['excluded'] ) : ?>
				<div class="block" id="included">
					<h2><?php echo wp_kses( __( "What's <em>included</em>", 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
					<div class="incl">
						<div class="yes"><h4><?php esc_html_e( 'Included', 'ogtrips' ); ?></h4><ul>
							<?php foreach ( $ogtrips_b['included'] as $ogtrips_row ) : ?>
								<li><?php echo ogtrips_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) ( $ogtrips_row['text'] ?? '' ) ); ?></li>
							<?php endforeach; ?>
						</ul></div>
						<div class="no"><h4><?php esc_html_e( 'Not included', 'ogtrips' ); ?></h4><ul>
							<?php foreach ( $ogtrips_b['excluded'] as $ogtrips_row ) : ?>
								<li><?php echo ogtrips_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) ( $ogtrips_row['text'] ?? '' ) ); ?></li>
							<?php endforeach; ?>
						</ul></div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $ogtrips_pdf || $ogtrips_b['documents'] ) : ?>
				<div class="block" id="documents">
					<h2><?php echo wp_kses( __( 'Documents & <em>vouchers</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
					<ul class="docs">
						<?php if ( '' !== $ogtrips_pdf ) : ?>
							<li><a href="<?php echo esc_url( $ogtrips_pdf ); ?>" target="_blank" rel="noopener"><?php echo ogtrips_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><strong><?php esc_html_e( 'Complete itinerary (PDF)', 'ogtrips' ); ?></strong><small><?php esc_html_e( 'Your full day-by-day plan to keep offline', 'ogtrips' ); ?></small></span></a></li>
						<?php endif; ?>
						<?php foreach ( $ogtrips_b['documents'] as $ogtrips_doc ) : ?>
							<?php
							$ogtrips_fid  = (int) ( $ogtrips_doc['file'] ?? 0 );
							$ogtrips_furl = $ogtrips_fid ? wp_get_attachment_url( $ogtrips_fid ) : '';
							if ( '' === (string) $ogtrips_furl ) {
								continue;
							}
							$ogtrips_fname = '' !== (string) ( $ogtrips_doc['label'] ?? '' ) ? (string) $ogtrips_doc['label'] : get_the_title( $ogtrips_fid );
							$ogtrips_fsize = size_format( (int) filesize( get_attached_file( $ogtrips_fid ) ?: '' ) );
							?>
							<li><a href="<?php echo esc_url( $ogtrips_furl ); ?>" target="_blank" rel="noopener"><?php echo ogtrips_icon( 'ticket' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><strong><?php echo esc_html( $ogtrips_fname ); ?></strong><small><?php echo esc_html( strtoupper( (string) pathinfo( (string) $ogtrips_furl, PATHINFO_EXTENSION ) ) . ( $ogtrips_fsize ? ' · ' . $ogtrips_fsize : '' ) ); ?></small></span></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>

		<aside class="portal-side">
			<?php if ( $ogtrips_b['price_total'] > 0 ) : ?>
				<div class="pay-box" id="payment">
					<span class="label"><?php esc_html_e( 'Payment', 'ogtrips' ); ?></span>
					<dl>
						<div><dt><?php esc_html_e( 'Package price', 'ogtrips' ); ?></dt><dd><?php echo esc_html( ogtrips_price( $ogtrips_b['price_total'] ) ); ?></dd></div>
						<?php if ( $ogtrips_b['discount'] > 0 ) : ?>
							<div class="disc"><dt><?php echo esc_html( sprintf( /* translators: %s: percentage */ __( 'Member discount (%s%%)', 'ogtrips' ), rtrim( rtrim( number_format_i18n( $ogtrips_b['discount_pct'], 1 ), '0' ), '.' ) ) ); ?></dt><dd>− <?php echo esc_html( ogtrips_price( $ogtrips_b['discount'] ) ); ?></dd></div>
						<?php endif; ?>
						<div class="total"><dt><?php esc_html_e( 'Total', 'ogtrips' ); ?></dt><dd><?php echo esc_html( ogtrips_price( $ogtrips_b['payable'] ) ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Paid', 'ogtrips' ); ?></dt><dd><?php echo esc_html( ogtrips_price( $ogtrips_b['paid'] ) ); ?></dd></div>
						<div class="<?php echo $ogtrips_b['balance'] > 0 ? 'due' : 'settled'; ?>"><dt><?php esc_html_e( 'Balance', 'ogtrips' ); ?></dt><dd><?php echo $ogtrips_b['balance'] > 0 ? esc_html( ogtrips_price( $ogtrips_b['balance'] ) ) : esc_html__( 'Settled', 'ogtrips' ); ?></dd></div>
					</dl>
					<?php if ( '' !== $ogtrips_b['payment_note'] ) : ?>
						<small><?php echo esc_html( $ogtrips_b['payment_note'] ); ?></small>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="quick-actions">
				<span class="label"><?php esc_html_e( 'Quick actions', 'ogtrips' ); ?></span>
				<?php if ( '' !== $ogtrips_pdf ) : ?>
					<a href="<?php echo esc_url( $ogtrips_pdf ); ?>" class="btn btn--coral btn--block" target="_blank" rel="noopener"><?php esc_html_e( 'Download complete itinerary PDF', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
				<?php endif; ?>
				<?php if ( '' !== $ogtrips_wa ) : ?>
					<a href="<?php echo esc_url( $ogtrips_wa ); ?>" class="btn btn--ghost btn--block btn--wa" target="_blank" rel="noopener"><?php echo ogtrips_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Contact personal guide 24/7', 'ogtrips' ); ?></a>
				<?php endif; ?>
				<a href="<?php echo esc_url( ogtrips_core_portal_url() ); ?>" class="btn btn--ghost btn--block"><?php esc_html_e( 'All my trips', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
			</div>

			<?php if ( $ogtrips_cur ) : ?>
				<?php
				$ogtrips_sub = array_filter( [ (string) get_user_meta( $ogtrips_cur->ID, 'byline_role', true ), (string) get_user_meta( $ogtrips_cur->ID, 'reply_time', true ) ] );
				?>
				<div class="expert">
					<?php echo get_avatar( $ogtrips_cur->ID, 120, '', '', [ 'loading' => 'lazy' ] ); ?>
					<div><strong><?php echo esc_html( $ogtrips_cur->display_name ); ?></strong><small><?php echo esc_html( $ogtrips_sub ? implode( ' · ', $ogtrips_sub ) : __( 'Your personal trip expert', 'ogtrips' ) ); ?></small></div>
					<?php if ( '' !== $ogtrips_wa ) : ?>
						<a href="<?php echo esc_url( $ogtrips_wa ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'WhatsApp', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( function_exists( 'ogtrips_core_is_client' ) && ogtrips_core_is_client() ) : ?>
				<?php get_template_part( 'template-parts/portal/member-card' ); ?>
			<?php endif; ?>
		</aside>
	</div>
</div>
