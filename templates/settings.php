<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>KloudPanel Settings</h1>
    
    <div class="card">
        <h2>Hetzner Cloud API Token</h2>
        <p>Enter your Hetzner Cloud API token to connect to your servers.</p>
        
        <form id="kloudpanel-settings-form">
            <?php wp_nonce_field('kloudpanel-nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_token">API Token</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="api_token" 
                               name="api_token" 
                               class="regular-text"
                               value="<?php echo esc_attr($this->get_api_token()); ?>"
                               required>
                        <p class="description">
                            Generate an API token from your <a href="https://console.hetzner.cloud/projects" target="_blank">Hetzner Cloud Console</a>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Save Changes</button>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#kloudpanel-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'save_api_token');
        formData.append('nonce', kloudpanel.nonce);
        
        $.ajax({
            url: kloudpanel.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Settings saved successfully!');
                    window.location.reload();
                } else {
                    alert('Error saving settings: ' + response.data);
                }
            },
            error: function() {
                alert('Error saving settings. Please try again.');
            }
        });
    });
});
