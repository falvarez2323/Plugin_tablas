+<?php
// Seguridad: impedir acceso directo
if ( ! defined( 'ABSPATH' ) ) exit;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function mpt_test_phpspreadsheet() {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Hola desde PhpSpreadsheet!');
    $sheet->setCellValue('B1', date('Y-m-d H:i:s'));

    $writer = new Xlsx($spreadsheet);

    $file_path = MPT_UPLOAD_DIR . '/test.xlsx';
    $writer->save($file_path);

    echo '<div class="notice notice-success"><p>Archivo generado: <a href="' . esc_url(MPT_UPLOAD_URL . '/test.xlsx') . '" target="_blank">Descargar test.xlsx</a></p></div>';
}
add_action('admin_notices', 'mpt_test_phpspreadsheet');
