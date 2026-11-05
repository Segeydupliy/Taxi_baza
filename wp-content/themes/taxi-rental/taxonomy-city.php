<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$city_term = get_queried_object();

if ( ! ( $city_term instanceof WP_Term ) ) {
	wp_redirect( get_post_type_archive_link( 'car_listing' ) );
	exit;
}

// Parse URL filters but force city to current term slug.
$filters         = \TaxiRental\Core\Queries::parse_filters();
$filters['city'] = $city_term->slug;

$paged = max( 1, (int) ( $_GET['paged'] ?? get_query_var( 'paged', 1 ) ) );

$ctx        = tb_catalog_context( $filters, $paged );
$q          = $ctx['query'];
$base_url   = get_term_link( $city_term );
$base_url   = is_wp_error( $base_url ) ? get_post_type_archive_link( 'car_listing' ) : $base_url;

// The city is already in the URL path, so it must not also ride in the query.
$city_args = $filters;
unset( $city_args['city'] );
$query_args = \TaxiRental\Core\Queries::to_query_args( $city_args );

// City hero image: ACF term meta → fallback static asset.
$city_img_id  = (int) get_term_meta( $city_term->term_id, 'tb_image_id', true );
$city_img_url = $city_img_id ? (string) wp_get_attachment_image_url( $city_img_id, 'full' ) : '';
if ( ! $city_img_url ) {
	$city_img_url = TAXI_THEME_URL . '/assets/img/cities/' . sanitize_file_name( $city_term->slug ) . '.jpg';
}

$city_count = (int) $city_term->count;

get_header();
?>

<main class="tb-city-page">

  <!-- Hero -->
  <section
    class="tb-city-hero"
    style="background-image: url('<?php echo esc_url( $city_img_url ); ?>')"
    aria-label="<?php echo esc_attr( $city_term->name ); ?>"
  >
    <div class="tb-city-hero__inner">
      <h1 class="tb-city-hero__name"><?php echo esc_html( $city_term->name ); ?></h1>
      <p class="tb-city-hero__count">
        <?php
        if ( $city_count > 0 ) {
          printf(
            esc_html( tb_opt( 'tb_cat_city_count', __( 'Доступно %d авто', 'taxi-rental' ) ) ),
            $city_count
          );
        } else {
          echo esc_html( tb_opt( 'tb_cat_city_empty', __( 'Поки немає оголошень', 'taxi-rental' ) ) );
        }
        ?>
      </p>
    </div>
  </section>

  <!-- Catalog -->
  <div class="tb-container">
    <div class="tb-city-catalog">

      <div
        data-tb-catalog
        data-base="<?php echo esc_url( $base_url ); ?>"
        data-nonce="<?php echo esc_attr( wp_create_nonce( 'tb_filter_listings' ) ); ?>"
        data-city="<?php echo esc_attr( $city_term->slug ); ?>"
      >

        <?php // The city hero above already shows a count ("Доступно N авто", $city_term->count) — this second, AJAX-updated count duplicated it. catalog.js's `if (countEl)` guard means it's safe to just not render the element here rather than hide it with CSS. ?>

        <div data-tb-filters>
          <?php get_template_part( 'template-parts/catalog/filters-bar', null, $ctx ); ?>
        </div>

        <div data-tb-results>
          <?php
          get_template_part(
              'template-parts/catalog/results',
              null,
              [
                  'query'           => $q,
                  'paged'           => $paged,
                  'base_url'        => $base_url,
                  'query_args'      => $query_args,
                  'load_more_class' => 'tb-btn--dark',
              ]
          );
          ?>
        </div>

      </div>

    </div><!-- /.tb-city-catalog -->
  </div>

  <!-- Telegram CTA -->
  <section class="tb-cta-tg">
    <div class="tb-container">
      <div class="tb-cta-tg__inner">
        <div class="tb-cta-tg__content">
          <span class="tb-cta-tg__label"><?php echo esc_html( tb_opt( 'tb_cta_city_label', 'Telegram' ) ); ?></span>
          <h2 class="tb-cta-tg__title"><?php echo esc_html( tb_opt( 'tb_cta_city_title', __( 'Нові оголошення у вашому місті', 'taxi-rental' ) ) ); ?></h2>
          <p class="tb-cta-tg__desc"><?php echo esc_html( tb_opt( 'tb_cta_city_sub', __( 'Підписуйтесь — отримуйте нові авто першими', 'taxi-rental' ) ) ); ?></p>
        </div>
        <a href="<?php echo esc_url( tb_telegram_url() ); ?>" target="_blank" rel="noopener noreferrer" class="tb-btn tb-btn--dark tb-cta-tg__btn">
          <?php echo esc_html( tb_opt( 'tb_cta_city_button', __( 'Підписатись у Telegram', 'taxi-rental' ) ) ); ?>
        </a>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
