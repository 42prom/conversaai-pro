/**
 * Messaging channels admin JavaScript.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/assets/js
 */

(function($) {
    'use strict';
    
    // Document ready
    $(document).ready(function() {
        
        // Channel tabs functionality
        $('.conversaai-tab').on('click', function() {
            var $this = $(this);
            var tabId = $this.data('tab');
            
            // Activate the tab
            $('.conversaai-tab').removeClass('active');
            $this.addClass('active');
            
            // Show the tab content
            $('.conversaai-tab-content').removeClass('active').hide();
            $('#tab-' + tabId).addClass('active').fadeIn(300);
            
            // Store the active tab in localStorage
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('conversaai_active_channel_tab', tabId);
            }
        });
        
        // Restore the active tab from localStorage
        if (typeof(Storage) !== "undefined") {
            var activeTab = localStorage.getItem('conversaai_active_channel_tab');
            if (activeTab) {
                $('.conversaai-tab[data-tab="' + activeTab + '"]').trigger('click');
            }
        }
        
        // Copy webhook URL to clipboard
        $('.copy-webhook-url').on('click', function() {
            var $this = $(this);
            var url = $this.data('url');
            var originalHtml = $this.html();
            
            // Create a temporary input element
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(url).select();
            
            // Copy the URL
            var successful = document.execCommand('copy');
            $temp.remove();
            
            // Visual feedback
            if (successful) {
                $this.html('<span class="dashicons dashicons-yes"></span>');
                setTimeout(function() {
                    $this.html(originalHtml);
                }, 2000);
                
                // Show success message
                showNotice('success', conversaai_channels.copy_success);
            } else {
                showNotice('error', conversaai_channels.copy_error);
            }
        });
        
        // Channel toggle functionality
        $('input[type="checkbox"][id$="-enabled"]').on('change', function() {
            var $this = $(this);
            var channel = $this.attr('id').replace('-enabled', '');
            var isEnabled = $this.is(':checked');
            
            // Visual feedback
            var $channelIcon = $('.conversaai-channel-icon.' + channel);
            var $status = $channelIcon.find('.conversaai-channel-status');
            
            if (isEnabled) {
                $status.removeClass('channel-inactive').addClass('channel-active').text(conversaai_channels.active_text);
            } else {
                $status.removeClass('channel-active').addClass('channel-inactive').text(conversaai_channels.inactive_text);
            }
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_toggle_channel',
                    nonce: conversaai_channels.nonce,
                    channel_type: channel,
                    status: isEnabled ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                    } else {
                        showNotice('error', response.data.message || conversaai_channels.toggle_error);
                        // Revert the toggle if there was an error
                        $this.prop('checked', !isEnabled);
                        
                        if (isEnabled) {
                            $status.removeClass('channel-active').addClass('channel-inactive').text(conversaai_channels.inactive_text);
                        } else {
                            $status.removeClass('channel-inactive').addClass('channel-active').text(conversaai_channels.active_text);
                        }
                    }
                },
                error: function() {
                    showNotice('error', conversaai_channels.ajax_error);
                    // Revert the toggle
                    $this.prop('checked', !isEnabled);
                    
                    if (isEnabled) {
                        $status.removeClass('channel-active').addClass('channel-inactive').text(conversaai_channels.inactive_text);
                    } else {
                        $status.removeClass('channel-inactive').addClass('channel-active').text(conversaai_channels.active_text);
                    }
                }
            });
        });
        
        // Test connection button
        $('.test-connection').on('click', function() {
            var $this = $(this);
            var channel = $this.data('channel');
            var $form = $('#' + channel + '-settings-form');
            var $spinner = $this.closest('.channel-actions').find('.spinner');
            
            // Disable the button and show spinner
            $this.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Collect form data
            var formData = {};
            $form.find('input, select, textarea').each(function() {
                var $input = $(this);
                var name = $input.attr('name');
                
                if (name) {
                    if ($input.attr('type') === 'checkbox') {
                        formData[name] = $input.is(':checked') ? 1 : 0;
                    } else {
                        formData[name] = $input.val();
                    }
                }
            });
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_test_channel_connection',
                    nonce: conversaai_channels.nonce,
                    channel_type: channel,
                    settings_data: formData
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                        
                        // Display additional details if available
                        if (response.data.details) {
                            var details = '';
                            
                            if (typeof response.data.details === 'object') {
                                // Format details as a list
                                details += '<ul>';
                                
                                for (var key in response.data.details) {
                                    if (response.data.details.hasOwnProperty(key)) {
                                        details += '<li><strong>' + key + ':</strong> ' + response.data.details[key] + '</li>';
                                    }
                                }
                                
                                details += '</ul>';
                            } else {
                                details = response.data.details;
                            }
                            
                            showNotice('success', details, false, 10000);
                        }
                    } else {
                        showNotice('error', response.data.message || conversaai_channels.test_error);
                    }
                },
                error: function() {
                    showNotice('error', conversaai_channels.ajax_error);
                },
                complete: function() {
                    // Re-enable the button and hide spinner
                    $this.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Channel settings form submission
        $('.channel-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var channel = $form.attr('id').replace('-settings-form', '');
            var $saveButton = $form.find('.save-channel');
            var $spinner = $saveButton.siblings('.spinner');
            
            // Disable the button and show spinner
            $saveButton.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Collect form data
            var formData = {};
            $form.find('input, select, textarea').each(function() {
                var $input = $(this);
                var name = $input.attr('name');
                
                if (name) {
                    if ($input.attr('type') === 'checkbox') {
                        formData[name] = $input.is(':checked') ? 1 : 0;
                    } else {
                        formData[name] = $input.val();
                    }
                }
            });
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_save_channel_settings',
                    nonce: conversaai_channels.nonce,
                    channel_type: channel,
                    settings_data: formData
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                    } else {
                        showNotice('error', response.data.message || conversaai_channels.save_error);
                    }
                },
                error: function() {
                    showNotice('error', conversaai_channels.ajax_error);
                },
                complete: function() {
                    // Re-enable the button and hide spinner
                    $saveButton.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Refresh channel stats
        $('#refresh-channel-stats').on('click', function() {
            var $this = $(this);
            var $spinner = $this.find('.spinner');
            
            // Disable the button and show spinner
            $this.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'conversaai_get_channel_stats',
                    nonce: conversaai_channels.nonce
                },
                success: function(response) {
                    if (response.success && response.data.stats) {
                        // Update stats
                        var stats = response.data.stats;
                        
                        for (var channel in stats) {
                            var channelStats = stats[channel];
                            
                            // Update total conversations
                            $('.conversaai-stat-item.' + channel + ' .total-count').text(channelStats.total || 0);
                            
                            // Update last 24 hours
                            $('.conversaai-stat-item.' + channel + ' .last-24h-count').text(channelStats.last_24h || 0);
                            
                            // Update last 7 days
                            $('.conversaai-stat-item.' + channel + ' .last-week-count').text(channelStats.last_week || 0);
                        }
                        
                        showNotice('success', conversaai_channels.stats_updated);
                    } else {
                        showNotice('error', response.data.message || conversaai_channels.stats_error);
                    }
                },
                error: function() {
                    showNotice('error', conversaai_channels.ajax_error);
                },
                complete: function() {
                    // Re-enable the button and hide spinner
                    $this.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Helper function for showing notices
        function showNotice(type, message, autoDismiss = true, duration = 5000) {
            var $notice = type === 'success' ? $('#message') : $('#error-message');
            
            // Set the message
            $notice.find('p').html(message);
            
            // Show the notice
            $notice.fadeIn(300);
            
            // Auto-dismiss after delay
            if (autoDismiss) {
                setTimeout(function() {
                    $notice.fadeOut(300);
                }, duration);
            }
        }
    });
    
})(jQuery);