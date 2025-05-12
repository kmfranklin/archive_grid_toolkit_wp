<?php

/**
 * Shortcodes for Archive & Grid Toolkit
 *
 * Handles [agt_grid id="..."] output, including search,
 * taxonomy filters, grid rendering, and the FAQ accordion.
 */

defined('ABSPATH') || exit;

class AGT_Shortcodes
{

  /** @var AGT_Shortcodes */
  private static $instance;

  /** Singleton */
  public static function get_instance()
  {
    if (! self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /** Register our shortcode override _after_ AGT_Grid does its own */
  private function __construct()
  {
    // Delay our shortcode registration to init@20 so it replaces AGT_Grid's default.
    add_action('init', [$this, 'register_shortcode'], 20);
  }

  /** Actually hook the [agt_grid] tag to our handler */
  public function register_shortcode()
  {
    remove_shortcode('agt_grid');
    add_shortcode('agt_grid', [$this, 'render_grid_shortcode']);
  }

  /**
   * Shortcode handler
   *
   * @param array $atts Shortcode attributes.
   * @return string HTML output.
   */
  public function render_grid_shortcode($atts)
  {
    // Load merged defaults + saved settings
    $settings = AGT_Settings::get_instance();
    $configs  = $settings->get_configs();

    // Parse attributes
    $atts = shortcode_atts(['id' => 'blog'], $atts, 'agt_grid');
    $id   = sanitize_key($atts['id']);

    // ——— FAQ branch always first ———
    if ('faq' === $id) {
      $cfg = $configs['faq'];
      $wp  = $this->get_query('faq', $cfg);
      return $this->render_faq_accordion($wp, $cfg);
    }

    // ——— Non‑FAQ: bail if no config ———
    if (empty($configs[$id])) {
      return '';
    }
    $cfg = $configs[$id];

    // ——— Build search & filter form ———
    ob_start();
    if ($cfg['enable_search'] || ($cfg['enable_filters'] && ! empty($cfg['filter_taxonomies']))) {
      echo '<form method="get" class="agt-filter-form">';
      if ($cfg['enable_search']) {
        printf(
          '<input type="text" name="agt_s" placeholder="%s" value="%s" class="agt-search" />',
          esc_attr($cfg['search_placeholder']),
          esc_attr($_GET['agt_s'] ?? '')
        );
      }
      if ($cfg['enable_filters']) {
        foreach ($cfg['filter_taxonomies'] as $tax) {
          $tax_obj = get_taxonomy($tax);
          if (! $tax_obj) {
            continue;
          }
          $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => true]);
          if (empty($terms)) {
            continue;
          }
          echo '<div class="agt-filter-group"><strong>'
            . esc_html($tax_obj->labels->singular_name)
            . '</strong><br>';
          $selected = (array) ($_GET[$tax] ?? []);
          foreach ($terms as $term) {
            printf(
              '<label><input type="checkbox" name="%1$s[]" value="%2$s"%3$s> %4$s</label> ',
              esc_attr($tax),
              esc_attr($term->slug),
              in_array($term->slug, $selected, true) ? ' checked' : '',
              esc_html($term->name)
            );
          }
          echo '</div>';
        }
      }
      echo '<button type="submit" class="agt-filter-submit">'
        . esc_html__('Filter', 'archive-grid-toolkit')
        . '</button>';
      echo '</form>';
    }
    $controls = ob_get_clean();

    // ——— Run the main query ———
    $wp = $this->get_query($id, $cfg);

    // ——— Render the standard grid ———
    return $controls . $this->render_grid($wp, $cfg, $id);
  }

  /**
   * Build a WP_Query for the given module ID.
   */
  private function get_query($id, $cfg)
  {
    $post_type = ('blog' === $id) ? 'post' : $id;
    $paged     = max(1, get_query_var('paged') ?: 1);
    $args      = [
      'post_type'      => $post_type,
      'posts_per_page' => intval($cfg['posts_per_page']),
      'orderby'        => ('random' === $cfg['sort_by']) ? 'rand' : $cfg['sort_by'],
      'order'          => ('ASC' === $cfg['order']) ? 'ASC' : 'DESC',
      'paged'          => $paged,
    ];

    if ($cfg['enable_search'] && ! empty($_GET['agt_s'])) {
      $args['s'] = sanitize_text_field(wp_unslash($_GET['agt_s']));
    }

    if ($cfg['enable_filters'] && ! empty($cfg['filter_taxonomies'])) {
      $tax_query = ['relation' => 'AND'];
      foreach ($cfg['filter_taxonomies'] as $tax) {
        if (! empty($_GET[$tax])) {
          $terms       = array_map('sanitize_key', (array) $_GET[$tax]);
          $tax_query[] = [
            'taxonomy' => $tax,
            'field'    => 'slug',
            'terms'    => $terms,
          ];
        }
      }
      if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
      }
    }

