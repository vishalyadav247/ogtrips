<?php
/**
 * Post type ogt_enquiry — "Enquiries": leads from the contact and trip forms.
 *
 * Private, admin-only (Editors and Administrators). Nobody can add one by hand: the
 * form handler (phase 06) creates them. Data lives in protected post meta
 * (_ogt_name, _ogt_phone, …); the team can only change the status.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lead details stored by the form handler, in display order. Meta key = '_ogt_' . key.
 *
 * @return array<string,string> key => label
 */
function ogtrips_core_enquiry_fields() {
	return [
		'name'        => __( 'Name', 'ogtrips-core' ),
		'phone'       => __( 'Phone', 'ogtrips-core' ),
		'email'       => __( 'Email', 'ogtrips-core' ),
		'destination' => __( 'Destination', 'ogtrips-core' ),
		'travel_date' => __( 'Travel date', 'ogtrips-core' ),
		'trip_type'   => __( 'Trip type', 'ogtrips-core' ),
		'travellers'  => __( 'Travellers', 'ogtrips-core' ),
		'departure'   => __( 'Departure', 'ogtrips-core' ),
		'message'     => __( 'Message', 'ogtrips-core' ),
		'channel'     => __( 'Sent by', 'ogtrips-core' ),
		'source_page' => __( 'Sent from page', 'ogtrips-core' ),
	];
}

/**
 * Enquiry statuses.
 *
 * @return array<string,string> value => label
 */
function ogtrips_core_enquiry_statuses() {
	return [
		'new'       => __( 'New', 'ogtrips-core' ),
		'contacted' => __( 'Contacted', 'ogtrips-core' ),
		'won'       => __( 'Booked', 'ogtrips-core' ),
		'lost'      => __( 'Closed', 'ogtrips-core' ),
	];
}

/**
 * Registers the Enquiries post type. Capabilities map to "edit others' posts" so only
 * Editors and Administrators can see leads; creating by hand is not allowed.
 */
function ogtrips_core_register_enquiry() {
	register_post_type(
		'ogt_enquiry',
		[
			'labels'          => [
				'name'               => __( 'Enquiries', 'ogtrips-core' ),
				'singular_name'      => __( 'Enquiry', 'ogtrips-core' ),
				'menu_name'          => __( 'Enquiries', 'ogtrips-core' ),
				'all_items'          => __( 'All enquiries', 'ogtrips-core' ),
				'edit_item'          => __( 'Enquiry', 'ogtrips-core' ),
				'search_items'       => __( 'Search enquiries', 'ogtrips-core' ),
				'not_found'          => __( 'No enquiries yet. New ones from the website appear here.', 'ogtrips-core' ),
				'not_found_in_trash' => __( 'No enquiries in the bin.', 'ogtrips-core' ),
				'item_updated'       => __( 'Enquiry updated.', 'ogtrips-core' ),
			],
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'supports'        => false,
			'map_meta_cap'    => true,
			'capability_type' => 'post',
			'capabilities'    => [
				'create_posts'           => 'do_not_allow',
				'edit_posts'             => 'edit_others_posts',
				'edit_others_posts'      => 'edit_others_posts',
				'edit_private_posts'     => 'edit_others_posts',
				'edit_published_posts'   => 'edit_others_posts',
				'publish_posts'          => 'edit_others_posts',
				'read_private_posts'     => 'edit_others_posts',
				'delete_posts'           => 'delete_others_posts',
				'delete_others_posts'    => 'delete_others_posts',
				'delete_private_posts'   => 'delete_others_posts',
				'delete_published_posts' => 'delete_others_posts',
			],
		]
	);
}
add_action( 'init', 'ogtrips_core_register_enquiry' );

/**
 * Every enquiry starts as "new" (also those created by WP-CLI or the handler without a status).
 *
 * @param int $post_id Post ID.
 */
