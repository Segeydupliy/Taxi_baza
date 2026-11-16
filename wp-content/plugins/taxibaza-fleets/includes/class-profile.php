<?php
/**
 * Fleet profile data: schema, sanitising, validation, storage and moderation flow.
 *
 * Storage (user meta):
 *   tbf_profile_live  — approved version, shown to drivers
 *   tbf_profile_draft — submitted version waiting for moderation
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Profile {

	public const META_LIVE  = 'tbf_profile_live';
	public const META_DRAFT = 'tbf_profile_draft';
	public const NONCE      = 'tbf_save_profile';

	/** Allowed values for choice fields. */
	public const CHOICES = [
		'platforms'       => [ 'uber', 'bolt', 'hopin' ],
		'langs'           => [ 'sk', 'uk', 'cs', 'en', 'de', 'ru' ],
		'models'          => [ 'own', 'fleet' ],
		'own_pay_type'    => [ 'percent', 'fixed', 'after' ],
		'own_fee_base'    => [ 'gross', 'net' ],
		'rent_period'     => [ 'week', 'day' ],
		'rent_dep_return' => [ 'handover', '7', '14' ],
		'rent_min_unit'   => [ 'days', 'weeks', 'months' ],
		'rent_ext'        => [ 'weekly', 'daily', 'monthly' ],
		'inc'             => [ 'insurance', 'service', 'tires', 'wash' ],
		'pay_freq'        => [ 'weekly', 'daily', 'biweekly', 'monthly' ],
		'pay_day'         => [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ],
		'pay_method'      => [ 'bank', 'cash' ],
		'fuel_unit'       => [ 'cents', 'percent' ],
		'for'             => [ 'own', 'fleet' ],
		'svc_where'       => [ 'partner', 'own' ],
		'svc_what'        => [ 'service', 'tires', 'parts', 'wash' ],
		'con_what'        => [ 'help', 'do' ],
		'con_cost'        => [ 'free', 'compensate', 'paid' ],
		'req_age'         => [ '18', '21', '25', '30' ],
		'req_exp'         => [ '0', '1', '2', '3', '5', '7', '10' ],
		'docs'            => [ 'id', 'license', 'record' ],
	];

	public static function init(): void {
		add_action( 'admin_post_tbf_save_profile', [ self::class, 'handle_save' ] );
	}

	/** @return array<string,mixed> */
	public static function defaults(): array {
		return [
			'name'            => '',
			'phone'           => '',
			'ico'             => '',
			'telegram'        => '',
			'whatsapp'        => '',
			'description'     => '',
			'logo_id'         => 0,
			'platforms'       => [],
			'langs'           => [],
			'models'          => [],
			'own_pay_type'    => 'percent',
			'own_fee'         => '',
			'own_fee_base'    => 'gross',
			'rent_price'      => '',
			'rent_period'     => 'week',
			'rent_comm_on'    => false,
			'rent_comm'       => '',
			'rent_dep_on'     => false,
			'rent_dep'        => '',
			'rent_dep_return' => 'handover',
			'rent_min'        => '',
			'rent_min_unit'   => 'days',
			'rent_ext'        => 'weekly',
			'inc'             => [],
			'pay_freq'        => 'weekly',
			'pay_day'         => 'mon',
			'pay_method'      => [],
			'perk_fuel'       => false,
			'fuel_discount'   => '',
			'fuel_unit'       => 'cents',
			'fuel_network'    => '',
			'fuel_for'        => [],
			'perk_service'    => false,
			'svc_discount'    => '',
			'svc_where'       => 'partner',
			'svc_what'        => [],
			'svc_for'         => [],
			'perk_concession' => false,
			'con_what'        => 'help',
			'con_cost'        => 'free',
			'con_for'         => [],
			'req_age'         => '21',
			'req_exp'         => '0',
			'docs'            => [],
			'req_other'       => '',
		];
	}

	/** @return array<string,mixed> */
	public static function get_live( int $user_id ): array {
		$v = get_user_meta( $user_id, self::META_LIVE, true );
		return is_array( $v ) ? array_merge( self::defaults(), $v ) : [];
	}

	/** @return array<string,mixed> */
	public static function get_draft( int $user_id ): array {
		$v = get_user_meta( $user_id, self::META_DRAFT, true );
		return is_array( $v ) ? array_merge( self::defaults(), $v ) : [];
	}

	/**
	 * Values for the edit form: pending draft, else live, else core account data.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_for_form( int $user_id ): array {
		$draft = self::get_draft( $user_id );
		if ( $draft ) {
			return $draft;
		}
		$live = self::get_live( $user_id );
		if ( $live ) {
			return $live;
		}
		$user = get_userdata( $user_id );
		return array_merge(
			self::defaults(),
			[
				'name'        => (string) get_user_meta( $user_id, 'company_name', true ) ?: ( $user ? (string) $user->display_name : '' ),
				'phone'       => (string) get_user_meta( $user_id, 'phone', true ),
				'telegram'    => ltrim( (string) get_user_meta( $user_id, 'telegram', true ), '@' ),
				'whatsapp'    => (string) get_user_meta( $user_id, 'whatsapp', true ),
				'description' => (string) get_user_meta( $user_id, 'about', true ),
				'logo_id'     => (int) get_user_meta( $user_id, 'avatar', true ),
			]
		);
	}

	/**
	 * Sanitise raw POST data into a profile array. Unknown keys are dropped.
	 *
	 * @param array<string,mixed> $raw Raw input (already unslashed).
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $raw ): array {
		$text  = static fn( $k, $max = 200 ) => mb_substr( sanitize_text_field( (string) ( $raw[ $k ] ?? '' ) ), 0, $max );
		$num   = static function ( $k ) use ( $raw ) {
			$v = str_replace( ',', '.', trim( (string) ( $raw[ $k ] ?? '' ) ) );
			return is_numeric( $v ) ? (string) ( 0 + $v ) : '';
		};
		$one   = static function ( $k, $choices_key = null ) use ( $raw ) {
			$allowed = self::CHOICES[ $choices_key ?? $k ];
			$v       = (string) ( $raw[ $k ] ?? '' );
			return in_array( $v, $allowed, true ) ? $v : $allowed[0];
		};
		$many  = static function ( $k, $choices_key = null ) use ( $raw ) {
			$allowed = self::CHOICES[ $choices_key ?? $k ];
			$v       = array_map( 'strval', (array) ( $raw[ $k ] ?? [] ) );
			return array_values( array_intersect( $allowed, $v ) );
		};
		$bool  = static fn( $k ) => ! empty( $raw[ $k ] );

		return [
			'name'            => $text( 'name', 120 ),
			'phone'           => preg_replace( '/[^\d+ ]/', '', $text( 'phone', 30 ) ),
			'ico'             => preg_replace( '/[^\d ]/', '', $text( 'ico', 20 ) ),
			'telegram'        => ltrim( preg_replace( '/[^\w.@]/u', '', $text( 'telegram', 64 ) ), '@' ),
			'whatsapp'        => preg_replace( '/[^\w.+ ]/u', '', $text( 'whatsapp', 64 ) ),
			'description'     => mb_substr( sanitize_textarea_field( (string) ( $raw['description'] ?? '' ) ), 0, 2000 ),
			'logo_id'         => absint( $raw['logo_id'] ?? 0 ),
			'platforms'       => $many( 'platforms' ),
			'langs'           => $many( 'langs' ),
			'models'          => $many( 'models' ),
			'own_pay_type'    => $one( 'own_pay_type' ),
			'own_fee'         => $num( 'own_fee' ),
			'own_fee_base'    => $one( 'own_fee_base' ),
			'rent_price'      => $num( 'rent_price' ),
			'rent_period'     => $one( 'rent_period' ),
			'rent_comm_on'    => $bool( 'rent_comm_on' ),
			'rent_comm'       => $num( 'rent_comm' ),
			'rent_dep_on'     => $bool( 'rent_dep_on' ),
			'rent_dep'        => $num( 'rent_dep' ),
			'rent_dep_return' => $one( 'rent_dep_return' ),
			'rent_min'        => $num( 'rent_min' ),
			'rent_min_unit'   => $one( 'rent_min_unit' ),
			'rent_ext'        => $one( 'rent_ext' ),
			'inc'             => $many( 'inc' ),
			'pay_freq'        => $one( 'pay_freq' ),
			'pay_day'         => $one( 'pay_day' ),
			'pay_method'      => $many( 'pay_method' ),
			'perk_fuel'       => $bool( 'perk_fuel' ),
			'fuel_discount'   => $num( 'fuel_discount' ),
			'fuel_unit'       => $one( 'fuel_unit' ),
			'fuel_network'    => $text( 'fuel_network', 80 ),
			'fuel_for'        => $many( 'fuel_for', 'for' ),
			'perk_service'    => $bool( 'perk_service' ),
			'svc_discount'    => $num( 'svc_discount' ),
			'svc_where'       => $one( 'svc_where' ),
			'svc_what'        => $many( 'svc_what' ),
			'svc_for'         => $many( 'svc_for', 'for' ),
			'perk_concession' => $bool( 'perk_concession' ),
			'con_what'        => $one( 'con_what' ),
			'con_cost'        => $one( 'con_cost' ),
			'con_for'         => $many( 'con_for', 'for' ),
			'req_age'         => $one( 'req_age' ),
			'req_exp'         => $one( 'req_exp' ),
			'docs'            => $many( 'docs' ),
			'req_other'       => $text( 'req_other', 1000 ),
		];
	}

	/**
	 * Validate a sanitised profile. Returns field => error key.
	 *
	 * @param array<string,mixed> $p Profile.
	 * @return array<string,string>
	 */
	public static function validate( array $p ): array {
		$e     = [];
		$range = static function ( string $k, float $min, float $max ) use ( $p, &$e ) {
			if ( '' === $p[ $k ] || (float) $p[ $k ] < $min || (float) $p[ $k ] > $max ) {
				$e[ $k ] = 'err_number';
			}
		};
		if ( mb_strlen( $p['name'] ) < 2 ) {
			$e['name'] = 'err_required';
		}
		if ( '' !== $p['phone'] && ! preg_match( '/^\+?\d[\d ]{7,18}$/', $p['phone'] ) ) {
			$e['phone'] = 'err_phone';
		}
		foreach ( [ 'platforms', 'langs', 'models', 'pay_method' ] as $k ) {
			if ( ! $p[ $k ] ) {
				$e[ $k ] = 'err_pick_one';
			}
		}
		if ( in_array( 'own', $p['models'], true ) ) {
			'fixed' === $p['own_pay_type'] ? $range( 'own_fee', 0, 100000 ) : $range( 'own_fee', 0, 100 );
		}
		// Fleet cars: prices differ per car and are given in each listing, so the
		// profile has no rent fields any more. Discounts are optional details.
		$opt = static function ( string $k, float $max ) use ( $p, &$e ) {
			if ( '' !== (string) $p[ $k ] && ( (float) $p[ $k ] < 0 || (float) $p[ $k ] > $max ) ) {
				$e[ $k ] = 'err_number';
			}
		};
		if ( $p['perk_fuel'] ) {
			$opt( 'fuel_discount', 100 );
		}
		if ( $p['perk_service'] ) {
			$opt( 'svc_discount', 100 );
		}
		return $e;
	}

	/** Can the fleet edit its profile right now? */
	public static function can_edit( int $user_id ): bool {
		return in_array( Status::get( $user_id ), [ Status::DRAFT, Status::PENDING, Status::APPROVED ], true );
	}

	/**
	 * Store a submitted profile and move the moderation state.
	 *
	 * @param array<string,mixed> $profile Sanitised, valid profile.
	 * @return string 'submitted' | 'edits' | ''
	 */
	public static function submit( int $user_id, array $profile ): string {
		$status = Status::get( $user_id );
		update_user_meta( $user_id, self::META_DRAFT, $profile );

		if ( Status::APPROVED === $status ) {
			update_user_meta( $user_id, Status::META_EDITS, 1 );
			do_action( 'tbf_profile_submitted', $user_id, 'edits' );
			return 'edits';
		}
		if ( Status::DRAFT === $status ) {
			Status::set( $user_id, Status::PENDING );
		}
		do_action( 'tbf_profile_submitted', $user_id, 'new' );
		return 'submitted';
	}

	/**
	 * Admin approves: draft becomes live, core account fields are synced.
	 */
	public static function approve( int $user_id ): bool {
		$draft = self::get_draft( $user_id );
		if ( $draft ) {
			update_user_meta( $user_id, self::META_LIVE, $draft );
			delete_user_meta( $user_id, self::META_DRAFT );
			self::sync_core( $user_id, $draft );
		}
		delete_user_meta( $user_id, Status::META_EDITS );
		if ( Status::APPROVED !== Status::get( $user_id ) ) {
			return Status::set( $user_id, Status::APPROVED );
		}
		do_action( 'tbf_status_changed', $user_id, Status::APPROVED, Status::APPROVED );
		return true;
	}

	/** Admin rejects pending edits of an approved fleet: live stays, draft is dropped. */
	public static function reject_edits( int $user_id ): void {
		delete_user_meta( $user_id, self::META_DRAFT );
		delete_user_meta( $user_id, Status::META_EDITS );
	}

	public static function has_pending_edits( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, Status::META_EDITS, true );
	}

	/**
	 * Copy approved data into the fields the core theme already shows (owner box etc.).
	 *
	 * @param array<string,mixed> $p Profile.
	 */
	private static function sync_core( int $user_id, array $p ): void {
		update_user_meta( $user_id, 'company_name', $p['name'] );
		update_user_meta( $user_id, 'phone', $p['phone'] );
		update_user_meta( $user_id, 'telegram', $p['telegram'] );
		update_user_meta( $user_id, 'whatsapp', $p['whatsapp'] );
		update_user_meta( $user_id, 'about', $p['description'] );
		if ( $p['logo_id'] ) {
			update_user_meta( $user_id, 'avatar', (int) $p['logo_id'] );
		}
		if ( '' !== $p['name'] && function_exists( 'wp_update_user' ) ) {
			wp_update_user(
				[
					'ID'           => $user_id,
					'display_name' => $p['name'],
				]
			);
		}
	}

	/** POST handler for the cabinet form. */
	public static function handle_save(): void {
		$admin_for = isset( $_POST['tbf_admin_fleet'] ) ? absint( $_POST['tbf_admin_fleet'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked below.
		if ( $admin_for ) {
			self::handle_admin_save( $admin_for );
			return;
		}
		$user_id = get_current_user_id();
		$back    = Cabinet::profile_url();

		if ( ! $user_id || ! Status::is_fleet( $user_id ) ) {
			wp_safe_redirect( $back );
			exit;
		}
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) || ! self::can_edit( $user_id ) ) {
			wp_safe_redirect( add_query_arg( 'tbf_error', 'server', $back ) );
			exit;
		}

		$raw = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field in sanitize().
		$raw['logo_id'] = self::handle_logo( $user_id, (int) ( $raw['logo_id'] ?? 0 ), ! empty( $raw['logo_delete'] ) );
		$profile        = self::sanitize( (array) $raw );
		$errors         = self::validate( $profile );
		if ( $errors ) {
			set_transient( 'tbf_form_' . $user_id, [ 'values' => $profile, 'errors' => $errors ], 10 * MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( [ 'edit' => 1, 'tbf_error' => 'form' ], $back ) );
			exit;
		}
		$result = self::submit( $user_id, $profile );
		wp_safe_redirect( add_query_arg( 'tbf_saved', $result, $back ) );
		exit;
	}

	/**
	 * An admin edits a fleet's profile from the panel (like "Edit" on a listing
	 * in moderation). A profile waiting for moderation (new or edits) is changed
	 * in place and stays in the queue; an approved profile without pending edits
	 * is updated directly. No moderation request, no email to the fleet.
	 */
	public static function handle_admin_save( int $fleet ): void {
		$panel = Admin_Panel::panel_url( [ 'fleet' => $fleet ] );
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! current_user_can( 'manage_options' ) || ! Status::is_fleet( $fleet ) || ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_safe_redirect( add_query_arg( 'tbf_done', 'error', $panel ) );
			exit;
		}
		$raw            = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised field by field in sanitize().
		$raw['logo_id'] = self::handle_logo( $fleet, (int) ( $raw['logo_id'] ?? 0 ), ! empty( $raw['logo_delete'] ) );
		$profile        = self::sanitize( (array) $raw );
		$errors         = self::validate( $profile );
		if ( $errors ) {
			set_transient( 'tbf_form_admin_' . $fleet, [ 'values' => $profile, 'errors' => $errors ], 10 * MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( [ 'edit' => 1, 'tbf_error' => 'form' ], $panel ) );
			exit;
		}
		self::admin_store( $fleet, $profile );
		wp_safe_redirect( add_query_arg( 'tbf_done', 'saved', $panel ) );
		exit;
	}

	/**
	 * Where an admin's edit goes: the version under moderation, else the live one.
	 *
	 * @param array<string,mixed> $profile Sanitised, valid profile.
	 * @return string 'draft' | 'live'
	 */
	public static function admin_store( int $fleet, array $profile ): string {
		if ( self::get_draft( $fleet ) || ! self::get_live( $fleet ) ) {
			update_user_meta( $fleet, self::META_DRAFT, $profile );
			return 'draft';
		}
		update_user_meta( $fleet, self::META_LIVE, $profile );
		self::sync_core( $fleet, $profile );
		return 'live';
	}

	/**
	 * Logo upload / delete. Returns attachment ID to store.
	 */
	private static function handle_logo( int $user_id, int $current, bool $delete ): int {
		if ( $delete ) {
			return 0;
		}
		if ( empty( $_FILES['logo']['name'] ) || ! empty( $_FILES['logo']['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked in handle_save().
			return $current;
		}
		$type = wp_check_filetype( sanitize_file_name( wp_unslash( $_FILES['logo']['name'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$size = (int) ( $_FILES['logo']['size'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! in_array( $type['type'], [ 'image/jpeg', 'image/png', 'image/webp' ], true ) || $size > 5 * MB_IN_BYTES ) {
			return $current;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$id = media_handle_upload( 'logo', 0, [ 'post_author' => $user_id ] );
		return is_wp_error( $id ) ? $current : (int) $id;
	}
}
