      <!-- フッター: 1画面につき1つだけ表示する。 -->
      <footer class="site-footer">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a>
        <nav aria-label="<?php esc_attr_e('フッターナビゲーション', 'wawahz'); ?>">
          <ul>
            <li><a href="<?php echo esc_url(wawahz_view_url('info')); ?>"><?php esc_html_e('サイト情報', 'wawahz'); ?></a></li>
            <li><button type="button" class="footer-modal-trigger" data-open-dialog="sitemap-dialog"><?php esc_html_e('サイトマップ', 'wawahz'); ?></button></li>
            <li><button type="button" class="footer-modal-trigger" data-open-dialog="form-dialog"><?php esc_html_e('お問い合わせ', 'wawahz'); ?></button></li>
            <?php if (get_privacy_policy_url()) : ?>
              <li><a href="<?php echo esc_url(get_privacy_policy_url()); ?>"><?php esc_html_e('プライバシーポリシー', 'wawahz'); ?></a></li>
            <?php else : ?>
              <li><button type="button" class="footer-modal-trigger" data-open-dialog="policy-dialog"><?php esc_html_e('プライバシーポリシー', 'wawahz'); ?></button></li>
            <?php endif; ?>
          </ul>
        </nav>
        <small>&copy; <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?>. All rights reserved.</small>
      </footer>

      <?php /* 3画面（画面1 ホーム ⇄ 画面2 カテゴリー ⇄ 画面3 情報欄）のページ送り。
               画面1=右のみ / 画面2=左右 / 画面3=左のみ の構成で、
               画面の左右中央に固定して横スライドで移動する。 */ ?>
      <?php $wawahz_slide_prev = wawahz_slide_prev_url(); ?>
      <?php $wawahz_slide_next = wawahz_slide_next_url(); ?>
      <?php if ($wawahz_slide_prev) : ?>
        <a class="slide-prev-btn" href="<?php echo esc_url($wawahz_slide_prev); ?>" data-slide-dir="back"
           title="<?php esc_attr_e('前の画面へ', 'wawahz'); ?>" aria-label="<?php esc_attr_e('前の画面へ（横スライド）', 'wawahz'); ?>">
          <span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
        </a>
      <?php endif; ?>
      <?php if ($wawahz_slide_next) : ?>
        <a class="slide-next-btn" href="<?php echo esc_url($wawahz_slide_next); ?>" data-slide-dir="forward"
           title="<?php esc_attr_e('次の画面へ', 'wawahz'); ?>" aria-label="<?php esc_attr_e('次の画面へ（横スライド）', 'wawahz'); ?>">
          <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
        </a>
      <?php endif; ?>
    </div><!-- /.main-content-wrapper -->
  </div><!-- /#app-container -->
</div><!-- /#app-viewport -->

<!-- ====================================================================
     下部ナビゲーションバー (モバイル用・高さ 80dp)
     ==================================================================== -->
<nav class="m3-navigation-bar mobile-only" id="mobile-bottom-nav-bar" aria-label="<?php esc_attr_e('モバイルナビゲーション', 'wawahz'); ?>">
  <?php foreach (wawahz_navigation() as $key => $item) : if ($key === 'nowplaying') { continue; } $active = wawahz_current_section() === $key; ?>
    <a class="nav-item <?php echo $active ? 'active' : ''; ?>" id="nav-item-<?php echo esc_attr($key); ?>" href="<?php echo esc_url($item['url']); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?> aria-label="<?php echo esc_attr($item['label']); ?>">
      <div class="nav-indicator">
        <span class="material-symbols-rounded <?php echo $active ? 'icon-filled' : ''; ?>" aria-hidden="true"><?php echo esc_html($item['icon']); ?></span>
      </div>
      <span class="nav-label"><?php echo esc_html($item['label']); ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<!-- ====================================================================
     M3 Expressive スライドドロワー (Hamburger Navigation Drawer)
     ==================================================================== -->
