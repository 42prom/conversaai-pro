<?php
/**
 * Messaging Channels page template.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap conversaai-pro-channels">
    <h1 class="conversaai-page-header"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="conversaai-admin-banner">
        <div class="conversaai-admin-banner-content">
            <h2><?php _e('Messaging Channels', 'conversaai-pro-wp'); ?></h2>
            <p><?php _e('Configure external messaging channels to enable ConversaAI on platforms like WhatsApp, Facebook Messenger, and Instagram.', 'conversaai-pro-wp'); ?></p>
        </div>
        <div class="conversaai-admin-banner-icon">
            <span class="dashicons dashicons-share"></span>
        </div>
    </div>
        
    <div id="message" class="updated notice is-dismissible" style="display:none;">
        <p></p>
    </div>
    
    <div id="error-message" class="error notice is-dismissible" style="display:none;">
        <p></p>
    </div>
    
    <!-- Channel Statistics Overview -->
    <div class="conversaai-overview-cards">
        <div class="conversaai-card">
            <h2><span class="dashicons dashicons-chart-bar"></span> <?php _e('Channels Overview', 'conversaai-pro-wp'); ?></h2>
            <div class="conversaai-stats-grid">
                <?php foreach (array('webchat', 'whatsapp', 'messenger', 'instagram') as $channel): 
                    $stats = $channel_stats[$channel] ?? array('total' => 0, 'last_24h' => 0, 'last_week' => 0);
                    $enabled = ($channel === 'webchat') ? 
                        (bool) (get_option('conversaai_pro_general_settings', array())['enable_chat_widget'] ?? false) : 
                        (bool) ($channels_settings[$channel]['enabled'] ?? false);
                    $status_class = $enabled ? 'channel-active' : 'channel-inactive';
                    $status_text = $enabled ? __('Active', 'conversaai-pro-wp') : __('Inactive', 'conversaai-pro-wp');
                    $channel_label = ucfirst($channel);
                    if ($channel === 'webchat') $channel_label = __('Website Chat', 'conversaai-pro-wp');
                ?>
                <div class="conversaai-stat-item">
                    <div class="conversaai-channel-icon <?php echo esc_attr($channel); ?>">
                        <span class="conversaai-channel-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                    </div>
                    <h3><?php echo esc_html($channel_label); ?></h3>
                    <div class="conversaai-channel-stats">
                        <div class="stat-row">
                            <span class="stat-label"><?php _e('Total Conversations:', 'conversaai-pro-wp'); ?></span>
                            <span class="stat-value"><?php echo number_format($stats['total']); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label"><?php _e('Last 24 hours:', 'conversaai-pro-wp'); ?></span>
                            <span class="stat-value"><?php echo number_format($stats['last_24h']); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label"><?php _e('Last 7 days:', 'conversaai-pro-wp'); ?></span>
                            <span class="stat-value"><?php echo number_format($stats['last_week']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Channel Configuration Tabs -->
    <div class="conversaai-tab-container">
        <div class="conversaai-tabs">
            <div class="conversaai-tab active" data-tab="whatsapp"><?php _e('WhatsApp', 'conversaai-pro-wp'); ?></div>
            <div class="conversaai-tab" data-tab="messenger"><?php _e('Facebook Messenger', 'conversaai-pro-wp'); ?></div>
            <div class="conversaai-tab" data-tab="instagram"><?php _e('Instagram', 'conversaai-pro-wp'); ?></div>
        </div>
        
        <!-- WhatsApp Tab -->
        <div class="conversaai-tab-content active" id="tab-whatsapp">
            <div class="conversaai-card">
                <form id="whatsapp-settings-form" class="channel-settings-form">
                    <div class="channel-header">
                        <h3>
                            <img src="<?php echo CONVERSAAI_PRO_PLUGIN_URL; ?>admin/assets/images/whatsapp-logo.svg" alt="WhatsApp" class="channel-logo">
                            <?php _e('WhatsApp Business API Integration', 'conversaai-pro-wp'); ?>
                        </h3>
                        
                        <div class="channel-toggle">
                            <label class="conversaai-switch">
                                <input type="checkbox" id="whatsapp-enabled" name="enabled" value="1" <?php checked(isset($channels_settings['whatsapp']['enabled']) ? $channels_settings['whatsapp']['enabled'] : false); ?>>
                                <span class="conversaai-slider round"></span>
                            </label>
                            <span class="toggle-label"><?php _e('Enable WhatsApp', 'conversaai-pro-wp'); ?></span>
                        </div>
                    </div>
                    
                    <div class="channel-fields-container">
                        <div class="channel-description">
                            <p><?php _e('Connect your WhatsApp Business account to enable conversational AI on WhatsApp. You\'ll need access to the WhatsApp Business API through Meta or an official Business Solution Provider.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="whatsapp-phone-number"><?php _e('Phone Number', 'conversaai-pro-wp'); ?></label>
                            <input type="text" id="whatsapp-phone-number" name="phone_number" value="<?php echo esc_attr($channels_settings['whatsapp']['phone_number'] ?? ''); ?>" placeholder="+1234567890">
                            <p class="description"><?php _e('Enter your WhatsApp business phone number with country code.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="whatsapp-api-key"><?php _e('API Key', 'conversaai-pro-wp'); ?></label>
                            <input type="password" id="whatsapp-api-key" name="api_key" value="<?php echo esc_attr(!empty($channels_settings['whatsapp']['api_key']) ? '**************************************' : ''); ?>" placeholder="<?php _e('Enter your WhatsApp API key', 'conversaai-pro-wp'); ?>">
                            <p class="description"><?php _e('Your WhatsApp Business API key from Meta or your BSP.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="whatsapp-business-account-id"><?php _e('Business Account ID', 'conversaai-pro-wp'); ?></label>
                            <input type="text" id="whatsapp-business-account-id" name="business_account_id" value="<?php echo esc_attr($channels_settings['whatsapp']['business_account_id'] ?? ''); ?>" placeholder="1234567890">
                            <p class="description"><?php _e('Your WhatsApp Business Account ID (WABA) from Meta Business Manager.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="whatsapp-webhook-secret"><?php _e('Webhook Verify Token', 'conversaai-pro-wp'); ?></label>
                            <input type="password" id="whatsapp-webhook-secret" name="webhook_secret" value="<?php echo esc_attr(!empty($channels_settings['whatsapp']['webhook_secret']) ? '**************************************' : ''); ?>" placeholder="<?php _e('Enter your webhook verification token', 'conversaai-pro-wp'); ?>">
                            <p class="description"><?php _e('Create a unique token for webhook verification.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="whatsapp-welcome-message"><?php _e('Welcome Message', 'conversaai-pro-wp'); ?></label>
                            <textarea id="whatsapp-welcome-message" name="welcome_message" rows="3" placeholder="<?php _e('Enter a welcome message for new conversations', 'conversaai-pro-wp'); ?>"><?php echo esc_textarea($channels_settings['whatsapp']['welcome_message'] ?? ''); ?></textarea>
                            <p class="description"><?php _e('This message will be sent when a user starts a new conversation.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="webhook-info">
                            <h4><?php _e('Webhook Configuration', 'conversaai-pro-wp'); ?></h4>
                            <p><?php _e('Configure the following webhook in your WhatsApp Business account:', 'conversaai-pro-wp'); ?></p>
                            <div class="webhook-url">
                                <code><?php echo esc_url(site_url('wp-json/conversaai/v1/webhook/whatsapp')); ?></code>
                                <button type="button" class="copy-webhook-url button" data-url="<?php echo esc_url(site_url('wp-json/conversaai/v1/webhook/whatsapp')); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="channel-actions">
                        <button type="button" class="button button-secondary test-connection" data-channel="whatsapp">
                            <span class="dashicons dashicons-update"></span> <?php _e('Test Connection', 'conversaai-pro-wp'); ?>
                        </button>
                        <button type="submit" class="button button-primary save-channel" data-channel="whatsapp">
                            <?php _e('Save Settings', 'conversaai-pro-wp'); ?>
                        </button>
                        <span class="spinner"></span>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Facebook Messenger Tab -->
        <div class="conversaai-tab-content" id="tab-messenger">
            <div class="conversaai-card">
                <form id="messenger-settings-form" class="channel-settings-form">
                    <div class="channel-header">
                        <h3>
                            <img src="<?php echo CONVERSAAI_PRO_PLUGIN_URL; ?>admin/assets/images/messenger-logo.svg" alt="Facebook Messenger" class="channel-logo">
                            <?php _e('Facebook Messenger Integration', 'conversaai-pro-wp'); ?>
                        </h3>
                        
                        <div class="channel-toggle">
                            <label class="conversaai-switch">
                                <input type="checkbox" id="messenger-enabled" name="enabled" value="1" <?php checked(isset($channels_settings['messenger']['enabled']) ? $channels_settings['messenger']['enabled'] : false); ?>>
                                <span class="conversaai-slider round"></span>
                            </label>
                            <span class="toggle-label"><?php _e('Enable Messenger', 'conversaai-pro-wp'); ?></span>
                        </div>
                    </div>
                    
                    <div class="channel-fields-container">
                        <div class="channel-description">
                            <p><?php _e('Connect your Facebook Page to enable conversational AI in Messenger. You\'ll need a Facebook Page and a Meta App with Messenger permissions.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="messenger-page-id"><?php _e('Facebook Page ID', 'conversaai-pro-wp'); ?></label>
                            <input type="text" id="messenger-page-id" name="page_id" value="<?php echo esc_attr($channels_settings['messenger']['page_id'] ?? ''); ?>" placeholder="1234567890">
                            <p class="description"><?php _e('Your Facebook Page ID. You can find this in your Facebook Page settings.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="messenger-app-id"><?php _e('App ID', 'conversaai-pro-wp'); ?></label>
                            <input type="text" id="messenger-app-id" name="app_id" value="<?php echo esc_attr($channels_settings['messenger']['app_id'] ?? ''); ?>" placeholder="1234567890">
                            <p class="description"><?php _e('Your Meta App ID from the Meta for Developers dashboard.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="messenger-app-secret"><?php _e('App Secret', 'conversaai-pro-wp'); ?></label>
                            <input type="password" id="messenger-app-secret" name="app_secret" value="<?php echo esc_attr(!empty($channels_settings['messenger']['app_secret']) ? '**************************************' : ''); ?>" placeholder="<?php _e('Enter your App Secret', 'conversaai-pro-wp'); ?>">
                            <p class="description"><?php _e('Your Meta App Secret from the Meta for Developers dashboard.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="messenger-access-token"><?php _e('Page Access Token', 'conversaai-pro-wp'); ?></label>
                            <input type="password" id="messenger-access-token" name="access_token" value="<?php echo esc_attr(!empty($channels_settings['messenger']['access_token']) ? '**************************************' : ''); ?>" placeholder="<?php _e('Enter your Page Access Token', 'conversaai-pro-wp'); ?>">
                            <p class="description"><?php _e('A valid Page Access Token with messaging permissions.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="messenger-welcome-message"><?php _e('Welcome Message', 'conversaai-pro-wp'); ?></label>
                            <textarea id="messenger-welcome-message" name="welcome_message" rows="3" placeholder="<?php _e('Enter a welcome message for new conversations', 'conversaai-pro-wp'); ?>"><?php echo esc_textarea($channels_settings['messenger']['welcome_message'] ?? ''); ?></textarea>
                            <p class="description"><?php _e('This message will be sent when a user starts a new conversation.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="webhook-info">
                            <h4><?php _e('Webhook Configuration', 'conversaai-pro-wp'); ?></h4>
                            <p><?php _e('Configure the following webhook in your Meta App Settings:', 'conversaai-pro-wp'); ?></p>
                            <div class="webhook-url">
                                <code><?php echo esc_url(site_url('wp-json/conversaai/v1/webhook/messenger')); ?></code>
                                <button type="button" class="copy-webhook-url button" data-url="<?php echo esc_url(site_url('wp-json/conversaai/v1/webhook/messenger')); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php _e('Subscription Fields: messages, messaging_postbacks, messaging_optins', 'conversaai-pro-wp'); ?></p>
                        </div>
                    </div>
                    
                    <div class="channel-actions">
                        <button type="button" class="button button-secondary test-connection" data-channel="messenger">
                            <span class="dashicons dashicons-update"></span> <?php _e('Test Connection', 'conversaai-pro-wp'); ?>
                        </button>
                        <button type="submit" class="button button-primary save-channel" data-channel="messenger">
                            <?php _e('Save Settings', 'conversaai-pro-wp'); ?>
                        </button>
                        <span class="spinner"></span>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Instagram Tab -->
        <div class="conversaai-tab-content" id="tab-instagram">
            <div class="conversaai-card">
                <form id="instagram-settings-form" class="channel-settings-form">
                    <div class="channel-header">
                        <h3>
                            <img src="<?php echo CONVERSAAI_PRO_PLUGIN_URL; ?>admin/assets/images/instagram-logo.svg" alt="Instagram" class="channel-logo">
                            <?php _e('Instagram Direct Messages Integration', 'conversaai-pro-wp'); ?>
                        </h3>
                        
                        <div class="channel-toggle">
                            <label class="conversaai-switch">
                                <input type="checkbox" id="instagram-enabled" name="enabled" value="1" <?php checked(isset($channels_settings['instagram']['enabled']) ? $channels_settings['instagram']['enabled'] : false); ?>>
                                <span class="conversaai-slider round"></span>
                            </label>
                            <span class="toggle-label"><?php _e('Enable Instagram', 'conversaai-pro-wp'); ?></span>
                        </div>
                    </div>
                    
                    <div class="channel-fields-container">
                        <div class="channel-description">
                            <p><?php _e('Connect your Instagram Business account to enable conversational AI in Instagram Direct Messages. Your Instagram account must be a Business account and connected to a Facebook Page.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="instagram-account-id"><?php _e('Instagram Business Account ID', 'conversaai-pro-wp'); ?></label>
                            <input type="text" id="instagram-account-id" name="account_id" value="<?php echo esc_attr($channels_settings['instagram']['account_id'] ?? ''); ?>" placeholder="1234567890">
                            <p class="description"><?php _e('Your Instagram Business Account ID. You can find this in the Meta Business Manager.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="instagram-access-token"><?php _e('Access Token', 'conversaai-pro-wp'); ?></label>
                            <input type="password" id="instagram-access-token" name="access_token" value="<?php echo esc_attr(!empty($channels_settings['instagram']['access_token']) ? '**************************************' : ''); ?>" placeholder="<?php _e('Enter your Access Token', 'conversaai-pro-wp'); ?>">
                            <p class="description"><?php _e('A valid Instagram Graph API access token with instagram_messaging permissions.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="instagram-welcome-message"><?php _e('Welcome Message', 'conversaai-pro-wp'); ?></label>
                            <textarea id="instagram-welcome-message" name="welcome_message" rows="3" placeholder="<?php _e('Enter a welcome message for new conversations', 'conversaai-pro-wp'); ?>"><?php echo esc_textarea($channels_settings['instagram']['welcome_message'] ?? ''); ?></textarea>
                            <p class="description"><?php _e('This message will be sent when a user starts a new conversation.', 'conversaai-pro-wp'); ?></p>
                        </div>
                        
                        <div class="webhook-info">
                            <h4><?php _e('Webhook Configuration', 'conversaai-pro-wp'); ?></h4>
                            <p><?php _e('Instagram uses the same webhook as Facebook Messenger. Configure the following webhook in your Meta App Settings:', 'conversaai-pro-wp'); ?></p>
                            <div class="webhook-url">
                                <code><?php echo esc_url(site_url('wp-json/conversaai/v1/webhook/instagram')); ?></code>
                                <button type="button" class="copy-webhook-url button" data-url="<?php echo esc_url(site_url('wp-json/conversaai/v1/webhook/instagram')); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                            </div>
                            <p><?php _e('Subscription Fields: instagram_messaging', 'conversaai-pro-wp'); ?></p>
                        </div>
                    </div>
                    
                    <div class="channel-actions">
                        <button type="button" class="button button-secondary test-connection" data-channel="instagram">
                            <span class="dashicons dashicons-update"></span> <?php _e('Test Connection', 'conversaai-pro-wp'); ?>
                        </button>
                        <button type="submit" class="button button-primary save-channel" data-channel="instagram">
                            <?php _e('Save Settings', 'conversaai-pro-wp'); ?>
                        </button>
                        <span class="spinner"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Channels page specific styles */
