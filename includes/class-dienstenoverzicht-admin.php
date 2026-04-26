<?php
/**
 * Admin settings and list-table enhancements.
 *
 * @package Dienstenoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin pages and settings.
 */
final class Dienstenoverzicht_Admin {
	private const OPTION_GROUP = 'dienstenoverzicht_settings_group';
	private const SETTINGS_PAGE = 'dienstenoverzicht-settings';
	private const TEXT_DOMAIN = 'dienstenoverzicht';

	/**
	 * Registers hooks.
	 */
	public static function init(): void {
		add_action( 'admin_init', [ self::class, 'register_settings' ] );
		add_action( 'admin_menu', [ self::class, 'add_settings_page' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
		add_action( 'admin_notices', [ self::class, 'settings_saved_notice' ] );
		add_filter( 'manage_diensten_posts_columns', [ self::class, 'add_custom_columns' ] );
		add_action( 'manage_diensten_posts_custom_column', [ self::class, 'custom_columns_content' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ self::class, 'filter_by_taxonomy' ] );
		add_filter( 'parse_query', [ self::class, 'apply_taxonomy_filter_query' ] );
	}

	/**
	 * Adds columns to the services admin list table.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public static function add_custom_columns( array $columns ): array {
		$columns['prijs']       = __( 'Prijsinformatie', self::TEXT_DOMAIN );
		$columns['samenvatting'] = __( 'Samenvatting', self::TEXT_DOMAIN );

		return $columns;
	}

	/**
	 * Renders custom column content.
	 */
	public static function custom_columns_content( string $column, int $post_id ): void {
		if ( 'prijs' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_basispakket_prijzen', true ) );
			echo '<br>';
			echo esc_html( get_post_meta( $post_id, '_uitgebreid_pakket_prijzen', true ) );
			return;
		}

		if ( 'samenvatting' === $column ) {
			echo esc_html( wp_trim_words( get_post_meta( $post_id, '_samenvatting', true ), 18 ) );
		}
	}

	/**
	 * Adds taxonomy dropdown to the services admin list table.
	 */
	public static function filter_by_taxonomy(): void {
		global $typenow;

		if ( Dienstenoverzicht_CPT::POST_TYPE !== $typenow ) {
			return;
		}

		$taxonomy = Dienstenoverzicht_Taxonomy::TAXONOMY;
		$selected = isset( $_GET[ $taxonomy ] ) ? absint( wp_unslash( $_GET[ $taxonomy ] ) ) : 0;

		wp_dropdown_categories(
			[
				'show_option_all' => __( 'Alle categorieën', self::TEXT_DOMAIN ),
				'taxonomy'        => $taxonomy,
				'name'            => $taxonomy,
				'orderby'         => 'name',
				'selected'        => $selected,
				'show_count'      => true,
				'hide_empty'      => false,
				'value_field'     => 'term_id',
			]
		);
	}

	/**
	 * Converts the taxonomy term ID filter to a query var WordPress understands.
	 *
	 * @param WP_Query $query Current query object.
	 */
	public static function apply_taxonomy_filter_query( WP_Query $query ): void {
		global $pagenow;

		$taxonomy = Dienstenoverzicht_Taxonomy::TAXONOMY;
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

		if ( ! is_admin() || 'edit.php' !== $pagenow || Dienstenoverzicht_CPT::POST_TYPE !== $post_type || empty( $_GET[ $taxonomy ] ) ) {
			return;
		}

		$term_id = absint( wp_unslash( $_GET[ $taxonomy ] ) );
		$term    = get_term( $term_id, $taxonomy );

		if ( $term && ! is_wp_error( $term ) ) {
			$query->query_vars[ $taxonomy ] = $term->slug;
		}
	}

	/**
	 * Adds the settings submenu.
	 */
	public static function add_settings_page(): void {
		add_submenu_page(
			'edit.php?post_type=' . Dienstenoverzicht_CPT::POST_TYPE,
			__( 'Dienstenoverzicht instellingen', self::TEXT_DOMAIN ),
			__( 'Instellingen', self::TEXT_DOMAIN ),
			'manage_options',
			self::SETTINGS_PAGE,
			[ self::class, 'render_settings_page' ]
		);
	}

	/**
	 * Registers plugin options.
	 */
	public static function register_settings(): void {
		foreach ( self::get_settings() as $option_key => $setting ) {
			register_setting(
				self::OPTION_GROUP,
				'dienstenoverzicht_' . $option_key,
				[
					'type'              => $setting['type'],
					'sanitize_callback' => [ self::class, $setting['sanitize_callback'] ],
					'default'           => $setting['default'],
				]
			);
		}
	}

	/**
	 * Renders the settings page.
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen toestemming om deze pagina te bekijken.', self::TEXT_DOMAIN ) );
		}
		?>
		<div class="wrap dienstenoverzicht-settings-wrap">
			<h1><?php esc_html_e( 'Dienstenoverzicht instellingen', self::TEXT_DOMAIN ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GROUP ); ?>
				<table class="form-table" role="presentation">
					<?php foreach ( self::get_settings() as $option_key => $setting ) : ?>
						<?php self::render_field_row( $option_key, $setting ); ?>
					<?php endforeach; ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders one settings field row.
	 *
	 * @param array<string,mixed> $setting Setting configuration.
	 */
	private static function render_field_row( string $option_key, array $setting ): void {
		$id    = 'dienstenoverzicht_' . $option_key;
		$value = get_option( $id, $setting['default'] );
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $setting['label'] ); ?></label></th>
			<td>
				<?php if ( 'boolean' === $setting['type'] ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $id ); ?>" value="0">
					<label><input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="1" <?php checked( 1, (int) $value ); ?>> <?php esc_html_e( 'Ja', self::TEXT_DOMAIN ); ?></label>
				<?php elseif ( 'color' === $setting['field_type'] ) : ?>
					<input type="text" class="color-field" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" pattern="^#[0-9a-fA-F]{6}$">
				<?php elseif ( 'number' === $setting['field_type'] ) : ?>
					<input type="number" min="1" max="48" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<?php else : ?>
					<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
				<?php endif; ?>
				<?php if ( ! empty( $setting['description'] ) ) : ?>
					<p class="description"><?php echo esc_html( $setting['description'] ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Enqueues settings assets only on the settings page.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( 'diensten_page_' . self::SETTINGS_PAGE !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'dienstenoverzicht-admin-style', DIENSTENOVERZICHT_PLUGIN_URL . 'css/dienstenoverzicht-admin-style.css', [ 'wp-color-picker' ], DIENSTENOVERZICHT_VERSION );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'dienstenoverzicht-admin-settings', DIENSTENOVERZICHT_PLUGIN_URL . 'js/dienstenoverzicht-admin-settings.js', [ 'jquery', 'wp-color-picker' ], DIENSTENOVERZICHT_VERSION, true );
	}

	/**
	 * Shows a settings saved notice on the plugin settings page.
	 */
	public static function settings_saved_notice(): void {
		$settings_updated = isset( $_GET['settings-updated'] ) ? sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) : '';
		$page             = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( self::SETTINGS_PAGE !== $page || 'true' !== $settings_updated ) {
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Instellingen zijn succesvol opgeslagen.', self::TEXT_DOMAIN ) . '</p></div>';
	}

	/**
	 * Sanitizes checkbox settings.
	 */
	public static function sanitize_checkbox( $input ): int {
		return $input ? 1 : 0;
	}

	/**
	 * Sanitizes items-per-page setting.
	 */
	public static function sanitize_positive_int( $input ): int {
		return max( 1, min( 48, absint( $input ) ) );
	}

	/**
	 * Sanitizes URL settings.
	 */
	public static function sanitize_url_value( $input ): string {
		return esc_url_raw( (string) $input );
	}

	/**
	 * Sanitizes hex color settings.
	 */
	public static function sanitize_color( $input ): string {
		$color = sanitize_hex_color( (string) $input );

		return $color ?: '#000000';
	}

	/**
	 * Sanitizes plain text settings.
	 */
	public static function sanitize_text_value( $input ): string {
		return sanitize_text_field( (string) $input );
	}

	/**
	 * Sanitizes CSS length settings.
	 */
	public static function sanitize_css_size( $input ): string {
		$value = trim( sanitize_text_field( (string) $input ) );

		if ( preg_match( '/^\d+(\.\d+)?(px|rem|em|%)$/', $value ) ) {
			return $value;
		}

		return '14px';
	}

	/**
	 * Returns setting definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function get_settings(): array {
		return [
			'title'              => [ 'label' => __( 'Titel', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'text', 'default' => __( 'Onze Diensten', self::TEXT_DOMAIN ), 'sanitize_callback' => 'sanitize_text_value', 'description' => '' ],
			'items_per_page'     => [ 'label' => __( 'Aantal diensten', self::TEXT_DOMAIN ), 'type' => 'integer', 'field_type' => 'number', 'default' => 6, 'sanitize_callback' => 'sanitize_positive_int', 'description' => __( 'Aantal diensten dat in de shortcode wordt getoond.', self::TEXT_DOMAIN ) ],
			'show_prices'        => [ 'label' => __( 'Prijzen tonen', self::TEXT_DOMAIN ), 'type' => 'boolean', 'field_type' => 'checkbox', 'default' => 1, 'sanitize_callback' => 'sanitize_checkbox', 'description' => '' ],
			'gratis_advies_url'  => [ 'label' => __( 'URL voor gratis advies', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'url', 'default' => '', 'sanitize_callback' => 'sanitize_url_value', 'description' => '' ],
			'summary_font_size'  => [ 'label' => __( 'Lettergrootte samenvatting', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'text', 'default' => '14px', 'sanitize_callback' => 'sanitize_css_size', 'description' => __( 'Gebruik bijvoorbeeld 14px, 1rem of 100%.', self::TEXT_DOMAIN ) ],
			'button_font_size'   => [ 'label' => __( 'Lettergrootte knop', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'text', 'default' => '14px', 'sanitize_callback' => 'sanitize_css_size', 'description' => __( 'Gebruik bijvoorbeeld 14px, 1rem of 100%.', self::TEXT_DOMAIN ) ],
			'title_font_size'    => [ 'label' => __( 'Lettergrootte titel', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'text', 'default' => '24px', 'sanitize_callback' => 'sanitize_css_size', 'description' => __( 'Gebruik bijvoorbeeld 24px, 1.5rem of 120%.', self::TEXT_DOMAIN ) ],
			'prijs_font_size'    => [ 'label' => __( 'Lettergrootte prijs', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'text', 'default' => '14px', 'sanitize_callback' => 'sanitize_css_size', 'description' => __( 'Gebruik bijvoorbeeld 14px, 1rem of 100%.', self::TEXT_DOMAIN ) ],
			'title_color'        => [ 'label' => __( 'Kleur titel', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'color', 'default' => '#000000', 'sanitize_callback' => 'sanitize_color', 'description' => '' ],
			'prijs_color'        => [ 'label' => __( 'Kleur prijs', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'color', 'default' => '#000000', 'sanitize_callback' => 'sanitize_color', 'description' => '' ],
			'button_color'       => [ 'label' => __( 'Kleur knop', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'color', 'default' => '#0073aa', 'sanitize_callback' => 'sanitize_color', 'description' => '' ],
			'button_hover_color' => [ 'label' => __( 'Kleur knop hover', self::TEXT_DOMAIN ), 'type' => 'string', 'field_type' => 'color', 'default' => '#005a87', 'sanitize_callback' => 'sanitize_color', 'description' => '' ],
		];
	}
}
