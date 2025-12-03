<?php

class JEM_Events_Debugger {

    public static function run_test() {

        $settings = get_option('jem_events_settings');
        $domain   = rtrim($settings['domain'] ?? '', '/');
        $token    = $settings['token'] ?? '';

        if (!$domain) {
            return '<p>Error: No domain configured.</p>';
        }

        $url = $domain . '/index.php?option=com_ajax&plugin=jemembed&group=content&format=json';

        if ($token) {
            $url .= '&token=' . urlencode($token);
        }

        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response)) {
            return '<p>WP Error: ' . esc_html($response->get_error_message()) . '</p>';
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return '<p>JSON Error: ' . json_last_error_msg() . '</p><pre>' . esc_html($body) . '</pre>';
        }

        if (!isset($json['data'][0]['data'])) {
            return '<p>No events found in JSON structure.</p><pre>' . esc_html(print_r($json, true)) . '</pre>';
        }

        $events = $json['data'][0]['data'];
        $first  = reset($events);

        return 
            '<p>Connection OK.</p>' .
            '<p>Total events returned: ' . count($events) . '</p>' .
            '<h4>First event sample:</h4>' .
            '<pre>' . esc_html(print_r($first, true)) . '</pre>';
    }
}
