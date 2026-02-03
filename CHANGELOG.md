# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-02-03

### Added
- Media endpoints: list, get, upload, sideload from URL, update metadata, delete, bulk delete, regenerate thumbnails, stats
- Widgets endpoints: list sidebars, get sidebar widgets, list widget types, widget CRUD, move between sidebars, reorder

### Notes
- Phase 1 complete: All core free plugin features from ROADMAP.md are now implemented
- API-REFERENCE.md updated with full documentation for all 70+ endpoints

## [1.1.0] - 2026-02-03

### Added
- Custom Post Types endpoints: list types, get schema, CRUD operations with meta support
- Taxonomies endpoints: list taxonomies, manage terms, assign terms to posts
- Menus endpoints: manage navigation menus, items, and location assignments
- Users endpoints: user management, roles, capabilities, user meta
- Health endpoints: site health score, debug info, PHP info, plugin health, cron status

## [1.0.0] - 2026-02-03

### Added
- Initial release
- Plugin endpoints: search, install, update, update-all
- Theme endpoints: search, install, update, update-all, delete
- Core endpoints: version, system-info, check-updates, update, flush-rewrite, flush-cache
- Database endpoints: tables, search-replace, optimize, clean-revisions, clean-comments
- Options endpoints: list, get, set, delete, bulk-get
- Application Password authentication support
- Admin capability checks on all endpoints
