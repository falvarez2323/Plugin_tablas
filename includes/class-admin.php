<?php
namespace MPT;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {
    private static $instance = null;
    private string $menu_slug = 'mpt_admin';

    public static function get_instance() : self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function register_menu() : void {
        add_menu_page(
            esc_html__( 'Mi Plugin Tablas', 'mi-plugin-tablas' ),
            esc_html__( 'Mi Plugin Tablas', 'mi-plugin-tablas' ),
            Helpers::cap_manage(),
            $this->menu_slug,
            [ $this, 'render_page' ],
            'dashicons-table-col-before',
            30
        );
    }

    public function enqueue_admin_assets( string $hook ) : void {
        // Cargar solo en nuestra página
        if ( $hook !== 'toplevel_page_' . $this->menu_slug ) return;

        wp_enqueue_style(
            'mpt-admin',
            MPT_URL . 'assets/admin.css',
            [],
            MPT_VERSION
        );

        wp_enqueue_script(
            'mpt-admin',
            MPT_URL . 'assets/admin.js',
            [ 'jquery' ],
            MPT_VERSION,
            true
        );

        wp_localize_script( 'mpt-admin', 'MPT_ADMIN', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'upload_url' => defined('MPT_UPLOAD_URL') ? MPT_UPLOAD_URL : '',
        ] );
    }

    public function render_page() : void {
        ?>
        <div class="wrap mpt-wrap">
            <h1><?php echo esc_html__( 'Mi Plugin Tablas', 'mi-plugin-tablas' ); ?></h1>
            <p class="description">
                <?php echo esc_html__( 'Versión base instalada. Próximo paso: formulario de subida y listado.', 'mi-plugin-tablas' ); ?>
            </p>

            <div class="mpt-panel">
                <h2><?php echo esc_html__( 'Estado', 'mi-plugin-tablas' ); ?></h2>
                <ul>
                    <li>PHP: <?php echo esc_html( PHP_VERSION ); ?></li>
                    <li>Carpeta de uploads: <code><?php echo esc_html( defined('MPT_UPLOAD_DIR') ? MPT_UPLOAD_DIR : 'N/D' ); ?></code></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
