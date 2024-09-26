<?php
if (! defined('WPINC')) {
  die;
}

class MapifyMe_DB
{
  /**
   * Create the custom table for settings on plugin activation.
   */
  public static function create_settings_table()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mapifyme_settings';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL to create the table
    $sql = "CREATE TABLE `$table_name` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_name varchar(191) NOT NULL,
            setting_value longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE (setting_name)
        ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  /**
   * Insert or update a setting in the custom table.
   * Cache is cleared after updating.
   */
  public static function update_setting($setting_name, $setting_value)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mapifyme_settings';

    // Generate a cache key based on the setting name
    $cache_key = 'mapifyme_setting_exists_' . $setting_name;

    // Check the cache first to see if the setting exists
    $exists = wp_cache_get($cache_key, 'mapifyme');

    if (false === $exists) {
      // Cache miss: Check if the setting exists in the database
      $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `$table_name` WHERE setting_name = %s",
        $setting_name
      ));

      // Set the cache for future queries
      wp_cache_set($cache_key, $exists, 'mapifyme');
    }

    if ($exists) {
      // Update existing setting
      $wpdb->update(
        $table_name,
        array('setting_value' => maybe_serialize($setting_value)),
        array('setting_name' => $setting_name),
        array('%s'),
        array('%s')
      );
    } else {
      // Insert new setting
      $wpdb->insert(
        $table_name,
        array(
          'setting_name' => $setting_name,
          'setting_value' => maybe_serialize($setting_value)
        ),
        array('%s', '%s')
      );
    }

    // Invalidate both the cache for the existence check and the setting itself
    wp_cache_delete($cache_key, 'mapifyme');
    wp_cache_delete('mapifyme_setting_' . $setting_name, 'mapifyme');
  }


  /**
   * Retrieve a setting from the custom table.
   * Uses cache to avoid direct DB hits.
   */
  public static function get_setting($setting_name, $default = false)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mapifyme_settings';

    // Check cache first
    $cache_key = 'mapifyme_setting_' . $setting_name;
    $cached_result = wp_cache_get($cache_key, 'mapifyme');

    if (false === $cached_result) {
      // Cache miss: Retrieve from DB
      $result = $wpdb->get_var($wpdb->prepare(
        "SELECT setting_value FROM `$table_name` WHERE setting_name = %s",
        $setting_name
      ));

      if ($result) {
        wp_cache_set($cache_key, $result, 'mapifyme');
      }
    } else {
      // Cache hit: Use cached result
      $result = $cached_result;
    }

    if ($result) {
      return maybe_unserialize($result);
    }

    return $default;
  }

  /**
   * Create the custom table for geotag, address, and contact details on plugin activation.
   */
  public static function create_data_table()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mapifyme_geodata';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE `$table_name` (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      post_id bigint(20) NOT NULL,
      latitude varchar(255),
      longitude varchar(255),
      street varchar(255),
      city varchar(255),
      state varchar(255),
      zip varchar(255),
      country varchar(255),
      website varchar(255),
      email varchar(255),
      phone varchar(255),
      PRIMARY KEY (id),
      UNIQUE(post_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  /**
   * Insert or update geotag, address, and contact data for a post.
   * Cache is cleared after updating.
   */
  public static function update_post_data($post_id, $data)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mapifyme_geodata';

    // Generate a cache key based on the post ID for the existence check
    $cache_key = 'mapifyme_post_exists_' . $post_id;

    // Check the cache first to see if the post data exists
    $exists = wp_cache_get($cache_key, 'mapifyme');

    if (false === $exists) {
      // Cache miss: Check if post data exists in the database
      $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `$table_name` WHERE post_id = %d",
        $post_id
      ));

      // Set the cache for future queries
      wp_cache_set($cache_key, $exists, 'mapifyme');
    }

    if ($exists) {
      // Update existing post data
      $updated = $wpdb->update(
        $table_name,
        $data,
        array('post_id' => $post_id),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'), // 10 '%s's
        array('%d')
      );

      if ($updated === false) {
        error_log('MapifyMe_DB: Failed to update geotag data for Post ID ' . $post_id . '. Error: ' . $wpdb->last_error);
        return false;
      }
    } else {
      // Insert new post data
      $data['post_id'] = $post_id;
      $inserted = $wpdb->insert(
        $table_name,
        $data,
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') // 11 format specifiers
      );

      if ($inserted === false) {
        error_log('MapifyMe_DB: Failed to insert geotag data for Post ID ' . $post_id . '. Error: ' . $wpdb->last_error);
        return false;
      }
    }

    // Invalidate both the cache for the existence check and the actual post data
    wp_cache_delete($cache_key, 'mapifyme');
    wp_cache_delete('mapifyme_post_data_' . $post_id, 'mapifyme');

    return true;
  }


  /**
   * Retrieve geotag, address, and contact data for a post.
   * Uses cache to avoid direct DB hits.
   */
  public static function get_post_data($post_id)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mapifyme_geodata';

    // Check cache first
    $cache_key = 'mapifyme_post_data_' . $post_id;
    $cached_result = wp_cache_get($cache_key, 'mapifyme');

    if (false === $cached_result) {
      // Cache miss: Retrieve from DB
      $result = $wpdb->get_row(
        $wpdb->prepare(
          "SELECT * FROM `$table_name` WHERE post_id = %d",
          $post_id
        ),
        ARRAY_A
      );

      if ($result) {
        wp_cache_set($cache_key, $result, 'mapifyme');
      }
    } else {
      // Cache hit: Use cached result
      $result = $cached_result;
    }

    return $result;
  }

  /**
   * Retrieve posts with geotag data within a specified radius using the Haversine formula.
   *
   * @param float $latitude The center latitude.
   * @param float $longitude The center longitude.
   * @param float $radius The radius in kilometers.
   * @param string $post_type The post type to filter (optional).
   * @return array The list of posts with geodata.
   */
  public static function get_posts_within_radius($latitude, $longitude, $radius, $post_type = 'post')
  {
    global $wpdb;
    $geodata_table = $wpdb->prefix . 'mapifyme_geodata';
    $posts_table = $wpdb->prefix . 'posts';

    $query = $wpdb->prepare(
      "
        SELECT p.ID, p.post_title, p.post_content, geo.latitude, geo.longitude,
        (6371 * acos(
            cos(radians(%f)) * cos(radians(geo.latitude)) *
            cos(radians(geo.longitude) - radians(%f)) +
            sin(radians(%f)) * sin(radians(geo.latitude))
        )) AS distance
        FROM $geodata_table geo
        INNER JOIN $posts_table p ON p.ID = geo.post_id
        WHERE p.post_type = %s
        AND p.post_status = 'publish'
        HAVING distance < %f
        ORDER BY distance ASC
        ",
      $latitude,
      $longitude,
      $latitude,
      $post_type,
      $radius
    );

    return $wpdb->get_results($query, ARRAY_A);
  }


  /**
   * Retrieve all posts with geotag data.
   *
   * @return array The list of all geotagged posts.
   */
  public static function get_all_geotagged_posts()
  {
    global $wpdb;

    $geodata_table = $wpdb->prefix . 'mapifyme_geodata';
    $posts_table = $wpdb->prefix . 'posts';

    // Query all posts with valid latitude and longitude, including post_content
    $query = "
        SELECT p.ID, p.post_title, p.post_content, p.post_type, geo.latitude, geo.longitude
        FROM $posts_table p
        INNER JOIN $geodata_table geo ON p.ID = geo.post_id
        WHERE p.post_status = 'publish'
        AND geo.latitude IS NOT NULL
        AND geo.longitude IS NOT NULL
    ";

    return $wpdb->get_results($query, ARRAY_A);
  }


  public static function get_posts_by_location($latitude = null, $longitude = null, $street = '', $city = '', $state = '', $zip = '', $country = '', $radius = 10)
  {
    global $wpdb;
    $geodata_table = $wpdb->prefix . 'mapifyme_geodata';
    $posts_table = $wpdb->prefix . 'posts';

    // Base query to join the posts and geodata tables
    $query = "
        SELECT p.ID, p.post_title, p.post_content, geo.latitude, geo.longitude,
        (6371 * acos(
            cos(radians(%f)) * cos(radians(geo.latitude)) *
            cos(radians(geo.longitude) - radians(%f)) +
            sin(radians(%f)) * sin(radians(geo.latitude))
        )) AS distance
        FROM $posts_table p
        INNER JOIN $geodata_table geo ON p.ID = geo.post_id
        WHERE p.post_status = 'publish'
    ";

    // Initialize parameters for the query
    $params = [];
    $has_location = false;

    // Phase 1: Check if latitude and longitude are provided
    if (!empty($latitude) && !empty($longitude)) {
      $params = [$latitude, $longitude, $latitude];
      $has_location = true;
    } else {
      // Phase 2: Try to find latitude and longitude based on provided city, street, etc.
      $location_query = "SELECT latitude, longitude FROM $geodata_table WHERE 1=1";
      $location_params = [];

      if (!empty($street)) {
        $location_query .= ' AND street LIKE %s';
        $location_params[] = '%' . $wpdb->esc_like($street) . '%';
      }
      if (!empty($city)) {
        $location_query .= ' AND city LIKE %s';
        $location_params[] = '%' . $wpdb->esc_like($city) . '%';
      }
      if (!empty($state)) {
        $location_query .= ' AND state LIKE %s';
        $location_params[] = '%' . $wpdb->esc_like($state) . '%';
      }
      if (!empty($zip)) {
        $location_query .= ' AND zip LIKE %s';
        $location_params[] = '%' . $wpdb->esc_like($zip) . '%';
      }
      if (!empty($country)) {
        $location_query .= ' AND country LIKE %s';
        $location_params[] = '%' . $wpdb->esc_like($country) . '%';
      }

      // Get the first matching row with latitude and longitude
      $location_row = $wpdb->get_row($wpdb->prepare($location_query, ...$location_params), ARRAY_A);

      if ($location_row && !empty($location_row['latitude']) && !empty($location_row['longitude'])) {
        $latitude = $location_row['latitude'];
        $longitude = $location_row['longitude'];
        $params = [$latitude, $longitude, $latitude];
        $has_location = true;
      }
    }

    // Phase 3: If we have latitude and longitude, proceed with the proximity search
    if ($has_location) {
      // Continue building the main query
      $query .= " HAVING distance <= %d ORDER BY distance ASC";
      $params[] = $radius;

      // Prepare and execute the main proximity query
      $prepared_query = $wpdb->prepare($query, ...$params);
      return $wpdb->get_results($prepared_query, ARRAY_A);
    } else {
      // No latitude and longitude could be found, return an empty result
      return [];
    }
  }
}
