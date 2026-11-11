<?php
/**
 * Public fleet pages: /flotily/ (catalogue) and /flotily/<slug>/ (one fleet).
 * Figma: Flotillas (896:8179), Flotilla (909:11272).
 *
 * The same base slug is used in every language; WPML adds its language folder
 * (/uk/flotily/, /en/flotily/, /ru/flotily/). The rewrite rules also accept a
 * two-letter prefix in case the language folder reaches WP's rewrite matching.
 * Rules are flushed once per plugin version (the plugin is updated by zip upload,
 * so the activation hook does not run).
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Fleet_Pages {

	public const QV_LIST  = 'tbf_fleets';
	public const QV_FLEET = 'tbf_fleet';
	public const OPT_RULES = 'tbf_rewrite_version';

	private static int $fleet = 0;

	public static function init(): void {
		add_action( 'init', [ self::class, 'add_rules' ] );
		add_action( 'init', [ self::class, 'maybe_flush' ], 99 );
		add_filter( 'query_vars', [ self::class, 'query_vars' ] );
		add_action( 'pre_get_posts', [ self::class, 'light_main_query' ] );
		add_filter( 'pre_handle_404', [ self::class, 'skip_404' ], 10, 2 );
		add_action( 'template_redirect', [ self::class, 'route' ], 1 );
		add_filter( 'redirect_canonical', [ self::class, 'no_canonical_redirect' ], 5 );
		add_filter( 'template_include', [ self::class, 'template' ], 98 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ] );
		add_filter( 'pre_get_document_title', [ self::class, 'document_title' ], 20 );
		add_filter( 'wpseo_title', [ self::class, 'seo_title' ], 20 );
		add_filter( 'wpseo_opengraph_title', [ self::class, 'seo_title' ], 20 );
		add_filter( 'wpseo_metadesc', [ self::class, 'seo_desc' ], 20 );
		add_filter( 'wpseo_opengraph_desc', [ self::class, 'seo_desc' ], 20 );
		add_filter( 'wpseo_canonical', [ self::class, 'seo_url' ], 20 );
		add_filter( 'wpseo_opengraph_url', [ self::class, 'seo_url' ], 20 );
		add_filter( 'body_class', [ self::class, 'body_class' ] );
		add_filter( 'icl_ls_languages', [ self::class, 'language_links' ] );
		add_action( 'wp_head', [ self::class, 'meta_description' ], 2 );
	}

	/* ---------------- Routing ---------------- */

	public static function add_rules(): void {
		$base = Directory::BASE;
		add_rewrite_rule( '^(?:[a-z]{2}/)?' . $base . '/?$', 'index.php?' . self::QV_LIST . '=1', 'top' );
		add_rewrite_rule( '^(?:[a-z]{2}/)?' . $base . '/([^/]+)/?$', 'index.php?' . self::QV_FLEET . '=$matches[1]', 'top' );
	}

	/** Flush once after an update (zip upload does not run the activation hook). */
	public static function maybe_flush(): void {
		if ( get_option( self::OPT_RULES ) !== VERSION ) {
			flush_rewrite_rules( false );
			update_option( self::OPT_RULES, VERSION, true );
		}
	}

	public static function on_activate(): void {
		delete_option( self::OPT_RULES ); // Flushed on the next request, after the rules are registered.
	}

	/**
	 * @param string[] $vars Query vars.
	 * @return string[]
	 */
	public static function query_vars( $vars ): array {
		$vars[] = self::QV_LIST;
		$vars[] = self::QV_FLEET;
		return $vars;
	}

	public static function is_list(): bool {
		return '' !== (string) get_query_var( self::QV_LIST );
	}

	public static function fleet_slug(): string {
		return sanitize_title( (string) get_query_var( self::QV_FLEET ) );
	}

	/** Any fleet page (catalogue or a found fleet). */
	public static function is_fleet_page(): bool {
		return self::is_list() || self::$fleet > 0;
	}

	public static function current_fleet(): int {
		return self::$fleet;
	}

	/**
	 * The main query of our pages would load blog posts for nothing.
	 *
	 * @param \WP_Query $q Query.
	 */
	public static function light_main_query( $q ): void {
		if ( ! $q->is_main_query() || is_admin() ) {
			return;
		}
		if ( '' !== (string) $q->get( self::QV_LIST ) || '' !== (string) $q->get( self::QV_FLEET ) ) {
			$q->set( 'posts_per_page', 1 );
			$q->set( 'no_found_rows', true );
			$q->set( 'ignore_sticky_posts', true );
		}
	}

	/**
	 * @param bool      $pre   Short-circuit.
	 * @param \WP_Query $query Query.
	 */
	public static function skip_404( $pre, $query ) {
		if ( '' !== (string) $query->get( self::QV_LIST ) || '' !== (string) $query->get( self::QV_FLEET ) ) {
			return true; // route() decides (unknown fleet → 404).
		}
		return $pre;
	}

	/**
	 * WordPress must not "correct" our URLs (to the blog / front page, or to a
	 * post with a similar name when the fleet is unknown).
	 *
	 * @param string|false $redirect Redirect URL.
	 * @return string|false
	 */
	public static function no_canonical_redirect( $redirect ) {
		if ( self::is_list() || '' !== (string) get_query_var( self::QV_FLEET ) ) {
			return false;
		}
		return $redirect;
	}

	public static function route(): void {
		if ( self::is_list() ) {
			return;
		}
		$slug = self::fleet_slug();
		if ( '' === (string) get_query_var( self::QV_FLEET ) ) {
			return;
		}
		$uid = Directory::find( $slug );
		if ( ! $uid ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}
		$canonical = (string) get_user_meta( $uid, Directory::META_SLUG, true );
		if ( '' !== $canonical && $canonical !== $slug ) {
			wp_safe_redirect( Directory::fleet_url( $uid ), 301 ); // /flotily/123/ → /flotily/gold-fleet/.
			exit;
		}
		self::$fleet = $uid;
		status_header( 200 );
	}

	/**
	 * @param string $template Template.
	 */
	public static function template( $template ) {
		if ( self::is_list() ) {
			return dirname( __DIR__ ) . '/templates/fleets-catalog.php';
		}
		if ( self::$fleet ) {
			return dirname( __DIR__ ) . '/templates/fleet-single.php';
		}
		return $template;
	}

	public static function assets(): void {
		if ( ! self::is_fleet_page() ) {
			return;
		}
		$base = plugins_url( 'assets/', dirname( __DIR__ ) . '/taxibaza-fleets.php' );
		wp_enqueue_style( 'tbf-fleets', $base . 'fleets.css', [], VERSION );
		wp_enqueue_script( 'tbf-fleets', $base . 'fleets.js', [], VERSION, true );
		wp_localize_script(
			'tbf-fleets',
			'tbfFleets',
			[
				'ajax'   => admin_url( 'admin-ajax.php' ),
				'action' => Work_Request::ACTION,
				'lang'   => I18n::lang(),
				'i18n'   => [
					'banner' => t( 'fl_m_err_banner' ),
					'server' => t( 'err_server' ),
				],
			]
		);
	}

	/* ---------------- Titles, SEO, language switcher ---------------- */

	public static function title(): string {
		$site = (string) get_bloginfo( 'name' );
		$sep  = (string) apply_filters( 'document_title_separator', '–' );
		if ( self::$fleet ) {
			return Listing_Fleet::fleet_name( self::$fleet ) . " $sep " . t( 'fl_nav' ) . " $sep $site";
		}
		return t( 'fl_hero_title' ) . " $sep " . t( 'fl_nav' ) . " $sep $site";
	}

	/**
	 * @param string $title Title.
	 */
	public static function document_title( $title ) {
		return self::is_fleet_page() ? self::title() : $title;
	}

	/**
	 * @param string $title Title.
	 */
	public static function seo_title( $title ) {
		return self::is_fleet_page() ? self::title() : $title;
	}

	public static function description(): string {
		if ( self::$fleet ) {
			$card = Directory::card( self::$fleet );
			$desc = Directory::excerpt( (string) $card['desc'], 155 );
			return '' !== $desc ? $desc : t( 'fl_meta_desc' );
		}
		return t( 'fl_meta_desc' );
	}

	/**
	 * @param string $desc Description.
	 */
	public static function seo_desc( $desc ) {
		return self::is_fleet_page() ? self::description() : $desc;
	}

	public static function url( ?string $lang = null ): string {
		return self::$fleet ? Directory::fleet_url( self::$fleet, $lang ) : Directory::base_url( $lang );
	}

	/**
	 * @param string $url URL.
	 */
	public static function seo_url( $url ) {
		return self::is_fleet_page() ? self::url() : $url;
	}

	/** Meta description when Yoast is not active. */
	public static function meta_description(): void {
		if ( self::is_fleet_page() && ! defined( 'WPSEO_VERSION' ) ) {
			echo '<meta name="description" content="' . esc_attr( self::description() ) . '">' . "\n";
			echo '<link rel="canonical" href="' . esc_url( self::url() ) . '">' . "\n";
		}
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ): array {
		$classes = (array) $classes;
		if ( self::is_fleet_page() ) {
			$classes   = array_values( array_diff( $classes, [ 'blog', 'home' ] ) );
			$classes[] = self::$fleet ? 'tbf-page-fleet' : 'tbf-page-fleets';
		}
		return $classes;
	}

	/**
	 * Language switcher on our pages links to the same fleet page in each language
	 * (WPML would otherwise offer the home page, as the main query looks like the blog).
	 *
	 * @param array<string,array<string,mixed>> $languages WPML languages.
	 * @return array<string,array<string,mixed>>
	 */
	public static function language_links( $languages ) {
		if ( ! is_array( $languages ) || ! did_action( 'template_redirect' ) || ! self::is_fleet_page() ) {
			return $languages;
		}
		foreach ( $languages as $code => &$l ) {
			$l['url'] = self::url( (string) ( $l['code'] ?? $code ) );
		}
		unset( $l );
		return $languages;
	}
}
