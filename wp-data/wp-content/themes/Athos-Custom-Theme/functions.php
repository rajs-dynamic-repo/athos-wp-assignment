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
  wp_enqueue_style('contactUs-style', get_stylesheet_directory_uri() . '/assets/css/contact-us.css');
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles');


// This Function is to Process the form submissions
function process_custom_form()
{
  if (isset($_POST['custom_form_submit'])) {
    $to = 'rajdelegend@gmail.com';
    $subject = 'New Audit Request from Website';

    $body = "First Name: " . sanitize_text_field($_POST['firstName']) . "\n";
    $body .= "Last Name: " . sanitize_text_field($_POST['lastName']) . "\n";
    $body .= "Email: " . sanitize_email($_POST['email']) . "\n";
    $body .= "Phone: " . sanitize_text_field($_POST['phone']) . "\n";
    $body .= "Company: " . sanitize_text_field($_POST['company']) . "\n";
    $body .= "Website: " . esc_url_raw($_POST['website']) . "\n";

    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($to, $subject, $body, $headers);

    // Redirect to same page with success parameter
    wp_redirect(add_query_arg('form_success', 'true', wp_get_referer()));
    exit;
  }
}
add_action('init', 'process_custom_form');

// Setting up function to register polylang translations,
// this is used to translate the custom template strings.
if (function_exists('pll_register_string')) {
  pll_register_string('asklo_hero_subtitle', 'Asklo: Product Questions & Answers', 'Athos Commerce');
  pll_register_string('asklo_hero_title', 'Turn visitors into shoppers with Asklo AI assistant', 'Athos Commerce');
  pll_register_string('asklo_hero_description', 'Asklo AI assistant resolves your customers\' queries on product pages to engage and convert them faster', 'Athos Commerce');
  pll_register_string('asklo_start_free', 'START FREE', 'Athos Commerce');
  pll_register_string('asklo_shopify_alt', 'Find it on the Shopify App Store', 'Athos Commerce');
  pll_register_string('asklo_demo_alt', 'Asklo AI Assistant Demo', 'Athos Commerce');
}
