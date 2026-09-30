<?php
/**
 * search.php
 * 検索結果画面テンプレート
 *
 * カテゴリー画面 (template-parts/filter-list.php) と同じ「モバイル / デスクトップ」の
 * 2レイアウト構成。中身は template-parts/search-screen-body.php を variant 違いで
 * 2回読み込んで出力する。
 *   mobile  … 380×320 のボックス (.box-surface-high-380-320)
 *   desktop … 616×320 のボックス (.box-surface-high-616-320)
 *
 * 検索画面は3画面の横スライド（画面1 ホーム / 画面2 カテゴリー / 画面3 情報欄）には
 * 含めないため、画面右中央の「次の画面」ボタンは表示されない。
 */
get_header();
?>
<main class="site-main wawa-container" id="main-content" tabindex="-1">
  <section class="screen-view active search-screen-view" id="screen-search" aria-label="<?php esc_attr_e('記事検索', 'wawahz'); ?>">

    <!-- ==============================================
         1. モバイル版レイアウト (412 / 380×320 ボックス)
         ============================================== -->
    <div class="mobile-only" style="display: flex; flex-direction: column; width: 100%; align-items: center; justify-content: center;">
      <div class="search-screen-container">
        <?php get_template_part('template-parts/search-screen-body', null, array('variant' => 'mobile')); ?>
      </div>
    </div>

    <!-- ==============================================
         2. デスクトップ版レイアウト (1280 / 616×320 ボックス)
         dcat-desktop-container はカテゴリー画面のデスクトップ枠と
         同じ挙動（900px以上 / force-desktop で表示）のコンテナ。
         ============================================== -->
    <div class="desktop-only dcat-desktop-container">
      <div class="search-screen-container">
        <?php get_template_part('template-parts/search-screen-body', null, array('variant' => 'desktop')); ?>
      </div>
    </div>

  </section>
</main>
<?php get_footer(); ?>
