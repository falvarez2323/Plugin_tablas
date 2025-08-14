<?php
/**
 * Gestiona la tabla personalizada de registros (CRUD + schema).
 * Tabla: {$wpdb->prefix}mpt_registros
 */

if ( ! defined('ABSPATH') ) exit;

class MPT_Records {
    public static $table;

    public static function init() {
        global $wpdb;
        self::$table = $wpdb->prefix . 'mpt_registros';

        // Crear tabla si no existe (al cargar el plugin).
        add_action('plugins_loaded', [__CLASS__, 'maybe_create_table']);
    }

    public static function maybe_create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = self::$table;

        // Verificación simple
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s", $table
        ));

        if ( $exists === $table ) return;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ano SMALLINT UNSIGNED NOT NULL,              -- 'Fecha' (año)
            consecutivo VARCHAR(50) NOT NULL,
            detalle LONGTEXT NOT NULL,
            dirigido_a VARCHAR(255) DEFAULT '' NOT NULL,
            tipo_contratacion VARCHAR(255) DEFAULT '' NOT NULL,
            adjudicado_a VARCHAR(255) DEFAULT '' NOT NULL,
            url_terminos TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ano (ano),
            KEY idx_consecutivo (consecutivo)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /** Sanear y validar datos del registro */
    public static function sanitize_record( $raw ) {
        $data = [];

        $data['ano']               = isset($raw['ano']) ? intval($raw['ano']) : 0;
        $data['consecutivo']       = isset($raw['consecutivo']) ? sanitize_text_field($raw['consecutivo']) : '';
        $data['detalle']           = isset($raw['detalle']) ? wp_kses_post($raw['detalle']) : '';
        $data['dirigido_a']        = isset($raw['dirigido_a']) ? sanitize_text_field($raw['dirigido_a']) : '';
        $data['tipo_contratacion'] = isset($raw['tipo_contratacion']) ? sanitize_text_field($raw['tipo_contratacion']) : '';
        $data['adjudicado_a']      = isset($raw['adjudicado_a']) ? sanitize_text_field($raw['adjudicado_a']) : '';

        $url = isset($raw['url_terminos']) ? trim($raw['url_terminos']) : '';
        if ($url !== '' && ! wp_http_validate_url($url)) {
            // Si no es URL válida, lo dejamos vacío para no romper la UX
            $url = '';
        }
        $data['url_terminos']      = $url ? esc_url_raw($url) : null;

        return $data;
    }

    /** Crear registro */
    public static function insert( $raw ) {
        global $wpdb;
        $data = self::sanitize_record($raw);
        if ( $data['ano'] <= 0 || $data['consecutivo'] === '' || $data['detalle'] === '' ) {
            return new WP_Error('mpt_invalid', 'Faltan campos obligatorios.');
        }
        $ok = $wpdb->insert(self::$table, $data);
        return $ok ? $wpdb->insert_id : false;
    }

    /** Actualizar registro */
    public static function update( $id, $raw ) {
        global $wpdb;
        $id = intval($id);
        if ($id <= 0) return false;
        $data = self::sanitize_record($raw);
        return $wpdb->update(self::$table, $data, ['id' => $id]) !== false;
    }

    /** Obtener registro por ID */
    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::$table . " WHERE id = %d", $id
        ), ARRAY_A );
    }

    /** Eliminar un registro */
    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete(self::$table, ['id' => intval($id)]) !== false;
    }

    /**
     * Listado con paginación/orden/filtros.
     * @return array [items => array, total => int]
     */
    public static function list( $args = [] ) {
        global $wpdb;
        $table = self::$table;

        $paged   = max(1, intval($args['paged'] ?? 1));
        $per     = max(1, min(100, intval($args['per_page'] ?? 20)));
        $offset  = ($paged - 1) * $per;

        $orderby = $args['orderby'] ?? 'created_at';
        $allowed = ['id','ano','consecutivo','created_at','updated_at','dirigido_a','tipo_contratacion','adjudicado_a'];
        if ( ! in_array($orderby, $allowed, true) ) $orderby = 'created_at';

        $order = strtoupper($args['order'] ?? 'DESC');
        if (! in_array($order, ['ASC','DESC'], true)) $order = 'DESC';

        // filtros simples
        $where = " WHERE 1=1 ";
        $binds = [];
        if ( !empty($args['f_ano']) ) {
            $where .= " AND ano = %d ";
            $binds[] = intval($args['f_ano']);
        }
        if ( !empty($args['search']) ) {
            $s = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where .= " AND (consecutivo LIKE %s OR detalle LIKE %s OR dirigido_a LIKE %s OR tipo_contratacion LIKE %s OR adjudicado_a LIKE %s) ";
            array_push($binds, $s, $s, $s, $s, $s);
        }

        // total
        $sql_total = "SELECT COUNT(*) FROM {$table} {$where}";
        $total = $binds ? $wpdb->get_var( $wpdb->prepare($sql_total, $binds) ) : $wpdb->get_var($sql_total);

        // items
        $sql = "SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $binds_items = $binds;
        array_push($binds_items, $per, $offset);

        $items = $binds_items
            ? $wpdb->get_results( $wpdb->prepare($sql, $binds_items), ARRAY_A )
            : $wpdb->get_results( $wpdb->prepare($sql, $per, $offset), ARRAY_A );

        return ['items' => $items, 'total' => intval($total)];
    }
}

MPT_Records::init();
