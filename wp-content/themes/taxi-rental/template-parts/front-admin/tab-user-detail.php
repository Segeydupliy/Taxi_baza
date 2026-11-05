<?php
/**
 * Front-admin tab: User Detail.
 * Inherits from page-front-admin.php: $view_user_id, $panel_url.
 */

$user = get_userdata( $view_user_id );
if ( ! $user ) {
	echo '<div class="tb-fa-empty">' . esc_html( tb_opt( 'tb_fa_ud_not_found', 'Používateľ sa nenašiel.' ) ) . '</div>';
	return;
}

$is_blocked   = '1' === get_user_meta( $view_user_id, 'tb_user_blocked', true );
$primary_role = ! empty( $user->roles ) ? $user->roles[0] : 'subscriber';
$phone        = (string) get_user_meta( $view_user_id, 'phone_number', true );
$telegram     = (string) get_user_meta( $view_user_id, 'telegram', true );
$description  = (string) get_user_meta( $view_user_id, 'description', true );
$company      = (string) get_user_meta( $view_user_id, 'company_name', true );
$user_city    = (string) get_user_meta( $view_user_id, 'city', true );
$avatar_char  = strtoupper( mb_substr( $user->display_name ?: $user->user_login, 0, 1 ) );

$avatar_id  = tb_user_avatar_id( (int) $view_user_id );
$avatar_url = $avatar_id ? (string) wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';

$role_type_labels = [
	'car_owner'     => tb_opt( 'tb_fa_ue_type_owner', 'Fyzická osoba' ),
	'fleet'         => tb_opt( 'tb_fa_ue_type_fleet', 'Flotila' ),
	'driver'        => tb_opt( 'tb_fa_ue_type_driver', 'Vodič' ),
	'administrator' => tb_opt( 'tb_fa_ud_role_admin', 'Administrátor' ),
];
$role_label = $role_type_labels[ $primary_role ] ?? ucfirst( $primary_role );

$back_url = esc_url( add_query_arg( 'tab', 'users', $panel_url ) );
$edit_url = esc_url( add_query_arg( [ 'tab' => 'users', 'user' => $view_user_id, 'action' => 'edit' ], $panel_url ) );

/**
 * Block/Unblock + Edit buttons.
 *
 * Printed twice: in the page head on desktop, and at the bottom of the info
 * card on mobile, where the design puts them. Both copies are live — the JS
 * binds every [data-fa-action] and resolves the user through the nearest
 * [data-user-id] ancestor, so each wrapper carries that attribute. Kept in one
 * function so the two copies cannot drift apart.
 */
$render_user_actions = static function () use ( $is_blocked, $edit_url ): void {
	?>
	<?php if ( $is_blocked ) : ?>
		<button type="button" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--lg" data-fa-action="unblock">
			<?php echo esc_html( tb_opt( 'tb_fa_ud_unblock_btn', 'Odblokovať' ) ); ?>
		</button>
	<?php else : ?>
		<button type="button" class="tb-fa-btn tb-fa-btn--dark tb-fa-btn--lg" data-fa-action="block">
			<?php echo esc_html( tb_opt( 'tb_fa_ud_block_btn', 'Zablokovať' ) ); ?>
		</button>
	<?php endif; ?>
	<a href="<?php echo $edit_url; ?>" class="tb-fa-btn tb-fa-btn--dark tb-fa-btn--lg">
		<?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?>
	</a>
	<?php
};

// ── Per-user listing counts ──────────────────────────────────────
// A raw $wpdb count used to be used here; it bypassed WPML's language filter
// while the listing query below honours it, so the subtitle and the list
// disagreed on any non-default language.
$cnt_pub      = tb_listing_count( [ 'publish' ], $view_user_id );
$cnt_pend     = tb_listing_count( [ 'pending' ], $view_user_id );
$cnt_arch     = tb_listing_count( [ 'archived' ], $view_user_id );
$cnt_rej      = tb_listing_count( [ 'rejected' ], $view_user_id );
$cnt_user_all = tb_listing_count( [ 'publish', 'pending', 'draft', 'rejected', 'archived' ], $view_user_id );

