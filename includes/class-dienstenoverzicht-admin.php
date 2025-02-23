<?php

namespace Dienstenoverzicht\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('Dienstenoverzicht_Admin')) {

    /**
     * Class Dienstenoverzicht_Admin
     *
     * Beheert de administratieve instellingen en functionaliteiten voor het Dienstenoverzicht.
     *
     * @package Dienstenoverzicht\Admin
     */
    final class Dienstenoverzicht_Admin {

        // Define constants for option group and page slug
        private const OPTION_GROUP = 'dienstenoverzicht_settings_group';
        private const SETTINGS_PAGE = 'dienstenoverzicht-settings';

        /**
         * Initialize the admin class by setting up hooks.
         *
         * Registers all necessary hooks for the admin functionality.
         *
         * @return void
         */
        public static function init(): void {
            add_action('admin_init', [__CLASS__, 'register_settings']);
            add_action('admin_menu', [__CLASS__, 'add_settings_page']);
            add_action('manage_diensten_posts_custom_column', [__CLASS__, 'custom_columns_content'], 10, 2);
            add_action('restrict_manage_posts', [__CLASS__, 'filter_by_taxonomy'], 10);
            add_action('admin_notices', [__CLASS__, 'settings_saved_notice'], 10);
            add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets'], 10);
        }

        /**
         * Add custom columns to the 'diensten' post type.
         *
         * @param array $columns Existing columns.
         * @return array Modified columns.
         */
        public static function add_custom_columns(array $columns): array {
            $columns['prijs'] = __('Prijsinformatie', 'dienstenoverzicht');
            $columns['samenvatting'] = __('Samenvatting', 'dienstenoverzicht');
            return apply_filters('dienstenoverzicht_custom_columns', $columns);
        }

        /**
         * Populate custom columns with content.
         *
         * @param string $column  Column name.
         * @param int    $post_id Post ID.
         * @return void
         */
        public static function custom_columns_content(string $column, int $post_id): void {
            switch ($column) {
                case 'prijs':
                    $basispakket_prijzen = get_post_meta($post_id, '_basispakket_prijzen', true);
                    $uitgebreid_pakket_prijzen = get_post_meta($post_id, '_uitgebreid_pakket_prijzen', true);
                    echo esc_html($basispakket_prijzen) . '<br>' . esc_html($uitgebreid_pakket_prijzen);
                    break;
                case 'samenvatting':
                    $samenvatting = get_post_meta($post_id, '_samenvatting', true);
                    echo esc_html($samenvatting);
                    break;
                default:
                    // Optioneel: Handle onbekende kolommen
                    break;
            }
            do_action('dienstenoverzicht_custom_columns_content', $column, $post_id);
        }

        /**
         * Add taxonomy filter dropdown to the 'diensten' post type list.
         *
         * @return void
         */
        public static function filter_by_taxonomy(): void {
            global $typenow;
            if ($typenow === 'diensten') {
                $taxonomy = 'dienstenoverzicht_categorie';
                if (!taxonomy_exists($taxonomy)) {
                    return;
                }

                $selected = isset($_GET[$taxonomy]) ? intval($_GET[$taxonomy]) : '';
                $info_taxonomy = get_taxonomy($taxonomy);
                if ($info_taxonomy) {
                    wp_dropdown_categories([
                        'show_option_all' => sprintf(esc_html__('Toon alle %s', 'dienstenoverzicht'), esc_html($info_taxonomy->label)),
                        'taxonomy'        => $taxonomy,
                        'name'            => esc_attr($taxonomy),
                        'orderby'         => 'name',
                        'selected'        => $selected,
                        'show_count'      => true,
                        'hide_empty'      => true,
                        'class'           => 'postform',
                    ]);
                }
            }
            do_action('dienstenoverzicht_filter_by_taxonomy');
        }

        /**
         * Add settings page to the admin menu.
         *
         * @return void
         */
        public static function add_settings_page(): void {
            $hook = add_submenu_page(
                'edit.php?post_type=diensten',
                __('Dienstenoverzicht Instellingen', 'dienstenoverzicht'),
                __('Instellingen', 'dienstenoverzicht'),
                'manage_options',
                self::SETTINGS_PAGE,
                [__CLASS__, 'render_settings_page']
            );

            add_action("load-$hook", [__CLASS__, 'add_help_tab']);
        }

