<?php
/**
 * Tour Guide article (also used for Blog posts via single.php) — approved design guide.html.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$ogtrips_id     = get_the_ID();
	$ogtrips_type   = get_post_type();
	$ogtrips_author = (int) get_post_field( 'post_author', $ogtrips_id );
	$ogtrips_role   = (string) get_user_meta( $ogtrips_author, 'byline_role', true );
	$ogtrips_dest   = get_the_terms( $ogtrips_id, 'ogt_destination' );
	$ogtrips_dest   = $ogtrips_dest && ! is_wp_error( $ogtrips_dest ) ? $ogtrips_dest[0]->name : '';
	$ogtrips_label  = implode( ' · ', array_filter( [ ogtrips_article_label( $ogtrips_id ), $ogtrips_dest ] ) );
	$ogtrips_toc    = ogtrips_prepare_toc( (string) apply_filters( 'the_content', get_the_content() ) );
	$ogtrips_min    = ogtrips_read_minutes( $ogtrips_id );
	$ogtrips_glance = array_filter(
		[
			[ 'sun', __( 'Best time', 'ogtrips' ), (string) ogtrips_field( 'glance_best_time', $ogtrips_id ) ],
			[ 'wallet', __( 'Daily budget', 'ogtrips' ), (string) ogtrips_field( 'glance_budget', $ogtrips_id ) ],
			[ 'calendar-days', __( 'Ideal length', 'ogtrips' ), (string) ogtrips_field( 'glance_length', $ogtrips_id ) ],
			[ 'plane', __( 'Visa', 'ogtrips' ), (string) ogtrips_field( 'glance_visa', $ogtrips_id ) ],
		],
		static function ( $row ) {
			return '' !== $row[2];
		}
	);
	$ogtrips_caption = (string) ogtrips_field( 'cover_caption', $ogtrips_id );
	$ogtrips_url     = get_permalink();
	?>
<header class="article-head container">
	<?php if ( '' !== $ogtrips_label ) : ?>
		<span class="label reveal"><?php echo esc_html( $ogtrips_label ); ?></span>
	<?php endif; ?>
	<h1 class="reveal"><?php echo ogtrips_em( get_post_field( 'post_title', get_the_ID() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by ogtrips_em(). ?></h1>
	<div class="byline reveal">
		<?php echo get_avatar( $ogtrips_author, 120, '', '' ); ?>
		<div><strong><?php the_author(); ?></strong><?php echo '' !== $ogtrips_role ? ' · ' . esc_html( $ogtrips_role ) : ''; ?>
			<small>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: date, 2: minutes */
						__( 'Updated %1$s · %2$s min read', 'ogtrips' ),
						get_the_modified_date( 'j M Y' ),
						number_format_i18n( $ogtrips_min )
					)
				);
				?>
			</small>
		</div>
	</div>
</header>

	<?php if ( has_post_thumbnail() ) : ?>
<figure class="cover reveal">
		<?php the_post_thumbnail( 'ogt-hero', [ 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw' ] ); ?>
		<?php if ( '' !== $ogtrips_caption ) : ?>
		<figcaption><?php echo esc_html( $ogtrips_caption ); ?></figcaption>
		<?php endif; ?>
</figure>
	<?php endif; ?>

<div class="container">
	<div class="read-layout">
		<aside class="toc" aria-label="<?php esc_attr_e( 'Table of contents', 'ogtrips' ); ?>">
			<?php if ( $ogtrips_toc['items'] ) : ?>
				<h4><?php echo 'post' === $ogtrips_type ? esc_html__( 'In this article', 'ogtrips' ) : esc_html__( 'In this guide', 'ogtrips' ); ?></h4>
				<ol>
					<?php foreach ( $ogtrips_toc['items'] as $ogtrips_i => $ogtrips_item ) : ?>
						<li><a href="#<?php echo esc_attr( $ogtrips_item[0] ); ?>"<?php echo 0 === $ogtrips_i ? ' class="is-active"' : ''; ?>><?php echo esc_html( $ogtrips_item[1] ); ?></a></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
			<div class="share">
				<a href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( get_the_title() . ' ' . $ogtrips_url ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Share on WhatsApp', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $ogtrips_url ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Share on Facebook', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<a href="<?php echo esc_url( $ogtrips_url ); ?>" data-copy-link aria-label="<?php esc_attr_e( 'Copy link', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>
		</aside>

		<article class="prose">
			<?php if ( $ogtrips_glance ) : ?>
				<div class="glance">
					<h4>
						<?php
						/* translators: %s: destination */
						echo esc_html( '' !== $ogtrips_dest ? sprintf( __( '%s at a glance', 'ogtrips' ), $ogtrips_dest ) : __( 'At a glance', 'ogtrips' ) );
						?>
					</h4>
					<?php foreach ( $ogtrips_glance as $ogtrips_row ) : ?>
						<div><?php echo ogtrips_icon( $ogtrips_row[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><small><?php echo esc_html( $ogtrips_row[1] ); ?></small><strong><?php echo esc_html( $ogtrips_row[2] ); ?></strong></span></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php echo $ogtrips_toc['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- post content after the_content filters. ?>

			<?php $ogtrips_bio = get_the_author_meta( 'description', $ogtrips_author ); ?>
			<?php if ( '' !== $ogtrips_bio ) : ?>
				<div class="author-card">
					<?php echo get_avatar( $ogtrips_author, 160, '', '', [ 'loading' => 'lazy' ] ); ?>
					<div><strong>
						<?php
						/* translators: %s: author name */
						echo esc_html( sprintf( __( 'Written by %s', 'ogtrips' ), get_the_author() ) );
						?>
					</strong><p class="muted"><?php echo esc_html( $ogtrips_bio ); ?></p></div>
				</div>
			<?php endif; ?>
		</article>
	</div>
</div>

	<?php
	$ogtrips_related = new WP_Query(
		[
			'post_type'           => $ogtrips_type,
			'posts_per_page'      => 3,
			'post__not_in'        => [ $ogtrips_id ],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		]
	);
	$ogtrips_all     = 'post' === $ogtrips_type ? get_permalink( (int) get_option( 'page_for_posts' ) ) : get_post_type_archive_link( 'ogt_guide' );
	?>
	<?php if ( $ogtrips_related->have_posts() ) : ?>
<section class="section">
	<div class="container">
		<div class="section-top">
			<div class="reveal"><span class="label"><?php esc_html_e( 'Keep reading', 'ogtrips' ); ?></span><h2 class="display-sm"><?php echo 'post' === $ogtrips_type ? wp_kses( __( 'More from the <em>blog</em>', 'ogtrips' ), [ 'em' => [] ] ) : wp_kses( __( 'More from the <em>travel guide</em>', 'ogtrips' ), [ 'em' => [] ] ); ?></h2></div>
			<?php if ( $ogtrips_all ) : ?>
				<a href="<?php echo esc_url( (string) $ogtrips_all ); ?>" class="link-arrow reveal"><?php echo 'post' === $ogtrips_type ? esc_html__( 'All posts', 'ogtrips' ) : esc_html__( 'All guides', 'ogtrips' ); ?> <?php echo ogtrips_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<?php endif; ?>
		</div>
		<div class="posts">
			<?php
			$ogtrips_i = 0;
			while ( $ogtrips_related->have_posts() ) :
				$ogtrips_related->the_post();
				get_template_part( 'template-parts/card-post', null, [ 'delay' => $ogtrips_i++ % 3 ] );
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
