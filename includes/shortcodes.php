<?php
// includes/shortcodes.php

add_shortcode('blog_grid', 'render_blog_only_grid');
add_shortcode('topic_archive_grid', 'render_topic_archive_grid');
add_shortcode('resource_grid', 'render_resource_grid');
add_shortcode('topic_description', 'topic_description_shortcode');

/**
 * [blog_grid] — main Blog page (posts only)
 */
function render_blog_only_grid()
{
  ob_start(); ?>
  <div class="filters">
    <input type="text" id="search-bar" class="filter-input" placeholder="Search">
    <?php render_child_category_dropdown('industry', 'Industry', 'industry-filter'); ?>
    <?php render_child_category_dropdown('service', 'Service', 'service-filter'); ?>
  </div>
  <div id="all-posts-grid" data-category="blog">
    <?php render_filtered_blog_posts(['category_name' => 'blog']); ?>
  </div>
<?php return ob_get_clean();
}

/**
 * [topic_archive_grid] — tag or category archives (posts + resources)
 */
function render_topic_archive_grid()
{
  $term = get_queried_object();
  ob_start(); ?>
  <div class="filters">
    <input type="text" id="search-bar" class="filter-input" placeholder="Search">
    <?php
    if (! is_category() || ! is_descendant_of_category('industry')) {
      render_child_category_dropdown('industry', 'Industry', 'industry-filter');
    }
    if (! is_category() || ! is_descendant_of_category('service')) {
      render_child_category_dropdown('service', 'Service', 'service-filter');
    }
    render_filtered_resource_dropdown();
    ?>
  </div>
  <div id="all-posts-grid"
    data-category="<?php echo esc_attr(is_category() ? $term->slug : ''); ?>"
    data-exclude=""
    data-tag="<?php echo esc_attr(is_tag()      ? $term->slug : ''); ?>">
    <?php
    $args = [
      'post_type'      => ['post', 'resource'],
      'posts_per_page' => -1,
    ];
    if (is_category()) {
      $args['category_name'] = $term->slug;
    } elseif (is_tag()) {
      $args['tag'] = $term->slug;
    }
    render_filtered_blog_posts($args);
    ?>
  </div>
<?php
  return ob_get_clean();
}

/**
 * [resource_grid] — standalone resources page (everything except blog)
 */
function render_resource_grid()
{
  ob_start(); ?>
  <div class="filters">
    <input type="text" id="search-bar" class="filter-input" placeholder="Search">
    <?php
    render_child_category_dropdown('industry', 'Industry',        'industry-filter');
    render_child_category_dropdown('service',  'Service',         'service-filter');
    render_child_category_dropdown('resource', 'Resource Type',   'resource_type-filter', 'blog');
    ?>
  </div>
  <div id="all-posts-grid" data-exclude="blog">
    <?php
    // Fetch all resource‐type children except “blog”
    $resource = get_term_by('slug', 'resource', 'category');
    $children = get_terms([
      'taxonomy'   => 'category',
      'hide_empty' => true,
      'parent'     => $resource->term_id,
    ]);
    $slugs = wp_list_pluck($children, 'slug');
    render_filtered_blog_posts([
      'tax_query' => [[
        'taxonomy' => 'category',
        'field'    => 'slug',
        'terms'    => $slugs,
        'operator' => 'IN',
      ]],
    ]);
    ?>
  </div>
<?php
  return ob_get_clean();
}

/**
 * [topic_description] — description of the topic/tag/category
 */
function topic_description_shortcode()
{
  if (!is_tag() && !is_category()) return '';

  $term = get_queried_object();

  // Show for tags (always)
  if (is_tag() && !empty($term->description)) {
    return '<div class="topic-description">' . wp_kses_post($term->description) . '</div>';
  }

  // Category logic
  if (is_category()) {
    // Define allowed top-level parents
    $allowed_parents = ['industry', 'service'];

    // Get the immediate parent
    if ($term->parent) {
      $parent = get_term($term->parent, 'category');

      // Only include if parent is one of the allowed
      if (
        in_array($parent->slug, $allowed_parents) &&
        !empty($term->description)
      ) {
        return '<div class="topic-description">' . wp_kses_post($term->description) . '</div>';
      }
    }
  }

  return '';
}
