<?php
/**
 * Front-admin tab: Add / Edit blog post.
 * Sub-route: ?tab=blog&new=1  OR  ?tab=blog&edit=POST_ID
 * Inherits: $panel_url, $tab, $blog_post_id, $is_blog_new.
 */

$is_new   = $is_blog_new ?? false;
$post_id  = $blog_post_id ?? 0;
$post     = $post_id ? get_post( $post_id ) : null;

// Guard: must be a 'post' type.
if ( $post && 'post' !== $post->post_type ) {
	echo '<div class="tb-fa-empty">' . esc_html( tb_opt( 'tb_fa_ae_not_found', 'Nenašlo sa.' ) ) . '</div>';
	return;
}

$post_title   = $post ? $post->post_title : '';
$post_content = $post ? $post->post_content : '';
$post_status  = $post ? $post->post_status : 'draft';
$post_date    = $post ? date( 'Y-m-d', strtotime( $post->post_date ) ) : date( 'Y-m-d' );
$thumb_id     = $post ? (int) get_post_thumbnail_id( $post_id ) : 0;
$thumb_url    = $thumb_id ? (string) wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
$post_slug    = $post ? $post->post_name : '';

// Blog categories (WP native 'category' taxonomy).
$blog_cats    = get_terms( [
	'taxonomy'   => 'category',
	'hide_empty' => false,
	'orderby'    => 'name',
] );
if ( is_wp_error( $blog_cats ) ) {
	$blog_cats = [];
}
$post_cats      = $post ? wp_get_post_categories( $post_id ) : [];
$selected_cat   = $post_cats ? (int) $post_cats[0] : 0;

$back_url = add_query_arg( 'tab', 'blog', $panel_url );
$page_title = $is_new
	? tb_opt( 'tb_fa_be_new_title', 'Nový článok' )
	: tb_opt( 'tb_fa_be_edit_title', 'Upraviť článok' );

$saved_state = sanitize_key( wp_unslash( $_GET['saved'] ?? '' ) );
?>

<?php if ( 'draft' === $saved_state || 'publish' === $saved_state ) : ?>
<div class="tb-fa-notify" role="status" aria-live="polite">
	<div class="tb-fa-notify__icon" aria-hidden="true">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17L4 12" stroke="black" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
	</div>
	<span class="tb-fa-notify__text">
		<?php
		if ( 'draft' === $saved_state ) {
			echo esc_html( tb_opt( 'tb_fa_be_toast_draft', 'Uložené ako koncept' ) );
		} else {
			echo esc_html( tb_opt( 'tb_fa_be_toast_published', 'Článok bol zverejnený' ) );
		}
		?>
	</span>
</div>
<?php endif; ?>

<!-- ── Page head ─────────────────────────────────────────────────── -->
<div class="tb-fa-blog-edit-topbar">
	<h1 class="tb-fa-page-head__title tb-fa-blog-edit-topbar__title">
		<?php echo esc_html( $page_title ); ?>
	</h1>

	<div class="tb-fa-blog-edit-topbar__actions">
		<button type="button" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--lg" id="tb-blog-save-draft">
			<?php echo esc_html( tb_opt( 'tb_fa_be_save_draft', 'Uložiť koncept' ) ); ?>
		</button>
		<button type="button" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--lg" id="tb-blog-save-publish">
			<?php echo esc_html( tb_opt( 'tb_fa_be_publish_btn', 'Zverejniť článok' ) ); ?>
		</button>
	</div>
</div>

