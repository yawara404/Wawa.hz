<?php
/**
 * ホーム画面テンプレート
 * 静的版 (~/Wawa.hz/index.html) のデザインに準拠。
 * モバイル：Pickup カルーセル (5件) + Lately 3 ボックス
 * デスクトップ：左 (Pickup 2×2 + NowPlaying ボックス) + 右 (Lately 2×2 + Gallery ボックス)
 */

// === データ取得 ===
$sticky_ids  = get_option( 'sticky_posts', array() );
$pickup_args = array(
  'post_type'           => 'post',
  'post_status'         => 'publish',
  'has_password'        => false,
  'posts_per_page'      => 5,
  'ignore_sticky_posts' => true,
);
if ( $sticky_ids ) {
  $pickup_args['post__in'] = $sticky_ids;
}
$pickup_posts = get_posts( $pickup_args );
if ( ! $pickup_posts ) {
  // フォールバック：スティッキーなし → 最新5件
  $pickup_posts = get_posts( array(
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'has_password'        => false,
    'posts_per_page'      => 5,
    'ignore_sticky_posts' => true,
  ) );
}

// Lately 用：最新6件
$lately_posts = get_posts( array(
  'post_type'           => 'post',
  'post_status'         => 'publish',
  'has_password'        => false,
  'posts_per_page'      => 6,
  'ignore_sticky_posts' => true,
) );

// モバイルの初期表示（3枚目）には最新記事を置く。Pickup に含まれる場合は重複させない。
$mobile_pickup_posts = $pickup_posts;
if ( $lately_posts ) {
  $newest_post = $lately_posts[0];
  $mobile_pickup_posts = array_values( array_filter(
    $mobile_pickup_posts,
    static function ( $post ) use ( $newest_post ) {
      return $post->ID !== $newest_post->ID;
    }
  ) );
  array_splice( $mobile_pickup_posts, min( 2, count( $mobile_pickup_posts ) ), 0, array( $newest_post ) );
  $mobile_pickup_posts = array_slice( $mobile_pickup_posts, 0, 5 );
}

// === ホーム画面カード設定 (固定ページ) ===
$home_settings_id = function_exists( 'wawahz_home_settings_page_id' ) ? wawahz_home_settings_page_id() : 0;
$home_np_id       = $home_settings_id ? (int) get_post_meta( $home_settings_id, 'wawahz_home_nowplaying', true ) : 0;
$home_work_ids    = $home_settings_id ? array(
  (int) get_post_meta( $home_settings_id, 'wawahz_home_work_1', true ),
  (int) get_post_meta( $home_settings_id, 'wawahz_home_work_2', true ),
) : array( 0, 0 );

// Now Playing 表示楽曲 (固定ページ指定 → 自動=最新楽曲)
$latest_track = null;
if ( $home_np_id ) {
  $picked_np = get_post( $home_np_id );
  if ( $picked_np && 'post' === $picked_np->post_type && 'publish' === $picked_np->post_status ) {
    $picked_media = wawahz_post_media( $picked_np );
    if ( $picked_media ) {
      $latest_track = array( 'post' => $picked_np, 'media' => $picked_media );
    }
  }
}
if ( ! $latest_track ) {
  $latest_music = wawahz_nowplaying_posts( 1 );
  $latest_track = $latest_music ? $latest_music[0] : null;
}
$latest_track_artist = '';
$latest_track_album  = '';
$latest_track_mood   = '';
if ( $latest_track ) {
  $latest_track_artist = get_post_meta( $latest_track['post']->ID, 'wawahz_artist', true )
    ?: get_the_author_meta( 'display_name', $latest_track['post']->post_author );
  $latest_track_album  = get_post_meta( $latest_track['post']->ID, 'wawahz_album', true );
  $latest_track_mood   = get_post_meta( $latest_track['post']->ID, 'wawahz_mood', true );
}
$latest_track_yt_id = ( $latest_track && $latest_track['media']['type'] === 'youtube' )
  ? esc_attr( $latest_track['media']['youtube_id'] ) : '';