// ── Listings query ───────────────────────────────────────────────
$lst_status = sanitize_key( wp_unslash( $_GET['lst_status'] ?? '' ) );
$lst_search = sanitize_text_field( wp_unslash( $_GET['lst_s'] ?? '' ) );
$lst_paged  = max( 1, absint( wp_unslash( $_GET['lst_paged'] ?? 1 ) ) );
$lst_sort   = sanitize_key( wp_unslash( $_GET['lst_sort'] ?? 'date_desc' ) );
$lst_pp     = 10;

$allowed_lst_sorts = [ 'date_desc', 'date_asc', 'price_desc', 'price_asc' ];
if ( ! in_array( $lst_sort, $allowed_lst_sorts, true ) ) {
	$lst_sort = 'date_desc';
}

$all_st = [ 'publish', 'pending', 'rejected', 'archived', 'draft' ];

$lst_args = [
	'post_type'      => 'car_listing',
	'author'         => $view_user_id,
	'post_status'    => $lst_status ?: $all_st,
	'posts_per_page' => $lst_pp,
	'paged'          => $lst_paged,
];
switch ( $lst_sort ) {
	case 'date_asc':
		$lst_args['orderby'] = 'date';
		$lst_args['order']   = 'ASC';
		break;
	case 'price_asc':
		$lst_args['orderby']  = 'meta_value_num';
		$lst_args['meta_key'] = 'price_week';
		$lst_args['order']    = 'ASC';
		break;
	case 'price_desc':
		$lst_args['orderby']  = 'meta_value_num';
		$lst_args['meta_key'] = 'price_week';
		$lst_args['order']    = 'DESC';
		break;
	default: // date_desc
		$lst_args['orderby'] = 'date';
		$lst_args['order']   = 'DESC';
		break;
}
if ( $lst_search ) {
	$lst_args['s'] = $lst_search;
}

$lst_query = new \WP_Query( $lst_args );
$lst_posts = $lst_query->posts;
$lst_pages = (int) ceil( $lst_query->found_posts / $lst_pp );

$badge_cfg = [
	'publish'  => [ 'label' => tb_opt( 'tb_fa_ae_st_publish', 'Zverejnené' ),  'bg' => '#02f797' ],
	'pending'  => [ 'label' => tb_opt( 'tb_fa_ae_st_pending', 'V moderácii' ), 'bg' => '#f4d597' ],
	'rejected' => [ 'label' => tb_opt( 'tb_fa_ae_st_rejected', 'Zamietnuté' ), 'bg' => '#ffbdbd' ],
	'archived' => [ 'label' => tb_opt( 'tb_fa_ae_st_archived', 'Archivované' ),'bg' => '#e5e5e5' ],
	'draft'    => [ 'label' => tb_opt( 'tb_fa_ae_st_draft', 'Koncept' ),    'bg' => '#e5e5e5' ],
];

$detail_base = add_query_arg( [ 'tab' => 'users', 'user' => $view_user_id ], $panel_url );
$lst_edit_url = static fn( int $id ): string => esc_url( add_query_arg( [ 'tab' => 'listings', 'edit' => $id ], $panel_url ) );
?>

