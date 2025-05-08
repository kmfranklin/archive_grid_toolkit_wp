<?php

/**
 * Registers FAQ CPT
 */

defined('ABSPATH') || exit;

class AGT_CPT_FAQ
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
    add_action('init', [$this, 'register_faq_cpt']);
  }

  public function register_faq_cpt()
  {
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
      'taxonomies'         => get_option('agt_faq_taxonomies'),
      'menu_icon'          => 'dashicons-editor-help',
    ];
    register_post_type('faq', $args);
  }
}