// Works (Gallery) サブカード用
// ※ category_name は存在しないスラッグだとフィルターが無視され全件返るため、
//   タームを解決して存在しない場合は空にする (「カテゴリが無くても works が出る」対策)
$work_term  = get_category_by_slug( 'work' );
$auto_works = $work_term ? get_posts( array(
  'post_type'      => 'post',
  'post_status'    => 'publish',
  'has_password'   => false,
  'posts_per_page' => 4,
  'category__in'   => array( $work_term->term_id ),
) ) : array();
// 固定ページ指定の作品を優先し、空き枠は最新の作品で自動補完 (最大2件)
$work_posts    = array();
$work_seen_ids = array();
foreach ( $home_work_ids as $picked_id ) {
  if ( ! $picked_id || isset( $work_seen_ids[ $picked_id ] ) ) {
    continue;
  }
  $picked_work = get_post( $picked_id );
  if ( $picked_work && 'post' === $picked_work->post_type && 'publish' === $picked_work->post_status ) {
    $work_posts[]              = $picked_work;
    $work_seen_ids[ $picked_id ] = true;
  }
}
foreach ( $auto_works as $auto_work ) {
  if ( count( $work_posts ) >= 2 ) {
    break;
  }
  if ( isset( $work_seen_ids[ $auto_work->ID ] ) ) {
    continue;
  }
  $work_posts[]                = $auto_work;
  $work_seen_ids[ $auto_work->ID ] = true;
}

?>

