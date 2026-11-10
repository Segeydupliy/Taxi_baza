<?php
/**
 * Dependency-free test runner for taxibaza-fleets logic.
 * Stubs the small part of the WordPress API the plugin uses.
 *
 * Run: php wp-content/plugins/taxibaza-fleets/tests/run.php
 */

// phpcs:ignoreFile

namespace {
	define( 'ABSPATH', __DIR__ . '/' );

	// ---- tiny WP stub state ----
	$GLOBALS['__hooks']   = [];
	$GLOBALS['__meta']    = [];
	$GLOBALS['__users']   = []; // id => roles[]
	$GLOBALS['__options'] = [];
	$GLOBALS['__env']     = [ 'admin' => false, 'ajax' => false, 'tpl' => '', 'did_wp' => 1, 'uid' => 0, 'caps' => [], 'singular' => null ];
	$GLOBALS['__ajax_out'] = null;

	class WP_Ajax_Exit extends \Exception {}
	class WP_Redirect_Exit extends \Exception {}

	function add_action( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['__hooks'][ $h ][ $p ][] = [ $cb, $a ]; }
	function add_filter( $h, $cb, $p = 10, $a = 1 ) { add_action( $h, $cb, $p, $a ); }
	function do_action( $h, ...$args ) {
		if ( empty( $GLOBALS['__hooks'][ $h ] ) ) { return; }
		ksort( $GLOBALS['__hooks'][ $h ] );
		foreach ( $GLOBALS['__hooks'][ $h ] as $list ) { foreach ( $list as [ $cb, $n ] ) { call_user_func_array( $cb, array_slice( $args, 0, max( 1, $n ) ) ); } }
	}
	function apply_filters( $h, $v, ...$args ) {
		if ( empty( $GLOBALS['__hooks'][ $h ] ) ) { return $v; }
		ksort( $GLOBALS['__hooks'][ $h ] );
		foreach ( $GLOBALS['__hooks'][ $h ] as $list ) { foreach ( $list as [ $cb, $n ] ) { $v = call_user_func_array( $cb, array_merge( [ $v ], array_slice( $args, 0, max( 0, $n - 1 ) ) ) ); } }
		return $v;
	}
	function did_action( $h ) { return 'wp' === $h ? $GLOBALS['__env']['did_wp'] : 0; }
	function register_activation_hook( $f, $cb ) {}
	function __( $s, $d = '' ) { return $s; }
	function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); }
	function sanitize_html_class( $s ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', $s ); }
	function wp_unslash( $s ) { return $s; }
	function get_user_meta( $id, $k, $single = false ) { return $GLOBALS['__meta'][ $id ][ $k ] ?? ''; }
	function update_user_meta( $id, $k, $v ) { $GLOBALS['__meta'][ $id ][ $k ] = $v; return true; }
	$GLOBALS['__posts'] = []; $GLOBALS['__pmeta'] = [];
	function get_post_field( $f, $id ) { return $GLOBALS['__posts'][ $id ][ $f ] ?? ''; }
	function get_post_meta( $id, $k, $single = false ) { return $GLOBALS['__pmeta'][ $id ][ $k ] ?? ''; }
	function update_post_meta( $id, $k, $v ) { $GLOBALS['__pmeta'][ $id ][ $k ] = $v; return true; }
	function delete_user_meta( $id, $k ) { unset( $GLOBALS['__meta'][ $id ][ $k ] ); return true; }
	function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['__options'] ) ? $GLOBALS['__options'][ $k ] : $d; }
	function update_option( $k, $v, $al = null ) { $GLOBALS['__options'][ $k ] = $v; return true; }
	function get_users( $args ) {
		$out = [];
		foreach ( $GLOBALS['__users'] as $id => $roles ) {
			if ( ! empty( $args['role'] ) && ! in_array( $args['role'], $roles, true ) ) { continue; }
			if ( isset( $args['meta_key'] ) && ( $GLOBALS['__meta'][ $id ][ $args['meta_key'] ] ?? null ) !== $args['meta_value'] ) { continue; }
			$out[] = (string) $id;
		}
		return isset( $args['number'] ) ? array_slice( $out, 0, (int) $args['number'] ) : $out;
	}
	function get_userdata( $id ) { return isset( $GLOBALS['__users'][ $id ] ) ? (object) [ 'ID' => $id, 'roles' => $GLOBALS['__users'][ $id ], 'user_email' => 'u' . $id . '@test.sk', 'display_name' => 'User ' . $id ] : false; }
	if ( ! function_exists( 'esc_html' ) ) { function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } }
	if ( ! function_exists( 'esc_attr' ) ) { function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } }
	if ( ! function_exists( 'esc_url' ) ) { function esc_url( $s ) { return (string) $s; } }
	function wp_mail( $to, $subj, $body, $h = [] ) { $GLOBALS['__mail'][] = compact( 'to', 'subj', 'body' ); return true; }
	function is_admin() { return $GLOBALS['__env']['admin'] || $GLOBALS['__env']['ajax']; }
	function wp_doing_ajax() { return $GLOBALS['__env']['ajax']; }
	function is_page_template( $t ) { return $GLOBALS['__env']['tpl'] === $t; }
	function get_current_user_id() { return $GLOBALS['__env']['uid']; }
	function current_user_can( $c ) { return in_array( $c, $GLOBALS['__env']['caps'], true ); }
	function is_singular( $t = '' ) { return null !== $GLOBALS['__env']['singular']; }
	function get_queried_object() { return $GLOBALS['__env']['singular']; }
	function status_header( $c ) { $GLOBALS['__status'] = $c; }
	function nocache_headers() {}
	function wp_send_json_error( $d = null, $c = null ) { $GLOBALS['__ajax_out'] = [ 'error', $d, $c ]; throw new WP_Ajax_Exit(); }
	function wp_safe_redirect( $u ) { $GLOBALS['__redirect'] = $u; throw new WP_Redirect_Exit(); }
	function add_query_arg( $k, $v = null, $u = '' ) { if ( is_array( $k ) ) { $u = (string) $v; return $u . ( str_contains( $u, '?' ) ? '&' : '?' ) . http_build_query( $k ); } return $u . ( str_contains( $u, '?' ) ? '&' : '?' ) . $k . '=' . $v; }
	function home_url( $p = '' ) { return 'https://example.test' . $p; }

	function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
	function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
	function absint( $v ) { return abs( (int) $v ); }
	function has_action( $h ) { return ! empty( $GLOBALS['__hooks'][ $h ] ); }
	function wp_generate_uuid4() { return bin2hex( random_bytes( 8 ) ); }
	function wp_update_user( $a ) { $GLOBALS['__updated_user'] = $a; return $a['ID']; }

	// Simulates WP_User::set_role firing set_user_role.
	function test_set_role( $id, $role ) {
		$old = $GLOBALS['__users'][ $id ] ?? [];
		$GLOBALS['__users'][ $id ] = [ $role ];
		do_action( 'set_user_role', $id, $role, $old );
	}

	function remove_all_wpml_permalink() { unset( $GLOBALS['__hooks']['wpml_permalink'] ); }
	class WP_User { public $ID; function __construct( $id ) { $this->ID = $id; } function set_role( $r ) { test_set_role( $this->ID, $r ); } }
	class Fake_Query {
		public array $q; public bool $main; public bool $single;
		function __construct( array $q, bool $main = false, bool $single = false ) { $this->q = $q; $this->main = $main; $this->single = $single; }
		function get( $k ) { return $this->q[ $k ] ?? ''; }
		function set( $k, $v ) { $this->q[ $k ] = $v; }
		function is_main_query() { return $this->main; }
		function is_singular() { return $this->single; }
		function set_404() { $GLOBALS['__is404'] = true; }
	}

	require dirname( __DIR__ ) . '/taxibaza-fleets.php';
	do_action( 'plugins_loaded' );
}

namespace TaxiBaza\Fleets\Tests {
	use TaxiBaza\Fleets\Status;
	use TaxiBaza\Fleets\Visibility;
	use TaxiBaza\Fleets\Lock;

	$fails = 0; $n = 0;
	function check( string $name, $cond ) { global $fails, $n; $n++; if ( $cond ) { echo "  ok  $name\n"; } else { $fails++; echo "FAIL  $name\n"; } }
	function reset_env() { $GLOBALS['wp_query'] = new \Fake_Query( [] ); $GLOBALS['__env'] = [ 'admin' => false, 'ajax' => false, 'tpl' => '', 'did_wp' => 1, 'uid' => 0, 'caps' => [], 'singular' => null ]; $_REQUEST = []; $GLOBALS['__is404'] = false; }

	// Fixture: 1 admin, 2 car owner, 3 legacy fleet (before plugin), 4 owner who becomes fleet.
	$GLOBALS['__users'] = [ 1 => [ 'administrator' ], 2 => [ 'car_owner' ], 3 => [ 'fleet' ], 4 => [ 'car_owner' ] ];

