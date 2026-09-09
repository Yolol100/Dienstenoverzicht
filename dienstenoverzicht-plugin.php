<?php
/**
 * Plugin Name: Dienstenoverzicht Plugin
 * Plugin URI: https://webactueel.nl/
 * Description: Toont een veilig, beheerbaar dienstenoverzicht met custom post type, shortcode, instellingen en CSV-import/export.
 * Version: 3.0.1
 * Author: Webactueel
 * Author URI: https://webactueel.nl/
 * Text Domain: dienstenoverzicht
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Dienstenoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DIENSTENOVERZICHT_VERSION', '3.0.1' );
define( 'DIENSTENOVERZICHT_PLUGIN_FILE', __FILE__ );
define( 'DIENSTENOVERZICHT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DIENSTENOVERZICHT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Main plugin bootstrap.
 */
final class Dienstenoverzicht {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Returns the plugin instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Loads files and registers hooks.
	 */
	private function __construct() {
		$this->include_required_files();
		$this->register_hooks();
	}

	/**
	 * Includes runtime classes.
	 */
	private function include_required_files(): void {
		$includes_path = DIENSTENOVERZICHT_PLUGIN_PATH . 'includes/';

		require_once $includes_path . 'class-dienstenoverzicht-cpt.php';
		require_once $includes_path . 'class-dienstenoverzicht-taxonomy.php';
		require_once $includes_path . 'class-dienstenoverzicht-metaboxes.php';
		require_once $includes_path . 'class-dienstenoverzicht-shortcode.php';
		require_once $includes_path . 'class-dienstenoverzicht-assets.php';
		require_once $includes_path . 'class-dienstenoverzicht-admin.php';
		require_once $includes_path . 'class-dienstenoverzicht-import-export.php';
	}

	/**
	 * Registers all WordPress hooks.
	 */
	private function register_hooks(): void {
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'init', [ 'Dienstenoverzicht_CPT', 'register_post_type' ], 0 );
		add_action( 'init', [ 'Dienstenoverzicht_Taxonomy', 'register_taxonomy' ], 0 );

		Dienstenoverzicht_Metaboxes::init();
		Dienstenoverzicht_Admin::init();
		Dienstenoverzicht_ImportExport::init();
		Dienstenoverzicht_Assets::init();
		Dienstenoverzicht_Shortcode::init();
	}

	/**
	 * Runs on activation.
	 */
	public function activate(): void {
		Dienstenoverzicht_CPT::register_post_type();
		Dienstenoverzicht_Taxonomy::register_taxonomy();
		flush_rewrite_rules();
	}

	/**
	 * Runs on deactivation.
	 */
	public function deactivate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Loads translations.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'dienstenoverzicht', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}

Dienstenoverzicht::get_instance();
