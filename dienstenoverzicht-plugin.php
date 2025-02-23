<?php
/**
 * Plugin Name: Dienstenoverzicht Plugin
 * Description: Toont een overzicht van alle aangeboden diensten met een korte beschrijving en link naar meer informatie. Beheer de diensten via de WordPress Admin.
 * Version: 2.3.1
 * Author: Jouw Naam
 * Text Domain: dienstenoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Definieer een constante voor de plugin URL
if ( ! defined( 'DIENSTENOVERZICHT_PLUGIN_URL' ) ) {
    define( 'DIENSTENOVERZICHT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Definieer een constante voor de plugin path
if ( ! defined( 'DIENSTENOVERZICHT_PLUGIN_PATH' ) ) {
    define( 'DIENSTENOVERZICHT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

class Dienstenoverzicht {

    /**
     * Instance of the class
     *
     * @var Dienstenoverzicht
     */
    private static $instance = null;

    /**
     * Get instance of the class
     *
     * @return Dienstenoverzicht
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Set up hooks and localization
     */
    private function __construct() {
        $this->load_textdomain();
        $this->register_hooks();
    }

    /**
     * Load plugin textdomain for translations
     */
    private function load_textdomain() {
        load_plugin_textdomain('dienstenoverzicht', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Register all necessary hooks
     */
    private function register_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'register_wpml_compatibility'));
        add_action('wpml_loaded', array($this, 'register_wpml_post_types'));

        $this->include_required_files();
        $this->register_ajax_actions();
    }

    /**
     * Actions to perform on plugin activation
     */
    public function activate() {
        $this->register_post_types();
        $this->register_taxonomies();
        flush_rewrite_rules(); // Flush rewrite rules to ensure custom post types are recognized
    }

    /**
     * Actions to perform on plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules(); // Flush rewrite rules to clean up
    }

    /**
     * Register WPML and Polylang compatibility
     */
    public function register_wpml_compatibility() {
        if (function_exists('pll_register_string')) {
            pll_register_string('dienstenoverzicht', 'Diensten Overzicht Plugin', 'dienstenoverzicht');
        }
    }

    /**
     * Register custom post types and taxonomies for WPML
     */
    public function register_wpml_post_types() {
        do_action('wpml_register_post_type_action', 'diensten');
    }

    /**
     * Include required files for the plugin
     */
    private function include_required_files() {
        $includes_path = DIENSTENOVERZICHT_PLUGIN_PATH . 'includes/';

        require_once $includes_path . 'class-dienstenoverzicht-i18n.php';
        require_once $includes_path . 'class-dienstenoverzicht-cpt.php';
        require_once $includes_path . 'class-dienstenoverzicht-metaboxes.php';
        require_once $includes_path . 'class-dienstenoverzicht-shortcode.php';
        require_once $includes_path . 'class-dienstenoverzicht-assets.php';
        require_once $includes_path . 'class-dienstenoverzicht-taxonomy.php';
        require_once $includes_path . 'class-dienstenoverzicht-admin.php';
        require_once $includes_path . 'class-dienstenoverzicht-import-export.php';
    }

    /**
     * Register AJAX actions for the plugin
     */
    private function register_ajax_actions() {
        add_action('wp_ajax_dienstenoverzicht_filter', array($this, 'dienstenoverzicht_filter'));
        add_action('wp_ajax_nopriv_dienstenoverzicht_filter', array($this, 'dienstenoverzicht_filter'));
    }

    /**
     * AJAX handler for filtering diensten
     */
    public function dienstenoverzicht_filter() {
        check_ajax_referer('dienstenoverzicht_nonce', 'security');

        $args = array(
            'post_type'      => 'diensten',
            'posts_per_page' => -1,
            'meta_query'     => array(),
            'tax_query'      => array(),
        );

        if (!empty($_POST['filters'])) {
            parse_str($_POST['filters'], $filters);
            // Process filters here and update $args accordingly
        }

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                // Output post content or relevant data here
            }
        } else {
            echo esc_html(__('No services found matching your criteria.', 'dienstenoverzicht'));
        }

        wp_die(); // This is required to terminate immediately and return a proper response
    }

    /**
     * Register custom post types for diensten
     */
    public function register_post_types() {
        // Register custom post types here
    }

    /**
     * Register custom taxonomies for diensten
     */
    public function register_taxonomies() {
        // Register custom taxonomies here
    }
}

// Initialize the plugin
Dienstenoverzicht::get_instance();