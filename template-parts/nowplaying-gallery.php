<?php
/**
 * template-parts/nowplaying-gallery.php
 * 静的版 screen-nowplaying に完全準拠した Now Playing ギャラリー。
 */
$limit = isset($args['limit']) ? max(1, min(50, intval($args['limit']))) : 12;
$show_header = isset($args['showHeader']) ? (bool) $args['showHeader'] : (isset($args['show_header']) ? (bool) $args['show_header'] : true);
$tracks = wawahz_nowplaying_posts($limit);
$heading = !empty($args['standalone']) ? 'h1' : 'h2';
?>
<section class="screen-view active nowplaying-gallery-wrapper" id="screen-nowplaying" aria-label="<?php esc_attr_e('Now Playing ギャラリー', 'wawahz'); ?>">
  <div style="display: flex; flex-direction: column; gap: 20px; width: 100%; max-width: 960px; margin: 0 auto;">
    <?php if ($show_header) : ?>
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-rounded screen-heading-icon" style="color: var(--md-sys-color-primary); font-size: 28px;">headphones</span>
            <<?php echo $heading; ?> class="headline-medium screen-heading-text" style="font-weight: 700; color: var(--md-sys-color-primary); margin: 0;">Now Playing Gallery</<?php echo $heading; ?>>
          </div>
          <p class="body-medium" style="color: var(--md-sys-color-on-surface-variant); margin-top: 4px;">
            <?php esc_html_e('最近聴いている音楽とアンビエント和音スケッチ（タップして公式プレイヤーで再生）', 'wawahz'); ?>
          </p>
        </div>
        <?php /* 追加ボタンは誰にでも表示する (実際の追加は AJAX 側で権限チェック)。 */ ?>
        <a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="m3-btn m3-btn-filled" id="nowplaying-add-track-open" data-open-dialog="dialog-nowplaying-add-track" style="height: 48px; padding: 0 16px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
          <span class="material-symbols-rounded" aria-hidden="true">library_music</span>
          <span><?php esc_html_e('曲を追加', 'wawahz'); ?></span>
        </a>
      </div>
    <?php endif; ?>

    <p class="nowplaying-add-track-toast" id="nowplaying-add-track-toast" role="status" aria-live="polite" hidden></p>

    <?php if (!$tracks) : ?>
      <div class="nowplaying-empty" style="text-align: center; padding: 48px 16px; background: var(--md-sys-color-surface-container-low); border-radius: 24px;">
        <span class="material-symbols-rounded" style="font-size: 48px; color: var(--md-sys-color-outline); margin-bottom: 12px;">music_off</span>
        <p class="title-medium" style="font-weight: 600;"><?php esc_html_e('楽曲はまだ登録されていません。', 'wawahz'); ?></p>
        <p class="body-medium" style="color: var(--md-sys-color-on-surface-variant); margin-top: 8px;">
          <?php esc_html_e('「曲を追加」から YouTube のURLを登録すると、このギャラリーに表示されます。投稿編集画面の「🎵 Now Playing 楽曲設定（音声・動画・YouTube）」からも設定できます。', 'wawahz'); ?>
        </p>
      </div>
    <?php else : ?>
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px;" id="gallery-cards-container">
        <?php foreach ($tracks as $track) : ?>
          <?php get_template_part('template-parts/nowplaying-card', null, $track); ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php /* 専用モーダル (誰にでも描画。追加は AJAX 側で権限チェック) */ ?>
<?php get_template_part('template-parts/nowplaying-add-track-modal'); ?>
