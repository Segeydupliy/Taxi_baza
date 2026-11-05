<?php
/**
 * Template Name: Login / Register
 * Template Post Type: page
 *
 * States: login (default) | register | recover | reset_password
 */

if ( is_user_logged_in() ) {
	// Nothing on this page concerns a logged-in user any more — the
	// "choose account type" exception went away with the screen itself.
	wp_safe_redirect( tb_url( '/cabinet/' ) );
	exit;
}

$state      = sanitize_key( $_GET['action'] ?? 'login' );
// Flash cookie takes priority — survives server-side GET-param stripping or caching.
$error = '';
if ( ! empty( $_COOKIE['tb_flash_error'] ) ) {
	$error = sanitize_key( wp_unslash( $_COOKIE['tb_flash_error'] ) );
	setcookie( 'tb_flash_error', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
}
if ( '' === $error && ! empty( $_GET['error'] ) ) {
	$error = sanitize_key( wp_unslash( $_GET['error'] ) );
}
$sent        = ! empty( $_GET['sent'] );
$reset_done  = ! empty( $_GET['done'] );
$reg_ok      = ! empty( $_GET['registered'] );
$confirmed   = ! empty( $_GET['confirmed'] );
$is_blocked  = ( '1' === sanitize_key( $_GET['blocked'] ?? '' ) );

// Email address to echo on the "check your inbox" / "email sent" screens.
$confirm_email = sanitize_email( wp_unslash( $_GET['email'] ?? '' ) );
$recover_email = sanitize_email( wp_unslash( $_GET['email'] ?? '' ) );

// For reset_password state, grab key + login from query string.
$reset_key   = sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) );
$reset_login = sanitize_user( wp_unslash( $_GET['login'] ?? '' ) );

// Validate allowed states.
if ( ! in_array( $state, [ 'login', 'register', 'recover', 'reset_password', 'confirm_email', 'account_deleted' ], true ) ) {
	$state = 'login';
}

// If reset_password state but no key and no "done" success flag — fall back to recover.
if ( 'reset_password' === $state && empty( $reset_key ) && ! $reset_done ) {
	$state = 'recover';
}

// All ACF label lookups upfront.
$lbl_login_title         = tb_opt( 'tb_auth_login_title',         __( 'Увійти в обліковий запис', 'taxi-rental' ) );
$lbl_email_label         = tb_opt( 'tb_auth_login_email_label',   __( 'Email', 'taxi-rental' ) );
$lbl_email_ph            = tb_opt( 'tb_auth_login_email_ph',      __( 'your@email.com', 'taxi-rental' ) );
$lbl_pass_label          = tb_opt( 'tb_auth_login_pass_label',    __( 'Пароль', 'taxi-rental' ) );
$lbl_pass_ph             = tb_opt( 'tb_auth_login_pass_ph',       __( '••••••••', 'taxi-rental' ) );
$lbl_forgot              = tb_opt( 'tb_auth_login_forgot',        __( 'Забули пароль?', 'taxi-rental' ) );
$lbl_login_submit        = tb_opt( 'tb_auth_login_submit',        __( 'Увійти', 'taxi-rental' ) );
$lbl_no_account          = tb_opt( 'tb_auth_login_no_account',    __( 'Ще немає акаунту?', 'taxi-rental' ) );
$lbl_register_link       = tb_opt( 'tb_auth_login_register_link', __( 'Зареєструватись', 'taxi-rental' ) );
$lbl_reg_title           = tb_opt( 'tb_auth_reg_title',           __( 'Реєстрація облікового запису', 'taxi-rental' ) );
$lbl_reg_subtitle        = tb_opt( 'tb_auth_reg_subtitle',        __( 'Zaregistrujte sa a pridajte svoje auto na prenájom.', 'taxi-rental' ) );
$lbl_req_note            = tb_opt( 'tb_auth_req_fields_note',     __( 'Обов\'язкові поля до заповнення', 'taxi-rental' ) );
$lbl_name_label          = tb_opt( 'tb_auth_reg_name_label',      __( 'Ім\'я / Назва флотилії', 'taxi-rental' ) );
$lbl_name_ph             = tb_opt( 'tb_auth_reg_name_ph',         __( 'Іван Петренко', 'taxi-rental' ) );
$lbl_reg_email_label     = tb_opt( 'tb_auth_reg_email_label',     __( 'Email / Телефон', 'taxi-rental' ) );
$lbl_reg_pass_ph         = tb_opt( 'tb_auth_reg_pass_ph',         __( 'Мінімум 8 символів', 'taxi-rental' ) );
$lbl_confirm_label       = tb_opt( 'tb_auth_reg_confirm_label',   __( 'Повторіть пароль', 'taxi-rental' ) );
$lbl_reg_submit          = tb_opt( 'tb_auth_reg_submit',          __( 'Зареєструватись', 'taxi-rental' ) );
$lbl_have_account        = tb_opt( 'tb_auth_reg_have_account',    __( 'Вже маєте акаунт?', 'taxi-rental' ) );
$lbl_recover_title       = tb_opt( 'tb_auth_recover_title',       __( 'Відновлення пароля', 'taxi-rental' ) );
$lbl_recover_subtitle    = tb_opt( 'tb_auth_recover_subtitle',    __( 'Введіть email адресу — надішлемо посилання для скидання пароля.', 'taxi-rental' ) );
$lbl_recover_submit      = tb_opt( 'tb_auth_recover_submit',      __( 'Надіслати посилання', 'taxi-rental' ) );
$lbl_back_to_login       = tb_opt( 'tb_auth_back_to_login',       __( 'Späť na prihlásenie', 'taxi-rental' ) );
$lbl_recover_email_ph    = tb_opt( 'tb_auth_recover_email_ph',    __( 'Zadajte váš email *', 'taxi-rental' ) );

// Recover — "email sent" success screen (Figma 343:1737)
$lbl_recover_sent_title  = tb_opt( 'tb_auth_recover_sent_title',  __( 'List bol odoslaný!', 'taxi-rental' ) );
$lbl_recover_sent_desc   = tb_opt( 'tb_auth_recover_sent_desc',   __( 'Skontrolujte email %s a kliknite na odkaz na obnovenie hesla. Odkaz je platný 30 minút.', 'taxi-rental' ) );
$lbl_recover_no_email    = tb_opt( 'tb_auth_recover_no_email',    __( 'Nedostali ste ho?', 'taxi-rental' ) );
$lbl_recover_resend      = tb_opt( 'tb_auth_recover_resend',      __( 'Odoslať znova', 'taxi-rental' ) );

