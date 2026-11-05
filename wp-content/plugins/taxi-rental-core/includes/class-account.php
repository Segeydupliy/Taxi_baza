<?php
/**
 * class-account.php
 * Handles registration, login, password recovery and reset.
 * All form processing runs on init (before headers sent).
 */

namespace TaxiRental\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Account {

	public static function init(): void {
		add_action( 'init', [ static::class, 'handle_form' ] );
		add_action( 'template_redirect', [ static::class, 'redirect_if_logged_in' ] );
		add_action( 'template_redirect', [ static::class, 'redirect_if_unconfirmed' ] );

		// Point all WP-generated login/register/lost-password links at our custom page.
		add_filter( 'login_url',        [ static::class, 'custom_login_url' ], 10, 3 );
		add_filter( 'register_url',     [ static::class, 'custom_register_url' ] );
		add_filter( 'lostpassword_url', [ static::class, 'custom_lostpassword_url' ], 10, 2 );

		// Redirect direct hits to wp-login.php to our custom page.
		add_action( 'login_init', [ static::class, 'redirect_wp_login' ] );
	}

	/** Redirect already-authenticated users away from the login page. */
	public static function redirect_if_logged_in(): void {
		if ( ! is_user_logged_in() || ! is_page( 'login' ) ) {
			return;
		}

		// No "choose account type" step any more — sign-up (form or Google)
		// always produces a car owner, so a logged-in user never has a reason
		// to be on /login/.
		wp_safe_redirect( tb_url( '/cabinet/' ) );
		exit;
	}

	/** Log out and redirect users whose email is still unconfirmed (tb_email_confirmed = '0'). */
	public static function redirect_if_unconfirmed(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( '0' !== (string) get_user_meta( $user_id, 'tb_email_confirmed', true ) ) {
			return;
		}

		$email = (string) get_userdata( $user_id )->user_email;
		wp_logout();
		wp_safe_redirect( add_query_arg(
			[ 'action' => 'confirm_email', 'email' => rawurlencode( $email ) ],
			tb_url( '/login/' )
		) );
		exit;
	}

