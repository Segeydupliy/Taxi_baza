<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TAXI_THEME_VERSION', '1.0.0' );
define( 'TAXI_THEME_DIR', get_template_directory() );
define( 'TAXI_THEME_URL', get_template_directory_uri() );

add_action( 'after_setup_theme', 'taxi_rental_setup' );
function taxi_rental_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'menus' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'html5',
		[ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ]
	);

	register_nav_menus(
		[
			'primary' => __( 'Hlavná navigácia', 'taxi-rental' ),
			'mobile'  => __( 'Mobilná navigácia', 'taxi-rental' ),
			'footer'  => __( 'Navigácia v päte', 'taxi-rental' ),
		]
	);

	load_theme_textdomain( 'taxi-rental', TAXI_THEME_DIR . '/languages' );
}

/**
 * Inline SVG icon helper.
 *
 * @param string $name  Icon slug: chevron | close | hamburger | language.
 */
function taxi_icon( string $name ): void {
	$icons = [
		'chevron'   => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'close'     => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
		'hamburger' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><rect y="4" width="24" height="2" rx="1" fill="currentColor"/><rect y="11" width="24" height="2" rx="1" fill="currentColor"/><rect y="18" width="24" height="2" rx="1" fill="currentColor"/></svg>',
		'bell'      => '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" fill="currentColor"/></svg>',
		'language'  => '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.9 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26zm.81 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.987 7.987 0 0 1 5.07 16zm2.95-8H5.07a7.987 7.987 0 0 1 4.33-3.56A15.65 15.65 0 0 0 8.02 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.65-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.35-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z" fill="currentColor"/></svg>',
	];
	if ( isset( $icons[ $name ] ) ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded SVG markup.
		echo $icons[ $name ];
	}
}

