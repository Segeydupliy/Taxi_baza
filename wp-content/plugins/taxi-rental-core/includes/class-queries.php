<?php
/**
 * class-queries.php
 * Catalog WP_Query builder + filter sanitizer.
 */

namespace TaxiRental\Core;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

class Queries {

	public static function init(): void {
		add_action( 'pre_get_posts', [ static::class, 'filter_blog_query' ] );
		add_action( 'template_redirect', [ static::class, 'count_view' ] );
		add_action( 'wp_ajax_tb_filter_listings', [ static::class, 'ajax_filter_listings' ] );
		add_action( 'wp_ajax_nopriv_tb_filter_listings', [ static::class, 'ajax_filter_listings' ] );
	}

	/**
	 * Catalog filtering without a page reload.
	 *
	 * Returns the same markup a full request would render — the filter bar, the
	 * results and the count line — by going through the very same context
	 * helper and template parts the archive uses. The filter bar has to come
	 * back too, not just the results: the Model list only exists relative to
	 * the selected Make, and the active pill labels change with every choice.
	 *
	 * The filters are read out of $_GET because the browser sends the exact
	 * query string it is about to push into the address bar, so parse_filters()
	 * needs no second implementation.
	 */
	public static function ajax_filter_listings(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'tb_filter_listings' ) ) {
			wp_send_json_error( [ 'message' => 'bad_nonce' ], 403 );
		}

		// #102 — admin-ajax.php requests carry no /uk/ in their URL, so WPML
		// has nothing to detect the language from and every tb_opt()/
		// tb_plural_opt() call below (count line, filter labels) silently
		// falls back to the site default (sk) — the count line looked
		// "stuck in Slovak" on the uk catalog specifically because filtering
		// is the only thing that re-renders it through this AJAX path. The
		// JS sends the page's own <html lang>; only switch to it if WPML
		// actually lists it as a real active language, never on trust alone.
		$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
		if ( $lang ) {
			$active_langs = (array) apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] );
			if ( isset( $active_langs[ $lang ] ) ) {
				do_action( 'wpml_switch_language', $lang );
			}
		}

		$query = isset( $_POST['query'] ) ? (string) wp_unslash( $_POST['query'] ) : '';
		$parsed = [];
		wp_parse_str( ltrim( $query, '?' ), $parsed );

		// parse_filters() reads $_GET; hand it the query string the page is
		// about to adopt so both paths sanitise identically.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked above.
		$_GET = is_array( $parsed ) ? $parsed : [];

		$filters = static::parse_filters();

		// A city archive pins its own city no matter what the query says.
		$city = isset( $_POST['city'] ) ? sanitize_key( wp_unslash( $_POST['city'] ) ) : '';
		if ( '' !== $city ) {
			$filters['city'] = $city;
		}

		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$base  = isset( $_POST['base'] ) ? esc_url_raw( wp_unslash( $_POST['base'] ) ) : '';
		if ( '' === $base ) {
			$base = (string) get_post_type_archive_link( 'car_listing' );
		}

		if ( ! function_exists( 'tb_catalog_context' ) ) {
			wp_send_json_error( [ 'message' => 'theme_missing' ], 500 );
		}

		$ctx = tb_catalog_context( $filters, $paged );

		$link_args = $filters;
		if ( '' !== $city ) {
			unset( $link_args['city'] );
		}

		ob_start();
		get_template_part( 'template-parts/catalog/filters-bar', null, $ctx );
		$filters_html = (string) ob_get_clean();

		ob_start();
		get_template_part(
			'template-parts/catalog/results',
			null,
			[
				'query'           => $ctx['query'],
				'paged'           => $paged,
				'base_url'        => $base,
				'query_args'      => static::to_query_args( $link_args ),
				'load_more_class' => ( '' !== $city ) ? 'tb-btn--dark' : 'tb-btn--outline',
			]
		);
		$results_html = (string) ob_get_clean();

		wp_send_json_success(
			[
				'filters' => $filters_html,
				'results' => $results_html,
				'count'   => tb_catalog_count_label( (int) $ctx['query']->found_posts ),
				'append'  => $paged > 1,
			]
		);
	}

	/**
	 * Increment the view counter for a single listing or blog post.
	 *
	 * #108 asked whether the view counters work at all. They did not: three
	 * templates and the "popularity" sort read `_tb_listing_views` /
	 * `tb_views`, but nothing ever wrote them, so every counter sat at zero.
	 *
	 * It lives in Queries because Queries is what consumes the meta for the
	 * popularity ordering. Deliberately simple: one count per visitor per post
	 * per day via a cookie, the author's own visits excluded. It is a vanity
	 * counter, not analytics — full page caching in front of PHP would bypass
	 * it, and crawlers are not filtered out.
	 */
	public static function count_view(): void {
		if ( is_feed() || is_preview() || is_embed() ) {
			return;
		}

		if ( ! is_singular( [ 'car_listing', 'post' ] ) ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		// An owner refreshing their own advert should not inflate its count.
		if ( is_user_logged_in()
			&& (int) get_post_field( 'post_author', $post_id ) === get_current_user_id()
		) {
			return;
		}

		$cookie = 'tb_v_' . $post_id;
		if ( isset( $_COOKIE[ $cookie ] ) ) {
			return;
		}

		if ( ! headers_sent() ) {
			setcookie(
				$cookie,
				'1',
				time() + DAY_IN_SECONDS,
				COOKIEPATH ? COOKIEPATH : '/',
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);
		}

		$key     = ( 'post' === get_post_type( $post_id ) ) ? 'tb_views' : '_tb_listing_views';
		$current = (int) get_post_meta( $post_id, $key, true );

		update_post_meta( $post_id, $key, $current + 1 );
	}

	/**
	 * Apply the blog archive's search box and sort control to the main query.
	 *
	 * The search term arrives as `tb_bq`, not `s`, on purpose: `s` is a
	 * WordPress-reserved query var, so `/blog/?s=…` stops being the blog
	 * archive and becomes a site-wide search (a different template, all post
	 * types, and a canonical redirect off the page). Mapping our own param onto
	 * the main query here keeps the archive, its category context and its
	 * pagination intact.
	 *
	 * @param \WP_Query $query Query being prepared.
	 */
	public static function filter_blog_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_home() && ! $query->is_category() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only public archive filters.
		$term = isset( $_GET['tb_bq'] ) ? sanitize_text_field( wp_unslash( $_GET['tb_bq'] ) ) : '';
		$sort = isset( $_GET['tb_bsort'] ) ? sanitize_key( wp_unslash( $_GET['tb_bsort'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '' !== $term ) {
			$query->set( 's', $term );
		}

		switch ( $sort ) {
			case 'date_asc':
				$query->set( 'orderby', 'date' );
				$query->set( 'order', 'ASC' );
				break;

			case 'views_desc':
				// Named clause with an EXISTS/NOT EXISTS pair: a plain meta_key
				// would INNER JOIN and hide every post that has no view counter
				// yet, which is most of them.
				$query->set(
					'meta_query',
					[
						'relation'        => 'OR',
						'tb_post_views'   => [
							'key'     => 'tb_views',
							'compare' => 'EXISTS',
						],
						[
							'key'     => 'tb_views',
							'compare' => 'NOT EXISTS',
						],
					]
				);
				$query->set( 'orderby', [ 'tb_post_views' => 'DESC' ] );
				break;
		}
	}

	/**
	 * Sanitize filter values from $_GET.
	 *
	 * @return array<string, mixed>
	 */
	public static function parse_filters(): array {
		$filters = [];

		// Taxonomy slugs (single value each). make/model are terms of car_make.
		foreach ( [ 'city', 'cat', 'platform', 'make', 'model' ] as $key ) {
			if ( ! empty( $_GET[ $key ] ) ) {
				$filters[ $key ] = sanitize_key( wp_unslash( $_GET[ $key ] ) );
			}
		}

		// Year (exact match against the `year` meta).
		//
		// The public parameter is tb_year, NOT year: `year` is a reserved
		// WordPress query var, so /avto/?year=2021 stopped being the listings
		// archive and became a date archive for 2021 — which matches nothing
		// and returned a 404. Same trap as `s` on the cabinet search.
		if ( ! empty( $_GET['tb_year'] ) ) {
			$filters['year'] = absint( wp_unslash( $_GET['tb_year'] ) );
		}

		// Fuel type (engine_type field).
		$allowed_fuel = [ 'petrol', 'diesel', 'electric', 'hybrid', 'lpg' ];
		if ( ! empty( $_GET['fuel'] ) ) {
			$v = sanitize_key( wp_unslash( $_GET['fuel'] ) );
			if ( \in_array( $v, $allowed_fuel, true ) ) {
				$filters['fuel'] = $v;
			}
		}

		// Transmission.
		$allowed_trans = [ 'automatic', 'manual' ];
		if ( ! empty( $_GET['transmission'] ) ) {
			$v = sanitize_key( wp_unslash( $_GET['transmission'] ) );
			if ( \in_array( $v, $allowed_trans, true ) ) {
				$filters['transmission'] = $v;
			}
		}

		// Price range (raw string → validated int on build).
		if ( isset( $_GET['price_min'] ) && '' !== $_GET['price_min'] ) {
			$filters['price_min'] = \max( 0, (int) wp_unslash( $_GET['price_min'] ) );
		}
		if ( isset( $_GET['price_max'] ) && '' !== $_GET['price_max'] ) {
			$filters['price_max'] = \max( 0, (int) wp_unslash( $_GET['price_max'] ) );
		}

		// Deposit range.
		if ( isset( $_GET['deposit_min'] ) && '' !== $_GET['deposit_min'] ) {
			$filters['deposit_min'] = \max( 0, (int) wp_unslash( $_GET['deposit_min'] ) );
		}
		if ( isset( $_GET['deposit_max'] ) && '' !== $_GET['deposit_max'] ) {
			$filters['deposit_max'] = \max( 0, (int) wp_unslash( $_GET['deposit_max'] ) );
		}

		// Sort order.
		$allowed_order = [ 'date_desc', 'date_asc', 'price_asc', 'price_desc', 'popularity', 'updated_desc' ];
		if ( ! empty( $_GET['orderby'] ) ) {
			$v = sanitize_key( wp_unslash( $_GET['orderby'] ) );
			if ( \in_array( $v, $allowed_order, true ) ) {
				$filters['orderby'] = $v;
			}
		}

		return $filters;
	}

	/**
	 * Turn an internal filter array into the query args a catalog URL carries.
	 *
	 * Only the year differs: internally it is `year`, on the wire `tb_year`.
	 * Empty values and the default sort are dropped so URLs stay short.
	 *
	 * @param array<string,mixed> $filters From parse_filters().
	 * @return array<string,string>
	 */
	public static function to_query_args( array $filters ): array {
		$args = [];

		foreach ( $filters as $key => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			if ( 'orderby' === $key && 'date_desc' === $value ) {
				continue;
			}
			$args[ 'year' === $key ? 'tb_year' : $key ] = (string) $value;
		}

		return $args;
	}

	/**
	 * Build WP_Query for the public catalog.
	 *
	 * @param array<string,mixed> $filters  From parse_filters().
	 * @param int                 $paged    Page number.
	 */
	public static function build_query( array $filters, int $paged = 1 ): \WP_Query {
		$args = [
			'post_type'      => 'car_listing',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		// ── Tax queries ────────────────────────────────────────────────────
		$tax_query = [ 'relation' => 'AND' ];
		$has_tax   = false;

		if ( ! empty( $filters['city'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'city',
				'field'    => 'slug',
				'terms'    => $filters['city'],
			];
			$has_tax = true;
		}

		if ( ! empty( $filters['cat'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'ride_category',
				'field'    => 'slug',
				'terms'    => $filters['cat'],
			];
			$has_tax = true;
		}

		if ( ! empty( $filters['platform'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'platform',
				'field'    => 'slug',
				'terms'    => $filters['platform'],
			];
			$has_tax = true;
		}

		// Make + model are both terms of the hierarchical car_make taxonomy.
		if ( ! empty( $filters['make'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'car_make',
				'field'    => 'slug',
				'terms'    => $filters['make'],
			];
			$has_tax = true;
		}

		if ( ! empty( $filters['model'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'car_make',
				'field'    => 'slug',
				'terms'    => $filters['model'],
			];
			$has_tax = true;
		}

		if ( $has_tax ) {
			$args['tax_query'] = $tax_query;
		}

		// ── Meta queries ───────────────────────────────────────────────────
		$meta_query = [ 'relation' => 'AND' ];
		$has_meta   = false;

		if ( ! empty( $filters['fuel'] ) ) {
			$meta_query[] = [
				'key'     => 'engine_type',
				'value'   => $filters['fuel'],
				'compare' => '=',
			];
			$has_meta = true;
		}

		if ( ! empty( $filters['transmission'] ) ) {
			$meta_query[] = [
				'key'     => 'transmission',
				'value'   => $filters['transmission'],
				'compare' => '=',
			];
			$has_meta = true;
		}

		if ( isset( $filters['price_min'] ) ) {
			$meta_query[] = [
				'key'     => 'price_week',
				'value'   => $filters['price_min'],
				'type'    => 'NUMERIC',
				'compare' => '>=',
			];
			$has_meta = true;
		}

		if ( isset( $filters['price_max'] ) ) {
			$meta_query[] = [
				'key'     => 'price_week',
				'value'   => $filters['price_max'],
				'type'    => 'NUMERIC',
				'compare' => '<=',
			];
			$has_meta = true;
		}

		if ( ! empty( $filters['year'] ) ) {
			$meta_query[] = [
				'key'     => 'year',
				'value'   => $filters['year'],
				'type'    => 'NUMERIC',
				'compare' => '=',
			];
			$has_meta = true;
		}

		if ( isset( $filters['deposit_min'] ) ) {
			$meta_query[] = [
				'key'     => 'deposit',
				'value'   => $filters['deposit_min'],
				'type'    => 'NUMERIC',
				'compare' => '>=',
			];
			$has_meta = true;
		}

		if ( isset( $filters['deposit_max'] ) ) {
			$meta_query[] = [
				'key'     => 'deposit',
				'value'   => $filters['deposit_max'],
				'type'    => 'NUMERIC',
				'compare' => '<=',
			];
			$has_meta = true;
		}

		// ── Sort ───────────────────────────────────────────────────────────
		$orderby = $filters['orderby'] ?? 'date_desc';
		switch ( $orderby ) {
			case 'date_asc':
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
				break;
			case 'price_asc':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'price_week';
				$args['order']    = 'ASC';
				break;
			case 'price_desc':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'price_week';
				$args['order']    = 'DESC';
				break;
			case 'popularity':
				// Top-level meta_key + meta_value_num does an INNER JOIN and would
				// silently drop every listing that has no views meta row at all
				// (the counter isn't incremented anywhere yet, so that's most of
				// them). Use a named meta_query clause instead: an OR of
				// EXISTS/NOT EXISTS keeps listings without the meta in the
				// results — sorted after the ones that do have a count.
				$meta_query[] = [
					'relation'         => 'OR',
					'tb_views_clause'  => [
						'key'     => '_tb_listing_views',
						'compare' => 'EXISTS',
					],
					[
						'key'     => '_tb_listing_views',
						'compare' => 'NOT EXISTS',
					],
				];
				$has_meta        = true;
				$args['orderby'] = [ 'tb_views_clause' => 'DESC' ];
				break;
			case 'updated_desc':
				$args['orderby'] = 'modified';
				$args['order']   = 'DESC';
				break;
		}

		if ( $has_meta ) {
			$args['meta_query'] = $meta_query;
		}

		// Owners aren't required to translate a listing before it's visible —
		// WPML's own pre_get_posts filter would otherwise hide a sk-only
		// listing from every uk visitor entirely (confirmed: a listing
		// submitted in one language never gets an automatic translation
		// duplicate, so without this it just never appears on the other
		// language's catalog at all). 'lang' => 'all' turns that filter off;
		// post__in then does the real, duplicate-safe restriction ourselves:
		// exactly one row per trid, preferring the current-language copy
		// when one exists so an already-translated demo listing still shows
		// once, not once per language.
		$visible_ids = self::listing_ids_for_current_language();
		if ( $visible_ids ) {
			$args['lang']     = 'all';
			$args['post__in'] = $visible_ids;
		}

		return new \WP_Query( $args );
	}

	/**
	 * One car_listing ID per WPML translation group (trid): the current
	 * language's copy if it has one, otherwise whichever language it does
	 * have. Lets untranslated listings stay visible cross-language without
	 * ever showing the same listing twice.
	 *
	 * Category/city/platform filters still only match a listing's own
	 * taxonomy terms, which are in whatever language it was submitted in —
	 * so a filtered search can still miss a cross-language fallback listing.
	 * Fixing that needs matching terms across the whole trid, not just this.
	 *
	 * @return int[]
	 */
	private static function listing_ids_for_current_language(): array {
		global $wpdb;
		$current_lang = (string) apply_filters( 'wpml_current_language', null );

		$rows = $wpdb->get_results(
			"SELECT element_id, trid, language_code
			 FROM {$wpdb->prefix}icl_translations
			 WHERE element_type = 'post_car_listing'",
			ARRAY_A
		);

		$by_trid = [];
		foreach ( $rows as $row ) {
			$trid       = (int) $row['trid'];
			$is_current = $current_lang === $row['language_code'];
			if ( ! isset( $by_trid[ $trid ] ) || ( $is_current && ! $by_trid[ $trid ]['is_current'] ) ) {
				$by_trid[ $trid ] = [
					'id'         => (int) $row['element_id'],
					'is_current' => $is_current,
				];
			}
		}

		return array_map(
			static fn( array $row ): int => $row['id'],
			array_values( $by_trid )
		);
	}
}
