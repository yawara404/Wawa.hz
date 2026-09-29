<?php
/**
 * Wawa.hz テーマ機能定義ファイル
 * 
 * sample-theme の初期設定を踏襲し、Material 3 Expressive のための
 * スタイル・スクリプト・ウィジェット・テーマサポートを管理します。
 */

require_once get_template_directory() . '/inc/nowplaying.php';
require_once get_template_directory() . '/inc/theme-ui.php';

// テーマ初期設定
function wawahz_setup()
{
  // アイキャッチ画像を有効化
  add_theme_support('post-thumbnails');

  // <title>タグの出力をWordPressに任せる（必須）
  add_theme_support('title-tag');

  // RSSフィードのリンクを自動出力（必須）
  add_theme_support('automatic-feed-links');

  // ブロックエディタの基本スタイルをテーマに適用（Gutenberg対応）
  add_theme_support('wp-block-styles');
  add_theme_support('align-wide');
  add_theme_support('editor-styles');
  add_editor_style('editor-style.css');
  load_theme_textdomain('wawahz', get_template_directory() . '/languages');

  // YouTubeなどの埋め込み動画をレスポンシブ対応にする
  add_theme_support('responsive-embeds');

  // HTML5準拠のマークアップを有効化
  add_theme_support('html5', array(
    'search-form',
    'comment-form',
    'comment-list',
    'gallery',
    'caption',
    'style',
    'script'
  ));

  // カスタムロゴ対応
  add_theme_support('custom-logo', array(
    'height'      => 48,
    'width'       => 160,
    'flex-height' => true,
    'flex-width'  => true,
  ));

  // ナビゲーションメニューの登録
  register_nav_menus(array(
    'primary' => __('メインナビゲーション', 'wawahz'),
    'drawer'  => __('ドロワーメニュー', 'wawahz'),
    'footer'  => __('フッターメニュー', 'wawahz'),
  ));

  // 絵文字のスクリプトとスタイルを削除（読み込み速度向上）
  remove_action('wp_head', 'print_emoji_detection_script', 7);
  remove_action('admin_print_scripts', 'print_emoji_detection_script');
  remove_action('wp_print_styles', 'print_emoji_styles');
  remove_action('admin_print_styles', 'print_emoji_styles');

  // セキュリティ強化
  remove_action('wp_head', 'wp_generator'); // WordPressのバージョン情報を削除
  remove_action('wp_head', 'rsd_link');     // EditURI（RSD）のリンクを削除
  remove_action('wp_head', 'wlwmanifest_link'); // wlwmanifest（Windows Live Writer）のリンクを削除
}
add_action('after_setup_theme', 'wawahz_setup');

// スタイル・スクリプトのエンキュー
function wawahz_scripts()
{
  if (is_singular() && comments_open() && get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
  }
  // Google Fonts: Inter, Roboto, Noto Sans JP & Material Symbols Rounded
  wp_enqueue_style(
    'google-fonts-roboto',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@400;500;700&family=Roboto:wght@400;500;700&display=swap',
    array(),
    null
  );
  wp_enqueue_style(
    'material-symbols-rounded',
    'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200',
    array(),
    null
  );

  // メインスタイルシート (style.css - css/ ディレクトリ内の最新モジュール更新日時を反映)
  $css_mtime = file_exists(get_stylesheet_directory() . '/style.css') ? filemtime(get_stylesheet_directory() . '/style.css') : time();
  $css_dir = get_stylesheet_directory() . '/css';
  if (is_dir($css_dir)) {
    $mod_files = glob($css_dir . '/*.css');
    if ($mod_files) {
      foreach ($mod_files as $mf) {
        $css_mtime = max($css_mtime, filemtime($mf));
      }
    }
  }

  wp_enqueue_style(
    'wawahz-style',
    get_stylesheet_uri(),
    array('google-fonts-roboto', 'material-symbols-rounded'),
    $css_mtime
  );

  // テーマ用フロントエンドUIスクリプト (ドロワー開閉、テーマ切替など)
  if (file_exists(get_template_directory() . '/js/theme.js')) {
    wp_enqueue_script(
      'wawahz-theme-js',
      get_template_directory_uri() . '/js/theme.js',
      array(),
      filemtime(get_template_directory() . '/js/theme.js'),
      true
    );

    // Now Playing 楽曲追加モーダル用の設定 (AJAX URL / nonce / 表示文言)
    wp_localize_script('wawahz-theme-js', 'wawahzNowPlaying', array(
      'ajaxUrl'  => admin_url('admin-ajax.php'),
      'action'   => 'wawahz_add_track',
      'nonce'    => wp_create_nonce('wawahz_add_track'),
      'canAdd'   => wawahz_can_add_track(),
      'messages' => array(
        'sending' => __('追加しています…', 'wawahz'),
        'added'   => __('楽曲を追加しました。', 'wawahz'),
        'error'   => __('楽曲を追加できませんでした。時間をおいてもう一度お試しください。', 'wawahz'),
      ),
    ));
  }

  // Gallery 画面2 のミニゲーム枠 (ゲーム本体の p5play は起動時に iframe で遅延読み込み)
  $mini_game_script = get_template_directory() . '/js/mini-game.js';
  if (file_exists($mini_game_script) && (wawahz_view() === 'game' || is_page_template('page-game.php'))) {
    wp_enqueue_script(
      'wawahz-mini-game',
      get_template_directory_uri() . '/js/mini-game.js',
      array(),
      filemtime($mini_game_script),
      true
    );
  }
}
add_action('wp_enqueue_scripts', 'wawahz_scripts');

