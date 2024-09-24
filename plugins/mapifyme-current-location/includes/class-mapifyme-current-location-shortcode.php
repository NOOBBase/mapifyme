<?php
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Current_Location_Shortcode
{
  public function __construct()
  {
    // Register the shortcode for current location
    add_shortcode('mapifyme_current_location', array($this, 'render_current_location_shortcode'));
  }

  /**
   * Render the current location shortcode
   *
   * @param array $atts Shortcode attributes
   * @return string HTML output
   */
  public function render_current_location_shortcode($atts)
  {
    // Prevent recursion
    static $is_processing = false;
    if ($is_processing) {
      return ''; // Prevent recursion by returning empty content
    }
    $is_processing = true;

    // Define default shortcode attributes
    $atts = shortcode_atts(array(
      'show_latitude' => 'true',
      'show_longitude' => 'true',
      'show_map' => 'true',
      'map_height' => '400px',
      'map_width' => '100%',
      'show_address' => 'true',
    ), $atts);

    // Generate a unique ID for the map container
    $map_container_id = 'mapifyme-current-location-map-' . uniqid();

    // Initialize output
    $output = '';

    // Add the map container HTML with the required data attributes for geolocation
    if ($atts['show_map'] === 'true') {
      $output .= '<div id="' . esc_attr($map_container_id) . '" 
                     data-mapifyme-geolocation
                     data-show-latitude="' . esc_attr($atts['show_latitude']) . '"
                     data-show-longitude="' . esc_attr($atts['show_longitude']) . '"
                     data-show-address="' . esc_attr($atts['show_address']) . '"
                     style="width: ' . esc_attr($atts['map_width']) . '; height: ' . esc_attr($atts['map_height']) . ';">Loading map...</div>';
    }

    // Add the info container for displaying location info (latitude, longitude, address)
    $output .= '<div id="' . esc_attr($map_container_id) . '-info" style="margin-top: 15px;"></div>';

    // Enqueue the map processor and pass the map container ID and shortcode attributes
    $this->enqueue_map_processor($map_container_id, $atts);

    $is_processing = false;
    return $output;
  }

  /**
   * Enqueue the map processor and JavaScript for retrieving current location.
   *
   * @param string $map_container_id The map container ID
   * @param array $atts Shortcode attributes
   */
  private function enqueue_map_processor($map_container_id, $atts)
  {
    // Enqueue map scripts using the MapifyMe_Maps class, similar to Single Location shortcode
    $mapifyme_maps = new MapifyMe_Maps();
    $mapifyme_maps->enqueue_map_processor_scripts();

    // Localize the script with settings for map processing
    wp_localize_script('mapifyme-geolocation', 'mapifymeGeolocation', array(
      'map_container_id' => $map_container_id,
      'show_latitude' => $atts['show_latitude'],
      'show_longitude' => $atts['show_longitude'],
      'show_address' => $atts['show_address'],
      'ajax_url' => admin_url('admin-ajax.php'),
    ));
  }
}
