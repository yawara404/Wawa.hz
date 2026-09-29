<?php
/**
 * Read-only mini game screen (Gallery 画面2) regression checks.
 *
 * 実行例 (Local の MySQL ソケットを指定して CLI から実行する):
 *   php -d mysqli.default_socket="$HOME/Library/Application Support/Local/run/<site>/mysql/mysqld.sock" tests/mini-game-screen.php
 *
 * データベースは一切変更しない。
 */
if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}
require dirname(__DIR__, 4) . '/wp-load.php';
$checks = 0;
if (!function_exists('np_check')) {
  function np_check($condition, $message)
  {
    global $checks;
    if (!$condition) {
      throw new RuntimeException($message);
    }
    $checks++;
  }
}

// ?view=game（フロント画面）を is_home のクエリでエミュレートする (DB は変更しない)。
$original_query = $GLOBALS['wp_query'] ?? null;
$original_the_query = $GLOBALS['wp_the_query'] ?? null;
$home_query = new WP_Query(array('post_type' => 'post', 'posts_per_page' => 1));
$home_query->is_home = true;
$home_query->is_front_page = true;
$home_query->is_singular = false;
$GLOBALS['wp_query'] = $home_query;

$game_url = null;
$gallery_url = null;
foreach (array('gallery', 'game') as $view) {
  $_GET = array('view' => $view);
  np_check(wawahz_view() === $view, 'Gallery 画面の view を認識する: ' . $view);
  np_check(basename(apply_filters('template_include', '/index.php')) === 'view.php', '共有テンプレート (view.php) を選ぶ: ' . $view);
  np_check(wawahz_current_section() === 'gallery', 'ミニゲーム画面でもナビは Gallery がアクティブ: ' . $view);
}
$gallery_url = wawahz_view_url('gallery');
$game_url = wawahz_view_url('game');
np_check($game_url !== '' && $game_url !== $gallery_url, 'ミニゲーム画面は作品一覧とは別の URL を持つ');

// Gallery 画面1（作品一覧）: 右中央のボタンだけがミニゲーム画面へ進む。
$_GET = array('view' => 'gallery');
np_check(wawahz_slide_screen() === 'gallery', '作品一覧は Gallery 系のページ送り対象になる');
np_check(wawahz_slide_next_url() === $game_url, '作品一覧の次はミニゲーム画面');
np_check(wawahz_slide_prev_url() === '', '作品一覧は Gallery の先頭なので戻るボタンは無い');

// Gallery 画面2（ミニゲーム）: 左中央のボタンだけが作品一覧へ戻る。
$_GET = array('view' => 'game');
np_check(wawahz_slide_screen() === 'game', 'ミニゲームは Gallery 系のページ送り対象になる');
np_check(wawahz_slide_prev_url() === $gallery_url, 'ミニゲームの前は作品一覧');
np_check(wawahz_slide_next_url() === '', 'ミニゲームは Gallery の末尾なので次ボタンは無い');

// 既存のホーム3画面のページ送りは変わっていない。
$_GET = array();
$GLOBALS['wp_query'] = $home_query;
$home_query->is_paged = false;
np_check(wawahz_slide_screen() === 'home', 'ホームは従来どおり画面1');
np_check(wawahz_slide_next_url() === wawahz_view_url('category'), 'ホームの次はカテゴリー (従来どおり)');

$GLOBALS['wp_query'] = $original_query;
$GLOBALS['wp_the_query'] = $original_the_query;
$_GET = array();

// ナビゲーション項目は増えていない (Home / Search / Post / Gallery の4項目のまま)。
np_check(array_keys(wawahz_navigation()) === array('home', 'search', 'post', 'gallery'), 'ナビゲーション項目は増えていない');

// 画面の中身: 作品一覧からはミニゲーム枠が外れ、専用画面 (#screen-game) が描画される。
ob_start();
get_template_part('template-parts/works-gallery');
$works_html = ob_get_clean();
np_check(strpos($works_html, 'id="screen-gallery"') !== false, '作品一覧は #screen-gallery を描画する');
np_check(strpos($works_html, 'data-mini-game') === false, '作品一覧にミニゲーム枠は埋め込まれていない');
np_check(strpos($works_html, 'works-pagination') !== false, '作品一覧のページ送りは残っている');

ob_start();
get_template_part('template-parts/mini-game-screen');
$game_html = ob_get_clean();
np_check(strpos($game_html, 'id="screen-game"') !== false, 'ミニゲーム画面は #screen-game を描画する');
np_check(strpos($game_html, 'data-mini-game') !== false || strpos($game_html, 'game-screen-empty') !== false, 'ゲーム枠 (または準備中の案内) を表示する');

// フッターの表示範囲: 作品一覧 (#screen-gallery) ではフッターを出さない。
$footer_css = file_get_contents(get_template_directory() . '/css/footer.css');
np_check(
  strpos($footer_css, ':has(> .site-main > #screen-gallery) > .site-footer') !== false,
  '作品一覧ではサイトフッターを表示しない'
);

echo "Mini game screen: {$checks} checks passed." . PHP_EOL;
