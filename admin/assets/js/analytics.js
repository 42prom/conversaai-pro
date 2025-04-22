/**
 * Analytics Dashboard JavaScript
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/assets/js
 */

(function($) {
    'use strict';
    
    // Chart instances
    let conversationsChart;
    let sourcesChart;
    let successDistributionChart;
    let channelsChart;
    
    // Current filter settings
    let currentFilters = {
        startDate: conversaaiAnalytics.dateRange.start,
        endDate: conversaaiAnalytics.dateRange.end,
        channel: ''
    };
    
    // Color schemes for charts
    const colorSchemes = {
        conversations: {
            backgroundColor: ['rgba(76, 102, 239, 0.2)', 'rgba(67, 160, 71, 0.2)'],
            borderColor: ['rgba(76, 102, 239, 1)', 'rgba(67, 160, 71, 1)'],
            borderWidth: 2
        },
        sources: {
            backgroundColor: ['rgba(25, 118, 210, 0.7)', 'rgba(76, 102, 239, 0.7)'],
            borderColor: ['rgba(25, 118, 210, 1)', 'rgba(76, 102, 239, 1)'],
            borderWidth: 1
        },
        success: {
            backgroundColor: [
                'rgba(76, 175, 80, 0.7)',  // Excellent
                'rgba(139, 195, 74, 0.7)',  // Good
                'rgba(255, 193, 7, 0.7)',   // Average
                'rgba(255, 152, 0, 0.7)',   // Poor
                'rgba(244, 67, 54, 0.7)'    // Very Poor
            ],
            borderColor: [
                'rgba(76, 175, 80, 1)',
                'rgba(139, 195, 74, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(255, 152, 0, 1)',
                'rgba(244, 67, 54, 1)'
            ],
            borderWidth: 1
        },
        channels: {
            backgroundColor: [
                'rgba(76, 102, 239, 0.7)',
                'rgba(67, 160, 71, 0.7)',
                'rgba(255, 152, 0, 0.7)',
                'rgba(233, 30, 99, 0.7)'
            ],
            borderColor: [
                'rgba(76, 102, 239, 1)',
                'rgba(67, 160, 71, 1)',
                'rgba(255, 152, 0, 1)',
                'rgba(233, 30, 99, 1)'
            ],
            borderWidth: 1
        }
    };
    
    // Initialize the analytics dashboard
    function initAnalyticsDashboard() {
        // Initialize date pickers
        initDateControls();
        
        // Initialize export functionality
        initExportControls();
        
        // Apply filters button click handler
        $('#conversaai-apply-filters').on('click', function() {
            applyFilters();
        });
        
        // Widget refresh button click handlers
        $('.conversaai-widget-refresh').on('click', function() {
            const widget = $(this).data('widget');
            loadWidgetData(widget);
        });
        
        // Load initial data
        loadAnalyticsData();
    }
    
    // Initialize date range controls
    function initDateControls() {
        // Date preset buttons
        $('.conversaai-date-preset').on('click', function() {
            const startDate = $(this).data('start');
            const endDate = $(this).data('end');
            
            $('#conversaai-date-start').val(startDate);
            $('#conversaai-date-end').val(endDate);
            
            // Highlight active preset
            $('.conversaai-date-preset').removeClass('active');
            $(this).addClass('active');
        });
        
        // Custom date range button
        $('.conversaai-date-preset-custom').on('click', function() {
            // Just clear highlighting for presets
            $('.conversaai-date-preset').removeClass('active');
            $(this).addClass('active');
        });
    }
    
    // Initialize export functionality
    function initExportControls() {
        $('.conversaai-export-option').on('click', function(e) {
            e.preventDefault();
            
            const format = $(this).data('format');
            exportAnalytics(format);
        });
    }
    
    // Apply filters and reload data
    function applyFilters() {
        // Get current filter values
        const startDate = $('#conversaai-date-start').val();
        const endDate = $('#conversaai-date-end').val();
        const channel = $('#conversaai-channel').val();
        
        // Update current filters
        currentFilters.startDate = startDate;
        currentFilters.endDate = endDate;
        currentFilters.channel = channel;
        
        // Reload analytics data
        loadAnalyticsData();
    }
    
    // Load analytics data from the server
    function loadAnalyticsData() {
        // Show loading overlay
        $('#conversaai-loading-overlay').show();
        
        // Clear any existing notices
        $('#conversaai-analytics-notices').empty();
        
        // Make AJAX request
        $.ajax({
            url: conversaaiAnalytics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'conversaai_get_analytics',
                nonce: conversaaiAnalytics.nonce,
                start_date: currentFilters.startDate,
                end_date: currentFilters.endDate,
                channel: currentFilters.channel
            },
            success: function(response) {
                // Hide loading overlay
                $('#conversaai-loading-overlay').hide();
                
                if (response.success) {
                    // Update summary metrics
                    updateSummaryMetrics(response.data.analytics);
                    
                    // Load individual widgets
                    loadAllWidgets();
                } else {
                    // Show error notice
                    showNotice('error', response.data.message || conversaaiAnalytics.i18n.error);
                }
            },
            error: function() {
                // Hide loading overlay
                $('#conversaai-loading-overlay').hide();
                
                // Show error notice
                showNotice('error', conversaaiAnalytics.i18n.error);
            }
        });
    }
    
    // Load data for all widgets
    function loadAllWidgets() {
        loadWidgetData('conversations_chart');
        loadWidgetData('sources_chart');
        loadWidgetData('success_distribution');
        loadWidgetData('channels_chart');
        loadWidgetData('trending_queries');
    }
    
    // Load data for a specific widget
    function loadWidgetData(widget) {
        // Show widget loading indicator
        $(`#${widget}-widget .conversaai-widget-loading`).show();
        
        // Make AJAX request
        $.ajax({
            url: conversaaiAnalytics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'conversaai_get_dashboard_widgets',
                nonce: conversaaiAnalytics.nonce,
                widget: widget,
                start_date: currentFilters.startDate,
                end_date: currentFilters.endDate,
                channel: currentFilters.channel
            },
            success: function(response) {
                // Hide widget loading indicator
                $(`#${widget}-widget .conversaai-widget-loading`).hide();
                
                if (response.success) {
                    // Update widget with data
                    updateWidget(widget, response.data.data);
                } else {
                    // Show error in widget
                    showWidgetError(widget);
                }
            },
            error: function() {
                // Hide widget loading indicator
                $(`#${widget}-widget .conversaai-widget-loading`).hide();
                
                // Show error in widget
                showWidgetError(widget);
            }
        });
    }
    
    // Update summary metrics at the top of the dashboard
    function updateSummaryMetrics(data) {
        $('#conversation-count').text(numberFormat(data.totals.conversation_count || 0));
        $('#message-count').text(numberFormat(data.totals.message_count || 0));
        $('#success-rate').text(numberFormat((data.success_rate || 0) * 100, 1) + '%');
        $('#kb-usage').text(numberFormat((data.kb_usage_rate || 0) * 100, 1) + '%');
    }
    
    // Update a specific widget with data
    function updateWidget(widget, data) {
        switch (widget) {
            case 'conversations_chart':
                updateConversationsChart(data);
                break;
                
            case 'sources_chart':
                updateSourcesChart(data);
                break;
                
            case 'success_distribution':
                updateSuccessDistributionChart(data);
                break;
                
            case 'channels_chart':
                updateChannelsChart(data);
                break;
                
            case 'trending_queries':
                updateTrendingQueries(data);
                break;
        }
    }
    
    // Update conversations over time chart
    function updateConversationsChart(data) {
        const ctx = document.getElementById('conversaai-conversations-chart').getContext('2d');
        
        // Check if chart already exists
        if (conversationsChart) {
            conversationsChart.destroy();
        }
        
        // Check if we have data
        if (!data.labels || data.labels.length === 0) {
            showWidgetError('conversations_chart');
            return;
        }
        
        // Create new chart
        conversationsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: data.datasets[0].label || conversaaiAnalytics.i18n.conversations,
                        data: data.datasets[0].data,
                        backgroundColor: colorSchemes.conversations.backgroundColor[0],
                        borderColor: colorSchemes.conversations.borderColor[0],
                        borderWidth: colorSchemes.conversations.borderWidth,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: data.datasets[1].label || conversaaiAnalytics.i18n.messages,
                        data: data.datasets[1].data,
                        backgroundColor: colorSchemes.conversations.backgroundColor[1],
                        borderColor: colorSchemes.conversations.borderColor[1],
                        borderWidth: colorSchemes.conversations.borderWidth,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
    
    // Update response sources chart
    function updateSourcesChart(data) {
        const ctx = document.getElementById('conversaai-sources-chart').getContext('2d');
        
        // Check if chart already exists
        if (sourcesChart) {
            sourcesChart.destroy();
        }
        
        // Check if we have data
        if (!data.labels || data.labels.length === 0) {
            showWidgetError('sources_chart');
            return;
        }
        
        // Create new chart
        sourcesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.datasets[0].data,
                    backgroundColor: colorSchemes.sources.backgroundColor,
                    borderColor: colorSchemes.sources.borderColor,
                    borderWidth: colorSchemes.sources.borderWidth
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Update success distribution chart
    function updateSuccessDistributionChart(data) {
        const ctx = document.getElementById('conversaai-success-distribution-chart').getContext('2d');
        
        // Check if chart already exists
        if (successDistributionChart) {
            successDistributionChart.destroy();
        }
        
        // Check if we have data
        if (!data.labels || data.labels.length === 0) {
            showWidgetError('success_chart');
            return;
        }
        
        // Create new chart
        successDistributionChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.datasets[0].data,
                    backgroundColor: colorSchemes.success.backgroundColor,
                    borderColor: colorSchemes.success.borderColor,
                    borderWidth: colorSchemes.success.borderWidth
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Update channels chart
    function updateChannelsChart(data) {
        const ctx = document.getElementById('conversaai-channels-chart').getContext('2d');
        
        // Check if chart already exists
        if (channelsChart) {
            channelsChart.destroy();
        }
        
        // Check if we have data
        if (!data.labels || data.labels.length === 0) {
            showWidgetError('channels_chart');
            return;
        }
        
        // Create new chart
        channelsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: data.datasets[0].label || conversaaiAnalytics.i18n.conversations,
                    data: data.datasets[0].data,
                    backgroundColor: colorSchemes.channels.backgroundColor,
                    borderColor: colorSchemes.channels.borderColor,
                    borderWidth: colorSchemes.channels.borderWidth
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Update trending queries table
    function updateTrendingQueries(data) {
        // Check if we have data
        if (!data || data.length === 0) {
            $('#conversaai-trending-queries').html(
                `<tr><td colspan="2" class="conversaai-no-data">${conversaaiAnalytics.i18n.noData}</td></tr>`
            );
            return;
        }
        
        // Build table rows
        let html = '';
        data.forEach(function(item) {
            html += `
                <tr>
                    <td>${escapeHtml(item.query)}</td>
                    <td>${numberFormat(item.count)}</td>
                </tr>
            `;
        });
        
        // Update table
        $('#conversaai-trending-queries').html(html);
        
        // Initialize DataTable if not already
        if (!$.fn.DataTable.isDataTable('.conversaai-trending-table')) {
            $('.conversaai-trending-table').DataTable({
                paging: false,
                searching: false,
                info: false,
                order: [[1, 'desc']]
            });
        }
    }
    
    // Export analytics data
    function exportAnalytics(format) {
        // Show loading overlay
        $('#conversaai-loading-overlay').show();
        
        // Make AJAX request
        $.ajax({
            url: conversaaiAnalytics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'conversaai_export_analytics',
                nonce: conversaaiAnalytics.nonce,
                format: format,
                start_date: currentFilters.startDate,
                end_date: currentFilters.endDate,
                channel: currentFilters.channel
            },
            success: function(response) {
                // Hide loading overlay
                $('#conversaai-loading-overlay').hide();
                
                if (response.success) {
                    // Create a download link
                    const blob = new Blob([response.data.data], {
                        type: format === 'csv' ? 'text/csv' : 'application/json'
                    });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    
                    // Show success notice
                    showNotice('success', 'Export completed successfully.');
                } else {
                    // Show error notice
                    showNotice('error', response.data.message || 'Error exporting data.');
                }
            },
            error: function() {
                // Hide loading overlay
                $('#conversaai-loading-overlay').hide();
                
                // Show error notice
                showNotice('error', 'Connection error. Please try again.');
            }
        });
    }
    
    // Show a notice
    function showNotice(type, message) {
        const icon = type === 'success' ? 'yes-alt' : type === 'error' ? 'dismiss' : 'info';
        
        const html = `
            <div class="conversaai-notice conversaai-notice-${type}">
                <span class="conversaai-notice-icon dashicons dashicons-${icon}"></span>
                <span class="conversaai-notice-message">${message}</span>
            </div>
        `;
        
        $('#conversaai-analytics-notices').html(html);
    }
    
    // Show an error in a widget
    function showWidgetError(widget) {
        const widgetId = widget.replace('_', '-');
        $(`#${widgetId}-widget .conversaai-chart-container, #${widgetId}-widget .conversaai-trending-table-container`).html(
            `<div class="conversaai-no-data">${conversaaiAnalytics.i18n.noData}</div>`
        );
    }
    
    // Format numbers
    function numberFormat(number, decimals = 0) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    }
    
    // Escape HTML
    function escapeHtml(string) {
        const div = document.createElement('div');
        div.textContent = string;
        return div.innerHTML;
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initAnalyticsDashboard();
    });
    
})(jQuery);