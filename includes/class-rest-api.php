<?php
/**
 * REST API class for Interactivity Gallery
 */
class Interactivity_Gallery_REST_API {
    
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
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
        
        // Add CORS support
        add_action('rest_api_init', array($this, 'add_cors_support'), 15);
    }
    
    /**
     * Add CORS support for REST API
     */
    public function add_cors_support() {
        // Add CORS headers for API requests
        add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Credentials: true');
            return $served;
        }, 10, 4);
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_rest_endpoints() {
        register_rest_route('interactivity-gallery/v1', '/media/(?P<post_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_post_media'),
            'permission_callback' => '__return_true',
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'per_page' => array(
                    'default' => 12,
                    'sanitize_callback' => 'absint',
                ),
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
    }
    
    /**
     * REST API callback for getting post media
     */
    public function get_post_media($request) {
        $post_id = $request->get_param('post_id');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        
        // Get attached media
        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_parent' => $post_id,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'menu_order ID',
            'order' => 'ASC',
        );
        
        $query = new WP_Query($args);
        $total_items = $query->found_posts;
        $total_pages = $query->max_num_pages;
        
        $media_items = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attachment_id = get_the_ID();
                
                // Get better sized images for thumbnails and full display
                $thumbnail_size = apply_filters('interactivity_gallery_thumbnail_size', 'medium');
                $full_size = apply_filters('interactivity_gallery_full_size', 'large');
                
                // Get attachment details
                $attachment_type = get_post_mime_type($attachment_id);
                $attachment_title = get_the_title();
                $attachment_caption = wp_get_attachment_caption($attachment_id);
                $attachment_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                
                // Get thumbnail
                $attachment_thumbnail = wp_get_attachment_image_src($attachment_id, $thumbnail_size);
                $thumbnail_url = $attachment_thumbnail ? $attachment_thumbnail[0] : '';
                
                // For images, get a more appropriate size for the lightbox
                if (strpos($attachment_type, 'image') !== false) {
                    $full_image = wp_get_attachment_image_src($attachment_id, $full_size);
                    if ($full_image) {
                        $attachment_url = $full_image[0];
                    } else {
                        $attachment_url = wp_get_attachment_url($attachment_id);
                    }
                } else {
                    $attachment_url = wp_get_attachment_url($attachment_id);
                }
                
                // For images, make sure we have a valid thumbnail
                if (strpos($attachment_type, 'image') !== false && empty($thumbnail_url)) {
                    // Fallback to full image if thumbnail is missing
                    $thumbnail_url = $attachment_url;
                }
                
                // Ensure we have absolute URLs with proper protocol
                $site_url = site_url();
                $site_url_parts = parse_url($site_url);
                $site_protocol = isset($site_url_parts['scheme']) ? $site_url_parts['scheme'] : 'https';
                
                // Fix URLs that are missing protocol
                if ($attachment_url && strpos($attachment_url, 'http') !== 0) {
                    if (strpos($attachment_url, '//') === 0) {
                        // URL has protocol-relative format (//domain.com/path)
                        $attachment_url = $site_protocol . ':' . $attachment_url;
                    } else {
                        // URL is relative to site root
                        $attachment_url = $site_url . '/' . ltrim($attachment_url, '/');
                    }
                }
                
                if ($thumbnail_url && strpos($thumbnail_url, 'http') !== 0) {
                    if (strpos($thumbnail_url, '//') === 0) {
                        $thumbnail_url = $site_protocol . ':' . $thumbnail_url;
                    } else {
                        $thumbnail_url = $site_url . '/' . ltrim($thumbnail_url, '/');
                    }
                }
                
                // Add a cache-busting parameter to prevent browser caching
                $timestamp = time();
                $attachment_url = add_query_arg('_t', $timestamp, $attachment_url);
                $thumbnail_url = add_query_arg('_t', $timestamp, $thumbnail_url);
                
                $media_item = array(
                    'id' => $attachment_id,
                    'url' => esc_url($attachment_url),
                    'thumbnail' => esc_url($thumbnail_url),
                    'title' => $attachment_title,
                    'caption' => $attachment_caption,
                    'alt' => $attachment_alt,
                    'type' => $attachment_type,
                );
                
                // Extra validation for URLs
                if (empty($media_item['url'])) {
                    // Set a fallback
                    $media_item['url'] = 'https://via.placeholder.com/800x600?text=No+Image';
                }
                
                if (empty($media_item['thumbnail'])) {
                    // Set the URL as thumbnail if no thumbnail available
                    $media_item['thumbnail'] = $media_item['url'];
                }
                
                $media_items[] = $media_item;
            }
            
            wp_reset_postdata();
        }
        
        $response = array(
            'success' => true,
            'media' => $media_items,
            'total' => $total_items,
            'pages' => $total_pages,
            'current_page' => $page,
        );
        
        return rest_ensure_response($response);
    }
}