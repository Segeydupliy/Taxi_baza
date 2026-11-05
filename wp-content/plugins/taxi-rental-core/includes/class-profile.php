<?php
/**
 * Cabinet profile & settings form handlers.
 *
 * @package TaxiRental\Core
 */

namespace TaxiRental\Core;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles profile edit, avatar, password change and notification
 * preferences submitted from the front-end cabinet (/cabinet/).
 */
final class Profile {

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'admin_post_tb_update_profile', [ self::class, 'update_profile' ] );
		add_action( 'admin_post_tb_change_password', [ self::class, 'change_password' ] );
		add_action( 'admin_post_tb_save_notifications', [ self::class, 'save_notifications' ] );
		add_action( 'admin_post_tb_delete_avatar', [ self::class, 'delete_avatar' ] );
		add_action( 'admin_post_tb_delete_account', [ self::class, 'delete_account' ] );
	}

	/**
	 * Delete the current account together with its listings.
	 */
	public static function delete_account(): void {
		$user_id = self::guard( 'tb_delete_account' );

		// Administrators cannot self-delete from the front-end.
		if ( user_can( $user_id, 'manage_options' ) ) {
			wp_safe_redirect( tb_url( '/cabinet/' ) );
			exit;
		}

		require_once ABSPATH . 'wp-admin/includes/user.php';

		wp_logout();
		wp_delete_user( $user_id ); // No reassign — the user's listings are removed too.

		wp_safe_redirect( add_query_arg( 'action', 'account_deleted', tb_url( '/login/' ) ) );
		exit;
	}

	/**
	 * Redirect back to the cabinet with a status flag.
	 */
	private static function redirect( string $tab, string $flag ): void {
		wp_safe_redirect(
			add_query_arg(
				[
					'tab'  => $tab,
					$flag  => 1,
				],
				tb_url( '/cabinet/' )
			)
		);
		exit;
	}

	/**
	 * Bail out unless a logged-in user posted a valid nonce.
	 */
	private static function guard( string $nonce_action ): int {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( tb_url( '/login/' ) );
			exit;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_die( esc_html__( 'Сесія застаріла. Поверніться назад та спробуйте ще раз.', 'taxi-rental' ) );
		}

		return get_current_user_id();
	}

	/**
	 * Save base profile fields + optional avatar upload.
	 */
	public static function update_profile(): void {
		$user_id = self::guard( 'tb_update_profile' );

		$first_name   = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last_name    = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
		$email        = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone        = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$telegram     = ltrim( sanitize_text_field( wp_unslash( $_POST['telegram'] ?? '' ) ), '@' );
		$whatsapp     = sanitize_text_field( wp_unslash( $_POST['whatsapp'] ?? '' ) );
		$city         = sanitize_text_field( wp_unslash( $_POST['city'] ?? '' ) );
		$account_type = sanitize_key( wp_unslash( $_POST['account_type'] ?? '' ) );
		$about        = sanitize_textarea_field( wp_unslash( $_POST['about'] ?? '' ) );

		if ( '' === $first_name || ! is_email( $email ) ) {
			self::redirect( 'profile', 'profile_error' );
		}

		// Email must stay unique.
		$existing = email_exists( $email );
		if ( $existing && (int) $existing !== $user_id ) {
			self::redirect( 'profile', 'profile_email_taken' );
		}

		wp_update_user(
			[
				'ID'           => $user_id,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'user_email'   => $email,
				'display_name' => trim( $first_name . ' ' . $last_name ),
			]
		);

		update_user_meta( $user_id, 'phone', $phone );
		update_user_meta( $user_id, 'telegram', $telegram );
		update_user_meta( $user_id, 'whatsapp', $whatsapp );
		update_user_meta( $user_id, 'about', $about );

		// #112 — the City input is gone from the profile form. Writing it
		// unconditionally would blank the stored value on every save, so only
		// touch it when the form actually submitted the field.
		if ( array_key_exists( 'city', $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked above.
			update_user_meta( $user_id, 'city', $city );
		}
		update_user_meta( $user_id, 'display_name', trim( $first_name . ' ' . $last_name ) );

		// Account type also switches the role (own listings caps differ).
		if ( \in_array( $account_type, [ 'car_owner', 'fleet', 'driver' ], true ) ) {
			update_user_meta( $user_id, 'account_type', $account_type );
			$user = new \WP_User( $user_id );
			if ( ! \in_array( 'administrator', $user->roles, true ) ) {
				$user->set_role( $account_type );
			}
		}

		// Optional avatar upload.
		if ( ! empty( $_FILES['avatar']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			$attachment_id = media_handle_upload( 'avatar', 0 );
			if ( ! is_wp_error( $attachment_id ) ) {
				$old = (int) get_user_meta( $user_id, 'avatar', true );
				if ( $old ) {
					wp_delete_attachment( $old, true );
				}
				update_user_meta( $user_id, 'avatar', $attachment_id );
			}
		}

		self::redirect( 'profile', 'updated' );
	}

	/**
	 * Remove the custom avatar.
	 */
	public static function delete_avatar(): void {
		$user_id = self::guard( 'tb_delete_avatar' );

		$old = (int) get_user_meta( $user_id, 'avatar', true );
		if ( $old ) {
			wp_delete_attachment( $old, true );
			delete_user_meta( $user_id, 'avatar' );
		}

		self::redirect( 'profile', 'updated' );
	}

	/**
	 * Change the account password (requires the current one).
	 */
	public static function change_password(): void {
		$user_id = self::guard( 'tb_change_password' );
		$user    = get_userdata( $user_id );

		$current = (string) wp_unslash( $_POST['current_password'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- passwords must not be altered.
		$new     = (string) wp_unslash( $_POST['new_password'] ?? '' );     // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$confirm = (string) wp_unslash( $_POST['confirm_password'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( ! $user || ! wp_check_password( $current, $user->user_pass, $user_id ) ) {
			self::redirect( 'settings', 'pass_wrong' );
		}

		if ( \strlen( $new ) < 8 ) {
			self::redirect( 'settings', 'pass_short' );
		}

		if ( $new !== $confirm ) {
			self::redirect( 'settings', 'pass_mismatch' );
		}

		wp_set_password( $new, $user_id );

		// Keep the user logged in after the password change.
		wp_set_auth_cookie( $user_id );

		self::redirect( 'settings', 'pass_changed' );
	}

	/**
	 * Persist notification toggles.
	 */
	public static function save_notifications(): void {
		$user_id = self::guard( 'tb_save_notifications' );

		foreach ( [ 'tb_notify_messages', 'tb_notify_moderation', 'tb_notify_marketing' ] as $key ) {
			update_user_meta( $user_id, $key, empty( $_POST[ $key ] ) ? '0' : '1' );
		}

		self::redirect( 'settings', 'notif_saved' );
	}
}
