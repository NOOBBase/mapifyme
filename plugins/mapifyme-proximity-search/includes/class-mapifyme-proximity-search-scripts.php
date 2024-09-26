<?php
// Prevent direct access
if (!defined('WPINC')) {
  die;
}


class MapifyMe_Proximity_Search_Scripts
{

  public function __construct()
  {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
  }

  public function enqueue_scripts()
  {
    wp_enqueue_style('mapifyme-proximity-search', MAPIFYME_PROXIMITY_SEARCH_URL . 'assets/css/proximity-search.css', array(), MAPIFYME_PROXIMITY_SEARCH_VERSION);
    wp_enqueue_script('mapifyme-proximity-search', MAPIFYME_PROXIMITY_SEARCH_URL . 'assets/js/proximity-search.js', array('jquery'), MAPIFYME_PROXIMITY_SEARCH_VERSION, true);
  }
}
