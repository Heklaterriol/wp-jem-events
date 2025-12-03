<?php
if (!defined('ABSPATH')) exit;

class JEM_Events_Settings {

    private $option_name = 'jem_events_settings';
    private $defaults = [
        'domain' => '',
        'token' => '',
        'type' => '',
        'featured' => '',
        'title' => 'link',
        'date' => 'no_link',
        'category' => 'no_link',
        'venue' => 'no_link',
        'max' => 10,
        'cuttitle' => '',
        'noeventsmsg' => '',
        'catids' => '',
        'venueids' => '',
        'target_blank' => 0,
        'template' => "<div class=\"jem-event\">\n  <h3>[title]</h3>\n  <div>[date] [time]</div>\n  <div>[venue]</div>\n  <p>[description]</p>\n</div>",
        'custom_css' => ""
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page_jem-events') return;
        wp_enqueue_script('jem-events-admin', JEM_EVENTS_URL . 'js/admin.js', ['jquery'], '1.0', true);
        // Localize replacement tags for admin JS
        $tags = [
            'title','title_url','date','time','endtime','venue','venue_url','address','categories','description','id','raw'
        ];
        wp_localize_script('jem-events-admin', 'JEMAdmin', ['tags' => $tags]);
    }

    public function add_menu() {
        add_options_page(
            __('JEM Events Settings','jem-events'),
            __('JEM Events','jem-events'),
            'manage_options',
            'jem-events',
            [$this, 'settings_page']
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, [$this, 'sanitize_options']);

        add_settings_section('general', __('General Settings','jem-events'), null, $this->option_name);

        // Domain
        add_settings_field('domain', __('Joomla Base URL','jem-events'), [$this, 'field_domain'], $this->option_name, 'general');
        add_settings_field('token', __('API Token (optional)','jem-events'), [$this, 'field_token'], $this->option_name, 'general');

        // Dropdowns
        add_settings_field('type', __('Type','jem-events'), [$this, 'field_select_type'], $this->option_name, 'general');
        add_settings_field('featured', __('Featured','jem-events'), [$this, 'field_select_featured'], $this->option_name, 'general');

        // Link options
        add_settings_field('title', __('Title link behavior','jem-events'), [$this, 'field_select_title'], $this->option_name, 'general');
        add_settings_field('date', __('Date link behavior','jem-events'), [$this, 'field_select_date'], $this->option_name, 'general');
        add_settings_field('category', __('Category link behavior','jem-events'), [$this, 'field_select_category'], $this->option_name, 'general');
        add_settings_field('venue', __('Venue link behavior','jem-events'), [$this, 'field_select_venue'], $this->option_name, 'general');

        // Other fields
        add_settings_field('max', __('Max events','jem-events'), [$this, 'field_max'], $this->option_name, 'general');
        add_settings_field('cuttitle', __('Cut title (chars)','jem-events'), [$this, 'field_cuttitle'], $this->option_name, 'general');
        add_settings_field('noeventsmsg', __('No events message','jem-events'), [$this, 'field_noeventsmsg'], $this->option_name, 'general');
        add_settings_field('catids', __('Category IDs (comma separated)','jem-events'), [$this, 'field_catids'], $this->option_name, 'general');
        add_settings_field('venueids', __('Venue IDs (comma separated)','jem-events'), [$this, 'field_venueids'], $this->option_name, 'general');

        // Target blank
        add_settings_field('target_blank', __('Open links in new window','jem-events'), [$this, 'field_target_blank'], $this->option_name, 'general');

        // Template
        add_settings_section('template_section', __('Template / CSS','jem-events'), null, $this->option_name);
        add_settings_field('template', __('Event Template','jem-events'), [$this, 'field_template'], $this->option_name, 'template_section');
        add_settings_field('custom_css', __('Custom CSS (applies only where shortcode used)','jem-events'), [$this, 'field_custom_css'], $this->option_name, 'template_section');
    }

    public function sanitize_options($input) {
        $out = get_option($this->option_name, $this->defaults);

        $out['domain'] = esc_url_raw(trim($input['domain'] ?? ''));
        $out['token'] = sanitize_text_field($input['token'] ?? '');
        $out['type'] = sanitize_text_field($input['type'] ?? '');
        $out['featured'] = sanitize_text_field($input['featured'] ?? '');
        $out['title'] = in_array($input['title'] ?? 'link', ['link','no_link']) ? $input['title'] : 'link';
        $out['date'] = in_array($input['date'] ?? 'no_link', ['link','no_link']) ? $input['date'] : 'no_link';
        $out['category'] = in_array($input['category'] ?? 'no_link', ['link','no_link']) ? $input['category'] : 'no_link';
        $out['venue'] = in_array($input['venue'] ?? 'no_link', ['link','no_link']) ? $input['venue'] : 'no_link';
        $out['max'] = intval($input['max'] ?? 10);
        $out['cuttitle'] = intval($input['cuttitle'] ?? 0);
        $out['noeventsmsg'] = sanitize_text_field($input['noeventsmsg'] ?? '');
        $out['catids'] = sanitize_text_field($input['catids'] ?? '');
        $out['venueids'] = sanitize_text_field($input['venueids'] ?? '');
        $out['target_blank'] = !empty($input['target_blank']) ? 1 : 0;
        // Template: allow safe HTML
        $out['template'] = wp_kses_post($input['template'] ?? $this->defaults['template']);
        // Custom CSS: allow CSS as raw text but strip < and >
        $out['custom_css'] = str_replace(['<','>'], ['',''], $input['custom_css'] ?? '');
        return $out;
    }

