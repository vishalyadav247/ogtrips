<?php
/**
 * Comments and pingbacks off everywhere: no support on any post type, no forms,
 * no admin screens, menu, admin-bar item, widget, feed or X-Pingback header.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Removes comment/trackback support from every post type.
 */
function ogtrips_core_remove_comment_support() {
	foreach ( get_post_types() as $post_type ) {
		remove_post_type_support( $post_type, 'comments' );
		remove_post_type_support( $post_type, 'trackbacks' );
	}
}
add_action( 'init', 'ogtrips_core_remove_comment_support', 100 );

add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 10 );
add_filter( 'feed_links_show_comments_feed', '__return_false' );

/**
 * Comment feeds return 404.
 */
function ogtrips_core_block_comment_feeds() {
	if ( is_comment_feed() ) {
		wp_die( esc_html__( 'Comments are closed.', 'ogtrips-core' ), '', [ 'response' => 404 ] );
	}
}
add_action( 'template_redirect', 'ogtrips_core_block_comment_feeds', 9 );

/**
 * No pingback header or XML-RPC pingback methods.
 *
 * @param array<string,string> $headers Response headers.
 * @return array<string,string>
 */
function ogtrips_core_remove_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );

	return $headers;
}
add_filter( 'wp_headers', 'ogtrips_core_remove_pingback_header' );

/**
 * Removes pingback XML-RPC methods.
 *
 * @param array<string,callable> $methods XML-RPC methods.
 * @return array<string,callable>
 */
function ogtrips_core_remove_pingback_methods( $methods ) {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

	return $methods;
}
add_filter( 'xmlrpc_methods', 'ogtrips_core_remove_pingback_methods' );

/**
 * Pings are off, so publishing must not queue the do_pings cron job (it also only
 * produced "could not save cron event" noise under concurrent requests).
 */
function ogtrips_core_disable_ping_jobs() {
	remove_action( 'publish_post', '_publish_post_hook', 5 );
	remove_action( 'do_pings', 'do_all_pings', 10 );
	wp_clear_scheduled_hook( 'do_pings' );
}
add_action( 'init', 'ogtrips_core_disable_ping_jobs' );

/**
 * Removes the Comments menu and Discussion settings, and blocks those screens.
 */
function ogtrips_core_remove_comment_admin() {
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
}
add_action( 'admin_menu', 'ogtrips_core_remove_comment_admin', 99 );

/**
 * Redirects direct visits to the comment screens to the dashboard.
 */
function ogtrips_core_block_comment_screens() {
	global $pagenow;

	if ( in_array( $pagenow, [ 'edit-comments.php', 'comment.php', 'options-discussion.php' ], true ) ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_init', 'ogtrips_core_block_comment_screens' );

/**
 * Removes the comments dashboard box parts and the admin-bar bubble.
 */
function ogtrips_core_remove_comment_dashboard() {
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'ogtrips_core_remove_comment_dashboard' );

/**
 * Removes the admin-bar comments item.
 *
 * @param WP_Admin_Bar $bar Admin bar.
 */
function ogtrips_core_remove_comment_admin_bar( $bar ) {
	$bar->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'ogtrips_core_remove_comment_admin_bar', 999 );

/**
 * Removes the Recent Comments widget.
 */
function ogtrips_core_remove_comment_widget() {
	unregister_widget( 'WP_Widget_Recent_Comments' );
}
add_action( 'widgets_init', 'ogtrips_core_remove_comment_widget', 20 );

/**
 * Removes the comment blocks from the editor.
 *
 * @param array<string,mixed> $args Block type args.
 * @param string              $name Block name.
 * @return array<string,mixed>
 */
function ogtrips_core_hide_comment_blocks( $args, $name ) {
	if ( str_starts_with( $name, 'core/comment' ) || 'core/latest-comments' === $name || 'core/post-comments-form' === $name ) {
		$args['supports']             = $args['supports'] ?? [];
		$args['supports']['inserter'] = false;
	}

	return $args;
}
add_filter( 'register_block_type_args', 'ogtrips_core_hide_comment_blocks', 10, 2 );
