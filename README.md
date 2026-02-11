# Master Control Press

MCP Plugin for WordPress

## Description

Master Control Press is a WordPress plugin that integrates Model Context Protocol (MCP) functionality into WordPress.

## Installation

1. Download or clone this repository
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Features

- MCP integration for WordPress
- Easy-to-use admin interface
- Extensible architecture

## Development

### Directory Structure

```
master-control-press/
├── admin/              # Admin-specific functionality
├── assets/             # CSS, JavaScript, and images
│   ├── css/
│   ├── js/
│   └── images/
├── includes/           # Core plugin classes
├── languages/          # Translation files
├── public/             # Public-facing functionality
├── master-control-press.php    # Main plugin file
└── uninstall.php       # Cleanup on uninstall
```

### Development Setup

```bash
# Install dependencies (if using Composer)
composer install

# Install npm dependencies (if any)
npm install
```

## Support

For issues and questions, please use the GitHub issue tracker.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
