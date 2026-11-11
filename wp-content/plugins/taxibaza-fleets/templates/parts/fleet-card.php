<?php
/**
 * One fleet card of the catalogue (Figma: "Fleet card", 896:8229).
 *
 * Expects: $tbf_card (Directory::card()).
 *
 * @package TaxiBaza\Fleets
 */

use TaxiBaza\Fleets\Directory;
use TaxiBaza\Fleets\Icons;
use function TaxiBaza\Fleets\t;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$c    = (array) $tbf_card;
$city = Directory::city_line( $c );
$head = $c['headline'];
$desc = Directory::excerpt( (string) $c['desc'] );
?>
<article class="tbf-fc">
	<div class="tbf-fc__top">
		<a class="tbf-logo" href="<?php echo esc_url( $c['url'] ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( '' !== $c['logo'] ) : ?>
				<img src="<?php echo esc_url( $c['logo'] ); ?>" alt="" loading="lazy" width="96" height="96">
			<?php else : ?>
				<span class="tbf-logo__txt"><?php echo esc_html( $c['initials'] ); ?></span>
			<?php endif; ?>
		</a>
		<div class="tbf-fc__id">
			<div class="tbf-fc__name-row">
				<h2 class="tbf-fc__name"><a href="<?php echo esc_url( $c['url'] ); ?>"><?php echo esc_html( $c['name'] ); ?></a></h2>
				<span class="tbf-verify" title="<?php echo esc_attr( t( 'fl_verified' ) ); ?>"><?php Icons::out( 'verify', 24 ); ?><span class="screen-reader-text"><?php echo esc_html( t( 'fl_verified' ) ); ?></span></span>
			</div>
			<?php if ( '' !== $city ) : ?>
				<p class="tbf-fc__city"><?php Icons::out( 'location', 20 ); ?><span><?php echo esc_html( $city ); ?></span></p>
			<?php endif; ?>
			<?php if ( $c['platforms'] ) : ?>
				<div class="tbf-plats">
					<?php foreach ( $c['platforms'] as $pl ) : ?>
						<span class="tbf-plat tbf-plat--<?php echo esc_attr( $pl ); ?>"><?php echo esc_html( Directory::PLATFORMS[ $pl ] ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( $head ) : ?>
			<div class="tbf-fc__price">
				<span class="tbf-fc__price-l"><?php echo esc_html( $head['label'] ); ?></span>
				<span class="tbf-fc__price-v"><?php echo esc_html( $head['value'] ); ?></span>
				<span class="tbf-fc__price-u"><?php echo esc_html( $head['unit'] ); ?></span>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( '' !== $desc ) : ?>
		<p class="tbf-fc__desc"><?php echo esc_html( $desc ); ?></p>
	<?php endif; ?>

	<?php if ( $c['features'] ) : ?>
		<ul class="tbf-chips" role="list">
			<?php foreach ( $c['features'] as $f ) : ?>
				<li class="tbf-chip"><?php Icons::out( $f['icon'], 18 ); ?><?php echo esc_html( $f['text'] ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="tbf-fc__foot">
		<?php if ( $c['langs'] ) : ?>
			<ul class="tbf-langs" role="list" aria-label="<?php echo esc_attr( t( 'fl_langs' ) ); ?>">
				<?php foreach ( $c['langs'] as $l ) : ?>
					<li class="tbf-lang" title="<?php echo esc_attr( t( 'lang_' . $l ) ); ?>"><?php echo esc_html( Directory::lang_label( (string) $l ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<span></span>
		<?php endif; ?>
		<div class="tbf-fc__btns">
			<a class="tbf-btn tbf-btn--outline" href="<?php echo esc_url( $c['url'] . '#tbf-cars' ); ?>"><?php echo esc_html( t( 'fl_btn_cars' ) ); ?></a>
			<button type="button" class="tbf-btn tbf-btn--accent" data-tbf-apply data-fleet="<?php echo esc_attr( (string) $c['id'] ); ?>" data-name="<?php echo esc_attr( $c['name'] ); ?>" data-models="<?php echo esc_attr( implode( ',', $c['models'] ) ); ?>"><?php echo esc_html( t( 'fl_btn_work' ) ); ?></button>
		</div>
	</div>
</article>
