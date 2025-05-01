<?php
/**
 * Main plugin class
 */
class Interactivity_Gallery {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
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
        // Register shortcode
        add_shortcode('interactivity_gallery', array($this, 'gallery_shortcode'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue if shortcode is present or block is used
        global $post;
        
        if (!is_a($post, 'WP_Post')) {
            return;
        }
        
        $has_block = false;
        if (function_exists('has_block') && has_block('interactivity-gallery/gallery', $post)) {
            $has_block = true;
        }
        
        if (!$has_block && !has_shortcode($post->post_content, 'interactivity_gallery')) {
            return;
        }
        
        // Enqueue the Interactivity API
        wp_enqueue_script('wp-interactivity');
        
        // Enqueue CSS
        wp_enqueue_style(
            'interactivity-gallery-styles',
            IG_PLUGIN_URL . 'assets/css/interactivity-gallery.css',
            array(),
            IG_PLUGIN_VERSION
        );
        
        // Enqueue JavaScript - FIXED
        wp_register_script(
            'interactivity-gallery-script',
            IG_PLUGIN_URL . 'assets/js/interactivity-gallery.js',
            array('wp-interactivity'),
            IG_PLUGIN_VERSION,
            true
        );
        
        // Make sure the script is properly registered before enqueueing
        wp_enqueue_script('interactivity-gallery-script');
    }
    
    /**
     * Shortcode callback function
     */
    public function gallery_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'post_id' => get_the_ID(),
                'per_page' => 12,
                'columns' => 3,
            ),
            $atts,
            'interactivity_gallery'
        );
        
        return $this->render_gallery($atts);
    }
    
    /**
     * Common rendering function for both shortcode and block
     * Public so it can be accessed by the block renderer
     */
    public function render_gallery($args) {
        // Ensure numeric values
        $args['post_id'] = intval($args['post_id']);
        $args['per_page'] = intval($args['per_page']);
        $args['columns'] = intval($args['columns']);
        
        // Generate a unique namespace for this gallery instance
        $namespace = 'interactivityGallery' . uniqid();
        
        // Start output buffering
        ob_start();
        
        // Output data store initialization using wp-context
        echo '<script type="application/json" data-wp-context="' . esc_attr($namespace) . '">';
        echo json_encode(array(
            'postId' => $args['post_id'],
            'perPage' => $args['per_page'],
            'columns' => $args['columns'],
            'currentPage' => 1,
            'totalPages' => 0,
            'isLoading' => true,
            'hasError' => false,
            'media' => array(),
            'activeMediaIndex' => -1,
            'isLightboxOpen' => false,
        ));
        echo '</script>';
        
        // Output the gallery container
        ?>
        <div
            class="interactivity-gallery-container"
            data-wp-interactive="<?php echo esc_attr($namespace); ?>"
            data-wp-context="<?php echo esc_attr($namespace); ?>"
            data-wp-on--load="actions.loadMedia"
            data-wp-bind--data-columns="state.columns"
            data-wp-class--is-loading="state.isLoading"
            data-wp-class--has-error="state.hasError"
        >
            <!-- Loading state -->
            <div 
                class="interactivity-gallery-loading" 
                data-wp-bind--hidden="!state.isLoading"
            >
                <span>Loading media...</span>
            </div>
            
            <!-- Error state -->
            <div 
                class="interactivity-gallery-error" 
                data-wp-bind--hidden="!state.hasError || state.isLoading"
            >
                <span>Failed to load media items.</span>
            </div>
            
            <!-- Gallery items container -->
            <div 
                class="interactivity-gallery-items" 
                data-wp-bind--hidden="state.isLoading || state.hasError || state.media.length === 0"
            >
                <template data-wp-foreach--item="state.media" data-wp-foreach-key="index">
                    <div class="interactivity-gallery-item">
                        <!-- Media content based on type -->
                        <div data-wp-bind--hidden="!item.type.includes('image')">
                            <a 
                                href="#" 
                                data-wp-on--click="actions.openLightbox"
                                data-wp-on--click-data="{ index }"
                            >
                                <img 
                                    data-wp-bind--src="item.thumbnail" 
                                    data-wp-bind--alt="item.alt || item.title" 
                                />
                            </a>
                        </div>
                        
                        <div data-wp-bind--hidden="!item.type.includes('video')" class="media-video-wrapper">
                            <video controls>
                                <source data-wp-bind--src="item.url" data-wp-bind--type="item.type">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        
                        <div data-wp-bind--hidden="!item.type.includes('audio')" class="media-audio-wrapper">
                            <audio controls>
                                <source data-wp-bind--src="item.url" data-wp-bind--type="item.type">
                                Your browser does not support the audio tag.
                            </audio>
                        </div>
                        
                        <div 
                            data-wp-bind--hidden="item.type.includes('image') || item.type.includes('video') || item.type.includes('audio')" 
                            class="media-file-wrapper"
                        >
                            <a data-wp-bind--href="item.url" target="_blank">
                                <span class="media-file-icon"></span>
                                <span class="media-file-name" data-wp-text="item.title"></span>
                            </a>
                        </div>
                        
                        <!-- Media title -->
                        <div class="interactivity-gallery-item-title" data-wp-text="item.title"></div>
                    </div>
                </template>
            </div>
            
            <!-- Empty state -->
            <div 
                class="interactivity-gallery-empty"
                data-wp-bind--hidden="state.isLoading || state.hasError || state.media.length > 0"
            >
                <span>No media items found.</span>
            </div>
            
            <!-- Pagination -->
            <div 
                class="interactivity-gallery-pagination"
                data-wp-bind--hidden="state.isLoading || state.hasError || state.totalPages <= 1"
            >
                <!-- Previous button -->
                <a 
                    href="#" 
                    class="page-numbers prev" 
                    data-wp-on--click="actions.goToPage"
                    data-wp-on--click-data="{ page: state.currentPage - 1 }"
                    data-wp-bind--hidden="state.currentPage <= 1"
                >&laquo;</a>
                
                <!-- Page numbers -->
                <template data-wp-foreach--pageNum="Array.from({ length: state.totalPages }, (_, i) => i + 1)" data-wp-foreach-key="i">
                    <a 
                        href="#" 
                        class="page-numbers"
                        data-wp-bind--class:current="state.currentPage === pageNum"
                        data-wp-on--click="actions.goToPage"
                        data-wp-on--click-data="{ page: pageNum }"
                        data-wp-text="pageNum"
                    ></a>
                </template>
                
                <!-- Next button -->
                <a 
                    href="#" 
                    class="page-numbers next" 
                    data-wp-on--click="actions.goToPage"
                    data-wp-on--click-data="{ page: state.currentPage + 1 }"
                    data-wp-bind--hidden="state.currentPage >= state.totalPages"
                >&raquo;</a>
            </div>
            
            <!-- Lightbox -->
            <div 
                class="interactivity-gallery-lightbox"
                data-wp-bind--hidden="!state.isLightboxOpen"
                data-wp-on--click="actions.closeLightbox"
            >
                <div 
                    class="interactivity-gallery-lightbox-content"
                    data-wp-on--click="actions.stopPropagation"
                >
                    <div class="interactivity-gallery-lightbox-header">
                        <span 
                            class="interactivity-gallery-lightbox-title"
                            data-wp-bind--hidden="state.activeMediaIndex === -1"
                            data-wp-text="state.activeMediaIndex !== -1 ? state.media[state.activeMediaIndex].title : ''"
                        ></span>
                        <button 
                            class="interactivity-gallery-lightbox-close"
                            data-wp-on--click="actions.closeLightbox"
                        >&times;</button>
                    </div>
                    
                    <div class="interactivity-gallery-lightbox-body">
                        <button 
                            class="interactivity-gallery-lightbox-prev"
                            data-wp-on--click="actions.prevMedia"
                            data-wp-bind--hidden="state.activeMediaIndex <= 0"
                        >&lsaquo;</button>
                        
                        <div class="interactivity-gallery-lightbox-image-container">
                            <img 
                                class="interactivity-gallery-lightbox-image"
                                data-wp-bind--hidden="state.activeMediaIndex === -1"
                                data-wp-bind--src="state.activeMediaIndex !== -1 ? state.media[state.activeMediaIndex].url : ''"
                                data-wp-bind--alt="state.activeMediaIndex !== -1 ? (state.media[state.activeMediaIndex].alt || state.media[state.activeMediaIndex].title) : ''"
                            />
                        </div>
                        
                        <button 
                            class="interactivity-gallery-lightbox-next"
                            data-wp-on--click="actions.nextMedia"
                            data-wp-bind--hidden="state.activeMediaIndex >= state.media.length - 1"
                        >&rsaquo;</button>
                    </div>
                    
                    <div class="interactivity-gallery-lightbox-footer">
                        <span 
                            class="interactivity-gallery-lightbox-count"
                            data-wp-bind--hidden="state.activeMediaIndex === -1"
                            data-wp-text="(state.activeMediaIndex + 1) + ' of ' + state.media.length"
                        ></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
}