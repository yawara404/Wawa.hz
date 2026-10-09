<?php
/**
 * Wikipedia 系 (Wikimedia プロジェクト) のサマリーカード。
 *
 * Wikipedia は oEmbed (action=oembed / link rel=oembed) を一切提供していないため、
 * WordPress 標準の autoembed では「URL のそのまま貼り出し」になってしまう。
 * 本ファイルは公式の REST API (page summary) / Action API (pageimages|extracts) /
 * Wikidata API からタイトル・要約・サムネイルを取り寄せ、カード表示用の配列に
 * 正規化する。取得に失敗してもタイトルだけのリンクカードを出すため、
 * 本文のレイアウトが崩れることはない。
 */

/**
 * 埋め込み対象ドメイン => サイト名。
 *
 * @return array<string,string>
 */
function wawahz_embed_wiki_projects()
{
  return array(
    'wikipedia.org'           => 'Wikipedia',
    'wiktionary.org'          => 'Wiktionary',
    'wikiquote.org'           => 'Wikiquote',
    'wikibooks.org'           => 'Wikibooks',
    'wikisource.org'          => 'Wikisource',
    'wikiversity.org'         => 'Wikiversity',
    'wikivoyage.org'          => 'Wikivoyage',
    'wikinews.org'            => 'Wikinews',
    'wikidata.org'            => 'Wikidata',
    'wikimedia.org'           => 'Wikimedia',
    'mediawiki.org'           => 'MediaWiki',
    'wikimediafoundation.org' => 'Wikimedia Foundation',
  );
}

/**
 * Wikimedia 系のサブドメイン名から表示名を返す (commons / meta など)。
 *
 * @param string $subdomain 言語コード等のサブドメイン (空文字可)。
 * @param string $fallback  対応表に無い場合のサイト名。
 * @return string
 */
function wawahz_embed_wiki_subdomain_label($subdomain, $fallback)
{
  $map = array(
    'commons'    => 'Wikimedia Commons',
    'species'    => 'Wikispecies',
    'meta'       => 'Meta-Wikimedia',
    'foundation' => 'Wikimedia Foundation',
    'incubator'  => 'Wikimedia Incubator',
    'wikimania'  => 'Wikimania',
    'www'        => $fallback,
    ''           => $fallback,
  );
  return isset($map[$subdomain]) ? $map[$subdomain] : $fallback;
}

/**
 * 埋め込み対象の Wikipedia 系 URL を解析する。
 *
 * 対応 URL 例:
 *   https://ja.wikipedia.org/wiki/音楽
 *   https://en.wikipedia.org/wiki/Chiptune#History
 *   https://ja.wikipedia.org/w/index.php?title=音楽&oldid=123
 *   https://ja.m.wikipedia.org/wiki/音楽          (モバイル版はデスクトップへ正規化)
 *   https://commons.wikimedia.org/wiki/File:Example.jpg
 *   https://www.wikidata.org/wiki/Q638
 *
 * @param string $url 解析する URL。
 * @return array|false ホスト・サイト名・記事タイトル等の配列。対象外なら false。
 */
function wawahz_embed_parse_wiki_url($url)
{
  $url = trim((string) $url);
  $parts = wp_parse_url($url);
  if (!$parts || empty($parts['host']) || empty($parts['scheme'])) {
    return false;
  }
  if (!in_array(strtolower($parts['scheme']), array('http', 'https'), true)) {
    return false;
  }

  // モバイル版ドメイン (ja.m.wikipedia.org / en.mobile.wikipedia.org) は API 用に寄せる。
  $host = preg_replace('/^([a-z0-9-]+\.)?(?:m|mobile)\./', '$1', strtolower($parts['host']));

  $projects = wawahz_embed_wiki_projects();
  $project = '';
  foreach (array_keys($projects) as $domain) {
    if ($host === $domain || substr($host, -(strlen($domain) + 1)) === '.' . $domain) {
      $project = $domain;
      break;
    }
  }
  if ($project === '') {
    return false;
  }

  // 記事タイトルの取り出し (/wiki/... または /w/index.php?title=...)。
  $path = isset($parts['path']) ? $parts['path'] : '';
  $title = '';
  if (preg_match('~/wiki/(.+)$~', $path, $match)) {
    $title = rawurldecode($match[1]);
  } elseif (preg_match('~/w/index\.php$~', $path)) {
    $query = array();
    parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
    if (isset($query['title']) && is_string($query['title'])) {
      $title = rawurldecode($query['title']);
    }
  }
  $title = trim(preg_replace('/#.*$/s', '', $title), '/');
  if ($title === '') {
    return false; // 検索結果・トップページなどは対象外 (通常リンクのまま)
  }
  // 特殊ページ (Special:Random など) は要約取得できないため対象外。
  if (preg_match('/^special:/i', $title)) {
    return false;
  }

  $subdomain = ($host === $project) ? '' : substr($host, 0, -(strlen($project) + 1));
  $site = wawahz_embed_wiki_subdomain_label($subdomain, $projects[$project]);

  return array(
    'host'      => $host,
    'project'   => $project,
    'subdomain' => $subdomain,
    'title'     => $title,
    'site'      => $site,
    'url'       => $url,
  );
}

