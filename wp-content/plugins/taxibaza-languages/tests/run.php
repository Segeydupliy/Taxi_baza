<?php
/**
 * Plain-PHP tests (no WordPress): php tests/run.php
 *
 * @package TaxiBaza\Languages
 */

namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	define( 'ARRAY_A', 'ARRAY_A' );
	$GLOBALS['__filters'] = [];
	$GLOBALS['__env']     = [ 'ajax' => false, 'admin' => false, 'tpl' => '', 'cap' => true ];
	$GLOBALS['__posts']   = [];
	$GLOBALS['__meta']    = [];
	$GLOBALS['__terms']   = [];
	$GLOBALS['__icl']     = [];   // id => [trid, lang, src]
	$GLOBALS['__next']    = 5000;
	$GLOBALS['__lang']    = 'sk';
	$GLOBALS['__options'] = [];
	$GLOBALS['__mails']   = 0;

	function add_filter( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['__filters'][ $h ][ $p ][] = [ $cb, $a ]; return true; }
	function add_action( $h, $cb, $p = 10, $a = 1 ) { return add_filter( $h, $cb, $p, $a ); }
	function has_filter( $h, $cb = false ) {
		if ( empty( $GLOBALS['__filters'][ $h ] ) ) { return false; }
		if ( false === $cb ) { return true; }
		foreach ( $GLOBALS['__filters'][ $h ] as $p => $list ) { foreach ( $list as $f ) { if ( $f[0] === $cb ) { return $p; } } }
		return false;
	}
	function has_action( $h, $cb = false ) { return has_filter( $h, $cb ); }
	function remove_action( $h, $cb, $p = 10 ) { if ( ! empty( $GLOBALS['__filters'][ $h ][ $p ] ) ) { $GLOBALS['__filters'][ $h ][ $p ] = array_values( array_filter( $GLOBALS['__filters'][ $h ][ $p ], fn( $f ) => $f[0] !== $cb ) ); } return true; }
	function apply_filters( $h, $v = null, ...$args ) {
		if ( empty( $GLOBALS['__filters'][ $h ] ) ) { return $v; }
		ksort( $GLOBALS['__filters'][ $h ] );
		foreach ( $GLOBALS['__filters'][ $h ] as $list ) { foreach ( $list as $f ) { $v = call_user_func_array( $f[0], array_slice( array_merge( [ $v ], $args ), 0, max( 1, $f[1] ) ) ); } }
		return $v;
	}
	function do_action( $h, ...$args ) {
		if ( empty( $GLOBALS['__filters'][ $h ] ) ) { return; }
		ksort( $GLOBALS['__filters'][ $h ] );
		foreach ( $GLOBALS['__filters'][ $h ] as $list ) { foreach ( $list as $f ) { call_user_func_array( $f[0], array_slice( $args, 0, max( 1, $f[1] ) ) ); } }
	}
	function wp_doing_ajax() { return $GLOBALS['__env']['ajax']; }
	function is_admin() { return $GLOBALS['__env']['admin']; }
	function did_action( $h ) { return 1; }
	function is_page_template( $t ) { return $GLOBALS['__env']['tpl'] === $t; }
	function sanitize_key( $k ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $k ) ); }
	function wp_unslash( $v ) { return $v; }
	function wp_slash( $v ) { return $v; }
	function current_user_can( $c ) { return $GLOBALS['__env']['cap']; }
	function wp_json_encode( $v, $f = 0 ) { return json_encode( $v, $f ); }
	function get_option( $k, $d = false ) { return $GLOBALS['__options'][ $k ] ?? $d; }
	function update_option( $k, $v, $a = null ) { $GLOBALS['__options'][ $k ] = $v; return true; }
	function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
	function wp_next_scheduled( $h ) { return false; }
	function wp_schedule_event() { return true; }
	function wp_clear_scheduled_hook() { return true; }
	function number_format_i18n( $n ) { return number_format( $n ); }
	function maybe_unserialize( $v ) { return $v; }
	function is_wp_error( $v ) { return false; }

	// Posts in memory.
	function get_post( $id ) { $p = $GLOBALS['__posts'][ (int) $id ] ?? null; return $p ? new WP_Post( $p ) : null; }
	function get_post_status( $id ) { return $GLOBALS['__posts'][ (int) $id ]['post_status'] ?? false; }
	function get_post_type( $id ) { return $GLOBALS['__posts'][ (int) $id ]['post_type'] ?? false; }
	function wp_insert_post( $d ) {
		$id = ++$GLOBALS['__next'];
		$old = 'new';
		$GLOBALS['__posts'][ $id ] = array_merge( [ 'ID' => $id, 'post_name' => sanitize_key( $d['post_title'] ?? '' ) . '-2' ], $d );
		do_action( 'transition_post_status', $d['post_status'], $old, get_post( $id ) );
		return $id;
	}
	function wp_update_post( $d ) {
		$id  = (int) $d['ID'];
		$old = $GLOBALS['__posts'][ $id ]['post_status'];
		$GLOBALS['__posts'][ $id ] = array_merge( $GLOBALS['__posts'][ $id ], $d );
		if ( isset( $d['post_status'] ) ) { do_action( 'transition_post_status', $d['post_status'], $old, get_post( $id ) ); }
		return $id;
	}
	function wp_trash_post( $id ) { return wp_update_post( [ 'ID' => $id, 'post_status' => 'trash' ] ); }
	function wp_delete_post( $id, $force = false ) { do_action( 'before_delete_post', $id ); unset( $GLOBALS['__posts'][ $id ], $GLOBALS['__icl'][ $id ] ); return true; }
	function get_post_meta( $id, $k = '', $single = false ) {
		if ( '' === $k ) { return array_map( fn( $v ) => [ $v ], $GLOBALS['__meta'][ $id ] ?? [] ); }
		return $GLOBALS['__meta'][ $id ][ $k ] ?? ( $single ? '' : [] );
	}
	function update_post_meta( $id, $k, $v ) { $GLOBALS['__meta'][ $id ][ $k ] = $v; return true; }
	function add_post_meta( $id, $k, $v ) { $GLOBALS['__meta'][ $id ][ $k ] = $v; return true; }
	function delete_post_meta( $id, $k ) { unset( $GLOBALS['__meta'][ $id ][ $k ] ); return true; }
	function get_object_taxonomies( $t ) { return [ 'city' ]; }
	function wp_get_object_terms( $id, $tax, $a = [] ) { return $GLOBALS['__terms'][ $id ][ $tax ] ?? []; }
	function wp_set_object_terms( $id, $terms, $tax ) { $GLOBALS['__terms'][ $id ][ $tax ] = $terms; return $terms; }
	function get_the_title( $id ) { return $GLOBALS['__posts'][ $id ]['post_title'] ?? ''; }

	class WP_Post { public function __construct( array $a ) { foreach ( $a as $k => $v ) { $this->$k = $v; } } }
	class Fake_Query {
		public array $q; public bool $main;
		public function __construct( array $q, bool $main = false ) { $this->q = $q; $this->main = $main; }
		public function get( $k ) { return $this->q[ $k ] ?? ''; }
		public function set( $k, $v ) { $this->q[ $k ] = $v; }
		public function is_main_query() { return $this->main; }
	}

	// WPML in memory.
	add_filter( 'wpml_current_language', fn() => $GLOBALS['__lang'] );
	add_filter( 'wpml_default_language', fn() => 'sk' );
	add_filter( 'wpml_active_languages', fn() => [ 'sk' => [], 'uk' => [], 'en' => [], 'ru' => [] ] );
	add_filter( 'wpml_element_language_code', fn( $v, $a ) => $GLOBALS['__icl'][ $a['element_id'] ][1] ?? '', 10, 2 );
	add_filter( 'wpml_object_id', fn( $id, $tax, $ret, $lang ) => 'uk' === $lang ? $id + 100 : $id, 10, 4 );
	add_action( 'wpml_set_element_language_details', function ( $a ) { $GLOBALS['__icl'][ $a['element_id'] ] = [ $a['trid'], $a['language_code'], $a['source_language_code'] ]; } );

	require dirname( __DIR__ ) . '/includes/class-groups.php';
	require dirname( __DIR__ ) . '/includes/class-scope.php';
	require dirname( __DIR__ ) . '/includes/class-sitemap.php';
	require dirname( __DIR__ ) . '/includes/class-deepl.php';
	require dirname( __DIR__ ) . '/includes/class-auto-translate.php';

	$GLOBALS['__fail'] = 0; $GLOBALS['__n'] = 0;
	function check( string $name, bool $ok ): void { $GLOBALS['__n']++; if ( ! $ok ) { $GLOBALS['__fail']++; echo "FAIL  $name\n"; } }
}

