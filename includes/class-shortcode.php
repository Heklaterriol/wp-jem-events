<?php

class JEM_Events_Shortcode {

    public function __construct() {
        add_shortcode('jem_events', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_jem_events_load_more', [$this, 'ajax_load_more']);
        add_action('wp_ajax_nopriv_jem_events_load_more', [$this, 'ajax_load_more']);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jem-events-js', plugin_dir_url(__FILE__) . '../js/jem-events.js', ['jquery'], '1.0', true);
        wp_localize_script('jem-events-js', 'jemEvents', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'atts'    => []
        ]);
    }

    public function render($atts) {
        jemEvents_enqueue_scripts($atts);

        $events = JEM_Events_Fetcher::get_events($atts);

        ob_start();
        echo '<div id="jem-events-container">';
        include plugin_dir_path(__FILE__) . '../templates/list.php';
        echo '</div>';

        if(count($events) >= ($atts['max'] ?? 10)) {
            echo '<button id="jem-load-more">' . __('Load More','jem-events') . '</button>';
        }

        return ob_get_clean();
    }

    public function ajax_load_more() {
        $offset = intval($_POST['offset'] ?? 0);
        $atts   = $_POST['atts'] ?? [];
        $atts['max'] = 10;
        $atts['offset'] = $offset;

        $events = JEM_Events_Fetcher::get_events($atts);

        if (empty($events)) {
            wp_send_json(['html' => '', 'more' => false]);
        }

        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/list.php';
        $html = ob_get_clean();

        wp_send_json(['html' => $html, 'more' => count($events) == 10]);
    }
}

// Helper für JS-Localization
function jemEvents_enqueue_scripts($atts){
    wp_localize_script('jem-events-js', 'jemEvents', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'atts'    => $atts
    ]);
}
