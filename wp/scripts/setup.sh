#!/bin/sh
# OgTrips — local WordPress baseline. Idempotent: safe to re-run.
# Runs INSIDE the wp-env CLI container (scripts/ is mounted at wp-content/ogtrips-scripts):
#   npm run setup
set -e

# Plain option/post/comment calls don't need plugins or themes loaded (much faster on Docker Desktop).
wpq() { wp --skip-plugins --skip-themes "$@"; }

# Wrapped in main() so sh parses the whole file before running anything (editing it mid-run is safe).
main() {
	echo "== Theme: OgTrips (child of GeneratePress)"
	wp theme activate ogtrips

	echo "== Plugins: Secure Custom Fields, Yoast SEO, OgTrips Core"
	wp plugin activate secure-custom-fields wordpress-seo ogtrips-core
	for p in akismet hello hello-dolly; do
		if wp plugin is-installed "$p"; then
			wp plugin delete "$p"
		fi
	done

	echo "== Default content"
	delete_post_by_slug() {
		ids=$(wpq post list --post_type="$1" --name="$2" --post_status=any --field=ID)
		if [ -n "$ids" ]; then
			wpq post delete $ids --force
		fi
	}
	delete_post_by_slug post hello-world
	delete_post_by_slug page sample-page
	# The Privacy Policy draft has no published slug; WordPress points to it from an option.
	privacy_id=$(wpq option get wp_page_for_privacy_policy)
	if [ "${privacy_id:-0}" -gt 0 ] && wpq post exists "$privacy_id" >/dev/null 2>&1; then
		wpq post delete "$privacy_id" --force
	fi
	ids=$(wpq post list --post_type=page --post_status=draft --title="Privacy Policy" --field=ID)
	if [ -n "$ids" ]; then
		wpq post delete $ids --force
	fi
	# Only WordPress's default comment (author "A WordPress Commenter") — never real/test comments.
	comment_ids=$(wpq comment list --author_email=wapuu@wordpress.example --field=comment_ID)
	if [ -n "$comment_ids" ]; then
		wpq comment delete $comment_ids --force
	fi
	wpq option update wp_page_for_privacy_policy 0

	echo "== Site settings"
	wpq option update blogname "OgTrips"
	wpq option update blogdescription "Where to next?"
	wpq option update timezone_string "Asia/Kolkata"
	wpq option update date_format "j M Y"

	echo "== Permalinks"
	wp rewrite structure '/%postname%/'
	wp rewrite flush

	echo "== Comments closed by default"
	wpq option update default_comment_status closed
	wpq option update default_ping_status closed
	wpq option update default_pingback_flag 0

	echo "== Discourage search engines (local only)"
	wpq option update blog_public 0

	echo "== Done: http://localhost:8888 (admin / password)"
}

main "$@"
