# MCP Endpoints

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
