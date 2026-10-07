<?php
/**
 * Trip page — approved design itinerary.html, every part from the trip's admin form.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$ogtrips_id       = get_the_ID();
	$ogtrips_t        = ogtrips_trip_card_data( $ogtrips_id );
	$ogtrips_heading  = (string) ogtrips_field( 'title_display', $ogtrips_id, get_the_title() );
	$ogtrips_dest     = get_the_terms( $ogtrips_id, 'ogt_destination' );
	$ogtrips_dest     = $ogtrips_dest && ! is_wp_error( $ogtrips_dest ) ? $ogtrips_dest[0] : null;
	$ogtrips_route    = (array) ogtrips_field( 'route_stops', $ogtrips_id, [] );
	$ogtrips_hls      = (array) ogtrips_field( 'highlights', $ogtrips_id, [] );
	$ogtrips_days     = (array) ogtrips_field( 'days', $ogtrips_id, [] );
	$ogtrips_stays    = (array) ogtrips_field( 'stays', $ogtrips_id, [] );
	$ogtrips_incl     = (array) ogtrips_field( 'included', $ogtrips_id, [] );
	$ogtrips_excl     = (array) ogtrips_field( 'excluded', $ogtrips_id, [] );
	$ogtrips_gallery  = (array) ogtrips_field( 'gallery', $ogtrips_id, [] );
	$ogtrips_faqs     = (array) ogtrips_field( 'faqs', $ogtrips_id, [] );
	$ogtrips_min      = (int) ogtrips_field( 'group_min', $ogtrips_id, 0 );
	$ogtrips_max      = (int) ogtrips_field( 'group_max', $ogtrips_id, 0 );
	$ogtrips_best     = (string) ogtrips_field( 'best_time', $ogtrips_id );
	$ogtrips_pace     = ogtrips_pace_label( (string) ogtrips_field( 'pace', $ogtrips_id ) );
	$ogtrips_stay_lbl = (string) ogtrips_field( 'stays_label', $ogtrips_id );

	$ogtrips_facts = [];
	if ( $ogtrips_t['days'] ) {
		$ogtrips_facts[] = [
			'clock',
			__( 'Duration', 'ogtrips' ),
			/* translators: 1: days, 2: nights */
			sprintf( __( '%1$s · %2$s', 'ogtrips' ), sprintf( _n( '%d day', '%d days', $ogtrips_t['days'], 'ogtrips' ), $ogtrips_t['days'] ), sprintf( _n( '%d night', '%d nights', $ogtrips_t['nights'], 'ogtrips' ), $ogtrips_t['nights'] ) ),
		];
	}
	if ( $ogtrips_min || $ogtrips_max ) {
		$ogtrips_facts[] = [
			'users',
			__( 'Group size', 'ogtrips' ),
			/* translators: %s: group size range */
			sprintf( __( '%s people', 'ogtrips' ), $ogtrips_min && $ogtrips_max && $ogtrips_min !== $ogtrips_max ? $ogtrips_min . ' – ' . $ogtrips_max : max( $ogtrips_min, $ogtrips_max ) ),
		];
	}
	if ( '' !== $ogtrips_best ) {
		$ogtrips_facts[] = [ 'sun', __( 'Best time', 'ogtrips' ), $ogtrips_best ];
	}
	if ( '' !== $ogtrips_pace ) {
		$ogtrips_facts[] = [ 'activity', __( 'Pace', 'ogtrips' ), $ogtrips_pace ];
	}
	if ( '' !== $ogtrips_stay_lbl ) {
		$ogtrips_facts[] = [ 'bed-double', __( 'Stays', 'ogtrips' ), $ogtrips_stay_lbl ];
	}

	$ogtrips_sections = [
		'overview' => __( 'Overview', 'ogtrips' ),
		'plan'     => $ogtrips_days ? __( 'Day by day', 'ogtrips' ) : '',
		'stays'    => $ogtrips_stays ? __( 'Stays', 'ogtrips' ) : '',
		'included' => ( $ogtrips_incl || $ogtrips_excl ) ? __( "What's included", 'ogtrips' ) : '',
		'gallery'  => $ogtrips_gallery ? __( 'Gallery', 'ogtrips' ) : '',
		'faq'      => $ogtrips_faqs ? __( 'FAQ', 'ogtrips' ) : '',
	];
	?>
