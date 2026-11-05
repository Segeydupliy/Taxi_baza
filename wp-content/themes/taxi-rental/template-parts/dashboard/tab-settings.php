<?php
/**
 * Cabinet — "Налаштування" tab.
 *
 * Figma: 154:16572 — password change card + notifications card.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id   = get_current_user_id();
$is_driver = in_array( 'driver', (array) wp_get_current_user()->roles, true );

// Blacklist block (drivers only) — placeholder until messaging ships (phase 2).
$lbl_bl_title       = tb_opt( 'tb_cab_bl_title',       __( 'Čierna listina', 'taxi-rental' ) );
$lbl_bl_restore_all = tb_opt( 'tb_cab_bl_restore_all', __( 'Obnoviť všetkých', 'taxi-rental' ) );
$lbl_bl_empty       = tb_opt( 'tb_cab_bl_empty',       __( 'Zatiaľ nemáte žiadnych blokovaných používateľov. Zoznam sa sprístupní po spustení správ.', 'taxi-rental' ) );

// Messages and moderation are opt-out (ТЗ mandates moderation emails),
// marketing is opt-in.
$notify_defaults = [
	'tb_notify_messages'   => '1',
	'tb_notify_moderation' => '1',
	'tb_notify_marketing'  => '0',
];

$notify = [];
foreach ( $notify_defaults as $key => $default ) {
	$saved          = get_user_meta( $user_id, $key, true );
	$notify[ $key ] = ( '' === $saved ) ? $default : $saved;
}

// ACF label lookups.
$lbl_notice_pass_changed  = tb_opt( 'tb_cab_notice_pass_changed',  __( 'Пароль оновлено.', 'taxi-rental' ) );
$lbl_notice_pass_wrong    = tb_opt( 'tb_cab_notice_pass_wrong',    __( 'Поточний пароль невірний.', 'taxi-rental' ) );
$lbl_notice_pass_short    = tb_opt( 'tb_cab_notice_pass_short',    __( 'Новий пароль має містити щонайменше 8 символів.', 'taxi-rental' ) );
$lbl_notice_pass_mismatch = tb_opt( 'tb_cab_notice_pass_mismatch', __( 'Паролі не співпадають.', 'taxi-rental' ) );
$lbl_notice_notif_saved   = tb_opt( 'tb_cab_notice_notif_saved',   __( 'Налаштування сповіщень збережено.', 'taxi-rental' ) );
$lbl_pass_title           = tb_opt( 'tb_cab_settings_pass_title',  __( 'Змінити пароль', 'taxi-rental' ) );
$lbl_pass_current_ph      = tb_opt( 'tb_cab_settings_pass_current_ph', __( 'Поточний пароль', 'taxi-rental' ) );
$lbl_pass_new_ph          = tb_opt( 'tb_cab_settings_pass_new_ph',     __( 'Новий пароль', 'taxi-rental' ) );
$lbl_pass_confirm_ph      = tb_opt( 'tb_cab_settings_pass_confirm_ph', __( 'Повторіть новий пароль', 'taxi-rental' ) );
$lbl_pass_update          = tb_opt( 'tb_cab_settings_pass_update',  __( 'Оновити', 'taxi-rental' ) );
$lbl_notif_title          = tb_opt( 'tb_cab_settings_notif_title',  __( 'Сповіщення', 'taxi-rental' ) );
$lbl_notif_desc           = tb_opt( 'tb_cab_settings_notif_desc',   __( 'Налаштуйте сповіщення під себе. Обирайте лише те, що дійсно важливо для вас.', 'taxi-rental' ) );
$lbl_notif_msg_title      = tb_opt( 'tb_cab_notif_messages_title',   __( 'Email-сповіщення про повідомлення', 'taxi-rental' ) );
$lbl_notif_msg_desc       = tb_opt( 'tb_cab_notif_messages_desc',    __( 'Отримуйте листи на пошту щоразу, коли вам надходить нове повідомлення.', 'taxi-rental' ) );
$lbl_notif_mod_title      = tb_opt( 'tb_cab_notif_moderation_title', __( 'Сповіщення про модерацію', 'taxi-rental' ) );
$lbl_notif_mod_desc       = tb_opt( 'tb_cab_notif_moderation_desc',  __( 'Дізнавайтесь про статус перевірки вашого контенту командою модераторів.', 'taxi-rental' ) );
$lbl_notif_mkt_title      = tb_opt( 'tb_cab_notif_marketing_title',  __( 'Маркетингова розсилка', 'taxi-rental' ) );
$lbl_notif_mkt_desc       = tb_opt( 'tb_cab_notif_marketing_desc',   __( 'Отримуйте новини, акції та корисні поради від нашої команди.', 'taxi-rental' ) );

// Password show/hide + strength meter — same labels/behavior as the reset-password screen.
$lbl_pw_toggle_show    = tb_opt( 'tb_auth_pw_toggle_show',     __( 'Zobraziť heslo', 'taxi-rental' ) );
$lbl_pw_toggle_hide    = tb_opt( 'tb_auth_pw_toggle_hide',     __( 'Skryť heslo', 'taxi-rental' ) );
$lbl_pw_strength_label = tb_opt( 'tb_auth_pw_strength_label',  __( 'Sila hesla', 'taxi-rental' ) );
$lbl_pw_strength_weak  = tb_opt( 'tb_auth_pw_strength_weak',   __( 'Slabé', 'taxi-rental' ) );
$lbl_pw_strength_medium = tb_opt( 'tb_auth_pw_strength_medium', __( 'Stredné', 'taxi-rental' ) );
$lbl_pw_strength_strong = tb_opt( 'tb_auth_pw_strength_strong', __( 'Silné', 'taxi-rental' ) );

$pass_notices = [
	'pass_changed'  => [ 'success', $lbl_notice_pass_changed ],
	'pass_wrong'    => [ 'error',   $lbl_notice_pass_wrong ],
	'pass_short'    => [ 'error',   $lbl_notice_pass_short ],
	'pass_mismatch' => [ 'error',   $lbl_notice_pass_mismatch ],
	'notif_saved'   => [ 'success', $lbl_notice_notif_saved ],
];
?>

<?php foreach ( $pass_notices as $flag => $notice ) : ?>
	<?php if ( ! empty( $_GET[ $flag ] ) ) : ?>
	<div class="tb-cabinet-notice tb-cabinet-notice--<?php echo esc_attr( $notice[0] ); ?>" role="alert">
		<?php echo esc_html( $notice[1] ); ?>
	</div>
	<?php endif; ?>
<?php endforeach; ?>

<div class="tb-settings<?php echo $is_driver ? ' tb-settings--driver' : ''; ?>">

	<?php if ( $is_driver ) : ?><div class="tb-settings__col"><?php endif; ?>

	<!-- Password card -->
	<div class="tb-profile__card tb-settings__card">
		<h2 class="tb-profile__card-title"><?php echo esc_html( $lbl_pass_title ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tb-settings__form" data-tb-validate>
			<input type="hidden" name="action" value="tb_change_password">
			<?php wp_nonce_field( 'tb_change_password' ); ?>

			<div class="tb-error-banner" hidden role="alert">
				<div class="tb-error-banner__icon" aria-hidden="true">!</div>
				<p class="tb-error-banner__text"><?php esc_html_e( 'Будь ласка, виправте помилки перед збереженням.', 'taxi-rental' ); ?></p>
			</div>

			<div class="tb-profile__field">
				<label for="tb_st_current" class="screen-reader-text"><?php echo esc_html( $lbl_pass_current_ph ); ?></label>
				<div class="tb-pw-field">
					<input type="password" id="tb_st_current" name="current_password" required autocomplete="current-password"
						placeholder="<?php echo esc_attr( $lbl_pass_current_ph ); ?>"
						data-err-required="<?php esc_attr_e( 'Введіть поточний пароль', 'taxi-rental' ); ?>">
					<button type="button" class="tb-pw-toggle" data-pw-toggle
						aria-label="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
						data-label-show="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
						data-label-hide="<?php echo esc_attr( $lbl_pw_toggle_hide ); ?>">
						<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.4792 8.47917C12.1597 7.79861 12.5 6.97222 12.5 6C12.5 5.02778 12.1597 4.20139 11.4792 3.52083C10.7986 2.84028 9.97222 2.5 9 2.5C8.02778 2.5 7.20139 2.84028 6.52083 3.52083C5.84028 4.20139 5.5 5.02778 5.5 6C5.5 6.97222 5.84028 7.79861 6.52083 8.47917C7.20139 9.15972 8.02778 9.5 9 9.5C9.97222 9.5 10.7986 9.15972 11.4792 8.47917ZM7.58333 7.41667C7.19444 7.02778 7 6.55556 7 6C7 5.44444 7.19444 4.97222 7.58333 4.58333C7.97222 4.19444 8.44444 4 9 4C9.55556 4 10.0278 4.19444 10.4167 4.58333C10.8056 4.97222 11 5.44444 11 6C11 6.55556 10.8056 7.02778 10.4167 7.41667C10.0278 7.80556 9.55556 8 9 8C8.44444 8 7.97222 7.80556 7.58333 7.41667ZM3.58333 10.3646C1.95833 9.27431 0.763889 7.81944 0 6C0.763889 4.18056 1.95833 2.72569 3.58333 1.63542C5.20833 0.545139 7.01389 0 9 0C10.9861 0 12.7917 0.545139 14.4167 1.63542C16.0417 2.72569 17.2361 4.18056 18 6C17.2361 7.81944 16.0417 9.27431 14.4167 10.3646C12.7917 11.4549 10.9861 12 9 12C7.01389 12 5.20833 11.4549 3.58333 10.3646ZM13.3125 9.29167C14.6319 8.48611 15.6458 7.38889 16.3542 6C15.6458 4.61111 14.6319 3.51389 13.3125 2.70833C11.9931 1.90278 10.5556 1.5 9 1.5C7.44444 1.5 6.00694 1.90278 4.6875 2.70833C3.36806 3.51389 2.35417 4.61111 1.64583 6C2.35417 7.38889 3.36806 8.48611 4.6875 9.29167C6.00694 10.0972 7.44444 10.5 9 10.5C10.5556 10.5 11.9931 10.0972 13.3125 9.29167Z" fill="currentColor"/></svg>
					</button>
				</div>
				<span class="tb-field-error" role="alert"></span>
			</div>
			<div class="tb-profile__field">
				<label for="tb_st_new" class="screen-reader-text"><?php echo esc_html( $lbl_pass_new_ph ); ?></label>
				<div class="tb-pw-field">
					<input type="password" id="tb_st_new" name="new_password" required minlength="8" autocomplete="new-password"
						placeholder="<?php echo esc_attr( $lbl_pass_new_ph ); ?>"
						data-err-required="<?php esc_attr_e( 'Введіть новий пароль', 'taxi-rental' ); ?>"
						data-err-minlength="<?php esc_attr_e( 'Пароль має містити щонайменше 8 символів', 'taxi-rental' ); ?>"
						data-pw-strength-input>
					<button type="button" class="tb-pw-toggle" data-pw-toggle
						aria-label="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
						data-label-show="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
						data-label-hide="<?php echo esc_attr( $lbl_pw_toggle_hide ); ?>">
						<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.4792 8.47917C12.1597 7.79861 12.5 6.97222 12.5 6C12.5 5.02778 12.1597 4.20139 11.4792 3.52083C10.7986 2.84028 9.97222 2.5 9 2.5C8.02778 2.5 7.20139 2.84028 6.52083 3.52083C5.84028 4.20139 5.5 5.02778 5.5 6C5.5 6.97222 5.84028 7.79861 6.52083 8.47917C7.20139 9.15972 8.02778 9.5 9 9.5C9.97222 9.5 10.7986 9.15972 11.4792 8.47917ZM7.58333 7.41667C7.19444 7.02778 7 6.55556 7 6C7 5.44444 7.19444 4.97222 7.58333 4.58333C7.97222 4.19444 8.44444 4 9 4C9.55556 4 10.0278 4.19444 10.4167 4.58333C10.8056 4.97222 11 5.44444 11 6C11 6.55556 10.8056 7.02778 10.4167 7.41667C10.0278 7.80556 9.55556 8 9 8C8.44444 8 7.97222 7.80556 7.58333 7.41667ZM3.58333 10.3646C1.95833 9.27431 0.763889 7.81944 0 6C0.763889 4.18056 1.95833 2.72569 3.58333 1.63542C5.20833 0.545139 7.01389 0 9 0C10.9861 0 12.7917 0.545139 14.4167 1.63542C16.0417 2.72569 17.2361 4.18056 18 6C17.2361 7.81944 16.0417 9.27431 14.4167 10.3646C12.7917 11.4549 10.9861 12 9 12C7.01389 12 5.20833 11.4549 3.58333 10.3646ZM13.3125 9.29167C14.6319 8.48611 15.6458 7.38889 16.3542 6C15.6458 4.61111 14.6319 3.51389 13.3125 2.70833C11.9931 1.90278 10.5556 1.5 9 1.5C7.44444 1.5 6.00694 1.90278 4.6875 2.70833C3.36806 3.51389 2.35417 4.61111 1.64583 6C2.35417 7.38889 3.36806 8.48611 4.6875 9.29167C6.00694 10.0972 7.44444 10.5 9 10.5C10.5556 10.5 11.9931 10.0972 13.3125 9.29167Z" fill="currentColor"/></svg>
					</button>
				</div>
				<span class="tb-field-error" role="alert"></span>

				<div class="tb-pw-strength" data-pw-strength
					data-label="<?php echo esc_attr( $lbl_pw_strength_label ); ?>"
					data-level-weak="<?php echo esc_attr( $lbl_pw_strength_weak ); ?>"
					data-level-medium="<?php echo esc_attr( $lbl_pw_strength_medium ); ?>"
					data-level-strong="<?php echo esc_attr( $lbl_pw_strength_strong ); ?>">
					<div class="tb-pw-strength__bars" aria-hidden="true">
						<span class="tb-pw-strength__bar"></span>
						<span class="tb-pw-strength__bar"></span>
						<span class="tb-pw-strength__bar"></span>
					</div>
					<p class="tb-pw-strength__label"></p>
				</div>
			</div>
			<div class="tb-profile__field">
				<label for="tb_st_confirm" class="screen-reader-text"><?php echo esc_html( $lbl_pass_confirm_ph ); ?></label>
				<div class="tb-pw-field">
					<input type="password" id="tb_st_confirm" name="confirm_password" required minlength="8" autocomplete="new-password"
						placeholder="<?php echo esc_attr( $lbl_pass_confirm_ph ); ?>"
						data-match-id="tb_st_new"
						data-err-required="<?php esc_attr_e( 'Підтвердіть новий пароль', 'taxi-rental' ); ?>"
						data-err-match="<?php esc_attr_e( 'Паролі не збігаються', 'taxi-rental' ); ?>">
					<button type="button" class="tb-pw-toggle" data-pw-toggle
						aria-label="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
						data-label-show="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
						data-label-hide="<?php echo esc_attr( $lbl_pw_toggle_hide ); ?>">
						<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.4792 8.47917C12.1597 7.79861 12.5 6.97222 12.5 6C12.5 5.02778 12.1597 4.20139 11.4792 3.52083C10.7986 2.84028 9.97222 2.5 9 2.5C8.02778 2.5 7.20139 2.84028 6.52083 3.52083C5.84028 4.20139 5.5 5.02778 5.5 6C5.5 6.97222 5.84028 7.79861 6.52083 8.47917C7.20139 9.15972 8.02778 9.5 9 9.5C9.97222 9.5 10.7986 9.15972 11.4792 8.47917ZM7.58333 7.41667C7.19444 7.02778 7 6.55556 7 6C7 5.44444 7.19444 4.97222 7.58333 4.58333C7.97222 4.19444 8.44444 4 9 4C9.55556 4 10.0278 4.19444 10.4167 4.58333C10.8056 4.97222 11 5.44444 11 6C11 6.55556 10.8056 7.02778 10.4167 7.41667C10.0278 7.80556 9.55556 8 9 8C8.44444 8 7.97222 7.80556 7.58333 7.41667ZM3.58333 10.3646C1.95833 9.27431 0.763889 7.81944 0 6C0.763889 4.18056 1.95833 2.72569 3.58333 1.63542C5.20833 0.545139 7.01389 0 9 0C10.9861 0 12.7917 0.545139 14.4167 1.63542C16.0417 2.72569 17.2361 4.18056 18 6C17.2361 7.81944 16.0417 9.27431 14.4167 10.3646C12.7917 11.4549 10.9861 12 9 12C7.01389 12 5.20833 11.4549 3.58333 10.3646ZM13.3125 9.29167C14.6319 8.48611 15.6458 7.38889 16.3542 6C15.6458 4.61111 14.6319 3.51389 13.3125 2.70833C11.9931 1.90278 10.5556 1.5 9 1.5C7.44444 1.5 6.00694 1.90278 4.6875 2.70833C3.36806 3.51389 2.35417 4.61111 1.64583 6C2.35417 7.38889 3.36806 8.48611 4.6875 9.29167C6.00694 10.0972 7.44444 10.5 9 10.5C10.5556 10.5 11.9931 10.0972 13.3125 9.29167Z" fill="currentColor"/></svg>
					</button>
				</div>
				<span class="tb-field-error" role="alert"></span>
			</div>

			<button type="submit" class="tb-btn tb-btn--dark"><?php echo esc_html( $lbl_pass_update ); ?></button>
		</form>
	</div>

	<!-- Notifications card -->
	<div class="tb-profile__card tb-settings__card">
		<h2 class="tb-profile__card-title"><?php echo esc_html( $lbl_notif_title ); ?></h2>
		<p class="tb-settings__desc">
			<?php echo esc_html( $lbl_notif_desc ); ?>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="tb-settings__notify-form" id="tb-notify-form">
			<input type="hidden" name="action" value="tb_save_notifications">
			<?php wp_nonce_field( 'tb_save_notifications' ); ?>

			<?php
			$toggles = [
				'tb_notify_messages'   => [
					'title' => $lbl_notif_msg_title,
					'desc'  => $lbl_notif_msg_desc,
				],
			];
			// Moderation notifications only apply to listing owners (car_owner/fleet)
			// — drivers never post adverts, so there's nothing to moderate.
			if ( ! $is_driver ) {
				$toggles['tb_notify_moderation'] = [
					'title' => $lbl_notif_mod_title,
					'desc'  => $lbl_notif_mod_desc,
				];
			}
			$toggles['tb_notify_marketing'] = [
				'title' => $lbl_notif_mkt_title,
				'desc'  => $lbl_notif_mkt_desc,
			];
			?>

			<?php foreach ( $toggles as $key => $row ) : ?>
			<div class="tb-settings__row">
				<div class="tb-settings__row-text">
					<p class="tb-settings__row-title"><?php echo esc_html( $row['title'] ); ?></p>
					<p class="tb-settings__row-desc"><?php echo esc_html( $row['desc'] ); ?></p>
				</div>
				<label class="tb-toggle">
					<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( '1', $notify[ $key ] ); ?> onchange="document.getElementById('tb-notify-form').submit()">
					<span class="tb-toggle__track" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php echo esc_html( $row['title'] ); ?></span>
				</label>
			</div>
			<?php endforeach; ?>
		</form>
	</div>

	<?php if ( $is_driver ) : ?>
	</div><!-- .tb-settings__col (left) -->

	<div class="tb-settings__col">
		<!-- Blacklist (placeholder stub — messaging is phase 2) -->
		<div class="tb-profile__card tb-settings__card tb-settings__blacklist">
			<div class="tb-settings__blacklist-head">
				<h2 class="tb-profile__card-title"><?php echo esc_html( $lbl_bl_title ); ?></h2>
				<button type="button" class="tb-btn tb-btn--dark tb-btn--sm" disabled aria-disabled="true">
					<?php echo esc_html( $lbl_bl_restore_all ); ?>
				</button>
			</div>
			<p class="tb-settings__desc"><?php echo esc_html( $lbl_bl_empty ); ?></p>
		</div>
	</div><!-- .tb-settings__col (right) -->
	<?php endif; ?>

</div>
