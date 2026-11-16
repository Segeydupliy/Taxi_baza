<?php
/**
 * Limit of active listings per account (published + waiting for moderation):
 * car owner 5, fleet 30, administrators / moderators without a limit.
 * Drafts don't count and can always be saved. A listing that is already active
 * can always be edited and sent again. Each listing counts once, whatever the
 * number of its translations.
 *
 * Limits: filter `tbf_listing_limit` ( int $limit, int $user_id ).
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Listing_Limits {

	public const OWNER  = 5;
	public const FLEET  = 30;
	public const ACTIVE = [ 'publish', 'pending' ];

	/** For tests: replaces the database count. @var callable|null */
	public static $counter = null;

	public static function init(): void {
		add_action( 'wp_ajax_tb_submit_listing', [ self::class, 'check_submit' ], 0 ); // Before the core handler (10).
	}

	/** 0 = no limit. */
	public static function limit( int $user_id ): int {
		if ( user_can( $user_id, 'edit_others_posts' ) || user_can( $user_id, 'manage_options' ) ) {
			$limit = 0;
		} else {
			$limit = Status::is_fleet( $user_id ) ? self::FLEET : self::OWNER;
		}
		return max( 0, (int) apply_filters( 'tbf_listing_limit', $limit, $user_id ) );
	}

	public static function active_count( int $user_id ): int {
		return self::$counter ? (int) call_user_func( self::$counter, $user_id ) : tbf_count_listings( $user_id, self::ACTIVE );
	}

	/**
	 * Would sending this listing to moderation / publishing go over the limit?
	 *
	 * @param int $post_id 0 for a new listing.
	 */
	public static function over( int $user_id, int $post_id ): bool {
		$limit = self::limit( $user_id );
		if ( ! $limit ) {
			return false;
		}
		if ( $post_id && in_array( (string) get_post_status( $post_id ), self::ACTIVE, true ) ) {
			return false; // Already counted.
		}
		return self::active_count( $user_id ) >= $limit;
	}

	public static function check_submit(): void {
		$set = sanitize_key( wp_unslash( $_POST['set_status'] ?? 'draft' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read only; the core handler verifies its nonce.
		if ( ! in_array( $set, [ 'pending', 'publish' ], true ) ) {
			return;
		}
		$uid     = get_current_user_id();
		$post_id = absint( wp_unslash( $_POST['post_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $post_id ) {
			$author = (int) get_post_field( 'post_author', $post_id );
			$uid    = $author ?: $uid;
		}
		if ( ! $uid || ! self::over( $uid, $post_id ) ) {
			return;
		}
		wp_send_json_error( [ 'message' => sprintf( t( 'limit_reached' ), self::limit( $uid ) ) ] );
	}
}
