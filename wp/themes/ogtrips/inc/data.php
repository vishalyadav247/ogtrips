<?php
/**
 * Formatting and data helpers shared by the templates.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site Settings value (SCF options page).
 *
 * @param string $key     Field name.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function ogtrips_setting( $key, $default = '' ) {
	return ogtrips_field( $key, 'option', $default );
}

/**
 * Merchant text with *stars* turned into <em> (safe HTML).
 *
 * @param string $text Plain text.
 * @return string
 */
function ogtrips_em( $text ) {
	if ( function_exists( 'ogtrips_core_emphasis' ) ) {
		return ogtrips_core_emphasis( $text );
	}

	return esc_html( str_replace( '*', '', (string) $text ) );
}

/**
 * Indian-style rupee amount: 124000 → ₹1,24,000.
 *
 * @param int|float|string $amount Amount.
 * @return string
 */
function ogtrips_price( $amount ) {
	$n     = (string) absint( $amount );
	$last3 = substr( $n, -3 );
	$rest  = substr( $n, 0, -3 );

	if ( '' !== $rest ) {
		$rest  = preg_replace( '/\B(?=(\d{2})+(?!\d))/', ',', $rest );
		$last3 = $rest . ',' . $last3;
	}

	return '₹' . $last3;
}

/**
 * Digits-only phone number for tel:/wa.me links.
 *
 * @param string $phone Phone as typed.
 * @return string
 */
function ogtrips_phone_digits( $phone ) {
	return preg_replace( '/\D+/', '', (string) $phone );
}

/**
 * WhatsApp link with an optional pre-filled message.
 *
 * @param string $phone Phone number.
 * @param string $text  Message.
 * @return string
 */
function ogtrips_whatsapp_url( $phone, $text = '' ) {
	$digits = ogtrips_phone_digits( $phone );

	if ( '' === $digits ) {
		return '';
	}

	return 'https://wa.me/' . $digits . ( '' !== $text ? '?text=' . rawurlencode( $text ) : '' );
}

/**
 * Image tag for an attachment; '' when missing.
 *
 * @param int                 $id    Attachment ID.
 * @param string              $size  Image size.
 * @param array<string,mixed> $attrs Extra attributes.
 * @return string
 */
function ogtrips_img( $id, $size = 'ogt-card', $attrs = [] ) {
	$id = (int) $id;

	if ( ! $id ) {
		return '';
	}

	$attrs = wp_parse_args( $attrs, [ 'loading' => 'lazy', 'decoding' => 'async' ] );

	return wp_get_attachment_image( $id, $size, false, $attrs );
}

/**
 * Lucide icon names allowed in merchant-chosen fields (same list as the SCF selects).
 *
 * @param string $name     Icon name.
 * @param string $fallback Fallback icon.
 * @return string
 */
function ogtrips_safe_icon( $name, $fallback = 'sparkles' ) {
	$name = sanitize_key( (string) $name );

	return '' !== $name ? $name : $fallback;
}

/**
 * Pace label for the trip facts bar.
 *
 * @param string $pace Pace key.
 * @return string
 */
function ogtrips_pace_label( $pace ) {
	$labels = [
		'easy'                 => __( 'Easy', 'ogtrips' ),
		'easy-moderate'        => __( 'Easy – moderate', 'ogtrips' ),
		'moderate'             => __( 'Moderate', 'ogtrips' ),
		'moderate-challenging' => __( 'Moderate – challenging', 'ogtrips' ),
		'challenging'          => __( 'Challenging', 'ogtrips' ),
	];

	return $labels[ $pace ] ?? '';
}

/**
 * Card data for a trip, used by every trip card.
 *
 * @param int|WP_Post $post Trip.
 * @return array<string,mixed>
 */
