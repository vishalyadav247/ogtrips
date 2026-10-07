<?php
/**
 * Page not found.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<header class="article-head container listing-head">
	<span class="label"><?php esc_html_e( 'Error 404', 'ogtrips' ); ?></span>
	<h1><?php echo wp_kses( __( 'This road leads <em>nowhere</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h1>
	<p class="lead"><?php esc_html_e( "The page you were looking for has moved or never existed. Let's get you back on track.", 'ogtrips' ); ?></p>
	<div class="hero-actions" style="margin-top:28px">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--coral"><?php esc_html_e( 'Back to home', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
		<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'ogt_itinerary' ) ); ?>" class="btn btn--ghost"><?php esc_html_e( 'See all trips', 'ogtrips' ); ?></a>
	</div>
</header>
<?php
$ogtrips_best = ogtrips_trip_query( [ 'posts_per_page' => 3 ] );
if ( $ogtrips_best->have_posts() ) :
	?>
<section class="section">
	<div class="container">
		<div class="bento">
			<?php
			$ogtrips_i = 0;
			while ( $ogtrips_best->have_posts() ) :
				$ogtrips_best->the_post();
				get_template_part( 'template-parts/card-tour', null, [ 'delay' => $ogtrips_i++ % 3 ] );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
	<?php
endif;

get_footer();
