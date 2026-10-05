import { test, expect, type Page } from '@playwright/test';

// Phase 01 smoke: the site and login respond, our one CSS + one JS load, fonts are
// self-hosted, no third-party font/icon CDNs, no PHP notices, no console errors.

const PHP_NOTICE = /(Warning|Notice|Deprecated|Fatal error):/;
const BLOCKED_HOSTS = /fonts\.googleapis\.com|fonts\.gstatic\.com|unpkg\.com/;

function trackPage( page: Page ) {
	const consoleErrors: string[] = [];
	const thirdPartyRequests: string[] = [];
	page.on( 'console', ( msg ) => {
		if ( msg.type() === 'error' ) consoleErrors.push( msg.text() );
	} );
	page.on( 'pageerror', ( err ) => consoleErrors.push( err.message ) );
	page.on( 'request', ( req ) => {
		if ( BLOCKED_HOSTS.test( req.url() ) ) thirdPartyRequests.push( req.url() );
	} );
	return { consoleErrors, thirdPartyRequests };
}

test.describe( 'smoke', () => {
	test( 'home page loads our assets cleanly', async ( { page } ) => {
		const tracked = trackPage( page );
		const response = await page.goto( '/' );
		expect( response?.status() ).toBe( 200 );

		const html = await page.content();
		expect( html ).not.toMatch( PHP_NOTICE );

		// One custom stylesheet + one deferred custom script from the child theme.
		await expect( page.locator( 'link[rel="stylesheet"][href*="themes/ogtrips/style.css?ver="]' ) ).toHaveCount( 1 );
		await expect( page.locator( 'script[src*="themes/ogtrips/assets/js/main.js?ver="][defer]' ) ).toHaveCount( 1 );

		// Self-hosted fonts actually load. document.fonts.check() alone is not proof (it returns
		// true for unknown families), so assert the FontFace status and the theme-served files.
		const fonts = await page.evaluate( async () => {
			await Promise.all( [ document.fonts.load( '700 16px Poppins' ), document.fonts.load( '400 16px "DM Sans"' ) ] );
			const status = ( family: string, weight: string ) =>
				[ ...document.fonts ].find( ( f ) => f.family.replace( /"/g, '' ) === family && f.weight === weight )?.status;
			return { poppins: status( 'Poppins', '700' ), dmSans: status( 'DM Sans', '400' ) };
		} );
		expect( fonts ).toEqual( { poppins: 'loaded', dmSans: 'loaded' } );
		const fontFiles = await page.evaluate( () =>
			performance
				.getEntriesByType( 'resource' )
				.map( ( e ) => e.name )
				.filter( ( url ) => /\/themes\/ogtrips\/assets\/fonts\/.+\.woff2$/.test( url ) )
		);
		expect( fontFiles.length ).toBeGreaterThanOrEqual( 2 );

		await page.waitForLoadState( 'networkidle' );
		expect( tracked.thirdPartyRequests ).toEqual( [] );
		expect( tracked.consoleErrors ).toEqual( [] );
	} );

	test( 'wp-admin redirects to a working login page', async ( { page } ) => {
		const tracked = trackPage( page );
		const response = await page.goto( '/wp-admin/' );
		expect( response?.status() ).toBe( 200 );
		await expect( page ).toHaveURL( /wp-login\.php/ );
		await expect( page.locator( '#loginform' ) ).toBeVisible();
		expect( await page.content() ).not.toMatch( PHP_NOTICE );
		expect( tracked.consoleErrors ).toEqual( [] );
	} );

	test( '404 page responds without PHP notices', async ( { page } ) => {
		const response = await page.goto( '/ogtrips-smoke-missing-page/' );
		expect( response?.status() ).toBe( 404 );
		expect( await page.content() ).not.toMatch( PHP_NOTICE );
	} );
} );
