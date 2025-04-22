<?php
/**
 * Facebook Messenger channel implementation.
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
 * Messenger channel class.
 *
 * Implements Facebook Messenger API messaging.
 *
 * @since      1.0.0
 */
class Messenger_Channel extends Abstract_Messaging_Channel {

    /**
     * Messenger API endpoint.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    The Messenger API endpoint.
     */
    private $api_endpoint = 'https://graph.facebook.com/v16.0/me/messages';

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
        if (empty($settings['page_id']) || empty($settings['app_id']) || empty($settings['access_token'])) {
            $this->logger->warning('Messenger channel initialized with missing required settings', array(
                'has_page_id' => !empty($settings['page_id']),
                'has_app_id' => !empty($settings['app_id']),
                'has_access_token' => !empty($settings['access_token'])
            ));
            return false;
        }
        
        // Return success
        return true;
    }

    /**
     * Send a message to a Messenger recipient.
     *
     * @since    1.0.0
     * @param    string    $recipient_id    The recipient PSID.
     * @param    string    $message         The message to send.
     * @param    array     $options         Additional options for the message.
     * @return   array     Response data with message ID and other info.
     */
    public function send_message($recipient_id, $message, $options = array()) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'Messenger channel is not available or properly configured'
            );
        }
        
        // Prepare message text
        $message = $this->prepare_text_for_sending($message);
        
        // Build the API request
        $endpoint = $this->api_endpoint . '?access_token=' . urlencode($this->settings['access_token']);
        
        // Determine message type (text, template, etc.)
        $message_type = isset($options['type']) ? $options['type'] : 'text';
        
        // Build message payload
        $payload = array(
            'recipient' => array(
                'id' => $recipient_id
            ),
            'messaging_type' => 'RESPONSE'
        );
        
        // Add message content based on type
        if ($message_type === 'template') {
            // Template message
            $template_type = isset($options['template_type']) ? $options['template_type'] : 'generic';
            $template_data = isset($options['template_data']) ? $options['template_data'] : array();
            
            $payload['message'] = array(
                'attachment' => array(
                    'type' => 'template',
                    'payload' => array(
                        'template_type' => $template_type,
                        'elements' => $template_data
                    )
                )
            );
        } elseif ($message_type === 'quick_replies') {
            // Quick replies
            $quick_replies = isset($options['quick_replies']) ? $options['quick_replies'] : array();
            
            $payload['message'] = array(
                'text' => $message,
                'quick_replies' => $quick_replies
            );
        } else {
            // Regular text message
            $payload['message'] = array(
                'text' => $message
            );
        }
        
        // Send the request
        $api_request = new API_Request();
        $response = $api_request->post(
            $endpoint,
            $payload,
            array(
                'Content-Type' => 'application/json',
            )
        );
        
        // Process the response
        if (is_wp_error($response)) {
            $this->logger->error('Messenger API error', array(
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
            $this->logger->error('Messenger API returned error', array(
                'error' => $response_body['error'],
                'recipient' => $recipient_id
            ));
            
            return array(
                'success' => false,
                'error' => $response_body['error']['message'] ?? 'Unknown API error'
            );
        }
        
        if (isset($response_body['message_id'])) {
            return array(
                'success' => true,
                'message_id' => $response_body['message_id'],
                'recipient_id' => $response_body['recipient_id'] ?? $recipient_id
            );
        }
        
        return array(
            'success' => false,
            'error' => 'Invalid API response'
        );
    }

    /**
     * Process incoming webhook data from Messenger.
     *
     * @since    1.0.0
     * @param    array    $data    The webhook data to process.
     * @return   array    Processing result.
     */
    public function process_webhook($data) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'Messenger channel is not available'
            );
        }
        
        try {
            // Check if this is a verification request
            if (isset($data['hub_mode']) && $data['hub_mode'] === 'subscribe') {
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
                if (!isset($entry['messaging']) || !is_array($entry['messaging'])) {
                    continue;
                }
                
                // Process each messaging event
                foreach ($entry['messaging'] as $event) {
                    $result = $this->process_messaging_event($event);
                    
                    if ($result['success']) {
                        $processed++;
                    } else {
                        $errors++;
                    }
                }
            }
            
            return array(
                'success' => true,
                'processed' => $processed,
                'errors' => $errors
            );
        } catch (\Exception $e) {
            $this->logger->error('Error processing Messenger webhook', array(
                'error' => $e->getMessage()
            ));
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Process an individual messaging event from Messenger.
     *
     * @since    1.0.0
     * @param    array    $event    The messaging event data.
     * @return   array    Processing result.
     */
    private function process_messaging_event($event) {
        // Check if this is a message event
        if (isset($event['message']) && isset($event['message']['text']) && !isset($event['message']['is_echo'])) {
            // This is an incoming message
            return $this->process_incoming_message($event);
        }
        
        // Check if this is a postback event
        if (isset($event['postback']) && isset($event['postback']['payload'])) {
            // This is a postback (button click)
            return $this->process_postback($event);
        }
        
        // Unsupported event type
        $this->logger->info('Ignoring unsupported messaging event', array(
            'event_type' => isset($event['message']) ? 'message_echo' : 'unknown'
        ));
        
        return array(
            'success' => false,
            'error' => 'Unsupported event type'
        );
    }

    /**
     * Process an incoming message from Messenger.
     *
     * @since    1.0.0
     * @param    array    $event    The message event data.
     * @return   array    Processing result.
     */
    private function process_incoming_message($event) {
        // Extract sender and message text
        $sender_id = $event['sender']['id'] ?? null;
        $message_text = $event['message']['text'] ?? '';
        
        if (empty($sender_id) || empty($message_text)) {
            return array(
                'success' => false,
                'error' => 'Missing sender or message text'
            );
        }
        
        // Handle the message
        $result = $this->handle_incoming_message($sender_id, $message_text, array(
            'message_id' => $event['message']['mid'] ?? '',
            'timestamp' => $event['timestamp'] ?? '',
            'page_id' => $event['recipient']['id'] ?? $this->settings['page_id']
        ));
        
        return array(
            'success' => $result,
            'sender' => $sender_id,
            'message' => substr($message_text, 0, 50) . (strlen($message_text) > 50 ? '...' : '')
        );
    }

    /**
     * Process a postback from Messenger.
     *
     * @since    1.0.0
     * @param    array    $event    The postback event data.
     * @return   array    Processing result.
     */
    private function process_postback($event) {
        // Extract sender and postback payload
        $sender_id = $event['sender']['id'] ?? null;
        $payload = $event['postback']['payload'] ?? '';
        $title = $event['postback']['title'] ?? '';
        
        if (empty($sender_id) || empty($payload)) {
            return array(
                'success' => false,
                'error' => 'Missing sender or payload'
            );
        }
        
        // Handle the postback as a message
        $message_text = $title ?: $payload;
        
        $result = $this->handle_incoming_message($sender_id, $message_text, array(
            'postback' => true,
            'payload' => $payload,
            'timestamp' => $event['timestamp'] ?? '',
            'page_id' => $event['recipient']['id'] ?? $this->settings['page_id']
        ));
        
        return array(
            'success' => $result,
            'sender' => $sender_id,
            'payload' => $payload
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
        $mode = $data['hub_mode'] ?? '';
        $token = $data['hub_verify_token'] ?? '';
        $challenge = $data['hub_challenge'] ?? '';
        
        if ($mode !== 'subscribe' || $token !== $this->settings['app_secret']) {
            $this->logger->warning('Invalid webhook verification attempt', array(
                'mode' => $mode,
                'token_valid' => $token === $this->settings['app_secret']
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
        // Facebook uses x-hub-signature headers
        if (empty($signature) || empty($body) || empty($this->settings['app_secret'])) {
            return false;
        }
        
        // Extract the signature value
        $signature_parts = explode('=', $signature);
        if (count($signature_parts) !== 2 || $signature_parts[0] !== 'sha1') {
            return false;
        }
        
        $expected_signature = hash_hmac('sha1', $body, $this->settings['app_secret']);
        $actual_signature = $signature_parts[1];
        
        return hash_equals($expected_signature, $actual_signature);
    }

    /**
     * Test the Messenger connection.
     *
     * @since    1.0.0
     * @return   array    Result of the connection test.
     */
    public function test_connection() {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'error' => 'Messenger channel is not properly configured'
            );
        }
        
        // Test the connection by fetching the page details
        $endpoint = "https://graph.facebook.com/v16.0/{$this->settings['page_id']}?fields=name,id,category&access_token={$this->settings['access_token']}";
        
        $api_request = new API_Request();
        $response = $api_request->get($endpoint);
        
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
        
        // Check if the page ID matches
        if (!isset($response_body['id']) || $response_body['id'] !== $this->settings['page_id']) {
            return array(
                'success' => false,
                'error' => 'Page ID mismatch'
            );
        }
        
        // Successfully fetched page details
        return array(
            'success' => true,
            'details' => array(
                'name' => $response_body['name'] ?? '',
                'id' => $response_body['id'] ?? '',
                'category' => $response_body['category'] ?? ''
            )
        );
    }

    /**
     * Get the channel name.
     *
     * @since    1.0.0
     * @return   string   The channel name.
     */
    public function get_name() {
        return __('Facebook Messenger', 'conversaai-pro-wp');
    }

    /**
     * Get the channel identifier.
     *
     * @since    1.0.0
     * @return   string   The channel identifier.
     */
    public function get_id() {
        return 'messenger';
    }

    /**
     * Check if the channel is properly configured and available.
     *
     * @since    1.0.0
     * @return   bool     Whether the channel is available.
     */
    public function is_available() {
        return $this->enabled && 
               !empty($this->settings['page_id']) && 
               !empty($this->settings['app_id']) && 
               !empty($this->settings['access_token']);
    }
}