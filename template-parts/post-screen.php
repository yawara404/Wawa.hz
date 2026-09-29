<?php

/**
 * template-parts/post-screen.php
 * 静的見本 (backup_static/index.html 行720〜832) および Image 1 に完全準拠した
 * Post 画面 (2列×3行均等カードグリッド・並び替え・連結ページネーション)
 */

$sort = wawahz_request_value('sort', 'date-desc');
$current_page = max(1, absint(wawahz_request_value('wawa_page', '1')));

$orderby = 'date';
$order = 'DESC';
if ($sort === 'date-asc') {
  $orderby = 'date';
  $order = 'ASC';
} elseif ($sort === 'title-asc') {
  $orderby = 'title';
  $order = 'ASC';
}

$query_args = array(
  'post_type'           => 'post',
  'post_status'         => 'publish',
  'has_password'        => false,
  // 画面サイズに応じて 6 / 8 / 10 枚を表示するため、最大 10 件を取得する
  'posts_per_page'      => 10,
  'paged'               => $current_page,
  'orderby'             => $orderby,
  'order'               => $order,
  'ignore_sticky_posts' => true,
);

$post_query = new WP_Query($query_args);
$posts = $post_query->posts;
$max_pages = (int) $post_query->max_num_pages;

// ページネーション（モバイル / デスクトップ両方のレイアウトで共用）
$prev_url = $current_page > 1 ? add_query_arg(array('view' => 'post', 'wawa_page' => $current_page - 1, 'sort' => $sort)) : null;
$next_url = $current_page < $max_pages ? add_query_arg(array('view' => 'post', 'wawa_page' => $current_page + 1, 'sort' => $sort)) : null;

