<?php
/**
 * Generic page template — legal / text pages (Privacy, Terms).
 *
 * Figma: Privacy 107:3898 / 246:7144, Terms 107:4017 / 246:7255.
 * Layout: sticky table of contents (built from h2) + article content.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$content = apply_filters( 'the_content', get_the_content() );

	// Inject ids into h2 headings and collect them for the TOC.
	$toc     = [];
	$content = preg_replace_callback(
		'/<h2([^>]*)>(.*?)<\/h2>/is',
		function ( $m ) use ( &$toc ) {
			$title = wp_strip_all_tags( $m[2] );
			$id    = sanitize_title( $title );
			$toc[] = [
				'id'    => $id,
				'title' => $title,
			];
			return '<h2' . $m[1] . ' id="' . esc_attr( $id ) . '">' . $m[2] . '</h2>';
		},
		$content
	);

	// "Aktualizované: október 2026" — prefix and month in the page's language.
	// Date: the editable "Dátum aktualizácie" field (side box on the page),
	// otherwise the page's last-modified date. The old free-text custom field
	// tb_updated_label held a Ukrainian text on the Slovak pages, so it is no
	// longer used.
	$updated    = '';
	$updated_ts = 0;
	$picked     = (string) get_post_meta( get_the_ID(), 'tb_updated_date', true );
	if ( preg_match( '/^\d{8}$/', $picked ) ) {
		$picked_date = DateTimeImmutable::createFromFormat( '!Ymd', $picked, wp_timezone() );
		$updated_ts  = $picked_date ? $picked_date->getTimestamp() : 0;
	}
	if ( ! $updated_ts ) {
		$updated_ts = (int) get_post_timestamp( get_the_ID(), 'modified' );
	}
	if ( $updated_ts ) {
		$updated = tb_legal_updated_prefix() . ' ' . tb_date_label( $updated_ts, true );
	}
	?>

<main class="tb-legal">
	<div class="tb-container tb-legal__inner<?php echo $toc ? '' : ' tb-legal__inner--no-toc'; ?>">

		<?php if ( $toc ) : ?>
		<aside class="tb-legal__toc">
			<details class="tb-legal__toc-box" open>
				<summary class="tb-legal__toc-label"><?php esc_html_e( 'Obsah', 'taxi-rental' ); ?></summary>
				<ul class="tb-legal__toc-list">
					<?php foreach ( $toc as $item ) : ?>
					<li>
						<a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
					</li>
					<?php endforeach; ?>
				</ul>
			</details>
		</aside>
		<?php endif; ?>

		<article class="tb-legal__content">
			<h1 class="tb-legal__title"><?php the_title(); ?></h1>
			<?php if ( $updated ) : ?>
			<p class="tb-legal__updated"><?php echo esc_html( $updated ); ?></p>
			<?php endif; ?>
			<div class="tb-legal__body">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered post content. ?>
			</div>
		</article>

	</div>

	<?php get_template_part( 'template-parts/cta-telegram' ); ?>
</main>

	<?php
endwhile;

get_footer();
