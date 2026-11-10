<?php
/**
 * Phone number confirmation by SMS (Twilio Verify) after registration.
 *
 * - Settings → TaxiBaza SMS: Account SID, Auth Token, Verify Service SID and an
 *   on/off switch. The token is stored in an option and never printed back.
 * - Every account registered while the feature is on (form or Google, any role
 *   except admins) gets `tbf_phone_pending`. Until the number is confirmed the
 *   account is sent to /?tbf_phone=1 (legal pages and logging out stay open),
 *   after the terms consent screen if that one is pending too.
 * - Screen: phone number → "Send code" (Twilio sends the SMS in the page
 *   language) → 6-digit code → confirmed. The number is saved to the profile
 *   (`phone`) and `tbf_phone_verified` / `tbf_phone_verified_at`.
 * - One number = one account; at most 5 SMS per account per hour.
 * - Accounts that existed before the feature was switched on are not asked.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Phone_Verify {

	public const OPTION      = 'tbf_sms_settings';
	public const PENDING     = 'tbf_phone_pending';
	public const META_PHONE  = 'tbf_phone_verified';
	public const META_AT     = 'tbf_phone_verified_at';
	public const META_SENT   = 'tbf_phone_sent';
	public const QUERY       = 'tbf_phone';
	public const NONCE       = 'tbf_phone';
	public const SEND_LIMIT  = 5;
	private const PAGE       = 'tbf-sms';

	/** For tests: replaces the HTTP call to Twilio. @var callable|null */
	public static $http = null;

	public static function init(): void {
		add_action( 'user_register', [ self::class, 'on_user_register' ], 6 );
		add_action( 'template_redirect', [ self::class, 'gate' ], 2 ); // After the consent gate (1).
		add_filter( 'template_include', [ self::class, 'screen' ], 101 );
		add_action( 'wp_ajax_tbf_phone_send', [ self::class, 'ajax_send' ] );
		add_action( 'wp_ajax_tbf_phone_check', [ self::class, 'ajax_check' ] );
		if ( is_admin() ) {
			add_action( 'admin_menu', [ self::class, 'menu' ] );
			add_action( 'admin_post_tbf_sms_save', [ self::class, 'save_settings' ] );
		}
	}

	/* ---------------- settings ---------------- */

	/** @return array{on:bool,sid:string,token:string,service:string} */
	public static function settings(): array {
		$s = get_option( self::OPTION, [] );
		$s = is_array( $s ) ? $s : [];
		return [
			'on'      => ! empty( $s['on'] ),
			'sid'     => (string) ( $s['sid'] ?? '' ),
			'token'   => (string) ( $s['token'] ?? '' ),
			'service' => (string) ( $s['service'] ?? '' ),
		];
	}

	public static function enabled(): bool {
		$s = self::settings();
		return $s['on'] && '' !== $s['sid'] && '' !== $s['token'] && '' !== $s['service'];
	}

	public static function menu(): void {
		add_options_page( 'TaxiBaza SMS', 'TaxiBaza SMS', 'manage_options', self::PAGE, [ self::class, 'render_settings' ] );
	}

	public static function render_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s     = self::settings();
		$saved = isset( $_GET['saved'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1>TaxiBaza SMS — <?php echo esc_html( t( 'sms_admin_title' ) ); ?></h1>
			<?php if ( $saved ) : ?><div class="notice notice-success"><p><?php echo esc_html( t( 'sms_admin_saved' ) ); ?></p></div><?php endif; ?>
			<p><?php echo esc_html( t( 'sms_admin_help' ) ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tbf_sms_save">
				<?php wp_nonce_field( 'tbf_sms_save' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php echo esc_html( t( 'sms_admin_on' ) ); ?></th><td><label><input type="checkbox" name="on" value="1"<?php checked( $s['on'] ); ?>> <?php echo esc_html( t( 'sms_admin_on_hint' ) ); ?></label></td></tr>
					<tr><th scope="row"><label for="tbf-sid">Account SID</label></th><td><input class="regular-text" id="tbf-sid" name="sid" value="<?php echo esc_attr( $s['sid'] ); ?>" autocomplete="off" placeholder="AC…"></td></tr>
					<tr><th scope="row"><label for="tbf-token">Auth Token</label></th><td><input class="regular-text" type="password" id="tbf-token" name="token" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( '' !== $s['token'] ? t( 'sms_admin_token_kept' ) : '' ); ?>"></td></tr>
					<tr><th scope="row"><label for="tbf-svc">Verify Service SID</label></th><td><input class="regular-text" id="tbf-svc" name="service" value="<?php echo esc_attr( $s['service'] ); ?>" autocomplete="off" placeholder="VA…"></td></tr>
				</table>
				<p><?php echo esc_html( self::enabled() ? t( 'sms_admin_state_on' ) : t( 'sms_admin_state_off' ) ); ?></p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'tbf_sms_save' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'default' ) );
		}
		$old   = self::settings();
		$token = trim( (string) wp_unslash( $_POST['token'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- secret, validated by the pattern below.
		$new   = [
			'on'      => ! empty( $_POST['on'] ),
			'sid'     => preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_POST['sid'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'token'   => '' !== $token ? preg_replace( '/[^A-Za-z0-9]/', '', $token ) : $old['token'],
			'service' => preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_POST['service'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		];
		update_option( self::OPTION, $new, false );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE . '&saved=1' ) );
		exit;
	}

	/* ---------------- who has to confirm ---------------- */

	/** @param int $user_id New user. */
	public static function on_user_register( $user_id ): void {
		if ( ! self::enabled() || user_can( (int) $user_id, 'manage_options' ) ) {
			return;
		}
		update_user_meta( (int) $user_id, self::PENDING, 1 );
	}

	public static function is_pending( int $uid ): bool {
		return self::enabled() && (bool) get_user_meta( $uid, self::PENDING, true ) && ! user_can( $uid, 'manage_options' );
	}

	private static function on_screen(): bool {
		return isset( $_GET[ self::QUERY ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	public static function gate(): void {
		if ( ! is_user_logged_in() || wp_doing_ajax() ) {
			return;
		}
		$uid = get_current_user_id();
		if ( ! self::is_pending( $uid ) || get_user_meta( $uid, Consent::PENDING, true ) ) {
			return; // Nothing to do, or the terms screen comes first.
		}
		if ( self::on_screen() ) {
			return;
		}
		$legal = array_filter( [ (int) apply_filters( 'wpml_object_id', 3, 'page', true ), (int) apply_filters( 'wpml_object_id', 17, 'page', true ) ] );
		if ( is_page( $legal ) ) {
			return;
		}
		$base = function_exists( 'tb_url' ) ? tb_url( '/' ) : home_url( '/' );
		wp_safe_redirect( add_query_arg( self::QUERY, 1, $base ) );
		exit;
	}

	/** @param string $template Template. */
	public static function screen( $template ) {
		if ( self::on_screen() && is_user_logged_in() && self::is_pending( get_current_user_id() ) && ! get_user_meta( get_current_user_id(), Consent::PENDING, true ) ) {
			return dirname( __DIR__ ) . '/templates/phone-verify.php';
		}
		return $template;
	}

	/* ---------------- phone numbers ---------------- */

	/**
	 * To E.164. Without a country code the number is taken as Slovak (+421).
	 */
	public static function normalize( string $raw ): string {
		$raw = trim( $raw );
		$plus = str_starts_with( $raw, '+' ) || str_starts_with( $raw, '00' );
		$d    = preg_replace( '/\D/', '', $raw );
		if ( str_starts_with( $raw, '00' ) ) {
			$d = substr( $d, 2 );
		}
		if ( ! $plus ) {
			$d = '421' . ltrim( $d, '0' );
		}
		return preg_match( '/^[1-9]\d{7,14}$/', $d ) ? '+' . $d : '';
	}

	/** Is the number already confirmed by another account? */
	public static function taken( string $e164, int $uid ): bool {
		$users = get_users( [ 'meta_key' => self::META_PHONE, 'meta_value' => $e164, 'fields' => 'ID', 'number' => 2 ] ); // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( (array) $users as $id ) {
			if ( (int) $id !== $uid ) {
				return true;
			}
		}
		return false;
	}

	/* ---------------- Twilio ---------------- */

	/**
	 * @param array<string,string> $body Form fields.
	 * @return array{code:int,body:array<string,mixed>}
	 */
	private static function twilio( string $path, array $body ): array {
		$s   = self::settings();
		$url = 'https://verify.twilio.com/v2/Services/' . rawurlencode( $s['service'] ) . '/' . $path;
		if ( is_callable( self::$http ) ) {
			return ( self::$http )( $url, $body );
		}
		$res = wp_remote_post(
			$url,
			[
				'timeout' => 15,
				'headers' => [ 'Authorization' => 'Basic ' . base64_encode( $s['sid'] . ':' . $s['token'] ) ], // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP Basic auth.
				'body'    => $body,
			]
		);
		if ( is_wp_error( $res ) ) {
			return [ 'code' => 0, 'body' => [] ];
		}
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		return [ 'code' => (int) wp_remote_retrieve_response_code( $res ), 'body' => is_array( $json ) ? $json : [] ];
	}

	private static function locale(): string {
		$l = I18n::lang();
		return in_array( $l, [ 'sk', 'uk', 'en', 'ru' ], true ) ? $l : 'sk';
	}

	/** Twilio error code → message key. */
	private static function error_key( array $r ): string {
		$code = (int) ( $r['body']['code'] ?? 0 );
		if ( in_array( $code, [ 60200, 60205, 21211, 21614 ], true ) ) {
			return 'sms_err_number';
		}
		if ( in_array( $code, [ 60202, 60203 ], true ) ) {
			return 'sms_err_limit';
		}
		if ( 20404 === $code ) {
			return 'sms_err_expired';
		}
		return 'sms_err_service';
	}

	/* ---------------- AJAX ---------------- */

	private static function guard(): int {
		$uid = get_current_user_id();
		if ( ! $uid || ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => t( 'sms_err_service' ) ], 403 );
		}
		if ( ! self::is_pending( $uid ) ) {
			wp_send_json_success( [ 'done' => true ] );
		}
		return $uid;
	}

	public static function ajax_send(): void {
		$uid   = self::guard();
		$phone = self::normalize( (string) wp_unslash( $_POST['phone'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- normalised to digits; nonce in guard().
		if ( '' === $phone ) {
			wp_send_json_error( [ 'message' => t( 'sms_err_number' ) ], 422 );
		}
		if ( self::taken( $phone, $uid ) ) {
			wp_send_json_error( [ 'message' => t( 'sms_err_taken' ) ], 422 );
		}
		$sent = array_filter( (array) get_user_meta( $uid, self::META_SENT, true ), static fn( $ts ) => (int) $ts > time() - HOUR_IN_SECONDS );
		if ( count( $sent ) >= self::SEND_LIMIT ) {
			wp_send_json_error( [ 'message' => t( 'sms_err_limit' ) ], 429 );
		}
		$r = self::twilio( 'Verifications', [ 'To' => $phone, 'Channel' => 'sms', 'Locale' => self::locale() ] );
		if ( $r['code'] < 200 || $r['code'] >= 300 ) {
			wp_send_json_error( [ 'message' => t( self::error_key( $r ) ) ], 422 );
		}
		$sent[] = time();
		update_user_meta( $uid, self::META_SENT, array_values( $sent ) );
		update_user_meta( $uid, 'tbf_phone_candidate', $phone );
		wp_send_json_success( [ 'phone' => $phone, 'message' => sprintf( t( 'sms_sent' ), $phone ) ] );
	}

	public static function ajax_check(): void {
		$uid   = self::guard();
		$phone = (string) get_user_meta( $uid, 'tbf_phone_candidate', true );
		$code  = preg_replace( '/\D/', '', (string) wp_unslash( $_POST['code'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
		if ( '' === $phone ) {
			wp_send_json_error( [ 'message' => t( 'sms_err_expired' ) ], 422 );
		}
		if ( strlen( $code ) < 4 || strlen( $code ) > 10 ) {
			wp_send_json_error( [ 'message' => t( 'sms_err_code' ) ], 422 );
		}
		$r = self::twilio( 'VerificationCheck', [ 'To' => $phone, 'Code' => $code ] );
		if ( $r['code'] >= 200 && $r['code'] < 300 && 'approved' === ( $r['body']['status'] ?? '' ) ) {
			self::confirm( $uid, $phone );
			$cab = function_exists( 'tb_url' ) ? tb_url( '/cabinet/' ) : home_url( '/cabinet/' );
			wp_send_json_success( [ 'done' => true, 'redirect' => $cab ] );
		}
		wp_send_json_error( [ 'message' => t( $r['code'] >= 200 && $r['code'] < 300 ? 'sms_err_code' : self::error_key( $r ) ) ], 422 );
	}

	public static function confirm( int $uid, string $phone ): void {
		update_user_meta( $uid, self::META_PHONE, $phone );
		update_user_meta( $uid, self::META_AT, gmdate( 'Y-m-d H:i:s' ) );
		update_user_meta( $uid, 'phone', $phone );
		delete_user_meta( $uid, self::PENDING );
		delete_user_meta( $uid, 'tbf_phone_candidate' );
		delete_user_meta( $uid, self::META_SENT );
	}
}
