import path from 'node:path';
import { test as base, request as pwRequest, type APIRequestContext, type Page } from '@playwright/test';
import {
	QA,
	addRow,
	adminMenu,
	blockPublish,
	blockUpdate,
	canvas,
	classicSave,
	enquiryBubble,
	expect,
	field,
	modalConfirm,
	modalPick,
	modalUpload,
	openBlockEditor,
	pickDate,
	rows,
	saveLogin,
	scfTab,
	select2Pick,
	setSwitch,
	sub,
	trackGravatar,
	trashAndEmpty,
	visibleModal,
	wpCli,
	wpPhp,
} from './utils/wp';

/*
 * Phase 02 acceptance (spec .claude/specs/02-content-model-admin.md, criteria 1–10).
 * AC11 (setup re-run, lint, debug.log, full run) is checked outside this file.
 *
 * - Runs on the desktop project only, tests in order in one worker (they share site state).
 * - One login per role per worker (storageState); every page records Gravatar requests (AC9).
 * - Everything created is prefixed "[QA]" / "qa-" and removed by the `qa` fixture teardown.
 */

type QaData = { userId: number; termId: number; tripId: number; tripSlug: string; tripUrl: string };
type TestFx = { gravatar: string[]; merchant: Page; admin: Page; anon: APIRequestContext };
type WorkerFx = { merchantState: string; adminState: string; qa: QaData; baseUrl: string };

const test = base.extend<TestFx, WorkerFx>( {
	baseUrl: [ async ( {}, use, info ) => use( String( info.project.use.baseURL ?? 'http://localhost:8888' ) ), { scope: 'worker' } ],

	merchantState: [
		async ( { browser, baseUrl }, use, info ) => {
			const file = path.join( info.project.outputDir, `.auth/merchant-${ info.workerIndex }.json` );
			await saveLogin( browser, 'merchant', file, baseUrl );
			await use( file );
		},
		{ scope: 'worker', timeout: 120_000 },
	],

	adminState: [
		async ( { browser, baseUrl }, use, info ) => {
			const file = path.join( info.project.outputDir, `.auth/admin-${ info.workerIndex }.json` );
			await saveLogin( browser, 'admin', file, baseUrl );
			await use( file );
		},
		{ scope: 'worker', timeout: 120_000 },
	],

	// Expert user (Author), a destination and one published "fixture" trip used by Trip CTA / reviews / URLs.
	qa: [
		async ( {}, use ) => {
			const data = wpPhp<QaData>( `
				require_once ABSPATH . 'wp-admin/includes/user.php';
				$user = get_user_by( 'login', 'qa-expert' );
				$uid  = $user ? $user->ID : wp_insert_user( [ 'user_login' => 'qa-expert', 'user_pass' => wp_generate_password(), 'user_email' => 'qa-expert@ogtrips.local', 'display_name' => '${ QA } Neha Expert', 'role' => 'author' ] );
				$term = term_exists( 'qa-destination', 'ogt_destination' );
				if ( ! $term ) { $term = wp_insert_term( '${ QA } Bali', 'ogt_destination', [ 'slug' => 'qa-destination' ] ); }
				$tid  = (int) $term['term_id'];
				$trip = get_page_by_path( 'qa-fixture-trip', OBJECT, 'ogt_itinerary' );
				if ( ! $trip ) {
					$pid = wp_insert_post( [ 'post_type' => 'ogt_itinerary', 'post_status' => 'publish', 'post_title' => '${ QA } Fixture trip', 'post_name' => 'qa-fixture-trip', 'post_author' => 1 ] );
					update_field( 'duration_days', 5, $pid );
					update_field( 'price_from', 39999, $pid );
					wp_set_object_terms( $pid, [ $tid ], 'ogt_destination' );
					$trip = get_post( $pid );
				}
				return [ 'userId' => (int) $uid, 'termId' => $tid, 'tripId' => (int) $trip->ID, 'tripSlug' => $trip->post_name, 'tripUrl' => get_permalink( $trip ) ];
			` );
			await use( data );
			// Teardown: remove everything the admin tests created (also runs after a failure).
			wpPhp( `
				global $wpdb;
				require_once ABSPATH . 'wp-admin/includes/user.php';
				$types = "'ogt_itinerary','ogt_guide','post','page','ogt_review','ogt_moment','ogt_enquiry'";
				$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ($types) AND ( post_title LIKE '[QA]%' OR post_status = 'auto-draft' )" );
				foreach ( $ids as $id ) { wp_delete_post( (int) $id, true ); }
				$att = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_title LIKE 'qa-photo%'" );
				foreach ( $att as $id ) { wp_delete_attachment( (int) $id, true ); }
				$user = get_user_by( 'login', 'qa-expert' );
				if ( $user ) { wp_delete_user( $user->ID, 1 ); }
				$term = get_term_by( 'slug', 'qa-destination', 'ogt_destination' );
				if ( $term ) { wp_delete_term( $term->term_id, 'ogt_destination' ); }
				delete_transient( 'ogtrips_core_new_enquiries' );
				return [ 'posts' => count( $ids ), 'attachments' => count( $att ) ];
			` );
		},
		{ scope: 'worker', timeout: 240_000 },
	],

	gravatar: async ( {}, use ) => {
		const sink: string[] = [];
		await use( sink );
		// AC9 — checked for every page every test opened.
		expect( sink, 'requests to gravatar.com' ).toEqual( [] );
	},

	merchant: async ( { browser, merchantState, gravatar, baseUrl }, use ) => {
		const context = await browser.newContext( { storageState: merchantState, baseURL: baseUrl, viewport: { width: 1440, height: 900 } } );
		const page = await context.newPage();
		trackGravatar( page, gravatar );
		await use( page );
		await context.close();
	},

	admin: async ( { browser, adminState, gravatar, baseUrl }, use ) => {
		const context = await browser.newContext( { storageState: adminState, baseURL: baseUrl, viewport: { width: 1440, height: 900 } } );
		const page = await context.newPage();
		trackGravatar( page, gravatar );
		await use( page );
		await context.close();
	},

	anon: async ( { baseUrl }, use ) => {
		const ctx = await pwRequest.newContext( { baseURL: baseUrl } );
		await use( ctx );
		await ctx.dispose();
	},
} );

const FATAL = /critical error on this website|Fatal error|Parse error|Uncaught (Error|TypeError)/i;
const MERCHANT_MENU = [ 'Dashboard', 'Trips', 'Tour Guides', 'Blog', 'Reviews', 'Moments', 'Enquiries', 'Homepage', 'Site Settings', 'Media', 'Profile' ];
const ARTICLE_BLOCK_LABELS = [ 'Heading', 'Image', 'List', 'Paragraph', 'Quote', 'Separator', 'Table', 'Trip CTA' ];
const ARTICLE_BLOCKS = [ 'core/heading', 'core/image', 'core/list', 'core/paragraph', 'core/quote', 'core/separator', 'core/table', 'ogtrips/trip-cta' ];
const VISIBLE_NOTICES = '#wpbody-content .notice, #wpbody-content .update-nag, #wpbody-content div.error, #wpbody-content div.updated, #wpbody-content .yoast-notification, #wpbody-content .yoast-alert';
const INSERTER_TOGGLE = '.editor-document-tools__inserter-toggle';
const INSERTER = '.block-editor-inserter__menu, .editor-inserter-sidebar';

/** Inserts a block through the global inserter (search + click), then closes the inserter. */
async function insertBlock( page: Page, label: string ) {
	const toggle = page.locator( INSERTER_TOGGLE );
	if ( ( await toggle.getAttribute( 'aria-pressed' ) ) !== 'true' ) await toggle.click();
	const inserter = page.locator( INSERTER ).first();
	await inserter.locator( 'input[type="search"]' ).fill( label );
	await inserter.locator( '.block-editor-block-types-list__item', { hasText: new RegExp( '^\\s*' + label + '\\s*$' ) } ).first().click();
	if ( ( await toggle.getAttribute( 'aria-pressed' ) ) === 'true' ) await toggle.click();
}

/** Block names the editor lets the current user insert at the root. */
async function insertableBlocks( page: Page ): Promise<string[]> {
	return page.evaluate( () => {
		const wp = ( window as any ).wp;
		return wp.blocks.getBlockTypes().filter( ( b: any ) => wp.data.select( 'core/block-editor' ).canInsertBlockType( b.name ) ).map( ( b: any ) => b.name ).sort();
	} );
}

/** Opens the document tab of the editor sidebar and returns the Article details input for `name`. */
async function articleField( page: Page, name: string ) {
	const settingsToggle = page.locator( '.editor-header button[aria-label="Settings"]' ).first();
	if ( ( await settingsToggle.getAttribute( 'aria-pressed' ) ) !== 'true' ) await settingsToggle.click();
	const docTab = page.locator( '.editor-sidebar__panel-tabs [role="tab"]' ).first();
	if ( await docTab.isVisible() ) await docTab.click();
	const input = page.locator( `#acf-group_ogt_article .acf-field[data-name="${ name }"] input` );
	if ( ! ( await input.isVisible() ) ) await page.locator( '#acf-group_ogt_article .handlediv, #acf-group_ogt_article .hndle' ).first().click();
	return input;
}