        /**
         * Render the settings page.
         *
         * @return void
         */
        public static function render_settings_page(): void {
            ?>
            <div class="wrap dienstenoverzicht-settings-wrap">
                <h1><?php echo esc_html__('Dienstenoverzicht Instellingen', 'dienstenoverzicht'); ?></h1>
                <form method="post" action="options.php" class="dienstenoverzicht-settings-form">
                    <?php
                    settings_fields(self::OPTION_GROUP);
                    ?>
                    <input type="hidden" name="active_tab" id="active_tab" value="<?php echo esc_attr(self::get_active_tab()); ?>">
                    <h2 class="nav-tab-wrapper">
                        <a href="#general-settings" class="nav-tab <?php echo self::is_active_tab('general-settings') ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons dashicons-admin-generic"></span> <?php echo esc_html__('Algemene Instellingen', 'dienstenoverzicht'); ?>
                        </a>
                        <a href="#style-settings" class="nav-tab <?php echo self::is_active_tab('style-settings') ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons dashicons-art"></span> <?php echo esc_html__('Stijl Instellingen', 'dienstenoverzicht'); ?>
                        </a>
                        <a href="#custom-code-settings" class="nav-tab <?php echo self::is_active_tab('custom-code-settings') ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons dashicons-editor-code"></span> <?php echo esc_html__('Aangepaste Code', 'dienstenoverzicht'); ?>
                        </a>
                        <a href="#integration-settings" class="nav-tab <?php echo self::is_active_tab('integration-settings') ? 'nav-tab-active' : ''; ?>">
                            <span class="dashicons dashicons-admin-network"></span> <?php echo esc_html__('Externe Integraties', 'dienstenoverzicht'); ?>
                        </a>
                    </h2>
                    <div id="general-settings" class="tab-content" style="display: <?php echo self::is_active_tab('general-settings') ? 'block' : 'none'; ?>;">
                        <table class="form-table">
                            <?php
                            // Algemene Instellingen
                            do_settings_sections(self::SETTINGS_PAGE . '-general-settings');
                            ?>
                        </table>
                    </div>
                    <div id="style-settings" class="tab-content" style="display: <?php echo self::is_active_tab('style-settings') ? 'block' : 'none'; ?>;">
                        <table class="form-table">
                            <?php
                            // Stijl Instellingen
                            do_settings_sections(self::SETTINGS_PAGE . '-style-settings');
                            ?>
                        </table>
                    </div>
                    <div id="custom-code-settings" class="tab-content" style="display: <?php echo self::is_active_tab('custom-code-settings') ? 'block' : 'none'; ?>;">
                        <table class="form-table">
                            <?php
                            // Aangepaste Code
                            do_settings_sections(self::SETTINGS_PAGE . '-custom-code-settings');
                            ?>
                        </table>
                    </div>
                    <div id="integration-settings" class="tab-content" style="display: <?php echo self::is_active_tab('integration-settings') ? 'block' : 'none'; ?>;">
                        <table class="form-table">
                            <?php
                            // Externe Integraties
                            do_settings_sections(self::SETTINGS_PAGE . '-integration-settings');
                            ?>
                        </table>
                    </div>
                    <?php submit_button(); ?>
                </form>
                <?php self::render_inline_assets(); ?>
            </div>
            <?php
        }

        /**
         * Render inline CSS and JavaScript assets.
         *
         * @return void
         */
        private static function render_inline_assets(): void {
            ?>
            <style>
                <?php echo self::get_inline_css(); ?>
            </style>
            <script>
                <?php echo self::get_inline_js(); ?>
            </script>
            <?php
        }

