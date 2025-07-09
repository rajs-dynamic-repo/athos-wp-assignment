<?php

// Theme constants
// --------------------------------------------------
define('THEME_NAME', 'searchspring');
define('DIST_DIR', get_template_directory_uri() . '/dist/');
define('IMAGES_DIR', get_template_directory_uri() . '/dist/images/');
define('TEMPLATE_DIR', get_template_directory_uri());
define('STYLESHEET_DIR', get_stylesheet_directory());


//
// endo Functions
// --------------------------------------------------


// Siloed function groups
// ---------------------------------------
require_once("inc/helpers.php");
require_once("inc/navigation.php");
// require_once("inc/footer-nav.php");
require_once("inc/rogue.php");
require_once("inc/scripts.php");
require_once("inc/custom_post_types.php");
// require_once("inc/login.php");
require_once("inc/shortcodes.php");
// // require_once("inc/comments.php");
require_once("inc/widgets.php");
require_once("inc/content.php");
require_once("inc/site-helpers.php");
require_once("inc/breadcrumbs.php");
// // require_once("inc/editor_styles.php");
// require_once("inc/acf-helpers.php");
require_once("inc/acf-setup-and-customizations.php");


// endo theme setup
// ---------------------------------------
add_action('after_setup_theme', 'endo_setup');

function endo_setup()
{
  add_theme_support('automatic-feed-links');
  add_theme_support('post-thumbnails');
  add_theme_support('align-wide');
  add_theme_support('title-tag');

  // add_theme_support( 'editor-styles' );
  // add_editor_style( 'editor-style.css' );

  // Add custom image sizes
  // --------------------------
  add_image_size('xl',   1280, 1280, false);
  add_image_size('xxl',  1600, 1600, false);
  // add_image_size( 'xxxl', 2200, 2200, false );

  // set up nav menus
  // --------------------------
  register_nav_menus(
    array(
      'main-menu'    => __('Main Menu',    THEME_NAME),
      'utility-menu' => __('Utility Menu', THEME_NAME),
      'footer-menu'  => __('Footer Menu',  THEME_NAME),
      'footer-secondary-menu'  => __('Footer Secondary Menu',  THEME_NAME)
    )
  );
}

//add redirect to cademy post that are linking to the knowledge base
//-----------------------------------------
add_action('template_redirect', 'academy_redirect_knowledge_base');

function academy_redirect_knowledge_base()
{
  if (is_singular('academy') && (get_field("knowledge_base_url"))) {
    wp_redirect("https://searchspring.com/ecommerce-academy/", 301);
    exit;
  }
}


// add excerpt to Page type
// ---------------------------------------
add_post_type_support('page', 'excerpt');
// add select template option for pages
add_post_type_support('page', 'page-attributes');

// Change Title Separator
// ---------------------------------------
function wploop_change_separator()
{
  return '|';
}
add_filter('document_title_separator', 'wploop_change_separator');



// remove default image sizes
// ---------------------------------------
add_filter('intermediate_image_sizes', 'remove_default_img_sizes', 10, 1);

function remove_default_img_sizes($sizes)
{
  $targets = ['1536x1536', '2048x2048'];

  foreach ($sizes as $size_index => $size) {
    if (in_array($size, $targets)) {
      unset($sizes[$size_index]);
    }
  }

  return $sizes;
}


// Limit search to blog posts
// ---------------------------------------
if (!is_admin()) {
  function wpb_search_filter($query)
  {

    if ($query->is_search) {

      $resource_types = searchspring_get_learn_types();
      $query->set('post_type', $resource_types);
    }

    return $query;
  }
  //add_filter('pre_get_posts','wpb_search_filter');
}


// Exclude Certain Pages from search
// ---------------------------------------
function exclude_pages_from_search($query)
{
  if ($query->is_main_query() && is_search() && !is_admin()) {

    $resource_types = isset($query->query["post_type"]) ? $query->query["post_type"] : searchspring_get_learn_types();
    //print_r($query);
    $query->set('post_type', $resource_types);
  }
  return $query;
}
add_filter('pre_get_posts', 'exclude_pages_from_search');



