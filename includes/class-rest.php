<?php
/**
 * REST API para pruebas de render del shortcode.
 *
 * @package MiPluginTablas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPT_Rest {

	const NAMESPACE = 'mpt/v1';

	public function init() : void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() : void {
		register_rest_route(
			self::NAMESPACE,
			'/preview',
			[
				'methods'             => \WP_REST_Server::READABLE, // GET
				'callback'            => [ $this, 'handle_preview' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'shortcode' => [
						'type'              => 'string',
						'required'          => false,
						'default'           => '[mpt_registros]',
						'sanitize_callback' => [ $this, 'sanitize_shortcode' ],
					],
				],
			]
		);
	}

	public function check_permissions( \WP_REST_Request $request ) : bool {
		if ( defined( 'MI_PLUGIN_TABLAS_DEV' ) && true === MI_PLUGIN_TABLAS_DEV ) {
			return true;
		}
		return current_user_can( 'read' );
	}

	public function sanitize_shortcode( string $shortcode ) : string {
		$shortcode = trim( wp_strip_all_tags( $shortcode ) );
		if ( ! preg_match( '/^\[mpt_registros(\s+[^\]]+)?\]$/i', $shortcode ) ) {
			return '[mpt_registros]';
		}
		return $shortcode;
	}

	public function handle_preview( \WP_REST_Request $request ) : \WP_REST_Response {
		$shortcode = (string) $request->get_param( 'shortcode' );

		// Buffer local (solo captura salida DURANTE el render).
		ob_start();
		$exists = shortcode_exists( 'mpt_registros' );

		$html = do_shortcode( $shortcode );
		$local_output = ob_get_clean();

		// Si el shortcode no existe, do_shortcode devolverá la cadena original.
		$shortcode_worked = $exists && ( $html !== $shortcode );

		$allowed = wp_kses_allowed_html( 'post' );
		$clean   = wp_kses( $html, $allowed );

		$resp = [
			'ok'        => true,
			'shortcode' => $shortcode,
			'html'      => $clean,
			'debug'     => [
				'shortcode_registered' => $exists,
				'shortcode_worked'     => $shortcode_worked,
				'local_output'         => ( $local_output !== '' ), // salida dentro del render
				// Nota: salida previa al callback (BOM/echo en otros archivos) no se puede capturar aquí.
			],
		];

		return new \WP_REST_Response( $resp, 200 );
	}
}
