<?php
/**
 * Site footer wrapper — called by get_footer().
 * Renders parts/footer, mobile nav drawer, then closes <body></html>.
 */
?>
<?php get_template_part( 'parts/footer' ); ?>
<?php get_template_part( 'parts/mobile-nav' ); ?>
<?php wp_footer(); ?>
</body>
</html>