	echo "Activation\n";
	Status::on_activate();
	check( 'legacy fleet grandfathered as approved', Status::APPROVED === Status::get( 3 ) );
	check( 'no hidden authors after activation', [] === Status::hidden_authors() );
	check( 'car owner has no fleet status', '' === Status::get( 2 ) );

	echo "Role switch (owner -> fleet via cabinet)\n";
	\test_set_role( 4, 'fleet' );
	check( 'new fleet gets draft', Status::DRAFT === Status::get( 4 ) );
	check( 'draft fleet is hidden', [ 4 ] === Status::hidden_authors() );
	check( 'draft fleet cannot add listings', ! Status::can_add_listings( 4 ) );
	\test_set_role( 4, 'fleet' ); // core re-posts account_type on every profile save
	check( 're-applying fleet role keeps status', Status::DRAFT === Status::get( 4 ) );

	echo "Transitions\n";
	check( 'draft -> approved not allowed', false === Status::set( 4, Status::APPROVED ) );
	check( 'draft -> pending', Status::set( 4, Status::PENDING ) && Status::PENDING === Status::get( 4 ) );
	check( 'pending can add listings', Status::can_add_listings( 4 ) );
	check( 'pending still hidden', in_array( 4, Status::hidden_authors(), true ) );
	$events = [];
	add_action( 'tbf_status_changed', function ( $id, $to, $from ) use ( &$events ) { $events[] = "$id:$from>$to"; }, 10, 3 );
	check( 'pending -> approved', Status::set( 4, Status::APPROVED ) );
	check( 'approved fleet visible', ! in_array( 4, Status::hidden_authors(), true ) );
	check( 'status_changed event fired', [ '4:pending>approved' ] === $events );
	check( 'approved -> blocked', Status::set( 4, Status::BLOCKED ) && in_array( 4, Status::hidden_authors(), true ) );
	check( 'blocked cannot add listings', ! Status::can_add_listings( 4 ) );
	check( 'blocked -> approved (unblock)', Status::set( 4, Status::APPROVED ) && ! in_array( 4, Status::hidden_authors(), true ) );
	check( 'unknown status rejected', false === Status::set( 4, 'foo' ) );

	echo "Leaving fleet role\n";
	Status::set( 4, Status::BLOCKED );
	\test_set_role( 4, 'car_owner' );
	check( 'status cleared', '' === Status::get( 4 ) );
	check( 'not hidden as car owner', ! in_array( 4, Status::hidden_authors(), true ) );
	\test_set_role( 4, 'fleet' );
	check( 'coming back starts from draft', Status::DRAFT === Status::get( 4 ) );

	echo "Visibility: which queries are filtered\n";
	reset_env();
	$q = new \Fake_Query( [ 'post_type' => 'car_listing', 'post_status' => 'publish' ] );
	Visibility::filter_query( $q );
	check( 'catalog query gets author__not_in', [ 4 ] === $q->get( 'author__not_in' ) );
	$q = new \Fake_Query( [ 'post_type' => 'car_listing', 'post_status' => 'publish', 'author__not_in' => [ 99 ] ] );
	Visibility::filter_query( $q );
	check( 'existing author__not_in is merged', [ 99, 4 ] === $q->get( 'author__not_in' ) );
	$q = new \Fake_Query( [ 'post_type' => 'car_listing', 'post_status' => 'publish' ] );
	$GLOBALS['__options']['tbf_hidden_authors'] = [];
	Visibility::filter_query( $q );
	check( 'no hidden fleets: query untouched', '' === $q->get( 'author__not_in' ) );
	Status::refresh_hidden_authors();
	$q = new \Fake_Query( [ 'post_type' => 'car_listing', 'post_status' => 'publish', 'author' => 4 ] );
	Visibility::filter_query( $q );
	check( 'cabinet (author) query untouched', '' === $q->get( 'author__not_in' ) );
	$q = new \Fake_Query( [ 'post_type' => 'car_listing', 'post_status' => [ 'publish', 'pending', 'draft' ] ] );
	Visibility::filter_query( $q );
	check( 'multi-status admin list untouched', '' === $q->get( 'author__not_in' ) );
	$q = new \Fake_Query( [ 'post_type' => 'post', 'post_status' => 'publish' ] );
	Visibility::filter_query( $q );
	check( 'blog posts untouched', '' === $q->get( 'author__not_in' ) );
	$GLOBALS['__env']['ajax'] = true; $_REQUEST['action'] = 'tb_filter_listings';
	$q = new \Fake_Query( [ 'post_type' => 'car_listing', 'post_status' => 'publish' ] );
	Visibility::filter_query( $q );
	check( 'catalog AJAX filtered', [ 4 ] === $q->get( 'author__not_in' ) );
	$_REQUEST['action'] = 'tb_fa_filter_listings';
	$q = new \Fake_Query( [ 'post_type' => 'car_listing', 'post_status' => 'publish' ] );
	Visibility::filter_query( $q );
	check( '/panel/ AJAX untouched', '' === $q->get( 'author__not_in' ) );
	reset_env(); $GLOBALS['__env']['admin'] = true;
	$q = new \Fake_Query( [ 'post_type' => 'car_listing', 'post_status' => 'publish' ] );
	Visibility::filter_query( $q );
	check( 'wp-admin untouched', '' === $q->get( 'author__not_in' ) );
	reset_env(); $GLOBALS['__env']['tpl'] = 'page-front-admin.php';
	$q = new \Fake_Query( [ 'post_type' => 'car_listing', 'post_status' => 'publish' ] );
	Visibility::filter_query( $q );
	check( '/panel/ server-rendered tab untouched', '' === $q->get( 'author__not_in' ) );

	echo "Visibility: single listing page\n";
	reset_env(); $GLOBALS['__env']['singular'] = (object) [ 'post_author' => 4 ];
	Visibility::guard_single();
	check( 'guest gets 404 for hidden fleet listing', true === $GLOBALS['__is404'] && 404 === $GLOBALS['__status'] );
	reset_env(); $GLOBALS['__env']['singular'] = (object) [ 'post_author' => 4 ]; $GLOBALS['__env']['uid'] = 4;
	Visibility::guard_single();
	check( 'author can preview own listing', false === $GLOBALS['__is404'] );
	reset_env(); $GLOBALS['__env']['singular'] = (object) [ 'post_author' => 4 ]; $GLOBALS['__env']['caps'] = [ 'manage_options' ];
	Visibility::guard_single();
	check( 'admin can see it', false === $GLOBALS['__is404'] );
	reset_env(); $GLOBALS['__env']['singular'] = (object) [ 'post_author' => 3 ];
	Visibility::guard_single();
	check( 'approved fleet listing visible', false === $GLOBALS['__is404'] );
	reset_env(); $GLOBALS['__env']['singular'] = (object) [ 'post_author' => 2 ];
	Visibility::guard_single();
	check( 'car owner listing visible', false === $GLOBALS['__is404'] );

	echo "Lock\n";
	reset_env(); $GLOBALS['__env']['uid'] = 4;
	try { Lock::guard_ajax_submit(); $blocked = false; } catch ( \WP_Ajax_Exit $e ) { $blocked = true; }
	check( 'draft fleet AJAX submit rejected with 403', $blocked && 403 === $GLOBALS['__ajax_out'][2] );
	try { $GLOBALS['__env']['tpl'] = 'page-add-listing.php'; Lock::guard_add_listing_page(); $r = false; } catch ( \WP_Redirect_Exit $e ) { $r = true; }
	check( 'draft fleet redirected from add-listing page', $r && str_contains( $GLOBALS['__redirect'], 'tab=profile' ) );
	check( 'body class tbf-locked', in_array( 'tbf-locked', Lock::body_class( [] ), true ) );
	Status::set( 4, Status::PENDING );
	try { Lock::guard_ajax_submit(); $blocked = false; } catch ( \WP_Ajax_Exit $e ) { $blocked = true; }
	check( 'pending fleet may submit listings', ! $blocked );
	reset_env(); $GLOBALS['__env']['uid'] = 2;
	try { Lock::guard_ajax_submit(); $blocked = false; } catch ( \WP_Ajax_Exit $e ) { $blocked = true; }
	check( 'car owner never locked', ! $blocked );


