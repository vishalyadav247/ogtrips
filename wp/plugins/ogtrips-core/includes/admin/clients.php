<?php
/**
 * Clients (travellers) and their portal.
 *
 * - Role "Client" (ogt_client): can only read; never sees wp-admin.
 * - The team (Editor account) manages clients under Users → shown as "Clients": only
 *   client accounts are listed and editable; staff accounts stay administrator-only.
 * - Branded welcome email ("Your journey is confirmed! Set your password") and a
 *   "Resend login email" button.
 * - Portal routes /my-trips/ and /my-trips/{booking}/ rendered by the theme's portal.php.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

const OGTRIPS_CORE_CLIENT_ROLE = 'ogt_client';

/**
 * Registers the Client role (idempotent).
 */
function ogtrips_core_register_client_role() {
	if ( ! get_role( OGTRIPS_CORE_CLIENT_ROLE ) ) {
		add_role( OGTRIPS_CORE_CLIENT_ROLE, __( 'Client', 'ogtrips-core' ), [ 'read' => true ] );
	}
}
add_action( 'init', 'ogtrips_core_register_client_role', 5 );

/**
 * Is this user a client (traveller)?
 *
 * @param int|WP_User|null $user User (default: current).
 * @return bool
 */
function ogtrips_core_is_client( $user = null ) {
	if ( null === $user ) {
		$user = wp_get_current_user();
	} elseif ( is_numeric( $user ) ) {
		$user = get_userdata( (int) $user );
	}

	return $user instanceof WP_User && $user->exists() && in_array( OGTRIPS_CORE_CLIENT_ROLE, (array) $user->roles, true );
}

/**
 * Portal URL: the list, or one booking.
 *
 * @param int $booking_id Booking ID (0 = the list).
 * @return string
 */
function ogtrips_core_portal_url( $booking_id = 0 ) {
	return home_url( '/my-trips/' . ( $booking_id ? (int) $booking_id . '/' : '' ) );
}

/* -------------------------------------------------------------------------
 * The team manages clients
 * ---------------------------------------------------------------------- */

/**
 * The Editor account gets the user capabilities needed to add and edit clients.
 * What those capabilities reach is limited below (clients only).
 *
 * @param array<string,bool> $allcaps User capabilities.
 * @param string[]           $caps    Required primitive caps.
 * @param array<int,mixed>   $args    Requested cap and args.
 * @param WP_User            $user    User.
 * @return array<string,bool>
 */
function ogtrips_core_merchant_user_caps( $allcaps, $caps, $args, $user ) {
	if ( empty( $allcaps['manage_options'] ) && ! empty( $allcaps['edit_others_posts'] ) ) {
		foreach ( [ 'list_users', 'create_users', 'edit_users', 'delete_users', 'promote_users' ] as $cap ) {
			if ( in_array( $cap, $caps, true ) ) {
				$allcaps[ $cap ] = true;
			}
		}
	}

	return $allcaps;
}
add_filter( 'user_has_cap', 'ogtrips_core_merchant_user_caps', 10, 4 );

/**
 * Non-administrators may only edit, delete or change the role of client accounts.
 *
 * @param string[] $caps    Primitive caps.
 * @param string   $cap     Meta cap.
 * @param int      $user_id Acting user.
 * @param array    $args    Args (target user ID).
 * @return string[]
 */
function ogtrips_core_limit_user_edits( $caps, $cap, $user_id, $args ) {
	if ( in_array( $cap, [ 'edit_user', 'delete_user', 'promote_user', 'remove_user' ], true ) && ! empty( $args[0] ) && ! user_can( $user_id, 'manage_options' ) ) {
		$target = (int) $args[0];
		if ( $target !== (int) $user_id && ! ogtrips_core_is_client( $target ) ) {
			$caps[] = 'do_not_allow';
		}
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'ogtrips_core_limit_user_edits', 10, 4 );

/**
 * Non-administrators can only assign the Client role.
 *
 * @param array<string,array<string,mixed>> $roles Editable roles.
 * @return array<string,array<string,mixed>>
 */
function ogtrips_core_editable_roles( $roles ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		$roles = array_intersect_key( $roles, [ OGTRIPS_CORE_CLIENT_ROLE => true ] );
	}

	return $roles;
}
add_filter( 'editable_roles', 'ogtrips_core_editable_roles' );

