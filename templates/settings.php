<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('kloudpanel_settings');
        do_settings_sections('kloudpanel_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="refresh_interval">Dashboard Refresh Interval</label>
                </th>
                <td>
                    <select name="kloudpanel_refresh_interval" id="refresh_interval">
                        <option value="30">30 seconds</option>
                        <option value="60">1 minute</option>
                        <option value="300">5 minutes</option>
                        <option value="600">10 minutes</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
