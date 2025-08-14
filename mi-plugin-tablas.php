<?php
/**
 * Plugin Name: Mi Plugin Tablas
 * Description: Tablas desde Excel/CSV con admin moderno.
 * Version: 0.1.0
 * Author: Tu Nombre
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MI_PLUGIN_TABLAS_VERSION', '0.1.0' );
define( 'MI_PLUGIN_TABLAS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MI_PLUGIN_TABLAS_URL', plugin_dir_url( __FILE__ ) );

// DEV flag para pruebas locales.
if ( ! defined( 'MI_PLUGIN_TABLAS_DEV' ) ) {
	define( 'MI_PLUGIN_TABLAS_DEV', true );
}

/*
 * Cargar clases (asegurarse de que los archivos existen).
 */
$includes = [
	MI_PLUGIN_TABLAS_PATH . 'includes/class-admin.php',
	MI_PLUGIN_TABLAS_PATH . 'includes/class-frontend.php',
	MI_PLUGIN_TABLAS_PATH . 'includes/class-rest.php',
	MI_PLUGIN_TABLAS_PATH . 'includes/helpers.php',
];

foreach ( $includes as $inc ) {
	if ( file_exists( $inc ) ) {
		require_once $inc;
	}
}

/**
 * Bootstrap: instanciar clases en plugins_loaded (prioridad temprana).
 */
add_action( 'plugins_loaded', function () {
	// Admin
	if ( class_exists( 'MPT_Admin' ) ) {
		$GLOBALS['mpt_admin'] = new MPT_Admin();
		$GLOBALS['mpt_admin']->init();
	}

	// Frontend
	if ( class_exists( 'MPT_Frontend' ) ) {
		$GLOBALS['mpt_frontend'] = new MPT_Frontend();
		$GLOBALS['mpt_frontend']->init();
	}

	// REST
	if ( class_exists( 'MPT_Rest' ) ) {
		$GLOBALS['mpt_rest'] = new MPT_Rest();
		$GLOBALS['mpt_rest']->init();
	}
}, 5);
