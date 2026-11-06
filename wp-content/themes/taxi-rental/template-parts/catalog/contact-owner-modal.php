<?php
/**
 * "Send Message" modal — request to rent a specific car.
 *
 * Figma: desktop 349:4912, mobile 349:5148.
 * Expects $args: post_id, title, price_week, city, category.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id    = (int) ( $args['post_id'] ?? 0 );
$title      = (string) ( $args['title'] ?? '' );
$price_week = (string) ( $args['price_week'] ?? '' );
$city       = (string) ( $args['city'] ?? '' );
$category   = (string) ( $args['category'] ?? '' );
$thumb      = get_the_post_thumbnail_url( $post_id, 'thumbnail' );

$lbl_modal_title  = tb_opt( 'tb_sng_modal_title',    __( 'Зв\'язатись з власником', 'taxi-rental' ) );
$lbl_modal_close  = tb_opt( 'tb_sng_modal_close',    __( 'Закрити', 'taxi-rental' ) );
$lbl_required     = tb_opt( 'tb_sng_modal_required', __( 'Обов\'язкові поля до заповнення', 'taxi-rental' ) );
$lbl_first_name   = tb_opt( 'tb_sng_form_first_name', __( 'Ім\'я', 'taxi-rental' ) );
$lbl_last_name    = tb_opt( 'tb_sng_form_last_name',  __( 'Прізвище', 'taxi-rental' ) );
$lbl_email        = tb_opt( 'tb_sng_form_email',      __( 'Email', 'taxi-rental' ) );
$lbl_phone        = tb_opt( 'tb_sng_form_phone',      __( 'Телефон', 'taxi-rental' ) );
$lbl_exp          = tb_opt( 'tb_sng_form_exp',        __( 'Стаж водіння', 'taxi-rental' ) );
$lbl_term         = tb_opt( 'tb_sng_form_term',       __( 'Термін оренди', 'taxi-rental' ) );
$lbl_date         = tb_opt( 'tb_sng_form_date',       __( 'Бажана дата початку', 'taxi-rental' ) );
$lbl_message      = tb_opt( 'tb_sng_form_message',    __( 'Повідомлення', 'taxi-rental' ) );
$lbl_select_ph    = tb_opt( 'tb_sng_select_ph',       __( 'Виберіть будь ласка', 'taxi-rental' ) );
$lbl_exp_lt1      = tb_opt( 'tb_sng_exp_lt1',   __( 'Менше 1 року', 'taxi-rental' ) );
$lbl_exp_1_3      = tb_opt( 'tb_sng_exp_1_3',   __( '1–3 роки', 'taxi-rental' ) );
$lbl_exp_3_5      = tb_opt( 'tb_sng_exp_3_5',   __( '3–5 років', 'taxi-rental' ) );
$lbl_exp_5plus    = tb_opt( 'tb_sng_exp_5plus',  __( '5+ років', 'taxi-rental' ) );
$lbl_term_1_2w    = tb_opt( 'tb_sng_term_1_2w', __( '1–2 тижні', 'taxi-rental' ) );
$lbl_term_2_4w    = tb_opt( 'tb_sng_term_2_4w', __( '2–4 тижні', 'taxi-rental' ) );
$lbl_term_1_3m    = tb_opt( 'tb_sng_term_1_3m', __( '1–3 місяці', 'taxi-rental' ) );
$lbl_term_3plus   = tb_opt( 'tb_sng_term_3plus', __( '3+ місяці', 'taxi-rental' ) );
$lbl_check_lic    = tb_opt( 'tb_sng_check_license',  __( 'Маю діючі водійські права', 'taxi-rental' ) );
$lbl_check_plat   = tb_opt( 'tb_sng_check_platform', __( 'Маю діючий договір з Bolt / Uber', 'taxi-rental' ) );
$lbl_check_dep    = tb_opt( 'tb_sng_check_deposit',  __( 'Готовий сплатити депозит', 'taxi-rental' ) );
$lbl_submit       = tb_opt( 'tb_sng_form_submit',    __( 'Надіслати запит', 'taxi-rental' ) );
$lbl_privacy      = tb_opt( 'tb_sng_privacy_link',   __( 'Політикою конфіденційності', 'taxi-rental' ) );
$lbl_terms_link   = tb_opt( 'tb_sng_terms_link',     __( 'Умовами використання', 'taxi-rental' ) );
$lbl_price_from   = tb_opt( 'tb_cat_price_from',     __( 'від', 'taxi-rental' ) );
$lbl_price_unit   = tb_opt( 'tb_cat_price_unit',     __( '/ тиждень', 'taxi-rental' ) );
$consent_tpl      = tb_opt(
	'tb_sng_consent_text',
	__( 'Надсилаючи повідомлення, ви погоджуєтесь з %1$s та %2$s. Ми використовуємо cookie для покращення роботи сайту.', 'taxi-rental' )
);

/* ── Success state (Figma 356:6355) ──────────────────────────────────────
 * #101: the form used to stay put and only grow a green line of text. The
 * comp replaces the whole body with a confirmation screen, so build it here
 * (hidden) and let the AJAX handler swap them over.
 *
 * The owner's name is known now; the phone number in the copy is the one the
 * VISITOR types into the form ("we will call you back on ..."), so JS fills
 * that placeholder at submit time.
 */
