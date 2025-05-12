<?php

/**
 * Settings page and API for Archive & Grid Toolkit
 *
 * Provides an intuitive admin UI for embedding and configuring
 * Blog Grid, Resource Grid, and FAQ Accordion modules,
 * with dynamic taxonomy filters per CPT.
 */

defined('ABSPATH') || exit;

class AGT_Settings
{
  private static $instance;
  const OPTION_NAME = 'agt_grid_configs';

  /** Slug for settings page */
  private $slug = 'agt_toolkit';

  /** Default configs for each module/tab */
  private $defaults = [
    'blog' => [
      'label'              => 'Blog Grid',
      'posts_per_page'     => 10,
      'sort_by'            => 'date',
      'order'              => 'DESC',
      'show_featured'      => true,
      'show_title'         => true,
      'show_excerpt'       => true,
      'excerpt_length'     => 55,
      'show_meta'          => false,
      'columns'            => 3,
      'gutter'             => 'medium',
      'container_class'    => '',
      'enable_search'      => true,
      'search_placeholder' => '',
      'enable_filters'     => false,
      'filter_taxonomies'  => [],
      'transient_duration' => 0,
      'lazy_load'          => false,
    ],
    'resource' => [
      'label'              => 'Resource Grid',
      'posts_per_page'     => -1,
      'sort_by'            => 'date',
      'order'              => 'DESC',
      'show_featured'      => true,
      'show_title'         => true,
      'show_excerpt'       => true,
      'excerpt_length'     => 55,
      'show_meta'          => false,
      'columns'            => 3,
      'gutter'             => 'medium',
      'container_class'    => '',
      'enable_search'      => true,
      'search_placeholder' => '',
      'enable_filters'     => true,
      'filter_taxonomies'  => [],
      'transient_duration' => 0,
      'lazy_load'          => false,
    ],
    'faq' => [
      'label'              => 'FAQ Accordion',
      'posts_per_page'     => -1,
      'sort_by'            => 'date',
      'order'              => 'ASC',
      'show_featured'      => false,
      'show_title'         => true,
      'show_excerpt'       => false,
      'excerpt_length'     => 55,
      'show_meta'          => false,
      'columns'            => 1,
      'gutter'             => 'medium',
      'container_class'    => '',
      'enable_search'      => false,
      'search_placeholder' => '',
      'enable_filters'     => true,
      'filter_taxonomies'  => [],
      'transient_duration' => 0,
      'lazy_load'          => false,
      'multi_open'         => false,
      'expand_first'       => true,
      'animation_speed'    => 'normal',
    ],
  ];

  private $configs;

  /**
   * Return the merged settings array.
   * 
   * @return array
   */
  public function get_configs(): array
  {
    return $this->configs;
  }