// Lower Yoast SEO metabox "priority" so it appears below that of Flexible Content Modules in the admin
// ---------------------------------------
add_filter('wpseo_metabox_prio', function () {
  return 'low';
});


// Remove the Body / Content Editor for Pages and Content Masters, which rely solely on our ACF Flexible Content Modules solution
// ---------------------------------------
function remove_content_editor()
{
  remove_post_type_support('page', 'editor');
  remove_post_type_support('content_masters', 'editor');
  remove_post_type_support('module_library', 'editor');
}
add_action('init', 'remove_content_editor');


// Add Wistia to oEmbed options
// ---------------------------------------
// wp_oembed_add_provider( '/https?:\/\/(.+)?(wistia.com|wi.st)\/(medias|embed)\/.*/', 'http://fast.wistia.com/oembed', true);


// ?
// ---------------------------------------
function capture_email_template($template)
{
  if (get_query_var("capture_email")) {
    $new_template = locate_template("inc/capture_email.php");
    if ('' != $new_template) {
      return $new_template;
    }
  }
  return $template;
}
add_filter('template_include', 'capture_email_template', 99);


// Resource retrieval
// ---------------------------------------

add_filter('query_vars', function ($vars) {
  $vars[] = "number_resources";
  $vars[] = "offset_resources";
  $vars[] = "abs_offset";
  $vars[] = "retrieve";
  $vars[] = "infinite";
  return $vars;
});

add_filter('template_include', 'searchspring_resource_templates');

function searchspring_resource_templates($template)
{

  global $wp_query;
  global $post;

  $fields = get_fields($post->ID);
  $can_gate = is_single() && array_key_exists("is_gated", $fields);
  $is_gated = get_field("is_gated", $post->ID);

  if (array_key_exists("retrieve", $wp_query->query_vars)):
    $template = __DIR__ . '/index.php';
  endif;

  if ($can_gate && !$is_gated) {
    switch ($post->post_type) {
      case "guides":
        $template = __DIR__ . '/single-guides.php';
        break;
      default:
        $template = __DIR__ . '/single--open.php';
        break;
    }
  } elseif ($can_gate) {
    switch ($post->post_type) {
      case "webinars":
        $template = __DIR__ . '/single-webinars.php';
        break;
      default:
        $template = __DIR__ . '/single--gated.php';
        break;
    }
  }

  if (file_exists($template)) {
    return $template;
  }

  return $template;
}


function assemble_retrieve_url($wp_query = null, $start_page = 0)
{

  if (!$wp_query) {
    global $wp_query;
  }

  //print_r($wp_query);

  $post_type = $wp_query->query['post_type'] ? $wp_query->query['post_type'] : "post";

  if (is_array($post_type) && count($post_type) == 1) {
    $post_type = array_pop($post_type);
  }

  $use_learn = is_array($post_type) || $post_type == "learn";
  $next_root = $post_type && !$use_learn ? $post_type : "learn";

  $default_numberposts = $wp_query->query_vars["is_search"] ? 9 : 6;
  $numberposts = $wp_query->query["number_resources"] ? $wp_query->query["number_resources"] : $default_numberposts;
  $offset = $wp_query->query["offset_resources"] ? $wp_query->query["offset_resources"] : 0;

  $relative_offset = $offset % $numberposts;

  $this_page = floor($offset / $numberposts) + 1 + $start_page;

  $total_start = $wp_query->found_posts - $relative_offset;
  $total_pages = ceil(($wp_query->found_posts - $relative_offset) / $numberposts);
  $offset = ($numberposts * ($this_page)) + ($relative_offset);
  if ($wp_query->query_vars["abs_offset"]) {
    $offset += $wp_query->query_vars["abs_offset"];
  }

  $next_url = $offset < $wp_query->found_posts ? site_url("retrieve/$next_root/{$numberposts}/{$offset}/") : false;

  if (is_tax()) {

    $taxonomy = get_query_var("taxonomy");
    $term = get_query_var("term");
    $next_url = $offset < $wp_query->found_posts ? site_url("retrieve/learn/{$taxonomy}/{$term}/{$numberposts}/{$offset}/") : false;
  }

  return $next_url;
}


