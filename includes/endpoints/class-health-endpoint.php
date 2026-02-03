<?php
/**
 * Health endpoints - site health, diagnostics, debug info
 */

defined('ABSPATH') || exit;

class MCP_Health_Endpoint {

    public function register_routes(): void {
        // Get site health status
        register_rest_route(MCP_Endpoints::NAMESPACE, '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'get_health'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get debug info
        register_rest_route(MCP_Endpoints::NAMESPACE, '/health/debug', [
            'methods' => 'GET',
            'callback' => [$this, 'get_debug_info'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get PHP info
        register_rest_route(MCP_Endpoints::NAMESPACE, '/health/php', [
            'methods' => 'GET',
            'callback' => [$this, 'get_php_info'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get active plugins
        register_rest_route(MCP_Endpoints::NAMESPACE, '/health/plugins', [
            'methods' => 'GET',
            'callback' => [$this, 'get_plugins_health'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get cron status
        register_rest_route(MCP_Endpoints::NAMESPACE, '/health/cron', [
            'methods' => 'GET',
            'callback' => [$this, 'get_cron_status'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Run specific cron job
        register_rest_route(MCP_Endpoints::NAMESPACE, '/health/cron/run', [
            'methods' => 'POST',
            'callback' => [$this, 'run_cron'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'hook' => ['required' => true, 'type' => 'string'],
            ],
        ]);
    }

    public function get_health(WP_REST_Request $request): WP_REST_Response {
        global $wp_version, $wpdb;

        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        // Check for updates
        wp_version_check();
        $core_updates = get_core_updates();
        $plugin_updates = get_site_transient('update_plugins');
        $theme_updates = get_site_transient('update_themes');

        $update_count = 0;
        if (!empty($core_updates) && $core_updates[0]->response === 'upgrade') {
            $update_count++;
        }
        $update_count += !empty($plugin_updates->response) ? count($plugin_updates->response) : 0;
        $update_count += !empty($theme_updates->response) ? count($theme_updates->response) : 0;

        // Calculate health score
        $issues = [];

        if (WP_DEBUG) {
            $issues[] = 'WP_DEBUG is enabled';
        }
        if (!is_ssl()) {
            $issues[] = 'Site not using HTTPS';
        }
        if ($update_count > 0) {
            $issues[] = "{$update_count} updates available";
        }

        $health_score = 100 - (count($issues) * 10);
        $health_status = $health_score >= 80 ? 'good' : ($health_score >= 60 ? 'warning' : 'critical');

        return MCP_Endpoints::success([
            'status' => $health_status,
            'score' => max(0, $health_score),
            'wordpress' => [
                'version' => $wp_version,
                'update_available' => !empty($core_updates) && $core_updates[0]->response === 'upgrade',
            ],
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
            ],
            'database' => [
                'version' => $wpdb->db_version(),
                'prefix' => $wpdb->prefix,
            ],
            'updates' => [
                'total' => $update_count,
                'plugins' => !empty($plugin_updates->response) ? count($plugin_updates->response) : 0,
                'themes' => !empty($theme_updates->response) ? count($theme_updates->response) : 0,
            ],
            'debug' => [
                'wp_debug' => WP_DEBUG,
                'wp_debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
                'wp_debug_display' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY,
            ],
            'ssl' => is_ssl(),
            'multisite' => is_multisite(),
            'issues' => $issues,
        ]);
    }

    public function get_debug_info(WP_REST_Request $request): WP_REST_Response {
        global $wp_version, $wpdb;

        if (!class_exists('WP_Debug_Data')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
        }

        // Get basic debug info without heavy Site Health checks
        $upload_dir = wp_upload_dir();

        return MCP_Endpoints::success([
            'wordpress' => [
                'version' => $wp_version,
                'home_url' => home_url(),
                'site_url' => site_url(),
                'is_multisite' => is_multisite(),
                'max_upload_size' => wp_max_upload_size(),
                'memory_limit' => WP_MEMORY_LIMIT,
                'max_memory_limit' => defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT : '',
                'debug_mode' => WP_DEBUG,
                'cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
                'language' => get_locale(),
                'timezone' => wp_timezone_string(),
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            ],
            'database' => [
                'server_version' => $wpdb->db_version(),
                'client_version' => $wpdb->db_server_info(),
                'database_name' => DB_NAME,
                'table_prefix' => $wpdb->prefix,
                'charset' => $wpdb->charset,
                'collate' => $wpdb->collate,
            ],
            'paths' => [
                'wordpress' => ABSPATH,
                'content' => WP_CONTENT_DIR,
                'plugins' => WP_PLUGIN_DIR,
                'uploads' => $upload_dir['basedir'],
                'themes' => get_theme_root(),
            ],
            'constants' => [
                'WP_DEBUG' => WP_DEBUG,
                'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false,
                'WP_DEBUG_DISPLAY' => defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : true,
                'SCRIPT_DEBUG' => defined('SCRIPT_DEBUG') ? SCRIPT_DEBUG : false,
                'WP_CACHE' => defined('WP_CACHE') ? WP_CACHE : false,
                'CONCATENATE_SCRIPTS' => defined('CONCATENATE_SCRIPTS') ? CONCATENATE_SCRIPTS : true,
                'COMPRESS_SCRIPTS' => defined('COMPRESS_SCRIPTS') ? COMPRESS_SCRIPTS : true,
                'COMPRESS_CSS' => defined('COMPRESS_CSS') ? COMPRESS_CSS : true,
            ],
        ]);
    }

    public function get_php_info(WP_REST_Request $request): WP_REST_Response {
        $extensions = get_loaded_extensions();
        sort($extensions);

        return MCP_Endpoints::success([
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'display_errors' => ini_get('display_errors'),
            'error_reporting' => error_reporting(),
            'opcache' => [
                'enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
            ],
            'extensions' => $extensions,
            'disabled_functions' => array_filter(array_map('trim', explode(',', ini_get('disable_functions')))),
        ]);
    }

    public function get_plugins_health(WP_REST_Request $request): WP_REST_Response {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        wp_update_plugins();
        $updates = get_site_transient('update_plugins');
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);

        $plugins = [];
        foreach ($all_plugins as $file => $data) {
            $needs_update = isset($updates->response[$file]);
            $plugins[] = [
                'file' => $file,
                'name' => $data['Name'],
                'version' => $data['Version'],
                'active' => in_array($file, $active_plugins),
                'update_available' => $needs_update,
                'new_version' => $needs_update ? $updates->response[$file]->new_version : null,
            ];
        }

        $active_count = count($active_plugins);
        $update_count = !empty($updates->response) ? count($updates->response) : 0;

        return MCP_Endpoints::success([
            'plugins' => $plugins,
            'total' => count($plugins),
            'active' => $active_count,
            'inactive' => count($plugins) - $active_count,
            'updates_available' => $update_count,
        ]);
    }

    public function get_cron_status(WP_REST_Request $request): WP_REST_Response {
        $crons = _get_cron_array();
        $schedules = wp_get_schedules();

        $events = [];
        foreach ($crons as $timestamp => $hooks) {
            foreach ($hooks as $hook => $args) {
                foreach ($args as $key => $data) {
                    $events[] = [
                        'hook' => $hook,
                        'timestamp' => $timestamp,
                        'next_run' => date('Y-m-d H:i:s', $timestamp),
                        'schedule' => $data['schedule'] ?: 'single',
                        'interval' => $data['interval'] ?? null,
                        'args' => $data['args'],
                    ];
                }
            }
        }

        // Sort by timestamp
        usort($events, fn($a, $b) => $a['timestamp'] - $b['timestamp']);

        return MCP_Endpoints::success([
            'cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
            'schedules' => $schedules,
            'events' => array_slice($events, 0, 50), // Limit to 50 events
            'total_events' => count($events),
        ]);
    }

    public function run_cron(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $hook = sanitize_text_field($request->get_param('hook'));

        $crons = _get_cron_array();
        $found = false;

        foreach ($crons as $timestamp => $hooks) {
            if (isset($hooks[$hook])) {
                foreach ($hooks[$hook] as $key => $data) {
                    $found = true;
                    do_action_ref_array($hook, $data['args']);
                    break 2;
                }
            }
        }

        if (!$found) {
            return MCP_Endpoints::error("Cron hook '{$hook}' not found", 'not_found', 404);
        }

        return MCP_Endpoints::success([
            'hook' => $hook,
            'executed' => true,
        ]);
    }
}
