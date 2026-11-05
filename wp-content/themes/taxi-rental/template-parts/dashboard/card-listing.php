<?php
/**
 * template-parts/dashboard/card-listing.php
 * Single row card for the owner's listing table.
 * Expects global $post (inside The Loop).
 *
 * @var WP_Post $post
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id    = get_the_ID();
$title      = get_the_title();
$status     = get_post_status();
$price      = get_field( 'price_week' );
$city_terms = get_the_terms( $post_id, 'city' );
$city_name  = ( $city_terms && ! is_wp_error( $city_terms ) ) ? esc_html( $city_terms[0]->name ) : '';
$thumbnail  = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
$views      = (int) get_post_meta( $post_id, '_tb_listing_views', true );

// Archive nonce URL.
$archive_url = wp_nonce_url(
	add_query_arg(
		[ 'tb_action' => 'archive', 'post_id' => $post_id ],
		get_permalink( get_page_by_path( 'cabinet' ) )
	),
	'tb_archive_' . $post_id
);

// Edit URL (Phase 7).
$edit_url = add_query_arg( 'post_id', $post_id, tb_url( '/cabinet/add-listing/' ) );

// ACF label lookups.
$lbl_status_publish  = tb_opt( 'tb_cab_status_publish',  __( 'Опубліковано', 'taxi-rental' ) );
$lbl_status_pending  = tb_opt( 'tb_cab_status_pending',  __( 'На модерації', 'taxi-rental' ) );
$lbl_status_draft    = tb_opt( 'tb_cab_status_draft',    __( 'Чернетка', 'taxi-rental' ) );
$lbl_status_rejected = tb_opt( 'tb_cab_status_rejected', __( 'Відхилено', 'taxi-rental' ) );
$lbl_status_archived = tb_opt( 'tb_cab_status_archived', __( 'Архівовано', 'taxi-rental' ) );
$lbl_archive_confirm = tb_opt( 'tb_cab_archive_confirm', __( 'Архівувати це оголошення?', 'taxi-rental' ) );
$lbl_archive_btn     = tb_opt( 'tb_cab_archive_btn',     __( 'Архівувати', 'taxi-rental' ) );
$lbl_restore_btn     = tb_opt( 'tb_cab_restore_btn',     __( 'Відновити', 'taxi-rental' ) );
$lbl_edit_btn        = tb_opt( 'tb_cab_edit_btn',        __( 'Редагувати', 'taxi-rental' ) );

// Status badge.
$status_labels = [
	'publish'  => $lbl_status_publish,
	'pending'  => $lbl_status_pending,
	'draft'    => $lbl_status_draft,
	'rejected' => $lbl_status_rejected,
	'archived' => $lbl_status_archived,
];
$status_colors = [
	'publish'  => '#02f797',
	'pending'  => '#f4d597',
	'draft'    => '#e5e5e5',
	'rejected' => '#ffbdbd',
	'archived' => '#e5e5e5',
];
$badge_label = $status_labels[ $status ] ?? esc_html( $status );
$badge_color = $status_colors[ $status ] ?? '#e5e5e5';

$can_archive = in_array( $status, [ 'publish', 'pending', 'draft', 'rejected' ], true );
$can_restore = 'archived' === $status;
?>
<div class="tb-listing-row" id="listing-<?php echo esc_attr( $post_id ); ?>">

	<div class="tb-listing-row__main">
		<!-- Thumbnail -->
		<div class="tb-listing-row__thumb">
			<?php if ( $thumbnail ) : ?>
				<img
					src="<?php echo esc_url( $thumbnail ); ?>"
					alt="<?php echo esc_attr( $title ); ?>"
					loading="lazy"
					width="140"
					height="100"
				>
			<?php else : ?>
				<div class="tb-listing-row__thumb-placeholder" aria-hidden="true"></div>
			<?php endif; ?>
		</div>

		<!-- Info -->
		<div class="tb-listing-row__info">
			<div class="tb-listing-row__meta">
				<div class="tb-listing-row__title-row">
					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="tb-listing-row__title">
						<?php echo esc_html( $title ); ?>
					</a>
					<span
						class="tb-listing-row__badge"
						style="background-color:<?php echo esc_attr( $badge_color ); ?>;"
					>
						<?php echo esc_html( $badge_label ); ?>
					</span>
				</div>
				<?php if ( $city_name ) : ?>
					<div class="tb-listing-row__location">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/></svg>
						<span><?php echo esc_html( $city_name ); ?></span>
					</div>
				<?php endif; ?>
			</div>

			<div class="tb-listing-row__stats">
				<span class="tb-listing-row__stat">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="currentColor"/></svg>
					<?php
					printf(
						/* translators: %d: view count */
						esc_html( tb_plural_opt( (int) $views, 'view', __( '%d zobrazenie', 'taxi-rental' ), __( '%d zobrazenia', 'taxi-rental' ), __( '%d zobrazení', 'taxi-rental' ) ) ),
						$views
					);
					?>
				</span>
			</div>
		</div>

		<!-- Price -->
		<?php if ( $price ) : ?>
			<div class="tb-listing-row__price">
				<?php
				printf(
					/* translators: %s: price amount */
					esc_html__( 'від %s€ / тиждень', 'taxi-rental' ),
					esc_html( number_format( (float) $price, 0, '.', ' ' ) )
				);
				?>
			</div>
		<?php endif; ?>
	</div><!-- .tb-listing-row__main -->

	<div class="tb-listing-row__footer">
		<span class="tb-listing-row__id">
			<?php printf( 'ID: %d', $post_id ); ?>
		</span>

		<div class="tb-listing-row__actions">
			<?php if ( $can_archive ) : ?>
				<a
					href="<?php echo esc_url( $archive_url ); ?>"
					class="tb-btn tb-btn--outline tb-btn--sm"
					onclick="return confirm('<?php echo esc_attr( $lbl_archive_confirm ); ?>')"
				>
					<?php echo esc_html( $lbl_archive_btn ); ?>
				</a>
			<?php elseif ( $can_restore ) : ?>
				<a
					href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'tb_action' => 'unarchive', 'post_id' => $post_id ], get_permalink( get_page_by_path( 'cabinet' ) ) ), 'tb_archive_' . $post_id ) ); ?>"
					class="tb-btn tb-btn--outline tb-btn--sm"
				>
					<?php echo esc_html( $lbl_restore_btn ); ?>
				</a>
			<?php endif; ?>

			<a href="<?php echo esc_url( $edit_url ); ?>" class="tb-btn tb-btn--outline tb-btn--sm">
				<?php echo esc_html( $lbl_edit_btn ); ?>
			</a>
		</div>
	</div><!-- .tb-listing-row__footer -->

</div><!-- .tb-listing-row -->
