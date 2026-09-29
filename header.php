<!doctype html>
<html <?php language_attributes(); ?> data-theme="light">
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <script>document.documentElement.classList.add('js');try{var t=localStorage.getItem('wawahz_theme');if(t==='light'||t==='dark')document.documentElement.dataset.theme=t;}catch(e){}try{var d='';try{d=sessionStorage.getItem('wawahz_slide_dir')||'';}catch(e){}var n=performance.getEntriesByType?performance.getEntriesByType('navigation')[0]:null;if(n&&n.type==='back_forward')d='back';if(d)document.documentElement.dataset.slideDir=d;}catch(e){}</script>
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e('本文へスキップ', 'wawahz'); ?></a>
<div id="app-viewport">
  <div id="app-container">
    <!-- デスクトップ用 Navigation Rail -->
    <nav class="m3-navigation-rail desktop-only" id="desktop-nav-rail" aria-label="<?php esc_attr_e('メインナビゲーション', 'wawahz'); ?>">
      <button class="icon-btn-48 rail-menu-btn drawer-trigger" id="rail-menu-toggle-btn" type="button" data-open-drawer aria-controls="slide-drawer" aria-expanded="false" title="<?php esc_attr_e('メニュー開閉', 'wawahz'); ?>" aria-label="<?php esc_attr_e('メニューを開く', 'wawahz'); ?>">
        <span class="material-symbols-rounded" aria-hidden="true">menu</span>
      </button>
      <div class="rail-destinations">
        <?php foreach (wawahz_navigation() as $key => $item) : $active = wawahz_current_section() === $key; ?>
          <a class="rail-item <?php echo $active ? 'active' : ''; ?>" id="rail-dest-<?php echo esc_attr($key); ?>" href="<?php echo esc_url($item['url']); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?> aria-label="<?php echo esc_attr($item['label']); ?>">
            <div class="rail-indicator">
              <span class="material-symbols-rounded <?php echo $active ? 'icon-filled' : ''; ?>" aria-hidden="true"><?php echo esc_html($item['icon']); ?></span>
            </div>
            <span class="rail-label"><?php echo esc_html($item['label']); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <div style="margin-top: auto; display: flex; flex-direction: column; gap: 8px; align-items: center; width: 100%;">
        <button type="button" class="icon-btn-48 rail-theme-toggle theme-toggle" id="rail-theme-toggle-btn" title="<?php esc_attr_e('テーマ切り替え', 'wawahz'); ?>" aria-label="<?php esc_attr_e('配色を切り替える', 'wawahz'); ?>" aria-pressed="false">
          <span class="material-symbols-rounded" id="rail-theme-icon" aria-hidden="true">dark_mode</span>
        </button>
      </div>
    </nav>

    <div class="main-content-wrapper">
      <!-- トップアプリバー -->
      <header class="top-app-bar" id="main-top-app-bar">
        <div style="display: flex; align-items: center; gap: 8px;">
          <button type="button" class="icon-btn-48 mobile-only drawer-trigger" id="mobile-menu-btn" data-open-drawer aria-controls="slide-drawer" aria-expanded="false" title="<?php esc_attr_e('メニュー', 'wawahz'); ?>" aria-label="<?php esc_attr_e('メニューを開く', 'wawahz'); ?>">
            <span class="material-symbols-rounded" aria-hidden="true">menu</span>
          </button>
          <a href="<?php echo esc_url(home_url('/')); ?>" class="brand-home-btn" id="brand-home-btn" title="<?php esc_attr_e('ホーム画面に戻る', 'wawahz'); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> <?php esc_attr_e('ホーム画面に戻る', 'wawahz'); ?>">
            <span class="material-symbols-rounded brand-logo-icon" aria-hidden="true">graphic_eq</span>
            <h1 class="app-title" id="app-title-logo"><?php echo esc_html(get_bloginfo('name')); ?></h1>
          </a>
        </div>
        <div class="app-bar-actions">
          <!-- テーマ切り替え (Light/Dark): モバイル専用。
               デスクトップはナビゲーションレール下部の #rail-theme-toggle-btn に集約し、
               トップバー側と二重に表示されないようにする (重複防止)。 -->
          <button type="button" class="icon-btn-48 mobile-only theme-toggle" id="top-theme-toggle-btn" title="<?php esc_attr_e('テーマ切り替え', 'wawahz'); ?>" aria-label="<?php esc_attr_e('配色を切り替える', 'wawahz'); ?>" aria-pressed="false">
            <span class="material-symbols-rounded" id="top-theme-icon" aria-hidden="true">dark_mode</span>
          </button>
          <!-- 右端 more_vert アイコンボタン -->
          <button type="button" class="icon-btn-48" id="top-more-vert-btn" title="<?php esc_attr_e('その他オプション', 'wawahz'); ?>" aria-label="<?php esc_attr_e('その他オプション', 'wawahz'); ?>" data-open-dialog="more-menu-dialog">
            <span class="material-symbols-rounded" aria-hidden="true">more_vert</span>
          </button>
        </div>
      </header>
      <?php if (has_nav_menu('primary')) : ?>
        <nav class="site-menu" aria-label="<?php esc_attr_e('サイトメニュー', 'wawahz'); ?>"><?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'fallback_cb' => false, 'depth' => 2)); ?></nav>
      <?php endif; ?>
