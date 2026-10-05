<?php
/**
 * Plugin Name:       OgTrips Core
 * Description:       OgTrips content model (trips, tour guides, reviews, moments, enquiries), admin clean-up, enquiry handler and demo-content seeder. Content lives here so it survives a theme change.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            OgTrips
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ogtrips-core
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

define( 'OGTRIPS_CORE_VERSION', '0.1.0' );
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
