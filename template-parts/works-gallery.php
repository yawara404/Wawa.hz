<?php
/**
 * template-parts/works-gallery.php
 * 静的版 screen-gallery に完全準拠した作品記事ギャラリー。
 */
// ダミーの固定カテゴリ (Hardware / Audio / Photo / Design / Art / Craft) は廃止。
// 「work」カテゴリの作品をまとめて表示する。
$filter_terms = wawahz_work_filter_terms();
$selected_filter = wawahz_resolve_work_filter(wawahz_request_value('filter', 'all'));
$works_query = wawahz_gallery_query($selected_filter, wawahz_request_value('gallery_page', '1'));
$current_page = (int) $works_query->get('paged');
$total_pages = max(1, (int) $works_query->max_num_pages);
$gallery_url = wawahz_view_url('gallery');
$page_url = function ($page) use ($gallery_url, $selected_filter) {
  return add_query_arg(array('filter' => $selected_filter, 'gallery_page' => $page), $gallery_url);
};
// カテゴリソートのチップは常に1ページ目へ戻す (gallery_page を引き継がない)。
$filter_url = function ($filter) use ($gallery_url) {
  return $filter === 'all' ? $gallery_url : add_query_arg('filter', $filter, $gallery_url);
};
// グリッドは常に4枠 (デスクトップは2列×2行)。ページ内の作品が4件に満たない場合は
// 「近日公開」カードで埋め、作品が無い場合も空のグリッドにせず4枚表示する。
$grid_slots = 4;
$work_count = (int) $works_query->post_count;
$placeholder_count = max(0, $grid_slots - $work_count);
?>
<section class="screen-view active works-screen-view" id="screen-gallery" aria-label="<?php esc_attr_e('作品ギャラリー', 'wawahz'); ?>">
  <div class="works-gallery-container">
    <!-- 作品画面ヘッダー -->
    <div class="works-header">
      <div class="works-header-text">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span class="material-symbols-rounded screen-heading-icon" style="color: var(--md-sys-color-primary); font-size: 28px;">palette</span>
          <h2 class="headline-medium screen-heading-text" style="font-weight: 700; color: var(--md-sys-color-primary); margin: 0;">Works &amp; Creations</h2>
        </div>
        <p class="body-medium" style="color: var(--md-sys-color-on-surface-variant); margin-top: 4px;">
          <?php esc_html_e('自作キーボード、Web Audio、モノクロ写真、UIシステム。思索と触感を形にした制作物アーカイブ。', 'wawahz'); ?>
        </p>
      </div>

      <!-- カテゴリフィルタ (見出しの右上): 作品に存在する分類だけを、並び替えと同じドロップダウンで選ぶ -->
      <div class="m3-sort-dropdown-wrapper works-filter-dropdown" id="works-filter-dropdown">
        <span class="material-symbols-rounded sort-icon" aria-hidden="true">filter_list</span>
        <select class="m3-sort-select" id="works-filter-select" aria-label="<?php esc_attr_e('作品カテゴリで絞り込む', 'wawahz'); ?>" onchange="location.href = this.value;">
          <option value="<?php echo esc_url($filter_url('all')); ?>" <?php selected($selected_filter, 'all'); ?>><?php esc_html_e('All', 'wawahz'); ?></option>
          <?php foreach ($filter_terms as $term_name) : ?>
            <option value="<?php echo esc_url($filter_url($term_name)); ?>" <?php selected($selected_filter, $term_name); ?>><?php echo esc_html($term_name); ?></option>
          <?php endforeach; ?>
        </select>
        <span class="material-symbols-rounded sort-arrow" aria-hidden="true">arrow_drop_down</span>
      </div>
    </div>

    <!-- 作品カードグリッド -->
    <div class="works-grid" id="works-cards-grid">
      <?php if ($works_query->have_posts()) : ?>
        <?php while ($works_query->have_posts()) : $works_query->the_post();
          $post_id = get_the_ID();
          $work_cat = wawahz_work_category($post_id);
          $work_year = get_post_meta($post_id, 'wawahz_work_year', true) ?: get_the_date('Y');
          $thumb_url = get_the_post_thumbnail_url($post_id, 'large') ?: get_template_directory_uri() . '/images/work_design.svg?v=20250925_02';
          $summary = wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 20);
          $tags = get_the_tags();
          $tag_names = $tags ? wp_list_pluck($tags, 'name') : array();
          $content_full = wp_strip_all_tags(get_the_content());
        ?>
          <article class="work-card"
                   data-id="<?php echo esc_attr($post_id); ?>"
                   data-category="<?php echo esc_attr($work_cat); ?>"
                   data-year="<?php echo esc_attr($work_year); ?>"
                   data-title="<?php echo esc_attr(get_the_title()); ?>"
                   data-summary="<?php echo esc_attr($summary); ?>"
                   data-description="<?php echo esc_attr($content_full); ?>"
                   data-cover="<?php echo esc_url($thumb_url); ?>"
                   data-tags="<?php echo esc_attr(implode(',', $tag_names)); ?>"
                   data-permalink="<?php echo esc_url(get_permalink()); ?>"
                   tabindex="0"
                   role="button"
                   aria-label="<?php echo esc_attr(get_the_title() . ' の詳細を見る'); ?>"
                   style="--card-bg-img: url('<?php echo esc_url($thumb_url); ?>');">
            <div class="work-card-glass">
              <div class="work-card-top-row">
                <span class="work-badge-cat"><?php echo esc_html($work_cat); ?></span>
                <span class="work-badge-year"><?php echo esc_html($work_year); ?></span>
              </div>
              <div class="work-card-body">
                <h3 class="work-card-title"><?php the_title(); ?></h3>
                <?php if ($summary) : ?>
                  <p class="work-card-summary"><?php echo esc_html($summary); ?></p>
                <?php endif; ?>
                <?php if ($tag_names) : ?>
                  <div class="work-card-tags">
                    <?php foreach ($tag_names as $tag_name) : ?>
                      <span class="work-tag">#<?php echo esc_html($tag_name); ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="work-card-footer">
                <button type="button" class="m3-btn m3-btn-tonal work-detail-action-btn" data-id="<?php echo esc_attr($post_id); ?>">
                  <span><?php esc_html_e('詳細を見る', 'wawahz'); ?></span>
                  <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 16px;">arrow_forward</span>
                </button>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      <?php endif; wp_reset_postdata(); ?>
      <?php /* 2列×2行の4枠を保つための「近日公開」枠 (作品が0件なら4枚、足りない分だけ埋める) */ ?>
      <?php for ($placeholder = 0; $placeholder < $placeholder_count; $placeholder++) : ?>
        <article class="work-card is-coming-soon">
          <div class="work-card-glass">
            <div class="work-card-top-row">
              <span class="work-badge-cat"><?php esc_html_e('Coming soon', 'wawahz'); ?></span>
            </div>
            <div class="work-card-body">
              <span class="material-symbols-rounded work-card-coming-icon" aria-hidden="true">hourglass_empty</span>
              <h3 class="work-card-title"><?php esc_html_e('近日公開', 'wawahz'); ?></h3>
              <p class="work-card-summary"><?php esc_html_e('新しい作品を準備中です。公開までお待ちください。', 'wawahz'); ?></p>
            </div>
          </div>
        </article>
      <?php endfor; ?>
    </div>
    <nav class="works-pagination" aria-label="<?php esc_attr_e('作品のページ送り', 'wawahz'); ?>">
      <div class="connected-button-group">
        <?php foreach (array('prev' => -1, 'next' => 1) as $direction => $step) :
          $target = $current_page + $step;
          $enabled = $target >= 1 && $target <= $total_pages;
          $label = $step < 0 ? __('前の作品ページ', 'wawahz') : __('次の作品ページ', 'wawahz');
          $icon = $step < 0 ? 'navigate_before' : 'navigate_next';
        ?>
          <?php if ($direction === 'next') : ?>
            <span class="page-indicator" aria-hidden="true"><?php echo esc_html($current_page); ?><span class="page-sep">/</span><?php echo esc_html(max(1, $total_pages)); ?></span>
          <?php endif; ?>
          <?php if ($enabled) : ?>
            <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($page_url($target)); ?>" aria-label="<?php echo esc_attr($label); ?>" rel="<?php echo esc_attr($direction); ?>">
              <span class="material-symbols-rounded" aria-hidden="true"><?php echo esc_html($icon); ?></span>
            </a>
          <?php else : ?>
            <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-label="<?php echo esc_attr($label); ?>">
              <span class="material-symbols-rounded" aria-hidden="true"><?php echo esc_html($icon); ?></span>
            </button>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <p class="gallery-pagination-info"><?php
        printf(esc_html__('全 %d 件', 'wawahz'), (int) $works_query->found_posts);
      ?></p>
    </nav>

    <?php /* 最下部のおまけ枠: 授業でつくったミニゲーム (ゲーム本体は起動時に遅延読み込み) */ ?>
    <?php get_template_part('template-parts/mini-game'); ?>
  </div>
</section>
