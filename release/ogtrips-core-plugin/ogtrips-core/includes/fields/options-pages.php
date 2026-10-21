<?php
/**
 * SCF options page "Site Settings" — read with get_field( 'name', 'option' ).
 * The homepage fields live on the Home page itself (Pages → Home; see homepage.php).
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Site Settings page (Editors can use it: capability edit_pages).
 */
function ogtrips_core_options_pages() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

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