<div class="m3-slide-drawer-backdrop" id="slide-drawer-backdrop" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('ナビゲーションメニュー', 'wawahz'); ?>">
  <aside class="m3-slide-drawer" id="slide-drawer">
    <!-- ドロワーヘッダー -->
    <div class="drawer-header">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="drawer-branding" id="drawer-brand-btn" title="<?php esc_attr_e('ホーム画面に戻る', 'wawahz'); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> <?php esc_attr_e('ホーム画面に戻る', 'wawahz'); ?>">
        <span class="material-symbols-rounded" aria-hidden="true" style="color: var(--md-sys-color-primary); font-size: 26px;">graphic_eq</span>
        <span class="drawer-logo"><?php echo esc_html(get_bloginfo('name')); ?></span>
        <span class="drawer-badge">M3 Expressive</span>
      </a>
      <button type="button" class="icon-btn-48" id="drawer-close-btn" title="<?php esc_attr_e('メニューを閉じる', 'wawahz'); ?>" aria-label="<?php esc_attr_e('メニューを閉じる', 'wawahz'); ?>" data-close-drawer>
        <span class="material-symbols-rounded" aria-hidden="true">close</span>
      </button>
    </div>

    <!-- ドロワー検索バー -->
    <div class="drawer-search-section">
      <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="drawer-search-bar" id="drawer-search-bar-wrap">
        <span class="material-symbols-rounded drawer-search-icon" aria-hidden="true">search</span>
        <input type="search" id="drawer-search-input" name="s" autocomplete="off" class="drawer-search-input" placeholder="<?php esc_attr_e('ブログ内の記事を検索…', 'wawahz'); ?>" aria-label="<?php esc_attr_e('記事を検索', 'wawahz'); ?>">
        <button type="button" class="drawer-search-clear-btn" id="drawer-search-clear-btn" title="<?php esc_attr_e('クリア', 'wawahz'); ?>" aria-label="<?php esc_attr_e('クリア', 'wawahz'); ?>" style="display: none;">
          <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 18px;">close</span>
        </button>
      </form>
      <div class="drawer-search-results" id="drawer-search-results" style="display: none;"></div>
    </div>

    <!-- ドロワー内：Now Playing ギャラリーへの誘導特集メニュー -->
    <a href="<?php echo esc_url(wawahz_view_url('nowplaying')); ?>" class="drawer-nowplaying-card" id="drawer-nowplaying-entry" title="<?php esc_attr_e('Now Playing ギャラリーを開く', 'wawahz'); ?>" aria-label="<?php esc_attr_e('Now Playing ギャラリーを開く', 'wawahz'); ?>">
      <div class="drawer-nowplaying-icon-wrap">
        <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 26px;">graphic_eq</span>
      </div>
      <div class="drawer-nowplaying-info">
        <div class="drawer-nowplaying-badge-row">
          <span class="drawer-nowplaying-title">Now Playing ギャラリー</span>
          <span class="drawer-nowplaying-badge">Web Audio</span>
        </div>
        <p class="drawer-nowplaying-desc"><?php esc_html_e('最近聴いている音楽とアンビエント和音スケッチ', 'wawahz'); ?></p>
      </div>
      <span class="material-symbols-rounded drawer-nowplaying-arrow" aria-hidden="true">arrow_forward</span>
    </a>

    <!-- ドロワー内ナビゲーションリスト -->
    <div class="drawer-nav-section">
      <span class="drawer-section-title">NAVIGATION</span>
      
      <a class="drawer-nav-item <?php echo (is_front_page() || is_home()) && !wawahz_view() ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/')); ?>" data-drawer-nav="home">
        <span class="material-symbols-rounded" aria-hidden="true">home</span>
        <span>Home (ホーム / dホーム)</span>
      </a>

      <a class="drawer-nav-item <?php echo wawahz_view() === 'gallery' ? 'active' : ''; ?>" href="<?php echo esc_url(wawahz_view_url('gallery')); ?>" data-drawer-nav="gallery">
        <span class="material-symbols-rounded" aria-hidden="true">palette</span>
        <span>Works Gallery (作品記事画面)</span>
      </a>

      <a class="drawer-nav-item <?php echo wawahz_view() === 'category' ? 'active' : ''; ?>" href="<?php echo esc_url(wawahz_view_url('category')); ?>" data-drawer-nav="category">
        <span class="material-symbols-rounded" aria-hidden="true">category</span>
        <span>Category Search (画面2)</span>
      </a>

      <a class="drawer-nav-item <?php echo wawahz_view() === 'post' ? 'active' : ''; ?>" href="<?php echo esc_url(wawahz_view_url('post')); ?>" data-drawer-nav="post">
        <span class="material-symbols-rounded" aria-hidden="true">send</span>
        <span>Post Archive (画面p)</span>
      </a>

      <a class="drawer-nav-item <?php echo is_search() ? 'active' : ''; ?>" href="<?php echo esc_url(add_query_arg('s', '', home_url('/'))); ?>" data-drawer-nav="search">
        <span class="material-symbols-rounded" aria-hidden="true">search</span>
        <span>Search &amp; Voice Query (画面s)</span>
      </a>

      <a class="drawer-nav-item <?php echo wawahz_view() === 'nowplaying' ? 'active' : ''; ?>" href="<?php echo esc_url(wawahz_view_url('nowplaying')); ?>" data-drawer-nav="nowplaying">
        <span class="material-symbols-rounded" aria-hidden="true">headphones</span>
        <span>Now Playing Gallery (音楽)</span>
      </a>

      <div class="drawer-divider"></div>
      <span class="drawer-section-title">INFORMATION &amp; LINKS</span>

      <a class="drawer-nav-item" href="<?php echo esc_url(wawahz_view_url('info')); ?>" id="drawer-link-info">
        <span class="material-symbols-rounded" aria-hidden="true">info</span>
        <span>About <?php echo esc_html(get_bloginfo('name')); ?></span>
      </a>
      <button type="button" class="drawer-nav-item" id="drawer-link-sitemap" data-open-dialog="sitemap-dialog">
        <span class="material-symbols-rounded" aria-hidden="true">map</span>
        <span>SiteMap (<?php esc_html_e('サイトマップ', 'wawahz'); ?>)</span>
      </button>
      <button type="button" class="drawer-nav-item" id="drawer-link-contact" data-open-dialog="form-dialog">
        <span class="material-symbols-rounded" aria-hidden="true">mail</span>
        <span>Contact Form (<?php esc_html_e('お問い合わせ', 'wawahz'); ?>)</span>
      </button>
      <?php if (get_privacy_policy_url()) : ?>
        <a class="drawer-nav-item" id="drawer-link-policy" href="<?php echo esc_url(get_privacy_policy_url()); ?>">
          <span class="material-symbols-rounded" aria-hidden="true">policy</span>
          <span>Privacy Policy (<?php esc_html_e('プライバシーポリシー', 'wawahz'); ?>)</span>
        </a>
      <?php else : ?>
        <button type="button" class="drawer-nav-item" id="drawer-link-policy" data-open-dialog="policy-dialog">
          <span class="material-symbols-rounded" aria-hidden="true">policy</span>
          <span>Privacy Policy (<?php esc_html_e('プライバシーポリシー', 'wawahz'); ?>)</span>
        </button>
      <?php endif; ?>
    </div>

    <!-- ドロワーフッター -->
    <div class="drawer-footer">
      <button type="button" class="m3-btn m3-btn-tonal theme-toggle" id="drawer-theme-toggle-btn" style="width: 100%; height: 48px;">
        <span class="material-symbols-rounded" id="drawer-theme-icon" aria-hidden="true">dark_mode</span>
        <span><?php esc_html_e('テーマ切替', 'wawahz'); ?></span>
      </button>
    </div>
  </aside>
