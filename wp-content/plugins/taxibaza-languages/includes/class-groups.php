<?php
/**
 * WPML translation groups of car listings, read straight from the database
 * (WPML's own API filters by the current language, which is the problem here).
 *
 * @package TaxiBaza\Languages
 */

namespace TaxiBaza\Languages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Groups {

	public const TYPE = 'post_car_listing';

	/** For tests: replaces the database read. @var callable|null */
	public static $source = null;

	/** @var array<int,array{id:int,trid:int,lang:string,src:string,status:string,author:int,exists:bool}>|null */
	private static ?array $rows = null;

	public static function wpml(): bool {
		return has_filter( 'wpml_current_language' ) || defined( 'ICL_SITEPRESS_VERSION' );
	}

	public static function flush(): void {
		self::$rows = null;
	}

	/**
	 * Every car_listing translation row plus listings WPML doesn't know about.
	 * `exists` = the post is in the database (not a dangling WPML row).
	 *
	 * @return array<int,array{id:int,trid:int,lang:string,src:string,status:string,author:int,exists:bool}>
	 */
	public static function rows(): array {
		if ( null !== self::$rows ) {
			return self::$rows;
		}
		if ( self::$source ) {
			self::$rows = (array) call_user_func( self::$source );
			return self::$rows;
		}
		global $wpdb;
		$rows = [];
		if ( self::wpml() ) {
			$table = $wpdb->prefix . 'icl_translations';
			$found = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT t.translation_id AS tid, COALESCE(t.element_id,0) AS id, t.trid, t.language_code AS lang, COALESCE(t.source_language_code,'') AS src,
				        COALESCE(p.post_status,'') AS status, COALESCE(p.post_author,0) AS author, (p.ID IS NOT NULL) AS ex
				 FROM {$table} t
				 LEFT JOIN {$wpdb->posts} p ON p.ID = t.element_id AND p.post_type = 'car_listing'
				 WHERE t.element_type = '" . self::TYPE . "'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);
			foreach ( (array) $found as $r ) {
				$rows[] = [
					'tid'    => (int) $r['tid'],
					'id'     => (int) $r['id'],
					'trid'   => (int) $r['trid'],
					'lang'   => (string) $r['lang'],
					'src'    => (string) $r['src'],
					'status' => (string) $r['status'],
					'author' => (int) $r['author'],
					'exists' => (bool) $r['ex'],
				];
			}
			$loose = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				"SELECT p.ID AS id, p.post_status AS status, p.post_author AS author FROM {$wpdb->posts} p
				 LEFT JOIN {$table} t ON t.element_id = p.ID AND t.element_type = '" . self::TYPE . "'
				 WHERE p.post_type = 'car_listing' AND t.translation_id IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);
		} else {
			$loose = $wpdb->get_results( "SELECT ID AS id, post_status AS status, post_author AS author FROM {$wpdb->posts} WHERE post_type = 'car_listing'", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		foreach ( (array) $loose as $r ) {
			$rows[] = [
				'tid'    => 0,
				'id'     => (int) $r['id'],
				'trid'   => -(int) $r['id'],
				'lang'   => '',
				'src'    => '',
				'status' => (string) $r['status'],
				'author' => (int) $r['author'],
				'exists' => true,
			];
		}
		self::$rows = $rows;
		return $rows;
	}

	/** @return array<int,array<int,array<string,mixed>>> trid => rows. */
	public static function by_trid(): array {
		$out = [];
		foreach ( self::rows() as $r ) {
			$out[ $r['trid'] ][] = $r;
		}
		return $out;
	}

	/**
	 * The original of a group: the source-language post if it still exists,
	 * otherwise the oldest existing post of the group.
	 *
	 * @param array<int,array<string,mixed>> $group Rows of one trid.
	 * @return array<string,mixed>|null
	 */
	public static function original_of( array $group ): ?array {
		$alive = array_values( array_filter( $group, static fn( $r ) => $r['exists'] && 'trash' !== $r['status'] && 'auto-draft' !== $r['status'] ) );
		if ( ! $alive ) {
			return null;
		}
		foreach ( $alive as $r ) {
			if ( '' === $r['src'] ) {
				return $r;
			}
		}
		usort( $alive, static fn( $a, $b ) => $a['id'] <=> $b['id'] );
		return $alive[0];
	}

	/**
	 * One listing per group — its original — in every language.
	 *
	 * @return int[]
	 */
	public static function original_ids(): array {
		$ids = [];
		foreach ( self::by_trid() as $group ) {
			$o = self::original_of( $group );
			if ( $o ) {
				$ids[] = (int) $o['id'];
			}
		}
		sort( $ids );
		return $ids;
	}

	/**
	 * What a visitor of $lang should see for a group: the published copy in
	 * $lang, else the published original, else any published copy.
	 *
	 * @param array<int,array<string,mixed>> $group Rows of one trid.
	 */
	public static function best_published( array $group, string $lang ): int {
		$pub = array_values( array_filter( $group, static fn( $r ) => $r['exists'] && 'publish' === $r['status'] ) );
		if ( ! $pub ) {
			return 0;
		}
		foreach ( $pub as $r ) {
			if ( $lang === $r['lang'] ) {
				return (int) $r['id'];
			}
		}
		foreach ( $pub as $r ) {
			if ( '' === $r['src'] ) {
				return (int) $r['id'];
			}
		}
		return (int) $pub[0]['id'];
	}

	/**
	 * Same list of groups, each mapped to the copy a visitor of $lang should see.
	 * IDs WPML doesn't know are kept as they are.
	 *
	 * @param int[] $ids Listing IDs (any copy of each group).
	 * @return int[]
	 */
	public static function for_visitors( array $ids, string $lang ): array {
		$trid_of = [];
		foreach ( self::rows() as $r ) {
			if ( $r['id'] > 0 ) {
				$trid_of[ $r['id'] ] = $r['trid'];
			}
		}
		$groups = self::by_trid();
		$out    = [];
		// The core's catalogue list holds one row per group and keeps empty WPML
		// placeholders (element_id NULL → 0). A 0 can only come from that full
		// list, so then every group is answered here.
		if ( in_array( 0, array_map( 'intval', $ids ), true ) ) {
			foreach ( $groups as $trid => $group ) {
				$best = self::best_published( $group, $lang );
				if ( $best ) {
					$out[ $best ] = $best;
				}
			}
		}
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( 0 === $id ) {
				continue;
			}
			if ( ! isset( $trid_of[ $id ] ) ) {
				$out[ $id ] = $id;
				continue;
			}
			$best = self::best_published( $groups[ $trid_of[ $id ] ], $lang );
			if ( $best ) {
				$out[ $best ] = $best;
			}
		}
		return array_values( $out );
	}

	/** Language of the original of a listing (any copy of it), '' if unknown. */
	public static function original_lang( int $id ): string {
		foreach ( self::by_trid() as $group ) {
			foreach ( $group as $r ) {
				if ( $r['id'] === $id ) {
					$o = self::original_of( $group );
					return $o ? (string) $o['lang'] : '';
				}
			}
		}
		return '';
	}

	/**
	 * Broken links: WPML rows without a post — empty placeholders left by
	 * deleted translations (element_id NULL) or rows of posts deleted outside
	 * WordPress. Listings in the trash are not broken: WordPress empties the
	 * trash by itself.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function broken(): array {
		return array_values( array_filter( self::rows(), static fn( $r ) => $r['trid'] > 0 && ! $r['exists'] ) );
	}
}
