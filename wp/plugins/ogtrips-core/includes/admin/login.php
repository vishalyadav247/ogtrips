<?php
/**
 * Branded login screen (wp-login.php): OgTrips logo, brand fonts and colours, a homepage photo
 * behind a centred card; works on phones. Styles in assets/login.css.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the login stylesheet with the theme's fonts, logo and a hero photo.
 */
function ogtrips_core_login_assets() {
	wp_enqueue_style( 'ogtrips-login', OGTRIPS_CORE_URL . 'assets/login.css', [ 'login' ], (string) filemtime( OGTRIPS_CORE_DIR . 'assets/login.css' ) );

	$theme = get_stylesheet_directory();
	$fonts = '';
	foreach ( [ [ 'Poppins', 600, 'poppins-latin-600-normal' ], [ 'Poppins', 700, 'poppins-latin-700-normal' ], [ 'DM Sans', 400, 'dm-sans-latin-400-normal' ], [ 'DM Sans', 500, 'dm-sans-latin-500-normal' ] ] as $font ) {
		if ( file_exists( $theme . '/assets/fonts/' . $font[2] . '.woff2' ) ) {
			$fonts .= sprintf( '@font-face{font-family:"%1$s";font-weight:%2$d;font-style:normal;font-display:swap;src:url("%3$s") format("woff2");}', $font[0], $font[1], esc_url( get_theme_file_uri( 'assets/fonts/' . $font[2] . '.woff2' ) ) );
		}
	}

	$css = $fonts;
	if ( file_exists( $theme . '/assets/img/ogtrips-mark.svg' ) ) {
		$css .= sprintf( ':root{--ogt-logo:url("%s");}', esc_url( get_theme_file_uri( 'assets/img/ogtrips-mark.svg' ) ) );
	}

	// Background: the first homepage hero photo, if set.
	$places = function_exists( 'get_field' ) ? get_field( 'hero_places', 'option' ) : [];
	$photo  = is_array( $places ) && ! empty( $places[0]['image'] ) ? wp_get_attachment_image_url( (int) $places[0]['image'], 'large' ) : '';
	if ( $photo ) {
		$css .= sprintf( ':root{--ogt-photo:url("%s");}', esc_url( $photo ) );
	}

	wp_add_inline_style( 'ogtrips-login', $css );
}
add_action( 'login_enqueue_scripts', 'ogtrips_core_login_assets' );

/**
 * Logo links to the site, not wordpress.org.
 *
 * @return string
 */
function ogtrips_core_login_logo_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'ogtrips_core_login_logo_url' );

/**
 * Logo text (shown next to the mark).
 *
 * @return string
 */
function ogtrips_core_login_logo_text() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'ogtrips_core_login_logo_text' );

/**
 * Short welcome line above the form on the plain log-in screen.
 *
 * @param string $message Existing message.
 * @return string
 */
function ogtrips_core_login_message( $message ) {
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

	if ( '' === $message && 'login' === $action ) {
		$message = '<p class="ogt-login-welcome"><strong>' . esc_html__( 'Welcome back', 'ogtrips-core' ) . '</strong><span>' . esc_html__( 'Log in to manage trips, tour guides and enquiries.', 'ogtrips-core' ) . '</span></p>';
	}

	return $message;
}
add_filter( 'login_message', 'ogtrips_core_login_message' );

// No language switcher on the login screen.
add_filter( 'login_display_language_dropdown', '__return_false' );
