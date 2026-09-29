<?php
get_header();
$parts = array(
  'category'   => 'category-screen',
  'post'       => 'post-screen',
  'gallery'    => 'works-gallery',
  'nowplaying' => 'nowplaying-gallery',
  'info'       => 'info-screen',
);
?>
<main class="site-main wawa-container" id="main-content" tabindex="-1">
  <?php get_template_part('template-parts/' . $parts[wawahz_view()], null, array('standalone' => true)); ?>
</main>
<?php get_footer(); ?>
