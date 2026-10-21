<?php
/**
 * Clean user screens: WordPress's profile form shows editor preferences, colour schemes,
 * website, nickname, Gravatar and application passwords that this site never uses. They
 * are hidden (assets/admin.css), and Client profiles hide the trip-expert fields, bio and
 * Yoast author settings. Body classes tell the stylesheet who is being edited.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

// No admin colour scheme picker (WordPress then omits the whole row).
remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );

/**
 * The user whose profile is on screen (user-edit.php?user_id=… or profile.php).
 *
 * @return WP_User|null
 */
function ogtrips_core_profile_user() {
	global $pagenow;

	if ( 'profile.php' === $pagenow ) {
		return wp_get_current_user();
	}

	if ( 'user-edit.php' === $pagenow ) {
		$id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
		$user = $id ? get_userdata( $id ) : false;

		return $user ? $user : null;
	}

	return null;
}

/**
 * Body classes for the stylesheet: ogt-user-form, ogt-editing-client, ogt-editing-staff, ogt-not-admin.
 *
 * @param string $classes Body classes.
 * @return string
 */
function ogtrips_core_user_form_body_class( $classes ) {
	global $pagenow;

	if ( in_array( $pagenow, [ 'user-edit.php', 'profile.php', 'user-new.php' ], true ) ) {
		$classes .= ' ogt-user-form';
		$user     = ogtrips_core_profile_user();

		if ( $user ) {
			$classes .= ogtrips_core_is_client( $user ) ? ' ogt-editing-client' : ' ogt-editing-staff';
		}
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		$classes .= ' ogt-not-admin';
	}

	return $classes;
}
add_filter( 'admin_body_class', 'ogtrips_core_user_form_body_class' );

/**
 * Application passwords (REST/API access) only for administrators' own accounts.
 *
 * @param bool    $available Whether available.
 * @param WP_User $user      User being edited.
 * @return bool
 */
function ogtrips_core_application_passwords( $available, $user ) {
	return $available && $user instanceof WP_User && user_can( $user, 'manage_options' ) && current_user_can( 'manage_options' );
}
add_filter( 'wp_is_application_passwords_available_for_user', 'ogtrips_core_application_passwords', 10, 2 );

/**
 * Plain-language headings: "Contact Info" → "Contact", "About the user" → "Profile" etc.
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $domain      Text domain.
 * @return string
 */
function ogtrips_core_user_form_labels( $translation, $text, $domain ) {
	global $pagenow;

	if ( 'default' !== $domain || ! in_array( $pagenow, [ 'user-edit.php', 'profile.php', 'user-new.php' ], true ) ) {
		return $translation;
	}

	$user   = ogtrips_core_profile_user();
	$client = $user && ogtrips_core_is_client( $user );

	switch ( $text ) {
		case 'Contact Info':
			return __( 'Contact', 'ogtrips-core' );
		case 'About the user':
			return $client ? __( 'Client', 'ogtrips-core' ) : __( 'Shown on the website', 'ogtrips-core' );
		case 'About Yourself':
			return __( 'Shown on the website', 'ogtrips-core' );
		case 'Biographical Info':
			return __( 'Short bio (tour guide author card)', 'ogtrips-core' );
		case 'Account Management':
			return __( 'Password & login', 'ogtrips-core' );
	}

	return $translation;
}
add_filter( 'gettext', 'ogtrips_core_user_form_labels', 10, 3 );

/**
 * No social-profile rows (Yoast adds Facebook, MySpace, SoundCloud… to every profile).
 *
 * @param array<string,string> $methods Contact methods.
 * @return array<string,string>
 */
function ogtrips_core_contact_methods( $methods ) {
	foreach ( [ 'facebook', 'instagram', 'linkedin', 'myspace', 'pinterest', 'soundcloud', 'tumblr', 'wikipedia', 'twitter', 'youtube', 'mastodon', 'bluesky', 'threads' ] as $key ) {
		unset( $methods[ $key ] );
	}

	return $methods;
}
add_filter( 'user_contactmethods', 'ogtrips_core_contact_methods', 99 );

/**
 * Roles this site uses. Everything else (Subscriber, Contributor, Yoast's SEO roles) is
 * hidden from the role lists — the roles still exist, nothing is deleted.
 *
 * @return string[]
 */
function ogtrips_core_site_roles() {
	return [ 'administrator', 'editor', 'author', OGTRIPS_CORE_CLIENT_ROLE ];
}

/**
 * Administrators choose from the site's four roles only (the team already gets Client only).
 *
 * @param array<string,array<string,mixed>> $roles Editable roles.
 * @return array<string,array<string,mixed>>
 */
function ogtrips_core_admin_editable_roles( $roles ) {
	if ( current_user_can( 'manage_options' ) ) {
		$roles = array_intersect_key( $roles, array_flip( ogtrips_core_site_roles() ) );
	}

	return $roles;
}
add_filter( 'editable_roles', 'ogtrips_core_admin_editable_roles', 20 );

/**
 * Plain-language role names wherever WordPress prints them (dropdowns, user list, filters).
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $context     Context.
 * @return string
 */
function ogtrips_core_role_labels( $translation, $text, $context ) {
	if ( 'User role' !== $context ) {
		return $translation;
	}

	$labels = [
		'Administrator' => __( 'Administrator (developer)', 'ogtrips-core' ),
		'Editor'        => __( 'Editor (OgTrips team)', 'ogtrips-core' ),
		'Author'        => __( 'Trip expert (author)', 'ogtrips-core' ),
		'Client'        => __( 'Client (traveller)', 'ogtrips-core' ),
	];

	return $labels[ $text ] ?? $translation;
}
add_filter( 'gettext_with_context', 'ogtrips_core_role_labels', 10, 3 );
