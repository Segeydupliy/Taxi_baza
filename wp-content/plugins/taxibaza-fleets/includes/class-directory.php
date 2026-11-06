<?php
/**
 * Public fleet directory: which fleets are public, their URL slugs, the data a
 * fleet card / fleet page shows, filtering, sorting and pagination.
 *
 * A fleet is public when the account has the role `fleet`, an approved live
 * profile and a visible status (Status::VISIBLE — not draft/pending/rejected/blocked).
 *
 * Slug: user meta `tbf_slug`, made from the fleet name the first time the fleet
 * is approved (or first shown) and kept afterwards, so links stay stable when the
 * name changes. Fallback in URLs: the user ID.
 *
 * @package TaxiBaza\Fleets
 */

namespace TaxiBaza\Fleets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Directory {

	public const META_SLUG = 'tbf_slug';
	public const BASE      = 'flotily';
	public const PER_PAGE  = 12;
	public const PLATFORMS = [ 'bolt' => 'Bolt', 'uber' => 'Uber', 'hopin' => 'Hopin' ];
	public const SORTS     = [ 'new', 'name' ];

	/** Words that never become part of a slug or of the initials. */
	private const LEGAL = [ 's.r.o.', 's. r. o.', 'sro', 'spol.', 'a.s.', 'a. s.', 'k.s.', 'v.o.s.', 'ltd', 'ltd.', 'llc', 'gmbh', 'inc', 'inc.', 'тов', 'фоп', 'ооо', 'ип' ];

	/** Slugs that clash with WordPress URL parts. */
	private const RESERVED = [ 'page', 'feed', 'amp', 'embed', 'trackback' ];

	/** @var array<int,array<string,mixed>> */
	private static array $cards = [];
	/** @var array<int,int[]> */
	private static array $listings = [];
	/** @var int[]|null */
	private static ?array $visible = null;

	public static function init(): void {
		add_action( 'tbf_status_changed', [ self::class, 'on_status' ], 5, 2 );
	}

