<?php
/**
 * Consent to the Terms of Use and the Privacy Policy (GDPR).
 *
 * - Registration form: a required checkbox with links to both pages (added to
 *   the theme's form by a small script, like the account-type select), checked
 *   again on the server before the core creates the account.
 * - Google sign-in: an account created through Google gets a consent screen on
 *   its first login; until it is accepted the site redirects there.
 * - The date of consent is stored on the user (meta `tbf_terms_accepted_at`, UTC),
 *   with the language and how it was given.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Consent {

	public const FIELD     = 'tbf_terms';
	public const META_AT   = 'tbf_terms_accepted_at';
	public const META_VIA  = 'tbf_terms_accepted_via';
	public const META_LANG = 'tbf_terms_accepted_lang';
	public const PENDING   = 'tbf_terms_pending';
	public const QUERY     = 'tbf_terms';
	public const NONCE     = 'tbf_terms_accept';
	public const ERROR     = 'tbf_terms';

	public static function init(): void {
		add_action( 'init', [ self::class, 'check_register_post' ], 5 ); // Before the core's form handler (init:10).
		add_action( 'user_register', [ self::class, 'on_user_register' ], 5 );
		add_action( 'set_logged_in_cookie', [ self::class, 'on_google_login' ], 10, 4 );
		add_action( 'template_redirect', [ self::class, 'gate' ], 1 );
		add_filter( 'template_include', [ self::class, 'screen' ], 100 );
		add_action( 'wp_footer', [ self::class, 'print_field_script' ], 51 );
	}

	/** Legal page URL by ID in the current language. */
	public static function legal_url( string $which ): string {
		if ( function_exists( 'tb_legal_url' ) ) {
			return tb_legal_url( $which );
		}
		$id  = 'terms' === $which ? 17 : 3;
		$tid = (int) apply_filters( 'wpml_object_id', $id, 'page', true );
		$url = $tid ? get_permalink( $tid ) : '';
		return $url ? (string) $url : home_url( '/' );
	}

	/** The consent sentence with both links (escaped HTML). */
	public static function label_html(): string {
		return sprintf(
			esc_html( t( 'terms_label' ) ),
			'<a href="' . esc_url( self::legal_url( 'terms' ) ) . '" target="_blank" rel="noopener">' . esc_html( t( 'terms_link_terms' ) ) . '</a>',
			'<a href="' . esc_url( self::legal_url( 'privacy' ) ) . '" target="_blank" rel="noopener">' . esc_html( t( 'terms_link_privacy' ) ) . '</a>'
		);
	}

	private static function url( string $path, array $args = [] ): string {
		$base = function_exists( 'tb_url' ) ? tb_url( $path ) : home_url( $path );
		return $args ? add_query_arg( $args, $base ) : $base;
	}

	/** Registration POST without the checkbox: back to the form, nothing created. */
	public static function check_register_post(): void {
		if ( empty( $_POST['tb_action'] ) || 'register' !== sanitize_key( wp_unslash( $_POST['tb_action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the core verifies its nonce; we only refuse.
			return;
		}
		if ( ! empty( $_POST[ self::FIELD ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		wp_safe_redirect( self::url( '/login/', [ 'action' => 'register', 'error' => self::ERROR ] ) );
		exit;
	}

	/**
	 * @param int $user_id New user.
	 */
	public static function on_user_register( $user_id ): void {
		$user_id = (int) $user_id;
		if ( ! empty( $_POST[ self::FIELD ] ) && isset( $_POST['tb_action'] ) && 'register' === sanitize_key( wp_unslash( $_POST['tb_action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			self::record( $user_id, 'register_form' );
			return;
		}
		if ( isset( $_GET['tb_google'] ) && 'callback' === sanitize_key( wp_unslash( $_GET['tb_google'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			update_user_meta( $user_id, self::PENDING, 1 );
		}
	}

	/**
	 * Signing in with Google to an account that has never accepted the terms
	 * (an account created before consent existed, or one that was registered
	 * earlier with the same email) gets the consent screen too — not only a
	 * brand-new Google account.
	 *
	 * @param string $cookie     Cookie value.
	 * @param int    $expire     Expire.
	 * @param int    $expiration Expiration.
	 * @param int    $user_id    User.
	 */
	public static function on_google_login( $cookie, $expire, $expiration, $user_id ): void {
		if ( ! isset( $_GET['tb_google'] ) || 'callback' !== sanitize_key( wp_unslash( $_GET['tb_google'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Google's redirect; the core checks its state.
			return;
		}
		$user_id = (int) $user_id;
		if ( ! $user_id || user_can( $user_id, 'manage_options' ) || get_user_meta( $user_id, self::META_AT, true ) ) {
			return;
		}
		update_user_meta( $user_id, self::PENDING, 1 );
	}

	public static function record( int $user_id, string $via ): void {
		update_user_meta( $user_id, self::META_AT, gmdate( 'Y-m-d H:i:s' ) );
		update_user_meta( $user_id, self::META_VIA, $via );
		update_user_meta( $user_id, self::META_LANG, I18n::lang() );
		delete_user_meta( $user_id, self::PENDING );
	}

	private static function on_screen(): bool {
		return isset( $_GET[ self::QUERY ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/** Users created through Google can't go anywhere before accepting. */
	public static function gate(): void {
		if ( ! is_user_logged_in() || wp_doing_ajax() ) {
			return;
		}
		$uid = get_current_user_id();
		if ( ! get_user_meta( $uid, self::PENDING, true ) ) {
			return;
		}
		if ( self::on_screen() ) {
			if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE ) ) {
				if ( ! empty( $_POST[ self::FIELD ] ) ) {
					self::record( $uid, 'google_first_login' );
					wp_safe_redirect( self::url( '/cabinet/' ) );
					exit;
				}
			}
			return;
		}
		// Let them read the two documents.
		$legal = array_filter( [ (int) apply_filters( 'wpml_object_id', 3, 'page', true ), (int) apply_filters( 'wpml_object_id', 17, 'page', true ) ] );
		if ( is_page( $legal ) ) {
			return;
		}
		wp_safe_redirect( self::url( '/', [ self::QUERY => 1 ] ) );
		exit;
	}

	/**
	 * @param string $template Template.
	 */
	public static function screen( $template ) {
		if ( self::on_screen() && is_user_logged_in() && get_user_meta( get_current_user_id(), self::PENDING, true ) ) {
			return dirname( __DIR__ ) . '/templates/consent.php';
		}
		return $template;
	}

	/** Checkbox in the theme's registration form. */
	public static function print_field_script(): void {
		if ( is_user_logged_in() || ! is_page_template( 'page-login.php' ) ) {
			return;
		}
		$err  = isset( $_GET['error'] ) && self::ERROR === sanitize_key( wp_unslash( $_GET['error'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$data = [
			'name'  => self::FIELD,
			'html'  => self::label_html(),
			'error' => t( 'terms_error' ),
			'show'  => $err,
		];
		?>
<script id="tbf-terms">
(function () {
	var d = <?php echo wp_json_encode( $data ); ?>;
	var hidden = document.querySelector('form input[name="tb_action"][value="register"]');
	if (!hidden) { return; }
	var form = hidden.form, submit = form.querySelector('button[type="submit"]');
	if (!submit || form.querySelector('[name="' + d.name + '"]')) { return; }
	var wrap = document.createElement('div'); wrap.className = 'tb-auth-form__field tbf-terms';
	var label = document.createElement('label'); label.className = 'tbf-terms__label';
	var box = document.createElement('input'); box.type = 'checkbox'; box.name = d.name; box.value = '1'; box.required = true; box.id = 'tbf-terms';
	var text = document.createElement('span'); text.innerHTML = d.html;
	var err = document.createElement('span'); err.className = 'tb-field-error tbf-terms__error'; err.setAttribute('role', 'alert'); err.textContent = d.show ? d.error : '';
	label.appendChild(box); label.appendChild(text); wrap.appendChild(label); wrap.appendChild(err);
	var anchor = form.querySelector('#tb-recaptcha-token') || submit;
	anchor.parentNode.insertBefore(wrap, anchor);
	box.addEventListener('invalid', function (e) { e.preventDefault(); err.textContent = d.error; });
	box.addEventListener('change', function () { if (box.checked) { err.textContent = ''; } });
	form.addEventListener('submit', function (e) { if (!box.checked) { e.preventDefault(); e.stopImmediatePropagation(); err.textContent = d.error; box.focus(); } }, true);
	if (d.show) { wrap.scrollIntoView({ block: 'center' }); }
}());
</script>
<style>.tbf-terms__label{display:flex;gap:10px;align-items:flex-start;font-size:14px;line-height:1.45;cursor:pointer}.tbf-terms__label input{margin-top:3px;width:18px;height:18px;flex:none;accent-color:#02f797}.tbf-terms__label a{text-decoration:underline}.tbf-terms__error{display:block;margin-top:6px;color:#d0021b;font-size:13px}</style>
		<?php
	}
}
