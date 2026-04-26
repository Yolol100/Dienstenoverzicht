<?php
/**
 * CSV import and export tools.
 *
 * @package Dienstenoverzicht
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles CSV import/export admin flows.
 */
final class Dienstenoverzicht_ImportExport {
	private const TEXT_DOMAIN = 'dienstenoverzicht';
	private const MENU_SLUG = 'diensten-export-import';
	private const MAX_IMPORT_BYTES = 2097152;
	private const CSV_HEADERS = [ 'ID', 'Title', 'Basis Pakket Prijzen', 'Uitgebreid Pakket Prijzen', 'Content', 'Summary' ];

	/**
	 * Registers hooks.
	 */
	public static function init(): void {
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_assets' ] );
		add_action( 'admin_menu', [ self::class, 'add_menu_items' ] );
		add_action( 'admin_post_export_single_dienst', [ self::class, 'export_single_dienst' ] );
		add_action( 'admin_post_import_diensten', [ self::class, 'handle_file_upload' ] );
		add_action( 'admin_post_export_selected_diensten', [ self::class, 'export_selected_diensten' ] );
	}

	/**
	 * Enqueues assets for the import/export page.
	 */
	public static function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'diensten_page_' . self::MENU_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'dienstenoverzicht-admin-style', DIENSTENOVERZICHT_PLUGIN_URL . 'css/dienstenoverzicht-admin-style.css', [], DIENSTENOVERZICHT_VERSION );
		wp_enqueue_script( 'dienstenoverzicht-admin-export-import', DIENSTENOVERZICHT_PLUGIN_URL . 'js/dienstenoverzicht-admin-export-import.js', [], DIENSTENOVERZICHT_VERSION, true );
	}

	/**
	 * Adds the import/export submenu.
	 */
	public static function add_menu_items(): void {
		add_submenu_page(
			'edit.php?post_type=' . Dienstenoverzicht_CPT::POST_TYPE,
			__( 'Diensten exporteren/importeren', self::TEXT_DOMAIN ),
			__( 'Export/Import', self::TEXT_DOMAIN ),
			'manage_options',
			self::MENU_SLUG,
			[ self::class, 'render_export_import_page' ]
		);
	}

	/**
	 * Renders the import/export page.
	 */
	public static function render_export_import_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen toestemming om deze pagina te bekijken.', self::TEXT_DOMAIN ) );
		}

		$imported = isset( $_GET['imported'] ) ? absint( wp_unslash( $_GET['imported'] ) ) : null;
		?>
		<div class="wrap dienstenoverzicht-import-export-wrap">
			<h1><?php esc_html_e( 'Diensten exporteren/importeren', self::TEXT_DOMAIN ); ?></h1>

			<?php if ( null !== $imported ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf( _n( '%d dienst geïmporteerd.', '%d diensten geïmporteerd.', $imported, self::TEXT_DOMAIN ), $imported ) ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Diensten exporteren', self::TEXT_DOMAIN ); ?></h2>
			<?php self::render_diensten_table(); ?>

			<hr>

			<h2><?php esc_html_e( 'Diensten importeren', self::TEXT_DOMAIN ); ?></h2>
			<p><?php esc_html_e( 'Upload een CSV-bestand dat eerder met deze plugin is geëxporteerd. Maximale bestandsgrootte: 2 MB.', self::TEXT_DOMAIN ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'import_diensten_nonce', 'import_diensten_nonce_field' ); ?>
				<input type="hidden" name="action" value="import_diensten">
				<p>
					<label for="import_file"><strong><?php esc_html_e( 'CSV-bestand', self::TEXT_DOMAIN ); ?></strong></label><br>
					<input type="file" id="import_file" name="import_file" accept=".csv,text/csv" required>
				</p>
				<?php submit_button( __( 'Importeren', self::TEXT_DOMAIN ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders export table.
	 */
	private static function render_diensten_table(): void {
		$posts = get_posts(
			[
				'post_type'              => Dienstenoverzicht_CPT::POST_TYPE,
				'posts_per_page'         => 500,
				'post_status'            => 'publish',
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			]
		);

		if ( empty( $posts ) ) {
			echo '<p>' . esc_html__( 'Er zijn geen diensten om te exporteren.', self::TEXT_DOMAIN ) . '</p>';
			return;
		}
		?>
		<form id="diensten-export-form-dienstenoverzicht" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'export_diensten_nonce', 'export_diensten_nonce_field' ); ?>
			<input type="hidden" name="action" value="export_selected_diensten">
			<p>
				<label class="screen-reader-text" for="diensten-search"><?php esc_html_e( 'Zoek diensten', self::TEXT_DOMAIN ); ?></label>
				<input type="search" id="diensten-search" placeholder="<?php esc_attr_e( 'Zoek diensten...', self::TEXT_DOMAIN ); ?>">
			</p>
			<table class="widefat fixed striped dienstenoverzicht-table-dienstenoverzicht">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column"><input type="checkbox" id="select-all-dienstenoverzicht" aria-label="<?php esc_attr_e( 'Selecteer alle diensten', self::TEXT_DOMAIN ); ?>"></td>
						<th scope="col"><?php esc_html_e( 'ID', self::TEXT_DOMAIN ); ?></th>
						<th scope="col"><?php esc_html_e( 'Titel', self::TEXT_DOMAIN ); ?></th>
						<th scope="col"><?php esc_html_e( 'Basispakket', self::TEXT_DOMAIN ); ?></th>
						<th scope="col"><?php esc_html_e( 'Uitgebreid pakket', self::TEXT_DOMAIN ); ?></th>
						<th scope="col"><?php esc_html_e( 'Acties', self::TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts as $post ) : ?>
						<?php $export_url = wp_nonce_url( admin_url( 'admin-post.php?action=export_single_dienst&post_id=' . absint( $post->ID ) ), 'export_single_dienst_nonce' ); ?>
						<tr>
							<th scope="row" class="check-column"><input type="checkbox" name="post_ids[]" value="<?php echo esc_attr( $post->ID ); ?>" class="dienst-checkbox"></th>
							<td><?php echo esc_html( $post->ID ); ?></td>
							<td class="dienst-title"><?php echo esc_html( get_the_title( $post ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $post->ID, '_basispakket_prijzen', true ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $post->ID, '_uitgebreid_pakket_prijzen', true ) ); ?></td>
							<td><a href="<?php echo esc_url( $export_url ); ?>" class="button"><?php esc_html_e( 'Exporteer', self::TEXT_DOMAIN ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( __( 'Geselecteerde diensten exporteren', self::TEXT_DOMAIN ), 'primary', 'submit', false, [ 'id' => 'export-selected-button-dienstenoverzicht', 'disabled' => 'disabled' ] ); ?>
		</form>
		<?php
	}

	/**
	 * Exports selected services.
	 */
	public static function export_selected_diensten(): void {
		self::assert_manage_permission();
		check_admin_referer( 'export_diensten_nonce', 'export_diensten_nonce_field' );

		$post_ids = isset( $_POST['post_ids'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['post_ids'] ) ) ) : [];

		if ( empty( $post_ids ) ) {
			wp_die( esc_html__( 'Geen diensten geselecteerd.', self::TEXT_DOMAIN ) );
		}

		$post_ids = array_slice( array_unique( $post_ids ), 0, 500 );
		$posts    = get_posts(
			[
				'post_type'      => Dienstenoverzicht_CPT::POST_TYPE,
				'post__in'       => $post_ids,
				'posts_per_page' => count( $post_ids ),
				'post_status'    => 'publish',
				'orderby'        => 'post__in',
			]
		);

		self::send_csv_headers( 'diensten-export-selected-' . gmdate( 'Ymd' ) . '.csv' );
		self::output_csv( $posts );
		exit;
	}

	/**
	 * Exports one service.
	 */
	public static function export_single_dienst(): void {
		self::assert_manage_permission();
		check_admin_referer( 'export_single_dienst_nonce' );

		$post_id = isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || Dienstenoverzicht_CPT::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_die( esc_html__( 'Ongeldige dienst ID.', self::TEXT_DOMAIN ) );
		}

		self::send_csv_headers( 'dienst-export-' . $post_id . '-' . gmdate( 'Ymd' ) . '.csv' );
		self::output_csv( [ $post ] );
		exit;
	}

	/**
	 * Handles CSV import upload.
	 */
	public static function handle_file_upload(): void {
		self::assert_manage_permission();
		check_admin_referer( 'import_diensten_nonce', 'import_diensten_nonce_field' );

		if ( empty( $_FILES['import_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
			wp_die( esc_html__( 'Geen bestand geüpload.', self::TEXT_DOMAIN ) );
		}

		$file_tmp_name = sanitize_text_field( wp_unslash( $_FILES['import_file']['tmp_name'] ) );
		$file_name     = isset( $_FILES['import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['import_file']['name'] ) ) : '';
		$file_size     = isset( $_FILES['import_file']['size'] ) ? absint( $_FILES['import_file']['size'] ) : 0;
		$file_error    = isset( $_FILES['import_file']['error'] ) ? absint( $_FILES['import_file']['error'] ) : UPLOAD_ERR_NO_FILE;
		$file_type     = wp_check_filetype( $file_name, [ 'csv' => 'text/csv' ] );

		if ( UPLOAD_ERR_OK !== $file_error ) {
			wp_die( esc_html__( 'Het CSV-bestand kon niet worden geüpload.', self::TEXT_DOMAIN ) );
		}

		if ( 'csv' !== $file_type['ext'] ) {
			wp_die( esc_html__( 'Alleen CSV-bestanden zijn toegestaan.', self::TEXT_DOMAIN ) );
		}

		if ( 0 === $file_size || $file_size > self::MAX_IMPORT_BYTES ) {
			wp_die( esc_html__( 'Het CSV-bestand is leeg of groter dan 2 MB.', self::TEXT_DOMAIN ) );
		}

		$imported = self::import_csv_file( $file_tmp_name );
		$redirect = add_query_arg(
			[
				'post_type' => Dienstenoverzicht_CPT::POST_TYPE,
				'page'      => self::MENU_SLUG,
				'imported'  => $imported,
			],
			admin_url( 'edit.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Imports CSV rows from a temporary upload path.
	 */
	private static function import_csv_file( string $file_path ): int {
		$handle = fopen( $file_path, 'r' );

		if ( false === $handle ) {
			wp_die( esc_html__( 'Het CSV-bestand kon niet worden gelezen.', self::TEXT_DOMAIN ) );
		}

		$headers = fgetcsv( $handle );

		if ( self::CSV_HEADERS !== $headers ) {
			fclose( $handle );
			wp_die( esc_html__( 'Ongeldig CSV-bestand.', self::TEXT_DOMAIN ) );
		}

		$imported = 0;

		while ( false !== ( $row = fgetcsv( $handle ) ) ) {
			if ( 6 !== count( $row ) ) {
				continue;
			}

			if ( self::import_row( $row ) ) {
				++$imported;
			}
		}

		fclose( $handle );

		return $imported;
	}

	/**
	 * Imports a single row.
	 *
	 * @param array<int,string> $row CSV row.
	 */
	private static function import_row( array $row ): bool {
		$post_id       = absint( $row[0] );
		$existing_post = $post_id ? get_post( $post_id ) : null;
		$title         = sanitize_text_field( self::remove_csv_formula_prefix( $row[1] ) );

		if ( '' === $title ) {
			return false;
		}

		$post_data = [
			'post_title'   => $title,
			'post_content' => wp_kses_post( self::remove_csv_formula_prefix( $row[4] ) ),
			'post_status'  => 'publish',
			'post_type'    => Dienstenoverzicht_CPT::POST_TYPE,
		];

		if ( $existing_post && Dienstenoverzicht_CPT::POST_TYPE === $existing_post->post_type ) {
			$post_data['ID'] = $post_id;
			$post_id         = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return false;
		}

		update_post_meta( $post_id, '_basispakket_prijzen', sanitize_textarea_field( self::remove_csv_formula_prefix( $row[2] ) ) );
		update_post_meta( $post_id, '_uitgebreid_pakket_prijzen', sanitize_textarea_field( self::remove_csv_formula_prefix( $row[3] ) ) );
		update_post_meta( $post_id, '_samenvatting', sanitize_textarea_field( self::remove_csv_formula_prefix( $row[5] ) ) );

		return true;
	}

	/**
	 * Requires administrator-level access for import/export tools.
	 */
	private static function assert_manage_permission(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Je hebt geen toestemming om deze actie uit te voeren.', self::TEXT_DOMAIN ) );
		}
	}

	/**
	 * Sends CSV response headers.
	 */
	private static function send_csv_headers( string $filename ): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
	}

	/**
	 * Writes CSV output.
	 *
	 * @param WP_Post[] $posts Posts to export.
	 */
	private static function output_csv( array $posts ): void {
		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			wp_die( esc_html__( 'CSV-output kon niet worden geopend.', self::TEXT_DOMAIN ) );
		}

		fputcsv( $output, self::CSV_HEADERS );

		foreach ( $posts as $post ) {
			fputcsv(
				$output,
				[
					$post->ID,
					self::escape_csv_formula( $post->post_title ),
					self::escape_csv_formula( get_post_meta( $post->ID, '_basispakket_prijzen', true ) ),
					self::escape_csv_formula( get_post_meta( $post->ID, '_uitgebreid_pakket_prijzen', true ) ),
					self::escape_csv_formula( $post->post_content ),
					self::escape_csv_formula( get_post_meta( $post->ID, '_samenvatting', true ) ),
				]
			);
		}

		fclose( $output );
	}

	/**
	 * Prevents spreadsheet formula execution when exported CSV files are opened.
	 */
	private static function escape_csv_formula( $value ): string {
		$value = (string) $value;

		if ( '' !== $value && preg_match( '/^[=+\-@]/', $value ) ) {
			return "'" . $value;
		}

		return $value;
	}

	/**
	 * Removes the protective export prefix during re-import.
	 */
	private static function remove_csv_formula_prefix( $value ): string {
		$value = (string) $value;

		if ( 0 === strpos( $value, "'" ) && isset( $value[1] ) && preg_match( '/^[=+\-@]/', $value[1] ) ) {
			return substr( $value, 1 );
		}

		return $value;
	}
}
