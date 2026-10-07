<?php
/**
 * Search results — matching trips first (trip cards), then tour guides, blog posts and pages.
 * Matching itself (fields, destinations, trip types) is done in ogtrips-core.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ogtrips_trips = [];
$ogtrips_other = [];
while ( have_posts() ) {
	the_post();
	if ( 'ogt_itinerary' === get_post_type() ) {
		$ogtrips_trips[] = get_post();
	} else {
		$ogtrips_other[] = get_post();
	}
}

global $wp_query;
$ogtrips_total = (int) $wp_query->found_posts;
?>
<header class="article-head container listing-head">
	<span class="label reveal"><?php esc_html_e( 'Search', 'ogtrips' ); ?></span>
	<h1 class="reveal">
		<?php
		/* translators: %s: search terms */
		echo ogtrips_em( sprintf( __( 'Results for *%s*', 'ogtrips' ), get_search_query( false ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by ogtrips_em().
		?>
	</h1>
	<p class="lead reveal">
		<?php
		echo esc_html(
			$ogtrips_total
				/* translators: %s: number of results */
				? sprintf( _n( '%s result', '%s results', $ogtrips_total, 'ogtrips' ), number_format_i18n( $ogtrips_total ) )
				: __( 'Nothing matched that search. Try a destination like Ladakh or Kashmir, or a trip type like honeymoon — or ask us below.', 'ogtrips' )
		);
		?>
	</p>
</header>

<?php if ( $ogtrips_trips ) : ?>
<section class="section listing-body">
	<div class="container">
		<?php if ( $ogtrips_other ) : ?>
			<div class="section-top"><h2 class="display-sm"><?php echo wp_kses( __( 'Matching <em>trips</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2></div>
		<?php endif; ?>
		<div class="best">
			<?php
			foreach ( $ogtrips_trips as $ogtrips_i => $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- setup_postdata() loop.
				setup_postdata( $post );
				get_template_part( 'template-parts/card-best', null, [ 'delay' => $ogtrips_i % 2 ] );
			endforeach;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $ogtrips_other ) : ?>
<section class="section listing-body">
	<div class="container">
		<?php if ( $ogtrips_trips ) : ?>
			<div class="section-top"><h2 class="display-sm"><?php echo wp_kses( __( 'Guides &amp; <em>articles</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2></div>
		<?php endif; ?>
		<div class="posts">
			<?php
			foreach ( $ogtrips_other as $ogtrips_i => $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- setup_postdata() loop.
				setup_postdata( $post );
				get_template_part( 'template-parts/card-post', null, [ 'delay' => $ogtrips_i % 3 ] );
			endforeach;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $wp_query->max_num_pages > 1 ) : ?>
<div class="container">
	<?php
	the_posts_pagination(
		[
			'mid_size'  => 1,
			'prev_text' => __( 'Previous', 'ogtrips' ),
			'next_text' => __( 'Next', 'ogtrips' ),
		]
	);
	?>
</div>
<?php endif; ?>

<?php
get_template_part( 'template-parts/contact' );

get_footer();
