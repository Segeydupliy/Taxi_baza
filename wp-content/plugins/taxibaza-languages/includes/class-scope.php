<?php
/**
 * Which copies of a listing each screen shows.
 *
 * - Cabinet (/cabinet/) and moderation panel (/panel/, its AJAX `tb_fa_*`):
 *   every listing once — its original — whatever the interface language.
 *   Counters, lists and the "N listings" of a user/fleet agree in all languages.
 *   The panel shows the language of the original next to the ID.
 * - Panel: cities / categories / platforms are listed in the main language
 *   (SK), so the lists and counters are the same in every language.
 * - Catalogue (core query with lang=all + post__in): each listing maps to the
 *   published copy in the visitor's language, else the published original.
 *   A deleted, trashed or unpublished translation no longer hides a listing.
 *
 * @package TaxiBaza\Languages
 */

namespace TaxiBaza\Languages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Scope {

	public const TAXONOMIES = [ 'city', 'platform', 'ride_category' ];

	/** @var string[] Languages to switch back to after a term query. */
	private static array $term_stack = [];

	/** For tests: forced context ('manage' | 'panel' | ''). */
	public static ?string $force = null;

	public static function init(): void {
		add_action( 'pre_get_posts', [ self::class, 'queries' ], 0 ); // Before the theme's lang=all handler (1).
		add_filter( 'query', [ self::class, 'cabinet_counts' ] );
		add_filter( 'terms_clauses', [ self::class, 'terms_main_language' ], PHP_INT_MIN, 3 );
		add_filter( 'terms_clauses', [ self::class, 'terms_restore_language' ], PHP_INT_MAX, 3 );
		add_action( 'wp_footer', [ self::class, 'panel_badges' ], 50 );
		add_action( 'save_post_car_listing', [ Groups::class, 'flush' ] );
		add_action( 'deleted_post', [ Groups::class, 'flush' ] );
	}

	private static function current_lang(): string {
		return (string) apply_filters( 'wpml_current_language', '' );
	}

	/** Panel page or its AJAX. */
	public static function is_panel(): bool {
		if ( null !== self::$force ) {
			return 'panel' === self::$force;
		}
		if ( wp_doing_ajax() ) {
			$action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return str_starts_with( $action, 'tb_fa_' );
		}
		return ! is_admin() && did_action( 'wp' ) && is_page_template( 'page-front-admin.php' );
	}

	/** Cabinet or panel: screens where people manage listings. */
	public static function is_manage(): bool {
		if ( null !== self::$force ) {
			return in_array( self::$force, [ 'manage', 'panel' ], true );
		}
		if ( self::is_panel() ) {
			return true;
		}
		return ! is_admin() && ! wp_doing_ajax() && did_action( 'wp' ) && is_page_template( 'page-dashboard.php' );
	}

	/** @param \WP_Query $q Query. */
	private static function is_listing_query( $q ): bool {
		$type = $q->get( 'post_type' );
		return 'car_listing' === $type || ( is_array( $type ) && [ 'car_listing' ] === array_values( $type ) );
	}

	/** @param \WP_Query $q Query. */
	public static function queries( $q ): void {
		if ( ! Groups::wpml() || $q->is_main_query() || ! self::is_listing_query( $q ) ) {
			return;
		}
		if ( is_admin() && ! wp_doing_ajax() ) {
			return; // wp-admin lists stay as WPML shows them.
		}
		if ( self::is_manage() ) {
			$ids = Groups::original_ids();
			$in  = array_values( array_filter( array_map( 'intval', (array) $q->get( 'post__in' ) ) ) );
			if ( $in ) {
				$ids = array_values( array_intersect( $ids, $in ) );
			}
			$q->set( 'post__in', $ids ?: [ 0 ] );
			$q->set( 'lang', 'all' );
			$q->set( 'suppress_filters', true ); // WPML's SQL filters would cut it back to one language.
			$q->set( 'tbl_scope', 'manage' );
			return;
		}
		$raw = array_map( 'intval', array_filter( (array) $q->get( 'post__in' ), static fn( $v ) => '' !== $v && null !== $v ) );
		if ( 'all' === $q->get( 'lang' ) && $raw ) {
			$ids = Groups::for_visitors( $raw, self::current_lang() );
			$q->set( 'post__in', $ids ?: [ 0 ] );
			$q->set( 'tbl_scope', 'catalog' );
		}
	}