test.describe( 'phase 02 admin', () => {
	// In order, in one worker (shared site state) — a failure does not skip the remaining tests.
	test.describe.configure( { mode: 'default' } );
	test.use( { actionTimeout: 60_000, navigationTimeout: 90_000 } );

	test.beforeEach( ( {}, info ) => {
		test.skip( info.project.name !== 'desktop-1440', 'admin tests run once, on the desktop project' );
		test.setTimeout( 600_000 );
	} );

	/* ------------------------------------------------------------ AC1 */

	test( 'AC1 merchant sees exactly the content menu, no clutter or notices', async ( { merchant: page, admin } ) => {
		await page.goto( '/wp-admin/' );
		expect.soft( await adminMenu( page ) ).toEqual( MERCHANT_MENU );

		for ( const href of [ 'edit-comments.php', 'tools.php', 'edit.php?post_type=page', 'themes.php', 'plugins.php', 'options-general.php', 'edit.php?post_type=acf-field-group', 'users.php' ] ) {
			await expect.soft( page.locator( `#adminmenu a[href="${ href }"]` ), href ).toHaveCount( 0 );
		}
		await expect.soft( page.locator( '#adminmenu a[href*="wpseo"]' ), 'no Yoast links in the merchant menu' ).toHaveCount( 0, { timeout: 5_000 } );
		for ( const node of [ 'updates', 'comments', 'wpseo-menu', 'wp-logo', 'new-page', 'customize' ] ) {
			await expect.soft( page.locator( `#wp-admin-bar-${ node }` ), `admin bar ${ node }` ).toHaveCount( 0 );
		}
		await expect.soft( page.locator( '#ogtrips_dashboard' ) ).toBeVisible();

		// No plugin notices / nags on the dashboard and the main content screens.
		for ( const url of [ '/wp-admin/', '/wp-admin/edit.php?post_type=ogt_itinerary', '/wp-admin/post-new.php?post_type=ogt_itinerary', '/wp-admin/admin.php?page=ogtrips-homepage', '/wp-admin/upload.php' ] ) {
			if ( url !== '/wp-admin/' ) await page.goto( url );
			const notices = await page.locator( VISIBLE_NOTICES ).filter( { visible: true } ).allInnerTexts();
			expect.soft( notices, `notices on ${ url }` ).toEqual( [] );
		}

		// Administrator keeps the same order, then the developer menus.
		await admin.goto( '/wp-admin/' );
		const adminItems = await adminMenu( admin );
		expect.soft( adminItems.slice( 0, 10 ) ).toEqual( MERCHANT_MENU.slice( 0, 10 ) );
		for ( const label of [ /^Pages$/, /^Appearance$/, /^Plugins/, /^Users$/, /^Tools$/, /^Settings$/, /^Yoast SEO/, /^SCF$|Custom Fields/ ] ) {
			expect.soft( adminItems.some( ( i ) => label.test( i ) ), `admin menu has ${ label }` ).toBe( true );
		}
		expect.soft( adminItems ).not.toContain( 'Comments' );
	} );

	test( 'AC1 merchant is blocked by URL from Pages and Tools (403); admin is not', async ( { merchant: page, admin } ) => {
		const pageId = wpPhp<number>( `return wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => '${ QA } Blocked page', 'post_author' => 1 ] );` );
		try {
			const blocked = [
				'/wp-admin/edit.php?post_type=page',
				'/wp-admin/post-new.php?post_type=page',
				`/wp-admin/post.php?post=${ pageId }&action=edit`,
				'/wp-admin/tools.php',
				'/wp-admin/import.php',
				'/wp-admin/export.php',
			];
			for ( const url of blocked ) {
				const r = await page.goto( url );
				expect.soft( r?.status(), `merchant ${ url }` ).toBe( 403 );
				// tools.php is open to Editors in core, so our wp_die message shows; import/export are already refused by core (same 403).
				await expect.soft( page.locator( 'body#error-page' ), `merchant ${ url }` ).toContainText( /only for the site administrator|not allowed to access this page/ );
				await expect.soft( page.locator( '#adminmenu' ), `merchant ${ url } renders no admin screen` ).toHaveCount( 0 );
			}
			// Content screens of the merchant are not caught by the block (posts, trips, a post by ID).
			for ( const url of [ '/wp-admin/edit.php', '/wp-admin/edit.php?post_type=ogt_itinerary', '/wp-admin/post-new.php', '/wp-admin/admin.php?page=ogtrips-site-settings' ] ) {
				const r = await page.goto( url );
				expect.soft( r?.status(), `merchant ${ url }` ).toBe( 200 );
			}
			for ( const url of blocked ) {
				const r = await admin.goto( url );
				expect.soft( r?.status(), `admin ${ url }` ).toBe( 200 );
				await expect.soft( admin.locator( 'body#error-page' ), `admin ${ url }` ).toHaveCount( 0 ); // page editor is full-screen: no #adminmenu
			}
		} finally {
			wpPhp( `wp_delete_post( ${ pageId }, true ); return true;` );
		}
	} );

	test( 'AC1/spec every SCF field has a one-line instruction; review rating labels; rewrite version', async () => {
		const d = wpPhp<{ missing: string[]; multiline: string[]; total: number; rating: Record<string, string>; rewrite: string; version: string; blockGap: unknown }>( `
			$missing = []; $multiline = []; $total = 0;
			$walk = function ( $fields, $path ) use ( &$walk, &$missing, &$multiline, &$total ) {
				foreach ( $fields as $f ) {
					$p = $path . '/' . ( $f['name'] ?: $f['key'] );
					if ( ! in_array( $f['type'], [ 'tab', 'message', 'accordion' ], true ) ) {
						$total++;
						$i = trim( (string) ( $f['instructions'] ?? '' ) );
						if ( '' === $i ) { $missing[] = $p; }
						elseif ( preg_match( '/\\R/', $i ) ) { $multiline[] = $p; }
					}
					if ( ! empty( $f['sub_fields'] ) ) { $walk( $f['sub_fields'], $p ); }
				}
			};
			foreach ( acf_get_field_groups() as $g ) { $walk( acf_get_fields( $g['key'] ), $g['key'] ); }
			$rating = acf_get_field( 'field_ogt_review_rating' ) ?: ( function () { foreach ( acf_get_fields( 'group_ogt_review' ) as $f ) { if ( 'rating' === $f['name'] ) return $f; } return []; } )();
			return [ 'missing' => $missing, 'multiline' => $multiline, 'total' => $total, 'rating' => (object) ( $rating['choices'] ?? [] ),
				'rewrite' => get_option( 'ogtrips_core_rewrite_version' ), 'version' => OGTRIPS_CORE_VERSION, 'blockGap' => wp_get_global_settings( [ 'spacing', 'blockGap' ] ) ];
		` );
		test.info().annotations.push( { type: 'fields checked', description: String( d.total ) }, { type: 'rating choices (in order)', description: JSON.stringify( d.rating ) } );
		expect( d.total ).toBeGreaterThan( 50 );
		expect.soft( d.missing, 'fields without instructions' ).toEqual( [] );
		expect.soft( d.multiline, 'instructions longer than one line' ).toEqual( [] );
		expect.soft( d.rating ).toEqual( { '1': '1 star', '2': '2 stars', '3': '3 stars', '4': '4 stars', '5': '5 stars' } );
		expect.soft( d.rewrite, 'ogtrips_core_rewrite_version = plugin version' ).toBe( d.version );
		expect.soft( d.blockGap ?? null, 'theme.json lock leaves blockGap unset' ).toBeNull();
	} );

	/* ------------------------------------------------------------ AC2 */

	test( 'AC2 merchant adds, edits and deletes a Trip through every tab', async ( { merchant: page, qa, anon } ) => {
		const G = 'group_ogt_itinerary';
		const title = `${ QA } Bali Bliss e2e trip`;

		await page.goto( '/wp-admin/post-new.php?post_type=ogt_itinerary' );
		await expect( page.locator( 'body.block-editor-page' ) ).toHaveCount( 0 );
		await page.locator( '#title' ).fill( title );
		await page.locator( '#content-html' ).click();
		await page.locator( '#content' ).fill( 'QA overview: five days of temples and rice terraces.' );
		await page.locator( '#ogt_trip_typechecklist label', { hasText: 'Honeymoon' } ).locator( 'input' ).check();
		await page.locator( '#ogt_destinationchecklist label', { hasText: `${ QA } Bali` } ).locator( 'input' ).check();

		// Hero (featured) image — real upload through the media modal.
		await page.locator( '#set-post-thumbnail' ).click();
		await modalUpload( page, 'qa-photo-1.png' );
		await modalConfirm( page );
		await expect( page.locator( '#postimagediv img' ) ).toBeVisible();

		// Overview
		await scfTab( page, G, 'Overview' );
		await field( page, G, 'title_display' ).locator( 'input' ).fill( `${ QA } Bali Bliss: temples *&* rice terraces` );
		await field( page, G, 'location_label' ).locator( 'input' ).fill( 'Bali, Indonesia' );
		await field( page, G, 'badge' ).locator( 'input' ).fill( '#1 Bestseller' );
		await field( page, G, 'badge_style' ).locator( 'select' ).selectOption( 'coral' );
		await setSwitch( field( page, G, 'is_bestseller' ), true );
		const stop = await addRow( field( page, G, 'route_stops' ) );
		await sub( stop, 'label' ).locator( 'input' ).fill( 'Ubud' );
		await sub( stop, 'days' ).locator( 'input' ).fill( 'Day 1–3' );
		const hl = await addRow( field( page, G, 'highlights' ) );
		await sub( hl, 'icon' ).locator( 'select' ).selectOption( 'sunrise' );
		await sub( hl, 'text' ).locator( 'input' ).fill( 'Sunrise trek up Mount Batur' );

		// Facts
		await scfTab( page, G, 'Facts' );
		await field( page, G, 'duration_days' ).locator( 'input' ).fill( '6' );
		await field( page, G, 'duration_nights' ).locator( 'input' ).fill( '5' );
		await field( page, G, 'pace' ).locator( 'select' ).selectOption( 'easy-moderate' );
		await field( page, G, 'rating' ).locator( 'input' ).fill( '4.8' );

		// Price & dates — departure date via the date picker
		await scfTab( page, G, 'Price & dates' );
		await field( page, G, 'price_from' ).locator( 'input' ).fill( '45999' );
		await field( page, G, 'offer_label' ).locator( 'input' ).fill( 'Early-bird' );
		const dep = await addRow( field( page, G, 'departures' ) );
		const depYmd = await pickDate( page, sub( dep, 'date' ) );
		await sub( dep, 'seats_left' ).locator( 'input' ).fill( '4' );

		// Day by day — row with a photo from the library
		await scfTab( page, G, 'Day by day' );
		const day = await addRow( field( page, G, 'days' ) );
		await sub( day, 'title' ).locator( 'input' ).fill( 'Arrive in Ubud' );
		await sub( day, 'location' ).locator( 'input' ).fill( 'Denpasar → Ubud' );
		await sub( day, 'image' ).locator( 'a[data-name="add"]' ).click();
		await modalPick( page, 'qa-photo-1' );
		await modalConfirm( page );
		await expect( sub( day, 'image' ).locator( 'img' ) ).toBeVisible();

		// Stays
		await scfTab( page, G, 'Stays' );
		const stay = await addRow( field( page, G, 'stays' ) );
		await sub( stay, 'name' ).locator( 'input' ).fill( `${ QA } Villa Kayu` );
		await sub( stay, 'nights' ).locator( 'input' ).fill( '2' );

		// Inclusions
		await scfTab( page, G, 'Inclusions' );
		await field( page, G, 'incl_icons' ).locator( 'input[value="bed-double"]' ).check();
		const inc = await addRow( field( page, G, 'included' ) );
		await sub( inc, 'text' ).locator( 'input' ).fill( 'Breakfast daily' );

		// Gallery — one new upload + one existing image
		await scfTab( page, G, 'Gallery' );
		await field( page, G, 'gallery' ).locator( '.acf-gallery-add' ).click();
		await modalUpload( page, 'qa-photo-2.png' );
		await visibleModal( page ).locator( 'li.attachment[aria-label^="qa-photo-1"]' ).first().click();
		await modalConfirm( page );
		await expect( field( page, G, 'gallery' ).locator( '.acf-gallery-attachment' ) ).toHaveCount( 2 );

		// FAQ — answer is a (delayed) TinyMCE editor
		await scfTab( page, G, 'FAQ' );
		const faq = await addRow( field( page, G, 'faqs' ) );
		await sub( faq, 'question' ).locator( 'input' ).fill( 'Do I need a visa?' );
		await sub( faq, 'answer' ).locator( '.acf-editor-wrap' ).click();
		await sub( faq, 'answer' ).frameLocator( 'iframe' ).locator( 'body' ).click();
		await page.keyboard.type( 'Indians get a visa on arrival.' );

		// Expert (SCF labels users by login + first/last name, not display name)
		await scfTab( page, G, 'Expert' );
		await select2Pick( page, field( page, G, 'expert' ), 'Neha', /qa-expert/ );

		const id = await classicSave( page, /published\./ );

		// Persisted (database view).
		const saved = wpPhp( `
			$id = ${ id };
			return [ 'title' => get_the_title( $id ), 'status' => get_post_status( $id ), 'thumb' => (int) get_post_thumbnail_id( $id ),
				'types' => wp_get_object_terms( $id, 'ogt_trip_type', [ 'fields' => 'slugs' ] ), 'dest' => wp_get_object_terms( $id, 'ogt_destination', [ 'fields' => 'ids' ] ),
				'content' => get_post_field( 'post_content', $id ), 'f' => get_fields( $id ), 'url' => get_permalink( $id ) ];
		` );
		expect( saved.title ).toBe( title );
		expect( saved.status ).toBe( 'publish' );
		expect( saved.thumb ).toBeGreaterThan( 0 );
		expect( saved.types ).toEqual( [ 'honeymoon' ] );
		expect( saved.dest ).toEqual( [ qa.termId ] );
		expect( saved.content ).toContain( 'QA overview' );
		const f = saved.f;
		expect( f.title_display ).toBe( `${ QA } Bali Bliss: temples *&* rice terraces` );
		expect( f.badge_style ).toBe( 'coral' );
		expect( f.is_bestseller ).toBe( true );
		expect( f.route_stops ).toEqual( [ { label: 'Ubud', days: 'Day 1–3' } ] );
		expect( f.highlights ).toEqual( [ { icon: 'sunrise', text: 'Sunrise trek up Mount Batur' } ] );
		expect( Number( f.duration_days ) ).toBe( 6 );
		expect( f.pace ).toBe( 'easy-moderate' );
		expect( Number( f.rating ) ).toBe( 4.8 );
		expect( Number( f.price_from ) ).toBe( 45999 );
		expect( f.departures[ 0 ].date ).toBe( `${ depYmd.slice( 0, 4 ) }-${ depYmd.slice( 4, 6 ) }-${ depYmd.slice( 6 ) }` );
		expect( Number( f.departures[ 0 ].seats_left ) ).toBe( 4 );
		expect( f.days[ 0 ].title ).toBe( 'Arrive in Ubud' );
		expect( Number( f.days[ 0 ].image ) ).toBeGreaterThan( 0 );
		expect( f.stays[ 0 ].name ).toBe( `${ QA } Villa Kayu` );
		expect( f.incl_icons ).toEqual( [ 'bed-double' ] );
		expect( f.included ).toEqual( [ { text: 'Breakfast daily' } ] );
		expect( f.gallery ).toHaveLength( 2 );
		expect( f.faqs[ 0 ].question ).toBe( 'Do I need a visa?' );
		expect( f.faqs[ 0 ].answer ).toContain( 'Indians get a visa on arrival.' );
		expect( Number( f.expert ) ).toBe( qa.userId );

		// Reloads correctly in the form (UI view).
		await page.reload();
		await expect( page.locator( '#title' ) ).toHaveValue( title );
		await scfTab( page, G, 'Overview' );
		await expect( sub( rows( field( page, G, 'route_stops' ) ).first(), 'label' ).locator( 'input' ) ).toHaveValue( 'Ubud' );
		await expect( field( page, G, 'badge_style' ).locator( 'select' ) ).toHaveValue( 'coral' );
		await scfTab( page, G, 'Price & dates' );
		await expect( sub( rows( field( page, G, 'departures' ) ).first(), 'date' ).locator( 'input[type="hidden"]' ) ).toHaveValue( depYmd );
		await scfTab( page, G, 'Gallery' );
		await expect( field( page, G, 'gallery' ).locator( '.acf-gallery-attachment' ) ).toHaveCount( 2 );
		await scfTab( page, G, 'Expert' );
		await expect( field( page, G, 'expert' ).locator( '.select2-selection__rendered' ) ).toContainText( 'qa-expert' );
		await expect( page.locator( '#postimagediv img' ) ).toBeVisible();

		// Public URL while published.
		expect( saved.url ).toMatch( /\/trips\/[^/]+\/$/ );
		expect( ( await anon.get( saved.url ) ).status() ).toBe( 200 );

		// Edit one value.
		await scfTab( page, G, 'Price & dates' );
		await field( page, G, 'price_from' ).locator( 'input' ).fill( '47999' );
		await classicSave( page, /updated\./ );
		await page.reload();
		await scfTab( page, G, 'Price & dates' );
		await expect( field( page, G, 'price_from' ).locator( 'input' ) ).toHaveValue( '47999' );

		// Delete (bin + empty bin).
		await trashAndEmpty( page, 'ogt_itinerary', id );
		expect( wpPhp( `return get_post( ${ id } ) ? 'exists' : 'gone';` ) ).toBe( 'gone' );
	} );

	/* ------------------------------------------------------------ AC3 */

	test( 'AC3 Tour Guide: restricted block editor, Trip CTA, add/edit/delete', async ( { merchant: page, qa, anon } ) => {
		const title = `${ QA } Bali travel guide e2e`;
		await openBlockEditor( page, '/wp-admin/post-new.php?post_type=ogt_guide' );
		const c = await canvas( page );

		// Inserter offers only the allowed blocks (it renders categories progressively: poll until stable).
		await page.locator( INSERTER_TOGGLE ).click();
		const inserter = page.locator( INSERTER ).first();
		await expect
			.poll( async () => [ ...new Set( ( await inserter.locator( '.block-editor-block-types-list__item-title' ).allInnerTexts() ).map( ( t ) => t.trim() ) ) ].sort(), { timeout: 30_000 } )
			.toEqual( ARTICLE_BLOCK_LABELS );
		test.info().annotations.push( { type: 'inserter tabs', description: ( await inserter.getByRole( 'tab' ).allInnerTexts() ).join( ', ' ) } );
		expect( await insertableBlocks( page ) ).toEqual( ARTICLE_BLOCKS );
		await page.locator( INSERTER_TOGGLE ).click();

		// Title + paragraph.
		await c.locator( '.editor-post-title__input' ).click();
		await page.keyboard.type( title );
		await page.keyboard.press( 'Enter' );
		await page.keyboard.type( 'QA intro paragraph for the guide.' );

		// Heading: only H2/H3 (WP 7 shows levels as "Transform to variation" radios in the inspector).
		await insertBlock( page, 'Heading' );
		await c.locator( '[data-type="core/heading"]' ).last().click();
		await page.keyboard.type( 'Best time to visit' );
		const settingsToggle = page.locator( '.editor-header button[aria-label="Settings"]' ).first();
		if ( ( await settingsToggle.getAttribute( 'aria-pressed' ) ) !== 'true' ) await settingsToggle.click();
		const blockTab = page.getByRole( 'tab', { name: 'Block', exact: true } );
		if ( await blockTab.isVisible() ) await blockTab.click();
		const inspector = page.locator( '.block-editor-block-inspector' );
		const levels = (
			await inspector
				.getByRole( 'radiogroup', { name: /variation/i } )
				.getByRole( 'radio' )
				.evaluateAll( ( els ) => els.map( ( e ) => e.getAttribute( 'aria-label' ) || e.textContent || '' ) )
		).map( ( l ) => l.replace( /^Transform to /, '' ).trim() );
		expect( levels ).toEqual( [ 'Heading 2', 'Heading 3' ] );
		expect( await page.evaluate( () => ( window as any ).wp.blocks.getBlockType( 'core/heading' ).attributes.levelOptions?.default ) ).toEqual( [ 2, 3 ] );

		// No colour / font-size controls: editor settings, block inspector, Typography menu, rich-text Highlight.
		const settings = await page.evaluate( () => {
			const s = ( window as any ).wp.data.select( 'core/block-editor' ).getSettings();
			return {
				colors: s.colors?.length ?? 0,
				gradients: s.gradients?.length ?? 0,
				fontSizes: s.fontSizes?.length ?? 0,
				customColors: ! s.disableCustomColors,
				customFontSizes: ! s.disableCustomFontSizes,
				customGradients: ! s.disableCustomGradients,
			};
		} );
		expect.soft( settings ).toEqual( { colors: 0, gradients: 0, fontSizes: 0, customColors: false, customFontSizes: false, customGradients: false } );
		for ( const type of [ 'core/heading', 'core/paragraph' ] ) {
			await c.locator( `[data-type="${ type }"]` ).first().click();
			if ( await blockTab.isVisible() ) await blockTab.click();
			await expect( inspector ).toBeVisible();
			await expect.soft( inspector.locator( '.block-editor-panel-color-gradient-settings, .color-block-support-panel, .components-font-size-picker, .block-editor-color-gradient-control' ), type ).toHaveCount( 0 );
			await expect.soft( inspector.getByRole( 'tab', { name: 'Styles' } ), type ).toHaveCount( 0 );
			await expect.soft( inspector.getByText( /^(Color|Colour|Font size)$/ ), type ).toHaveCount( 0 );
			const typo = inspector.getByRole( 'button', { name: 'Typography options' } );
			if ( await typo.count() ) {
				await typo.click();
				const items = await page.locator( '.components-popover [role^="menuitem"]' ).allInnerTexts();
				test.info().annotations.push( { type: `${ type } Typography options menu`, description: items.join( ' | ' ) } );
				expect.soft( items.join( '|' ), `${ type } typography options` ).not.toMatch( /size|font|colou?r/i );
				await page.keyboard.press( 'Escape' );
			}
		}
		await page.mouse.move( 300, 300 );
		await page.mouse.move( 320, 330 );
		const more = page.locator( '.block-editor-block-toolbar button[aria-label="More"]' ).first();
		await expect( more ).toBeVisible();
		await more.click();
		const formats = await page.locator( '.components-popover [role^="menuitem"]' ).allInnerTexts();
		test.info().annotations.push( { type: 'paragraph rich-text formats', description: formats.join( ' | ' ) } );
		expect.soft( formats.join( '|' ) ).not.toMatch( /Highlight/ );
		await page.keyboard.press( 'Escape' );

		// Trip CTA pointing at the fixture trip.
		await insertBlock( page, 'Trip CTA' );
		if ( await blockTab.isVisible() ) await blockTab.click();
		const ctaFields = page.locator( '.block-editor-block-inspector .acf-block-fields, .block-editor-block-inspector .acf-block-panel' ).first();
		await select2Pick( page, ctaFields.locator( '.acf-field[data-name="itinerary"]' ), 'Fixture', /Fixture trip/ );
		await ctaFields.locator( '.acf-field[data-name="heading"] input' ).fill( `${ QA } Bali Bliss, fully planned` );
		// SCF keeps the block's field values in attributes.data (keyed by name or field key).
		await expect
			.poll(
				() =>
					page.evaluate( () =>
						JSON.stringify(
							( window as any ).wp.data
								.select( 'core/block-editor' )
								.getBlocks()
								.find( ( b: any ) => b.name === 'ogtrips/trip-cta' )?.attributes?.data ?? null
						)
					),
				{ timeout: 30_000 }
			)
			.toMatch( new RegExp( `"(itinerary|field_ogt_trip_cta_itinerary)":"?${ qa.tripId }"?.*fully planned|fully planned.*"(itinerary|field_ogt_trip_cta_itinerary)":"?${ qa.tripId }"?` ) );
		// SCF re-validates blocks via a debounced AJAX call; until it returns, the "required field empty"
		// state from insertion blocks saving. Wait for SCF's own validation state to clear.
		await expect
			.poll(
				() =>
					page.evaluate( () => {
						const w = window as any;
						const block = w.wp.data.select( 'core/block-editor' ).getBlocks().find( ( b: any ) => b.name === 'ogtrips/trip-cta' );
						return JSON.stringify( w.acf?.blockInstances?.[ block?.clientId ]?.validation_errors ?? false );
					} ),
				{ timeout: 30_000 }
			)
			.toBe( 'false' );

		// Side fields (Article details) + guide topic.
		await ( await articleField( page, 'glance_best_time' ) ).fill( 'Apr – Oct' );
		const topic = page.getByRole( 'checkbox', { name: 'Travel tips' } );
		if ( ! ( await topic.isVisible() ) ) await page.getByRole( 'button', { name: 'Guide topics' } ).click();
		await topic.check();

		const id = await blockPublish( page );

		const saved = wpPhp( `
			$id = ${ id };
			return [ 'title' => get_the_title( $id ), 'content' => get_post_field( 'post_content', $id ), 'glance' => get_field( 'glance_best_time', $id ),
				'topics' => wp_get_object_terms( $id, 'ogt_guide_topic', [ 'fields' => 'slugs' ] ), 'url' => get_permalink( $id ) ];
		` );
		expect( saved.title ).toBe( title );
		expect( saved.content ).toContain( '<!-- wp:paragraph -->' );
		expect( saved.content ).toContain( '<!-- wp:heading -->' );
		expect( saved.content ).toMatch( new RegExp( `<!-- wp:ogtrips/trip-cta \\{[^]*"(itinerary|field_ogt_trip_cta_itinerary)":"?${ qa.tripId }"?` ) );
		expect( saved.glance ).toBe( 'Apr – Oct' );
		expect( saved.topics ).toEqual( [ 'travel-tips' ] );
		expect( saved.url ).toMatch( /\/travel-guide\/[^/]+\/$/ );

		// Reload: the block and its trip are still there.
		await openBlockEditor( page, `/wp-admin/post.php?post=${ id }&action=edit` );
		const names = await page.evaluate( () => ( window as any ).wp.data.select( 'core/block-editor' ).getBlocks().map( ( b: any ) => b.name ) );
		expect( [ ...names ].sort() ).toEqual( [ 'core/heading', 'core/paragraph', 'ogtrips/trip-cta' ] );

		// Front end renders the CTA linking to the trip.
		const front = await anon.get( saved.url );
		expect( front.status() ).toBe( 200 );
		const html = await front.text();
		expect( html ).toContain( 'class="trip-cta"' );
		expect( html ).toContain( `href="${ qa.tripUrl }"` );

		// Edit the title, then delete.
		await ( await canvas( page ) ).locator( '.editor-post-title__input' ).click();
		await page.keyboard.press( 'End' );
		await page.keyboard.type( ' (edited)' );
		await blockUpdate( page );
		expect( wpPhp( `return get_the_title( ${ id } );` ) ).toBe( `${ title } (edited)` );
		await trashAndEmpty( page, 'ogt_guide', id );
		expect( wpPhp( `return get_post( ${ id } ) ? 'exists' : 'gone';` ) ).toBe( 'gone' );
	} );

	test( 'AC3 Review: title auto-set from the reviewer name; add/edit/delete', async ( { merchant: page, qa } ) => {
		const G = 'group_ogt_review';
		await page.goto( '/wp-admin/post-new.php?post_type=ogt_review' );
		await expect( page.locator( 'body.block-editor-page' ) ).toHaveCount( 0 );
		await expect( page.locator( '#title' ) ).toHaveCount( 0 );

		await field( page, G, 'quote' ).locator( 'textarea' ).fill( 'Everything was planned to the minute. Loved it!' );
		await field( page, G, 'reviewer_name' ).locator( 'input' ).fill( `${ QA } Priya Sharma` );
		await field( page, G, 'reviewer_city' ).locator( 'input' ).fill( 'Pune' );
		const travelYmd = await pickDate( page, field( page, G, 'travel_date' ) );
		await field( page, G, 'rating' ).locator( 'select' ).selectOption( '4' );
		await field( page, G, 'source' ).locator( 'select' ).selectOption( 'tripadvisor' );
		await field( page, G, 'avatar' ).locator( 'a[data-name="add"]' ).click();
		await modalUpload( page, 'qa-photo-1.png' );
		await modalConfirm( page );
		await select2Pick( page, field( page, G, 'itinerary' ), 'Fixture', /Fixture trip/ );
		await setSwitch( field( page, G, 'featured' ), true );

		const id = await classicSave( page, /published\./ );
		const saved = wpPhp( `return [ 'title' => get_the_title( ${ id } ), 'f' => get_fields( ${ id } ) ];` );
		expect( saved.title ).toBe( `${ QA } Priya Sharma` );
		expect( saved.f ).toMatchObject( { reviewer_city: 'Pune', rating: '4', source: 'tripadvisor', featured: true } );
		expect( saved.f.travel_date ).toBe( `${ travelYmd.slice( 0, 4 ) }-${ travelYmd.slice( 4, 6 ) }-${ travelYmd.slice( 6 ) }` );
		expect( Number( saved.f.itinerary ) ).toBe( qa.tripId );
		expect( Number( saved.f.avatar ) ).toBeGreaterThan( 0 );

		await page.reload();
		await expect( field( page, G, 'reviewer_name' ).locator( 'input' ) ).toHaveValue( `${ QA } Priya Sharma` );
		await expect( field( page, G, 'source' ).locator( 'select' ) ).toHaveValue( 'tripadvisor' );

		// Edit the name → title follows.
		await field( page, G, 'reviewer_name' ).locator( 'input' ).fill( `${ QA } Priya S.` );
		await classicSave( page, /updated\./ );
		await page.goto( '/wp-admin/edit.php?post_type=ogt_review' );
		await expect( page.locator( `#post-${ id } .row-title` ) ).toHaveText( `${ QA } Priya S.` );

		await trashAndEmpty( page, 'ogt_review', id );
		expect( wpPhp( `return get_post( ${ id } ) ? 'exists' : 'gone';` ) ).toBe( 'gone' );
	} );

	test( 'AC3 Moment: add/edit/delete', async ( { merchant: page } ) => {
		const G = 'group_ogt_moment';
		await page.goto( '/wp-admin/post-new.php?post_type=ogt_moment' );
		await expect( page.locator( 'body.block-editor-page' ) ).toHaveCount( 0 );
		await page.locator( '#title' ).fill( `${ QA } Ubud swing reel` );
		await field( page, G, 'media_type' ).locator( 'input[value="video"]' ).check();
		await field( page, G, 'image' ).locator( 'a[data-name="add"]' ).click();
		await modalUpload( page, 'qa-photo-2.png' );
		await modalConfirm( page );
		await field( page, G, 'duration' ).locator( 'input' ).fill( '0:42' );
		await field( page, G, 'handle' ).locator( 'input' ).fill( '@qa.traveller' );
		await field( page, G, 'tile_size' ).locator( 'select' ).selectOption( 'tall' );
		await field( page, G, 'likes' ).locator( 'input' ).fill( '12.4k' );
		await field( page, G, 'views' ).locator( 'input' ).fill( '98k' );
		await field( page, G, 'link' ).locator( 'input' ).fill( 'https://www.instagram.com/p/QA123/' );

		const id = await classicSave( page, /published\./ );
		const saved = wpPhp( `return get_fields( ${ id } );` );
		expect( saved ).toMatchObject( { media_type: 'video', duration: '0:42', handle: '@qa.traveller', tile_size: 'tall', likes: '12.4k', views: '98k', link: 'https://www.instagram.com/p/QA123/' } );
		expect( Number( saved.image ) ).toBeGreaterThan( 0 );

		await page.reload();
		await expect( field( page, G, 'likes' ).locator( 'input' ) ).toHaveValue( '12.4k' );
		await field( page, G, 'likes' ).locator( 'input' ).fill( '13k' );
		await classicSave( page, /updated\./ );
		await page.reload();
		await expect( field( page, G, 'likes' ).locator( 'input' ) ).toHaveValue( '13k' );

		await trashAndEmpty( page, 'ogt_moment', id );
		expect( wpPhp( `return get_post( ${ id } ) ? 'exists' : 'gone';` ) ).toBe( 'gone' );
	} );

	test( 'AC3 Blog post: block editor, side fields, add/edit/delete', async ( { merchant: page, anon } ) => {
		const title = `${ QA } Blog post e2e`;
		await openBlockEditor( page, '/wp-admin/post-new.php' );
		const c = await canvas( page );
		await c.locator( '.editor-post-title__input' ).click();
		await page.keyboard.type( title );
		await page.keyboard.press( 'Enter' );
		await page.keyboard.type( 'QA blog body.' );
		expect( await insertableBlocks( page ) ).toEqual( ARTICLE_BLOCKS );
		await ( await articleField( page, 'glance_budget' ) ).fill( '₹4,000 / day' );

		const id = await blockPublish( page );
		const saved = wpPhp( `return [ 'title' => get_the_title( ${ id } ), 'budget' => get_field( 'glance_budget', ${ id } ), 'url' => get_permalink( ${ id } ) ];` );
		expect( saved.title ).toBe( title );
		expect( saved.budget ).toBe( '₹4,000 / day' );
		expect( saved.url ).toMatch( /\/blog\/[^/]+\/$/ );
		expect( ( await anon.get( saved.url ) ).status() ).toBe( 200 );

		await openBlockEditor( page, `/wp-admin/post.php?post=${ id }&action=edit` );
		await ( await canvas( page ) ).locator( '.editor-post-title__input' ).click();
		await page.keyboard.press( 'End' );
		await page.keyboard.type( ' (edited)' );
		await blockUpdate( page );
		expect( wpPhp( `return get_the_title( ${ id } );` ) ).toBe( `${ title } (edited)` );

		await trashAndEmpty( page, 'post', id );
		expect( wpPhp( `return get_post( ${ id } ) ? 'exists' : 'gone';` ) ).toBe( 'gone' );
	} );

	test( 'UX classic save notices name the content type (Trip/Review/Moment published.)', async ( { merchant: page } ) => {
		const ids = wpPhp<Record<string, number>>( `
			$out = [];
			foreach ( [ 'ogt_itinerary', 'ogt_review', 'ogt_moment' ] as $t ) { $out[ $t ] = wp_insert_post( [ 'post_type' => $t, 'post_status' => 'publish', 'post_title' => '${ QA } notice ' . $t ] ); }
			return $out;
		` );
		try {
			const expected: Record<string, string> = { ogt_itinerary: 'Trip published.', ogt_review: 'Review published.', ogt_moment: 'Moment published.' };
			for ( const [ type, id ] of Object.entries( ids ) ) {
				await page.goto( `/wp-admin/post.php?post=${ id }&action=edit&message=6` );
				await expect.soft( page.locator( '#message' ), type ).toContainText( expected[ type ], { timeout: 5_000 } );
			}
		} finally {
			wpPhp( `foreach ( [ ${ Object.values( ids ).join( ',' ) } ] as $id ) { wp_delete_post( $id, true ); } return true;` );
		}
	} );

	/* ------------------------------------------------------------ AC4 */

	test( 'AC4 Homepage and Site Settings save and reload', async ( { merchant: page } ) => {
		const snapshot = wpPhp<Record<string, string>>( `
			global $wpdb;
			$rows = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'options\\\\_%' OR option_name LIKE '\\\\_options\\\\_%'", ARRAY_A );
			return (object) wp_list_pluck( $rows, 'option_value', 'option_name' );
		` );
		try {
			// Homepage
			const H = 'group_ogt_homepage';
			await page.goto( '/wp-admin/admin.php?page=ogtrips-homepage' );
			await scfTab( page, H, 'Hero' );
			await field( page, H, 'hero_heading' ).locator( 'input' ).fill( `${ QA } Where to *next*?` );
			await field( page, H, 'hero_subtitle' ).locator( 'textarea' ).fill( 'Handpicked trips, planned to the minute.' );
			await field( page, H, 'hero_cta_primary' ).locator( 'a[data-name="add"]' ).click();
			await expect( page.locator( '#wp-link-wrap' ) ).toBeVisible();
			await page.locator( '#wp-link-url' ).fill( 'http://localhost:8888/trips/' );
			await page.locator( '#wp-link-text' ).fill( 'Explore trips' );
			await page.locator( '#wp-link-submit' ).click();
			await expect( field( page, H, 'hero_cta_primary' ).locator( '.link-title' ) ).toHaveText( 'Explore trips' );
			const place = await addRow( field( page, H, 'hero_places' ) );
			await sub( place, 'image' ).locator( 'a[data-name="add"]' ).click();
			await modalUpload( page, 'qa-photo-1.png' );
			await modalConfirm( page );
			await sub( place, 'place' ).locator( 'input' ).fill( 'Bali' );
			await sub( place, 'country' ).locator( 'input' ).fill( 'Indonesia' );
			await sub( place, 'tab_label' ).locator( 'input' ).fill( 'Bali' );
			await scfTab( page, H, 'Why OG' );
			await field( page, H, 'why_heading' ).locator( 'input' ).fill( 'Why *OG*' );
			await scfTab( page, H, 'Contact' );
			await field( page, H, 'form_title' ).locator( 'input' ).fill( 'Plan my trip' );
			await Promise.all( [ page.waitForURL( /message=1/ ), page.locator( '#publish' ).click() ] );
			await expect( page.locator( '#wpbody-content .notice-success, #wpbody-content .updated' ).first() ).toContainText( 'Homepage saved' );

			await page.reload();
			await scfTab( page, H, 'Hero' );
			await expect( field( page, H, 'hero_heading' ).locator( 'input' ) ).toHaveValue( `${ QA } Where to *next*?` );
			await expect( field( page, H, 'hero_cta_primary' ).locator( '.link-title' ) ).toHaveText( 'Explore trips' );
			await expect( rows( field( page, H, 'hero_places' ) ) ).toHaveCount( 1 );
			await expect( sub( rows( field( page, H, 'hero_places' ) ).first(), 'place' ).locator( 'input' ) ).toHaveValue( 'Bali' );

			// Site Settings
			const S = 'group_ogt_site_settings';
			await page.goto( '/wp-admin/admin.php?page=ogtrips-site-settings' );
			await scfTab( page, S, 'Contact' );
			await field( page, S, 'phone' ).locator( 'input' ).fill( '+91 98765 43210' );
			await field( page, S, 'whatsapp' ).locator( 'input' ).fill( '919876543210' );
			await field( page, S, 'enquiry_email' ).locator( 'input' ).fill( 'qa-enquiries@ogtrips.local' );
			await scfTab( page, S, 'Social' );
			await field( page, S, 'instagram_handle' ).locator( 'input' ).fill( '@ogtrips.qa' );
			await scfTab( page, S, 'Footer' );
			await setSwitch( field( page, S, 'newsletter_enabled' ), true );
			await scfTab( page, S, 'Booking' );
			await field( page, S, 'booking_trust_text' ).locator( 'input' ).fill( 'No payment now · Free cancellation' );
			await Promise.all( [ page.waitForURL( /message=1/ ), page.locator( '#publish' ).click() ] );
			await expect( page.locator( '#wpbody-content .notice-success, #wpbody-content .updated' ).first() ).toContainText( 'Settings saved' );

			await page.reload();
			await scfTab( page, S, 'Contact' );
			await expect( field( page, S, 'whatsapp' ).locator( 'input' ) ).toHaveValue( '919876543210' );
			await expect( field( page, S, 'enquiry_email' ).locator( 'input' ) ).toHaveValue( 'qa-enquiries@ogtrips.local' );
			await scfTab( page, S, 'Footer' );
			await expect( field( page, S, 'newsletter_enabled' ).locator( 'input[type="checkbox"]' ) ).toBeChecked();

			const opts = wpPhp( `
				$o = fn( $n ) => get_field( $n, 'option' );
				return [ 'hero_heading' => $o( 'hero_heading' ), 'cta' => $o( 'hero_cta_primary' ), 'places' => $o( 'hero_places' ), 'why_heading' => $o( 'why_heading' ),
					'form_title' => $o( 'form_title' ), 'whatsapp' => $o( 'whatsapp' ), 'enquiry_email' => $o( 'enquiry_email' ), 'phone' => $o( 'phone' ),
					'instagram_handle' => $o( 'instagram_handle' ), 'newsletter_enabled' => $o( 'newsletter_enabled' ), 'booking_trust_text' => $o( 'booking_trust_text' ) ];
			` );
			expect( opts ).toMatchObject( {
				hero_heading: `${ QA } Where to *next*?`,
				cta: { title: 'Explore trips', url: 'http://localhost:8888/trips/' },
				why_heading: 'Why *OG*',
				form_title: 'Plan my trip',
				whatsapp: '919876543210',
				enquiry_email: 'qa-enquiries@ogtrips.local',
				phone: '+91 98765 43210',
				instagram_handle: '@ogtrips.qa',
				newsletter_enabled: true,
				booking_trust_text: 'No payment now · Free cancellation',
			} );
			expect( opts.places ).toHaveLength( 1 );
			expect( opts.places[ 0 ] ).toMatchObject( { place: 'Bali', country: 'Indonesia', tab_label: 'Bali' } );
			expect( Number( opts.places[ 0 ].image ) ).toBeGreaterThan( 0 );
		} finally {
			// Restore the options exactly as they were before this test.
			const before = JSON.stringify( snapshot ).replace( /\\/g, '\\\\' ).replace( /'/g, "\\'" );
			wpPhp( `
				global $wpdb;
				$before = json_decode( '${ before }', true ) ?: [];
				$now = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'options\\\\_%' OR option_name LIKE '\\\\_options\\\\_%'" );
				foreach ( $now as $name ) { if ( ! array_key_exists( $name, $before ) ) { delete_option( $name ); } }
				foreach ( $before as $name => $value ) { update_option( $name, maybe_unserialize( $value ) ); }
				return true;
			` );
		}
	} );

	/* ------------------------------------------------------------ AC5 */

	test( 'AC5 Enquiries: list, status column, no Add New, bubble count, status change', async ( { merchant: page, admin } ) => {
		const created = wpPhp<{ ids: number[]; count: number }>( `
			$ids = [];
			foreach ( [ [ 'Priya', 'email' ], [ 'Rahul', 'whatsapp' ] ] as $i => $e ) {
				$id = wp_insert_post( [ 'post_type' => 'ogt_enquiry', 'post_status' => 'private', 'post_title' => '${ QA } Enquiry from ' . $e[0] ] );
				update_post_meta( $id, '_ogt_name', $e[0] );
				update_post_meta( $id, '_ogt_phone', '+91 90000 0000' . $i );
				update_post_meta( $id, '_ogt_channel', $e[1] );
				update_post_meta( $id, '_ogt_destination', 'Bali' );
				$ids[] = $id;
			}
			return [ 'ids' => $ids, 'count' => ogtrips_core_new_enquiry_count(), 'status' => array_map( fn( $id ) => get_post_meta( $id, '_ogt_status', true ), $ids ) ];
		` );
		const [ idA, idB ] = created.ids;
		const extra: number[] = [];
		try {
			expect( created.count ).toBeGreaterThanOrEqual( 2 );

			await page.goto( '/wp-admin/edit.php?post_type=ogt_enquiry' );
			expect( await enquiryBubble( page ) ).toBe( created.count );
			await expect( page.locator( 'th#ogt_status' ) ).toHaveText( 'Status' );
			await expect( page.locator( '.page-title-action' ) ).toHaveCount( 0 );
			await expect( page.locator( '#wp-admin-bar-new-ogt_enquiry' ) ).toHaveCount( 0 );
			await expect( page.locator( `#post-${ idA } .ogtrips-status--new` ) ).toHaveText( 'New' );
			await expect( page.locator( `#post-${ idB } td.ogt_channel` ) ).toHaveText( 'WhatsApp' );
			await expect( page.locator( '#ogtrips-status-filter' ) ).toBeVisible();

			// Change status from the enquiry screen.
			await page.locator( `#post-${ idA } a.row-title` ).click();
			await expect( page.locator( '#ogtrips-enquiry-details' ) ).toContainText( 'Priya' );
			await page.locator( '#ogtrips-enquiry-status-select' ).selectOption( 'contacted' );
			await Promise.all( [ page.waitForURL( /message=/ ), page.locator( '#ogtrips-enquiry-status input[name="save"], #ogtrips-enquiry-status #save' ).first().click() ] );
			await expect( page.locator( '#ogtrips-enquiry-status-select' ) ).toHaveValue( 'contacted' );
			expect( await enquiryBubble( page ) ).toBe( created.count - 1 );
			expect( wpPhp( `return [ get_post_status( ${ idA } ), get_post_meta( ${ idA }, '_ogt_status', true ) ];` ) ).toEqual( [ 'private', 'contacted' ] );

			// Status filter.
			await page.goto( '/wp-admin/edit.php?post_type=ogt_enquiry&ogt_status=contacted' );
			await expect( page.locator( `#post-${ idA }` ) ).toBeVisible();
			await expect( page.locator( `#post-${ idB }` ) ).toHaveCount( 0 );

			// Status written directly with WP-CLI (`wp post meta update`) and an enquiry created with `wp post create`
			// must both be reflected in the bubble straight away (cache cleared on meta change / save).
			const before = await enquiryBubble( page );
			wpCli( [ 'post', 'meta', 'update', String( idB ), '_ogt_status', 'won' ] );
			await page.goto( '/wp-admin/edit.php?post_type=ogt_enquiry' );
			expect( await enquiryBubble( page ), 'bubble after wp post meta update' ).toBe( before - 1 );
			await expect( page.locator( `#post-${ idB } td.ogt_status` ) ).not.toContainText( 'New' );
			const idC = Number( wpCli( [ 'post', 'create', '--post_type=ogt_enquiry', '--post_status=private', `--post_title=${ QA } Enquiry via CLI`, '--porcelain' ] ).out.trim() );
			extra.push( idC );
			await page.reload();
			expect( await enquiryBubble( page ), 'bubble after wp post create' ).toBe( before );
			await expect( page.locator( `#post-${ idC } .ogtrips-status--new` ) ).toHaveText( 'New' );
			wpCli( [ 'post', 'meta', 'update', String( idC ), '_ogt_status', 'new' ] ); // unchanged value: still counted once
			wpPhp( `wp_trash_post( ${ idC } ); return true;` ); // to the bin (WP-CLI refuses to trash custom types without --force)
			await page.reload();
			expect( await enquiryBubble( page ), 'bubble after trashing via CLI' ).toBe( before - 1 );

			// Dashboard widget lists the latest enquiries.
			await page.goto( '/wp-admin/' );
			await expect( page.locator( '#ogtrips_dashboard' ) ).toContainText( `${ QA } Enquiry from Rahul` );

			// Adding by hand is refused, for both roles.
			for ( const p of [ page, admin ] ) {
				const r = await p.goto( '/wp-admin/post-new.php?post_type=ogt_enquiry' );
				expect( r?.status() ).toBe( 403 );
				await expect( p.locator( 'body' ) ).toContainText( /not allowed/i );
			}
		} finally {
			wpPhp( `foreach ( [ ${ [ idA, idB, ...extra ].join( "," ) } ] as $id ) { wp_delete_post( $id, true ); } return true;` );
		}
	} );

	/* ------------------------------------------------------------ AC6 */

	test( 'AC6 URLs, status codes and Yoast sitemap exclusions', async ( { anon, qa } ) => {
		const d = wpPhp( `
			$make = function ( $type, $status = 'publish' ) {
				$id = wp_insert_post( [ 'post_type' => $type, 'post_status' => $status, 'post_title' => '${ QA } URL ' . $type, 'post_content' => 'QA', 'post_author' => 1 ] );
				return [ 'id' => $id, 'slug' => get_post_field( 'post_name', $id ), 'url' => get_permalink( $id ) ];
			};
			return [ 'guide' => $make( 'ogt_guide' ), 'post' => $make( 'post' ), 'review' => $make( 'ogt_review' ), 'moment' => $make( 'ogt_moment' ), 'enquiry' => $make( 'ogt_enquiry', 'private' ),
				'term' => get_term_link( ${ qa.termId }, 'ogt_destination' ) ];
		` );
		try {
			const ok: Record<string, string> = {
				trip: qa.tripUrl,
				guide: d.guide.url,
				blog: d.post.url,
				'trips archive': '/trips/',
				'guides archive': '/travel-guide/',
				destination: d.term,
			};
			expect( qa.tripUrl ).toMatch( /\/trips\/qa-fixture-trip\/$/ );
			expect( d.guide.url ).toMatch( new RegExp( `/travel-guide/${ d.guide.slug }/$` ) );
			expect( d.post.url ).toMatch( new RegExp( `/blog/${ d.post.slug }/$` ) );
			expect( d.term ).toMatch( /\/destinations\/qa-destination\/$/ );
			for ( const [ what, url ] of Object.entries( ok ) ) {
				const r = await anon.get( url );
				expect.soft( r.status(), `${ what } ${ url }` ).toBe( 200 );
				expect.soft( await r.text(), `${ what } has no PHP errors` ).not.toMatch( /(Warning|Notice|Deprecated|Fatal error):/ );
			}

			for ( const kind of [ 'review', 'moment', 'enquiry' ] as const ) {
				const { id, slug } = d[ kind ];
				const type = `ogt_${ kind }`;
				for ( const url of [ `/?p=${ id }`, `/?post_type=${ type }&p=${ id }`, `/?${ type }=${ slug }`, `/${ type }/${ slug }/`, `/${ kind }s/${ slug }/` ] ) {
					const r = await anon.get( url );
					// Unregistered query vars (?ogt_review=slug) are ignored by WP and render the home page — fine as long as
					// the item itself is not shown. Every other URL must be a 404.
					if ( url.startsWith( `/?${ type }=` ) ) {
						expect.soft( await r.text(), `${ kind } ${ url } must not render the item` ).not.toContain( `URL ${ type }` );
					} else {
						expect.soft( r.status(), `${ kind } ${ url } -> ${ r.url() }` ).toBe( 404 );
					}
				}
			}

			const index = await anon.get( '/sitemap_index.xml' );
			expect( index.status() ).toBe( 200 );
			const xml = await index.text();
			test.info().annotations.push( { type: 'sitemap_index.xml', description: ( xml.match( /<loc>[^<]+<\/loc>/g ) ?? [] ).join( ' ' ) } );
			expect.soft( xml ).toContain( 'ogt_itinerary-sitemap' );
			expect.soft( xml ).not.toMatch( /ogt_review|ogt_moment|ogt_enquiry/ );
			for ( const type of [ 'ogt_review', 'ogt_moment', 'ogt_enquiry' ] ) {
				expect.soft( ( await anon.get( `/${ type }-sitemap.xml` ) ).status(), `${ type }-sitemap.xml` ).toBe( 404 );
			}
		} finally {
			wpPhp( `foreach ( [ ${ [ 'guide', 'post', 'review', 'moment', 'enquiry' ].map( ( k ) => d[ k ].id ).join( ',' ) } ] as $id ) { wp_delete_post( $id, true ); } return true;` );
		}
	} );

	/* ------------------------------------------------------------ AC7 */

	test( 'AC7 comments are off everywhere', async ( { anon, admin, merchant } ) => {
		const d = wpPhp( `
			$id = wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => '${ QA } Comments check', 'post_content' => 'QA', 'post_author' => 1 ] );
			$supports = array_values( array_filter( get_post_types(), fn( $t ) => post_type_supports( $t, 'comments' ) || post_type_supports( $t, 'trackbacks' ) ) );
			return [ 'id' => $id, 'url' => get_permalink( $id ), 'default' => get_option( 'default_comment_status' ), 'ping' => get_option( 'default_ping_status' ),
				'supports' => $supports, 'open' => comments_open( $id ), 'comment_status' => get_post_field( 'comment_status', $id ) ];
		` );
		try {
			expect.soft( d.default ).toBe( 'closed' );
			expect.soft( d.ping ).toBe( 'closed' );
			expect.soft( d.supports ).toEqual( [] );
			expect.soft( d.open ).toBe( false );

			// Front end: no form, count or comment feeds.
			const post = await anon.get( d.url );
			expect( post.status() ).toBe( 200 );
			const html = await post.text();
			expect.soft( html ).not.toMatch( /id="respond"|class="comment-form|wp-comments-post\.php|comments-link|comments-title/ );
			const home = await ( await anon.get( '/' ) ).text();
			expect.soft( home ).not.toMatch( /Comments Feed|comments\/feed/i );
			for ( const url of [ '/comments/feed/', `${ d.url }feed/`, '/?feed=comments-rss2' ] ) {
				expect.soft( ( await anon.get( url ) ).status(), url ).toBe( 404 );
			}
			const submit = await anon.post( '/wp-comments-post.php', { form: { comment_post_ID: String( d.id ), comment: 'QA spam', author: 'QA', email: 'qa@example.com' }, maxRedirects: 0 } );
			expect.soft( submit.status(), 'comment submission is refused' ).toBeGreaterThanOrEqual( 400 );

			// wp-admin: no menu, bar item, list column; screens redirect away.
			for ( const page of [ admin, merchant ] ) {
				await page.goto( '/wp-admin/edit.php' );
				await expect.soft( page.locator( '#menu-comments, #adminmenu a[href="edit-comments.php"]' ) ).toHaveCount( 0 );
				await expect.soft( page.locator( '#wp-admin-bar-comments' ) ).toHaveCount( 0 );
				await expect.soft( page.locator( 'th#comments, th.column-comments' ) ).toHaveCount( 0 );
				await page.goto( '/wp-admin/edit-comments.php' );
				await expect.soft( page ).toHaveURL( /\/wp-admin\/(index\.php)?$/ );
			}
			await admin.goto( '/wp-admin/options-discussion.php' );
			await expect.soft( admin ).toHaveURL( /\/wp-admin\/(index\.php)?$/ );
		} finally {
			wpPhp( `wp_delete_post( ${ d.id }, true ); return true;` );
		}
	} );

	/* ------------------------------------------------------------ AC8 */

	test( 'AC8 block editor only for Tour Guides and Blog', async ( { merchant: page } ) => {
		const flags = wpPhp( `
			require_once ABSPATH . 'wp-admin/includes/post.php';
			$out = [];
			foreach ( [ 'ogt_itinerary', 'ogt_review', 'ogt_moment', 'ogt_enquiry', 'ogt_guide', 'post' ] as $t ) { $out[ $t ] = use_block_editor_for_post_type( $t ); }
			return $out;
		` );
		expect( flags ).toEqual( { ogt_itinerary: false, ogt_review: false, ogt_moment: false, ogt_enquiry: false, ogt_guide: true, post: true } );

		for ( const type of [ 'ogt_itinerary', 'ogt_review', 'ogt_moment' ] ) {
			await page.goto( `/wp-admin/post-new.php?post_type=${ type }` );
			await expect( page.locator( 'body.block-editor-page' ), type ).toHaveCount( 0 );
			await expect( page.locator( 'form#post #poststuff' ), type ).toBeVisible();
			await expect( page.locator( `#acf-group_${ type }` ), type ).toBeVisible();
		}
		for ( const url of [ '/wp-admin/post-new.php?post_type=ogt_guide', '/wp-admin/post-new.php' ] ) {
			await page.goto( url );
			await expect( page.locator( 'body.block-editor-page' ), url ).toHaveCount( 1 );
		}
	} );

	/* ------------------------------------------------------------ AC9 */

	test( 'AC9 no Gravatar on admin or front-end pages', async ( { admin, merchant, anon, qa } ) => {
		// The `gravatar` fixture fails the test on any request to gravatar.com; the markup is checked too.
		// Profile photo (ogt_photo) set through the profile screen becomes the avatar.
		await admin.goto( `/wp-admin/user-edit.php?user_id=${ qa.userId }` );
		const photo = admin.locator( '.acf-field[data-name="ogt_photo"]' );
		await expect( photo ).toBeVisible();
		await expect( admin.locator( '.acf-field[data-name="photo"]' ), 'old field name gone' ).toHaveCount( 0 );
		await photo.locator( 'a[data-name="add"]' ).click();
		await modalUpload( admin, 'qa-photo-1.png' );
		await modalConfirm( admin );
		await Promise.all( [ admin.waitForURL( /updated=1/, { timeout: 120_000 } ), admin.locator( '#submit' ).click() ] );
		const av = wpPhp<{ meta: number; url: string }>( `return [ 'meta' => (int) get_user_meta( ${ qa.userId }, 'ogt_photo', true ), 'url' => get_avatar_url( ${ qa.userId } ) ];` );
		expect( av.meta ).toBeGreaterThan( 0 );
		expect( av.url ).toMatch( /\/wp-content\/uploads\/.+qa-photo-1/ );
		await admin.goto( '/wp-admin/users.php' );
		await expect( admin.locator( `#user-${ qa.userId } img.avatar` ) ).toHaveAttribute( 'src', /\/wp-content\/uploads\/.+qa-photo-1/ );

		const pages: [ Page, string ][] = [
			[ admin, '/wp-admin/' ],
			[ admin, '/wp-admin/profile.php' ],
			[ admin, '/wp-admin/users.php' ],
			[ admin, `/wp-admin/user-edit.php?user_id=${ qa.userId }` ],
			[ admin, '/wp-admin/edit.php' ],
			[ admin, '/' ],
			[ admin, qa.tripUrl ],
			[ merchant, '/wp-admin/' ],
			[ merchant, '/wp-admin/profile.php' ],
			[ merchant, '/wp-admin/edit.php?post_type=ogt_guide' ],
			[ merchant, '/' ],
		];
		for ( const [ page, url ] of pages ) {
			await page.goto( url );
			await page.waitForLoadState( 'networkidle' );
			// No image source on gravatar.com (actual requests are caught by the fixture). A plain <a> link is not a request.
			expect.soft( await page.content(), url ).not.toMatch( /(src|srcset)="[^"]*gravatar\.com/i );
			const links = await page.locator( 'a[href*="gravatar.com"]' ).allInnerTexts();
			if ( links.length ) test.info().annotations.push( { type: 'gravatar.com link (not a request)', description: `${ url }: ${ links.join( ' | ' ) }` } );
			const avatars = await page.locator( 'img.avatar' ).evaluateAll( ( imgs ) => imgs.map( ( i ) => ( i as HTMLImageElement ).src ) );
			for ( const src of avatars ) expect.soft( src, `avatar on ${ url }` ).toMatch( /^http:\/\/localhost:8888\// );
		}
		for ( const url of [ '/', qa.tripUrl, '/trips/' ] ) {
			expect.soft( await ( await anon.get( url ) ).text(), url ).not.toMatch( /gravatar\.com/i );
		}
	} );

	/* ------------------------------------------------------------ AC10 */

	test( 'AC10 field groups from acf-json; site survives SCF deactivation', async ( { admin, merchant, anon } ) => {
		const groups = wpPhp<{ key: string; local: string; file: string }[]>( `
			return array_map( fn( $g ) => [ 'key' => $g['key'], 'local' => $g['local'] ?? '', 'file' => $g['local_file'] ?? '' ], acf_get_field_groups() );
		` );
		expect( groups.map( ( g ) => g.key ).sort() ).toEqual(
			[ 'group_ogt_article', 'group_ogt_homepage', 'group_ogt_itinerary', 'group_ogt_moment', 'group_ogt_review', 'group_ogt_site_settings', 'group_ogt_trip_cta', 'group_ogt_trip_type', 'group_ogt_user' ].sort()
		);
		for ( const g of groups ) {
			expect.soft( g.local, g.key ).toBe( 'json' );
			expect.soft( g.file, g.key ).toMatch( /\/plugins\/ogtrips-core\/acf-json\/group_ogt_[a-z_]+\.json$/ );
		}

		const logBefore = wpCli( [ 'eval', 'echo (int) @filesize( WP_CONTENT_DIR . "/debug.log" );' ] ).out.trim();
		const deactivate = wpCli( [ 'plugin', 'deactivate', 'secure-custom-fields' ], { allowFail: true } );
		test.info().annotations.push( { type: 'wp plugin deactivate secure-custom-fields', description: `exit ${ deactivate.code }: ${ deactivate.out.trim() }` } );
		try {
			const state = wpPhp( `
				return [ 'scf' => is_plugin_active( 'secure-custom-fields/secure-custom-fields.php' ), 'core' => is_plugin_active( 'ogtrips-core/ogtrips-core.php' ),
					'core_loaded' => function_exists( 'ogtrips_core_load_includes' ), 'trips' => post_type_exists( 'ogt_itinerary' ), 'acf' => function_exists( 'get_field' ) ];
			` );
			test.info().annotations.push( { type: 'state with SCF off', description: JSON.stringify( state ) } );
			expect( state.scf, 'SCF deactivated' ).toBe( false );

			for ( const url of [ '/', '/trips/', '/wp-login.php' ] ) {
				const r = await anon.get( url );
				expect.soft( r.status(), url ).toBeLessThan( 500 );
				expect.soft( await r.text(), url ).not.toMatch( FATAL );
			}
			for ( const [ page, url ] of [
				[ admin, '/wp-admin/' ],
				[ admin, '/wp-admin/plugins.php' ],
				[ admin, '/wp-admin/edit.php?post_type=ogt_itinerary' ],
				[ admin, '/wp-admin/post-new.php?post_type=ogt_itinerary' ],
				[ admin, '/wp-admin/post-new.php?post_type=ogt_guide' ],
				[ admin, '/wp-admin/profile.php' ],
				[ merchant, '/wp-admin/' ],
				[ merchant, '/wp-admin/edit.php?post_type=ogt_review' ],
			] as [ Page, string ][] ) {
				const r = await page.goto( url );
				expect.soft( r?.status(), url ).toBeLessThan( 500 );
				expect.soft( await page.content(), url ).not.toMatch( FATAL );
			}
			const newLog = wpCli( [ 'eval', `$f = WP_CONTENT_DIR . "/debug.log"; echo file_exists( $f ) ? substr( (string) file_get_contents( $f ), ${ Number( logBefore ) || 0 } ) : "";` ] ).out;
			test.info().annotations.push( { type: 'debug.log while SCF off', description: newLog.trim() || '(nothing new)' } );
			expect.soft( newLog, 'debug.log lines while SCF was off' ).not.toMatch( /PHP Fatal|Uncaught/ );
		} finally {
			wpCli( [ 'plugin', 'activate', 'secure-custom-fields' ], { allowFail: true } );
			const after = wpCli( [ 'plugin', 'list', '--status=active', '--field=name' ] ).out;
			expect( after ).toContain( 'secure-custom-fields' );
			expect( after ).toContain( 'ogtrips-core' );
		}
	} );
} );
