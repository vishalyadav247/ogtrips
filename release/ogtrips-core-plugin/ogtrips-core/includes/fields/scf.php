<?php
/**
 * Secure Custom Fields set-up: field groups live as JSON in acf-json/ (versioned),
 * and the SCF builder screens are only for administrators on a local site.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Saves field groups edited in the SCF UI into the plugin (not the theme).
 *
 * @return string
 */
function ogtrips_core_scf_save_json() {
	return OGTRIPS_CORE_DIR . 'acf-json';
}
add_filter( 'acf/settings/save_json', 'ogtrips_core_scf_save_json' );

/**
 * Loads field groups only from the plugin's acf-json/.
 *
 * @return string[]
 */
function ogtrips_core_scf_load_json() {
	return [ OGTRIPS_CORE_DIR . 'acf-json' ];
}
add_filter( 'acf/settings/load_json', 'ogtrips_core_scf_load_json' );

/**
 * SCF builder menu: administrators on the local site only (field groups ship as JSON).
 *
 * @return bool
 */
function ogtrips_core_scf_show_admin() {
	return current_user_can( 'manage_options' ) && 'local' === wp_get_environment_type();
}
add_filter( 'acf/settings/show_admin', 'ogtrips_core_scf_show_admin' );
