<?php
/**
 * Dashboard widget "OgTrips": quick-add buttons and the latest enquiries.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the widget at the top of the dashboard.
 */
function ogtrips_core_dashboard_widget() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_add_dashboard_widget( 'ogtrips_dashboard', __( 'OgTrips', 'ogtrips-core' ), 'ogtrips_core_dashboard_widget_render', null, null, 'normal', 'high' );
}
add_action( 'wp_dashboard_setup', 'ogtrips_core_dashboard_widget' );

/**
 * Renders the widget.
 */
function ogtrips_core_dashboard_widget_render() {
	$quick_add = [
		'ogt_itinerary' => __( 'Add a trip', 'ogtrips-core' ),
		'ogt_guide'     => __( 'Add a tour guide', 'ogtrips-core' ),
		'post'          => __( 'Add a blog post', 'ogtrips-core' ),
		'ogt_review'    => __( 'Add a review', 'ogtrips-core' ),
		'ogt_moment'    => __( 'Add a moment', 'ogtrips-core' ),
	];

	echo '<div class="ogtrips-quick-add">';

	foreach ( $quick_add as $post_type => $label ) {
		$type = get_post_type_object( $post_type );

		if ( $type && current_user_can( $type->cap->create_posts ) ) {
			printf(
				'<a class="button %s" href="%s">%s</a>',
				'ogt_itinerary' === $post_type ? 'button-primary' : '',
				esc_url( admin_url( 'post-new.php' . ( 'post' === $post_type ? '' : '?post_type=' . $post_type ) ) ),
				esc_html( $label )
			);
		}
	}

	echo '</div>';

	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}

	$enquiries = get_posts(
		[
			'post_type'      => 'ogt_enquiry',
			'post_status'    => 'any',
			'posts_per_page' => 5,
			'no_found_rows'  => true,
		]
	);

	printf( '<h3 class="ogtrips-dashboard-heading">%s</h3>', esc_html__( 'Latest enquiries', 'ogtrips-core' ) );

	if ( ! $enquiries ) {
		printf( '<p>%s</p>', esc_html__( 'No enquiries yet. New ones from the website appear here and in Enquiries.', 'ogtrips-core' ) );
		return;
	}

	$statuses = ogtrips_core_enquiry_statuses();

	echo '<ul class="ogtrips-dashboard-enquiries">';

	foreach ( $enquiries as $enquiry ) {
		$status = (string) get_post_meta( $enquiry->ID, '_ogt_status', true );

		printf(
			'<li><a href="%s">%s</a> <span class="ogtrips-status ogtrips-status--%s">%s</span> <span class="ogtrips-muted">%s</span></li>',
			esc_url( get_edit_post_link( $enquiry->ID ) ?? '' ),
			esc_html( get_the_title( $enquiry ) ),
			esc_attr( $status ),
			esc_html( $statuses[ $status ] ?? $status ),
			esc_html( get_the_date( '', $enquiry ) )
		);
	}

	echo '</ul>';
	printf(
		'<p><a href="%s">%s</a></p>',
		esc_url( admin_url( 'edit.php?post_type=ogt_enquiry' ) ),
		esc_html__( 'See all enquiries', 'ogtrips-core' )
	);
}
