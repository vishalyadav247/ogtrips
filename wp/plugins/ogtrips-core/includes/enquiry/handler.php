<?php
/**
 * Enquiry handler for the contact, trip booking and newsletter forms.
 *
 * Every enquiry is saved as a private ogt_enquiry post. channel=email also emails the team;
 * channel=whatsapp redirects the visitor to wa.me with the details pre-filled (and is logged too).
 * Protection: nonce, honeypot field, per-IP rate limit.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Max enquiries per IP in ten minutes.
 */
const OGTRIPS_CORE_ENQUIRY_LIMIT = 5;

/**
 * Where to send the visitor back to, with ?enquiry=sent|error and the form anchor.
 *
 * @param string $state  sent|error.
 * @param string $anchor Form anchor.
 * @return string
 */
function ogtrips_core_enquiry_return_url( $state, $anchor ) {
	$back = wp_get_referer();
	$back = $back ? $back : home_url( '/' );
	$back = remove_query_arg( 'enquiry', strtok( $back, '#' ) );

	return add_query_arg( 'enquiry', $state, $back ) . '#' . $anchor;
}

/**
 * Handles a form post to admin-post.php?action=ogtrips_enquiry.
 */
function ogtrips_core_handle_enquiry() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified below.
	$form   = isset( $_POST['form'] ) ? sanitize_key( wp_unslash( $_POST['form'] ) ) : 'contact';
	$form   = in_array( $form, [ 'contact', 'booking', 'newsletter' ], true ) ? $form : 'contact';
	$anchor = 'booking' === $form ? 'book' : ( 'newsletter' === $form ? 'main' : 'contact' );

	// Bots fill the hidden "website" field: pretend success, store nothing.
	if ( ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( ogtrips_core_enquiry_return_url( 'sent', $anchor ) );
		exit;
	}

	if ( ! isset( $_POST['ogtrips_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ogtrips_nonce'] ) ), 'ogtrips_enquiry' ) ) {
		wp_safe_redirect( ogtrips_core_enquiry_return_url( 'error', $anchor ) );
		exit;
	}

	$ip_key = 'ogtrips_enq_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= OGTRIPS_CORE_ENQUIRY_LIMIT ) {
		wp_safe_redirect( ogtrips_core_enquiry_return_url( 'error', $anchor ) );
		exit;
	}

	$text = static function ( $key, $max = 200 ) {
		return isset( $_POST[ $key ] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ), 0, $max ) : '';
	};

	$data = [
		'name'        => $text( 'name', 100 ),
		'phone'       => $text( 'phone', 30 ),
		'email'       => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'destination' => $text( 'destination', 100 ),
		'travel_date' => $text( 'travel_date', 20 ),
		'trip_type'   => $text( 'trip_type', 60 ),
		'travellers'  => (string) min( 50, absint( $_POST['travellers'] ?? 0 ) ),
		'departure'   => $text( 'departure', 100 ),
		'message'     => isset( $_POST['message'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['message'] ) ), 0, 2000 ) : '',
		'channel'     => ( isset( $_POST['channel'] ) && 'whatsapp' === $_POST['channel'] ) ? 'whatsapp' : ( 'newsletter' === $form ? 'newsletter' : 'email' ),
		'source_page' => isset( $_POST['source_page'] ) ? esc_url_raw( wp_unslash( $_POST['source_page'] ) ) : '',
	];
	$trip = absint( $_POST['itinerary_id'] ?? 0 );
	// phpcs:enable

	if ( '0' === $data['travellers'] ) {
		$data['travellers'] = '';
	}
	if ( $trip && 'ogt_itinerary' !== get_post_type( $trip ) ) {
		$trip = 0;
	}

	// Need a way to reply: a phone with 7+ digits, or (newsletter) an email.
	$digits = preg_replace( '/\D+/', '', $data['phone'] );
	$valid  = 'newsletter' === $form ? is_email( $data['email'] ) : strlen( $digits ) >= 7;
	if ( ! $valid ) {
		wp_safe_redirect( ogtrips_core_enquiry_return_url( 'error', $anchor ) );
		exit;
	}

	set_transient( $ip_key, $count + 1, 10 * MINUTE_IN_SECONDS );

	$who   = '' !== $data['name'] ? $data['name'] : ( '' !== $data['phone'] ? $data['phone'] : $data['email'] );
	$about = $trip ? str_replace( '*', '', get_post_field( 'post_title', $trip ) ) : ( 'newsletter' === $form ? __( 'Newsletter sign-up', 'ogtrips-core' ) : $data['destination'] );
	$title = trim( $who . ( '' !== $about ? ' — ' . $about : '' ) );

	$meta = [ '_ogt_status' => 'new' ];
	foreach ( $data as $key => $value ) {
		$meta[ '_ogt_' . $key ] = $value;
	}
	if ( $trip ) {
		$meta['_ogt_itinerary_id'] = $trip;
	}

	$post_id = wp_insert_post(
		[
			'post_type'   => 'ogt_enquiry',
			'post_status' => 'private',
			'post_title'  => $title,
			'meta_input'  => $meta,
		],
		true
	);

	if ( is_wp_error( $post_id ) ) {
		wp_safe_redirect( ogtrips_core_enquiry_return_url( 'error', $anchor ) );
		exit;
	}

	// Summary used by both the email and the WhatsApp message.
	$labels = ogtrips_core_enquiry_fields();
	$lines  = [];
	if ( $trip ) {
		/* translators: %s: trip name */
		$lines[] = sprintf( __( 'Trip: %s', 'ogtrips-core' ), str_replace( '*', '', get_post_field( 'post_title', $trip ) ) );
	}
	foreach ( [ 'name', 'phone', 'email', 'destination', 'travel_date', 'trip_type', 'travellers', 'departure', 'message' ] as $key ) {
		if ( '' !== $data[ $key ] && __( 'Not sure yet', 'ogtrips' ) !== $data[ $key ] ) {
			$lines[] = $labels[ $key ] . ': ' . $data[ $key ];
		}
	}

	if ( 'whatsapp' === $data['channel'] ) {
		$number = preg_replace( '/\D+/', '', (string) ( function_exists( 'get_field' ) ? get_field( 'whatsapp', 'option' ) : '' ) );
		if ( '' !== $number ) {
			$message = __( 'Hi OgTrips! I would like to plan a trip.', 'ogtrips-core' ) . "\n\n" . implode( "\n", $lines );
			// Direct header: wp_redirect() strips the encoded line breaks (%0A) from the message.
			header( 'Location: https://wa.me/' . $number . '?text=' . rawurlencode( $message ), true, 302 );
			exit;
		}
	}

	ogtrips_core_email_enquiry( $post_id, $title, $lines, $data );

	wp_safe_redirect( ogtrips_core_enquiry_return_url( 'sent', $anchor ) );
	exit;
}
add_action( 'admin_post_nopriv_ogtrips_enquiry', 'ogtrips_core_handle_enquiry' );
add_action( 'admin_post_ogtrips_enquiry', 'ogtrips_core_handle_enquiry' );

