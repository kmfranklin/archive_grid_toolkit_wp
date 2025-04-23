<?php
// includes/helpers.php

if (!defined('ABSPATH')) exit;

/**
 * Output a <select> dropdown of child categories for a given parent category.
 *
 * @param string $parent_slug   Slug of the parent category.
 * @param string $label         Human-readable label used in the “All …” option.
 * @param string $dropdown_id   HTML id attribute for the <select>.
 * @param string $exclude_slug  (Optional) A child category slug to omit.
 * @return void
 */
function render_child_category_dropdown($parent_slug, $label, $dropdown_id, $exclude_slug = '')
{
  $parent = get_term_by('slug', $parent_slug, 'category');
  if (! $parent) {
    return;
  }
  $children = get_terms([
    'taxonomy'   => 'category',
    'hide_empty' => true,
    'parent'     => $parent->term_id,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]);
  if (empty($children) || is_wp_error($children)) {
    return;
  }
  echo '<select id="' . esc_attr($dropdown_id) . '" class="filter-dropdown">';
  echo '<option value="">' . esc_html('All ' . $label) . '</option>';
  foreach ($children as $term) {
    if ($term->slug === $exclude_slug) {
      continue;
    }
    printf(
      '<option value="%1$s">%2$s</option>',
      esc_attr($term->slug),
      esc_html($term->name)
    );
  }
  echo '</select>';
}

/**
 * Output a <select> dropdown of non-empty “resource” child categories,
 * filtered to only those with at least one post in the current tag/category context.
 *
 * @param string $dropdown_id   HTML id attribute for the <select> (default 'resource_type-filter').
 * @return void
 */
function render_filtered_resource_dropdown($dropdown_id = 'resource_type-filter')
{
  $parent = get_term_by('slug', 'resource', 'category');
  if (! $parent) {
    return;
  }
  $types = get_terms([
    'taxonomy'   => 'category',
    'hide_empty' => false,
    'parent'     => $parent->term_id,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]);
  if (empty($types) || is_wp_error($types)) {
    return;
  }

  $current = get_queried_object();
  echo '<select id="' . esc_attr($dropdown_id) . '" class="filter-dropdown">';
  echo '<option value="">' . esc_html__('All Resource Types', 'your-text-domain') . '</option>';

  foreach ($types as $type) {
    // Build an AND‐relation query: this resource_type + current term
    $tax_query = [
      'relation' => 'AND',
      [
        'taxonomy' => 'category',
        'field'    => 'term_id',
        'terms'    => $type->term_id,
      ],
    ];
    if (is_tag()) {
      $tax_query[] = [
        'taxonomy' => 'post_tag',
        'field'    => 'term_id',
        'terms'    => $current->term_id,
      ];
    } elseif (is_category()) {
      $tax_query[] = [
        'taxonomy' => 'category',
        'field'    => 'term_id',
        'terms'    => $current->term_id,
      ];
    }

    $q = new WP_Query([
      'post_type'      => ['post', 'resource'],
      'posts_per_page' => 1,
      'fields'         => 'ids',
      'tax_query'      => $tax_query,
    ]);

    if ($q->have_posts()) {
      printf(
        '<option value="%1$s">%2$s</option>',
        esc_attr($type->slug),
        esc_html($type->name)
      );
    }
    wp_reset_postdata();
  }

  echo '</select>';
}

/**
 * Render a grid of posts/resources based on the provided WP_Query args.
 *
 * @param array $args  WP_Query arguments to merge with defaults (post_type, posts_per_page).
 * @return void
 */
function render_filtered_blog_posts($args = [])
{
  $defaults = [
    'post_type'      => ['post', 'resource'],
    'posts_per_page' => -1,
  ];
  $q = new WP_Query(array_merge($defaults, $args));

  if ($q->have_posts()) {
    echo '<div class="pp-posts-wrapper pp-posts-initiated pp-content-post-grid">';
    while ($q->have_posts()) {
      $q->the_post();
      $is_resource   = (get_post_type() === 'resource');
      $file_url      = $is_resource ? get_field('resource_url') : get_permalink();
      $link_target   = $is_resource ? ' target="_blank"' : '';
      $thumb_url     = has_post_thumbnail()
        ? get_the_post_thumbnail_url(get_the_ID(), 'large')
        : 'https://nomadicsoftware.com/wp-content/uploads/nomadic-ribbon-cutting.jpg';
?>
      <a href="<?php echo esc_url($file_url); ?>"
        class="pp-content-post card-wrapper" <?php echo $link_target; ?>>
        <div>
          <div class="pp-content-grid-post-image">
            <img src="<?php echo esc_url($thumb_url); ?>"
              alt="<?php the_title_attribute(); ?>">
          </div>
          <div class="pp-content-grid-post-body">
            <div class="pp-content-grid-post-data">
              <div class="category-tags">
                <?php foreach (get_the_category() as $cat) : ?>
                  <span class="category-tag"><?php echo esc_html($cat->name); ?></span>
                <?php endforeach; ?>
              </div>
              <h2 class="pp-content-grid-post-title"><?php the_title(); ?></h2>
              <?php if (! $is_resource) : ?>
                <div><?php echo wp_trim_words(get_the_excerpt(), 25, '...'); ?></div>
              <?php endif; ?>
            </div>
            <div class="post-date">
              <?php echo get_the_date(); ?>
              <span class="dot-separator">•</span>
              <?php the_author(); ?>
            </div>
          </div>
        </div>
      </a>
<?php
    }
    echo '</div>';
  } else {
    echo '<p>No results found.</p>';
  }
  wp_reset_postdata();
}

/**
 * Check if the current category archive is a child of a given parent slug.
 *
 * @param string $parent_slug  Slug of the potential parent category.
 * @return bool True if current category’s ancestor list includes the parent.
 */
function is_descendant_of_category($parent_slug)
{
  if (! is_category()) {
    return false;
  }
  $term   = get_queried_object();
  $parent = get_term_by('slug', $parent_slug, 'category');
  if (! $parent) {
    return false;
  }
  return in_array($parent->term_id, get_ancestors($term->term_id, 'category'));
}
