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

	$name = sanitize_key( $name );

	// Brand logo (Lucide has no brand icons): filled WhatsApp glyph, sized like the line icons.
	if ( 'whatsapp' === $name ) {
		return '<svg class="' . esc_attr( trim( 'lucide icon-whatsapp ' . $class ) ) . '" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.26-.46-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.42-.08-.12-.27-.2-.57-.34M12.05 21.79h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88a9.82 9.82 0 0 1 6.99 2.9 9.82 9.82 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.88 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89a11.82 11.82 0 0 0-3.48-8.41z"/></svg>';
	}

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

	// Pages that exist get a link (About us under Explore; FAQs, Cancellation policy, Privacy under Support).
	$page_link = static function ( $slug ) {
		$page = get_page_by_path( $slug );
		return ( $page && 'publish' === $page->post_status ) ? (string) get_permalink( $page ) : '';
	};
	if ( 'footer-explore' === $location ) {
		$about = $page_link( 'about-us' );
		if ( '' !== $about ) {
			$menus['footer-explore'][] = [ $about, __( 'About us', 'ogtrips' ) ];
		}
		$blog = (int) get_option( 'page_for_posts' );
		if ( $blog && wp_count_posts( 'post' )->publish ) {
			$menus['footer-explore'][] = [ (string) get_permalink( $blog ), __( 'Blog', 'ogtrips' ) ];
		}
	}
	if ( 'footer-support' === $location ) {
		foreach ( [ 'faqs' => __( 'FAQs', 'ogtrips' ), 'cancellation-policy' => __( 'Cancellation policy', 'ogtrips' ) ] as $slug => $label ) {
			$url = $page_link( $slug );
			if ( '' !== $url ) {
				$menus['footer-support'][] = [ $url, $label ];
			}
		}
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
