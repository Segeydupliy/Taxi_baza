<?php
/**
 * parts/header.php — site header: logo, primary nav, lang switcher, auth links.
 * Desktop: full nav visible. Mobile: hamburger only (drawer in parts/mobile-nav.php).
 *
 * Logo + nav + auth labels are editable in wp-admin → TaxiBaza → Контент сайту → Хедер / Меню.
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
<header class="tb-header" id="site-header" role="banner">
	<div class="tb-container">
		<div class="tb-header__inner">

			<div class="tb-header__left">

			<!-- Logo -->
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="tb-logo" aria-label="<?php esc_attr_e( 'TaxiBaza.sk — на головну', 'taxi-rental' ); ?>">
				<?php echo tb_logo_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside helper. ?>
			</a>

			<!-- Primary navigation (desktop) — ACF: Контент сайту → Хедер / Меню -->
			<nav class="tb-header__nav" aria-label="<?php esc_attr_e( 'Головна навігація', 'taxi-rental' ); ?>">
				<ul class="tb-nav__list" role="list">

					<?php foreach ( tb_header_menu() as $i => $item ) :
						$label = (string) ( $item['label'] ?? '' );
						if ( '' === $label ) {
							continue;
						}
						$type    = (string) ( $item['submenu_type'] ?? 'none' );
						$href    = tb_menu_url( (string) ( $item['url'] ?? '' ) );
						$drop_id = 'dropdown-' . (int) $i;
						?>

						<?php if ( 'cities' === $type ) : ?>
							<li class="tb-nav__item tb-nav__item--has-dropdown">
								<button class="tb-nav__btn" aria-expanded="false" aria-haspopup="true" aria-controls="<?php echo esc_attr( $drop_id ); ?>">
									<?php echo esc_html( $label ); ?>
									<span class="tb-nav__chevron" aria-hidden="true"><?php taxi_icon( 'chevron' ); ?></span>
								</button>
								<?php if ( ! is_wp_error( $cities ) && ! empty( $cities ) ) : ?>
								<ul class="tb-nav__dropdown" id="<?php echo esc_attr( $drop_id ); ?>" role="list">
									<?php foreach ( $cities as $city ) : ?>
									<li>
										<a href="<?php echo esc_url( get_term_link( $city ) ); ?>">
											<?php echo esc_html( $city->name ); ?>
										</a>
									</li>
									<?php endforeach; ?>
									<li class="tb-nav__dropdown-all">
										<a href="<?php echo esc_url( home_url( '/mista/' ) ); ?>">
											<?php echo esc_html( tb_opt( 'tb_nav_all_cities', __( 'Všetky mestá →', 'taxi-rental' ) ) ); ?>
										</a>
									</li>
								</ul>
								<?php endif; ?>
							</li>

						<?php elseif ( 'custom' === $type && ! empty( $item['submenu'] ) ) : ?>
							<li class="tb-nav__item tb-nav__item--has-dropdown">
								<button class="tb-nav__btn" aria-expanded="false" aria-haspopup="true" aria-controls="<?php echo esc_attr( $drop_id ); ?>">
									<?php echo esc_html( $label ); ?>
									<span class="tb-nav__chevron" aria-hidden="true"><?php taxi_icon( 'chevron' ); ?></span>
								</button>
								<ul class="tb-nav__dropdown" id="<?php echo esc_attr( $drop_id ); ?>" role="list">
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
							<li class="tb-nav__item<?php echo $is_current ? ' tb-nav__item--current' : ''; ?>">
								<a href="<?php echo esc_url( $href ); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
							</li>
						<?php endif; ?>

					<?php endforeach; ?>

					<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<li class="tb-nav__item tb-nav__item--panel">
						<a href="<?php echo esc_url( home_url( '/panel/' ) ); ?>">
							<?php echo esc_html( tb_opt( 'tb_nav_panel', __( 'Panel', 'taxi-rental' ) ) ); ?>
						</a>
					</li>
					<?php endif; ?>

				</ul>
			</nav><!-- .tb-header__nav -->

			</div><!-- .tb-header__left -->

			<!-- Right-side actions -->
			<div class="tb-header__actions">

				<?php get_template_part( 'parts/lang-switcher' ); ?>

				<?php if ( is_user_logged_in() ) :
					$current_user  = wp_get_current_user();
					$display_name  = $current_user->display_name ?: $current_user->user_login;
					$first_letter  = mb_strtoupper( mb_substr( $display_name, 0, 1, 'UTF-8' ), 'UTF-8' );
					$avatar_id     = tb_user_avatar_id( (int) $current_user->ID );
					$avatar_url    = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';
				?>
					<a href="#" class="tb-header__bell" aria-label="<?php esc_attr_e( 'Сповіщення', 'taxi-rental' ); ?>">
						<?php taxi_icon( 'bell' ); ?>
					</a>
					<div class="tb-header__user" id="tb-user-dropdown-toggle" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
						<div class="tb-header__avatar<?php echo $avatar_url ? ' tb-header__avatar--photo' : ''; ?>" aria-hidden="true">
							<?php if ( $avatar_url ) : ?>
								<img src="<?php echo esc_url( $avatar_url ); ?>" alt="" width="36" height="36" loading="lazy">
							<?php else : ?>
								<?php echo esc_html( $first_letter ); ?>
							<?php endif; ?>
						</div>
						<span class="tb-nav__chevron" aria-hidden="true"><?php taxi_icon( 'chevron' ); ?></span>
						<ul class="tb-header__user-menu" role="menu">
							<?php foreach ( tb_account_menu() as $acc ) :
								$acc_label = (string) ( $acc['label'] ?? '' );
								$acc_href  = tb_menu_url( (string) ( $acc['url'] ?? '' ) );
								if ( '' === $acc_label ) {
									continue;
								}
							?>
							<li role="none"><a href="<?php echo esc_url( $acc_href ); ?>" role="menuitem"><?php echo esc_html( $acc_label ); ?></a></li>
							<?php endforeach; ?>
							<li role="none">
								<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" role="menuitem">
									<?php echo esc_html( (string) tb_opt( 'tb_logout_label', __( 'Вийти', 'taxi-rental' ) ) ); ?>
								</a>
							</li>
						</ul>
					</div>
				<?php else : ?>
					<a href="<?php echo esc_url( tb_url( '/login/' ) ); ?>" class="tb-header__link">
						<?php echo esc_html( $login_label ); ?>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'action', 'register', tb_url( '/login/' ) ) ); ?>" class="tb-btn tb-btn--secondary tb-header__cta">
						<?php echo esc_html( $register_label ); ?>
					</a>
				<?php endif; ?>

				<!-- Hamburger button — visible on mobile only -->
				<button
					class="tb-hamburger"
					id="tb-menu-toggle"
					aria-expanded="false"
					aria-controls="tb-mobile-nav"
					aria-label="<?php esc_attr_e( 'Відкрити меню', 'taxi-rental' ); ?>"
				>
					<?php taxi_icon( 'hamburger' ); ?>
				</button>

			</div><!-- .tb-header__actions -->

		</div><!-- .tb-header__inner -->
	</div><!-- .tb-container -->
</header>

