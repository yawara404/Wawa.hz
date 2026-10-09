<?php
/**
 * 埋め込みハンドラの登録と、本文・ブロックへの適用。
 *
 * WordPress の autoembed / [embed] スコット / エディタの埋め込みプレビュー
 * (/wp-json/oembed/1.0/proxy) は、すべて WP_Embed::get_embed_handler_html() を
 * 通るため、ここで登録したハンドラがエディタ上でもフロントでも同じ HTML を返す。
 */
/** Wikipedia 系 URL に一致する正規表現。 */
function wawahz_embed_wiki_regex()
{
  $domains = array();
  foreach (array_keys(wawahz_embed_wiki_projects()) as $domain) {
    $domains[] = preg_quote($domain, '#');
  }
  // /wiki/記事名 と /w/index.php?title=記事名 の両形式に対応。
  return '#^https?://(?:[a-z0-9-]+\.)*(?:' . implode('|', $domains) . ')/(?:wiki/|w/index\.php)#i';
}

/** ニコニコ動画の視聴ページ URL に一致する正規表現 (キャプチャ = 動画 ID)。 */
function wawahz_embed_nicovideo_regex()
{
  return '#^https?://(?:(?:www|embed)\.)?nicovideo\.jp/watch/((?:sm|nm|so)\d+|\d+)#i';
}

/** nico.ms 短縮 URL に一致する正規表現 (キャプチャ = 動画 ID)。 */
function wawahz_embed_nico_ms_regex()
{
  return '#^https?://nico\.ms/((?:sm|nm|so)\d+|\d+)#i';
}

/** 指定 URL が本テーマの埋め込み対象かどうか (API へのアクセスは行わない)。 */
function wawahz_embed_is_supported_url($url)
{
  if (wawahz_embed_parse_wiki_url($url)) {
    return true;
  }
  if (preg_match(wawahz_embed_nicovideo_regex(), $url) || preg_match(wawahz_embed_nico_ms_regex(), $url)) {
    return true;
  }
  return false;
}

/**
 * URL を埋め込み HTML に変換する。対象外なら空文字。
 *
 * @param string $url 変換する URL。
 * @return string HTML
 */
function wawahz_embed_render_url($url)
{
  $html = '';
  if (wawahz_embed_parse_wiki_url($url)) {
    $summary = wawahz_embed_wiki_summary($url);
    $html = wawahz_embed_render_wiki_card($url, is_array($summary) ? $summary : array());
  } elseif (preg_match(wawahz_embed_nicovideo_regex(), $url, $match)) {
    $html = wawahz_embed_render_nicovideo($match[1]);
  } elseif (preg_match(wawahz_embed_nico_ms_regex(), $url, $match)) {
    $html = wawahz_embed_render_nicovideo($match[1]);
  }
  /**
   * 生成した埋め込み HTML を差し替えられるようにする。
   *
   * @param string $html 埋め込み HTML (空文字なら対象外)。
   * @param string $url  元の URL。
   */
  $html = apply_filters('wawahz_embed_html', $html, $url);

  if ($html === '') {
    return '';
  }
  // wpautop (the_content, 優先度 10) は HTML 内の改行や先頭のインデント空白を
  // <br /> に変換してしまうため、1 行に畳んで前後の余白も落として渡す。
  // (カード内部のレイアウトが崩れるのを防ぐ)
  return trim(preg_replace('/[\r\n]+/', ' ', $html));
}

/** 埋め込みハンドラの共通コールバック (oEmbed より先に走る)。 */
function wawahz_embed_handler($matches, $attr, $url, $rawattr)
{
  $html = wawahz_embed_render_url($url);
  return $html !== '' ? $html : false; // false = このハンドラ対象外 → 標準の oEmbed へ
}

/**
 * ニコニコ動画を公式埋め込みプレイヤーの iframe で描画する。
 * (embed.nicovideo.jp は X-Frame-Options / frame-ancestors を返していない)
 *
 * @param string $video_id 動画 ID (sm12345678 など)。
 * @return string HTML
 */
function wawahz_embed_render_nicovideo($video_id)
{
  $video_id = strtolower(trim((string) $video_id));
  // 動画 ID の形 (sm123 / nm123 / so123 / 数字) だけを受け付ける。
  if (!preg_match('/^(?:[a-z]{2}\d+|\d+)$/', $video_id)) {
    return '';
  }
  $src = 'https://embed.nicovideo.jp/watch/' . $video_id;
  $title = sprintf(__('%s の動画プレイヤー', 'wawahz'), __('ニコニコ動画', 'wawahz'));
  $arrow = '<svg class="wawahz-embed__arrow" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg>';

  ob_start();
  ?>
  <figure class="wawahz-embed wawahz-embed--video">
    <div class="wawahz-embed__head"><a class="wawahz-embed__link" href="https://www.nicovideo.jp/watch/<?php echo esc_attr($video_id); ?>" target="_blank" rel="noopener noreferrer"><span class="wawahz-embed__badge"><?php echo esc_html(__('ニコニコ動画', 'wawahz')); ?></span><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- svg は下で整形済み ?></a></div>
    <div class="wawahz-embed__frame">
      <iframe src="<?php echo esc_url($src); ?>" title="<?php echo esc_attr($title); ?>" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen; autoplay; encrypted-media" allowfullscreen></iframe>
    </div>
  </figure>
  <?php
  return ob_get_clean();
}

