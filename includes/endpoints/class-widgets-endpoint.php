<?php
/**
 * Widgets endpoints - sidebars and widget management
 */

defined('ABSPATH') || exit;

class MCP_Widgets_Endpoint {

    public function register_routes(): void {
        // List all sidebars
        register_rest_route(MCP_Endpoints::NAMESPACE, '/widgets/sidebars', [
            'methods' => 'GET',
            'callback' => [$this, 'list_sidebars'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get widgets in a sidebar
        register_rest_route(MCP_Endpoints::NAMESPACE, '/widgets/sidebars/(?P<sidebar_id>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sidebar_widgets'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // List all available widget types
        register_rest_route(MCP_Endpoints::NAMESPACE, '/widgets/types', [
            'methods' => 'GET',
            'callback' => [$this, 'list_widget_types'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get single widget
        register_rest_route(MCP_Endpoints::NAMESPACE, '/widgets/(?P<widget_id>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_widget'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Add widget to sidebar
        register_rest_route(MCP_Endpoints::NAMESPACE, '/widgets', [
            'methods' => 'POST',
            'callback' => [$this, 'add_widget'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'sidebar_id' => ['required' => true, 'type' => 'string'],
                'widget_type' => ['required' => true, 'type' => 'string'],
                'settings' => ['type' => 'object', 'default' => []],
                'position' => ['type' => 'integer'],
            ],
        ]);

        // Update widget
        register_rest_route(MCP_Endpoints::NAMESPACE, '/widgets/(?P<widget_id>[a-zA-Z0-9_-]+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_widget'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Delete widget
        register_rest_route(MCP_Endpoints::NAMESPACE, '/widgets/(?P<widget_id>[a-zA-Z0-9_-]+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_widget'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Move widget to different sidebar
        register_rest_route(MCP_Endpoints::NAMESPACE, '/widgets/(?P<widget_id>[a-zA-Z0-9_-]+)/move', [
            'methods' => 'POST',
            'callback' => [$this, 'move_widget'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'sidebar_id' => ['required' => true, 'type' => 'string'],
                'position' => ['type' => 'integer'],
            ],
        ]);

        // Reorder widgets in sidebar
        register_rest_route(MCP_Endpoints::NAMESPACE, '/widgets/sidebars/(?P<sidebar_id>[a-zA-Z0-9_-]+)/reorder', [
            'methods' => 'POST',
            'callback' => [$this, 'reorder_widgets'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'widget_ids' => ['required' => true, 'type' => 'array'],
            ],
        ]);
    }

    public function list_sidebars(WP_REST_Request $request): WP_REST_Response {
        global $wp_registered_sidebars;

        $sidebars_widgets = wp_get_sidebars_widgets();
        $result = [];

        foreach ($wp_registered_sidebars as $id => $sidebar) {
            $widget_count = isset($sidebars_widgets[$id]) ? count($sidebars_widgets[$id]) : 0;

            $result[] = [
                'id' => $id,
                'name' => $sidebar['name'],
                'description' => $sidebar['description'] ?? '',
                'class' => $sidebar['class'] ?? '',
                'widget_count' => $widget_count,
            ];
        }

        return MCP_Endpoints::success([
            'sidebars' => $result,
            'count' => count($result),
        ]);
    }

    public function get_sidebar_widgets(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wp_registered_sidebars;

        $sidebar_id = sanitize_key($request->get_param('sidebar_id'));

        if (!isset($wp_registered_sidebars[$sidebar_id])) {
            return MCP_Endpoints::error("Sidebar '{$sidebar_id}' not found", 'not_found', 404);
        }

        $sidebars_widgets = wp_get_sidebars_widgets();
        $widget_ids = $sidebars_widgets[$sidebar_id] ?? [];

        $widgets = [];
        foreach ($widget_ids as $widget_id) {
            $widget_data = $this->get_widget_data($widget_id);
            if ($widget_data) {
                $widgets[] = $widget_data;
            }
        }

        return MCP_Endpoints::success([
            'sidebar' => [
                'id' => $sidebar_id,
                'name' => $wp_registered_sidebars[$sidebar_id]['name'],
            ],
            'widgets' => $widgets,
            'count' => count($widgets),
        ]);
    }

    public function list_widget_types(WP_REST_Request $request): WP_REST_Response {
        global $wp_widget_factory;

        $types = [];
        foreach ($wp_widget_factory->widgets as $class => $widget) {
            $types[] = [
                'id_base' => $widget->id_base,
                'name' => $widget->name,
                'description' => $widget->widget_options['description'] ?? '',
                'class' => $class,
            ];
        }

        return MCP_Endpoints::success([
            'types' => $types,
            'count' => count($types),
        ]);
    }

    public function get_widget(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $widget_id = sanitize_text_field($request->get_param('widget_id'));

        $widget_data = $this->get_widget_data($widget_id);

        if (!$widget_data) {
            return MCP_Endpoints::error("Widget '{$widget_id}' not found", 'not_found', 404);
        }

        // Find which sidebar it's in
        $sidebars_widgets = wp_get_sidebars_widgets();
        $sidebar_id = null;
        foreach ($sidebars_widgets as $sid => $widgets) {
            if (is_array($widgets) && in_array($widget_id, $widgets)) {
                $sidebar_id = $sid;
                break;
            }
        }

        $widget_data['sidebar_id'] = $sidebar_id;

        return MCP_Endpoints::success($widget_data);
    }

    public function add_widget(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wp_widget_factory, $wp_registered_sidebars;

        $sidebar_id = sanitize_key($request->get_param('sidebar_id'));
        $widget_type = sanitize_key($request->get_param('widget_type'));
        $settings = $request->get_param('settings');
        $position = $request->get_param('position');

        // Verify sidebar exists
        if (!isset($wp_registered_sidebars[$sidebar_id])) {
            return MCP_Endpoints::error("Sidebar '{$sidebar_id}' not found", 'not_found', 404);
        }

        // Find widget by id_base
        $widget_class = null;
        foreach ($wp_widget_factory->widgets as $class => $widget) {
            if ($widget->id_base === $widget_type) {
                $widget_class = $widget;
                break;
            }
        }

        if (!$widget_class) {
            return MCP_Endpoints::error("Widget type '{$widget_type}' not found", 'invalid_type', 400);
        }

        // Get next instance number
        $option_name = 'widget_' . $widget_type;
        $instances = get_option($option_name, []);

        // Find next available number
        $numbers = array_keys($instances);
        $numbers = array_filter($numbers, 'is_numeric');
        $next_number = empty($numbers) ? 1 : max($numbers) + 1;

        // Save widget settings
        $instances[$next_number] = is_array($settings) ? $settings : [];
        update_option($option_name, $instances);

        // Add to sidebar
        $widget_id = $widget_type . '-' . $next_number;
        $sidebars_widgets = wp_get_sidebars_widgets();

        if (!isset($sidebars_widgets[$sidebar_id])) {
            $sidebars_widgets[$sidebar_id] = [];
        }

        if ($position !== null && $position >= 0) {
            array_splice($sidebars_widgets[$sidebar_id], $position, 0, [$widget_id]);
        } else {
            $sidebars_widgets[$sidebar_id][] = $widget_id;
        }

        wp_set_sidebars_widgets($sidebars_widgets);

        return MCP_Endpoints::success([
            'widget_id' => $widget_id,
            'sidebar_id' => $sidebar_id,
            'created' => true,
        ]);
    }

    public function update_widget(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $widget_id = sanitize_text_field($request->get_param('widget_id'));

        // Parse widget ID
        preg_match('/^(.+)-(\d+)$/', $widget_id, $matches);
        if (count($matches) !== 3) {
            return MCP_Endpoints::error("Invalid widget ID format", 'invalid_format', 400);
        }

        $widget_type = $matches[1];
        $widget_number = (int) $matches[2];

        // Get current settings
        $option_name = 'widget_' . $widget_type;
        $instances = get_option($option_name, []);

        if (!isset($instances[$widget_number])) {
            return MCP_Endpoints::error("Widget '{$widget_id}' not found", 'not_found', 404);
        }

        // Update settings
        $settings = $request->get_param('settings');
        if (is_array($settings)) {
            $instances[$widget_number] = array_merge($instances[$widget_number], $settings);
            update_option($option_name, $instances);
        }

        return MCP_Endpoints::success([
            'widget_id' => $widget_id,
            'updated' => true,
        ]);
    }

    public function delete_widget(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $widget_id = sanitize_text_field($request->get_param('widget_id'));

        // Parse widget ID
        preg_match('/^(.+)-(\d+)$/', $widget_id, $matches);
        if (count($matches) !== 3) {
            return MCP_Endpoints::error("Invalid widget ID format", 'invalid_format', 400);
        }

        $widget_type = $matches[1];
        $widget_number = (int) $matches[2];

        // Remove from sidebar
        $sidebars_widgets = wp_get_sidebars_widgets();
        $found = false;

        foreach ($sidebars_widgets as $sidebar_id => &$widgets) {
            if (is_array($widgets)) {
                $key = array_search($widget_id, $widgets);
                if ($key !== false) {
                    unset($widgets[$key]);
                    $widgets = array_values($widgets); // Re-index
                    $found = true;
                }
            }
        }

        if (!$found) {
            return MCP_Endpoints::error("Widget '{$widget_id}' not found", 'not_found', 404);
        }

        wp_set_sidebars_widgets($sidebars_widgets);

        // Remove widget settings
        $option_name = 'widget_' . $widget_type;
        $instances = get_option($option_name, []);
        if (isset($instances[$widget_number])) {
            unset($instances[$widget_number]);
            update_option($option_name, $instances);
        }

        return MCP_Endpoints::success([
            'widget_id' => $widget_id,
            'deleted' => true,
        ]);
    }

    public function move_widget(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wp_registered_sidebars;

        $widget_id = sanitize_text_field($request->get_param('widget_id'));
        $target_sidebar = sanitize_key($request->get_param('sidebar_id'));
        $position = $request->get_param('position');

        if (!isset($wp_registered_sidebars[$target_sidebar])) {
            return MCP_Endpoints::error("Sidebar '{$target_sidebar}' not found", 'not_found', 404);
        }

        $sidebars_widgets = wp_get_sidebars_widgets();
        $found = false;
        $source_sidebar = null;

        // Find and remove from current sidebar
        foreach ($sidebars_widgets as $sidebar_id => &$widgets) {
            if (is_array($widgets)) {
                $key = array_search($widget_id, $widgets);
                if ($key !== false) {
                    unset($widgets[$key]);
                    $widgets = array_values($widgets);
                    $found = true;
                    $source_sidebar = $sidebar_id;
                    break;
                }
            }
        }

        if (!$found) {
            return MCP_Endpoints::error("Widget '{$widget_id}' not found", 'not_found', 404);
        }

        // Add to target sidebar
        if (!isset($sidebars_widgets[$target_sidebar])) {
            $sidebars_widgets[$target_sidebar] = [];
        }

        if ($position !== null && $position >= 0) {
            array_splice($sidebars_widgets[$target_sidebar], $position, 0, [$widget_id]);
        } else {
            $sidebars_widgets[$target_sidebar][] = $widget_id;
        }

        wp_set_sidebars_widgets($sidebars_widgets);

        return MCP_Endpoints::success([
            'widget_id' => $widget_id,
            'from_sidebar' => $source_sidebar,
            'to_sidebar' => $target_sidebar,
            'moved' => true,
        ]);
    }

    public function reorder_widgets(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wp_registered_sidebars;

        $sidebar_id = sanitize_key($request->get_param('sidebar_id'));
        $widget_ids = $request->get_param('widget_ids');

        if (!isset($wp_registered_sidebars[$sidebar_id])) {
            return MCP_Endpoints::error("Sidebar '{$sidebar_id}' not found", 'not_found', 404);
        }

        $sidebars_widgets = wp_get_sidebars_widgets();
        $current_widgets = $sidebars_widgets[$sidebar_id] ?? [];

        // Verify all widget IDs exist in this sidebar
        foreach ($widget_ids as $widget_id) {
            if (!in_array($widget_id, $current_widgets)) {
                return MCP_Endpoints::error("Widget '{$widget_id}' not in sidebar", 'invalid_widget', 400);
            }
        }

        // Update order
        $sidebars_widgets[$sidebar_id] = array_map('sanitize_text_field', $widget_ids);
        wp_set_sidebars_widgets($sidebars_widgets);

        return MCP_Endpoints::success([
            'sidebar_id' => $sidebar_id,
            'widget_ids' => $widget_ids,
            'reordered' => true,
        ]);
    }

    private function get_widget_data(string $widget_id): ?array {
        global $wp_widget_factory;

        // Parse widget ID (e.g., "text-2" => type="text", number=2)
        preg_match('/^(.+)-(\d+)$/', $widget_id, $matches);
        if (count($matches) !== 3) {
            return null;
        }

        $widget_type = $matches[1];
        $widget_number = (int) $matches[2];

        // Find widget instance
        $option_name = 'widget_' . $widget_type;
        $instances = get_option($option_name, []);

        if (!isset($instances[$widget_number])) {
            return null;
        }

        // Get widget info
        $widget_info = null;
        foreach ($wp_widget_factory->widgets as $class => $widget) {
            if ($widget->id_base === $widget_type) {
                $widget_info = $widget;
                break;
            }
        }

        return [
            'id' => $widget_id,
            'type' => $widget_type,
            'name' => $widget_info ? $widget_info->name : $widget_type,
            'settings' => $instances[$widget_number],
        ];
    }
}