add_action( 'wp_enqueue_scripts', 'taxi_rental_enqueue_assets' );
function taxi_rental_enqueue_assets(): void {
	// Preload both display fonts per design spec.
	wp_enqueue_style(
		'taxi-rental-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Hanken+Grotesk:wght@800&display=swap',
		[],
		null
	);

	$tokens_path = TAXI_THEME_DIR . '/assets/css/tokens.css';
	wp_enqueue_style(
		'taxi-rental-tokens',
		TAXI_THEME_URL . '/assets/css/tokens.css',
		[ 'taxi-rental-fonts' ],
		file_exists( $tokens_path ) ? (string) filemtime( $tokens_path ) : TAXI_THEME_VERSION
	);

	$style_path = TAXI_THEME_DIR . '/style.css';
	wp_enqueue_style(
		'taxi-rental-style',
		get_stylesheet_uri(),
		[ 'taxi-rental-tokens' ],
		file_exists( $style_path ) ? (string) filemtime( $style_path ) : TAXI_THEME_VERSION
	);

	$main_js_path = TAXI_THEME_DIR . '/assets/js/main.js';
	wp_enqueue_script(
		'taxi-rental-main',
		TAXI_THEME_URL . '/assets/js/main.js',
		[],
		file_exists( $main_js_path ) ? (string) filemtime( $main_js_path ) : TAXI_THEME_VERSION,
		true
	);

	// Universal form validator — used on auth, cabinet, contact forms.
	$forms_js_path = TAXI_THEME_DIR . '/assets/js/forms.js';
	wp_enqueue_script(
		'taxi-rental-forms',
		TAXI_THEME_URL . '/assets/js/forms.js',
		[],
		file_exists( $forms_js_path ) ? (string) filemtime( $forms_js_path ) : TAXI_THEME_VERSION,
		true
	);

	// Pass AJAX URL + nonce to JS for catalog filters and contact forms.
	wp_localize_script(
		'taxi-rental-main',
		'taxiRental',
		[
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'taxi_rental_ajax' ),
			'genericError'  => __( 'Error', 'taxi-rental' ),
			'submitFailed'  => __( 'Žiadosť sa nepodarilo odoslať. Skúste neskôr.', 'taxi-rental' ),
		]
	);

	// auth.js (the custom account-type dropdown) is gone: registration no longer
	// asks for an account type, so the login page has nothing left for it to wire up.

	// Google reCAPTCHA v3 — only on the login/register page, only if keys are configured.
	if ( is_page( 'login' )
		&& defined( 'TB_RECAPTCHA_SITE_KEY' )
		&& TB_RECAPTCHA_SITE_KEY
	) {
		wp_enqueue_script(
			'google-recaptcha-v3',
			'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( TB_RECAPTCHA_SITE_KEY ),
			[],
			null,
			true
		);
		// Make the site key available to forms.js without an inline script.
		wp_add_inline_script(
			'taxi-rental-forms',
			'window.tbRecaptchaSiteKey = ' . wp_json_encode( TB_RECAPTCHA_SITE_KEY ) . ';',
			'before'
		);
	}

	// Catalog filter dropdowns — archive + city taxonomy + cabinet sort dropdown.
	if ( is_post_type_archive( 'car_listing' ) || is_tax( 'city' ) || is_page_template( 'page-dashboard.php' ) ) {
		$catalog_js = TAXI_THEME_DIR . '/assets/js/catalog.js';
		wp_enqueue_script(
			'taxi-rental-catalog',
			TAXI_THEME_URL . '/assets/js/catalog.js',
			[],
			file_exists( $catalog_js ) ? (string) filemtime( $catalog_js ) : TAXI_THEME_VERSION,
			true
		);
	}

	// Gallery switcher — only on single car listing.
	if ( is_singular( 'car_listing' ) ) {
		$single_js = TAXI_THEME_DIR . '/assets/js/single.js';
		wp_enqueue_script(
			'taxi-rental-single',
			TAXI_THEME_URL . '/assets/js/single.js',
			[],
			file_exists( $single_js ) ? (string) filemtime( $single_js ) : TAXI_THEME_VERSION,
			true
		);
	}

	// Swiper carousel — front page latest listings.
	if ( is_front_page() ) {
		wp_enqueue_style( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11' );
		wp_enqueue_script( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11', true );
		wp_add_inline_script( 'swiper', '(function(){var el=document.querySelector(".tb-listings-swiper");if(!el)return;new Swiper(el,{slidesPerView:4,spaceBetween:24,grabCursor:true,pagination:{el:".tb-listings-dots",clickable:true},navigation:{prevEl:".tb-listings-prev",nextEl:".tb-listings-next"},breakpoints:{0:{slidesPerView:1.2,spaceBetween:16},768:{slidesPerView:2,spaceBetween:24},1025:{slidesPerView:4,spaceBetween:24}}});})();' );
	}

	// Add / Edit Listing page.
	if ( is_page_template( 'page-add-listing.php' ) ) {
		wp_enqueue_media();
		$fa_css = TAXI_THEME_DIR . '/assets/css/front-admin.css';
		wp_enqueue_style(
			'taxi-rental-front-admin',
			TAXI_THEME_URL . '/assets/css/front-admin.css',
			[ 'taxi-rental-tokens' ],
			file_exists( $fa_css ) ? (string) filemtime( $fa_css ) : TAXI_THEME_VERSION
		);
		$al_js = TAXI_THEME_DIR . '/assets/js/add-listing.js';
		wp_enqueue_script(
			'taxi-rental-add-listing',
			TAXI_THEME_URL . '/assets/js/add-listing.js',
			[],
			file_exists( $al_js ) ? (string) filemtime( $al_js ) : TAXI_THEME_VERSION,
			true
		);
		wp_localize_script(
			'taxi-rental-add-listing',
			'tbAddListing',
			[
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'tb_submit_listing' ),
				'selectPhotos' => __( 'Вибрати фото', 'taxi-rental' ),
				'saveError'    => __( 'Помилка збереження', 'taxi-rental' ),
				'networkError' => __( 'Помилка мережі', 'taxi-rental' ),
			]
		);
	}

	// Front-end admin panel.
	if ( is_page_template( 'page-front-admin.php' ) ) {
		wp_enqueue_media();
		// Load TinyMCE only on blog add/edit sub-pages.
		$_req_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) );
		if ( 'blog' === $_req_tab && ( isset( $_GET['edit'] ) || isset( $_GET['new'] ) ) ) {
			wp_enqueue_editor();
		}
		$fa_css = TAXI_THEME_DIR . '/assets/css/front-admin.css';
		wp_enqueue_style(
			'taxi-rental-front-admin',
			TAXI_THEME_URL . '/assets/css/front-admin.css',
			[ 'taxi-rental-tokens' ],
			file_exists( $fa_css ) ? (string) filemtime( $fa_css ) : TAXI_THEME_VERSION
		);
		$fa_js = TAXI_THEME_DIR . '/assets/js/front-admin.js';
		wp_enqueue_script(
			'taxi-rental-front-admin',
			TAXI_THEME_URL . '/assets/js/front-admin.js',
			[],
			file_exists( $fa_js ) ? (string) filemtime( $fa_js ) : TAXI_THEME_VERSION,
			true
		);
		wp_localize_script(
			'taxi-rental-front-admin',
			'tbFrontAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tb_front_admin_nonce' ),
				// Routed through tb_opt(), like every other panel string, so the
				// values are editable in wp-admin and picked up by WPML String
				// Translation. They used to be __() calls with Ukrainian source
				// text and no .po behind them, so the panel answered in
				// Ukrainian whatever language the visitor had chosen.
				'i18n'    => [
					'published'       => tb_opt( 'tb_fa_js_published', 'Zverejnené' ),
					'rejected'        => tb_opt( 'tb_fa_js_rejected', 'Zamietnuté' ),
					'archived'        => tb_opt( 'tb_fa_js_archived', 'Archivované' ),
					'pending'         => tb_opt( 'tb_fa_js_pending', 'Na posúdenie' ),
					'deleted'         => tb_opt( 'tb_fa_js_deleted', 'Odstránené' ),
					'saved'           => tb_opt( 'tb_fa_js_saved', 'Uložené' ),
					'blocked'         => tb_opt( 'tb_fa_js_blocked', 'Používateľ bol zablokovaný' ),
					'unblocked'       => tb_opt( 'tb_fa_js_unblocked', 'Používateľ bol odblokovaný' ),
					'error'           => tb_opt( 'tb_fa_js_error', 'Chyba. Skúste znova.' ),
					'deleteLabel'     => tb_opt( 'tb_fa_js_delete_label', 'Odstrániť' ),
					'catNamePh'       => tb_opt( 'tb_fa_js_cat_name_ph', 'Názov kategórie' ),
					'catNameRequired' => tb_opt( 'tb_fa_js_cat_name_req', 'Názov je povinný.' ),
					'cityNameRequired' => tb_opt( 'tb_fa_js_city_name_req', 'Názov mesta je povinný.' ),
					'mediaProfile'    => tb_opt( 'tb_fa_js_media_profile', 'Fotografia profilu' ),
					'mediaGallery'    => tb_opt( 'tb_fa_js_media_gallery', 'Fotografie' ),
					'mediaCity'       => tb_opt( 'tb_fa_js_media_city', 'Vyberte obrázok mesta' ),
					'mediaCover'      => tb_opt( 'tb_fa_js_media_cover', 'Vybrať fotografiu' ),
					'mediaChoose'     => tb_opt( 'tb_fa_js_media_choose', 'Vybrať' ),
					'mediaAdd'        => tb_opt( 'tb_fa_js_media_add', 'Pridať' ),
					'mediaUse'        => tb_opt( 'tb_fa_js_media_use', 'Použiť' ),
				],
			]
		);
	}
}