    // Field callbacks
    public function field_domain() {
        $opt = get_option($this->option_name, $this->defaults);
        printf('<input type="text" name="%s[domain]" value="%s" style="width:400px">', $this->option_name, esc_attr($opt['domain']));
    }

    public function field_token() {
        $opt = get_option($this->option_name, $this->defaults);
        printf('<input type="text" name="%s[token]" value="%s" style="width:300px">', $this->option_name, esc_attr($opt['token']));
    }

    public function field_select_type() {
        $opt = get_option($this->option_name, $this->defaults);
        $vals = [''=>'(none)','today'=>'today','unfinished'=>'unfinished','upcoming'=>'upcoming','archived'=>'archived','newest'=>'newest'];
        echo '<select name="'.$this->option_name.'[type]">';
        foreach($vals as $k=>$v) printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($opt['type'],$k,false), esc_html($v));
        echo '</select>';
    }

    public function field_select_featured() {
        $opt = get_option($this->option_name, $this->defaults);
        $vals = [''=>'(none)','on'=>'on','off'=>'off','only'=>'only'];
        echo '<select name="'.$this->option_name.'[featured]">';
        foreach($vals as $k=>$v) printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($opt['featured'],$k,false), esc_html($v));
        echo '</select>';
    }

    public function field_select_title() {
        $opt = get_option($this->option_name, $this->defaults);
        echo '<select name="'.$this->option_name.'[title]">';
        printf('<option value="link"%s>%s</option>', selected($opt['title'],'link',false), 'link');
        printf('<option value="no_link"%s>%s</option>', selected($opt['title'],'no_link',false), 'no link');
        echo '</select>';
    }

    public function field_select_date() {
        $opt = get_option($this->option_name, $this->defaults);
        echo '<select name="'.$this->option_name.'[date]">';
        printf('<option value="link"%s>%s</option>', selected($opt['date'],'link',false), 'link');
        printf('<option value="no_link"%s>%s</option>', selected($opt['date'],'no_link',false), 'no link');
        echo '</select>';
    }

    public function field_select_category() {
        $opt = get_option($this->option_name, $this->defaults);
        echo '<select name="'.$this->option_name.'[category]">';
        printf('<option value="link"%s>%s</option>', selected($opt['category'],'link',false), 'link');
        printf('<option value="no_link"%s>%s</option>', selected($opt['category'],'no_link',false), 'no link');
        echo '</select>';
    }

    public function field_select_venue() {
        $opt = get_option($this->option_name, $this->defaults);
        echo '<select name="'.$this->option_name.'[venue]">';
        printf('<option value="link"%s>%s</option>', selected($opt['venue'],'link',false), 'link');
        printf('<option value="no_link"%s>%s</option>', selected($opt['venue'],'no_link',false), 'no link');
        echo '</select>';
    }

    public function field_max() {
        $opt = get_option($this->option_name, $this->defaults);
        printf('<input type="number" name="%s[max]" value="%s" style="width:80px">', $this->option_name, esc_attr($opt['max']));
    }

    public function field_cuttitle() {
        $opt = get_option($this->option_name, $this->defaults);
        printf('<input type="number" name="%s[cuttitle]" value="%s" style="width:80px">', $this->option_name, esc_attr($opt['cuttitle']));
    }

    public function field_noeventsmsg() {
        $opt = get_option($this->option_name, $this->defaults);
        printf('<input type="text" name="%s[noeventsmsg]" value="%s" style="width:400px">', $this->option_name, esc_attr($opt['noeventsmsg']));
    }

    public function field_catids() {
        $opt = get_option($this->option_name, $this->defaults);
        printf('<input type="text" name="%s[catids]" value="%s" style="width:300px">', $this->option_name, esc_attr($opt['catids']));
    }

    public function field_venueids() {
        $opt = get_option($this->option_name, $this->defaults);
        printf('<input type="text" name="%s[venueids]" value="%s" style="width:300px">', $this->option_name, esc_attr($opt['venueids']));
    }

    public function field_target_blank() {
        $opt = get_option($this->option_name, $this->defaults);
        printf('<input type="checkbox" name="%s[target_blank]" value="1" %s> %s', $this->option_name, checked($opt['target_blank'],1,false), __('Open links in new window','jem-events'));
    }

    public function field_template() {
        $opt = get_option($this->option_name, $this->defaults);
        echo '<div style="display:flex; gap:20px;">';
        printf('<textarea id="jem_template" name="%s[template]" rows="12" style="width:70%%;">%s</textarea>', $this->option_name, esc_textarea($opt['template']));
        // replacement tags list / clickable
        echo '<div style="width:25%;">';
        echo '<p><strong>'.__('Replacement tags','jem-events').'</strong></p>';
        $tags = ['title','title_url','date','time','endtime','venue','venue_url','address','categories','description','id','raw'];
        foreach($tags as $t) {
            printf('<div style="margin:4px 0;"><a href="#" class="jem-insert-tag" data-tag="[%s]">[%s]</a></div>', esc_attr($t), esc_html($t));
        }
        echo '<p style="font-size:90%;">'.__('Click tag to insert into template at cursor position.','jem-events').'</p>';
        echo '</div>'; // end right column
        echo '</div>';
    }

    public function field_custom_css() {
        $opt = get_option($this->option_name, $this->defaults);
        printf('<textarea name="%s[custom_css]" rows="8" style="width:100%%;">%s</textarea>', $this->option_name, esc_textarea($opt['custom_css']));
        echo '<p style="font-size:90%;">'.__('CSS will be injected only on pages where the shortcode is used. No < or > characters are allowed.','jem-events').'</p>';
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>' . __('JEM Events Settings','jem-events') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields($this->option_name);
        do_settings_sections($this->option_name);
        submit_button();
        echo '</form></div>';
    }
}
