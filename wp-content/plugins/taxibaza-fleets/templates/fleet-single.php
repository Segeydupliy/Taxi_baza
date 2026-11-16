<?php
/**
 * One fleet — /flotily/<slug>/ (Figma: Flotilla 909:11272, content 909:11298).
 * Car cards are the theme's (template-parts/catalog/card-car.php).
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Directory;
use TaxiBaza\Fleets\Fleet_Pages;
use TaxiBaza\Fleets\Fleet_View;
use TaxiBaza\Fleets\I18n;
use TaxiBaza\Fleets\Icons;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tbf_uid   = Fleet_Pages::current_fleet();
$tbf_c     = Directory::card( $tbf_uid );
$tbf_p     = array_merge( \TaxiBaza\Fleets\Profile::defaults(), (array) $tbf_c['profile'] );
$tbf_city  = Directory::city_line( $tbf_c );
$tbf_first = $tbf_c['cities'] ? reset( $tbf_c['cities'] ) : null;
$tbf_terms = Fleet_View::terms( $tbf_p );
$tbf_reqs  = Fleet_View::requirements( $tbf_p );
$tbf_cars  = Directory::listing_ids( $tbf_uid );
$tbf_total = count( $tbf_cars );
$tbf_show  = 4;
$tbf_all   = ! empty( $_GET['cars'] ) && 'all' === sanitize_key( wp_unslash( $_GET['cars'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display toggle.
$tbf_tel   = '' !== $tbf_p['phone'] ? 'tel:' . preg_replace( '/[^\d+]/', '', (string) $tbf_p['phone'] ) : '';
$tbf_tg    = ltrim( (string) $tbf_p['telegram'], '@' );
$tbf_wa    = (string) $tbf_p['whatsapp'];
$tbf_wa_n  = preg_replace( '/[^\d]/', '', $tbf_wa );

get_header();
?>
<main class="tbf-fp" id="main-content">
	<div class="tb-container">

		<nav class="tbf-crumbs" aria-label="<?php echo esc_attr( t( 'fl_breadcrumbs' ) ); ?>">
			<ol role="list">
				<li><a href="<?php echo esc_url( function_exists( 'tb_url' ) ? tb_url( '/' ) : home_url( '/' ) ); ?>"><?php echo esc_html( t( 'fl_home' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( Directory::base_url() ); ?>"><?php echo esc_html( t( 'fl_nav' ) ); ?></a></li>
				<?php if ( $tbf_first ) : ?>
					<li><a href="<?php echo esc_url( Directory::catalog_url( [ 'f_city' => $tbf_first['slug'] ] ) ); ?>"><?php echo esc_html( $tbf_first['name'] ); ?></a></li>
				<?php endif; ?>
				<li aria-current="page"><?php echo esc_html( $tbf_c['name'] ); ?></li>
			</ol>
		</nav>

		<div class="tbf-fp__top">
			<div class="tbf-fp__info">
				<div class="tbf-fp__id">
					<div class="tbf-logo tbf-logo--lg">
						<?php if ( '' !== $tbf_c['logo'] ) : ?>
							<img src="<?php echo esc_url( $tbf_c['logo'] ); ?>" alt="<?php echo esc_attr( $tbf_c['name'] ); ?>" width="126" height="126">
						<?php else : ?>
							<span class="tbf-logo__txt" aria-hidden="true"><?php echo esc_html( $tbf_c['initials'] ); ?></span>
						<?php endif; ?>
					</div>
					<div class="tbf-fp__head">
						<div class="tbf-fp__name-row">
							<h1 class="tbf-fp__name"><?php echo esc_html( $tbf_c['name'] ); ?></h1>
							<span class="tbf-verify" title="<?php echo esc_attr( t( 'fl_verified' ) ); ?>"><?php Icons::out( 'verify', 48 ); ?><span class="screen-reader-text"><?php echo esc_html( t( 'fl_verified' ) ); ?></span></span>
						</div>
						<?php if ( '' !== $tbf_city ) : ?>
							<p class="tbf-fp__addr"><?php Icons::out( 'location', 20 ); ?><span><?php echo esc_html( $tbf_city ); ?></span></p>
						<?php endif; ?>
						<?php if ( $tbf_c['platforms'] ) : ?>
							<div class="tbf-plats tbf-plats--lg">
								<?php foreach ( $tbf_c['platforms'] as $tbf_pl ) : ?>
									<span class="tbf-plat tbf-plat--<?php echo esc_attr( $tbf_pl ); ?>"><?php echo esc_html( Directory::PLATFORMS[ $tbf_pl ] ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( '' !== trim( (string) $tbf_p['description'] ) ) : ?>
					<p class="tbf-fp__desc"><?php echo nl2br( esc_html( (string) $tbf_p['description'] ) ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $tbf_p['ico'] ) ) : ?>
					<p class="tbf-fp__ico"><?php echo esc_html( t( 'fl_ico' ) ); ?>: <strong><?php echo esc_html( (string) $tbf_p['ico'] ); ?></strong></p>
				<?php endif; ?>
				<?php if ( $tbf_c['langs'] ) : ?>
					<ul class="tbf-langs" role="list" aria-label="<?php echo esc_attr( t( 'fl_langs' ) ); ?>">
						<?php foreach ( $tbf_c['langs'] as $tbf_l ) : ?>
							<li class="tbf-lang" title="<?php echo esc_attr( t( 'lang_' . $tbf_l ) ); ?>"><?php echo esc_html( Directory::lang_label( (string) $tbf_l ) ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<aside class="tbf-contact">
				<h2 class="tbf-contact__title"><?php echo esc_html( t( 'fl_contact_title' ) ); ?></h2>
				<?php if ( $tbf_tel || '' !== $tbf_tg || '' !== $tbf_wa ) : ?>
					<ul class="tbf-contact__list" role="list">
						<?php if ( $tbf_tel ) : ?>
							<li><?php Icons::out( 'phone', 20 ); ?><a href="<?php echo esc_url( $tbf_tel ); ?>"><?php echo esc_html( (string) $tbf_p['phone'] ); ?></a></li>
						<?php endif; ?>
						<?php if ( '' !== $tbf_tg ) : ?>
							<li><?php Icons::out( 'telegram', 20 ); ?><a href="<?php echo esc_url( 'https://t.me/' . rawurlencode( $tbf_tg ) ); ?>" target="_blank" rel="noopener noreferrer">@<?php echo esc_html( $tbf_tg ); ?></a></li>
						<?php endif; ?>
						<?php if ( '' !== $tbf_wa ) : ?>
							<li><?php Icons::out( 'whatsapp', 20 ); ?><?php if ( strlen( (string) $tbf_wa_n ) >= 8 ) : ?><a href="<?php echo esc_url( 'https://wa.me/' . $tbf_wa_n ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $tbf_wa ); ?></a><?php else : ?><span><?php echo esc_html( $tbf_wa ); ?></span><?php endif; ?></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>
				<div class="tbf-contact__cta">
					<p><?php echo esc_html( t( 'fl_want_work' ) ); ?></p>
					<button type="button" class="tbf-btn tbf-btn--accent tbf-btn--lg tbf-btn--block" data-tbf-apply data-fleet="<?php echo esc_attr( (string) $tbf_uid ); ?>" data-name="<?php echo esc_attr( $tbf_c['name'] ); ?>" data-models="<?php echo esc_attr( implode( ',', $tbf_c['models'] ) ); ?>"><?php echo esc_html( t( 'fl_apply' ) ); ?></button>
				</div>
			</aside>
		</div>

		<?php if ( $tbf_terms ) : ?>
			<section class="tbf-panel">
				<div class="tbf-panel__head">
					<h2 class="tbf-panel__title"><?php echo esc_html( t( 'fl_terms_title' ) ); ?></h2>
					<?php if ( 2 === count( $tbf_terms ) ) : ?>
						<p class="tbf-panel__sub"><?php echo esc_html( t( 'fl_terms_sub' ) ); ?></p>
					<?php endif; ?>
				</div>
				<div class="tbf-terms tbf-terms--<?php echo (int) count( $tbf_terms ); ?>">
					<?php foreach ( $tbf_terms as $tbf_col ) : ?>
						<div class="tbf-terms__col">
							<p class="tbf-kicker"><?php echo esc_html( $tbf_col['kicker'] ); ?></p>
							<?php if ( '' !== $tbf_col['value'] ) : ?>
								<p class="tbf-metric"><strong><?php echo esc_html( $tbf_col['value'] ); ?></strong> <span><?php echo esc_html( $tbf_col['value_text'] ); ?></span></p>
							<?php elseif ( ! empty( $tbf_col['note'] ) ) : ?>
								<p class="tbf-panel__sub"><?php echo esc_html( $tbf_col['note'] ); ?></p>
							<?php endif; ?>
							<?php if ( $tbf_col['perks'] ) : ?>
								<ul class="tbf-perks" role="list">
									<?php foreach ( $tbf_col['perks'] as $tbf_pk ) : ?>
										<li class="tbf-perk"><span class="tbf-perk__ic"><?php Icons::out( $tbf_pk['icon'], 20 ); ?></span><span class="tbf-perk__txt"><strong><?php echo esc_html( $tbf_pk['title'] ); ?></strong><span><?php echo esc_html( $tbf_pk['text'] ); ?></span></span></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $tbf_reqs ) : ?>
			<section class="tbf-panel">
				<div class="tbf-panel__head">
					<h2 class="tbf-panel__title"><?php echo esc_html( t( 'sec_req' ) ); ?></h2>
					<p class="tbf-panel__sub tbf-panel__sub--sm"><?php echo esc_html( t( 'fl_req_sub' ) ); ?></p>
				</div>
				<ul class="tbf-perks tbf-perks--grid" role="list">
					<?php foreach ( $tbf_reqs as $tbf_pk ) : ?>
						<li class="tbf-perk"><span class="tbf-perk__ic"><?php Icons::out( $tbf_pk['icon'], 20 ); ?></span><span class="tbf-perk__txt"><strong><?php echo esc_html( $tbf_pk['title'] ); ?></strong><span><?php echo esc_html( $tbf_pk['text'] ); ?></span></span></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<section class="tbf-panel">
			<div class="tbf-panel__head"><h2 class="tbf-panel__title"><?php echo esc_html( t( 'fl_start_title' ) ); ?></h2></div>
			<ol class="tbf-steps" role="list">
				<?php for ( $tbf_s = 1; $tbf_s <= 3; $tbf_s++ ) : ?>
					<?php if ( $tbf_s > 1 ) : ?>
						<li class="tbf-steps__arrow" aria-hidden="true"><?php Icons::out( 'step' ); ?></li>
					<?php endif; ?>
					<li class="tbf-step"><span class="tbf-step__num" aria-hidden="true"><?php echo (int) $tbf_s; ?></span><span class="tbf-step__txt"><strong><?php echo esc_html( t( 'fl_step' . $tbf_s . '_t' ) ); ?></strong><span><?php echo esc_html( t( 'fl_step' . $tbf_s . '_d' ) ); ?></span></span></li>
				<?php endfor; ?>
			</ol>
		</section>

		<?php if ( $tbf_total ) : ?>
			<section class="tbf-cars" id="tbf-cars">
				<div class="tbf-cars__head">
					<div>
						<h2 class="tbf-cars__title"><?php echo esc_html( t( 'fl_cars_title' ) ); ?></h2>
						<p class="tbf-cars__count"><?php echo esc_html( I18n::plural( $tbf_total, 'fl_cars' ) ); ?></p>
					</div>
					<?php if ( $tbf_total > $tbf_show && ! $tbf_all ) : ?>
						<a class="tbf-btn tbf-btn--accent tbf-btn--lg" href="<?php echo esc_url( add_query_arg( 'cars', 'all', Directory::fleet_url( $tbf_uid ) ) . '#tbf-cars' ); ?>"><?php echo esc_html( t( 'fl_cars_all' ) ); ?></a>
					<?php endif; ?>
				</div>
				<?php
				$tbf_q = new WP_Query(
					[
						'post_type'           => 'car_listing',
						'post_status'         => 'publish',
						'post__in'            => $tbf_all ? $tbf_cars : array_slice( $tbf_cars, 0, $tbf_show ),
						'orderby'             => 'post__in',
						'posts_per_page'      => $tbf_all ? count( $tbf_cars ) : $tbf_show,
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
						'suppress_filters'    => true, // IDs are already one per language group.
						'lang'                => 'all',
					]
				);
				?>
				<div class="tb-catalog__grid tbf-cars__grid">
					<?php
					while ( $tbf_q->have_posts() ) {
						$tbf_q->the_post();
						get_template_part( 'template-parts/catalog/card-car' );
					}
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>

	</div>

	<?php get_template_part( 'template-parts/cta-telegram' ); ?>
</main>
<?php
require __DIR__ . '/parts/work-modal.php';
get_footer();
