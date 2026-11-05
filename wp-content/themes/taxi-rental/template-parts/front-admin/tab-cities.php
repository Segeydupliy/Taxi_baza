<?php
/**
 * Front-admin tab: Міста (Cities).
 * Two-tier card layout (body 148px + bar 75px) — inline add/edit via AJAX.
 * Inherits from page-front-admin.php: $panel_url, $tab.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$terms = get_terms( [
	'taxonomy'   => 'city',
	'hide_empty' => false,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );
if ( is_wp_error( $terms ) ) {
	$terms = [];
}
$city_count = count( $terms );

// Distinct listing-owners per city.
global $wpdb;
$city_user_counts = [];
$rows = $wpdb->get_results( $wpdb->prepare(
	"SELECT tt.term_id, COUNT(DISTINCT p.post_author) AS cnt
	 FROM {$wpdb->term_taxonomy} tt
	 JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
	 JOIN {$wpdb->posts} p ON p.ID = tr.object_id
	 WHERE tt.taxonomy = %s
	   AND p.post_type = %s
	   AND p.post_status IN ( 'publish', 'pending', 'draft', 'archived', 'rejected' )
	 GROUP BY tt.term_id",
	'city',
	'car_listing'
) );
foreach ( (array) $rows as $r ) {
	$city_user_counts[ (int) $r->term_id ] = (int) $r->cnt;
}
?>

<!-- ── Page head ──────────────────────────────────────────────── -->
<div class="tb-fa-page-head tb-fa-page-head--with-action">
	<div>
		<h1 class="tb-fa-page-head__title"><?php echo esc_html( tb_opt( 'tb_fa_cit_heading', 'Mestá' ) ); ?></h1>
		<p class="tb-fa-page-head__stats">
			<?php printf(
				/* translators: %d: city count */
				esc_html( tb_opt( 'tb_fa_cit_count', '%d aktívnych miest' ) ),
				$city_count
			); ?>
		</p>
	</div>
	<button type="button" id="tb-fa-add-city-btn" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--icon">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
		</svg>
		<?php echo esc_html( tb_opt( 'tb_fa_cit_add_btn', 'Pridať mesto' ) ); ?>
	</button>
</div>

