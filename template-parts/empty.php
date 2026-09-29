<div class="empty-state">
  <?php echo wawahz_icon('article'); ?>
  <h2><?php echo esc_html($args['title'] ?? __('記事が見つかりませんでした', 'wawahz')); ?></h2>
  <p><?php echo esc_html($args['message'] ?? __('カテゴリーや検索キーワードを変えてお試しください。', 'wawahz')); ?></p>
  <a class="m3-btn m3-btn-tonal" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('ホームへ戻る', 'wawahz'); ?></a>
</div>