$lbl_reset_title         = tb_opt( 'tb_auth_reset_title',         __( 'Новий пароль', 'taxi-rental' ) );
$lbl_reset_subtitle      = tb_opt( 'tb_auth_reset_subtitle',      __( 'Введіть новий пароль для вашого акаунту.', 'taxi-rental' ) );
$lbl_reset_pass_label    = tb_opt( 'tb_auth_reset_new_pass_label', __( 'Новий пароль', 'taxi-rental' ) );
$lbl_reset_pass_ph       = tb_opt( 'tb_auth_reset_new_pass_ph',   __( 'Мінімум 8 символів', 'taxi-rental' ) );
$lbl_reset_confirm_label = tb_opt( 'tb_auth_reset_confirm_label', __( 'Zopakujte heslo', 'taxi-rental' ) );
$lbl_reset_submit        = tb_opt( 'tb_auth_reset_submit',        __( 'Uložiť heslo', 'taxi-rental' ) );
$lbl_pw_toggle_show      = tb_opt( 'tb_auth_pw_toggle_show',      __( 'Zobraziť heslo', 'taxi-rental' ) );
$lbl_pw_toggle_hide      = tb_opt( 'tb_auth_pw_toggle_hide',      __( 'Skryť heslo', 'taxi-rental' ) );
$lbl_pw_strength_label   = tb_opt( 'tb_auth_pw_strength_label',   __( 'Sila hesla', 'taxi-rental' ) );
$lbl_pw_strength_weak    = tb_opt( 'tb_auth_pw_strength_weak',    __( 'Slabé', 'taxi-rental' ) );
$lbl_pw_strength_medium  = tb_opt( 'tb_auth_pw_strength_medium',  __( 'Stredné', 'taxi-rental' ) );
$lbl_pw_strength_strong  = tb_opt( 'tb_auth_pw_strength_strong',  __( 'Silné', 'taxi-rental' ) );

// Reset — "password changed" success screen (Figma 343:2101)
$lbl_reset_done_title    = tb_opt( 'tb_auth_reset_done_title',    __( 'Heslo bolo úspešne zmenené!', 'taxi-rental' ) );
$lbl_reset_done_desc     = tb_opt( 'tb_auth_reset_done_desc',     __( 'Teraz sa môžete prihlásiť do svojho účtu s novým heslom.', 'taxi-rental' ) );
$lbl_reset_done_btn      = tb_opt( 'tb_auth_reset_done_btn',      __( 'Prihlásiť sa do účtu', 'taxi-rental' ) );

// Account deleted — success screen (Figma 347:4838)
$lbl_deleted_title       = tb_opt( 'tb_auth_deleted_title',       __( 'Váš účet bol vymazaný', 'taxi-rental' ) );
$lbl_deleted_desc        = tb_opt( 'tb_auth_deleted_desc',        __( 'Hotovo. Váš účet už neexistuje. Škoda, že odchádzate — spolu by sme mohli zarábať ešte dlho. Ak si to rozmyslíte, registrácia zaberie len pár minút.', 'taxi-rental' ) );
$lbl_deleted_btn         = tb_opt( 'tb_auth_deleted_btn',         __( 'Vytvoriť nový účet', 'taxi-rental' ) );
$lbl_confirm_title       = tb_opt( 'tb_auth_confirm_title',       __( 'Підтвердіть свій email', 'taxi-rental' ) );
$lbl_confirm_email_text  = tb_opt( 'tb_auth_confirm_email_text',  __( 'Ми надіслали лист на %s. Перейдіть за посиланням у листі, щоб активувати акаунт.', 'taxi-rental' ) );
$lbl_confirm_no_email    = tb_opt( 'tb_auth_confirm_no_email_text', __( 'Ми надіслали лист із посиланням для підтвердження. Перевірте вашу поштову скриньку.', 'taxi-rental' ) );
$lbl_confirm_hint        = tb_opt( 'tb_auth_confirm_hint',        __( 'Не отримали листа? Перевірте теку «Спам» або надішліть повторно.', 'taxi-rental' ) );
$lbl_confirm_resend      = tb_opt( 'tb_auth_confirm_resend',      __( 'Надіслати лист повторно', 'taxi-rental' ) );
$lbl_notice_confirmed         = tb_opt( 'tb_auth_notice_confirmed',         __( 'Email підтверджено. Тепер ви можете увійти.', 'taxi-rental' ) );
$lbl_confirm_success_title    = tb_opt( 'tb_auth_confirm_success_title',    __( 'Email potvrdený!', 'taxi-rental' ) );
$lbl_confirm_success_desc     = tb_opt( 'tb_auth_confirm_success_desc',     __( 'Váš účet bol aktivovaný. Teraz sa môžete prihlásiť a používať TaxiBaza.sk.', 'taxi-rental' ) );
$lbl_confirm_login_btn        = tb_opt( 'tb_auth_confirm_login_btn',        __( 'Prihlásiť sa do účtu', 'taxi-rental' ) );
$lbl_change_email             = tb_opt( 'tb_auth_change_email',             __( 'Zmeniť email', 'taxi-rental' ) );

$lbl_required = __( 'обов\'язково', 'taxi-rental' );

get_header();
?>

