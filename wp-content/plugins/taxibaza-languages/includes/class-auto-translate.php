<?php
/**
 * Automatic translations of car listings (WPML stays in manual mode).
 *
 * When a moderator publishes an original listing, a translation is created for
 * every other site language that doesn't have one yet:
 * - the description fields are translated by DeepL;
 * - everything else (title, city, price, platforms, year, gearbox, fuel,
 *   deposit, photos, contacts …) is copied, so a translation is never empty;
 * - the translation joins the listing's WPML group, so the catalogue and the
 *   language switcher of each language link to their own language.
 * A translation written by DeepL is refreshed when the original is published
 * again, unless somebody has edited its texts by hand (then it is left alone).
 * Translations made by people are never overwritten.
 *
 * Translations follow the original's visibility: when the original leaves
 * "published" (back to moderation, archived, rejected, trash) its published
 * translations are hidden too, and come back when the original is published.
 *
 * @package TaxiBaza\Languages
 */

namespace TaxiBaza\Languages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Auto_Translate {

	/** Fields translated by DeepL; the rest of the meta is copied. */
	public const FIELDS = [ 'short_description', 'insurance_details', 'maintenance_details' ];

	/** Never copied to a translation. */
	public const SKIP_META = [ '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_wp_desired_post_slug', '_wp_trash_meta_status', '_wp_trash_meta_time', '_tb_listing_views', '_icl_lang_duplicate_of', self::META_AUTO, self::META_HELD ];

	public const META_AUTO = '_tbl_auto';  // On a translation made here: hash of the texts written.
	public const META_HELD = '_tbl_held';  // On a translation hidden because its original was unpublished.
	public const CRON      = 'tbl_translate_backlog';

	private static bool $busy = false;

	public static function init(): void {
		add_action( 'transition_post_status', [ self::class, 'on_transition' ], 20, 3 );
		add_action( 'before_delete_post', [ self::class, 'on_delete' ] );
		add_action( self::CRON, [ self::class, 'run_backlog' ] );
		add_action( 'init', [ self::class, 'schedule' ] );
	}

