<?php
// phpcs:ignoreFile
// Renders the public fleet templates with WP stubs.
// Usage: php tests/render-fleets.php <catalog|single|single-own|empty> <lang> [f_city] [f_platform] [sort] [pg]
namespace {
	define( 'ABSPATH', __DIR__ . '/' ); define( 'MINUTE_IN_SECONDS', 60 ); define( 'HOUR_IN_SECONDS', 3600 ); define( 'MB_IN_BYTES', 1048576 );
	$mode = $argv[1] ?? 'catalog'; $GLOBALS['lang'] = $argv[2] ?? 'uk';
	$_GET = array_filter( [ 'f_city' => $argv[3] ?? '', 'f_platform' => $argv[4] ?? '', 'sort' => $argv[5] ?? '', 'pg' => $argv[6] ?? '' ] );
	$GLOBALS['__hooks'] = []; $GLOBALS['__meta'] = []; $GLOBALS['__users'] = []; $GLOBALS['__options'] = []; $GLOBALS['__qv'] = [];
	function add_action( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['__hooks'][ $h ][ $p ][] = [ $cb, $a ]; } function add_filter( $h, $cb, $p = 10, $a = 1 ) { add_action( $h, $cb, $p, $a ); }
	function do_action( ...$a ) {} function has_action( $h ) { return false; } function has_filter( $h ) { return false; }
	function apply_filters( $h, $v, ...$a ) { if ( 'wpml_current_language' === $h ) { return $GLOBALS['lang']; } if ( 'wpml_default_language' === $h ) { return 'sk'; } if ( 'document_title_separator' === $h ) { return '–'; } return $v; }
	function register_activation_hook() {} function __( $s ) { return $s; }
	function get_user_meta( $id, $k, $s = false ) { return $GLOBALS['__meta'][ $id ][ $k ] ?? ''; } function update_user_meta( $id, $k, $v ) { $GLOBALS['__meta'][ $id ][ $k ] = $v; }
	function get_option( $k, $d = false ) { return $GLOBALS['__options'][ $k ] ?? $d; } function update_option( $k, $v, $a = null ) { $GLOBALS['__options'][ $k ] = $v; }
	function get_users( $a ) { $o = []; foreach ( $GLOBALS['__users'] as $id => $r ) { if ( ! empty( $a['role'] ) && ! in_array( $a['role'], $r, true ) ) continue; if ( isset( $a['meta_key'] ) && ( $GLOBALS['__meta'][ $id ][ $a['meta_key'] ] ?? null ) !== $a['meta_value'] ) continue; $o[] = (string) $id; } return $o; }
	function get_userdata( $id ) { return isset( $GLOBALS['__users'][ $id ] ) ? (object) [ 'ID' => $id, 'roles' => $GLOBALS['__users'][ $id ], 'display_name' => 'Account ' . $id, 'user_email' => 'f' . $id . '@x.sk', 'user_registered' => '2026-0' . ( $id % 9 + 1 ) . '-01 10:00:00' ] : false; }
	function wp_get_current_user() { return (object) [ 'ID' => 0, 'display_name' => '', 'user_email' => '' ]; } function get_current_user_id() { return 0; }
	function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); } function sanitize_title( $s ) { return strtolower( preg_replace( '/[^a-z0-9\-]/i', '', $s ) ); } function wp_unslash( $s ) { return $s; } function absint( $v ) { return abs( (int) $v ); }
	function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } function esc_url( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
	function home_url( $p = '' ) { return 'https://www.taxibaza.sk' . ( 'sk' === $GLOBALS['lang'] ? '' : '/' . $GLOBALS['lang'] ) . $p; } function tb_url( $p ) { return home_url( $p ); }
	function add_query_arg( $k, $v = null, $u = '' ) { if ( is_array( $k ) ) { $u = (string) $v; return $u . ( str_contains( $u, '?' ) ? '&' : '?' ) . http_build_query( $k ); } return $u . ( str_contains( $u, '?' ) ? '&' : '?' ) . $k . '=' . $v; }
	function admin_url( $p = '' ) { return 'https://www.taxibaza.sk/wp-admin/' . $p; } function plugins_url( $p, $f ) { return 'https://www.taxibaza.sk/wp-content/plugins/taxibaza-fleets/' . $p; }
	function wp_nonce_field( $a = -1, $n = '_wpnonce', $r = true, $e = true ) { $h = '<input type="hidden" name="' . $n . '" value="nonce">'; if ( $e ) echo $h; return $h; }
	function get_query_var( $k ) { return $GLOBALS['__qv'][ $k ] ?? ''; } function is_wp_error( $x ) { return false; }
	function wp_get_attachment_image_url() { return ''; } function get_bloginfo() { return 'TaxiBaza.sk'; } function tb_legal_url( $w ) { return 'https://www.taxibaza.sk/' . $w . '/'; }
	$GLOBALS['__city'] = [ 11 => (object) [ 'term_id' => 11, 'name' => 'Братислава', 'slug' => 'bratislava' ], 12 => (object) [ 'term_id' => 12, 'name' => 'Жиліна', 'slug' => 'zilina' ], 13 => (object) [ 'term_id' => 13, 'name' => 'Трнава', 'slug' => 'trnava' ] ];
	function get_terms( $a ) { return array_values( $GLOBALS['__city'] ); } function get_term( $id ) { return $GLOBALS['__city'][ $id ] ?? null; }
	$GLOBALS['__posts'] = []; function get_post_field( $f, $id ) { return $GLOBALS['__posts'][ $id ][ $f ] ?? ''; } function get_post_meta( $id, $k, $s = false ) { return ''; }
	function get_posts( $a ) { $o = []; foreach ( $GLOBALS['__posts'] as $id => $p ) { if ( (int) $p['post_author'] === (int) $a['author'] ) $o[] = $id; } return $o; }
	function wp_get_object_terms( $ids, $tax ) { $o = []; foreach ( (array) $ids as $id ) { $o[] = $GLOBALS['__city'][ $GLOBALS['__posts'][ $id ]['city'] ]; } return $o; }
	function get_template_part( $slug ) { echo "\n<!-- theme:$slug -->" . ( str_contains( $slug, 'card-car' ) ? '<article class="tb-car-card">car ' . $GLOBALS['__cur'] . '</article>' : '<section class="tb-cta-tg">CTA</section>' ) . "\n"; }
	class WP_Query { public $ids; public $i = 0; function __construct( $a ) { $this->ids = $a['post__in']; } function have_posts() { return $this->i < count( $this->ids ); } function the_post() { $GLOBALS['__cur'] = $this->ids[ $this->i++ ]; } }
	function wp_reset_postdata() {} function status_header( $c ) {} function nocache_headers() {} function wp_safe_redirect( $u ) { echo 'REDIRECT ' . $u; exit; }
	function get_header() { echo '<!doctype html><html lang="' . $GLOBALS['lang'] . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( \TaxiBaza\Fleets\Fleet_Pages::title() ) . '</title><link rel="stylesheet" href="tokens.css"><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="../assets/fleets.css"></head><body><header class="tb-header">HEADER</header>'; }
	function get_footer() { echo '<footer>FOOTER</footer><script src="../assets/fleets.js"></script></body></html>'; }

	require dirname( __DIR__ ) . '/taxibaza-fleets.php';

	$base = [ 'phone' => '+421 902 123 456', 'ico' => '54 123 456', 'telegram' => 'gold_fleet', 'whatsapp' => '+421902123456', 'description' => "Надійна флотилія з чесними умовами для нових і досвідчених водіїв.\nПрацюємо в Братиславі та Трнаві з 2019 року.", 'logo_id' => 0, 'platforms' => [ 'bolt', 'uber', 'hopin' ], 'langs' => [ 'sk', 'uk', 'en' ], 'models' => [ 'own', 'fleet' ], 'own_pay_type' => 'percent', 'own_fee' => '8', 'own_fee_base' => 'net', 'rent_price' => '210', 'rent_period' => 'week', 'rent_comm_on' => true, 'rent_comm' => '8', 'rent_dep_on' => true, 'rent_dep' => '200', 'rent_dep_return' => 'handover', 'rent_min' => '14', 'rent_min_unit' => 'days', 'rent_ext' => 'weekly', 'inc' => [ 'insurance', 'service' ], 'pay_freq' => 'weekly', 'pay_day' => 'mon', 'pay_method' => [ 'bank' ], 'perk_fuel' => true, 'fuel_discount' => '4', 'fuel_unit' => 'cents', 'fuel_network' => '', 'fuel_for' => [ 'own' ], 'perk_service' => true, 'svc_discount' => '15', 'svc_where' => 'partner', 'svc_what' => [ 'service', 'tires' ], 'svc_for' => [ 'own' ], 'perk_concession' => true, 'con_what' => 'do', 'con_cost' => 'free', 'con_for' => [ 'own' ], 'req_age' => '21', 'req_exp' => '2', 'docs' => [ 'id', 'license', 'record' ] ];
	$fleets = [
		21 => [ 'Gold Fleet s.r.o.', [], 11 ],
		22 => [ 'DriveRent Partner s.r.o.', [ 'models' => [ 'fleet' ], 'platforms' => [ 'bolt', 'uber' ], 'rent_comm_on' => false, 'rent_price' => '25', 'langs' => [ 'sk', 'en' ] ], 11 ],
		23 => [ 'North Fleet s.r.o.', [ 'own_fee' => '7', 'platforms' => [ 'bolt', 'uber' ] ], 12 ],
		24 => [ 'RideDrive Nitra', [ 'models' => [ 'own' ], 'own_pay_type' => 'fixed', 'own_fee' => '20', 'platforms' => [ 'uber', 'hopin' ], 'perk_fuel' => false, 'description' => '' ], 13 ],
		25 => [ 'Hidden Blocked', [], 11 ],
	];
	$pid = 500;
	foreach ( $fleets as $id => [ $name, $over, $city ] ) {
		$GLOBALS['__users'][ $id ] = [ 'fleet' ];
		$GLOBALS['__meta'][ $id ] = [ 'tbf_status' => 25 === $id ? 'blocked' : 'approved', 'tbf_profile_live' => array_merge( $base, [ 'name' => $name ], $over ) ];
		for ( $i = 0; $i < ( 21 === $id ? 6 : 2 ); $i++ ) { $GLOBALS['__posts'][ ++$pid ] = [ 'post_author' => $id, 'city' => $i % 2 ? 13 : $city ]; }
	}
	if ( 'empty' === $mode ) { foreach ( $fleets as $id => $f ) { $GLOBALS['__meta'][ $id ]['tbf_status'] = 'pending'; } }
	if ( 0 === strpos( $mode, 'single' ) ) {
		$fid = 'single-own' === $mode ? 24 : 21;
		$GLOBALS['__qv'] = [ 'tbf_fleet' => \TaxiBaza\Fleets\Directory::ensure_slug( $fid ) ];
		\TaxiBaza\Fleets\Fleet_Pages::route();
		require dirname( __DIR__ ) . '/templates/fleet-single.php';
		return;
	}
	$GLOBALS['__qv'] = [ 'tbf_fleets' => '1' ];
	require dirname( __DIR__ ) . '/templates/fleets-catalog.php';
}
