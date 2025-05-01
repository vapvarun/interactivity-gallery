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
        
        // Log debugging info for REST request
        if (defined('IG_DEBUG') && IG_DEBUG) {
            ig_debug_log("REST API request for post ID: $post_id, per_page: $per_page, page: $page");
        }
        
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
        
        // Log query results
        if (defined('IG_DEBUG') && IG_DEBUG) {
            ig_debug_log("Query found $total_items items and $total_pages pages");
            ig_debug_log($query->request); // Log the actual SQL query
        }
        
        $media_items = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attachment_id = get_the_ID();
                
                // Get attachment details
                $attachment_url = wp_get_attachment_url($attachment_id);
                $attachment_type = get_post_mime_type($attachment_id);
                $attachment_title = get_the_title();
                $attachment_caption = wp_get_attachment_caption($attachment_id);
                $attachment_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                
                // Get thumbnail
                $attachment_thumbnail = wp_get_attachment_image_src($attachment_id, 'medium');
                $thumbnail_url = $attachment_thumbnail ? $attachment_thumbnail[0] : '';
                
                // For images, make sure we have a valid thumbnail
                if (strpos($attachment_type, 'image') !== false && empty($thumbnail_url)) {
                    // Fallback to full image if thumbnail is missing
                    $thumbnail_url = $attachment_url;
                }
                
                // Ensure we have absolute URLs
                $site_url = site_url();
                if ($attachment_url && strpos($attachment_url, 'http') !== 0) {
                    $attachment_url = $site_url . $attachment_url;
                }
                if ($thumbnail_url && strpos($thumbnail_url, 'http') !== 0) {
                    $thumbnail_url = $site_url . $thumbnail_url;
                }
                
                $media_item = array(
                    'id' => $attachment_id,
                    'url' => esc_url($attachment_url),  // Make sure URL is properly escaped
                    'thumbnail' => esc_url($thumbnail_url),  // Make sure thumbnail URL is properly escaped
                    'title' => $attachment_title,
                    'caption' => $attachment_caption,
                    'alt' => $attachment_alt,
                    'type' => $attachment_type,
                );
                
                // Extra validation for URLs
                if (empty($media_item['url'])) {
                    ig_debug_log("Warning: Empty URL for attachment ID: $attachment_id");
                    // Set a fallback
                    $media_item['url'] = 'https://via.placeholder.com/800x600?text=No+Image';
                }
                
                if (empty($media_item['thumbnail'])) {
                    ig_debug_log("Warning: Empty thumbnail for attachment ID: $attachment_id");
                    // Set the URL as thumbnail if no thumbnail available
                    $media_item['thumbnail'] = $media_item['url'];
                }
                
                // Debug log each item
                if (defined('IG_DEBUG') && IG_DEBUG) {
                    ig_debug_log("Media item: " . json_encode($media_item));
                }
                
                $media_items[] = $media_item;
            }
            
            wp_reset_postdata();
        }
        
        // Handle case when no attachments are found
        if (empty($media_items)) {
            // Double check with a direct DB query
            global $wpdb;
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'attachment' AND post_status = 'inherit'",
                $post_id
            ));
            
            if (defined('IG_DEBUG') && IG_DEBUG) {
                ig_debug_log("Direct DB query found $count attachments");
            }
        }
        
        // Add detailed logging for each media item in the REST API response
        if (defined('IG_DEBUG') && IG_DEBUG) {
            ig_debug_log(sprintf(
                "REST API returning %d media items for post ID %d (page %d of %d)",
                count($media_items),
                $post_id,
                $page,
                $total_pages
            ));
            
            // Log the first few media items in detail
            $items_to_log = min(count($media_items), 3);
            for ($i = 0; $i < $items_to_log; $i++) {
                $item = $media_items[$i];
                ig_debug_log(sprintf(
                    "Media item %d: ID=%d, Type=%s, URL=%s, Thumbnail=%s, Title=%s",
                    $i + 1,
                    $item['id'],
                    $item['type'],
                    $item['url'],
                    $item['thumbnail'],
                    $item['title']
                ));
                
                // Validate URL
                if (empty($item['url']) || !filter_var($item['url'], FILTER_VALIDATE_URL)) {
                    ig_debug_log("WARNING: Media item {$i} has invalid URL: {$item['url']}");
                }
                
                // Validate thumbnail
                if (empty($item['thumbnail']) || !filter_var($item['thumbnail'], FILTER_VALIDATE_URL)) {
                    ig_debug_log("WARNING: Media item {$i} has invalid thumbnail URL: {$item['thumbnail']}");
                }
            }
        }
        
        $response = array(
            'success' => true,
            'media' => $media_items,
            'total' => $total_items,
            'pages' => $total_pages,
            'current_page' => $page,
            'debug_info' => defined('IG_DEBUG') && IG_DEBUG ? array(
                'post_id' => $post_id,
                'attachment_count' => count($media_items),
                'query_args' => $args
            ) : null,
        );
        
        return rest_ensure_response($response);
    }
}