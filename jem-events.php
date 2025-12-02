<?php
/**
 * Plugin Name: JEM Events Importer
 * Description: Lädt Events aus JEM (Joomla) via JSON (AJAX-Plugin jemembed) und gibt sie in WordPress aus.
 * Version: 1.0
 * Text Domain: jem-events
 */

if (!defined('ABSPATH')) exit;

// Includes
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-fetcher.php';
require_once __DIR__ . '/includes/class-shortcode.php';

// Init
add_action('plugins_loaded', function() {
    load_plugin_textdomain('jem-events', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    new JEM_Events_Settings();
    new JEM_Events_Shortcode();
});
