<?php
/**
 * Custom Post Types endpoints - list, schema, CRUD operations
 */

defined('ABSPATH') || exit;

class MCP_CPT_Endpoint {

    public function register_routes(): void {
        // List all registered post types
        register_rest_route(MCP_Endpoints::NAMESPACE, '/cpt', [
            'methods' => 'GET',
            'callback' => [$this, 'list_post_types'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get single post type schema
        register_rest_route(MCP_Endpoints::NAMESPACE, '/cpt/(?P<type>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_post_type'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'type' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // Get posts of a specific type
        register_rest_route(MCP_Endpoints::NAMESPACE, '/cpt/(?P<type>[a-zA-Z0-9_-]+)/posts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_posts'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'type' => ['required' => true, 'type' => 'string'],
                'per_page' => ['type' => 'integer', 'default' => 20],
                'page' => ['type' => 'integer', 'default' => 1],
                'status' => ['type' => 'string', 'default' => 'any'],
                'orderby' => ['type' => 'string', 'default' => 'date'],
                'order' => ['type' => 'string', 'default' => 'DESC'],
            ],
        ]);

        // Create post of specific type
        register_rest_route(MCP_Endpoints::NAMESPACE, '/cpt/(?P<type>[a-zA-Z0-9_-]+)/posts', [
            'methods' => 'POST',
            'callback' => [$this, 'create_post'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'type' => ['required' => true, 'type' => 'string'],
                'title' => ['required' => true, 'type' => 'string'],
                'content' => ['type' => 'string', 'default' => ''],
                'status' => ['type' => 'string', 'default' => 'draft'],
                'meta' => ['type' => 'object', 'default' => []],
            ],
        ]);

        // Update post
        register_rest_route(MCP_Endpoints::NAMESPACE, '/cpt/(?P<type>[a-zA-Z0-9_-]+)/posts/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_post'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'type' => ['required' => true, 'type' => 'string'],
                'id' => ['required' => true, 'type' => 'integer'],
                'title' => ['type' => 'string'],
                'content' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'meta' => ['type' => 'object'],
            ],
        ]);

        // Delete post
        register_rest_route(MCP_Endpoints::NAMESPACE, '/cpt/(?P<type>[a-zA-Z0-9_-]+)/posts/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_post'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'type' => ['required' => true, 'type' => 'string'],
                'id' => ['required' => true, 'type' => 'integer'],
                'force' => ['type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    public function list_post_types(WP_REST_Request $request): WP_REST_Response {
        $post_types = get_post_types(['public' => true], 'objects');

        $result = [];
        foreach ($post_types as $type) {
            $result[] = [
                'name' => $type->name,
                'label' => $type->label,
                'singular' => $type->labels->singular_name,
                'public' => $type->public,
                'hierarchical' => $type->hierarchical,
                'has_archive' => $type->has_archive,
                'rest_base' => $type->rest_base ?: $type->name,
                'supports' => get_all_post_type_supports($type->name),
                'taxonomies' => get_object_taxonomies($type->name),
                'count' => (int) wp_count_posts($type->name)->publish,
            ];
        }

        return MCP_Endpoints::success([
            'post_types' => $result,
            'count' => count($result),
        ]);
    }

    public function get_post_type(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $type_name = sanitize_key($request->get_param('type'));
        $type = get_post_type_object($type_name);

        if (!$type) {
            return MCP_Endpoints::error("Post type '{$type_name}' not found", 'not_found', 404);
        }

        $counts = wp_count_posts($type_name);

        return MCP_Endpoints::success([
            'name' => $type->name,
            'label' => $type->label,
            'singular' => $type->labels->singular_name,
            'description' => $type->description,
            'public' => $type->public,
            'hierarchical' => $type->hierarchical,
            'has_archive' => $type->has_archive,
            'rest_base' => $type->rest_base ?: $type->name,
            'supports' => get_all_post_type_supports($type->name),
            'taxonomies' => get_object_taxonomies($type->name),
            'counts' => [
                'publish' => (int) $counts->publish,
                'draft' => (int) $counts->draft,
                'pending' => (int) $counts->pending,
                'private' => (int) $counts->private,
                'trash' => (int) $counts->trash,
            ],
            'labels' => [
                'add_new' => $type->labels->add_new,
                'add_new_item' => $type->labels->add_new_item,
                'edit_item' => $type->labels->edit_item,
                'view_item' => $type->labels->view_item,
            ],
        ]);
    }

    public function get_posts(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $type = sanitize_key($request->get_param('type'));

        if (!post_type_exists($type)) {
            return MCP_Endpoints::error("Post type '{$type}' not found", 'not_found', 404);
        }

        $args = [
            'post_type' => $type,
            'posts_per_page' => absint($request->get_param('per_page')),
            'paged' => absint($request->get_param('page')),
            'post_status' => sanitize_text_field($request->get_param('status')),
            'orderby' => sanitize_key($request->get_param('orderby')),
            'order' => strtoupper($request->get_param('order')) === 'ASC' ? 'ASC' : 'DESC',
        ];

        $query = new WP_Query($args);
        $posts = [];

        foreach ($query->posts as $post) {
            $posts[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'slug' => $post->post_name,
                'status' => $post->post_status,
                'date' => $post->post_date,
                'modified' => $post->post_modified,
                'author' => (int) $post->post_author,
                'excerpt' => $post->post_excerpt,
                'parent' => (int) $post->post_parent,
            ];
        }

        return MCP_Endpoints::success([
            'posts' => $posts,
            'total' => (int) $query->found_posts,
            'pages' => (int) $query->max_num_pages,
            'page' => absint($request->get_param('page')),
        ]);
    }

    public function create_post(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $type = sanitize_key($request->get_param('type'));

        if (!post_type_exists($type)) {
            return MCP_Endpoints::error("Post type '{$type}' not found", 'not_found', 404);
        }

        $post_data = [
            'post_type' => $type,
            'post_title' => sanitize_text_field($request->get_param('title')),
            'post_content' => wp_kses_post($request->get_param('content')),
            'post_status' => sanitize_key($request->get_param('status')),
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Handle meta
        $meta = $request->get_param('meta');
        if (!empty($meta) && is_array($meta)) {
            foreach ($meta as $key => $value) {
                update_post_meta($post_id, sanitize_key($key), $value);
            }
        }

        return MCP_Endpoints::success([
            'id' => $post_id,
            'created' => true,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
        ]);
    }

    public function update_post(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $type = sanitize_key($request->get_param('type'));
        $post_id = absint($request->get_param('id'));

        $post = get_post($post_id);
        if (!$post || $post->post_type !== $type) {
            return MCP_Endpoints::error("Post not found", 'not_found', 404);
        }

        $post_data = ['ID' => $post_id];

        if ($request->has_param('title')) {
            $post_data['post_title'] = sanitize_text_field($request->get_param('title'));
        }
        if ($request->has_param('content')) {
            $post_data['post_content'] = wp_kses_post($request->get_param('content'));
        }
        if ($request->has_param('status')) {
            $post_data['post_status'] = sanitize_key($request->get_param('status'));
        }

        $result = wp_update_post($post_data, true);

        if (is_wp_error($result)) {
            return $result;
        }

        // Handle meta
        $meta = $request->get_param('meta');
        if (!empty($meta) && is_array($meta)) {
            foreach ($meta as $key => $value) {
                update_post_meta($post_id, sanitize_key($key), $value);
            }
        }

        return MCP_Endpoints::success([
            'id' => $post_id,
            'updated' => true,
        ]);
    }

    public function delete_post(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $type = sanitize_key($request->get_param('type'));
        $post_id = absint($request->get_param('id'));
        $force = $request->get_param('force');

        $post = get_post($post_id);
        if (!$post || $post->post_type !== $type) {
            return MCP_Endpoints::error("Post not found", 'not_found', 404);
        }

        $result = wp_delete_post($post_id, $force);

        if (!$result) {
            return MCP_Endpoints::error("Failed to delete post", 'delete_failed');
        }

        return MCP_Endpoints::success([
            'id' => $post_id,
            'deleted' => true,
            'trashed' => !$force,
        ]);
    }
}
