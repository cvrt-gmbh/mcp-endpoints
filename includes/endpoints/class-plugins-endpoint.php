<?php
/**
 * Plugin management endpoints - install, update from repository
 */

defined('ABSPATH') || exit;

class MCP_Plugins_Endpoint {

    public function register_routes(): void {
        // Install plugin from WordPress.org
        register_rest_route(MCP_Endpoints::NAMESPACE, '/plugins/install', [
            'methods' => 'POST',
            'callback' => [$this, 'install_plugin'],
            'permission_callback' => [MCP_Endpoints::class, 'check_install_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Plugin slug from WordPress.org',
                ],
                'activate' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Activate after installation',
                ],
            ],
        ]);

        // Update plugin
        register_rest_route(MCP_Endpoints::NAMESPACE, '/plugins/update', [
            'methods' => 'POST',
            'callback' => [$this, 'update_plugin'],
            'permission_callback' => [MCP_Endpoints::class, 'check_install_permission'],
            'args' => [
                'plugin' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Plugin file path (e.g., akismet/akismet.php)',
                ],
            ],
        ]);

        // Update all plugins
        register_rest_route(MCP_Endpoints::NAMESPACE, '/plugins/update-all', [
            'methods' => 'POST',
            'callback' => [$this, 'update_all_plugins'],
            'permission_callback' => [MCP_Endpoints::class, 'check_install_permission'],
        ]);

        // Search WordPress.org plugins
        register_rest_route(MCP_Endpoints::NAMESPACE, '/plugins/search', [
            'methods' => 'GET',
            'callback' => [$this, 'search_plugins'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'search' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 10,
                ],
            ],
        ]);
    }

    public function install_plugin(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $slug = sanitize_key($request->get_param('slug'));
        $activate = $request->get_param('activate');

        // Get plugin info from WordPress.org
        $api = plugins_api('plugin_information', [
            'slug' => $slug,
            'fields' => ['sections' => false],
        ]);

        if (is_wp_error($api)) {
            return MCP_Endpoints::error("Plugin '{$slug}' not found on WordPress.org", 'plugin_not_found', 404);
        }

        // Install
        $upgrader = new Plugin_Upgrader(new Quiet_Skin());
        $result = $upgrader->install($api->download_link);

        if (is_wp_error($result)) {
            return $result;
        }

        if ($result === false) {
            return MCP_Endpoints::error('Installation failed', 'install_failed');
        }

        $plugin_file = $upgrader->plugin_info();

        // Activate if requested
        if ($activate && $plugin_file) {
            $activated = activate_plugin($plugin_file);
            if (is_wp_error($activated)) {
                return MCP_Endpoints::success([
                    'installed' => true,
                    'activated' => false,
                    'plugin' => $plugin_file,
                    'activation_error' => $activated->get_error_message(),
                ]);
            }
        }

        return MCP_Endpoints::success([
            'installed' => true,
            'activated' => $activate,
            'plugin' => $plugin_file,
            'name' => $api->name,
            'version' => $api->version,
        ]);
    }

    public function update_plugin(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugin = sanitize_text_field($request->get_param('plugin'));

        // Check plugin exists
        $all_plugins = get_plugins();
        if (!isset($all_plugins[$plugin])) {
            return MCP_Endpoints::error("Plugin '{$plugin}' not found", 'plugin_not_found', 404);
        }

        $upgrader = new Plugin_Upgrader(new Quiet_Skin());
        $result = $upgrader->upgrade($plugin);

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'updated' => $result !== false,
            'plugin' => $plugin,
        ]);
    }

    public function update_all_plugins(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        wp_update_plugins();
        $updates = get_site_transient('update_plugins');

        if (empty($updates->response)) {
            return MCP_Endpoints::success([
                'updated' => [],
                'message' => 'All plugins are up to date',
            ]);
        }

        $plugins_to_update = array_keys($updates->response);
        $upgrader = new Plugin_Upgrader(new Quiet_Skin());
        $results = $upgrader->bulk_upgrade($plugins_to_update);

        $updated = [];
        $failed = [];

        foreach ($results as $plugin => $result) {
            if ($result === true || is_array($result)) {
                $updated[] = $plugin;
            } else {
                $failed[] = $plugin;
            }
        }

        return MCP_Endpoints::success([
            'updated' => $updated,
            'failed' => $failed,
        ]);
    }

    public function search_plugins(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $search = sanitize_text_field($request->get_param('search'));
        $per_page = absint($request->get_param('per_page'));

        $api = plugins_api('query_plugins', [
            'search' => $search,
            'per_page' => $per_page,
            'fields' => [
                'short_description' => true,
                'icons' => false,
                'banners' => false,
                'sections' => false,
            ],
        ]);

        if (is_wp_error($api)) {
            return $api;
        }

        $plugins = array_map(function($plugin) {
            return [
                'name' => $plugin->name,
                'slug' => $plugin->slug,
                'version' => $plugin->version,
                'author' => strip_tags($plugin->author),
                'rating' => $plugin->rating,
                'active_installs' => $plugin->active_installs,
                'description' => $plugin->short_description,
            ];
        }, $api->plugins);

        return MCP_Endpoints::success([
            'total' => $api->info['results'],
            'plugins' => $plugins,
        ]);
    }
}

// Quiet skin for background operations
class Quiet_Skin extends WP_Upgrader_Skin {
    public function feedback($feedback, ...$args) {}
    public function header() {}
    public function footer() {}
}
