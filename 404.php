<?php get_header(); ?>
<main class="site-main wawa-container" id="main-content" tabindex="-1">
  <header class="page-heading"><p class="eyebrow">404 / NOT FOUND</p><h1><?php esc_html_e('ページが見つかりません', 'wawahz'); ?></h1><p><?php esc_html_e('URLをご確認いただくか、記事を検索してください。', 'wawahz'); ?></p></header>
  <?php get_search_form(); ?>
  <p class="back-home"><a class="m3-btn m3-btn-tonal" href="<?php echo esc_url(home_url('/')); ?>"><?php echo wawahz_icon('home'); ?><?php esc_html_e('ホームへ戻る', 'wawahz'); ?></a></p>
</main>
<?php get_footer(); ?>