<?php /* ===================== モバイルのみ: Pickup カルーセル + Lately ===================== */ ?>
<div class="mobile-home-wrapper mobile-only">

  <?php /* --- Pickup 見出し (デザイン仕様: 中央寄せ 16sp 太字) --- */ ?>
  <div class="mobile-pickup-section">
    <div class="mobile-pickup-heading">
      <h2 class="title-medium"><?php echo wawahz_icon( 'push_pin' ); ?><span><?php esc_html_e( 'Pickup', 'wawahz' ); ?></span></h2>
    </div>

    <?php /* --- Pickup カルーセル (スワイプ / スナップ / ドット) --- */ ?>
    <div class="mobile-pickup-row" id="mobile-pickup-row" role="region" aria-roledescription="<?php esc_attr_e( 'カルーセル', 'wawahz' ); ?>" aria-label="<?php esc_attr_e( 'Pickup 記事', 'wawahz' ); ?>">
      <button class="m3-btn m3-btn-filled m3-btn-icon-only" id="mobile-pickup-prev-btn" type="button" aria-label="<?php esc_attr_e( '前の記事', 'wawahz' ); ?>">
        <?php echo wawahz_icon( 'arrow_back' ); ?>
      </button>

      <div class="mobile-pickup-viewport">
        <ul class="mobile-pickup-track" id="mobile-pickup-track" role="list" tabindex="0" aria-label="<?php esc_attr_e( 'Pickup 記事', 'wawahz' ); ?>">
          <?php
          if ( $mobile_pickup_posts ) :
            $initial_pickup_index = count( $mobile_pickup_posts ) >= 3 ? 2 : 0;
            foreach ( $mobile_pickup_posts as $pickup_index => $pickup_post ) :
              $pickup_cats  = get_the_category( $pickup_post->ID );
              $pickup_thumb = get_the_post_thumbnail_url( $pickup_post, 'large' ) ?: get_template_directory_uri() . '/images/music_shimmer.svg?v=20250925_02';
          ?>
              <li class="mobile-pickup-slide<?php echo $pickup_index === $initial_pickup_index ? ' is-active' : ''; ?>">
                <a class="mobile-pickup-card"
                   href="<?php echo esc_url( get_permalink( $pickup_post ) ); ?>"
                   style="background-image:url('<?php echo esc_url( $pickup_thumb ); ?>')"
                   aria-label="<?php echo esc_attr( get_the_title( $pickup_post ) ); ?>">
                  <div class="mobile-pickup-scrim">
                    <?php if ( $pickup_cats ) : ?><span class="mobile-pickup-badge"><?php echo esc_html( $pickup_cats[0]->name ); ?></span><?php endif; ?>
                    <h3 class="mobile-pickup-title"><?php echo esc_html( get_the_title( $pickup_post ) ); ?></h3>
                    <p class="mobile-pickup-summary"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $pickup_post ) ), 18 ) ); ?></p>
                  </div>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else : ?>
            <li class="mobile-pickup-slide is-active">
              <div class="mobile-pickup-card mobile-pickup-card--empty">
                <div class="mobile-pickup-scrim">
                  <h3 class="mobile-pickup-title"><?php esc_html_e( '最初の記事をお楽しみに', 'wawahz' ); ?></h3>
                </div>
              </div>
            </li>
          <?php endif; ?>
        </ul>
      </div>

      <button class="m3-btn m3-btn-filled m3-btn-icon-only" id="mobile-pickup-next-btn" type="button" aria-label="<?php esc_attr_e( '次の記事', 'wawahz' ); ?>">
        <?php echo wawahz_icon( 'arrow_forward' ); ?>
      </button>
    </div>

    <?php /* カルーセル ページネーション (ドットインジケーター) */ ?>
    <div class="mobile-pickup-dots" id="mobile-pickup-dots" role="tablist" aria-label="<?php esc_attr_e( 'Pickup ページネーション', 'wawahz' ); ?>"></div>
  </div>

  <?php /* --- Lately ヘッダー --- */ ?>
  <div class="mobile-lately-heading">
    <h2 class="title-medium"><?php echo wawahz_icon( 'schedule' ); ?><span><?php esc_html_e( 'Lately', 'wawahz' ); ?></span></h2>
  </div>

  <?php /* --- Lately ボックス × 3 --- */ ?>
  <div class="mobile-lately-list" id="mobile-lately-container">
    <?php
    $lately_count = 0;
    foreach ( $lately_posts as $lp ) :
      if ( $lately_count >= 3 ) break;
      $lately_count++;
      $lcats      = get_the_category( $lp->ID );
      $lbadge     = $lcats[0]->name ?? '';
      $lthumb_url = get_the_post_thumbnail_url( $lp, 'large' ) ?: get_template_directory_uri() . '/images/music_shimmer.svg?v=20250925_02';
      $lmedia     = wawahz_post_media( $lp );
      $lyt_id     = ( $lmedia && $lmedia['type'] === 'youtube' ) ? $lmedia['youtube_id'] : '';
      $lartist    = get_post_meta( $lp->ID, 'wawahz_artist', true ) ?: get_the_author_meta( 'display_name', $lp->post_author );
      $lmedia_id  = 'wawahz-lately-media-' . $lp->ID;
    ?>
      <a class="lately-item" href="<?php echo esc_url( get_permalink( $lp ) ); ?>" style="background-image:url('<?php echo esc_url( $lthumb_url ); ?>')" aria-label="<?php echo esc_attr( get_the_title( $lp ) ); ?>">
        <div class="lately-item-content">
          <?php if ( $lbadge ) : ?><span class="lately-item-badge"><?php echo esc_html( strtoupper( $lbadge ) ); ?></span><?php endif; ?>
          <h3 class="lately-item-title"><?php echo esc_html( get_the_title( $lp ) ); ?></h3>
          <p class="lately-item-desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $lp ) ), 12 ) ); ?></p>
          <?php if ( $lmedia ) : ?>
            <?php /* 試聴チップ: [data-open-player] でモーダル再生 (カード内リンクの遷移はJS側で抑止) */ ?>
            <span class="lately-play-chip"
                  data-open-player
                  data-youtube-id="<?php echo esc_attr( $lyt_id ); ?>"
                  data-title="<?php echo esc_attr( get_the_title( $lp ) ); ?>"
                  data-artist="<?php echo esc_attr( $lartist ); ?>"
                  data-post-url="<?php echo esc_url( get_permalink( $lp ) ); ?>"
                  data-media-template-id="<?php echo esc_attr( $lmedia_id ); ?>"
                  aria-label="<?php esc_attr_e( '試聴', 'wawahz' ); ?>">
              <?php echo wawahz_icon( 'play_arrow' ); ?>
              <span><?php esc_html_e( '試聴', 'wawahz' ); ?></span>
            </span>
            <?php if ( $lmedia['type'] !== 'youtube' ) : ?>
              <template id="<?php echo esc_attr( $lmedia_id ); ?>" class="nowplaying-inline-media"><?php echo wawahz_render_gallery_media( $lmedia ); ?></template>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
    <?php if ( ! $lately_posts ) : ?>
      <p class="lately-empty"><?php esc_html_e( 'まだ記事がありません。', 'wawahz' ); ?></p>
    <?php endif; ?>
  </div>

  <div class="mobile-feature-links">
    <a href="<?php echo esc_url( wawahz_view_url( 'nowplaying' ) ); ?>">
      <?php echo wawahz_icon( 'headphones' ); ?>
      <span><strong>Now Playing</strong><small><?php echo esc_html( $latest_track ? get_the_title( $latest_track['post'] ) : __( '音楽を探す', 'wawahz' ) ); ?></small></span>
      <?php echo wawahz_icon( 'arrow_forward' ); ?>
    </a>
    <a href="<?php echo esc_url( wawahz_view_url( 'gallery' ) ); ?>">
      <?php echo wawahz_icon( 'grid_view' ); ?>
      <span><strong>Gallery</strong><small><?php esc_html_e( '作品とアイデアをみる', 'wawahz' ); ?></small></span>
      <?php echo wawahz_icon( 'arrow_forward' ); ?>
    </a>
  </div>
