<?php
/**
 * Plugin Name:       OgTrips Core
 * Description:       OgTrips content model (trips, tour guides, reviews, moments, enquiries), admin clean-up, enquiry handler and demo-content seeder. Content lives here so it survives a theme change.
 * Version:           0.2.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Requires Plugins:  secure-custom-fields
 * Author:            OgTrips
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ogtrips-core
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

define( 'OGTRIPS_CORE_VERSION', '0.2.0' );
define( 'OGTRIPS_CORE_FILE', __FILE__ );
define( 'OGTRIPS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'OGTRIPS_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Loads every PHP file in includes/<group>/ (post-types, fields, admin, enquiry, cli).
 * Each file only registers hooks; index.php guards are skipped.
 */
function ogtrips_core_load_includes() {
	$groups = [ 'post-types', 'fields', 'admin', 'enquiry', 'cli' ];

	foreach ( $groups as $group ) {
		$files = glob( OGTRIPS_CORE_DIR . 'includes/' . $group . '/*.php' ) ?: [];

		foreach ( $files as $file ) {
			if ( 'index.php' !== basename( $file ) ) {
				require_once $file;
			}
		}
	}
}
ogtrips_core_load_includes();

/**
 * Rewrite rules must be rebuilt when our post types/taxonomies appear, change or disappear.
 * On activation (and after every plugin update, via the version check) the rules are
 * flushed once on init, after all types are registered (see ogtrips_core_maybe_flush_rewrites()).
 */
function ogtrips_core_activate() {
	update_option( 'ogtrips_core_rewrite_version', '', true );
}
register_activation_hook( __FILE__, 'ogtrips_core_activate' );

/**
 * Drops the stored rules so WordPress rebuilds them without our types.
 */
function ogtrips_core_deactivate() {
	delete_option( 'rewrite_rules' );
}
register_deactivation_hook( __FILE__, 'ogtrips_core_deactivate' );

/**
 * Flushes rewrite rules once after activation or a plugin update (autoloaded option: no extra query).
 */
function ogtrips_core_maybe_flush_rewrites() {
	if ( get_option( 'ogtrips_core_rewrite_version' ) !== OGTRIPS_CORE_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'ogtrips_core_rewrite_version', OGTRIPS_CORE_VERSION, true );
	}
}
add_action( 'init', 'ogtrips_core_maybe_flush_rewrites', 99 );
