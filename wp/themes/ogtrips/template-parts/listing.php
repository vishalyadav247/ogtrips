<?php
/**
 * Shared listing layout (trips, tour guides, blog, search, destinations): heading, optional filter chips,
 * card grid of the main query, pagination.
 *
 * Args: label (string), title (string, *stars* allowed), lead (string), card ('best'|'post'),
 *       chips (array of [url, label, active]), empty (string).
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_args = wp_parse_args(
	$args,
	[
		'label' => '',
		'title' => '',
		'lead'  => '',
		'card'  => 'post',
		'chips' => [],
		'empty' => __( 'Nothing here yet — check back soon.', 'ogtrips' ),
	]
);
?>
<header class="article-head container listing-head">
	<?php if ( '' !== $ogtrips_args['label'] ) : ?>
		<span class="label reveal"><?php echo esc_html( $ogtrips_args['label'] ); ?></span>
	<?php endif; ?>
	<h1 class="reveal"><?php echo ogtrips_em( $ogtrips_args['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by ogtrips_em(). ?></h1>
	<?php if ( '' !== $ogtrips_args['lead'] ) : ?>
		<p class="lead reveal"><?php echo esc_html( $ogtrips_args['lead'] ); ?></p>
	<?php endif; ?>
	<?php if ( $ogtrips_args['chips'] ) : ?>
		<nav class="listing-chips reveal" aria-label="<?php esc_attr_e( 'Filter', 'ogtrips' ); ?>">
			<?php foreach ( $ogtrips_args['chips'] as $ogtrips_chip ) : ?>
				<a href="<?php echo esc_url( $ogtrips_chip[0] ); ?>" class="chip chip--line<?php echo ! empty( $ogtrips_chip[2] ) ? ' is-active' : ''; ?>"<?php echo ! empty( $ogtrips_chip[2] ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $ogtrips_chip[1] ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>
</header>

<section class="section listing-body">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="<?php echo 'best' === $ogtrips_args['card'] ? 'best' : 'posts'; ?>">
				<?php
				$ogtrips_i = 0;
				while ( have_posts() ) :
					the_post();
					if ( 'best' === $ogtrips_args['card'] && 'ogt_itinerary' === get_post_type() ) {
						get_template_part( 'template-parts/card-best', null, [ 'delay' => $ogtrips_i++ % 2 ] );
					} else {
						get_template_part( 'template-parts/card-post', null, [ 'delay' => $ogtrips_i++ % 3 ] );
					}
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination(
				[
					'mid_size'  => 1,
					'prev_text' => __( 'Previous', 'ogtrips' ),
					'next_text' => __( 'Next', 'ogtrips' ),
				]
			);
			?>
		<?php else : ?>
			<p class="lead listing-empty"><?php echo esc_html( $ogtrips_args['empty'] ); ?></p>
		<?php endif; ?>
	</div>
</section>