	public static function schedule(): void {
		if ( DeepL::enabled() && ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + 300, 'hourly', self::CRON );
		} elseif ( ! DeepL::enabled() && wp_next_scheduled( self::CRON ) ) {
			wp_clear_scheduled_hook( self::CRON );
		}
	}

	/* ---------------- groups ---------------- */

	/** @return array{trid:int,lang:string,src:string} */
	public static function details( int $id ): array {
		foreach ( Groups::rows() as $r ) {
			if ( $r['id'] === $id && $r['trid'] > 0 ) {
				return [ 'trid' => $r['trid'], 'lang' => $r['lang'], 'src' => $r['src'] ];
			}
		}
		return [ 'trid' => 0, 'lang' => '', 'src' => '' ];
	}

	public static function is_original( int $id ): bool {
		$d = self::details( $id );
		if ( ! $d['trid'] ) {
			return false;
		}
		$o = Groups::original_of( Groups::by_trid()[ $d['trid'] ] ?? [] );
		return $o && $o['id'] === $id;
	}

	/** @return array<string,int> lang => post ID of the other copies (existing, not in the trash). */
	public static function translations( int $id ): array {
		$d = self::details( $id );
		if ( ! $d['trid'] ) {
			return [];
		}
		$out = [];
		foreach ( Groups::by_trid()[ $d['trid'] ] ?? [] as $r ) {
			if ( $r['id'] !== $id && $r['exists'] && 'trash' !== $r['status'] ) {
				$out[ $r['lang'] ] = $r['id'];
			}
		}
		return $out;
	}

	/** @return string[] Site languages. */
	public static function languages(): array {
		$langs = array_keys( (array) apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] ) );
		return $langs ?: [ 'sk', 'uk', 'en', 'ru' ];
	}

	/* ---------------- status follows the original ---------------- */

	/**
	 * @param string   $new  New status.
	 * @param string   $old  Old status.
	 * @param \WP_Post $post Post.
	 */
	public static function on_transition( $new, $old, $post ): void {
		if ( self::$busy || ! $post instanceof \WP_Post || 'car_listing' !== $post->post_type || $new === $old || ! Groups::wpml() ) {
			return;
		}
		Groups::flush();
		if ( ! self::is_original( (int) $post->ID ) ) {
			return;
		}
		if ( 'publish' === $new ) {
			self::release( (int) $post->ID );
			if ( DeepL::enabled() ) {
				self::sync( (int) $post->ID );
			}
		} elseif ( 'publish' === $old ) {
			self::hold( (int) $post->ID, (string) $new );
		}
	}

	/**
	 * Run without the core's "your listing was published / is in moderation"
	 * e-mails: they belong to the original, not to each translation.
	 */
	private static function quietly( callable $fn ): void {
		$cb  = [ 'TaxiRental\Core\Notifications', 'listing_status' ];
		$had = class_exists( 'TaxiRental\Core\Notifications' ) && false !== has_action( 'transition_post_status', $cb );
		if ( $had ) {
			remove_action( 'transition_post_status', $cb, 10 );
		}
		self::$busy = true;
		try {
			$fn();
		} finally {
			self::$busy = false;
			if ( $had ) {
				add_action( 'transition_post_status', $cb, 10, 3 );
			}
			Groups::flush();
		}
	}

	/** Original left "published": its published translations are hidden (draft) until it is back. */
	private static function hold( int $id, string $status ): void {
		self::quietly(
			static function () use ( $id, $status ): void {
				foreach ( self::translations( $id ) as $tid ) {
					if ( 'publish' !== get_post_status( $tid ) ) {
						continue;
					}
					update_post_meta( $tid, self::META_HELD, 1 );
					if ( 'trash' === $status ) {
						wp_trash_post( $tid );
					} else {
						wp_update_post( [ 'ID' => $tid, 'post_status' => 'draft' ] );
					}
				}
			}
		);
	}

	/** Original published again: show the translations hidden with it. */
	private static function release( int $id ): void {
		self::quietly(
			static function () use ( $id ): void {
				foreach ( self::translations( $id ) as $tid ) {
					if ( get_post_meta( $tid, self::META_HELD, true ) ) {
						delete_post_meta( $tid, self::META_HELD );
						wp_update_post( [ 'ID' => $tid, 'post_status' => 'publish' ] );
					}
				}
			}
		);
	}

	/** An original deleted for good takes its machine translations with it. */
	public static function on_delete( $post_id ): void {
		$post_id = (int) $post_id;
		if ( self::$busy || 'car_listing' !== get_post_type( $post_id ) || ! self::is_original( $post_id ) ) {
			return;
		}
		self::quietly(
			static function () use ( $post_id ): void {
				foreach ( self::translations( $post_id ) as $tid ) {
					if ( get_post_meta( $tid, self::META_AUTO, true ) ) {
						wp_delete_post( $tid, true );
					}
				}
			}
		);
	}

	/* ---------------- translating ---------------- */

	/** @param array<string,string> $texts Field => text. */
	public static function hash( array $texts ): string {
		ksort( $texts );
		return md5( (string) wp_json_encode( array_map( 'strval', $texts ) ) );
	}

	/** @return array<string,string> */
	private static function texts( int $id ): array {
		$out = [];
		foreach ( self::FIELDS as $f ) {
			$out[ $f ] = (string) get_post_meta( $id, $f, true );
		}
		return $out;
	}

	/**
	 * Create the missing translations of an original and refresh the ones made
	 * here that nobody edited.
	 *
	 * @return array{created:string[],updated:string[],failed:string[]}
	 */
	public static function sync( int $id ): array {
		$done = [ 'created' => [], 'updated' => [], 'failed' => [] ];
		$d    = self::details( $id );
		if ( ! $d['trid'] || '' === $d['lang'] || 'publish' !== get_post_status( $id ) ) {
			return $done;
		}
		$have  = self::translations( $id );
		$texts = self::texts( $id );
		foreach ( self::languages() as $lang ) {
			if ( $lang === $d['lang'] ) {
				continue;
			}
			$tid = $have[ $lang ] ?? 0;
			if ( $tid ) {
				$mark = (string) get_post_meta( $tid, self::META_AUTO, true );
				if ( '' === $mark || self::hash( self::texts( $tid ) ) !== $mark ) {
					continue; // Made or edited by a person.
				}
			}
			$tr = DeepL::translate( $texts, $d['lang'], $lang );
			if ( null === $tr ) {
				$done['failed'][] = $lang;
				continue;
			}
			self::write( $id, $tid, $lang, $d, $tr );
			$done[ $tid ? 'updated' : 'created' ][] = $lang;
		}
		Groups::flush();
		return $done;
	}

	/**
	 * @param array{trid:int,lang:string,src:string} $d  Original's WPML details.
	 * @param array<string,string>                   $tr Translated fields.
	 */
	private static function write( int $id, int $tid, string $lang, array $d, array $tr ): int {
		$orig = get_post( $id );
		if ( ! $orig ) {
			return 0;
		}
		$out = 0;
		self::quietly(
			static function () use ( $orig, $id, $tid, $lang, $d, $tr, &$out ): void {
				$out = self::write_now( $orig, $id, $tid, $lang, $d, $tr );
			}
		);
		return $out;
	}

	/**
	 * @param array{trid:int,lang:string,src:string} $d  Original's WPML details.
	 * @param array<string,string>                   $tr Translated fields.
	 */
	private static function write_now( \WP_Post $orig, int $id, int $tid, string $lang, array $d, array $tr ): int {
		$data = [
			'post_type'    => 'car_listing',
			'post_status'  => 'publish',
			'post_author'  => (int) $orig->post_author,
			'post_title'   => $orig->post_title,
			'post_content' => $orig->post_content,
			'post_excerpt' => $orig->post_excerpt,
		];
		if ( $tid ) {
			$data['ID'] = $tid;
			wp_update_post( wp_slash( $data ) );
		} else {
			$data['post_date']     = $orig->post_date;
			$data['post_date_gmt'] = $orig->post_date_gmt;
			$tid                   = (int) wp_insert_post( wp_slash( $data ) );
			if ( ! $tid ) {
				return 0;
			}
			do_action(
				'wpml_set_element_language_details',
				[
					'element_id'           => $tid,
					'element_type'         => Groups::TYPE,
					'trid'                 => $d['trid'],
					'language_code'        => $lang,
					'source_language_code' => $d['lang'],
				]
			);
			// Same address as the original, in the language folder (WPML allows it once the language is known).
			wp_update_post( [ 'ID' => $tid, 'post_name' => $orig->post_name ] );
		}
		self::copy_meta( $id, $tid );
		foreach ( $tr as $field => $text ) {
			update_post_meta( $tid, $field, wp_slash( $text ) );
		}
		update_post_meta( $tid, self::META_AUTO, self::hash( $tr ) );
		self::copy_terms( $id, $tid, $lang );
		return $tid;
	}

	private static function copy_meta( int $from, int $to ): void {
		foreach ( (array) get_post_meta( $from ) as $key => $values ) {
			if ( in_array( $key, self::SKIP_META, true ) || in_array( $key, self::FIELDS, true ) ) {
				continue;
			}
			delete_post_meta( $to, $key );
			foreach ( (array) $values as $v ) {
				add_post_meta( $to, $key, wp_slash( maybe_unserialize( $v ) ) );
			}
		}
	}

	/** City, platforms, categories … in the translation's language when that term is translated. */
	private static function copy_terms( int $from, int $to, string $lang ): void {
		foreach ( get_object_taxonomies( 'car_listing' ) as $tax ) {
			$ids = wp_get_object_terms( $from, $tax, [ 'fields' => 'ids' ] );
			if ( is_wp_error( $ids ) ) {
				continue;
			}
			$mapped = [];
			foreach ( (array) $ids as $term_id ) {
				$mapped[] = (int) apply_filters( 'wpml_object_id', (int) $term_id, $tax, true, $lang );
			}
			wp_set_object_terms( $to, array_values( array_unique( array_filter( $mapped ) ) ), $tax );
		}
	}

	/* ---------------- backlog ---------------- */

	/**
	 * Published originals that miss a translation in some language.
	 *
	 * @return int[]
	 */
	public static function backlog(): array {
		$langs = self::languages();
		$out   = [];
		foreach ( Groups::by_trid() as $trid => $group ) {
			if ( $trid <= 0 ) {
				continue;
			}
			$o = Groups::original_of( $group );
			if ( ! $o || 'publish' !== $o['status'] || '' === $o['lang'] ) {
				continue;
			}
			$have = [];
			foreach ( $group as $r ) {
				if ( $r['exists'] && 'trash' !== $r['status'] ) {
					$have[] = $r['lang'];
				}
			}
			if ( array_diff( $langs, $have ) ) {
				$out[] = (int) $o['id'];
			}
		}
		sort( $out );
		return $out;
	}

	/** @return array{done:int,failed:int,left:int} */
	public static function run_backlog( int $limit = 10 ): array {
		$limit = $limit > 0 ? $limit : 10;
		$res   = [ 'done' => 0, 'failed' => 0, 'left' => 0 ];
		if ( ! DeepL::enabled() ) {
			$res['left'] = count( self::backlog() );
			return $res;
		}
		foreach ( array_slice( self::backlog(), 0, $limit ) as $id ) {
			$r = self::sync( $id );
			$res[ $r['failed'] ? 'failed' : 'done' ]++;
		}
		Groups::flush();
		$res['left'] = count( self::backlog() );
		return $res;
	}
}
