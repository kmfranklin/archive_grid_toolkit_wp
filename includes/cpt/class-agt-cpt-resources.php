<?php

/**
 * Registers Resource CPT
 */

defined('ABSPATH') || exit;

class AGT_CPT_Resource
{
  private static $instance;

  public static function get_instance()
  {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function __construct()
  {
    add_action('init', [$this, 'register_resource_cpt']);
  }

  public function register_resource_cpt()
  {
    // Resources CPT
    $labels = [
      'name'            => __('Resources', 'archive-grid-toolkit'),
      'singular_name'   => __('Resource', 'archive-grid-toolkit'),
      'menu_name'       => __('Resources', 'archive-grid-toolkit'),
      'name_admin_bar'  => __('Resource', 'archive-grid-toolkit'),
      'add_new'         => __('Add New', 'archive-grid-toolkit'),
      'add_new_item'       => __('Add New Resource',  'archive-grid-toolkit'),
      'edit_item'          => __('Edit Resource',     'archive-grid-toolkit'),
      'new_item'           => __('New Resource',      'archive-grid-toolkit'),
      'view_item'          => __('View Resource',     'archive-grid-toolkit'),
      'search_items'       => __('Search Resources',  'archive-grid-toolkit'),
      'not_found'          => __('No Resources found', 'archive-grid-toolkit'),
      'not_found_in_trash' => __('No Resources found in Trash', 'archive-grid-toolkit'),
    ];
    $args = [
      'labels'             => $labels,
      'public'             => true,
      'show_in_rest'       => true,
      'has_archive'        => false,
      'rewrite'            => ['slug' => 'resources'],
      'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
      'taxonomies'         => get_option('agt_resource_taxonomies', []),
      'menu_icon'          => 'dashicons-portfolio',
    ];
    register_post_type('resource', $args);
  }
}
