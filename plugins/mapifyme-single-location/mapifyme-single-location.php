<?php

/**
 * MapifyMe Single Location Module Loader
 *
 * This file loads the Single Location widget and shortcode for the MapifyMe plugin.
 */

// Prevent direct access
if (!defined('WPINC')) {
  die;
}

// Define the path to the Single Location module directory
define('MAPIFYME_SINGLE_LOCATION_DIR', plugin_dir_path(__FILE__));

// Include the widget and shortcode classes
require_once MAPIFYME_SINGLE_LOCATION_DIR . 'class-mapifyme-single-location-widget.php';
require_once MAPIFYME_SINGLE_LOCATION_DIR . 'class-mapifyme-single-location-shortcode.php';

// Initialize the widget
function mapifyme_single_location_register_widget()
{
  register_widget('MapifyMe_Single_Location_Widget');
}
add_action('widgets_init', 'mapifyme_single_location_register_widget');

// Initialize the shortcode
function mapifyme_single_location_register_shortcode()
{
  $mapifyme_single_location_shortcode = new MapifyMe_Single_Location_Shortcode();
}
add_action('init', 'mapifyme_single_location_register_shortcode');
