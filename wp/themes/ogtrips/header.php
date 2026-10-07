<?php
/**
 * Site header — replaces GeneratePress's header with the approved design's nav.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

$ogtrips_phone   = (string) ogtrips_setting( 'phone' );
$ogtrips_reserve = is_singular( 'ogt_itinerary' ) ? '#book' : ogtrips_contact_url();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0a2540">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'ogtrips' ); ?></a>
<?php if ( is_singular( [ 'ogt_guide', 'post' ] ) ) : ?>
<div class="progress" aria-hidden="true"></div>
<?php endif; ?>

<div class="nav-wrap">
	<nav class="nav" aria-label="<?php esc_attr_e( 'Main', 'ogtrips' ); ?>">
		<?php get_template_part( 'template-parts/logo' ); ?>
		<?php ogtrips_menu( 'primary', 'menu' ); ?>
		<form class="nav-search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo ogtrips_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search Bali, Ladakh, Maldives…', 'ogtrips' ); ?>" aria-label="<?php esc_attr_e( 'Search trips', 'ogtrips' ); ?>">
		</form>
		<a href="#" class="nav-icon account" aria-label="<?php esc_attr_e( 'My account', 'ogtrips' ); ?>"><?php echo ogtrips_icon( 'user-round' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<a href="<?php echo esc_url( $ogtrips_reserve ); ?>" class="btn btn--coral"><?php esc_html_e( 'Reserve', 'ogtrips' ); ?> <span class="arrow"><?php echo ogtrips_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a>
		<button class="burger" aria-label="<?php esc_attr_e( 'Menu', 'ogtrips' ); ?>" aria-expanded="false"><?php echo ogtrips_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
	</nav>
</div>
<div class="mobile-menu">
	<div>
		<form class="mm-search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>"><input type="search" name="s" placeholder="<?php esc_attr_e( 'Where to next?', 'ogtrips' ); ?>" aria-label="<?php esc_attr_e( 'Search trips', 'ogtrips' ); ?>"></form>
		<?php ogtrips_menu( 'primary', '' ); ?>
	</div>
	<?php if ( '' !== $ogtrips_phone ) : ?>
		<div><small><?php esc_html_e( 'Talk to a trip expert', 'ogtrips' ); ?></small><p style="font-size:1.3rem;margin:4px 0 0"><a href="tel:<?php echo esc_attr( ogtrips_phone_digits( $ogtrips_phone ) ); ?>"><?php echo esc_html( $ogtrips_phone ); ?></a></p></div>
	<?php endif; ?>
</div>

<main id="main">