<!-- ── Head ──────────────────────────────────────────────────── -->
<div class="tb-fa-edit-head" data-user-id="<?php echo esc_attr( $view_user_id ); ?>">
	<div class="tb-fa-edit-head__info">
		<h1 class="tb-fa-edit-head__title">
			<?php echo esc_html( $user->display_name ?: $user->user_login ); ?>
			<?php if ( $is_blocked ) : ?>
				<span class="tb-fa-badge tb-fa-badge--red"><?php echo esc_html( tb_opt( 'tb_fa_ud_st_blocked', 'Zablokovaný' ) ); ?></span>
			<?php else : ?>
				<span class="tb-fa-badge tb-fa-badge--green"><?php echo esc_html( tb_opt( 'tb_fa_ud_st_active', 'Aktívny' ) ); ?></span>
			<?php endif; ?>
		</h1>
		<span class="tb-fa-edit-head__id">ID: <?php echo esc_html( (string) $view_user_id ); ?></span>
	</div>

	<div class="tb-fa-edit-head__actions tb-fa-edit-head__actions--pc">
		<?php $render_user_actions(); ?>
	</div>
</div>

<!-- ── Profile Card ──────────────────────────────────────────── -->
<div class="tb-fa-profile-grid">

	<!-- Photo card -->
	<div class="tb-fa-profile-photo-card">
		<p class="tb-fa-profile-photo-card__label"><?php echo esc_html( tb_opt( 'tb_fa_ud_photo_title', 'Profilová fotografia' ) ); ?></p>
		<div class="tb-fa-profile-photo-card__body">
			<div class="tb-fa-profile-avatar-wrap">
				<div class="tb-fa-profile-avatar">
					<?php if ( $avatar_url ) : ?>
						<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $user->display_name ); ?>" width="131" height="131">
					<?php else : ?>
						<span class="tb-fa-profile-avatar__char"><?php echo esc_html( $avatar_char ); ?></span>
					<?php endif; ?>
				</div>
				<span class="tb-fa-profile-avatar__camera" aria-hidden="true">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						<circle cx="12" cy="13" r="4" stroke="currentColor" stroke-width="1.5"/>
					</svg>
				</span>
			</div>
			<div class="tb-fa-profile-photo-card__btns">
				<button type="button" class="tb-fa-btn tb-fa-btn--dark tb-fa-btn--lg">
					<?php echo esc_html( tb_opt( 'tb_fa_ud_upload_btn', 'Nahrať nové' ) ); ?>
				</button>
				<button type="button" class="tb-fa-profile-photo-card__delete">
					<?php echo esc_html( tb_opt( 'tb_fa_ud_delete_photo', 'Odstrániť' ) ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Info card -->
	<div class="tb-fa-profile-info-card">
		<h2 class="tb-fa-profile-info-card__label"><?php echo esc_html( tb_opt( 'tb_fa_ud_section_info', 'Základné informácie' ) ); ?></h2>
		<div class="tb-fa-profile-fields">

			<div class="tb-fa-profile-field">
				<span class="tb-fa-profile-field__label"><?php echo esc_html( tb_opt( 'tb_fa_ud_first_name', 'Meno' ) ); ?></span>
				<span class="tb-fa-profile-field__value"><?php echo esc_html( $user->first_name ?: '—' ); ?></span>
			</div>

			<div class="tb-fa-profile-field">
				<span class="tb-fa-profile-field__label"><?php echo esc_html( tb_opt( 'tb_fa_ud_last_name', 'Priezvisko' ) ); ?></span>
				<span class="tb-fa-profile-field__value"><?php echo esc_html( $user->last_name ?: '—' ); ?></span>
			</div>

			<div class="tb-fa-profile-field">
				<span class="tb-fa-profile-field__label"><?php echo esc_html( tb_opt( 'tb_fa_ud_phone', 'Telefón' ) ); ?></span>
				<span class="tb-fa-profile-field__value"><?php echo esc_html( $phone ?: '—' ); ?></span>
			</div>

			<div class="tb-fa-profile-field">
				<span class="tb-fa-profile-field__label"><?php echo esc_html( tb_opt( 'tb_fa_ud_email', 'Email' ) ); ?></span>
				<span class="tb-fa-profile-field__value"><?php echo esc_html( $user->user_email ); ?></span>
			</div>

			<div class="tb-fa-profile-field">
				<span class="tb-fa-profile-field__label"><?php echo esc_html( tb_opt( 'tb_fa_ud_city', 'Mesto' ) ); ?></span>
				<span class="tb-fa-profile-field__value"><?php echo esc_html( $user_city ?: '—' ); ?></span>
			</div>

			<div class="tb-fa-profile-field">
				<span class="tb-fa-profile-field__label"><?php echo esc_html( tb_opt( 'tb_fa_ud_account_type', 'Typ účtu' ) ); ?></span>
				<span class="tb-fa-profile-field__value"><?php echo esc_html( $role_label ); ?></span>
			</div>

			<div class="tb-fa-profile-field">
				<span class="tb-fa-profile-field__label"><?php echo esc_html( tb_opt( 'tb_fa_ud_telegram', 'Telegram' ) ); ?></span>
				<span class="tb-fa-profile-field__value"><?php echo esc_html( $telegram ?: '—' ); ?></span>
			</div>

		</div>

		<!-- Mobile position for the head actions (hidden on wider screens) -->
		<div class="tb-fa-profile-info-card__actions" data-user-id="<?php echo esc_attr( $view_user_id ); ?>">
			<?php $render_user_actions(); ?>
		</div>
	</div>
</div>

<!-- ── User's Listings ───────────────────────────────────────── -->
<div class="tb-fa-section" style="margin-top:32px">
	<div class="tb-fa-page-head">
		<h2 class="tb-fa-page-head__title"><?php echo esc_html( tb_opt( 'tb_fa_ud_listings_title', 'Inzeráty' ) ); ?></h2>
		<p class="tb-fa-page-head__stats">
			<?php
			printf(
				/* translators: %1$d total %2$d published %3$d pending %4$d archived %5$d rejected */
				esc_html( tb_opt( 'tb_fa_ud_stats', 'Celkom %1$d, zverejnených %2$d, v moderácii %3$d, archivovaných %4$d, zamietnutých %5$d' ) ),
				$cnt_user_all, $cnt_pub, $cnt_pend, $cnt_arch, $cnt_rej
			);
			?>
		</p>
	</div>

	<!-- Status pills + search -->
	<div class="tb-fa-toolbar">
		<?php
		$pill_items = [
			''         => tb_opt( 'tb_fa_ud_filter_all', 'Všetky' ),
			'publish'  => tb_opt( 'tb_fa_ud_filter_publish', 'Zverejnené' ),
			'pending'  => tb_opt( 'tb_fa_ud_filter_pending', 'V moderácii' ),
			'rejected' => tb_opt( 'tb_fa_ud_filter_reject', 'Zamietnuté' ),
			'archived' => tb_opt( 'tb_fa_ud_filter_archive', 'Archivované' ),
		];
		// One URL per filter, reused by both the pills and the mobile dropdown.
		$sort_qs   = ( 'date_desc' !== $lst_sort ) ? [ 'lst_sort' => $lst_sort ] : [];
		$pill_urls = [];
		foreach ( $pill_items as $st_key => $st_label ) {
			$pill_urls[ $st_key ] = $st_key
				? esc_url( add_query_arg( array_merge( [ 'lst_status' => $st_key ], $sort_qs ), $detail_base ) )
				: esc_url( add_query_arg( $sort_qs, remove_query_arg( 'lst_status', $detail_base ) ) );
		}
		?>
		<div class="tb-fa-pills">
			<?php foreach ( $pill_items as $st_key => $st_label ) :
				$st_active = ( $lst_status === $st_key );
			?>
			<a href="<?php echo $pill_urls[ $st_key ]; // already escaped ?>" class="tb-fa-pill<?php echo $st_active ? ' is-active' : ''; ?>"
				<?php echo $st_active ? 'aria-current="true"' : ''; ?>>
				<?php echo esc_html( $st_label ); ?>
			</a>
			<?php endforeach; ?>
		</div>

		<!-- Same filter as a dropdown for tablet/mobile -->
		<select
			class="tb-fa-pill-select tb-fa-status-select"
			data-fa-nav-select
			aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_adv_status_label', 'Stav inzerátu' ) ); ?>"
		>
			<?php foreach ( $pill_items as $st_key => $st_label ) :
				// Fuller label for "all" — the dropdown is read on its own.
				$opt_label = $st_key ? $st_label : tb_opt( 'tb_fa_adv_all_long', 'Všetky inzeráty' );
			?>
				<option value="<?php echo $pill_urls[ $st_key ]; // already escaped ?>" <?php selected( $lst_status, $st_key ); ?>>
					<?php echo esc_html( $opt_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<div class="tb-fa-search-wrap">
			<!-- Collapsed search trigger — mobile only (Figma 265:23164) -->
			<button type="button" class="tb-fa-search-toggle" data-fa-search-toggle aria-expanded="false"
				aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_ud_search_label', 'Hľadať inzeráty' ) ); ?>">
				<svg class="tb-fa-search-toggle__open" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 21L16.514 16.506M19 11C19 15.418 15.418 19 11 19C6.582 19 3 15.418 3 11C3 6.582 6.582 3 11 3C15.418 3 19 6.582 19 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
				<svg class="tb-fa-search-toggle__close" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</button>

			<form method="get" action="<?php echo esc_url( $panel_url ); ?>" class="tb-fa-search-form" role="search">
				<input type="hidden" name="tab" value="users">
				<input type="hidden" name="user" value="<?php echo esc_attr( $view_user_id ); ?>">
				<?php if ( $lst_status ) : ?>
					<input type="hidden" name="lst_status" value="<?php echo esc_attr( $lst_status ); ?>">
				<?php endif; ?>
				<?php if ( 'date_desc' !== $lst_sort ) : ?>
					<input type="hidden" name="lst_sort" value="<?php echo esc_attr( $lst_sort ); ?>">
				<?php endif; ?>
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="tb-fa-search-icon"><path d="M21 21L16.514 16.506M19 11C19 15.418 15.418 19 11 19C6.582 19 3 15.418 3 11C3 6.582 6.582 3 11 3C15.418 3 19 6.582 19 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
				<input
					type="search"
					name="lst_s"
					class="tb-fa-search-input"
					value="<?php echo esc_attr( $lst_search ); ?>"
					placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_ud_search_ph', 'Hľadať...' ) ); ?>"
					aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_ud_search_label', 'Hľadať inzeráty' ) ); ?>"
				>
				<button type="submit" class="screen-reader-text"><?php echo esc_html( tb_opt( 'tb_fa_ud_search_btn', 'Hľadať' ) ); ?></button>
			</form>

			<!-- Sort (Figma 214:5865) — same control as the Adverts tab -->
			<div class="tb-fa-sort-drop">
				<button type="button" class="tb-fa-sort-btn" data-fa-sort-toggle aria-haspopup="true" aria-expanded="false" aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_adv_sort_label', 'Zoradiť' ) ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
				</button>
				<div class="tb-fa-sort-panel" role="menu">
					<?php
					$lst_sort_items = [
						'date_desc'  => tb_opt( 'tb_fa_adv_sort_date_desc', 'Najnovšie' ),
						'date_asc'   => tb_opt( 'tb_fa_adv_sort_date_asc', 'Najstaršie' ),
						'price_asc'  => tb_opt( 'tb_fa_adv_sort_price_asc', 'Cena: od najnižšej' ),
						'price_desc' => tb_opt( 'tb_fa_adv_sort_price_desc', 'Cena: od najvyššej' ),
					];
					$lst_sort_base = $lst_status ? add_query_arg( 'lst_status', $lst_status, $detail_base ) : $detail_base;
					if ( $lst_search ) {
						$lst_sort_base = add_query_arg( 'lst_s', $lst_search, $lst_sort_base );
					}
					foreach ( $lst_sort_items as $sk => $sl ) :
						$s_active = ( $lst_sort === $sk );
						$s_url    = esc_url( add_query_arg( 'lst_sort', $sk, remove_query_arg( 'lst_paged', $lst_sort_base ) ) );
						?>
						<a
							href="<?php echo $s_url; // already escaped ?>"
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

	<!-- Listing cards (two-tier mod-card) -->
	<?php if ( empty( $lst_posts ) ) : ?>
		<div class="tb-fa-empty"><?php echo esc_html( tb_opt( 'tb_fa_ud_empty', 'Žiadne inzeráty.' ) ); ?></div>
	<?php else : ?>
	<div class="tb-fa-cards" role="list">
		<?php foreach ( $lst_posts as $p ) :
			$post_id     = $p->ID;
			$title       = $p->post_title ?: tb_opt( 'tb_fa_blg_no_title', '(bez názvu)' );
			$status      = $p->post_status;
			$thumb_url   = get_the_post_thumbnail_url( $post_id, 'medium' );
			$price       = (float) get_post_meta( $post_id, 'price_week', true );
			$city_terms  = get_the_terms( $post_id, 'city' );
			$city_name   = ( $city_terms && ! is_wp_error( $city_terms ) ) ? $city_terms[0]->name : '';
			$author_name = $user->display_name ?: $user->user_login;
			$views       = (int) get_post_meta( $post_id, '_tb_listing_views', true );
			$messages    = (int) get_post_meta( $post_id, '_tb_listing_messages', true );
			$calls       = (int) get_post_meta( $post_id, '_tb_listing_calls', true );
			$badge       = $badge_cfg[ $status ] ?? [ 'label' => $status, 'bg' => '#e5e5e5' ];
		?>
		<article class="tb-fa-mod-card" data-listing-id="<?php echo esc_attr( $post_id ); ?>" role="listitem">

			<!-- Top body -->
			<div class="tb-fa-mod-card__body">
				<div class="tb-fa-mod-card__thumb">
					<?php if ( $thumb_url ) : ?>
						<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" width="140" height="100">
					<?php else : ?>
						<div class="tb-fa-mod-card__thumb-placeholder" aria-hidden="true"></div>
					<?php endif; ?>
				</div>

				<div class="tb-fa-mod-card__info">
					<div class="tb-fa-mod-card__head">
						<span class="tb-fa-mod-card__title"><?php echo esc_html( $title ); ?></span>
						<span class="tb-fa-mod-card__badge" style="background:<?php echo esc_attr( $badge['bg'] ); ?>;">
							<?php echo esc_html( $badge['label'] ); ?>
						</span>
					</div>

					<div class="tb-fa-mod-card__meta">
						<?php if ( $city_name ) : ?>
							<span class="tb-fa-mod-card__meta-item">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
									<path d="M12 21s-7-5.686-7-11a7 7 0 1114 0c0 5.314-7 11-7 11z" stroke="currentColor" stroke-width="1.6"/>
									<circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/>
								</svg>
								<?php echo esc_html( $city_name ); ?>
							</span>
						<?php endif; ?>
						<span class="tb-fa-mod-card__meta-item">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6"/>
								<path d="M5 20a7 7 0 0114 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
							</svg>
							<?php echo esc_html( $author_name ); ?>
						</span>
					</div>

					<div class="tb-fa-mod-card__metrics">
						<span class="tb-fa-mod-card__metric">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.6"/>
								<circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.6"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $views, 'view', '%d zobrazenie', '%d zobrazenia', '%d zobrazení' ) ), (int) $views ); ?>
						</span>
						<span class="tb-fa-mod-card__metric">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M21 11.5a8.38 8.38 0 01-9 8.4L3 21l1.1-3.3A8.38 8.38 0 1121 11.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $messages, 'message', '%d správa', '%d správy', '%d správ' ) ), (int) $messages ); ?>
						</span>
						<span class="tb-fa-mod-card__metric">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3 19.5 19.5 0 01-6-6 19.8 19.8 0 01-3-8.6A2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.3 1.8.6 2.6a2 2 0 01-.5 2.1L8 9.6a16 16 0 006 6l1.2-1.2a2 2 0 012.1-.4c.8.3 1.7.5 2.6.6a2 2 0 011.7 2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $calls, 'call', '%d hovor', '%d hovory', '%d hovorov' ) ), (int) $calls ); ?>
						</span>
					</div>
				</div>

				<?php if ( $price > 0 ) : ?>
					<div class="tb-fa-mod-card__price">
						<?php printf( esc_html( tb_opt( 'tb_fa_adv_price', 'od %s€ / týždeň' ) ), esc_html( number_format( $price, 0, '.', ' ' ) ) ); ?>
					</div>
				<?php endif; ?>

				<!-- ID lives in the body, under the price (Figma 265:22941) -->
				<span class="tb-fa-mod-card__id">ID: <?php echo esc_html( $post_id ); ?></span>
			</div>

			<!-- Bottom bar — actions only -->
			<div class="tb-fa-mod-card__bar">
				<div class="tb-fa-mod-card__actions">
					<?php if ( 'pending' === $status ) : ?>
						<!-- Archive is part of the moderation card in Figma 265:22906 -->
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="archive"><?php echo esc_html( tb_opt( 'tb_fa_ud_archive_btn', 'Archivovať' ) ); ?></button>
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="reject"><?php echo esc_html( tb_opt( 'tb_fa_ud_reject_btn', 'Zamietnuť' ) ); ?></button>
						<a href="<?php echo $lst_edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?></a>
						<button type="button" class="tb-fa-btn tb-fa-btn--dark" data-fa-action="publish"><?php echo esc_html( tb_opt( 'tb_fa_ud_publish_btn', 'Zverejniť' ) ); ?></button>

					<?php elseif ( 'publish' === $status ) : ?>
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="archive"><?php echo esc_html( tb_opt( 'tb_fa_ud_archive_btn', 'Archivovať' ) ); ?></button>
						<a href="<?php echo $lst_edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?></a>

					<?php elseif ( 'rejected' === $status ) : ?>
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="pending"><?php echo esc_html( tb_opt( 'tb_fa_ud_review_btn', 'Na posúdenie' ) ); ?></button>
						<a href="<?php echo $lst_edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?></a>

					<?php elseif ( 'archived' === $status ) : ?>
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="pending"><?php echo esc_html( tb_opt( 'tb_fa_ud_review_btn', 'Na posúdenie' ) ); ?></button>
						<a href="<?php echo $lst_edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?></a>

					<?php else : ?>
						<a href="<?php echo $lst_edit_url( $post_id ); ?>" class="tb-fa-btn tb-fa-btn--outline"><?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?></a>
					<?php endif; ?>
				</div>
			</div>

		</article>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<!-- Load more -->
	<?php if ( $lst_paged < $lst_pages ) : ?>
	<div class="tb-fa-load-more">
		<?php
		// Carry the active status / search / sort over to the next page,
		// otherwise "load more" silently resets the filters.
		$more_url = add_query_arg(
			array_filter( [
				'lst_paged'  => $lst_paged + 1,
				'lst_status' => $lst_status ?: null,
				'lst_s'      => $lst_search ?: null,
				'lst_sort'   => ( 'date_desc' !== $lst_sort ) ? $lst_sort : null,
			] ),
			$detail_base
		);
		?>
		<a href="<?php echo esc_url( $more_url ); ?>"
		   class="tb-fa-btn tb-fa-btn--dark">
			<?php echo esc_html( tb_opt( 'tb_fa_adv_load_more', 'Načítať viac' ) ); ?>
		</a>
	</div>
	<?php endif; ?>
</div>