// ウィジェットエリアの登録
function wawahz_widgets_init()
{
  register_sidebar(array(
    'name'          => __('メインサイドバー', 'wawahz'),
    'id'            => 'sidebar-1',
    'description'   => __('メインコンテンツの横に表示されるウィジェットエリアです。', 'wawahz'),
    'before_widget' => '<aside id="%1$s" class="widget %2$s">',
    'after_widget'  => '</aside>',
    'before_title'  => '<h2 class="side-title">',
    'after_title'   => '</h2>',
  ));
}
add_action('widgets_init', 'wawahz_widgets_init');

// 抜粋文の文字数調整
function wawahz_excerpt_length($length)
{
  return 80;
}
add_filter('excerpt_length', 'wawahz_excerpt_length', 999);

// 抜粋文の末尾文字列調整
function wawahz_excerpt_more($more)
{
  return '...';
}
add_filter('excerpt_more', 'wawahz_excerpt_more');

// サムネイル未設定時のデフォルト画像URL取得ヘルパー
function wawahz_get_default_thumbnail_url()
{
  // 既存のSVGまたはテーマ内デフォルト画像
  $default_svg = get_template_directory_uri() . '/images/music_shimmer.svg?v=20250925_02';
  return esc_url($default_svg);
}

// ==========================================================================
// Gutenberg カスタムブロック & ブロックパターン & ショートコード
// Now Playing ギャラリー (Material 3 Expressive)
// ==========================================================================

/**
 * Now Playing ギャラリーの動的レンダリング共通コールバック
 */
function wawahz_render_nowplaying_block($attributes = array())
{
  ob_start();
  get_template_part('template-parts/nowplaying-gallery', null, $attributes);
  return ob_get_clean();
}

/**
 * Gutenberg カスタムブロックの登録
 */
function wawahz_register_custom_blocks()
{
  // ブロック用スクリプトの登録
  $script_asset_path = get_template_directory() . '/js/block-nowplaying.js';
  if (file_exists($script_asset_path)) {
    wp_register_script(
      'wawahz-block-nowplaying',
      get_template_directory_uri() . '/js/block-nowplaying.js',
      array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
      filemtime($script_asset_path)
    );

    register_block_type('wawahz/nowplaying-gallery', array(
      'api_version'     => 2,
      'editor_script'   => 'wawahz-block-nowplaying',
      'render_callback' => 'wawahz_render_nowplaying_block',
      'attributes'      => array(
        'limit' => array(
          'type'    => 'number',
          'default' => 6,
        ),
        'showHeader' => array(
          'type'    => 'boolean',
          'default' => true,
        ),
      ),
    ));
  }
}
add_action('init', 'wawahz_register_custom_blocks');

