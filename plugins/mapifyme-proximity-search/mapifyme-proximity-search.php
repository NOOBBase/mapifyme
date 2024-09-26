<?php

/**
 * Plugin Name: MapifyMe Proximity Search
 * Plugin URI: https://mapifyme.com
 * Description: Adds proximity search forms to MapifyMe for post types and BuddyPress members.
 * Version: 1.0.0
 * Author: NOOBBase
 * Text Domain: mapifyme-proximity-search
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('WPINC')) {
  die;
}

// Define constants
define('MAPIFYME_PROXIMITY_SEARCH_VERSION', '1.0.0'); // Add this line to define the version constant
define('MAPIFYME_PROXIMITY_SEARCH_DIR', plugin_dir_path(__FILE__));
define('MAPIFYME_PROXIMITY_SEARCH_URL', plugin_dir_url(__FILE__));

// Include core plugin classes
require_once MAPIFYME_PROXIMITY_SEARCH_DIR . 'includes/class-mapifyme-proximity-search-scripts.php';
require_once MAPIFYME_PROXIMITY_SEARCH_DIR . 'includes/class-mapifyme-proximity-search-widget.php';
require_once MAPIFYME_PROXIMITY_SEARCH_DIR . 'includes/class-mapifyme-proximity-search-shortcode.php';

/**
 * MapifyMe Proximity Search Plugin
 *
 * This class handles the registration and initialization of the Proximity Search widget and shortcode.
 */
class MapifyMe_Proximity_Search
{
  public $version = '1.0.0';
  public $status = 'active';
  public $plugin_dir;
  public $plugin_url;
  public $is_core = true;
  public $name = 'Proximity Search';
  public $slug = 'mapifyme-proximity-search';
  public $is_hidden_plugin = false;
  public $allowed_disable = false;

  public function __construct()
  {
    $this->plugin_dir = MAPIFYME_PROXIMITY_SEARCH_DIR;
    $this->plugin_url = MAPIFYME_PROXIMITY_SEARCH_URL;
  }

  public static function init()
  {
    add_action('widgets_init', array(__CLASS__, 'register_widget'));
    add_action('init', array(__CLASS__, 'register_shortcode'));
    new MapifyMe_Proximity_Search_Scripts();  // Initialize the script enqueue class
  }

  public static function register_widget()
  {
    require_once MAPIFYME_PROXIMITY_SEARCH_DIR . 'includes/class-mapifyme-proximity-search-widget.php';
    register_widget('MapifyMe_Proximity_Search_Widget');
  }

  public static function register_shortcode()
  {
    require_once MAPIFYME_PROXIMITY_SEARCH_DIR . 'includes/class-mapifyme-proximity-search-shortcode.php';
    new MapifyMe_Proximity_Search_Shortcode();
  }

  public static function activate() {}
  public static function deactivate() {}
}

// Instantiate and register the plugin with the MapifyMe Plugins system
$mapifyme_proximity_search = new MapifyMe_Proximity_Search();
MapifyMe_Plugins::register($mapifyme_proximity_search);