	/** Route form submissions by tb_action value. */
	public static function handle_form(): void {
		// Email confirmation link click (GET request from email).
		if ( ! empty( $_GET['tb_confirm_token'] ) ) {
			static::process_confirm_email();
			return;
		}

		if ( empty( $_POST['tb_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['tb_action'] ) );

		switch ( $action ) {
			case 'login':
				static::process_login();
				break;
			case 'register':
				static::process_register();
				break;
			case 'recover':
				static::process_recover();
				break;
			case 'reset_password':
				static::process_reset();
				break;
			case 'resend_confirm':
				static::process_resend_confirm();
				break;
		}
	}

	// -------------------------------------------------------------------------
	// Login
	// -------------------------------------------------------------------------

	private static function process_login(): void {
		if ( ! isset( $_POST['tb_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_nonce'] ) ), 'tb_login' )
		) {
			static::redirect_error( 'login', 'invalid_nonce' );
		}

		// Honeypot.
		if ( ! empty( $_POST['tb_website'] ) ) {
			static::redirect_error( 'login', 'spam' );
		}

		$email    = sanitize_email( wp_unslash( $_POST['tb_email'] ?? '' ) );
		$password = wp_unslash( $_POST['tb_password'] ?? '' );

		if ( empty( $email ) || empty( $password ) ) {
			static::redirect_error( 'login', 'empty_fields' );
		}

		$user = wp_signon(
			[
				'user_login'    => $email,
				'user_password' => $password,
				'remember'      => ! empty( $_POST['tb_remember'] ),
			],
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			static::redirect_error( 'login', 'invalid_credentials' );
		}

		// Deny access for blocked accounts.
		if ( 'blocked' === get_user_meta( $user->ID, 'tb_account_status', true ) ) {
			wp_logout();
			wp_safe_redirect( add_query_arg( 'blocked', '1', tb_url( '/login/' ) ) );
			exit;
		}

		// Require email confirmation (only blocks when meta is explicitly '0' — pre-existing users pass through).
		if ( '0' === (string) get_user_meta( $user->ID, 'tb_email_confirmed', true ) ) {
			wp_logout();
			wp_safe_redirect( add_query_arg(
				[ 'action' => 'confirm_email', 'email' => rawurlencode( $user->user_email ) ],
				tb_url( '/login/' )
			) );
			exit;
		}

		$redirect = tb_url( '/cabinet/' );
		if ( ! empty( $_POST['tb_redirect'] ) ) {
			$redirect = esc_url_raw( wp_unslash( $_POST['tb_redirect'] ) );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	// -------------------------------------------------------------------------
	// Register
	// -------------------------------------------------------------------------

	private static function process_register(): void {
		if ( ! isset( $_POST['tb_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_nonce'] ) ), 'tb_register' )
		) {
			static::redirect_error( 'register', 'invalid_nonce' );
		}

		if ( ! empty( $_POST['tb_website'] ) ) {
			static::redirect_error( 'register', 'spam' );
		}

		// reCAPTCHA v3 verification.
		if ( defined( 'TB_RECAPTCHA_SECRET_KEY' ) && TB_RECAPTCHA_SECRET_KEY ) {
			$token = sanitize_text_field( wp_unslash( $_POST['tb_recaptcha_token'] ?? '' ) );

			if ( empty( $token ) ) {
				static::redirect_error( 'register', 'recaptcha_failed' );
			}

			$rc_response = wp_remote_post(
				'https://www.google.com/recaptcha/api/siteverify',
				[
					'body' => [
						'secret'   => TB_RECAPTCHA_SECRET_KEY,
						'response' => $token,
						'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
					],
				]
			);

			if ( is_wp_error( $rc_response ) ) {
				static::redirect_error( 'register', 'recaptcha_failed' );
			}

			$rc_result = json_decode( wp_remote_retrieve_body( $rc_response ), true );
			$rc_score  = (float) ( $rc_result['score'] ?? 0 );

			if ( empty( $rc_result['success'] ) || $rc_score < 0.5 ) {
				static::redirect_error( 'register', 'recaptcha_failed' );
			}
		}

		if ( ! get_option( 'users_can_register' ) ) {
			static::redirect_error( 'register', 'registration_closed' );
		}

		$display_name = sanitize_text_field( wp_unslash( $_POST['tb_name'] ?? '' ) );
		$email        = sanitize_email( wp_unslash( $_POST['tb_email'] ?? '' ) );
		$password     = wp_unslash( $_POST['tb_password'] ?? '' );

		// The account type is fixed, not submitted. Ignoring any tb_account_type
		// in the payload is the point: a crafted POST must not be able to create
		// a fleet account, because that upgrade is manual by design.
		$account_type = Roles::DEFAULT_ACCOUNT_TYPE;

		// Validate required.
		if ( empty( $display_name ) || empty( $email ) || empty( $password ) ) {
			static::redirect_error( 'register', 'empty_fields' );
		}

		if ( ! is_email( $email ) ) {
			static::redirect_error( 'register', 'invalid_email' );
		}

		if ( strlen( $password ) < 8 ) {
			static::redirect_error( 'register', 'weak_password' );
		}

		$password_confirm = wp_unslash( $_POST['tb_password_confirm'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- passwords must not be altered.
		if ( $password !== $password_confirm ) {
			static::redirect_error( 'register', 'password_mismatch' );
		}

		if ( email_exists( $email ) ) {
			static::redirect_error( 'register', 'email_taken' );
		}

		// Create user — username = email.
		$username = sanitize_user( $email, true );
		if ( username_exists( $username ) ) {
			$username = sanitize_user( $email . '_' . wp_rand( 10, 99 ), true );
		}

		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			static::redirect_error( 'register', 'creation_failed' );
		}

		// Assign role and meta.
		$user = new \WP_User( $user_id );
		$user->set_role( $account_type );

		$name_parts = explode( ' ', $display_name, 2 );
		$first_name = $name_parts[0];
		$last_name  = ! empty( $name_parts[1] ) ? $name_parts[1] : '';

		wp_update_user( [ 'ID' => $user_id, 'display_name' => $display_name ] );
		update_user_meta( $user_id, 'first_name', $first_name );
		update_user_meta( $user_id, 'last_name', $last_name );
		update_user_meta( $user_id, 'account_type', $account_type );

		// Admin notification is sent by Notifications::on_user_register() via user_register hook.

		// Mark as pending email confirmation and send confirmation link.
		update_user_meta( $user_id, 'tb_email_confirmed', '0' );
		static::send_confirmation_email( $user_id );

		wp_safe_redirect( add_query_arg(
			[ 'action' => 'confirm_email', 'email' => rawurlencode( $email ) ],
			tb_url( '/login/' )
		) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Password recovery (step 1 — send email)
	// -------------------------------------------------------------------------

	private static function process_recover(): void {
		if ( ! isset( $_POST['tb_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_nonce'] ) ), 'tb_recover' )
		) {
			static::redirect_error( 'recover', 'invalid_nonce' );
		}

		if ( ! empty( $_POST['tb_website'] ) ) {
			static::redirect_error( 'recover', 'spam' );
		}

		$email = sanitize_email( wp_unslash( $_POST['tb_email'] ?? '' ) );

		if ( empty( $email ) || ! is_email( $email ) ) {
			static::redirect_error( 'recover', 'invalid_email' );
		}

		$user = get_user_by( 'email', $email );

		// Always show success message — prevents user enumeration.
		if ( $user ) {
			$key         = get_password_reset_key( $user );
			$reset_url   = add_query_arg(
				[
					'action' => 'reset_password',
					'key'    => rawurlencode( $key ),
					'login'  => rawurlencode( $user->user_login ),
				],
				tb_url( '/login/' )
			);

			[ $subj, $html, $headers ] = Notifications::render(
				'recover',
				[
					'site_name' => get_bloginfo( 'name' ),
					'name'      => $user->display_name,
					'reset_url' => $reset_url,
				]
			);

			wp_mail( $email, $subj, $html, $headers );
		}

		wp_safe_redirect( add_query_arg(
			[ 'action' => 'recover', 'sent' => '1', 'email' => rawurlencode( $email ) ],
			tb_url( '/login/' )
		) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Password reset (step 2 — save new password)
	// -------------------------------------------------------------------------

	private static function process_reset(): void {
		if ( ! isset( $_POST['tb_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_nonce'] ) ), 'tb_reset_password' )
		) {
			static::redirect_error( 'reset_password', 'invalid_nonce' );
		}

		$key              = sanitize_text_field( wp_unslash( $_POST['tb_key'] ?? '' ) );
		$login            = sanitize_user( wp_unslash( $_POST['tb_login'] ?? '' ) );
		$password         = wp_unslash( $_POST['tb_password'] ?? '' );
		$password_confirm = wp_unslash( $_POST['tb_password_confirm'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- passwords must not be altered.

		if ( empty( $key ) || empty( $login ) || strlen( $password ) < 8 ) {
			static::redirect_error( 'reset_password', 'invalid_data' );
		}

		if ( $password !== $password_confirm ) {
			static::redirect_error( 'reset_password', 'password_mismatch' );
		}

		$user = check_password_reset_key( $key, $login );

		if ( is_wp_error( $user ) ) {
			static::redirect_error( 'reset_password', 'invalid_key' );
		}

		reset_password( $user, $password );

		wp_safe_redirect( add_query_arg( [ 'action' => 'reset_password', 'done' => '1' ], tb_url( '/login/' ) ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Custom login URL filters
	// -------------------------------------------------------------------------

	/** Replace wp_login_url() output with our /login/ page. */
	public static function custom_login_url( string $login_url, string $redirect, bool $force_reauth ): string {
		$url = tb_url( '/login/' );
		if ( $redirect ) {
			$url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
		}
		return $url;
	}

	/** Replace the WP registration URL. */
	public static function custom_register_url(): string {
		return add_query_arg( 'action', 'register', tb_url( '/login/' ) );
	}

	/** Replace the lost-password URL. */
	public static function custom_lostpassword_url( string $lostpassword_url, string $redirect ): string {
		return add_query_arg( 'action', 'recover', tb_url( '/login/' ) );
	}

	/**
	 * Redirect direct hits to wp-login.php to the custom /login/ page.
	 * Runs on login_init (early in wp-login.php loading, before output).
	 * Skips logout, postpass, and admin-redirected requests so those still work.
	 */
	public static function redirect_wp_login(): void {
		$action = sanitize_key( $_GET['action'] ?? '' );

		// Logout and password-protected post unlocking must stay on wp-login.php.
		if ( in_array( $action, [ 'logout', 'postpass' ], true ) ) {
			return;
		}

		// If WP is trying to send the admin to wp-admin after login, don't intercept.
		$redirect_to = rawurldecode( wp_unslash( $_GET['redirect_to'] ?? '' ) );
		if ( $redirect_to && false !== strpos( $redirect_to, 'wp-admin' ) ) {
			return;
		}

		// Password-reset link from WP email: map key + login to our reset_password state.
		if ( in_array( $action, [ 'rp', 'resetpass' ], true ) ) {
			$key   = sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) );
			$login = sanitize_user( wp_unslash( $_GET['login'] ?? '' ) );
			if ( $key && $login ) {
				wp_safe_redirect( add_query_arg(
					[ 'action' => 'reset_password', 'key' => $key, 'login' => $login ],
					tb_url( '/login/' )
				) );
				exit;
			}
		}

		// Map WP action names to our page states.
		$state_map = [
			'register'         => 'register',
			'lostpassword'     => 'recover',
			'retrievepassword' => 'recover',
		];
		$state = $state_map[ $action ] ?? '';

		wp_safe_redirect( $state
			? add_query_arg( 'action', $state, tb_url( '/login/' ) )
			: tb_url( '/login/' )
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// Email confirmation
	// -------------------------------------------------------------------------

	private static function send_confirmation_email( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$token = wp_generate_password( 32, false );
		update_user_meta( $user_id, 'tb_email_confirm_token', $token );

		$confirm_url = add_query_arg(
			[
				'tb_confirm_token' => $token,
				'tb_confirm_login' => $user->user_login,
			],
			tb_url( '/login/' )
		);

		[ $subj, $html, $headers ] = Notifications::render(
			'confirm',
			[
				'site_name'   => get_bloginfo( 'name' ),
				'name'        => $user->display_name,
				'confirm_url' => $confirm_url,
			]
		);

		wp_mail( $user->user_email, $subj, $html, $headers );
	}

	private static function process_confirm_email(): never {
		$token = sanitize_text_field( wp_unslash( $_GET['tb_confirm_token'] ?? '' ) );
		$login = sanitize_user( wp_unslash( $_GET['tb_confirm_login'] ?? '' ) );

		if ( ! $token || ! $login ) {
			static::redirect_error( 'login', 'invalid_key' );
		}

		$user = get_user_by( 'login', $login );
		if ( ! $user ) {
			static::redirect_error( 'login', 'invalid_key' );
		}

		// If already confirmed (e.g. email client pre-fetched the link), treat as success.
		if ( '1' === (string) get_user_meta( $user->ID, 'tb_email_confirmed', true ) ) {
			wp_safe_redirect( add_query_arg(
				[ 'action' => 'confirm_email', 'confirmed' => '1' ],
				tb_url( '/login/' )
			) );
			exit;
		}

		$stored = (string) get_user_meta( $user->ID, 'tb_email_confirm_token', true );
		if ( ! $stored || ! hash_equals( $stored, $token ) ) {
			static::redirect_error( 'login', 'invalid_key' );
		}

		delete_user_meta( $user->ID, 'tb_email_confirm_token' );
		update_user_meta( $user->ID, 'tb_email_confirmed', '1' );

		// Show success screen — user must log in manually.
		wp_safe_redirect( add_query_arg(
			[ 'action' => 'confirm_email', 'confirmed' => '1' ],
			tb_url( '/login/' )
		) );
		exit;
	}

	private static function process_resend_confirm(): never {
		if ( ! isset( $_POST['tb_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_nonce'] ) ), 'tb_resend_confirm' )
		) {
			static::redirect_error( 'login', 'invalid_nonce' );
		}

		$email = sanitize_email( wp_unslash( $_POST['tb_email'] ?? '' ) );

		// Always succeed to prevent user enumeration.
		if ( $email && is_email( $email ) ) {
			$user = get_user_by( 'email', $email );
			if ( $user && '1' !== (string) get_user_meta( $user->ID, 'tb_email_confirmed', true ) ) {
				static::send_confirmation_email( $user->ID );
			}
		}

		wp_safe_redirect( add_query_arg(
			[ 'action' => 'confirm_email', 'email' => rawurlencode( $email ), 'sent' => '1' ],
			tb_url( '/login/' )
		) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private static function redirect_error( string $action, string $code ): never {
		// Flash cookie so the error is readable even when the server strips GET params.
		setcookie(
			'tb_flash_error',
			$code,
			[
				'expires'  => time() + 120,
				'path'     => (string) COOKIEPATH,
				'domain'   => (string) COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
		wp_safe_redirect(
			add_query_arg(
				[ 'action' => $action, 'error' => $code ],
				tb_url( '/login/' )
			)
		);
		exit;
	}

	/** Maps error codes to translatable messages for the template. */
	public static function error_message( string $code ): string {
		$messages = [
			'invalid_nonce'        => __( 'Chyba zabezpečenia. Skúste znova.', 'taxi-rental' ),
			'spam'                 => __( 'Kontrola spamu zlyhala.', 'taxi-rental' ),
			'empty_fields'         => __( 'Vyplňte všetky povinné polia.', 'taxi-rental' ),
			'invalid_email'        => __( 'Zadajte platnú emailovú adresu.', 'taxi-rental' ),
			'weak_password'        => __( 'Heslo musí mať aspoň 8 znakov.', 'taxi-rental' ),
			'password_mismatch'    => __( 'Heslá sa nezhodujú.', 'taxi-rental' ),
			'invalid_account_type' => __( 'Vyberte typ účtu.', 'taxi-rental' ),
			'email_taken'          => __( 'Tento email je už zaregistrovaný. <a href="/login/">Prihláste sa</a>?', 'taxi-rental' ),
			'invalid_credentials'  => __( 'Nesprávny email alebo heslo.', 'taxi-rental' ),
			'creation_failed'      => __( 'Registrácia zlyhala. Skúste znova.', 'taxi-rental' ),
			'registration_closed'  => __( 'Registrácia je momentálne uzavretá.', 'taxi-rental' ),
			'invalid_key'          => __( 'Platnosť odkazu vypršala. Požiadajte o nový.', 'taxi-rental' ),
			'invalid_data'         => __( 'Neplatná požiadavka. Skúste znova.', 'taxi-rental' ),
			'recaptcha_failed'     => __( 'Overenie reCAPTCHA zlyhalo. Skúste znova.', 'taxi-rental' ),
		];

		return $messages[ $code ] ?? __( 'Nastala chyba. Skúste znova.', 'taxi-rental' );
	}
}
