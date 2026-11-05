<?php
/**
 * Front-admin tab: Користувачі (Users list).
 * Inherits from page-front-admin.php: $panel_url, $tab.
 */

$status_filter = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) );
// NOTE: "fa_s" (not "s") — "s" is WordPress's reserved core search query var.
$search_q      = sanitize_text_field( wp_unslash( $_GET['fa_s'] ?? '' ) );
$sort_filter   = sanitize_key( wp_unslash( $_GET['sort'] ?? 'registered_desc' ) );
$paged         = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) );
$per_page      = 20;

$allowed_sorts = [ 'registered_desc', 'registered_asc', 'name_asc', 'name_desc' ];
if ( ! in_array( $sort_filter, $allowed_sorts, true ) ) {
	$sort_filter = 'registered_desc';
}

// Extra filters (Figma: city + user-type dropdowns).
$city_filter = absint( wp_unslash( $_GET['city'] ?? 0 ) );
$type_filter = sanitize_key( wp_unslash( $_GET['utype'] ?? '' ) );
if ( ! in_array( $type_filter, [ 'car_owner', 'fleet', 'driver' ], true ) ) {
	$type_filter = '';
}

// City terms for the dropdown.
$city_terms = get_terms( [ 'taxonomy' => 'city', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
if ( is_wp_error( $city_terms ) ) {
	$city_terms = [];
}

// ── Counts (car_owner + fleet + driver) ──────────────────────────
$base_count_args = [ 'role__in' => [ 'car_owner', 'fleet', 'driver' ], 'fields' => 'ID', 'number' => 0 ];
$total_count     = count( get_users( $base_count_args ) );
$blocked_count   = count( get_users( array_merge( $base_count_args, [
	'meta_query' => [ [ 'key' => 'tb_user_blocked', 'value' => '1', 'compare' => '=' ] ],
] ) ) );
$active_count = $total_count - $blocked_count;

// ── Paginated query ───────────────────────────────────────────────
$query_args = [
	'role__in' => $type_filter ? [ $type_filter ] : [ 'car_owner', 'fleet', 'driver' ],
	'number'   => $per_page,
	'paged'    => $paged,
	'count_total' => true,
];
switch ( $sort_filter ) {
	case 'registered_asc':
		$query_args['orderby'] = 'registered';
		$query_args['order']   = 'ASC';
		break;
	case 'name_asc':
		$query_args['orderby'] = 'display_name';
		$query_args['order']   = 'ASC';
		break;
	case 'name_desc':
		$query_args['orderby'] = 'display_name';
		$query_args['order']   = 'DESC';
		break;
	default: // registered_desc
		$query_args['orderby'] = 'registered';
		$query_args['order']   = 'DESC';
		break;
}

// City filter → restrict to owners who have a listing in that city.
if ( $city_filter ) {
	global $wpdb;
	$city_author_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT p.post_author
		 FROM {$wpdb->term_taxonomy} tt
		 JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		 JOIN {$wpdb->posts} p ON p.ID = tr.object_id
		 WHERE tt.taxonomy = %s AND tt.term_id = %d
		   AND p.post_type = %s
		   AND p.post_status IN ( 'publish', 'pending', 'draft', 'archived', 'rejected' )",
		'city',
		$city_filter,
		'car_listing'
	) );
	$city_author_ids = array_map( 'intval', (array) $city_author_ids );
	// Empty → force zero results.
	$query_args['include'] = $city_author_ids ?: [ 0 ];
}

if ( $search_q ) {
	$query_args['search']         = '*' . $search_q . '*';
	$query_args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
}
if ( 'blocked' === $status_filter ) {
	$query_args['meta_query'] = [
		[ 'key' => 'tb_user_blocked', 'value' => '1', 'compare' => '=' ],
	];
} elseif ( 'active' === $status_filter ) {
	$query_args['meta_query'] = [
		'relation' => 'OR',
		[ 'key' => 'tb_user_blocked', 'compare' => 'NOT EXISTS' ],
		[ 'key' => 'tb_user_blocked', 'value' => '0', 'compare' => '=', 'type' => 'NUMERIC' ],
	];
}

$user_query  = new \WP_User_Query( $query_args );
$users       = $user_query->get_results();
$total_found = (int) $user_query->get_total();
$num_pages   = (int) ceil( $total_found / $per_page );

// Params to carry across pill / search links.
$extra_qs = array_filter( [
	'city'  => $city_filter ?: null,
	'utype' => $type_filter ?: null,
	'sort'  => 'registered_desc' !== $sort_filter ? $sort_filter : null,
] );

$users_base_url = esc_url( add_query_arg( array_merge( [ 'tab' => 'users' ], $extra_qs ), $panel_url ) );

$sort_url = static function ( string $sk ) use ( $panel_url, $status_filter, $city_filter, $type_filter, $search_q ): string {
	$args = array_filter( [
		'tab'    => 'users',
		'status' => $status_filter ?: null,
		'city'   => $city_filter ?: null,
		'utype'  => $type_filter ?: null,
		'fa_s'   => $search_q ?: null,
		'sort'   => $sk,
	] );
	return esc_url( add_query_arg( $args, $panel_url ) );
};

