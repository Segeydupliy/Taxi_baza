<?php
/**
 * "Хочу працювати" — a driver's job application to a fleet.
 * Figma: Send Message Pop-up (895:5659), error (895:5712), success (895:5774).
 *
 * AJAX action `tbf_work_request` (guests allowed): nonce + honeypot + a small
 * per-IP rate limit, server-side validation. On success the fleet gets an email
 * (in the language it used for its profile, else SK) and a bell notification;
 * the last 50 applications are also kept in user meta `tbf_work_requests`.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Work_Request {

	public const ACTION   = 'tbf_work_request';
	public const NONCE    = 'tbf_work_request';
	public const HONEYPOT = 'tbf_website';
	public const META     = 'tbf_work_requests';
	public const MIN_MSG  = 20;
	public const RATE_MAX = 5;   // Requests per IP …
	public const RATE_TTL = 600; // … per 10 minutes.

	public static function init(): void {
		add_action( 'wp_ajax_' . self::ACTION, [ self::class, 'handle' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ self::class, 'handle' ] );
	}

	/**
	 * Validate the form. Pure: no WordPress state is read.
	 *
	 * @param array<string,mixed> $raw    Unslashed POST.
	 * @param int[]               $cities Allowed city term IDs (empty = any positive ID).
	 * @return array{0:array<string,mixed>,1:array<string,string>} [ clean data, field => string key ]
	 */
	public static function validate( array $raw, array $cities = [] ): array {
		$str  = static fn( $k, $max = 200 ) => mb_substr( trim( strip_tags( (string) ( $raw[ $k ] ?? '' ) ) ), 0, $max );
		$data = [
			'car'     => $str( 'car', 10 ),
			'license' => $str( 'license', 10 ),
			'city'    => (int) ( $raw['city'] ?? 0 ),
			'name'    => (string) preg_replace( '/\s+/u', ' ', $str( 'name', 120 ) ),
			'phone'   => (string) preg_replace( '/[^\d+ ]/', '', $str( 'phone', 30 ) ),
			'email'   => $str( 'email', 120 ),
			'message' => mb_substr( trim( strip_tags( str_replace( "\r", '', (string) ( $raw['message'] ?? '' ) ) ) ), 0, 2000 ),
		];
		$e = [];
		if ( ! in_array( $data['car'], [ 'fleet', 'own' ], true ) ) {
			$e['car'] = 'fl_m_err_pick';
		}
		if ( ! in_array( $data['license'], [ 'yes', 'no' ], true ) ) {
			$e['license'] = 'fl_m_err_pick';
		}
		if ( $data['city'] <= 0 || ( $cities && ! in_array( $data['city'], array_map( 'intval', $cities ), true ) ) ) {
			$e['city'] = 'fl_m_err_pick';
		}
		if ( mb_strlen( $data['name'] ) < 2 ) {
			$e['name'] = 'err_required';
		}
		if ( '' === $data['phone'] ) {
			$e['phone'] = 'err_required';
		} elseif ( ! preg_match( '/^\+?\d[\d ]{7,18}$/', $data['phone'] ) ) {
			$e['phone'] = 'err_phone';
		}
		if ( '' !== $data['email'] && ! filter_var( $data['email'], FILTER_VALIDATE_EMAIL ) ) {
			$e['email'] = 'fl_m_err_email';
		}
		if ( '' === $data['message'] ) {
			$e['message'] = 'err_required';
		} elseif ( mb_strlen( $data['message'] ) < self::MIN_MSG ) {
			$e['message'] = 'fl_m_err_short';
		}
		return [ $data, $e ];
	}

	/** True when the honeypot was filled (bots). */
	public static function is_spam( array $raw ): bool {
		return '' !== trim( (string) ( $raw[ self::HONEYPOT ] ?? '' ) );
	}

	/** Language of the page the form was sent from (admin-ajax.php itself has none). */
	public static function lang( array $raw ): string {
		$l = sanitize_key( (string) ( $raw['lang'] ?? '' ) );
		return isset( I18n::LANGS[ $l ] ) ? $l : I18n::lang();
	}

	/** City term IDs the form offers (current language). @return int[] */
	public static function city_ids(): array {
		$terms = get_terms( [ 'taxonomy' => 'city', 'hide_empty' => false, 'fields' => 'ids' ] );
		return is_wp_error( $terms ) ? [] : array_map( 'intval', (array) $terms );
	}

	private static function ip(): string {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	}

	/** Count this request against the IP's budget; false when it is used up. */
	public static function rate_ok(): bool {
		$key = 'tbf_wr_' . md5( self::ip() );
		$n   = (int) get_transient( $key );
		if ( $n >= self::RATE_MAX ) {
			return false;
		}
		set_transient( $key, $n + 1, self::RATE_TTL );
		return true;
	}

	public static function handle(): void {
		$raw  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked right below; fields sanitised in validate().
		$raw  = is_array( $raw ) ? $raw : [];
		$lang = self::lang( $raw );
		do_action( 'wpml_switch_language', $lang );
		$tr = static fn( $k ) => I18n::t_in( $k, $lang );

		if ( ! wp_verify_nonce( (string) ( $raw['_wpnonce'] ?? '' ), self::NONCE ) ) {
			wp_send_json_error( [ 'message' => $tr( 'err_server' ) ], 403 );
		}
		$fleet = absint( $raw['fleet'] ?? 0 );
		if ( ! Directory::is_visible( $fleet ) ) {
			wp_send_json_error( [ 'message' => $tr( 'err_server' ) ], 404 );
		}
		$name = Listing_Fleet::fleet_name( $fleet );
		$ok   = [
			'title' => $tr( 'fl_m_ok_title' ),
			'text'  => sprintf( $tr( 'fl_m_ok_text' ), $name ),
		];
		if ( self::is_spam( $raw ) ) {
			wp_send_json_success( $ok ); // Look like success, send nothing.
		}
		[ $data, $errors ] = self::validate( $raw, self::city_ids() );
		if ( $errors ) {
			wp_send_json_error(
				[
					'message' => $tr( 'fl_m_err_banner' ),
					'errors'  => array_map( $tr, $errors ),
				],
				422
			);
		}
		if ( ! self::rate_ok() ) {
			wp_send_json_error( [ 'message' => $tr( 'fl_m_err_rate' ) ], 429 );
		}
		self::deliver( $fleet, $data, $lang );
		wp_send_json_success( $ok );
	}

	/**
	 * Email + bell notification + stored copy.
	 *
	 * @param array<string,mixed> $data Valid data.
	 * @param string              $from Language of the form.
	 */
	public static function deliver( int $fleet, array $data, string $from = 'sk' ): bool {
		$city = get_term( (int) $data['city'], 'city' );
		$data['city_name'] = $city && ! is_wp_error( $city ) ? (string) $city->name : '';
		$data['time']      = time();
		$data['form_lang'] = $from;

		$list = get_user_meta( $fleet, self::META, true );
		$list = is_array( $list ) ? $list : [];
		array_unshift( $list, $data );
		update_user_meta( $fleet, self::META, array_slice( $list, 0, 50 ) );

		Notifications::add( $fleet, 'work_request', [ 'name' => $data['name'], 'phone' => $data['phone'] ] );

		$sent = false;
		$user = get_userdata( $fleet );
		if ( $user && ! empty( $user->user_email ) ) {
			$lang = (string) get_user_meta( $fleet, Mailer::META_LANG, true );
			$lang = isset( I18n::LANGS[ $lang ] ) ? $lang : 'sk';
			[ $subject, $html ] = Mailer::build_work_request( $lang, Listing_Fleet::fleet_name( $fleet ), $data, Directory::fleet_url( $fleet, $lang ) );
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
			if ( '' !== $data['email'] ) {
				$headers[] = 'Reply-To: ' . str_replace( [ "\r", "\n", '<', '>' ], '', $data['name'] ) . ' <' . $data['email'] . '>';
			}
			$sent = (bool) wp_mail( $user->user_email, $subject, $html, $headers );
		}
		do_action( 'tbf_work_request', $fleet, $data, $sent );
		return $sent;
	}
}
