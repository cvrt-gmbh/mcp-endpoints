<?php
/**
 * Database endpoints - search/replace, optimize, export
 */

defined('ABSPATH') || exit;

class MCP_Database_Endpoint {

    public function register_routes(): void {
        // Search and replace
        register_rest_route(MCP_Endpoints::NAMESPACE, '/db/search-replace', [
            'methods' => 'POST',
            'callback' => [$this, 'search_replace'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'search' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'replace' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'tables' => [
                    'type' => 'array',
                    'default' => [],
                    'description' => 'Specific tables (empty = all)',
                ],
                'dry_run' => [
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Preview changes without applying',
                ],
            ],
        ]);

        // Optimize tables
        register_rest_route(MCP_Endpoints::NAMESPACE, '/db/optimize', [
            'methods' => 'POST',
            'callback' => [$this, 'optimize_tables'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Get table sizes
        register_rest_route(MCP_Endpoints::NAMESPACE, '/db/tables', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tables'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);

        // Clean up post revisions
        register_rest_route(MCP_Endpoints::NAMESPACE, '/db/clean-revisions', [
            'methods' => 'POST',
            'callback' => [$this, 'clean_revisions'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
            'args' => [
                'keep' => [
                    'type' => 'integer',
                    'default' => 5,
                    'description' => 'Number of revisions to keep per post',
                ],
            ],
        ]);

        // Clean up spam and trash comments
        register_rest_route(MCP_Endpoints::NAMESPACE, '/db/clean-comments', [
            'methods' => 'POST',
            'callback' => [$this, 'clean_comments'],
            'permission_callback' => [MCP_Endpoints::class, 'check_admin_permission'],
        ]);
    }

    public function search_replace(WP_REST_Request $request): WP_REST_Response|WP_Error {
        global $wpdb;

        $search = $request->get_param('search');
        $replace = $request->get_param('replace');
        $tables = $request->get_param('tables');
        $dry_run = $request->get_param('dry_run');

        if (empty($search)) {
            return MCP_Endpoints::error('Search string cannot be empty', 'empty_search');
        }

        // Get tables to process
        if (empty($tables)) {
            $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        }

        $results = [];
        $total_changes = 0;

        foreach ($tables as $table) {
            // Get columns for this table
            $columns = $wpdb->get_results("DESCRIBE `{$table}`");

            $table_changes = 0;

            foreach ($columns as $column) {
                $col_name = $column->Field;

                // Only process text-like columns
                if (!preg_match('/(char|text|blob)/i', $column->Type)) {
                    continue;
                }

                // Count occurrences
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM `{$table}` WHERE `{$col_name}` LIKE %s",
                    '%' . $wpdb->esc_like($search) . '%'
                ));

                if ($count > 0) {
                    $table_changes += $count;

                    if (!$dry_run) {
                        $wpdb->query($wpdb->prepare(
                            "UPDATE `{$table}` SET `{$col_name}` = REPLACE(`{$col_name}`, %s, %s) WHERE `{$col_name}` LIKE %s",
                            $search,
                            $replace,
                            '%' . $wpdb->esc_like($search) . '%'
                        ));
                    }
                }
            }

            if ($table_changes > 0) {
                $results[$table] = $table_changes;
                $total_changes += $table_changes;
            }
        }

        return MCP_Endpoints::success([
            'dry_run' => $dry_run,
            'search' => $search,
            'replace' => $replace,
            'total_changes' => $total_changes,
            'tables' => $results,
        ]);
    }

    public function optimize_tables(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        $optimized = [];

        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE `{$table}`");
            $optimized[] = $table;
        }

        return MCP_Endpoints::success([
            'optimized' => $optimized,
            'count' => count($optimized),
        ]);
    }

    public function get_tables(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $tables = $wpdb->get_results(
            "SELECT table_name AS name,
                    ROUND(data_length / 1024 / 1024, 2) AS data_mb,
                    ROUND(index_length / 1024 / 1024, 2) AS index_mb,
                    table_rows AS rows
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name LIKE '{$wpdb->prefix}%'
             ORDER BY data_length DESC"
        );

        $total_size = array_sum(array_column($tables, 'data_mb'));

        return MCP_Endpoints::success([
            'tables' => $tables,
            'total_size_mb' => round($total_size, 2),
        ]);
    }

    public function clean_revisions(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $keep = absint($request->get_param('keep'));

        // Get all posts with revisions
        $posts_with_revisions = $wpdb->get_col(
            "SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'revision'"
        );

        $deleted = 0;

        foreach ($posts_with_revisions as $post_id) {
            if (!$post_id) continue;

            // Get revisions for this post, ordered by date desc
            $revisions = $wpdb->get_col($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_parent = %d AND post_type = 'revision'
                 ORDER BY post_modified DESC",
                $post_id
            ));

            // Skip the ones we want to keep
            $to_delete = array_slice($revisions, $keep);

            foreach ($to_delete as $revision_id) {
                wp_delete_post_revision($revision_id);
                $deleted++;
            }
        }

        return MCP_Endpoints::success([
            'deleted' => $deleted,
            'kept_per_post' => $keep,
        ]);
    }

    public function clean_comments(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $spam = $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'");
        $trash = $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'");

        return MCP_Endpoints::success([
            'spam_deleted' => $spam,
            'trash_deleted' => $trash,
        ]);
    }
}
