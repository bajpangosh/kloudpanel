<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="kloudpanel-add-instance">
        <form method="post" action="" class="kloudpanel-form">
            <?php wp_nonce_field('kloudpanel_add_instance', 'kloudpanel_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="instance_name"><?php _e('Instance Name', 'kloudpanel'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="instance_name" name="instance_name" class="regular-text" required>
                        <p class="description"><?php _e('A friendly name for your CyberPanel instance', 'kloudpanel'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="instance_url"><?php _e('Instance URL', 'kloudpanel'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="instance_url" name="instance_url" class="regular-text" required>
                        <p class="description"><?php _e('The URL of your CyberPanel instance (e.g., https://panel.example.com)', 'kloudpanel'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="api_key"><?php _e('API Key', 'kloudpanel'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="api_key" name="api_key" class="regular-text" required>
                        <p class="description"><?php _e('Your CyberPanel API key', 'kloudpanel'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Add Instance', 'kloudpanel'); ?>">
            </p>
        </form>
    </div>
    
    <div class="kloudpanel-instances-list">
        <h2><?php _e('Managed Instances', 'kloudpanel'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'kloudpanel'); ?></th>
                    <th><?php _e('URL', 'kloudpanel'); ?></th>
                    <th><?php _e('Status', 'kloudpanel'); ?></th>
                    <th><?php _e('Actions', 'kloudpanel'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $instances = get_option('kloudpanel_instances', array());
                if (!empty($instances)) {
                    foreach ($instances as $id => $instance) {
                        ?>
                        <tr>
                            <td><?php echo esc_html($instance['name']); ?></td>
                            <td><?php echo esc_url($instance['url']); ?></td>
                            <td class="instance-status" data-id="<?php echo esc_attr($id); ?>">
                                <span class="checking"><?php _e('Checking...', 'kloudpanel'); ?></span>
                            </td>
                            <td>
                                <a href="#" class="button button-small delete-instance" data-id="<?php echo esc_attr($id); ?>">
                                    <?php _e('Delete', 'kloudpanel'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="4"><?php _e('No instances added yet.', 'kloudpanel'); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.kloudpanel-form {
    max-width: 800px;
    margin-top: 20px;
}

.kloudpanel-instances-list {
    margin-top: 40px;
}

.instance-status .checking {
    color: #737373;
}

.instance-status .online {
    color: #46b450;
}

.instance-status .offline {
    color: #dc3232;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle form submission
    $('.kloudpanel-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('#submit');
        
        submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kloudpanel_add_instance',
                nonce: $('#kloudpanel_nonce').val(),
                instance_name: $('#instance_name').val(),
                instance_url: $('#instance_url').val(),
                api_key: $('#api_key').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });
    
    // Handle instance deletion
    $('.delete-instance').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this instance?')) {
            return;
        }
        
        var button = $(this);
        var instanceId = button.data('id');
        
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kloudpanel_delete_instance',
                nonce: $('#kloudpanel_nonce').val(),
                instance_id: instanceId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
</script>
