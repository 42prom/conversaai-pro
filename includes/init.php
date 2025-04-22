<?php
/**
 * Initialize the plugin.
 *
 * @package    ConversaAI_Pro_WP
 * @subpackage ConversaAI_Pro_WP/includes
 */

namespace ConversaAI_Pro_WP;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Initialize the plugin.
 *
 * This function is run during plugin startup and initializes
 * the main plugin functionality.
 */
function initialize_plugin() {
    // Start the plugin
    $plugin = new Plugin();
    $plugin->initialize(); // Call initialize() first
    $plugin->run();

    // Initialize content indexer
    require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/wp/class-content-indexer.php';
    $content_indexer = new \ConversaAI_Pro_WP\Integrations\WP\Content_Indexer();

    // Initialize WooCommerce indexer if WooCommerce is active
    if (class_exists('WooCommerce')) {
        require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/integrations/wp/class-woocommerce-indexer.php';
        $woocommerce_indexer = new \ConversaAI_Pro_WP\Integrations\WP\WooCommerce_Indexer();
    }
    
    // Initialize messaging channels manager
    if (defined('CONVERSAAI_PRO_ENABLE_MESSENGER') && CONVERSAAI_PRO_ENABLE_MESSENGER || 
        defined('CONVERSAAI_PRO_ENABLE_WHATSAPP') && CONVERSAAI_PRO_ENABLE_WHATSAPP || 
        defined('CONVERSAAI_PRO_ENABLE_INSTAGRAM') && CONVERSAAI_PRO_ENABLE_INSTAGRAM) {
        $channel_manager = \ConversaAI_Pro_WP\Integrations\Messaging\Channel_Manager::get_instance();
    }

    return $plugin;
}

// Run the initialization
$GLOBALS['conversaai_pro'] = initialize_plugin();