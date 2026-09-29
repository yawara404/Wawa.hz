<?php
/**
 * template-parts/search-screen-body.php
 *
 * 検索画面 (#screen-search) の中身。カテゴリー画面 (template-parts/filter-list.php) と
 * 同じ「モバイル / デスクトップ」2レイアウト構成にするため、search.php から
 * $args['variant'] を変えて2回読み込む。
 *   mobile  … 380×320 のボックス (.box-surface-high-380-320)
 *   desktop … 616×320 のボックス (.box-surface-high-616-320)
 *
 * id は variant ごとに一意になるよう接尾辞を付けている（JS フックは data 属性）。
 */

$variant   = (isset($args['variant']) && $args['variant'] === 'desktop') ? 'desktop' : 'mobile';
$box_class = $variant === 'desktop' ? 'box-surface-high-616-320' : 'box-surface-high-380-320';

global $wp_query;
$query_str        = get_search_query();
$quick_categories = get_categories(array('hide_empty' => true, 'number' => 6));
$current_page     = max(1, get_query_var('paged'));
$max_pages        = (int) $wp_query->max_num_pages;
$found_posts      = (int) $wp_query->found_posts;
$result_posts     = (array) $wp_query->posts;          // メインクエリの結果（両レイアウトで共用）
$prev_url         = $current_page > 1 ? get_previous_posts_page_link() : null;
$next_url         = $current_page < $max_pages ? get_next_posts_page_link() : null;
?>
<h2 class="visually-hidden"><?php esc_html_e('記事検索', 'wawahz'); ?></h2>

<!-- 上部: 「Search」トーナル見出しボタン（カテゴリ見出しと完全統一） -->
<div style="display: flex; justify-content: center;">
  <div class="m3-btn m3-btn-tonal search-screen-title" id="btn-search-header-<?php echo esc_attr($variant); ?>" tabindex="-1" aria-hidden="true">
    <span class="material-symbols-rounded" aria-hidden="true">search</span>
    <span>Search</span>
  </div>
</div>

<!-- 検索フォームバー -->
<form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="search-controls-row" id="search-controls-form-<?php echo esc_attr($variant); ?>">
  <div class="m3-search-bar" id="main-search-bar-<?php echo esc_attr($variant); ?>">
    <span class="material-symbols-rounded search-input-icon" aria-hidden="true">search</span>
    <input type="text"
           id="search-input-<?php echo esc_attr($variant); ?>"
           data-search-input
           name="s"
           value="<?php echo esc_attr($query_str); ?>"
           autocomplete="off"
           placeholder="<?php esc_attr_e('記事を検索…', 'wawahz'); ?>"
           aria-label="<?php esc_attr_e('記事を検索', 'wawahz'); ?>">
    <button type="button"
            class="search-clear-btn"
            id="search-clear-btn-<?php echo esc_attr($variant); ?>"
            data-search-clear
            title="<?php esc_attr_e('クリア', 'wawahz'); ?>"
            aria-label="<?php esc_attr_e('検索語をクリア', 'wawahz'); ?>"
            <?php echo $query_str === '' ? 'style="display:none;"' : ''; ?>>
      <span class="material-symbols-rounded" aria-hidden="true">close</span>
    </button>
    <button type="submit" class="icon-btn-48 search-submit-btn" style="width: 36px; height: 36px;" title="<?php esc_attr_e('検索を実行', 'wawahz'); ?>" aria-label="<?php esc_attr_e('検索を実行', 'wawahz'); ?>">
      <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
    </button>
  </div>
</form>

<!-- おすすめ・クイックカテゴリータグ -->
<?php if (!empty($quick_categories)) : ?>
  <div class="search-quick-tags" aria-label="<?php esc_attr_e('クイック検索タグ', 'wawahz'); ?>">
    <span class="search-quick-tags-label"><?php esc_html_e('トピック:', 'wawahz'); ?></span>
    <div class="search-quick-tags-list">
      <?php foreach ($quick_categories as $cat) : ?>
        <a href="<?php echo esc_url(add_query_arg(array('s' => $cat->name), home_url('/'))); ?>" class="search-tag-chip">
          <?php echo esc_html($cat->name); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($query_str === '') : ?>
  <!-- 初期状態（キーワード未入力時のウェルカム表示） -->
  <div class="search-initial-card">
    <div class="search-initial-icon-wrap">
      <span class="material-symbols-rounded" aria-hidden="true">manage_search</span>
    </div>
    <h3 class="search-initial-title"><?php esc_html_e('気になる記事を検索', 'wawahz'); ?></h3>
    <p class="search-initial-desc">
      <?php esc_html_e('キーワードを入力するか、上のトピックタグからカテゴリー別の記事を探せます。', 'wawahz'); ?>
    </p>
    <div class="search-initial-categories">
      <?php foreach ($quick_categories as $cat) : ?>
        <a href="<?php echo esc_url(add_query_arg(array('view' => 'category', 'cat_slug' => $cat->slug), home_url('/'))); ?>" class="search-category-tile">
          <span class="material-symbols-rounded" aria-hidden="true">category</span>
          <span><?php echo esc_html($cat->name); ?></span>
          <span class="search-cat-count"><?php echo esc_html($cat->count); ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