// ?
// ---------------------------------------
// function resource_permalink_structure($post_link, $post, $leavename, $sample) {
//   if ( 'resources' != $post->post_type || 'publish' != $post->post_status ) {
//     return $post_link;
//   }

//   if (false !== strpos($post_link, '%resource-type%')) {
//       $term = get_the_terms($post->ID, 'resource-type');
//       if (!empty($term))
//           $post_link = str_replace('%resource-type%', array_pop($term)->slug, $post_link);
//       else
//           $post_link = str_replace('%resource-type%', 'uncategorized', $post_link);
//   }

//   $post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );

//   return $post_link;
// }
// add_filter('post_type_link', 'resource_permalink_structure', 10, 4);


// ?
// ---------------------------------------
// function q_parse_request( $query ) {
//   if ( ! $query->is_main_query() || ! isset( $query->query['resource-type'] ) ) {
//     return;
//   }

//   if ( ! empty( $query->query['name'] ) ) {
//     $query->set( 'post_type', array( 'post', 'resources', 'page' ) );
//   }
// }
// add_action( 'pre_get_posts', 'q_parse_request' );


// Resources rewrite rules
// ---------------------------------------

add_action('init',  function () {
  $terms = get_terms('resource-type');
  $learn_types_query = array("post_type[]=post");
  $learn_post_types = array("post", "blog", "articles", "case-studies", "ceo-blog", "ebooks", "guides", "news", "tech-talk", "product-updates", "webinars");

  if (function_exists('acf_add_options_page')) {
    $learn_post_types_option = get_field("learn_post_types", "option");
    $learn_post_types = $learn_post_types_option && count($learn_post_types_option) ? $learn_post_types_option : $learn_post_types;
  }

  add_rewrite_rule('learn/topic/([a-z0-9-]+)[/]?$', 'index.php?topic=$matches[1]&' . $learn_types_query_str);

  foreach ($learn_post_types as $type):

    add_rewrite_rule('retrieve/' . $type . '/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&post_type=' . $type . '&posts_per_page=$matches[1]&number_resources=$matches[1]&offset_resources=0', 'top');
    add_rewrite_rule('retrieve/' . $type . '/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&post_type=' . $type . '&number_resources=$matches[1]&offset_resources=$matches[2]', 'top');
    add_rewrite_rule('retrieve/' . $type . '/topic/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&post_type=' . $type . '&number_resources=$matches[2]&offset_resources=$matches[3]&topic=$matches[1]', 'top');
    add_rewrite_rule('retrieve/' . $type . '/industry/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&post_type=' . $type . '&number_resources=$matches[2]&offset_resources=$matches[3]&industry=$matches[1]', 'top');
    add_rewrite_rule('retrieve/' . $type . '/solution/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&post_type=' . $type . '&number_resources=$matches[2]&offset_resources=$matches[3]&solution=$matches[1]', 'top');

    $learn_types_query[] = "&post_type[]={$type}";

  endforeach;

  add_rewrite_rule('retrieve/news/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&post_type=news&posts_per_page=$matches[1]&number_resources=$matches[1]&offset_resources=0', 'top');
  add_rewrite_rule('retrieve/news/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&post_type=news&number_resources=$matches[1]&offset_resources=$matches[2]', 'top');

  $learn_types_query_str = implode("&", $learn_types_query);
  add_rewrite_rule('retrieve/learn/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&' . $learn_types_query_str . '&posts_per_page=$matches[1]&number_resources=$matches[1]&offset_resources=0', 'top');
  add_rewrite_rule('retrieve/learn/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&' . $learn_types_query_str . '&number_resources=$matches[1]&offset_resources=$matches[2]', 'top');
  add_rewrite_rule('retrieve/learn/topic/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&' . $learn_types_query_str . '&number_resources=$matches[2]&offset_resources=$matches[3]&topic=$matches[1]', 'top');
  add_rewrite_rule('retrieve/learn/industry/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&' . $learn_types_query_str . '&number_resources=$matches[2]&offset_resources=$matches[3]&industry=$matches[1]', 'top');
  add_rewrite_rule('retrieve/learn/solution/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&' . $learn_types_query_str . '&number_resources=$matches[2]&offset_resources=$matches[3]&solution=$matches[1]', 'top');


  //add_rewrite_rule( 'topic/([a-z0-9-]+)[/]?$', 'index.php?topic=$matches[1]&' . $learn_types_query_str );
  //add_rewrite_rule( 'topic/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?topic=$matches[1]&paged=$matches[2]&' . $learn_types_query_str );

  //add_rewrite_rule( 'articles/([a-z0-9-]+)[/]?$', 'index.php?post_type=post&pagename=[1]', 'top' );
  add_rewrite_rule('retrieve/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&post_type[]=' . implode("&post_type[]=", $learn_post_types) . '&posts_per_page=$matches[1]&number_resources=$matches[1]&offset_resources=0', 'top');
  add_rewrite_rule('retrieve/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?retrieve=1&post_type[]=' . implode("&post_type[]=", $learn_post_types) . '&number_resources=$matches[1]&offset_resources=$matches[2]', 'top');




  /*
    add_rewrite_rule( 'retrieve/posts/([a-z0-9-]+)[/]?$', 'index.php?post_type=post&posts_per_page=$matches[1]&number_resources=$matches[1]&offset_resources=0', 'top' );
    add_rewrite_rule( 'retrieve/posts/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?post_type=post&number_resources=$matches[1]&offset_resources=$matches[2]', 'top' );

    // Topic
    add_rewrite_rule( 'retrieve/posts/topic/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?post_type=post&posts_per_page=$matches[3]&number_resources=$matches[3]&offset_resources=0', 'top' );
    add_rewrite_rule( 'retrieve/posts/topic/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?post_type=post&number_resources=$matches[3]&offset_resources=$matches[4]', 'top' );

    // Solution
    add_rewrite_rule( 'retrieve/posts/solution/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?post_type=post&posts_per_page=$matches[3]&number_resources=$matches[3]&offset_resources=0', 'top' );
    add_rewrite_rule( 'retrieve/posts/solution/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?post_type=post&number_resources=$matches[3]&offset_resources=$matches[4]', 'top' );

    // Industry
    add_rewrite_rule( 'retrieve/posts/industry/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?post_type=post&posts_per_page=$matches[3]&number_resources=$matches[3]&offset_resources=0', 'top' );
    add_rewrite_rule( 'retrieve/posts/industry/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)[/]?$', 'index.php?post_type=post&number_resources=$matches[3]&offset_resources=$matches[4]', 'top' );
    */
});


