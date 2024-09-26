<?php
// Prevent direct access
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Maps
{
  public function __construct()
  {
    // Enqueue map processor scripts on the frontend
    add_action('wp_enqueue_scripts', array($this, 'enqueue_map_processor_scripts'));
  }

  /**
   * Enqueue the map processor scripts for individual instances.
   *
   * @param string $map_container_id The ID of the map container.
   * @param string $popup_content The content for the popup.
   * @param float $latitude Latitude for the map center.
   * @param float $longitude Longitude for the map center.
   * @param bool $enable_geolocation Enable or disable geolocation.
   * @param bool|null $showGetLocationButton (Optional) Show or hide the "Get Current Location" button.
   * @param string|null $override_map_provider Optionally override the map provider for this instance only.
   * @param int $zoom_level The zoom level for the map (optional).
   */
  public function enqueue_map_processor_scripts($map_container_id = '', $popup_content = '', $latitude = 0, $longitude = 0, $enable_geolocation = false, $showGetLocationButton = null, $override_map_provider = null, $zoom_level = 13)
  {
    // Fetch the selected map provider from the database if no override is passed.
    // Use $override_map_provider only for this specific instance, fallback to the global setting otherwise.
    $map_provider = !empty($override_map_provider) ? $override_map_provider : MapifyMe_DB::get_setting('map_provider', 'leaflet');

    // If showGetLocationButton is null, default to false
    $showGetLocationButton = is_null($showGetLocationButton) ? false : $showGetLocationButton;

    // For Google Maps
    if ($map_provider === 'google_maps') {
      $this->enqueue_google_maps($map_container_id, $popup_content, $latitude, $longitude, $enable_geolocation, $showGetLocationButton, $zoom_level);
    }
    // For Leaflet Maps
    elseif ($map_provider === 'leaflet') {
      $this->enqueue_leaflet_maps($map_container_id, $popup_content, $latitude, $longitude, $enable_geolocation, $showGetLocationButton, $zoom_level);
    }
  }

  /**
   * Enqueue Google Maps scripts.
   */
  private function enqueue_google_maps($map_container_id, $popup_content, $latitude, $longitude, $enable_geolocation, $showGetLocationButton, $zoom_level)
  {
    // Load Google Maps scripts
    $google_maps_api_key = MapifyMe_DB::get_setting('google_maps_api_key', '');
    if (!empty($google_maps_api_key)) {
      wp_enqueue_script(
        'google-maps-js',
        'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($google_maps_api_key),
        array(),
        '3',
        true
      );

      // Enqueue the custom Google Maps initialization script
      wp_enqueue_script(
        'mapifyme-google-map',
        MAPIFYME_PLUGIN_URL . 'assets/js/google-map.js',
        array('google-maps-js'),
        filemtime(MAPIFYME_PLUGIN_DIR . 'assets/js/google-map.js'),
        true
      );

      // Render the popup template
      $popup_template = $this->render_google_maps_popup_template();
      // Ensure popup_content is a string
      $safe_popup_content = is_string($popup_content) ? wp_kses_post($popup_content) : wp_json_encode($popup_content);


      // Add inline script to initialize Google Maps
      wp_add_inline_script(
        'mapifyme-google-map',
        "
    document.addEventListener('DOMContentLoaded', function() {
      initializeGoogleMap(
        '" . esc_js($map_container_id) . "',
        " . esc_js($latitude) . ",
        " . esc_js($longitude) . ",
        " . wp_json_encode($popup_content) . ",
        " . ($enable_geolocation ? 'true' : 'false') . ",
        " . ($showGetLocationButton ? 'true' : 'false') . ",
        " . esc_js($zoom_level) . "
      );
    });
    "
      );
    }
  }

  /**
   * Enqueue Leaflet Maps scripts.
   */
  private function enqueue_leaflet_maps($map_container_id, $popup_content, $latitude, $longitude, $enable_geolocation, $showGetLocationButton, $zoom_level)
  {
    // Enqueue Leaflet scripts
    wp_enqueue_script(
      'leaflet-js',
      MAPIFYME_PLUGIN_URL . 'assets/lib/leaflet/leaflet.js',
      array(),
      filemtime(MAPIFYME_PLUGIN_DIR . 'assets/lib/leaflet/leaflet.js'),
      true
    );
    wp_enqueue_style(
      'leaflet-css',
      MAPIFYME_PLUGIN_URL . 'assets/lib/leaflet/leaflet.css',
      array(),
      filemtime(MAPIFYME_PLUGIN_DIR . 'assets/lib/leaflet/leaflet.css')
    );

    // Enqueue the interaction scripts
    wp_enqueue_script(
      'mapifyme-geolocation',
      MAPIFYME_PLUGIN_URL . 'assets/js/mapifyme-geolocation.js',
      array('leaflet-js', 'jquery'),
      filemtime(MAPIFYME_PLUGIN_DIR . 'assets/js/mapifyme-geolocation.js'),
      true
    );

    wp_enqueue_script(
      'mapifyme-leaflet',
      MAPIFYME_PLUGIN_URL . 'assets/js/leaflet-map.js',
      array('leaflet-js', 'jquery'),
      filemtime(MAPIFYME_PLUGIN_DIR . 'assets/js/leaflet-map.js'),
      true
    );

    // Render the custom popup template for Leaflet
    $popup_template = $this->render_popup_template();

    // Localize data for the Leaflet map
    $localized_data = array(
      'reverse_geocode_api_url' => 'https://nominatim.openstreetmap.org/reverse',
      'ajax_url' => admin_url('admin-ajax.php'),
      'you_are_here' => __('You are here!', 'mapifyme'),
      'address_label' => __('Address', 'mapifyme'),
      'location_error' => __('Unable to retrieve your location.', 'mapifyme'),
      'geolocation_not_supported' => __('Geolocation is not supported by your browser.', 'mapifyme'),
      'custom_popup_template' => $popup_template,
      'zoom_level' => $zoom_level,
      'show_get_location_button' => $showGetLocationButton
    );

    wp_localize_script('mapifyme-geolocation', 'mapifymeGeotag', $localized_data);
  }

  /**
   * Render the custom popup template for Leaflet.
   */
  private function render_popup_template()
  {
    ob_start();
    include MAPIFYME_PLUGIN_DIR . 'assets/templates/current-location/popup-template1.php';
    return wp_kses_post(ob_get_clean());
  }

  /**
   * Render the custom popup template for Google Maps.
   */
  private function render_google_maps_popup_template()
  {
    ob_start();
    include MAPIFYME_PLUGIN_DIR . 'assets/templates/current-location/popup-template1.php';
    return wp_kses_post(ob_get_clean());
  }
}
