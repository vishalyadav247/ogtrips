<?php
/**
 * Clean wp-admin for the merchant (anyone without manage_options, i.e. the Editor
 * account): only content menus, no nags/notices from plugins, no clutter.
 * Administrators keep the full admin.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the current user the merchant (not an administrator)?
 *
 * @return bool
 */
function ogtrips_core_is_merchant() {
	return is_user_logged_in() && ! current_user_can( 'manage_options' );
}

/**
 * Hides menus the merchant doesn't need.
 */
function ogtrips_core_merchant_menus() {
	if ( ! ogtrips_core_is_merchant() ) {
		return;
	}

	global $menu;

	foreach ( [ 'tools.php', 'edit.php?post_type=page' ] as $slug ) {
		remove_menu_page( $slug );
	}

	// Yoast SEO: its top-level slug differs by role and version (wpseo_dashboard, wpseo_page_academy…).
	foreach ( (array) $menu as $item ) {
		if ( isset( $item[2] ) && str_starts_with( (string) $item[2], 'wpseo_' ) ) {
			remove_menu_page( $item[2] );
		}
	}
}
add_action( 'admin_menu', 'ogtrips_core_merchant_menus', 999 );

/**
 * Hidden menus are also blocked by URL: Pages (list, new, edit), Tools and Yoast screens are admin-only.
 * (Editors keep the edit_pages capability because the Homepage/Site Settings screens use it.)
 */
function ogtrips_core_merchant_block_screens() {
	global $pagenow;

	if ( ! ogtrips_core_is_merchant() || wp_doing_ajax() ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only routing check.
	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
	$post_id   = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	// phpcs:enable

	$blocked = in_array( $pagenow, [ 'tools.php', 'import.php', 'export.php' ], true )
		|| ( in_array( $pagenow, [ 'edit.php', 'post-new.php' ], true ) && 'page' === $post_type )
		|| ( 'post.php' === $pagenow && $post_id && 'page' === get_post_type( $post_id ) )
		|| ( 'admin.php' === $pagenow && str_starts_with( $page, 'wpseo_' ) );

	if ( $blocked ) {
		wp_die( esc_html__( 'Sorry, this screen is only for the site administrator.', 'ogtrips-core' ), '', [ 'response' => 403, 'back_link' => true ] );
	}
}
add_action( 'admin_init', 'ogtrips_core_merchant_block_screens' );

/**
 * Admin bar: no WordPress logo, Yoast menu, "New page" or update items; "Howdy" removed.
 *
 * @param WP_Admin_Bar $bar Admin bar.
 */
function ogtrips_core_merchant_admin_bar( $bar ) {
	$bar->remove_node( 'wp-logo' );

	if ( ! ogtrips_core_is_merchant() ) {
		return;
	}

	foreach ( [ 'wpseo-menu', 'new-page', 'new-user', 'updates', 'customize' ] as $node ) {
		$bar->remove_node( $node );
	}

	$account = $bar->get_node( 'my-account' );

	if ( $account ) {
		$user = wp_get_current_user();
		$bar->add_node(
			[
				'id'    => 'my-account',
				'title' => '<span class="display-name">' . esc_html( $user->display_name ) . '</span>' . get_avatar( $user->ID, 26 ),
			]
		);
	}
}
add_action( 'admin_bar_menu', 'ogtrips_core_merchant_admin_bar', 999 );

/**
 * Keeps only SCF's own notices (e.g. "Settings saved") for the merchant; plugin
 * promos, Yoast notifications and nags are removed before they print.
 */
function ogtrips_core_merchant_notices() {
	global $wp_filter;

	if ( ! ogtrips_core_is_merchant() ) {
		return;
	}

	foreach ( [ 'admin_notices', 'all_admin_notices', 'user_admin_notices' ] as $hook ) {
		if ( empty( $wp_filter[ $hook ] ) ) {
			continue;
		}

		foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( ! ogtrips_core_is_allowed_notice( $callback['function'] ) ) {
					remove_action( $hook, $callback['function'], $priority );
				}
			}
		}
	}
}
add_action( 'in_admin_header', 'ogtrips_core_merchant_notices', 999 );

/**
 * Whether a notice callback belongs to SCF or OgTrips.
 *
 * @param callable $callback Hooked callback.
 * @return bool
 */
function ogtrips_core_is_allowed_notice( $callback ) {
	if ( is_array( $callback ) ) {
		$callback = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
	} elseif ( $callback instanceof Closure ) {
		return false;
	}

	// Core notices the merchant needs (e.g. "confirm your new email address").
	$core = [ 'new_user_email_admin_notice' ];

	return is_string( $callback ) && ( in_array( $callback, $core, true ) || 1 === preg_match( '/^(acf|ogtrips)/i', $callback ) );
}

/**
 * Dashboard: only the OgTrips widget for the merchant; no welcome panel for anyone.
 */
function ogtrips_core_merchant_dashboard() {
	remove_action( 'welcome_panel', 'wp_welcome_panel' );

	if ( ! ogtrips_core_is_merchant() ) {
		return;
	}

	$widgets = [
		'dashboard_primary'                 => 'side',
		'dashboard_quick_press'             => 'side',
		'dashboard_site_health'             => 'normal',
		'dashboard_activity'                => 'normal',
		'dashboard_right_now'               => 'normal',
		'dashboard_php_nag'                 => 'normal',
		'wpseo-dashboard-overview'          => 'normal',
		'wpseo-wincher-dashboard-overview'  => 'normal',
	];

	foreach ( $widgets as $id => $context ) {
		remove_meta_box( $id, 'dashboard', $context );
	}
}
add_action( 'wp_dashboard_setup', 'ogtrips_core_merchant_dashboard', 999 );

/**
 * Removes Yoast's SEO score columns from list screens for the merchant.
 *
 * @param array<string,string> $columns List columns.
 * @return array<string,string>
 */
function ogtrips_core_merchant_columns( $columns ) {
	if ( ogtrips_core_is_merchant() ) {
		foreach ( array_keys( $columns ) as $key ) {
			if ( str_starts_with( $key, 'wpseo-' ) ) {
				unset( $columns[ $key ] );
			}
		}
	}

	return $columns;
}

/**
 * Hooks the column clean-up on every list screen.
 */
function ogtrips_core_merchant_column_hooks() {
	foreach ( get_post_types( [ 'show_ui' => true ] ) as $post_type ) {
		add_filter( "manage_{$post_type}_posts_columns", 'ogtrips_core_merchant_columns', 99 );
	}
}
add_action( 'admin_init', 'ogtrips_core_merchant_column_hooks' );

/**
 * Footer: brand text instead of WordPress credits; no version number for the merchant.
 *
 * @return string
 */
function ogtrips_core_admin_footer_text() {
	return esc_html__( 'OgTrips — Where to next?', 'ogtrips-core' );
}
add_filter( 'admin_footer_text', 'ogtrips_core_admin_footer_text' );

/**
 * Hides the WordPress version in the admin footer for the merchant.
 *
 * @param string $text Footer version text.
 * @return string
 */
function ogtrips_core_admin_footer_version( $text ) {
	return ogtrips_core_is_merchant() ? '' : $text;
}
add_filter( 'update_footer', 'ogtrips_core_admin_footer_version', 99 );

/**
 * Profile screen: the avatar comes from the OgTrips "Photo" field, not Gravatar.
 *
 * @return string
 */
function ogtrips_core_profile_picture_description() {
	return esc_html__( 'Upload your photo in the "Photo" field under OgTrips profile below.', 'ogtrips-core' );
}
add_filter( 'user_profile_picture_description', 'ogtrips_core_profile_picture_description' );