add_filter( 'show_admin_bar', 'taxi_rental_show_admin_bar' );
function taxi_rental_show_admin_bar( bool $show ): bool {
	// The front-admin panel has its own header/nav — never show the WP toolbar there.
	if ( is_page_template( 'page-front-admin.php' ) ) {
		return false;
	}

	// Everywhere else: only staff (administrator/editor) get the WP toolbar.
	// Regular users (car_owner, fleet, driver) never see it on the front-end.
	if ( ! is_user_logged_in() ) {
		return false;
	}

	return (bool) array_intersect( (array) wp_get_current_user()->roles, [ 'administrator', 'editor' ] );
}

/**
 * ACF field value with a hardcoded fallback.
 *
 * Returns the saved (or default) ACF value when ACF is active and the value
 * is non-empty; otherwise returns the provided fallback so templates keep
 * rendering the Figma content even before fields are filled in.
 *
 * @param string          $name     ACF field name.
 * @param mixed           $fallback Fallback value.
 * @param int|string|bool $post_id  ACF post id ('option' for options page).
 * @return mixed
 */
function tb_field( string $name, $fallback = '', $post_id = false ) {
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $name, $post_id );
		if ( null !== $value && '' !== $value && [] !== $value ) {
			return $value;
		}
	}
	return $fallback;
}

/**
 * Global option from the "Контент сайту" ACF options page.
 *
 * @param string $name     ACF field name.
 * @param mixed  $fallback Fallback value.
 * @return mixed
 */
function tb_opt( string $name, $fallback = '' ) {
	$value = tb_field( $name, $fallback, 'option' );

	if ( is_string( $value ) && '' !== $value ) {
		// Explicit source language — without it WPML guesses from the current
		// request context and has repeatedly mis-tagged fresh Slovak strings as 'en'.
		do_action( 'wpml_register_single_string', 'taxi-rental-options', $name, $value, false, 'sk' );
		$value = apply_filters( 'wpml_translate_single_string', $value, 'taxi-rental-options', $name );
	}

	return $value;
}

/**
 * Telegram community URL (global option).
 */
function tb_telegram_url(): string {
	return (string) tb_opt( 'tb_telegram_url', 'https://t.me/taxibaza_sk' );
}

/**
 * Home-relative URL for one of our fixed custom pages (login, cabinet, …),
 * adjusted to the *current* WPML language.
 *
 * home_url( '/login/' ) alone always resolves to the default-language URL
 * (e.g. always /login/, never /uk/login/) even when the visitor is browsing
 * the Ukrainian version — WPML only rewrites links it generates itself
 * (get_permalink(), nav menus, …), not raw home_url() strings. Every
 * redirect/link to a fixed path (login, cabinet, add-listing, …) must go
 * through this helper instead of home_url() directly, or the user gets
 * silently bounced back to the Slovak version mid-flow (e.g. right after
 * deleting their account).
 *
 * @param string $path Path relative to the site root, e.g. '/login/'.
 */
