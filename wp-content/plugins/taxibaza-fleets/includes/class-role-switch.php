<?php
/**
 * Becoming a fleet goes through moderation.
 *
 * Core lets a car owner switch to `fleet` in the cabinet profile form
 * (Profile::update_profile → set_role). We keep that switch but route it:
 * the new fleet gets status `draft`, its cabinet is locked until the fleet
 * profile is submitted (→ `pending`) and approved by an admin.
 *
 * An admin changing the role in /panel/ or wp-admin is treated the same way
 * (the fleet still has to fill its profile); the admin can approve afterwards.
 *
 * Leaving the fleet role clears the fleet status.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Role_Switch {

	public static function init(): void {
		add_action( 'set_user_role', [ self::class, 'on_set_role' ], 10, 3 );
		add_action( 'add_user_role', [ self::class, 'on_add_role' ], 10, 2 );
		add_action( 'remove_user_role', [ self::class, 'on_remove_role' ], 10, 2 );
	}

	/**
	 * @param int      $user_id   User ID.
	 * @param string   $role      New role.
	 * @param string[] $old_roles Previous roles.
	 */
	public static function on_set_role( $user_id, $role, $old_roles = [] ): void {
		$user_id   = (int) $user_id;
		$was_fleet = in_array( 'fleet', (array) $old_roles, true );
		if ( 'fleet' === $role && ! $was_fleet ) {
			self::became_fleet( $user_id );
		} elseif ( 'fleet' !== $role && $was_fleet ) {
			Status::clear( $user_id );
		}
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $role    Added role.
	 */
	public static function on_add_role( $user_id, $role ): void {
		if ( 'fleet' === $role ) {
			self::became_fleet( (int) $user_id );
		}
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $role    Removed role.
	 */
	public static function on_remove_role( $user_id, $role ): void {
		if ( 'fleet' === $role ) {
			Status::clear( (int) $user_id );
		}
	}

	private static function became_fleet( int $user_id ): void {
		if ( '' !== Status::get( $user_id ) ) {
			return; // Already tracked (e.g. role re-applied).
		}
		Status::set( $user_id, Status::DRAFT );
	}
}
