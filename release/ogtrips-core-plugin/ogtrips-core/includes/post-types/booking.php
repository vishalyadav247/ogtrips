<?php
/**
 * Post type ogt_booking — "Bookings": a confirmed trip for a client, entered by the team
 * (no payments, no automation). The client sees it in their portal (/my-trips/).
 *
 * Fields: acf-json/group_ogt_booking.json. Title is set automatically ("Client — Trip").
 * Only the team (edit_others_posts: Editors, Administrators) can see or edit bookings;
 * a client only ever reads their own through ogtrips_core_client_bookings().
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Bookings post type.
 */
function ogtrips_core_register_booking() {
	register_post_type(
		'ogt_booking',
		[
			'labels'          => [
				'name'               => __( 'Bookings', 'ogtrips-core' ),
				'singular_name'      => __( 'Booking', 'ogtrips-core' ),
				'menu_name'          => __( 'Bookings', 'ogtrips-core' ),
				'all_items'          => __( 'All bookings', 'ogtrips-core' ),
				'add_new'            => __( 'Add booking', 'ogtrips-core' ),
				'add_new_item'       => __( 'Add a booking', 'ogtrips-core' ),
				'edit_item'          => __( 'Edit booking', 'ogtrips-core' ),
				'new_item'           => __( 'New booking', 'ogtrips-core' ),
				'search_items'       => __( 'Search bookings', 'ogtrips-core' ),
				'not_found'          => __( 'No bookings yet. When a client confirms a trip, add it here and they see it in their portal.', 'ogtrips-core' ),
				'not_found_in_trash' => __( 'No bookings in the bin.', 'ogtrips-core' ),
				'item_published'     => __( 'Booking saved — it is now visible in the client\'s portal.', 'ogtrips-core' ),
				'item_updated'       => __( 'Booking updated.', 'ogtrips-core' ),
			],
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-tickets-alt',
			'supports'        => false,
			'map_meta_cap'    => true,
			'capability_type' => 'post',
			'capabilities'    => [
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
add_action( 'init', 'ogtrips_core_register_booking' );

/**
 * Booking statuses (value => label), as shown to the client.
 *
 * @return array<string,string>
 */
function ogtrips_core_booking_statuses() {
	return [
		'confirmed' => __( 'Confirmed', 'ogtrips-core' ),
		'paid'      => __( 'Confirmed & paid', 'ogtrips-core' ),
		'ongoing'   => __( 'On the road', 'ogtrips-core' ),
		'completed' => __( 'Completed', 'ogtrips-core' ),
		'cancelled' => __( 'Cancelled', 'ogtrips-core' ),
	];
}

/**
 * Service tracker statuses (value => label).
 *
 * @return array<string,string>
 */
function ogtrips_core_service_statuses() {
	return [
		'confirmed' => __( 'Confirmed', 'ogtrips-core' ),
		'pending'   => __( 'Being arranged', 'ogtrips-core' ),
		'na'        => __( 'Not included', 'ogtrips-core' ),
	];
}

/**
 * Member tiers (value => label).
 *
 * @return array<string,string>
 */
function ogtrips_core_member_tiers() {
	return [
		'explorer' => __( 'Explorer', 'ogtrips-core' ),
		'voyager'  => __( 'Voyager', 'ogtrips-core' ),
		'legend'   => __( 'Legend', 'ogtrips-core' ),
	];
}

/**
 * Booking reference shown to the client, e.g. #TRIP-00012.
 *
 * @param int $booking_id Booking ID.
 * @return string
 */
function ogtrips_core_booking_ref( $booking_id ) {
	return sprintf( '#TRIP-%05d', (int) $booking_id );
}

/**
 * Plain title of what was booked: the custom package name or the trip title.
 *
 * @param int $booking_id Booking ID.
 * @return string
 */
function ogtrips_core_booking_title( $booking_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	if ( 'custom' === get_field( 'trip_mode', $booking_id ) ) {
		return trim( (string) get_field( 'custom_title', $booking_id ) );
	}

	$trip = (int) get_field( 'itinerary', $booking_id );

	return $trip ? trim( str_replace( '*', '', (string) get_post_field( 'post_title', $trip ) ) ) : '';
}

/**
 * Titles the booking "{client} — {trip}" after SCF has saved the fields.
 *
 * @param int|string $post_id Post ID (or 'option' etc.).
 */
function ogtrips_core_booking_auto_title( $post_id ) {
	if ( ! is_numeric( $post_id ) || 'ogt_booking' !== get_post_type( $post_id ) || ! function_exists( 'get_field' ) ) {
		return;
	}

	$client = get_userdata( (int) get_field( 'client', $post_id ) );
	$parts  = array_filter( [ $client ? $client->display_name : '', ogtrips_core_booking_title( $post_id ) ] );
	$title  = $parts ? implode( ' — ', $parts ) : ogtrips_core_booking_ref( $post_id );

	if ( get_the_title( $post_id ) !== $title ) {
		wp_update_post(
			[
				'ID'         => (int) $post_id,
				'post_title' => $title,
				'post_name'  => sanitize_title( ogtrips_core_booking_ref( $post_id ) ),
			]
		);
	}
}
add_action( 'acf/save_post', 'ogtrips_core_booking_auto_title', 20 );

/**
 * Published bookings of a client, newest travel date first.
 *
 * @param int $user_id Client user ID.
 * @return int[] Booking IDs.
 */
function ogtrips_core_client_bookings( $user_id ) {
	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return [];
	}

	$ids = get_posts(
		[
			'post_type'      => 'ogt_booking',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => 'start_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => [ 'meta_value' => 'DESC', 'date' => 'DESC' ],
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'   => 'client',
					'value' => $user_id,
				],
			],
		]
	);

	return array_map( 'intval', $ids );
}

/**
 * Whether a user may read a booking: the team, or the client it belongs to.
 *
 * @param int $booking_id Booking ID.
 * @param int $user_id    User ID.
 * @return bool
 */
function ogtrips_core_user_can_view_booking( $booking_id, $user_id ) {
	$booking_id = (int) $booking_id;
	$user_id    = (int) $user_id;

	if ( ! $booking_id || ! $user_id || 'ogt_booking' !== get_post_type( $booking_id ) || 'publish' !== get_post_status( $booking_id ) ) {
		return false;
	}

	if ( user_can( $user_id, 'edit_others_posts' ) ) {
		return true;
	}

	return function_exists( 'get_field' ) && (int) get_field( 'client', $booking_id ) === $user_id;
}

/**
 * Everything the portal needs for one booking, with the trip's own plan filled in for
 * "one of our trips" bookings. Raw values — escape on output.
 *
 * @param int $booking_id Booking ID.
 * @return array<string,mixed>
 */
function ogtrips_core_booking_data( $booking_id ) {
	$booking_id = (int) $booking_id;
	$f          = static function ( $name, $default = '' ) use ( $booking_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}
		$value = get_field( $name, $booking_id );

		return ( null === $value || '' === $value || false === $value || [] === $value ) ? $default : $value;
	};

	$custom   = 'custom' === $f( 'trip_mode', 'existing' );
	$trip     = $custom ? 0 : (int) $f( 'itinerary', 0 );
	$trip     = $trip && 'ogt_itinerary' === get_post_type( $trip ) ? $trip : 0;
	$statuses = ogtrips_core_booking_statuses();
	$status   = (string) $f( 'status', 'confirmed' );
	$rows     = static function ( $value ) {
		return array_values( array_filter( (array) $value, 'is_array' ) );
	};

	// Plan, stays and inclusions: from the custom package, or from the trip.
	$days = $rows( $f( $custom ? 'custom_days' : '', [] ) );
	if ( ! $custom && $trip ) {
		$days = $rows( function_exists( 'get_field' ) ? get_field( 'days', $trip ) : [] );
	}
	$stays    = $custom ? $rows( $f( 'custom_stays', [] ) ) : ( $trip && function_exists( 'get_field' ) ? $rows( get_field( 'stays', $trip ) ) : [] );
	$included = $custom ? $rows( $f( 'custom_included', [] ) ) : ( $trip && function_exists( 'get_field' ) ? $rows( get_field( 'included', $trip ) ) : [] );
	$excluded = $custom ? $rows( $f( 'custom_excluded', [] ) ) : ( $trip && function_exists( 'get_field' ) ? $rows( get_field( 'excluded', $trip ) ) : [] );

	$cover = $custom ? (int) $f( 'custom_cover', 0 ) : ( $trip ? (int) get_post_thumbnail_id( $trip ) : 0 );
	if ( ! $cover && function_exists( 'ogtrips_core_home_field' ) ) {
		$places = ogtrips_core_home_field( 'hero_places', [] );
		$cover  = is_array( $places ) && ! empty( $places[0]['image'] ) ? (int) $places[0]['image'] : 0;
	}

	$total    = (float) $f( 'price_total', 0 );
	$pct      = (float) $f( 'discount_pct', 0 );
	$discount = $total > 0 && $pct > 0 ? round( $total * $pct / 100 ) : 0;
	$payable  = max( 0, $total - $discount );
	$paid     = (float) $f( 'amount_paid', 0 );

	$schedule = $rows( $f( 'schedule', [] ) );
	usort(
		$schedule,
		static function ( $a, $b ) {
			return (int) ( $a['day'] ?? 0 ) <=> (int) ( $b['day'] ?? 0 );
		}
	);

	$summary = $custom ? (string) $f( 'custom_summary' ) : ( $trip ? (string) get_the_excerpt( $trip ) : '' );
	if ( ! $custom && $trip && '' === trim( $summary ) ) {
		$summary = wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $trip ) ), 40 );
	}

	return [
		'id'               => $booking_id,
		'ref'              => ogtrips_core_booking_ref( $booking_id ),
		'url'              => ogtrips_core_portal_url( $booking_id ),
		'client'           => (int) $f( 'client', 0 ),
		'custom'           => $custom,
		'trip'             => $trip,
		'trip_url'         => $trip && 'publish' === get_post_status( $trip ) ? get_permalink( $trip ) : '',
		'title'            => ogtrips_core_booking_title( $booking_id ),
		'summary'          => $summary,
		'cover'            => $cover,
		'days'             => $days,
		'stays'            => $stays,
		'included'         => $included,
		'excluded'         => $excluded,
		'start'            => (string) $f( 'start_date' ),
		'end'              => (string) $f( 'end_date' ),
		'travellers'       => (int) $f( 'travellers', 0 ),
		'pickup'           => (string) $f( 'pickup' ),
		'schedule'         => $schedule,
		'services'         => $rows( $f( 'services', [] ) ),
		'special_requests' => (string) $f( 'special_requests' ),
		'price_total'      => $total,
		'discount_pct'     => $pct,
		'discount'         => $discount,
		'payable'          => $payable,
		'paid'             => $paid,
		'balance'          => max( 0, $payable - $paid ),
		'payment_note'     => (string) $f( 'payment_note' ),
		'itinerary_pdf'    => (int) $f( 'itinerary_pdf', 0 ),
		'documents'        => $rows( $f( 'documents', [] ) ),
		'status'           => isset( $statuses[ $status ] ) ? $status : 'confirmed',
		'status_label'     => $statuses[ $status ] ?? $statuses['confirmed'],
		'curator'          => (int) $f( 'curator', 0 ),
		'client_note'      => (string) $f( 'client_note' ),
	];
}