function tb_url( string $path ): string {
	$url = home_url( $path );

	if ( has_filter( 'wpml_permalink' ) ) {
		$lang = null;

		// admin-post.php / admin-ajax.php handlers run with is_admin() === true
		// and their own URL is always /wp-admin/admin-post.php — no /xx/ segment
		// for WPML to read. Front-end requests (login/register self-submit to
		// their own /uk/login/… URL) don't have this problem, only handlers that
		// forward through admin-post.php do (e.g. account deletion). Recover the
		// visitor's actual language from the referring page's URL instead.
		if ( is_admin() ) {
			$referer = wp_get_referer();
			if ( $referer ) {
				$referer_path = (string) wp_parse_url( $referer, PHP_URL_PATH );
				$active_langs = apply_filters( 'wpml_active_languages', null, '' );
				if ( preg_match( '#^/([a-z]{2})(?:/|$)#', (string) $referer_path, $m )
					&& is_array( $active_langs ) && isset( $active_langs[ $m[1] ] )
				) {
					$lang = $m[1];
				} else {
					// No recognized language segment → referer was the default-language URL.
					$lang = (string) apply_filters( 'wpml_default_language', null );
				}
			}

			// Last resort — WPML's own language cookie.
			if ( ! $lang && ! empty( $_COOKIE['_icl_current_language'] ) ) {
				$lang = sanitize_key( wp_unslash( $_COOKIE['_icl_current_language'] ) );
			}
		}

		$url = $lang
			? (string) apply_filters( 'wpml_permalink', $url, $lang )
			: (string) apply_filters( 'wpml_permalink', $url );
	}

	return $url;
}

/**
 * Count car_listing posts the same way the lists that display them are built.
 *
 * The admin panel used to mix two counting methods: the lists run through
 * WP_Query (which WPML filters down to the current language, because
 * car_listing is translate="1" in wpml-config.xml), while the headline
 * counters used wp_count_posts() / raw $wpdb / count_user_posts() — none of
 * which are language-filtered. The result was a subtitle claiming "Celkom 24"
 * above a page listing 12. count_user_posts() was doubly wrong: it only counts
 * public statuses, so drafts, pending, rejected and archived went missing.
 *
 * Going through WP_Query here means the number and the list can't disagree —
 * whatever filters the list also filters the count.
 *
 * @param string[] $statuses Post statuses to include.
 * @param int|null $author   Restrict to one author, or null for all.
 */
function tb_listing_count( array $statuses, ?int $author = null ): int {
	static $cache = [];

	sort( $statuses );
	$key = implode( ',', $statuses ) . '|' . (int) $author;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$args = [
		'post_type'              => 'car_listing',
		'post_status'            => $statuses,
		'posts_per_page'         => 1,
		'fields'                 => 'ids',
		'ignore_sticky_posts'    => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	];
	if ( $author ) {
		$args['author'] = $author;
	}

	$q                = new WP_Query( $args );
	$cache[ $key ]    = (int) $q->found_posts;

	return $cache[ $key ];
}

/**
 * Everything the catalog needs to render for one filter set.
 *
 * The archive template, the city template and the AJAX filter endpoint all
 * build the same thing — the query plus every taxonomy list the filter bar
 * offers. Keeping it in one place is what lets the AJAX response be byte-for-
 * byte the markup a full page load would have produced.
 *
 * @param array<string,mixed> $filters From Queries::parse_filters().
 * @param int                 $paged   Page number.
 * @return array<string,mixed>
 */
