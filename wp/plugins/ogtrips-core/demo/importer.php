<?php
/**
 * Demo-content importer/remover. Everything it creates is tagged with the _ogt_demo meta
 * so "Remove demo content" deletes exactly that (and nothing the merchant added).
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Downloads an image once into the Media Library (cached by URL) and returns its ID.
 *
 * @param string $url  Remote image URL.
 * @param string $name File/alt name.
 * @return int
 */
function ogtrips_core_demo_image( $url, $name ) {
	$map = (array) get_option( 'ogtrips_demo_images', [] );

	if ( ! empty( $map[ $url ] ) && get_post( (int) $map[ $url ] ) ) {
		return (int) $map[ $url ];
	}

	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 60 );
	if ( is_wp_error( $tmp ) ) {
		return 0;
	}

	$id = media_handle_sideload(
		[
			'name'     => sanitize_file_name( $name ) . '.jpg',
			'tmp_name' => $tmp,
		],
		0,
		ucwords( str_replace( '-', ' ', $name ) )
	);

	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}

	update_post_meta( $id, '_ogt_demo', 1 );
	update_post_meta( $id, '_wp_attachment_image_alt', ucwords( str_replace( '-', ' ', $name ) ) );
	$map[ $url ] = $id;
	update_option( 'ogtrips_demo_images', $map, false );

	return (int) $id;
}

/**
 * Finds a post by slug and type. By default only posts the importer created (tagged _ogt_demo),
 * so a merchant post that happens to use the same slug is never adopted or overwritten.
 *
 * @param string $type      Post type.
 * @param string $slug      Slug.
 * @param bool   $demo_only Only match demo-tagged posts.
 * @return int
 */
function ogtrips_core_demo_find( $type, $slug, $demo_only = true ) {
	$args = [
		'post_type'      => $type,
		'name'           => $slug,
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	];
	if ( $demo_only ) {
		$args['meta_key'] = '_ogt_demo'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	}
	$found = get_posts( $args );

	return $found ? (int) $found[0] : 0;
}

/**
 * Which post an import should write to: the untouched demo post (its ID), a new one (0), or none (-1)
 * when the merchant edited the demo post (it is real content now — untagged and left alone) or owns
 * a post with the same slug.
 *
 * @param string $type Post type.
 * @param string $slug Slug.
 * @return int
 */
function ogtrips_core_demo_slot( $type, $slug ) {
	$id = ogtrips_core_demo_find( $type, $slug );

	if ( ! $id ) {
		return ogtrips_core_demo_find( $type, $slug, false ) ? -1 : 0;
	}

	$stamp = (string) get_post_meta( $id, '_ogt_demo_modified', true );
	if ( '' !== $stamp && get_post_field( 'post_modified_gmt', $id ) !== $stamp ) {
		delete_post_meta( $id, '_ogt_demo' );
		delete_post_meta( $id, '_ogt_demo_modified' );
		return -1;
	}

	return $id;
}

/**
 * Stable fingerprint of a field value (DB values come back as strings, fresh ones as ints).
 *
 * @param mixed $value Raw field value.
 * @return string
 */
function ogtrips_core_demo_hash( $value ) {
	if ( is_array( $value ) ) {
		array_walk_recursive(
			$value,
			static function ( &$v ) {
				$v = is_bool( $v ) ? (string) (int) $v : (string) $v;
			}
		);
	} else {
		$value = is_bool( $value ) ? (string) (int) $value : (string) $value;
	}

	return md5( wp_json_encode( $value ) );
}

/**
 * Records a demo post's last-modified time; "Remove demo content" keeps posts edited after import.
 *
 * @param int $id Post ID.
 */
function ogtrips_core_demo_stamp( $id ) {
	clean_post_cache( $id );
	update_post_meta( $id, '_ogt_demo_modified', get_post_field( 'post_modified_gmt', $id ) );
}

/**
 * Sets a Homepage/Site Settings field only when it is empty or still holds the value the importer
 * wrote last time — the merchant's own settings are never overwritten. Remembers what it wrote.
 *
 * @param string $name  Field name.
 * @param mixed  $value Value.
 */
