<?php
/**
 * Cabinet — "Мій профіль" tab (view + edit modes).
 *
 * Figma: 148:15945 (view), 150:16470 (edit form).
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
$user_id      = (int) $current_user->ID;
$is_edit      = ! empty( $_GET['edit'] );

$phone        = (string) get_user_meta( $user_id, 'phone', true );
$telegram     = ltrim( (string) get_user_meta( $user_id, 'telegram', true ), '@' );
$whatsapp     = (string) get_user_meta( $user_id, 'whatsapp', true );
$about        = (string) get_user_meta( $user_id, 'about', true );
$account_type = (string) get_user_meta( $user_id, 'account_type', true );
if ( ! $account_type ) {
	$account_type = in_array( 'fleet', (array) $current_user->roles, true ) ? 'fleet' : 'car_owner';
}

$avatar_id  = tb_user_avatar_id( $user_id );
$avatar_url = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';
$initials   = mb_strtoupper( mb_substr( $current_user->first_name ?: $current_user->display_name, 0, 1 ) );

$profile_url = add_query_arg( 'tab', 'profile', get_permalink() );

// ACF label lookups.
$lbl_profile_error       = tb_opt( 'tb_cab_profile_error',        __( 'Перевірте імʼя та email — поля обовʼязкові.', 'taxi-rental' ) );
$lbl_email_taken         = tb_opt( 'tb_cab_profile_email_taken',  __( 'Цей email вже використовується іншим акаунтом.', 'taxi-rental' ) );
$lbl_photo_title         = tb_opt( 'tb_cab_profile_photo_title',  __( 'Фото профілю', 'taxi-rental' ) );
$lbl_upload_btn          = tb_opt( 'tb_cab_profile_upload_btn',   __( 'Завантажити нове', 'taxi-rental' ) );
$lbl_delete_photo        = tb_opt( 'tb_cab_profile_delete_photo', __( 'Видалити', 'taxi-rental' ) );
$lbl_info_title          = tb_opt( 'tb_cab_profile_info_title',   __( 'Основна інформація', 'taxi-rental' ) );
$lbl_edit_btn            = tb_opt( 'tb_cab_profile_edit_btn',     __( 'Редагувати', 'taxi-rental' ) );
$lbl_first_name          = tb_opt( 'tb_cab_profile_first_name',   __( 'Імʼя', 'taxi-rental' ) );
$lbl_last_name           = tb_opt( 'tb_cab_profile_last_name',    __( 'Прізвище', 'taxi-rental' ) );
$lbl_phone               = tb_opt( 'tb_cab_profile_phone',        __( 'Телефон', 'taxi-rental' ) );
$lbl_account_type        = tb_opt( 'tb_cab_profile_account_type', __( 'Тип облікового запису', 'taxi-rental' ) );
$lbl_user_type_label     = tb_opt( 'tb_cab_profile_account_type', __( 'Тип користувача', 'taxi-rental' ) );
$lbl_telegram            = tb_opt( 'tb_cab_profile_telegram',     __( 'Телеграм', 'taxi-rental' ) );
$lbl_whatsapp            = tb_opt( 'tb_cab_profile_whatsapp',     __( 'WhatsApp', 'taxi-rental' ) );
$lbl_about               = tb_opt( 'tb_cab_profile_about',        __( 'Короткий опис', 'taxi-rental' ) );
$lbl_save                = tb_opt( 'tb_cab_profile_save',         __( 'Зберегти', 'taxi-rental' ) );
$lbl_cancel              = tb_opt( 'tb_cab_profile_cancel',       __( 'Скасувати', 'taxi-rental' ) );
$lbl_type_owner          = tb_opt( 'tb_cab_profile_type_owner',   __( 'Фізична особа', 'taxi-rental' ) );
$lbl_type_fleet          = tb_opt( 'tb_cab_profile_type_fleet',   __( 'Автопарк / флотилія', 'taxi-rental' ) );
$lbl_type_driver         = tb_opt( 'tb_cab_profile_type_driver',  __( 'Vodič', 'taxi-rental' ) );
$lbl_delete_account_btn  = tb_opt( 'tb_cab_delete_account_btn',   __( 'Vymazať účet', 'taxi-rental' ) );
$lbl_delete_modal_title  = tb_opt( 'tb_cab_delete_modal_title',   __( 'Naozaj chcete vymazať účet', 'taxi-rental' ) );
$lbl_delete_modal_close  = tb_opt( 'tb_cab_delete_modal_close',   __( 'Zavrieť', 'taxi-rental' ) );
$lbl_delete_modal_desc   = tb_opt( 'tb_cab_delete_modal_desc',    __( 'Ste si istý? Majitelia áut na TaxiBaza.sk zarábajú až 600 € týždenne z jedného auta — po vymazaní účtu prijdete o všetky inzeráty, príjem aj nazbieranú históriu. Túto akciu nie je možné vrátiť späť.', 'taxi-rental' ) );
$lbl_delete_confirm      = tb_opt( 'tb_cab_delete_confirm',       __( 'Vymazať', 'taxi-rental' ) );
$lbl_delete_cancel       = tb_opt( 'tb_cab_delete_cancel',        __( 'Zrušiť', 'taxi-rental' ) );

$type_labels = [
	'car_owner' => $lbl_type_owner,
	'fleet'     => $lbl_type_fleet,
	'driver'    => $lbl_type_driver,
];

// Drivers should not switch their account type from the profile form.
$is_driver = in_array( 'driver', (array) $current_user->roles, true );
?>

<?php if ( ! empty( $_GET['profile_error'] ) ) : ?>
<div class="tb-error-banner" role="alert">
	<div class="tb-error-banner__icon" aria-hidden="true">!</div>
	<p class="tb-error-banner__text"><?php echo esc_html( $lbl_profile_error ); ?></p>
</div>
<?php elseif ( ! empty( $_GET['profile_email_taken'] ) ) : ?>
<div class="tb-error-banner" role="alert">
	<div class="tb-error-banner__icon" aria-hidden="true">!</div>
	<p class="tb-error-banner__text"><?php echo esc_html( $lbl_email_taken ); ?></p>
</div>
<?php endif; ?>

<div class="tb-profile">

	<!-- Avatar card -->
	<div class="tb-profile__card tb-profile__card--photo">
		<h2 class="tb-profile__card-title"><?php echo esc_html( $lbl_photo_title ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="tb-profile__photo-form">
			<input type="hidden" name="action" value="tb_update_profile">
			<?php wp_nonce_field( 'tb_update_profile' ); ?>
			<input type="hidden" name="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>">
			<input type="hidden" name="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>">
			<input type="hidden" name="email" value="<?php echo esc_attr( $current_user->user_email ); ?>">
			<input type="hidden" name="phone" value="<?php echo esc_attr( $phone ); ?>">
			<input type="hidden" name="telegram" value="<?php echo esc_attr( $telegram ); ?>">
			<input type="hidden" name="whatsapp" value="<?php echo esc_attr( $whatsapp ); ?>">
			<input type="hidden" name="account_type" value="<?php echo esc_attr( $account_type ); ?>">
			<input type="hidden" name="about" value="<?php echo esc_attr( $about ); ?>">

			<label class="tb-profile__userpic<?php echo $avatar_url ? '' : ' tb-profile__userpic--empty'; ?>" for="tb-avatar-input">
				<?php if ( $avatar_url ) : ?>
					<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $lbl_photo_title ); ?>">
				<?php endif; ?>
				<span class="tb-profile__userpic-cam" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 15.5A3.5 3.5 0 1012 8.5A3.5 3.5 0 0012 15.5ZM9 3L7.17 5H4A2 2 0 002 7V19A2 2 0 004 21H20A2 2 0 0022 19V7A2 2 0 0020 5H16.83L15 3H9Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
				</span>
			</label>
			<input type="file" id="tb-avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp" class="tb-profile__file" onchange="this.form.submit()">

			<button type="button" class="tb-btn tb-btn--dark tb-profile__upload-btn" onclick="document.getElementById('tb-avatar-input').click()">
				<?php echo esc_html( $lbl_upload_btn ); ?>
			</button>
		</form>

		<?php if ( $avatar_id ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tb-profile__delete-form">
			<input type="hidden" name="action" value="tb_delete_avatar">
			<?php wp_nonce_field( 'tb_delete_avatar' ); ?>
			<button type="submit" class="tb-profile__delete-link"><?php echo esc_html( $lbl_delete_photo ); ?></button>
		</form>
		<?php endif; ?>
	</div>

	<!-- Info / edit card -->
	<div class="tb-profile__card tb-profile__card--info">

		<?php if ( ! $is_edit ) : ?>

			<div class="tb-profile__card-head">
				<h2 class="tb-profile__card-title"><?php echo esc_html( $lbl_info_title ); ?></h2>
				<div class="tb-profile__head-actions">
					<a href="<?php echo esc_url( add_query_arg( 'edit', 1, $profile_url ) ); ?>" class="tb-btn tb-btn--dark tb-btn--sm">
						<?php echo esc_html( $lbl_edit_btn ); ?>
					</a>
					<button type="button" class="tb-profile__delete-account" data-modal-open="tb-delete-modal">
						<?php echo esc_html( $lbl_delete_account_btn ); ?>
					</button>
				</div>
			</div>

			<dl class="tb-profile__facts">
				<div class="tb-profile__fact">
					<dt><?php echo esc_html( $lbl_first_name ); ?></dt>
					<dd><?php echo esc_html( $current_user->first_name ?: '—' ); ?></dd>
				</div>
				<div class="tb-profile__fact">
					<dt><?php echo esc_html( $lbl_last_name ); ?></dt>
					<dd><?php echo esc_html( $current_user->last_name ?: '—' ); ?></dd>
				</div>
				<div class="tb-profile__fact">
					<dt><?php echo esc_html( $lbl_phone ); ?></dt>
					<dd><?php echo esc_html( $phone ?: '—' ); ?></dd>
				</div>
				<div class="tb-profile__fact">
					<dt>Email</dt>
					<dd><?php echo esc_html( $current_user->user_email ); ?></dd>
				</div>
				<div class="tb-profile__fact">
					<dt><?php echo esc_html( $lbl_account_type ); ?></dt>
					<dd><?php echo esc_html( $type_labels[ $account_type ] ?? '—' ); ?></dd>
				</div>
				<div class="tb-profile__fact">
					<dt><?php echo esc_html( $lbl_telegram ); ?></dt>
					<dd><?php echo $telegram ? esc_html( '@' . $telegram ) : '—'; ?></dd>
				</div>
				<?php if ( $about ) : ?>
				<div class="tb-profile__fact tb-profile__fact--wide">
					<dt><?php echo esc_html( $lbl_about ); ?></dt>
					<dd><?php echo esc_html( $about ); ?></dd>
				</div>
				<?php endif; ?>
			</dl>

			<a href="<?php echo esc_url( add_query_arg( 'edit', 1, $profile_url ) ); ?>" class="tb-btn tb-btn--dark tb-profile__edit-mobile">
				<?php echo esc_html( $lbl_edit_btn ); ?>
			</a>

		<?php else : ?>

			<?php // #120 — Figma (150:16470) has no heading here at all: the form goes straight from the field list to "Zberegti". Reusing $lbl_edit_btn as a section title was never in the design; the ≤900px reorder rule below just made the stray text more prominent. ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tb-profile__form" data-tb-validate>
				<input type="hidden" name="action" value="tb_update_profile">
				<?php wp_nonce_field( 'tb_update_profile' ); ?>

				<!-- Error banner (JS shows on validation fail) -->
				<div class="tb-error-banner" hidden role="alert">
					<div class="tb-error-banner__icon" aria-hidden="true">!</div>
					<p class="tb-error-banner__text"><?php esc_html_e( 'Будь ласка, виправте помилки перед збереженням.', 'taxi-rental' ); ?></p>
				</div>

				<div class="tb-profile__field">
					<label for="tb_pf_first"><?php echo esc_html( $lbl_first_name ); ?> <span class="tb-req">*</span></label>
					<input type="text" id="tb_pf_first" name="first_name" required value="<?php echo esc_attr( $current_user->first_name ); ?>"
						data-err-required="<?php esc_attr_e( 'Введіть ім\'я', 'taxi-rental' ); ?>">
					<span class="tb-field-error" role="alert"></span>
				</div>

				<div class="tb-profile__field">
					<label for="tb_pf_last"><?php echo esc_html( $lbl_last_name ); ?></label>
					<input type="text" id="tb_pf_last" name="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>">
				</div>

				<div class="tb-profile__field">
					<label for="tb_pf_email">Email <span class="tb-req">*</span></label>
					<input type="email" id="tb_pf_email" name="email" required value="<?php echo esc_attr( $current_user->user_email ); ?>"
						data-err-required="<?php esc_attr_e( 'Вкажіть email', 'taxi-rental' ); ?>"
						data-err-email="<?php esc_attr_e( 'Введіть коректну адресу email', 'taxi-rental' ); ?>">
					<span class="tb-field-error" role="alert"></span>
				</div>

				<div class="tb-profile__field">
					<label for="tb_pf_phone"><?php echo esc_html( $lbl_phone ); ?></label>
					<input type="tel" id="tb_pf_phone" name="phone" value="<?php echo esc_attr( $phone ); ?>">
				</div>

				<div class="tb-profile__field">
					<label for="tb_pf_type"><?php echo esc_html( $lbl_user_type_label ); ?></label>
					<?php if ( $is_driver ) : ?>
						<?php // Drivers can't change their account type — show it read-only. ?>
						<input type="text" id="tb_pf_type" value="<?php echo esc_attr( $type_labels['driver'] ); ?>" readonly disabled>
					<?php else : ?>
						<select id="tb_pf_type" name="account_type">
							<?php foreach ( [ 'car_owner' => $lbl_type_owner, 'fleet' => $lbl_type_fleet, 'driver' => $lbl_type_driver ] as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $account_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>

				<div class="tb-profile__field">
					<label for="tb_pf_tg"><?php echo esc_html( $lbl_telegram ); ?></label>
					<input type="text" id="tb_pf_tg" name="telegram" placeholder="@" value="<?php echo esc_attr( $telegram ); ?>">
				</div>

				<div class="tb-profile__field">
					<label for="tb_pf_wa"><?php echo esc_html( $lbl_whatsapp ); ?></label>
					<input type="tel" id="tb_pf_wa" name="whatsapp" placeholder="+421" value="<?php echo esc_attr( $whatsapp ); ?>">
				</div>

				<div class="tb-profile__field">
					<label for="tb_pf_about"><?php echo esc_html( $lbl_about ); ?></label>
					<textarea id="tb_pf_about" name="about" rows="6"><?php echo esc_textarea( $about ); ?></textarea>
				</div>

				<div class="tb-profile__form-actions">
					<button type="submit" class="tb-btn tb-btn--primary"><?php echo esc_html( $lbl_save ); ?></button>
					<button type="button" class="tb-profile__delete-account" data-modal-open="tb-delete-modal">
						<?php echo esc_html( $lbl_delete_account_btn ); ?>
					</button>
				</div>
			</form>

		<?php endif; ?>

	</div>
</div>

<!-- Delete Account confirmation (Figma 347:4776) — available in view + edit modes -->
<div class="tb-modal tb-delete-modal" id="tb-delete-modal" role="dialog" aria-modal="true" aria-labelledby="tb-delete-modal-title" hidden>
	<div class="tb-modal__backdrop" data-modal-close></div>
	<div class="tb-modal__panel tb-modal__panel--narrow">
		<h2 class="tb-modal__title" id="tb-delete-modal-title" style="text-align:center;letter-spacing:-0.8px;">
			<?php echo esc_html( $lbl_delete_modal_title ); ?>
		</h2>
		<p class="tb-modal__text" style="text-align:center;">
			<?php echo esc_html( $lbl_delete_modal_desc ); ?>
		</p>
		<div class="tb-modal__actions">
			<!-- Cancel (green, top) — matches Figma 347:4776 button order -->
			<button type="button" class="tb-btn tb-btn--primary" data-modal-close>
				<?php echo esc_html( $lbl_delete_cancel ); ?>
			</button>
			<!-- Confirm delete (outlined, bottom) -->
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="width:100%;">
				<input type="hidden" name="action" value="tb_delete_account">
				<?php wp_nonce_field( 'tb_delete_account' ); ?>
				<button type="submit" class="tb-btn tb-btn--outline" style="width:100%;justify-content:center;">
					<?php echo esc_html( $lbl_delete_confirm ); ?>
				</button>
			</form>
		</div>
	</div>
</div>
