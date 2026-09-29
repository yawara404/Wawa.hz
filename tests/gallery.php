<?php
/** Read-only gallery regression checks. Run with Local PHP and php.ini. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__, 4) . '/wp-load.php';
$checks = 0;
function np_check($condition, $message) {
  global $checks;
  if (!$condition) { throw new RuntimeException($message); }
  $checks++;
}
$work_term = get_category_by_slug('work');
// Read-only pagination checks against all published work posts, not just page 1.
$gallery_ids = $work_term ? get_posts(array(
  'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
  'posts_per_page' => -1, 'fields' => 'ids',
  'tax_query' => array(array('taxonomy' => 'category', 'field' => 'term_id', 'terms' => $work_term->term_id)),
  'orderby' => array('date' => 'DESC', 'ID' => 'DESC'),
)) : array();
$page_size = (int) wawahz_gallery_query('all', 1)->get('posts_per_page');
np_check($page_size === 4, 'Gallery shows 4 works per page (desktop 2 columns x 2 rows)');
foreach (array('all', 'Hardware', 'Audio', 'Photo', 'Design', 'Art', 'Craft') as $filter) {
  $expected = array_values(array_filter($gallery_ids, function ($id) use ($filter) {
    return $filter === 'all' || strcasecmp(wawahz_work_category($id), $filter) === 0;
  }));
  $seen = array();
  $page_count = max(1, (int) ceil(count($expected) / $page_size));
  for ($page = 1; $page <= $page_count; $page++) {
    $query = wawahz_gallery_query($filter, $page);
    np_check($query->post_count <= $page_size, 'Gallery page size: ' . $filter);
    np_check((int) $query->found_posts === count($expected), 'Gallery filtered total: ' . $filter);
    $seen = array_merge($seen, wp_list_pluck($query->posts, 'ID'));
  }
  np_check($seen === $expected, 'All gallery pages cover exactly the selected category: ' . $filter);
}
np_check(wawahz_gallery_query('all', 999999)->get('paged') === 1, 'Stale gallery page recovers to first page');

// 見出し右上のカテゴリフィルタ (ドロップダウン) は、作品がある分類だけを作品数の多い順に返す。
$terms = wawahz_work_filter_terms();
$in_use = array_values(array_unique(array_map('wawahz_work_category', $gallery_ids)));
$sorted_terms = $terms;
sort($sorted_terms);
sort($in_use);
np_check($sorted_terms === $in_use, 'Filter options list exactly the categories actually in use');
np_check(count($terms) === count(array_unique($terms)), 'Filter options are unique');
np_check(!in_array('all', $terms, true), 'Filter options exclude the All pseudo category');
$previous = PHP_INT_MAX;
foreach ($terms as $term_name) {
  $count = (int) wawahz_gallery_query($term_name)->found_posts;
  np_check($count > 0, 'Filter option has works: ' . $term_name);
  np_check($count <= $previous, 'Filter options are ordered by work count: ' . $term_name);
  $previous = $count;
  np_check(wawahz_resolve_work_filter(strtolower($term_name)) === $term_name, 'Filter name is normalized: ' . $term_name);
}
np_check(wawahz_resolve_work_filter('all') === 'all', 'All filter stays All');
np_check(wawahz_resolve_work_filter('no-such-category') === 'all', 'Unknown filter falls back to All');
np_check(wawahz_resolve_work_filter(array('Audio')) === 'all', 'Array filter falls back to All');

// 描画チェック: チップは廃止し、並び替えと同じピル型ドロップダウンを1つだけ出す。
$render_gallery = function () {
  ob_start();
  get_template_part('template-parts/works-gallery');
  return preg_replace('/\s+/', ' ', (string) ob_get_clean());
};
$html = $render_gallery();
np_check(strpos($html, 'works-chip') === false, 'Legacy category chips are gone from the gallery header');
np_check(strpos($html, 'class="m3-sort-dropdown-wrapper works-filter-dropdown"') !== false, 'Filter dropdown reuses the shared sort dropdown component');
np_check(substr_count($html, '<select') === 1, 'Gallery header renders a single dropdown control');
np_check(strpos($html, 'class="m3-sort-select" id="works-filter-select"') !== false, 'Filter dropdown exposes a labelled native select');
np_check(strpos($html, 'onchange="location.href = this.value;"') !== false, 'Filter dropdown navigates on change');
np_check(substr_count($html, 'class="work-card"') === min($page_size, count($gallery_ids)), 'Gallery renders exactly one page of cards');

// グリッドの形 (デスクトップ2列×2行 = 4枠) は作品数に関係なく保つ:
// 足りない枠は「近日公開」カードで埋める。
$coming_soon = 'class="work-card is-coming-soon"';
np_check(strpos($html, 'empty-state') === false, 'An empty grid shows coming-soon cards instead of a bare message');
np_check(substr_count($html, $coming_soon) === max(0, $page_size - min($page_size, count($gallery_ids))), 'Gallery pads the 4 slots with coming-soon cards');
$saved_get = $_GET;
$last_page = max(1, (int) ceil(count($gallery_ids) / $page_size));
$_GET['gallery_page'] = (string) $last_page;
$last_html = $render_gallery();
$_GET = $saved_get;
$last_page_works = min($page_size, max(0, count($gallery_ids) - ($last_page - 1) * $page_size));
np_check(substr_count($last_html, 'class="work-card"') === $last_page_works, 'The last gallery page renders only its remaining works');
np_check(substr_count($last_html, $coming_soon) === $page_size - $last_page_works, 'The last gallery page fills the remaining slots with coming-soon cards');
preg_match_all('/<option value="([^"]*)"[^>]*>([^<]*)</', $html, $found, PREG_SET_ORDER);
$option_urls = array_map(function ($option) { return $option[1]; }, $found);
$option_labels = array_map(function ($option) { return trim($option[2]); }, $found);
np_check($option_labels === array_merge(array('All'), $terms), 'Dropdown lists All + every category once, ordered by work count');
np_check(strpos($option_urls[0], 'filter=') === false, 'All option points at the unfiltered gallery URL');
foreach (array_slice($option_labels, 1) as $index => $label) {
  $url = $option_urls[$index + 1];
  np_check(strpos($url, 'filter=' . rawurlencode($label)) !== false || strpos($url, 'filter=' . urlencode($label)) !== false, 'Category option links to its filtered URL: ' . $label);
}
np_check(preg_match('/<option value="[^"]*" selected=[\'"]selected[\'"]>\s*All</', $html) === 1, 'A clean URL marks All as the selected option');

// ?filter= を渡すと、その分類が選択状態になり 1 ページ目に戻る。
$original_get = $_GET;
$_GET['filter'] = 'no-such-category';
$fallback_html = $render_gallery();
if ($terms) {
  $_GET['filter'] = $terms[0];
  $filtered_html = $render_gallery();
}
$_GET = $original_get;
np_check(preg_match('/<option value="[^"]*" selected=[\'"]selected[\'"]>\s*All</', $fallback_html) === 1, 'An unknown filter falls back to All in the dropdown');
if ($terms) {
  np_check(preg_match('/<option value="([^"]*)" selected=[\'"]selected[\'"]>\s*([^<]*)</', $filtered_html, $selected) === 1 && trim($selected[2]) === $terms[0], 'The requested category becomes the selected option');
  np_check(strpos($selected[1], 'filter=') !== false && strpos($selected[1], 'gallery_page') === false, 'Selected option keeps the filter and drops the page number');
}

echo "Gallery: {$checks} checks passed." . PHP_EOL;
