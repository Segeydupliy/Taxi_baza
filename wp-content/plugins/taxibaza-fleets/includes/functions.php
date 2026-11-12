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

if ( ! function_exists( 'tbf_count_listings' ) ) {
	/**
	 * Listings of an author, one per WPML translation group (the original), so
	 * the number is the same in every language and translations don't count twice.
	 *
	 * @param int      $user_id  Author.
	 * @param string[] $statuses Post statuses.
	 */
	function tbf_count_listings( int $user_id, array $statuses = [ 'publish' ] ): int {
		global $wpdb;
		$statuses = array_values( array_filter( array_map( 'sanitize_key', $statuses ) ) );
		if ( ! $statuses ) {
			return 0;
		}
		$in    = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$table = $wpdb->prefix . 'icl_translations';
		if ( ! has_filter( 'wpml_current_language' ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'car_listing' AND post_author = %d AND post_status IN ($in)", $user_id, ...$statuses ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				 LEFT JOIN {$table} t ON t.element_id = p.ID AND t.element_type = 'post_car_listing'
				 WHERE p.post_type = 'car_listing' AND p.post_author = %d AND p.post_status IN ($in)
				   AND ( t.translation_id IS NULL OR t.source_language_code IS NULL )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				...$statuses
			)
		);
	}
}