<!-- ── Cities grid ───────────────────────────────────────────── -->
<div class="tb-fa-city-grid" id="tb-fa-city-grid">

	<!-- Add-city card (hidden by default, shown on button click) -->
	<div class="tb-fa-city-card is-editing is-new" data-term-id="0" id="tb-fa-add-city-card" style="display:none">
		<div class="tb-fa-city-card__body">
			<div class="tb-fa-city-card__thumb tb-fa-city-card__thumb--empty" data-city-action="pick-image">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" stroke="currentColor" stroke-width="1.5"/>
					<circle cx="12" cy="13" r="4" stroke="currentColor" stroke-width="1.5"/>
				</svg>
				<div class="tb-fa-city-card__thumb-overlay" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none">
						<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" stroke="#fff" stroke-width="1.5"/>
						<circle cx="12" cy="13" r="4" stroke="#fff" stroke-width="1.5"/>
					</svg>
				</div>
				<input type="hidden" name="image_id" value="">
			</div>
			<div class="tb-fa-city-card__info">
				<div class="tb-fa-city-card__edit-info">
					<label class="tb-fa-city-card__field-label">
						<?php echo esc_html( tb_opt( 'tb_fa_cit_name_label', 'Názov mesta' ) ); ?> <span class="tb-fa-required">*</span>
					</label>
					<input
						type="text"
						class="tb-fa-city-name-input tb-fa-field__input"
						placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_cit_name_ph', 'Napr.: Bratislava' ) ); ?>"
					>
				</div>
			</div>
		</div>
		<div class="tb-fa-city-card__bar">
			<span class="tb-fa-city-slug tb-fa-city-card__slug">/</span>
			<div class="tb-fa-city-card__edit-actions">
				<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-city-action="cancel">
					<?php echo esc_html( tb_opt( 'tb_fa_ue_cancel_btn', 'Zrušiť' ) ); ?>
				</button>
				<button type="button" class="tb-fa-btn tb-fa-btn--accent" data-city-action="save">
					<?php echo esc_html( tb_opt( 'tb_fa_ue_save_btn', 'Uložiť' ) ); ?>
				</button>
			</div>
		</div>
	</div>

	<?php foreach ( $terms as $term ) :
		$image_id  = (int) get_term_meta( $term->term_id, 'tb_image_id', true );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium_large' ) : '';

		$listing_count = (int) $term->count;
		$user_count    = (int) ( $city_user_counts[ $term->term_id ] ?? 0 );
	?>
	<div
		class="tb-fa-city-card"
		data-term-id="<?php echo esc_attr( $term->term_id ); ?>"
	>
		<div class="tb-fa-city-card__body">

			<!-- Thumbnail -->
			<div
				class="tb-fa-city-card__thumb<?php echo $image_url ? '' : ' tb-fa-city-card__thumb--empty'; ?>"
				data-city-action="pick-image"
				<?php if ( $image_url ) : ?>style="background-image:url('<?php echo esc_url( $image_url ); ?>')"<?php endif; ?>
			>
				<?php if ( ! $image_url ) : ?>
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
						<circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="1.5"/>
						<path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="1.5"/>
					</svg>
				<?php endif; ?>
				<div class="tb-fa-city-card__thumb-overlay" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none">
						<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z" stroke="#fff" stroke-width="1.5"/>
						<circle cx="12" cy="13" r="4" stroke="#fff" stroke-width="1.5"/>
					</svg>
				</div>
				<input type="hidden" name="image_id" value="<?php echo esc_attr( $image_id ?: '' ); ?>">
			</div>

			<!-- Info -->
			<div class="tb-fa-city-card__info">

				<!-- Display mode -->
				<div class="tb-fa-city-card__display-info">
					<h3 class="tb-fa-city-name"><?php echo esc_html( $term->name ); ?></h3>
					<div class="tb-fa-city-stats">
						<span class="tb-fa-city-stat">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.5"/>
								<circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.5"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $listing_count, 'listing', '%d inzerát', '%d inzeráty', '%d inzerátov' ) ), (int) $listing_count ); ?>
						</span>
						<span class="tb-fa-city-stat">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M21 11.5a8.38 8.38 0 01-9 8.4L3 21l1.1-3.3A8.38 8.38 0 1121 11.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
							</svg>
							<?php printf( esc_html( tb_plural_opt( (int) $user_count, 'user', '%d používateľ', '%d používatelia', '%d používateľov' ) ), (int) $user_count ); ?>
						</span>
					</div>
				</div>

				<!-- Edit mode -->
				<div class="tb-fa-city-card__edit-info">
					<label class="tb-fa-city-card__field-label">
						<?php echo esc_html( tb_opt( 'tb_fa_cit_name_label', 'Názov mesta' ) ); ?> <span class="tb-fa-required">*</span>
					</label>
					<input
						type="text"
						class="tb-fa-city-name-input tb-fa-field__input"
						value="<?php echo esc_attr( $term->name ); ?>"
					>
				</div>

			</div>
		</div>

		<!-- Bar -->
		<div class="tb-fa-city-card__bar">
			<span class="tb-fa-city-slug tb-fa-city-card__slug">/<?php echo esc_html( $term->slug ); ?></span>

			<!-- Display actions -->
			<div class="tb-fa-city-card__display-actions">
				<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-city-action="delete">
					<?php echo esc_html( tb_opt( 'tb_fa_cit_delete_btn', 'Odstrániť' ) ); ?>
				</button>
				<button type="button" class="tb-fa-btn tb-fa-btn--dark" data-city-action="edit">
					<?php echo esc_html( tb_opt( 'tb_fa_ud_edit_btn', 'Upraviť' ) ); ?>
				</button>
			</div>

			<!-- Edit actions -->
			<div class="tb-fa-city-card__edit-actions">
				<button type="button" class="tb-fa-btn tb-fa-btn--outline" data-city-action="delete">
					<?php echo esc_html( tb_opt( 'tb_fa_cit_delete_btn', 'Odstrániť' ) ); ?>
				</button>
				<button type="button" class="tb-fa-btn tb-fa-btn--accent" data-city-action="save">
					<?php echo esc_html( tb_opt( 'tb_fa_ue_save_btn', 'Uložiť' ) ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php endforeach; ?>

	<?php if ( empty( $terms ) ) : ?>
		<div class="tb-fa-empty"><?php echo esc_html( tb_opt( 'tb_fa_cit_empty_first', 'Mestá ešte neexistujú. Pridajte prvé mesto.' ) ); ?></div>
	<?php endif; ?>
</div>
