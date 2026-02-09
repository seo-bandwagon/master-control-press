# WP MCP Core Abilities

Production-ready WordPress plugin that exposes comprehensive WordPress core functionality to AI agents through the Model Context Protocol (MCP).

## Overview

WP MCP Core Abilities provides a complete set of WordPress management capabilities through the MCP Adapter, allowing AI agents like Claude to interact with your WordPress site programmatically. This includes content management, user administration, media handling, taxonomy management, theme and plugin management, and more.

## Recent Updates

### Version 1.1.0 - Parameter Handling & Theme Management Improvements

**Critical Fixes:**
- **Robust Parameter Handling**: All list abilities now include defensive array validation to handle empty or null input parameters gracefully
- **WordPress REST API Pattern Compliance**: Theme detection updated to use `wp_get_theme()` matching WordPress core's REST API controller pattern
- **Enhanced Error Handling**: Comprehensive try-catch blocks with detailed error messages for all operations
- **Filesystem Operations**: Added proper `WP_Filesystem()` initialization for theme and plugin installation

**Affected Abilities:**
- ✅ `list-themes` - Fixed active theme detection and parameter handling
- ✅ `list-block-patterns` - Added defensive input validation
- ✅ `list-block-types` - Added defensive input validation
- ✅ `install-theme` - Added WP_Filesystem initialization
- ✅ `install-plugin` - Added WP_Filesystem initialization
- ✅ All list abilities - Enhanced null coalescing operator usage with array validation

## Requirements

- **WordPress:** 6.9 or higher
- **PHP:** 7.4 or higher (8.0+ recommended)
- **Dependencies:** WordPress Abilities API (bundled in WordPress 6.9+)
- **Required Plugin:** WordPress MCP Adapter

## Installation

### Via WordPress Admin

