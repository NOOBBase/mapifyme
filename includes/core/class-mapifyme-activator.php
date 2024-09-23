<?php
if (! defined('WPINC')) {
  die;
}

class MapifyMe_Activator
{

  /**
   * Code to execute during plugin activation.
   */
  public static function activate()
  {
    // Create the settings table
    MapifyMe_DB::create_settings_table();

    // Create the geodata table
    MapifyMe_DB::create_data_table();  // <-- Add this line to create the geodata table

    // Other activation tasks
    flush_rewrite_rules();
  }
}
