/* KloudPanel Admin JavaScript */
jQuery(document).ready(function($) {
    // Server status update
    function updateServerStatus() {
        $.ajax({
            url: kloudpanel.ajax_url,
            type: 'POST',
            data: {
                action: 'get_server_status',
                nonce: kloudpanel.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                }
            }
        });
    }

    // Update dashboard with server data
    function updateDashboard(servers) {
        const grid = $('#servers-grid');
        grid.empty();
        
        $('#total-servers').text(servers.length);
        
        let runningServers = 0;
        let totalCost = 0;
        
        servers.forEach(function(server) {
            const card = createServerCard(server);
            grid.append(card);
            
            if (server.status === 'running') {
                runningServers++;
                totalCost += parseFloat(server.price_hourly || 0);
            }
        });
        
        $('#running-servers').text(runningServers);
        $('#total-cost').text('€' + totalCost.toFixed(2));
    }

    // Create server card from template
    function createServerCard(server) {
        const template = document.getElementById('server-card-template');
        const card = $(template.content.cloneNode(true));
        
        // Update basic info
        card.find('.server-name').text(server.name);
        card.find('.server-status')
            .text(server.status)
            .addClass(server.status);
        card.find('.ip').text(server.ip);
        card.find('.type').text(server.type);
        card.find('.datacenter').text(server.datacenter);
        
        // Update metrics if available
        if (server.metrics) {
            updateMetrics(card, server.metrics);
        }
        
        // Setup action buttons
        setupActionButtons(card, server);
        
        return card;
    }

    // Update server metrics
    function updateMetrics(card, metrics) {
        updateResourceBar(card, '.cpu-usage', '.cpu-value', metrics.cpu);
        updateResourceBar(card, '.memory-usage', '.memory-value', metrics.memory);
        updateResourceBar(card, '.disk-usage', '.disk-value', metrics.disk);
    }

    // Update resource usage bar
    function updateResourceBar(card, barSelector, valueSelector, value) {
        const percentage = Math.round(value * 100);
        const bar = card.find(barSelector);
        const valueElement = card.find(valueSelector);
        
        bar.css('width', percentage + '%')
           .removeClass('low medium high')
           .addClass(getResourceClass(percentage));
           
        valueElement.text(percentage + '%');
    }

    // Get resource usage class based on percentage
    function getResourceClass(percentage) {
        if (percentage < 50) return 'low';
        if (percentage < 80) return 'medium';
        return 'high';
    }

    // Setup server action buttons
    function setupActionButtons(card, server) {
        const consoleBtn = card.find('.console-btn');
        const powerBtn = card.find('.power-btn');
        
        consoleBtn.attr('href', `https://console.hetzner.cloud/projects/${server.project_id}/servers/${server.id}/console`);
        
        powerBtn.text(server.status === 'running' ? 'Stop' : 'Start')
                .addClass(server.status === 'running' ? 'button-secondary' : 'button-primary');
                
        powerBtn.on('click', function(e) {
            e.preventDefault();
            toggleServerPower(server.id, server.status);
        });
    }

    // Toggle server power state
    function toggleServerPower(serverId, currentStatus) {
        const action = currentStatus === 'running' ? 'poweroff' : 'poweron';
        
        $.ajax({
            url: kloudpanel.ajax_url,
            type: 'POST',
            data: {
                action: 'toggle_server_power',
                nonce: kloudpanel.nonce,
                server_id: serverId,
                power_action: action
            },
            success: function(response) {
                if (response.success) {
                    updateServerStatus();
                } else {
                    alert('Failed to change server power state: ' + response.data);
                }
            }
        });
    }

    // Settings page form handler
    $('#kloudpanel-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
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
            }
        });
    });

    // Initialize dashboard updates
    if ($('#servers-grid').length) {
        updateServerStatus();
        setInterval(updateServerStatus, 30000);
    }
});
