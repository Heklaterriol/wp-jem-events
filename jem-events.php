<?php
/**
 * Plugin Name: JEM Events Importer
 * Description: Lädt Events aus JEM (Joomla) via JSON (AJAX-Plugin jemembed) und gibt sie in WordPress aus.
 * Version: 1.1
 * Text Domain: jem-events
 */

if (!defined('ABSPATH')) exit;

define('JEM_EVENTS_PATH', plugin_dir_path(__FILE__));
define('JEM_EVENTS_URL', plugin_dir_url(__FILE__));

// Includes
require_once JEM_EVENTS_PATH . 'includes/class-settings.php';
require_once JEM_EVENTS_PATH . 'includes/class-fetcher.php';
require_once JEM_EVENTS_PATH . 'includes/class-shortcode.php';
require_once JEM_EVENTS_PATH .  'includes/class-admin.php';
new JEMEmbed_Admin();

// Init
add_action('plugins_loaded', function() {
    load_plugin_textdomain('jem-events', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    new JEM_Events_Settings();
    new JEM_Events_Shortcode();
});
