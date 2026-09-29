<?php get_header(); ?>
<main class="site-main wawa-container" id="main-content" tabindex="-1">
  <?php if (is_home() && !is_paged()) : ?>
    <?php get_template_part('template-parts/home'); ?>
  <?php else : ?>
    <header class="page-heading"><p class="eyebrow">JOURNAL</p><h1><?php esc_html_e('記事一覧', 'wawahz'); ?></h1></header>
    <?php get_template_part('template-parts/post-list'); ?>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
