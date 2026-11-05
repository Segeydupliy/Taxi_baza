<?php
/**
 * Front-admin tab: Блог/Новини.
 * Posts list + WP blog categories section below.
 * Inherits: $panel_url, $tab.
 */

$status_filter = sanitize_key( wp_unslash( $_GET['bstatus'] ?? '' ) );
$search        = sanitize_text_field( wp_unslash( $_GET['bs'] ?? '' ) );
$paged         = max( 1, absint( wp_unslash( $_GET['bpaged'] ?? 1 ) ) );
$per_page      = 10;

$allowed_statuses = [ 'publish', 'draft', 'pending', 'trash' ];
$query_statuses   = ( $status_filter && in_array( $status_filter, $allowed_statuses, true ) )
	? [ $status_filter ]
	: [ 'publish', 'draft', 'pending' ];

$query_args = [
	'post_type'      => 'post',
	'post_status'    => $query_statuses,
	'posts_per_page' => $per_page,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
];
if ( $search ) {
	$query_args['s'] = $search;
}
$q           = new \WP_Query( $query_args );
$posts       = $q->posts;
$total       = $q->found_posts;
$total_pages = (int) $q->max_num_pages;

// Blog categories.
$blog_cats = get_terms( [
	'taxonomy'   => 'category',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );
if ( is_wp_error( $blog_cats ) ) {
	$blog_cats = [];
}

$new_post_url = add_query_arg( [ 'tab' => 'blog', 'new' => '1' ], $panel_url );
$show_posted  = ! empty( $_GET['posted'] );

$status_labels = [
	'publish' => [ 'label' => tb_opt( 'tb_fa_ae_st_publish', 'Zverejnené' ),  'class' => 'tb-fa-badge--green' ],
	'draft'   => [ 'label' => tb_opt( 'tb_fa_ae_st_draft', 'Koncept' ),     'class' => 'tb-fa-badge--gray' ],
	'pending' => [ 'label' => tb_opt( 'tb_fa_blg_review', 'Na posúdenie' ),  'class' => 'tb-fa-badge--yellow' ],
	'trash'   => [ 'label' => tb_opt( 'tb_fa_blg_trash', 'Kôš' ),           'class' => 'tb-fa-badge--red' ],
];
?>

<?php if ( $show_posted ) : ?>
<div class="tb-fa-notify" role="status" aria-live="polite">
	<div class="tb-fa-notify__icon" aria-hidden="true">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17L4 12" stroke="black" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</div>
	<span class="tb-fa-notify__text"><?php echo esc_html( tb_opt( 'tb_fa_blg_toast_added', 'Článok bol pridaný' ) ); ?></span>
</div>
<?php endif; ?>

<!-- ── Page head ──────────────────────────────────────────────────── -->
<div class="tb-fa-page-head tb-fa-page-head--with-action">
	<div>
		<h1 class="tb-fa-page-head__title"><?php echo esc_html( tb_opt( 'tb_fa_blg_heading', 'Blog / Správy' ) ); ?></h1>
		<p class="tb-fa-page-head__stats">
			<?php printf(
				/* translators: 1: posts count, 2: categories count */
				esc_html( tb_opt( 'tb_fa_blg_stats', '%1$s článkov, %2$s kategórií' ) ),
				esc_html( $total ),
				esc_html( count( $blog_cats ) )
			); ?>
		</p>
	</div>
	<a href="<?php echo esc_url( $new_post_url ); ?>" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--icon">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
		</svg>
		<?php echo esc_html( tb_opt( 'tb_fa_blg_add_btn', 'Pridať článok' ) ); ?>
	</a>
</div>

<!-- ── Posts list ────────────────────────────────────────────────── -->
<div class="tb-fa-blog-list" id="tb-fa-blog-list">
	<?php if ( empty( $posts ) ) : ?>
		<div class="tb-fa-empty"><?php echo esc_html( tb_opt( 'tb_fa_blg_empty', 'Žiadne články nenájdené.' ) ); ?></div>
	<?php else : ?>
		<?php foreach ( $posts as $post ) :
			$thumb_url = get_the_post_thumbnail_url( $post->ID, [ 140, 100 ] );
			$cats      = get_the_category( $post->ID );
			$cat_name  = $cats ? $cats[0]->name : '—';
			$st        = $post->post_status;
			$badge     = $status_labels[ $st ] ?? [ 'label' => $st, 'class' => 'tb-fa-badge--gray' ];
			$edit_url  = add_query_arg( [ 'tab' => 'blog', 'edit' => $post->ID ], $panel_url );
			$post_date = date_i18n( get_option( 'date_format' ), strtotime( $post->post_date ) );
			$views     = (int) get_post_meta( $post->ID, 'tb_views', true );
			$shares    = (int) get_post_meta( $post->ID, 'tb_shares', true );
		?>
		<article class="tb-fa-blog-card" data-post-id="<?php echo esc_attr( $post->ID ); ?>">

			<div class="tb-fa-blog-card__body">

				<a href="<?php echo esc_url( $edit_url ); ?>"
				   class="tb-fa-blog-card__thumb<?php echo $thumb_url ? '' : ' tb-fa-blog-card__thumb--empty'; ?>"
				   aria-hidden="true" tabindex="-1">
					<?php if ( $thumb_url ) : ?>
						<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" width="140" height="100" loading="lazy">
					<?php else : ?>
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
							<circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="1.5"/>
							<path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="1.5"/>
						</svg>
					<?php endif; ?>
				</a>

				<div class="tb-fa-blog-card__info">

					<div class="tb-fa-blog-card__title-row">
						<a href="<?php echo esc_url( $edit_url ); ?>" class="tb-fa-blog-card__title">
							<?php echo esc_html( $post->post_title ?: tb_opt( 'tb_fa_blg_no_title', '(bez názvu)' ) ); ?>
						</a>
						<span class="tb-fa-badge <?php echo esc_attr( $badge['class'] ); ?>">
							<?php echo esc_html( $badge['label'] ); ?>
						</span>
					</div>

					<div class="tb-fa-blog-card__meta-row">
						<span class="tb-fa-blog-card__meta-item">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V9a2 2 0 012-2h4l2-3h4l2 3h4a2 2 0 012 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
							</svg>
							<?php echo esc_html( $cat_name ); ?>
						</span>
						<span class="tb-fa-blog-card__meta-item">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
								<path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
								<circle cx="17" cy="17" r="2.5" stroke="currentColor" stroke-width="1.5"/>
								<path d="M17 16v1.5l1 .5" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
							<?php echo esc_html( $post_date ); ?>
						</span>
					</div>

					<div class="tb-fa-blog-card__stats-row">
						<span class="tb-fa-blog-card__stat">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.5"/>
								<circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.5"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $views, 'view', '%d zobrazenie', '%d zobrazenia', '%d zobrazení' ) ), (int) $views ); ?>
						</span>
						<span class="tb-fa-blog-card__stat">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="1.5"/>
								<circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
								<circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="1.5"/>
								<path d="M8.59 13.51l6.83 3.98M15.41 6.51L8.59 10.49" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $shares, 'share', '%d zdieľanie', '%d zdieľania', '%d zdieľaní' ) ), (int) $shares ); ?>
						</span>
					</div>

				</div>
			</div>

			<div class="tb-fa-blog-card__bar">
				<span class="tb-fa-blog-card__slug">/<?php echo esc_html( $post->post_name ); ?></span>
				<div class="tb-fa-blog-card__actions">
					<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-fa-action="delete-post"
						data-post-id="<?php echo esc_attr( $post->ID ); ?>">
						<?php echo esc_html( tb_opt( 'tb_fa_blg_delete_btn', 'Odstrániť' ) ); ?>
					</button>
					<a href="<?php echo esc_url( $edit_url ); ?>" class="tb-fa-btn tb-fa-btn--dark">
						<?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?>
					</a>
				</div>
			</div>

		</article>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

