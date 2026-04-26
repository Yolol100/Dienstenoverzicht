<?php
/**
 * Service metaboxes.
 *
 * @package Dienstenoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and saves service meta fields.
 */
final class Dienstenoverzicht_Metaboxes {
	private const NONCE_ACTION = 'dienstenoverzicht_save_metabox_data';
	private const NONCE_FIELD = 'dienstenoverzicht_nonce';
	private const TEXT_DOMAIN = 'dienstenoverzicht';

	/**
	 * Registers hooks.
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', [ self::class, 'add_metaboxes' ] );
		add_action( 'save_post_' . Dienstenoverzicht_CPT::POST_TYPE, [ self::class, 'save_metabox_data' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_assets' ] );
	}

	/**
	 * Adds the details metabox.
	 */
	public static function add_metaboxes(): void {
		add_meta_box(
			'dienstenoverzicht_details',
			__( 'Dienstenoverzicht details', self::TEXT_DOMAIN ),
			[ self::class, 'render_metabox' ],
			Dienstenoverzicht_CPT::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Renders the details metabox.
	 */
	public static function render_metabox( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$fields = [
			'basispakket_prijzen'       => [ __( 'Basispakket', self::TEXT_DOMAIN ), get_post_meta( $post->ID, '_basispakket_prijzen', true ) ],
			'uitgebreid_pakket_prijzen' => [ __( 'Uitgebreid pakket', self::TEXT_DOMAIN ), get_post_meta( $post->ID, '_uitgebreid_pakket_prijzen', true ) ],
			'samenvatting'              => [ __( 'Samenvatting', self::TEXT_DOMAIN ), get_post_meta( $post->ID, '_samenvatting', true ) ],
		];
		?>
		<div class="dienstenoverzicht-metabox">
			<?php foreach ( $fields as $field_id => $field ) : ?>
				<p class="meta-box-field">
					<label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $field[0] ); ?></strong></label>
					<textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_id ); ?>" rows="4" class="widefat"><?php echo esc_textarea( $field[1] ); ?></textarea>
				</p>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Saves service meta fields.
	 */
	public static function save_metabox_data( int $post_id ): void {
		if ( self::should_abort_save( $post_id ) ) {
			return;
		}

		$fields = [
			'basispakket_prijzen'       => '_basispakket_prijzen',
			'uitgebreid_pakket_prijzen' => '_uitgebreid_pakket_prijzen',
			'samenvatting'              => '_samenvatting',
		];

		foreach ( $fields as $request_key => $meta_key ) {
			$value = isset( $_POST[ $request_key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $request_key ] ) ) : '';
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	/**
	 * Determines whether a save request should be ignored.
	 */
	private static function should_abort_save( int $post_id ): bool {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return true;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return true;
		}

		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return true;
		}

		return ! current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Enqueues admin CSS only on service edit screens.
	 */
	public static function enqueue_admin_assets(): void {
		$screen = get_current_screen();

		if ( $screen && 'post' === $screen->base && Dienstenoverzicht_CPT::POST_TYPE === $screen->post_type ) {
			wp_enqueue_style( 'dienstenoverzicht-admin-style', DIENSTENOVERZICHT_PLUGIN_URL . 'css/dienstenoverzicht-admin-style.css', [], DIENSTENOVERZICHT_VERSION );
		}
	}
}
