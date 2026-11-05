<?php
/**
 * Plugin Name: Taxi Rental Core
 * Plugin URI:  https://taxibaza.sk
 * Description: Core data and logic for TaxiBaza.sk — car rental marketplace for taxi work.
 * Version:     1.0.0
 * Author:      TaxiBaza
 * Text Domain: taxi-rental
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 */

namespace TaxiRental\Core;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TAXI_RENTAL_VERSION', '1.0.0' );
define( 'TAXI_RENTAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TAXI_RENTAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'TaxiRental\\Core\\';
	if ( ! str_starts_with( $class, $prefix ) ) {
		return;
	}
	$name = substr( $class, \strlen( $prefix ) );
	$file = TAXI_RENTAL_PLUGIN_DIR . 'includes/class-' . strtolower( $name ) . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

register_activation_hook( __FILE__, [ Seeder::class, 'run' ] );
register_deactivation_hook( __FILE__, [ Roles::class, 'remove_roles' ] );

// One-time migration: seed options defaults for already-active installs.
add_action( 'admin_init', static function (): void {
	if ( ! get_option( 'tb_options_seeded_v2' ) ) {
		Seeder::seed_options_defaults();
	}

	// Migrate account-type labels to descriptive text (v1 → new copy).
	if ( ! get_option( 'tb_migration_type_labels_v1' ) && function_exists( 'update_field' ) ) {
		update_field( 'tb_auth_reg_type_owner', __( 'Фізична особа — оренда 1-2 авто.', 'taxi-rental' ), 'option' );
		update_field( 'tb_auth_reg_type_fleet', __( 'Флотилія — оренда 3+ авто, відображення як компанії.', 'taxi-rental' ), 'option' );
		update_option( 'tb_migration_type_labels_v1', true );
	}

	// One-time: create the driver role on already-active installs (add_roles()
	// otherwise only runs on plugin activation).
	if ( ! get_option( 'tb_migration_driver_role_v1' ) ) {
		Roles::add_roles();

		// Force the driver role display name into the stored roles option.
		global $wp_roles;
		if ( isset( $wp_roles->roles['driver'] ) ) {
			$wp_roles->roles['driver']['name'] = 'Vodič';
			update_option( 'wp_user_roles', $wp_roles->roles );
		}

		update_option( 'tb_migration_driver_role_v1', true );
	}

	// One-time: seed the account-type dropdown role strings.
	Seeder::seed_role_strings();

	// One-time: seed car makes/models + migrate existing listings, then flush
	// rewrite rules so the new car_make taxonomy permalinks resolve.
	if ( ! get_option( 'tb_makes_seeded_v1' ) || ! get_option( 'tb_makes_migrated_v1' ) ) {
		Seeder::seed_car_makes();
		Seeder::migrate_listing_makes();
		flush_rewrite_rules();
	}
} );

add_action( 'plugins_loaded', static function (): void {
	CPT::init();
	Taxonomies::init();
	Roles::init();
	Statuses::init();
	Capabilities::init();
	Account::init();
	Submission::init();
	Queries::init();
	Admin::init();
	Contact::init();
	Requests::init();
	GoogleOAuth::init();
	Profile::init();
	Notifications::init();
	Content::init();

	load_plugin_textdomain( 'taxi-rental', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Prevent blocked users from logging in (runs on all requests, not just admin).
add_filter( 'authenticate', static function ( $user ) {
	if ( ! ( $user instanceof \WP_User ) ) {
		return $user;
	}
	if ( '1' === get_user_meta( $user->ID, 'tb_user_blocked', true ) ) {
		return new \WP_Error(
			'tb_user_blocked',
			__( 'Ваш акаунт заблокований. Зверніться до адміністратора.', 'taxi-rental' )
		);
	}
	return $user;
}, 30 );

add_filter( 'acf/settings/load_json', static function ( array $paths ): array {
	$paths[] = TAXI_RENTAL_PLUGIN_DIR . 'acf-json';
	return $paths;
} );

add_filter( 'acf/settings/save_json', static function (): string {
	return TAXI_RENTAL_PLUGIN_DIR . 'acf-json';
} );
