<?php

/**
 * Template Name: Language Translation
 * @package WordPress
 */

get_header();
?>

<?php
$lang = isset($_GET['lang']) && $_GET['lang'] === 'fr' ? 'fr' : 'en';
?>


<section class="asklo-hero">
  <div class="scrn-container">
    <div class="hero-content">
      <div class="hero-text">

        <div class="hero-subtitle">
          <?php the_field("hero_subtitle_$lang"); ?>
        </div>

        <h1 class="hero-title">
          <?php
          $title_part1 = the_field("hero_title_$lang", false, false);
          $highlight = '<span class="highlight">' . ($lang === 'fr' ? 'Asklo assistant IA' : 'Asklo AI assistant') . '</span>';
          echo sprintf($title_part1, $highlight);
          ?>
        </h1>

        <p class="hero-description">
          <?php the_field("hero_description_$lang"); ?>
        </p>

        <div class="hero-buttons">
          <a href="#" class="start-free-btn">
            <?php the_field("hero_cta_text_$lang"); ?>
          </a>

          <a href="#" class="shopify-btn">
            <img src="/wp-content/uploads/2025/03/Group-7003.png" alt="<?php the_field("hero_shopify_alt_$lang"); ?>">
          </a>
        </div>

      </div>

      <div class="hero-image">
        <img src="/wp-content/uploads/2025/03/Group-7109.png" alt="<?php the_field("hero_image_alt_$lang"); ?>">
      </div>
    </div>
  </div>
</section>