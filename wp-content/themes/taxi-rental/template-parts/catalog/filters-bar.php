<?php
/**
 * template-parts/catalog/filters-bar.php
 * Filter pills bar for car_listing catalog.
 *
 * Args (passed via get_template_part 3rd param):
 *   filters        — array from Queries::parse_filters()
 *   city_terms     — WP_Term[]
 *   category_terms — WP_Term[]
 *   platform_terms — WP_Term[]
 */

$filters        = $args['filters']        ?? [];
$city_terms     = $args['city_terms']     ?? [];
$category_terms = $args['category_terms'] ?? [];
$platform_terms = $args['platform_terms'] ?? [];
$make_terms     = $args['make_terms']     ?? [];
$model_terms    = $args['model_terms']    ?? [];
$year_values    = $args['year_values']    ?? [];
$hide_city      = (bool) ( $args['hide_city'] ?? false );

$archive_url = get_post_type_archive_link( 'car_listing' );

$active_city     = $filters['city']         ?? '';
$active_cat      = $filters['cat']          ?? '';
$active_platform = $filters['platform']     ?? '';
$active_make     = $filters['make']         ?? '';
$active_model    = $filters['model']        ?? '';
$active_year     = ! empty( $filters['year'] ) ? (string) $filters['year'] : '';
$active_fuel     = $filters['fuel']         ?? '';
$active_trans    = $filters['transmission'] ?? '';
$active_pmin     = isset( $filters['price_min'] ) ? (string) $filters['price_min'] : '';
$active_pmax     = isset( $filters['price_max'] ) ? (string) $filters['price_max'] : '';
$active_dmin     = isset( $filters['deposit_min'] ) ? (string) $filters['deposit_min'] : '';
$active_dmax     = isset( $filters['deposit_max'] ) ? (string) $filters['deposit_max'] : '';
$active_order    = $filters['orderby']      ?? 'date_desc';

$fuel_labels = [
	'petrol'   => tb_opt( 'tb_cat_fuel_petrol',   __( 'Бензин',  'taxi-rental' ) ),
	'diesel'   => tb_opt( 'tb_cat_fuel_diesel',   __( 'Дизель',  'taxi-rental' ) ),
	'electric' => tb_opt( 'tb_cat_fuel_electric', __( 'Електро', 'taxi-rental' ) ),
	'hybrid'   => tb_opt( 'tb_cat_fuel_hybrid',   __( 'Гібрид',  'taxi-rental' ) ),
	'lpg'      => tb_opt( 'tb_cat_fuel_lpg',      __( 'LPG',     'taxi-rental' ) ),
];

$trans_labels = [
	'automatic' => tb_opt( 'tb_cat_trans_automatic', __( 'Автомат',  'taxi-rental' ) ),
	'manual'    => tb_opt( 'tb_cat_trans_manual',    __( 'Механіка', 'taxi-rental' ) ),
];

// Base params — all currently active filters, used to build every link so
// selecting one filter never silently drops the others.
$base_params = [];
if ( $active_city )     $base_params['city']         = $active_city;
if ( $active_cat )      $base_params['cat']          = $active_cat;
if ( $active_platform ) $base_params['platform']     = $active_platform;
if ( $active_make )     $base_params['make']         = $active_make;
if ( $active_model )    $base_params['model']        = $active_model;
// tb_year, not year: `year` is a reserved WP query var and turned the
// catalog into a date archive (404). See Queries::parse_filters().
if ( $active_year )     $base_params['tb_year']      = $active_year;
if ( $active_fuel )     $base_params['fuel']         = $active_fuel;
if ( $active_trans )    $base_params['transmission'] = $active_trans;
if ( $active_pmin )     $base_params['price_min']    = $active_pmin;
if ( $active_pmax )     $base_params['price_max']    = $active_pmax;
if ( $active_dmin )     $base_params['deposit_min']  = $active_dmin;
if ( $active_dmax )     $base_params['deposit_max']  = $active_dmax;
if ( $active_order && 'date_desc' !== $active_order ) $base_params['orderby'] = $active_order;

/**
 * Build URL for an option inside a dropdown: replaces one param, keeps the rest.
 *
 * @param string $key    Query param name.
 * @param string $value  New value.
 * @return string Escaped URL.
 */
$option_url = static function ( string $key, string $value ) use ( $base_params, $archive_url ): string {
	$params          = $base_params;
	$params[ $key ]  = $value;
	return esc_url( add_query_arg( $params, $archive_url ) );
};

