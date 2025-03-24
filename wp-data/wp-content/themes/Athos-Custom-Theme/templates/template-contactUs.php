<?php

/**
 * Template Name: Contact Us
 * @package WordPress
 */

get_header();
?>
<!-- Contact Us Section -->
<!-- Contact Us Section -->
<section class="contact-us-section">
  <div class="scrn-container-small">
    <div class="contact-header">
      <h1>Contact Us</h1>
      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
    </div>

    <div class="contact-content">
      <div class="left-content">
        <h2>See how Asklo works</h2>
        <p>Experience a live demo created especially for you and find out why Asklo is the right choice for your ecommerce site's unique needs.</p>

        <div class="testimonial-box">
          <p class="testimonial-text">Integrating Asklo into our product detail pages has been a game-changer. The seamless interaction gives customers a way to ask questions that is far quicker than email and available 24/7/365.</p>

          <div class="testimonial-author">
            <div class="author-avatar">
              <img src="/wp-content/uploads/2025/03/Group-7020.png" alt="Mark Castiglione">
            </div>
            <div class="author-info">
              <h4>Mark Castiglione</h4>
              <p>Director of eCommerce Operations, TravelPro</p>
            </div>
          </div>
        </div>
      </div>

      <div class="right-content">
        <?php if (isset($_GET['form_success']) && $_GET['form_success'] == 'true'): ?>
          <!-- Success message -->
          <div class="success-message" style="background-color: #e0f0e0; border: 1px solid #006600; color: #006600; padding: 30px; border-radius: 15px; text-align: center; font-size: 18px; line-height: 1.6;">
            <p>Thanks for filling the form, we will get back to you shortly 😊</p>
          </div>
        <?php else: ?>
          <!-- Contact form -->
          <form class="contact-form" method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
            <input type="text" name="firstName" placeholder="First name*" class="form-input" required>
            <input type="text" name="lastName" placeholder="Last name*" class="form-input" required>
            <input type="email" name="email" placeholder="Work Email*" class="form-input" required>
            <input type="tel" name="phone" placeholder="Phone number*" class="form-input" required>
            <input type="text" name="company" placeholder="Company Name*" class="form-input" required>
            <input type="text" name="website" placeholder="Website URL*" class="form-input" required>

            <p class="form-disclaimer">
              Asklo Team needs the contact information you provide to us to contact you about our products and services. You may unsubscribe from these communications at any time. For information on how to unsubscribe, as well as our privacy practices and commitment to protecting your privacy, please review our Privacy Policy.
            </p>

            <div class="recaptcha-container">
              <!-- Google reCAPTCHA, We can enable this by configuring through our Gmail account -->
              <?php if (function_exists('recaptcha_get_html')) echo recaptcha_get_html(); ?>
            </div>

            <input type="hidden" name="custom_form_submit" value="1">
            <button type="submit" class="submit-button">Request an audit</button>
          </form>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<!-- Askio CTA Section  -->
<section class="askio-cta-section">
  <div class="cta-container">
    <div class="cta-content">
      <h2 class="cta-title">Get <span class="highlight">Askio AI</span> to instantly answer customers questions</h2>
      <p class="cta-text">Start using Askio AI for free and create an engaging experience for your shoppers on product pages.</p>
      <a href="#" class="cta-button">START FREE</a>
    </div>
  </div>
  <div class="cta-image-container">
    <img src="/wp-content/uploads/2025/03/Group-3.webp" alt="Askio AI in action" class="cta-image">
  </div>
</section>

<!-- Form Script for Success Message -->
<script>
  document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    document.getElementById('contactForm').style.display = 'none';
    document.getElementById('successMessage').style.display = 'flex';
  });
</script>

<?php get_footer(); ?>