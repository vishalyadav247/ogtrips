<?php
/**
 * Destination page (/destinations/<name>/): the place's trips, then a row of its tour guides.
 * One page per place for search engines; the Trips and Travel Guide pages have their own filters.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ogtrips_term = get_queried_object();

get_template_part(
	'template-parts/listing',
	null,
	[
		'label' => __( 'Destination', 'ogtrips' ),
		/* translators: %s: destination */
		'title' => sprintf( __( 'Trips to *%s*', 'ogtrips' ), $ogtrips_term->name ),
		'lead'  => '' !== $ogtrips_term->description ? $ogtrips_term->description : __( 'Handpicked stays, day-by-day plans and a real human on call 24/7.', 'ogtrips' ),
		'card'  => 'best',
		'empty' => __( 'No trips here yet — tell us your dates and we will plan one for you.', 'ogtrips' ),
	]
);

$ogtrips_guides = new WP_Query(
	[
		'post_type'           => 'ogt_guide',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'no_found_rows'       => false,
		'ignore_sticky_posts' => true,
		'tax_query'           => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			[
				'taxonomy' => 'ogt_destination',
				'terms'    => [ $ogtrips_term->term_id ],
			],
		],
	]
);

if ( $ogtrips_guides->have_posts() ) :
	?>
<section class="section">
	<div class="container">
		<div class="section-top">
			<div class="reveal"><span class="label"><?php esc_html_e( 'Before you go', 'ogtrips' ); ?></span><h2 class="display-sm">
				<?php
				/* translators: %s: destination */
				echo ogtrips_em( sprintf( __( '*%s* travel guides', 'ogtrips' ), $ogtrips_term->name ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by ogtrips_em().
				?>
			</h2></div>
			<?php if ( $ogtrips_guides->found_posts > 3 ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'destination', $ogtrips_term->slug, (string) get_post_type_archive_link( 'ogt_guide' ) ) ); ?>" class="link-arrow reveal"><?php esc_html_e( 'All guides', 'ogtrips' ); ?> <?php echo ogtrips_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<?php endif; ?>
		</div>
		<div class="posts">
			<?php
			$ogtrips_i = 0;
			while ( $ogtrips_guides->have_posts() ) :
				$ogtrips_guides->the_post();
				get_template_part( 'template-parts/card-post', null, [ 'delay' => $ogtrips_i++ % 3 ] );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
	<?php
endif;

get_template_part( 'template-parts/contact' );

get_footer();
