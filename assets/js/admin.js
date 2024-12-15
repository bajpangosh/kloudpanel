jQuery(document).ready(function($) {
    // Refresh dashboard data periodically
    function refreshDashboard() {
        $.ajax({
            url: kloudpanelData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'kloudpanel_get_dashboard_data',
                nonce: kloudpanelData.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                }
            }
        });
    }

    function updateDashboard(data) {
        // Update summary cards
        $('#total-instances').text(data.total_instances);
        $('#active-websites').text(data.active_websites);
        $('#total-storage').text(data.total_storage);

        // Update instances list
        const instancesList = $('#instances-list');
        instancesList.empty();

        data.instances.forEach(function(instance) {
            instancesList.append(createInstanceCard(instance));
        });
    }

    function createInstanceCard(instance) {
        return `
            <div class="instance-card">
                <div class="instance-header">
                    <span class="instance-name">${instance.name}</span>
                    <span class="instance-status status-${instance.status}">${instance.status}</span>
                </div>
                <div class="instance-stats">
                    <div class="stat-item">
                        <div class="stat-label">CPU Usage</div>
                        <div class="stat-value">${instance.cpu_usage}%</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Memory Usage</div>
                        <div class="stat-value">${instance.memory_usage}%</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Disk Usage</div>
                        <div class="stat-value">${instance.disk_usage}%</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Websites</div>
                        <div class="stat-value">${instance.websites_count}</div>
                    </div>
                </div>
            </div>
        `;
    }

    // Add Instance Form Handler
    $('#add-instance-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'kloudpanel_add_instance',
            nonce: kloudpanelData.nonce,
            name: $('#instance-name').val(),
            url: $('#instance-url').val(),
            api_key: $('#instance-api-key').val()
        };

        $.ajax({
            url: kloudpanelData.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    closeModal();
                    refreshDashboard();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Modal Functions
    window.openModal = function() {
        $('#add-instance-modal').show();
    }

    window.closeModal = function() {
        $('#add-instance-modal').hide();
        $('#add-instance-form')[0].reset();
    }

    // Initial load
    refreshDashboard();

    // Set up refresh interval
    const refreshInterval = parseInt($('#refresh_interval').val()) || 60;
    setInterval(refreshDashboard, refreshInterval * 1000);
});