function ogtrips_trip_card_data( $post ) {
	$post = get_post( $post );
	$id   = $post->ID;

	$days   = (int) ogtrips_field( 'duration_days', $id, 0 );
	$nights = (int) ogtrips_field( 'duration_nights', $id, 0 );
	$min    = (int) ogtrips_field( 'group_min', $id, 0 );
	$max    = (int) ogtrips_field( 'group_max', $id, 0 );

	$terms = get_the_terms( $id, 'ogt_trip_type' );

	return [
		'id'        => $id,
		'url'       => get_permalink( $id ),
		'title'     => get_the_title( $id ),
		'short'     => (string) ogtrips_field( 'short_title', $id, get_the_title( $id ) ),
		'location'  => (string) ogtrips_field( 'location_label', $id ),
		'badge'     => (string) ogtrips_field( 'badge', $id ),
		'badge_sty' => (string) ogtrips_field( 'badge_style', $id, 'plain' ),
		'badge_ic'  => (string) ogtrips_field( 'badge_icon', $id ),
		'days'      => $days,
		'nights'    => $nights,
		'group'     => $min && $max && $min !== $max ? $min . '–' . $max : (string) ( $max ? $max : $min ),
		'rating'    => (string) ogtrips_field( 'rating', $id ),
		'reviews'   => (int) ogtrips_field( 'review_count', $id, 0 ),
		'price'     => (int) ogtrips_field( 'price_from', $id, 0 ),
		'orig'      => (int) ogtrips_field( 'price_original', $id, 0 ),
		'incl'      => (array) ogtrips_field( 'incl_icons', $id, [] ),
		'cat'       => $terms && ! is_wp_error( $terms ) ? $terms[0]->slug : '',
	];
}

/**
 * Trip query helper.
 *
 * @param array<string,mixed> $args Extra WP_Query args.
 * @return WP_Query
 */
function ogtrips_trip_query( $args = [] ) {
	return new WP_Query(
		wp_parse_args(
			$args,
			[
				'post_type'           => 'ogt_itinerary',
				'post_status'         => 'publish',
				'posts_per_page'      => 4,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			]
		)
	);
}

/**
 * Estimated reading time of a post, in minutes.
 *
 * @param int|WP_Post $post Post.
 * @return int
 */
function ogtrips_read_minutes( $post ) {
	$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post ) ) );

	return max( 1, (int) ceil( $words / 220 ) );
}

/**
 * Main label for an article (tour guide topic or blog category).
 *
 * @param int|WP_Post $post Post.
 * @return string
 */
function ogtrips_article_label( $post ) {
	$post     = get_post( $post );
	$taxonomy = 'ogt_guide' === $post->post_type ? 'ogt_guide_topic' : 'category';
	$terms    = get_the_terms( $post, $taxonomy );

	return $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '';
}

/**
 * Link to the homepage contact section (the site-wide "Reserve" target).
 *
 * @return string
 */
function ogtrips_contact_url() {
	return home_url( '/#contact' );
}

/**
 * Notice after an enquiry was sent (?enquiry=sent|error).
 *
 * @return string Safe HTML.
 */
function ogtrips_enquiry_notice() {
	$state = isset( $_GET['enquiry'] ) ? sanitize_key( wp_unslash( $_GET['enquiry'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

	if ( 'sent' === $state ) {
		return '<p class="form-msg is-ok" role="status">' . esc_html__( 'Thank you — your enquiry has reached us. A trip expert will be in touch shortly.', 'ogtrips' ) . '</p>';
	}

	if ( 'error' === $state ) {
		return '<p class="form-msg is-error" role="alert">' . esc_html__( 'Sorry, we could not send that. Please check your phone number and try again, or message us on WhatsApp.', 'ogtrips' ) . '</p>';
	}

	return '';
}

/**
 * Hidden fields every enquiry form needs.
 *
 * @param string $source Form name (contact, booking, newsletter).
 * @param int    $trip   Trip ID, if any.
 */
function ogtrips_enquiry_hidden_fields( $source, $trip = 0 ) {
	?>
	<input type="hidden" name="action" value="ogtrips_enquiry">
	<input type="hidden" name="form" value="<?php echo esc_attr( $source ); ?>">
	<input type="hidden" name="itinerary_id" value="<?php echo esc_attr( (string) (int) $trip ); ?>">
	<input type="hidden" name="source_page" value="<?php echo esc_url( home_url( add_query_arg( [] ) ) ); ?>">
	<?php wp_nonce_field( 'ogtrips_enquiry', 'ogtrips_nonce' ); ?>
	<div class="hp-field" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
	<?php
}