<main class="tb-auth-page" id="main-content">
	<div class="tb-container">
		<div class="tb-auth-wrap">

			<?php if ( $error && ! ( 'login' === $state && 'invalid_credentials' === $error ) ) : ?>
				<div class="tb-auth-notice tb-auth-notice--error" role="alert">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- error_message() returns escaped/translatable strings.
					echo wp_kses(
						TaxiRental\Core\Account::error_message( $error ),
						[ 'a' => [ 'href' => [] ] ]
					);
					?>
				</div>
			<?php endif; ?>

			<?php if ( $confirmed ) : ?>
				<div class="tb-auth-notice tb-auth-notice--success" role="alert">
					<?php echo esc_html( $lbl_notice_confirmed ); ?>
				</div>
			<?php endif; ?>

			<?php
			// ----------------------------------------------------------------
			// LOGIN
			// ----------------------------------------------------------------
			if ( 'login' === $state ) :
			?>
			<div class="tb-auth-card">
				<h1 class="tb-auth-card__title">
					<?php echo esc_html( $lbl_login_title ); ?>
				</h1>

				<form class="tb-auth-form" method="post" action="" novalidate data-tb-validate>
					<?php wp_nonce_field( 'tb_login', 'tb_nonce' ); ?>
					<input type="hidden" name="tb_action" value="login">
					<input type="hidden" name="tb_redirect" value="<?php echo esc_attr( tb_url( '/cabinet/' ) ); ?>">

					<!-- Login Block banner (Figma 343:1251) — inside form card, above first field -->
					<?php if ( $is_blocked ) : ?>
					<div class="tb-warn-banner" role="alert">
						<div class="tb-warn-banner__icon" aria-hidden="true">!</div>
						<p class="tb-warn-banner__text">
							<?php esc_html_e( 'Ваш акаунт заблоковано. Зверніться до служби підтримки:', 'taxi-rental' ); ?>
							<a href="mailto:support@taxibaza.sk">support@taxibaza.sk</a>
						</p>
					</div>
					<?php endif; ?>

					<!-- Error banner (shown by JS on failed client-side validation) -->
					<div class="tb-error-banner" hidden role="alert">
						<div class="tb-error-banner__icon" aria-hidden="true">!</div>
						<p class="tb-error-banner__text"><?php esc_html_e( 'Будь ласка, виправте помилки перед відправкою.', 'taxi-rental' ); ?></p>
					</div>

					<?php if ( 'invalid_credentials' === $error ) : ?>
					<div class="tb-error-banner" role="alert">
						<div class="tb-error-banner__icon" aria-hidden="true">!</div>
						<p class="tb-error-banner__text">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- error_message() returns escaped/translatable string.
							echo wp_kses(
								TaxiRental\Core\Account::error_message( $error ),
								[ 'a' => [ 'href' => [] ] ]
							);
							?>
						</p>
					</div>
					<?php endif; ?>

					<!-- Honeypot -->
					<div class="tb-auth-form__hp" aria-hidden="true" tabindex="-1">
						<label for="tb_website">Website</label>
						<input type="text" id="tb_website" name="tb_website" autocomplete="off" tabindex="-1">
					</div>

					<div class="tb-auth-form__field">
						<label for="tb_email_login" class="tb-auth-form__label">
							<?php echo esc_html( $lbl_email_label ); ?>
						</label>
						<input
							type="email"
							id="tb_email_login"
							name="tb_email"
							class="tb-auth-form__input<?php echo ( 'invalid_credentials' === $error ) ? ' is-invalid' : ''; ?>"
							placeholder="<?php echo esc_attr( $lbl_email_ph ); ?>"
							autocomplete="email"
							required
							data-err-required="<?php esc_attr_e( 'Вкажіть email', 'taxi-rental' ); ?>"
							data-err-email="<?php esc_attr_e( 'Введіть коректну адресу: example@domain.com', 'taxi-rental' ); ?>"
						>
						<span class="tb-field-error" role="alert"></span>
					</div>

					<div class="tb-auth-form__field">
						<label for="tb_password_login" class="tb-auth-form__label">
							<?php echo esc_html( $lbl_pass_label ); ?>
						</label>
						<div class="tb-pw-field">
							<input
								type="password"
								id="tb_password_login"
								name="tb_password"
								class="tb-auth-form__input<?php echo ( 'invalid_credentials' === $error ) ? ' is-invalid' : ''; ?>"
								placeholder="<?php echo esc_attr( $lbl_pass_ph ); ?>"
								autocomplete="current-password"
								required
								data-err-required="<?php esc_attr_e( 'Введіть пароль', 'taxi-rental' ); ?>"
							>
							<button
								type="button"
								class="tb-pw-toggle"
								data-pw-toggle
								aria-label="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-show="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-hide="<?php echo esc_attr( $lbl_pw_toggle_hide ); ?>"
							>
								<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.4792 8.47917C12.1597 7.79861 12.5 6.97222 12.5 6C12.5 5.02778 12.1597 4.20139 11.4792 3.52083C10.7986 2.84028 9.97222 2.5 9 2.5C8.02778 2.5 7.20139 2.84028 6.52083 3.52083C5.84028 4.20139 5.5 5.02778 5.5 6C5.5 6.97222 5.84028 7.79861 6.52083 8.47917C7.20139 9.15972 8.02778 9.5 9 9.5C9.97222 9.5 10.7986 9.15972 11.4792 8.47917ZM7.58333 7.41667C7.19444 7.02778 7 6.55556 7 6C7 5.44444 7.19444 4.97222 7.58333 4.58333C7.97222 4.19444 8.44444 4 9 4C9.55556 4 10.0278 4.19444 10.4167 4.58333C10.8056 4.97222 11 5.44444 11 6C11 6.55556 10.8056 7.02778 10.4167 7.41667C10.0278 7.80556 9.55556 8 9 8C8.44444 8 7.97222 7.80556 7.58333 7.41667ZM3.58333 10.3646C1.95833 9.27431 0.763889 7.81944 0 6C0.763889 4.18056 1.95833 2.72569 3.58333 1.63542C5.20833 0.545139 7.01389 0 9 0C10.9861 0 12.7917 0.545139 14.4167 1.63542C16.0417 2.72569 17.2361 4.18056 18 6C17.2361 7.81944 16.0417 9.27431 14.4167 10.3646C12.7917 11.4549 10.9861 12 9 12C7.01389 12 5.20833 11.4549 3.58333 10.3646ZM13.3125 9.29167C14.6319 8.48611 15.6458 7.38889 16.3542 6C15.6458 4.61111 14.6319 3.51389 13.3125 2.70833C11.9931 1.90278 10.5556 1.5 9 1.5C7.44444 1.5 6.00694 1.90278 4.6875 2.70833C3.36806 3.51389 2.35417 4.61111 1.64583 6C2.35417 7.38889 3.36806 8.48611 4.6875 9.29167C6.00694 10.0972 7.44444 10.5 9 10.5C10.5556 10.5 11.9931 10.0972 13.3125 9.29167Z" fill="currentColor"/></svg>
							</button>
						</div>
						<span class="tb-field-error" role="alert"></span>
						<div class="tb-auth-form__row tb-auth-form__row--right">
							<a href="<?php echo esc_url( add_query_arg( 'action', 'recover', get_permalink() ) ); ?>" class="tb-auth-form__link-small">
								<?php echo esc_html( $lbl_forgot ); ?>
							</a>
						</div>
					</div>

					<button type="submit" class="tb-btn tb-btn--primary tb-btn--block">
						<?php echo esc_html( $lbl_login_submit ); ?>
					</button>

					<?php if ( class_exists( 'TaxiRental\\Core\\GoogleOAuth' ) && \TaxiRental\Core\GoogleOAuth::is_configured() ) : ?>
					<a href="<?php echo esc_url( \TaxiRental\Core\GoogleOAuth::start_url() ); ?>" class="tb-auth-google">
						<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/google-g.svg' ) ); ?>" alt="" width="24" height="24" aria-hidden="true">
						<span><?php echo esc_html( tb_opt( 'tb_auth_google_btn', __( 'Pokračovať cez Google', 'taxi-rental' ) ) ); ?></span>
					</a>
					<?php endif; ?>
				</form>

				<p class="tb-auth-card__footer">
					<?php echo esc_html( $lbl_no_account ); ?>
					<a href="<?php echo esc_url( add_query_arg( 'action', 'register', get_permalink() ) ); ?>" class="tb-auth-card__switch-link">
						<strong><?php echo esc_html( $lbl_register_link ); ?></strong>
					</a>
				</p>
			</div>
			<?php

			// ----------------------------------------------------------------
			// REGISTER
			// ----------------------------------------------------------------
			elseif ( 'register' === $state ) :
			?>
			<div class="tb-auth-card">
				<h1 class="tb-auth-card__title">
					<?php echo esc_html( $lbl_reg_title ); ?>
				</h1>
				<p class="tb-auth-card__subtitle">
					<?php echo esc_html( $lbl_reg_subtitle ); ?>
				</p>

				<p class="tb-auth-form__required-note">
					<span class="tb-auth-form__star" aria-hidden="true">*</span>
					<?php echo esc_html( $lbl_req_note ); ?>
				</p>

				<form class="tb-auth-form" method="post" action="" novalidate data-tb-validate
					<?php if ( defined( 'TB_RECAPTCHA_SITE_KEY' ) && TB_RECAPTCHA_SITE_KEY ) : ?>
					data-recaptcha-key="<?php echo esc_attr( TB_RECAPTCHA_SITE_KEY ); ?>"
					<?php endif; ?>>
					<?php wp_nonce_field( 'tb_register', 'tb_nonce' ); ?>
					<input type="hidden" name="tb_action" value="register">

					<!-- Error banner (shown by JS on failed validation) -->
					<div class="tb-error-banner" hidden role="alert">
						<div class="tb-error-banner__icon" aria-hidden="true">!</div>
						<p class="tb-error-banner__text"><?php esc_html_e( 'Будь ласка, виправте помилки перед відправкою.', 'taxi-rental' ); ?></p>
					</div>

					<!-- Honeypot -->
					<div class="tb-auth-form__hp" aria-hidden="true" tabindex="-1">
						<label for="tb_website_reg">Website</label>
						<input type="text" id="tb_website_reg" name="tb_website" autocomplete="off" tabindex="-1">
					</div>

					<div class="tb-auth-form__field">
						<label for="tb_name" class="tb-auth-form__label">
							<?php echo esc_html( $lbl_name_label ); ?>
							<span class="tb-auth-form__star" aria-label="<?php echo esc_attr( $lbl_required ); ?>">*</span>
						</label>
						<input
							type="text"
							id="tb_name"
							name="tb_name"
							class="tb-auth-form__input"
							placeholder="<?php echo esc_attr( $lbl_name_ph ); ?>"
							autocomplete="name"
							required
							data-err-required="<?php esc_attr_e( 'Введіть ваше ім\'я або назву флотилії', 'taxi-rental' ); ?>"
						>
						<span class="tb-field-error" role="alert"></span>
					</div>

					<div class="tb-auth-form__field">
						<label for="tb_email_reg" class="tb-auth-form__label">
							<?php echo esc_html( $lbl_reg_email_label ); ?>
							<span class="tb-auth-form__star" aria-label="<?php echo esc_attr( $lbl_required ); ?>">*</span>
						</label>
						<input
							type="email"
							id="tb_email_reg"
							name="tb_email"
							class="tb-auth-form__input"
							placeholder="<?php echo esc_attr( $lbl_email_ph ); ?>"
							autocomplete="email"
							required
							data-err-required="<?php esc_attr_e( 'Вкажіть email', 'taxi-rental' ); ?>"
							data-err-email="<?php esc_attr_e( 'Введіть коректну адресу: example@domain.com', 'taxi-rental' ); ?>"
						>
						<span class="tb-field-error" role="alert"></span>
					</div>

					<div class="tb-auth-form__field">
						<label for="tb_password_reg" class="tb-auth-form__label">
							<?php echo esc_html( $lbl_pass_label ); ?>
							<span class="tb-auth-form__star" aria-label="<?php echo esc_attr( $lbl_required ); ?>">*</span>
						</label>
						<div class="tb-pw-field">
							<input
								type="password"
								id="tb_password_reg"
								name="tb_password"
								class="tb-auth-form__input"
								placeholder="<?php echo esc_attr( $lbl_reg_pass_ph ); ?>"
								autocomplete="new-password"
								minlength="8"
								required
								data-err-required="<?php esc_attr_e( 'Введіть пароль', 'taxi-rental' ); ?>"
								data-err-minlength="<?php esc_attr_e( 'Пароль має містити щонайменше 8 символів', 'taxi-rental' ); ?>"
								data-pw-strength-input
							>
							<button
								type="button"
								class="tb-pw-toggle"
								data-pw-toggle
								aria-label="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-show="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-hide="<?php echo esc_attr( $lbl_pw_toggle_hide ); ?>"
							>
								<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.4792 8.47917C12.1597 7.79861 12.5 6.97222 12.5 6C12.5 5.02778 12.1597 4.20139 11.4792 3.52083C10.7986 2.84028 9.97222 2.5 9 2.5C8.02778 2.5 7.20139 2.84028 6.52083 3.52083C5.84028 4.20139 5.5 5.02778 5.5 6C5.5 6.97222 5.84028 7.79861 6.52083 8.47917C7.20139 9.15972 8.02778 9.5 9 9.5C9.97222 9.5 10.7986 9.15972 11.4792 8.47917ZM7.58333 7.41667C7.19444 7.02778 7 6.55556 7 6C7 5.44444 7.19444 4.97222 7.58333 4.58333C7.97222 4.19444 8.44444 4 9 4C9.55556 4 10.0278 4.19444 10.4167 4.58333C10.8056 4.97222 11 5.44444 11 6C11 6.55556 10.8056 7.02778 10.4167 7.41667C10.0278 7.80556 9.55556 8 9 8C8.44444 8 7.97222 7.80556 7.58333 7.41667ZM3.58333 10.3646C1.95833 9.27431 0.763889 7.81944 0 6C0.763889 4.18056 1.95833 2.72569 3.58333 1.63542C5.20833 0.545139 7.01389 0 9 0C10.9861 0 12.7917 0.545139 14.4167 1.63542C16.0417 2.72569 17.2361 4.18056 18 6C17.2361 7.81944 16.0417 9.27431 14.4167 10.3646C12.7917 11.4549 10.9861 12 9 12C7.01389 12 5.20833 11.4549 3.58333 10.3646ZM13.3125 9.29167C14.6319 8.48611 15.6458 7.38889 16.3542 6C15.6458 4.61111 14.6319 3.51389 13.3125 2.70833C11.9931 1.90278 10.5556 1.5 9 1.5C7.44444 1.5 6.00694 1.90278 4.6875 2.70833C3.36806 3.51389 2.35417 4.61111 1.64583 6C2.35417 7.38889 3.36806 8.48611 4.6875 9.29167C6.00694 10.0972 7.44444 10.5 9 10.5C10.5556 10.5 11.9931 10.0972 13.3125 9.29167Z" fill="currentColor"/></svg>
							</button>
						</div>
						<span class="tb-field-error" role="alert"></span>

						<div class="tb-pw-strength" data-pw-strength
							data-label="<?php echo esc_attr( $lbl_pw_strength_label ); ?>"
							data-level-weak="<?php echo esc_attr( $lbl_pw_strength_weak ); ?>"
							data-level-medium="<?php echo esc_attr( $lbl_pw_strength_medium ); ?>"
							data-level-strong="<?php echo esc_attr( $lbl_pw_strength_strong ); ?>"
						>
							<div class="tb-pw-strength__bars" aria-hidden="true">
								<span class="tb-pw-strength__bar"></span>
								<span class="tb-pw-strength__bar"></span>
								<span class="tb-pw-strength__bar"></span>
							</div>
							<p class="tb-pw-strength__label"></p>
						</div>
					</div>

					<div class="tb-auth-form__field">
						<label for="tb_password_confirm" class="tb-auth-form__label">
							<?php echo esc_html( $lbl_confirm_label ); ?>
							<span class="tb-auth-form__star" aria-label="<?php echo esc_attr( $lbl_required ); ?>">*</span>
						</label>
						<div class="tb-pw-field">
							<input
								type="password"
								id="tb_password_confirm"
								name="tb_password_confirm"
								class="tb-auth-form__input"
								placeholder="<?php echo esc_attr( $lbl_confirm_label ); ?>"
								autocomplete="new-password"
								minlength="8"
								required
								data-match-id="tb_password_reg"
								data-err-required="<?php esc_attr_e( 'Підтвердіть пароль', 'taxi-rental' ); ?>"
								data-err-match="<?php esc_attr_e( 'Паролі не збігаються', 'taxi-rental' ); ?>"
							>
							<button
								type="button"
								class="tb-pw-toggle"
								data-pw-toggle
								aria-label="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-show="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-hide="<?php echo esc_attr( $lbl_pw_toggle_hide ); ?>"
							>
								<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.4792 8.47917C12.1597 7.79861 12.5 6.97222 12.5 6C12.5 5.02778 12.1597 4.20139 11.4792 3.52083C10.7986 2.84028 9.97222 2.5 9 2.5C8.02778 2.5 7.20139 2.84028 6.52083 3.52083C5.84028 4.20139 5.5 5.02778 5.5 6C5.5 6.97222 5.84028 7.79861 6.52083 8.47917C7.20139 9.15972 8.02778 9.5 9 9.5C9.97222 9.5 10.7986 9.15972 11.4792 8.47917ZM7.58333 7.41667C7.19444 7.02778 7 6.55556 7 6C7 5.44444 7.19444 4.97222 7.58333 4.58333C7.97222 4.19444 8.44444 4 9 4C9.55556 4 10.0278 4.19444 10.4167 4.58333C10.8056 4.97222 11 5.44444 11 6C11 6.55556 10.8056 7.02778 10.4167 7.41667C10.0278 7.80556 9.55556 8 9 8C8.44444 8 7.97222 7.80556 7.58333 7.41667ZM3.58333 10.3646C1.95833 9.27431 0.763889 7.81944 0 6C0.763889 4.18056 1.95833 2.72569 3.58333 1.63542C5.20833 0.545139 7.01389 0 9 0C10.9861 0 12.7917 0.545139 14.4167 1.63542C16.0417 2.72569 17.2361 4.18056 18 6C17.2361 7.81944 16.0417 9.27431 14.4167 10.3646C12.7917 11.4549 10.9861 12 9 12C7.01389 12 5.20833 11.4549 3.58333 10.3646ZM13.3125 9.29167C14.6319 8.48611 15.6458 7.38889 16.3542 6C15.6458 4.61111 14.6319 3.51389 13.3125 2.70833C11.9931 1.90278 10.5556 1.5 9 1.5C7.44444 1.5 6.00694 1.90278 4.6875 2.70833C3.36806 3.51389 2.35417 4.61111 1.64583 6C2.35417 7.38889 3.36806 8.48611 4.6875 9.29167C6.00694 10.0972 7.44444 10.5 9 10.5C10.5556 10.5 11.9931 10.0972 13.3125 9.29167Z" fill="currentColor"/></svg>
							</button>
						</div>
						<span class="tb-field-error" role="alert"></span>
					</div>

					<?php
					// No account-type picker here by design: everyone signs up as a
					// car owner. Switching to a fleet account is a manual step in the
					// cabinet profile (or wp-admin), and the server ignores any
					// tb_account_type sent with this form.
					?>

					<?php if ( defined( 'TB_RECAPTCHA_SITE_KEY' ) && TB_RECAPTCHA_SITE_KEY ) : ?>
					<input type="hidden" name="tb_recaptcha_token" id="tb-recaptcha-token">
					<?php endif; ?>

					<button type="submit" class="tb-btn tb-btn--primary tb-btn--block">
						<?php echo esc_html( $lbl_reg_submit ); ?>
					</button>

					<?php if ( class_exists( 'TaxiRental\\Core\\GoogleOAuth' ) && \TaxiRental\Core\GoogleOAuth::is_configured() ) : ?>
					<a href="<?php echo esc_url( \TaxiRental\Core\GoogleOAuth::start_url() ); ?>" class="tb-auth-google">
						<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/google-g.svg' ) ); ?>" alt="" width="24" height="24" aria-hidden="true">
						<span><?php echo esc_html( tb_opt( 'tb_auth_google_btn', __( 'Pokračovať cez Google', 'taxi-rental' ) ) ); ?></span>
					</a>
					<?php endif; ?>
				</form>

				<p class="tb-auth-card__footer">
					<?php echo esc_html( $lbl_have_account ); ?>
					<a href="<?php echo esc_url( add_query_arg( 'action', 'login', get_permalink() ) ); ?>" class="tb-auth-card__switch-link">
						<strong><?php echo esc_html( $lbl_login_submit ); ?></strong>
					</a>
				</p>
			</div>
			<?php

			// ----------------------------------------------------------------
			// RECOVER (forgot password) — two sub-states: form / email sent
			// ----------------------------------------------------------------
			elseif ( 'recover' === $state ) :
			?>
			<?php if ( $sent ) : ?>
			<!-- SENT: "email sent" success screen (Figma 343:1737) -->
			<div class="tb-auth-card tb-auth-card--confirm">
				<div class="tb-auth-confirm__body">
					<div class="tb-auth-confirm__icon-wrap" aria-hidden="true">
						<svg width="32" height="32" viewBox="0 0 32 32" fill="none">
							<path d="M4 9.333l12 8 12-8M5.333 6.667h21.334A1.333 1.333 0 0128 8v16a1.333 1.333 0 01-1.333 1.333H5.333A1.333 1.333 0 014 24V8a1.333 1.333 0 011.333-1.333z" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<div>
						<h1 class="tb-auth-card__title" style="margin:0 0 8px">
							<?php echo esc_html( $lbl_recover_sent_title ); ?>
						</h1>
						<p class="tb-auth-card__subtitle" style="margin:0">
							<?php
							/* translators: %s: email address, wrapped in <strong> */
							printf(
								esc_html( $lbl_recover_sent_desc ),
								'<strong>' . esc_html( $recover_email ) . '</strong>'
							);
							?>
						</p>
					</div>
				</div>

				<div class="tb-auth-confirm__actions">
					<p class="tb-auth-card__hint" style="margin:0;text-align:center">
						<?php echo esc_html( $lbl_recover_no_email ); ?>
					</p>
					<?php if ( $recover_email ) : ?>
					<form method="post" action="">
						<?php wp_nonce_field( 'tb_recover', 'tb_nonce' ); ?>
						<input type="hidden" name="tb_action" value="recover">
						<input type="hidden" name="tb_email" value="<?php echo esc_attr( $recover_email ); ?>">
						<button type="submit" class="tb-btn tb-btn--secondary tb-btn--block">
							<?php echo esc_html( $lbl_recover_resend ); ?>
						</button>
					</form>
					<?php endif; ?>
				</div>
			</div>

			<?php else : ?>
			<!-- FORM: request the reset link -->
			<div class="tb-auth-card">
				<h1 class="tb-auth-card__title">
					<?php echo esc_html( $lbl_recover_title ); ?>
				</h1>
				<p class="tb-auth-card__subtitle">
					<?php echo esc_html( $lbl_recover_subtitle ); ?>
				</p>

				<form class="tb-auth-form" method="post" action="" novalidate data-tb-validate>
					<?php wp_nonce_field( 'tb_recover', 'tb_nonce' ); ?>
					<input type="hidden" name="tb_action" value="recover">

					<div class="tb-error-banner" hidden role="alert">
						<div class="tb-error-banner__icon" aria-hidden="true">!</div>
						<p class="tb-error-banner__text"><?php esc_html_e( 'Будь ласка, виправте помилки перед відправкою.', 'taxi-rental' ); ?></p>
					</div>

					<!-- Honeypot -->
					<div class="tb-auth-form__hp" aria-hidden="true" tabindex="-1">
						<label for="tb_website_rec">Website</label>
						<input type="text" id="tb_website_rec" name="tb_website" autocomplete="off" tabindex="-1">
					</div>

					<div class="tb-auth-form__field">
						<label for="tb_email_rec" class="screen-reader-text">
							<?php echo esc_html( $lbl_email_label ); ?>
						</label>
						<input
							type="email"
							id="tb_email_rec"
							name="tb_email"
							class="tb-auth-form__input"
							placeholder="<?php echo esc_attr( $lbl_recover_email_ph ); ?>"
							autocomplete="email"
							required
							data-err-required="<?php esc_attr_e( 'Вкажіть email', 'taxi-rental' ); ?>"
							data-err-email="<?php esc_attr_e( 'Введіть коректну адресу: example@domain.com', 'taxi-rental' ); ?>"
						>
						<span class="tb-field-error" role="alert"></span>
					</div>

					<button type="submit" class="tb-btn tb-btn--primary tb-btn--block">
						<?php echo esc_html( $lbl_recover_submit ); ?>
					</button>
				</form>

				<p class="tb-auth-card__footer">
					<a href="<?php echo esc_url( add_query_arg( 'action', 'login', get_permalink() ) ); ?>" class="tb-btn tb-btn--outline tb-btn--block">
						<?php echo esc_html( $lbl_back_to_login ); ?>
					</a>
				</p>
			</div>
			<?php endif; ?>
			<?php

			// ----------------------------------------------------------------
			// RESET PASSWORD (step 2 — enter new password) — two sub-states:
			// the form, or the "password changed" success screen (343:2101)
			// ----------------------------------------------------------------
			elseif ( 'reset_password' === $state ) :
			?>
			<?php if ( $reset_done ) : ?>
			<!-- SUCCESS: password changed, prompt to log in (Figma 343:2101) -->
			<div class="tb-auth-confirm-success">
				<div class="tb-auth-confirm__icon-wrap tb-auth-confirm__icon-wrap--success" aria-hidden="true">
					<svg width="36" height="36" viewBox="0 0 36 36" fill="none">
						<path d="M7 18.5L14 25.5L29 11" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<div class="tb-auth-confirm-success__content">
					<h1 class="tb-auth-confirm-success__title">
						<?php echo esc_html( $lbl_reset_done_title ); ?>
					</h1>
					<p class="tb-auth-confirm-success__desc">
						<?php echo esc_html( $lbl_reset_done_desc ); ?>
					</p>
				</div>
				<a href="<?php echo esc_url( tb_url( '/login/' ) ); ?>" class="tb-btn tb-btn--accent tb-btn--block">
					<?php echo esc_html( $lbl_reset_done_btn ); ?>
				</a>
			</div>

			<?php else : ?>
			<!-- FORM: new password + strength meter + confirm (Figma 343:1849) -->
			<div class="tb-auth-card">
				<h1 class="tb-auth-card__title">
					<?php echo esc_html( $lbl_reset_title ); ?>
				</h1>
				<p class="tb-auth-card__subtitle">
					<?php echo esc_html( $lbl_reset_subtitle ); ?>
				</p>

				<form class="tb-auth-form" method="post" action="" novalidate data-tb-validate>
					<?php wp_nonce_field( 'tb_reset_password', 'tb_nonce' ); ?>
					<input type="hidden" name="tb_action" value="reset_password">
					<input type="hidden" name="tb_key" value="<?php echo esc_attr( $reset_key ); ?>">
					<input type="hidden" name="tb_login" value="<?php echo esc_attr( $reset_login ); ?>">

					<div class="tb-error-banner" hidden role="alert">
						<div class="tb-error-banner__icon" aria-hidden="true">!</div>
						<p class="tb-error-banner__text"><?php esc_html_e( 'Будь ласка, виправте помилки перед відправкою.', 'taxi-rental' ); ?></p>
					</div>

					<div class="tb-auth-form__field">
						<label for="tb_password_reset" class="tb-auth-form__label">
							<?php echo esc_html( $lbl_reset_pass_label ); ?>
						</label>
						<div class="tb-pw-field">
							<input
								type="password"
								id="tb_password_reset"
								name="tb_password"
								class="tb-auth-form__input"
								placeholder="<?php echo esc_attr( $lbl_reset_pass_ph ); ?>"
								autocomplete="new-password"
								minlength="8"
								required
								data-err-required="<?php esc_attr_e( 'Введіть новий пароль', 'taxi-rental' ); ?>"
								data-err-minlength="<?php esc_attr_e( 'Пароль має містити щонайменше 8 символів', 'taxi-rental' ); ?>"
								data-pw-strength-input
							>
							<button
								type="button"
								class="tb-pw-toggle"
								data-pw-toggle
								aria-label="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-show="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-hide="<?php echo esc_attr( $lbl_pw_toggle_hide ); ?>"
							>
								<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.4792 8.47917C12.1597 7.79861 12.5 6.97222 12.5 6C12.5 5.02778 12.1597 4.20139 11.4792 3.52083C10.7986 2.84028 9.97222 2.5 9 2.5C8.02778 2.5 7.20139 2.84028 6.52083 3.52083C5.84028 4.20139 5.5 5.02778 5.5 6C5.5 6.97222 5.84028 7.79861 6.52083 8.47917C7.20139 9.15972 8.02778 9.5 9 9.5C9.97222 9.5 10.7986 9.15972 11.4792 8.47917ZM7.58333 7.41667C7.19444 7.02778 7 6.55556 7 6C7 5.44444 7.19444 4.97222 7.58333 4.58333C7.97222 4.19444 8.44444 4 9 4C9.55556 4 10.0278 4.19444 10.4167 4.58333C10.8056 4.97222 11 5.44444 11 6C11 6.55556 10.8056 7.02778 10.4167 7.41667C10.0278 7.80556 9.55556 8 9 8C8.44444 8 7.97222 7.80556 7.58333 7.41667ZM3.58333 10.3646C1.95833 9.27431 0.763889 7.81944 0 6C0.763889 4.18056 1.95833 2.72569 3.58333 1.63542C5.20833 0.545139 7.01389 0 9 0C10.9861 0 12.7917 0.545139 14.4167 1.63542C16.0417 2.72569 17.2361 4.18056 18 6C17.2361 7.81944 16.0417 9.27431 14.4167 10.3646C12.7917 11.4549 10.9861 12 9 12C7.01389 12 5.20833 11.4549 3.58333 10.3646ZM13.3125 9.29167C14.6319 8.48611 15.6458 7.38889 16.3542 6C15.6458 4.61111 14.6319 3.51389 13.3125 2.70833C11.9931 1.90278 10.5556 1.5 9 1.5C7.44444 1.5 6.00694 1.90278 4.6875 2.70833C3.36806 3.51389 2.35417 4.61111 1.64583 6C2.35417 7.38889 3.36806 8.48611 4.6875 9.29167C6.00694 10.0972 7.44444 10.5 9 10.5C10.5556 10.5 11.9931 10.0972 13.3125 9.29167Z" fill="currentColor"/></svg>
							</button>
						</div>
						<span class="tb-field-error" role="alert"></span>

						<div class="tb-pw-strength" data-pw-strength
							data-label="<?php echo esc_attr( $lbl_pw_strength_label ); ?>"
							data-level-weak="<?php echo esc_attr( $lbl_pw_strength_weak ); ?>"
							data-level-medium="<?php echo esc_attr( $lbl_pw_strength_medium ); ?>"
							data-level-strong="<?php echo esc_attr( $lbl_pw_strength_strong ); ?>"
						>
							<div class="tb-pw-strength__bars" aria-hidden="true">
								<span class="tb-pw-strength__bar"></span>
								<span class="tb-pw-strength__bar"></span>
								<span class="tb-pw-strength__bar"></span>
							</div>
							<p class="tb-pw-strength__label"></p>
						</div>
					</div>

					<div class="tb-auth-form__field">
						<label for="tb_password_reset_confirm" class="tb-auth-form__label">
							<?php echo esc_html( $lbl_reset_confirm_label ); ?>
							<span class="tb-auth-form__star" aria-label="<?php echo esc_attr( $lbl_required ); ?>">*</span>
						</label>
						<div class="tb-pw-field">
							<input
								type="password"
								id="tb_password_reset_confirm"
								name="tb_password_confirm"
								class="tb-auth-form__input"
								autocomplete="new-password"
								minlength="8"
								required
								data-match-id="tb_password_reset"
								data-err-required="<?php esc_attr_e( 'Potvrďte heslo', 'taxi-rental' ); ?>"
								data-err-match="<?php esc_attr_e( 'Heslá sa nezhodujú', 'taxi-rental' ); ?>"
							>
							<button
								type="button"
								class="tb-pw-toggle"
								data-pw-toggle
								aria-label="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-show="<?php echo esc_attr( $lbl_pw_toggle_show ); ?>"
								data-label-hide="<?php echo esc_attr( $lbl_pw_toggle_hide ); ?>"
							>
								<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.4792 8.47917C12.1597 7.79861 12.5 6.97222 12.5 6C12.5 5.02778 12.1597 4.20139 11.4792 3.52083C10.7986 2.84028 9.97222 2.5 9 2.5C8.02778 2.5 7.20139 2.84028 6.52083 3.52083C5.84028 4.20139 5.5 5.02778 5.5 6C5.5 6.97222 5.84028 7.79861 6.52083 8.47917C7.20139 9.15972 8.02778 9.5 9 9.5C9.97222 9.5 10.7986 9.15972 11.4792 8.47917ZM7.58333 7.41667C7.19444 7.02778 7 6.55556 7 6C7 5.44444 7.19444 4.97222 7.58333 4.58333C7.97222 4.19444 8.44444 4 9 4C9.55556 4 10.0278 4.19444 10.4167 4.58333C10.8056 4.97222 11 5.44444 11 6C11 6.55556 10.8056 7.02778 10.4167 7.41667C10.0278 7.80556 9.55556 8 9 8C8.44444 8 7.97222 7.80556 7.58333 7.41667ZM3.58333 10.3646C1.95833 9.27431 0.763889 7.81944 0 6C0.763889 4.18056 1.95833 2.72569 3.58333 1.63542C5.20833 0.545139 7.01389 0 9 0C10.9861 0 12.7917 0.545139 14.4167 1.63542C16.0417 2.72569 17.2361 4.18056 18 6C17.2361 7.81944 16.0417 9.27431 14.4167 10.3646C12.7917 11.4549 10.9861 12 9 12C7.01389 12 5.20833 11.4549 3.58333 10.3646ZM13.3125 9.29167C14.6319 8.48611 15.6458 7.38889 16.3542 6C15.6458 4.61111 14.6319 3.51389 13.3125 2.70833C11.9931 1.90278 10.5556 1.5 9 1.5C7.44444 1.5 6.00694 1.90278 4.6875 2.70833C3.36806 3.51389 2.35417 4.61111 1.64583 6C2.35417 7.38889 3.36806 8.48611 4.6875 9.29167C6.00694 10.0972 7.44444 10.5 9 10.5C10.5556 10.5 11.9931 10.0972 13.3125 9.29167Z" fill="currentColor"/></svg>
							</button>
						</div>
						<span class="tb-field-error" role="alert"></span>
					</div>

					<button type="submit" class="tb-btn tb-btn--primary tb-btn--block">
						<?php echo esc_html( $lbl_reset_submit ); ?>
					</button>
				</form>
			</div>
			<?php endif; ?>
			<?php
			// ----------------------------------------------------------------
			// CONFIRM EMAIL — two sub-states: pending inbox / confirmed success
			// ----------------------------------------------------------------
			elseif ( 'confirm_email' === $state ) :
			?>
			<?php if ( $confirmed ) : ?>
			<!-- SUCCESS: email confirmed, prompt to log in -->
			<div class="tb-auth-confirm-success">
				<div class="tb-auth-confirm__icon-wrap tb-auth-confirm__icon-wrap--success" aria-hidden="true">
					<svg width="36" height="36" viewBox="0 0 36 36" fill="none">
						<path d="M7 18.5L14 25.5L29 11" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<div class="tb-auth-confirm-success__content">
					<h1 class="tb-auth-confirm-success__title">
						<?php echo esc_html( $lbl_confirm_success_title ); ?>
					</h1>
					<p class="tb-auth-confirm-success__desc">
						<?php echo esc_html( $lbl_confirm_success_desc ); ?>
					</p>
				</div>
				<a href="<?php echo esc_url( tb_url( '/login/' ) ); ?>" class="tb-btn tb-btn--accent tb-btn--block">
					<?php echo esc_html( $lbl_confirm_login_btn ); ?>
				</a>
			</div>

			<?php else : ?>
			<!-- PENDING: check your inbox -->
			<div class="tb-auth-card tb-auth-card--confirm">
				<div class="tb-auth-confirm__body">
					<div class="tb-auth-confirm__icon-wrap" aria-hidden="true">
						<svg width="32" height="32" viewBox="0 0 32 32" fill="none">
							<path d="M4 9.333l12 8 12-8M5.333 6.667h21.334A1.333 1.333 0 0128 8v16a1.333 1.333 0 01-1.333 1.333H5.333A1.333 1.333 0 014 24V8a1.333 1.333 0 011.333-1.333z" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<div>
						<h1 class="tb-auth-card__title" style="margin:0 0 8px">
							<?php echo esc_html( $lbl_confirm_title ); ?>
						</h1>
						<p class="tb-auth-card__subtitle" style="margin:0">
							<?php
							if ( $confirm_email ) {
								printf(
									/* translators: %s: email address */
									wp_kses( $lbl_confirm_email_text, [ 'strong' => [] ] ),
									'<strong>' . esc_html( $confirm_email ) . '</strong>'
								);
							} else {
								echo esc_html( $lbl_confirm_no_email );
							}
							?>
						</p>
						<p class="tb-auth-card__hint" style="margin:8px 0 0">
							<?php echo esc_html( $lbl_confirm_hint ); ?>
						</p>
					</div>
				</div>

				<div class="tb-auth-confirm__actions">
					<?php if ( $confirm_email ) : ?>
					<form method="post" action="">
						<?php wp_nonce_field( 'tb_resend_confirm', 'tb_nonce' ); ?>
						<input type="hidden" name="tb_action" value="resend_confirm">
						<input type="hidden" name="tb_email" value="<?php echo esc_attr( $confirm_email ); ?>">
						<button type="submit" class="tb-btn tb-btn--primary tb-btn--block">
							<?php echo esc_html( $lbl_confirm_resend ); ?>
						</button>
					</form>
					<?php endif; ?>
					<a href="<?php echo esc_url( add_query_arg( 'action', 'register', tb_url( '/login/' ) ) ); ?>" class="tb-btn tb-btn--outline tb-btn--block">
						<?php echo esc_html( $lbl_change_email ); ?>
					</a>
				</div>
			</div>
			<?php endif; ?>

			<?php
			// The "choose account type" screen that used to sit here (Figma
			// 634:1716) is gone: Google sign-up assigns the car-owner role
			// itself and lands straight in the cabinet.
			// ----------------------------------------------------------------
			// ACCOUNT DELETED — success screen after self-delete (347:4838)
			// ----------------------------------------------------------------
			elseif ( 'account_deleted' === $state ) :
			?>
			<div class="tb-auth-confirm-success">
				<div class="tb-auth-confirm__icon-wrap tb-auth-confirm__icon-wrap--success" aria-hidden="true">
					<svg width="48" height="48" viewBox="0 0 48 48" fill="none">
						<path d="M31.3 21.35C32.0667 21.35 32.7083 21.0917 33.225 20.575C33.7417 20.0583 34 19.4167 34 18.65C34 17.8833 33.7417 17.2417 33.225 16.725C32.7083 16.2083 32.0667 15.95 31.3 15.95C30.5333 15.95 29.8917 16.2083 29.375 16.725C28.8583 17.2417 28.6 17.8833 28.6 18.65C28.6 19.4167 28.8583 20.0583 29.375 20.575C29.8917 21.0917 30.5333 21.35 31.3 21.35ZM16.7 21.35C17.4667 21.35 18.1083 21.0917 18.625 20.575C19.1417 20.0583 19.4 19.4167 19.4 18.65C19.4 17.8833 19.1417 17.2417 18.625 16.725C18.1083 16.2083 17.4667 15.95 16.7 15.95C15.9333 15.95 15.2917 16.2083 14.775 16.725C14.2583 17.2417 14 17.8833 14 18.65C14 19.4167 14.2583 20.0583 14.775 20.575C15.2917 21.0917 15.9333 21.35 16.7 21.35ZM17.925 29.025C16.1083 30.275 14.7667 31.9333 13.9 34H16.55C17.2833 32.6 18.3167 31.5167 19.65 30.75C20.9833 29.9833 22.45 29.6 24.05 29.6C25.6167 29.6 27.0583 29.9917 28.375 30.775C29.6917 31.5583 30.7333 32.6333 31.5 34H34.1C33.2667 31.9 31.9333 30.2333 30.1 29C28.2667 27.7667 26.2333 27.15 24 27.15C21.7667 27.15 19.7417 27.775 17.925 29.025ZM16.2 42.425C13.7667 41.375 11.65 39.95 9.85 38.15C8.05 36.35 6.625 34.2333 5.575 31.8C4.525 29.3667 4 26.7667 4 24C4 21.2333 4.525 18.6333 5.575 16.2C6.625 13.7667 8.05 11.65 9.85 9.85C11.65 8.05 13.7667 6.625 16.2 5.575C18.6333 4.525 21.2333 4 24 4C26.7667 4 29.3667 4.525 31.8 5.575C34.2333 6.625 36.35 8.05 38.15 9.85C39.95 11.65 41.375 13.7667 42.425 16.2C43.475 18.6333 44 21.2333 44 24C44 26.7667 43.475 29.3667 42.425 31.8C41.375 34.2333 39.95 36.35 38.15 38.15C36.35 39.95 34.2333 41.375 31.8 42.425C29.3667 43.475 26.7667 44 24 44C21.2333 44 18.6333 43.475 16.2 42.425ZM36.05 36.05C39.35 32.75 41 28.7333 41 24C41 19.2667 39.35 15.25 36.05 11.95C32.75 8.65 28.7333 7 24 7C19.2667 7 15.25 8.65 11.95 11.95C8.65 15.25 7 19.2667 7 24C7 28.7333 8.65 32.75 11.95 36.05C15.25 39.35 19.2667 41 24 41C28.7333 41 32.75 39.35 36.05 36.05Z" fill="#000"/>
					</svg>
				</div>
				<div class="tb-auth-confirm-success__content">
					<h1 class="tb-auth-confirm-success__title">
						<?php echo esc_html( $lbl_deleted_title ); ?>
					</h1>
					<p class="tb-auth-confirm-success__desc">
						<?php echo esc_html( $lbl_deleted_desc ); ?>
					</p>
				</div>
				<a href="<?php echo esc_url( add_query_arg( 'action', 'register', tb_url( '/login/' ) ) ); ?>" class="tb-btn tb-btn--accent tb-btn--block">
					<?php echo esc_html( $lbl_deleted_btn ); ?>
				</a>
			</div>

			<?php endif; ?>

		</div><!-- .tb-auth-wrap -->
	</div><!-- .tb-container -->

	<?php get_template_part( 'template-parts/cta-telegram' ); ?>
</main>

<?php get_footer(); ?>