function ogtrips_core_enquiry_default_status( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( '' === (string) get_post_meta( $post_id, '_ogt_status', true ) ) {
		update_post_meta( $post_id, '_ogt_status', 'new' );
	}

	delete_transient( 'ogtrips_core_new_enquiries' );
}
add_action( 'save_post_ogt_enquiry', 'ogtrips_core_enquiry_default_status' );

/**
 * Number of enquiries with status "new" (cached until a status changes).
 *
 * @return int
 */
function ogtrips_core_new_enquiry_count() {
	$count = get_transient( 'ogtrips_core_new_enquiries' );

	if ( false === $count ) {
		$query = new WP_Query(
			[
				'post_type'      => 'ogt_enquiry',
				'post_status'    => [ 'private', 'publish', 'pending', 'draft' ],
				'meta_key'       => '_ogt_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 'new', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'         => 'ids',
				'posts_per_page' => 1,
			]
		);
		$count = (int) $query->found_posts;
		set_transient( 'ogtrips_core_new_enquiries', $count, HOUR_IN_SECONDS );
	}

	return (int) $count;
}

/**
 * Clears the cached count whenever an enquiry is deleted.
 *
 * @param int $post_id Post ID.
 */
function ogtrips_core_enquiry_flush_count( $post_id ) {
	if ( 'ogt_enquiry' === get_post_type( $post_id ) ) {
		delete_transient( 'ogtrips_core_new_enquiries' );
	}
}
add_action( 'deleted_post', 'ogtrips_core_enquiry_flush_count' );
add_action( 'trashed_post', 'ogtrips_core_enquiry_flush_count' );
add_action( 'untrashed_post', 'ogtrips_core_enquiry_flush_count' );

/**
 * Clears the cached count when a status is written directly (handler meta_input, WP-CLI).
 *
 * @param int    $meta_id  Meta ID.
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 */
function ogtrips_core_enquiry_status_meta_changed( $meta_id, $post_id, $meta_key ) {
	if ( '_ogt_status' === $meta_key ) {
		delete_transient( 'ogtrips_core_new_enquiries' );
	}
}
add_action( 'added_post_meta', 'ogtrips_core_enquiry_status_meta_changed', 10, 3 );
add_action( 'updated_post_meta', 'ogtrips_core_enquiry_status_meta_changed', 10, 3 );

/**
 * Detail box (read-only lead data) and status box on the enquiry screen.
 */
function ogtrips_core_enquiry_meta_boxes() {
	add_meta_box( 'ogtrips-enquiry-details', __( 'Enquiry details', 'ogtrips-core' ), 'ogtrips_core_enquiry_details_box', 'ogt_enquiry', 'normal', 'high' );
	add_meta_box( 'ogtrips-enquiry-status', __( 'Status', 'ogtrips-core' ), 'ogtrips_core_enquiry_status_box', 'ogt_enquiry', 'side', 'high' );
	remove_meta_box( 'submitdiv', 'ogt_enquiry', 'side' );
	remove_meta_box( 'slugdiv', 'ogt_enquiry', 'normal' );
}
add_action( 'add_meta_boxes_ogt_enquiry', 'ogtrips_core_enquiry_meta_boxes' );

/**
 * Renders the read-only details table.
 *
 * @param WP_Post $post Enquiry.
 */
