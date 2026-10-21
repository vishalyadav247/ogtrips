<?php
/**
 * SCF block "Trip CTA" (ogtrips/trip-cta): the "Bali Bliss, fully planned" box the
 * merchant can drop anywhere in a Tour Guide or Blog post. Fields: group_ogt_trip_cta.json.
 *
 * @package ogtrips-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers blocks from blocks/<name>/block.json (SCF reads the "acf" key).
 */
function ogtrips_core_register_blocks() {
	if ( ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}

	register_block_type( OGTRIPS_CORE_DIR . 'blocks/trip-cta' );
}
add_action( 'init', 'ogtrips_core_register_blocks' );
