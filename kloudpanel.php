<?php
/**
 * Plugin Name: KloudPanel
 * Plugin URI: https://github.com/bajpangosh/kloudpanel
 * Description: A beautiful dashboard to monitor your Hetzner Cloud instances
 * Version: 1.0.0
 * Author: BajPanGosh
 * Author URI: https://github.com/bajpangosh
 * License: GPL v2 or later
 * Text Domain: kloudpanel
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KLOUDPANEL_VERSION', '1.0.0');
define('KLOUDPANEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KLOUDPANEL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once KLOUDPANEL_PLUGIN_DIR . 'includes/class-kloudpanel.php';
require_once KLOUDPANEL_PLUGIN_DIR . 'includes/class-hetzner-api.php';

// Initialize the plugin
function kloudpanel_init() {
    $kloudpanel = new KloudPanel();
    $kloudpanel->init();
}
add_action('plugins_loaded', 'kloudpanel_init');

// Activation Hook
register_activation_hook(__FILE__, 'kloudpanel_activate');
function kloudpanel_activate() {
    // Create necessary database tables
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}kloudpanel_hetzner_api (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        api_token varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Deactivation Hook
register_deactivation_hook(__FILE__, 'kloudpanel_deactivate');
function kloudpanel_deactivate() {
    // Cleanup if needed
}