function ogtrips_core_enquiry_details_box( $post ) {
	echo '<table class="widefat striped ogtrips-enquiry"><tbody>';

	foreach ( ogtrips_core_enquiry_fields() as $key => $label ) {
		$value = (string) get_post_meta( $post->ID, '_ogt_' . $key, true );

		if ( '' === $value ) {
			continue;
		}

		if ( 'email' === $key ) {
			$value_html = '<a href="' . esc_url( 'mailto:' . $value ) . '">' . esc_html( $value ) . '</a>';
		} elseif ( 'phone' === $key ) {
			$value_html = '<a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $value ) ) . '">' . esc_html( $value ) . '</a>';
		} elseif ( 'source_page' === $key ) {
			$value_html = '<a href="' . esc_url( $value ) . '">' . esc_html( $value ) . '</a>';
		} else {
			$value_html = nl2br( esc_html( $value ) );
		}

		printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $label ), $value_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	}

	$itinerary_id = (int) get_post_meta( $post->ID, '_ogt_itinerary_id', true );

	if ( $itinerary_id && get_post( $itinerary_id ) ) {
		printf(
			'<tr><th scope="row">%s</th><td><a href="%s">%s</a></td></tr>',
			esc_html__( 'Trip', 'ogtrips-core' ),
			esc_url( get_edit_post_link( $itinerary_id ) ?? '' ),
			esc_html( get_the_title( $itinerary_id ) )
		);
	}

	printf(
		'<tr><th scope="row">%s</th><td>%s</td></tr>',
		esc_html__( 'Received', 'ogtrips-core' ),
		esc_html( get_the_date( get_option( 'date_format' ) . ' · H:i', $post ) )
	);

	echo '</tbody></table>';
}

/**
 * Renders the status select + save button.
 *
 * @param WP_Post $post Enquiry.
 */
function ogtrips_core_enquiry_status_box( $post ) {
	$current = (string) get_post_meta( $post->ID, '_ogt_status', true );

	wp_nonce_field( 'ogtrips_enquiry_status', 'ogtrips_enquiry_status_nonce' );
	echo '<p><label class="screen-reader-text" for="ogtrips-enquiry-status-select">' . esc_html__( 'Status', 'ogtrips-core' ) . '</label>';
	echo '<select name="ogtrips_enquiry_status" id="ogtrips-enquiry-status-select" class="widefat">';

	foreach ( ogtrips_core_enquiry_statuses() as $value => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
	}

	echo '</select></p>';
	// Keep the enquiry private when saving.
	echo '<input type="hidden" name="post_status" value="private">';
	submit_button( __( 'Save status', 'ogtrips-core' ), 'primary', 'save', false );

	if ( current_user_can( 'delete_post', $post->ID ) ) {
		printf(
			' <a class="submitdelete" href="%s">%s</a>',
			esc_url( get_delete_post_link( $post->ID ) ?? '' ),
			esc_html__( 'Move to bin', 'ogtrips-core' )
		);
	}
}

/**
 * Saves the status from the status box.
 *
 * @param int $post_id Post ID.
 */
