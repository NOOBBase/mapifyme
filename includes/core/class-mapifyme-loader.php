<?php

if (! defined('WPINC')) {
  die;
}


class MapifyMe_Loader
{
  protected $actions = array();
  protected $filters = array();

  public function add_action($hook, $component, $callback)
  {
    $this->actions[] = array('hook' => $hook, 'component' => $component, 'callback' => $callback);
  }

  public function add_filter($hook, $component, $callback)
  {
    $this->filters[] = array('hook' => $hook, 'component' => $component, 'callback' => $callback);
  }

  public function run()
  {
    foreach ($this->actions as $action) {
      add_action($action['hook'], array($action['component'], $action['callback']));
    }

    foreach ($this->filters as $filter) {
      add_filter($filter['hook'], array($filter['component'], $filter['callback']));
    }
  }
}