function tb_catalog_context( array $filters, int $paged = 1 ): array {
	$terms = static function ( array $args ): array {
		$result = get_terms( $args );
		return is_wp_error( $result ) ? [] : (array) $result;
	};

	// Models only exist relative to the selected make.
	$model_terms = [];
	if ( ! empty( $filters['make'] ) ) {
		$make_term = get_term_by( 'slug', $filters['make'], 'car_make' );
		if ( $make_term ) {
			$model_terms = $terms(
				[
					'taxonomy'   => 'car_make',
					'parent'     => (int) $make_term->term_id,
					'hide_empty' => false,
					'orderby'    => 'name',
				]
			);
		}
	}

	global $wpdb;
	$year_values = $wpdb->get_col(
		"SELECT DISTINCT pm.meta_value
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = 'year' AND pm.meta_value <> ''
		   AND p.post_type = 'car_listing' AND p.post_status = 'publish'
		 ORDER BY pm.meta_value + 0 DESC"
	);

	return [
		'filters'        => $filters,
		'paged'          => $paged,
		'query'          => \TaxiRental\Core\Queries::build_query( $filters, $paged ),
		'city_terms'     => $terms( [ 'taxonomy' => 'city', 'hide_empty' => false ] ),
		'category_terms' => $terms( [ 'taxonomy' => 'ride_category', 'hide_empty' => false ] ),
		'platform_terms' => $terms( [ 'taxonomy' => 'platform', 'hide_empty' => false ] ),
		'make_terms'     => $terms( [ 'taxonomy' => 'car_make', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name' ] ),
		'model_terms'    => $model_terms,
		'year_values'    => array_values( array_filter( array_map( 'absint', (array) $year_values ) ) ),
	];
}

/**
 * The "N inzerátov k dispozícii" line above the catalog.
 *
 * @param int $found Number of matching listings.
 */
function tb_catalog_count_label( int $found ): string {
	return sprintf(
		tb_plural_opt(
			$found,
			'listing_available',
			__( '%d inzerát k dispozícii', 'taxi-rental' ),
			__( '%d inzeráty k dispozícii', 'taxi-rental' ),
			__( '%d inzerátov k dispozícii', 'taxi-rental' )
		),
		$found
	);
}

/**
 * Attachment id of a user's profile photo.
 *
 * The avatar was being stored under two different meta keys that never met:
 * the cabinet (Profile::update_profile) wrote `avatar`, while the admin panel
 * (Admin::ajax_fa_save_user) wrote `tb_avatar_id`. So a photo a user uploaded
 * themselves was invisible in /panel/, and one an admin set was invisible in
 * the header and in the user's own profile.
 *
 * `avatar` is now the canonical key. The legacy key is still read as a
 * fallback so photos saved through the panel before this change keep working
 * without a data migration.
 *
 * @param int $user_id User to look up. Defaults to the current user.
 */
function tb_user_avatar_id( int $user_id = 0 ): int {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) {
		return 0;
	}

	$id = (int) get_user_meta( $user_id, 'avatar', true );
	if ( ! $id ) {
		$id = (int) get_user_meta( $user_id, 'tb_avatar_id', true );
	}

	return $id;
}

/**
 * Current WPML language code (sk when WPML is off).
 */
function tb_lang(): string {
	$lang = apply_filters( 'wpml_current_language', null );
	return is_string( $lang ) && '' !== $lang ? $lang : 'sk';
}

/**
 * Month names per language, lower case as the languages require. Nominative
 * for "október 2026", genitive for "1. novembra 2026" / "1 листопада 2026".
 *
 * @return array{nom:string[],gen:string[]}
 */
function tb_month_names( string $lang ): array {
	$m = [
		'sk' => [
			'nom' => [ 'január', 'február', 'marec', 'apríl', 'máj', 'jún', 'júl', 'august', 'september', 'október', 'november', 'december' ],
			'gen' => [ 'januára', 'februára', 'marca', 'apríla', 'mája', 'júna', 'júla', 'augusta', 'septembra', 'októbra', 'novembra', 'decembra' ],
		],
		'uk' => [
			'nom' => [ 'січень', 'лютий', 'березень', 'квітень', 'травень', 'червень', 'липень', 'серпень', 'вересень', 'жовтень', 'листопад', 'грудень' ],
			'gen' => [ 'січня', 'лютого', 'березня', 'квітня', 'травня', 'червня', 'липня', 'серпня', 'вересня', 'жовтня', 'листопада', 'грудня' ],
		],
		'ru' => [
			'nom' => [ 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь' ],
			'gen' => [ 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря' ],
		],
		'en' => [
			'nom' => [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ],
			'gen' => [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ],
		],
	];
	return $m[ $lang ] ?? $m['sk'];
}

/**
 * Date in the page language, built with wp_date() (no ucfirst):
 * sk "1. novembra 2026", uk "1 листопада 2026", ru "1 ноября 2026", en "1 November 2026".
 * With $month_only: "október 2026", "листопад 2026", "October 2026".
 */
function tb_date_label( int $timestamp, bool $month_only = false, ?string $lang = null ): string {
	$lang  = $lang ?? tb_lang();
	$names = tb_month_names( $lang );
	$month = (int) wp_date( 'n', $timestamp ) - 1;
	$year  = wp_date( 'Y', $timestamp );
	if ( $month_only ) {
		return $names['nom'][ $month ] . ' ' . $year;
	}
	$day = wp_date( 'j', $timestamp );
	return ( 'sk' === $lang ? $day . '.' : $day ) . ' ' . $names['gen'][ $month ] . ' ' . $year;
}

/**
 * Link to a legal page by ID, in the current language (survives slug changes).
 *
 * @param string $which 'privacy' (page 3) or 'terms' (page 17).
 */
function tb_legal_url( string $which ): string {
	$id  = 'terms' === $which ? 17 : 3;
	$tid = (int) apply_filters( 'wpml_object_id', $id, 'page', true );
	$url = $tid ? get_permalink( $tid ) : '';
	return $url ? $url : home_url( '/' );
}

/**
 * "Updated:" prefix for the legal pages, per language.
 */
function tb_legal_updated_prefix( ?string $lang = null ): string {
	$p = [ 'sk' => 'Aktualizované:', 'uk' => 'Оновлено:', 'en' => 'Updated:', 'ru' => 'Обновлено:' ];
	return $p[ $lang ?? tb_lang() ] ?? $p['sk'];
}

// Editable "last updated" date for text pages (Privacy, Terms). ACFML copies it
// to the translations, so one date serves every language.
add_action(
	'acf/init',
	static function (): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		acf_add_local_field_group(
			[
				'key'      => 'group_tb_legal_updated',
				'title'    => 'Dátum aktualizácie',
				'fields'   => [
					[
						'key'                 => 'field_tb_legal_updated_date',
						'label'               => 'Dátum poslednej aktualizácie',
						'name'                => 'tb_updated_date',
						'type'                => 'date_picker',
						'instructions'        => 'Zobrazí sa pod nadpisom ako „Aktualizované: október 2026“ v jazyku stránky. Ak je prázdne, použije sa dátum poslednej úpravy stránky.',
						'display_format'      => 'd.m.Y',
						'return_format'       => 'Ymd',
						'wpml_cf_preferences' => 1,
					],
				],
				'location' => [
					[
						[ 'param' => 'post_type', 'operator' => '==', 'value' => 'page' ],
						[ 'param' => 'page_template', 'operator' => '==', 'value' => 'default' ],
					],
				],
				'position' => 'side',
			]
		);
	}
);

/**
 * Pick the right plural form for a Slavic count.
 *
 * _n() cannot do this job here. It delegates to the locale's plural rules,
 * which live in the .mo file — and this project ships its UI strings as source
 * literals with no translation catalogue. With no catalogue loaded, gettext
 * falls back to the ENGLISH rule (1 -> singular, everything else -> plural),
 * so "2 оголошень" and "22 оголошень" came out wrong where Ukrainian wants
 * "оголошення". Slovak needs exactly the same three-way split, so one helper
 * serves both site languages.
 *
 *   1, 21, 31 …            -> $one   (1 оголошення  / 1 inzerát)
 *   2-4, 22-24 …           -> $few   (2 оголошення  / 2 inzeráty)
 *   0, 5-20, 25-30 …       -> $many  (5 оголошень   / 5 inzerátov)
 *
 * @param int    $number Count to agree with.
 * @param string $one    Form for 1.
 * @param string $few    Form for 2-4.
 * @param string $many   Form for 0 and 5+.
 */
function tb_plural( int $number, string $one, string $few, string $many ): string {
	$number = abs( $number );
	$mod10  = $number % 10;
	$mod100 = $number % 100;

	if ( 1 === $mod10 && 11 !== $mod100 ) {
		return $one;
	}

	if ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 12 || $mod100 > 14 ) ) {
		return $few;
	}

	return $many;
}

