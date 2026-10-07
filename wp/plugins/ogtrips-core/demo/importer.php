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
 * Finds a demo post by slug and type.
 *
 * @param string $type Post type.
 * @param string $slug Slug.
 * @return int
 */
function ogtrips_core_demo_find( $type, $slug ) {
	$found = get_posts(
		[
			'post_type'      => $type,
			'name'           => $slug,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		]
	);

	return $found ? (int) $found[0] : 0;
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
		}
		if ( ! is_wp_error( $term ) ) {
			$dest_ids[ $slug ] = (int) $term['term_id'];
		}
	}

	// Trip expert (guide author).
	$e    = $data['expert'];
	$user = get_user_by( 'login', $e['login'] );
	if ( ! $user ) {
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
	} else {
		$user_id = $user->ID;
	}
	$user_id = is_wp_error( $user_id ) ? 1 : (int) $user_id;
	update_user_meta( $user_id, '_ogt_demo', 1 );
	update_field( 'ogt_photo', $img( $e['photo'] ), 'user_' . $user_id );
	foreach ( [ 'byline_role', 'specialty', 'reply_time' ] as $key ) {
		update_field( $key, $e[ $key ], 'user_' . $user_id );
	}

	// Trips.
	$trip_ids = [];
	foreach ( $data['trips'] as $trip ) {
		$log( 'Trip: ' . $trip['title'] );
		$id = ogtrips_core_demo_find( 'ogt_itinerary', $trip['slug'] );
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
		if ( isset( $dest_ids[ $trip['dest'] ] ) ) {
			wp_set_object_terms( $id, [ $dest_ids[ $trip['dest'] ] ], 'ogt_destination' );
		}
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
		$f['expert'] = $user_id;
		foreach ( $f as $name => $value ) {
			update_field( $name, $value, $id );
		}
	}
	$counts['trips'] = count( $trip_ids );

	// Reviews.
	foreach ( $data['reviews'] as $i => $r ) {
		$slug = 'demo-review-' . ( $i + 1 );
		$id   = wp_insert_post(
			[
				'ID'          => ogtrips_core_demo_find( 'ogt_review', $slug ),
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
	}
	$counts['reviews'] = count( $data['reviews'] );

	// Moments.
	foreach ( $data['moments'] as $i => $m ) {
		$slug = 'demo-moment-' . ( $i + 1 );
		$id   = wp_insert_post(
			[
				'ID'          => ogtrips_core_demo_find( 'ogt_moment', $slug ),
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
	}
	$counts['moments'] = count( $data['moments'] );

	// Tour guides.
	foreach ( $data['guides'] as $g ) {
		$log( 'Guide: ' . $g['title'] );
		$content = 'bali' === $g['blocks']
			? ogtrips_core_demo_bali_blocks( $img( 'ubud' ), $trip_ids['bali-bliss'] ?? 0 )
			: ogtrips_core_demo_short_blocks( $g['excerpt'] );
		$id      = wp_insert_post(
			[
				'ID'           => ogtrips_core_demo_find( 'ogt_guide', $g['slug'] ),
				'post_type'    => 'ogt_guide',
				'post_status'  => 'publish',
				'post_name'    => $g['slug'],
				'post_title'   => $g['title'],
				'post_excerpt' => $g['excerpt'],
				'post_content' => wp_slash( $content ), // wp_insert_post() unslashes.
				'post_author'  => $user_id,
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
		update_field( $name, $value, 'option' );
	}
	$settings                  = $data['settings'];
	$settings['enquiry_email'] = (string) get_option( 'admin_email' );
	foreach ( $settings as $name => $value ) {
		update_field( $name, $value, 'option' );
	}

	// Static front page + "Blog" posts page (structural, not demo-tagged), only if not set up yet.
	if ( 'page' !== get_option( 'show_on_front' ) || ! get_option( 'page_on_front' ) ) {
		$front = ogtrips_core_demo_find( 'page', 'home' );
		$front = $front ? $front : wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Home', 'post_name' => 'home' ] );
		$blog  = ogtrips_core_demo_find( 'page', 'blog' );
		$blog  = $blog ? $blog : wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Blog', 'post_name' => 'blog' ] );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $front );
		update_option( 'page_for_posts', (int) $blog );
	}

	update_option( 'ogtrips_demo_imported', time(), false );

	return $counts;
}

/**
 * Bali guide body as core blocks (+ Trip CTA block).
 *
 * @param int $image_id Inline image.
 * @param int $trip_id  Trip for the CTA.
 * @return string
 */
function ogtrips_core_demo_bali_blocks( $image_id, $trip_id ) {
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
				'heading'    => 'Bali Bliss, fully planned',
				'_heading'   => 'field_ogt_trip_cta_heading',
				'text'       => 'Everything in this guide, done for you — stays, drivers, tickets and a trip captain on call.',
				'_text'      => 'field_ogt_trip_cta_text',
			],
			'mode' => 'preview',
		],
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);

	return $p( "Bali is easy to love and surprisingly easy to get wrong. Traffic is slow, every area feels completely different, and the \"must-see\" spots are crowded by 9 AM. This guide covers everything our trip captains tell travellers before they fly — so your first trip feels like your fifth." )
		. $h2( 'Best time to visit' )
		. $p( 'The dry season from <strong>April to October</strong> brings sunny days, low humidity and calm seas for island hopping. July and August are peak months, so book stays early. November to March is the wet season — short afternoon showers, greener landscapes and lower prices.' )
		. $h2( 'Where to stay' )
		. $p( 'Most first-timers split their week between the cultural heart and the coast.' )
		. $ul( [ '<strong>Ubud:</strong> rice terraces, temples, yoga &amp; cafés. 3 nights.', '<strong>Seminyak:</strong> beach clubs, boutiques &amp; sunsets. 2 nights.', '<strong>Uluwatu:</strong> cliff views, surf &amp; hidden coves. 2 nights.' ] )
		. "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>\"Do Ubud first, beaches last. Ending your trip by the ocean is the best cure for post-holiday blues.\"</p>\n<!-- /wp:paragraph --></blockquote>\n<!-- /wp:quote -->\n\n"
		. $h2( 'Getting around' )
		. $ul( [ '<strong>Private driver:</strong> ₹3,000–3,500 a day — the most comfortable way to see the island.', '<strong>Ride apps:</strong> Grab and Gojek work in most areas, though some zones restrict pick-ups.', "<strong>Scooter:</strong> Cheap and fun, but only if you're an experienced rider with an international licence." ] )
		. ( $image_id ? "<!-- wp:image {\"id\":" . (int) $image_id . ",\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"" . esc_url( $url ) . "\" alt=\"\" class=\"wp-image-" . (int) $image_id . "\"/><figcaption class=\"wp-element-caption\">Hire a driver for temple days — distances look short but take hours.</figcaption></figure>\n<!-- /wp:image -->\n\n" : '' )
		. $h2( 'Budget breakdown' )
		. "<!-- wp:table -->\n<figure class=\"wp-block-table\"><table><thead><tr><th>Per day</th><th>Budget</th><th>Mid-range</th><th>Luxury</th></tr></thead><tbody><tr><td>Stay</td><td>₹1,500</td><td>₹5,000</td><td>₹20,000+</td></tr><tr><td>Food</td><td>₹800</td><td>₹2,000</td><td>₹5,000+</td></tr><tr><td>Transport</td><td>₹500</td><td>₹3,000</td><td>₹5,000</td></tr><tr><td>Activities</td><td>₹1,000</td><td>₹2,500</td><td>₹6,000+</td></tr></tbody></table></figure>\n<!-- /wp:table -->\n\n"
		. ( $trip_id ? '<!-- wp:ogtrips/trip-cta ' . $cta . " /-->\n\n" : '' )
		. $h2( 'Visa &amp; entry' )
		. $p( 'Indian passport holders can get a visa on arrival or apply for an e-VOA online before flying. A small tourist levy is also payable on arrival. Rules change often — always check the official immigration website before you travel.' )
		. $h2( 'What to pack' )
		. $ul( [ 'Light cotton clothes and a sarong (needed for temple visits)', 'Reef-safe sunscreen and insect repellent', 'Sturdy shoes for the Mount Batur trek', 'Universal adapter — Bali uses Type C/F sockets' ] );
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
 * Deletes everything tagged as demo content (posts, images, the demo expert user).
 *
 * @return int Number of items deleted.
 */
function ogtrips_core_remove_demo() {
	$ids = get_posts(
		[
			'post_type'      => [ 'ogt_itinerary', 'ogt_guide', 'ogt_review', 'ogt_moment', 'post', 'attachment' ],
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_ogt_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		]
	);

	foreach ( $ids as $id ) {
		if ( 'attachment' === get_post_type( $id ) ) {
			wp_delete_attachment( $id, true );
		} else {
			wp_delete_post( $id, true );
		}
	}

	$users = get_users(
		[
			'meta_key' => '_ogt_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'fields'   => 'ID',
		]
	);
	if ( $users ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		foreach ( $users as $user_id ) {
			wp_delete_user( (int) $user_id, get_current_user_id() ? get_current_user_id() : 1 );
		}
	}

	delete_option( 'ogtrips_demo_images' );
	delete_option( 'ogtrips_demo_imported' );

	return count( $ids ) + count( $users );
}
