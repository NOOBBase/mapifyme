<?php
if (! defined('WPINC')) {
  die;
}

class MapifyMe_Deactivator
{
  public static function deactivate()
  {
    // Code to execute on plugin deactivation
    flush_rewrite_rules();
  }
}
