<?php
if (!defined('ABSPATH')) exit;

class JEM_Events_Fetcher {

    /**
     * $atts supports shortcode overrides (they will override admin defaults).
     * Accepts offset for pagination.
     */
    public static function get_events($atts = []) {

        $settings = get_option('jem_events_settings', []);
        $defaults = [
            'type' => $settings['type'] ?? '',
            'featured' => $settings['featured'] ?? '',
            'title' => $settings['title'] ?? 'link',
            'date' => $settings['date'] ?? 'no_link',
            'category' => $settings['category'] ?? 'no_link',
            'venue' => $settings['venue'] ?? 'no_link',
            'max' => $settings['max'] ?? 10,
            'cuttitle' => $settings['cuttitle'] ?? '',
            'noeventsmsg' => $settings['noeventsmsg'] ?? '',
            'catids' => $settings['catids'] ?? '',
            'venueids' => $settings['venueids'] ?? '',
            'token' => $settings['token'] ?? '',
            'offset' => 0
        ];

        // Shortcode attributes override admin defaults
        $params = shortcode_atts($defaults, $atts, 'jem_events');

        $domain = rtrim($settings['domain'] ?? '', '/');
        if (!$domain) {
            error_log('JEM Events: Domain not set.');
            return [];
        }

        $base = $domain . '/index.php?option=com_ajax&plugin=jemembed&group=content&format=json';

        // build query only with allowed params the endpoint accepts
        $query_params = [];
        if (!empty($params['type'])) $query_params['type'] = $params['type'];
        if (!empty($params['featured'])) $query_params['featured'] = $params['featured'];
        if (!empty($params['max'])) $query_params['max'] = intval($params['max']);
        if (!empty($params['catids'])) $query_params['catids'] = $params['catids'];
        if (!empty($params['venueids'])) $query_params['venueids'] = $params['venueids'];
        if (!empty($params['cuttitle'])) $query_params['cuttitle'] = intval($params['cuttitle']);
        // token
        if (!empty($params['token'])) $query_params['token'] = $params['token'];

        // Some endpoints support offset/limit via start / limit or offset param;
        // JEM embed might not support offset — we try to pass 'offset' if present.
        if (!empty($params['offset'])) $query_params['offset'] = intval($params['offset']);

        $url = $base . '&' . http_build_query($query_params);

        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            error_log('JEM Events fetch error: ' . $response->get_error_message());
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JEM Events JSON decode error: ' . json_last_error_msg());
            error_log('Response body: ' . substr($body, 0, 4000));
            return [];
        }

        // Typical structure: { success: true, data: [ { success:true, meta:..., data:[ events... ] } ] }
        if (isset($json['data'][0]['data']) && is_array($json['data'][0]['data'])) {
            $raw_events = $json['data'][0]['data'];
        } elseif (isset($json['data']) && is_array($json['data'])) {
            // fallback if endpoint returns data directly
            $raw_events = $json['data'];
        } else {
            error_log('JEM Events: No events found in JSON.');
            return [];
        }

        // Normalize events: produce array of associative arrays with known keys for template parser
        $events = [];
        foreach ($raw_events as $ev) {
            $item = [];
            // id
            $item['id'] = $ev['id'] ?? ($ev['eventid'] ?? '');
            // title
            $item['title'] = $ev['title']['display'] ?? ($ev['title']['full'] ?? ($ev['title'] ?? ''));
            $item['title_url'] = $ev['title']['url'] ?? ($ev['title_url'] ?? '');
            // dates
            $item['date'] = $ev['dates']['formatted_start_date'] ?? ($ev['date'] ?? '');
            $item['time'] = $ev['dates']['formatted_start_time'] ?? ($ev['time'] ?? '');
            $item['endtime'] = $ev['dates']['formatted_end_time'] ?? '';
            // venue
            $item['venue'] = $ev['venue']['name'] ?? ($ev['venue'] ?? '');
            $item['venue_url'] = $ev['venue']['url'] ?? '';
            // address if present
            $item['address'] = $ev['venue']['address'] ?? ($ev['address'] ?? '');
            // categories: flatten names comma separated
            $cats = [];
            if (!empty($ev['categories']) && is_array($ev['categories'])) {
                foreach ($ev['categories'] as $c) {
                    if (is_array($c) && isset($c['name'])) $cats[] = $c['name'];
                    elseif (is_string($c)) $cats[] = $c;
                }
            }
            $item['categories'] = implode(', ', $cats);
            // description (safe HTML provided by server) -> keep raw for now
            $item['description'] = $ev['description'] ?? '';
            // raw JSON string if needed
            $item['raw'] = wp_json_encode($ev);
            $events[] = $item;
        }

        return $events;
    }
}