namespace TaxiRental\Core {
	final class Notifications {
		public static function listing_status( $new, $old, $post ): void { if ( 'publish' === $new && in_array( $old, [ 'pending', 'draft', 'rejected' ], true ) ) { $GLOBALS['__mails']++; } }
	}
}

namespace {
	use TaxiBaza\Languages\Groups;
	use TaxiBaza\Languages\Scope;
	use TaxiBaza\Languages\Sitemap;
	use TaxiBaza\Languages\DeepL;
	use TaxiBaza\Languages\Auto_Translate;

	// Data: Passat 841 (sk original) with empty translations 1428 (deleted) and 1429 (trash);
	// Corolla 10 (sk) + 11 (uk published) + 12 (en draft); KTM 2043 (uk original, author 7); loose 77.
	$post = fn( $id, $status, $author = 1, $title = 'Car' ) => $GLOBALS['__posts'][ $id ] = [ 'ID' => $id, 'post_type' => 'car_listing', 'post_status' => $status, 'post_author' => $author, 'post_title' => $title, 'post_name' => sanitize_key( $title ), 'post_content' => '', 'post_excerpt' => '', 'post_date' => '2026-11-01 10:00:00', 'post_date_gmt' => '2026-11-01 09:00:00' ];
	$post( 841, 'publish', 1, 'Passat 2022' ); $post( 1429, 'trash' );
	$post( 10, 'publish', 1, 'Corolla' ); $post( 11, 'publish', 1, 'Corolla' ); $post( 12, 'draft', 1, 'Corolla' );
	$post( 2043, 'publish', 7, 'KTM Skoda' ); $post( 77, 'pending', 3, 'Loose' );
	$GLOBALS['__icl'] = [ 841 => [ 1, 'sk', '' ], 1428 => [ 1, 'uk', 'sk' ], 1429 => [ 1, 'ru', 'sk' ], 10 => [ 2, 'sk', '' ], 11 => [ 2, 'uk', 'sk' ], 12 => [ 2, 'en', 'sk' ], 2043 => [ 3, 'uk', '' ] ];
	Groups::$source = function () {
		$rows = [];
		foreach ( $GLOBALS['__icl'] as $id => $r ) {
			$p      = $GLOBALS['__posts'][ $id ] ?? null;
			$rows[] = [ 'tid' => $id, 'id' => $id, 'trid' => $r[0], 'lang' => $r[1], 'src' => $r[2], 'status' => $p['post_status'] ?? '', 'author' => (int) ( $p['post_author'] ?? 0 ), 'exists' => (bool) $p ];
		}
		foreach ( $GLOBALS['__posts'] as $id => $p ) {
			if ( ! isset( $GLOBALS['__icl'][ $id ] ) && 'car_listing' === $p['post_type'] ) {
				$rows[] = [ 'id' => $id, 'trid' => -$id, 'lang' => '', 'src' => '', 'status' => $p['post_status'], 'author' => (int) $p['post_author'], 'exists' => true ];
			}
		}
		return $rows;
	};