/** Wikimedia 要求用の User-Agent。 */
function wawahz_embed_user_agent()
{
  $theme = wp_get_theme();
  $version = $theme ? (string) $theme->get('Version') : '1.0.0';
  return 'WawaHz-Theme/' . $version . ' (WordPress/' . get_bloginfo('version') . '; ' . home_url('/') . ')';
}

/**
 * JSON を返す外部 API を GET する (ローカル/内部アドレスへのアクセスは拒否)。
 *
 * @param string $url 取得先 URL。
 * @return array|null 失敗時 null。
 */
function wawahz_embed_http_json($url)
{
  if (!function_exists('wp_safe_remote_get')) {
    return null;
  }
  $response = wp_safe_remote_get($url, array(
    'timeout'     => 6,
    'redirection' => 3,
    'headers'     => array(
      'Accept'     => 'application/json',
      'User-Agent' => wawahz_embed_user_agent(),
    ),
  ));
  if (is_wp_error($response)) {
    return null;
  }
  if ((int) wp_remote_retrieve_response_code($response) !== 200) {
    return null;
  }
  $data = json_decode(wp_remote_retrieve_body($response), true);
  return is_array($data) ? $data : null;
}

/** テキストを行単位でクリップして 1 行に戻す。 */
function wawahz_embed_clamp($text, $length = 180)
{
  $text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $text)));
  if ($text === '') {
    return '';
  }
  if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 1) . '…' : $text;
  }
  return strlen($text) > $length ? substr($text, 0, $length - 1) . '…' : $text;
}

/** 記事タイトルを記事ページ URL に変換する (API の正規化タイトル利用時)。 */
function wawahz_embed_wiki_page_url($host, $title)
{
  $segments = explode('/', str_replace(' ', '_', (string) $title));
  $encoded = implode('/', array_map('rawurlencode', $segments));
  return 'https://' . $host . '/wiki/' . $encoded;
}

/** このプロジェクトは REST page summary API を持っているか。 */
function wawahz_embed_supports_rest($info)
{
  if ($info['host'] === 'commons.wikimedia.org') {
    return true;
  }
  return in_array($info['project'], array('wikipedia.org', 'wikiquote.org'), true)
    && $info['subdomain'] !== ''
    && $info['subdomain'] !== 'www';
}

/**
 * REST page summary API から要約を取得する (wikipedia / wikiquote / commons)。
 *
 * @param array $info wawahz_embed_parse_wiki_url() の戻り値。
 * @return array|null
 */
function wawahz_embed_fetch_rest_summary($info)
{
  $endpoint = 'https://' . $info['host'] . '/api/rest_v1/page/summary/' . rawurlencode($info['title']);
  $json = wawahz_embed_http_json($endpoint);
  if (!$json || empty($json['title'])) {
    return null;
  }

  $image = '';
  $width = 0;
  $height = 0;
  if (!empty($json['thumbnail']['source'])) {
    $image = $json['thumbnail']['source'];
    $width = isset($json['thumbnail']['width']) ? (int) $json['thumbnail']['width'] : 0;
    $height = isset($json['thumbnail']['height']) ? (int) $json['thumbnail']['height'] : 0;
  }

  $page = '';
  if (!empty($json['content_urls']['desktop']['page'])) {
    $page = $json['content_urls']['desktop']['page'];
  }

  return array(
    'title'       => wp_strip_all_tags($json['title']),
    'description' => isset($json['description']) ? wp_strip_all_tags($json['description']) : '',
    'extract'     => isset($json['extract']) ? wp_strip_all_tags($json['extract']) : '',
    'image'       => $image,
    'width'       => $width,
    'height'      => $height,
    'page'        => $page,
    'lang'        => isset($json['lang']) ? $json['lang'] : $info['subdomain'],
  );
}

