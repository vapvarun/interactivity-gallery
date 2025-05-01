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
        
        // Enqueue the CSS first
        wp_enqueue_style(
            'interactivity-gallery-styles',
            IG_PLUGIN_URL . 'assets/css/interactivity-gallery.css',
            array(),
            IG_PLUGIN_VERSION
        );
        
        // Enqueue the Interactivity API
        wp_enqueue_script('wp-interactivity');
        
        // Instead of using a file reference, we'll directly embed the JS code
        $js_content = $this->get_gallery_js();
        wp_add_inline_script('wp-interactivity', $js_content);
    }
    
    /**
     * Get the gallery JavaScript code
     */
    private function get_gallery_js() {
        // You can either include the file or paste its content here
        $js_file = IG_PLUGIN_DIR . 'assets/js/interactivity-gallery.js';
        
        if (file_exists($js_file)) {
            return file_get_contents($js_file);
        } else {
            // Fallback JS in case the file doesn't exist
            return "
            /**
             * Interactivity Gallery - Interactive API Implementation
             */
            wp.interactivity.init({
                context: {
                    interactivityGallery: {
                        state: {
                            // State is initialized from the data-wp-context attribute
                        },
                        actions: {
                            loadMedia: async ({ state, event }) => {
                                state.isLoading = true;
                                state.hasError = false;
                                
                                try {
                                    console.log(`Loading media from: /wp-json/interactivity-gallery/v1/media/${state.postId}?per_page=${state.perPage}&page=${state.currentPage}`);
                                    
                                    const response = await fetch(`/wp-json/interactivity-gallery/v1/media/${state.postId}?per_page=${state.perPage}&page=${state.currentPage}`);
                                    
                                    if (!response.ok) {
                                        throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
                                    }
                                    
                                    const data = await response.json();
                                    console.log('Media data received:', data);
                                    
                                    if (!data.success || !Array.isArray(data.media)) {
                                        throw new Error('Invalid data format received from server');
                                    }
                                    
                                    // Update state with fetched data
                                    state.media = data.media;
                                    state.totalPages = data.pages;
                                    state.currentPage = data.current_page;
                                    state.isLoading = false;
                                    
                                    console.log('Media items loaded:', state.media.length);
                                    
                                    // Set the first image as the active one if we have images
                                    if (state.media.length > 0) {
                                        // Find the first image in the collection
                                        let firstImageIndex = -1;
                                        for (let i = 0; i < state.media.length; i++) {
                                            if (state.media[i].type && state.media[i].type.includes('image')) {
                                                firstImageIndex = i;
                                                break;
                                            }
                                        }
                                        
                                        if (firstImageIndex !== -1) {
                                            console.log('Setting first image as active:', firstImageIndex);
                                            state.activeMediaIndex = firstImageIndex;
                                        }
                                    }
                                } catch (error) {
                                    console.error('Error loading media:', error);
                                    state.hasError = true;
                                    state.isLoading = false;
                                }
                            },
                            
                            goToPage: async ({ state, event, data }) => {
                                event.preventDefault();
                                
                                if (data.page < 1 || data.page > state.totalPages || data.page === state.currentPage) {
                                    return;
                                }
                                
                                // Update current page
                                state.currentPage = data.page;
                                
                                // Reset active media index
                                state.activeMediaIndex = -1;
                                
                                // Reload media
                                await wp.interactivity.actions.interactivityGallery.loadMedia({ state, event });
                                
                                // Scroll to top of gallery
                                const galleryContainer = event.target.closest('.interactivity-gallery-container');
                                if (galleryContainer) {
                                    galleryContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                }
                            },
                            
                            openLightbox: ({ state, event, data }) => {
                                event.preventDefault();
                                
                                const index = data.index;
                                console.log('Opening lightbox for index:', index);
                                console.log('Media item:', state.media[index]);
                                
                                // Only open lightbox for images
                                if (state.media[index] && state.media[index].type && state.media[index].type.includes('image')) {
                                    state.activeMediaIndex = index;
                                    state.isLightboxOpen = true;
                                    document.body.style.overflow = 'hidden'; // Prevent body scrolling
                                    console.log('Lightbox opened, activeMediaIndex:', state.activeMediaIndex);
                                } else {
                                    console.log('Not opening lightbox - not an image or invalid media item');
                                }
                            },
                            
                            closeLightbox: ({ state, event }) => {
                                console.log('Closing lightbox');
                                state.isLightboxOpen = false;
                                document.body.style.overflow = ''; // Restore body scrolling
                            },
                            
                            prevMedia: ({ state, event }) => {
                                event.preventDefault();
                                console.log('Current activeMediaIndex:', state.activeMediaIndex);
                                
                                if (state.activeMediaIndex > 0) {
                                    // Find previous image in the collection
                                    let prevIndex = state.activeMediaIndex - 1;
                                    
                                    // Skip non-image media types
                                    while (prevIndex >= 0 && !state.media[prevIndex].type.includes('image')) {
                                        prevIndex--;
                                    }
                                    
                                    if (prevIndex >= 0) {
                                        console.log('Moving to previous image at index:', prevIndex);
                                        state.activeMediaIndex = prevIndex;
                                    } else {
                                        console.log('No previous image found');
                                    }
                                }
                            },
                            
                            nextMedia: ({ state, event }) => {
                                event.preventDefault();
                                console.log('Current activeMediaIndex:', state.activeMediaIndex);
                                
                                if (state.activeMediaIndex < state.media.length - 1) {
                                    // Find next image in the collection
                                    let nextIndex = state.activeMediaIndex + 1;
                                    
                                    // Skip non-image media types
                                    while (nextIndex < state.media.length && !state.media[nextIndex].type.includes('image')) {
                                        nextIndex++;
                                    }
                                    
                                    if (nextIndex < state.media.length) {
                                        console.log('Moving to next image at index:', nextIndex);
                                        state.activeMediaIndex = nextIndex;
                                    } else {
                                        console.log('No next image found');
                                    }
                                }
                            },
                            
                            stopPropagation: ({ event }) => {
                                event.stopPropagation();
                            }
                        }
                    }
                }
            });";
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
                'lightbox' => false, // New parameter to allow opening directly in lightbox
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
            'activeMediaIndex' => -1, // Will be set to 0 after loading
            'isLightboxOpen' => $args['lightbox'], // Open lightbox directly if requested
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