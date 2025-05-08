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
    add_action('init',               [$this, 'register_faq_cpt']);
    add_action('add_meta_boxes',     [$this, 'add_meta_boxes']);
    add_action('save_post_faq',      [$this, 'save_meta'], 10, 2);
  }

  public function remove_default_meta_boxes()
  {
    remove_post_type_support('faq', 'editor');
    remove_meta_box('yoast_meta',    'faq', 'normal');
    remove_meta_box('smartcrawl_metabox', 'faq', 'normal');
    remove_meta_box('slugdiv',       'faq', 'normal');
    remove_meta_box('authordiv',     'faq', 'normal');
    remove_meta_box('commentstatusdiv', 'faq', 'normal');
    remove_meta_box('commentsdiv',   'faq', 'normal');
    remove_meta_box('postcustom',    'faq', 'normal');
  }

  public function add_meta_boxes()
  {
    add_meta_box(
      'agt_faq_fields',
      __('Question & Answer', 'archive-grid-toolkit'),
      [$this, 'render_faq_fields'],
      'faq',
      'normal',
      'high'
    );
  }

  public function render_faq_fields($post)
  {
    wp_nonce_field('agt_save_faq_fields', 'agt_faq_fields_nonce');
    $q = get_post_meta($post->ID, '_agt_faq_question', true);
    $a = get_post_meta($post->ID, '_agt_faq_answer', true);
    echo '<p><label>' . __('Question', 'archive-grid-toolkit') . '</label><br />';
    echo '<input type="text" name="agt_faq_question" value="' . esc_attr($q) . '" style="width:100%;" /></p>';
    echo '<p><label>' . __('Answer', 'archive-grid-toolkit') . '</label><br />';
    echo '<textarea name="agt_faq_answer" rows="5" style="width:100%;">' . esc_textarea($a) . '</textarea></p>';
  }

  public function save_meta($post_id, $post)
  {
    if (
      ! isset($_POST['agt_faq_fields_nonce'])
      || ! wp_verify_nonce($_POST['agt_faq_fields_nonce'], 'agt_save_faq_fields')
    ) {
      return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }
    if (isset($_POST['agt_faq_question'])) {
      update_post_meta(
        $post_id,
        '_agt_faq_question',
        sanitize_text_field($_POST['agt_faq_question'])
      );
    }
    if (isset($_POST['agt_faq_answer'])) {
      update_post_meta(
        $post_id,
        '_agt_faq_answer',
        sanitize_textarea_field($_POST['agt_faq_answer'])
      );
    }
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
      'supports'           => ['title'],
      'taxonomies'         => get_option('agt_faq_taxonomies', []),
      'menu_icon'          => 'dashicons-editor-help',
    ];
    register_post_type('faq', $args);
  }
}