/**
 * Action API (pageimages|extracts) から要約を取得する。
 * wikipedia 以外の言語 Wiki (wiktionary / wikisource 等) でも同じ仕組みで動く。
 *
 * @param array $info wawahz_embed_parse_wiki_url() の戻り値。
 * @return array|null
 */
function wawahz_embed_fetch_action_summary($info)
{
  $endpoint = add_query_arg(
    array(
      'action'     => 'query',
      'format'     => 'json',
      'formatversion' => 2,
      'redirects'  => 1,
      'prop'       => 'pageimages|extracts',
      'piprop'     => 'thumbnail',
      'pithumbsize' => 480,
      'exintro'    => 1,
      'explaintext' => 1,
      'exchars'    => 240,
      'titles'     => $info['title'],
    ),
    'https://' . $info['host'] . '/w/api.php'
  );
  $json = wawahz_embed_http_json($endpoint);
  if (!$json || empty($json['query']['pages'][0])) {
    return null;
  }
  $page = $json['query']['pages'][0];
  if (!empty($page['missing'])) {
    return null;
  }

  $image = '';
  $width = 0;
  $height = 0;
  if (!empty($page['thumbnail']['source'])) {
    $image = $page['thumbnail']['source'];
    $width = isset($page['thumbnail']['width']) ? (int) $page['thumbnail']['width'] : 0;
    $height = isset($page['thumbnail']['height']) ? (int) $page['thumbnail']['height'] : 0;
  }

  $title = isset($page['title']) ? $page['title'] : $info['title'];

  return array(
    'title'       => wp_strip_all_tags($title),
    'description' => '',
    'extract'     => isset($page['extract']) ? wp_strip_all_tags($page['extract']) : '',
    'image'       => $image,
    'width'       => $width,
    'height'      => $height,
    'page'        => wawahz_embed_wiki_page_url($info['host'], $title),
    'lang'        => $info['subdomain'],
  );
}

/**
 * Wikidata (Q638 など) のラベル・説明・P18 画像を取得する。
 *
 * @param array $info wawahz_embed_parse_wiki_url() の戻り値。
 * @return array|null
 */
function wawahz_embed_fetch_wikidata($info)
{
  if (!preg_match('/^q\d+$/i', $info['title'])) {
    return null;
  }
  $ids = strtoupper($info['title']);
  $site_lang = strtolower(str_replace('_', '-', (string) get_bloginfo('language')));
  if ($site_lang === '') {
    $site_lang = 'ja';
  }
  $languages = array_values(array_unique(array($site_lang, 'ja', 'en')));

  $endpoint = add_query_arg(
    array(
      'action'    => 'wbgetentities',
      'format'    => 'json',
      'formatversion' => 2,
      'ids'       => $ids,
      'props'     => 'labels|descriptions|claims',
      'languages' => implode('|', $languages),
      'language'  => $site_lang,
    ),
    'https://www.wikidata.org/w/api.php'
  );
  $json = wawahz_embed_http_json($endpoint);
  if (!$json || empty($json['entities'][$ids])) {
    return null;
  }
  $entity = $json['entities'][$ids];
  if (!empty($entity['missing'])) {
    return null;
  }

  $pick = static function ($bag) use ($languages) {
    if (!is_array($bag)) {
      return '';
    }
    foreach ($languages as $lang) {
      if (!empty($bag[$lang]['value'])) {
        return (string) $bag[$lang]['value'];
      }
    }
    foreach ($bag as $entry) {
      if (!empty($entry['value'])) {
        return (string) $entry['value'];
      }
    }
    return '';
  };

  $image = '';
  $width = 0;
  $height = 480;
  // P18 = 画像 (Commons のファイル名)。Special:FilePath なら追加リクエスト不要。
  $claim = isset($entity['claims']['P18'][0]['mainsnak']['datavalue']['value'])
    ? $entity['claims']['P18'][0]['mainsnak']['datavalue']['value']
    : '';
  if (is_string($claim) && $claim !== '') {
    $image = 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($claim) . '?width=480';
    $width = 480;
  }

  return array(
    'title'       => $pick(isset($entity['labels']) ? $entity['labels'] : array()),
    'description' => $pick(isset($entity['descriptions']) ? $entity['descriptions'] : array()),
    'extract'     => '',
    'image'       => $image,
    'width'       => $width,
    'height'      => $height,
    'page'        => $info['url'],
    'lang'        => $site_lang,
  );
}

/**
 * 取得ルートの振り分け。
 *
 * @param array $info wawahz_embed_parse_wiki_url() の戻り値。
 * @return array|null
 */
