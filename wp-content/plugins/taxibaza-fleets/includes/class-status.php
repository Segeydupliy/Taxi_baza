<?php
/**
 * Fleet profile moderation status.
 *
 * Stored in user meta `tbf_status`. Independent from listing moderation
 * (post statuses) and from the core login block (`tb_user_blocked`).
 *
 * Lifecycle:
 *   draft    — account is a fleet, profile not submitted yet (cabinet locked)
 *   pending  — profile submitted, waiting for admin (can add listings, listings hidden)
 *   approved — profile visible, listings visible
 *   rejected — not approved (cabinet locked, listings hidden)
 *   blocked  — blocked by admin (listings hidden, cabinet locked)
 *
 * Edits of an approved profile go to `tbf_profile_draft` while the status stays
 * `approved` and `tbf_has_pending_edits` = 1; the live version keeps showing.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Status {

	public const META          = 'tbf_status';
	public const META_EDITS    = 'tbf_has_pending_edits';
	public const META_CHANGED  = 'tbf_status_changed_at';
	public const HIDDEN_OPTION = 'tbf_hidden_authors';

	public const DRAFT    = 'draft';
	public const PENDING  = 'pending';
	public const APPROVED = 'approved';
	public const REJECTED = 'rejected';
	public const BLOCKED  = 'blocked';

	public const ALL = [ self::DRAFT, self::PENDING, self::APPROVED, self::REJECTED, self::BLOCKED ];

	/** Statuses whose listings are visible in the public catalog. */
	public const VISIBLE = [ self::APPROVED ];

	/** Statuses in which the fleet may create / submit listings. */
	public const CAN_ADD_LISTINGS = [ self::PENDING, self::APPROVED ];

	/** Allowed transitions: from => [to...]. */
	private const TRANSITIONS = [
		''             => [ self::DRAFT, self::APPROVED ],
		self::DRAFT    => [ self::PENDING, self::BLOCKED ],
		self::PENDING  => [ self::APPROVED, self::REJECTED, self::BLOCKED ],
		self::APPROVED => [ self::BLOCKED, self::PENDING ],
		self::REJECTED => [ self::PENDING, self::BLOCKED, self::APPROVED ],
		self::BLOCKED  => [ self::APPROVED, self::PENDING ],
	];

	public static function init(): void {
		// Keep the hidden-authors cache honest if roles change outside our code.
		add_action( 'set_user_role', [ self::class, 'refresh_hidden_authors' ], 99 );
		add_action( 'deleted_user', [ self::class, 'refresh_hidden_authors' ] );
	}

	/**
	 * On activation, existing fleet accounts are grandfathered as approved so
	 * nothing disappears from the live catalog. Admin can re-check them later.
	 */
	public static function on_activate(): void {
		$ids = get_users(
			[
				'role'   => 'fleet',
				'fields' => 'ID',
			]
		);
		foreach ( $ids as $id ) {
			if ( '' === self::get( (int) $id ) ) {
				update_user_meta( (int) $id, self::META, self::APPROVED );
				update_user_meta( (int) $id, self::META_CHANGED, time() );
			}
		}
		self::refresh_hidden_authors();
	}

	public static function is_fleet( int $user_id ): bool {
		$user = get_userdata( $user_id );
		return $user && in_array( 'fleet', (array) $user->roles, true );
	}

	public static function get( int $user_id ): string {
		$status = (string) get_user_meta( $user_id, self::META, true );
		return in_array( $status, self::ALL, true ) ? $status : '';
	}

	public static function can_transition( string $from, string $to ): bool {
		return in_array( $to, self::TRANSITIONS[ $from ] ?? [], true );
	}

	/**
	 * Change status. Returns false when the transition is not allowed.
	 *
	 * @param int    $user_id Fleet user.
	 * @param string $to      Target status.
	 * @param bool   $force   Skip transition rules (activation / repair only).
	 */
	public static function set( int $user_id, string $to, bool $force = false ): bool {
		if ( ! in_array( $to, self::ALL, true ) ) {
			return false;
		}
		$from = self::get( $user_id );
		if ( $from === $to ) {
			return true;
		}
		if ( ! $force && ! self::can_transition( $from, $to ) ) {
			return false;
		}
		update_user_meta( $user_id, self::META, $to );
		update_user_meta( $user_id, self::META_CHANGED, time() );
		self::refresh_hidden_authors();

		/**
		 * Fires after a fleet profile status changed.
		 *
		 * @param int    $user_id Fleet user ID.
		 * @param string $to      New status.
		 * @param string $from    Previous status ('' when none).
		 */
		do_action( 'tbf_status_changed', $user_id, $to, $from );
		return true;
	}

	/** Remove fleet status (user is no longer a fleet). */
	public static function clear( int $user_id ): void {
		delete_user_meta( $user_id, self::META );
		delete_user_meta( $user_id, self::META_EDITS );
		self::refresh_hidden_authors();
	}

	public static function listings_visible( int $user_id ): bool {
		if ( ! self::is_fleet( $user_id ) ) {
			return true; // Not our business: car owners are moderated per listing by core.
		}
		return in_array( self::get( $user_id ), self::VISIBLE, true );
	}

	public static function can_add_listings( int $user_id ): bool {
		if ( ! self::is_fleet( $user_id ) ) {
			return true;
		}
		return in_array( self::get( $user_id ), self::CAN_ADD_LISTINGS, true );
	}

	/**
	 * IDs of fleet users whose listings must be hidden from the public catalog.
	 *
	 * @return int[]
	 */
	public static function hidden_authors(): array {
		$ids = get_option( self::HIDDEN_OPTION, null );
		if ( ! is_array( $ids ) ) {
			$ids = self::refresh_hidden_authors();
		}
		return array_values( array_map( 'intval', $ids ) );
	}

	/**
	 * Rebuild the cached list of hidden fleet authors.
	 *
	 * @return int[]
	 */
	public static function refresh_hidden_authors(): array {
		$fleet_ids = get_users(
			[
				'role'   => 'fleet',
				'fields' => 'ID',
			]
		);
		$hidden    = [];
		foreach ( $fleet_ids as $id ) {
			$id = (int) $id;
			if ( ! in_array( self::get( $id ), self::VISIBLE, true ) ) {
				$hidden[] = $id;
			}
		}
		sort( $hidden );
		update_option( self::HIDDEN_OPTION, $hidden, true );
		return $hidden;
	}
}
