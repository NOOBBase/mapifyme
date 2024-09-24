<?php
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

  public function enqueue_map_processor_scripts()
  {
    // Fetch the selected map provider
    $map_provider = MapifyMe_DB::get_setting('map_provider', 'leaflet');

    if ($map_provider === 'leaflet') {
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

      // Enqueue map interaction scripts with jQuery dependency
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

      // Render the custom popup template
      $popup_template = $this->render_popup_template();

      // Prepare localized data
      $reverse_geocode_url = 'https://nominatim.openstreetmap.org/reverse';

      $localized_data = array(
        'reverse_geocode_api_url'   => $reverse_geocode_url,
        'ajax_url'                  => admin_url('admin-ajax.php'),
        'you_are_here'              => __('You are here!', 'mapifyme'),
        'address_label'             => __('Address', 'mapifyme'),
        'location_error'            => __('Unable to retrieve your location.', 'mapifyme'),
        'geolocation_not_supported' => __('Geolocation is not supported by your browser.', 'mapifyme'),
        'custom_popup_template'     => $popup_template, // Pass the rendered template
      );

      // Localize script for mapifyme-geolocation.js
      wp_localize_script('mapifyme-geolocation', 'mapifymeGeotag', $localized_data);

      // Localize script for leaflet-map.js (ensure it uses the same object)
      wp_localize_script('mapifyme-leaflet', 'mapifymeGeotag', array(
        'reverse_geocode_api_url'   => $reverse_geocode_url,
        'ajax_url'                  => admin_url('admin-ajax.php'),
        'custom_popup_template'     => $popup_template,
        'you_are_here'              => __('You are here!', 'mapifyme'),
        'address_label'             => __('Address', 'mapifyme'),
      ));
    } elseif ($map_provider === 'google_maps') {
      // Handle Google Maps script loading
      $google_maps_api_key = MapifyMe_DB::get_setting('google_maps_api_key', '');
      if (!empty($google_maps_api_key)) {
        wp_enqueue_script(
          'google-maps-js',
          'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($google_maps_api_key),
          array(),
          '3',
          true
        );

        wp_enqueue_script(
          'mapifyme-google-maps',
          MAPIFYME_PLUGIN_URL . 'assets/js/google-maps.js',
          array('google-maps-js', 'jquery'),
          filemtime(MAPIFYME_PLUGIN_DIR . 'assets/js/google-maps.js'),
          true
        );
      }
    }
  }

  /**
   * Render the custom popup template.
   *
   * @return string Rendered HTML of the popup template.
   */
  private function render_popup_template()
  {
    // Start output buffering
    ob_start();

    /**
     * If your template requires dynamic data, prepare it here.
     * For example:
     * $user_name = wp_get_current_user()->display_name;
     * $location = 'Sample Location'; // Replace with actual data
     * You can pass these variables to the template as needed.
     */

    // Include the popup template
    include MAPIFYME_PLUGIN_DIR . 'assets/templates/current-location/popup-template1.php';

    // Get the contents of the buffer and clean it
    $popup_template = ob_get_clean();

    // Optionally, sanitize the output if it contains user-generated content
    // For example, using wp_kses_post() if the content is allowed to have HTML
    $popup_template = wp_kses_post($popup_template);

    return $popup_template;
  }

  /**
   * (Optional) AJAX handler to fetch popup template dynamically
   */
  /*
    public function fetch_popup_template()
    {
        // Check nonce for security if using
        // check_ajax_referer( 'mapifyme_nonce', 'security' );

        // Render the popup template
        $popup_template = $this->render_popup_template();

        wp_send_json_success(array('template' => $popup_template));

        wp_die();
    }
    */
}
