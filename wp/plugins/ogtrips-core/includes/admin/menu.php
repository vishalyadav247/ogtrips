<?php
/**
 * Admin menu: order, Enquiries "new" bubble, and the small admin stylesheet.
 *
 * Order: Dashboard · Trips · Tour Guides · Blog · Reviews · Moments · Enquiries ·
 * Homepage · Site Settings · Media · (administrator-only items after).
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'custom_menu_order', '__return_true' );

/**
 * Puts our content menus first, in the agreed order.
 *
 * @param string[] $order Current menu order (slugs).
 * @return string[]
 */
function ogtrips_core_menu_order( $order ) {
	$ours = [
		'index.php',
		'edit.php?post_type=ogt_itinerary',
		'edit.php?post_type=ogt_guide',
		'edit.php',
		'edit.php?post_type=ogt_review',
		'edit.php?post_type=ogt_moment',
		'edit.php?post_type=ogt_enquiry',
		'edit.php?post_type=page',
		'ogtrips-site-settings',
		'upload.php',
		'separator1',
	];

	return array_merge( $ours, array_values( array_diff( (array) $order, $ours ) ) );
}
add_filter( 'menu_order', 'ogtrips_core_menu_order' );

/**
 * Adds the count of new enquiries to the Enquiries menu item.
 */
function ogtrips_core_enquiry_menu_bubble() {
	global $menu;

	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}

	$count = ogtrips_core_new_enquiry_count();

	if ( ! $count ) {
		return;
	}

	foreach ( $menu as $index => $item ) {
		if ( isset( $item[2] ) && 'edit.php?post_type=ogt_enquiry' === $item[2] ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- standard way to add a menu count bubble.
			$menu[ $index ][0] .= sprintf(
				' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>',
				$count,
				esc_html( sprintf( /* translators: %d: number of new enquiries */ _n( '%d new enquiry', '%d new enquiries', $count, 'ogtrips-core' ), $count ) )
			);
			break;
		}
	}
}
add_action( 'admin_menu', 'ogtrips_core_enquiry_menu_bubble', 99 );

/**
 * The plugin's admin stylesheet (wp-admin only).
 */
function ogtrips_core_admin_assets() {
	$file = OGTRIPS_CORE_DIR . 'assets/admin.css';

	wp_enqueue_style(
		'ogtrips-core-admin',
		OGTRIPS_CORE_URL . 'assets/admin.css',
		[],
		file_exists( $file ) ? (string) filemtime( $file ) : OGTRIPS_CORE_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'ogtrips_core_admin_assets' );
