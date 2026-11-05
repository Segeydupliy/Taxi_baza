<?php
/**
 * Template Name: Contact page
 *
 * Contact page — /kontakt/.
 *
 * Figma: desktop 107:3705, mobile 246:5813.
 * Form posts via AJAX to TaxiRental\Core\Contact (action: taxi_rental_contact).
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main class="tb-contact-page">
	<div class="tb-container">

		<?php
		$tb_contact_email   = (string) tb_opt( 'tb_contact_email', __( 'support@taxibaza.sk', 'taxi-rental' ) );
		$tb_cp_tg_text_html = tb_field(
			'tb_cp_tg_text',
			sprintf(
				/* translators: %s: link to the Telegram community */
				'<p>' . esc_html__( 'Отримайте швидку підтримку та відповіді на всі запитання у нашій %s.', 'taxi-rental' ) . '</p>',
				'<a href="' . esc_url( tb_telegram_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Telegram-спільноті', 'taxi-rental' ) . '</a>'
			)
		);

		// Form label lookups (page-bound fields — translatable via WPML page translation).
		$lbl_required_note   = tb_field( 'tb_cp_form_required_note',      __( 'Обов\'язкові поля до заповнення', 'taxi-rental' ) );
		$lbl_first_name      = tb_field( 'tb_cp_form_first_name_label',   __( 'Ім\'я', 'taxi-rental' ) );
		$lbl_first_err_ph    = tb_field( 'tb_cp_form_first_name_err_ph',  __( 'Це поле обов\'язкове', 'taxi-rental' ) );
		$lbl_first_err_msg   = tb_field( 'tb_cp_form_first_name_err_msg', __( 'Будь ласка, введіть ваше ім\'я', 'taxi-rental' ) );
		$lbl_last_name       = tb_field( 'tb_cp_form_last_name_label',    __( 'Прізвище', 'taxi-rental' ) );
		$lbl_last_err_ph     = tb_field( 'tb_cp_form_first_name_err_ph',  __( 'Це поле обов\'язкове', 'taxi-rental' ) );
		$lbl_last_err_msg    = tb_field( 'tb_cp_form_last_name_err_msg',  __( 'Будь ласка, введіть ваше прізвище', 'taxi-rental' ) );
		$lbl_email_err_ph    = tb_field( 'tb_cp_form_email_err_ph',       __( 'Невірний формат email', 'taxi-rental' ) );
		$lbl_email_err_msg   = tb_field( 'tb_cp_form_email_err_msg',      __( 'Введіть коректну адресу: example@domain.com', 'taxi-rental' ) );
		$lbl_phone_label     = tb_field( 'tb_cp_form_phone_label',        __( 'Телефон', 'taxi-rental' ) );
		$lbl_phone_err_ph    = tb_field( 'tb_cp_form_phone_err_ph',       __( 'Вкажіть номер', 'taxi-rental' ) );
		$lbl_phone_err_msg   = tb_field( 'tb_cp_form_phone_err_msg',      __( 'Формат: +421 9XX XXX XXX', 'taxi-rental' ) );
		$lbl_type_label      = tb_field( 'tb_cp_form_user_type_label',    __( 'Тип користувача', 'taxi-rental' ) );
		$lbl_type_select_ph  = tb_field( 'tb_cp_form_user_type_select_ph', __( 'Виберіть будь ласка', 'taxi-rental' ) );
		$lbl_type_driver     = tb_field( 'tb_cp_form_type_driver',        __( 'Водій', 'taxi-rental' ) );
		$lbl_type_owner      = tb_field( 'tb_cp_form_type_owner',         __( 'Власник авто', 'taxi-rental' ) );
		$lbl_type_fleet      = tb_field( 'tb_cp_form_type_fleet',         __( 'Автопарк / флотилія', 'taxi-rental' ) );
		$lbl_type_other      = tb_field( 'tb_cp_form_type_other',         __( 'Інше', 'taxi-rental' ) );
		$lbl_type_err        = tb_field( 'tb_cp_form_user_type_err',      __( 'Будь ласка, оберіть тип користувача', 'taxi-rental' ) );
		$lbl_message_label   = tb_field( 'tb_cp_form_message_label',      __( 'Повідомлення', 'taxi-rental' ) );
		$lbl_message_err_ph  = tb_field( 'tb_cp_form_message_err_ph',     __( 'Введіть повідомлення', 'taxi-rental' ) );
		$lbl_message_err_msg = tb_field( 'tb_cp_form_message_err_msg',    __( 'Повідомлення не може бути порожнім', 'taxi-rental' ) );
		$lbl_consent_tpl     = tb_field(
			'tb_cp_form_consent',
			__( 'Надсилаючи повідомлення, ви погоджуєтесь з %1$s та %2$s. Ми використовуємо cookie для покращення роботи сайту.', 'taxi-rental' )
		);
		$lbl_privacy_link    = tb_field( 'tb_cp_form_privacy_link', __( 'Політикою конфіденційності', 'taxi-rental' ) );
		$lbl_terms_link      = tb_field( 'tb_cp_form_terms_link',   __( 'Умовами використання', 'taxi-rental' ) );
		$lbl_error_banner    = tb_field( 'tb_cp_form_error_banner',  __( 'Будь ласка, виправте помилки перед відправкою', 'taxi-rental' ) );
		$lbl_submit          = tb_field( 'tb_cp_form_submit',        __( 'Відправити', 'taxi-rental' ) );
		?>

		<h1 class="tb-contact-page__title"><?php echo esc_html( tb_field( 'tb_cp_title', __( 'Контакти', 'taxi-rental' ) ) ); ?></h1>

		<div class="tb-contact-page__grid">

			<div class="tb-contact-page__info">
				<h2 class="tb-contact-page__subtitle"><?php echo esc_html( tb_field( 'tb_cp_subtitle', __( 'Зв\'яжіться з нами', 'taxi-rental' ) ) ); ?></h2>

				<div class="tb-contact-page__channel">
					<p class="tb-contact-page__label">Email</p>
					<a href="mailto:<?php echo esc_attr( $tb_contact_email ); ?>" class="tb-contact-page__email"><?php echo esc_html( $tb_contact_email ); ?></a>
				</div>

				<div class="tb-contact-page__channel">
					<p class="tb-contact-page__label"><?php echo esc_html( tb_field( 'tb_cp_tg_label', __( 'Телеграм спільнота', 'taxi-rental' ) ) ); ?></p>
					<div class="tb-contact-page__text">
						<?php echo wp_kses_post( $tb_cp_tg_text_html ); ?>
					</div>
				</div>
			</div>

			<div class="tb-contact-card">
				<form class="tb-contact-form" id="tb-contact-form" novalidate>
					<p class="tb-contact-form__required">
						<span class="tb-req">*</span> <?php echo esc_html( $lbl_required_note ); ?>
					</p>

					<div class="tb-contact-form__row">
						<div class="tb-contact-form__field">
							<label for="tb_cf_first_name"><?php echo esc_html( $lbl_first_name ); ?> <span class="tb-req">*</span></label>
							<input type="text" id="tb_cf_first_name" name="first_name" required autocomplete="given-name"
								data-error-placeholder="<?php echo esc_attr( $lbl_first_err_ph ); ?>">
							<span class="tb-field-error" hidden><?php echo esc_html( $lbl_first_err_msg ); ?></span>
						</div>
						<div class="tb-contact-form__field">
							<label for="tb_cf_last_name"><?php echo esc_html( $lbl_last_name ); ?> <span class="tb-req">*</span></label>
							<input type="text" id="tb_cf_last_name" name="last_name" required autocomplete="family-name"
								data-error-placeholder="<?php echo esc_attr( $lbl_last_err_ph ); ?>">
							<span class="tb-field-error" hidden><?php echo esc_html( $lbl_last_err_msg ); ?></span>
						</div>
					</div>

					<div class="tb-contact-form__row">
						<div class="tb-contact-form__field">
							<label for="tb_cf_email">Email <span class="tb-req">*</span></label>
							<input type="email" id="tb_cf_email" name="email" required autocomplete="email"
								data-error-placeholder="<?php echo esc_attr( $lbl_email_err_ph ); ?>">
							<span class="tb-field-error" hidden><?php echo esc_html( $lbl_email_err_msg ); ?></span>
						</div>
						<div class="tb-contact-form__field">
							<label for="tb_cf_phone"><?php echo esc_html( $lbl_phone_label ); ?> <span class="tb-req">*</span></label>
							<input type="tel" id="tb_cf_phone" name="phone" required autocomplete="tel"
								data-error-placeholder="<?php echo esc_attr( $lbl_phone_err_ph ); ?>">
							<span class="tb-field-error" hidden><?php echo esc_html( $lbl_phone_err_msg ); ?></span>
						</div>
					</div>

					<div class="tb-contact-form__field">
						<label for="tb_cf_user_type"><?php echo esc_html( $lbl_type_label ); ?> <span class="tb-req">*</span></label>
						<select id="tb_cf_user_type" name="user_type" required>
							<option value=""><?php echo esc_html( $lbl_type_select_ph ); ?></option>
							<option value="driver"><?php echo esc_html( $lbl_type_driver ); ?></option>
							<option value="owner"><?php echo esc_html( $lbl_type_owner ); ?></option>
							<option value="fleet"><?php echo esc_html( $lbl_type_fleet ); ?></option>
							<option value="other"><?php echo esc_html( $lbl_type_other ); ?></option>
						</select>
						<span class="tb-field-error" hidden><?php echo esc_html( $lbl_type_err ); ?></span>
					</div>

					<div class="tb-contact-form__field">
						<label for="tb_cf_message"><?php echo esc_html( $lbl_message_label ); ?> <span class="tb-req">*</span></label>
						<textarea id="tb_cf_message" name="message" rows="6" required
							data-error-placeholder="<?php echo esc_attr( $lbl_message_err_ph ); ?>"></textarea>
						<span class="tb-field-error" hidden><?php echo esc_html( $lbl_message_err_msg ); ?></span>
					</div>

					<p class="tb-contact-form__consent">
						<?php
						printf(
							wp_kses(
								$lbl_consent_tpl,
								[ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
							),
							'<a href="' . esc_url( home_url( '/konfidenzialnist/' ) ) . '">' . esc_html( $lbl_privacy_link ) . '</a>',
							'<a href="' . esc_url( home_url( '/umovy/' ) ) . '">' . esc_html( $lbl_terms_link ) . '</a>'
						);
						?>
					</p>

					<div class="tb-contact-form__error-banner" id="tb-contact-error-banner" role="alert" hidden>
						<span class="tb-contact-form__error-icon" aria-hidden="true">!</span>
						<p><?php echo esc_html( $lbl_error_banner ); ?></p>
					</div>

					<div class="tb-contact-form__notice" id="tb-contact-notice" role="status" aria-live="polite" hidden></div>

					<button type="submit" class="tb-btn tb-btn--primary tb-contact-form__submit">
						<?php echo esc_html( $lbl_submit ); ?>
					</button>
				</form>

				<?php
				// #99 — success state, centered layout with no card background
				// (matches the reference mockup: circular icon, title, description
				// and an auto-width button, all centered — not the boxed/left-
				// aligned row this used to render).
				?>
				<div class="tb-contact-success" id="tb-contact-success" hidden>
					<span class="tb-contact-success__icon" aria-hidden="true">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17L4 12" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</span>
					<p class="tb-contact-success__title">
						<?php echo esc_html( tb_field( 'tb_cp_success_title', __( 'Správa bola úspešne odoslaná!', 'taxi-rental' ) ) ); ?>
					</p>
					<p class="tb-contact-success__desc">
						<?php echo esc_html( tb_field( 'tb_cp_success_desc', __( 'Ozveme sa vám do 1–2 pracovných dní.', 'taxi-rental' ) ) ); ?>
					</p>

					<button type="button" class="tb-btn tb-btn--secondary tb-contact-success__again" data-tb-contact-again>
						<?php echo esc_html( tb_field( 'tb_cp_success_again', __( 'Odoslať ďalšiu', 'taxi-rental' ) ) ); ?>
					</button>
				</div>
			</div>

		</div>
	</div>

	<?php get_template_part( 'template-parts/faq', null, [ 'show_more' => false ] ); ?>

	<?php get_template_part( 'template-parts/cta-telegram' ); ?>

</main>

<?php get_footer(); ?>
