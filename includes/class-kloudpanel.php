<?php
class KloudPanel {
    private $version;

    public function __construct() {
        $this->version = KLOUDPANEL_VERSION;
    }

    public function init() {
        // Add menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add AJAX handlers
        add_action('wp_ajax_kloudpanel_add_instance', array($this, 'ajax_add_instance'));
        add_action('wp_ajax_kloudpanel_delete_instance', array($this, 'ajax_delete_instance'));
        add_action('wp_ajax_kloudpanel_check_instance_status', array($this, 'ajax_check_instance_status'));
        
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
            'Add Instance',
            'Add Instance',
            'manage_options',
            'kloudpanel-add-instance',
            array($this, 'render_add_instance_page')
        );
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

    public function render_add_instance_page() {
        include KLOUDPANEL_PLUGIN_DIR . 'templates/add-instance.php';
    }

    public function ajax_add_instance() {
        check_ajax_referer('kloudpanel_add_instance', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $instance_name = sanitize_text_field($_POST['instance_name']);
        $instance_url = esc_url_raw($_POST['instance_url']);
        $api_key = sanitize_text_field($_POST['api_key']);

        if (empty($instance_name) || empty($instance_url) || empty($api_key)) {
            wp_send_json_error(array('message' => 'All fields are required'));
        }

        $instances = get_option('kloudpanel_instances', array());
        $instance_id = uniqid('cp_');

        $instances[$instance_id] = array(
            'name' => $instance_name,
            'url' => $instance_url,
            'api_key' => $api_key
        );

        update_option('kloudpanel_instances', $instances);
        wp_send_json_success();
    }

    public function ajax_delete_instance() {
        check_ajax_referer('kloudpanel_add_instance', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $instance_id = sanitize_text_field($_POST['instance_id']);
        $instances = get_option('kloudpanel_instances', array());

        if (isset($instances[$instance_id])) {
            unset($instances[$instance_id]);
            update_option('kloudpanel_instances', $instances);
            wp_send_json_success();
        }

        wp_send_json_error(array('message' => 'Instance not found'));
    }

    public function ajax_check_instance_status() {
        check_ajax_referer('kloudpanel_add_instance', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $instance_id = sanitize_text_field($_POST['instance_id']);
        $instances = get_option('kloudpanel_instances', array());

        if (!isset($instances[$instance_id])) {
            wp_send_json_error(array('message' => 'Instance not found'));
        }

        $instance = $instances[$instance_id];
        $api = new KloudPanel_API($instance['url'], $instance['api_key']);
        $status = $api->get_system_status();

        wp_send_json_success($status);
    }
}
