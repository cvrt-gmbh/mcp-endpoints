<?php
/**
 * Main MCP Endpoints class
 */

defined('ABSPATH') || exit;

class MCP_Endpoints {

    const NAMESPACE = 'mcp/v1';

    private array $endpoints = [];

    public function __construct() {
        $this->endpoints = [
            new MCP_Plugins_Endpoint(),
            new MCP_Themes_Endpoint(),
            new MCP_Core_Endpoint(),
            new MCP_Database_Endpoint(),
            new MCP_Options_Endpoint(),
        ];
    }

    public function register_routes(): void {
        foreach ($this->endpoints as $endpoint) {
            $endpoint->register_routes();
        }
    }

    /**
     * Check if current user can manage options (admin capability)
     */
    public static function check_admin_permission(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if current user can install plugins
     */
    public static function check_install_permission(): bool {
        return current_user_can('install_plugins');
    }

    /**
     * Standard error response
     */
    public static function error(string $message, string $code = 'error', int $status = 400): WP_Error {
        return new WP_Error($code, $message, ['status' => $status]);
    }

    /**
     * Standard success response
     */
    public static function success(array $data): WP_REST_Response {
        return new WP_REST_Response($data, 200);
    }
}