/**
 * Display a custom taxonomy dropdown in admin
 * @author Mike Hemberger
 * @link http://thestizmedia.com/custom-post-type-filter-admin-custom-taxonomy/
 */
// add_action('restrict_manage_posts', 'tsm_filter_post_type_by_taxonomy');
// function tsm_filter_post_type_by_taxonomy() {
//   global $typenow;
//   $post_type = 'resources'; // change to your post type
//   $taxonomy  = 'resource-type'; // change to your taxonomy
//   if ($typenow == $post_type) {
//     $selected      = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
//     $info_taxonomy = get_taxonomy($taxonomy);
//     wp_dropdown_categories(array(
//       'show_option_all' => sprintf( __( 'Show all %s', 'textdomain' ), $info_taxonomy->label ),
//       'taxonomy'        => $taxonomy,
//       'name'            => $taxonomy,
//       'orderby'         => 'name',
//       'selected'        => $selected,
//       'show_count'      => true,
//       'hide_empty'      => true,
//     ));
//   };
// }

/**
 * Filter posts by taxonomy in admin
 * @author  Mike Hemberger
 * @link http://thestizmedia.com/custom-post-type-filter-admin-custom-taxonomy/
 */
// add_filter('parse_query', 'tsm_convert_id_to_term_in_query');
// function tsm_convert_id_to_term_in_query($query) {
//   global $pagenow;
//   $post_type = 'resources'; // change to your post type
//   $taxonomy  = 'resource-type'; // change to your taxonomy
//   $q_vars    = &$query->query_vars;
//   if ( $pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0 ) {
//     $term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
//     $q_vars[$taxonomy] = $term->slug;
//   }
// }


