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
		foreach ( $GLOBALS['__users'] as $id => $roles ) { if ( empty( $args['role'] ) || in_array( $args['role'], $roles, true ) ) { $out[] = (string) $id; } }
		return $out;
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
	check( 'errors: name, fee>100, docs', isset( $bad['name'], $bad['own_fee'], $bad['docs'] ) );
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

	echo "\n$n checks, $fails failed\n";
	exit( $fails ? 1 : 0 );
}
