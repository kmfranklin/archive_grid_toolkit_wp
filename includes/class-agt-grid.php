<?php

defined('ABSPATH') || exit;

/**
 * Handles [agt_grid] shortcode, assets, and AJAX load-more.
 */
class AGT_Grid
{
  const SHORTCODE   = 'agt_grid';
  const AJAX_ACTION = 'agt_load_more';

  /** @var AGT_Grid */
  private static $instance;

  /** @var array */
  private $settings;

  /** @var string */
  private $plugin_url;

  /** @var string */
  private $version;

  /**
   * Get singleton instance.
   *
   * @return AGT_Grid
   */
  public static function get_instance()
  {
    if (! self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Constructor.
   */
  private function __construct()
  {
    $this->settings   = AGT_Settings::get_instance()->get_configs();
    $this->plugin_url = defined('AGT_PLUGIN_URL') ? AGT_PLUGIN_URL : plugin_dir_url(__FILE__);
    $this->version    = defined('AGT_VERSION')    ? AGT_VERSION    : '1.0';

    add_action('init', [$this, 'register_shortcode']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    add_action('wp_ajax_' . self::AJAX_ACTION,        [$this, 'ajax_load_more']);
    add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'ajax_load_more']);
  }

  /**
   * Register the shortcode.
   */
  public function register_shortcode()
  {
    add_shortcode(self::SHORTCODE, [$this, 'handle_shortcode']);
  }

  /**
   * Enqueue styles/scripts and localize AJAX data.
   */
  public function enqueue_assets()
  {
    wp_enqueue_style(
      'agt-grid',
      $this->plugin_url . 'assets/css/archive-grid.css',
      [],
      $this->version
    );

    wp_enqueue_script(
      'agt-grid',
      $this->plugin_url . 'assets/js/agt-grid.js',
      ['jquery'],
      $this->version,
      true
    );

    wp_localize_script('agt-grid', 'AGTGrid', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce(self::AJAX_ACTION),
    ]);
  }

  /**
   * Shortcode callback.
   *
   * @param array $atts
   * @return string
   */
  public function handle_shortcode($atts)
  {
    $atts = shortcode_atts(['id' => 'blog'], $atts, self::SHORTCODE);
    $id   = sanitize_key($atts['id']);

    if (! isset($this->settings[$id])) {
      return '';
    }

    return $this->render_grid($id, $this->settings[$id]);
  }

  /**
   * Render the grid (filters + items + load-more button).
   *
   * @param string $id
   * @param array  $cfg
   * @return string
   */
  private function render_grid($id, array $cfg)
  {
    ob_start();

    $this->render_filters($id, $cfg);

    printf(
      '<div class="agt-grid cols-%1$d gutter-%2$s %3$s" data-id="%4$s">',
      absint($cfg['columns']),
      esc_attr($cfg['gutter']),
      esc_attr($cfg['container_class']),
      esc_attr($id)
    );

    $this->render_items($id, $cfg, 1);

    if (! empty($cfg['lazy_load'])) {
      // Initialize page=2 on first load-more
      echo '<button class="agt-load-more" data-page="2">'
        . esc_html__('Load More', 'archive-grid-toolkit')
        . '</button>';
    }

    echo '</div>';

    return ob_get_clean();
  }

  /**
   * Render search & taxonomy filters.
   *
   * @param string $id
   * @param array  $cfg
   */
  private function render_filters($id, array $cfg)
  {
    if (! $cfg['enable_search'] && ! $cfg['enable_filters']) {
      return;
    }

    echo '<div class="agt-filters">';

    if ($cfg['enable_search']) {
      printf(
        '<input type="search" class="agt-search" placeholder="%s"/>',
        esc_attr($cfg['search_placeholder'] ?: __('Search...', 'archive-grid-toolkit'))
      );
    }

    if ($cfg['enable_filters'] && ! empty($cfg['filter_taxonomies'])) {
      foreach ($cfg['filter_taxonomies'] as $tax) {
        $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => true]);
        if (empty($terms)) {
          continue;
        }

        printf(
          '<select class="agt-filter-%1$s"><option value="">%2$s %1$s</option>',
          esc_attr($tax),
          esc_html__('All', 'archive-grid-toolkit')
        );

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

    echo '</div>';
  }

  /**
   * Render items for a given page (each card now a link).
   *
   * @param string $id
   * @param array  $cfg
   * @param int    $page
   */
  private function render_items($id, array $cfg, $page)
  {
    $post_type = ('blog' === $id) ? 'post' : $id;
    $query = new WP_Query([
      'post_type'      => $post_type,
      'posts_per_page' => absint($cfg['posts_per_page']),
      'orderby'        => $cfg['sort_by'],
      'order'          => $cfg['order'],
      'paged'          => max(1, (int) $page),
    ]);

    if ($query->have_posts()) {
      while ($query->have_posts()) {
        $query->the_post();

        printf(
          '<a href="%s" class="agt-grid-item">',
          esc_url(get_permalink())
        );

        if ($cfg['show_featured'] && has_post_thumbnail()) {
          the_post_thumbnail('medium');
        }

        if ($cfg['show_title']) {
          printf(
            '<h3 class="agt-item-title">%s</h3>',
            get_the_title()
          );
        }

        if ($cfg['show_excerpt']) {
          echo '<div class="agt-item-excerpt">'
            . wp_trim_words(get_the_excerpt(), absint($cfg['excerpt_length']))
            . '</div>';
        }

        if ($cfg['show_meta']) {
          printf(
            '<div class="agt-item-meta">%s</div>',
            get_the_date()
          );
        }

        echo '</a>';
      }
      wp_reset_postdata();
    }
  }

  /**
   * Handle AJAX load-more.
   */
  public function ajax_load_more()
  {
    check_ajax_referer(self::AJAX_ACTION, 'nonce');

    $id   = isset($_POST['id'])   ? sanitize_key($_POST['id'])   : '';
    $page = isset($_POST['page']) ? absint($_POST['page']) : 2;

    if (! isset($this->settings[$id])) {
      wp_send_json_error();
    }

    $cfg = $this->settings[$id];

    ob_start();
    $this->render_items($id, $cfg, $page);
    $html = ob_get_clean();

    $total_pages = (new WP_Query([
      'post_type'      => ('blog' === $id) ? 'post' : $id,
      'posts_per_page' => absint($cfg['posts_per_page']),
      'orderby'        => $cfg['sort_by'],
      'order'          => $cfg['order'],
    ]))->max_num_pages;

    wp_send_json_success([
      'html'      => $html,
      'has_more'  => $page < $total_pages,
      'next_page' => $page + 1,
    ]);
  }
}
