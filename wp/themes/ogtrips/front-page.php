<?php
/**
 * Homepage — approved design index.html; copy from Homepage settings, content from trips/reviews/moments.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ogtrips_h = static function ( $key, $default = '' ) {
	return ogtrips_home( $key, $default );
};

$ogtrips_places = (array) $ogtrips_h( 'hero_places', [] );
$ogtrips_first  = $ogtrips_places[0] ?? [];
$ogtrips_cta1   = (array) $ogtrips_h( 'hero_cta_primary', [] );
$ogtrips_cta2   = (array) $ogtrips_h( 'hero_cta_secondary', [] );
$ogtrips_cta1   = $ogtrips_cta1 ? $ogtrips_cta1 : [ 'url' => '#trips', 'title' => __( 'Our OG Trips', 'ogtrips' ), 'target' => '' ];
$ogtrips_cta2   = $ogtrips_cta2 ? $ogtrips_cta2 : [ 'url' => '#social', 'title' => __( 'Watch traveller reels', 'ogtrips' ), 'target' => '' ];
?>
<section class="hero" id="home">
	<div class="hero-frame">
		<div class="slides">
			<?php foreach ( $ogtrips_places as $ogtrips_i => $ogtrips_place ) : ?>
				<figure class="slide<?php echo 0 === $ogtrips_i ? ' is-active' : ''; ?>" data-place="<?php echo esc_attr( (string) ( $ogtrips_place['place'] ?? '' ) ); ?>" data-country="<?php echo esc_attr( (string) ( $ogtrips_place['country'] ?? '' ) ); ?>">
					<?php
					$ogtrips_slide = ogtrips_img(
						(int) ( $ogtrips_place['image'] ?? 0 ),
						'ogt-hero',
						0 === $ogtrips_i
							? [ 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw' ]
							: [ 'sizes' => '100vw', 'loading' => false ]
					);
					// Later slides load in JS just before they show, so they don't compete with the first (LCP) image.
					if ( $ogtrips_i ) {
						$ogtrips_slide = str_replace( [ ' src=', ' srcset=' ], [ ' data-src=', ' data-srcset=' ], $ogtrips_slide );
					}
					echo $ogtrips_slide; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output.
					?>
				</figure>
			<?php endforeach; ?>
		</div>

		<svg class="flight" viewBox="0 0 600 300" fill="none" aria-hidden="true">
			<path d="M10 270 C 140 290, 250 220, 320 160 S 480 50, 548 38" stroke="#fff" stroke-width="3"/>
			<g class="plane">
				<path d="M540 44 L596 10 L572 60 L563 46 Z" fill="#FF5A4F"/>
				<path d="M563 46 L596 10 L552 44 Z" fill="#c9382f"/>
			</g>
		</svg>

		<div class="hero-content">
			<?php if ( $ogtrips_first ) : ?>
				<div class="now-showing"><b><?php esc_html_e( 'Now showing', 'ogtrips' ); ?></b><span id="now-country"><?php echo esc_html( (string) ( $ogtrips_first['country'] ?? '' ) ); ?></span></div>
			<?php endif; ?>
			<h1><?php echo esc_html( (string) $ogtrips_h( 'hero_heading', __( 'Where to next?', 'ogtrips' ) ) ); ?><?php if ( $ogtrips_first ) : ?><br><span class="place"><?php echo esc_html( (string) ( $ogtrips_first['place'] ?? '' ) ); ?></span><?php endif; ?></h1>
			<p class="hero-sub"><?php echo esc_html( (string) $ogtrips_h( 'hero_subtitle', __( "Handpicked stays, day-by-day plans and a real human on call 24/7. You pack the bags — we'll handle everything else.", 'ogtrips' ) ) ); ?></p>
			<div class="hero-actions">
				<a href="<?php echo esc_url( $ogtrips_cta1['url'] ); ?>" class="btn btn--coral btn--callout"<?php echo '_blank' === ( $ogtrips_cta1['target'] ?? '' ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $ogtrips_cta1['title'] ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-down-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
				<a href="<?php echo esc_url( $ogtrips_cta2['url'] ); ?>" class="btn btn--ghost-light"<?php echo '_blank' === ( $ogtrips_cta2['target'] ?? '' ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo ogtrips_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_cta2['title'] ); ?></a>
			</div>
		</div>

		<?php if ( count( $ogtrips_places ) > 1 ) : ?>
			<div class="hero-places" role="tablist" aria-label="<?php esc_attr_e( 'Destinations', 'ogtrips' ); ?>">
				<?php foreach ( $ogtrips_places as $ogtrips_i => $ogtrips_place ) : ?>
					<?php $ogtrips_tab = (string) ( $ogtrips_place['tab_label'] ?? '' ); ?>
					<button class="hp<?php echo 0 === $ogtrips_i ? ' is-active' : ''; ?>" type="button" role="tab" aria-selected="<?php echo 0 === $ogtrips_i ? 'true' : 'false'; ?>"><span class="bar"><i></i></span><strong><?php echo esc_html( '' !== $ogtrips_tab ? $ogtrips_tab : rtrim( (string) ( $ogtrips_place['place'] ?? '' ), '.' ) ); ?></strong><small><?php echo esc_html( (string) ( $ogtrips_place['country'] ?? '' ) ); ?></small></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
// ---- Why OG ----
$ogtrips_quote = (string) $ogtrips_h( 'why_quote' );
$ogtrips_quote = preg_replace( '#<em>#', '<span class="teal">', ogtrips_em( $ogtrips_quote ), 1 );
$ogtrips_quote = str_replace( [ '<em>', '</em>' ], [ '<span class="hl">', '</span>' ], (string) $ogtrips_quote );
$ogtrips_pills = (array) $ogtrips_h( 'why_pillars', [] );
?>
<section class="section" id="why">
	<div class="container">
		<div style="text-align:center" class="reveal"><span class="label"><?php echo esc_html( (string) $ogtrips_h( 'why_label', __( "Why we are OG's", 'ogtrips' ) ) ); ?></span></div>
		<?php if ( '' !== $ogtrips_quote ) : ?>
			<div class="og-quote reveal">
				<span class="mark" aria-hidden="true">“</span>
				<blockquote><?php echo wp_kses( $ogtrips_quote, [ 'span' => [ 'class' => [] ] ] ); ?></blockquote>
				<?php if ( '' !== (string) $ogtrips_h( 'why_cite' ) ) : ?>
					<cite>— <?php echo esc_html( (string) $ogtrips_h( 'why_cite' ) ); ?></cite>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $ogtrips_pills ) : ?>
			<div class="pillars">
				<?php foreach ( $ogtrips_pills as $ogtrips_i => $ogtrips_p ) : ?>
					<div class="pillar reveal<?php echo $ogtrips_i % 3 ? ' reveal-d' . ( $ogtrips_i % 3 ) : ''; ?>">
						<div class="ic"><?php echo ogtrips_icon( ogtrips_safe_icon( $ogtrips_p['icon'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<h3><?php echo esc_html( (string) ( $ogtrips_p['title'] ?? '' ) ); ?></h3>
						<p><?php echo esc_html( (string) ( $ogtrips_p['text'] ?? '' ) ); ?></p>
						<?php if ( '' !== (string) ( $ogtrips_p['stat_number'] ?? '' ) ) : ?>
							<div class="stat"><strong data-count="<?php echo esc_attr( (string) $ogtrips_p['stat_number'] ); ?>" data-suffix="<?php echo esc_attr( (string) ( $ogtrips_p['stat_suffix'] ?? '' ) ); ?>"><?php echo esc_html( $ogtrips_p['stat_number'] . ( $ogtrips_p['stat_suffix'] ?? '' ) ); ?></strong><span><?php echo esc_html( (string) ( $ogtrips_p['stat_label'] ?? '' ) ); ?></span></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
// ---- Reviews ----
$ogtrips_reviews = get_posts(
	[
		'post_type'      => 'ogt_review',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
	]
);
// Featured reviews first, then newest.
usort(
	$ogtrips_reviews,
	static function ( $a, $b ) {
		return (int) get_post_meta( $b->ID, 'featured', true ) <=> (int) get_post_meta( $a->ID, 'featured', true );
	}
);
$ogtrips_reviews = array_slice( $ogtrips_reviews, 0, 8 );
$ogtrips_sources = [
	'google'      => __( 'Google', 'ogtrips' ),
	'tripadvisor' => __( 'Tripadvisor', 'ogtrips' ),
	'direct'      => __( 'Verified traveller', 'ogtrips' ),
];
?>
<?php if ( $ogtrips_reviews ) : ?>
<section class="section reviews-sec" id="reviews">
	<div class="container">
		<div class="section-top">
			<div class="reveal">
				<span class="label"><?php echo esc_html( (string) $ogtrips_h( 'reviews_label', __( 'Reviews', 'ogtrips' ) ) ); ?></span>
				<h2 class="display-sm"><?php echo ogtrips_em( (string) $ogtrips_h( 'reviews_heading', __( 'Loved by *travellers*', 'ogtrips' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			</div>
			<div class="rating-sum reveal">
				<?php if ( '' !== (string) $ogtrips_h( 'reviews_rating' ) ) : ?>
					<strong><?php echo esc_html( number_format_i18n( (float) $ogtrips_h( 'reviews_rating' ), 1 ) ); ?></strong>
					<div><?php echo ogtrips_stars( 5 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><small><?php echo esc_html( (string) $ogtrips_h( 'reviews_count_label' ) ); ?></small></div>
				<?php endif; ?>
				<div class="slider-ctrl" style="margin-left:14px">
					<button class="icon-btn" type="button" data-carousel="prev" data-target="#review-track" aria-label="<?php esc_attr_e( 'Previous', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<button class="icon-btn" type="button" data-carousel="next" data-target="#review-track" aria-label="<?php esc_attr_e( 'Next', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>
			</div>
		</div>

		<div class="track reveal" id="review-track">
			<?php
			foreach ( $ogtrips_reviews as $ogtrips_review ) :
				$ogtrips_rid    = $ogtrips_review->ID;
				$ogtrips_src    = (string) ogtrips_field( 'source', $ogtrips_rid, 'direct' );
				$ogtrips_trip   = (int) ogtrips_field( 'itinerary', $ogtrips_rid, 0 );
				$ogtrips_date   = (string) ogtrips_field( 'travel_date', $ogtrips_rid );
				$ogtrips_meta   = array_filter( [ (string) ogtrips_field( 'reviewer_city', $ogtrips_rid ), '' !== $ogtrips_date ? wp_date( 'F Y', strtotime( $ogtrips_date ) ) : '' ] );
				?>
				<article class="review">
					<div class="top"><?php echo ogtrips_stars( (int) ogtrips_field( 'rating', $ogtrips_rid, 5 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="src"><?php echo ogtrips_icon( 'badge-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ogtrips_sources[ $ogtrips_src ] ?? '' ); ?></span></div>
					<p>“<?php echo esc_html( (string) ogtrips_field( 'quote', $ogtrips_rid ) ); ?>”</p>
					<?php if ( $ogtrips_trip && 'publish' === get_post_status( $ogtrips_trip ) ) : ?>
						<?php $ogtrips_tdays = (int) ogtrips_field( 'duration_days', $ogtrips_trip, 0 ); ?>
						<a class="trip-pic" href="<?php echo esc_url( get_permalink( $ogtrips_trip ) ); ?>"><?php echo get_the_post_thumbnail( $ogtrips_trip, 'ogt-avatar', [ 'alt' => '', 'loading' => 'lazy' ] ); ?><span><?php echo esc_html( (string) ogtrips_field( 'short_title', $ogtrips_trip, get_the_title( $ogtrips_trip ) ) ); ?>
						<?php
						/* translators: %d: days */
						echo $ogtrips_tdays ? esc_html( ' · ' . sprintf( _n( '%d day', '%d days', $ogtrips_tdays, 'ogtrips' ), $ogtrips_tdays ) ) : '';
						?>
						</span></a>
					<?php endif; ?>
					<div class="person"><?php echo ogtrips_img( (int) ogtrips_field( 'avatar', $ogtrips_rid, 0 ), 'ogt-avatar', [ 'alt' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div><strong><?php echo esc_html( (string) ogtrips_field( 'reviewer_name', $ogtrips_rid ) ); ?></strong><small><?php echo esc_html( implode( ' · ', $ogtrips_meta ) ); ?></small></div></div>
				</article>
				<?php
			endforeach;
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
// ---- Moments ----
$ogtrips_moments = new WP_Query(
	[
		'post_type'      => 'ogt_moment',
		'post_status'    => 'publish',
		'posts_per_page' => 8,
		'no_found_rows'  => true,
		'orderby'        => [
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		],
	]
);
$ogtrips_handle  = ltrim( (string) ogtrips_setting( 'instagram_handle', 'ogtrips' ), '@' );
$ogtrips_insta   = (string) ogtrips_setting( 'instagram_url' );
?>
<?php if ( $ogtrips_moments->have_posts() ) : ?>
<section class="section" id="social" style="padding-bottom:0">
	<div class="social-sec on-dark">
		<div class="container section">
			<div class="section-top">
				<div class="reveal">
					<span class="label"><?php echo esc_html( (string) $ogtrips_h( 'moments_label', __( '#OgTrips moments', 'ogtrips' ) ) ); ?></span>
					<h2 class="display-sm" style="color:#fff"><?php echo ogtrips_em( (string) $ogtrips_h( 'moments_heading', __( 'Tagged by *you*, loved by us', 'ogtrips' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				</div>
				<p class="reveal"><?php echo ogtrips_em( (string) $ogtrips_h( 'moments_intro' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			</div>

			<div class="ugc reveal">
				<?php
				while ( $ogtrips_moments->have_posts() ) :
					$ogtrips_moments->the_post();
					$ogtrips_mid   = get_the_ID();
					$ogtrips_video = 'video' === ogtrips_field( 'media_type', $ogtrips_mid, 'image' );
					$ogtrips_size  = (string) ogtrips_field( 'tile_size', $ogtrips_mid, 'normal' );
					$ogtrips_link  = (string) ogtrips_field( 'link', $ogtrips_mid, $ogtrips_insta );
					$ogtrips_dur   = (string) ogtrips_field( 'duration', $ogtrips_mid );
					$ogtrips_cls   = 'ugc-item' . ( 'normal' !== $ogtrips_size ? ' ' . sanitize_html_class( $ogtrips_size ) : '' ) . ( $ogtrips_video ? ' is-video' : '' );
					?>
					<a href="<?php echo esc_url( '' !== $ogtrips_link ? $ogtrips_link : '#social' ); ?>" class="<?php echo esc_attr( $ogtrips_cls ); ?>" target="_blank" rel="noopener">
						<?php echo ogtrips_img( (int) ogtrips_field( 'image', $ogtrips_mid, 0 ), in_array( $ogtrips_size, [ 'big', 'wide' ], true ) ? 'ogt-card' : 'ogt-thumb', [ 'alt' => get_the_title() ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $ogtrips_video ) : ?>
							<span class="chip chip--dark reel"><?php echo ogtrips_icon( 'clapperboard' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( 'big' === $ogtrips_size ? __( 'Reel', 'ogtrips' ) . ( '' !== $ogtrips_dur ? ' · ' . $ogtrips_dur : '' ) : $ogtrips_dur ); ?></span>
							<span class="play"><?php echo ogtrips_icon( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
						<span class="ov">
							<span class="handle"><?php echo ogtrips_icon( 'instagram' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( (string) ogtrips_field( 'handle', $ogtrips_mid ) ); ?></span>
							<span class="meta">
								<?php if ( '' !== (string) ogtrips_field( 'likes', $ogtrips_mid ) ) : ?>
									<span><?php echo ogtrips_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( (string) ogtrips_field( 'likes', $ogtrips_mid ) ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== (string) ogtrips_field( 'views', $ogtrips_mid ) ) : ?>
									<span><?php echo ogtrips_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( (string) ogtrips_field( 'views', $ogtrips_mid ) ); ?></span>
								<?php endif; ?>
							</span>
						</span>
					</a>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>

			<div class="tag-cta reveal">
				<span class="hash">
					<?php
					printf(
						/* translators: 1: Instagram handle, 2: hashtag */
						wp_kses( __( 'Tag <span>@%1$s</span> &amp; use <span>%2$s</span>', 'ogtrips' ), [ 'span' => [] ] ),
						esc_html( $ogtrips_handle ),
						esc_html( (string) ogtrips_setting( 'hashtag', '#WhereToNext' ) )
					);
					?>
				</span>
				<?php if ( '' !== $ogtrips_insta ) : ?>
					<a href="<?php echo esc_url( $ogtrips_insta ); ?>" class="btn btn--sun" target="_blank" rel="noopener"><?php esc_html_e( 'Follow on Instagram', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'instagram' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
// ---- Most-loved trips ----
// Only trips ticked "Show on the homepage" (max 4), by their position number; trips without a
// number come after numbered ones. If none are ticked, the 4 newest trips are shown instead.
$ogtrips_featured = get_posts(
	[
		'post_type'      => 'ogt_itinerary',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'fields'         => 'ids',
		'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			[
				'key'   => 'is_bestseller',
				'value' => '1',
			],
		],
	]
);
usort(
	$ogtrips_featured,
	static function ( $a, $b ) {
		$ra = (int) get_post_meta( $a, 'bestseller_rank', true );
		$rb = (int) get_post_meta( $b, 'bestseller_rank', true );
		return ( $ra ? $ra : PHP_INT_MAX ) <=> ( $rb ? $rb : PHP_INT_MAX );
	}
);
$ogtrips_best = $ogtrips_featured
	? ogtrips_trip_query(
		[
			'post__in'       => array_slice( $ogtrips_featured, 0, 4 ),
			'orderby'        => 'post__in',
			'posts_per_page' => 4,
		]
	)
	: ogtrips_trip_query( [ 'posts_per_page' => 4 ] );
$ogtrips_count = (int) wp_count_posts( 'ogt_itinerary' )->publish;
?>
<?php if ( $ogtrips_best->have_posts() ) : ?>
<section class="section" id="trips">
	<div class="container">
		<div class="section-top">
			<div class="reveal">
				<span class="label"><?php echo esc_html( (string) $ogtrips_h( 'trips_label', __( 'Our OG Trips', 'ogtrips' ) ) ); ?></span>
				<h2 class="display-sm"><?php echo ogtrips_em( (string) $ogtrips_h( 'trips_heading', __( 'Most-loved *trips* right now', 'ogtrips' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
			</div>
			<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'ogt_itinerary' ) ); ?>" class="link-arrow reveal">
				<?php
				/* translators: %s: number of trips */
				echo esc_html( sprintf( _n( 'View all %s trip', 'View all %s trips', $ogtrips_count, 'ogtrips' ), number_format_i18n( $ogtrips_count ) ) );
				?>
				<?php echo ogtrips_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		</div>

		<div class="best">
			<?php
			$ogtrips_i = 0;
			while ( $ogtrips_best->have_posts() ) :
				$ogtrips_best->the_post();
				get_template_part( 'template-parts/card-best', null, [ 'delay' => $ogtrips_i++ % 2 ] );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/contact' ); ?>

<?php
get_footer();
