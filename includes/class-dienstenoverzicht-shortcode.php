<?php
/**
 * Shortcode and AJAX rendering.
 *
 * @package Dienstenoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the public services overview.
 */
final class Dienstenoverzicht_Shortcode {
	private const TEXT_DOMAIN = 'dienstenoverzicht';
	private const SHORTCODE = 'dienstenoverzicht';

	/**
	 * Registers shortcode and AJAX hooks.
	 */
	public static function init(): void {
		add_shortcode( self::SHORTCODE, [ self::class, 'render_shortcode' ] );
		add_action( 'wp_ajax_dienstenoverzicht_filter', [ self::class, 'ajax_filter' ] );
		add_action( 'wp_ajax_nopriv_dienstenoverzicht_filter', [ self::class, 'ajax_filter' ] );
	}

	/**
	 * Renders the shortcode.
	 *
	 * @param array<string,mixed> $atts Shortcode attributes.
	 */
	public static function render_shortcode( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'category' => 0,
			],
			$atts,
			self::SHORTCODE
		);

		$options        = self::get_options();
		$initial_cat_id = absint( $atts['category'] );
		$category_terms = get_terms(
			[
				'taxonomy'   => Dienstenoverzicht_Taxonomy::TAXONOMY,
				'hide_empty' => true,
			]
		);

		ob_start();
		?>
		<section class="dienstenoverzicht-container" style="--dienstenoverzicht-button-hover-color: <?php echo esc_attr( $options['button_hover_color'] ); ?>;">
			<div class="dienstenoverzicht-header">
				<?php if ( '' !== $options['title'] ) : ?>
					<h2 class="dienstenoverzicht-title" style="font-size: <?php echo esc_attr( $options['title_font_size'] ); ?>; color: <?php echo esc_attr( $options['title_color'] ); ?>;">
						<?php echo esc_html( $options['title'] ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( ! is_wp_error( $category_terms ) && ! empty( $category_terms ) ) : ?>
					<form class="faceted-filter dienstenoverzicht-filter" aria-label="<?php esc_attr_e( 'Diensten filteren', self::TEXT_DOMAIN ); ?>">
						<label for="dienstenoverzicht-categorie"><?php esc_html_e( 'Categorie', self::TEXT_DOMAIN ); ?></label>
						<select id="dienstenoverzicht-categorie" name="dienstenoverzicht_categorie">
							<option value="0"><?php esc_html_e( 'Alle categorieën', self::TEXT_DOMAIN ); ?></option>
							<?php foreach ( $category_terms as $term ) : ?>
								<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $initial_cat_id, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</form>
				<?php endif; ?>
			</div>

			<div id="dienstenoverzicht-results" class="dienstenoverzicht" aria-live="polite">
				<?php echo self::render_diensten( $options, $initial_cat_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * AJAX callback for category filtering.
	 */
	public static function ajax_filter(): void {
		check_ajax_referer( 'dienstenoverzicht_filter', 'nonce' );

		$options     = self::get_options();
		$category_id = isset( $_POST['dienstenoverzicht_categorie'] ) ? absint( wp_unslash( $_POST['dienstenoverzicht_categorie'] ) ) : 0;

		wp_send_json_success(
			[
				'html' => self::render_diensten( $options, $category_id ),
			]
		);
	}

	/**
	 * Renders service cards.
	 *
	 * @param array<string,mixed> $options Plugin display options.
	 */
	private static function render_diensten( array $options, int $category_id = 0 ): string {
		$args = [
			'post_type'      => Dienstenoverzicht_CPT::POST_TYPE,
			'posts_per_page' => $options['items_per_page'],
			'post_status'    => 'publish',
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		];

		if ( $category_id > 0 ) {
			$args['tax_query'] = [
				[
					'taxonomy' => Dienstenoverzicht_Taxonomy::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => [ $category_id ],
				],
			];
		}

		$query = new WP_Query( $args );
		ob_start();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				self::render_dienst_card( get_the_ID(), $options );
			}
			wp_reset_postdata();
		} else {
			echo '<p class="no-results">' . esc_html__( 'Geen diensten gevonden.', self::TEXT_DOMAIN ) . '</p>';
		}

		return (string) ob_get_clean();
	}

	/**
	 * Renders a single service card.
	 *
	 * @param array<string,mixed> $options Plugin display options.
	 */
	private static function render_dienst_card( int $post_id, array $options ): void {
		$basis        = get_post_meta( $post_id, '_basispakket_prijzen', true );
		$uitgebreid  = get_post_meta( $post_id, '_uitgebreid_pakket_prijzen', true );
		$samenvatting = get_post_meta( $post_id, '_samenvatting', true );
		?>
		<article class="dienst">
			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<div class="dienst-image"><?php echo get_the_post_thumbnail( $post_id, 'large' ); ?></div>
			<?php endif; ?>

			<div class="dienst-content">
				<h3 class="dienst-title" style="font-size: <?php echo esc_attr( $options['title_font_size'] ); ?>; color: <?php echo esc_attr( $options['title_color'] ); ?>;">
					<?php echo esc_html( get_the_title( $post_id ) ); ?>
				</h3>

				<?php if ( '' !== $samenvatting ) : ?>
					<p class="dienst-summary" style="font-size: <?php echo esc_attr( $options['summary_font_size'] ); ?>;"><?php echo esc_html( $samenvatting ); ?></p>
				<?php endif; ?>

				<?php if ( $options['show_prices'] && ( '' !== $basis || '' !== $uitgebreid ) ) : ?>
					<div class="dienst-pricing">
						<?php if ( '' !== $basis ) : ?>
							<p class="prijs-item"><strong><?php esc_html_e( 'Basis:', self::TEXT_DOMAIN ); ?></strong> <span style="font-size: <?php echo esc_attr( $options['prijs_font_size'] ); ?>; color: <?php echo esc_attr( $options['prijs_color'] ); ?>;"><?php echo esc_html( $basis ); ?></span></p>
						<?php endif; ?>
						<?php if ( '' !== $uitgebreid ) : ?>
							<p class="prijs-item"><strong><?php esc_html_e( 'Uitgebreid:', self::TEXT_DOMAIN ); ?></strong> <span style="font-size: <?php echo esc_attr( $options['prijs_font_size'] ); ?>; color: <?php echo esc_attr( $options['prijs_color'] ); ?>;"><?php echo esc_html( $uitgebreid ); ?></span></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( '' !== $options['gratis_advies_url'] ) : ?>
					<a href="<?php echo esc_url( $options['gratis_advies_url'] ); ?>" class="button gratis-advies-button" style="font-size: <?php echo esc_attr( $options['button_font_size'] ); ?>; background-color: <?php echo esc_attr( $options['button_color'] ); ?>;">
						<span class="dienstenoverzicht-button-icon" aria-hidden="true">🤝</span>
						<?php esc_html_e( 'Gratis advies', self::TEXT_DOMAIN ); ?>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Returns sanitized display options.
	 *
	 * @return array<string,mixed>
	 */
	private static function get_options(): array {
		return [
			'title'              => sanitize_text_field( get_option( 'dienstenoverzicht_title', __( 'Onze Diensten', self::TEXT_DOMAIN ) ) ),
			'items_per_page'     => max( 1, min( 48, absint( get_option( 'dienstenoverzicht_items_per_page', 6 ) ) ) ),
			'show_prices'        => (bool) get_option( 'dienstenoverzicht_show_prices', 1 ),
			'button_font_size'   => self::sanitize_css_size( get_option( 'dienstenoverzicht_button_font_size', '14px' ), '14px' ),
			'title_font_size'    => self::sanitize_css_size( get_option( 'dienstenoverzicht_title_font_size', '24px' ), '24px' ),
			'summary_font_size'  => self::sanitize_css_size( get_option( 'dienstenoverzicht_summary_font_size', '14px' ), '14px' ),
			'title_color'        => sanitize_hex_color( get_option( 'dienstenoverzicht_title_color', '#000000' ) ) ?: '#000000',
			'prijs_color'        => sanitize_hex_color( get_option( 'dienstenoverzicht_prijs_color', '#000000' ) ) ?: '#000000',
			'button_color'       => sanitize_hex_color( get_option( 'dienstenoverzicht_button_color', '#0073aa' ) ) ?: '#0073aa',
			'button_hover_color' => sanitize_hex_color( get_option( 'dienstenoverzicht_button_hover_color', '#005a87' ) ) ?: '#005a87',
			'gratis_advies_url'  => esc_url_raw( get_option( 'dienstenoverzicht_gratis_advies_url', '' ) ),
			'prijs_font_size'    => self::sanitize_css_size( get_option( 'dienstenoverzicht_prijs_font_size', '14px' ), '14px' ),
		];
	}

	/**
	 * Sanitizes a small CSS length value used in inline styles.
	 */
	private static function sanitize_css_size( $value, string $fallback ): string {
		$value = trim( (string) $value );

		if ( preg_match( '/^\d+(\.\d+)?(px|rem|em|%)$/', $value ) ) {
			return $value;
		}

		return $fallback;
	}
}
