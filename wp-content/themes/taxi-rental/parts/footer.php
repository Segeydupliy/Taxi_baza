<?php

$current_year = gmdate( 'Y' );
$catalog_url  = get_post_type_archive_link( 'car_listing' );
$register_url = add_query_arg( 'action', 'register', tb_url( '/login/' ) );

// City terms for Міста column — top 8 by count.
// Same cities in every language (see tb_city_cards()).
$footer_cities = array_column( tb_city_cards( 'count', 8 ), 'term' );

// -------------------------------------------------------------------------
// ACF nav column data (repeaters seeded on first admin visit).
// Each item: [ 'label' => string, 'url' => string (relative or absolute) ].
// -------------------------------------------------------------------------
$nav_heading  = tb_opt( 'tb_footer_nav_heading',      __( 'Навігація', 'taxi-rental' ) );
$plat_heading = tb_opt( 'tb_footer_platform_heading', __( 'Платформа', 'taxi-rental' ) );
$city_heading = tb_opt( 'tb_footer_cities_heading', __( 'Mestá', 'taxi-rental' ) );
$sup_heading  = tb_opt( 'tb_footer_support_heading',  __( 'Підтримка', 'taxi-rental' ) );
$lbl_all_cities = tb_opt( 'tb_footer_cities_all',     __( 'Всі міста', 'taxi-rental' ) );
$lbl_lang       = tb_opt( 'tb_footer_lang_label',     __( 'Українська', 'taxi-rental' ) );

$nav_links = tb_footer_rows( tb_opt( 'tb_footer_nav_links', [] ) );
if ( empty( $nav_links ) ) {
	$nav_links = [
		[ 'label' => __( 'Головна',      'taxi-rental' ), 'url' => '/'         ],
		[ 'label' => __( 'Каталог авто', 'taxi-rental' ), 'url' => '/avto/'    ],
		[ 'label' => __( 'Міста',        'taxi-rental' ), 'url' => '/mista/'   ],
		[ 'label' => __( 'Про сервіс',   'taxi-rental' ), 'url' => '/pro-nas/' ],
		[ 'label' => __( 'Блог',         'taxi-rental' ), 'url' => '/blog/'    ],
		[ 'label' => __( 'Контакти',     'taxi-rental' ), 'url' => '/kontakt/' ],
	];
}

/**
 * Footer "Navigácia" rows (label, url, optional 'internal' => true for an absolute
 * URL of this site that must not open in a new tab, optional 'current' => true).
 * Plugins add items here (taxibaza-fleets adds "Flotily" after the catalogue).
 *
 * @param array<int,array<string,mixed>> $nav_links Rows.
 */
$nav_links = (array) apply_filters( 'tb_footer_nav_links', $nav_links );

$platform_links = tb_footer_rows( tb_opt( 'tb_footer_platform_links', [] ) );
if ( empty( $platform_links ) ) {
	$platform_links = [
		[ 'label' => __( 'Як це працює?', 'taxi-rental' ), 'url' => '/pro-nas/'                                                   ],
		[ 'label' => __( 'Розмістити авто', 'taxi-rental' ), 'url' => add_query_arg( 'action', 'add', '/cabinet/add-listing/' )   ],
		[ 'label' => __( 'Для власників', 'taxi-rental' ), 'url' => add_query_arg( 'type', 'owner', '/login/?action=register' )   ],
		[ 'label' => __( 'Для водіїв',    'taxi-rental' ), 'url' => '/avto/'                                                      ],
	];
}

$support_links = tb_footer_rows( tb_opt( 'tb_footer_support_links', [] ) );
if ( empty( $support_links ) ) {
	$support_links = [
		[ 'label' => __( 'Контакти',                  'taxi-rental' ), 'url' => '/kontakt/'              ],
		[ 'label' => __( 'Питання та відповіді',      'taxi-rental' ), 'url' => '/pro-nas/#faq'           ],
		[ 'label' => __( 'Telegram',                  'taxi-rental' ), 'url' => tb_telegram_url()         ],
		[ 'label' => __( 'Політика конфіденційності', 'taxi-rental' ), 'url' => '/ochrana-osobnych-udajov/' ],
		[ 'label' => __( 'Правила користування',      'taxi-rental' ), 'url' => '/podmienky-pouzivania/'    ],
	];
}

/**
 * Resolve a repeater item URL: absolute URLs pass through; relative paths get home_url() prepended.
 */