</div>

<?php /* ===================== デスクトップのみ: 2 カラムレイアウト (見本Canvas完全準拠) ===================== */ ?>
<div class="desktop-only dhome-desktop-container" id="desktop-home-container">
  <div class="dhome-two-column-layout">

    <?php /* ========== 左カラム: Pickup 側 ========== */ ?>
    <div class="dhome-column">
      <h2 class="dhome-section-title"><?php echo wawahz_icon( 'push_pin' ); ?><span>Pickup</span></h2>

      <div class="dhome-grid-2x2">
        <?php
        $desk_cards = array_slice( $pickup_posts, 0, 4 );
        for ( $i = 0; $i < 4; $i++ ) :
          if ( isset( $desk_cards[$i] ) ) :
            $dc = $desk_cards[$i];
            $dc_cats  = get_the_category( $dc->ID );
            $dc_thumb = get_the_post_thumbnail_url( $dc, 'large' ) ?: get_template_directory_uri() . '/images/music_shimmer.svg?v=20250925_02';
        ?>
          <a class="dhome-article-card" id="desk-card-<?php echo esc_attr( $i + 1 ); ?>" href="<?php echo esc_url( get_permalink( $dc ) ); ?>"
             style="background-image:url('<?php echo esc_url( $dc_thumb ); ?>')"
             aria-label="<?php echo esc_attr( get_the_title( $dc ) ); ?>">
            <div class="card-scrim-white">
              <?php if ( $dc_cats ) : ?><span class="card-badge"><?php echo esc_html( strtoupper( $dc_cats[0]->name ) ); ?></span><?php endif; ?>
              <h3 class="card-title"><?php echo esc_html( get_the_title( $dc ) ); ?></h3>
              <p class="card-desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $dc ) ), 14 ) ); ?></p>
            </div>
          </a>
        <?php else : ?>
          <div class="dhome-article-card dhome-article-card--empty" aria-hidden="true">
            <div class="card-scrim-white"><h3 class="card-title"><?php esc_html_e( '近日公開', 'wawahz' ); ?></h3></div>
          </div>
        <?php endif; endfor; ?>
      </div>

      <?php /* 濃いグレーのコンテナボックス + 上部に重なるNowplayingピルボタン */ ?>
      <div class="dhome-bottom-box-wrapper home-feature home-feature--music">
        <a class="dhome-overlapping-pill" href="<?php echo esc_url( wawahz_view_url( 'nowplaying' ) ); ?>" id="desk-nowplaying-btn" title="<?php esc_attr_e( 'Now Playing ギャラリーを開く', 'wawahz' ); ?>" aria-label="Now Playing Gallery">
          <?php echo wawahz_icon( 'headphones' ); ?><span>Now Playing</span><?php echo wawahz_icon( 'arrow_forward' ); ?>
        </a>

        <div class="m3-box dhome-bottom-box">
          <div class="dhome-player-row">
            <!-- 左: 丸型 music_note ボタン (ギャラリー画面へジャンプ) -->
            <a class="dhome-circle-btn" href="<?php echo esc_url( wawahz_view_url( 'nowplaying' ) ); ?>" id="desk-music-note-btn" title="<?php esc_attr_e( 'Now Playing ギャラリーを開く', 'wawahz' ); ?>" aria-label="Go to Now Playing Gallery">
              <span class="material-symbols-rounded" aria-hidden="true">music_note</span>
            </a>

            <!-- 中央: ダークティール色カード (半透明画像プレビュー・見出し・補足テキスト・imageアイコン) -->
            <?php if ( $latest_track ) :
              $latest_track_thumb = get_the_post_thumbnail_url( $latest_track['post'], 'medium_large' ) ?: ( $latest_track_yt_id ? 'https://img.youtube.com/vi/' . $latest_track_yt_id . '/hqdefault.jpg' : get_template_directory_uri() . '/images/music_shimmer.svg?v=20250925_02' );
            ?>
              <div class="dhome-inner-card"
                 id="desk-music-playback-card"
                 role="button"
                 tabindex="0"
                 style="cursor: pointer; --card-bg-img: url('<?php echo esc_url( $latest_track_thumb ); ?>');"
                 title="<?php esc_attr_e( 'モーダルで再生', 'wawahz' ); ?>"
                 data-open-player
                 data-youtube-id="<?php echo esc_attr( $latest_track_yt_id ); ?>"
                 data-title="<?php echo esc_attr( get_the_title( $latest_track['post'] ) ); ?>"
                 data-artist="<?php echo esc_attr( $latest_track_artist ); ?>"
                 data-album="<?php echo esc_attr( $latest_track_album ?? '' ); ?>"
                 data-mood="<?php echo esc_attr( $latest_track_mood ?? '' ); ?>"
                 data-year="<?php echo esc_attr( get_the_date( 'Y', $latest_track['post'] ) ); ?>"
                 data-commentary="<?php echo esc_attr( wp_strip_all_tags( get_the_excerpt( $latest_track['post'] ) ) ); ?>"
                 data-post-url="<?php echo esc_url( get_permalink( $latest_track['post'] ) ); ?>"
                 data-media-template-id="wawahz-desk-media-<?php echo esc_attr( $latest_track['post']->ID ); ?>"
                 aria-label="<?php echo esc_attr( get_the_title( $latest_track['post'] ) ); ?> を再生">
                <span class="home-music-cover"><img class="home-feature-artwork" src="<?php echo esc_url( $latest_track_thumb ); ?>" alt="" loading="lazy" decoding="async" width="80" height="80"></span>
                <div class="dhome-inner-card-texts">
                  <span class="home-music-eyebrow"><?php esc_html_e( 'LATEST TRACK', 'wawahz' ); ?></span>
                  <h3 class="dhome-inner-card-title" id="desk-music-title"><?php echo esc_html( get_the_title( $latest_track['post'] ) ); ?></h3>
                  <p class="dhome-inner-card-desc" id="desk-music-artist"><?php echo esc_html( $latest_track_artist . ( ! empty( $latest_track_album ) ? ' - ' . $latest_track_album : '' ) ); ?></p>
                </div>
                <div class="dhome-inner-card-icon">
                  <span class="material-symbols-rounded" aria-hidden="true">image</span>
                </div>
                <?php /* YouTube 以外のメディアは標準プレイヤー (audio / video) をモーダルで再生 */ ?>
                <?php if ( $latest_track['media']['type'] !== 'youtube' ) : ?>
                  <template id="wawahz-desk-media-<?php echo esc_attr( $latest_track['post']->ID ); ?>" class="nowplaying-inline-media"><?php echo wawahz_render_gallery_media( $latest_track['media'] ); ?></template>
                <?php endif; ?>
              </div>
            <?php else : ?>
              <?php /* 楽曲が無いときは架空の曲名を出さず中立的な空状態に */ ?>
              <a class="dhome-inner-card dhome-inner-card--empty" href="<?php echo esc_url( wawahz_view_url( 'nowplaying' ) ); ?>" id="desk-music-playback-card" title="<?php esc_attr_e( 'Now Playing ギャラリーを開く', 'wawahz' ); ?>">
                <span class="home-music-cover" aria-hidden="true"><span class="home-feature-artwork home-feature-artwork--empty"><?php echo wawahz_icon( 'music_note' ); ?></span></span>
                <div class="dhome-inner-card-texts">
                  <span class="home-music-eyebrow">COMING SOON</span>
                  <h3 class="dhome-inner-card-title" id="desk-music-title"><?php esc_html_e( '楽曲を準備中', 'wawahz' ); ?></h3>
                  <p class="dhome-inner-card-desc" id="desk-music-artist">Coming soon</p>
                </div>
                <div class="dhome-inner-card-icon">
                  <span class="material-symbols-rounded" aria-hidden="true">music_note</span>
                </div>
              </a>
            <?php endif; ?>

            <!-- 右: 丸型 play_arrow ボタン (プレイヤーモーダルで再生) -->
            <button class="dhome-circle-btn" id="desk-music-play-btn" type="button"
                    title="<?php esc_attr_e( 'プレイヤーモーダルで再生', 'wawahz' ); ?>"
                    <?php if ( $latest_track ) : ?>
                      data-open-player
                      data-youtube-id="<?php echo esc_attr( $latest_track_yt_id ); ?>"
                      data-title="<?php echo esc_attr( get_the_title( $latest_track['post'] ) ); ?>"
                      data-artist="<?php echo esc_attr( $latest_track_artist ); ?>"
                      data-album="<?php echo esc_attr( $latest_track_album ?? '' ); ?>"
                      data-mood="<?php echo esc_attr( $latest_track_mood ?? '' ); ?>"
                      data-year="<?php echo esc_attr( get_the_date( 'Y', $latest_track['post'] ) ); ?>"
                      data-commentary="<?php echo esc_attr( wp_strip_all_tags( get_the_excerpt( $latest_track['post'] ) ) ); ?>"
                      data-post-url="<?php echo esc_url( get_permalink( $latest_track['post'] ) ); ?>"
                      data-media-template-id="wawahz-desk-media-<?php echo esc_attr( $latest_track['post']->ID ); ?>"
                    <?php endif; ?>
                    <?php disabled( ! $latest_track ); ?>
                    aria-label="<?php esc_attr_e( 'モーダルで再生', 'wawahz' ); ?>">
              <span class="material-symbols-rounded" id="desk-music-play-icon" aria-hidden="true">play_arrow</span>
            </button>
          </div>
        </div>
        <div class="home-music-caption">
          <?php echo wawahz_icon( 'graphic_eq' ); ?>
          <span><?php esc_html_e( '気になる音を、ひと息。', 'wawahz' ); ?></span>
          <?php if ( $latest_track ) : ?>
            <span class="home-music-hint"><?php esc_html_e( 'タップして再生', 'wawahz' ); ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php /* ========== 右カラム: Lately 側 ========== */ ?>
    <div class="dhome-column">
      <h2 class="dhome-section-title"><?php echo wawahz_icon( 'schedule' ); ?><span>Lately</span></h2>

      <div class="dhome-grid-2x2">
        <?php
        $lately_desk = array_slice( $lately_posts, 0, 4 );
        for ( $j = 0; $j < 4; $j++ ) :
          if ( isset( $lately_desk[$j] ) ) :
            $lc = $lately_desk[$j];
            $lc_cats  = get_the_category( $lc->ID );
            $lc_thumb = get_the_post_thumbnail_url( $lc, 'large' ) ?: get_template_directory_uri() . '/images/music_velocity.svg?v=20250925_02';
        ?>
          <a class="dhome-article-card" id="desk-lately-card-<?php echo esc_attr( $j + 1 ); ?>" href="<?php echo esc_url( get_permalink( $lc ) ); ?>"
             style="background-image:url('<?php echo esc_url( $lc_thumb ); ?>')"
             aria-label="<?php echo esc_attr( get_the_title( $lc ) ); ?>">
            <div class="card-scrim-white">
              <?php if ( $lc_cats ) : ?><span class="card-badge"><?php echo esc_html( strtoupper( $lc_cats[0]->name ) ); ?></span><?php endif; ?>
              <h3 class="card-title"><?php echo esc_html( get_the_title( $lc ) ); ?></h3>
              <p class="card-desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $lc ) ), 14 ) ); ?></p>
            </div>
          </a>
        <?php else : ?>
          <div class="dhome-article-card dhome-article-card--empty" aria-hidden="true">
            <div class="card-scrim-white"><h3 class="card-title"><?php esc_html_e( '近日公開', 'wawahz' ); ?></h3></div>
          </div>
        <?php endif; endfor; ?>
      </div>

      <?php /* 濃いグレーのコンテナボックス + 上部に重なるGalleryピルボタン (常に表示) */ ?>
      <div class="dhome-bottom-box-wrapper home-feature home-feature--gallery">
        <a class="dhome-overlapping-pill" href="<?php echo esc_url( wawahz_view_url( 'gallery' ) ); ?>" id="desk-gallery-btn" title="<?php esc_attr_e( 'Works Gallery (作品集) を開く', 'wawahz' ); ?>" aria-label="Open Works Gallery">
          <?php echo wawahz_icon( 'grid_view' ); ?><span>Gallery</span><?php echo wawahz_icon( 'arrow_forward' ); ?>
        </a>

        <div class="m3-box dhome-bottom-box">
          <div class="dhome-subcards-row">
            <?php for ( $k = 0; $k < 2; $k++ ) : ?>
              <?php if ( isset( $work_posts[ $k ] ) ) :
                $wp_post  = $work_posts[ $k ];
                $wp_cats  = get_the_category( $wp_post->ID );
                // 作品分類はメタ (wawahz_work_category) を優先し、ギャラリー画面のバッジ表記と揃える
                $wp_cat_name = function_exists( 'wawahz_work_category' ) ? wawahz_work_category( $wp_post->ID ) : ( $wp_cats[0]->name ?? 'Hardware' );
                $wp_year  = get_post_meta( $wp_post->ID, 'wawahz_work_year', true ) ?: get_the_date( 'Y', $wp_post );
                $wp_thumb = get_the_post_thumbnail_url( $wp_post, 'medium_large' ) ?: get_template_directory_uri() . '/images/music_shimmer.svg?v=20250925_02';
              ?>
                <a class="dhome-inner-card dhome-inner-card--gallery" id="desk-sub-card-<?php echo esc_attr( $k + 1 ); ?>" href="<?php echo esc_url( get_permalink( $wp_post ) ); ?>"
                   style="cursor: pointer; --card-bg-img: url('<?php echo esc_url( $wp_thumb ); ?>');"
                   title="<?php esc_attr_e( '作品詳細を開く', 'wawahz' ); ?>"
                   aria-label="<?php echo esc_attr( get_the_title( $wp_post ) ); ?>">
                  <img class="home-gallery-artwork" src="<?php echo esc_url( $wp_thumb ); ?>" alt="" loading="lazy" decoding="async" width="160" height="64">
                  <h3 class="dhome-inner-card-title" id="desk-sub-title-<?php echo esc_attr( $k + 1 ); ?>"><?php echo esc_html( get_the_title( $wp_post ) ); ?></h3>
                  <p class="dhome-inner-card-desc" id="desk-sub-desc-<?php echo esc_attr( $k + 1 ); ?>"><?php echo esc_html( $wp_cat_name . ' • ' . $wp_year ); ?></p>
                </a>
              <?php else : ?>
              <a class="dhome-inner-card dhome-inner-card--empty" id="desk-sub-card-<?php echo esc_attr( $k + 1 ); ?>" href="<?php echo esc_url( wawahz_view_url( 'gallery' ) ); ?>" title="<?php esc_attr_e( '作品一覧を開く', 'wawahz' ); ?>">
                <div class="dhome-inner-card-texts">
                  <h3 class="dhome-inner-card-title" id="desk-sub-title-<?php echo esc_attr( $k + 1 ); ?>"><?php esc_html_e( '作品を準備中', 'wawahz' ); ?></h3>
                  <p class="dhome-inner-card-desc" id="desk-sub-desc-<?php echo esc_attr( $k + 1 ); ?>">Coming soon</p>
                </div>
                <div class="dhome-inner-card-icon">
                  <span class="material-symbols-rounded" aria-hidden="true">palette</span>
                </div>
              </a>
              <?php endif; ?>
            <?php endfor; ?>
          </div>
        </div>
        <div class="home-gallery-caption">
          <?php echo wawahz_icon( 'palette' ); ?>
          <span><?php esc_html_e( '日々のアイデアを、かたちに。', 'wawahz' ); ?></span>
          <a href="<?php echo esc_url( wawahz_view_url( 'gallery' ) ); ?>"><?php esc_html_e( '作品一覧へ', 'wawahz' ); ?></a>
        </div>
      </div>
    </div>

  </div>
</div>
