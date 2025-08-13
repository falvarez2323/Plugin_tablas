<?php
namespace MPT;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {
    private static $instance = null;
    private string $menu_slug = 'mpt_admin';

    public static function get_instance() : self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Registrar el menú en el admin
     */
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

    /**
     * Cargar CSS y JS en el admin
     */
    public function enqueue_admin_assets( string $hook ) : void {
        // Solo cargar en la página de nuestro plugin
        if ( $hook !== 'toplevel_page_' . $this->menu_slug ) {
            return;
        }

        // CSS para el admin
        wp_enqueue_style(
            'mpt-admin',
            MPT_URL . 'assets/admin.css',
            [],
            MPT_VERSION
        );

        // JS para el admin
        wp_enqueue_script(
            'mpt-admin',
            MPT_URL . 'assets/admin.js',
            [ 'jquery' ],
            MPT_VERSION,
            true
        );

        // Pasar variables PHP a JS
        wp_localize_script(
            'mpt-admin',
            'MPT_ADMIN',
            [
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'upload_url' => defined('MPT_UPLOAD_URL') ? MPT_UPLOAD_URL : '',
                'nonce'      => wp_create_nonce( Helpers::nonce_action('admin') ),
            ]
        );
    }

    /**
     * Vista del admin
     */
    public function render_page() : void {
        ?>
        <div class="wrap mpt-wrap">
            <h1><?php echo esc_html__( 'Mi Plugin Tablas', 'mi-plugin-tablas' ); ?></h1>
            <p class="description">
                <?php echo esc_html__( 'Panel de administración. Próximo paso: subir y listar Excel/CSV.', 'mi-plugin-tablas' ); ?>
            </p>

            <div class="mpt-panel">
                <h2><?php echo esc_html__( 'Estado', 'mi-plugin-tablas' ); ?></h2>
                <ul>
                    <li>PHP: <strong><?php echo esc_html( PHP_VERSION ); ?></strong></li>
                    <li>PhpSpreadsheet: <strong><?php echo class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet') ? 'Activo' : 'No cargado'; ?></strong></li>
                    <li>Uploads: <code><?php echo esc_html( defined('MPT_UPLOAD_DIR') ? MPT_UPLOAD_DIR : 'N/D' ); ?></code></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
// Intencionalmente sin etiqueta de cierre PHP