// Set post per page for Resources
// ---------------------------------------
// function q_resources_posts_per_page( $query ) {
//   //print_r($query);
//   if ( is_admin() || ! $query->is_main_query() ) {
//      return;
//   }

//   if ( is_post_type_archive( 'resources' ) ) {
//     //print_r($query);

//     if(isset($query->query["number_resources"])) {
//       $query->set( 'posts_per_page', $query->query["number_resources"] );
//     }else{
//       $query->set( 'posts_per_page', 12 );
//     }

//     if(isset($query->query["offset_resources"])) {
//       $query->set( 'offset', $query->query["offset_resources"] );
//     }

//   }
// }
// add_filter( 'pre_get_posts', 'q_resources_posts_per_page' );

add_action('pre_get_posts', 'alter_query', 10);

function alter_query($query)
{

  if (is_admin() || ! $query->is_main_query()) {
    return;
  }

  global $wp_query;
  $is_retrieval = array_key_exists("retrieve", $wp_query->query_vars);

  //print_r($query);
  if (array_key_exists("number_resources", $query->query_vars)) {
    $query->set('posts_per_page', $query->query_vars["number_resources"]);
    $query->set('numberposts', $query->query_vars["number_resources"]);
    $query->set('ignore_sticky_posts', true);
  }

  if (array_key_exists("offset_resources", $query->query_vars)) {
    $query->set('offset', $query->query_vars["offset_resources"]);
    $query->set('ignore_sticky_posts', true);
  }

  if (is_search()) {
    $query->set('numberposts', 18); // AK22 - Archive Page Number
    $query->set('posts_per_page', 18); // AK22 - Archive Page Number
  }
  // Below added merch mix archive exception AK-21
  if ((is_archive() && !is_post_type_archive('merchandisers-mix') || $wp_query->is_posts_page || is_search()) && !$is_retrieval) {

    $term               = get_queried_object();
    $term_name          = isset($term->name) ? $term->name : "post";

    // For empty searches
    if (
      is_search() &&
      isset($wp_query->query["post_type"])
    ) {

      //  Search fields
      $search_fields = array("s", "topic", "industry", "solution");
      $search_field_count = 0;
      foreach ($search_fields as $search_field) {
        if (
          isset($wp_query->query[$search_field]) &&
          $wp_query->query[$search_field] !== 0 &&
          !empty($wp_query->query[$search_field])
        ) {
          $search_field_count++;
        }
      }

      if ($search_field_count === 0) {
        $term_name = $wp_query->query["post_type"];
      }
    }

    $featured_posts     = get_field('featured_post', $term);

    if (!$featured_posts) {
      $featured_posts     = get_field("{$term_name}__featured_post", "option");
      if ($wp_query->is_posts_page) {
        $featured_posts     = get_field("post__featured_post", "option");
      }
    }

    $featured_post      = is_array($featured_posts) && count($featured_posts) ? $featured_posts[0] : null;

    $posts_per_page = 9;
    //  $posts_per_page = is_search() ? 15 : 12; // AK22 - Archive Page Number
    //  
    // //add exception for partner directiry archive to chow 9 items AK-22
    //    if( is_post_type_archive( 'partner-directory' )) {
    //      $posts_per_page = $posts_per_page - 6; // AK22 - Archive Page Number
    //    }


    if (!is_search()) {
      if (!is_post_type_archive('partner-directory')) {
        if ($featured_post || $wp_query->query_vars["paged"] == 0) {
          $posts_per_page = $posts_per_page + 1; // AK22 - Archive Page Number
        } else {
          $query->set('offset', $posts_per_page * (intval($wp_query->query_vars["paged"]) - 1) + 1);
        }
      } else {
        $query->set('abs_offset', -2); // AK22 - Archive Page Number
      }
    }

    if ($featured_post && !is_search()) {
      $query->set('post__not_in', array($featured_post->ID));
    }

    $query->set('numberposts', $posts_per_page);
    $query->set('posts_per_page', $posts_per_page);
  }

  //$query->set('posts_per_page', 1);
  //$query->set('numberposts', 1);

  //print_r($query);

}

