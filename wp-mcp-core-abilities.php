<?php
/**
 * Plugin Name: WP MCP Core Abilities
 * Plugin URI: https://strongclose.ai
 * Description: Comprehensive WordPress abilities for MCP Adapter - exposes core WordPress functionality to AI agents
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Author: Kyle Alm
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-mcp-core-abilities
 *
 * @package WP_MCP_Core_Abilities
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'WP_MCP_CORE_ABILITIES_VERSION', '1.0.0' );
define( 'WP_MCP_CORE_ABILITIES_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_MCP_CORE_ABILITIES_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 */
class WP_MCP_Core_Abilities {

	/**
	 * Singleton instance.
	 *
	 * @var WP_MCP_Core_Abilities
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return WP_MCP_Core_Abilities
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Initialize immediately - don't wait for plugins_loaded.
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init() {
		// Check if wp_register_ability function exists (more reliable than version check).
		if ( ! function_exists( 'wp_register_ability' ) ) {
			add_action( 'admin_notices', array( $this, 'abilities_api_notice' ) );
			return;
		}

		// Load required files.
		$this->load_dependencies();

		// Register categories on the wp_abilities_api_categories_init hook.
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ), 5 );

		// Register abilities on the wp_abilities_api_init hook.
		// We must register early (priority 5) to ensure we're before other systems.
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ), 5 );

		// Add diagnostic link.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_links' ) );
	}

	/**
	 * Load plugin dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		// Load trait.
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/traits/trait-ability-helpers.php';

		// Load ability categories.
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/class-ability-categories.php';

		// Load ability registry.
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/class-ability-registry.php';

		// Load ability classes.
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-core-site-abilities.php';
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-post-abilities.php';
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-page-abilities.php';
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-taxonomy-abilities.php';
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-user-abilities.php';
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-comment-abilities.php';
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-media-abilities.php';
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-block-abilities.php';
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-theme-abilities.php';
		require_once WP_MCP_CORE_ABILITIES_PATH . 'includes/abilities/class-plugin-abilities.php';
	}

	/**
	 * Register all ability categories.
	 *
	 * @return void
	 */
	public function register_categories() {
		WP_MCP_Ability_Categories::register_all();
	}

	/**
	 * Register all abilities.
	 *
	 * @return void
	 */
	   public function register_abilities() {
		   WP_MCP_Ability_Registry::register_all();
		   
		   // All ACF ability registration and checks have been removed from core. Only core CPT logic remains.
	   }

	/**
	 * Display version notice.
	 *
	 * @return void
	 */
	public function version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: Required WordPress version */
						__( 'WP MCP Core Abilities requires WordPress %s or higher.', 'wp-mcp-core-abilities' ),
						'6.9'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add links to the plugin action links.
	 *
	 * @param array $links Plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_plugin_links( $links ) {
		$diagnostic_link = '<a href="' . esc_url( plugins_url( 'mcp-test-suite.php', __FILE__ ) ) . '">' . __( 'Diagnostic Page', 'wp-mcp-core-abilities' ) . '</a>';
		array_unshift( $links, $diagnostic_link );
		return $links;
	}

	/**
	 * Display abilities API notice.
	 *
	 * @return void
	 */
	public function abilities_api_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				esc_html_e(
					'WP MCP Core Abilities requires the WordPress Abilities API. Please ensure you are running WordPress 6.9 or higher.',
					'wp-mcp-core-abilities'
				);
				?>
			</p>
		</div>
		<?php
	}
}

// Initialize the plugin.
WP_MCP_Core_Abilities::get_instance();
