/* KloudPanel Admin JavaScript */
jQuery(document).ready(function($) {
    let isLoading = false;
    let progressInterval;

    // Initialize projects
    function initDashboard() {
        updateServerStatus(true);
    }

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
                action: 'get_servers_data',
                nonce: kloudpanel.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                    hideError();
                } else {
                    const errorMsg = response.data ? response.data.message : 'Failed to fetch server data';
                    showError(errorMsg);
                    if (kloudpanel.debug) {
                        console.error('Server Error:', response);
                    }
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = 'Failed to connect to the server';
                showError(errorMsg);
                if (kloudpanel.debug) {
                    console.error('AJAX Error:', {xhr, status, error});
                }
            },
            complete: function() {
                isLoading = false;
                if (showProgress) {
                    stopLoadingProgress();
                }
            }
        });
    }

    function updateDashboard(data) {
        const servers = data.servers || [];
        const defaultProject = 'Default Project';
        
        // Group servers by project (for now, all in default project)
        const projectServers = {
            [defaultProject]: servers
        };

        // Clear existing projects
        $('.projects-container').empty();

        // Create project cards
        Object.entries(projectServers).forEach(([projectName, projectServers]) => {
            const projectCard = createProjectCard(projectName, projectServers);
            $('.projects-container').append(projectCard);
        });
    }

    function createProjectCard(projectName, servers) {
        const template = document.getElementById('project-template');
        const card = $(template.content.cloneNode(true));
        
        card.find('.project-name').text(projectName);
        
        // Add servers to project
        const serversGrid = card.find('.servers-grid');
        servers.forEach(server => {
            const serverCard = createServerCard(server);
            serversGrid.append(serverCard);
        });

        return card;
    }

    function createServerCard(server) {
        const template = document.getElementById('server-template');
        const card = $(template.content.cloneNode(true));

        // Calculate server age
        const createdDate = new Date(server.created);
        const now = new Date();
        const ageInDays = Math.floor((now - createdDate) / (1000 * 60 * 60 * 24));
        const age = formatAge(ageInDays);

        // Update card content
        card.find('.server-name').text(server.name);
        card.find('.server-status')
            .addClass(server.status)
            .attr('title', server.status);
        card.find('.ip-address').text(server.public_net.ipv4.ip);
        card.find('.created-date').text(createdDate.toLocaleDateString());
        card.find('.server-age').text(age);

        // Set power button icon based on status
        const powerButton = card.find('.power-action .dashicons');
        powerButton.addClass(server.status === 'running' ? 'dashicons-power-off' : 'dashicons-power-on');

        return card;
    }

    function formatAge(days) {
        if (days < 1) return 'Today';
        if (days < 30) return days + ' days';
        
        const months = Math.floor(days / 30);
        if (months < 12) return months + ' months';
        
        const years = Math.floor(months / 12);
        const remainingMonths = months % 12;
        return years + ' years' + (remainingMonths ? ', ' + remainingMonths + ' months' : '');
    }

    // Loading Progress
    function startLoadingProgress() {
        const progress = $('#loading-progress');
        progress.show();
        animateProgress();
    }

    function stopLoadingProgress() {
        const progress = $('#loading-progress');
        progress.hide();
        $('.progress-value').css('width', '0%');
    }

    function animateProgress() {
        const progressBar = $('.progress-value');
        progressBar.css('width', '0%');
        progressBar.animate({ width: '100%' }, 1000);
    }

    // Error Handling
    function showError(message = 'An unexpected error occurred') {
        if (!message || message === '') {
            message = 'An unexpected error occurred';
        }
        
        const errorHtml = `
            <div class="notice notice-error">
                <p>${message}</p>
            </div>
        `;
        
        // Remove any existing error messages
        $('.kloudpanel-dashboard .notice').remove();
        
        // Add the new error message at the top of the dashboard
        $('.kloudpanel-dashboard').prepend(errorHtml);
    }

    function hideError() {
        $('.kloudpanel-dashboard .notice').fadeOut(300, function() {
            $(this).remove();
        });
    }

    // Project Modal
    $('#add-project').on('click', function() {
        $('#project-modal').fadeIn();
    });

    $('.modal-close').on('click', function() {
        $(this).closest('.modal').fadeOut();
    });

    $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            $('.modal').fadeOut();
        }
    });

    // Initialize dashboard if we're on the dashboard page
    if ($('.kloudpanel-dashboard').length) {
        initDashboard();
    }
});
