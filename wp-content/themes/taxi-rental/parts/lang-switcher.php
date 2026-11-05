<?php
/**
 * parts/lang-switcher.php — language switcher trigger + overlays.
 *
 * Desktop: trigger opens a centred popup card.
 * Mobile:  trigger opens a full-screen language panel.
 *
 * WPML: uses wpml_active_languages / wpml_current_language filters.
 * No WPML: falls back to a static Slovenčina entry.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Language data ──────────────────────────────────────────────────────────
$tb_lang_flags = [
	'uk' => '🇺🇦',
	'sk' => '🇸🇰',
	'en' => '🇬🇧',
	'de' => '🇩🇪',
	'ru' => '🇷🇺',
	'pl' => '🇵🇱',
	'cs' => '🇨🇿',
	'hu' => '🇭🇺',
];

// English names always shown as the secondary (gray) label — regardless of the
// current UI language. WPML's own `translated_name` is translated INTO the
// current language, which is not what the design calls for here.
$tb_lang_english = [
	'uk' => 'Ukrainian',
	'sk' => 'Slovak',
	'en' => 'English',
	'de' => 'German',
	'ru' => 'Russian',
	'pl' => 'Polish',
	'cs' => 'Czech',
	'hu' => 'Hungarian',
];

$tb_lang_list = apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] );
if ( empty( $tb_lang_list ) || ! is_array( $tb_lang_list ) ) {
	$tb_lang_list = [
		[
			'code'            => 'sk',
			'native_name'     => 'Slovenčina',
			'translated_name' => 'Slovak',
			'url'             => home_url( '/' ),
			'active'          => 1,
		],
	];
}

$tb_current_code = strtolower( (string) apply_filters( 'wpml_current_language', 'sk' ) );
$tb_display_code = strtoupper( $tb_current_code );
?>

<?php /* ── Trigger button (inside header) ──────────────────────────────────── */ ?>
<button
	class="tb-lang-btn"
	id="tb-lang-btn"
	aria-haspopup="dialog"
	aria-expanded="false"
	aria-controls="tb-lang-popup"
	aria-label="<?php esc_attr_e( 'Змінити мову', 'taxi-rental' ); ?>"
>
	<?php taxi_icon( 'language' ); ?>
	<span class="tb-lang-btn__code"><?php echo esc_html( $tb_display_code ); ?></span>
</button>

<?php /* ── Desktop popup (fixed, centred) ─────────────────────────────────── */ ?>
<div
	class="tb-lang-popup"
	id="tb-lang-popup"
	role="dialog"
	aria-modal="true"
	aria-label="<?php esc_attr_e( 'Вибір мови', 'taxi-rental' ); ?>"
	hidden
>
	<div class="tb-lang-popup__overlay" id="tb-lang-popup-overlay" aria-hidden="true"></div>

	<div class="tb-lang-popup__card">
		<button
			class="tb-lang-popup__close"
			id="tb-lang-popup-close"
			aria-label="<?php esc_attr_e( 'Закрити', 'taxi-rental' ); ?>"
		>
			<?php taxi_icon( 'close' ); ?>
		</button>

		<h2 class="tb-lang-popup__title"><?php esc_html_e( 'Мова', 'taxi-rental' ); ?></h2>

		<ul class="tb-lang-popup__list" role="list">
			<?php foreach ( $tb_lang_list as $tb_lang ) :
				$tb_code       = (string) ( $tb_lang['code']        ?? '' );
				$tb_native     = (string) ( $tb_lang['native_name'] ?? $tb_code );
				$tb_translated = $tb_lang_english[ $tb_code ] ?? '';
				$tb_url        = (string) ( $tb_lang['url']         ?? home_url( '/' ) );
				$tb_is_active  = ! empty( $tb_lang['active'] );
				$tb_flag       = $tb_lang_flags[ $tb_code ] ?? '🌐';
			?>
			<li class="tb-lang-popup__item<?php echo $tb_is_active ? ' is-active' : ''; ?>">
				<a
					href="<?php echo esc_url( $tb_url ); ?>"
					class="tb-lang-popup__link"
					<?php echo $tb_is_active ? 'aria-current="true"' : ''; ?>
				>
					<span class="tb-lang-popup__flag" aria-hidden="true"><?php echo esc_html( $tb_flag ); ?></span>
					<span class="tb-lang-popup__names">
						<span class="tb-lang-popup__native"><?php echo esc_html( $tb_native ); ?></span>
						<?php if ( '' !== $tb_translated && strtolower( $tb_translated ) !== strtolower( $tb_native ) ) : ?>
						<span class="tb-lang-popup__translated"><?php echo esc_html( $tb_translated ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>

<?php /* ── Mobile full-screen language panel ──────────────────────────────── */ ?>
<div
	class="tb-lang-screen"
	id="tb-lang-screen"
	role="dialog"
	aria-modal="true"
	aria-label="<?php esc_attr_e( 'Вибір мови', 'taxi-rental' ); ?>"
	aria-hidden="true"
>
	<div class="tb-lang-screen__head">
		<a
			href="<?php echo esc_url( home_url( '/' ) ); ?>"
			class="tb-logo"
			aria-label="TaxiBaza.sk"
		>
			<?php echo tb_logo_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<button
			class="tb-lang-screen__close"
			id="tb-lang-screen-close"
			aria-label="<?php esc_attr_e( 'Закрити', 'taxi-rental' ); ?>"
		>
			<?php taxi_icon( 'close' ); ?>
		</button>
	</div>

	<div class="tb-lang-screen__content">
		<h2 class="tb-lang-screen__title"><?php esc_html_e( 'Мова', 'taxi-rental' ); ?></h2>

		<ul class="tb-lang-screen__list" role="list">
			<?php foreach ( $tb_lang_list as $tb_lang ) :
				$tb_code      = (string) ( $tb_lang['code']        ?? '' );
				$tb_native    = (string) ( $tb_lang['native_name'] ?? $tb_code );
				$tb_url       = (string) ( $tb_lang['url']         ?? home_url( '/' ) );
				$tb_is_active = ! empty( $tb_lang['active'] );
				$tb_flag      = $tb_lang_flags[ $tb_code ] ?? '🌐';
			?>
			<li class="tb-lang-screen__item<?php echo $tb_is_active ? ' is-active' : ''; ?>">
				<a
					href="<?php echo esc_url( $tb_url ); ?>"
					class="tb-lang-screen__link"
					<?php echo $tb_is_active ? 'aria-current="true"' : ''; ?>
				>
					<span class="tb-lang-screen__flag" aria-hidden="true"><?php echo esc_html( $tb_flag ); ?></span>
					<span class="tb-lang-screen__name"><?php echo esc_html( $tb_native ); ?></span>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="tb-lang-screen__footer">
		<button class="tb-lang-screen__close-btn" id="tb-lang-screen-close-btn">
			<?php esc_html_e( 'Закрити', 'taxi-rental' ); ?>
		</button>
	</div>
</div>
