<?php
class KloudPanel {
    private $hetzner_api;

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_get_server_status', array($this, 'ajax_get_server_status'));
        add_action('wp_ajax_save_api_token', array($this, 'ajax_save_api_token'));
        
        // Initialize Hetzner API if token exists
        $api_token = $this->get_api_token();
        if ($api_token) {
            $this->hetzner_api = new Hetzner_API($api_token);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'KloudPanel',
            'KloudPanel',
            'manage_options',
            'kloudpanel',
            array($this, 'render_dashboard'),
            'dashicons-cloud',
            30
        );

        add_submenu_page(
            'kloudpanel',
            'Settings',
            'Settings',
            'manage_options',
            'kloudpanel-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'kloudpanel') === false) {
            return;
        }

        wp_enqueue_style('kloudpanel-admin', KLOUDPANEL_PLUGIN_URL . 'assets/css/admin.css', array(), KLOUDPANEL_VERSION);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
        wp_enqueue_script('kloudpanel-admin', KLOUDPANEL_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'chart-js'), KLOUDPANEL_VERSION, true);
        
        wp_localize_script('kloudpanel-admin', 'kloudpanel', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kloudpanel-nonce')
        ));
    }

    public function render_dashboard() {
        if (!$this->get_api_token()) {
            echo '<div class="wrap"><div class="notice notice-warning"><p>Please configure your Hetzner API token in the settings first.</p></div></div>';
            return;
        }
        include KLOUDPANEL_PLUGIN_DIR . 'templates/dashboard.php';
    }

    public function render_settings() {
        include KLOUDPANEL_PLUGIN_DIR . 'templates/settings.php';
    }

    public function ajax_get_server_status() {
        check_ajax_referer('kloudpanel-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $servers = $this->hetzner_api->get_servers();
        $server_data = array();

        if (isset($servers['servers'])) {
            foreach ($servers['servers'] as $server) {
                $metrics = $this->hetzner_api->get_server_metrics($server['id']);
                $server_data[] = array(
                    'id' => $server['id'],
                    'name' => $server['name'],
                    'status' => $server['status'],
                    'ip' => $server['public_net']['ipv4']['ip'],
                    'type' => $server['server_type']['name'],
                    'datacenter' => $server['datacenter']['name'],
                    'metrics' => $metrics
                );
            }
        }

        wp_send_json_success($server_data);
    }

    public function ajax_save_api_token() {
        check_ajax_referer('kloudpanel-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $api_token = sanitize_text_field($_POST['api_token']);
        
        global $wpdb;
        $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'kloudpanel_hetzner_api');
        $wpdb->insert(
            $wpdb->prefix . 'kloudpanel_hetzner_api',
            array('api_token' => $api_token),
            array('%s')
        );

        wp_send_json_success('API token saved successfully');
    }

    private function get_api_token() {
        global $wpdb;
        $token = $wpdb->get_var("SELECT api_token FROM {$wpdb->prefix}kloudpanel_hetzner_api ORDER BY id DESC LIMIT 1");
        return $token;
    }
}
