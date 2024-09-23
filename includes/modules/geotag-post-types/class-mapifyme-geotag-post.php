<?php
if (! defined('WPINC')) {
  die;
}

class MapifyMe_Geotag_Post
{
  public function __construct()
  {
    // Hook into WordPress to add the custom geotag fields
    add_action('add_meta_boxes', array($this, 'add_geotag_metabox'));
    add_action('save_post', array($this, 'save_geotag_data'));

    // Register AJAX action for updating geotag data
    add_action('wp_ajax_mapifyme_update_geotag_data', array($this, 'update_geotag_data_ajax'));
  }




  /**
   * Handle AJAX request to update geotag data.
   */
  public function update_geotag_data_ajax()
  {
    // Check if nonce is set and valid
    if (
      !isset($_POST['nonce']) ||
      !wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['nonce'])),
        'mapifyme_update_geotag_data_nonce'
      )
    ) {
      error_log('MapifyMe: Invalid nonce.');
      wp_send_json_error(__('Invalid nonce.', 'mapifyme'));
    }

    // Check user capabilities
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id || !current_user_can('edit_post', $post_id)) {
      error_log('MapifyMe: Permission denied or invalid post ID. Post ID: ' . $post_id);
      wp_send_json_error(__('Permission denied or invalid post ID.', 'mapifyme'));
    }

    // Check if geotag_data is set and is an array
    if (!isset($_POST['geotag_data']) || !is_array($_POST['geotag_data'])) {
      error_log('MapifyMe: Invalid geotag data.');
      wp_send_json_error(__('Invalid geotag data.', 'mapifyme'));
    }

    // Initialize sanitized geotag data array
    $geotag_data = array();

    // Sanitize each field individually
    if (isset($_POST['geotag_data']['latitude'])) {
      $geotag_data['latitude'] = floatval(wp_unslash($_POST['geotag_data']['latitude']));
    } else {
      $geotag_data['latitude'] = '';
    }

    if (isset($_POST['geotag_data']['longitude'])) {
      $geotag_data['longitude'] = floatval(wp_unslash($_POST['geotag_data']['longitude']));
    } else {
      $geotag_data['longitude'] = '';
    }

    if (isset($_POST['geotag_data']['street'])) {
      $geotag_data['street'] = sanitize_text_field(wp_unslash($_POST['geotag_data']['street']));
    } else {
      $geotag_data['street'] = '';
    }

    if (isset($_POST['geotag_data']['city'])) {
      $geotag_data['city'] = sanitize_text_field(wp_unslash($_POST['geotag_data']['city']));
    } else {
      $geotag_data['city'] = '';
    }

    if (isset($_POST['geotag_data']['state'])) {
      $geotag_data['state'] = sanitize_text_field(wp_unslash($_POST['geotag_data']['state']));
    } else {
      $geotag_data['state'] = '';
    }

    if (isset($_POST['geotag_data']['zip'])) {
      $geotag_data['zip'] = sanitize_text_field(wp_unslash($_POST['geotag_data']['zip']));
    } else {
      $geotag_data['zip'] = '';
    }

    if (isset($_POST['geotag_data']['country'])) {
      $geotag_data['country'] = sanitize_text_field(wp_unslash($_POST['geotag_data']['country']));
    } else {
      $geotag_data['country'] = '';
    }

    // Log sanitized data
    error_log('MapifyMe: Sanitized Geotag Data for Post ID ' . $post_id . ': ' . print_r($geotag_data, true));

    // Validate required fields
    if (empty($geotag_data['latitude']) || empty($geotag_data['longitude'])) {
      error_log('MapifyMe: Latitude and Longitude are required. Post ID: ' . $post_id);
      wp_send_json_error(__('Latitude and Longitude are required.', 'mapifyme'));
    }

    // Validate latitude and longitude ranges
    if ($geotag_data['latitude'] < -90 || $geotag_data['latitude'] > 90) {
      error_log('MapifyMe: Invalid latitude value (' . $geotag_data['latitude'] . ') for Post ID: ' . $post_id);
      wp_send_json_error(__('Invalid latitude value.', 'mapifyme'));
    }

    if ($geotag_data['longitude'] < -180 || $geotag_data['longitude'] > 180) {
      error_log('MapifyMe: Invalid longitude value (' . $geotag_data['longitude'] . ') for Post ID: ' . $post_id);
      wp_send_json_error(__('Invalid longitude value.', 'mapifyme'));
    }

    // Update the data in the custom database table
    $update_result = MapifyMe_DB::update_post_data($post_id, $geotag_data);

    if ($update_result) {
      // Log the successful update
      error_log('MapifyMe: Successfully updated Geotag Data for Post ID ' . $post_id);

      // Retrieve the updated data to send back
      $updated_data = MapifyMe_DB::get_post_data($post_id);

      wp_send_json_success(array(
        'message' => __('Geotag data updated successfully.', 'mapifyme'),
        'data'    => $updated_data,
      ));
    } else {
      // Log the failure
      error_log('MapifyMe: Failed to update Geotag Data for Post ID ' . $post_id);
      wp_send_json_error(__('Failed to update geotag data.', 'mapifyme'));
    }
  }





  /**
   * Add a metabox for geotagging and address to the enabled post types.
   */
  public function add_geotag_metabox()
  {
    // Fetch the enabled post types from the settings
    $enabled_post_types = MapifyMe_DB::get_setting('enabled_post_types', array('post', 'page')); // Default to 'post' and 'page' if not set

    if (!empty($enabled_post_types) && is_array($enabled_post_types)) {
      foreach ($enabled_post_types as $post_type) {
        add_meta_box(
          'mapifyme_geotag',
          __('Geotag Post', 'mapifyme'),
          array($this, 'render_geotag_metabox'),
          $post_type,
          'advanced',
          'default'
        );
      }
    }
  }

  /**
   * Render the metabox with vertical tabs.
   */
  public function render_geotag_metabox($post)
  {
    // Get the saved values for geotagging, address, and contact fields from the custom table
    $post_data = MapifyMe_DB::get_post_data($post->ID);

    $latitude = isset($post_data['latitude']) ? esc_attr($post_data['latitude']) : '';
    $longitude = isset($post_data['longitude']) ? esc_attr($post_data['longitude']) : '';
    $address = array(
      'street' => isset($post_data['street']) ? esc_attr($post_data['street']) : '',
      'city' => isset($post_data['city']) ? esc_attr($post_data['city']) : '',
      'state' => isset($post_data['state']) ? esc_attr($post_data['state']) : '',
      'zip' => isset($post_data['zip']) ? esc_attr($post_data['zip']) : '',
      'country' => isset($post_data['country']) ? esc_attr($post_data['country']) : ''
    );
    $contact = array(
      'website' => isset($post_data['website']) ? esc_url($post_data['website']) : '',
      'email' => isset($post_data['email']) ? sanitize_email($post_data['email']) : '',
      'phone' => isset($post_data['phone']) ? esc_attr($post_data['phone']) : ''
    );

    // Add nonce field to secure the form
    wp_nonce_field('mapifyme_save_geotag', 'mapifyme_geotag_nonce');
?>
    <!-- Vertical Tabbed Interface -->
    <div id="mapifyme-vertical-tabs">
      <div class="mapifyme-tab-links-vertical">
        <ul>
          <li class="active"><a href="#search-location-tab"><?php esc_html_e('Search Location', 'mapifyme'); ?></a></li>
          <li><a href="#geo-tab"><?php esc_html_e('Geo Location', 'mapifyme'); ?></a></li>
          <li><a href="#address-tab"><?php esc_html_e('Address', 'mapifyme'); ?></a></li>
          <li><a href="#contact-tab"><?php esc_html_e('Contact', 'mapifyme'); ?></a></li>
        </ul>
      </div>

      <div class="mapifyme-tab-content-vertical">

        <!-- Search Location Tab -->
        <div id="search-location-tab" class="tab-vertical active">
          <!-- Search Address Input -->
          <p>

            <input type="text" id="mapifyme_search_address" class="regular-text" placeholder="Type an address...">
          </p>
          <!-- Map Container -->
          <div id="mapifyme-search-map"
            data-mapifyme-map
            data-latitude="<?php echo esc_attr($latitude); ?>"
            data-longitude="<?php echo esc_attr($longitude); ?>"
            data-draggable="true"
            style="width: 100%; height: 400px;">
          </div>

        </div>

        <!-- Geo Location Tab -->
        <div id="geo-tab" class="tab-vertical">
          <p>
            <label for="mapifyme_latitude"><?php esc_html_e('Latitude:', 'mapifyme'); ?></label>
            <input type="text" id="mapifyme_latitude" name="mapifyme_latitude" value="<?php echo esc_attr($latitude); ?>" />
          </p>
          <p>
            <label for="mapifyme_longitude"><?php esc_html_e('Longitude:', 'mapifyme'); ?></label>
            <input type="text" id="mapifyme_longitude" name="mapifyme_longitude" value="<?php echo esc_attr($longitude); ?>" />
          </p>
          <p>
            <button type="button" id="mapifyme-fetch-address" class="button"><?php esc_html_e('Fetch Address from Coordinates', 'mapifyme'); ?></button>
          </p>
        </div>

        <!-- Address Tab -->
        <div id="address-tab" class="tab-vertical">
          <p>
            <label for="mapifyme_street"><?php esc_html_e('Street:', 'mapifyme'); ?></label>
            <input type="text" id="mapifyme_street" name="mapifyme_address[street]" value="<?php echo esc_attr($address['street']); ?>" />
          </p>
          <p>
            <label for="mapifyme_city"><?php esc_html_e('City:', 'mapifyme'); ?></label>
            <input type="text" id="mapifyme_city" name="mapifyme_address[city]" value="<?php echo esc_attr($address['city']); ?>" />
          </p>
          <p>
            <label for="mapifyme_state"><?php esc_html_e('State:', 'mapifyme'); ?></label>
            <input type="text" id="mapifyme_state" name="mapifyme_address[state]" value="<?php echo esc_attr($address['state']); ?>" />
          </p>
          <p>
            <label for="mapifyme_zip"><?php esc_html_e('ZIP Code:', 'mapifyme'); ?></label>
            <input type="text" id="mapifyme_zip" name="mapifyme_address[zip]" value="<?php echo esc_attr($address['zip']); ?>" />
          </p>
          <p>
            <label for="mapifyme_country"><?php esc_html_e('Country:', 'mapifyme'); ?></label>
            <input type="text" id="mapifyme_country" name="mapifyme_address[country]" value="<?php echo esc_attr($address['country']); ?>" />
          </p>
          <p>
            <button type="button" id="mapifyme-fetch-coordinates" class="button"><?php esc_html_e('Fetch Coordinates from Address', 'mapifyme'); ?></button>
          </p>

        </div>

        <!-- Contact Tab -->
        <div id="contact-tab" class="tab-vertical">
          <p>
            <label for="mapifyme_website"><?php esc_html_e('Website:', 'mapifyme'); ?></label>
            <input type="url" id="mapifyme_website" name="mapifyme_contact[website]" value="<?php echo esc_url($contact['website']); ?>" />
          </p>
          <p>
            <label for="mapifyme_email"><?php esc_html_e('Email:', 'mapifyme'); ?></label>
            <input type="email" id="mapifyme_email" name="mapifyme_contact[email]" value="<?php echo esc_attr($contact['email']); ?>" />
          </p>
          <p>
            <label for="mapifyme_phone"><?php esc_html_e('Phone:', 'mapifyme'); ?></label>
            <input type="text" id="mapifyme_phone" name="mapifyme_contact[phone]" value="<?php echo esc_attr($contact['phone']); ?>" />
          </p>
        </div>
        <!-- Save Location Button -->
        <p>
          <button type="button" id="mapifyme_save_location" class="button button-primary">Save Location</button>
        </p>
      </div>
    </div>
    <!-- JavaScript for tab navigation -->
    <script>
      jQuery(document).ready(function($) {
        // Switch between tabs
        $('#mapifyme-vertical-tabs .mapifyme-tab-links-vertical a').on('click', function(e) {
          e.preventDefault();
          var target = $(this).attr('href');
          $('#mapifyme-vertical-tabs .mapifyme-tab-links-vertical li, .tab-vertical').removeClass('active');
          $(this).parent().addClass('active');
          $(target).addClass('active');
        });
      });
    </script>

<?php
  }

  /**
   * Save the geotag, address, and contact data when the post is saved.
   */
  public function save_geotag_data($post_id)
  {
    // Verify nonce
    if (!isset($_POST['mapifyme_geotag_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mapifyme_geotag_nonce'])), 'mapifyme_save_geotag')) {
      return;
    }

    // Check if user has permissions
    if (!current_user_can('edit_post', $post_id)) {
      return;
    }

    // Prevent autosave from triggering the save
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    // Prepare data for saving
    $data = array(
      'latitude' => isset($_POST['mapifyme_latitude']) ? floatval(sanitize_text_field(wp_unslash($_POST['mapifyme_latitude']))) : '',
      'longitude' => isset($_POST['mapifyme_longitude']) ? floatval(sanitize_text_field(wp_unslash($_POST['mapifyme_longitude']))) : '',
      'street' => isset($_POST['mapifyme_address']['street']) ? sanitize_text_field(wp_unslash($_POST['mapifyme_address']['street'])) : '',
      'city' => isset($_POST['mapifyme_address']['city']) ? sanitize_text_field(wp_unslash($_POST['mapifyme_address']['city'])) : '',
      'state' => isset($_POST['mapifyme_address']['state']) ? sanitize_text_field(wp_unslash($_POST['mapifyme_address']['state'])) : '',
      'zip' => isset($_POST['mapifyme_address']['zip']) ? sanitize_text_field(wp_unslash($_POST['mapifyme_address']['zip'])) : '',
      'country' => isset($_POST['mapifyme_address']['country']) ? sanitize_text_field(wp_unslash($_POST['mapifyme_address']['country'])) : '',
      'website' => isset($_POST['mapifyme_contact']['website']) ? esc_url_raw(wp_unslash($_POST['mapifyme_contact']['website'])) : '',
      'email' => isset($_POST['mapifyme_contact']['email']) ? sanitize_email(wp_unslash($_POST['mapifyme_contact']['email'])) : '',
      'phone' => isset($_POST['mapifyme_contact']['phone']) ? sanitize_text_field(wp_unslash($_POST['mapifyme_contact']['phone'])) : '',
    );

    // Save data in the custom table
    $save_result = MapifyMe_DB::update_post_data($post_id, $data);

    if ($save_result) {
      error_log('MapifyMe: Successfully saved Geotag Data for Post ID ' . $post_id);
    } else {
      error_log('MapifyMe: Failed to save Geotag Data for Post ID ' . $post_id);
    }
  }
}