	echo "Profile: sanitize + validate\n";
	$raw = [ 'name' => '  <b>Gold Fleet</b> s.r.o. ', 'phone' => '+421 902 123 456<script>', 'ico' => '54 123 456x', 'telegram' => '@gold_fleet', 'platforms' => [ 'uber', 'evil' ], 'langs' => [ 'sk', 'uk' ], 'models' => [ 'own', 'fleet' ],
		'own_pay_type' => 'percent', 'own_fee' => '8,5', 'rent_price' => '210', 'rent_period' => 'week', 'rent_comm_on' => '1', 'rent_comm' => '8', 'rent_dep_on' => '', 'rent_min' => '14', 'rent_min_unit' => 'days',
		'pay_freq' => 'weekly', 'pay_day' => 'xxx', 'pay_method' => [ 'bank' ], 'perk_fuel' => '1', 'fuel_discount' => '4', 'docs' => [ 'id' ], 'req_age' => '99' ];
	$p = \TaxiBaza\Fleets\Profile::sanitize( $raw );
	check( 'html stripped from name', 'Gold Fleet s.r.o.' === $p['name'] );
	check( 'phone keeps digits/+/spaces only', '+421 902 123 456' === $p['phone'] );
	check( 'unknown platform dropped', [ 'uber' ] === $p['platforms'] );
	check( 'decimal comma parsed', '8.5' === $p['own_fee'] );
	check( 'invalid choice falls back to first allowed', 'mon' === $p['pay_day'] && '18' === $p['req_age'] );
	check( 'telegram @ stripped', 'gold_fleet' === $p['telegram'] );
	check( 'valid profile has no errors', [] === \TaxiBaza\Fleets\Profile::validate( $p ) );
	$bad = \TaxiBaza\Fleets\Profile::validate( array_merge( $p, [ 'name' => 'G', 'own_fee' => '150', 'models' => [ 'own' ], 'docs' => [] ] ) );
	check( 'errors: name, fee>100; documents no longer required', isset( $bad['name'], $bad['own_fee'] ) && ! isset( $bad['docs'] ) );
	$ok2 = \TaxiBaza\Fleets\Profile::validate( array_merge( $p, [ 'models' => [ 'fleet' ], 'rent_price' => '', 'rent_min' => '', 'perk_fuel' => true, 'fuel_discount' => '', 'perk_service' => true, 'svc_discount' => '', 'svc_what' => [] ] ) );
	check( 'fleet cars without rent fields, perks without discounts: valid', [] === $ok2 );
	check( 'German is a communication language', in_array( 'de', \TaxiBaza\Fleets\Profile::CHOICES['langs'], true ) );
	$bad = \TaxiBaza\Fleets\Profile::validate( array_merge( $p, [ 'models' => [ 'own' ], 'rent_price' => '' ] ) );
	check( 'rent fields not required without fleet model', ! isset( $bad['rent_price'] ) );

	echo "Profile: moderation flow\n";
	$GLOBALS['__users'][5] = [ 'car_owner' ];
	\test_set_role( 5, 'fleet' );
	check( 'new fleet draft', \TaxiBaza\Fleets\Status::DRAFT === \TaxiBaza\Fleets\Status::get( 5 ) );
	check( 'submit from draft -> submitted', 'submitted' === \TaxiBaza\Fleets\Profile::submit( 5, $p ) );
	check( 'status pending after submit', \TaxiBaza\Fleets\Status::PENDING === \TaxiBaza\Fleets\Status::get( 5 ) );
	check( 'nothing live yet', [] === \TaxiBaza\Fleets\Profile::get_live( 5 ) );
	check( 'approve', \TaxiBaza\Fleets\Profile::approve( 5 ) );
	check( 'live = submitted, draft cleared', 'Gold Fleet s.r.o.' === \TaxiBaza\Fleets\Profile::get_live( 5 )['name'] && [] === \TaxiBaza\Fleets\Profile::get_draft( 5 ) );
	check( 'core meta synced (phone, company_name)', '+421 902 123 456' === get_user_meta( 5, 'phone' ) && 'Gold Fleet s.r.o.' === get_user_meta( 5, 'company_name' ) );
	check( 'approved fleet visible', ! in_array( 5, \TaxiBaza\Fleets\Status::hidden_authors(), true ) );
	$p2 = array_merge( $p, [ 'name' => 'Gold Fleet NEW' ] );
	check( 'edit of approved -> edits', 'edits' === \TaxiBaza\Fleets\Profile::submit( 5, $p2 ) );
	check( 'still approved & visible during edits', \TaxiBaza\Fleets\Status::APPROVED === \TaxiBaza\Fleets\Status::get( 5 ) && ! in_array( 5, \TaxiBaza\Fleets\Status::hidden_authors(), true ) );
	check( 'live keeps old name until approval', 'Gold Fleet s.r.o.' === \TaxiBaza\Fleets\Profile::get_live( 5 )['name'] );
	check( 'pending edits flag set', \TaxiBaza\Fleets\Profile::has_pending_edits( 5 ) );
	\TaxiBaza\Fleets\Profile::approve( 5 );
	check( 'after approval live has new name, flag cleared', 'Gold Fleet NEW' === \TaxiBaza\Fleets\Profile::get_live( 5 )['name'] && ! \TaxiBaza\Fleets\Profile::has_pending_edits( 5 ) );
	\TaxiBaza\Fleets\Profile::submit( 5, array_merge( $p, [ 'name' => 'Rejected name' ] ) );
	\TaxiBaza\Fleets\Profile::reject_edits( 5 );
	check( 'rejecting edits keeps live version', 'Gold Fleet NEW' === \TaxiBaza\Fleets\Profile::get_live( 5 )['name'] && [] === \TaxiBaza\Fleets\Profile::get_draft( 5 ) );
	\TaxiBaza\Fleets\Status::set( 5, \TaxiBaza\Fleets\Status::BLOCKED );
	check( 'blocked fleet cannot edit profile', ! \TaxiBaza\Fleets\Profile::can_edit( 5 ) );

	echo "I18n\n";
	check( 'slovak by default', 'Uložiť' === \TaxiBaza\Fleets\t( 'save' ) );
	add_filter( 'wpml_current_language', fn() => 'uk' );
	check( 'ukrainian when WPML says uk', 'Зберегти' === \TaxiBaza\Fleets\t( 'save' ) );
	add_filter( 'wpml_current_language', fn() => 'en' );
	check( 'english when WPML says en', 'Save' === \TaxiBaza\Fleets\t( 'save' ) );
	add_filter( 'wpml_current_language', fn() => 'ru' );
	check( 'russian when WPML says ru', 'Сохранить' === \TaxiBaza\Fleets\t( 'save' ) );
	add_filter( 'wpml_current_language', fn() => 'de' );
	check( 'unknown language falls back to slovak', 'Uložiť' === \TaxiBaza\Fleets\t( 'save' ) );
	$tbl  = require dirname( __DIR__ ) . '/includes/strings.php';
	$bad  = [];
	foreach ( $tbl as $k => $row ) {
		preg_match_all( '/%(?:\d\$)?[sd]/', $row[0], $m0 );
		sort( $m0[0] );
		for ( $i = 0; $i < 4; $i++ ) {
			preg_match_all( '/%(?:\d\$)?[sd]/', $row[ $i ] ?? '', $mi );
			sort( $mi[0] );
			if ( '' === trim( $row[ $i ] ?? '' ) || $mi[0] !== $m0[0] ) { $bad[] = "$k#$i"; }
		}
	}
	check( 'all strings have 4 languages with matching placeholders (' . count( $tbl ) . ')', [] === $bad );
	if ( $bad ) { echo '    ', implode( ', ', $bad ), "\n"; }
	add_filter( 'wpml_current_language', fn() => 'uk' );
	add_filter( 'wpml_translate_single_string', fn( $v, $c = '', $k = '', $l = '' ) => 'save' === $k ? 'Зберегти зміни' : $v, 10, 4 );
	check( 'WPML override wins', 'Зберегти зміни' === \TaxiBaza\Fleets\t( 'save' ) );


	echo "Admin panel: actions\n";
	$AP='\TaxiBaza\Fleets\Admin_Panel'; $S='\TaxiBaza\Fleets\Status'; $P='\TaxiBaza\Fleets\Profile';
	$GLOBALS['__users'][8] = [ 'car_owner' ]; \test_set_role( 8, 'fleet' );
	check( 'draft fleet: only block button', [ 'block' ] === $AP::actions_for( 8 ) );
	$P::submit( 8, $p );
	check( 'pending fleet: reject + approve', [ 'reject', 'approve' ] === $AP::actions_for( 8 ) );
	check( 'reject pending', $AP::apply( 8, 'reject' ) && $S::REJECTED === $S::get( 8 ) && in_array( 8, $S::hidden_authors(), true ) );
	check( 'rejected: approve + block', [ 'approve', 'block' ] === $AP::actions_for( 8 ) );
	check( 'approve rejected (draft kept) -> approved + live', $AP::apply( 8, 'approve' ) && $S::APPROVED === $S::get( 8 ) && 'Gold Fleet s.r.o.' === $P::get_live( 8 )['name'] );
	check( 'approved: only block', [ 'block' ] === $AP::actions_for( 8 ) );
	$P::submit( 8, array_merge( $p, [ 'name' => 'Edited' ] ) );
	check( 'approved with edits: reject_edits + approve + block', [ 'reject_edits', 'approve', 'block' ] === $AP::actions_for( 8 ) );
	check( 'grouped puts edits fleet into pending', in_array( 8, $AP::grouped()['pending'], true ) && in_array( 8, $AP::grouped()['approved'], true ) );
	check( 'block', $AP::apply( 8, 'block' ) && $S::BLOCKED === $S::get( 8 ) && in_array( 8, $S::hidden_authors(), true ) );
	check( 'blocked: only unblock', [ 'unblock' ] === $AP::actions_for( 8 ) );
	check( 'unblock with live profile -> approved', $AP::apply( 8, 'unblock' ) && $S::APPROVED === $S::get( 8 ) && ! in_array( 8, $S::hidden_authors(), true ) );
	$GLOBALS['__users'][9] = [ 'car_owner' ]; \test_set_role( 9, 'fleet' ); $AP::apply( 9, 'block' );
	check( 'unblock without live profile -> pending', $AP::apply( 9, 'unblock' ) && $S::PENDING === $S::get( 9 ) );
	check( 'unknown action rejected', false === $AP::apply( 9, 'nuke' ) );