</div>

<!-- ====================================================================
     モーダルダイアログ群
     ==================================================================== -->

<!-- 1. Now Playing 楽曲プレイヤーモーダル (YouTube公式規約準拠) -->
<div class="m3-dialog-backdrop" id="dialog-nowplaying-player" role="dialog" aria-modal="true" aria-labelledby="player-modal-title">
  <div class="m3-dialog m3-player-modal-dialog">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 8px; gap: 12px;">
      <div style="flex: 1; min-width: 0;">
        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; flex-wrap: wrap;">
          <span class="nowplaying-yt-tag" style="background: rgba(255, 68, 68, 0.16); color: #ff5252; font-size: 11px; padding: 2px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
            <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 14px;">smart_display</span>
            <span id="player-modal-badge">YouTube Player</span>
          </span>
          <span id="player-modal-year" class="preview-selector-chip" style="font-size: 11px; padding: 2px 8px; border-radius: 999px; background: rgba(255,255,255,0.08); border: none;">2026</span>
          <span id="player-modal-mood" class="preview-selector-chip" style="font-size: 11px; padding: 2px 8px; border-radius: 999px; background: rgba(0, 105, 110, 0.25); color: var(--md-sys-color-primary); border: none;">Ambient &amp; Chill</span>
        </div>
        <h3 id="player-modal-title" class="title-large" style="margin: 0; font-weight: 700; color: var(--md-sys-color-on-surface); line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">曲名</h3>
        <p id="player-modal-artist" class="body-medium" style="margin: 3px 0 0; color: var(--md-sys-color-secondary); font-weight: 500;">アーティスト名</p>
      </div>
      <button class="icon-btn-48" id="player-modal-close-btn" title="<?php esc_attr_e('閉じる', 'wawahz'); ?>" aria-label="<?php esc_attr_e('閉じる', 'wawahz'); ?>">
        <span class="material-symbols-rounded" aria-hidden="true">close</span>
      </button>
    </div>

    <!-- YouTube 公式埋め込みプレイヤーコンテナ (IFrame Player API が iframe を生成) -->
    <div class="m3-player-video-container" id="player-modal-video-container">
      <div class="m3-player-yt-host" id="player-modal-yt-host"></div>
      <!-- 投稿本文の標準メディア (audio / video) を差し込むスロット -->
      <div class="m3-player-media-slot" id="player-modal-media-slot" hidden></div>
    </div>

    <p id="player-modal-error" class="body-medium" role="status" hidden></p>

    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 4px;">
      <p id="player-modal-commentary" class="body-medium" style="color: var(--md-sys-color-on-surface-variant); font-size: 13px; line-height: 1.5; margin: 0; background: var(--md-sys-color-surface-container-high); padding: 10px 14px; border-radius: 12px;">
        楽曲の紹介文
      </p>
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <a id="player-modal-yt-external" href="#" target="_blank" rel="noopener noreferrer" class="m3-btn m3-btn-tonal" style="font-size: 12px; height: 38px; text-decoration: none; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px;">
          <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 16px;">open_in_new</span>
          <span id="player-modal-external-label">YouTube 公式で開く</span>
        </a>
        <button type="button" class="m3-btn m3-btn-outlined" id="player-modal-close-action" style="height: 38px; font-size: 12px; padding: 0 16px;">
          <?php esc_html_e('閉じる', 'wawahz'); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- 2. 作品詳細ダイアログ (Works Detail) -->