function ogtrips_core_enquiry_save_status( $post_id ) {
	if ( ! isset( $_POST['ogtrips_enquiry_status_nonce'], $_POST['ogtrips_enquiry_status'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ogtrips_enquiry_status_nonce'] ) ), 'ogtrips_enquiry_status' )
		|| ! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}

	$status = sanitize_key( wp_unslash( $_POST['ogtrips_enquiry_status'] ) );

	if ( array_key_exists( $status, ogtrips_core_enquiry_statuses() ) ) {
		update_post_meta( $post_id, '_ogt_status', $status );
		delete_transient( 'ogtrips_core_new_enquiries' );
	}
}
add_action( 'save_post_ogt_enquiry', 'ogtrips_core_enquiry_save_status', 5 );

/**
 * List-screen columns.
 *
 * @param array<string,string> $columns Default columns.
 * @return array<string,string>
 */
function ogtrips_core_enquiry_columns( $columns ) {
	return [
		'cb'          => $columns['cb'] ?? '',
		'title'       => __( 'Enquiry', 'ogtrips-core' ),
		'ogt_phone'   => __( 'Phone', 'ogtrips-core' ),
		'ogt_trip'    => __( 'Trip / destination', 'ogtrips-core' ),
		'ogt_channel' => __( 'Sent by', 'ogtrips-core' ),
		'ogt_status'  => __( 'Status', 'ogtrips-core' ),
		'date'        => __( 'Received', 'ogtrips-core' ),
	];
}
add_filter( 'manage_ogt_enquiry_posts_columns', 'ogtrips_core_enquiry_columns' );

/**
 * List-screen column values.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function ogtrips_core_enquiry_column_values( $column, $post_id ) {
	switch ( $column ) {
		case 'ogt_phone':
			echo esc_html( (string) get_post_meta( $post_id, '_ogt_phone', true ) );
			break;

		case 'ogt_trip':
			$itinerary_id = (int) get_post_meta( $post_id, '_ogt_itinerary_id', true );
			echo esc_html( $itinerary_id ? get_the_title( $itinerary_id ) : (string) get_post_meta( $post_id, '_ogt_destination', true ) );
			break;

		case 'ogt_channel':
			$channel = (string) get_post_meta( $post_id, '_ogt_channel', true );
			echo esc_html( 'whatsapp' === $channel ? __( 'WhatsApp', 'ogtrips-core' ) : ( 'email' === $channel ? __( 'Email form', 'ogtrips-core' ) : $channel ) );
			break;

		case 'ogt_status':
			$status   = (string) get_post_meta( $post_id, '_ogt_status', true );
			$statuses = ogtrips_core_enquiry_statuses();
			printf(
				'<span class="ogtrips-status ogtrips-status--%s">%s</span>',
				esc_attr( $status ),
				esc_html( $statuses[ $status ] ?? $status )
			);
			break;
	}
}
add_action( 'manage_ogt_enquiry_posts_custom_column', 'ogtrips_core_enquiry_column_values', 10, 2 );

/**
 * Status filter above the list.
 *
 * @param string $post_type Current list post type.
 */
function ogtrips_core_enquiry_status_filter( $post_type ) {
	if ( 'ogt_enquiry' !== $post_type ) {
		return;
	}

	$current = isset( $_GET['ogt_status'] ) ? sanitize_key( wp_unslash( $_GET['ogt_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.

	echo '<label class="screen-reader-text" for="ogtrips-status-filter">' . esc_html__( 'Filter by status', 'ogtrips-core' ) . '</label>';
	echo '<select name="ogt_status" id="ogtrips-status-filter"><option value="">' . esc_html__( 'All statuses', 'ogtrips-core' ) . '</option>';

	foreach ( ogtrips_core_enquiry_statuses() as $value => $label ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
	}

	echo '</select>';
}
add_action( 'restrict_manage_posts', 'ogtrips_core_enquiry_status_filter' );

/**
 * Applies the status filter to the list query.
 *
 * @param WP_Query $query Admin list query.
 */
function ogtrips_core_enquiry_filter_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'ogt_enquiry' !== $query->get( 'post_type' ) ) {
		return;
	}

	$status = isset( $_GET['ogt_status'] ) ? sanitize_key( wp_unslash( $_GET['ogt_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.

	if ( array_key_exists( $status, ogtrips_core_enquiry_statuses() ) ) {
		$query->set( 'meta_key', '_ogt_status' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query->set( 'meta_value', $status ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	}
}
add_action( 'pre_get_posts', 'ogtrips_core_enquiry_filter_query' );

/**
 * No "View" / quick-edit links for enquiries — only open and bin.
 *
 * @param array<string,string> $actions Row actions.
 * @param WP_Post              $post    Row post.
 * @return array<string,string>
 */
function ogtrips_core_enquiry_row_actions( $actions, $post ) {
	if ( 'ogt_enquiry' === $post->post_type ) {
		unset( $actions['inline hide-if-no-js'], $actions['view'] );
	}

	return $actions;
}
add_filter( 'post_row_actions', 'ogtrips_core_enquiry_row_actions', 10, 2 );

/**
 * No bulk "Edit" for enquiries.
 *
 * @param array<string,string> $actions Bulk actions.
 * @return array<string,string>
 */
function ogtrips_core_enquiry_bulk_actions( $actions ) {
	unset( $actions['edit'] );

	return $actions;
}
add_filter( 'bulk_actions-edit-ogt_enquiry', 'ogtrips_core_enquiry_bulk_actions' );