	echo "Registration with account type\n";
	$_POST = [ 'tb_action' => 'register', 'tbf_account_type' => 'fleet' ];
	$GLOBALS['__users'][20] = [ 'subscriber' ];
	\test_set_role( 20, 'car_owner' );                       // what core does
	update_user_meta( 20, 'account_type', 'car_owner' );     // core writes meta after set_role
	do_action( 'updated_user_meta', 1, 20, 'account_type', 'car_owner' );
	check( 'chosen fleet -> role fleet', [ 'fleet' ] === $GLOBALS['__users'][20] );
	check( 'fleet starts as draft', \TaxiBaza\Fleets\Status::DRAFT === \TaxiBaza\Fleets\Status::get( 20 ) );
	check( 'account_type meta kept as fleet', 'fleet' === get_user_meta( 20, 'account_type' ) );
	$_POST = [ 'tb_action' => 'register', 'tbf_account_type' => 'car_owner' ];
	$GLOBALS['__users'][21] = [ 'subscriber' ];
	\test_set_role( 21, 'car_owner' );
	check( 'chosen owner stays car_owner', [ 'car_owner' ] === $GLOBALS['__users'][21] && '' === \TaxiBaza\Fleets\Status::get( 21 ) );
	$_POST = [ 'tb_action' => 'update_profile', 'tbf_account_type' => 'fleet' ];
	$GLOBALS['__users'][22] = [ 'subscriber' ];
	\test_set_role( 22, 'car_owner' );
	check( 'field ignored outside registration', [ 'car_owner' ] === $GLOBALS['__users'][22] );
	$_POST = [];


	echo "Notifications\n";
	$N='\TaxiBaza\Fleets\Notifications'; $S='\TaxiBaza\Fleets\Status'; $P='\TaxiBaza\Fleets\Profile'; $AP='\TaxiBaza\Fleets\Admin_Panel';
	add_filter( 'wpml_current_language', fn() => 'uk' );
	$GLOBALS['__users'][30] = [ 'car_owner' ]; \test_set_role( 30, 'fleet' );
	$before = $N::unread( 1 );
	$P::submit( 30, array_merge( $p, [ 'name' => 'Bell Fleet' ] ) );
	check( 'admin gets "new fleet" notification', $N::unread( 1 ) === $before + 1 && 'fleet_new' === $N::get( 1 )[0]['type'] );
	$pres = $N::present( $N::get( 1 )[0] );
	check( 'admin text mentions fleet name (uk)', str_contains( $pres['d'], 'Bell Fleet' ) && str_contains( $pres['t'], 'флотилія' ) );
	$AP::apply( 30, 'approve' );
	check( 'fleet gets "approved"', 'approved' === $N::get( 30 )[0]['type'] && 1 === $N::unread( 30 ) );
	$P::submit( 30, array_merge( $p, [ 'name' => 'Bell Fleet 2' ] ) );
	check( 'admin gets "edits" notification', 'fleet_edits' === $N::get( 1 )[0]['type'] );
	$AP::apply( 30, 'approve' );
	check( 'fleet gets "edits approved"', 'edits_ok' === $N::get( 30 )[0]['type'] );
	$P::submit( 30, array_merge( $p, [ 'name' => 'Bell Fleet 3' ] ) );
	$AP::apply( 30, 'reject_edits' );
	check( 'fleet gets "edits rejected"', 'edits_no' === $N::get( 30 )[0]['type'] );
	$AP::apply( 30, 'block' );
	check( 'fleet gets "blocked"', 'blocked' === $N::get( 30 )[0]['type'] );
	for ( $i = 0; $i < 40; $i++ ) { $N::add( 30, 'approved' ); }
	check( 'list capped at 30', 30 === count( $N::get( 30 ) ) );
	check( 'car owner gets nothing', [] === $N::get( 2 ) );

	echo "Email on approval\n";
	$GLOBALS['__mail'] = [];
	$GLOBALS['__users'][40] = [ 'car_owner' ]; \test_set_role( 40, 'fleet' );
	add_filter( 'wpml_current_language', fn() => 'ru' );
	$P::submit( 40, array_merge( $p, [ 'name' => 'Mail Fleet' ] ) );
	check( 'no email on submit', [] === $GLOBALS['__mail'] );
	add_filter( 'wpml_current_language', fn() => 'sk' ); // admin moderates in SK
	$AP::apply( 40, 'approve' );
	$m = $GLOBALS['__mail'][0] ?? [];
	check( 'approval email sent to the fleet', 1 === count( $GLOBALS['__mail'] ) && 'u40@test.sk' === ( $m['to'] ?? '' ) );
	check( 'email in the language the fleet used (ru)', str_contains( $m['subj'] ?? '', 'прошёл модерацию' ) && str_contains( $m['body'] ?? '', 'Mail Fleet' ) && str_contains( $m['body'] ?? '', 'lang="ru"' ) );
	check( 'email links to the profile tab', str_contains( $m['body'] ?? '', '/cabinet/?tab=profile' ) );
	$GLOBALS['__mail'] = [];
	add_filter( 'wpml_current_language', fn() => 'ru' );
	$P::submit( 40, array_merge( $p, [ 'name' => 'Mail Fleet 2' ] ) );
	add_filter( 'wpml_current_language', fn() => 'sk' );
	$AP::apply( 40, 'approve' );
	check( 'edits approval email', str_contains( $GLOBALS['__mail'][0]['subj'] ?? '', 'Изменения профиля' ) );
	$GLOBALS['__mail'] = [];
	$AP::apply( 40, 'block' );
	$AP::apply( 40, 'unblock' );
	check( 'no email on block; unblock back to approved sends one', 1 === count( $GLOBALS['__mail'] ) );
	$GLOBALS['__mail'] = [];
	$GLOBALS['__users'][41] = [ 'car_owner' ]; \test_set_role( 41, 'fleet' );
	update_user_meta( 41, 'tb_notify_moderation', '0' );
	$P::submit( 41, array_merge( $p, [ 'name' => 'Quiet Fleet' ] ) );
	$AP::apply( 41, 'approve' );
	check( 'respects "moderation emails" opt-out', [] === $GLOBALS['__mail'] );
	$GLOBALS['__mail'] = [];
	$GLOBALS['__users'][42] = [ 'car_owner' ]; \test_set_role( 42, 'fleet' );
	add_filter( 'wpml_current_language', fn() => 'sk' ); // admin-post.php: WPML sees the default language
	$_POST = [ 'tbf_lang' => 'en' ];
	$P::submit( 42, array_merge( $p, [ 'name' => 'Form Lang Fleet' ] ) );
	$_POST = [];
	$AP::apply( 42, 'approve' );
	check( 'email language = language of the form page, not admin-post default', str_contains( $GLOBALS['__mail'][0]['subj'] ?? '', 'passed moderation' ) );
	$_POST = [ 'tbf_lang' => 'xx' ];
	check( 'unknown posted language ignored', 'sk' === \TaxiBaza\Fleets\Mailer::form_lang() );
	$_POST = [];
	$GLOBALS['__mail'] = [];
	$AP::apply( 41, 'reject' );
	check( 'no email on reject', [] === $GLOBALS['__mail'] );

