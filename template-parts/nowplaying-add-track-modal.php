<?php
/**
 * template-parts/nowplaying-add-track-modal.php
 * Now Playing 専用の楽曲追加モーダル。表示は誰にでも行い、
 * 実際の追加は admin-ajax.php (wp_ajax_wawahz_add_track) の権限チェックで制御する。
 */
// ブロックやショートコードで複数回描画されても ID が重複しないようにする。
if (!empty($GLOBALS['wawahz_add_track_modal_rendered'])) {
  return;
}
$GLOBALS['wawahz_add_track_modal_rendered'] = true;

$statuses = wawahz_add_track_statuses();
?>
<div class="m3-dialog-backdrop" id="dialog-nowplaying-add-track" role="dialog" aria-modal="true" aria-labelledby="nowplaying-add-track-dialog-title">
  <div class="m3-dialog nowplaying-add-track-dialog">
    <h2 class="m3-dialog-title" id="nowplaying-add-track-dialog-title"><?php esc_html_e('Now Playing に曲を追加', 'wawahz'); ?></h2>
    <p class="m3-dialog-body nowplaying-add-track-intro">
      <?php esc_html_e('YouTube のURLを登録すると、ギャラリーの先頭にカードが追加されます。カードをタップすると公式プレイヤーで再生できます。アーティスト名・アルバム・ムード・紹介文は任意です。', 'wawahz'); ?>
    </p>

    <?php if (!is_user_logged_in()) : ?>
      <p class="nowplaying-add-track-login-note" role="note">
        <?php
        printf(
          wp_kses(
            /* translators: %s: ログイン画面のURL */
            __('楽曲を追加するには<a href="%s">管理者・投稿者としてログイン</a>してください。', 'wawahz'),
            array('a' => array('href' => array()))
          ),
          esc_url(wp_login_url(wawahz_view_url('nowplaying')))
        );
        ?>
      </p>
    <?php endif; ?>

    <form id="nowplaying-add-track-form" class="nowplaying-add-track-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
      <input type="hidden" name="action" value="wawahz_add_track">
      <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('wawahz_add_track')); ?>">

      <div class="m3-text-field">
        <label for="nowplaying-add-track-title"><?php esc_html_e('曲名（必須）', 'wawahz'); ?></label>
        <input type="text" id="nowplaying-add-track-title" name="title" maxlength="120" required autocomplete="off" placeholder="<?php esc_attr_e('例: Rain & Resonator - 金属板と雨音の共鳴', 'wawahz'); ?>">
      </div>

      <div class="m3-text-field">
        <label for="nowplaying-add-track-youtube"><?php esc_html_e('YouTube URL または 動画ID（必須）', 'wawahz'); ?></label>
        <input type="text" id="nowplaying-add-track-youtube" name="youtube_url" required autocomplete="off" spellcheck="false" placeholder="https://www.youtube.com/watch?v=xxxxxxxxxxx">
      </div>

      <div class="nowplaying-add-track-preview" id="nowplaying-add-track-preview" hidden>
        <img id="nowplaying-add-track-preview-img" src="" alt="" width="480" height="270" loading="lazy" referrerpolicy="no-referrer">
      </div>

      <div class="nowplaying-add-track-grid">
        <div class="m3-text-field">
          <label for="nowplaying-add-track-artist"><?php esc_html_e('アーティスト名', 'wawahz'); ?></label>
          <input type="text" id="nowplaying-add-track-artist" name="artist" maxlength="80" autocomplete="off" placeholder="<?php esc_attr_e('例: wawa.hz sound lab', 'wawahz'); ?>">
        </div>
        <div class="m3-text-field">
          <label for="nowplaying-add-track-album"><?php esc_html_e('アルバム', 'wawahz'); ?></label>
          <input type="text" id="nowplaying-add-track-album" name="album" maxlength="80" autocomplete="off" placeholder="<?php esc_attr_e('例: Ambient Session #01', 'wawahz'); ?>">
        </div>
        <div class="m3-text-field">
          <label for="nowplaying-add-track-mood"><?php esc_html_e('ムード / タグ', 'wawahz'); ?></label>
          <input type="text" id="nowplaying-add-track-mood" name="mood" maxlength="60" autocomplete="off" placeholder="<?php esc_attr_e('例: Ambient & Chill', 'wawahz'); ?>">
        </div>
      </div>

      <div class="m3-text-field">
        <label for="nowplaying-add-track-commentary"><?php esc_html_e('紹介文（カードに表示）', 'wawahz'); ?></label>
        <textarea id="nowplaying-add-track-commentary" name="commentary" rows="3" maxlength="400" placeholder="<?php esc_attr_e('例: 雨樋に当たる雫のリズムをグラニュラーシンセシスで増幅。', 'wawahz'); ?>"></textarea>
      </div>

      <div class="nowplaying-add-track-grid">
        <div class="m3-text-field">
          <label for="nowplaying-add-track-status"><?php esc_html_e('公開状態', 'wawahz'); ?></label>
          <select id="nowplaying-add-track-status" name="status">
            <?php foreach ($statuses as $value => $label) : ?>
              <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <p class="nowplaying-add-track-status" id="nowplaying-add-track-progress" role="status" aria-live="polite" hidden></p>

      <div class="m3-dialog-actions">
        <button type="button" class="m3-btn m3-btn-outlined dialog-close-btn"><?php esc_html_e('キャンセル', 'wawahz'); ?></button>
        <button type="submit" class="m3-btn m3-btn-filled" id="nowplaying-add-track-submit">
          <span class="material-symbols-rounded" aria-hidden="true">library_add</span>
          <span><?php esc_html_e('追加する', 'wawahz'); ?></span>
        </button>
      </div>
    </form>
  </div>
</div>