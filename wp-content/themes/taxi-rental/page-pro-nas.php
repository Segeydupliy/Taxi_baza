<?php
/**
 * About page — /pro-nas/.
 *
 * Figma: desktop 107:2797, mobile 246:6787.
 * All texts editable via ACF group "Сторінка «Про нас»".
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$values_defaults = [
	[
		'title' => __( 'Прозорість у всьому', 'taxi-rental' ),
		'text'  => __( 'Ми будуємо довіру через чесність. На платформі немає прихованих платежів, незрозумілих комісій чи дрібного шрифту в договорах. Усі умови оренди, вимоги до авто та фінансові розрахунки є абсолютно відкритими для обох сторін.', 'taxi-rental' ),
	],
	[
		'title' => __( 'Безпека та надійність', 'taxi-rental' ),
		'text'  => __( 'Ваш спокій — наш пріоритет. Ми ретельно перевіряємо документи водіїв та технічний стан автомобілів. Кожна угода на платформі має чітке юридичне підґрунтя, що захищає інтереси як власників автопарків, так і орендарів.', 'taxi-rental' ),
	],
	[
		'title' => __( 'Взаємовигідне партнерство', 'taxi-rental' ),
		'text'  => __( 'Ми створюємо середовище, де виграє кожен. Власники автомобілів отримують стабільний та прогнозований дохід, а водії — комфортні, повністю готові до роботи авто, що дозволяє їм заробляти більше з першого дня.', 'taxi-rental' ),
	],
	[
		'title' => __( 'Зручність та простота', 'taxi-rental' ),
		'text'  => __( 'Ми цінуємо ваш час, тому позбулися зайвої бюрократії. Завдяки продуманому інтерфейсу та автоматизації рутинних процесів, пошук автомобіля чи здача його в оренду займає мінімум часу та зусиль.', 'taxi-rental' ),
	],
	[
		'title' => __( 'Спільнота та підтримка', 'taxi-rental' ),
		'text'  => __( 'Ми не залишаємо вас сам на сам після підписання договору. Наша команда завжди поруч, щоб допомогти з документами, технічними запитаннями чи порадою. Ми формуємо спільноту професіоналів, які допомагають одне одному.', 'taxi-rental' ),
	],
];

$milestones_defaults = [
	[
		'year'  => '2026',
		'title' => __( 'Засновано в Братиславі', 'taxi-rental' ),
		'text'  => __( 'Команда ентузіастів розпочинає створення платформи, щоб зробити ринок оренди таксі прозорим, безпечним та зручним для всіх учасників.', 'taxi-rental' ),
	],
	[
		'year'  => '2026',
		'title' => __( 'Запуск веб-платформи', 'taxi-rental' ),
		'text'  => __( 'Офіційний старт сервісу. Вже за перші місяці ми зібрали понад 100 актуальних оголошень та успішно запустилися в 3 містах Словаччини.', 'taxi-rental' ),
	],
	[
		'year'  => '2027',
		'title' => __( 'Впровадження функцій для флотилій', 'taxi-rental' ),
		'text'  => __( 'Запуск нових інструментів для автопарків. Тепер власники можуть легко управляти десятками автомобілів та контролювати доходи в єдиному кабінеті.', 'taxi-rental' ),
	],
	[
		'year'  => '2027',
		'title' => __( '10 міст, 1000+ оголошень', 'taxi-rental' ),
		'text'  => __( "Ми об'єднали найбільшу спільноту водіїв у Словаччині, пропонуючи найширший вибір перевірених авто для стабільного заробітку.", 'taxi-rental' ),
	],
];

$values = tb_field( 'tb_ap_values', [] );
if ( ! is_array( $values ) || empty( $values ) ) {
	$values = $values_defaults;
}

$milestones = tb_field( 'tb_ap_milestones', [] );
if ( ! is_array( $milestones ) || empty( $milestones ) ) {
	$milestones = $milestones_defaults;
}

// Wide photo — ACF override with theme asset fallback.
$photo_id  = tb_field( 'tb_ap_photo', 0 );
$photo_url = $photo_id ? wp_get_attachment_image_url( (int) $photo_id, 'full' ) : '';
if ( ! $photo_url ) {
	$photo_url = get_template_directory_uri() . '/assets/img/about-wide.jpg';
}

$mission_text_default = '<p>' . esc_html__( 'TaxiBaza.sk — словацький маркетплейс, що допомагає власникам автомобілів та флотиліям знайти надійних водіїв Bolt і Uber, а водіям — авто для роботи на справедливих умовах.', 'taxi-rental' ) . '</p>'
	. '<p>' . esc_html__( 'Ринок рідшеру в Словаччині росте щороку. Ми створюємо інструменти, які допомагають власникам авто заробляти більше, а водіям — стартувати швидше та з меншими витратами. Без зайвих посередників і прихованих комісій.', 'taxi-rental' ) . '</p>';
?>

<main class="tb-about-page">

	<section class="tb-about-hero">
		<div class="tb-container">
			<h1 class="tb-about-hero__title">
				<?php echo tb_nl2br( tb_field( 'tb_ap_hero_title', __( "З'єднуємо власників авто з водіями по всій Словаччині", 'taxi-rental' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in tb_nl2br(). ?>
			</h1>
		</div>
	</section>

	<section class="tb-about-photo">
		<img
			src="<?php echo esc_url( $photo_url ); ?>"
			alt="<?php esc_attr_e( 'Автомобіль для роботи в таксі', 'taxi-rental' ); ?>"
			loading="lazy"
		>
	</section>

	<section class="tb-about-mission">
		<div class="tb-container tb-about-mission__inner">
			<h2 class="tb-about-mission__title">
				<?php echo tb_nl2br( tb_field( 'tb_ap_mission_title', __( 'Ринок вимагає швидких рішень. Ми допомагаємо йти попереду.', 'taxi-rental' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in tb_nl2br(). ?>
			</h2>
			<div class="tb-about-mission__text">
				<?php echo wp_kses_post( tb_field( 'tb_ap_mission_text', $mission_text_default ) ); ?>
			</div>
		</div>
	</section>

	<section class="tb-about-values">
		<div class="tb-container">
			<div class="tb-about-values__head">
				<h2><?php echo esc_html( tb_field( 'tb_ap_values_title', __( 'Наші цінності', 'taxi-rental' ) ) ); ?></h2>
				<p><?php echo esc_html( tb_field( 'tb_ap_values_sub', __( 'Наша мета — безпечна та взаємовигідна оренда таксі, де технології працюють на користь людей.', 'taxi-rental' ) ) ); ?></p>
			</div>
			<div class="tb-about-values__grid">
				<?php foreach ( $values as $value ) : ?>
				<div class="tb-about-values__card">
					<h3><?php echo esc_html( $value['title'] ?? '' ); ?></h3>
					<p><?php echo esc_html( $value['text'] ?? '' ); ?></p>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="tb-about-history">
		<div class="tb-container">
			<div class="tb-about-history__head">
				<p class="tb-about-history__label"><?php echo esc_html( tb_field( 'tb_ap_history_label', __( 'Історія', 'taxi-rental' ) ) ); ?></p>
				<h2><?php echo esc_html( tb_field( 'tb_ap_history_title', __( 'Від заснування до 1000+ оголошень', 'taxi-rental' ) ) ); ?></h2>
			</div>
			<ol class="tb-timeline">
				<?php foreach ( $milestones as $step ) : ?>
				<li class="tb-timeline__item">
					<span class="tb-timeline__year"><?php echo esc_html( $step['year'] ?? '' ); ?></span>
					<h3 class="tb-timeline__title"><?php echo esc_html( $step['title'] ?? '' ); ?></h3>
					<p class="tb-timeline__text"><?php echo esc_html( $step['text'] ?? '' ); ?></p>
				</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<?php get_template_part( 'template-parts/faq' ); ?>

	<?php get_template_part( 'template-parts/cta-telegram' ); ?>

</main>

<?php get_footer(); ?>
