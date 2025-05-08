<?php

/**
 * Settings page and API for Archive & Grid Toolkit
 *
 * Provides an intuitive admin UI for embedding and configuring
 * Blog Grid, Resource Grid, and FAQ Accordion modules.
 */

defined('ABSPATH') || exit;

class AGT_Settings
{
  private static $instance;
  const OPTION_NAME = 'agt_grid_configs';

  /** Default module configurations */
  private $defaults = [
    'blog'     => [
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
    'faq'      => [
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
      'enable_filters'     => false,
      'filter_taxonomies'  => [],
      'transient_duration' => 0,
      'lazy_load'          => false,
      'multi_open'         => false,
      'expand_first'       => true,
      'animation_speed'    => 'normal',
    ],
  ];

  private $configs;

  /** Singleton instance */
  public static function get_instance()
  {
    if (! self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /** Load saved configs and register hooks */
  private function __construct()
  {
    $saved         = get_option(self::OPTION_NAME, []);
    $this->configs = is_array($saved)
      ? array_replace_recursive($this->defaults, $saved)
      : $this->defaults;

    add_action('admin_menu', [$this, 'add_settings_page']);
    add_action('admin_init', [$this, 'register_settings']);
  }

  /** Add settings submenu */
  public function add_settings_page()
  {
    add_options_page(
      __('Archive & Grid Toolkit', 'archive-grid-toolkit'),
      __('Archive & Grid Toolkit', 'archive-grid-toolkit'),
      'manage_options',
      'agt_toolkit',
      [$this, 'render_settings_page']
    );
  }

  /** Register settings, sections, and fields */
  public function register_settings()
  {
    register_setting(
      'agt_toolkit_group',
      self::OPTION_NAME,
      [
        'type'              => 'array',
        'default'           => $this->defaults,
        'sanitize_callback' => [$this, 'sanitize_configs'],
      ]
    );

    foreach ($this->configs as $id => $cfg) {
      $section = "agt_section_{$id}";
      $page    = "agt_toolkit_{$id}";

      add_settings_section(
        $section,
        esc_html($cfg['label']),
        function () use ($id) {
          echo '<p>' . esc_html(sprintf(
            __('Settings for %s', 'archive-grid-toolkit'),
            $this->configs[$id]['label']
          )) . '</p>';
        },
        $page
      );

      // Common
      $this->add_field($id, $page, $section, 'ppp',         'Items Per Page',                   'render_ppp_field');
      $this->add_field($id, $page, $section, 'sort_by',     'Sort By',                         'render_sort_by_field');
      $this->add_field($id, $page, $section, 'order',       'Order',                           'render_order_field');
      $this->add_field($id, $page, $section, 'display',     'Display Options',                 'render_display_options_field');

      // Grid-only
      if ('faq' !== $id) {
        $this->add_field($id, $page, $section, 'layout', 'Layout Options',              'render_layout_options_field');
        $this->add_field($id, $page, $section, 'lazy',   'Enable "Load More" button', 'render_lazy_load_field');
      }

      // Always
      $this->add_field($id, $page, $section, 'filter',      'Filter Controls',        'render_filter_controls_field');
      $this->add_field($id, $page, $section, 'performance', 'Performance',            'render_performance_field');

      // FAQ-only
      if ('faq' === $id) {
        $this->add_field($id, $page, $section, 'accordion', 'Accordion Options',     'render_accordion_options_field');
      }
    }
  }

  /** Helper to add a field */
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

  /** Render settings page and shortcodes */
  public function render_settings_page()
  {
?>
    <div class="wrap">
      <h1><?php esc_html_e('Archive & Grid Toolkit Settings', 'archive-grid-toolkit'); ?></h1>

      <div class="postbox">
        <h2 class="hndle"><?php esc_html_e('Shortcodes', 'archive-grid-toolkit'); ?></h2>
        <div class="inside">
          <?php foreach ($this->configs as $id => $cfg) : ?>
            <p><code>[agt_grid id="<?php echo esc_attr($id); ?>"]</code></p>
          <?php endforeach; ?>
        </div>
      </div>

      <form method="post" action="options.php">
        <?php
        settings_fields('agt_toolkit_group');
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->configs as $id => $cfg) {
          printf('<a href="#tab-%1$s" class="nav-tab">%2$s</a>', esc_attr($id), esc_html($cfg['label']));
        }
        echo '</h2>';

        foreach ($this->configs as $id => $cfg) {
          printf('<div id="tab-%1$s" class="tab-content" style="display:none"><table class="form-table">', esc_attr($id));
          do_settings_sections("agt_toolkit_{$id}");
          echo '</table></div>';
        }

        submit_button();
        ?>
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

  /** Sanitize settings input */
  public function sanitize_configs($input)
  {
    $clean = [];
    foreach ($this->defaults as $id => $def) {
      $raw           = $input[$id] ?? [];
      $clean[$id]  = [];
      $fields_to_sanitize = [
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
        'multi_open',
        'expand_first',
        'animation_speed'
      ];
      foreach ($fields_to_sanitize as $field) {
        // apply appropriate sanitization per type
        switch ($field) {
          case 'posts_per_page':
          case 'excerpt_length':
          case 'columns':
          case 'transient_duration':
            $clean[$id][$field] = max(-1, intval($raw[$field] ?? $def[$field]));
            break;
          case 'sort_by':
            $clean[$id][$field] = in_array($raw[$field] ?? $def[$field], ['date', 'title', 'random'], true) ? $raw[$field] : $def[$field];
            break;
          case 'order':
            $clean[$id][$field] = in_array($raw[$field] ?? $def[$field], ['ASC', 'DESC'], true) ? $raw[$field] : $def[$field];
            break;
          case 'gutter':
            $clean[$id][$field] = in_array($raw[$field] ?? $def[$field], ['small', 'medium', 'large'], true) ? $raw[$field] : $def[$field];
            break;
          case 'filter_taxonomies':
            $clean[$id][$field] = array_map('sanitize_key', (array) ($raw[$field] ?? []));
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
            $clean[$id][$field] = ! empty($raw[$field]);
            break;
          case 'container_class':
          case 'search_placeholder':
            $clean[$id][$field] = sanitize_text_field($raw[$field] ?? $def[$field]);
            break;
          case 'animation_speed':
            $clean[$id][$field] = in_array($raw[$field] ?? $def[$field], ['slow', 'normal', 'fast'], true) ? $raw[$field] : $def[$field];
            break;
        }
      }
    }
    return $clean;
  }

  /** Field renderers **/
  public function render_ppp_field($args)
  {
    $id  = $args['module_id'];
    $val = $this->configs[$id]['posts_per_page'];
    printf(
      '<input type="number" name="%1$s[%2$s][posts_per_page]" value="%3$d" class="small-text" min="-1" />',
      esc_attr(self::OPTION_NAME),
      esc_attr($id),
      intval($val)
    );
  }
  public function render_sort_by_field($args)
  {
    $id   = $args['module_id'];
    $opts = ['date' => 'Date', 'title' => 'Title', 'random' => 'Random'];
    printf('<select name="%1$s[%2$s][sort_by]">', esc_attr(self::OPTION_NAME), esc_attr($id));
    foreach ($opts as $k => $l) {
      printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($k), selected($this->configs[$id]['sort_by'], $k, false), esc_html($l));
    }
    echo '</select>';
  }
  public function render_order_field($args)
  {
    $id   = $args['module_id'];
    $opts = ['ASC' => 'Ascending', 'DESC' => 'Descending'];
    printf('<select name="%1$s[%2$s][order]">', esc_attr(self::OPTION_NAME), esc_attr($id));
    foreach ($opts as $k => $l) {
      printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($k), selected($this->configs[$id]['order'], $k, false), esc_html($l));
    }
    echo '</select>';
  }
  public function render_display_options_field($args)
  {
    $id  = $args['module_id'];
    $cfg = $this->configs[$id];
    echo '<fieldset>';
    printf('<label><input type="checkbox" name="%1$s[%2$s][show_featured]" value="1"%3$s /> Show featured image</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['show_featured'], true, false));
    printf('<label><input type="checkbox" name="%1$s[%2$s][show_title]" value="1"%3$s /> Show title</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['show_title'], true, false));
    printf('<label><input type="checkbox" name="%1$s[%2$s][show_excerpt]" value="1"%3$s /> Show excerpt</label> ', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['show_excerpt'], true, false));
    printf('<input type="number" name="%1$s[%2$s][excerpt_length]" value="%3$d" class="small-text" min="0" /> words<br>', esc_attr(self::OPTION_NAME), esc_attr($id), intval($cfg['excerpt_length']));
    printf('<label><input type="checkbox" name="%1$s[%2$s][show_meta]" value="1"%3$s /> Show meta (date/author)</label>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['show_meta'], true, false));
    echo '</fieldset>';
  }
  public function render_layout_options_field($args)
  {
    $id  = $args['module_id'];
    $cfg = $this->configs[$id];
    echo '<fieldset>';
    printf('<label>Columns: <input type="number" name="%1$s[%2$s][columns]" value="%3$d" min="1" max="6" class="small-text" /></label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), intval($cfg['columns']));
    $gut = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];
    printf('<label>Gutter: <select name="%1$s[%2$s][gutter]">', esc_attr(self::OPTION_NAME), esc_attr($id));
    foreach ($gut as $k => $l) {
      printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($k), selected($cfg['gutter'], $k, false), esc_html($l));
    }
    echo '</select></label><br>';
    printf('<label>Container class: <input type="text" name="%1$s[%2$s][container_class]" value="%3$s" class="regular-text" /></label>', esc_attr(self::OPTION_NAME), esc_attr($id), esc_attr($cfg['container_class']));
    echo '</fieldset>';
  }
  public function render_filter_controls_field($args)
  {
    $id  = $args['module_id'];
    $cfg = $this->configs[$id];
    echo '<fieldset>';
    printf('<label><input type="checkbox" name="%1$s[%2$s][enable_search]" value="1"%3$s /> Enable search box</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['enable_search'], true, false));
    printf('<label for="%1$s_%2$s_search_placeholder">Search box placeholder text</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id));
    printf('<input type="text" id="%1$s_%2$s_search_placeholder" name="%1$s[%2$s][search_placeholder]" value="%3$s" class="regular-text" /><br>', esc_attr(self::OPTION_NAME), esc_attr($id), esc_attr($cfg['search_placeholder']));
    printf('<label><input type="checkbox" name="%1$s[%2$s][enable_filters]" value="1"%3$s /> Enable taxonomy filters</label>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['enable_filters'], true, false));
    echo '</fieldset>';
  }
  public function render_performance_field($args)
  {
    $id  = $args['module_id'];
    $cfg = $this->configs[$id];
    printf('<label>Cache duration (minutes): <input type="number" name="%1$s[%2$s][transient_duration]" value="%3$d" min="0" class="small-text" /></label>', esc_attr(self::OPTION_NAME), esc_attr($id), intval($cfg['transient_duration']));
  }
  public function render_lazy_load_field($args)
  {
    $id  = $args['module_id'];
    $cfg = $this->configs[$id];
    printf('<label><input type="checkbox" name="%1$s[%2$s][lazy_load]" value="1"%3$s /> Enable "Load More" button</label>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['lazy_load'], true, false));
  }
  public function render_accordion_options_field($args)
  {
    $id  = $args['module_id'];
    $cfg = $this->configs[$id];
    echo '<fieldset>';
    printf('<label><input type="checkbox" name="%1$s[%2$s][multi_open]" value="1"%3$s /> Allow multiple open</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['multi_open'], true, false));
    printf('<label><input type="checkbox" name="%1$s[%2$s][expand_first]" value="1"%3$s /> Expand first by default</label><br>', esc_attr(self::OPTION_NAME), esc_attr($id), checked($cfg['expand_first'], true, false));
    $speeds = ['slow' => 'Slow', 'normal' => 'Normal', 'fast' => 'Fast'];
    printf('<label>Animation speed: <select name="%1$s[%2$s][animation_speed]">', esc_attr(self::OPTION_NAME), esc_attr($id));
    foreach ($speeds as $k => $l) {
      printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($k), selected($cfg['animation_speed'], $k, false), esc_html($l));
    }
    echo '</select></label>';
    echo '</fieldset>';
  }
}
