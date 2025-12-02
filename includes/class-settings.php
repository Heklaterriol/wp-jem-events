<?php

class JEM_Events_Settings {

    private $option_name = 'jem_events_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
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
        register_setting($this->option_name, $this->option_name);

        add_settings_section('general', __('General Settings','jem-events'), null, $this->option_name);

        add_settings_field('domain', __('Joomla Base URL','jem-events'), function() {
            $opt = get_option($this->option_name);
            echo '<input type="text" name="jem_events_settings[domain]" value="' . esc_attr($opt['domain'] ?? '') . '" style="width: 300px;">';
        }, $this->option_name, 'general');

        add_settings_field('token', __('API Token (optional)','jem-events'), function() {
            $opt = get_option($this->option_name);
            echo '<input type="text" name="jem_events_settings[token]" value="' . esc_attr($opt['token'] ?? '') . '" style="width: 300px;">';
        }, $this->option_name, 'general');
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
