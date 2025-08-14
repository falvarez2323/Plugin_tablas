<?php
/**
 * Helpers comunes para el plugin.
 *
 * @package MiPluginTablas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MPT_UPLOAD_SUBDIR = 'mi-plugin-tablas';

/**
 * Ruta absoluta del subdirectorio del plugin en uploads.
 */
function mpt_get_upload_dir_path(): string {
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return '';
	}
	return trailingslashit( (string) $uploads['basedir'] ) . MPT_UPLOAD_SUBDIR . '/';
}

/**
 * URL base del subdirectorio del plugin en uploads.
 */
function mpt_get_upload_dir_url(): string {
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return '';
	}
	return trailingslashit( (string) $uploads['baseurl'] ) . MPT_UPLOAD_SUBDIR . '/';
}

/**
 * Carga el index.json como array seguro.
 *
 * @return array<int,array<string,mixed>>
 */
function mpt_load_index(): array {
	$base = mpt_get_upload_dir_path();
	if ( '' === $base ) {
		return [];
	}
	$index = $base . 'index.json';
	if ( ! file_exists( $index ) || ! is_readable( $index ) ) {
		return [];
	}
	$json = file_get_contents( $index ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
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

/**
 * Guarda el array en index.json (atomic write).
 *
 * @param array<int,array<string,mixed>> $items
 * @return bool
 */
function mpt_save_index( array $items ): bool {
	$base = mpt_get_upload_dir_path();
	if ( '' === $base ) {
		return false;
	}
	if ( ! file_exists( $base ) ) {
		wp_mkdir_p( $base );
	}
	$index = $base . 'index.json';
	$tmp   = $index . '.tmp';

	$json = wp_json_encode( array_values( $items ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	if ( false === file_put_contents( $tmp, $json ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		return false;
	}
	if ( ! rename( $tmp, $index ) ) {
		@unlink( $tmp );
		return false;
	}
	return true;
}

/**
 * Elimina un archivo físico (si existe) y lo quita del index.json.
 *
 * @param string $name Nombre del archivo (basename).
 * @return bool
 */
function mpt_delete_file_and_index( string $name ): bool {
	$name = sanitize_file_name( $name );
	if ( '' === $name ) {
		return false;
	}
	$items = mpt_load_index();
	$base  = mpt_get_upload_dir_path();
	$url_base = mpt_get_upload_dir_url();

	$changed = false;
	$new = [];
	foreach ( $items as $it ) {
		if ( isset( $it['name'] ) && $it['name'] === $name ) {
			// eliminar archivo físico por path seguro
			$expected = $base . $name;
			if ( file_exists( $expected ) && strpos( realpath( $expected ) ?: '', realpath( $base ) ?: '' ) === 0 ) {
				@unlink( $expected );
			}
			$changed = true;
			continue; // no agregues al nuevo index
		}
		$new[] = $it;
	}

	if ( $changed ) {
		return mpt_save_index( $new );
	}
	return false;
}
