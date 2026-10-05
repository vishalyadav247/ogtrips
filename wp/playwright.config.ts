import { defineConfig, devices } from '@playwright/test';

// Requires the local site: `npx wp-env start` (http://localhost:8888).
export default defineConfig( {
	testDir: './tests/e2e',
	fullyParallel: true,
	// wp-env on Docker Desktop for Windows serves pages in ~10 s (bind-mounted files), so allow headroom.
	timeout: 120_000,
	workers: 2,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 1 : 0,
	reporter: [ [ 'list' ], [ 'html', { open: 'never' } ] ],
	use: {
		baseURL: 'http://localhost:8888',
		navigationTimeout: 60_000,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'desktop-1440',
			use: { ...devices[ 'Desktop Chrome' ], viewport: { width: 1440, height: 900 } },
		},
		{
			name: 'mobile-390',
			use: { ...devices[ 'Desktop Chrome' ], viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true },
		},
	],
} );