/** 埋め込みハンドラの登録。 */
function wawahz_embed_register_handlers()
{
  if (!function_exists('wp_embed_register_handler')) {
    return;
  }
  wp_embed_register_handler('wawahz-wiki', wawahz_embed_wiki_regex(), 'wawahz_embed_handler');
  wp_embed_register_handler('wawahz-nicovideo', wawahz_embed_nicovideo_regex(), 'wawahz_embed_handler');
  wp_embed_register_handler('wawahz-nico-ms', wawahz_embed_nico_ms_regex(), 'wawahz_embed_handler');
}
add_action('init', 'wawahz_embed_register_handlers');

/**
 * コアのホワイトリストに無くても oEmbed を公開しているサイトの登録。
 * (それ以外の主要サイト — YouTube / Vimeo / SoundCloud / X / TikTok 等は
 *  WordPress 標準の提供元リストまたはリンクタグの自動発見で対応済み)
 */
function wawahz_embed_register_providers()
{
  if (!function_exists('wp_oembed_add_provider')) {
    return;
  }
  // OpenProcessing (p5.js / Processing のスケッチ)。公式が oEmbed を公開している。
  wp_oembed_add_provider(
    '#https?://(www\.)?openprocessing\.org/sketch/\d+.*#i',
    'https://www.openprocessing.org/api/oembed',
    true
  );
}
add_action('init', 'wawahz_embed_register_providers', 5);

/**
 * core/embed ブロックの最終防衛線。
 *
 * autoembed (the_content, 優先度 8) が URL を変換済みなら何もしないが、
 * 単独行になっていない URL や、render_block 経由で描画されるコンテンツは
 * ここでも同じ変換を通して「失敗した埋め込み」を残さない。
 * 優先度 20 はテーマ側の YouTube 補完 (優先度 10) の後に走らせるため。
 *
 * @param string $block_content ブロック出力。
 * @param array  $block         ブロック情報。
 * @return string
 */
function wawahz_embed_render_block($block_content, $block)
{
  $name = (string) ($block['blockName'] ?? '');
  if ($name !== 'core/embed' && strpos($name, 'core-embed/') !== 0) {
    return $block_content;
  }
  if (stripos($block_content, '<iframe') !== false) {
    return $block_content; // 通常の oEmbed / YouTube 補完が成功している
  }

  $url = '';
  if (isset($block['attrs']['url']) && is_string($block['attrs']['url'])) {
    $url = $block['attrs']['url'];
  }
  if ($url === '' && preg_match('~https?://[^\s<>"\']+~', $block_content, $match)) {
    $url = html_entity_decode($match[0], ENT_QUOTES, 'UTF-8');
  }

  $has_card = stripos($block_content, 'wawahz-embed') !== false;
  if (!$has_card && ($url === '' || !wawahz_embed_is_supported_url($url))) {
    return $block_content;
  }

  if (!$has_card) {
    $html = wawahz_embed_render_url($url);
    if ($html !== '') {
      $replaced = preg_replace_callback(
        '~(<div class="wp-block-embed__wrapper">).*?(</div>)~s',
        static function ($matches) use ($html) {
          return $matches[1] . $html . $matches[2];
        },
        $block_content,
        1,
        $count
      );
      if ($count === 1) {
        $block_content = $replaced;
      } else {
        // ラッパーが無い保存形式は URL を直接置換して構造 (キャプション等) を保つ。
        $escaped = str_replace('&', '&amp;', $url);
        $block_content = str_replace(array($url, $escaped), $html, $block_content);
      }
    }
  }

  // 自分たちのカード / プレイヤーが入った場合だけラッパーに印を付ける。
  // (YouTube 等の他サイトの埋め込みにはテーマの 16:9 フレームを維持する)
  if (stripos($block_content, 'wawahz-embed') === false) {
    return $block_content;
  }

  return wawahz_embed_mark_wrapper($block_content);
}
add_filter('render_block', 'wawahz_embed_render_block', 20, 2);

/**
 * 埋め込みブロックのラッパーに印を付ける。
 * テーマは .wp-block-embed__wrapper に 16:9 の黒い枠を強制しているため、
 * カード / プレイヤー枠を出す場合はここを解除する必要がある。
 * YouTube 等の他サイトの埋め込みには付与しない (フレームを壊さないため)。
 *
 * @param string $block_content ブロック出力。
 * @return string
 */
function wawahz_embed_mark_wrapper($block_content)
{
  if (strpos($block_content, 'wawahz-embed-host') !== false) {
    return $block_content;
  }
  return str_replace(
    '<div class="wp-block-embed__wrapper">',
    '<div class="wp-block-embed__wrapper wawahz-embed-host">',
    $block_content
  );
}

/**
 * エディタの埋め込みプレビューにもカードの見た目を適用する。
 * フロントのスタイルは style.css から @import している (他の CSS モジュールと同じ)。
 */
function wawahz_embeds_editor_style()
{
  $file = get_template_directory() . '/css/embeds.css';
  $version = file_exists($file) ? (string) filemtime($file) : (string) wp_get_theme()->get('Version');
  wp_enqueue_style('wawahz-embeds', get_template_directory_uri() . '/css/embeds.css', array(), $version);
}
add_action('enqueue_block_editor_assets', 'wawahz_embeds_editor_style');