function wawahz_embed_fetch_summary($info)
{
  if ($info['project'] === 'wikidata.org') {
    return wawahz_embed_fetch_wikidata($info);
  }
  if (wawahz_embed_supports_rest($info)) {
    $data = wawahz_embed_fetch_rest_summary($info);
    if ($data) {
      return $data;
    }
  }
  return wawahz_embed_fetch_action_summary($info);
}

/**
 * URL の要約データを返す (transient にキャッシュ。取得失敗も 6 時間キャッシュ)。
 *
 * @param string $url Wikipedia 系 URL。
 * @return array|false
 */
function wawahz_embed_wiki_summary($url)
{
  $info = wawahz_embed_parse_wiki_url($url);
  if (!$info) {
    return false;
  }

  $key = 'wawahz_embed_wiki_' . md5($info['host'] . '|' . $info['title']);
  $cached = get_transient($key);
  if (is_array($cached)) {
    return (isset($cached['status']) && $cached['status'] === 'ok' && !empty($cached['data'])) ? $cached['data'] : false;
  }

  $data = wawahz_embed_fetch_summary($info);
  if ($data) {
    $data['site'] = $info['site'];
    $data['host'] = $info['host'];
    if (empty($data['page'])) {
      $data['page'] = $info['url'];
    }
    if (empty($data['lang'])) {
      $data['lang'] = $info['subdomain'];
    }
    set_transient($key, array('status' => 'ok', 'data' => $data), WEEK_IN_SECONDS);
    return $data;
  }

  set_transient($key, array('status' => 'fail'), 6 * HOUR_IN_SECONDS);
  return false;
}

/**
 * Wikipedia 系 URL を Material 3 のサマリーカード HTML に変換する。
 *
 * 要約が取れなくてもタイトルとサイト名だけのカードは必ず返すため、
 * 「埋め込み失敗で本文が崩れる」ことがない。
 *
 * @param string $url  元の URL。
 * @param array  $data wawahz_embed_wiki_summary() の戻り値 (省略可)。
 * @return string HTML
 */
function wawahz_embed_render_wiki_card($url, $data = array())
{
  $info = wawahz_embed_parse_wiki_url($url);
  if (!$info) {
    return '';
  }
  $data = is_array($data) ? $data : array();

  $site = !empty($data['site']) ? $data['site'] : $info['site'];
  $title = !empty($data['title']) ? $data['title'] : str_replace('_', ' ', $info['title']);
  $page = !empty($data['page']) ? $data['page'] : $info['url'];
  $host = !empty($data['host']) ? $data['host'] : $info['host'];
  $desc = !empty($data['description']) ? trim($data['description']) : '';
  $text = wawahz_embed_clamp(isset($data['extract']) ? $data['extract'] : '', 170);
  $image = !empty($data['image']) ? $data['image'] : '';
  $width = !empty($data['width']) ? (int) $data['width'] : 0;
  $height = !empty($data['height']) ? (int) $data['height'] : 0;
  if ($text === $desc) {
    $text = '';
  }

  $classes = 'wawahz-embed wawahz-embed--wiki';
  if ($image === '') {
    $classes .= ' wawahz-embed--nothumb';
  }

  $arrow = '<svg class="wawahz-embed__arrow" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg>';

  ob_start();
  ?>
  <figure class="<?php echo esc_attr($classes); ?>">
    <a class="wawahz-embed__card" href="<?php echo esc_url($page); ?>" target="_blank" rel="noopener noreferrer">
      <?php if ($image !== '') : ?>
      <span class="wawahz-embed__thumb">
        <img src="<?php echo esc_url($image); ?>" alt=""<?php echo $width ? ' width="' . $width . '"' : ''; ?><?php echo $height ? ' height="' . $height . '"' : ''; ?> loading="lazy" decoding="async">
      </span>
      <?php endif; ?>
      <span class="wawahz-embed__body">
        <span class="wawahz-embed__badge"><?php echo esc_html($site); ?></span>
        <span class="wawahz-embed__title"><?php echo esc_html($title); ?></span>
        <?php if ($desc !== '') : ?>
        <span class="wawahz-embed__desc"><?php echo esc_html($desc); ?></span>
        <?php endif; ?>
        <?php if ($text !== '') : ?>
        <span class="wawahz-embed__text"><?php echo esc_html($text); ?></span>
        <?php endif; ?>
        <span class="wawahz-embed__host"><?php echo esc_html($host); ?><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput -- svg は下で整形済み ?></span>
      </span>
    </a>
  </figure>
  <?php
  return ob_get_clean();
}
