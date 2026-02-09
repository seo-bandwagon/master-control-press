<?php
/**
 * WP MCP Core Abilities - Consolidated Test Suite
 * 
 * Combines diagnostics, registration checks, hook debugging, and execution tests.
 */

define( 'WP_USE_THEMES', false );
// Handle CLI vs Web execution for wp-load
if ( ! defined( 'ABSPATH' ) ) {
    require '../../../wp-load.php';
}

$view = isset( $_GET['view'] ) ? $_GET['view'] : 'diagnostic';
$self_url = plugin_dir_url( __FILE__ ) . basename( __FILE__ );

function render_nav( $current, $url ) {
    $tabs = [
        'diagnostic' => 'Diagnostics',
        'registration' => 'Registration & Public Flags',
        'hooks' => 'Hooks & Trace',
        'execution' => 'Execution Tests',
        'tools' => 'Tools'
    ];
    
    echo '<div class="nav">';
    foreach ( $tabs as $key => $label ) {
        $active = $key === $current ? 'active' : '';
        echo "<a href='{$url}?view={$key}' class='nav-item {$active}'>{$label}</a>";
    }
    echo '</div>';
}

function print_status( $condition, $success_msg, $fail_msg ) {
    if ( $condition ) {
        echo '<div class="success">✓ ' . esc_html( $success_msg ) . '</div>';
    } else {
        echo '<div class="error">✗ ' . esc_html( $fail_msg ) . '</div>';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>WP MCP Test Suite - <?php echo esc_html( ucfirst( $view ) ); ?></title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; background: #f9fafb; color: #1f2937; }
        h1 { border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 20px; }
        h2 { margin-top: 30px; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
        .nav { display: flex; gap: 10px; margin-bottom: 30px; background: #fff; padding: 10px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .nav-item { text-decoration: none; color: #4b5563; padding: 8px 16px; border-radius: 6px; font-weight: 500; transition: all 0.2s; }
        .nav-item:hover { background: #f3f4f6; }
        .nav-item.active { background: #2563eb; color: white; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; border: 1px solid #e5e7eb; }
        .success { color: #059669; background: #d1fae5; padding: 10px; border-radius: 6px; margin: 5px 0; border: 1px solid #a7f3d0; }
        .error { color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 6px; margin: 5px 0; border: 1px solid #fecaca; }
        .warning { color: #d97706; background: #fef3c7; padding: 10px; border-radius: 6px; margin: 5px 0; border: 1px solid #fde68a; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { text-align: center; padding: 12px; border-bottom: 1px solid #e5e7eb; }
        th:first-child, td:first-child { text-align: left; }
        th { background: #f9fafb; font-weight: 600; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: 500; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        
        /* Collapsible Details */
        details { background: white; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 15px; overflow: hidden; }
        summary { padding: 15px; cursor: pointer; background: #f9fafb; font-weight: 600; user-select: none; outline: none; list-style: none; display: flex; justify-content: space-between; align-items: center; position: relative; }
        summary::-webkit-details-marker { display: none; }
        summary:hover { background: #f3f4f6; }
        summary::after { content: '+'; font-size: 18px; color: #6b7280; }
        details[open] summary::after { content: '-'; }
        details[open] summary { border-bottom: 1px solid #e5e7eb; }
        details table { margin: 0; border: none; }
        details table tr:last-child td { border-bottom: none; }
        details .summary-count { background: #e5e7eb; padding: 2px 8px; border-radius: 12px; font-size: 12px; color: #374151; position: absolute; left: 50%; transform: translateX(-50%); }
    </style>
</head>
<body>

    <h1>WP MCP Core Abilities Test Suite</h1>
    
    <?php render_nav( $view, $self_url ); ?>

    <div class="content">
        <?php
        switch ( $view ) {
            case 'diagnostic':
                render_diagnostic_view();
                break;
            case 'registration':
                render_registration_view();
                break;
            case 'hooks':
                render_hooks_view();
                break;
            case 'execution':
                render_execution_view();
                break;
            case 'tools':
                render_tools_view();
                break;
            default:
                echo '<div class="error">Unknown view</div>';
        }
        ?>
    </div>

</body>
</html>

<?php
// -----------------------------------------------------------------------------
// VIEW FUNCTIONS
// -----------------------------------------------------------------------------

function render_diagnostic_view() {
    echo '<div class="card">';
    echo '<h2>System Information</h2>';
    
    // WP Version
    $wp_version = get_bloginfo( 'version' );
    $has_abilities_api = function_exists( 'wp_register_ability' );
    print_status( $has_abilities_api, "WordPress $wp_version (Abilities API available)", "WordPress $wp_version (Abilities API MISSING)" );
    
    // PHP Version
    $php_version = PHP_VERSION;
    $php_ok = version_compare( $php_version, '7.4', '>=' );
    print_status( $php_ok, "PHP $php_version", "PHP $php_version (Requires 7.4+)" );
    
    // Plugin Status
    $plugin_file = 'wp-mcp-core-abilities/wp-mcp-core-abilities.php';
    $plugin_active = is_plugin_active( $plugin_file );
    print_status( $plugin_active, "Plugin Active", "Plugin Inactive" );
    
    echo '<h3>Class Availability</h3>';
    $classes = [
        'WP_MCP_Core_Abilities' => 'Main Plugin Class',
        'WP_MCP_Ability_Registry' => 'Ability Registry',
        'WP_MCP_Core_Site_Abilities' => 'Core Site Abilities',
        'WP_MCP_Ability_Helpers' => 'Helper Trait'
    ];
    
    echo '<table><tr><th>Class/Trait</th><th>Status</th></tr>';
    foreach ( $classes as $class => $desc ) {
        $exists = class_exists( $class ) || trait_exists( $class );
        echo '<tr>';
        echo "<td>$class <span style='color:#6b7280; font-size:0.9em'>($desc)</span></td>";
        echo '<td>' . ( $exists ? '<span class="badge badge-green">Loaded</span>' : '<span class="badge badge-red">Missing</span>' ) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
}

function render_registration_view() {
    echo '<div class="card" style="background:transparent; box-shadow:none; padding:0;">';
    echo '<h2>Registered Abilities & Public Flags</h2>';
    
    if ( ! function_exists( 'wp_get_abilities' ) ) {
        echo '<div class="error">wp_get_abilities() not available.</div>';
        return;
    }
    
    $abilities = wp_get_abilities();
    $wp_mcp_core_count = 0;
    $public_count = 0;
    
    // Group by category
    $by_category = [];
    foreach ( $abilities as $ability ) {
        $cat = $ability->get_category();
        if ( empty( $cat ) ) $cat = 'Uncategorized';
        if ( ! isset( $by_category[ $cat ] ) ) {
            $by_category[ $cat ] = [];
        }
        $by_category[ $cat ][] = $ability;
        
        // Stats
        $name = $ability->get_name();
        $meta = $ability->get_meta();
        $is_core = str_starts_with( $name, 'wp-mcp-core/' ) || str_starts_with( $name, 'core/' );
        if ( $is_core ) $wp_mcp_core_count++;
        
        $is_public = isset( $meta['mcp']['public'] ) && $meta['mcp']['public'] === true;
        if ( $is_public ) $public_count++;
    }

    // Split Core into Themes and Plugins for display
    if ( isset( $by_category['core'] ) ) {
        $core_abilities = $by_category['core'];
        $by_category['core'] = [];
        $by_category['plugins'] = [];
        $by_category['themes'] = [];
        
        foreach ( $core_abilities as $ability ) {
            $name = $ability->get_name();
            if ( strpos( $name, 'plugin' ) !== false ) {
                $by_category['plugins'][] = $ability;
            } elseif ( strpos( $name, 'theme' ) !== false ) {
                $by_category['themes'][] = $ability;
            } else {
                $by_category['core'][] = $ability;
            }
        }
        
        // Clean up empty
        if ( empty( $by_category['plugins'] ) ) unset( $by_category['plugins'] );
        if ( empty( $by_category['themes'] ) ) unset( $by_category['themes'] );
    }
    
    // Custom Sort Order
    $sort_order = [ 'core', 'users', 'content', 'taxonomies', 'themes', 'plugins' ];
    $ordered_categories = [];
    
    // Add sorted items first
    foreach ( $sort_order as $key ) {
        if ( isset( $by_category[ $key ] ) ) {
            $ordered_categories[ $key ] = $by_category[ $key ];
            unset( $by_category[ $key ] );
        }
    }
    
    // Add remaining items (sorted alphabetically)
    ksort( $by_category );
    foreach ( $by_category as $key => $val ) {
        $ordered_categories[ $key ] = $val;
    }
    
    foreach ( $ordered_categories as $cat => $cat_abilities ) {
        $count = count( $cat_abilities );
        $label = ucfirst( $cat );
        
        // Calculate public count for this category
        $cat_public_count = 0;
        foreach ( $cat_abilities as $ability ) {
            $meta = $ability->get_meta();
            if ( isset( $meta['mcp']['public'] ) && $meta['mcp']['public'] === true ) {
                $cat_public_count++;
            }
        }
        
        echo "<details>";
        echo "<summary><span>$label</span> <span class='summary-count'>$cat_public_count / $count Public</span></summary>";
        echo '<table>';
        echo '<tr><th>Ability Name</th><th>Public?</th><th>Meta</th></tr>';
        
        foreach ( $cat_abilities as $ability ) {
            $name = $ability->get_name();
            $meta = $ability->get_meta();
            
            $is_core = str_starts_with( $name, 'wp-mcp-core/' ) || str_starts_with( $name, 'core/' );
            $is_public = isset( $meta['mcp']['public'] ) && $meta['mcp']['public'] === true;
            
            $row_style = $is_core ? 'background-color: #f0fdf4;' : '';
            
            echo "<tr style='$row_style'>";
            echo "<td><strong>" . esc_html( $name ) . "</strong></td>";
            echo "<td>" . ( $is_public ? '<span class="badge badge-green">YES</span>' : '<span class="badge badge-red">NO</span>' ) . "</td>";
            echo "<td><pre style='margin:0; padding:5px; font-size:10px;'>" . esc_html( print_r( $meta, true ) ) . "</pre></td>";
            echo "</tr>";
        }
        echo '</table>';
        echo "</details>";
    }
    
    echo '<div class="card">';
    echo '<div class="info">';
    echo "<p>Total Abilities: <strong>" . count( $abilities ) . "</strong></p>";
    echo "<p>Core Abilities: <strong>$wp_mcp_core_count</strong></p>";
    echo "<p>Public Abilities: <strong>$public_count</strong></p>";
    echo '</div>';
    
    if ( $wp_mcp_core_count === 0 ) {
        echo '<div class="error">No wp-mcp-core abilities found! Check plugin activation and hooks.</div>';
    } elseif ( $public_count < $wp_mcp_core_count ) {
        echo '<div class="warning">Some core abilities are not marked as public.</div>';
    } else {
        echo '<div class="success">All core abilities appear to be registered and public.</div>';
    }
    echo '</div>';
    echo '</div>';
}

function render_hooks_view() {
    echo '<div class="card">';
    echo '<h2>Hook Lifecycle & Trace</h2>';
    
    $hooks = [
        'plugins_loaded',
        'muplugins_loaded',
        'wp_abilities_api_init',
        'wp_abilities_api_categories_init',
        'init'
    ];
    
    echo '<table><tr><th>Hook</th><th>Fired Count</th></tr>';
    foreach ( $hooks as $hook ) {
        $count = did_action( $hook );
        echo "<tr><td>$hook</td><td><strong>$count</strong></td></tr>";
    }
    echo '</table>';
    
    echo '<h3>Callbacks on wp_abilities_api_init</h3>';
    global $wp_filter;
    if ( isset( $wp_filter['wp_abilities_api_init'] ) ) {
        echo '<pre>';
        foreach ( $wp_filter['wp_abilities_api_init']->callbacks as $priority => $callbacks ) {
            echo "Priority $priority:\n";
            foreach ( $callbacks as $idx => $callback ) {
                $name = 'Unknown';
                if ( is_array( $callback['function'] ) ) {
                    if ( is_string( $callback['function'][0] ) ) {
                        $name = $callback['function'][0] . '::' . $callback['function'][1];
                    } elseif ( is_object( $callback['function'][0] ) ) {
                        $name = get_class( $callback['function'][0] ) . '::' . $callback['function'][1];
                    }
                } elseif ( is_string( $callback['function'] ) ) {
                    $name = $callback['function'];
                } elseif ( $callback['function'] instanceof Closure ) {
                    $name = 'Closure';
                }
                echo "  - $name\n";
            }
        }
        echo '</pre>';
    } else {
        echo '<div class="error">No callbacks registered for wp_abilities_api_init</div>';
    }
    echo '</div>';
}

function render_execution_view() {
    echo '<div class="card">';
    echo '<h2>Execution Tests</h2>';
    echo '<p>Running a subset of safe read-only tests to verify execution logic.</p>';
    
    if ( ! class_exists( 'WP_MCP_Core_Site_Abilities' ) ) {
        echo '<div class="error">Core classes not loaded. Cannot run tests.</div>';
        return;
    }
    
    // Instantiate classes
    $core_site = new WP_MCP_Core_Site_Abilities();
    
    // Helper to run test
    $run_test = function( $name, $callback ) {
        echo "<div style='margin-bottom:10px; border:1px solid #eee; padding:10px; border-radius:4px;'>";
        echo "<strong>$name</strong>: ";
        try {
            $result = $callback();
            if ( is_wp_error( $result ) ) {
                echo "<span class='badge badge-red'>WP_Error</span>";
                echo "<pre>" . esc_html( $result->get_error_message() ) . "</pre>";
            } elseif ( isset( $result['error'] ) ) {
                echo "<span class='badge badge-red'>API Error</span>";
                echo "<pre>" . esc_html( print_r( $result, true ) ) . "</pre>";
            } else {
                echo "<span class='badge badge-green'>Success</span>";
                // Truncate output for display
                $output = print_r( $result, true );
                if ( strlen( $output ) > 500 ) $output = substr( $output, 0, 500 ) . '... (truncated)';
                echo "<pre>" . esc_html( $output ) . "</pre>";
            }
        } catch ( Exception $e ) {
            echo "<span class='badge badge-red'>Exception</span>";
            echo "<pre>" . esc_html( $e->getMessage() ) . "</pre>";
        }
        echo "</div>";
    };
    
    // Run Tests
    $run_test( 'get-site-info', function() use ( $core_site ) {
        return $core_site->execute_get_site_info();
    });
    
    $run_test( 'list-plugins', function() use ( $core_site ) {
        return $core_site->execute_list_plugins();
    });
    
    $run_test( 'list-themes', function() use ( $core_site ) {
        return $core_site->execute_list_themes();
    });
    
    $run_test( 'list-menus', function() use ( $core_site ) {
        return $core_site->execute_list_menus();
    });
    
    echo '</div>';
}

function render_tools_view() {
    echo '<div class="card">';
    echo '<h2>Tools</h2>';
    
    // Reload Action
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'reload_plugin' ) {
        $plugin = 'wp-mcp-core-abilities/wp-mcp-core-abilities.php';
        deactivate_plugins( $plugin );
        if ( function_exists( 'opcache_reset' ) ) opcache_reset();
        activate_plugin( $plugin );
        echo '<div class="success">Plugin reloaded successfully!</div>';
    }
    
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="reload_plugin">';
    echo '<button type="submit" style="background:#2563eb; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-size:16px;">Reload Plugin</button>';
    echo '<p style="margin-top:10px; color:#6b7280;">Deactivates and reactivates the plugin to force re-initialization.</p>';
    echo '</form>';
    echo '</div>';
}
?>