$theme_uri = get_template_directory_uri();
$fallback_images = array(
  'music' => array(
    $theme_uri . '/images/music_shimmer.svg?v=20250925_02',
    $theme_uri . '/images/music_velocity.svg?v=20250925_02',
    $theme_uri . '/images/music_pourover.svg?v=20250925_02',
    $theme_uri . '/images/music_monologue.svg?v=20250925_02',
  ),
  'work' => array(
    $theme_uri . '/images/work_keyboard.svg?v=20250925_02',
    $theme_uri . '/images/work_ambient.svg?v=20250925_02',
    $theme_uri . '/images/work_design.svg?v=20250925_02',
  ),
  'life' => array(
    $theme_uri . '/images/uncat_minimal_web.svg?v=20250925_02',
    $theme_uri . '/images/life_desk_plant.svg?v=20250925_02',
    $theme_uri . '/images/uncat_coffee_beans.svg?v=20250925_02',
  ),
  'default' => array(
    $theme_uri . '/images/music_shimmer.svg?v=20250925_02',
    $theme_uri . '/images/music_velocity.svg?v=20250925_02',
    $theme_uri . '/images/desk_coffee.svg?v=20250925_02',
    $theme_uri . '/images/hobby_camera.svg?v=20250925_02',
  ),
);
?>
<section class="screen-view active post-screen-view" id="screen-post" aria-label="<?php esc_attr_e('Post Screen', 'wawahz'); ?>">

  <?php /* ==============================================
       1. モバイル版レイアウト (412 / 380×320 ボックス)
       カテゴリー画面と同じ「スクロールする記事リスト」構成
       ============================================== */ ?>
  <div class="mobile-only" style="display: flex; flex-direction: column; gap: 16px; width: 100%;">
    <div style="display: flex; flex-direction: column; gap: 16px; width: 100%; max-width: 380px; margin: 0 auto;">

      <div class="post-screen-action-bar">
        <h2 class="post-screen-title">
          <span class="material-symbols-rounded" aria-hidden="true">send</span>
          <span><?php esc_html_e('Posts', 'wawahz'); ?></span>
        </h2>

        <div class="m3-sort-dropdown-wrapper">
          <span class="material-symbols-rounded sort-icon" aria-hidden="true">sort</span>
          <select class="m3-sort-select" id="post-sort-select-mobile" aria-label="<?php esc_attr_e('記事の並び替え', 'wawahz'); ?>" onchange="location.href = this.value;">
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'post', 'sort' => 'date-desc', 'wawa_page' => 1))); ?>" <?php selected($sort, 'date-desc'); ?>>新しい順</option>
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'post', 'sort' => 'date-asc', 'wawa_page' => 1))); ?>" <?php selected($sort, 'date-asc'); ?>>古い順</option>
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'post', 'sort' => 'title-asc', 'wawa_page' => 1))); ?>" <?php selected($sort, 'title-asc'); ?>>タイトル順</option>
          </select>
          <span class="material-symbols-rounded sort-arrow" aria-hidden="true">arrow_drop_down</span>
        </div>
      </div>

      <div class="m3-box box-surface-high-380-320" id="mobile-post-articles-box" style="margin: 0 auto;">
        <div class="cat-scroll-articles-list" id="mobile-post-articles-list">
          <?php if ($posts) : ?>
            <?php foreach ($posts as $post_item) :
              $cats = get_the_category($post_item->ID);
              $badge = $cats ? $cats[0]->name : 'ARTICLE';
              $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($post_item)), 20);
            ?>
              <a class="cat-article-item" href="<?php echo esc_url(get_permalink($post_item)); ?>" aria-label="<?php echo esc_attr(get_the_title($post_item)); ?>">
                <div class="cat-article-info">
                  <div class="cat-article-meta">
                    <span class="cat-article-badge"><?php echo esc_html(strtoupper($badge)); ?></span>
                    <span class="cat-article-date"><?php echo esc_html(get_the_date('Y-m-d', $post_item)); ?></span>
                  </div>
                  <h3 class="cat-article-title"><?php echo esc_html(get_the_title($post_item)); ?></h3>
                  <?php if ($excerpt) : ?>
                    <p class="cat-article-summary"><?php echo esc_html($excerpt); ?></p>
                  <?php endif; ?>
                </div>
                <span class="material-symbols-rounded cat-article-chevron" aria-hidden="true">chevron_right</span>
              </a>
            <?php endforeach; ?>
          <?php else : ?>
            <div class="cat-article-item cat-article-empty">
              <div class="cat-article-info">
                <h3 class="cat-article-title"><?php esc_html_e('記事が見つかりませんでした', 'wawahz'); ?></h3>
                <p class="cat-article-summary"><?php esc_html_e('まだ記事がありません。', 'wawahz'); ?></p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ページネーション: 連結ボタングループ -->
      <div class="dcat-pagination-row">
        <div class="connected-button-group">
          <?php if ($prev_url) : ?>
            <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($prev_url); ?>" id="post-pagination-prev-mobile" title="<?php esc_attr_e('前の10件', 'wawahz'); ?>" aria-label="<?php esc_attr_e('前の10件', 'wawahz'); ?>">
              <span class="material-symbols-rounded" aria-hidden="true">navigate_before</span>
            </a>
          <?php else : ?>
            <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true" title="<?php esc_attr_e('前の10件', 'wawahz'); ?>" aria-label="<?php esc_attr_e('前の10件', 'wawahz'); ?>">
              <span class="material-symbols-rounded" aria-hidden="true">navigate_before</span>
            </button>
          <?php endif; ?>

          <span class="page-indicator" aria-hidden="true">
            <?php echo esc_html($current_page); ?><span class="page-sep">/</span><?php echo esc_html(max(1, $max_pages)); ?>
          </span>

          <?php if ($next_url) : ?>
            <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($next_url); ?>" id="post-pagination-next-mobile" title="<?php esc_attr_e('次の10件', 'wawahz'); ?>" aria-label="<?php esc_attr_e('次の10件', 'wawahz'); ?>">
              <span class="material-symbols-rounded" aria-hidden="true">navigate_next</span>
            </a>
          <?php else : ?>
            <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true" title="<?php esc_attr_e('次の10件', 'wawahz'); ?>" aria-label="<?php esc_attr_e('次の10件', 'wawahz'); ?>">
              <span class="material-symbols-rounded" aria-hidden="true">navigate_next</span>
            </button>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <?php /* ==============================================
       2. デスクトップ版レイアウト (1280 / 616 幅・2列×3行のカードグリッド)
       ============================================== */ ?>
  <div class="desktop-only dcat-desktop-container">
    <div class="post-desktop-container">

      <!-- 上部のアクションバー: 「記事一覧」見出し & 並び替えドロップダウン -->
      <div class="post-screen-action-bar">
        <h2 class="post-screen-title">
          <span class="material-symbols-rounded" aria-hidden="true">send</span>
          <span><?php esc_html_e('Posts', 'wawahz'); ?></span>
        </h2>

        <div class="m3-sort-dropdown-wrapper">
          <span class="material-symbols-rounded sort-icon" aria-hidden="true">sort</span>
          <select class="m3-sort-select" id="post-sort-select-desk" aria-label="<?php esc_attr_e('記事の並び替え', 'wawahz'); ?>" onchange="location.href = this.value;">
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'post', 'sort' => 'date-desc', 'wawa_page' => 1))); ?>" <?php selected($sort, 'date-desc'); ?>>新しい順</option>
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'post', 'sort' => 'date-asc', 'wawa_page' => 1))); ?>" <?php selected($sort, 'date-asc'); ?>>古い順</option>
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'post', 'sort' => 'title-asc', 'wawa_page' => 1))); ?>" <?php selected($sort, 'title-asc'); ?>>タイトル順</option>
          </select>
          <span class="material-symbols-rounded sort-arrow" aria-hidden="true">arrow_drop_down</span>
        </div>
      </div>

      <!-- カードグリッド（2列。画面が大きいほど段数＝枚数が増える: 6 → 8 → 10枚）
           ※ スロット7〜10 は CSS（css/components.css の [data-post-slot]）で
             画面幅・高さに応じて表示する -->
      <div class="post-cards-grid" id="post-cards-grid-container">
        <?php for ($idx = 0; $idx < 10; $idx++) :
          $slot_number = $idx + 1;
          if (isset($posts[$idx])) :
            $p = $posts[$idx];
            $cats = get_the_category($p->ID);
            $cat_name = $cats ? $cats[0]->name : 'ARTICLE';
            $cat_lower = strtolower($cat_name);

                // サムネイル画像の取得（アイキャッチ or テーマ内SVG）
                $thumb = get_the_post_thumbnail_url($p, 'large');
                if (!$thumb) {
                  $pool = $fallback_images['default'];
                  if (isset($fallback_images[$cat_lower])) {
                    $pool = $fallback_images[$cat_lower];
                  } elseif (str_contains($cat_lower, 'music') || str_contains($cat_lower, 'audio')) {
                    $pool = $fallback_images['music'];
                  } elseif (str_contains($cat_lower, 'work') || str_contains($cat_lower, 'hardware')) {
                    $pool = $fallback_images['work'];
                  } elseif (str_contains($cat_lower, 'life')) {
                    $pool = $fallback_images['life'];
                  }
                  $thumb = $pool[$idx % count($pool)];
                }

                $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($p)), 20);
            ?>
              <a class="m3-card m3-card-filled m3-card-scrim post-article-card" id="post-card-<?php echo esc_attr($slot_number); ?>" data-post-slot="<?php echo esc_attr($slot_number); ?>"
                href="<?php echo esc_url(get_permalink($p)); ?>"
                style="background-image: url('<?php echo esc_url($thumb); ?>'); text-decoration: none;"
                aria-label="<?php echo esc_attr(get_the_title($p)); ?>">
                <div class="card-scrim-white">
                  <span class="card-badge"><?php echo esc_html(strtoupper($cat_name)); ?></span>
                  <h3 class="card-title"><?php echo esc_html(get_the_title($p)); ?></h3>
                  <p class="card-desc"><?php echo esc_html($excerpt); ?></p>
                </div>
              </a>
            <?php else : ?>
              <?php /* 空記事枠: ホーム画面の空カードとデザインを統一 */ ?>
              <div class="m3-card m3-card-filled m3-card-scrim post-article-card is-empty-slot" id="post-card-<?php echo esc_attr($slot_number); ?>" data-post-slot="<?php echo esc_attr($slot_number); ?>"
                aria-hidden="true">
                <div class="card-scrim-white">
                  <h3 class="card-title"><?php esc_html_e('近日公開', 'wawahz'); ?></h3>
                </div>
              </div>
          <?php endif;
        endfor;
        wp_reset_postdata(); ?>
      </div>


      <!-- 記事グリッドの真下に連結ボタングループ（トーナル） -->
      <div style="display: flex; justify-content: center;">
        <div class="connected-button-group">
          <?php if ($prev_url) : ?>
            <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($prev_url); ?>" id="post-pagination-prev-desk" title="<?php esc_attr_e('前の10件', 'wawahz'); ?>" aria-label="<?php esc_attr_e('前の10件', 'wawahz'); ?>">
              <span class="material-symbols-rounded" aria-hidden="true">navigate_before</span>
            </a>
          <?php else : ?>
            <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true" title="<?php esc_attr_e('前の10件', 'wawahz'); ?>" aria-label="<?php esc_attr_e('前の10件', 'wawahz'); ?>">
              <span class="material-symbols-rounded" aria-hidden="true">navigate_before</span>
            </button>
          <?php endif; ?>

          <span class="page-indicator" aria-hidden="true">
            <?php echo esc_html($current_page); ?><span class="page-sep">/</span><?php echo esc_html(max(1, $max_pages)); ?>
          </span>

          <?php if ($next_url) : ?>
            <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($next_url); ?>" id="post-pagination-next-desk" title="<?php esc_attr_e('次の10件', 'wawahz'); ?>" aria-label="<?php esc_attr_e('次の10件', 'wawahz'); ?>">
              <span class="material-symbols-rounded" aria-hidden="true">navigate_next</span>
            </a>
          <?php else : ?>
            <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true" title="<?php esc_attr_e('次の10件', 'wawahz'); ?>" aria-label="<?php esc_attr_e('次の10件', 'wawahz'); ?>">
              <span class="material-symbols-rounded" aria-hidden="true">navigate_next</span>
            </button>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</section>