/**
 * tb_plural() with the three forms coming from ACF options, so each language
 * can be corrected in WPML String Translation without touching templates.
 *
 * Reads the option keys "tb_pl_{$key}_one", "_few" and "_many"; the literals
 * passed in are the fallbacks and register themselves for translation on first
 * render, exactly like tb_opt() elsewhere.
 *
 * @param int    $number Count to agree with.
 * @param string $key    Option key stem, e.g. 'listing'.
 * @param string $one    Fallback form for 1.
 * @param string $few    Fallback form for 2-4.
 * @param string $many   Fallback form for 0 and 5+.
 */
function tb_plural_opt( int $number, string $key, string $one, string $few, string $many ): string {
	return tb_plural(
		$number,
		(string) tb_opt( 'tb_pl_' . $key . '_one', $one ),
		(string) tb_opt( 'tb_pl_' . $key . '_few', $few ),
		(string) tb_opt( 'tb_pl_' . $key . '_many', $many )
	);
}

/**
 * Normalize a menu URL: pass absolute URLs / special schemes through,
 * prefix relative paths with home_url().
 */
function tb_menu_url( string $url ): string {
	$url = trim( $url );
	if ( '' === $url ) {
		return '';
	}
	if ( preg_match( '#^(https?:)?//#', $url )
		|| str_starts_with( $url, 'mailto:' )
		|| str_starts_with( $url, 'tel:' )
		|| str_starts_with( $url, '#' )
	) {
		return $url;
	}
	return home_url( '/' . ltrim( $url, '/' ) );
}

