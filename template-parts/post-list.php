<?php if (have_posts()) : ?>
  <div class="post-grid">
    <?php while (have_posts()) : the_post(); get_template_part('template-parts/post-card'); endwhile; ?>
  </div>
  <?php wawahz_pagination(); ?>
<?php else : get_template_part('template-parts/empty'); endif; ?>
