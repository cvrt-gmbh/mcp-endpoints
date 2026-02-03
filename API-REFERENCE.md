# MCP Endpoints API Reference

> **Version:** 1.2.0
> **Namespace:** `mcp/v1`
> **Base URL:** `https://your-site.com/wp-json/mcp/v1`

## Table of Contents

- [Authentication](#authentication)
- [Response Format](#response-format)
- [Error Codes](#error-codes)
- [Endpoints](#endpoints)
  - [Plugins](#plugins)
  - [Themes](#themes)
  - [Core](#core)
  - [Database](#database)
  - [Options](#options)
  - [Custom Post Types](#custom-post-types)
  - [Taxonomies](#taxonomies)
  - [Menus](#menus)
  - [Users](#users)
  - [Media](#media)
  - [Widgets](#widgets)
  - [Health](#health)

---

## Authentication

All endpoints require admin-level authentication via **Application Passwords** (WordPress 5.6+).

### Setup

1. Go to **Users → Profile** in WordPress admin
2. Scroll to **Application Passwords**
3. Create a new password for your MCP integration

### Usage

```bash
# Basic Auth header
curl -u "username:xxxx xxxx xxxx xxxx" https://example.com/wp-json/mcp/v1/...

# Or Authorization header
curl -H "Authorization: Basic base64(username:password)" https://example.com/wp-json/mcp/v1/...
```

### Required Capabilities

| Capability | Endpoints |
|------------|-----------|
| `manage_options` | Most endpoints (admin access) |
| `install_plugins` | Plugin/theme install, update, core update |

---

## Response Format

### Success Response

```json
{
  "key": "value",
  "another_key": "another_value"
}
```

### Error Response

```json
{
  "code": "error_code",
  "message": "Human readable error message",
  "data": {
    "status": 400
  }
}
```

---

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `not_found` | 404 | Resource not found |
| `plugin_not_found` | 404 | Plugin not in WordPress.org repository |
| `theme_not_found` | 404 | Theme not in WordPress.org repository |
| `option_not_found` | 404 | Option does not exist |
| `install_failed` | 400 | Installation failed |
| `delete_failed` | 400 | Deletion failed |
| `active_theme` | 400 | Cannot delete active theme |
| `username_exists` | 400 | Username already taken |
| `email_exists` | 400 | Email already in use |
| `invalid_role` | 400 | Role does not exist |
| `cannot_delete_self` | 400 | Cannot delete current user |
| `empty_search` | 400 | Search string cannot be empty |

---

## Endpoints

---

## Plugins

### Search Plugins

Search WordPress.org plugin repository.

```
GET /plugins/search
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `search` | string | Yes | - | Search query |
| `per_page` | integer | No | 10 | Results per page |

**Example:**

```bash
curl -u "admin:xxxx" "https://example.com/wp-json/mcp/v1/plugins/search?search=security&per_page=5"
```

**Response:**

```json
{
  "total": 1234,
  "plugins": [
    {
      "name": "Wordfence Security",
      "slug": "wordfence",
      "version": "7.10.0",
      "author": "Wordfence",
      "rating": 96,
      "active_installs": 4000000,
      "description": "Firewall, Malware Scanner, and Security..."
    }
  ]
}
```

---

### Install Plugin

Install a plugin from WordPress.org.

```
POST /plugins/install
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `slug` | string | Yes | - | Plugin slug from WordPress.org |
| `activate` | boolean | No | false | Activate after installation |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/plugins/install \
  -H "Content-Type: application/json" \
  -d '{"slug": "akismet", "activate": true}'
```

**Response:**

```json
{
  "installed": true,
  "activated": true,
  "plugin": "akismet/akismet.php",
  "name": "Akismet Anti-spam",
  "version": "5.3"
}
```

---

### Update Plugin

Update a single installed plugin.

```
POST /plugins/update
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `plugin` | string | Yes | Plugin file path (e.g., `akismet/akismet.php`) |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/plugins/update \
  -H "Content-Type: application/json" \
  -d '{"plugin": "akismet/akismet.php"}'
```

**Response:**

```json
{
  "updated": true,
  "plugin": "akismet/akismet.php"
}
```

---

### Update All Plugins

Update all plugins with available updates.

```
POST /plugins/update-all
```

**Parameters:** None

**Example:**

```bash
curl -X POST -u "admin:xxxx" https://example.com/wp-json/mcp/v1/plugins/update-all
```

**Response:**

```json
{
  "updated": ["akismet/akismet.php", "jetpack/jetpack.php"],
  "failed": []
}
```

---

## Themes

### Search Themes

Search WordPress.org theme repository.

```
GET /themes/search
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `search` | string | Yes | - | Search query |
| `per_page` | integer | No | 10 | Results per page |

**Example:**

```bash
curl -u "admin:xxxx" "https://example.com/wp-json/mcp/v1/themes/search?search=developer"
```

**Response:**

```json
{
  "total": 456,
  "themes": [
    {
      "name": "Developer Theme",
      "slug": "developer-theme",
      "version": "1.0.0",
      "author": "Developer",
      "rating": 90,
      "description": "A theme for developers..."
    }
  ]
}
```

---

### Install Theme

Install a theme from WordPress.org.

```
POST /themes/install
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `slug` | string | Yes | - | Theme slug from WordPress.org |
| `activate` | boolean | No | false | Activate after installation |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/themes/install \
  -H "Content-Type: application/json" \
  -d '{"slug": "developer-theme", "activate": true}'
```

**Response:**

```json
{
  "installed": true,
  "activated": true,
  "stylesheet": "developer-theme",
  "name": "Developer Theme",
  "version": "1.0.0"
}
```

---

### Update Theme

Update a single installed theme.

```
POST /themes/update
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `stylesheet` | string | Yes | Theme folder name |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/themes/update \
  -H "Content-Type: application/json" \
  -d '{"stylesheet": "developer-theme"}'
```

**Response:**

```json
{
  "updated": true,
  "stylesheet": "developer-theme"
}
```

---

### Update All Themes

Update all themes with available updates.

```
POST /themes/update-all
```

**Parameters:** None

**Example:**

```bash
curl -X POST -u "admin:xxxx" https://example.com/wp-json/mcp/v1/themes/update-all
```

**Response:**

```json
{
  "updated": ["developer-theme", "twenty-twentyfour"],
  "failed": []
}
```

---

### Delete Theme

Delete an inactive theme.

```
DELETE /themes/delete
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `stylesheet` | string | Yes | Theme folder name |

**Example:**

```bash
curl -X DELETE -u "admin:xxxx" \
  "https://example.com/wp-json/mcp/v1/themes/delete?stylesheet=old-theme"
```

**Response:**

```json
{
  "deleted": true,
  "stylesheet": "old-theme"
}
```

---

## Core

### Get Version

Get WordPress version and update status.

```
GET /core/version
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/core/version
```

**Response:**

```json
{
  "wordpress_version": "6.4.2",
  "php_version": "8.2.0",
  "mysql_version": "8.0.35",
  "required_php": "7.0",
  "required_mysql": "5.0",
  "update_available": true,
  "latest_version": "6.4.3",
  "multisite": false
}
```

---

### Check Updates

Force check for all updates (core, plugins, themes).

```
POST /core/check-updates
```

**Parameters:** None

**Example:**

```bash
curl -X POST -u "admin:xxxx" https://example.com/wp-json/mcp/v1/core/check-updates
```

**Response:**

```json
{
  "core": "6.4.3",
  "plugins": 5,
  "themes": 2
}
```

---

### Update Core

Update WordPress to the latest version.

```
POST /core/update
```

**Parameters:** None

**Example:**

```bash
curl -X POST -u "admin:xxxx" https://example.com/wp-json/mcp/v1/core/update
```

**Response:**

```json
{
  "updated": true,
  "version": "6.4.3"
}
```

---

### Get System Info

Get comprehensive system information.

```
GET /core/system-info
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/core/system-info
```

**Response:**

```json
{
  "wordpress": {
    "version": "6.4.2",
    "url": "https://example.com",
    "admin_url": "https://example.com/wp-admin/",
    "multisite": false,
    "debug": false
  },
  "server": {
    "php": "8.2.0",
    "mysql": "8.0.35",
    "server": "nginx/1.24.0",
    "max_upload": 104857600,
    "memory_limit": "256M"
  },
  "paths": {
    "abspath": "/var/www/html/",
    "content": "/var/www/html/wp-content",
    "plugins": "/var/www/html/wp-content/plugins",
    "uploads": "/var/www/html/wp-content/uploads",
    "themes": "/var/www/html/wp-content/themes"
  },
  "counts": {
    "posts": 42,
    "pages": 12,
    "users": 5,
    "plugins": 15,
    "themes": 3
  }
}
```

---

### Flush Rewrite Rules

Flush permalink rewrite rules.

```
POST /core/flush-rewrite
```

**Parameters:** None

**Example:**

```bash
curl -X POST -u "admin:xxxx" https://example.com/wp-json/mcp/v1/core/flush-rewrite
```

**Response:**

```json
{
  "flushed": true
}
```

---

### Flush Cache

Clear all caches and transients.

```
POST /core/flush-cache
```

**Parameters:** None

**Example:**

```bash
curl -X POST -u "admin:xxxx" https://example.com/wp-json/mcp/v1/core/flush-cache
```

**Response:**

```json
{
  "flushed": true
}
```

---

## Database

### Get Tables

List all database tables with sizes.

```
GET /db/tables
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/db/tables
```

**Response:**

```json
{
  "tables": [
    {
      "name": "wp_posts",
      "data_mb": 12.45,
      "index_mb": 2.30,
      "rows": 1523
    },
    {
      "name": "wp_postmeta",
      "data_mb": 8.20,
      "index_mb": 1.50,
      "rows": 15420
    }
  ],
  "total_size_mb": 45.67
}
```

---

### Search Replace

Search and replace strings in the database.

```
POST /db/search-replace
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `search` | string | Yes | - | String to search for |
| `replace` | string | Yes | - | Replacement string |
| `tables` | array | No | [] | Specific tables (empty = all with prefix) |
| `dry_run` | boolean | No | true | Preview without applying changes |

**Example (Dry Run):**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/db/search-replace \
  -H "Content-Type: application/json" \
  -d '{
    "search": "http://old-domain.com",
    "replace": "https://new-domain.com",
    "dry_run": true
  }'
```

**Response:**

```json
{
  "dry_run": true,
  "search": "http://old-domain.com",
  "replace": "https://new-domain.com",
  "total_changes": 156,
  "tables": {
    "wp_posts": 89,
    "wp_postmeta": 45,
    "wp_options": 22
  }
}
```

---

### Optimize Tables

Optimize all database tables.

```
POST /db/optimize
```

**Parameters:** None

**Example:**

```bash
curl -X POST -u "admin:xxxx" https://example.com/wp-json/mcp/v1/db/optimize
```

**Response:**

```json
{
  "optimized": ["wp_posts", "wp_postmeta", "wp_options", "..."],
  "count": 12
}
```

---

### Clean Revisions

Delete old post revisions.

```
POST /db/clean-revisions
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `keep` | integer | No | 5 | Revisions to keep per post |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/db/clean-revisions \
  -H "Content-Type: application/json" \
  -d '{"keep": 3}'
```

**Response:**

```json
{
  "deleted": 245,
  "kept_per_post": 3
}
```

---

### Clean Comments

Delete spam and trashed comments.

```
POST /db/clean-comments
```

**Parameters:** None

**Example:**

```bash
curl -X POST -u "admin:xxxx" https://example.com/wp-json/mcp/v1/db/clean-comments
```

**Response:**

```json
{
  "spam_deleted": 1523,
  "trash_deleted": 42
}
```

---

## Options

### List Options

List WordPress options with optional prefix filter.

```
GET /options
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `prefix` | string | No | - | Filter by option name prefix |
| `per_page` | integer | No | 50 | Results per page |

**Example:**

```bash
curl -u "admin:xxxx" "https://example.com/wp-json/mcp/v1/options?prefix=woo"
```

**Response:**

```json
{
  "options": [
    {
      "key": "woocommerce_version",
      "value": "8.5.1",
      "autoload": true
    },
    {
      "key": "woocommerce_currency",
      "value": "EUR",
      "autoload": true
    }
  ],
  "count": 2
}
```

---

### Get Option

Get a single option value.

```
GET /options/{key}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `key` | string | Yes | Option name |

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/options/blogname
```

**Response:**

```json
{
  "key": "blogname",
  "value": "My WordPress Site"
}
```

---

### Set Option

Create or update an option.

```
POST /options/{key}
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `key` | string | Yes | - | Option name |
| `value` | mixed | Yes | - | Option value (any type) |
| `autoload` | boolean | No | true | Load on every page |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/options/my_custom_option \
  -H "Content-Type: application/json" \
  -d '{"value": {"setting1": true, "setting2": "value"}, "autoload": false}'
```

**Response:**

```json
{
  "key": "my_custom_option",
  "value": {"setting1": true, "setting2": "value"},
  "created": true
}
```

---

### Delete Option

Delete an option.

```
DELETE /options/{key}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `key` | string | Yes | Option name |

**Example:**

```bash
curl -X DELETE -u "admin:xxxx" https://example.com/wp-json/mcp/v1/options/my_custom_option
```

**Response:**

```json
{
  "deleted": true,
  "key": "my_custom_option"
}
```

---

### Bulk Get Options

Get multiple options at once.

```
POST /options-bulk
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `keys` | array | Yes | Array of option names |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/options-bulk \
  -H "Content-Type: application/json" \
  -d '{"keys": ["blogname", "blogdescription", "admin_email", "timezone_string"]}'
```

**Response:**

```json
{
  "options": {
    "blogname": "My WordPress Site",
    "blogdescription": "Just another WordPress site",
    "admin_email": "admin@example.com",
    "timezone_string": "Europe/Berlin"
  }
}
```

---

## Custom Post Types

### List Post Types

List all registered public post types.

```
GET /cpt
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/cpt
```

**Response:**

```json
{
  "post_types": [
    {
      "name": "post",
      "label": "Posts",
      "singular": "Post",
      "public": true,
      "hierarchical": false,
      "has_archive": false,
      "rest_base": "posts",
      "supports": {
        "title": true,
        "editor": true,
        "thumbnail": true,
        "comments": true
      },
      "taxonomies": ["category", "post_tag"],
      "count": 42
    },
    {
      "name": "product",
      "label": "Products",
      "singular": "Product",
      "public": true,
      "hierarchical": false,
      "has_archive": true,
      "rest_base": "products",
      "supports": {
        "title": true,
        "editor": true,
        "thumbnail": true
      },
      "taxonomies": ["product_cat", "product_tag"],
      "count": 156
    }
  ],
  "count": 5
}
```

---

### Get Post Type

Get detailed information about a specific post type.

```
GET /cpt/{type}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `type` | string | Yes | Post type name |

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/cpt/product
```

**Response:**

```json
{
  "name": "product",
  "label": "Products",
  "singular": "Product",
  "description": "WooCommerce products",
  "public": true,
  "hierarchical": false,
  "has_archive": true,
  "rest_base": "products",
  "supports": {
    "title": true,
    "editor": true,
    "thumbnail": true
  },
  "taxonomies": ["product_cat", "product_tag"],
  "counts": {
    "publish": 156,
    "draft": 12,
    "pending": 3,
    "private": 0,
    "trash": 5
  },
  "labels": {
    "add_new": "Add New",
    "add_new_item": "Add New Product",
    "edit_item": "Edit Product",
    "view_item": "View Product"
  }
}
```

---

### Get Posts

Get posts of a specific post type.

```
GET /cpt/{type}/posts
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `type` | string | Yes | - | Post type name |
| `per_page` | integer | No | 20 | Posts per page |
| `page` | integer | No | 1 | Page number |
| `status` | string | No | any | Post status filter |
| `orderby` | string | No | date | Order by field |
| `order` | string | No | DESC | ASC or DESC |

**Example:**

```bash
curl -u "admin:xxxx" "https://example.com/wp-json/mcp/v1/cpt/product/posts?per_page=10&status=publish"
```

**Response:**

```json
{
  "posts": [
    {
      "id": 123,
      "title": "Sample Product",
      "slug": "sample-product",
      "status": "publish",
      "date": "2024-01-15 10:30:00",
      "modified": "2024-01-20 14:45:00",
      "author": 1,
      "excerpt": "Product description excerpt...",
      "parent": 0
    }
  ],
  "total": 156,
  "pages": 16,
  "page": 1
}
```

---

### Create Post

Create a new post of a specific type.

```
POST /cpt/{type}/posts
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `type` | string | Yes | - | Post type name |
| `title` | string | Yes | - | Post title |
| `content` | string | No | "" | Post content |
| `status` | string | No | draft | Post status |
| `meta` | object | No | {} | Post meta key-value pairs |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/cpt/product/posts \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New Product",
    "content": "<p>Product description here</p>",
    "status": "publish",
    "meta": {
      "_price": "99.00",
      "_sku": "PROD-001"
    }
  }'
```

**Response:**

```json
{
  "id": 456,
  "created": true,
  "edit_url": "https://example.com/wp-admin/post.php?post=456&action=edit"
}
```

---

### Update Post

Update an existing post.

```
PUT /cpt/{type}/posts/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `type` | string | Yes | Post type name |
| `id` | integer | Yes | Post ID |
| `title` | string | No | Post title |
| `content` | string | No | Post content |
| `status` | string | No | Post status |
| `meta` | object | No | Post meta to update |

**Example:**

```bash
curl -X PUT -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/cpt/product/posts/456 \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Product Name",
    "meta": {"_price": "129.00"}
  }'
```

**Response:**

```json
{
  "id": 456,
  "updated": true
}
```

---

### Delete Post

Delete a post (trash or permanent).

```
DELETE /cpt/{type}/posts/{id}
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `type` | string | Yes | - | Post type name |
| `id` | integer | Yes | - | Post ID |
| `force` | boolean | No | false | Skip trash, delete permanently |

**Example:**

```bash
curl -X DELETE -u "admin:xxxx" \
  "https://example.com/wp-json/mcp/v1/cpt/product/posts/456?force=true"
```

**Response:**

```json
{
  "id": 456,
  "deleted": true,
  "trashed": false
}
```

---

## Taxonomies

### List Taxonomies

List all public taxonomies.

```
GET /taxonomies
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/taxonomies
```

**Response:**

```json
{
  "taxonomies": [
    {
      "name": "category",
      "label": "Categories",
      "singular": "Category",
      "hierarchical": true,
      "public": true,
      "post_types": ["post"],
      "rest_base": "categories",
      "count": 15
    },
    {
      "name": "product_cat",
      "label": "Product categories",
      "singular": "Category",
      "hierarchical": true,
      "public": true,
      "post_types": ["product"],
      "rest_base": "product_cat",
      "count": 42
    }
  ],
  "count": 6
}
```

---

### Get Taxonomy

Get detailed taxonomy information.

```
GET /taxonomies/{taxonomy}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `taxonomy` | string | Yes | Taxonomy name |

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/taxonomies/product_cat
```

**Response:**

```json
{
  "name": "product_cat",
  "label": "Product categories",
  "singular": "Category",
  "description": "",
  "hierarchical": true,
  "public": true,
  "post_types": ["product"],
  "rest_base": "product_cat",
  "count": 42,
  "labels": {
    "add_new_item": "Add New Category",
    "edit_item": "Edit Category",
    "search_items": "Search Categories"
  }
}
```

---

### Get Terms

Get terms of a taxonomy.

```
GET /taxonomies/{taxonomy}/terms
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `taxonomy` | string | Yes | - | Taxonomy name |
| `hide_empty` | boolean | No | false | Hide terms with no posts |
| `parent` | integer | No | - | Filter by parent term ID |
| `search` | string | No | - | Search term names |

**Example:**

```bash
curl -u "admin:xxxx" "https://example.com/wp-json/mcp/v1/taxonomies/product_cat/terms?hide_empty=true"
```

**Response:**

```json
{
  "terms": [
    {
      "id": 15,
      "name": "Electronics",
      "slug": "electronics",
      "description": "Electronic products",
      "parent": 0,
      "count": 45
    },
    {
      "id": 18,
      "name": "Phones",
      "slug": "phones",
      "description": "Mobile phones",
      "parent": 15,
      "count": 23
    }
  ],
  "count": 42
}
```

---

### Create Term

Create a new term in a taxonomy.

```
POST /taxonomies/{taxonomy}/terms
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `taxonomy` | string | Yes | - | Taxonomy name |
| `name` | string | Yes | - | Term name |
| `slug` | string | No | - | Term slug (auto-generated if empty) |
| `description` | string | No | "" | Term description |
| `parent` | integer | No | 0 | Parent term ID |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/taxonomies/product_cat/terms \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Laptops",
    "slug": "laptops",
    "description": "Laptop computers",
    "parent": 15
  }'
```

**Response:**

```json
{
  "id": 25,
  "created": true
}
```

---

### Update Term

Update an existing term.

```
PUT /taxonomies/{taxonomy}/terms/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `taxonomy` | string | Yes | Taxonomy name |
| `id` | integer | Yes | Term ID |
| `name` | string | No | Term name |
| `slug` | string | No | Term slug |
| `description` | string | No | Term description |
| `parent` | integer | No | Parent term ID |

**Example:**

```bash
curl -X PUT -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/taxonomies/product_cat/terms/25 \
  -H "Content-Type: application/json" \
  -d '{"name": "Notebook Computers", "slug": "notebook-computers"}'
```

**Response:**

```json
{
  "id": 25,
  "updated": true
}
```

---

### Delete Term

Delete a term.

```
DELETE /taxonomies/{taxonomy}/terms/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `taxonomy` | string | Yes | Taxonomy name |
| `id` | integer | Yes | Term ID |

**Example:**

```bash
curl -X DELETE -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/taxonomies/product_cat/terms/25
```

**Response:**

```json
{
  "id": 25,
  "deleted": true
}
```

---

### Assign Terms

Assign terms to a post.

```
POST /taxonomies/assign
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `post_id` | integer | Yes | - | Post ID |
| `taxonomy` | string | Yes | - | Taxonomy name |
| `terms` | array | Yes | - | Term IDs, slugs, or names |
| `append` | boolean | No | false | Append or replace existing |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/taxonomies/assign \
  -H "Content-Type: application/json" \
  -d '{
    "post_id": 456,
    "taxonomy": "product_cat",
    "terms": [15, "laptops", "New Category"],
    "append": true
  }'
```

**Response:**

```json
{
  "post_id": 456,
  "taxonomy": "product_cat",
  "terms": [15, 25, 30],
  "appended": true
}
```

---

## Menus

### List Menus

List all navigation menus.

```
GET /menus
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/menus
```

**Response:**

```json
{
  "menus": [
    {
      "id": 5,
      "name": "Main Menu",
      "slug": "main-menu",
      "count": 8,
      "locations": ["primary"]
    },
    {
      "id": 6,
      "name": "Footer Menu",
      "slug": "footer-menu",
      "count": 4,
      "locations": ["footer"]
    }
  ],
  "count": 2
}
```

---

### Get Menu Locations

Get registered menu locations.

```
GET /menus/locations
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/menus/locations
```

**Response:**

```json
{
  "locations": [
    {
      "location": "primary",
      "description": "Primary Menu",
      "menu_id": 5
    },
    {
      "location": "footer",
      "description": "Footer Menu",
      "menu_id": 6
    },
    {
      "location": "mobile",
      "description": "Mobile Menu",
      "menu_id": null
    }
  ],
  "count": 3
}
```

---

### Get Menu

Get a menu with all its items.

```
GET /menus/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | Menu ID |

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/menus/5
```

**Response:**

```json
{
  "id": 5,
  "name": "Main Menu",
  "slug": "main-menu",
  "locations": ["primary"],
  "items": [
    {
      "id": 101,
      "title": "Home",
      "url": "https://example.com/",
      "type": "custom",
      "object": "custom",
      "object_id": 0,
      "parent": 0,
      "position": 1,
      "target": "",
      "classes": []
    },
    {
      "id": 102,
      "title": "Products",
      "url": "https://example.com/products/",
      "type": "post_type",
      "object": "page",
      "object_id": 10,
      "parent": 0,
      "position": 2,
      "target": "",
      "classes": []
    },
    {
      "id": 103,
      "title": "Laptops",
      "url": "https://example.com/product-category/laptops/",
      "type": "taxonomy",
      "object": "product_cat",
      "object_id": 25,
      "parent": 102,
      "position": 3,
      "target": "",
      "classes": []
    }
  ],
  "count": 8
}
```

---

### Create Menu

Create a new navigation menu.

```
POST /menus
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `name` | string | Yes | Menu name |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/menus \
  -H "Content-Type: application/json" \
  -d '{"name": "Social Links"}'
```

**Response:**

```json
{
  "id": 10,
  "name": "Social Links",
  "created": true
}
```

---

### Update Menu

Update a menu's properties.

```
PUT /menus/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | Menu ID |
| `name` | string | No | New menu name |

**Example:**

```bash
curl -X PUT -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/menus/10 \
  -H "Content-Type: application/json" \
  -d '{"name": "Social Media Links"}'
```

**Response:**

```json
{
  "id": 10,
  "updated": true
}
```

---

### Delete Menu

Delete a navigation menu.

```
DELETE /menus/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | Menu ID |

**Example:**

```bash
curl -X DELETE -u "admin:xxxx" https://example.com/wp-json/mcp/v1/menus/10
```

**Response:**

```json
{
  "id": 10,
  "deleted": true
}
```

---

### Add Menu Item

Add an item to a menu.

```
POST /menus/{id}/items
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `id` | integer | Yes | - | Menu ID |
| `title` | string | Yes | - | Menu item title |
| `url` | string | No | - | URL (for custom items) |
| `object_type` | string | No | custom | Type: custom, post_type, taxonomy |
| `object` | string | No | - | Object type (page, product_cat, etc.) |
| `object_id` | integer | No | - | Object ID |
| `parent` | integer | No | 0 | Parent menu item ID |
| `position` | integer | No | - | Menu position |

**Example (Custom Link):**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/menus/5/items \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Twitter",
    "url": "https://twitter.com/example",
    "object_type": "custom"
  }'
```

**Example (Page Link):**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/menus/5/items \
  -H "Content-Type: application/json" \
  -d '{
    "title": "About Us",
    "object_type": "post_type",
    "object": "page",
    "object_id": 42
  }'
```

**Example (Category Link):**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/menus/5/items \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Electronics",
    "object_type": "taxonomy",
    "object": "product_cat",
    "object_id": 15
  }'
```

**Response:**

```json
{
  "id": 150,
  "menu_id": 5,
  "created": true
}
```

---

### Update Menu Item

Update an existing menu item.

```
PUT /menus/items/{item_id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `item_id` | integer | Yes | Menu item ID |
| `title` | string | No | Menu item title |
| `url` | string | No | URL |
| `parent` | integer | No | Parent menu item ID |
| `position` | integer | No | Menu position |

**Example:**

```bash
curl -X PUT -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/menus/items/150 \
  -H "Content-Type: application/json" \
  -d '{"title": "Follow us on Twitter", "position": 5}'
```

**Response:**

```json
{
  "id": 150,
  "updated": true
}
```

---

### Delete Menu Item

Delete a menu item.

```
DELETE /menus/items/{item_id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `item_id` | integer | Yes | Menu item ID |

**Example:**

```bash
curl -X DELETE -u "admin:xxxx" https://example.com/wp-json/mcp/v1/menus/items/150
```

**Response:**

```json
{
  "id": 150,
  "deleted": true
}
```

---

### Assign Menu to Location

Assign a menu to a theme location.

```
POST /menus/locations/assign
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `menu_id` | integer | Yes | Menu ID (0 to unassign) |
| `location` | string | Yes | Theme location slug |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/menus/locations/assign \
  -H "Content-Type: application/json" \
  -d '{"menu_id": 5, "location": "primary"}'
```

**Response:**

```json
{
  "location": "primary",
  "menu_id": 5,
  "assigned": true
}
```

---

## Users

### List Users

List WordPress users with filtering.

```
GET /users
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `role` | string | No | - | Filter by role |
| `per_page` | integer | No | 20 | Users per page |
| `page` | integer | No | 1 | Page number |
| `search` | string | No | - | Search term |
| `orderby` | string | No | registered | Order by field |
| `order` | string | No | DESC | ASC or DESC |

**Example:**

```bash
curl -u "admin:xxxx" "https://example.com/wp-json/mcp/v1/users?role=editor&per_page=10"
```

**Response:**

```json
{
  "users": [
    {
      "id": 5,
      "username": "editor1",
      "email": "editor@example.com",
      "display_name": "John Editor",
      "first_name": "John",
      "last_name": "Editor",
      "roles": ["editor"],
      "registered": "2023-06-15 10:30:00"
    }
  ],
  "total": 3,
  "page": 1
}
```

---

### Get User

Get detailed user information.

```
GET /users/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | User ID |

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/users/5
```

**Response:**

```json
{
  "id": 5,
  "username": "editor1",
  "email": "editor@example.com",
  "display_name": "John Editor",
  "first_name": "John",
  "last_name": "Editor",
  "nickname": "johne",
  "description": "Content editor at Example Inc.",
  "url": "https://johne.com",
  "roles": ["editor"],
  "capabilities": ["edit_posts", "publish_posts", "edit_others_posts", "..."],
  "registered": "2023-06-15 10:30:00",
  "posts_count": 42
}
```

---

### Create User

Create a new user.

```
POST /users
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `username` | string | Yes | - | Login username |
| `email` | string | Yes | - | Email address |
| `password` | string | No | auto | Password (auto-generated if empty) |
| `first_name` | string | No | - | First name |
| `last_name` | string | No | - | Last name |
| `role` | string | No | subscriber | User role |
| `send_notification` | boolean | No | true | Email new user notification |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/users \
  -H "Content-Type: application/json" \
  -d '{
    "username": "newuser",
    "email": "newuser@example.com",
    "password": "SecureP@ss123!",
    "first_name": "New",
    "last_name": "User",
    "role": "author",
    "send_notification": false
  }'
```

**Response:**

```json
{
  "id": 15,
  "username": "newuser",
  "created": true
}
```

---

### Update User

Update an existing user.

```
PUT /users/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | User ID |
| `email` | string | No | Email address |
| `password` | string | No | New password |
| `first_name` | string | No | First name |
| `last_name` | string | No | Last name |
| `display_name` | string | No | Display name |
| `description` | string | No | Biographical info |
| `url` | string | No | Website URL |

**Example:**

```bash
curl -X PUT -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/users/15 \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Updated",
    "display_name": "Updated User",
    "description": "Senior content author"
  }'
```

**Response:**

```json
{
  "id": 15,
  "updated": true
}
```

---

### Delete User

Delete a user.

```
DELETE /users/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | User ID |
| `reassign` | integer | No | User ID to reassign posts to |

**Example:**

```bash
curl -X DELETE -u "admin:xxxx" \
  "https://example.com/wp-json/mcp/v1/users/15?reassign=1"
```

**Response:**

```json
{
  "id": 15,
  "deleted": true,
  "posts_reassigned_to": 1
}
```

---

### List Roles

List all user roles with capabilities.

```
GET /users/roles
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/users/roles
```

**Response:**

```json
{
  "roles": [
    {
      "slug": "administrator",
      "name": "Administrator",
      "capabilities": ["manage_options", "edit_users", "install_plugins", "..."],
      "count": 2
    },
    {
      "slug": "editor",
      "name": "Editor",
      "capabilities": ["edit_posts", "publish_posts", "edit_others_posts", "..."],
      "count": 3
    },
    {
      "slug": "author",
      "name": "Author",
      "capabilities": ["edit_posts", "publish_posts", "upload_files"],
      "count": 5
    }
  ],
  "count": 5
}
```

---

### Update User Role

Change a user's role.

```
PUT /users/{id}/role
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | User ID |
| `role` | string | Yes | New role slug |

**Example:**

```bash
curl -X PUT -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/users/15/role \
  -H "Content-Type: application/json" \
  -d '{"role": "editor"}'
```

**Response:**

```json
{
  "id": 15,
  "role": "editor",
  "updated": true
}
```

---

### Get User Meta

Get all public meta for a user.

```
GET /users/{id}/meta
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | User ID |

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/users/15/meta
```

**Response:**

```json
{
  "user_id": 15,
  "meta": {
    "nickname": "johne",
    "first_name": "John",
    "last_name": "Editor",
    "description": "Content editor",
    "rich_editing": "true",
    "syntax_highlighting": "true",
    "admin_color": "fresh",
    "show_admin_bar_front": "true"
  }
}
```

---

### Update User Meta

Update user meta values.

```
POST /users/{id}/meta
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | User ID |
| `meta` | object | Yes | Key-value pairs to update |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/users/15/meta \
  -H "Content-Type: application/json" \
  -d '{
    "meta": {
      "twitter_handle": "@johne",
      "company": "Example Inc."
    }
  }'
```

**Response:**

```json
{
  "user_id": 15,
  "updated_keys": ["twitter_handle", "company"]
}
```

---

## Media

### List Media

List media items with filtering.

```
GET /media
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `per_page` | integer | No | 20 | Items per page |
| `page` | integer | No | 1 | Page number |
| `mime_type` | string | No | - | Filter by mime type (e.g., image/jpeg) |
| `search` | string | No | - | Search term |
| `orderby` | string | No | date | Order by field |
| `order` | string | No | DESC | ASC or DESC |

**Example:**

```bash
curl -u "admin:xxxx" "https://example.com/wp-json/mcp/v1/media?mime_type=image&per_page=10"
```

**Response:**

```json
{
  "media": [
    {
      "id": 123,
      "title": "hero-image",
      "url": "https://example.com/wp-content/uploads/2024/01/hero-image.jpg",
      "mime_type": "image/jpeg",
      "date": "2024-01-15 10:30:00",
      "alt": "Hero banner image",
      "width": 1920,
      "height": 1080
    }
  ],
  "total": 156,
  "pages": 16,
  "page": 1
}
```

---

### Get Media

Get detailed media item information.

```
GET /media/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | Media attachment ID |

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/media/123
```

**Response:**

```json
{
  "id": 123,
  "title": "hero-image",
  "url": "https://example.com/wp-content/uploads/2024/01/hero-image.jpg",
  "mime_type": "image/jpeg",
  "date": "2024-01-15 10:30:00",
  "alt": "Hero banner image",
  "caption": "Main hero image for homepage",
  "description": "Full-width hero banner",
  "filename": "hero-image.jpg",
  "filesize": 245678,
  "width": 1920,
  "height": 1080,
  "sizes": {
    "thumbnail": {
      "url": "https://example.com/wp-content/uploads/2024/01/hero-image-150x150.jpg",
      "width": 150,
      "height": 150
    },
    "medium": {
      "url": "https://example.com/wp-content/uploads/2024/01/hero-image-300x169.jpg",
      "width": 300,
      "height": 169
    },
    "large": {
      "url": "https://example.com/wp-content/uploads/2024/01/hero-image-1024x576.jpg",
      "width": 1024,
      "height": 576
    }
  },
  "attached_to": 42
}
```

---

### Upload Media

Upload a file to the media library.

```
POST /media/upload
```

**Parameters:**

Form-data upload with optional metadata:

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `file` | file | Yes | The file to upload |
| `title` | string | No | Media title |
| `alt` | string | No | Alt text (for images) |
| `caption` | string | No | Caption |
| `description` | string | No | Description |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/media/upload \
  -F "file=@/path/to/image.jpg" \
  -F "title=My Image" \
  -F "alt=Descriptive alt text"
```

**Response:**

```json
{
  "id": 456,
  "url": "https://example.com/wp-content/uploads/2024/02/image.jpg",
  "uploaded": true
}
```

---

### Sideload Media

Upload media from a remote URL.

```
POST /media/sideload
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `url` | string | Yes | - | Remote URL to download |
| `filename` | string | No | from URL | Custom filename |
| `title` | string | No | - | Media title |
| `alt` | string | No | - | Alt text |
| `caption` | string | No | - | Caption |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/media/sideload \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.org/images/photo.jpg",
    "title": "Downloaded Photo",
    "alt": "Photo from external source"
  }'
```

**Response:**

```json
{
  "id": 457,
  "url": "https://example.com/wp-content/uploads/2024/02/photo.jpg",
  "uploaded": true,
  "source_url": "https://example.org/images/photo.jpg"
}
```

---

### Update Media

Update media metadata.

```
PUT /media/{id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | Media attachment ID |
| `title` | string | No | Media title |
| `alt` | string | No | Alt text |
| `caption` | string | No | Caption |
| `description` | string | No | Description |

**Example:**

```bash
curl -X PUT -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/media/123 \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Title",
    "alt": "Better alt text for SEO",
    "caption": "New caption"
  }'
```

**Response:**

```json
{
  "id": 123,
  "updated": true
}
```

---

### Delete Media

Delete a media item.

```
DELETE /media/{id}
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `id` | integer | Yes | - | Media attachment ID |
| `force` | boolean | No | true | Permanently delete (skip trash) |

**Example:**

```bash
curl -X DELETE -u "admin:xxxx" "https://example.com/wp-json/mcp/v1/media/123?force=true"
```

**Response:**

```json
{
  "id": 123,
  "deleted": true
}
```

---

### Bulk Delete Media

Delete multiple media items at once.

```
POST /media/bulk-delete
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `ids` | array | Yes | - | Array of media IDs |
| `force` | boolean | No | true | Permanently delete |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/media/bulk-delete \
  -H "Content-Type: application/json" \
  -d '{"ids": [123, 124, 125], "force": true}'
```

**Response:**

```json
{
  "deleted": [123, 124, 125],
  "failed": [],
  "deleted_count": 3
}
```

---

### Regenerate Thumbnails

Regenerate image thumbnails.

```
POST /media/{id}/regenerate
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `id` | integer | Yes | Image attachment ID |

**Example:**

```bash
curl -X POST -u "admin:xxxx" https://example.com/wp-json/mcp/v1/media/123/regenerate
```

**Response:**

```json
{
  "id": 123,
  "regenerated": true,
  "sizes": ["thumbnail", "medium", "medium_large", "large"]
}
```

---

### Get Media Stats

Get media library statistics.

```
GET /media/stats
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/media/stats
```

**Response:**

```json
{
  "total": 1523,
  "by_type": {
    "image/jpeg": 1200,
    "image/png": 180,
    "image/webp": 50,
    "application/pdf": 45,
    "video/mp4": 28,
    "audio/mpeg": 20
  },
  "upload_path": "/var/www/html/wp-content/uploads",
  "upload_url": "https://example.com/wp-content/uploads",
  "total_size_mb": 2456.78,
  "max_upload_size": 104857600,
  "max_upload_size_mb": 100
}
```

---

## Widgets

### List Sidebars

List all registered sidebars.

```
GET /widgets/sidebars
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/widgets/sidebars
```

**Response:**

```json
{
  "sidebars": [
    {
      "id": "sidebar-1",
      "name": "Main Sidebar",
      "description": "Add widgets here to appear in your sidebar.",
      "class": "",
      "widget_count": 5
    },
    {
      "id": "footer-1",
      "name": "Footer Widget Area",
      "description": "Widgets in this area will appear in the footer.",
      "class": "",
      "widget_count": 3
    }
  ],
  "count": 4
}
```

---

### Get Sidebar Widgets

Get all widgets in a specific sidebar.

```
GET /widgets/sidebars/{sidebar_id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `sidebar_id` | string | Yes | Sidebar ID |

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/widgets/sidebars/sidebar-1
```

**Response:**

```json
{
  "sidebar": {
    "id": "sidebar-1",
    "name": "Main Sidebar"
  },
  "widgets": [
    {
      "id": "search-2",
      "type": "search",
      "name": "Search",
      "settings": {
        "title": "Search"
      }
    },
    {
      "id": "recent-posts-2",
      "type": "recent-posts",
      "name": "Recent Posts",
      "settings": {
        "title": "Recent Articles",
        "number": 5,
        "show_date": true
      }
    },
    {
      "id": "text-3",
      "type": "text",
      "name": "Text",
      "settings": {
        "title": "About Us",
        "text": "<p>Welcome to our site!</p>"
      }
    }
  ],
  "count": 3
}
```

---

### List Widget Types

List all available widget types.

```
GET /widgets/types
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/widgets/types
```

**Response:**

```json
{
  "types": [
    {
      "id_base": "text",
      "name": "Text",
      "description": "Arbitrary text or HTML.",
      "class": "WP_Widget_Text"
    },
    {
      "id_base": "search",
      "name": "Search",
      "description": "A search form for your site.",
      "class": "WP_Widget_Search"
    },
    {
      "id_base": "recent-posts",
      "name": "Recent Posts",
      "description": "Your site's most recent Posts.",
      "class": "WP_Widget_Recent_Posts"
    },
    {
      "id_base": "categories",
      "name": "Categories",
      "description": "A list or dropdown of categories.",
      "class": "WP_Widget_Categories"
    }
  ],
  "count": 15
}
```

---

### Get Widget

Get a single widget's details.

```
GET /widgets/{widget_id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `widget_id` | string | Yes | Widget ID (e.g., `text-2`) |

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/widgets/text-3
```

**Response:**

```json
{
  "id": "text-3",
  "type": "text",
  "name": "Text",
  "settings": {
    "title": "About Us",
    "text": "<p>Welcome to our site!</p>",
    "filter": true,
    "visual": true
  },
  "sidebar_id": "sidebar-1"
}
```

---

### Add Widget

Add a new widget to a sidebar.

```
POST /widgets
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `sidebar_id` | string | Yes | - | Target sidebar ID |
| `widget_type` | string | Yes | - | Widget type (e.g., `text`, `search`) |
| `settings` | object | No | {} | Widget settings |
| `position` | integer | No | end | Position in sidebar |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/widgets \
  -H "Content-Type: application/json" \
  -d '{
    "sidebar_id": "sidebar-1",
    "widget_type": "text",
    "settings": {
      "title": "Welcome",
      "text": "<p>Hello visitors!</p>"
    },
    "position": 0
  }'
```

**Response:**

```json
{
  "widget_id": "text-4",
  "sidebar_id": "sidebar-1",
  "created": true
}
```

---

### Update Widget

Update a widget's settings.

```
PUT /widgets/{widget_id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `widget_id` | string | Yes | Widget ID |
| `settings` | object | Yes | Settings to update |

**Example:**

```bash
curl -X PUT -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/widgets/text-3 \
  -H "Content-Type: application/json" \
  -d '{
    "settings": {
      "title": "Updated Title",
      "text": "<p>New content here</p>"
    }
  }'
```

**Response:**

```json
{
  "widget_id": "text-3",
  "updated": true
}
```

---

### Delete Widget

Remove a widget from its sidebar.

```
DELETE /widgets/{widget_id}
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `widget_id` | string | Yes | Widget ID |

**Example:**

```bash
curl -X DELETE -u "admin:xxxx" https://example.com/wp-json/mcp/v1/widgets/text-3
```

**Response:**

```json
{
  "widget_id": "text-3",
  "deleted": true
}
```

---

### Move Widget

Move a widget to a different sidebar.

```
POST /widgets/{widget_id}/move
```

**Parameters:**

| Name | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `widget_id` | string | Yes | - | Widget ID |
| `sidebar_id` | string | Yes | - | Target sidebar ID |
| `position` | integer | No | end | Position in target sidebar |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/widgets/text-3/move \
  -H "Content-Type: application/json" \
  -d '{"sidebar_id": "footer-1", "position": 0}'
```

**Response:**

```json
{
  "widget_id": "text-3",
  "from_sidebar": "sidebar-1",
  "to_sidebar": "footer-1",
  "moved": true
}
```

---

### Reorder Widgets

Reorder widgets within a sidebar.

```
POST /widgets/sidebars/{sidebar_id}/reorder
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `sidebar_id` | string | Yes | Sidebar ID |
| `widget_ids` | array | Yes | Ordered array of widget IDs |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/widgets/sidebars/sidebar-1/reorder \
  -H "Content-Type: application/json" \
  -d '{"widget_ids": ["search-2", "text-3", "recent-posts-2"]}'
```

**Response:**

```json
{
  "sidebar_id": "sidebar-1",
  "widget_ids": ["search-2", "text-3", "recent-posts-2"],
  "reordered": true
}
```

---

## Health

### Get Health Status

Get overall site health status and score.

```
GET /health
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/health
```

**Response:**

```json
{
  "status": "good",
  "score": 90,
  "wordpress": {
    "version": "6.4.2",
    "update_available": false
  },
  "php": {
    "version": "8.2.0",
    "memory_limit": "256M"
  },
  "database": {
    "version": "8.0.35",
    "prefix": "wp_"
  },
  "updates": {
    "total": 2,
    "plugins": 2,
    "themes": 0
  },
  "debug": {
    "wp_debug": false,
    "wp_debug_log": false,
    "wp_debug_display": false
  },
  "ssl": true,
  "multisite": false,
  "issues": ["2 updates available"]
}
```

**Score Interpretation:**

| Score | Status | Meaning |
|-------|--------|---------|
| 80-100 | good | Site is healthy |
| 60-79 | warning | Minor issues present |
| 0-59 | critical | Significant issues |

---

### Get Debug Info

Get detailed debug information.

```
GET /health/debug
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/health/debug
```

**Response:**

```json
{
  "wordpress": {
    "version": "6.4.2",
    "home_url": "https://example.com",
    "site_url": "https://example.com",
    "is_multisite": false,
    "max_upload_size": 104857600,
    "memory_limit": "256M",
    "max_memory_limit": "512M",
    "debug_mode": false,
    "cron_disabled": false,
    "language": "en_US",
    "timezone": "Europe/Berlin"
  },
  "server": {
    "php_version": "8.2.0",
    "server_software": "nginx/1.24.0",
    "document_root": "/var/www/html"
  },
  "database": {
    "server_version": "8.0.35",
    "client_version": "mysqlnd 8.2.0",
    "database_name": "wordpress",
    "table_prefix": "wp_",
    "charset": "utf8mb4",
    "collate": "utf8mb4_unicode_ci"
  },
  "paths": {
    "wordpress": "/var/www/html/",
    "content": "/var/www/html/wp-content",
    "plugins": "/var/www/html/wp-content/plugins",
    "uploads": "/var/www/html/wp-content/uploads",
    "themes": "/var/www/html/wp-content/themes"
  },
  "constants": {
    "WP_DEBUG": false,
    "WP_DEBUG_LOG": false,
    "WP_DEBUG_DISPLAY": true,
    "SCRIPT_DEBUG": false,
    "WP_CACHE": true,
    "CONCATENATE_SCRIPTS": true,
    "COMPRESS_SCRIPTS": true,
    "COMPRESS_CSS": true
  }
}
```

---

### Get PHP Info

Get PHP configuration details.

```
GET /health/php
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/health/php
```

**Response:**

```json
{
  "version": "8.2.0",
  "sapi": "fpm-fcgi",
  "memory_limit": "256M",
  "max_execution_time": "300",
  "upload_max_filesize": "64M",
  "post_max_size": "64M",
  "max_input_vars": "5000",
  "display_errors": "0",
  "error_reporting": 32767,
  "opcache": {
    "enabled": true
  },
  "extensions": [
    "Core", "curl", "date", "dom", "exif", "fileinfo", "filter",
    "gd", "hash", "iconv", "imagick", "intl", "json", "libxml",
    "mbstring", "mysqli", "mysqlnd", "openssl", "pcre", "PDO",
    "pdo_mysql", "Phar", "posix", "readline", "Reflection",
    "session", "SimpleXML", "sodium", "SPL", "standard",
    "tokenizer", "xml", "xmlreader", "xmlwriter", "zip", "zlib"
  ],
  "disabled_functions": []
}
```

---

### Get Plugins Health

Get plugin health status and available updates.

```
GET /health/plugins
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/health/plugins
```

**Response:**

```json
{
  "plugins": [
    {
      "file": "akismet/akismet.php",
      "name": "Akismet Anti-spam",
      "version": "5.3",
      "active": true,
      "update_available": false,
      "new_version": null
    },
    {
      "file": "woocommerce/woocommerce.php",
      "name": "WooCommerce",
      "version": "8.5.0",
      "active": true,
      "update_available": true,
      "new_version": "8.5.1"
    }
  ],
  "total": 15,
  "active": 12,
  "inactive": 3,
  "updates_available": 2
}
```

---

### Get Cron Status

Get WordPress cron jobs status.

```
GET /health/cron
```

**Parameters:** None

**Example:**

```bash
curl -u "admin:xxxx" https://example.com/wp-json/mcp/v1/health/cron
```

**Response:**

```json
{
  "cron_disabled": false,
  "schedules": {
    "hourly": {
      "interval": 3600,
      "display": "Once Hourly"
    },
    "twicedaily": {
      "interval": 43200,
      "display": "Twice Daily"
    },
    "daily": {
      "interval": 86400,
      "display": "Once Daily"
    }
  },
  "events": [
    {
      "hook": "wp_scheduled_delete",
      "timestamp": 1706918400,
      "next_run": "2024-02-03 00:00:00",
      "schedule": "daily",
      "interval": 86400,
      "args": []
    },
    {
      "hook": "wp_update_plugins",
      "timestamp": 1706875200,
      "next_run": "2024-02-02 12:00:00",
      "schedule": "twicedaily",
      "interval": 43200,
      "args": []
    }
  ],
  "total_events": 25
}
```

---

### Run Cron Job

Manually trigger a cron hook.

```
POST /health/cron/run
```

**Parameters:**

| Name | Type | Required | Description |
|------|------|----------|-------------|
| `hook` | string | Yes | Cron hook name to run |

**Example:**

```bash
curl -X POST -u "admin:xxxx" \
  https://example.com/wp-json/mcp/v1/health/cron/run \
  -H "Content-Type: application/json" \
  -d '{"hook": "wp_update_plugins"}'
```

**Response:**

```json
{
  "hook": "wp_update_plugins",
  "executed": true
}
```

---

## Quick Reference

### All Endpoints Summary

| Category | Method | Endpoint | Description |
|----------|--------|----------|-------------|
| **Plugins** | GET | `/plugins/search` | Search WordPress.org |
| | POST | `/plugins/install` | Install plugin |
| | POST | `/plugins/update` | Update single plugin |
| | POST | `/plugins/update-all` | Update all plugins |
| **Themes** | GET | `/themes/search` | Search WordPress.org |
| | POST | `/themes/install` | Install theme |
| | POST | `/themes/update` | Update single theme |
| | POST | `/themes/update-all` | Update all themes |
| | DELETE | `/themes/delete` | Delete theme |
| **Core** | GET | `/core/version` | Get version info |
| | POST | `/core/check-updates` | Check all updates |
| | POST | `/core/update` | Update WordPress |
| | GET | `/core/system-info` | Get system info |
| | POST | `/core/flush-rewrite` | Flush rewrites |
| | POST | `/core/flush-cache` | Flush all caches |
| **Database** | GET | `/db/tables` | List tables |
| | POST | `/db/search-replace` | Search and replace |
| | POST | `/db/optimize` | Optimize tables |
| | POST | `/db/clean-revisions` | Clean revisions |
| | POST | `/db/clean-comments` | Clean spam/trash |
| **Options** | GET | `/options` | List options |
| | GET | `/options/{key}` | Get option |
| | POST | `/options/{key}` | Set option |
| | DELETE | `/options/{key}` | Delete option |
| | POST | `/options-bulk` | Bulk get options |
| **CPT** | GET | `/cpt` | List post types |
| | GET | `/cpt/{type}` | Get post type info |
| | GET | `/cpt/{type}/posts` | Get posts |
| | POST | `/cpt/{type}/posts` | Create post |
| | PUT | `/cpt/{type}/posts/{id}` | Update post |
| | DELETE | `/cpt/{type}/posts/{id}` | Delete post |
| **Taxonomies** | GET | `/taxonomies` | List taxonomies |
| | GET | `/taxonomies/{tax}` | Get taxonomy |
| | GET | `/taxonomies/{tax}/terms` | Get terms |
| | POST | `/taxonomies/{tax}/terms` | Create term |
| | PUT | `/taxonomies/{tax}/terms/{id}` | Update term |
| | DELETE | `/taxonomies/{tax}/terms/{id}` | Delete term |
| | POST | `/taxonomies/assign` | Assign terms |
| **Menus** | GET | `/menus` | List menus |
| | GET | `/menus/locations` | Get locations |
| | GET | `/menus/{id}` | Get menu |
| | POST | `/menus` | Create menu |
| | PUT | `/menus/{id}` | Update menu |
| | DELETE | `/menus/{id}` | Delete menu |
| | POST | `/menus/{id}/items` | Add item |
| | PUT | `/menus/items/{item_id}` | Update item |
| | DELETE | `/menus/items/{item_id}` | Delete item |
| | POST | `/menus/locations/assign` | Assign location |
| **Users** | GET | `/users` | List users |
| | GET | `/users/{id}` | Get user |
| | POST | `/users` | Create user |
| | PUT | `/users/{id}` | Update user |
| | DELETE | `/users/{id}` | Delete user |
| | GET | `/users/roles` | List roles |
| | PUT | `/users/{id}/role` | Update role |
| | GET | `/users/{id}/meta` | Get meta |
| | POST | `/users/{id}/meta` | Update meta |
| **Media** | GET | `/media` | List media |
| | GET | `/media/{id}` | Get media item |
| | POST | `/media/upload` | Upload file |
| | POST | `/media/sideload` | Upload from URL |
| | PUT | `/media/{id}` | Update metadata |
| | DELETE | `/media/{id}` | Delete media |
| | POST | `/media/bulk-delete` | Bulk delete |
| | POST | `/media/{id}/regenerate` | Regenerate thumbnails |
| | GET | `/media/stats` | Media statistics |
| **Widgets** | GET | `/widgets/sidebars` | List sidebars |
| | GET | `/widgets/sidebars/{id}` | Get sidebar widgets |
| | GET | `/widgets/types` | List widget types |
| | GET | `/widgets/{id}` | Get widget |
| | POST | `/widgets` | Add widget |
| | PUT | `/widgets/{id}` | Update widget |
| | DELETE | `/widgets/{id}` | Delete widget |
| | POST | `/widgets/{id}/move` | Move widget |
| | POST | `/widgets/sidebars/{id}/reorder` | Reorder widgets |
| **Health** | GET | `/health` | Health status |
| | GET | `/health/debug` | Debug info |
| | GET | `/health/php` | PHP info |
| | GET | `/health/plugins` | Plugin health |
| | GET | `/health/cron` | Cron status |
| | POST | `/health/cron/run` | Run cron hook |

---

## Changelog

### v1.2.0

- Added Media endpoints (upload, sideload, edit, bulk delete, regenerate thumbnails, stats)
- Added Widgets endpoints (sidebars, widget CRUD, move, reorder)
- Phase 1 complete: All core free plugin features implemented

### v1.1.0

- Added Custom Post Types endpoints
- Added Taxonomies endpoints
- Added Menus endpoints
- Added Users endpoints
- Added Health endpoints

### v1.0.0

- Initial release
- Plugins, Themes, Core, Database, Options endpoints
