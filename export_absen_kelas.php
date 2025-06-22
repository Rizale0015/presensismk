<?php
require '../vendor/autoload.php'; // Atur sesuai letak folder 'vendor'


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include 'database.php';

if (isset($_GET['kelas'])) {
    $kelas = $_GET['kelas'];
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Judul kolom
    $sheet->setCellValue('A1', 'Nama Siswa');
    $sheet->setCellValue('B1', 'Tanggal');
    $sheet->setCellValue('C1', 'Status');

    $query = "SELECT s.nama_siswa, a.tanggal, a.status
              FROM absensi a
              JOIN siswa s ON a.id_siswa = s.id
              WHERE s.kelas = '$kelas'
              ORDER BY a.tanggal DESC";

    $result = mysqli_query($conn, $query);
    $row = 2;

    while ($data = mysqli_fetch_assoc($result)) {
        $sheet->setCellValue("A$row", $data['nama_siswa']);
        $sheet->setCellValue("B$row", $data['tanggal']);
        $sheet->setCellValue("C$row", $data['status']);
        $row++;
    }

    $filename = "Laporan_Absensi_$kelas.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} else {
    echo "Kelas tidak ditemukan.";
}
