<?php
/**
 * Menus endpoints - navigation menus and menu items
 */

defined('ABSPATH') || exit;

class MCP_Menus_Endpoint {

    public function register_routes(): void {
        // List all menus
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus', [
            'methods' => 'GET',
            'callback' => [$this, 'list_menus'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get menu locations
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus/locations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_locations'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get single menu with items
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_menu'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Create menu
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus', [
            'methods' => 'POST',
            'callback' => [$this, 'create_menu'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'name' => ['required' => true, 'type' => 'string'],
            ],
        ]);

        // Update menu
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_menu'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Delete menu
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_menu'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Add menu item
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus/(?P<id>\d+)/items', [
            'methods' => 'POST',
            'callback' => [$this, 'add_menu_item'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'id' => ['required' => true, 'type' => 'integer'],
                'title' => ['required' => true, 'type' => 'string'],
                'url' => ['type' => 'string'],
                'object_type' => ['type' => 'string', 'default' => 'custom'],
                'object_id' => ['type' => 'integer'],
                'parent' => ['type' => 'integer', 'default' => 0],
                'position' => ['type' => 'integer'],
            ],
        ]);

        // Update menu item
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus/items/(?P<item_id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_menu_item'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Delete menu item
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus/items/(?P<item_id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_menu_item'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Assign menu to location
        register_rest_route(MCP_Endpoints::NAMESPACE, '/menus/locations/assign', [
            'methods' => 'POST',
            'callback' => [$this, 'assign_location'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'menu_id' => ['required' => true, 'type' => 'integer'],
                'location' => ['required' => true, 'type' => 'string'],
            ],
        ]);
    }

    public function list_menus(WP_REST_Request $request): WP_REST_Response {
        $menus = wp_get_nav_menus();
        $locations = get_nav_menu_locations();

        $result = [];
        foreach ($menus as $menu) {
            $menu_locations = array_keys($locations, $menu->term_id);
            $result[] = [
                'id' => $menu->term_id,
                'name' => $menu->name,
                'slug' => $menu->slug,
                'count' => $menu->count,
                'locations' => $menu_locations,
            ];
        }

        return MCP_Endpoints::success([
            'menus' => $result,
            'count' => count($result),
        ]);
    }

    public function get_locations(WP_REST_Request $request): WP_REST_Response {
        $registered = get_registered_nav_menus();
        $assigned = get_nav_menu_locations();

        $result = [];
        foreach ($registered as $location => $description) {
            $result[] = [
                'location' => $location,
                'description' => $description,
                'menu_id' => $assigned[$location] ?? null,
            ];
        }

        return MCP_Endpoints::success([
            'locations' => $result,
            'count' => count($result),
        ]);
    }

    public function get_menu(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $menu_id = absint($request->get_param('id'));
        $menu = wp_get_nav_menu_object($menu_id);

        if (!$menu) {
            return MCP_Endpoints::error("Menu not found", 'not_found', 404);
        }

        $items = wp_get_nav_menu_items($menu_id);
        $locations = get_nav_menu_locations();
        $menu_locations = array_keys($locations, $menu_id);

        $formatted_items = [];
        foreach ($items as $item) {
            $formatted_items[] = [
                'id' => $item->ID,
                'title' => $item->title,
                'url' => $item->url,
                'type' => $item->type,
                'object' => $item->object,
                'object_id' => (int) $item->object_id,
                'parent' => (int) $item->menu_item_parent,
                'position' => (int) $item->menu_order,
                'target' => $item->target,
                'classes' => $item->classes,
            ];
        }

        return MCP_Endpoints::success([
            'id' => $menu->term_id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'locations' => $menu_locations,
            'items' => $formatted_items,
            'count' => count($formatted_items),
        ]);
    }

    public function create_menu(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $name = sanitize_text_field($request->get_param('name'));

        $menu_id = wp_create_nav_menu($name);

        if (is_wp_error($menu_id)) {
            return $menu_id;
        }

        return MCP_Endpoints::success([
            'id' => $menu_id,
            'name' => $name,
            'created' => true,
        ]);
    }

    public function update_menu(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $menu_id = absint($request->get_param('id'));
        $menu = wp_get_nav_menu_object($menu_id);

        if (!$menu) {
            return MCP_Endpoints::error("Menu not found", 'not_found', 404);
        }

        $args = [];
        if ($request->has_param('name')) {
            $args['menu-name'] = sanitize_text_field($request->get_param('name'));
        }

        $result = wp_update_nav_menu_object($menu_id, $args);

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'id' => $menu_id,
            'updated' => true,
        ]);
    }

    public function delete_menu(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $menu_id = absint($request->get_param('id'));

        $result = wp_delete_nav_menu($menu_id);

        if (is_wp_error($result)) {
            return $result;
        }

        if ($result === false) {
            return MCP_Endpoints::error("Failed to delete menu", 'delete_failed');
        }

        return MCP_Endpoints::success([
            'id' => $menu_id,
            'deleted' => true,
        ]);
    }

    public function add_menu_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $menu_id = absint($request->get_param('id'));

        if (!wp_get_nav_menu_object($menu_id)) {
            return MCP_Endpoints::error("Menu not found", 'not_found', 404);
        }

        $item_data = [
            'menu-item-title' => sanitize_text_field($request->get_param('title')),
            'menu-item-status' => 'publish',
            'menu-item-parent-id' => absint($request->get_param('parent')),
        ];

        $object_type = $request->get_param('object_type');

        if ($object_type === 'custom' || !$object_type) {
            $item_data['menu-item-type'] = 'custom';
            $item_data['menu-item-url'] = esc_url_raw($request->get_param('url'));
        } elseif ($object_type === 'post_type') {
            $item_data['menu-item-type'] = 'post_type';
            $item_data['menu-item-object'] = sanitize_key($request->get_param('object'));
            $item_data['menu-item-object-id'] = absint($request->get_param('object_id'));
        } elseif ($object_type === 'taxonomy') {
            $item_data['menu-item-type'] = 'taxonomy';
            $item_data['menu-item-object'] = sanitize_key($request->get_param('object'));
            $item_data['menu-item-object-id'] = absint($request->get_param('object_id'));
        }

        if ($request->has_param('position')) {
            $item_data['menu-item-position'] = absint($request->get_param('position'));
        }

        $item_id = wp_update_nav_menu_item($menu_id, 0, $item_data);

        if (is_wp_error($item_id)) {
            return $item_id;
        }

        return MCP_Endpoints::success([
            'id' => $item_id,
            'menu_id' => $menu_id,
            'created' => true,
        ]);
    }

    public function update_menu_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $item_id = absint($request->get_param('item_id'));

        $item = get_post($item_id);
        if (!$item || $item->post_type !== 'nav_menu_item') {
            return MCP_Endpoints::error("Menu item not found", 'not_found', 404);
        }

        $menu_id = (int) get_post_meta($item_id, '_menu_item_menu_item_parent', true);
        $menus = wp_get_object_terms($item_id, 'nav_menu');
        $menu_id = !empty($menus) ? $menus[0]->term_id : 0;

        $item_data = [];

        if ($request->has_param('title')) {
            $item_data['menu-item-title'] = sanitize_text_field($request->get_param('title'));
        }
        if ($request->has_param('url')) {
            $item_data['menu-item-url'] = esc_url_raw($request->get_param('url'));
        }
        if ($request->has_param('parent')) {
            $item_data['menu-item-parent-id'] = absint($request->get_param('parent'));
        }
        if ($request->has_param('position')) {
            $item_data['menu-item-position'] = absint($request->get_param('position'));
        }

        $result = wp_update_nav_menu_item($menu_id, $item_id, $item_data);

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'id' => $item_id,
            'updated' => true,
        ]);
    }

    public function delete_menu_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $item_id = absint($request->get_param('item_id'));

        $result = wp_delete_post($item_id, true);

        if (!$result) {
            return MCP_Endpoints::error("Failed to delete menu item", 'delete_failed');
        }

        return MCP_Endpoints::success([
            'id' => $item_id,
            'deleted' => true,
        ]);
    }

    public function assign_location(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $menu_id = absint($request->get_param('menu_id'));
        $location = sanitize_key($request->get_param('location'));

        $registered = get_registered_nav_menus();
        if (!isset($registered[$location])) {
            return MCP_Endpoints::error("Location '{$location}' not registered", 'invalid_location', 400);
        }

        if ($menu_id > 0 && !wp_get_nav_menu_object($menu_id)) {
            return MCP_Endpoints::error("Menu not found", 'not_found', 404);
        }

        $locations = get_nav_menu_locations();
        $locations[$location] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);

        return MCP_Endpoints::success([
            'location' => $location,
            'menu_id' => $menu_id,
            'assigned' => true,
        ]);
    }
}
