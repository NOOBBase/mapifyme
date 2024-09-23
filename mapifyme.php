<?php

/**
 * Plugin Name: MapifyMe
 * Plugin URI:        https://mapifyme.com
 * Author URI:        https://mapifyme.com
 * Description: A modular geolocation plugin for WordPress with advanced proximity search and geotagging features.
 * Version: 1.0.0
 * Requires at least: 5.6
 * Tested up to:      6.6.1
 * Author: NOOBBase
 * Text Domain: mapifyme
 * Domain Path: /languages
 * Requires PHP:      7.0
 * License:           GNU General Public License v2.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('WPINC')) {
  die;
}

// Define constants
define('MAPIFYME_VERSION', '1.0.0');
define('MAPIFYME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAPIFYME_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MAPIFYME_PLUGINS_DIR', MAPIFYME_PLUGIN_DIR . 'plugins/');

// Include core files
require_once MAPIFYME_PLUGIN_DIR . 'includes/core/class-mapifyme-loader.php';
require_once MAPIFYME_PLUGIN_DIR . 'includes/core/class-mapifyme-activator.php';
require_once MAPIFYME_PLUGIN_DIR . 'includes/core/class-mapifyme-deactivator.php';
require_once MAPIFYME_PLUGIN_DIR . 'includes/core/class-mapifyme-db.php';
require_once MAPIFYME_PLUGIN_DIR . 'includes/admin/class-mapifyme-admin.php';
require_once MAPIFYME_PLUGIN_DIR . 'includes/modules/geotag-post-types/class-mapifyme-geotag-post.php';
require_once MAPIFYME_PLUGIN_DIR . 'includes/modules/maps/class-mapifyme-maps.php';
require_once MAPIFYME_PLUGIN_DIR . 'includes/front/class-mapifyme-front.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('MapifyMe_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('MapifyMe_Deactivator', 'deactivate'));

/**
 * Load all plugin files from the "plugins" directory
 */
function mapifyme_load_plugins_from_directory()
{
  $plugins_dir = MAPIFYME_PLUGINS_DIR;

  if (is_dir($plugins_dir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugins_dir));
    foreach ($iterator as $file) {
      if ($file->isFile() && $file->getExtension() === 'php') {
        include_once $file->getPathname();
      }
    }
  } else {
    error_log('MapifyMe: Plugin directory not found: ' . $plugins_dir);
  }
}

/**
 * Initialize the plugin
 */
function run_mapifyme()
{
  // Initialize the plugin loader
  $plugin_loader = new MapifyMe_Loader();

  // Admin-related functionality
  if (is_admin()) {
    $admin = new MapifyMe_Admin();
    $plugin_loader->add_action('admin_menu', $admin, 'add_admin_menu');
    $plugin_loader->add_action('admin_init', $admin, 'register_settings');
    $plugin_loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_admin_scripts');
  }

  // Front-end functionality (including styles and scripts)
  if (!is_admin()) {
    $front = new MapifyMe_Front();
    $plugin_loader->add_action('wp_enqueue_scripts', $front, 'enqueue_scripts');
    $maps = new MapifyMe_Maps();
    $plugin_loader->add_action('wp_enqueue_scripts', $maps, 'enqueue_map_processor_scripts');
  }

  $geotag_post = new MapifyMe_Geotag_Post();

  // Load additional plugins from the "plugins" directory
  mapifyme_load_plugins_from_directory();

  // Run the plugin loader to hook everything
  $plugin_loader->run();
}

run_mapifyme();
