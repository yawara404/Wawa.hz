<?php
$card_post = get_post($args['post'] ?? get_the_ID());
if (!$card_post) { return; }
$card_categories = get_the_category($card_post->ID);
$compact = !empty($args['compact']);
$heading = !empty($args['home']) ? 'h3' : 'h2';
?>
<article class="post-card <?php echo $compact ? 'post-card-compact' : ''; ?>">
  <a class="post-card-link" href="<?php echo esc_url(get_permalink($card_post)); ?>">
    <div class="post-card-media"><?php wawahz_card_image($card_post->ID); ?></div>
    <div class="post-card-content">
      <div class="post-card-meta"><span><?php echo esc_html($card_categories[0]->name ?? ($card_post->post_type === 'page' ? __('固定ページ', 'wawahz') : __('記事', 'wawahz'))); ?></span><time datetime="<?php echo esc_attr(get_the_date('c', $card_post)); ?>"><?php echo esc_html(get_the_date('Y.m.d', $card_post)); ?></time></div>
      <<?php echo $heading; ?>><?php echo esc_html(wawahz_post_title($card_post)); ?></<?php echo $heading; ?>>
      <p><?php echo esc_html(wp_trim_words(wp_strip_all_tags(get_the_excerpt($card_post)), 24)); ?></p>
      <span class="post-card-arrow" aria-hidden="true"><?php echo wawahz_icon('arrow_outward'); ?></span>
    </div>
  </a>
</article>
