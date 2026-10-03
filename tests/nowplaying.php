<?php
/** Run with Local's PHP + php.ini. Uses in-memory fixtures; never saves posts. */
if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}
require dirname(__DIR__, 4) . '/wp-load.php';
$checks = 0;
function np_check($condition, $message) {
  global $checks;
  if (!$condition) {
    throw new RuntimeException($message);
  }
  $checks++;
}
$id = 'abcdefghijk';
foreach (array($id, 'https://youtu.be/' . $id, 'https://www.youtube.com/watch?v=' . $id . '&t=20', 'https://youtube.com/shorts/' . $id, 'https://youtube.com/live/' . $id) as $url) {
  np_check(wawahz_youtube_id($url) === $id, 'Valid YouTube URL');
}
foreach (array('https://evil.test/watch?v=' . $id, 'https://youtube.com.evil.test/watch?v=' . $id, 'javascript:abcdefghijk', 'https://youtube.com/watch?v[]=x', 'bad') as $url) {
  np_check(wawahz_youtube_id($url) === '', 'Reject invalid YouTube URL');
}
$audio = '<!-- wp:audio --><figure class="wp-block-audio"><audio autoplay src="https://example.org/music.mp3"></audio><figcaption>Audio caption</figcaption></figure><!-- /wp:audio -->';
$video = '<!-- wp:video --><figure class="wp-block-video"><video autoplay poster="https://example.org/poster.jpg" src="https://example.org/video.mp4"><track kind="captions" src="https://example.org/captions.vtt" srclang="ja"></video></figure><!-- /wp:video -->';
$youtube = '<!-- wp:embed {"url":"https://www.youtube.com/watch?v=abcdefghijk","providerNameSlug":"youtube"} --><figure class="wp-block-embed"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=abcdefghijk</div></figure><!-- /wp:embed -->';
foreach (array('audio' => $audio, 'video' => $video, 'youtube' => $youtube) as $type => $content) {
  $media = wawahz_find_media_block(parse_blocks($content));
  np_check($media['type'] === $type, 'Block detection: ' . $type);
  $html = wawahz_render_gallery_media($media);
  np_check(strpos($html, $type === 'youtube' ? 'youtube.com/embed/abcdefghijk' : '<' . $type . ' controls preload="none"') !== false, 'Playable output: ' . $type);
  np_check(!preg_match('/<(audio|video)[^>]*\bautoplay/', $html), 'No autoplay');
  if ($type === 'video') {
    np_check(strpos($html, '<track') !== false && strpos($html, 'poster.jpg') !== false, 'Keep captions and poster');
  }
}
$nested = '<!-- wp:group --><div class="wp-block-group">' . $audio . $video . '</div><!-- /wp:group -->';
np_check(wawahz_find_media_block(parse_blocks($nested))['type'] === 'audio', 'Nested block and first media priority');
np_check(wawahz_find_media_block(parse_blocks('<!-- wp:paragraph --><p>Text only</p><!-- /wp:paragraph -->')) === null, 'No media means no track');
$unsafe = str_replace('<audio autoplay', '<audio onplay="alert(1)" autoplay', $audio);
np_check(strpos(wawahz_render_gallery_media(wawahz_find_media_block(parse_blocks($unsafe))), 'onplay') === false, 'Strip executable attributes');

// 埋め込みブロックに <iframe> が無い (autoembed に掛からなかった) 場合は公式プレイヤーを直接描画する。
$embed_fallback = '<!-- wp:embed {"url":"https://www.youtube.com/watch?v=abcdefghijk","type":"video","providerNameSlug":"youtube"} -->'
  . '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">https://www.youtube.com/watch?v=abcdefghijk</div></figure>'
  . '<!-- /wp:embed -->';
$embed_block = parse_blocks($embed_fallback)[0];
$rendered = wawahz_render_playlist_embed($embed_block['innerHTML'], $embed_block);
np_check(strpos($rendered, 'youtube.com/embed/abcdefghijk') !== false, 'Fallback embed renders the official iframe');
np_check(strpos($rendered, '>https://www.youtube.com/watch?v=') === false, 'Bare URL fallback is replaced');
np_check(strpos($rendered, 'wp-block-embed__wrapper') !== false, 'Embed figure markup is preserved');

