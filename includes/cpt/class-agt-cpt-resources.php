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
    add_action('init',                  [$this, 'register_resource_cpt']);
    add_action('add_meta_boxes',        [$this, 'add_meta_boxes']);
    add_action('save_post_resource',    [$this, 'save_meta'], 10, 2);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_media_uploader']);
  }

  public function remove_default_meta_boxes()
  {
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
      'agt_resource_media',
      __('Resource Media', 'archive-grid-toolkit'),
      [$this, 'render_media_box'],
      'resource',
      'normal',
      'default',
    );
  }

  public function render_media_box($post)
  {
    wp_nonce_field('agt_save_resource_media', 'agt_resource_media_nonce');
    $url = get_post_meta($post->ID, '_agt_resource_media', true);
    echo '<p><input type="text" name="agt_resource_media" value="' . esc_attr($url) . '" style="width:75%;" placeholder="Enter URL or select via uploader">';
    echo ' <button class="button js-agt-media-upload">' . esc_html__('Select Media', 'archive-grid-toolkit') . '</button></p>';
  }

  public function enqueue_media_uploader($hook)
  {
    // only load on resource add/edit screens
    if (
      in_array($hook, ['post.php', 'post-new.php'], true)
      && get_post_type() === 'resource'
    ) {
      wp_enqueue_media();
      wp_enqueue_script(
        'agt-media-uploader',
        AGT_PLUGIN_URL . 'assets/js/media-uploader.js',
        ['jquery'],
        AGT_VERSION,
        true
      );
    }
  }

  public function save_meta($post_id, $post)
  {
    if (
      ! isset($_POST['agt_resource_media_nonce'])
      || ! wp_verify_nonce($_POST['agt_resource_media_nonce'], 'agt_save_resource_media')
    ) {
      return;
    }
    // bail on autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }
    if (isset($_POST['agt_resource_media'])) {
      update_post_meta(
        $post_id,
        '_agt_resource_media',
        esc_url_raw($_POST['agt_resource_media'])
      );
    }
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
      'supports'           => ['title', 'thumbnail', 'excerpt'],
      'taxonomies'         => get_option('agt_resource_taxonomies', []),
      'menu_icon'          => 'dashicons-portfolio',
    ];
    register_post_type('resource', $args);
  }
}
