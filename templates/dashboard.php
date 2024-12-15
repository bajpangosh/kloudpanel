<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Hetzner Cloud Dashboard</h1>
    
    <div class="kloudpanel-grid">
        <!-- Summary Cards -->
        <div class="kloudpanel-summary">
            <div class="card">
                <h3>Total Servers</h3>
                <div class="count" id="total-servers">-</div>
            </div>
            <div class="card">
                <h3>Running Servers</h3>
                <div class="count" id="running-servers">-</div>
            </div>
            <div class="card">
                <h3>Total Cost/Hour</h3>
                <div class="count" id="total-cost">€0.00</div>
            </div>
        </div>

        <!-- Servers Grid -->
        <div id="servers-grid" class="kloudpanel-servers-grid"></div>
    </div>
</div>

<template id="server-card-template">
    <div class="server-card card">
        <div class="server-header">
            <h3 class="server-name"></h3>
            <span class="server-status"></span>
        </div>
        <div class="server-info">
            <div class="info-row">
                <span class="label">IP Address:</span>
                <span class="ip"></span>
            </div>
            <div class="info-row">
                <span class="label">Type:</span>
                <span class="type"></span>
            </div>
            <div class="info-row">
                <span class="label">Location:</span>
                <span class="datacenter"></span>
            </div>
        </div>
        <div class="server-metrics">
            <div class="metric">
                <label>CPU</label>
                <div class="progress-bar">
                    <div class="progress cpu-usage"></div>
                </div>
                <span class="value cpu-value">-%</span>
            </div>
            <div class="metric">
                <label>Memory</label>
                <div class="progress-bar">
                    <div class="progress memory-usage"></div>
                </div>
                <span class="value memory-value">-%</span>
            </div>
            <div class="metric">
                <label>Disk</label>
                <div class="progress-bar">
                    <div class="progress disk-usage"></div>
                </div>
                <span class="value disk-value">-%</span>
            </div>
        </div>
        <div class="server-actions">
            <a href="#" class="button console-btn">Console</a>
            <a href="#" class="button power-btn">Power</a>
        </div>
    </div>
</template>

<style>
.kloudpanel-grid {
    margin-top: 20px;
}

.kloudpanel-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.kloudpanel-summary .card {
    padding: 20px;
    text-align: center;
}

.kloudpanel-summary .count {
    font-size: 2em;
    font-weight: bold;
    color: #2271b1;
}

.kloudpanel-servers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.server-card {
    padding: 20px;
}

.server-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.server-status {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8em;
}

.server-status.running {
    background: #d1fae5;
    color: #065f46;
}

.server-status.stopped {
    background: #fee2e2;
    color: #991b1b;
}

.server-info {
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.server-metrics {
    margin-bottom: 20px;
}

.metric {
    margin-bottom: 10px;
}

.metric label {
    display: block;
    margin-bottom: 5px;
}

.progress-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 5px;
}

.progress {
    height: 100%;
    background: #2271b1;
    transition: width 0.3s ease;
}

.value {
    font-size: 0.9em;
    color: #6b7280;
}

.server-actions {
    display: flex;
    gap: 10px;
}

.console-btn, .power-btn {
    flex: 1;
    text-align: center;
}
</style>

<script>
jQuery(document).ready(function($) {
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
            }
        });
        
        $('#running-servers').text(runningServers);
        $('#total-cost').text('€' + totalCost.toFixed(2));
    }

    function createServerCard(server) {
        const template = document.getElementById('server-card-template');
        const card = $(template.content.cloneNode(true));
        
        card.find('.server-name').text(server.name);
        card.find('.server-status')
            .text(server.status)
            .addClass(server.status);
        card.find('.ip').text(server.ip);
        card.find('.type').text(server.type);
        card.find('.datacenter').text(server.datacenter);
        
        if (server.metrics) {
            updateMetrics(card, server.metrics);
        }
        
        return card;
    }

    function updateMetrics(card, metrics) {
        // Update CPU usage
        const cpuUsage = calculateCPUUsage(metrics.cpu);
        card.find('.cpu-usage').css('width', cpuUsage + '%');
        card.find('.cpu-value').text(cpuUsage + '%');
        
        // Update Memory usage
        const memoryUsage = calculateMemoryUsage(metrics.memory);
        card.find('.memory-usage').css('width', memoryUsage + '%');
        card.find('.memory-value').text(memoryUsage + '%');
        
        // Update Disk usage
        const diskUsage = calculateDiskUsage(metrics.disk);
        card.find('.disk-usage').css('width', diskUsage + '%');
        card.find('.disk-value').text(diskUsage + '%');
    }

    function calculateCPUUsage(cpuMetrics) {
        // Implement CPU usage calculation based on metrics
        return Math.round(cpuMetrics * 100) || 0;
    }

    function calculateMemoryUsage(memoryMetrics) {
        // Implement memory usage calculation based on metrics
        return Math.round(memoryMetrics * 100) || 0;
    }

    function calculateDiskUsage(diskMetrics) {
        // Implement disk usage calculation based on metrics
        return Math.round(diskMetrics * 100) || 0;
    }

    // Update status every 30 seconds
    updateServerStatus();
    setInterval(updateServerStatus, 30000);
});
</script>
