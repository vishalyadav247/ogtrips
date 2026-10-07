<?php
/**
 * Template tags used across the child theme templates.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns an inline SVG that references a Lucide icon in the local sprite (assets/icons/sprite.svg).
 *
 * Same names as the design's data-lucide attributes, same markup Lucide generates (class "lucide"),
 * so the design CSS applies unchanged. Decorative: hidden from assistive tech — give the parent
 * link/button an accessible name.
 *
 * @param string $name  Lucide icon name, e.g. 'map-pin'.
 * @param string $class Extra CSS classes.
 * @return string
 */
function ogtrips_icon( $name, $class = '' ) {
	static $sprite = null;

	if ( null === $sprite ) {
		$sprite = get_theme_file_uri( 'assets/icons/sprite.svg' ) . '?ver=' . ogtrips_asset_version( 'assets/icons/sprite.svg' );
	}

	$name    = sanitize_key( $name );
	$classes = trim( 'lucide lucide-' . $name . ' ' . $class );
	$href    = $sprite . '#' . $name;

	return sprintf(
		'<svg class="%1$s" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><use href="%2$s"></use></svg>',
		esc_attr( $classes ),
		esc_url( $href )
	);
}

/**
 * Default links per menu location, used until the merchant builds the menu in Appearance → Menus.
 *
 * @param string $location Menu location.
 * @return array<int,array{0:string,1:string}> [ url, label ] pairs.
 */
function ogtrips_default_menu( $location ) {
	$home   = home_url( '/' );
	$trips  = get_post_type_archive_link( 'ogt_itinerary' );
	$guides = get_post_type_archive_link( 'ogt_guide' );

	$menus = [
		'primary'        => [
			[ $trips ? $trips : $home . '#trips', __( 'Trips', 'ogtrips' ) ],
			[ $home . '#why', __( 'Why OG', 'ogtrips' ) ],
			[ $home . '#reviews', __( 'Reviews', 'ogtrips' ) ],
			[ $guides ? $guides : $home, __( 'Travel Guide', 'ogtrips' ) ],
			[ $home . '#contact', __( 'Contact', 'ogtrips' ) ],
		],
		'footer-explore' => [
			[ $trips ? $trips : $home . '#trips', __( 'Trips', 'ogtrips' ) ],
			[ $guides ? $guides : $home, __( 'Travel guide', 'ogtrips' ) ],
			[ $home . '#why', __( 'Why OgTrips', 'ogtrips' ) ],
			[ $home . '#reviews', __( 'Reviews', 'ogtrips' ) ],
		],
		'footer-support' => [
			[ $home . '#contact', __( 'Contact', 'ogtrips' ) ],
		],
	];

	if ( 'footer-support' === $location ) {
		$privacy = get_privacy_policy_url();
		if ( $privacy ) {
			$menus['footer-support'][] = [ $privacy, __( 'Privacy', 'ogtrips' ) ];
		}
	}

	return $menus[ $location ] ?? [];
}

/**
 * Prints a menu location as a plain <ul> (design markup), or the default links when no menu is assigned.
 *
 * @param string $location Menu location.
 * @param string $class    Class of the <ul>.
 */
function ogtrips_menu( $location, $class = '' ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu(
			[
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => $class,
				'depth'          => 1,
				'fallback_cb'    => false,
			]
		);
		return;
	}

	echo '<ul' . ( '' !== $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>';
	foreach ( ogtrips_default_menu( $location ) as $item ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $item[0] ), esc_html( $item[1] ) );
	}
	echo '</ul>';
}

/**
 * Star icons for a rating.
 *
 * @param int $count Number of stars (1–5).
 * @return string
 */
function ogtrips_stars( $count = 5 ) {
	$count = max( 1, min( 5, (int) $count ) );

	/* translators: %d: star rating */
	return '<div class="stars" role="img" aria-label="' . esc_attr( sprintf( __( '%d out of 5 stars', 'ogtrips' ), $count ) ) . '">' . str_repeat( ogtrips_icon( 'star' ), $count ) . '</div>';
}
