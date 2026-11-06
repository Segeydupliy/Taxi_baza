<?php
/**
 * Email to the fleet when its profile (or a later edit) passes moderation.
 *
 * Sent in the language the fleet used when it submitted the profile (meta
 * `tbf_lang`), with the same look as the core's moderation emails. Respects the
 * user's "moderation notifications" toggle in the cabinet (`tb_notify_moderation`).
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Mailer {

	public const META_LANG = 'tbf_lang';

	public static function init(): void {
		add_action( 'tbf_profile_submitted', [ self::class, 'remember_lang' ], 5 );
		add_action( 'tbf_status_changed', [ self::class, 'on_status' ], 30, 3 );
	}

	/**
	 * @param int $fleet Fleet user.
	 */
	public static function remember_lang( $fleet ): void {
		update_user_meta( (int) $fleet, self::META_LANG, self::form_lang() );
	}

	/**
	 * Language of the page the profile form was filled in on. The form posts to
	 * admin-post.php, where WPML only knows the default language, so the page's
	 * language travels in a hidden field (nonce is checked in Profile::handle_save()).
	 */
	public static function form_lang(): string {
		$posted = isset( $_POST['tbf_lang'] ) ? sanitize_key( wp_unslash( $_POST['tbf_lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( I18n::LANGS[ $posted ] ) ? $posted : I18n::lang();
	}

	private static function wants( int $user_id ): bool {
		if ( class_exists( '\TaxiRental\Core\Notifications' ) ) {
			return \TaxiRental\Core\Notifications::wants( $user_id, 'tb_notify_moderation' );
		}
		return '0' !== (string) get_user_meta( $user_id, 'tb_notify_moderation', true );
	}

	/**
	 * @param int    $fleet Fleet user.
	 * @param string $to    New status.
	 * @param string $from  Old status.
	 */
	public static function on_status( $fleet, $to, $from ): bool {
		$fleet = (int) $fleet;
		if ( Status::APPROVED !== $to || ! self::wants( $fleet ) ) {
			return false;
		}
		$user = get_userdata( $fleet );
		if ( ! $user || empty( $user->user_email ) ) {
			return false;
		}
		$kind = Status::APPROVED === $from ? 'edits' : 'approved';
		$lang = (string) get_user_meta( $fleet, self::META_LANG, true );
		$lang = '' !== $lang ? $lang : 'sk';
		$p    = Profile::get_live( $fleet );
		$name = ! empty( $p['name'] ) ? (string) $p['name'] : (string) $user->display_name;

		[ $subject, $html ] = self::build( $kind, $lang, $name, self::cabinet_url( $lang ) );
		return (bool) wp_mail( $user->user_email, $subject, $html, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	private static function cabinet_url( string $lang ): string {
		$url = add_query_arg( 'tab', 'profile', home_url( '/cabinet/' ) );
		$tr  = apply_filters( 'wpml_permalink', $url, $lang, true );
		return is_string( $tr ) && '' !== $tr ? $tr : $url;
	}

	/**
	 * @return array{0:string,1:string} Subject, HTML body.
	 */
	public static function build( string $kind, string $lang, string $name, string $url ): array {
		$p       = 'edits' === $kind ? 'mail_edits_' : 'mail_approved_';
		$subject = I18n::t_in( $p . 'subject', $lang );
		$hello   = sprintf( I18n::t_in( 'mail_hello', $lang ), $name );
		$body    = I18n::t_in( $p . 'body', $lang );
		$button  = I18n::t_in( 'mail_button', $lang );
		$sign    = I18n::t_in( 'mail_sign', $lang );

		$content = '<p style="margin:0 0 16px;font-size:18px;font-weight:700;">' . esc_html( $hello ) . '</p>'
			. '<p style="margin:0 0 24px;">' . nl2br( esc_html( $body ) ) . '</p>'
			. '<p style="margin:0 0 24px;"><a href="' . esc_url( $url ) . '" style="display:inline-block;background:#02F797;color:#000000;text-decoration:none;font-weight:700;padding:14px 28px;border-radius:100px;">' . esc_html( $button ) . '</a></p>'
			. '<p style="margin:0;color:#666666;">' . esc_html( $sign ) . '</p>';

		return [ $subject, self::wrap( $content, $lang ) ];
	}

	/** Same shell as the core's emails (black header, white card). */
	private static function wrap( string $content, string $lang ): string {
		$site = esc_url( home_url( '/' ) );
		$year = (int) gmdate( 'Y' );
		return '<!DOCTYPE html><html lang="' . esc_attr( substr( $lang, 0, 2 ) ) . '"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TaxiBaza.sk</title></head>'
			. '<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">'
			. '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 16px;"><tr><td align="center">'
			. '<table width="100%" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e5e5;">'
			. '<tr><td style="background:#000000;padding:24px 40px;"><a href="' . $site . '" style="text-decoration:none;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">TaxiBaza<span style="color:#02F797;">.sk</span></a></td></tr>'
			. '<tr><td style="padding:40px;color:#1a1a1a;font-size:15px;line-height:26px;">' . $content . '</td></tr>'
			. '<tr><td style="padding:20px 40px;border-top:1px solid #eeeeee;color:#999999;font-size:12px;">&copy; ' . $year . ' TaxiBaza.sk</td></tr>'
			. '</table></td></tr></table></body></html>';
	}
}
