<?php
/**
 * Category archive — same layout as the blog index.
 *
 * @package TaxiRental
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
get_template_part( 'template-parts/blog/archive-layout' );
get_footer();