/**
 * Build reset URL for one filter: removes its param, keeps the rest.
 */
$reset_url = static function ( string $key ) use ( $base_params, $archive_url ): string {
	$params = $base_params;
	unset( $params[ $key ] );
	return esc_url( empty( $params ) ? $archive_url : add_query_arg( $params, $archive_url ) );
};

// Term names for active pill labels.
$active_city_label = $active_city     ? get_term_by( 'slug', $active_city,     'city' )?->name          : '';
$active_cat_label  = $active_cat      ? get_term_by( 'slug', $active_cat,      'ride_category' )?->name  : '';
$active_plat_label = $active_platform ? get_term_by( 'slug', $active_platform, 'platform' )?->name       : '';
$active_make_label = $active_make     ? get_term_by( 'slug', $active_make,     'car_make' )?->name       : '';
$active_model_label = $active_model   ? get_term_by( 'slug', $active_model,    'car_make' )?->name       : '';

// Labels from ACF options.
$label_reset  = tb_opt( 'tb_cat_filter_reset', __( 'Скинути', 'taxi-rental' ) );
$label_apply  = tb_opt( 'tb_cat_filter_apply', __( 'Застосувати', 'taxi-rental' ) );
$label_pmin   = tb_opt( 'tb_cat_filter_price_min_ph', __( 'від €', 'taxi-rental' ) );
$label_pmax   = tb_opt( 'tb_cat_filter_price_max_ph', __( 'до €', 'taxi-rental' ) );
$label_preset = tb_opt( 'tb_cat_filter_price_reset', __( 'Скинути ціну', 'taxi-rental' ) );
$label_dmin   = tb_opt( 'tb_cat_filter_deposit_min_ph', __( 'od €', 'taxi-rental' ) );
$label_dmax   = tb_opt( 'tb_cat_filter_deposit_max_ph', __( 'do €', 'taxi-rental' ) );
$label_dreset = tb_opt( 'tb_cat_filter_deposit_reset', __( 'Zrušiť depozit', 'taxi-rental' ) );

/* ── Sort ─────────────────────────────────────────────────────────────── */
$order_labels = [
	'date_desc'    => tb_opt( 'tb_cat_sort_date_desc',    __( 'Najprv nové', 'taxi-rental' ) ),
	'date_asc'     => tb_opt( 'tb_cat_sort_date_asc',     __( 'Najprv staré', 'taxi-rental' ) ),
	'price_asc'    => tb_opt( 'tb_cat_sort_price_asc',    __( 'Od najlacnejších', 'taxi-rental' ) ),
	'price_desc'   => tb_opt( 'tb_cat_sort_price_desc',   __( 'Od najdrahších', 'taxi-rental' ) ),
	'popularity'   => tb_opt( 'tb_cat_sort_popularity',   __( 'Podľa popularity', 'taxi-rental' ) ),
	'updated_desc' => tb_opt( 'tb_cat_sort_updated_desc', __( 'Nedávno aktualizované', 'taxi-rental' ) ),
];
$lbl_sort    = tb_opt( 'tb_cat_filter_sort_label',  __( 'Zoradiť', 'taxi-rental' ) );
$lbl_sort_by = tb_opt( 'tb_cat_filter_sort_kicker', __( 'Zoradiť podľa', 'taxi-rental' ) );
$sort_active = ( 'date_desc' !== $active_order );

/* ── Mobile sheet (Figma 428:381 bar, 428:744 drill-down) ─────────────────
 * Below 1024px the pill row is not a row any more: it becomes a full-screen
 * sheet. The count on the button is what report item #104 asks for — a range
 * filter counts once, not once per bound.
 */
$active_filter_count = 0;
foreach ( [ $active_city, $active_cat, $active_platform, $active_make, $active_model, $active_year, $active_fuel, $active_trans ] as $tb_fv ) {
	if ( '' !== (string) $tb_fv ) {
		$active_filter_count++;
	}
}
if ( '' !== $active_pmin || '' !== $active_pmax ) {
	$active_filter_count++;
}
if ( '' !== $active_dmin || '' !== $active_dmax ) {
	$active_filter_count++;
}