<section class="page-hero">
	<div class="hero-frame">
		<div class="hero-media"><?php the_post_thumbnail( 'ogt-hero', [ 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw' ] ); ?></div>
		<div class="hero-content" style="grid-template-columns:1fr">
			<div>
				<div class="crumbs">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ogtrips' ); ?></a><?php echo ogtrips_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'ogt_itinerary' ) ); ?>"><?php esc_html_e( 'Trips', 'ogtrips' ); ?></a>
					<?php if ( $ogtrips_dest ) : ?>
						<?php echo ogtrips_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="<?php echo esc_url( (string) get_term_link( $ogtrips_dest ) ); ?>"><?php echo esc_html( $ogtrips_dest->name ); ?></a>
					<?php endif; ?>
				</div>
				<h1><?php echo ogtrips_em( $ogtrips_heading ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by ogtrips_em(). ?></h1>
				<div class="hero-chips">
					<?php if ( '' !== $ogtrips_t['badge'] ) : ?>
						<span class="chip"><?php echo ogtrips_icon( ogtrips_safe_icon( $ogtrips_t['badge_ic'], 'award' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_t['badge'] ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $ogtrips_t['rating'] ) : ?>
						<span class="chip"><?php echo ogtrips_icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php
							echo esc_html( number_format_i18n( (float) $ogtrips_t['rating'], 1 ) );
							if ( $ogtrips_t['reviews'] ) {
								/* translators: %s: number of reviews */
								echo esc_html( ' · ' . sprintf( _n( '%s review', '%s reviews', $ogtrips_t['reviews'], 'ogtrips' ), number_format_i18n( $ogtrips_t['reviews'] ) ) );
							}
							?>
						</span>
					<?php endif; ?>
					<?php if ( '' !== $ogtrips_t['location'] ) : ?>
						<span class="chip"><?php echo ogtrips_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_t['location'] ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>

<div class="container">
	<?php if ( $ogtrips_facts ) : ?>
		<div class="facts-bar reveal">
			<?php foreach ( $ogtrips_facts as $ogtrips_fact ) : ?>
				<div class="fact"><?php echo ogtrips_icon( $ogtrips_fact[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><small><?php echo esc_html( $ogtrips_fact[1] ); ?></small><strong><?php echo esc_html( $ogtrips_fact[2] ); ?></strong></div></div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<nav class="subnav" aria-label="<?php esc_attr_e( 'Sections', 'ogtrips' ); ?>">
		<?php $ogtrips_first = true; ?>
		<?php foreach ( array_filter( $ogtrips_sections ) as $ogtrips_anchor => $ogtrips_label ) : ?>
			<a href="#<?php echo esc_attr( $ogtrips_anchor ); ?>"<?php echo $ogtrips_first ? ' class="is-active"' : ''; ?>><?php echo esc_html( $ogtrips_label ); ?></a>
			<?php $ogtrips_first = false; ?>
		<?php endforeach; ?>
	</nav>

	<div class="layout">
		<div>
			<div class="block" id="overview">
				<h2><?php echo wp_kses( __( 'The <em>trip</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
				<div class="lead"><?php the_content(); ?></div>

				<?php if ( $ogtrips_route ) : ?>
					<div class="route">
						<?php foreach ( $ogtrips_route as $ogtrips_i => $ogtrips_stop ) : ?>
							<?php if ( $ogtrips_i ) : ?><span class="seg"></span><?php endif; ?>
							<div class="stop"><small><?php echo esc_html( (string) ( $ogtrips_stop['days'] ?? '' ) ); ?></small><span class="dot"></span><strong><?php echo esc_html( (string) ( $ogtrips_stop['label'] ?? '' ) ); ?></strong></div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( $ogtrips_hls ) : ?>
					<div class="highlight-grid">
						<?php foreach ( $ogtrips_hls as $ogtrips_i => $ogtrips_hl ) : ?>
							<div class="hl reveal<?php echo $ogtrips_i % 3 ? ' reveal-d' . ( $ogtrips_i % 3 ) : ''; ?>"><?php echo ogtrips_icon( ogtrips_safe_icon( $ogtrips_hl['icon'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><strong><?php echo esc_html( (string) ( $ogtrips_hl['text'] ?? '' ) ); ?></strong></div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $ogtrips_days ) : ?>
				<div class="block" id="plan">
					<div style="display:flex;justify-content:space-between;align-items:end;gap:20px;flex-wrap:wrap;margin-bottom:24px">
						<h2 style="margin:0;font-family:var(--f-display);font-size:clamp(2.2rem,4vw,3.4rem)"><?php echo wp_kses( __( 'Day by <em>day</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
						<button class="btn btn--ghost" type="button" data-expand-all style="height:44px"><?php esc_html_e( 'Expand all', 'ogtrips' ); ?> <?php echo ogtrips_icon( 'chevrons-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					</div>
					<div class="days">
						<?php foreach ( $ogtrips_days as $ogtrips_i => $ogtrips_day ) : ?>
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

			<?php if ( $ogtrips_stays ) : ?>
				<div class="block" id="stays">
					<h2><?php echo wp_kses( __( "Where you'll <em>stay</em>", 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
					<div class="stays">
						<?php foreach ( $ogtrips_stays as $ogtrips_i => $ogtrips_stay ) : ?>
							<div class="stay reveal<?php echo $ogtrips_i % 3 ? ' reveal-d' . ( $ogtrips_i % 3 ) : ''; ?>">
								<div class="media"><?php echo ogtrips_img( (int) ( $ogtrips_stay['image'] ?? 0 ), 'ogt-thumb', [ 'alt' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
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

			<?php if ( $ogtrips_incl || $ogtrips_excl ) : ?>
				<div class="block" id="included">
					<h2><?php echo wp_kses( __( "What's <em>included</em>", 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
					<div class="incl">
						<div class="yes"><h4><?php esc_html_e( 'Included', 'ogtrips' ); ?></h4><ul>
							<?php foreach ( $ogtrips_incl as $ogtrips_row ) : ?>
								<li><?php echo ogtrips_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) ( $ogtrips_row['text'] ?? '' ) ); ?></li>
							<?php endforeach; ?>
						</ul></div>
						<div class="no"><h4><?php esc_html_e( 'Not included', 'ogtrips' ); ?></h4><ul>
							<?php foreach ( $ogtrips_excl as $ogtrips_row ) : ?>
								<li><?php echo ogtrips_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( (string) ( $ogtrips_row['text'] ?? '' ) ); ?></li>
							<?php endforeach; ?>
						</ul></div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $ogtrips_gallery ) : ?>
				<div class="block" id="gallery">
					<h2><?php echo wp_kses( __( 'Moments <em>ahead</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
					<div class="gallery">
						<?php foreach ( array_slice( $ogtrips_gallery, 0, 6 ) as $ogtrips_i => $ogtrips_img_id ) : ?>
							<?php echo ogtrips_img( (int) $ogtrips_img_id, $ogtrips_i ? 'ogt-thumb' : 'ogt-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $ogtrips_faqs ) : ?>
				<div class="block" id="faq" style="padding-bottom:20px">
					<h2><?php echo wp_kses( __( 'Good to <em>know</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2>
					<?php foreach ( $ogtrips_faqs as $ogtrips_i => $ogtrips_faq ) : ?>
						<?php $ogtrips_open = 0 === $ogtrips_i; ?>
						<div class="faq-item<?php echo $ogtrips_open ? ' is-open' : ''; ?>">
							<button class="faq-q" type="button" aria-expanded="<?php echo $ogtrips_open ? 'true' : 'false'; ?>"><?php echo esc_html( (string) ( $ogtrips_faq['question'] ?? '' ) ); ?><span class="plus"><?php echo ogtrips_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></button>
							<div class="faq-a"><div><?php echo wp_kses_post( (string) ( $ogtrips_faq['answer'] ?? '' ) ); ?></div></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php get_template_part( 'template-parts/trip/booking', null, [ 'trip' => $ogtrips_t ] ); ?>
	</div>
</div>

	<?php
	$ogtrips_tax = [];
	if ( $ogtrips_dest ) {
		$ogtrips_tax[] = [
			'taxonomy' => 'ogt_destination',
			'terms'    => [ $ogtrips_dest->term_id ],
		];
	}
	$ogtrips_types = wp_get_post_terms( $ogtrips_id, 'ogt_trip_type', [ 'fields' => 'ids' ] );
	if ( $ogtrips_types && ! is_wp_error( $ogtrips_types ) ) {
		$ogtrips_tax[] = [
			'taxonomy' => 'ogt_trip_type',
			'terms'    => $ogtrips_types,
		];
	}
	$ogtrips_related_args = [
		'posts_per_page' => 3,
		'post__not_in'   => [ $ogtrips_id ],
		'orderby'        => 'rand',
	];
	if ( $ogtrips_tax ) {
		$ogtrips_related_args['tax_query'] = array_merge( [ 'relation' => 'OR' ], $ogtrips_tax ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
	$ogtrips_related = ogtrips_trip_query( $ogtrips_related_args );
	if ( $ogtrips_related->post_count < 3 ) {
		$ogtrips_related = ogtrips_trip_query(
			[
				'posts_per_page' => 3,
				'post__not_in'   => [ $ogtrips_id ],
			]
		);
	}
	?>
	<?php if ( $ogtrips_related->have_posts() ) : ?>
<section class="section">
	<div class="container">
		<div class="section-top">
			<div class="reveal"><span class="label"><?php esc_html_e( 'You may also like', 'ogtrips' ); ?></span><h2 class="display-sm"><?php echo wp_kses( __( 'More <em>OG trips</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2></div>
			<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'ogt_itinerary' ) ); ?>" class="link-arrow reveal"><?php esc_html_e( 'See all', 'ogtrips' ); ?> <?php echo ogtrips_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		</div>
		<div class="bento">
			<?php
			$ogtrips_i = 0;
			while ( $ogtrips_related->have_posts() ) :
				$ogtrips_related->the_post();
				get_template_part( 'template-parts/card-tour', null, [ 'delay' => $ogtrips_i++ % 3 ] );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
