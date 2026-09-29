<?php
/**
 * template-parts/nowplaying-card.php
 * Now Playing カード1件分。ギャラリーのPHP描画と追加モーダルの AJAX 応答で共用する。
 *
 * @param array $args {
 *   @type WP_Post $post  対象の投稿。
 *   @type array   $media wawahz_post_media() の戻り値。
 * }
 */
$track_post = isset($args['post']) ? $args['post'] : null;
$media = isset($args['media']) ? $args['media'] : ($track_post ? wawahz_post_media($track_post) : null);
if (!$track_post || !$media) {
  return;
}

$post_id = $track_post->ID;
$title = get_the_title($track_post);
$artist = get_post_meta($post_id, 'wawahz_artist', true) ?: get_the_author_meta('display_name', $track_post->post_author);
$album = get_post_meta($post_id, 'wawahz_album', true) ?: get_the_date('Y.m.d', $track_post);
$categories = get_the_category($post_id);
$mood = get_post_meta($post_id, 'wawahz_mood', true) ?: ($categories[0]->name ?? 'Ambient');
$year = get_the_date('Y', $track_post);
$yt_id = ($media['type'] === 'youtube') ? $media['youtube_id'] : '';
$yt_url = $yt_id ? 'https://www.youtube.com/watch?v=' . $yt_id : '';
$thumb_url = get_the_post_thumbnail_url($post_id, 'medium_large');
if (!$thumb_url && $yt_id) {
  $thumb_url = 'https://img.youtube.com/vi/' . $yt_id . '/hqdefault.jpg';
}
if (!$thumb_url) {
  $thumb_url = get_template_directory_uri() . '/images/music_shimmer.svg?v=20250925_02';
}
$commentary = wp_strip_all_tags(get_the_excerpt($track_post)) ?: wp_trim_words(wp_strip_all_tags(get_the_content(null, false, $track_post)), 20);
// 本文に紛れ込んだ生URLを除去してカードの見た目を整える
$commentary = trim(preg_replace('#\s*https?://\S+#', '', $commentary));
$media_id = 'wawahz-gallery-media-' . $post_id;
?>
<div class="nowplaying-card"
     role="button"
     tabindex="0"
     data-open-player
     data-youtube-id="<?php echo esc_attr($yt_id); ?>"
     data-title="<?php echo esc_attr($title); ?>"
     data-artist="<?php echo esc_attr($artist); ?>"
     data-album="<?php echo esc_attr($album); ?>"
     data-mood="<?php echo esc_attr($mood); ?>"
     data-year="<?php echo esc_attr($year); ?>"
     data-commentary="<?php echo esc_attr($commentary); ?>"
     data-yt-url="<?php echo esc_url($yt_url); ?>"
     data-post-url="<?php echo esc_url(get_permalink($post_id)); ?>"
     data-media-template-id="<?php echo esc_attr($media_id); ?>"
     aria-label="<?php echo esc_attr($title . ' - ' . $artist . ' を再生'); ?>"
     style="cursor: pointer;">
  <?php /* YouTube 以外のメディアは投稿本文の標準プレイヤーをモーダルで再生する */ ?>
  <?php if ($media['type'] !== 'youtube') : ?>
    <template id="<?php echo esc_attr($media_id); ?>" class="nowplaying-inline-media"><?php echo wawahz_render_gallery_media($media); ?></template>
  <?php endif; ?>
  <!-- メディアボックス -->
  <div class="nowplaying-media-box" style="background-image: linear-gradient(rgba(0,0,0,0.08), rgba(0,0,0,0.62)), url('<?php echo esc_url($thumb_url); ?>');">
    <div class="nowplaying-media-bar">
      <div class="nowplaying-media-badges">
        <span class="nowplaying-year-chip"><?php echo esc_html($year); ?></span>
        <?php if ($yt_id) : ?>
          <span class="nowplaying-yt-tag">
            <span class="material-symbols-rounded" aria-hidden="true">smart_display</span>
            YouTube
          </span>
        <?php endif; ?>
      </div>
      <div class="nowplaying-media-actions">
        <?php if ($yt_url) : ?>
          <a href="<?php echo esc_url($yt_url); ?>" target="_blank" rel="noopener noreferrer" class="nowplaying-yt-link-btn" title="<?php esc_attr_e('YouTube公式で開く', 'wawahz'); ?>" aria-label="<?php esc_attr_e('YouTube公式で開く', 'wawahz'); ?>" onclick="event.stopPropagation();">
            <span class="material-symbols-rounded" aria-hidden="true">open_in_new</span>
          </a>
        <?php endif; ?>
        <button type="button" class="m3-btn m3-btn-filled m3-btn-icon-only gallery-play-btn" title="<?php esc_attr_e('試聴', 'wawahz'); ?>" aria-label="<?php echo esc_attr($title . ' を試聴'); ?>">
          <span class="material-symbols-rounded" aria-hidden="true">play_arrow</span>
        </button>
      </div>
    </div>
  </div>

  <!-- 楽曲情報 -->
  <div class="nowplaying-card-body">
    <h4 class="nowplaying-title"><?php echo esc_html($title); ?></h4>
    <p class="nowplaying-artist"><?php echo esc_html($artist); ?></p>
    <p class="nowplaying-meta"><?php echo esc_html(implode(' • ', array_filter(array($album, $mood)))); ?></p>
    <?php if ($commentary) : ?>
      <p class="nowplaying-commentary"><?php echo esc_html($commentary); ?></p>
    <?php endif; ?>
  </div>
</div>