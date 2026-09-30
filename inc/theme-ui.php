<?php
/** WordPress の URL・メニュー・カード・ページ送りを共通化。 */
function wawahz_request_value($key, $default = '')
{
  return isset($_GET[$key]) && is_string($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : $default;
}

function wawahz_view()
{
  $view = wawahz_request_value('view');
  return (is_home() || is_front_page()) && in_array($view, array('category', 'post', 'gallery', 'game', 'nowplaying', 'profile', 'info'), true) ? $view : '';
}

/**
 * 指定した view に対応する固定ページを返す (優先順あり)。無ければ null。
 * Now Playing は page-music.php → page-nowplaying.php の順に探す。
 */
function wawahz_view_page($view)
{
  $templates = array(
    'gallery'    => array('page-gallery.php'),
    'game'       => array('page-game.php'),
    'nowplaying' => array('page-music.php', 'page-nowplaying.php'),
    'profile'    => array('page-profile.php'),
  );
  if (!isset($templates[$view])) {
    return null;
  }
  foreach ($templates[$view] as $template) {
    $found = get_posts(array(
      'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 1,
      'meta_key' => '_wp_page_template', 'meta_value' => $template,
      'orderby' => 'ID', 'order' => 'ASC',
    ));
    if ($found) {
      return $found[0];
    }
  }
  return null;
}

function wawahz_view_url($view)
{
  static $urls = array();
  if (!isset($urls[$view])) {
    $page = wawahz_view_page($view);
    $urls[$view] = $page ? get_permalink($page) : add_query_arg('view', $view, home_url('/'));
  }
  return $urls[$view];
}

/**
 * 横スライドで巡回する画面の現在位置を返す。
 *   ホーム系: 画面1 = home（ホーム） / 画面2 = category（カテゴリー・検索） / 画面3 = info（情報欄）
 *   Gallery系: 画面1 = gallery（作品一覧） / 画面2 = game（ミニゲーム）
 * これら以外（Post / Nowplaying / 検索 / 記事ページなど）では空文字を返す。
 */
function wawahz_slide_screen()
{
  if (is_home() || is_front_page()) {
    $view = wawahz_view();
    if ($view === 'category' || $view === 'info') {
      return $view;
    }
    // Gallery の2画面（画面1 作品一覧 ⇄ 画面2 ミニゲーム）も同じページ送りでつなぐ。
    if ($view === 'gallery' || $view === 'game') {
      return $view;
    }
    if ($view === '' && !is_paged()) {
      return 'home';
    }
  }
  // 固定ページにテンプレートを割り当てて表示する場合（?view= なし）も同じ画面として扱う。
  if (is_page_template('page-gallery.php')) {
    return 'gallery';
  }
  if (is_page_template('page-game.php')) {
    return 'game';
  }
  return '';
}

/**
 * 「次の画面」の URL（横スライドで進む）。
 *   ホーム: ホーム → カテゴリー → 情報欄（末尾では空文字）
 *   Gallery: 作品一覧 → ミニゲーム（末尾では空文字）
 * 対象外の画面では空文字を返す。
 */
function wawahz_slide_next_url()
{
  switch (wawahz_slide_screen()) {
    case 'home':
      return wawahz_view_url('category');
    case 'category':
      return wawahz_view_url('info');
    case 'gallery':
      return wawahz_view_url('game');
  }
  return '';
}

/**
 * 「前の画面」の URL（横スライドで戻る）。
 *   ホーム: カテゴリー → ホーム / 情報欄 → カテゴリー（先頭では空文字）
 *   Gallery: ミニゲーム → 作品一覧（先頭では空文字）
 * 対象外の画面では空文字を返す。
 */
function wawahz_slide_prev_url()
{
  switch (wawahz_slide_screen()) {
    case 'category':
      return home_url('/');
    case 'info':
      return wawahz_view_url('category');
    case 'game':
      return wawahz_view_url('gallery');
  }
  return '';
}

/**
 * ページ送りボタンの下に出す「次の画面 / 前の画面」の英字ガイド。
 */
function wawahz_slide_next_label()
{
  switch (wawahz_slide_screen()) {
    case 'home':
      return 'category';
    case 'category':
      return 'info';
    case 'gallery':
      return 'minigame';
  }
  return '';
}

function wawahz_slide_prev_label()
{
  switch (wawahz_slide_screen()) {
    case 'category':
      return 'home';
    case 'info':
      return 'category';
    case 'game':
      return 'works';
  }
  return '';
}

function wawahz_current_section()
{
  $v = wawahz_view();
  if ($v === 'post') { return 'post'; }
  if ($v === 'gallery' || $v === 'game') { return 'gallery'; }
  if ($v === 'category') { return 'category'; }
  if ($v === 'nowplaying') { return 'nowplaying'; }
  if ($v === 'info') { return 'info'; }
  if (is_page_template('page-gallery.php') || is_page_template('page-game.php')) { return 'gallery'; }
  if (is_page_template('page-nowplaying.php') || is_page_template('page-music.php')) { return 'nowplaying'; }
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

/** Works ギャラリーで「実際に見えている枚数」。画面サイズに応じて js/theme.js が gallery_per_page で指定する。 */
function wawahz_gallery_per_page()
{
  $allowed = array(4, 6, 8, 10);
  $value = (int) wawahz_request_value('gallery_per_page', '4');
  return in_array($value, $allowed, true) ? $value : 4;
}

/** Works グリッドの最大枠数 (Post 画面と同じ 10)。画面が大きいほど表示する枠が増える。 */
function wawahz_gallery_slots()
{
  return 10;
}

function wawahz_gallery_query($filter = 'all', $page = 1)
{
  $per_page = wawahz_gallery_per_page();
  $page = max(1, absint($page));
  $work = get_category_by_slug('work');
  $args = array(
    'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
    // 画面が大きいほど段数＝枚数が増えるため最大10件を取得する
    // (実際に見せるのは per_page 枚。5〜10枠は CSS が画面サイズで出し分ける)。
    'posts_per_page' => wawahz_gallery_slots(),
    // ページ送りは「実際に見えている枚数 (per_page)」ぶんだけ進める。
    // offset を使うため paged は 1 に固定する (取りこぼし・重複を防ぐ)。
    'offset' => ($page - 1) * $per_page,
    'paged' => 1,
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
        'posts_per_page' => -1, 'offset' => 0, 'paged' => 1, 'fields' => 'ids',
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
  // offset を使うと max_num_pages は posts_per_page (10) 基準になるため、per_page で計算し直す。
  $max_pages = max(1, (int) ceil((int) $query->found_posts / $per_page));
  if ($page > $max_pages) {
    $page = $max_pages;
    $args['offset'] = ($page - 1) * $per_page;
    $query = new WP_Query($args);
  }
  $query->set('wawahz_gallery_page', $page);
  $query->set('wawahz_gallery_per_page', $per_page);
  $query->set('wawahz_gallery_max_pages', $max_pages);
  return $query;
}

function wawahz_filter_query($works = false)
{
  $sort = wawahz_request_value('sort', 'date-desc');
  $music_id = wawahz_music_category_id();
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
    // 楽曲は Now Playing 側なので Gallery からも除外する。
    if ($music_id) {
      $args['category__not_in'] = array($music_id);
    }
  } elseif ($selected) {
    $args['cat'] = $selected->term_id;
  } elseif (wawahz_request_value('cat_slug')) {
    $args['post__in'] = array(0);
  } elseif ($music_id) {
    // 通常の記事一覧では楽曲カテゴリ (music) を除外する (Now Playing と分離)。
    $args['category__not_in'] = array($music_id);
  }
  return new WP_Query($args);
}

// ?view=... は固定フロントページを設定した場合も機能させる。
add_filter('template_include', function ($template) {
  return wawahz_view() ? get_template_directory() . '/view.php' : $template;
});

/**
 * ?view=... の画面ごとのメタ情報 (タイトル / 説明 / canonical URL)。
 * 固定ページを作成していない状態でも、各画面が
 * 「トップページと同じタイトル・canonical・OGP」になる問題を補正する。
 *
 * @return array|null
 */
function wawahz_view_seo()
{
  $view = wawahz_view();
  if (!$view) {
    return null;
  }
  $map = array(
    'gallery'    => array('Works & Creations', '自作キーボード、Web Audio、モノクロ写真、UIシステム。思索と触感を形にした制作物アーカイブ。'),
    'game'       => array('Mini Game', '制作の合間にどうぞ。授業の最終課題でつくったちいさな1作です。'),
    'nowplaying' => array('Now Playing Gallery', '最近聴いている音楽とアンビエント和音スケッチ。'),
    'profile'    => array('Profile', 'プロフィール。制作と音楽の記録。'),
    'post'       => array('Posts', 'ブログ記事の一覧。'),
    'category'   => array('Category', 'カテゴリーから記事を探す。'),
    'info'       => array('Site Info', 'サイト情報・運営者情報。'),
  );
  if (!isset($map[$view])) {
    return null;
  }
  return array(
    'title'       => $map[$view][0],
    'description' => $map[$view][1],
    'url'         => wawahz_view_url($view),
  );
}

/**
 * SEO SIMPLE PACK が有効なとき、?view=... の画面ごとに
 * タイトル・説明・canonical・OGP を上書きする (プラグインが無ければ何もしない)。
 */
function wawahz_view_seo_value($key, $fallback)
{
  $meta = wawahz_view_seo();
  if (!$meta || !isset($meta[$key])) {
    return $fallback;
  }
  if ($key === 'title') {
    $blogname = get_bloginfo('name');
    return $blogname ? $meta['title'] . ' | ' . $blogname : $meta['title'];
  }
  return $meta[$key];
}

foreach (array('title', 'description', 'canonical', 'og_title', 'og_description', 'og_url') as $wawahz_seo_key) {
  add_filter('ssp_output_' . $wawahz_seo_key, function ($value) use ($wawahz_seo_key) {
    $map = array(
      'title'          => 'title',
      'description'    => 'description',
      'canonical'      => 'url',
      'og_title'       => 'title',
      'og_description' => 'description',
      'og_url'         => 'url',
    );
    return wawahz_view_seo_value($map[$wawahz_seo_key], $value);
  });
}

// 通常の一覧 (アーカイブ / ホーム / ブログ) からは楽曲カテゴリ (music) を除外して、
// Now Playing と通常投稿を分離する。検索と Music カテゴリのアーカイブでは除外しない。
add_action('pre_get_posts', function ($query) {
  if (is_admin() || !$query->is_main_query() || $query->is_singular() || $query->is_feed()) {
    return;
  }
  if ($query->is_search()) {
    return;
  }
  $music_id = wawahz_music_category_id();
  if (!$music_id) {
    return;
  }
  if ($query->is_category() && (int) $query->get_queried_object_id() === $music_id) {
    return;
  }
  $exclude = array_filter((array) $query->get('category__not_in'));
  $exclude[] = $music_id;
  $query->set('category__not_in', array_values(array_unique($exclude)));
});

// 検索画面のカテゴリフィルタ (?cat=<slug>): 検索結果をカテゴリで絞り込む。
add_action('pre_get_posts', function ($query) {
  if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
    return;
  }
  $slug = wawahz_request_value('cat');
  if ($slug === '') {
    return;
  }
  $term = get_category_by_slug($slug);
  if ($term && !is_wp_error($term)) {
    $query->set('cat', (int) $term->term_id);
  }
});

/** サイト既定の OGP 画像 (ロゴマーク)。SEO SIMPLE PACK が画像未設定のときに使う。 */
function wawahz_default_og_image_url()
{
  return get_template_directory_uri() . '/images/ogp.png';
}

// og:image が未設定ならロゴの OGP 画像 (1200x630) を使う。
add_filter('ssp_output_og_image', function ($image) {
  return $image ? $image : wawahz_default_og_image_url();
});

// 大きな画像カード (1200x630) を使う。
add_filter('ssp_output_tw_card', function () {
  return 'summary_large_image';
});
