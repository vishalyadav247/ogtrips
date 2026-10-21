<?php
/**
 * Save messages on the classic edit screens: "Trip published." instead of "Post published."
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Messages per post type, indexed like core's (1 updated, 4 updated, 6 published, 7 saved, 8 submitted, 10 draft).
 *
 * @param array<string,array<int,string>> $messages Core messages.
 * @return array<string,array<int,string>>
 */
function ogtrips_core_post_updated_messages( $messages ) {
	$post = get_post();

	$types = [
		'ogt_itinerary' => [
			'updated'   => __( 'Trip updated.', 'ogtrips-core' ),
			'published' => __( 'Trip published.', 'ogtrips-core' ),
			'saved'     => __( 'Trip saved.', 'ogtrips-core' ),
			'draft'     => __( 'Trip draft updated.', 'ogtrips-core' ),
			'view'      => __( 'View trip', 'ogtrips-core' ),
		],
		'ogt_guide'     => [
			'updated'   => __( 'Tour guide updated.', 'ogtrips-core' ),
			'published' => __( 'Tour guide published.', 'ogtrips-core' ),
			'saved'     => __( 'Tour guide saved.', 'ogtrips-core' ),
			'draft'     => __( 'Tour guide draft updated.', 'ogtrips-core' ),
			'view'      => __( 'View tour guide', 'ogtrips-core' ),
		],
		'ogt_review'    => [
			'updated'   => __( 'Review updated.', 'ogtrips-core' ),
			'published' => __( 'Review published.', 'ogtrips-core' ),
			'saved'     => __( 'Review saved.', 'ogtrips-core' ),
			'draft'     => __( 'Review draft updated.', 'ogtrips-core' ),
			'view'      => '',
		],
		'ogt_moment'    => [
			'updated'   => __( 'Moment updated.', 'ogtrips-core' ),
			'published' => __( 'Moment published.', 'ogtrips-core' ),
			'saved'     => __( 'Moment saved.', 'ogtrips-core' ),
			'draft'     => __( 'Moment draft updated.', 'ogtrips-core' ),
			'view'      => '',
		],
	];

	foreach ( $types as $type => $text ) {
		// Public types get a "View" link like core's messages.
		$view = ( $post && $text['view'] && $type === $post->post_type )
			? sprintf( ' <a href="%s">%s</a>', esc_url( get_permalink( $post ) ), esc_html( $text['view'] ) )
			: '';

		$messages[ $type ] = [
			0  => '',
			1  => esc_html( $text['updated'] ) . $view,
			4  => esc_html( $text['updated'] ),
			6  => esc_html( $text['published'] ) . $view,
			7  => esc_html( $text['saved'] ),
			8  => esc_html( $text['saved'] ),
			10 => esc_html( $text['draft'] ),
		];
	}

	return $messages;
}
add_filter( 'post_updated_messages', 'ogtrips_core_post_updated_messages' );