<!-- ── Load more ─────────────────────────────────────────────────── -->
<?php if ( $paged < $total_pages ) :
	$next_url = add_query_arg( [
		'tab'     => 'blog',
		'bpaged'  => $paged + 1,
		'bstatus' => $status_filter ?: null,
		'bs'      => $search ?: null,
	], $panel_url );
?>
<div class="tb-fa-blog-load-more">
	<a href="<?php echo esc_url( $next_url ); ?>" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--load-more">
		<?php echo esc_html( tb_opt( 'tb_fa_blg_load_more', 'Načítať viac' ) ); ?>
	</a>
</div>
<?php endif; ?>

<!-- ── Categories section ─────────────────────────────────────────── -->
<section class="tb-fa-blog-cats-section">
	<div class="tb-fa-page-head tb-fa-page-head--with-action">
		<div>
			<h2 class="tb-fa-page-head__title">
				<?php echo esc_html( tb_opt( 'tb_fa_cat_heading', 'Kategórie' ) ); ?>
			</h2>
			<p class="tb-fa-page-head__stats">
				<?php printf(
					/* translators: %s: categories count */
					esc_html( tb_opt( 'tb_fa_blg_cat_count', '%s kategórií' ) ),
					esc_html( count( $blog_cats ) )
				); ?>
			</p>
		</div>
		<button type="button" id="tb-fa-add-blog-cat-btn" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--icon">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
				<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
			<?php echo esc_html( tb_opt( 'tb_fa_cat_add_btn', 'Pridať kategóriu' ) ); ?>
		</button>
	</div>

	<?php if ( empty( $blog_cats ) ) : ?>
		<div class="tb-fa-empty"><?php echo esc_html( tb_opt( 'tb_fa_blg_cat_empty', 'Žiadne kategórie.' ) ); ?></div>
	<?php else : ?>
	<ul class="tb-fa-blog-cat-list" role="list">
		<?php foreach ( $blog_cats as $cat ) : ?>
		<li class="tb-fa-blog-cat-row" data-term-id="<?php echo esc_attr( $cat->term_id ); ?>"
		    data-name="<?php echo esc_attr( $cat->name ); ?>">
			<span class="tb-fa-blog-cat-row__name"><?php echo esc_html( $cat->name ); ?></span>
			<div class="tb-fa-blog-cat-row__actions">
				<button type="button" class="tb-fa-icon-action" data-blog-cat-action="edit"
					aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?>">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
				<button type="button" class="tb-fa-icon-action tb-fa-icon-action--del" data-blog-cat-action="delete"
					aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_cat_delete_btn', 'Odstrániť kategóriu' ) ); ?>">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
					</svg>
				</button>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
</section>
