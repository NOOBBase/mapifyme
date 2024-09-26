<div class="popup-template-1">
  <!-- Display post title -->
  <?php if (!empty($fields['title'])): ?>
    <h4 class="popup-title"><?php echo esc_html($fields['title']); ?></h4>
  <?php endif; ?>

  <!-- Display post content -->
  <?php if (!empty($fields['content'])): ?>
    <div class="popup-content">
      <?php echo wp_kses_post($fields['content']); // Properly escapes content with allowed HTML 
      ?>
    </div>
  <?php endif; ?>


  <!-- Display post address -->
  <?php if (!empty($fields['address'])): ?>
    <p class="popup-address"><strong>Address:</strong> <?php echo esc_html($fields['address']); ?></p>
  <?php endif; ?>

  <!-- Display post phone -->
  <?php if (!empty($fields['phone'])): ?>
    <p class="popup-phone"><strong>Phone:</strong> <?php echo esc_html($fields['phone']); ?></p>
  <?php endif; ?>

  <!-- Display post website -->
  <?php if (!empty($fields['website'])): ?>
    <p class="popup-website"><strong>Website:</strong> <a href="<?php echo esc_url($fields['website']); ?>" target="_blank"><?php echo esc_html($fields['website']); ?></a></p>
  <?php endif; ?>

  <!-- Display post category -->
  <?php if (!empty($fields['category'])): ?>
    <p class="popup-category"><strong>Category:</strong> <?php echo esc_html($fields['category']); ?></p>
  <?php endif; ?>

  <!-- Display post tags -->
  <?php if (!empty($fields['tags'])): ?>
    <p class="popup-tags"><strong>Tags:</strong> <?php echo esc_html($fields['tags']); ?></p>
  <?php endif; ?>

  <!-- Display post author -->
  <?php if (!empty($fields['author'])): ?>
    <p class="popup-author"><strong>Author:</strong> <?php echo esc_html($fields['author']); ?></p>
  <?php endif; ?>
</div>