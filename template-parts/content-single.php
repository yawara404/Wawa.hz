<?php
$is_article = get_post_type() === 'post';
$post_id = get_the_ID();
$yt_url = get_post_meta($post_id, 'wawahz_youtube_url', true);
$artist = get_post_meta($post_id, 'wawahz_artist', true);
$album = get_post_meta($post_id, 'wawahz_album', true);
$mood = get_post_meta($post_id, 'wawahz_mood', true);
$media = wawahz_post_media(get_post());
$yt_id = '';
if ($yt_url) {
  $yt_id = wawahz_youtube_id($yt_url);
} elseif ($media && $media['type'] === 'youtube') {
  $yt_id = $media['youtube_id'];
  $yt_url = 'https://www.youtube.com/watch?v=' . $yt_id;
}
// カバー (16:10 ヒーロー): YouTube サムネイル → アイキャッチの順に解決
$cover = wawahz_cover_image($post_id);
// YouTube のサムネイルをカバーに使うときだけ、プレイヤーと重複するので隠す。
// アイキャッチ画像がある通常記事は、YouTube 埋め込みがあってもカバーを表示する。
$show_cover = $cover && !post_password_required() && !($cover['youtube'] && $yt_id);
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('reading-card'); ?>>
  <?php if ($show_cover) : ?>
    <?php /* 16:10 のヒーロー。YouTube のサムネイルは配信画像をそのまま使用 */ ?>
    <div class="entry-cover<?php echo $cover['youtube'] ? ' entry-cover--youtube' : ''; ?>" style="--cover-bg: url('<?php echo esc_url($cover['url']); ?>');">
      <img src="<?php echo esc_url($cover['url']); ?>" width="<?php echo (int) $cover['width']; ?>" height="<?php echo (int) $cover['height']; ?>" alt="" loading="eager" decoding="async">
    </div>
  <?php endif; ?>
  <header class="entry-header">
    <?php if ($is_article) : ?>
      <?php /* アイブロウ: カテゴリをアクセントラベルで表示 */ ?>
      <?php $wawahz_categories = get_the_category(); ?>
      <?php if ($wawahz_categories) : ?>
        <div class="entry-eyebrow">
          <span class="eyebrow-bar" aria-hidden="true"></span>
          <?php foreach (array_slice($wawahz_categories, 0, 3) as $wawahz_category) : ?>
            <a class="eyebrow-chip" href="<?php echo esc_url(get_category_link($wawahz_category)); ?>"><?php echo esc_html($wawahz_category->name); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <h1 class="entry-title"><?php echo esc_html(wawahz_post_title()); ?></h1>

    <?php if ($is_article) : ?>
      <?php /* バイライン: 著者 · 日付 */ ?>
      <div class="entry-byline">
        <span class="byline-author"><?php echo esc_html(get_the_author()); ?></span>
        <span class="byline-sep" aria-hidden="true">·</span>
        <time class="byline-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y.m.d')); ?></time>
      </div>
    <?php endif; ?>

    <?php if ($yt_id) : ?>
      <!-- 音楽レビュー (Nowplaying) 専用プレイヤー & YouTube連携 -->
      <div class="detail-music-player-box" id="detail-music-box" style="margin: 16px 0 20px;">
        <div class="detail-music-header">
          <div class="detail-music-meta">
            <span class="detail-music-track-title"><?php echo esc_html(get_the_title()); ?></span>
            <span class="detail-music-track-artist"><?php echo esc_html($artist ?: get_the_author()); ?></span>
          </div>
          <span class="material-symbols-rounded" aria-hidden="true" style="color: var(--md-sys-color-primary); font-size: 24px;">headphones</span>
        </div>
        <div class="detail-music-btn-row">
          <button class="btn-music-play" type="button"
                  data-open-player
                  data-youtube-id="<?php echo esc_attr($yt_id); ?>"
                  data-title="<?php echo esc_attr(get_the_title()); ?>"
                  data-artist="<?php echo esc_attr($artist ?: get_the_author()); ?>"
                  data-album="<?php echo esc_attr($album); ?>"
                  data-mood="<?php echo esc_attr($mood); ?>"
                  data-year="<?php echo esc_attr(get_the_date('Y')); ?>"
                  data-commentary="<?php echo esc_attr(wp_strip_all_tags(get_the_excerpt())); ?>"
                  aria-label="<?php esc_attr_e('プレイヤーモーダルで再生', 'wawahz'); ?>">
            <span class="material-symbols-rounded" aria-hidden="true">play_arrow</span>
            <span><?php esc_html_e('プレビュー再生', 'wawahz'); ?></span>
          </button>
          <?php if ($yt_url) : ?>
            <a href="<?php echo esc_url($yt_url); ?>" target="_blank" rel="noopener noreferrer" class="btn-youtube-link">
              <span class="material-symbols-rounded" aria-hidden="true" style="font-size: 18px;">smart_display</span>
              <span>YouTubeで聴く</span>
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </header>

  <div class="entry-content">
    <?php the_content(); ?>
    <?php wp_link_pages(array('before' => '<nav class="page-links" aria-label="' . esc_attr__('記事内のページ', 'wawahz') . '">', 'after' => '</nav>')); ?>
  </div>

  <?php if ($is_article && has_tag()) : ?>
    <footer class="entry-tags"><?php the_tags('', ' '); ?></footer>
  <?php endif; ?>
  <?php edit_post_link(__('この記事を編集', 'wawahz'), '<p class="edit-link">', '</p>'); ?>
</article>

<?php if ($is_article) :
  // 前後の記事も Now Playing (music) と通常記事で分ける。
  $wawahz_nav_music_id = function_exists('wawahz_music_category_id') ? wawahz_music_category_id() : 0;
  $wawahz_nav_args = array(
    'prev_text' => '<span class="nav-caption">' . esc_html__('前の記事', 'wawahz') . '</span><span>%title</span>',
    'next_text' => '<span class="nav-caption">' . esc_html__('次の記事', 'wawahz') . '</span><span>%title</span>',
    'taxonomy'  => 'category',
  );
  if ($wawahz_nav_music_id && in_category($wawahz_nav_music_id)) {
    // 楽曲記事: 同じ music カテゴリ内で前後をたどる
    $wawahz_nav_args['in_same_term'] = true;
  } elseif ($wawahz_nav_music_id) {
    // 通常記事: music カテゴリを除外して前後をたどる
    $wawahz_nav_args['excluded_terms'] = array($wawahz_nav_music_id);
  }
  the_post_navigation($wawahz_nav_args);
endif; ?>
<?php if (comments_open() || get_comments_number()) : comments_template(); endif; ?>

