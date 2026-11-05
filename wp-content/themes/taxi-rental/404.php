<?php
/**
 * 404 — page not found.
 *
 * Figma: desktop 301:121, mobile 301:246.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main class="tb-404">
	<div class="tb-container tb-404__inner">
		<p class="tb-404__label"><?php echo esc_html( tb_opt( 'tb_404_label', __( 'Помилка', 'taxi-rental' ) ) ); ?></p>
		<p class="tb-404__code">404</p>
		<h1 class="tb-404__title"><?php echo esc_html( tb_opt( 'tb_404_title', __( 'Сторінку не знайдено', 'taxi-rental' ) ) ); ?></h1>
		<p class="tb-404__sub">
			<?php echo esc_html( tb_opt( 'tb_404_desc', __( 'Схоже, ця адреса більше не існує або була переміщена. Перевірте посилання або поверніться на головну сторінку.', 'taxi-rental' ) ) ); ?>
		</p>
		<div class="tb-404__actions">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="tb-btn tb-btn--primary">
				<?php echo esc_html( tb_opt( 'tb_404_home_btn', __( 'На головну', 'taxi-rental' ) ) ); ?>
			</a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'car_listing' ) ); ?>" class="tb-btn tb-btn--dark">
				<?php echo esc_html( tb_opt( 'tb_404_catalog_btn', __( 'Каталог авто', 'taxi-rental' ) ) ); ?>
			</a>
		</div>
	</div>
</main>

<?php get_footer(); ?>
