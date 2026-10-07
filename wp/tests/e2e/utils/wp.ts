import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect as baseExpect, type Browser, type FrameLocator, type Locator, type Page } from '@playwright/test';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );

/** expect with headroom for the slow local Docker stack (pages take 3-15 s). */
export const expect = baseExpect.configure( { timeout: 45_000 } );

/*
 * Shared helpers for the OgTrips admin tests: WP-CLI bridge, role logins, SCF field
 * helpers (tabs, repeaters, select2, media modal, date picker) and block-editor helpers.
 */

export const FIXTURES = path.join( __dirname, '..', 'fixtures' );
export const QA = '[QA]'; // Every post/term/user these tests create is marked with this prefix.

/* ------------------------------------------------------------------ WP-CLI */

let cliContainer: string | undefined;

/** Name of the running wp-env CLI container (override with OGT_CLI_CONTAINER). */
function container(): string {
	if ( cliContainer ) return cliContainer;
	if ( process.env.OGT_CLI_CONTAINER ) return ( cliContainer = process.env.OGT_CLI_CONTAINER );
	const names = execFileSync( 'docker', [ 'ps', '--format', '{{.Names}}' ], { encoding: 'utf8' } )
		.split( /\r?\n/ )
		.filter( ( n ) => /^wp-env-.+-cli-1$/.test( n ) );
	if ( ! names.length ) throw new Error( 'wp-env CLI container not running (npx wp-env start)' );
	return ( cliContainer = names[ 0 ] );
}

/** Runs `wp <args>` in the CLI container. Returns stdout+stderr; throws on non-zero exit unless allowFail. */
export function wpCli( args: string[], { allowFail = false } = {} ): { code: number; out: string } {
	try {
		const out = execFileSync( 'docker', [ 'exec', '-i', '-w', '/var/www/html', container(), 'wp', ...args ], {
			encoding: 'utf8',
			timeout: 240_000,
			stdio: [ 'pipe', 'pipe', 'pipe' ],
		} );
		return { code: 0, out };
	} catch ( e: any ) {
		const out = `${ e.stdout ?? '' }${ e.stderr ?? '' }`;
		if ( allowFail ) return { code: e.status ?? 1, out };
		throw new Error( `wp ${ args.join( ' ' ) } failed (${ e.status }): ${ out }` );
	}
}

/** Runs a PHP snippet with WordPress loaded (`wp eval-file -`); the snippet must `return` a JSON-able value. */
export function wpPhp<T = any>( php: string, extraArgs: string[] = [] ): T {
	const code = `<?php\n$__r = ( function () {\n${ php }\n} )();\necho "\\n@@OGT@@" . wp_json_encode( $__r );\n`;
	let out: string;
	try {
		out = execFileSync(
			'docker',
			[ 'exec', '-i', '-w', '/var/www/html', container(), 'wp', 'eval-file', '-', ...extraArgs ],
			{ input: code, encoding: 'utf8', timeout: 240_000, stdio: [ 'pipe', 'pipe', 'pipe' ] }
		);
	} catch ( e: any ) {
		throw new Error( `wp eval-file failed (${ e.status }): ${ e.stdout ?? '' }${ e.stderr ?? '' }` );
	}
	const marker = out.lastIndexOf( '@@OGT@@' );
	if ( marker < 0 ) throw new Error( `No result from wp eval-file: ${ out }` );
	return JSON.parse( out.slice( marker + 7 ).trim() ) as T;
}

/* ------------------------------------------------------------------ logins */

export type Role = 'admin' | 'merchant';

/** Logs in through wp-login.php and saves the storage state to `file`. */
export async function saveLogin( browser: Browser, role: Role, file: string, baseURL: string ) {
	const context = await browser.newContext( { baseURL } );
	const page = await context.newPage();
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( role );
	await page.locator( '#user_pass' ).fill( 'password' );
	await Promise.all( [ page.waitForURL( /\/wp-admin\/?/, { timeout: 90_000 } ), page.locator( '#wp-submit' ).click() ] );
	await context.storageState( { path: file } );
	await context.close();
}

/** Collects every request to Gravatar made by a page (AC9). */
export function trackGravatar( page: Page, sink: string[] ) {
	page.on( 'request', ( req ) => {
		if ( /gravatar\.com/i.test( req.url() ) ) sink.push( `${ page.url() } -> ${ req.url() }` );
	} );
}

/** Text of the visible top-level admin menu items, without count bubbles. */
export async function adminMenu( page: Page ): Promise<string[]> {
	return page.$$eval( '#adminmenu > li.menu-top:not(#collapse-menu)', ( items ) =>
		items
			.filter( ( li ) => ( li as HTMLElement ).offsetParent !== null )
			.map( ( li ) => {
				const name = li.querySelector( '.wp-menu-name' )?.cloneNode( true ) as HTMLElement | null;
				name?.querySelectorAll( '.awaiting-mod, .update-plugins, .screen-reader-text' ).forEach( ( n ) => n.remove() );
				return ( name?.textContent ?? '' ).replace( /\s+/g, ' ' ).trim();
			} )
	);
}