$filter_pills = [
	''        => [ 'label' => tb_opt( 'tb_fa_adv_all', 'Všetky' ),          'count' => $total_count ],
	'active'  => [ 'label' => tb_opt( 'tb_fa_usr_active', 'Aktívni' ),     'count' => $active_count ],
	'blocked' => [ 'label' => tb_opt( 'tb_fa_usr_blocked', 'Zablokovaní' ), 'count' => $blocked_count ],
];

$role_type_labels = [
	'car_owner' => tb_opt( 'tb_fa_ue_type_owner', 'Fyzická osoba' ),
	'fleet'     => tb_opt( 'tb_fa_ue_type_fleet', 'Flotila' ),
	'driver'    => tb_opt( 'tb_fa_ue_type_driver', 'Vodič' ),
];
?>

<!-- ── Page Head ──────────────────────────────────────────────── -->
<div class="tb-fa-page-head">
	<h1 class="tb-fa-page-head__title"><?php echo esc_html( tb_opt( 'tb_fa_usr_heading', 'Používatelia' ) ); ?></h1>
	<p class="tb-fa-page-head__stats">
		<?php
		printf(
			/* translators: 1: total 2: active 3: blocked */
			esc_html( tb_opt( 'tb_fa_usr_stats', 'Celkom %1$d, aktívnych %2$d, zablokovaných %3$d' ) ),
			$total_count, $active_count, $blocked_count
		);
		?>
	</p>
</div>