	echo "Dashboard: one moderation queue\n";
	foreach ( [ 'is_ssl' => 'return true;', 'remove_query_arg' => 'return $a[1] ?? "";', 'wp_get_attachment_image_url' => 'return "";', 'sanitize_text_field' => 'return (string) $a[0];', 'esc_url_raw' => 'return (string) $a[0];', 'wp_json_encode' => 'return json_encode($a[0]);', 'admin_url' => 'return "https://www.taxibaza.sk/wp-admin/" . ($a[0] ?? "");', 'wp_nonce_field' => 'return "<input type=hidden name=_wpnonce value=x>";' ] as $fn => $body ) {
		if ( ! function_exists( $fn ) ) { eval( "function $fn( ...\$a ) { $body }" ); }
	}
	$_SERVER['HTTP_HOST'] = 'www.taxibaza.sk'; $_SERVER['REQUEST_URI'] = '/panel/';
	$DQ = '\TaxiBaza\Fleets\Dashboard_Queue';
	$GLOBALS['__users'][50] = [ 'car_owner' ]; \test_set_role( 50, 'fleet' );
	$P::submit( 50, array_merge( $p, [ 'name' => 'Queue Fleet' ] ) );
	check( 'pending fleet is in the queue', in_array( 50, $DQ::queue(), true ) );
	$withCards = '<h2>Q</h2><div class="tb-fa-cards" role="list"><article class="tb-fa-mod-card">CAR</article></div>';
	$out = $DQ::inject( $withCards );
	if ( getenv( 'TBF_DUMP' ) ) { file_put_contents( getenv( 'TBF_DUMP' ), $out ); }
	check( 'fleet cards added before listing cards', strpos( $out, 'Queue Fleet' ) < strpos( $out, 'CAR' ) && 1 === substr_count( $out, 'tb-fa-cards' ) );
	check( 'card has approve + reject + view', str_contains( $out, 'value="approve"' ) && str_contains( $out, 'value="reject"' ) && str_contains( $out, 'fleet=50' ) && ! str_contains( $out, 'value="block"' ) );
	$withEmpty = '<h2>Q</h2><div class="tb-fa-empty"><svg></svg><p>No listings.</p></div><footer>F</footer>';
	$out = $DQ::inject( $withEmpty );
	check( 'empty state replaced by fleet cards', str_contains( $out, 'Queue Fleet' ) && ! str_contains( $out, 'tb-fa-empty' ) && str_contains( $out, '<footer>F</footer>' ) );
	check( 'unknown markup left untouched', '<main>x</main>' === $DQ::inject( '<main>x</main>' ) );
	$AP::apply( 50, 'approve' );
	foreach ( $DQ::queue() as $q ) { $AP::apply( $q, 'approve' ); }
	check( 'nothing pending -> page untouched', $withEmpty === $DQ::inject( $withEmpty ) );

	echo "Consent\n";
	$C = '\TaxiBaza\Fleets\Consent';
	if ( ! function_exists( 'user_can' ) ) { eval( 'function user_can( $u, $c ) { return in_array( "administrator", $GLOBALS["__users"][ $u ] ?? [], true ); }' ); }
	$_POST = [ 'tb_action' => 'register', 'tbf_terms' => '1' ]; $GLOBALS['__users'][60] = [ 'car_owner' ];
	$C::on_user_register( 60 );
	check( 'form consent stores the date (UTC) and how', preg_match( '/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/', (string) get_user_meta( 60, 'tbf_terms_accepted_at' ) ) && 'register_form' === get_user_meta( 60, 'tbf_terms_accepted_via' ) );
	$_POST = []; $_GET = [ 'tb_google' => 'callback' ]; $GLOBALS['__users'][61] = [ 'car_owner' ];
	$C::on_user_register( 61 );
	check( 'new Google account must accept on first login', 1 == get_user_meta( 61, 'tbf_terms_pending' ) && '' === get_user_meta( 61, 'tbf_terms_accepted_at' ) );
	$C::record( 61, 'google_first_login' );
	check( 'accepting clears the gate and stores the date', '' === get_user_meta( 61, 'tbf_terms_pending' ) && 'google_first_login' === get_user_meta( 61, 'tbf_terms_accepted_via' ) );
	$_GET = []; $GLOBALS['__users'][62] = [ 'car_owner' ];
	$C::on_user_register( 62 );
	check( 'other account creation (admin) is not gated', '' === get_user_meta( 62, 'tbf_terms_pending' ) );
	if ( ! function_exists( 'get_permalink' ) ) { eval( 'function get_permalink( $id ) { return "https://www.taxibaza.sk/?page_id=" . $id; }' ); }
	$h = $C::label_html();
	check( 'label links both legal pages', 2 === substr_count( $h, '<a href=' ) && str_contains( $h, 'target="_blank"' ) );

	echo "Fleet listing\n";
	$L = '\TaxiBaza\Fleets\Listing_Fleet';
	$GLOBALS['__users'][70] = [ 'fleet' ]; $GLOBALS['__users'][71] = [ 'car_owner' ];
	update_user_meta( 70, 'tbf_profile_live', [ 'name' => 'Rapid Fleet s.r.o.' ] );
	$GLOBALS['__posts'][700] = [ 'post_author' => 70 ]; $GLOBALS['__posts'][701] = [ 'post_author' => 71 ];
	check( 'old fleet listing (no meta) counts as fleet listing', $L::is_fleet_listing( 700 ) && [ 'fleet' => true, 'name' => 'Rapid Fleet s.r.o.' ] === tbf_listing_owner( 700 ) );
	check( 'owner listing never belongs to a fleet', ! $L::is_fleet_listing( 701 ) && null === tbf_listing_owner( 701 ) );
	$GLOBALS['__env']['ajax'] = true;
	$_POST = [ 'action' => 'tb_submit_listing', 'tbf_fleet_listing_sent' => '1' ];
	$L::on_save( 700, (object) [ 'post_author' => 70 ] );
	check( 'unchecked box -> independent listing', '0' === get_post_meta( 700, 'tbf_fleet_listing' ) && [ 'fleet' => false, 'name' => 'User 70' ] === tbf_listing_owner( 700 ) );
	$_POST['tbf_fleet_listing'] = '1';
	$L::on_save( 700, (object) [ 'post_author' => 70 ] );
	check( 'checked box -> fleet listing', '1' === get_post_meta( 700, 'tbf_fleet_listing' ) && $L::is_fleet_listing( 700 ) );
	$_POST = [ 'action' => 'tb_submit_listing' ];
	$L::on_save( 700, (object) [ 'post_author' => 70 ] );
	check( 'save without our field (other form) keeps the choice', '1' === get_post_meta( 700, 'tbf_fleet_listing' ) );
	$_POST = [ 'action' => 'tb_submit_listing', 'tbf_fleet_listing_sent' => '1' ];
	$L::on_save( 701, (object) [ 'post_author' => 71 ] );
	check( 'car owner listing gets no fleet meta', '' === get_post_meta( 701, 'tbf_fleet_listing' ) );
	$GLOBALS['__env']['ajax'] = false; $_POST = [];

	echo "Admin edits a fleet profile\n";
	$P = '\TaxiBaza\Fleets\Profile';
	$GLOBALS['__users'][80] = [ 'fleet' ];
	update_user_meta( 80, 'tbf_profile_live', [ 'name' => 'Live' ] );
	update_user_meta( 80, 'tbf_profile_draft', [ 'name' => 'Draft' ] );
	check( 'pending version is edited in place', 'draft' === $P::admin_store( 80, [ 'name' => 'Fixed draft' ] + $P::defaults() ) && 'Fixed draft' === get_user_meta( 80, 'tbf_profile_draft' )['name'] && 'Live' === get_user_meta( 80, 'tbf_profile_live' )['name'] );
	$GLOBALS['__users'][81] = [ 'fleet' ];
	update_user_meta( 81, 'tbf_profile_live', [ 'name' => 'Live' ] );
	check( 'approved profile without edits is updated directly', 'live' === $P::admin_store( 81, [ 'name' => 'New name' ] + $P::defaults() ) && 'New name' === get_user_meta( 81, 'tbf_profile_live' )['name'] && '' === get_user_meta( 81, 'tbf_profile_draft' ) && 'New name' === get_user_meta( 81, 'company_name' ) );

	echo "Rental request in the bell\n";
	$N = '\TaxiBaza\Fleets\Notifications';
	$GLOBALS['__users'][90] = [ 'car_owner' ];
	$GLOBALS['__posts'][900] = [ 'post_author' => 90 ];
	if ( ! function_exists( 'get_the_title' ) ) { eval( 'function get_the_title( $id ) { return "Audi A1 2018"; }' ); }
	$N::on_rental_request( 900, 'Oleg Driver' );
	$list = $N::get( 90 );
	check( 'owner gets an unread "rental_request" notification', 1 === count( $list ) && 'rental_request' === $list[0]['type'] && 900 === $list[0]['args']['listing'] && 1 === $N::unread( 90 ) );


