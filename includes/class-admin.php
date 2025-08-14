<?php
/**
 * Admin: menú, listado con paginación y eliminar.
 *
 * @package MiPluginTablas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPT_Admin {

	const MENU_SLUG = 'mpt-main';
	const PER_PAGE  = 10; // cambia si quieres más filas por página

	public function init() : void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_post_mpt_delete', [ $this, 'handle_delete' ] );
		// estilos mínimos
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
	}

	public function register_menu() : void {
		add_menu_page(
			'Mi Plugin Tablas',
			'Mi Plugin Tablas',
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'page_main' ],
			'dashicons-table-col-after',
			66
		);
	}

	public function enqueue_admin_styles( $hook ) : void {
		if ( strpos( (string) $hook, self::MENU_SLUG ) === false ) {
			return;
		}
		$css = '
		.mpt-admin-wrap .widefat th, .mpt-admin-wrap .widefat td { vertical-align: top; }
		.mpt-admin-actions { display:flex; gap:.5rem; align-items:center; }
		.mpt-badge { display:inline-block; font-size:11px; padding:2px 6px; background:#eef2ff; border:1px solid #e5e7eb; border-radius:999px;}
		';
		wp_register_style( 'mpt-admin-inline', false, [], MI_PLUGIN_TABLAS_VERSION );
		wp_enqueue_style( 'mpt-admin-inline' );
		wp_add_inline_style( 'mpt-admin-inline', $css );
	}

	public function page_main() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'mi-plugin-tablas' ) );
		}

		$items = mpt_load_index();
		$total = count( $items );

		// Paginación
		$paged = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$per   = self::PER_PAGE;
		$start = ( $paged - 1 ) * $per;
		$rows  = array_slice( $items, $start, $per );

		$delete_nonce = wp_create_nonce( 'mpt_delete_file' );
		$self_url = menu_page_url( self::MENU_SLUG, false );

		echo '<div class="wrap mpt-admin-wrap">';
		echo '<h1>Mi Plugin Tablas <span class="mpt-badge">v' . esc_html( MI_PLUGIN_TABLAS_VERSION ) . '</span></h1>';
		echo '<p>Shortcode: <code>[mpt_registros]</code></p>';

		if ( empty( $items ) ) {
			echo '<div class="notice notice-info"><p>No hay archivos aún. Sube uno desde tu flujo de carga.</p></div>';
			echo '</div>';
			return;
		}

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>Nombre</th><th>Tamaño</th><th>Fecha</th><th>Tipo</th><th>Descarga</th><th style="width:120px;">Acciones</th>';
		echo '</tr></thead><tbody>';

		foreach ( $rows as $it ) {
			$name = esc_html( (string) ( $it['name'] ?? '' ) );
			$size = size_format( (int) ( $it['size'] ?? 0 ) );
			$date = esc_html( (string) ( $it['date'] ?? '' ) );
			$type = esc_html( strtoupper( (string) ( $it['type'] ?? '' ) ) );
			$url  = esc_url( (string) ( $it['url'] ?? '' ) );

			$del_url = wp_nonce_url(
				admin_url( 'admin-post.php?action=mpt_delete&name=' . rawurlencode( (string) ( $it['name'] ?? '' ) ) ),
				'mpt_delete_file'
			);

			echo '<tr>';
			echo '<td><strong>' . $name . '</strong><div style="color:#6b7280;font-size:12px;">' . esc_html( MPT_UPLOAD_SUBDIR . '/' . (string)( $it['name'] ?? '' ) ) . '</div></td>';
			echo '<td>' . esc_html( $size ) . '</td>';
			echo '<td>' . $date . '</td>';
			echo '<td>' . $type . '</td>';
			echo '<td>' . ( $url ? '<a href="' . $url . '" target="_blank" rel="noopener">Descargar</a>' : '<span style="color:#6b7280;">N/D</span>' ) . '</td>';
			echo '<td><div class="mpt-admin-actions">';
			echo '<a class="button button-small" href="' . $del_url . '" onclick="return confirm(\'¿Eliminar ' . esc_js( (string) ( $it['name'] ?? '' ) ) . '?\')">Eliminar</a>';
			// (Próximo paso) Edición inline aquí
			echo '</div></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		// Paginador
		$total_pages = (int) ceil( $total / $per );
		if ( $total_pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			$current = $paged;
			$base = add_query_arg( 'paged', '%#%', $self_url );
			echo paginate_links( [
				'base'      => $base,
				'format'    => '',
				'current'   => $current,
				'total'     => $total_pages,
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
			] );
			echo '</div></div>';
		}

		echo '</div>'; // wrap
	}

	/**
	 * Acción admin_post para eliminar un archivo y actualizar index.json.
	 */
	public function handle_delete() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'mi-plugin-tablas' ) );
		}
		check_admin_referer( 'mpt_delete_file' );

		$name = isset( $_GET['name'] ) ? sanitize_file_name( (string) $_GET['name'] ) : '';
		if ( '' === $name ) {
			wp_safe_redirect( menu_page_url( self::MENU_SLUG, false ) );
			exit;
		}

		$ok = mpt_delete_file_and_index( $name );
		if ( $ok ) {
			add_action( 'admin_notices', function () use ( $name ) {
				echo '<div class="notice notice-success is-dismissible"><p>Archivo eliminado: ' . esc_html( $name ) . '</p></div>';
			} );
		} else {
			add_action( 'admin_notices', function () use ( $name ) {
				echo '<div class="notice notice-error is-dismissible"><p>No se pudo eliminar: ' . esc_html( $name ) . '</p></div>';
			} );
		}

		wp_safe_redirect( menu_page_url( self::MENU_SLUG, false ) );
		exit;
	}
}
