<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/class-debugger.php';

class JEMEmbed_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_menu() {
        add_menu_page(
            'JEM Embed',
            'JEM Embed',
            'manage_options',
            'jemembed',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('jemembed_settings', 'jemembed_options', [
            'default' => $this->get_defaults(),
            'sanitize_callback' => [$this, 'validate']
        ]);

        add_settings_section(
            'jemembed_main',
            'JEM Embed Settings',
            null,
            'jemembed'
        );

        // Beispiel für ein Dropdown-Feld
        add_settings_field(
            'type',
            'Type',
            [$this, 'field_type'],
            'jemembed',
            'jemembed_main'
        );

        // Weitere Felder: featured, title, date, venue, category, max, cuttitle, noeventsmsg, catids, venueids, template, css, target_blank
        add_settings_field('featured', 'Featured', [$this, 'field_featured'], 'jemembed', 'jemembed_main');
        add_settings_field('title', 'Title Link', [$this, 'field_title'], 'jemembed', 'jemembed_main');
        add_settings_field('date', 'Date Link', [$this, 'field_date'], 'jemembed', 'jemembed_main');
        add_settings_field('venue', 'Venue Link', [$this, 'field_venue'], 'jemembed', 'jemembed_main');
        add_settings_field('category', 'Category Link', [$this, 'field_category'], 'jemembed', 'jemembed_main');
        add_settings_field('max', 'Max Events', [$this, 'field_max'], 'jemembed', 'jemembed_main');
        add_settings_field('cuttitle', 'Cut Title', [$this, 'field_cuttitle'], 'jemembed', 'jemembed_main');
        add_settings_field('noeventsmsg', 'No Events Message', [$this, 'field_noeventsmsg'], 'jemembed', 'jemembed_main');
        add_settings_field('catids', 'Category IDs', [$this, 'field_catids'], 'jemembed', 'jemembed_main');
        add_settings_field('venueids', 'Venue IDs', [$this, 'field_venueids'], 'jemembed', 'jemembed_main');
        add_settings_field('target_blank', 'Open links in new window', [$this, 'field_target_blank'], 'jemembed', 'jemembed_main');
        add_settings_field('template', 'Event Template', [$this, 'field_template'], 'jemembed', 'jemembed_main');
        add_settings_field('css', 'Custom CSS', [$this, 'field_css'], 'jemembed', 'jemembed_main');
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_jemembed') return;

        wp_enqueue_style('jemembed-admin', plugin_dir_url(__FILE__) . 'assets/admin.css');
        wp_enqueue_script('jemembed-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], false, true);
    }

    public function render_settings_page() {
        $options = get_option('jemembed_options');
        include __DIR__ . '/views/admin-settings.php';
    }

    public function get_defaults() {
        return [
            'type' => 'upcoming',
            'featured' => 'off',
            'title' => 'link',
            'date' => 'link',
            'venue' => 'link',
            'category' => 'link',
            'max' => 10,
            'cuttitle' => 0,
            'noeventsmsg' => 'No events found',
            'catids' => '',
            'venueids' => '',
            'template' => '[title] - [date]',
            'css' => '',
            'target_blank' => false
        ];
    }

    public function validate($input) {
        // Sanitizing simple example
        $input['type'] = sanitize_text_field($input['type'] ?? 'upcoming');
        $input['max'] = intval($input['max'] ?? 10);
        $input['cuttitle'] = intval($input['cuttitle'] ?? 0);
        $input['template'] = wp_kses_post($input['template'] ?? '');
        $input['css'] = wp_strip_all_tags($input['css'] ?? '');
        $input['target_blank'] = !empty($input['target_blank']);
        return $input;
    }

    // Beispiel Dropdown-Felder
    public function field_type() { $this->render_dropdown('type', ['today','unfinished','upcoming','archived','newest']); }
    public function field_featured() { $this->render_dropdown('featured', ['on','off','only']); }
    public function field_title() { $this->render_dropdown('title', ['link','no link']); }
    public function field_date() { $this->render_dropdown('date', ['link','no link']); }
    public function field_venue() { $this->render_dropdown('venue', ['link','no link']); }
    public function field_category() { $this->render_dropdown('category', ['link','no link']); }

    // Textfelder
    public function field_max() { $this->render_text('max'); }
    public function field_cuttitle() { $this->render_text('cuttitle'); }
    public function field_noeventsmsg() { $this->render_text('noeventsmsg'); }
    public function field_catids() { $this->render_text('catids'); }
    public function field_venueids() { $this->render_text('venueids'); }

    // Checkbox
    public function field_target_blank() {
        $options = get_option('jemembed_options');
        ?>
        <input type="checkbox" name="jemembed_options[target_blank]" value="1" <?php checked($options['target_blank'], true); ?>>
        <?php
    }

    // Textareas
    public function field_template() {
        $options = get_option('jemembed_options');
        ?>
        <textarea name="jemembed_options[template]" rows="12" style="width:100%;"><?php echo esc_textarea($options['template']); ?></textarea>
        <?php
    }

    public function field_css() {
        $options = get_option('jemembed_options');
        ?>
        <textarea name="jemembed_options[css]" rows="10" style="width:100%;"><?php echo esc_textarea($options['css']); ?></textarea>
        <?php
    }

    // Helper
    private function render_dropdown($field, $options_array) {
        $options = get_option('jemembed_options');
        echo '<select name="jemembed_options['.$field.']">';
        foreach($options_array as $val) {
            echo '<option value="'.esc_attr($val).'" '.selected($options[$field]??'', $val, false).'>'.esc_html($val).'</option>';
        }
        echo '</select>';
    }

    private function render_text($field) {
        $options = get_option('jemembed_options');
        echo '<input type="text" name="jemembed_options['.$field.']" value="'.esc_attr($options[$field] ?? '').'">';
    }
}