	echo "Groups\n";
	check( 'originals: one per group, in every language, loose listings kept', [ 10, 77, 841, 2043 ] === Groups::original_ids() );
	check( 'catalog UK: Passat shows its original (empty translation is gone)', [ 841 ] === Groups::for_visitors( [ 1428 ], 'uk' ) );
	check( 'catalog RU: trashed translation -> original', [ 841 ] === Groups::for_visitors( [ 1429 ], 'ru' ) );
	check( 'catalog EN: draft translation -> published original', [ 10 ] === Groups::for_visitors( [ 12 ], 'en' ) );
	check( 'catalog UK: published translation wins', [ 11 ] === Groups::for_visitors( [ 10 ], 'uk' ) );
	check( 'catalog: KTM (uk original) shown in SK', [ 2043 ] === Groups::for_visitors( [ 2043 ], 'sk' ) );
	check( 'catalog: unknown IDs kept', [ 999 ] === Groups::for_visitors( [ 999 ], 'sk' ) );
	check( 'broken links: rows without a post only (trash is not broken)', [ 1428 ] === array_column( Groups::broken(), 'id' ) );
	$GLOBALS['__icl'][-1] = [ 1, 'en', 'sk' ]; Groups::flush();
	$src = Groups::$source;
	Groups::$source = function () use ( $src ) { $rows = $src(); foreach ( $rows as &$r ) { if ( -1 === $r['id'] ) { $r['id'] = 0; $r['tid'] = 9001; } } return $rows; };
	Groups::flush();
	$r = Groups::for_visitors( [ 0, 10 ], 'en' ); sort( $r );
	check( 'catalog EN: empty WPML placeholder (core sends 0) -> every group, published copy', [ 10, 841, 2043 ] === $r );
	check( 'empty placeholder listed as broken', in_array( 9001, array_column( Groups::broken(), 'tid' ), true ) );
	unset( $GLOBALS['__icl'][-1] ); Groups::$source = $src; Groups::flush();
	check( 'original language', 'uk' === Groups::original_lang( 2043 ) && 'sk' === Groups::original_lang( 11 ) );

