<?php
/**
 * Template Name: Add / Edit Listing
 * Template Post Type: page
 *
 * Modes:
 *  - New:  GET /cabinet/add-listing/
 *  - Edit: GET /cabinet/add-listing/?post_id=123
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect(
		add_query_arg( 'redirect_to', rawurlencode( get_permalink() ), tb_url( '/login/' ) )
	);
	exit;
}

$user          = wp_get_current_user();
$user_id       = (int) $user->ID;
$allowed_roles = [ 'car_owner', 'fleet', 'administrator', 'editor' ];

if ( ! array_intersect( (array) $user->roles, $allowed_roles ) ) {
	wp_safe_redirect( tb_url( '/cabinet/' ) );
	exit;
}

// ── Mode: new or edit ──────────────────────────────────────────────────────
$edit_post_id = absint( wp_unslash( $_GET['post_id'] ?? 0 ) );
$is_edit      = false;
$edit_post    = null;

if ( $edit_post_id ) {
	$edit_post = get_post( $edit_post_id );

	if ( ! $edit_post || 'car_listing' !== $edit_post->post_type ) {
		wp_safe_redirect( tb_url( '/cabinet/' ) );
		exit;
	}

	$is_privileged = (bool) array_intersect( (array) $user->roles, [ 'administrator', 'editor' ] );
	if ( ! $is_privileged && (int) $edit_post->post_author !== $user_id ) {
		wp_safe_redirect( tb_url( '/cabinet/' ) );
		exit;
	}

	$is_edit = true;
}

$show_saved = ! empty( $_GET['updated'] ) || ! empty( $_GET['submitted'] );

// ── Field values ───────────────────────────────────────────────────────────
$v_make         = '';
$v_model        = '';
$v_year         = '';
$v_consumption  = '';
$v_price        = '';
$v_deposit      = '';
$v_mileage      = '';
$v_min_term     = '7';
$v_desc         = '';
$engine_val     = '';
$trans_val      = '';
$engine_choices = [];
$trans_choices  = [];
$insurance_on   = false;
$casco_on       = false;
$maintenance_on = false;
$toll_on        = false;
$gallery_items  = [];
$gallery_csv    = '';
$cur_city_id    = 0;
$cur_cats       = [];

// Choices are built from ACF options so labels can be edited in wp-admin → TaxiBaza → Pridanie inzerátu.
$engine_choices = [
	'petrol'   => tb_opt( 'tb_al_engine_petrol', __( 'Benzín', 'taxi-rental' ) ),
	'diesel'   => tb_opt( 'tb_al_engine_diesel', __( 'Nafta', 'taxi-rental' ) ),
	'electric' => tb_opt( 'tb_al_engine_electric', __( 'Elektrický', 'taxi-rental' ) ),
	'hybrid'   => tb_opt( 'tb_al_engine_hybrid', __( 'Hybrid', 'taxi-rental' ) ),
	'lpg'      => tb_opt( 'tb_al_engine_lpg', __( 'LPG', 'taxi-rental' ) ),
];
$trans_choices = [
	'automatic' => tb_opt( 'tb_al_trans_automatic', __( 'Automatická', 'taxi-rental' ) ),
	'manual'    => tb_opt( 'tb_al_trans_manual', __( 'Manuálna', 'taxi-rental' ) ),
];

if ( $is_edit && function_exists( 'get_field' ) ) {
	$v_make        = (string) get_field( 'make', $edit_post_id );
	$v_model       = (string) get_field( 'model', $edit_post_id );
	$v_year        = (int) get_field( 'year', $edit_post_id );
	$v_consumption = (string) get_field( 'consumption_100km', $edit_post_id );
	$v_price       = (string) get_field( 'price_week', $edit_post_id );
	$v_deposit     = (string) get_field( 'deposit', $edit_post_id );
	$v_mileage     = (string) get_field( 'mileage_limit_month', $edit_post_id );
	$v_min_term    = (string) ( get_field( 'min_term_days', $edit_post_id ) ?: '7' );
	$v_desc        = (string) get_field( 'short_description', $edit_post_id );

	$engine_val = (string) get_field( 'engine_type', $edit_post_id );
	$trans_val  = (string) get_field( 'transmission', $edit_post_id );

	$insurance_on   = in_array( (string) get_field( 'insurance', $edit_post_id ), [ 'yes', 'details' ], true );
	$casco_on       = '1' === get_post_meta( $edit_post_id, 'tb_casco', true );
	$maintenance_on = in_array( (string) get_field( 'maintenance_included', $edit_post_id ), [ 'yes', 'details' ], true );
	$toll_on        = '1' === get_post_meta( $edit_post_id, 'tb_toll_roads', true );

	$gallery_raw = get_field( 'gallery', $edit_post_id );
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

	$cur_city    = wp_get_object_terms( $edit_post_id, 'city', [ 'fields' => 'ids' ] );
	$cur_city_id = ( $cur_city && ! is_wp_error( $cur_city ) ) ? (int) $cur_city[0] : 0;

	$cur_cats = wp_get_object_terms( $edit_post_id, 'ride_category', [ 'fields' => 'ids' ] );
	$cur_cats = is_wp_error( $cur_cats ) ? [] : array_map( 'intval', $cur_cats );
}

// ── Terms ──────────────────────────────────────────────────────────────────
$city_terms = get_terms( [ 'taxonomy' => 'city', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
if ( is_wp_error( $city_terms ) ) {
	$city_terms = [];
}

$rc_parents = get_terms( [ 'taxonomy' => 'ride_category', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
if ( is_wp_error( $rc_parents ) ) {
	$rc_parents = [];
}

// Car makes (parent terms) + models grouped by make, for the dependent selects.
$make_parents = get_terms( [ 'taxonomy' => 'car_make', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
if ( is_wp_error( $make_parents ) ) {
	$make_parents = [];
}
$models_by_make = [];
foreach ( $make_parents as $mk ) {
	$kids                           = get_terms( [ 'taxonomy' => 'car_make', 'parent' => $mk->term_id, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ] );
	$models_by_make[ $mk->term_id ] = is_wp_error( $kids ) ? [] : $kids;
}

// Current make/model selection for edit mode (taxonomy first, meta as fallback).
$cur_make_id  = 0;
$cur_model_id = 0;
if ( $is_edit ) {
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
}

// ── Helpers ────────────────────────────────────────────────────────────────
$min_term_opts = [
	'1'  => __( '1 deň', 'taxi-rental' ),
	'3'  => __( '3 dni', 'taxi-rental' ),
	'7'  => __( '7 dní', 'taxi-rental' ),
	'14' => __( '14 dní', 'taxi-rental' ),
	'30' => __( '30 dní', 'taxi-rental' ),
];

$chevron_svg = '<svg class="tb-fa-select-chevron" width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

$return_url = esc_url(
	add_query_arg(
		$is_edit ? 'updated' : 'submitted',
		'1',
		tb_url( '/cabinet/' )
	)
);

get_header();
?>

<div class="tb-fadmin tb-al-page">
<div class="tb-fa-container">

	<?php if ( $show_saved ) : ?>
		<div class="tb-fa-inline-toast is-visible" role="status">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php
			if ( $is_edit ) {
				echo esc_html( tb_opt( 'tb_al_toast_saved', __( 'Zmeny uložené', 'taxi-rental' ) ) );
			} else {
				echo esc_html( tb_opt( 'tb_al_toast_submitted', __( 'Inzerát bol odoslaný na moderáciu', 'taxi-rental' ) ) );
			}
			?>
		</div>
	<?php endif; ?>

	<!-- ── Page head ──────────────────────────────────────────────────────── -->
	<div class="tb-al-head">
		<h1 class="tb-al-head__title">
			<?php echo $is_edit ? esc_html( tb_opt( 'tb_al_title_edit', __( 'Upraviť inzerát', 'taxi-rental' ) ) ) : esc_html( tb_opt( 'tb_al_title_new', __( 'Nový inzerát', 'taxi-rental' ) ) ); ?>
		</h1>
		<?php
		// #109/#118 — the Cancel link is gone. It was not in the comp, and because
		// it is an <a> the "unsaved changes" interceptor treated leaving through it
		// as an accidental navigation and threw the exit alert at the user. The two
		// save actions below are the whole toolbar now, and they are hidden
		// altogether under 1024px in favour of the bottom bar.
		?>
		<div class="tb-al-head__actions">
			<button type="button" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--lg" id="tb-al-btn-draft">
				<?php echo esc_html( tb_opt( 'tb_al_btn_draft', __( 'Uložiť koncept', 'taxi-rental' ) ) ); ?>
			</button>
			<button type="button" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--lg" id="tb-al-btn-publish">
				<?php echo esc_html( tb_opt( 'tb_al_btn_publish', __( 'Zverejniť', 'taxi-rental' ) ) ); ?>
			</button>
		</div>
	</div>

	<!-- ── 2-column layout ────────────────────────────────────────────────── -->
	<div class="tb-al-layout">

		<!-- Form column -->
		<div class="tb-al-layout__form">

			<form id="tb-al-form"
				class="tb-fa-listing-form"
				data-post-id="<?php echo esc_attr( $edit_post_id ); ?>"
				data-redirect="<?php echo $return_url; ?>">

				<?php wp_nonce_field( 'tb_submit_listing', 'tb_al_nonce' ); ?>

				<!-- ── Section: Základné informácie ── -->
				<section class="tb-fa-fs">
					<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_al_sec_basic', __( 'Základné informácie', 'taxi-rental' ) ) ); ?></h2>
					<div class="tb-fa-fs__fields">
						<div class="tb-fa-fld">
							<label class="tb-fa-fld__label" for="tb-al-make"><?php echo esc_html( tb_opt( 'tb_al_make', __( 'Značka auta', 'taxi-rental' ) ) ); ?> <span class="tb-fa-required">*</span></label>
							<div class="tb-fa-select-wrap">
								<select class="tb-fa-fld__select" id="tb-al-make" name="make_term" data-make-select required>
									<option value=""><?php echo esc_html( tb_opt( 'tb_al_select_ph', __( 'Vyberte prosím', 'taxi-rental' ) ) ); ?></option>
									<?php foreach ( $make_parents as $mk ) : ?>
										<option value="<?php echo esc_attr( $mk->term_id ); ?>" <?php selected( $cur_make_id, $mk->term_id ); ?>><?php echo esc_html( $mk->name ); ?></option>
									<?php endforeach; ?>
								</select>
								<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</div>
						<div class="tb-fa-fld">
							<label class="tb-fa-fld__label" for="tb-al-model"><?php echo esc_html( tb_opt( 'tb_al_model', __( 'Model', 'taxi-rental' ) ) ); ?> <span class="tb-fa-required">*</span></label>
							<div class="tb-fa-select-wrap">
								<select class="tb-fa-fld__select" id="tb-al-model" name="model_term" data-model-select required>
									<option value=""><?php echo esc_html( tb_opt( 'tb_al_select_ph', __( 'Vyberte prosím', 'taxi-rental' ) ) ); ?></option>
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
							<label class="tb-fa-fld__label" for="tb-al-year"><?php echo esc_html( tb_opt( 'tb_al_year', __( 'Rok výroby', 'taxi-rental' ) ) ); ?> <span class="tb-fa-required">*</span></label>
							<div class="tb-fa-select-wrap">
								<select class="tb-fa-fld__select" id="tb-al-year" name="year" required>
									<option value=""><?php echo esc_html( tb_opt( 'tb_al_select_ph', __( 'Vyberte prosím', 'taxi-rental' ) ) ); ?></option>
									<?php for ( $yr = max( (int) wp_date( 'Y' ), (int) $v_year ); $yr >= 1990; $yr-- ) : ?>
										<option value="<?php echo esc_attr( $yr ); ?>" <?php selected( $v_year, $yr ); ?>><?php echo esc_html( $yr ); ?></option>
									<?php endfor; ?>
								</select>
								<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</div>
						<div class="tb-fa-fld">
							<label class="tb-fa-fld__label" for="tb-al-engine"><?php echo esc_html( tb_opt( 'tb_al_engine', __( 'Typ motora', 'taxi-rental' ) ) ); ?> <span class="tb-fa-required">*</span></label>
							<div class="tb-fa-select-wrap">
								<select class="tb-fa-fld__select" id="tb-al-engine" name="engine_type" required>
									<option value="" <?php selected( $engine_val, '' ); ?>><?php echo esc_html( tb_opt( 'tb_al_select_ph', __( 'Vyberte prosím', 'taxi-rental' ) ) ); ?></option>
									<?php foreach ( $engine_choices as $ck => $cl ) : ?>
										<option value="<?php echo esc_attr( $ck ); ?>" <?php selected( $engine_val, $ck ); ?>><?php echo esc_html( $cl ); ?></option>
									<?php endforeach; ?>
								</select>
								<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</div>
						<div class="tb-fa-fld">
							<label class="tb-fa-fld__label" for="tb-al-trans"><?php echo esc_html( tb_opt( 'tb_al_trans', __( 'Prevodovka', 'taxi-rental' ) ) ); ?> <span class="tb-fa-required">*</span></label>
							<div class="tb-fa-select-wrap">
								<select class="tb-fa-fld__select" id="tb-al-trans" name="transmission" required>
									<option value="" <?php selected( $trans_val, '' ); ?>><?php echo esc_html( tb_opt( 'tb_al_select_ph', __( 'Vyberte prosím', 'taxi-rental' ) ) ); ?></option>
									<?php foreach ( $trans_choices as $ck => $cl ) : ?>
										<option value="<?php echo esc_attr( $ck ); ?>" <?php selected( $trans_val, $ck ); ?>><?php echo esc_html( $cl ); ?></option>
									<?php endforeach; ?>
								</select>
								<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</div>
						<div class="tb-fa-fld">
							<label class="tb-fa-fld__label" for="tb-al-cons"><?php echo esc_html( tb_opt( 'tb_al_consumption', __( 'Spotreba', 'taxi-rental' ) ) ); ?> <span class="tb-fa-required">*</span></label>
							<input class="tb-fa-fld__input" type="number" step="0.1" id="tb-al-cons" name="consumption_100km"
								value="<?php echo esc_attr( $v_consumption ); ?>"
								placeholder="<?php echo esc_attr( tb_opt( 'tb_al_consumption_ph', __( 'Napr. 8l/100km', 'taxi-rental' ) ) ); ?>">
						</div>
					</div>
				</section>

				<!-- ── Section: Fotografie ── -->
				<section class="tb-fa-fs">
					<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_al_sec_photos', __( 'Fotografie', 'taxi-rental' ) ) ); ?></h2>
					<p class="tb-fa-fs__hint"><?php echo esc_html( tb_opt( 'tb_al_photos_hint', __( 'Prvá fotografia bude na obálke inzerátu. Potiahnutím zmeňte poradie.', 'taxi-rental' ) ) ); ?></p>
					<input type="hidden" id="tb-al-gallery" name="gallery" value="<?php echo esc_attr( $gallery_csv ); ?>">
					<div class="tb-fa-gallery" id="tb-al-gallery-grid">
						<?php foreach ( $gallery_items as $gi ) : ?>
							<div class="tb-fa-gallery__item" data-id="<?php echo esc_attr( $gi['id'] ); ?>">
								<img src="<?php echo esc_url( $gi['url'] ); ?>" alt="">
								<button type="button" class="tb-fa-gallery__remove" aria-label="<?php echo esc_attr( tb_opt( 'tb_al_remove_photo', __( 'Odstrániť fotografiu', 'taxi-rental' ) ) ); ?>">&times;</button>
							</div>
						<?php endforeach; ?>
						<button type="button" class="tb-fa-gallery__add" id="tb-al-gallery-add">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
							<span><?php echo esc_html( tb_opt( 'tb_al_add_photo', __( 'Pridať fotografiu', 'taxi-rental' ) ) ); ?></span>
						</button>
					</div>
				</section>

				<!-- ── Section: Podmienky prenájmu ── -->
				<section class="tb-fa-fs">
					<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_al_sec_terms', __( 'Podmienky prenájmu', 'taxi-rental' ) ) ); ?></h2>
					<div class="tb-fa-fs__fields">
						<div class="tb-fa-fld">
							<label class="tb-fa-fld__label" for="tb-al-price"><?php echo esc_html( tb_opt( 'tb_al_price', __( 'Cena / týždeň (€)', 'taxi-rental' ) ) ); ?> <span class="tb-fa-required">*</span></label>
							<input class="tb-fa-fld__input" type="number" min="0" id="tb-al-price" name="price_week" value="<?php echo esc_attr( $v_price ); ?>" required>
						</div>
						<div class="tb-fa-fld">
							<label class="tb-fa-fld__label" for="tb-al-deposit"><?php echo esc_html( tb_opt( 'tb_al_deposit', __( 'Depozit (€)', 'taxi-rental' ) ) ); ?></label>
							<input class="tb-fa-fld__input" type="number" min="0" id="tb-al-deposit" name="deposit" value="<?php echo esc_attr( $v_deposit ); ?>">
						</div>
						<div class="tb-fa-fld">
							<label class="tb-fa-fld__label" for="tb-al-mileage"><?php echo esc_html( tb_opt( 'tb_al_mileage', __( 'Limit najazdených km / mesiac (km)', 'taxi-rental' ) ) ); ?></label>
							<input class="tb-fa-fld__input" type="number" min="0" id="tb-al-mileage" name="mileage_limit_month"
								value="<?php echo esc_attr( $v_mileage ); ?>"
								placeholder="<?php echo esc_attr( tb_opt( 'tb_al_mileage_ph', __( '0 — bez obmedzenia', 'taxi-rental' ) ) ); ?>">
						</div>
						<div class="tb-fa-fld">
							<label class="tb-fa-fld__label" for="tb-al-term"><?php echo esc_html( tb_opt( 'tb_al_min_term', __( 'Minimálna doba prenájmu', 'taxi-rental' ) ) ); ?> <span class="tb-fa-required">*</span></label>
							<div class="tb-fa-select-wrap">
								<select class="tb-fa-fld__select" id="tb-al-term" name="min_term_days" required>
									<?php foreach ( $min_term_opts as $days => $opt_label ) : ?>
										<option value="<?php echo esc_attr( $days ); ?>" <?php selected( $v_min_term, (string) $days ); ?>><?php echo esc_html( $opt_label ); ?></option>
									<?php endforeach; ?>
								</select>
								<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</div>
					</div>

					<div class="tb-fa-toggles">
						<label class="tb-fa-toggle">
							<input type="checkbox" name="insurance" value="yes" <?php checked( $insurance_on ); ?>>
							<span class="tb-fa-toggle__track"><span class="tb-fa-toggle__thumb"></span></span>
							<span class="tb-fa-toggle__label"><?php echo esc_html( tb_opt( 'tb_al_insurance', __( 'Poistenie zahrnuté', 'taxi-rental' ) ) ); ?></span>
						</label>
						<label class="tb-fa-toggle tb-al-toggle--desktop">
							<input type="checkbox" name="casco" value="yes" <?php checked( $casco_on ); ?>>
							<span class="tb-fa-toggle__track"><span class="tb-fa-toggle__thumb"></span></span>
							<span class="tb-fa-toggle__label"><?php echo esc_html( tb_opt( 'tb_al_casco', __( 'KASKO', 'taxi-rental' ) ) ); ?></span>
						</label>
						<label class="tb-fa-toggle">
							<input type="checkbox" name="maintenance_included" value="yes" <?php checked( $maintenance_on ); ?>>
							<span class="tb-fa-toggle__track"><span class="tb-fa-toggle__thumb"></span></span>
							<span class="tb-fa-toggle__label"><?php echo esc_html( tb_opt( 'tb_al_maintenance', __( 'Servis zahrnutý', 'taxi-rental' ) ) ); ?></span>
						</label>
						<label class="tb-fa-toggle tb-al-toggle--desktop">
							<input type="checkbox" name="toll_roads" value="yes" <?php checked( $toll_on ); ?>>
							<span class="tb-fa-toggle__track"><span class="tb-fa-toggle__thumb"></span></span>
							<span class="tb-fa-toggle__label"><?php echo esc_html( tb_opt( 'tb_al_toll', __( 'Platené cesty', 'taxi-rental' ) ) ); ?></span>
						</label>
					</div>
				</section>

				<!-- ── Section: Lokalita a kategórie ── -->
				<section class="tb-fa-fs">
					<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_al_sec_location', __( 'Lokalita a kategórie', 'taxi-rental' ) ) ); ?></h2>
					<div class="tb-fa-fld tb-fa-fld--narrow">
						<label class="tb-fa-fld__label" for="tb-al-city"><?php echo esc_html( tb_opt( 'tb_al_city', __( 'Mesto', 'taxi-rental' ) ) ); ?> <span class="tb-fa-required">*</span></label>
						<div class="tb-fa-select-wrap">
							<select class="tb-fa-fld__select" id="tb-al-city" name="city" required>
								<option value="" <?php selected( $cur_city_id, 0 ); ?>><?php echo esc_html( tb_opt( 'tb_al_select_ph', __( 'Vyberte prosím', 'taxi-rental' ) ) ); ?></option>
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

				<!-- ── Section: Doplňujúce informácie ── -->
				<section class="tb-fa-fs">
					<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_al_sec_extra', __( 'Doplňujúce informácie', 'taxi-rental' ) ) ); ?></h2>
					<div class="tb-fa-fld">
						<label class="tb-fa-fld__label" for="tb-al-desc"><?php echo esc_html( tb_opt( 'tb_al_description', __( 'Popis inzerátu', 'taxi-rental' ) ) ); ?></label>
						<textarea class="tb-fa-fld__input tb-fa-fld__textarea" id="tb-al-desc" name="short_description" rows="5"
							placeholder="<?php echo esc_attr( tb_opt( 'tb_al_desc_ph', __( 'Zamyslite sa, čo by ste chceli vedieť z inzerátu. Doplňte to do popisu.', 'taxi-rental' ) ) ); ?>"><?php echo esc_textarea( $v_desc ); ?></textarea>
					</div>
				</section>

			</form><!-- #tb-al-form -->

		</div><!-- .tb-al-layout__form -->

		<!-- Tips sidebar (desktop: right column; mobile: below form via CSS order) -->
		<aside class="tb-al-layout__tips" aria-label="<?php echo esc_attr( tb_opt( 'tb_al_tips_aria', __( 'Tipy', 'taxi-rental' ) ) ); ?>">
			<div class="tb-fa-fs tb-al-tips-card">
				<h3 class="tb-al-tips-card__title"><?php echo esc_html( tb_opt( 'tb_al_tips_title', __( 'Tipy pre lepší inzerát', 'taxi-rental' ) ) ); ?></h3>
				<ul class="tb-al-tips-card__list">
					<li>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span><?php echo esc_html( tb_opt( 'tb_al_tip_photos', __( 'Pridajte aspoň 4 kvalitné fotografie auta', 'taxi-rental' ) ) ); ?></span>
					</li>
					<li>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span><?php echo esc_html( tb_opt( 'tb_al_tip_desc', __( 'Podrobne opíšte stav a podmienky prenájmu', 'taxi-rental' ) ) ); ?></span>
					</li>
					<li>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span><?php echo esc_html( tb_opt( 'tb_al_tip_location', __( 'Uveďte presnú lokalitu', 'taxi-rental' ) ) ); ?></span>
					</li>
					<li>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span><?php echo esc_html( tb_opt( 'tb_al_tip_contacts', __( 'Presvedčte sa, že kontakty sú aktuálne', 'taxi-rental' ) ) ); ?></span>
					</li>
				</ul>
			</div>
		</aside>

	</div><!-- .tb-al-layout -->

</div><!-- .tb-fa-container -->

<!-- ── Bottom action bar (full width) ─────────────────────────────────── -->
<div class="tb-al-foot">
	<div class="tb-al-foot__inner">
		<button type="button" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--lg" id="tb-al-btn-draft-foot">
			<?php echo esc_html( tb_opt( 'tb_al_btn_draft', __( 'Uložiť koncept', 'taxi-rental' ) ) ); ?>
		</button>
		<button type="button" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--lg" id="tb-al-btn-publish-foot">
			<?php echo esc_html( tb_opt( 'tb_al_btn_pub_listing', __( 'Zverejniť inzerát', 'taxi-rental' ) ) ); ?>
		</button>
	</div>
</div>

</div><!-- .tb-fadmin.tb-al-page -->

<!-- ── Exit Alert Popup (Figma 410:6461) ────────────────────────────── -->
<div class="tb-modal tb-exit-alert" id="tb-exit-alert"
	role="dialog" aria-modal="true" aria-labelledby="tb-exit-alert-title" hidden>
	<div class="tb-modal__backdrop" id="tb-exit-backdrop"></div>
	<div class="tb-modal__panel">
		<div class="tb-exit-alert__icon" aria-hidden="true">
			<svg width="48" height="48" viewBox="0 0 48 48" fill="none">
				<path d="M25.425 33.425C25.8083 33.0417 26 32.5667 26 32C26 31.4333 25.8083 30.9583 25.425 30.575C25.0417 30.1917 24.5667 30 24 30C23.4333 30 22.9583 30.1917 22.575 30.575C22.1917 30.9583 22 31.4333 22 32C22 32.5667 22.1917 33.0417 22.575 33.425C22.9583 33.8083 23.4333 34 24 34C24.5667 34 25.0417 33.8083 25.425 33.425ZM22 26H26V14H22V26ZM24 44C21.2333 44 18.6333 43.475 16.2 42.425C13.7667 41.375 11.65 39.95 9.85 38.15C8.05 36.35 6.625 34.2333 5.575 31.8C4.525 29.3667 4 26.7667 4 24C4 21.2333 4.525 18.6333 5.575 16.2C6.625 13.7667 8.05 11.65 9.85 9.85C11.65 8.05 13.7667 6.625 16.2 5.575C18.6333 4.525 21.2333 4 24 4C26.7667 4 29.3667 4.525 31.8 5.575C34.2333 6.625 36.35 8.05 38.15 9.85C39.95 11.65 41.375 13.7667 42.425 16.2C43.475 18.6333 44 21.2333 44 24C44 26.7667 43.475 29.3667 42.425 31.8C41.375 34.2333 39.95 36.35 38.15 38.15C36.35 39.95 34.2333 41.375 31.8 42.425C29.3667 43.475 26.7667 44 24 44ZM24 40C28.4667 40 32.25 38.45 35.35 35.35C38.45 32.25 40 28.4667 40 24C40 19.5333 38.45 15.75 35.35 12.65C32.25 9.55 28.4667 8 24 8C19.5333 8 15.75 9.55 12.65 12.65C9.55 15.75 8 19.5333 8 24C8 28.4667 9.55 32.25 12.65 35.35C15.75 38.45 19.5333 40 24 40Z" fill="currentColor"/>
			</svg>
		</div>
		<h2 class="tb-exit-alert__title" id="tb-exit-alert-title">
			<?php echo esc_html( tb_opt( 'tb_al_exit_title', __( 'Naozaj chcete odísť bez uloženia?', 'taxi-rental' ) ) ); ?>
		</h2>
		<p class="tb-exit-alert__subtitle">
			<?php echo esc_html( tb_opt( 'tb_al_exit_text', __( 'Všetky neuložené zmeny budú stratené.', 'taxi-rental' ) ) ); ?>
		</p>
		<div class="tb-modal__actions">
			<button type="button" class="tb-btn tb-btn--outline" id="tb-exit-stay">
				<?php echo esc_html( tb_opt( 'tb_al_exit_stay', __( 'Pokračovať', 'taxi-rental' ) ) ); ?>
			</button>
			<button type="button" class="tb-btn tb-btn--primary" id="tb-exit-save">
				<?php echo esc_html( tb_opt( 'tb_al_exit_save', __( 'Uložiť koncept', 'taxi-rental' ) ) ); ?>
			</button>
		</div>
	</div>
</div>

<?php get_footer(); ?>