1. Download the latest release from the [GitHub repository](https://github.com/your-repo/wp-mcp-core-abilities)
2. Navigate to **Plugins > Add New > Upload Plugin**
3. Upload the `wp-mcp-core-abilities.zip` file
4. Click **Install Now**
5. Activate the plugin

### Manual Installation

1. Download and extract the plugin files
2. Upload the `wp-mcp-core-abilities` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin panel

### Via Composer

```bash
composer require your-vendor/wp-mcp-core-abilities
```

## Abilities Reference

### 1. Core Site Management (`wp-mcp-core/*`)

#### Site Information
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `get-site-info` | Get WordPress site information including name, URL, description, language, and timezone. | `read` |
| `update-site-info` | Update WordPress site information including name, description, admin email, and timezone. | `manage_options` |
| `get-environment-info` | Get WordPress environment information including versions, server details, and active theme. | `manage_options` |
| `search-content` | Global search across posts, pages, and custom post types. | `read` |
| `flush-cache` | Flush the WordPress object cache. | `manage_options` |

#### Plugin Management
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-plugins` | List all installed plugins with their status and metadata. | Authenticated user |
| `get-plugin` | Get detailed information about a specific plugin. | `activate_plugins` |
| `install-plugin` | Install a plugin from WordPress.org repository. | `install_plugins` |
| `activate-plugin` | Activate a WordPress plugin. | `activate_plugins` |
| `deactivate-plugin` | Deactivate a WordPress plugin. | `activate_plugins` |
| `delete-plugin` | Delete an inactive plugin. | `delete_plugins` |

#### Theme Management
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-themes` | List all installed themes with status filtering. | Authenticated user |
| `get-active-theme` | Get current active theme information. | Authenticated user |
| `install-theme` | Install a theme from WordPress.org repository. | `install_themes` |
| `activate-theme` | Activate a WordPress theme. | `switch_themes` |
| `delete-theme` | Delete an inactive theme. | `delete_themes` |

#### Menu Management
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-menus` | List all registered navigation menus. | `edit_theme_options` |
| `get-menu` | Get menu items and structure for a specific menu. | `edit_theme_options` |
| `create-menu` | Create a new navigation menu. | `edit_theme_options` |
| `delete-menu` | Delete a navigation menu. | `edit_theme_options` |

#### Options Management
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `get-option` | Get a WordPress option value by key. | `manage_options` |
| `update-option` | Set or update a WordPress option value. | `manage_options` |

### 2. Content Management

#### Posts (`wp-mcp-core/*`)
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-posts` | Retrieve a list of WordPress posts with filtering and pagination. | `read` |
| `get-post` | Get a single post by ID with all content and metadata. | `read` |
| `create-post` | Create a new WordPress post with content, title, and metadata. | `publish_posts` |
| `update-post` | Update an existing WordPress post. | `edit_posts` |
| `delete-post` | Delete a WordPress post (move to trash or permanently delete). | `delete_posts` |
| `get-post-meta` | Get custom fields/metadata for a post. | `read` |
| `update-post-meta` | Set or update custom fields/metadata for a post. | `edit_posts` |

#### Pages (`wp-mcp-core/*`)
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-pages` | Retrieve a list of WordPress pages with hierarchy support. | `read` |
| `get-page` | Get a single page by ID with all content and metadata. | `read` |
| `create-page` | Create a new WordPress page. | `publish_pages` |
| `update-page` | Update an existing WordPress page. | `edit_pages` |
| `delete-page` | Delete a WordPress page (move to trash or permanently delete). | `delete_pages` |
| `get-page-hierarchy` | Get the hierarchical structure of all pages. | `read` |

### 3. Taxonomy Management (`wp-mcp-core/*`)

#### Categories
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-categories` | List all post categories with hierarchy. | `read` |
| `get-category` | Get a single category by ID. | `read` |
| `create-category` | Create a new category. | `manage_categories` |
| `update-category` | Update an existing category. | `manage_categories` |
| `delete-category` | Delete a category. | `manage_categories` |

#### Tags
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-tags` | List all post tags. | `read` |
| `create-tag` | Create a new tag. | `manage_categories` |
| `update-tag` | Update an existing tag. | `manage_categories` |
| `delete-tag` | Delete a tag. | `manage_categories` |

#### Post Taxonomy Assignments
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `get-post-categories` | Get categories assigned to a post. | `read` |
| `set-post-categories` | Assign categories to a post. | `edit_posts` |
| `get-post-tags` | Get tags assigned to a post. | `read` |
| `set-post-tags` | Assign tags to a post. | `edit_posts` |

### 4. User Management (`wp-mcp-core/*`)
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-users` | List all users with roles and metadata. | `list_users` |
| `get-user` | Get a single user by ID. | `list_users` |
| `get-current-user` | Get currently authenticated user info. | Authenticated user |
| `create-user` | Create a new user account. | `create_users` |
| `update-user` | Update user profile. | `edit_users` |
| `delete-user` | Delete a user account. | `delete_users` |
| `get-user-meta` | Get user custom metadata. | `list_users` |
| `update-user-meta` | Set user custom metadata. | `edit_users` |
| `list-user-posts` | Get all posts by a specific user. | `read` |
| `update-user-role` | Change user role/capabilities. | `promote_users` |

### 5. Media Library (`wp-mcp-core/*`)
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-media` | List media library files with filtering. | `upload_files` |
| `get-media` | Get single media item with metadata. | `upload_files` |
| `upload-media` | Upload a new media file (base64 encoded). | `upload_files` |
| `update-media` | Update media metadata. | `upload_files` |
| `delete-media` | Delete media file permanently. | `delete_posts` |
| `search-media` | Search media library by filename, alt text, caption. | `upload_files` |
| `get-media-meta` | Get custom metadata for media attachment. | `upload_files` |
| `update-media-meta` | Set custom metadata for media attachment. | `upload_files` |
| `get-attachment-url` | Get direct URL for media file. | `upload_files` |
| `get-image-sizes` | Get all available image size URLs for image attachment. | `upload_files` |

### 6. Comment Management (`wp-mcp-core/*`)
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-comments` | List comments with filtering. | `moderate_comments` |
| `get-comment` | Get a single comment by ID. | `moderate_comments` |
| `create-comment` | Add a new comment to a post. | Authenticated user |
| `update-comment` | Update comment content/status. | `moderate_comments` |
| `delete-comment` | Trash or permanently delete a comment. | `moderate_comments` |
| `approve-comment` | Approve a pending comment. | `moderate_comments` |
| `spam-comment` | Mark a comment as spam. | `moderate_comments` |
| `get-post-comments` | Get all comments for a specific post. | `read` |

### 7. Block Editor (`wp-mcp-core/*`)
| Ability | Description | Permissions |
| :--- | :--- | :--- |
| `list-block-patterns` | Retrieve a list of registered block patterns with optional category filtering. | `edit_posts` |
| `get-block-pattern` | Retrieve details of a specific block pattern, including its content. | `edit_posts` |
| `list-block-types` | Retrieve a list of registered block types with optional namespace filtering. | `edit_posts` |

## Technical Implementation Details

### Parameter Handling Pattern

All ability execute functions follow this robust pattern for handling input parameters:

```php
public function execute_ability( $input ) {
    $result = array(
        'success' => false,
        'data'    => [],
        'total'   => 0,
        'error'   => ''
    );

    try {
        // Ensure input is always an array
        if ( ! is_array( $input ) ) {
            $input = array();
        }

        // Use null coalescing operator with defaults
        $param = $input['param'] ?? 'default_value';

        // Add validation
        $param = max( 1, min( 100, $param ) );

        // Execute logic...

        $result['success'] = true;
        return $result;
    } catch ( \Throwable $e ) {
        $result['error'] = 'Operation failed: ' . $e->getMessage();
        return $result;
    }
}
```

This pattern ensures:
- Graceful handling of null or missing parameters
- Defensive array type checking
- Comprehensive error catching
- Consistent return format

### Theme Detection

Theme active status detection uses WordPress core's REST API pattern:

```php
$current_theme = wp_get_theme();
foreach ( $themes as $slug => $theme ) {
    $is_active = ( $theme->get_stylesheet() === $current_theme->get_stylesheet() );
}
```

This ensures:
- Proper child theme handling
- Consistent with WordPress core behavior
- Reliable across different WordPress configurations

### Filesystem Operations

Theme and plugin installation use proper WordPress filesystem abstraction:

```php
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

// Initialize WordPress filesystem
WP_Filesystem();

// Now perform file operations
$upgrader = new Theme_Upgrader( $skin );
$result = $upgrader->install( $download_link );
```

## Security Features

### 1. Capability Checks
Every ability has a `permission_callback` that verifies the current user has appropriate WordPress capabilities.

### 2. Input Validation
- All inputs are validated and sanitized
- Required fields are enforced
- Type checking on all parameters
- Enum validation for restricted values
- Defensive array handling for optional parameters

### 3. Output Sanitization
- Sensitive data is filtered (e.g., passwords never exposed)
- Email addresses only visible to administrators
- Proper escaping of all output data

### 4. Whitelisting
- Options API uses strict whitelists
- Only safe options can be read or modified
- Prevents access to sensitive configuration

### 5. WordPress Standards
- Uses WordPress native functions
- Leverages built-in nonce and capability systems
- Follows WordPress security best practices
- Matches WordPress core REST API patterns

## Usage Examples

### Theme Management
```
"List all installed themes"
→ Returns all themes with active/inactive status

"Install the Astra theme and activate it"
→ Downloads from WordPress.org and activates

"Show me which theme is currently active"
→ Returns active theme details
```

### Plugin Management
```
"List all installed plugins"
→ Returns plugins with active/inactive status

"Install and activate the Yoast SEO plugin"
→ Downloads and activates the plugin

"Deactivate the Hello Dolly plugin"
→ Deactivates the specified plugin
```

### Content Creation
```
"Create a new blog post titled 'Getting Started with AI' with an introduction about artificial intelligence"
→ Creates a new post with the specified content

"List all draft posts and show me their titles and authors"
→ Returns filtered list of draft posts

"Update post 123 to change its status to published"
→ Updates the post status
```

### Block Patterns
```
"Show me all available block patterns"
→ Returns all registered patterns

"List block patterns in the 'featured' category"
→ Returns filtered patterns

"Get the content for the 'core/query-standard-posts' pattern"
→ Returns specific pattern details
```

## Error Handling

All abilities return standardized error objects when operations fail:

```json
{
  "success": false,
  "error": "Operation failed: Post not found",
  "data": []
}
```

For critical errors, abilities return `WP_Error` objects:

```php
return new WP_Error(
    'post_not_found',
    'Post not found.',
    array( 'status' => 404 )
);
```

Common error codes:
- `missing_required_field` - Required parameter missing
- `*_not_found` - Resource doesn't exist (theme, plugin, post, etc.)
- `*_failed` - Operation failed (install, delete, update, etc.)
- `invalid_*` - Invalid parameter value
- `option_not_allowed` - Option not in whitelist
- `*_already_installed` - Resource already exists

## Development

### File Structure
```
wp-mcp-core-abilities/
├── wp-mcp-core-abilities.php      # Main plugin file
├── includes/
│   ├── class-ability-registry.php # Central registry
│   ├── abilities/                 # Ability implementations
│   │   ├── class-core-site-abilities.php
│   │   ├── class-post-abilities.php
│   │   ├── class-page-abilities.php
│   │   ├── class-media-abilities.php
│   │   ├── class-user-abilities.php
│   │   ├── class-taxonomy-abilities.php
│   │   ├── class-comment-abilities.php
│   │   ├── class-theme-abilities.php
│   │   ├── class-plugin-abilities.php
│   │   └── class-block-abilities.php
│   └── traits/
│       └── trait-ability-helpers.php # Shared utilities
├── README.md
└── composer.json
```

### Adding Custom Abilities

1. Create a new class in `includes/abilities/`
2. Use the `WP_MCP_Ability_Helpers` trait
3. Register abilities in a static `register()` method
4. Add to `class-ability-registry.php`

Example:
```php
class WP_MCP_Custom_Abilities {
    use WP_MCP_Ability_Helpers;

    public static function register() {
        $instance = new self();
        $instance->register_custom_ability();
    }

    private function register_custom_ability() {
        wp_register_ability('wp-mcp-core/my-ability', [
            'category' => 'custom',
            'label' => __( 'My Custom Ability', 'wp-mcp-core-abilities' ),
            'description' => __( 'Does something custom', 'wp-mcp-core-abilities' ),
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'param' => [
                        'type' => 'string',
                        'description' => 'Parameter description'
                    ]
                ]
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'result' => ['type' => 'string']
                ]
            ],
            'execute_callback' => [$this, 'execute_custom'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'meta' => [
                'mcp' => ['public' => true],
                'annotations' => [
                    'readonly' => true,
                    'idempotent' => true,
                    'destructive' => false
                ]
            ]
        ]);
    }

    public function execute_custom( $input ) {
        // Ensure input is an array
        if ( ! is_array( $input ) ) {
            $input = array();
        }

        $param = $input['param'] ?? '';

        return [
            'success' => true,
            'result' => 'Custom result: ' . $param
        ];
    }
}
```

## Troubleshooting

### Plugin Not Activating
- Ensure WordPress 6.9+ is installed
- Check PHP version (7.4+ required, 8.0+ recommended)
- Verify WordPress Abilities API is available

### Abilities Not Appearing
- Check that the plugin is activated
- Verify MCP Adapter is installed and configured
- Check WordPress debug logs for errors
- Ensure you're using a compatible MCP client

### Permission Errors
- Verify the authenticated user has required capabilities
- Check WordPress user roles and permissions
- Review capability requirements in ability definitions

### Theme/Plugin Installation Fails
- Ensure proper file system permissions
- Verify WordPress can write to wp-content directories
- Check that `WP_Filesystem` is available
- Review server PHP configuration for file upload limits

### Empty Parameter Failures
- Ensure you're using version 1.1.0 or later with defensive array handling
- Check MCP client is sending properly formatted requests
- Review WordPress debug logs for specific error messages

## Performance Considerations

- Pagination is enforced on all list operations
- Maximum items per request: 100 (configurable via parameters)
- Database queries use WordPress query optimization
- Efficient use of WordPress object caching
- Defensive parameter handling prevents unnecessary processing

## Changelog

### Version 1.1.0 (Current)
- **FIXED**: Theme active detection now uses `wp_get_theme()` pattern matching WordPress REST API
- **FIXED**: Added defensive array validation to all list abilities for robust parameter handling
- **FIXED**: Added `WP_Filesystem()` initialization to theme and plugin installation
- **IMPROVED**: Enhanced error handling with comprehensive try-catch blocks
- **IMPROVED**: All abilities now handle null/empty parameters gracefully
- **UPDATED**: Documentation with technical implementation details

### Version 1.0.0
- Initial release
- 70+ WordPress abilities across 8 categories
- Comprehensive input validation and security
- Full JSON Schema definitions
- Production-ready code quality

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Follow WordPress Coding Standards
4. Add PHPDoc blocks
5. Include defensive parameter handling
6. Test thoroughly with empty parameters
7. Submit a pull request

## License

GPL v2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

## Credits

Developed for the WordPress MCP ecosystem. Built with the WordPress Abilities API and MCP Adapter.

## Support

- **Issues:** [GitHub Issues](https://github.com/your-repo/wp-mcp-core-abilities/issues)
- **Documentation:** [Wiki](https://github.com/your-repo/wp-mcp-core-abilities/wiki)
- **WordPress Support:** [WordPress.org Forums](https://wordpress.org/support/)

## Roadmap

Future enhancements:
- Admin settings page for ability management
- Activity logging and audit trail
- Rate limiting configuration
- Enhanced multisite compatibility
- Custom post type builder UI
- Advanced block pattern management
- WooCommerce integration (separate plugin)
