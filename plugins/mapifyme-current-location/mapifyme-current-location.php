<?php

/**
 * MapifyMe Current Location Module Loader
 *
 * This file loads the Current Location widget and shortcode for the MapifyMe plugin.
 */

// Prevent direct access
if (!defined('WPINC')) {
  die;
}

// Define the path to the Current Location module directory
define('MAPIFYME_CURRENT_LOCATION_DIR', plugin_dir_path(__FILE__));
define('MAPIFYME_CURRENT_LOCATION_URL', plugin_dir_url(__FILE__));

/**
 * MapifyMe Current Location Plugin
 *
 * This class handles the registration and initialization of the Current Location widget and shortcode.
 */
class MapifyMe_Current_Location
{
  /**
   * Plugin version
   * 
   * @var string
   */
  public $version = '1.0.0';

  /**
   * Plugin activation status
   * 
   * @var string
   */
  public $status = 'active';

  /**
   * Plugin directory
   * 
   * @var string
   */
  public $plugin_dir;

  /**
   * Plugin URL
   * 
   * @var string
   */
  public $plugin_url;

  /**
   * Is this a core plugin?
   * 
   * @var boolean
   */
  public $is_core = true;

  /******** Newly Added Variables ********/

  /**
   * Plugin name
   * 
   * @var string
   */
  public $name = 'Current Location';

  /**
   * Plugin slug
   * 
   * @var string
   */
  public $slug = 'mapifyme-current-location';

  /**
   * Is this a hidden plugin?
   * 
   * @var boolean
   */
  public $is_hidden_plugin = false;

  /**
   * Can this plugin be disabled?
   * 
   * @var boolean
   */
  public $allowed_disable = false;

  /**
   * Constructor: Initializes the plugin and sets values for directory and URL.
   */
  public function __construct()
  {
    $this->plugin_dir = MAPIFYME_CURRENT_LOCATION_DIR;
    $this->plugin_url = MAPIFYME_CURRENT_LOCATION_URL;
  }

  /**
   * Initialize the plugin.
   * 
   * This method will register the widget and the shortcode.
   */
  public static function init()
  {
    // Register the widget
    add_action('widgets_init', array(__CLASS__, 'register_widget'));

    // Register the shortcode
    add_action('init', array(__CLASS__, 'register_shortcode'));
  }

  /**
   * Register the current Location widget.
   */
  public static function register_widget()
  {
    require_once MAPIFYME_CURRENT_LOCATION_DIR . 'includes/class-mapifyme-current-location-widget.php';
    register_widget('MapifyMe_Current_Location_Widget');
  }

  /**
   * Register the Current Location shortcode.
   */
  public static function register_shortcode()
  {
    require_once MAPIFYME_CURRENT_LOCATION_DIR . 'includes/class-mapifyme-current-location-shortcode.php';
    new MapifyMe_Current_Location_Shortcode();
  }

  /**
   * Provide submenu items for this plugin.
   * 
   * @return array
   */
  public static function admin_menu_items()
  {
    return array(
      array(
        'page_title' => 'Current Location Settings',
        'menu_title' => 'Current Location',
        'capability' => 'manage_options',
        'menu_slug'  => 'mapifyme-current-location-settings',
        'function'   => array(__CLASS__, 'settings_page')
      )
    );
  }

  /**
   * Plugin activation logic.
   */
  public static function activate()
  {
    // Add activation logic if needed
  }

  /**
   * Plugin deactivation logic.
   */
  public static function deactivate()
  {
    // Add deactivation logic if needed
  }

  /**
   * Settings page callback.
   */
  public static function settings_page()
  {
    echo '<div class="wrap"><h1>Current Location Settings</h1>';
    // Add content for the settings page here.
    echo '</div>';
  }
}

// Instantiate and register the plugin with the MapifyMe Plugins system
$mapifyme_current_location = new MapifyMe_Current_Location();
MapifyMe_Plugins::register($mapifyme_current_location);
