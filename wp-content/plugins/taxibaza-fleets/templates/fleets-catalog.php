<?php
/**
 * Fleet catalogue — /flotily/ (Figma: Flotillas 896:8179 — hero 896:8206, list 896:8213).
 * Header, footer and the Telegram band (896:8503) are the theme's.
 *
 * GET: f_city (city slug), f_platform (bolt|uber|hopin), sort (new|name), pg (page).
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Directory;
use TaxiBaza\Fleets\I18n;
use TaxiBaza\Fleets\Icons;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filters.
$tbf_q_city = isset( $_GET['f_city'] ) ? sanitize_title( wp_unslash( $_GET['f_city'] ) ) : '';
$tbf_q_plat = isset( $_GET['f_platform'] ) ? sanitize_key( wp_unslash( $_GET['f_platform'] ) ) : '';
$tbf_q_sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'new';
$tbf_q_page = isset( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1;
// phpcs:enable

$tbf_all      = array_map( [ Directory::class, 'card' ], Directory::visible_ids() );
$tbf_cities   = Directory::city_options( $tbf_all );
$tbf_plats    = Directory::platform_options( $tbf_all );
$tbf_city_key = Directory::city_key( $tbf_cities, $tbf_q_city );
$tbf_q_city   = $tbf_city_key ? $tbf_q_city : '';
$tbf_q_plat   = isset( Directory::PLATFORMS[ $tbf_q_plat ] ) ? $tbf_q_plat : '';
$tbf_q_sort   = in_array( $tbf_q_sort, Directory::SORTS, true ) ? $tbf_q_sort : 'new';
$tbf_found    = Directory::filter( $tbf_all, $tbf_city_key, $tbf_q_plat, $tbf_q_sort );
$tbf_pager    = Directory::paginate( $tbf_found, $tbf_q_page );

$tbf_state = [
	'f_city'     => $tbf_q_city,
	'f_platform' => $tbf_q_plat,
	'sort'       => $tbf_q_sort,
];
$tbf_link = static function ( array $change ) use ( $tbf_state ): string {
	return Directory::catalog_url( array_merge( $tbf_state, [ 'pg' => '' ], $change ) );
};
$tbf_assets   = plugins_url( 'assets/', dirname( __DIR__ ) . '/taxibaza-fleets.php' );
$tbf_register = add_query_arg(
	[ 'action' => 'register', 'type' => 'fleet' ],
	function_exists( 'tb_url' ) ? tb_url( '/login/' ) : home_url( '/login/' )
);

get_header();
?>
<main class="tbf-fl" id="main-content">

	<section class="tbf-hero" style="background-image:url('<?php echo esc_url( $tbf_assets . 'img/fleets-hero.jpg' ); ?>')">
		<div class="tb-container tbf-hero__inner">
			<h1 class="tbf-hero__title"><?php echo esc_html( t( 'fl_hero_title' ) ); ?></h1>
			<p class="tbf-hero__sub"><?php echo esc_html( t( 'fl_hero_sub' ) ); ?></p>
		</div>
	</section>

	<section class="tbf-list">
		<div class="tb-container">

			<div class="tbf-bar">
				<div class="tbf-bar__filters">
					<details class="tbf-dd" data-tbf-dd>
						<summary class="tbf-pill<?php echo $tbf_city_key ? ' is-active' : ''; ?>">
							<span><?php echo esc_html( $tbf_city_key ? $tbf_cities[ $tbf_city_key ]['name'] : t( 'fl_f_city' ) ); ?></span><?php Icons::out( 'expand', 16 ); ?>
						</summary>
						<ul class="tbf-dd__menu" role="list">
							<li><a href="<?php echo esc_url( $tbf_link( [ 'f_city' => '' ] ) ); ?>"<?php echo $tbf_city_key ? '' : ' aria-current="true"'; ?>><?php echo esc_html( t( 'fl_f_all_cities' ) ); ?></a></li>
							<?php foreach ( $tbf_cities as $tbf_k => $tbf_c ) : ?>
								<li><a href="<?php echo esc_url( $tbf_link( [ 'f_city' => $tbf_c['slug'] ] ) ); ?>"<?php echo $tbf_k === $tbf_city_key ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $tbf_c['name'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</details>
					<details class="tbf-dd" data-tbf-dd>
						<summary class="tbf-pill<?php echo $tbf_q_plat ? ' is-active' : ''; ?>">
							<span><?php echo esc_html( $tbf_q_plat ? Directory::PLATFORMS[ $tbf_q_plat ] : t( 'fl_f_platform' ) ); ?></span><?php Icons::out( 'expand', 16 ); ?>
						</summary>
						<ul class="tbf-dd__menu" role="list">
							<li><a href="<?php echo esc_url( $tbf_link( [ 'f_platform' => '' ] ) ); ?>"<?php echo $tbf_q_plat ? '' : ' aria-current="true"'; ?>><?php echo esc_html( t( 'fl_f_all_platforms' ) ); ?></a></li>
							<?php foreach ( $tbf_plats as $tbf_p ) : ?>
								<li><a href="<?php echo esc_url( $tbf_link( [ 'f_platform' => $tbf_p ] ) ); ?>"<?php echo $tbf_p === $tbf_q_plat ? ' aria-current="true"' : ''; ?>><?php echo esc_html( Directory::PLATFORMS[ $tbf_p ] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</details>
				</div>
				<details class="tbf-dd tbf-dd--end" data-tbf-dd>
					<summary class="tbf-pill tbf-pill--icon" aria-label="<?php echo esc_attr( t( 'fl_sort' ) ); ?>" title="<?php echo esc_attr( t( 'fl_sort' ) ); ?>"><?php Icons::out( 'sort', 20 ); ?></summary>
					<ul class="tbf-dd__menu" role="list">
						<?php foreach ( Directory::SORTS as $tbf_s ) : ?>
							<li><a href="<?php echo esc_url( $tbf_link( [ 'sort' => $tbf_s ] ) ); ?>"<?php echo $tbf_s === $tbf_q_sort ? ' aria-current="true"' : ''; ?>><?php echo esc_html( t( 'fl_sort_' . $tbf_s ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</details>
			</div>

			<div class="tbf-list__body">
				<p class="tbf-count"><?php echo esc_html( I18n::plural( $tbf_pager['total'], 'fl_count' ) ); ?></p>

				<div class="tbf-grid">
					<?php
					$tbf_promo_html = static function () use ( $tbf_assets, $tbf_register ): void {
						?>
						<aside class="tbf-promo" style="background-image:url('<?php echo esc_url( $tbf_assets . 'img/fleets-hero.jpg' ); ?>')">
							<div class="tbf-promo__body">
								<div class="tbf-promo__text">
									<h2 class="tbf-promo__title"><?php echo esc_html( t( 'fl_promo_title' ) ); ?></h2>
									<p><?php echo esc_html( t( 'fl_promo_text' ) ); ?></p>
								</div>
								<a class="tbf-btn tbf-btn--accent tbf-btn--lg" href="<?php echo esc_url( $tbf_register ); ?>"><?php echo esc_html( t( 'fl_promo_btn' ) ); ?></a>
							</div>
						</aside>
						<?php
					};
					$tbf_i = 0;
					foreach ( $tbf_pager['items'] as $tbf_card ) {
						if ( 2 === $tbf_i && 1 === $tbf_pager['page'] ) {
							$tbf_promo_html();
						}
						require __DIR__ . '/parts/fleet-card.php';
						$tbf_i++;
					}
					if ( 1 === $tbf_pager['page'] && $tbf_i <= 2 ) {
						$tbf_promo_html();
					}
					?>
				</div>

				<?php if ( ! $tbf_pager['total'] && ( $tbf_city_key || $tbf_q_plat ) ) : ?>
					<div class="tbf-empty">
						<p class="tbf-empty__title"><?php echo esc_html( t( 'fl_empty_title' ) ); ?></p>
						<p><?php echo esc_html( t( 'fl_empty_desc' ) ); ?></p>
						<a class="tbf-btn tbf-btn--outline" href="<?php echo esc_url( Directory::base_url() ); ?>"><?php echo esc_html( t( 'fl_reset' ) ); ?></a>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $tbf_pager['pages'] > 1 ) : ?>
				<nav class="tbf-pager" aria-label="<?php echo esc_attr( t( 'fl_pages' ) ); ?>">
					<?php $tbf_pg = $tbf_pager['page']; ?>
					<?php if ( $tbf_pg > 1 ) : ?>
						<a class="tbf-pager__arrow" href="<?php echo esc_url( $tbf_link( [ 'pg' => $tbf_pg - 1 ] ) ); ?>" aria-label="<?php echo esc_attr( t( 'fl_prev' ) ); ?>" rel="prev"><?php Icons::out( 'prev', 20 ); ?></a>
					<?php else : ?>
						<span class="tbf-pager__arrow is-off" aria-hidden="true"><?php Icons::out( 'prev', 20 ); ?></span>
					<?php endif; ?>
					<?php foreach ( Directory::page_links( $tbf_pg, $tbf_pager['pages'] ) as $tbf_n ) : ?>
						<?php if ( 0 === $tbf_n ) : ?>
							<span class="tbf-pager__num is-gap" aria-hidden="true">…</span>
						<?php elseif ( $tbf_n === $tbf_pg ) : ?>
							<span class="tbf-pager__num is-current" aria-current="page"><?php echo esc_html( (string) $tbf_n ); ?></span>
						<?php else : ?>
							<a class="tbf-pager__num" href="<?php echo esc_url( $tbf_link( [ 'pg' => $tbf_n ] ) ); ?>"><?php echo esc_html( (string) $tbf_n ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ( $tbf_pg < $tbf_pager['pages'] ) : ?>
						<a class="tbf-pager__arrow" href="<?php echo esc_url( $tbf_link( [ 'pg' => $tbf_pg + 1 ] ) ); ?>" aria-label="<?php echo esc_attr( t( 'fl_next' ) ); ?>" rel="next"><?php Icons::out( 'next', 20 ); ?></a>
					<?php else : ?>
						<span class="tbf-pager__arrow is-off" aria-hidden="true"><?php Icons::out( 'next', 20 ); ?></span>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

		</div>
	</section>

	<?php get_template_part( 'template-parts/cta-telegram' ); ?>

</main>
<?php
require __DIR__ . '/parts/work-modal.php';
get_footer();