        /**
         * Get inline CSS for the settings page.
         *
         * @return string CSS styles.
         */
        private static function get_inline_css(): string {
            return <<<CSS
/* CSS Variabelen voor Consistentie */
:root {
    --tab-active-bg: #ffffff;
    --tab-inactive-bg: #f1f1f1;
    --tab-active-border: #0073aa;
    --tab-inactive-border: #ccc;
    --tab-active-color: #0073aa;
    --tab-inactive-color: #555555;
    --transition-speed: 0.3s;
    --form-table-th-width: 250px;
}

/* Algemeen */
.dienstenoverzicht-settings-wrap {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

/* Tabs */
.nav-tab-wrapper {
    border-bottom: 2px solid #ccc;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.nav-tab {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    background: var(--tab-inactive-bg);
    border: 1px solid var(--tab-inactive-border);
    border-bottom: none;
    border-radius: 5px 5px 0 0;
    color: var(--tab-inactive-color);
    text-decoration: none;
    transition: background var(--transition-speed), color var(--transition-speed), border-color var(--transition-speed);
    font-weight: 600;
}

.nav-tab .dashicons {
    margin-right: 8px;
}

.nav-tab:hover {
    background: #e1e1e1;
    color: #000000;
}

.nav-tab-active {
    background: var(--tab-active-bg);
    border-color: var(--tab-active-border);
    color: var(--tab-active-color);
    font-weight: bold;
}

/* Tab Content */
.tab-content {
    border: 1px solid #ccc;
    padding: 20px;
    background: #ffffff;
    border-radius: 0 5px 5px 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    animation: fadeIn var(--transition-speed) ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Form Table */
table.form-table th {
    width: var(--form-table-th-width);
    padding: 10px 15px;
    text-align: left;
    vertical-align: top;
    font-weight: 600;
    color: #333333;
}

table.form-table td {
    padding: 10px 15px;
    vertical-align: middle;
}

/* Form Inputs */
.dienstenoverzicht-settings-form input[type="text"],
.dienstenoverzicht-settings-form input[type="number"],
.dienstenoverzicht-settings-form input[type="color"],
.dienstenoverzicht-settings-form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1em;
    background-color: #ffffff;
    transition: border-color var(--transition-speed), box-shadow var(--transition-speed);
}

.dienstenoverzicht-settings-form input[type="text"]:focus,
.dienstenoverzicht-settings-form input[type="number"]:focus,
.dienstenoverzicht-settings-form input[type="color"]:focus,
.dienstenoverzicht-settings-form textarea:focus {
    border-color: #0073aa;
    outline: none;
    box-shadow: 0 0 5px rgba(0, 115, 170, 0.5);
}

.dienstenoverzicht-settings-form input[type="checkbox"] {
    transform: scale(1.2);
    margin-right: 10px;
    vertical-align: middle;
}

/* Submit Button */
.dienstenoverzicht-settings-form input[type="submit"].button-primary {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    background-color: #0073aa;
    color: #ffffff;
    text-transform: uppercase;
    font-weight: bold;
    cursor: pointer;
    transition: background-color var(--transition-speed), box-shadow var(--transition-speed);
    margin-top: 20px;
}

.dienstenoverzicht-settings-form input[type="submit"].button-primary:hover {
    background-color: #005177;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.dienstenoverzicht-settings-form input[type="submit"].button-primary:focus {
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.5);
    outline: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    table.form-table th {
        width: 100%;
        display: block;
        margin-bottom: 10px;
    }

    table.form-table td {
        display: block;
        width: 100%;
    }

    .nav-tab-wrapper {
        flex-direction: column;
    }

    .nav-tab {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .dienstenoverzicht-settings-wrap {
        padding: 10px;
    }

    .nav-tab {
        padding: 8px 12px;
        font-size: 0.9em;
    }

    table.form-table th,
    table.form-table td {
        padding: 8px 10px;
    }
}
CSS;
        }

        /**
         * Get inline JavaScript for the settings page.
         *
         * @return string JavaScript code.
         */
        private static function get_inline_js(): string {
            return <<<JS
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.nav-tab');
    const contents = document.querySelectorAll('.tab-content');
    const activeTabInput = document.getElementById('active_tab');
    const form = document.querySelector('.dienstenoverzicht-settings-form');

    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();

            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            // Hide all content
            contents.forEach(c => c.style.display = 'none');

            // Add active class to clicked tab
            this.classList.add('nav-tab-active');
            // Show corresponding content
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.style.display = 'block';
            }
            // Update hidden input
            activeTabInput.value = this.getAttribute('href').substring(1);
        });
    });

    // Initialize active tab based on hidden input
    const initialActiveTab = activeTabInput.value;
    if (initialActiveTab) {
        const activeTab = document.querySelector('.nav-tab[href="#' + initialActiveTab + '"]');
        if (activeTab) {
            activeTab.click();
        }
    } else {
        // Default to the first tab
        if (tabs.length > 0) {
            tabs[0].click();
        }
    }

    // Handle form submission to include the 'tab' parameter in the URL
    form.addEventListener('submit', function(e) {
        const activeTab = activeTabInput.value;
        if (activeTab) {
            const currentAction = form.action;
            const url = new URL(currentAction);
            url.searchParams.set('tab', activeTab);
            form.action = url.toString();
        }
    });

    // Initialize WordPress color picker
    if (typeof jQuery !== 'undefined') {
        jQuery('.color-field').wpColorPicker();
    }
});
JS;
        }

        /**
         * Determine the active tab based on GET or POST data.
         *
         * @return string Active tab ID.
         */
        private static function get_active_tab(): string {
            if (isset($_POST['active_tab'])) {
                return sanitize_text_field($_POST['active_tab']);
            } elseif (isset($_GET['tab'])) {
                return sanitize_text_field($_GET['tab']);
            }
            return 'general-settings';
        }

        /**
         * Check if the given tab is active.
         *
         * @param string $tab_id Tab ID to check.
         * @return bool Whether the tab is active.
         */
        private static function is_active_tab(string $tab_id): bool {
            return self::get_active_tab() === $tab_id;
        }

        /**
         * Register settings, sections, and fields.
         *
         * @return void
         */
        public static function register_settings(): void {
            // Controleer of de gebruiker de juiste rechten heeft
            if (!current_user_can('manage_options')) {
                return;
            }

            // Define settings with consistent hyphenated keys
            $settings = [
                'general-settings' => [
                    'title'             => __('Titel:', 'dienstenoverzicht'),
                    'items_per_page'    => __('Aantal items per pagina:', 'dienstenoverzicht'),
                    'show_prices'       => __('Prijzen tonen:', 'dienstenoverzicht'),
                    'gratis_advies_url' => __('URL voor gratis advies:', 'dienstenoverzicht'),
                ],
                'style-settings' => [
                    'font_size'           => __('Lettergrootte:', 'dienstenoverzicht'),
                    'summary_font_size'   => __('Lettergrootte samenvatting:', 'dienstenoverzicht'),
                    'button_font_size'    => __('Lettergrootte knop:', 'dienstenoverzicht'),
                    'title_font_size'     => __('Lettergrootte titel:', 'dienstenoverzicht'),
                    'prijs_font_size'     => __('Lettergrootte prijs:', 'dienstenoverzicht'),
                    'title_color'         => __('Kleur titel:', 'dienstenoverzicht'),
                    'prijs_color'         => __('Kleur prijs:', 'dienstenoverzicht'),
                    'button_color'        => __('Kleur knop:', 'dienstenoverzicht'),
                    'button_hover_color'  => __('Kleur knop hover:', 'dienstenoverzicht'),
                ],
                'custom-code-settings' => [
                    'custom_css' => __('Aangepaste CSS:', 'dienstenoverzicht'),
                    'custom_js'  => __('Aangepaste JavaScript:', 'dienstenoverzicht'),
                ],
                'integration-settings' => [
                    'integration_service' => __('Integratieservice URL:', 'dienstenoverzicht'),
                    'integration_api_key' => __('API Sleutel:', 'dienstenoverzicht'),
                ],
            ];

            // Register settings
            foreach ($settings as $section_key => $fields) {
                foreach ($fields as $option_key => $label) {
                    $option_name = "dienstenoverzicht_{$option_key}";
                    // Determine sanitization callback based on field type
                    $sanitize_callback = self::get_sanitize_callback($option_key);
                    register_setting(self::OPTION_GROUP, $option_name, [
                        'sanitize_callback' => [__CLASS__, $sanitize_callback],
                    ]);
                }
            }

            // Define sections and fields
            foreach ($settings as $section_key => $fields) {
                $section_title = ucfirst(str_replace('-', ' ', $section_key)) . ' ' . __('Instellingen', 'dienstenoverzicht');
                add_settings_section(
                    "dienstenoverzicht_{$section_key}_section",
                    __($section_title, 'dienstenoverzicht'),
                    null,
                    self::SETTINGS_PAGE . '-' . $section_key
                );

                foreach ($fields as $option_key => $label) {
                    $option_name = "dienstenoverzicht_{$option_key}";
                    add_settings_field(
                        $option_name,
                        '',
                        [__CLASS__, 'render_field'],
                        self::SETTINGS_PAGE . '-' . $section_key,
                        "dienstenoverzicht_{$section_key}_section",
                        [
                            'id'      => $option_name,
                            'label'   => $label,
                            'type'    => self::get_field_type($option_key),
                            'default' => self::get_default_value($option_key),
                        ]
                    );
                }
            }
        }

        /**
         * Determine the field type based on the option key.
         *
         * @param string $option_key The option key.
         * @return string Field type ('text', 'checkbox', 'color', 'textarea').
         */
        private static function get_field_type(string $option_key): string {
            $checkboxes = ['show_prices'];
            $colors = ['title_color', 'prijs_color', 'button_color', 'button_hover_color'];
            $textareas = ['custom_css', 'custom_js'];

            if (in_array($option_key, $checkboxes, true)) {
                return 'checkbox';
            } elseif (in_array($option_key, $colors, true)) {
                return 'color';
            } elseif (in_array($option_key, $textareas, true)) {
                return 'textarea';
            }
            return 'text';
        }

        /**
         * Get default value based on the option key.
         *
         * @param string $option_key The option key.
         * @return mixed Default value.
         */
        private static function get_default_value(string $option_key) {
            $defaults = [
                'show_prices'          => 1,
                'gratis_advies_url'    => 'https://voorbeeld.nl/advies',
                'font_size'            => '14px',
                'summary_font_size'    => '14px',
                'button_font_size'     => '14px',
                'title_font_size'      => '16px',
                'prijs_font_size'      => '14px',
                'title_color'          => '#000000',
                'prijs_color'          => '#000000',
                'button_color'         => '#0073aa',
                'button_hover_color'   => '#005a87',
                'custom_css'           => '',
                'custom_js'            => '',
                'integration_service'  => 'https://voorbeeld.nl/api',
                'integration_api_key'  => 'API Sleutel',
            ];

            return $defaults[$option_key] ?? '';
        }

        /**
         * Get sanitization callback based on the option key.
         *
         * @param string $option_key The option key.
         * @return string Sanitization callback method name.
         */
        private static function get_sanitize_callback(string $option_key): string {
            $checkboxes = ['show_prices'];
            $urls = ['gratis_advies_url', 'integration_service'];
            $api_keys = ['integration_api_key'];

            if (in_array($option_key, $checkboxes, true)) {
                return 'sanitize_checkbox';
            } elseif (in_array($option_key, $urls, true)) {
                return 'sanitize_url';
            } elseif (in_array($option_key, $api_keys, true)) {
                return 'sanitize_text';
            } elseif (in_array($option_key, ['custom_css', 'custom_js'], true)) {
                return 'sanitize_custom_code';
            }
            return 'sanitize_text';
        }

        /**
         * Render the appropriate field based on its type.
         *
         * @param array $args Arguments passed to the field callback.
         * @return void
         */
        public static function render_field(array $args): void {
            $id = esc_attr($args['id']);
            $label = esc_html($args['label']);
            $type = esc_attr($args['type']);
            $default = esc_attr($args['default']);
            $value = get_option($id, $default);

            echo '<tr>';
            echo '<th scope="row"><label for="' . $id . '">' . $label . '</label></th>';
            echo '<td>';

            switch ($type) {
                case 'checkbox':
                    echo '<label>';
                    echo '<input type="checkbox" id="' . $id . '" name="' . $id . '" value="1"' . checked(1, $value, false) . ' />';
                    echo ' ' . esc_html__('Ja', 'dienstenoverzicht');
                    echo '</label>';
                    break;

                case 'color':
                    echo '<input type="text" class="color-field" id="' . $id . '" name="' . $id . '" value="' . esc_attr($value) . '" />';
                    break;

                case 'textarea':
                    echo '<textarea id="' . $id . '" name="' . $id . '" rows="10" cols="50">' . esc_textarea($value) . '</textarea>';
                    break;

                case 'text':
                default:
                    echo '<input type="text" id="' . $id . '" name="' . $id . '" value="' . esc_attr($value) . '" />';
                    break;
            }

            echo '</td>';
            echo '</tr>';
        }

        /**
         * Enqueue necessary scripts and styles.
         *
         * @param string $hook_suffix Current admin page hook.
         * @return void
         */
        public static function enqueue_assets(string $hook_suffix): void {
            if ($hook_suffix !== 'diensten_page_' . self::SETTINGS_PAGE) {
                return;
            }

            // Enqueue WordPress color picker
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');

            // Enqueue jQuery (if not already included)
            wp_enqueue_script('jquery');
        }

        /**
         * Add a help tab to the settings page.
         *
         * @return void
         */
        public static function add_help_tab(): void {
            $screen = get_current_screen();

            if ($screen->id !== 'diensten_page_' . self::SETTINGS_PAGE) {
                return;
            }

            $screen->add_help_tab([
                'id'      => 'dienstenoverzicht_help_tab',
                'title'   => __('Help', 'dienstenoverzicht'),
                'content' => '<p>' . esc_html__('Hier vind je alle instellingen voor het Dienstenoverzicht. Gebruik de tabbladen bovenaan om verschillende secties te navigeren.', 'dienstenoverzicht') . '</p>',
            ]);

            $screen->set_help_sidebar(
                '<p><strong>' . esc_html__('Meer informatie:', 'dienstenoverzicht') . '</strong></p>' .
                '<p>' . esc_html__('Voor verdere ondersteuning, bezoek onze documentatie of neem contact op met de ontwikkelaar.', 'dienstenoverzicht') . '</p>'
            );
        }

        /**
         * Display an admin notice after settings are saved.
         *
         * @return void
         */
        public static function settings_saved_notice(): void {
            if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Instellingen zijn succesvol opgeslagen.', 'dienstenoverzicht'); ?></p>
                </div>
                <?php
            }
        }

        /**
         * Custom sanitization callback for custom CSS and JS.
         *
         * @param string $input The input string.
         * @return string Sanitized input.
         */
        public static function sanitize_custom_code(string $input): string {
            // Remove PHP tags for security
            $input = preg_replace('/<\?php/i', '', $input);
            // Strip all HTML tags to allow only plain text (CSS/JS code)
            $input = wp_strip_all_tags($input);
            return $input;
        }

        /**
         * Custom sanitization callback for checkboxes.
         *
         * @param mixed $input The input value.
         * @return int Sanitized value.
         */
        public static function sanitize_checkbox($input): int {
            return $input ? 1 : 0;
        }

        /**
         * Custom sanitization callback for URLs.
         *
         * @param string $input The input URL.
         * @return string Sanitized URL.
         */
        public static function sanitize_url(string $input): string {
            return esc_url_raw($input);
        }

        /**
         * Custom sanitization callback for text fields.
         *
         * @param string $input The input text.
         * @return string Sanitized text.
         */
        public static function sanitize_text(string $input): string {
            return sanitize_text_field($input);
        }

    }

    // Initialize the admin class
    Dienstenoverzicht_Admin::init();

    // Hooking up the custom columns and filters
    add_filter('manage_diensten_posts_columns', ['Dienstenoverzicht\Admin\Dienstenoverzicht_Admin', 'add_custom_columns']);

}