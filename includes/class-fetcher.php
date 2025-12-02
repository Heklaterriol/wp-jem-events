<?php

class JEM_Events_Fetcher {

    public static function get_events($atts) {

        // Einstellungen aus Backend
        $settings = get_option('jem_events_settings');
        $domain   = rtrim($settings['domain'] ?? '', '/');
        $token    = $settings['token'] ?? '';

        if (!$domain) {
            error_log('JEM Events: Domain not set.');
            return [];
        }

        // Basis-URL
        $base = $domain . '/index.php?option=com_ajax&plugin=jemembed&group=content&format=json';

        // Shortcode-Parameter
        $params = shortcode_atts([
            'type'        => '',
            'featured'    => '',
            'title'       => '',
            'date'        => '',
            'time'        => '',
            'enddatetime' => '',
            'catids'      => '',
            'category'    => '',
            'venueids'    => '',
            'venue'       => '',
            'max'         => 10,
            'cuttitle'    => '',
            'noeventsmsg' => ''
        ], $atts);

        // Token hinzufügen, falls gesetzt
        if (!empty($token)) {
            $params['token'] = $token;
        }

        // Vollständige URL bauen
        $query = http_build_query(array_filter($params));
        $url   = $base . '&' . $query;

        // Serverseitiger Request
        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response)) {
            error_log('JEM Events fetch error: ' . $response->get_error_message());
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JEM Events JSON decode error: ' . json_last_error_msg());
            return [];
        }

        // Korrekte Ebene extrahieren: data[0]['data']
        if (isset($json['data'][0]['data'])) {
            $events = $json['data'][0]['data'];
        } else {
            error_log('JEM Events: No events found in JSON.');
            $events = [];
        }

        return $events;
    }
}
