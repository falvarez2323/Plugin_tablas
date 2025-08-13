<?php
namespace MPT;

if ( ! defined( 'ABSPATH' ) ) exit;

class Helpers {
    public static function cap_manage() : string {
        // Podremos refinar por rol más adelante
        return 'manage_options';
    }

    public static function nonce_action( string $key ) : string {
        return 'mpt_' . $key . '_nonce_action';
    }

    public static function nonce_name() : string {
        return 'mpt_nonce';
    }

    public static function verify_nonce_or_die( string $key ) : void {
        $name = self::nonce_name();
        $action = self::nonce_action($key);
        if ( ! isset($_POST[$name]) || ! wp_verify_nonce( sanitize_text_field($_POST[$name]), $action ) ) {
            wp_die( esc_html__('Nonce inválido.', 'mi-plugin-tablas') );
        }
    }

    public static function sanitize_file_basename( string $name ) : string {
        $name = wp_basename( $name );
        $name = sanitize_file_name( $name );
        return $name;
    }
}
