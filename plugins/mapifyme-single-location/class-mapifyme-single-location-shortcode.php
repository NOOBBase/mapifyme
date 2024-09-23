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

    // Define default shortcode attributes, using the global template as the default value
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
      'popup_template' => $global_popup_template, // Set the global template as default
    ), $atts);

    // Check if 'id' attribute is provided
    if (empty($atts['id'])) {
      $is_processing = false;
      return '<p>' . __('No location specified.', 'mapifyme') . '</p>';
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
      $fields['address'] = esc_html($location_data['street'] ?? '');
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
      $fields['content'] = wp_kses_post(apply_filters('the_content', $post->post_content));
    }
    if (
      $atts['show_category'] === 'true'
    ) {
      $fields['category'] = get_the_category_list(', ', '', $post->ID);
    }
    if ($atts['show_tags'] === 'true') {
      $fields['tags'] = get_the_tag_list('', ', ', '', $post->ID);
    }
    if (
      $atts['show_author'] === 'true'
    ) {
      $fields['author'] = get_the_author_meta('display_name', $post->post_author);
    }

    // Encode fields for the data-popup attribute
    $popup_content = wp_json_encode($fields);

    // Generate a unique map container ID
    $map_container_id = 'mapifyme-single-location-map-' . esc_attr($atts['id']) . '-' . uniqid();

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

    // Add the map container HTML
    $output = '<div id="' . $map_container_id . '" 
                  data-mapifyme-map 
                  data-latitude="' . esc_attr($location_data['latitude']) . '" 
                  data-longitude="' . esc_attr($location_data['longitude']) . '" 
                  data-popup-html=\'' . esc_attr($popup_html) . '\' 
                  data-template="' . esc_attr($atts['popup_template']) . '" 
                  style="width: 100%; height: 500px;"></div>';

    // Enqueue map processor
    $this->enqueue_map_processor($location_data, $map_container_id);

    $is_processing = false;
    return $output;
  }

  private function enqueue_map_processor($location_data, $map_container_id)
  {
    $mapifyme_maps = new MapifyMe_Maps();
    $mapifyme_maps->enqueue_map_processor_scripts();
  }

  private function get_location_data($id, $type)
  {
    if ($type === 'post') {
      return MapifyMe_DB::get_post_data($id);
    }
    return false;
  }
}
