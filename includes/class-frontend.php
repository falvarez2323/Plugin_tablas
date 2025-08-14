<?php
/**
 * Frontend: Shortcodes y render en el sitio
 *
 * @package MiPluginTablas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPT_Frontend {

	const SHORTCODE = 'mpt_registros';
	const UPLOAD_SUBDIR = 'mi-plugin-tablas';

	public function init() : void {
		add_action( 'init', [ $this, 'register_shortcodes' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
	}

	public function register_shortcodes() : void {
		add_shortcode( self::SHORTCODE, [ $this, 'shortcode_registros' ] );
	}

	public function maybe_enqueue_assets() : void {
		if ( is_admin() ) {
			return;
		}
		global $post;
		if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, self::SHORTCODE ) ) {
			$this->enqueue_assets();
		}
	}

	private function enqueue_assets() : void {
		$ver = defined( 'MI_PLUGIN_TABLAS_VERSION' ) ? MI_PLUGIN_TABLAS_VERSION : '1.0.0';

		$inline_css = '
			.mpt-wrap{overflow:auto;margin:1rem 0;}
			.mpt-table{border-collapse:collapse;width:100%;font-size:14px}
			.mpt-table th,.mpt-table td{border:1px solid #e5e7eb;padding:.5rem;text-align:left;vertical-align:top}
			.mpt-table th{background:#f9fafb;font-weight:600}
			.mpt-empty{padding:1rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:.5rem}
			.mpt-meta{font-size:12px;color:#6b7280}
		';

		wp_register_style( 'mpt-frontend', false, [], $ver );
		wp_enqueue_style( 'mpt-frontend' );
		wp_add_inline_style( 'mpt-frontend', $inline_css );
	}

	public function shortcode_registros( array $atts = [] ) : string {
		// Evita warnings que rompan JSON.
		$atts = shortcode_atts( [], $atts, self::SHORTCODE );

		// Encolar recursos por si el shortcode se procesa fuera del loop.
		$this->enqueue_assets();

		$files = $this->get_files_from_index();

		ob_start();
		?>
		<div class="mpt-wrap">
			<?php if ( empty( $files ) ) : ?>
				<div class="mpt-empty">No hay archivos para mostrar todavía.</div>
			<?php else : ?>
				<table class="mpt-table">
					<thead>
						<tr>
							<th>Nombre</th>
							<th>Tamaño</th>
							<th>Fecha</th>
							<th>Tipo</th>
							<th>Descarga</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $files as $file ) : 
							$name      = isset( $file['name'] ) ? (string) $file['name'] : '';
							$size      = isset( $file['size'] ) ? (int) $file['size'] : 0;
							$date_iso  = isset( $file['date'] ) ? (string) $file['date'] : '';
							$type      = isset( $file['type'] ) ? (string) $file['type'] : '';
							$url       = isset( $file['url'] )  ? (string) $file['url']  : '';

							$name_safe = esc_html( $name );
							$type_safe = esc_html( strtoupper( $type ) );
							$url_safe  = esc_url( $url );
							$size_human = size_format( $size );
							$date_disp  = $this->format_date_for_display( $date_iso );
						?>
							<tr>
								<td>
									<strong><?php echo $name_safe; ?></strong>
									<div class="mpt-meta"><?php echo esc_html( $this->relative_path_from_uploads( $url ) ); ?></div>
								</td>
								<td><?php echo esc_html( $size_human ); ?></td>
								<td><?php echo esc_html( $date_disp ); ?></td>
								<td><?php echo $type_safe; ?></td>
								<td>
									<?php if ( ! empty( $url_safe ) ) : ?>
										<a href="<?php echo $url_safe; ?>" download>Descargar</a>
									<?php else : ?>
										<span class="mpt-meta">N/D</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private function get_files_from_index() : array {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return [];
		}

		$base_dir   = trailingslashit( (string) $uploads['basedir'] ) . self::UPLOAD_SUBDIR . '/';
		$index_path = $base_dir . 'index.json';

		if ( ! file_exists( $index_path ) || ! is_readable( $index_path ) ) {
			return [];
		}

		$json = file_get_contents( $index_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $json ) {
			return [];
		}

		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			return [];
		}

		$out = [];
		foreach ( $data as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$name = isset( $item['name'] ) ? sanitize_file_name( (string) $item['name'] ) : '';
			$size = isset( $item['size'] ) ? (int) $item['size'] : 0;
			$date = isset( $item['date'] ) ? (string) $item['date'] : '';
			$type = isset( $item['type'] ) ? sanitize_key( (string) $item['type'] ) : '';
			$url  = isset( $item['url'] )  ? esc_url_raw( (string) $item['url'] ) : '';

			if ( '' === $name || '' === $url ) {
				continue;
			}

			$out[] = [
				'name' => $name,
				'size' => $size,
				'date' => $date,
				'type' => $type,
				'url'  => $url,
			];
		}
		return $out;
	}

	private function format_date_for_display( string $iso ) : string {
		if ( '' === $iso ) {
			return '';
		}
		try {
			$dt = new DateTime( $iso );
			$timestamp = $dt->getTimestamp();
			return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
		} catch ( Exception $e ) {
			return $iso;
		}
	}

	private function relative_path_from_uploads( string $absolute_url ) : string {
		$uploads = wp_upload_dir();
		if ( empty( $uploads['baseurl'] ) ) {
			return $absolute_url;
		}
		$baseurl = trailingslashit( (string) $uploads['baseurl'] );
		if ( 0 === strpos( $absolute_url, $baseurl ) ) {
			return ltrim( substr( $absolute_url, strlen( $baseurl ) ), '/' );
		}
		return $absolute_url;
	}
}