/**
 * ブロックエディタ編集画面でのアセット確実読み込み
 */
function wawahz_enqueue_block_editor_assets()
{
  $script_asset_path = get_template_directory() . '/js/block-nowplaying.js';
  if (file_exists($script_asset_path)) {
    wp_enqueue_script(
      'wawahz-block-nowplaying',
      get_template_directory_uri() . '/js/block-nowplaying.js',
      array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
      filemtime($script_asset_path),
      true
    );
  }
}
add_action('enqueue_block_editor_assets', 'wawahz_enqueue_block_editor_assets');

/**
 * ショートコード [nowplaying_gallery]
 * 記事本文やウィジェットで [nowplaying_gallery limit="6"] で呼び出し可能
 */
function wawahz_nowplaying_gallery_shortcode($atts)
{
  $atts = shortcode_atts(array(
    'limit'       => 6,
    'show_header' => true,
  ), $atts, 'nowplaying_gallery');

  return wawahz_render_nowplaying_block(array(
    'limit'       => intval($atts['limit']),
    'show_header' => filter_var($atts['show_header'], FILTER_VALIDATE_BOOLEAN),
  ));
}
add_shortcode('nowplaying_gallery', 'wawahz_nowplaying_gallery_shortcode');

/**
 * Gutenberg ブロックパターンの登録
 * 投稿編集画面の「パターン」タブからワンクリックで挿入可能
 */
function wawahz_register_block_patterns()
{
  if (function_exists('register_block_pattern_category')) {
    register_block_pattern_category(
      'wawahz',
      array('label' => __('Wawa.hz テーマ', 'wawahz'))
    );
  }

  if (function_exists('register_block_pattern')) {
    register_block_pattern(
      'wawahz/nowplaying-gallery-pattern',
      array(
        'title'       => __('Now Playing 音楽ギャラリー', 'wawahz'),
        'description' => __('投稿の音声・動画・YouTubeブロックを表示する音楽ギャラリー', 'wawahz'),
        'categories'  => array('wawahz', 'media', 'gallery'),
        'content'     => '<!-- wp:wawahz/nowplaying-gallery {"limit":6,"showHeader":true} /-->',
      )
    );
  }
}
add_action('init', 'wawahz_register_block_patterns');

// ==========================================================================
// Now Playing 投稿設定メタボックス (YouTube URL, アーティスト, アルバム, ムード)
// ==========================================================================

/**
 * カスタムフィールドの登録（Gutenberg / REST API 対応）
 */
function wawahz_register_nowplaying_meta()
{
  $meta_fields = array(
    'wawahz_youtube_url' => array(
      'type'         => 'string',
      'description'  => 'YouTube URL または 動画ID',
      'single'       => true,
      'show_in_rest' => true,
    ),
    'wawahz_artist' => array(
      'type'         => 'string',
      'description'  => 'アーティスト名',
      'single'       => true,
      'show_in_rest' => true,
    ),
    'wawahz_album' => array(
      'type'         => 'string',
      'description'  => 'アルバム / サブタイトル',
      'single'       => true,
      'show_in_rest' => true,
    ),
    'wawahz_mood' => array(
      'type'         => 'string',
      'description'  => 'ムード / ジャンル',
      'single'       => true,
      'show_in_rest' => true,
    ),
  );

  foreach ($meta_fields as $key => $args) {
    register_post_meta('post', $key, array(
      'show_in_rest'      => $args['show_in_rest'],
      'single'            => $args['single'],
      'type'              => $args['type'],
      'description'       => $args['description'],
      'sanitize_callback' => 'sanitize_text_field',
      'auth_callback'     => function () {
        return current_user_can('edit_posts');
      },
    ));
  }
}
add_action('init', 'wawahz_register_nowplaying_meta');

/**
 * 投稿画面に Now Playing メタボックスを追加
 */
function wawahz_add_nowplaying_metabox()
{
  add_meta_box(
    'wawahz_nowplaying_metabox',
    '🎵 Now Playing 楽曲設定（音声・動画・YouTube）',
    'wawahz_render_nowplaying_metabox',
    'post',
    'normal',
    'high'
  );
}
add_action('add_meta_boxes', 'wawahz_add_nowplaying_metabox');