add_filter('http_request_host_is_external', '__return_true');

if (function_exists('acf_add_options_page')) {

  acf_add_options_sub_page(array(
    'page_title'     => 'Case Studies Options',
    'menu_title'    => 'Case Studies Options',
    'parent_slug'    => 'edit.php?post_type=case-studies',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'Articles Options',
    'menu_title'    => 'Articles Options',
    'parent_slug'    => 'edit.php',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'News & Events Options',
    'menu_title'    => 'News & Events Options',
    'parent_slug'    => 'edit.php?post_type=news',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'Guides Options',
    'menu_title'    => 'Guides Options',
    'parent_slug'    => 'edit.php?post_type=guides',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'Webinars Options',
    'menu_title'    => 'Webinar Options',
    'parent_slug'    => 'edit.php?post_type=webinars',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'Tech Talk Options',
    'menu_title'    => 'Tech Talk Options',
    'parent_slug'    => 'edit.php?post_type=tech-talk',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'Ebooks Options',
    'menu_title'    => 'Ebooks Options',
    'parent_slug'    => 'edit.php?post_type=ebooks',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'Product Updates Options',
    'menu_title'    => 'Product Updates Options',
    'parent_slug'    => 'edit.php?post_type=product-updates',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'CEO Blog Options',
    'menu_title'     => 'CEO Blog Options',
    'parent_slug'    => 'edit.php?post_type=ceo-blog',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'Partner Directory Options',
    'menu_title'     => 'Partner Directory Options',
    'parent_slug'    => 'edit.php?post_type=partner-directory',
  ));

  acf_add_options_sub_page(array(
    'page_title'     => 'Academy Options',
    'menu_title'     => 'Academy Options',
    'parent_slug'    => 'edit.php?post_type=academy',
  ));
}


// Get featured image, with support for a fallback featured image, and a legacy featured image
// ---------------------------------------
function searchspring_get_featured_image($post_id)
{

  $featured_image = get_stylesheet_directory_uri() . "/dist/images/default-featured-image.png";

  if ($thumb_id = get_post_thumbnail_id($post_id)) {
    $thumb_url_array = wp_get_attachment_image_src($thumb_id, 'thumbnail-size', true);
    $featured_image = $thumb_url_array[0];
  }

  return $featured_image;
}

function searchspring_get_hero_image($post_id)
{

  $featured_image = get_stylesheet_directory_uri() . "/dist/images/default-featured-image.png";

  if ($thumb_id = get_post_thumbnail_id($post_id)) {
    $thumb_url_array = wp_get_attachment_image_src($thumb_id, 'thumbnail-size', true);
    $featured_image = $thumb_url_array[0];
  }

  $resource_featured_image = get_field("resource_hero_image", $post_id);
  $featured_image =  $resource_featured_image ? $resource_featured_image["url"] : $featured_image;


  return $featured_image;
}

remove_filter('template_redirect', 'redirect_canonical');





// Return the excerpt
// ---------------------------------------

function searchspring_custom_excerpt_length($length)
{
  return 23;
}
add_filter('excerpt_length', 'searchspring_custom_excerpt_length', 999);

function searchspring_excerpt_more($more)
{
  return '&hellip;';
}
add_filter('excerpt_more', 'searchspring_excerpt_more');

function q_excerpt($post)
{

  if (is_string($post)) {
    return $post;
  }

  if (is_int($post)) {
    $post = get_post($post);
  }

  $excerpt = $post->post_excerpt;

  if (empty($excerpt)) {
    $excerpt = wp_trim_excerpt("", $post);
    return strip_tags($excerpt);
  }

  if ($post->post_type == "page" && empty($excerpt)):
    $lines = array();
    if ($hero_text = get_field("text", $post->ID)):
      $lines[] = $hero_text["pre-heading_text"] . " |";
      $lines[] = $hero_text["hero_heading"];
      // if($hero_text["add_secondary_heading"]):
      //   $lines[] = $hero_text["pre-secondary-heading_text"];
      //   $lines[] = $hero_text["hero_secondary_heading"];
      // endif;
      $lines[] = $hero_text["hero_copy"];
      $excerpt = implode(" ", $lines);
    endif;

  elseif (empty($post->post_excerpt)):
    $excerpt = wp_trim_excerpt("", $post);
  endif;

  return strip_tags($excerpt);
}
add_filter('get_the_excerpt', 'q_excerpt');

