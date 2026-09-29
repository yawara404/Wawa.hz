<?php
/** Template Name: Works Gallery ページテンプレート */
get_header();
?>
<main class="site-main wawa-container" id="main-content" tabindex="-1">
  <?php while (have_posts()) : the_post(); if (post_password_required()) : the_content(); else : get_template_part('template-parts/works-gallery'); endif; endwhile; ?>
</main>
<?php get_footer(); ?>
