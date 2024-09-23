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
  }

  /**
   * Add the MapifyMe admin menu.
   */
  public function add_admin_menu()
  {
    add_menu_page(
      __('MapifyMe Settings', 'mapifyme'),
      __('MapifyMe', 'mapifyme'),
      'manage_options',
      'mapifyme-settings',
      array($this, 'render_settings_page'),
      'dashicons-location-alt',
      80
    );
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
    MapifyMe_DB::update_setting('enabled_post_types', array_map('sanitize_text_field', $settings['enabled_post_types']));
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
    <input type="text" name="mapifyme_settings[google_maps_api_key]" id="google_maps_api_key" value="<?php echo esc_attr($value); ?>" />
    <p class="description"><?php esc_html_e('Enter your Google Maps API key if you are using Google Maps as the provider.', 'mapifyme'); ?></p>
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
    if ($hook_suffix === 'post.php' || $hook_suffix === 'post-new.php') {
      // Enqueue Leaflet CSS and JS first
      wp_enqueue_style('leaflet-css', MAPIFYME_PLUGIN_URL . 'assets/lib/leaflet/leaflet.css', array(), '1.9.4');
      wp_enqueue_script('leaflet-js', MAPIFYME_PLUGIN_URL . 'assets/lib/leaflet/leaflet.js', array(), "1.9.4", true);

      // Enqueue initialize-maps.js (our main map initialization script)
      wp_enqueue_script(
        'mapifyme-initialize-maps',
        MAPIFYME_PLUGIN_URL . 'includes/admin/assets/js/initialize-maps.js',
        array('leaflet-js', 'jquery'),
        "1.0.0",
        true
      );

      // Enqueue mapifyme-geotag.js, which depends on initialize-maps.js
      wp_enqueue_script(
        'mapifyme-geotag',
        MAPIFYME_PLUGIN_URL . 'includes/admin/assets/js/mapifyme-geotag.js',
        array('mapifyme-initialize-maps', 'jquery'),
        "1.0.0",
        true
      );

      // Enqueue other custom admin scripts if necessary
      wp_enqueue_script(
        'mapifyme-admin-script',
        MAPIFYME_PLUGIN_URL . 'includes/admin/assets/js/mapifyme-admin.js',
        array('jquery'),
        "1.0.0",
        true
      );

      // Custom styles for the admin map
      wp_enqueue_style(
        'mapifyme-admin-css',
        MAPIFYME_PLUGIN_URL .
          'includes/admin/assets/css/admin.css',
        array(),
        '1.0.0'
      );

      // Localize the mapifyme-geotag.js for AJAX and geocoding URLs
      wp_localize_script('mapifyme-geotag', 'mapifymeGeotag', array(
        'geocode_api_url' => 'https://nominatim.openstreetmap.org/search',
        'reverse_geocode_api_url' => 'https://nominatim.openstreetmap.org/reverse',
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mapifyme_update_geotag_data_nonce'),
      ));
    }

    // Enqueue admin scripts for settings pages or global admin tasks
    if ($hook_suffix === 'toplevel_page_mapifyme-settings') {
      $script_path = MAPIFYME_PLUGIN_DIR . 'includes/admin/assets/js/mapifyme-admin.js';
      $script_url = MAPIFYME_PLUGIN_URL . 'includes/admin/assets/js/mapifyme-admin.js';
      $script_version = filemtime($script_path);
      wp_enqueue_script(
        'mapifyme-admin-script-settings',
        $script_url,
        array('jquery'),
        $script_version,
        true
      );
    }
  }
}