    return new WP_Query($args);
  }

  /**
   * Render a post grid.
   */
  private function render_grid(WP_Query $wp, array $cfg, string $id)
  {
    ob_start();
    printf(
      '<div class="agt-grid agt-grid-%1$s columns-%2$d gutter-%3$s %4$s" data-id="%1$s">',
      esc_attr($id),
      intval($cfg['columns']),
      esc_attr($cfg['gutter']),
      esc_attr($cfg['container_class'])
    );

    if ($wp->have_posts()) {
      while ($wp->have_posts()) {
        $wp->the_post();
        echo '<div class="agt-item">';
        if ($cfg['show_featured'] && has_post_thumbnail()) {
          echo '<div class="agt-image"><a href="' . get_permalink() . '">';
          the_post_thumbnail('medium');
          echo '</a></div>';
        }
        if ($cfg['show_title']) {
          printf(
            '<h2 class="agt-title"><a href="%s">%s</a></h2>',
            esc_url(get_permalink()),
            esc_html(get_the_title())
          );
        }
        if ($cfg['show_excerpt']) {
          printf(
            '<div class="agt-excerpt">%s</div>',
            wp_trim_words(get_the_excerpt(), intval($cfg['excerpt_length']))
          );
        }
        if ($cfg['show_meta']) {
          printf(
            '<div class="agt-meta">%s by %s</div>',
            esc_html(get_the_date()),
            esc_html(get_the_author())
          );
        }
        echo '</div>';
      }
      wp_reset_postdata();
    } else {
      echo '<p>' . esc_html__('No items found.', 'archive-grid-toolkit') . '</p>';
    }

    if ($cfg['lazy_load']) {
      printf(
        '<button class="agt-load-more" data-id="%s">%s</button>',
        esc_attr($id),
        esc_html__('Load More', 'archive-grid-toolkit')
      );

      echo '</div>'; // .agt-grid

    }

    return ob_get_clean();
  }

  /**
   * Render the FAQ accordion.
   */
  private function render_faq_accordion(WP_Query $wp, array $cfg)
  {
    ob_start();
    echo '<div class="agt-accordion">';

    if ($wp->have_posts()) {
      $first = true;
      while ($wp->have_posts()) {
        $wp->the_post();

        $question = get_post_meta(get_the_ID(), '_agt_faq_question', true);
        $answer   = get_post_meta(get_the_ID(), '_agt_faq_answer',   true);

        if (! $question) {
          $question = get_the_title();
        }
        if (! $answer) {
          $answer = get_the_content();
        }

        $open_class = ($first && $cfg['expand_first']) ? ' open' : '';
        $icon       = $open_class ? '−' : '+';

        printf(
          '<div class="agt-item%1$s">
             <h4 class="agt-header agt-toggle">%2$s
               <span class="agt-icon" style="float:right;">%3$s</span>
             </h4>
             <div class="agt-body">%4$s</div>
           </div>',
          esc_attr($open_class),
          esc_html($question),
          esc_html($icon),
          wpautop($answer)
        );

        $first = false;
      }
      wp_reset_postdata();
    } else {
      echo '<p>' . esc_html__('No FAQs found.', 'archive-grid-toolkit') . '</p>';
    }

    echo '</div>'; // .agt-accordion

    // Inline JS settings for animation & multi-open
    $speed_map = ['slow' => 600, 'normal' => 400, 'fast' => 200];
    $ms        = $speed_map[$cfg['animation_speed']] ?? 400;
    printf(
      '<script>window.AGT_animSpeed=%1$d;window.AGT_multiOpen=%2$s;</script>',
      intval($ms),
      $cfg['multi_open'] ? 'true' : 'false'
    );

    return ob_get_clean();
  }
}
