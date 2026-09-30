<?php
/** Template Name: Profile ページテンプレート */
get_header();
?>
<main class="site-main wawa-container" id="main-content" tabindex="-1">
  <?php get_template_part('template-parts/profile-screen', null, array('show_content' => true)); ?>
</main>
<?php get_footer(); ?>
