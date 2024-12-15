/* KloudPanel Admin JavaScript */
jQuery(document).ready(function($) {
    let loadingProgress = 0;
    const progressBar = $('#loading-progress');
    const progressValue = progressBar.find('.progress-value');

    function updateProgress(value) {
        loadingProgress = value;
        progressValue.css('width', value + '%');
        if (value >= 100) {
            setTimeout(() => progressBar.fadeOut(), 500);
        } else {
            progressBar.fadeIn();
        }
    }

    // Load API Key Groups and their servers
    function loadApiGroups() {
        updateProgress(0);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_api_groups',
                nonce: kloudpanel.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateProgress(30);
                    const groups = response.data;
                    $('.api-groups-container').empty();
                    
                    // Load servers for each group
                    groups.forEach((group, index) => {
                        const groupCard = createApiKeyGroupCard(group);
                        $('.api-groups-container').append(groupCard);
                        
                        loadServers(group.api_key, group.id);
                        updateProgress(30 + ((index + 1) / groups.length * 70));
                    });
                } else {
                    showError('Failed to load API groups: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                showError('Failed to load API groups: ' + error);
                updateProgress(100);
            }
        });
    }

    function loadServers(apiKey, groupId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_servers',
                api_key: apiKey,
                group_id: groupId,
                nonce: kloudpanel.nonce
            },
            success: function(response) {
                if (response.success) {
                    const servers = response.data;
                    const groupCard = $(`.api-group-card[data-group-id="${groupId}"]`);
                    const serversGrid = groupCard.find('.servers-grid');
                    serversGrid.empty();
                    
                    servers.forEach(server => {
                        const serverCard = createServerCard(server);
                        serversGrid.append(serverCard);
                    });
                } else {
                    showError('Failed to load servers: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                showError('Failed to load servers: ' + error);
            }
        });
    }

    function createApiKeyGroupCard(group) {
        const template = document.getElementById('api-group-template');
        const card = $(template.content.cloneNode(true));
        
        card.find('.api-group-card').attr('data-group-id', group.id);
        card.find('.group-name').text(group.name);
        card.find('.api-key-hint').text('API Key: ' + maskApiKey(group.api_key));
        
        // Add event listeners for group actions
        card.find('.edit-api').on('click', () => editApiGroup(group));
        card.find('.delete-api').on('click', () => deleteApiGroup(group.id));
        
        return card;
    }

    function createServerCard(server) {
        const template = document.getElementById('server-template');
        const card = $(template.content.cloneNode(true));
        
        card.find('.server-card').attr('data-server-id', server.id);
        card.find('.server-name').text(server.name);
        card.find('.server-status').addClass(server.status.toLowerCase());
        card.find('.ip-address').text(server.ip || 'No IP');
        card.find('.created-date').text(formatDate(server.created));
        card.find('.server-age').text(calculateAge(server.created));
        
        // Add event listeners for server actions
        setupServerActions(card, server);
        
        return card;
    }

    function setupServerActions(card, server) {
        const powerBtn = card.find('.power-action');
        powerBtn.addClass(server.status === 'running' ? 'power-off' : 'power-on');
        powerBtn.find('.dashicons').addClass(server.status === 'running' ? 'dashicons-power-off' : 'dashicons-power-on');
        
        powerBtn.on('click', () => toggleServerPower(server));
        card.find('.edit-server').on('click', () => editServer(server));
        card.find('.delete-server').on('click', () => deleteServer(server.id));
    }

    // API Key Group Actions
    $('#api-key-form').on('submit', function(e) {
        e.preventDefault();
        const groupName = $('#group-name').val();
        const apiKey = $('#api-key').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'add_api_group',
                name: groupName,
                api_key: apiKey,
                nonce: kloudpanel.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#api-key-modal').fadeOut();
                    loadApiGroups();
                } else {
                    showError('Failed to add API group: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                showError('Failed to add API group: ' + error);
            }
        });
    });

    // Utility functions
    function maskApiKey(apiKey) {
        return apiKey.substring(0, 8) + '...' + apiKey.substring(apiKey.length - 4);
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    function calculateAge(dateString) {
        const created = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - created);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 30) {
            return diffDays + ' days';
        } else if (diffDays < 365) {
            const months = Math.floor(diffDays / 30);
            return months + ' month' + (months === 1 ? '' : 's');
        } else {
            const years = Math.floor(diffDays / 365);
            return years + ' year' + (years === 1 ? '' : 's');
        }
    }

    function showError(message) {
        // Implement error display logic
        console.error(message);
    }

    // Initialize dashboard
    loadApiGroups();
    
    // Refresh dashboard periodically
    setInterval(loadApiGroups, 300000); // Refresh every 5 minutes
});
