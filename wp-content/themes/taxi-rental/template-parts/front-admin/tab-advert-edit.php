<?php
/**
 * template-parts/front-admin/tab-advert-edit.php
 * Custom (non-ACF) edit form for a car_listing — pixel-matched to Figma.
 * Saves via AJAX action tb_fa_save_listing (writes ACF fields by key + taxonomies).
 * Variable $edit_post_id is in scope from the parent template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_post = $edit_post_id ? get_post( $edit_post_id ) : null;
if ( ! $edit_post || 'car_listing' !== $edit_post->post_type ) {
	echo '<div class="tb-fa-empty">' . esc_html( tb_opt( 'tb_fa_ae_not_found', 'Inzerát sa nenašiel.' ) ) . '</div>';
	return;
}

if ( ! function_exists( 'get_field' ) ) {
	echo '<div class="tb-fa-empty">' . esc_html( tb_opt( 'tb_fa_ae_no_acf', 'ACF Pro nie je aktivované.' ) ) . '</div>';
	return;
}

$post_status = $edit_post->post_status;
$post_title  = get_the_title( $edit_post_id );
$back_url    = esc_url( add_query_arg( 'tab', 'listings', remove_query_arg( 'edit', $panel_url ) ) );
$return_url  = esc_url( add_query_arg( [ 'tab' => 'listings', 'edit' => $edit_post_id, 'updated' => '1' ], $panel_url ) );
$show_saved  = ! empty( $_GET['updated'] );

$badge_cfg = [
	'publish'  => [ 'label' => tb_opt( 'tb_fa_ae_st_publish', 'Zverejnené' ),  'bg' => '#02f797' ],
	'pending'  => [ 'label' => tb_opt( 'tb_fa_ae_st_pending', 'V moderácii' ), 'bg' => '#f4d597' ],
	'rejected' => [ 'label' => tb_opt( 'tb_fa_ae_st_rejected', 'Zamietnuté' ), 'bg' => '#ffbdbd' ],
	'archived' => [ 'label' => tb_opt( 'tb_fa_ae_st_archived', 'Archivované' ),'bg' => '#e5e5e5' ],
	'draft'    => [ 'label' => tb_opt( 'tb_fa_ae_st_draft', 'Koncept' ),    'bg' => '#e5e5e5' ],
];
$badge = $badge_cfg[ $post_status ] ?? [ 'label' => $post_status, 'bg' => '#e5e5e5' ];

// ── Current field values ────────────────────────────────────────
$v_make        = (string) get_field( 'make', $edit_post_id );
$v_model       = (string) get_field( 'model', $edit_post_id );
$v_year        = (int) get_field( 'year', $edit_post_id );
$v_consumption = (string) get_field( 'consumption_100km', $edit_post_id );
$v_price       = (string) get_field( 'price_week', $edit_post_id );
$v_deposit     = (string) get_field( 'deposit', $edit_post_id );
$v_mileage     = (string) get_field( 'mileage_limit_month', $edit_post_id );
$v_min_term    = (string) get_field( 'min_term_days', $edit_post_id );
$v_desc        = (string) get_field( 'short_description', $edit_post_id );

$engine_obj    = get_field_object( 'field_tb_engine_type', $edit_post_id );
$engine_choices = $engine_obj['choices'] ?? [];
$engine_val     = (string) ( $engine_obj['value'] ?? '' );

$trans_obj     = get_field_object( 'field_tb_transmission', $edit_post_id );
$trans_choices = $trans_obj['choices'] ?? [];
$trans_val     = (string) ( $trans_obj['value'] ?? '' );

$insurance_on   = in_array( (string) get_field( 'insurance', $edit_post_id ), [ 'yes', 'details' ], true );
$maintenance_on = in_array( (string) get_field( 'maintenance_included', $edit_post_id ), [ 'yes', 'details' ], true );

// Make/model term selectors (car_make taxonomy).
$make_parents = get_terms( [ 'taxonomy' => 'car_make', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name' ] );
if ( is_wp_error( $make_parents ) ) {
	$make_parents = [];
}
$models_by_make = [];
foreach ( $make_parents as $mk ) {
	$kids                           = get_terms( [ 'taxonomy' => 'car_make', 'parent' => $mk->term_id, 'hide_empty' => false, 'orderby' => 'name' ] );
	$models_by_make[ $mk->term_id ] = is_wp_error( $kids ) ? [] : $kids;
}
$cur_make_id  = 0;
$cur_model_id = 0;
$assigned_makes = wp_get_object_terms( $edit_post_id, 'car_make' );
if ( ! is_wp_error( $assigned_makes ) ) {
	foreach ( $assigned_makes as $t ) {
		if ( 0 === (int) $t->parent ) {
			$cur_make_id = (int) $t->term_id;
		} else {
			$cur_model_id = (int) $t->term_id;
		}
	}
}
if ( ! $cur_make_id && '' !== $v_make ) {
	$mt = get_term_by( 'name', $v_make, 'car_make' );
	if ( $mt && 0 === (int) $mt->parent ) {
		$cur_make_id = (int) $mt->term_id;
	}
}
if ( $cur_make_id && ! $cur_model_id && '' !== $v_model ) {
	$mt = get_term_by( 'name', $v_model, 'car_make' );
	if ( $mt ) {
		$cur_model_id = (int) $mt->term_id;
	}
}

// Gallery → [ id, thumb url ]
$gallery_raw   = get_field( 'gallery', $edit_post_id );
$gallery_items = [];
foreach ( (array) $gallery_raw as $img ) {
	if ( is_array( $img ) && ! empty( $img['ID'] ) ) {
		$gid = (int) $img['ID'];
		$url = $img['sizes']['thumbnail'] ?? ( $img['url'] ?? '' );
	} elseif ( is_numeric( $img ) ) {
		$gid = (int) $img;
		$url = (string) wp_get_attachment_image_url( $gid, 'thumbnail' );
	} else {
		continue;
	}
	$gallery_items[] = [ 'id' => $gid, 'url' => $url ];
}
$gallery_csv = implode( ',', array_map( static fn( $i ) => $i['id'], $gallery_items ) );

// ── Taxonomies ──────────────────────────────────────────────────
$city_terms = get_terms( [ 'taxonomy' => 'city', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
if ( is_wp_error( $city_terms ) ) {
	$city_terms = [];
}
$cur_city     = wp_get_object_terms( $edit_post_id, 'city', [ 'fields' => 'ids' ] );
$cur_city_id  = ( $cur_city && ! is_wp_error( $cur_city ) ) ? (int) $cur_city[0] : 0;

$cur_cats = wp_get_object_terms( $edit_post_id, 'ride_category', [ 'fields' => 'ids' ] );
$cur_cats = is_wp_error( $cur_cats ) ? [] : array_map( 'intval', $cur_cats );

$rc_parents = get_terms( [ 'taxonomy' => 'ride_category', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
if ( is_wp_error( $rc_parents ) ) {
	$rc_parents = [];
}
$chevron_svg = '<svg class="tb-fa-select-chevron" width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>

<?php if ( $show_saved ) : ?>
	<div class="tb-fa-inline-toast is-visible" role="status">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<?php echo esc_html( tb_opt( 'tb_fa_ue_saved_notice', 'Zmeny uložené' ) ); ?>
	</div>
<?php endif; ?>

<!-- ─── Edit head ─────────────────────────────────────────────── -->
<div class="tb-fa-edit-head">
	<div class="tb-fa-edit-head__info">
		<h1 class="tb-fa-edit-head__title">
			<?php echo esc_html( $post_title ); ?>
			<span class="tb-fa-list-row__badge" style="background:<?php echo esc_attr( $badge['bg'] ); ?>;">
				<?php echo esc_html( $badge['label'] ); ?>
			</span>
		</h1>
		<span class="tb-fa-edit-head__id">ID: <?php echo esc_html( $edit_post_id ); ?></span>
	</div>

	<!-- --form: hidden below 1024px, .tb-fa-edit-foot repeats these buttons -->
	<div class="tb-fa-edit-head__actions tb-fa-edit-head__actions--form" data-listing-id="<?php echo esc_attr( $edit_post_id ); ?>">
		<a href="<?php echo $back_url; ?>" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--lg">
			<?php echo esc_html( tb_opt( 'tb_fa_ae_cancel_btn', 'Zrušiť zmeny' ) ); ?>
		</a>
		<?php if ( 'archived' !== $post_status ) : ?>
			<button type="button" class="tb-fa-btn tb-fa-btn--dark tb-fa-btn--lg" data-fa-action="archive" data-redirect="<?php echo $back_url; ?>">
				<?php echo esc_html( tb_opt( 'tb_fa_ae_archive_btn', 'Archivovať' ) ); ?>
			</button>
		<?php else : ?>
			<button type="button" class="tb-fa-btn tb-fa-btn--dark tb-fa-btn--lg" data-fa-action="pending" data-redirect="<?php echo $back_url; ?>">
				<?php echo esc_html( tb_opt( 'tb_fa_ae_restore_btn', 'Obnoviť' ) ); ?>
			</button>
		<?php endif; ?>
		<button type="submit" form="tb-fa-listing-form" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--lg">
			<?php echo esc_html( tb_opt( 'tb_fa_ae_publish_btn', 'Zverejniť' ) ); ?>
		</button>
	</div>
</div>

<!-- ─── Custom listing form ───────────────────────────────────── -->
<form id="tb-fa-listing-form" class="tb-fa-listing-form"
	data-post-id="<?php echo esc_attr( $edit_post_id ); ?>"
	data-redirect="<?php echo esc_attr( $return_url ); ?>">

	<?php wp_nonce_field( 'tb_front_admin_nonce', 'tb_fa_listing_nonce' ); ?>

	<!-- ── Section: Базова інформація ── -->
	<section class="tb-fa-fs">
		<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_fa_ae_section_basic', 'Základné informácie' ) ); ?></h2>
		<div class="tb-fa-fs__fields">
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-make"><?php echo esc_html( tb_opt( 'tb_fa_ae_make', 'Značka auta' ) ); ?> <span class="tb-fa-required">*</span></label>
				<div class="tb-fa-select-wrap">
					<select class="tb-fa-fld__select" id="tb-l-make" name="make_term" data-make-select required>
						<option value=""><?php echo esc_html( tb_opt( 'tb_fa_ae_select_ph', 'Vyberte prosím' ) ); ?></option>
						<?php foreach ( $make_parents as $mk ) : ?>
							<option value="<?php echo esc_attr( $mk->term_id ); ?>" <?php selected( $cur_make_id, $mk->term_id ); ?>><?php echo esc_html( $mk->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-model"><?php echo esc_html( tb_opt( 'tb_fa_ae_model', 'Model' ) ); ?> <span class="tb-fa-required">*</span></label>
				<div class="tb-fa-select-wrap">
					<select class="tb-fa-fld__select" id="tb-l-model" name="model_term" data-model-select required>
						<option value=""><?php echo esc_html( tb_opt( 'tb_fa_ae_select_ph', 'Vyberte prosím' ) ); ?></option>
						<?php foreach ( $models_by_make as $make_id => $models ) : ?>
							<?php foreach ( $models as $model_term ) : ?>
							<option value="<?php echo esc_attr( $model_term->term_id ); ?>"
								data-make="<?php echo esc_attr( $make_id ); ?>"
								<?php selected( $cur_model_id, $model_term->term_id ); ?>><?php echo esc_html( $model_term->name ); ?></option>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</select>
					<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-year"><?php echo esc_html( tb_opt( 'tb_fa_ae_year', 'Rok výroby' ) ); ?> <span class="tb-fa-required">*</span></label>
				<div class="tb-fa-select-wrap">
					<select class="tb-fa-fld__select" id="tb-l-year" name="year" required>
						<option value=""><?php echo esc_html( tb_opt( 'tb_fa_ae_select_ph', 'Vyberte prosím' ) ); ?></option>
						<?php for ( $yr = 2030; $yr >= 1990; $yr-- ) : ?>
							<option value="<?php echo esc_attr( $yr ); ?>" <?php selected( $v_year, $yr ); ?>><?php echo esc_html( $yr ); ?></option>
						<?php endfor; ?>
					</select>
					<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-engine"><?php echo esc_html( tb_opt( 'tb_fa_ae_engine', 'Typ motora' ) ); ?> <span class="tb-fa-required">*</span></label>
				<div class="tb-fa-select-wrap">
					<select class="tb-fa-fld__select" id="tb-l-engine" name="engine_type" required>
						<option value="" <?php selected( $engine_val, '' ); ?>><?php echo esc_html( tb_opt( 'tb_fa_ae_select_ph', 'Vyberte prosím' ) ); ?></option>
						<?php foreach ( $engine_choices as $ck => $cl ) : ?>
							<option value="<?php echo esc_attr( $ck ); ?>" <?php selected( $engine_val, $ck ); ?>><?php echo esc_html( $cl ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-trans"><?php echo esc_html( tb_opt( 'tb_fa_ae_trans', 'Prevodovka' ) ); ?> <span class="tb-fa-required">*</span></label>
				<div class="tb-fa-select-wrap">
					<select class="tb-fa-fld__select" id="tb-l-trans" name="transmission" required>
						<option value="" <?php selected( $trans_val, '' ); ?>><?php echo esc_html( tb_opt( 'tb_fa_ae_select_ph', 'Vyberte prosím' ) ); ?></option>
						<?php foreach ( $trans_choices as $ck => $cl ) : ?>
							<option value="<?php echo esc_attr( $ck ); ?>" <?php selected( $trans_val, $ck ); ?>><?php echo esc_html( $cl ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-cons"><?php echo esc_html( tb_opt( 'tb_fa_ae_consumption', 'Spotreba (l/100km)' ) ); ?></label>
				<input class="tb-fa-fld__input" type="number" step="0.1" id="tb-l-cons" name="consumption_100km" value="<?php echo esc_attr( $v_consumption ); ?>" placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_ae_consumption_ph', 'Napr. 8l/100km' ) ); ?>">
			</div>
		</div>
	</section>

	<!-- ── Section: Фото ── -->
	<section class="tb-fa-fs">
		<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_fa_ae_photos', 'Fotografie' ) ); ?></h2>
		<p class="tb-fa-fs__hint"><?php echo esc_html( tb_opt( 'tb_fa_ae_photos_hint', 'Prvá fotografia bude na obálke inzerátu. Poradie obrázkov zatiaľ nie je možné meniť.' ) ); ?></p>
		<input type="hidden" id="tb-l-gallery" name="gallery" value="<?php echo esc_attr( $gallery_csv ); ?>">
		<div class="tb-fa-gallery" id="tb-l-gallery-grid">
			<?php foreach ( $gallery_items as $gi ) : ?>
				<div class="tb-fa-gallery__item" data-id="<?php echo esc_attr( $gi['id'] ); ?>">
					<img src="<?php echo esc_url( $gi['url'] ); ?>" alt="">
					<button type="button" class="tb-fa-gallery__remove" aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_ae_remove_photo', 'Odstrániť fotografiu' ) ); ?>">×</button>
				</div>
			<?php endforeach; ?>
			<button type="button" class="tb-fa-gallery__add" id="tb-l-gallery-add">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				<span><?php echo esc_html( tb_opt( 'tb_fa_ae_add_photo', 'Pridať fotografiu' ) ); ?></span>
			</button>
		</div>
	</section>

	<!-- ── Section: Умови оренди ── -->
	<section class="tb-fa-fs">
		<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_fa_ae_section_terms', 'Podmienky prenájmu' ) ); ?></h2>
		<div class="tb-fa-fs__fields">
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-price"><?php echo esc_html( tb_opt( 'tb_fa_ae_price', 'Cena / týždeň (€)' ) ); ?> <span class="tb-fa-required">*</span></label>
				<input class="tb-fa-fld__input" type="number" min="0" id="tb-l-price" name="price_week" value="<?php echo esc_attr( $v_price ); ?>" required>
			</div>
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-deposit"><?php echo esc_html( tb_opt( 'tb_fa_ae_deposit', 'Depozit (€)' ) ); ?></label>
				<input class="tb-fa-fld__input" type="number" min="0" id="tb-l-deposit" name="deposit" value="<?php echo esc_attr( $v_deposit ); ?>">
			</div>
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-mileage"><?php echo esc_html( tb_opt( 'tb_fa_ae_mileage', 'Limit najazdených km / mesiac (km)' ) ); ?></label>
				<input class="tb-fa-fld__input" type="number" min="0" id="tb-l-mileage" name="mileage_limit_month" value="<?php echo esc_attr( $v_mileage ); ?>" placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_ae_mileage_ph', '0 — bez obmedzenia' ) ); ?>">
			</div>
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb-l-term"><?php echo esc_html( tb_opt( 'tb_fa_ae_min_term', 'Minimálna doba prenájmu (dní)' ) ); ?></label>
				<input class="tb-fa-fld__input" type="number" min="1" id="tb-l-term" name="min_term_days" value="<?php echo esc_attr( $v_min_term ?: '7' ); ?>">
			</div>
		</div>

		<div class="tb-fa-toggles">
			<label class="tb-fa-toggle">
				<input type="checkbox" name="insurance" value="yes" <?php checked( $insurance_on ); ?>>
				<span class="tb-fa-toggle__track"><span class="tb-fa-toggle__thumb"></span></span>
				<span class="tb-fa-toggle__label"><?php echo esc_html( tb_opt( 'tb_fa_ae_insurance', 'Poistenie zahrnuté' ) ); ?></span>
			</label>
			<label class="tb-fa-toggle">
				<input type="checkbox" name="maintenance_included" value="yes" <?php checked( $maintenance_on ); ?>>
				<span class="tb-fa-toggle__track"><span class="tb-fa-toggle__thumb"></span></span>
				<span class="tb-fa-toggle__label"><?php echo esc_html( tb_opt( 'tb_fa_ae_maintenance', 'Servis zahrnutý' ) ); ?></span>
			</label>
		</div>
	</section>

	<!-- ── Section: Локація та категорії ── -->
	<section class="tb-fa-fs">
		<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_fa_ae_section_loc', 'Lokalita a kategórie' ) ); ?></h2>
		<div class="tb-fa-fld tb-fa-fld--narrow">
			<label class="tb-fa-fld__label" for="tb-l-city"><?php echo esc_html( tb_opt( 'tb_fa_ae_city', 'Mesto' ) ); ?> <span class="tb-fa-required">*</span></label>
			<div class="tb-fa-select-wrap">
				<select class="tb-fa-fld__select" id="tb-l-city" name="city" required>
					<option value="" <?php selected( $cur_city_id, 0 ); ?>><?php echo esc_html( tb_opt( 'tb_fa_ae_select_ph', 'Vyberte prosím' ) ); ?></option>
					<?php foreach ( $city_terms as $ct ) : ?>
						<option value="<?php echo esc_attr( $ct->term_id ); ?>" <?php selected( $cur_city_id, $ct->term_id ); ?>><?php echo esc_html( $ct->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>

		<?php if ( $rc_parents ) : ?>
		<div class="tb-fa-cat-cols">
			<?php foreach ( $rc_parents as $parent ) :
				$children = get_terms( [ 'taxonomy' => 'ride_category', 'parent' => $parent->term_id, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
				if ( is_wp_error( $children ) ) {
					$children = [];
				}
			?>
			<div class="tb-fa-cat-col">
				<p class="tb-fa-cat-col__title"><?php echo esc_html( $parent->name ); ?></p>
				<div class="tb-fa-toggles tb-fa-toggles--col">
					<?php foreach ( $children as $child ) : ?>
						<label class="tb-fa-toggle">
							<input type="checkbox" name="ride_category[]" value="<?php echo esc_attr( $child->term_id ); ?>" <?php checked( in_array( (int) $child->term_id, $cur_cats, true ) ); ?>>
							<span class="tb-fa-toggle__track"><span class="tb-fa-toggle__thumb"></span></span>
							<span class="tb-fa-toggle__label"><?php echo esc_html( $child->name ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</section>

	<!-- ── Section: Додаткова інформація ── -->
	<section class="tb-fa-fs">
		<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_fa_ae_section_extra', 'Doplňujúce informácie' ) ); ?></h2>
		<div class="tb-fa-fld">
			<label class="tb-fa-fld__label" for="tb-l-desc"><?php echo esc_html( tb_opt( 'tb_fa_ae_description', 'Popis inzerátu' ) ); ?></label>
			<textarea class="tb-fa-fld__input tb-fa-fld__textarea" id="tb-l-desc" name="short_description" rows="5" placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_ae_desc_hint', 'Zamyslite sa, čo by ste chceli vedieť z inzerátu. A doplňte to do popisu.' ) ); ?>"><?php echo esc_textarea( $v_desc ); ?></textarea>
		</div>
	</section>
</form>

<!-- Bottom action buttons (repeat, 1:1 with head).
     --wide marks the status button that gets a full-width row of its own on
     mobile; which of the two it is depends on the status, so it is tagged in
     the markup rather than matched by position in CSS. -->
<div class="tb-fa-edit-foot">
	<a href="<?php echo $back_url; ?>" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--lg">
		<?php echo esc_html( tb_opt( 'tb_fa_ae_cancel_btn', 'Zrušiť zmeny' ) ); ?>
	</a>
	<?php if ( 'archived' !== $post_status ) : ?>
		<button type="button" class="tb-fa-btn tb-fa-btn--dark tb-fa-btn--lg tb-fa-edit-foot__wide" data-fa-action="archive" data-redirect="<?php echo $back_url; ?>">
			<?php echo esc_html( tb_opt( 'tb_fa_ae_archive_btn', 'Archivovať' ) ); ?>
		</button>
	<?php else : ?>
		<button type="button" class="tb-fa-btn tb-fa-btn--dark tb-fa-btn--lg tb-fa-edit-foot__wide" data-fa-action="pending" data-redirect="<?php echo $back_url; ?>">
			<?php echo esc_html( tb_opt( 'tb_fa_ae_restore_btn', 'Obnoviť' ) ); ?>
		</button>
	<?php endif; ?>
	<button type="submit" form="tb-fa-listing-form" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--lg">
		<?php echo esc_html( tb_opt( 'tb_fa_ae_publish_btn', 'Zverejniť' ) ); ?>
	</button>
</div>
