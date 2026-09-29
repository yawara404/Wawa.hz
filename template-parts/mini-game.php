<?php
/**
 * template-parts/mini-game.php
 *
 * Works ギャラリー最下部のちいさなミニゲーム枠。
 * ゲーム本体 (games/falling-survivor/index.html: p5play 製 "Falling Survivor") は
 * 「ゲームを起動」を押したときだけ iframe として読み込むため、
 * 通常のページ表示では追加のライブラリ (p5 / planck / p5play) をダウンロードしない。
 */
$wawahz_game_dir = get_template_directory() . '/games/falling-survivor';
if (!file_exists($wawahz_game_dir . '/index.html')) {
  return; // ゲーム一式が無い環境では何も表示しない
}
$wawahz_game_uri = get_template_directory_uri() . '/games/falling-survivor';
$wawahz_game_version = 0;
foreach (array('index.html', 'sketch.js', 'embed.js', 'touch.js') as $wawahz_game_part) {
  if (file_exists($wawahz_game_dir . '/' . $wawahz_game_part)) {
    $wawahz_game_version = max($wawahz_game_version, (int) filemtime($wawahz_game_dir . '/' . $wawahz_game_part));
  }
}
// キャッシュ更新用のバージョン付きURL (ゲームを差し替えると自動で更新される)
$wawahz_game_url = add_query_arg('v', (string) $wawahz_game_version, $wawahz_game_uri . '/index.html');
?>
<section class="mini-game-section" id="mini-game" data-mini-game aria-labelledby="mini-game-title">
  <div class="mini-game-head">
    <span class="mini-game-head-icon material-symbols-rounded" aria-hidden="true">sports_esports</span>
    <div class="mini-game-head-text">
      <h2 class="mini-game-title" id="mini-game-title"><?php esc_html_e('Mini Game', 'wawahz'); ?></h2>
      <p class="mini-game-sub"><?php esc_html_e('制作の合間にどうぞ。授業の最終課題でつくったちいさな1作です。', 'wawahz'); ?></p>
    </div>
    <span class="mini-game-badge"><?php esc_html_e('アルゴリズム 最終課題', 'wawahz'); ?></span>
  </div>

  <div class="mini-game-body">
    <div class="mini-game-main">
      <div class="mini-game-stage" data-mini-game-stage>
        <div class="mini-game-placeholder" data-mini-game-placeholder>
          <div class="mini-game-sprites" aria-hidden="true">
            <img class="mini-game-sprite mini-game-sprite--player" src="<?php echo esc_url($wawahz_game_uri . '/images/character_monster_slime_green.png'); ?>" alt="" width="45" height="35" loading="lazy" decoding="async">
            <img class="mini-game-sprite mini-game-sprite--enemy" src="<?php echo esc_url($wawahz_game_uri . '/images/yurei_01.png'); ?>" alt="" width="50" height="50" loading="lazy" decoding="async">
          </div>
          <p class="mini-game-lead"><?php esc_html_e('落ちてくる敵をよけながら20秒生きのびる横スクロール。生きのびた時間がそのままランクになります。', 'wawahz'); ?></p>
          <button type="button" class="mini-game-start-btn" data-mini-game-start
                  data-game-src="<?php echo esc_url($wawahz_game_url); ?>"
                  data-game-title="Falling Survivor">
            <span class="material-symbols-rounded" aria-hidden="true">play_arrow</span>
            <span><?php esc_html_e('ゲームを起動', 'wawahz'); ?></span>
          </button>
          <p class="mini-game-hint"><?php esc_html_e('初回の起動は p5play の読み込みに数秒かかります。', 'wawahz'); ?></p>
        </div>
        <div class="mini-game-frame" data-mini-game-frame hidden></div>
      </div>

      <?php /* モバイル表示で JS が表示する両手用パッド (キー操作を iframe へ転送) */ ?>
      <div class="mini-game-pad" data-mini-game-pad hidden>
        <div class="mini-game-pad-top">
          <span class="mini-game-pad-label"><?php esc_html_e('タッチ操作', 'wawahz'); ?></span>
          <button type="button" class="mini-game-pad-btn mini-game-pad-btn--start" data-game-key=" " aria-label="<?php esc_attr_e('スタート / リトライ', 'wawahz'); ?>">START</button>
        </div>
        <div class="mini-game-pad-controls">
          <div class="mini-game-pad-move" role="group" aria-label="<?php esc_attr_e('移動', 'wawahz'); ?>">
            <button type="button" class="mini-game-pad-btn" data-game-key="ArrowLeft" aria-label="<?php esc_attr_e('左へ移動', 'wawahz'); ?>"><span class="material-symbols-rounded" aria-hidden="true">arrow_back</span></button>
            <button type="button" class="mini-game-pad-btn" data-game-key="ArrowRight" aria-label="<?php esc_attr_e('右へ移動', 'wawahz'); ?>"><span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span></button>
          </div>
          <div class="mini-game-pad-actions" role="group" aria-label="<?php esc_attr_e('ジャンプと急降下', 'wawahz'); ?>">
            <button type="button" class="mini-game-pad-btn mini-game-pad-btn--drop" data-game-key="ArrowDown" aria-label="<?php esc_attr_e('下へ降りる', 'wawahz'); ?>"><span class="material-symbols-rounded" aria-hidden="true">arrow_downward</span></button>
            <button type="button" class="mini-game-pad-btn mini-game-pad-btn--jump" data-game-key="ArrowUp" aria-label="<?php esc_attr_e('ジャンプ', 'wawahz'); ?>"><span class="material-symbols-rounded" aria-hidden="true">arrow_upward</span></button>
          </div>
        </div>
      </div>
    </div>

    <div class="mini-game-aside">
      <h3 class="mini-game-aside-title"><?php esc_html_e('あそびかた', 'wawahz'); ?></h3>
      <ul class="mini-game-rules">
        <li><span class="mini-game-keys"><kbd>&#8592;</kbd><kbd>&#8594;</kbd></span><?php esc_html_e('左右に移動', 'wawahz'); ?></li>
        <li><span class="mini-game-keys"><kbd>&#8593;</kbd></span><?php esc_html_e('ジャンプ (地面か敵の上で)', 'wawahz'); ?></li>
        <li><span class="mini-game-keys"><kbd>&#8595;</kbd></span><?php esc_html_e('空中で急降下', 'wawahz'); ?></li>
        <li><span class="mini-game-keys"><kbd>Space</kbd></span><?php esc_html_e('スタート / タイトルへ戻る', 'wawahz'); ?></li>
      </ul>
      <p class="mini-game-aside-note"><?php esc_html_e('20秒生きのびればクリア。20秒をこえると敵が速くなります。スマホでは左手で移動、右手でジャンプと急降下を操作できます。', 'wawahz'); ?></p>

      <div class="mini-game-ranks" aria-label="<?php esc_attr_e('ランク一覧', 'wawahz'); ?>">
        <span class="mini-game-rank"><b>B</b><small>20秒未満</small></span>
        <span class="mini-game-rank"><b>A</b><small>20秒</small></span>
        <span class="mini-game-rank"><b>S</b><small>30秒</small></span>
        <span class="mini-game-rank"><b>SS</b><small>50秒</small></span>
        <span class="mini-game-rank"><b>SSS</b><small>100秒</small></span>
        <span class="mini-game-rank"><b>LR</b><small>200秒</small></span>
      </div>

      <div class="mini-game-actions">
        <button type="button" class="m3-btn m3-btn-tonal mini-game-reset-btn" data-mini-game-reset hidden>
          <span class="material-symbols-rounded" aria-hidden="true">restart_alt</span>
          <span><?php esc_html_e('タイトルへ戻す', 'wawahz'); ?></span>
        </button>
        <a class="m3-btn m3-btn-tonal" href="<?php echo esc_url($wawahz_game_url); ?>" target="_blank" rel="noopener noreferrer">
          <span class="material-symbols-rounded" aria-hidden="true">open_in_new</span>
          <span><?php esc_html_e('別タブで遊ぶ', 'wawahz'); ?></span>
        </a>
      </div>
      <noscript>
        <p class="mini-game-aside-note"><a href="<?php echo esc_url($wawahz_game_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('ゲームを別タブで開く', 'wawahz'); ?></a></p>
      </noscript>
    </div>
  </div>
</section>