$owner_id    = $post_id ? (int) get_post_field( 'post_author', $post_id ) : 0;
$owner_name  = $owner_id ? (string) get_the_author_meta( 'display_name', $owner_id ) : '';
$city_terms  = $post_id ? get_the_terms( $post_id, 'city' ) : [];
$city_slug   = ( $city_terms && ! is_wp_error( $city_terms ) ) ? $city_terms[0]->slug : '';
$browse_url  = get_post_type_archive_link( 'car_listing' );
if ( $city_slug && $browse_url ) {
	$browse_url = add_query_arg( 'city', $city_slug, $browse_url );
}

$lbl_ok_title = tb_opt( 'tb_sng_ok_title', 'Žiadosť bola odoslaná!' );
$lbl_ok_desc  = tb_opt(
	'tb_sng_ok_desc',
	/* translators: 1: owner name, 2: the visitor's phone number */
	'Majiteľ auta %1$s dostal vašu žiadosť a ozve sa vám do 24 hodín na číslo %2$s'
);
$lbl_ok_hint  = tb_opt(
	'tb_sng_ok_hint',
	/* translators: %s: city name */
	'Kým čakáte, pozrite si ďalšie dostupné autá v meste %s'
);
$lbl_ok_btn   = tb_opt( 'tb_sng_ok_btn', 'Nájsť iné auto' );
?>

