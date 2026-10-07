<?php
/**
 * OgTrips child theme (parent: GeneratePress) — loads the inc/ files only; no logic here.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

define( 'OGTRIPS_VERSION', '1.0.0' );

require_once get_stylesheet_directory() . '/inc/helpers.php';
require_once get_stylesheet_directory() . '/inc/data.php';
require_once get_stylesheet_directory() . '/inc/setup.php';
require_once get_stylesheet_directory() . '/inc/enqueue.php';
require_once get_stylesheet_directory() . '/inc/template-tags.php';
require_once get_stylesheet_directory() . '/inc/content.php';
require_once get_stylesheet_directory() . '/inc/query.php';
