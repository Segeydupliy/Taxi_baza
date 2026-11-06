<?php
/**
 * WPML registration guard for the theme's options strings.
 *
 * The theme's tb_opt() (and the core's Notifications::opt()) call
 * `wpml_register_single_string` on every read, passing the value of the ACF
 * option in the *current* language (ACFML stores options per language:
 * options_<name>, options_en_<name>, ...). On /en/, /uk/, /ru/ pages that
 * overwrote the Slovak original in WPML with the English/Ukrainian/Russian text,
 * and every page view rewrote dozens of strings back and forth (≈90 UPDATE
 * queries per page, the main reason the site was slow, and the cause of
 * Slovak/Ukrainian texts appearing on the wrong language version).
 *
 * The guard keeps the theme's intent — "the Slovak value from the options page
 * is the source; edits to it are picked up automatically" — but:
 *  - for the context `taxi-rental-options` it always registers the Slovak value
 *    (options_<name>) with source language `sk`, whatever language the page is in;
 *  - it skips the call entirely when that exact value is already registered
 *    (remembered in one non-autoloaded option, reset daily and whenever the WPML
 *    String Translation screen is opened).
 * Translation lookups (`wpml_translate_single_string`) are not touched.
 *
 * @package TaxiBaza\Speed
 */

namespace TaxiBaza\Speed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WPML_Guard {

	public const HOOK        = 'wpml_register_single_string';
	public const OPTIONS_CTX = 'taxi-rental-options';
	public const CACHE       = 'tbs_wpml_registered';
	public const TTL         = DAY_IN_SECONDS;

	/** @var array<int,array{function:callable,accepted_args:int}> Original callbacks, by priority order. */
	private static array $originals = [];

	/** @var array{t:int,h:array<string,string>}|null */
	private static ?array $cache = null;

	private static bool $dirty = false;

	public static function init(): void {
		add_action( 'init', [ self::class, 'wrap' ], 1 );
		add_action( 'wp', [ self::class, 'wrap' ], 1 );
		add_action( 'shutdown', [ self::class, 'save' ] );
		add_action( 'admin_init', [ self::class, 'reset_on_st_screen' ] );
		add_action( 'acf/save_post', [ self::class, 'reset' ], 99 );
	}

	/** Move WPML's own callbacks behind the guard (idempotent). */
	public static function wrap(): void {
		global $wp_filter;
		if ( empty( $wp_filter[ self::HOOK ] ) || ! $wp_filter[ self::HOOK ] instanceof \WP_Hook ) {
			return;
		}
		foreach ( $wp_filter[ self::HOOK ]->callbacks as $priority => $cbs ) {
			foreach ( $cbs as $id => $cb ) {
				if ( [ self::class, 'register' ] === $cb['function'] ) {
					continue;
				}
				self::$originals[] = $cb;
				remove_action( self::HOOK, $cb['function'], $priority );
			}
		}
		if ( self::$originals && false === has_action( self::HOOK, [ self::class, 'register' ] ) ) {
			add_action( self::HOOK, [ self::class, 'register' ], 10, 5 );
		}
	}

	/**
	 * The guarded `wpml_register_single_string` action.
	 *
	 * @param string      $context     String domain.
	 * @param string      $name        String name.
	 * @param mixed       $value       Value.
	 * @param bool        $allow_empty Allow empty value.
	 * @param string|null $lang        Source language.
	 */
	public static function register( $context = '', $name = '', $value = '', $allow_empty = false, $lang = null ): void {
		$context = (string) $context;
		$name    = (string) $name;

		if ( self::OPTIONS_CTX === $context ) {
			$sk = get_option( 'options_' . $name, null );
			if ( is_string( $sk ) && '' !== $sk ) {
				$value = $sk;
				$lang  = 'sk';
			} elseif ( 'sk' !== self::current_lang() ) {
				return; // No Slovak value to register, and the current one is another language: never use it as the source.
			}
		}
		if ( ! is_string( $value ) ) {
			self::call( [ $context, $name, $value, $allow_empty, $lang ] );
			return;
		}

		$key  = $context . '|' . $name;
		$hash = md5( $value . '|' . (string) $lang );
		$c    = self::cache();
		if ( isset( $c['h'][ $key ] ) && $c['h'][ $key ] === $hash ) {
			return; // Already registered with exactly this value.
		}
		self::call( [ $context, $name, $value, $allow_empty, $lang ] );
		self::$cache['h'][ $key ] = $hash;
		self::$dirty              = true;
	}

	/** @param array<int,mixed> $args */
	private static function call( array $args ): void {
		foreach ( self::$originals as $cb ) {
			call_user_func_array( $cb['function'], array_slice( $args, 0, max( 1, (int) $cb['accepted_args'] ) ) );
		}
	}

	private static function current_lang(): string {
		$l = apply_filters( 'wpml_current_language', null );
		return is_string( $l ) && '' !== $l ? $l : 'sk';
	}

	/** @return array{t:int,h:array<string,string>} */
	private static function cache(): array {
		if ( null === self::$cache ) {
			$c = get_option( self::CACHE );
			if ( ! is_array( $c ) || ! isset( $c['t'], $c['h'] ) || time() - (int) $c['t'] > self::TTL ) {
				$c = [ 't' => time(), 'h' => [] ];
				self::$dirty = true;
			}
			self::$cache = $c;
		}
		return self::$cache;
	}

	public static function save(): void {
		if ( self::$dirty && null !== self::$cache ) {
			update_option( self::CACHE, self::$cache, false );
		}
	}

	public static function reset(): void {
		delete_option( self::CACHE );
		self::$cache = null;
		self::$dirty = false;
	}

	public static function reset_on_st_screen(): void {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( false !== strpos( $page, 'wpml-string-translation' ) ) {
			self::reset();
		}
	}
}
