<?php
/**
 * Modal: Add / Edit Blog Category.
 * Opened by JS for both add and edit actions on the blog tab.
 */
?>
<div id="tb-fa-modal-blog-cat" class="tb-fa-modal" hidden aria-modal="true" role="dialog"
     aria-labelledby="tb-fa-blog-cat-heading">
	<div class="tb-fa-modal__backdrop" id="tb-fa-blog-cat-backdrop"></div>
	<div class="tb-fa-modal__dialog">
		<button type="button" class="tb-fa-modal__close-btn" id="tb-fa-blog-cat-close"
			aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_mbc_close', 'Zavrieť' ) ); ?>">×</button>

		<h2 class="tb-fa-modal__title" id="tb-fa-blog-cat-heading">
			<?php echo esc_html( tb_opt( 'tb_fa_mbc_title', 'Kategória blogu' ) ); ?>
		</h2>

		<input type="hidden" id="tb-fa-blog-cat-term-id" value="0">

		<div class="tb-fa-blog-field" style="margin-top:20px">
			<label class="tb-fa-blog-label" for="tb-fa-blog-cat-name">
				<?php echo esc_html( tb_opt( 'tb_fa_mbc_name_label', 'Názov kategórie' ) ); ?> <span class="tb-fa-required">*</span>
			</label>
			<input type="text" id="tb-fa-blog-cat-name" class="tb-fa-input tb-fa-input--full"
				placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_mbc_name_ph', 'Napr.: Tipy pre vodičov' ) ); ?>">
		</div>

		<div class="tb-fa-modal__footer tb-fa-modal__footer--spaced">
			<button type="button" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--flex" id="tb-fa-blog-cat-cancel">
				<?php echo esc_html( tb_opt( 'tb_fa_mbc_cancel', 'Zrušiť' ) ); ?>
			</button>
			<button type="button" class="tb-fa-btn tb-fa-btn--dark tb-fa-btn--flex" id="tb-fa-blog-cat-save">
				<?php echo esc_html( tb_opt( 'tb_fa_mbc_save', 'Uložiť' ) ); ?>
			</button>
		</div>
	</div>
</div>