/**
 * Header logo markup. Returns an <img> when a logo image is set in options,
 * otherwise the text logo with the final dot accent-colored.
 */
function tb_logo_markup(): string {
	$img = tb_opt( 'tb_logo_image', '' );
	if ( is_array( $img ) && ! empty( $img['url'] ) ) {
		return sprintf(
			'<img src="%s" alt="%s" class="tb-logo__img" style="height:30px;width:auto;display:block">',
			esc_url( $img['url'] ),
			esc_attr( $img['alt'] ?: get_bloginfo( 'name' ) )
		);
	}

	$text = (string) tb_opt( 'tb_logo_text', 'TaxiBaza.sk' );
	$dot  = strrpos( $text, '.' );
	if ( false === $dot ) {
		return esc_html( $text );
	}

	return esc_html( substr( $text, 0, $dot ) )
		. '<span class="dot">.</span>'
		. esc_html( substr( $text, $dot + 1 ) );
}

/**
 * Resolve the shared header/mobile navigation menu from ACF options.
 * Falls back to a hardcoded menu that mirrors the original static header
 * when the repeater is empty.
 *
 * @return array<int,array<string,mixed>>
 */
function tb_header_menu(): array {
	$menu = tb_opt( 'tb_header_menu', [] );
	if ( ! empty( $menu ) && is_array( $menu ) ) {
		return $menu;
	}

	return [
		[ 'label' => __( 'Каталог авто', 'taxi-rental' ), 'url' => (string) get_post_type_archive_link( 'car_listing' ), 'submenu_type' => 'none', 'submenu' => [] ],
		[ 'label' => __( 'Міста', 'taxi-rental' ),         'url' => '', 'submenu_type' => 'cities', 'submenu' => [] ],
		[ 'label' => __( 'Про сервіс', 'taxi-rental' ),    'url' => '', 'submenu_type' => 'custom', 'submenu' => [
			[ 'label' => __( 'Про нас', 'taxi-rental' ),  'url' => home_url( '/pro-nas/' ) ],
			[ 'label' => __( 'Контакти', 'taxi-rental' ), 'url' => home_url( '/kontakt/' ) ],
		] ],
		[ 'label' => __( 'Новини', 'taxi-rental' ),   'url' => home_url( '/blog/' ),    'submenu_type' => 'none', 'submenu' => [] ],
		[ 'label' => __( 'Контакти', 'taxi-rental' ), 'url' => home_url( '/kontakt/' ), 'submenu_type' => 'none', 'submenu' => [] ],
	];
}

/**
 * Resolve the account dropdown menu (logged-in user) from ACF options.
 * Falls back to the standard four cabinet links when the repeater is empty.
 *
 * @return array<int,array<string,string>>
 */
function tb_account_menu(): array {
	$menu = tb_opt( 'tb_account_menu', [] );
	if ( ! empty( $menu ) && is_array( $menu ) ) {
		return $menu;
	}

	$cab = tb_url( '/cabinet/' );
	return [
		[ 'label' => __( 'Кабінет', 'taxi-rental' ),        'url' => $cab ],
		[ 'label' => __( 'Мої оголошення', 'taxi-rental' ),  'url' => add_query_arg( 'tab', 'listings', $cab ) ],
		[ 'label' => __( 'Мій профіль', 'taxi-rental' ),     'url' => add_query_arg( 'tab', 'profile',  $cab ) ],
		[ 'label' => __( 'Налаштування', 'taxi-rental' ),    'url' => add_query_arg( 'tab', 'settings', $cab ) ],
	];
}

/**
 * Multiline text → escaped HTML with <br> for line breaks.
 */
function tb_nl2br( string $text ): string {
	return nl2br( esc_html( $text ) );
}

/**
 * Estimated reading time label for a post, e.g. "5 хв".
 */
function taxi_rental_reading_time( int $post_id ): string {
	$words   = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
	$minutes = max( 1, (int) ceil( $words / 200 ) );

	/* translators: %d: reading time in minutes */
	return sprintf( __( '%d min', 'taxi-rental' ), $minutes );
}



/*
 * ---------------------------------------------------------------------------
 * Cities in every language version (home page grid, /mista/, city page).
 *
 * The Slovak city list is the master: every language shows the same cities in
 * the same order, with the translated name when WPML has one (otherwise the
 * Slovak one), the photo of the Slovak city when the translation has none, and
 * the number of cars counted the way the catalogue shows them (one car per
 * WPML translation group, whatever language its city term is in).
 * ---------------------------------------------------------------------------
 */

/** Default (source) language of the site. */
function tb_default_lang(): string {
	$l = apply_filters( 'wpml_default_language', null );
	return is_string( $l ) && '' !== $l ? $l : 'sk';
}

