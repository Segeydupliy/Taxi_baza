<?php
/**
 * Front page — all texts editable via ACF (page "Головна" + TaxiBaza → Контент сайту).
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Cities grid (top 10 by cars): same cities in every language.
$home_city_cards = tb_city_cards( 'count', 10 );
// Ride categories grouped under Bolt / Uber parent terms.
$bolt_cat_parent = get_term_by( 'slug', 'bolt', 'ride_category' );
$uber_cat_parent = get_term_by( 'slug', 'uber', 'ride_category' );

$bolt_cats = ( $bolt_cat_parent instanceof WP_Term )
	? get_terms( [ 'taxonomy' => 'ride_category', 'parent' => $bolt_cat_parent->term_id, 'hide_empty' => false ] )
	: [];

$uber_cats = ( $uber_cat_parent instanceof WP_Term )
	? get_terms( [ 'taxonomy' => 'ride_category', 'parent' => $uber_cat_parent->term_id, 'hide_empty' => false ] )
	: [];

// Latest 4 published listings.
$latest_q = new WP_Query( [
	'post_type'      => 'car_listing',
	'post_status'    => 'publish',
	'posts_per_page' => 8,
	'orderby'        => 'date',
	'order'          => 'DESC',
] );

$catalog_url  = get_post_type_archive_link( 'car_listing' );
$cities_url   = home_url( '/mista/' );
$about_url    = home_url( '/pro-nas/' );
$login_url    = tb_url( '/login/' );
$rent_out_url = add_query_arg( 'action', 'register', $login_url );

// Hero image — ACF override with theme asset fallback.
$hero_img_id  = tb_field( 'tb_hero_image', 0 );
$hero_img_url = $hero_img_id ? wp_get_attachment_image_url( (int) $hero_img_id, 'full' ) : '';
if ( ! $hero_img_url ) {
	$hero_img_url = TAXI_THEME_URL . '/assets/img/hero-car.jpg';
}

// About image — ACF override with theme asset fallback.
$about_img_id  = tb_field( 'tb_about_image', 0 );
$about_img_url = $about_img_id ? wp_get_attachment_image_url( (int) $about_img_id, 'large' ) : '';
if ( ! $about_img_url ) {
	$about_img_url = TAXI_THEME_URL . '/assets/img/about-car.jpg';
}

// About accordion items — ACF repeater with Figma defaults.
$about_defaults = [
	[
		'title'       => __( 'Про сервіс', 'taxi-rental' ),
		'text'        => __( "TaxiBaza.sk об'єднує власників автомобілів та водіїв рідшеру у Словаччині. Ми забезпечуємо прозорі умови, перевірені оголошення та пряме спілкування — без зайвих комісій.", 'taxi-rental' ),
		'button_text' => __( 'Дізнатись більше', 'taxi-rental' ),
		'button_url'  => $about_url,
	],
	[
		'title'       => __( 'Переваги для водіїв', 'taxi-rental' ),
		'text'        => __( 'Широкий вибір авто для Bolt і Uber. Прямий контакт з власником — без посередників і прихованих комісій.', 'taxi-rental' ),
		'button_text' => __( 'Переглянути каталог', 'taxi-rental' ),
		'button_url'  => $catalog_url,
	],
	[
		'title'       => __( 'Переваги для власників', 'taxi-rental' ),
		'text'        => __( 'Здайте авто перевіреним водіям. Безкоштовне оголошення, захищені умови оренди та підтримка 24/7.', 'taxi-rental' ),
		'button_text' => __( 'Здати авто', 'taxi-rental' ),
		'button_url'  => $rent_out_url,
	],
];

$about_items = tb_field( 'tb_about_items', [] );
if ( ! is_array( $about_items ) || empty( $about_items ) ) {
	$about_items = $about_defaults;
}

get_header();
?>

<main>

  <!-- ================================================================ -->
  <!-- Hero                                                             -->
  <!-- ================================================================ -->
  <section class="tb-hero">
    <div class="tb-hero__bg" aria-hidden="true">
      <img
        src="<?php echo esc_url( $hero_img_url ); ?>"
        alt=""
        class="tb-hero__bg-car"
      />
      <img
        src="<?php echo esc_url( TAXI_THEME_URL . '/assets/img/hero-overlay.jpg' ); ?>"
        alt=""
        class="tb-hero__bg-overlay"
      />
    </div>

    <div class="tb-container tb-hero__inner">
      <h1 class="tb-hero__headline">
        <?php echo tb_nl2br( tb_field( 'tb_hero_title', __( "З'єднуємо власників\nавто з водіями по всій\nСловаччині.", 'taxi-rental' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in tb_nl2br(). ?>
      </h1>

      <div class="tb-hero__card">
        <p class="tb-hero__card-text">
          <?php echo esc_html( tb_field( 'tb_hero_text', __( 'Знайдіть автомобіль для роботи на Bolt або Uber, або здайте свій автомобіль у надійні руки.', 'taxi-rental' ) ) ); ?>
        </p>
        <div class="tb-hero__card-cta">
          <a href="<?php echo esc_url( $rent_out_url ); ?>" class="tb-btn tb-btn--primary">
            <?php echo esc_html( tb_field( 'tb_hero_btn_owner', __( 'Здати в оренду', 'taxi-rental' ) ) ); ?>
          </a>
          <a href="<?php echo esc_url( $catalog_url ); ?>" class="tb-btn tb-btn--secondary">
            <?php echo esc_html( tb_field( 'tb_hero_btn_driver', __( 'Взяти в оренду', 'taxi-rental' ) ) ); ?>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ================================================================ -->
  <!-- About                                                            -->
  <!-- ================================================================ -->
  <section class="tb-about">
    <div class="tb-container">
      <div class="tb-about__layout">

          <div class="tb-about__head">
            <p class="tb-about__kicker"><?php echo esc_html( tb_field( 'tb_about_kicker', 'TaxiBaza.sk' ) ); ?></p>
            <h2 class="tb-about__title">
              <?php echo tb_nl2br( tb_field( 'tb_about_title', __( "Розумна платформа\nдля оренди авто", 'taxi-rental' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in tb_nl2br(). ?>
            </h2>
          </div>

          <div class="tb-about__accordion" data-accordion>

            <?php foreach ( $about_items as $i => $item ) :
              $is_first = ( 0 === $i );
              $btn_text = $item['button_text'] ?? '';
              $btn_url  = ! empty( $item['button_url'] ) ? $item['button_url'] : $catalog_url;
            ?>
            <div class="tb-about__item<?php echo $is_first ? ' is-expanded' : ''; ?>">
              <button
                class="tb-about__item-toggle"
                type="button"
                aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>"
                data-accordion-toggle
              >
                <span><?php echo esc_html( $item['title'] ?? '' ); ?></span>
                <svg class="tb-about__chevron" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                  <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <div class="tb-about__item-body-wrap">
                <div class="tb-about__item-body">
                  <?php if ( $is_first ) : ?>
                  <div class="tb-about__img-mobile" aria-hidden="true">
                    <img
                      src="<?php echo esc_url( $about_img_url ); ?>"
                      alt=""
                      loading="lazy"
                    />
                  </div>
                  <?php endif; ?>
                  <p>
                    <?php echo esc_html( $item['text'] ?? '' ); ?>
                  </p>
                  <?php if ( $btn_text ) : ?>
                  <a href="<?php echo esc_url( $btn_url ); ?>" class="tb-btn tb-btn--primary">
                    <?php echo esc_html( $btn_text ); ?>
                  </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>

          </div>

          <div class="tb-about__right">
          <div class="tb-about__img-wrap">
            <img
              src="<?php echo esc_url( $about_img_url ); ?>"
              alt="<?php esc_attr_e( 'Оренда авто для таксі в Словаччині', 'taxi-rental' ); ?>"
              class="tb-about__img"
              loading="lazy"
              width="560"
              height="660"
            />
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ================================================================ -->
  <!-- Dark Section: Cities + Categories                                -->
  <!-- ================================================================ -->
  <section class="tb-dark-section">
    <div class="tb-container tb-dark-section__inner">

      <!-- Cities -->
      <div class="tb-home-cities">
        <div class="tb-home-cities__head">
          <div>
            <p class="tb-section-kicker"><?php echo esc_html( tb_field( 'tb_cities_kicker', __( 'Міста', 'taxi-rental' ) ) ); ?></p>
            <h2 class="tb-section-title tb-section-title--white">
              <?php echo tb_nl2br( tb_field( 'tb_cities_title', __( "Знайди авто у\nсвоєму місті", 'taxi-rental' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in tb_nl2br(). ?>
            </h2>
          </div>
          <a href="<?php echo esc_url( $cities_url ); ?>" class="tb-btn tb-btn--primary">
            <?php echo esc_html( tb_field( 'tb_cities_button', __( 'Дивитись всі міста', 'taxi-rental' ) ) ); ?>
          </a>
        </div>

        <?php if ( ! empty( $home_city_cards ) ) : ?>
        <div class="tb-photo-city-grid">
          <?php foreach ( $home_city_cards as $cc ) :
            $ct         = $cc['term'];
            $ct_img_url = $cc['img'];
            $ct_link    = '' !== $cc['link'] ? $cc['link'] : new WP_Error();
            $ct_count   = $cc['count'];
          ?>
          <a
            href="<?php echo is_wp_error( $ct_link ) ? '#' : esc_url( $ct_link ); ?>"
            class="tb-photo-city-card"
            style="background-image: url('<?php echo esc_url( $ct_img_url ); ?>')"
          >
            <div class="tb-photo-city-card__gradient"></div>
            <div class="tb-photo-city-card__info">
              <div>
                <p class="tb-photo-city-card__name"><?php echo esc_html( $ct->name ); ?></p>
                <p class="tb-photo-city-card__count">
                  <?php
                  /* translators: %d car count */
                  printf( esc_html( _n( '%d авто', '%d авто', $ct_count, 'taxi-rental' ) ), $ct_count );
                  ?>
                </p>
              </div>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="rgba(255,255,255,0.6)"/>
              </svg>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Categories -->
      <div class="tb-home-cats">
        <div class="tb-home-cats__head">
          <p class="tb-section-kicker"><?php echo esc_html( tb_field( 'tb_cats_kicker', __( 'Категорії', 'taxi-rental' ) ) ); ?></p>
          <h2 class="tb-section-title tb-section-title--white">
            <?php echo tb_nl2br( tb_field( 'tb_cats_title', __( "Підбери авто\nпотрібної категорії", 'taxi-rental' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in tb_nl2br(). ?>
          </h2>
        </div>

        <?php
        $bolt_label = tb_field( 'tb_cats_bolt_label', __( 'Категорії Bolt', 'taxi-rental' ) );
        $uber_label = tb_field( 'tb_cats_uber_label', __( 'Категорії Uber', 'taxi-rental' ) );
        ?>
        <?php if ( ! empty( $bolt_cats ) || ! empty( $uber_cats ) ) : ?>
        <div class="tb-home-cats__groups">

          <?php if ( ! empty( $bolt_cats ) && ! is_wp_error( $bolt_cats ) ) : ?>
          <div class="tb-home-cats__group">
            <p class="tb-home-cats__group-label"><?php echo esc_html( $bolt_label ); ?></p>
            <div class="tb-home-cats__pills">
              <?php foreach ( $bolt_cats as $cat ) : ?>
              <a
                href="<?php echo esc_url( add_query_arg( [ 'cat' => $cat->slug ], $catalog_url ) ); ?>"
                class="tb-category-pill"
              ><?php echo esc_html( $cat->name ); ?></a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ( ! empty( $uber_cats ) && ! is_wp_error( $uber_cats ) ) : ?>
          <div class="tb-home-cats__group">
            <p class="tb-home-cats__group-label"><?php echo esc_html( $uber_label ); ?></p>
            <div class="tb-home-cats__pills">
              <?php foreach ( $uber_cats as $cat ) : ?>
              <a
                href="<?php echo esc_url( add_query_arg( [ 'cat' => $cat->slug ], $catalog_url ) ); ?>"
                class="tb-category-pill"
              ><?php echo esc_html( $cat->name ); ?></a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

        </div>
        <?php else : ?>
        <!-- Fallback: static pills if taxonomy not seeded yet -->
        <div class="tb-home-cats__groups">
          <div class="tb-home-cats__group">
            <p class="tb-home-cats__group-label"><?php echo esc_html( $bolt_label ); ?></p>
            <div class="tb-home-cats__pills">
              <?php
              $bolt_fallback = [ 'Bolt', 'Comfort', 'Green', 'XL', 'Pets', 'Premium', 'Luxury' ];
              foreach ( $bolt_fallback as $name ) : ?>
              <a href="<?php echo esc_url( $catalog_url ); ?>" class="tb-category-pill"><?php echo esc_html( $name ); ?></a>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="tb-home-cats__group">
            <p class="tb-home-cats__group-label"><?php echo esc_html( $uber_label ); ?></p>
            <div class="tb-home-cats__pills">
              <?php
              $uber_fallback = [ 'UberX', 'Uber Comfort', 'Uber XL' ];
              foreach ( $uber_fallback as $name ) : ?>
              <a href="<?php echo esc_url( $catalog_url ); ?>" class="tb-category-pill"><?php echo esc_html( $name ); ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </section>

  <!-- ================================================================ -->
  <!-- Latest Listings                                                  -->
  <!-- ================================================================ -->
  <section class="tb-latest-listings">
    <div class="tb-container">

      <?php $listings_btn = tb_field( 'tb_listings_button', __( 'Весь каталог авто', 'taxi-rental' ) ); ?>
      <div class="tb-latest-listings__head">
        <div>
          <p class="tb-section-kicker tb-section-kicker--green"><?php echo esc_html( tb_field( 'tb_listings_kicker', __( 'Автомобілі', 'taxi-rental' ) ) ); ?></p>
          <h2 class="tb-section-title">
            <?php echo tb_nl2br( tb_field( 'tb_listings_title', __( "Актуальні оголошення\nдля оренди авто", 'taxi-rental' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in tb_nl2br(). ?>
          </h2>
        </div>
        <a href="<?php echo esc_url( $catalog_url ); ?>" class="tb-btn tb-btn--primary">
          <?php echo esc_html( $listings_btn ); ?>
        </a>
      </div>

      <?php if ( $latest_q->have_posts() ) : ?>
      <div class="swiper tb-listings-swiper">
        <div class="swiper-wrapper">
          <?php while ( $latest_q->have_posts() ) : $latest_q->the_post(); ?>
            <div class="swiper-slide">
              <?php get_template_part( 'template-parts/catalog/card-car' ); ?>
            </div>
          <?php endwhile; wp_reset_postdata(); ?>
        </div>
      </div>

      <div class="tb-listings-nav">
        <div class="tb-listings-dots"></div>
        <div class="tb-listings-arrows">
          <button type="button" class="tb-listings-arrow tb-listings-prev" aria-label="<?php esc_attr_e( 'Попередні', 'taxi-rental' ); ?>">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12.5 5L7.5 10L12.5 15" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <button type="button" class="tb-listings-arrow tb-listings-next" aria-label="<?php esc_attr_e( 'Наступні', 'taxi-rental' ); ?>">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7.5 5L12.5 10L7.5 15" stroke="#000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>

      <a href="<?php echo esc_url( $catalog_url ); ?>" class="tb-btn tb-btn--primary tb-listings-mobile-catalog">
        <?php echo esc_html( $listings_btn ); ?>
      </a>

      <?php else : ?>
      <p class="tb-latest-listings__empty">
        <?php esc_html_e( 'Оголошень ще немає.', 'taxi-rental' ); ?>
        <a href="<?php echo esc_url( $rent_out_url ); ?>"><?php esc_html_e( 'Додайте перше!', 'taxi-rental' ); ?></a>
      </p>
      <?php endif; ?>

    </div>
  </section>

  <!-- ================================================================ -->
  <!-- Telegram CTA (texts: TaxiBaza → Контент сайту)                   -->
  <!-- ================================================================ -->
  <?php get_template_part( 'template-parts/cta-telegram', null, [ 'btn_class' => 'tb-btn--secondary' ] ); ?>

</main>

<?php get_footer(); ?>