/**
 * Emails a new enquiry to the team (Site Settings → enquiry email, else the admin email).
 *
 * @param int                  $post_id Enquiry ID.
 * @param string               $title   Enquiry title.
 * @param array<int,string>    $lines   Summary lines.
 * @param array<string,string> $data    Submitted data.
 */
function ogtrips_core_email_enquiry( $post_id, $title, $lines, $data ) {
	$to = function_exists( 'get_field' ) ? (string) get_field( 'enquiry_email', 'option' ) : '';
	$to = is_email( $to ) ? $to : (string) get_option( 'admin_email' );

	/* translators: %s: enquiry title */
	$subject = sprintf( __( 'New enquiry: %s', 'ogtrips-core' ), $title );
	$body    = implode( "\n", $lines ) . "\n\n";
	if ( '' !== $data['source_page'] ) {
		/* translators: %s: page URL */
		$body .= sprintf( __( 'Sent from: %s', 'ogtrips-core' ), $data['source_page'] ) . "\n";
	}
	/* translators: %s: admin URL */
	$body .= sprintf( __( 'Open in wp-admin: %s', 'ogtrips-core' ), admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' ) ) . "\n";

	$headers = [];
	if ( is_email( $data['email'] ) ) {
		$headers[] = 'Reply-To: ' . $data['email'];
	}

	wp_mail( $to, $subject, $body, $headers );
}