	echo "Fleet pages: plural forms\n";
	add_filter( 'wpml_current_language', fn() => 'uk' );
	$I = '\TaxiBaza\Fleets\I18n';
	check( 'uk: 1 one, 2 few, 5 many, 11 many, 21 one, 22 few', 'one' === $I::plural_form( 1, 'uk' ) && 'few' === $I::plural_form( 2, 'uk' ) && 'many' === $I::plural_form( 5, 'uk' ) && 'many' === $I::plural_form( 11, 'uk' ) && 'one' === $I::plural_form( 21, 'uk' ) && 'few' === $I::plural_form( 22, 'uk' ) );
	check( 'sk: 1 one, 3 few, 5 many, 21 many, 0 many', 'one' === $I::plural_form( 1, 'sk' ) && 'few' === $I::plural_form( 3, 'sk' ) && 'many' === $I::plural_form( 5, 'sk' ) && 'many' === $I::plural_form( 21, 'sk' ) && 'many' === $I::plural_form( 0, 'sk' ) );
	check( 'en: 1 one, 2 many', 'one' === $I::plural_form( 1, 'en' ) && 'many' === $I::plural_form( 2, 'en' ) );
	check( 'count text uk / sk / ru / en', 'Доступно 40 флотилій' === $I::plural( 40, 'fl_count', 'uk' ) && 'Dostupné 3 flotily' === $I::plural( 3, 'fl_count', 'sk' ) && 'Доступен 21 таксопарк' === $I::plural( 21, 'fl_count', 'ru' ) && '1 fleet available' === $I::plural( 1, 'fl_count', 'en' ) );

	echo "Fleet pages: slugs\n";
	$D = '\TaxiBaza\Fleets\Directory';
	check( 'slug drops legal form', 'gold-fleet' === $D::slugify( 'Gold Fleet s.r.o.' ) );
	check( 'cyrillic transliterated', 'taksi-best' === $D::slugify( 'Таксі Бест' ) && 'taksopark-iuh' === $D::slugify( 'Таксопарк «Юг»' ) );
	check( 'accents removed', 'zilina-taxi' === $D::slugify( 'Žilina Taxi' ) );
	$owner = fn( $s ) => [ 'gold-fleet' => 5, 'gold-fleet-2' => 6 ][ $s ] ?? 0;
	check( 'taken slug gets the next free suffix', 'gold-fleet-3' === $D::unique_slug( 'gold-fleet', 7, $owner ) );
	check( 'own slug stays', 'gold-fleet' === $D::unique_slug( 'gold-fleet', 5, $owner ) );
	check( 'empty / numeric / reserved slugs are prefixed', 'fleet-7' === $D::unique_slug( '', 7, $owner ) && 'fleet-123' === $D::unique_slug( '123', 7, $owner ) && 'fleet-page' === $D::unique_slug( 'page', 7, $owner ) );

	echo "Fleet pages: visibility + slug lifecycle\n";
	if ( ! function_exists( 'is_wp_error' ) ) { eval( 'function is_wp_error( $x ) { return $x instanceof \WP_Error; } class WP_Error {}' ); }
	foreach ( [
		'has_filter' => 'return ! empty( $GLOBALS["__hooks"][ $a[0] ] );',
		'get_posts' => 'return $GLOBALS["__get_posts"] ?? [];',
		'wp_get_object_terms' => 'return $GLOBALS["__obj_terms"] ?? [];',
		'get_term' => 'return $GLOBALS["__terms"][ (int) $a[0] ] ?? null;',
		'get_terms' => 'return array_keys( $GLOBALS["__terms"] ?? [] );',
		'wp_parse_url' => 'return parse_url( ...$a );',
		'get_post_type_archive_link' => 'return "https://example.test/avto/";',
		'sanitize_title' => 'return strtolower( preg_replace( "/[^a-z0-9-]/i", "", (string) $a[0] ) );',
		'wp_verify_nonce' => 'return "ok" === $a[0];',
		'get_transient' => 'return $GLOBALS["__transients"][ $a[0] ] ?? false;',
		'set_transient' => '$GLOBALS["__transients"][ $a[0] ] = $a[1]; return true;',
		'wp_send_json_success' => '$GLOBALS["__ajax_out"] = [ "success", $a[0] ?? null ]; throw new \WP_Ajax_Exit();',
		'get_bloginfo' => 'return "TaxiBaza.sk";',
		'get_query_var' => 'return $GLOBALS["__qv"][ $a[0] ] ?? "";',
	] as $fn => $body ) {
		if ( ! function_exists( $fn ) ) { eval( "function $fn( ...\$a ) { $body }" ); }
	}
	$GLOBALS['__users'][100] = [ 'fleet' ]; $GLOBALS['__users'][101] = [ 'fleet' ]; $GLOBALS['__users'][102] = [ 'fleet' ]; $GLOBALS['__users'][103] = [ 'fleet' ]; $GLOBALS['__users'][104] = [ 'car_owner' ];
	$live = array_merge( \TaxiBaza\Fleets\Profile::defaults(), $p, [ 'name' => 'Silver Line s.r.o.', 'own_fee' => '8', 'own_fee_base' => 'gross', 'rent_dep_on' => true, 'rent_dep' => '200', 'inc' => [ 'insurance', 'service' ], 'langs' => [ 'sk', 'uk' ], 'req_age' => '21', 'req_exp' => '2', 'docs' => [ 'id', 'license', 'record' ], 'pay_method' => [ 'bank' ] ] );
	update_user_meta( 100, 'tbf_status', 'approved' ); update_user_meta( 100, 'tbf_profile_live', $live );
	update_user_meta( 101, 'tbf_status', 'approved' );
	update_user_meta( 102, 'tbf_status', 'blocked' ); update_user_meta( 102, 'tbf_profile_live', $live );
	update_user_meta( 103, 'tbf_status', 'pending' ); update_user_meta( 103, 'tbf_profile_live', $live );
	update_user_meta( 104, 'tbf_profile_live', $live );
	$D::flush_cache();
	$vis = $D::visible_ids();
	check( 'approved fleet with live profile is public', in_array( 100, $vis, true ) );
	check( 'approved without live profile, blocked, pending, non-fleet are not', ! array_intersect( [ 101, 102, 103, 104 ], $vis ) );
	check( 'ensure_slug stores a slug once', 'silver-line' === $D::ensure_slug( 100 ) && 'silver-line' === get_user_meta( 100, 'tbf_slug' ) );
	update_user_meta( 100, 'tbf_profile_live', array_merge( $live, [ 'name' => 'Renamed Fleet' ] ) );
	check( 'slug stays after a rename', 'silver-line' === $D::ensure_slug( 100 ) );
	$GLOBALS['__users'][105] = [ 'fleet' ];
	update_user_meta( 105, 'tbf_profile_live', array_merge( $live, [ 'name' => 'Silver Line' ] ) );
	update_user_meta( 105, 'tbf_status', 'pending' );
	\TaxiBaza\Fleets\Status::set( 105, \TaxiBaza\Fleets\Status::APPROVED );
	check( 'approval generates a unique slug for a namesake', 'silver-line-2' === get_user_meta( 105, 'tbf_slug' ) );
	check( 'find by slug', 100 === $D::find( 'silver-line' ) && 105 === $D::find( 'silver-line-2' ) );
	check( 'find by user ID fallback', 100 === $D::find( '100' ) );
	update_user_meta( 102, 'tbf_slug', 'blocked-fleet' );
	check( 'blocked / unknown / non-fleet -> 0 (404)', 0 === $D::find( 'blocked-fleet' ) && 0 === $D::find( 'nope' ) && 0 === $D::find( '104' ) );
	check( 'fleet URL', 'https://example.test/flotily/silver-line/' === $D::fleet_url( 100 ) );
	check( 'catalog URL keeps only real filters', 'https://example.test/flotily/?f_city=bratislava' === $D::catalog_url( [ 'f_city' => 'bratislava', 'f_platform' => '', 'sort' => 'new', 'pg' => 1 ] ) );
	add_filter( 'wpml_permalink', fn( $u, $l = null ) => str_replace( 'https://example.test/', 'https://example.test/' . ( $l ?: 'uk' ) . '/', $u ), 10, 2 );
	check( 'language-aware base URL (WPML)', 'https://example.test/en/flotily/' === $D::base_url( 'en' ) );
	remove_all_wpml_permalink();

	echo "Fleet pages: listings of a fleet\n";
	check( 'one copy per translation group, current language preferred', [ 11, 20, 30 ] === $D::one_per_group( [ [ 'id' => 10, 'trid' => 1, 'lang' => 'sk' ], [ 'id' => 11, 'trid' => 1, 'lang' => 'uk' ], [ 'id' => 20, 'trid' => 2, 'lang' => 'sk' ], [ 'id' => 30, 'trid' => 0, 'lang' => '' ] ], 'uk' ) );
	$GLOBALS['__posts'][901] = [ 'post_author' => 100 ]; $GLOBALS['__posts'][902] = [ 'post_author' => 100 ];
	$GLOBALS['__pmeta'][902]['tbf_fleet_listing'] = '0';
	$GLOBALS['__get_posts'] = [ 901, 902 ];
	$D::flush_cache();
	check( 'listing unticked as fleet listing is left out', [ 901 ] === $D::listing_ids( 100 ) );

