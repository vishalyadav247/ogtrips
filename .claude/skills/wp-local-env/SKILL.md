---
name: wp-local-env
description: Run and manage the local OgTrips WordPress site (GeneratePress parent + ogtrips child, SCF, Yoast) with wp-env (Docker) and WP-CLI on this Windows machine — start/stop, install plugins, activate theme, import demo content, reset, debug logs. Load before any command that touches the local WordPress install.
---

# Local WordPress with wp-env

This machine has **no PHP/Composer**; WordPress, PHP and WP-CLI all run in Docker via `@wordpress/env`. Docker Desktop must be running (`docker ps` should succeed).

All commands run from `wp/`.

## First-time setup

```bash
cd wp
npm install                 # installs @wordpress/env, @playwright/test, @axe-core/playwright
npx wp-env start            # dev site http://localhost:8888 (tests site disabled)
npm run setup               # idempotent baseline: scripts/setup.sh inside the cli container
```

GeneratePress, SCF and Yoast are installed by wp-env from `.wp-env.json`; `setup.sh` activates the theme/plugins, removes default content and sets options. `scripts/` is mounted at `wp-content/ogtrips-scripts` (see `mappings`).

Login: http://localhost:8888/wp-admin — `admin` / `password`.

## `.wp-env.json` (reference)

```json
{
  "core": null,
  "testsEnvironment": false,
  "phpVersion": "8.2",
  "themes": [
    "https://downloads.wordpress.org/theme/generatepress.3.6.1.zip",
    "./themes/ogtrips"
  ],
  "plugins": [
    "./plugins/ogtrips-core",
    "https://downloads.wordpress.org/plugin/secure-custom-fields.6.9.5.zip",
    "https://downloads.wordpress.org/plugin/wordpress-seo.28.6.zip"
  ],
  "config": {
    "WP_DEBUG": true,
    "WP_DEBUG_LOG": true,
    "WP_DEBUG_DISPLAY": false,
    "SCRIPT_DEBUG": true,
    "WP_ENVIRONMENT_TYPE": "local"
  }
}
```

Only free wordpress.org themes/plugins are allowed, pinned to exact versioned zips in `.wp-env.json` as above (bump deliberately, never "latest" — the unversioned Yoast zip once served an RC) so every setup is identical. Production stack: **GeneratePress** (parent) + **ogtrips** (child) + **Secure Custom Fields** + **Yoast SEO** + **ogtrips-core**, plus a free cache plugin chosen with hosting. Never add paid/"Pro" plugins, GP Premium, page builders or starter-template importers.

## Everyday commands

| Task | Command |
|---|---|
| Start / stop | `npx wp-env start` · `npx wp-env stop` |
| WP-CLI | `npx wp-env run cli wp <command>` |
| PHP lint a file | `npx wp-env run cli php -l wp-content/themes/ogtrips/functions.php` |
| Debug log | `npx wp-env run cli tail -n 50 wp-content/debug.log` |
| Container logs | `npx wp-env logs` |
| Install free plugin | `npx wp-env run cli wp plugin install query-monitor --activate` |
| Flush permalinks | `npx wp-env run cli wp rewrite flush` |
| Export DB | `npx wp-env run cli wp db export - > backup.sql` |
| Import demo content | `npx wp-env run cli wp ogtrips seed` (custom command, see wp-content-model) |
| Reset DB (destructive — ask first) | `npx wp-env clean development` |
| Remove everything (destructive — ask first) | `npx wp-env destroy` |

Paths inside containers are relative to the WordPress root (`/var/www/html`). In Git Bash, prefix absolute container paths with `MSYS_NO_PATHCONV=1` so they aren't rewritten to Windows paths.

## Recommended dev plugins (local only)

Query Monitor (queries, hooks, PHP errors), Theme Check (theme review rules), Debug Bar optional. Never ship them to production.

## Troubleshooting

- Port 8888 busy → set `"port": 8890` in `.wp-env.override.json`.
- "Docker not running" → start Docker Desktop, retry.
- Theme changes not visible → hard refresh; check `filemtime` versioning; `npx wp-env run cli wp cache flush`.
- White screen → read `debug.log` (above) before changing code.
