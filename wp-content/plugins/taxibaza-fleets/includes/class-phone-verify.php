<?php
/**
 * Phone number confirmation by SMS (Twilio Verify) at registration (Figma
 * "Register" → "Confirm Phone" 343:2846 / 343:2971 → "Confirm Phone Success" 343:3104).
 *
 * - Settings → TaxiBaza SMS: Account SID, Auth Token, Verify Service SID and an
 *   on/off switch. The token is stored in an option and never printed back.
 * - While the feature is on, the registration form gets a required "Phone"
 *   field. After the form is sent the account is signed in and taken straight
 *   to the code screen; the SMS goes out on its own. The confirmed phone
 *   activates the account, so the e-mail confirmation link is not needed
 *   (and not sent) for these accounts.
 * - Every account registered while the feature is on (form or Google, any role
 *   except admins) gets `tbf_phone_pending`. Until the number is confirmed the
 *   account is sent to /?tbf_phone=1 (legal pages and logging out stay open,
 *   AJAX is closed), after the terms consent screen if that one is pending too.
 *   Google accounts type the number on that screen.
 * - The number is saved to the profile (`phone`) and `tbf_phone_verified` /
 *   `tbf_phone_verified_at`.
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
	public const CANDIDATE   = 'tbf_phone_candidate';
	public const FIELD       = 'tbf_phone';
	public const ERR_QUERY   = 'tbf_phone_err';
	public const LAST_ERROR  = 'tbf_sms_last_error';
	private const PAGE       = 'tbf-sms';

	/** For tests: replaces the HTTP call to Twilio. @var callable|null */
	public static $http = null;

	/** Account created by the registration form in this request. */
	private static int $form_user = 0;

	public static function init(): void {
		add_action( 'init', [ self::class, 'check_register_post' ], 6 ); // After the consent check (5), before the core form handler (10).
		add_action( 'user_register', [ self::class, 'on_user_register' ], 6 );
		add_filter( 'pre_wp_mail', [ self::class, 'skip_email_confirmation' ], 10, 2 );
		add_filter( 'wp_redirect', [ self::class, 'after_register' ], 99 );
		add_action( 'wp_footer', [ self::class, 'print_register_field' ], 52 );
		add_action( 'admin_init', [ self::class, 'close_ajax' ], 1 );
		add_action( 'template_redirect', [ self::class, 'gate' ], 2 ); // After the consent gate (1).
		add_filter( 'template_include', [ self::class, 'screen' ], 101 );
		add_action( 'wp_ajax_tbf_phone_send', [ self::class, 'ajax_send' ] );
		add_action( 'wp_ajax_tbf_phone_check', [ self::class, 'ajax_check' ] );
		if ( is_admin() ) {
			add_action( 'admin_menu', [ self::class, 'menu' ] );
			add_action( 'admin_post_tbf_sms_save', [ self::class, 'save_settings' ] );
			add_action( 'admin_post_tbf_sms_test', [ self::class, 'test_connection' ] );
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
			<hr>
			<h2><?php echo esc_html( t( 'sms_admin_diag' ) ); ?></h2>
			<p><?php echo esc_html( sprintf( t( 'sms_admin_token_len' ), strlen( $s['token'] ) ) ); ?></p>
			<?php
			$test = get_transient( 'tbf_sms_test' );
			if ( is_array( $test ) && isset( $_GET['tested'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				?>
				<div class="notice <?php echo $test['ok'] ? 'notice-success' : 'notice-error'; ?> inline"><p><?php echo esc_html( $test['text'] ); ?></p></div>
			<?php endif; ?>
			<?php
			$last = get_option( self::LAST_ERROR );
			if ( is_array( $last ) && ! empty( $last['at'] ) ) :
				?>
				<p><strong><?php echo esc_html( t( 'sms_admin_last_err' ) ); ?></strong> <?php echo esc_html( wp_date( 'd.m.Y H:i', (int) $last['at'] ) . ' — ' . self::describe( $last ) ); ?></p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tbf_sms_test">
				<?php wp_nonce_field( 'tbf_sms_test' ); ?>
				<?php submit_button( t( 'sms_admin_test' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/** Human-readable Twilio failure (no secrets). @param array<string,mixed> $e */
	public static function describe( array $e ): string {
		$parts = [];
		if ( ! empty( $e['wp_error'] ) ) {
			$parts[] = 'network: ' . $e['wp_error'];
		}
		if ( ! empty( $e['http'] ) ) {
			$parts[] = 'HTTP ' . (int) $e['http'];
		}
		if ( ! empty( $e['code'] ) ) {
			$parts[] = 'Twilio ' . (int) $e['code'];
		}
		if ( ! empty( $e['message'] ) ) {
			$parts[] = (string) $e['message'];
		}
		if ( 20003 === (int) ( $e['code'] ?? 0 ) || 401 === (int) ( $e['http'] ?? 0 ) ) {
			$parts[] = '→ ' . t( 'sms_admin_hint_auth' );
		} elseif ( 20404 === (int) ( $e['code'] ?? 0 ) && ! empty( $e['service'] ) ) {
			$parts[] = '→ ' . t( 'sms_admin_hint_service' );
		}
		return implode( ' · ', $parts );
	}

	/** Settings → "Check connection": reads the Verify service with the saved keys. */
	public static function test_connection(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'tbf_sms_test' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'default' ) );
		}
		$s = self::settings();
		if ( '' === $s['sid'] || '' === $s['token'] || '' === $s['service'] ) {
			$out = [ 'ok' => false, 'text' => t( 'sms_admin_state_off' ) ];
		} else {
			$res = wp_remote_get(
				'https://verify.twilio.com/v2/Services/' . rawurlencode( $s['service'] ),
				[
					'timeout' => 15,
					'headers' => [ 'Authorization' => 'Basic ' . base64_encode( $s['sid'] . ':' . $s['token'] ) ], // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP Basic auth.
				]
			);
			if ( is_wp_error( $res ) ) {
				$err = [ 'wp_error' => $res->get_error_message() ];
			} else {
				$code = (int) wp_remote_retrieve_response_code( $res );
				$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
				$json = is_array( $json ) ? $json : [];
				if ( 200 === $code ) {
					$out = [ 'ok' => true, 'text' => sprintf( t( 'sms_admin_test_ok' ), (string) ( $json['friendly_name'] ?? '' ) ) ];
				}
				$err = [ 'http' => $code, 'code' => (int) ( $json['code'] ?? 0 ), 'message' => (string) ( $json['message'] ?? '' ), 'service' => 1 ];
			}
			if ( empty( $out ) ) {
				$out = [ 'ok' => false, 'text' => self::describe( $err ) ];
			}
		}
		set_transient( 'tbf_sms_test', $out, 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE . '&tested=1' ) );
		exit;
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

	private static function url( string $path, array $args = [] ): string {
		$base = function_exists( 'tb_url' ) ? tb_url( $path ) : home_url( $path );
		return $args ? add_query_arg( $args, $base ) : $base;
	}

	private static function is_register_post(): bool {
		return isset( $_POST['tb_action'] ) && 'register' === sanitize_key( wp_unslash( $_POST['tb_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the core verifies its nonce; we only read the phone.
	}

	private static function posted_phone(): string {
		return self::normalize( (string) wp_unslash( $_POST[ self::FIELD ] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- normalised to digits.
	}

	/** Registration form without a usable phone: back to the form, nothing created. */
	public static function check_register_post(): void {
		if ( ! self::enabled() || ! self::is_register_post() ) {
			return;
		}
		$phone = self::posted_phone();
		$err   = '' === $phone ? 'number' : ( self::taken( $phone, 0 ) ? 'taken' : '' );
		if ( '' === $err ) {
			return;
		}
		wp_safe_redirect( self::url( '/login/', [ 'action' => 'register', self::ERR_QUERY => $err ] ) );
		exit;
	}

	/** @param int $user_id New user. */
	public static function on_user_register( $user_id ): void {
		$user_id = (int) $user_id;
		if ( ! self::enabled() || user_can( $user_id, 'manage_options' ) ) {
			return;
		}
		update_user_meta( $user_id, self::PENDING, 1 );
		if ( self::is_register_post() ) {
			$phone = self::posted_phone();
			if ( '' !== $phone ) {
				update_user_meta( $user_id, self::CANDIDATE, $phone );
			}
			self::$form_user = $user_id;
		}
	}

	/**
	 * The confirmed phone activates a form account, so its e-mail link is not sent.
	 *
	 * @param null|bool            $short Short-circuit value.
	 * @param array<string, mixed> $atts  wp_mail() arguments.
	 * @return null|bool
	 */
	public static function skip_email_confirmation( $short, $atts ) {
		if ( ! self::$form_user ) {
			return $short;
		}
		$user = get_userdata( self::$form_user );
		$to   = array_map( 'strtolower', array_map( 'trim', (array) ( is_array( $atts['to'] ?? null ) ? $atts['to'] : explode( ',', (string) ( $atts['to'] ?? '' ) ) ) ) );
		return $user && in_array( strtolower( (string) $user->user_email ), $to, true ) ? true : $short;
	}

	/**
	 * The core sends a new form account to "check your e-mail". Instead: sign it
	 * in and open the SMS code screen.
	 *
	 * @param string $location Redirect target.
	 */
	public static function after_register( $location ) {
		$uid = self::$form_user;
		if ( ! $uid || ! str_contains( (string) $location, 'confirm_email' ) ) {
			return $location;
		}
		self::$form_user = 0;
		update_user_meta( $uid, 'tb_email_confirmed', '1' );
		delete_user_meta( $uid, 'tb_email_confirm_token' );
		wp_set_current_user( $uid );
		wp_set_auth_cookie( $uid, false, is_ssl() );
		return self::url( '/', [ self::QUERY => 1 ] );
	}

	/** Until the phone is confirmed the account can't use AJAX (except the SMS screen). */
	public static function close_ajax(): void {
		if ( ! wp_doing_ajax() || ! is_user_logged_in() || ! self::is_pending( get_current_user_id() ) ) {
			return;
		}
		$action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $action, [ 'tbf_phone_send', 'tbf_phone_check', 'heartbeat' ], true ) ) {
			return;
		}
		wp_send_json_error( [ 'message' => t( 'sms_title' ) ], 403 );
	}

	/** Required "Phone" field in the theme's registration form. */
	public static function print_register_field(): void {
		if ( ! self::enabled() || is_user_logged_in() || ! is_page_template( 'page-login.php' ) ) {
			return;
		}
		$err  = sanitize_key( wp_unslash( $_GET[ self::ERR_QUERY ] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$data = [
			'name'     => self::FIELD,
			'label'    => t( 'sms_reg_label' ),
			'required' => t( 'sms_reg_required' ),
			'ph'       => '+421 9XX XXX XXX',
			'empty'    => t( 'sms_reg_empty' ),
			'bad'      => t( 'sms_err_number' ),
			'show'     => 'taken' === $err ? t( 'sms_err_taken' ) : ( 'number' === $err ? t( 'sms_err_number' ) : '' ),
		];
		?>
<script id="tbf-reg-phone">
(function () {
	var d = <?php echo wp_json_encode( $data ); ?>;
	var hidden = document.querySelector('form input[name="tb_action"][value="register"]');
	if (!hidden) { return; }
	var form = hidden.form;
	if (form.querySelector('[name="' + d.name + '"]')) { return; }
	var email = form.querySelector('[name="tb_email"]');
	var after = email ? email.closest('.tb-auth-form__field') : null;
	var f = document.createElement('div'); f.className = 'tb-auth-form__field tbf-reg-phone';
	var l = document.createElement('label'); l.className = 'tb-auth-form__label'; l.htmlFor = 'tbf_phone_reg'; l.textContent = d.label + ' ';
	var star = document.createElement('span'); star.className = 'tb-auth-form__star'; star.setAttribute('aria-label', d.required); star.textContent = '*'; l.appendChild(star);
	var i = document.createElement('input'); i.type = 'tel'; i.id = 'tbf_phone_reg'; i.name = d.name; i.className = 'tb-auth-form__input'; i.placeholder = d.ph;
	i.autocomplete = 'tel'; i.inputMode = 'tel'; i.required = true; i.setAttribute('data-err-required', d.empty);
	var e = document.createElement('span'); e.className = 'tb-field-error'; e.setAttribute('role', 'alert');
	f.appendChild(l); f.appendChild(i); f.appendChild(e);
	if (after && after.parentNode) { after.parentNode.insertBefore(f, after.nextSibling); } else { form.insertBefore(f, form.querySelector('button[type="submit"]')); }
	function ok(v) { var n = v.replace(/[^\d]/g, ''); return n.length >= 9 && n.length <= 15 && /^[+\d\s()\-.\/]+$/.test(v.trim()); }
	function fail(msg) { e.textContent = msg; i.classList.add('is-invalid'); i.setAttribute('aria-invalid', 'true'); }
	function clear() { e.textContent = ''; i.classList.remove('is-invalid'); i.removeAttribute('aria-invalid'); }
	i.addEventListener('input', clear);
	i.addEventListener('invalid', function (ev) { ev.preventDefault(); fail(d.empty); });
	form.addEventListener('submit', function (ev) {
		var v = i.value;
		if (!v.trim() || !ok(v)) { ev.preventDefault(); ev.stopImmediatePropagation(); fail(v.trim() ? d.bad : d.empty); i.focus(); }
	}, true);
	if (d.show) { fail(d.show); f.scrollIntoView({ block: 'center' }); }
}());
</script>
		<?php
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
			update_option( self::LAST_ERROR, [ 'at' => time(), 'wp_error' => $res->get_error_message() ], false );
			return [ 'code' => 0, 'body' => [] ];
		}
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		$json = is_array( $json ) ? $json : [];
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( $code < 200 || $code >= 300 ) {
			update_option( self::LAST_ERROR, [ 'at' => time(), 'http' => $code, 'code' => (int) ( $json['code'] ?? 0 ), 'message' => substr( (string) ( $json['message'] ?? '' ), 0, 300 ), 'service' => 1 ], false );
		}
		return [ 'code' => $code, 'body' => $json ];
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
		$raw   = (string) wp_unslash( $_POST['phone'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing -- normalised to digits below; nonce in guard().
		$phone = '' === trim( $raw ) ? (string) get_user_meta( $uid, self::CANDIDATE, true ) : self::normalize( $raw ); // "Send again" re-uses the number.
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
		update_user_meta( $uid, self::CANDIDATE, $phone );
		wp_send_json_success( [ 'phone' => $phone, 'pretty' => self::pretty( $phone ), 'message' => sprintf( t( 'sms_sent' ), $phone ) ] );
	}

	/** +421900123456 → "+421 900 123 456". */
	public static function pretty( string $e164 ): string {
		$d = ltrim( $e164, '+' );
		if ( strlen( $d ) < 6 ) {
			return $e164;
		}
		return '+' . substr( $d, 0, 3 ) . ' ' . trim( chunk_split( substr( $d, 3 ), 3, ' ' ) );
	}

	/** Was a code sent to this account within the last minute? */
	public static function sent_recently( int $uid ): bool {
		foreach ( (array) get_user_meta( $uid, self::META_SENT, true ) as $ts ) {
			if ( (int) $ts > time() - MINUTE_IN_SECONDS ) {
				return true;
			}
		}
		return false;
	}

	public static function ajax_check(): void {
		$uid   = self::guard();
		$phone = (string) get_user_meta( $uid, self::CANDIDATE, true );
		$code  =preg_replace( '/\D/', '', (string) wp_unslash( $_POST['code'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
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
			wp_send_json_success( [ 'done' => true, 'redirect' => $cab, 'pretty' => self::pretty( $phone ) ] );
		}
		wp_send_json_error( [ 'message' => t( $r['code'] >= 200 && $r['code'] < 300 ? 'sms_err_code' : self::error_key( $r ) ) ], 422 );
	}

	public static function confirm( int $uid, string $phone ): void {
		update_user_meta( $uid, self::META_PHONE, $phone );
		update_user_meta( $uid, self::META_AT, gmdate( 'Y-m-d H:i:s' ) );
		update_user_meta( $uid, 'phone', $phone );
		delete_user_meta( $uid, self::PENDING );
		delete_user_meta( $uid, self::CANDIDATE );
		delete_user_meta( $uid, self::META_SENT );
	}
}
