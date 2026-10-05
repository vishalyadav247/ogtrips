<?php
/**
 * OgTrips child theme (parent: GeneratePress) — loads the inc/ files only; no logic here.
 *
 * @package ogtrips
 */

defined( 'ABSPATH' ) || exit;

define( 'OGTRIPS_VERSION', '0.1.0' );

require_once get_stylesheet_directory() . '/inc/helpers.php';
require_once get_stylesheet_directory() . '/inc/setup.php';
require_once get_stylesheet_directory() . '/inc/enqueue.php';
require_once get_stylesheet_directory() . '/inc/template-tags.php';