/**
 * New users default to Client for everyone, administrators included: WordPress would
 * otherwise pre-select "Subscriber", and a Subscriber lands in a restricted wp-admin
 * instead of the portal. Staff accounts are still created by picking another role.
 *
 * @param string $role Default role.
 * @return string
 */
function ogtrips_core_default_new_user_role( $role ) {
	return is_admin() ? OGTRIPS_CORE_CLIENT_ROLE : $role;
}
add_filter( 'pre_option_default_role', 'ogtrips_core_default_new_user_role' );

/**
 * A traveller created as "Subscriber" by mistake becomes a Client the moment they log in
 * (the site has no other use for subscribers: comments and registration are off).
 *
 * @param string  $login User login.
 * @param WP_User $user  User.
 */
function ogtrips_core_subscriber_becomes_client( $login, $user ) {
	if ( $user instanceof WP_User && [ 'subscriber' ] === array_values( (array) $user->roles ) && get_role( OGTRIPS_CORE_CLIENT_ROLE ) ) {
		$user->set_role( OGTRIPS_CORE_CLIENT_ROLE );
	}
}
add_action( 'wp_login', 'ogtrips_core_subscriber_becomes_client', 5, 2 );

/**
 * Users list for the team shows clients only.
 *
 * @param WP_User_Query $query Query.
 */
function ogtrips_core_users_list_clients_only( $query ) {
	global $pagenow;

	if ( is_admin() && 'users.php' === $pagenow && ! current_user_can( 'manage_options' ) ) {
		$query->set( 'role', OGTRIPS_CORE_CLIENT_ROLE );
	}
}
add_action( 'pre_get_users', 'ogtrips_core_users_list_clients_only' );

/**
 * "Users" is called "Clients" for the team; the role filter links are reduced to clients.
 */
