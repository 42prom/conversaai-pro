<?php
/**
 * Logger utility class.
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
 * Logger utility class.
 *
 * Handles logging for the plugin.
 *
 * @since      1.0.0
 */
class Logger {

    /**
     * Log file path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $log_file    The path to the log file.
     */
    private $log_file;

    /**
     * Whether logging is enabled.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $enabled    Whether logging is enabled.
     */
    private $enabled = false;

    /**
     * Log levels.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $levels    Available log levels.
     */
    private $levels = array(
        'error' => 0,
        'warning' => 1,
        'info' => 2,
        'debug' => 3,
    );

    /**
     * Current log level.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $current_level    Current log level.
     */
    private $current_level = 'error';

    /**
     * Initialize the logger.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/conversaai-pro-logs/conversaai-pro-' . date('Y-m-d') . '.log';
        
        // Create logs directory if it doesn't exist
        $logs_dir = dirname($this->log_file);
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
            
            // Create .htaccess file to protect logs
            $htaccess_file = $logs_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "# Deny access to all files in this directory\n";
                $htaccess_content .= "<Files \"*\">\n";
                $htaccess_content .= "    Require all denied\n";
                $htaccess_content .= "</Files>";
                file_put_contents($htaccess_file, $htaccess_content);
            }
        }
        
        // Check if logging is enabled in settings
        $general_settings = get_option('conversaai_pro_general_settings', array());
        $this->enabled = isset($general_settings['enable_logging']) ? (bool) $general_settings['enable_logging'] : false;
        
        // Set log level from settings (default to 'error' if not set)
        $this->current_level = isset($general_settings['log_level']) ? $general_settings['log_level'] : 'error';
    }

    /**
     * Log an error message.
     *
     * @since    1.0.0
     * @param    string    $message    The message to log.
     * @param    array     $context    Optional. Context data to include.
     */
    public function error($message, $context = array()) {
        $this->log('error', $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @since    1.0.0
     * @param    string    $message    The message to log.
     * @param    array     $context    Optional. Context data to include.
     */
    public function warning($message, $context = array()) {
        $this->log('warning', $message, $context);
    }

    /**
     * Log an info message.
     *
     * @since    1.0.0
     * @param    string    $message    The message to log.
     * @param    array     $context    Optional. Context data to include.
     */
    public function info($message, $context = array()) {
        $this->log('info', $message, $context);
    }

    /**
     * Log a debug message.
     *
     * @since    1.0.0
     * @param    string    $message    The message to log.
     * @param    array     $context    Optional. Context data to include.
     */
    public function debug($message, $context = array()) {
        $this->log('debug', $message, $context);
    }

    /**
     * Log a message.
     *
     * @since    1.0.0
     * @param    string    $level      The log level.
     * @param    string    $message    The message to log.
     * @param    array     $context    Optional. Context data to include.
     */
    private function log($level, $message, $context = array()) {
        // Check if logging is enabled
        if (!$this->enabled) {
            return;
        }
        
        // Check if level is enabled
        if (!isset($this->levels[$level]) || $this->levels[$level] > $this->levels[$this->current_level]) {
            return;
        }
        
        // Format timestamp
        $timestamp = date('Y-m-d H:i:s');
        
        // Format context data
        $context_string = !empty($context) ? ' ' . json_encode($context) : '';
        
        // Format log entry
        $log_entry = "[{$timestamp}] [{$level}] {$message}{$context_string}\n";
        
        // Write to log file
        error_log($log_entry, 3, $this->log_file);
    }

    /**
     * Enable or disable logging.
     *
     * @since    1.0.0
     * @param    bool    $enabled    Whether to enable logging.
     */
    public function set_enabled($enabled) {
        $this->enabled = (bool) $enabled;
    }

    /**
     * Set the log level.
     *
     * @since    1.0.0
     * @param    string    $level    The log level to set.
     */
    public function set_level($level) {
        if (isset($this->levels[$level])) {
            $this->current_level = $level;
        }
    }
}