	echo "Fleet pages: card data\n";
	check( 'initials', 'GF' === $D::initials( 'Gold Fleet s.r.o.' ) && 'DR' === $D::initials( 'DriveRent Partner s.r.o.' ) && 'TB' === $D::initials( 'taxi best' ) && 'ТБ' === $D::initials( 'Таксі Бест' ) );
	$h = $D::headline( [ 'models' => [ 'own', 'fleet' ], 'own_pay_type' => 'percent', 'own_fee' => '8' ] );
	check( 'own-car commission in % of earnings', '8 %' === $h['value'] && 'з виручки' === $h['unit'] );
	$h = $D::headline( [ 'models' => [ 'own' ], 'own_pay_type' => 'fixed', 'own_fee' => '20' ] );
	check( 'fixed commission per week', '20 €' === $h['value'] && '/ тиждень' === $h['unit'] );
	check( 'fleet cars only: no headline (each car has its own price)', null === $D::headline( [ 'models' => [ 'fleet' ], 'rent_price' => '210', 'rent_period' => 'week' ] ) );
	check( 'nothing set -> no headline (no placeholder numbers)', null === $D::headline( [ 'models' => [ 'own' ], 'own_fee' => '' ] ) );
	$f = array_column( $D::features( [ 'models' => [ 'own' ], 'pay_freq' => 'weekly', 'perk_fuel' => true ] ), 'text' );
	check( 'feature chips from the profile', [ 'Тижневі виплати', 'Власне авто', 'Паливна картка' ] === $f );
	check( 'money format', '8,5' === $D::money( '8.5' ) && '1 200' === $D::money( 1200 ) );

	echo "Fleet pages: filter, sort, paginate\n";
	$cards = [
		[ 'id' => 1, 'name' => 'Zeta', 'since' => 100, 'cities' => [ 5 => [ 'name' => 'Bratislava', 'slug' => 'bratislava' ] ], 'platforms' => [ 'bolt', 'uber' ] ],
		[ 'id' => 2, 'name' => 'alpha', 'since' => 300, 'cities' => [ 6 => [ 'name' => 'Košice', 'slug' => 'kosice' ] ], 'platforms' => [ 'uber' ] ],
		[ 'id' => 3, 'name' => 'Beta', 'since' => 200, 'cities' => [ 5 => [ 'name' => 'Bratislava', 'slug' => 'bratislava' ], 6 => [ 'name' => 'Košice', 'slug' => 'kosice' ] ], 'platforms' => [ 'hopin' ] ],
	];
	check( 'newest first by default', [ 2, 3, 1 ] === array_column( $D::filter( $cards, 0, '', 'new' ), 'id' ) );
	check( 'sort by name, case-insensitive', [ 2, 3, 1 ] === array_column( $D::filter( $cards, 0, '', 'name' ), 'id' ) && 'alpha' === $D::filter( $cards, 0, '', 'name' )[0]['name'] );
	check( 'city filter', [ 3, 1 ] === array_column( $D::filter( $cards, 5, '', 'new' ), 'id' ) );
	check( 'platform filter', [ 2, 1 ] === array_column( $D::filter( $cards, 0, 'uber', 'new' ), 'id' ) );
	check( 'city + platform', [ 1 ] === array_column( $D::filter( $cards, 5, 'uber', 'new' ), 'id' ) && [] === $D::filter( $cards, 6, 'bolt', 'new' ) );
	$opts = $D::city_options( $cards );
	check( 'city options (unique, by name) and slug lookup', [ 5, 6 ] === array_keys( $opts ) && 6 === $D::city_key( $opts, 'kosice' ) && 0 === $D::city_key( $opts, 'x' ) );
	check( 'platform options in fixed order', [ 'bolt', 'uber', 'hopin' ] === $D::platform_options( $cards ) );
	$pg = $D::paginate( range( 1, 30 ), 3, 12 );
	check( 'pagination: 30 items, page 3 of 3 has 6', 3 === $pg['pages'] && 3 === $pg['page'] && [ 25, 26, 27, 28, 29, 30 ] === $pg['items'] );
	check( 'page out of range is clamped', 1 === $D::paginate( [], 9 )['page'] && 3 === $D::paginate( range( 1, 30 ), 99, 12 )['page'] );
	check( 'page links with gaps', [ 1, 2, 3, 0, 11 ] === $D::page_links( 1, 11 ) && [ 1, 0, 5, 6, 7, 0, 11 ] === $D::page_links( 6, 11 ) && [ 1, 2, 3 ] === $D::page_links( 2, 3 ) );

	echo "Fleet pages: profile -> page texts\n";
	$V = '\TaxiBaza\Fleets\Fleet_View';
	$terms = $V::terms( $live );
	check( 'both models -> two columns', [ 'own', 'fleet' ] === array_keys( $terms ) );
	check( 'own column metric 8 % (gross)', '8 %' === $terms['own']['value'] && str_contains( $terms['own']['value_text'], 'загальної' ) );
	check( 'fleet column: no rent figure, a note about prices in listings', '' === $terms['fleet']['value'] && str_contains( $terms['fleet']['note'], 'оголошенні' ) );
	$titles = array_column( $terms['fleet']['perks'], 'title' );
	check( 'fleet perks: old rent fields are not shown', ! in_array( 'Депозит', $titles, true ) && ! in_array( 'Мінімальний термін', $titles, true ) );
	check( 'payouts text', 'Щотижня, понеділок, на банківський рахунок.' === $terms['own']['perks'][0]['text'] );
	$t2 = $V::terms( array_merge( $live, [ 'models' => [ 'fleet' ], 'rent_dep_on' => false, 'inc' => [], 'rent_comm_on' => false ] ) );
	check( 'fleet-only: one column, empty blocks left out', [ 'fleet' ] === array_keys( $t2 ) && ! in_array( 'Депозит', array_column( $t2['fleet']['perks'], 'title' ), true ) );
	$req = $V::requirements( $live );
	check( 'requirements: age with plural, experience, languages (documents list removed)', '21 рік' === $req[0]['text'] && 'Від 2 років' === $req[1]['text'] && 'Словацька, українська' === $req[2]['text'] );
	$req2 = $V::requirements( array_merge( $live, [ 'req_other' => 'Знання міста' ] ) );
	check( 'requirements: free-text "other requirements" shown', in_array( 'Знання міста', array_column( $req2, 'text' ), true ) );

	echo "Work request: validation\n";
	$W = '\TaxiBaza\Fleets\Work_Request';
	$ok = [ 'car' => 'fleet', 'license' => 'yes', 'city' => '5', 'name' => ' Oleh  Driver ', 'phone' => '+421 902 123 456', 'email' => 'oleh@example.com', 'message' => 'Dobrý deň, chcem jazdiť u vás od budúceho týždňa.' ];
	[ $d, $e ] = $W::validate( $ok, [ 5, 6 ] );
	check( 'valid application', [] === $e && 'Oleh Driver' === $d['name'] && 5 === $d['city'] );
	[ , $e ] = $W::validate( [], [ 5 ] );
	check( 'empty form: every required field flagged', [ 'car', 'license', 'city', 'name', 'phone', 'message' ] === array_keys( $e ) && 'fl_m_err_pick' === $e['car'] );
	[ , $e ] = $W::validate( array_merge( $ok, [ 'message' => 'Привіт!' ] ), [ 5 ] );
	check( 'short message (min 20)', [ 'message' => 'fl_m_err_short' ] === $e );
	[ , $e ] = $W::validate( array_merge( $ok, [ 'phone' => '123', 'email' => 'bad@', 'car' => 'bike', 'city' => '9' ] ), [ 5 ] );
	check( 'bad phone, email, choice, unknown city', isset( $e['phone'], $e['email'], $e['car'], $e['city'] ) && 'err_phone' === $e['phone'] );
	[ $d, ] = $W::validate( array_merge( $ok, [ 'message' => '<script>x</script>Hello there, I would like to drive.' ] ), [ 5 ] );
	check( 'tags stripped from the message', ! str_contains( $d['message'], '<' ) );
	check( 'honeypot', $W::is_spam( [ 'tbf_website' => 'http://spam' ] ) && ! $W::is_spam( [] ) );