add_filter('body_class', function ($classes) {
  $new_classes = array();
  if (is_single() && get_field("is_gated")) {
    $new_classes[] = "single--gated";
  } else {
    $new_classes[] = "single--open";
  }
  return array_merge($classes, $new_classes);
});

// Search redirect?
// ---------------------------------------
// function q_change_search_url_rewrite() {
//   if ( is_search() && ! empty( $_GET['s'] ) ) {
//       wp_redirect( home_url( "/resources/search/" ) . urlencode( get_query_var( 's' ) ) );
//       exit();
//   }   
// }
// add_action( 'template_redirect', 'q_change_search_url_rewrite' );

function new_excerpt_more($more)
{
  return "...";
}
add_filter('excerpt_more', 'new_excerpt_more', 11);


add_action('rest_endpoints', function ($endpoints) {
  if (isset($endpoints['wp/v2/dashboard-updates'])) {
    foreach ($endpoints['wp/v2/dashboard-updates'] as &$post_endpoint) {
      print_r($post_endpoint);
    }
  }
  return $endpoints;
}, 15);



add_filter('json_prepare_post', 'json_api_encode_acf');

function json_api_encode_acf($post)
{

  $acf = get_fields($post['ID']);

  if (isset($post)) {
    $post['acf'] = $acf;
  }

  return $post;
}

function searchspring_get_learn_types()
{

  $learn_post_types = array("post", "blog", "articles", "case-studies", "ceo-blog", "ebooks", "guides", "news", "tech-talk", "product-updates", "webinars");

  if (function_exists('acf_add_options_page')) {
    $learn_post_types_option = get_field("learn_post_types", "option");
    $learn_post_types = $learn_post_types_option && count($learn_post_types_option) ? $learn_post_types_option : $learn_post_types;
  }

  return $learn_post_types;
}

// sitemap.xml excludes

/* Exclude Multiple Content Types From Yoast SEO Sitemap */
add_filter('wpseo_sitemap_exclude_post_type', 'sitemap_exclude_post_type', 10, 2);
function sitemap_exclude_post_type($value, $post_type)
{
  $post_type_to_exclude = array('module_library', 'hidden');
  if (in_array($post_type, $post_type_to_exclude)) return true;
}

/* Exclude Multiple Taxonomies From Yoast SEO Sitemap */
add_filter('wpseo_sitemap_exclude_taxonomy', 'sitemap_exclude_taxonomy', 10, 2);
function sitemap_exclude_taxonomy($value, $taxonomy)
{
  $taxonomy_to_exclude = array('module_library_types', 'tag', 'post_tag');
  if (in_array($taxonomy, $taxonomy_to_exclude)) return true;
}

/*
if (!isset($has_fixed_featured_post_on_first_page))
{
    $has_fixed_featured_post_on_first_page = false;
}

        add_filter('posts_request', function($request,$wp_query) {
        global $has_fixed_featured_post_on_first_page;
        global $template;        
//            var_dump($request);
            //if (strpos($request, "wp_posts.post_type = 'webinars'") !== false)
if (basename($template) == "archive.php")
{
    echo "Ffffffffffff";
}
            
            if (isset($has_fixed_featured_post_on_first_page) && $has_fixed_featured_post_on_first_page)
            {
                $pos = strpos($request,"LIMIT");
                if ($pos !== false)
                {
                    $pom = explode(",",trim(substr($request,$pos+5)));
                    if (count($pom) == 2)
                    {
                        $pom[0]=intval($pom[0]);
                        if ($pom[0]>0)
                        {
                            $pom[0]++;
                        }
                        $request = substr($request,0,$pos)." LIMIT ".$pom[0].",".$pom[1];
//                        var_dump($request);
                    }
                }            
            }      
//            echo "<PRE>";
//            var_dump($query);
            return $request;
        },99999,2);                
*/