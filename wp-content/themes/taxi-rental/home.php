<?php
/**
 * Blog posts index — /blog/.
 *
 * Figma: desktop 119:5429, mobile 246:6979.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
get_template_part( 'template-parts/blog/archive-layout' );
get_footer();
