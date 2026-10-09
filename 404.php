<?php get_header(); ?>
<main class="site-main wawa-container not-found-main" id="main-content" tabindex="-1">
  <section class="not-found-card" aria-labelledby="not-found-title">
    <div class="not-found-visual" aria-hidden="true">
      <span class="not-found-orbit not-found-orbit--outer"></span>
      <span class="not-found-orbit not-found-orbit--inner"></span>
      <span class="not-found-number">404</span>
      <span class="not-found-signal"><?php echo wawahz_icon('wifi_off'); ?> SIGNAL LOST</span>
    </div>

    <div class="not-found-content">
      <p class="not-found-eyebrow"><span aria-hidden="true"></span> 404 / PAGE NOT FOUND</p>
      <h1 id="not-found-title"><?php esc_html_e('ページが見つかりません', 'wawahz'); ?></h1>
      <p class="not-found-description"><?php esc_html_e('リンクが変わったか、ページが移動したようです。キーワードで探すか、ホームからもう一度たどってみてください。', 'wawahz'); ?></p>

      <div class="not-found-search">
        <p><?php esc_html_e('記事を探す', 'wawahz'); ?></p>
        <?php get_search_form(); ?>
      </div>

      <a class="m3-btn m3-btn-tonal not-found-home" href="<?php echo esc_url(home_url('/')); ?>">
        <?php echo wawahz_icon('arrow_back'); ?>
        <?php esc_html_e('ホームへ戻る', 'wawahz'); ?>
      </a>
    </div>
  </section>
</main>
<?php get_footer(); ?>
