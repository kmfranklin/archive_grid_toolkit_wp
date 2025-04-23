/**
 * filter-grid.js
 *
 * Handles live AJAX filtering of posts and resources based on
 * search input and selected dropdown filters (Industry, Service, Resource Type).
 *
 * Relies on `ArchiveGridAjax.ajax_url` being localized in PHP.
 */
jQuery(function ($) {
  /**
   * Initialize filter inputs from URL query params (if present).
   */
  const url = new URL(window.location);
  if (url.searchParams.has('search')) $('#search-bar').val(url.searchParams.get('search'));
  if (url.searchParams.has('industry')) $('#industry-filter').val(url.searchParams.get('industry'));
  if (url.searchParams.has('service')) $('#service-filter').val(url.searchParams.get('service'));
  if (url.searchParams.has('resource_type')) $('#resource_type-filter').val(url.searchParams.get('resource_type'));

  /**
   * Fetch filtered posts via AJAX and inject the response HTML
   * into #all-posts-grid, then re-init any JS grid layout.
   *
   * @return {void}
   */
  function fetchFilteredPosts() {
    const g = $('#all-posts-grid').data();
    $.post(
      ArchiveGridAjax.ajax_url,
      {
        action: 'filter_blog_posts_with_search',
        search: $('#search-bar').val() || '',
        industry: $('#industry-filter').val() || '',
        service: $('#service-filter').val() || '',
        resource_type: $('#resource_type-filter').val() || '',
        category: g.category || '',
        tag: g.tag || '',
        exclude: g.exclude || '',
      },
      function (response) {
        $('#all-posts-grid').html(response);
        if (typeof PPContentGrid === 'function') PPContentGrid();
      }
    );
  }

  // Initial load
  fetchFilteredPosts();

  // Re-fetch on any filter change or search input
  $('#search-bar, .filter-dropdown').on('input change', fetchFilteredPosts);
});