/**
 * Admin list: reference, client, trip, dates, status.
 *
 * @param array<string,string> $columns Columns.
 * @return array<string,string>
 */
function ogtrips_core_booking_columns( $columns ) {
	return [
		'cb'          => $columns['cb'] ?? '',
		'title'       => __( 'Booking', 'ogtrips-core' ),
		'ogt_ref'     => __( 'Ref', 'ogtrips-core' ),
		'ogt_dates'   => __( 'Travel dates', 'ogtrips-core' ),
		'ogt_status'  => __( 'Status', 'ogtrips-core' ),
		'ogt_curator' => __( 'Curator', 'ogtrips-core' ),
	];
}
add_filter( 'manage_ogt_booking_posts_columns', 'ogtrips_core_booking_columns' );

/**
 * Admin list cell content.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function ogtrips_core_booking_column( $column, $post_id ) {
	$get = static function ( $name ) use ( $post_id ) {
		return function_exists( 'get_field' ) ? get_field( $name, $post_id ) : '';
	};

	switch ( $column ) {
		case 'ogt_ref':
			echo esc_html( ogtrips_core_booking_ref( $post_id ) );
			break;
		case 'ogt_dates':
			$start = (string) $get( 'start_date' );
			$end   = (string) $get( 'end_date' );
			$dates = array_filter( [ '' !== $start ? wp_date( 'j M Y', strtotime( $start ) ) : '', '' !== $end ? wp_date( 'j M Y', strtotime( $end ) ) : '' ] );
			echo esc_html( $dates ? implode( ' → ', $dates ) : '—' );
			break;
		case 'ogt_status':
			$statuses = ogtrips_core_booking_statuses();
			$status   = (string) $get( 'status' );
			echo esc_html( $statuses[ $status ] ?? $statuses['confirmed'] );
			break;
		case 'ogt_curator':
			$user = get_userdata( (int) $get( 'curator' ) );
			echo esc_html( $user ? $user->display_name : '—' );
			break;
	}
}
add_action( 'manage_ogt_booking_posts_custom_column', 'ogtrips_core_booking_column', 10, 2 );

/**
 * "Add booking" goes to the client, if opened from a client's row in Clients (?client=ID).
 *
 * @param mixed $value Field value.
 * @return mixed
 */
function ogtrips_core_booking_prefill_client( $value ) {
	global $pagenow;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- pre-fills a form field only.
	if ( 'post-new.php' === $pagenow && empty( $value ) && ! empty( $_GET['client'] ) ) {
		return absint( $_GET['client'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	return $value;
}
add_filter( 'acf/load_value/name=client', 'ogtrips_core_booking_prefill_client' );