/** Count shown in the Enquiries menu bubble (0 when there is no bubble). */
export async function enquiryBubble( page: Page ): Promise<number> {
	const bubble = page.locator( '#adminmenu a[href="edit.php?post_type=ogt_enquiry"] .awaiting-mod .pending-count' );
	return ( await bubble.count() ) ? Number( await bubble.first().textContent() ) : 0;
}

/* ------------------------------------------------------------------ classic screen + SCF */

/** Top-level SCF field of a field group. */
export function field( page: Page, group: string, name: string ): Locator {
	return page.locator( `#acf-${ group } > .inside > .acf-field[data-name="${ name }"]` );
}

/** Clicks an SCF tab by label inside a field group. */
export async function scfTab( page: Page, group: string, label: string ) {
	await page.locator( `#acf-${ group } .acf-tab-wrap a.acf-tab-button`, { hasText: label } ).first().click();
}

/** Top-level rows of a repeater field (excludes the hidden clone row). */
export function rows( repeater: Locator ): Locator {
	return repeater.locator( ':scope > .acf-input > .acf-repeater > table > tbody > tr.acf-row:not(.acf-clone)' );
}

/** Adds a row to a (top-level) repeater and returns it. */
export async function addRow( repeater: Locator ): Promise<Locator> {
	const before = await rows( repeater ).count();
	await repeater.locator( ':scope > .acf-input > .acf-repeater > .acf-actions [data-event="add-row"]' ).click();
	await expect( rows( repeater ) ).toHaveCount( before + 1 );
	return rows( repeater ).nth( before );
}

/** Sub-field inside a repeater row (direct child of the row, not a nested repeater's). */
export function sub( row: Locator, name: string ): Locator {
	return row.locator( `:scope > td.acf-field[data-name="${ name }"], :scope > td.acf-fields > .acf-field[data-name="${ name }"]` ).first();
}

/** Toggles an SCF true/false (ui) field to the wanted state. */
export async function setSwitch( fieldLoc: Locator, on: boolean ) {
	const box = fieldLoc.locator( 'input[type="checkbox"]' );
	if ( ( await box.isChecked() ) !== on ) await fieldLoc.locator( '.acf-switch' ).click();
	await expect( box ).toBeChecked( { checked: on } );
}

/** Picks a day in next month in an SCF date picker; returns the stored Ymd value. */
export async function pickDate( page: Page, dateField: Locator, day = '15' ): Promise<string> {
	await dateField.locator( 'input.input' ).click();
	const dp = page.locator( '#ui-datepicker-div' );
	await expect( dp ).toBeVisible();
	await dp.locator( '.ui-datepicker-next' ).click();
	await dp.locator( 'td:not(.ui-datepicker-other-month) a.ui-state-default', { hasText: new RegExp( `^${ day }$` ) } ).click();
	const stored = await dateField.locator( 'input[type="hidden"]' ).inputValue();
	expect( stored ).toMatch( /^\d{8}$/ );
	return stored;
}

/** Opens a select2 (SCF user / post-object field) and picks the option matching `text`. */
export async function select2Pick( page: Page, fieldLoc: Locator, search: string, text: RegExp | string ) {
	await fieldLoc.locator( '.select2-selection' ).click();
	const open = page.locator( '.select2-container--open' );
	await open.locator( '.select2-search__field' ).fill( search );
	await open.locator( '.select2-results__option:not(.loading-results)', { hasText: text } ).first().click();
	await expect( fieldLoc.locator( '.select2-selection__rendered' ) ).toContainText( text );
}

/* ------------------------------------------------------------------ media modal */

export function visibleModal( page: Page ): Locator {
	return page.locator( '.media-modal' ).filter( { visible: true } );
}

/** Uploads a fixture through the open media modal's "Upload files" tab; it ends up selected. */
export async function modalUpload( page: Page, file: string ) {
	const modal = visibleModal( page );
	await expect( modal ).toHaveCount( 1 );
	const uploadTab = modal.locator( '#menu-item-upload' );
	if ( await uploadTab.isVisible() ) await uploadTab.click();
	const chooserPromise = page.waitForEvent( 'filechooser' );
	await modal.locator( '.upload-ui .browser' ).filter( { visible: true } ).first().click();
	await ( await chooserPromise ).setFiles( path.join( FIXTURES, file ) );
	const base = file.replace( /\.png$/, '' );
	await expect( modal.locator( `li.attachment.selected[aria-label^="${ base }"]` ).first() ).toBeVisible( { timeout: 90_000 } );
	await expect( modal.locator( 'li.attachment .media-progress-bar' ) ).toHaveCount( 0, { timeout: 90_000 } );
}

