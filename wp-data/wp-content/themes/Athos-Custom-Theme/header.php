<?php

/**
 * Header template for the WordPress theme.
 * 
 * This file loads the <head> section and opening <html> structure.
 * It includes meta tags, stylesheets, and WordPress hooks.
 *
 * 
 * @package WordPress
 * @subpackage Athos-Custom-Theme
 * 
 */
?>

<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Athos a merchandising, and personalization platform built exclusively for ecommerce">
  <link rel="canonical" href="https://yourwebsite.com/current-page-url">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <title><?php wp_title('|', true, 'right'); ?> <?php bloginfo('name'); ?></title>

  <?php wp_head(); ?>
</head>

<body>
  <header>
    <nav class="main-nav" id="athos-mainNav" role="navigation">
      <div class="scrn-container">
        <ul class='nav-bar' id='nav-bar'>
          <li class='logo'><a href='#'><img src='/wp-content/uploads/2025/03/Layer_1-2.png' /></a></li>
          <input type='checkbox' id='check' />
          <span class="menu">
            <li><a href="#">Features</a></li>
            <li><a href="#">Integration</a></li>
            <li><a href="#">Plans</a></li>
            <li><a href="#">Blog</a></li>
            <li><a href="#">FAQs</a></li>
            <label for="check" class="close-menu" aria-label="Close Menu"><i class="fas fa-times"></i></label>
          </span>
          <li class="nav-buttons">
            <a href="#" class="btn secondary">Login</a>
            <a href="#" class="btn primary">START FREE</a>
          </li>
          <label for="check" class="open-menu" aria-label="Open Menu"><i class="fas fa-bars"></i></label>
        </ul>
      </div>
    </nav>
  </header>
</body>