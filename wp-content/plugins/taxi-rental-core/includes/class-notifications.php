<?php
/**
 * Email notifications: registration, submission, moderation.
 *
 * Templates are editable via WP-Admin → TaxiBaza → Email-šablóny (ACF options).
 * Each template has a subject and body field with {{placeholder}} syntax.
 *
 * @package TaxiRental\Core
 */

namespace TaxiRental\Core;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Phase 12 — transactional emails.
 *
 * Moderation emails respect the user's `tb_notify_moderation` toggle
 * (unset = on); the admin copy is always sent.
 */
final class Notifications {

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'user_register', [ self::class, 'on_user_register' ], 20 );
		add_action( 'transition_post_status', [ self::class, 'listing_status' ], 10, 3 );
	}

	// -------------------------------------------------------------------------
	// Public helpers (used by Contact class)
	// -------------------------------------------------------------------------

	/**
	 * True when the user opted in (or never touched the toggle).
	 */
	public static function wants( int $user_id, string $meta_key ): bool {
		return '0' !== (string) get_user_meta( $user_id, $meta_key, true );
	}

	// -------------------------------------------------------------------------
	// Hooks
	// -------------------------------------------------------------------------

	/**
	 * On new user: welcome email → owner/fleet, notification → admin.
	 */
	public static function on_user_register( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$is_owner_role = (bool) array_intersect( [ 'car_owner', 'fleet' ], (array) $user->roles );

		// Welcome email to the new owner / fleet.
		if ( $is_owner_role ) {
			[ $subj, $html, $headers ] = self::render(
				'welcome',
				[
					'name'            => $user->display_name,
					'cabinet_url'     => tb_url( '/cabinet/' ),
					'add_listing_url' => tb_url( '/cabinet/add-listing/' ),
				]
			);
			wp_mail( $user->user_email, $subj, $html, $headers );
		}

		// Admin notification — new user registered.
		$role_labels = [
			'car_owner'    => 'Vlastník auta',
			'fleet'        => 'Flotila',
			'administrator' => 'Administrátor',
		];
		$role_key   = ! empty( $user->roles ) ? (string) reset( $user->roles ) : '';
		$role_label = $role_labels[ $role_key ] ?? $role_key;

		[ $subj, $html, $headers ] = self::render(
			'new_user_admin',
			[
				'name'      => $user->display_name,
				'email'     => $user->user_email,
				'role'      => $role_label,
				'admin_url' => admin_url( 'user-edit.php?user_id=' . $user_id ),
			]
		);
		wp_mail( get_option( 'admin_email' ), $subj, $html, $headers );
	}

	/**
	 * Listing moderation emails on status transitions.
	 */
	public static function listing_status( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( 'car_listing' !== $post->post_type || $new_status === $old_status ) {
			return;
		}

		$owner = get_userdata( (int) $post->post_author );
		if ( ! $owner ) {
			return;
		}

		$title = $post->post_title ?: __( '(bez názvu)', 'taxi-rental' );

		// New submission waiting for moderation.
		if ( 'pending' === $new_status ) {
			[ $subj, $html, $headers ] = self::render(
				'pending_admin',
				[
					'title'          => $title,
					'owner_name'     => $owner->display_name,
					'moderation_url' => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
				]
			);
			wp_mail( get_option( 'admin_email' ), $subj, $html, $headers );

			if ( self::wants( $owner->ID, 'tb_notify_moderation' ) ) {
				[ $subj, $html, $headers ] = self::render(
					'pending_owner',
					[
						'name'  => $owner->display_name,
						'title' => $title,
					]
				);
				wp_mail( $owner->user_email, $subj, $html, $headers );
			}
			return;
		}

		// Approved & published.
		if ( 'publish' === $new_status && \in_array( $old_status, [ 'pending', 'draft', 'rejected' ], true ) ) {
			if ( self::wants( $owner->ID, 'tb_notify_moderation' ) ) {
				[ $subj, $html, $headers ] = self::render(
					'published',
					[
						'name'        => $owner->display_name,
						'title'       => $title,
						'listing_url' => (string) get_permalink( $post ),
					]
				);
				wp_mail( $owner->user_email, $subj, $html, $headers );
			}
			return;
		}

		// Rejected — always notify, the owner must see the reason.
		if ( 'rejected' === $new_status ) {
			// Core stores the reason as `_rejection_reason`; keep the old key as a fallback.
			$reason = (string) get_post_meta( $post->ID, '_rejection_reason', true );
			if ( '' === $reason ) {
				$reason = (string) get_post_meta( $post->ID, 'rejection_reason', true );
			}

			[ $subj, $html, $headers ] = self::render(
				'rejected',
				[
					'name'     => $owner->display_name,
					'title'    => $title,
					'reason'   => $reason ?: __( 'neuvedený', 'taxi-rental' ),
					'edit_url' => add_query_arg( [ 'post_id' => $post->ID ], tb_url( '/cabinet/add-listing/' ) ),
				]
			);
			wp_mail( $owner->user_email, $subj, $html, $headers );
		}
	}

	// -------------------------------------------------------------------------
	// Template engine
	// -------------------------------------------------------------------------

	/**
	 * Resolve subject + body from ACF options, replace {{placeholders}}, wrap in HTML.
	 *
	 * @param  string  $type  Template key suffix (e.g. 'welcome', 'rejected').
	 * @param  array<string,string> $vars  Placeholder → value map.
	 * @return array{0:string, 1:string, 2:string[]}  [subject, html_body, headers]
	 */
	public static function render( string $type, array $vars = [] ): array {
		$subject = self::opt( 'tb_email_' . $type . '_subject' );
		$body    = self::opt( 'tb_email_' . $type . '_body' );

		foreach ( $vars as $key => $value ) {
			$subject = str_replace( '{{' . $key . '}}', (string) $value, $subject );
			$body    = str_replace( '{{' . $key . '}}', (string) $value, $body );
		}

		$html = self::html_wrap( nl2br( esc_html( $body ) ) );

		return [
			$subject,
			$html,
			[ 'Content-Type: text/html; charset=UTF-8' ],
		];
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Read a string option from ACF (options), fallback to empty string.
	 * Does NOT depend on the theme's tb_opt() helper.
	 *
	 * Falls back to a direct wp_options read when WPML has switched the language
	 * context to a language for which no per-language ACF option exists (e.g. only
	 * the default Slovak value was seeded). Then applies the WPML translation filter
	 * so String Translation overrides are respected.
	 */
	private static function opt( string $key ): string {
		$val = '';

		if ( \function_exists( 'get_field' ) ) {
			$val = (string) get_field( $key, 'option' );
		}

		// WPML may have switched language so get_field() returns empty.
		// Read the language-independent default directly from wp_options.
		if ( '' === $val ) {
			$val = (string) get_option( 'options_' . $key, '' );
		}

		// Re-register on every read (like the theme's tb_opt()) so an edit to the
		// Slovak source in wp-admin is picked up by WPML String Translation instead
		// of staying pinned to whatever value existed at plugin activation.
		if ( '' !== $val ) {
			// Explicit source language — see tb_opt() in functions.php for why.
			do_action( 'wpml_register_single_string', 'taxi-rental-options', $key, $val, false, 'sk' );
			$val = (string) apply_filters( 'wpml_translate_single_string', $val, 'taxi-rental-options', $key );
		}

		return $val;
	}

	/**
	 * Wrap plain-text body (already nl2br'd + escaped) in a minimal HTML email shell.
	 */
	private static function html_wrap( string $content ): string {
		$site_name = esc_html( get_bloginfo( 'name' ) );
		$site_url  = esc_url( home_url( '/' ) );
		$year      = (int) gmdate( 'Y' );

		return '<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . $site_name . '</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 16px;">
<tr><td align="center">
<table width="100%" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e5e5;">
  <tr>
    <td style="background:#000000;padding:24px 40px;">
      <a href="' . $site_url . '" style="text-decoration:none;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">
        TaxiBaza<span style="color:#02F797;">.sk</span>
      </a>
    </td>
  </tr>
  <tr>
    <td style="padding:40px;color:#1a1a1a;font-size:15px;line-height:26px;">
      ' . $content . '
    </td>
  </tr>
  <tr>
    <td style="background:#f4f4f4;padding:20px 40px;text-align:center;font-size:12px;color:#999999;border-top:1px solid #e5e5e5;">
      &copy; ' . $year . ' <a href="' . $site_url . '" style="color:#999999;text-decoration:none;">' . $site_name . '</a>
    </td>
  </tr>
</table>
</td></tr>
</table>
</body>
</html>';
	}
}
