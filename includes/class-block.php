<?php
/**
 * Block class for Interactivity Gallery
 */
class Interactivity_Gallery_Block {
    
    /**
     * Class instance
     */
    private static $instance = null;
    
    /**
     * Get class instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register block on init
        add_action('init', array($this, 'register_block'));
    }
    
    /**
     * Register the block
     */
    public function register_block() {
        // Skip if block editor is not available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Register block editor assets
        wp_register_style(
            'interactivity-gallery-editor-style',
            IG_PLUGIN_URL . 'assets/css/interactivity-gallery-editor.css',
            array(),
            IG_PLUGIN_VERSION
        );
        
        wp_register_script(
            'interactivity-gallery-editor-script',
            IG_PLUGIN_URL . 'assets/js/block.js',
            array(
                'wp-blocks',
                'wp-element',
                'wp-block-editor',
                'wp-components',
                'wp-i18n',
                'wp-server-side-render'
            ),
            IG_PLUGIN_VERSION,
            true
        );
        
        // Register the block
        register_block_type('interactivity-gallery/gallery', array(
            'editor_script' => 'interactivity-gallery-editor-script',
            'editor_style' => 'interactivity-gallery-editor-style',
            'render_callback' => array($this, 'render_block'),
            'attributes' => array(
                'postId' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'perPage' => array(
                    'type' => 'number',
                    'default' => 12
                ),
                'columns' => array(
                    'type' => 'number',
                    'default' => 3
                )
            ),
        ));
    }
    
    /**
     * Render the block (callback function)
     */
    public function render_block($attributes) {
        // Access the Interactivity_Gallery class instance
        $gallery = Interactivity_Gallery::get_instance();
        
        $args = array(
            'post_id' => isset($attributes['postId']) && $attributes['postId'] ? $attributes['postId'] : get_the_ID(),
            'per_page' => isset($attributes['perPage']) ? $attributes['perPage'] : 12,
            'columns' => isset($attributes['columns']) ? $attributes['columns'] : 3,
        );
        
        // Use the render_gallery method from the Interactivity_Gallery class
        return $gallery->render_gallery($args);
    }
}