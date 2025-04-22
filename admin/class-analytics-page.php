<?php
/**
 * Analytics page functionality.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/admin
 */

namespace ConversaAI_Pro_WP\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use ConversaAI_Pro_WP\Core\Analytics_Manager;

/**
 * Analytics page class.
 *
 * Handles the analytics dashboard functionality.
 *
 * @since      1.0.0
 */
class Analytics_Page {

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
     * Analytics manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      \ConversaAI_Pro_WP\Core\Analytics_Manager    $analytics_manager    The analytics manager instance.
     */
    private $analytics_manager;

    /**
     * Date range for analytics.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $date_range    The current date range for analytics.
     */
    protected $date_range;

    /**
     * Date presets for quick selection.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $date_presets    The available date presets.
     */
    protected $date_presets;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->analytics_manager = new Analytics_Manager();
        
        // Initialize date range and presets with default values to prevent notices
        $this->date_range = array(
            'start' => date('Y-m-d', strtotime('-30 days')),
            'end' => date('Y-m-d'),
        );
        
        $this->date_presets = array();
        
        // Add AJAX handlers for analytics page
        add_action('wp_ajax_conversaai_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_conversaai_get_dashboard_widgets', array($this, 'ajax_get_dashboard_widgets'));
        add_action('wp_ajax_conversaai_export_analytics', array($this, 'ajax_export_analytics'));
    }

    /**
     * Display the analytics page.
     *
     * @since    1.0.0
     */
    public function display() {
        // Initialize default date ranges
        $this->prepare_default_date_ranges();
        
        // Load required assets
        $this->enqueue_analytics_assets();
        
        // Get initial summary metrics for quick display
        $summary_metrics = $this->get_summary_metrics();
        
        // Load the view
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'admin/views/analytics-page.php';
    }

    /**
     * Prepare default date ranges for analytics.
     *
     * @since    1.0.0
     * @access   private
     */
    private function prepare_default_date_ranges() {
        // Set default date range (last 30 days)
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-30 days'));
        
        // Make date range available to the view
        $this->date_range = array(
            'start' => $start_date,
            'end' => $end_date,
        );
        
        // Common date presets for quick selection
        $this->date_presets = array(
            'today' => array(
                'label' => __('Today', 'conversaai-pro-wp'),
                'start' => date('Y-m-d'),
                'end' => date('Y-m-d'),
            ),
            'yesterday' => array(
                'label' => __('Yesterday', 'conversaai-pro-wp'),
                'start' => date('Y-m-d', strtotime('-1 day')),
                'end' => date('Y-m-d', strtotime('-1 day')),
            ),
            'last7days' => array(
                'label' => __('Last 7 Days', 'conversaai-pro-wp'),
                'start' => date('Y-m-d', strtotime('-7 days')),
                'end' => date('Y-m-d'),
            ),
            'last30days' => array(
                'label' => __('Last 30 Days', 'conversaai-pro-wp'),
                'start' => date('Y-m-d', strtotime('-30 days')),
                'end' => date('Y-m-d'),
            ),
            'thismonth' => array(
                'label' => __('This Month', 'conversaai-pro-wp'),
                'start' => date('Y-m-01'),
                'end' => date('Y-m-d'),
            ),
            'lastmonth' => array(
                'label' => __('Last Month', 'conversaai-pro-wp'),
                'start' => date('Y-m-01', strtotime('first day of last month')),
                'end' => date('Y-m-t', strtotime('last day of last month')),
            ),
        );
    }

    /**
     * Enqueue assets required for the analytics page.
     *
     * @since    1.0.0
     * @access   private
     */
    private function enqueue_analytics_assets() {
        // Enqueue Chart.js from CDN
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Enqueue data tables for better table functionality
        wp_enqueue_style(
            'datatables-css',
            'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css',
            array(),
            '1.13.4'
        );
        
        wp_enqueue_script(
            'datatables-js',
            'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
            array('jquery'),
            '1.13.4',
            true
        );
        
        // Register our analytics page specific JavaScript
        wp_register_script(
            'conversaai-analytics-js',
            CONVERSAAI_PRO_PLUGIN_URL . 'admin/assets/js/analytics.js',
            array('jquery', 'chartjs', 'datatables-js'),
            $this->version,
            true
        );
        
        // Localize the script with necessary data
        wp_localize_script(
            'conversaai-analytics-js',
            'conversaaiAnalytics',
            array(
                'nonce' => wp_create_nonce('conversaai_analytics_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'dateRange' => $this->date_range,
                'i18n' => array(
                    'loading' => __('Loading data...', 'conversaai-pro-wp'),
                    'noData' => __('No data available for the selected period.', 'conversaai-pro-wp'),
                    'error' => __('Error loading data. Please try again.', 'conversaai-pro-wp'),
                    'conversations' => __('Conversations', 'conversaai-pro-wp'),
                    'messages' => __('Messages', 'conversaai-pro-wp'),
                    'aiResponses' => __('AI Responses', 'conversaai-pro-wp'),
                    'kbResponses' => __('KB Responses', 'conversaai-pro-wp'),
                    'successRate' => __('Success Rate', 'conversaai-pro-wp'),
                )
            )
        );
        
        // Enqueue the script
        wp_enqueue_script('conversaai-analytics-js');
    }

    /**
     * Get summary metrics for initial page load.
     *
     * @since    1.0.0
     * @access   private
     * @return   array    Summary metrics.
     */
    private function get_summary_metrics() {
        // Get a small subset of analytics data for the initial page load
        // This makes the page load faster while the rest loads via AJAX
        $start_date = $this->date_range['start'];
        $end_date = $this->date_range['end'];
        
        try {
            $analytics_data = $this->analytics_manager->get_analytics($start_date, $end_date);
            $success_metrics = $this->analytics_manager->get_conversation_success_metrics();
            
            return array(
                'conversation_count' => $analytics_data['totals']['conversation_count'] ?? 0,
                'message_count' => $analytics_data['totals']['message_count'] ?? 0,
                'success_rate' => $analytics_data['success_rate'] ?? 0,
                'kb_usage_rate' => $analytics_data['kb_usage_rate'] ?? 0,
            );
        } catch (\Exception $e) {
            // Log the error
            error_log('ConversaAI Pro: Error getting summary metrics: ' . $e->getMessage());
            
            // Return empty metrics
            return array(
                'conversation_count' => 0,
                'message_count' => 0,
                'success_rate' => 0,
                'kb_usage_rate' => 0,
            );
        }
    }

    /**
     * AJAX handler for getting analytics data.
     *
     * @since    1.0.0
     */
    public function ajax_get_analytics() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_analytics_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'conversaai-pro-wp'),
                'code' => 'security_check_failed'
            ));
            return;
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to access analytics.', 'conversaai-pro-wp'),
                'code' => 'insufficient_permissions'
            ));
            return;
        }
        
        // Get and validate input with defaults
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');
        $channel = isset($_POST['channel']) ? sanitize_text_field($_POST['channel']) : '';
        
        // Validate dates
        if (!$this->validate_date($start_date) || !$this->validate_date($end_date)) {
            wp_send_json_error(array(
                'message' => __('Invalid date format.', 'conversaai-pro-wp'),
                'code' => 'invalid_date_format'
            ));
            return;
        }
        
        try {
            // Get analytics data
            $analytics_data = $this->analytics_manager->get_analytics($start_date, $end_date, $channel);
            $success_metrics = $this->analytics_manager->get_conversation_success_metrics();
            
            // Send success response
            wp_send_json_success(array(
                'analytics' => $analytics_data,
                'success_metrics' => $success_metrics,
                'dateRange' => array(
                    'start' => $start_date,
                    'end' => $end_date,
                ),
                'channel' => $channel,
            ));
        } catch (\Exception $e) {
            // Log the error
            error_log('ConversaAI Pro: Error getting analytics data: ' . $e->getMessage());
            
            // Send error response
            wp_send_json_error(array(
                'message' => __('Error retrieving analytics data. Please try again.', 'conversaai-pro-wp'),
                'code' => 'data_retrieval_error',
                'details' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler for getting individual dashboard widgets data.
     * This allows loading each chart separately to improve page load performance.
     *
     * @since    1.0.0
     */
    public function ajax_get_dashboard_widgets() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_analytics_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
            return;
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access analytics.', 'conversaai-pro-wp')));
            return;
        }
        
        // Get and validate input
        $widget = isset($_POST['widget']) ? sanitize_text_field($_POST['widget']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');
        $channel = isset($_POST['channel']) ? sanitize_text_field($_POST['channel']) : '';
        
        // Validate dates
        if (!$this->validate_date($start_date) || !$this->validate_date($end_date)) {
            wp_send_json_error(array('message' => __('Invalid date format.', 'conversaai-pro-wp')));
            return;
        }
        
        // Validate widget name
        $valid_widgets = array('conversations_chart', 'sources_chart', 'channels_chart', 'success_distribution', 'trending_queries');
        if (!in_array($widget, $valid_widgets)) {
            wp_send_json_error(array('message' => __('Invalid widget name.', 'conversaai-pro-wp')));
            return;
        }
        
        try {
            // Get analytics data specific to the requested widget
            $data = $this->get_widget_data($widget, $start_date, $end_date, $channel);
            
            wp_send_json_success(array(
                'widget' => $widget,
                'data' => $data,
            ));
        } catch (\Exception $e) {
            // Log the error
            error_log('ConversaAI Pro: Error getting widget data: ' . $e->getMessage());
            
            wp_send_json_error(array(
                'message' => __('Error retrieving widget data. Please try again.', 'conversaai-pro-wp'),
                'details' => $e->getMessage()
            ));
        }
    }

    /**
     * Get data for a specific dashboard widget.
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $widget       The widget identifier.
     * @param    string    $start_date   The start date.
     * @param    string    $end_date     The end date.
     * @param    string    $channel      Optional. Filter by channel.
     * @return   array     Widget specific data.
     */
    private function get_widget_data($widget, $start_date, $end_date, $channel = '') {
        // Get analytics data
        $analytics_data = $this->analytics_manager->get_analytics($start_date, $end_date, $channel);
        $success_metrics = $this->analytics_manager->get_conversation_success_metrics();
        
        // Return relevant data based on widget type
        switch ($widget) {
            case 'conversations_chart':
                // Prepare data for conversations over time chart
                $dates = array_keys($analytics_data['by_date']);
                sort($dates); // Ensure dates are in order
                
                $data = array(
                    'labels' => $dates,
                    'datasets' => array(
                        array(
                            'label' => __('Conversations', 'conversaai-pro-wp'),
                            'data' => array_map(function($date) use ($analytics_data) {
                                return $analytics_data['by_date'][$date]['conversation_count'] ?? 0;
                            }, $dates),
                        ),
                        array(
                            'label' => __('Messages', 'conversaai-pro-wp'),
                            'data' => array_map(function($date) use ($analytics_data) {
                                return $analytics_data['by_date'][$date]['message_count'] ?? 0;
                            }, $dates),
                        ),
                    ),
                );
                break;
                
            case 'sources_chart':
                // Prepare data for response sources chart
                $data = array(
                    'labels' => array(
                        __('Knowledge Base', 'conversaai-pro-wp'),
                        __('AI Responses', 'conversaai-pro-wp'),
                    ),
                    'datasets' => array(
                        array(
                            'data' => array(
                                $analytics_data['totals']['kb_answer_count'] ?? 0,
                                $analytics_data['totals']['ai_request_count'] ?? 0,
                            ),
                        ),
                    ),
                );
                break;
                
            case 'channels_chart':
                // Prepare data for channels chart
                $channels = array_keys($analytics_data['by_channel']);
                
                $data = array(
                    'labels' => array_map(function($channel) {
                        return ucfirst($channel);
                    }, $channels),
                    'datasets' => array(
                        array(
                            'label' => __('Conversations', 'conversaai-pro-wp'),
                            'data' => array_map(function($channel) use ($analytics_data) {
                                return $analytics_data['by_channel'][$channel]['conversation_count'] ?? 0;
                            }, $channels),
                        ),
                    ),
                );
                break;
                
            case 'success_distribution':
                // Prepare data for success distribution chart
                $distribution = $success_metrics['distribution'] ?? array();
                
                $data = array(
                    'labels' => array(
                        __('Excellent', 'conversaai-pro-wp'),
                        __('Good', 'conversaai-pro-wp'),
                        __('Average', 'conversaai-pro-wp'),
                        __('Poor', 'conversaai-pro-wp'),
                        __('Very Poor', 'conversaai-pro-wp'),
                    ),
                    'datasets' => array(
                        array(
                            'data' => array(
                                $distribution['excellent'] ?? 0,
                                $distribution['good'] ?? 0,
                                $distribution['average'] ?? 0,
                                $distribution['poor'] ?? 0,
                                $distribution['very_poor'] ?? 0,
                            ),
                        ),
                    ),
                );
                break;
                
            case 'trending_queries':
                // Prepare data for trending queries
                $data = $analytics_data['trending_queries'] ?? array();
                break;
                
            default:
                $data = array();
        }
        
        return $data;
    }

    /**
     * AJAX handler for exporting analytics data.
     *
     * @since    1.0.0
     */
    public function ajax_export_analytics() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'conversaai_analytics_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'conversaai-pro-wp')));
            return;
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to export analytics.', 'conversaai-pro-wp')));
            return;
        }
        
        // Get and validate input
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');
        $channel = isset($_POST['channel']) ? sanitize_text_field($_POST['channel']) : '';
        
        // Validate dates
        if (!$this->validate_date($start_date) || !$this->validate_date($end_date)) {
            wp_send_json_error(array('message' => __('Invalid date format.', 'conversaai-pro-wp')));
            return;
        }
        
        try {
            // Get analytics data
            $analytics_data = $this->analytics_manager->get_analytics($start_date, $end_date, $channel);
            
            // Generate export data based on format
            $export_data = $this->generate_export_data($analytics_data, $format);
            
            // Generate filename
            $date_range = $start_date . '_to_' . $end_date;
            $channel_str = !empty($channel) ? '_' . $channel : '';
            $filename = 'conversaai_analytics_' . $date_range . $channel_str . '.' . $format;
            
            wp_send_json_success(array(
                'data' => $export_data,
                'filename' => $filename,
                'format' => $format,
            ));
        } catch (\Exception $e) {
            // Log the error
            error_log('ConversaAI Pro: Error exporting analytics data: ' . $e->getMessage());
            
            wp_send_json_error(array(
                'message' => __('Error exporting analytics data. Please try again.', 'conversaai-pro-wp'),
                'details' => $e->getMessage()
            ));
        }
    }

    /**
     * Generate export data in the specified format.
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $analytics_data   The analytics data.
     * @param    string    $format           The export format (csv or json).
     * @return   string    The formatted export data.
     */
    private function generate_export_data($analytics_data, $format = 'csv') {
        if ($format === 'json') {
            return json_encode($analytics_data);
        }
        
        // CSV format
        $csv_data = array();
        
        // Add headers
        $csv_data[] = array(
            'Date',
            'Channel',
            'Conversations',
            'Messages',
            'AI Responses',
            'KB Responses',
            'Successful Conversations'
        );
        
        // Add daily data
        foreach ($analytics_data['by_date'] as $date => $data) {
            foreach ($analytics_data['by_channel'] as $channel => $channel_data) {
                // Skip if this channel has no data for this date
                if (!isset($analytics_data['by_date'][$date][$channel])) {
                    continue;
                }
                
                $csv_data[] = array(
                    $date,
                    $channel,
                    $data['conversation_count'] ?? 0,
                    $data['message_count'] ?? 0,
                    $data['ai_request_count'] ?? 0,
                    $data['kb_answer_count'] ?? 0,
                    $data['successful_conversation_count'] ?? 0
                );
            }
        }
        
        // Convert to CSV string
        $csv_string = '';
        foreach ($csv_data as $row) {
            $csv_string .= implode(',', $row) . "\n";
        }
        
        return $csv_string;
    }

    /**
     * Validate a date string.
     *
     * @since    1.0.0
     * @param    string    $date    The date string to validate.
     * @return   bool      Whether the date is valid.
     */
    private function validate_date($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}