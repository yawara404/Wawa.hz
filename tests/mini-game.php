<?php
/**
 * Read-only mini game regression checks.
 *
 * 実行例 (Local の MySQL ソケットを指定して CLI から実行する):
 *   php -d mysqli.default_socket="$HOME/Library/Application Support/Local/run/<site>/mysql/mysqld.sock" tests/mini-game.php
 *
 * データベースは一切変更しない。
 */
if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}
require dirname(__DIR__, 4) . '/wp-load.php';
$checks = 0;
function np_check($condition, $message)
{
  global $checks;
  if (!$condition) {
    throw new RuntimeException($message);
  }
  $checks++;
}

$game_dir = get_template_directory() . '/games/falling-survivor';
$embed_page = $game_dir . '/index.html';
np_check(file_exists($embed_page), 'Embedded game page exists');

$embed_html = file_get_contents($embed_page);
foreach (array('lib/p5.min.js', 'lib/planck.min.js', 'lib/p5play.min.js', 'sketch.js', 'embed.js', 'touch.js') as $asset) {
  np_check(strpos($embed_html, $asset) !== false, 'Embed page loads ' . $asset);
  np_check(file_exists($game_dir . '/' . $asset), 'Asset exists: ' . $asset);
}
np_check(preg_match('#https?://#', $embed_html) === 0, 'Embed page has no external dependency (CDN free)');
np_check(strpos($embed_html, 'name="robots"') !== false, 'Embed page is excluded from search indexes');

// 単体ページとして開いたときも中央に収まるようにしている (iframe 内では wrapper 依存の幅計算になるため)
np_check(substr_count($embed_html, 'data-game-key=') === 5, 'In-page touch pad maps four arrows and START');
np_check(strpos($embed_html, 'is-standalone') !== false && strpos($embed_html, 'is-embedded') !== false, 'Page detects standalone vs iframe before paint');
np_check(
  strpos($embed_html, '--game-stage-width') !== false && strpos($embed_html, 'body>main') !== false,
  'Canvas wrapper (p5play <main>) is sized for the responsive fit'
);
np_check(strpos($embed_html, 'place-items: center') === false, 'Grid centering (which broke on Retina) is not used');

$sketch = file_get_contents($game_dir . '/sketch.js');
np_check(strpos($sketch, 'Falling Survivor') !== false, 'Game logic (sketch.js) is present');
np_check(strpos($sketch, 'images/character_monster_slime_green.png') !== false, 'Game loads its local sprite sheets');
np_check(file_exists($game_dir . '/images/character_monster_slime_green.png'), 'Player sprite exists');
np_check(file_exists($game_dir . '/images/yurei_01.png'), 'Enemy sprite exists');

$embed_js = file_get_contents($game_dir . '/embed.js');
np_check(strpos($embed_js, 'wawahzMiniGame') !== false, 'Embed adapter exposes the parent control API');
np_check(strpos($embed_js, 'visibilitychange') !== false, 'Embed adapter pauses in hidden tabs');

// 単体ページ用の画面内操作キー (タッチ端末のみ / iframe 内では親ページのパッドに任せる)
$touch_js = file_get_contents($game_dir . '/touch.js');
np_check(strpos($touch_js, 'KeyboardEvent') !== false, 'In-page pad forwards keyboard events to the game');
np_check(strpos($touch_js, 'pointerdown') !== false, 'In-page pad reacts to touch/pointer input');
np_check(strpos($touch_js, "window.self !== window.top") !== false, 'In-page pad stays hidden inside the iframe');
np_check(strpos($touch_js, 'is-touch-pad') !== false, 'In-page pad reserves space for itself');

// 画面内キーの配置: 十字キーは画面 (ゲーム画面) の左右中央、START は中央揃えを崩さないよう右端へ逃がす
np_check(
  preg_match('#\.game-pad\s*\{[^}]*justify-content:\s*center#s', $embed_html) === 1,
  'In-page pad centers the D-pad horizontally'
);
np_check(
  preg_match('#\.game-pad-btn--start\s*\{[^}]*position:\s*absolute[^}]*right:\s*0#s', $embed_html) === 1,
  'In-page START is parked at the right edge so it cannot shift the D-pad'
);
np_check(
  strpos($embed_html, '@media (max-width: 380px)') !== false && strpos($embed_html, 'position: static') !== false,
  'Narrow screens stack START below the D-pad instead of overlapping it'
);
// p5play は読み込み時に `html, body { margin: 0; padding: 0 }` を注入するため、body 側は特異度を上げて勝たせる
np_check(
  preg_match('#html\.is-standalone body,\s*html\.is-embedded body\s*\{#', $embed_html) === 1,
  'Body layout still wins over the stylesheet p5play injects at runtime'
);

// テーマ側テンプレート: クリックするまで iframe を出力しない (遅延読み込み)
ob_start();
get_template_part('template-parts/mini-game');
$section = ob_get_clean();
np_check(strpos($section, 'mini-game-section') !== false, 'Mini game section renders');
np_check(strpos($section, 'data-mini-game-start') !== false, 'Start button present');
np_check(
  preg_match('#data-game-src="[^"]*games/falling-survivor/index\.html\?v=\d+"#', $section) === 1,
  'Start button points at the versioned game URL'
);
np_check(strpos($section, '<iframe') === false, 'No iframe markup before launch (lazy loading)');
np_check(substr_count($section, 'data-game-key=') === 5, 'Touch pad maps four arrows and START');
np_check(strpos($section, 'rel="noopener noreferrer"') !== false, 'New tab link is safe');
np_check(strpos($section, 'sports_esports') !== false, 'Material icon is used for the heading');

// フロント側スクリプト
$script_path = get_template_directory() . '/js/mini-game.js';
np_check(file_exists($script_path), 'Front-end helper script exists');
$script = file_get_contents($script_path);
np_check(strpos($script, 'KeyboardEvent') !== false, 'Touch pad forwards keyboard events to the frame');
np_check(strpos($script, 'IntersectionObserver') !== false, 'Off-screen pausing is implemented');
np_check(strpos($script, 'wawahzMiniGame') !== false, 'Helper talks to the game API');
np_check(strpos($script, '(max-width: 760px)') !== false, 'Pad also appears on mobile widths (no touch detection needed)');

// スタイルとエンキュー条件
$css_path = get_template_directory() . '/css/mini-game.css';
np_check(file_exists($css_path), 'Mini game stylesheet exists');
np_check(
  strpos(file_get_contents(get_stylesheet_directory() . '/style.css'), 'css/mini-game.css') !== false,
  'style.css imports the mini game stylesheet'
);
np_check(strpos(file_get_contents($css_path), 'color-mix') !== false, 'Stylesheet uses the Material 3 tokens');
np_check(has_action('wp_enqueue_scripts', 'wawahz_scripts') !== false, 'Theme assets are enqueued');
np_check(strpos(file_get_contents(get_template_directory() . '/functions.php'), 'wawahz-mini-game') !== false, 'Mini game script handle is registered');

echo "Mini game: {$checks} checks passed." . PHP_EOL;
