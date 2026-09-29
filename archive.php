<?php get_header(); ?>
<main class="site-main wawa-container" id="main-content" tabindex="-1">
  <header class="page-heading"><p class="eyebrow">ARCHIVE</p><h1><?php echo esc_html(wp_strip_all_tags(get_the_archive_title())); ?></h1>
    <?php if (get_the_archive_description()) : ?><div class="archive-description"><?php echo wp_kses_post(get_the_archive_description()); ?></div><?php endif; ?>
  </header>
  <nav class="category-chips" aria-label="<?php esc_attr_e('カテゴリー', 'wawahz'); ?>"><a href="<?php echo esc_url(wawahz_view_url('category')); ?>"><?php esc_html_e('すべて', 'wawahz'); ?></a>
    <?php foreach (get_categories() as $category) : ?><a href="<?php echo esc_url(get_category_link($category)); ?>" <?php echo is_category($category->term_id) ? 'aria-current="page"' : ''; ?>><?php echo esc_html($category->name); ?></a><?php endforeach; ?>
  </nav>
  <?php get_template_part('template-parts/post-list'); ?>
</main>
<?php get_footer(); ?>