<!-- ── Two-column layout (grid-areas: alert/basic/content left, photo right) ── -->
<div class="tb-fa-blog-edit-layout">

		<!-- Form-level error banner (Figma 410:4956) -->
		<div class="tb-fa-blog-form-alert" id="tb-blog-form-alert" hidden role="alert">
			<span class="tb-fa-blog-form-alert__icon" aria-hidden="true">!</span>
			<p class="tb-fa-blog-form-alert__text">
				<?php echo esc_html( tb_opt( 'tb_fa_be_err_banner', 'Prosím, opravte chyby pred odoslaním.' ) ); ?>
			</p>
		</div>

		<!-- Базова інформація -->
		<div class="tb-fa-blog-box tb-fa-blog-box--basic">
			<h2 class="tb-fa-blog-box__title"><?php echo esc_html( tb_opt( 'tb_fa_be_section_basic', 'Základné informácie' ) ); ?></h2>

			<div class="tb-fa-blog-field">
				<label class="tb-fa-blog-label" for="tb-blog-title">
					<?php echo esc_html( tb_opt( 'tb_fa_be_art_title', 'Názov článku' ) ); ?> <span class="tb-fa-required">*</span>
				</label>
				<input type="text" id="tb-blog-title" class="tb-fa-input tb-fa-input--full"
					value="<?php echo esc_attr( $post_title ); ?>"
					placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_be_art_title_ph', 'Zadajte názov článku…' ) ); ?>"
					data-placeholder-default="<?php echo esc_attr( tb_opt( 'tb_fa_be_art_title_ph', 'Zadajte názov článku…' ) ); ?>"
					data-placeholder-error="<?php echo esc_attr( tb_opt( 'tb_fa_be_err_field_required', 'Toto pole je povinné' ) ); ?>"
					autocomplete="off">
				<p class="tb-fa-blog-slug-preview" id="tb-blog-slug-preview">
					<?php if ( $post_slug ) : ?>
						/<span id="tb-blog-slug-text"><?php echo esc_html( $post_slug ); ?></span>
					<?php else : ?>
						<span id="tb-blog-slug-text"></span>
					<?php endif; ?>
				</p>
				<p class="tb-fa-field-error" id="tb-blog-title-error">
					<?php echo esc_html( tb_opt( 'tb_fa_be_err_title', 'Prosím, zadajte názov článku.' ) ); ?>
				</p>
			</div>

			<div class="tb-fa-blog-field">
				<label class="tb-fa-blog-label" for="tb-blog-category">
					<?php echo esc_html( tb_opt( 'tb_fa_be_category', 'Kategória' ) ); ?> <span class="tb-fa-required">*</span>
				</label>
				<select id="tb-blog-category" class="tb-fa-select tb-fa-select--full">
					<option value="0"><?php echo esc_html( tb_opt( 'tb_fa_be_category_ph', '— Vyberte kategóriu —' ) ); ?></option>
					<?php foreach ( $blog_cats as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat->term_id ); ?>"
						<?php selected( $selected_cat, $cat->term_id ); ?>>
						<?php echo esc_html( $cat->name ); ?>
					</option>
					<?php endforeach; ?>
				</select>
				<p class="tb-fa-field-error" id="tb-blog-category-error">
					<?php echo esc_html( tb_opt( 'tb_fa_be_err_category', 'Toto pole je povinné.' ) ); ?>
				</p>
			</div>

			<div class="tb-fa-blog-field">
				<label class="tb-fa-blog-label" for="tb-blog-date">
					<?php echo esc_html( tb_opt( 'tb_fa_be_pub_date', 'Dátum zverejnenia' ) ); ?>
				</label>
				<?php
				// An already-published article may legitimately carry a past
				// date, so its own date stays selectable — but nothing earlier.
				// The attribute used to be empty on existing posts, which left
				// the field with no lower bound at all.
				$date_min = ( $is_new || ! $post_date ) ? current_time( 'Y-m-d' ) : $post_date;
				?>
				<input type="date" id="tb-blog-date" class="tb-fa-input"
					value="<?php echo esc_attr( $post_date ); ?>"
					min="<?php echo esc_attr( $date_min ); ?>">
			</div>
		</div>

		<!-- Контент статті -->
		<div class="tb-fa-blog-box tb-fa-blog-box--content">
			<h2 class="tb-fa-blog-box__title"><?php echo esc_html( tb_opt( 'tb_fa_be_content', 'Obsah článku' ) ); ?></h2>
			<?php
			wp_editor(
				$post_content,
				'tb_blog_content',
				[
					'textarea_name' => 'tb_blog_content_ta',
					'media_buttons' => false,
					'textarea_rows' => 22,
					'tinymce'       => [
						'toolbar1' => 'bold italic underline strikethrough | bullist numlist blockquote | link unlink | undo redo',
						'toolbar2' => '',
						'plugins'  => 'lists,link,paste,tabfocus',
					],
					'quicktags'     => true,
				]
			);
			?>
			<p class="tb-fa-field-error" id="tb-blog-content-error">
				<?php echo esc_html( tb_opt( 'tb_fa_be_err_content', 'Článok je príliš krátky (minimálne 500 znakov).' ) ); ?>
			</p>
		</div>

		<!-- Фото -->
		<div class="tb-fa-blog-box tb-fa-blog-box--photo">
			<h2 class="tb-fa-blog-box__title"><?php echo esc_html( tb_opt( 'tb_fa_be_photo', 'Fotografia' ) ); ?></h2>
			<p class="tb-fa-blog-side-hint"><?php echo esc_html( tb_opt( 'tb_fa_be_photo_hint', 'Fotografia bude na obálke článku.' ) ); ?></p>

			<input type="hidden" id="tb-blog-thumb-id" value="<?php echo esc_attr( $thumb_id ?: '' ); ?>">
			<input type="hidden" id="tb-blog-thumb-cleared" value="0">

			<div class="tb-fa-blog-photo-area" id="tb-blog-photo-area">
				<?php if ( $thumb_url ) : ?>
				<div class="tb-fa-blog-photo-preview" id="tb-blog-photo-preview">
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" id="tb-blog-photo-img">
					<button type="button" class="tb-fa-blog-photo-remove" id="tb-blog-photo-remove"
						aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_be_delete_photo', 'Odstrániť fotografiu' ) ); ?>">×</button>
				</div>
				<?php else : ?>
				<div class="tb-fa-blog-photo-preview tb-fa-blog-photo-preview--hidden" id="tb-blog-photo-preview">
					<img src="" alt="" id="tb-blog-photo-img">
					<button type="button" class="tb-fa-blog-photo-remove" id="tb-blog-photo-remove"
						aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_be_delete_photo', 'Odstrániť fotografiu' ) ); ?>">×</button>
				</div>
				<?php endif; ?>

				<button type="button" class="tb-fa-blog-add-photo" id="tb-blog-add-photo">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
					<?php echo esc_html( tb_opt( 'tb_fa_be_add_photo', 'Pridať fotografiu' ) ); ?>
				</button>
			</div>
			<p class="tb-fa-field-error" id="tb-blog-photo-error">
				<?php echo esc_html( tb_opt( 'tb_fa_be_err_photo', 'Fotografia je povinná.' ) ); ?>
			</p>
		</div>

</div><!-- /.tb-fa-blog-edit-layout -->

<!-- Hidden fields -->
<input type="hidden" id="tb-blog-post-id" value="<?php echo esc_attr( $post_id ?: '' ); ?>">

<!-- Bottom action buttons (repeat) -->
<div class="tb-fa-blog-edit-bottom">
	<button type="button" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--lg tb-blog-save-draft-bottom">
		<?php echo esc_html( tb_opt( 'tb_fa_be_save_draft', 'Uložiť koncept' ) ); ?>
	</button>
	<button type="button" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--lg tb-blog-save-publish-bottom">
		<?php echo esc_html( tb_opt( 'tb_fa_be_publish_btn', 'Zverejniť článok' ) ); ?>
	</button>
</div>

<script>
// Blog edit page — inline script to avoid global namespace pollution.
// (Runs in the front-admin DOMContentLoaded context after this HTML is injected.)
(function () {
	var BACK_URL  = <?php echo wp_json_encode( esc_url( $back_url ) ); ?>;
	var PANEL_URL = <?php echo wp_json_encode( esc_url( $panel_url ) ); ?>;
	window.tbBlogEdit = { backUrl: BACK_URL, panelUrl: PANEL_URL };
}());
</script>
