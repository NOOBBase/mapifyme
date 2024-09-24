<?php
// wp-content/plugins/mapifyme/assets/templates/current-location/popup-template1.php

/**
 * Custom Popup Template for Leaflet Map
 * 
 * This template includes placeholders that will be replaced with actual data.
 * Ensure that the placeholders match those used in the JavaScript code.
 */
?>
<div class="custom-popup">
  <h3><?php echo esc_html__('You are here!', 'mapifyme'); ?></h3>
  <p>
    <strong><?php echo esc_html__('Latitude:', 'mapifyme'); ?></strong> {latitude}<br>
    <strong><?php echo esc_html__('Longitude:', 'mapifyme'); ?></strong> {longitude}<br>
    <strong><?php echo esc_html__('Address:', 'mapifyme'); ?></strong> {address}
  </p>
</div>