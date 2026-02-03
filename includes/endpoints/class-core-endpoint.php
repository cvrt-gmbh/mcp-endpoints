<?php
/**
 * WordPress Core endpoints - version info, updates
 */

defined('ABSPATH') || exit;

class MCP_Core_Endpoint {

    public function register_routes(): void {
        // Get WordPress version and update info
        register_rest_route(MCP_Endpoints::NAMESPACE, '/core/version', [
            'methods' => 'GET',
            'callback' => [$this, 'get_version'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Check for updates
        register_rest_route(MCP_Endpoints::NAMESPACE, '/core/check-updates', [
            'methods' => 'POST',
            'callback' => [$this, 'check_updates'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Update WordPress core
        register_rest_route(MCP_Endpoints::NAMESPACE, '/core/update', [
            'methods' => 'POST',
            'callback' => [$this, 'update_core'],
            'permission_callback' => [MCP_Endpoints::class, 'check_install_permission'],
        ]);

        // Get system info
        register_rest_route(MCP_Endpoints::NAMESPACE, '/core/system-info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_system_info'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Flush rewrite rules
        register_rest_route(MCP_Endpoints::NAMESPACE, '/core/flush-rewrite', [
            'methods' => 'POST',
            'callback' => [$this, 'flush_rewrite_rules'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Clear all caches
        register_rest_route(MCP_Endpoints::NAMESPACE, '/core/flush-cache', [
            'methods' => 'POST',
            'callback' => [$this, 'flush_cache'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);
    }

    public function get_version(WP_REST_Request $request): WP_REST_Response {
        global $wp_version, $required_php_version, $required_mysql_version;

        require_once ABSPATH . 'wp-admin/includes/update.php';
        wp_version_check();

        $updates = get_core_updates();
        $update_available = false;
        $latest_version = $wp_version;

        if (!empty($updates) && $updates[0]->response === 'upgrade') {
            $update_available = true;
            $latest_version = $updates[0]->version;
        }

        return MCP_Endpoints::success([
            'wordpress_version' => $wp_version,
            'php_version' => PHP_VERSION,
            'mysql_version' => $GLOBALS['wpdb']->db_version(),
            'required_php' => $required_php_version,
            'required_mysql' => $required_mysql_version,
            'update_available' => $update_available,
            'latest_version' => $latest_version,
            'multisite' => is_multisite(),
        ]);
    }

    public function check_updates(WP_REST_Request $request): WP_REST_Response {
        require_once ABSPATH . 'wp-admin/includes/update.php';

        // Force update check
        wp_version_check([], true);
        wp_update_plugins();
        wp_update_themes();

        $core_updates = get_core_updates();
        $plugin_updates = get_site_transient('update_plugins');
        $theme_updates = get_site_transient('update_themes');

        return MCP_Endpoints::success([
            'core' => !empty($core_updates) && $core_updates[0]->response === 'upgrade'
                ? $core_updates[0]->version
                : null,
            'plugins' => !empty($plugin_updates->response)
                ? count($plugin_updates->response)
                : 0,
            'themes' => !empty($theme_updates->response)
                ? count($theme_updates->response)
                : 0,
        ]);
    }

    public function update_core(WP_REST_Request $request): WP_REST_Response|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        $updates = get_core_updates();

        if (empty($updates) || $updates[0]->response !== 'upgrade') {
            return MCP_Endpoints::success([
                'updated' => false,
                'message' => 'WordPress is already up to date',
            ]);
        }

        $upgrader = new Core_Upgrader(new Quiet_Skin());
        $result = $upgrader->upgrade($updates[0]);

        if (is_wp_error($result)) {
            return $result;
        }

        return MCP_Endpoints::success([
            'updated' => true,
            'version' => $updates[0]->version,
        ]);
    }

    public function get_system_info(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $upload_dir = wp_upload_dir();

        return MCP_Endpoints::success([
            'wordpress' => [
                'version' => get_bloginfo('version'),
                'url' => home_url(),
                'admin_url' => admin_url(),
                'multisite' => is_multisite(),
                'debug' => WP_DEBUG,
            ],
            'server' => [
                'php' => PHP_VERSION,
                'mysql' => $wpdb->db_version(),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                'max_upload' => wp_max_upload_size(),
                'memory_limit' => WP_MEMORY_LIMIT,
            ],
            'paths' => [
                'abspath' => ABSPATH,
                'content' => WP_CONTENT_DIR,
                'plugins' => WP_PLUGIN_DIR,
                'uploads' => $upload_dir['basedir'],
                'themes' => get_theme_root(),
            ],
            'counts' => [
                'posts' => (int) wp_count_posts()->publish,
                'pages' => (int) wp_count_posts('page')->publish,
                'users' => (int) count_users()['total_users'],
                'plugins' => count(get_plugins()),
                'themes' => count(wp_get_themes()),
            ],
        ]);
    }

    public function flush_rewrite_rules(WP_REST_Request $request): WP_REST_Response {
        flush_rewrite_rules();

        return MCP_Endpoints::success([
            'flushed' => true,
        ]);
    }

    public function flush_cache(WP_REST_Request $request): WP_REST_Response {
        // Clear WordPress object cache
        wp_cache_flush();

        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");

        return MCP_Endpoints::success([
            'flushed' => true,
        ]);
    }
}
