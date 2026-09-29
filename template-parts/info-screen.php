<?php

/**
 * template-parts/info-screen.php
 * 静的見本 (backup_static/index.html 行550〜715) および画像1に完全準拠した
 * サイト情報・Now Playing Track Station 画面。
 */
$tracks = wawahz_nowplaying_posts(10);
$selected_track = $tracks ? $tracks[0] : null;
$selected_post = $selected_track ? $selected_track['post'] : null;
$selected_media = $selected_track ? $selected_track['media'] : null;
$selected_yt_id = ($selected_media && $selected_media['type'] === 'youtube') ? $selected_media['youtube_id'] : '';
$selected_artist = $selected_post ? (get_post_meta($selected_post->ID, 'wawahz_artist', true) ?: get_the_author_meta('display_name', $selected_post->post_author)) : 'wawa.hz sound lab';
$selected_title = $selected_post ? get_the_title($selected_post) : 'Midnight Shimmer';
$selected_album = $selected_post ? get_post_meta($selected_post->ID, 'wawahz_album', true) : '';
$selected_mood = $selected_post ? get_post_meta($selected_post->ID, 'wawahz_mood', true) : '';
$selected_year = $selected_post ? get_the_date('Y', $selected_post) : '2026';
$selected_excerpt = $selected_post ? wp_strip_all_tags(get_the_excerpt($selected_post)) : '';
// YouTube 以外のメディアは <template> の標準プレイヤーで再生する (先頭 = index 0)
$selected_media_id = ($selected_media && $selected_media['type'] !== 'youtube') ? 'wawahz-info-media-0' : '';
$selected_post_url = $selected_post ? get_permalink($selected_post) : '';
?>
<section class="screen-view active info-screen-view" id="screen-info" aria-label="<?php esc_attr_e('Site Information & Nowplaying Screen', 'wawahz'); ?>">
  <h2 class="visually-hidden"><?php esc_html_e('サイト情報・Now Playing', 'wawahz'); ?></h2>

  <div class="site-info-footer-container">

    <!-- 1. 上部: Nowplaying ドロップダウン & プレイヤーステーション (Nowplayingギャラリー同期) -->
    <div class="site-info-card np-hub-card">
      <div class="np-hub-header">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span class="material-symbols-rounded" aria-hidden="true" style="color: var(--md-sys-color-primary); font-size: 22px;">graphic_eq</span>
          <h3 class="title-medium" style="font-weight: 700; color: var(--md-sys-color-on-surface); margin: 0;">Now Playing Track Station</h3>
        </div>
        <span class="nowplaying-yt-tag" style="font-size: 11px; padding: 2px 8px;">
          <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 13px;">smart_display</span>
          YouTube再生連携
        </span>
      </div>

      <!-- Nowplaying ドロップダウン (全Nowplaying曲と双方向同期) -->
      <div style="width: 100%; position: relative;">
        <div class="m3-dropdown-field" id="info-nowplaying-dropdown-field" tabindex="0" role="combobox" aria-haspopup="listbox" aria-expanded="false" aria-controls="info-nowplaying-dropdown-menu" aria-label="Now Playing 楽曲選択" title="<?php esc_attr_e('曲を選択してプレイヤーモーダルで再生', 'wawahz'); ?>">
          <span class="dropdown-label" id="info-nowplaying-dropdown-label">Nowplaying</span>
          <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
            <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 18px; color: var(--md-sys-color-primary); flex-shrink: 0;">headphones</span>
            <span id="info-nowplaying-dropdown-selected" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600;">
              <?php echo esc_html($selected_title . ' - ' . $selected_artist); ?>
            </span>
          </div>
          <span class="material-symbols-rounded" aria-hidden="true" style="flex-shrink: 0;">arrow_drop_down</span>
        </div>
        <div class="m3-dropdown-menu info-np-dropdown-menu" id="info-nowplaying-dropdown-menu" role="listbox" aria-labelledby="info-nowplaying-dropdown-label" style="display: none;">
          <?php if ($tracks) : ?>
            <?php foreach ($tracks as $index => $t) :
              $tp = $t['post'];
              $m = $t['media'];
              $yt_id = ($m['type'] === 'youtube') ? $m['youtube_id'] : '';
              $artist = get_post_meta($tp->ID, 'wawahz_artist', true) ?: get_the_author_meta('display_name', $tp->post_author);
              $album = get_post_meta($tp->ID, 'wawahz_album', true) ?: '';
              $mood = get_post_meta($tp->ID, 'wawahz_mood', true) ?: '';
              $year = get_the_date('Y', $tp);
              $excerpt = wp_strip_all_tags(get_the_excerpt($tp));
              $media_id = 'wawahz-info-media-' . $index;
            ?>
              <div class="m3-dropdown-item <?php echo $index === 0 ? 'selected' : ''; ?>"
                role="option"
                tabindex="0"
                data-post-id="<?php echo esc_attr($tp->ID); ?>"
                data-youtube-id="<?php echo esc_attr($yt_id); ?>"
                data-title="<?php echo esc_attr(get_the_title($tp)); ?>"
                data-artist="<?php echo esc_attr($artist); ?>"
                data-album="<?php echo esc_attr($album); ?>"
                data-mood="<?php echo esc_attr($mood); ?>"
                data-year="<?php echo esc_attr($year); ?>"
                data-commentary="<?php echo esc_attr($excerpt); ?>"
                data-post-url="<?php echo esc_url(get_permalink($tp)); ?>"
                data-media-template-id="<?php echo esc_attr($media_id); ?>">
                <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 16px; margin-right: 8px; color: var(--md-sys-color-primary);">music_note</span>
                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo esc_html(get_the_title($tp) . ' - ' . $artist); ?></span>
              </div>
              <?php /* YouTube 以外のメディアは投稿本文の標準プレイヤーをモーダルで再生する */ ?>
              <?php if ($m['type'] !== 'youtube') : ?>
                <template id="<?php echo esc_attr($media_id); ?>" class="nowplaying-inline-media"><?php echo wawahz_render_gallery_media($m); ?></template>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else : ?>
            <div class="m3-dropdown-item" style="opacity: 0.6;"><?php esc_html_e('登録楽曲なし', 'wawahz'); ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- クイックアクション (プレイヤーモーダル起動 / ギャラリーへ移動) -->
      <div class="np-hub-actions">
        <button type="button" class="m3-btn m3-btn-filled" id="info-open-player-btn" style="flex: 1; height: 42px; font-size: 13px;"
          data-open-player
          data-youtube-id="<?php echo esc_attr($selected_yt_id); ?>"
          data-title="<?php echo esc_attr($selected_title); ?>"
          data-artist="<?php echo esc_attr($selected_artist); ?>"
          data-album="<?php echo esc_attr($selected_album); ?>"
          data-mood="<?php echo esc_attr($selected_mood); ?>"
          data-year="<?php echo esc_attr($selected_year); ?>"
          data-commentary="<?php echo esc_attr($selected_excerpt); ?>"
          data-post-url="<?php echo esc_url($selected_post_url); ?>"
          data-media-template-id="<?php echo esc_attr($selected_media_id); ?>">
          <span class="material-symbols-rounded" aria-hidden="true">play_circle</span>
          <span><?php esc_html_e('モーダルで再生', 'wawahz'); ?></span>
        </button>
        <a href="<?php echo esc_url(wawahz_view_url('nowplaying')); ?>" class="m3-btn m3-btn-tonal" id="info-goto-gallery-btn" style="flex: 1; height: 42px; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
          <span class="material-symbols-rounded" aria-hidden="true">grid_view</span>
          <span><?php esc_html_e('ギャラリーを開く', 'wawahz'); ?></span>
        </a>
      </div>
    </div>

    <!-- 2. 中央: サイト概要・コンセプトカード (About wawa.hz) -->
    <div class="site-info-card brand-about-card">
      <div class="brand-about-top">
        <div class="brand-logo-badge">
          <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 32px; color: var(--md-sys-color-primary);">graphic_eq</span>
          <span class="brand-wordmark" style="font-size: 28px; font-weight: 800; color: var(--md-sys-color-on-surface); letter-spacing: -0.5px;">wawa<span class="brand-wordmark-suffix" style="color: var(--md-sys-color-primary);">.hz</span></span>
        </div>
        <div class="brand-tags-row">
          <span class="preview-selector-chip" style="background: rgba(0, 105, 110, 0.2); color: var(--md-sys-color-primary); font-weight: 600;">v2.4 LTS</span>
          <span class="preview-selector-chip">M3 Expressive</span>
          <span class="preview-selector-chip">PHP 8.3 &amp; SQLite</span>
          <span class="preview-selector-chip">Local-First</span>
        </div>
      </div>

      <p class="body-medium brand-about-desc">
        <strong>wawa.hz（ワワ・ヘルツ）</strong> は、心地よいアンビエント音響スケッチ、自作キーボードや珈琲の記録、デザインと思索のログを綴るパーソナルWebメディア・アーカイブです。日々の暮らしの穏やかな波長（hz）をお届けします。
      </p>

      <div class="site-spec-grid">
        <div class="site-spec-item">
          <span class="material-symbols-rounded" aria-hidden="true">palette</span>
          <div>
            <strong>Design System</strong>
            <span>Material 3 Expressive &amp; Dark Mode</span>
          </div>
        </div>
        <div class="site-spec-item">
          <span class="material-symbols-rounded" aria-hidden="true">equalizer</span>
          <div>
            <strong>Audio Architecture</strong>
            <span>Web Audio Synthesizer &amp; YouTube ToS Embed</span>
          </div>
        </div>
        <div class="site-spec-item">
          <span class="material-symbols-rounded" aria-hidden="true">database</span>
          <div>
            <strong>Storage &amp; Sync</strong>
            <span>IndexedDB Client + MAMP PHP 8.3 RESTful API</span>
          </div>
        </div>
        <div class="site-spec-item">
          <span class="material-symbols-rounded" aria-hidden="true">verified_user</span>
          <div>
            <strong>Security &amp; Policy</strong>
            <span>Anti-Spam Filter &amp; Rate-Limit Cooldown</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. 下部: ナビゲーション・ダイアログ導線 (Info, SiteMap, Form, Policy) & サイトリンク -->
    <div class="site-info-card site-nav-directory-card">
      <h3 class="title-medium" style="margin: 0 0 12px 0; font-weight: 700; color: var(--md-sys-color-on-surface);">Quick Navigation &amp; Modals</h3>

      <!-- 4つのダイアログ起動ボタン (Info, SiteMap, Form, Policy) -->
      <div class="info-action-buttons-grid">
        <button class="m3-btn m3-btn-elevated info-grid-btn" id="btn-dialog-info" type="button" data-open-dialog="info-dialog">
          <span class="material-symbols-rounded" aria-hidden="true" style="color: var(--md-sys-color-primary);">info</span>
          <span>Info (概要)</span>
        </button>
        <button class="m3-btn m3-btn-elevated info-grid-btn" id="btn-dialog-sitemap" type="button" data-open-dialog="sitemap-dialog">
          <span class="material-symbols-rounded" aria-hidden="true" style="color: var(--md-sys-color-primary);">lan</span>
          <span>SiteMap</span>
        </button>
        <button class="m3-btn m3-btn-elevated info-grid-btn" id="btn-dialog-form" type="button" data-open-dialog="form-dialog">
          <span class="material-symbols-rounded" aria-hidden="true" style="color: var(--md-sys-color-primary);">mail</span>
          <span>Form (連絡)</span>
        </button>
        <?php if (get_privacy_policy_url()) : ?>
          <a class="m3-btn m3-btn-elevated info-grid-btn" id="btn-dialog-policy" href="<?php echo esc_url(get_privacy_policy_url()); ?>" style="text-decoration: none;">
            <span class="material-symbols-rounded" aria-hidden="true" style="color: var(--md-sys-color-primary);">policy</span>
            <span>Policy (規約)</span>
          </a>
        <?php else : ?>
          <button class="m3-btn m3-btn-elevated info-grid-btn" id="btn-dialog-policy" type="button" data-open-dialog="policy-dialog">
            <span class="material-symbols-rounded" aria-hidden="true" style="color: var(--md-sys-color-primary);">policy</span>
            <span>Policy (規約)</span>
          </button>
        <?php endif; ?>
      </div>

      <hr style="border: none; border-top: 1px solid var(--md-sys-color-outline-variant); margin: 16px 0;">

      <!-- サイトリンク集 (2カラムリンクディレクトリ) -->
      <div class="site-footer-links-row">
        <div class="footer-links-col">
          <span class="label-large" style="color: var(--md-sys-color-primary); font-weight: 700; display: block; margin-bottom: 8px;">コンテンツ一覧</span>
          <ul class="footer-links-list">
            <li><a href="<?php echo esc_url(home_url('/')); ?>">🏠 ホーム (Pickup &amp; Lately)</a></li>
            <li><a href="<?php echo esc_url(wawahz_view_url('nowplaying')); ?>">🎧 Now Playing ギャラリー</a></li>
            <li><a href="<?php echo esc_url(wawahz_view_url('gallery')); ?>">🎨 Works Gallery (作品集)</a></li>
            <li><a href="<?php echo esc_url(add_query_arg('s', '', home_url('/'))); ?>">🔍 スマート検索 (Voice / Category)</a></li>
            <li><a href="<?php echo esc_url(wawahz_view_url('category')); ?>">✍️ 記事アーカイブ &amp; 投稿</a></li>
          </ul>
        </div>

        <div class="footer-links-col">
          <span class="label-large" style="color: var(--md-sys-color-primary); font-weight: 700; display: block; margin-bottom: 8px;">コミュニティ</span>
          <ul class="footer-links-list">
            <li><a href="https://github.com/yawara404" target="_blank" rel="noopener noreferrer">💻 GitHub リポジトリ</a></li>
            <li><a href="https://youtube.com" target="_blank" rel="noopener noreferrer">📺 YouTube チャンネル</a></li>
            <li><a href="https://x.com/ya_ya_moderate" target="_blank" rel="noopener noreferrer">𝕏 (Twitter) 公式アカウント</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- 4. 最下部: コピーライト & ToS免責事項バー -->
    <div class="site-footer-bottom-bar">
      <p class="body-small" style="color: var(--md-sys-color-on-surface-variant); margin: 0 0 8px 0; font-size: 11px; line-height: 1.5;">
        ※ 本サイトで提供される楽曲プレビューは、YouTube利用規約およびデベロッパーポリシー（Section 4.A / 4.D / 8.B）に完全準拠した公式IFrameプレイヤーを通じて再生されます。
      </p>
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <p class="label-medium" style="color: var(--md-sys-color-secondary); margin: 0; font-weight: 500;">
          &copy; <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?> — All Rights Reserved.
        </p>
      </div>
    </div>
  </div>
</section>