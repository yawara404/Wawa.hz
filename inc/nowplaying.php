<?php
/** Now Playing: 投稿本文の標準メディアブロックをギャラリーでも利用する。 */

function wawahz_youtube_id($value)
{
  $value = trim((string) $value);
  if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
    return $value;
  }
  $url = wp_parse_url($value);
  if (!$url || empty($url['host']) || !in_array(strtolower($url['scheme'] ?? ''), array('http', 'https'), true)) {
    return '';
  }
  $host = strtolower($url['host']);
  $path = trim($url['path'] ?? '', '/');
  $id = '';
  if ($host === 'youtu.be') {
    $id = explode('/', $path)[0];
  } elseif (in_array($host, array('youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'), true)) {
    if ($path === 'watch') {
      parse_str($url['query'] ?? '', $query);
      $id = $query['v'] ?? '';
    } elseif (preg_match('~^(?:embed|shorts|live)/([^/]+)~', $path, $match)) {
      $id = $match[1];
    }
  }
  return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : '';
}

/** YouTube のプレイリストURL または list ID からプレイリストIDを取得する。 */
function wawahz_youtube_playlist_id($value)
{
  $value = trim((string) $value);
  if (preg_match('/^[A-Za-z0-9_-]{12,64}$/', $value)) {
    return $value;
  }
  $url = wp_parse_url($value);
  if (!$url || empty($url['host']) || !in_array(strtolower($url['scheme'] ?? ''), array('http', 'https'), true)) {
    return '';
  }
  $host = strtolower($url['host']);
  if (!in_array($host, array('youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'), true)) {
    return '';
  }
  parse_str($url['query'] ?? '', $query);
  $list = isset($query['list']) && is_string($query['list']) ? $query['list'] : '';
  return preg_match('/^[A-Za-z0-9_-]{12,64}$/', $list) ? $list : '';
}

/**
 * YouTube が配信しているサムネイル画像のURLを返す（画像はそのまま利用）。
 * 既定は hqdefault (480×360)。存在しない解像度は YouTube 側が 404 を返すため、用途に応じて選ぶ。
 */
function wawahz_youtube_thumbnail_url($value, $size = 'hqdefault')
{
  $id = wawahz_youtube_id($value);
  if (!$id) {
    return '';
  }
  if (!in_array($size, array('maxresdefault', 'sddefault', 'hqdefault', 'mqdefault'), true)) {
    $size = 'hqdefault';
  }
  return 'https://img.youtube.com/vi/' . $id . '/' . $size . '.jpg';
}

/** YouTube サムネイル各サイズの実寸 (img の width/height 属性用)。 */
function wawahz_youtube_thumbnail_size($size = 'hqdefault')
{
  $sizes = array(
    'maxresdefault' => array(1280, 720),
    'sddefault'     => array(640, 480),
    'hqdefault'     => array(480, 360),
    'mqdefault'     => array(320, 180),
  );
  return isset($sizes[$size]) ? $sizes[$size] : $sizes['hqdefault'];
}

/**
 * プレイリストの代表動画ID（YouTube のプレイリストカバー = 先頭動画のサムネイル）。
 * 投稿メタ `wawahz_playlist_cover_id` があればそれを、無ければ本文/メタの動画IDを返す。
 */
function wawahz_playlist_cover_id($post = null)
{
  $post = get_post($post);
  if (!$post) {
    return '';
  }
  $id = wawahz_youtube_id(get_post_meta($post->ID, 'wawahz_playlist_cover_id', true));
  if ($id) {
    return $id;
  }
  return wawahz_youtube_id(get_post_meta($post->ID, 'wawahz_youtube_url', true));
}

/**
 * プレイリストの埋め込みブロックは oEmbed の取得結果に依存せず公式 iframe を直接描画する。
 * 単体動画のブロック (11文字の動画ID) は WordPress 標準の処理に任せる。
 */
