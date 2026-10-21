<?php
/**
 * Trip experts and guide authors are WordPress users. Profile fields
 * (group_ogt_user.json): ogt_photo, byline_role, specialty, reply_time, whatsapp.
 *
 * Avatars come from the profile photo — never from Gravatar (no third-party requests).
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolves a user ID from anything get_avatar() accepts.
 *
 * @param mixed $id_or_email User ID, email, WP_User, WP_Post or WP_Comment.
 * @return int
 */
function ogtrips_core_avatar_user_id( $id_or_email ) {
	if ( is_numeric( $id_or_email ) ) {
		return (int) $id_or_email;
	}

	if ( $id_or_email instanceof WP_User ) {
		return (int) $id_or_email->ID;
	}

	if ( $id_or_email instanceof WP_Post ) {
		return (int) $id_or_email->post_author;
	}

	if ( $id_or_email instanceof WP_Comment ) {
		return (int) $id_or_email->user_id;
	}

	if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );

		return $user ? (int) $user->ID : 0;
	}

	return 0;
}

/**
 * Uses the profile photo as the avatar, or a local placeholder — never Gravatar.
 *
 * @param array<string,mixed> $args        Avatar data.
 * @param mixed               $id_or_email Who the avatar is for.
 * @return array<string,mixed>
 */
function ogtrips_core_local_avatar( $args, $id_or_email ) {
	$user_id  = ogtrips_core_avatar_user_id( $id_or_email );
	$photo_id = $user_id ? (int) get_user_meta( $user_id, 'ogt_photo', true ) : 0;
	$size     = max( 1, (int) ( $args['size'] ?? 96 ) );
	$url      = $photo_id ? wp_get_attachment_image_url( $photo_id, $size > 150 ? 'medium' : 'thumbnail' ) : false;

	$args['url']          = $url ? $url : OGTRIPS_CORE_URL . 'assets/avatar.svg';
	$args['found_avatar'] = (bool) $url;

	return $args;
}
add_filter( 'pre_get_avatar_data', 'ogtrips_core_local_avatar', 10, 2 );
