<?php
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Maps
{
  public function __construct()
  {
    // Enqueue the correct map processor scripts (Leaflet or Google Maps) on frontend
    add_action('wp_enqueue_scripts', array($this, 'enqueue_map_processor_scripts'));
  }

  // Enqueue the correct map processor (Leaflet or Google Maps)
  public function enqueue_map_processor_scripts()
  {
    // Fetch the selected map provider from the database
    $map_provider = MapifyMe_DB::get_setting('map_provider', 'leaflet'); // Default to 'leaflet' if not set

    if ($map_provider === 'leaflet') {
      // Load local Leaflet scripts and styles with dynamic versioning based on file modification time
      $leaflet_js_path = MAPIFYME_PLUGIN_DIR . 'assets/lib/leaflet/leaflet.js';
      $leaflet_css_path = MAPIFYME_PLUGIN_DIR . 'assets/lib/leaflet/leaflet.css';

      wp_enqueue_script(
        'leaflet-js',
        MAPIFYME_PLUGIN_URL . 'assets/lib/leaflet/leaflet.js',
        array(),
        filemtime($leaflet_js_path),
        true
      );
      wp_enqueue_style(
        'leaflet-css',
        MAPIFYME_PLUGIN_URL . 'assets/lib/leaflet/leaflet.css',
        array(),
        filemtime($leaflet_css_path)
      );

      // Custom JS for Leaflet map interaction with dynamic version based on file modification time
      $leaflet_map_js_path = MAPIFYME_PLUGIN_DIR . 'assets/js/leaflet-map.js';
      wp_enqueue_script(
        'mapifyme-leaflet',
        MAPIFYME_PLUGIN_URL . 'assets/js/leaflet-map.js',
        array('leaflet-js'),
        filemtime($leaflet_map_js_path),
        true
      );
    } elseif ($map_provider === 'google_maps') {
      // Load Google Maps API with your API key
      $google_maps_api_key = MapifyMe_DB::get_setting('google_maps_api_key', '');
      if (!empty($google_maps_api_key)) {
        wp_enqueue_script(
          'google-maps-js',
          'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($google_maps_api_key),
          array(),
          '3',
          true
        );

        // Custom JS for Google Maps map interaction with dynamic version based on file modification time
        $google_map_js_path = MAPIFYME_PLUGIN_DIR . 'assets/js/google-maps.js';
        wp_enqueue_script(
          'mapifyme-google-maps',
          MAPIFYME_PLUGIN_URL . 'assets/js/google-maps.js',
          array('google-maps-js'),
          filemtime($google_map_js_path),
          true
        );
      }
    }
  }
}