<div class="tb-modal" id="tb-owner-modal" role="dialog" aria-modal="true" aria-labelledby="tb-owner-modal-title" hidden>
	<div class="tb-modal__backdrop" data-modal-close></div>
	<div class="tb-modal__panel">

		<div class="tb-modal__head">
			<h2 class="tb-modal__title" id="tb-owner-modal-title"><?php echo esc_html( $lbl_modal_title ); ?></h2>
			<button type="button" class="tb-modal__close" data-modal-close aria-label="<?php echo esc_attr( $lbl_modal_close ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</button>
		</div>

		<div class="tb-modal__car">
			<?php if ( $thumb ) : ?>
			<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="tb-modal__car-img">
			<?php endif; ?>
			<div class="tb-modal__car-info">
				<div class="tb-modal__car-row">
					<span class="tb-modal__car-title"><?php echo esc_html( $title ); ?></span>
					<?php if ( $price_week ) : ?>
					<span class="tb-modal__car-price"><?php echo esc_html( $lbl_price_from ); ?> <strong><?php echo esc_html( $price_week ); ?>€</strong> <?php echo esc_html( $lbl_price_unit ); ?></span>
					<?php endif; ?>
				</div>
				<div class="tb-modal__car-meta">
					<?php if ( $category ) : ?>
					<span class="tb-modal__car-tag"><?php echo esc_html( $category ); ?></span>
					<?php endif; ?>
					<?php if ( $city ) : ?>
					<span class="tb-modal__car-city"><?php echo esc_html( $city ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<form class="tb-owner-form" id="tb-owner-form" novalidate>
			<p class="tb-contact-form__required"><span class="tb-req">*</span> <?php echo esc_html( $lbl_required ); ?></p>

			<input type="hidden" name="listing_id" value="<?php echo esc_attr( $post_id ); ?>">

			<div class="tb-contact-form__row">
				<div class="tb-contact-form__field">
					<label for="tb_om_first"><?php echo esc_html( $lbl_first_name ); ?> <span class="tb-req">*</span></label>
					<input type="text" id="tb_om_first" name="first_name" required autocomplete="given-name"
						data-err-required="<?php esc_attr_e( 'Будь ласка, введіть ваше ім\'я', 'taxi-rental' ); ?>">
					<span class="tb-field-error" role="alert"></span>
				</div>
				<div class="tb-contact-form__field">
					<label for="tb_om_last"><?php echo esc_html( $lbl_last_name ); ?> <span class="tb-req">*</span></label>
					<input type="text" id="tb_om_last" name="last_name" required autocomplete="family-name"
						data-err-required="<?php esc_attr_e( 'Будь ласка, введіть ваше прізвище', 'taxi-rental' ); ?>">
					<span class="tb-field-error" role="alert"></span>
				</div>
			</div>

			<div class="tb-contact-form__row">
				<div class="tb-contact-form__field">
					<label for="tb_om_email"><?php echo esc_html( $lbl_email ); ?> <span class="tb-req">*</span></label>
					<input type="email" id="tb_om_email" name="email" required autocomplete="email"
						data-err-required="<?php esc_attr_e( 'Вкажіть email', 'taxi-rental' ); ?>"
						data-err-email="<?php esc_attr_e( 'Введіть коректну адресу: example@domain.com', 'taxi-rental' ); ?>">
					<span class="tb-field-error" role="alert"></span>
				</div>
				<div class="tb-contact-form__field">
					<label for="tb_om_phone"><?php echo esc_html( $lbl_phone ); ?> <span class="tb-req">*</span></label>
					<input type="tel" id="tb_om_phone" name="phone" required autocomplete="tel"
						data-err-required="<?php esc_attr_e( 'Формат: +421 9XX XXX XXX', 'taxi-rental' ); ?>">
					<span class="tb-field-error" role="alert"></span>
				</div>
			</div>

			<div class="tb-contact-form__row">
				<div class="tb-contact-form__field">
					<label for="tb_om_exp"><?php echo esc_html( $lbl_exp ); ?> <span class="tb-req">*</span></label>
					<select id="tb_om_exp" name="experience" required
						data-err-required="<?php esc_attr_e( 'Це поле обов\'язкове', 'taxi-rental' ); ?>">
						<option value=""><?php echo esc_html( $lbl_select_ph ); ?></option>
						<option><?php echo esc_html( $lbl_exp_lt1 ); ?></option>
						<option><?php echo esc_html( $lbl_exp_1_3 ); ?></option>
						<option><?php echo esc_html( $lbl_exp_3_5 ); ?></option>
						<option><?php echo esc_html( $lbl_exp_5plus ); ?></option>
					</select>
					<span class="tb-field-error" role="alert"></span>
				</div>
				<div class="tb-contact-form__field">
					<label for="tb_om_term"><?php echo esc_html( $lbl_term ); ?> <span class="tb-req">*</span></label>
					<select id="tb_om_term" name="term" required
						data-err-required="<?php esc_attr_e( 'Це поле обов\'язкове', 'taxi-rental' ); ?>">
						<option value=""><?php echo esc_html( $lbl_select_ph ); ?></option>
						<option><?php echo esc_html( $lbl_term_1_2w ); ?></option>
						<option><?php echo esc_html( $lbl_term_2_4w ); ?></option>
						<option><?php echo esc_html( $lbl_term_1_3m ); ?></option>
						<option><?php echo esc_html( $lbl_term_3plus ); ?></option>
					</select>
					<span class="tb-field-error" role="alert"></span>
				</div>
			</div>

			<?php // #120 — unwrapped, this field spanned the full form width while every paired field above sits in a two-column .tb-contact-form__row, so on a wide tablet (900–1024px, before the row collapses to one column) it looked longer than the rest. A lone grid item only fills its own column, so wrapping it here matches their width without a dummy second field. ?>
			<div class="tb-contact-form__row">
				<div class="tb-contact-form__field tb-contact-form__field--date">
					<label for="tb_om_date"><?php echo esc_html( $lbl_date ); ?> <span class="tb-req">*</span></label>
					<input type="date" id="tb_om_date" name="start_date" required
						min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>"
						data-err-required="<?php esc_attr_e( 'Це поле обов\'язкове', 'taxi-rental' ); ?>"
						data-err-min="<?php esc_attr_e( 'Оберіть сьогоднішню або пізнішу дату', 'taxi-rental' ); ?>">
					<span class="tb-field-error" role="alert"></span>
				</div>
			</div>

			<div class="tb-contact-form__field">
				<label for="tb_om_message"><?php echo esc_html( $lbl_message ); ?> <span class="tb-req">*</span></label>
				<textarea id="tb_om_message" name="message" rows="5" required
					data-min-length="20"
					data-err-required="<?php esc_attr_e( 'Напишіть повідомлення для власника', 'taxi-rental' ); ?>"
					data-err-minlength="<?php esc_attr_e( 'Повідомлення занадто коротке (мінімум 20 символів)', 'taxi-rental' ); ?>"></textarea>
				<span class="tb-field-error" role="alert"></span>
			</div>

			<div class="tb-owner-form__checks">
				<label class="tb-check">
					<input type="checkbox" name="has_license" value="1">
					<span><?php echo esc_html( $lbl_check_lic ); ?></span>
				</label>
				<label class="tb-check">
					<input type="checkbox" name="has_platform" value="1">
					<span><?php echo esc_html( $lbl_check_plat ); ?></span>
				</label>
				<label class="tb-check">
					<input type="checkbox" name="has_deposit" value="1">
					<span><?php echo esc_html( $lbl_check_dep ); ?></span>
				</label>
			</div>

			<p class="tb-contact-form__consent">
				<?php
				printf(
					wp_kses(
						$consent_tpl,
						[ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ]
					),
					'<a href="' . esc_url( tb_legal_url( 'privacy' ) ) . '">' . esc_html( $lbl_privacy ) . '</a>',
					'<a href="' . esc_url( tb_legal_url( 'terms' ) ) . '">' . esc_html( $lbl_terms_link ) . '</a>'
				);
				?>
			</p>

			<?php // #100 — the banner belongs under the consent checkboxes (Figma 349:5638),
			// not at the top of the form where it used to sit. ?>
			<div class="tb-error-banner" id="tb-owner-err-banner" hidden role="alert">
				<div class="tb-error-banner__icon" aria-hidden="true">!</div>
				<p class="tb-error-banner__text"><?php esc_html_e( 'Будь ласка, виправте помилки перед відправкою.', 'taxi-rental' ); ?></p>
			</div>

			<!-- Status notice: success / server error (set by AJAX handler in main.js) -->
			<div class="tb-contact-form__notice" id="tb-owner-notice" role="status" aria-live="polite" hidden></div>

			<button type="submit" class="tb-btn tb-btn--primary tb-owner-form__submit">
				<?php echo esc_html( $lbl_submit ); ?>
			</button>
		</form>

		<?php // #101 — shown by main.js once the request goes through. ?>
		<div class="tb-owner-success" id="tb-owner-success" hidden>
			<div class="tb-owner-success__icon" aria-hidden="true">
				<svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17L4 12" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</div>
			<h3 class="tb-owner-success__title"><?php echo esc_html( $lbl_ok_title ); ?></h3>
			<p class="tb-owner-success__desc"
			   data-tpl="<?php echo esc_attr( $lbl_ok_desc ); ?>"
			   data-owner="<?php echo esc_attr( $owner_name ); ?>"></p>

			<?php if ( $city ) : ?>
			<div class="tb-owner-success__hint">
				<span class="tb-owner-success__hint-icon" aria-hidden="true">i</span>
				<span><?php echo esc_html( sprintf( $lbl_ok_hint, $city ) ); ?></span>
			</div>
			<?php endif; ?>

			<a href="<?php echo esc_url( $browse_url ); ?>" class="tb-btn tb-btn--outline-dark tb-owner-success__btn">
				<?php echo esc_html( $lbl_ok_btn ); ?>
			</a>
		</div>

	</div>
</div>
