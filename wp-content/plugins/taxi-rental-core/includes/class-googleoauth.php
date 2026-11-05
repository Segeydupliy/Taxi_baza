<?php
/**
 * Custom "Continue with Google" login/registration (OAuth 2.0 authorization
 * code flow) — no third-party plugin.
 *
 * Everything is pulled from the Google profile except the account type, which
 * the platform needs (owner / fleet / driver). After a first Google login with
 * no account type, the user is sent to a "choose account type" screen
 * (Figma 634:1716) before reaching the cabinet.
 *
 * Configuration — add to wp-config.php (never commit secrets):
 *   define( 'TB_GOOGLE_CLIENT_ID',     '…apps.googleusercontent.com' );
 *   define( 'TB_GOOGLE_CLIENT_SECRET', '…' );
 * Authorized redirect URI in Google Cloud console must be:
 *   https://YOURDOMAIN/login/?tb_google=callback
 *
 * @package TaxiRental\Core
 */

namespace TaxiRental\Core;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google OAuth handler.
 */
final class GoogleOAuth {

	private const AUTH_ENDPOINT     = 'https://accounts.google.com/o/oauth2/v2/auth';
	private const TOKEN_ENDPOINT    = 'https://oauth2.googleapis.com/token';
	private const USERINFO_ENDPOINT = 'https://www.googleapis.com/oauth2/v3/userinfo';
	private const STATE_COOKIE       = 'tb_google_state';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'init', [ self::class, 'route' ] );
	}

	/**
	 * True when Google OAuth credentials are configured.
	 */
	public static function is_configured(): bool {
		return \defined( 'TB_GOOGLE_CLIENT_ID' ) && \constant( 'TB_GOOGLE_CLIENT_ID' )
			&& \defined( 'TB_GOOGLE_CLIENT_SECRET' ) && \constant( 'TB_GOOGLE_CLIENT_SECRET' );
	}

	/**
	 * Where Google redirects back to.
	 */
	public static function redirect_uri(): string {
		return add_query_arg( 'tb_google', 'callback', tb_url( '/login/' ) );
	}

	/**
	 * The "start Google login" URL used by the buttons.
	 */
	public static function start_url(): string {
		return add_query_arg( 'tb_google', 'start', tb_url( '/login/' ) );
	}

	/**
	 * Dispatch the ?tb_google=… routes.
	 */
	public static function route(): void {
		$action = isset( $_GET['tb_google'] ) ? sanitize_key( wp_unslash( $_GET['tb_google'] ) ) : '';
		if ( 'start' === $action ) {
			self::start();
		} elseif ( 'callback' === $action ) {
			self::callback();
		}
	}

	/**
	 * Redirect the user to Google's consent screen.
	 */
	private static function start(): void {
		if ( ! self::is_configured() ) {
			self::fail();
		}

		$state = wp_generate_password( 24, false );
		setcookie(
			self::STATE_COOKIE,
			$state,
			[
				'expires'  => time() + 600,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);

		$url = add_query_arg(
			[
				'client_id'     => rawurlencode( (string) \constant( 'TB_GOOGLE_CLIENT_ID' ) ),
				'redirect_uri'  => rawurlencode( self::redirect_uri() ),
				'response_type' => 'code',
				'scope'         => rawurlencode( 'openid email profile' ),
				'state'         => $state,
				'access_type'   => 'online',
				'prompt'        => 'select_account',
			],
			self::AUTH_ENDPOINT
		);

		wp_redirect( $url );
		exit;
	}

	/**
	 * Handle Google's redirect back: verify state, exchange code, log the user in.
	 */
	private static function callback(): void {
		if ( ! self::is_configured() ) {
			self::fail();
		}

		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$saved = isset( $_COOKIE[ self::STATE_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::STATE_COOKIE ] ) ) : '';

		// Clear the state cookie immediately (single use).
		setcookie( self::STATE_COOKIE, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN ? COOKIE_DOMAIN : '' );

		if ( ! $code || ! $state || ! $saved || ! hash_equals( $saved, $state ) ) {
			self::fail();
		}

		// Exchange the authorization code for tokens.
		$token_res = wp_remote_post(
			self::TOKEN_ENDPOINT,
			[
				'timeout' => 15,
				'body'    => [
					'code'          => $code,
					'client_id'     => (string) \constant( 'TB_GOOGLE_CLIENT_ID' ),
					'client_secret' => (string) \constant( 'TB_GOOGLE_CLIENT_SECRET' ),
					'redirect_uri'  => self::redirect_uri(),
					'grant_type'    => 'authorization_code',
				],
			]
		);

		if ( is_wp_error( $token_res ) || 200 !== (int) wp_remote_retrieve_response_code( $token_res ) ) {
			self::fail();
		}

		$token = json_decode( (string) wp_remote_retrieve_body( $token_res ), true );
		$access_token = $token['access_token'] ?? '';
		if ( ! $access_token ) {
			self::fail();
		}

		// Fetch the user's Google profile.
		$info_res = wp_remote_get(
			self::USERINFO_ENDPOINT,
			[
				'timeout' => 15,
				'headers' => [ 'Authorization' => 'Bearer ' . $access_token ],
			]
		);

		if ( is_wp_error( $info_res ) || 200 !== (int) wp_remote_retrieve_response_code( $info_res ) ) {
			self::fail();
		}

		$info = json_decode( (string) wp_remote_retrieve_body( $info_res ), true );
		$email = isset( $info['email'] ) ? sanitize_email( (string) $info['email'] ) : '';
		$sub   = isset( $info['sub'] ) ? sanitize_text_field( (string) $info['sub'] ) : '';

		if ( ! $email || ! is_email( $email ) || empty( $info['email_verified'] ) ) {
			self::fail();
		}

		$user = self::find_or_create_user( $email, $sub, $info );
		if ( is_wp_error( $user ) ) {
			self::fail();
		}

		// Blocked users cannot log in.
		if ( '1' === get_user_meta( $user->ID, 'tb_user_blocked', true ) ) {
			self::fail( 'blocked' );
		}

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		// There is no "choose account type" step any more: a typeless account —
		// a brand-new Google sign-up, or an old one stranded by the removed
		// screen — becomes a car owner and goes straight to the cabinet.
		$account_type = (string) get_user_meta( $user->ID, 'account_type', true );

		if ( ! in_array( $account_type, [ 'car_owner', 'fleet', 'driver' ], true ) ) {
			$account_type = Roles::DEFAULT_ACCOUNT_TYPE;
			if ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
				$user->set_role( $account_type );
			}
			update_user_meta( $user->ID, 'account_type', $account_type );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[TB Google OAuth] user_id=%d email=%s account_type="%s" -> cabinet',
				$user->ID,
				$email,
				$account_type
			) );
		}

		wp_safe_redirect( tb_url( '/cabinet/' ) );
		exit;
	}

	/**
	 * Find an existing user by Google id or email, or create a new one.
	 *
	 * @param string              $email Verified Google email.
	 * @param string              $sub   Google account id.
	 * @param array<string,mixed> $info  Google userinfo payload.
	 * @return \WP_User|\WP_Error
	 */
	private static function find_or_create_user( string $email, string $sub, array $info ) {
		// 1) Match by stored Google id.
		if ( $sub ) {
			$by_id = get_users(
				[
					'meta_key'   => 'tb_google_id',
					'meta_value' => $sub,
					'number'     => 1,
					'fields'     => 'all',
				]
			);
			if ( $by_id ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( '[TB Google OAuth] matched existing user_id=%d by tb_google_id=%s', $by_id[0]->ID, $sub ) );
				}
				return $by_id[0];
			}
		}

		// 2) Match by email — link the Google id to the existing account.
		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			if ( $sub ) {
				update_user_meta( $existing->ID, 'tb_google_id', $sub );
			}
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf(
					'[TB Google OAuth] matched existing user_id=%d by email=%s (registered %s, account_type="%s")',
					$existing->ID,
					$email,
					$existing->user_registered,
					get_user_meta( $existing->ID, 'account_type', true )
				) );
			}
			return $existing;
		}

		// 3) Create a new account. The role is left empty here and assigned by
		// route() right after, so both branches (new user, pre-existing typeless
		// user) go through exactly one place.
		$username = sanitize_user( current( explode( '@', $email ) ), true );
		if ( ! $username || username_exists( $username ) ) {
			$username = sanitize_user( $email, true );
		}
		if ( username_exists( $username ) ) {
			$username .= '_' . wp_rand( 10, 99 );
		}

		$user_id = wp_insert_user(
			[
				'user_login' => $username,
				'user_email' => $email,
				'user_pass'  => wp_generate_password( 24, true ),
				'first_name' => sanitize_text_field( (string) ( $info['given_name'] ?? '' ) ),
				'last_name'  => sanitize_text_field( (string) ( $info['family_name'] ?? '' ) ),
				'display_name' => sanitize_text_field( (string) ( $info['name'] ?? $username ) ),
				'role'       => '',
			]
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		if ( $sub ) {
			update_user_meta( $user_id, 'tb_google_id', $sub );
		}
		update_user_meta( $user_id, 'tb_email_confirmed', '1' ); // Google verified the email.

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$new_user = new \WP_User( $user_id );
			error_log( sprintf(
				'[TB Google OAuth] created NEW user_id=%d email=%s roles=%s',
				$user_id,
				$email,
				implode( ',', $new_user->roles ) ?: '(none)'
			) );
		}

		return new \WP_User( $user_id );
	}

	/**
	 * Abort the flow and return to the login screen with an error flag.
	 */
	private static function fail( string $code = 'google' ): void {
		wp_safe_redirect( add_query_arg( 'login_error', $code, tb_url( '/login/' ) ) );
		exit;
	}
}
