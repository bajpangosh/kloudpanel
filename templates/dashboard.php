<?php
if (!defined('ABSPATH')) {
    exit;
}

$instances = get_option('kloudpanel_instances', array());
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (empty($instances)) : ?>
        <div class="notice notice-info">
            <p>
                <?php _e('No CyberPanel instances found. ', 'kloudpanel'); ?>
                <a href="<?php echo admin_url('admin.php?page=kloudpanel-add-instance'); ?>">
                    <?php _e('Add your first instance', 'kloudpanel'); ?>
                </a>
            </p>
        </div>
    <?php else : ?>
        <div class="kloudpanel-dashboard">
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="card">
                    <h3><?php _e('Total Instances', 'kloudpanel'); ?></h3>
                    <div class="count"><?php echo count($instances); ?></div>
                </div>
                <div class="card">
                    <h3><?php _e('Online Instances', 'kloudpanel'); ?></h3>
                    <div class="count" id="online-count">-</div>
                </div>
                <div class="card">
                    <h3><?php _e('Total Websites', 'kloudpanel'); ?></h3>
                    <div class="count" id="websites-count">-</div>
                </div>
            </div>

            <!-- Instances Grid -->
            <div class="instances-grid">
                <?php foreach ($instances as $id => $instance) : ?>
                    <div class="instance-card" data-id="<?php echo esc_attr($id); ?>">
                        <div class="instance-header">
                            <h3><?php echo esc_html($instance['name']); ?></h3>
                            <span class="status">Checking...</span>
                        </div>
                        <div class="instance-body">
                            <div class="stats">
                                <div class="stat">
                                    <label><?php _e('CPU Usage', 'kloudpanel'); ?></label>
                                    <span class="cpu-usage">-</span>
                                </div>
                                <div class="stat">
                                    <label><?php _e('Memory Usage', 'kloudpanel'); ?></label>
                                    <span class="memory-usage">-</span>
                                </div>
                                <div class="stat">
                                    <label><?php _e('Disk Usage', 'kloudpanel'); ?></label>
                                    <span class="disk-usage">-</span>
                                </div>
                                <div class="stat">
                                    <label><?php _e('Websites', 'kloudpanel'); ?></label>
                                    <span class="websites">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="instance-footer">
                            <a href="<?php echo esc_url($instance['url']); ?>" target="_blank" class="button">
                                <?php _e('Open Panel', 'kloudpanel'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.kloudpanel-dashboard {
    margin-top: 20px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-cards .card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.summary-cards .card h3 {
    margin: 0 0 10px 0;
    color: #23282d;
    font-size: 14px;
}

.summary-cards .card .count {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.instances-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.instance-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.instance-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.instance-header h3 {
    margin: 0;
    font-size: 16px;
}

.instance-header .status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.instance-header .status.online {
    background: #edfaef;
    color: #46b450;
}

.instance-header .status.offline {
    background: #fbeaea;
    color: #dc3232;
}

.instance-body {
    padding: 20px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.stat {
    text-align: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-bottom: 5px;
}

.stat span {
    font-size: 16px;
    font-weight: 500;
    color: #1d2327;
}

.instance-footer {
    padding: 15px 20px;
    border-top: 1px solid #f0f0f1;
    text-align: right;
}
</style>

<script>
jQuery(document).ready(function($) {
    function updateInstanceStatus(instanceId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kloudpanel_check_instance_status',
                nonce: kloudpanelData.nonce,
                instance_id: instanceId
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var card = $('.instance-card[data-id="' + instanceId + '"]');
                    
                    // Update status
                    card.find('.status')
                        .text(data.status ? 'Online' : 'Offline')
                        .removeClass('online offline')
                        .addClass(data.status ? 'online' : 'offline');
                    
                    // Update stats
                    card.find('.cpu-usage').text(data.cpu_usage + '%');
                    card.find('.memory-usage').text(data.memory_usage + '%');
                    card.find('.disk-usage').text(data.disk_usage + '%');
                    card.find('.websites').text(data.websites_count);
                }
            }
        });
    }
    
    // Update all instances status
    function updateAllInstances() {
        $('.instance-card').each(function() {
            updateInstanceStatus($(this).data('id'));
        });
        
        // Update summary counts
        var onlineCount = $('.status.online').length;
        var websitesCount = 0;
        $('.websites').each(function() {
            var count = parseInt($(this).text());
            if (!isNaN(count)) {
                websitesCount += count;
            }
        });
        
        $('#online-count').text(onlineCount);
        $('#websites-count').text(websitesCount);
    }
    
    // Initial update
    updateAllInstances();
    
    // Update every 30 seconds
    setInterval(updateAllInstances, 30000);
});
</script>
