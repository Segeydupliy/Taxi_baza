<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Same cities in every language (see tb_city_cards()).
$city_cards = tb_city_cards( 'name' );

get_header();
?>

<main class="tb-cities-page">
  <div class="tb-container">

    <div class="tb-cities-page__head">
      <h1 class="tb-cities-page__title"><?php echo esc_html( tb_field( 'tb_mp_title', __( 'Знайди авто у своєму місті', 'taxi-rental' ) ) ); ?></h1>
      <p class="tb-cities-page__sub">
        <?php echo esc_html( tb_field( 'tb_mp_sub', __( 'Оберіть місто, щоб побачити доступні авто для роботи в таксі.', 'taxi-rental' ) ) ); ?>
      </p>
    </div>

    <?php if ( $city_cards ) : ?>

    <div class="tb-photo-city-grid">
      <?php foreach ( $city_cards as $cc ) :
        $term      = $cc['term'];
        $count     = $cc['count'];
        $img_url   = $cc['img'];
        $term_link = '' !== $cc['link'] ? $cc['link'] : new WP_Error();
      ?>
      <a
        href="<?php echo is_wp_error( $term_link ) ? '#' : esc_url( $term_link ); ?>"
        class="tb-photo-city-card"
        style="background-image: url('<?php echo esc_url( $img_url ); ?>')"
      >
        <div class="tb-photo-city-card__gradient"></div>
        <div class="tb-photo-city-card__info">
          <div>
            <p class="tb-photo-city-card__name"><?php echo esc_html( $term->name ); ?></p>
            <p class="tb-photo-city-card__count">
              <?php
              if ( 0 === $count ) {
                echo esc_html( tb_opt( 'tb_cat_mista_zero', __( '0 авто', 'taxi-rental' ) ) );
              } else {
                /* translators: %d car count */
                printf( esc_html( _n( '%d авто', '%d авто', $count, 'taxi-rental' ) ), $count );
              }
              ?>
            </p>
          </div>
          <?php // #90 — same pin the home-page tile carries; it was only ever missing here. ?>
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="rgba(255,255,255,0.6)"/>
          </svg>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <?php else : ?>

    <p class="tb-cities-page__empty"><?php echo esc_html( tb_opt( 'tb_cat_mista_empty', __( 'Міста ще не додані.', 'taxi-rental' ) ) ); ?></p>

    <?php endif; ?>

  </div>

  <section class="tb-cta-tg">
    <div class="tb-container">
      <div class="tb-cta-tg__inner">
        <div class="tb-cta-tg__content">
          <span class="tb-cta-tg__label"><?php echo esc_html( tb_field( 'tb_mp_cta_label', 'Telegram' ) ); ?></span>
          <h2 class="tb-cta-tg__title"><?php echo esc_html( tb_field( 'tb_mp_cta_title', __( 'Нові оголошення у вашому місті', 'taxi-rental' ) ) ); ?></h2>
          <p class="tb-cta-tg__desc"><?php echo esc_html( tb_field( 'tb_mp_cta_sub', __( 'Підписуйтесь — отримуйте нові авто першими', 'taxi-rental' ) ) ); ?></p>
        </div>
        <a href="<?php echo esc_url( tb_telegram_url() ); ?>" target="_blank" rel="noopener noreferrer" class="tb-btn tb-btn--dark tb-cta-tg__btn">
          <?php echo esc_html( tb_field( 'tb_mp_cta_button', __( 'Підписатись у Telegram', 'taxi-rental' ) ) ); ?>
        </a>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
