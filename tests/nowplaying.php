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
require __DIR__ . '/theme-ui.php';
echo $checks . " checks passed; no posts changed.\n";