	echo "Scope\n";
	Scope::$force = 'manage';
	$q = new Fake_Query( [ 'post_type' => 'car_listing', 'author' => 7 ] );
	Scope::queries( $q );
	check( 'cabinet: all languages, originals only', [ 10, 77, 841, 2043 ] === $q->get( 'post__in' ) && 'all' === $q->get( 'lang' ) && true === $q->get( 'suppress_filters' ) );
	$q = new Fake_Query( [ 'post_type' => 'car_listing', 'post__in' => [ 11, 2043 ] ] );
	Scope::queries( $q );
	check( 'cabinet: an explicit post__in is narrowed, not widened', [ 2043 ] === $q->get( 'post__in' ) );
	$q = new Fake_Query( [ 'post_type' => 'post' ] );
	Scope::queries( $q );
	check( 'other post types untouched', '' === $q->get( 'post__in' ) );
	$q = new Fake_Query( [ 'post_type' => 'car_listing' ], true );
	Scope::queries( $q );
	check( 'main query untouched', '' === $q->get( 'post__in' ) );
	Scope::$force = '';
	$GLOBALS['__lang'] = 'uk';
	$q = new Fake_Query( [ 'post_type' => 'car_listing', 'lang' => 'all', 'post__in' => [ 1428, 10, 2043 ] ] );
	Scope::queries( $q );
	check( 'catalog UK: repaired picks', [ 841, 11, 2043 ] === $q->get( 'post__in' ) );
	$GLOBALS['__lang'] = 'sk';
	$sql = "SELECT p.post_status, COUNT(*) AS cnt FROM wp_posts p INNER JOIN wp_icl_translations t ON t.element_id = p.ID AND t.element_type = 'post_car_listing' WHERE p.post_type = 'car_listing' AND p.post_author = 7 AND p.post_status IN ('publish') AND t.language_code = 'sk' GROUP BY p.post_status";
	check( 'cabinet counters outside the cabinet: untouched', $sql === Scope::cabinet_counts( $sql ) );
	Scope::$force = 'manage';
	check( 'cabinet counters count originals', str_contains( Scope::cabinet_counts( $sql ), 't.source_language_code IS NULL' ) && ! str_contains( Scope::cabinet_counts( $sql ), "language_code = 'sk'" ) );
	Scope::$force = 'panel';
	$GLOBALS['__lang'] = 'uk';
	$switched = [];
	add_action( 'wpml_switch_language', function ( $l ) use ( &$switched ) { $switched[] = $l; $GLOBALS['__lang'] = $l; } );
	Scope::terms_restore_language( Scope::terms_main_language( [], [ 'city' ], [] ) );
	check( 'panel: city lists in SK, then back to UK', [ 'sk', 'uk' ] === $switched && 'uk' === $GLOBALS['__lang'] );
	$switched = [];
	Scope::terms_restore_language( Scope::terms_main_language( [], [ 'category' ], [] ) );
	check( 'panel: other taxonomies untouched', [] === $switched );
	Scope::$force = '';
	Scope::terms_restore_language( Scope::terms_main_language( [], [ 'city' ], [] ) );
	check( 'outside the panel: untouched', [] === $switched );
	$GLOBALS['__lang'] = 'sk';

