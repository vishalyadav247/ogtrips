<?php
/**
 * SCF options pages "Homepage" and "Site Settings". Values are read with
 * get_field( 'name', 'option' ); field names are unique across both pages.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the two options pages (Editors can use them: capability edit_pages).
 */
function ogtrips_core_options_pages() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		[
			'page_title'      => __( 'Homepage', 'ogtrips-core' ),
			'menu_title'      => __( 'Homepage', 'ogtrips-core' ),
			'menu_slug'       => 'ogtrips-homepage',
			'capability'      => 'edit_pages',
			'icon_url'        => 'dashicons-admin-home',
			'position'        => 26,
			'redirect'        => false,
			'update_button'   => __( 'Save homepage', 'ogtrips-core' ),
			'updated_message' => __( 'Homepage saved.', 'ogtrips-core' ),
		]
	);

	acf_add_options_page(
		[
			'page_title'      => __( 'Site Settings', 'ogtrips-core' ),
			'menu_title'      => __( 'Site Settings', 'ogtrips-core' ),
			'menu_slug'       => 'ogtrips-site-settings',
			'capability'      => 'edit_pages',
			'icon_url'        => 'dashicons-admin-settings',
			'position'        => 27,
			'redirect'        => false,
			'update_button'   => __( 'Save settings', 'ogtrips-core' ),
			'updated_message' => __( 'Settings saved.', 'ogtrips-core' ),
		]
	);
}
add_action( 'acf/init', 'ogtrips_core_options_pages' );