function ogtrips_core_clients_menu_label() {
	global $menu, $submenu;

	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	foreach ( (array) $menu as $index => $item ) {
		if ( isset( $item[2] ) && 'users.php' === $item[2] ) {
			$menu[ $index ][0] = __( 'Clients', 'ogtrips-core' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	if ( isset( $submenu['users.php'] ) ) {
		foreach ( $submenu['users.php'] as $index => $item ) {
			if ( 'users.php' === $item[2] ) {
				$submenu['users.php'][ $index ][0] = __( 'All clients', 'ogtrips-core' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} elseif ( 'user-new.php' === $item[2] ) {
				$submenu['users.php'][ $index ][0] = __( 'Add client', 'ogtrips-core' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}
	}
}
add_action( 'admin_menu', 'ogtrips_core_clients_menu_label', 998 );

/**
 * Role counts above the users list: clients only for the team.
 *
 * @param array<string,string> $views Views.
 * @return array<string,string>
 */
function ogtrips_core_users_views( $views ) {
	return current_user_can( 'manage_options' ) ? $views : array_intersect_key( $views, [ OGTRIPS_CORE_CLIENT_ROLE => true ] );
}
add_filter( 'views_users', 'ogtrips_core_users_views' );

/**
 * Users list: "Bookings" and "Resend login email" actions on client rows.
 *
 * @param array<string,string> $actions Row actions.
 * @param WP_User              $user    User.
 * @return array<string,string>
 */
function ogtrips_core_client_row_actions( $actions, $user ) {
	if ( ! ogtrips_core_is_client( $user ) || ! current_user_can( 'edit_user', $user->ID ) ) {
		return $actions;
	}

	$actions['ogt_bookings'] = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'post-new.php?post_type=ogt_booking&client=' . $user->ID ) ),
		esc_html__( 'Add booking', 'ogtrips-core' )
	);
	$actions['ogt_resend']   = sprintf(
		'<a href="%s">%s</a>',
		esc_url( ogtrips_core_resend_login_url( $user->ID ) ),
		esc_html__( 'Resend login email', 'ogtrips-core' )
	);

	return $actions;
}
add_filter( 'user_row_actions', 'ogtrips_core_client_row_actions', 10, 2 );

/**
 * Nonce-protected URL that re-sends the set-password email to a client.
 *
 * @param int $user_id Client user ID.
 * @return string
 */
function ogtrips_core_resend_login_url( $user_id ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=ogtrips_resend_login&user=' . (int) $user_id ), 'ogtrips_resend_login_' . (int) $user_id );
}

/**
 * Re-sends the welcome / set-password email (admin-post.php?action=ogtrips_resend_login).
 */
function ogtrips_core_resend_login() {
	$user_id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked below.

	if ( ! $user_id || ! check_admin_referer( 'ogtrips_resend_login_' . $user_id ) || ! current_user_can( 'edit_user', $user_id ) || ! ogtrips_core_is_client( $user_id ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'ogtrips-core' ), '', [ 'response' => 403, 'back_link' => true ] );
	}

	wp_new_user_notification( $user_id, null, 'user' );

	$back = wp_get_referer();
	$back = $back ? remove_query_arg( 'ogt_sent', $back ) : admin_url( 'users.php' );

	wp_safe_redirect( add_query_arg( 'ogt_sent', $user_id, $back ) );
	exit;
}
add_action( 'admin_post_ogtrips_resend_login', 'ogtrips_core_resend_login' );

/**
 * "Login email sent" notice after a resend.
 */
function ogtrips_core_resend_login_notice() {
	$user_id = isset( $_GET['ogt_sent'] ) ? absint( $_GET['ogt_sent'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$user    = $user_id ? get_userdata( $user_id ) : false;

	if ( $user ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			/* translators: %s: email address */
			esc_html( sprintf( __( 'Login email sent to %s.', 'ogtrips-core' ), $user->user_email ) )
		);
	}
}
add_action( 'admin_notices', 'ogtrips_core_resend_login_notice' );

/**
 * Client profile screen: their bookings and the resend button.
 *
 * @param WP_User $user User being edited.
 */
function ogtrips_core_client_profile_box( $user ) {
	if ( ! ogtrips_core_is_client( $user ) || ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}

	$bookings = ogtrips_core_client_bookings( $user->ID );
	?>
	<h2><?php esc_html_e( 'Client portal', 'ogtrips-core' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'Bookings', 'ogtrips-core' ); ?></th>
			<td>
				<?php if ( $bookings ) : ?>
					<ul style="margin:0 0 8px">
						<?php foreach ( $bookings as $booking_id ) : ?>
							<li><a href="<?php echo esc_url( get_edit_post_link( $booking_id ) ); ?>"><?php echo esc_html( ogtrips_core_booking_ref( $booking_id ) . ' · ' . get_the_title( $booking_id ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="description" style="margin-bottom:8px"><?php esc_html_e( 'No bookings yet.', 'ogtrips-core' ); ?></p>
				<?php endif; ?>
				<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ogt_booking&client=' . $user->ID ) ); ?>"><?php esc_html_e( 'Add a booking', 'ogtrips-core' ); ?></a>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Login', 'ogtrips-core' ); ?></th>
			<td>
				<a class="button" href="<?php echo esc_url( ogtrips_core_resend_login_url( $user->ID ) ); ?>"><?php esc_html_e( 'Resend login email', 'ogtrips-core' ); ?></a>
				<p class="description"><?php echo esc_html( sprintf( /* translators: %s: portal URL */ __( 'Sends a fresh "set your password" link. The client logs in at %s.', 'ogtrips-core' ), ogtrips_core_portal_url() ) ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'edit_user_profile', 'ogtrips_core_client_profile_box', 5 );

/* -------------------------------------------------------------------------
 * Welcome email
 * ---------------------------------------------------------------------- */

/**
 * Branded welcome email for clients: subject and wording from the client's blueprint
 * ("Your journey is confirmed! Set your password"), with the set-password link and the
 * portal address. Staff accounts keep WordPress's default email.
 *
 * @param array<string,string> $email    Email (to, subject, message, headers).
 * @param WP_User              $user     New user.
 * @param string               $blogname Site name.
 * @return array<string,string>
 */
function ogtrips_core_client_welcome_email( $email, $user, $blogname ) {
	if ( ! ogtrips_core_is_client( $user ) ) {
		return $email;
	}

	// The set-password URL WordPress put in its default message.
	$link = preg_match( '#https?://\S+action=rp\S*#', (string) $email['message'], $m ) ? html_entity_decode( $m[0] ) : '';
	$name = '' !== $user->first_name ? $user->first_name : $user->display_name;

	/* translators: %s: site name */
	$email['subject'] = sprintf( __( 'Your journey is confirmed! Set your password · %s', 'ogtrips-core' ), $blogname );

	$lines   = [];
	/* translators: %s: first name */
	$lines[] = sprintf( __( 'Hi %s,', 'ogtrips-core' ), $name );
	$lines[] = '';
	/* translators: %s: site name */
	$lines[] = sprintf( __( 'Welcome to the %s member club. Your account is ready: set a password to see your confirmed trip, your personalised day-by-day schedule, vouchers and documents, and your member rewards.', 'ogtrips-core' ), $blogname );
	$lines[] = '';
	$lines[] = __( 'Set your password (one click):', 'ogtrips-core' );
	$lines[] = $link;
	$lines[] = '';
	/* translators: %s: username */
	$lines[] = sprintf( __( 'Your username: %s', 'ogtrips-core' ), $user->user_login );
	/* translators: %s: portal URL */
	$lines[] = sprintf( __( 'Your trips, any time: %s', 'ogtrips-core' ), ogtrips_core_portal_url() );
	$lines[] = '';
	$lines[] = __( 'Questions? Reply to this email or message your trip expert on WhatsApp from the portal.', 'ogtrips-core' );
	$lines[] = '';
	$lines[] = __( 'Where to next?', 'ogtrips-core' );
	$lines[] = $blogname;

	$email['message'] = implode( "\n", $lines );

	return $email;
}
add_filter( 'wp_new_user_notification_email', 'ogtrips_core_client_welcome_email', 10, 3 );

/* -------------------------------------------------------------------------
 * Clients never see wp-admin; they land in the portal
 * ---------------------------------------------------------------------- */

/**
 * After login: clients go to the portal; staff who asked for the portal go to the dashboard.
 *
 * @param string           $redirect_to Requested redirect.
 * @param string           $requested   Requested redirect (raw).
 * @param WP_User|WP_Error $user        Logged-in user.
 * @return string
 */
function ogtrips_core_login_redirect( $redirect_to, $requested, $user ) {
	if ( ! $user instanceof WP_User ) {
		return $redirect_to;
	}

	if ( ogtrips_core_is_client( $user ) || ! user_can( $user, 'edit_posts' ) ) {
		return str_starts_with( (string) $redirect_to, ogtrips_core_portal_url() ) ? $redirect_to : ogtrips_core_portal_url();
	}

	return str_starts_with( (string) $redirect_to, ogtrips_core_portal_url() ) ? admin_url() : $redirect_to;
}
add_filter( 'login_redirect', 'ogtrips_core_login_redirect', 10, 3 );

/**
 * wp-admin is not for clients (profile edits happen in the portal).
 */
function ogtrips_core_block_admin_for_clients() {
	// Clients, and any other account with no content rights (e.g. a Subscriber), belong in the portal.
	if ( is_user_logged_in() && ( ogtrips_core_is_client() || ! current_user_can( 'edit_posts' ) ) && ! wp_doing_ajax() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		wp_safe_redirect( ogtrips_core_portal_url() );
		exit;
	}
}
add_action( 'admin_init', 'ogtrips_core_block_admin_for_clients' );

/**
 * No admin bar for clients.
 *
 * @param bool $show Whether to show the bar.
 * @return bool
 */
function ogtrips_core_hide_admin_bar_for_clients( $show ) {
	return ogtrips_core_is_client() ? false : $show;
}
add_filter( 'show_admin_bar', 'ogtrips_core_hide_admin_bar_for_clients' );

/**
 * Login screen welcome line mentions clients too.
 *
 * @param string $message Existing message.
 * @return string
 */
function ogtrips_core_client_login_message( $message ) {
	return str_replace(
		esc_html__( 'Log in to manage trips, tour guides and enquiries.', 'ogtrips-core' ),
		esc_html__( 'Log in to see your trips, schedule and documents.', 'ogtrips-core' ),
		$message
	);
}
add_filter( 'login_message', 'ogtrips_core_client_login_message', 20 );

/* -------------------------------------------------------------------------
 * Portal routes: /my-trips/ and /my-trips/{id}/
 * ---------------------------------------------------------------------- */

/**
 * Rewrite rules and query vars.
 */
function ogtrips_core_portal_rewrites() {
	add_rewrite_rule( '^my-trips/?$', 'index.php?ogt_portal=list', 'top' );
	add_rewrite_rule( '^my-trips/([0-9]+)/?$', 'index.php?ogt_portal=booking&ogt_booking=$matches[1]', 'top' );
}
add_action( 'init', 'ogtrips_core_portal_rewrites' );

/**
 * Public query vars for the portal.
 *
 * @param string[] $vars Query vars.
 * @return string[]
 */
function ogtrips_core_portal_query_vars( $vars ) {
	$vars[] = 'ogt_portal';
	$vars[] = 'ogt_booking';

	return $vars;
}
add_filter( 'query_vars', 'ogtrips_core_portal_query_vars' );

/**
 * Which portal screen this request is: 'list', 'booking' or '' (not the portal).
 *
 * @return string
 */
function ogtrips_core_portal_screen() {
	$screen = (string) get_query_var( 'ogt_portal' );

	return in_array( $screen, [ 'list', 'booking' ], true ) ? $screen : '';
}

/**
 * Portal requests: require login, resolve the booking, and never 404.
 */
function ogtrips_core_portal_route() {
	global $wp_query;

	$screen = ogtrips_core_portal_screen();
	if ( '' === $screen ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( ogtrips_core_portal_url( 'booking' === $screen ? (int) get_query_var( 'ogt_booking' ) : 0 ) ) );
		exit;
	}

	if ( 'booking' === $screen && ! ogtrips_core_user_can_view_booking( (int) get_query_var( 'ogt_booking' ), get_current_user_id() ) ) {
		wp_safe_redirect( ogtrips_core_portal_url() );
		exit;
	}

	$wp_query->is_404 = false; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- virtual page.
	status_header( 200 );
	nocache_headers();
}
add_action( 'template_redirect', 'ogtrips_core_portal_route', 1 );

/**
 * Renders the portal with the theme's portal.php (falls back to a minimal page).
 *
 * @param string $template Template path.
 * @return string
 */
function ogtrips_core_portal_template( $template ) {
	if ( '' === ogtrips_core_portal_screen() ) {
		return $template;
	}

	$theme = locate_template( 'portal.php' );

	return '' !== $theme ? $theme : OGTRIPS_CORE_DIR . 'includes/portal-fallback.php';
}
add_filter( 'template_include', 'ogtrips_core_portal_template', 99 );

/**
 * Search engines never index the portal.
 *
 * @param array<string,mixed> $robots Robots directives.
 * @return array<string,mixed>
 */
function ogtrips_core_portal_robots( $robots ) {
	if ( '' !== ogtrips_core_portal_screen() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'ogtrips_core_portal_robots' );

/**
 * Yoast's robots string for the portal (it replaces core's robots output).
 *
 * @param string $robots Robots string.
 * @return string
 */
function ogtrips_core_portal_yoast_robots( $robots ) {
	return '' !== ogtrips_core_portal_screen() ? 'noindex, nofollow' : $robots;
}
add_filter( 'wpseo_robots', 'ogtrips_core_portal_yoast_robots', 20 );

/**
 * Document title for the portal.
 *
 * @param string $title Title.
 * @return string
 */
function ogtrips_core_portal_title( $title ) {
	$screen = ogtrips_core_portal_screen();

	if ( '' === $screen ) {
		return $title;
	}

	$page = 'booking' === $screen ? ogtrips_core_booking_ref( (int) get_query_var( 'ogt_booking' ) ) : __( 'My trips', 'ogtrips-core' );

	return $page . ' · ' . get_bloginfo( 'name' );
}
add_filter( 'pre_get_document_title', 'ogtrips_core_portal_title' );
add_filter( 'wpseo_title', 'ogtrips_core_portal_title', 20 );
add_filter( 'wpseo_opengraph_title', 'ogtrips_core_portal_title', 20 );

/**
 * Body class for portal styling.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function ogtrips_core_portal_body_class( $classes ) {
	if ( '' !== ogtrips_core_portal_screen() ) {
		$classes[] = 'ogt-portal';
	}

	return $classes;
}
add_filter( 'body_class', 'ogtrips_core_portal_body_class' );

/* -------------------------------------------------------------------------
 * The client updates their phone from the portal
 * ---------------------------------------------------------------------- */

/**
 * Saves the client's phone (admin-post.php?action=ogtrips_client_phone).
 */
function ogtrips_core_save_client_phone() {
	if ( ! is_user_logged_in() || ! check_admin_referer( 'ogtrips_client_phone' ) ) {
		wp_safe_redirect( ogtrips_core_portal_url() );
		exit;
	}

	$phone = isset( $_POST['client_phone'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['client_phone'] ) ), 0, 30 ) : '';
	update_user_meta( get_current_user_id(), 'client_phone', $phone );

	wp_safe_redirect( add_query_arg( 'saved', '1', ogtrips_core_portal_url() ) . '#account' );
	exit;
}
add_action( 'admin_post_ogtrips_client_phone', 'ogtrips_core_save_client_phone' );
