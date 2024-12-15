<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap kloudpanel-dashboard">
    <div class="dashboard-header">
        <h1>KloudPanel Dashboard</h1>
        <div class="dashboard-actions">
            <button id="add-api-key" class="button button-primary">Add API Key</button>
            <button id="refresh-dashboard" class="button"><span class="dashicons dashicons-update"></span> Refresh</button>
        </div>
    </div>

    <div id="loading-progress" class="progress-bar">
        <div class="progress-value"></div>
    </div>

    <div class="api-groups-container">
        <!-- API Groups will be loaded here -->
    </div>

    <!-- API Group Template -->
    <template id="api-group-template">
        <div class="api-group-card">
            <div class="group-header">
                <div class="group-info">
                    <h2 class="group-name"></h2>
                    <span class="api-key-hint"></span>
                </div>
                <div class="group-actions">
                    <button class="button edit-api">Edit API Key</button>
                    <button class="button delete-api">Delete</button>
                </div>
            </div>
            <div class="servers-grid">
                <!-- Servers will be loaded here -->
            </div>
        </div>
    </template>

    <!-- Server Template -->
    <template id="server-template">
        <div class="server-card">
            <div class="server-header">
                <div class="server-status"></div>
                <h3 class="server-name"></h3>
            </div>
            <div class="server-details">
                <div class="detail-row">
                    <span class="label">IP Address:</span>
                    <span class="ip-address"></span>
                </div>
                <div class="detail-row">
                    <span class="label">Created:</span>
                    <span class="created-date"></span>
                </div>
                <div class="detail-row">
                    <span class="label">Age:</span>
                    <span class="server-age"></span>
                </div>
            </div>
            <div class="server-actions">
                <button class="button power-action">
                    <span class="dashicons"></span>
                </button>
                <button class="button edit-server">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <button class="button delete-server">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
    </template>

    <!-- Add/Edit API Key Modal -->
    <div id="api-key-modal" class="modal">
        <div class="modal-content">
            <h2>Add API Key Group</h2>
            <form id="api-key-form">
                <div class="form-group">
                    <label for="group-name">Group Name</label>
                    <input type="text" id="group-name" name="group-name" required placeholder="e.g., Production Servers">
                </div>
                <div class="form-group">
                    <label for="api-key">Hetzner API Key</label>
                    <input type="password" id="api-key" name="api-key" required placeholder="Enter your Hetzner Cloud API key">
                    <p class="description">Your API key will be securely stored and encrypted.</p>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button button-primary">Save API Key</button>
                    <button type="button" class="button modal-close">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.kloudpanel-dashboard {
    margin: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.dashboard-actions {
    display: flex;
    gap: 15px;
}

.progress-bar {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 30px;
    margin-top: 4px;
    display: none;
}

.progress-value {
    height: 100%;
    width: 0;
    background: #2271b1;
    border-radius: 2px;
}

.api-groups-container {
    display: grid;
    gap: 30px;
}

.api-group-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.api-group-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.group-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.group-name {
    margin: 0;
    font-size: 18px;
    color: #1d2327;
}

.api-key-hint {
    font-size: 12px;
    color: #666;
}

.group-actions {
    display: flex;
    gap: 8px;
}

.servers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.server-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.server-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.server-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.server-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.server-status.running {
    background: #d1fae5;
    color: #065f46;
}

.server-status.stopped {
    background: #fee2e2;
    color: #991b1b;
}

.server-actions {
    display: flex;
    gap: 8px;
}

.server-actions .button {
    padding: 4px;
    min-height: 30px;
    line-height: 1;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.label {
    font-size: 12px;
    color: #666;
}

.server-details {
    margin-bottom: 20px;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1;
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.modal-close {
    background: #f0f0f0;
    color: #666;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    cursor: pointer;
}

.modal-close:hover {
    background: #e5e5e5;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
}

.form-group input, .form-group textarea {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.button {
    padding: 10px 20px;
    font-size: 14px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.button-primary {
    background: #2271b1;
    color: white;
}

.button-primary:hover {
    background: #1a6da8;
}

.button-secondary {
    background: #f0f0f0;
    color: #666;
}

.button-secondary:hover {
    background: #e5e5e5;
}

@media (max-width: 782px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .api-groups-container {
        grid-template-columns: 1fr;
    }
    
    .servers-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add event listeners for API key actions
    $('#add-api-key').on('click', function() {
        // Show API key modal
        $('#api-key-modal').fadeIn();
    });

    // Add event listener for API key form submission
    $('#api-key-form').on('submit', function(e) {
        e.preventDefault();
        // Get API key group name and API key
        const groupName = $('#group-name').val();
        const apiKey = $('#api-key').val();

        // Create API key group card
        const apiKeyGroupCard = createApiKeyGroupCard(groupName, apiKey);

        // Add API key group card to API key groups container
        $('.api-groups-container').append(apiKeyGroupCard);

        // Hide API key modal
        $('#api-key-modal').fadeOut();
    });

    // Function to create API key group card
    function createApiKeyGroupCard(groupName, apiKey) {
        const template = document.getElementById('api-group-template');
        const card = $(template.content.cloneNode(true));

        card.find('.group-name').text(groupName);
        card.find('.api-key-hint').text('API Key: ' + apiKey.substring(0, 10) + '...');

        return card;
    }

    // Add event listeners for server actions
    $(document).on('click', '.add-server', function() {
        // Show server modal
        // ...
    });

    $(document).on('click', '.edit-server', function() {
        // Show server modal
        // ...
    });

    $(document).on('click', '.delete-server', function() {
        // Delete server
        // ...
    });
});
</script>
