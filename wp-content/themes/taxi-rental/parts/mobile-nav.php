<?php
/**
 * parts/mobile-nav.php — full-screen mobile navigation drawer.
 * Triggered by the hamburger button in parts/header.php.
 * Hidden by default; toggled via JS (class "is-open").
 *
 * Logo + menu + auth labels share the same ACF options as the desktop header
 * (wp-admin → TaxiBaza → Контент сайту → Хедер / Меню).
 */

$cities = get_terms( [
	'taxonomy'   => 'city',
	'hide_empty' => false,
	'number'     => 20,
	'orderby'    => 'name',
	'order'      => 'ASC',
] );

$login_label    = (string) tb_opt( 'tb_login_label', __( 'Увійти', 'taxi-rental' ) );
$register_label = (string) tb_opt( 'tb_register_label', __( 'Зареєструватись', 'taxi-rental' ) );
?>
<div
	class="tb-mobile-nav"
	id="tb-mobile-nav"
	aria-hidden="true"
	aria-label="<?php esc_attr_e( 'Мобільне меню', 'taxi-rental' ); ?>"
	role="dialog"
	tabindex="-1"
>
	<!-- Drawer header: logo + close -->
	<div class="tb-mobile-nav__header">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="tb-logo tb-logo--sm" aria-label="<?php esc_attr_e( 'TaxiBaza.sk', 'taxi-rental' ); ?>">
			<?php echo tb_logo_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside helper. ?>
		</a>
		<button
			class="tb-mobile-nav__close"
			id="tb-menu-close"
			aria-label="<?php esc_attr_e( 'Закрити меню', 'taxi-rental' ); ?>"
		>
			<?php taxi_icon( 'close' ); ?>
		</button>
	</div>

	<?php if ( is_user_logged_in() ) :
		$tb_user   = wp_get_current_user();
		$tb_name   = $tb_user->display_name ?: $tb_user->user_login;
		$tb_letter = mb_strtoupper( mb_substr( $tb_name, 0, 1, 'UTF-8' ), 'UTF-8' );
		$tb_cab    = tb_url( '/cabinet/' );

		// The side menu only ever drew the first letter, so uploading a profile
		// photo appeared to do nothing here. Show the photo when there is one.
		$tb_av_id  = tb_user_avatar_id( (int) $tb_user->ID );
		$tb_av_url = $tb_av_id ? wp_get_attachment_image_url( $tb_av_id, 'thumbnail' ) : '';
	?>
	<!-- Account menu (logged-in) — Figma "Account Menu" sheet -->
	<div class="tb-mobile-nav__account">
		<div class="tb-mobile-nav__account-head">
			<span class="tb-mobile-nav__avatar<?php echo $tb_av_url ? ' tb-mobile-nav__avatar--photo' : ''; ?>" aria-hidden="true">
				<?php if ( $tb_av_url ) : ?>
					<img src="<?php echo esc_url( $tb_av_url ); ?>" alt="" width="40" height="40" loading="lazy">
				<?php else : ?>
					<?php echo esc_html( $tb_letter ); ?>
				<?php endif; ?>
			</span>
			<span class="tb-mobile-nav__account-id">
				<span class="tb-mobile-nav__account-name"><?php echo esc_html( $tb_name ); ?></span>
				<span class="tb-mobile-nav__account-email"><?php echo esc_html( $tb_user->user_email ); ?></span>
			</span>
		</div>
		<ul class="tb-mobile-nav__account-links" role="list">
			<?php foreach ( tb_account_menu() as $acc ) :
				$acc_label = (string) ( $acc['label'] ?? '' );
				$acc_href  = tb_menu_url( (string) ( $acc['url'] ?? '' ) );
				if ( '' === $acc_label ) {
					continue;
				}
			?>
			<li><a href="<?php echo esc_url( $acc_href ); ?>"><?php echo esc_html( $acc_label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<!-- Nav list — ACF: Контент сайту → Хедер / Меню -->
	<nav class="tb-mobile-nav__body" aria-label="<?php esc_attr_e( 'Мобільна навігація', 'taxi-rental' ); ?>">
		<ul class="tb-mobile-nav__list" role="list">

			<?php foreach ( tb_header_menu() as $i => $item ) :
				$label = (string) ( $item['label'] ?? '' );
				if ( '' === $label ) {
					continue;
				}
				$type   = (string) ( $item['submenu_type'] ?? 'none' );
				$href   = tb_menu_url( (string) ( $item['url'] ?? '' ) );
				$sub_id = 'mnav-' . (int) $i;
				?>

				<?php if ( 'cities' === $type ) :
					if ( is_wp_error( $cities ) || empty( $cities ) ) {
						continue;
					}
					?>
					<li class="tb-mobile-nav__item tb-mobile-nav__item--accordion">
						<button
							class="tb-mobile-nav__toggle"
							aria-expanded="true"
							aria-controls="<?php echo esc_attr( $sub_id ); ?>"
							data-accordion="<?php echo esc_attr( $sub_id ); ?>"
						>
							<?php echo esc_html( $label ); ?>
							<span class="tb-mobile-nav__chevron" aria-hidden="true"><?php taxi_icon( 'chevron' ); ?></span>
						</button>
						<ul class="tb-mobile-nav__sub is-open" id="<?php echo esc_attr( $sub_id ); ?>" role="list">
							<?php foreach ( $cities as $city ) : ?>
							<li>
								<a href="<?php echo esc_url( get_term_link( $city ) ); ?>">
									<?php echo esc_html( $city->name ); ?>
								</a>
							</li>
							<?php endforeach; ?>
							<li>
								<a href="<?php echo esc_url( home_url( '/mista/' ) ); ?>">
									<?php echo esc_html( tb_opt( 'tb_nav_all_cities', __( 'Všetky mestá →', 'taxi-rental' ) ) ); ?>
								</a>
							</li>
						</ul>
					</li>

				<?php elseif ( 'custom' === $type && ! empty( $item['submenu'] ) ) : ?>
					<li class="tb-mobile-nav__item tb-mobile-nav__item--accordion">
						<button
							class="tb-mobile-nav__toggle"
							aria-expanded="false"
							aria-controls="<?php echo esc_attr( $sub_id ); ?>"
							data-accordion="<?php echo esc_attr( $sub_id ); ?>"
						>
							<?php echo esc_html( $label ); ?>
							<span class="tb-mobile-nav__chevron" aria-hidden="true"><?php taxi_icon( 'chevron' ); ?></span>
						</button>
						<ul class="tb-mobile-nav__sub" id="<?php echo esc_attr( $sub_id ); ?>" role="list">
							<?php foreach ( (array) $item['submenu'] as $sub ) :
								$s_label = (string) ( $sub['label'] ?? '' );
								if ( '' === $s_label ) {
									continue;
								}
								$s_href = tb_menu_url( (string) ( $sub['url'] ?? '' ) );
								?>
								<li><a href="<?php echo esc_url( $s_href ); ?>"><?php echo esc_html( $s_label ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</li>

				<?php else :
					$is_current = ! empty( $item['current'] );
					?>
					<li class="tb-mobile-nav__item<?php echo $is_current ? ' tb-mobile-nav__item--current' : ''; ?>">
						<a class="tb-mobile-nav__link" href="<?php echo esc_url( $href ); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>>
							<?php echo esc_html( $label ); ?>
						</a>
					</li>
				<?php endif; ?>

			<?php endforeach; ?>

				<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<li class="tb-mobile-nav__item tb-mobile-nav__item--panel">
					<a class="tb-mobile-nav__link" href="<?php echo esc_url( home_url( '/panel/' ) ); ?>">
						<?php echo esc_html( tb_opt( 'tb_nav_panel', __( 'Panel', 'taxi-rental' ) ) ); ?>
					</a>
				</li>
				<?php endif; ?>

		</ul>
	</nav>

	<!-- Bottom auth buttons -->
	<div class="tb-mobile-nav__footer">
		<?php if ( is_user_logged_in() ) : ?>
			<a href="<?php echo esc_url( tb_url( '/cabinet/add-listing/' ) ); ?>" class="tb-btn tb-btn--primary tb-btn--block">
				<?php echo esc_html( (string) tb_opt( 'tb_add_listing_label', __( 'Додати оголошення', 'taxi-rental' ) ) ); ?>
			</a>
			<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="tb-btn tb-btn--outline tb-btn--block">
				<?php echo esc_html( (string) tb_opt( 'tb_logout_label', __( 'Вийти', 'taxi-rental' ) ) ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( tb_url( '/login/' ) ); ?>" class="tb-btn tb-btn--outline tb-btn--block">
				<?php echo esc_html( $login_label ); ?>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'action', 'register', tb_url( '/login/' ) ) ); ?>" class="tb-btn tb-btn--secondary tb-btn--block">
				<?php echo esc_html( $register_label ); ?>
			</a>
		<?php endif; ?>
	</div>

</div><!-- .tb-mobile-nav -->

<!-- Backdrop -->
<div class="tb-mobile-nav__backdrop" id="tb-menu-backdrop" aria-hidden="true"></div>