function wawahz_render_playlist_embed($block_content, $block)
{
  if (($block['blockName'] ?? '') !== 'core/embed' || preg_match('/<iframe\b/i', $block_content)) {
    return $block_content;
  }
  $url = $block['attrs']['url'] ?? '';
  if (wawahz_youtube_id($url)) {
    return $block_content;
  }
  $playlist_id = wawahz_youtube_playlist_id($url);
  if (!$playlist_id) {
    return $block_content;
  }
  $src = 'https://www.youtube.com/embed/videoseries?list=' . $playlist_id . '&playsinline=1&rel=0';
  return '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">'
    . '<div class="wp-block-embed__wrapper">'
    . '<iframe src="' . esc_url($src) . '" title="' . esc_attr__('YouTube プレイリストプレイヤー', 'wawahz') . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>'
    . '</div></figure>';
}
add_filter('render_block', 'wawahz_render_playlist_embed', 10, 2);

/** グループ・カラム内も含め、本文で最初に見つかる再生可能なブロック。 */
function wawahz_find_media_block($blocks)
{
  foreach ($blocks as $block) {
    $name = $block['blockName'];
    if (in_array($name, array('core/audio', 'core/video'), true)
        && preg_match('/<(?:audio|video|source)\b[^>]*\bsrc\s*=\s*["\'][^"\']+["\']/i', $block['innerHTML'])) {
      return array('type' => $name === 'core/audio' ? 'audio' : 'video', 'block' => $block);
    }
    if (in_array($name, array('core/embed', 'core-embed/youtube'), true)) {
      $id = wawahz_youtube_id($block['attrs']['url'] ?? '');
      if ($id) {
        return array('type' => 'youtube', 'youtube_id' => $id);
      }
    }
    if (!empty($block['innerBlocks'])) {
      $media = wawahz_find_media_block($block['innerBlocks']);
      if ($media) {
        return $media;
      }
    }
  }
  return null;
}

