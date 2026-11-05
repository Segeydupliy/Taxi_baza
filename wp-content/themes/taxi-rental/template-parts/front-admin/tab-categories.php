<?php
/**
 * Front-admin tab: Категорії (ride_category).
 * Two-column grid: each top-level term = one platform block.
 * Inherits from page-front-admin.php: $panel_url, $tab.
 */

$parent_terms = get_terms( [
	'taxonomy'   => 'ride_category',
	'parent'     => 0,
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );
if ( is_wp_error( $parent_terms ) ) {
	$parent_terms = [];
}

// Collect children per parent in one pass.
$platform_data = [];
foreach ( $parent_terms as $pt ) {
	$children = get_terms( [
		'taxonomy'   => 'ride_category',
		'parent'     => $pt->term_id,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	] );
	if ( is_wp_error( $children ) ) {
		$children = [];
	}
	$platform_data[] = [
		'term'     => $pt,
		'children' => $children,
	];
}

// Subtitle: "X Uber категорій, Y Bolt категорій".
$subtitle_parts = [];
foreach ( $platform_data as $pd ) {
	$cnt              = count( $pd['children'] );
	$subtitle_parts[] = $cnt . ' ' . esc_html( $pd['term']->name ) . ' ' . esc_html( tb_plural_opt( (int) $cnt, 'category', 'kategória', 'kategórie', 'kategórií' ) );
}
?>

<!-- ── Page head ──────────────────────────────────────────────────── -->
<div class="tb-fa-page-head tb-fa-page-head--with-action">
	<div>
		<h1 class="tb-fa-page-head__title"><?php echo esc_html( tb_opt( 'tb_fa_cat_heading', 'Kategórie' ) ); ?></h1>
		<?php if ( $subtitle_parts ) : ?>
		<p class="tb-fa-page-head__stats">
			<?php echo implode( ', ', $subtitle_parts ); // already escaped above ?>
		</p>
		<?php endif; ?>
	</div>
	<button type="button" id="tb-fa-add-cat-btn" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--icon">
		<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
		</svg>
		<?php echo esc_html( tb_opt( 'tb_fa_cat_add_btn', 'Pridať kategóriu' ) ); ?>
	</button>
</div>

<!-- ── Categories grid ──────────────────────────────────────────── -->
<?php if ( empty( $platform_data ) ) : ?>
	<div class="tb-fa-empty"><?php echo esc_html( tb_opt( 'tb_fa_cat_empty', 'Ziadne kategorie.' ) ); ?></div>
<?php else : ?>
<div class="tb-fa-cat-grid">
	<?php foreach ( $platform_data as $pd ) :
		$pt            = $pd['term'];
		$children      = $pd['children'];
		$children_data = array_values( array_map( static function ( \WP_Term $c ) {
			return [ 'id' => $c->term_id, 'name' => $c->name ];
		}, $children ) );
	?>
	<div class="tb-fa-cat-block"
		data-term-id="<?php echo esc_attr( $pt->term_id ); ?>"
		data-name="<?php echo esc_attr( $pt->name ); ?>"
		data-children="<?php echo esc_attr( wp_json_encode( $children_data ) ); ?>">

		<div class="tb-fa-cat-block__header">
			<span class="tb-fa-cat-block__title"><?php echo esc_html( $pt->name ); ?></span>
		</div>

		<ul class="tb-fa-cat-list" aria-label="<?php echo esc_attr( $pt->name ); ?>">
			<?php if ( empty( $children ) ) : ?>
				<li class="tb-fa-cat-item tb-fa-cat-item--empty">
					<span><?php echo esc_html( tb_opt( 'tb_fa_cat_no_sub', 'Žiadne podkategórie' ) ); ?></span>
				</li>
			<?php else : ?>
				<?php foreach ( $children as $child ) : ?>
				<li class="tb-fa-cat-item" data-term-id="<?php echo esc_attr( $child->term_id ); ?>">
					<span class="tb-fa-cat-item__name"><?php echo esc_html( $child->name ); ?></span>
					<button type="button"
						class="tb-fa-cat-item__del"
						data-cat-action="delete-child"
						data-term-id="<?php echo esc_attr( $child->term_id ); ?>"
						aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_cat_delete_btn', 'Odstraniť kategóriu' ) ); ?>">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
						</svg>
					</button>
				</li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>

		<div class="tb-fa-cat-block__footer">
			<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-cat-action="delete">
				<?php echo esc_html( tb_opt( 'tb_fa_cat_delete_btn', 'Odstraniť' ) ); ?>
			</button>
			<button type="button" class="tb-fa-btn tb-fa-btn--dark" data-cat-action="edit">
				<?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?>
			</button>
		</div>

	</div>
	<?php endforeach; ?>
</div>
<?php endif; ?>