function ogtrips_core_demo_set_option( $name, $value ) {
	$written = (array) get_option( 'ogtrips_demo_options', [] );
	$current = get_field( $name, 'option', false );
	$is_ours = isset( $written[ $name ] ) && ogtrips_core_demo_hash( $current ) === $written[ $name ];

	if ( ! $is_ours && ! ( null === $current || '' === $current || false === $current || [] === $current ) ) {
		return;
	}

	update_field( $name, $value, 'option' );
	$written[ $name ] = ogtrips_core_demo_hash( get_field( $name, 'option', false ) );
	update_option( 'ogtrips_demo_options', $written, false );
}

/**
 * Imports the demo content. Safe to run twice (updates instead of duplicating).
 *
 * @param callable|null $log Progress logger.
 * @return array<string,int> Counts.
 */
function ogtrips_core_import_demo( $log = null ) {
	$log  = $log ? $log : static function () {};
	$data = require OGTRIPS_CORE_DIR . 'demo/data.php';

	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 900 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- image downloads.
	}

	$images = [];
	$img    = static function ( $key ) use ( &$images, $data, $log ) {
		if ( ! isset( $images[ $key ] ) ) {
			$log( 'Image: ' . $key );
			$images[ $key ] = ogtrips_core_demo_image( $data['images'][ $key ], 'ogtrips-demo-' . $key );
		}
		return $images[ $key ];
	};

	$counts = [];

	// Destinations.
	$dest_ids = [];
	foreach ( $data['destinations'] as $slug => $dest ) {
		$term = term_exists( $slug, 'ogt_destination' );
		if ( ! $term ) {
			$term = wp_insert_term( $dest[0], 'ogt_destination', [ 'slug' => $slug, 'description' => $dest[1] ] );
			// Only destinations created here are demo; existing ones belong to the merchant.
			if ( ! is_wp_error( $term ) ) {
				update_term_meta( (int) $term['term_id'], '_ogt_demo', 1 );
			}
		}
		if ( $term && ! is_wp_error( $term ) ) {
			$dest_ids[ $slug ] = (int) $term['term_id'];
		}
	}

	// Trip expert (guide author). An existing non-demo user with the same login is left alone.
	$e       = $data['expert'];
	$user    = get_user_by( 'login', $e['login'] );
	$user_id = 0;
	if ( $user && get_user_meta( $user->ID, '_ogt_demo', true ) ) {
		$user_id = (int) $user->ID;
	} elseif ( ! $user ) {
		$user_id = wp_insert_user(
			[
				'user_login'   => $e['login'],
				'user_pass'    => wp_generate_password( 24 ),
				'user_email'   => $e['login'] . '@ogtrips.local',
				'first_name'   => $e['first_name'],
				'last_name'    => $e['last_name'],
				'display_name' => $e['first_name'] . ' ' . $e['last_name'],
				'description'  => $e['description'],
				'role'         => 'author',
			]
		);
		$user_id = is_wp_error( $user_id ) ? 0 : (int) $user_id;
	}
	if ( $user_id ) {
		update_user_meta( $user_id, '_ogt_demo', 1 );
		update_field( 'ogt_photo', $img( $e['photo'] ), 'user_' . $user_id );
		foreach ( [ 'byline_role', 'specialty', 'reply_time' ] as $key ) {
			update_field( $key, $e[ $key ], 'user_' . $user_id );
		}
	}
	$admins    = get_users(
		[
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ID',
		]
	);
	$author_id = $user_id ? $user_id : ( get_current_user_id() ? get_current_user_id() : (int) ( $admins[0] ?? 0 ) );

	// Trips.
	$trip_ids = [];
	foreach ( $data['trips'] as $trip ) {
		$log( 'Trip: ' . $trip['title'] );
		$id = ogtrips_core_demo_slot( 'ogt_itinerary', $trip['slug'] );
		if ( -1 === $id ) {
			$trip_ids[ $trip['slug'] ] = ogtrips_core_demo_find( 'ogt_itinerary', $trip['slug'], false ); // Merchant's version: link to it, don't touch it.
			continue;
		}
		$id = wp_insert_post(
			[
				'ID'           => $id,
				'post_type'    => 'ogt_itinerary',
				'post_status'  => 'publish',
				'post_name'    => $trip['slug'],
				'post_title'   => $trip['title'],
				'post_excerpt' => $trip['excerpt'],
				'post_content' => $trip['content'],
				'meta_input'   => [ '_ogt_demo' => 1 ],
			]
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$trip_ids[ $trip['slug'] ] = $id;
		set_post_thumbnail( $id, $img( $trip['image'] ) );
		$trip_dests = array_values( array_filter( array_map( static fn( $slug ) => $dest_ids[ $slug ] ?? 0, [ $trip['dest'], $trip['dest2'] ?? '' ] ) ) );
		wp_set_object_terms( $id, $trip_dests, 'ogt_destination' );
		wp_set_object_terms( $id, $trip['types'], 'ogt_trip_type' );

		$f = $trip['fields'];
		foreach ( [ 'days', 'stays' ] as $rep ) {
			if ( isset( $f[ $rep ] ) ) {
				foreach ( $f[ $rep ] as $k => $row ) {
					$f[ $rep ][ $k ]['image'] = $img( $row['image'] );
				}
			}
		}
		foreach ( [ 'included', 'excluded' ] as $rep ) {
			if ( isset( $f[ $rep ] ) ) {
				$f[ $rep ] = array_map(
					static function ( $text ) {
						return [ 'text' => $text ];
					},
					$f[ $rep ]
				);
			}
		}
		if ( isset( $f['gallery'] ) ) {
			$f['gallery'] = array_values( array_filter( array_map( $img, $f['gallery'] ) ) );
		}
		if ( $user_id ) {
			$f['expert'] = $user_id;
		}
		foreach ( $f as $name => $value ) {
			update_field( $name, $value, $id );
		}
		ogtrips_core_demo_stamp( $id );
	}
	$counts['trips'] = count( $trip_ids );

	// Reviews.
	foreach ( $data['reviews'] as $i => $r ) {
		$slug = 'demo-review-' . ( $i + 1 );
		$slot = ogtrips_core_demo_slot( 'ogt_review', $slug );
		if ( -1 === $slot ) {
			continue;
		}
		$id = wp_insert_post(
			[
				'ID'          => $slot,
				'post_type'   => 'ogt_review',
				'post_status' => 'publish',
				'post_name'   => $slug,
				'post_title'  => $r[0],
				'meta_input'  => [ '_ogt_demo' => 1 ],
			]
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$values = [
			'reviewer_name' => $r[0],
			'reviewer_city' => $r[1],
			'travel_date'   => $r[2],
			'rating'        => '5',
			'source'        => $r[3],
			'avatar'        => $img( $r[4] ),
			'itinerary'     => $trip_ids[ $r[5] ] ?? 0,
			'quote'         => $r[6],
			'featured'      => 1,
		];
		foreach ( $values as $name => $value ) {
			update_field( $name, $value, $id );
		}
		ogtrips_core_demo_stamp( $id );
	}
	$counts['reviews'] = count( $data['reviews'] );

	// Moments.
	foreach ( $data['moments'] as $i => $m ) {
		$slug = 'demo-moment-' . ( $i + 1 );
		$slot = ogtrips_core_demo_slot( 'ogt_moment', $slug );
		if ( -1 === $slot ) {
			continue;
		}
		$id = wp_insert_post(
			[
				'ID'          => $slot,
				'post_type'   => 'ogt_moment',
				'post_status' => 'publish',
				'post_name'   => $slug,
				'post_title'  => $m[0],
				'menu_order'  => $i,
				'meta_input'  => [ '_ogt_demo' => 1 ],
			]
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$values = [
			'handle'     => $m[0],
			'image'      => $img( $m[1] ),
			'tile_size'  => $m[2],
			'media_type' => $m[3],
			'duration'   => $m[4],
			'likes'      => $m[5],
			'views'      => $m[6],
		];
		foreach ( $values as $name => $value ) {
			update_field( $name, $value, $id );
		}
		ogtrips_core_demo_stamp( $id );
	}
	$counts['moments'] = count( $data['moments'] );

	// Tour guides.
	foreach ( $data['guides'] as $g ) {
		$log( 'Guide: ' . $g['title'] );
		$slot = ogtrips_core_demo_slot( 'ogt_guide', $g['slug'] );
		if ( -1 === $slot ) {
			continue;
		}
		$content = 'guide' === $g['blocks']
			? ogtrips_core_demo_guide_blocks( $img( 'flags' ), $trip_ids['soul-of-ladakh'] ?? 0 )
			: ogtrips_core_demo_short_blocks( $g['excerpt'] );
		$id      = wp_insert_post(
			[
				'ID'           => $slot,
				'post_type'    => 'ogt_guide',
				'post_status'  => 'publish',
				'post_name'    => $g['slug'],
				'post_title'   => $g['title'],
				'post_excerpt' => $g['excerpt'],
				'post_content' => wp_slash( $content ), // wp_insert_post() unslashes.
				'post_author'  => $author_id,
				'meta_input'   => [ '_ogt_demo' => 1 ],
			]
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		set_post_thumbnail( $id, $img( $g['image'] ) );
		if ( '' !== $g['dest'] && isset( $dest_ids[ $g['dest'] ] ) ) {
			wp_set_object_terms( $id, [ $dest_ids[ $g['dest'] ] ], 'ogt_destination' );
		}
		wp_set_object_terms( $id, [ $g['topic'] ], 'ogt_guide_topic' );
		foreach ( (array) ( $g['fields'] ?? [] ) as $name => $value ) {
			update_field( $name, $value, $id );
		}
		ogtrips_core_demo_stamp( $id );
	}
	$counts['guides'] = count( $data['guides'] );

	// Homepage + Site Settings.
	$home = $data['homepage'];
	foreach ( $home['hero_places'] as $k => $row ) {
		$home['hero_places'][ $k ]['image'] = $img( $row['image'] );
	}
	$home['hero_cta_primary']   = [ 'url' => '#trips', 'title' => 'Our OG Trips', 'target' => '' ];
	$home['hero_cta_secondary'] = [ 'url' => '#social', 'title' => 'Watch traveller reels', 'target' => '' ];
	foreach ( $home as $name => $value ) {
		ogtrips_core_demo_set_option( $name, $value );
	}
	$settings                  = $data['settings'];
	$settings['enquiry_email'] = (string) get_option( 'admin_email' );
	foreach ( $settings as $name => $value ) {
		ogtrips_core_demo_set_option( $name, $value );
	}

	// Static front page + "Blog" posts page (structural, not demo-tagged), only if not set up yet.
	if ( 'page' !== get_option( 'show_on_front' ) || ! get_option( 'page_on_front' ) ) {
		$front = ogtrips_core_demo_find( 'page', 'home', false );
		$front = $front ? $front : wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Home', 'post_name' => 'home' ] );
		$blog  = ogtrips_core_demo_find( 'page', 'blog', false );
		$blog  = $blog ? $blog : wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Blog', 'post_name' => 'blog' ] );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $front );
		update_option( 'page_for_posts', (int) $blog );
	}

	update_option( 'ogtrips_demo_imported', time(), false );

	return $counts;
}

/**
 * Ladakh guide body as core blocks (+ Trip CTA block).
 *
 * @param int $image_id Inline image.
 * @param int $trip_id  Trip for the CTA.
 * @return string
 */
function ogtrips_core_demo_guide_blocks( $image_id, $trip_id ) {
	$p  = static function ( $html ) {
		return "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->\n\n";
	};
	$h2 = static function ( $text ) {
		return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . $text . "</h2>\n<!-- /wp:heading -->\n\n";
	};
	$ul = static function ( $items ) {
		$out = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
		foreach ( $items as $item ) {
			$out .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->";
		}
		return $out . "</ul>\n<!-- /wp:list -->\n\n";
	};

	$url = (string) wp_get_attachment_image_url( $image_id, 'large' );
	$cta = wp_json_encode(
		[
			'name' => 'ogtrips/trip-cta',
			'data' => [
				'itinerary'  => $trip_id,
				'_itinerary' => 'field_ogt_trip_cta_itinerary',
				'heading'    => 'The Soul of Ladakh, fully planned',
				'_heading'   => 'field_ogt_trip_cta_heading',
				'text'       => 'Everything in this guide, done for you — hotels, a private car and driver, permits and a trip captain on call.',
				'_text'      => 'field_ogt_trip_cta_text',
			],
			'mode' => 'preview',
		],
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);

	return $p( 'Ladakh is easy to love and surprisingly easy to get wrong. The air is thin, distances are long and the best views sit above 5,000 metres. This guide covers everything our trip captains tell travellers before they fly to Leh — so your first trip feels like your fifth.' )
		. $h2( 'Best time to visit' )
		. $p( 'The season runs from <strong>May to September</strong>, when the high passes are open and Pangong Lake shines turquoise. June to August is peak season, so book early. From October the roads to Nubra and Pangong can close with snow — winter trips are for the well-prepared.' )
		. $h2( 'Acclimatisation comes first' )
		. $p( 'Leh sits at 3,500 metres. Spend your first two days resting and taking it slow around Leh before crossing Khardung La (5,359 m) — every OgTrips itinerary is planned this way.' )
		. "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>\"Do nothing on day one. The passes will still be there on day three — and you will actually enjoy them.\"</p>\n<!-- /wp:paragraph --></blockquote>\n<!-- /wp:quote -->\n\n"
		. $h2( 'Where to go' )
		. $ul( [ '<strong>Leh:</strong> Shanti Stupa, Leh Palace, the market and the Indus-valley monasteries. 2–3 nights.', '<strong>Nubra Valley:</strong> Diskit Monastery, Hunder sand dunes and Bactrian camels. 1 night.', '<strong>Pangong Lake:</strong> sunsets, sunrise and the clearest night skies. 1 night.' ] )
		. ( $image_id ? "<!-- wp:image {\"id\":" . (int) $image_id . ",\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"" . esc_url( $url ) . "\" alt=\"\" class=\"wp-image-" . (int) $image_id . "\"/><figcaption class=\"wp-element-caption\">Prayer flags on the passes — distances look short but take hours.</figcaption></figure>\n<!-- /wp:image -->\n\n" : '' )
		. $h2( 'Getting around' )
		. $ul( [ '<strong>Private car with driver:</strong> the most comfortable way to cover the long mountain roads.', '<strong>Motorbike:</strong> unforgettable, but only for experienced riders.', '<strong>Permits:</strong> an Inner Line Permit is needed for Nubra and Pangong — we arrange it.' ] )
		. $h2( 'Budget breakdown' )
		. "<!-- wp:table -->\n<figure class=\"wp-block-table\"><table><thead><tr><th>Per day</th><th>Budget</th><th>Mid-range</th><th>Luxury</th></tr></thead><tbody><tr><td>Stay</td><td>₹1,500</td><td>₹4,500</td><td>₹12,000+</td></tr><tr><td>Food</td><td>₹600</td><td>₹1,500</td><td>₹3,000+</td></tr><tr><td>Transport</td><td>₹1,000</td><td>₹4,000</td><td>₹6,000</td></tr><tr><td>Activities</td><td>₹500</td><td>₹1,500</td><td>₹4,000+</td></tr></tbody></table></figure>\n<!-- /wp:table -->\n\n"
		. ( $trip_id ? '<!-- wp:ogtrips/trip-cta ' . $cta . " /-->\n\n" : '' )
		. $h2( 'Permits &amp; entry' )
		. $p( 'Indian citizens need an Inner Line Permit for Nubra Valley and Pangong Lake; foreign nationals need a Protected Area Permit. Rules change — always check before you travel.' )
		. $h2( 'What to pack' )
		. $ul( [ 'Warm layers and a down jacket, even in summer', 'Sunscreen, sunglasses and lip balm — the sun is strong at altitude', 'Comfortable walking shoes', 'Basic medicines and a refillable water bottle' ] );
}


/**
 * Short placeholder body for the smaller demo guides.
 *
 * @param string $intro Intro line.
 * @return string
 */
function ogtrips_core_demo_short_blocks( $intro ) {
	return "<!-- wp:paragraph -->\n<p>" . esc_html( $intro ) . " This is demo content — replace it with your own article in wp-admin → Tour Guides.</p>\n<!-- /wp:paragraph -->\n\n"
		. "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">The short answer</h2>\n<!-- /wp:heading -->\n\n"
		. "<!-- wp:paragraph -->\n<p>Our trip captains have done this many times. Ask us on WhatsApp and we will tailor the advice to your dates, budget and travel style.</p>\n<!-- /wp:paragraph -->\n\n"
		. "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Our top tips</h2>\n<!-- /wp:heading -->\n\n"
		. "<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>Book early for peak season.</li>\n<!-- /wp:list-item --><!-- wp:list-item -->\n<li>Travel light and plan slow days.</li>\n<!-- /wp:list-item --><!-- wp:list-item -->\n<li>Keep a trip captain on speed dial.</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n";
}

/**
 * Deletes the demo content the importer created and nobody has touched since:
 * - demo posts that were edited after import are kept (they became real content);
 * - demo photos still used by kept or merchant content are kept;
 * - demo destinations that still have other posts are kept;
 * - Homepage / Site Settings fields are cleared only if they still hold the demo value.
 *
 * @return int Number of items deleted.
 */
function ogtrips_core_remove_demo() {
	global $wpdb;

	$deleted = 0;

	// 1. Settings that still hold the value the importer wrote.
	$written = (array) get_option( 'ogtrips_demo_options', [] );
	foreach ( $written as $name => $hash ) {
		if ( ogtrips_core_demo_hash( get_field( $name, 'option', false ) ) === $hash ) {
			delete_field( $name, 'option' );
		}
	}
	delete_option( 'ogtrips_demo_options' );

	// 2. Demo posts not edited since import.
	$ids     = get_posts(
		[
			'post_type'      => [ 'ogt_itinerary', 'ogt_guide', 'ogt_review', 'ogt_moment', 'post' ],
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_ogt_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		]
	);
	$removed = [];
	foreach ( $ids as $id ) {
		$stamp = (string) get_post_meta( $id, '_ogt_demo_modified', true );
		if ( '' !== $stamp && get_post_field( 'post_modified_gmt', $id ) !== $stamp ) {
			delete_post_meta( $id, '_ogt_demo' ); // Edited by the merchant: it is real content now.
			delete_post_meta( $id, '_ogt_demo_modified' );
			continue;
		}
		$removed[] = (int) $id;
	}
	foreach ( $removed as $id ) {
		wp_delete_post( $id, true );
		++$deleted;
	}

	// 3. Demo user, unless they now author real (kept) content.
	$users = get_users(
		[
			'meta_key' => '_ogt_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'fields'   => 'ID',
		]
	);
	if ( $users ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		foreach ( $users as $user_id ) {
			if ( count_user_posts( (int) $user_id, [ 'ogt_guide', 'post', 'ogt_itinerary' ] ) > 0 ) {
				continue;
			}
			wp_delete_user( (int) $user_id );
			++$deleted;
		}
	}

	// 4. Demo photos nobody else uses (posts, settings, user photos, article bodies).
	$images = get_posts(
		[
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_ogt_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		]
	);
	foreach ( $images as $image_id ) {
		$id   = (string) (int) $image_id;
		$used = $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type <> 'attachment' AND p.post_status <> 'inherit' AND ( pm.meta_value = %s OR pm.meta_value LIKE %s ) LIMIT 1", $id, '%"' . $id . '"%' ) ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			|| $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->options} WHERE option_name LIKE 'options\_%%' AND ( option_value = %s OR option_value LIKE %s ) LIMIT 1", $id, '%"' . $id . '"%' ) ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			|| $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->usermeta} WHERE meta_key = 'ogt_photo' AND meta_value = %s LIMIT 1", $id ) ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			|| $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->posts} WHERE post_type <> 'revision' AND post_content LIKE %s LIMIT 1", '%wp-image-' . $id . '"%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $used ) {
			continue;
		}
		wp_delete_attachment( (int) $image_id, true );
		++$deleted;
	}

	// 5. Demo destinations with nothing left in them.
	$terms = get_terms(
		[
			'taxonomy'   => 'ogt_destination',
			'hide_empty' => false,
			'meta_key'   => '_ogt_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		]
	);
	foreach ( is_wp_error( $terms ) ? [] : $terms as $term ) {
		$objects = get_objects_in_term( $term->term_id, 'ogt_destination' );
		if ( empty( $objects ) || is_wp_error( $objects ) ) {
			wp_delete_term( $term->term_id, 'ogt_destination' );
			++$deleted;
		}
	}

	delete_option( 'ogtrips_demo_images' );
	delete_option( 'ogtrips_demo_imported' );

	return $deleted;
}
