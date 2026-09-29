<?php
/**
 * template-parts/mini-game-screen.php
 *
 * Gallery 画面2: ミニゲーム専用の1画面 (#screen-game)。
 * ゲーム枠そのものは template-parts/mini-game.php が担当し、
 * ここでは画面の枠（Gallery 画面1 の作品一覧と対になる画面）だけを用意する。
 *
 * ゲーム一式 (games/falling-survivor/) が無い環境では案内だけを表示する。
 */
$wawahz_game_index = get_template_directory() . '/games/falling-survivor/index.html';
?>
<section class="screen-view active mini-game-screen-view" id="screen-game" aria-label="<?php esc_attr_e('ミニゲーム', 'wawahz'); ?>">
  <div class="mini-game-screen-container">
    <?php if (file_exists($wawahz_game_index)) : ?>
      <?php get_template_part('template-parts/mini-game'); ?>
    <?php else : ?>
      <div class="game-screen-empty">
        <span class="material-symbols-rounded" aria-hidden="true">sports_esports</span>
        <p class="title-medium" style="font-weight: 600; margin: 0;"><?php esc_html_e('ゲームの準備中です。', 'wawahz'); ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>
