<?php
// includes/ajax-handler.php

/**
 * AJAX handler to filter posts and resources on-the-fly.
 * 
 * Listens for 'filter_blog_posts_with_search'; 
 * reads filter parameters from $_POST, builds a WP_Query, and echoes the resulting grid.
 * 
 * Expects the following $_POST parameters:
 *    - search        (string)              Optional search keyword.
 *    - industry      (string)              Slug of an Industry category to filter by.
 *    - service       (string)              Slug of a Service category to filter by.
 *    - resource_type (string)              Slug of a Resource Type category to filter by.
 *    - category      (string)              Slug of a Category archive context.
 *    - tag           (string)              Slug of a Tag archive context.
 *    - exclude       (string) (comma-sep)  Slugs to exclude (e.g., 'blog,news').
 * 
 * This function outputs HTML via render_filtered_blog_posts() and then exits.
 * 
 * @return void
 */

add_action('wp_ajax_filter_blog_posts_with_search', 'filter_blog_posts_with_search');
add_action('wp_ajax_nopriv_filter_blog_posts_with_search', 'filter_blog_posts_with_search');

function filter_blog_posts_with_search()
{
  $args = [
    'post_type'      => ['post', 'resource'],
    'posts_per_page' => -1,
    's'              => sanitize_text_field($_POST['search'] ?? ''),
  ];
  if (! empty($_POST['tag'])) {
    $args['tag'] = sanitize_text_field($_POST['tag']);
  }

  // Build a tax_query of any selected filters
  $tax_queries = [];
  foreach (['industry', 'service', 'resource_type'] as $key) {
    if (! empty($_POST[$key])) {
      $tax_queries[] = [
        'taxonomy' => 'category',
        'field'    => 'slug',
        'terms'    => sanitize_text_field($_POST[$key]),
      ];
    }
  }
  if (! empty($_POST['category'])) {
    $tax_queries[] = [
      'taxonomy' => 'category',
      'field'    => 'slug',
      'terms'    => sanitize_text_field($_POST['category']),
    ];
  }
  if (! empty($_POST['exclude'])) {
    $ex = array_map('sanitize_text_field', explode(',', $_POST['exclude']));
    $tax_queries[] = [
      'taxonomy' => 'category',
      'field'    => 'slug',
      'terms'    => $ex,
      'operator' => 'NOT IN',
    ];
  }
  if ($tax_queries) {
    $args['tax_query'] = $tax_queries;
  }

  ob_start();
  render_filtered_blog_posts($args);
  echo ob_get_clean();
  wp_die();
}
