<?php
/**
 * Taxonomies endpoints - list taxonomies, manage terms
 */

defined('ABSPATH') || exit;

class MCP_Taxonomies_Endpoint {

    public function register_routes(): void {
        // List all taxonomies
        register_rest_route(MCP_Endpoints::NAMESPACE, '/taxonomies', [
            'methods' => 'GET',
            'callback' => [$this, 'list_taxonomies'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get single taxonomy
        register_rest_route(MCP_Endpoints::NAMESPACE, '/taxonomies/(?P<taxonomy>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_taxonomy'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get terms of a taxonomy
        register_rest_route(MCP_Endpoints::NAMESPACE, '/taxonomies/(?P<taxonomy>[a-zA-Z0-9_-]+)/terms', [
            'methods' => 'GET',
            'callback' => [$this, 'get_terms'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'taxonomy' => ['required' => true, 'type' => 'string'],
                'hide_empty' => ['type' => 'boolean', 'default' => false],
                'parent' => ['type' => 'integer'],
                'search' => ['type' => 'string'],
            ],
        ]);

        // Create term
        register_rest_route(MCP_Endpoints::NAMESPACE, '/taxonomies/(?P<taxonomy>[a-zA-Z0-9_-]+)/terms', [
            'methods' => 'POST',
            'callback' => [$this, 'create_term'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'taxonomy' => ['required' => true, 'type' => 'string'],
                'name' => ['required' => true, 'type' => 'string'],
                'slug' => ['type' => 'string'],
                'description' => ['type' => 'string', 'default' => ''],
                'parent' => ['type' => 'integer', 'default' => 0],
            ],
        ]);

        // Update term
        register_rest_route(MCP_Endpoints::NAMESPACE, '/taxonomies/(?P<taxonomy>[a-zA-Z0-9_-]+)/terms/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_term'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Delete term
        register_rest_route(MCP_Endpoints::NAMESPACE, '/taxonomies/(?P<taxonomy>[a-zA-Z0-9_-]+)/terms/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_term'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Assign terms to post
        register_rest_route(MCP_Endpoints::NAMESPACE, '/taxonomies/assign', [
            'methods' => 'POST',
            'callback' => [$this, 'assign_terms'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'post_id' => ['required' => true, 'type' => 'integer'],
                'taxonomy' => ['required' => true, 'type' => 'string'],
                'terms' => ['required' => true, 'type' => 'array'],
                'append' => ['type' => 'boolean', 'default' => false],
            ],
        ]);
    }

    public function list_taxonomies(WP_REST_Request $request): WP_REST_Response {
        $taxonomies = get_taxonomies(['public' => true], 'objects');

        $result = [];
        foreach ($taxonomies as $tax) {
            $result[] = [
                'name' => $tax->name,
                'label' => $tax->label,
                'singular' => $tax->labels->singular_name,
                'hierarchical' => $tax->hierarchical,
                'public' => $tax->public,
                'post_types' => $tax->object_type,
                'rest_base' => $tax->rest_base ?: $tax->name,
                'count' => (int) wp_count_terms(['taxonomy' => $tax->name, 'hide_empty' => false]),
            ];
        }

        return MCP_Endpoints::success([
            'taxonomies' => $result,
            'count' => count($result),
        ]);
    }

    public function get_taxonomy(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $taxonomy = sanitize_key($request->get_param('taxonomy'));
        $tax = get_taxonomy($taxonomy);

        if (!$tax) {
            return MCP_Endpoints::error("Taxonomy '{$taxonomy}' not found", 'not_found', 404);
        }

        return MCP_Endpoints::success([
            'name' => $tax->name,
            'label' => $tax->label,
            'singular' => $tax->labels->singular_name,
            'description' => $tax->description,
            'hierarchical' => $tax->hierarchical,
            'public' => $tax->public,
            'post_types' => $tax->object_type,
            'rest_base' => $tax->rest_base ?: $tax->name,
            'count' => (int) wp_count_terms(['taxonomy' => $tax->name, 'hide_empty' => false]),
            'labels' => [
                'add_new_item' => $tax->labels->add_new_item,
                'edit_item' => $tax->labels->edit_item,
                'search_items' => $tax->labels->search_items,
            ],
        ]);
    }

    public function get_terms(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $taxonomy = sanitize_key($request->get_param('taxonomy'));

        if (!taxonomy_exists($taxonomy)) {
            return MCP_Endpoints::error("Taxonomy '{$taxonomy}' not found", 'not_found', 404);
        }

        $args = [
            'taxonomy' => $taxonomy,
            'hide_empty' => $request->get_param('hide_empty'),
        ];

        if ($request->has_param('parent')) {
            $args['parent'] = absint($request->get_param('parent'));
        }

        if ($request->has_param('search')) {
            $args['search'] = sanitize_text_field($request->get_param('search'));
        }

        $terms = get_terms($args);

        if (is_wp_error($terms)) {
            return $terms;
        }

        $result = [];
        foreach ($terms as $term) {
            $result[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'parent' => $term->parent,
                'count' => $term->count,
            ];
        }

        return MCP_Endpoints::success([
            'terms' => $result,
            'count' => count($result),
        ]);
    }

    public function create_term(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $taxonomy = sanitize_key($request->get_param('taxonomy'));

        if (!taxonomy_exists($taxonomy)) {
            return MCP_Endpoints::error("Taxonomy '{$taxonomy}' not found", 'not_found', 404);
        }

        $args = [
            'description' => sanitize_textarea_field($request->get_param('description')),
            'parent' => absint($request->get_param('parent')),
        ];

        if ($request->has_param('slug')) {
            $args['slug'] = sanitize_title($request->get_param('slug'));
        }

        $result = wp_insert_term(
            sanitize_text_field($request->get_param('name')),
            $taxonomy,
            $args
        );

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'id' => $result['term_id'],
            'created' => true,
        ]);
    }

    public function update_term(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $taxonomy = sanitize_key($request->get_param('taxonomy'));
        $term_id = absint($request->get_param('id'));

        if (!taxonomy_exists($taxonomy)) {
            return MCP_Endpoints::error("Taxonomy '{$taxonomy}' not found", 'not_found', 404);
        }

        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            return MCP_Endpoints::error("Term not found", 'not_found', 404);
        }

        $args = [];
        if ($request->has_param('name')) {
            $args['name'] = sanitize_text_field($request->get_param('name'));
        }
        if ($request->has_param('slug')) {
            $args['slug'] = sanitize_title($request->get_param('slug'));
        }
        if ($request->has_param('description')) {
            $args['description'] = sanitize_textarea_field($request->get_param('description'));
        }
        if ($request->has_param('parent')) {
            $args['parent'] = absint($request->get_param('parent'));
        }

        $result = wp_update_term($term_id, $taxonomy, $args);

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'id' => $term_id,
            'updated' => true,
        ]);
    }

    public function delete_term(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $taxonomy = sanitize_key($request->get_param('taxonomy'));
        $term_id = absint($request->get_param('id'));

        if (!taxonomy_exists($taxonomy)) {
            return MCP_Endpoints::error("Taxonomy '{$taxonomy}' not found", 'not_found', 404);
        }

        $result = wp_delete_term($term_id, $taxonomy);

        if (is_wp_error($result)) {
            return $result;
        }

        if ($result === false) {
            return MCP_Endpoints::error("Failed to delete term", 'delete_failed');
        }

        return MCP_Endpoints::success([
            'id' => $term_id,
            'deleted' => true,
        ]);
    }

    public function assign_terms(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $post_id = absint($request->get_param('post_id'));
        $taxonomy = sanitize_key($request->get_param('taxonomy'));
        $terms = $request->get_param('terms');
        $append = $request->get_param('append');

        if (!get_post($post_id)) {
            return MCP_Endpoints::error("Post not found", 'not_found', 404);
        }

        if (!taxonomy_exists($taxonomy)) {
            return MCP_Endpoints::error("Taxonomy '{$taxonomy}' not found", 'not_found', 404);
        }

        // Convert term names/slugs to IDs if needed
        $term_ids = [];
        foreach ($terms as $term) {
            if (is_numeric($term)) {
                $term_ids[] = (int) $term;
            } else {
                $term_obj = get_term_by('slug', $term, $taxonomy) ?: get_term_by('name', $term, $taxonomy);
                if ($term_obj) {
                    $term_ids[] = $term_obj->term_id;
                }
            }
        }

        $result = wp_set_object_terms($post_id, $term_ids, $taxonomy, $append);

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'post_id' => $post_id,
            'taxonomy' => $taxonomy,
            'terms' => $result,
            'appended' => $append,
        ]);
    }
}