	/**
	 * @param int    $fleet Fleet user.
	 * @param string $to    New status.
	 */
	public static function on_status( $fleet, $to ): void {
		self::$visible = null;
		if ( Status::APPROVED === $to ) {
			self::ensure_slug( (int) $fleet );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Visibility
	 * ------------------------------------------------------------------ */

	public static function is_visible( int $uid ): bool {
		return $uid > 0
			&& Status::is_fleet( $uid )
			&& in_array( Status::get( $uid ), Status::VISIBLE, true )
			&& [] !== Profile::get_live( $uid );
	}

	/** @return int[] IDs of all public fleets. */
	public static function visible_ids(): array {
		if ( null === self::$visible ) {
			$ids           = array_map( 'intval', (array) get_users( [ 'role' => 'fleet', 'fields' => 'ID' ] ) );
			self::$visible = array_values( array_filter( $ids, [ self::class, 'is_visible' ] ) );
		}
		return self::$visible;
	}

	public static function flush_cache(): void {
		self::$visible  = null;
		self::$cards    = [];
		self::$listings = [];
	}

	/* ------------------------------------------------------------------ *
	 * Slugs
	 * ------------------------------------------------------------------ */

	/** Latin, lowercase, dash-separated slug from a fleet name (Cyrillic transliterated, legal form dropped). */
	public static function slugify( string $name ): string {
		$name = mb_strtolower( trim( $name ) );
		$name = str_replace( [ '’', "'", 'ʼ', '`' ], '', $name );
		foreach ( self::LEGAL as $form ) {
			$name = preg_replace( '/(^|[\s,])' . preg_quote( $form, '/' ) . '(?=$|[\s,])/u', ' ', $name ) ?? $name;
		}
		$map  = [
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g', 'д' => 'd', 'е' => 'e', 'є' => 'ie', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
			'и' => 'y', 'і' => 'i', 'ї' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
			'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '',
			'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'iu', 'я' => 'ia',
		];
		$name = strtr( $name, $map );
		if ( function_exists( 'remove_accents' ) ) {
			$name = remove_accents( $name );
		} else {
			$conv = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $name ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- best effort outside WP.
			$name = false !== $conv ? $conv : $name;
		}
		$slug = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) ), '-' );
		$slug = substr( $slug, 0, 60 );
		return rtrim( $slug, '-' );
	}

	/**
	 * First free slug: base, base-2, base-3 …
	 *
	 * @param callable $owner fn( string $slug ): int — user that already has the slug (0 = free).
	 */
	public static function unique_slug( string $base, int $uid, callable $owner ): string {
		if ( '' === $base || ctype_digit( $base ) || in_array( $base, self::RESERVED, true ) ) {
			$base = 'fleet-' . ( '' === $base ? $uid : $base );
		}
		$slug = $base;
		for ( $i = 2; $i < 1000; $i++ ) {
			$taken = (int) $owner( $slug );
			if ( 0 === $taken || $uid === $taken ) {
				return $slug;
			}
			$slug = $base . '-' . $i;
		}
		return $base . '-' . $uid;
	}

	public static function user_by_slug( string $slug ): int {
		if ( '' === $slug ) {
			return 0;
		}
		$ids = get_users(
			[
				'meta_key'   => self::META_SLUG, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
				'meta_value' => $slug, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
				'fields'     => 'ID',
				'number'     => 1,
			]
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/** Make (once) and return the fleet's slug. */
	public static function ensure_slug( int $uid ): string {
		$slug = (string) get_user_meta( $uid, self::META_SLUG, true );
		if ( '' !== $slug ) {
			return $slug;
		}
		$slug = self::unique_slug( self::slugify( Listing_Fleet::fleet_name( $uid ) ), $uid, [ self::class, 'user_by_slug' ] );
		update_user_meta( $uid, self::META_SLUG, $slug );
		return $slug;
	}

	/** Public fleet for a URL slug (or user ID), 0 when there is none. */
	public static function find( string $slug ): int {
		$slug = strtolower( $slug );
		$uid  = self::user_by_slug( $slug );
		if ( ! $uid && ctype_digit( $slug ) ) {
			$uid = (int) $slug;
		}
		return self::is_visible( $uid ) ? $uid : 0;
	}

	/* ------------------------------------------------------------------ *
	 * URLs (language-aware: /flotily/, /uk/flotily/, /en/flotily/, /ru/flotily/)
	 * ------------------------------------------------------------------ */

	public static function base_url( ?string $lang = null ): string {
		$url = home_url( '/' . self::BASE . '/' );
		if ( has_filter( 'wpml_permalink' ) ) {
			$tr  = null === $lang ? apply_filters( 'wpml_permalink', $url ) : apply_filters( 'wpml_permalink', $url, $lang, true );
			$url = is_string( $tr ) && '' !== $tr ? $tr : $url;
		}
		return $url;
	}

	/**
	 * @param array<string,scalar> $args f_city, f_platform, sort, pg.
	 */
	public static function catalog_url( array $args = [], ?string $lang = null ): string {
		$args = array_filter( $args, static fn( $v ) => '' !== (string) $v && 'new' !== $v && 1 !== $v && '1' !== $v );
		return $args ? add_query_arg( $args, self::base_url( $lang ) ) : self::base_url( $lang );
	}

	public static function fleet_url( int $uid, ?string $lang = null ): string {
		$slug = (string) get_user_meta( $uid, self::META_SLUG, true );
		if ( '' === $slug ) {
			$slug = self::is_visible( $uid ) ? self::ensure_slug( $uid ) : (string) $uid;
		}
		return self::base_url( $lang ) . rawurlencode( $slug ) . '/';
	}

	/* ------------------------------------------------------------------ *
	 * Listings of a fleet
	 * ------------------------------------------------------------------ */

	/**
	 * One ID per WPML translation group, preferring the current language.
	 *
	 * @param array<int,array{id:int,trid:int,lang:string}> $rows Listing rows in display order.
	 * @return int[]
	 */
	public static function one_per_group( array $rows, string $current ): array {
		$pick  = [];
		$order = [];
		foreach ( $rows as $r ) {
			$group = $r['trid'] ?: -$r['id'];
			if ( ! isset( $pick[ $group ] ) ) {
				$pick[ $group ] = $r;
				$order[]        = $group;
			} elseif ( $r['lang'] === $current && $pick[ $group ]['lang'] !== $current ) {
				$pick[ $group ] = $r;
			}
		}
		return array_map( static fn( $g ) => (int) $pick[ $g ]['id'], $order );
	}

	/**
	 * Published listings that belong to the fleet (Listing_Fleet::is_fleet_listing), newest first.
	 *
	 * @return int[]
	 */
	public static function listing_ids( int $uid ): array {
		if ( isset( self::$listings[ $uid ] ) ) {
			return self::$listings[ $uid ];
		}
		$ids  = get_posts(
			[
				'post_type'        => 'car_listing',
				'post_status'      => 'publish',
				'author'           => $uid,
				'fields'           => 'ids',
				'posts_per_page'   => 200,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => true, // Every language; one copy per translation group below.
				'no_found_rows'    => true,
			]
		);
		$rows = [];
		foreach ( array_map( 'intval', (array) $ids ) as $id ) {
			if ( ! Listing_Fleet::is_fleet_listing( $id ) ) {
				continue;
			}
			$rows[] = [
				'id'   => $id,
				'trid' => (int) apply_filters( 'wpml_element_trid', 0, $id, 'post_car_listing' ),
				'lang' => (string) apply_filters( 'wpml_element_language_code', '', [ 'element_id' => $id, 'element_type' => 'car_listing' ] ),
			];
		}
		self::$listings[ $uid ] = self::one_per_group( $rows, I18n::lang() );
		return self::$listings[ $uid ];
	}

	/**
	 * Cities of the fleet's listings: default-language term ID => [name, slug] in the current language.
	 *
	 * @return array<int,array{name:string,slug:string}>
	 */
	public static function cities( int $uid ): array {
		$ids = self::listing_ids( $uid );
		if ( ! $ids ) {
			return [];
		}
		$terms = wp_get_object_terms( $ids, 'city' );
		if ( is_wp_error( $terms ) ) {
			return [];
		}
		$default = (string) apply_filters( 'wpml_default_language', 'sk' );
		$out     = [];
		foreach ( $terms as $term ) {
			$key = (int) apply_filters( 'wpml_object_id', (int) $term->term_id, 'city', true, $default );
			if ( isset( $out[ $key ] ) ) {
				continue;
			}
			$cur_id = (int) apply_filters( 'wpml_object_id', (int) $term->term_id, 'city', true );
			$cur    = $cur_id && $cur_id !== (int) $term->term_id ? get_term( $cur_id, 'city' ) : $term;
			$cur    = $cur && ! is_wp_error( $cur ) ? $cur : $term;
			$out[ $key ] = [ 'name' => (string) $cur->name, 'slug' => (string) $cur->slug ];
		}
		uasort( $out, static fn( $a, $b ) => strcmp( $a['name'], $b['name'] ) );
		return $out;
	}

	/* ------------------------------------------------------------------ *
	 * Card / page data
	 * ------------------------------------------------------------------ */

	/** Two letters for the round placeholder: capitals of the name (DriveRent → DR), else first letters of two words. */
	public static function initials( string $name ): string {
		$clean = trim( (string) preg_replace( '/\s+/u', ' ', $name ) );
		foreach ( self::LEGAL as $form ) {
			$clean = trim( (string) preg_replace( '/(^|[\s,])' . preg_quote( $form, '/' ) . '(?=$|[\s,])/iu', ' ', $clean ) );
		}
		if ( preg_match_all( '/\p{Lu}/u', $clean, $m ) && count( $m[0] ) >= 2 ) {
			return $m[0][0] . $m[0][1];
		}
		$words = array_values( array_filter( preg_split( '/[^\p{L}\p{N}]+/u', $clean ) ?: [] ) );
		if ( count( $words ) >= 2 ) {
			return mb_strtoupper( mb_substr( $words[0], 0, 1 ) . mb_substr( $words[1], 0, 1 ) );
		}
		return mb_strtoupper( mb_substr( $words[0] ?? '?', 0, 2 ) );
	}

	/** 8 → "8", 8.5 → "8,5", 1200 → "1 200". */
	public static function money( $v ): string {
		return rtrim( rtrim( number_format( (float) $v, 2, ',', ' ' ), '0' ), ',' );
	}

	/**
	 * Headline price of a fleet card: commission for own-car drivers, else the
	 * commission on top of the rent, else the rent itself. Null when nothing is set.
	 *
	 * @param array<string,mixed> $p Live profile.
	 * @return array{label:string,value:string,unit:string}|null
	 */
	public static function headline( array $p ): ?array {
		$models = (array) ( $p['models'] ?? [] );
		if ( in_array( 'own', $models, true ) && '' !== (string) ( $p['own_fee'] ?? '' ) ) {
			$fixed = 'fixed' === ( $p['own_pay_type'] ?? '' );
			return [
				'label' => t( 'fl_commission' ),
				'value' => self::money( $p['own_fee'] ) . ( $fixed ? ' €' : ' %' ),
				'unit'  => $fixed ? t( 'fl_per_week' ) : t( 'fl_of_revenue' ),
			];
		}
		if ( in_array( 'fleet', $models, true ) ) {
			if ( ! empty( $p['rent_comm_on'] ) && '' !== (string) ( $p['rent_comm'] ?? '' ) ) {
				return [ 'label' => t( 'fl_commission' ), 'value' => self::money( $p['rent_comm'] ) . ' %', 'unit' => t( 'fl_of_revenue' ) ];
			}
			if ( '' !== (string) ( $p['rent_price'] ?? '' ) ) {
				return [
					'label' => t( 'fl_rent_label' ),
					'value' => self::money( $p['rent_price'] ) . ' €',
					'unit'  => 'day' === ( $p['rent_period'] ?? '' ) ? t( 'fl_per_day' ) : t( 'fl_per_week' ),
				];
			}
		}
		return null;
	}

	/**
	 * Feature chips of a fleet card.
	 *
	 * @param array<string,mixed> $p Live profile.
	 * @return array<int,array{icon:string,text:string}>
	 */
	public static function features( array $p ): array {
		$out    = [];
		$models = (array) ( $p['models'] ?? [] );
		$freq   = (string) ( $p['pay_freq'] ?? '' );
		if ( in_array( $freq, Profile::CHOICES['pay_freq'], true ) ) {
			$out[] = [ 'icon' => 'paid', 'text' => t( 'fl_feat_pay_' . $freq ) ];
		}
		if ( in_array( 'fleet', $models, true ) ) {
			$out[] = [ 'icon' => 'car', 'text' => t( 'fl_feat_fleet_car' ) ];
		}
		if ( in_array( 'own', $models, true ) ) {
			$out[] = [ 'icon' => 'car', 'text' => t( 'fl_feat_own_car' ) ];
		}
		if ( ! empty( $p['perk_fuel'] ) ) {
			$out[] = [ 'icon' => 'card', 'text' => t( 'perk_fuel' ) ];
		}
		return $out;
	}

	/** Language chip label (uk → UA). */
	public static function lang_label( string $code ): string {
		return 'uk' === $code ? 'UA' : strtoupper( $code );
	}

	/** Short description for a card. */
	public static function excerpt( string $text, int $max = 180 ): string {
		$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
		if ( mb_strlen( $text ) <= $max ) {
			return $text;
		}
		$cut = mb_substr( $text, 0, $max );
		$sp  = mb_strrpos( $cut, ' ' );
		return rtrim( false !== $sp && $sp > $max * 0.6 ? mb_substr( $cut, 0, $sp ) : $cut, " ,.;:—-" ) . '…';
	}

	/**
	 * Everything a card / page needs about one fleet.
	 *
	 * @return array<string,mixed>
	 */
	public static function card( int $uid ): array {
		if ( isset( self::$cards[ $uid ] ) ) {
			return self::$cards[ $uid ];
		}
		$p      = Profile::get_live( $uid );
		$name   = Listing_Fleet::fleet_name( $uid );
		$cities = self::cities( $uid );
		$user   = get_userdata( $uid );
		$logo   = ! empty( $p['logo_id'] ) ? (string) wp_get_attachment_image_url( (int) $p['logo_id'], 'medium' ) : '';
		$plats  = array_values( array_intersect( array_keys( self::PLATFORMS ), (array) ( $p['platforms'] ?? [] ) ) );

		$card = [
			'id'         => $uid,
			'url'        => self::fleet_url( $uid ),
			'name'       => $name,
			'initials'   => self::initials( $name ),
			'logo'       => $logo,
			'cities'     => $cities,
			'city_text'  => trim( (string) get_user_meta( $uid, 'city', true ) ),
			'platforms'  => $plats,
			'headline'   => self::headline( $p ),
			'desc'       => (string) ( $p['description'] ?? '' ),
			'features'   => self::features( $p ),
			'langs'      => array_values( (array) ( $p['langs'] ?? [] ) ),
			'models'     => array_values( (array) ( $p['models'] ?? [] ) ),
			'since'      => $user && ! empty( $user->user_registered ) ? (int) strtotime( (string) $user->user_registered ) : 0,
			'profile'    => $p,
		];
		self::$cards[ $uid ] = $card;
		return $card;
	}

	/** City line of a card: listing cities, else the account's city text. */
	public static function city_line( array $card ): string {
		$names = array_column( (array) $card['cities'], 'name' );
		return $names ? implode( ', ', $names ) : (string) ( $card['city_text'] ?? '' );
	}

	/* ------------------------------------------------------------------ *
	 * Filtering, sorting, pagination (pure)
	 * ------------------------------------------------------------------ */

	/**
	 * @param array<int,array<string,mixed>> $cards    Cards.
	 * @param int                            $city     Default-language city term ID, 0 = any.
	 * @param string                         $platform Platform code, '' = any.
	 * @param string                         $sort     'new' | 'name'.
	 * @return array<int,array<string,mixed>>
	 */
	public static function filter( array $cards, int $city, string $platform, string $sort ): array {
		$out = array_values(
			array_filter(
				$cards,
				static function ( $c ) use ( $city, $platform ) {
					if ( $city && ! isset( $c['cities'][ $city ] ) ) {
						return false;
					}
					return '' === $platform || in_array( $platform, (array) $c['platforms'], true );
				}
			)
		);
		usort(
			$out,
			'name' === $sort
				? static fn( $a, $b ) => strnatcasecmp( (string) $a['name'], (string) $b['name'] ) ?: $a['id'] <=> $b['id']
				: static fn( $a, $b ) => ( $b['since'] <=> $a['since'] ) ?: $b['id'] <=> $a['id']
		);
		return $out;
	}

	/**
	 * @param array<int,mixed> $items Items.
	 * @return array{items:array<int,mixed>,page:int,pages:int,total:int}
	 */
	public static function paginate( array $items, int $page, int $per = self::PER_PAGE ): array {
		$total = count( $items );
		$pages = max( 1, (int) ceil( $total / max( 1, $per ) ) );
		$page  = min( max( 1, $page ), $pages );
		return [ 'items' => array_slice( $items, ( $page - 1 ) * $per, $per ), 'page' => $page, 'pages' => $pages, 'total' => $total ];
	}

	/**
	 * Page numbers to show: 1 2 3 … 11 (0 = gap).
	 *
	 * @return int[]
	 */
	public static function page_links( int $page, int $pages ): array {
		if ( $pages <= 7 ) {
			return range( 1, $pages );
		}
		$set = array_unique( [ 1, $pages, $page - 1, $page, $page + 1, $page <= 3 ? 2 : 0, $page <= 3 ? 3 : 0, $page >= $pages - 2 ? $pages - 1 : 0, $page >= $pages - 2 ? $pages - 2 : 0 ] );
		$set = array_values( array_filter( $set, static fn( $n ) => $n >= 1 && $n <= $pages ) );
		sort( $set );
		$out  = [];
		$prev = 0;
		foreach ( $set as $n ) {
			if ( $prev && $n > $prev + 1 ) {
				$out[] = 0;
			}
			$out[] = $n;
			$prev  = $n;
		}
		return $out;
	}

	/**
	 * Cities that at least one card has: key => [name, slug], sorted by name.
	 *
	 * @param array<int,array<string,mixed>> $cards Cards.
	 * @return array<int,array{name:string,slug:string}>
	 */
	public static function city_options( array $cards ): array {
		$out = [];
		foreach ( $cards as $c ) {
			foreach ( (array) $c['cities'] as $k => $v ) {
				$out[ (int) $k ] = $v;
			}
		}
		uasort( $out, static fn( $a, $b ) => strcmp( $a['name'], $b['name'] ) );
		return $out;
	}

	/**
	 * Platforms that at least one card has, in the fixed order.
	 *
	 * @param array<int,array<string,mixed>> $cards Cards.
	 * @return string[]
	 */
	public static function platform_options( array $cards ): array {
		$have = [];
		foreach ( $cards as $c ) {
			$have = array_merge( $have, (array) $c['platforms'] );
		}
		return array_values( array_intersect( array_keys( self::PLATFORMS ), $have ) );
	}

	/**
	 * City key for a slug from the filter (the slug may be in any language the options show).
	 *
	 * @param array<int,array{name:string,slug:string}> $options City options.
	 */
	public static function city_key( array $options, string $slug ): int {
		if ( '' === $slug ) {
			return 0;
		}
		foreach ( $options as $k => $o ) {
			if ( $o['slug'] === $slug ) {
				return (int) $k;
			}
		}
		return 0;
	}
}
