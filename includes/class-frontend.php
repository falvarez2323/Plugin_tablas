<?php
namespace MPT;

if ( ! defined( 'ABSPATH' ) ) exit;

class Frontend {
    private static $instance = null;

    public static function get_instance() : self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Aquí registraremos shortcodes y assets públicos más adelante.
        add_shortcode( 'mpt_table', [ $this, 'shortcode_table' ] );
    }

    public function shortcode_table( $atts = [] ) : string {
        $atts = shortcode_atts( [
            'id' => '',
        ], $atts );

        if ( empty( $atts['id'] ) ) {
            return '<div class="mpt-notice">[mpt_table] requiere atributo id.</div>';
        }

        // Próximamente: leer caché/archivo parseado y renderizar tabla.
        return '<div class="mpt-table-placeholder">Tabla ID ' . esc_html($atts['id']) . ' (frontend en preparación)</div>';
    }
}