<div class="m3-dialog-backdrop" id="work-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="work-detail-title">
  <div class="m3-dialog" style="max-width: 640px; width: 100%;">
    <div style="width: 100%; height: 220px; border-radius: 18px; overflow: hidden; background-size: cover; background-position: center; position: relative;" id="work-detail-cover-img">
      <div style="position: absolute; bottom: 12px; left: 12px; display: flex; gap: 8px;">
        <span class="preview-selector-chip" id="work-detail-category-tag" style="background: rgba(0,0,0,0.7); color: #fff; border: none;">Category</span>
        <span class="preview-selector-chip" id="work-detail-year-tag" style="background: rgba(0,0,0,0.7); color: #fff; border: none;">Year</span>
      </div>
    </div>
    <h2 class="m3-dialog-title" id="work-detail-title" style="margin-top: 14px;">作品名</h2>
    <div class="m3-dialog-body" id="work-detail-description" style="white-space: pre-line; line-height: 1.8; margin-top: 8px;">説明</div>
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 14px;" id="work-detail-tags-container"></div>
    <div class="m3-dialog-actions" style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center;">
      <a href="#" class="m3-btn m3-btn-filled" id="work-detail-permalink" style="text-decoration: none;">
        <span class="material-symbols-rounded" aria-hidden="true">open_in_new</span>
        <span>個別記事を読む</span>
      </a>
      <button type="button" class="m3-btn m3-btn-tonal" id="work-detail-close-btn">
        <span><?php esc_html_e('閉じる', 'wawahz'); ?></span>
      </button>
    </div>
  </div>
