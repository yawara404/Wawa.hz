<?php
/**
 * template-parts/filter-list.php
 * 静的版 screen-category (画面 2 / d画面 2) に完全準拠したカテゴリー別記事検索テンプレート。
 * Image 3 見本に完全一致。
 */

$works = !empty($args['works']);
$listing = wawahz_filter_query($works);
$all_categories = get_categories(array('hide_empty' => false));
$selected_slug = wawahz_request_value('cat_slug');
$selected_cat = $selected_slug ? get_category_by_slug($selected_slug) : null;
$selected_name = $selected_cat ? $selected_cat->name : 'Uncategorize';
$sort = wawahz_request_value('sort', 'date-desc');
$current_page = max(1, absint(wawahz_request_value('wawa_page', '1')));
$max_pages = (int) $listing->max_num_pages;
$prev_url = $current_page > 1 ? add_query_arg(array('view' => 'category', 'cat_slug' => $selected_slug, 'sort' => $sort, 'wawa_page' => $current_page - 1)) : null;
$next_url = $current_page < $max_pages ? add_query_arg(array('view' => 'category', 'cat_slug' => $selected_slug, 'sort' => $sort, 'wawa_page' => $current_page + 1)) : null;
?>
<section class="screen-view active search-screen-view category-screen-view" id="screen-category" aria-label="<?php esc_attr_e('Category Screen', 'wawahz'); ?>">
  <h2 class="visually-hidden"><?php esc_html_e('カテゴリ別記事検索', 'wawahz'); ?></h2>

  <!-- ==============================================
       1. モバイル版レイアウト (412×892)
       ============================================== -->
  <div class="mobile-only" style="display: flex; flex-direction: column; width: 100%; align-items: center; justify-content: center;">
    <div class="search-screen-container">
      <!-- 上部中央の「Category」トーナルボタン（幅 182dp） -->
      <div style="display: flex; justify-content: center;">
        <div class="m3-btn m3-btn-tonal search-screen-title" id="mobile-category-header-btn" tabindex="-1" aria-hidden="true">
          <span class="material-symbols-rounded" aria-hidden="true">category</span>
          <span>Category</span>
        </div>
      </div>

      <!-- カテゴリ選択ドロップダウン & 並び替えメニュー -->
      <div class="category-controls-row search-controls-row" style="width: 100%; display: flex; flex-direction: row; gap: 8px; align-items: center; position: relative;">
        <div style="flex: 1; position: relative;">
          <div class="m3-dropdown-field" id="mobile-cat-dropdown-field" tabindex="0" role="combobox" aria-label="<?php esc_attr_e('カテゴリ選択', 'wawahz'); ?>" style="height: 48px; flex: 1 1 auto; min-width: 0;">
            <span class="dropdown-label" id="mobile-cat-dropdown-label">Select</span>
            <span id="mobile-cat-dropdown-selected" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($selected_name); ?></span>
            <span class="material-symbols-rounded" aria-hidden="true">arrow_drop_down</span>
            <select class="m3-dropdown-native-select" onchange="location.href = this.value" aria-label="<?php esc_attr_e('カテゴリ選択', 'wawahz'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2;">
              <option value="<?php echo esc_url(wawahz_view_url('category')); ?>">Uncategorize / すべて</option>
              <?php foreach ($all_categories as $c) : ?>
                <option value="<?php echo esc_url(add_query_arg(array('view' => 'category', 'cat_slug' => $c->slug, 'sort' => $sort))); ?>" <?php selected($selected_slug, $c->slug); ?>>
                  <?php echo esc_html($c->name); ?> (<?php echo esc_html($c->count); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="m3-sort-dropdown-wrapper" style="height: 48px; flex-shrink: 0;">
          <span class="material-symbols-rounded sort-icon" aria-hidden="true">sort</span>
          <select class="m3-sort-select" id="mobile-cat-sort-select" aria-label="<?php esc_attr_e('記事の並び替え', 'wawahz'); ?>" onchange="location.href = this.value">
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'category', 'cat_slug' => $selected_slug, 'sort' => 'date-desc'))); ?>" <?php selected($sort, 'date-desc'); ?>>新しい順</option>
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'category', 'cat_slug' => $selected_slug, 'sort' => 'date-asc'))); ?>" <?php selected($sort, 'date-asc'); ?>>古い順</option>
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'category', 'cat_slug' => $selected_slug, 'sort' => 'title-asc'))); ?>" <?php selected($sort, 'title-asc'); ?>>タイトル順</option>
          </select>
          <span class="material-symbols-rounded sort-arrow" aria-hidden="true">arrow_drop_down</span>
        </div>
      </div>

      <!-- 中央付近に 380×320dp のボックス（背景 surfaceContainerHigh、角丸 28dp、スクロール可能記事欄） -->
      <div class="m3-box box-surface-high-380-320" id="mobile-cat-articles-box" style="margin: 0 auto;">
        <div class="cat-scroll-articles-list" id="mobile-cat-articles-list">
          <?php if ($listing->have_posts()) : ?>
            <?php while ($listing->have_posts()) : $listing->the_post();
              $post_cats = get_the_category();
              $cat_badge = $post_cats ? $post_cats[0]->name : 'ARTICLE';
              $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 20);
            ?>
              <a class="cat-article-item" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                <div class="cat-article-info">
                  <div class="cat-article-meta">
                    <span class="cat-article-badge"><?php echo esc_html(strtoupper($cat_badge)); ?></span>
                    <span class="cat-article-date"><?php echo esc_html(get_the_date('Y-m-d')); ?></span>
                  </div>
                  <h3 class="cat-article-title" style="margin: 0; font-size: 14px;"><?php the_title(); ?></h3>
                  <?php if ($excerpt) : ?>
                    <p class="cat-article-summary"><?php echo esc_html($excerpt); ?></p>
                  <?php endif; ?>
                </div>
                <span class="material-symbols-rounded cat-article-chevron" aria-hidden="true">chevron_right</span>
              </a>
            <?php endwhile; ?>
          <?php else : ?>
            <div class="cat-article-item cat-article-empty">
              <div class="cat-article-info">
                <h3 class="cat-article-title"><?php esc_html_e('記事が見つかりませんでした', 'wawahz'); ?></h3>
                <p class="cat-article-summary"><?php esc_html_e('選択した条件に該当する記事はありません。', 'wawahz'); ?></p>
              </div>
            </div>
          <?php endif; wp_reset_postdata(); ?>
        </div>
      </div>

      <!-- 連結ボタングループ（トーナル） -->
      <div style="display: flex; justify-content: center; margin-top: 4px;">
        <div class="connected-button-group">
          <?php if ($prev_url) : ?>
            <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($prev_url); ?>" aria-label="Previous Page">
              <span class="material-symbols-rounded" aria-hidden="true">chevron_left</span>
            </a>
          <?php else : ?>
            <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true"><span class="material-symbols-rounded" aria-hidden="true">chevron_left</span></button>
          <?php endif; ?>

          <span class="page-indicator" aria-hidden="true">
            <?php echo esc_html($current_page); ?><span class="page-sep">/</span><?php echo esc_html(max(1, $max_pages)); ?>
          </span>

          <?php if ($next_url) : ?>
            <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($next_url); ?>" aria-label="Next Page">
              <span class="material-symbols-rounded" aria-hidden="true">chevron_right</span>
            </a>
          <?php else : ?>
            <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true"><span class="material-symbols-rounded" aria-hidden="true">chevron_right</span></button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ==============================================
       2. デスクトップ版レイアウト (1280×800 / d画面 2) 手本完全準拠
       ============================================== -->
  <div class="desktop-only dcat-desktop-container">
    <div class="search-screen-container">
      <!-- 上部中央の「Category」トーナルボタン（幅 380dp） -->
      <div style="display: flex; justify-content: center;">
        <div class="m3-btn m3-btn-tonal search-screen-title" style="width: 380px; height: 56px; font-weight: 700; pointer-events: none;" id="desk-category-header-btn" tabindex="-1" aria-hidden="true">
          <span class="material-symbols-rounded" aria-hidden="true">category</span>
          <span>Category</span>
        </div>
      </div>

      <!-- 中央付近の中央にラベル「Select」のアウトラインドロップダウン & 並び替えメニュー -->
      <div class="category-controls-row search-controls-row" style="width: 100%; max-width: 616px; margin: 0 auto; display: flex; gap: 12px; align-items: center; position: relative;">
        <div style="flex: 1; position: relative;">
          <div class="m3-dropdown-field" id="desk-cat-dropdown-field" tabindex="0" role="combobox" aria-label="<?php esc_attr_e('カテゴリ選択', 'wawahz'); ?>" style="height: 56px;">
            <span class="dropdown-label" id="desk-cat-dropdown-label">Select</span>
            <span id="desk-cat-dropdown-selected" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500;"><?php echo esc_html($selected_name); ?></span>
            <span class="material-symbols-rounded" aria-hidden="true">arrow_drop_down</span>
            <select class="m3-dropdown-native-select" onchange="location.href = this.value" aria-label="<?php esc_attr_e('カテゴリ選択', 'wawahz'); ?>" style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2;">
              <option value="<?php echo esc_url(wawahz_view_url('category')); ?>">Uncategorize / すべて</option>
              <?php foreach ($all_categories as $c) : ?>
                <option value="<?php echo esc_url(add_query_arg(array('view' => 'category', 'cat_slug' => $c->slug, 'sort' => $sort))); ?>" <?php selected($selected_slug, $c->slug); ?>>
                  <?php echo esc_html($c->name); ?> (<?php echo esc_html($c->count); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="m3-sort-dropdown-wrapper" style="height: 56px; flex-shrink: 0; min-width: 140px;">
          <span class="material-symbols-rounded sort-icon" aria-hidden="true">sort</span>
          <select class="m3-sort-select" id="desk-cat-sort-select" aria-label="<?php esc_attr_e('記事の並び替え', 'wawahz'); ?>" onchange="location.href = this.value">
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'category', 'cat_slug' => $selected_slug, 'sort' => 'date-desc'))); ?>" <?php selected($sort, 'date-desc'); ?>>新しい順</option>
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'category', 'cat_slug' => $selected_slug, 'sort' => 'date-asc'))); ?>" <?php selected($sort, 'date-asc'); ?>>古い順</option>
            <option value="<?php echo esc_url(add_query_arg(array('view' => 'category', 'cat_slug' => $selected_slug, 'sort' => 'title-asc'))); ?>" <?php selected($sort, 'title-asc'); ?>>タイトル順</option>
          </select>
          <span class="material-symbols-rounded sort-arrow" aria-hidden="true">arrow_drop_down</span>
        </div>
      </div>

      <!-- 中央付近の中央に 616×320dp のボックス（背景 surfaceContainerHigh、角丸 28dp、スクロール可能記事欄） -->
      <div class="m3-box box-surface-high-616-320" id="desk-cat-articles-box" style="margin: 0 auto;">
        <div class="cat-scroll-articles-list" id="desk-cat-articles-list">
          <?php if ($listing->have_posts()) : ?>
            <?php while ($listing->have_posts()) : $listing->the_post();
              $post_cats = get_the_category();
              $cat_badge = $post_cats ? $post_cats[0]->name : 'ARTICLE';
              $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 24);
            ?>
              <a class="cat-article-item" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                <div class="cat-article-info">
                  <div class="cat-article-meta">
                    <span class="cat-article-badge"><?php echo esc_html(strtoupper($cat_badge)); ?></span>
                    <span class="cat-article-date"><?php echo esc_html(get_the_date('Y-m-d')); ?></span>
                  </div>
                  <h3 class="cat-article-title" style="margin: 0; font-size: 15px;"><?php the_title(); ?></h3>
                  <?php if ($excerpt) : ?>
                    <p class="cat-article-summary"><?php echo esc_html($excerpt); ?></p>
                  <?php endif; ?>
                </div>
                <span class="material-symbols-rounded cat-article-chevron" aria-hidden="true">chevron_right</span>
              </a>
            <?php endwhile; ?>
          <?php else : ?>
            <div class="cat-article-item cat-article-empty">
              <div class="cat-article-info">
                <h3 class="cat-article-title"><?php esc_html_e('記事が見つかりませんでした', 'wawahz'); ?></h3>
                <p class="cat-article-summary"><?php esc_html_e('選択した条件に該当する記事はありません。別のカテゴリーをお試しください。', 'wawahz'); ?></p>
              </div>
            </div>
          <?php endif; wp_reset_postdata(); ?>
        </div>
      </div>

      <!-- 下部の中央に連結したボタングループ（トーナル） -->
      <div class="dcat-pagination-row search-pagination-row">
        <div class="connected-button-group">
          <?php if ($prev_url) : ?>
            <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($prev_url); ?>" aria-label="Previous Page">
              <span class="material-symbols-rounded" aria-hidden="true">chevron_left</span>
            </a>
          <?php else : ?>
            <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true"><span class="material-symbols-rounded" aria-hidden="true">chevron_left</span></button>
          <?php endif; ?>

          <span class="page-indicator" aria-hidden="true">
            <?php echo esc_html($current_page); ?><span class="page-sep">/</span><?php echo esc_html(max(1, $max_pages)); ?>
          </span>

          <?php if ($next_url) : ?>
            <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($next_url); ?>" aria-label="Next Page">
              <span class="material-symbols-rounded" aria-hidden="true">chevron_right</span>
            </a>
          <?php else : ?>
            <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true"><span class="material-symbols-rounded" aria-hidden="true">chevron_right</span></button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
