<?php
/**
 * Now Playing 楽曲追加モーダル (AJAX) の検証。
 * 検証中に下書き投稿を1件作成し、必ず削除する。既存の投稿は変更しない。
 * Run with Local's PHP + php.ini:
 *   php -c <php.ini> tests/add-track.php
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

function np_expect_error($result, $code, $message)
{
  np_check(is_wp_error($result) && $result->get_error_code() === $code, $message . ' (' . $code . ')');
}

$video_id = 'abcdefghijk';
$youtube_url = 'https://www.youtube.com/watch?v=' . $video_id;
$valid_input = array(
  'title'       => 'Rain & Resonator',
  'youtube_url' => $youtube_url,
  'artist'      => 'wawa.hz sound lab',
  'album'       => 'Weather Tones',
  'mood'        => 'Ambient & Chill',
  'commentary'  => '雨音と金属板の共鳴スケッチ。',
  'category'    => '0',
  'status'      => 'draft',
);

// 追加は常に楽曲カテゴリ (music) だけに所属する
$music_term = wawahz_music_category();
$music_category_id = $music_term ? (int) $music_term->term_id : 0;

// 1. 入力検証（データベースに触れない純粋処理）
$data = wawahz_add_track_validate($valid_input);
np_check(is_array($data), 'Valid input is accepted');
np_check($data['youtube_id'] === $video_id, 'Video ID is extracted');
np_check($data['youtube_url'] === $youtube_url, 'YouTube URL is normalized');
np_check($data['status'] === 'draft' && $data['category'] === $music_category_id, 'Status is kept and the music category is applied');
np_check($data['title'] === 'Rain & Resonator' && $data['mood'] === 'Ambient & Chill', 'Text values are kept');

np_expect_error(wawahz_add_track_validate(array('youtube_url' => $youtube_url)), 'title_required', 'Title is required');
np_expect_error(wawahz_add_track_validate(array('title' => '   ', 'youtube_url' => $youtube_url)), 'title_required', 'Blank title is rejected');
np_expect_error(wawahz_add_track_validate(array('title' => 'A', 'youtube_url' => 'https://evil.test/watch?v=' . $video_id)), 'youtube_invalid', 'Foreign host is rejected');
np_expect_error(wawahz_add_track_validate(array('title' => 'A', 'youtube_url' => 'abcdefghij')), 'youtube_invalid', 'Short video ID is rejected');
$ignored_category = wawahz_add_track_validate(array('title' => 'A', 'youtube_url' => $youtube_url, 'category' => '99999999'));
np_check(is_array($ignored_category) && $ignored_category['category'] === $music_category_id, 'A submitted category is ignored (always the music category)');

foreach (array('https://youtu.be/' . $video_id, 'https://www.youtube.com/watch?v=' . $video_id . '&t=40', 'https://youtube.com/shorts/' . $video_id, 'https://youtube.com/live/' . $video_id, $video_id) as $url) {
  $parsed = wawahz_add_track_validate(array('title' => 'A', 'youtube_url' => $url));
  np_check(is_array($parsed) && $parsed['youtube_id'] === $video_id, 'Accepted YouTube URL form: ' . $url);
}

$fallback = wawahz_add_track_validate(array('title' => 'A', 'youtube_url' => $youtube_url, 'status' => 'trash'));
np_check($fallback['status'] === 'publish', 'Unknown status falls back to publish');
$trimmed = wawahz_add_track_validate(array('title' => '  Spaced  ', 'youtube_url' => $youtube_url, 'artist' => "A\nB", 'unknown' => 'ignored'));
np_check($trimmed['title'] === 'Spaced', 'Title is trimmed');
np_check(strpos($trimmed['artist'], "\n") === false, 'Multiline value is sanitized');
np_check(!isset($trimmed['unknown']), 'Unknown fields are dropped');
$escaped = wawahz_add_track_validate(array('title' => '<script>alert(1)</script>Rain', 'youtube_url' => $youtube_url, 'commentary' => "Line1\nLine2"));
np_check(strpos($escaped['title'], '<') === false, 'Markup is stripped from the title');
np_check($escaped['commentary'] === "Line1\nLine2", 'Commentary keeps line breaks');
$slashed = wawahz_add_track_validate(array('title' => "It\\'s fine", 'youtube_url' => $youtube_url));
np_check($slashed['title'] === "It's fine", 'Slashed input is unslashed');
np_check($music_term !== null && $music_category_id > 0, 'The music category exists (created on demand)');

// 2. 投稿本文（ギャラリーが再生対象として検出するブロック）
$content = wawahz_add_track_content($youtube_url, '紹介文のテスト');
np_check(strpos($content, '<!-- wp:paragraph -->') === 0, 'Commentary paragraph comes first');
np_check(strpos($content, '<p>紹介文のテスト</p>') !== false, 'Commentary is rendered as a paragraph');
np_check(substr_count($content, $youtube_url) === 2, 'Embed block keeps the URL in attributes and wrapper');
np_check(strpos($content, '"providerNameSlug":"youtube"') !== false, 'Embed block declares the YouTube provider');
np_check(stripos($content, 'autoplay') === false && stripos($content, '<iframe') === false, 'No autoplay or hardcoded iframe');
np_check(strpos(wawahz_add_track_content($youtube_url), '<p>') === false, 'Empty commentary adds no paragraph');
$media = wawahz_find_media_block(parse_blocks($content));
np_check(is_array($media) && $media['type'] === 'youtube' && $media['youtube_id'] === $video_id, 'Gallery detects the created embed block');

// 3. 文言と公開状態
np_check(array_keys(wawahz_add_track_statuses()) === array('publish', 'draft'), 'Only publish and draft are offered');
foreach (array('invalid_request', 'forbidden', 'title_required', 'youtube_invalid', 'create_failed') as $code) {
  $message = wawahz_add_track_error_message($code);
  np_check($message !== '' && strpos($message, $code) === false, 'Error message exists: ' . $code);
}
np_check(wawahz_add_track_error_message('no_such_code') !== '', 'Unknown code falls back to a generic message');
np_check(has_action('wp_ajax_wawahz_add_track', 'wawahz_handle_add_track') !== false, 'AJAX handler is registered');
np_check(has_action('wp_ajax_nopriv_wawahz_add_track', 'wawahz_handle_add_track_nopriv') !== false, 'Anonymous AJAX handler is registered');

// 4. 権限（CLI では未ログイン → 追加不可）
$original_user = get_current_user_id();
$original_post = $_POST;
$original_request = $_REQUEST;
wp_set_current_user(0);
np_check(wawahz_can_add_track() === false, 'Anonymous visitors cannot add tracks');
$editors = get_users(array('capability' => 'edit_posts', 'number' => 1, 'fields' => 'ID'));
np_check(!empty($editors), 'An editor account exists for this check');
$editor_id = (int) $editors[0];
wp_set_current_user($editor_id);
np_check(wawahz_can_add_track() === true, 'Editors can add tracks');
wp_set_current_user(0);

$count_posts = function () {
  $counts = wp_count_posts('post');
  return (int) $counts->publish + (int) $counts->draft;
};
$posts_before = $count_posts();

$run_request = function ($post) {
  $_POST = $post;
  $_REQUEST = $post;
  return wawahz_add_track_response();
};

// 5. AJAX 応答（拒否されるケースは投稿を作成しない）
$no_nonce = $run_request(array('title' => 'No nonce', 'youtube_url' => $youtube_url));
np_check($no_nonce['status'] === 403 && $no_nonce['code'] === 'invalid_request', 'Missing nonce is rejected with 403');
np_check($no_nonce['data'] === array(), 'Rejected request returns no data');
np_check($count_posts() === $posts_before, 'Missing nonce creates no post');

$anonymous_nonce = wp_create_nonce('wawahz_add_track');
$anonymous = $run_request(array('nonce' => $anonymous_nonce, 'title' => 'Anonymous', 'youtube_url' => $youtube_url));
np_check($anonymous['status'] === 403 && $anonymous['code'] === 'forbidden', 'Anonymous request is rejected with 403');
np_check($count_posts() === $posts_before, 'Anonymous request creates no post');

wp_set_current_user($editor_id);
$editor_nonce = wp_create_nonce('wawahz_add_track');
$invalid = $run_request(array('nonce' => $editor_nonce, 'title' => '', 'youtube_url' => $youtube_url));
np_check($invalid['status'] === 400 && $invalid['code'] === 'title_required', 'Invalid input is rejected with 400');
np_check($count_posts() === $posts_before, 'Invalid input creates no post');

// 6. 作成される投稿（作成した投稿はすべて検証後に完全削除）
$created_id = 0;
$cleanup = array();
try {
  $payload = array_merge($valid_input, array('nonce' => $editor_nonce, 'title' => 'wawahz add-track test'));
  $response = $run_request($payload);
  np_check($response['status'] === 200 && $response['code'] === 'ok', 'Valid editor request succeeds');
  $created_id = (int) $response['data']['id'];
  $cleanup[] = $created_id;
  np_check($created_id > 0, 'Response contains the new post ID');
  np_check($response['data']['status'] === 'draft' && strpos($response['data']['message'], '下書き') !== false, 'Draft response message matches the status');

  $created = get_post($created_id);
  np_check($created && $created->post_title === 'wawahz add-track test', 'Created post has the submitted title');
  np_check($created->post_status === 'draft' && $created->post_type === 'post' && (int) $created->post_author === $editor_id, 'Created post keeps status, type and author');
  np_check($created->post_excerpt === $valid_input['commentary'], 'Created post keeps the commentary as the excerpt');
  np_check(get_post_meta($created_id, 'wawahz_youtube_url', true) === $youtube_url, 'YouTube meta is stored');
  np_check(get_post_meta($created_id, 'wawahz_artist', true) === $valid_input['artist'], 'Artist meta is stored');
  np_check(get_post_meta($created_id, 'wawahz_album', true) === $valid_input['album'], 'Album meta is stored');
  np_check(get_post_meta($created_id, 'wawahz_mood', true) === $valid_input['mood'], 'Mood meta is stored');
  np_check($music_category_id > 0 && in_array($music_category_id, wp_get_post_categories($created_id)), 'Created track is filed under the music category');

  $created_media = wawahz_post_media($created);
  np_check($created_media['type'] === 'youtube' && $created_media['youtube_id'] === $video_id, 'Created post is playable as a YouTube track');

  $html = $response['data']['html'];
  np_check(strpos($html, 'data-open-player') !== false && strpos($html, 'gallery-play-btn') !== false, 'Response HTML is a playable card');
  np_check(strpos($html, 'data-youtube-id="' . $video_id . '"') !== false, 'Card carries the new video ID');
  np_check(strpos($html, 'wawahz add-track test') !== false && strpos($html, 'wawa.hz sound lab') !== false, 'Card shows the submitted title and artist');
  np_check(strpos($html, 'img.youtube.com/vi/' . $video_id) !== false, 'Card falls back to the YouTube thumbnail');

  // 音声メディアのカードは再生用 <template> を持つ
  $audio_post = new WP_Post((object) array(
    'ID' => 999999, 'post_author' => $editor_id, 'post_date' => '2026-09-18 00:00:00',
    'post_title' => 'Audio fixture', 'post_content' => '<!-- wp:audio --><figure class="wp-block-audio"><audio src="https://example.org/music.mp3"></audio></figure><!-- /wp:audio -->',
    'post_excerpt' => '', 'post_status' => 'publish', 'post_type' => 'post', 'post_password' => '', 'post_name' => 'audio-fixture',
  ));
  $audio_html = wawahz_get_nowplaying_card_html(array('post' => $audio_post, 'media' => wawahz_post_media($audio_post)));
  np_check(strpos($audio_html, 'nowplaying-inline-media') !== false && strpos($audio_html, '<audio controls') !== false, 'Inline media card keeps the template');
  np_check(strpos($audio_html, 'data-youtube-id=""') !== false, 'Inline media card has no YouTube ID');

  // 公開済みで追加した場合もギャラリー先頭（最新）に入る
  $published = $run_request(array_merge($valid_input, array('nonce' => $editor_nonce, 'title' => 'wawahz add-track publish test', 'status' => 'publish')));
  np_check($published['status'] === 200 && $published['data']['status'] === 'publish', 'Published request succeeds');
  $published_id = (int) $published['data']['id'];
  $cleanup[] = $published_id;
  $tracks = wawahz_nowplaying_posts(6);
  np_check(!empty($tracks) && (int) $tracks[0]['post']->ID === $published_id, 'Newly published track is the newest gallery entry');
} finally {
  foreach ($cleanup as $cleanup_id) {
    wp_delete_post($cleanup_id, true);
  }
}

np_check(get_post($created_id) === null, 'Draft test post is removed');
np_check($count_posts() === $posts_before, 'Post counts are restored');

// 7. 画面の描画（編集権限の有無でモーダルとID重複が変わらないこと）
wp_set_current_user($editor_id);
ob_start();
get_template_part('template-parts/nowplaying-gallery', null, array('standalone' => true, 'limit' => 3));
$gallery_html = ob_get_clean();
preg_match_all('/\sid="([^"]+)"/', $gallery_html, $id_matches);
$duplicate_ids = array_keys(array_filter(array_count_values($id_matches[1]), function ($count) {
  return $count > 1;
}));
np_check($duplicate_ids === array(), 'Rendered screen has unique IDs (duplicates: ' . implode(', ', $duplicate_ids) . ')');
np_check(strpos($gallery_html, 'data-open-dialog="dialog-nowplaying-add-track"') !== false, 'The gallery button opens the add-track modal');
np_check(strpos($gallery_html, 'id="dialog-nowplaying-add-track"') !== false, 'The add-track modal is rendered for editors');
np_check(substr_count($gallery_html, 'id="dialog-nowplaying-add-track"') === 1, 'The modal is rendered exactly once');
np_check(strpos($gallery_html, 'name="nonce"') !== false && strpos($gallery_html, 'value="wawahz_add_track"') !== false, 'The modal form carries the action and nonce');
np_check(strpos($gallery_html, 'id="gallery-cards-container"') !== false, 'The card container used for insertion exists');
np_check(strpos($gallery_html, 'nowplaying-add-track-toast') !== false, 'The toast region is rendered');
np_check(strpos($gallery_html, 'id="nowplaying-add-track-preview"') !== false && strpos($gallery_html, 'id="nowplaying-add-track-submit"') !== false, 'The modal exposes the preview and submit hooks');

$second_block = wawahz_render_nowplaying_block(array('limit' => 1, 'showHeader' => true));
np_check(substr_count($second_block, 'id="dialog-nowplaying-add-track"') === 0, 'A second gallery block does not duplicate the modal');

wp_set_current_user(0);
$public_html = wawahz_render_nowplaying_block(array('limit' => 1, 'showHeader' => true));
np_check(strpos($public_html, 'data-open-dialog="dialog-nowplaying-add-track"') !== false, 'Anonymous visitors see the add-track button');
np_check(strpos($public_html, 'nowplaying-card') !== false, 'Anonymous visitors still see the tracks');

wp_set_current_user($original_user);
$_POST = $original_post;
$_REQUEST = $original_request;

echo $checks . " checks passed; no lasting posts created." . PHP_EOL;