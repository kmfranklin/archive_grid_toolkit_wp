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

// Autoload classes
require_once AGT_PLUGIN_DIR . 'includes/class-agt-settings.php';
require_once AGT_PLUGIN_DIR . 'includes/class-agt-grid';
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

add_action('plugins_loaded', 'agt_init');