/**
 * メタボックス描画コールバック
 */
function wawahz_render_nowplaying_metabox($post)
{
  wp_nonce_field('wawahz_save_nowplaying_meta', 'wawahz_nowplaying_nonce');

  $youtube_url = get_post_meta($post->ID, 'wawahz_youtube_url', true);
  $artist = get_post_meta($post->ID, 'wawahz_artist', true);
  $album = get_post_meta($post->ID, 'wawahz_album', true);
  $mood = get_post_meta($post->ID, 'wawahz_mood', true);
  ?>
  <div style="display: grid; gap: 14px; padding: 6px 0;">
    <p style="margin: 0; color: #555; font-size: 13px;">
      <strong>Now Playing ギャラリー</strong> の掲載方法と、楽曲の補足情報（任意）を設定できます。<br>
      本文に「音声」「動画」「YouTube」ブロックを追加して公開すると、ギャラリーに自動表示されます。カテゴリー指定は不要です。<br>複数ある場合は最初の対応メディアブロックを使用します。ブロックを使用する場合、下のYouTube URLは空欄で構いません。
    </p>

    <div>
      <label for="wawahz_youtube_url" style="display: block; font-weight: 600; margin-bottom: 4px;">
        1. YouTube URL または 動画ID（任意・従来方式）
      </label>
      <input type="text" id="wawahz_youtube_url" name="wawahz_youtube_url" value="<?php echo esc_attr($youtube_url); ?>" placeholder="例: https://www.youtube.com/watch?v=xxxxxxxxxxx または xxxxxxxxxxx" style="width: 100%; max-width: 600px; padding: 8px 12px; border-radius: 6px; border: 1px solid #ccd0d4;">
      <p style="margin: 4px 0 0; color: #666; font-size: 12px;">
        YouTubeの通常URL、短縮URL（youtu.be）、または11文字の動画IDを入力できます。本文に対応メディアブロックがない場合、この動画の公式プレイヤーをカード内に表示します。
      </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
      <div>
        <label for="wawahz_artist" style="display: block; font-weight: 600; margin-bottom: 4px;">
          2. アーティスト名
        </label>
        <input type="text" id="wawahz_artist" name="wawahz_artist" value="<?php echo esc_attr($artist); ?>" placeholder="例: wawa.hz sound lab" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ccd0d4;">
        <p style="margin: 4px 0 0; color: #666; font-size: 12px;">空欄の場合は投稿者の表示名が使用されます。</p>
      </div>

      <div>
        <label for="wawahz_album" style="display: block; font-weight: 600; margin-bottom: 4px;">
          3. アルバム / サブタイトル
        </label>
        <input type="text" id="wawahz_album" name="wawahz_album" value="<?php echo esc_attr($album); ?>" placeholder="例: Resonance in 412p" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ccd0d4;">
        <p style="margin: 4px 0 0; color: #666; font-size: 12px;">空欄の場合は投稿日（日付）が使用されます。</p>
      </div>

      <div>
        <label for="wawahz_mood" style="display: block; font-weight: 600; margin-bottom: 4px;">
          4. ムード / タグ
        </label>
        <input type="text" id="wawahz_mood" name="wawahz_mood" value="<?php echo esc_attr($mood); ?>" placeholder="例: Chill & Ambient" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #ccd0d4;">
        <p style="margin: 4px 0 0; color: #666; font-size: 12px;">空欄の場合は投稿カテゴリー名が使用されます。</p>
      </div>
    </div>
  </div>
  <?php
}

/**
 * メタボックスの保存処理
 */
function wawahz_save_nowplaying_metabox($post_id)
{
  if (!isset($_POST['wawahz_nowplaying_nonce']) || !wp_verify_nonce($_POST['wawahz_nowplaying_nonce'], 'wawahz_save_nowplaying_meta')) {
    return;
  }

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  $fields = array('wawahz_youtube_url', 'wawahz_artist', 'wawahz_album', 'wawahz_mood');
  foreach ($fields as $field) {
    if (isset($_POST[$field])) {
      update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
    }
  }
}
add_action('save_post', 'wawahz_save_nowplaying_metabox');

