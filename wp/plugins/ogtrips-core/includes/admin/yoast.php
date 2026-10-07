<?php
/**
 * Yoast SEO in the editing screens: its box sits below our fields.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Yoast metabox priority: low (below SCF field groups).
 *
 * @return string
 */
function ogtrips_core_yoast_metabox_priority() {
	return 'low';
}
add_filter( 'wpseo_metabox_prio', 'ogtrips_core_yoast_metabox_priority' );
