<?php
header("Content-Type: application/xml; charset=UTF-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <?php
  $posts = get_posts(array('numberposts' => -1, 'post_type' => 'post', 'post_status' => 'publish'));
  foreach ($posts as $post) {
    setup_postdata($post);
  ?>
    <url>
      <loc><?php the_permalink(); ?></loc>
      <lastmod><?php echo get_the_modified_date('c'); ?></lastmod>
      <changefreq>weekly</changefreq>
      <priority>0.8</priority>
    </url>
  <?php
  }
  wp_reset_postdata();
  ?>
</urlset>