	echo "Work request: AJAX end-to-end\n";
	$GLOBALS['__terms'] = [ 5 => (object) [ 'term_id' => 5, 'name' => 'Bratislava' ], 6 => (object) [ 'term_id' => 6, 'name' => 'Košice' ] ];
	$GLOBALS['__mail'] = []; $GLOBALS['__transients'] = [];
	update_user_meta( 100, 'tbf_lang', 'en' );
	$N = '\TaxiBaza\Fleets\Notifications';
	$before = count( $N::get( 100 ) );
	$run = function ( array $post ) use ( $W ) { $_POST = $post; $GLOBALS['__ajax_out'] = null; try { $W::handle(); } catch ( \WP_Ajax_Exit $e ) {} $_POST = []; return $GLOBALS['__ajax_out']; };
	$out = $run( $ok + [ '_wpnonce' => 'bad', 'fleet' => '100', 'lang' => 'uk' ] );
	check( 'bad nonce -> 403', 'error' === $out[0] && 403 === $out[2] );
	$out = $run( $ok + [ '_wpnonce' => 'ok', 'fleet' => '102', 'lang' => 'uk' ] );
	check( 'blocked fleet -> 404', 'error' === $out[0] && 404 === $out[2] );
	$out = $run( array_merge( $ok, [ 'message' => 'short' ] ) + [ '_wpnonce' => 'ok', 'fleet' => '100', 'lang' => 'sk' ] );
	check( 'invalid -> 422 with texts in the page language', 'error' === $out[0] && 422 === $out[2] && str_contains( $out[1]['errors']['message'], 'príliš krátka' ) && [] === $GLOBALS['__mail'] );
	$out = $run( $ok + [ '_wpnonce' => 'ok', 'fleet' => '100', 'lang' => 'uk', 'tbf_website' => 'x' ] );
	check( 'honeypot: fake success, nothing sent', 'success' === $out[0] && [] === $GLOBALS['__mail'] );
	$out = $run( $ok + [ '_wpnonce' => 'ok', 'fleet' => '100', 'lang' => 'uk' ] );
	check( 'valid -> success text names the fleet (uk)', 'success' === $out[0] && str_contains( $out[1]['text'], 'Renamed Fleet' ) && str_contains( $out[1]['title'], 'успішно' ) );
	$m = $GLOBALS['__mail'][0] ?? [];
	check( 'email to the fleet in its language (en)', 'u100@test.sk' === ( $m['to'] ?? '' ) && str_contains( $m['subj'] ?? '', 'job application' ) && str_contains( $m['body'] ?? '', 'lang="en"' ) && str_contains( $m['body'] ?? '', 'Oleh Driver' ) && str_contains( $m['body'] ?? '', 'Bratislava' ) );
	check( 'email links to the fleet page', str_contains( $m['body'] ?? '', '/flotily/silver-line/' ) );
	$wn = $N::get( 100 )[0];
	check( 'bell notification for the fleet', count( $N::get( 100 ) ) === $before + 1 && 'work_request' === $wn['type'] );
	$pres = $N::present( $wn );
	check( 'notification text: title + name · phone, links to the cabinet', 'Нова заявка на роботу' === $pres['t'] && 'Oleh Driver · +421 902 123 456' === $pres['d'] && str_contains( $pres['url'], 'tab=profile' ) );
	check( 'application stored for the fleet', 'Oleh Driver' === get_user_meta( 100, 'tbf_work_requests' )[0]['name'] );
	for ( $i = 0; $i < 5; $i++ ) { $out = $run( $ok + [ '_wpnonce' => 'ok', 'fleet' => '100', 'lang' => 'uk' ] ); }
	check( 'rate limit: 6th request from one IP in 10 min -> 429', 'error' === $out[0] && 429 === $out[2] );
	$mail_html = \TaxiBaza\Fleets\Mailer::build_work_request( 'sk', 'X', [ 'name' => '<b>A</b>', 'message' => "<img src=x>\nline2", 'phone' => '+421 1', 'email' => '', 'car' => 'own', 'license' => 'no', 'city_name' => 'Nitra' ], 'https://example.test/flotily/x/' )[1];
	check( 'email escapes user input', ! str_contains( $mail_html, '<img src=x>' ) && ! str_contains( $mail_html, '<b>A</b>' ) && str_contains( $mail_html, 'Mám vlastné auto' ) );


	echo "Fleet pages: routing\n";
	$FP = '\TaxiBaza\Fleets\Fleet_Pages';
	$GLOBALS['__qv'] = [ 'tbf_fleet' => 'nope' ]; $GLOBALS['wp_query'] = new \Fake_Query( [] ); $GLOBALS['__is404'] = false; $GLOBALS['__status'] = 0;
	$FP::route();
	check( 'unknown fleet slug -> 404', true === $GLOBALS['__is404'] && 404 === $GLOBALS['__status'] && ! $FP::is_fleet_page() );
	check( 'WP canonical redirect is off on fleet URLs', false === $FP::no_canonical_redirect( 'https://example.test/' ) );
	$GLOBALS['__qv'] = [ 'tbf_fleet' => '100' ];
	try { $FP::route(); $r = ''; } catch ( \WP_Redirect_Exit $e ) { $r = $GLOBALS['__redirect']; }
	check( 'user-ID URL redirects to the slug URL', 'https://example.test/flotily/silver-line/' === $r );
	$GLOBALS['__qv'] = [ 'tbf_fleet' => 'silver-line' ]; $GLOBALS['__is404'] = false;
	$FP::route();
	check( 'known slug -> fleet page', 100 === $FP::current_fleet() && $FP::is_fleet_page() && false === $GLOBALS['__is404'] );
	check( 'document title with the fleet name', str_starts_with( $FP::document_title( 'x' ), 'Renamed Fleet – Флотилії – ' ) );
	check( 'body class', in_array( 'tbf-page-fleet', $FP::body_class( [ 'blog', 'home' ] ), true ) && ! in_array( 'blog', $FP::body_class( [ 'blog' ] ), true ) );
	$GLOBALS['__qv'] = [ 'tbf_fleets' => '1' ];
	check( 'catalogue: 404 handling skipped', true === $FP::skip_404( false, new \Fake_Query( [ 'tbf_fleets' => '1' ] ) ) && false === $FP::skip_404( false, new \Fake_Query( [] ) ) );
	check( 'template swap', str_ends_with( $FP::template( 'theme.php' ), 'templates/fleets-catalog.php' ) );
	$GLOBALS['__qv'] = [];
	echo "Menu\n";
	$M = '\TaxiBaza\Fleets\Menu';
	$item = [ 'label' => 'Flotily', 'url' => 'https://example.test/uk/flotily/' ];
	$menu = [ [ 'label' => 'Каталог авто', 'url' => 'https://example.test/uk/avto/' ], [ 'label' => 'Міста', 'url' => '' ], [ 'label' => 'Контакти', 'url' => '/kontakt/' ] ];
	check( 'inserted right after the catalogue', [ 'Каталог авто', 'Flotily', 'Міста', 'Контакти' ] === array_column( $M::insert( $menu, $item, '/avto/' ), 'label' ) );
	check( 'no catalogue item -> second position', [ 'Міста', 'Flotily', 'Контакти' ] === array_column( $M::insert( array_slice( $menu, 1 ), $item, '/avto/' ), 'label' ) );
	$menu2 = [ [ 'label' => 'Katalóg', 'url' => '/avto/' ], [ 'label' => 'Flotily (admin)', 'url' => '/flotily/' ] ];
	$r = $M::insert( $menu2, $item + [ 'current' => true ], '/avto/' );
	check( 'already in the menu -> not duplicated, marked current', 2 === count( $r ) && true === $r[1]['current'] );
	check( 'empty menu', [ 'Flotily' ] === array_column( $M::insert( [], $item, '/avto/' ), 'label' ) );
	$h = $M::header( [ [ 'label' => 'Catalog', 'url' => 'https://example.test/avto/' ] ] );
	check( 'header filter adds "Флотилії", current on a fleet page', 'Флотилії' === $h[1]['label'] && 'https://example.test/flotily/' === $h[1]['url'] && true === $h[1]['current'] );
	$f = $M::footer( [ [ 'label' => 'Domov', 'url' => '/' ], [ 'label' => 'Katalóg', 'url' => '/avto/' ] ] );
	check( 'footer filter adds an internal link after the catalogue', 'Флотилії' === $f[2]['label'] && true === $f[2]['internal'] );

	echo "Consent on Google sign-in\n";
	$C = '\TaxiBaza\Fleets\Consent';
	$_GET = [ 'tb_google' => 'callback' ];
	$GLOBALS['__users'][95] = [ 'car_owner' ];
	$C::on_google_login( '', 0, 0, 95 );
	check( 'existing account without consent gets the consent screen', 1 == get_user_meta( 95, 'tbf_terms_pending' ) );
	$GLOBALS['__users'][96] = [ 'car_owner' ];
	update_user_meta( 96, 'tbf_terms_accepted_at', '2026-11-06 10:00:00' );
	$C::on_google_login( '', 0, 0, 96 );
	check( 'account that already accepted is not asked again', '' === get_user_meta( 96, 'tbf_terms_pending' ) );
	$_GET = [];
	$GLOBALS['__users'][97] = [ 'car_owner' ];
	$C::on_google_login( '', 0, 0, 97 );
	check( 'ordinary password login is not affected', '' === get_user_meta( 97, 'tbf_terms_pending' ) );

	echo "\n$n checks, $fails failed\n";
	exit( $fails ? 1 : 0 );
}