/**
 * ホーム画面カード設定を保持する固定ページの ID を解決。
 * 1. 静的フロントページ → 2. スラッグ home-settings の固定ページ → 3. なし (0 = 自動表示)
 */
function wawahz_home_settings_page_id()
{
  if (get_option('show_on_front') === 'page') {
    $front_id = (int) get_option('page_on_front');
    if ($front_id && get_post_status($front_id) === 'publish') {
      return $front_id;
    }
  }
  $settings = get_page_by_path('home-settings');
  if ($settings && $settings->post_status === 'publish') {
    return (int) $settings->ID;
  }
  return 0;
}

/**
 * ホーム NowPlaying カードの候補楽曲 (メディア付き投稿・最新30件)
 */
function wawahz_home_music_candidates()
{
  $candidates = array();
  $posts = get_posts(array(
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'has_password'   => false,
    'posts_per_page' => 50,
    'orderby'        => array('date' => 'DESC', 'ID' => 'DESC'),
  ));
  foreach ($posts as $post) {
    if (function_exists('wawahz_post_media') && wawahz_post_media($post)) {
      $candidates[] = $post;
      if (count($candidates) >= 30) {
        break;
      }
    }
  }
  return $candidates;
}

/**
 * ホーム Gallery カードの候補作品 (work カテゴリー・最新50件)
 */
function wawahz_home_work_candidates()
{
  $work = get_category_by_slug('work');
  if (!$work) {
    return array();
  }
  return get_posts(array(
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'has_password'   => false,
    'posts_per_page' => 50,
    'category__in'   => array($work->term_id),
    'orderby'        => array('date' => 'DESC', 'ID' => 'DESC'),
  ));
}

/**
 * 固定ページにホーム画面カード設定メタボックスを追加
 */
function wawahz_add_home_cards_metabox()
{
  add_meta_box(
    'wawahz_home_cards_metabox',
    '🏠 ホーム画面カード設定（NowPlaying / Gallery）',
    'wawahz_render_home_cards_metabox',
    'page',
    'normal',
    'high'
  );
}
add_action('add_meta_boxes_page', 'wawahz_add_home_cards_metabox');

/**
 * ホーム画面カード設定メタボックスの描画
 */