	echo "Sitemap\n";
	$langs = [ 'sk', 'uk', 'en', 'ru' ];
	check( 'uk translation gets /uk/', 'https://www.taxibaza.sk/uk/avto/toyota-corolla-2021/' === Sitemap::in_lang( 'https://www.taxibaza.sk/avto/toyota-corolla-2021/', 'uk', 'sk', $langs ) );
	check( 'main language has no folder', 'https://www.taxibaza.sk/avto/x/' === Sitemap::in_lang( 'https://www.taxibaza.sk/ru/avto/x/', 'sk', 'sk', $langs ) );
	check( 'wrong folder replaced', 'https://www.taxibaza.sk/en/avto/x/' === Sitemap::in_lang( 'https://www.taxibaza.sk/uk/avto/x/', 'en', 'sk', $langs ) );
	check( 'post_url: uk post', 'https://www.taxibaza.sk/uk/avto/corolla/' === Sitemap::post_url( 'https://www.taxibaza.sk/avto/corolla/', get_post( 11 ) ) );
	check( 'post_url: sk post untouched', 'https://www.taxibaza.sk/avto/corolla/' === Sitemap::post_url( 'https://www.taxibaza.sk/avto/corolla/', get_post( 10 ) ) );

	echo "DeepL\n";
	update_option( DeepL::OPTION, [ 'on' => true, 'key' => 'abc:fx' ] );
	$calls = [];
	DeepL::$http = function ( $m, $url, $args ) use ( &$calls ) {
		$calls[] = [ $m, $url, json_decode( $args['body'] ?? '{}', true ), $args['headers']['Authorization'] ];
		$b = json_decode( $args['body'] ?? '{}', true );
		return [ 'code' => 200, 'body' => [ 'translations' => array_map( fn( $t ) => [ 'text' => '[' . $b['target_lang'] . '] ' . $t ], $b['text'] ?? [] ) ] ];
	};
	$tr = DeepL::translate( [ 'short_description' => 'Pekné auto', 'insurance_details' => '', 'maintenance_details' => 'Servis' ], 'sk', 'uk' );
	check( 'free key -> api-free host, auth header', str_starts_with( $calls[0][1], 'https://api-free.deepl.com/v2/translate' ) && 'DeepL-Auth-Key abc:fx' === $calls[0][3] );
	check( 'only non-empty texts are sent, keys kept', [ 'Pekné auto', 'Servis' ] === $calls[0][2]['text'] && 'UK' === $calls[0][2]['target_lang'] && 'SK' === $calls[0][2]['source_lang'] && '' === $tr['insurance_details'] && '[UK] Servis' === $tr['maintenance_details'] );
	check( 'pro key -> api host', 'https://api.deepl.com/v2/' === DeepL::base( 'abc' ) );

