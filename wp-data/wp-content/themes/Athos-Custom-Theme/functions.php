<?php

/**
 *
 * Theme Functions
 * Sets up theme defaults and registers support for various WordPress features.
 * 
 * @package WordPress
 * @subpackage Athos-Custom-Theme
 * 
 */


// Following function sets the Content Security Policy (CSP) headers for enhanced security. *
function custom_csp_headers()
{
  header("Content-Security-Policy: script-src 'self' 'strict-dynamic' 'unsafe-inline' https:; script-src-elem 'self' 'unsafe-inline' https:; object-src 'none'; base-uri 'none';");
}
add_action('send_headers', 'custom_csp_headers');


//Following function register template selection dropwdown to wp-dashboard
function register_custom_templates($post_templates, $wp_theme, $post)
{
  $post_templates['template-homepage.php'] = __('Homepage Custom Template', 'your-textdomain');
  return $post_templates;
}
add_filter('theme_page_templates', 'register_custom_templates', 10, 3);


// Enqueue header and footer styles from the CSS folder
function enqueue_custom_styles()
{
  wp_enqueue_style('global-style', get_stylesheet_directory_uri() . '/assets/css/global.css');
  wp_enqueue_style('header-style', get_stylesheet_directory_uri() . '/assets/css/header.css');
  wp_enqueue_style('footer-style', get_stylesheet_directory_uri() . '/assets/css/footer.css');
  wp_enqueue_style('homepage-style', get_stylesheet_directory_uri() . '/assets/css/homepage.css');
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles');
