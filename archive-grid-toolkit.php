<?php

/**
 * Plugin Name:   Archive & Grid Toolkit
 * Plugin URI:    https://nomadicsoftware.com/
 * Description:   A flexible toolkit for AJAX-powered archive grids (posts, resources, FAQs, etc.).
 * Version:       2.0.0
 * Author:        Kevin Franklin | Nomadic Software
 * Author URI:    https://nomadicsoftware.com/
 * License:       All Rights Reserved
 */

defined('ABSPATH') || exit;

// Constants
define('AGT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AGT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AGT_VERSION', '2.0.0');

// Load classes
require_once AGT_PLUGIN_DIR . 'includes/cpt/class-agt-cpt-loader.php';
require_once AGT_PLUGIN_DIR . 'includes/cpt/class-agt-cpt-resources.php';
require_once AGT_PLUGIN_DIR . 'includes/cpt/class-agt-cpt-faq.php';

// Load modules
require_once AGT_PLUGIN_DIR . 'includes/class-agt-settings.php';
require_once AGT_PLUGIN_DIR . 'includes/class-agt-grid.php';
require_once AGT_PLUGIN_DIR . 'includes/class-agt-ajax.php';
require_once AGT_PLUGIN_DIR . 'includes/class-agt-shortcodes.php';

/**
 * Initialize all modules.
 */
function agt_init()
{
  AGT_Settings::get_instance();
  AGT_Grid::get_instance();
  AGT_Ajax::get_instance();
  AGT_Shortcodes::get_instance();
}

add_action('plugins_loaded', ['AGT_CPT_Loader', 'init']);
add_action('plugins_loaded', 'agt_init');

// Enqueue scripts
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_script(
    'agt-accordion',
    AGT_PLUGIN_URL . 'assets/js/agt-accordion.js',
    ['jquery'],
    AGT_VERSION,
    true
  );

  wp_enqueue_style(
    'agt-accordion-css',
    AGT_PLUGIN_URL . 'assets/css/agt-accordion.css',
    [],
    AGT_VERSION
  );
});

// Register and flush CPTs on activation
register_activation_hook(
  __FILE__,
  function () {
    AGT_CPT_Resource::get_instance()->register_resource_cpt();
    AGT_CPT_FAQ::get_instance()->register_faq_cpt();
    flush_rewrite_rules();
  }
);
