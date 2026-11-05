<?php
/**
 * FAQ accordion — shared between About and Contact pages.
 *
 * Figma: 107:3198 (About), 107:3705 (Contact).
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_more = $args['show_more'] ?? true;

// Default Q&A — used when the FAQ repeater in TaxiBaza → Контент сайту is empty.
$faq_defaults = [
	[
		'q' => __( 'Як працює платформа для власників авто та водіїв?', 'taxi-rental' ),
		'a' => __( 'Власники та автопарки розміщують оголошення з умовами оренди, а водії обирають авто у своєму місті та зв\'язуються з власником напряму. Платформа не бере комісій з угоди — ви домовляєтесь безпосередньо.', 'taxi-rental' ),
	],
	[
		'q' => __( 'Які вимоги до автомобіля для реєстрації у Словаччині?', 'taxi-rental' ),
		'a' => __( 'Авто має бути зареєстроване у Словаччині, мати дійсний технічний огляд (STK/EK), страхування та відповідати вимогам платформ Bolt/Uber: зазвичай вік до 10–15 років і чотири дверей.', 'taxi-rental' ),
	],
	[
		'q' => __( 'Чи обов\'язково мати ліцензію таксі (Koncesia)?', 'taxi-rental' ),
		'a' => __( 'Так, для легальної роботи в таксі у Словаччині потрібна ліцензія (koncesia na taxislužbu). Частина власників пропонує авто, вже зареєстровані під ліцензією — уточнюйте це в оголошенні.', 'taxi-rental' ),
	],
	[
		'q' => __( 'Які документи повинен надати водій для оренди?', 'taxi-rental' ),
		'a' => __( 'Зазвичай: посвідчення водія, документ що посвідчує особу, та підтвердження права на роботу у Словаччині. Конкретний перелік визначає власник авто.', 'taxi-rental' ),
	],
	[
		'q' => __( 'Як відбувається оплата оренди?', 'taxi-rental' ),
		'a' => __( 'Оплату ви узгоджуєте напряму з власником — найчастіше це щотижневий платіж. Платформа не бере участі у розрахунках між сторонами.', 'taxi-rental' ),
	],
	[
		'q' => __( 'Хто несе відповідальність за технічне обслуговування авто?', 'taxi-rental' ),
		'a' => __( 'Це визначається договором оренди. Зазвичай планове ТО забезпечує власник, а водій відповідає за дбайливу експлуатацію та дрібні витратні матеріали.', 'taxi-rental' ),
	],
	[
		'q' => __( 'Чи включене страхування у вартість оренди?', 'taxi-rental' ),
		'a' => __( 'У більшості оголошень — так: PZP та havarijné poistenie включені в тижневу ціну. Перевіряйте деталі в описі авто або уточнюйте у власника.', 'taxi-rental' ),
	],
	[
		'q' => __( 'Чи можу я працювати одночасно на Uber та Bolt?', 'taxi-rental' ),
		'a' => __( 'Так, якщо авто відповідає вимогам обох платформ і власник не обмежує це договором. Багато водіїв поєднують платформи для більшого заробітку.', 'taxi-rental' ),
	],
	[
		'q' => __( 'Що таке "депозит" (застава) і чи він обов\'язковий?', 'taxi-rental' ),
		'a' => __( 'Депозит — це поворотна застава, яку власник може вимагати на випадок пошкоджень чи штрафів. Розмір і умови повернення вказуються в оголошенні та договорі.', 'taxi-rental' ),
	],
	[
		'q' => __( 'Як розірвати договір оренди?', 'taxi-rental' ),
		'a' => __( 'Умови розірвання прописуються в договорі з власником — зазвичай достатньо попередження за 1–2 тижні та повернення авто у належному стані.', 'taxi-rental' ),
	],
];

// Editable list from the options page (repeater rows: question / answer).
$faq_rows  = tb_opt( 'tb_faq_items', [] );
$faq_items = [];
if ( is_array( $faq_rows ) ) {
	foreach ( $faq_rows as $row ) {
		if ( ! empty( $row['question'] ) && ! empty( $row['answer'] ) ) {
			$faq_items[] = [
				'q' => $row['question'],
				'a' => $row['answer'],
			];
		}
	}
}
if ( empty( $faq_items ) ) {
	$faq_items = $faq_defaults;
}
?>

<section class="tb-faq" id="faq">
	<div class="tb-container tb-faq__inner">
		<h2 class="tb-faq__title"><?php echo esc_html( tb_opt( 'tb_faq_title', __( 'Питання та відповіді', 'taxi-rental' ) ) ); ?></h2>

		<div class="tb-faq__list">
			<?php foreach ( $faq_items as $item ) : ?>
			<details class="tb-faq__item">
				<summary class="tb-faq__q">
					<span><?php echo esc_html( $item['q'] ); ?></span>
					<svg class="tb-faq__arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
						<path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</summary>
				<p class="tb-faq__a"><?php echo esc_html( $item['a'] ); ?></p>
			</details>
			<?php endforeach; ?>
		</div>

		<?php if ( $show_more ) : ?>
		<div class="tb-faq__more">
			<p><?php echo esc_html( tb_opt( 'tb_faq_more_text', __( 'Не знайшли відповідь?', 'taxi-rental' ) ) ); ?></p>
			<a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>" class="tb-btn tb-btn--primary">
				<?php echo esc_html( tb_opt( 'tb_faq_more_button', __( 'Зв\'яжіться з нами', 'taxi-rental' ) ) ); ?>
			</a>
		</div>
		<?php endif; ?>
	</div>
</section>
