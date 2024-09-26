<?php

/**
 * MapifyMe Plugins Class.
 *
 * This class centralizes plugin registration and initialization for MapifyMe.
 */
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Plugins
{
  /********** Required variables **********/
  public $name = '';                  // Plugin name
  public $slug = '';                  // Plugin slug (used for URLs, etc.)
  public $is_hidden_plugin = false;   // If true, the plugin is hidden from the UI
  public $allowed_disable = false;    // If false, this plugin cannot be disabled
  public $version = '1.0.0';            // Plugin version
  public $status = 'inactive';        // Plugin status (active/inactive)
  public $plugin_dir = false;         // Directory path for the plugin
  public $plugin_url = false;         // URL path for the plugin
  public $is_core = false;            // If true, this is a core plugin of MapifyMe

  /**
   * Collection of registered plugins
   *
   * @var array
   */
  private static $registered_plugins = array();

  /**
   * Option name for storing active plugins in the database.
   */
  private static $active_plugins_option = 'mapifyme_active_plugins';

  /********** Public methods **********/

  /**
   * Register a plugin object.
   *
   * @param object $plugin The plugin instance to register.
   */
  public static function register($plugin = false)
  {
    if (is_object($plugin)) {
      // Add the plugin object to the registered plugins array
      self::$registered_plugins[] = $plugin;

      // Automatically activate the plugin if the status is 'active'
      $active_plugins = get_option(self::$active_plugins_option, array());
      $plugin_class = get_class($plugin);

      if ($plugin->status === 'active' && !in_array($plugin_class, $active_plugins)) {
        self::activate_plugin($plugin); // Activate plugin if it's not already active
      }
    }
  }

  /**
   * Initialize all registered plugins.
   */
  public static function init_plugins()
  {
    $active_plugins = get_option(self::$active_plugins_option, array());

    foreach (self::$registered_plugins as $plugin) {
      $plugin_class = get_class($plugin);

      // Initialize only active plugins
      if (in_array($plugin_class, $active_plugins)) {
        // Initialize each plugin by calling its init() method
        if (method_exists($plugin, 'init')) {
          $plugin->init();
        }
      }
    }
  }

  /**
   * Activate a plugin.
   *
   * @param object $plugin The plugin object to activate.
   */
  public static function activate_plugin($plugin)
  {
    $active_plugins = get_option(self::$active_plugins_option, array());
    $plugin_class = get_class($plugin);

    if (!in_array($plugin_class, $active_plugins)) {
      $active_plugins[] = $plugin_class;
      update_option(self::$active_plugins_option, $active_plugins);
    }

    if (method_exists($plugin, 'activate')) {
      $plugin->activate();
    }
  }

  /**
   * Deactivate a plugin.
   *
   * @param object $plugin The plugin object to deactivate.
   */
  public static function deactivate_plugin($plugin)
  {
    $active_plugins = get_option(self::$active_plugins_option, array());
    $plugin_class = get_class($plugin);

    if (($key = array_search($plugin_class, $active_plugins)) !== false) {
      unset($active_plugins[$key]);
      update_option(self::$active_plugins_option, $active_plugins);
    }

    if (method_exists($plugin, 'deactivate')) {
      $plugin->deactivate();
    }
  }

  /**
   * Add submenu items to the MapifyMe admin menu.
   */
  public static function add_admin_menu_items()
  {
    foreach (self::$registered_plugins as $plugin) {
      if (method_exists($plugin, 'admin_menu_items')) {
        $menu_items = $plugin->admin_menu_items();
        if (!empty($menu_items)) {
          foreach ($menu_items as $item) {
            add_submenu_page(
              'mapifyme', // Parent slug
              $item['page_title'], // Page title
              $item['menu_title'], // Menu title
              $item['capability'], // Capability required
              $item['menu_slug'], // Menu slug
              $item['function'] // Function to display the page
            );
          }
        }
      }
    }
  }

  /**
   * Get all registered plugins.
   *
   * @return array
   */
  public static function get_registered_plugins()
  {
    return self::$registered_plugins;
  }

  /**
   * Get active plugins from the database.
   *
   * @return array
   */
  public static function get_active_plugins()
  {
    return get_option(self::$active_plugins_option, array());
  }

  /**
   * Check if a plugin is active.
   *
   * @param string $plugin_class The plugin class name.
   * @return bool
   */
  public static function is_plugin_active($plugin_class)
  {
    $active_plugins = self::get_active_plugins();
    return in_array($plugin_class, $active_plugins);
  }
}

// Hook to initialize plugins after all plugins are loaded
add_action('plugins_loaded', array('MapifyMe_Plugins', 'init_plugins'));

// Hook to add admin menu items
add_action('admin_menu', array('MapifyMe_Plugins', 'add_admin_menu_items'));
