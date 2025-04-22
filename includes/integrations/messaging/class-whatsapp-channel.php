<?php
/**
 * WhatsApp messaging channel implementation.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/integrations/messaging
 */

namespace ConversaAI_Pro_WP\Integrations\Messaging;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Utils\API_Request;

/**
 * WhatsApp channel class.
 *
 * Implements WhatsApp Business API messaging.
 *
 * @since      1.0.0
 */
class WhatsApp_Channel extends Abstract_Messaging_Channel {

    /**
     * WhatsApp Business API endpoint.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    The WhatsApp API endpoint.
     */
    private $api_endpoint = 'https://graph.facebook.com/v16.0';

    /**
     * Initialize the channel with settings.
     *
     * @since    1.0.0
     * @param    array    $settings    Channel-specific settings.
     * @return   bool     Whether initialization was successful.
     */
    public function initialize($settings) {
        parent::initialize($settings);
        
        // Verify required settings
        if (empty($settings['phone_number']) || empty($settings['api_key'])) {
            $this->logger->warning('WhatsApp channel initialized with missing required settings', array(
                'has_phone' => !empty($settings['phone_number']),
                'has_api_key' => !empty($settings['api_key'])
            ));
            return false;
        }
        
        // Return success
        return true;
    }

    /**
     * Send a message to a WhatsApp recipient.
     *
     * @since    1.0.0
     * @param    string    $recipient_id    The recipient phone number.
     * @param    string    $message         The message to send.
     * @param    array     $options         Additional options for the message.
     * @return   array     Response data with message ID and other info.
     */
    public function send_message($recipient_id, $message, $options = array()) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'WhatsApp channel is not available or properly configured'
            );
        }
        
        // Prepare message text
        $message = $this->prepare_text_for_sending($message);
        
        // Format recipient (ensure it's in international format)
        $recipient_id = $this->format_phone_number($recipient_id);
        
        // Build the API request
        $endpoint = $this->api_endpoint . '/' . $this->settings['business_account_id'] . '/messages';
        
        // Determine message type (text, template, etc.)
        $message_type = isset($options['type']) ? $options['type'] : 'text';
        
        // Build message payload
        $payload = array(
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient_id,
        );
        
        // Add message content based on type
        if ($message_type === 'template') {
            // Template message
            $template_name = isset($options['template_name']) ? $options['template_name'] : null;
            $template_language = isset($options['template_language']) ? $options['template_language'] : 'en_US';
            $template_components = isset($options['template_components']) ? $options['template_components'] : array();
            
            if (empty($template_name)) {
                return array(
                    'success' => false,
                    'error' => 'Template name is required for template messages'
                );
            }
            
            $payload['type'] = 'template';
            $payload['template'] = array(
                'name' => $template_name,
                'language' => array('code' => $template_language),
                'components' => $template_components
            );
        } else {
            // Regular text message
            $payload['type'] = 'text';
            $payload['text'] = array('body' => $message);
        }
        
        // Send the request
        $api_request = new API_Request();
        $response = $api_request->post(
            $endpoint,
            $payload,
            array(
                'Authorization' => 'Bearer ' . $this->settings['api_key'],
                'Content-Type' => 'application/json',
            )
        );
        
        // Process the response
        if (is_wp_error($response)) {
            $this->logger->error('WhatsApp API error', array(
                'error' => $response->get_error_message(),
                'recipient' => $recipient_id
            ));
            
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['error'])) {
            $this->logger->error('WhatsApp API returned error', array(
                'error' => $response_body['error'],
                'recipient' => $recipient_id
            ));
            
            return array(
                'success' => false,
                'error' => $response_body['error']['message'] ?? 'Unknown API error'
            );
        }
        
        if (isset($response_body['messages']) && isset($response_body['messages'][0]['id'])) {
            return array(
                'success' => true,
                'message_id' => $response_body['messages'][0]['id']
            );
        }
        
        return array(
            'success' => false,
            'error' => 'Invalid API response'
        );
    }

    /**
     * Process incoming webhook data from WhatsApp.
     *
     * @since    1.0.0
     * @param    array    $data    The webhook data to process.
     * @return   array    Processing result.
     */
    public function process_webhook($data) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'WhatsApp channel is not available'
            );
        }
        
        try {
            // Check if this is a verification request
            if (isset($data['hub.mode']) && $data['hub.mode'] === 'subscribe') {
                // This is a webhook verification request
                return $this->handle_verification_request($data);
            }
            
            // Parse webhook data
            if (!isset($data['entry']) || !is_array($data['entry'])) {
                $this->logger->warning('Invalid webhook data received', array(
                    'data' => $data
                ));
                
                return array(
                    'success' => false,
                    'error' => 'Invalid webhook data'
                );
            }
            
            $processed = 0;
            $errors = 0;
            
            // Process each entry
            foreach ($data['entry'] as $entry) {
                if (!isset($entry['changes']) || !is_array($entry['changes'])) {
                    continue;
                }
                
                // Process each change
                foreach ($entry['changes'] as $change) {
                    if (!isset($change['value']) || !isset($change['value']['messages'])) {
                        continue;
                    }
                    
                    // Process each message
                    foreach ($change['value']['messages'] as $message) {
                        $result = $this->process_incoming_message($message, $change['value']);
                        
                        if ($result['success']) {
                            $processed++;
                        } else {
                            $errors++;
                        }
                    }
                }
            }
            
            return array(
                'success' => true,
                'processed' => $processed,
                'errors' => $errors
            );
        } catch (\Exception $e) {
            $this->logger->error('Error processing WhatsApp webhook', array(
                'error' => $e->getMessage()
            ));
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Process an individual incoming message from WhatsApp.
     *
     * @since    1.0.0
     * @param    array    $message    The message data.
     * @param    array    $metadata   Additional metadata.
     * @return   array    Processing result.
     */
    private function process_incoming_message($message, $metadata) {
        // Check if this is a text message
        if (!isset($message['type']) || $message['type'] !== 'text' || !isset($message['text']['body'])) {
            $this->logger->info('Ignoring non-text message', array(
                'type' => $message['type'] ?? 'unknown'
            ));
            
            return array(
                'success' => false,
                'error' => 'Only text messages are supported'
            );
        }
        
        // Extract sender and message text
        $sender_id = $message['from'] ?? null;
        $message_text = $message['text']['body'] ?? '';
        
        if (empty($sender_id) || empty($message_text)) {
            return array(
                'success' => false,
                'error' => 'Missing sender or message text'
            );
        }
        
        // Handle the message
        $result = $this->handle_incoming_message($sender_id, $message_text, array(
            'message_id' => $message['id'] ?? '',
            'timestamp' => $message['timestamp'] ?? '',
            'metadata' => $metadata
        ));
        
        return array(
            'success' => $result,
            'sender' => $sender_id,
            'message' => substr($message_text, 0, 50) . (strlen($message_text) > 50 ? '...' : '')
        );
    }

    /**
     * Handle webhook verification request.
     *
     * @since    1.0.0
     * @param    array    $data    The verification data.
     * @return   array    Verification result.
     */
    private function handle_verification_request($data) {
        $mode = $data['hub.mode'] ?? '';
        $token = $data['hub.verify_token'] ?? '';
        $challenge = $data['hub.challenge'] ?? '';
        
        if ($mode !== 'subscribe' || $token !== $this->settings['webhook_secret']) {
            $this->logger->warning('Invalid webhook verification attempt', array(
                'mode' => $mode,
                'token_valid' => $token === $this->settings['webhook_secret']
            ));
            
            return array(
                'success' => false,
                'error' => 'Verification failed'
            );
        }
        
        $this->logger->info('Webhook verification successful');
        
        return array(
            'success' => true,
            'challenge' => $challenge
        );
    }

    /**
     * Verify webhook signature/security.
     *
     * @since    1.0.0
     * @param    string    $signature     The signature from the webhook headers.
     * @param    string    $body          The raw request body.
     * @return   bool      Whether the webhook is valid.
     */
    public function verify_webhook($signature, $body) {
        // WhatsApp/Meta uses x-hub-signature headers
        if (empty($signature) || empty($body) || empty($this->settings['api_key'])) {
            return false;
        }
        
        // Extract the signature value
        $signature_parts = explode('=', $signature);
        if (count($signature_parts) !== 2 || $signature_parts[0] !== 'sha256') {
            return false;
        }
        
        $expected_signature = hash_hmac('sha256', $body, $this->settings['api_key']);
        $actual_signature = $signature_parts[1];
        
        return hash_equals($expected_signature, $actual_signature);
    }

    /**
     * Test the WhatsApp connection.
     *
     * @since    1.0.0
     * @return   array    Result of the connection test.
     */
    public function test_connection() {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'WhatsApp channel is not properly configured'
            );
        }
        
        // Test the connection by fetching the business profile
        $endpoint = $this->api_endpoint . '/' . $this->settings['phone_number'] . '/whatsapp_business_profile';
        
        $api_request = new API_Request();
        $response = $api_request->get(
            $endpoint,
            array(
                'Authorization' => 'Bearer ' . $this->settings['api_key'],
                'Content-Type' => 'application/json',
            )
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['error'])) {
            return array(
                'success' => false,
                'error' => $response_body['error']['message'] ?? 'Unknown API error'
            );
        }
        
        // Successfully fetched business profile
        return array(
            'success' => true,
            'details' => isset($response_body['data'][0]) ? $response_body['data'][0] : $response_body
        );
    }

    /**
     * Get the channel name.
     *
     * @since    1.0.0
     * @return   string   The channel name.
     */
    public function get_name() {
        return __('WhatsApp', 'conversaai-pro-wp');
    }

    /**
     * Get the channel identifier.
     *
     * @since    1.0.0
     * @return   string   The channel identifier.
     */
    public function get_id() {
        return 'whatsapp';
    }

    /**
     * Check if the channel is properly configured and available.
     *
     * @since    1.0.0
     * @return   bool     Whether the channel is available.
     */
    public function is_available() {
        return $this->enabled && 
               !empty($this->settings['phone_number']) && 
               !empty($this->settings['api_key']) && 
               !empty($this->settings['business_account_id']);
    }

    /**
     * Format phone number to ensure it's in international format.
     *
     * @since    1.0.0
     * @param    string    $phone_number    The phone number to format.
     * @return   string    The formatted phone number.
     */
    private function format_phone_number($phone_number) {
        // Remove any non-numeric characters
        $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
        
        // Ensure it starts with country code (add + if missing)
        if (substr($phone_number, 0, 1) !== '+') {
            $phone_number = '+' . $phone_number;
        }
        
        return $phone_number;
    }
}