<?php

/**
 * Registers custom post types used by the Archive & Grid Toolkit.
 */

defined('ABSPATH') || exit;

class AGT_CPT
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
    add_action('init', [$this, 'register_post_types']);
  }

  /**
   * Register the Resource and FAQ post types.
   */
  public function register_post_types()
  {
    // Resources CPT
    $labels = [
      'name'            => __('Resources', 'archive_grid_toolkit'),
      'singular_name'   => __('Resource', 'archive_grid_toolkit'),
      'menu_name'       => __('Resources', 'archive_grid_toolkit'),
      'name_admin_bar'  => __('Resource', 'archive_grid_toolkit'),
      'add_new'         => __('Add New', 'archive_grid_toolkit'),
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
      'menu_icon'          => 'dashicons-portfolio',
    ];
    register_post_type('resource', $args);

    // FAQ CPT
    $labels = [
      'name'               => __('FAQs', 'archive-grid-toolkit'),
      'singular_name'      => __('FAQ',  'archive-grid-toolkit'),
      'menu_name'          => __('FAQs', 'archive-grid-toolkit'),
      'name_admin_bar'     => __('FAQ',  'archive-grid-toolkit'),
      'add_new'            => __('Add New', 'archive-grid-toolkit'),
      'add_new_item'       => __('Add New FAQ', 'archive-grid-toolkit'),
      'edit_item'          => __('Edit FAQ',    'archive-grid-toolkit'),
      'new_item'           => __('New FAQ',     'archive-grid-toolkit'),
      'view_item'          => __('View FAQ',    'archive-grid-toolkit'),
      'search_items'       => __('Search FAQs', 'archive-grid-toolkit'),
      'not_found'          => __('No FAQs found', 'archive-grid-toolkit'),
      'not_found_in_trash' => __('No FAQs found in Trash', 'archive-grid-toolkit'),
    ];
    $args = [
      'labels'             => $labels,
      'public'             => true,
      'show_in_rest'       => true,
      'has_archive'        => false,
      'rewrite'            => ['slug' => 'faqs'],
      'supports'           => ['title', 'editor'],
      'menu_icon'          => 'dashicons-editor-help',
    ];
    register_post_type('faq', $args);
  }
}
