<?php
/** Run with Local's PHP + php.ini. Uses fixtures; saves no posts and calls no external API. */
if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}
require dirname(__DIR__, 4) . '/wp-load.php';
$checks = 0;
function emb_check($condition, $message)
{
  global $checks;
  if (!$condition) {
    throw new RuntimeException($message);
  }
  $checks++;
}

// ---- URL パース (Wikipedia 系) ----
$music = 'https://ja.wikipedia.org/wiki/%E9%9F%B3%E6%A5%BD';
$info = wawahz_embed_parse_wiki_url($music);
emb_check(is_array($info), 'Wikipedia URL をパースできる');
emb_check($info['host'] === 'ja.wikipedia.org', 'ホスト');
emb_check($info['project'] === 'wikipedia.org', 'プロジェクト名');
emb_check($info['subdomain'] === 'ja', 'サブドメイン');
emb_check($info['title'] === '音楽', 'タイトルを URL デコード');
emb_check($info['site'] === 'Wikipedia', '表示名');

$mobile = wawahz_embed_parse_wiki_url('https://en.m.wikipedia.org/wiki/Music');
emb_check($mobile['host'] === 'en.wikipedia.org', 'モバイルホストを正規化');

$commons = wawahz_embed_parse_wiki_url('https://commons.wikimedia.org/wiki/File:Example.jpg');
emb_check($commons['site'] === 'Wikimedia Commons', 'Commons の表示名');

$wikidata = wawahz_embed_parse_wiki_url('https://www.wikidata.org/wiki/Q11424');
emb_check($wikidata['project'] === 'wikidata.org' && $wikidata['title'] === 'Q11424', 'Wikidata');

$index = wawahz_embed_parse_wiki_url('https://ja.wiktionary.org/w/index.php?title=test');
emb_check($index['site'] === 'Wiktionary' && $index['title'] === 'test', 'index.php 形式の URL');

$bare = wawahz_embed_parse_wiki_url('https://wikiquote.org/wiki/Foo');
emb_check($bare['subdomain'] === '' && $bare['site'] === 'Wikiquote', 'サブドメイン無しドメイン直下');

foreach (array(
  'https://example.com/wiki/Foo',
  'https://ja.wikipedia.org.evil.test/wiki/Foo',
  'https://ja.wikipedia.org/',
  'javascript:alert(1)',
  'not a url',
) as $rejected) {
  emb_check(wawahz_embed_parse_wiki_url($rejected) === false, '対象外 URL を拒否: ' . $rejected);
}

// ---- ニコニコ動画の URL マッチ (ネットワーク不要) ----
foreach (array(
  'https://www.nicovideo.jp/watch/sm31819477',
  'https://nicovideo.jp/watch/so9000',
  'https://embed.nicovideo.jp/watch/nm123',
  'https://www.nicovideo.jp/watch/sm31819477?ref=xyz',
) as $nico_ok) {
  emb_check((bool) preg_match(wawahz_embed_nicovideo_regex(), $nico_ok), 'nico URL: ' . $nico_ok);
}
emb_check((bool) preg_match(wawahz_embed_nico_ms_regex(), 'https://nico.ms/sm31819477'), 'nico.ms 短縮 URL');
foreach (array(
  'https://nicovideo.jp/watch/',
  'https://evil.test/watch/sm1',
  'https://www.nicovideo.jp/watch/abc',
) as $nico_bad) {
  emb_check(!preg_match(wawahz_embed_nicovideo_regex(), $nico_bad), '対象外 nico URL: ' . $nico_bad);
}

emb_check(wawahz_embed_is_supported_url($music) === true, '対象判定 (Wikipedia)');
emb_check(wawahz_embed_is_supported_url('https://www.nicovideo.jp/watch/sm1') === true, '対象判定 (nico)');
emb_check(wawahz_embed_is_supported_url('https://www.youtube.com/watch?v=dQw4w9WgXcQ') === false, '対象判定 (対象外)');
emb_check(wawahz_embed_render_url('https://example.com/x') === '', '対象外 URL は空文字を返す');

// ---- ニコニコ動画のプレイヤー ----
$nico = 'https://www.nicovideo.jp/watch/sm31819477';
$player = wawahz_embed_render_url($nico);
emb_check($player !== '', 'プレイヤーを描画');
emb_check(strpos($player, '<iframe src="https://embed.nicovideo.jp/watch/sm31819477"') !== false, '公式埋め込みプレイヤー');
emb_check(strpos($player, 'wawahz-embed__frame') !== false, '16:9 のフレームを持つ');
emb_check(strpos($player, "\n") === false, 'wpautop に壊されない 1 行出力');
emb_check(strpos($player, '<script') === false, 'script を出力しない');
emb_check(strpos($player, 'rel="noopener noreferrer"') !== false, 'リンクに rel 付与');
emb_check(wawahz_embed_render_nicovideo('<script>') === '', '不正な動画 ID を拒否');
emb_check(strpos(wawahz_embed_render_nicovideo('sm31819477'), 'embed.nicovideo.jp/watch/sm31819477') !== false, '正しい動画 ID なら直接呼び出しでも描画できる');

