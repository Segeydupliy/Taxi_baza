<?php
/**
 * Telegram community CTA (green band).
 *
 * Texts are editable globally in wp-admin → TaxiBaza → Контент сайту
 * (tab "CTA Telegram"). Callers may override any value via $args:
 * label, title, desc, button, url, btn_class.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cta = wp_parse_args(
	$args ?? [],
	[
		'label'     => tb_opt( 'tb_cta_label', __( 'Telegram спільнота', 'taxi-rental' ) ),
		'title'     => tb_opt( 'tb_cta_title', __( "Приєднайся до 1,200+\nводіїв та власників", 'taxi-rental' ) ),
		'desc'      => tb_opt( 'tb_cta_desc', __( 'Нові оголошення, поради та пряме спілкування між власниками й водіями', 'taxi-rental' ) ),
		'button'    => tb_opt( 'tb_cta_button', __( 'Відкрити Telegram', 'taxi-rental' ) ),
		'url'       => tb_telegram_url(),
		'btn_class' => 'tb-btn--dark',
	]
);
?>
<section class="tb-cta-tg">
	<div class="tb-container">
		<div class="tb-cta-tg__inner">
			<div class="tb-cta-tg__content">
				<span class="tb-cta-tg__label"><?php echo esc_html( $cta['label'] ); ?></span>
				<h2 class="tb-cta-tg__title">
					<?php echo tb_nl2br( $cta['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in tb_nl2br(). ?>
				</h2>
				<p class="tb-cta-tg__desc">
					<?php echo esc_html( $cta['desc'] ); ?>
				</p>
			</div>
			<a href="<?php echo esc_url( $cta['url'] ); ?>" class="tb-btn <?php echo esc_attr( $cta['btn_class'] ); ?> tb-cta-tg__btn" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $cta['button'] ); ?>
			</a>
		</div>
	</div>
</section>
