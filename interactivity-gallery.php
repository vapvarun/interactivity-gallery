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

// Define plugin constants
define('IG_PLUGIN_FILE', __FILE__);
define('IG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IG_PLUGIN_VERSION', '1.0.0');

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
}
add_action('plugins_loaded', 'interactivity_gallery_init');