// Slovak is the primary language, so every default here is Slovak; the
// Ukrainian wording comes from WPML String Translation, not from the code.
$lbl_filters_btn = tb_opt( 'tb_cat_filter_mobile_btn', 'Filtre' );
$lbl_sheet_close = tb_opt( 'tb_cat_filter_close',      'Zavrieť' );
$lbl_sheet_back  = tb_opt( 'tb_cat_filter_back',       'Späť' );

// "Reset all" keeps the chosen sort order and drops every filter.
$reset_all_params = ( $active_order && 'date_desc' !== $active_order ) ? [ 'orderby' => $active_order ] : [];
$reset_all_url    = empty( $reset_all_params ) ? $archive_url : add_query_arg( $reset_all_params, $archive_url );

// Chevron SVG (inline, reused per pill).
$chevron_svg = '<svg class="tb-filter-pill__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>

<div class="tb-filters-bar" data-filter-bar>

	<?php // Visible only below 1024px; the desktop pill row stays untouched. ?>
	<?php // #104 — wrapped in a plain div, not a bare flex-item <button>: form controls get their own "automatic minimum size" under flex-basis:0, so a <button> and a <div> with byte-identical flex/min-width never actually split 50/50 — verified live, off by ~16px each side even with matching values forced via !important. The sort pill needs the exact same wrapper (below) for the same reason. ?>
	<div class="tb-filter-mobile-btn-wrap">
		<button
			type="button"
			class="tb-filter-mobile-btn<?php echo $active_filter_count ? ' is-active' : ''; ?>"
			data-filters-open
			aria-expanded="false"
		>
			<span class="tb-filter-mobile-btn__label">
				<?php
				echo esc_html(
					$active_filter_count
						? sprintf( '%s (%d)', $lbl_filters_btn, $active_filter_count )
						: $lbl_filters_btn
				);
				?>
			</span>
			<svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>

	<div class="tb-filters-bar__pills">

		<?php // Sheet chrome — inert on desktop, the header of the sheet below 1024px. ?>
		<div class="tb-filter-sheet__head">
			<button type="button" class="tb-filter-sheet__back" data-filters-back aria-label="<?php echo esc_attr( $lbl_sheet_back ); ?>">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<span class="tb-filter-sheet__title" data-filters-title><?php echo esc_html( $lbl_filters_btn ); ?></span>
			<button type="button" class="tb-filter-sheet__close" data-filters-close aria-label="<?php echo esc_attr( $lbl_sheet_close ); ?>">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</button>
		</div>

		<?php
		/* ── Helper: render a taxonomy dropdown ────────────────────────── */
		$render_tax_drop = static function (
			string $param,
			string $default_label,
			string $active_value,
			string $active_label,
			array  $terms
		) use ( $option_url, $reset_url, $chevron_svg, $label_reset ): void {
			$is_active = ! empty( $active_value );
			$label     = $is_active && $active_label ? $active_label : $default_label;
			?>
			<div class="tb-filter-drop<?php echo $is_active ? ' tb-filter-drop--active' : ''; ?>" data-filter-drop>
				<button type="button" class="tb-filter-pill" data-filter-toggle aria-expanded="false" aria-haspopup="listbox">
					<?php echo esc_html( $label ); ?>
					<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<div class="tb-filter-panel" role="listbox" aria-label="<?php echo esc_attr( $default_label ); ?>">
					<?php if ( $is_active ) : ?>
						<a href="<?php echo $reset_url( $param ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" class="tb-filter-panel__reset">
							&times; <?php echo esc_html( $label_reset ); ?>
						</a>
					<?php endif; ?>
					<?php foreach ( $terms as $term ) :
						$is_sel = ( $term->slug === $active_value );
					?>
						<a
							href="<?php echo $option_url( $param, $term->slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
							class="tb-filter-panel__option<?php echo $is_sel ? ' is-selected' : ''; ?>"
							role="option"
							aria-selected="<?php echo $is_sel ? 'true' : 'false'; ?>"
						>
							<?php echo esc_html( $term->name ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
		};

		// City — hidden on taxonomy-city pages.
		if ( ! $hide_city ) :
			$render_tax_drop(
				'city',
				tb_opt( 'tb_cat_filter_city', __( 'Місто', 'taxi-rental' ) ),
				$active_city,
				$active_city_label,
				$city_terms
			);
		endif;

		// Category.
		$render_tax_drop(
			'cat',
			tb_opt( 'tb_cat_filter_cat', __( 'Категорія', 'taxi-rental' ) ),
			$active_cat,
			$active_cat_label,
			$category_terms
		);

		// Platform.
		$render_tax_drop(
			'platform',
			tb_opt( 'tb_cat_filter_platform', __( 'Платформа', 'taxi-rental' ) ),
			$active_platform,
			$active_plat_label,
			$platform_terms
		);

		// Make (car_make parent terms).
		$render_tax_drop(
			'make',
			tb_opt( 'tb_cat_filter_make', __( 'Značka', 'taxi-rental' ) ),
			$active_make,
			$active_make_label,
			$make_terms
		);
		?>

		<?php
		/* ── Model — only usable once a make is chosen ─────────────────── */
		$lbl_model = tb_opt( 'tb_cat_filter_model', __( 'Model', 'taxi-rental' ) );
		if ( $active_make ) :
			$render_tax_drop(
				'model',
				$lbl_model,
				$active_model,
				$active_model_label,
				$model_terms
			);
		else :
			?>
			<div class="tb-filter-drop tb-filter-drop--disabled">
				<button type="button" class="tb-filter-pill" disabled aria-disabled="true" title="<?php echo esc_attr( tb_opt( 'tb_cat_filter_model_hint', __( 'Najprv vyberte značku', 'taxi-rental' ) ) ); ?>">
					<?php echo esc_html( $lbl_model ); ?>
					<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
			<?php
		endif;
		?>

		<?php
		/* ── Year ─────────────────────────────────────────────────────── */
		$lbl_year    = tb_opt( 'tb_cat_filter_year', __( 'Рік', 'taxi-rental' ) );
		$year_active = '' !== $active_year;
		$year_label  = $year_active ? $active_year : $lbl_year;
		?>
		<?php if ( ! empty( $year_values ) ) : ?>
		<div class="tb-filter-drop<?php echo $year_active ? ' tb-filter-drop--active' : ''; ?>" data-filter-drop>
			<button type="button" class="tb-filter-pill" data-filter-toggle aria-expanded="false" aria-haspopup="listbox">
				<?php echo esc_html( $year_label ); ?>
				<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<div class="tb-filter-panel" role="listbox" aria-label="<?php echo esc_attr( $lbl_year ); ?>">
				<?php if ( $year_active ) : ?>
					<a href="<?php echo $reset_url( 'tb_year' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" class="tb-filter-panel__reset">
						&times; <?php echo esc_html( $label_reset ); ?>
					</a>
				<?php endif; ?>
				<?php foreach ( $year_values as $yr ) :
					$yr = (string) $yr;
				?>
					<a
						href="<?php echo $option_url( 'tb_year', $yr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						class="tb-filter-panel__option<?php echo $yr === $active_year ? ' is-selected' : ''; ?>"
						role="option"
						aria-selected="<?php echo $yr === $active_year ? 'true' : 'false'; ?>"
					>
						<?php echo esc_html( $yr ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php
		/* ── Fuel ─────────────────────────────────────────────────────── */
		$lbl_fuel    = tb_opt( 'tb_cat_filter_fuel', __( 'Паливо', 'taxi-rental' ) );
		$fuel_active = ! empty( $active_fuel );
		$fuel_label  = $fuel_active ? ( $fuel_labels[ $active_fuel ] ?? $active_fuel ) : $lbl_fuel;
		?>
		<div class="tb-filter-drop<?php echo $fuel_active ? ' tb-filter-drop--active' : ''; ?>" data-filter-drop>
			<button type="button" class="tb-filter-pill" data-filter-toggle aria-expanded="false" aria-haspopup="listbox">
				<?php echo esc_html( $fuel_label ); ?>
				<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<div class="tb-filter-panel" role="listbox" aria-label="<?php echo esc_attr( $lbl_fuel ); ?>">
				<?php if ( $fuel_active ) : ?>
					<a href="<?php echo $reset_url( 'fuel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" class="tb-filter-panel__reset">
						&times; <?php echo esc_html( $label_reset ); ?>
					</a>
				<?php endif; ?>
				<?php foreach ( $fuel_labels as $val => $lbl ) : ?>
					<a
						href="<?php echo $option_url( 'fuel', $val ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						class="tb-filter-panel__option<?php echo $val === $active_fuel ? ' is-selected' : ''; ?>"
						role="option"
						aria-selected="<?php echo $val === $active_fuel ? 'true' : 'false'; ?>"
					>
						<?php echo esc_html( $lbl ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<?php
		/* ── Transmission ─────────────────────────────────────────────── */
		$lbl_trans    = tb_opt( 'tb_cat_filter_trans', __( 'КПП', 'taxi-rental' ) );
		$trans_active = ! empty( $active_trans );
		$trans_label  = $trans_active ? ( $trans_labels[ $active_trans ] ?? $active_trans ) : $lbl_trans;
		?>
		<div class="tb-filter-drop<?php echo $trans_active ? ' tb-filter-drop--active' : ''; ?>" data-filter-drop>
			<button type="button" class="tb-filter-pill" data-filter-toggle aria-expanded="false" aria-haspopup="listbox">
				<?php echo esc_html( $trans_label ); ?>
				<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<div class="tb-filter-panel" role="listbox" aria-label="<?php echo esc_attr( $lbl_trans ); ?>">
				<?php if ( $trans_active ) : ?>
					<a href="<?php echo $reset_url( 'transmission' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" class="tb-filter-panel__reset">
						&times; <?php echo esc_html( $label_reset ); ?>
					</a>
				<?php endif; ?>
				<?php foreach ( $trans_labels as $val => $lbl ) : ?>
					<a
						href="<?php echo $option_url( 'transmission', $val ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						class="tb-filter-panel__option<?php echo $val === $active_trans ? ' is-selected' : ''; ?>"
						role="option"
						aria-selected="<?php echo $val === $active_trans ? 'true' : 'false'; ?>"
					>
						<?php echo esc_html( $lbl ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<?php
		/* ── Price range ──────────────────────────────────────────────── */
		$lbl_price    = tb_opt( 'tb_cat_filter_price', __( 'Ціна', 'taxi-rental' ) );
		$price_active = '' !== $active_pmin || '' !== $active_pmax;
		$price_label  = $price_active
			? ( ( '' !== $active_pmin ? $active_pmin . '€' : '…' ) . ' – ' . ( '' !== $active_pmax ? $active_pmax . '€' : '…' ) )
			: $lbl_price;

		// Build the action URL for the mini price form preserving all non-price filters.
		$price_params = $base_params;
		unset( $price_params['price_min'], $price_params['price_max'] );
		$price_form_action = empty( $price_params )
			? $archive_url
			: add_query_arg( $price_params, $archive_url );
		?>
		<div class="tb-filter-drop<?php echo $price_active ? ' tb-filter-drop--active' : ''; ?>" data-filter-drop>
			<button type="button" class="tb-filter-pill" data-filter-toggle aria-expanded="false" aria-haspopup="dialog">
				<?php echo esc_html( $price_label ); ?>
				<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<div class="tb-filter-panel tb-filter-panel--price" role="dialog" aria-label="<?php echo esc_attr( $lbl_price ); ?>">
				<form method="get" action="<?php echo esc_url( $price_form_action ); ?>">
					<div class="tb-filter-panel__price-inputs">
						<input
							type="number"
							name="price_min"
							class="tb-filter-panel__price-input"
							placeholder="<?php echo esc_attr( $label_pmin ); ?>"
							value="<?php echo esc_attr( $active_pmin ); ?>"
							min="0"
						>
						<input
							type="number"
							name="price_max"
							class="tb-filter-panel__price-input"
							placeholder="<?php echo esc_attr( $label_pmax ); ?>"
							value="<?php echo esc_attr( $active_pmax ); ?>"
							min="0"
						>
					</div>
					<?php if ( $price_active ) : ?>
						<a href="<?php echo $reset_url( 'price_min' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						   onclick="window.location=this.href.replace('price_min=','').replace(/price_max=[^&]*/,'');return false;"
						   class="tb-filter-panel__reset" style="margin-bottom:8px;display:block;">
							&times; <?php echo esc_html( $label_preset ); ?>
						</a>
					<?php endif; ?>
					<button type="submit" class="tb-filter-panel__apply">
						<?php echo esc_html( $label_apply ); ?>
					</button>
				</form>
			</div>
		</div>

		<?php
		/* ── Deposit range ────────────────────────────────────────────── */
		$lbl_deposit    = tb_opt( 'tb_cat_filter_deposit', __( 'Depozit', 'taxi-rental' ) );
		$deposit_active = '' !== $active_dmin || '' !== $active_dmax;
		$deposit_label  = $deposit_active
			? ( ( '' !== $active_dmin ? $active_dmin . '€' : '…' ) . ' – ' . ( '' !== $active_dmax ? $active_dmax . '€' : '…' ) )
			: $lbl_deposit;

		// Action URL preserving all non-deposit filters.
		$deposit_params = $base_params;
		unset( $deposit_params['deposit_min'], $deposit_params['deposit_max'] );
		$deposit_form_action = empty( $deposit_params )
			? $archive_url
			: add_query_arg( $deposit_params, $archive_url );
		?>
		<div class="tb-filter-drop<?php echo $deposit_active ? ' tb-filter-drop--active' : ''; ?>" data-filter-drop>
			<button type="button" class="tb-filter-pill" data-filter-toggle aria-expanded="false" aria-haspopup="dialog">
				<?php echo esc_html( $deposit_label ); ?>
				<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<div class="tb-filter-panel tb-filter-panel--price" role="dialog" aria-label="<?php echo esc_attr( $lbl_deposit ); ?>">
				<form method="get" action="<?php echo esc_url( $deposit_form_action ); ?>">
					<div class="tb-filter-panel__price-inputs">
						<input
							type="number"
							name="deposit_min"
							class="tb-filter-panel__price-input"
							placeholder="<?php echo esc_attr( $label_dmin ); ?>"
							value="<?php echo esc_attr( $active_dmin ); ?>"
							min="0"
						>
						<input
							type="number"
							name="deposit_max"
							class="tb-filter-panel__price-input"
							placeholder="<?php echo esc_attr( $label_dmax ); ?>"
							value="<?php echo esc_attr( $active_dmax ); ?>"
							min="0"
						>
					</div>
					<?php if ( $deposit_active ) : ?>
						<a href="<?php echo $reset_url( 'deposit_min' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
						   onclick="window.location=this.href.replace('deposit_min=','').replace(/deposit_max=[^&]*/,'');return false;"
						   class="tb-filter-panel__reset" style="margin-bottom:8px;display:block;">
							&times; <?php echo esc_html( $label_dreset ); ?>
						</a>
					<?php endif; ?>
					<button type="submit" class="tb-filter-panel__apply">
						<?php echo esc_html( $label_apply ); ?>
					</button>
				</form>
			</div>
		</div>

		<div class="tb-filter-sheet__foot">
			<a href="<?php echo esc_url( $reset_all_url ); ?>" class="tb-btn tb-btn--outline tb-filter-sheet__reset">
				<?php echo esc_html( $label_reset ); ?>
			</a>
			<?php // Options navigate on click, so "apply" only has to dismiss the sheet. ?>
			<button type="button" class="tb-btn tb-btn--accent tb-filter-sheet__apply" data-filters-close>
				<?php echo esc_html( $label_apply ); ?>
			</button>
		</div>

	</div><!-- .tb-filters-bar__pills -->

	<div class="tb-filter-drop tb-filter-drop--sort<?php echo $sort_active ? ' tb-filter-drop--active' : ''; ?>" data-filter-drop>
		<button type="button" class="tb-filter-sort-btn" data-filter-toggle aria-expanded="false" title="<?php echo esc_attr( $lbl_sort ); ?>">
			<?php // Desktop (Figma 66:308): the same 37px circular icon button as the admin panel's sort control — label span below is mobile-only. ?>
			<span class="tb-filter-sort-btn__label"><?php echo esc_html( $sort_active ? $order_labels[ $active_order ] : $lbl_sort ); ?></span>
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
		</button>
		<div class="tb-filter-panel tb-filter-panel--sort">
			<?php foreach ( $order_labels as $order_val => $order_lbl ) :
				$ord_params = $base_params;
				if ( 'date_desc' === $order_val ) {
					unset( $ord_params['orderby'] );
				} else {
					$ord_params['orderby'] = $order_val;
				}
				$ord_url = empty( $ord_params ) ? $archive_url : add_query_arg( $ord_params, $archive_url );
				$is_cur  = ( $order_val === $active_order || ( 'date_desc' === $order_val && 'date_desc' === $active_order ) );
			?>
				<a
					href="<?php echo esc_url( $ord_url ); ?>"
					class="tb-sort-option<?php echo $is_cur ? ' is-selected' : ''; ?>"
					role="radio"
					aria-checked="<?php echo $is_cur ? 'true' : 'false'; ?>"
				>
					<?php echo esc_html( $order_lbl ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>

</div><!-- .tb-filters-bar -->
