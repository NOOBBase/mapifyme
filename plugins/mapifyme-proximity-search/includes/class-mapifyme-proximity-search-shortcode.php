<?php
// Prevent direct access
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Proximity_Search_Shortcode
{
  public function __construct()
  {
    // Register the shortcode for proximity search
    add_shortcode('mapifyme_proximity_search', array($this, 'render_proximity_search'));
  }

  /**
   * Render the proximity search shortcode
   *
   * @param array $atts Shortcode attributes
   * @return string HTML output
   */
  public function render_proximity_search($atts)
  {
    // Prevent recursion
    static $is_processing = false;
    if ($is_processing) {
      return ''; // Prevent recursion by returning empty content
    }
    $is_processing = true;

    // Fetch the global popup template and map provider from the database
    $global_popup_template = MapifyMe_DB::get_setting('popup_template', 'template1');
    $map_provider = MapifyMe_DB::get_setting('map_provider', 'leaflet'); // Get the map provider

    // Parse shortcode attributes
    $atts = shortcode_atts(array(
      'radius' => '10', // Default radius in km
      'zoom' => '14', // Default zoom level
      'map_height' => '400px',
      'map_width' => '100%',
      'showGetLocationButton' => 'false', // Default to hiding the button
      'popup_template' => $global_popup_template, // Fetch the global popup template
    ), $atts, 'mapifyme_proximity_search');

    // Handle form submissions for location fields
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;

    // Initialize default values for other location parameters
    $street = $city = $state = $zip = $country = '';
    $radius = intval($atts['radius']); // Default radius

    // Retrieve the address fields if provided
    $street = isset($_POST['street']) ? sanitize_text_field($_POST['street']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    $zip = isset($_POST['zip']) ? sanitize_text_field($_POST['zip']) : '';
    $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
    $radius = isset($_POST['radius']) ? intval($_POST['radius']) : intval($atts['radius']);

    // If latitude and longitude are not provided, geocode the address based on the map provider
    if ($latitude === null && $longitude === null && (!empty($street) || !empty($city) || !empty($state) || !empty($zip) || !empty($country))) {
      if ($map_provider === 'google_maps') {
        // Use Google Maps Geocoding
        $geocoded_location = $this->geocode_address($street, $city, $state, $zip, $country);
      } else {
        // Use Nominatim for Leaflet
        $geocoded_location = $this->geocode_address_leaflet($street, $city, $state, $zip, $country);
      }

      if ($geocoded_location) {
        $latitude = $geocoded_location['latitude'];
        $longitude = $geocoded_location['longitude'];
      } else {
        // Fallback to default coordinates if geocoding fails
        $latitude = 51.45400691006; // Default latitude
        $longitude = -0.1332313840429; // Default longitude
      }
    }

    // If no location is provided (geocoded or otherwise), fetch all posts
    if ($latitude === null && $longitude === null) {
      $posts = MapifyMe_DB::get_all_geotagged_posts();
      $latitude = 51.45400691006; // Default latitude
      $longitude = -0.1332313840429; // Default longitude
    } else {
      // Query posts based on the submitted/geocoded location data
      $posts = MapifyMe_DB::get_posts_by_location($latitude, $longitude, $street, $city, $state, $zip, $country, $radius);
    }

    // Prepare popup content for each post using the template logic
    $markers = [];
    foreach ($posts as $post) {
      // Fetch location data for the post
      $post_data = MapifyMe_DB::get_post_data($post['ID']);

      if (!$post_data) {
        error_log('Post data not found for post ID: ' . $post['ID']);
        continue;
      }

      // Initialize fields array for popup content
      $fields = [
        'title' => esc_html($post['post_title']),
        'latitude' => $post_data['latitude'] ?? '',
        'content' => is_string($post['post_content']) ? wp_kses_post(apply_filters('the_content', strip_shortcodes($post['post_content']))) : '',
        'longitude' => $post_data['longitude'] ?? '',
        'street' => $post_data['street'] ?? '',
        'city' => $post_data['city'] ?? '',
        'state' => $post_data['state'] ?? '',
        'zip' => $post_data['zip'] ?? '',
        'country' => $post_data['country'] ?? '',
      ];

      // Fetch the selected popup template
      $popup_template = 'popup-' . esc_attr($atts['popup_template']);
      $template_path = MAPIFYME_PLUGIN_DIR . 'assets/templates/' . $popup_template . '.php';

      if (file_exists($template_path)) {
        ob_start();
        include($template_path);
        $popup_html = ob_get_clean();
      } else {
        error_log('Template not found: ' . $template_path);
        $popup_html = '<p>Default popup content for post ' . esc_html($fields['title']) . '</p>';
      }

      // Add the post's latitude, longitude, and popup HTML to markers array
      $markers[] = [
        'latitude' => $post_data['latitude'],
        'longitude' => $post_data['longitude'],
        'popup_content' => $popup_html,  // Popup content should include HTML
      ];

      error_log('Marker added for ' . $fields['latitude'] . ' at (' . $post_data['latitude'] . ', ' . $post_data['longitude'] . ')');
    }

    // Create a unique ID for the map container
    $map_container_id = 'mapifyme-proximity-map-' . uniqid();

    // Initialize the search form
    $output = '<div class="mapifyme-proximity-search-form">
                    <form id="proximity-search-form" method="post">
                        <label for="latitude">Latitude:</label>
                        <input type="text" name="latitude" id="latitude" value="' . (isset($_POST['latitude']) ? esc_attr($_POST['latitude']) : '') . '">

                        <label for="longitude">Longitude:</label>
                        <input type="text" name="longitude" id="longitude" value="' . (isset($_POST['longitude']) ? esc_attr($_POST['longitude']) : '') . '">

                        <label for="street">Street:</label>
                        <input type="text" name="street" id="street" value="' . esc_attr($street) . '">

                        <label for="city">City:</label>
                        <input type="text" name="city" id="city" value="' . esc_attr($city) . '">

                        <label for="state">State:</label>
                        <input type="text" name="state" id="state" value="' . esc_attr($state) . '">

                        <label for="zip">Zip Code:</label>
                        <input type="text" name="zip" id="zip" value="' . esc_attr($zip) . '">

                        <label for="country">Country:</label>
                        <input type="text" name="country" id="country" value="' . esc_attr($country) . '">

                        <label for="radius">Radius (km):</label>
                        <input type="number" name="radius" id="radius" value="' . esc_attr($radius) . '" required>

                        <button type="submit">Search</button>
                    </form>';

    $output .= '<div id="' . esc_attr($map_container_id) . '" 
                        class="mapifyme-proximity-map"
                        style="width: ' . esc_attr($atts['map_width']) . '; height: ' . esc_attr($atts['map_height']) . ';"
                        data-mapifyme-map="true"
                        data-latitude="' . esc_attr($latitude) . '"
                        data-longitude="' . esc_attr($longitude) . '"
                        data-zoom="' . esc_attr($atts['zoom']) . '"
                        data-markers=\'' . wp_json_encode($markers) . '\'>
                        ' . (empty($markers) ? 'No results found. Please search to see the map.' : 'Loading map...') . '
                        </div>';

    // Always enqueue the map processor to render the map even if there are no posts
    $this->enqueue_map_processor($map_container_id, $atts['zoom'], $latitude, $longitude, $markers, $map_provider);

    $is_processing = false;
    return $output;
  }

  /**
   * Geocode address using Nominatim (OpenStreetMap) API.
   *
   * @param string $street
   * @param string $city
   * @param string $state
   * @param string $zip
   * @param string $country
   * @return array|null Returns array with latitude and longitude, or null if failed.
   */
  private function geocode_address_leaflet($street, $city, $state, $zip, $country)
  {
    // Build the address string for geocoding
    $address = urlencode(trim("$street $city $state $zip $country"));

    // Nominatim API URL for geocoding
    $url = "https://nominatim.openstreetmap.org/search?q=$address&format=json&addressdetails=1&limit=1";

    // Perform the remote request to Nominatim
    $response = wp_remote_get($url);

    // Check if there was an error with the request
    if (is_wp_error($response)) {
      return null;
    }

    // Get the response body and decode it
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // If a valid location is found, return the latitude and longitude
    if (!empty($data[0])) {
      return [
        'latitude' => $data[0]['lat'],
        'longitude' => $data[0]['lon'],
      ];
    }

    // Return null if no valid location was found
    return null;
  }

  /**
   * Geocode address using Google Maps API.
   *
   * @param string $street
   * @param string $city
   * @param string $state
   * @param string $zip
   * @param string $country
   * @return array|null Returns array with latitude and longitude, or null if failed.
   */
  private function geocode_address($street, $city, $state, $zip, $country)
  {
    $address = urlencode(trim("$street $city $state $zip $country"));
    $google_maps_api_key = MapifyMe_DB::get_setting('google_maps_api_key', '');

    if (empty($google_maps_api_key)) {
      return null;
    }

    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&key=$google_maps_api_key";
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
      return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data['results'][0]['geometry']['location'])) {
      $location = $data['results'][0]['geometry']['location'];
      return [
        'latitude' => $location['lat'],
        'longitude' => $location['lng'],
      ];
    }

    return null;
  }

  /**
   * Enqueue the map processor and JavaScript for rendering the map.
   *
   * @param string $map_container_id The map container ID
   * @param int $zoom_level The zoom level for the map
   * @param float $latitude The latitude for the map center
   * @param float $longitude The longitude for the map center
   * @param array $markers Array of markers with popup content
   */
  private function enqueue_map_processor($map_container_id, $zoom_level, $latitude, $longitude, $markers)
  {
    $mapifyme_maps = new MapifyMe_Maps();

    // Enqueue the map processor script with relevant parameters, passing markers and popup content
    $mapifyme_maps->enqueue_map_processor_scripts(
      $map_container_id,    // The ID of the map container
      $markers,             // Array of markers with popup content
      $latitude,            // Latitude for the map center
      $longitude,           // Longitude for the map center
      false,                // Geolocation disabled
      false,                // Hide "Get Location" button
      null,                 // No specific map provider override
      $zoom_level           // Initial zoom level
    );
  }
}
