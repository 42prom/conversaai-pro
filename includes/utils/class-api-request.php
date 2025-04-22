<?php
/**
 * API Request utility class.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes/utils
 */

namespace ConversaAI_Pro_WP\Utils;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * API Request utility class.
 *
 * Handles HTTP requests to external APIs.
 *
 * @since      1.0.0
 */
class API_Request {

    /**
     * Default request timeout.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $timeout    The request timeout in seconds.
     */
    private $timeout = 45;

    /**
     * Make a GET request.
     *
     * @since    1.0.0
     * @param    string    $url       The URL to request.
     * @param    array     $headers   Optional. Headers to include in the request.
     * @return   array|WP_Error    The response or WP_Error on failure.
     */
    public function get($url, $headers = array()) {
        $args = array(
            'timeout' => $this->timeout,
            'headers' => $headers,
        );
        
        return wp_remote_get($url, $args);
    }

    /**
     * Make a POST request.
     *
     * @since    1.0.0
     * @param    string    $url       The URL to request.
     * @param    array     $data      The data to send.
     * @param    array     $headers   Optional. Headers to include in the request.
     * @return   array|WP_Error    The response or WP_Error on failure.
     */
    public function post($url, $data, $headers = array()) {
        $args = array(
            'timeout' => $this->timeout,
            'headers' => $headers,
            'body' => is_array($data) ? json_encode($data) : $data,
        );
        
        return wp_remote_post($url, $args);
    }

    /**
     * Make a PUT request.
     *
     * @since    1.0.0
     * @param    string    $url       The URL to request.
     * @param    array     $data      The data to send.
     * @param    array     $headers   Optional. Headers to include in the request.
     * @return   array|WP_Error    The response or WP_Error on failure.
     */
    public function put($url, $data, $headers = array()) {
        $args = array(
            'timeout' => $this->timeout,
            'headers' => $headers,
            'body' => is_array($data) ? json_encode($data) : $data,
            'method' => 'PUT',
        );
        
        return wp_remote_request($url, $args);
    }

    /**
     * Make a DELETE request.
     *
     * @since    1.0.0
     * @param    string    $url       The URL to request.
     * @param    array     $headers   Optional. Headers to include in the request.
     * @return   array|WP_Error    The response or WP_Error on failure.
     */
    public function delete($url, $headers = array()) {
        $args = array(
            'timeout' => $this->timeout,
            'headers' => $headers,
            'method' => 'DELETE',
        );
        
        return wp_remote_request($url, $args);
    }

    /**
     * Set the request timeout.
     *
     * @since    1.0.0
     * @param    int    $timeout    The timeout in seconds.
     */
    public function set_timeout($timeout) {
        $this->timeout = (int) $timeout;
    }
}