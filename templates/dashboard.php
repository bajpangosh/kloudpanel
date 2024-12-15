<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap kloudpanel-dashboard">
    <div class="dashboard-header">
        <h1>Hetzner Cloud Dashboard</h1>
        <div class="refresh-info">
            <span class="last-update">Last updated: <span id="last-update-time">Just now</span></span>
            <button id="refresh-dashboard" class="button button-secondary">
                <span class="dashicons dashicons-update"></span> Refresh
            </button>
        </div>
    </div>

    <!-- Loading Progress -->
    <div id="loading-progress" class="loading-progress">
        <div class="progress-bar">
            <div class="progress-value"></div>
        </div>
        <div class="progress-text">Loading server data...</div>
    </div>
    
    <div class="dashboard-grid">
        <!-- Summary Cards -->
        <div class="summary-section">
            <div class="summary-cards">
                <div class="summary-card total-servers">
                    <div class="card-icon">
                        <span class="dashicons dashicons-cloud"></span>
                    </div>
                    <div class="card-content">
                        <h3>Total Servers</h3>
                        <div class="count" id="total-servers">-</div>
                    </div>
                </div>
                <div class="summary-card running-servers">
                    <div class="card-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="card-content">
                        <h3>Running Servers</h3>
                        <div class="count" id="running-servers">-</div>
                    </div>
                </div>
                <div class="summary-card total-cost">
                    <div class="card-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="card-content">
                        <h3>Total Cost/Hour</h3>
                        <div class="count" id="total-cost">€0.00</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Servers Grid -->
        <div id="servers-grid" class="servers-grid"></div>

        <!-- No Servers Message -->
        <div id="no-servers" class="no-servers" style="display: none;">
            <div class="empty-state">
                <span class="dashicons dashicons-cloud"></span>
                <h2>No Servers Found</h2>
                <p>No Hetzner Cloud servers were found in your account.</p>
                <a href="https://console.hetzner.cloud/projects" target="_blank" class="button button-primary">
                    Create Server
                </a>
            </div>
        </div>
    </div>
</div>

<template id="server-card-template">
    <div class="server-card">
        <div class="server-header">
            <div class="server-name-status">
                <h3 class="server-name"></h3>
                <span class="server-status"></span>
            </div>
            <div class="server-actions">
                <button class="button console-btn">
                    <span class="dashicons dashicons-desktop"></span>
                </button>
                <button class="button power-btn">
                    <span class="dashicons dashicons-power-on"></span>
                </button>
            </div>
        </div>
        <div class="server-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">IP Address</span>
                    <span class="ip value"></span>
                </div>
                <div class="info-item">
                    <span class="label">Type</span>
                    <span class="type value"></span>
                </div>
                <div class="info-item">
                    <span class="label">Location</span>
                    <span class="location value"></span>
                </div>
            </div>
            <div class="server-metrics">
                <div class="metric-item">
                    <span class="metric-icon dashicons dashicons-dashboard"></span>
                    <div class="metric-info">
                        <span class="metric-label">CPU Usage</span>
                        <span class="metric-value cpu-usage">0%</span>
                        <div class="progress-bar">
                            <div class="progress cpu-progress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                <div class="metric-item">
                    <span class="metric-icon dashicons dashicons-memory"></span>
                    <div class="metric-info">
                        <span class="metric-label">Memory Usage</span>
                        <span class="metric-value memory-usage">0%</span>
                        <div class="progress-bar">
                            <div class="progress memory-progress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                <div class="metric-item">
                    <span class="metric-icon dashicons dashicons-database"></span>
                    <div class="metric-info">
                        <span class="metric-label">Disk Usage</span>
                        <span class="metric-value disk-usage">0%</span>
                        <div class="progress-bar">
                            <div class="progress disk-progress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

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

.refresh-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.last-update {
    color: #666;
    font-size: 13px;
}

/* Loading Progress */
.loading-progress {
    display: none;
    margin: 20px 0;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.loading-progress.active {
    display: block;
}

.loading-progress .progress-bar {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 10px;
}

.loading-progress .progress-value {
    height: 100%;
    width: 0;
    background: #2271b1;
    transition: width 0.3s ease;
}

.loading-progress .progress-text {
    font-size: 13px;
    color: #666;
    text-align: center;
}

/* Summary Cards */
.summary-section {
    margin-bottom: 30px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.summary-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-icon {
    background: #f0f9ff;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #2271b1;
}

.card-content h3 {
    margin: 0;
    font-size: 14px;
    color: #666;
}

.card-content .count {
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
    margin-top: 5px;
}

/* Servers Grid */
.servers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
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
    align-items: flex-start;
    margin-bottom: 20px;
}

.server-name-status {
    flex: 1;
}

.server-name {
    margin: 0 0 8px 0;
    font-size: 16px;
    color: #1d2327;
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

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-item .label {
    font-size: 12px;
    color: #666;
}

.info-item .value {
    font-size: 14px;
    color: #1d2327;
}

.server-metrics {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.metric-item {
    display: flex;
    align-items: center;
    gap: 15px;
}

.metric-icon {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: #666;
}

.metric-info {
    flex: 1;
}

.metric-label {
    font-size: 12px;
    color: #666;
}

.metric-value {
    font-size: 12px;
    color: #1d2327;
}

.progress-bar {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.progress {
    height: 100%;
    transition: width 0.3s ease;
}

.progress.low {
    background: #2271b1;
}

.progress.medium {
    background: #f59e0b;
}

.progress.high {
    background: #dc2626;
}

.progress {
    height: 4px;
    background-color: #4CAF50;
    transition: width 0.3s ease-in-out;
}

.progress.medium {
    background-color: #FFA726;
}

.progress.high {
    background-color: #EF5350;
}

.progress-bar {
    width: 100%;
    height: 4px;
    background-color: #f0f0f0;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 4px;
}

/* Empty State */
.no-servers {
    text-align: center;
    padding: 60px 20px;
}

.empty-state {
    max-width: 400px;
    margin: 0 auto;
}

.empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #666;
    margin-bottom: 20px;
}

.empty-state h2 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.empty-state p {
    color: #666;
    margin-bottom: 20px;
}

/* Responsive Design */
@media (max-width: 782px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .servers-grid {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
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
        card.find('.location').text(server.datacenter);
        
        if (server.metrics) {
            updateMetrics(card, server.metrics);
        }
        
        return card;
    }

    function updateMetrics(card, metrics) {
        // Update CPU usage
        const cpuUsage = calculateCPUUsage(metrics.cpu);
        card.find('.cpu-progress').css('width', cpuUsage + '%');
        card.find('.cpu-usage').text(cpuUsage + '%');
        
        // Update Memory usage
        const memoryUsage = calculateMemoryUsage(metrics.memory);
        card.find('.memory-progress').css('width', memoryUsage + '%');
        card.find('.memory-usage').text(memoryUsage + '%');
        
        // Update Disk usage
        const diskUsage = calculateDiskUsage(metrics.disk);
        card.find('.disk-progress').css('width', diskUsage + '%');
        card.find('.disk-usage').text(diskUsage + '%');
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
