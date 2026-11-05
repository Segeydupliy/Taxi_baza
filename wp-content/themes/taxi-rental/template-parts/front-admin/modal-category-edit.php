<?php
/**
 * Front-admin category edit/add modal.
 * Opened by JS for both "Додати категорію" (new) and "Редагувати" (edit).
 * JS pre-fills: term-id, parent name, children list.
 */
?>
<div id="tb-fa-modal-cat-edit" class="tb-fa-modal" role="dialog" aria-modal="true"
     aria-labelledby="tb-fa-cat-edit-title" hidden>
	<div id="tb-fa-cat-edit-backdrop" class="tb-fa-modal__backdrop"></div>
	<div class="tb-fa-modal__dialog tb-fa-modal__dialog--wide">

		<div class="tb-fa-modal__header">
			<h2 id="tb-fa-cat-edit-title" class="tb-fa-modal__title"
				data-label-edit="<?php echo esc_attr( tb_opt( 'tb_fa_mce_title', 'Upraviť' ) ); ?>"
				data-label-add="<?php echo esc_attr( tb_opt( 'tb_fa_mce_title_add', 'Pridať kategóriu' ) ); ?>">
				<?php echo esc_html( tb_opt( 'tb_fa_mce_title', 'Upraviť' ) ); ?>
			</h2>
			<button id="tb-fa-cat-edit-close" type="button" class="tb-fa-modal__close-btn" aria-label="<?php echo esc_attr( tb_opt( 'tb_fa_mce_close', 'Zavrieť' ) ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>
		</div>

		<div class="tb-fa-cat-edit-body">

			<input type="hidden" id="tb-fa-cat-term-id" value="0">

			<div class="tb-fa-cat-edit-field">
				<label for="tb-fa-cat-edit-name" class="tb-fa-cat-edit-label">
					<?php echo esc_html( tb_opt( 'tb_fa_mce_block_name', 'Názov bloku kategórií' ) ); ?>
					<span class="tb-fa-required">*</span>
				</label>
				<input type="text" id="tb-fa-cat-edit-name" class="tb-fa-field__input"
					placeholder="<?php echo esc_attr( tb_opt( 'tb_fa_mce_block_ph', 'Napr.: Bolt' ) ); ?>">
			</div>

			<div class="tb-fa-cat-edit-section">
				<p class="tb-fa-cat-edit-section-label">
					<?php echo esc_html( tb_opt( 'tb_fa_mce_cats_label', 'Kategórie' ) ); ?>
				</p>
				<ul id="tb-fa-cat-children-list" class="tb-fa-cat-modal-list">
					<!-- populated by JS when modal opens -->
				</ul>
				<button type="button" id="tb-fa-cat-add-child" class="tb-fa-cat-add-child-btn">
					+ <?php echo esc_html( tb_opt( 'tb_fa_mce_add_more', 'Pridať ďalšie' ) ); ?>
				</button>
			</div>

		</div>

		<div class="tb-fa-modal__footer tb-fa-modal__footer--spaced">
			<button id="tb-fa-cat-edit-cancel" type="button" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--flex">
				<?php echo esc_html( tb_opt( 'tb_fa_mce_cancel', 'Zrušiť' ) ); ?>
			</button>
			<button id="tb-fa-cat-edit-save" type="button" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--flex">
				<?php echo esc_html( tb_opt( 'tb_fa_mce_save', 'Uložiť' ) ); ?>
			</button>
		</div>

	</div>
</div>
