<?php
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Single_Location_Shortcode
{
  public function __construct()
  {
    add_shortcode('mapifyme_single_location', array($this, 'render_single_location_shortcode'));
  }

  public function render_single_location_shortcode($atts)
  {
    // Prevent recursion
    static $is_processing = false;
    if ($is_processing) {
      return ''; // Prevent recursion by returning empty content
    }
    $is_processing = true;

    // Fetch the global popup template from the database
    $global_popup_template = MapifyMe_DB::get_setting('popup_template', 'template1');

    // Define default shortcode attributes
    $atts = shortcode_atts(array(
      'id' => '',
      'type' => 'post',
      'show_latitude' => 'true',
      'show_longitude' => 'true',
      'show_street' => 'true',
      'show_city' => 'true',
      'show_state' => 'true',
      'show_zip' => 'true',
      'show_country' => 'true',
      'show_website' => 'true',
      'show_email' => 'true',
      'show_phone' => 'true',
      'show_title' => 'true',
      'show_content' => 'true',
      'show_category' => 'true',
      'show_tags' => 'true',
      'show_author' => 'true',
      'popup_template' => $global_popup_template,
      'zoom' => '13', // Default zoom level
      'map_provider' => '', // Allow map_provider override via shortcode
      'enable_geolocation' => 'false', // Default geolocation to false
      'showGetLocationButton' => 'false', // Default to hiding the button
      'map_height' => '400px',
      'map_width' => '100%',
    ), $atts);

    // If 'id' is not provided, use the global post ID dynamically
    if (empty($atts['id'])) {
      global $post;
      if (isset($post->ID)) {
        $atts['id'] = $post->ID;
      } else {
        $is_processing = false;
        return '<p>' . __('No location specified or found.', 'mapifyme') . '</p>';
      }
    }

    // Fetch location data based on 'id' and 'type'
    $location_data = $this->get_location_data($atts['id'], $atts['type']);
    if (!$location_data) {
      $is_processing = false;
      return '<p>' . __('No location data found.', 'mapifyme') . '</p>';
    }

    // Retrieve the post object for the given 'id'
    $post = get_post($atts['id']);

    // Initialize fields array based on the shortcode attributes
    $fields = [];

    // Conditionally add each field based on the 'show_*' attributes
    if ($atts['show_title'] === 'true') {
      $fields['title'] = esc_html(get_the_title($post));
    }
    if ($atts['show_street'] === 'true') {
      $fields['street'] = esc_html($location_data['street'] ?? '');
    }
    if ($atts['show_city'] === 'true') {
      $fields['city'] = esc_html($location_data['city'] ?? '');
    }
    if ($atts['show_state'] === 'true') {
      $fields['state'] = esc_html($location_data['state'] ?? '');
    }
    if ($atts['show_country'] === 'true') {
      $fields['country'] = esc_html($location_data['country'] ?? '');
    }
    if ($atts['show_phone'] === 'true') {
      $fields['phone'] = sanitize_text_field($location_data['phone'] ?? '');
    }
    if ($atts['show_website'] === 'true') {
      $fields['website'] = esc_url($location_data['website'] ?? '');
    }
    if ($atts['show_content'] === 'true') {
      // Get the post content without applying filters yet
      $content = $post->post_content;

      // Strip all shortcodes before applying filters
      $content = strip_shortcodes($content);

      // Apply WordPress content filters to the stripped content
      $content = apply_filters('the_content', $content);

      // Sanitize the content to prevent XSS
      $fields['content'] = wp_kses_post($content);
    }

    if ($atts['show_category'] === 'true') {
      $categories = wp_get_post_categories($post->ID);
      if (!empty($categories)) {
        $category_names = array_map('get_cat_name', $categories);
        $fields['category'] = implode(', ', $category_names); // Show categories as plain text
      }
    }

    if ($atts['show_tags'] === 'true') {
      $fields['tags'] = get_the_tag_list('', ', ', '', $post->ID);
    }
    if ($atts['show_author'] === 'true') {
      $fields['author'] = get_the_author_meta('display_name', $post->post_author);
    }

    // Fetch the selected popup template
    $popup_template = 'popup-' . esc_attr($atts['popup_template']); // Set template based on shortcode attribute or default

    // Define the path to the popup template
    $template_path = MAPIFYME_PLUGIN_DIR . 'assets/templates/' . $popup_template . '.php';

    // Check if the template file exists
    if (!file_exists($template_path)) {
      $is_processing = false;
      return '<p>' . __('Template not found:', 'mapifyme') . ' ' . esc_html($popup_template) . '</p>';
    }

    // Include the template file and pass the fields data
    ob_start();
    include($template_path); // The template will use $fields for rendering
    $popup_html = ob_get_clean();

    // Prepare the markers array
    $markers = array(
      array(
        'latitude' => $location_data['latitude'],
        'longitude' => $location_data['longitude'],
        'popup_content' => $popup_html  // Make sure this contains valid HTML
      )
    );



    // Encode the markers for passing to JavaScript
    $markers_json = wp_json_encode($markers);

    // Generate a unique map container ID
    $map_container_id = 'mapifyme-single-location-map-' . esc_attr($atts['id']) . '-' . uniqid();

    // Generate map container
    $output = '<div id="' . esc_attr($map_container_id) . '" 
                  data-mapifyme-map 
                  data-latitude="' . esc_attr($location_data['latitude']) . '" 
                  data-longitude="' . esc_attr($location_data['longitude']) . '" 
                  data-zoom="' . esc_attr($atts['zoom']) . '"  
                  data-markers=\'' . esc_attr($markers_json) . '\' 
                  data-map-provider="' . esc_attr($atts['map_provider']) . '" 
                  style="width: ' . esc_attr($atts['map_width']) . '; height: ' . esc_attr($atts['map_height']) . ';"></div>';


    // Enqueue map processor by passing the $map_container_id and $markers array
    $this->enqueue_map_processor($map_container_id, $markers, filter_var($atts['enable_geolocation'], FILTER_VALIDATE_BOOLEAN), intval($atts['zoom']), esc_attr($atts['map_provider']), filter_var($atts['showGetLocationButton'], FILTER_VALIDATE_BOOLEAN));

    $is_processing = false;
    return $output;
  }

  private function enqueue_map_processor($map_container_id, $markers, $enable_geolocation = false, $zoom_level = 13, $map_provider = null, $showGetLocationButton = false)
  {
    $mapifyme_maps = new MapifyMe_Maps();

    // Use the first marker's latitude and longitude for map center
    $latitude = isset($markers[0]['latitude']) ? floatval($markers[0]['latitude']) : 0;
    $longitude = isset($markers[0]['longitude']) ? floatval($markers[0]['longitude']) : 0;

    // Pass the markers array to the map processor
    $mapifyme_maps->enqueue_map_processor_scripts(
      $map_container_id,
      $markers,
      $latitude,
      $longitude,
      $enable_geolocation,
      $showGetLocationButton,
      $map_provider,
      $zoom_level
    );
  }


  private function get_location_data($id, $type)
  {
    if ($type === 'post') {
      return MapifyMe_DB::get_post_data($id); // Ensure this function returns title, address, etc.
    }
    return false;
  }
}