  public static function get_instance()
  {
    if (! self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /** Constructor: load saved configs & register hooks */
  private function __construct()
  {
    $saved = get_option(self::OPTION_NAME, []);
    $this->configs = is_array($saved)
      ? array_replace_recursive($this->defaults, $saved)
      : $this->defaults;

    add_action('admin_menu', [$this, 'add_settings_page']);
    add_action('admin_init', [$this, 'register_settings']);
  }

  /** Add settings page under Settings menu */
  public function add_settings_page()
  {
    add_options_page(
      __('Archive & Grid Toolkit', 'archive-grid-toolkit'),
      __('Archive & Grid Toolkit', 'archive-grid-toolkit'),
      'manage_options',
      $this->slug,
      [$this, 'render_settings_page']
    );
  }

  /** Register settings, sections, and fields */
  public function register_settings()
  {
    // Main configs array
    register_setting(
      'agt_toolkit_group',
      self::OPTION_NAME,
      [
        'type'              => 'array',
        'default'           => $this->defaults,
        'sanitize_callback' => [$this, 'sanitize_configs'],
      ]
    );

    // Loop modules
    foreach ($this->configs as $id => $cfg) {
      $section = "{$this->slug}_section_{$id}";
      $page = "{$this->slug}_{$id}";

      add_settings_section(
        $section,
        esc_html($cfg['label']),
        function () use ($id) {
          echo '<p>' . esc_html(sprintf(__('Settings for %s', 'archive-grid-toolkit'), $this->configs[$id]['label'])) . '</p>';
        },
        $page
      );

      // Common fields
      $this->add_field($id, $page, $section, 'ppp',      'Items Per Page',             'render_ppp_field');
      $this->add_field($id, $page, $section, 'sort_by',  'Sort By',                   'render_sort_by_field');
      $this->add_field($id, $page, $section, 'order',    'Order',                     'render_order_field');
      $this->add_field($id, $page, $section, 'display',  'Display Options',           'render_display_options_field');

      if ('faq' !== $id) {
        $this->add_field($id, $page, $section, 'layout', 'Layout Options',         'render_layout_options_field');
        $this->add_field($id, $page, $section, 'lazy',   'Enable "Load More"',    'render_lazy_load_field');
      }

      // Filter controls + performance
      $this->add_field($id, $page, $section, 'filter',      'Filter Controls',    'render_filter_controls_field');
      $this->add_field($id, $page, $section, 'performance', 'Performance',        'render_performance_field');

      // FAQ only
      if ('faq' === $id) {
        $this->add_field($id, $page, $section, 'accordion', 'Accordion Options', 'render_accordion_options_field');
      }
    }
  }

  /** Add a settings field to a section */
  private function add_field($id, $page, $section, $key, $label, $cb)
  {
    add_settings_field(
      "{$id}_{$key}",
      __($label, 'archive-grid-toolkit'),
      [$this, $cb],
      $page,
      $section,
      ['module_id' => $id]
    );
  }

  /** Render settings page (tabs + module forms) */
  public function render_settings_page()
  {
?>
    <div class="wrap">
      <h1><?php esc_html_e('Archive & Grid Toolkit Settings', 'archive-grid-toolkit'); ?></h1>
      <form method="post" action="options.php">
        <?php settings_fields('agt_toolkit_group'); ?>
        <h2 class="nav-tab-wrapper">
          <?php foreach ($this->configs as $id => $cfg) {
            printf('<a href="#tab-%1$s" class="nav-tab">%2$s</a>', esc_attr($id), esc_html($cfg['label']));
          } ?>
        </h2>

        <?php foreach ($this->configs as $id => $cfg) :
          printf('<div id="tab-%1$s" class="tab-content" style="display:none"><table class="form-table">', esc_attr($id));
          do_settings_sections("{$this->slug}_{$id}");
          echo '</table></div>';
        endforeach; ?>

        <?php submit_button(); ?>
      </form>
    </div>
    <script>
      jQuery(function($) {
        var tabs = $('.nav-tab'),
          contents = $('.tab-content');
        tabs.on('click', function(e) {
          e.preventDefault();
          tabs.removeClass('nav-tab-active');
          $(this).addClass('nav-tab-active');
          contents.hide();
          $($(this).attr('href')).show();
        });
        tabs.first().click();
      });
    </script>
<?php
  }

  /** Sanitize config array */
  public function sanitize_configs($input)
  {
    $clean = [];

    foreach ($this->defaults as $id => $def) {
      $raw   = $input[$id] ?? [];
      $clean[$id] = [];

      // always‑present fields
      $fields = [
        'posts_per_page',
        'sort_by',
        'order',
        'show_featured',
        'show_title',
        'show_excerpt',
        'excerpt_length',
        'show_meta',
        'columns',
        'gutter',
        'container_class',
        'enable_search',
        'search_placeholder',
        'enable_filters',
        'filter_taxonomies',
        'transient_duration',
        'lazy_load',
      ];

      // FAQ only
      if ('faq' === $id) {
        $fields[] = 'multi_open';
        $fields[] = 'expand_first';
        $fields[] = 'animation_speed';
      }

      foreach ($fields as $f) {
        $v = $raw[$f] ?? ($def[$f] ?? null);
        switch ($f) {
          case 'posts_per_page':
          case 'excerpt_length':
          case 'columns':
          case 'transient_duration':
            $clean[$id][$f] = max(-1, intval($v));
            break;
          case 'sort_by':
            $clean[$id][$f] = in_array($v, ['date', 'title', 'random'], true) ? $v : $def[$f];
            break;
          case 'order':
            $clean[$id][$f] = in_array($v, ['ASC', 'DESC'], true) ? $v : $def[$f];
            break;
          case 'gutter':
            $clean[$id][$f] = in_array($v, ['small', 'medium', 'large'], true) ? $v : $def[$f];
            break;
          case 'filter_taxonomies':
            $clean[$id][$f] = array_map('sanitize_key', (array)$v);
            break;
          case 'enable_search':
          case 'show_featured':
          case 'show_title':
          case 'show_excerpt':
          case 'show_meta':
          case 'enable_filters':
          case 'lazy_load':
          case 'multi_open':
          case 'expand_first':
            $clean[$id][$f] = isset($raw[$f]) && (bool) $raw[$f];
            break;
          case 'container_class':
          case 'search_placeholder':
            $clean[$id][$f] = sanitize_text_field($v);
            break;
          case 'animation_speed':
            $clean[$id][$f] = in_array($v, ['slow', 'normal', 'fast'], true) ? $v : $def[$f];
            break;
        }
      }
    }
    return $clean;
  }

  /** Render filter controls including taxonomy checkboxes */
  public function render_filter_controls_field($args)
  {
    $id = $args['module_id'];
    $cfg = $this->configs[$id];
    echo '<fieldset>';
    // Search box
    printf(
      '<label><input type="checkbox" name="%1$s[%2$s][enable_search]" value="1"%3$s /> Enable search</label><br>',
      esc_attr(self::OPTION_NAME),
      esc_attr($id),
      checked($cfg['enable_search'], true, false)
    );
    // Placeholder
    printf(
      '<input type="text" name="%1$s[%2$s][search_placeholder]" value="%3$s" class="regular-text" placeholder="%4$s"/><br>',
      esc_attr(self::OPTION_NAME),
      esc_attr($id),
      esc_attr($cfg['search_placeholder']),
      esc_attr__('Search...', 'archive-grid-toolkit')
    );
    // Enable filters
    printf(
      '<label><input type="checkbox" name="%1$s[%2$s][enable_filters]" value="1"%3$s /> Enable filters</label><br>',
      esc_attr(self::OPTION_NAME),
      esc_attr($id),
      checked($cfg['enable_filters'], true, false)
    );
    if ($cfg['enable_filters']) {
      // Map module to CPT
      $map = ['blog' => 'post', 'resource' => 'resource', 'faq' => 'faq'];
      $pt = $map[$id] ?? 'post';
      $taxes = get_object_taxonomies($pt, 'objects');
      echo '<p><strong>' . esc_html__('Taxonomies', 'archive-grid-toolkit') . '</strong></p>';
      foreach ($taxes as $tax) {
        printf(
          '<label><input type="checkbox" name="%1$s[%2$s][filter_taxonomies][]" value="%3$s"%4$s> %5$s</label><br>',
          esc_attr(self::OPTION_NAME),
          esc_attr($id),
          esc_attr($tax->name),
          in_array($tax->name, $cfg['filter_taxonomies'], true) ? ' checked' : '',
          esc_html($tax->labels->singular_name)
        );
      }
    }
    echo '</fieldset>';
  }

  /** Render items per page field */
  public function render_ppp_field($args)
  {
    $id = $args['module_id'];
    $val = $this->configs[$id]['posts_per_page'];
    printf(
      '<input type="number" name="%1$s[%2$s][posts_per_page]" value="%3$d" class="small-text" min="-1"/>',
      esc_attr(self::OPTION_NAME),
      esc_attr($id),
      intval($val)
    );
  }

  /** Render sort by field */
  public function render_sort_by_field($args)
  {
    $id = $args['module_id'];
    $opts = ['date' => 'Date', 'title' => 'Title', 'random' => 'Random'];
    printf('<select name="%1$s[%2$s][sort_by]">', esc_attr(self::OPTION_NAME), esc_attr($id));
    foreach ($opts as $k => $l) {
      printf(
        '<option value="%1$s"%2$s>%3$s</option>',
        esc_attr($k),
        selected($this->configs[$id]['sort_by'], $k, false),
        esc_html($l)
      );
    }
    echo '</select>';
  }

  /** Render order field */
  public function render_order_field($args)
  {
    $id = $args['module_id'];
    $opts = ['ASC' => 'Ascending', 'DESC' => 'Descending'];
    printf('<select name="%1$s[%2$s][order]">', esc_attr(self::OPTION_NAME), esc_attr($id));
    foreach ($opts as $k => $l) {
      printf(
        '<option value="%1$s"%2$s>%3$s</option>',
        esc_attr($k),
        selected($this->configs[$id]['order'], $k, false),
        esc_html($l)
      );
    }
    echo '</select>';
  }

  /** Render display options (checkboxes) */
  public function render_display_options_field($args)
  {
    $id = $args['module_id'];
    $cfg = $this->configs[$id];
    echo '<fieldset>';
    printf('<label><input type="checkbox" name="%1$s[%2$s][show_featured]" value="1"%3$s /> Show featured image</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['show_featured'], true, false));
    printf('<label><input type="checkbox" name="%1$s[%2$s][show_title]" value="1"%3$s /> Show title</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['show_title'], true, false));
    printf('<label><input type="checkbox" name="%1$s[%2$s][show_excerpt]" value="1"%3$s /> Show excerpt</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['show_excerpt'], true, false));
    printf('<input type="number" name="%1$s[%2$s][excerpt_length]" value="%3$d" class="small-text" min="0"/> words<br>', esc_attr(self::OPTION_NAME), esc_attr($id), intval($cfg['excerpt_length']));
    printf('<label><input type="checkbox" name="%1$s[%2$s][show_meta]" value="1"%3$s /> Show meta</label>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['show_meta'], true, false));
    echo '</fieldset>';
  }

  /** Render layout options */
  public function render_layout_options_field($args)
  {
    $id = $args['module_id'];
    $cfg = $this->configs[$id];
    echo '<fieldset>';
    printf('Columns: <input type="number" name="%1$s[%2$s][columns]" value="%3$d" min="1" max="6" class="small-text"/><br>', esc_attr(self::OPTION_NAME), esc_attr($id), intval($cfg['columns']));
    printf('<label>Gutter: <select name="%1$s[%2$s][gutter]">', esc_attr(self::OPTION_NAME), esc_attr($id));
    foreach (['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'] as $k => $l) {
      printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($k), selected($cfg['gutter'], $k, false), esc_html($l));
    }
    echo '</select></label><br>';
    printf('Container class: <input type="text" name="%1$s[%2$s][container_class]" value="%3$s" class="regular-text"/>', esc_attr(self::OPTION_NAME), esc_attr($id), esc_attr($cfg['container_class']));
    echo '</fieldset>';
  }

  /** Render performance field */
  public function render_performance_field($args)
  {
    $id = $args['module_id'];
    $cfg = $this->configs[$id];
    printf('Cache duration: <input type="number" name="%1$s[%2$s][transient_duration]" value="%3$d" class="small-text" min="0"/> mins', esc_attr(self::OPTION_NAME), esc_attr($id), intval($cfg['transient_duration']));
  }

  /** Render lazy load toggle */
  public function render_lazy_load_field($args)
  {
    $id = $args['module_id'];
    $cfg = $this->configs[$id];
    printf('<label><input type="checkbox" name="%1$s[%2$s][lazy_load]" value="1"%3$s /> Enable Load More</label>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['lazy_load'], true, false));
  }

  /** Render accordion options for FAQ */
  public function render_accordion_options_field($args)
  {
    $id = $args['module_id'];
    $cfg = $this->configs[$id];
    echo '<fieldset>';
    printf('<label><input type="checkbox" name="%1$s[%2$s][multi_open]" value="1"%3$s /> Allow multi open</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['multi_open'], true, false));
    printf('<label><input type="checkbox" name="%1$s[%2$s][expand_first]" value="1"%3$s /> Expand first</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['expand_first'], true, false));
    printf('Speed: <select name="%1$s[%2$s][animation_speed]">', esc_attr(self::OPTION_NAME), esc_attr($id));
    foreach (['slow' => 'Slow', 'normal' => 'Normal', 'fast' => 'Fast'] as $k => $l) {
      printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($k), selected($cfg['animation_speed'], $k, false), esc_html($l));
    }
    echo '</select></fieldset>';
  }
}
