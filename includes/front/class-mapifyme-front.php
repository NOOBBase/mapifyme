<?php
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Front
{
  public function __construct()
  {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
  }

  // Enqueue the required scripts for the front-end based on the map provider
  public function enqueue_scripts()
  {
    // Enqueue custom popup template styles with a version number
    wp_enqueue_style(
      'mapifyme-popup-templates-css',
      MAPIFYME_PLUGIN_URL . 'assets/css/mapifyme-popup-templates.css',
      array(),
      filemtime(MAPIFYME_PLUGIN_DIR . 'assets/css/mapifyme-popup-templates.css')
    );
  }
}
