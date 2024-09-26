<?php
if (!defined('WPINC')) {
  die;
}

class MapifyMe_Single_Location_Widget extends WP_Widget
{
  public function __construct()
  {
    parent::__construct(
      'mapifyme_single_location_widget',
      __('MapifyMe Single Location', 'mapifyme'),
      array('description' => __('Display a single location map.', 'mapifyme'))
    );
  }

  public function widget($args, $instance)
  {
    echo wp_kses_post($args['before_widget']);

    if (!empty($instance['title'])) {
      echo wp_kses_post($args['before_title']) . esc_html(apply_filters('widget_title', $instance['title'])) . wp_kses_post($args['after_title']);
    }

    // Fetch the default template from the settings table if no template is set
    $default_template = MapifyMe_DB::get_setting('popup_template', 'template1');

    // Use the widget instance value if set, otherwise fallback to the default template
    $popup_template = !empty($instance['popup_template']) ? $instance['popup_template'] : $default_template;

    // Check if 'location_id' is provided in the widget settings
    if (empty($instance['location_id'])) {
      global $post;
      if (isset($post->ID)) {
        $instance['location_id'] = $post->ID;  // Dynamically use the current post ID
      } else {
        echo '<p>' . esc_html__('No location specified.', 'mapifyme') . '</p>';

        return;
      }
    }

    // Build shortcode attributes from widget instance settings
    $shortcode_atts = array(
      'id' => $instance['location_id'],
      'type' => 'post',
      'popup_template' => $popup_template, // Use the template from the widget or default from settings
      'show_latitude' => isset($instance['show_latitude']) ? $instance['show_latitude'] : 'true',
      'show_longitude' => isset($instance['show_longitude']) ? $instance['show_longitude'] : 'true',
      'show_street' => isset($instance['show_street']) ? $instance['show_street'] : 'true',
      'show_city' => isset($instance['show_city']) ? $instance['show_city'] : 'true',
      'show_state' => isset($instance['show_state']) ? $instance['show_state'] : 'true',
      'show_zip' => isset($instance['show_zip']) ? $instance['show_zip'] : 'true',
      'show_country' => isset($instance['show_country']) ? $instance['show_country'] : 'true',
      'show_website' => isset($instance['show_website']) ? $instance['show_website'] : 'true',
      'show_email' => isset($instance['show_email']) ? $instance['show_email'] : 'true',
      'show_phone' => isset($instance['show_phone']) ? $instance['show_phone'] : 'true',
      'show_title' => isset($instance['show_title']) ? $instance['show_title'] : 'true',
      'show_content' => isset($instance['show_content']) ? $instance['show_content'] : 'true',
      'show_category' => isset($instance['show_category']) ? $instance['show_category'] : 'true',
      'show_tags' => isset($instance['show_tags']) ? $instance['show_tags'] : 'true',
      'show_author' => isset($instance['show_author']) ? $instance['show_author'] : 'true',
      'zoom' => isset($instance['zoom']) ? $instance['zoom'] : '13',
    );

    // Output the map using the shortcode
    echo do_shortcode('[mapifyme_single_location ' . http_build_query($shortcode_atts, '', ' ') . ']');

    echo wp_kses_post($args['after_widget']);
  }


  public function form($instance)
  {
    // Default values for fields
    $defaults = array(
      'title' => '',
      'location_id' => '',
      'popup_template' => '', // Allow it to be empty to fallback to the settings table
      'show_latitude' => 'true',
      'show_longitude' => 'true',
      'show_street' => 'true',
      'show_city' => 'true',
      'show_state' => 'true',
      'show_zip' => 'true',
      'show_country' => 'true',
      'show_website' => 'true',
      'show_email' => 'true',
      'show_phone' => 'true',
      'show_title' => 'true',
      'show_content' => 'true',
      'show_category' => 'true',
      'show_tags' => 'true',
      'show_author' => 'true',
      'zoom' => '13',
    );

    // Merge provided instance values with default values
    $instance = wp_parse_args((array) $instance, $defaults);

    // Output form fields for each setting
?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title', 'mapifyme'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>">
    </p>

    <p>
      <label for="<?php echo esc_attr($this->get_field_id('location_id')); ?>"><?php esc_html_e('Post ID to show(leave empty to fetch curernt post)', 'mapifyme'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('location_id')); ?>" name="<?php echo esc_attr($this->get_field_name('location_id')); ?>" type="text" value="<?php echo esc_attr($instance['location_id']); ?>">
    </p>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('zoom')); ?>"><?php esc_html_e('Zoom Level:', 'mapifyme'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('zoom')); ?>" name="<?php echo esc_attr($this->get_field_name('zoom')); ?>" type="number" value="<?php echo esc_attr($instance['zoom']); ?>">
    </p>


    <p>
      <label for="<?php echo esc_attr($this->get_field_id('popup_template')); ?>"><?php esc_html_e('Popup Template:', 'mapifyme'); ?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('popup_template')); ?>" name="<?php echo esc_attr($this->get_field_name('popup_template')); ?>" type="text" value="<?php echo esc_attr($instance['popup_template']); ?>" placeholder="<?php esc_html_e('Leave empty for default', 'mapifyme'); ?>">
    </p>

    <?php $this->render_checkbox($instance, 'show_latitude', __('Show Latitude', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_longitude', __('Show Longitude', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_street', __('Show Street', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_city', __('Show City', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_state', __('Show State', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_zip', __('Show Zip', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_country', __('Show Country', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_website', __('Show Website', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_email', __('Show Email', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_phone', __('Show Phone', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_title', __('Show Title', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_content', __('Show Content', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_category', __('Show Category', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_tags', __('Show Tags', 'mapifyme')); ?>
    <?php $this->render_checkbox($instance, 'show_author', __('Show Author', 'mapifyme')); ?>
  <?php
  }

  public function update($new_instance, $old_instance)
  {
    // Save all fields into the instance
    $instance = array();
    $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
    $instance['location_id'] = (!empty($new_instance['location_id'])) ? sanitize_text_field($new_instance['location_id']) : '';
    $instance['popup_template'] = (!empty($new_instance['popup_template'])) ? sanitize_text_field($new_instance['popup_template']) : '';
    $instance['zoom'] = (!empty($new_instance['zoom'])) ? sanitize_text_field($new_instance['zoom']) : '13';
    $fields = array(
      'show_latitude',
      'show_longitude',
      'show_street',
      'show_city',
      'show_state',
      'show_zip',
      'show_country',
      'show_website',
      'show_email',
      'show_phone',
      'show_title',
      'show_content',
      'show_category',
      'show_tags',
      'show_author'
    );

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
