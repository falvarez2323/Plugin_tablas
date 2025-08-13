<?php
/**
 * Plugin Name: Mi Plugin Tablas
 * Description: Gestión y render de tablas (Excel/CSV) con panel de administración.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Tu Nombre
 * Text Domain: mi-plugin-tablas
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MPT_VERSION', '0.1.0' );
define( 'MPT_PATH', plugin_dir_path( __FILE__ ) );
define( 'MPT_URL',  plugin_dir_url( __FILE__ ) );

// Definimos rutas de subida (se crean en activación)
add_action('plugins_loaded', function () {
    $wp_uploads = wp_upload_dir();
    if ( ! defined('MPT_UPLOAD_DIR') ) define( 'MPT_UPLOAD_DIR', trailingslashit( $wp_uploads['basedir'] ) . 'mi-plugin-tablas' );
    if ( ! defined('MPT_UPLOAD_URL') ) define( 'MPT_UPLOAD_URL', trailingslashit( $wp_uploads['baseurl'] ) . 'mi-plugin-tablas' );
});

// Crear carpeta de subidas al activar
register_activation_hook( __FILE__, function () {
    if ( ! current_user_can('activate_plugins') ) return;
    $wp_uploads = wp_upload_dir();
    $dir = trailingslashit( $wp_uploads['basedir'] ) . 'mi-plugin-tablas';
    if ( ! file_exists( $dir ) ) wp_mkdir_p( $dir );
});

// Carga de autoloader (Composer) si existe
if ( file_exists( MPT_PATH . 'vendor/autoload.php' ) ) {
    require_once MPT_PATH . 'vendor/autoload.php';
}

// Includes base
require_once MPT_PATH . 'includes/helpers.php';
require_once MPT_PATH . 'includes/class-admin.php';
require_once MPT_PATH . 'includes/class-frontend.php';

// Bootstrap
add_action( 'plugins_loaded', function () {
    MPT\Admin::get_instance();
    MPT\Frontend::get_instance();
});
