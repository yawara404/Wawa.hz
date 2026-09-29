<?php get_header(); ?>
<main class="site-main wawa-container" id="main-content" tabindex="-1">
  <div class="reading-layout"><div class="reading-main"><?php while (have_posts()) : the_post(); get_template_part('template-parts/content-single'); endwhile; ?></div></div>
</main>
<?php get_footer(); ?>
