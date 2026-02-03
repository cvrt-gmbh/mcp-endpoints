<?php
/**
 * Theme management endpoints - install, update from repository
 */

defined('ABSPATH') || exit;

class MCP_Themes_Endpoint {

    public function register_routes(): void {
        // Install theme from WordPress.org
        register_rest_route(MCP_Endpoints::NAMESPACE, '/themes/install', [
            'methods' => 'POST',
            'callback' => [$this, 'install_theme'],
            'permission_callback' => [MCP_Endpoints::class, 'check_install_permission'],
            'args' => [
                'slug' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Theme slug from WordPress.org',
                ],
                'activate' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Activate after installation',
                ],
            ],
        ]);

        // Update theme
        register_rest_route(MCP_Endpoints::NAMESPACE, '/themes/update', [
            'methods' => 'POST',
            'callback' => [$this, 'update_theme'],
            'permission_callback' => [MCP_Endpoints::class, 'check_install_permission'],
            'args' => [
                'stylesheet' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Theme stylesheet (folder name)',
                ],
            ],
        ]);

        // Update all themes
        register_rest_route(MCP_Endpoints::NAMESPACE, '/themes/update-all', [
            'methods' => 'POST',
            'callback' => [$this, 'update_all_themes'],
            'permission_callback' => [MCP_Endpoints::class, 'check_install_permission'],
        ]);

        // Search WordPress.org themes
        register_rest_route(MCP_Endpoints::NAMESPACE, '/themes/search', [
            'methods' => 'GET',
            'callback' => [$this, 'search_themes'],
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

        // Delete theme
        register_rest_route(MCP_Endpoints::NAMESPACE, '/themes/delete', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_theme'],
            'permission_callback' => [MCP_Endpoints::class, 'check_install_permission'],
            'args' => [
                'stylesheet' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);
    }

    public function install_theme(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/theme-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        $slug = sanitize_key($request->get_param('slug'));
        $activate = $request->get_param('activate');

        // Get theme info from WordPress.org
        $api = themes_api('theme_information', [
            'slug' => $slug,
            'fields' => ['sections' => false],
        ]);

        if (is_wp_error($api)) {
            return MCP_Endpoints::error("Theme '{$slug}' not found on WordPress.org", 'theme_not_found', 404);
        }

        // Install
        $upgrader = new Theme_Upgrader(new Quiet_Skin());
        $result = $upgrader->install($api->download_link);

        if (is_wp_error($result)) {
            return $result;
        }

        if ($result === false) {
            return MCP_Endpoints::error('Installation failed', 'install_failed');
        }

        // Activate if requested
        if ($activate) {
            switch_theme($slug);
        }

        return MCP_Endpoints::success([
            'installed' => true,
            'activated' => $activate,
            'stylesheet' => $slug,
            'name' => $api->name,
            'version' => $api->version,
        ]);
    }

    public function update_theme(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        $stylesheet = sanitize_text_field($request->get_param('stylesheet'));

        // Check theme exists
        $theme = wp_get_theme($stylesheet);
        if (!$theme->exists()) {
            return MCP_Endpoints::error("Theme '{$stylesheet}' not found", 'theme_not_found', 404);
        }

        $upgrader = new Theme_Upgrader(new Quiet_Skin());
        $result = $upgrader->upgrade($stylesheet);

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'updated' => $result !== false,
            'stylesheet' => $stylesheet,
        ]);
    }

    public function update_all_themes(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        wp_update_themes();
        $updates = get_site_transient('update_themes');

        if (empty($updates->response)) {
            return MCP_Endpoints::success([
                'updated' => [],
                'message' => 'All themes are up to date',
            ]);
        }

        $themes_to_update = array_keys($updates->response);
        $upgrader = new Theme_Upgrader(new Quiet_Skin());
        $results = $upgrader->bulk_upgrade($themes_to_update);

        $updated = [];
        $failed = [];

        foreach ($results as $theme => $result) {
            if ($result === true || is_array($result)) {
                $updated[] = $theme;
            } else {
                $failed[] = $theme;
            }
        }

        return MCP_Endpoints::success([
            'updated' => $updated,
            'failed' => $failed,
        ]);
    }

    public function search_themes(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/theme-install.php';

        $search = sanitize_text_field($request->get_param('search'));
        $per_page = absint($request->get_param('per_page'));

        $api = themes_api('query_themes', [
            'search' => $search,
            'per_page' => $per_page,
            'fields' => [
                'description' => true,
                'sections' => false,
                'screenshot_url' => false,
            ],
        ]);

        if (is_wp_error($api)) {
            return $api;
        }

        $themes = array_map(function($theme) {
            return [
                'name' => $theme->name,
                'slug' => $theme->slug,
                'version' => $theme->version,
                'author' => strip_tags($theme->author),
                'rating' => $theme->rating,
                'description' => wp_trim_words($theme->description, 30),
            ];
        }, $api->themes);

        return MCP_Endpoints::success([
            'total' => $api->info['results'],
            'themes' => $themes,
        ]);
    }

    public function delete_theme(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        $stylesheet = sanitize_text_field($request->get_param('stylesheet'));

        // Check theme exists
        $theme = wp_get_theme($stylesheet);
        if (!$theme->exists()) {
            return MCP_Endpoints::error("Theme '{$stylesheet}' not found", 'theme_not_found', 404);
        }

        // Can't delete active theme
        if ($stylesheet === get_stylesheet()) {
            return MCP_Endpoints::error('Cannot delete active theme', 'active_theme', 400);
        }

        $result = delete_theme($stylesheet);

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'deleted' => true,
            'stylesheet' => $stylesheet,
        ]);
    }
}