	/**
	 * The cabinet's status counters are one SQL query limited to the interface
	 * language (theme page-dashboard.php). Count the originals instead.
	 *
	 * @param string $sql Query.
	 */
	public static function cabinet_counts( $sql ) {
		if ( ! is_string( $sql ) || ! str_contains( $sql, 'icl_translations t' ) || ! str_contains( $sql, 'p.post_author' ) || ! str_contains( $sql, 'GROUP BY p.post_status' ) ) {
			return $sql;
		}
		if ( ! self::is_manage() ) {
			return $sql;
		}
		return (string) preg_replace( "/AND\s+t\.language_code\s*=\s*'[a-z_-]+'/i", 'AND t.source_language_code IS NULL', $sql, 1 );
	}

	/**
	 * Panel: cities / categories / platforms in the main language, so every
	 * interface language shows the same list.
	 *
	 * @param array<string,string> $clauses    SQL pieces.
	 * @param string[]             $taxonomies Taxonomies.
	 * @param array<string,mixed>  $args       Query args.
	 * @return array<string,string>
	 */
	public static function terms_main_language( $clauses, $taxonomies = [], $args = [] ) {
		if ( ! Groups::wpml() || ! array_intersect( (array) $taxonomies, self::TAXONOMIES ) || ! self::is_panel() ) {
			self::$term_stack[] = '';
			return $clauses;
		}
		$current = self::current_lang();
		$main    = (string) apply_filters( 'wpml_default_language', '' );
		if ( '' === $main || $main === $current ) {
			self::$term_stack[] = '';
			return $clauses;
		}
		self::$term_stack[] = $current;
		do_action( 'wpml_switch_language', $main );
		return $clauses;
	}

	/**
	 * @param array<string,string> $clauses SQL pieces.
	 * @return array<string,string>
	 */
	public static function terms_restore_language( $clauses ) {
		$back = array_pop( self::$term_stack );
		if ( is_string( $back ) && '' !== $back ) {
			do_action( 'wpml_switch_language', $back );
		}
		return $clauses;
	}

	/** Panel: "UK" / "SK" … next to each listing's ID = language of the original. */
	public static function panel_badges(): void {
		if ( ! Groups::wpml() || ! self::is_panel() || ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$map = [];
		foreach ( Groups::by_trid() as $group ) {
			$o = Groups::original_of( $group );
			if ( ! $o || '' === $o['lang'] ) {
				continue;
			}
			foreach ( $group as $r ) {
				$map[ $r['id'] ] = strtoupper( (string) $o['lang'] );
			}
		}
		?>
<script id="tbl-panel-lang">
(function () {
	var map = <?php echo wp_json_encode( (object) $map ); ?>;
	function mark(root) {
		[].forEach.call((root || document).querySelectorAll('[data-listing-id]'), function (card) {
			var lang = map[card.getAttribute('data-listing-id')], id = card.querySelector('.tb-fa-mod-card__id');
			if (!lang || !id || card.querySelector('.tbl-lang')) { return; }
			var b = document.createElement('span'); b.className = 'tbl-lang'; b.title = lang; b.textContent = lang;
			id.insertAdjacentElement('afterend', b);
		});
	}
	mark();
	new MutationObserver(function () { mark(); }).observe(document.body, { childList: true, subtree: true });
}());
</script>
<style>.tbl-lang{display:inline-block;margin-left:8px;padding:1px 7px;border:1px solid #d9d9d9;border-radius:100px;font-size:11px;font-weight:700;line-height:16px;color:#555;vertical-align:middle}</style>
		<?php
	}
}
