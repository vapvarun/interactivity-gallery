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
        
        // Add footer script for emergency fix
        add_action('wp_footer', array($this, 'add_emergency_script'));
        
        // Add support for cache prevention in lightbox images
        add_filter('wp_get_attachment_image_src', array($this, 'add_cache_prevention_to_images'), 10, 4);
    }
    
    /**
     * Add cache prevention to images
     */
    public function add_cache_prevention_to_images($image, $attachment_id, $size, $icon) {
        if (is_array($image) && !empty($image[0])) {
            // Add a timestamp to prevent caching
            $image[0] = add_query_arg('_nocache', time(), $image[0]);
        }
        return $image;
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
        
        // Enqueue the CSS first
        wp_enqueue_style(
            'interactivity-gallery-styles',
            IG_PLUGIN_URL . 'assets/css/interactivity-gallery.css',
            array(),
            IG_PLUGIN_VERSION . '.' . time() // Add time to version to prevent caching
        );
        
        // Enqueue the Interactivity API
        if (function_exists('wp_enqueue_interactivity_api')) {
            // WP 6.5+ function
            wp_enqueue_interactivity_api();
        } else {
            // Fallback
            wp_enqueue_script('wp-interactivity');
        }
        
        // Add our JS code
        $js_content = $this->get_gallery_js();
        $handle = 'interactivity-gallery-script-' . wp_unique_id();
        wp_add_inline_script('wp-interactivity', $js_content, 'after');
        
        // Additionally, add the lightbox fix script
        $lightbox_fix_file = IG_PLUGIN_DIR . 'assets/js/lightbox-fix.js';
        if (file_exists($lightbox_fix_file)) {
            wp_enqueue_script(
                'interactivity-gallery-lightbox-fix',
                IG_PLUGIN_URL . 'assets/js/lightbox-fix.js',
                array('wp-interactivity'),
                IG_PLUGIN_VERSION . '.' . time(),
                true
            );
        }
    }
    
    /**
     * Add emergency fallback script in footer
     */
    public function add_emergency_script() {
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
        
        // Only add the inline script if the separate file wasn't loaded
        $lightbox_fix_file = IG_PLUGIN_DIR . 'assets/js/lightbox-fix.js';
        if (!file_exists($lightbox_fix_file)) {
            ?>
            <script>
            // Emergency Lightbox Fix
            (function() {
                document.addEventListener('DOMContentLoaded', function() {
                    // Find all galleries
                    document.querySelectorAll('.interactivity-gallery-container').forEach(function(gallery) {
                        const namespace = gallery.getAttribute('data-wp-interactive');
                        if (!namespace) return;
                        
                        const lightbox = gallery.querySelector('.interactivity-gallery-lightbox');
                        const lightboxImage = gallery.querySelector('.interactivity-gallery-lightbox-image');
                        if (!lightbox || !lightboxImage) return;
                        
                        // Make sure lightbox is visible when it should be
                        setInterval(function() {
                            if (wp.interactivity && wp.interactivity.state && 
                                wp.interactivity.state[namespace] && 
                                wp.interactivity.state[namespace].isLightboxOpen) {
                                
                                if (lightbox.hidden || lightbox.hasAttribute('hidden') || 
                                    window.getComputedStyle(lightbox).display === 'none') {
                                    
                                    lightbox.hidden = false;
                                    lightbox.removeAttribute('hidden');
                                    lightbox.style.cssText = `
                                        position: fixed !important;
                                        top: 0 !important;
                                        left: 0 !important;
                                        right: 0 !important;
                                        bottom: 0 !important;
                                        background-color: rgba(0, 0, 0, 0.9) !important;
                                        z-index: 9999999 !important;
                                        display: flex !important;
                                        align-items: center !important;
                                        justify-content: center !important;
                                        visibility: visible !important;
                                        opacity: 1 !important;
                                    `;
                                }
                            }
                        }, 500);
                        
                        // Add click handlers
                        gallery.querySelectorAll('.interactivity-gallery-item a').forEach(function(link) {
                            link.addEventListener('click', function() {
                                setTimeout(function() {
                                    if (wp.interactivity && wp.interactivity.state && 
                                        wp.interactivity.state[namespace] && 
                                        wp.interactivity.state[namespace].isLightboxOpen) {
                                        
                                        lightbox.hidden = false;
                                        lightbox.removeAttribute('hidden');
                                        lightbox.style.display = 'flex';
                                    }
                                }, 50);
                            });
                        });
                    });
                });
            })();
            </script>
            <?php
        }
    }
    
    /**
     * Get the gallery JavaScript code
     */
    private function get_gallery_js() {
        // Try to read the JS file
        $js_file = IG_PLUGIN_DIR . 'assets/js/interactivity-gallery.js';
        
        if (file_exists($js_file)) {
            return file_get_contents($js_file);
        } else {
            // Use fallback JS in case the file doesn't exist
            return "console.log('[IG] Using fallback JS');";
        }
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
                'lightbox' => true,
            ),
            $atts,
            'interactivity_gallery'
        );
        
        // Convert string 'true'/'false' to boolean
        $atts['lightbox'] = filter_var($atts['lightbox'], FILTER_VALIDATE_BOOLEAN);
        
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
        $args['lightbox'] = filter_var($args['lightbox'], FILTER_VALIDATE_BOOLEAN);
        
        // Verify that the post exists
        if (!get_post($args['post_id'])) {
            return '<p class="interactivity-gallery-error">Error: Post ID ' . esc_html($args['post_id']) . ' does not exist.</p>';
        }
        
        // Check if the post has attachments using a direct query for verification
        $attachment_count = $this->count_post_attachments($args['post_id']);
        
        if ($attachment_count == 0) {
            return '<p class="interactivity-gallery-error">No media attachments found for post ID ' . esc_html($args['post_id']) . '.</p>';
        }
        
        // Generate a unique namespace for this gallery instance
        $namespace = 'interactivityGallery' . uniqid();
        
        // Start output buffering
        ob_start();
        
        // Add HTML comment for debugging
        echo '<!-- Interactivity Gallery | Post ID: ' . esc_html($args['post_id']) . ' | Attachments: ' . esc_html($attachment_count) . ' -->';
        
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
            // New data structures for improved lightbox functionality
            'mediaMap' => (object)array(), // Empty object for JSON
            'lightboxItems' => array(),
            'currentImageIndex' => -1, // Index in the lightboxItems array
            'isLightboxOpen' => false, // Don't open lightbox initially
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
                        <div data-wp-bind--hidden="!item.type || !item.type.includes('image')">
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
                        
                        <div data-wp-bind--hidden="!item.type || !item.type.includes('video')" class="media-video-wrapper">
                            <video controls>
                                <source data-wp-bind--src="item.url" data-wp-bind--type="item.type">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        
                        <div data-wp-bind--hidden="!item.type || !item.type.includes('audio')" class="media-audio-wrapper">
                            <audio controls>
                                <source data-wp-bind--src="item.url" data-wp-bind--type="item.type">
                                Your browser does not support the audio tag.
                            </audio>
                        </div>
                        
                        <div 
                            data-wp-bind--hidden="!item.type || item.type.includes('image') || item.type.includes('video') || item.type.includes('audio')" 
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
            
            <!-- Improved Lightbox Implementation -->
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
                            data-wp-text="state.currentImageIndex >= 0 && state.lightboxItems.length > 0 ? state.lightboxItems[state.currentImageIndex].title : ''"
                        ></span>
                        <button 
                            class="interactivity-gallery-lightbox-close"
                            data-wp-on--click="actions.closeLightbox"
                        >&times;</button>
                    </div>
                    
                    <div class="interactivity-gallery-lightbox-body">
                        <button 
                            class="interactivity-gallery-lightbox-prev"
                            data-wp-on--click="actions.prevImage"
                            data-wp-bind--hidden="state.currentImageIndex <= 0"
                        >&lsaquo;</button>
                        
                        <div class="interactivity-gallery-lightbox-image-container">
                            <!-- The lightbox image -->
                            <img 
                                class="interactivity-gallery-lightbox-image"
                                data-wp-bind--src="state.currentImageIndex >= 0 && state.lightboxItems.length > 0 ? state.lightboxItems[state.currentImageIndex].url : ''"
                                data-wp-bind--alt="state.currentImageIndex >= 0 && state.lightboxItems.length > 0 ? state.lightboxItems[state.currentImageIndex].alt : ''"
                            />
                        </div>
                        
                        <button 
                            class="interactivity-gallery-lightbox-next"
                            data-wp-on--click="actions.nextImage"
                            data-wp-bind--hidden="state.currentImageIndex < 0 || state.currentImageIndex >= state.lightboxItems.length - 1"
                        >&rsaquo;</button>
                    </div>
                    
                    <div class="interactivity-gallery-lightbox-footer">
                        <span 
                            class="interactivity-gallery-lightbox-count"
                            data-wp-bind--hidden="state.currentImageIndex < 0 || state.lightboxItems.length === 0"
                            data-wp-text="state.currentImageIndex >= 0 && state.lightboxItems.length > 0 ? ((state.currentImageIndex + 1) + ' of ' + state.lightboxItems.length) : ''"
                        ></span>
                    </div>
                </div>
            </div>
            
            <!-- Critical inline styles with !important to override any theme styles -->
            <style>
            /* Critical lightbox styles with !important to ensure visibility */
            .interactivity-gallery-lightbox {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background-color: rgba(0, 0, 0, 0.9) !important;
                z-index: 99999 !important; /* Extremely high z-index */
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            /* When hidden attribute is present */
            .interactivity-gallery-lightbox[hidden] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
            }
            
            .interactivity-gallery-lightbox-content {
                width: 90% !important;
                max-width: 1000px !important;
                background-color: #1a1a1a !important;
                border-radius: 4px !important;
                display: flex !important;
                flex-direction: column !important;
                max-height: 90vh !important;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.5) !important;
            }
            
            .interactivity-gallery-lightbox-image-container {
                width: 100% !important;
                height: 70vh !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 20px !important;
                box-sizing: border-box !important;
                position: relative !important;
            }
            
            .interactivity-gallery-lightbox-image {
                max-width: 100% !important;
                max-height: 100% !important;
                object-fit: contain !important;
                display: block !important;
            }
            
            .interactivity-gallery-lightbox-close {
                background: none !important;
                border: none !important;
                color: #fff !important;
                font-size: 24px !important;
                cursor: pointer !important;
                padding: 0 !important;
                margin: 0 !important;
                line-height: 1 !important;
            }
            
            .interactivity-gallery-lightbox-prev,
            .interactivity-gallery-lightbox-next {
                position: absolute !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                background: rgba(0, 0, 0, 0.5) !important;
                border: none !important;
                color: #fff !important;
                font-size: 36px !important;
                cursor: pointer !important;
                padding: 10px !important;
                z-index: 2 !important;
                width: 50px !important;
                height: 50px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 50% !important;
            }
            
            .interactivity-gallery-lightbox-prev {
                left: 10px !important;
            }
            
            .interactivity-gallery-lightbox-next {
                right: 10px !important;
            }
            </style>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Count attachments for a post
     */
    private function count_post_attachments($post_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'attachment' AND post_status = 'inherit'",
            $post_id
        ));
        
        return $count ? intval($count) : 0;
    }
}