</div>

<!-- 2.5 About Info ダイアログ -->
<div class="m3-dialog-backdrop" id="info-dialog" role="dialog" aria-modal="true" aria-labelledby="info-dialog-title">
  <div class="m3-dialog" style="max-width: 520px;">
    <h2 class="m3-dialog-title" id="info-dialog-title">About <?php echo esc_html(get_bloginfo('name')); ?></h2>
    <div class="m3-dialog-body">
      <p style="margin-bottom: 12px; line-height: 1.6;"><strong>wawa.hz（ワワ・ヘルツ）</strong> は、Web技術・ハードウェア設計・アンビエント音響の記録を綴るパーソナルメディアです。</p>
      <p style="font-size: 13px; color: var(--md-sys-color-on-surface-variant); line-height: 1.6;">Google Material 3 Expressive ガイドラインに完全準拠し、YouTube公式規約に則ったクリーンな音楽体験と快適な閲覧体験を提供します。</p>
    </div>
    <div class="m3-dialog-actions">
      <button type="button" class="m3-btn m3-btn-tonal dialog-close-btn"><?php esc_html_e('閉じる', 'wawahz'); ?></button>
    </div>
  </div>
</div>

<!-- 3. SiteMap ダイアログ -->
<div class="m3-dialog-backdrop" id="sitemap-dialog" role="dialog" aria-modal="true" aria-labelledby="sitemap-dialog-title">
  <div class="m3-dialog" style="max-width: 520px;">
    <h2 class="m3-dialog-title" id="sitemap-dialog-title">SiteMap</h2>
    <div class="m3-dialog-body">
      <ul style="padding-left: 20px; display: flex; flex-direction: column; gap: 10px;">
        <li><a href="<?php echo esc_url(home_url('/')); ?>" style="color: var(--md-sys-color-primary); font-weight: 500;">ホーム (Home &amp; Pickup)</a></li>
        <li><a href="<?php echo esc_url(wawahz_view_url('gallery')); ?>" style="color: var(--md-sys-color-primary); font-weight: 500;">Works Gallery (作品記事画面)</a></li>
        <li><a href="<?php echo esc_url(wawahz_view_url('category')); ?>" style="color: var(--md-sys-color-primary); font-weight: 500;">Category Search (カテゴリー一覧)</a></li>
        <li><a href="<?php echo esc_url(wawahz_view_url('nowplaying')); ?>" style="color: var(--md-sys-color-primary); font-weight: 500;">Now Playing Gallery (Ambient Tracks &amp; YouTube)</a></li>
        <li><a href="<?php echo esc_url(wawahz_view_url('info')); ?>" style="color: var(--md-sys-color-primary); font-weight: 500;">Site Information (サイト概要と仕様)</a></li>
      </ul>
    </div>
    <div class="m3-dialog-actions">
      <button type="button" class="m3-btn m3-btn-tonal dialog-close-btn"><?php esc_html_e('閉じる', 'wawahz'); ?></button>
    </div>
  </div>
</div>

<!-- 4. Contact Form ダイアログ -->
<div class="m3-dialog-backdrop" id="form-dialog" role="dialog" aria-modal="true" aria-labelledby="form-dialog-title">
  <div class="m3-dialog" style="max-width: 480px;">
    <h2 class="m3-dialog-title" id="form-dialog-title">Contact Form</h2>
    <form id="contact-form" style="display: flex; flex-direction: column; gap: 14px;" onsubmit="event.preventDefault(); alert('メッセージが送信されました（デモ）'); this.closest('.m3-dialog-backdrop').classList.remove('active');">
      <div class="m3-text-field">
        <label for="contact-name"><?php esc_html_e('お名前', 'wawahz'); ?></label>
        <input type="text" id="contact-name" name="name" autocomplete="name" required placeholder="山田 太郎">
      </div>
      <div class="m3-text-field">
        <label for="contact-email"><?php esc_html_e('メールアドレス', 'wawahz'); ?></label>
        <input type="email" id="contact-email" name="email" autocomplete="email" inputmode="email" required placeholder="example@wawa.hz">
      </div>
      <div class="m3-text-field">
        <label for="contact-msg"><?php esc_html_e('メッセージ', 'wawahz'); ?></label>
        <textarea id="contact-msg" name="message" autocomplete="off" rows="4" required placeholder="メッセージをご記入ください…"></textarea>
      </div>
      <div class="m3-dialog-actions">
        <button type="button" class="m3-btn m3-btn-outlined dialog-close-btn"><?php esc_html_e('キャンセル', 'wawahz'); ?></button>
        <button type="submit" class="m3-btn m3-btn-filled"><?php esc_html_e('送信する', 'wawahz'); ?></button>
      </div>
    </form>
  </div>
