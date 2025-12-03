<?php
if (!defined('ABSPATH')) exit;

class JEM_Events_Shortcode {

    private static $shortcode_used = false;

    public function __construct() {
        add_shortcode('jem_events', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'register_front_assets']);
        add_action('wp_ajax_jem_events_load_more', [$this, 'ajax_load_more']);
        add_action('wp_ajax_nopriv_jem_events_load_more', [$this, 'ajax_load_more']);
        add_action('wp_footer', [$this, 'maybe_print_custom_css'], 20);
    }

    public function register_front_assets() {
        wp_register_script('jem-events-js', JEM_EVENTS_URL . 'js/jem-events.js', ['jquery'], '1.0', true);
    }

    /**
     * Shortcode render.
     * $atts override admin defaults (per user request).
     */
    public function render($atts) {
        self::$shortcode_used = true;

        // merge admin defaults into $atts such that shortcode overrides admin values
        $settings = get_option('jem_events_settings', []);
        // build initial combined defaults for fetcher
        $defaults = [
            'type' => $settings['type'] ?? '',
            'featured' => $settings['featured'] ?? '',
            'max' => $settings['max'] ?? 10,
            'catids' => $settings['catids'] ?? '',
            'venueids' => $settings['venueids'] ?? '',
            'cuttitle' => $settings['cuttitle'] ?? '',
            'token' => $settings['token'] ?? '',
            'offset' => 0
        ];
        $atts = shortcode_atts($defaults, $atts, 'jem_events');

        // enqueue frontend script and localize (atts for ajax)
        wp_enqueue_script('jem-events-js');
        wp_localize_script('jem-events-js', 'jemEvents', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'atts' => $atts
        ]);

        $events = JEM_Events_Fetcher::get_events($atts);

        // if no events and admin set a noeventsmsg, show it
        if (empty($events)) {
            $msg = $settings['noeventsmsg'] ?? '';
            if ($msg) return '<div class="jem-no-events">' . esc_html($msg) . '</div>';
            return '<div class="jem-no-events">' . __('No events available','jem-events') . '</div>';
        }

        // render events using admin template
        ob_start();
        echo '<div id="jem-events-container">';
        foreach ($events as $ev) {
            echo $this->render_event_with_template($ev, $settings);
        }
        echo '</div>';

        // show load more button if more may exist (we base this simply on returned count == max)
        $max = intval($atts['max']);
        if (count($events) >= $max) {
            echo '<button id="jem-load-more">' . __('Load More','jem-events') . '</button>';
        }

        return ob_get_clean();
    }

    public function render_event_with_template($event, $settings, $atts = []) {
        $template = $settings['template'] ?? "<div class=\"jem-event\">[title]</div>";
        $target_blank = !empty($settings['target_blank']);

        // parse template
        $html = self::parse_template($template, $event, $target_blank, $atts);
        return $html;
    }

    /**
     * Very small template parser:
     * - replaces [tag] with event[tag]
     * - supports simple conditionals: [if_tag]...[/if_tag] (shows inner content if tag non-empty)
     */
public static function parse_template($template, $event, $target_blank = false, $atts = []) {
    $out = $template;

    // Shortcode-Overrides oder Admin-Defaults
    $settings = get_option('jem_events_settings', []);
    $params = array_merge($settings, $atts);

    $target_attr = $target_blank ? ' target="_blank" rel="noopener"' : '';

    $supported = ['title','title_url','date','time','endtime','venue','venue_url','address','categories','description','id','raw'];

    // Conditionals [if_tag]...[/if_tag]
    foreach ($supported as $tag) {
        $pattern = '/\[if_' . preg_quote($tag, '/') . '\](.*?)\[\/if_' . preg_quote($tag, '/') . '\]/is';
        $out = preg_replace_callback($pattern, function($m) use ($event, $tag) {
            $val = $event[$tag] ?? '';
            if (is_string($val)) $val = trim($val);
            return (!empty($val) || $val === '0') ? $m[1] : '';
        }, $out);
    }

    // Simple replacements with link logic
    foreach ($supported as $tag) {
        $value = $event[$tag] ?? '';
        if ($tag === 'description') $value = wp_kses_post($value);
        else $value = esc_html($value);

        // Link logic
        if ($tag === 'title' && ($params['title'] ?? 'link') === 'link' && !empty($event['url'])) {
            $value = '<a href="'.esc_url($event['url']).'"'.$target_attr.'>'.$value.'</a>';
        }

        if ($tag === 'venue' && ($params['venue'] ?? 'link') === 'link' && !empty($event['venue']['url'])) {
            $venue_name = esc_html($event['venue']['name'] ?? '');
            $value = '<a href="'.esc_url($event['venue']['url']).'"'.$target_attr.'>'.$venue_name.'</a>';
        }

        if ($tag === 'date' && ($params['date'] ?? 'link') === 'link' && !empty($event['url'])) {
            $value = '<a href="'.esc_url($event['url']).'"'.$target_attr.'>'.$value.'</a>';
        }

        $out = str_replace('['.$tag.']', $value, $out);
    }

    // Categories (array -> comma separated)
    if (strpos($out,'[categories]') !== false) {
        $cats = [];
        if (!empty($event['categories']) && is_array($event['categories'])) {
            foreach ($event['categories'] as $cat) $cats[] = esc_html($cat['name'] ?? '');
        }
        $out = str_replace('[categories]', implode(', ', $cats), $out);
    }

    return $out;
}

    /**
     * AJAX handler for load more
     */
    public function ajax_load_more() {
        $offset = intval($_POST['offset'] ?? 0);
        $atts = $_POST['atts'] ?? [];
        $atts['offset'] = $offset;
        $atts['max'] = intval($atts['max'] ?? 10);

        $events = JEM_Events_Fetcher::get_events($atts);
        if (empty($events)) {
            wp_send_json(['html'=>'','more'=>false]);
        }

        $settings = get_option('jem_events_settings', []);
        ob_start();
        foreach ($events as $ev) {
            echo $this->render_event_with_template($ev, $settings);
        }
        $html = ob_get_clean();
        wp_send_json(['html'=>$html,'more'=>count($events) >= intval($atts['max'])]);
    }

    /**
     * Print custom CSS from admin only if shortcode was used on the page.
     */
    public function maybe_print_custom_css() {
        if (!self::$shortcode_used) return;
        $settings = get_option('jem_events_settings', []);
        $css = trim($settings['custom_css'] ?? '');
        if (!$css) return;
        echo '<style id="jem-events-custom-css">' . esc_html($css) . '</style>';
    }
}
