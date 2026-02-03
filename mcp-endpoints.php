<?php
/**
 * Plugin Name: MCP Endpoints
 * Plugin URI: https://github.com/cvrt-gmbh/mcp-endpoints
 * Description: Extends WordPress REST API with additional endpoints for MCP (Model Context Protocol) servers. Adds plugin/theme installation, WP-CLI commands, database operations, and more.
 * Version: 1.0.0
 * Author: CAVORT
 * Author URI: https://cavort.de
 * License: MIT
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: mcp-endpoints
 */

defined('ABSPATH') || exit;

define('MCP_ENDPOINTS_VERSION', '1.0.0');
define('MCP_ENDPOINTS_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load components
require_once MCP_ENDPOINTS_PLUGIN_DIR . 'includes/class-mcp-endpoints.php';
require_once MCP_ENDPOINTS_PLUGIN_DIR . 'includes/endpoints/class-plugins-endpoint.php';
require_once MCP_ENDPOINTS_PLUGIN_DIR . 'includes/endpoints/class-themes-endpoint.php';
require_once MCP_ENDPOINTS_PLUGIN_DIR . 'includes/endpoints/class-core-endpoint.php';
require_once MCP_ENDPOINTS_PLUGIN_DIR . 'includes/endpoints/class-database-endpoint.php';
require_once MCP_ENDPOINTS_PLUGIN_DIR . 'includes/endpoints/class-options-endpoint.php';

// Initialize
add_action('rest_api_init', function() {
    $endpoints = new MCP_Endpoints();
    $endpoints->register_routes();
});
