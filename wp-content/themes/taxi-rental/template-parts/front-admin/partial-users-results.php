<?php
/**
 * template-parts/front-admin/partial-users-results.php
 * Renders the user rows + load-more + empty state for the Users tab.
 * Included both by tab-users.php (normal page load) and by the
 * tb_fa_filter_users AJAX handler (class-admin.php) — both callers must
 * define: $users, $role_type_labels, $panel_url, $paged, $num_pages,
 * $status_filter, $extra_qs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- ── User list ─────────────────────────────────────────────── -->
<div class="tb-fa-user-list">
	<?php if ( empty( $users ) ) : ?>
		<div class="tb-fa-empty"><?php echo esc_html( tb_opt( 'tb_fa_usr_empty', 'Žiadni používatelia.' ) ); ?></div>
	<?php else : ?>
		<?php foreach ( $users as $u ) :
			$is_blocked    = '1' === get_user_meta( $u->ID, 'tb_user_blocked', true );
			$primary_role  = ! empty( $u->roles ) ? $u->roles[0] : 'subscriber';
			$role_label    = $role_type_labels[ $primary_role ] ?? ucfirst( $primary_role );
			$phone         = (string) get_user_meta( $u->ID, 'phone_number', true );
			$user_city_val = (string) get_user_meta( $u->ID, 'city', true );
			// count_user_posts() counts public statuses only, so drafts,
			// pending, rejected and archived went missing — the row showed a
			// smaller number than the user's own detail page.
			$listing_count = tb_listing_count( [ 'publish', 'pending', 'draft', 'rejected', 'archived' ], (int) $u->ID );
			$avatar_id_val = tb_user_avatar_id( (int) $u->ID );
			$avatar_url_v  = $avatar_id_val ? (string) wp_get_attachment_image_url( $avatar_id_val, 'thumbnail' ) : '';
			$avatar_char   = strtoupper( mb_substr( $u->display_name ?: $u->user_login, 0, 1 ) );
			$detail_url    = esc_url( add_query_arg( [ 'tab' => 'users', 'user' => $u->ID ], $panel_url ) );
			$edit_url      = esc_url( add_query_arg( [ 'tab' => 'users', 'user' => $u->ID, 'action' => 'edit' ], $panel_url ) );
		?>
		<article class="tb-fa-user-row" data-user-id="<?php echo esc_attr( $u->ID ); ?>">

			<!-- Upper body: avatar + info + listing count -->
			<div class="tb-fa-user-row__body">
				<div class="tb-fa-user-avatar-link">
					<div class="tb-fa-user-avatar tb-fa-user-avatar--lg">
						<?php if ( $avatar_url_v ) : ?>
							<img src="<?php echo esc_url( $avatar_url_v ); ?>" alt="<?php echo esc_attr( $u->display_name ); ?>" width="100" height="100">
						<?php else : ?>
							<?php echo esc_html( $avatar_char ); ?>
						<?php endif; ?>
					</div>
				</div>

				<div class="tb-fa-user-row__info">
					<div class="tb-fa-user-row__name-wrap">
						<a href="<?php echo $detail_url; ?>" class="tb-fa-user-row__name">
							<?php echo esc_html( $u->display_name ?: $u->user_login ); ?>
						</a>
						<?php if ( $is_blocked ) : ?>
							<span class="tb-fa-badge tb-fa-badge--red"><?php echo esc_html( tb_opt( 'tb_fa_ud_st_blocked', 'Zablokovaný' ) ); ?></span>
						<?php else : ?>
							<span class="tb-fa-badge tb-fa-badge--green"><?php echo esc_html( tb_opt( 'tb_fa_ud_st_active', 'Aktívny' ) ); ?></span>
						<?php endif; ?>
					</div>

					<div class="tb-fa-user-row__meta">
						<?php if ( $user_city_val ) : ?>
						<span class="tb-fa-user-row__meta-item">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M12 21s-7-5.686-7-11a7 7 0 1114 0c0 5.314-7 11-7 11z" stroke="currentColor" stroke-width="1.6"/>
								<circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/>
							</svg>
							<?php echo esc_html( $user_city_val ); ?>
						</span>
						<?php endif; ?>
						<span class="tb-fa-user-row__meta-item">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.6"/>
								<path d="M5 20a7 7 0 0114 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
							</svg>
							<?php echo esc_html( $role_label ); ?>
						</span>
					</div>

					<div class="tb-fa-user-row__meta tb-fa-user-row__meta2">
						<?php if ( $phone ) : ?>
						<span class="tb-fa-user-row__meta-item">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3 19.5 19.5 0 01-6-6 19.8 19.8 0 01-3-8.6A2 2 0 014.1 2h3a2 2 0 012 1.7c.1.9.3 1.8.6 2.6a2 2 0 01-.5 2.1L8 9.6a16 16 0 006 6l1.2-1.2a2 2 0 012.1-.4c.8.3 1.7.5 2.6.6a2 2 0 011.7 2z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							</svg>
							<?php echo esc_html( $phone ); ?>
						</span>
						<?php endif; ?>
						<span class="tb-fa-user-row__meta-item">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M21 11.5a8.38 8.38 0 01-9 8.4L3 21l1.1-3.3A8.38 8.38 0 1121 11.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
							</svg>
							<?php echo esc_html( $u->user_email ); ?>
						</span>
					</div>
				</div>

				<div class="tb-fa-user-row__count-col">
					<span class="tb-fa-user-row__count">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.6"/>
							<circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.6"/>
						</svg>
						<?php printf( esc_html( tb_plural_opt( (int) $listing_count, 'listing', '%d inzerát', '%d inzeráty', '%d inzerátov' ) ), (int) $listing_count ); ?>
					</span>
				</div>
			</div>

			<!-- Bottom bar: ID + actions -->
			<div class="tb-fa-user-row__bar">
				<span class="tb-fa-user-row__id">ID: <?php echo esc_html( (string) $u->ID ); ?></span>
				<div class="tb-fa-user-row__actions">
					<?php if ( $is_blocked ) : ?>
						<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="unblock">
							<?php echo esc_html( tb_opt( 'tb_fa_usr_unblock_btn', 'Odblokovať' ) ); ?>
						</button>
					<?php else : ?>
						<button type="button" class="tb-fa-btn tb-fa-btn--dark" data-fa-action="block">
							<?php echo esc_html( tb_opt( 'tb_fa_usr_block_btn', 'Zablokovať' ) ); ?>
						</button>
					<?php endif; ?>
					<a href="<?php echo $edit_url; ?>" class="tb-fa-btn tb-fa-btn--outline">
						<?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?>
					</a>
					<a href="<?php echo $detail_url; ?>" class="tb-fa-btn tb-fa-btn--outline">
						<?php echo esc_html( tb_opt( 'tb_fa_ud_view_btn', 'Zobraziť' ) ); ?>
					</a>
				</div>
			</div>

		</article>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

<!-- ── Load more ──────────────────────────────────────────────── -->
<?php if ( $paged < $num_pages ) :
	$next_page = $paged + 1;
	$next_url  = add_query_arg( array_merge( [ 'tab' => 'users', 'status' => $status_filter ?: null, 'paged' => $next_page ], $extra_qs ), $panel_url );
	?>
<div class="tb-fa-load-more">
	<a href="<?php echo esc_url( $next_url ); ?>"
	   class="tb-fa-btn tb-fa-btn--dark"
	   data-fa-page="<?php echo esc_attr( $next_page ); ?>">
		<?php echo esc_html( tb_opt( 'tb_fa_adv_load_more', 'Načítať viac' ) ); ?>
	</a>
</div>
<?php endif; ?>