</div>

<!-- 5. Privacy Policy ダイアログ (本文は template-parts/privacy-policy.php で管理) -->
<div class="m3-dialog-backdrop" id="policy-dialog" role="dialog" aria-modal="true" aria-labelledby="policy-dialog-title">
  <div class="m3-dialog" style="max-width: 600px;">
    <h2 class="m3-dialog-title" id="policy-dialog-title"><?php esc_html_e('プライバシーポリシー', 'wawahz'); ?></h2>
    <div class="m3-dialog-body" style="font-size: 13px; line-height: 1.7;">
      <?php get_template_part('template-parts/privacy-policy'); ?>
    </div>
    <div class="m3-dialog-actions">
      <button type="button" class="m3-btn m3-btn-tonal dialog-close-btn"><?php esc_html_e('了承する', 'wawahz'); ?></button>
    </div>
  </div>
</div>

<!-- 6. その他オプション (more_vert) ダイアログ -->
<div class="m3-dialog-backdrop" id="more-menu-dialog" role="dialog" aria-modal="true" aria-labelledby="more-menu-title">
  <div class="m3-dialog" style="max-width: 320px;">
    <h3 class="title-medium" id="more-menu-title" style="margin-bottom: 12px; font-weight: 700;">Menu Options</h3>
    <div style="display: flex; flex-direction: column; gap: 8px;">
      <button type="button" class="m3-btn m3-btn-tonal theme-toggle" id="menu-opt-theme-toggle" style="justify-content: flex-start; width: 100%;">
        <span class="material-symbols-rounded" aria-hidden="true">dark_mode</span>
        <span><?php esc_html_e('テーマ切り替え', 'wawahz'); ?></span>
      </button>
      <a href="<?php echo esc_url(wawahz_view_url('info')); ?>" class="m3-btn m3-btn-tonal" style="justify-content: flex-start; text-decoration: none; width: 100%;">
        <span class="material-symbols-rounded" aria-hidden="true">info</span>
        <span>About <?php echo esc_html(get_bloginfo('name')); ?></span>
      </a>
      <a href="<?php echo esc_url(wawahz_view_url('profile')); ?>" class="m3-btn m3-btn-tonal" style="justify-content: flex-start; text-decoration: none; width: 100%;">
        <span class="material-symbols-rounded" aria-hidden="true">account_circle</span>
        <span><?php esc_html_e('プロフィール', 'wawahz'); ?></span>
      </a>
      <?php if (current_user_can('edit_posts')) : ?>
        <a href="<?php echo esc_url(admin_url()); ?>" class="m3-btn m3-btn-tonal" style="justify-content: flex-start; text-decoration: none; width: 100%;">
          <span class="material-symbols-rounded" aria-hidden="true">admin_panel_settings</span>
          <span><?php esc_html_e('管理ダッシュボード', 'wawahz'); ?></span>
        </a>
      <?php endif; ?>
    </div>
    <div class="m3-dialog-actions" style="margin-top: 16px;">
      <button type="button" class="m3-btn m3-btn-outlined dialog-close-btn" style="width: 100%;"><?php esc_html_e('閉じる', 'wawahz'); ?></button>
    </div>
  </div>
</div>

<!-- スナックバー通知 -->
<div id="snackbar" role="alert" aria-live="polite">メッセージ</div>

<?php wp_footer(); ?>
</body>
</html>
