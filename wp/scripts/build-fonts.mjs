// Copies the self-hosted font files (latin subset, woff2) from @fontsource into the child theme.
// Poppins 500/600/700 (headings) + DM Sans 400/500/700 (body). @font-face rules live in style.css.
import { copyFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const require = createRequire( import.meta.url );
const root = dirname( dirname( fileURLToPath( import.meta.url ) ) );
const outDir = join( root, 'themes/ogtrips/assets/fonts' );

const fonts = {
	poppins: [ 500, 600, 700 ],
	'dm-sans': [ 400, 500, 700 ],
};

mkdirSync( outDir, { recursive: true } );

for ( const [ family, weights ] of Object.entries( fonts ) ) {
	const pkgDir = dirname( require.resolve( `@fontsource/${ family }/package.json` ) );
	for ( const weight of weights ) {
		const file = `${ family }-latin-${ weight }-normal.woff2`;
		copyFileSync( join( pkgDir, 'files', file ), join( outDir, file ) );
		console.log( `fonts: ${ file }` );
	}
}
