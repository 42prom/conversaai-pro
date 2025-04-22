<?php
/**
 * Messaging Channels admin page.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin
 */

namespace ConversaAI_Pro_WP\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Messaging Channels admin page class.
 *
 * Handles the messaging channels management and configuration.
 *
 * @since      1.0.0
 */
class Channels_Page {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Add AJAX handlers for channel operations
        add_action('wp_ajax_conversaai_save_channel_settings', array($this, 'ajax_save_channel_settings'));
        add_action('wp_ajax_conversaai_test_channel_connection', array($this, 'ajax_test_channel_connection'));
        add_action('wp_ajax_conversaai_toggle_channel', array($this, 'ajax_toggle_channel'));
    }

    /**
     * Display the channels page.
     *
     * @since    1.0.0
     */
    public function display() {
        // Get channels settings
        $channels_settings = get_option('conversaai_pro_channels_settings', array(
            'whatsapp' => array(
                'enabled' => false,
                'phone_number' => '',
                'api_key' => '',
                'business_account_id' => '',
                'webhook_secret' => '',
                'welcome_message' => 'Hello! Thank you for contacting us on WhatsApp. How can we assist you today?',
            ),
            'messenger' => array(
                'enabled' => false,
                'page_id' => '',
                'app_id' => '',
                'app_secret' => '',
                'access_token' => '',
                'welcome_message' => 'Hello! Thank you for contacting us on Messenger. How can we assist you today?',
            ),
            'instagram' => array(
                'enabled' => false,
                'account_id' => '',
                'access_token' => '',
                'welcome_message' => 'Hello! Thank you for contacting us on Instagram. How can we assist you today?',
            )
        ));
        
        // Get all recent conversations for each channel (useful for statistics)
        global $wpdb;
        $table_name = $wpdb->prefix . CONVERSAAI_PRO_CONVERSATIONS_TABLE;
        
        $channel_stats = array();
        $channels = array('whatsapp', 'messenger', 'instagram', 'webchat');
        
        foreach ($channels as $channel) {
            $channel_stats[$channel] = array(
                'total' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE channel = %s",
                    $channel
                )),
                'last_24h' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE channel = %s AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                    $channel
                )),
                'last_week' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE channel = %s AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                    $channel
                )),
            );
        }
        
        // Load the view
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/channels-page.php';
    }

    /**
     * AJAX handler for saving channel settings.
     *
     * @since    1.0.0
     */
    public function ajax_save_channel_settings() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_channels_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to change channel settings.', 'conversaai-pro-wp')));
        }
        
        // Get the channel type and settings data
        $channel_type = isset($_POST['channel_type']) ? sanitize_text_field($_POST['channel_type']) : '';
        $settings_data = isset($_POST['settings_data']) ? $_POST['settings_data'] : array();
        
        if (!in_array($channel_type, array('whatsapp', 'messenger', 'instagram'))) {
            wp_send_json_error(array('message' => __('Invalid channel type.', 'conversaai-pro-wp')));
        }
        
        // Get current settings
        $channels_settings = get_option('conversaai_pro_channels_settings', array());
        
        // Sanitize and update specific channel settings
        $sanitized = $this->sanitize_channel_settings($channel_type, $settings_data);
        $channels_settings[$channel_type] = $sanitized;
        
        // Save updated settings
        update_option('conversaai_pro_channels_settings', $channels_settings);
        
        wp_send_json_success(array(
            'message' => sprintf(__('%s settings saved successfully.', 'conversaai-pro-wp'), ucfirst($channel_type)),
            'settings' => $sanitized,
        ));
    }

    /**
     * AJAX handler for testing channel connections.
     *
     * @since    1.0.0
     */
    public function ajax_test_channel_connection() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_channels_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to test connections.', 'conversaai-pro-wp')));
        }
        
        // Get the channel type
        $channel_type = isset($_POST['channel_type']) ? sanitize_text_field($_POST['channel_type']) : '';
        
        if (!in_array($channel_type, array('whatsapp', 'messenger', 'instagram'))) {
            wp_send_json_error(array('message' => __('Invalid channel type.', 'conversaai-pro-wp')));
        }
        
        // Get settings for this channel
        $channels_settings = get_option('conversaai_pro_channels_settings', array());
        
        if (!isset($channels_settings[$channel_type])) {
            wp_send_json_error(array('message' => __('Channel settings not found.', 'conversaai-pro-wp')));
        }
        
        $channel_settings = $channels_settings[$channel_type];
        
        // Initialize the channel class based on type
        $channel_class = null;
        
        switch ($channel_type) {
            case 'whatsapp':
                require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/class-whatsapp-channel.php';
                $channel_class = new \ConversaAI_Pro_WP\Integrations\Messaging\WhatsApp_Channel();
                break;
                
            case 'messenger':
                require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/class-messenger-channel.php';
                $channel_class = new \ConversaAI_Pro_WP\Integrations\Messaging\Messenger_Channel();
                break;
                
            case 'instagram':
                require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/messaging/class-instagram-channel.php';
                $channel_class = new \ConversaAI_Pro_WP\Integrations\Messaging\Instagram_Channel();
                break;
        }
        
        if (!$channel_class) {
            wp_send_json_error(array('message' => __('Channel initialization failed.', 'conversaai-pro-wp')));
        }
        
        // Initialize the channel with settings
        $channel_class->initialize($channel_settings);
        
        // Test the connection
        $test_result = $channel_class->test_connection();
        
        if ($test_result['success']) {
            wp_send_json_success(array(
                'message' => sprintf(__('Connection to %s successful.', 'conversaai-pro-wp'), ucfirst($channel_type)),
                'details' => $test_result['details'],
            ));
        } else {
            wp_send_json_error(array(
                'message' => sprintf(__('Connection to %s failed: %s', 'conversaai-pro-wp'), 
                    ucfirst($channel_type), 
                    $test_result['error']
                ),
            ));
        }
    }

    /**
     * AJAX handler for toggling channel active status.
     *
     * @since    1.0.0
     */
    public function ajax_toggle_channel() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_channels_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to toggle channels.', 'conversaai-pro-wp')));
        }
        
        // Get the channel type and status
        $channel_type = isset($_POST['channel_type']) ? sanitize_text_field($_POST['channel_type']) : '';
        $status = isset($_POST['status']) ? (bool) $_POST['status'] : false;
        
        if (!in_array($channel_type, array('whatsapp', 'messenger', 'instagram'))) {
            wp_send_json_error(array('message' => __('Invalid channel type.', 'conversaai-pro-wp')));
        }
        
        // Get current settings
        $channels_settings = get_option('conversaai_pro_channels_settings', array());
        
        if (!isset($channels_settings[$channel_type])) {
            $channels_settings[$channel_type] = array('enabled' => false);
        }
        
        // Update status
        $channels_settings[$channel_type]['enabled'] = $status;
        
        // Save updated settings
        update_option('conversaai_pro_channels_settings', $channels_settings);
        
        $message = $status 
            ? sprintf(__('%s channel enabled successfully.', 'conversaai-pro-wp'), ucfirst($channel_type))
            : sprintf(__('%s channel disabled.', 'conversaai-pro-wp'), ucfirst($channel_type));
        
        wp_send_json_success(array(
            'message' => $message,
            'status' => $status,
        ));
    }

    /**
     * Sanitize channel settings.
     *
     * @since    1.0.0
     * @param    string    $channel_type    The channel type.
     * @param    array     $settings_data   The settings data to sanitize.
     * @return   array     The sanitized settings.
     */
    private function sanitize_channel_settings($channel_type, $settings_data) {
        $sanitized = array();
        
        // Common settings for all channels
        $sanitized['enabled'] = isset($settings_data['enabled']) ? (bool) $settings_data['enabled'] : false;
        $sanitized['welcome_message'] = isset($settings_data['welcome_message']) 
            ? sanitize_textarea_field($settings_data['welcome_message']) 
            : '';
            
        // Channel-specific settings
        switch ($channel_type) {
            case 'whatsapp':
                $sanitized['phone_number'] = isset($settings_data['phone_number']) 
                    ? sanitize_text_field($settings_data['phone_number']) 
                    : '';
                
                // API key handling (keep existing value if placeholder)
                if (isset($settings_data['api_key']) && !empty($settings_data['api_key'])) {
                    if (strpos($settings_data['api_key'], '******') === 0) {
                        // This is a masked placeholder, keep existing value
                        $existing_settings = get_option('conversaai_pro_channels_settings', array());
                        $sanitized['api_key'] = isset($existing_settings['whatsapp']['api_key']) 
                            ? $existing_settings['whatsapp']['api_key'] 
                            : '';
                    } else {
                        // This is a new value
                        $sanitized['api_key'] = sanitize_text_field($settings_data['api_key']);
                    }
                } else {
                    $sanitized['api_key'] = '';
                }
                
                $sanitized['business_account_id'] = isset($settings_data['business_account_id']) 
                    ? sanitize_text_field($settings_data['business_account_id']) 
                    : '';
                    
                // Webhook secret handling (similar to API key)
                if (isset($settings_data['webhook_secret']) && !empty($settings_data['webhook_secret'])) {
                    if (strpos($settings_data['webhook_secret'], '******') === 0) {
                        $existing_settings = get_option('conversaai_pro_channels_settings', array());
                        $sanitized['webhook_secret'] = isset($existing_settings['whatsapp']['webhook_secret']) 
                            ? $existing_settings['whatsapp']['webhook_secret'] 
                            : '';
                    } else {
                        $sanitized['webhook_secret'] = sanitize_text_field($settings_data['webhook_secret']);
                    }
                } else {
                    $sanitized['webhook_secret'] = '';
                }
                break;
                
            case 'messenger':
                $sanitized['page_id'] = isset($settings_data['page_id']) 
                    ? sanitize_text_field($settings_data['page_id']) 
                    : '';
                $sanitized['app_id'] = isset($settings_data['app_id']) 
                    ? sanitize_text_field($settings_data['app_id']) 
                    : '';
                
                // App Secret handling
                if (isset($settings_data['app_secret']) && !empty($settings_data['app_secret'])) {
                    if (strpos($settings_data['app_secret'], '******') === 0) {
                        $existing_settings = get_option('conversaai_pro_channels_settings', array());
                        $sanitized['app_secret'] = isset($existing_settings['messenger']['app_secret']) 
                            ? $existing_settings['messenger']['app_secret'] 
                            : '';
                    } else {
                        $sanitized['app_secret'] = sanitize_text_field($settings_data['app_secret']);
                    }
                } else {
                    $sanitized['app_secret'] = '';
                }
                
                // Access Token handling
                if (isset($settings_data['access_token']) && !empty($settings_data['access_token'])) {
                    if (strpos($settings_data['access_token'], '******') === 0) {
                        $existing_settings = get_option('conversaai_pro_channels_settings', array());
                        $sanitized['access_token'] = isset($existing_settings['messenger']['access_token']) 
                            ? $existing_settings['messenger']['access_token'] 
                            : '';
                    } else {
                        $sanitized['access_token'] = sanitize_text_field($settings_data['access_token']);
                    }
                } else {
                    $sanitized['access_token'] = '';
                }
                break;
                
            case 'instagram':
                $sanitized['account_id'] = isset($settings_data['account_id']) 
                    ? sanitize_text_field($settings_data['account_id']) 
                    : '';
                
                // Access Token handling
                if (isset($settings_data['access_token']) && !empty($settings_data['access_token'])) {
                    if (strpos($settings_data['access_token'], '******') === 0) {
                        $existing_settings = get_option('conversaai_pro_channels_settings', array());
                        $sanitized['access_token'] = isset($existing_settings['instagram']['access_token']) 
                            ? $existing_settings['instagram']['access_token'] 
                            : '';
                    } else {
                        $sanitized['access_token'] = sanitize_text_field($settings_data['access_token']);
                    }
                } else {
                    $sanitized['access_token'] = '';
                }
                break;
        }
        
        return $sanitized;
    }
}