/** Selects an existing library item whose title starts with `titlePrefix`. */
export async function modalPick( page: Page, titlePrefix: string ) {
	const modal = visibleModal( page );
	const libTab = modal.locator( '#menu-item-browse' );
	if ( await libTab.isVisible() ) await libTab.click();
	const item = modal.locator( `li.attachment[aria-label^="${ titlePrefix }"]` ).first();
	await item.click();
	await expect( item ).toHaveClass( /selected/ );
}

/** Confirms the media modal (Select / Set hero image / Add to gallery…). */
export async function modalConfirm( page: Page ) {
	const modal = visibleModal( page );
	const button = modal.locator( '.media-toolbar-primary .button-primary' ).filter( { visible: true } ).first();
	await expect( button ).toBeEnabled();
	await button.click();
	await expect( modal ).toHaveCount( 0 );
}

/* ------------------------------------------------------------------ classic publish */

/** Clicks Publish/Update on a classic edit screen and waits for the reloaded screen. Returns the post ID. */
export async function classicSave( page: Page, expectMessage: RegExp ): Promise<number> {
	await Promise.all( [
		page.waitForURL( /post\.php\?post=\d+&action=edit/, { timeout: 120_000 } ),
		page.locator( '#publish' ).click(),
	] );
	await expect( page.locator( '#message, .notice-success' ).first() ).toContainText( expectMessage );
	return Number( new URL( page.url() ).searchParams.get( 'post' ) );
}

/* ------------------------------------------------------------------ block editor */

/** Editor canvas (iframed in recent WordPress, inline when meta boxes force the legacy mode). */
export async function canvas( page: Page ): Promise<Page | FrameLocator> {
	return ( await page.locator( 'iframe[name="editor-canvas"]' ).count() ) ? page.frameLocator( 'iframe[name="editor-canvas"]' ) : page;
}

/** Opens a block-editor screen and dismisses first-run guides. */
export async function openBlockEditor( page: Page, url: string ) {
	await page.goto( url );
	await page.waitForFunction( () => ( window as any ).wp?.data?.select( 'core/editor' )?.getCurrentPostId?.(), null, { timeout: 90_000 } );
	await page.evaluate( () => {
		const prefs = ( window as any ).wp.data.dispatch( 'core/preferences' );
		prefs.set( 'core/edit-post', 'welcomeGuide', false );
		prefs.set( 'core', 'enableChoosePatternModal', false );
	} );
	const guide = page.locator( '.edit-post-welcome-guide, .components-guide' );
	if ( await guide.isVisible() ) await page.keyboard.press( 'Escape' );
	await page.locator( '.editor-header, .edit-post-header' ).first().waitFor();
}

/** Waits until the editor finished saving the post and its meta boxes. */
export async function waitEditorSaved( page: Page ) {
	await page.waitForFunction(
		() => {
			const ed = ( window as any ).wp.data.select( 'core/editor' );
			const mb = ( window as any ).wp.data.select( 'core/edit-post' );
			return ! ed.isSavingPost() && ! ed.isAutosavingPost() && ! ( mb?.isSavingMetaBoxes?.() ) && ! ed.isEditedPostDirty();
		},
		null,
		{ timeout: 120_000 }
	);
}

/** Publishes from the block editor (with the pre-publish panel). Returns the post ID. */
export async function blockPublish( page: Page ): Promise<number> {
	await page.locator( '.editor-post-publish-panel__toggle' ).click();
	await page.locator( '.editor-post-publish-panel .editor-post-publish-button' ).click();
	await page.waitForFunction( () => ( window as any ).wp.data.select( 'core/editor' ).isCurrentPostPublished(), null, { timeout: 120_000 } );
	await waitEditorSaved( page );
	return page.evaluate( () => ( window as any ).wp.data.select( 'core/editor' ).getCurrentPostId() );
}

/** Saves an already-published post from the block editor (Update/Save). */
export async function blockUpdate( page: Page ) {
	await page.locator( '.editor-header .editor-post-publish-button, .edit-post-header .editor-post-publish-button' ).first().click();
	await waitEditorSaved( page );
}

/* ------------------------------------------------------------------ list screen */

/** Moves a post to the bin from its list screen, then empties the bin for that type. */
export async function trashAndEmpty( page: Page, postType: string, postId: number ) {
	await page.goto( `/wp-admin/edit.php?post_type=${ postType }` );
	const row = page.locator( `#post-${ postId }` );
	await row.hover();
	await Promise.all( [ page.waitForURL( /trashed=1/ ), row.locator( '.row-actions .trash a, .row-actions a.submitdelete' ).first().click() ] );
	await expect( page.locator( `#post-${ postId }` ) ).toHaveCount( 0 );
	await page.goto( `/wp-admin/edit.php?post_status=trash&post_type=${ postType }` );
	await Promise.all( [ page.waitForURL( /deleted=\d+/ ), page.locator( '#delete_all' ).first().click() ] );
}
