<?php
/** WordPress の URL・メニュー・カード・ページ送りを共通化。 */
function wawahz_request_value($key, $default = '')
{
  return isset($_GET[$key]) && is_string($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : $default;
}

function wawahz_view()
{
  $view = wawahz_request_value('view');
  return (is_home() || is_front_page()) && in_array($view, array('category', 'post', 'gallery', 'nowplaying', 'info'), true) ? $view : '';
}

function wawahz_view_url($view)
{
  static $urls = array();
  if (!isset($urls[$view])) {
    $templates = array('gallery' => 'page-gallery.php', 'nowplaying' => 'page-nowplaying.php');
    $pages = isset($templates[$view]) ? get_posts(array(
      'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 1,
      'meta_key' => '_wp_page_template', 'meta_value' => $templates[$view],
      'orderby' => 'ID', 'order' => 'ASC',
    )) : array();
    $urls[$view] = $pages ? get_permalink($pages[0]) : add_query_arg('view', $view, home_url('/'));
  }
  return $urls[$view];
}

/**
 * 横スライドで巡回する3画面の現在位置を返す。
 *   画面1 = home（ホーム） / 画面2 = category（カテゴリー・検索） / 画面3 = info（情報欄）
 * 3画面以外（Post / Gallery / Nowplaying / 記事ページなど）では空文字を返す。
 */
function wawahz_slide_screen()
{
  if (is_home() || is_front_page()) {
    $view = wawahz_view();
    if ($view === 'category' || $view === 'info') {
      return $view;
    }
    if ($view === '' && !is_paged()) {
      return 'home';
    }
  }
  return '';
}

/**
 * 「次の画面」の URL（3画面を横スライドで進む）。
 *   画面1 ホーム → 画面2 カテゴリー / 画面3 情報欄 と進み、画面3では空文字
 *   （末尾なので次は無し＝右中央のボタンは表示されない）。
 * 3画面以外では空文字を返す。
 */
function wawahz_slide_next_url()
{
  switch (wawahz_slide_screen()) {
    case 'home':
      return wawahz_view_url('category');
    case 'category':
      return wawahz_view_url('info');
  }
  return '';
}

/**
 * 「前の画面」の URL（3画面を横スライドで戻る）。
 *   画面2 カテゴリー → 画面1 ホーム / 画面3 情報欄 → 画面2 カテゴリー。
 *   先頭の画面1 では空文字（左中央のボタンは表示されない）。
 * 3画面以外では空文字を返す。
 */
function wawahz_slide_prev_url()
{
  switch (wawahz_slide_screen()) {
    case 'category':
      return home_url('/');
    case 'info':
      return wawahz_view_url('category');
  }
  return '';
}

function wawahz_current_section()
{
  $v = wawahz_view();
  if ($v === 'post') { return 'post'; }
  if ($v === 'gallery') { return 'gallery'; }
  if ($v === 'category') { return 'category'; }
  if ($v === 'nowplaying') { return 'nowplaying'; }
  if ($v === 'info') { return 'info'; }
  if (is_page_template('page-gallery.php')) { return 'gallery'; }
  if (is_page_template('page-nowplaying.php')) { return 'nowplaying'; }
  if (is_search()) { return 'search'; }
  if (is_archive() || is_single()) { return 'post'; }
  return is_front_page() || is_home() ? 'home' : '';
}

function wawahz_navigation()
{
  return array(
    'home' => array('label' => 'Home', 'icon' => 'home', 'url' => home_url('/')),
    'search' => array('label' => 'Search', 'icon' => 'search', 'url' => add_query_arg('s', '', home_url('/'))),
    'post' => array('label' => 'Post', 'icon' => 'send', 'url' => wawahz_view_url('post')),
    'gallery' => array('label' => 'Gallery', 'icon' => 'grid_view', 'url' => wawahz_view_url('gallery')),
  );
}

function wawahz_icon($name)
{
  return '<span class="material-symbols-rounded" aria-hidden="true">' . esc_html($name) . '</span>';
}

function wawahz_post_title($post = null)
{
  return get_the_title($post) ?: __('タイトルなし', 'wawahz');
}

function wawahz_card_image($post_id, $size = 'medium_large')
{
  if (has_post_thumbnail($post_id)) {
    echo get_the_post_thumbnail($post_id, $size, array('class' => 'post-card-image', 'alt' => '', 'loading' => 'lazy'));
  } else {
    $categories = get_the_category($post_id);
    $slug = $categories[0]->slug ?? '';
    $image = $slug === 'work' ? 'work_design.svg' : 'life_desk_plant.svg';
    echo '<img class="post-card-image" src="' . esc_url(get_template_directory_uri() . '/images/' . $image) . '" alt="" loading="lazy" width="640" height="400">';
  }
}

/**
 * 個別ページのカバー画像 (16:10 ヒーロー) を解決する。
 *
 * 優先順位: プレイリストの代表動画（YouTubeサムネイルをそのまま使用）
 *          → アイキャッチ画像 → 動画投稿のYouTubeサムネイル
 *
 * @return array|null { url, width, height, youtube }
 */
function wawahz_cover_image($post = null)
{
  $post = get_post($post);
  if (!$post) {
    return null;
  }

  $cover_video_id = wawahz_playlist_cover_id($post);
  if ($cover_video_id) {
    $url = wawahz_youtube_thumbnail_url($cover_video_id);
    if ($url) {
      list($width, $height) = wawahz_youtube_thumbnail_size();
      return array('url' => $url, 'width' => $width, 'height' => $height, 'youtube' => true);
    }
  }

  $thumbnail_id = get_post_thumbnail_id($post);
  if ($thumbnail_id) {
    $src = wp_get_attachment_image_src($thumbnail_id, 'large');
    if ($src) {
      return array('url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2], 'youtube' => false);
    }
  }

  $media = wawahz_post_media($post);
  $video_id = ($media && $media['type'] === 'youtube') ? $media['youtube_id'] : '';
  $url = wawahz_youtube_thumbnail_url($video_id);
  if (!$url) {
    return null;
  }
  list($width, $height) = wawahz_youtube_thumbnail_size();
  return array('url' => $url, 'width' => $width, 'height' => $height, 'youtube' => true);
}

function wawahz_pagination($query = null, $key = '')
{
  global $wp_query;
  $query = $query ?: $wp_query;
  $current = $key ? max(1, absint(wawahz_request_value($key, '1'))) : max(1, get_query_var('paged'));
  $args = array(
    'total' => (int) $query->max_num_pages, 'current' => $current,
    'mid_size' => 1, 'type' => 'list',
    'prev_text' => __('← 前へ', 'wawahz'), 'next_text' => __('次へ →', 'wawahz'),
  );
  if ($key) {
    $args['base'] = str_replace('999999', '%#%', esc_url_raw(add_query_arg($key, 999999)));
    $args['format'] = '';
  }
  $links = paginate_links($args);
  if ($links) {
    echo '<nav class="wawa-pagination" aria-label="' . esc_attr__('ページ送り', 'wawahz') . '">' . wp_kses_post($links) . '</nav>';
  }
}

/** Use the same category for filtering and badges, including legacy posts. */
function wawahz_work_category($post_id)
{
  $category = get_post_meta($post_id, 'wawahz_work_category', true);
  if (!$category) {
    $categories = get_the_category($post_id);
    $category = ($categories && $categories[0]->slug !== 'work') ? $categories[0]->name : 'Design';
  }
  return $category;
}

/** ギャラリー右上のカテゴリソートに出す分類名 (公開中の作品に存在するものだけを作品数の多い順で返す)。 */
function wawahz_work_filter_terms()
{
  static $names = null;
  if ($names !== null) {
    return $names;
  }
  $names = array();
  $work = get_category_by_slug('work');
  if (!$work) {
    return $names;
  }
  // バッジと同じ判定 (wawahz_work_category) を使うため、メタとタームをまとめて先読みする。
  $ids = get_posts(array(
    'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
    'posts_per_page' => -1, 'paged' => 1, 'fields' => 'ids',
    'ignore_sticky_posts' => true,
    'tax_query' => array(array(
      'taxonomy' => 'category', 'field' => 'term_id',
      'terms' => $work->term_id, 'include_children' => true,
    )),
  ));
  update_meta_cache('post', $ids);
  update_object_term_cache($ids, 'post');
  $counts = array();
  foreach ($ids as $id) {
    $name = wawahz_work_category($id);
    $counts[$name] = isset($counts[$name]) ? $counts[$name] + 1 : 1;
  }
  $order = $counts;
  uksort($counts, function ($a, $b) use ($order) {
    return ($order[$b] <=> $order[$a]) ?: strcasecmp($a, $b);
  });
  $names = array_keys($counts);
  return $names;
}

/** ?filter= の値を検証し、存在する分類の正式表記 (または all) に寄せる。 */
function wawahz_resolve_work_filter($filter)
{
  if (!is_string($filter) || $filter === '' || strcasecmp($filter, 'all') === 0) {
    return 'all';
  }
  foreach (wawahz_work_filter_terms() as $name) {
    if (strcasecmp($name, $filter) === 0) {
      return $name;
    }
  }
  return 'all';
}

function wawahz_gallery_query($filter = 'all', $page = 1)
{
  $work = get_category_by_slug('work');
  $args = array(
    'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
    // デスクトップは 2列×2行 で1画面に収まる4件 (モバイルは1カラム縦並び)。
    // 作品が4件に満たない場合は「近日公開」カードで埋め、グリッドの形を保つ。
    'posts_per_page' => 4, 'paged' => max(1, absint($page)),
    'ignore_sticky_posts' => true,
    'orderby' => array('date' => 'DESC', 'ID' => 'DESC'),
  );
  if (!$work) {
    $args['post__in'] = array(0);
  } else {
    $args['tax_query'] = array(array(
      'taxonomy' => 'category', 'field' => 'term_id',
      'terms' => $work->term_id, 'include_children' => true,
    ));
    if ($filter !== 'all') {
      // Resolve badges before paging; load IDs and bulk caches, not full content.
      $ids = get_posts(array_merge($args, array(
        'posts_per_page' => -1, 'paged' => 1, 'fields' => 'ids',
      )));
      update_meta_cache('post', $ids);
      update_object_term_cache($ids, 'post');
      $matches = array_filter($ids, function ($id) use ($filter) {
        return strcasecmp(wawahz_work_category($id), $filter) === 0;
      });
      $args['post__in'] = $matches ? array_values($matches) : array(0);
    }
  }
  $query = new WP_Query($args);
  $last_page = max(1, (int) $query->max_num_pages);
  if ($args['paged'] > $last_page) {
    $args['paged'] = $last_page;
    $query = new WP_Query($args);
  }
  return $query;
}

function wawahz_filter_query($works = false)
{
  $sort = wawahz_request_value('sort', 'date-desc');
  $args = array(
    'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
    'posts_per_page' => max(1, min(24, (int) get_option('posts_per_page', 10))),
    'paged' => max(1, absint(wawahz_request_value('wawa_page', '1'))),
    'ignore_sticky_posts' => true,
    'orderby' => $sort === 'title-asc' ? 'title' : 'date',
    'order' => $sort === 'date-asc' || $sort === 'title-asc' ? 'ASC' : 'DESC',
  );
  $selected = get_category_by_slug(wawahz_request_value('cat_slug'));
  if ($works) {
    $work = get_category_by_slug('work');
    if (!$work) {
      $args['post__in'] = array(0);
    } else {
      $args['tax_query'] = array('relation' => 'AND', array('taxonomy' => 'category', 'field' => 'term_id', 'terms' => $work->term_id, 'include_children' => true));
      if ($selected) {
        $args['tax_query'][] = array('taxonomy' => 'category', 'field' => 'term_id', 'terms' => $selected->term_id);
      }
    }
  } elseif ($selected) {
    $args['cat'] = $selected->term_id;
  } elseif (wawahz_request_value('cat_slug')) {
    $args['post__in'] = array(0);
  }
  return new WP_Query($args);
}

// ?view=... は固定フロントページを設定した場合も機能させる。
add_filter('template_include', function ($template) {
  return wawahz_view() ? get_template_directory() . '/view.php' : $template;
});
