/* KloudPanel Admin JavaScript */
jQuery(document).ready(function($) {
    let isLoading = false;
    let progressInterval;

    // Server status update
    function updateServerStatus(showProgress = true) {
        if (isLoading) return;
        isLoading = true;

        if (showProgress) {
            startLoadingProgress();
        }

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
                    updateLastUpdateTime();
                } else {
                    showError('Failed to fetch server data');
                }
            },
            error: function() {
                showError('Failed to connect to the server');
            },
            complete: function() {
                isLoading = false;
                if (showProgress) {
                    stopLoadingProgress();
                }
            }
        });
    }

    // Loading Progress
    function startLoadingProgress() {
        const progress = $('#loading-progress');
        const progressValue = progress.find('.progress-value');
        let width = 0;

        progress.addClass('active');
        progressValue.css('width', '0%');

        progressInterval = setInterval(function() {
            if (width >= 90) {
                clearInterval(progressInterval);
                return;
            }
            width += (90 - width) * 0.1;
            progressValue.css('width', width + '%');
        }, 100);
    }

    function stopLoadingProgress() {
        const progress = $('#loading-progress');
        const progressValue = progress.find('.progress-value');

        clearInterval(progressInterval);
        progressValue.css('width', '100%');

        setTimeout(function() {
            progress.removeClass('active');
            progressValue.css('width', '0%');
        }, 300);
    }

    // Show error message
    function showError(message) {
        const html = `
            <div class="notice notice-error is-dismissible">
                <p>${message}</p>
            </div>
        `;
        $('.wrap > h1').after(html);
    }

    // Update last update time
    function updateLastUpdateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        $('#last-update-time').text(timeString);
    }

    // Update dashboard with server data
    function updateDashboard(servers) {
        const grid = $('#servers-grid');
        const noServers = $('#no-servers');
        grid.empty();
        
        if (!servers || servers.length === 0) {
            grid.hide();
            noServers.show();
            updateSummaryCards(0, 0, 0);
            return;
        }

        noServers.hide();
        grid.show();
        
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
        
        updateSummaryCards(servers.length, runningServers, totalCost);
    }

    // Update summary cards
    function updateSummaryCards(total, running, cost) {
        $('#total-servers').text(total);
        $('#running-servers').text(running);
        $('#total-cost').text('€' + cost.toFixed(2));
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
        
        consoleBtn.attr('href', `https://console.hetzner.cloud/projects/${server.project_id}/servers/${server.id}/console`)
                 .attr('target', '_blank');
        
        powerBtn.html(`<span class="dashicons dashicons-${server.status === 'running' ? 'power-off' : 'power-on'}"></span>`)
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
                    showError('Failed to change server power state: ' + response.data);
                }
            }
        });
    }

    // Manual refresh button
    $('#refresh-dashboard').on('click', function() {
        updateServerStatus(true);
    });

    // Initialize dashboard updates
    if ($('#servers-grid').length) {
        updateServerStatus(true);
        setInterval(function() {
            updateServerStatus(false);
        }, 30000);
    }
});
