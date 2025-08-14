<?php
/**
 * WP_List_Table para la gestión de registros.
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('WP_List_Table') ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MPT_Records_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'registro',
            'plural'   => 'registros',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'                => '<input type="checkbox" />',
            'ano'               => 'Fecha',
            'consecutivo'       => 'Consecutivo',
            'detalle'           => 'Detalle',
            'dirigido_a'        => 'Dirigido a',
            'tipo_contratacion' => 'Tipo de Contratación',
            'adjudicado_a'      => 'Adjudicado a',
            'url_terminos'      => 'Ver términos',
        ];
    }

    protected function get_sortable_columns() {
        return [
            'ano'          => ['ano', true],
            'consecutivo'  => ['consecutivo', false],
        ];
    }

    protected function column_cb($item) {
        return sprintf('<input type="checkbox" name="ids[]" value="%d" />', intval($item['id']));
    }

    protected function column_consecutivo($item) {
        $id = intval($item['id']);
        $edit_url = wp_nonce_url(
            add_query_arg(['page'=>'mpt-registros','action'=>'edit','id'=>$id], admin_url('admin.php')),
            'mpt_edit_'.$id
        );
        $delete_url = wp_nonce_url(
            add_query_arg(['page'=>'mpt-registros','action'=>'delete','id'=>$id], admin_url('admin.php')),
            'mpt_delete_'.$id
        );
        $name = esc_html($item['consecutivo']);
        $actions = [
            'edit'   => '<a href="'.$edit_url.'">Editar</a>',
            'delete' => '<a href="'.$delete_url.'" onclick="return confirm(\'¿Eliminar este registro?\')">Eliminar</a>',
        ];
        return sprintf('<strong><a href="%s">%s</a></strong> %s', $edit_url, $name, $this->row_actions($actions));
    }

    protected function column_detalle($item) {
        $text = wp_strip_all_tags($item['detalle']);
        $text = wp_html_excerpt($text, 140, '&hellip;');
        return esc_html($text);
    }

    protected function column_url_terminos($item) {
        if (empty($item['url_terminos'])) return '—';
        $url = esc_url($item['url_terminos']);
        return '<a class="button button-primary" href="'.$url.'" target="_blank" rel="noopener noreferrer">Ver términos</a>';
    }

    protected function column_default($item, $column_name) {
        if (isset($item[$column_name])) {
            return esc_html($item[$column_name] ?? '—');
        }
        return '—';
    }

    public function get_bulk_actions() {
        return [
            'bulk-delete' => 'Eliminar seleccionados'
        ];
    }

    public function process_bulk_action() {
        if ( 'bulk-delete' === $this->current_action() ) {
            check_admin_referer('mpt_list_bulk');
            $ids = isset($_POST['ids']) ? array_map('intval', (array) $_POST['ids']) : [];
            foreach ($ids as $id) {
                MPT_Records::delete($id);
            }
        }
    }

    public function prepare_items() {
        $per_page = 20;
        $paged  = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $order  = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
        $orderby= isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';

        $f_ano  = isset($_GET['f_ano']) ? intval($_GET['f_ano']) : 0;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $data = MPT_Records::list([
            'paged'   => $paged,
            'per_page'=> $per_page,
            'order'   => $order,
            'orderby' => $orderby,
            'f_ano'   => $f_ano,
            'search'  => $search,
        ]);

        $this->process_bulk_action();

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns(), 'consecutivo'];
        $this->items = $data['items'];

        $this->set_pagination_args([
            'total_items' => $data['total'],
            'per_page'    => $per_page,
            'total_pages' => ceil($data['total'] / $per_page),
        ]);
    }
}