<?php elseif ($result_posts) : ?>
  <!-- 検索結果件数表示 -->
  <div class="search-meta-row">
    <p class="results-count" style="margin: 0; font-size: 13px; font-weight: 500; color: var(--md-sys-color-on-surface-variant);">
      <?php echo esc_html(sprintf(__('「%s」の検索結果: %s 件', 'wawahz'), $query_str, number_format_i18n($found_posts))); ?>
    </p>
  </div>

  <!-- 検索結果: 記事一覧ボックス（カテゴリー画面と同じボックス構成） -->
  <div class="m3-box <?php echo esc_attr($box_class); ?> search-results-panel" id="search-results-box-<?php echo esc_attr($variant); ?>">
    <div class="cat-scroll-articles-list" id="search-results-list-<?php echo esc_attr($variant); ?>">
      <?php foreach ($result_posts as $result_post) :
        $cats = get_the_category($result_post->ID);
        $badge = $cats ? $cats[0]->name : 'ARTICLE';
        $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($result_post)), 22);
      ?>
        <a class="cat-article-item" href="<?php echo esc_url(get_permalink($result_post)); ?>" aria-label="<?php echo esc_attr(get_the_title($result_post)); ?>">
          <div class="cat-article-info">
            <div class="cat-article-meta">
              <span class="cat-article-badge"><?php echo esc_html(strtoupper($badge)); ?></span>
              <span class="cat-article-date"><?php echo esc_html(get_the_date('Y-m-d', $result_post)); ?></span>
            </div>
            <h3 class="cat-article-title"><?php echo esc_html(get_the_title($result_post)); ?></h3>
            <?php if ($excerpt) : ?>
              <p class="cat-article-summary"><?php echo esc_html($excerpt); ?></p>
            <?php endif; ?>
          </div>
          <span class="material-symbols-rounded cat-article-chevron" aria-hidden="true">chevron_right</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>


  <!-- ページネーション: 連結ボタングループ（カテゴリー画面と同一） -->
  <?php if ($max_pages > 1) : ?>
    <div class="dcat-pagination-row">
      <div class="connected-button-group">
        <?php if ($prev_url) : ?>
          <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($prev_url); ?>" title="<?php esc_attr_e('前へ', 'wawahz'); ?>" aria-label="<?php esc_attr_e('前の検索結果', 'wawahz'); ?>">
            <span class="material-symbols-rounded" aria-hidden="true">chevron_left</span>
          </a>
        <?php else : ?>
          <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true">
            <span class="material-symbols-rounded" aria-hidden="true">chevron_left</span>
          </button>
        <?php endif; ?>

        <span class="page-indicator" aria-hidden="true">
          <?php echo esc_html($current_page); ?><span class="page-sep">/</span><?php echo esc_html(max(1, $max_pages)); ?>
        </span>

        <?php if ($next_url) : ?>
          <a class="m3-btn m3-btn-filled m3-btn-icon-only" href="<?php echo esc_url($next_url); ?>" title="<?php esc_attr_e('次へ', 'wawahz'); ?>" aria-label="<?php esc_attr_e('次の検索結果', 'wawahz'); ?>">
            <span class="material-symbols-rounded" aria-hidden="true">chevron_right</span>
          </a>
        <?php else : ?>
          <button class="m3-btn m3-btn-filled m3-btn-icon-only" disabled aria-disabled="true">
            <span class="material-symbols-rounded" aria-hidden="true">chevron_right</span>
          </button>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

<?php else : ?>
  <!-- 検索結果ゼロ: 空ステート -->
  <div class="search-empty-card">
    <div class="search-empty-icon-wrap">
      <span class="material-symbols-rounded search-empty-icon" aria-hidden="true">search_off</span>
    </div>
    <h3 class="search-empty-title"><?php echo esc_html(sprintf(__('「%s」に一致する記事は見つかりませんでした', 'wawahz'), $query_str)); ?></h3>
    <p class="search-empty-desc"><?php esc_html_e('キーワードのスペルをご確認いただくか、別の言葉やカテゴリーから探してみてください。', 'wawahz'); ?></p>
    <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-top: 14px;">
      <a href="<?php echo esc_url(wawahz_view_url('category')); ?>" class="m3-btn m3-btn-tonal" style="display: inline-flex; align-items: center; gap: 6px;">
        <span class="material-symbols-rounded" aria-hidden="true">category</span>
        <span><?php esc_html_e('カテゴリー一覧', 'wawahz'); ?></span>
      </a>
      <a href="<?php echo esc_url(home_url('/')); ?>" class="m3-btn m3-btn-tonal" style="display: inline-flex; align-items: center; gap: 6px;">
        <span class="material-symbols-rounded" aria-hidden="true">home</span>
        <span><?php esc_html_e('ホームに戻る', 'wawahz'); ?></span>
      </a>
    </div>
  </div>
<?php endif; ?>