<!-- ── Toolbar ───────────────────────────────────────────────── -->
<div class="tb-fa-toolbar tb-fa-toolbar--users">
	<!-- Left group: status pills + city/type dropdowns -->
	<div class="tb-fa-toolbar__left">
		<?php
		// One URL per filter, reused by both the pills and the mobile dropdown.
		$pill_urls = [];
		foreach ( $filter_pills as $pill_key => $pill ) {
			$pill_urls[ $pill_key ] = $pill_key
				? esc_url( add_query_arg( array_merge( [ 'tab' => 'users', 'status' => $pill_key ], $extra_qs ), $panel_url ) )
				: $users_base_url;
		}
		?>
		<div class="tb-fa-pills">
			<?php foreach ( $filter_pills as $pill_key => $pill ) :
				$is_active = ( $status_filter === $pill_key );
			?>
			<a href="<?php echo $pill_urls[ $pill_key ]; // already escaped ?>"
			   class="tb-fa-pill<?php echo $is_active ? ' is-active' : ''; ?>"
			   <?php echo $is_active ? 'aria-current="true"' : ''; ?>>
				<?php echo esc_html( $pill['label'] ); ?>
			</a>
			<?php endforeach; ?>
		</div>


		<!-- City + type dropdowns (auto-submit on change) -->
		<form class="tb-fa-user-filters" method="get" action="<?php echo esc_url( $panel_url ); ?>">
			<input type="hidden" name="tab" value="users">
			<?php if ( $status_filter ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
			<?php endif; ?>
			<?php if ( $search_q ) : ?>
				<input type="hidden" name="fa_s" value="<?php echo esc_attr( $search_q ); ?>">
			<?php endif; ?>
			<?php if ( 'registered_desc' !== $sort_filter ) : ?>
				<input type="hidden" name="sort" value="<?php echo esc_attr( $sort_filter ); ?>">
			<?php endif; ?>
			<select name="city" class="tb-fa-pill-select" onchange="this.form.submit()"
				aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_usr_filter_city', 'Mesto' ) ); ?>">
				<option value="0"><?php echo esc_html( tb_opt( 'tb_fa_usr_filter_city', 'Mesto' ) ); ?></option>
				<?php foreach ( $city_terms as $ct ) : ?>
					<option value="<?php echo esc_attr( $ct->term_id ); ?>" <?php selected( $city_filter, $ct->term_id ); ?>>
						<?php echo esc_html( $ct->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<select name="utype" class="tb-fa-pill-select" onchange="this.form.submit()"
				aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_usr_filter_type', 'Typ používateľa' ) ); ?>">
				<option value=""><?php echo esc_html( tb_opt( 'tb_fa_usr_filter_type', 'Typ používateľa' ) ); ?></option>
				<option value="car_owner" <?php selected( $type_filter, 'car_owner' ); ?>><?php echo esc_html( tb_opt( 'tb_fa_ue_type_owner', 'Fyzická osoba' ) ); ?></option>
				<option value="fleet" <?php selected( $type_filter, 'fleet' ); ?>><?php echo esc_html( tb_opt( 'tb_fa_ue_type_fleet', 'Flotila' ) ); ?></option>
				<option value="driver" <?php selected( $type_filter, 'driver' ); ?>><?php echo esc_html( tb_opt( 'tb_fa_ue_type_driver', 'Vodič' ) ); ?></option>
			</select>
			<noscript><button type="submit" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--sm"><?php echo esc_html( tb_opt( 'tb_fa_usr_filter_apply', 'Použiť' ) ); ?></button></noscript>
		</form>
	</div>

	<!-- Same filter as a dropdown for tablet/mobile. A direct child of the
	     toolbar (not of __left) so it can sit next to the search and sort
	     buttons on one row — Figma 265:23709. -->
	<select
		class="tb-fa-pill-select tb-fa-status-select"
		data-fa-status-select
		aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_usr_status_label', 'Stav účtu' ) ); ?>"
	>
		<?php foreach ( $filter_pills as $pill_key => $pill ) :
			// Read on its own, the "all" entry needs a fuller label than a pill
			// sitting in a row does — Figma 265:23709 shows "Všetci používatelia".
			$opt_label = $pill_key ? $pill['label'] : tb_opt( 'tb_fa_usr_all_long', 'Všetci používatelia' );
		?>
			<option value="<?php echo $pill_urls[ $pill_key ]; // already escaped ?>" <?php selected( $status_filter, $pill_key ); ?>>
				<?php echo esc_html( $opt_label ); ?>
			</option>
		<?php endforeach; ?>
	</select>

	<!-- Right: search + sort -->
	<div class="tb-fa-search-wrap">
		<!-- Collapsed search trigger — mobile only (Figma 265:23715) -->
		<button type="button" class="tb-fa-search-toggle" data-fa-search-toggle aria-expanded="false"
			aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_usr_search_label', 'Hľadať' ) ); ?>">
			<svg class="tb-fa-search-toggle__open" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 21L16.514 16.506M19 11C19 15.418 15.418 19 11 19C6.582 19 3 15.418 3 11C3 6.582 6.582 3 11 3C15.418 3 19 6.582 19 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			<svg class="tb-fa-search-toggle__close" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
		</button>

		<form class="tb-fa-search-form" method="get" action="<?php echo esc_url( $panel_url ); ?>">
			<input type="hidden" name="tab" value="users">
			<?php if ( $status_filter ) : ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $status_filter ); ?>">
			<?php endif; ?>
			<?php if ( $city_filter ) : ?>
				<input type="hidden" name="city" value="<?php echo esc_attr( $city_filter ); ?>">
			<?php endif; ?>
			<?php if ( $type_filter ) : ?>
				<input type="hidden" name="utype" value="<?php echo esc_attr( $type_filter ); ?>">
			<?php endif; ?>
			<?php if ( 'registered_desc' !== $sort_filter ) : ?>
				<input type="hidden" name="sort" value="<?php echo esc_attr( $sort_filter ); ?>">
			<?php endif; ?>
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="tb-fa-search-icon"><path d="M21 21L16.514 16.506M19 11C19 15.418 15.418 19 11 19C6.582 19 3 15.418 3 11C3 6.582 6.582 3 11 3C15.418 3 19 6.582 19 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			<input
				type="search"
				name="fa_s"
				class="tb-fa-search-input"
				value="<?php echo esc_attr( $search_q ); ?>"
				placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_usr_search_ph', 'Hľadať používateľov' ) ); ?>"
				aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_usr_search_label', 'Hľadať' ) ); ?>"
			>
			<button type="submit" class="screen-reader-text"><?php echo esc_html( tb_opt( 'tb_fa_adv_search_btn', 'Hľadať' ) ); ?></button>
		</form>

		<?php if ( $search_q ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array_merge( [ 'tab' => 'users' ], $extra_qs ), remove_query_arg( 'fa_s', $panel_url ) ) ); ?>" class="tb-fa-search-clear" aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_adv_search_reset', 'Vymazať hľadanie' ) ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</a>
		<?php endif; ?>

		<!-- Sort (Figma 214:5865) -->
		<div class="tb-fa-sort-drop">
			<button type="button" class="tb-fa-sort-btn" data-fa-sort-toggle aria-haspopup="true" aria-expanded="false" aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_adv_sort_label', 'Zoradiť' ) ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
			<div class="tb-fa-sort-panel" role="menu">
				<?php
				$user_sort_items = [
					'registered_desc' => tb_opt( 'tb_fa_usr_sort_registered_desc', 'Najnovší' ),
					'registered_asc'  => tb_opt( 'tb_fa_usr_sort_registered_asc', 'Najstarší' ),
					'name_asc'        => tb_opt( 'tb_fa_usr_sort_name_asc', 'Meno: A-Z' ),
					'name_desc'       => tb_opt( 'tb_fa_usr_sort_name_desc', 'Meno: Z-A' ),
				];
				foreach ( $user_sort_items as $sk => $sl ) :
					$s_active = $sort_filter === $sk;
					?>
					<a
						href="<?php echo $sort_url( $sk ); ?>"
						class="tb-fa-sort-option<?php echo $s_active ? ' is-active' : ''; ?>"
						role="menuitemradio"
						aria-checked="<?php echo $s_active ? 'true' : 'false'; ?>"
					>
						<?php echo esc_html( $sl ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<div id="tb-fa-users-results" data-fa-list="users">
	<?php require __DIR__ . '/partial-users-results.php'; ?>
</div>
