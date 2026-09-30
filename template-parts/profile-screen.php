<?php
/**
 * template-parts/profile-screen.php
 * プロフィール画面。名前とプロフィール文は X (@ya_ya_moderate) を参考にする。
 * ?view=profile (view.php) と page-profile.php で共用する。
 */
$wawahz_show_content = !empty($args['show_content']);
$wawahz_avatar = get_template_directory_uri() . '/images/profile.jpg';
$wawahz_name = 'Wawa404';
$wawahz_bio = "trident computer / 07\n興味絵とボカリス寄り。メンタル餅。\n自称ガジェットと web 系音楽愛好家。\nAT1選 : snowmilk (aqu3ra)\nhigma / Adomiori / 筧川真生";
$wawahz_socials = array(
  array('icon' => 'code', 'label' => 'GitHub', 'url' => 'https://github.com/yawara404', 'external' => true),
  array('icon' => 'smart_display', 'label' => 'YouTube', 'url' => 'https://youtube.com', 'external' => true),
  array('icon' => 'tag', 'label' => 'X (Twitter)', 'url' => 'https://x.com/ya_ya_moderate', 'external' => true),
  array('icon' => 'headphones', 'label' => 'Now Playing', 'url' => wawahz_view_url('nowplaying'), 'external' => false),
  array('icon' => 'grid_view', 'label' => 'Works', 'url' => wawahz_view_url('gallery'), 'external' => false),
);
?>
<section class="screen-view active profile-screen-view" id="screen-profile" aria-label="<?php esc_attr_e('プロフィール', 'wawahz'); ?>">
  <div class="profile-container">
    <div class="profile-card">
      <div class="profile-head">
        <img class="profile-avatar" src="<?php echo esc_url($wawahz_avatar); ?>" alt="" width="96" height="96" loading="lazy" decoding="async">
        <h1 class="profile-name"><?php echo esc_html($wawahz_name); ?></h1>
      </div>

      <p class="profile-bio"><?php echo esc_html($wawahz_bio); ?></p>

      <div class="profile-links">
        <?php foreach ($wawahz_socials as $wawahz_link) : ?>
          <a class="profile-link" href="<?php echo esc_url($wawahz_link['url']); ?>"
             <?php echo $wawahz_link['external'] ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
            <span class="material-symbols-rounded" aria-hidden="true"><?php echo esc_html($wawahz_link['icon']); ?></span>
            <span><?php echo esc_html($wawahz_link['label']); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($wawahz_show_content && have_posts()) : ?>
      <?php while (have_posts()) : the_post(); ?>
        <?php if (trim(wp_strip_all_tags(get_the_content())) !== '') : ?>
          <div class="profile-card profile-content">
            <?php the_content(); ?>
          </div>
        <?php endif; ?>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</section>
