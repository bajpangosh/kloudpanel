<?php
class KloudPanel {
    private $version;

    public function __construct() {
        $this->version = KLOUDPANEL_VERSION;
    }

    public function init() {
        // Add menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        // Add settings
        add_action('admin_init', array($this, 'register_settings'));
        // Add scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'KloudPanel', 
            'KloudPanel',
            'manage_options',
            'kloudpanel',
            array($this, 'render_dashboard_page'),
            'dashicons-cloud',
            30
        );

        add_submenu_page(
            'kloudpanel',
            'Settings',
            'Settings',
            'manage_options',
            'kloudpanel-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('kloudpanel_settings', 'kloudpanel_refresh_interval');
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'kloudpanel') === false) {
            return;
        }

        wp_enqueue_style(
            'kloudpanel-admin',
            KLOUDPANEL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            'kloudpanel-admin',
            KLOUDPANEL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script('kloudpanel-admin', 'kloudpanelData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kloudpanel_nonce')
        ));
    }

    public function render_dashboard_page() {
        include KLOUDPANEL_PLUGIN_DIR . 'templates/dashboard.php';
    }

    public function render_settings_page() {
        include KLOUDPANEL_PLUGIN_DIR . 'templates/settings.php';
    }
}
