<?php $search_id = wp_unique_id('wawa-search-'); ?>
<form class="wawa-search-form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
  <label class="screen-reader-text" for="<?php echo esc_attr($search_id); ?>"><?php esc_html_e('検索キーワード', 'wawahz'); ?></label>
  <input id="<?php echo esc_attr($search_id); ?>" type="search" name="s" value="<?php echo esc_attr(get_search_query(false)); ?>" placeholder="<?php esc_attr_e('記事を検索…', 'wawahz'); ?>">
  <button type="submit" class="m3-btn m3-btn-filled" aria-label="<?php esc_attr_e('検索する', 'wawahz'); ?>"><?php echo wawahz_icon('search'); ?></button>
</form>
