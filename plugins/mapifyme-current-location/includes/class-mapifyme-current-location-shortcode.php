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

    // Fetch the global popup template from the database
    $global_popup_template = MapifyMe_DB::get_setting('popup_template', 'template1');

    // Define default shortcode attributes, including zoom and map_provider
    $atts = shortcode_atts(array(
      'show_latitude'   => 'true',
      'show_longitude'  => 'true',
      'show_map'        => 'true',
      'map_height'      => '400px',
      'map_width'       => '100%',
      'show_address'    => 'true',
      'popup_template'  => $global_popup_template, // Set the global template as default
      'zoom'            => '13', // Default zoom level
      'map_provider'    => '',  // Allow map_provider override via shortcode
      'showGetLocationButton' => 'false', // Default to hiding the button
    ), $atts);


    // Generate a unique ID for the map container
    $map_container_id = 'mapifyme-current-location-map-' . uniqid();

    // Initialize output
    $output = '';

    // Fetch the selected popup template
    $popup_template = 'popup-' . esc_attr($atts['popup_template']); // Set template based on shortcode attribute or default

    // Define the path to the popup template
    $template_path = MAPIFYME_PLUGIN_DIR . 'assets/templates/current-location/' . $popup_template . '.php';

    // Check if the template file exists
    if (!file_exists($template_path)) {
      $is_processing = false;
      return '<p>' . __('Template not found:', 'mapifyme') . ' ' . esc_html($popup_template) . '</p>';
    }

    // Prepare fields for the popup template
    $fields = array(
      'latitude'  => '',
      'longitude' => '',
      'address'   => '', // Dynamic address field (if needed)
    );

    // Include the template file and pass the fields data
    ob_start();
    include($template_path); // The template will use $fields for rendering
    $popup_html = ob_get_clean();
    // Prepare the markers array with placeholder latitude and longitude
    $markers = array(
      array(
        'latitude' => 0, // Placeholder, will be updated in JavaScript
        'longitude' => 0,
        'popup_content' => $popup_html
      )
    );
    // Encode the popup HTML to safely pass it into JavaScript
    $safe_popup_html = addslashes($popup_html); // Escape quotes for safe JS injection

    // Add the map container HTML with the required data attributes for geolocation
    if ($atts['show_map'] === 'true') {
      $output .= '<div id="' . esc_attr($map_container_id) . '" 
               data-mapifyme-geolocation
               data-template="' . esc_attr($atts['popup_template']) . '"
               data-popup-html="' . esc_js($safe_popup_html) . '" 
               data-show-latitude="' . esc_attr($atts['show_latitude']) . '"
               data-show-longitude="' . esc_attr($atts['show_longitude']) . '"
               data-show-address="' . esc_attr($atts['show_address']) . '"
              data-zoom="' . esc_attr($atts['zoom']) . '"  // Pass zoom level
               style="width: ' . esc_attr($atts['map_width']) . '; height: ' . esc_attr($atts['map_height']) . ';">
               Loading map...
            </div>';
    }

    // Add the info container for displaying location info (latitude, longitude, address)
    $output .= '<div id="' . esc_attr($map_container_id) . '-info" style="margin-top: 15px;"></div>';
    // Enqueue map processor by passing the $map_container_id and $popup_html
    $this->enqueue_map_processor($map_container_id, $markers, filter_var($atts['showGetLocationButton'], FILTER_VALIDATE_BOOLEAN), intval($atts['zoom']));
    // Enqueue the map processor and pass the map container ID, attributes, and popup content
    //$this->enqueue_map_processor($map_container_id, $atts, $safe_popup_html, $showGetLocationButton);

    $is_processing = false;
    return $output;
  }

  /**
   * Enqueue the map processor and JavaScript for retrieving current location.
   *
   * @param string $map_container_id The map container ID
   * @param array $atts Shortcode attributes
   * @param string $popup_html The rendered popup HTML content
   * @param bool $showGetLocationButton Control the visibility of the "Get Current Location" button
   */
  private function enqueue_map_processor($map_container_id, $safe_popup_html, $showGetLocationButton = false, $zoom_level = 13, $map_provider = null, $enable_geolocation = true,)
  {
    $mapifyme_maps = new MapifyMe_Maps();

    // Set latitude and longitude based on location data
    $latitude = isset($location_data['latitude']) ? floatval($location_data['latitude']) : 0;
    $longitude = isset($location_data['longitude']) ? floatval($location_data['longitude']) : 0;

    // Pass latitude, longitude, popup content, and enable_geolocation flag to the map processor
    $mapifyme_maps->enqueue_map_processor_scripts(
      $map_container_id,
      $safe_popup_html,
      $latitude,
      $longitude,
      $enable_geolocation,
      $showGetLocationButton,   // Pass the showGetLocationButton value
      $map_provider,            // Pass map provider override
      $zoom_level               // Pass the zoom level
    );
  }
}
