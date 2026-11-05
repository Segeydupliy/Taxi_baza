<?php

namespace TaxiRental\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Roles {
	/**
	 * Account type given to every self-registered user.
	 *
	 * Registration does not ask for a type any more — sign-up always creates a
	 * car owner. Converting an account to `fleet` is a deliberate, manual step
	 * (profile edit in the cabinet, or the admin panel), never something the
	 * sign-up form can do.
	 */
	public const DEFAULT_ACCOUNT_TYPE = 'car_owner';

	/**
	 * Base capabilities shared by car_owner and fleet.
	 * No edit_others_car_listings — owners manage only their own posts.
	 */
	private const CAPS = [
		'read'                        => true,
		'edit_car_listings'           => true,
		'edit_published_car_listings' => true,
		'delete_car_listings'         => true,
		'upload_files'                => true,
		// Explicitly denied — kept for clarity.
		'publish_car_listings'        => false,
		'edit_others_car_listings'    => false,
		'delete_others_car_listings'  => false,
		'delete_published_car_listings' => true,
	];

	/**
	 * Capabilities for the driver role — a regular user who can only browse
	 * listings and manage their own profile. No car_listing management.
	 */
	private const DRIVER_CAPS = [
		'read'         => true,
		'upload_files' => true, // Needed for uploading their own profile avatar.
	];

	/** All primitive caps the administrator must have to manage car_listing CPT. */
	private const ADMIN_CAPS = [
		'edit_car_listings',
		'edit_others_car_listings',
		'edit_published_car_listings',
		'edit_private_car_listings',
		'read_private_car_listings',
		'publish_car_listings',
		'delete_car_listings',
		'delete_others_car_listings',
		'delete_published_car_listings',
		'delete_private_car_listings',
	];

	public static function init(): void {
		// Grant caps to administrator on every request — cheap: only writes to DB if cap is missing.
		add_action( 'init', [ self::class, 'grant_admin_caps' ], 1 );
	}

	public static function grant_admin_caps(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}
		foreach ( self::ADMIN_CAPS as $cap ) {
			if ( empty( $admin->capabilities[ $cap ] ) ) {
				$admin->add_cap( $cap, true );
			}
		}
	}

	public static function add_roles(): void {
		if ( ! get_role( 'car_owner' ) ) {
			add_role(
				'car_owner',
				__( 'Car Owner', 'taxi-rental' ),
				self::CAPS
			);
		}

		if ( ! get_role( 'fleet' ) ) {
			add_role(
				'fleet',
				__( 'Fleet', 'taxi-rental' ),
				self::CAPS
			);
		}

		if ( ! get_role( 'driver' ) ) {
			add_role(
				'driver',
				__( 'Driver', 'taxi-rental' ),
				self::DRIVER_CAPS
			);
		}

		self::grant_admin_caps();
	}

	public static function remove_roles(): void {
		remove_role( 'car_owner' );
		remove_role( 'fleet' );
		remove_role( 'driver' );
	}
}
