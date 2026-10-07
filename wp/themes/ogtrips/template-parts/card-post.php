<?php
/**
 * Article card (tour guides, blog posts, search results). Expects the current post in the loop.
 *
 * Args: delay (int) reveal delay index.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_delay = (int) ( $args['delay'] ?? 0 );
$ogtrips_type  = get_post_type();
$ogtrips_label = ogtrips_article_label( get_post() );

if ( 'ogt_itinerary' === $ogtrips_type ) {
	$ogtrips_label = __( 'Trip', 'ogtrips' );
} elseif ( 'page' === $ogtrips_type ) {
	$ogtrips_label = '';
}
?>
<a href="<?php the_permalink(); ?>" class="post reveal<?php echo $ogtrips_delay ? ' reveal-d' . (int) $ogtrips_delay : ''; ?>">
	<div class="media">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'ogt-card', [ 'loading' => 'lazy', 'alt' => '' ] ); ?>
		<?php else : ?>
			<span class="media-empty" aria-hidden="true"></span>
		<?php endif; ?>
	</div>
	<div class="post-meta">
		<?php if ( '' !== $ogtrips_label ) : ?>
			<span class="cat"><?php echo esc_html( $ogtrips_label ); ?></span>
		<?php endif; ?>
		<?php if ( in_array( $ogtrips_type, [ 'ogt_guide', 'post' ], true ) ) : ?>
			<span>
				<?php
				$ogtrips_min = ogtrips_read_minutes( get_post() );
				/* translators: %d: minutes */
				echo esc_html( sprintf( _n( '%d min read', '%d min read', $ogtrips_min, 'ogtrips' ), $ogtrips_min ) );
				?>
			</span>
		<?php endif; ?>
	</div>
	<h3><?php the_title(); ?></h3>
</a>
