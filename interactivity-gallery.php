<?php
/**
 * Plugin Name: Interactivity Gallery
 * Plugin URI: https://wbcomdesigns.com/interactivity-gallery
 * Description: A modern gallery plugin that provides both a shortcode and block editor support to display all attached media to a post with lightbox functionality and pagination using the WordPress Interactivity API.
 * Version: 1.0.0
 * Author: vapvarun
 * Author URI: https://wbcomdesigns.com
 * Text Domain: interactivity-gallery
 * Requires at least: 6.5
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enable debugging
define('IG_DEBUG', true);

/**
 * Debug logging function
 */
function ig_debug_log($message) {
    if (defined('IG_DEBUG') && IG_DEBUG) {
        error_log('[Interactivity Gallery] ' . $message);
    }
}

// Log plugin initialization
ig_debug_log('Interactivity Gallery plugin initializing...');

// Define plugin constants
define('IG_PLUGIN_FILE', __FILE__);
define('IG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IG_PLUGIN_VERSION', '1.0.0');

// Log plugin paths
ig_debug_log('Plugin path: ' . IG_PLUGIN_DIR);
ig_debug_log('Plugin URL: ' . IG_PLUGIN_URL);

// Include required files
require_once IG_PLUGIN_DIR . 'includes/class-interactivity-gallery.php';
require_once IG_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once IG_PLUGIN_DIR . 'includes/class-block.php';

/**
 * Initialize plugin
 */
function interactivity_gallery_init() {
    // Initialize main class
    $gallery = Interactivity_Gallery::get_instance();
    
    // Initialize REST API
    $rest_api = Interactivity_Gallery_REST_API::get_instance();
    
    // Initialize Block
    $block = Interactivity_Gallery_Block::get_instance();
    
    // Log successful initialization
    ig_debug_log('Interactivity Gallery plugin initialized successfully');
}
add_action('plugins_loaded', 'interactivity_gallery_init');

/**
 * Add activation hook to log plugin activation
 */
function interactivity_gallery_activate() {
    ig_debug_log('Interactivity Gallery plugin activated');
}
register_activation_hook(__FILE__, 'interactivity_gallery_activate');

/**
 * Add deactivation hook
 */
function interactivity_gallery_deactivate() {
    ig_debug_log('Interactivity Gallery plugin deactivated');
}
register_deactivation_hook(__FILE__, 'interactivity_gallery_deactivate');

/**
 * Register custom debug endpoint for testing
 */
function interactivity_gallery_add_debug_endpoint() {
    if (defined('IG_DEBUG') && IG_DEBUG) {
        add_action('wp_ajax_interactivity_gallery_debug', 'interactivity_gallery_debug_callback');
        add_action('wp_ajax_nopriv_interactivity_gallery_debug', 'interactivity_gallery_debug_callback');
    }
}
add_action('init', 'interactivity_gallery_add_debug_endpoint');

/**
 * Debug endpoint callback
 */
function interactivity_gallery_debug_callback() {
    // Security check
    if (!defined('IG_DEBUG') || !IG_DEBUG) {
        wp_die('Debug mode is not enabled.');
    }
    
    $response = array(
        'status' => 'success',
        'message' => 'Debug mode enabled',
        'plugin_dir' => IG_PLUGIN_DIR,
        'plugin_url' => IG_PLUGIN_URL,
        'plugin_version' => IG_PLUGIN_VERSION,
        'wordpress_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
    );
    
    // Check for Interactivity API support
    $response['interactivity_api_supported'] = function_exists('wp_interactivity_registry');
    
    // Log the debug request
    ig_debug_log('Debug endpoint accessed');
    
    wp_send_json($response);
    die();
}