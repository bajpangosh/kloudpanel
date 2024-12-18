<?php
class KloudPanel {
    private $hetzner_api;

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_get_servers_data', array($this, 'ajax_get_servers_data'));
        add_action('wp_ajax_get_server_metrics', array($this, 'ajax_get_server_metrics'));
        add_action('admin_post_kloudpanel_save_token', array($this, 'handle_save_token'));
        
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
            'nonce' => wp_create_nonce('kloudpanel_nonce'),
            'debug' => WP_DEBUG
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

    public function handle_save_token() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('kloudpanel_save_token', 'kloudpanel_nonce');

        $api_token = sanitize_text_field($_POST['api_token']);
        
        if (empty($api_token)) {
            add_settings_error('kloudpanel', 'empty_token', 'API token cannot be empty');
            wp_redirect(admin_url('admin.php?page=kloudpanel-settings'));
            exit;
        }

        // Verify the token by making a test API call
        $test_api = new Hetzner_API($api_token);
        $test_response = $test_api->get_servers();

        if (isset($test_response['error'])) {
            add_settings_error('kloudpanel', 'invalid_token', 'Invalid API token. Please check and try again.');
            wp_redirect(admin_url('admin.php?page=kloudpanel-settings'));
            exit;
        }

        global $wpdb;
        $wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'kloudpanel_hetzner_api');
        $wpdb->insert(
            $wpdb->prefix . 'kloudpanel_hetzner_api',
            array('api_token' => $api_token),
            array('%s')
        );

        wp_redirect(admin_url('admin.php?page=kloudpanel-settings&saved=1'));
        exit;
    }

    public function ajax_get_servers_data() {
        try {
            check_ajax_referer('kloudpanel_nonce', 'nonce');

            error_log('KloudPanel: Fetching server data');
            
            $api_token = $this->get_api_token();
            if (!$api_token) {
                error_log('KloudPanel Error: API token not configured');
                wp_send_json_error(['message' => 'API token not configured. Please configure it in the settings.']);
                return;
            }

            $api = new Hetzner_API($api_token);
            error_log('KloudPanel: Initialized Hetzner API');
            
            $response = $api->get_servers();
            error_log('KloudPanel Debug: Server response - ' . print_r($response, true));

            if (!isset($response['servers'])) {
                $error_msg = isset($response['error']) ? $response['error']['message'] : 'Failed to fetch servers';
                error_log('KloudPanel Error: ' . $error_msg);
                wp_send_json_error(['message' => $error_msg]);
                return;
            }

            $servers = $response['servers'];
            error_log('KloudPanel: Got ' . count($servers) . ' servers');
            
            $costs = $api->calculate_server_costs($servers);
            error_log('KloudPanel Debug: Cost calculation response - ' . print_r($costs, true));

            if (!$costs['success']) {
                error_log('KloudPanel Error: Failed to calculate costs - ' . print_r($costs, true));
                wp_send_json_error(['message' => 'Failed to calculate costs']);
                return;
            }

            $server_data = [];
            foreach ($servers as $server) {
                // Find cost data for this server
                $server_cost = null;
                foreach ($costs['data']['server_costs'] as $cost) {
                    if ($cost['id'] === $server['id']) {
                        $server_cost = $cost;
                        break;
                    }
                }

                $server_data[] = [
                    'id' => $server['id'],
                    'name' => $server['name'],
                    'status' => $server['status'],
                    'ip' => $server['public_net']['ipv4']['ip'],
                    'type' => $server['server_type']['name'],
                    'datacenter' => $server['datacenter']['location']['name'],
                    'hourlyCost' => $server_cost ? $server_cost['hourly'] : 0,
                    'monthlyCost' => $server_cost ? $server_cost['monthly'] : 0
                ];
            }

            error_log('KloudPanel: Processed server data - ' . count($server_data) . ' servers');

            wp_send_json_success([
                'servers' => $server_data,
                'summary' => [
                    'total_hourly' => $costs['data']['total_hourly'],
                    'total_monthly' => $costs['data']['total_monthly']
                ]
            ]);

        } catch (Exception $e) {
            error_log('KloudPanel Error: ' . $e->getMessage());
            error_log('KloudPanel Error Stack Trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => 'Internal server error']);
        }
    }

    public function ajax_get_server_metrics() {
        try {
            check_ajax_referer('kloudpanel_nonce', 'nonce');

            error_log('KloudPanel: Fetching server metrics');

            $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
            if (!$server_id) {
                error_log('KloudPanel Error: Invalid server ID');
                wp_send_json_error(['message' => 'Invalid server ID']);
                return;
            }

            $api_token = $this->get_api_token();
            if (!$api_token) {
                error_log('KloudPanel Error: API token not configured for metrics');
                wp_send_json_error(['message' => 'API token not configured']);
                return;
            }

            $api = new Hetzner_API($api_token);
            $metrics = $api->get_server_metrics($server_id);
            error_log('KloudPanel Debug: Metrics response - ' . print_r($metrics, true));

            if (isset($metrics['metrics'])) {
                wp_send_json_success($metrics['metrics']);
            } else {
                error_log('KloudPanel Error: Failed to fetch metrics - ' . print_r($metrics, true));
                wp_send_json_error(['message' => 'Failed to fetch server metrics']);
            }

        } catch (Exception $e) {
            error_log('KloudPanel Metrics Error: ' . $e->getMessage());
            error_log('KloudPanel Metrics Error Stack Trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => 'Internal server error']);
        }
    }

    private function get_api_token() {
        global $wpdb;
        $token = $wpdb->get_var("SELECT api_token FROM {$wpdb->prefix}kloudpanel_hetzner_api ORDER BY id DESC LIMIT 1");
        return $token;
    }
}
