<?php
/**
 * Generic page (Privacy, Cancellation policy, FAQs…).
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
<header class="article-head container listing-head">
	<h1><?php echo ogtrips_em( get_post_field( 'post_title', get_the_ID() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by ogtrips_em(). ?></h1>
</header>
<div class="container">
	<article class="prose page-prose">
		<?php the_content(); ?>
	</article>
</div>
	<?php
endwhile;

get_footer();