$resolve_url = static function ( string $url ): string {
	// Legal pages by ID: the menu items saved in the options still carry the old
	// slugs (/konfidenzialnist/, /umovy/, renamed 1.11.2026) or the new ones.
	if ( preg_match( '#^/(konfidenzialnist|ochrana-osobnych-udajov)/?$#', $url ) ) {
		return tb_legal_url( 'privacy' );
	}
	if ( preg_match( '#^/(umovy|podmienky-pouzivania)/?$#', $url ) ) {
		return tb_legal_url( 'terms' );
	}
	return str_starts_with( $url, 'http' ) ? $url : home_url( $url );
};
?>
<footer class="tb-footer" role="contentinfo">
	<div class="tb-container">
		<div class="tb-footer__inner">

		<!-- Logo -->
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="tb-logo tb-footer__logo" aria-label="<?php esc_attr_e( 'TaxiBaza.sk — на головну', 'taxi-rental' ); ?>">
			TaxiBaza<span class="dot">.</span>sk
		</a>

		<div class="tb-footer__cols">

			<!-- Col 1 — Навігація -->
			<div class="tb-footer__col">
				<p class="tb-footer__col-title"><?php echo esc_html( $nav_heading ); ?></p>
				<ul class="tb-footer__col-list" role="list">
					<?php foreach ( $nav_links as $link ) :
						$href       = $resolve_url( (string) ( $link['url'] ?? '' ) );
						$is_ext     = empty( $link['internal'] ) && str_starts_with( (string) ( $link['url'] ?? '' ), 'http' );
						$is_current = ! empty( $link['current'] );
					?>
					<li>
						<a href="<?php echo esc_url( $href ); ?>"<?php echo $is_ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?><?php echo $is_current ? ' aria-current="page"' : ''; ?>>
							<?php echo esc_html( $link['label'] ?? '' ); ?>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Col 2 — Платформа -->
			<div class="tb-footer__col">
				<p class="tb-footer__col-title"><?php echo esc_html( $plat_heading ); ?></p>
				<ul class="tb-footer__col-list" role="list">
					<?php foreach ( $platform_links as $link ) :
						$href   = $resolve_url( (string) ( $link['url'] ?? '' ) );
						$is_ext = str_starts_with( (string) ( $link['url'] ?? '' ), 'http' );
					?>
					<li>
						<a href="<?php echo esc_url( $href ); ?>"<?php echo $is_ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
							<?php echo esc_html( $link['label'] ?? '' ); ?>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Col 3 — Міста (dynamic taxonomy) -->
			<div class="tb-footer__col">
				<p class="tb-footer__col-title"><?php echo esc_html( $city_heading ); ?></p>
				<?php if ( ! empty( $footer_cities ) && ! is_wp_error( $footer_cities ) ) : ?>
				<ul class="tb-footer__col-list" role="list">
					<?php foreach ( $footer_cities as $fc ) :
						$fc_link = get_term_link( $fc );
					?>
					<li>
						<a href="<?php echo is_wp_error( $fc_link ) ? esc_url( $catalog_url ) : esc_url( $fc_link ); ?>">
							<?php echo esc_html( $fc->name ); ?>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php else : ?>
				<ul class="tb-footer__col-list" role="list">
					<li><a href="<?php echo esc_url( home_url( '/mista/' ) ); ?>"><?php echo esc_html( $lbl_all_cities ); ?></a></li>
				</ul>
				<?php endif; ?>
			</div>

			<!-- Col 4 — Підтримка -->
			<div class="tb-footer__col">
				<p class="tb-footer__col-title"><?php echo esc_html( $sup_heading ); ?></p>
				<ul class="tb-footer__col-list" role="list">
					<?php foreach ( $support_links as $link ) :
						$href   = $resolve_url( (string) ( $link['url'] ?? '' ) );
						$is_ext = str_starts_with( (string) ( $link['url'] ?? '' ), 'http' );
					?>
					<li>
						<a href="<?php echo esc_url( $href ); ?>"<?php echo $is_ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
							<?php echo esc_html( $link['label'] ?? '' ); ?>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>

		</div><!-- .tb-footer__cols -->

		<div class="tb-footer__bottom">
			<p class="tb-footer__copy">
				&copy; <?php echo esc_html( $current_year ); ?> TaxiBaza.sk.
				<?php echo esc_html( tb_opt( 'tb_footer_copy', __( 'Усі права захищені.', 'taxi-rental' ) ) ); ?>
				<span class="tb-footer__powered">&middot;
					<?php echo esc_html( tb_opt( 'tb_footer_powered_label', __( 'Powered by', 'taxi-rental' ) ) ); ?>
					<a href="https://www.resultsfactory.com/" class="tb-footer__powered-link" target="_blank" rel="noopener noreferrer">Results Factory</a>
				</span>
			</p>
			<div class="tb-footer__bottom-links">
				<a href="<?php echo esc_url( tb_legal_url( 'privacy' ) ); ?>" class="tb-footer__bottom-link">
					<?php echo esc_html( tb_opt( 'tb_footer_privacy_label', __( 'Zásady ochrany osobných údajov', 'taxi-rental' ) ) ); ?>
				</a>
				<a href="<?php echo esc_url( tb_legal_url( 'terms' ) ); ?>" class="tb-footer__bottom-link">
					<?php echo esc_html( tb_opt( 'tb_footer_terms_label', __( 'Podmienky používania', 'taxi-rental' ) ) ); ?>
				</a>
				<span class="tb-footer__lang">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm6.93 6h-2.95a15.65 15.65 0 0 0-1.38-3.56A8.03 8.03 0 0 1 18.93 8zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26zm.81 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.987 7.987 0 0 1 5.07 16zm2.95-8H5.07a7.987 7.987 0 0 1 4.33-3.56A15.65 15.65 0 0 0 8.02 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.65-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.35-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z" fill="currentColor"/></svg>
					<?php echo esc_html( $lbl_lang ); ?>
				</span>
			</div>
		</div>

		</div><!-- .tb-footer__inner -->
	</div><!-- .tb-container -->

</footer>
