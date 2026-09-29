<?php get_header(); ?>
<main class="site-main wawa-container" id="main-content" tabindex="-1">
  <div class="reading-topbar">
    <a class="m3-corner-nav-btn reading-back-btn" id="reading-back-btn" href="<?php echo esc_url(wawahz_view_url('category')); ?>" aria-label="<?php esc_attr_e('前の画面に戻る', 'wawahz'); ?>" title="<?php esc_attr_e('前の画面に戻る', 'wawahz'); ?>">
      <span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
    </a>
    <nav class="breadcrumbs" aria-label="<?php esc_attr_e('パンくずリスト', 'wawahz'); ?>"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a><span aria-hidden="true">/</span><a href="<?php echo esc_url(wawahz_view_url('category')); ?>"><?php esc_html_e('記事一覧', 'wawahz'); ?></a></nav>
  </div>
  <div class="reading-layout">
    <div class="reading-main">
      <?php while (have_posts()) : the_post();
        get_template_part('template-parts/content-single');
      endwhile; ?>
    </div>
  </div>
</main>
<?php get_footer(); ?>