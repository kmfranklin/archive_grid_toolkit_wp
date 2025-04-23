<?php

/**
 * Plugin Name:   Archive & Grid Toolkit
 * Plugin URI:    https://nomadicsoftware.com/
 * Description:   Provides [blog_grid], [topic_archive_grid], & [resource_grid] shortcodes plus AJAX filtering.
 * Version:       1.0.0
 * Author:        Kevin Franklin | Nomadic Software
 * Author URI:    https://nomadicsoftware.com/
 * License:       All Rights Reserved
 */

defined('ABSPATH') || exit;

// Constants
if (!defined('ARCHIVE_GRID_VERSION')) {
  define('ARCHIVE_GRID_VERSION', '1.0.0');
}

if (!defined('ARCHIVE_GRID_PATH')) {
  define('ARCHIVE_GRID_PATH', plugin_dir_path(__FILE__));
}

if (!defined('ARCHIVE_GRID_URL')) {
  define('ARCHIVE_GRID_URL', plugin_dir_url(__FILE__));
}

// Autoload classes
require_once ARCHIVE_GRID_PATH . 'includes/helpers.php';
require_once ARCHIVE_GRID_PATH . 'includes/ajax-handler.php';
require_once ARCHIVE_GRID_PATH . 'includes/shortcodes.php';

// Enqueue assets
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_script(
    'archive-grid-filter',
    ARCHIVE_GRID_URL . 'assets/js/filter-grid.js',
    ['jquery'],
    ARCHIVE_GRID_VERSION,
    true
  );
  wp_localize_script('archive-grid-filter', 'ArchiveGridAjax', [
    'ajax_url' => admin_url('admin-ajax.php')
  ]);
  wp_enqueue_style(
    'archive-grid-styles',
    ARCHIVE_GRID_URL . 'assets/css/archive-grid.css',
    [],
    ARCHIVE_GRID_VERSION
  );
});