function wawahz_render_home_cards_metabox($post)
{
  wp_nonce_field('wawahz_save_home_cards_meta', 'wawahz_home_cards_nonce');

  $settings_id = wawahz_home_settings_page_id();
  $np_id   = (int) get_post_meta($post->ID, 'wawahz_home_nowplaying', true);
  $work_1  = (int) get_post_meta($post->ID, 'wawahz_home_work_1', true);
  $work_2  = (int) get_post_meta($post->ID, 'wawahz_home_work_2', true);
  $is_active = ($settings_id === (int) $post->ID);

  $music_posts = wawahz_home_music_candidates();
  $work_posts  = wawahz_home_work_candidates();
  ?>
  <div style="display: grid; gap: 14px; padding: 6px 0;">
    <?php if ($is_active) : ?>
      <p style="margin: 0; padding: 8px 12px; background: #e7f6f0; border-left: 4px solid #2f7475; font-size: 13px;">
        ✅ このページの設定がホーム画面に反映されています。
      </p>
    <?php else : ?>
      <p style="margin: 0; padding: 8px 12px; background: #f6f6f6; border-left: 4px solid #ccc; font-size: 13px; color: #555;">
        この設定は「フロントページに指定した固定ページ」またはスラッグ <code>home-settings</code> の固定ページでのみホーム画面に反映されます。「自動」を選んだ項目は最新記事から自動表示されます。
      </p>
    <?php endif; ?>

    <div>
      <label for="wawahz_home_nowplaying" style="display: block; font-weight: 600; margin-bottom: 4px;">
        1. NowPlaying カードに表示する楽曲
      </label>
      <select id="wawahz_home_nowplaying" name="wawahz_home_nowplaying" style="width: 100%; max-width: 600px; padding: 8px 12px;">
        <option value="0" <?php selected($np_id, 0); ?>>自動（最新の楽曲）</option>
        <?php foreach ($music_posts as $mp) : ?>
          <option value="<?php echo esc_attr($mp->ID); ?>" <?php selected($np_id, $mp->ID); ?>>
            <?php echo esc_html(get_the_title($mp) . '（' . get_the_date('Y-m-d', $mp) . '）'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="wawahz_home_work_1" style="display: block; font-weight: 600; margin-bottom: 4px;">
        2. Gallery カード（左）に表示する作品
      </label>
      <select id="wawahz_home_work_1" name="wawahz_home_work_1" style="width: 100%; max-width: 600px; padding: 8px 12px;">
        <option value="0" <?php selected($work_1, 0); ?>>自動（最新の作品）</option>
        <?php foreach ($work_posts as $wp) : ?>
          <option value="<?php echo esc_attr($wp->ID); ?>" <?php selected($work_1, $wp->ID); ?>>
            <?php echo esc_html(get_the_title($wp) . '（' . get_the_date('Y-m-d', $wp) . '）'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="wawahz_home_work_2" style="display: block; font-weight: 600; margin-bottom: 4px;">
        3. Gallery カード（右）に表示する作品
      </label>
      <select id="wawahz_home_work_2" name="wawahz_home_work_2" style="width: 100%; max-width: 600px; padding: 8px 12px;">
        <option value="0" <?php selected($work_2, 0); ?>>自動（最新の作品）</option>
        <?php foreach ($work_posts as $wp) : ?>
          <option value="<?php echo esc_attr($wp->ID); ?>" <?php selected($work_2, $wp->ID); ?>>
            <?php echo esc_html(get_the_title($wp) . '（' . get_the_date('Y-m-d', $wp) . '）'); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p style="margin: 4px 0 0; color: #666; font-size: 12px;">空き枠は最新の作品で自動補完されます。作品が無い場合は「作品を準備中」と表示されます。</p>
    </div>
  </div>
  <?php
}

/**
 * ホーム画面カード設定の保存処理
 */
function wawahz_save_home_cards_metabox($post_id)
{
  if (!isset($_POST['wawahz_home_cards_nonce']) || !wp_verify_nonce($_POST['wawahz_home_cards_nonce'], 'wawahz_save_home_cards_meta')) {
    return;
  }

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  if (!current_user_can('edit_page', $post_id)) {
    return;
  }

  // NowPlaying: 公開済み・メディア付きの投稿のみ有効。NG → 自動 (0)
  $np_id = isset($_POST['wawahz_home_nowplaying']) ? absint($_POST['wawahz_home_nowplaying']) : 0;
  if ($np_id) {
    $np_post = get_post($np_id);
    if (!$np_post || $np_post->post_status !== 'publish' || !wawahz_post_media($np_post)) {
      $np_id = 0;
    }
  }
  update_post_meta($post_id, 'wawahz_home_nowplaying', $np_id);

  // Gallery: 公開済みの投稿のみ有効。NG → 自動 (0)
  foreach (array('wawahz_home_work_1', 'wawahz_home_work_2') as $field) {
    $work_id = isset($_POST[$field]) ? absint($_POST[$field]) : 0;
    if ($work_id) {
      $work_post = get_post($work_id);
      if (!$work_post || $work_post->post_type !== 'post' || $work_post->post_status !== 'publish') {
        $work_id = 0;
      }
    }
    update_post_meta($post_id, $field, $work_id);
  }
}
/**
 * ホーム画面 Gallery カード固定メタボックスを作品記事にも追加。
 *
 * ホーム画面の Gallery ボックス (最大2枠) の指定は home-settings 固定ページの
 * 投稿メタ (wawahz_home_work_1 / wawahz_home_work_2) が唯一の保存先。
 * 記事編集画面から同じメタを更新できるようにして、記事側から固定できるようにする。
 */
function wawahz_add_home_gallery_pin_metabox($post)
{
  if (!$post instanceof WP_Post) {
    return;
  }
  $work = get_category_by_slug('work');
  $is_work = $work && has_category($work->term_id, $post);
  if (!$is_work && !wawahz_home_gallery_slot($post->ID)) {
    return;
  }
  add_meta_box(
    'wawahz_home_gallery_pin',
    '🏠 ホーム画面 Gallery カード固定',
    'wawahz_render_home_gallery_pin_metabox',
    'post',
    'side',
    'default'
  );
}
add_action('add_meta_boxes_post', 'wawahz_add_home_gallery_pin_metabox');

/**
 * 記事がホーム画面 Gallery ボックスの何枠目に固定されているかを返す。(0 = 未固定)
 */
function wawahz_home_gallery_slot($post_id)
{
  $settings_id = wawahz_home_settings_page_id();
  if (!$settings_id) {
    return 0;
  }
  foreach (array(1, 2) as $slot) {
    if ((int) get_post_meta($settings_id, 'wawahz_home_work_' . $slot, true) === (int) $post_id) {
      return $slot;
    }
  }
  return 0;
}

/** ホーム画面 Gallery カード固定メタボックスの描画 */
function wawahz_render_home_gallery_pin_metabox($post)
{
  wp_nonce_field('wawahz_save_home_gallery_pin', 'wawahz_home_gallery_pin_nonce');
  $settings_id = wawahz_home_settings_page_id();
  $slot = wawahz_home_gallery_slot($post->ID);
  ?>
  <p style="margin: 0 0 8px; font-size: 12px; color: #555;">
    ホーム画面の <strong>Gallery ボックス</strong>に固定表示できます。選び直すと即時反映されます。
  </p>
  <select name="wawahz_home_gallery_pin" id="wawahz_home_gallery_pin" style="width: 100%;">
    <option value="0" <?php selected($slot, 0); ?>>固定しない（新着順で表示）</option>
    <option value="1" <?php selected($slot, 1); ?>>1つ目に固定（左カード）</option>
    <option value="2" <?php selected($slot, 2); ?>>2つ目に固定（右カード）</option>
  </select>
  <?php if ($post->post_status !== 'publish') : ?>
    <p style="margin: 8px 0 0; font-size: 12px; color: #8a6d3b;">下書きのため、公開するとホーム画面に表示されます。</p>
  <?php endif; ?>
  <?php if (!$settings_id) : ?>
    <p style="margin: 8px 0 0; font-size: 12px; color: #b3261e;">
      反映先が見つかりません。スラッグ <code>home-settings</code> の固定ページを作成するか、「設定 → 表示設定」でフロントページに固定ページを指定してください。
    </p>
  <?php else : ?>
    <p style="margin: 8px 0 0; font-size: 11px; color: #666;">反映先: <?php echo esc_html(get_the_title($settings_id)); ?></p>
  <?php endif; ?>
  <?php
}

/**
 * 記事側の Gallery 固定設定を保存する。
 * 空き枠はホーム画面側で最新の作品から自動補完されるため、解除時は 0 を書き込む。
 */
function wawahz_save_home_gallery_pin_metabox($post_id)
{
  if (!isset($_POST['wawahz_home_gallery_pin_nonce']) || !wp_verify_nonce($_POST['wawahz_home_gallery_pin_nonce'], 'wawahz_save_home_gallery_pin')) {
    return;
  }
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }
  if (!current_user_can('edit_post', $post_id)) {
    return;
  }
  $settings_id = wawahz_home_settings_page_id();
  if (!$settings_id || (int) $settings_id === (int) $post_id) {
    return;
  }
  $slot = isset($_POST['wawahz_home_gallery_pin']) ? absint($_POST['wawahz_home_gallery_pin']) : 0;
  if ($slot > 2) {
    $slot = 0;
  }
  if ($slot && get_post_status($post_id) !== 'publish') {
    $slot = 0;
  }
  foreach (array(1, 2) as $index) {
    $field = 'wawahz_home_work_' . $index;
    if ($slot === $index) {
      update_post_meta($settings_id, $field, (int) $post_id);
    } elseif ((int) get_post_meta($settings_id, $field, true) === (int) $post_id) {
      update_post_meta($settings_id, $field, 0);
    }
  }
}
add_action('save_post_post', 'wawahz_save_home_gallery_pin_metabox');

