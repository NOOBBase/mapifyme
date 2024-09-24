<?php
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Current_Location_Widget extends WP_Widget
{
  public function __construct()
  {
    parent::__construct(
      'mapifyme_current_location_widget',
      __('MapifyMe Current Location', 'mapifyme'),
      array('description' => __('Display the user\'s current location on a map.', 'mapifyme'))
    );
  }

  public function widget($args, $instance)
  {
    echo wp_kses_post($args['before_widget']);

    if (!empty($instance['title'])) {
      echo wp_kses_post($args['before_title']) . esc_html(apply_filters('widget_title', $instance['title'])) . wp_kses_post($args['after_title']);
    }

    // Build shortcode attributes from widget instance settings
    $shortcode_atts = array(
      'show_latitude' => isset($instance['show_latitude']) ? $instance['show_latitude'] : 'true',
      'show_longitude' => isset($instance['show_longitude']) ? $instance['show_longitude'] : 'true',
      'show_map' => isset($instance['show_map']) ? $instance['show_map'] : 'true',
      'map_height' => isset($instance['map_height']) ? $instance['map_height'] : '400px',
      'map_width' => isset($instance['map_width']) ? $instance['map_width'] : '100%',
      'show_address' => isset($instance['show_address']) ? $instance['show_address'] : 'true',
    );

    // Output the map using the shortcode
    echo do_shortcode('[mapifyme_current_location ' . http_build_query($shortcode_atts, '', ' ') . ']');

    echo wp_kses_post($args['after_widget']);
  }

  public function form($instance)
  {
    // Default values for fields
    $defaults = array(
      'title' => '',
      'show_latitude' => 'true',
      'show_longitude' => 'true',
      'show_map' => 'true',
      'map_height' => '400px',
      'map_width' => '100%',
      'show_address' => 'true',
    );

    // Merge provided instance values with default values
    $instance = wp_parse_args((array)$instance, $defaults);
?>

    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title', 'mapifyme'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>">
    </p>

    <p>
      <label for="<?php echo esc_attr($this->get_field_id('map_height')); ?>"><?php esc_html_e('Map Height (e.g., 400px):', 'mapifyme'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('map_height')); ?>" name="<?php echo esc_attr($this->get_field_name('map_height')); ?>" type="text" value="<?php echo esc_attr($instance['map_height']); ?>">
    </p>

    <p>
      <label for="<?php echo esc_attr($this->get_field_id('map_width')); ?>"><?php esc_html_e('Map Width (e.g., 100%):', 'mapifyme'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('map_width')); ?>" name="<?php echo esc_attr($this->get_field_name('map_width')); ?>" type="text" value="<?php echo esc_attr($instance['map_width']); ?>">
    </p>

    <?php $this->render_checkbox($instance, 'show_latitude', __('Show Latitude', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_longitude', __('Show Longitude', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_map', __('Show Map', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_address', __('Show Address', 'mapifyme')); ?>
  <?php
  }

  public function update($new_instance, $old_instance)
  {
    // Save all fields into the instance
    $instance = array();
    $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
    $instance['map_height'] = (!empty($new_instance['map_height'])) ? sanitize_text_field($new_instance['map_height']) : '400px';
    $instance['map_width'] = (!empty($new_instance['map_width'])) ? sanitize_text_field($new_instance['map_width']) : '100%';

    $fields = array('show_latitude', 'show_longitude', 'show_map', 'show_address');

    foreach ($fields as $field) {
      $instance[$field] = (!empty($new_instance[$field])) ? 'true' : 'false';
    }

    return $instance;
  }

  private function render_checkbox($instance, $field, $label)
  {
  ?>
    <p>
      <input class="checkbox" type="checkbox" <?php checked($instance[$field], 'true'); ?> id="<?php echo esc_attr($this->get_field_id($field)); ?>" name="<?php echo esc_attr($this->get_field_name($field)); ?>" value="true">
      <label for="<?php echo esc_attr($this->get_field_id($field)); ?>"><?php echo esc_html($label); ?></label>
    </p>
<?php
  }
}
