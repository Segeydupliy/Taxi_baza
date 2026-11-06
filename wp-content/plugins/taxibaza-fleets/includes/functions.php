<?php
/**
 * Global template helpers for the theme.
 *
 * @package TaxiBaza\Fleets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tbf_listing_owner' ) ) {
	/**
	 * Owner shown on a listing page when the author is a fleet.
	 *
	 * @param int $post_id Listing ID.
	 * @return array{fleet:bool,name:string}|null Null when the author is not a fleet.
	 */
	function tbf_listing_owner( int $post_id ): ?array {
		return \TaxiBaza\Fleets\Listing_Fleet::owner( $post_id );
	}
}
