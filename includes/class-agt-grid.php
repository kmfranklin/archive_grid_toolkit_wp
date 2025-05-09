<?php

defined('ABSPATH') || exit;

/**
 * Handles [agt_grid] shortcode, assets, and AJAX load-more.
 */
class AGT_Grid
{
  private static $instance;
  private $settings;

  public static function get_instance()
  {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }
  private function __construct()
  {
    $this->settings = AGT_Settings::get_instance()->get_configs();

    add_action('init', [$this, 'register_shortcode']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    add_action('wp_ajax_agt_load_more', [$this, 'ajax_load_more']);
    add_action('wp_ajax_nopriv_agt_load_more', [$this, 'ajax_load_more']);
  }

  /** Register [agt_grid] shortcode */
  public function register_shortcode()
  {
    add_shortcode('agt_grid', [$this, 'handle_shortcode']);
  }

  /** Enqueue front-end assets */
  public function enqueue_assets()
  {
    wp_enqueue_style(
      'agt-grid',
      plugin_dir_url(__FILE__) . 'css/agt-grid.css',
      [],
      AGT_VERSION
    );

    wp_enqueue_script(
      'agt-grid',
      plugin_dir_url(__FILE__) . 'js/agt-grid.js',
      ['jquery'],
      AGT_VERSION,
      true
    );

    wp_localize_script('agt-grid', 'AGTGrid', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('agt_grid'),
    ]);
  }

  /** Shortcode callback */
  public function handle_shortcode($atts)
  {
    $atts = shortcode_atts([
      'id' => 'blog',
    ], $atts, 'agt_grid');

    $id = sanitize_key($atts['id']);
    if (empty($this->settings[$id])) {
      return '';
    }

    $cfg = $this->settings[$id];
    return $this->render_grid($id, $cfg);
  }

  /** Render the grid markup */
  private function render_grid($id, $cfg)
  {
    ob_start();

    // Filters & search UI
    if ($cfg['enable_search'] || $cfg['enable_filters']) {
      echo '<div class="agt-filters">';

      if ($cfg['enable_search']) {
        printf(
          '<input type="search" class="agt-search" placeholder="%s"/>',
          esc_attr($cfg['search_placeholder'] ?: __('Search...', 'archive-grid-toolkit'))
        );
      }

      if ($cfg['enable_filters']) {
        foreach ((array)$cfg['filter_taxonomies'] as $tax) {
          $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => true]);
          if ($terms) {
            echo '<select class="agt-filter-' . esc_attr($tax) . '">';
            echo '<option value="">' . esc_html__('All', 'archive-grid-toolkit') . " $tax" . '</option>';
            foreach ($terms as $term) {
              printf(
                '<option value="%s">%s</option>',
                esc_attr($term->slug),
                esc_html($term->name)
              );
            }
            echo '</select>';
          }
        }
      }

      echo '</div>';
    }

    // Grid container
    printf(
      '<div class="agt-grid cols-%1$d gutter-%2$s %3$s">',
      intval($cfg['columns']),
      esc_attr($cfg['gutter']),
      esc_attr($cfg['container_class'])
    );

    // Build WP_Query
    $post_type = ('blog' === $id) ? 'post' : $id;
    $args = [
      'post_type'      => $post_type,
      'posts_per_page' => intval($cfg['posts_per_page']),
      'orderby'        => esc_attr($cfg['sort_by']),
      'order'          => esc_attr($cfg['order']),
    ];

    $query = new WP_Query($args);
    if ($query->have_posts()) {
      while ($query->have_posts()) {
        $query->the_post();

        echo '<div class="agt-grid-item">';
        if ($cfg['show_featured'] && has_post_thumbnail()) {
          the_post_thumbnail('medium');
        }
        if ($cfg['show_title']) {
          echo '<h3 class="agt-item-title">' . get_the_title() . '</h3>';
        }
        if ($cfg['show_excerpt']) {
          echo '<div class="agt-item-excerpt">' . wp_trim_words(get_the_excerpt(), intval($cfg['excerpt_length'])) . '</div>';
        }
        if ($cfg['show_meta']) {
          echo '<div class="agt-item-meta">' . get_the_date() . '</div>';
        }
        echo '</div>';
      }
      wp_reset_postdata();
    }

    echo '</div>';

    // Load More button
    if (! empty($cfg['lazy_load'])) {
      echo '<button class="agt-load-more">' . esc_html__('Load More', 'archive-grid-toolkit') . '</button>';
    }

    return ob_get_clean();
  }

  /** AJAX handler placeholder */
  public function ajax_load_more()
  {
    check_ajax_referer('agt_grid', 'nonce');

    wp_send_json_error('Not implemented yet');
  }
}
