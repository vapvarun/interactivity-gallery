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
        
        // Get attached media
        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_parent' => $post_id,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'menu_order',
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
                
                // Get attachment details
                $attachment_url = wp_get_attachment_url($attachment_id);
                $attachment_type = get_post_mime_type($attachment_id);
                $attachment_title = get_the_title();
                $attachment_caption = wp_get_attachment_caption($attachment_id);
                $attachment_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                
                // Get thumbnail
                $attachment_thumbnail = wp_get_attachment_image_src($attachment_id, 'medium');
                
                $media_items[] = array(
                    'id' => $attachment_id,
                    'url' => $attachment_url,
                    'thumbnail' => $attachment_thumbnail ? $attachment_thumbnail[0] : '',
                    'title' => $attachment_title,
                    'caption' => $attachment_caption,
                    'alt' => $attachment_alt,
                    'type' => $attachment_type,
                );
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