	echo "Auto translate\n";
	Auto_Translate::init();
	add_action( 'transition_post_status', [ 'TaxiRental\Core\Notifications', 'listing_status' ], 10, 3 );
	Groups::flush();
	$GLOBALS['__meta'][2043] = [ 'short_description' => 'Гарне авто', 'price_week' => '250', 'gallery' => [ 5, 6 ], '_edit_lock' => 'x', '_tb_listing_views' => 9, 'tbf_fleet_listing' => '1' ];
	$GLOBALS['__terms'][2043]['city'] = [ 40 ];
	check( 'backlog: published originals missing a language', in_array( 2043, Auto_Translate::backlog(), true ) && in_array( 841, Auto_Translate::backlog(), true ) );
	$GLOBALS['__posts'][2043]['post_status'] = 'pending';
	Groups::flush();
	$GLOBALS['__mails'] = 0;
	wp_update_post( [ 'ID' => 2043, 'post_status' => 'publish' ] ); // moderator publishes
	Groups::flush();
	$tr = Auto_Translate::translations( 2043 );
	check( 'publish creates sk/en/ru translations', [ 'en', 'ru', 'sk' ] === ( function ( $a ) { $k = array_keys( $a ); sort( $k ); return $k; } )( $tr ) );
	$sk = $tr['sk'] ?? 0;
	check( 'translation: description translated, rest copied, internals skipped', '[SK] Гарне авто' === get_post_meta( $sk, 'short_description', true ) && '250' === get_post_meta( $sk, 'price_week', true ) && [ 5, 6 ] === get_post_meta( $sk, 'gallery', true ) && '' === get_post_meta( $sk, '_edit_lock', true ) && '' === get_post_meta( $sk, '_tb_listing_views', true ) && '1' === get_post_meta( $sk, 'tbf_fleet_listing', true ) );
	check( 'translation: same title, author, slug, published', 'KTM Skoda' === get_post( $sk )->post_title && 7 === get_post( $sk )->post_author && 'ktmskoda' === get_post( $sk )->post_name && 'publish' === get_post_status( $sk ) );
	check( 'translation joined the WPML group', [ 3, 'sk', 'uk' ] === $GLOBALS['__icl'][ $sk ] );
	check( 'city term in the translation language', [ 40 ] === $GLOBALS['__terms'][ $sk ]['city'] );
	check( 'only one "published" e-mail (the original)', 1 === $GLOBALS['__mails'] );
	check( 'core e-mail hook is back afterwards', false !== has_action( 'transition_post_status', [ 'TaxiRental\Core\Notifications', 'listing_status' ] ) );

	$GLOBALS['__mails'] = 0;
	wp_update_post( [ 'ID' => 2043, 'post_status' => 'pending' ] ); // owner edits → moderation
	Groups::flush();
	check( 'original in moderation: translations hidden (draft)', 'draft' === get_post_status( $sk ) && get_post_meta( $sk, '_tbl_held', true ) );
	$GLOBALS['__meta'][2043]['short_description'] = 'Нове авто';
	wp_update_post( [ 'ID' => 2043, 'post_status' => 'publish' ] );
	Groups::flush();
	check( 'published again: translations back and refreshed', 'publish' === get_post_status( $sk ) && '[SK] Нове авто' === get_post_meta( $sk, 'short_description', true ) && '' === get_post_meta( $sk, '_tbl_held', true ) );
	check( 'no extra e-mails for translations', 1 === $GLOBALS['__mails'] );
	$en = $tr['en'];
	update_post_meta( $en, 'short_description', 'Hand-written EN' );
	$GLOBALS['__meta'][2043]['short_description'] = 'Ще новіше';
	wp_update_post( [ 'ID' => 2043, 'post_status' => 'pending' ] );
	wp_update_post( [ 'ID' => 2043, 'post_status' => 'publish' ] );
	check( 'a hand-edited translation is not overwritten', 'Hand-written EN' === get_post_meta( $en, 'short_description', true ) && '[SK] Ще новіше' === get_post_meta( $sk, 'short_description', true ) );
	check( 'existing human translation (Corolla UK) untouched by sync', [ 'created' => [ 'ru' ], 'updated' => [], 'failed' => [] ] === Auto_Translate::sync( 10 ) );

	DeepL::$http = fn() => [ 'code' => 456, 'body' => [ 'message' => 'Quota exceeded' ] ];
	$GLOBALS['__meta'][841]['short_description'] = 'Passat popis';
	check( 'DeepL failure -> reported, nothing created', [ 'created' => [], 'updated' => [], 'failed' => [ 'uk', 'en', 'ru' ] ] === Auto_Translate::sync( 841 ) && str_contains( (string) get_option( DeepL::LAST_ERROR )['message'], '456' ) );

	Groups::flush();
	wp_delete_post( 2043, true );
	Groups::flush();
	check( 'original deleted -> its machine translations deleted', ! get_post( $sk ) && ! get_post( $en ) && get_post( 11 ) );

	echo "\n" . $GLOBALS['__n'] . ' checks, ' . $GLOBALS['__fail'] . " failed\n";
	exit( $GLOBALS['__fail'] ? 1 : 0 );
}
