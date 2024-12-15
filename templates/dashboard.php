<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap kloudpanel-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="kloudpanel-grid">
        <!-- Summary Cards -->
        <div class="kloudpanel-card summary-card">
            <h3>Total Instances</h3>
            <div class="card-content">
                <span class="count" id="total-instances">-</span>
            </div>
        </div>
        
        <div class="kloudpanel-card summary-card">
            <h3>Active Websites</h3>
            <div class="card-content">
                <span class="count" id="active-websites">-</span>
            </div>
        </div>
        
        <div class="kloudpanel-card summary-card">
            <h3>Total Storage</h3>
            <div class="card-content">
                <span class="count" id="total-storage">-</span>
            </div>
        </div>
    </div>

    <!-- Instances List -->
    <div class="kloudpanel-instances">
        <h2>CyberPanel Instances</h2>
        <div class="instances-grid" id="instances-list">
            <!-- Instances will be loaded here via JavaScript -->
        </div>
    </div>

    <!-- Add Instance Modal -->
    <div id="add-instance-modal" class="kloudpanel-modal">
        <div class="modal-content">
            <h2>Add New Instance</h2>
            <form id="add-instance-form">
                <div class="form-group">
                    <label for="instance-name">Instance Name</label>
                    <input type="text" id="instance-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="instance-url">URL</label>
                    <input type="url" id="instance-url" name="url" required>
                </div>
                <div class="form-group">
                    <label for="instance-api-key">API Key</label>
                    <input type="password" id="instance-api-key" name="api_key" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button button-primary">Add Instance</button>
                    <button type="button" class="button" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
