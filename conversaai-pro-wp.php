<?php
/**
 * ConversaAI Pro for WP
 *
 * @package           ConversaAI_Pro_WP
 * @author            Mikheili Nakeuri
 * @copyright         2025 Mikheili Nakeuri
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       ConversaAI Pro for WP
 * Plugin URI:        https://github.com/42prom
 * Description:       Comprehensive AI-powered communication solution with multi-channel support, self-learning capabilities, and deep WordPress integration.
 * Version:           1.0.0
 * Author:            Mikheili Nakeuri
 * Author URI:        https://github.com/42prom
 * Text Domain:       conversaai-pro-wp
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('CONVERSAAI_PRO_VERSION', '1.0.0');
define('CONVERSAAI_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONVERSAAI_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONVERSAAI_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include constants file
require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/constants.php';

// Include the core plugin class and initialization
require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/class-loader.php';
require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/class-plugin.php';
require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/init.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'conversaai_pro_activate');
register_deactivation_hook(__FILE__, 'conversaai_pro_deactivate');

// Define activation and deactivation functions
function conversaai_pro_activate() {
    require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/activation.php';
    ConversaAI_Pro_WP\Activation::activate();
}

function conversaai_pro_deactivate() {
    require_once CONVERSAAI_PRO_PLUGIN_DIR . 'includes/deactivation.php';
    ConversaAI_Pro_WP\Deactivation::deactivate();
}

// Initialize the plugin
$conversaai_pro = new ConversaAI_Pro_WP\Plugin();
$conversaai_pro->initialize();