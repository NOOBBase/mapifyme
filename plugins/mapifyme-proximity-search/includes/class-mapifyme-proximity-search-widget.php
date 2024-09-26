<?php
// Prevent direct access
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Proximity_Search_Widget extends WP_Widget
{

  public function __construct()
  {
    parent::__construct(
      'mapifyme_proximity_search_widget',
      __('MapifyMe Proximity Search', 'mapifyme'),
      array('description' => __('A widget to display the MapifyMe Proximity Search form.', 'mapifyme'))
    );

    add_action('widgets_init', function () {
      register_widget('MapifyMe_Proximity_Search_Widget');
    });
  }

  public function widget($args, $instance)
  {
    echo $args['before_widget'];
    echo do_shortcode('[mapifyme_proximity_search]');
    echo $args['after_widget'];
  }

  public function form($instance)
  {
    // Widget form settings in the admin
  }
}