// 旧ブロック名 (core-embed/youtube) の保存済み投稿も同じく修復する。
$legacy_block = array('blockName' => 'core-embed/youtube', 'attrs' => array('url' => 'https://www.youtube.com/watch?v=abcdefghijk'));
$legacy_rendered = wawahz_render_playlist_embed($embed_block['innerHTML'], $legacy_block);
np_check(strpos($legacy_rendered, 'youtube.com/embed/abcdefghijk') !== false, 'Legacy embed block name is handled');

// iframe が既にあるときは触らない (二重埋め込みしない)。
$already = '<figure class="wp-block-embed"><div class="wp-block-embed__wrapper"><iframe src="https://www.youtube.com/embed/abcdefghijk"></iframe></div></figure>';
np_check(wawahz_render_playlist_embed($already, $embed_block) === $already, 'Existing iframe is kept as-is');

// プレイリストは videoseries 埋め込みのまま。
$playlist_block = array('blockName' => 'core/embed', 'attrs' => array('url' => 'https://www.youtube.com/playlist?list=PLI-z8IB_57vJxbuz8eQ9LrW_WSt1dzBju'));
$playlist_html = '<figure class="wp-block-embed"><div class="wp-block-embed__wrapper">https://www.youtube.com/playlist?list=PLI-z8IB_57vJxbuz8eQ9LrW_WSt1dzBju</div></figure>';
np_check(strpos(wawahz_render_playlist_embed($playlist_html, $playlist_block), 'videoseries?list=PLI-z8IB_57vJxbuz8eQ9LrW_WSt1dzBju') !== false, 'Playlist still renders the videoseries iframe');

// 対象外のブロック・対象外のサイトはそのまま。
np_check(wawahz_render_playlist_embed('<p>text</p>', array('blockName' => 'core/paragraph', 'attrs' => array())) === '<p>text</p>', 'Non-embed blocks are untouched');
$twitter = '<figure class="wp-block-embed"><div class="wp-block-embed__wrapper">https://twitter.com/user/status/1</div></figure>';
np_check(wawahz_render_playlist_embed($twitter, array('blockName' => 'core-embed/twitter', 'attrs' => array('url' => 'https://twitter.com/user/status/1'))) === $twitter, 'Non-YouTube providers are untouched');

// Simulate a media post older than the first 50 posts without changing the database.
$fixture = new WP_Post((object) array('ID' => 999999, 'post_author' => 1, 'post_date' => '2026-09-18 00:00:00', 'post_date_gmt' => '2026-09-17 15:00:00', 'post_title' => 'Now Playing test', 'post_content' => $audio, 'post_excerpt' => '', 'post_status' => 'publish', 'post_type' => 'post', 'post_password' => '', 'post_name' => 'nowplaying-test', 'filter' => 'raw'));
$empty = clone $fixture;
$empty->post_content = '';
$filter = function ($posts, $query) use ($fixture, $empty) {
  if ($query->get('posts_per_page') !== 50) { return $posts; }
  np_check($query->get('post_status') === 'publish' && $query->get('has_password') === false, 'Only public unprotected posts requested');
  return (int) $query->get('paged') === 1 ? array_fill(0, 50, $empty) : array($fixture);
};
add_filter('posts_pre_query', $filter, 10, 2);
$tracks = wawahz_nowplaying_posts(1);
np_check(count($tracks) === 1 && $tracks[0]['media']['type'] === 'audio', 'Find older media post');
$html = wawahz_render_nowplaying_block(array('limit' => 1, 'showHeader' => false));
np_check(strpos($html, 'nowplaying-header-row') === false && strpos($html, '<audio controls') !== false, 'Gutenberg header toggle and audio rendering');
$html = wawahz_nowplaying_gallery_shortcode(array('limit' => 1, 'show_header' => 'false'));
np_check(strpos($html, 'nowplaying-header-row') === false, 'Shortcode header toggle');
remove_filter('posts_pre_query', $filter, 10);

// Now Playing は楽曲カテゴリ (music) の投稿だけを列挙する (通常投稿と分離)。
$music_id = wawahz_music_category_id();
if ($music_id) {
  $all_music = true;
  foreach (wawahz_nowplaying_posts(50) as $track) {
    if (!in_array($music_id, wp_get_post_categories($track['post']->ID), true)) {
      $all_music = false;
      break;
    }
  }
  np_check($all_music, 'Now Playing only lists music-category posts');
}

require __DIR__ . '/theme-ui.php';
echo $checks . " checks passed; no posts changed.\n";