/**
 * Same term in another language (or the term itself).
 */
function tb_city_in_lang( WP_Term $term, string $lang ): WP_Term {
	$id = (int) apply_filters( 'wpml_object_id', $term->term_id, 'city', false, $lang );
	if ( $id && $id !== (int) $term->term_id ) {
		$t = get_term( $id, 'city' );
		if ( $t instanceof WP_Term ) {
			return $t;
		}
	}
	return $term;
}

/**
 * City photo: the term's own, else the Slovak original's, else ''.
 */
function tb_city_image_url( WP_Term $term, string $size = 'medium_large' ): string {
	foreach ( [ $term, tb_city_in_lang( $term, tb_default_lang() ) ] as $t ) {
		$img_id = (int) get_term_meta( $t->term_id, 'tb_image_id', true );
		$url    = $img_id ? (string) wp_get_attachment_image_url( $img_id, $size ) : '';
		if ( $url ) {
			return $url;
		}
	}
	$sk   = tb_city_in_lang( $term, tb_default_lang() );
	$file = '/assets/img/cities/' . sanitize_file_name( $sk->slug ) . '.jpg';
	return file_exists( TAXI_THEME_DIR . $file ) ? TAXI_THEME_URL . $file : '';
}

/**
 * Cars per city, keyed by the Slovak city term ID.
 *
 * @return array<int,int>
 */
function tb_city_car_counts(): array {
	static $counts = null;
	if ( null !== $counts ) {
		return $counts;
	}
	$cached = get_transient( 'tb_city_car_counts' );
	if ( is_array( $cached ) ) {
		$counts = $cached;
		return $counts;
	}
	$ids = get_posts( [
		'post_type'        => 'car_listing',
		'post_status'      => 'publish',
		'posts_per_page'   => -1,
		'fields'           => 'ids',
		'lang'             => 'all',
		'suppress_filters' => false,
		'no_found_rows'    => true,
	] );
	$groups = [];
	$def    = tb_default_lang();
	foreach ( $ids as $id ) {
		$trid  = (int) apply_filters( 'wpml_element_trid', null, $id, 'post_car_listing' );
		$group = $trid ? 't' . $trid : 'p' . $id;
		$terms = wp_get_object_terms( $id, 'city', [ 'fields' => 'ids' ] );
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $tid ) {
			$sk_id                      = (int) apply_filters( 'wpml_object_id', (int) $tid, 'city', true, $def );
			$groups[ $sk_id ][ $group ] = true;
		}
	}
	$counts = array_map( 'count', $groups );
	set_transient( 'tb_city_car_counts', $counts, 10 * MINUTE_IN_SECONDS );
	return $counts;
}

add_action( 'save_post_car_listing', static fn() => delete_transient( 'tb_city_car_counts' ) );
add_action( 'set_object_terms', static fn() => delete_transient( 'tb_city_car_counts' ) );
add_action( 'transition_post_status', static fn() => delete_transient( 'tb_city_car_counts' ) );

/**
 * Cards for the city grids.
 *
 * @param string $orderby 'count' (most cars first) or 'name'.
 * @param int    $limit   0 = all.
 * @return array<int,array{term:WP_Term,name:string,link:string,count:int,img:string}>
 */
function tb_city_cards( string $orderby = 'count', int $limit = 0 ): array {
	$cur = (string) apply_filters( 'wpml_current_language', null );
	$def = tb_default_lang();
	if ( $cur && $cur !== $def ) {
		do_action( 'wpml_switch_language', $def );
	}
	$sk_terms = get_terms( [ 'taxonomy' => 'city', 'hide_empty' => false ] );
	if ( $cur && $cur !== $def ) {
		do_action( 'wpml_switch_language', $cur );
	}
	if ( is_wp_error( $sk_terms ) || ! $sk_terms ) {
		return [];
	}
	$counts = tb_city_car_counts();
	$cards  = [];
	foreach ( $sk_terms as $sk ) {
		$t    = $cur ? tb_city_in_lang( $sk, $cur ) : $sk;
		$link = get_term_link( $t );
		$cards[] = [
			'term'  => $t,
			'name'  => $t->name,
			'link'  => is_wp_error( $link ) ? '' : (string) $link,
			'count' => (int) ( $counts[ (int) $sk->term_id ] ?? 0 ),
			'img'   => tb_city_image_url( $t ),
			'sk_id' => (int) $sk->term_id,
		];
	}
	usort( $cards, static function ( $a, $b ) use ( $orderby ) {
		if ( 'count' === $orderby ) {
			return [ $b['count'], $a['sk_id'] ] <=> [ $a['count'], $b['sk_id'] ];
		}
		return strcoll( $a['name'], $b['name'] );
	} );
	return $limit > 0 ? array_slice( $cards, 0, $limit ) : $cards;
}
