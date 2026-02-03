# MCP Endpoints

[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0+-purple.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

WordPress plugin that extends the REST API with additional endpoints for MCP (Model Context Protocol) servers.

Adds capabilities missing from the standard WordPress REST API: plugin/theme installation from WordPress.org, core updates, database operations, and more.

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Application Password authentication (WP 5.6+)

## Installation

1. Download or clone this repository
2. Upload to `wp-content/plugins/mcp-endpoints`
3. Activate the plugin

## Authentication

All endpoints require admin-level authentication via Application Passwords:

```bash
curl -u "admin:xxxx xxxx xxxx xxxx" https://example.com/wp-json/mcp/v1/...
```

## Endpoints

All endpoints use the `mcp/v1` namespace.

### Plugins

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/plugins/search?search=...` | Search WordPress.org plugins |
| POST | `/plugins/install` | Install plugin from WordPress.org |
| POST | `/plugins/update` | Update single plugin |
| POST | `/plugins/update-all` | Update all plugins |

#### Install Plugin

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/plugins/install \
  -H "Content-Type: application/json" \
  -d '{"slug": "akismet", "activate": true}'
```

### Themes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/themes/search?search=...` | Search WordPress.org themes |
| POST | `/themes/install` | Install theme from WordPress.org |
| POST | `/themes/update` | Update single theme |
| POST | `/themes/update-all` | Update all themes |
| DELETE | `/themes/delete` | Delete theme |

### Core

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/core/version` | Get WordPress version and update status |
| GET | `/core/system-info` | Get comprehensive system info |
| POST | `/core/check-updates` | Force check for all updates |
| POST | `/core/update` | Update WordPress core |
| POST | `/core/flush-rewrite` | Flush rewrite rules |
| POST | `/core/flush-cache` | Clear all caches and transients |

### Database

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/db/tables` | List tables with sizes |
| POST | `/db/search-replace` | Search and replace in database |
| POST | `/db/optimize` | Optimize all tables |
| POST | `/db/clean-revisions` | Clean post revisions |
| POST | `/db/clean-comments` | Delete spam/trash comments |

#### Search Replace (Dry Run)

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/db/search-replace \
  -H "Content-Type: application/json" \
  -d '{"search": "http://old-domain.com", "replace": "https://new-domain.com", "dry_run": true}'
```

### Options

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/options` | List options (with prefix filter) |
| GET | `/options/{key}` | Get single option |
| POST | `/options/{key}` | Set option value |
| DELETE | `/options/{key}` | Delete option |
| POST | `/options-bulk` | Get multiple options at once |

### Custom Post Types

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cpt` | List all registered post types |
| GET | `/cpt/{type}` | Get post type schema and counts |
| GET | `/cpt/{type}/posts` | Get posts of a specific type |
| POST | `/cpt/{type}/posts` | Create post of specific type |
| PUT | `/cpt/{type}/posts/{id}` | Update post |
| DELETE | `/cpt/{type}/posts/{id}` | Delete post |

#### Create Custom Post

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/cpt/product/posts \
  -H "Content-Type: application/json" \
  -d '{"title": "New Product", "content": "Description", "status": "publish", "meta": {"price": "99.00"}}'
```

### Taxonomies

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/taxonomies` | List all public taxonomies |
| GET | `/taxonomies/{taxonomy}` | Get taxonomy details |
| GET | `/taxonomies/{taxonomy}/terms` | Get terms of a taxonomy |
| POST | `/taxonomies/{taxonomy}/terms` | Create term |
| PUT | `/taxonomies/{taxonomy}/terms/{id}` | Update term |
| DELETE | `/taxonomies/{taxonomy}/terms/{id}` | Delete term |
| POST | `/taxonomies/assign` | Assign terms to post |

### Menus

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/menus` | List all navigation menus |
| GET | `/menus/locations` | Get registered menu locations |
| GET | `/menus/{id}` | Get menu with all items |
| POST | `/menus` | Create new menu |
| PUT | `/menus/{id}` | Update menu |
| DELETE | `/menus/{id}` | Delete menu |
| POST | `/menus/{id}/items` | Add menu item |
| PUT | `/menus/items/{item_id}` | Update menu item |
| DELETE | `/menus/items/{item_id}` | Delete menu item |
| POST | `/menus/locations/assign` | Assign menu to location |

### Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/users` | List users with filtering |
| GET | `/users/{id}` | Get single user details |
| POST | `/users` | Create new user |
| PUT | `/users/{id}` | Update user |
| DELETE | `/users/{id}` | Delete user |
| GET | `/users/roles` | List all roles with capabilities |
| PUT | `/users/{id}/role` | Update user role |
| GET | `/users/{id}/meta` | Get user meta |
| POST | `/users/{id}/meta` | Update user meta |

### Health & Diagnostics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Site health status and score |
| GET | `/health/debug` | Debug info (paths, constants) |
| GET | `/health/php` | PHP configuration info |
| GET | `/health/plugins` | Plugin health and updates |
| GET | `/health/cron` | Cron jobs status |
| POST | `/health/cron/run` | Run specific cron job |

#### Health Check

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/health
```

Returns health score, WordPress/PHP/DB versions, update counts, and issues.

## Usage with MCP Server

After installing this plugin, the `wordpress-mcp` server can be extended to use these endpoints for:

- Installing plugins: `POST /mcp/v1/plugins/install`
- Updating everything: `POST /mcp/v1/plugins/update-all`, `/themes/update-all`, `/core/update`
- Database migrations: `POST /mcp/v1/db/search-replace`
- System maintenance: `POST /mcp/v1/core/flush-cache`

## Security

- All endpoints require `manage_options` or `install_plugins` capability
- Uses standard WordPress nonce and capability checks
- Sanitizes all input parameters
- Supports Application Passwords (recommended)

## License

MIT
