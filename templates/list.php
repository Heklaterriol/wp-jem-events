<?php
if (!defined('ABSPATH')) exit;
if (empty($events)) {
    echo '<p>' . __('No events available','jem-events') . '</p>';
    return;
}
foreach ($events as $ev) {
    // Use the global settings template
    $settings = get_option('jem_events_settings', []);
    echo (new JEM_Events_Shortcode())->render_event_with_template($ev, $settings);
}
