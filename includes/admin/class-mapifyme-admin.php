<?php
if (! defined('WPINC')) {
  die;
}

class MapifyMe_Admin
{

  public function __construct()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts')); // Add admin scripts
    // Hook for handling plugin activation/deactivation.
    add_action('admin_post_mapifyme_plugin_activation', array($this, 'handle_plugin_activation'));
    // Add AJAX hook to validate API key
    add_action('wp_ajax_mapifyme_validate_google_api_key', array($this, 'validate_google_api_key'));
  }


  /**
   * Validate the Google Maps API key.
   */
  // Update the validation function to use the Places API.
  public function validate_google_api_key()
  {
    // Verify the nonce for security
    check_ajax_referer('mapifyme_google_api_key_nonce', 'nonce');

    // Get the API key from the request
    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

    if (empty($api_key)) {
      wp_send_json_error(array('message' => __('API key is missing.', 'mapifyme')));
      return;
    }

    // Use the Google Places API to validate the key
    $response = wp_remote_get("https://maps.googleapis.com/maps/api/place/textsearch/json?query=restaurant&key=$api_key");

    if (is_wp_error($response)) {
      wp_send_json_error(array('message' => __('Failed to validate the API key.', 'mapifyme')));
    } else {
      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body, true);

      // Check if the API key is valid
      if (isset($data['error_message'])) {
        wp_send_json_error(array('message' => __('Invalid API key: ', 'mapifyme') . $data['error_message']));
      } else {
        wp_send_json_success(array('message' => __('API key is valid!', 'mapifyme')));
      }
    }

    wp_die();
  }




  /**
   * Add the MapifyMe admin menu.
   */
  public function add_admin_menu()
  {
    // Main "MapifyMe" menu (top-level menu item)
    add_menu_page(
      __('MapifyMe', 'mapifyme'), // Menu title
      __('MapifyMe', 'mapifyme'), // Page title
      'manage_options',
      'mapifyme-main', // Slug for upsell page
      array($this, 'render_main_page'), // Callback function to display the upsell/marketing content
      'dashicons-location-alt',
      80
    );

    // Add a submenu for MapifyMe Settings
    add_submenu_page(
      'mapifyme-main', // Parent slug
      __('MapifyMe Settings', 'mapifyme'), // Page title
      __('Settings', 'mapifyme'), // Menu title
      'manage_options',
      'mapifyme-settings', // Submenu slug
      array($this, 'render_settings_page') // Callback function to render the settings page
    );

    // Add a submenu for MapifyMe Plugins
    add_submenu_page(
      'mapifyme-main', // Parent slug
      __('MapifyMe Plugins', 'mapifyme'), // Page title
      __('Plugins', 'mapifyme'), // Menu title
      'manage_options',
      'mapifyme-plugins', // Submenu slug
      array($this, 'render_plugins_page') // Callback function to render the plugins page
    );
  }


  /**
   * Render the main MapifyMe marketing page.
   * This page can be used for upselling premium features, displaying marketing content, etc.
   */
  public function render_main_page()
  {
?>
    <div class="wrap">
      <h1><?php esc_html_e('Welcome to MapifyMe', 'mapifyme'); ?></h1>
      <p><?php esc_html_e('Thank you for using MapifyMe. Check out our premium features to enhance your experience!', 'mapifyme'); ?></p>

      <h2><?php esc_html_e('Premium Features', 'mapifyme'); ?></h2>
      <ul>
        <li><?php esc_html_e('Advanced Geolocation Tools', 'mapifyme'); ?></li>
        <li><?php esc_html_e('Unlimited Proximity Search Forms', 'mapifyme'); ?></li>
        <li><?php esc_html_e('Customizable Map Themes', 'mapifyme'); ?></li>
        <li><?php esc_html_e('Premium Support', 'mapifyme'); ?></li>
        <!-- Add more upsell features here -->
      </ul>

      <h3><?php esc_html_e('Upgrade to Pro', 'mapifyme'); ?></h3>
      <p>
        <a href="https://mapifyme.com/upgrade" class="button button-primary"><?php esc_html_e('Upgrade Now', 'mapifyme'); ?></a>
      </p>

      <hr>

      <h2><?php esc_html_e('Learn More', 'mapifyme'); ?></h2>
      <p><?php esc_html_e('Explore our tutorials, guides, and community forums to make the most out of MapifyMe.', 'mapifyme'); ?></p>
      <p>
        <a href="https://yourwebsite.com/docs" class="button"><?php esc_html_e('Documentation', 'mapifyme'); ?></a>
        <a href="https://yourwebsite.com/forums" class="button"><?php esc_html_e('Community Forums', 'mapifyme'); ?></a>
      </p>
    </div>
  <?php
  }

  /**
   * Render the settings page.
   */
  public function render_settings_page()
  {
  ?>
    <div class="wrap">
      <h1><?php esc_html_e('MapifyMe Settings', 'mapifyme'); ?></h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('mapifyme_settings_group');
        do_settings_sections('mapifyme-settings');
        submit_button();
        ?>
      </form>
    </div>
  <?php
  }

  /**
   * Render the MapifyMe Plugins page, displaying all registered plugins.
   */
  public function render_plugins_page()
  {
    if (isset($_GET['message']) && $_GET['message'] === 'plugin_updated') {
      add_settings_error('mapifyme_messages', 'plugin_updated', __('Plugin status updated.', 'mapifyme'), 'success');
    }
    settings_errors('mapifyme_messages'); // Display messages
    // Fetch the registered plugins from the MapifyMe_Plugins class
    $registered_plugins = MapifyMe_Plugins::get_registered_plugins();
  ?>
    <div class="wrap">
      <h1><?php esc_html_e('MapifyMe Plugins', 'mapifyme'); ?></h1>

      <table class="widefat fixed striped">
        <thead>
          <tr>
            <th><?php esc_html_e('Plugin Name', 'mapifyme'); ?></th>
            <th><?php esc_html_e('Version', 'mapifyme'); ?></th>
            <th><?php esc_html_e('Status', 'mapifyme'); ?></th>
            <th><?php esc_html_e('Directory', 'mapifyme'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($registered_plugins)) : ?>
            <?php foreach ($registered_plugins as $plugin) :
              // Skip hidden plugins
              if ($plugin->is_hidden_plugin) {
                continue;
              }

              $plugin_class = get_class($plugin);
              $is_active = MapifyMe_Plugins::is_plugin_active($plugin_class);
            ?>
              <tr>
                <!-- Display the plugin name instead of the class name -->
                <td><?php echo esc_html($plugin->name); ?></td>
                <td><?php echo esc_html($plugin->version); ?></td>
                <td>
                  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('mapifyme_plugin_activation', 'mapifyme_plugin_activation_nonce'); ?>
                    <input type="hidden" name="plugin_class" value="<?php echo esc_attr($plugin_class); ?>">
                    <input type="hidden" name="action" value="mapifyme_plugin_activation">

                    <!-- Disable the checkbox if allowed_disable is false -->
                    <input type="checkbox" name="plugin_status" value="1" <?php checked($is_active); ?>
                      <?php if (!$plugin->allowed_disable) echo 'disabled'; ?> onchange="this.form.submit()">

                    <?php echo esc_html($is_active ? __('Active', 'mapifyme') : __('Inactive', 'mapifyme')); ?>
                  </form>
                </td>
                <td><?php echo esc_html($plugin->plugin_dir); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else : ?>
            <tr>
              <td colspan="4"><?php esc_html_e('No plugins are registered.', 'mapifyme'); ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php
  }


  /**
   * Handle plugin activation and deactivation.
   */
  public function handle_plugin_activation()
  {
    // Verify the nonce
    if (isset($_POST['mapifyme_plugin_activation_nonce']) && wp_verify_nonce($_POST['mapifyme_plugin_activation_nonce'], 'mapifyme_plugin_activation')) {
      if (isset($_POST['plugin_class'])) {
        $plugin_class = sanitize_text_field($_POST['plugin_class']);
        $new_status = isset($_POST['plugin_status']) ? true : false;

        // Get the plugin object by class name
        foreach (MapifyMe_Plugins::get_registered_plugins() as $plugin) {
          if (get_class($plugin) === $plugin_class) {
            // Check if the plugin allows disabling
            if (!$plugin->allowed_disable) {
              add_settings_error('mapifyme_messages', 'plugin_disable_not_allowed', __('This plugin cannot be disabled.', 'mapifyme'), 'error');
              break;
            }

            if ($new_status) {
              // Activate the plugin if the checkbox is checked
              MapifyMe_Plugins::activate_plugin($plugin);
              add_settings_error('mapifyme_messages', 'plugin_activated', __('Plugin activated successfully!', 'mapifyme'), 'success');
            } else {
              // Deactivate the plugin if the checkbox is unchecked
              MapifyMe_Plugins::deactivate_plugin($plugin);
              add_settings_error('mapifyme_messages', 'plugin_deactivated', __('Plugin deactivated successfully!', 'mapifyme'), 'success');
            }
            break;
          }
        }
      }
    }

    // Redirect to prevent form resubmission and add feedback
    wp_redirect(admin_url('admin.php?page=mapifyme-plugins&message=plugin_updated'));
    exit;
  }



  /**
   * Register settings with WordPress Settings API.
   */
  public function register_settings()
  {
    register_setting('mapifyme_settings_group', 'mapifyme_settings', array($this, 'sanitize_settings'));

    // Map Provider Section
    add_settings_section(
      'mapifyme_map_provider_section',
      __('Map Provider Settings', 'mapifyme'),
      null,
      'mapifyme-settings'
    );

    add_settings_field(
      'map_provider',
      __('Choose Map Provider', 'mapifyme'),
      array($this, 'render_map_provider_field'),
      'mapifyme-settings',
      'mapifyme_map_provider_section'
    );

    add_settings_field(
      'google_maps_api_key',
      __('Google Maps API Key', 'mapifyme'),
      array($this, 'render_google_maps_api_key_field'),
      'mapifyme-settings',
      'mapifyme_map_provider_section'
    );

    // Post Types Section
    add_settings_section(
      'mapifyme_post_type_section',
      __('Post Type Settings', 'mapifyme'),
      null,
      'mapifyme-settings'
    );

    add_settings_field(
      'enabled_post_types',
      __('Enable Post Types for Geotagging', 'mapifyme'),
      array($this, 'render_post_types_field'),
      'mapifyme-settings',
      'mapifyme_post_type_section'
    );

    // Popup Template Section
    add_settings_section(
      'mapifyme_popup_template_section',
      __('Popup Template Settings', 'mapifyme'),
      null,
      'mapifyme-settings'
    );

    add_settings_field(
      'popup_template',
      __('Select Popup Template', 'mapifyme'),
      array($this, 'render_popup_template_field'),
      'mapifyme-settings',
      'mapifyme_popup_template_section'
    );

    // Rate Limit Section
    add_settings_section(
      'mapifyme_rate_limit_section',
      __('Rate Limiting Settings', 'mapifyme'),
      null,
      'mapifyme-settings'
    );

    add_settings_field(
      'rate_limit',
      __('Rate Limiting - Max Requests Per IP', 'mapifyme'),
      array($this, 'render_rate_limit_field'),
      'mapifyme-settings',
      'mapifyme_rate_limit_section'
    );

    add_settings_field(
      'rate_limit_time',
      __('Rate Limiting - Time Period (Minutes)', 'mapifyme'),
      array($this, 'render_rate_limit_time_field'),
      'mapifyme-settings',
      'mapifyme_rate_limit_section'
    );
  }

  /**
   * Sanitize settings before saving.
   */
  public function sanitize_settings($settings)
  {
    MapifyMe_DB::update_setting('map_provider', sanitize_text_field($settings['map_provider']));
    MapifyMe_DB::update_setting('google_maps_api_key', sanitize_text_field($settings['google_maps_api_key']));

    // Check if 'enabled_post_types' is set and is an array, otherwise set it to an empty array
    if (isset($settings['enabled_post_types']) && is_array($settings['enabled_post_types'])) {
      MapifyMe_DB::update_setting('enabled_post_types', array_map('sanitize_text_field', $settings['enabled_post_types']));
    } else {
      MapifyMe_DB::update_setting('enabled_post_types', array()); // Save an empty array if no post types are selected
    }

    MapifyMe_DB::update_setting('popup_template', sanitize_text_field($settings['popup_template'])); // Save popup template
    MapifyMe_DB::update_setting('rate_limit', intval($settings['rate_limit']));
    MapifyMe_DB::update_setting('rate_limit_time', intval($settings['rate_limit_time']));

    return $settings;
  }

  /**
   * Render the map provider field with Leaflet and Google Maps options.
   */
  public function render_map_provider_field()
  {
    $options = MapifyMe_DB::get_setting('map_provider', 'leaflet');
  ?>
    <select name="mapifyme_settings[map_provider]" id="map_provider">
      <option value="leaflet" <?php selected($options, 'leaflet'); ?>><?php esc_html_e('Leaflet (Default)', 'mapifyme'); ?></option>
      <option value="google_maps" <?php selected($options, 'google_maps'); ?>><?php esc_html_e('Google Maps', 'mapifyme'); ?></option>
    </select>
  <?php
  }

  /**
   * Render the Google Maps API key field.
   */
  public function render_google_maps_api_key_field()
  {
    $value = esc_attr(MapifyMe_DB::get_setting('google_maps_api_key', ''));
  ?>
    <div id="google_maps_api_key_wrapper">
      <input type="text" name="mapifyme_settings[google_maps_api_key]" id="google_maps_api_key" value="<?php echo esc_attr($value); ?>" />
      <p class="description"><?php esc_html_e('Enter your Google Maps API key. It will be validated when you save.', 'mapifyme'); ?></p>
    </div>
    <!-- This is where the feedback message will appear -->
    <div id="api-validation-feedback"></div>
    <?php
  }



  /**
   * Render post types selection field.
   */
  public function render_post_types_field()
  {
    $enabled_post_types = MapifyMe_DB::get_setting('enabled_post_types', array());
    $post_types = get_post_types(array('public' => true), 'objects');

    foreach ($post_types as $post_type) {
    ?>
      <label>
        <input type="checkbox" name="mapifyme_settings[enabled_post_types][]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $enabled_post_types, true)); ?> />
        <?php echo esc_html($post_type->label); ?>
      </label><br />
    <?php
    }
  }

  /**
   * Render the popup template selection field.
   */
  public function render_popup_template_field()
  {
    $selected_template = esc_attr(MapifyMe_DB::get_setting('popup_template', 'template1'));
    ?>
    <select name="mapifyme_settings[popup_template]" id="popup_template">
      <option value="template1" <?php selected($selected_template, 'template1'); ?>><?php esc_html_e('Template 1', 'mapifyme'); ?></option>
      <option value="template2" <?php selected($selected_template, 'template2'); ?>><?php esc_html_e('Template 2', 'mapifyme'); ?></option>
    </select>
    <p class="description"><?php esc_html_e('Choose a popup template for displaying map location details.', 'mapifyme'); ?></p>
  <?php
  }

  /**
   * Render rate limit field (number of requests per IP).
   */
  public function render_rate_limit_field()
  {
    $rate_limit = intval(MapifyMe_DB::get_setting('rate_limit', 10));
  ?>
    <input type="number" name="mapifyme_settings[rate_limit]" id="rate_limit" value="<?php echo esc_attr($rate_limit); ?>" min="1" />
    <p class="description"><?php esc_html_e('Set the maximum number of requests allowed per IP within the specified time period.', 'mapifyme'); ?></p>
  <?php
  }

  /**
   * Render rate limit time field (time period in minutes).
   */
  public function render_rate_limit_time_field()
  {
    $rate_limit_time = intval(MapifyMe_DB::get_setting('rate_limit_time', 10));
  ?>
    <input type="number" name="mapifyme_settings[rate_limit_time]" id="rate_limit_time" value="<?php echo esc_attr($rate_limit_time); ?>" min="1" />
    <p class="description"><?php esc_html_e('Set the time period (in minutes) during which the rate limit is enforced.', 'mapifyme'); ?></p>
<?php
  }

  /**
   * Enqueue admin scripts.
   */
  public function enqueue_admin_scripts($hook_suffix)
  {
    // Fetch the selected map provider
    $map_provider = MapifyMe_DB::get_setting('map_provider', 'leaflet');

    // Enqueue admin scripts for settings pages or global admin tasks
    if ($hook_suffix === 'mapifyme_page_mapifyme-settings') {
      // Enqueue admin scripts for the settings page
      wp_enqueue_script(
        'mapifyme-admin-script-settings',
        MAPIFYME_PLUGIN_URL . 'includes/admin/assets/js/mapifyme-admin.js',
        array('jquery'),
        filemtime(MAPIFYME_PLUGIN_DIR . 'includes/admin/assets/js/mapifyme-admin.js'),
        true
      );

      // Enqueue the Google API validation script if Google Maps is selected
      if ($map_provider === 'google_maps') {
        wp_enqueue_script(
          'mapifyme-admin-validate-api',
          MAPIFYME_PLUGIN_URL . 'includes/admin/assets/js/mapifyme-validate-api.js',
          array('jquery'),
          '1.0.0',
          true
        );

        // Localize the validation script to pass AJAX URL and nonce
        wp_localize_script('mapifyme-admin-validate-api', 'mapifymeApiValidation', array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('mapifyme_google_api_key_nonce'),
        ));
      }
    }

    // Enqueue scripts for post editor pages (map integration)
    if ($hook_suffix === 'post.php' || $hook_suffix === 'post-new.php') {
      if ($map_provider === 'leaflet') {
        // Enqueue Leaflet CSS and JS
        wp_enqueue_style('leaflet-css', MAPIFYME_PLUGIN_URL . 'assets/lib/leaflet/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet-js', MAPIFYME_PLUGIN_URL . 'assets/lib/leaflet/leaflet.js', array(), '1.9.4', true);

        // Enqueue initialize-maps.js for Leaflet
        wp_enqueue_script(
          'mapifyme-initialize-maps',
          MAPIFYME_PLUGIN_URL . 'includes/admin/assets/js/initialize-maps.js',
          array('leaflet-js', 'jquery'),
          '1.0.0',
          true
        );

        // Enqueue mapifyme-geotag.js for Leaflet
        wp_enqueue_script(
          'mapifyme-geotag',
          MAPIFYME_PLUGIN_URL . 'includes/admin/assets/js/mapifyme-geotag.js',
          array('mapifyme-initialize-maps', 'jquery'),
          '1.0.0',
          true
        );

        // Localize the mapifyme-geotag.js for Leaflet
        wp_localize_script('mapifyme-geotag', 'mapifymeGeotag', array(
          'geocode_api_url' => 'https://nominatim.openstreetmap.org/search',
          'reverse_geocode_api_url' => 'https://nominatim.openstreetmap.org/reverse',
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('mapifyme_update_geotag_data_nonce'),
        ));
      } elseif ($map_provider === 'google_maps') {
        // Enqueue Google Maps API script if Google Maps is selected
        $google_maps_api_key = MapifyMe_DB::get_setting('google_maps_api_key', '');
        if (!empty($google_maps_api_key)) {
          wp_enqueue_script(
            'google-maps-js',
            'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($google_maps_api_key),
            array(),
            '3',
            true
          );

          // Enqueue Google Maps initialization script
          wp_enqueue_script(
            'mapifyme-initialize-google-maps',
            MAPIFYME_PLUGIN_URL . 'includes/admin/assets/js/initialize-google-maps.js',
            array('google-maps-js', 'jquery'),
            '1.0.0',
            true
          );


          // Localize the Google Maps specific script
          wp_localize_script('mapifyme-initialize-google-maps', 'mapifymeGeotag', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mapifyme_update_geotag_data_nonce'),
          ));
        }
      }

      // Enqueue custom admin styles
      wp_enqueue_style(
        'mapifyme-admin-css',
        MAPIFYME_PLUGIN_URL . 'includes/admin/assets/css/admin.css',
        array(),
        '1.0.0'
      );
    }
  }
}
