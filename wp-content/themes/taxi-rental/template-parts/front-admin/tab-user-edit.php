<?php
/**
 * Front-admin tab: User Edit. (Figma 222-1794 — 1:1)
 * Layout: head (title + ID + Скасувати/Зберегти) ; grid [photo card 360 | form card 880], gap 32.
 * Inherits from page-front-admin.php: $view_user_id, $panel_url.
 * Saves via AJAX action tb_fa_save_user.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user = get_userdata( $view_user_id );
if ( ! $user ) {
	echo '<div class="tb-fa-empty">' . esc_html( tb_opt( 'tb_fa_ue_not_found', 'Používateľ sa nenašiel.' ) ) . '</div>';
	return;
}

$primary_role = ! empty( $user->roles ) ? $user->roles[0] : 'subscriber';
$phone        = (string) get_user_meta( $view_user_id, 'phone_number', true );
$telegram     = (string) get_user_meta( $view_user_id, 'telegram', true );
$description  = (string) get_user_meta( $view_user_id, 'description', true );
$avatar_char  = strtoupper( mb_substr( $user->display_name ?: $user->user_login, 0, 1 ) );

$avatar_id    = tb_user_avatar_id( (int) $view_user_id );
$avatar_url   = $avatar_id ? (string) wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';

$back_url   = esc_url( add_query_arg( [ 'tab' => 'users', 'user' => $view_user_id ], $panel_url ) );
$cancel_url = $back_url;

$all_roles = [
	'car_owner' => tb_opt( 'tb_fa_ue_type_owner', 'Fyzická osoba' ),
	'fleet'     => tb_opt( 'tb_fa_ue_type_fleet', 'Flotila' ),
];

$chevron_svg = '<svg class="tb-fa-select-chevron" width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

// Toast when ?saved=1 returned after save.
$show_saved_toast = isset( $_GET['saved'] ) && '1' === $_GET['saved'];
?>

<!-- ── Head ──────────────────────────────────────────────────── -->
<div class="tb-fa-edit-head">
	<div class="tb-fa-edit-head__info">
		<h1 class="tb-fa-edit-head__title">
			<?php echo esc_html( $user->display_name ?: $user->user_login ); ?>
		</h1>
		<span class="tb-fa-edit-head__id">ID: <?php echo esc_html( (string) $view_user_id ); ?></span>
	</div>

	<!-- --form: hidden below 1024px, the form footer repeats these buttons -->
	<div class="tb-fa-edit-head__actions tb-fa-edit-head__actions--form">
		<a href="<?php echo $cancel_url; ?>" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--lg">
			<?php echo esc_html( tb_opt( 'tb_fa_ue_cancel_btn', 'Zrušiť' ) ); ?>
		</a>
		<button type="submit" form="tb-fa-user-edit-form" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--lg">
			<?php echo esc_html( tb_opt( 'tb_fa_ue_save_btn', 'Uložiť' ) ); ?>
		</button>
	</div>
</div>

<?php if ( $show_saved_toast ) : ?>
<div class="tb-fa-inline-toast is-visible" role="status">
	<?php echo esc_html( tb_opt( 'tb_fa_ue_saved_notice', 'Zmeny uložené' ) ); ?>
</div>
<?php endif; ?>

<!-- ── Edit Grid: [photo card 360 | form card 880] ───────────── -->
<div class="tb-fa-user-edit-grid">

	<!-- Photo card -->
	<div class="tb-fa-fs tb-fa-photo-card">
		<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_fa_ue_photo_title', 'Profilová fotografia' ) ); ?></h2>

		<div class="tb-fa-photo-card__body">
			<div class="tb-fa-photo-card__circle" id="tb-ue-avatar-circle">
				<?php if ( $avatar_url ) : ?>
					<img src="<?php echo esc_url( $avatar_url ); ?>" alt="" id="tb-ue-avatar-img">
				<?php else : ?>
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M3 9a2 2 0 0 1 2-2h1.5l1-1.5h5l1 1.5H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z" stroke="#999" stroke-width="1.5"/>
						<circle cx="12.5" cy="13" r="3.5" stroke="#999" stroke-width="1.5"/>
					</svg>
				<?php endif; ?>
			</div>

			<button type="button" class="tb-fa-btn tb-fa-btn--dark tb-fa-btn--lg" id="tb-ue-avatar-upload">
				<?php echo esc_html( tb_opt( 'tb_fa_ue_upload_btn', 'Nahrať nové' ) ); ?>
			</button>
			<button type="button" class="tb-fa-photo-card__remove<?php echo $avatar_id ? '' : ' is-hidden'; ?>" id="tb-ue-avatar-remove">
				<?php echo esc_html( tb_opt( 'tb_fa_ue_delete_photo', 'Odstrániť' ) ); ?>
			</button>
		</div>
	</div>

	<!-- Form card -->
	<form id="tb-fa-user-edit-form" class="tb-fa-fs tb-fa-user-form" data-user-id="<?php echo esc_attr( $view_user_id ); ?>" data-redirect="<?php echo $back_url; ?>">
		<?php wp_nonce_field( 'tb_fa_save_user_' . $view_user_id, 'tb_fa_nonce' ); ?>
		<input type="hidden" name="user_id" value="<?php echo esc_attr( $view_user_id ); ?>">
		<input type="hidden" name="avatar_id" id="tb-ue-avatar-id" value="<?php echo esc_attr( $avatar_id ?: '' ); ?>">

		<h2 class="tb-fa-fs__title"><?php echo esc_html( tb_opt( 'tb_fa_ue_section_info', 'Základné informácie' ) ); ?></h2>

		<div class="tb-fa-fs__fields tb-fa-fs__fields--full">
			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb_ue_first_name"><?php echo esc_html( tb_opt( 'tb_fa_ue_first_name', 'Meno' ) ); ?></label>
				<input class="tb-fa-fld__input" type="text" id="tb_ue_first_name" name="first_name"
					value="<?php echo esc_attr( $user->first_name ); ?>">
			</div>

			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb_ue_last_name"><?php echo esc_html( tb_opt( 'tb_fa_ue_last_name', 'Priezvisko' ) ); ?></label>
				<input class="tb-fa-fld__input" type="text" id="tb_ue_last_name" name="last_name"
					value="<?php echo esc_attr( $user->last_name ); ?>">
			</div>

			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb_ue_email"><?php echo esc_html( tb_opt( 'tb_fa_ue_email', 'Email' ) ); ?></label>
				<input class="tb-fa-fld__input" type="email" id="tb_ue_email" name="user_email"
					value="<?php echo esc_attr( $user->user_email ); ?>" required>
			</div>

			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb_ue_phone"><?php echo esc_html( tb_opt( 'tb_fa_ue_phone', 'Telefón' ) ); ?></label>
				<input class="tb-fa-fld__input" type="tel" id="tb_ue_phone" name="phone_number"
					value="<?php echo esc_attr( $phone ); ?>" placeholder="+421 XXX XXX XXX">
			</div>

			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb_ue_role"><?php echo esc_html( tb_opt( 'tb_fa_ue_user_type', 'Typ používateľa' ) ); ?></label>
				<div class="tb-fa-select-wrap">
					<select class="tb-fa-fld__select" id="tb_ue_role" name="user_role">
						<?php foreach ( $all_roles as $rk => $rl ) : ?>
							<option value="<?php echo esc_attr( $rk ); ?>" <?php selected( $primary_role, $rk ); ?>>
								<?php echo esc_html( $rl ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php echo $chevron_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>

			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb_ue_telegram"><?php echo esc_html( tb_opt( 'tb_fa_ue_telegram', 'Telegram' ) ); ?></label>
				<input class="tb-fa-fld__input" type="text" id="tb_ue_telegram" name="telegram"
					value="<?php echo esc_attr( $telegram ); ?>" placeholder="@username">
			</div>

			<div class="tb-fa-fld">
				<label class="tb-fa-fld__label" for="tb_ue_desc"><?php echo esc_html( tb_opt( 'tb_fa_ue_about', 'Krátky popis' ) ); ?></label>
				<textarea class="tb-fa-fld__input tb-fa-fld__textarea" id="tb_ue_desc" name="description" rows="6"><?php echo esc_textarea( $description ); ?></textarea>
			</div>
		</div>

		<!-- The head actions are hidden from 1024px down (the design puts them
		     below the form on narrow screens), so this footer carries the full
		     pair — not just Save. -->
		<div class="tb-fa-user-form__footer">
			<a href="<?php echo $cancel_url; ?>" class="tb-fa-btn tb-fa-btn--outline tb-fa-btn--lg">
				<?php echo esc_html( tb_opt( 'tb_fa_ue_cancel_btn', 'Zrušiť' ) ); ?>
			</a>
			<button type="submit" class="tb-fa-btn tb-fa-btn--accent tb-fa-btn--lg">
				<?php echo esc_html( tb_opt( 'tb_fa_ue_save_btn', 'Uložiť' ) ); ?>
			</button>
		</div>
	</form>
</div>
