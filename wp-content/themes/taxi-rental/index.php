<?php
// Fallback template — WordPress requires index.php in every theme.
get_header();
?>

<main class="tb-container" style="padding-top:48px;padding-bottom:48px;">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			the_title( '<h2>', '</h2>' );
			the_excerpt();
		endwhile;
	else :
		esc_html_e( 'Nič sa nenašlo.', 'taxi-rental' );
	endif;
	?>
</main>

<?php get_footer();
