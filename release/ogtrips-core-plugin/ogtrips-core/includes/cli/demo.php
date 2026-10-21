<?php
/**
 * Demo content: Settings → OgTrips demo (administrators only) and `wp ogtrips demo import|remove`.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the admin page.
 */
function ogtrips_core_demo_menu() {
	add_options_page(
		__( 'OgTrips demo content', 'ogtrips-core' ),
		__( 'OgTrips demo', 'ogtrips-core' ),
		'manage_options',
		'ogtrips-demo',
		'ogtrips_core_demo_page'
	);
}
add_action( 'admin_menu', 'ogtrips_core_demo_menu' );

/**
 * Renders the admin page.
 */
function ogtrips_core_demo_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$imported = (int) get_option( 'ogtrips_demo_imported' );
	$done     = isset( $_GET['done'] ) ? sanitize_key( wp_unslash( $_GET['done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'OgTrips demo content', 'ogtrips-core' ); ?></h1>
		<?php if ( 'import' === $done ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Demo content imported. Visit the site to see it.', 'ogtrips-core' ); ?></p></div>
		<?php elseif ( 'remove' === $done ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Demo content removed.', 'ogtrips-core' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Fills the site with sample content built from your itineraries: trips (Ladakh, Shimla & Manali, Kashmir, Spiti), tour guides, reviews, Instagram moments, homepage text and site settings, with photos from Unsplash. Reviews, social handles, phone numbers and ratings are fake placeholders.', 'ogtrips-core' ); ?></p>
		<p><?php esc_html_e( 'Remove: deletes only demo items nobody has edited since the import. A demo trip or guide you edited is kept as real content, photos still in use are kept, and Homepage / Site Settings fields are cleared only if they still hold the demo text. Best used before adding real content.', 'ogtrips-core' ); ?></p>
		<p><?php esc_html_e( 'Importing downloads about 20 photos and can take a few minutes. Running it again updates the demo items instead of duplicating them. Homepage and Site Settings fields are only filled when empty — your own text is never overwritten.', 'ogtrips-core' ); ?></p>
		<?php if ( $imported ) : ?>
			<p><strong>
				<?php
				/* translators: %s: date */
				echo esc_html( sprintf( __( 'Demo content was imported on %s.', 'ogtrips-core' ), wp_date( 'j M Y H:i', $imported ) ) );
				?>
			</strong></p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px">
			<input type="hidden" name="action" value="ogtrips_demo">
			<input type="hidden" name="task" value="import">
			<?php wp_nonce_field( 'ogtrips_demo' ); ?>
			<?php submit_button( __( 'Import demo content', 'ogtrips-core' ), 'primary', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block" onsubmit="return confirm('<?php echo esc_js( __( 'Delete the unedited demo trips, guides, reviews, moments and their photos?', 'ogtrips-core' ) ); ?>');">
			<input type="hidden" name="action" value="ogtrips_demo">
			<input type="hidden" name="task" value="remove">
			<?php wp_nonce_field( 'ogtrips_demo' ); ?>
			<?php submit_button( __( 'Remove demo content', 'ogtrips-core' ), 'delete', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Runs the import/remove from the admin page.
 */
function ogtrips_core_demo_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'ogtrips-core' ), 403 );
	}
	check_admin_referer( 'ogtrips_demo' );

	require_once OGTRIPS_CORE_DIR . 'demo/importer.php';
	$task = isset( $_POST['task'] ) && 'remove' === $_POST['task'] ? 'remove' : 'import';

	if ( 'remove' === $task ) {
		ogtrips_core_remove_demo();
	} else {
		ogtrips_core_import_demo();
	}

	wp_safe_redirect( admin_url( 'options-general.php?page=ogtrips-demo&done=' . $task ) );
	exit;
}
add_action( 'admin_post_ogtrips_demo', 'ogtrips_core_demo_action' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * `wp ogtrips demo import` / `wp ogtrips demo remove`.
	 *
	 * @param array<int,string> $args Positional args.
	 */
	function ogtrips_core_demo_cli( $args ) {
		require_once OGTRIPS_CORE_DIR . 'demo/importer.php';

		if ( isset( $args[0] ) && 'remove' === $args[0] ) {
			WP_CLI::success( sprintf( 'Removed %d demo items.', ogtrips_core_remove_demo() ) );
			return;
		}

		$counts = ogtrips_core_import_demo(
			static function ( $msg ) {
				WP_CLI::log( $msg );
			}
		);
		WP_CLI::success( 'Imported: ' . wp_json_encode( $counts ) );
	}
	WP_CLI::add_command( 'ogtrips demo', 'ogtrips_core_demo_cli' );
}