function wawahz_post_media($post)
{
  $media = wawahz_find_media_block(parse_blocks($post->post_content));
  if ($media) {
    return $media;
  }
  // 従来のメタボックスと本文の YouTube リンクを引き続きサポート。
  $id = wawahz_youtube_id(get_post_meta($post->ID, 'wawahz_youtube_url', true));
  if (!$id && preg_match_all('~https?://[^\s<>"\']+~', $post->post_content, $matches)) {
    foreach ($matches[0] as $url) {
      $id = wawahz_youtube_id(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
      if ($id) {
        break;
      }
    }
  }
  return $id ? array('type' => 'youtube', 'youtube_id' => $id) : null;
}

/** 公開済み・パスワードなしの投稿を走査。最新10件以外の楽曲も対象。 */
function wawahz_nowplaying_posts($limit)
{
  $tracks = array();
  $page = 1;
  do {
    $posts = get_posts(array(
      'post_type' => 'post',
      'post_status' => 'publish',
      'has_password' => false,
      'posts_per_page' => 50,
      'paged' => $page++,
      'orderby' => array('date' => 'DESC', 'ID' => 'DESC'),
    ));
    foreach ($posts as $post) {
      $media = wawahz_post_media($post);
      if ($media) {
        $tracks[] = array('post' => $post, 'media' => $media);
        if (count($tracks) >= $limit) {
          return $tracks;
        }
      }
    }
  } while (count($posts) === 50);
  return $tracks;
}

/** 投稿本文のメディアを標準ブロックとして描画。自動再生はギャラリーでは無効。 */
function wawahz_render_gallery_media($media)
{
  if ($media['type'] === 'youtube') {
    // YouTube ブロックの URL を公式 iframe へ。外部 oEmbed API の失敗に依存しない。
    $src = 'https://www.youtube.com/embed/' . $media['youtube_id'] . '?playsinline=1&rel=0';
    return '<figure class="wp-block-embed is-type-video is-provider-youtube"><div class="wp-block-embed__wrapper"><iframe src="' . esc_url($src) . '" title="' . esc_attr__('YouTube 動画プレイヤー', 'wawahz') . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div></figure>';
  }
  // 標準ブロックの source / 字幕 / キャプションを保持し、実行可能な属性は除外。
  $allowed = array(
    'figure' => array('class' => true),
    'figcaption' => array('class' => true),
    'audio' => array('src' => true, 'loop' => true),
    'video' => array('src' => true, 'poster' => true, 'loop' => true, 'muted' => true, 'playsinline' => true),
    'source' => array('src' => true, 'type' => true),
    'track' => array('src' => true, 'kind' => true, 'srclang' => true, 'label' => true, 'default' => true),
    'a' => array('href' => true),
    'br' => array(), 'em' => array(), 'strong' => array(),
  );
  $html = wp_kses(render_block($media['block']), $allowed, array('http', 'https'));
  return preg_replace('/<(audio|video)\b/i', '<$1 controls preload="none"', $html);
}

// ==========================================================================
// Now Playing 楽曲追加モーダル (AJAX)
// ==========================================================================

/** 追加モーダルを利用できる権限。 */
function wawahz_can_add_track()
{
  return current_user_can('edit_posts');
}

/** 追加モーダルで選べる公開状態。 */
function wawahz_add_track_statuses()
{
  return array(
    'publish' => __('公開（ギャラリーに表示）', 'wawahz'),
    'draft'   => __('下書きとして保存', 'wawahz'),
  );
}

/** 検証エラーコードに対応するメッセージ。 */
function wawahz_add_track_error_message($code)
{
  $messages = array(
    'invalid_request'  => __('リクエストを確認できませんでした。ページを再読み込みしてもう一度お試しください。', 'wawahz'),
    'forbidden'        => __('楽曲を追加する権限がありません。', 'wawahz'),
    'title_required'   => __('曲名を入力してください。', 'wawahz'),
    'youtube_invalid'  => __('YouTubeのURLまたは動画IDを正しく入力してください。', 'wawahz'),
    'category_invalid' => __('選択したカテゴリーが見つかりません。', 'wawahz'),
    'create_failed'    => __('楽曲を保存できませんでした。時間をおいてもう一度お試しください。', 'wawahz'),
  );
  return isset($messages[$code]) ? $messages[$code] : __('楽曲を追加できませんでした。', 'wawahz');
}

/**
 * 送信値を検証・整形する（データベースに触れない純粋処理）。
 * 戻り値は整形済み配列、または WP_Error。
 */
function wawahz_add_track_validate($input)
{
  $input = is_array($input) ? $input : array();
  $value = function ($key) use ($input) {
    return isset($input[$key]) && is_string($input[$key]) ? trim(wp_unslash($input[$key])) : '';
  };

  $title = sanitize_text_field($value('title'));
  if ($title === '') {
    return new WP_Error('title_required', wawahz_add_track_error_message('title_required'));
  }

  $youtube_id = wawahz_youtube_id($value('youtube_url'));
  if (!$youtube_id) {
    return new WP_Error('youtube_invalid', wawahz_add_track_error_message('youtube_invalid'));
  }

  $category = 0;
  $category_id = absint($value('category'));
  if ($category_id) {
    $term = get_category($category_id);
    if (!$term || is_wp_error($term)) {
      return new WP_Error('category_invalid', wawahz_add_track_error_message('category_invalid'));
    }
    $category = (int) $term->term_id;
  }

  $statuses = wawahz_add_track_statuses();
  $status = $value('status');

  return array(
    'title'       => $title,
    'youtube_id'  => $youtube_id,
    'youtube_url' => 'https://www.youtube.com/watch?v=' . $youtube_id,
    'artist'      => sanitize_text_field($value('artist')),
    'album'       => sanitize_text_field($value('album')),
    'mood'        => sanitize_text_field($value('mood')),
    'commentary'  => sanitize_textarea_field($value('commentary')),
    'category'    => $category,
    'status'      => array_key_exists($status, $statuses) ? $status : 'publish',
  );
}

/** YouTube 埋め込みブロックを含む投稿本文（ギャラリーはこのブロックを再生対象にする）。 */
function wawahz_add_track_content($youtube_url, $commentary = '')
{
  $url = esc_url_raw($youtube_url);
  $content = '';
  if ($commentary !== '') {
    $content .= '<!-- wp:paragraph --><p>' . esc_html($commentary) . '</p><!-- /wp:paragraph -->';
  }
  $content .= '<!-- wp:core-embed/youtube {"url":"' . $url . '","type":"video","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->';
  $content .= '<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">'
    . '<div class="wp-block-embed__wrapper">' . esc_html($url) . '</div></figure>';
  $content .= '<!-- /wp:core-embed/youtube -->';
  return $content;
}

/** 検証済みデータから投稿を作成し、投稿IDを返す。 */
function wawahz_add_track($data)
{
  $post_id = wp_insert_post(array(
    'post_type'    => 'post',
    'post_status'  => $data['status'],
    'post_title'   => $data['title'],
    'post_content' => wawahz_add_track_content($data['youtube_url'], $data['commentary']),
    'post_excerpt' => $data['commentary'],
    'post_author'  => get_current_user_id(),
  ), true);
  if (is_wp_error($post_id)) {
    return $post_id;
  }
  if (!empty($data['category'])) {
    wp_set_post_categories($post_id, array((int) $data['category']));
  }
  update_post_meta($post_id, 'wawahz_youtube_url', $data['youtube_url']);
  update_post_meta($post_id, 'wawahz_artist', $data['artist']);
  update_post_meta($post_id, 'wawahz_album', $data['album']);
  update_post_meta($post_id, 'wawahz_mood', $data['mood']);
  return (int) $post_id;
}

/** カード1件分のHTML。ギャラリーのPHP描画と AJAX 応答で同じテンプレートを共用する。 */
function wawahz_get_nowplaying_card_html($track)
{
  ob_start();
  get_template_part('template-parts/nowplaying-card', null, $track);
  return ob_get_clean();
}

/**
 * 追加リクエストを処理し、応答データを組み立てる（AJAX 応答とテストで共用）。
 *
 * @return array { status: int, code: string, message: string, data: array }
 */
function wawahz_add_track_response()
{
  if (!check_ajax_referer('wawahz_add_track', 'nonce', false)) {
    return array(
      'status'  => 403,
      'code'    => 'invalid_request',
      'message' => wawahz_add_track_error_message('invalid_request'),
      'data'    => array(),
    );
  }
  if (!wawahz_can_add_track()) {
    return array(
      'status'  => 403,
      'code'    => 'forbidden',
      'message' => wawahz_add_track_error_message('forbidden'),
      'data'    => array(),
    );
  }

  $data = wawahz_add_track_validate($_POST);
  if (is_wp_error($data)) {
    return array(
      'status'  => 400,
      'code'    => $data->get_error_code(),
      'message' => $data->get_error_message(),
      'data'    => array(),
    );
  }

  $post_id = wawahz_add_track($data);
  if (is_wp_error($post_id)) {
    return array(
      'status'  => 500,
      'code'    => 'create_failed',
      'message' => wawahz_add_track_error_message('create_failed'),
      'data'    => array(),
    );
  }

  $post = get_post($post_id);
  $media = $post ? wawahz_post_media($post) : null;
  $message = $data['status'] === 'publish'
    ? __('楽曲を追加しました。ギャラリーの先頭に表示しています。', 'wawahz')
    : __('下書きとして保存しました。公開するとギャラリーに表示されます。', 'wawahz');
  return array(
    'status'  => 200,
    'code'    => 'ok',
    'message' => $message,
    'data'    => array(
      'id'      => $post_id,
      'status'  => $data['status'],
      'url'     => get_permalink($post_id),
      'html'    => $media ? wawahz_get_nowplaying_card_html(array('post' => $post, 'media' => $media)) : '',
      'message' => $message,
    ),
  );
}

/** AJAX: 追加モーダルから Now Playing の楽曲を追加する。 */
function wawahz_handle_add_track()
{
  $response = wawahz_add_track_response();
  if ($response['status'] === 200) {
    wp_send_json_success($response['data']);
  }
  wp_send_json_error(
    array('code' => $response['code'], 'message' => $response['message']),
    $response['status']
  );
}
add_action('wp_ajax_wawahz_add_track', 'wawahz_handle_add_track');