// ---- サマリーカード (要約は transient に事前投入して API を呼ばない) ----
$summary = array(
  'site'        => 'Wikipedia',
  'host'        => 'ja.wikipedia.org',
  'title'       => '音楽',
  'description' => '芸術表現',
  'extract'     => '音楽 とは、音を主に用いた芸術表現である。',
  'image'       => 'https://upload.wikimedia.org/thumb/example.jpg/330px-example.jpg',
  'width'       => 330,
  'height'      => 220,
  'page'        => $music,
  'lang'        => 'ja',
);
$transient_key = 'wawahz_embed_wiki_' . md5('ja.wikipedia.org|音楽');
set_transient($transient_key, array('status' => 'ok', 'data' => $summary), 60);

$card = wawahz_embed_render_url($music);
emb_check(strpos($card, 'wawahz-embed__card') !== false, 'カードを描画');
emb_check(strpos($card, '>音楽<') !== false, 'タイトルを表示');
emb_check(strpos($card, '芸術表現') !== false, '説明を表示');
emb_check(strpos($card, '330px-example.jpg') !== false, 'サムネイルを表示');
emb_check(strpos($card, 'ja.wikipedia.org') !== false, 'ホスト名を表示');
emb_check(strpos($card, "\n") === false, 'カードも 1 行出力');
emb_check(strpos(wawahz_embed_render_wiki_card('https://ja.wikipedia.org/wiki/%E3%81%93%E3%82%93%E3%81%AB%E3%81%A3%E3%81%9D%E3%82%93', array()), 'wawahz-embed--nothumb') !== false, '要約が取れなくてもサムネ無しカード');

// ---- the_content 全経路 ----
$rendered = apply_filters('the_content', "導入文です。\n\n" . $music . "\n\n結びです。");
emb_check(strpos($rendered, 'wawahz-embed__card') !== false, '本文中の単独行 URL がカードになる');
emb_check(substr_count($rendered, '<p>') === substr_count($rendered, '</p>'), '前後の段落が残る');
emb_check(strpos($rendered, '<br />') === false, 'wpautop が <br> を混入させない');
emb_check(substr_count($rendered, '<figure') === substr_count($rendered, '</figure>'), 'figure が閉じる');

$alone = apply_filters('the_content', $music);
emb_check(substr_count($alone, '<p>') === 0, 'URL 単独行は p で包まれない');

$block = "<!-- wp:embed {\"url\":\"" . $music . "\",\"type\":\"rich\"} -->\n<figure class=\"wp-block-embed\"><div class=\"wp-block-embed__wrapper\">\n" . $music . "\n</div></figure>\n<!-- /wp:embed -->";
$block_out = apply_filters('the_content', $block);
emb_check(strpos($block_out, 'wawahz-embed__card') !== false, '埋め込みブロックの URL もカードになる');
emb_check(strpos($block_out, 'wawahz-embed-host') !== false, '16:9 の黒い枠を解除する印を付ける');

$nico_block = "<!-- wp:embed {\"url\":\"" . $nico . "\",\"type\":\"rich\"} -->\n<figure class=\"wp-block-embed\"><div class=\"wp-block-embed__wrapper\">\n" . $nico . "\n</div></figure>\n<!-- /wp:embed -->";
$nico_out = apply_filters('the_content', $nico_block);
emb_check(strpos($nico_out, 'wawahz-embed__frame') !== false, 'ブロック内の nico もプレイヤーになる');
emb_check(substr_count($nico_out, '<p>') === substr_count($nico_out, '</p>'), 'プレイヤー側の p タグも閉じる');

$youtube = "<!-- wp:embed {\"url\":\"https://www.youtube.com/watch?v=dQw4w9WgXcQ\",\"type\":\"video\"} -->\n<figure class=\"wp-block-embed is-provider-youtube\"><div class=\"wp-block-embed__wrapper\"><iframe src=\"https://www.youtube.com/embed/dQw4w9WgXcQ\"></iframe></div></figure>\n<!-- /wp:embed -->";
$youtube_out = apply_filters('the_content', $youtube);
emb_check(stripos($youtube_out, '<iframe') !== false, 'YouTube の iframe は残る');
emb_check(strpos($youtube_out, 'wawahz-embed-host') === false, '他サイトの埋め込みには印を付けない');

// ---- エディタの埋め込みプレビュー経路 (/wp-json/oembed/1.0/proxy) ----
global $wp_embed;
$preview = $wp_embed->get_embed_handler_html(array(), $music);
emb_check(is_string($preview) && strpos($preview, 'wawahz-embed__card') !== false, 'エディタのプレビューでもカードになる');

delete_transient($transient_key);
echo $checks . " checks passed; no posts changed.\n";
