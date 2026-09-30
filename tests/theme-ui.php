<?php
/** In-memory and read-only UI regression checks. Loaded by nowplaying.php. */
if (!defined('ABSPATH') || PHP_SAPI !== 'cli') { return; }
$original_get = $_GET;
$_GET['sort'] = array('bad');
np_check(wawahz_request_value('sort', 'date-desc') === 'date-desc', 'Array query parameters are rejected');
$_GET = array('cat_slug' => 'no-such-wawa-category');
$missing = wawahz_filter_query();
np_check($missing->post_count === 0, 'Unknown category returns no posts');
$_GET = array('sort' => 'date-asc');
$ascending = wawahz_filter_query();
$dates = wp_list_pluck($ascending->posts, 'post_date');
$sorted = $dates;
sort($sorted);
np_check($dates === $sorted, 'Ascending list actually orders dates');
$_GET = array('sort' => 'invalid-sort');
$latest = wawahz_filter_query();
np_check($latest->get('order') === 'DESC', 'Unknown sort uses latest first');
np_check($latest->get('post_status') === 'publish' && $latest->get('has_password') === false, 'Listing excludes non-public content');
$_GET = array();
$works = wawahz_filter_query(true);
$work_term = get_category_by_slug('work');
foreach ($works->posts as $item) {
  $terms = wp_get_post_categories($item->ID);
  $allowed = $work_term ? array_merge(array($work_term->term_id), get_term_children($work_term->term_id, 'category')) : array();
  np_check((bool) array_intersect($terms, $allowed), 'Gallery contains actual work-category posts');
}

$original_query = $GLOBALS['wp_query'] ?? null;
$original_the_query = $GLOBALS['wp_the_query'] ?? null;
$home_query = new WP_Query(array('post_type' => 'post', 'posts_per_page' => 1));
$home_query->is_home = true;
$home_query->is_singular = false;
$GLOBALS['wp_query'] = $home_query;
$_GET = array('view' => 'gallery');
np_check(wawahz_view() === 'gallery', 'Legacy gallery route recognized');
np_check(basename(apply_filters('template_include', '/index.php')) === 'view.php', 'Legacy route selects shared template');
$_GET = array('view' => '../../outside');
np_check(wawahz_view() === '', 'Unrecognized view cannot select a template');

// Emulate static front-page settings in memory; no database writes.
$pages = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 1));
if ($pages) {
  $front_id = $pages[0]->ID;
  $show_front = function () { return 'page'; };
  $page_front = function () use ($front_id) { return $front_id; };
  add_filter('pre_option_show_on_front', $show_front);
  add_filter('pre_option_page_on_front', $page_front);
  $GLOBALS['wp_query'] = new WP_Query(array('page_id' => $front_id));
  $_GET = array('view' => 'nowplaying');
  np_check(wawahz_view() === 'nowplaying', 'Legacy route works with a static front page');
  remove_filter('pre_option_show_on_front', $show_front);
  remove_filter('pre_option_page_on_front', $page_front);
}
$_GET = array();
$GLOBALS['wp_query'] = $original_query;
$GLOBALS['wp_the_query'] = $original_the_query;

// Query pages without creating or assigning any actual page templates.
if ($pages) {
  $resolve_page = function ($posts, $query) use ($pages) {
    return $query->get('meta_value') === 'page-gallery.php' ? $pages : $posts;
  };
  add_filter('posts_pre_query', $resolve_page, 10, 2);
  np_check(wawahz_view_url('gallery') === get_permalink($pages[0]), 'Assigned gallery template uses a real permalink');
  remove_filter('posts_pre_query', $resolve_page, 10);

  // Now Playing は page-music.php を優先して検出する。
  $resolve_music = function ($posts, $query) use ($pages) {
    return $query->get('meta_value') === 'page-music.php' ? $pages : $posts;
  };
  add_filter('posts_pre_query', $resolve_music, 10, 2);
  $music_page = wawahz_view_page('nowplaying');
  np_check($music_page && (int) $music_page->ID === (int) $pages[0]->ID, 'Music template page takes priority for the Now Playing URL');
  remove_filter('posts_pre_query', $resolve_music, 10);
}
$_GET = $original_get;
np_check(current_theme_supports('align-wide') && current_theme_supports('editor-styles'), 'Editor and wide block support enabled');

// Home Gallery の固定枠解決はメタを差し替えて検証する (DB は変更しない)。
np_check(has_action('add_meta_boxes_post', 'wawahz_add_home_gallery_pin_metabox'), 'Gallery pin metabox registered for posts');
np_check(has_action('save_post_post', 'wawahz_save_home_gallery_pin_metabox'), 'Gallery pin save handler registered for posts');
np_check(has_action('add_meta_boxes_post', 'wawahz_add_home_nowplaying_pin_metabox'), 'NowPlaying pin metabox registered for posts');
np_check(has_action('save_post_post', 'wawahz_save_home_nowplaying_pin_metabox'), 'NowPlaying pin save handler registered for posts');
np_check(has_action('add_meta_boxes_post', 'wawahz_add_home_pickup_pin_metabox'), 'Pickup pin metabox registered for posts');
np_check(has_action('save_post_post', 'wawahz_save_home_pickup_pin_metabox'), 'Pickup pin save handler registered for posts');
if (wawahz_home_settings_page_id()) {
  $stub_home_work = function ($value, $object_id, $meta_key, $single) {
    if ($meta_key === 'wawahz_home_work_1') { return 111111; }
    if ($meta_key === 'wawahz_home_work_2') { return 222222; }
    return $value;
  };
  add_filter('get_post_metadata', $stub_home_work, 10, 4);
  np_check(wawahz_home_gallery_slot(111111) === 1, 'First Gallery slot resolves to the pinned post');
  np_check(wawahz_home_gallery_slot(222222) === 2, 'Second Gallery slot resolves to the pinned post');
  np_check(wawahz_home_gallery_slot(333333) === 0, 'Unpinned post keeps the automatic order');
  remove_filter('get_post_metadata', $stub_home_work, 10);
}