.conversaai-overview-cards { margin: 20px 0; }
.conversaai-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
.conversaai-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px; }
.conversaai-stat-item { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; position: relative; }
.conversaai-channel-icon { width: 60px; height: 60px; border-radius: 50%; margin: 0 auto 10px; display: flex; justify-content: center; align-items: center; position: relative; background: #e9ecef; }
.conversaai-channel-icon.whatsapp { background: #25D366; }
.conversaai-channel-icon.messenger { background: #0084FF; }
.conversaai-channel-icon.instagram { background: linear-gradient(45deg, #405DE6, #5851DB, #833AB4, #C13584, #E1306C, #FD1D1D, #F56040, #F77737, #FCAF45, #FFDC80); }
.conversaai-channel-icon.webchat { background: #4c66ef; }
.conversaai-channel-status { position: absolute; top: -5px; right: -5px; font-size: 10px; padding: 2px 6px; border-radius: 10px; color: white; }
.conversaai-channel-status.channel-active { background: #28a745; }
.conversaai-channel-status.channel-inactive { background: #dc3545; }
.conversaai-channel-stats { text-align: left; margin-top: 15px; }
.stat-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
.stat-label { color: #666; }
.stat-value { font-weight: bold; }

/* Tabs */
.conversaai-tab-container { margin-top: 30px; }
.conversaai-tabs { display: flex; border-bottom: 1px solid #ddd; }
.conversaai-tab { padding: 10px 20px; cursor: pointer; border: 1px solid transparent; border-bottom: none; margin-bottom: -1px; font-weight: 500; }
.conversaai-tab.active { background: white; border-color: #ddd; border-bottom-color: white; }
.conversaai-tab-content { display: none; background: white; border: 1px solid #ddd; border-top: none; padding: 0; }
.conversaai-tab-content.active { display: block; }

/* Channel Settings Form */
.channel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
.channel-header h3 { margin: 0; display: flex; align-items: center; }
.channel-logo { height: 30px; margin-right: 10px; }
.channel-toggle { display: flex; align-items: center; }
.toggle-label { margin-left: 10px; }
.channel-fields-container { margin-bottom: 30px; }
.channel-description { margin-bottom: 20px; }
.form-field { margin-bottom: 20px; }
.form-field label { display: block; font-weight: 500; margin-bottom: 5px; }
.form-field input, .form-field textarea { width: 100%; padding: 8px; }
.channel-actions { display: flex; align-items: center; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
.channel-actions .spinner { float: none; margin-top: 0; }

/* Webhook Info Section */
.webhook-info { background: #f7f7f7; padding: 15px; border-radius: 8px; margin-top: 20px; }
.webhook-info h4 { margin-top: 0; }
.webhook-url { display: flex; align-items: center; background: #fff; padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin: 10px 0; }
.webhook-url code { flex: 1; padding: 5px; }
.webhook-url .button { padding: 0; min-height: 30px; }
.webhook-url .dashicons { margin: 0; }

/* Switch Toggle */
.conversaai-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}
.conversaai-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.conversaai-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
}
.conversaai-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
}
input:checked + .conversaai-slider {
    background-color: #4c66ef;
}
input:focus + .conversaai-slider {
    box-shadow: 0 0 1px #4c66ef;
}
input:checked + .conversaai-slider:before {
    transform: translateX(26px);
}
.conversaai-slider.round {
    border-radius: 34px;
}
.conversaai-slider.round:before {
    border-radius: 50%;
}

@media (max-width: 782px) {
    .conversaai-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .conversaai-stats-grid { grid-template-columns: 1fr; }
    .channel-header { flex-direction: column; align-items: flex-start; }
    .channel-toggle { margin-top: 10px; }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.conversaai-tab').on('click', function() {
        $('.conversaai-tab').removeClass('active');
        $(this).addClass('active');
        $('.conversaai-tab-content').removeClass('active');
        $('#tab-' + $(this).data('tab')).addClass('active');
    });
    
    // Copy webhook URL to clipboard
    $('.copy-webhook-url').on('click', function() {
        const url = $(this).data('url');
        
        // Create temporary input
        const tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(url).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Show success message
        const originalText = $(this).html();
        $(this).html('<span class="dashicons dashicons-yes"></span>');
        setTimeout(() => {
            $(this).html(originalText);
        }, 2000);
    });
    
    // Toggle channel enabled state
    $('input[type="checkbox"][id$="-enabled"]').on('change', function() {
        const channel = $(this).attr('id').replace('-enabled', '');
        const isEnabled = $(this).is(':checked');
        
        // Change visual state immediately for better UX
        const $channelIcon = $(`.conversaai-channel-icon.${channel}`);
        const $status = $channelIcon.find('.conversaai-channel-status');
        
        if (isEnabled) {
            $status.removeClass('channel-inactive').addClass('channel-active').text('<?php _e('Active', 'conversaai-pro-wp'); ?>');
        } else {
            $status.removeClass('channel-active').addClass('channel-inactive').text('<?php _e('Inactive', 'conversaai-pro-wp'); ?>');
        }
        
        // Send AJAX request to update the state
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_toggle_channel',
                nonce: '<?php echo wp_create_nonce('conversaai_channels_nonce'); ?>',
                channel_type: channel,
                status: isEnabled ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message);
                } else {
                    showError(response.data.message || '<?php _e('Error toggling channel.', 'conversaai-pro-wp'); ?>');
                    // Revert the checkbox state if there was an error
                    $('#' + channel + '-enabled').prop('checked', !isEnabled);
                }
            },
            error: function() {
                showError('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
                // Revert the checkbox state
                $('#' + channel + '-enabled').prop('checked', !isEnabled);
            }
        });
    });
    
    // Test connection button handler
    $('.test-connection').on('click', function() {
        const channel = $(this).data('channel');
        const $button = $(this);
        const $spinner = $button.siblings('.spinner');
        
        // Disable button and show spinner
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // Collect form data
        const formData = $('#' + channel + '-settings-form').serialize();
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_test_channel_connection',
                nonce: '<?php echo wp_create_nonce('conversaai_channels_nonce'); ?>',
                channel_type: channel,
                settings_data: formData
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message);
                } else {
                    showError(response.data.message || '<?php _e('Connection test failed.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function() {
                showError('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Channel settings form submission
    $('.channel-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const channel = $(this).attr('id').replace('-settings-form', '');
        const $form = $(this);
        const $button = $form.find('.save-channel');
        const $spinner = $button.siblings('.spinner');
        
        // Disable button and show spinner
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // Collect form data
        const formData = {};
        $form.find('input, textarea, select').each(function() {
            const $input = $(this);
            const name = $input.attr('name');
            
            if (name) {
                if ($input.attr('type') === 'checkbox') {
                    formData[name] = $input.is(':checked') ? 1 : 0;
                } else {
                    formData[name] = $input.val();
                }
            }
        });
        
        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'conversaai_save_channel_settings',
                nonce: '<?php echo wp_create_nonce('conversaai_channels_nonce'); ?>',
                channel_type: channel,
                settings_data: formData
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message);
                } else {
                    showError(response.data.message || '<?php _e('Error saving settings.', 'conversaai-pro-wp'); ?>');
                }
            },
            error: function() {
                showError('<?php _e('Connection error. Please try again.', 'conversaai-pro-wp'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Helper functions for showing messages
    function showMessage(message) {
        $('#message p').text(message);
        $('#message').show();
        setTimeout(function() { $('#message').fadeOut(); }, 5000);
    }
    
    function showError(message) {
        $('#error-message p').text(message);
        $('#error-message').show();
        setTimeout(function() { $('#error-message').fadeOut(); }, 5000);
    }
});
</script>