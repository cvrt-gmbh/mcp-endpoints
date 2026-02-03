<?php
/**
 * Options endpoints - get/set any WordPress option
 */

defined('ABSPATH') || exit;

class MCP_Options_Endpoint {

    public function register_routes(): void {
        // Get option
        register_rest_route(MCP_Endpoints::NAMESPACE, '/options/(?P<key>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_option'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'key' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // Set option
        register_rest_route(MCP_Endpoints::NAMESPACE, '/options/(?P<key>[a-zA-Z0-9_-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'set_option'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'key' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'value' => [
                    'required' => true,
                ],
                'autoload' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
            ],
        ]);

        // Delete option
        register_rest_route(MCP_Endpoints::NAMESPACE, '/options/(?P<key>[a-zA-Z0-9_-]+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_option'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'key' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);

        // List options (with prefix filter)
        register_rest_route(MCP_Endpoints::NAMESPACE, '/options', [
            'methods' => 'GET',
            'callback' => [$this, 'list_options'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'prefix' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 50,
                ],
            ],
        ]);

        // Bulk get options
        register_rest_route(MCP_Endpoints::NAMESPACE, '/options-bulk', [
            'methods' => 'POST',
            'callback' => [$this, 'bulk_get_options'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'keys' => [
                    'required' => true,
                    'type' => 'array',
                ],
            ],
        ]);
    }

    public function get_option(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $key = sanitize_key($request->get_param('key'));

        $value = get_option($key);

        if ($value === false && !$this->option_exists($key)) {
            return MCP_Endpoints::error("Option '{$key}' not found", 'option_not_found', 404);
        }

        return MCP_Endpoints::success([
            'key' => $key,
            'value' => $value,
        ]);
    }

    public function set_option(WP_REST_Request $request): WP_REST_Response {
        $key = sanitize_key($request->get_param('key'));
        $value = $request->get_param('value');
        $autoload = $request->get_param('autoload') ? 'yes' : 'no';

        $updated = update_option($key, $value, $autoload);

        return MCP_Endpoints::success([
            'key' => $key,
            'value' => $value,
            'created' => !$this->option_exists($key),
        ]);
    }

    public function delete_option(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $key = sanitize_key($request->get_param('key'));

        if (!$this->option_exists($key)) {
            return MCP_Endpoints::error("Option '{$key}' not found", 'option_not_found', 404);
        }

        $deleted = delete_option($key);

        return MCP_Endpoints::success([
            'deleted' => $deleted,
            'key' => $key,
        ]);
    }

    public function list_options(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $prefix = sanitize_key($request->get_param('prefix'));
        $per_page = absint($request->get_param('per_page'));

        $where = '';
        if ($prefix) {
            $where = $wpdb->prepare(" WHERE option_name LIKE %s", $prefix . '%');
        }

        $options = $wpdb->get_results(
            "SELECT option_name, option_value, autoload
             FROM {$wpdb->options}
             {$where}
             ORDER BY option_name ASC
             LIMIT {$per_page}"
        );

        // Unserialize values where applicable
        $options = array_map(function($opt) {
            return [
                'key' => $opt->option_name,
                'value' => maybe_unserialize($opt->option_value),
                'autoload' => $opt->autoload === 'yes',
            ];
        }, $options);

        return MCP_Endpoints::success([
            'options' => $options,
            'count' => count($options),
        ]);
    }

    public function bulk_get_options(WP_REST_Request $request): WP_REST_Response {
        $keys = $request->get_param('keys');

        $results = [];
        foreach ($keys as $key) {
            $key = sanitize_key($key);
            $results[$key] = get_option($key);
        }

        return MCP_Endpoints::success([
            'options' => $results,
        ]);
    }

    private function option_exists(string $key): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
            $key
        ));
    }
}
